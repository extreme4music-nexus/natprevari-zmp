# 🚨 STATUS UPDATE — 2026-11-27

# 🚨 CURRENT MESSENGER SCHEMA UPDATE — 2026-11-27

**OWNER REPORT: the installed `zmp-competitions-1.2.4.66.a-2026-11-27-messenger-schema-fix.zip` still displays the Messenger schema warning on the live site. This means the local migration PASS did not establish the live root cause. The diagnostic candidate is built and listed in the release manifest; use its result before claiming the issue is fixed.**

The diagnostic build adds **„Провери ја Messenger шемата“** to the administrator Debugging page. It reports the installed plugin version, database schema marker, retry state/deadline, the first incompatible Messenger table, column, or index with expected and observed metadata, and already-recorded diagnostics from other schema prerequisites. It does not rerun other components' readiness functions because some can change Messenger retry state. It does not run a migration or change message data; it writes one privacy-filtered event to the ZMP diagnostic log. The live cause remains unconfirmed until the owner runs this check.

.66.a advances the database schema marker from .65 to .66.a so the locked upgrade path runs after installation. On the existing local :8111 clone, the legacy index was replaced, Messenger schema verification passed, a synthetic message survived migration, and cleanup completed. **The owner installed that ZIP on the live site and confirmed the warning remains; the live root cause is unresolved.** See reports/MESSENGER_SCHEMA_AUTO_UPGRADE_FIX_1.2.4.66.a_2026-11-27.md and reports/MESSENGER_LIVE_WARNING_DIAGNOSTIC_1.2.4.66.a_2026-11-27.md.


**THE 2026-11-26 STATUS BELOW IS HISTORICAL AND IS NOT THE CURRENT ACCEPTANCE.** Appointment role eligibility allows Administrator + Subscriber + Teacher and the permitted stacked ZMP roles; matrix **21/21 PASS** and full synthetic stacked appointment **PASS**. A matching grant-path `TypeError` was fixed locally. Owner installation is still needed to confirm the live-site result; this is not full phase acceptance.

# 🚨 HISTORICAL OWNER STATUS — 2026-11-26 — SUPERSEDED

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

**HISTORICAL .66.a STATUS AT THAT CHECKPOINT: ITEMS 1–5 HAD EXACT-SCOPE PASS EVIDENCE; PRESIDENT APPOINTMENT ROLE-POLICY FIX HAS FOCUSED 18/18 PASS. A FRESH TEST ZIP IS BUILT AND HASH-VERIFIED FOR THIS SOURCE. FULL CURRENT-SOURCE REGRESSION (ITEM 6), GITHUB READBACK, AND OWNER INSTALLATION (ITEM 7) ARE STILL OPEN. OLDER ZIP/FULL-REGRESSION RESULTS DO NOT APPLY TO THE UPDATED SOURCE.**

## CURRENT .66.a RELEASE NOTES AND OWNER TEST CHECKLIST — 2026-11-26

The WordPress dashboard **ЗМП Натпревари → Што е ново?** now has a dedicated `1.2.4.66.a` entry. The list below describes the actual phase features and their expected behavior; it does not claim that synthetic school or subject rows are included in the ZIP.

1. **President passkeys:** on the President's own WordPress profile, register, view, or remove that account's passkey. Registration/removal asks for the current password. The section is shown only when the account has an active President mandate or an already registered passkey. For a test, use a synthetic President account with `subscriber + zmp_teacher` and an active mandate.
2. **Passkey login:** on the WordPress login page, sign in with a previously registered passkey. The normal username/password form remains available. Confirm a failed or cancelled ceremony does not create a session.
3. **President subject decisions:** for a synthetic pending subject proposal, test approve as new, approve as alias, and reject. Each decision requires a fresh passkey. Cancel or authenticator failure must leave the proposal pending.
4. **School profile save/readback:** change a synthetic user's per-school employment fields and visibility selections, save, reload, and confirm the values persist. Only fields explicitly selected for directory visibility are shown there.
5. **Role stacking and first President appointment:** every ZMP member requires `subscriber`; the President must have both `subscriber + zmp_teacher`. Administrator and allowed ZMP roles may be combined on one member account; the mandate flow preserves existing roles.
6. **Schema failure handling:** passkey table creation/migration is checked independently. A missing or partially migrated schema must fail closed without saving a subject decision or creating an authenticated session.
7. **Privacy-safe diagnostics:** inspect Debugging events for failed passkey/profile actions. They must contain only safe event details, never a password, PIN, authenticator assertion, challenge, or request body.

