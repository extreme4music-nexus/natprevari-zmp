# 🚨 CURRENT OWNER STATUS — 2026-11-26 — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

**CURRENT .66.a STATUS: ITEMS 1–5 RETAIN THEIR EXACT-SCOPE PASS EVIDENCE. PRESIDENT APPOINTMENT ROLE-POLICY FIX: FOCUSED 18/18 PASS. ITEMS 6 AND 7 ARE DEFERRED FOR FINAL CLOSE-OUT AFTER THIS SOURCE CHANGE; THE EARLIER FULL-REGRESSION AND ZIP PASS DO NOT APPLY TO THE CURRENT SOURCE. AUTHORITATIVE STATUS: `reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`.**

## CURRENT PASS SUPERSESSION — PROFILE, PASSKEY SCHEMA, EXPIRY, AND UI LAYOUT — 2026-11-25

**Original open-scope items 2, 3, 4, 5, and 6 are PASS on the existing local clone at `:8111`; do not count older NOT RUN/INCONCLUSIVE records for these exact scopes as current.** Item 2: synthetic President/Teacher profile form submit/reload/readback plus stale-form partial-save repair. Item 3: exact v2-to-v3 `ADD COLUMN peer_hash` failure injection failed closed. Item 4: normal expiry cleanup removed only an expired row and preserved a future-expiring row. Item 5: owner-confirmed Cancel followed by deliberate PIN-authenticated retry; repeated locally with assertion success and persisted synthetic approval. Item 6: corrected profile layout, visible focus, keyboard interactions, and owner-confirmed Narrator operation (`reports/PRESIDENT_TEACHER_PROFILE_UI_LAYOUT_AND_KEYBOARD_CHECK_1.2.4.66.a_2026-11-25.md`). PIN entry/waiting is expected, not a defect. As of 2026-11-26, checklist items 1–7 PASS. The owner installation check is the next user-side step. Consult the 2026-11-26 regression report.
## CURRENT .65 TRANSFER ADDENDUM — 2026-11-25

The direct comparison found and transferred one additional user-facing .65 change that had been incomplete in this phase: when a valid Subscriber + Teacher account has no linked teacher record, the WordPress profile now explains to check both roles and the linked teacher record. The service still returns `teacher_profile_required`; the missing-link state is not mislabeled as a schema failure.

Verification on the existing local clone at `http://127.0.0.1:8100`: inherited-fix regression **12/12 PASS**; rendered missing-profile guidance **1/1 PASS** using a synthetic Administrator + Subscriber + Teacher account with no mentor row; PHP lint **PASS**; `wp db check` **PASS**; HTTP **200**. Synthetic test user was removed. `.66.a` source was restored in the clone after `.66.b` verification. The owner-confirmed 2026-11-24 PASS results remain final for their exact scopes.

Updated UI source SHA-256: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`. This is a scoped transfer confirmation; it does not close whole-phase acceptance or production checks.

## CURRENT PRESIDENT SELF-SETUP AND DIAGNOSTICS — 2026-11-25

A current-role regression exposed that an Administrator editing another President's first unlinked profile could trigger the President's first-time Teacher setup after President became a required Teacher role. The setup path is now owner-only for an unlinked President: the profile does not offer that self-setup form to another account, and a non-owner save is denied. The owner retains the required `zmp_president + zmp_teacher + subscriber` stack and can complete the normal setup.

The denial writes a privacy-safe Debugging event `School profile first-time President setup denied.` with `error_code=president_setup_not_owner`; no profile values, email, nonce, or request body are logged.

Current local results on `127.0.0.1:8100`: stacked-President first setup, denial for another account, form render, rollback after invalid partial input, successful form-handler save/readback, collision no-autolink, and cleanup/integrity **10/10 PASS**; inherited regression **12/12 PASS**; missing-link guidance render **1/1 PASS**. `.66.b` was tested in the same clone and `.66.a` was restored afterward. Browser-driven submission and the President passkey step-up ceremony remain separate UI scopes.

New regression fixture: `tests/phase66a-president-teacher-stacked-profile-self-setup.php`. Current shared support source SHA-256: `49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3`; Debug source SHA-256: `c3c0771ff72a35a16bb5af0f6ace079128c6c19f756929cfffa142920ada5a28`; profile UI SHA-256: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`.


## CURRENT REGRESSION STATUS — 2026-11-26

**CHECKLIST ITEMS 1–6: PASS. ONLY ITEM 7 (FRESH ZIP, PHASE-HASH VERIFICATION, AND AUTHORIZED GITHUB SYNC/READBACK) REMAINS.** The end-to-end step-up matrix passed 38 checks on the existing local `:8111` clone; the exact-hash concurrent V2 fixture remains PASS. See `reports/FULL_FUNCTIONAL_SECURITY_REGRESSION_1.2.4.66.a_2026-11-26.md`. Do not use older open-count statements below this current addendum.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# 🚨 OWNER POLICY — WORDPRESS MEMBER ROLE STACKING

## REQUIRED RULE — READ BEFORE CONTINUING ANY PHASE

**EVERY ZMP MEMBER ACCOUNT MUST HAVE THE WORDPRESS `subscriber` ROLE.**

**`zmp_teacher` IS OPTIONAL FOR A MEMBER, BUT ANY MEMBER MAY ALSO HAVE IT. IT MUST WORK TOGETHER WITH `subscriber` AND WITH OTHER VALID ZMP ROLES WITHOUT BREAKING THE PLUGIN.**

- `subscriber` is the required baseline membership role.
- `zmp_teacher` is additive, not a replacement for `subscriber`, and is not required for every member.
- Members may also hold any authorized combination of `zmp_moderator`, `zmp_commission`, `zmp_testator`, `zmp_organizer`, and `zmp_president`. Role assignment/removal must preserve the other existing roles.
- The President must always retain both `subscriber` and `zmp_teacher`, in addition to `zmp_president` and any other authorized roles.
- An external Commission person who is not a ZMP member may lack `subscriber` and `zmp_teacher`; if that person is a ZMP member, `subscriber` is required and `zmp_teacher` remains optional.
- Teacher-only functions (school profile, teacher directory, teacher subject proposals, and other explicitly teacher-scoped features) require `zmp_teacher`. Missing `zmp_teacher` must not break unrelated plugin functions for a valid Subscriber member.
- Presence of `zmp_teacher` must not make a valid role stack fail, hide unrelated plugin features, replace roles, or crash the plugin. Authorization must check the capability/role needed for each operation rather than requiring an exact one-role array.
- Test fixtures for a ZMP member must include `subscriber`. Teacher-positive fixtures must include both `subscriber` and `zmp_teacher`; negative Teacher-scope fixtures may omit `zmp_teacher` while retaining `subscriber`.

