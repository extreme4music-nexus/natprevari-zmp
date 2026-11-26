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
## CURRENT STATUS PRECEDENCE — 2026-11-25

**OWNER RULE:** PASS is final for the exact acceptance scope on 2026-11-24; an older NOT RUN/FAIL for that same scope is historical and must never be counted open or repeated without new contradictory evidence. Scoped PASS records include role/President stacks 26/26, stacked-President school-email route, stacked-Teacher proposal route, moderator delivery-mode route, invitation send, Messenger reply/attachment/limited-review flows, and Messenger legacy-index migration. See dated work-log entries and evidence reports.

The 2026-11-12 blanket NOT RUN bullets below are a dated checkpoint, superseded for the specific scopes with later PASS evidence. Current scoped remaining items recorded after those passes include the school-finalization browser passkey ceremony/database persistence and untested route/UI variants. The full-phase acceptance state remains separate from individual PASS results.


# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

## CURRENT INHERITED-FIX RESULTS — 2026-11-25

- `.65` inherited-fix regression on `.66.b`: **11/11 PASS**.
- Confirmed stale schema marker bypasses future retry deadline; current marker preserves cooldown: **2/2 PASS**.
- Partial school-profile index loss returns `schema_not_ready` and restores cleanly: **4/4 PASS**.
- Local database integrity PASS after fixture cleanup. `.66.b` was tested on the existing loopback clone, then `.66.a` was restored.
- Existing integer display-width normalizers and diagnostic detail remained intact.
- Still open: Drive video submission/thumbnail remains documented but not implemented; production Messenger state and browser passkey ceremonies are not closed by local tests.

Evidence: `reports/INHERITED_65_FIXES_AND_66B_OPEN_SCOPE_1.2.4.66.b_2026-11-25.md`.

---
# Test report — ZMP 1.2.4.66.b

## Current checkpoint

- PHP syntax check — PASS: `includes/class-zmp-passkeys.php` (`php -l`, 2026-11-12).
- PHP syntax check — PASS: `includes/class-zmp-category-jury.php` (`php -l`, 2026-11-12).
- PHP syntax check — PASS: `admin/class-zmp-passkey-profile.php` (`php -l`, 2026-11-12).
- JavaScript syntax check — PASS: `assets/js/zmp-president-subject-stepup.js` (`node --check`, 2026-11-12).
- Historical 2026-11-12 checkpoint: broad functional WordPress/database testing had not yet run. Scoped WordPress/database runtime checks passed later on 2026-11-24 and 2026-11-25; see the work log.
- Historical 2026-11-12 checkpoint: browser passkey ceremony and responsive UI had not yet been verified. The Owner later accepted the selected mobile layout; the school-finalization passkey ceremony/database persistence remains a separate NOT RUN scope.
- Historical 2026-11-12 checkpoint: the broad negative/security matrix had not yet run. Named role/nonce/replay/route denial cases have later scoped PASS evidence; exhaustive cross-route and DB-failure/no-mutation coverage remains separate.

These syntax checks do not establish runtime behavior or phase acceptance. At the 2026-11-12 checkpoint, the active local site was `.66.a`; later `.66.b` synthetic runtime work is recorded in the dated work log. No final-result or audit data was written. Continue with a phase-isolated synthetic environment before recording any functional result.

