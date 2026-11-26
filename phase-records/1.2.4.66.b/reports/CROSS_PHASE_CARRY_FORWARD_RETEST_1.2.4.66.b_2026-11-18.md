# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Cross-phase carry-forward retest — ZMP 1.2.4.66.b

Date: 2026-11-18 (Europe/Skopje)
Scope: Local synthetic verification of open carry-forward items identified from .53/.60, .55, .65 and .66.a. No production/active site, YouTube account, GitHub remote, or external API was opened. The main PROJECT_ROADMAP.md was not changed.

## Result summary

| Carry-forward item | Result in .66.b |
|---|---|
| .53/.60 Messenger trigger_type capacity and retry behavior | PASS on tested paths. Simulated old VARCHAR(20); activation migrated it to VARCHAR(32). All nine table contracts passed. Immediate and scheduled audit writes passed; a narrow-schema failure denied writes while establishing a 300-second retry. |
| .55 future automatic-check timestamps | PASS. Current is fresh; future, older-than-14-days and invalid timestamps are stale. |
| .55 required privacy/terms URL | PASS for server-side rejection. Admin POST with both fields absent was rejected and the approved-tools option hash was unchanged. |
| .55 mobile UI | Previously accepted by Owner for the .66.b jury view. No code changed; broader mobile review across roles/screens was not performed by this retest. |
| .55 broad AJAX/POST authorization and regression matrix | Still open. Existing inventory is partial/static; this run did not test every route. |
| .65 subject catalog normalized type/timestamp migration | PASS on .66.b runtime. Missing unique index failed compatibility closed; restoration reopened it. A seeded legacy row with NULL timestamps was preserved and backfilled to NOT NULL by installation. |
| .65 Teacher-to-President proposal route and outbound marker | Current .66.b Teacher submission route PASS. Synthetic admin-post created one pending proposal and event; local HTTP interceptor recorded no outbound HTTP. Old .65 R13 outbound-marker attribution remains historically unresolved. President decision/passkey success was not exercised. |
| .66.a Teacher profile save/readback | PASS at local callback/service level. Synthetic profile save/readback succeeded, then fixture rows were removed. This is not browser evidence. |
| .66.a partial passkey schema fail-closed injection | PASS. One exact challenge-table DDL was intercepted; installation returned false, marker stayed absent, challenge creation failed closed, and child database was restored. |
| .65 historical AJAX Origin log | Unattributable / not actionable from available evidence. Historic record lacks action and source site/version; no endpoint was inferred or tested. |
| .65 GitHub provenance gap | Not checked. This retest stayed local and did not read or change a remote branch. |

## Test evidence

### Messenger — class-zmp-role-messenger.php

Candidate SHA-256: 832F563E6202C118B908DB5B21E5C66DABCC023C01A796DEBEDAF257E4B5C1BE.

- Local reactivation passed. In the isolated .66.b database, changing the empty audit table to legacy VARCHAR(20) and reactivating restored VARCHAR(32); database integrity passed.
- schema_is_compatible(true) and retention_audit_schema_is_compatible(true) returned true across the full nine-table contract.
- Direct audit insert of early_delete_requested (22 characters) was read back exactly and removed; count returned to 0.
- Exercised the actual retention transaction method with synthetic message rows: immediate path wrote early_delete_requested and set an immediately due expiry; scheduled path wrote deadline_set and set a future expiry. Both synthetic messages and audit rows were deleted; counts returned to 0.
- Narrowed the audit column to legacy VARCHAR(20) with no audit rows. Schema predicates rejected it, the operation returned the fail-closed 503 result, no expiry or audit write occurred, and retry pending/deadline were established. The column, retry options and test row were restored; schema predicates returned true.
- Retry test established exactly 300 seconds, blocked retry before the deadline, and preserved the same deadline on repeated failure. Retry options were restored. Reactivation above verifies the immediate activation retry path.
- Full WordPress database check passed after restoration.

### Approved audio tools — class-zmp-approved-audio-tools.php

Candidate SHA-256: 10641166B008302FD12A05CD8AD0372DB30ECB5B8B7B31AF626475C17FA8F1E7.

- Runtime freshness results: current 1, future 0, 15-day-old 0, invalid 0.
- Empty public URL helper returned empty.
- Admin save handler with a valid local admin/nonce and missing URLs was rejected; approved-tools option hash was unchanged. The first harness attempt omitted $_REQUEST and stopped at WordPress nonce validation; it made no write. The corrected invocation reached application validation and confirmed no mutation.
- No valid external URL was opened or probed.

