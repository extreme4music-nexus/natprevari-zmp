# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# School-email request UI render — current local runtime — 2026-11-25

## Result — PASS 7/7
The actual shortcode renderer was invoked on the existing isolated runtime (database zmp66b_isolated, prefix z66a_) with synthetic requester, owner, shared active school, pending request and an active President.
- Teacher view rendered the request form with owner selection, own school ID, nonce and submit button.
- Teacher view showed only the pending status and request reason; the reserved official address did not appear.
- Active President view rendered the pending approval queue with approve/reject actions and request-bound nonce; the official address did not appear before approval.
- The underlying request and one submitted event remained pending during rendering.
- Exact cleanup restored request/event counts and user 116's exact role stack; synthetic profiles, mentors, schools and users were removed. wp db check passed.
- This validates generated server-side HTML, not a browser screenshot or a CSS/mobile visual acceptance.
- Fixture tests/school-email-access-ui-render-current-runtime.php — SHA-256 f30758643709c05144f580c8e0f90ecca007351ee51a2c87f7c1bd0ade86b924.

## Remaining scope
No new source change. Browser-level responsive checks, full approval/disclosure UI interaction and broader route/phase acceptance remain open.
