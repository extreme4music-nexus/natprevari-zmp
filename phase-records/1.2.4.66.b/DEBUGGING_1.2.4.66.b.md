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


# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Debugging guide — ZMP 1.2.4.66.b

## CURRENT INHERITED-FIX STATUS — 2026-11-25

The `.65` stale-schema retry and Administrator + Teacher profile corrections are now in `.66.b`. Its pre-existing integer-type normalizers and schema diagnostics were preserved. Local loopback results: role/profile 11/11, retry 2/2, partial-schema fail-closed 4/4 PASS. Details and hashes: `reports/INHERITED_65_FIXES_AND_66B_OPEN_SCOPE_1.2.4.66.b_2026-11-25.md`.

The President must retain both Subscriber and Teacher for teacher-scoped profile and subject flows. The President-role guard accepts approved additional roles, and an account without Teacher must fail the Teacher-only path. Do not diagnose a remaining production Messenger warning from these local results; live schema/version evidence is still required.

The owner-directed Drive upload, compression, progress, sharing validation and jury-thumbnail flow is documented but remains **NOT IMPLEMENTED OR TESTED**.

## Moderator delivery-mode submenu — local status 2026-11-18

The main portal tab remains **Модератори**. Inside that workspace, **Начин на одржување** lets an authorized moderator select season, enabled category, available phase, and (for regional phase) region. For regional music, only physical/online is valid; state phase is fixed physical; other supported phases permit physical/online/hybrid. Save requests require moderator capability and the moderator-operations nonce and are scoped to an active season/category/phase. Local synthetic render/save/readback and exact cleanup passed; browser visual/accessibility review and nonregional/state variants remain unverified. See `reports/MODERATOR_DELIVERY_MODE_RUNTIME_TEST_1.2.4.66.b_2026-11-18.md`.

## Database schema mismatch diagnostics — added 2026-11-18
When the School Engagement, School Profile, or School Email Access checks fail, the Debugging log now records the table and bounded schema details: missing columns/indexes; column type, nullability, or default mismatches; and index columns/uniqueness mismatches. Integer display widths are normalized for compatibility with MySQL/MariaDB reporting. The checks remain fail-closed. No profile values, emails, row contents, credentials, or SQL statement text are logged. Trigger a single profile page read or plugin schema retry with synthetic data, then filter the log for `schema mismatch details`. Remove the entries after diagnosis only through the existing log retention/clear controls. The behavior has not yet been runtime tested on the Owner's production database.

For the Messenger retention warning, filter for `Plugin schema prerequisite not ready.` to identify which shared schema prerequisite blocked the commit; filter for `Messenger schema mismatch detail.` for exact Messenger table/column/index shape. The warning is shared by Messenger, school profile, school email, subject proposal, and admin facsimile schema migration failures, so it does not by itself prove that a Messenger table is defective. Local controlled `trigger_type` mismatch and idempotent repair evidence is in `reports/MESSENGER_SCHEMA_DIAGNOSTICS_1.2.4.66.b_2026-11-18.md`. Production remains unverified until the Owner installs this test ZIP and triggers one retry.

The Owner's export review found the 17:23 activation failed the `messenger` and `admin_facsimile` prerequisites; school engagement was successfully created at 17:18:59 and the later profile errors were `forbidden`. The export had no per-schema details, so the next test build also records Messenger `dbDelta`/retry-intent failure categories and the exact admin-facsimile table/column/index mismatch. See `reports/OWNER_DIAGNOSTIC_EXPORT_REVIEW_1.2.4.66.b_2026-11-18.md`.

## Jury final-result passkey confirmation (in progress)

Expected behavior: when an eligible commission member assigned to the exact entry/phase or an Administrator submits a final result, the browser requests a fresh passkey assertion. Only after server verification does the existing result transaction write the final result and audit record. A passkey does not grant a role or assignment.

Safe diagnostic steps: confirm the logged-in synthetic user and current assignment; confirm the active season/category/entry/phase; inspect only HTTP status and the fixed UI status message; verify whether the final-result row and audit row changed using synthetic test data. Recheck database identity before writes.

Common failure causes: no enrolled credential; unsupported/insecure browser context; missing or stale assignment; inactive category/season; expired, consumed or replayed challenge; changed score, qualification, factual basis or rationale; unavailable challenge schema/library; database write failure.

Sensitive values never to log or report: password, passkey credential ID/raw ID, assertion JSON, challenge ID/secret, session token/cookie, nonce, user personal data, or full factual/rationale text. Preserve only redacted status codes and whether the mutation occurred.

