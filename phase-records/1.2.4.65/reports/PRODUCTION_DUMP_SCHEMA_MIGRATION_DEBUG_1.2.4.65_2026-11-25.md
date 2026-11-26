# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# `.65` production-dump schema reproduction — 2026-11-25

## Source and isolation

- Source SQL: `/mnt/z/Documents/Natprevari_ZMP/WSL/ZMP-SQL/zmp-database-20261125-162548.sql`
- Source SHA-256: `7F2FA1428B0372A72CA40672AE80C6B3C4058B273696591B95586375B486857E`
- The user-provided SQL dump was imported into the new local database `zmp_debug_prodclone_20261125`; it was not replaced with a hand-built schema. The empty database was only an import destination. The original dump was not modified.
- WordPress source was copied from `/mnt/z/Documents/Natprevari_ZMP/WSL/ZMP-Site/site` to `/home/tonic/zmp-prodclone-debug-20261125/site`. The original clone was left untouched. User-uploaded media, cache, and backup folders were excluded from the copy.
- The dump uses prefix `wp_yqqdt5_`; the copied `wp-config.php` was retargeted to that prefix and the isolated local database. External HTTP and cron were disabled in the copied config; salts were rotated.
- Existing user accounts, usermeta, comments, teacher/school records, ZMP user/transaction records, payment/form/OAuth/mail/Drive/gallery/session data, and historical debug events were scrubbed. 165 sensitive tables were cleared (16,727 rows removed); 13 general competition/reference tables were retained. Published public pages were retained without author IDs; postmeta was cleared. Old database options containing credentials, tokens, or email settings were scrubbed, and admin email was changed to an `.invalid` address.
- Exactly two synthetic WordPress accounts were created: an administrator with `subscriber`, and a teacher with `subscriber` + `zmp_teacher`. The teacher has one synthetic mentor row and one synthetic primary-school row. No real account or teacher profile remains in the test database.

## Plugin installation and migration

- The old plugin directory in the copied site was removed from `wp-content/plugins` and preserved outside the webroot at `/home/tonic/zmp-prodclone-debug-20261125/removed-plugin-code/zmp-competitions-old`.
- Installed and activated the exact `.65` ZIP: `zmp-competitions-1.2.4.65-2026-11-25.zip`; ZIP SHA-256 `D6DF6E9E466FB3BECCF91D0F151C526905931AB80728A957C5D42D4B0755698C`. Active plugin reports version `1.2.4.65`.
- Before activation the imported database reported `zmp_db_version=1.2.4.53.2`, with `zmp_role_messenger_schema_retry=1` and a retry deadline. The existing Messenger `scoped_threads` index already included `region_key`; the key profile and mentor-school columns/indexes were present in the imported schema.
- Activation of the installed `.65` completed the schema migration: `zmp_db_version` became `1.2.4.65`, the retry markers were removed, and compatibility checks returned **ready** for Messenger, Messenger retention, school profile, mentor schools, school email access, and subject proposals.

## Reproduction result

- Using the actual `edit_user_profile_update` save hook with a valid nonce and the synthetic administrator/teacher role stacks, the primary school was saved successfully.
- Readback as the synthetic `subscriber + zmp_teacher` account returned the school profile successfully (15 fields). Exactly one mentor row and one active primary-school row exist.
- Repeated save of the same synthetic profile passed without creating a duplicate school.
- PHP/WP-CLI schema checks all returned ready; `wp db check` passed.
- A local PHP server is running only on `127.0.0.1:8111` (PID recorded in `/home/tonic/zmp-prodclone-debug-20261125/php-server.pid`). `GET /wp-login.php` returned HTTP 200.

## Finding and limits

On the exact supplied dump, a fresh activation of the current `.65` ZIP cleared the persisted retry state and made the primary-school profile save work. This points to an uncommitted/pending schema migration in the imported starting state; the local reproduction did **not** reproduce a persistent `.65` code defect after activation.

The production diagnostic excerpt contains repeated generic `schema_not_ready` events for school-profile and school-email prerequisites, but no `schema_table`, `schema_issue`, `schema_expected`, or `schema_actual`. The generic Messenger notice is emitted by the shared readiness gate when any required component fails, so the notice alone does not prove that Messenger's index is still wrong. The export is insufficient to name the exact production table/column/index mismatch. This report does not claim production was repaired or that its active code hash was verified.

No production site, production database, external service, or outbound integration was accessed. Main `PROJECT_ROADMAP.md` was not changed. Synthetic-data cleanup record: `/home/tonic/zmp-prodclone-debug-20261125/data-sanitization.json`.

## Retry-window timing from the imported dump
The imported option `zmp_role_messenger_schema_retry_after` was `1795624172`, which is **2026-11-25 16:29:32 UTC / 17:29:32 Europe/Skopje**. The dump header says it was created at **16:25:48 UTC / 17:25:48 local**, so its recorded retry deadline was 3 minutes 44 seconds after the dump snapshot. The attached diagnostic excerpt ends at 17:07 local and contains no later event. Therefore it does not show the first post-deadline request, and cannot establish whether the production retry later succeeded or failed. Fresh plugin activation in the isolated clone ran migration immediately and cleared that pending state.


## Follow-up — ordinary update path distinguished from activation

The earlier test proved migration on fresh activation, but did not test the normal plugin-update path with a persisted future retry deadline. A new controlled A/B run reproduced that gap: pre-fix code left a confirmed old schema marker unchanged; the corrected ordinary init path migrated it immediately. The exact fix and new package are documented in SCHEMA_UPGRADE_RETRY_COOLDOWN_FIX_1.2.4.65_2026-11-25.md. This follow-up narrows the earlier conclusion; it does not identify the exact production table mismatch from the generic diagnostic excerpt.
