# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Static Review — Step-up One-time Challenge Consumption Under Concurrency

**Date:** 2026-11-11  
**Verdict:** **STATIC CONTROL-FLOW PASS — true concurrent runtime interleaving NOT VERIFIED**  
**Scope:** Read-only review of current challenge consumption, assertion verification, President decision routing, and proposal write transaction. No source/fixture edits, tests, DB/browser/server operations, or phase acceptance.

## Exact candidate hashes

| File | SHA-256 |
|---|---|
| `includes/class-zmp-passkey-store.php` | `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB` |
| `includes/class-zmp-passkeys.php` | `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE` |
| `includes/class-zmp-president-subject-approvals.php` | `7E8C431DA2CEEFEB09C91E23AB293260E959743765E35CE9B27A3BAEFE904021` |
| `includes/class-zmp-mentor-subject-proposals.php` | `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69` |

## Race analysis

**Two requests cannot both consume the same stored challenge under the reviewed SQL path.** `ZMP_Passkey_Store::consume_challenge()` performs one conditional `UPDATE` setting `consumed_at`, whose `WHERE` clause requires the exact challenge hash and full binding, an unconsumed row, and unexpired timestamp (`class-zmp-passkey-store.php:382-411`). Only `query() === 1` returns a binding; zero rows, SQL failure, or incompatible schema returns `null`. With concurrent requests, the database serializes writes to the challenge row and re-evaluates the `consumed_at IS NULL` predicate against the committed row; after the first statement consumes it, the other statement cannot also match. There is no read-then-write split for challenge consumption.

`ZMP_Passkeys::verify_step_up()` rechecks nonce, secure request context, current President authority, library/schema readiness and exact session/origin/action/resource/decision/canonical-target context before consumption, then consumes before parsing/verifying the assertion (`class-zmp-passkeys.php:100-132`). Therefore one malformed or invalid first submission burns that challenge and cannot leave a concurrent copy available. A second call using the same proof/challenge should fail before assertion verification or the decision route.

Even when two separately issued challenges/assertions exist for the same proposal, the decision service starts a transaction and locks the target proposal row with `SELECT ... FOR UPDATE`. It rejects a non-pending row, conditionally updates only `status='pending'`, appends one event, and commits/rolls back as a unit (`class-zmp-mentor-subject-proposals.php:132-213`). Thus interleaved distinct proofs should still yield at most one decision/event for the same proposal. `handle_post()` invokes the decision service only after successful proof and another nonce/active-President check (`class-zmp-president-subject-approvals.php:190-245`).

No static path was found where two real interleaved submissions can both consume one exact challenge or both commit the same proposal decision. The critical same-challenge control is the atomic conditional update, not a PHP mutex. Database-specific runtime semantics, engine/configuration and two-request behavior were not inspected or exercised.

## Smallest useful plugin-behavior verification if required

Run only on a disposable synthetic `.66.a` clone/database with verified backup, exact source/site parity and no unrelated active plugins. Create one synthetic active-President session, one pending synthetic proposal, one credential, and one valid step-up challenge/assertion. Send two concurrent requests to the actual decision route with the **same exact challenge, assertion, action, proposal, decision, nonce and session cookie**. Use a test-only barrier outside product source (for example, a temporary clone-local request/query interceptor immediately before the exact challenge-consume `UPDATE`) so both requests reach the one-use boundary before either SQL statement executes; do not add a product synchronization hook or timing sleep.

Expected safe outcome: exactly one consume reports success; one submission gets the generic rejection; at most one proposal state transition and one corresponding event exist; no second alias/catalog side effect occurs; the challenge is consumed once; credential counter/state remains valid for the accepted assertion; exact synthetic rows, session state and protected credential baseline are restored afterward; database integrity passes. Emit only booleans, integer status/counts and allowlisted outcome labels—never the challenge/assertion/cookie/nonce, user payload, SQL text or credential identifiers. Remove the temporary barrier and restore the clone baseline after the run. If the barrier cannot be made deterministic without product changes, report concurrency as NOT RUN rather than inferring it from sequential replay.

A separate two-proof/same-proposal run is not necessary to answer the one-use-proof question; the proposal row lock/conditional update remains a distinct defense whose concurrent behavior may be included if broader race acceptance requires it.

## Limits

The existing Task5 matrix explicitly records true concurrent interleaving as NOT RUN. This report is a static control-flow review only, does not upgrade that runtime status, and does not accept Task5, the phase, package or release.

