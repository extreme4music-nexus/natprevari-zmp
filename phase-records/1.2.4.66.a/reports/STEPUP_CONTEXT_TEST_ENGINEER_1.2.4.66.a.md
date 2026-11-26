# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Step-up Context Test Engineer — ZMP 1.2.4.66.a (pre-implementation)

**Date:** 2026-11-05  
**Status:** **FAIL (expected RED; missing behavior reproduced)**  
**Scope:** Test-only check that a step-up challenge for approve_alias is bound to the exact canonical subject ID as well as current user/session/action/proposal/decision, and stays one-use.

## Environment

- Site: /home/tonic/zmp-local-sandbox/phase66a-test/site, HTTP loopback 127.0.0.1:8101.
- Synthetic database: zmp66a_isolated, prefix z66a_; active plugin is .66.a.
- Existing services were inspected and reused: PHP PID 286942 on 127.0.0.1:8101; MariaDB PID 286676 on its existing private socket. No service was started or stopped.
- No site configuration was changed, no President decision route was called, and the protected UI credential was not touched.

## Fixture and source provenance

- New test-only fixture: tests/phase66a-step-up-context.php
- Fixture SHA-256: 15af8ca7dcc1d1dc702778ec35524cc5b9bf51a8752c48a36ed62e9f294d9aea
- Product source was unchanged. Current includes/class-zmp-passkey-store.php SHA-256: fa58ed5c1355fb729504e330271260462657cd4ed61ee36410737600db38dea5.
- The fixture supplies a context_hash over the action, proposal ID, decision and canonical subject ID. It expects another target's context to be rejected without consuming the original challenge, then the exact binding to consume once and replay to fail.

## Results

1. **PASS, exit 0** — php -l /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-step-up-context.php  
   Output: No syntax errors detected in /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-step-up-context.php.
2. **FAIL (1/2), exit 1 — expected RED** — exact command:
   wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-step-up-context.php
   
   Output:
   FAIL (1/2): step-up context binding
   - step-up challenge accepts a canonical action-context hash
   CHECKS 2; CLEANUP PASS
   
   Current ZMP_Passkey_Store::normalize_binding() rejects the additional context_hash field, so the context-bound challenge cannot be created. The mismatch/exact-consume/replay assertions were therefore **NOT RUN** in this pre-implementation round; no result is claimed for them.
3. **PASS, exit 0** — wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check → Success: Database checked. All reported tables were OK.
4. **PASS cleanup/baseline** — fixture reported user, credential and challenge counts unchanged from pre-run baseline. It created no credential/user, and deleted only challenge hashes created by this fixture (none were created because the missing binding field was rejected). No protected UI credential was altered.
5. **PASS environment state** — the existing PHP/MariaDB PIDs and service endpoints remained present after the run.

## Disposition

This is a recorded expected red test, not feature acceptance. No product code changed. Next implementation work may add a bounded context hash to challenge storage and atomic consume binding; this fixture must then be rerun to establish the mismatch-denial, exact-binding, one-use, cleanup and database checks on the new exact source hash.



## Current candidate round 2 — context binding and ceremony-shape regression (2026-11-05)

The original expected RED above is preserved for the pre-migration candidate. The backend candidate has now been copied only into the active `.66.a` synthetic plugin; it has not yet passed this current candidate's focused suite because two new negative requirements fail below.

### Provenance and environment

- Source `includes/class-zmp-passkey-store.php` SHA-256: `132657b2ba5d75773dcddfb222ce051be93f28b6717a39244035c6d78393ec79`.
- Active site copy SHA-256: `132657b2ba5d75773dcddfb222ce051be93f28b6717a39244035c6d78393ec79` (exact parity).
- Synthetic site `/home/tonic/zmp-local-sandbox/phase66a-test/site`; database `zmp66a_isolated`, prefix `z66a_`; active plugin list contains only `zmp-competitions`.
- Existing PHP PID `286942` remains on loopback port `8101`; MariaDB PID `286676` remains on the private socket. No process was started/stopped.
- WP-CLI plugin load ran the documented additive migration: `zmp_passkey_schema_version` is `2`; SHOW COLUMNS confirms challenge column `context_hash char(64) NOT NULL`.
- Before tests, credential count was 1, protected `Synthetic UI review only` row count was 1, challenge count was 0. No fixture changed the protected row.

### Test-only files and results