Local evidence for these scopes is recorded in `reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md` and `reports/FULL_FUNCTIONAL_SECURITY_REGRESSION_1.2.4.66.a_2026-11-26.md`. The owner's installation/visual check on the official site remains separate. The ZIP contains code only; it does not copy synthetic database rows, create an active President mandate, or populate the live approved-subject catalog.

## Primary school profile can be corrected — 2026-11-27

- Existing linked Teacher/President profiles can edit the primary school and municipality instead of seeing disabled fields.
- Saving a correction updates the same primary school engagement and keeps the subject/grade assignment linked. The server continues to validate the municipality and derive the region.
- If a linked teacher profile has no primary engagement yet, the form exposes the primary subject/grade controls needed for the first save.
- Local test on the existing `:8111` clone: **5/5 PASS**, including submit/readback, role stacking, assignment continuity, and synthetic cleanup. Details: `reports/EDITABLE_PRIMARY_SCHOOL_PROFILE_1.2.4.66.a_2026-11-27.md`.
## CURRENT .65 TRANSFER ADDENDUM — 2026-11-25

The direct comparison found and transferred one additional user-facing .65 change that had been incomplete in this phase: when a valid Subscriber + Teacher account has no linked teacher record, the WordPress profile now explains to check both roles and the linked teacher record. The service still returns `teacher_profile_required`; the missing-link state is not mislabeled as a schema failure.

Verification on the existing local clone at `http://127.0.0.1:8100`: inherited-fix regression **12/12 PASS**; rendered missing-profile guidance **1/1 PASS** using a synthetic Administrator + Subscriber + Teacher account with no mentor row; PHP lint **PASS**; `wp db check` **PASS**; HTTP **200**. Synthetic test user was removed. `.66.a` source was restored in the clone after `.66.b` verification. The owner-confirmed 2026-11-24 PASS results remain final for their exact scopes.

Updated UI source SHA-256: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`. This is a scoped transfer confirmation; it does not close whole-phase acceptance or production checks.

## CURRENT PRESIDENT SELF-SETUP AND DIAGNOSTICS — 2026-11-25

A current-role regression exposed that an Administrator editing another President's first unlinked profile could trigger the President's first-time Teacher setup after President became a required Teacher role. The setup path is now owner-only for an unlinked President: the profile does not offer that self-setup form to another account, and a non-owner save is denied. The owner retains the required `zmp_president + zmp_teacher + subscriber` stack and can complete the normal setup.

The denial writes a privacy-safe Debugging event `School profile first-time President setup denied.` with `error_code=president_setup_not_owner`; no profile values, email, nonce, or request body are logged.

Current local results on `127.0.0.1:8100`: stacked-President first setup, denial for another account, form render, rollback after invalid partial input, successful form-handler save/readback, collision no-autolink, and cleanup/integrity **10/10 PASS**; inherited regression **12/12 PASS**; missing-link guidance render **1/1 PASS**. `.66.b` was tested in the same clone and `.66.a` was restored afterward. Browser-driven submission and the President passkey step-up ceremony remain separate UI scopes.

New regression fixture: `tests/phase66a-president-teacher-stacked-profile-self-setup.php`. Current shared support source SHA-256: `49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3`; Debug source SHA-256: `c3c0771ff72a35a16bb5af0f6ace079128c6c19f756929cfffa142920ada5a28`; profile UI SHA-256: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`.


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

# What's New — ZMP Competitions 1.2.4.66.a

## Inherited `.65` fixes and role correction — 2026-11-25

