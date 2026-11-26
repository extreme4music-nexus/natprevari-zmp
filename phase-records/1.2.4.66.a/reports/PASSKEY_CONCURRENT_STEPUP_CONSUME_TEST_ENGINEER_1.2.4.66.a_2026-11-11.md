# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — concurrent step-up challenge consumption — `.66.a`

**Run:** 2026-11-11, one execution only  
**Scoped result:** **PASS for the observed two-call storage outcome; race evidence remains PARTIAL.** This does not establish full President-route acceptance or a deterministic SQL-boundary race.

## Exact evidence identity

- Candidate manifest: `CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES.txt`, SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`; fresh source-to-clone check matched all 67/67 first-party files.
- Relevant clone source hashes: entrypoint `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`; passkeys `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; store `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`.
- Fixture `tests/phase66a-passkey-concurrent-consume.php`, SHA-256 `D1FD6152F88DBF754DCC85FD4A406AD2A43FB08DE322309DD29AA956F153CEE1`; clone-private copy hash matched.
- Independent review `CODE_REVIEW_CONCURRENT_STEPUP_CONSUME_FIXTURE_1.2.4.66.a.md`, SHA-256 `971E703052073BEED2C5D3A47F1DFB70BA1F58026D1B6B47A799B7AE899D1504`: fixture safety PASS, race evidence PARTIAL.

## Preflight and isolation

- The first read-only invocation-path check found the reviewed copy absent at its required hard-guarded clone path; no database mutation had occurred. After parent confirmed that the exact reviewed bytes could be placed at that clone-private path, the copy hash was verified and the full preflight was repeated successfully before the single run.
- Exact site `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`; DB `zmp66a_isolated`; prefix `z66a_`; DB host resolved to the clone-private MariaDB socket; Home and Site URL both `http://127.0.0.1:8102`.
- Only active plugin was `zmp-competitions`; marker `3`; schema compatibility PASS; users/credentials/challenges/protected-credential-label counts `1/1/0/1`; `wp db check` PASS.
- Fresh source parity matched 67/67 files. Clone MariaDB PID 3828 remained on its private socket with networking disabled. Owner PHP PID 620 and Owner MariaDB PID 409 remained present and untouched. Port 8101 remained the Owner PHP listener; clone port 8102 was free. No PHP server or HTTP/network call was used.
- A fresh whole-clone recovery backup was verified before data mutation and retained privately with mode `0600`. Its identifier and contents are intentionally not included here.

## One execution

Exact command, using the clone-local byte-matched copy and its required self-path argument:

`wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site/tests/phase66a-passkey-concurrent-consume.php /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site/tests/phase66a-passkey-concurrent-consume.php`

- **Exit 0.** Allowlisted output: `workers=2; successes=1; rejections=1`.
- Fixture cleanup reported baseline, protected credential, artifact removal, and database integrity as true.
- The test created one synthetic `step_up`-shaped challenge through `ZMP_Passkey_Store::create_challenge()` and launched two clone-local WP-CLI workers. Both passed the same challenge/binding preflight and waited at the reviewed clone-local file gate. After release, both invoked the real `ZMP_Passkey_Store::consume_challenge()` once; one returned a binding and one returned null.
- This is the only execution. No retry was made.

## Post-run verification

- Users/credentials/challenges/protected-label counts returned to `1/1/0/1`; schema marker `3`, compatibility, and `wp db check` all passed. The fixture’s in-memory credential comparison matched.
- Both owned worker handles exited; no worker process remained. No barrier/result directory remained. The temporary copy under the fixture’s hard-guarded site path was removed; the byte-matched sandbox test copy remains available for evidence.
- Clone port 8102 remained free. MariaDB PID 3828 and Owner PIDs 409/620 remained present. The private recovery backup remained mode `0600` and verified.

## Caveats and limits

- The baseline account is the synthetic Administrator account, not an active President session. The fixture used a synthetic session hash and a valid synthetic nonce to construct a structurally consistent President subject-decision binding. It did **not** call `step_up_options()` or `verify_step_up()`, verify a WebAuthn assertion, or test authorization/session issuance.
- The gate releases workers immediately before the PHP method call. It does not force both workers to reach or overlap the atomic SQL `UPDATE`; a pass demonstrates synchronized concurrent method calls, not deterministic row-lock-boundary scheduling.
- The losing `null` result is not independently classified as “already consumed” versus SQL/schema failure. Matching preflight, the single consumed row, final baseline, and DB integrity support the observed outcome but do not distinguish that internal cause.
- The cleanup `wp db check` subprocess has no explicit timeout. It completed successfully in this run.

Accordingly, record this as a scoped runtime PASS with **PARTIAL** race evidence. Full President-route and deterministic SQL-boundary race checks remain **NOT RUN**.

