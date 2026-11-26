# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Current-source passkey login regression batch — `.66.a`

**Date:** 2026-11-11  
**Overall scope result:** Four current-hash behavior fixtures **PASS** with cleanup; one expired-cleanup fixture **BLOCKED before execution** by its hard-coded site-path guard. This is focused synthetic evidence only, not whole-phase acceptance.

## Candidate and environment

- Current first-party source manifest: `CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES.txt`, SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`. Existing exact-hash review/test evidence records 67/67 first-party source-file parity for the clone.
- Reverified key clone source hashes: passkeys `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; passkey store `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; plugin entrypoint `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`.
- Target: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, DB `zmp66a_isolated`, prefix `z66a_`. Home/site URL is `http://127.0.0.1:8102`; only active plugin is `zmp-competitions`. Schema marker `3`, schema compatibility PASS, initial users/credentials/challenges `1/1/0`, and the protected synthetic UI credential was present. `wp db check` PASS.
- A private clone-only recovery backup was verified before any fixture mutation (mode `0600`, readable and nonempty). Its identifiers and contents remain only in the sandbox.
- Owner PHP PID 620 remains bound to `127.0.0.1:8101`; Owner MariaDB PID 409 and clone MariaDB PID 3828 remain present. No PHP listener existed on clone port 8102, and no server was started for these in-process fixtures.

## Fixture scope and transport

The NAS share is not mounted inside WSL. Four fixtures were copied byte-for-byte into the clone-private test directory and their SHA-256 values matched the originals before execution. No source fixture was edited. Each runnable fixture invokes the actual registered WordPress AJAX hooks using in-process `do_action()`; none calls `wp_remote_*`, cURL, or an HTTP client. Their synthetic request context comes from the clone URL at port 8102. The context/rate fixture contains `http://localhost:8101` only as a deliberately different context digest for a fixture-owned row; it does not send a request to port 8101.

## Results

Commands below use the clone-private copies after exact hash verification. Each ran once with WP-CLI `eval-file`. After each runnable suite, fixture cleanup reported PASS; baseline users/credentials/challenges returned to `1/1/0`; the fixture's credential comparison passed; and `wp db check` passed.

| Behavior / fixture | Fixture SHA-256 | Outcome |
|---|---|---|
| Login context and per-peer rate — `phase66a-login-context-rate.php` | `FDF19DEAADEADEEA045A3052AFA34BAA96F29EDDAEC6D652D66D0AB9EF908763` | **PASS (7), exit 0.** Twelve in-process anonymous options requests stayed within the five-pending bound; issue-time origin/RP context and drift denial checks passed. Cleanup PASS. |
| Expired-cleanup failure — `phase66a-login-expired-cleanup-failure.php` | `60F2FDD5AEC0816DDD0EBC7EC149ECA615587C35C799B25D7992D823BB84B584` | **BLOCKED before execution.** Preflight found an exact `ABSPATH` guard for `/home/tonic/zmp-local-sandbox/phase66a-test/site`; the authorized clone is `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`. No fixture call, row write, or guard bypass was attempted. |
| Aggregate quota — `phase66a-login-global-quota.php` | `604DDDED0A8EB52A2F6C978069F28EAA2ECA954CEAFD4F2B14D27FA0245F9B6F` | **PASS (5), exit 0.** Seeded 500 synthetic rows; the next options request returned generic HTTP 400 and challenge count stayed 500. Cleanup PASS. |
| Issuance churn — `phase66a-login-issuance-churn.php` | `E2C696BAC0EAE526A47D920D0B7B566D89F23D93C23C475432EE0DF2C39E03BF` | **PASS (9), exit 0.** Five options/challenges were accepted and consumed; later issuance was denied across the 12-cycle flow. Cleanup PASS. |
| Transient eviction — `phase66a-login-transient-eviction.php` | `663512CF0418C8B756258CB969AC8490F2C267901A6B2A21E648994FB14005CB` | **PASS (4), exit 0.** Five options/challenges were accepted and consumed; after deleting the exact synthetic peer transient, the sixth request was denied. Cleanup PASS. |

Exact command pattern for each executed fixture: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/<fixture-name>`.

## Integrity, cleanup, and command note

After each suite, users/credentials/challenges were `1/1/0`, marker remained `3`, schema compatibility remained true, protected UI credential label count remained `1`, and database integrity passed. Final plugin inventory remained only `zmp-competitions`; clone MariaDB PID 3828, Owner MariaDB PID 409, and Owner PHP PID 620 remained present. No clone PHP listener was left running.

For the transient-eviction run, an auxiliary read-only credential comparator used a doubled table prefix and printed a harmless missing-table read warning before and after the fixture. That comparator result was discarded. The fixture's own correctly scoped credential comparison and cleanup assertion passed; a corrected post-run baseline check confirmed users/credentials/challenges `1/1/0`, protected label count `1`, marker `3`, compatible schema, and DB integrity PASS. The fixture was not retried.

## Evidence limits

The expired-cleanup behavior was not revalidated on the current source because its exact path precondition does not match the authorized clone. Historical results remain bound to their recorded earlier source hashes. No UI/browser, production, external network, or HTTP route test was performed. These results do not establish whole-phase acceptance, package readiness, or GitHub synchronization.
