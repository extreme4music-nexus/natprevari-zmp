# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer report — diagnostic President step-up replay (`.66.a`)

**Run time:** 2026-11-11 18:28 UTC  
**Status:** **FAIL** — one invocation; no retry  
**Scope:** diagnostic-only fixture against the isolated synthetic clone

## Exact evidence binding

- Current source manifest SHA-256: `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`; fresh comparison checked 67 first-party source files with 0 mismatches.
- Diagnostic fixture `tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php`: SHA-256 `49998D8C6FF39437920CA9541870C9A175373CBAD2A10C8FBF5927C71706A466`.
- Diagnostic fixture exact-hash review `reports/CODE_REVIEW_DIAGNOSTIC_CONCURRENT_STEPUP_REPLAY_FIXTURE_1.2.4.66.a.md`: SHA-256 `C2BD91B00DA037A58B48510EEF0CA9547D85B30224707DD0FC718A5484065AF5`; review verdict PASS for fixture safety/design.
- Preparation note `reports/PREPARE_DIAGNOSTIC_PRESIDENT_STEPUP_REPLAY_1.2.4.66.a_2026-11-11.md`: SHA-256 `4918A12CF92B743CD540D886758BDB243BB5A2EA194AB0859C74881CB9A1ED6E`.
- Original failing fixture and its earlier FAIL report were preserved; neither was edited or rerun.

## Fresh preflight and recovery

The only target was `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, URL `http://127.0.0.1:8102`, database `zmp66a_isolated`, prefix `z66a_`, and private socket `localhost:/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/run/mariadb.sock`. The installed and active plugin inventory contained only `zmp-competitions/zmp-competitions.php`; schema marker was `3` and schema compatibility passed.

`wp db check` passed before the run. The synthetic baseline was 1 user / 1 credential / 0 challenges; the protected synthetic credential was present. Proposal, event, mandate, mandate-audit, subject-catalog and alias counts were zero. A fresh clone-only compressed database backup was created with mode `0600`; compression, non-empty content, table-definition presence and DB integrity were verified. Backup identifiers, hashes, size and contents remain private in the sandbox.

Clone MariaDB PID `3828` and Owner MariaDB PID `409` were present before and after. The clone's PHP listener on `8102` and Owner PHP PID `620`/port `8101` were absent before the run; no service was started or stopped. The private socket remained present. No browser, HTTP request, external network, production, or product-code edit was involved.

## Single invocation and result

The exact reviewed fixture bytes were copied to the clone-private test path, and the copied SHA-256 matched before invocation. It was run once with:

```text
wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site --quiet eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php
```

The process exited `1` and emitted only `DIAGNOSTIC FAIL: worker_exit`. The other allowlisted assertion IDs were not emitted as failures. The `worker_exit` category combines worker startup/result/status-matrix and process-exit checks, so this run does not distinguish among those subconditions. No raw values or worker results were emitted. This preserves the runtime outcome as **FAIL**; it does not establish a new product defect or a full route PASS.

## Cleanup and final state

The fixture copy and temporary baseline fingerprint file were removed. A private before/after snapshot comparison matched, including the protected credential; core counts returned to 1/1/0. A post-run `wp db check` passed. No worker process or fixture state directory remained; ports `8101` and `8102` had no listener. Clone MariaDB PID `3828`, Owner MariaDB PID `409`, private socket and private recovery backup remained. Owner PHP PID `620`/port `8101` remained absent and untouched.

## Limits

This is a single diagnostic run with only the `worker_exit` identifier failing. It does not reveal whether the issue was worker startup, the result/status matrix, or process-exit bookkeeping. It uses direct WP-CLI handler invocation rather than browser UI or HTTP/admin-post dispatch. The rendezvous before the DB driver does not establish a deterministic overlapping SQL schedule. No retry was made, and the original FAIL remains open.