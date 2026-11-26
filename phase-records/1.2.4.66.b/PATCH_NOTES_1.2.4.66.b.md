## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Patch notes — ZMP 1.2.4.66.b

## 2026-11-12 — Owner-selected jury result step-up implementation
- Added authenticated AJAX option issuance for exact jury final-result context.
- Added one-time passkey verification before the result transaction; challenge context binds season, category, entry, phase, normalized score, qualification choice, factual basis and rationale.
- Reused the accessible WebAuthn interaction script for the jury form and added live status feedback. Updated profile guidance for the eligible roles.
- Extended passkey enrollment authorization to administrators and commission members assigned in the active season; registration option and completion handlers enforce the same eligibility.
- PHP and JavaScript syntax checks passed. Functional/UI verification remains NOT RUN; see `TEST_REPORT_1.2.4.66.b.md`.

## Owner theme and service cleanup — 2026-11-12
- Owner screenshot `Screenshot 2026-11-12 233814.png` confirms the `.66.b` jury page and reveals inline correction/disqualification controls. Added responsive layout rules for those controls as well as the finalization form.
- Copied the `Music Class` theme (version 0.2.5) from Owner-provided clone `Z:\Documents\Natprevari_ZMP\WSL\ZMP-Site\site\wp-content\themes\music-class` into the disposable `.66.b` test site and activated it. Removed the temporary `zmp-ui-harness` theme. The Owner clone itself was not modified.
- Verified the exact test page returns HTTP 200 (36,279 bytes), the Music Class stylesheet and plugin public CSS both return HTTP 200, and a synthetic WordPress shortcode render as user 116 shows the jury dashboard and finalization form with approved synthetic docs. PHP syntax check for the changed jury class PASS. These checks do not verify post-change Edge/mobile rendering or passkey submission.
- Stopped the old `.66.a` PHP web server PID 25642 on port 8101 and verified the process ended and port is free. Preserved `.66.a` MariaDB PID 409 and its database unchanged. Current `.66.b` PHP server PID 60347 remains on 8103; `.66.b` MariaDB PID 54934 remains on its private socket.
- Current candidate SHA-256: jury PHP `972F845A4ABE62606557841FADEFFB8AAA6F6F1F8B73CC07C7CB293343ED8D12`; public CSS `D6AEE4B03164E179309445ADEE4B30C6A1A6BB23630A993CC0268367BA56D803`.
- Next: Owner hard-refreshes `http://localhost:8103/?page_id=4&zmp_commission_tab=jury` in the same Edge tab and checks the assessment-correction/disqualification and finalization forms on desktop and Mobile View at 375×812. Browser screenshots after these changes and final-result submission remain NOT RUN.
## Verification record — 2026-11-12 23:48 Europe/Skopje
- Owner-confirmed browser passkey step-up and jury final-result submission succeeded in the isolated synthetic `.66.b` site; screenshots show the confirmed state. Read-only DB check found one confirmed result and one matching audit event. No additional product source change in this checkpoint.
- Owner accepts current Desktop UI. Mobile View remains NOT RUN. Phase remains IN PROGRESS / NOT_READY pending remaining phase gates.

## Mobile UI follow-up — 2026-11-12 23:52 Europe/Skopje
- Based on Owner screenshots at 375×679, adjusted `.66.b` mobile CSS for tab wrapping, compact filters, and readable horizontally scrollable result/criteria tables. This is an in-progress UI correction; post-change Owner verification is pending.

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

