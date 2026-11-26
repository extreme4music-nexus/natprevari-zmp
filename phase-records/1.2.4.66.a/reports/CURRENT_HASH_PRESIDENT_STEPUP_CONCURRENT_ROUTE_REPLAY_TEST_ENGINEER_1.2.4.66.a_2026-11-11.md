# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer report — President subject step-up concurrent replay

**Phase:** `.66.a`  
**Execution time:** 2026-11-11 18:20 UTC  
**Overall result:** **FAIL** (one invocation; no retry)  
**Candidate:** current `.66.a` source, manifest SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`

## Exact reviewed artifacts

- Fixture: `tests/phase66a-president-step-up-concurrent-route-replay.php` — SHA-256 `36B7D2295145C4B255FC11731302052377484685B1E828ACFF48D7D91AD067AC`.
- Independent fixture safety/design review: `reports/CODE_REVIEW_PRESIDENT_STEPUP_CONCURRENT_ROUTE_REPLAY_FIXTURE_1.2.4.66.a.md` — SHA-256 `C01039A9254D80AE87D02490346097879C7A2588718093ADD3A8C1318668C26F`; verdict PASS for safety/design only, not runtime.
- Reviewed preparation note: `reports/PREPARE_PRESIDENT_SUBJECT_STEPUP_CONCURRENT_ROUTE_REPLAY_1.2.4.66.a_2026-11-11.md` — SHA-256 `376CE7008E4579F6F74ECC993BB61297BB864305AE73959BEA89400E7F826BEA`.
- Preparation-note review: `reports/CODE_REVIEW_PREPARE_PRESIDENT_CONCURRENT_ROUTE_REPLAY_1.2.4.66.a.md` — SHA-256 `60A9A68959AECD975493ECCDB7C6230B0222E33F624EA358D5CB62ED63454D38`.
- The clone's 67 first-party plugin files matched the current source manifest (67 checked, 0 mismatches). The fixture's guarded source hashes and exact fixture-copy hash were confirmed before execution.

## Scope and preflight

The only site/database used was `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, database `zmp66a_isolated`, prefix `z66a_`, using the clone's private MariaDB socket. Site URL was `http://127.0.0.1:8102`; schema marker was `3`; `ZMP_Passkey_Store::schema_is_compatible()` returned true. The sole installed and active plugin was `zmp-competitions/zmp-competitions.php`.

Before execution, `wp db check` passed. Synthetic baseline was one user, one credential and zero challenges; the protected synthetic account and its expected labeled credential were present. Proposal, proposal-event, mandate, mandate-audit, subject-catalog and alias counts were zero. A fresh clone-only compressed database backup was created with mode `0600`; compression, non-empty content, table-definition presence and DB integrity checks passed. Backup identifiers and contents remain private in the sandbox.

Clone MariaDB PID `3828` and Owner MariaDB PID `409` were present before and after. Clone PHP/port `8102` was absent. Owner PHP PID `620` and port `8101` were already absent at preflight and remained absent; I did not start, stop or otherwise alter them. No browser, HTTP request, external network or product-source change was involved.

## Invocation and result

The exact reviewed fixture bytes were copied to the clone-private `tests` directory and SHA-256 matched before the single run. Command:

```text
wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site --quiet eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay.php /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-step-up-concurrent-route-replay.php
```

- **SQL-boundary rendezvous:** PASS — two worker requests reached the exact guarded challenge-consume query boundary (`boundary_hits=2`).
- **Observed handler statuses:** one `rejected`, one `failed`, as expected by the fixture's status matrix.
- **Full route assertions:** **FAIL** — fixture exited `1` with `FAIL/BLOCKED`. Its safe output does not identify which post-handler assertion failed, so proposal/event, challenge-consumption, credential-counter and worker-exit assertions remain unresolved rather than individually claimed PASS.
- **Cleanup and fixture integrity:** PASS — fixture reported baseline restored, protected credential preserved, owned artifacts removed and database integrity passed.
- **Independent post-run verification:** PASS — private before/after baseline comparison matched; counts returned to `1/1/0` and protected credential remained present. A separate `wp db check` passed.
- **Processes/artifacts:** PASS — owned workers and fixture state directory were absent, port `8102` remained free, clone MariaDB PID `3828` and Owner MariaDB PID `409` remained present. The clone-private fixture copy and temporary baseline fingerprint file were removed; the private backup remains in the sandbox.

## Limits and disposition

This is one FAIL result and was not retried. It is not a full President-route acceptance PASS. The workers rendezvoused immediately before the database driver call; this demonstrates a shared boundary was reached, but does not establish a deterministic overlapping SQL execution schedule. The fixture invokes the actual President handler directly in WP-CLI workers; it does not exercise browser UI, HTTP/admin-post dispatch, or a real authenticator. The precise failed post-handler assertion is not distinguishable from the fixture's privacy-safe output and remains unresolved for a separately scoped diagnosis.