# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President subject-proposal mobile UI check — 2026-11-12

## Scope
Existing Edge tab on the local synthetic WordPress site, President subject-proposal queue. Read-only visual inspection; no decision was submitted.

## Finding and correction
The mobile CSS rules in `assets/css/zmp-admin.css` already specified a column layout for `.zmp-subject-approvals__actions`, but the rendered decision form in `includes/class-zmp-president-subject-approvals.php` did not include that container. The rule could not apply to the form controls. The PHP now wraps the canonical-subject label/select and three decision buttons in that container. At the max-width 600px breakpoint, the select and buttons use full width and border-box sizing.

## Evidence
- Existing Edge tab reloaded after synchronizing the candidate into the already-running synthetic site `http://localhost:8101`.
- Edge responsive emulation showed the page at 400 CSS px wide (height field was 2233 px). The changed page rendered; the label/select and decision buttons were vertically stacked. The visual self-check found no obvious horizontal overflow in the visible rendered surface.
- Accessibility tree after reload showed the proposal queue and all three decision controls. The pending count remained one. No decision, passkey operation, profile save, or other form submission was made.
- This was the Engineering Lead's visual self-check, **not an independent UI reviewer verdict**. The Owner stopped Computer Use with physical Escape while the viewport width was being adjusted to 375 px; the height was not set to 812 px. Exact 375×812 review is **NOT RUN**.

## Exact source hashes
- `includes/class-zmp-president-subject-approvals.php`: `45435CB47D290D736622B71E8C0C5E92416BBF4418E2A0020B23D61EF0B16D88`
- `assets/css/zmp-admin.css`: `3F299415265FD8F54390F9D0D2496DB41B60CF22C12AB16DCCE4837512B6D189`

## Status
- Mobile layout at 400 CSS px: visual self-check **PASS**, scoped to rendering/stacked controls.
- Exact 375×812, keyboard, accessibility semantics, decision submission, independent UI review and additional devices/orientations: **NOT RUN**.
- No ZIP, release, or GitHub synchronization was performed. Phase `.66.a` remains **IN PROGRESS / NOT_READY**.
