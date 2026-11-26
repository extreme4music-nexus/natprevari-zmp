# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# UI review — President subject approvals after CSS cache-key fix

- Date: 2026-11-12
- Route: `http://localhost:8101/wp-admin/admin.php?page=zmp-president-subject-approvals`
- Browser context: reloaded existing Edge tab `913915354`; no new tab opened.
- Viewport: 375 × 812 CSS px.
- `zmp-competitions.php` SHA-256: `E97C178329FCAD3662508D7D492C4CFEA729A7F5B927CD73A829206DBF03BA69`.
- `includes/class-zmp-president-subject-approvals.php` SHA-256: `45435CB47D290D736622B71E8C0C5E92416BBF4418E2A0020B23D61EF0B16D88`.
- `assets/css/zmp-admin.css` SHA-256: `A3BD3F9DF6AA91C5973A79AED5254BBCA0AC6AC4E4D982B81F1DD482A490BC83`.
- Loaded stylesheet URL: `http://localhost:8101/wp-content/plugins/zmp-competitions/assets/css/zmp-admin.css?ver=1794495760`.

## Findings

- **PASS — refreshed asset URL:** after reloading the existing page, the stylesheet URL used a timestamp-style version key (`1794495760`) rather than the previous fixed phase string.
- **PASS — mobile label rule applied:** at 375 px, the max-width:600px breakpoint matched. The label computed `flex-basis: auto`, `flex-grow: 0`, and height 73.2 px (rounded bounding box 73 px), down from the earlier 260 px.
- **PASS — no horizontal overflow:** viewport width was 375 px; document and body scroll widths were 360 px.
- **PASS — stacked controls:** the action wrapper was 312 px wide and 235 px high. The label/select and three buttons were vertically stacked; each button measured 312 × 40 px. The first action began at y=583, within the 812 px viewport; subsequent buttons began at y=637 and y=691.

## NOT RUN

Keyboard-only traversal, screen-reader announcement, selector interaction, decision/authenticator flow, persistence, other browser/device combinations, and physical mobile devices. No decision was submitted, and no passkey or profile data was touched. The viewport override was reset after measurement.

## Evidence correction

The initial report draft contained unexpanded template variables and corrupted control characters in the CSS property names. This corrected version binds the exact hashes and measurements reported by the UI Reviewer. It supersedes only that malformed draft; no test result was changed.

This is a scoped responsive UI review, not a phase or release acceptance decision.
