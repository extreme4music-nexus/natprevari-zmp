## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Master reassessment — multi-school profile point — 1.2.4.65 — 2026-11-05

## Verdict
**PARTIAL PASS — NOT READY TO CLOSE.** This is a criterion-scoped reconciliation of the current `.65` multi-school profile point, not whole-phase acceptance or Auditor approval. Preserve President photo/consent PASSes and preserve `.64`'s aggregate runtime result **FAIL 115/120**. Named passing assertions within that failed aggregate remain valid only for their individually reported scope; they do not turn the aggregate into PASS.

## Candidate and evidence identity
- Product change reviewed: `includes/class-zmp-mentor-school-profile.php`, SHA-256 `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`.
- Source review: `reports/CODE_REVIEW_MULTISCHOOL_SCHOOL_SCOPE_1.2.4.65_2026-11-05.md`, SHA-256 `C7C982D593F259014A93BBE6292FB14E1BB5168B77D3319FAB07CA043F70483F`, exact-source PASS for restricting all-school and explicit-school directory results to the viewer's authorized active engagements, preserving role checks, field projection, and email omission.
- Test fixture: `tests/phase65-multischool-directory-scope.php`, SHA-256 `467C7C473252C2DB1ED04961A0360D7688FF4604E83B6EA049C1840CE8DA06FB`.
- Fixture source review: `reports/CODE_REVIEW_MULTISCHOOL_DIRECTORY_SCOPE_FIXTURE_1.2.4.65_2026-11-05.md`, SHA-256 `9F7FDD1AD9D1E0B56A0A99CD328F6030A591141A75AF1A5458BDFCAD5356842A`, exact-hash PASS for fixture guards, scoped restoration, privacy-safe output, and candidate binding. It is a source review, not a runtime verdict.
- Independent Test Engineer execution: `reports/TEST_ENGINEER_MULTISCHOOL_DIRECTORY_SCOPE_1.2.4.65_2026-11-05.md`, SHA-256 `887919A3D5A2D6930187C4B58951EA0B9AB60D38172C2E8E41E0041CD5461E9F`; exact fixture and product hashes above; **PASS 24/24**. Fresh DB preflight and postflight checks PASS; installed source hash unchanged; staged fixture removed; server processes/port unchanged.
- Profile UI save/reload evidence: `reports/MULTISCHOOL_PROFILE_ACCEPTANCE_1.2.4.65_2026-11-05.md`, SHA-256 `3D72CE07C152C5DB678B0D8ECBA4F69BF2CB461425B724D7AF5981711B045DC5`. Actual authenticated Teacher profile displayed two schools and independently saved/reloaded school 17 fixed/full with only term visible, and school 18 indefinite/part/retired with only retired visible. Official email/replacement fields/dates were not entered in this `.65` UI exercise.
- Cleanup: synthetic Teacher #127, its exact mentor 16, profile rows and school-engagement rows were removed after verification; post-cleanup counts were zero for those exact records and `wp db check` PASS. No unrelated records were selected for deletion.

## Criterion-by-criterion reconciliation

