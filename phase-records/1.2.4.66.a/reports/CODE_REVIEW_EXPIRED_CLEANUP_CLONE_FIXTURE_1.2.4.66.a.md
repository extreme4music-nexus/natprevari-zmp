# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Clone-Specific Expired-Cleanup Failure Fixture

**Date:** 2026-11-11  
**Verdict:** **BLOCKED FOR EXECUTION — DB SOCKET IDENTITY IS NOT GUARDED**  
**Scope:** Read-only exact-hash review and comparison to the original fixture. No test, database, browser, server, or source mutation.

## Exact hashes and comparison

- Clone-specific fixture: `tests/phase66a-login-expired-cleanup-failure-clone.php` SHA-256 `D508D43D573AA2275D47CBC8AB63C1C83B647AB2FD706FAE6DB1B3D0A8FC4444` (recomputed; matches assigned hash).
- Preparation note: `reports/EXPIRED_CLEANUP_FAILURE_CLONE_FIXTURE_PREPARATION_1.2.4.66.a.md` SHA-256 `6DC61E9E6FB0DD6B24CC528BE717DEDCCFC495444334776CD5BBBF70277AB989` (recomputed; matches assigned hash).
- Original fixture: `tests/phase66a-login-expired-cleanup-failure.php` SHA-256 `60F2FDD5AEC0816DDD0EBC7EC149ECA615587C35C799B25D7992D823BB84B584` (recomputed; matches assigned hash).
- A normalized line comparison confirms the clone-specific file is unchanged outside its top site/path guard block. The original DB name `zmp66a_isolated`, prefix `z66a_`, schema marker `3`, and schema compatibility checks remain present.

## Safety finding

### Blocker — expected clone database host/socket is not enforced

The clone guard verifies the site filesystem path, fixture path, database name and prefix, and passkey schema (`:10-19`). It does **not** verify `DB_HOST` (or another effective connection identity) against the clone-private MariaDB socket recorded in the preparation note: `localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`.

The Owner/main local site also uses DB name `zmp66a_isolated` and prefix `z66a_`. If the clone's `wp-config.php` or effective DB configuration were pointed at the Owner database while retaining the clone filesystem path, the current guard could pass. Baseline counts/fingerprints may also coincide. The fixture would then insert and delete a challenge row on a database outside its allowed target. The one narrow failure injection does not make that connection risk acceptable.

**Required resolution before execution:** add an exact `DB_HOST` check for the clone-private socket (and, if the project’s connection configuration permits routing through alternate constants, verify the effective socket endpoint as well). Recompute the fixture SHA and obtain independent review of that new hash. Before any run, separately confirm DB host/name/prefix against the live clone connection, create and verify a fresh clone-only database backup, and recheck the baseline/integrity. Do not weaken the database-name/prefix/schema checks.

## Other reviewed controls

- The injected `$wpdb` query filter matches only the exact `DELETE FROM <challenge table> WHERE expires_at <= UTC_TIMESTAMP()` string and substitutes one invalid statement. It does not intercept other SQL. The fixture checks one interception, fixed generic HTTP 400, no added challenge, and no WordPress session.
- It invokes the registered `wp_ajax_nopriv_zmp_passkey_login_options` action in-process and contains no HTTP/network request or server startup.
- The synthetic expired row is uniquely random and cleanup deletes only its owned challenge hash. The fixture restores user/POST/server globals, WPDB suppression/query/error state, filters, and checks user/credential/challenge counts plus the protected credential fingerprint.
- It exercises a locally synthesized expired row with the same cleanup statement and generic endpoint response; it does not exercise a real browser login or production behavior.

## Limits

This is fixture safety review only. The test was not run and no DB access occurred. Because of the missing clone socket guard, the fixture is **BLOCKED for execution** despite the unchanged failure-injection and cleanup body. No product, phase, package, or release acceptance is implied.

