# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Task 5 President subject step-up matrix (pre-run)

## Scope and current status

This is a test-only, synthetic-data fixture for the `.66.a` Task 5 acceptance behavior. It is authored for a later bounded run against a stable candidate after Lead review. **Runtime status: NOT RUN.** No site, database, browser, or service was accessed for this matrix preparation. Product files and the original one-run Task 5 RED evidence were not modified.

The fixture exercises valid alias approval and rejection through the actual loopback `admin-post` route, plus narrowly scoped negative checks for alias-target binding, malformed assertion dimensions, origin/RP/UV, expiry, replay, changed resource/decision/user/session, lost role/mandate, bad nonce, and pre-existing nonpending proposal no-write behavior. A true concurrent interleaving is explicitly **NOT RUN**: the approved test design has no deterministic synchronization seam, and this fixture will not introduce one or issue concurrent DB writes.

## Contract and trace ruling

The fixture follows the Lead's ruling that canonical alias target is a required binding dimension, as required by the DESIGN route trace. The expected public call signatures and parameter order are:

* `step_up_options($uid, $action, $resourceId, $decision, $canonicalSubjectId)`
* `verify_step_up($uid, $challengeId, $action, $resourceId, $decision, $canonicalSubjectId, $payloadJson)`

For `approve_alias`, canonical subject ID must be a canonical positive decimal. For `approve_new` and `reject`, it must be exactly zero. The domain-separated challenge context binds this target. This intentionally extends the older plan text, whose signatures omitted `canonicalSubjectId`; the route trace is the governing requirement for the matrix.

The fixture currently assumes the future route's POST proof fields are `step_up_challenge_id` and `step_up_credential`. The currently inspected `class-zmp-president-subject-approvals.php::handle_post()` does not yet read either field or invoke verification. **Do not run this fixture until the Backend candidate confirms the route contract and contains the reviewed guard.** If the candidate uses different names, revise this fixture before a run and preserve this report's preparation history.

## Bounded setup and cleanup plan

Strict preflight requires the already authorized site `/home/tonic/zmp-local-sandbox/phase66a-test/site`, database `zmp66a_isolated`, prefix `z66a_`, the sole active plugin `zmp-competitions/zmp-competitions.php`, compatible passkey/proposal schemas, and exact expected empty synthetic baselines (one protected user and one protected credential; no challenges, proposals, events, mandates, audit rows, subjects, or aliases). A changed baseline exits before fixture writes.

The fixture will create only two uniquely suffixed WordPress users (teacher and President), appoint the President via `ZMP_President_Mandate::appoint`, create six uniquely named pending proposals via `ZMP_Mentor_Subject_Proposals::submit`, and create two uniquely named canonical subjects via `ZMP_Mentor_Subjects::create_approved_subject_for_president` while authenticated as that President. It generates one ephemeral P-256 key and one synthetic public credential for the President entirely in process memory; private key/assertions/cookies/nonces are never printed or persisted outside the test site's normal credential/challenge rows. It records only returned fixture IDs/keys and hashed challenge identifiers for cleanup.

Cleanup is limited to challenge hashes issued by this fixture; proposal events and proposal rows matched by its unique IDs/keys; aliases matched by its unique proposal keys and synthetic President; catalog subjects matched by returned IDs; the exact synthetic credential ID hash and President ID; the fixture mandate/audit ID; the two synthetic user IDs and their session tokens; and debug events bearing the fixture's IDs. It restores current user and request/cookie/server globals and checks exact table counts, complete credential-table fingerprint, protected UI credential digest, global role option, and debug-event value against preflight. If the preflight or cleanup invariant cannot be established, no runtime run is authorized by this report.

## Check record

| Check | Status | Evidence |
|---|---|---|
| Fixture PHP syntax | Pending | To be recorded after the bounded lint command. |
| Task 5 behavior matrix runtime | NOT RUN | Awaiting stable reviewed backend route contract and Lead authorization. |
| True concurrent race interleaving | NOT RUN | No approved deterministic synchronization seam; no concurrent writes or timing sleeps will be used. |
| Site/database mutation | NOT RUN | None during this preparation. |
| Product source edits | NOT RUN | None; fixture/report only. |

The original bounded Task5 RED remains preserved in `PRESIDENT_SUBJECT_STEPUP_TEST_ENGINEER_1.2.4.66.a.md` and `tests/phase66a-president-subject-step-up.php`; this matrix does not rewrite or supersede that evidence.

## Contract reconciliation addendum — 2026-11-06