- Schema checks now normalize MySQL integer display widths in the mentor-school, school-profile, school-email and President/registration checks.
- Automatic forward schema upgrades now bypass the retry cooldown only when the database marker is positively read as stale; a current marker keeps normal throttling.
- An administrator who also has `subscriber + zmp_teacher` can manage only their own Teacher profile; Subscriber and Teacher remain required.
- President Teacher/profile/subject guards follow the current rule: President has both `subscriber` and `zmp_teacher`, with permitted additional member roles.
- Local evidence: inherited-fix regression 12/12, stale marker retry 2/2, partial-schema fail-closed 4/4 PASS. Report: `reports/INHERITED_65_FIXES_AND_66A_OPEN_SCOPE_1.2.4.66.a_2026-11-25.md`.
- Still open: President browser passkey step-up result and browser submit/readback of the President/Teacher WordPress Profile form. No whole-phase completion claim.

## In progress — not released
- `.66.a` is investigating passkey registration/login and one step-up path for a privileged action.
- A private credential/challenge storage foundation and pinned WebAuthn dependency are present. Registration and self-profile credential removal are implemented in the current candidate for synthetic evaluation: enrollment requires the current account owner, exact `zmp_president` role, active mandate, and current-password reauthentication; removal requires the credential owner, current password, and a dedicated nonce and remains available after demotion. This work is unreleased and pending required runtime/UI checks. Passkey login and President subject step-up are implemented in the current candidate but remain under review and functional verification.
- The current proposal keeps WordPress sessions, role/capability and resource authorization authoritative. WebAuthn will only prove control of a registered authenticator.
- Owner-reported HTTPS, REST, WP-Cron and ES256/P-256 self-tests are recorded as PASS; PHP and SQL versions are pending.

## In progress — round-2 profile and transport corrections; not released
- The profile password field no longer blocks an ordinary profile update when left blank. Passkey enrollment/removal still require the current password at the server.
- Passkey removal refuses insecure browser contexts before reading credential/password values. The server accepts only same-origin HTTPS matching configured `WP_HOME`, or loopback HTTP from an actual loopback peer; all role, nonce, session and credential-owner checks remain in force.
- These are current-candidate changes under synthetic evaluation. They do not establish successful browser authenticator registration/attestation, passkey login, President step-up.

## Task4 login interface status — unreleased, 2026-11-05
- The WordPress login page now has an implemented passkey sign-in button with accessible status feedback and login-page-only browser assets; the standard username/password form remains. The script contacts the existing same-origin anonymous passkey endpoints without an account identifier and leaves secrets/session material out of browser storage/logs.
- Focused implementer fixture PASS9 and syntax checks PASS are preliminary only; original pre-implementation RED remains preserved. Independent code review, UI review, Test Engineer verification and actual browser authenticator flow are pending. Feature remains **IN PROGRESS / NOT RELEASED**; no shipped/phase PASS claim.

## Task5 President subject decision step-up — unreleased, 2026-11-06
- The President proposal queue now requires a fresh passkey assertion for each approve-new, approve-alias, or reject decision. Challenge binding includes the active President, current session, same-origin/RP, route nonce, proposal, exact decision, and canonical alias target where applicable. Existing role, active-mandate, pending-proposal and transaction checks still authorize the write.
- The decision form obtains assertion options from an authenticated same-origin request and submits only after successful `navigator.credentials.get()`. Unsupported browser, cancellation, or transport/verification failure leaves the proposal unsubmitted. No fallback bypass is provided.
- Exact-hash static Code Review is PASS. The corrected, independently reviewed synthetic server-side matrix is **PASS (38)**, including approve-new, approve-alias, reject, and canonical-target mismatch with no-write state checks; cleanup, DB baseline, credential preservation, plugin inventory, source parity and services also passed. Earlier failures caused by invalid fixture setup remain preserved in the test history. The actual browser WebAuthn ceremony, Task5 rendered/responsive/keyboard UI review, cancellation/unavailable-authenticator browser behavior are still **NOT RUN**. The feature remains **IN PROGRESS / NOT RELEASED**; this scoped matrix does not accept the phase.