| Acceptance criterion | Status | Evidence and limits |
|---|---|---|
| Multiple schools remain independent; new profiles default private | **PASS, scoped** | `.64` runtime assertions `profiles_isolated_by_school` and `new_school_profile_private_defaults` were among the 115 passing assertions, as recorded in `zmp-competitions-1.2.4.64/reports/FEATURE_RUNTIME_ACCEPTANCE_2026-11-03.md`. `.65` profile UI also persisted distinct values for schools 17 and 18. Preserve `.64` overall 115/120 FAIL. |
| Employment values and validation: fixed/indefinite, full/part, retired, replacement/colleague, valid period and reversed-period rejection | **PASS, scoped** | `.64` assertions `valid_private_profile_saved` and `reversed_replacement_period_rejected` passed; the valid payload includes replacement/colleague and a valid date range. `.65` UI exercised fixed/full, indefinite/part, and retired independently. Diff of the service class shows employment validation/save methods are unchanged; `.65` edits were in schema-readiness handling and directory scoping. Current `.65` UI did not exercise a positive replacement form submission. |
| Owner-controlled visibility for each employment field; defaults private | **PASS, scoped** | `.64` runtime checked all seven fields independently for show/hide and private defaults. `.65` UI confirmed independent per-school visibility for term and retired fields. `.65` directory fixture separately checks enabled fields appear and disabled fields are omitted. |
| Teacher and higher-role access; below-Teacher and unapproved-role denial | **PASS, scoped** | `.64` runtime covered Teacher use, moderator and active-President allow, and testator, subscriber, and mixed-role denial. `.65` fixture adds an exact synthetic Teacher positive case and existing Subscriber denial. These checks do not change `.64` aggregate status. |
| Directory results restricted to viewer's authorized active schools | **PASS** | Exact-source Code Review plus independent Test Engineer runtime verify connected-school inclusion, explicit connected-school requests, unrelated active-school denial, and all-school scope. |
| Hidden/private fields and official email omitted from directory service/shortcode output | **PASS, scoped** | `.64` per-field service projections and email omission remain valid. `.65` fixture confirms allowed projection, disabled-field omission, no email in rows or actual shortcode callback HTML. This does not prove other delivery surfaces. |
| Anonymous access denied | **NOT RUN** | No explicit named anonymous runtime assertion was identified in the bound `.64` evidence or `.65` fixture. Do not infer runtime PASS from role-gated source alone. |
| Privacy across direct routes, AJAX/REST, search, exports, cache, and logs | **NOT RUN / evidence map incomplete** | The new fixture exercises the service and shortcode callback only. Existing reports do not establish a current `.65` runtime matrix for each listed surface or confirm which surfaces are absent. Reconcile implemented route inventory and test applicable surfaces; for absent surfaces, record exact source evidence and why no route exists. |
| Profile fieldset keyboard and responsive behavior | **PASS, scoped** | Independent `.64` UI report `reports/UI_RENDERED_REVIEW_RETRY_2026-11-03.md` and responsive fallback `reports/UI_RENDERED_REVIEW_DEVTOOLS_RESPONSIVE_2026-11-03.md` cover the profile controls at mobile viewport; the `.65` acceptance report binds the profile UI to unchanged SHA-256 `88EA74E055F0E68FCE9F95F8EE97E75357B903DDBB76785E80199FC554A8FC9D`. This is profile UI evidence only. |
| Rendered directory responsive/mobile and keyboard review | **NOT RUN — acceptance requirement** | **Yes.** `.64/PLAN_1.2.4.64.md` acceptance criterion 5 expressly requires the directory to be responsive at mobile viewport and contain no hidden/private fields. The earlier `UI_RENDERED_REVIEW_PRESIDENT_DIRECTORY_2026-11-03.md` recorded blank shortcode pages and no content to inspect; the current `.65` fixture verifies callback HTML but is not browser/UI review. A rendered directory UI review remains required unless a current version plan explicitly removes that criterion (none identified). |
| Synthetic profile/account cleanup and DB integrity | **PASS** | Exact task-owned account/mentor/profile/engagement records removed after the independent Test Engineer run; DB check PASS. Test Engineer report confirms its run itself made no data-row changes. |

## Remaining action before closing this roadmap point
1. Reconcile the implemented employment-directory delivery surfaces against the `.64` privacy criteria. Exercise applicable anonymous/direct/AJAX/REST/search/export/cache/log paths with synthetic data; document absent paths by exact source evidence. Preserve all prior named PASSes and do not repeat unchanged validation/UI checks.
2. Complete an independent rendered directory UI review at mobile viewport, including visible/private-field absence. Keyboard review is required for the relevant controls if present; record precisely what could be inspected.
3. Update the point's acceptance ledger and Master reconciliation with exact evidence/hashes. Until then the point remains **PARTIAL PASS / NOT READY TO CLOSE**. Whole `.65` remains IN PROGRESS; this report does not authorize Auditor, ZIP, or GitHub synchronization.

