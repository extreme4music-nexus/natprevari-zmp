# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President first teacher-profile setup — runtime result

Date: 2026-11-12 (Europe/Skopje)
Phase: `.66.a` — remains **IN PROGRESS / NOT_READY**.

## Finding and correction

The first exact-candidate runtime attempt stopped at `save_user_profile_fields()` while verifying user metadata. A safe diagnostic capture identified `zmp_phone` with a stored empty string. WordPress `wpdb::get_var()` returns `NULL` for an empty-string result as well as for no row, so the previous check treated the allowed blank phone as missing and aborted the transaction. A diagnostic-only clone copy confirmed the condition as `missing`; it emitted only the whitelisted metadata key and condition, never a value. No production or Owner data was used.

The active `.66.a` implementation now reads the latest metadata row with `get_row()` and separately checks query error, row/property existence, and exact string equality. That accepts a real empty string while still rejecting a missing row, mismatch, or database error. The test fixture's embedded hashes were corrected to the exact active source hashes; the initial stale hash guard invocation was BLOCKED with `writes=0` and is not a feature result.

## Evidence

- `includes/class-zmp-support.php` SHA-256: `A44FFAF243E185ABB32B0E1B4706EF9FEBEE6EC1764710D09ECAF8DE60487D81`.
- `includes/class-zmp-mentor-school-profile.php` SHA-256: `BED1C58E6D895FA04907D6C53CAA9494946FBF0BF53C13E115D0AF8A834F6C31`.
- `tests/phase66a-president-teacher-profile-self-setup.php` SHA-256: `8E311A23572CF2E867FF41FE77D0D9FFCA86CF7A7F602B23F6314F70932AE8E4`.
- PHP syntax checks: **PASS** for implementation and fixture.
- Independent focused Code Review: **PASS** at the exact implementation and fixture hashes above; report `CODE_REVIEW_TEACHER_PROFILE_EMPTY_PHONE_READBACK_FIX_1.2.4.66.a_2026-11-12.md`.
- Isolated WordPress runtime fixture exit 0: `ADMIN_OTHER_USER_SETUP=DENIED`; `PRESIDENT_FORM=PASS`; `SELF_PROFILE_SETUP=PASS`; `SELF_MAPPING_AND_SCHOOL=PASS`; `SELF_ENGAGEMENT_READ=PASS`; `OTHER_PRESIDENT_DENIED=PASS`; `EMAIL_COLLISION_NO_AUTOLINK=PASS`; `FIRST_SETUP_ROLLBACK=PASS`; `CLEANUP=PASS`; `DB_INTEGRITY=PASS`.
- The expected cross-user `forbidden` diagnostic was emitted by the negative authorization case; its generated flow identifier is intentionally omitted.

## Environment and cleanup

The run used `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, isolated DB `zmp66a_isolated`, prefix `z66a_`, MariaDB PID 3828. Only the `.66.a` plugin was installed/active. A private mode-600 pre-run SQL backup was created in the clone sandbox and verified by successful `wp db check`; its contents and digest are not included in project evidence. After the fixture, the baseline was restored: 1 user, 1 passkey credential, 0 challenges, 0 mentors, 0 schools, 0 school profiles, 0 assignments, 0 grades; DB integrity **PASS**. Existing UI server PID 25642 / port 8101 remained running and untouched; port 8102 remained free. Temporary diagnostic-only clone instrumentation was removed by resynchronizing the exact candidate source before the final runtime run.

## Preserved failed attempt

Before the fix, the exact-candidate behavior attempt failed at empty-phone readback. WP-CLI exited through `wp_die` before the fixture's `finally`; a metadata-only check found one extra synthetic user and zero mentor/school/profile/assignment rows, with DB integrity PASS. The isolated DB was restored from the verified private pre-attempt backup and baseline rechecked. This historical feature FAIL is superseded only for current behavior by the successful exact-hash runtime above; the failure and restoration are preserved here.

## Scope

This proves the isolated first-setup transaction and the listed denial/collision/rollback/cleanup behaviors at the exact candidate hashes. It does not establish browser form submission/readback, UI accessibility, step-up browser behavior, whole-phase acceptance, release readiness, ZIP, or GitHub synchronization. No Owner tab, PIN/passkey ceremony, production site, or real data was touched.