### Subject catalog and proposals

Candidate SHA-256 values:

- class-zmp-mentor-subjects.php: D9D6BA3B5DD56C20E4CBB1E830A44A3210824B18DA3F7C3A9E920CDEEF87C245
- class-zmp-mentor-subject-proposals.php: 8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69
- class-zmp-mentor.php: 897298C52A8317378392245A68A3DCFD09B60E06A35EDDDB32D0E1433FC03FC2

- Catalog and proposal installers returned ready; proposal schema compatibility was true.
- Removing the proposal unique index caused compatibility false; restoring it returned compatibility true.
- A synthetic legacy catalog row with NULL created_at/updated_at survived install with both timestamps backfilled and NOT NULL. The exact row was removed; catalog count returned to baseline 0.
- Wrong-actor submission was denied. A Teacher service submission created one pending proposal and one audit event; both were removed, counts returned to baseline.
- The registered admin_post_zmp_submit_subject_proposal handler was invoked with synthetic Teacher 117 and valid local nonce. It created one pending proposal and one submitted event. A pre_http_request interceptor blocked every WordPress HTTP request; capture was empty. Exact proposal/event were deleted. WP-CLI emitted its expected redirect warning because the handler calls wp_safe_redirect and exits in CLI; this was not a browser/HTTP 302 observation.
- The old .65 R13 record remains FAIL/UNRESOLVED for attribution of its blocked outbound marker. Current .66.b route evidence does not rewrite that historic result or test President passkey approval.

### Teacher profile

Candidate SHA-256 values:

- class-zmp-school-profile-ui.php: FFDC2C810280924E5217CDB4D4083D5B49C6EE02D8194479278CAECD0F450F26
- class-zmp-mentor-school-profile.php: BED1C58E6D895FA04907D6C53CAA9494946FBF0BF53C13E115D0AF8A834F6C31

A temporary mentor/school association for synthetic Teacher 117 was created. Self-edit capability was true; the profile save callback saved synthetic values, readback matched, and exact profile/school/mentor rows were removed. Database integrity passed. This closes a local callback/service check, not the prior browser-render/submission gate.

### Passkey partial-schema injection

Candidate class-zmp-passkey-store.php SHA-256: F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB. The .66.a child/runtime and .66.b candidate hashes match exactly.

- Used child sandbox /home/tonic/zmp-local-sandbox/phase66a-schema-create-20261111, whose passkey credential/challenge tables were empty. Whole-database recovery dump SHA-256: e2cfc53621d4e149e745afc3f40646a09654857ec08ea9d30429bf3617444625, mode 0600.
- Temporary MU-plugin removed only the named automatic schema callback and replaced exactly one CREATE TABLE z66a_zmp_passkey_challenges query with harmless SELECT 1. Interception count 1; unexpected DDL match count 0.
- Install returned false; schema compatibility false; marker absent; credentials table present with 0 rows; challenge table absent; representative create_challenge() returned null.
- Injector was removed before database-only restore. Restored marker was 3, both passkey tables were empty, compatibility true, .66.a plugin active again, and database integrity passed.
- Main .66.b pre-retest dump was mode 0600, SHA-256 53be581927de83cf7c70ebc7d3c2b6bc985156b48d89187e8277dce580216a20. It contained four credential rows and one challenge at preflight; those were never touched. Final counts remain 4/1.

## Exact test helper hashes

Helpers remain under /home/tonic/zmp-local-sandbox/phase66b-test/tmp/; the passkey injection helper remains in the child sandbox tmp/. Development-only; exclude from an installation ZIP.