## Owner UI verification setup — 2026-11-12
- Active phase: `.66.b`, Owner-selected privileged action: finalizing a jury result. The separate synthetic runtime is `/home/tonic/zmp-local-sandbox/phase66b-test/site`, database `zmp66b_isolated`, table prefix `z66a_`; PHP PID `60347` listens on `127.0.0.1:8103`, MariaDB PID `54934` uses a private socket and `--skip-networking`.
- Candidate plugin `zmp-competitions-66b` version `1.2.4.66.b` is the sole active plugin. HTTP smoke request for the test route returned 200. The synthetic fixture has 1 entry, 1 jury assignment, and 1 submitted municipal assessment; the copied synthetic President account also has the Commission role and regional assignment. The page `zmp-66b-jury-test` contains `[zmp_commission_portal]`.
- Fixture setup initially exited before writes because the script treated `WP_User::add_role()` as returning a success value. Corrected the fixture-only guard; the setup then completed. No `.66.a` files, database, or service were modified. No UI behavior has been inferred from HTTP 200.
- The existing Edge tab was accessible but displayed `.66.a`; the UI Reviewer could not independently claim it. Per Owner direction, record only: “UI Reviewer agent failed to check — irrelevant to the project.” Owner visual and interaction checks are pending; no PASS/FAIL/BLOCKED is attributed to that agent.
- Owner check URL: `http://localhost:8103/?page_id=4&zmp_commission_tab=jury`. Capture desktop view of “Подготвени за конечна одлука” and the complete synthetic finalization form. Then use Mobile View at iPhone 13 Mini, 375×812, refresh, and capture the same form/status/button across scroll if needed. For the interaction check, use synthetic text of at least 30 characters in both required explanation fields and submit once; if the browser requests passkey/PIN, Owner confirms locally. Capture the resulting page/status, never the PIN prompt or PIN. If the page or form is absent, send a screenshot and do not submit.
- Superseded by the Owner screenshot update below: desktop rendering evidence received for the earlier layout; revised layout, Mobile View, passkey interaction and final-result database mutation remain NOT RUN. Phase remains IN PROGRESS / NOT_READY; no package, GitHub sync, or Auditor gate claimed.
## Temporary Owner login prepared — 2026-11-12
- Reset the password for synthetic user ID 116 only in the isolated database `zmp66b_isolated`; confirmed password-hash validation and `wp_authenticate()` PASS. User retains synthetic President and Commission roles. No credential value is recorded here.
- Private credential note is stored only at `/home/tonic/zmp-local-sandbox/phase66b-test/private/owner-login.txt` (mode 600). Open it from Windows using `\\wsl.localhost\Ubuntu\home\tonic\zmp-local-sandbox\phase66b-test\private\owner-login.txt`. Do not copy its contents into screenshots/chat/reports. Remove the note and reset/remove the temporary credential after Owner UI check is complete.
## Owner blank-page correction — 2026-11-12
- Owner reported a blank white page at the provided slug URL. Diagnosis: the cloned synthetic WordPress site had no installed/active theme and had an empty permalink structure; the slug path fell through to the posts home (Hello world). PHP server logs had no fatal/parse error.
- Added and activated a minimal `zmp-ui-harness` theme only inside the isolated `.66.b` test site. No plugin source was modified. The exact test page works at `http://localhost:8103/?page_id=4&zmp_commission_tab=jury`.
- Verified the test page response is HTTP 200 with rendered HTML, and shortcode render under synthetic user 116 contains the commission dashboard, decision-basis field and “Потврди конечен резултат” button (10,970 bytes). This is server-render evidence only; Edge/mobile visual checks remain pending Owner evidence.
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
## Owner-confirmed finalization and desktop UI evidence — 2026-11-12 23:48 Europe/Skopje
- Owner-provided screenshots `Screenshot 2026-11-12 234809.png`, `234815.png`, `234818.png`, `234821.png`, and `234824.png` show the `.66.b` jury workflow after finalization. The Owner explicitly confirms the browser requested a passkey and the confirmation succeeded. Record the privileged browser action as PASS on Owner evidence; no PIN value or prompt capture was requested or recorded.
- The screenshots show approved required-document statuses, the final result as confirmed, and the jury page UI. The Owner explicitly accepts the current desktop UI. Record desktop UI acceptance PASS by Owner. The supplied images are desktop captures; Mobile View remains NOT RUN.
- Read-only synthetic DB verification: exactly one scoped final-result row for season 1/category 2/entry 1 at municipal level: jury average `92.50`, final score `100.00`, award `first`, status `confirmed`, actor ID 116; exactly one matching `result_confirmed` audit event. WordPress DB check returned `Success: Database checked` with all tables OK. These are synthetic local data; no rationale text is repeated here.
- This supersedes earlier NOT RUN statements for finalization browser interaction/result persistence and current desktop rendering. Do not erase those historical checkpoints. No claim is made for mobile responsiveness, broader authorization/replay matrix, independent phase reviews, package, GitHub sync, or Auditor acceptance. Phase remains IN PROGRESS / NOT_READY.

## Owner Mobile View review — first iteration — 2026-11-12 23:52 Europe/Skopje
- Owner screenshots `Screenshot 2026-11-12 235233.png`, `235237.png`, `235239.png`, `235242.png`, `235245.png`, `235247.png`, and `235250.png` show Edge responsive emulation at 375×679 on the `.66.b` jury route.
- Findings from the screenshots: commission tabs are clipped horizontally; dense jury/result tables are difficult to scan at phone width and result cells have excessive vertical expansion; region/municipality/school filters are separated by excessive gaps. Overall Mobile UI acceptance is NOT YET ACCEPTED.
- Updated only the `.66.b` public stylesheet: wrap commission tabs on narrow screens, use a one-column compact filter layout, keep dense jury tables in a bounded horizontal scroll area with readable minimum column width and top-aligned cells. No stored result or other DB state was changed. No browser recheck has occurred after this CSS edit; outcome NOT RUN.

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

## YouTube admin-route denial matrix — 2026-11-13
- On isolated `.66.b` synthetic WordPress, synthetic Teacher user 117 received HTTP 403 before nonce/state processing for the YouTube admin page, settings save, OAuth connect, OAuth callback, OAuth disconnect, regional-juror assignment, and uncertain-upload clearing. All seven sampled admin-only surfaces denied as expected.
- Before/after YouTube settings option, YouTube media-row count, and regional-video-juror row count were unchanged. Counts remained zero; no OAuth call or media operation occurred. This is a sampled role-denial check, not proof of positive admin flows.
- Retained helper `reports/youtube-teacher-admin-denial-check.php` SHA-256 `D069CFCBA0710CDE447C43F826B5F0A5B24BE569882B4CF7067B0C72CECA40A5`; the WSL temporary execution copy matched exactly and was removed after the run. PHP syntax check PASS.
- OAuth consent, browser rendering, channel identity, synthetic video upload/processing, playback/embed, and regional-juror access remain unperformed and Owner-dependent. No phase status changed.

