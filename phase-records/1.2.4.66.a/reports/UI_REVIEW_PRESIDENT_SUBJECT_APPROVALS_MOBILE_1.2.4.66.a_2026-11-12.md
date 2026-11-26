# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# UI review — President subject approvals, mobile

- Date: 2026-11-12
- Route: `http://localhost:8101/wp-admin/admin.php?page=zmp-president-subject-approvals`
- Browser context: existing Edge tab `913915354`; no new tab opened.
- Viewport: 375 × 812 CSS px.
- Candidate PHP SHA-256: `45435CB47D290D736622B71E8C0C5E92416BBF4418E2A0020B23D61EF0B16D88`
- Candidate CSS SHA-256: `3F299415265FD8F54390F9D0D2496DB41B60CF22C12AB16DCCE4837512B6D189`

## Findings

- **PASS — proposed subject is visible:** the proposal card heading is rendered from the proposed subject name (`proposed_name`) in the PHP renderer, and a heading appeared in the reviewed page.
- **PASS — no horizontal overflow at 375 px:** viewport width was 375 px; measured document and body scroll widths were 360 px.
- **PASS — responsive controls stack:** the action wrapper computed to `flex-direction: column`. The labeled canonical-subject selector and all three decision buttons were 312 px wide within the viewport. Each button was 40 px high and stacked vertically; the controls were reachable by scrolling.
- **Usability observation:** the label/select block occupied 260 px vertically. The first action began at y=770; the second and third actions began below the 812 px viewport, so users must scroll to reach all actions. No horizontal clipping was observed.
- **NOT RUN:** keyboard-only traversal, screen-reader announcements, select interaction, decision/authenticator flow, persistence, other browser/device combinations, and physical mobile devices.

Review was read-only. No decision was submitted, and no passkey or profile data was touched. This is a scoped mobile layout review, not a phase/release acceptance decision.
