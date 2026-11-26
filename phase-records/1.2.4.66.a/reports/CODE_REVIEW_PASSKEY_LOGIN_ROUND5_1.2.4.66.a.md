# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Passkey Login Backend — Round 5 — ZMP 1.2.4.66.a

**Review date:** 2026-11-05  
**Verdict:** **SPEC FAIL / QUALITY FAIL**  
**Scope:** Read-only exact-hash review of schema-v3/global-quota candidate. No runtime tests, database/site operations, or product edits. Round 4 report remains unchanged.

## Exact source hashes

- `includes/class-zmp-passkeys.php`: `587CEAA26CB3A023717B0B366249449D0385FC038204FD798D50562473B73DC7`
- `includes/class-zmp-passkey-store.php`: `4A6BC4D0DACD23A0C41318FCAEAEA6886B1032406E40269A89CFF19401036B42`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`
- Preserved Round 4 report: SHA-256 `7F97CDB9BD36FF1889273583B2AB95BDD60763C028D697374F665DABCDBF0C6A`.

## Round 4 findings

### `peer_hash` column exactness — CLOSED by static review
`tables_are_structurally_valid()` now requires challenge `peer_hash` to be exactly `char(64)`, `NOT NULL`, default empty string (`class-zmp-passkey-store.php:564-570, 572-611`). Index structure is checked by name, uniqueness and ordered column list (`:612-641`). The passkey marker remains behind this structural check: `install_schema()` runs `dbDelta()`, returns without advancing on failed validation, writes schema version 3 only after validation, then reads the marker and revalidates (`:18-76`).

### Aggregate/per-peer issuance cap — CLOSED by static review
`rate_limited_login_options()` acquires one global named lock, then one canonical-peer lock, always in that order; both are released in `finally` in reverse order (`class-zmp-passkeys.php:187-299`). It counts all login rows created during the last 300 seconds with unexpired timestamps; it intentionally has no `consumed_at` predicate, so finished/invalid attempts still count. It enforces the global limit of 500 and per-peer limit of 5 before calling the private challenge-creation method. SQL count results must be numeric; lock, query, exception, and release failures return generic failure. Newly created challenge is burned when either lock release is not confirmed.

`request_peer_hash()` canonicalizes only validated `REMOTE_ADDR` and uses keyed HMAC-SHA256 from `wp_salt('auth')` (`:300-315`); neither raw IP nor an unkeyed IP digest is stored. Challenge creation is the only service call in the reviewed code path that writes a login challenge and it occurs while both locks are held. Finish intentionally does not compare the current peer hash, so a client IP change during the authenticator ceremony does not invalidate a valid challenge; legacy peerless rows remain denied by the store predicate.

## Finding

### [P2] Expired-challenge cleanup errors are ignored, leaving retained-row growth unbounded on failure

**Evidence:** `ZMP_Passkey_Store::create_challenge()` inserts the challenge, then runs `DELETE FROM <challenge table> WHERE expires_at <= UTC_TIMESTAMP()` at `class-zmp-passkey-store.php:331-365`. It ignores the delete result and still returns the new challenge ID when deletion fails. The new 500 cap counts only rows whose `expires_at` is still in the future (`class-zmp-passkeys.php:231-240`), so expired rows left by a failed cleanup do not consume the issuance quota on subsequent windows.

**Risk:** If cleanup fails repeatedly while inserts continue to succeed, each 300-second period can add up to 500 expired challenge rows indefinitely. This defeats the aggregate retained-row bound under a cleanup/database failure mode; the schema-v3 aggregate cap bounds live challenges, but not retained rows when the only cleanup query fails.

**Acceptance condition:** Make challenge issuance handle cleanup failure explicitly—fail closed before inserting, check/require successful cleanup after a safe ordering, or add a separately verified cleanup path that bounds expired-row retention. Add a focused failure-path criterion showing that a failed cleanup cannot return a new challenge while unbounded expired rows accumulate.

## Additional review notes

- All relevant quota count and challenge insertion work occurs under the global and peer locks. Global-first/peer-second acquisition and peer-first/global-second release avoid an in-module lock-order inversion. Failure acquiring the peer lock returns through `finally` and releases global lock. Release failures deny the request; if a challenge was created, the code attempts to consume/burn it while the challenge row continues to count against the quota.
- Lock name `zmpplr_global` is server-wide, so separate WordPress databases sharing one MySQL server will serialize their login-options requests unnecessarily. This is a throughput concern, not a quota bypass; a database/site-scoped global lock name would avoid unrelated contention.
- V2→v3 remains additive: schema adds `peer_hash CHAR(64) NOT NULL DEFAULT ''` and `(ceremony, peer_hash, expires_at)` index via `dbDelta`; marker update is after structure validation. Legacy v2 login rows retain empty peer hash and cannot be consumed. This was verified by static control-flow only; migration was not executed by this reviewer.
- The count is rolling on `created_at > UTC_TIMESTAMP()-300s` and `expires_at > UTC_TIMESTAMP()`. At 500 global or 5 for the current peer it denies; consumed rows are included. The two named locks serialize concurrent service endpoint calls against these count/insert operations. Correctness assumes these requests use the same MySQL server and shared site configuration.
- Context/origin digest, WebAuthn assertion validation, owner/userHandle lookup, credential CAS/counter persistence, generic denial, and WordPress session/cookie only after successful verification remain unchanged from the earlier static review. The anonymous finish flow remains peer-independent by design.

## Status

Round 4's schema exactness and aggregate cap findings are closed by source inspection. One P2 remains for cleanup failure leaving expired rows outside the cap. No runtime schema migration, threshold/concurrency, SQL/lock-failure, cleanup-failure, peer-independent finish, assertion/counter, DB-integrity, cleanup, or source/site parity test was run. **No whole-phase acceptance, package or release verdict is given.**
