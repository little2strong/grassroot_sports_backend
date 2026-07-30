# Fixture Squad Management APIs

## Overview

This document describes the refactored fixture squad management APIs that allow both **Club Owners/Admins** and **Assigned Scorers** to manage club and opponent squads for fixtures.

---

## Authorization

Both endpoints require authentication and check if the user is:

1. **Club Owner/Admin** - The authenticated user owns the club that owns the fixture
2. **Assigned Scorer** - The authenticated user is the scorer assigned to the fixture

If neither condition is met, the API returns:

```json
{
    "success": false,
    "message": "Unauthorized."
}
```

With HTTP status code `403 Forbidden`.

---

## Endpoints

### 1. Save Club Squad

Sets the club's playing squad for a fixture.

```http
POST /api/fixtures/{fixtureId}/club-squad
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
    "players": [
        {
            "player_id": 12,
            "role": "captain"
        },
        {
            "player_id": 15,
            "role": "wicketkeeper"
        },
        {
            "player_id": 21,
            "role": "batsman"
        },
        {
            "player_id": 28,
            "role": "bowler"
        }
    ]
}
```

**Validation Rules:**
- `players` - Required, array, min 1 player, max 12 players
- `players.*.player_id` - Required, integer, must exist in users table, must be a player user type
- `players.*.role` - Required, must be one of: `captain`, `vice_captain`, `wicketkeeper`, `batsman`, `bowler`, `all_rounder`
- No duplicate player IDs
- All players must belong to the club's team assigned to the fixture

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Squad saved successfully.",
    "data": {
        "fixture": {
            "id": 25,
            "club_id": 1,
            "home_team": {
                "id": 5,
                "name": "Team A",
                "short_name": "TA",
                "is_external": false,
                "club_id": 1,
                "opponent_players": null
            },
            "away_team": {
                "id": null,
                "name": "Opponent Club",
                "short_name": null,
                "is_external": true,
                "club_id": null,
                "opponent_players": []
            },
            "club_squad": [
                {
                    "id": 1,
                    "user_id": 12,
                    "player_name": "John Doe",
                    "position": "playing_xi",
                    "position_label": "Playing XI",
                    "jersey_number": null,
                    "is_captain": false,
                    "is_wicket_keeper": false,
                    "role": "captain"
                }
            ],
            "opponent_players": [],
            "scorer": {
                "id": 10,
                "first_name": "Jane",
                "last_name": "Smith",
                "email": "jane@example.com"
            },
            "scorer_user_id": 10,
            "status": "draft",
            "scheduled_date": "2026-07-15",
            "scheduled_time": "14:00",
            "match_type": "t20",
            "is_match_ready": false
        }
    }
}
```

**Error Responses:**

Unauthorized (403):
```json
{
    "success": false,
    "message": "Unauthorized."
}
```

Validation Error (422):
```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "players": [
            "Duplicate player IDs are not allowed."
        ]
    }
}
```

```json
{
    "success": false,
    "message": "A squad cannot contain more than 12 players."
}
```

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "players": [
            "One or more players do not belong to the selected club."
        ]
    }
}
```

Match Live/Completed (422):
```json
{
    "success": false,
    "message": "Cannot change squad after the match is live or completed."
}
```

---

### 2. Save Opponent Squad

Sets the opponent's squad for a fixture.