This addendum supersedes the earlier pre-run API/coverage assumptions above; the original preparation record remains preserved. Fixture now matches the backend API contract:

- `step_up_options($uid, $action, $resource_id, $decision, $canonical_subject_id, $route_nonce)`
- `verify_step_up($uid, $challenge_id, $action, $resource_id, $decision, $canonical_subject_id, $payload_json, $route_nonce)`
- Step-up options are created against the exact valid route nonce. Route POST still submits `_wpnonce`, `step_up_challenge_id`, and `step_up_credential`; UI AJAX supplies `route_nonce`.

The exact preflight now requires zero subjects and zero aliases as well as the prior empty synthetic proposal/mandate/challenge state. The valid matrix now drives an `approve_new` proposal through the actual loopback route in addition to valid alias approval and rejection. It checks approved proposal, exact actor, newly created canonical subject owned by that President, and exactly one `approved_new` event. The returned canonical subject ID is added to the fixture-owned cleanup set. Lost-President-role and suspended-mandate checks now invoke the actual route with a current challenge and assert HTTP 403 plus unchanged proposal/event snapshots. Sequential successful replay remains covered. True concurrent replay/interleaving remains explicitly NOT RUN; no synchronization seam or timing sleep was added.

Cleanup remains scoped to fixture-owned user/session tokens, known challenge hashes, proposal IDs plus unique proposal keys/events, aliases by unique proposal keys and fixture President, returned canonical subject IDs, exact credential hash/user ID, exact mandate/audit ID, and fixture-attributed debug events. Final assertions compare all pre/post table counts, full credential fingerprint, protected credential digest, role option hash, debug-option value, and current user.

**Fixture PHP lint: PASS** (`php -l`, no syntax errors). **Behavior matrix runtime: NOT RUN. Site/database/browser access and mutation: NOT RUN / NONE.** This was a fixture/report-only change; it does not claim product acceptance. Remaining explicit limit: concurrent replay is not exercised. Before runtime, bind the matrix report to the reviewed/frozen product hashes and perform the project’s required synthetic-site preflight.

## Bounded runtime attempt — reviewed Task5 candidate — 2026-11-06

Exact run: `wp --path=/home/tonic/zmp-local-sandbox/phase66a-test/site eval-file /mnt/z/Documents/Natprevari_ZMP/ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.a/tests/phase66a-president-subject-step-up-matrix.php`.

**Task5 matrix runtime: FAIL (2/2), exit 1.** The fixture stopped during the first valid `approve_new` flow because `step_up_options()` did not return options (`step-up options issued for approve_new` failed), before calling the decision route. This is not an expected RED; the candidate had independent static Code Review PASS. The fixture was not rerun. The failed path did not print private key/assertion/cookie/nonce material. The matrix output reported **CLEANUP PASS** and concurrent interleaving **NOT RUN**.

Candidate/source-site hashes were exact after syncing only the four reviewed files: passkeys `F01D94425E06A7978F1FC1D721BFFE0B3404FB71069E7246BB91F10E0AE0B6E7`; approvals `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`; JS `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`; CSS `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`. Matrix fixture SHA `452005C5CF306D0FF2A927169562C82E4DCE49F021371E2DCB058B2DB27528B5` and this report SHA must be recomputed after this addendum.

Post-run cleanup/integrity: **PASS**. `wp db check --silent`: Success. Exact pre/post counts remained users=1, credentials=1, challenges=0, proposals=0, proposal_events=0, mandates=0, mandate_audit=0, subjects=0, aliases=0. Full credential-table fingerprint remained `511b7006cfa53e2bced9ef69b96331783af1472295afe6638d347235575afe6e`; protected credential SQL digest `d610dfaf3460da32e95d2207add33bb903cafbc3dd40786e220c48b8a6f1affd`; role option SHA `514d4be7f0cc4a96f3e57bcd828c03c609212621a806fe1052e7e11fd6abe788`; debug events SHA `48ab8d7c9c9e6899aa6c5b152a28441f55bd8086a5f29bce234ca5f704c34c68`; debug-capture option remained absent. Only `zmp-competitions` remains installed/active.

The existing services were preserved and verified: PHP PID 286942 listening on 127.0.0.1:8101; MariaDB PID 286676 active. Exact four-file source/site parity remains PASS. No database rows/options or plugin inventory difference remained after fixture cleanup. Login options and valid Task4 assertion/session/counter rechecks were **NOT RUN** because the matrix failed and the assigned instruction requires stop/no rerun. Concurrent replay is **NOT RUN**. No browser interaction, phase acceptance, package or GitHub action.

