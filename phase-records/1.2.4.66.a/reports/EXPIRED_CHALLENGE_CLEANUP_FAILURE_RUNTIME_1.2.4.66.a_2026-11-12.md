# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Expired passkey challenge cleanup failure — isolated runtime result

Date: 2026-11-12 (Europe/Skopje)
Phase `.66.a` remains **IN PROGRESS / NOT_READY**.

## Exact artifacts and preflight

- Clone-specific fixture `tests/phase66a-login-expired-cleanup-failure-clone.php`, SHA-256 `A68FE9220B845EDB333862F15316A62C19698E6B5C33C8319DF0EDD37AC0E30F`.
- Original exact-hash clone-scope Code Review remains PASS: `reports/CODE_REVIEW_EXPIRED_CLEANUP_CLONE_FIXTURE_RECHECK_1.2.4.66.a.md`, SHA-256 `FD12A35B68A921CD1ACC8A2A7C80D9478F54DF047D9F91FED70B95DE691643AB`.
- PHP syntax check PASS. The fixture was copied to its guarded clone-private path and its hash matched before execution.
- Clone identity: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, DB `zmp66a_isolated`, private socket configuration, prefix `z66a_`, schema version 3. One active `.66.a` plugin; MariaDB PID 3828 alive; 8102 free. Pre-run baseline one user, one credential, zero challenges; DB check PASS.
- A fresh mode-600 private clone backup was made via the configured WordPress DB connection and export succeeded. Backup content and digest are not included in project records.

## Runtime outcome

The one exact-hash invocation exited 0 and emitted:

`OBSERVED cleanup_interceptions=1; generic_http_400=true; challenge_rows_after_request=1`

`PASS (5): expired cleanup failure fails closed`

`CLEANUP PASS`

The fixture intercepted only the exact expired-row DELETE query and replaced that query with invalid SQL. It verified one interception, the fixed generic HTTP 400 response, no new login challenge and no logged-in session. It removed its exact synthetic challenge and restored in-memory request/query state.

Post-run counts returned to baseline: one user, one credential, zero challenges. `wp db check` PASS. The staged clone fixture was removed and verified absent. Existing PHP site PID 25642/port 8101 remained running; 8102 remained free. No production site, Owner browser tab, real credential, or passkey ceremony was used.

## Disposition

This is **PASS for the focused expired-challenge cleanup failure behavior only**. It does not establish normal challenge cleanup timing, browser behavior, whole-phase acceptance, release readiness, ZIP, or GitHub synchronization. The prior preflight BLOCKED/NOT RUN remains historical; this later fresh exact-hash preflight and run supersede it for the current fixture. Phase remains IN PROGRESS / NOT_READY.