- audio freshness: f7f89c7c21c3af4b267537ec8d1732ddf9b792ea28015bf6627068d8a4abd285
- audio server POST: c93675f23e9f11d36dd78c55d4f5acf844dc82d82d0282529f4f8fbe071ed2a5
- Messenger schema/audit: a0a41abc503cfb970306ad23f5a39afc47ab6bf36862f38fea9d9f0b907e3b69
- Messenger retry: 55de9cac2f850c26cb06fb32f002857a91a56f6dfdecfe24123d462ce894bb14
- Messenger audit flows: 331fd267423e48d79f7ca8132c6a0390b047bff2babcea03d50f123f2d410d33
- Messenger fail-closed: 326facc5e0379d010f17706e40d07c7bd32de3319b56514f425016a69d829763
- subject schema: d1662f1404addb2c959eb0bb36a9db757f77bb358e48db128a3355a4ed38acde
- partial proposal schema: d45d305c9d9394503d364df9f9e436693922bd4abdafbc6cf07c04b4eb9a41c2
- subject legacy migration: 7046b6ad26dbd43278aa6d93cc92d8691068d5fc5679c2c5673db075ddfbe3e4
- subject service: f02359f578eca0b284ddb56684947a7b48f724ca16027b194fe02387bce87382
- subject route + HTTP guard: e6b458ace4e14e858a96c4a294f4ad3397b9e3d24568715547ed567d005a5fe6
- school profile: cca1addf8260b2768d864f1d7d4cbee4ecaed62f77ba7cb91e5d97f3c3916ad8
- passkey injection: fea0c49d0eec04718f75bf5f97dc6a78de531fe04238c4ad6b1bd213f7d371b8

## Remaining work and limitations

1. Complete .55 direct AJAX/POST authorization and functional regression matrix remains open; this retest sampled specific inherited flows only.
2. President subject-proposal passkey success/browser flow remains unverified. Teacher submission success and expected denial do not prove the President decision route.
3. Historical .65 outbound-marker cause and historical AJAX Origin source/action remain unresolved/unattributable.
4. .65 GitHub provenance was not checked because this work was local-only.
5. .66.b still has separate whole-phase route/review/release and YouTube-related gates. No active site/YouTube account was opened and no ZIP was produced.

**Phase status after this report:** .66.b remains **IN PROGRESS / NOT_READY**. Tested carry-forward paths passed; no broader phase acceptance or historical .65 Auditor closure is claimed.


## Jury qualification normalizer retest — 2026-11-18

- Found that the active local WordPress install had an older duplicate plugin copy selected (`zmp-competitions-66b`); the expected candidate file was instead copied to an inactive duplicate directory. The stale active copy was synchronized with the exact candidate file. Candidate and active runtime SHA-256 now both equal `3710600BDBE4B3B3506F4DB3FA99BB5F85F4824008EDEF4EBC7ACCDE0E7A1150`; the inactive duplicate file was restored byte-for-byte to its pre-test hash `f6fe7ea98e160f139c56dd534cca9e4d6b965936c73006f9c29bbd512e222a56` from the separate `.66.a` sandbox copy.
- Invoked the actual private normalization helper through reflection in WP-CLI against the active candidate: omitted value -> 0; exact `'0'` -> 0; browser-style `'on'` -> `WP_Error(qualification_invalid)`; exact `'1'` without an eligible next stage -> `WP_Error(qualification_invalid)`. PASS. This helper test is non-mutating and does not claim a full finalization/browser/passkey ceremony.
- Helper SHA-256: `4f8a103ff1629afb7d6cb6213219b859db5df12b1bf31127337e8e0f0448378c`. `wp db check` passed for all tables after the check.
- Follow-up with the active synthetic season fixture found category 1 municipal has an enabled next stage. The same candidate helper accepted exact `'1'` as integer 1 for that eligible path. Read-only helper SHA-256: `7c3823ae4e21961b5b0554ec34a41155ebeb89cbb57357f76ebc9f9d7de3921b`. This closes the conditional normalization branch at helper level; final write/passkey ceremony remains a separate unverified end-to-end gate.
- Also invoked the actual step-up binding helper read-only: identical normalized qualification values produce identical 64-character digests, while 0 and 1 produce different digests. PASS. Helper SHA-256: `783bde59f5add51d5f2af40ac2548779f93917edc439fe53b6cae7a9f053a890`.
- Executed the actual `finalize_result()` handler in the isolated site with the synthetic active season/category and malformed browser value `'on'`. It redirected with `qualification_invalid`; `category_final_results` stayed 1→1 and `stage_events` stayed 0→0. No mutation: PASS. Handler harness SHA-256 `49fc40626100582caccf9afe8bc69777531f01f3f04b841f16d2158b3812ddca`. Final `wp db check` passed. This exercises the handler's early validation/no-write branch only; no successful passkey ceremony or finalization was attempted.

