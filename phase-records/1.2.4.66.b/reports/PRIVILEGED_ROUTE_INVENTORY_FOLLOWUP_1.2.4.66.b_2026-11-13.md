# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Privileged route inventory follow-up — ZMP 1.2.4.66.b

Date: 2026-11-13. Static code-path inventory only; no route was executed and no database/UI state was changed. This extends, but does not complete, `PRIVILEGED_ROUTE_INVENTORY_INITIAL_1.2.4.66.b_2026-11-12.md`.

## Additional route families mapped

- **President subject proposal decision (write):** `includes/class-zmp-president-subject-approvals.php` plus `class-zmp-mentor-subject-proposals.php`. Admin-post path requires authenticated active President role/mandate, capability, POST/action, nonce, exact proposal/decision/canonical identity and fresh step-up binding; service rechecks same actor, active mandate, pending row and transaction scope. This is a global subject-catalog operation, not a region/category assignment.
- **Teacher / President profile writes:** core profile POST in `class-zmp-support.php`, school profile in `class-zmp-school-profile-ui.php` and `class-zmp-mentor-school-profile.php`. Checks include edit-user capability, nonce, target self/role and exact owned-school ID. Whether President profile updates must additionally stop when a mandate ends was not found as a stated requirement in the reviewed scope; retained as a scope question, not a defect finding.
- **Document review and correction upload:** `class-zmp-document-review.php`. Review is authenticated, document-scoped nonce, enumerated decision, reason validation, exact document→entry→season/category join and moderator/admin or assigned-juror visibility. Correction upload requires exact entry owner, `needs_correction` state, scoped document/entry/season and one-time repair-token rules; token endpoints use bearer-token hash/expiry/unused validation and upload nonce.
- **Sampled AJAX families:** incident/complaint/testator/disqualification/DB-test/live-student operations in `class-zmp-support.php` are logged-in routes with action nonce, role checks and sampled school/attempt scope. The full chain for every AJAX action and other route classes is not yet traced.
- No REST route registration was found in the targeted pass; this does not constitute a repository-wide proof that REST routes are absent.

## Passkey eligibility correction linked to route inventory

A code review found regional-only commission assignments authorize regional jury work but were omitted by passkey self-enrollment eligibility. Updated `ZMP_Passkeys::can_register_for_user()` to consider active-season regional category assignments only when the regional migration is complete and then validate through `ZMP_Category_Jury::has_any_assignment()`. General category assignments retain existing behavior; municipal assignment rules are unchanged. Source/runtime hash match and PHP lint pass. Exact-hash focused code re-review confirmed this correction. The earlier dynamic-check NOT RUN status was superseded by the Owner-run focused synthetic runtime check recorded in `TEST_REPORT_1.2.4.66.b.md`: regional-only assignment recognized PASS, no-assignment denied PASS, cleanup PASS; no passkey was enrolled.

## Remaining scope

This report is static inventory, not proof of runtime authorization. Individual traces remain for mandate administration, approved-audio tools, registration requirements, circular invitations, governance/facsimile, YouTube assignment/upload, communications and additional AJAX classes. Do not infer a new step-up requirement absent an Owner-approved action. No phase-wide acceptance, package, GitHub sync or Auditor decision is implied.

## Additional static route traces — 2026-11-13

### Admin audio catalog
`includes/class-zmp-approved-audio-tools.php` exposes authenticated admin-post create/audit/approve/reject/archive actions; all are `manage_options` only, with a common save nonce or per-resource nonces and exact option item IDs. Review fields and fresh audit are required for approval. Writes are single option updates (no multi-row transaction). A scheduled daily WP-Cron handler mutates audit/cursor state for up to two approved entries; it has no user/nonce because it is a scheduled system action. No runtime check performed.

### Circular invitations
`includes/class-zmp-circular-invitations.php` uses moderator/admin capability and a shared nonce for draft save, approval, and send. Draft updates are limited to exact draft IDs and validated season/category/group/contact snapshots. Approval conditionally transitions exact draft rows and creates recipient rows transactionally. Send uses an exact invitation ID/status transition and per-recipient token hashes/state; it calls `wp_mail`. This earlier checkpoint did not execute the send route under the then-recorded real-email prohibition. Owner clarified on 2026-11-13 that synthetic email is authorized when intercepted locally with no external delivery. The invitation send route was subsequently exercised with a synthetic `.invalid` recipient and in-memory `pre_wp_mail` interception; see the Owner-run runtime evidence appended below. Public print is read-only and requires an exact sent recipient plus matching bearer-token hash. No runtime check performed.