## YouTube administrator page render — 2026-11-13
- Rendered `ZMP_YouTube::render_admin_page()` through WP-CLI as synthetic Administrator user 1 on the isolated local WordPress site. Seven expected items were present: page heading, client ID field, password-type client secret field, callback URI, Unlisted warning, video rules, and disconnected setup hint. Output: all seven `PASS`; `connected=no`.
- This is a server-render/markup check, not an interactive browser visual review. No form was submitted, no setting changed, no network request was made, and no credential was read into the report.
- Retained helper `reports/youtube-admin-render-check.php` SHA-256 `F20739C48F8ED325C45D63EA32554F1D3BBC0EC02978CF43B2C6A29551AD0339`; the WSL execution copy was removed after the run.

## Owner OAuth connection observed in local UI — 2026-11-13
- Owner reported completing Google OAuth setup. In the existing Edge tab for the isolated `http://localhost:8103` WordPress site, signed in as the temporary synthetic Administrator, the YouTube archive page displayed the successful connection notice and a connected-channel status. This is evidence of a successful local OAuth callback/configuration as shown by the plugin UI.
- Client Secret, OAuth Client ID, and channel identifier were visible in the page but were not copied into project evidence or chat. No setting was changed by the agent and no additional API request was sent during this observation.
- This does not verify video upload, privacy status, processing completion, playback, or regional-juror access. Do not proceed to upload until the selected channel and clip are confirmed synthetic/non-participant.
- The temporary login document was deleted after the Owner's successful login was observed; the dedicated synthetic Administrator remains for the local test session and should be removed after YouTube checks are complete.

## Owner-authorized synthetic Unlisted upload attempt — 2026-11-13
- The Owner explicitly authorized one test upload to the already-connected, non-synthetic YouTube channel using a synthetic clip, with Owner deletion immediately after a successful upload. No participant or personal media was used.
- Local UI confirmed the OAuth-connected state. The synthetic clip was 8 seconds, 1280×720 H.264, no audio, with a synthetic on-screen marker; SHA-256 `850C211C201E2A610344003E27044FBB632721AB9042099968AAD27523FBB32C`, size 16,248 bytes. It remains only in the isolated sandbox `tmp` folder.
- The isolated synthetic fixture had been `approved`, with no regional delivery event and no media row. For the actual upload handler check, it was temporarily changed to `draft`, an `online` regional event was added, and a temporary loopback-only PHP shim invoked the real `ZMP_YouTube::upload_entry_video()` as the fixture's owning synthetic Teacher (117). The shim was removed immediately after the single request. This is not a browser UI acceptance check.
- One multipart request returned the plugin's HTTP 302 redirect. The database recorded `processing_status=session_unknown`, empty privacy status and video ID, upload offset 0, file size 16,248, and no encrypted resumable URL. No video ID/link or successful Unlisted state was confirmed. No second upload or uncertainty-clear action was attempted.
- A separate read-only YouTube `channels.list` request returned HTTP 200 using the in-memory access token. This confirms token refresh/read access, not the `videos.insert` upload permission or resumable-session outcome. The plugin currently collapses session-start failure into `session_unknown`; the exact Google HTTP/error response was not captured. Do not claim an OAuth failure or a successful upload from this evidence.
- The synthetic entry/event and `session_unknown` media row remain in the isolated test DB to preserve the no-blind-retry guard and make the state diagnosable. No connected-channel setting was changed. Owner should check recent YouTube Studio content for any corresponding incomplete item; if one exists, delete it and report that action. Do not retry until the uncertain state is resolved.
- Upload criterion: **FAIL / UNRESOLVED** for this single attempt; `Unlisted` and processing are NOT VERIFIED. `.66.b` remains IN PROGRESS / NOT_READY; no package, GitHub sync, Auditor result, or phase acceptance is implied.

## Owner-directed temporary entry-video add-on — 2026-11-13
- Corrected target: video selection must appear on the teacher "Нови пријави" form for vocal interpretation (1), choir (2), orchestra (2), and bands (2). Admin YouTube settings recheck is not relevant to this report.
- Built `temporary-plugin/zmp-entry-youtube-upload` and temporary ZIP `.temp/zmp-entry-youtube-upload-1.2.4.66a-TEMP.zip`. The add-on injects category/mode-aware selectors and, after the main handler creates the entry, posts selected video files through the existing YouTube upload forms/nonce/handler. It creates no tables and stores no OAuth values.
- Compatibility source check: `.66.a` and `.66.b` registration class SHA-256 both `7B4774416BE708B59A78A7256E1758AF18A07A6F98C736C244D8C6CB6944D699`; `.66.a` YouTube class SHA-256 `689257D80B900280C9ED9A81B0D5C6E2393D0B5C011013FA953B948A3393672D`, which matches the `.66.b` YouTube class hash recorded earlier.
- PHP syntax lint PASS. Embedded JavaScript syntax check PASS. ZIP contains exactly one top-level plugin directory with `README.md` and the PHP entrypoint. No activation hook, schema change, table creation, or DB migration exists in the add-on source.
- Authenticated page rendering, selection-to-upload flow, actual YouTube transfer, and physical-entry submit without videos remain NOT RUN. The IAB browser did not have the teacher session and displayed the WordPress starter homepage; that is not evidence about the Owner's Edge session. Owner should install/test only on an isolated local synthetic copy and return screenshots/status text.
- This is a temporary test add-on, not an official `.66.a`/`.66.b` plugin release. No production site was accessed or changed.