- Context fixture `tests/phase66a-step-up-context.php` SHA-256: `f45db464f92830a288c0660597d603fcecf0fd38c7021062e3048b0e6c5d16d9`.
- Existing storage fixture `tests/phase66a-passkey-store.php` SHA-256: `6238d32bc98d1197680b58b5fd8a787dc9bfae233d85be3fad20e44ebe0a1750`; its step_up binding shapes now include valid context hashes. Registration/login binding shapes in this existing suite are otherwise unchanged.
- **PASS, exit 0** — PHP lint of the context fixture; output reports no syntax errors.
- **FAIL (2/7), exit 1 — expected RED on exact current source/site hash** — command:
  `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-step-up-context.php`

  Exact markers:
  `FAIL (2/7): step-up context binding`
  `- registration rejects context_hash without DB insertion`
  `- anonymous login rejects context_hash without DB insertion`
  `CHECKS 7; CLEANUP PASS`

  The other four assertions passed on this candidate: valid step_up challenge creation, mismatch denial without consuming the original, exact-context one-use consumption, and replay denial. The cleanup/baseline assertion passed. The two required registration/login negative checks show this candidate currently allows context_hash on ceremonies where it is not permitted. The fixture deleted any challenge rows it created and preserved the one protected credential.
- **NOT RUN** pending the owner's minimal code fix: existing 65-assertion store suite, Task 3 18-assertion round-2 suite, registration 49-assertion suite, their full required lint/DB/inventory post-check sequence. Do not infer those results from prior hashes after the store source changed.
- `wp db check` for this post-migration candidate is **NOT RUN** yet; no database-integrity result is claimed for this round.

### Disposition

No product changes were made by this Test Engineer. The exact-candidate focused fixture is red due to the two negative ceremony-shape assertions. Await the minimal source correction before running the queued green/regression suites. Existing challenge-context assertions are preserved and passed in this red run, but they do not make the fixture or candidate PASS.

## Corrected source candidate round 3 — test and regression results (2026-11-05)

This round uses backend source candidate SHA-256 `88497a37c87c2a9961b4fc3980c535d30d536bf9df31f31cf27c9b04bee02d19`, synced only to the authorized `.66.a` synthetic site's active plugin copy. Source and site copy matched after sync and again after testing. This is scoped evidence only, not phase acceptance.

### Site and migration safety

- Verified site: `/home/tonic/zmp-local-sandbox/phase66a-test/site`, database `zmp66a_isolated`, prefix `z66a_`, site URL `http://127.0.0.1:8101`; active plugin inventory: only `zmp-competitions`.
- PHP PID `286942` continued to own `127.0.0.1:8101`; MariaDB PID `286676` continued on its existing private socket. Site returned HTTP 200 after testing. No service started/stopped.
- Schema remains version 2 with `context_hash char(64) NOT NULL` in the challenge table; migration had already been performed during the preceding candidate round, not repeated here.
- All five PHP lint checks completed with exit 0: current store source, context fixture, updated store fixture, Task 3 round-2 fixture and Task 3 registration fixture. Each reported `No syntax errors detected`.

### Focused and regression results

- **PASS (7), exit 0** — step-up context fixture command:
  `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-step-up-context.php`
  Output: `PASS (7): step-up action context binding and one-use consumption`; `CLEANUP PASS: user/credential/challenge baselines preserved`.
- **PASS (65), exit 0** — existing storage suite command:
  `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-store.php`
  Output: `PASS (65): credential and one-use challenge storage behavior`.
- **FAILED ATTEMPT, then superseded by a corrected test fixture:** the first storage-suite run on this candidate returned exit 1, `FAIL (2/59): schema fixture tables are empty before controlled alteration; passkey tables contain no test fixture rows after cleanup`. The cause was test setup assuming the credential table must be empty, although the authorized site's one protected `Synthetic UI review only` row is part of its valid baseline. The test-only fixture was corrected to snapshot and compare all pre-existing credential row IDs/hashes and the protected UI row. The subsequent 65/65 run above passed with the protected row unchanged. The earlier failed attempt is retained here; it is not hidden or counted as a product-source failure.
- **FAIL (2/18), exit 1 — unresolved on the current candidate** — Task 3 round-2 command:
  `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-review-round2.php`
  Exact failing assertions: `localhost HTTP removal is permitted for a loopback client`; `127.0.0.1 HTTP removal is permitted for a loopback client`. The fixture also observed its three expected denials: insecure non-loopback HTTP rejected with credential retained; loopback Host from a non-loopback peer rejected with credential retained; HTTPS origin differing from configured HTTP `WP_HOME` rejected with credential retained. No source/configuration change was made by the Test Engineer. Do not mark this suite PASS; the two permitted-loopback positives remain FAIL pending diagnosis.
- **PASS (49), exit 0** — registration/management suite command:
  `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-registration.php`
  Output: `PASS (49): Task 3 registration and passkey management behaviors`.
- **PASS, exit 0** — `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site db check --silent`; WP-CLI reported `Success: Database checked.`

### Post-run baseline and exact hashes

