## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Initial privileged-route inventory — ZMP 1.2.4.66.b

Date: 2026-11-12. Static source mapping only; no database, server, browser, or code was changed. This is not a full inventory and no runtime PASS is claimed.

## Mapped route families

- President subject decision: `ZMP_President_Subject_Approvals::handle_post()` checks active-President scope, nonce/method, and binds `ZMP_Passkeys::verify_step_up()` to the exact proposal, decision and resource. The service rechecks actor/current assignment, locks the exact pending proposal, and conditionally updates only that pending row. No bypass was identified in this static mapping. `.66.a` browser attempt still failed to submit a decision; that separate gate remains FAIL in `.66.a`.
- President governance documents: upload/archive/publish handlers use admin nonce/capability checks; acknowledgements and downloads use active-President mandate and exact obligation/document scope. No bypass identified in sampled paths. Which actor should perform admin-only governance changes remains a scope question, not a defect claim.
- Committees/organizers: assignments use moderator/admin capability and nonce; invitations use expiring single-use hashed tokens, nonce and exact assignment; organizer messages/read markers are limited to assigned recipients. No bypass identified in sampled paths.
- Jury/scoring: shortcode POST routes use login, capability/action allowlists, nonce, active season, enabled category and assignment checks; assessment/finalization routes call phase/entry guards. `admin_post_zmp_jury_print_roster` checks capability, season/category/phase and nonce bound to those values, then filters output by juror assignment/region. No bypass confirmed in static mapping. No `verify_step_up` call was found in `class-zmp-category-jury.php`.
- Teacher registration/roster downloads, register export, regional jury assignment and video upload/check/finalize routes have teacher ownership, exact entry/season/category scopes, role allowlists and nonces in the sampled handlers.
- Legacy competition AJAX rejects restricted jurors from unscoped moderator/commission actions; one historical-attempt scope question remains around checking `active_competition()`.
- No `register_rest_route` occurrence was found in the phase plugin source during this search.

## Unresolved scope and next safe action

The `.66.b` roadmap requires approved privileged actions to bind step-up to the exact action/resource, but it does not enumerate which actions need step-up. Do not infer that every route requires it or that the absence of step-up on a read-only roster print is a defect. The Owner has been asked to select the first action. Continue the complete route/action inventory and define route-specific positive/negative synthetic cases. No `.66.b` runtime tests or code edits have occurred.

## Search coverage note
A source search found 111 registration lines for `admin_post_*` and/or `wp_ajax_*` across the `.66.b/includes` PHP files; this is not a deduplicated route count and is not a complete per-route review. No `register_rest_route` call was found. Within the passkey implementation, `verify_step_up()` is currently used by the President subject-decision path; the `.66.b` approved-action list remains to be specified before extending that mechanism.

A bounded source read of legacy moderator attempt control found it is incident-linked to the exact attempt/student/competition and permits previously completed attempts by design comment; whether the `.66.b` active-competition restriction should apply to historical attempt control is an unresolved policy question, not a confirmed defect.