## Owner-confirmed entry-video flow and core integration — 2026-11-14
- Owner-confirmed result: on the Owner-used site, video fields appeared in “Нови пријави”; one video upload succeeded, the video appeared on the channel, and the application completed. Record this exact scope as OWNER-CONFIRMED PASS. The Owner did not state the category, online/physical mode, privacy/processing verification, or juror playback outcome; those are not inferred.
- Integrated the temporary category-aware entry video fields into the `.66.b` plugin as `includes/class-zmp-entry-youtube-fields.php` and loaded it from the plugin bootstrap. It delegates the file upload to the existing `ZMP_YouTube` upload forms/actions and does not add schema or tables. PHP syntax lint passed for the new class and bootstrap. Browser execution against the `.66.b` candidate was not performed; Owner confirmation refers to their actual tested installation and is kept separately from candidate-local runtime evidence.
- The earlier isolated `.66.b` upload attempt remains a distinct `session_unknown` event; it was not retried or cleared and is not conflated with the Owner-confirmed success. No claim is made that the two events share a row, channel, or video.
- Phase remains IN PROGRESS / NOT_READY. Remaining YouTube scope: exact candidate rendered/interactive field behavior, privacy and processing state, and reviewer playback/access where applicable; package/release gates remain open.

## `.66.b` runtime integration checkpoint — 2026-11-14
- Copied only the candidate entry-video class and plugin bootstrap into the existing isolated `.66.b` runtime. Candidate/runtime hashes match exactly: entry-video class `0731D0651F161AAEAA88795A4C00885535A17B94C67327840759BA91CF72C5AF`; bootstrap `ED0E746765FB478D6D46E5E6C3104E90329E9D9759C20132CF6A6264CC24E355`. Existing active plugin inventory is only `zmp-competitions-66b`; PHP PID 60347 serves port 8103 and MariaDB PID 54934 is the `.66.b` sandbox DB. No service lifecycle action.
- `wp db check` passed. Read-only query found one regional `session_unknown` media row, with blank privacy status, no video ID, and no upload progress. It is retained unchanged and not equated with the Owner-confirmed upload.
- Exact-candidate registration page render was NOT RUN: the local synthetic site has no teacher registration hub page (only the sample page and jury portal among published pages); a command-tool attempt to create a temporary render script was rejected before creation. An Edge address-bar interaction was interrupted by a user-input detection and the browser remained on the homepage. No database/page/user changes occurred from those attempts.
- This runtime limitation does not negate the Owner-confirmed selector/upload/application event. It leaves exact-candidate browser presentation unobserved. No new video upload was performed.

## Matrix upload correction — 2026-11-14
- Owner clarified that the defect is matrix/audio upload. The supplied screenshots show the upload modal with an audio file selected and a generic required-fields/upload error. No file or form submission was performed by the agent.
- Cause identified: the UI advertised a fixed 20 MB limit while the isolated PHP server previously used `upload_max_filesize=2M` and `post_max_size=8M`; the handler also collapsed upload-size errors into a generic validation message. The specific selected file size was not available, so exceeding 2 MB is a plausible cause, not a confirmed fact.
- Candidate fix in `includes/class-zmp-matrix-catalog.php` and `assets/js/zmp-matrix-catalog.js`: advertise and validate against the effective WordPress/server limit, use the JS file modification time for cache busting, detect oversized POSTs and PHP upload errors before generic validation, and return specific messages/statuses. Source hashes: PHP `97681421B5712CF16A97DB4E4B83C8487D4DDC40431AF7A151C23CF749DBFEC5`; JS `272ECF8278C6DE91A580C5464D0C47D40395FB813A587E0314CB7FE323215DB5`.
- The exact candidate files are present in the isolated `.66.b` runtime. PHP lint PASS; JavaScript syntax check returned success; `wp db check` PASS. Edge visibly rendered the matrix picker and upload modal with the 20 MB label. No Drive request, file upload, or matrix row write was made. End-to-end Drive upload remains NOT RUN pending Owner's Drive setup and test.
- Local PHP server PID `75729` listens on `127.0.0.1:8103` with per-process `upload_max_filesize=20M` and `post_max_size=24M`; MariaDB PID `54934` remains unchanged. WP-CLI reports its own separate PHP context and still shows the default 2 MB limit; it is not evidence of the web server's process settings. The visible browser label confirms the rendered web request currently sees the 20 MB effective limit.
- Phase remains IN PROGRESS / NOT_READY. No package, GitHub sync, or Auditor result is claimed.

## Owner-requested cross-phase carry-forward retest — 2026-11-18
Local synthetic runtime checks PASS for the tested Messenger legacy-width migration, nine-table compatibility, immediate/scheduled audit writes, fail-closed narrow-schema behavior and 300-second retry; approved-audio future timestamp and missing-URL rejection; catalog partial-schema detection and nullable-timestamp migration; Teacher subject submission with no outbound WordPress HTTP; Teacher profile callback save/readback; and passkey challenge-table DDL failure injection. Exact hashes, cleanup, backup/restore, and boundaries are in reports/CROSS_PHASE_CARRY_FORWARD_RETEST_1.2.4.66.b_2026-11-18.md.

## Messenger schema warning attribution and diagnostics — 2026-11-18