```http
POST /api/fixtures/{fixtureId}/opponent-squad
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**
```json
{
    "players": [
        {
            "name": "John Smith",
            "role": "captain"
        },
        {
            "name": "David Lee",
            "role": "bowler"
        },
        {
            "name": "Michael Brown",
            "role": "batsman"
        }
    ]
}
```

**Validation Rules:**
- `players` - Required, array, min 1 player, max 12 players
- `players.*.name` - Required, string, max 255 characters
- `players.*.role` - Required, must be one of: `captain`, `vice_captain`, `wicketkeeper`, `batsman`, `bowler`, `all_rounder`
- No duplicate player names within the request

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Opponent squad saved successfully.",
    "data": {
        "fixture": {
            "id": 25,
            "club_id": 1,
            "home_team": {
                "id": 5,
                "name": "Team A",
                "short_name": "TA",
                "is_external": false,
                "club_id": 1,
                "opponent_players": null
            },
            "away_team": {
                "id": null,
                "name": "Opponent Club",
                "short_name": null,
                "is_external": true,
                "club_id": null,
                "opponent_players": [
                    {
                        "name": "John Smith",
                        "role": "captain"
                    },
                    {
                        "name": "David Lee",
                        "role": "bowler"
                    },
                    {
                        "name": "Michael Brown",
                        "role": "batsman"
                    }
                ]
            },
            "club_squad": [],
            "opponent_players": [
                {
                    "name": "John Smith",
                    "role": "captain"
                },
                {
                    "name": "David Lee",
                    "role": "bowler"
                },
                {
                    "name": "Michael Brown",
                    "role": "batsman"
                }
            ],
            "scorer": {
                "id": 10,
                "first_name": "Jane",
                "last_name": "Smith",
                "email": "jane@example.com"
            },
            "scorer_user_id": 10,
            "status": "draft",
            "scheduled_date": "2026-07-15",
            "scheduled_time": "14:00",
            "match_type": "t20",
            "is_match_ready": false
        }
    }
}
```

**Error Responses:**

Unauthorized (403):
```json
{
    "success": false,
    "message": "Unauthorized."
}
```

Validation Error (422):
```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "players": [
            "Duplicate player names are not allowed."
        ]
    }
}
```

```json
{
    "success": false,
    "message": "A squad cannot contain more than 12 players."
}
```

Match Live/Completed (422):
```json
{
    "success": false,
    "message": "Cannot change opponent squad after the match is live or completed."
}
```

---

## Supported Roles

Both APIs use the same role enum values:

| Role | Description |
|------|-------------|
| `captain` | Team captain |
| `vice_captain` | Vice captain |
| `wicketkeeper` | Wicket keeper |
| `batsman` | Batsman |
| `bowler` | Bowler |
| `all_rounder` | All-rounder |

---

## Breaking Changes

### Endpoint URL Changes

**Old Endpoints:**
- `POST /api/club/{clubId}/fixtures/{fixtureId}/club-squad`
- `POST /api/club/{clubId}/fixtures/{fixtureId}/opponent-players`

**New Endpoints:**
- `POST /api/fixtures/{fixtureId}/club-squad`
- `POST /api/fixtures/{fixtureId}/opponent-squad`

**Action Required:** Update all client applications to use the new endpoint URLs. The `clubId` parameter is no longer required in the URL.

### Request Payload Changes

**Club Squad:**

Old payload:
```json
{
    "team_id": 5,
    "players": [
        {
            "user_id": 12,
            "position": "playing_xi",
            "is_captain": true,
            "is_wicket_keeper": false
        }
    ]
}
```

New payload:
```json
{
    "players": [
        {
            "player_id": 12,
            "role": "captain"
        }
    ]
}
```

**Changes:**
- `user_id` renamed to `player_id`
- `position`, `is_captain`, `is_wicket_keeper` removed
- `role` field added (required)
- `team_id` removed from request (automatically determined from fixture)

**Opponent Squad:**

Old payload:
```json
{
    "opponent_name": "Opponent Club",
    "players": ["John Smith", "David Lee"]
}
```

New payload:
```json
{
    "players": [
        {
            "name": "John Smith",
            "role": "captain"
        },
        {
            "name": "David Lee",
            "role": "bowler"
        }
    ]
}
```

**Changes:**
- Players now require both `name` and `role`
- `opponent_name` removed from request (already stored in fixture)

### Response Format Changes

All squad endpoints now return standardized responses:

**Success:**
```json
{
    "success": true,
    "message": "Squad saved successfully.",
    "data": { ... }
}
```

**Failure:**
```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": { ... }
}
```

**Authorization:**
```json
{
    "success": false,
    "message": "Unauthorized."
}
```

---

## Authorization Logic

### Club Owner/Admin

A user is authorized if:
1. User is authenticated
2. User type is `club`
3. User owns a club
4. The fixture belongs to that club

### Assigned Scorer

A user is authorized if:
1. User is authenticated
2. User's ID matches the fixture's `scorer_user_id` (regardless of user_type)

