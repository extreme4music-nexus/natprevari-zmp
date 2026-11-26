# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Passkey Login Backend — ZMP 1.2.4.66.a

**Review date:** 2026-11-05  
**Verdict:** **SPEC FAIL / QUALITY FAIL**  
**Scope:** Static review of exact source hashes below. No tests, site operations, database access, or UI inspection were performed.

## Exact candidate hashes

- `includes/class-zmp-passkeys.php`: `76CC60511EA21446E35D5C9BF4A2DFC1472F8094EBE1CB29F17ECD8677214FDE`
- `includes/class-zmp-passkey-store.php`: `E581879AD75DB716005E076A440F8C2732A1EE114D87F02BC4856DDE26C6E0A8`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`

The hashes were recomputed at review time and match the assigned candidate. The local vendored WebAuthn library is 5.3.9. Its `AuthenticatorAssertionResponseValidator::check()` runs the configured ceremony steps and returns the updated `CredentialRecord`; its request ceremony includes allowed-origin, RP-ID hash, user-verification, signature, and counter checks. This confirms the selected validator API is present, but does not substitute for runtime verification.

## Findings

### [P2] Login challenge is not bound to the origin/RP ID used to issue it

**Evidence:** `includes/class-zmp-passkeys.php:19-49` creates an anonymous challenge with only `user_id=0`, empty `session_hash`, and `ceremony=login`; no origin or RP-ID binding is persisted. `finish_login()` at `:59-85` consumes only those ceremony fields and then obtains `site_origin()` again for validation. `request_options()` at `:519-532` derives the RP ID from that current origin. The store's `normalize_binding()` at `includes/class-zmp-passkey-store.php:474-480` requires an empty `context_hash` for login.

**Risk:** The signed assertion is checked against whichever configured origin/RP ID is current when finishing, rather than the exact origin/RP ID associated with the outstanding challenge. This does not meet the phase plan's explicit requirement to bind login challenges to origin/RP ID. A configuration/origin change during the challenge lifetime can change the ceremony scope without invalidating the challenge. WebAuthn's cryptographic validation prevents arbitrary cross-origin use, but it does not itself prove the challenge was issued under this exact server-side origin binding.

**Acceptance condition:** Persist a server-side binding to the configured origin and RP ID when issuing the challenge, require that exact binding during atomic consumption, and add focused tests for altered origin/RP ID and unchanged exact-origin success. Preserve the store's rule that arbitrary action-context hashes are rejected for login; use a narrowly defined origin binding rather than accepting unconstrained context values.

### [P2] Anonymous login-options endpoint has no issuance bound or rate control

**Evidence:** `includes/class-zmp-passkeys.php:14-15,19-56` registers unauthenticated WordPress AJAX handlers and each options request creates a database challenge. `includes/class-zmp-passkey-store.php:329-362` inserts one row per request and deletes only already-expired rows; there is no per-origin/IP/session limit, global pending-row cap, or request-independent cleanup bound.

**Risk:** An unauthenticated caller can repeatedly invoke the endpoint and grow the challenge table for the full challenge TTL, creating avoidable database growth and write load. Because cleanup runs once per new request and only removes expired rows, it does not bound the number of live rows.

**Acceptance condition:** Add a bounded issuance policy and/or an independently verified front-door rate limit, plus a hard bound/cleanup policy for pending login challenges. Test that repeated anonymous requests cannot grow retained challenge state without limit while normal independent login attempts still receive distinct challenges.

## Scope disposition

- Anonymous discoverable options and generic failure handling are present by inspection; no runtime PASS is claimed.
- The AJAX success/error wrappers are same-origin guarded for options and route finish through the same request-context guard. Password-login hooks were not replaced in the reviewed entrypoint.
- Atomic challenge consumption precedes assertion parsing and validator invocation, which prevents replay but consumes the attempt on malformed assertions; this is safe fail-closed behavior.
- Store compare-and-swap is limited to the exact user ID and credential hash; it rechecks the serialized prior row and public key/user handle and prevents stale-counter overwrite. Concurrent valid assertions may fail closed, requiring a retry.
- PHP lint reported by the implementer is not independently rerun here. Login options/assertion behavior, challenge expiry/replay runtime, DB integrity, cleanup, and source/site parity remain **NOT RUN**. No phase acceptance, package, or release verdict is given.