**THIS OWNER POLICY, RECORDED 2026-11-25, SUPERSEDES ANY CONFLICTING HISTORICAL WORDING IN ROADMAPS, PLANS, REPORTS, TEST NOTES, OR PHASE COPIES. HISTORICAL PASS/FAIL RESULTS KEEP THEIR ORIGINAL SCOPE AND MUST NOT BE EXPANDED TO COVER ROLE COMBINATIONS THEY DID NOT TEST.**

---

# Debugging Guide — ZMP Competitions 1.2.4.66.a

## CURRENT INHERITED-FIX STATUS — 2026-11-25

The `.65` MySQL integer metadata, stale-schema retry and Administrator + Teacher profile fixes are present and passed on the exact `.66.a` source in the local clone. A partial school-profile index failure was injected and verified fail-closed; the index/options were restored. Details and source hashes: `reports/INHERITED_65_FIXES_AND_66A_OPEN_SCOPE_1.2.4.66.a_2026-11-25.md`.

For a remaining **President passkey step-up** report, distinguish options request, authenticator result, assertion submission and decision persistence. The last recorded browser attempt did not submit a decision and did not capture the failing stage. Do not infer the cause from the generic UI message. Do not repeat the owner-confirmed registration/login checks.

For a remaining **President/Teacher profile browser submit/readback**, the local service-level synthetic profile save/readback is PASS; browser form submission remains unverified. Keep those evidence scopes separate.

Status: **synthetic implementation under review; not released**. Do not diagnose this phase through production or real accounts.

## Planned categories
- `passkey_registration`: expected behavior is registration only for the current account owner with the exact `zmp_president` role and an active mandate, after current-password reauthentication and a valid one-use session-bound challenge. Options and finish both recheck authority. For a denial, check the synthetic user's current role and mandate state; never log the password, challenge, user handle, or credential payload.
- `passkey_login`: expected behavior is a valid assertion creating a WordPress session for only the credential's bound user; public failures must not enumerate accounts.
- `passkey_step_up`: expected behavior is a fresh assertion bound to one action/resource, followed by all existing server-side role, mandate, resource and nonce checks. See the Task5 route procedure below.
- `passkey_removal`: expected behavior is removal only by the current signed-in credential owner after current-password reauthentication and a dedicated valid nonce. Removal remains available after role/mandate loss. Check sanitized success/failure and synthetic row state; never log the credential ID or password.
- `passkey_recovery`: expected behavior is a separately approved and audited recovery path; no silent bypass on authenticator loss.

## Safe future diagnostics
Record only a fixed category, bounded result code, phase/environment label and a non-sensitive correlation ID. In synthetic tests, verify the stored credential belongs to the synthetic user without printing credential material. Compare before/after row counts and authorization outcomes, not user payloads.

## Never log or expose
Passwords, session cookies, nonces, WebAuthn challenges, raw clientDataJSON/authenticatorData/signatures/attestation, credential private keys, recovery codes, OAuth tokens, full names, emails, school/participant data or uploaded documents. Public-key credential material is still security-sensitive; restrict access and never dump it to reports.

## Common failure causes to check after implementation
Expired or reused challenge; incorrect origin/RP ID; HTTP on a non-localhost origin; user/credential mismatch; missing user-verification flag when required; changed role/mandate/resource; browser authenticator cancellation; clock skew; plugin cache/dependency mismatch; database migration incomplete; or unsupported local PHP crypto extension. Do not weaken verification to make a test pass.

## Passkey removal: secure origin and ordinary profile save
Expected behavior: the current-password field is optional for the surrounding WordPress profile save; the passkey buttons themselves prompt for a password and validate it on the server. Removal runs only for the signed-in credential owner with a valid dedicated nonce and current password. It is denied before posted credential/password values are processed when the browser context is insecure or the origin does not match policy.

Safe checks in the authorized synthetic site:
- Confirm the profile is opened at the configured canonical HTTPS origin. HTTP is allowed only for the local loopback development origin and only when the server's actual `REMOTE_ADDR` is loopback; a spoofed `Host` value is insufficient.
- Confirm the browser reports a secure context before pressing a passkey control. The server independently compares request scheme/host/effective port with the configured origin and checks any supplied `Origin`/`Referer` for same-origin equality.
- A generic refusal can be caused by remote HTTP, a mismatched alias/port, missing/mismatched origin headers, a non-loopback peer using a loopback host name, invalid nonce, expired session, wrong owner, or current-password failure. Use the configured URL and reauthenticate through the normal account flow; never weaken the guard to make a test pass.
- For diagnosis record only fixed category/result code, environment label, and a non-sensitive correlation value. Do not log passwords, credential IDs, Origin/Referer values, cookies/session tokens, nonces or authenticator payloads.

## Passkey login page — in progress, not released
Expected behavior: the WordPress login page offers a discoverable passkey option and keeps the standard password form. The button requests anonymous same-origin options, invokes `navigator.credentials.get()` only in a secure supported context, then posts the assertion to the same-origin finish endpoint. Unknown-account/invalid-assertion errors remain generic; password login remains available.

Safe checks: verify the current URL is the intended local login page; check the passkey button's accessible status for unsupported browser, cancellation, or generic failure; use the normal password form if no authenticator is available. Do not infer account existence from public failures. Never log or report assertion JSON, challenge, credential ID, session cookie, account identifier, or private-key material. UI integration is implemented but not independently reviewed or browser-authenticator verified yet.

## President proposal decision step-up — in progress, not released
Expected flow: on the President subject-proposal queue, choosing approve-new, approve-alias, or reject starts a same-origin authenticated options request using the form's route nonce. The browser invokes `navigator.credentials.get()` from that user action and submits the assertion, challenge ID, and original route decision only after success. The server binds the proof to the active President's user/session, configured origin/RP, route nonce, exact proposal and decision, and alias canonical target. Existing role/capability, active mandate, pending-resource and transaction checks remain authoritative. Cancellation, unavailable authenticator, invalid proof, expired challenge, changed nonce/context or transport failure must leave proposal status/events unchanged.

