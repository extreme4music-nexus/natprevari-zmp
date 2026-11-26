## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Exact-hash Code Review — first-catalog active-President route fixture — 1.2.4.65

## Verdict
**FAIL — test candidate is not ready for runtime.** Candidate SHA-256 reviewed: `B33C3E824B06A695C60C062938283A2DF0317A91A466BBA6EF1F8B002755F5CA`. No route/runtime execution was performed.

## Findings

### P1 — Calls a private proposal-normalization method
- File: `tests/phase65-subject-first-catalog-active-president-route.php`, line 170.
- Evidence: the fixture calls `ZMP_Mentor_Subject_Proposals::normalize_name($phrase)`, but the bound product source declares `normalize_name()` as `private static` at `includes/class-zmp-mentor-subject-proposals.php:335`.
- Impact: PHP throws an access error before the Teacher route request, so the candidate cannot reach its assigned acceptance behavior.
- Required fix: derive the deterministic test subject key in fixture code from the synthetic ASCII phrase, or use a public API that does not write before the route. Do not expose/change product visibility just for the test.

### P2 — Cleanup helper may be undefined under WP-CLI
- File: `tests/phase65-subject-first-catalog-active-president-route.php`, line 294.
- Evidence: cleanup calls `wp_delete_user()` without requiring `wp-admin/includes/user.php`. The fixture is invoked by `wp eval-file`; this helper is conditionally loaded in the older bounded route fixture, not guaranteed by this candidate's bootstrap.
- Impact: if the route reaches cleanup, an undefined-function fatal can prevent the synthetic Teacher and related rows from being cleaned up and verified.
- Required fix: explicitly require the core user helper before the test enters its mutation window, and ensure the cleanup's failure path stays bounded and reports incomplete cleanup.

## Scope and safeguards reviewed
The exact candidate is bound to `zmp65_isolated`/`z65_`, loopback `127.0.0.1:8094`, a candidate-only active plugin list, exact route-source hashes, empty subject proposal/event/catalog/alias tables, and exactly one synthetic active President. It snapshots user/usermeta, posts/postmeta, options, subject/mandate tables and diagnostic options; constrains HTTP to the exact local admin-post endpoint; blocks/counts mail and other HTTP; checks Teacher submission, President `approve_new`, duplicate decision, canonical/audit readback, response disclosure, and cleanup. It contains no MU-plugin trace-marker/marker-file logic and does not reference or modify the older route candidates. These safeguards do not offset the execution and cleanup blockers above.

## Review limits
This is a test-fixture review, not a product route verdict. The earlier trace-marker failures are not repeated or reinterpreted. Runtime remains NOT RUN pending a corrected candidate, PHP lint, and new independent exact-hash review.
