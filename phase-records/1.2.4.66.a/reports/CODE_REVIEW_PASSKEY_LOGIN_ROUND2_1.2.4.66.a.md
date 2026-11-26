# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Passkey Login Backend — Round 2 — ZMP 1.2.4.66.a

**Review date:** 2026-11-05  
**Verdict:** **SPEC FAIL / QUALITY FAIL**  
**Scope:** Read-only static review of the exact Task 4 candidate listed below. No runtime tests, site/data access, or product edits were performed. The Round 1 FAIL report remains unchanged.

## Exact candidate hashes

- `includes/class-zmp-passkeys.php`: `9708137CE291D8D55A00B04E35B2F1B7CD4FDF69EFA533FC41C4C8A1AB053925`
- `includes/class-zmp-passkey-store.php`: `9AA2ACE4B9932DB8DD7D158BC22B003AB6D8F46D11B55ADD101798E54431F95D`
- `zmp-competitions.php`: `15A87C3ECB1FFDFCACE9198D2F206060117396FA9C58FFD80F2A9A00D9CC9B93`
- Preserved Round 1 report `reports/CODE_REVIEW_PASSKEY_LOGIN_1.2.4.66.a.md` recomputed unchanged at SHA-256 `2E6F164C39EADD839E90A7444BC53A107D209FD4B213122D03AA423FB4180D6E`.

## Round 1 findings

### Origin/RP ID binding — CLOSED by static review

`login_options()` computes a versioned, domain-separated digest over the canonical configured origin and RP host and stores it as the login challenge's `context_hash` (`class-zmp-passkeys.php:26-39, 264-285`). `finish_login()` computes the digest again and requires it in the atomic consume binding (`:73-85`). The request options and assertion validator use the corresponding current RP host/origin. Context shape validation in the store keeps registration context empty, requires a nonempty lowercase SHA-256 for login and step-up, and rejects empty step-up context (`class-zmp-passkey-store.php:436-498`). Thus an origin/RP change between issue and consume fails the exact stored-hash comparison. Canonicalization lowercases/trims the host, removes default ports, validates port range and includes non-default ports in the digest.

This closes Round 1's missing server-side issue-time origin/RP binding finding for the reviewed service path. It remains static evidence; no altered-origin runtime assertion was executed.

### Anonymous issuance bound — PARTIALLY ADDRESSED; prior P2 remains OPEN

The endpoint now enforces at most five **simultaneously pending** challenges per canonical `REMOTE_ADDR` and serializes same-peer issuance using a MySQL named lock (`class-zmp-passkeys.php:181-230`). The rate state stores only hashed peer/challenge identifiers, rechecks challenge rows, and expires with the challenge TTL. Cleanup removes stale transient entries and `create_challenge()` deletes expired challenge rows (`class-zmp-passkeys.php:233-262`; `class-zmp-passkey-store.php:329-362`). This is a concrete per-peer pending-row bound, with no raw IP persisted by this code.

However, it does **not** enforce five issuances per 300 seconds. `pending_login_challenges()` removes a challenge as soon as its `consumed_at` is non-null (`:243-259`). The public finish route consumes a caller-provided challenge before parsing/verifying the assertion (`:65-100`); a caller can therefore obtain an options challenge, submit an arbitrary/malformed finish request to consume it, and immediately obtain another. Consumed rows remain in the challenge table until expiry cleanup, so this loop can still create an unbounded number of rows during the TTL. The limit bounds outstanding challenges only; it does not bound issuance rate or total retained rows under active churn.

**Acceptance condition:** Keep per-peer concurrency protection, and separately retain a per-peer count/window for all issued challenges for the full 300-second window even after finish consumes them. Also enforce a bounded global pending/retained challenge policy or equivalent verified edge limit/cleanup so distributed peers cannot grow the table without bound. Tests should cover repeated options→invalid finish churn, concurrency at the threshold, expiration, and cleanup.

## Additional security and regression review

- AJAX routes are registered for unauthenticated WordPress requests, and both use fixed generic 400 JSON failures. The options route checks actual same-origin/secure request context before creating a challenge; finish repeats this check before challenge consumption. No username/account identifier is accepted.
- Challenges remain bound to `user_id=0`, empty session, `login` ceremony, context digest, database expiry and atomic one-use consume. Finish consumes before parsing/verifying, so malformed and invalid assertions burn the attempt; this is fail-closed.
- The installed WebAuthn 5.3.9 source confirms `AuthenticatorAssertionResponseValidator::check()` runs the configured request ceremony, including allowed origins, RP-ID hash, user verification, signature and counter steps, and returns the updated `CredentialRecord`. The candidate passes expected request options, current RP host and server-resolved user handle to it.
- Credential ownership is resolved from the stored credential ID hash, not client account fields; the stored public key, user handle and exact credential ID are checked before validation. The update uses compare-and-swap on exact serialized prior row/user/credential hash and rejects stale nonzero counters. The no-counter zero/zero case permits a no-op/backup-state update. The auth cookie and `wp_login` hook occur only after verification and storage update.
- The invalid assertion path returns the same response body as unknown credential, expired challenge and request-context failures. No account fields or sensitive assertion details are sent to output or logs. No concrete response-body enumeration finding was identified statically.
- `zmp-competitions.php` hash is unchanged from Round 1; existing WordPress password login remains untouched by the reviewed diff. No PHP lint or runtime result is independently verified in this review.

## Status

The origin/RP binding P2 is closed by source inspection. The Round 1 anonymous resource-exhaustion P2 is only partially addressed and remains open because challenge churn bypasses the pending-only cap. Overall Task 4 source review remains **SPEC FAIL / QUALITY FAIL**. Runtime login options/assertion, invalid signature, changed-origin denial, expiry/replay, rate-limit/concurrency, storage/counter update, cleanup, database integrity and source/site parity remain **NOT RUN**. No whole-phase acceptance, package or release verdict is made.
