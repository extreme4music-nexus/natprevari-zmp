## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger analysis — approved-subject catalog failure

Date: 2026-11-04

## Evidence inspected

- Test fixture: `tests/phase65-approved-subject-query-error.php`
- Exact SHA-256 verified: `6EFC1B8C13E33178F83C4944F1F91AAA12EEDEFEE235DA41D945D7FC7C86E7AE`
- One reported runtime attempt: 12 assertions, with failures for the President unavailable-state assertion, assignment save's expected error, and the no-partial-write snapshot. No retry was performed for this candidate.
- Candidate source was inspected only; no file, database, or server was changed during this analysis.

## Source trace

- `includes/class-zmp-mentor-subjects.php:141–160`: `approved_subjects()` resets the DB error state, reads the approved catalog, and returns a generic `schema_not_ready` error if the query failed. A valid empty result remains an empty array.
- `includes/class-zmp-president-subject-approvals.php:28–36,55–76`: rendering requires a logged-in President with the dashboard capability, exactly the President role, and an active mandate. If proposal schema/pending lookup or the approved catalog returns an error, the route renders the generic alert and returns before its queue controls.
- `includes/class-zmp-mentor-subjects.php:199–216`: assignment saving checks the caller and mentor record, then checks `approved_subjects()` and returns that error before the legacy-primary reconciliation or assignment writes.
- `includes/class-zmp-mentor.php:250–264,312–320`: profile setup and direct school-add paths check the approved catalog before their respective writes.
- `includes/class-zmp-support.php:161–167`: admin profile save checks the approved catalog before mentor, metadata, primary-school, or assignment writes.

## Diagnosis

The inspected product flow is consistent with the intended fail-closed behavior. The three fixture failures do not yet establish a product defect: the assertion that assignment saving returns `schema_not_ready` depends on a synthetic teacher being recognized as the currently logged-in teacher and having a mentor row; President rendering depends on `can_review()` succeeding, including the active mandate. The fixture did not report those preconditions or the returned error code/rendered text needed to distinguish setup failures from product failures. The no-write assertion may consequently be downstream of the same unverified setup, or may indicate an actual mutation; its failure remains unresolved.

WP-CLI printed its own database failure detail and query context during the deliberately injected errors. The plugin diagnostic events in the observed output were generic. Treat the CLI emission separately from `ZMP_Debug`; a corrected fixture should suppress database error printing around its injected failure and inspect only sanitized outcomes.

## Next diagnostic step

Revise the test-only fixture, obtain exact-hash independent review, then make one new bounded run on the blank clone. Report only whether President access/mandate preconditions passed, the generic assignment error code, whether the expected alert was present, and the names/counts of changed test-owned tables/meta. Preserve the failed run and do not rerun its candidate. Reassess product code only if those controls confirm a reproducible behavior mismatch.

Verdict: **INCONCLUSIVE — no product defect confirmed; the failed acceptance assertions remain unresolved.**
