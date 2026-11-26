# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

## CURRENT .65 TRANSFER ADDENDUM — 2026-11-25

The direct comparison found and transferred one additional user-facing .65 change that had been incomplete in this phase: when a valid Subscriber + Teacher account has no linked teacher record, the WordPress profile now explains to check both roles and the linked teacher record. The service still returns `teacher_profile_required`; the missing-link state is not mislabeled as a schema failure.

Verification on the existing local clone at `http://127.0.0.1:8100`: inherited-fix regression **11/11 PASS**; rendered missing-profile guidance **1/1 PASS** using a synthetic Administrator + Subscriber + Teacher account with no mentor row; PHP lint **PASS**; `wp db check` **PASS**; HTTP **200**. Synthetic test user was removed. `.66.a` source was restored in the clone after `.66.b` verification. The owner-confirmed 2026-11-24 PASS results remain final for their exact scopes.

Updated UI source SHA-256: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`. This is a scoped transfer confirmation; it does not close whole-phase acceptance or production checks.

## CURRENT PRESIDENT SELF-SETUP AND DIAGNOSTICS — 2026-11-25

A current-role regression exposed that an Administrator editing another President's first unlinked profile could trigger the President's first-time Teacher setup after President became a required Teacher role. The setup path is now owner-only for an unlinked President: the profile does not offer that self-setup form to another account, and a non-owner save is denied. The owner retains the required `zmp_president + zmp_teacher + subscriber` stack and can complete the normal setup.

The denial writes a privacy-safe Debugging event `School profile first-time President setup denied.` with `error_code=president_setup_not_owner`; no profile values, email, nonce, or request body are logged.

Current local results on `127.0.0.1:8100`: stacked-President first setup, denial for another account, form render, rollback after invalid partial input, successful form-handler save/readback, collision no-autolink, and cleanup/integrity **10/10 PASS**; inherited regression **11/11 PASS**; missing-link guidance render **1/1 PASS**. `.66.b` was tested in the same clone and `.66.a` was restored afterward. Browser-driven submission and the President passkey step-up ceremony remain separate UI scopes.

New regression fixture: `tests/phase66b-president-teacher-stacked-profile-self-setup.php`. Current shared support source SHA-256: `49fa8ffd590efd1bb26d51cbe7ad000d33cff807f8260395580243293079e1e3`; Debug source SHA-256: `f6850be2957ccc285736b7b1b9483c9ce633270ae725f24e09ec21bba95dac6b`; profile UI SHA-256: `b845de01e03ecb68eb634ccb1ed460220340cecf4722bdd64fdf9e35c68d9475`.

## CURRENT OWNER VIDEO-DELIVERY DECISION — 2026-11-25

The Owner has selected plugin-managed upload to restricted Google Drive as the direction for new entry-video submissions. This supersedes the prior YouTube upload/Unlisted/processing acceptance target for the new submission flow. Do not keep the historical YouTube upload failure as a current requirement to retry. Previous YouTube results remain historical evidence only. The replacement Drive flow is documented but is NOT IMPLEMENTED OR TESTED yet; its upload, compression, thumbnail, access, and no-refresh progress criteria are the current pending work.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

## CURRENT RECONCILIATION — 2026-11-25

`.66.b` now includes the `.65` stale-schema retry correction and the Administrator + Teacher profile authorization correction. Its existing schema validators already normalize integer display widths and retain richer diagnostics. President profile/subject guards now require President + Teacher + Subscriber. Exact local results: inherited-fix regression **11/11 PASS**, stale-schema retry **2/2 PASS**, partial-profile-schema fail-closed **4/4 PASS**, DB integrity PASS. See `reports/INHERITED_65_FIXES_AND_66B_OPEN_SCOPE_1.2.4.66.b_2026-11-25.md`.

The owner-confirmed 2026-11-24 passes remain final for their exact scopes. The owner-selected Drive video submission and jury thumbnail flow remains **DOCUMENTED / NOT IMPLEMENTED OR TESTED**. Production Messenger status and browser passkey ceremonies remain separate checks; these local scoped passes do not close them. Phase is still not complete.

---
# Project state — ZMP 1.2.4.66.b

## Current reconciliation — 2026-11-25

- Approved school-email contact disclosure passed 7/7 on current runtime: requester-bound nonce disclosed only the reserved contact and recorded a non-sensitive audit event; other Teacher and invalid nonce were denied without event. Exact cleanup, role/debug restoration and DB check passed. See reports/SCHOOL_EMAIL_APPROVED_DISCLOSURE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md.
- Changed official email after approval was also tested: the old approval was invalidated, stale disclosure denied, hash/epoch cleared, and contact_changed recorded without either address; exact cleanup and DB integrity passed.
- School-email decision denial matrix passed 5/5 on current source/runtime: ordinary Teacher, President role without active mandate, and invalid request-bound nonce were denied with no mutation; exact cleanup, diagnostic-option restoration and DB check passed. See reports/SCHOOL_EMAIL_DECISION_DENIAL_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md.
- Communications rollback on a forced linked-post insert failure passed before cleanup; announcement/notification/post totals stayed unchanged, no email was attempted, and DB integrity passed. Linked-post and partial notification-insert rollback both pass. See reports/COMMUNICATIONS_POST_FAILURE_ROLLBACK_1.2.4.66.b_2026-11-25.md.
- Current-hash President appointment, scheduled cancellation and active revocation callbacks passed on the existing local 8103 runtime; all synthetic fixtures were removed, existing mandate 58/user 116 was unchanged, and wp db check passed. Evidence: reports/PRESIDENT_MANDATE_CURRENT_HASH_RUNTIME_1.2.4.66.b_2026-11-25.md.
- Active plugin source matches the candidate 69/69. temporary-plugin/zmp-entry-youtube-upload is documented as temporary .66.a test add-on; the feature is integrated in the .66.b main plugin, so it is excluded from official release package.
- The appointment route's subscriber/valid-nonce and administrator/invalid-nonce denials passed without mutation; other mandate denial variants and admin-browser visual checks remain open. This scoped PASS does not close the phase.


## Historical status snapshot — 2026-11-18 (superseded where later PASS evidence exists)

.66.b remains IN PROGRESS / NOT_READY. The Owner's current instruction is to close all inherited unfinished work and current phase gates; earlier scoped pauses do not narrow that scope. Current local PASS evidence includes the jury passkey step-up/replay checks, teacher-profile HTTP save/readback, 119-route unauthorized callback matrix and missing-GET-default fix, .59 certificate issuance/revocation concurrency, .48 matrix multipart upload and mocked playback/readback, .56 scheduled mandate appointment/cancellation/active-revocation handlers, the YouTube resumable-helper mock, President governance service authorization/decryption and HTTP document download, the moderator delivery-mode render/save/readback, Communications draft creation, and Role Messenger moderator-thread send/notification. Each synthetic test was cleaned and local DB integrity passed where applicable.

Historical 2026-11-18 open-item snapshot: several listed paths have since gained focused PASS evidence dated 2026-11-24/25, including Communications publish/post-sync, Messenger reply/attachment/limited-review scopes, and school-email route cases. Do not use this old list as current for those exact scopes. Current remaining items and their scope are recorded in the 2026-11-25 reconciliation above and in later dated reports.

The `.66.b` YouTube registration candidate now moves its UI initializer into a versioned asset and keeps the category-specific slot section visible while requiring a moderator to configure the regional mode before file selection. PHP and JavaScript syntax pass; this change is not live-installed or browser-verified yet. See the 2026-11-18 work-log and debugging entries.

The earlier .66.b state bullets below preserve their dated checkpoints and should be read as history where later evidence above or in the dated reports supersedes them.



- Status: IN PROGRESS / NOT READY. The Owner-selected jury final-result passkey step-up is implemented in source; live functional verification, UI review, independent review and phase acceptance remain open.
- Active folder: `ZMP_PROJECT_LIBRARY/03_PHASES/zmp-competitions-1.2.4.66.b/`.
- `.66.a` remains IN PROGRESS / NOT_READY; Auditor decision NOT ACCEPTED. Its ZIP/GitHub records and unfinished evidence are preserved. No `.66.a` source/package was changed in this work.
- Historical `.66.a` carry-forward: subject-proposal browser attempt remains stage-unknown; teacher-profile submit/readback and partial-schema fail-closed were initially NOT RUN, then gained current `.66.b` local HTTP/runtime evidence in the dated carry-forward report. Whole-phase/Master and `.65` GitHub provenance remain open. Owner-confirmed passkey enrollment/login remains PASS and was not repeated.
- `.66.b` selected action: finalizing a jury result. The browser flow now requests passkey proof and binds the challenge to the current user/session/origin, category-jury nonce, season, category, entry, phase, and normalized score/qualification/basis/rationale payload. Existing role and assignment checks still govern authorization; a passkey grants no role.
- Enrollment eligibility was extended to active President, administrator, and commission users assigned in the active season. Registration options and completion both enforce this eligibility.
- Changed source: `includes/class-zmp-category-jury.php`, `includes/class-zmp-passkeys.php`, `assets/js/zmp-president-subject-stepup.js`, `admin/class-zmp-passkey-profile.php`. Before-edit reference copies from `.66.a` are in `reports/source-originals-before-jury-stepup/`; they are development-only and must not enter a ZIP or GitHub.
- Syntax checks: PHP lint PASS for all three changed PHP files; Node syntax check PASS for the changed JavaScript. These are syntax-only results.
- Historical opening checkpoint: functional browser/database flow was NOT RUN at phase opening. Current `.66.b` local HTTP/runtime passkey and database evidence is recorded below; browser authenticator and broader route/UI gates remain open.
- UI review, independent code review, independent functional testing, whole-phase review, Auditor: NOT RUN.
- Package and GitHub synchronization: NOT RUN.
- Test environment: the existing synthetic `.66.a` WordPress at `127.0.0.1:8101` and its MariaDB were left running and unchanged. Production was not accessed.
- Next safe action: verify `.66.b` in a phase-isolated synthetic WordPress environment, including assigned commission and Administrator success, denied/no-proof and altered-payload cases, no-mutation assertions, then desktop/mobile browser behavior.

## Owner UI verification setup — 2026-11-12
- Active phase: `.66.b`, Owner-selected privileged action: finalizing a jury result. The separate synthetic runtime is `/home/tonic/zmp-local-sandbox/phase66b-test/site`, database `zmp66b_isolated`, table prefix `z66a_`; PHP PID `60347` listens on `127.0.0.1:8103`, MariaDB PID `54934` uses a private socket and `--skip-networking`.
- Candidate plugin `zmp-competitions-66b` version `1.2.4.66.b` is the sole active plugin. HTTP smoke request for the test route returned 200. The synthetic fixture has 1 entry, 1 jury assignment, and 1 submitted municipal assessment; the copied synthetic President account also has the Commission role and regional assignment. The page `zmp-66b-jury-test` contains `[zmp_commission_portal]`.
- Fixture setup initially exited before writes because the script treated `WP_User::add_role()` as returning a success value. Corrected the fixture-only guard; the setup then completed. No `.66.a` files, database, or service were modified. No UI behavior has been inferred from HTTP 200.
- The existing Edge tab was accessible but displayed `.66.a`; the UI Reviewer could not independently claim it. Per Owner direction, record only: “UI Reviewer agent failed to check — irrelevant to the project.” Owner visual and interaction checks are pending; no PASS/FAIL/BLOCKED is attributed to that agent.
- Owner check URL: `http://localhost:8103/?page_id=4&zmp_commission_tab=jury`. Capture desktop view of “Подготвени за конечна одлука” and the complete synthetic finalization form. Then use Mobile View at iPhone 13 Mini, 375×812, refresh, and capture the same form/status/button across scroll if needed. For the interaction check, use synthetic text of at least 30 characters in both required explanation fields and submit once; if the browser requests passkey/PIN, Owner confirms locally. Capture the resulting page/status, never the PIN prompt or PIN. If the page or form is absent, send a screenshot and do not submit.
- Status: isolated runtime setup PASS; HTTP response PASS; visual rendering and interaction NOT RUN pending Owner evidence. Phase remains IN PROGRESS / NOT_READY; no package, GitHub sync, or Auditor gate claimed.
## Temporary Owner login prepared — 2026-11-12
- Reset the password for synthetic user ID 116 only in the isolated database `zmp66b_isolated`; confirmed password-hash validation and `wp_authenticate()` PASS. User retains synthetic President and Commission roles. No credential value is recorded here.
- Private credential note is stored only at `/home/tonic/zmp-local-sandbox/phase66b-test/private/owner-login.txt` (mode 600). Open it from Windows using `\\wsl.localhost\Ubuntu\home\tonic\zmp-local-sandbox\phase66b-test\private\owner-login.txt`. Do not copy its contents into screenshots/chat/reports. Remove the note and reset/remove the temporary credential after Owner UI check is complete.
## Owner screenshot evidence and correction — 2026-11-12
- Owner screenshots `Screenshot 2026-11-12 232528/232531/232535.png` show the `.66.b` jury tab and synthetic finalization form in Edge. This is direct Owner-provided desktop rendering evidence. The form was visibly broken: labels, number input and textareas ran together inline. No screenshot shows Mobile View or a submitted result; browser interaction and responsive checks remain NOT RUN.
- The synthetic fixture was inconsistent: the assessment had been inserted directly while required documents were absent, so the page showed “Чека документи” beside a finalization form. This did not represent a valid assessment flow. Also, code inspection found finalization rendering and its handler lacked an explicit documents-ready guard for documents that become pending later. Added guards to hide the form while required documents are pending and reject the finalization request server-side.
- Added scoped responsive styling for the finalization form; updated only `.66.b` candidate PHP/CSS. Added a minimal theme stylesheet enqueue in the disposable test site only. Seeded three synthetic private PDF placeholders with approved reviews so the valid ready-form state is available again.
- Verification on exact current candidate: PHP syntax PASS for `class-zmp-category-jury.php`; synthetic shortcode render with approved documents shows dashboard/form; changing one synthetic review to pending hides the form and makes `documents_ready` false; restoring all three to approved shows the form again. The temporary pending status was restored. Actual passkey submission and post-submit database assertions remain NOT RUN.
- Candidate hashes: jury `972F845A4ABE62606557841FADEFFB8AAA6F6F1F8B73CC07C7CB293343ED8D12`; public CSS `6B0479DF45B70BDDC99DF45C76506993BB44510BFE035CA7948BDC288A7422AB`; entrypoint `7AC6FC4C10E1EFBBBB43B0A9E0F90684495374C1F00F34D3F1F551D54504DFDF`; passkeys `25E64679A2C5CF9D6E41C1E18064EE3515DAFDF240457B317A56B352824E1B6C`; step-up JS `E42B402ACB3A1B9C3B89AFF5CDDDFF048A7B216D68B7EA0310FE44B7619DB0F9`; profile `A2423698B3709CE32EC7E5863833AB2978659C61E4F1BA70C49B6C9A15F6F9D9`.
- Next: Owner hard-refreshes `http://localhost:8103/?page_id=4&zmp_commission_tab=jury` in the same Edge tab. Confirm form labels/controls are vertically separated on desktop; then inspect iPhone 13 Mini 375×812 in Mobile View and send screenshot evidence. Do not submit yet if the form or document-ready status is wrong.
## Owner theme and service cleanup — 2026-11-12
- Owner screenshot `Screenshot 2026-11-12 233814.png` confirms the `.66.b` jury page and reveals inline correction/disqualification controls. Added responsive layout rules for those controls as well as the finalization form.
- Copied the `Music Class` theme (version 0.2.5) from Owner-provided clone `Z:\Documents\Natprevari_ZMP\WSL\ZMP-Site\site\wp-content\themes\music-class` into the disposable `.66.b` test site and activated it. Removed the temporary `zmp-ui-harness` theme. The Owner clone itself was not modified.
- Verified the exact test page returns HTTP 200 (36,279 bytes), the Music Class stylesheet and plugin public CSS both return HTTP 200, and a synthetic WordPress shortcode render as user 116 shows the jury dashboard and finalization form with approved synthetic docs. PHP syntax check for the changed jury class PASS. These checks do not verify post-change Edge/mobile rendering or passkey submission.
- Stopped the old `.66.a` PHP web server PID 25642 on port 8101 and verified the process ended and port is free. Preserved `.66.a` MariaDB PID 409 and its database unchanged. Current `.66.b` PHP server PID 60347 remains on 8103; `.66.b` MariaDB PID 54934 remains on its private socket.
- Current candidate SHA-256: jury PHP `972F845A4ABE62606557841FADEFFB8AAA6F6F1F8B73CC07C7CB293343ED8D12`; public CSS `D6AEE4B03164E179309445ADEE4B30C6A1A6BB23630A993CC0268367BA56D803`.
- Next: Owner hard-refreshes `http://localhost:8103/?page_id=4&zmp_commission_tab=jury` in the same Edge tab and checks the assessment-correction/disqualification and finalization forms on desktop and Mobile View at 375×812. Browser screenshots after these changes and final-result submission remain NOT RUN.
## Latest Owner evidence checkpoint — 2026-11-12 23:48 Europe/Skopje
- Owner confirms the passkey prompt appeared and was successfully completed during final-result confirmation. Treat this explicit Owner confirmation as PASS evidence for the browser step-up interaction; PIN entry itself is not separately recorded or required as an agent-verifiable artifact.
- Owner screenshots `234809`, `234815`, `234818`, `234821`, `234824` show the final result confirmed, the approved document statuses, and the current jury UI. Owner accepts the current Desktop UI: PASS. Mobile View: NOT RUN (no mobile capture in this evidence set).
- Read-only isolated DB check found exactly one scoped confirmed result: average 92.50, final 100.00, first award, actor 116; one `result_confirmed` audit event. WordPress DB check: PASS, all tables OK.
- Supersedes earlier NOT RUN for this browser submission, persisted result/audit, and current desktop rendering. Other route/authorization/replay cases, responsive Mobile View, independent code/functional/whole-phase reviews, package, GitHub sync and Auditor remain NOT RUN. Phase stays IN PROGRESS / NOT_READY.

