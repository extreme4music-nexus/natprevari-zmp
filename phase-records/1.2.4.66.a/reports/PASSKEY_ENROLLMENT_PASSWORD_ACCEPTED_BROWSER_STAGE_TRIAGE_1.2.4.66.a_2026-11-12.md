# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Passkey enrollment password/browser-stage triage — 2026-11-12

## Confirmed from live synthetic runtime
The active local PHP log records two Owner attempts:
- 2026-11-12 07:40:46–07:40:47 local: options_request_accepted, registration_options_succeeded, options_handler_succeeded.
- 2026-11-12 07:41:20 local: options_request_accepted, registration_options_succeeded, options_handler_succeeded.

This proves for these requests that WordPress recognized the logged-in session, accepted the registration options request, and the passkey backend accepted the supplied current password and generated registration options. No finish_request/registration_finish marker followed either options success. Therefore the displayed generic phrase is not evidence of a wrong password; the interruption is after options generation and before the finish POST, most likely the browser WebAuthn credential-creation step. Exact browser exception remains unknown.

## Confirmed diagnostic display defect and fix
Read-only inspection of the existing Edge page's localized inline config found debugDiagnostics serialized as the string "1". The JavaScript previously enabled diagnostic messages only when the value was boolean true. Therefore it fell through to the generic password wording and hid the actual stage.
Changed only assets/js/zmp-passkeys.js so diagnostics enable for boolean true or string "1". Node syntax check PASS. Runtime JS was updated only after checking its old hash; runtime postimage equals candidate hash below. No browser retry after the JS fix yet.

## Hashes
- includes/class-zmp-president-dashboard.php — EDECF02CDF7C79491AD1AE0A32010B60B4E5508C436E339933C4E07F06836C4A
- assets/js/zmp-passkeys.js — 081CFB49A3079D631FE7DA7B5D5DB161EFEA2E6DF299F8BE6728751AC04B7961
- admin/class-zmp-passkey-profile.php — 705C7DDA64DB3307DB34C06D1A5134AEA76464DF78CAB77BD3406E5E6A5DDD6D
- zmp-competitions.php — C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F

President admin-post allowlist exact-hash review remains PASS/no findings, report SHA-256 1F07F9951443A92159607C999E48A50A2303BD074D6ECD2345A1A00756621CEE. Independent exact-hash review of the JS diagnostic fix: PASS/no findings, reviewer verified no auth/request flow changes and safe fixed diagnostics only. Browser retry after this fix remains NOT RUN. Enrollment as a whole remains FAIL/UNRESOLVED; phase .66.a remains IN PROGRESS / NOT_READY. No ZIP, GitHub sync or Auditor acceptance.

## Browser handoff after diagnostic fix
The attached Edge extension exposed the existing Owner tab in this session; a new Codex session was unnecessary. The same profile tab was reloaded after the JS candidate was installed. The page still serializes debugDiagnostics as string "1", which the candidate now accepts. No password was entered or enrollment clicked by the agent. Post-fix browser attempt remains NOT RUN; Owner handoff is pending.

## Latest owner attempt — explicit backend category — 2026-11-12
At 07:52:30 local, options_request_accepted + registration_options_succeeded + options_handler_succeeded: password check PASS. At 07:53:01 local, options_request_accepted + registration_options_rejected category=password_rejected + options_handler_rejected, HTTP 400: that later request's submitted value did not match the current WordPress password hash. These results conflict only if the submitted values/account hash were identical; no separate passkey password exists. Source uses the current logged-in user ID, fetches that user's user_pass hash, and calls WordPress wp_check_password. No code path in the options handler changes the user's password. The prior success proves the server can accept a value for this account; the later rejection indicates a different request value or a changed underlying account hash, with no evidence of a hash change in this run. The password input is cleared immediately after every enrollment request, confirmed in the current Edge page without reading its value. Do not infer which exact factor changed; the password was never recorded.

## Later retry — password check passed again — 2026-11-12
A later local request at 07:57:19 emitted options_request_accepted, registration_options_succeeded, and options_handler_succeeded, HTTP 200. This independently confirms that the current-password check can pass for this account. No registration finish marker/POST followed, so no passkey credential was saved. The earlier password_rejected event applies only to that one request. No password, nonce, body, challenge, assertion, or credential value was read or recorded. Browser tab control timed out before command dispatch during a read-only state check; no browser action was sent.

## WebAuthn SecurityError from numeric loopback RP ID
Owner's current browser diagnostic is SecurityError during credential creation. The test site origin was http://127.0.0.1:8101 and the plugin set RP ID 127.0.0.1 from that configured host. The W3C RP ID algorithm rejects a direct IP literal; HTTP localhost is the approved loopback exception. Corrected only the synthetic site's WP_HOME/WP_SITEURL to http://localhost:8101. The same existing PHP server answered localhost with HTTP 200; wp-config PHP syntax PASS. The existing Edge tab was navigated in place to localhost and now requires a fresh login because cookies are host-scoped. No password was entered and no credential/DB state changed. Browser retry remains pending Owner login and confirmation.