## Matrix upload correction — 2026-11-14
- Owner clarified that the defect is matrix/audio upload. The supplied screenshots show the upload modal with an audio file selected and a generic required-fields/upload error. No file or form submission was performed by the agent.
- Cause identified: the UI advertised a fixed 20 MB limit while the isolated PHP server previously used `upload_max_filesize=2M` and `post_max_size=8M`; the handler also collapsed upload-size errors into a generic validation message. The specific selected file size was not available, so exceeding 2 MB is a plausible cause, not a confirmed fact.
- Candidate fix in `includes/class-zmp-matrix-catalog.php` and `assets/js/zmp-matrix-catalog.js`: advertise and validate against the effective WordPress/server limit, use the JS file modification time for cache busting, detect oversized POSTs and PHP upload errors before generic validation, and return specific messages/statuses. Source hashes: PHP `97681421B5712CF16A97DB4E4B83C8487D4DDC40431AF7A151C23CF749DBFEC5`; JS `272ECF8278C6DE91A580C5464D0C47D40395FB813A587E0314CB7FE323215DB5`.
- The exact candidate files are present in the isolated `.66.b` runtime. PHP lint PASS; JavaScript syntax check returned success; `wp db check` PASS. Edge visibly rendered the matrix picker and upload modal with the 20 MB label. No Drive request, file upload, or matrix row write was made. End-to-end Drive upload remains NOT RUN pending Owner's Drive setup and test.
- Local PHP server PID `75729` listens on `127.0.0.1:8103` with per-process `upload_max_filesize=20M` and `post_max_size=24M`; MariaDB PID `54934` remains unchanged. WP-CLI reports its own separate PHP context and still shows the default 2 MB limit; it is not evidence of the web server's process settings. The visible browser label confirms the rendered web request currently sees the 20 MB effective limit.
- Phase remains IN PROGRESS / NOT_READY. No package, GitHub sync, or Auditor result is claimed.


## 2026-11-18 — malformed document-download requests
The registration-form and entry-document download callbacks now default absent GET IDs to 0 before nonce validation, preventing PHP undefined-key warnings and preserving fail-closed rejection. This change was found during the local 119-route negative runtime matrix; exact evidence is in 
reports/PRIVILEGED_ROUTE_NEGATIVE_MATRIX_1.2.4.66.b_2026-11-18.md.


## Messenger legacy index migration — 2026-11-18
Before dbDelta, detect and replace the obsolete nonunique scoped_threads index when its columns differ from the current contract. An unexpected unique index or unreadable metadata fails closed. Local reproduction of the 2026-11-18 dump's exact duplicate-key failure passed after repair, with synthetic row preservation. Owner test package: Z:\Documents\Natprevari_ZMP\ZMP_PROJECT_LIBRARY\zmp-competitions-1.2.4.66.b-MESSENGER-INDEX-FIX-TEST.zip.
# Follow-up: moderator dashboard in the main portal

- The moderator dashboard remains in the main site portal, not in a separate WordPress admin menu.
- Renamed its main portal tab to **Модераторска табла** and made it the default landing tab for non-administrator moderators. Administrators retain **Почетна** as their default.
- Existing role/capability checks and the nested **Преглед** operations panel remain authoritative. Per-region regional mode controls remain in that panel.
- Live user profile review confirmed the affected account has the moderator role. The prior production log's school-profile `forbidden` record concerns owner-only teacher profile data and is separate from moderator dashboard access.
- Source wiring has been updated. Live visual confirmation requires deploying this follow-up build and opening the main portal while signed in as the moderator.


## Owner correction — 2026-11-18: dedicated delivery-mode submenu

This entry supersedes the earlier 2026-11-18 note titled “moderator dashboard in the main portal.” Preserve the main menu label **Модератори** and the portal's prior default tab. The Owner requested a dedicated **Начин на одржување** submenu within the moderator menu, where the moderator selects year, category, competition phase, and region, then sets the delivery mode. Implemented as a focused panel backed by the existing event tables. Saving the mode updates only `delivery_mode`, `updated_by`, and `updated_at` on an existing event row, or creates the minimal scoped row; it preserves schedule, location, online details, and notes. Regional music categories allow only physical/online; other eligible stages allow physical/online/combined; state phase is fixed physical. Live visual and persistence verification remain pending.

Package artifact: ZMP_PROJECT_LIBRARY/zmp-competitions-1.2.4.66.b-MODERATOR-DELIVERY-MODE-SUBMENU.zip; archive root zmp-competitions-66b/ verified; SHA-256 69503067BAFC40F4AB4C534B527B9175814AEC9681AB7CCC7C3D7F5CDEF00750. The earlier MAIN-PORTAL-MODERATOR-DASHBOARD.zip is superseded by this package.