## Current-candidate status reconciliation — 2026-11-11
- All `.66.a` work remains **IN PROGRESS / NOT RELEASED**. Candidate source contains passkey registration/removal, login, and President subject step-up; no feature is claimed accepted or shipped.
- Owner-reported target-host facts are PHP 8.3.36 (`cgi-fcgi`), MySQL-compatible 8.0.41 and WordPress 7.1.4, with the supplied HTTPS/REST/WP-Cron/ES256 self-tests. These replace the earlier “PHP and SQL versions pending” wording, but are owner-reported and not independent production-host verification. The synthetic WSL PHP 8.5.4/WordPress 7.1.3 values belong to a separate earlier test checkpoint.
- Historical 2026-11-12 scope summary; current same-scope results are reconciled above. Browser step-up/profile visual paths remain separate; partial-schema failure proof and expired cleanup now have scoped PASS evidence. Browser-only step-up, route-level concurrency and phase Master/Auditor/release gates remain open.
- Enrollment failure investigation remains unresolved at runtime. Current code maps pre-password request validation rejection to HTTP 403; a wrong password after request validation maps to HTTP 400. The prior user-reported 403 is not correlated to a specific enrollment request. No additional retry or PASS claim is implied here.

## Migration runtime status — 2026-11-11
- Independent runtime report `reports/PASSKEY_SCHEMA_MIGRATION_ROLLBACK_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `7E8C21A98B5C7F279665E485E096165CF0AA200A9366FC8D019DFA3F018B1C75`: existing-v3 idempotence and exact `.65` rollback/reinstall behavior PASS at scoped runtime level. Fresh passkey schema creation has a separate scoped PASS report; partial-schema failure injection remains INCONCLUSIVE/NOT RUN. This is partial evidence, not phase acceptance.
- Current readiness follow-up `reports/GATE_READINESS_RUNTIME_FOLLOWUP_1.2.4.66.a_2026-11-11.md`, SHA-256 `E697323C98CF552C0344114AF3C40E2B0E4B2FA8E156502FCD97E532F0CDAF48`, keeps overall NOT_READY. No package/GitHub sync or phase acceptance.

## Fresh passkey schema creation evidence — 2026-11-11
- Independent Test Engineer report `reports/PASSKEY_FRESH_SCHEMA_CREATION_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `E7F4FB749950255F3226345A4556537010BF75A8612EF6F0A9C9E6A186413136`: first creation on a separate copied synthetic child DB **PASS**. `.66.a` normal plugin loading created both passkey tables, expected indexes and marker 3; compatibility and DB integrity passed; unrelated synthetic competition data stayed unchanged. The child models missing passkey schema on copied data, not a full historical `.65` database.
- Readiness follow-up `reports/GATE_READINESS_AFTER_FRESH_SCHEMA_1.2.4.66.a_2026-11-11.md`, SHA-256 `F19445728E230492298B94AEFDDBA3577721742936ECFA879B45DBE28CADAB5C`: overall remains NOT_READY. Partial migration failure injection and all browser/Master/Auditor/release gates remain open/NOT RUN.

## Partial migration failure injection attempt — 2026-11-11
- Historical 2026-11-11 attempt: reviewed DDL filter intercepted zero queries, so that attempt was INCONCLUSIVE/NOT RUN. Superseded 2026-11-25 by current local partial-schema fail-closed **PASS 4/4** in `reports/INHERITED_65_FIXES_AND_66A_OPEN_SCOPE_1.2.4.66.a_2026-11-25.md`.
- The injector was removed and the child DB restored/verified (schema marker3, compatible, both tables/indexes, baseline, no passkey rows and integrity PASS). Main clone and Owner site/services remained unchanged. Current gate report `reports/GATE_READINESS_AFTER_PARTIAL_SCHEMA_ATTEMPT_1.2.4.66.a_2026-11-11.md`, SHA-256 `549AFDE977B7906C0DA84BE9BC7BDEBFA5EABAD1335AEF950A4A3DC954339587`, overall NOT_READY.