These traces extend static inventory only. No new step-up requirement is inferred. They do not establish route-wide runtime authorization PASS or whole-phase acceptance.





## Runtime authorization sample — circular invitation send — 2026-11-13
Synthetic Teacher user 117 was rejected with HTTP 403 before nonce/resource processing. The read-only WP-CLI check created no fixture and sent no email. This is one authorization sample only; draft/approval and other route families remain untested. See TEST_REPORT_1.2.4.66.b.md and 
`reports/synthetic-invitation-authorization-check.php`.


## Current-hash jury finalization guard — 2026-11-13
The actual `.66.b` jury handler was invoked on the isolated synthetic site using the existing confirmed fixture and no passkey proof. It redirected to `step_up_required`; result row and confirmation-audit count were unchanged. Candidate/runtime handler SHA-256 `3710600BDBE4B3B3506F4DB3FA99BB5F85F4824008EDEF4EBC7ACCDE0E7A1150`. PHP lint PASS. No credential prompt, new fixture or database mutation. Retained helper SHA-256 `93F089E176FF683F9FD88BDE92DD6043EAF728999BAA4764B4EBC8E559AA5F65`; executed temporary copy SHA-256 `4731E862FF10FB7541CC4BA2B24E67DD8367979867B8B33CA8BAF52FD8073282` (not byte-identical; both recorded). This covers no-proof rejection only; success/replay/altered-context and remaining route/release gates remain open.

## Runtime authorization matrix — circular invitations — 2026-11-13
The isolated synthetic Teacher was denied with HTTP 403 on invitation save, approval, and send actions. Invitation and recipient table counts remained unchanged; mail transport was not invoked. Candidate/runtime handler SHA-256 matched `C2D2F944A576D92D52D34F16405B6217B389D37905F731359AE19923D927C107`. PHP lint PASS. Report helper SHA-256 `CD6BE45C6A3BDF5708F3949BDE2BAA0DE2DDFD7618DDDAF832C11DDE1D35EE63`; temporary WSL copy SHA-256 `8056156B9BFBDF0607671FE402E66947DAAB3A20E83690534DA8F2703844A777` (not byte-identical; recorded separately), removed after run. This covers role denial only; allowed moderator paths are NOT RUN.

## Additional route-family map — communications, role messenger, school-email access — 2026-11-13
This is read-only source-path inspection; it is not runtime evidence.

- **Communications** (`includes/class-zmp-communications.php`): announcement publish/draft publish require moderator capability or administrator and a shared nonce; draft updates/publish are exact-ID and draft-state scoped; result publication rechecks confirmed results; announcement, recipient notices and post sync use a transaction. Notification read is login + nonce and updates only the current user's exact unread notification. Teacher inbox is current-user scoped. Runtime paths NOT RUN.
- **Role Messenger** (`includes/class-zmp-role-messenger.php`): send/export/attachment access uses current season and `can_access_room()` role + category/region assignment scope; send is transaction-backed and root-thread scope is checked. Attachment download also checks ID-bound nonce and private-file path/hash/size. Review requests are requester/thread/scope bound; admin review is `manage_options` with President excluded, exact pending request and bounded thread, with transactional audit/state update. Notice acknowledgement is recipient/unread/current-room scoped. Retention deadline is moderator + thread nonce; retry is admin-only with President excluded. No new step-up requirement inferred. Runtime paths NOT RUN.
- **School email access** (`includes/class-zmp-school-email-access.php`, `...-ui.php`): request creation binds current teacher, exact school ownership/current engagement and target teacher; decision requires active President mandate, ID-bound nonce, exact pending row and revalidated current relationships, with a transaction. Disclosure rechecks requester, approved request, current school/teacher relationship, and contact email hash/epoch before returning email. Runtime paths NOT RUN.
- No concrete code defect was established from this static pass. Scope question, not a finding: whether moderator global announcement/result publication routes are intended to remain globally moderator-scoped or should follow category/region scope. Do not change behavior absent approved requirement.