## Additional direct-route authorization samples — 2026-11-18
- Synthetic Teacher 117 was denied with HTTP 403 through actual handler calls for communications publish, Messenger retention retry, and Messenger admin review read. Counts for announcement, review-request, and retention-queue tables were unchanged. Helper SHA-256 `085decc233e5b09de051909179d75040695404e69be74277ea7d46189380ccae`.
- Synthetic Teacher 117 invoked the actual school-email President-decision POST handler with a valid request-bound WordPress nonce and a nonexistent synthetic request ID. It redirected with `decision_failed`; request and event counts remained 0→0. The handler reached the service, where the actor lacks active-President authority. Helper SHA-256 `cecaf74907041cc88bbdbffe83a0166bf565c9d952d9314c081869821eae6475`.
- These are denial/no-mutation samples only; they do not close the full route matrix or prove successful authorized communication, Messenger review, or President decision flows.

## `.65` subject queue inherited denial — current `.66.b` — 2026-11-18
The current candidate's actual `ZMP_President_Subject_Approvals::render()` callback was invoked as synthetic Teacher 117. It denied with HTTP 403 before rendering protected queue data; proposal/event counts remained 1/1→1/1. PASS for the route callback denial in current `.66.b`. Harness SHA-256 `a52a66f20315584e401b3ecd06ae0336310ed74672b14d5ca1b458551c7b0fac`. This supersedes the `.65` fixture's narrow authenticated queue-denial NOT RUN for current code, but not the separate President successful decision/passkey flow. Historical `.65` FAIL ledger remains preserved as a historical test-fixture failure.

## `.65` President profile photo privacy field — `.66.b` — 2026-11-18
Using current `.66.b` source hash `91BD91B0F4F76579553457E3F0B9779EBFE79CA9D5E509040F689AB2DD5D5D76`, rendered `ZMP_President_Public_Display::render_profile_photo_fields()` for the isolated synthetic President account with a temporary 1×1 synthetic image attachment. The output contained exactly the three intended custom input names; the image `src` matched the exact local uploads URL for that synthetic attachment. No email, name, or unrelated profile input was added by this custom section. PASS for this callback's hidden image-src and custom-field scope. The fixture attachment/file and profile metadata were removed/restored exactly; attachment row count returned to baseline. Helper SHA-256 `b01ffbb630c491f79c9453676165e26db7f096841c74e03f7d45300424401e9b`.
- A separate attempt to request the full `/wp-admin/profile.php` over local HTTP redirected to WordPress login because the generated test cookies were not accepted. This did not change source or DB state; the temporary attachment/session were cleaned. Therefore this result does not claim full-page browser UI acceptance, only actual server-rendered callback output.

## Static syntax gate — current `.66.b` source — 2026-11-18
PHP lint PASS for 53 files under `includes/` and `admin/`, plus the main `zmp-competitions.php` entry point. Node syntax check PASS for all 8 JavaScript files under `assets/`. No syntax errors were emitted. This does not substitute for the open runtime/authorization/UI/release gates.

## Final local cleanup and synthetic row invariants — 2026-11-18
Final `wp db check` PASS. Persistent protected-test counts match the recorded baseline: passkey credentials/challenges 4/1 (untouched), subject proposal/events 1/1 (baseline), school-email requests/events 0/0, President governance documents 0. Temporary synthetic President image attachment/file, profile metadata override, and one temporary session were removed/restored; cookie jar and captured HTTP page files are absent.

## `.65` President governance document download — `.66.b` — 2026-11-18
The active candidate/runtime hashes for `class-zmp-president-governance.php` match `BABF6A6310311EB97434AC7DF41025BFD506822839797C91668B0ADE5790B8D`; `class-zmp-president-mandate.php` matches `5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40`. With a temporary synthetic AES key loaded only by an ephemeral MU-plugin, the local HTTP admin-post route was exercised as the current synthetic President with active mandate 58 and a same-session nonce. It returned HTTP 200, `application/pdf`, attachment disposition, `nosniff`, and the exact synthetic PDF bytes. PASS. Synthetic document/audit rows, encrypted file, upload PDF, MU key plugin, temporary session and role override were removed/restored; counts returned to baseline and DB check passed. Harness SHA-256 `2f3f2e95d8733376daf835023e554bf38b9783410f09e07ff6b8be930357735d`.
- A prior WP-CLI-only attempt could not capture the route body across `exit` and had an incomplete test cleanup branch. It was not counted as evidence; exact leftover files were removed, capabilities/session/rows restored, and the subsequent actual local HTTP route test above passed with a corrected cleanup harness.

