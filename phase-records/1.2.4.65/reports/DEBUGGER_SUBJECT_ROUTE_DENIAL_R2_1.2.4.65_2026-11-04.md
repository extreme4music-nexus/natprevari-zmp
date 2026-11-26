## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger — Subject route denial R2 (`.65`)

Date: 2026-11-04  
Scope: read-only review of the route-denial fixture, its exact-hash reports, and the `.65` product authorization code. No site, database, browser, or runtime access; no rerun.

## Evidence reviewed

- Runtime fixture `tests/phase65-subject-president-route-denial-clone.php`, SHA-256 `83A96C6127BD41B781D565AFFF2B1FE9893CBBF6A1DA7870337BBC2956F75DAF`.
- Test Engineer result `reports/TEST_ENGINEER_SUBJECT_ROUTE_DENIAL_R2_1.2.4.65_2026-11-04.md`, SHA-256 `68DB825F99490946C7AF352FC39734FC432B865EDB663A8F3D4069AE500A7BCA`.
- Exact Code Review `reports/CODE_REVIEW_SUBJECT_ROUTE_DENIAL_FIXTURE_R2_1.2.4.65_2026-11-04.md`, SHA-256 `6AB84CC27A56572B0937275FB3BA00A7A1D1F5B2653482F394307F196DBECCE1`.
- Product class hashes guarded by the fixture: `class-zmp-president-subject-approvals.php` `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`; `class-zmp-mentor-subject-proposals.php` `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`.
- `class-zmp-president-dashboard.php` source review for role-scoped admin routing.

## Finding

The R2 runtime remains **FAIL 9/10**. The direct President queue GET returned HTTP `302` instead of the fixture's expected `403`; no `Location` header or server-side actor identity was recorded. The nonce-valid decision POST returned the expected generic HTTP `403`. No subject details or decision controls were disclosed; the proposal remained pending; catalog and alias tables remained empty; cleanup and post-run database integrity passed. Do not rerun either the prior `742C...` or R2 `83A96...` fixture hash.

Product source does not establish a defect. `ZMP_President_Subject_Approvals::register_menu()` only registers the page when `can_review()` confirms the active President role; its render callback and the decision callback both independently deny actors who fail that check. The President dashboard's `enforce_admin_scope()` targets President-only users; it does not explain a Teacher redirect. The runtime report's generic decision denial is consistent with the registered authenticated `admin_post_zmp_president_subject_decision` handler being invoked, but it does not prove what identity WordPress resolved on the separate GET.

An unauthenticated admin request being redirected to login is a plausible explanation for the GET `302`, but it is **only a hypothesis**. A route-specific or environment redirect is also not ruled out. Since the redirect target and request actor were not captured, neither the expected authenticated-Teacher denial nor a product authorization bypass is established. Keep the criterion **NOT VERIFIED** and preserve the 9/10 FAIL. Do not relax the 403 expectation or modify product code from this evidence.

## Smallest next diagnostic

If the direct queue denial remains a required acceptance criterion, use one new test-only, exact-reviewed diagnostic on the same verified disposable clone: first issue a read-only authenticated GET to the Teacher's allowed `/wp-admin/profile.php` as a positive cookie control; then issue the direct queue GET once, recording its status and only an allowlisted redirect-path class (for example, login, profile, or other admin path). Also record a minimal test-owned server-side marker of the resolved current user ID/role and requested page, without response body, cookie, nonce, profile values, or raw URL. Require the control to prove the intended synthetic Teacher session before interpreting the queue response. Keep the strict 403 acceptance for that authenticated queue request and preserve state/cleanup checks. If authentication cannot be proven, report the role-denial route BLOCKED/NOT VERIFIED; do not claim product failure or PASS. Independent exact-hash review must precede any such run.

No changed product file, confirmed product defect, or test candidate was produced by this Debugger review.