## Owner Mobile View review — first iteration — 2026-11-12 23:52 Europe/Skopje
- Owner screenshots show responsive emulation at 375×679. Findings: tab navigation is clipped; dense tables are hard to scan and expand vertically; filter fields have excessive spacing. Mobile UI is NOT YET ACCEPTED.
- Applied a scoped stylesheet correction in `.66.b` for wrapping tabs, compact single-column filters, and readable horizontally scrollable jury tables. Awaiting Owner screenshot after refresh; this is not a PASS.
- Desktop UI and finalization/passkey evidence remain PASS as documented above. The confirmed synthetic result and audit record were not changed.

## Owner mobile follow-up: runtime stylesheet synchronization — 2026-11-13 00:02 Europe/Skopje
- Owner screenshots `Screenshot 2026-11-13 000154.png` through `000216.png` still showed the first mobile layout: clipped tabs and widely spaced filters. Comparison found the isolated runtime was serving the older stylesheet hash `D6AEE4B0…A56D803`, while the updated candidate was `83E51585…DA4345F`; the previous CSS edit had not yet been copied into the active test installation. This explains why the screenshots did not show the intended correction.
- Copied only `.66.b/assets/css/zmp-public.css` to the isolated `.66.b` WordPress plugin installation. SHA-256 now matches exactly: `83E51585D1CAA98B3D6E820660101A55D82F5003E31FC4AEA1A478DB6DA4345F` on both source and runtime. No PHP, database, result or audit row changed.
- The newly synchronized CSS has not yet been observed in the browser. Mobile acceptance remains NOT YET ACCEPTED / verification NOT RUN. Next action: Owner hard-refreshes the same Edge responsive tab at 375×679 and sends screenshots; do not resume other phase work until Owner accepts the mobile UI.

