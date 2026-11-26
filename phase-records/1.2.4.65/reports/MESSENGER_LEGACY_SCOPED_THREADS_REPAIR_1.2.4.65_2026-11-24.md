## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Messenger legacy index migration repair — `.65` — 2026-11-24

## Finding

A clean synthetic WordPress installation using only the table definitions from `zmp-database-20261102-155759.sql.gz` reproduced the legacy Messenger migration failure during `.65` activation. The historical `scoped_threads` index on `zmp_role_messages` was `(scope_type,season_id,category_id,thread_id,id)`, while `.65` requires `(scope_type,season_id,category_id,region_key,thread_id,id)`. WordPress `dbDelta()` attempted to add the replacement under the already-used index name and MariaDB returned `Duplicate key name 'scoped_threads'`. The plugin activation command still reported the plugin active, leaving schema readiness false.

No dump rows were imported. The isolated database received only 93 `wp_yqqdt5_zmp_*` `CREATE TABLE` definitions, a blank synthetic WordPress installation, and temporary test records. No participant or account data from the dump was loaded. No production request, live database, email, YouTube, or outbound integration was used.

## Repair

`includes/class-zmp-role-messenger.php` now checks the exact Messenger messages table and reads the existing `scoped_threads` index metadata before `dbDelta()`. It replaces only an obsolete, verified non-unique index. It fails closed if the table metadata is unreadable, the index metadata is incomplete/ambiguous, the index is unexpectedly unique, or the targeted drop fails. Diagnostics contain only bounded schema names and fixed issue codes; no row contents or SQL error text are recorded.

Source before repair: `832F563E6202C118B908DB5B21E5C66DABCC023C01A796DEBEDAF257E4B5C1BE`.

Fixed candidate and local runtime source SHA-256: `BABFE20C900A4421DFD5154939EEE84BEB9824A80FF3B56639B8E9FC202BC655`.

The DDL-only extraction SHA-256 is `F7C8E207DA264C8A3EAAE6873BD0BF7F3ACB1AB48176F24415210D7229CFB104`. The `.66.b` source already contains an equivalent legacy-index preflight/repair (`class-zmp-role-messenger.php` SHA-256 `328606DE0A02633B3F7362E83257DF2B4C7946A230104479E78BC3F58C1D16BA`); this change brings `.65` into behavioral parity for this migration defect.

## Local results

- PHP syntax check: **PASS** on the fixed candidate and the installed runtime copy.
- Reproduction before repair: **FAIL reproduced** — MariaDB reported the duplicate `scoped_threads` key; the old index remained and compatibility was false.
- Upgrade of the legacy index with a synthetic message row present: **PASS** — activation completed without the duplicate-key error; the index became exactly `(scope_type,season_id,category_id,region_key,thread_id,id)`; schema compatibility returned true; the synthetic message row and its region value were preserved.
- Negative guard: **PASS** — a deliberately unique `scoped_threads` index was rejected; it remained unchanged and schema compatibility remained false. The guard did not drop an index whose semantics were unexpected.
- Recovery and idempotence: **PASS** — after restoring the expected non-unique index, activation and a second install both passed; the six index columns remained stable and compatibility stayed true.
- Cleanup and integrity: **PASS** — the exact synthetic message row was deleted, no fixture rows remained, and `wp db check` passed. No schema retry option remained after successful activation.
- Plugin runtime version: `1.2.4.65`; local PHP 8.5.4 / MariaDB 11.8.6 / WordPress 7.1.5; site bound only to `127.0.0.1:8096`.

## Disposition

The focused `.65` legacy-index migration defect is **FIXED / PASS at the scope above** and is no longer an open code defect. The original failed activation and its output remain historical evidence. This scoped PASS does not close unrelated `.65` routes, UI checks, integrated privacy/authorization coverage, phase review, packaging, or release checks. Overall `.65` remains open until its remaining gates are reconciled and tested.