Next: Debugger/Backend should diagnose why options issuance is denied for the valid synthetic President/nonce/session/context, using the exact fixture and reviewed source, without rerunning the matrix until a specific correction and fresh bounded run are approved. Preserve this failure and cleanup PASS separately.

## Matrix fixture late-bound closure capture correction — 2026-11-06
A static audit found that the `$issue` and `$verify` closures were declared while `$president_id` was still initialized to `0`, before the synthetic President was created. Both captured `$president_id` by value, so their later calls could use the stale zero. The matrix fixture now captures `$president_id` by reference in both closures. This is test-fixture-only; no product file or prior runtime artifact was changed.

Updated matrix fixture SHA-256 `AA60EFCA3532337426EE1233F880A3AF41DA72B1B43540EDB44A2C18A1F79C55`; PHP lint PASS. Historical matrix FAILs remain recorded exactly as observed; this static fixture correction does not retrospectively change them. No runtime execution occurred. The separate positive-route fixture has the same by-value `$president_id` capture in both its `$issue` and `$verify` closures (its current SHA remains `92F50726983CCFA304314573FE51149DBEFB8EC0405894FE9C45D38DECC76499`); it was inspected but not modified under the current instruction to update only the matrix fixture/report. Awaiting independent Debugger confirmation before any runtime work.


## Corrected-capture matrix runtime — 2026-11-06
Independent Debugger confirmed the prior fixture defect: the `$issue` and `$verify` closures captured the not-yet-created President ID (`0`) by value. Those captures were corrected to references, and the corrected fixture was run once on the exact reviewed candidate (`class-zmp-passkeys.php` source/site SHA-256 `F233CB371DCDEF3A05F447C3E5081F4B7F4DE453FE2BFBE2470B447847E68ED7`).

Fixture `tests/phase66a-president-subject-step-up-matrix.php` SHA-256 `AA60EFCA3532337426EE1233F880A3AF41DA72B1B43540EDB44A2C18A1F79C55`; PHP lint PASS. Runtime output: **FAIL (6/28)**, cleanup PASS, concurrent race interleaving NOT RUN. Failed checks were:
- valid assertion authorizes exact pending new-subject approval;
- valid assertion authorizes only the exact pending alias approval;
- valid assertion authorizes the exact pending rejection;
- changing canonical alias target after step-up options is denied without proposal/event write;
- step-up options issued for reject;
- fixture stopped during changed user/session and lost role/mandate.

The fixture continued after failed assertions and reached the later RuntimeException; this was one invocation and no retry occurred. Actual decision route outcomes remain failed by the recorded assertions. Post-run exact cleanup PASS; `wp db check --silent` PASS; baseline counts users/credentials/challenges/proposals/events/mandates/audit/subjects/aliases restored `1/1/0/0/0/0/0/0/0`; sole plugin, four-file parity, PHP286942 and MariaDB286676 preserved.

Prior matrix runtime FAILs used the invalid by-value fixture setup and are preserved as historical outcomes but are not valid evidence against product behavior. The corrected-capture runtime is a separate current-hash FAIL. The positive-route fixture has the same identified capture defect and its prior failure is likewise invalid as product evidence; that fixture was not run here and remains uncorrected. No cause beyond the verified fixture defect is inferred. Concurrent replay NOT RUN; no phase acceptance.

## Route outcome and session nonce fixture refinements — static-only review — 2026-11-06
Per the scoped follow-up, the matrix fixture received only test-side refinements; product code and runtime were not touched.

- Old matrix fixture SHA-256 `AA60EFCA3532337426EE1233F880A3AF41DA72B1B43540EDB44A2C18A1F79C55`; revised fixture SHA-256 `1F55516E11F3FA1A905B1A494FF0E2AF8167417651E9A64EA7A6D41346A929A0`; PHP lint PASS.
- Canonical alias-target mismatch now expects the route's `302` redirect with `zmp_subject_decision_status=failed` and an unchanged proposal/event snapshot, matching `handle_post()`'s redirect-and-exit behavior.
- The three positive routes now retain only sanitized route evidence: integer HTTP status and an allowlisted decision outcome (`approved`, `rejected`, `failed`) or fixed transport/unknown categories. This distinguishes route status from actor/event/catalog assertion failures. No redirect URL, assertion, challenge, credential, or private data is emitted.
- The changed-session case now creates a fresh President session, mints and verifies a nonce valid for that session, issues the challenge in that session, then restores the original session and its nonce before verification. Both session nonces are valid in their own session. This is a combined changed-session plus route-nonce challenge-binding denial; it does not isolate the session hash. The original valid nonce remains available for subsequent role/mandate denial requests.
- Static limitations: no runtime test was run; prior test outcomes remain as recorded. The positive-route fixture is still unchanged and retains the previously identified by-value President ID closure capture. Concurrent replay remains NOT RUN.

