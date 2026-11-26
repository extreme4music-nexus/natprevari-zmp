# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Communications confirmed-results publication — local synthetic PASS

Date: 2026-11-25 (Europe/Skopje).
Scope: Positive direct publication of a legacy, year-scoped result release after the exact phase was closed and had a result row, using the isolated `.66.b` WordPress/MariaDB clone.

Candidate/runtime `includes/class-zmp-communications.php` SHA-256 matched at `2A3BB1074CE446863F76ED0669CBB89582651FF4FED996C9C0661C9D7081F260`. Fixture `tests/communications-confirmed-results-publish-runtime.php`, SHA-256 `A7B00D01D723E1D590FFE7E6D4313DB6687CF42F4AE32D2D94FE76684C1CD95`, passed PHP syntax validation.

The fixture verified the original synthetic competition phase was open with zero regional result rows. It temporarily marked only that local phase closed and inserted one synthetic result assigned to an existing synthetic `.invalid` mentor account. The actual Communications publish handler then passed the results gate, created the published result announcement, exactly one unread notice for that result's mentor, and one linked published WordPress post with scope `2026/2027 · Регионално натпреварувачко ниво`. No email was attempted.

Cleanup deleted the synthetic announcement, notice, post, result and moderator, and restored the exact original phase row including its `NULL` close timestamp and `NULL` decision reason. All tracked counts returned to baseline, direct post-cleanup query confirmed the phase is open and results count is zero, and `wp db check` passed.

The first run's cleanup converted the original `NULL` decision reason into an empty string and therefore correctly failed its exact-restoration assertion. That local test fixture cleanup was fixed, the original phase row was restored to `NULL`, and the corrected run passed. This was a fixture cleanup issue, not a product defect.

Run log: `/home/tonic/zmp-local-sandbox/phase66b-test/logs/communications-confirmed-results-publish-20261125.txt`.

This closes the positive legacy result gate for one year/phase scope only. Category-scoped result gate behavior, broader result data, UI and external email remain separate checks.