Safe diagnostic steps in the synthetic site:
- Confirm the URL is the configured local test origin and the current account has exactly the President role plus an active mandate; inspect only synthetic role/mandate state.
- Check the accessible status text after options/cancellation/failure. If options fail, compare the sanitized precondition results for current session, route nonce, secure same-origin context, schema/library availability and credential presence; do not print nonce, cookie, assertion or credential bytes.
- For an attempted decision, compare only the synthetic proposal status and event count before/after. A valid assertion must be tied to the same proposal, exact decision, current route nonce and alias target. A generic failure should not trigger a retry with weaker authorization.
- Current corrected, independently reviewed synthetic route matrix is **PASS (38)** with expected approve-new/approve-alias/reject redirects and exact actor/event/canonical-target checks; the alias target mismatch is denied without proposal/event or fixture alias/catalog changes. Cleanup, DB baseline, credential preservation, source parity, plugin inventory and services passed. Earlier failed executions stay preserved; confirmed invalid fixture runs are not product behavior evidence.
- This is a server-side synthetic result. Actual browser WebAuthn ceremony, cancellation/unavailable-authenticator UI behavior, independent Task5 responsive/keyboard UI review, Task4 login UI/browser verification. Do not claim the feature or phase accepted from this matrix alone.

Never log or expose route nonce, challenge ID/value, session cookies/token, assertion JSON, credential ID/public-key row, private-key material, proposal personal data, or real users' information. Evidence should contain fixed result codes and sanitized synthetic counts only.

## Browser enrollment diagnostic — 2026-11-06
A same-tab local synthetic-President attempt displayed the generic enrollment failure after “preparing,” cleared the password, and showed no authenticator prompt. The JS maps options-endpoint, `navigator.credentials.create()` and finish failures to one status; server options handling similarly hides nonce, role/mandate, password, schema, origin and library details. Therefore this message cannot identify the cause. A zero unconsumed registration-challenge count is inconclusive because options construction may burn a newly created challenge. If another attempt is authorized, capture only the options request HTTP status and whether the UI reaches “continue in browser”; never capture request payloads, passwords, credential IDs or attestation. Mobile View rendering does not prove WebAuthn ceremony support.

## Existing local enrollment log evidence — 2026-11-06
The local synthetic PHP server (`PID 286942`) writes to `/home/tonic/zmp-local-sandbox/phase66a-test/run/php-server.log`. WP_DEBUG/DISPLAY/LOG are disabled. Sanitized method/endpoint/status scan found POST `admin-post.php` 403 responses at 15:04:35 and 15:05:18, but log entries omit the action and request body, so they cannot be matched to the passkey attempt. Repeated `admin-ajax.php` 403s do not use the configured enrollment endpoint and are not relevant evidence. Source mapping: the options handler returns 403 when `valid_request()` rejects auth/capability/current user, POST method, action or nonce; returns 400 for registration-options `WP_Error`; returns 200 with options on success. Therefore 403 means pre-password request validation only if tied to this enrollment request. Keep server cause unresolved until status-only request correlation; never log body/password/nonce/cookie.

## Owner-reported options HTTP 403 — 2026-11-11
- Owner reports the UI displayed: “Дијагностика: барањето за серверските опции не успеа (HTTP 403).” This indicates the options request failed before the browser WebAuthn ceremony; it is not evidence about authenticator prompt or attestation.
- The exact wording is not present in the current `.66.a` JS candidate: its diagnostic branch should show either the allowlisted PHP header category or “недостасува plugin header” plus redirect/route/content-type classes. Treat the loaded JS/site version as unverified (unless the Owner paraphrased the displayed wording).
- Read-only inspection of the phase PHP access log found no new matching `admin-post.php` request after the 2026-11-06 entries; the 2026-11-11 PHP server start is present. Current Edge inventory also had no ZMP test tab. Therefore this report cannot yet be correlated to the authorized `.66.a` runtime, and no server-side cause is established.
- Source behavior: this phase handler emits HTTP 403 for failed session/capability/method/action/nonce validation; registration-options errors, including password validation, map to HTTP 400. The 403 alone does not identify which branch ran or whether this handler produced it.
- **Status: USER-REPORTED / NOT CORRELATED; root cause unresolved.** Do not repeat enrollment yet. Next safe step is to confirm the displayed page is the `.66.a` synthetic local site and that its loaded JS matches the candidate, then obtain one status-only reproduction that is visible in the local access log. No credential/passkey, product PASS, or fix is claimed.

## Runtime correlation after Owner login — 2026-11-11 11:17–11:18 local
- Access log confirms authenticated profile load and GET of `/wp-content/plugins/zmp-competitions/assets/js/zmp-passkeys.js?ver=1.2.4.66.a` at 11:17:13; REST `/users/me` returned 200. This verifies the `.66.a` page/session and versioned JS were loaded.
- At 11:18:14 the only new POST was `admin-ajax.php` with HTTP 403. No `admin-post.php` passkey-options request or `[ZMP_PASSKEY_DIAG]` marker has appeared. Do not attribute the AJAX request to passkey enrollment without evidence.
- Owner says already logged in and is preparing the attempt. Browser ceremony/options request remains NOT RUN in this correlated session; await the single Begin click and then inspect only the sanitized endpoint/status/category.

## Read-only UI state after Owner login — 2026-11-11
- Inspected the existing Edge tab at the local synthetic `.66.a` profile page. WordPress session is authenticated as a synthetic active-President UI account; the passkey controls are rendered and the `Current password` field is blank. The Begin button is present. No button was clicked and no password entered by the Lead.
- This explains why no enrollment request is present in the 11:17+ access log yet: the frontend requires the current account password before it sends options. It does not explain the earlier user-reported 403, which remains uncorrelated. Next step requires Owner to enter the current synthetic account password locally and click Begin once; share only the displayed diagnostic text.
- No UI PASS or enrollment result is claimed; field/button state only. The scoped security/privacy code review remains PASS at the exact hashes recorded above.

## Independent Debugger code trace — enrollment HTTP 403 — 2026-11-11
- Read-only Debugger report: `reports/DEBUGGER_ENROLLMENT_403_CODE_TRACE_1.2.4.66.a.md`, SHA-256 `634FF17A2EAD8A7518BEDAEF427AACE15AC305224745B522B7B7BD2B91B49EF7`. Exact reviewed candidate hashes are in the report; no runtime call, test, DB/site change or code edit occurred.
- Proven code mapping: `handle_options()` emits 403 only when the pre-password `valid_request()` checks reject (login, capability/user, POST method, action, nonce configuration/presence/validity); it records a fixed diagnostic event and header if local diagnostics are enabled. Once those checks pass, wrong password and other `registration_options()` preconditions return 400, while success returns 200.
- Therefore password alone does not explain an enrollment HTTP 403 in this candidate. Existing 403 log lines lack action/body and remain uncorrelated; recurring AJAX 403s are a different endpoint. Current source does not prove the earlier user-reported status came from this candidate/runtime.
- Runtime root cause remains **UNRESOLVED**. On the Owner's already-planned click only, correlate endpoint/status, fixed diagnostic header/category and matching fixed log marker. If a 403 has no marker/header, leave origin unresolved; do not collect body, password, nonce, cookie or credential data, and do not trigger another attempt just for this report.
- No functional test or PASS claim added; enrollment browser flow remains NOT RUN in the current correlated session. Phase/release remains IN PROGRESS / OPEN.