## `.65` President profile page privacy DOM — `.66.b` — 2026-11-18
An authenticated local HTTP GET to `/wp-admin/profile.php` for the synthetic President returned 200. The actual profile DOM contained the photo-consent section and preview `src` matching the temporary local synthetic attachment URL. The attachment/file, profile metadata and one-time session were removed/restored; post count returned to baseline. PASS for rendered profile page/custom photo DOM at server-render level. Harness SHA-256 `441f26b77b57217b0632fe0efdb4b970abe3aaca0fcb5a418b43db67c34ecde2`.

## `.65` President mandate active-page UI scope — `.66.b` — 2026-11-18
An authenticated local HTTP GET of `/wp-admin/admin.php?page=zmp-president-mandate` as synthetic administrator 119 returned HTTP 200 and rendered the page heading. The initial sandbox had no eligible Subscriber, so the appointment form correctly did not render; this first fixture was insufficient and not counted as PASS. After adding one temporary synthetic Subscriber, the same read-only page rendered the appointment form and offered that synthetic user. Mandate/governance row counts and administrator session metadata stayed unchanged; the temporary user was deleted, user count restored, no email was sent. PASS for the current rendered admin route. Harness SHA-256 `2b2bc74d3c84b5a927f344229c4983c08caa9786cefb4c3718e44c98d11ae7a2`.

## `.65` public President photo consent/privacy — `.66.b` — 2026-11-18
With a temporary local synthetic attachment on the active synthetic mandate, `public_mandate_data()` omitted `photo_url` when consent was `0`; with consent `1`, it exposed only the exact same-site uploads URL. Public data contained only `display_name`, `start_date`, `end_date`, and consented `photo_url`; email, phone, address, and other profile fields were absent. Profile/consent metadata and attachment/file were restored/removed exactly; post count returned to baseline. PASS. Harness SHA-256 `145c5be83dbbaf83b6003d6bc41cc50887647e5ba3d0f863d3be3009e39961d2`.

## Follow-up: synthetic President passkey attempt (2026-11-18)

The local `.66.b` database was inspected before the attempt: 4 credential rows, one already expired/consumed challenge, one pending synthetic proposal, one proposal event, and no canonical catalog rows. A temporary synthetic ES256 credential was created for local user 116, and the temporary capability role metadata was restored after the test. The first attempt was rejected because this fixture had both `zmp_president` and `zmp_commission`; the production route correctly requires the exact President-only role set. With the role temporarily normalized, the real `step_up_options()` path issued valid options and the President step-up gate passed. The follow-up assertion harness did not pass: it signed the first manually created challenge rather than the fresh challenge returned by `step_up_options()`. This is a harness construction error; it is not a product PASS or a confirmed product defect. No subject decision was applied. Temporary credential, session, role change, and challenge were cleaned; the original expired challenge row was restored from the local pre-test SQL backup. `wp db check` passed afterward. The successful assertion and HTTP POST lifecycle remain NOT RUN pending correction of the challenge-bound synthetic assertion harness.

## `.65/.66.a` inherited President subject-decision flow — local HTTP closure — 2026-11-18

Exercised current `.66.b` `ZMP_Passkeys::step_up_options()` and `ZMP_President_Subject_Approvals::handle_post()` over the isolated localhost HTTP server using synthetic user 116, a temporary exact President-only role, the existing synthetic pending proposal 44, a one-use session, and a generated ES256 key/credential. The synthetic credential was serialized using the WebAuthn COSE map layout and 32-byte P-256 coordinates. The HTTP POST carried the exact server-issued challenge, President decision nonce, session cookies, same-origin headers, proposal ID, and decision.

Positive result: HTTP 302, redirect present, proposal transitioned to rejected, and exactly one decision event was created. Then replayed the consumed assertion with altered decision `approve_new`: HTTP 302, no additional event, and no overwrite of the first decision. Exact proposal and event snapshots, role/session/user-handle metadata, synthetic credential, and challenge state were restored; the pre-existing expired challenge row was restored from the local baseline. Before/after protected counts were credentials/challenges 4/1 and proposal/events 1/1; `wp db check` PASS. This is current-code local HTTP evidence for positive step-up, exact action binding, replay/altered-decision denial, and no residual fixture. It does not claim browser WebAuthn hardware interaction. Harness SHA-256 `a679b0b6e73d9f528e6ba7386323d491a6c3ac177ca49ea82245737af306e943`; candidate/runtime hashes match for `class-zmp-passkeys.php` (`A39B1417048BD7F6D753F6C3B094897292C8F3D0A3E4CBD53530A6A8F7330CB8`) and `class-zmp-president-subject-approvals.php` (`45435CB47D290D736622B71E8C0C5E92416BBF4418E2A0020B23D61EF0B16D88`). This closes the prior `.65/.66.a` carry-forward NOT RUN for server-side President proposal step-up/decision, but not UI/WebAuthn browser prompt or the selected jury-finalization step-up.

