# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger finding — subject catalog timestamp nullability mismatch

Date: 2026-11-04

## Confirmed root cause

The clean-clone evidence reported by the Lead is consistent with source:

- `includes/class-zmp-mentor-subjects.php:18–32` declares catalog `created_at` and `updated_at` as `DATETIME DEFAULT CURRENT_TIMESTAMP`, without `NOT NULL`.
- `includes/class-zmp-mentor-subject-proposals.php:439–449` specifies both columns as `nullable=false`; the strict comparison at lines 463–468 rejects the clean-clone `SHOW COLUMNS` results where these columns are nullable.
- `schema_is_compatible()` returns the conjunction of proposal, event, alias and catalog checks at lines 391–428. `install()` runs dbDelta then returns that same check at lines 13–59.

Therefore the fresh install is reported incompatible even with both columns present and the expected indexes. `ZMP_Mentor_Subjects::install()` uses its weaker column-name/index predicate and may return ready, but the proposal install/combined readiness gate fails on the catalog shape.

## Retry behavior

Activation runs the install set under a migration lock and passes all readiness results to `zmp_competitions_commit_messenger_schema()` (`zmp-competitions.php:144–179`). An incompatible subject-proposal schema keeps the shared retry pending. Ordinary initialization retries the install set only while that shared marker is pending and `schema_retry_allowed()` permits it (`zmp-competitions.php:382–424`). The Messenger retry code sets a five-minute deadline in `defer_schema_retry()` and rejects attempts before expiry (`includes/class-zmp-role-messenger.php:279–348`).

With the current DDL unchanged, each retry recreates the same nullable catalog columns and fails the same strict predicate. It cannot self-repair; the marker remains pending and automatic attempts repeat only after the cooldown. Reactivation is immediate but still repeats the mismatch.

## Minimal safe correction and verification

Make the DDL and readiness contract agree. The strict predicate suggests the intended invariant is non-null creation/update timestamps. If confirmed, declare both columns `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` and backfill any existing NULL values before applying dbDelta's nullability tightening. Verify both a blank install and an upgrade fixture containing NULL timestamps: preserve row counts, confirm `Null=NO`, verify indexes and the full schema predicate, then confirm the migration commit clears retry intent. Exercise fail-closed and retry behavior only after the schema repair.

If nullable timestamps are intentionally supported, change the predicate to `nullable=true` instead and test read/write behavior on null values. Do not clear the retry marker or relax unrelated schema checks to force readiness.

Analysis was source-only; no code, database or server was changed and no new runtime check was run.