## Current-hash verification reference — 2026-11-11
Current exact-hash focused backend results are recorded in `reports/CURRENT_HASH_FUNCTIONAL_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md` (SHA-256 `14154257191DA4A2596FB2CDAD0C0D641C4CDF74B783C15D577308FB2E7A54E5`). Current acceptance gates are in `reports/GATE_READINESS_CURRENT_1.2.4.66.a_2026-11-11.md` (SHA-256 `99D3FE50E993378E1A381969AC36318024037B548239F107330B1F712A3F9A83`). Neither report contains credentials or assertion/cookie data.
## Migration runtime status — 2026-11-11
- Independent runtime report `reports/PASSKEY_SCHEMA_MIGRATION_ROLLBACK_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `7E8C21A98B5C7F279665E485E096165CF0AA200A9366FC8D019DFA3F018B1C75`: existing-v3 idempotence and exact `.65` rollback/reinstall behavior PASS at scoped runtime level. Fresh passkey schema creation has a separate scoped PASS report; partial-schema failure injection remains INCONCLUSIVE/NOT RUN. This is partial evidence, not phase acceptance.
- Current readiness follow-up `reports/GATE_READINESS_RUNTIME_FOLLOWUP_1.2.4.66.a_2026-11-11.md`, SHA-256 `E697323C98CF552C0344114AF3C40E2B0E4B2FA8E156502FCD97E532F0CDAF48`, keeps overall NOT_READY. No package/GitHub sync or phase acceptance.

## Fresh passkey schema creation evidence — 2026-11-11
- Independent Test Engineer report `reports/PASSKEY_FRESH_SCHEMA_CREATION_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `E7F4FB749950255F3226345A4556537010BF75A8612EF6F0A9C9E6A186413136`: first creation on a separate copied synthetic child DB **PASS**. `.66.a` normal plugin loading created both passkey tables, expected indexes and marker 3; compatibility and DB integrity passed; unrelated synthetic competition data stayed unchanged. The child models missing passkey schema on copied data, not a full historical `.65` database.
- Readiness follow-up `reports/GATE_READINESS_AFTER_FRESH_SCHEMA_1.2.4.66.a_2026-11-11.md`, SHA-256 `F19445728E230492298B94AEFDDBA3577721742936ECFA879B45DBE28CADAB5C`: overall remains NOT_READY. Partial migration failure injection and all browser/Master/Auditor/release gates remain open/NOT RUN.

## Partial migration failure injection attempt — 2026-11-11
- Test Engineer report `reports/PARTIAL_SCHEMA_FAILURE_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `26DC3E28CFAE0CC2262A5092511127C836C3B5687D8044C08CD077F3B5A327B2`: the reviewed exact DDL filter intercepted zero queries. The attempt is **INCONCLUSIVE / NOT PASS**; no broad retry occurred, and it establishes no product defect. The intended fail-closed scenario remains **NOT RUN / unresolved**.
- The injector was removed and the child DB restored/verified (schema marker3, compatible, both tables/indexes, baseline, no passkey rows and integrity PASS). Main clone and Owner site/services remained unchanged. Current gate report `reports/GATE_READINESS_AFTER_PARTIAL_SCHEMA_ATTEMPT_1.2.4.66.a_2026-11-11.md`, SHA-256 `549AFDE977B7906C0DA84BE9BC7BDEBFA5EABAD1335AEF950A4A3DC954339587`, overall NOT_READY.

## Current-source login quota regression note — 2026-11-11
The four current-source backend security fixtures for request context/rate limits, aggregate challenge quota, issuance churn and transient eviction passed on the isolated synthetic clone. The expired-challenge cleanup fixture did not run: its hard-coded site identity guard does not match the authorized clone. Do not change/bypass that guard against the Owner's active site. Report only generic result categories; never log challenge tokens, request bodies, cookies, assertions, passwords or credential material. Exact outcomes and fixture hashes are in `reports/CURRENT_SOURCE_LOGIN_QUOTA_REGRESSION_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`.

## Fresh local access-log correlation — 2026-11-11 13:33 local
- Read-only scan of the active synthetic PHP log at `/home/tonic/zmp-local-sandbox/phase66a-test/run/php-server.log` used only timestamp, method, endpoint class and status. Across 18,127 timestamped log entries, 139 request records were dated Nov 11; there were 47 `admin-ajax.php` POST/403s (latest observed 13:33:37), zero `admin-post.php` requests dated Nov 11, and zero `[ZMP_PASSKEY_DIAG]` markers.
- This supports that no current-session enrollment-options request has reached the active server in the scanned interval. The recurring AJAX 403s remain uncorrelated and cannot be attributed to passkey enrollment. Older admin-post 403s remain uncorrelated by action and are preserved as historical evidence.
- No request was triggered; no DB/browser/service change. Diagnostics may be disabled; absence of a fixed marker alone does not identify a root cause. Current enrollment 403 remains **UNRESOLVED / NOT REPRODUCED**.
- Next safe step remains one Owner-initiated Begin click on the existing logged-in Edge tab after the Owner enters the current synthetic password locally; correlate only endpoint/status, allowlisted header/category and fixed marker. Never collect body/password/nonce/cookie/assertion/credential data.

## Enrollment status recheck — 2026-11-11
- Report: reports/PASSKEY_ENROLLMENT_SERVER_LOG_RECHECK_1.2.4.66.a_2026-11-11.md, SHA-256 EF67367897B0AF94FFDC7EA5B8445264D3A3FAAC536F64E3CBFF0BA7EF4490C9.
- Read-only same-tab review showed the logged-in synthetic President profile and enrollment control, with no displayed enrollment outcome. Do not inspect the Current password value.
- Sanitized log scan recorded 141 Nov 11 request records, zero admin-post.php, 49 admin-ajax.php POST/403 and zero fixed diagnostic markers. There is no current enrollment request to diagnose; do not attribute AJAX 403s to this flow.
- Current static mapping remains: valid_request() rejection returns 403; password/precondition rejection from registration_options() returns 400. Runtime cause is UNRESOLVED / NOT REPRODUCED. Next safe discriminator is one Owner-initiated same-tab click with only endpoint/status/fixed diagnostic category captured.

## Task5 historical failure disposition — current-hash result — 2026-11-11
- The old matrix/positive-route failures remain preserved as historical fixture outcomes. Current-hash Test Engineer report `reports/CURRENT_HASH_FUNCTIONAL_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `14154257191DA4A2596FB2CDAD0C0D641C4CDF74B783C15D577308FB2E7A54E5`, now supplies the current scoped Task5 route matrix **PASS (38)** on exact reviewed fixture SHA `58FA9A3519DE44C909600634972D9432A093321CF174A13696914F372D860901`.
- Preserve the historical fixture/debugger diagnosis and prior failure results; do not carry them forward as the current Task5 route status. True concurrent race and browser/authenticator ceremonies remain NOT RUN.