## Owner acceptance — Mobile View — 2026-11-13 00:13 Europe/Skopje
- Owner screenshots `Screenshot 2026-11-13 001013.png`, `001018.png`, `001021.png`, `001024.png`, `001027.png`, `001030.png`, `001035.png`, `001039.png`, `001044.png`, `001047.png`, and `001051.png` show the updated Edge responsive view at 353×679. Tabs wrap, filters are compact, and long tables expose horizontal scrolling.
- Owner explicitly accepts the design and prefers horizontal scrolling to avoid excessive vertical page scrolling. Record Owner mobile UI acceptance as PASS at the shown viewport. This supersedes the prior NOT YET ACCEPTED mobile iteration status.
- The confirmed synthetic result and approved document indicators remain visible across the supplied captures. The runtime stylesheet and candidate stylesheet were hash-matched before this review. No data/result/audit change occurred in this UI-only acceptance.
- This acceptance covers the Owner-selected mobile viewport and UI appearance. It does not close unrelated `.66.b` functional/security/review/release gates. No package, GitHub sync, or Auditor acceptance is claimed.

## Privileged-result payload binding correction — 2026-11-13
- Static code review identified that `qualified_next_stage` was bound as true only for exact `'1'` but persisted using PHP `!empty()`, which would treat `'on'` as true. Corrected `class-zmp-category-jury.php`: both the AJAX step-up-options route and final POST now use one strict normalizer; missing checkbox maps to 0, exact `'1'` is accepted only when a next stage is available, and malformed/unavailable values are rejected. The normalized integer is used both in the challenge payload digest and database result row.
- Added a user-facing `qualification_invalid` status explaining that no result was saved. Candidate source SHA-256: `2D8E2DA816D8E5CCF15E553010F588F40E0E2055DB5D021E1427A715080E789D`; isolated runtime copy matches exactly. PHP syntax lint: PASS.
- Targeted runtime check of `'on'` rejection/checked-value normalization and no-mutation behavior: NOT RUN. An attempt to launch a temporary WP-CLI verification script was rejected by command execution policy before the script was created or invoked. No fixture/result/audit state changed in that attempt. Do not count the prior positive owner flow or prior no-proof run as validation of this updated handler hash; retain them historically and rerun only affected checks when the runtime operation is available.
- The code-review finding has been corrected; exact-hash independent re-review is pending. Mobile and desktop Owner UI acceptance remain PASS. Phase remains IN PROGRESS / NOT_READY; no package/GitHub/Auditor claim.

## Qualification checkbox normalization follow-up — 2026-11-13
- Follow-up exact-hash review found the browser submits `'0'` for an unchecked or absent checkbox. The normalizer now accepts missing/`'0'` as 0, accepts exact `'1'` only when a next stage exists, and rejects all other supplied values. Both step-up options and finalization call this helper; binding and database persistence use the same returned integer.
- Focused read-only code re-review on exact candidate hash `3710600BDBE4B3B3506F4DB3FA99BB5F85F4824008EDEF4EBC7ACCDE0E7A1150` confirmed the mismatch is closed across PHP and the JavaScript checkbox values. This is a bounded review only, not whole-phase approval.
- PHP syntax lint PASS. Runtime source copy matches exact candidate hash. Isolated dynamic normalization/no-mutation verification remains NOT RUN because command execution was rejected before a script was created or invoked. The prior Test Engineer no-proof case is preserved at its earlier hash and does not validate this handler revision; rerun affected runtime checks when executable.
- No result/audit data changed. Owner Desktop and Mobile UI acceptance remain PASS. Phase remains IN PROGRESS / NOT_READY.

## Route inventory and enrollment eligibility follow-up — 2026-11-13
- Added static inventory `reports/PRIVILEGED_ROUTE_INVENTORY_FOLLOWUP_1.2.4.66.b_2026-11-13.md` for President proposal decisions, teacher/President profile writes, document review/correction upload, and sampled support AJAX routes. It also identifies route families still not traced; this is not a complete route matrix or a runtime PASS.
- Code review found regional-only commission assignments authorize regional jury work but were not eligible for passkey enrollment. Updated `ZMP_Passkeys::can_register_for_user()` to consider valid exact regional assignments in the active season when regional migration is complete. PHP lint PASS and source/runtime hashes match. Exact-hash focused re-review confirms the correction and fail-closed behavior. Dynamic regional-only enrollment remains NOT RUN.
- Qualification binding correction remains in place: missing/`'0'` maps to false, exact `'1'` requires a next stage, and binding/persistence share the normalized integer. PHP lint and focused exact-hash review PASS. Current isolated dynamic checks for the revised handlers remain NOT RUN due to command execution refusal before code ran.

## Additional static route traces — 2026-11-13
- Admin audio catalog and circular invitation routes were added to the static inventory. Audio create/review/archive actions are admin-only and exact-item scoped; daily audit runs from WP-Cron. Circular draft/approval/send uses moderator/admin authorization and exact IDs/state; invitation send calls `wp_mail` and was not executed because real email is prohibited. Public print is read-only and bearer-token scoped.
- This is source mapping only; no runtime authorization check or PASS is claimed. No extra step-up requirement inferred.

## Owner-run read-only verification — 2026-11-13
- The Owner ran `reports/owner-readonly-stepup-check.php` using WP-CLI against the isolated `.66.b` site. Owner-provided output: `unchecked_missing_maps_to_zero=PASS`; `explicit_zero_maps_to_zero=PASS`; `on_is_rejected=PASS`; `true_is_rejected=PASS`; `one_matches_next_stage_availability=PASS`; `zero_and_one_have_distinct_bindings=PASS`; `next_stage_available=yes`; `regional_only_enrollment=NOT_RUN_NO_ELIGIBLE_SYNTHETIC_FIXTURE`; final WP-CLI line: `Success: Read-only checks completed. No passkey prompt or database mutation was used.`
- Record the six normalization/binding checks as Owner-run PASS evidence for the currently reviewed handler logic. The command output and script report no database mutation. This verifies the specific read-only normalization/binding cases; it does not replace full finalization route testing or whole-phase review.
- Regional-only enrollment remains NOT RUN because no eligible synthetic fixture exists. No fixture was created. Preserve this as an unrun case, not a failure.
- Script SHA-256 is recorded in the session snapshot. Phase remains IN PROGRESS / NOT_READY; package, GitHub hash synchronization, remaining functional/security coverage, independent phase reviews, and final acceptance remain open.

