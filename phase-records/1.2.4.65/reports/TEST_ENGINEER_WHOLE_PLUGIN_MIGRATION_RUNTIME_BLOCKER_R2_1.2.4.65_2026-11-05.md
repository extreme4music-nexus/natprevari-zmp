## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — whole-plugin migration runtime blocker R2 — 1.2.4.65

Date: 2026-11-05
Scenario: `.64` baseline preparation for `.64→.65` whole-plugin migration retained-row acceptance
Result: **BLOCKED during `.64` fixture preparation; migration acceptance NOT RUN**

## Candidate and review

- Fixture: `tests/phase65-whole-plugin-migration-retained-rows.php`
- Exact SHA-256 verified before staging and invocation: `B651E0FAE63288CC914ED3E44F784D704B0C5CE4103276796C2712F9F7721529`
- Independent exact-hash Code Review R2: **PASS**, report `CODE_REVIEW_WHOLE_PLUGIN_MIGRATION_FIXTURE_R2_1.2.4.65_2026-11-05.md`.
- `.64` and `.65` source manifest digests verified: `F54EF4E09ED09A29A3B718DB2BDC1EBBF6E0E9C1FBF5B633EE2AB99081B3CFF0` and `40E01B235B16511DE4D26560D1A6670DB1392BF5F167393BF074E9771A19B7CB`.

## Environment and preflight

- Confirmed the task site, database and temp child were absent before setup.
- Created a disposable synthetic WordPress site at `/home/tonic/zmp-local-sandbox/phase65-test/site-migration`, from WordPress core plus only the manifest-listed `.64` ZMP package (755 shipped files). No production data, third-party plugin, or upload data was copied.
- Reused existing MariaDB PID `144183` and PHP PID `145525` on `127.0.0.1:8094`; no service was started or stopped.
- Created isolated database `zmp65_migration_synthetic`, prefix `z65m_`; WP install and `.64` activation passed. Both schema markers were `1.2.4.64`; `wp db check` passed.

## Attempt and observed result

The fixture was invoked once with `ZMP_PHASE65_MIGRATION_ACTION=prepare` on the exact `.64` site. It inserted synthetic scenario rows, then stopped while enumerating table primary keys:

`SQL syntax error near 'ORDER BY Seq_in_index' for SHOW INDEX ... WHERE Key_name='PRIMARY' ORDER BY Seq_in_index`

The fixture emitted `BLOCKED / plugin_table_primary_key_unreadable` and did not create its manifest. A count-only post-attempt check showed the expected base competition plus one fixture competition; one synthetic mentor, school, student and registration row; DB integrity still passed. No IDs, names, PINs or row contents were recorded. `.64` markers remained at `1.2.4.64`; the `.65` package was never staged or activated, so migration and repeat-activation assertions did not run. This is a test-fixture SQL compatibility blocker, not a product migration FAIL.

## Cleanup and lifecycle

- Dropped only the task-created `zmp65_migration_synthetic` database, which contained the test-only site and inserted synthetic fixture rows.
- Removed only the task-created `site-migration` directory and `tmp/phase65-whole-plugin-migration` child after checking resolved target paths remained under `/home/tonic/zmp-local-sandbox/phase65-test` and were not symlinks.
- Verified both task directories and the database were absent. The persistent sandbox parent and existing `/site` were preserved.
- Existing MariaDB PID `144183`, PHP PID `145525`, and ports 3306/8094 remain active unchanged.

## Gate disposition and next action

- `whole_plugin_migration_retained_rows`: **NOT RUN**; this attempt did not perform a `.64→.65` migration.
- Correct the test-only fixture's primary-key metadata query for the actual MariaDB dialect, and ensure a prepare failure after synthetic inserts is cleaned safely or leaves enough task-owned state to clean explicitly. Obtain exact-hash review before another runtime attempt.
- No product code, frozen `.64` artifact, active `.65` site/database, persistent clone, ZIP, or GitHub artifact was changed.
