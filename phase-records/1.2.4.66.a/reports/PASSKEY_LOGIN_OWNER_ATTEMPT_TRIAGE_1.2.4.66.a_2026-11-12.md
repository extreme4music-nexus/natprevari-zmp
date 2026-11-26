# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Passkey login Owner-attempt triage — 2026-11-12

**Disposition: FAIL for the reported browser attempt; runtime cause UNRESOLVED / NOT REPRODUCED.** Password login is reported to work.

## Observed evidence

- Owner reports the UI message: “Најавата со passkey не може да се заврши. Обидете се повторно или користете ја лозинката.”
- Privacy-safe local PHP access summary: five POSTs to admin-ajax.php since the server was started. The latest two records at 06:00:15 and 06:00:58 returned HTTP 403; an earlier record at 05:59:05 returned HTTP 200. No request action was inspected, so these records are not correlated to the passkey options or finish action.
- No fixed [ZMP_PASSKEY_DIAG] markers were found. The login path has no such markers; the current fixed diagnostic helper is for registration only. Their absence does not identify the login failure stage.
- Candidate/runtime SHA-256 parity is exact for the login JavaScript (assets/js/zmp-passkey-login.js: 6E161DF437384E40A9ADE2BA93265BA4C56A78EFDA5854CAC5DE2C698A812F6F), passkey backend handler (includes/class-zmp-passkeys.php: ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE), and login UI controller (includes/class-zmp-passkey-login-ui.php: E67E97427E1F96317DD5768A40FA4FD02488AD590F9250A31DC14A531523336C).
- Aggregate synthetic DB query reported one passkey credential across one user; it does not establish that this credential belongs to the account used in the reported attempt.

## Code mapping and limits

- Login endpoints are anonymous admin-ajax actions: zmp_passkey_login_options and zmp_passkey_login_finish.
- Their plugin-level rejection wrappers use HTTP 400. The frontend maps any non-2xx response or assertion/API error to the same generic failed message. Thus the user-visible text cannot distinguish options rejection, authenticator cancellation/API failure, finish rejection, or another HTTP layer.
- The access log records method, endpoint class and status only; it omits POST action by design. The observed 403s therefore cannot be assigned to the passkey handlers. The plugin's own expected rejection status is 400, but that does not prove the 403 came from outside the handler without action correlation.

## Next safe discriminator

On the existing failed Network entry, report only the action name (zmp_passkey_login_options or zmp_passkey_login_finish), HTTP status, content type, and any fixed safe response category. Do not share request/response body, assertion, challenge, cookie, nonce, account identifier, or credential material. Do not repeat the attempt until the existing request details have been checked if they remain available.