The shared retry flag also covers school-profile, school-email, proposal, and admin-facsimile schema prerequisites; the prior admin notice incorrectly described every pending migration as a Messenger schema defect. Updated notice wording and independent prerequisite logging. Added exact Messenger mismatch events and structured schema context support. A local `VARCHAR(20)` `trigger_type` fixture was rejected, logged with expected `VARCHAR(32)` and actual `VARCHAR(20)`, repaired by `ZMP_Role_Messenger::install()`, and returned to compatible state with all nine table row counts unchanged. Separate synthetic false readiness flags were attributed to Messenger, school profile, and school email. PHP lint, archive integrity, and database check passed. Production database diagnosis remains pending Owner installation and retry. Full evidence: `reports/MESSENGER_SCHEMA_DIAGNOSTICS_1.2.4.66.b_2026-11-18.md`.

Owner's 500-event export was reviewed. The 17:23 retry marked Messenger and admin facsimile, with no exact mismatch details; school engagement had a successful create at 17:18:59 and following profile errors were authorization `forbidden`. Added the missing early-installer failure diagnostics for Messenger and exact admin-facsimile schema diagnostics, including integer display-width normalization. Local Messenger/admin-facsimile predicates return true; injected diagnostics survived sanitization; controlled Messenger width repair, zero row-count delta, and full DB check PASS. Production remains pending a retry with the follow-up build. Full findings: `reports/OWNER_DIAGNOSTIC_EXPORT_REVIEW_1.2.4.66.b_2026-11-18.md`.

These results close only the named tested paths. Broad route authorization/regression, President proposal decision with passkey/browser success, historical .65 attribution and GitHub provenance, and .66.b whole-phase/release/YouTube gates remain unverified. .66.b remains IN PROGRESS / NOT_READY; no ZIP or Auditor result is claimed.

## Qualification checkbox normalizer local retest — 2026-11-18
The active isolated WP plugin copy was confirmed stale and synchronized to exact candidate `class-zmp-category-jury.php` hash `3710600BDBE4B3B3506F4DB3FA99BB5F85F4824008EDEF4EBC7ACCDE0E7A1150`. Reflection-invoked actual helper passed: omitted and `'0'` -> 0; `'on'` -> `qualification_invalid`; `'1'` without next stage -> `qualification_invalid`; `'1'` for active synthetic season 1/category 1 municipal phase with an enabled next stage -> 1. Helper hashes `4f8a103ff1629afb7d6cb6213219b859db5df12b1bf31127337e8e0f0448378c` and `7c3823ae4e21961b5b0554ec34a41155ebeb89cbb57357f76ebc9f9d7de3921b`. Both checks were read-only; `wp db check` PASS. The finalization handler, successful passkey ceremony, replay/tamper checks, and browser flow remain separate and unverified. This supersedes the prior specific runtime NOT RUN for normalization, not the broader route matrix.
Read-only step-up binding follow-up PASS: same payload repeated produces same digest; qualification 0 and 1 produce distinct digests. Helper SHA-256 `783bde59f5add51d5f2af40ac2548779f93917edc439fe53b6cae7a9f053a890`. No state changed.
Actual `finalize_result()` handler follow-up PASS on exact candidate with malformed `'on'`: redirects `qualification_invalid`; result rows 1→1 and stage-event rows 0→0. Harness SHA-256 `49fc40626100582caccf9afe8bc69777531f01f3f04b841f16d2158b3812ddca`. Final database check PASS. This verifies rejection/no mutation, not the valid browser/passkey success path.

## Additional direct-route authorization samples — 2026-11-18
Synthetic Teacher 117 received 403 on communications publish, Messenger retention retry, and Messenger admin review read; target row counts were unchanged. A valid-nonce school-email decision POST by the same Teacher redirected `decision_failed`; school-email request/event counts remained 0. Helpers and exact hashes are recorded in the carry-forward retest report. Denial samples only; authorized-success and complete route coverage remain open.

## `.65` subject queue inherited denial — `.66.b` — 2026-11-18
Actual queue render callback denied synthetic Teacher 117 with 403 and no proposal/event mutation. Harness hash `a52a66f20315584e401b3ecd06ae0336310ed74672b14d5ca1b458551c7b0fac`. This closes the current-code queue denial callback only; it does not certify the President decision success/passkey flow.

## `.65` President profile photo privacy callback — `.66.b` — 2026-11-18
Exact current-source callback rendered three intended custom inputs and a temporary synthetic image `src` matching the local uploads attachment URL; no unrelated profile fields in the callback. Attachment/file and user meta cleanup PASS. Full profile page HTTP attempt redirected to login due rejected test cookie and is not counted as browser PASS. Details and hash in the carry-forward retest report.

## Full-source syntax check — 2026-11-18
PHP lint PASS: 53 PHP files in `includes/` and `admin/`, plus plugin entry point. JavaScript syntax PASS: 8 files under `assets/`. Whole-phase status remains open for broader runtime/UI/release evidence.

## President governance HTTP download and profile privacy — `.66.b` — 2026-11-18
The exact `.66.b` governance/membership source hashes matched runtime. Synthetic active-President governance download passed over local HTTP (200 PDF, attachment, nosniff, exact body) using a temporary synthetic key; exact cleanup and database integrity passed. Authenticated local profile HTTP returned 200 and exposed only the intended local synthetic photo preview in the custom photo section; profile meta, file, attachment and session restored. Details and hashes are in the carry-forward report. This closes the relevant current-code `.65` blocked route and private photo DOM scopes; it does not claim browser screenshot/accessibility review.