## Current candidate status and safe browser diagnosis — 2026-11-11
- Current-hash synthetic backend evidence: registration/management PASS49; login assertion/session PASS17; President decision matrix PASS38; login context/rate PASS7, aggregate quota PASS5, issuance churn PASS9 and transient eviction PASS4. These remain scoped to their reports and isolated synthetic clone.
- Scoped migration/rollback and copied-child fresh-schema checks PASS. Partial-schema injected-failure behavior remains INCONCLUSIVE/NOT RUN; expired-challenge cleanup is NOT RUN after one bounded preflight failure; do not repeat that failed preflight.
- The current Edge tab could not be bound because the computer-use runtime returned “Local browser access is unavailable for this chat.” No alternate UI route is permitted. The active synthetic site config has diagnostics enabled, and current candidate/runtime hashes match for the enrollment handler, profile controller and enrollment JS. Logs still show no correlated Nov 11 admin-post enrollment request; unrelated admin-ajax 403s do not identify the cause.
- For the Owner's later local click, collect only the endpoint/status, fixed diagnostic category/header and matching fixed marker. Never inspect password, nonce, cookies, body, attestation or credential. If no correlated request/marker appears, keep cause unresolved.
- Overall phase remains IN PROGRESS / NOT_READY; no browser ceremony, current profile UI review, Master/Auditor, ZIP or GitHub readback is claimed.





## Step-up route replay concurrency fixture — preparation only — 2026-11-11
The reviewed test-only fixture SHA-256 `36B7D2295145C4B255FC11731302052377484685B1E828ACFF48D7D91AD067AC` has static safety/design PASS only (review report SHA-256 `C01039A9254D80AE87D02490346097879C7A2588718093ADD3A8C1318668C26F`). It calls the actual President handler from two clone-only WP-CLI workers after a test-only rendezvous before exact challenge-consumption SQL. It has not run. Wait for the preparation note to bind exact clone, DB/socket, backup, baseline, barriers, timeout and cleanup. Do not present static review as runtime PASS or deterministic database scheduling proof.

## Concurrent President route replay — 2026-11-11
Test Engineer report reports/CURRENT_HASH_PRESIDENT_STEPUP_CONCURRENT_ROUTE_REPLAY_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md, SHA-256 `950C8430E9D8405A40EC29CD61F1E1B40B291015077560462C2D243CBC28B7C0`; one reviewed invocation, exit 1, recorded FAIL. Both WP-CLI workers reached the exact challenge-consume SQL boundary and returned one rejected/one failed. At least one post-handler assertion failed, but privacy-safe output does not distinguish which; do not infer individual proposal/event, challenge-consumption, credential-counter, or worker-exit outcomes. No retry. Cleanup, baseline equality, protected credential and DB integrity PASS; fixture copy/state/fingerprint removed, backup retained privately; source unchanged. Preparation-note independent review PASS SHA `60A9A68959AECD975493ECCDB7C6230B0222E33F624EA358D5CB62ED63454D38`. Clone DB PID 3828 and Owner DB PID 409 remained present; Owner PHP PID 620/8101 was already absent and left untouched; clone PHP/8102 and owned worker processes/artifacts absent after run. .66.a remains IN PROGRESS / NOT_READY; Master/Auditor, browser UI, package and GitHub gates remain open.


## Independent debugger follow-up — 2026-11-11
Read-only Debugger report reports/DEBUGGER_CONCURRENT_STEPUP_REPLAY_FAILURE_1.2.4.66.a_2026-11-11.md, SHA-256 F8B3CA31636A6C18FAB7BCCDB56F8C5927F237BEF28DD0A1385483B121771CCF, classifies the runtime FAIL as INDETERMINATE: the evidence cannot distinguish product behavior from worker/fixture assertion bookkeeping. Static code paths are consistent with one-time challenge consumption and one decision, but they do not prove the winning request persisted every side effect. No product defect is confirmed; preserve the runtime gate as FAIL and do not rerun the unchanged fixture. The exact unresolved assertions are proposal/event, challenge consumption, credential counter and worker exit. A separately reviewed diagnostic-only fixture may emit fixed assertion IDs without exposing values; original fixture/report and all prior results remain preserved.
## Diagnostic follow-up fixture prepared — 2026-11-11
To resolve the single prior route-replay FAIL without rerunning the unchanged fixture, Test Engineer prepared a separate diagnostic-only fixture tests/phase66a-president-step-up-concurrent-route-replay-diagnostic.php, SHA-256 49998D8C6FF39437920CA9541870C9A175373CBAD2A10C8FBF5927C71706A466, and prep note reports/PREPARE_DIAGNOSTIC_PRESIDENT_STEPUP_REPLAY_1.2.4.66.a_2026-11-11.md, SHA-256 4918A12CF92B743CD540D886758BDB243BB5A2EA194AB0859C74881CB9A1ED6E. The original fixture and FAIL evidence remain unchanged. New fixture is NOT RUN; independent exact-hash review is pending. It must not execute until review passes and the full fresh private-clone preflight/backup is repeated. No runtime or product PASS is implied.
## Diagnostic fixture independent review — 2026-11-11
Independent exact-hash Code Review of the diagnostic-only fixture and preparation note is PASS for static safety/prep consistency; runtime remains NOT RUN. Report reports/CODE_REVIEW_DIAGNOSTIC_CONCURRENT_STEPUP_REPLAY_FIXTURE_1.2.4.66.a.md, SHA-256 C2BD91B00DA037A58B48510EEF0CA9547D85B30224707DD0FC718A5484065AF5, binds fixture SHA-256 49998D8C6FF39437920CA9541870C9A175373CBAD2A10C8FBF5927C71706A466 and prep note SHA-256 4918A12CF92B743CD540D886758BDB243BB5A2EA194AB0859C74881CB9A1ED6E. The original concurrent route runtime FAIL remains unchanged. One separate diagnostic-only invocation is now being performed by Test Engineer after a fresh preflight and private backup; no retry is permitted.
## Diagnostic route replay outcome — 2026-11-11
Test Engineer report reports/CURRENT_HASH_DIAGNOSTIC_PRESIDENT_STEPUP_REPLAY_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md, SHA-256 6C89EE99DCB140E0691F8843B445398F2F0F566FD1FFA3818C499AB8007F6400, records the one separately reviewed diagnostic run as FAIL (exit 1), with only the allowlisted failure ID worker_exit. The three route-side assertion IDs proposal_event, challenge_consumed and credential_counter were not reported as failures; cleanup and integrity IDs also were not failures, independently supported by baseline restoration and wp db check. However worker_exit combines boundary/status-matrix checks, worker result checks and process-exit bookkeeping, so its precise subcause remains unresolved. Do not infer deterministic SQL interleaving, full route acceptance, or a product defect. No third run or retry. Diagnostic fixture SHA 49998D8C6FF39437920CA9541870C9A175373CBAD2A10C8FBF5927C71706A466; its independent review PASS report SHA C2BD91B00DA037A58B48510EEF0CA9547D85B30224707DD0FC718A5484065AF5. Fresh private backup retained; clone baseline/integrity restored; temporary fixture/workers/artifacts removed. No product source change.