- 2026-11-24: Fixed exact-role-array checks that rejected authorized plugin roles when WordPress subscriber was also present. Extra plugin roles still fail closed. Local synthetic suites passed; see `reports/MIXED_WORDPRESS_ROLE_ACCESS_1_2_4_66_b_2026-11-24.md`.


- 2026-11-24: Corrected role combination handling for Teacher/President/Moderator/Commission/Testator/Organizer stacks with required Subscriber membership. President appointment now preserves existing roles; see `reports/ROLE_STACK_AND_PRESIDENT_ROLE_PRESERVATION_1_2_4_66_b_2026-11-24.md`.



## 2026-11-24 — Moderator delivery settings verified
Verified the moderator delivery-mode subtab in the main competition portal and the category/year/phase/region-scoped POST service locally. Regional music accepts only Physical or Online; Hybrid tampering fails closed. Runtime acceptance passed 11/11. Evidence: 
reports/MODERATOR_DELIVERY_MODE_PORTAL_ROUTE_1.2.4.66.b_2026-11-24.md. No product-source changes were required by this check.

## 2026-11-24 — circular invitation send capture
A synthetic approved campaign and reserved `.invalid` recipient completed the actual handler path with mail intercepted before transport. Sent state, token hash, exact cleanup and database integrity passed. Evidence: `reports/CIRCULAR_INVITATION_SEND_ROUTE_CAPTURE_1.2.4.66.b_2026-11-24.md`. No product source change was needed for this route.

## 2026-11-24 — Messenger reply route verification
The local synthetic reply route passed 7/7, including scoped root linkage, notification, retention-expiry cap and exact cleanup. A test-harness cleanup defect was corrected before the final run; product source was unchanged. Evidence: `reports/ROLE_MESSENGER_REPLY_ROUTE_1.2.4.66.b_2026-11-24.md`.

## 2026-11-24 — circular invitation and Messenger reply routes
Circular invitation send state passed with the reserved `.invalid` email intercepted before transport. Messenger reply passed scoped thread, notification, retention and cleanup checks; a harness cleanup defect was corrected before final PASS, with no product-source change. Evidence: `reports/CIRCULAR_INVITATION_SEND_ROUTE_CAPTURE_1.2.4.66.b_2026-11-24.md` and `reports/ROLE_MESSENGER_REPLY_ROUTE_1.2.4.66.b_2026-11-24.md`.

## 2026-11-24 — Messenger private attachment route verification
The current local candidate passed synthetic multipart reply upload, private storage integrity, authorized download, unassigned-user denial and scoped CSV export. Product code was unchanged; temporary test artifacts and users were removed. Evidence: `reports/ROLE_MESSENGER_ATTACHMENT_HTTP_1.2.4.66.b_2026-11-24.md`.

## 2026-11-24 — Messenger limited review and participant notices verified
The local synthetic request → exact-thread administrator review → participant notice/ack flow passed. No product-source change was required; an initial test-harness capture assertion was corrected before the final 7/7 PASS. Evidence: `reports/ROLE_MESSENGER_LIMITED_REVIEW_REQUEST_1.2.4.66.b_2026-11-24.md`.

## 2026-11-25 — Communications publish path verified
Local synthetic runtime verified moderator publication, exact recipient-notification rows, WordPress post synchronization and linked metadata with exact cleanup. No product code changed. Details: `reports/COMMUNICATIONS_PUBLISH_POST_SYNC_RUNTIME_1.2.4.66.b_2026-11-25.md`.

## 2026-11-25 — Communications saved-draft publication verified
Local synthetic test passed for publishing an existing general-announcement draft, exact notifications and linked WordPress post; cleanup and database integrity passed. No product code changed. Details: `reports/COMMUNICATIONS_DRAFT_REPUBLISH_RUNTIME_1.2.4.66.b_2026-11-25.md`.

