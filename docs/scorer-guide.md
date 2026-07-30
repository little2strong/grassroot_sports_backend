# Full Scorer Guide — Cricket OS

> This document explains the **complete scoring process** in Cricket OS so that app developers can understand the data flow, API endpoints, database schema, and how to build a scoring client (mobile/web) on top of the backend.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture & Key Files](#2-architecture--key-files)
3. [Database Schema](#3-database-schema)
4. [Player Reference System (Club vs Opponent)](#4-player-reference-system-club-vs-opponent)
5. [The Scoring Workflow](#5-the-scoring-workflow)
6. [Scorer API Endpoints](#6-scorer-api-endpoints)
7. [Public Match API Endpoints](#7-public-match-api-endpoints)
8. [Ball Event Types & Processing](#8-ball-event-types--processing)
9. [Innings State Management](#9-innings-state-management)
10. [Live State Response Structure](#10-live-state-response-structure)
11. [Public Scorecard Structure](#11-public-scorecard-structure)
12. [Error Handling](#12-error-handling)
13. [End-to-End Scoring Flow Example](#13-end-to-end-scoring-flow-example)
14. [Mobile App Integration Examples](#14-mobile-app-integration-examples)

---

## 1. Overview

Cricket OS provides a **ball-by-ball scoring system** for cricket matches. A scorer (assigned to a fixture) can:

- Check match readiness
- Record the toss
- Start the match with openers
- Record each ball (runs, extras, wickets, boundaries)
- Change bowlers and batters
- End innings and start the second innings
- Pause/resume the match

The system supports **two types of teams**:
- **Club team** — registered users with `user_id` references
- **Opponent team** — external players referenced by `player_index` (array index in the opponent players list)

All scoring operations are wrapped in **database transactions** to ensure data consistency.

---

## 2. Architecture & Key Files

### Backend Files

| File | Purpose |
|------|---------|
| `app/Services/ScoringService.php` | Core scoring logic — readiness, toss, start match, record ball, change bowler/batter, end innings |
| `app/Http/Controllers/Api/ScorerController.php` | API controller for scorer endpoints (authenticated) |
| `app/Services/PublicMatchService.php` | Public-facing scorecard formatting (no auth required) |
| `app/Http/Controllers/Api/PublicMatchController.php` | API controller for public match endpoints |
| `routes/api.php` | Route definitions for scorer and public endpoints |

### Models

| Model | Table | Description |
|-------|-------|-------------|
| `Fixture` | `fixtures` | The match fixture (teams, toss, status, scores) |
| `Matchs` | `matches` | The live match record (current innings, over, ball cursor) |
| `Innings` | `innings` | A single innings (runs, wickets, overs, striker, bowler) |
| `BallEvent` | `ball_events` | Each ball bowled (event type, runs, extras, wicket) |
| `BattingScore` | `batting_scores` | Per-batter batting stats for an innings |
| `BowlingFigure` | `bowling_figures` | Per-bowler bowling stats for an innings |
| `Wicket` | `wickets` | Wicket details (dismissal type, bowler, fielders) |
| `OverSummary` | `over_summaries` | Per-over summary (runs, wickets, maiden, ball list) |
| `MatchScorer` | `match_scorers` | Scorer assignment for a match |
| `Squad` | `squads` | Playing XI for a fixture |

---

## 3. Database Schema

### `fixtures` (key columns for scoring)

| Column | Type | Description |
|--------|------|-------------|
| `club_id` | FK | Club that owns this fixture |
| `home_team_id` | FK, nullable | Club's team (if playing home) |
| `away_team_id` | FK, nullable | Club's team (if playing away) |
| `home_opponent_name` | string, nullable | External opponent name (home) |
| `away_opponent_name` | string, nullable | External opponent name (away) |
| `home_opponent_players` | JSON, nullable | Array of `[{name: "..."}]` for external home opponent |
| `away_opponent_players` | JSON, nullable | Array of `[{name: "..."}]` for external away opponent |
| `club_plays_home` | boolean | Whether the club plays as home team |
| `overs_per_innings` | integer | Number of overs per innings (e.g., 20 for T20) |
| `toss_winner_side` | enum: `club`, `opponent` | Who won the toss |
| `toss_decision` | enum: `bat`, `bowl` | Toss winner's decision |
| `scorer_user_id` | FK, nullable | Assigned scorer's user ID |
| `status` | enum | `draft`, `published`, `live`, `paused`, `completed`, `abandoned`, `cancelled`, `postponed` |
| `home_team_runs`, `home_team_wickets`, `home_team_overs` | int/decimal | Synced score for home team |
| `away_team_runs`, `away_team_wickets`, `away_team_overs` | int/decimal | Synced score for away team |
| `started_at`, `completed_at` | timestamp | Match timing |

### `matches`

| Column | Type | Description |
|--------|------|-------------|
| `fixture_id` | FK, unique | One match per fixture |
| `current_innings_number` | tinyint | 1 or 2 |
| `current_over_number` | smallint | Current over (0-indexed) |
| `current_ball_number` | tinyint | Current ball in over (0–5) |
| `total_legal_deliveries` | smallint | Total legal balls bowled in match |
| `is_paused` | boolean | Pause state |
| `first_innings_id` | FK | Reference to first innings |
| `second_innings_id` | FK | Reference to second innings |

### `innings`

| Column | Type | Description |
|--------|------|-------------|
| `match_id`, `fixture_id` | FK | Parent references |
| `batting_team_id` | FK, nullable | Club team ID (null if opponent bats) |
| `bowling_team_id` | FK, nullable | Club team ID (null if opponent bowls) |
| `innings_number` | tinyint | 1 or 2 |
| `runs`, `wickets`, `overs` | int/decimal | Innings totals |
| `legal_deliveries` | smallint | Count of legal balls |
| `extras_total`, `wides`, `no_balls`, `byes`, `leg_byes`, `penalty_runs` | smallint | Extras breakdown |
| `target` | smallint, nullable | Target for 2nd innings |
| `striker_id` | FK, nullable | Current striker (club user) |
| `non_striker_id` | FK, nullable | Current non-striker (club user) |
| `current_bowler_id` | FK, nullable | Current bowler (club user) |
| `external_striker_index` | tinyint, nullable | Striker index (opponent) |
| `external_non_striker_index` | tinyint, nullable | Non-striker index (opponent) |
| `external_bowler_index` | tinyint, nullable | Bowler index (opponent) |
| `batting_is_club` | boolean | True if club is batting |
| `bowling_is_club` | boolean | True if club is bowling |
| `result` | enum | `in_progress`, `all_out`, `overs_completed`, `target_achieved`, `innings_declared`, `abandoned` |
| `total_batters` | tinyint | Usually 11 |

### `ball_events`

| Column | Type | Description |
|--------|------|-------------|
| `innings_id`, `match_id`, `fixture_id` | FK | Parent references |
| `over_number` | smallint | Over number (0-indexed) |
| `ball_number` | tinyint | Ball in over (1–6) |
| `ball_sequence` | integer | Sequential number across innings |
| `legal_ball_sequence` | integer | Sequential number of legal balls only |
| `striker_id`, `non_striker_id`, `bowler_id` | FK, nullable | Club player references |
| `external_striker_index`, `external_non_striker_index`, `external_bowler_index` | tinyint, nullable | Opponent player indices |
| `event_type` | enum | `dot`, `run`, `wide`, `no_ball`, `bye`, `leg_bye`, `wicket`, `penalty`, `retired`, `combo` |
| `runs_scored` | tinyint | Runs off the bat |
| `total_runs` | tinyint | Total runs (bat + extras) |
| `is_boundary_four`, `is_boundary_six` | boolean | Boundary flags |
| `extras_type` | enum, nullable | `wide`, `no_ball`, `bye`, `leg_bye`, `penalty` |
| `extras_runs` | tinyint | Extras runs |
| `is_legal_delivery` | boolean | False for wide/no_ball |
| `is_wicket_ball` | boolean | True if a wicket fell |
| `wicket_id` | FK, nullable | Linked wicket |
| `offline_uuid` | UUID | For offline sync support |

### `batting_scores`

| Column | Type | Description |
|--------|------|-------------|
| `innings_id`, `match_id`, `fixture_id` | FK | Parent references |
| `user_id` | FK, nullable | Club player |
| `external_player_index` | tinyint, nullable | Opponent player index |
| `external_player_name` | string, nullable | Opponent player name |
| `batting_order` | int | Order (1–11) |
| `runs`, `balls_faced`, `fours`, `sixes` | int | Batting stats |
| `is_on_strike` | boolean | Currently on strike |
| `has_batted` | boolean | Has faced at least one ball |
| `is_out` | boolean | Dismissed |
| `dismissal_type` | string | `bowled`, `caught`, `lbw`, etc. |
| `wicket_id` | FK, nullable | Linked wicket |

### `bowling_figures`

| Column | Type | Description |
|--------|------|-------------|
| `innings_id`, `match_id`, `fixture_id` | FK | Parent references |
| `user_id` | FK, nullable | Club bowler |
| `external_player_index` | tinyint, nullable | Opponent bowler index |
| `external_player_name` | string, nullable | Opponent bowler name |
| `overs`, `balls_bowled` | decimal/int | Overs bowled |
| `maidens`, `runs_conceded`, `wickets` | int | Bowling stats |
| `wides_bowled`, `no_balls_bowled` | int | Extras bowled |
| `is_current_bowler` | boolean | Currently bowling |

### `wickets`

| Column | Type | Description |
|--------|------|-------------|
| `ball_event_id`, `innings_id`, `match_id`, `fixture_id` | FK | Parent references |
| `dismissed_batter_id` | FK, nullable | Club batter dismissed |
| `external_dismissed_batter_index` | tinyint, nullable | Opponent batter index |
| `dismissal_type` | enum | `bowled`, `caught`, `lbw`, `run_out`, `stumped`, `hit_wicket`, `caught_and_bowled`, `retired`, `retired_hurt`, `obstructing_field`, `hit_ball_twice`, `timed_out` |
| `bowler_id` | FK, nullable | Club bowler |
| `external_bowler_index` | tinyint, nullable | Opponent bowler index |
| `fielder_one_id`, `fielder_two_id` | FK, nullable | Club fielders |
| `external_fielder_one_index`, `external_fielder_two_index` | tinyint, nullable | Opponent fielder indices |
| `runs_at_dismissal` | int | Batter's score when dismissed |

### `over_summaries`

| Column | Type | Description |
|--------|------|-------------|
| `innings_id`, `match_id` | FK | Parent references |
| `over_number` | int | Over number (0-indexed) |
| `bowler_id` | FK, nullable | Club bowler |
| `external_bowler_index`, `external_bowler_name` | nullable | Opponent bowler |
| `runs`, `wickets`, `extras` | int | Over totals |
| `is_maiden` | boolean | Maiden over |
| `balls` | JSON | Array of display strings (e.g., `["1", "•", "4", "W", "2", "•"]`) |

### `match_scorers`

| Column | Type | Description |
|--------|------|-------------|
| `match_id`, `fixture_id` | FK | Parent references |
| `user_id` | FK | Scorer user |
| `role` | string | `primary_scorer` |
| `assigned_by` | FK | Who assigned |
| `assigned_at` | timestamp | Assignment time |

---

## 4. Player Reference System (Club vs Opponent)

This is the **most important concept** to understand. Cricket OS supports matches where one team is a registered club (with real user accounts) and the other is an external opponent (just names, no accounts).

### How Players Are Referenced

| Side | Club Players | Opponent Players |
|------|-------------|-----------------|
| **Reference** | `user_id` (integer) | `player_index` (integer, 0-based array index) |
| **Example** | `user_id: 42` | `player_index: 3` (4th player in opponent list) |
| **Where stored** | `striker_id`, `bowler_id`, etc. | `external_striker_index`, `external_bowler_index`, etc. |
| **Name source** | `users` table | `fixture.home_opponent_players` or `fixture.away_opponent_players` JSON array |

### How to Determine Which Side is Club

The `Fixture` model has:
- `clubPlaysHome()` → returns `true` if the club is the home team
- `clubTeamId()` → returns the club's team ID
- `opponentPlayers()` → returns the array of opponent players `[{name: "..."}, ...]`

The `Innings` model has:
- `batting_is_club` → `true` if the club team is batting
- `bowling_is_club` → `true` if the club team is bowling

### When Sending Player References in API Requests

- **Club player**: send `user_id` (e.g., `{"user_id": 42}`)
- **Opponent player**: send `player_index` (e.g., `{"player_index": 3}`)

The backend's `resolvePlayerRef()` method handles this automatically based on which side is being referenced.

---

## 5. The Scoring Workflow

The scoring process follows a strict sequence:

```
┌─────────────────────────────────────────────────────────────────────┐
│                        SCORING WORKFLOW                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. PRE-MATCH SETUP (by Club Admin)                                 │
│     ├── Create fixture                                              │
│     ├── Assign club team                                            │
│     ├── Set club squad (playing XI)                                 │
│     ├── Add opponent players                                        │
│     └── Assign scorer                                               │
│                                                                     │
│  2. SCORER PRE-MATCH                                                │
│     ├── GET  /scorer/fixtures/{id}/readiness  ← Check readiness     │
│     └── POST /scorer/fixtures/{id}/toss        ← Record toss        │
│                                                                     │
│  3. START MATCH                                                     │
│     └── POST /scorer/fixtures/{id}/start       ← Start with openers │
│         ├── Creates Match record                                    │
│         ├── Creates MatchScorer record                              │
│         ├── Creates Innings #1                                      │
│         ├── Initializes BattingScores for all players               │
│         ├── Sets openers (striker + non-striker)                    │
│         └── Sets opening bowler                                     │
│                                                                     │
│  4. LIVE SCORING (repeat for each ball)                             │
│     ├── POST /scorer/matches/{id}/balls        ← Record ball        │
│     ├── POST /scorer/matches/{id}/change-bowler ← Change bowler     │
│     ├── POST /scorer/matches/{id}/change-batter ← Change batter     │
│     ├── POST /scorer/matches/{id}/pause        ← Pause match        │
│     └── POST /scorer/matches/{id}/resume       ← Resume match       │
│                                                                     │
│  5. END FIRST INNINGS                                               │
│     └── POST /scorer/matches/{id}/end-innings  ← End innings #1     │
│         └── Returns target for 2nd innings                          │
│                                                                     │
│  6. START SECOND INNINGS                                            │
│     └── POST /scorer/matches/{id}/start-second-innings              │
│         ├── Creates Innings #2                                      │
│         ├── Initializes BattingScores                               │
│         ├── Sets openers                                            │
│         └── Sets opening bowler                                     │
│                                                                     │
│  7. LIVE SCORING (repeat for 2nd innings)                          │
│     └── (same as step 4)                                            │
│                                                                     │
│  8. END MATCH                                                       │
│     └── POST /scorer/matches/{id}/end-innings  ← End innings #2     │
│         └── Fixture status → completed                              │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Readiness Checks

Before a match can start, these conditions must all be true:

| Check | Description |
|-------|-------------|
| `scorer_assigned` | A scorer is assigned to the fixture |
| `club_team_assigned` | The club team is set |
| `club_squad_assigned` | Playing XI is selected for the club |
| `opponent_squad_assigned` | Opponent players are added |
| `toss_recorded` | Toss winner and decision are recorded |
| `match_not_started` | No match record exists yet |

---

## 6. Scorer API Endpoints

All scorer endpoints require **authentication** (`Authorization: Bearer {token}`) and the authenticated user must be the assigned scorer for the fixture/match.

### Base URL
```
/api/scorer
```

### Authorization

The user must satisfy one of:
1. `fixture.scorer_user_id === user.id` (assigned as fixture scorer)
2. `user.isScorerForMatch(match)` (exists in `match_scorers` table)

If not authorized → `403 Forbidden`:
```json
{
    "message": "You are not authorized to score this match."
}
```

---

### 6.1 Get Match Readiness

```http
GET /api/scorer/fixtures/{fixtureId}/readiness
```

**Response (200):**
```json
{
    "message": "Match readiness fetched successfully.",
    "data": {
        "checks": {
            "scorer_assigned": true,
            "club_team_assigned": true,
            "club_squad_assigned": true,
            "opponent_squad_assigned": true,
            "toss_recorded": false,
            "match_not_started": true
        },
        "can_start_match": false,
        "is_match_ready": true,
        "club_team_id": 5,
        "club_squad": [
            {
                "user_id": 12,
                "name": "John Doe",
                "position": "playing_xi",
                "jersey_number": 1,
                "is_captain": true,
                "is_wicket_keeper": false
            }
        ],
        "opponent_players": [
            {"name": "James Cooper"},
            {"name": "Ryan Phillips"}
        ],
        "toss": {
            "winner_side": null,
            "decision": null
        },
        "scorer_user_id": 10,
        "missing": [
            "Record toss before starting the match."
        ]
    }
}
```

---

### 6.2 Record Toss

```http
POST /api/scorer/fixtures/{fixtureId}/toss
```

**Request Body:**
```json
{
    "winner_side": "club",
    "decision": "bat"
}
```

| Field | Values | Required |
|-------|--------|----------|
| `winner_side` | `club` or `opponent` | Yes |
| `decision` | `bat` or `bowl` | Yes |

**Response (200):**
```json
{
    "message": "Toss recorded successfully.",
    "data": {
        "fixture_id": 25,
        "toss_winner_side": "club",
        "toss_decision": "bat",
        "readiness": { ... }
    }
}
```

**Error (422)** — Cannot change toss after match started:
```json
{
    "message": "Cannot change toss after match has started."
}
```

---

### 6.3 Start Match

```http
POST /api/scorer/fixtures/{fixtureId}/start
```

The request body **depends on who bats first** (determined by toss):

**If club bats first** (`toss_winner_side=club` + `decision=bat`, OR `toss_winner_side=opponent` + `decision=bowl`):
```json
{
    "striker_user_id": 12,
    "non_striker_user_id": 15,
    "opening_bowler_player_index": 3
}
```

**If opponent bats first**:
```json
{
    "striker_player_index": 0,
    "non_striker_player_index": 1,
    "opening_bowler_user_id": 20
}
```

| Field | Type | Description |
|-------|------|-------------|
| `striker_user_id` | int | Club striker (if club bats) |
| `non_striker_user_id` | int | Club non-striker (if club bats) |
| `opening_bowler_player_index` | int | Opponent bowler index (if club bats) |
| `striker_player_index` | int | Opponent striker index (if opponent bats) |
| `non_striker_player_index` | int | Opponent non-striker index (if opponent bats) |
| `opening_bowler_user_id` | int | Club bowler (if opponent bats) |

**Response (201):**
```json
{
    "message": "Match started successfully.",
    "data": {
        "match_id": 1,
        "fixture_id": 25,
        "live": { ... }
    }
}
```

**What happens internally:**
1. Creates a `Matchs` record
2. Creates a `MatchScorer` record (primary_scorer)
3. Creates `Innings` #1
4. Creates `BattingScore` records for all 11 players
5. Sets the openers (striker & non-striker)
6. Sets the opening bowler
7. Updates fixture status to `live`

---

### 6.4 Get Live Score

```http
GET /api/scorer/matches/{matchId}/live
```

**Response (200):**
```json
{
    "message": "Live score fetched successfully.",
    "data": {
        "match": {
            "id": 1,
            "fixture_id": 25,
            "current_innings_number": 1,
            "current_over_display": "3.2",
            "is_paused": false
        },
        "fixture": {
            "id": 25,
            "status": "live",
            "home_display_name": "First XI",
            "away_display_name": "Nottingham Navigators",
            "overs_per_innings": 20,
            "toss_winner_side": "club",
            "toss_decision": "bat"
        },
        "innings": { ... },
        "recent_balls": [ ... ],
        "first_innings": { ... },
        "second_innings": null
    }
}
```

---

### 6.5 Record a Ball

```http
POST /api/scorer/matches/{matchId}/balls
```

**Request Body:**
```json
{
    "event_type": "run",
    "runs_scored": 4,
    "total_runs": 4,
    "is_boundary_four": true,
    "is_legal_delivery": true,
    "commentary": "Beautiful cover drive for four!"
}
```

**Full Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `event_type` | enum | Yes | `dot`, `run`, `wide`, `no_ball`, `bye`, `leg_bye`, `wicket`, `penalty`, `retired`, `combo` |
| `runs_scored` | int (0–6) | No | Runs off the bat |
| `extras_runs` | int (0–10) | No | Extras runs |
| `total_runs` | int (0–12) | No | Total runs (auto-calculated if omitted) |
| `extras_type` | enum | No | `wide`, `no_ball`, `bye`, `leg_bye`, `penalty` |
| `is_legal_delivery` | boolean | No | Auto-detected (false for wide/no_ball) |
| `is_boundary_four` | boolean | No | Is a four |
| `is_boundary_six` | boolean | No | Is a six |
| `is_wicket_ball` | boolean | No | Auto-detected (true if event_type=wicket) |
| `no_ball_type` | enum | No | `overstepping`, `high_full_toss`, `above_waist`, `bounce_above_shoulders` |
| `is_wide_plus_boundary` | boolean | No | Wide that reached the boundary |
| `commentary` | string | No | Custom commentary |
| `scorer_notes` | string | No | Scorer's private notes |
| `offline_uuid` | UUID | No | For offline sync |
| `wicket` | object | No | Wicket details (see below) |

**Wicket Object (when `event_type=wicket`):**
```json
{
    "event_type": "wicket",
    "wicket": {
        "dismissal_type": "bowled",
        "fielder_one_user_id": null,
        "fielder_one_player_index": null,
        "fielder_two_user_id": null,
        "fielder_two_player_index": null,
        "description": "Cleaned him up with a yorker!"
    }
}
```

| Wicket Field | Type | Description |
|--------------|------|-------------|
| `dismissal_type` | enum | `bowled`, `caught`, `lbw`, `run_out`, `stumped`, `hit_wicket`, `caught_and_bowled`, `retired`, `retired_hurt`, `obstructing_field`, `hit_ball_twice`, `timed_out` |
| `fielder_one_user_id` | int, nullable | Club fielder (for caught, run_out, stumped) |
| `fielder_one_player_index` | int, nullable | Opponent fielder index |
| `fielder_two_user_id` | int, nullable | Second club fielder (for run_out) |
| `fielder_two_player_index` | int, nullable | Second opponent fielder index |
| `description` | string | Wicket description |

**Response (201):**
```json
{
    "message": "Ball recorded successfully.",
    "data": {
        "ball": { ... },
        "wicket": null,
        "live": { ... }
    }
}
```

**What happens internally (per ball):**
1. Creates a `BallEvent` record
2. If wicket → creates a `Wicket` record and marks batter out
3. Updates `Innings` totals (runs, wickets, overs, extras)
4. Updates `BattingScore` for the striker
5. Updates `BowlingFigure` for the bowler
6. Advances the match cursor (over/ball number)
7. Swaps strike on odd runs or over completion
8. Finalizes `OverSummary` when an over completes
9. Syncs fixture score (`home_team_runs`, etc.)
10. Checks if innings should end (all out, overs complete, target achieved)

---

### 6.6 Change Bowler

```http
POST /api/scorer/matches/{matchId}/change-bowler
```

**Request Body (club bowler):**
```json
{
    "user_id": 20
}
```

**Request Body (opponent bowler):**
```json
{
    "player_index": 2
}
```

**Response (200):**
```json
{
    "message": "Bowler changed successfully.",
    "data": {
        "innings": { ... },
        "live": { ... }
    }
}
```

---

### 6.7 Change Batter

```http
POST /api/scorer/matches/{matchId}/change-batter
```

**Request Body:**
```json
{
    "side": "striker",
    "user_id": 18
}
```

Or for opponent:
```json
{
    "side": "non_striker",
    "player_index": 5
}
```

| Field | Type | Description |
|-------|------|-------------|
| `side` | enum | `striker` or `non_striker` (default: `striker`) |
| `user_id` | int | Club batter (if club is batting) |
| `player_index` | int | Opponent batter index (if opponent is batting) |

**Response (200):**
```json
{
    "message": "Batter changed successfully.",
    "data": {
        "innings": { ... },
        "live": { ... }
    }
}
```

---

### 6.8 End Innings

```http
POST /api/scorer/matches/{matchId}/end-innings
```

**Request Body:**
```json
{
    "result": "overs_completed",
    "result_note": "Innings completed after 20 overs"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `result` | enum | `all_out`, `overs_completed`, `target_achieved`, `innings_declared`, `abandoned` |
| `result_note` | string | Optional note |

**Response (200) — After 1st innings:**
```json
{
    "message": "Innings ended successfully.",
    "data": {
        "innings": { ... },
        "match": { ... },
        "next_step": "start_second_innings",
        "target": 179
    }
}
```

**Response (200) — After 2nd innings:**
```json
{
    "message": "Innings ended successfully.",
    "data": {
        "innings": { ... },
        "match": { ... },
        "next_step": "complete_match"
    }
}
```

> **Note:** Innings can also end **automatically** during `recordBall` when:
> - All out (wickets ≥ 10)
> - Overs completed (legal_deliveries ≥ overs_per_innings × 6)
> - Target achieved (runs ≥ target in 2nd innings)

---

### 6.9 Start Second Innings

```http
POST /api/scorer/matches/{matchId}/start-second-innings
```

The request body depends on who bats second (opposite of first innings):

**If club bats second:**
```json
{
    "striker_user_id": 12,
    "non_striker_user_id": 15,
    "opening_bowler_player_index": 3
}
```

**If opponent bats second:**
```json
{
    "striker_player_index": 0,
    "non_striker_player_index": 1,
    "opening_bowler_user_id": 20
}
```

**Response (200):**
```json
{
    "message": "Second innings started successfully.",
    "data": {
        "innings": { ... },
        "live": { ... }
    }
}
```

---

### 6.10 Pause Match

```http
POST /api/scorer/matches/{matchId}/pause
```

**Response (200):**
```json
{
    "message": "Match paused.",
    "data": {
        "live": { ... }
    }
}
```

> When paused, `recordBall` will return a 422 error: `"Match is paused. Resume before scoring."`

---

### 6.11 Resume Match

```http
POST /api/scorer/matches/{matchId}/resume
```

**Response (200):**
```json
{
    "message": "Match resumed.",
    "data": {
        "live": { ... }
    }
}
```

---

## 7. Public Match API Endpoints

These endpoints are **public** (no authentication required) and are used by spectators/apps to view live scores and completed scorecards.

### Base URL
```
/api/public
```

### 7.1 List Live Matches

```http
GET /api/public/matches/live?per_page=20
```

**Response (200):**
```json
{
    "message": "Live matches fetched successfully.",
    "data": [
        {
            "id": 25,
            "public_share_slug": "uuid-here",
            "public_url": "http://domain/match/uuid-here",
            "status": "live",
            "status_label": "Live",
            "match_type": "t20",
            "match_type_label": "T20",
            "scheduled_date": "2026-07-30",
            "scheduled_time": "14:00",
            "overs_per_innings": 20,
            "home": {
                "display_name": "First XI",
                "team_id": 5,
                "is_external": false
            },
            "away": {
                "display_name": "Nottingham Navigators",
                "team_id": null,
                "is_external": true
            },
            "venue": { ... },
            "club": { ... },
            "is_live": true,
            "is_completed": false,
            "result_text": null,
            "started_at": "2026-07-30T14:00:00+00:00",
            "completed_at": null,
            "match_id": 1
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 1
    }
}
```

### 7.2 List Upcoming Matches

```http
GET /api/public/matches/upcoming?per_page=20
```

### 7.3 List Completed Matches

```http
GET /api/public/matches/completed?per_page=20
```

### 7.4 Match Detail

```http
GET /api/public/matches/{slug}
```

The `{slug}` can be the `public_share_slug` (UUID) or the fixture ID.

### 7.5 Live/Completed Scorecard

```http
GET /api/public/matches/{slug}/score
```

Or by fixture ID:
```http
GET /api/public/fixtures/{fixtureId}/score
```

> **Polling:** For live matches, poll this endpoint every 5–10 seconds.

**Response (200) — Live match:**
```json
{
    "message": "Score fetched successfully.",
    "data": {
        "state": "live",
        "match": {
            "id": 1,
            "fixture_id": 25,
            "current_innings_number": 1,
            "current_over_display": "5.3",
            "is_paused": false
        },
        "fixture": {
            "id": 25,
            "status": "live",
            "home_display_name": "First XI",
            "away_display_name": "Nottingham Navigators",
            "home": { ... },
            "away": { ... },
            "home_score": {
                "runs": 45,
                "wickets": 1,
                "overs": "5.3",
                "display": "45/1 (5.3)"
            },
            "away_score": {
                "runs": 0,
                "wickets": 0,
                "overs": "0.0",
                "display": "0/0 (0.0)"
            }
        },
        "current_innings": {
            "id": 1,
            "innings_number": 1,
            "batting_side": "club",
            "runs": 45,
            "wickets": 1,
            "overs": "5.3",
            "score_display": "45/1 (5.3)",
            "target": null,
            "run_rate": 8.18,
            "required_run_rate": null,
            "result": "in_progress",
            "batting": [
                {
                    "name": "John Doe",
                    "runs": 22,
                    "balls_faced": 15,
                    "fours": 3,
                    "sixes": 1,
                    "strike_rate": 146.67,
                    "is_on_strike": true,
                    "is_out": false,
                    "dismissal": "not out",
                    "score_display": "22 (15)"
                }
            ],
            "bowling": [
                {
                    "name": "James Cooper",
                    "overs": "2.0",
                    "runs_conceded": 18,
                    "wickets": 1,
                    "figures_display": "1-18",
                    "is_current_bowler": false
                }
            ],
            "current_players": {
                "striker": "John Doe",
                "non_striker": "Jane Smith",
                "bowler": "Ryan Phillips"
            }
        },
        "first_innings": null,
        "second_innings": null,
        "recent_balls": [
            {
                "over_number": 5,
                "ball_number": 3,
                "display": "4",
                "color": "blue",
                "total_runs": 4,
                "event_type": "run",
                "is_wicket": false,
                "is_four": true,
                "is_six": false,
                "commentary": null
            }
        ],
        "last_updated": "2026-07-30T14:15:00+00:00"
    }
}
```

**Response (200) — Completed match:**
```json
{
    "message": "Score fetched successfully.",
    "data": {
        "state": "completed",
        "match": {
            "id": 1,
            "fixture_id": 25,
            "elapsed_time": "4h 25m"
        },
        "fixture": {
            "id": 25,
            "status": "completed",
            "home_display_name": "First XI",
            "away_display_name": "Nottingham Navigators",
            "home_score": { ... },
            "away_score": { ... },
            "result_text": "First XI won by 6 runs"
        },
        "first_innings": {
            "id": 1,
            "innings_number": 1,
            "batting_side": "club",
            "runs": 178,
            "wickets": 6,
            "overs": "20.0",
            "score_display": "178/6 (20.0)",
            "batting": [ ... ],
            "bowling": [ ... ]
        },
        "second_innings": {
            "id": 2,
            "innings_number": 2,
            "batting_side": "opponent",
            "runs": 172,
            "wickets": 9,
            "overs": "20.0",
            "score_display": "172/9 (20.0)",
            "target": 179,
            "batting": [ ... ],
            "bowling": [ ... ]
        },
        "last_updated": "2026-07-30T18:25:00+00:00"
    }
}
```

**Response (200) — Not started:**
```json
{
    "message": "Score fetched successfully.",
    "data": {
        "state": "not_started",
        "message": "Match has not started yet.",
        "fixture_scores": {
            "home": {
                "runs": null,
                "wickets": null,
                "overs": null,
                "display": "0/0 (0.0)"
            },
            "away": {
                "runs": null,
                "wickets": null,
                "overs": null,
                "display": "0/0 (0.0)"
            }
        }
    }
}
```

---

## 8. Ball Event Types & Processing

### Event Types

| `event_type` | Description | Legal? | Runs off bat? | Extras? |
|--------------|-------------|--------|---------------|---------|
| `dot` | Dot ball | Yes | 0 | 0 |
| `run` | Runs scored | Yes | `runs_scored` | 0 |
| `wide` | Wide ball | No | 0 | `extras_runs` (usually 1) |
| `no_ball` | No ball | No | `runs_scored` (if bat runs) | 1 + bat runs |
| `bye` | Bye | Yes | 0 | `extras_runs` |
| `leg_bye` | Leg bye | Yes | 0 | `extras_runs` |
| `wicket` | Wicket | Yes | 0 | 0 |
| `penalty` | Penalty runs | No | 0 | `extras_runs` |
| `retired` | Batter retired | Yes | 0 | 0 |
| `combo` | Combination (e.g., no_ball + wicket) | No | varies | varies |

### How Runs Are Calculated

```
total_runs = runs_scored + extras_runs
```

- For a **dot ball**: `runs_scored=0`, `extras_runs=0`, `total_runs=0`
- For a **four**: `runs_scored=4`, `extras_runs=0`, `total_runs=4`
- For a **wide**: `runs_scored=0`, `extras_runs=1`, `total_runs=1`
- For a **no_ball with 2 runs**: `runs_scored=2`, `extras_runs=1`, `total_runs=3`
- For a **wide + boundary**: `runs_scored=0`, `extras_runs=5`, `total_runs=5`, `is_wide_plus_boundary=true`

### What Gets Updated Per Ball

| Entity | What's Updated |
|--------|---------------|
| `BallEvent` | New record created |
| `Innings` | `runs`, `wickets`, `overs`, `legal_deliveries`, extras breakdown |
| `BattingScore` (striker) | `runs`, `balls_faced`, `fours`, `sixes` (skipped on wicket) |
| `BowlingFigure` (bowler) | `balls_bowled`, `overs`, `runs_conceded`, `wickets`, `wides_bowled`, `no_balls_bowled` |
| `Wicket` | New record (if wicket ball) |
| `OverSummary` | Created/updated when over completes |
| `Matchs` | `current_over_number`, `current_ball_number`, `total_legal_deliveries` |
| `Fixture` | `home_team_runs/wickets/overs` or `away_team_*` (synced) |

### Strike Rotation

- **Odd runs** (1, 3, 5) → swap striker and non-striker
- **Over completion** (6 legal balls) → swap striker and non-striker (unless wicket fell on last ball)
- **Wicket** → no swap (new batter comes in via `changeBatter`)

---

## 9. Innings State Management

### Innings Result Values

| `result` | Description | How it happens |
|----------|-------------|----------------|
| `in_progress` | Innings is ongoing | Default when innings starts |
| `all_out` | Batting team all out | Wickets ≥ 10 (auto-detected) |
| `overs_completed` | All overs bowled | Legal deliveries ≥ overs × 6 (auto-detected) |
| `target_achieved` | Target chased | Runs ≥ target (auto-detected, 2nd innings only) |
| `innings_declared` | Innings declared | Manually via `endInnings` |
| `abandoned` | Innings abandoned | Manually via `endInnings` |

### Auto-End Conditions

The `checkInningsEnd()` method runs after every ball:

```php
if ($innings->isTargetAchieved()) {
    $this->endInnings($match, 'target_achieved');
} elseif ($innings->isAllOut()) {
    $this->endInnings($match, 'all_out');
} elseif ($innings->isOversComplete()) {
    $this->endInnings($match, 'overs_completed');
}
```

### Match Status Flow

```
draft → published → live → paused → live → completed
                                    ↓
                                  abandoned
```

---

## 10. Live State Response Structure

The `getLiveState()` method (used by scorer endpoints) returns:

```json
{
    "match": {
        "id": 1,
        "fixture_id": 25,
        "current_innings_number": 1,
        "current_over_display": "3.2",
        "is_paused": false
    },
    "fixture": {
        "id": 25,
        "status": "live",
        "home_display_name": "First XI",
        "away_display_name": "Nottingham Navigators",
        "overs_per_innings": 20,
        "toss_winner_side": "club",
        "toss_decision": "bat"
    },
    "innings": {
        "id": 1,
        "innings_number": 1,
        "batting_is_club": true,
        "runs": 35,
        "wickets": 1,
        "overs": "3.2",
        "score_display": "35/1 (3.2)",
        "target": null,
        "run_rate": 10.5,
        "required_run_rate": null,
        "result": "in_progress",
        "striker_id": 12,
        "non_striker_id": 15,
        "external_striker_index": null,
        "external_non_striker_index": null,
        "current_bowler_id": null,
        "external_bowler_index": 2,
        "batting_scores": [ ... ],
        "bowling_figures": [ ... ]
    },
    "recent_balls": [
        {
            "over_number": 3,
            "ball_number": 2,
            "display": "4",
            "color": "blue",
            "total_runs": 4,
            "event_type": "run",
            "is_wicket": false,
            "is_four": true,
            "is_six": false,
            "commentary": null
        }
    ],
    "first_innings": null,
    "second_innings": null
}
```

### Ball Display Values

| Display | Color | Meaning |
|---------|-------|---------|
| `•` | gray | Dot ball |
| `1`, `2`, `3` | dark | Runs |
| `4` | blue | Four |
| `6` | green | Six |
| `W` | red | Wicket |
| `WD` or `WD 3` | yellow | Wide (with runs) |
| `NB` or `NB +2` | yellow | No ball (with runs) |
| `B1`, `B2` | yellow | Byes |
| `LB1`, `LB2` | yellow | Leg byes |
| `Pen 5` | yellow | Penalty runs |

---

## 11. Public Scorecard Structure

The public scorecard (from `GET /api/public/matches/{slug}/score`) has three states:

### State: `not_started`
Returns fixture scores (all zeros) with a message.

### State: `live` or `paused`
Returns:
- Match cursor info
- Fixture info with home/away scores
- `current_innings` with full batting/bowling card + current players
- `first_innings` / `second_innings` (if they exist)
- `recent_balls` (last 12 balls)
- `last_updated` timestamp

### State: `completed`
Returns:
- Match info with elapsed time
- Fixture info with final scores and result text
- `first_innings` and `second_innings` with full batting/bowling cards
- `last_updated` timestamp

### Batting Card Entry (Public)
```json
{
    "name": "John Doe",
    "runs": 52,
    "balls_faced": 38,
    "fours": 7,
    "sixes": 1,
    "strike_rate": 136.84,
    "is_on_strike": false,
    "is_out": true,
    "dismissal": "c Ryan Phillips b James Cooper",
    "score_display": "52 (38)"
}
```

### Bowling Card Entry (Public)
```json
{
    "name": "James Cooper",
    "overs": "4.0",
    "runs_conceded": 32,
    "wickets": 1,
    "figures_display": "1-32",
    "is_current_bowler": false
}
```

> **Privacy Note:** If `club.hide_player_names_publicly` is true, club player names are shown as `Player #ID` instead of real names.

---

## 12. Error Handling

### Common Error Responses

**401 Unauthorized** — No token or invalid token:
```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden** — Not the assigned scorer:
```json
{
    "message": "You are not authorized to score this match."
}
```

**404 Not Found** — Fixture/match not found:
```json
{
    "message": "Fixture not found."
}
```

**422 Validation Error** — Various scenarios:
```json
{
    "message": "Fixture is not ready to start. Missing: Record toss before starting the match.",
    "errors": {
        "fixture": ["Fixture is not ready to start. Missing: ..."]
    }
}
```

```json
{
    "message": "Match is paused. Resume before scoring."
}
```

```json
{
    "message": "No active innings to score."
}
```

```json
{
    "message": "First innings must be completed before starting second innings."
}
```

```json
{
    "message": "Provide user_id or player_index for the new bowler."
}
```

---

## 13. End-to-End Scoring Flow Example

Here's a complete example of scoring a T20 match where the **club bats first**:

### Step 1: Check Readiness
```http
GET /api/scorer/fixtures/25/readiness
Authorization: Bearer {scorer_token}
```

### Step 2: Record Toss
```http
POST /api/scorer/fixtures/25/toss
Authorization: Bearer {scorer_token}
Content-Type: application/json

{
    "winner_side": "club",
    "decision": "bat"
}
```

### Step 3: Start Match (club bats, opponent bowls)
```http
POST /api/scorer/fixtures/25/start
Authorization: Bearer {scorer_token}
Content-Type: application/json

{
    "striker_user_id": 12,
    "non_striker_user_id": 15,
    "opening_bowler_player_index": 0
}
```
→ Returns `match_id: 1`

### Step 4: Record Balls
```http
POST /api/scorer/matches/1/balls
Authorization: Bearer {scorer_token}
Content-Type: application/json

{"event_type": "dot"}
```
```http
POST /api/scorer/matches/1/balls
{"event_type": "run", "runs_scored": 4, "is_boundary_four": true}
```
```http
POST /api/scorer/matches/1/balls
{"event_type": "wide", "extras_runs": 1, "total_runs": 1}
```
```http
POST /api/scorer/matches/1/balls
{"event_type": "wicket", "wicket": {"dismissal_type": "bowled"}}
```

### Step 5: Change Batter (after wicket)
```http
POST /api/scorer/matches/1/change-batter
Authorization: Bearer {scorer_token}
Content-Type: application/json

{
    "side": "striker",
    "user_id": 18
}
```

### Step 6: Change Bowler (after over)
```http
POST /api/scorer/matches/1/change-bowler
Authorization: Bearer {scorer_token}
Content-Type: application/json

{
    "player_index": 1
}
```

### Step 7: End First Innings
```http
POST /api/scorer/matches/1/end-innings
Authorization: Bearer {scorer_token}
Content-Type: application/json

{
    "result": "overs_completed"
}
```
→ Returns `target: 179`, `next_step: "start_second_innings"`

### Step 8: Start Second Innings (opponent bats, club bowls)
```http
POST /api/scorer/matches/1/start-second-innings
Authorization: Bearer {scorer_token}
Content-Type: application/json

{
    "striker_player_index": 0,
    "non_striker_player_index": 1,
    "opening_bowler_user_id": 20
}
```

### Step 9: Continue Scoring (repeat Steps 4–6)

### Step 10: End Match
```http
POST /api/scorer/matches/1/end-innings
Authorization: Bearer {scorer_token}
Content-Type: application/json

{
    "result": "overs_completed"
}
```
→ Returns `next_step: "complete_match"`, fixture status → `completed`

### Step 11: View Public Scorecard
```http
GET /api/public/matches/{slug}/score
```

---

## 14. Mobile App Integration Examples

### React Native / JavaScript

```javascript
const API_BASE = 'https://your-domain.com/api';

// Helper: get headers
const getHeaders = (token) => ({
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json',
});

// 1. Check readiness
async function checkReadiness(fixtureId, token) {
  const res = await fetch(`${API_BASE}/scorer/fixtures/${fixtureId}/readiness`, {
    headers: getHeaders(token),
  });
  return res.json();
}

// 2. Record toss
async function recordToss(fixtureId, winnerSide, decision, token) {
  const res = await fetch(`${API_BASE}/scorer/fixtures/${fixtureId}/toss`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify({ winner_side: winnerSide, decision }),
  });
  return res.json();
}

// 3. Start match
async function startMatch(fixtureId, openers, token) {
  const res = await fetch(`${API_BASE}/scorer/fixtures/${fixtureId}/start`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify(openers),
  });
  return res.json();
}

// 4. Record a ball
async function recordBall(matchId, ballData, token) {
  const res = await fetch(`${API_BASE}/scorer/matches/${matchId}/balls`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify(ballData),
  });
  return res.json();
}

// 5. Record a wicket
async function recordWicket(matchId, dismissalType, fielders, token) {
  const res = await fetch(`${API_BASE}/scorer/matches/${matchId}/balls`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify({
      event_type: 'wicket',
      wicket: {
        dismissal_type: dismissalType,
        ...fielders,
      },
    }),
  });
  return res.json();
}

// 6. Change bowler
async function changeBowler(matchId, bowlerRef, token) {
  const res = await fetch(`${API_BASE}/scorer/matches/${matchId}/change-bowler`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify(bowlerRef),
  });
  return res.json();
}

// 7. Change batter
async function changeBatter(matchId, batterRef, side, token) {
  const res = await fetch(`${API_BASE}/scorer/matches/${matchId}/change-batter`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify({ side, ...batterRef }),
  });
  return res.json();
}

// 8. End innings
async function endInnings(matchId, result, note, token) {
  const res = await fetch(`${API_BASE}/scorer/matches/${matchId}/end-innings`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify({ result, result_note: note }),
  });
  return res.json();
}

// 9. Start second innings
async function startSecondInnings(matchId, openers, token) {
  const res = await fetch(`${API_BASE}/scorer/matches/${matchId}/start-second-innings`, {
    method: 'POST',
    headers: getHeaders(token),
    body: JSON.stringify(openers),
  });
  return res.json();
}

// 10. Get live state
async function getLiveState(matchId, token) {
  const res = await fetch(`${API_BASE}/scorer/matches/${matchId}/live`, {
    headers: getHeaders(token),
  });
  return res.json();
}

// 11. Get public score (no auth needed)
async function getPublicScore(slug) {
  const res = await fetch(`${API_BASE}/public/matches/${slug}/score`);
  return res.json();
}

// 12. Poll live score (for spectator view)
async function pollLiveScore(slug, intervalMs = 5000, callback) {
  const interval = setInterval(async () => {
    const data = await getPublicScore(slug);
    callback(data);
  }, intervalMs);
  return () => clearInterval(interval);
}
```

### Swift (iOS)

```swift
struct ScoringAPI {
    let baseURL: String
    let token: String

    var headers: [String: String] {
        [
            "Authorization": "Bearer \(token)",
            "Content-Type": "application/json",
            "Accept": "application/json"
        ]
    }

    func checkReadiness(fixtureId: Int) async throws -> [String: Any] {
        try await request("GET", path: "/scorer/fixtures/\(fixtureId)/readiness")
    }

    func recordToss(fixtureId: Int, winnerSide: String, decision: String) async throws -> [String: Any] {
        try await request("POST", path: "/scorer/fixtures/\(fixtureId)/toss",
            body: ["winner_side": winnerSide, "decision": decision])
    }

    func startMatch(fixtureId: Int, openers: [String: Any]) async throws -> [String: Any] {
        try await request("POST", path: "/scorer/fixtures/\(fixtureId)/start", body: openers)
    }

    func recordBall(matchId: Int, ballData: [String: Any]) async throws -> [String: Any] {
        try await request("POST", path: "/scorer/matches/\(matchId)/balls", body: ballData)
    }

    func changeBowler(matchId: Int, bowlerRef: [String: Any]) async throws -> [String: Any] {
        try await request("POST", path: "/scorer/matches/\(matchId)/change-bowler", body: bowlerRef)
    }

    func changeBatter(matchId: Int, batterRef: [String: Any], side: String = "striker") async throws -> [String: Any] {
        var body = batterRef
        body["side"] = side
        return try await request("POST", path: "/scorer/matches/\(matchId)/change-batter", body: body)
    }

    func endInnings(matchId: Int, result: String, note: String? = nil) async throws -> [String: Any] {
        var body: [String: Any] = ["result": result]
        if let note = note { body["result_note"] = note }
        return try await request("POST", path: "/scorer/matches/\(matchId)/end-innings", body: body)
    }

    func startSecondInnings(matchId: Int, openers: [String: Any]) async throws -> [String: Any] {
        try await request("POST", path: "/scorer/matches/\(matchId)/start-second-innings", body: openers)
    }

    func getLiveState(matchId: Int) async throws -> [String: Any] {
        try await request("GET", path: "/scorer/matches/\(matchId)/live")
    }

    private func request(_ method: String, path: String, body: [String: Any]? = nil) async throws -> [String: Any] {
        guard let url = URL(string: baseURL + path) else { throw URLError(.badURL) }
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.allHTTPHeaderFields = headers
        if let body = body {
            request.httpBody = try JSONSerialization.data(withJSONObject: body)
        }
        let (data, _) = try await URLSession.shared.data(for: request)
        return try JSONSerialization.jsonObject(with: data) as? [String: Any] ?? [:]
    }
}
```

### Kotlin (Android)

```kotlin
class ScoringAPI(private val baseURL: String, private val token: String) {

    private val client = OkHttpClient()
    private val json = MediaType.parse("application/json")

    private fun headers(): Headers {
        return Headers.Builder()
            .add("Authorization", "Bearer $token")
            .add("Content-Type", "application/json")
            .add("Accept", "application/json")
            .build()
    }

    suspend fun checkReadiness(fixtureId: Int): JSONObject = suspendCoroutine { cont ->
        val request = Request.Builder()
            .url("$baseURL/scorer/fixtures/$fixtureId/readiness")
            .headers(headers())
            .build()
        client.newCall(request).enqueue(object : Callback {
            override fun onResponse(call: Call, response: Response) {
                cont.resume(JSONObject(response.body()?.string() ?: "{}"))
            }
            override fun onFailure(call: Call, e: IOException) {
                cont.resumeWithException(e)
            }
        })
    }

    suspend fun recordBall(matchId: Int, ballData: JSONObject): JSONObject = suspendCoroutine { cont ->
        val body = RequestBody.create(json, ballData.toString())
        val request = Request.Builder()
            .url("$baseURL/scorer/matches/$matchId/balls")
            .headers(headers())
            .post(body)
            .build()
        client.newCall(request).enqueue(object : Callback {
            override fun onResponse(call: Call, response: Response) {
                cont.resume(JSONObject(response.body()?.string() ?: "{}"))
            }
            override fun onFailure(call: Call, e: IOException) {
                cont.resumeWithException(e)
            }
        })
    }

    suspend fun changeBowler(matchId: Int, bowlerRef: JSONObject): JSONObject = suspendCoroutine { cont ->
        val body = RequestBody.create(json, bowlerRef.toString())
        val request = Request.Builder()
            .url("$baseURL/scorer/matches/$matchId/change-bowler")
            .headers(headers())
            .post(body)
            .build()
        client.newCall(request).enqueue(object : Callback {
            override fun onResponse(call: Call, response: Response) {
                cont.resume(JSONObject(response.body()?.string() ?: "{}"))
            }
            override fun onFailure(call: Call, e: IOException) {
                cont.resumeWithException(e)
            }
        })
    }
}
```

---

## Key Takeaways for App Developers

1. **Always check readiness first** — The `GET /readiness` endpoint tells you exactly what's missing before you can start.

2. **Player references differ by side** — Club players use `user_id`, opponent players use `player_index`. The API validates differently based on who's batting/bowling.

3. **The response always includes `live` state** — After every scoring action (record ball, change bowler, etc.), the response includes the full live state so your UI can update immediately.

4. **Innings can end automatically** — When all out, overs complete, or target achieved, the backend auto-ends the innings. Check the `innings.result` field in the response.

5. **Use `next_step` from end-innings** — The `endInnings` response tells you whether to `start_second_innings` or if the match is `complete_match`.

6. **Public score endpoint for spectators** — Use `GET /api/public/matches/{slug}/score` for spectator apps. No auth needed. Poll every 5–10 seconds for live matches.

7. **Offline support** — The `offline_uuid` field in ball events supports offline scoring. Generate a UUID on the client, send it with the ball, and use it for deduplication when syncing.

8. **Pause/Resume** — When paused, `recordBall` returns 422. The UI should show a pause indicator and disable scoring buttons.

9. **Strike rotation is automatic** — The backend handles strike swaps on odd runs and over completion. The `is_on_strike` flag in batting scores tells you who's on strike.

10. **Over summaries are automatic** — When an over completes, the backend creates an `OverSummary` with the ball-by-ball display strings.
