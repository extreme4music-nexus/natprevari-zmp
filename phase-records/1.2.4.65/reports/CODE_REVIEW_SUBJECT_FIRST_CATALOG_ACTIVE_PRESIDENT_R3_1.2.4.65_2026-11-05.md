## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Exact-hash Code Review — first-catalog active-President route fixture — revision 3

## Verdict
**FAIL — cleanup can leave partial route records if an exact owned catalog row cannot be confirmed.** Candidate SHA-256 reviewed: `5DCE3E2710829C6E67EA0511B2F2C7B8462AAE698BA9A728E6F8D8EDAB97C8D1`. No runtime execution was performed.

## Finding

### P1 — Proposal/audit rows are deleted before the canonical row is validated and deletable
- File: `tests/phase65-subject-first-catalog-active-president-route.php:278–293`.
- Evidence: after a proposal is deemed safe, cleanup deletes each attached event and the proposal (lines 281–286), then queries/validates the canonical subject and only then tries to delete it (lines 287–293). If the route committed `approve_new` but `$canonical_id` is still zero because the test stopped before readback, or if the canonical row exists with unexpected fields, cleanup removes proposal/audit provenance and leaves the subject row. If one event deletion fails, cleanup continues deleting later events and the proposal.
- Impact: an ambiguous/partially failing test can leave incomplete or orphaned catalog state in the authorized site and cannot restore the exact empty-table baseline, despite trying to clean up.
- Required fix: before deleting any row, read and validate the entire candidate state, including a unique canonical row whose `subject_key`, name, actor, status and proposal reference match this run. If the state is not one of the exact safe pending/approved shapes, preserve all related rows and mark cleanup incomplete. If an owned approved row can be established, delete in a transaction or otherwise stop immediately on any failed delete and verify each expected baseline. Add bounded late-commit readback after transport ambiguity without issuing another route request.

## Re-review of prior findings and safeguards
- President login/email are now captured by the no-disclosure closure.
- The test locally derives the ASCII synthetic key instead of calling the private product method.
- `wp-admin/includes/user.php` is required before `wp_delete_user()` is used.
- The reviewed file is bound to exact local database/prefix/loopback route, candidate-only active plugin and exact product hashes. It requires empty proposal/event/catalog/alias tables and exactly one synthetic active-mandate President. It snapshots the users/usermeta, post/postmeta, options, proposal/catalog, and mandate tables; guards mail and external HTTP; asserts Teacher submission, active-President `approve_new`, duplicate denial and audit/canonical readback. No MU trace-marker/marker-file logic or references to older route candidate hashes were found.

## Limits
This is a read-only test-fixture review, not a product route verdict. Runtime remains **NOT RUN** until cleanup is made all-or-nothing for the owned rows, the candidate is linted, and a new independent exact-hash review passes.
