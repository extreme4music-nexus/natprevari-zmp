# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — whole-plugin migration fixture preparation blocker — 1.2.4.65

Date: 2026-11-05
Scenario: `.64` baseline preparation for `.64→.65` whole-plugin migration retained-row acceptance
Result: **BLOCKED before fixture preparation; migration acceptance NOT RUN**

## Candidate and provenance

- Fixture: `tests/phase65-whole-plugin-migration-retained-rows.php`
- Exact SHA-256 verified before execution: `7F13C3A1E64B3275CD2E0F1F27F82F2825D4F23EFC3EA1AC56F4EF464ABAC047`
- The exact candidate had independent Code Review PASS before runtime. No fixture/source edits were made during this run.
- Source manifests were verified by digest: `.64` `F54EF4E09ED09A29A3B718DB2BDC1EBBF6E0E9C1FBF5B633EE2AB99081B3CFF0`; `.65` `40E01B235B16511DE4D26560D1A6670DB1392BF5F167393BF074E9771A19B7CB`.

## Environment and preflight

- Confirmed task site `/home/tonic/zmp-local-sandbox/phase65-test/site-migration` and database `zmp65_migration_synthetic` did not exist before this run.
- Reused the existing synthetic local sandbox’s WordPress core files, copying only core files and the exact manifest-listed `.64` ZMP package (753 shipped files). The new site contained only the ZMP plugin; no third-party plugins, uploads, or source-clone data were copied.
- Reused existing MariaDB PID `144183` and PHP PID `145525` on `127.0.0.1:8094`; no server/service was started or stopped.
- Created fresh isolated database with prefix `z65m_`; WordPress installation and `wp db check` passed.
- Activated exact `.64` package. `zmp_db_version` and `zmp_role_version` both reported `1.2.4.64`; `wp db check` passed.

## Attempt and result

Ran the fixture once in `prepare` mode on the exact `.64` installation. It stopped before fixture seeding with:

`PHP Fatal error: Call to undefined method ZMP_Mentor_Subjects::schema_is_compatible()`

The method is unavailable in the `.64` source. The existing `.64` installation remained at version markers `1.2.4.64`; the fixture manifest was absent; fixture-owned mentor, school, student, and registration row counts remained zero. The single competition row was the normal synthetic `.64` activation seed. No `.65` package was staged or activated, so no `.64→.65` migration or repeat activation assertion ran. This is a fixture compatibility defect, not a product migration FAIL.

## Cleanup and lifecycle

- Dropped only the task-created database after confirming the target site's current database identity was exactly `zmp65_migration_synthetic`.
- Removed only the task-created `site-migration` directory and its `tmp/phase65-whole-plugin-migration` child after verifying their resolved paths stayed inside `/home/tonic/zmp-local-sandbox/phase65-test` and neither target was a symlink.
- Verified the task site and temporary child were absent; queried the existing synthetic site and confirmed `zmp65_migration_synthetic` no longer existed.
- Existing MariaDB PID `144183`, PHP PID `145525`, port 3306 and port 8094 remain active as before. The persistent sandbox parent and existing `/site` remain intact.

## Gate disposition and next action

- `whole_plugin_migration_retained_rows`: **NOT RUN**; this attempt is a fixture preparation blocker, not migration acceptance evidence.
- Correct the test-only fixture so the `.64` preparation branch checks only schema APIs present in `.64` (or uses explicit compatible probes); exact-hash Code Review it again, then repeat a clean disposable `.64→.65` runtime once.
- No product code, frozen `.64` artifacts, active `.65` site/database, persistent clone, ZIP, or GitHub artifact was changed.
