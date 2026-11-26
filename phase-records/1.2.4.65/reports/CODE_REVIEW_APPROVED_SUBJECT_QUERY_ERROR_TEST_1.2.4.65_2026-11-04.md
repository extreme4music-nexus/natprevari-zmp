## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent fixture review — approved-subject query error

## Initial candidate (preserved)

- File: `tests/phase65-approved-subject-query-error.php`
- SHA-256: `6E028E6104D39AC1723478E1ACB2851BA7562ABC80CC4903C4335F459006C120`
- Verdict: **FAIL (P2)**
- Reviewer findings: user rows were tracked only after all three inserts, so partial setup could leak users; cleanup outcomes and baseline restoration were not checked; profile snapshots omitted region and phone; diagnostics were not inspected for database error/query leakage.
- Scope reviewed: empty catalog versus query error, President/mentor/profile rendering, profile setup/add-school/profile/assignment fail-closed handling, limited DB/meta state, and HTTP/mail blocking. Static markup checks did not replace rendered UI review.
- Execution: none. The reviewer did not run PHP lint, the fixture, WP-CLI, DB mutations, or a server.

## Corrected candidate

- A follow-up revision tracks each successful user immediately, checks cleanup results, verifies table and diagnostic-option restoration, includes region/phone in profile snapshots, and asserts diagnostic output excludes injected database errors/query text.
- New SHA-256: `F31DA245DF2395DB4879B234E84718EC8F975519BDEA594713F4A4714F1E7591`
- Independent re-review: **PENDING**
- PHP lint and runtime: **NOT RUN**

The initial FAIL remains part of the review history and is not overwritten by the corrected candidate.

## Follow-up review of first correction

- SHA-256: `F31DA245DF2395DB4879B234E84718EC8F975519BDEA594713F4A4714F1E7591`
- Verdict: **FAIL (P2)**
- Remaining finding: if the deliberately exercised Teacher A setup/profile-save handler regresses and creates mentor-linked rows, the fixture detects changed counts but does not clean those user-owned rows or restore the created user's school/profile metadata. A failing run could leave synthetic test state behind.
- Other prior findings were addressed in that candidate: immediately track each created user, include region/phone in the no-write snapshot, check diagnostic-option restoration and table counts, and assert database/query text is absent from diagnostics.
- Execution: none.

## Current cleanup-complete candidate

- Revised cleanup now finds mentor rows linked to all created test users, removes their grade/assignment/school/mentor rows, clears the scoped profile metadata, and then deletes each user before baseline checks.
- SHA-256: `6EFC1B8C13E33178F83C4944F1F91AAA12EEDEFEE235DA41D945D7FC7C86E7AE`
- Independent re-review: **PASS** for the requested cleanup and safety criteria. Exact hash confirmed. Reviewer verified immediate user tracking, cleanup of all created-user-linked mentor/grade/assignment/school rows and profile metadata, user deletion, tracked table baseline checks and diagnostic-option restoration. The privacy assertion covers the configured debug option only; broader privacy assurance remains within product code review.
- PHP lint: **PASS** in the isolated clone. This candidate was run once; its result and fixture findings are recorded in the versioned test report. It is not the current test candidate.

## Diagnostic correction candidate

- Test Engineer corrected the President expected copy, wrapped assignment saving in the query-error injector, suppresses/restores `$wpdb` errors around the deliberate query failure, whitelists failure output, and removed the PHP 8.5 reflection deprecation call.
- SHA-256: `FC56D05D4446C1642BD9C94FAB0432B304F13C127930363C519AA3655152AFDE`
- Independent Code Review: **PASS with a cleanup caveat**. If assignment-ID lookup failed, cleanup skipped the relation rows; the current candidate replaces that lookup with a scoped join-delete for grades.
- Current final test candidate SHA-256 `386FB709A38C55C38E5020A64FBB9D9E710E340A3280CA5F64B06CB36344607A`; independent exact-hash re-review **PASS**, with no new issue found. Reviewer confirmed grade cleanup no longer depends on a separate assignment lookup. Runtime **NOT RUN**.