## Verification update — 2026-11-11
Current-source synthetic login regressions: context/rate PASS7, global quota PASS5, issuance churn PASS9, transient eviction PASS4. The expired-cleanup report was later updated: the corrected clone-specific fixture remains NOT RUN after one bounded preflight failure. Do not retry that environment preflight. These checks do not establish browser behavior or release; `.66.a` remains IN PROGRESS / NOT_READY.

## Latest current-candidate acceptance status — 2026-11-11 (unreleased)
- Scoped current-hash PASS evidence: registration/management 49; valid backend login 17; President subject-decision matrix 38; login context/rate 7, global quota 5, issuance churn 9 and transient eviction 4. Separate scoped migration/rollback and copied-child fresh-schema checks PASS; hosting compatibility assessment is version/specification scope only.
- Actual passkey enrollment, login assertion UI, President step-up browser ceremony, unavailable/cancelled authenticator behavior, current profile UI review, true route-level race, partial-schema fail-closed evidence and expired-challenge cleanup remain NOT RUN or incomplete as stated in the current gate report. The expired-cleanup preflight is not to be repeated.
- Existing Edge access was refused by the current computer-use runtime; no alternate UI path was used. Enrollment runtime failure remains uncorrelated. No feature or phase is accepted/released; Master/Auditor and ZIP/GitHub hash-readback remain pending.
- Current readiness report: `reports/GATE_READINESS_CURRENT_1.2.4.66.a_2026-11-11.md`, SHA-256 `99D3FE50E993378E1A381969AC36318024037B548239F107330B1F712A3F9A83`. Detailed test outcomes remain bound to the cited test reports.




## Step-up replay concurrency fixture — prepared, not run — 2026-11-11
- New test-only fixture `tests/phase66a-president-step-up-concurrent-route-replay.php`, SHA-256 `36B7D2295145C4B255FC11731302052377484685B1E828ACFF48D7D91AD067AC`, has independent static fixture-safety PASS report `reports/CODE_REVIEW_PRESIDENT_STEPUP_CONCURRENT_ROUTE_REPLAY_FIXTURE_1.2.4.66.a.md`, SHA-256 `C01039A9254D80AE87D02490346097879C7A2588718093ADD3A8C1318668C26F`.
- The fixture is for two synthetic WP-CLI workers invoking the actual President handler with the same proof and a barrier immediately before the exact challenge-consume query. This is **not runtime evidence**; a clone-specific preparation note is still being completed. Do not run before its exact preflight/backup steps are recorded. If run and passed, the evidence remains bounded to the observed SQL-boundary rendezvous, not guaranteed DB scheduling overlap.
- Overall `.66.a` remains IN PROGRESS / NOT_READY; browser ceremonies, current UI, cleanup/partial-schema and downstream gates remain open.

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

## President teacher profile — in progress — 2026-11-12
The active President can manage their own linked teacher-school profile through the exclusive `zmp_president` role. If no mentor mapping exists, the profile page now explains that the teacher profile is not linked. Implementation and isolated-clone service verification are complete; rendered browser review remains NOT RUN. This work is unreleased and is not phase acceptance.

## Passkey browser acceptance update — 2026-11-12 (candidate, unreleased)
The Owner verified successful passkey registration and sign-in on the local synthetic .66.a site after the site's loopback origin was corrected to localhost. The PHP admin-post scope now allows only the President's own passkey options, finish, and removal actions; browser diagnostics correctly accept WordPress's serialized string flag. This is local candidate evidence, not a shipped release claim. Remaining phase gates are open.

## Owner-supplied browser evidence update — 2026-11-12
Owner reconfirmed passkey login after logout: the system requested a device PIN, the Owner entered it, and login succeeded. A desktop screenshot confirms the authenticated profile page shell. Earlier Password Manager error remains a separate attempt. This is candidate verification evidence; `.66.a` is not released and its passkey/teacher profile controls and step-up gates remain open.

