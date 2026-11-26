# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent final re-review — President self-teacher-profile setup — `.66.a`

Date: 2026-11-12. Read-only static review; no test or runtime invocation.

## Exact reviewed hashes

- `includes/class-zmp-support.php`: `CFFB2531DEACF4986FF5C6F6E6EFDAED8F08B84C38F00574788B7D247A33F515`
- `includes/class-zmp-mentor-subjects.php`: `D9D6BA3B5DD56C20E4CBB1E830A44A3210824B18DA3F7C3A9E920CDEEF87C245`
- `tests/phase66a-president-teacher-profile-self-setup.php`: `5C2C588EE752F2038D9835522E90B070706D4F1518C3F8A5A20733402EF10736`

## Verdicts

- **Source static review: PASS, scoped to the listed implementation files and setup path.** The post-lock mentor mapping query fails closed on DB errors; the first-setup write sequence is transaction-bound after the InnoDB checks; profile metadata writes check database errors and read back the stored value, distinguishing missing `NULL` from a legitimate empty value. Email and stable President user-ID locks use deterministic sorted acquisition and reverse-order release; acquisition/release failures are surfaced rather than reported as success. Self-setup remains restricted to the target user's exclusive President role and required capability; administrator review is still required for an email collision.
- **Fixture hash consistency: PASS.** Its embedded expected hashes for `class-zmp-support.php` and `class-zmp-mentor-subjects.php` match the exact source hashes above.
- **Runtime/functional verification: NOT RUN.** No fixture, database, server, or browser was accessed in this review.

The fixture covers the synthetic successful setup, email-collision refusal, and a rollback path by design, but it does not exercise concurrent lock interleavings, lock timeout, release failure, or non-InnoDB rejection. Those behaviors remain unverified at runtime. Preserve the earlier SPEC/QUALITY FAIL report as historical evidence; this final exact-hash review supersedes its source findings only and does not erase the earlier record or establish phase/release acceptance.
