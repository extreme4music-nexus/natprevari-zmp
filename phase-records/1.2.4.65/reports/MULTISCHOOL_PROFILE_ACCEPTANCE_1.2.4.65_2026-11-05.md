# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Multi-school profile acceptance checkpoint — 1.2.4.65 — 2026-11-05

## Status
- **Current multi-school named gates: PASS at scoped acceptance.** Teacher profile save/reload, directory service/field/school-scope/role/privacy runtime, populated-directory desktop/mobile/keyboard UI, and the actual anonymous shortcode GET no-mutation are evidenced in the later dated appendices below and current ledger. The `.64` overall aggregate remains **FAIL 115/120**; this `.65` subcriterion PASS does not reclassify it.
- **Remaining broader integration:** the authenticated directory server-response cache-header criterion now has a scoped PASS for one populated GET. Exact DB/session no-mutation and proxy/CDN behavior were not established for that request; the wider direct URL/AJAX/REST/role authorization matrix remains open in a separate `.65` gate. Historical statements below about the unconfigured placeholder, absent Teacher UI, and inconclusive initial anonymous-fixture checksum are preserved as point-in-time findings and superseded only by the specific later follow-ups.
- **President photo/consent:** existing PASS evidence preserved; not touched or repeated.

The initial checkpoint narrative below is historical. See the dated addenda and current gate ledger for the later scoped PASSes and remaining broader integration work.

## Evidence reconciled
- `.64` plan defines multi-school per-engagement fields and privacy/role expectations. Its original runtime acceptance remains FAIL 115/120; later targeted/rendered evidence closes only its named subcriteria. Do not convert `.64` aggregate FAIL to PASS or reuse it as full `.65` acceptance.
- `.65` profile-route test PASS 11/11 is limited to ready-schema save and schema-unavailable fail-closed/retry. It does not cover multi-school fields or current route matrix.
- `.65` UI reviewer observed schema-unavailable state. This does not establish normal ready-schema multi-school behavior.
- Local site `http://127.0.0.1:8094`, synthetic DB `zmp65_isolated`; authorized synthetic Administrator session was used to create a Teacher-role WordPress account (`example.invalid` email, no email sent). The user creation succeeded. No ZMP profile, school-engagement or employment/status data was created or edited. The synthetic test user was not cleaned up in this checkpoint; it has no known profile/engagement data and its temporary credential remains only in the current browser automation memory. Cleanup and verification remain open.
- Prior bounded WP-CLI exercise returned `TISDK_CRASH / no_attribution` twice. Per project instruction, no further same-path attempt was made. This is an environment limitation, not plugin acceptance.
- Chrome is reachable in this session, so the prior runtime blocker should not be attributed to Chrome. The actual ZMP acceptance remains incomplete because the live profile/engagement route was not exercised.

## Next safe action
Use the current authorized local site to reach the Teacher's ZMP profile/engagement UI and complete only missing ready-schema multi-school field and role/privacy checks. If the route is unavailable, record the exact UI/source evidence and keep the criterion NOT RUN. Then clean up the exact synthetic Teacher and verify DB integrity. Do not repeat President photo/consent PASSes. Obtain independent review/testing and scoped Master reconciliation before advancing the roadmap point.

### Addendum — 2026-11-05
Chrome admin Users list was reachable, but the supported computer-use CTRL+SHIFT+N attempt for a separate Teacher session returned computer-use request timed out; no alternate automation path was used. Teacher ZMP route was not reached. Acceptance remains NOT RUN and cleanup is open. This is a bounded Chrome control limitation for the separate session only, not evidence that the local site/admin UI is unavailable.

### Addendum — temporary directory route rendered as Administrator — 2026-11-05
A fresh task-owned Chrome tab confirmed the local WordPress Administrator session. Through the editor, a temporary page was published as ID 37, title `ZMP .65 Synthetic Employment Directory Fixture`, with the exact `[zmp_employment_directory]` shortcode. The editor confirmed “Page published”; rendered URL is `http://127.0.0.1:8094/?page_id=37`. As Administrator, the shortcode rendered its empty linked-schools state (“Нема споделени податоци за училиштата со кои сте поврзани.”). This verifies the local route/shortcode is present; it does not satisfy populated Teacher directory or responsive/mobile/keyboard acceptance and is not a role/privacy PASS for Teacher. Page ID 37 remains a temporary fixture and must be removed after the pending Teacher review. No existing page or profile was changed; no password was handled. The user has been asked to leave a local synthetic Teacher session available because Chrome policy requires the user to enter any new password themselves.


