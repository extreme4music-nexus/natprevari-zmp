## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Local `.66.b` installation and legacy Messenger index repair — 2026-11-18

## Setup boundary

- The cloned `wp-config.php` initially pointed to the live database host. It was redirected to `127.0.0.1` and a local-only prefix before WordPress runtime commands. No plugin activation or migration was run against production.
- The full SQL dump from 2026-11-18 was read locally as a gzip stream. Only the `CREATE TABLE` definition for `wp_yqqdt5_zmp_role_messages` was printed/read for this investigation; the dump was not imported and no row data was inspected.
- The isolated runtime is `/home/tonic/zmp-isolated-test` (WordPress 7.1.5), with a new empty MariaDB database and synthetic administrator.

## Confirmed cause

The dump's `scoped_threads` index is `scope_type,season_id,category_id,thread_id,id`. It lacks `region_key`. The `.66.b` schema contract requires `scope_type,season_id,category_id,region_key,thread_id,id`.

This mismatch reproduces the supplied live error exactly: after creating the dump's old index in the isolated database, `dbDelta` attempted `ADD KEY scoped_threads (...)` and MariaDB returned `Duplicate key name 'scoped_threads'`. The plugin then recorded `schema_actual=duplicate_key` and failed closed.

## Local repair and verification

Added a preflight to `ZMP_Role_Messenger::install()` that reads only the target table's index metadata. If the existing `scoped_threads` index is the obsolete nonunique definition, it removes that index before `dbDelta` creates the current definition. It refuses to remove a unique index, fails closed on incomplete metadata/drop errors, and logs only the table/index names and column lists. It does not change Messenger rows.

- Plugin `zmp-competitions` active, version `1.2.4.66.b`.
- Source/runtime SHA-256 for `class-zmp-role-messenger.php`: `328606de0a02633b3f7362e83257df2b4c7946a230104479e78bc3f58c1d16ba`.
- PHP syntax check: PASS.
- Exact legacy-index reproduction: PASS; observed the same `Duplicate key name 'scoped_threads'`.
- Repair run: installer `true`; compatibility check `true`; index now includes `region_key` in the required order.
- Inserted one synthetic Messenger row before repair; verified subject/message/region survived; deleted that row afterward. Preservation and cleanup: PASS.
- Full local `wp db check`: PASS.

## Production scope

The 19:44 dump predates the supplied 20:03–20:19 failures but contains the exact legacy index that produces those errors. This establishes the cause represented by the dump and validates the code repair locally. The live database has not yet been migrated or re-read after the dump; install the updated plugin and confirm the next diagnostic reports `legacy_index_replaced`, followed by a ready Messenger prerequisite, before treating production as resolved. Automatic retention remains fail-closed until that confirmation.

## Test ZIP packaged for Owner validation — 2026-11-18

- Package: `Z:\Documents\Natprevari_ZMP\ZMP_PROJECT_LIBRARY\zmp-competitions-1.2.4.66.b-MESSENGER-INDEX-FIX-TEST.zip`
- SHA-256: `B7B7A0FFA7B9B1EF21938BCB3383DE285171D25959B5BFA55E2D23747712AFBF`
- ZIP contains 2,246 entries under `zmp-competitions-66b/`; the phase Messenger file hash was verified against the packaged file. Required main plugin file is present; project reports, temporary fixtures, and `.temp` are excluded.
- The package contains the locally verified legacy-index repair. Production installation and post-retry confirmation remain pending Owner installation.