## `.66.b` selected jury-result step-up — local HTTP closure — 2026-11-18

Exercised the exact candidate/runtime `ZMP_Category_Jury::ajax_step_up_options()` and `finalize_result()` routes on isolated localhost HTTP. The synthetic assigned Commission fixture used category 2, entry 1, municipal stage, a generated ES256 credential, exact category-jury nonce, same-origin HTTP headers, and a short-lived session. The pre-existing test entry was temporarily made `submitted` and the synthetic municipal threshold temporarily set to 1 because its original `draft` state and threshold correctly caused the route to deny access as `municipal_stage_skipped`; both fixture values were restored exactly afterward. The existing result row was temporarily set to `pending_review` to exercise the documented post-correction reconfirmation path, then restored exactly.

The actual AJAX step-up options request returned HTTP 200. A signed assertion for the returned challenge finalized the result through the HTTP handler (302), restored `confirmed` status and added one confirmation audit event. Replaying the same assertion with an altered rationale was denied by the consumed/context-bound challenge (302) and caused no additional result or audit change. Exact result, audit, entry, stage threshold, role/session/user-handle metadata, credentials and original challenge state were restored. Protected state returned to baseline; `wp db check` PASS. Candidate/runtime hashes match: `class-zmp-category-jury.php` `3710600BDBE4B3B3506F4DB3FA99BB5F85F4824008EDEF4EBC7ACCDE0E7A1150`; `class-zmp-passkeys.php` `A39B1417048BD7F6D753F6C3B094897292C8F3D0A3E4CBD53530A6A8F7330CB8`. Harness SHA-256 `49a9d5b92465029a4c6a00e030c52b48a52bcbc4bd8126b20c437e2ca3689579`. This closes the server-side selected jury-finalization success and altered-payload replay criteria locally; browser authenticator prompt, other roles/resources and the wider route/regression matrix remain open.

## YouTube synthetic upload preparation and offline/mocked transport — 2026-11-18

A fresh, clearly labeled synthetic 8-second test-pattern clip is prepared locally at `/home/tonic/zmp-local-sandbox/phase66b-test/tmp/zmp-youtube-synthetic-test-20261118.mp4` (SHA-256 `9a993e6b70f92262afa093a8f26175ce16d47a1c846107b4168dac717ad34b19`, 703570 bytes, no audio, metadata labels it synthetic). It was not uploaded.

Preflight found existing local YouTube media row ID 1 for synthetic entry 1 / regional `performance`, created 2026-11-13, with empty `video_id`, `session_unknown`, offset 0, and a file hash exactly matching the earlier 8-second synthetic test clip. The current upload handler intentionally rejects a new upload while that exact entry/slot is `session_unknown`; the moderator resolution handler requires manual channel confirmation that no orphan video exists. The row and all upload/session state were left unchanged. The Owner was asked to check whether the prior attempt appears on the channel before any retry.

Offline contract helper: 13/14 assertions passed; only `youtube_unconfigured_fails_closed` failed because this local environment already has saved YouTube connection settings, contrary to the helper's expected unconfigured fixture. No external request or database mutation occurred. Existing mocked transport helper PASS: token refresh 2, session start 1, probe 1, chunk 1, unexpected outbound calls 0; synthetic settings restored exactly and temporary upload file removed. This validates only mocked transport, not a real upload. New real upload remains pending resolution of the existing uncertain attempt and Owner confirmation.

## Owner clarification: website upload works — 2026-11-18

The Owner clarified that the production website YouTube upload had been functioning and that the prior NOT RUN label came from the local test environment. Accordingly, the local HTTP 403/authentication-harness attempts are classified as **local harness limitation**, not as a `.66.b` product failure and not as evidence against the Owner's live-site observation. The latest local isolated database still contains a synthetic `session_unknown` record for entry 1 / regional `performance`, with empty video ID and the hash of the earlier synthetic clip. No live YouTube attempt was made in this turn; the fresh synthetic MP4 remains local. A retry must first resolve that existing uncertain record after the Owner checks the channel; otherwise the implementation intentionally blocks a second upload to prevent a duplicate. The actual locally mocked transport remains PASS with zero unexpected external HTTP calls. Active website / browser upload verification remains **Owner-confirmed only for the previously reported upload; no new synthetic upload result is claimed**.