## Additive reassessment — anonymous denial and disclosure-surface map — 2026-11-05

This addendum preserves the prior criterion findings and incorporates the newly available anonymous-access, debugger, route-inventory, and UI-review evidence. It does not claim phase acceptance.

### Anonymous behavior and database delta
- **Anonymous behavior: PASS 6/6**, independently executed by Test Engineer using fixture `tests/phase65-multischool-anonymous-denial.php`, SHA-256 `F7412092F0BDC869F2D8E1964E321827BF5593A4CC040CD62A25D14B792126D3`, bound to service SHA `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B` and renderer SHA `255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042`. Assertions covered anonymous context, service denial for directory and profile access, static login notice in shortcode output, absence of school/employment fields, and restoration of in-memory current-user context.
- Independent exact-hash fixture review: PASS in `reports/CODE_REVIEW_MULTISCHOOL_ANONYMOUS_FIXTURE_1.2.4.65_2026-11-05.md`, report SHA-256 `6E7FF9E3A82EB0F1BE2B95B6408C7DD348917991D907088C7F3EBF0F432DEE1C`.
- Runtime report: `reports/TEST_ENGINEER_MULTISCHOOL_ANONYMOUS_DENIAL_1.2.4.65_2026-11-05.md`, SHA-256 `D10C0FBCECD33368093BB48148A1C2EC2E76B7424C38E0480270F3DCB4BF0A7B`. Pre/post DB checks PASS; staged fixture removed; service and renderer hashes unchanged; no user, profile, or engagement writes were reported.
- **Exact no-mutation result remains INCONCLUSIVE.** The pre/post database export checksums differ (`ab10d0c0…b81981` vs `a256910b…c6bfc7`), and no pre-run option/database preimage was retained. The read-only Debugger report `reports/DEBUGGER_MULTISCHOOL_ANONYMOUS_DB_CHANGE_1.2.4.65_2026-11-05.md`, SHA-256 `8FA095C5E2374B301F266891FAE8EAAE4BF74037BC6F00C9A07794EE28326166`, traces the two expected sanitized `forbidden` diagnostic appends to `zmp_debug_events`; it found no product/profile/account/engagement write path. This explains the expected delta but cannot prove that it consists only of those two appends. Keep the diagnostics; do not claim exact no-mutation or attempt speculative restoration.

### Disclosure-surface inventory reconciliation
The route map is `reports/MULTISCHOOL_PRIVACY_SURFACE_MAP_1.2.4.65_2026-11-05.md`, SHA-256 `DF771625E857DFCB26A0A17E958862C5D5421B5957FA4D03291A20EB5369EB5A`.

| Surface | Reconciled status |
|---|---|
| Anonymous service/profile/shortcode behavior | **PASS 6/6**, with exact database no-mutation boundary still inconclusive as above. |
| Below-Teacher and Teacher+ roles | **PASS, scoped** from the named `.64` role matrix and independent `.65` directory-scope runtime; `.64` aggregate remains FAIL 115/120. |
| HTML/service projection | **PASS, scoped** for anonymous login notice, directory shortcode HTML, enabled/disabled fields, and official-email omission. |
| Feature-specific AJAX/REST | **N/A by source inventory:** no employment-profile or directory AJAX hook or REST route is registered in this candidate. School-email admin-post workflows are separate acceptance criteria. |
| Search/export | **N/A by source inventory:** the employment-directory shortcode has no search or export handler. |
| Direct profile route | Core WordPress profile hooks and owner/capability/nonces are source-mapped; **direct HTTP denial was not independently exercised in `.65`**. No separate public employment-profile route was found. |
| Cache | Source maps private/no-store/no-cache response headers and a transient limited to success/error notices; **browser transport-header behavior was not tested**. |
| Logs | `.64` diagnostic-redaction PASS remains bound to the unchanged logging path; current anonymous denials append only sanitized generic `forbidden` diagnostics by the Debugger's source analysis. Full exact option-state preservation remains inconclusive due the checksum delta. |

