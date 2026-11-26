## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Subject Route Denial Fixture — 2026-11-04

## Review result: FAIL (P2) — scoped to original candidate hash

- Candidate: `tests/phase65-subject-president-route-denial-clone.php`
- Exact SHA-256 reviewed: `455588BC6304F96F2A773A65E66590A53BAC8EDB7528C76263FCF3C58A4318BF`
- PHP lint: **PASS** (`No syntax errors detected`).
- Runtime: **NOT RUN**.
- Product source guards matched current workspace hashes: `class-zmp-mentor-subject-proposals.php` `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`; `class-zmp-president-subject-approvals.php` `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`.

## Scope and findings

The fixture creates a synthetic Teacher proposal as a positive control, then sends a nonce-authenticated direct President-queue GET and a nonce-authenticated decision POST through the actual loopback site. Its expected 403 responses, generic denial, unchanged pending proposal/submission event, and empty catalog/alias assertions align with the current handlers. The HTTP pre-request guard limits these fixture requests to HTTP `127.0.0.1:8100` and the expected admin routes. The scoped subject classes expose no subject-specific AJAX or REST handler, so the candidate correctly does not fabricate one.

Two P2 issues prevent runtime:

1. **Missing exact site-root guard.** The candidate checks database name, table prefix, loopback URL and plugin version, but does not bind `realpath(ABSPATH)` to `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`. The test could therefore run on a different installation that shares those database/site identity values.
2. **Partial-submission cleanup gap.** Cleanup only deletes the proposal and event when `$proposal_id > 0`. If `submit()` leaves an owned proposal but returns an error or unusable ID, the final table snapshots detect residue but do not remove the test-owned row. Cleanup should locate only the generated synthetic proposal by its exact unique name and Teacher ID, remove only its matching `submitted` event, and then verify the baseline.

The denial checks cover a Teacher trying to access the President queue and decision endpoint. They do not cover a wrong-role submission to the Teacher proposal handler, and they do not constitute rendered UI acceptance.

## Re-review policy

This FAIL applies only to the exact SHA above. After the two cleanup/scope findings are corrected, review the new exact hash independently before any runtime. Preserve this finding as historical evidence; do not treat a later revised candidate's verdict as a rewrite of this result.

## Re-review result: PASS — revised candidate hash only

- Revised candidate: `tests/phase65-subject-president-route-denial-clone.php`
- Exact SHA-256 re-reviewed: `742C17BA22BD9ED103292A832CCFE1D3A6D102D3A42ADE32B6E9BCDBFE8C6425`
- PHP lint: **PASS**. Runtime: **NOT RUN**.
- The exact `realpath(ABSPATH)` guard now binds the fixture to `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`, in addition to the database, prefix, loopback URL/port, version and exact service-source hashes.
- Cleanup now finds a proposal by the fixture's exact generated name and Teacher ID if `submit()` did not return a usable ID; it validates pending status, actor, decision fields, and the sole matching `submitted` event before deleting. The orphan-event path is similarly constrained by exact generated event data, actor/type and absence of its referenced proposal. User, usermeta, subject tables and diagnostics are checked against pre-run snapshots.
- The original direct-queue GET and nonce-valid decision POST assertions still check Teacher 403/generic denial and unchanged pending/catalog state. The no-subject-AJAX/REST conclusion remains source-scoped; this fixture does not claim rendered UI or a wrong-role Teacher-submission test.
- The HTTP pre-request filter constrains initiated fixture calls to loopback port `8100` and the two admin routes. It is not evidence of independent outbound-egress enforcement in the PHP server process.

This PASS applies only to the revised exact hash. It supersedes the prior candidate for runtime readiness without deleting or changing the historical FAIL for SHA `455588BC6304F96F2A773A65E66590A53BAC8EDB7528C76263FCF3C58A4318BF`.