- Post-run database counts: users `1`; credentials `1`; protected `Synthetic UI review only` rows `1`; challenges `0`. The saved credential-row baseline comparison in the updated 65-check suite passed; no protected credential was removed or changed.
- Existing services remained active at the verified PIDs/port/socket. Active plugin inventory remained only `zmp-competitions`.
- Exact source/site parity: store source and active plugin copy both `88497a37c87c2a9961b4fc3980c535d30d536bf9df31f31cf27c9b04bee02d19`.
- Current test fixture hashes:
  - `tests/phase66a-step-up-context.php`: `f45db464f92830a288c0660597d603fcecf0fd38c7021062e3048b0e6c5d16d9`
  - `tests/phase66a-passkey-store.php`: `9b5854c58005d68a540eab3695206f7d4d1bc789e05d104d5d7626aebba46d6c`
  - `tests/phase66a-passkey-review-round2.php`: `51d32016b51347bda94e63b86464cb93751babda79db1ea1aed1c60299f64cf9`
  - `tests/phase66a-passkey-registration.php`: `7c041c1f6f5c269fb87cdf84d08ef9949d6ce92b52f0b6a65b77bd80fa9c5959`

No product code was edited by this Test Engineer. Preserve the earlier Task 2/Task 3 PASS records only for their previously tested source hashes. The current `.66.a` phase remains IN PROGRESS; the current Task 3 round-2 failure is open and this report does not claim phase acceptance, release or package readiness.

## Round 4 — bounded loopback-positive diagnostic (2026-11-05)

The prior current-candidate `FAIL (2/18)` for localhost and 127.0.0.1 loopback-positive removal assertions is preserved above. This one bounded diagnostic rerun did not reproduce it; no gate-specific cause is identified.

- Diagnostic-only test fixture hash: `1dc01d8a4ad82e6db136404154c71640afd37cf9e771e425cba7a8a9c19d4bfa`.
- **PASS, exit 0** — PHP lint for the updated round-2 fixture: `No syntax errors detected in /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-passkey-review-round2.php`.
- **PASS (18), exit 0** — ran the round-2 fixture exactly once after adding the bounded diagnostic. Output: `PASS (18): focused Task 3 profile and removal-origin behavior`.
- The fixture collected sanitized per-request fields in memory for the two allowed loopback cases: secure-context result; login/current-user match/read capability; POST/action match; nonce result; captured HTTP status and wp_die response; JSON success; credential retained/deleted. It emits the per-field diagnostic line only when an expected-allowed case fails. Because this run passed, those individual fields were **not emitted**; no specific first failing gate can be named from this run. The assertion outcome establishes that both loopback removals were accepted and their test credentials deleted, but it does not provide a separately recorded field-by-field diagnostic transcript. Do not infer an individual root cause.
- Post-run baseline query: users `1`; credentials `1`; protected `Synthetic UI review only` rows `1`; challenges `0`. These match the pre-run counts. The fixture's own cleanup assertions were included in PASS (18).
- Post-run active plugin inventory remained only `zmp-competitions`; PHP PID `286942` on `127.0.0.1:8101` and MariaDB PID `286676` on its private socket remained active; site returned HTTP 200. No site/database configuration or product source was changed during this diagnostic.
- The earlier `FAIL (2/18)` remains recorded as **transient / not reproduced on this single diagnostic rerun**. No second diagnostic run was performed. The current report does not claim an identified gate-level cause.

## Additive login ceremony contract correction

Backend clarified Task 4 login context semantics after the recorded 7-assertion PASS. The step-up-context fixture now keeps registration context rejection and all existing step-up action-target checks unchanged, but replaces the former anonymous-login context rejection with acceptance and exact consumption of the canonical origin/RP digest. The storage suite similarly supplies a valid login digest, tests mismatch rejection without consumption, and rejects missing/malformed login digests. Earlier PASS 7 and PASS 65 remain exact historical outcomes for their original fixture hashes and prior contract. Updated fixtures have not been run against the changing candidate; no result is carried forward as acceptance for the new login contract.

## Corrected candidate rerun — 2026-11-05

Against passkeys `9708137ce291d8d55a00b04e35b2f1b7cd4fdf69efa533fc41c4c8a1ab053925`, store `9aa2ace4b9932db8dd7d158bc22b003ab6d8f46d11b55add101798e54431f95d`, and entrypoint `15a87c3ecb1ffdfcace9198d2f206060117396fa9c58ffd80f2a9a00d9cc9b93`, revised storage fixture SHA `7077084d58e2b226c12f70f5e6223ff53e9cc78dc12a691aa9410c931b8fdf91` returned **PASS (68), exit 0**; revised step-up fixture SHA `373c3216f71a34751d4ae0a0a408908740b433e3b1302d7cb321b13eef4a1f68` returned **PASS (7), exit 0** with cleanup. Login now expects the canonical origin/RP digest; registration remains context-empty and prior step-up action-context checks are unchanged. Both fixtures linted. Historical PASS 65/PASS 7 results remain attached to their prior exact fixture hashes and contract.
