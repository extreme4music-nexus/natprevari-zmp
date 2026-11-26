# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Read-only Code Review — approved-subject query-error handling (`1.2.4.65`)

**Verdict: PASS for the reviewed approved-subject query-error acceptance criteria.**

## Exact reviewed files

- `includes/class-zmp-mentor-subjects.php` — SHA-256 `E76BC478801270F94B4DEA914B938FEAF923D8FC53EAF16C47DFC83A420FE1EF`
- `includes/class-zmp-support.php` — SHA-256 `34BE99AC328999513A80F9B95D0C2A3EE78CB631299BE33B975AB5292DA95198`
- `includes/class-zmp-mentor.php` — SHA-256 `897298C52A8317378392245A68A3DCFD09B60E06A35EDDDB32D0E1433FC03FC2`
- `includes/class-zmp-president-subject-approvals.php` — SHA-256 `720E0692D8534DD261C68457101E574701198B49C233CF98072B2B8CFD8A16C4`

## Findings

- `approved_subjects()` clears stale `$wpdb->last_error`, returns an array for a successful query (including a valid empty result), and returns a generic `WP_Error('schema_not_ready', ...)` if the query result is not an array or the current query reports an error.
- Diagnostics use the allow-listed `outcome=fail_closed` and `error_code=schema_not_ready` context under the existing subject-proposal category. No SQL, DB error text, request payload, nonce, or user-provided data is logged by this path.
- All product call sites found by repository search handle the error: President approvals render the existing unavailable notice and return; teacher profile and mentor-school views render explicit unavailable state and omit school/assignment controls; setup, direct school-add, profile-save, and assignment-save paths stop before their related mentor/school/assignment writes.
- The profile and assignment save paths reuse the successful catalog rows to validate subject IDs, avoiding a second query that could collapse a later catalog error into `subject_not_approved` after school writes.
- Existing role/nonce gates are retained in the affected handlers. The valid empty-catalog behavior remains distinct and allows the prior empty-catalog UI flow.

## Scope and limitations

This is a static, read-only source review bound to the exact hashes above. It did not run tests, access a database, or exercise rendered UI. Runtime and independent UI evidence are still required. Other query paths such as `teacher_assignments()` are outside this approved-subject query-error review scope.