## 2026-11-25 — Communications results-publication gate verified
Local synthetic direct and saved-draft handlers fail closed for an open phase with no results, with no notification or post writes and exact cleanup. No product code changed. Details: `reports/COMMUNICATIONS_RESULTS_PUBLICATION_GATE_1.2.4.66.b_2026-11-25.md`.

## 2026-11-25 — Communications linked-post update verified
Local synthetic publishing updated an existing linked WordPress draft in place without creating a duplicate; notices, cleanup and database integrity passed. No product code changed. Details: `reports/COMMUNICATIONS_LINKED_POST_UPDATE_1.2.4.66.b_2026-11-25.md`.


## 2026-11-25 — Communications confirmed-results gate verified
Positive synthetic legacy result publication passed after the phase was closed and a result row was present. Exact mentor notification, post linkage, cleanup, original phase NULL-field restoration and database integrity passed. No product code changed. Details: `reports/COMMUNICATIONS_CONFIRMED_RESULTS_PUBLISH_1.2.4.66.b_2026-11-25.md`.

## 2026-11-25 — Category result recipients follow the exact confirmed stage
Corrected Communications category-result notification selection to use the same unique season, exact phase eligibility, and disqualification scope used by the confirmed-results gate. This prevents notices to unqualified or draft category entries. The synthetic direct-publish runtime test passed with exact recipient assertions, linked public post, zero mail attempts, exact cleanup and clean database integrity. Source/runtime hash: `59F2593AB5F1D5FC534E8B5661CCCDBD7F1F785FA704E9895E4EE261504256F5`. Evidence: `reports/COMMUNICATIONS_CATEGORY_RESULTS_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md`; runtime output: `reports/COMMUNICATIONS_CATEGORY_RESULTS_RECIPIENT_SCOPE_RUNTIME_2026-11-25.txt`.


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

## 2026-11-25 — active .66.b local source sync
- The existing 8103 local WordPress was loading stale copies of 14 current candidate source files. Synchronized those exact code files into its active zmp-competitions-66b folder; backups are retained in local sandbox tmp for the remaining verification work.
- Hash-based source comparison now reports 69/69 active plugin source files equal to the candidate (0 mismatches). Candidate-only temporary-plugin source is outside the active plugin folder and remains a separate packaging/provenance question.
- Evidence: reports/ACTIVE_RUNTIME_SYNC_1.2.4.66.b_2026-11-25.md. Production and the main roadmap were not touched.

## 2026-11-25 — school email on current active runtime
- After syncing candidate source to the existing 8103 local runtime, the stacked active President email service flow passed 8/8 and the real authenticated admin-post approval passed 10/10. No real email was sent.
- Role metadata, existing active synthetic mandate, request/profile/audit rows, local wp-config bytes and HTTP-guard marker were restored; DB integrity passed.
- Current tested class and entrypoint hashes match candidate/runtime. Evidence: reports/SCHOOL_EMAIL_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md and its runtime outputs.
- Appointment/replacement with the current hash, role-denial/no-mutation matrix and UI remain open.

## 2026-11-25 — current-hash mandate callback retest
- Appointment, scheduled cancellation and active revocation callbacks pass on the current source, with exact audit/role assertions and cleanup; the existing active synthetic mandate was preserved.
- Evidence: reports/PRESIDENT_MANDATE_CURRENT_HASH_RUNTIME_1.2.4.66.b_2026-11-25.md. Negative role/nonce matrix and admin UI remain open.

- Appointment denial matrix now has current-runtime PASS for subscriber+valid nonce and administrator+invalid nonce; both were denied without mandate/audit mutation, exact cleanup and DB check passed.

## 2026-11-25 — Communications rollback evidence
- Forced synthetic WordPress-post insertion failure returned post_create_error and rolled back announcement/notification/post writes before cleanup. No email was attempted; exact cleanup and DB check passed.
- Notification-insert failure, category/phase variants and other rollback paths remain open. Evidence: reports/COMMUNICATIONS_POST_FAILURE_ROLLBACK_1.2.4.66.b_2026-11-25.md.