## Regional-only passkey eligibility — focused synthetic runtime check — 2026-11-13
- The earlier `NOT_RUN_NO_ELIGIBLE_SYNTHETIC_FIXTURE` described the state before this test; it was not a plugin failure. A temporary synthetic `zmp_commission` user with no ordinary category assignment and one valid regional assignment was created only in the isolated `.66.b` DB, then removed by the test cleanup.
- Current candidate and active runtime hashes matched before the check: `class-zmp-passkeys.php` `A39B1417048BD7F6D753F6C3B094897292C8F3D0A3E4CBD53530A6A8F7330CB8`; `class-zmp-category-jury.php` `3710600BDBE4B3B3506F4DB3FA99BB5F85F4824008EDEF4EBC7ACCDE0E7A1150`.
- Owner-run WP-CLI output: `regional_only_fixture_has_no_ordinary_assignment=PASS`; `regional_only_assignment_recognized=PASS`; `regional_only_without_assignment_rejected=PASS`; `fixture_cleanup=PASS`; final success stated the temporary synthetic user and assignment were removed. A follow-up read-only query found `leftover_test_users=0`.
- Record the regional-only enrollment eligibility predicate as focused runtime PASS for these exact source hashes. The check did not create or enroll a passkey, did not invoke a browser prompt, and does not replace enrollment-ceremony, full route-matrix, or whole-phase verification. Script: `reports/owner-regional-enrollment-runtime-check.php`.
- Phase remains IN PROGRESS / NOT_READY; remaining functional/security coverage, independent reviews, release artifacts, source-hash GitHub readback, and final acceptance remain open.

## Owner-approved synthetic email testing — 2026-11-13
- Owner explicitly clarified that synthetic email is permitted in the isolated local WordPress/database environment; the prohibition remains on real external email. The root `AGENTS.md` now records this scope: use reserved fake recipients and intercept via WordPress `pre_wp_mail` or a verified loopback-only catcher, with no external relay.
- No Mailpit/MailHog process or conventional local SMTP/capture port was found in the WSL environment. Used the in-memory WordPress `pre_wp_mail` filter instead. PHP lint PASS; WP-CLI output: `Success: Synthetic wp_mail captured in memory before transport; no SMTP/real email was used.` No real message or outbound SMTP was sent.
- This proves that synthetic messages can be safely intercepted before transport. The circular-invitation plugin route itself remains NOT RUN; its route, state transitions and message construction still need a synthetic end-to-end test using the same interceptor.
- Static route inventory was corrected to reflect the Owner's permission and remains hash `24310A874FC173F7E9C2A6DD05BCD86A867FA33CA00D4B0473B6A19D6C0581FC`. Synthetic invitation testing may proceed locally; no real recipient is authorized.
- Owner is available to perform/confirm UI steps for the YouTube assignment/upload route when that check is reached. No YouTube route has been tested yet.

## Synthetic circular-invitation send handler — Owner-run runtime — 2026-11-13
- Owner authorization for synthetic local email is recorded in root `AGENTS.md`; real outbound email remains prohibited. Candidate and active runtime `class-zmp-circular-invitations.php` SHA-256 matched `C2D2F944A576D92D52D34F16405B6217B389D37905F731359AE19923D927C107`.
- WP-CLI ran the actual `ZMP_Circular_Invitations::handle_send()` handler against one temporary approved synthetic campaign and one `example.invalid` recipient. The `pre_wp_mail` filter captured the call before any SMTP/PHPMailer transport and returned synthetic success.
- Owner-run output: `synthetic_recipient_captured_before_transport=PASS`; `send_handler_completed_campaign_and_token_state=PASS`; `exact_fixture_cleanup=PASS`. The send handler recorded its internal result as sent, but no email was delivered. Temporary campaign and recipient were deleted by shutdown cleanup. No real recipient, message delivery, or external SMTP was involved.
- The PHP lint was PASS. WP-CLI emitted an expected redirect warning because the handler redirects/exits; shutdown verification still completed. This check covers the send handler and mocked transport, not draft save/approval routes or actual delivery.
- Script: `reports/synthetic-invitation-send-route-check.php`. Phase remains IN PROGRESS / NOT_READY; other privileged routes and whole-phase/release gates remain open.

## Synthetic invitation authorization denial — 2026-11-13
- Read-only WP-CLI check against the isolated `.66.b` site used synthetic Teacher user 117 and invoked the authenticated invitation-send action with a nonexistent resource ID. The handler rejected the request with HTTP 403 before nonce/resource processing; no database fixture or email was created.
- `php -l` PASS. The report script in the phase folder and the WSL execution copy have the same SHA-256: `544E08935A3F5A57AC1F6488C06EE1ACC26AF16B117885560AD01B5E7EDF9643`.
- This proves the sampled send-route role denial only; it does not complete the invitation draft/approval flow or the full route inventory. Phase remains IN PROGRESS / NOT_READY.

## Current-hash no-proof finalization guard — 2026-11-13
- Candidate and active `.66.b` runtime `class-zmp-category-jury.php` SHA-256 matched `3710600BDBE4B3B3506F4DB3FA99BB5F85F4824008EDEF4EBC7ACCDE0E7A1150` before invocation.
- WP-CLI exercised `ZMP_Category_Jury::handle_post()` for the existing synthetic confirmed result, with valid scoped nonce and no step-up proof. Observed redirect `step_up_required`; post-call shutdown checks confirmed the exact result row hash and scoped confirmation-audit count were unchanged. Output: `current_hash_no_proof_redirect=PASS`; `result_and_confirmation_audit_unchanged=PASS`; `current_hash_no_proof_finalization=PASS`.
- `php -l` PASS. The retained report helper SHA-256 is `93F089E176FF683F9FD88BDE92DD6043EAF728999BAA4764B4EBC8E559AA5F65`; the temporary WSL invocation copy was `4731E862FF10FB7541CC4BA2B24E67DD8367979867B8B33CA8BAF52FD8073282`. These are recorded separately because their byte hashes differed; do not treat them as byte-identical. Temporary WSL copy was removed after the run.
- This is only the current-hash no-proof denial/no-mutation case. Successful proof on current hash, altered-binding/replay, broader role/scope, and whole-phase gates remain open. No passkey prompt was used and no result/audit mutation occurred.

## Synthetic invitation route denial matrix — 2026-11-13
- Candidate and active runtime `class-zmp-circular-invitations.php` hashes matched `C2D2F944A576D92D52D34F16405B6217B389D37905F731359AE19923D927C107`.
- Isolated WP-CLI role matrix for synthetic Teacher user 117 invoked save-draft, approve, and send admin-post actions. All three returned HTTP 403 before nonce/resource handling. Counts for invitation and recipient tables were unchanged; `mail_transport_calls=0`. PHP lint PASS. No fixture or email was created.
- Output: `admin_post_zmp_save_circular_invitation_teacher_denied_403=PASS`; `admin_post_zmp_approve_circular_invitation_teacher_denied_403=PASS`; `admin_post_zmp_send_circular_invitation_teacher_denied_403=PASS`; `invitation_and_recipient_counts_unchanged=PASS`; `mail_transport_calls=0`; WP-CLI success.
- Report helper hash `CD6BE45C6A3BDF5708F3949BDE2BAA0DE2DDFD7618DDDAF832C11DDE1D35EE63`; temporary WSL invocation copy hash `8056156B9BFBDF0607671FE402E66947DAAB3A20E83690534DA8F2703844A777` (different bytes; separately recorded). Temporary copy removed.
- This is a role-denial matrix only. Authorized moderator save/approval, exact-resource behavior, and whole-phase/release gates remain open.

## President mandate route role-denial check — 2026-11-13
- Candidate and active runtime `admin/class-zmp-president-mandate-admin.php` hashes matched `28F0B464263A4A69FA3D8E8DDCA85D53F12AF3BE9E7BF91C20B59EAC52BD7216`.
- Direct handler checks with synthetic Teacher user 117 returned HTTP 403 on appointment, revocation, and scheduled cancellation before nonce/service calls. No database mutation was possible. PHP lint PASS. Output: `mandate_appointment_teacher_denied_403=PASS`; `mandate_revocation_teacher_denied_403=PASS`; `mandate_scheduled_cancellation_teacher_denied_403=PASS`.
- One initial WP-CLI attempt invoked admin-post hooks that are not registered by the non-admin CLI bootstrap; it reported FAIL for all three because no callback ran. This was a test setup limitation, not a plugin denial result. The corrected direct-handler invocation above exercised the actual handlers and passed. Preserve this distinction; do not count the initial harness output as a plugin defect.
- Helper SHA-256 `307C876B08F526DA8433A1794DA8A011332C2E731CC870F735FBAA4EAAFAD1B2`; temporary WSL copy `D7174686E021DAB87BEFEDCBB3F4B221B19C66F275CAAB7534F663AB067D0F0C` (different bytes; separately recorded) removed after run. Positive Administrator mandate flows remain untested.

## Test-plan status reconciliation — 2026-11-13
- Added a dated current-status summary at the top of `TEST_PLAN_1.2.4.66.b.md`. Earlier NOT RUN entries remain historical checkpoints; later verified evidence now supersedes them as explicitly listed. No test was run by this documentation correction and no release status changed.
- Current test plan SHA-256: `D091F408C226A1AD413A0029D35EF1B9C1F8779600535D92CB97AD9016FAD9E7`.

