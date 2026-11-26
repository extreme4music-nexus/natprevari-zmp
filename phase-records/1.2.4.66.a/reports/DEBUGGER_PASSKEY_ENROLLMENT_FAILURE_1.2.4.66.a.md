# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger Report — Passkey enrollment failure — 1.2.4.66.a

Date: 2026-11-06
Scope: Read-only static diagnosis of one reported local synthetic passkey enrollment attempt.
Verdict: **RUNTIME CAUSE UNRESOLVED.** The displayed generic error does not identify which stage failed.

## Evidence supplied by the Lead

- The Owner-authenticated synthetic President profile was open in the existing Edge tab at `http://127.0.0.1:8101/wp-admin/profile.php`, with Mobile Simulator iPhone 13 Mini at 375×812.
- After the Owner approved starting a test passkey ceremony, the synthetic account’s current password was entered into the masked field and “Begin passkey enrollment” was activated.
- The UI showed “Се подготвува регистрацијата со passkey…” and then “Регистрацијата не може да се заврши. Проверете ја лозинката и обидете се повторно.” The password field was cleared. No operating-system/browser authenticator prompt was observed.
- I did not inspect or control the browser, read the credential file, inspect logs, query or mutate the database, or retry the ceremony. The above runtime facts are inherited from the Lead’s observation, not independently verified by me.

## Product code trace

- `assets/js/zmp-passkeys.js`, `handleEnrollment()` (lines 184–246): checks `window.isSecureContext` and the presence of `PublicKeyCredential`/`navigator.credentials.create`; reads the current-password field; POSTs `action=zmp_passkey_registration_options`, its nonce, and the password to `profileConfig.endpoint`; then immediately clears the field. If the options response succeeds, it sets the “continue in browser” status and calls `navigator.credentials.create()`. It serializes the resulting attestation and POSTs to `zmp_passkey_registration_finish`.
- The same function’s catch handler maps any non-cancellation rejection from the options request, `navigator.credentials.create()`/serialization, or finish request to the same `messages.failed` text. Therefore that visible message does not identify whether password/authentication, nonce/authorization, options generation, WebAuthn, serialization, or finish failed.
- `admin/class-zmp-passkey-profile.php`, `handle_options()` (lines 94–105) validates the request and passes the submitted password to `ZMP_Passkeys::registration_options()`. Any invalid request or `WP_Error` returns the generic unavailable response. `handle_finish()` similarly returns a generic response on invalid request or finish failure.
- `includes/class-zmp-passkeys.php`, `registration_options()` (lines 615–657) combines session, President/active-mandate, `wp_check_password()`, compatible storage schema, and WebAuthn-library checks before producing options. Failures of these conditions use `generic_error()`. It then creates a registration challenge and serializes the creation options; generation/serialization errors also return the generic error and burn the challenge where applicable.
- `includes/class-zmp-passkeys.php`, `has_active_president_authority()` (lines 822–828) requires the exact President role and active mandate. Profile rendering confirms the UI considers this user eligible, but does not prove the options request passed every gate.

## Finding and confidence

The observed status text is consistent with both a rejected registration-options POST (including password rejection) and a rejected browser WebAuthn ceremony. The lack of an observed authenticator prompt makes an options-request failure or a Mobile Simulator/WebAuthn incompatibility plausible, but neither is proven. A finish-stage failure is also covered by the same catch path, although no prompt was observed. The password was reportedly the synthetic account’s current credential; this makes a typo less likely but is not runtime proof that `wp_check_password()` succeeded.

**No product defect or environment cause is confirmed. Do not classify this attempt as password failure or as a confirmed simulator failure.**

## One minimal safe discriminator

If another attempt is explicitly authorized, capture only the registration-options request’s HTTP status and whether the page reached “continue in browser”; do not capture request/response bodies, headers containing secrets, assertion/attestation data, or password. A 4xx options response would localize failure to the server/options stage; a successful options response followed by failure before an authenticator prompt would localize it to browser WebAuthn/option consumption. Do not repeat the ceremony unchanged solely to obtain this evidence.

No browser or database action was performed by this Debugger report. No source code was changed.

