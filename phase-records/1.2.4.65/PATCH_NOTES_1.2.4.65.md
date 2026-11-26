## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Patch Notes — ZMP Competitions 1.2.4.65

Status: **IN PROGRESS / UNRELEASED**. Parent source is migrated from `.64`; implementation changes will be listed here with exact file/function, root cause, source hash and affected verification. No fixes are claimed at phase start.

## Initial queued work

- Add historically accurate `.63`, `.64` and `.65` entries to the dashboard What's New panel.
- Integrate and verify President, election-document, facsimile, school-register and official-email flows against the `.65` roadmap.
- Add voluntary President profile-photo display on Home, default OFF and eligibility-gated.
- Diagnose/fix owner-provided school/email `schema_not_ready` failures with synthetic schema states.
- Diagnose/fix approved-audio URL checker false-broken classification while retaining explicit real 403/unavailable results and SSRF protections.

## Implemented in this working candidate

- **President governance-document download form action:** confirmed by a real synthetic President click. The GET form's successful fields replaced the helper URL's query, omitting the action and triggering the existing scope-guard 403. Added hidden `action=zmp_president_governance_download` without changing authorization. Source SHA-256 `5AAF1D331DBB46D4ED2801ABDDE3F879229E4D69C7A53550B77F3A48765124DA`; PHP lint and exact-hash independent Code Review PASS. Post-fix browser response remained unverified (`ERR_BLOCKED_BY_CLIENT`); no runtime PASS is claimed. Evidence: `reports/PRESIDENT_GOVERNANCE_DOWNLOAD_ROUTE_1.2.4.65_2026-11-04.md`.

- **School-add schema retry marker:** `ZMP_Mentor_Schools::add_school()` now uses the public same-table readiness wrapper so schema drift fails closed and sets the shared five-minute retry intent. Source SHA `72D8EC5C13817C9825539ABA015E765831A95D477432CF846DE05832F54F1B46`; focused regression PASS 13/13, test SHA `DA5672D4986BE63853E3FF23F401B2CA76B35847A3567FBE707F6B8576535904`; PHP lint, exact-hash Code Review, cleanup assertions and pre/post `wp db check` PASS. Initial cleanup review FAIL and corrected re-review PASS are preserved in `reports/CODE_REVIEW_SCHOOL_RETRY_MARKER_1.2.4.65.md`.
- Bumped plugin/schema version identifiers to `1.2.4.65` in `zmp-competitions.php`; SHA-256 `FDF305F795D7E31C2388A5388D53157C71006799A60C67B2A1A6AB0C4FBF9091`.
- Dashboard What's New now includes `.63`, `.64`, and `.65`; `.65` is explicitly described as in progress. Source `admin/class-zmp-admin.php` SHA-256 `5309E7898F7782AB07E852FF1B7FB87CE486A99248208DA64751EA58AD6E7B63`.
- Added focused regression `tests/phase65-dashboard-whats-new.php` SHA-256 `57A7DAB576D130C5302BF6AB60D0BDD4F3F0E8CCE6FADEC959B74D93186E2791`. It failed first on the `.64` plugin version, then passed after version/dashboard change. Both changed PHP files pass `php -l`.
- This is a working candidate only; actual WordPress rendered dashboard review PASSed for `.63`, `.64`, and `.65` selection/content. Independent UI and Lead browser evidence are recorded in `reports/UI_REVIEW_1.2.4.65.md` and `reports/LEAD_BROWSER_ACCEPTANCE_1.2.4.65_2026-11-03.md`.
- **Teacher profile checkbox layout:** corrected the school profile check-field flex direction and inline label in `assets/css/zmp-public.css` after independent UI review found stretched controls. Exact CSS SHA-256 `BD12B88D3C84CF6E184F9AF02286B48D39273D33857FF925141DA28384FDC392`; synced file hash matches the isolated `.65` site. Independent desktop UI PASS and exact-hash Code Review PASS. Mobile View remains BLOCKED; responsive acceptance is open.

