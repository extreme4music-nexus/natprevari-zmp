# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# UI Review — President subject-proposal approval queue — 1.2.4.66.a — 2026-11-12

## Scope and evidence
- Read-only review of the active synthetic WordPress page `http://localhost:8101/wp-admin/admin.php?page=zmp-president-subject-approvals` in Edge at **1432×711**. The observed page showed one pending synthetic proposal.
- Evidence: same-tab screenshot and accessibility/DOM snapshot from the active Edge session. No proposal decision button was activated for this review; no form submit or database write was performed.
- Renderer `includes/class-zmp-president-subject-approvals.php` SHA-256 `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`; CSS `assets/css/zmp-public.css` SHA-256 `BD12B88D3C84CF6E184F9AF02286B48D39273D33857FF925141DA28384FDC392`.

## Findings
- The proposal subject **is visible** as the proposal card's level-2 heading. Source line 150 renders the escaped `proposed_name`; it is not a submitter name. The screenshot and DOM snapshot show that heading, submission time, normalized-match section, match result, passkey-required notice, canonical-subject selector and three separate actions (approve as new, approve as synonym, reject).
- The synonym selector has an explicit label and a placeholder requiring a choice. The actions have visible, distinct text. The queue count and page title are visible. Desktop rendering of the reviewed queue is **PASS** for these displayed elements.
- The earlier suspicion that the subject text was omitted is incorrect; no bug is raised for that claim.

## Limits
- **NOT RUN:** mobile/responsive layout for this queue, keyboard-only operation, screen-reader behavior, action submit/save, passkey ceremony, success/failure route result, and physical-device behavior.
- The later/current same-tab interaction that displayed a generic passkey failure is outside this visual-content verdict and is tracked separately by the Lead; no cause is inferred here.
- No source, browser, site or database state was changed by this read-only review.

This scoped UI review does not close `.66.a`; overall status remains IN PROGRESS / NOT_READY.
