# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Passkey Login Backend — Round 3 — ZMP 1.2.4.66.a

**Review date:** 2026-11-05  
**Verdict:** **SPEC FAIL / QUALITY FAIL**  
**Scope:** Read-only static review of the exact Task 4 churn-fix candidate. No runtime tests, site/data access, or product edits. Round 1 and Round 2 reports remain unchanged.

## Exact source hashes

- `includes/class-zmp-passkeys.php`: `BAA1D3D6CF27C7A06BA44A6ADC5FCA73AAD2E61AC5BF9668DE4AB6D938C14F9A`
- `includes/class-zmp-passkey-store.php`: `9AA2ACE4B9932DB8DD7D158BC22B003AB6D8F46D11B55ADD101798E54431F95D`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`
- Round 1 report remains SHA-256 `2E6F164C39EADD839E90A7444BC53A107D209FD4B213122D03AA423FB4180D6E`.
- Round 2 report remains SHA-256 `729EA07F3DC103DDD64FA6931953205123A8C2BC415629802E9E8CC4FEFD242A`.

## Prior findings

### Round 1: challenge origin/RP binding — CLOSED by static review
Login options persist a versioned SHA-256 digest of canonical configured origin plus RP host in the login challenge `context_hash`; finish recomputes and atomically matches it before assertion validation (`class-zmp-passkeys.php:26-40, 73-85, 264-285`). Default ports normalize away; non-default ports and scheme are included. Store shape rules keep registration context empty and require nonempty 64-hex context for login and step-up (`class-zmp-passkey-store.php:436-498`). A changed origin/RP configuration therefore cannot consume the outstanding login challenge.

### Round 2: options→invalid-finish churn bypass — CLOSED by static review
The issuance window now stores every issue timestamp, not challenge IDs, and does not remove an issuance when its challenge is consumed (`class-zmp-passkeys.php:181-231`). `login_issuance_window()` retains timestamps younger than the five-minute challenge TTL and rejects malformed, noncanonical, zero, or future timestamps with a generic failure (`:233-262`). Therefore options→invalid-finish churn no longer replenishes the peer's five-issuance quota while rate state is present.

## Remaining findings

### [P2] Rate state is an evictable transient and a missing value resets the quota

**Evidence:** `rate_limited_login_options()` treats `get_transient($transient_key) === false` as an empty issuance window (`class-zmp-passkeys.php:207`). WordPress transients use an external object cache when enabled and are not a durable storage guarantee; a missing value cannot distinguish a first request/expired window from early cache eviction. The database `GET_LOCK` serializes access to the transient key, but does not preserve or reconstruct the lost timestamps. Also, the code inserts a challenge before writing the transient (`:213-226`); if `set_transient()` fails, it burns the challenge but the consumed challenge row remains until expiry, while the failed write does not retain that issuance timestamp.

**Risk:** Cache eviction, per-node object caches, or transient write failure resets the per-peer five-per-300-second control. Under repeated loss/failure, issued challenge rows can still be produced without the intended durable rate cap. The original resource-exhaustion risk is therefore not fully closed for deployment configurations that do not retain/share transient state.

**Acceptance condition:** Persist the rolling count in a shared durable store guarded atomically with challenge issuance, or otherwise make absence/write failure fail closed before inserting a challenge. Verify the chosen storage is shared across app nodes and enforce a global pending/retained-row bound or an independently verified edge limit for many-peer traffic.

## Other requested checks

- **Concurrency/advisory lock:** Per-peer lock name is derived from the canonical IP hash, is below MySQL's lock-name limit, and is acquired before read/modify/write. The write path is inside `try/finally` with `RELEASE_LOCK`. On lock timeout or invalid peer, issuance fails closed with a generic response. Correct coordination still depends on requests sharing the same MySQL instance and transient backend.
- **Malformed/future transient:** Non-array state, nonnumeric/noncanonical/zero timestamps and future timestamps return a generic failure before creating a new challenge. Old timestamps at or beyond the TTL are pruned. Exactly five in-window timestamps are rejected; fewer than five permit a new issuance.
- **Expiry/consume:** Challenges remain database-expiring after 300 seconds and use atomic ceremony/context-bound consumption. Finish consumes before parsing/verification, so malformed assertions burn the attempt. Same-origin request validation and canonical origin digest are checked before consume.
- **WebAuthn/session behavior:** The vendored 5.3.9 assertion validator checks request ceremony origin, RP-ID hash, user verification, signature and counter. Credential lookup resolves the owner from stored credential ID, checks the stored user handle, and compare-and-swap updates the exact user's credential record before WordPress current-user/auth-cookie/wp_login actions. Password login bootstrap remains unchanged at the reviewed entrypoint hash. Static only; no runtime PASS.
- **Denial output:** Unknown, malformed, invalid-signature, expired/replayed and request-context errors use the same fixed generic JSON error. No concrete response-body account-enumeration issue was identified. Timing was not measured.
- **Peer privacy:** Raw `REMOTE_ADDR` is not stored by this code; the transient key uses an unkeyed SHA-256 of canonical IP. That is pseudonymous rather than anonymized, especially for IPv4's small address space. Consider HMAC with a stable site secret and keep the short TTL. WordPress references: [get_transient()](https://developer.wordpress.org/reference/functions/get_transient/) and [set_transient()](https://developer.wordpress.org/reference/functions/set_transient/).

## Status

The specific consumed-challenge churn loophole and origin/RP mismatch are closed by source inspection. The per-peer quota remains non-durable and can reset when transient state is missing, so the resource-exhaustion gate remains **FAIL / open**. Runtime options/assertion, transient eviction/write failure, threshold concurrency, changed-origin denial, expiry/replay, counter persistence, DB integrity, cleanup and source/site parity are **NOT RUN**. No whole-phase acceptance, package, or release verdict is made.
