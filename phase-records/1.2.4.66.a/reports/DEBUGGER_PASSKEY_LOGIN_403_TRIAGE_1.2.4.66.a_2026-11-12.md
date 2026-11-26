# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger triage — passkey login failure — `.66.a`

**Disposition: UNRESOLVED / NOT REPRODUCED.** The reported UI failure is real; the available HTTP summary does not identify its cause. No new log, database, browser, or service inspection was performed.

## Evidence and code binding

- Owner reports the UI message: “Најавата со passkey не може да се заврши. Обидете се повторно или користете ја лозинката.” Password login succeeds.
- Sanitized aggregate: five `admin-ajax.php` POSTs; one 200 at 05:59:05, then 403s at 06:00:15 and 06:00:58; no `[ZMP_PASSKEY_DIAG]` markers. No action, body, query, raw log line, or user identity was inspected.
- Safe aggregate: one passkey credential across one user. This does not establish that the credential is valid, belongs to the attempted account, matches the browser's RP/origin, or can complete assertion verification.
- Current candidate code hashes: `includes/class-zmp-passkeys.php` `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; `assets/js/zmp-passkey-login.js` `6E161DF437384E40A9ADE2BA93265BA4C56A78EFDA5854CAC5DE2C698A812F6F`.
- `class-zmp-passkeys.php:14-18` registers login options and finish as `wp_ajax_nopriv_*`. `:327-357` maps its own backend rejection paths to JSON HTTP 400. Thus a correlated 403 would not match the normal `send_login_error()` response; it would suggest a different action/handler or rejection before/outside these methods. The current aggregate lacks action correlation, so it does not establish that either 403 belongs to passkey login.
- `zmp-passkey-login.js:99-131,153-180` posts the options action, invokes the browser authenticator, posts finish, and displays one generic `failed` state for non-2xx, malformed JSON/shape/result, or other rejected flow. The reported string therefore does not identify which stage failed.
- `class-zmp-passkeys.php:1068-1090` diagnostic markers cover registration only, not login. Their absence provides no evidence about whether login handlers ran or why they rejected a request.

## Ranked interpretation

1. **Most directly supported:** failure is in the passkey flow, but the exact stage is unknown. Password login success shows the alternate login works; it does not validate passkey options, WebAuthn, or assertion submission.
2. **Plausible, unconfirmed:** the 403s are unrelated admin-AJAX calls, because action names were intentionally not inspected. If one is later shown to be a passkey action, its 403 is inconsistent with the plugin's normal HTTP-400 error wrapper and points toward rejection outside the normal handler path or another layer.
3. **Plausible, unconfirmed:** the options action succeeds, then browser `credentials.get()` is cancelled/rejected, or the finish request fails due to assertion, credential, origin/RP, sessionless lookup, challenge, or store validation. The single generic UI state and uncorrelated statuses cannot distinguish these.
4. **Not established by current facts:** a bad password, missing credential row, WordPress-wide login failure, or a specific plugin defect.

## Smallest safe next discriminator

On one later owner-initiated attempt, capture only DevTools Network metadata for the two exact passkey actions: action name, sequence (options/finish), HTTP status, whether the response is JSON, and the plugin's fixed generic error category if present. Do not capture request/response bodies, cookies, nonces, credential/assertion fields, raw logs, or account identifiers. If neither request appears, investigate frontend initialization/configuration; if options returns 403, inspect routing/security layers; if options is 400, use a privacy-safe fixed options error category; if options is 200 but no finish request follows, the blocker is browser/authenticator flow; if finish is 400, inspect the backend's fixed assertion-failure category. Current login diagnostics do not provide those categories, so backend/frontend engineers should agree on a minimal allowlisted, non-secret login-stage diagnostic before any code change.

No test or retry is recommended from these observations alone. Preserve password login and do not attribute the uncorrelated 403s to passkey login.