## President's own teacher-school profile — 2026-11-12 (candidate, unreleased)

**Expected flow:** WordPress profile hooks render the school-profile section for the signed-in owner. `ZMP_Mentor_School_Profile::get_engagements()` checks `can_manage_own_teacher_profile()` (exact teacher role, or exact President role with President dashboard capability), resolves only the same user's `zmp_mentors.wp_user_id`, then returns active school engagements. Profile reads and saves remain restricted to that mentor's active school; saves also require the user-bound nonce and existing field validation. The President role remains exclusive.

**Safe checks:** confirm the account has the exact `zmp_president` role and `view_zmp_president_dashboard` capability; confirm one mentor record is linked to that same user; confirm at least one active school engagement. A missing link returns `teacher_profile_required` and the UI message “Наставничкиот профил не е поврзан со оваа сметка.” A schema failure keeps the generic unavailable state. Other-user and inactive-school requests must remain denied.

**Common failure causes:** missing mentor mapping, no active school engagement, President capability/role mismatch, incompatible school-profile schema, invalid nonce, or changed ownership. Do not fix a missing mapping by inventing school/employment details or adding a teacher role to the President.

**Privacy-safe evidence:** record only role/capability booleans, fixed error code, active-engagement count, response status, and source hashes. Never log or attach official email, employment term/workload, retirement status, replacement identity/dates, nonces, cookies, or full profile payload. Browser UI review remains NOT RUN on this candidate.

## Passkey enrollment blocked before handler — 2026-11-12
For an active President-only account, ZMP_President_Dashboard::enforce_admin_scope() applies before admin-post dispatch. Passkey registration options/finish and removal must be in its narrow action allowlist; otherwise the scope gate returns 403 before password or nonce checks run, which can surface as the generic enrollment failure even when ordinary login succeeds. The allowlist now includes the three ZMP_Passkey_Profile actions. If enrollment still fails after this candidate is installed, capture only the fixed diagnostic category/HTTP status and correlate it with the handler stage; never record password, nonce, cookie, assertion, or credential ID. Browser recheck remains NOT RUN.

## Password accepted, browser stage pending — 2026-11-12
If enrollment displays the generic current-password warning, first distinguish the server stage from the browser stage. In this local run, options_request_accepted + registration_options_succeeded + options_handler_succeeded prove WordPress accepted the request and current password; absence of a finish request means the credential ceremony did not reach final submission. The browser diagnostic flag may be serialized by WordPress as string "1"; the JS now accepts both true and "1" to show fixed diagnostic categories. On the next Owner-initiated click, report only the displayed fixed diagnostic line/category and status. Do not disclose password, request body, nonce, cookies, challenge, assertion, or credential ID.

### Distinguishing password attempts
The current-password field is cleared by JavaScript after each options request. Each new attempt therefore needs a fresh entry. Server log category password_rejected means that request's value did not match the current account hash; options_handler_succeeded and registration_options_succeeded mean that request's value did match. A prior pass and later rejection are per-request outcomes; do not conclude a different passkey password exists or log/compare raw values. Confirm the account hash did not change through an authorized synthetic-only check, then if needed have the Owner manually type the same password into the cleared field once and report only the fixed category/status.

## WebAuthn SecurityError on numeric loopback host
The local synthetic page used http://127.0.0.1:8101. The plugin derived RP ID 127.0.0.1 from the configured home URL. W3C WebAuthn requires the RP ID to be a valid domain and specifies localhost as an acceptable HTTP loopback origin; a numeric IP literal is invalid as RP ID. The disposable test site's WP_HOME and WP_SITEURL have been changed to http://localhost:8101. Browser cookies are host-scoped, so log in again on localhost in the same Edge tab. Do not change RP ID alone while browsing the numeric IP: origin and RP ID must match. Production configuration was not touched.

## Confirmed local passkey registration and sign-in — 2026-11-12
Owner-verified flow: registration and passkey sign-in both succeeded on the synthetic .66.a site. The server log confirms registration options and finish success, HTTP 200. Local origin is http://localhost:8101, so RP ID is localhost; do not use numeric 127.0.0.1 as RP ID. This local-only site configuration does not alter production. Record fixed statuses and exact candidate hashes only; never record password, nonce, cookie, assertion, challenge, credential ID, or private key. Other flow categories (step-up, cancellation, unavailable authenticator, replay) remain separately open.

## Native passkey provider prompt and PIN — 2026-11-12
- Expected behavior: clicking the login passkey control opens the browser's native authenticator/provider prompt; if the device requests its PIN, the user enters it locally, then the browser returns an assertion to the site and successful authentication returns to the authenticated page.
- Safe diagnosis: distinguish a visible native provider error from a site/server response. “Can't reach Microsoft Password Manager” indicates the browser/provider surface reported an error; it does not by itself prove what the site received. A later Owner-confirmed PIN entry and successful login is separate, valid evidence. Confirm the intended local origin is `http://localhost:8101` for this synthetic test; do not switch the RP ID alone or weaken assertion checks.
- Role/state: the synthetic account must have the registered passkey; login begins while logged out. For enrollment/step-up, their separate role/mandate/resource requirements still apply.
- Privacy: the user may enter a PIN in the OS prompt locally. Never request, capture, log, or store the PIN, passkey/account label, credential ID, challenge, assertion, cookie or token. If UI automation is blocked, an Owner-supplied screenshot can support visual rendering/status only; it does not prove an unseen site response or keyboard behavior.

## First-time President teacher-profile setup — candidate, unreleased — 2026-11-12

**Expected behavior:** On their own WordPress profile page (`/wp-admin/profile.php`), a signed-in account with the exact exclusive `zmp_president` role and `view_zmp_president_dashboard` capability can see the existing “ЗМП Натпревари — училишта и наставни предмети” form even when no `zmp_mentors.wp_user_id` row exists. The President enters their own school and municipality; the server derives the region, creates a mentor row bound to the current WordPress user, and ensures the primary school through existing helpers. The President role remains unchanged. If the approved subject catalog has entries, the normal subject/grade choices are required by the existing form.