## Synthetic environment integrity after scoped route checks — 2026-11-13
- Existing `.66.b` PHP PID 60347 on 8103 and MariaDB PID 54934 were present; no service lifecycle action was taken.
- `wp db check` returned `Success: Database checked.` with all listed tables reported `OK`. Active plugin inventory contained only `zmp-competitions-66b`.
- This is a database/environment integrity result only; it does not add functional feature acceptance. No DB or service change made by this check.

## Static route mapping — communications, role messenger, school-email access — 2026-11-13
- Read-only code-path inspection added to the route inventory. Communications publish/draft publishing require moderator/admin capability and nonce; result publication rechecks confirmed results, and writes are transactional. Role Messenger routes bind actor to current room/season/category/region and exact message/thread; attachment access also checks private file integrity. School-email requests bind exact current teacher/school relationships; President decisions recheck active mandate and pending request; disclosure rechecks approval and contact hash/epoch.
- This is static mapping only: runtime checks for these route families remain NOT RUN. No defect or additional passkey step-up requirement was inferred. The question about global moderator publication scope is recorded as a scope question, not a defect.

## YouTube integration — offline contract and mocked resumable upload — 2026-11-13
- Candidate and active `.66.b` runtime `includes/class-zmp-youtube.php` SHA-256 matched exactly: `689257D80B900280C9ED9A81B0D5C6E2393D0B5C011013FA953B948A3393672D`.
- `reports/youtube-offline-contract-check.php` SHA-256 `99538B3A7D7BC2E5EE6BEC0512E5C48C1D072A438EF800096584433F10559FDA`; executed WSL temporary copy had the same hash. PHP lint PASS. All 14 offline contract assertions PASS: fail-closed unconfigured state, category slot definitions, invalid-region handling, nonregional player behavior, privacy/processing state transitions, malformed upload-response rejection, and synthetic response parsing. No DB write or HTTP request occurred.
- `reports/youtube-mocked-transport-check.php` SHA-256 `64082985B22AED6E1CE93872F23B93C60E3166E469E30771754EDC3BF8AF896D`; executed WSL temporary copy had the same hash. PHP lint PASS. A temporary synthetic OAuth option and 256-byte synthetic file exercised token-refresh parsing, resumable session creation, encrypted session-URL storage, resume probe, and upload-chunk response parsing. All five assertions PASS; mocked HTTP counts were token=2, session=1, probe=1, chunk=1, unexpected=0. `pre_http_request` rejected every unlisted host, so no external request was permitted.
- Cleanup evidence: `synthetic_settings_restored=PASS`; `synthetic_upload_file_removed=PASS`. Follow-up confirmed the YouTube settings option was absent and the temporary helper copy was removed. No media row, real OAuth credential, real YouTube channel, or real video was created or changed.
- Scope limitation: these are offline plugin-helper checks, not proof of Google OAuth, actual channel permissions, browser UI, live upload/processing, embed playback, or regional-juror access. Those remain NOT RUN and require Owner participation with an approved synthetic channel/clip; never use participant/private video.

## YouTube checkpoint — offline scope complete, live owner action pending — 2026-11-13
- Local YouTube helper contracts and mocked resumable upload passed: 14 + 5 assertions, with every HTTP request intercepted and unlisted destinations denied. Source and temporary executed helper hashes/outcomes are recorded in `TEST_REPORT_1.2.4.66.b.md`.
- Actual OAuth/channel authorization, browser page, real upload/processing, playback and regional-juror access remain unperformed. The isolated site has no connected channel and no YouTube media or regional-juror rows. These require an Owner-approved synthetic channel and clip; no participant/private video is allowed.
- No production contact, external API request, real account, channel or video was used. `.66.b` remains IN PROGRESS / NOT_READY; no package, GitHub synchronization, or Auditor result is claimed.

## YouTube role boundary — synthetic Teacher denial sample — 2026-11-13
- Synthetic Teacher received expected HTTP 403 on seven YouTube admin-only surfaces (admin page, settings save, connect, callback, disconnect, regional assignment, uncertain-upload clear). Settings/media/juror counts were unchanged and zero; no external call or DB mutation occurred. Exact evidence and helper hash are in the phase test report.
- Positive privileged operations and live YouTube flow remain Owner-dependent. `.66.b` status remains IN PROGRESS / NOT_READY.

## YouTube administrator setup screen — 2026-11-13
- Synthetic Administrator server-render check found the expected settings controls, callback URI, privacy warning, workflow rules, and disconnected setup hint. No settings were saved and no external request occurred. Browser rendering and live OAuth/upload remain open.

## Owner scope decision — 2026-11-13
- Earlier 2026-11-18 scope checkpoint: Owner reported previously handled non-YouTube work complete and asked not to reopen it. This checkpoint was superseded by the later same-day direction to close all unfinished inherited items and all current .66.b gates; current work follows the later direction.

## Owner OAuth setup observed locally — 2026-11-13
- Existing Edge tab on the isolated `.66.b` site displayed the successful YouTube connection notice and connected-channel status after the Owner's OAuth setup. No identifiers or secrets were retained. The temporary login document was deleted after login; the synthetic Administrator remains until test cleanup.
- No upload or additional external API request was made by the agent. Upload/processing/embed and regional-juror checks still require confirmation of a synthetic channel and non-participant clip.

## 2026-11-13 — Owner-authorized synthetic YouTube upload attempt
- Owner authorized a temporary synthetic clip upload to the connected non-synthetic channel, with Owner deletion after success. Clip: 8 s, 1280×720 H.264, no audio, SHA-256 850C211C201E2A610344003E27044FBB632721AB9042099968AAD27523FBB32C (16,248 bytes), sandbox-only.
- Connected state was visible in local UI. One call through the actual `ZMP_YouTube::upload_entry_video()` handler returned HTTP 302. Isolated media row is `session_unknown`, empty video ID/privacy, offset 0, no encrypted resumable URL. No successful upload was confirmed; no retry or clear was made.
- Read-only `channels.list` returned HTTP 200; this does not verify upload scope. Exact session-start HTTP/API error is not preserved by the current handler. Temporary loopback shim was deleted. Synthetic fixture remains draft + online regional event with uncertain row for safe diagnosis. See `TEST_REPORT_1.2.4.66.b.md`.
- No channel settings changed. YouTube upload/Unlisted/processing criterion remains unresolved; `.66.b` stays IN PROGRESS / NOT_READY.

## Owner-directed entry-video form add-on — 2026-11-13
- The reported omission is specifically the lack of video selectors in the teacher "Нови пријави" form. Core `.66.a` already has a post-creation upload flow under "Мои пријави"; it does not place video selection in the new-entry form.
- Temporary sidecar prototype exists under `temporary-plugin/zmp-entry-youtube-upload`; package is `.temp/zmp-entry-youtube-upload-1.2.4.66a-TEMP.zip`. It is not an official release and is not yet functionally accepted.
- Online regional: required categories/slots remain subject to the main plugin's existing draft/finalize gate. Physical regional: video slots are optional per latest Owner instruction. No new tables.
- PHP/JS syntax and ZIP structure PASS; authenticated UI and upload behavior NOT RUN. `.66.b` phase remains IN PROGRESS / NOT_READY.

## Owner-confirmed entry video submission — 2026-11-14
- Owner-confirmed PASS: video fields appeared in “Нови пријави”; one video was uploaded, appeared on the channel, and the application completed. Scope is limited to the event as reported; no unstated category, competition mode, privacy/processing status, or juror playback is inferred.
- Integrated the temporary feature into `.66.b` as `includes/class-zmp-entry-youtube-fields.php`, loaded by `zmp-competitions.php`. It uses the existing YouTube upload flow; no new table/schema code was added. PHP lint passed for both changed PHP files.
- The earlier isolated `.66.b` `session_unknown` row is a separate unresolved event; no retry or clear occurred. Keep both records without conflating them.
- `.66.b` remains IN PROGRESS / NOT_READY; exact-candidate UI and remaining YouTube privacy/processing/playback checks, plus phase/release gates, remain open.

