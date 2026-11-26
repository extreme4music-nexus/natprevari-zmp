# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Passkey Login Backend — Round 4 — ZMP 1.2.4.66.a

**Review date:** 2026-11-05  
**Verdict:** **SPEC FAIL / QUALITY FAIL**  
**Scope:** Read-only static review of the exact schema-v3 durable-quota candidate. No local site/database work, runtime tests, or product edits. Earlier round reports remain unchanged.

## Exact hashes

- `includes/class-zmp-passkeys.php`: `6C6ED88DB103FA908301AB57A65F228EAC2BAB6C73C26444EB310120F623BA96`
- `includes/class-zmp-passkey-store.php`: `AA11AD06D134DA3055AE0F7969F2AAF1F51DC97DF8FB677CF726E9669FECD848`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`

## What is closed by static review

- The transient-based limiter is removed from the issuance path. `request_peer_hash()` canonicalizes `REMOTE_ADDR` with `inet_pton`/`inet_ntop` and computes keyed `HMAC-SHA256` using `wp_salt('auth')`; raw IP and unkeyed IP hashes are not written (`class-zmp-passkeys.php:252-265`). Missing/invalid peer or salt fails closed before challenge creation.
- The per-peer quota now queries challenge rows in MySQL for `ceremony=login`, exact peer hash, `created_at` within the rolling TTL, and `expires_at` still in the future (`:187-231`). It deliberately does not filter `consumed_at`, so consumed invalid-finish challenges still consume issuance quota. Under the same database and WordPress salt configuration, `GET_LOCK` serializes each peer's count/insert; the lock is released in `finally`. Failure to acquire/count/release returns the same generic public error; release failure burns the newly issued challenge.
- SQL count errors (`null`/non-numeric result), exception, invalid HMAC peer or lock timeout fail before challenge insertion. The five-in-300-second cap is checked before `create_login_options()` inserts a row. Query placeholders are prepared; table name comes from the WordPress `$wpdb->prefix`.
- Finish intentionally does not require the browser's current peer hash to equal the stored issue peer. `consume_challenge()` requires the challenge row to have nonempty `peer_hash` (so old schema-v2 peerless login rows cannot be used) while matching challenge ID, anonymous user/session, ceremony, exact origin digest, expiry and one-use state. This allows client IP changes during a WebAuthn ceremony without permitting reuse of peerless legacy challenges.
- V2→v3 migration is additive in `install_schema()`: `peer_hash CHAR(64) NOT NULL DEFAULT ''` and index `(ceremony, peer_hash, expires_at)` are declared via `dbDelta`; the passkey schema marker advances only after structural validation. Old peerless challenge rows remain denied by the consume predicate and naturally expire. Credential/registration/step-up bindings remain respectively empty/valid-context/no-peer, valid-origin-context/peer-bound login, and valid step-up context/no-peer.
- Canonical origin/RP digest, required user verification, exact server-side credential/user-handle lookup, library validation, credential compare-and-swap/counter update, generic denial output, and login cookie only after successful assertion remain as described in earlier rounds. Entrypoint hash is unchanged.

## Findings

### [P2] Schema-v3 compatibility check does not verify the peer-hash column definition

**Evidence:** `tables_are_structurally_valid()` includes `peer_hash` among required names and checks index name/order/uniqueness (`class-zmp-passkey-store.php:546-592`), but `table_structure_is_valid()` calls `SHOW COLUMNS ...` and uses only column names (`:567-603`). It does not verify `peer_hash` is `CHAR(64) NOT NULL DEFAULT ''` or that the column type can store a full 64-hex HMAC. `install_schema()` can therefore accept the v3 marker after `dbDelta()` if a same-named but malformed column/index structure remains; request-time `schema_is_compatible()` repeats only this shallow check.

**Risk:** A malformed/truncated peer-hash column can make inserts fail closed under strict SQL mode, or truncate values in permissive mode so future exact-hash count queries do not match prior rows and the quota can be bypassed. This specifically weakens the new security control while the schema marker reports compatible.

**Acceptance condition:** Extend structural verification to check at least the peer-hash type/length, `NOT NULL`, default, and exact composite index structure before setting/accepting schema marker 3. Add a fixture or migration check proving v2→v3 corrects/blocks a wrong peer-hash definition without advancing the marker.

### [P2] Durable per-peer quota still has no aggregate bound across peers

**Evidence:** Count and lock are keyed by one HMAC peer value (`class-zmp-passkeys.php:196-230`); the challenge table permits any number of distinct peer hashes. There is no global cap on live login challenges or verified front-door aggregate rate control in this candidate. Expired rows are cleaned opportunistically in `create_challenge()`; that cleanup does not cap unexpired rows.

**Risk:** Many source addresses can each stay under five requests per 300 seconds while collectively growing the challenge table without bound. This is the residual distributed version of Round 1's anonymous resource-exhaustion risk across many source addresses.

**Acceptance condition:** Add a bounded global pending/retained login-challenge policy, or verify and document an external aggregate rate/abuse control before release. Preserve the per-peer counter and the short expiry cleanup.

## Privacy and deployment notes

- HMAC prevents a database/cache reader from cheaply brute-forcing IPv4 peer values as with plain SHA-256. The transient is gone; peer HMAC persists only on short-lived login challenge rows until expiry/cleanup. Rotating `AUTH_SALT` resets each peer's quota; multi-node deployments need the same relevant WordPress salts and database to coordinate.
- Five options requests per peer per 300 seconds also groups users behind a shared NAT/proxy unless the web tier supplies a canonical client address in `REMOTE_ADDR`. The code correctly ignores untrusted forwarded headers; test the actual authorized deployment topology before relying on this limit.

## Status

Round 3's missing-transient-state and consumed-challenge-churn issues are closed by the durable row counter for a given peer, assuming a correct v3 schema and shared DB/salts. Two P2 items remain open: incomplete structural verification of the quota column, and no aggregate bound across distinct peers. The origin/RP, challenge expiry/consume, credential/session and public denial paths pass static review only. Migration execution, schema-v2 legacy-row denial, SQL/lock failure handling, exact threshold/concurrency, HMAC parity, login assertion, counter persistence, cleanup, DB integrity and source/site parity are **NOT RUN**. No phase, package, or release acceptance is given.
