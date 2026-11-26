# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# R8 fixture revision — idempotent test guard marker validation

**Status: fixture-only revision complete; runtime NOT RUN pending independent exact-hash Code Review.**

**Additive runtime disposition (2026-11-05):** The statement above records the pre-runtime checkpoint and is superseded for execution status by the Test Engineer report below. The exact-reviewed candidate ran once and returned **FAIL for route acceptance (1/7)**. The POST received HTTP 302, but redirect status and proposal/event persistence assertions failed; an outbound HTTP attempt was intercepted; exact diagnostics restoration failed safely. The route handler emits 302 for both success and WP_Error, and the fixture report did not retain the sanitized `zmp_subject_status` query value, so the product failure cause remains unclassified. No product defect or PASS is established. Do not rerun R8. See `TEST_SUBJECT_TEACHER_TO_PRESIDENT_ROUTE_R8_1.2.4.65_2026-11-05.md`.

The prior R7 runtime reached one local `admin-post.php` request but returned HTTP 500 in the fixture MU guard because `write_marker()` rejected an already-existing marker. The exact substep that created the existing file was not recorded. A second request colliding with the shared single-use marker is plausible, but remains unconfirmed. No R7 route behavior PASS is inferred.

Only `tests/phase65-subject-register-admin-post.php` changed. `write_marker()` now permits an existing marker only after opening and locking a regular non-symlink file, verifying the path and open handle retain the same device/inode, reading it under lock, and validating the exact JSON fields against the current run token, requested kind, and that same file identity. If exclusive creation loses a race, it validates the newly appeared file by the same rules. Unknown, stale, changed, malformed, symlinked, or cross-kind markers still fail closed. Privacy-safe stage codes identify marker create/read/identity failures without recording marker contents, paths, tokens, or user data. The R7 diagnostics-option baseline preservation and restoration logic is unchanged.

- Candidate SHA-256: `F8EC211A4A95CDA6390A7E01D72D024E0D039074F0258B0D01818D91CCDF15C4`
- PHP lint: **PASS** — `php -l` reported no syntax errors.
- Revised candidate runtime: **NOT RUN**, pending independent exact-hash review.
- Product files and gate ledger: unchanged. Preserve R3 FAIL and all President photo/consent PASS evidence.