### Directory UI acceptance
Rendered directory review is an explicit acceptance requirement in `.64/PLAN_1.2.4.64.md`, criterion 5: responsive mobile directory with no hidden/private fields. Current UI Reviewer report `reports/UI_REVIEW_MULTISCHOOL_DIRECTORY_1.2.4.65_2026-11-05.md`, SHA-256 `A74B9A94C93EB11138EC1D91E601A0551DDD9EEB992A188643F46C75A651DF50`, is **BLOCKED / NOT RUN**. A task-owned Chrome attempt was closed at the login page because the only available synthetic admin credential was in a local `file:` path and browser policy explicitly refused access; no alternate credential retrieval or automation path was used. No login, page/account/data creation, or mutation occurred during that reviewer attempt. Therefore populated directory desktop/mobile rendering, visual field privacy, responsive behavior, and keyboard traversal remain **NOT RUN**. Preserve the profile UI responsive/keyboard PASS tied to unchanged profile UI hash `88EA74E055F0E68FCE9F95F8EE97E75357B903DDBB76785E80199FC554A8FC9D`; it does not substitute for directory UI review.

## Current disposition
Anonymous authorization behavior and the previously scoped role, per-school, employment, and field-visibility assertions now have criterion-mapped PASS evidence; AJAX/REST and search/export are N/A by source inventory. Remaining acceptance blockers are the inconclusive exact database no-mutation boundary for the anonymous run, the not separately exercised direct HTTP denial/cache transport behavior if required by the route criteria, and the required populated directory mobile/keyboard UI review. The multi-school point remains **PARTIAL PASS / NOT READY TO CLOSE**. `.64` aggregate FAIL 115/120 and all earlier PASS boundaries remain unchanged.

### Checksum values (exact)
The anonymous fixture report's pre-run database export SHA-256 is `ab10d0c0762e85b129d05e5baaf09af80b3f87398dfe6a4b6ab0d6e0b0b81981`; post-run export SHA-256 is `a256910b3c3387d7183a3006f9344151236b50bdaefd003887ce6eccb1c6bfc7`. The delta remains attributable by Debugger source-path analysis to the expected two sanitized debug-event appends, but the absence of a preimage prevents proof that no other database state changed.

## Additive reassessment — source-surface review and bounded UI follow-up — 2026-11-05

### Independent source-surface review
Independent Code Review `reports/CODE_REVIEW_MULTISCHOOL_PRIVACY_SURFACES_1.2.4.65_2026-11-05.md`, SHA-256 `009817045F296DEFC77171EC22307942F3BAEA238D5A9C3E53AB604EAD96713A`, reviewed the profile service (`8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`), profile UI (`88EA74E055F0E68FCE9F95F8EE97E75357B903DDBB76785E80199FC554A8FC9D`), and employment-directory renderer (`255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042`). **Source review PASS; no finding** in the assigned multi-school/privacy scope. It confirms the viewer-scoped school query, exact role gates, owner/capability/nonce profile controls, escaped shortcode output, no feature-specific AJAX/REST/search/export handler, private no-store cache headers, and non-sensitive diagnostics. This is source evidence, not a browser transport-header or route runtime check. AJAX/REST/search/export remain **N/A by source inventory** for this implementation; direct profile HTTP denial was not separately exercised, and cache-header transport was not verified in a browser.

### Bounded UI Reviewer follow-up
The UI Reviewer generated a distinct synthetic Teacher credential in memory and did not read the blocked credential file. WP-CLI created synthetic user #128; the subsequent `wp eval` setup attempt returned nonzero before temporary shortcode page/engagement setup could be confirmed. The failure cause was suppressed and is **NOT CAPTURED**; no retry or alternate credential retrieval was attempted. Exact synthetic user #128 was deleted, and verification found zero matching user, mentor, or temporary-page rows. `wp db check` PASS. The Owner's existing browser tabs were not touched; a new task-owned Chrome tab was opened without login and then closed. Clipboard was unchanged. Evidence is `reports/UI_REVIEW_MULTISCHOOL_DIRECTORY_1.2.4.65_2026-11-05.md`, SHA-256 `17462820D86DB4AAA2DCCE5332157073EC10AD5C3CC4A1B2B97833D3638CF02B`.