## Defect work in progress

- **School schema drift:** `zmp-competitions.php` now checks school, profile, and official-email schema compatibility when deciding whether the existing migration recovery path should run, including when the shared Messenger marker is current. TDD regression `tests/phase65-schema-recovery.php` first failed on an intentionally missing index and then passed 4/4 on the isolated synthetic WordPress DB after the fix. Source SHA-256 `B561C0BA454AAF81960B5532ACC8E109357E7137ACB772B737EC6FD5246665FB`; test SHA-256 `F40249A604D1FD0804A0ABCBEFC065F27742F54FC3413673FCB81406E920332D`.
- **Audio checker 403 — superseded by current review below:** initial 28/28 focused synthetic regression and source hash `9179D4...` are historical. Current source/test hashes and route/UI limits are recorded in the final reviewer-identity checkpoint below; independent UI PASSed form rendering, but action routes remain open and positive approval remains BLOCKED under local-only constraints.
- **President Home photo — superseded by current review below:** initial 8/8 test and hashes remain historical. Current backend regression is 12/12; independent UI PASSed desktop Home cards and admin consent/media-picker controls. Lead's authenticated Chrome UI save/opt-out/restoration check PASSed; keyboard and responsive checks remain open. See `reports/PRESIDENT_PROFILE_SELF_CONSENT_UI_1.2.4.65_2026-11-04.md`.

## Superseding implementation/review checkpoint — 2026-11-03

- **Schema retry performance P2:** Code Review first found forced retention metadata scans on ordinary `init`, then found the retention initializer still forced scans through its registered callback after the entrypoint-only correction. Both failed rechecks are preserved in `reports/CODE_REVIEW_SCHEMA_PERFORMANCE_1.2.4.65.md`. Final implementation adds an idempotent versioned retention-initialization marker; healthy ordinary requests skip the lock, schema scan and backfill. Migrations, explicit retries and actual retention readiness checks keep the locked/fail-closed path. Final re-review: PASS. Entry point SHA `291D0E7601B1DA01CDC96FE12774FB632E2638169E8526DDC3E9864D4B124A96`; Messenger SHA `38A7418CCC3DC1BEE6EC25EACE837214935CFA46B134D352C3A47F8D51607FC2`. Performance regression PASS 31/31; retry routes PASS 27/27; PHP lint, pre/post `wp db check`, cleanup and option restoration PASS. Tests: `EB98149D4577D4E3054538017FBA16B006174553C6DD255D83ACE6499B355AC2`, `EAC0571AEA5C3657B71B81DB3ABB015D331A0E1C26A76F578F2462863C726A58`.
- **Audio reviewer-identity P2:** current `probe_denied` approvals persist an authorized reviewer ID and require it alongside fresh timestamp, note, checklist, explicit URL-opened confirmation and exact URL hash. The admin catalog shows reviewer identity; teacher payload omits it and the review note. Final class SHA `86CEBE88A84A298C72EC188DB1EF84BA61F407CECA96F3D12853A82C6BBA62F6`; test PASS 37/37, test SHA `C85AD3957CE751685D0B27A9EB6C926A2A4510E180D0D1F3774F06ABE7C54040`; PHP lint and fixture/cursor restoration PASS. Independent UI PASSed rendered review form. Positive `admin-post` approval is BLOCKED under local-only constraints; negative authorization and incomplete-checklist routes remain open.
- **President consent P2:** only a President editing their own profile can grant/revoke photo consent; admin may manage the selected local image only. Final class SHA `7966462C232A0D7B387487B9E29BEEAA3FA271400AD68E6454A7F6B55CD4A759`; focused test PASS 12/12, test SHA `3C0C4FE73A57E14AB4E335D337C6829B182F92F977635BF388286731C72B0677`; PHP lint and `wp db check` PASS.
- These are focused implementation/source-review gates only. President Home desktop rendering, admin consent-protection/media-picker controls, audio 403 review-form rendering, and What's New desktop selection have independent UI evidence; self-profile save/accessibility/mobile and integrated phase workflows remain open. Whole-phase Master Review ran and returned **NOT READY**; acceptance remains open. Package parity, GitHub synchronization and Auditor remain NOT RUN. Candidate remains **IN PROGRESS / UNRELEASED**.
## Follow-up verification — 2026-11-03