## President transition/admin UI and public photo privacy — `.66.b` — 2026-11-18
Local authenticated HTTP GET of the exact admin mandate page returned 200 and rendered the appointment form when provided a temporary eligible synthetic Subscriber; no mandate/governance rows changed, synthetic user/session cleaned, and no email was sent. Public President display omitted photo with consent off and emitted only the same-site synthetic image URL with consent on; no contact fields were present. Exact evidence and hashes are in the carry-forward report.

## President subject proposal passkey — synthetic local HTTP success and altered replay — 2026-11-18
The exact current `.66.b` options and admin-post routes passed locally with a synthetic ES256 credential and active President scope. The valid assertion produced HTTP 302 and exactly one rejected proposal/audit event. Replaying its consumed challenge with a changed decision produced HTTP 302 and no second event. Exact data/session/role/credential/challenge cleanup and DB integrity passed. Candidate/runtime hashes matched. This closes the earlier server-side `.65/.66.a` carry-forward proposal step-up NOT RUN. See the dated cross-phase report for harness hash and row invariants. Browser authenticator interaction remains a separate UI criterion.

## Selected jury finalization passkey — local HTTP success and altered-payload replay — 2026-11-18
The exact `.66.b` AJAX step-up options route returned 200; a synthetic ES256 assertion for the issued challenge successfully reconfirmed the result and created one audit event through the actual HTTP handler (302). Replaying the consumed challenge after changing the rationale was denied with no extra result/audit mutation. Temporary fixture state was restored exactly and `wp db check` passed. Candidate/runtime hashes match. Full details and the required temporary test-fixture adjustment are in the dated cross-phase report. Browser authenticator interaction and broader role/resource route coverage are separate gates.

## YouTube upload follow-up — synthetic asset, local-only transport tests — 2026-11-18
Prepared a new, labeled synthetic test-pattern MP4 locally; it was not uploaded. Found the pre-existing entry 1 / regional `performance` media row still in `session_unknown`, and its saved hash matches the prior synthetic clip. The current handler correctly blocks that slot pending manual channel verification. Owner was asked to check whether the previous attempt created a video. Offline helper result: 13/14 PASS; one environment fixture assertion expected YouTube unconfigured while saved settings are present. Mocked transport PASS with zero unexpected/external calls; original settings restored and temp file removed. This is not a real upload PASS.

## Owner clarification — YouTube local harness — 2026-11-18
Owner confirmed that upload worked on the actual website. The local 403 is classified as a test-harness authentication limitation; no product failure is inferred. No new live upload was performed. The synthetic local session_unknown record still must be reconciled against the channel before retry, and the fresh clip remains local. See the dated carry-forward report.

## Owner-directed legacy reconciliation and active route inventory — 2026-11-18
106 explicit source hooks; 131 active runtime hooks; 102 shared. Candidate/runtime includes tree: 50 PHP files matched byte-for-byte. wp db check PASS. This inventory did not execute routes and does not close the full authorization/regression matrix. Details and remaining gates in the dated cross-phase carry-forward report.


## Privileged route negative matrix and defect correction — 2026-11-18
Exercised all 119 plugin-owned runtime hooks with unauthenticated/synthetic Teacher contexts, no nonce or valid resource. 116 denied; two intended public President-display reads exposed only public name/date fields; handover route took its forbidden redirect branch. No SQL DML, mail, HTTP, PHP warnings, or fatal errors. Test found missing query-key PHP warnings in registration-form and entry-document downloads; corrected both to default the ID to 0, which cleanly fails nonce validation. PHP lint PASS; candidate/runtime hashes match. wp db check PASS. Full route table and harnesses: 
reports/PRIVILEGED_ROUTE_NEGATIVE_MATRIX_1.2.4.66.b_2026-11-18.md. Positive-path and broader functional regression remain in progress.

## `.66.a` Teacher profile browser submit/readback — `.66.b` local HTTP closure — 2026-11-18

Exercised the current `.66.b` `ZMP_School_Profile_UI::save()` through the real isolated local WordPress `/wp-admin/profile.php` flow. Synthetic Teacher 117 was assigned a temporary synthetic mentor/school; GET rendered the form and yielded its exact core and plugin nonces. POST used `action=update`, both nonce values, and synthetic per-school email/employment/workload/visibility fields. Result: profile GET 200, POST 302, follow-up GET 200, database values matched, and rendered form readback matched. The original synthetic Teacher core user row stayed byte-for-byte equivalent at field level; temporary profile/mentor/school rows and session were removed/restored exactly. `wp db check` PASS. Candidate/runtime hashes match: UI `FFDC2C810280924E5217CDB4D4083D5B49C6EE02D8194479278CAECD0F450F26`; service `BED1C58E6D895FA04907D6C53CAA9494946FBF0BF53C13E115D0AF8A834F6C31`. This closes the `.66.a` browser form submit/readback carry-forward for this route; partial-schema injection and other independent release/phase gates remain separately tracked.

## `.59` President facsimile certificate lifecycle — current `.66.b` runtime — 2026-11-18

