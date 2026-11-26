# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# President/Teacher profile layout and keyboard check — .66.a — 2026-11-25

## Result

**PARTIAL PASS.** The overlapping school/subject assignment layout was corrected and visually checked on the existing local `:8111` clone. Keyboard operation was checked for grade controls, employment-term selector, and President subject selector. Full screen-reader/assistive-technology announcement verification remains open; this report does not claim complete accessibility PASS.

## Defect and correction

The assignment form rendered nested native fieldsets and legends without layout rules for their width, wrapping, spacing, and line height. This caused school and subject labels to collide and made the form visually chaotic. `.66.a` `class-zmp-support.php` now styles the top-level assignment fieldset and nested subject fieldsets, wraps legends, lays out grade options consistently, and adjusts layout at mobile widths. Renderer classes replace prior inline grade-label styling.

The exact same source is installed in the running local clone. SHA-256 for phase and runtime `includes/class-zmp-support.php`: `fe9e02aa0185d40534f7090264ccf6271ed44c5c4111954df4e30b2e761e5e86`. PHP lint passed for both copies. Browser reload showed separate readable school/subject frames with no overlapping labels.

## Interaction evidence

- Keyboard Space toggled a grade checkbox on and back off; the change was unsaved and discarded on reload.
- Reverse Tab moved from grade 2 to grade 1.
- ArrowDown/ArrowUp changed and restored the employment-term selector without saving.
- ArrowDown/ArrowUp selected and restored the President subject selector placeholder without submitting.
- The President step-up status element has `role="status"`, `aria-live="polite"`, and `aria-atomic="true"`. The owner confirms Narrator was working during the screen-reader check and was exited accidentally afterward. Record the screen-reader interaction as owner-confirmed PASS; no repeated run was needed.
- **Owner-confirmed passkey outcome:** the owner clicked Cancel in the authenticator prompt, then intentionally clicked “Одобри како нов предмет,” entered the PIN, and accepted. On repeat, the existing local browser flow again completed successfully: passkey assertion validation and step-up verification succeeded; synthetic proposal `id=6` became approved by synthetic President `user_id=5`, `approved_new` event `id=11` and consumed step-up challenge `id=36` were read back. Waiting for the owner to enter the PIN is the expected authentication step, not a defect. The earlier test log at 23:40:53 was removed from the local PHP server log as requested; the new repeat-test log remains as evidence. No production site was used.

## Remaining scope

**PASS — UI interaction scope completed for the recorded checks.** Layout and visible focus were checked; grade checkbox, selectors, reverse Tab, and keyboard activation worked; the owner confirms Narrator worked during the screen-reader check. The owner-confirmed Cancel followed by a deliberate PIN-authenticated retry and the repeat-test passkey approval also passed. No production site was used and no passkey PIN/password was entered by the assistant. The broader phase regression remains a separate open item.