Current evidence: PHP and JS syntax checks PASS only. Functional browser/database behavior is NOT RUN.

## Jury finalization form layout and document readiness — in progress
Expected behavior: finalization labels and controls are visually separated; score input and both explanations remain usable at desktop and narrow mobile widths. The finalization card is rendered only when all required documents are approved. A direct request is rejected if a required document becomes pending after the page was loaded.

Safe diagnostic steps: hard-refresh the synthetic page, confirm the public CSS request succeeds, check the document status row, and inspect whether the finalization card appears only after all required document rows show approved. Use only synthetic entries. After a passkey attempt, record only the fixed status message and whether a synthetic result row changed.

Common causes: cached CSS; missing required document/review rows in a test fixture; a review status changed after page render; unsupported/stale session; passkey challenge expired or bound payload changed. Do not log passwords, credential IDs/assertions, challenge IDs/secrets, nonce, session cookies, personal data, or full factual/rationale text.

Current evidence: Owner desktop screenshots confirmed the old crowded form. Synthetic PHP render checks verified pending-doc hide and approved-doc show. Edge after the CSS fix, mobile rendering and passkey submission remain NOT RUN.
## Owner theme and service cleanup — 2026-11-12
- Owner screenshot `Screenshot 2026-11-12 233814.png` confirms the `.66.b` jury page and reveals inline correction/disqualification controls. Added responsive layout rules for those controls as well as the finalization form.
- Copied the `Music Class` theme (version 0.2.5) from Owner-provided clone `Z:\Documents\Natprevari_ZMP\WSL\ZMP-Site\site\wp-content\themes\music-class` into the disposable `.66.b` test site and activated it. Removed the temporary `zmp-ui-harness` theme. The Owner clone itself was not modified.
- Verified the exact test page returns HTTP 200 (36,279 bytes), the Music Class stylesheet and plugin public CSS both return HTTP 200, and a synthetic WordPress shortcode render as user 116 shows the jury dashboard and finalization form with approved synthetic docs. PHP syntax check for the changed jury class PASS. These checks do not verify post-change Edge/mobile rendering or passkey submission.
- Stopped the old `.66.a` PHP web server PID 25642 on port 8101 and verified the process ended and port is free. Preserved `.66.a` MariaDB PID 409 and its database unchanged. Current `.66.b` PHP server PID 60347 remains on 8103; `.66.b` MariaDB PID 54934 remains on its private socket.
- Current candidate SHA-256: jury PHP `972F845A4ABE62606557841FADEFFB8AAA6F6F1F8B73CC07C7CB293343ED8D12`; public CSS `D6AEE4B03164E179309445ADEE4B30C6A1A6BB23630A993CC0268367BA56D803`.
- Next: Owner hard-refreshes `http://localhost:8103/?page_id=4&zmp_commission_tab=jury` in the same Edge tab and checks the assessment-correction/disqualification and finalization forms on desktop and Mobile View at 375×812. Browser screenshots after these changes and final-result submission remain NOT RUN.
## Owner-confirmed synthetic verification — 2026-11-12 23:48 Europe/Skopje
- Expected behavior: submitting a valid jury finalization asks for passkey confirmation; after Owner confirms the browser ceremony, the UI reports a confirmed result and the isolated DB contains one result plus one `result_confirmed` audit event.
- Observed: Owner confirms the prompt appeared and succeeded. Supplied screenshots show confirmed status. Read-only DB verification found exactly one scoped result (92.50 average, 100.00 final, first award) and one audit event; WP DB check passed. No PIN or prompt screenshot is needed.
- UI status: Owner accepts Desktop rendering. Mobile View has not been checked in this evidence set. If investigating a mobile-only layout issue, use the same synthetic page and capture the form/status at the target viewport; do not repeat the completed result submission unless the relevant code or data changes.

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

## YouTube resumable-session uncertainty — 2026-11-13
Expected behavior: a valid synthetic regional-online draft upload creates one resumable session, uploads the clip once, stores the returned video ID with `privacy_status=unlisted`, then reports `processing` or `ready`. The Owner may delete the resulting synthetic video from the connected channel after success.

Observed safe diagnostic: one Owner-authorized synthetic upload attempt reached the real `ZMP_YouTube::upload_entry_video()` handler and returned its HTTP 302 redirect. The isolated media row ended `session_unknown`; video ID, privacy status, encrypted session URL are empty and upload offset is zero. A separate read-only `channels.list` request returned HTTP 200. This does not prove upload authorization or that a YouTube video exists. Do not blindly retry or clear `session_unknown`; first resolve whether a corresponding upload exists in the channel and preserve the current row until then.