- Carried privacy-safe debugging guidance for every `.63`/`.64` feature family into the active `.65` guide: subject proposal/President decision/alias, school assignments/register/export, per-school employment visibility, and official-email request/decision/disclosure/invalidation. The `.65` What's New entry now points to this guide. Frozen `.63/.64` artifacts were not changed.
- Authenticated Teacher school-profile HTTP save under ready schema and fail-closed behavior under missing `mentor_active` passed 11/11 using exact-reviewed script SHA-256 `613C08F2F655CA3011ACB2827FF5BEFC65621BB45CB002B6807C903D6B65C5C1`. Pre/post DB checks and exact cleanup passed. This does not verify President photo-consent saving.
- Audio negative admin-post route passed 6/6 with exact-reviewed test SHA `4C1B3FC58D315D0A67516075F0EBAB17A81364ECBCD4151B8E529FBA984A3819`: anonymous denial and missing-`url_opened` rejection, no candidate approval/change, pre/post DB and scripted cleanup PASS. Initial loopback-guard Code Review FAIL and corrected PASS are both retained. Positive approval remains BLOCKED; see `reports/TEST_ENGINEER_AUDIO_ADMIN_POST_NEGATIVE_1.2.4.65_2026-11-03.md`.
- Independent Chrome UI follow-up could not verify President self-profile controls because the new local tab had no authenticated session; it was closed without input or data changes. Responsive and President consent-save checks remain BLOCKED/NOT RUN.
- Current source has no school profile/email `wp_ajax_*` handler; the owner diagnostic omits `action`, so its `/wp-admin/admin-ajax.php` flow remains unidentified.
- Overall phase remains IN PROGRESS / NOT READY for Auditor. The latest Master review predates this evidence; fresh Master reconciliation, remaining acceptance, package parity, GitHub readback and Auditor are pending.

## Additive audio positive-route status — 2026-11-04

The preceding positive-approval BLOCKED labels based solely on local-only HTTPS constraints are superseded. The manual approval route can be exercised with synthetic numeric public-IP URL fields without requesting/opening those URLs. Its candidate test has lint and exact-hash Code Review PASS, but runtime is BLOCKED before `wp eval-file` due staging/path handoff failures; no HTTP/DB mutation occurred and no further retry was made. Actual URL audit/remote accessibility remains NOT RUN under local-only. See `reports/TEST_ENGINEER_AUDIO_MANUAL_APPROVAL_POSITIVE_1.2.4.65_2026-11-04.md`.

## Schema diagnostic action attribution — 2026-11-04

- Updated `includes/class-zmp-debug.php` so a `schema_not_ready` event on the WordPress `admin-ajax.php` path records only a sanitized, bounded `request_action`. The expected path is derived from `admin_url('admin-ajax.php')`; POST takes precedence over GET; cookies, request payload, nonces and caller-supplied `request_action` are not logged. Source SHA-256 `ED252BEF7763DB792AE684117586693A688EA952412966F48CBEB2B2F5BCBEC6`; exact-hash Code Review PASS.
- Focused regression `tests/phase65-debug-schema-ajax-action.php`, SHA-256 `C3E72A63FCBA430248D3BB4042DA37F85BB599B9C279C4A4DDFC06C300C2A8AF`, exact-hash Code Review PASS and PHP lint PASS. Runtime PASS 14/14 in `zmp65_isolated` using custom admin-path coverage; database and exact diagnostic-option restoration passed. Focused report SHA-256 `9E99C575A0B18B039F08869668FC96500C1F6B691A2F8C1966C7BC043A4EA930`.
- This improves future AJAX log attribution. It does not retrospectively identify the originating action/site/version in Owner-supplied historical rows or establish an AJAX handler for the school/email flows.

