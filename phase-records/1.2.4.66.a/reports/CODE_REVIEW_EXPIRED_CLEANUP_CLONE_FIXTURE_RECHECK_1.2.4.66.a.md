# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Clone-Specific Expired-Cleanup Fixture Recheck

**Date:** 2026-11-11  
**Verdict:** **SPEC PASS — CLONE-SCOPE BLOCKER RESOLVED; STATIC FIXTURE REVIEW ONLY**  
**Scope:** Read-only exact-hash re-review of the clone-specific fixture and its preparation note. No test execution, DB access, browser/server operation, or source/fixture edits.

## Exact artifacts

- Clone fixture `tests/phase66a-login-expired-cleanup-failure-clone.php`: SHA-256 `A68FE9220B845EDB333862F15316A62C19698E6B5C33C8319DF0EDD37AC0E30F` (recomputed; matches assignment).
- Preparation note `reports/EXPIRED_CLEANUP_FAILURE_CLONE_FIXTURE_PREPARATION_1.2.4.66.a.md`: SHA-256 `7D1EBE3C9094ED3E34D69DEDFD7B8839EFA39A942ED8BA933206FE3391A73473` (recomputed; matches assignment).
- Original fixture `tests/phase66a-login-expired-cleanup-failure.php`: SHA-256 `60F2FDD5AEC0816DDD0EBC7EC149ECA615587C35C799B25D7992D823BB84B584` (recomputed; unchanged).
- Prior blocked clone review is preserved in `reports/CODE_REVIEW_EXPIRED_CLEANUP_CLONE_FIXTURE_1.2.4.66.a.md`, SHA-256 `C17A8A2FE06A0DF4F99ADA299B424A7633AAA2F6833B6763723F2AD651C440EF`.

## Resolution of prior blocker

The clone fixture now requires all of the following in its top-level guard (`:10-20`): exact clone filesystem root, `DB_NAME=zmp66a_isolated`, exact clone-private `DB_HOST=localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`, prefix `z66a_`, exact invocation path and resolved file path, schema marker `3`, and structural schema compatibility. This closes the earlier gap where same-named DB/prefix values could have allowed a clone config pointed to the Owner socket.

A normalized line comparison confirms the body after the guard is byte-for-byte line-identical to the original fixture; the only change is the clone site/path/DB-host identity guard. The original fixture hash remains unchanged. The preparation note records the same DB socket and preserves the required fresh clone-only backup before any future run.

## Failure injection and cleanup scope

- The `$wpdb` filter still matches only the exact expired-row cleanup statement for the prefixed passkey challenge table and substitutes one invalid SQL statement. No other query is changed.
- It creates one random synthetic expired challenge, then invokes the actual unauthenticated login-options callback in-process. There is no HTTP request, external network call, or server startup.
- It requires the one-user/one-credential/zero-challenge baseline and protected synthetic credential row before creating data. Expected checks remain exactly one interception, fixed generic HTTP 400, no new login challenge, and no logged-in session.
- `finally` removes the exact owned challenge, filter and temporary callback hooks; restores WPDB error suppression/query/error state, current user, POST and server globals; then verifies table counts and protected credential fingerprint. It does not delete or rewrite credential rows.

## Limits and status

**Static fixture safety: PASS.** The clone path, database name, private socket, prefix and schema are all explicitly guarded; no Owner DB/site route was found in the reviewed fixture. This relies on the standard WordPress DB configuration honoring the asserted `DB_HOST`; this static review did not inspect runtime connection state.

The fixture remains **PREPARED / NOT RUN**. Before execution, a fresh private clone-only database backup and live clone identity/baseline/integrity confirmation remain required by the preparation note. This report does not establish runtime behavior, product acceptance, phase acceptance, package or release readiness.