## Follow-up — 403 from registration-options POST — 2026-11-06

### Supplied runtime evidence

The Lead supplied the following instrumentation result: the same-tab local registration-options POST returned HTTP **403**; candidate and installed PHP/JS hashes matched; `ZMP_PASSKEY_DIAGNOSTICS` was reported enabled (`wp config get` output `1`); only ZMP was active, with no MU/drop-in plugins; PHP PID 286942 was `php -S 127.0.0.1:8101 -t /home/tonic/zmp-local-sandbox/phase66a-test/site`; stdout/stderr were routed to `run/php-server.log`; access log showed three `admin-post.php` POSTs with `[403]` and the log had zero `[ZMP_PASSKEY_DIAG]` markers. This runtime evidence is supplied by the Lead; I did not inspect the request body, browser, or database.

I read only the local WordPress core/plugin source and the single non-secret diagnostic constant line in the synthetic site's `wp-config.php` (literal `true`). I did not inspect any credential/config secrets. Local CLI PHP reports `error_log` unset and `log_errors` On; the built-in server process uses PHP CLI. I did not inspect its environment or process file descriptors.

### Dispatch and status-code trace

- WordPress core `wp-admin/admin-post.php` loads `wp-load.php`, loads admin includes, runs `admin_init`, then checks `has_action("admin_post_{$action}")` for an authenticated request. When the action is unregistered, core calls `wp_die('', 400)`; it then calls `do_action("admin_post_{$action}")` for a registered action. The inspected core file contains no 403 for this dispatch branch.
- Core `wp-settings.php` loads active/valid plugins before request dispatch. The active plugin's `zmp-competitions.php` requires the passkey/profile classes and calls `ZMP_Passkey_Profile::init()`; `init()` registers `admin_post_zmp_passkey_registration_options` to `handle_options()`.
- In `handle_options()`, `valid_request()` failure emits `options_request_rejected` with an allowlisted reason and then returns HTTP 403. If validation succeeds, the handler emits `options_request_accepted`, calls `registration_options()`, and returns HTTP 400 for its generic error. In particular, `wp_check_password()` failure is inside `registration_options()` and follows the 400 path, not the handler's 403 path.
- The site constant is literally `true`, satisfying the code's strict `ZMP_PASSKEY_DIAGNOSTICS === true` guard. The 403 validation branch calls `error_log('[ZMP_PASSKEY_DIAG] ...')` before sending the response. With PHP's CLI `error_log` unset and the supplied stdout/stderr redirection, a marker would normally be expected in `php-server.log` if that handler branch ran and the same logger destination was captured.

### Finding and uncertainty

The HTTP 403 is **not explained by WordPress core's ordinary unregistered-action response**, which is 400. It is consistent with the ZMP handler's `valid_request()` rejection or a separate 403 emitted before that callback (for example, an `admin_init` gate or an external/local request filter). A password or other `registration_options()` rejection alone does not explain 403 in the inspected candidate; it should produce 400.

The missing diagnostic marker conflicts with the ZMP callback's explicit “log then 403” path under the supplied config. The static hook registration and reported active plugin make an unregistered action less likely; the supplied matching hashes and CLI logging destination make a code/config mismatch or logger routing issue less likely, but do not eliminate them. Thus the evidence points to **request validation or a pre-callback 403, with diagnostic capture unresolved**. It does not prove which component emitted the 403, and I cannot conclude that the callback ran or that logging is misconfigured.

### Minimal next discriminator

Do not repeat the ceremony. On one already-authorized attempt, capture only (1) whether a sanitized `options_request_rejected` marker appears, and (2) the response status. If the marker appears, its allowlisted reason localizes the ZMP validation failure. If the status is 403 with no marker despite the confirmed constant and captured PHP stderr, determine whether the action hook ran using a temporary, fixed-label marker at the very first `handle_options()` line, before validation; remove that instrumentation immediately after the single authorized check. Do not capture request/response bodies, nonce, cookies, password, attestation, or other secrets. A pre-callback 403 remains the alternative if that entry marker is also absent.

No source, database, browser, credential file, or running service was changed. Only process metadata and PHP CLI configuration were read; no request was executed.

