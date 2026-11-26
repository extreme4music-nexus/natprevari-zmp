# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent code review — President self-teacher-profile setup — `.66.a`

Date: 2026-11-12. Review mode: read-only static review; the test fixture was not run.

## Exact reviewed hashes

- `includes/class-zmp-support.php`: `079A174A35E71F69A45C51F1259101AE6AC508181F3DA5C6E66788AA0032B405`
- `includes/class-zmp-mentor-subjects.php`: `D9D6BA3B5DD56C20E4CBB1E830A44A3210824B18DA3F7C3A9E920CDEEF87C245`
- `tests/phase66a-president-teacher-profile-self-setup.php`: `711DFCDC20D02547D6F6EF28F2191D4ED842FA7DEE3F7ED1ADB8B3B3CF876FCE`

## Findings

### [P2] Initial profile setup can leave partial records when later validation/save fails

`class-zmp-support.php:195–218` inserts the new mentor row and updates four user-meta values, then calls `ensure_primary()` before validating new-school and assignment input (`:220–275`). With a non-empty approved-subject catalog, a missing assignment marker or invalid/missing assignment can trigger `wp_die()` after those writes (`:225–228`, `:268–275`). A primary-school creation or other post-insert failure also returns an error without removing the mentor/meta writes. The initial setup path does not start a transaction around this sequence (`$own_transaction` is set only for the optional additional-school path at `:241–257`). The user sees failure, but the next request may find a newly linked mentor record and take a different update path. Make first-time mentor/meta/primary-school/assignment persistence atomic, or validate all inputs before the first write and compensate every failed write. Add a focused failure-path assertion for no partial state after invalid assignment or injected downstream failure.

### [P2, conditional] Email collision check and mentor insert are not atomic

The setup path performs a case-folded collision SELECT (`class-zmp-support.php:198–208`) and then a separate insert (`:209–211`). There is no lock, transaction, or uniqueness enforcement visible in the reviewed path. Concurrent setup submissions can both observe no matching email before either insert, potentially creating duplicate mentor rows or duplicate `wp_user_id` mappings unless an external database unique constraint prevents it. The relevant index definition is outside this review scope and was not assumed. Verify the schema constraint; if uniqueness is not guaranteed for normalized email and `wp_user_id`, enforce it atomically and handle duplicate-key rejection without linking to the colliding row.

## Reviewed safeguards

- The setup form and save path require the current President to edit their own user ID, exclusive `zmp_president` role, and the President dashboard capability (`class-zmp-support.php:87–91`, `:154–169`). Existing mapped mentors retain the same self-update path.
- A pre-existing mentor email is not auto-linked: collision returns HTTP 409 with administrator-review guidance (`:196–208`). The fixture checks collision refusal/no auto-link, admin editing another President is denied, and the intended self-setup path; it also checks cleanup and DB integrity by design. The fixture was not run in this review.
- Region and length validation occurs before mentor insertion. Approved-subject catalog read errors fail closed before writes; subject and grade persistence delegates to the focused catalog service.

## Verdict and limits

**SPEC/QUALITY REVIEW: FAIL pending the P2 findings above.** This is static analysis of the three exact files only. No fixture execution, runtime, database, browser, or schema/index review occurred. Do not infer functional PASS from this report; retain prior test results and statuses at their exact hashes. Re-review after a fix must bind new source and fixture hashes.