### Addendum — authenticated multi-school profile save/reload — 2026-11-05
Synthetic Teacher #127 logged in successfully at the authorized local site http://127.0.0.1:8094 and opened /wp-admin/profile.php. The page identified Synthetic Multi School Teacher. Separate active-school fieldsets rendered for Synthetic North School Phase65 (school 17) and Synthetic East School Phase65 (school 18), with term, workload, retired/replacement status, colleague and dates, and seven independent visibility controls per school. Initial visibility flags were all private.

A synthetic save set school 17 to fixed term/full workload, with only employment-term visibility enabled; school 18 to indefinite/part workload and retired status, with only retired-status visibility enabled. WordPress displayed the ZMP success notice and Profile updated. After reload, both school values and distinct visibility flags remained present. Official email, replacement values and dates were left blank; no email was sent. PASS is limited to the actual Teacher profile render, ready-schema save and independent per-school persistence for the values exercised. It does not cover replacement dates in this run; .64 runtime evidence exercised a valid replacement payload and reversed-date rejection on unchanged validation code.

The .65 directory school-scope source fix is exact-hash Code Review PASS in CODE_REVIEW_MULTISCHOOL_SCHOOL_SCOPE_1.2.4.65_2026-11-05.md, but changed directory-query/HTML role/privacy runtime remains NOT RUN. The known .64 temporary directory slug on this .65 site resolved to the theme placeholder/home template, with no employment-directory shortcode. No temporary page was created and no route variant was guessed.

Preserved .64 evidence: role allow/deny and each-field projection passed within the 115/120 composite run; the five named failures remain, so the composite stays FAIL. Responsive/keyboard review of profile fields passed at 390x844, with both school fieldsets and 14 visibility controls, bound to unchanged profile UI hash 88EA74E055F0E68FCE9F95F8EE97E75357B903DDBB76785E80199FC554A8FC9D. These earlier scoped results remain preserved; no President photo/consent checks were repeated.

Synthetic profile test data remain pending remaining acceptance and cleanup. No email was sent, President/facsimile/certificate state was touched, ZIP was created, GitHub sync performed or Auditor started.

### Addendum — directory school-scope runtime — 2026-11-05
The bounded test-only WP-CLI fixture `tests/phase65-multischool-directory-scope.php` ran once against the authorized local disposable site after preflight verified DB `zmp65_isolated`, healthy tables, candidate-only active plugin, existing exact synthetic Teacher #127 and Subscriber #111, and installed service SHA-256 `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`. Fixture SHA-256: `467C7C473252C2DB1ED04961A0360D7688FF4604E83B6EA049C1840CE8DA06FB`; independent exact-hash source review PASS is recorded in `reports/TEST_ENGINEER_MULTISCHOOL_DIRECTORY_SCOPE_1.2.4.65_2026-11-05.md` (verify exact report path/hash before final consolidation).

Runtime result: **PASS, 24/24 assertions**. It verified all-school queries only return the Teacher's authorized active schools; independently enabled employment-term and retired fields are projected correctly; disabled fields and emails are absent; explicit authorized school IDs work; unrelated active school and below-Teacher role are denied; actual shortcode HTML contains only allowed fields; scoped option/schema state was restored. Expected sanitized warnings were `school_not_owned` and `forbidden`. No email/network attempt or personal/profile-field output occurred. Post-run DB check PASS, fixture removed, same PHP/MariaDB processes and port 8094 remain active. This closes the changed directory-scope runtime criterion for the synthetic fixture, not whole `.65` acceptance. Teacher profile/account cleanup and independent test/master reconciliation remain open.

### Correction and closure candidate — directory scope evidence — 2026-11-05
The directory source-scope PASS is documented in `reports/CODE_REVIEW_MULTISCHOOL_SCHOOL_SCOPE_1.2.4.65_2026-11-05.md`. The separately independent Test Engineer execution is documented in `reports/TEST_ENGINEER_MULTISCHOOL_DIRECTORY_SCOPE_1.2.4.65_2026-11-05.md`, which records exact fixture/source hashes and PASS 24/24. Both Lead and independent Test Engineer runs passed 24/24. The earlier statement that changed directory runtime remained NOT RUN is superseded by these runs only; no other `.65` acceptance or `.64` aggregate status changes.

Cleanup PASS: synthetic WP user #127, its synthetic mentor row #16, engagement rows 17/18, and profile rows 13/14 were removed after confirming no authored posts or other mentor-linked application rows. Post-cleanup query counts were zero for the account, mentor, profiles and engagements; `wp db check` PASS. No other user/record, email or service was touched.


See 
reports/MULTISCHOOL_PRIVACY_SURFACE_MAP_1.2.4.65_2026-11-05.md for the current route/cache/log inventory and scoped statuses. Anonymous access behavior PASS 6/6; post-run database mutation verification remains BLOCKED/inconclusive because the before/after export checksums differ. Do not claim no-mutation.