## Owner-directed legacy open-item reconciliation and route inventory — 2026-11-18

The earlier 2026-11-13 scope pause is superseded by the Owner's instruction to verify unfinished pre-.66.b items in the current phase. The old phase documents preserve their original statuses; dated carry-forward notes were added in `.53`, `.55`, `.59`, `.60`, `.65`, and `.66.a` folders so each source phase links to current evidence or explicitly says its gate remains open.

Current `.66.b` source/runtime route inventory:
- Static source scan found 106 explicit unique `admin_post*` / `wp_ajax*` registrations under the phase `includes` tree.
- Active isolated WordPress runtime registered 131 matching hooks/callbacks; 102 explicit source hooks overlapped. Runtime-only hooks include WordPress core/theme and dynamically registered plugin handlers; the inventory needs tracing, not naive source-count equality.
- All 50 candidate `includes` source files (excluding vendor) matched byte-for-byte against the active isolated `.66.b` plugin copy. Full plugin-tree comparison was stopped because recursive traversal through the Windows-mounted phase tree was prohibitively slow; do not claim a full-tree hash match.
- `wp db check` passed after the inventory work. No route was triggered by this inventory scan.

Already documented current-hash/runtime checks include Messenger narrow-schema/retry, future-date freshness, privacy/terms server rejection, catalog type/timestamp migration, teacher-profile callback/readback, partial passkey schema fail-closed injection, selected Communications/Messenger/invitation/mandate denial routes, Teacher proposal submission, President proposal step-up success/replay denial, and selected jury-result step-up success/replay denial. Those targeted results do not constitute a full authorization/regression matrix.

Still open in `.66.b`: (1) route-by-route positive and negative authorization + no-mutation matrix for the 131 runtime hooks, prioritizing plugin-owned routes; (2) `.59` certificate/facsimile concurrent acceptance runtime; (3) browser teacher-profile submit/readback and broad mobile screen coverage beyond accepted jury view; (4) attribution of historical `.65` outbound-marker/AJAX Origin records; (5) `.65` GitHub provenance/release and full phase review gates. These are not silently marked PASS. The Owner reported that the actual website YouTube upload worked; the local 403 attempt was a test-harness/auth-context limitation. The separate local synthetic `session_unknown` row still needs channel reconciliation before another live synthetic upload; no new live upload was attempted. Main `PROJECT_ROADMAP.md` remains untouched.

**Status:** `.66.b` remains **IN PROGRESS / NOT_READY** until the remaining locally testable routes/gates are evidenced and the owner-only/remote evidence is resolved.


### .59 race evidence precision
.59 Race A passed; Race B completed with one in-pause issuance-row assertion NOT RUN because of an unsupported coordinator WP-CLI option. In .66.b, the certificate template is hash-identical to .59 (015836FF...C6BE), while facsimile and mandate service source hashes differ (153B2E4A...C587, 5B1A0B83...CA40). No current-hash concurrency race was run. The open item is a narrow current-hash race/assertion retest, not a claim that .59 races all failed.

## `.66.a` Teacher profile browser submit/readback — `.66.b` local HTTP closure — 2026-11-18

Exercised the current `.66.b` `ZMP_School_Profile_UI::save()` through the real isolated local WordPress `/wp-admin/profile.php` flow. Synthetic Teacher 117 was assigned a temporary synthetic mentor/school; GET rendered the form and yielded its exact core and plugin nonces. POST used `action=update`, both nonce values, and synthetic per-school email/employment/workload/visibility fields. Result: profile GET 200, POST 302, follow-up GET 200, database values matched, and rendered form readback matched. The original synthetic Teacher core user row stayed byte-for-byte equivalent at field level; temporary profile/mentor/school rows and session were removed/restored exactly. `wp db check` PASS. Candidate/runtime hashes match: UI `FFDC2C810280924E5217CDB4D4083D5B49C6EE02D8194479278CAECD0F450F26`; service `BED1C58E6D895FA04907D6C53CAA9494946FBF0BF53C13E115D0AF8A834F6C31`. This closes the `.66.a` browser form submit/readback carry-forward for this route; partial-schema injection and other independent release/phase gates remain separately tracked.