Likely failure boundary: resumable session initialization or encrypted-session persistence, before video bytes were transferred. The handler intentionally fails closed but currently reduces distinct start errors to `session_unknown`; it did not preserve an HTTP status or safe Google error category. This evidence does not identify the exact Google-side cause. A future diagnostic should retain only non-secret transport status/error category; never log access/refresh tokens, client secret, resumable Location URL, upload body, participant data, or full API response.

## Temporary entry-video form add-on — 2026-11-13
- Expected: on the teacher "Нови пријави" tab, selecting a music category and school shows one video selector for vocal interpretation, two for choir/orchestra, and two for bands. Online region explains the required finalization gate; physical region says the videos are optional.
- After selecting files and submitting, the existing registration handler first creates the entry. The add-on then uses the returned authenticated teacher "Мои пријави" row and its existing upload nonce/action for each selected slot. The main plugin remains authoritative for upload validation, resumability, privacy, and finalization.
- If upload does not complete, inspect the row's per-slot status before retrying; do not blindly resubmit. An uncertain session is escalated for status inspection. No filenames, OAuth values, file contents, or video links should be added to logs/reports.
- The add-on creates no database tables or options. Browser/UI/upload behavior has not yet been observed; PHP and embedded-JS syntax checks alone do not prove functionality.

## Entry-time YouTube fields in “Нови пријави” (in progress)
Expected behavior: selecting vocal interpretation shows one performance video selector; choir/orchestra show two composition selectors; bands show two optional composition selectors. For online regional mode, the existing entry remains a draft until the required videos are processed and verified; for physical mode, videos are optional. When the channel is disconnected, optional video selection must not block physical entry submission, while the core server-side mode gate remains authoritative.

Safe diagnostics: confirm selected school/category and the displayed regional mode; inspect only the fixed progress/status text and the resulting entry's per-slot status in “Мои пријави”. Do not repeat an upload when its outcome is uncertain. The core upload handler owns validation, resumable session state, Unlisted verification, processing state and finalization. Never log file contents, OAuth values, video links/IDs, participant names, or school/contact data.

Owner confirmed that the fields appeared, one video upload succeeded and appeared on the channel, and the application completed. This is an Owner-confirmed event only; it does not establish unreported mode, privacy/processing, or juror playback details. The `.66.b` candidate integration was PHP-linted but not browser-exercised in this checkpoint.

### Live `.66.b` form recheck — 2026-11-18
The logged-in teacher page showed the vocal-interpreter category selected, but no visible video inputs. Read-only DOM inspection found the expected video fieldset present with `hidden`, no initialization marker, and no generated slots. The page console had a JavaScript `SyntaxError` whose exact source was not isolated. The rendered config marked every music category `unconfigured` for the selected school, so the moderator must also create/set the regional mode for each category and region. The channel-connection flag was true. No entry was submitted and no file uploaded.

Candidate correction moves the initializer to a versioned enqueued JavaScript asset and passes escaped mode data on the fieldset. It keeps the fieldset visible for supported categories, displays the right slot count, and disables file selection until the regional mode is explicitly `online` or `physical`; online finalization remains mandatory for all slots, while physical entries remain optional. PHP and JavaScript syntax checks pass. This candidate has not been installed or browser-verified on the live site; end-to-end video upload remains NOT RUN.

## Matrix upload correction — 2026-11-14
- Owner clarified that the defect is matrix/audio upload. The supplied screenshots show the upload modal with an audio file selected and a generic required-fields/upload error. No file or form submission was performed by the agent.
- Cause identified: the UI advertised a fixed 20 MB limit while the isolated PHP server previously used `upload_max_filesize=2M` and `post_max_size=8M`; the handler also collapsed upload-size errors into a generic validation message. The specific selected file size was not available, so exceeding 2 MB is a plausible cause, not a confirmed fact.
- Candidate fix in `includes/class-zmp-matrix-catalog.php` and `assets/js/zmp-matrix-catalog.js`: advertise and validate against the effective WordPress/server limit, use the JS file modification time for cache busting, detect oversized POSTs and PHP upload errors before generic validation, and return specific messages/statuses. Source hashes: PHP `97681421B5712CF16A97DB4E4B83C8487D4DDC40431AF7A151C23CF749DBFEC5`; JS `272ECF8278C6DE91A580C5464D0C47D40395FB813A587E0314CB7FE323215DB5`.
- The exact candidate files are present in the isolated `.66.b` runtime. PHP lint PASS; JavaScript syntax check returned success; `wp db check` PASS. Edge visibly rendered the matrix picker and upload modal with the 20 MB label. No Drive request, file upload, or matrix row write was made. End-to-end Drive upload remains NOT RUN pending Owner's Drive setup and test.
- Local PHP server PID `75729` listens on `127.0.0.1:8103` with per-process `upload_max_filesize=20M` and `post_max_size=24M`; MariaDB PID `54934` remains unchanged. WP-CLI reports its own separate PHP context and still shows the default 2 MB limit; it is not evidence of the web server's process settings. The visible browser label confirms the rendered web request currently sees the 20 MB effective limit.
- Phase remains IN PROGRESS / NOT_READY. No package, GitHub sync, or Auditor result is claimed.