## Matrix upload correction — 2026-11-14
- Owner clarified that the defect is matrix/audio upload. The supplied screenshots show the upload modal with an audio file selected and a generic required-fields/upload error. No file or form submission was performed by the agent.
- Cause identified: the UI advertised a fixed 20 MB limit while the isolated PHP server previously used `upload_max_filesize=2M` and `post_max_size=8M`; the handler also collapsed upload-size errors into a generic validation message. The specific selected file size was not available, so exceeding 2 MB is a plausible cause, not a confirmed fact.
- Candidate fix in `includes/class-zmp-matrix-catalog.php` and `assets/js/zmp-matrix-catalog.js`: advertise and validate against the effective WordPress/server limit, use the JS file modification time for cache busting, detect oversized POSTs and PHP upload errors before generic validation, and return specific messages/statuses. Source hashes: PHP `97681421B5712CF16A97DB4E4B83C8487D4DDC40431AF7A151C23CF749DBFEC5`; JS `272ECF8278C6DE91A580C5464D0C47D40395FB813A587E0314CB7FE323215DB5`.
- The exact candidate files are present in the isolated `.66.b` runtime. PHP lint PASS; JavaScript syntax check returned success; `wp db check` PASS. Edge visibly rendered the matrix picker and upload modal with the 20 MB label. No Drive request, file upload, or matrix row write was made. End-to-end Drive upload remains NOT RUN pending Owner's Drive setup and test.
- Local PHP server PID `75729` listens on `127.0.0.1:8103` with per-process `upload_max_filesize=20M` and `post_max_size=24M`; MariaDB PID `54934` remains unchanged. WP-CLI reports its own separate PHP context and still shows the default 2 MB limit; it is not evidence of the web server's process settings. The visible browser label confirms the rendered web request currently sees the 20 MB effective limit.
- Phase remains IN PROGRESS / NOT_READY. No package, GitHub sync, or Auditor result is claimed.

## Owner-requested carry-forward retest — 2026-11-18
The Owner requested retesting named historical open items locally in the .66.b source. The exact-scope results are PASS for the Messenger paths, audio-tool timestamp/required-URL paths, subject catalog schema/migration, Teacher proposal submission path with outbound HTTP intercepted, Teacher profile callback/readback, and partial passkey-schema failure injection. See reports/CROSS_PHASE_CARRY_FORWARD_RETEST_1.2.4.66.b_2026-11-18.md for exact source hashes and cleanup/restore evidence.

Historical 2026-11-18 open-item snapshot: several listed paths have since gained focused PASS evidence dated 2026-11-24/25, including Communications publish/post-sync, Messenger reply/attachment/limited-review scopes, and school-email route cases. Do not use this old list as current for those exact scopes. Current remaining items and their scope are recorded in the 2026-11-25 reconciliation above and in later dated reports.

## Local re-verification update — 2026-11-18
Jury qualification normalization has now been dynamically re-tested on the exact `.66.b` candidate, including actual handler rejection/no mutation for malformed `'on'`, the eligible exact-`'1'` branch, and payload binding consistency. The previously stale isolated runtime copy was synchronized to the candidate hash. This closes the specific normalization runtime NOT RUN item only. Full route authorization/regression, President proposal passkey/browser success, historic `.65` attribution/provenance and whole-phase/release/YouTube gates remain open. Status: IN PROGRESS / NOT_READY.

Additional route-denial samples on 2026-11-18 cover selected Communications, Messenger, and school-email POST paths for a synthetic Teacher with no state changes. These are not route-wide acceptance. The broad matrix and valid President step-up flow remain open.

## Current continuation update — 2026-11-18
- Supersedes the older dated statement that President proposal step-up success was not exercised: exact current `.66.b` options/decision routes now passed a local HTTP test with a synthetic ES256 credential; altered-decision replay was denied without a second event; all fixture state was restored and DB integrity passed.
- This does not close `.66.b` as a whole. Jury-finalization step-up success, route-wide authorization matrix, broader regression, release/review gates, and remaining roadmap criteria still require evidence. Keep phase status IN PROGRESS / NOT_READY.

## Selected jury action — server-side HTTP proof — 2026-11-18
The Owner-selected result-finalization step-up is now verified end-to-end at local HTTP level: AJAX challenge options, synthetic ES256 assertion, finalization, one audit event, and altered-payload replay denial. Exact baseline state was restored and database integrity passed. This supersedes prior “valid browser/passkey success NOT RUN” statements for server-side success only; browser authenticator prompt and route-wide regression remain outstanding. Phase remains IN PROGRESS / NOT_READY.

## YouTube recheck — 2026-11-18
Fresh synthetic video is prepared but not uploaded. A previous local test row for the same synthetic entry/slot remains `session_unknown` with no video ID; its stored file hash matches the earlier test clip. The upload handler correctly blocks retry pending manual channel check. Owner has been asked to confirm whether that previous attempt appeared. Mocked resumable transport passed with zero unexpected outbound HTTP; one offline assertion failed only because the local environment has saved YouTube settings. Do not clear/retry until the old attempt is resolved.

- Owner clarification (2026-11-18): the previously reported live-site upload worked; local HTTP 403 attempts are a local auth-harness limitation, not a product failure. No fresh live synthetic retry has occurred; the local session_unknown slot still needs channel check/resolution. See reports/CROSS_PHASE_CARRY_FORWARD_RETEST_1.2.4.66.b_2026-11-18.md.

- Owner-directed legacy reconciliation 2026-11-18: 106 explicit source hooks, 131 active runtime hooks, 102 shared; 50/50 candidate includes PHP files byte-identical to runtime; database integrity PASS. Full route authorization matrix remains open; see dated carry-forward report.

- 2026-11-18: Fixed missing query-ID defaults in the two document download handlers after route matrix exposed PHP warnings. Candidate/runtime hashes match; negative route matrix 119/119; no writes/mail/HTTP/warnings/fatals; DB check PASS. Full positive route regression remains open.

- .66.a Teacher profile browser submit/readback now PASS in current .66.b local HTTP: GET 200, POST 302, DB + rendered readback matched; fixtures/session cleaned, user unchanged, DB integrity PASS. Evidence: dated cross-phase retest report.

- .59 current .66.b President certificate lifecycle now passes: synthetic certificate registered, verified for exact facsimile, revoked, and all fixtures/logs restored; DB integrity PASS. Race B's current-hash PDF-upload pause interleaving is still open.


## .59 Race B current .66.b concurrent retest — PASS — 2026-11-18
The in-pause issuance-row assertion and current-hash concurrency interleaving are now closed. During the paused synthetic PDF upload, two isolated database reads observed zero issuance rows; after release, the certificate was archived and concurrent revocation succeeded. Exact candidate/runtime hashes matched, HTTP requests were locally mocked, cleanup restored all synthetic tables/options/files, and wp db check passed. See the dated cross-phase retest report and certificate-race harnesses.


## `.48` Matrix catalog upload carry-forward — `.66.b` local HTTP PASS — 2026-11-18
The exact current `.66.b` upload handler passed a real multipart upload to the isolated local WordPress AJAX route using a synthetic MP3 and synthetic Administrator session. The Drive/OAuth transport was intercepted locally, and its fake response yielded successful catalogue JSON and a row with matching file size/hash. Exact row/session/temp-file cleanup returned matrix table count to baseline; database check passed. Candidate/runtime matrix class hash matched. This closes current-code local handler and mocked-transport coverage; real Drive OAuth/playback remains NOT RUN. See `reports/SYNTHETIC_MATRIX_UPLOAD_1.2.4.66.b_2026-11-18.md` in `.66.b`.


## `.48` matrix playback/readback carry-forward — `.66.b` local mock PASS — 2026-11-18
The current authenticated stream endpoint returned the synthetic MP3 with HTTP 200, correct audio/mpeg headers and byte range support. Downloaded bytes matched the source SHA-256 exactly. The mock blocked all external HTTP; the synthetic row and login session were removed and baseline restored. Real Drive playback remains NOT RUN. See the `.66.b` synthetic matrix upload report.


## YouTube resumable helper — exact `.66.b` mock PASS — 2026-11-18
The current YouTube helper passed its five synthetic resumable-session/transfer assertions with 0 unexpected HTTP calls; settings and temp file were restored. Candidate/runtime class hashes match and PHP lint passed. This does not resolve the separate `session_unknown` entry or verify real-channel upload/privacy/processing. No live retry was made. Details are in the dated cross-phase carry-forward report.


## `.56` President-mandate positive handler carry-forward — `.66.b` PASS — 2026-11-18
The current `.66.b` appointment handler and scheduled-cancellation handler passed positive synthetic runtime checks with capability/nonce enforcement, exact term/status/audit state, no role escalation for a scheduled mandate, and exact cleanup. Candidate/runtime hashes matched; database integrity passed. Full evidence and reproducible helpers are in `reports/SYNTHETIC_PRESIDENT_MANDATE_ADMIN_1.2.4.66.b_2026-11-18.md`. This does not claim browser-rendered admin UI acceptance.


## `.56` active-mandate revocation role-removal branch — `.66.b` PASS — 2026-11-18
The current admin revocation callback passed with a synthetic President/active-mandate fixture. It wrote the revoked state/audit reason, removed the President role, restored Subscriber, and exact cleanup passed. Existing active mandate data remained untouched; `wp db check` passed. See the mandate retest report and helper.


