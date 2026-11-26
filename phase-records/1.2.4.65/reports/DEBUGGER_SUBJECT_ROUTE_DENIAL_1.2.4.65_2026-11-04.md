## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger — Subject route denial semantics

**Version:** 1.2.4.65  
**Scope:** read-only source and installed WordPress core review; no fixture rerun, database access, or browser interaction.  
**Finding:** the 403 expectation for a direct queue URL is consistent with current product registration and WordPress core dispatch. The 9/10 runtime result is still unresolved because the exact observed status was not recorded. No product defect or wrong assertion is established by the available evidence.

## Exact reviewed source

- Route-denial fixture `tests/phase65-subject-president-route-denial-clone.php`: SHA-256 `742C17BA22BD9ED103292A832CCFE1D3A6D102D3A42ADE32B6E9BCDBFE8C6425`.
- `includes/class-zmp-president-subject-approvals.php`: SHA-256 `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`.
- `includes/class-zmp-mentor-subject-proposals.php`: SHA-256 `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`.
- Installed WordPress core reports version `7.1.4`; `wp-admin/includes/plugin.php` SHA-256 `8ce517bd2907759934c76663571d5c3ed52ca705c87ecc0063a11fc4c972d02e`; `wp-admin/includes/menu.php` SHA-256 `de7a1b71f854ea53d2f48bb36eb69a0b55aff170d9f4975592fc1a9e1fdf6e43`.

## Source behavior

`ZMP_President_Subject_Approvals::init()` attaches `register_menu()` to `admin_menu`. `register_menu()` returns without calling `add_submenu_page()` unless `can_review()` passes. `can_review()` requires a logged-in user with the exact `zmp_president` role, without `manage_options`, and a current active mandate (`class-zmp-president-subject-approvals.php:10–36`). The page callback itself also returns HTTP 403 when invoked for a user who fails `can_review()` (`:55–57`).

For a non-President Teacher, the queue page is therefore not registered. The installed core's `user_can_access_admin_page()` checks that a requested plugin page is in `$_registered_pages`; it returns false when the hook is absent (`wp-admin/includes/plugin.php:2182+`). The admin menu bootstrap responds to failed page authorization with `wp_die(..., 403)` (`wp-admin/includes/menu.php:375–382`). Under this source path, a normal direct request to the hidden queue URL is expected to be denied with 403. The UI callback's own 403 is a second defense if it is invoked.

The decision mutation is separately registered as authenticated `admin_post_zmp_president_subject_decision`. Its handler performs the `can_review()` check before method/action/nonce validation and returns a generic 403 on failure (`class-zmp-president-subject-approvals.php:150–160`). The service `ZMP_Mentor_Subject_Proposals::decide()` independently requires the current logged-in actor to be the active President (`class-zmp-mentor-subject-proposals.php:131–150,325–333`). The fixture's valid-nonce Teacher decision POST returning 403 is consistent with this contract.

## Reconciliation of the 9/10 result

The Test Engineer report records one failed assertion, `teacher_direct_queue_url_returns_403`; the other nine passed, including no subject/control disclosure, decision POST 403, unchanged pending state, no catalog/alias mutation and cleanup. The report explicitly says the actual direct-queue response code was not printed. Consequently:

- The strict 403 assertion matches the intended route-denial path and installed core behavior; it should not be relaxed solely to make the fixture green.
- The passing non-disclosure assertion is valuable but does not prove the expected HTTP response or that authorization followed the intended path.
- No product-code fix is warranted from this result alone. There is no evidence that the queue content leaked or that the decision mutation bypassed authorization.
- The 9/10 result remains **FAIL / unresolved**, not PASS. No same-hash rerun was performed.

## Next bounded diagnostic

If closing the direct-URL status criterion is required, prepare a new test-only exact-hash candidate that records, without sensitive body data, the actual response status, redirect location class, whether the authenticated actor is recognized in the request, and whether the queue callback is registered for that request. Keep the existing 403 requirement and non-disclosure/no-control assertions. Independently review that candidate before any one-time run on the disposable synthetic clone. If the candidate returns a non-403 while the protected body/controls remain absent, investigate the request/bootstrap path before deciding whether the acceptance contract permits a generic denial. Do not infer or claim the status from the previous report.