## President self-service teacher-profile setup — in progress, unreleased — 2026-11-12
The Owner requirement is that a President is also a teacher and needs a teacher profile. The current candidate now lets the exact exclusive `zmp_president` role with the President dashboard capability render and submit the existing WordPress teacher-profile form for their own account when no mentor mapping exists. The Owner enters their own school and municipality through the existing form; the server derives the region and creates the `wp_user_id` mapping for the current account. It never changes roles or auto-links by email. If an existing mentor record uses the same email, setup fails closed and directs the Owner to an administrator for explicit review. Source implementation is present; the focused integration fixture has not run, the changed UI has not been reviewed, and this remains unreleased.
## President profile setup integrity/concurrency revision — 2026-11-12
Independent SPEC/QUALITY review found non-atomic first-time writes and a check-then-insert email race. Candidate revision: exact President first setup now acquires a stable per-user lock and normalized-email lock, acquired in deterministic order, rechecks only the exact current `wp_user_id` mapping while locked, rejects any remaining case-insensitive email collision without linking, and holds the lock through commit. Before writes, it verifies every affected WordPress/ZMP table is InnoDB; if not, setup fails closed. A single transaction now covers the mentor row, user-meta, primary-school/engagement, optional school, and subject/grade writes; every error path rolls back and releases the lock. No schema change or role change. Added fixture coverage forcing a post-insert validation error and checking rollback leaves no mentor, school/profile, or profile user-meta. PHP syntax checks PASS for source and fixture only. Fixture runtime remains BLOCKED/NOT RUN because the prior current-candidate guard failed; do not retry. Independent re-review requested; no behavior, UI, or release PASS claimed. Source SHA-256 `CFFB2531DEACF4986FF5C6F6E6EFDAED8F08B84C38F00574788B7D247A33F515`; fixture SHA-256 `5C2C588EE752F2038D9835522E90B070706D4F1518C3F8A5A20733402EF10736`.

## Owner review follow-up: checked mapping/meta/lock operations — 2026-11-12
Candidate hardening records: the locked exact-user mapping read now fails closed on SQL error; collision query preserves/checks that error state and compares `LOWER(TRIM(email))`; normalized-email advisory lock now covers all unmapped teacher and President first-time submissions, while President also gets stable user-ID lock. Each of four profile-meta writes is followed by a direct stored-value/error check; mismatch rolls back the first-time President transaction. Release-lock return values are checked; an unconfirmed release cannot return normal success and displays a 503 instructing the user to check saved state before retry. Fixture expected support hash is updated. PHP lint PASS only, runtime NOT RUN, independent re-review PENDING, UI OWNER ACTION REQUIRED. Current source `CFFB2531DEACF4986FF5C6F6E6EFDAED8F08B84C38F00574788B7D247A33F515`; fixture `5C2C588EE752F2038D9835522E90B070706D4F1518C3F8A5A20733402EF10736`.


## Unreleased responsive correction — 2026-11-12
The President subject-proposal decision controls now use the intended stacked full-width layout below 600 CSS px. This correction is present in the active .66.a candidate and is not released. Visual self-check PASS at 400 CSS px; exact 375×812 and independent review were pending at that checkpoint; the final 375×812 review now PASS after the cache-key correction. See 
reports/UI_REVIEW_PRESIDENT_SUBJECT_APPROVALS_MOBILE_LEAD_CHECK_1.2.4.66.a_2026-11-12.md.



## Unreleased mobile layout/cache correction — 2026-11-12
The President subject-proposal controls now render in a compact full-width vertical stack at 375×812. The admin CSS cache key uses the stylesheet file modification time so CSS changes are applied on reload. This is active-candidate work, not released. Final scoped UI PASS and exact-hash review reports are in 
reports/UI_REVIEW_PRESIDENT_SUBJECT_APPROVALS_MOBILE_CACHEFIX_1.2.4.66.a_2026-11-12.md and the linked code review reports. Decision submission/accessibility/physical-device checks remain NOT RUN.


