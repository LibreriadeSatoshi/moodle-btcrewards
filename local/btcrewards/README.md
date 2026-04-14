# local_btcrewards

A Moodle local plugin that pays students in Bitcoin satoshis over Lightning when
they accumulate learning points. Moodle events (course completion, passing
grades, badges) are translated into points; once a student crosses a configurable
threshold, a payout row is queued and drained by a scheduled task that talks to
an internal Lightning payment microservice.

## Installation

1. Copy this directory to `local/btcrewards/` inside your Moodle codebase.
2. Visit **Site administration → Notifications** to run the plugin installer.
3. Configure the plugin at **Site administration → Plugins → Local plugins →
   Bitcoin Rewards**.
4. Make sure Moodle cron is running — the payout queue is drained every 5
   minutes by `local_btcrewards\task\process_payout_queue`.

## Configuration reference

| Setting                   | Default               | Purpose |
|---------------------------|-----------------------|---------|
| `points_source`           | `native`              | Backend used to read/write points. |
| `payout_threshold`        | `500`                 | Points above the watermark before a payout is queued. |
| `sats_per_point`          | `10`                  | Conversion rate at payout time. |
| `max_attempts`            | `5`                   | Retries before a queue row is marked failed. |
| `payment_service_url`     | `http://10.0.0.2:3000`| Base URL of the Lightning microservice. |
| `payment_service_secret`  | *(empty)*             | Sent as `X-Internal-Token`. |
| `points_course_completed` | `200`                 | Awarded on `\core\event\course_completed`. |
| `points_quiz_passed`      | `50`                  | Awarded on `\core\event\user_graded` at/above pass mark. |
| `points_badge_awarded`    | `100`                 | Awarded on `\core\event\badge_awarded`. |

## Extending with a new point source

1. Create `classes/points_source/<name>.php` implementing
   `local_btcrewards\points_source\base`.
2. Register it in `classes/points_source/factory.php` by adding it to the
   `$map` array (e.g. `'xp' => xp::class`).
3. Add a language string `source_<name>` and expose it in `settings.php` under
   the `points_source` select.

The factory calls `is_available()` and will throw a `moodle_exception` if the
selected source's dependencies are missing, so feel free to check for required
plugins inside that method.

## Payment service contract

The plugin talks to a single internal HTTP service. All requests carry the
header `X-Internal-Token: <payment_service_secret>`.

### `POST /pay`

Request body:

```json
{
  "amount_sats": 5000,
  "destination": "student@getalby.com",
  "dest_type": "ln_address"
}
```

`dest_type` is one of `ln_address`, `onchain`, or `bolt11`.

Response body:

```json
{
  "success": true,
  "txid": "abc123",
  "error": ""
}
```

### `GET /status/{txid}`

Response body:

```json
{
  "status": "paid",
  "txid": "abc123"
}
```

Any non-2xx response raises a `local_btcrewards\payment_exception`, which the
payout engine catches and records as a failed attempt on the queue row.
