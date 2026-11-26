# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Preparation note — President step-up concurrent route replay (`.66.a`)

**Status: PREPARED; runtime NOT RUN.** Independent exact-hash review passed for fixture safety/design only. No test execution, clone database access, service operation, or Owner-site access occurred while preparing this note.

## Exact reviewed artifacts and candidate binding

- Test-only fixture: `tests/phase66a-president-step-up-concurrent-route-replay.php`, SHA-256 `36B7D2295145C4B255FC11731302052377484685B1E828ACFF48D7D91AD067AC`.
- Independent static review: `reports/CODE_REVIEW_PRESIDENT_STEPUP_CONCURRENT_ROUTE_REPLAY_FIXTURE_1.2.4.66.a.md`, SHA-256 `C01039A9254D80AE87D02490346097879C7A2588718093ADD3A8C1318668C26F`. Verdict: PASS for fixture safety/design; runtime NOT RUN.
- Current source manifest: `reports/CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES.txt`, SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`.
- Exact guarded source hashes: entrypoint `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`; passkeys `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; passkey store `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; President approvals `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`; subject proposal service `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`.

## Required fresh preflight before any run

The fixture refuses execution unless it is invoked from the exact clone-private test path and all embedded clone/source guards pass. Before copying or invoking it, independently verify the live environment again:

- Site: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`; Home and Site URL: `http://127.0.0.1:8102`.
- Database `zmp66a_isolated`, private host/socket `localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`, prefix `z66a_`; schema marker `3` and structural compatibility PASS.
- Only installed and active plugin: `zmp-competitions/zmp-competitions.php`. Recheck the five source hashes above and full 67-file phase-to-clone parity.
- Synthetic baseline: exactly one user, one passkey credential, zero passkey challenges; protected synthetic `zmp66a_admin` credential remains present. Capture and later compare the proposal/event/mandate/catalog/alias counts, role-option state, debug-option state and credential fingerprints privately. Expected core count baseline is `1/1/0`.
- Run `wp db check` before and after. Confirm clone MariaDB PID 3828 is alive on its private `--skip-networking` socket, no clone PHP listener uses port 8102, and Owner PHP PID 620 / MariaDB PID 409 remain untouched. These are future requirements, not a live status claim from this preparation.
- Immediately before any data mutation, take a fresh whole-clone DB backup under the clone sandbox with mode `0600`; verify the compressed backup, readable table definitions and DB integrity. Keep backup details only in the sandbox. No backup was created for this preparation note.

## Invocation and expected behavior

After the preflight and backup pass, copy the exact reviewed fixture bytes to:

`/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay.php`

Verify the copied SHA-256 still equals the fixture hash above. Invoke once, from WP-CLI, with the fixture path also supplied as its self-path guard argument:

`wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay.php /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay.php`

The fixture creates a synthetic President with a temporary mandate, a synthetic teacher proposal, a temporary P-256 passkey, one step-up challenge and one signed assertion. Two WP-CLI workers receive the same synthetic proof. A test-only query filter matches only the exact prepared challenge-consume UPDATE; both workers signal readiness immediately before the database driver call. The parent releases them only after both are waiting. The handler is invoked directly in each worker, with no browser, HTTP request, local server or production call.

Expected allowlisted result: two boundary hits; one redirect status `rejected` and one `failed`; one proposal rejection and one corresponding event; the challenge is consumed; the synthetic credential counter advances once. No assertion, cookie, nonce, challenge, key, SQL text or user payload is emitted.

## Timeouts, recovery and cleanup

- Worker rendezvous deadline: 20 seconds; parent readiness deadline: 20 seconds; result deadline: 25 seconds; final `wp db check` deadline: 25 seconds.
- If either worker misses the exact-query rendezvous, the parent releases an `abort` marker, terminates only its own worker handles, preserves the outcome as FAIL/BLOCKED, and performs no retry.
- The fixture cleanup is limited to its created challenge, proposal/events, credential, mandate/audit rows, synthetic users and session token. It restores request globals and owned debug-option changes, removes only allowlisted files in its unique mode-`0700` child directory, and checks full baseline/fingerprint equality plus DB integrity.
- Keep the fresh recovery backup private and retained until post-run baseline and integrity verification complete. Remove only the exact clone-private fixture copy after the run; verify no worker or child-state artifact remains. Do not touch the original site/database or stop Owner services.

## Evidence limits

This is an actual President decision-handler replay with the real assertion verification and proposal side effect. It does not exercise browser UI, the HTTP/admin-post transport dispatcher, or a real authenticator. The two workers are synchronized immediately before the exact database update, but the fixture cannot dictate database scheduling or prove overlapping commits. Even if the expected result occurs, record route behavior as scoped PASS and race evidence as **PARTIAL**, not as proof of a deterministic SQL lock schedule or phase acceptance.