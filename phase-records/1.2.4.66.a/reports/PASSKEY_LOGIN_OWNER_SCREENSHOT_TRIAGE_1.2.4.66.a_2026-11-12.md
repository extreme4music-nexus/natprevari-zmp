# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Owner-supplied passkey login screenshots — `.66.a` — 2026-11-12

## Evidence and result

The Owner supplied two Edge screenshots from `http://localhost:8101/wp-login.php?loggedout=true` at desktop viewport 1440×900. Screenshot names: `Screenshot 2026-11-12 133003.png` and `Screenshot 2026-11-12 133037.png`.

- **Visual login UI: scoped PASS.** The WordPress login form and “Sign in with a passkey” control render. The native passkey chooser opens and shows a saved credential source. The displayed login card and control fit within the captured viewport. This is Owner-supplied screenshot evidence; no page was opened or interacted with by the agent.
- **Latest authenticator attempt: FAIL at provider / site result NOT OBSERVED.** The second screenshot shows the native dialog “Can't reach Microsoft Password Manager”; the browser remains on the logged-out page. The screenshots do not show an assertion returned to the page or a site-side response.
- The native dialog points to a Windows/Edge Microsoft Password Manager availability problem before a site response is visible. This is a likely boundary location, not a confirmed root cause; no OS/authenticator logs were inspected.
- A previously successful Owner-verified passkey sign-in remains valid evidence for that earlier attempt. This later failed retry does not erase it; keep both events distinct.

## Privacy and limitations

The passkey/account label visible in the chooser is intentionally not transcribed. No credential ID, assertion, password, cookie, token, request body or OS secret was captured or added to project files. The screenshots were not copied into the repository.

This evidence verifies only the desktop login-page/native-chooser rendering and the provider error displayed during this attempt. It does not verify keyboard/screen-reader behavior, mobile layout, a current President profile, or a server-side login result.

## Next safe step

Continue plugin-side work without changing RP/origin or assertion checks based only on this provider error. If Windows/Edge again blocks a required authenticator flow, the Owner may provide a screenshot; use it as visual evidence and keep it distinct from server-side proof. No immediate reattempt is required.

## PIN prompt capture limitation — Owner clarification
The Owner reports the system PIN prompt cannot be captured in a screenshot. No screenshot of that prompt is required, and no PIN was requested or shared. We cannot infer from this whether a PIN prompt appeared, whether it was accepted, or how far the Password Manager flow progressed before the visible provider error. Treat this as a capture limitation only; do not ask the Owner to expose a PIN or alter device authentication settings.

## Owner correction — PIN prompt and successful login — 2026-11-12
Correction to the preceding PIN-prompt limitation: the Owner confirms the system did request the device PIN, the Owner entered it, and passkey sign-in completed successfully. The earlier statement that no PIN was requested is superseded and incorrect. The PIN value was not shared or recorded. The successful later login is documented separately in `PASSKEY_LOGIN_OWNER_RECONFIRMATION_1.2.4.66.a_2026-11-12.md`.