## Same-origin route and state evidence update — static only — 2026-11-06
Further bounded refinement to the matrix fixture: the synthetic loopback admin-post request now includes `Origin` set to the configured loopback site origin. The sanitized per-route record now includes HTTP status, an allowlisted Location decision status, an allowlisted proposal status, event count, actor-match boolean, and applicable canonical-target/unchanged-snapshot booleans. It does not emit the redirect URL or any assertion/challenge/credential/private material. The changed-session case uses a fresh nonce created under the new synthetic session before options issuance, then restores the original session and nonce for combined changed-session/route-nonce binding verification.

Updated fixture SHA-256 `2A4645C17DF2CC416CEC1A73A494947CD92E2B56A0F27F0C5C7C9FC1EA5D7955`; PHP lint PASS. No runtime was run. Prior matrix FAIL outcomes remain unchanged; these corrections are not runtime evidence. Concurrent replay remains NOT RUN.


## Canonical alias/catalog no-write scope and Origin preflight — static only — 2026-11-06
The canonical-target mismatch case now hashes and compares (a) all alias rows scoped to the fixture-owned alias key and (b) catalog rows for the two fixture canonical target IDs, in addition to the proposal/event snapshot. Only a boolean indicating the combined scoped snapshot is unchanged is emitted; IDs, names, rows and hashes are not printed.

Before fixture writes, the matrix now verifies that configured Origin and `admin-post.php` have matching scheme, host and effective port and that the route is the expected loopback endpoint. The route helper repeats these checks before sending and includes that same Origin header. A mismatch blocks before any fixture writes/network POST.

Updated matrix fixture SHA-256 `AF0652DAA0FC09F17962BF21350AE523461CDBE3253049C7F08571C7F647EC88`; PHP lint PASS. No runtime was run. Changed-session negative checks have valid nonces in both sessions, but intentionally assert only the combined session-plus-route-nonce challenge-binding denial, not an isolated session-hash property. Historical FAIL evidence remains unchanged.

## Corrected-capture matrix Round2 runtime — 2026-11-06
Independent Round2 review PASS: fixture SHA-256 `AF0652DAA0FC09F17962BF0D2F8063447A0187FD45CAF8043920E263D61822`; review report `CODE_REVIEW_TASK5_STEPUP_MATRIX_ROUND2_1.2.4.66.a.md` SHA-256 `C37BB829CEA1A1212CFD73B4FC1197AAD305A877880480F589EE7584D6719704`. Exact reviewed passkeys source/site hash was `F233CB371DCDEF3A05F447C3E5081F4B7F4DE453FE2BFBE2470B447847E68ED7`.

Fresh preflight matched the authorized synthetic site, database/prefix, baseline counts, ZMP-only plugin inventory, schema markers and persistent PHP/MariaDB PIDs. Only the corrected matrix fixture was invoked, once.

**PASS (38):** Task5 step-up behavior matrix. Sanitized actual-route outcomes:
- `approve_new`: HTTP 302, Location `approved`, proposal `approved`, one event, actor matches, canonical subject created by President.
- `approve_alias`: HTTP 302, Location `approved`, proposal `approved`, one event, actor matches, exact canonical target matches.
- `reject`: HTTP 302, Location `rejected`, proposal `rejected`, one event, actor matches.
- Alias-target mismatch: HTTP 302, Location `failed`, proposal remains `pending`; proposal/event snapshot and fixture-key alias plus two-target catalog snapshot unchanged.

Cleanup PASS; post-run `wp db check --silent` PASS; baseline users/credentials/challenges/proposals/events/mandates/audit/subjects/aliases restored to `1/1/0/0/0/0/0/0/0`; protected credential unchanged. Only ZMP plugin remained active; four-file parity and PHP286942/8101 + MariaDB286676 preserved. No raw URL, assertion, challenge, credential, or private data was emitted.

The earlier matrix FAILs were under the now-confirmed by-value capture defect and remain in history, but are invalid as product-behavior evidence. The corrected current-hash run is PASS. Separate positive-route fixture remains uncorrected/unrun and its prior failure has the same capture limitation. Concurrent replay/interleaving remains **NOT RUN**. UI review, Task4 checks and whole-phase acceptance remain outside this invocation; this does not accept the full phase.