### Debugger reconciliation — anonymous-check database checksum — 2026-11-05
Read-only source-path triage in `reports/DEBUGGER_MULTISCHOOL_ANONYMOUS_DB_CHANGE_1.2.4.65_2026-11-05.md` (SHA-256 `8FA095C5E2374B301F266891FAE8EAAE4BF74037BC6F00C9A07794EE28326166`) confirms the two expected anonymous denial calls append sanitized events through `ZMP_Debug::log()` to `zmp_debug_events`; the fixture has no other DB writes, anonymous shortcode output is static, and current-user changes are in memory. No profile/account/engagement write path or product bug was found. Keep these diagnostic events. Since no pre-run snapshot exists, exact prior option value and unrelated runtime mutation cannot be ruled out; no-mutation status remains BLOCKED/inconclusive. No speculative option reset/deletion is authorized or performed.

### 2026-11-05 remaining multi-school evidence and UI blocker
Anonymous directory/profile service denial and the shortcode login-only response passed 6/6 on the exact-reviewed fixture. The Debugger confirms only the expected sanitized denial diagnostics can be written by that fixture; however, because no pre-run database preimage exists, exact DB no-mutation remains inconclusive and the logs are preserved. Source inventory finds no employment profile/directory AJAX, REST, search or export routes; official-email administration is separate.

The required populated-directory desktop/mobile/keyboard UI review is **BLOCKED / NOT RUN**. A new task-owned Chrome tab reached the authorized local login, but Chrome browser policy rejected access to the local credential file via `file:` and prohibited alternate credential-retrieval workarounds. The UI Reviewer entered no credentials, changed no data, closed the task tab and recorded the precise result in `reports/UI_REVIEW_MULTISCHOOL_DIRECTORY_1.2.4.65_2026-11-05.md`. User clarification is pending; do not bypass the browser policy. The point remains **PARTIAL PASS / OPEN**. See the scoped Master reassessment, `reports/MASTER_REASSESSMENT_MULTISCHOOL_PROFILE_1.2.4.65_2026-11-05.md`.

### Addendum — anonymous direct profile HTTP — 2026-11-05
A single unauthenticated GET to `http://127.0.0.1:8094/wp-admin/profile.php` returned 302 to `/wp-login.php`; response included `Cache-Control: no-store, must-revalidate, no-cache, max-age=0, private`, with no `Pragma` or `X-Frame-Options` header. No profile/school/employment/private markers were detected in the response body. Route source hash matched workspace and installed copy before the request. Scoped anonymous HTTP boundary PASS only; this does not replace authenticated Teacher UI, populated directory, or role-denial evidence. No database comparison was made. Test Engineer detail: `reports/TEST_ENGINEER_ANONYMOUS_PROFILE_HTTP_1.2.4.65_2026-11-05.md`.

### Addendum — anonymous direct HTTP to temporary directory page — 2026-11-05
Lead made one direct unauthenticated GET to local page #37 with cookies, proxy and redirect-following disabled. It returned HTTP 200 with no `Location`; the expected Teacher login notice was present, while school-row markup, employment labels and `official_email` were absent. No `Cache-Control` header was present on this anonymous page response. This closes only the observed anonymous page-render behavior for page #37; it does not verify populated Teacher rows, authenticated role behavior, or cache headers on a populated directory response. No DB snapshot was taken, so no-mutation is not claimed. Evidence: `reports/LEAD_ANONYMOUS_DIRECTORY_PAGE_HTTP_1.2.4.65_2026-11-05.md`.

### Authenticated populated-directory cache-header acceptance — 2026-11-05
The Test Engineer made one authenticated GET to the populated shortcoded directory on the existing synthetic site and retained only status, cache headers, and group/person counts. HTTP 200 returned Cache-Control private/no-store/no-cache/must-revalidate/max-age=0, Pragma no-cache, and expired Expires. The scoped Master Reviewer records PASS for these server-response headers only. Test report SHA-256 C8C062E067F1B39619975C5EBB108690726A9563544C7A164FD3379AB7C883C4; Master report SHA-256 C0AFBDD6B79AC8B356318CCEBC4ADD0AF23602AA1A7D214833ACFE0E8C0BDCAE.

Raw full-export hashes differed and were not normalized. Exact no-mutation and temporary-session cleanup are NOT VERIFIED. A later malformed read-only WP-CLI aggregate probe produced a PHP warning/TISDK_CRASH no_attribution; it may have appended a sanitized diagnostic, and no reset/restoration was attempted. This is separate from the earlier exact normalized no-mutation PASS for the anonymous GET. Proxy/CDN behavior and the broader direct URL/AJAX/REST/role matrix remain unverified.