Using the synthetic active President mandate 58/user 116, a temporary pending facsimile image and an ephemeral Ed25519 keypair, exercised current `.66.b` certificate registration, `verify_for_use()`, and synthetic Administrator 119 revocation. Registration returned a certificate; use verification passed for the exact synthetic facsimile hash; revocation returned success and persisted `revoked`. The facsimile/certificate/event table counts returned exactly to baseline; temporary private image, role metadata and `zmp_debug_events` option were restored; `wp db check` PASS. No Drive/HTTP calls occurred. Candidate/runtime hashes match: certificate service `153B2E4A73214B9B40660F98FF6C217ADBE687417EE950B1889513C37DC3C587`, mandate service `5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40`, certificate template `015836FF1D618917E78E8E30B257D4CB5D482D1809B5CBE4FA50F63498EDC6BE`. At this lifecycle-only checkpoint, the `.59` Race B assertion remained open; this status is superseded by the later concurrent retest documented below. Reproducer: `reports/synthetic-president-certificate-lifecycle.php`.


## .59 Race B current .66.b concurrent issuance/revocation — PASS — 2026-11-18

Closed the carried Race B gap against the exact current .66.b candidate in the isolated local WordPress clone. Candidate/runtime SHA-256 matched for the certificate service (153B2E4A73214B9B40660F98FF6C217ADBE687417EE950B1889513C37DC3C587), mandate service (5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40), certificate template service (015836FF1D618917E78E8E30B257D4CB5D482D1809B5CBE4FA50F63498EDC6BE), Google Drive service (5F5BF577E12A6E1F17A5FF34DFE24534201D5816963F302204DEE01EF5E41DB7) and registration/facsimile service (0047A11E003DD39132A30B997E8AF2115CA103490E4EF607AE69B31378E9581E).

A synthetic President mandate/facsimile/certificate, synthetic DOCX, local PDF renderer and HTTP preemption mock exercised the actual get_issued_pdf_file(category, 1, ...) path. The mock intercepted every WordPress HTTP request; no Google request left the local PHP process. With the second (PDF) upload paused, two independent database reads both observed 0 issuance rows. A concurrent synthetic Administrator revoke waited on the shared lock; after upload release, issuance completed with a 7,703-byte PDF and revocation completed successfully (0.087 s). The resulting synthetic issuance and revoked certificate were then removed during exact cleanup.

Final cleanup restored the synthetic user's original role metadata, Drive settings and debug option; removed the test facsimile file and all synthetic rows. Issuance/facsimile/certificate/event/template tables each returned to 0 rows; wp db check passed. No product source changed in this retest. Reproducible setup/worker/revoker/orchestrator/cleanup helpers are the adjacent certificate-race-*.php and certificate-race-orchestrate.sh files. The earlier .59 historic note that its in-pause assertion was NOT RUN remains accurate for that run; this current-hash .66.b race test closes the carried acceptance gap.


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


## Current `.66.b` scope update — 2026-11-18
The President governance service-level positive lifecycle was re-run locally and passed with exact synthetic cleanup; candidate/runtime governance-class hashes match. This closes service authorization/decryption only, not the HTTP download route. The old CLI streaming helper was invalid (the product handler calls `exit`); the unguarded HTTP helper was inspected and not executed. No product-code defect is established by those helper failures.


## Local route harness boundary — 2026-11-18
Communications draft positive-path harness was rejected before callback execution because its baseline guard assumed the WordPress posts table was empty; the site has nine baseline posts. The helper was removed, and after-check confirmed announcement/notification rows remained 0, posts remained 9, and `wp db check` passed. President governance service-level PASS does not cover the HTTP download callback; its unsafe CLI/HTTP helpers were not counted as route evidence. See `reports/LOCAL_ROUTE_HARNESS_REVIEW_1.2.4.66.b_2026-11-18.md`.

## President governance document download — current local HTTP PASS — 2026-11-18
Test helper SHA-256: `BA24BEAC9921BE40F65622A2911BC97FA1CFA936D2A4B74B14C37E546E5C7590`. The first invocation failed before upload because the harness lacked a test-only document encryption key; it left no document/audit rows, removed the synthetic PDF, restored role metadata, destroyed its session, and passed cleanup assertions. The harness was corrected to use a fixed synthetic-only key in the CLI process and a temporary MU-plugin for the HTTP child request; existing fixture paths are refused and only test-created files are removed. `php -l` passed. The rerun on isolated local `.66.b` WordPress (`127.0.0.1:8103`, synthetic DB `zmp66b_isolated`) passed: exact active President authority, same-session route nonce, HTTP 200, curl error-free, PDF content type, body hash equal to the synthetic PDF, attachment disposition, `X-Content-Type-Options: nosniff`, document row/count and audit count restored, synthetic PDF and MU-plugin removed, capability metadata restored, and session destroyed. No product source changed; no external HTTP request, production database or active site was used. This closes the local positive HTTP download path only; denial/resource-tampering variants remain in the broader route matrix.

## Communications draft creation — current local synthetic PASS — 2026-11-18
Helper SHA-256: `A12250960FB171259382C9F5B49182F41EB0274CC330E373542B110745E792A9`. PHP lint passed. A synthetic Subscriber+Moderator with a valid WordPress `_wpnonce` invoked `ZMP_Communications::handle_publish()` for draft status. The row was saved as a draft for the exact synthetic actor; no WordPress post and no notification were created. Exact cleanup restored announcement, notification and post counts and removed the test user. `wp db check` passed. The initial harness attempt used a non-form nonce field and was rejected before any write; the corrected field passed. WP-CLI's redirect warning is expected from direct callback execution. This closes draft creation only; publish, post synchronization, recipient notifications, results-publication gate and UI remain open. Details: `reports/COMMUNICATIONS_DRAFT_POSITIVE_RUNTIME_1.2.4.66.b_2026-11-18.md`.

