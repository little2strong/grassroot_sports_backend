<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\FixtureImport;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    public function squads(Request $request, ?int $clubId = null): JsonResponse
    {
        $user = $request->user();

        if (!$clubId && $user && $user->user_type === 'club') {
            $club = $user->ownedClub()->first();
        } elseif ($clubId) {
            $club = Club::query()->find($clubId);
        } else {
            return response()->json(['message' => 'Club id required'], 422);
        }

        if (!$club) {
            return response()->json(['message' => 'Club not found'], 404);
        }

        $teams = Team::query()->where('club_id', $club->id)->orderBy('name')->get();

        $data = $teams->map(function (Team $team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'short_name' => $team->short_name,
                'primary_color' => $team->primary_color,
                'secondary_color' => $team->secondary_color,
                'is_active' => (bool)$team->is_active,
                'player_count' => $team->playerCount(),
            ];
        });

        return response()->json(['data' => ['club' => ['id' => $club->id, 'name' => $club->name], 'squads' => $data]]);
    }

    public function squadPlayers(Request $request, int $teamId): JsonResponse
    {
        $team = Team::with(['players' => function ($q) {
            $q->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
                ->with('playerProfile');
        }])->find($teamId);

        if (!$team) {
            return response()->json(['message' => 'Squad not found'], 404);
        }

        $players = $team->players->map(function ($user) {
            $profile = $user->playerProfile;
            $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            return [
                'id' => $user->id,
                'name' => $displayName ?: $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'primary_role' => $profile->primary_role ?? null,
                'batting_style' => $profile->batting_style ?? null,
                'bowling_style' => $profile->bowling_style ?? null,
                'role_label' => $profile?->role_label,
                'batting_style_label' => $profile?->batting_style_label,
                'bowling_style_label' => $profile?->bowling_style_label,
                'role' => $user->pivot->role ?? 'player',
                'jersey_number' => $user->pivot->jersey_number,
                'joined_at' => optional($user->pivot->joined_at)->toIso8601String(),
            ];
        });

        $roleCounts = $team->players->reduce(function ($counts, $user) {
            $role = $user->playerProfile?->primary_role;

            if ($role === 'batsman') {
                $counts['batsman']++;
            } elseif ($role === 'bowler') {
                $counts['bowler']++;
            } elseif ($role === 'wicket_keeper') {
                $counts['wicket_keeper']++;
            } elseif ($role === 'all_rounder') {
                $counts['all_rounder']++;
            }

            return $counts;
        }, ['batsman' => 0, 'bowler' => 0, 'wicket_keeper' => 0, 'all_rounder' => 0]);

        return response()->json([
            'data' => [
                'team' => ['id' => $team->id, 'name' => $team->name],
                'players' => $players,
                'counts' => $roleCounts,
            ],
        ]);
    }

    public function createFixture(Request $request, int $clubId): JsonResponse
    {
        $club = $this->resolveClub($request, $clubId);

        if (!$club) {
            return response()->json(['message' => 'Club not found or access denied.'], 404);
        }

        $validated = $request->validate([
            'home_team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($query) => $query->where('club_id', $club->id)),
            ],
            'away_team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(fn ($query) => $query->where('club_id', $club->id)),
            ],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'match_type' => ['required', Rule::in(['t10', 't20', 'odi_50', 'odi_40', 'test', 'custom'])],
            'overs_per_innings' => 'nullable|integer|min:1|max:500',
            'ball_type' => ['required', Rule::in(['leather', 'tennis', 'tape'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'live', 'paused', 'completed', 'abandoned', 'cancelled', 'postponed'])],
            'is_public' => 'sometimes|boolean',
        ]);

        if ($validated['home_team_id'] === $validated['away_team_id']) {
            return response()->json(['message' => 'Home and away team must be different.'], 422);
        }

        $fixture = Fixture::create(array_merge($validated, [
            'club_id' => $club->id,
            'created_by' => $request->user()->id,
            'status' => $validated['status'] ?? 'draft',
            'is_public' => $validated['is_public'] ?? true,
        ]));

        return response()->json([
            'message' => 'Fixture created successfully.',
            'data' => ['fixture' => $fixture],
        ], 201);
    }

    public function importFixtures(Request $request, int $clubId): JsonResponse
    {
        $club = $this->resolveClub($request, $clubId);

        if (!$club) {
            return response()->json(['message' => 'Club not found or access denied.'], 404);
        }

        $validated = $request->validate([
            'fixture_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('fixture_file');
        $rows = $this->readCsvRows($file->getRealPath());

        $import = FixtureImport::create([
            'club_id' => $club->id,
            'imported_by' => $request->user()->id,
            'source_type' => 'csv',
            'file_path' => '',
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'processing',
            'total_extracted' => count($rows),
            'total_imported' => 0,
            'total_failed' => 0,
            'started_processing_at' => now(),
        ]);

        $importPath = $this->storeUpload($request, 'fixture_file', 'fixture_imports');
        $import->update(['file_path' => $importPath]);

        $results = $this->processFixtureImportRows($club, $request->user()->id, $rows);

        $import->update([
            'parsed_fixtures' => $results['parsed_fixtures'],
            'errors' => $results['errors'],
            'total_imported' => $results['imported'],
            'total_failed' => $results['failed'],
            'status' => $results['failed'] > 0 ? 'partial' : 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Fixture import completed.',
            'data' => [
                'import_id' => $import->id,
                'total_extracted' => $import->total_extracted,
                'total_imported' => $import->total_imported,
                'total_failed' => $import->total_failed,
                'errors' => $import->errors,
            ],
        ], 201);
    }

    private function resolveClub(Request $request, int $clubId): ?Club
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'club') {
            return null;
        }

        $club = $user->ownedClub()->first();

        return $club && $club->id === $clubId ? $club : null;
    }

    private function readCsvRows(string $path): array
    {
        $rows = [];

        if (!file_exists($path) || ($handle = fopen($path, 'r')) === false) {
            return $rows;
        }

        $header = null;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($column) => trim(strtolower($column)), $row);
                continue;
            }

            if (count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
                continue;
            }

            $rows[] = array_combine($header, array_map(fn ($value) => trim($value), $row));
        }

        fclose($handle);

        return $rows;
    }

    private function processFixtureImportRows(Club $club, int $userId, array $rows): array
    {
        $imported = 0;
        $failed = 0;
        $parsedFixtures = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $result = $this->buildFixtureFromRow($club, $row);

            if (!$result['valid']) {
                $failed++;
                $errors[] = ['row' => $rowNumber, 'errors' => $result['errors']];
                continue;
            }

            $fixture = Fixture::create(array_merge($result['data'], [
                'club_id' => $club->id,
                'created_by' => $userId,
                'status' => $result['data']['status'] ?? 'draft',
                'is_public' => $result['data']['is_public'] ?? true,
            ]));

            $imported++;
            $parsedFixtures[] = ['row' => $rowNumber, 'fixture_id' => $fixture->id];
        }

        return compact('imported', 'failed', 'parsedFixtures', 'errors');
    }

    private function buildFixtureFromRow(Club $club, array $row): array
    {
        $errors = [];

        $homeTeam = $this->resolveTeamFromRow($club, $row, 'home');
        $awayTeam = $this->resolveTeamFromRow($club, $row, 'away');

        if (!$homeTeam) {
            $errors[] = 'Invalid home team';
        }

        if (!$awayTeam) {
            $errors[] = 'Invalid away team';
        }

        if ($homeTeam && $awayTeam && $homeTeam->id === $awayTeam->id) {
            $errors[] = 'Home and away team must be different';
        }

        $scheduledDate = $row['scheduled_date'] ?? null;
        $scheduledTime = $row['scheduled_time'] ?? null;
        $matchType = $row['match_type'] ?? 't20';
        $oversPerInnings = $row['overs_per_innings'] ?? 20;
        $ballType = $row['ball_type'] ?? 'leather';
        $status = $row['status'] ?? 'draft';
        $isPublic = $this->normalizeBoolean($row['is_public'] ?? '1');

        if (!$scheduledDate || !strtotime($scheduledDate)) {
            $errors[] = 'Invalid scheduled_date';
        }

        if ($scheduledTime && !preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $scheduledTime)) {
            $errors[] = 'Invalid scheduled_time';
        }

        if (!in_array($matchType, ['t10', 't20', 'odi_50', 'odi_40', 'test', 'custom'], true)) {
            $errors[] = 'Invalid match_type';
        }

        if (!is_numeric($oversPerInnings) || $oversPerInnings < 1) {
            $errors[] = 'Invalid overs_per_innings';
        }

        if (!in_array($ballType, ['leather', 'tennis', 'tape'], true)) {
            $errors[] = 'Invalid ball_type';
        }

        if (!in_array($status, ['draft', 'published', 'live', 'paused', 'completed', 'abandoned', 'cancelled', 'postponed'], true)) {
            $errors[] = 'Invalid status';
        }

        if ($errors) {
            return ['valid' => false, 'errors' => $errors];
        }

        $venueId = isset($row['venue_id']) && is_numeric($row['venue_id']) ? (int)$row['venue_id'] : null;

        return [
            'valid' => true,
            'data' => [
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'venue_id' => $venueId,
                'scheduled_date' => $scheduledDate,
                'scheduled_time' => $scheduledTime,
                'match_type' => $matchType,
                'overs_per_innings' => (int)$oversPerInnings,
                'ball_type' => $ballType,
                'status' => $status,
                'is_public' => $isPublic,
            ],
        ];
    }

    private function resolveTeamFromRow(Club $club, array $row, string $prefix): ?Team
    {
        $idKey = "{$prefix}_team_id";
        $slugKey = "{$prefix}_team_slug";

        if (!empty($row[$idKey]) && is_numeric($row[$idKey])) {
            return Team::where('id', (int)$row[$idKey])->where('club_id', $club->id)->first();
        }

        if (!empty($row[$slugKey])) {
            return Team::where('slug', $row[$slugKey])->where('club_id', $club->id)->first();
        }

        return null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function storeUpload(Request $request, string $field, string $folder): string
    {
        $file = $request->file($field);
        $storagePath = public_path('uploads/' . trim($folder, '/'));

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($storagePath, $fileName);

        return 'uploads/' . trim($folder, '/') . '/' . $fileName;
    }
}
