# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Expired-challenge cleanup fixture clone preparation — `.66.a`

Status: **STATIC REVIEW PASS — RUNTIME NOT RUN**. Exact-hash review passed; a fresh private clone database backup was verified. The permitted final read-only preflight returned nonzero, so the fixture remains unrun.

- Original fixture: `tests/phase66a-login-expired-cleanup-failure.php`; SHA-256 `60F2FDD5AEC0816DDD0EBC7EC149ECA615587C35C799B25D7992D823BB84B584` (rechecked unchanged).
- Clone-specific copy: `tests/phase66a-login-expired-cleanup-failure-clone.php`; SHA-256 `A68FE9220B845EDB333862F15316A62C19698E6B5C33C8319DF0EDD37AC0E30F`.
- Scope guard accepts only site `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, database `zmp66a_isolated`, DB host `localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`, prefix `z66a_`, schema marker 3/compatible schema, and the exact future clone-private fixture path `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-login-expired-cleanup-failure-clone.php` passed as the WP-CLI `eval-file` argument. The original fixture and Owner site/database were not modified.
- The copied test body, assertions, injected cleanup failure, synthetic row setup, and cleanup logic are unchanged; only the site identity and exact fixture-path guard differ. No test, runtime, database, or service was changed or started.
- The prior original-fixture attempt remains **BLOCKED** because its hard-coded site identity did not match the authorized retest clone. This copy does not overwrite or reinterpret that evidence.
- Next safe step: obtain independent review of this exact fixture hash; after PASS, take and verify a fresh private clone-only database backup, recheck clone identity/baseline/integrity, then decide on the single authorized run.
## Engineering Lead runtime continuation — 2026-11-12
The prior Test Engineer report above remains a truthful record of its own NOT RUN result. On 2026-11-12, the same reviewed clone-specific fixture SHA A68FE9220B845EDB333862F15316A62C19698E6B5C33C8319DF0EDD37AC0E30F passed one later fresh-preflight isolated run: fixed generic HTTP 400, no new challenge/session, cleanup and DB integrity PASS. Runtime evidence rreports/EXPIRED_CHALLENGE_CLEANUP_FAILURE_RUNTIME_1.2.4.66.a_2026-11-12.md, SHA 5CB84F082C3C526A8D5713994F632C1AB5664DA1D9564AB41B5AFBDADA2B082A. This additive continuation does not change the earlier agent's report or erase its preflight blocker.