## Role Messenger moderator send — current local synthetic PASS — 2026-11-18
Helper SHA-256: `F6DE3628B232D81EBC799371AD6F34336C1028901B0D5510E60FCBB5E1E4E43E`. PHP lint passed. The current send handler accepted a synthetic Subscriber+Moderator and valid nonce for the moderator room. It stored the exact synthetic body/sender/scope, linked the first message to itself as the root thread, set retention expiry, and notified a second synthetic moderator. Cleanup removed all associated message/notification/read/attachment rows and both users; baseline counts were restored and `wp db check` passed. WP-CLI's redirect warning was expected from direct callback execution. Replies, attachments, exports/downloads, other role scopes and browser UI remain open. Detailed report: `reports/ROLE_MESSENGER_MODERATOR_SEND_RUNTIME_1.2.4.66.b_2026-11-18.md`.

## Moderator delivery-mode submenu — current local runtime PASS — 2026-11-18
Current source/runtime hashes matched for the moderator operations and portal shortcode classes. The isolated `.66.b` plugin folder was backed up before code synchronization. Helper SHA-256: `76b37841965be7474efdb6e866591c98dfc42f832384e2243628e8ff705ad486`. PHP lint passed. A synthetic account with both Subscriber and Moderator roles rendered the preserved main **Модератори** tab, the **Начин на одржување** submenu and selectors for active year, category, phase and region. For a regional music category, only physical/online options appeared. A refreshed Subscriber-only state hid the moderator tab and submenu. The valid-nonce local handler saved `online` for season 1/category 2/regional/polog and emitted the scoped actor audit record. Shutdown cleanup removed the event, audit row and synthetic user; no residual event/user was found and `wp db check` passed. The expected WP-CLI redirect warning is a harness artifact; all post-save and cleanup assertions passed. Detailed report: `reports/MODERATOR_DELIVERY_MODE_RUNTIME_TEST_1.2.4.66.b_2026-11-18.md`. This does not claim browser visual/accessibility acceptance, hybrid or state-mode checks, or live configuration.


## YouTube registration-fields local browser regression — PASS after fix — 2026-11-18
The local browser check exposed a real UI defect: every slot was labeled required in online mode, including the optional choir/orchestra composition and both band compositions. Fixed the label rule in `assets/js/zmp-entry-youtube-fields.js`: only vocal performance and the required choir/orchestra composition show required in online mode; choir/orchestra optional slots and both band slots remain optional. Chromium/Playwright rerun passed all 10 assertions across vocal/choir/orchestra/band in online and physical modes plus unconfigured mode, with no JavaScript exceptions. The fieldset activated and displayed the correct slots/guidance; local fixture had YouTube disconnected, and no form submission, upload, or outbound YouTube request occurred. Source/runtime asset SHA-256: `81054f7dcea06bf71b2ad52930c1468d9c5bfcd153e45fd68f63820b9f23b41e`; `node --check` passed. PHP shortcode render/enqueue and exact fixture cleanup are separately covered by `reports/youtube-registration-fields-render-check.php`. Exact output, setup, and limits: `reports/YOUTUBE_REGISTRATION_FIELDS_LOCAL_BROWSER_1.2.4.66.b_2026-11-18.md`. This does not resolve the prior `session_unknown` channel upload; owner confirmation remains required before retry.


## 2026-11-24 — member role stacks and President role preservation — PASS
Focused synthetic role/mandate runtime: 26/26 PASS on this phase; all 13 product PHP files matched this phase's loopback runtime source. Across four snapshots 52/52 phase/runtime file hashes matched and 54 PHP files linted. Full coverage and constraints: `reports/ROLE_STACK_AND_PRESIDENT_ROLE_PRESERVATION_1_2_4_66_b_2026-11-24.md`. This closes only the tested role/mandate defect; unrelated phase gates remain open.



## Owner-directed Drive video flow — 2026-11-25

Owner-directed design: replace the teacher-facing YouTube upload/link flow with plugin-managed upload to restricted ZMP Google Drive. The teacher sees the selected filename and size; client and server validate the actual video; upload begins only after “Поднеси пријава”. The form stays in place with animated staged progress and no refresh until Drive upload, object/metadata verification, and jury playback authorization all pass. The entry remains a draft on any failure and supports an exact retry.

The jury sees a small thumbnail card, not a Drive URL or file ID. Clicking it opens playback through the authenticated ZMP stream after exact commission assignment checks. Reduce file size with a quality-preserving validated compression profile before upload; keep the original privately until success. Preserve category slot rules and physical/online/combined requiredness. The full design and acceptance checklist are in reports/DRIVE_VIDEO_SUBMISSION_AND_JURY_THUMBNAIL_DESIGN_1.2.4.66.b_2026-11-25.md.

Status: DOCUMENTED / NOT IMPLEMENTED OR TESTED. Existing Drive OAuth, resumable upload, private media storage, and authenticated player are the starting mechanism; the current upload handler redirects and the jury view has no thumbnail card. Do not count the design note as a runtime PASS. The main PROJECT_ROADMAP.md remains unchanged.