**Directory UI remains BLOCKED / NOT RUN.** The fresh synthetic setup attempt did not reach a populated directory page, so desktop/mobile rendering, field privacy in the browser, responsive behavior, and keyboard traversal remain unobserved. No product failure is inferred. The directory responsive/mobile requirement in `.64/PLAN_1.2.4.64.md` criterion 5 remains open; the prior profile keyboard/responsive PASS bound to unchanged profile UI hash remains preserved and does not satisfy directory UI review.

## Current disposition after new evidence
The source-surface map is now independently reviewed PASS, anonymous behavior remains PASS 6/6 with exact database no-mutation still inconclusive, and feature-specific AJAX/REST/search/export are N/A by source inventory. Direct HTTP and cache transport runtime checks retain their stated evidence limits. The required populated directory UI review is still BLOCKED / NOT RUN after a bounded fresh-account attempt. Overall multi-school status remains **PARTIAL PASS / OPEN — NOT READY TO CLOSE**. The `.64` aggregate remains **FAIL 115/120**; no prior scoped PASS is withdrawn or elevated.

## Additive update — anonymous direct profile GET — 2026-11-05

The Test Engineer report `reports/TEST_ENGINEER_ANONYMOUS_PROFILE_HTTP_1.2.4.65_2026-11-05.md` SHA-256 `92E28EF3527BD4F6C1ECFD4BE74DC14F1DFEDC5584AB227E12A8B1B7426AC69F` records one unauthenticated loopback GET to `/wp-admin/profile.php` on the existing `.65` site. It returned HTTP 302 to `/wp-login.php`; no private/profile/school/employment markers were present in the response body. Observed `Cache-Control` was `no-store, must-revalidate, no-cache, max-age=0, private`; `Pragma` and `X-Frame-Options` were absent on this redirect response.

This closes **only** anonymous direct-GET handling and its observed response `Cache-Control` behavior. It does not establish authenticated role denial or framing policy; no DB comparison was made. Exact DB no-mutation for the earlier anonymous fixture remains inconclusive. The populated directory responsive/mobile/keyboard review remains BLOCKED / NOT RUN. Do not repeat the GET absent changed code/configuration or evidence invalidity. President photo/consent PASSes and `.64` aggregate FAIL 115/120 remain unchanged.

## Current disposition after anonymous direct-GET evidence
Anonymous service/profile/shortcode denial remains PASS 6/6; the new direct profile GET and observed Cache-Control response are PASS for their narrow scope. Feature-specific AJAX/REST/search/export remain N/A by source inventory. Exact no-mutation for the anonymous service fixture, authenticated role boundary, and populated directory responsive/mobile/keyboard UI remain open. Multi-school remains **PARTIAL PASS / OPEN — NOT READY TO CLOSE**; whole `.65` remains IN PROGRESS.

## Additive update — authenticated Teacher queue-denial setup — 2026-11-05

Independent Test Engineer attempted one bounded CUA setup for the authenticated Teacher negative-role direct queue GET. The attempt failed before browser/tab-state enumeration with exact error: `failed to write kernel assets: The system cannot find the path specified (os error 3)`. No tab/page/request was touched, no login or credential access occurred, and no site/database state changed. Report: `reports/TEST_ENGINEER_SUBJECT_QUEUE_TEACHER_DENIAL_CUA_BLOCKER_1.2.4.65_2026-11-05.md`, SHA-256 `8FE0B9DA2E4720087BCF53CBE61B927559EF1B1CD540213045E584293EBC3341`.

Status remains **NOT RUN** for authenticated Teacher queue denial. This browser setup failure establishes neither a product defect nor a PASS. Do not try an alternate browser surface. Authenticated role boundary, exact database no-mutation for the earlier anonymous fixture, and populated-directory responsive/mobile/keyboard UI remain open; multi-school remains **PARTIAL PASS / OPEN**.
