# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer Report — `.65` subject catalog clean install and nullable timestamp migration

## Result

**PASS — fixture output 5 assertions, 0 failures.** The exact-reviewed candidate was run once; no retry was made.

- Fixture: `tests/phase65-subject-catalog-clean-install-migration.php`
- SHA-256: `12760A55752042F9BE4E9AF82E116AC321EE3CDF49A5CC209A0EFFC453ADFFFA`
- PHP lint: PASS
- Exact-hash Code Review: PASS, `reports/CODE_REVIEW_SUBJECT_CATALOG_CLEAN_INSTALL_MIGRATION_1.2.4.65_2026-11-04.md`

## Preflight

- Disposable site: `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`
- URL: `http://127.0.0.1:8100`; DB `zmp65_subject_catalog_test`; prefix `z65_`
- Database host is the project-owned private socket `/home/tonic/zmp-local-sandbox/phase65-test/run/mariadb.sock`, served by MariaDB PID `144183`.
- Existing phase PHP server PID `218531` was listening only on `127.0.0.1:8100`; it was reused and not started/stopped for this run.
- One synthetic administrator (`@example.invalid`), one active plugin (`zmp-competitions` 1.2.4.65), proposal schema ready, and catalog/proposal/event/alias/mentor rows all zero.
- Pre-run `wp db check`: PASS. Workspace and installed source hashes matched both exact reviewed guards.

## Assertions and post-run state

The fixture passed catalog-table clean creation with the complete type/index/NOT NULL contract; recognized nullable drift as incompatible; repaired it; preserved pre-existing non-NULL timestamp values and other row values; backfilled NULL timestamps; and restored canonical readiness.

Post-run checks: `wp db check` PASS; proposal readiness PASS; catalog/proposal/event/alias/mentor row counts all zero; `created_at` and `updated_at` are `Null=NO`; `is_active` is `tinyint(1)`; primary, unique subject-key and status/active indexes are present; only the one synthetic admin and ZMP plugin remain. The staged fixture was removed and its absence verified. No product source changed.

Earlier failed migration candidates remain preserved in the test ledger; this PASS applies only to the exact hash above and the scoped catalog-table migration behavior.