## YouTube registration-fields local browser regression — PASS after fix — 2026-11-18
The local browser check exposed a real UI defect: every slot was labeled required in online mode, including the optional choir/orchestra composition and both band compositions. Fixed the label rule in `assets/js/zmp-entry-youtube-fields.js`: only vocal performance and the required choir/orchestra composition show required in online mode; choir/orchestra optional slots and both band slots remain optional. Chromium/Playwright rerun passed all 10 assertions across vocal/choir/orchestra/band in online and physical modes plus unconfigured mode, with no JavaScript exceptions. The fieldset activated and displayed the correct slots/guidance; local fixture had YouTube disconnected, and no form submission, upload, or outbound YouTube request occurred. Source/runtime asset SHA-256: `81054f7dcea06bf71b2ad52930c1468d9c5bfcd153e45fd68f63820b9f23b41e`; `node --check` passed. PHP shortcode render/enqueue and exact fixture cleanup are separately covered by `reports/youtube-registration-fields-render-check.php`. Exact output, setup, and limits: `reports/YOUTUBE_REGISTRATION_FIELDS_LOCAL_BROWSER_1.2.4.66.b_2026-11-18.md`. This does not resolve the prior `session_unknown` channel upload; owner confirmation remains required before retry.

## Communications category-result recipient correction — 2026-11-25
- Fixed category result notices that previously included all entries in a year/category, regardless of whether each entry reached the selected competition stage.
- Exact phase recipients now come from the category jury eligibility function and exclude disqualified entries after resolving exactly one season.
- Direct synthetic publish runtime PASS: only the qualified regional entry owner was notified; unqualified and pre-existing draft owners were not. Announcement and linked WordPress post were confirmed, mail was not attempted, all synthetic changes were cleaned, and `wp db check` passed.
- Product source and local plugin runtime copy matched at SHA-256 `59F2593AB5F1D5FC534E8B5661CCCDBD7F1F785FA704E9895E4EE261504256F5`.
- Evidence: `reports/COMMUNICATIONS_CATEGORY_RESULTS_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md`.
- This closes only the category direct-results recipient case. Category saved-draft results, general category announcement audience, broader functional coverage, source synchronization, packaging and overall phase acceptance remain open. Phase stays IN PROGRESS / NOT_READY.


## 2026-11-25 — Qualification normalization runtime gap closed
The historical Nov. 13 `NOT RUN` is superseded for the exact current handler hash by `reports/JURY_QUALIFICATION_NORMALIZATION_RUNTIME_1.2.4.66.b_2026-11-25.md`. Six normalization inputs passed, including rejecting `'on'`, `'2'`, and `'1'` at the terminal stage; the actual finalization POST rejected `'on'` before passkey/persistence, result and audit counts stayed unchanged, synthetic admin was removed, and DB integrity passed. No product source change in this retest. Full passkey ceremony, remaining route variants and whole-phase acceptance remain open.

## 2026-11-25 — Regional-only passkey registration options verified
The earlier regional-only enrollment `NOT RUN` is superseded for eligibility and registration-options issuance by `reports/REGIONAL_ONLY_PASSKEY_REGISTRATION_OPTIONS_1.2.4.66.b_2026-11-25.md`. A synthetic commission account with an exact active-season regional assignment and no ordinary assignment received valid local `localhost` registration options and an exact unconsumed challenge; no credential was created. Exact cleanup passed, including preserving/restoring pre-existing expired challenge rows, and database integrity passed. Browser authenticator ceremony and credential persistence remain NOT RUN; do not mark full enrollment complete.

## 2026-11-25 — Pending-document finalization guard verified
The historical pending-document direct-rejection `NOT RUN` is superseded for the current local handler by `reports/PENDING_DOCUMENT_FINALIZATION_GATE_1.2.4.66.b_2026-11-25.md`. Approved documents showed the finalization form; one pending review hid it. A direct HTTP finalize request with a valid synthetic signed step-up assertion redirected to `documents_not_approved` without result or audit mutation. Exact cleanup/restoration, PHP lint and database integrity passed. This is one bounded synthetic entry/phase only; broader finalization and route cases remain open.

## 2026-11-25 — Saved category-results draft publication verified
The saved-draft category-results recipient case now passes in the isolated local runtime. The actual `handle_publish_draft()` promoted a synthetic regional results draft only after the exact phase gate passed, notified only the qualified synthetic entry owner, linked one published WordPress post, attempted no email, and cleaned all fixture data exactly. `wp db check` passed. Evidence: `reports/COMMUNICATIONS_CATEGORY_RESULTS_DRAFT_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md`. This is one regional scope; general announcement audience rules and broader phase gates remain open.

## 2026-11-25 — audio catalog audit/visibility runtime
- Verified the current isolated runtime's HTTPS URL validator, mocked URL audit, and teacher visibility rules. Exact mock only: one intercepted request, zero unexpected outbound HTTP calls. Fresh approved synthetic tool visible; stale or pending tool hidden.
- Restored the exact prior audio option DB row (option ID, raw serialized value and autoload); `wp db check` passed. No production service or real tool URL was contacted.
- Evidence: `reports/AUDIO_CATALOG_AUDIT_VISIBILITY_1.2.4.66.b_2026-11-25.md`, fixture `tests/audio-catalog-audit-visibility-runtime.php`, stdout `reports/AUDIO_CATALOG_AUDIT_VISIBILITY_RUNTIME_2026-11-25.txt`.
- Scope remains partial: positive admin-post create/approve/reject/archive flows and admin visual UI remain open; this report does not close the audio-admin route family.

## 2026-11-25 — Audio catalog admin-post lifecycle — local synthetic PASS
- Actual admin-post routes passed for save/pending, mocked audit/available, approve and teacher visibility, URL edit resetting approval/audit, and archive. Current source/runtime hash was tied to the fixture. No external service was contacted; temporary mock allowed only the synthetic URL, while the pre-existing outbound guard remained in place.
- Exact option row restored; synthetic administrator, temporary MU filter and guard marker restored; `wp db check` passed.
- Two early harness attempts were corrected: loopback WP-CLI was blocked by the existing local guard, then stale option cache and MU-plugin load order obscured the audit state. They did not reveal a product defect and left no residual changes.
- Evidence: `reports/AUDIO_CATALOG_ADMIN_POST_1.2.4.66.b_2026-11-25.md`, fixture `tests/audio-catalog-admin-post-runtime.php`, stdout `reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt`.
- Remaining audio checks: invalid/unauthorized route matrix and admin UI; this scoped PASS does not close them.

## 2026-11-25 — Audio archive reapproval defect fixed
- Direct POST previously reapproved an archived tool if its URL audit was still fresh. The actual route reproduced notice=approved and changed archived to approved.
- approve() now rejects archived records with archived_locked; editing is required to return it to pending review. Candidate and active zmp-competitions-66b runtime files match exactly.
- Retest passed create, mocked audit, approve/teacher visibility, archive, direct archived reapproval denial/no status change, URL-edit invalidation, and archive. Exact option row, user, MU mock and existing HTTP-guard marker were restored; wp db check passed.
- Evidence: reports/AUDIO_CATALOG_ADMIN_POST_1.2.4.66.b_2026-11-25.md and reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt. Fixture: tests/audio-catalog-admin-post-runtime.php.
- Audio unauthorized/invalid-input matrix and visual admin UI remain open; broader .66.b gates remain open.

## 2026-11-25 — Audio catalog archived-decision guards and focused negative tests
- The same direct-route defect affected both approve and reject: archived records could be changed to approved or rejected. Both handlers now fail closed on archived records with archived_locked. Editing through save is the route to reopen review.
- Current active runtime tests pass for create, mocked audit, approve/teacher visibility, archive, denial of direct archived approve/reject, URL edit invalidation, denial of approve before audit, blocked YouTube privacy URL without mutation, anonymous route denial, and invalid nonce without mutation.
- Exact option row, temporary administrator/mock and pre-existing HTTP-guard marker were restored. Database integrity passed. Candidate and actual active plugin path wp-content/plugins/zmp-competitions-66b match hash C3DD0B13D5D1D4A5FCEA540279C884C0F1B871BC2216300A972B0DBECA4662A1.
- Evidence: reports/AUDIO_CATALOG_ADMIN_POST_1.2.4.66.b_2026-11-25.md; tests/audio-catalog-admin-post-runtime.php; reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt.
- Full role/input matrix and visual admin UI remain open; broader phase gates remain open.

## 2026-11-25 — school email on current active runtime
- After syncing candidate source to the existing 8103 local runtime, the stacked active President email service flow passed 8/8 and the real authenticated admin-post approval passed 10/10. No real email was sent.
- Role metadata, existing active synthetic mandate, request/profile/audit rows, local wp-config bytes and HTTP-guard marker were restored; DB integrity passed.
- Current tested class and entrypoint hashes match candidate/runtime. Evidence: reports/SCHOOL_EMAIL_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md and its runtime outputs.
- Appointment/replacement with the current hash, role-denial/no-mutation matrix and UI remain open.


## 2026-11-25 — President stacked-role UI status correction
The previously open President appointment form/UI item is now PASS for the scoped stacked-role case: candidate display, help copy, scheduled appointment callback, role retention, appointed audit, exact cleanup and DB integrity. See reports/PRESIDENT_MANDATE_STACKED_MEMBER_UI_AND_RUNTIME_1.2.4.66.b_2026-11-25.md. Other President mandate variants and all broader .66.b open gates remain in progress; this does not make the phase ready.