Reproducible local helper: reports/teacher-profile-http-submit-readback.php (synthetic user 117 and temporary school/profile rows only; it cleans up its fixture).

## `.59` President facsimile certificate lifecycle — current `.66.b` runtime — 2026-11-18

Using the synthetic active President mandate 58/user 116, a temporary pending facsimile image and an ephemeral Ed25519 keypair, exercised current `.66.b` certificate registration, `verify_for_use()`, and synthetic Administrator 119 revocation. Registration returned a certificate; use verification passed for the exact synthetic facsimile hash; revocation returned success and persisted `revoked`. The facsimile/certificate/event table counts returned exactly to baseline; temporary private image, role metadata and `zmp_debug_events` option were restored; `wp db check` PASS. No Drive/HTTP calls occurred. Candidate/runtime hashes match: certificate service `153B2E4A73214B9B40660F98FF6C217ADBE687417EE950B1889513C37DC3C587`, mandate service `5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40`, certificate template `015836FF1D618917E78E8E30B257D4CB5D482D1809B5CBE4FA50F63498EDC6BE`. This closes current-hash positive certificate lifecycle coverage, but does **not** close the `.59` Race B assertion while issuance is paused inside PDF upload; the full concurrency interleaving remains open. Reproducer: `reports/synthetic-president-certificate-lifecycle.php`.


## .59 Race B current .66.b concurrent issuance/revocation — PASS — 2026-11-18

Closed the carried Race B gap against the exact current .66.b candidate in the isolated local WordPress clone. Candidate/runtime SHA-256 matched for the certificate service (153B2E4A73214B9B40660F98FF6C217ADBE687417EE950B1889513C37DC3C587), mandate service (5B1A0B83F2BA1809125C2BBFF4B57C201E3E6BD72792C62BFFE73B8DE870CA40), certificate template service (015836FF1D618917E78E8E30B257D4CB5D482D1809B5CBE4FA50F63498EDC6BE), Google Drive service (5F5BF577E12A6E1F17A5FF34DFE24534201D5816963F302204DEE01EF5E41DB7) and registration/facsimile service (0047A11E003DD39132A30B997E8AF2115CA103490E4EF607AE69B31378E9581E).

A synthetic President mandate/facsimile/certificate, synthetic DOCX, local PDF renderer and HTTP preemption mock exercised the actual get_issued_pdf_file(category, 1, ...) path. The mock intercepted every WordPress HTTP request; no Google request left the local PHP process. With the second (PDF) upload paused, two independent database reads both observed 0 issuance rows. A concurrent synthetic Administrator revoke waited on the shared lock; after upload release, issuance completed with a 7,703-byte PDF and revocation completed successfully (0.087 s). The resulting synthetic issuance and revoked certificate were then removed during exact cleanup.

Final cleanup restored the synthetic user's original role metadata, Drive settings and debug option; removed the test facsimile file and all synthetic rows. Issuance/facsimile/certificate/event/template tables each returned to 0 rows; wp db check passed. No product source changed in this retest. Reproducible setup/worker/revoker/orchestrator/cleanup helpers are the adjacent certificate-race-*.php and certificate-race-orchestrate.sh files. The earlier .59 historic note that its in-pause assertion was NOT RUN remains accurate for that run; this current-hash .66.b race test closes the carried acceptance gap.


## Current `.66.b` YouTube resumable helper — mocked transport PASS — 2026-11-18

Ran reports/youtube-mocked-transport-check.php against the exact current isolated `.66.b` runtime. Candidate/runtime SHA-256 for class-zmp-youtube.php matched at 689257D80B900280C9ED9A81B0D5C6E2393D0B5C011013FA953B948A3393672D; PHP lint passed. All five assertions passed: synthetic credential sealing, resumable session creation, encrypted session URL storage, parsed mocked transfer response (`unlisted`, `processing`), and expected-only HTTP counts (2 token, 1 session, 1 probe, 1 chunk, 0 unexpected). Synthetic settings and temporary upload file were restored/removed.

This confirms the YouTube helper contract with a fully intercepted transport. It does not clear the separate local `session_unknown` entry, confirm whether that prior live attempt appeared in the channel, or prove real channel permission, processing, privacy, or playback. No live upload or retry was performed.