**Safe steps:**
1. Open the signed-in synthetic President's own profile page and scroll to the school/teacher profile section.
2. If no mapped teacher record exists, the setup form should be visible. Enter only the correct synthetic/local profile values and save through WordPress's normal profile form.
3. If the form is missing, save returns a permission error, or a fixed error appears, record the role/capability result and HTTP status only. For UI review, send a screenshot with school/contact values blurred; do not include a password, PIN, nonce, cookie, or credential data.

**Common causes and response:**
- Wrong/mixed role or missing President dashboard capability: the setup form is not shown; ask an administrator to correct the account role/capability.
- Invalid school/municipality or unresolved region: existing server-side validation rejects the save; correct the municipality through the form.
- HTTP 409 “Постои наставнички запис со оваа адреса”: an existing mentor record uses the account email. The plugin does not attach it automatically. Ask an administrator to verify and explicitly associate the correct record; do not create a second row or change roles as a workaround.
- HTTP 503 catalog/database check: changes are not saved; keep the fixed status/category and ask the administrator to inspect the synthetic/local service.

**Privacy-safe evidence:** Use only the fixed response status/error category, exact role/capability booleans, and whether the self-owned mapping/active school count is present. Redact school, municipality, phone, email, profile contents, passwords, session cookies and request data. The code/fixture has not yet received independent review/runtime verification; Owner-supplied UI evidence is still required and no release is claimed.
## President profile setup integrity/concurrency revision — 2026-11-12
Independent SPEC/QUALITY review found non-atomic first-time writes and a check-then-insert email race. Candidate revision: exact President first setup now acquires a stable per-user lock and normalized-email lock, acquired in deterministic order, rechecks only the exact current `wp_user_id` mapping while locked, rejects any remaining case-insensitive email collision without linking, and holds the lock through commit. Before writes, it verifies every affected WordPress/ZMP table is InnoDB; if not, setup fails closed. A single transaction now covers the mentor row, user-meta, primary-school/engagement, optional school, and subject/grade writes; every error path rolls back and releases the lock. No schema change or role change. Added fixture coverage forcing a post-insert validation error and checking rollback leaves no mentor, school/profile, or profile user-meta. PHP syntax checks PASS for source and fixture only. Fixture runtime remains BLOCKED/NOT RUN because the prior current-candidate guard failed; do not retry. Independent re-review requested; no behavior, UI, or release PASS claimed. Source SHA-256 `CFFB2531DEACF4986FF5C6F6E6EFDAED8F08B84C38F00574788B7D247A33F515`; fixture SHA-256 `5C2C588EE752F2038D9835522E90B070706D4F1518C3F8A5A20733402EF10736`.

## Owner review follow-up: checked mapping/meta/lock operations — 2026-11-12
Candidate hardening records: the locked exact-user mapping read now fails closed on SQL error; collision query preserves/checks that error state and compares `LOWER(TRIM(email))`; normalized-email advisory lock now covers all unmapped teacher and President first-time submissions, while President also gets stable user-ID lock. Each of four profile-meta writes is followed by a direct stored-value/error check; mismatch rolls back the first-time President transaction. Release-lock return values are checked; an unconfirmed release cannot return normal success and displays a 503 instructing the user to check saved state before retry. Fixture expected support hash is updated. PHP lint PASS only, runtime NOT RUN, independent re-review PENDING, UI OWNER ACTION REQUIRED. Current source `CFFB2531DEACF4986FF5C6F6E6EFDAED8F08B84C38F00574788B7D247A33F515`; fixture `5C2C588EE752F2038D9835522E90B070706D4F1518C3F8A5A20733402EF10736`.


## Subject-proposal controls on narrow screens — 2026-11-12
Expected behavior: below 600 CSS px, canonical-subject selection and the approve/reject buttons appear in one vertical, full-width stack. The PHP form must render .zmp-subject-approvals__actions; the stylesheet's media query targets that wrapper. If controls still share rows or overflow, first inspect the rendered wrapper and whether the current CSS asset is loaded; do not submit a decision while diagnosing layout. Never capture or log passkey assertions, challenge values, nonces, credentials, or passwords. The current 400 px visual self-check passed; exact 375×812 and independent review were pending at that checkpoint; the final 375×812 review is now PASS after the stylesheet cache-key correction. See 
reports/UI_REVIEW_PRESIDENT_SUBJECT_APPROVALS_MOBILE_LEAD_CHECK_1.2.4.66.a_2026-11-12.md.



## Stale admin CSS on President subject approvals — 2026-11-12
Expected behavior below 600 CSS px: the .zmp-subject-approvals__actions wrapper is a column, the label has natural flex basis, and selector/buttons are full width. After a CSS edit, reload and confirm the loaded zmp-admin.css?ver= changes to the file modification-time value; then inspect computed label flex-basis and height. A fixed phase ZMP_VERSION URL can keep a prior stylesheet active during same-version updates. Do not read or record stylesheet response bodies to diagnose layout; URL version and computed layout are sufficient. Never submit the moderation form or capture passkey assertion, challenge, nonce, credential, or password data during layout debugging. Final scoped 375×812 UI PASS is in the cache-fix report; keyboard, screen-reader, decision-flow and physical-device checks remain NOT RUN.


## Current acceptance status — 2026-11-12 (unreleased)
- Passkey registration and repeat sign-in are **OWNER-CONFIRMED PASS**. The Owner's confirmation that the device PIN was entered and sign-in succeeded is accepted; do not ask for a separate screenshot or agent-side PIN proof.
- President subject-approval and teacher-profile mobile layouts each have a scoped independent **PASS at 375×812**. These are rendering/layout results, not a step-up decision or profile-form save.
- President teacher-profile service self-read/write is **PASS** on the isolated clone; first-time setup remains **BLOCKED**, and browser form submit/readback is **NOT RUN**.
- The President step-up decision remains **NOT RUN** until the Owner completes the requested synthetic decision in the existing local Edge tab. The concurrent replay remains overall **FAIL** with a separate static fixture-supervision **FAIL**; the safe rerun preflight is **BLOCKED** because reviewed GNU timeout is unavailable. No product defect is established by that fixture FAIL.
- Partial-schema failure injection and expired-challenge cleanup remain **NOT RUN**. The phase remains **IN PROGRESS / NOT_READY / NOT RELEASED**; do not package or claim release acceptance.