- A second synthetic rollback case forced the second notification INSERT to fail after the first succeeded. The transaction removed all partial communication rows; save_error, exact cleanup and DB check passed. Evidence is in the Communications rollback report.

- School-email decision denies ordinary Teacher and President-role-without-mandate users; invalid request-bound nonce is denied before mutation. The pending request/audit, synthetic profile data and diagnostic option were restored exactly; 5/5 PASS. Evidence: reports/SCHOOL_EMAIL_DECISION_DENIAL_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md.

- Current-runtime school-email approved contact disclosure passed 7/7: only the requester with the bound nonce received the reserved contact; the event omitted the address; other Teacher and invalid-nonce requests were denied without a disclosure event. Exact role/data cleanup and DB check passed.

- Changed school contact invalidates an old approval at disclosure time: stale address is withheld, approval hash/epoch cleared, contact_changed recorded without exposing either address. Current runtime disclosure fixture passed all 7 assertions including cleanup.


## 2026-11-25 — President appointment stacked-role eligibility
- Reworded appointment UI and service error message to require Subscriber while retaining allowed teacher/moderator/commission/testator/organizer roles.
- Excluded non-current stale President-role accounts from the candidate list; current President renewal remains eligible when member requirements pass.
- Candidate and active local runtime match; PHP lint and Edge visual check pass. Actual scheduled appointment callback retained every original role and wrote the expected single audit event; exact cleanup and DB check pass.
- Evidence: reports/PRESIDENT_MANDATE_STACKED_MEMBER_UI_AND_RUNTIME_1.2.4.66.b_2026-11-25.md. This is scoped; broader mandate and phase checks remain open.


## 2026-11-25 — school-email submission negative cases — local synthetic PASS 6/6
- Current service rejected too-short reason, self-request, requester identity mismatch, and subscriber-only requester without teacher role; exact request/event counts were unchanged for each.
- Actual submit admin-post callback rejected an invalid nonce without writing request/event rows.
- Synthetic users, mentors, schools and profile were removed; DB integrity passed; no email sent and active mandate unchanged. Tested service SHA-256 d8855bc141d5e1f854b751a10420eb90d04af9b6cbcd2f7a3e0da7664be796ce.
- Fixture: tests/school-email-submit-negative-current-runtime.php, SHA-256 6d09da5ad727113b12c08db77cc1bef9bb54aab5b0bc7555a923276d1132cde8. Evidence: reports/SCHOOL_EMAIL_SUBMIT_NEGATIVE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md.
- Other submit variants, UI and wider school-email/phase acceptance remain open.