## Dashboard What's New copy verification — 2026-11-04

The `.65` dashboard copy now accurately describes President-controlled, default-off photo consent and sanitized schema AJAX action diagnostics. Exact source SHA-256 `06C99026398640208A18F5E59249EC1B25BDB7B4B348E6B60E94C3A5D7C1402E`, Code Review PASS and PHP lint PASS. Updated regression `tests/phase65-dashboard-whats-new.php` SHA-256 `E5FC9F949DF57EFA0AD593DF579A9B9E54074C02EC20052D03059E0C01E73B6F`, Code Review PASS, PHP lint PASS, runtime PASS (output `DASHBOARD_WHATS_NEW_63_64_65_PASS`, exit 0). It asserts `.63`, `.64`, and in-progress `.65` content. Independent visual review of this revised text remains NOT RUN because a fresh local Chrome session is unauthenticated.

## Follow-up — catalog acceptance and What's New UI — 2026-11-04

- Combined subject-catalog install plus nullable timestamp upgrade after both schema fixes: PASS 5 assertions/0 failures, exact-reviewed fixture SHA `12760A55752042F9BE4E9AF82E116AC321EE3CDF49A5CC209A0EFFC453ADFFFA`; exact source guards, DB integrity, readiness, NOT NULL timestamps/indexes and baseline restoration passed. Catalog-table scope only.
- First-catalog Teacher submission route: PASS 7/7, exact-reviewed fixture SHA `8E2B6F6780FF81FA52B0F7764A0F364E01F11F716767E1C361CE1C4AF9A5F0B3`; actual loopback admin-post redirect, pending proposal/event, no premature catalog/alias creation, cleanup and DB integrity passed. Negative roles and rendered Teacher route remain open.
- Independent Chrome UI Reviewer now PASSes desktop What's New `.63/.64/.65` rendering and keyboard selection. The dashboard was no longer classified NOT RUN after an authenticated synthetic-admin review. Detailed evidence appears in `.65/reports/UI_REVIEW_DASHBOARD_WHATS_NEW_1.2.4.65_2026-11-04.md`.

## Audio UI acceptance documentation — 2026-11-04

- Added `reports/AUDIO_UI_ACCEPTANCE_1.2.4.65_2026-11-04.md` as the Auditor-facing evidence index for the six per-tool mobile tabs, eligible HTTP 0 manual-review UI, device-preset/keyboard review, native viewport-resize focus evidence, candidate hashes and exact source reports.
- Updated `WHATS_NEW_1.2.4.65.md` and `DEBUGGING_1.2.4.65.md` to describe the reviewed UI, manual-review state/data flow, eligible states, safe diagnosis and remaining external-URL boundary. Added current-status corrections to the Test Report and Open Work Order so older BLOCKED/PARTIAL entries are not mistaken for the final Audio UI point status.
- No product source, tests, site data or roadmap changed. Existing scoped Audio UI **Master PASS** remains valid; overall `.65` remains IN PROGRESS / NOT READY FOR AUDITOR.
- Added the missing durable independent Code Review record for the exact final Audio UI PHP/CSS/JS hashes: `reports/CODE_REVIEW_AUDIO_UI_SOURCES_1.2.4.65_2026-11-04.md` (**PASS**, no concrete findings). The reviewer did not repeat UI checks or tests.

## President facsimile/certificate President-scope fix — 2026-11-05

- In `includes/class-zmp-president-dashboard.php`, added only the facsimile proposal submit and certificate registration action constants to the explicit President `admin-post.php` allowlist. The President stays non-admin; handler-level current-President, nonce and manifest checks remain authoritative.
- Exact candidate SHA-256 `FA16E74B46E33770F61505973E753BB8B6D38125D9F529A06EE71F2DCDA734EB`; PHP lint, source/install hash parity, and independent Code Review PASS.
- Actual UI upload and certificate registration passed; distinct fresh-key replacement passed with independent DB verification. Scoped Master verdict PARTIAL because the post-loss second upload through UI remains NOT RUN. See `reports/PRESIDENT_CERTIFICATE_ADMINPOST_SCOPE_REVIEW_1.2.4.65_2026-11-05.md`.
- This is unreleased candidate work. No `.65` ZIP or GitHub synchronization.