## Current Owner-confirmed acceptance and open gates — 2026-11-12 (unreleased)
- **Owner-confirmed PASS:** the synthetic President registered the passkey and later signed in after entering the device PIN. The Owner's confirmation is accepted; the PIN is not a separate proof item. This does not require another ceremony.
- **UI Reviewer observations only:** the President proposal-decision controls and teacher-profile layout were observed at 375×812. Per Owner direction, these reports are suggestions only and carry no acceptance status or gate authority; actual UI acceptance remains to be checked.
- Historical 2026-11-12 checkpoint; superseded 2026-11-25 by current stacked-President first-time setup and handler render/save/readback **PASS 10/10**. Browser-driven submission remains NOT RUN.
- **Still open:** President step-up browser decision (Owner action requested), concurrent route replay (prior runtime FAIL; corrected fixture static review FAIL; safe rerun preflight BLOCKED because GNU timeout is unavailable), partial-schema failure proof and expired-challenge cleanup (NOT RUN), whole-phase review, Master, Auditor and release gates.
- .66.a remains **IN PROGRESS / NOT_READY / NOT RELEASED**. No ZIP or GitHub synchronization is claimed.
## Empty-phone first-setup correction — unreleased — 2026-11-12
President self-service first-time teacher-profile setup now verifies an intentionally blank phone value without confusing it with a missing database row. Exact-hash isolated functional checks for setup, access denial, collision refusal, rollback, cleanup and database integrity passed. This is active `.66.a` candidate work and is not released. Runtime report: `reports/PRESIDENT_TEACHER_PROFILE_EMPTY_PHONE_RUNTIME_1.2.4.66.a_2026-11-12.md`.
## Expired challenge cleanup failure behavior — unreleased — 2026-11-12
The isolated .66.a candidate returned the fixed generic sign-in error when expired-challenge cleanup was forced to fail. It created no challenge or session and preserved baseline state. Exact fixture/runtime evidence: rreports/EXPIRED_CHALLENGE_CLEANUP_FAILURE_RUNTIME_1.2.4.66.a_2026-11-12.md. This is active candidate evidence, not a released feature or phase acceptance.
## Current owner-directed sprint status — 2026-11-12 20:55 Europe/Skopje
- Active phase `.66.a`: **IN PROGRESS / NOT_READY**.
- Preserved PASS: Owner-confirmed passkey registration/login; first-time President teacher-profile setup runtime (`49A9F765F45AD67546096309E340AEB629A9679D1B71565EDC83DFF2FD2E2F0B`); isolated expired-challenge cleanup failure path (`5CB84F082C3C526A8D5713994F632C1AB5664DA1D9564AB41B5AFBDADA2B082A`). These are scoped, not whole-phase acceptance.
- President step-up browser attempt: **FAIL — decision not submitted**. The existing Edge UI shows the generic failure; precise stage/status remains unknown. Do not repeat passkey login or claim a root cause from the message alone.
- Historical 2026-11-12 partial-schema fixture did not intercept its target query; superseded 2026-11-25 by current local **PASS 4/4**. Browser-driven profile submission remains NOT RUN; current nonce-backed handler result is **PASS 10/10**.
- UI Reviewer output is suggestion-only and cannot assign status or gate the phase. `.66.a` remains unreleased.
- GitHub readback: private repo `extreme4music-nexus/zmp-competitions` has verified branch `version/1.2.4.64` at `b66e70ecdc78010001c270c329298e2f2cbbc920`; `version/1.2.4.65` and `version/1.2.4.66.a` refs were not found. Do not claim `.66.a` sync; preserve the missing `.65` remote lineage as a provenance gap if a `.66.a` branch is prepared.
- This is an active status correction; dated historical entries remain historical. No release artifact or sync is claimed by this note.


## 2026-11-26 — final local regression and owner candidate package

- The seven-item checklist now has PASS for items 1–6. The President subject decision matrix completed 38 checks on the existing local clone, including positive route outcomes, negative assertion/context checks, no-write behavior, and cleanup. The exact-hash concurrent route-replay V2 fixture remains PASS.
- Candidate ZIP `zmp-competitions-1.2.4.66.a-2026-11-26.zip` was rebuilt from the phase-folder sources. Archive integrity and every package-to-phase file hash passed. `LICENSE.md` is included; third-party vendor terms remain intact.
- Item 7 also passed: the ZIP and restricted license were synced and read back from GitHub on both `main` and `version/1.2.4.66.a`. Owner installation validation is next.
