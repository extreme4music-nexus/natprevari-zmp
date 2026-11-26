# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — whole-plugin migration runtime blocker R3 — 1.2.4.65

Date: 2026-11-05
Scenario: `.64` baseline preparation and `.64→.65` upgrade for whole-plugin retained-row acceptance
Result: **BLOCKED during `.65` verification; migration acceptance NOT RUN**

## Candidate and review

- Fixture: `tests/phase65-whole-plugin-migration-retained-rows.php`
- Exact SHA-256 verified from both workspace and WSL-mounted source before staging: `33B6134BB3FD9F86DBE5B218A30A94C7921441FD29B4975941A5028B9B4D6153`.
- Independent exact-hash Code Review: **PASS**, `CODE_REVIEW_WHOLE_PLUGIN_MIGRATION_FIXTURE_R3_1.2.4.65_2026-11-05.md`; PHP lint PASS.
- Source manifest digests verified: `.64` `F54EF4E09ED09A29A3B718DB2BDC1EBBF6E0E9C1FBF5B633EE2AB99081B3CFF0`; `.65` `40E01B235B16511DE4D26560D1A6670DB1392BF5F167393BF074E9771A19B7CB`.

## Environment and `.64` preparation

- Created task-only site `/home/tonic/zmp-local-sandbox/phase65-test/site-migration` from WordPress core and the exact manifest-listed `.64` ZMP package (755 shipped files). No production data, third-party plugin, uploads, or source-clone database were copied.
- Reused existing MariaDB PID `144183` and PHP PID `145525` on port `8094`; no service was started or stopped.
- Created isolated DB `zmp65_migration_synthetic` with prefix `z65m_`; clean WordPress install, `.64` plugin activation, schema markers (`1.2.4.64`) and `wp db check` passed.
- `.64` fixture `prepare` passed and created six synthetic scenario records with hash-only manifest SHA-256 `091e6c6e3a1b8f01edc92ef04c5746693166de04d1cb819761123300e00b7029`.

## `.65` upgrade and verification attempt

- Replaced the task site's plugin with the exact manifest-listed `.65` package (755 files). Normal WP-CLI bootstrap advanced `zmp_db_version` and `zmp_role_version` to `1.2.4.65`; `wp db check` passed.
- The reviewed fixture `verify` action stopped with a PHP fatal at its schema-readiness assertion: `Call to undefined method ZMP_Mentor_Subjects::schema_is_compatible()` in `.65`.
- The full-table inventory and comparison code occurs before that assertion, but the fatal prevented the fixture from emitting its assertion JSON. No retention PASS is claimed. Repeat activation assertions did not run. This is a fixture API mismatch, not a product migration FAIL.

## Fixture cleanup and database integrity

- Invoked only the reviewed fixture's `cleanup` action, which passed: all five fixture-owned ZMP rows and its synthetic user were removed, no tokenized competition marker remained, and the manifest was removed.
- Post-cleanup `wp db check` passed. `.65` schema markers remained `1.2.4.65`; the base synthetic competition count returned to one and mentor, school, student, and registration row counts returned to zero.

## Environment cleanup and lifecycle

- Dropped only the task-created database `zmp65_migration_synthetic` after confirming the site's exact current database identity.
- Removed only task-created `site-migration` and `tmp/phase65-whole-plugin-migration` paths after verifying each resolved under `/home/tonic/zmp-local-sandbox/phase65-test` and was not a symlink.
- Verified task site, DB and temp child were absent. Preserved the sandbox parent and original `/site`.
- Existing MariaDB PID `144183`, PHP PID `145525`, and ports 3306/8094 remain active and unchanged.

## Gate disposition and next action

- `whole_plugin_migration_retained_rows`: **NOT RUN**. A `.64→.65` upgrade and DB integrity check occurred, but the required retained-row assertion did not produce verifiable output.
- Correct the test-only `.65` readiness probe to use an API that exists in the candidate, obtain exact-hash independent review, then run a fresh isolated scenario. Do not repeat prior candidate hashes.
- No product code, frozen `.64` artifact, active `.65` site/database, persistent clone, ZIP, or GitHub artifact was changed.
