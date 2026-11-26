# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Multi-school privacy surface map — 1.2.4.65

Date: 2026-11-05. Scope is the school-profile/employment-directory part of the `.65` multi-school point. This is not a whole-phase privacy verdict.

## Evidence and changed-code boundary
- `.64` scoped PASSes for private defaults, second-school independence, valid replacement payload/reversed-date rejection, seven visibility fields, role matrix, and profile UI keyboard/mobile behavior remain preserved. The `.64` whole composite stays **FAIL 115/120**.
- `.65` profile UI and directory renderer match `.64` exact hashes: `class-zmp-school-profile-ui.php` `88EA74E055F0E68FCE9F95F8EE97E75357B903DDBB76785E80199FC554A8FC9D`; `class-zmp-employment-directory.php` `255A9673C082F3472DCDEF676DC80E8F63C70AF68B14CD2AC56919F936FD7042`.
- The `.65` profile-service changes are schema-readiness wrapper and directory school-scope query. The employment validation/payload methods are unchanged. Service candidate SHA: `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`.
- Directory scope/source and exact fixture reviews PASS; independent TE directory fixture PASS 24/24. Exact report artifacts are `CODE_REVIEW_MULTISCHOOL_SCHOOL_SCOPE_1.2.4.65_2026-11-05.md`, `CODE_REVIEW_MULTISCHOOL_DIRECTORY_SCOPE_FIXTURE_1.2.4.65_2026-11-05.md`, and `TEST_ENGINEER_MULTISCHOOL_DIRECTORY_SCOPE_1.2.4.65_2026-11-05.md`.
- Anonymous runtime fixture exact SHA `F7412092F0BDC869F2D8E1964E321827BF5593A4CC040CD62A25D14B792126D3`, source-review artifact `CODE_REVIEW_MULTISCHOOL_ANONYMOUS_FIXTURE_1.2.4.65_2026-11-05.md`, and Test Engineer report `TEST_ENGINEER_MULTISCHOOL_ANONYMOUS_DENIAL_1.2.4.65_2026-11-05.md`. **Product behavior PASS 6/6**, but data-mutation verification is **BLOCKED/inconclusive** because database export checksums differ and no exact preimage was available; do not claim no-mutation or speculative cleanup.

