<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Fixture;
use App\Models\PlayerProfile;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClubDemoSeeder extends Seeder
{
    private const CLUB_SLUG = 'greenwood-rangers-cc';

    private const CLUB_NAME = 'Greenwood Rangers Cricket Club';

    private const EMAIL_DOMAIN = 'greenwoodrangers.test';

    public function run(): void
    {
        DB::transaction(function () {
            $owner = User::updateOrCreate(
                ['email' => 'club@cricket-os.test'],
                [
                    'first_name' => 'Thomas',
                    'last_name' => 'Wright',
                    'phone' => '+447700900001',
                    'password' => '12345678',
                    'user_type' => 'club',
                    'is_active' => true,
                    'is_onboarded' => true,
                    'email_verified_at' => now(),
                ]
            );

            $club = Club::updateOrCreate(
                ['slug' => self::CLUB_SLUG],
                [
                    'owner_id' => $owner->id,
                    'name' => self::CLUB_NAME,
                    'short_name' => 'GRCC',
                    'description' => 'A community cricket club based in Manchester, playing league and friendly matches across the North West.',
                    'country' => 'United Kingdom',
                    'city' => 'Manchester',
                    'address' => 'Deansgate, Manchester',
                    'website' => 'https://greenwoodrangers.example',
                    'founded_year' => '2012',
                    'is_public' => true,
                    'is_verified' => true,
                    'show_public_profiles' => true,
                ]
            );

            ClubMember::updateOrCreate(
                ['club_id' => $club->id, 'user_id' => $owner->id],
                [
                    'role' => 'owner',
                    'status' => 'active',
                    'joined_at' => now()->subYears(3),
                ]
            );

            $firstXi = Team::updateOrCreate(
                ['club_id' => $club->id, 'slug' => 'first-xi'],
                [
                    'name' => 'Greenwood Rangers First XI',
                    'short_name' => 'GRXI',
                    'primary_color' => '#1e3a5f',
                    'secondary_color' => '#16a34a',
                    'is_active' => true,
                ]
            );

            Team::updateOrCreate(
                ['club_id' => $club->id, 'slug' => 'second-xi'],
                [
                    'name' => 'Greenwood Rangers Second XI',
                    'short_name' => 'GR2',
                    'primary_color' => '#065f46',
                    'secondary_color' => '#ffffff',
                    'is_active' => true,
                ]
            );

            $players = $this->seedPlayers($club);
            $this->seedTeamMembers($firstXi, $players);
            $this->seedFixtures($club, $owner, $firstXi, $players);
            $this->seedFreeAgentPlayers();
        });
    }

    private function seedFreeAgentPlayers(): void
    {
        $freeAgents = [
            ['first_name' => 'Daniel', 'last_name' => 'Reed', 'role' => 'batsman', 'batting' => 'right_hand', 'bowling' => 'right_arm_medium'],
            ['first_name' => 'Samuel', 'last_name' => 'Grant', 'role' => 'all_rounder', 'batting' => 'left_hand', 'bowling' => 'left_arm_orthodox'],
            ['first_name' => 'Matthew', 'last_name' => 'Stone', 'role' => 'bowler', 'batting' => 'right_hand', 'bowling' => 'right_arm_fast'],
        ];

        foreach ($freeAgents as $index => $data) {
            $email = Str::slug($data['first_name'] . '.' . $data['last_name']) . '@freeagents.test';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => '+4477008' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                    'password' => '12345678',
                    'user_type' => 'player',
                    'is_active' => true,
                    'is_onboarded' => true,
                    'email_verified_at' => now(),
                ]
            );

            PlayerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'primary_role' => $data['role'],
                    'batting_style' => $data['batting'],
                    'bowling_style' => $data['bowling'],
                    'bio' => 'Available player on the platform.',
                    'is_public_profile' => true,
                ]
            );
        }
    }

    private function seedPlayers(Club $club): array
    {
        $roster = [
            ['first_name' => 'Oliver', 'last_name' => 'Clarke', 'role' => 'batsman', 'batting' => 'right_hand', 'bowling' => 'right_arm_off_break'],
            ['first_name' => 'Harry', 'last_name' => 'Turner', 'role' => 'all_rounder', 'batting' => 'right_hand', 'bowling' => 'right_arm_medium'],
            ['first_name' => 'George', 'last_name' => 'Mitchell', 'role' => 'bowler', 'batting' => 'right_hand', 'bowling' => 'right_arm_fast'],
            ['first_name' => 'Ethan', 'last_name' => 'Brooks', 'role' => 'wicket_keeper', 'batting' => 'right_hand', 'bowling' => null],
            ['first_name' => 'Noah', 'last_name' => 'Fletcher', 'role' => 'batsman', 'batting' => 'left_hand', 'bowling' => 'left_arm_orthodox'],
            ['first_name' => 'Leo', 'last_name' => 'Bennett', 'role' => 'all_rounder', 'batting' => 'right_hand', 'bowling' => 'right_arm_leg_break'],
            ['first_name' => 'Arthur', 'last_name' => 'Hayes', 'role' => 'bowler', 'batting' => 'right_hand', 'bowling' => 'left_arm_fast_medium'],
            ['first_name' => 'William', 'last_name' => 'Cross', 'role' => 'batsman', 'batting' => 'right_hand', 'bowling' => 'right_arm_medium'],
            ['first_name' => 'Henry', 'last_name' => 'Morris', 'role' => 'all_rounder', 'batting' => 'left_hand', 'bowling' => 'left_arm_medium'],
            ['first_name' => 'Jack', 'last_name' => 'Palmer', 'role' => 'bowler', 'batting' => 'right_hand', 'bowling' => 'right_arm_fast_medium'],
            ['first_name' => 'Charlie', 'last_name' => 'Quinn', 'role' => 'batsman', 'batting' => 'right_hand', 'bowling' => 'right_arm_off_break'],
        ];

        $players = [];

        foreach ($roster as $index => $data) {
            $email = Str::slug($data['first_name'] . '.' . $data['last_name']) . '@' . self::EMAIL_DOMAIN;
            $phoneSuffix = str_pad((string) ($index + 2), 7, '0', STR_PAD_LEFT);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => '+4477009' . $phoneSuffix,
                    'password' => '12345678',
                    'user_type' => 'player',
                    'is_active' => true,
                    'is_onboarded' => true,
                    'email_verified_at' => now(),
                ]
            );

            PlayerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'primary_role' => $data['role'],
                    'batting_style' => $data['batting'],
                    'bowling_style' => $data['bowling'],
                    'bio' => 'Member of ' . self::CLUB_NAME . '.',
                    'total_matches' => fake()->numberBetween(15, 80),
                    'total_runs' => fake()->numberBetween(200, 2500),
                    'total_wickets' => fake()->numberBetween(5, 120),
                    'is_public_profile' => true,
                ]
            );

            ClubMember::updateOrCreate(
                ['club_id' => $club->id, 'user_id' => $user->id],
                [
                    'role' => 'player',
                    'status' => 'active',
                    'joined_at' => now()->subMonths(fake()->numberBetween(3, 24)),
                ]
            );

            $players[] = $user;
        }

        return $players;
    }

    private function seedTeamMembers(Team $team, array $players): void
    {
        foreach ($players as $index => $player) {
            $role = $index === 0 ? 'captain' : 'player';

            TeamMember::updateOrCreate(
                ['team_id' => $team->id, 'user_id' => $player->id],
                [
                    'role' => $role,
                    'jersey_number' => $index + 1,
                    'is_active' => true,
                    'joined_at' => now()->subYear(),
                ]
            );
        }
    }

    private function seedFixtures(Club $club, User $owner, Team $team, array $players): void
    {
        $oldTrafford = Venue::where('name', 'Old Trafford')->first();
        $riverside = Venue::where('name', 'Riverside Community Ground')->first();

        $fixtures = [
            [
                'opponent' => 'Cambridge Coyotes',
                'club_plays_home' => true,
                'venue_id' => $riverside?->id,
                'scheduled_date' => now()->addDays(14)->toDateString(),
                'scheduled_time' => '15:00',
                'match_type' => 't20',
                'overs_per_innings' => 20,
                'status' => 'draft',
                'is_public' => false,
                'published_at' => null,
            ],
            [
                'opponent' => 'Bristol Bears CC',
                'club_plays_home' => true,
                'venue_id' => $oldTrafford?->id,
                'scheduled_date' => now()->addDays(5)->toDateString(),
                'scheduled_time' => '18:30',
                'match_type' => 't20',
                'overs_per_innings' => 20,
                'status' => 'published',
                'is_public' => true,
                'published_at' => now()->subDay(),
            ],
            [
                'opponent' => 'Leeds Lightning',
                'club_plays_home' => false,
                'venue_id' => Venue::where('city', 'Birmingham')->first()?->id,
                'scheduled_date' => now()->addDays(2)->toDateString(),
                'scheduled_time' => '16:00',
                'match_type' => 't10',
                'overs_per_innings' => 10,
                'status' => 'published',
                'is_public' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'opponent' => 'Nottingham Navigators',
                'club_plays_home' => true,
                'venue_id' => $riverside?->id,
                'scheduled_date' => now()->subDays(7)->toDateString(),
                'scheduled_time' => '14:00',
                'match_type' => 't20',
                'overs_per_innings' => 20,
                'status' => 'completed',
                'is_public' => true,
                'published_at' => now()->subDays(10),
                'started_at' => now()->subDays(7)->setTime(14, 0),
                'completed_at' => now()->subDays(7)->setTime(18, 15),
                'home_team_runs' => 178,
                'home_team_wickets' => 6,
                'home_team_overs' => 20.0,
                'away_team_runs' => 172,
                'away_team_wickets' => 9,
                'away_team_overs' => 20.0,
                'winner_team_id' => $team->id,
                'result_type' => 'runs',
                'result_margin' => 6,
                'man_of_the_match_id' => $players[0]->id,
            ],
        ];

        foreach ($fixtures as $data) {
            $clubPlaysHome = $data['club_plays_home'];
            $opponent = $data['opponent'];
            unset($data['opponent']);

            $payload = array_merge($data, [
                'club_id' => $club->id,
                'created_by' => $owner->id,
                'ball_type' => 'leather',
                'club_plays_home' => $clubPlaysHome,
                'home_team_id' => $clubPlaysHome ? $team->id : null,
                'away_team_id' => $clubPlaysHome ? null : $team->id,
                'home_opponent_name' => $clubPlaysHome ? null : $opponent,
                'away_opponent_name' => $clubPlaysHome ? $opponent : null,
                'public_share_slug' => Str::uuid()->toString(),
            ]);

            $existing = Fixture::where('club_id', $club->id)
                ->where('away_opponent_name', $payload['away_opponent_name'])
                ->where('home_opponent_name', $payload['home_opponent_name'])
                ->where('scheduled_date', $payload['scheduled_date'])
                ->first();

            if ($existing) {
                $existing->update($payload);
                continue;
            }

            Fixture::create($payload);
        }
    }
}
