# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Preparation note — diagnostic President step-up replay (`.66.a`)

**Status: PREPARED; diagnostic runtime NOT RUN.** This is a new diagnostic-only fixture for the existing concurrent-route FAIL. It does not replace or modify the original fixture and has not been copied into the clone. No database, service, backup, browser, or production access was used to prepare it.

## Evidence binding

- Debugger report: `reports/DEBUGGER_CONCURRENT_STEPUP_REPLAY_FAILURE_1.2.4.66.a_2026-11-11.md`, SHA-256 `F8B3CA31636A6C18FAB7BCCDB56F8C5927F237BEF28DD0A1385483B121771CCF`.
- Existing runtime Test Engineer report: `reports/CURRENT_HASH_PRESIDENT_STEPUP_CONCURRENT_ROUTE_REPLAY_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `950C8430E9D8405A40EC29CD61F1E1B40B291015077560462C2D243CBC28B7C0`; one run returned FAIL, with two boundary hits and one `rejected`/one `failed`; cleanup and baseline/integrity passed.
- Original fixture remains unchanged: `tests/phase66a-president-step-up-concurrent-route-replay.php`, SHA-256 `36B7D2295145C4B255FC11731302052377484685B1E828ACFF48D7D91AD067AC`.
- New diagnostic fixture: `tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php`, SHA-256 `49998D8C6FF39437920CA9541870C9A175373CBAD2A10C8FBF5927C71706A466`.
- Current source manifest: `reports/CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES.txt`, SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`.
- Guarded source files remain bound to that manifest: plugin entrypoint `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`; passkeys `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; passkey store `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; President approvals `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`; proposal service `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`.

## Diagnostic behavior

The new fixture retains the original exact clone/database/socket/URL/prefix, one-plugin, schema-compatibility, source-hash, synthetic-baseline, protected-credential and invocation-path guards. It drives the same two WP-CLI workers through the same actual President decision handler and exact challenge-consume query rendezvous. It adds no product-code changes and does not weaken the original safeguards.

After owned-row cleanup, it emits only a fixed assertion-ID list: `proposal_event`, `challenge_consumed`, `credential_counter`, `worker_exit`, `cleanup`, and `integrity`. On failure it emits only the failing IDs; on success it emits the fixed IDs as passed. The IDs are allowlisted in code. It never emits assertion/proof/cookie/nonce/challenge/session values, SQL, row values, event payloads, worker JSON, or private fingerprints. Diagnostic booleans remain in process memory until cleanup; no diagnostic sidecar is created. The existing private request envelope/state directory remains mode-restricted and is removed by the fixture cleanup.

`worker_exit` covers worker startup/result/status-matrix and process-exit checks. `proposal_event` covers the one expected rejection decision and its corresponding event. The remaining IDs identify the corresponding challenge, credential counter, cleanup/baseline, and database integrity assertions. This distinguishes the unresolved categories from the prior generic failure output without increasing report data exposure.

## Exact future clone scope and one-run plan

This note authorizes no execution. If separately approved after independent exact-hash review, a future single run is limited to:

- Clone site `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, URL `http://127.0.0.1:8102`.
- Clone database `zmp66a_isolated`, prefix `z66a_`, `DB_HOST=localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`.
- Sole installed and active plugin `zmp-competitions/zmp-competitions.php`; schema marker `3` and compatible current source matching the manifest above.
- Fresh preflight immediately before that run: exact site/database/socket/prefix/URL/plugin/source parity, synthetic-only one-user/one-credential/zero-challenge baseline and protected synthetic credential, related proposal/event/mandate/catalog counts and private fingerprints, `wp db check`, clone MariaDB PID `3828` with private socket, no clone PHP listener on `8102`, and Owner service observations recorded without changing them.
- A fresh whole-clone database backup must be created inside the clone sandbox with mode `0600`, then verified for non-empty content, compressed-stream integrity, readable table definitions and database integrity. Backup name/path/hash/size and contents remain private and are not written into reports.

After those gates and independent review PASS, copy the exact reviewed bytes only to `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php`, verify its SHA-256, and invoke exactly once:

```text
wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site --quiet eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php
```

No retry is planned. After any authorized run, verify allowed assertion IDs, exact baseline/protected-credential equality, cleanup, `wp db check`, child/worker artifacts, owned process/port state and backup retention. Remove only the exact clone-private fixture copy and temporary private diagnostic state. Do not restore blindly; stop and report if cleanup/baseline differs. Do not touch Owner site/database/processes, production, external network, other phases or the original fixture.

## Limitations

This is preparation evidence only. No independent review of this new fixture has been received yet, and no runtime result is claimed. It uses direct WP-CLI handler invocation, not browser UI or HTTP/admin-post dispatch. The query-filter rendezvous does not establish a deterministic SQL scheduling/locking outcome. The original run remains FAIL until an independently reviewed diagnostic run yields new evidence; this note does not convert or overwrite that result.