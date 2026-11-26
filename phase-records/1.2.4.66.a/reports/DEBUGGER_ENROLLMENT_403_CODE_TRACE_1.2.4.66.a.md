# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger Report — Enrollment HTTP 403 code trace — 1.2.4.66.a

Date: 2026-11-11  
Scope: Read-only trace of the current enrollment options request handler and its diagnostics. No browser request, test, database, service, or product-code change was made.  
Verdict: **CODE PATHS MAPPED; RUNTIME SOURCE OF THE REPORTED 403 UNRESOLVED.**

## Exact current candidate hashes

- `admin/class-zmp-passkey-profile.php`: `705C7DDA64DB3307DB34C06D1A5134AEA76464DF78CAB77BD3406E5E6A5DDD6D`
- `includes/class-zmp-passkeys.php`: `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`
- `assets/js/zmp-passkeys.js`: `B8B2BD2F352797671A3BC92FCF3887D3C2A77CFF2104BAFE69EAB47DC685D4FB`
- Plugin entrypoint `zmp-competitions.php`: `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`

## Proven current-code behavior

1. `ZMP_Passkey_Profile::handle_options()` in `admin/class-zmp-passkey-profile.php` calls `valid_request()` first. If it returns false, the handler records `options_request_rejected` with its fixed reason, then sends JSON error HTTP **403**.
2. `valid_request()` can reject for: not logged in; missing `read` capability/invalid user; request method other than POST; action mismatch; missing nonce configuration; missing nonce; or invalid nonce. These are the current in-handler 403 branches.
3. If request validation passes, `handle_options()` records `options_request_accepted` and calls `ZMP_Passkeys::registration_options()`. A `WP_Error` there records `options_handler_rejected` and returns HTTP **400**, including current-password rejection and other registration preconditions. Success records `options_handler_succeeded` and returns HTTP **200**.
4. `diagnostic()` in the profile class, when `ZMP_PASSKEY_DIAGNOSTICS === true`, emits only fixed allowlisted event/category labels to `[ZMP_PASSKEY_DIAG]`; for the options request it also sets the allowlisted `X-ZMP-Passkey-Diagnostic` header. Validation 403 reasons map to fixed header values (`auth_required`, `capability_denied`, `request_invalid`, `action_mismatch`, `nonce_missing`, `nonce_invalid`). Handler rejection/success have their own fixed header values.
5. `postEnrollmentFormData()` and `enrollmentDiagnosticMessage()` in `assets/js/zmp-passkeys.js` read only the allowlisted diagnostic header and, if absent, derive a sanitized message from status, redirect flag, same-origin route class, and content-type class. They do not expose response bodies or raw URLs. The exact earlier wording “Дијагностика: барањето за серверските опции не успеа (HTTP 403).” is not an exact current JS message; it may be paraphrased or produced by older/stale loaded code.
6. The existing `PASSKEY_ENROLLMENT_SERVER_LOG_TRIAGE_1.2.4.66.a.md` (SHA-256 `8A6E859FC415BE98DB6BC413CB5CD8091384ECAE1427557E36EC6540FD05B595`) records two timestamped `admin-post.php` 403 entries without action/body, so neither is correlated to enrollment. It also reports missing diagnostic markers; recurring `admin-ajax.php` 403s are uncorrelated and use a different endpoint. The prior static debugger report says ordinary WordPress `admin-post.php` unknown-action dispatch returns 400, not 403.

## Runtime hypotheses — not established

- A correlated 403 with a ZMP diagnostic header/marker would prove the request reached `handle_options()` and identify a `valid_request()` rejection category.
- A 403 with no ZMP header/marker could mean a refusal before the handler (for example, an earlier local filter/server layer), or that the handler’s logger/header evidence was not captured. Current evidence does not distinguish these.
- A wrong password by itself is not a code-path explanation for HTTP 403 in the current candidate: after valid request checks, it is handled by `registration_options()` and maps to HTTP 400.
- The user-reported earlier 403 remains unmatched to a specific enrollment action. No root cause is claimed.

## Single safe next evidence

On the Owner’s already-planned local attempt, collect only: (a) confirmation that the request was the `admin-post.php` enrollment options request and its HTTP status; (b) the fixed `X-ZMP-Passkey-Diagnostic` value or sanitized displayed status/category; and (c) whether the matching fixed `[ZMP_PASSKEY_DIAG]` event appeared. Do not collect request/response bodies, passwords, nonces, cookies, credential/assertion data, or raw sensitive headers. Do not trigger another attempt solely for this report. If a 403 has no marker/header, keep the source unresolved; do not add instrumentation without a separately bounded decision.

No code, database, browser state, service, or other phase was changed.