## President post-loss status correction — 2026-11-05
The historical note above saying the post-loss second upload remained NOT RUN is superseded by the later scoped PASS evidence in `reports/TEST_ENGINEER_PRESIDENT_POSTLOSS_UPLOAD_C_1.2.4.65_2026-11-05.md` and `reports/MASTER_REVIEW_PRESIDENT_POSTLOSS_UPLOAD_C_1.2.4.65_2026-11-05.md`: the authenticated President UI accepted facsimile #20 and distinct-key Certificate C; independent Test Engineer readback and scoped Master review passed. Do not repeat or inspect/change facsimile/certificate state before Auditor preparation. President transition is also scoped Master PASS. Whole `.65` remains open for the later-listed gates.

## Current ordered continuation — 2026-11-05
Multi-school profile is PARTIAL PASS / OPEN (exact anonymous DB no-mutation inconclusive; required populated-directory browser/mobile/keyboard UI BLOCKED). Subject proposal → active-President approval remains OPEN / NOT RUN. The candidate against `zmp65_isolated` stopped before route assertions because the catalog schema was not ready; the bounded alternate-clone preflight then failed to verify its target after a WSL path-translation error. No alternate candidate was prepared/run; do not retry either path or bypass the refusal. Preserve the staged-file cleanup limitation from the first attempt. Other direct President HTTP, subject unavailable-state UI/negative-role, and school-email approval fixture integrity gates remain as described in the current Plan/Test Report corrections. No phase ZIP, GitHub sync, or Auditor.

- Fixed legacy Messenger upgrade failure where dbDelta() attempted to add scoped_threads under an existing name after the index gained 
egion_key; verified safe index replacement, fail-closed handling of a unique mismatch, row preservation, repeat install, and cleanup. Evidence: 
reports/MESSENGER_LEGACY_SCOPED_THREADS_REPAIR_1.2.4.65_2026-11-24.md.

- 2026-11-24: Fixed exact-role-array checks that rejected authorized plugin roles when WordPress subscriber was also present. Extra plugin roles still fail closed. Local synthetic suites passed; see `reports/MIXED_WORDPRESS_ROLE_ACCESS_1_2_4_65_2026-11-24.md`.


- 2026-11-24: Corrected role combination handling for Teacher/President/Moderator/Commission/Testator/Organizer stacks with required Subscriber membership. President appointment now preserves existing roles; see `reports/ROLE_STACK_AND_PRESIDENT_ROLE_PRESERVATION_1_2_4_65_2026-11-24.md`.


- 2026-11-24: Added a complete-auth-cookie runtime denial test for the President subject queue and decision action. Direct Teacher access fails closed with HTTP 403; see `reports/SUBJECT_PRESIDENT_QUEUE_AUTH_COOKIE_RETEST_1.2.4.65_2026-11-24.md`.


## Follow-up: six-role teacher route and profile service — 2026-11-24
The synthetic Teacher+Subscriber account, also stacked with Moderator, Commission, Testator and Organizer, passed the actual first-subject proposal route and school-profile engagement service check **8/8** on the .65 source. `get_engagements()` returned a valid array (empty is valid when the synthetic teacher has no school engagements); route returned HTTP 302 `submitted`, and exact test cleanup plus no-outbound guards passed. Fixture SHA-256: `22C6EA8A296131CBADFC883EE6E8630796FD2EE5C75B8892D5DE5D0E38F18357`. This establishes role-stack service availability for the synthetic setup, not that a real user's school engagement rows or production database are correct. The four-phase role suite continues to cover Testator/Organizer stacking and negative controls.