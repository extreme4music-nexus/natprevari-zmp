# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# UI Review — Task 5 President subject decision step-up — 1.2.4.66.a

Date: 2026-11-06
Reviewer: Independent UI Reviewer
Initial verdict (before Owner authentication): **BLOCKED**, superseded by the follow-up evidence below. Current scoped verdict: **PASS for the rendered queue layout and keyboard reachability; profile controls rendered. Passkey ceremonies and decision submissions NOT RUN.**
The sections before “Owner-authenticated follow-up” document the earlier blocked attempt and are retained as history; the follow-up below is the current outcome.

## Scope and candidate
- Authorized synthetic site: `http://127.0.0.1:8101` (`/home/tonic/zmp-local-sandbox/phase66a-test/site`), phase `.66.a`.
- Requested route: `http://127.0.0.1:8101/wp-admin/admin.php?page=zmp-president-subject-approvals`.
- Candidate source SHA-256:
  - `includes/class-zmp-president-subject-approvals.php`: `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`
  - `assets/js/zmp-president-subject-stepup.js`: `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`
  - `assets/css/zmp-president-subject-stepup.css`: `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`
  - `includes/class-zmp-passkeys.php`: `F233CB371DCDEF3A05F447C3E5081F4B7F4DE453FE2BFBE2470B447847E68ED7`
  - `zmp-competitions.php`: `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`
- No claim is made that these source files match the files installed on the test site; runtime source parity was not checked.

## Browser and account state
- Read-only browser context: existing Chrome tab `2090200324` on the same synthetic site, initially at `/wp-admin/profile.php`. Its rendered WordPress profile identified the synthetic account `zmp66a_admin`.
- Read-only WP-CLI lookup of that account returned role `administrator`.
- Navigated that existing local tab to the requested route (no form/control submitted). The visible page returned WordPress access denial: “Sorry, you are not allowed to access this page.”
- Thus, the current browser context is **not** an active President context. No `zmp_president` session was available. The existence/state of any separate mandate row was not inspected.
- The exact existing Edge tab could not be bound because it was already part of the parent browser session. No new tab/browser was opened. No session, database, decision, or credential was changed.

## Render and interaction evidence
- Actual route render result: access-denial page above. This verifies the current synthetic account cannot reach the route, not the authorized President view.
- Requested iPhone 13 Mini Mobile View at 375×812 was unavailable on the accessible Chrome context; the existing Edge tab was session-locked. No mobile rendering, horizontal-overflow, clipping, keyboard traversal, or control focus was observed.
- The inaccessible President queue UI therefore remains **BLOCKED / NOT RUN**. This report is not a visual PASS.

## Read-only source/render analysis
- The route renderer builds each pending proposal as an article, places existing normalized matches before its decision form, labels the canonical-subject select, and provides native buttons for approve-as-new, approve-as-alias, and reject.
- The step-up explanation is rendered before the controls. A per-form `role="status"`, `aria-live="polite"`, `aria-atomic="true"` status node is present.
- JavaScript intercepts form submit, validates the selected decision/alias target, requests same-origin step-up options, invokes `navigator.credentials.get()` only after submission, then submits with the assertion. Unsupported/canceled/failed paths show a status message, clear proof fields, and unlock controls; no actual control was activated in this review.
- CSS defines visible `:focus-visible` outlines for select/buttons and, at max-width 782px, 40px minimum control heights, a select max-width, and status wrapping. These rules are source evidence only and do not prove their rendered behavior at 375×812.
- Keyboard accessibility, focus order/visibility, actual mobile spacing, overflow, localization in the authorized view, and authenticator prompts remain **NOT RUN**.

## Review boundary
No approve/alias/reject control was activated; no passkey ceremony was started; no password was entered; no route decision was submitted; no database/session state was changed; no production site was opened or used for this review.

## Next safe step
Provide/release a dedicated synthetic browser context for a synthetic account whose exact role is `zmp_president` and whose mandate is active, in the authorized local site, with the requested Mobile View 375×812. Then inspect the rendered route read-only and keyboard-focus order without starting the authenticator or activating any decision.


## Owner-authenticated follow-up — 2026-11-06

### Actual rendered UI result
- The same existing Edge tab `913911728` became available after the Owner authenticated the synthetic active-President context. I inspected only `http://127.0.0.1:8101/wp-admin/admin.php?page=zmp-president-subject-approvals` and the same site's `/wp-admin/profile.php`; no new tab/browser was opened and the production tab was not accessed.
- The active session displayed the synthetic President UI name. Read-only WP-CLI role lookup returned **`zmp_president`**. The queue rendered one pending synthetic proposal. The matching phase implementation and installed-site hashes are identical (listed below); the route's `can_review()` also requires exact `zmp_president` role and active mandate, so successful rendering confirms this session met that gate. No mandate/database record was edited or queried separately.
- On the rendered iPhone 13 Mini Mobile View at **375×812**, the proposal heading, date, normalized-match section, step-up instruction, canonical-subject selector and all three decision buttons were visible. The card and controls fit within the phone viewport without visible horizontal clipping; vertical scrolling is present. “Одобри како нов предмет” occupies its own line; alias and reject controls fit below it. Text is relatively small in the scaled device preview but remained legible. No browser/authenticator prompt was opened.
- The Owner's Mobile View was not retained after read-only navigation to Profile; profile was therefore reviewed in the same Edge tab at its normal desktop viewport. The profile exposed the “Passkey sign-in” section, current-password field, “Begin passkey enrollment” button, and active-President/mandate explanation. The synthetic President profile showed no existing passkey row/removal button. No password was entered and enrollment was not started.

### Keyboard/focus evidence
- With the queue open in the same Edge tab at its normal page viewport after returning from Profile, keyboard-only `Tab` traversal reached (in order) the skip links, President navigation, toolbar items, register link, canonical-subject selector, and all three decision buttons: approve-new, approve-alias, reject. `Shift+Tab` returned focus from the footer link to reject. A fresh screenshot showed a visible blue focus outline on the reject button.
- This verifies keyboard reachability and visible focus on the normal rendered page. Keyboard focus behavior specifically inside the 375×812 simulator was not independently re-run after the extension stopped wrapping the page on route navigation.
- No control was activated, and no decision, passkey action, database write or session change occurred.

### Exact candidate and installed-site parity
The following SHA-256 values were independently computed for the phase candidate and installed synthetic-site plugin files; each pair matched exactly:
- `includes/class-zmp-president-subject-approvals.php`: `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021`
- `assets/js/zmp-president-subject-stepup.js`: `49D7EC3947F3CF133DF1E54D9FD0B0B3D2E1810885CE94D5986D105C1A50F92B`
- `assets/css/zmp-president-subject-stepup.css`: `7B3C4B9D7AB98F05DE817E5D1356240AD3C92924B7CF6B04AC6D6AB4FE800F44`
- `includes/class-zmp-passkeys.php`: `F233CB371DCDEF3A05F447C3E5081F4B7F4DE453FE2BFBE2470B447847E68ED7`
- `zmp-competitions.php`: `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`

### Scoped verdict
**PASS — rendered 375×812 queue layout has no visible horizontal clipping; its decision controls are keyboard reachable with visible focus in the normal page view; profile passkey controls render for the synthetic President.** The small text in the scaled preview is a usability note. Authenticator flows, status/error interaction states, keyboard focus under the mobile simulator, and decision submission remain **NOT RUN**. This is UI review evidence only, not phase acceptance.