## Delivery-surface inventory
| Surface | Current `.65` behavior/evidence | Status |
|---|---|---|
| Anonymous directory/profile | Anonymous service calls return `forbidden`; actual shortcode emits login notice without school/employment fields. Anonymous fixture PASS 6/6. A separate actual anonymous GET was bracketed by independent normalized full DB export hashes with exact equality before/after. | PASS for the externally reachable shortcode GET; internal fixture's expected sanitized diagnostic write remains preserved |
| Below-Teacher / Teacher+ roles | `.64` scoped role matrix preserved; `.65` independent fixture confirms Teacher allowed and exact synthetic Subscriber denied. | PASS, scoped |
| HTML/source and hidden fields | Renderer is shortcode-only; it escapes output and only renders fields projected by the service. `.65` directory fixture verifies only enabled fields and no email/disabled fields in HTML. | PASS, scoped |
| Direct profile URL | WordPress profile controls use core profile hooks, `edit_user` authorization, and nonce-protected save; service enforces owner/current-user and exact Teacher role. No separate public profile route found. | **Anonymous GET PASS, narrow:** 302 to login, no private markers; authenticated role denial remains unverified. Evidence `reports/TEST_ENGINEER_ANONYMOUS_PROFILE_HTTP_1.2.4.65_2026-11-05.md`. |
| AJAX / REST | No profile or directory-specific `wp_ajax_*` hook or REST route is registered. | N/A (source inventory); school-email admin-post routes are a separate requirement |
| Search / export | Directory exposes no search or export handler; shortcode is the only employment-directory surface located. | N/A for this directory implementation |
| Cache | Directory service applies WordPress no-cache headers and `Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0`; the profile UI transient stores only a 60-second success/error notice, not profile values. One authenticated populated-directory GET returned HTTP 200 with private/no-store/no-cache/must-revalidate/max-age=0, Pragma no-cache, and expired Expires. | PASS for this server-response header capture only. Exact DB/session no-mutation is NOT VERIFIED; raw export hashes differed and were not normalized; a later diagnostic probe may have appended a sanitized event. Proxy/CDN behavior not tested. See `reports/TEST_ENGINEER_MULTISCHOOL_AUTHENTICATED_CACHE_TRANSPORT_1.2.4.65_2026-11-05.md`. |
| Logs | Profile-save diagnostic context contains user ID, school ID, email-changed boolean and outcome; it does not pass email/employment/visibility values. `.64` focused diagnostics redaction assertions PASS remain bound to unchanged logging path. | Scoped source/runtime PASS; no new values logged by directory read |
| Rendered directory mobile/keyboard | Independent UI Reviewer saw populated Teacher groups at desktop/mobile, keyboard focus and menu behavior, semantic headings/lists, no horizontal overflow, and omission of private email/replacement details. Independent Test Engineer verified persisted profile reload and grouped values. Anonymous GET showed only the login notice; normalized full DB hashes matched pre/post in separate Test Engineer and Lead runs. The anonymous page response itself had no `Cache-Control` header. | PASS for populated responsive/keyboard UI and exact no-mutation of the shortcode GET. Authenticated directory cache transport separately PASSes for the single server-response header capture; its exact DB/session no-mutation is NOT VERIFIED and proxy/CDN behavior was not tested. Evidence: `UI_REVIEW_MULTISCHOOL_DIRECTORY_FOLLOWUP_1.2.4.65_2026-11-05.md`, `TEST_ENGINEER_MULTISCHOOL_DIRECTORY_POPULATED_RUNTIME_1.2.4.65_2026-11-05.md`, `LEAD_ANONYMOUS_DIRECTORY_DB_SNAPSHOT_1.2.4.65_2026-11-05.md`, and `TEST_ENGINEER_MULTISCHOOL_AUTHENTICATED_CACHE_TRANSPORT_1.2.4.65_2026-11-05.md`. |

## Historical conclusion at initial checkpoint — superseded for named gates below
The service/query privacy gap found in `.65` was fixed and verified. Unchanged `.64` PASS evidence is preserved rather than repeated. At this checkpoint, populated directory UI was still open and the earlier direct in-process anonymous fixture had an inconclusive DB checksum because no preimage was retained. These point-in-time findings are superseded only by the later actual shortcode GET snapshot and authenticated populated-directory UI evidence below; broader authenticated cache transport and the integration role matrix remain open. No new `.65` endpoints were found; no application routes were created for testing.

### Debugger reconciliation — anonymous-check database checksum — 2026-11-05
Read-only source-path triage in `reports/DEBUGGER_MULTISCHOOL_ANONYMOUS_DB_CHANGE_1.2.4.65_2026-11-05.md` (SHA-256 `8FA095C5E2374B301F266891FAE8EAAE4BF74037BC6F00C9A07794EE28326166`) confirms the two expected anonymous denial calls append sanitized events through `ZMP_Debug::log()` to `zmp_debug_events`; the fixture has no other DB writes, anonymous shortcode output is static, and current-user changes are in memory. No profile/account/engagement write path or product bug was found. Keep these diagnostic events. The prior in-process fixture run still lacks an exact preimage; this does not contradict the later PASS for the externally reachable anonymous shortcode GET. No speculative option reset/deletion is authorized or performed.

## Current conclusion — 2026-11-05 scoped reconciliation
The service/query privacy gap is fixed and verified. Current named multi-school gates PASS within scope: populated Teacher directory UI, exact no-mutation for one anonymous shortcode GET, and server-response cache headers for one authenticated populated-directory GET. The authenticated cache capture does not verify exact session/database no-mutation or proxy/CDN behavior; the wider integrated direct URL/AJAX/REST/role matrix remains NOT RUN. Preserve the prior direct service-denial fixture's sanitized diagnostic events. The `.64` aggregate FAIL remains historical and unchanged. See `reports/MASTER_REVIEW_MULTISCHOOL_GATES_1.2.4.65_2026-11-05.md` and `reports/MASTER_REVIEW_MULTISCHOOL_CACHE_TRANSPORT_1.2.4.65_2026-11-05.md`.