## 2026-11-25 — President candidate filter verification
- Verification-only: current candidate filter includes allowed stacked member roles, excludes stale President-role accounts and Administrator. PASS 4/4, exact synthetic cleanup and DB check. No source change in this supplemental check.
- Evidence: reports/PRESIDENT_MANDATE_STACKED_MEMBER_UI_AND_RUNTIME_1.2.4.66.b_2026-11-25.md and tests/president-mandate-candidate-filter-current-runtime.php.
\n\n## 2026-11-25 — school-email submission positive-route verification\n- Verification-only: valid authenticated submit callback creates one pending request and submitted event; requester can read it; email absent from audit and redirect. Exact cleanup and DB check passed. No source change. Evidence: reports/SCHOOL_EMAIL_SUBMIT_POSITIVE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md.\n\n\n## 2026-11-25 — school-email boundary verification\n- Verification only: additional missing-contact, owner-school, contact-role, foreign school ID and overlong-reason denials passed without request/event mutation. Exact cleanup and DB check passed. No source change.\n\n\n## 2026-11-25 — school-email submit idempotency verification\n- Verification only: same-scope duplicate request reused the existing pending row; one submitted event and original reason preserved. Exact cleanup and DB check passed. No source change. Evidence: reports/SCHOOL_EMAIL_SUBMIT_IDEMPOTENCY_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md.\n\n\n## 2026-11-25 — President active replacement verification\n- Verification-only: current active mandate replaced with stacked synthetic successor; exact audit/role assertions and baseline restoration passed 7/7 with DB check. No source change. Evidence appended to President stacked-member runtime report.\n\n\n## 2026-11-25 — school-email UI render verification\n- Verification-only: actual shortcode rendered teacher request form/status and active-President queue; address absent before approval. Exact cleanup and DB check passed. Not browser/mobile visual acceptance; no source change. Evidence: reports/SCHOOL_EMAIL_ACCESS_UI_RENDER_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md.\n\n\n## 2026-11-25 — school-email disclosure route verification\n- Verification-only: actual local requester-bound disclosure endpoint passed with 200 no-store/noindex response; synthetic address omitted from audit; exact cleanup and DB check. No source change. Evidence in the dated disclosure report.\n\n\n## 2026-11-25 — school-email disclosure HTTP denials verification\n- Verification-only: other Teacher and invalid-nonce requests denied at actual route with 403 and no address/event leak; valid requester 200 no-store/noindex. Combined fixture pass 9/9, exact cleanup/DB check. No source change.\n\n\n## 2026-11-25 — audio malformed input route verification\n- Verification-only: malformed category save and sanitized-empty ID archive denied without option mutation; full fixture/cleanup/DB check passed. No product source change. Evidence in dated audio admin-post report.\n
## Audio admin screen verification — 2026-11-25
Verification-only; no product source change. The actual local admin screen rendered in Edge and showed the complete add-tool form, validation-required fields, category choices, and expected empty catalog. No browser console errors/warnings. Evidence: `reports/AUDIO_CATALOG_ADMIN_UI_EDGE_2026-11-25.md`. Responsive and role-matrix checks remain open.

## School-phase results support — 2026-11-25
Fixed a mismatch where the Communications UI and season configuration exposed a school phase but Jury stage resolution rejected it. School is now included in configured stage resolution/rendering, passkey result-context validation, and the Teacher results phase filter. PHP lint and focused local runtime/regression checks passed; candidate/runtime hashes match. Full authenticator ceremony for this phase remains unverified. Evidence: `reports/COMMUNICATIONS_KNOWLEDGE_SCHOOL_PHASE_1.2.4.66.b_2026-11-25.md`.


## Messenger role/scope verification — 2026-11-25
Verification-only; no Messenger product source changed in this check. Focused authorization matrix PASS 13/13; exact cleanup, DB integrity and local HTTP passed. Evidence: 
eports/ROLE_MESSENGER_SCOPE_MATRIX_1.2.4.66.b_2026-11-25.md. Broader Messenger gates remain open.



## Messenger schema readiness — 2026-11-25
Verification-only; no migration or product source change. Current local schema, marker and retry state PASS 7/7 with unchanged rows/options. Production status remains unverified. Evidence: 
eports/MESSENGER_SCHEMA_READINESS_RUNTIME_1.2.4.66.b_2026-11-25.md.



## Regional-only Messenger scope verification — 2026-11-25
Verification-only; no product source change. Exact regional-only assignment scope PASS 5/5 with cleanup and DB integrity. Evidence: 
eports/ROLE_MESSENGER_REGIONAL_ONLY_SCOPE_1.2.4.66.b_2026-11-25.md.



## President eligibility role contract — 2026-11-25
Require subscriber and zmp_teacher in candidate filtering, mandate eligibility, and central President-role granting. Existing additional member roles remain permitted and preserved. Candidate/runtime hash-matched local verification passed. Evidence: 
eports/PRESIDENT_TEACHER_SUBSCRIBER_RULE_1.2.4.66.b_2026-11-25.md.



## President dashboard member-role compatibility — 2026-11-25
Dashboard access now accepts the required Subscriber + Teacher + President stack and approved additional roles while retaining active-mandate/admin checks. Candidate/runtime hash-matched; supplemental 8103 checks PASS. Evidence: 
eports/PRESIDENT_STACKED_ROLE_DASHBOARD_1.2.4.66.b_2026-11-25.md.