**Note:** The scorer can be any user type (club, player, etc.) as long as they are assigned to the fixture.

---

## Implementation Details

### Database Changes

**Migration:** `2026_06_30_100000_add_role_to_squads_table.php`

Added `role` column to `squads` table:
- Type: `enum`
- Values: `captain`, `vice_captain`, `wicketkeeper`, `batsman`, `bowler`, `all_rounder`
- Nullable: Yes (for backward compatibility)

### Controller Methods

**New Methods:**
- `setFixtureClubSquad(Request $request, int $fixtureId)` - Save club squad
- `setFixtureOpponentSquad(Request $request, int $fixtureId)` - Save opponent squad
- `resolveFixtureByFixtureId(Request $request, int $fixtureId)` - Authorize by fixture ID
- `getFixtureClub(Fixture $fixture)` - Get club from fixture

**Updated Methods:**
- `formatFixtureSquad(Fixture $fixture)` - Now includes `role` field in output

### Routes

**File:** `routes/api.php`

```php
Route::middleware('auth:sanctum')->prefix('club')->group(function () {
    // ... other routes ...
    
    Route::post('/fixtures/{fixtureId}/club-squad', [ClubController::class, 'setFixtureClubSquad']);
    Route::post('/fixtures/{fixtureId}/opponent-squad', [ClubController::class, 'setFixtureOpponentSquad']);
    
    // ... other routes ...
});
```

---

## Testing

### Test Case 1: Club Owner Saves Squad

**Request:**
```http
POST /api/fixtures/25/club-squad
Authorization: Bearer {club_owner_token}
```

**Expected:** 200 OK with success message

### Test Case 2: Assigned Scorer Saves Squad

**Request:**
```http
POST /api/fixtures/25/club-squad
Authorization: Bearer {scorer_token}
```

**Expected:** 200 OK with success message

### Test Case 3: Unauthorized User

**Request:**
```http
POST /api/fixtures/25/club-squad
Authorization: Bearer {other_user_token}
```

**Expected:** 403 Forbidden

### Test Case 4: Duplicate Player IDs

**Request:**
```json
{
    "players": [
        {"player_id": 12, "role": "captain"},
        {"player_id": 12, "role": "bowler"}
    ]
}
```

**Expected:** 422 Validation Error

### Test Case 5: More Than 12 Players

**Request:**
```json
{
    "players": [
        {"player_id": 1, "role": "captain"},
        {"player_id": 2, "role": "batsman"},
        {"player_id": 3, "role": "bowler"},
        {"player_id": 4, "role": "batsman"},
        {"player_id": 5, "role": "bowler"},
        {"player_id": 6, "role": "batsman"},
        {"player_id": 7, "role": "bowler"},
        {"player_id": 8, "role": "batsman"},
        {"player_id": 9, "role": "bowler"},
        {"player_id": 10, "role": "batsman"},
        {"player_id": 11, "role": "bowler"},
        {"player_id": 12, "role": "batsman"},
        {"player_id": 13, "role": "bowler"}
    ]
}
```

**Expected:** 422 Validation Error

---

## Postman Collection

Import the following collection into Postman to test the APIs:

### Collection 1: Club Squad

**Name:** Save Club Squad

**Request:**
```
POST http://your-domain.com/api/fixtures/25/club-squad
Headers:
  Authorization: Bearer {{club_owner_token}}
  Content-Type: application/json

Body (raw, JSON):
{
    "players": [
        {
            "player_id": 11,
            "role": "captain"
        },
        {
            "player_id": 12,
            "role": "wicketkeeper"
        },
        {
            "player_id": 13,
            "role": "batsman"
        },
        {
            "player_id": 14,
            "role": "bowler"
        },
        {
            "player_id": 15,
            "role": "all_rounder"
        }
    ]
}
```

**Tests:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.success).to.be.true;
});

pm.test("Response has message", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.message).to.eql("Squad saved successfully.");
});
```

### Collection 2: Opponent Squad

**Name:** Save Opponent Squad

**Request:**
```
POST http://your-domain.com/api/fixtures/25/opponent-squad
Headers:
  Authorization: Bearer {{club_owner_token}}
  Content-Type: application/json

