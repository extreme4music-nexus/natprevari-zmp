# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Passkey Login Backend — Round 6 — ZMP 1.2.4.66.a

**Review date:** 2026-11-05  
**Verdict:** **SPEC PASS / QUALITY PASS — static review only**  
**Scope:** Read-only review of the expired-row cleanup fail-closed correction and prior Task 4 security/quota findings. No tests, local site/database operations, or product edits. No phase/release acceptance.

## Exact source hashes

- `includes/class-zmp-passkeys.php`: `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7`
- `includes/class-zmp-passkey-store.php`: `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`
- Round 5 report remains `reports/CODE_REVIEW_PASSKEY_LOGIN_ROUND5_1.2.4.66.a.md`, SHA-256 `E06C539C09BD26285776299E304A01676E31F3139AAB36F134BF1E09B2B7BA42`.

## Round 5 cleanup finding — CLOSED by static review

`ZMP_Passkey_Store::create_challenge()` now runs expired-row deletion before challenge insertion (`class-zmp-passkey-store.php:331-382`). The delete is inside a `try/catch`; an exception or strict `false` result returns `null` before `$wpdb->insert()` is reached. A successful delete returning `0` is correctly accepted as “no expired rows.” Thus a failed cleanup cannot issue or persist another challenge. The challenge random value may have been generated in memory, but it is not returned or inserted on cleanup failure.

For login, `create_login_options()` maps the null result to the generic `WP_Error`; `rate_limited_login_options()` releases the peer and global locks in its `finally` block, then returns the generic failure. The AJAX wrapper sends the same fixed 400 response used for other denials. No challenge exists to burn on this cleanup-failure path. If a challenge was inserted but a later lock release fails, the existing path attempts to consume/burn that row after lock release and still denies the request.

## Prior quota and schema findings — CLOSED by static review

- Schema-v3 structural validation checks `peer_hash` type `char(64)`, `NOT NULL`, and empty-string default, along with the named composite index columns. The v2→v3 `dbDelta()` migration advances the passkey marker only after successful structural validation; failed validation leaves the feature incompatible/fail closed.
- Login issuance acquires global then peer-specific MySQL named locks. It counts unexpired login rows created in the rolling 300-second window, including consumed rows, enforcing global maximum 500 and peer maximum 5 before challenge creation. Count errors, lock errors/timeouts and unconfirmed release are generic fail-closed outcomes. Locks are released peer-first then global in `finally`.
- Peer key is a keyed HMAC of canonical validated `REMOTE_ADDR`; raw address and unkeyed IP hash are not persisted. Finish intentionally does not compare the current peer with the challenge's issuance peer, so network changes during the authenticator flow are allowed. Store consumption still requires a nonempty peer hash, blocking legacy peerless v2 login challenges, and retains exact origin digest, expiry, one-use and anonymous login binding.

## Other security checks

The existing login path still uses no account identifier, required user verification, discoverable credentials, exact server-side credential/user-handle lookup, the pinned WebAuthn assertion validator, and credential CAS/counter update before establishing the WordPress user/auth cookie. Public rejection responses remain generic. Password-login bootstrap remains unchanged at the reviewed entrypoint hash. No new static authorization, SQL binding, secret-handling, or lock-order defect was found in the reviewed correction.

## Limits and status

This review establishes source behavior only. Cleanup failure injection, schema migration/shape, global/per-peer concurrency and thresholds, legacy peerless-row rejection, peer-independent finish, valid/invalid assertions, counter update, cleanup baseline, DB integrity, and source/site parity remain **NOT RUN** by this reviewer. Task 4 may proceed to its independent functional tests and remaining review gates; this report does not accept the phase, package, or release.
