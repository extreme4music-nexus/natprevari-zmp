# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# UI review — President subject proposals, mobile

- Date: 2026-11-12
- Route: `http://localhost:8101/wp-admin/admin.php?page=zmp-president-subject-approvals`
- Browser context: existing Edge tab `913915354`; no new tab opened.
- Viewport: 375 × 812 CSS px.
- Candidate PHP SHA-256: `45435CB47D290D736622B71E8C0C5E92416BBF4418E2A0020B23D61EF0B16D88`
- Candidate CSS SHA-256: `3F299415265FD8F54390F9D0D2496DB41B60CF22C12AB16DCCE4837512B6D189`

## Findings

- **PASS — proposal identity is visible:** the proposal card heading is rendered from the proposed subject name (`proposed_name`) in the PHP renderer. The heading appeared in the reviewed page.
- **PASS — no horizontal overflow at 375 px:** measured viewport width 375 px; document and body scroll widths were 360 px.
- **PASS — responsive action layout:** the action wrapper computed to `flex-direction: column`. The labeled canonical-subject selector and all three decision buttons had 312 px width within the 375 px viewport. Each button was 40 px high, stacked vertically, and reachable by scrolling.
- **Usability observation:** the label/select block occupied 260 px vertically and the first button began at y=770; the second and third buttons began below the 812 px viewport. This leaves substantial vertical whitespace and requires scrolling to reach all actions. The page remains scrollable; no decision was submitted.
- **NOT RUN:** keyboard-only traversal, screen-reader announcement, actual select interaction, decision/authenticator flow, persistence, other device/browser combinations, and physical mobile devices.

Review was read-only. No decision button was clicked and no profile/passkey data was touched. This is a scoped mobile UI review, not a phase/release acceptance decision.