Body (raw, JSON):
{
    "players": [
        {
            "name": "John Smith",
            "role": "captain"
        },
        {
            "name": "David Lee",
            "role": "wicketkeeper"
        },
        {
            "name": "Michael Brown",
            "role": "batsman"
        },
        {
            "name": "Chris Taylor",
            "role": "bowler"
        },
        {
            "name": "James Wilson",
            "role": "all_rounder"
        }
    ]
}
```

**Tests:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.success).to.be.true;
});

pm.test("Response has message", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData.message).to.eql("Opponent squad saved successfully.");
});
```

---

## Mobile API References

### iOS (Swift)

```swift
// Save Club Squad
func saveClubSquad(fixtureId: Int, players: [[String: Any]]) async throws -> Bool {
    let url = URL(string: "\(baseURL)/api/fixtures/\(fixtureId)/club-squad")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")
    
    let body: [String: Any] = ["players": players]
    request.httpBody = try JSONSerialization.data(withJSONObject: body)
    
    let (data, response) = try await URLSession.shared.data(for: request)
    
    guard let httpResponse = response as? HTTPURLResponse else {
        throw APIError.invalidResponse
    }
    
    if httpResponse.statusCode == 200 {
        let json = try JSONSerialization.jsonObject(with: data) as? [String: Any]
        return json?["success"] as? Bool ?? false
    }
    
    throw APIError.requestFailed
}

// Save Opponent Squad
func saveOpponentSquad(fixtureId: Int, players: [[String: Any]]) async throws -> Bool {
    let url = URL(string: "\(baseURL)/api/fixtures/\(fixtureId)/opponent-squad")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")
    
    let body: [String: Any] = ["players": players]
    request.httpBody = try JSONSerialization.data(withJSONObject: body)
    
    let (data, response) = try await URLSession.shared.data(for: request)
    
    guard let httpResponse = response as? HTTPURLResponse else {
        throw APIError.invalidResponse
    }
    
    if httpResponse.statusCode == 200 {
        let json = try JSONSerialization.jsonObject(with: data) as? [String: Any]
        return json?["success"] as? Bool ?? false
    }
    
    throw APIError.requestFailed
}
```

### Android (Kotlin)

```kotlin
// Save Club Squad
suspend fun saveClubSquad(fixtureId: Int, players: List<Map<String, Any>>): Result<Boolean> {
    return try {
        val url = "$baseURL/api/fixtures/$fixtureId/club-squad"
        val body = mapOf("players" to players)
        
        val request = Request.Builder()
            .url(url)
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Content-Type", "application/json")
            .post(RequestBody.create(MediaType.parse("application/json"), JSONObject(body).toString()))
            .build()
        
        val response = client.newCall(request).execute()
        
        if (response.isSuccessful) {
            val json = JSONObject(response.body()?.string() ?: "")
            Result.success(json.getBoolean("success"))
        } else {
            Result.failure(Exception("Request failed"))
        }
    } catch (e: Exception) {
        Result.failure(e)
    }
}

// Save Opponent Squad
suspend fun saveOpponentSquad(fixtureId: Int, players: List<Map<String, Any>>): Result<Boolean> {
    return try {
        val url = "$baseURL/api/fixtures/$fixtureId/opponent-squad"
        val body = mapOf("players" to players)
        
        val request = Request.Builder()
            .url(url)
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Content-Type", "application/json")
            .post(RequestBody.create(MediaType.parse("application/json"), JSONObject(body).toString()))
            .build()
        
        val response = client.newCall(request).execute()
        
        if (response.isSuccessful) {
            val json = JSONObject(response.body()?.string() ?: "")
            Result.success(json.getBoolean("success"))
        } else {
            Result.failure(Exception("Request failed"))
        }
    } catch (e: Exception) {
        Result.failure(e)
    }
}
```

### React Native (JavaScript)