## 2026-11-25 — school-email submit denial status correction
Focused request-submission negative cases now pass 6/6 on the current local runtime, including actual invalid-nonce callback rejection and no-mutation checks. Evidence: reports/SCHOOL_EMAIL_SUBMIT_NEGATIVE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md. Other owner/contact/relationship/input variants, positive submission UI and whole-phase gates remain open.

Candidate-list filter is additionally verified 4/4: allowed stacked member included, stale President-role account and Administrator excluded. Evidence is appended to reports/PRESIDENT_MANDATE_STACKED_MEMBER_UI_AND_RUNTIME_1.2.4.66.b_2026-11-25.md. This does not close other mandate or whole-phase checks.

School-email request submission now has a current-runtime positive admin-post PASS in addition to the focused 6/6 denial matrix. The valid shared-school request produced one pending record/event; requester readback and private audit/redirect checks passed, with exact cleanup and DB integrity. See reports/SCHOOL_EMAIL_SUBMIT_POSITIVE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md. Other variants and UI remain open.

Additional school-email submission boundary cases pass 6/6: missing contact profile, unrelated owner school, non-teacher contact, unowned school ID and overlong reason all failed closed without request/event writes. Evidence is appended to reports/SCHOOL_EMAIL_SUBMIT_NEGATIVE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md. Positive and negative submit backend paths are covered; duplicate/idempotency, visual form, and broader route/phase checks remain open.

School-email duplicate submission is now verified 4/4: repeated same-scope call reuses one pending request and keeps the first reason/event. Exact cleanup and DB integrity passed. Evidence: reports/SCHOOL_EMAIL_SUBMIT_IDEMPOTENCY_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md. Concurrent-race and visual form checks remain open.

The active-mandate replacement path is additionally PASS 7/7 for a stacked successor: old mandate replaced/audited, new mandate active/audited, six original roles preserved and President added. Exact baseline mandate/audit/roles were restored after the test; no synthetic account remains. Evidence: reports/PRESIDENT_MANDATE_STACKED_MEMBER_UI_AND_RUNTIME_1.2.4.66.b_2026-11-25.md. Other lifecycle and phase gates remain open.

School-email shortcode markup is verified 7/7 in the local runtime: teacher form and status, active-President approval queue, action nonces, and no official address exposed in either view before approval. Exact synthetic cleanup, President role restoration and DB check passed. Evidence: reports/SCHOOL_EMAIL_ACCESS_UI_RENDER_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md. Browser/mobile visual and full phase acceptance remain open.

The actual school-email disclosure admin-post route now passes 7/7 locally: request-bound requester received the approved synthetic address; HTTP response was no-store/noindex; audit omitted the address; request remained approved. Exact cleanup, President role restoration and DB integrity passed. Evidence: reports/SCHOOL_EMAIL_DISCLOSURE_ADMIN_POST_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md. Browser/mobile and negative HTTP disclosure route variants remain open.

The disclosure HTTP route matrix is now combined PASS 9/9: valid requester receives the synthetic approved address in 200 no-store/noindex HTML; another Teacher with a valid bound nonce and requester with an invalid nonce both receive 403 without address/event disclosure. Exact cleanup, session, diagnostic option, President role restoration and DB integrity passed. Evidence: reports/SCHOOL_EMAIL_DISCLOSURE_ADMIN_POST_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md. Broader UI/phase gates remain open.

Audio admin-post route coverage now includes two additional malformed-input denials: invalid category and identifier sanitized to empty. Both were no-mutation; the complete existing fixture and DB check passed with exact cleanup. Evidence: reports/AUDIO_CATALOG_ADMIN_POST_1.2.4.66.b_2026-11-25.md. Non-admin authenticated role, remaining malformed variants and visual admin UI remain open.

## Audio admin UI desktop check — 2026-11-25 (current)
The actual .66.b audio catalog admin screen rendered in Edge on the retained local clone; form, required fields, category selector, empty catalog message, and teacher-visibility guidance were visible. Browser console errors/warnings: zero. No form submission or data mutation. Candidate/runtime source hash matched. Temporary and stale synthetic audio-admin users 249 and 179–181 were removed; the latter three had no owned posts. Database check passed. Desktop render is PASS; responsive layout, other roles and residual route matrix remain open. See `reports/AUDIO_CATALOG_ADMIN_UI_EDGE_2026-11-25.md`.

## Current local fix — knowledge-test school-phase result path (2026-11-25)
The enabled `knowledge_test` school phase was rejected by category-result eligibility despite the Communications UI/configuration exposing it. Added school to Jury phase validation and enabled-stage/scoring enumeration, passkey finalization context validation, and Teacher phase filtering. Direct Communications publish now passes with exact owner scope, linked post, zero email calls and exact cleanup; Teacher filter readback, step-up context check, regional-recipient regression and qualification regression pass. DB check and local HTTP 200 pass. Full browser passkey ceremony/persistence for school finalization remains NOT RUN; broad .66.b gates remain open. See `reports/COMMUNICATIONS_KNOWLEDGE_SCHOOL_PHASE_1.2.4.66.b_2026-11-25.md`.


## Messenger authorization follow-up — 2026-11-25
Focused local role-room authorization and category/region scope checks PASS 13/13 with exact synthetic cleanup. This is not a full Messenger closure: UI, message lifecycle, AJAX routes, and schema repair/recovery remain open. Evidence: 
eports/ROLE_MESSENGER_SCOPE_MATRIX_1.2.4.66.b_2026-11-25.md.



## Messenger schema readiness — 2026-11-25
The retained local clone has compatible Messenger/retention schemas, current marker, no pending retry, and confirmed retry storage. Read-only fixture PASS 7/7 with unchanged row counts/options; candidate/runtime source hash matched. Does not close production migration, browser retention, or all Messenger flows. See 
eports/MESSENGER_SCHEMA_READINESS_RUNTIME_1.2.4.66.b_2026-11-25.md.



## Messenger regional-only authorization — 2026-11-25
A regional-only Commission account is allowed solely in the exact assigned region/category; national/unscoped, other-region and other-category access are denied. PASS 5/5 with exact cleanup. Regional UI and other Message routes remain open. Evidence: 
eports/ROLE_MESSENGER_REGIONAL_ONLY_SCOPE_1.2.4.66.b_2026-11-25.md.



## Stacked member compatibility — 2026-11-25
Final Messenger room matrix PASS 18/18 with Subscriber + Teacher baseline and stacked Testator + Moderator + Commission assignments. Correctly stacked synthetic President and Administrator have no assignment bypass; ordinary Teacher is denied. Existing malformed local test account 116 was not modified. See 
eports/ROLE_MESSENGER_SCOPE_MATRIX_1.2.4.66.b_2026-11-25.md.



## President membership role invariant — 2026-11-25
The appointment UI and server-side role-grant checks now require Subscriber + Teacher. A Subscriber-only synthetic candidate is hidden and direct appointment is denied without mandate/audit mutation; a stacked Teacher/Subscriber positive appointment regression passes. Existing local user 116 was not changed; production was not inspected. See 
eports/PRESIDENT_TEACHER_SUBSCRIBER_RULE_1.2.4.66.b_2026-11-25.md.



## President dashboard role stack — 2026-11-25
The President menu/dashboard no longer rejects mandatory Teacher + Subscriber roles. Supplemental active/inactive role-stack runtime passed 7/7 on existing 8103 clone; owner-reported 8100 PASS from yesterday remains separate. See 
eports/PRESIDENT_STACKED_ROLE_DASHBOARD_1.2.4.66.b_2026-11-25.md.



## Owner-directed Drive video flow — 2026-11-25

Owner-directed design: replace the teacher-facing YouTube upload/link flow with plugin-managed upload to restricted ZMP Google Drive. The teacher sees the selected filename and size; client and server validate the actual video; upload begins only after “Поднеси пријава”. The form stays in place with animated staged progress and no refresh until Drive upload, object/metadata verification, and jury playback authorization all pass. The entry remains a draft on any failure and supports an exact retry.

The jury sees a small thumbnail card, not a Drive URL or file ID. Clicking it opens playback through the authenticated ZMP stream after exact commission assignment checks. Reduce file size with a quality-preserving validated compression profile before upload; keep the original privately until success. Preserve category slot rules and physical/online/combined requiredness. The full design and acceptance checklist are in reports/DRIVE_VIDEO_SUBMISSION_AND_JURY_THUMBNAIL_DESIGN_1.2.4.66.b_2026-11-25.md.

Status: DOCUMENTED / NOT IMPLEMENTED OR TESTED. Existing Drive OAuth, resumable upload, private media storage, and authenticated player are the starting mechanism; the current upload handler redirects and the jury view has no thumbnail card. Do not count the design note as a runtime PASS. The main PROJECT_ROADMAP.md remains unchanged.
