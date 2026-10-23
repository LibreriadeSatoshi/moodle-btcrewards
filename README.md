# moodle-btcrewards

A Moodle plugin that pays students in Bitcoin when they earn learning points.
Points are priced in USD; payouts are made in sats over Lightning at the live
BTC/USD rate.

This repo is the Moodle half — it pairs with a separate Bitcoin payout
microservice that holds the wallet and talks to the network.

## How it works

```
Moodle event → points → student claims → queue row → cron submits to service
                                                            │
                                          service pays via Lightning swap
                                                            │
                                          signed webhook flips row to paid
```

Rate and sats are locked at claim time; the service pushes terminal events
back via a signed webhook (no polling). Only Lightning addresses on the
allowed domain are paid.

## Privacy

The privacy provider exports a user's points and payouts and, on an approved
deletion request, deletes all of them (points, payouts and their links). Moodle
is not the financial record: every payout is also in the payout wallet and the
payment service's ledger, which keeps the amount and destination of each
payout. A payout that is in flight when its user is deleted still completes:
the payment service may send it after the rows are gone, and its webhook then
finds no payout (404) until the service gives up. The saved Lightning address
is a custom profile field, handled by Moodle's profile field privacy provider.

## Installation

1. Drop `local/btcrewards/` into your Moodle codebase.
2. Visit *Site administration → Notifications* to run the installer.
3. Configure under *Site administration → Plugins → Local plugins → Bitcoin
   Rewards*. Pair it with a running payment service: matching shared secrets
   for both `payment_service_secret` (outbound) and `webhook_secret`
   (inbound).
4. Make sure Moodle cron is running.

The admin page renders the webhook URL to copy into the service config.

## Local development

Wires the plugin into
[moodlehq/moodle-docker](https://github.com/moodlehq/moodle-docker) via the
included Makefile.

```sh
make init       # clone moodle-docker + moodle, render local.yml
make up         # start containers
make install    # run the Moodle installer (admin / Admin*123)
make shell      # bash into the webserver
make cli CMD="admin/cli/purge_caches.php"
make down       # stop (keep DB)
make purge      # stop and wipe volumes
```

The dev tree lives under `.dev/` (gitignored). The plugin is bind-mounted
into the webserver container.