```javascript
// Save Club Squad
async function saveClubSquad(fixtureId, players) {
  try {
    const response = await fetch(
      `${BASE_URL}/api/fixtures/${fixtureId}/club-squad`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ players }),
      }
    );

    const data = await response.json();
    
    if (response.ok) {
      return { success: true, data };
    } else {
      return { success: false, message: data.message, errors: data.errors };
    }
  } catch (error) {
    return { success: false, message: error.message };
  }
}

// Save Opponent Squad
async function saveOpponentSquad(fixtureId, players) {
  try {
    const response = await fetch(
      `${BASE_URL}/api/fixtures/${fixtureId}/opponent-squad`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ players }),
      }
    );

    const data = await response.json();
    
    if (response.ok) {
      return { success: true, data };
    } else {
      return { success: false, message: data.message, errors: data.errors };
    }
  } catch (error) {
    return { success: false, message: error.message };
  }
}
```

---

## Frontend References

### React Example

```typescript
interface Player {
  player_id: number;
  role: 'captain' | 'vice_captain' | 'wicketkeeper' | 'batsman' | 'bowler' | 'all_rounder';
}

interface OpponentPlayer {
  name: string;
  role: 'captain' | 'vice_captain' | 'wicketkeeper' | 'batsman' | 'bowler' | 'all_rounder';
}

// Save Club Squad
async function saveClubSquad(fixtureId: number, players: Player[]): Promise<ApiResponse> {
  const response = await fetch(
    `/api/fixtures/${fixtureId}/club-squad`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ players }),
    }
  );

  return response.json();
}

// Save Opponent Squad
async function saveOpponentSquad(fixtureId: number, players: OpponentPlayer[]): Promise<ApiResponse> {
  const response = await fetch(
    `/api/fixtures/${fixtureId}/opponent-squad`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ players }),
    }
  );

  return response.json();
}

// Usage Example
const handleSaveSquad = async () => {
  const players: Player[] = [
    { player_id: 12, role: 'captain' },
    { player_id: 15, role: 'wicketkeeper' },
    { player_id: 21, role: 'batsman' },
  ];

  const result = await saveClubSquad(25, players);
  
  if (result.success) {
    console.log('Squad saved successfully!');
    // Refresh fixture data
  } else {
    console.error('Error:', result.message);
    // Handle validation errors
    if (result.errors) {
      console.error('Validation errors:', result.errors);
    }
  }
};
```

### Vue.js Example

```vue
<template>
  <div>
    <form @submit.prevent="saveSquad">
      <div v-for="(player, index) in players" :key="index">
        <select v-model="player.player_id">
          <option v-for="p in availablePlayers" :key="p.id" :value="p.id">
            {{ p.name }}
          </option>
        </select>
        
        <select v-model="player.role">
          <option value="captain">Captain</option>
          <option value="vice_captain">Vice Captain</option>
          <option value="wicketkeeper">Wicket Keeper</option>
          <option value="batsman">Batsman</option>
          <option value="bowler">Bowler</option>
          <option value="all_rounder">All Rounder</option>
        </select>
      </div>
      
      <button type="submit">Save Squad</button>
    </form>
    
    <div v-if="error" class="error">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  fixtureId: Number,
});

const players = ref([
  { player_id: null, role: 'batsman' }
]);

const error = ref('');

const saveSquad = async () => {
  error.value = '';
  
  try {
    const response = await fetch(
      `/api/fixtures/${props.fixtureId}/club-squad`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ players: players.value }),
      }
    );

    const data = await response.json();
    
    if (data.success) {
      alert('Squad saved successfully!');
    } else {
      error.value = data.message;
      if (data.errors) {
        console.error('Validation errors:', data.errors);
      }
    }
  } catch (err) {
    error.value = 'Failed to save squad';
    console.error(err);
  }
};
</script>
```

---

## Notes

1. **Backward Compatibility:** The old endpoints are no longer available. All clients must update to use the new endpoints.

2. **Role Field:** The `role` field is now required for all squad players. This replaces the old `is_captain` and `is_wicket_keeper` boolean fields.

3. **Team Assignment:** The club team is automatically determined from the fixture. No need to send `team_id` in the request.

4. **Player Validation:** All players in the club squad must be active members of the club's team assigned to the fixture.

5. **Match Status:** Squads cannot be modified when the match is `live`, `paused`, or `completed`.

---

## Support

For issues or questions, please contact the development team or refer to the main API documentation.