### 2026-11-18 local Messenger installation check
.66.b was activated on an isolated empty local database under /home/tonic/zmp-isolated-test. Its Messenger installer and compatibility check passed and created the four expected indexes. This proves clean-install behavior only; production continues to report duplicate_key, which cannot be diagnosed from this clean schema or the October dump. Read-only live index metadata is still required. See 
reports/LOCAL_CLONE_INSTALL_MESSENGER_SCHEMA_1.2.4.66.b_2026-11-18.md.



### 2026-11-18 duplicate scoped_threads index — local reproduction and repair
The Owner's 19:44 SQL dump shows scoped_threads(scope_type,season_id,category_id,thread_id,id), missing 
egion_key. An isolated local migration reproduced Duplicate key name 'scoped_threads' exactly. .66.b now replaces this obsolete nonunique index before dbDelta, refuses unexpected unique-index replacement, and keeps failures closed. Synthetic row preservation, schema compatibility, PHP lint and full local DB check passed. The live DB still needs plugin update and post-retry confirmation; automated retention must remain paused until the Messenger prerequisite is ready. See 
reports/LOCAL_CLONE_INSTALL_MESSENGER_SCHEMA_1.2.4.66.b_2026-11-18.md.

# Moderator delivery-mode submenu (2026-11-18 follow-up)

The main portal menu remains **Модератори**. Inside that workspace, **Начин на одржување** selects year, active category, available competition phase, and a region for regional competitions. Save uses `save_delivery_mode`; it updates only mode and audit metadata on an existing event, preserving schedule/location/online details/notes. Regional music mode must be explicitly physical or online per region; state remains physical; other eligible phases allow physical, online, or combined. After installing the follow-up ZIP, verify the submenu and scoped save/readback with a moderator account. Live verification is pending.



## 2026-11-24 — Moderator delivery panel local trace
The synthetic Moderator+Teacher+Subscriber stack rendered the main portal tab and delivery subtab. Direct loopback POSTs persisted distinct category/region modes; invalid regional-music Hybrid was rejected, with no unexpected outbound request. Fixture/data/guard cleanup and database integrity passed. Browser screenshot capture is separately NOT RUN because desktop URL identification failed. See 
reports/MODERATOR_DELIVERY_MODE_PORTAL_ROUTE_1.2.4.66.b_2026-11-24.md.


## Owner-directed Drive video flow — 2026-11-25

Owner-directed design: replace the teacher-facing YouTube upload/link flow with plugin-managed upload to restricted ZMP Google Drive. The teacher sees the selected filename and size; client and server validate the actual video; upload begins only after “Поднеси пријава”. The form stays in place with animated staged progress and no refresh until Drive upload, object/metadata verification, and jury playback authorization all pass. The entry remains a draft on any failure and supports an exact retry.

The jury sees a small thumbnail card, not a Drive URL or file ID. Clicking it opens playback through the authenticated ZMP stream after exact commission assignment checks. Reduce file size with a quality-preserving validated compression profile before upload; keep the original privately until success. Preserve category slot rules and physical/online/combined requiredness. The full design and acceptance checklist are in reports/DRIVE_VIDEO_SUBMISSION_AND_JURY_THUMBNAIL_DESIGN_1.2.4.66.b_2026-11-25.md.

Status: DOCUMENTED / NOT IMPLEMENTED OR TESTED. Existing Drive OAuth, resumable upload, private media storage, and authenticated player are the starting mechanism; the current upload handler redirects and the jury view has no thumbnail card. Do not count the design note as a runtime PASS. The main PROJECT_ROADMAP.md remains unchanged.