## Replay fixture supervision correction — 2026-11-12
- Owner direction: correct only the replay fixture, have it checked, and only then consider one isolated attempt.
- Fixture-only code path now uses dedicated `setsid` sessions, bounded TERM/KILL, `/proc` session/process-group membership checks, direct-child fallback when group enumeration is unavailable, and fail-closed DB cleanup. Exact candidate SHA-256: `E534058B473722EB26E199075900A3A64FDCC96F93341A0BA3DE291391A6D924`.
- Windows PHP syntax lint: **PASS**. Read-only exact-hash supervision review: **PASS** for `E534058B473722EB26E199075900A3A64FDCC96F93341A0BA3DE291391A6D924`; review caveat: OS/API status or signaling failure cannot guarantee child termination, so that outcome must remain BLOCKED and DB cleanup must not proceed.
- Fresh read-only preflight: clone identity `phase66a-retest-20261111/site`, DB `zmp66a_isolated`, prefix `z66a_`; sole installed/active plugin `zmp-competitions`; schema `3`; baseline `1 user / 1 credential / 0 challenges / 0 proposals / 0 proposal events`; DB check PASS. All 67 first-party source hashes match the reviewed current phase manifest. Port 8101 is bound as expected; 8102 is free; private clone MariaDB PID 3828 is present.
- Recovery backup attempt exited `1` without diagnostic output. Backup existence, completeness, and permissions are **NOT VERIFIED**. Fixture was not copied or invoked; no application DB write, service change, process signal, or browser action. Preserve prior runtime FAIL.
- Work is paused pending the Owner's instruction on read-only inspection/removal of any partial backup artifact and a single diagnostic retry. Phase remains **IN PROGRESS / NOT_READY**.


## 2026-11-12 — Concurrent route replay exact-hash runtime
- Current fixture `tests/phase66a-president-step-up-concurrent-route-replay-diagnostic-v2.php` SHA-256 `E534058B473722EB26E199075900A3A64FDCC96F93341A0BA3DE291391A6D924`; exact-hash static supervision review PASS; isolated runtime PASS, exit 0. Two workers, route matrix, worker exits, proposal/challenge/counter, cleanup, artifact removal and DB integrity all passed.
- Initial invocation guard refusal was `writes=0` due a missing fixture-path argument; corrected call supplied it. No application writes occurred on the refused call.
- Metadata-only inspection found no identifiable partial backup artifact; two existing private artifacts were preserved. One export retry succeeded: 251,395 bytes, mode 600. Backup contents/digest were not read or recorded.
- Detailed report SHA-256 `587EE76054AF6FEC3FB8B97932B264E1EC7696A73EE000B7D949F3C490AA378A`. Earlier FAIL/BLOCKED evidence is preserved as historical for the preceding fixture/command. Phase remains IN PROGRESS / NOT_READY.

## President first-time teacher profile reports a save/readback error with blank phone — 2026-11-12
Expected flow: `ZMP_Support::save_user_profile_fields()` stores the profile metadata, reads the latest usermeta row, verifies the stored value, then continues to create the primary-school/subject links inside the transaction. A blank contact phone is allowed. WordPress `wpdb::get_var()` returns `NULL` for an empty-string result, so using it for both value and row-existence checks falsely reports that a blank `zmp_phone` row is missing. The candidate now uses `get_row()` and checks row presence, `meta_value` property presence, database error and exact string equality separately.

Safe diagnosis: use only the isolated synthetic clone; record the metadata key and whether the row was missing/mismatched or the query failed. Never log the phone, school, email, user ID, nonce, credential or passkey data. Do not retry a failed browser save until the saved state is checked. Current focused code review and exact-hash isolated setup fixture are PASS; browser form submit/readback remains NOT RUN. See `reports/PRESIDENT_TEACHER_PROFILE_EMPTY_PHONE_RUNTIME_1.2.4.66.a_2026-11-12.md`.
## Expired-challenge cleanup failure behavior — 2026-11-12
- Clone-specific fixture A68FE9220B845EDB333862F15316A62C19698E6B5C33C8319DF0EDD37AC0E30F passed one exact-hash isolated WordPress runtime attempt, exit 0. It intercepted the precise expired-row cleanup query once, returned the fixed generic HTTP 400, created no new challenge or logged-in session, restored the owned challenge and in-memory state, and passed cleanup/DB-integrity checks.
- Fresh private mode-600 clone backup and live preflight passed. Post-run baseline: one synthetic user, one credential, zero challenges; DB check PASS. Staged clone fixture removed. UI server PID 25642/8101 and clone MariaDB PID 3828 remain running; 8102 remains free.
- Static review SHA FD12A35B68A921CD1ACC8A2A7C80D9478F54DF047D9F91FED70B95DE691643AB; runtime report SHA 5CB84F082C3C526A8D5713994F632C1AB5664DA1D9564AB41B5AFBDADA2B082A. Earlier no-run/preflight blockers are preserved as historical and superseded for the current fixture run. This is only the failure-path behavior; normal cleanup timing and phase/release acceptance remain open.
## President subject-decision passkey failure — current diagnostic guide — 2026-11-12
Expected flow: submit one of the three decision buttons; the same-origin options request returns a context-bound challenge; the browser invokes `navigator.credentials.get()`; a successful assertion is serialized and then the decision form is submitted to `admin-post.php`. The server verifies the bound challenge/assertion and only then records the decision.

Current observation: the existing local Edge page shows “Проверката со passkey не може да се заврши. Одлуката не е испратена.” while the synthetic proposal remains pending. The message alone does not identify whether form preflight, the `admin-ajax.php` options request, option decoding, authenticator interaction, assertion serialization, form submission, or server verification failed. The current JS maps multiple client exceptions to the same message, and the options endpoint currently returns a generic error. Do not infer that the password, authenticator PIN, or passkey is wrong from this message.

Safe evidence, only if a relevant browser record already exists: endpoint path (`admin-ajax.php` or later `admin-post.php`), HTTP status, content type, initiator, and whether the OS passkey prompt appeared. Never share request/response bodies, headers/cookies, nonce, challenge, assertion, credential ID, PIN or password. If the prior Network record was not preserved, the exact old failure stage cannot be recovered from this generic page text. Owner-confirmed registration/login remain PASS; do not repeat that ceremony for this diagnosis. Current browser attempt is recorded as failed/no decision sent, stage unknown.


## 2026-11-24 — legacy Messenger index collision
The old .66/.66.a installer was missing the safe preflight for the historical scoped_threads index that omits 
egion_key. It now replaces only that verified non-unique index before dbDelta() and fails closed on unknown/unique definitions. Local synthetic regression passed 7/7 and DB integrity passed. Full evidence: 
reports/MESSENGER_LEGACY_SCOPED_THREADS_REPAIR_1.2.4.66_a_2026-11-24.md; runtime matches source SHA-256 5CE8A617291C0CFDE349B819DA61275965148973E43683EED6062A71DBE8E832. No production database was accessed.
