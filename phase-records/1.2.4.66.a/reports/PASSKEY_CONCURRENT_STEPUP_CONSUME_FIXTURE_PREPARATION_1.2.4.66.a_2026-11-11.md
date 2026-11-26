# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Preparation note — same-challenge step-up consumption fixture

**Status:** PREPARED; **NOT RUN**. Awaiting independent exact-hash review. No database rows were changed, no worker process or barrier file was created, and no PHP server was started during preparation.

## Exact artifacts and candidate
- Fixture: `tests/phase66a-passkey-concurrent-consume.php`
- Fixture SHA-256: `D1FD6152F88DBF754DCC85FD4A406AD2A43FB08DE322309DD29AA956F153CEE1`
- Current source manifest SHA-256: `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`
- Relevant plugin hashes: passkeys `ACEE4133DEE295E2BDD401EF22B7CD304626A53F0F09D698027C69E34DCDC2AE`; store `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; entrypoint `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`.

## Exact target and current read-only check
- Only target: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, database `zmp66a_isolated`, prefix `z66a_`, clone-private MariaDB socket. The fixture checks the exact site and clone-test-file paths, DB identity, home/site URL `http://127.0.0.1:8102`, sole active plugin, marker 3, schema compatibility, current source hashes, one synthetic user, one protected synthetic passkey credential and zero challenge rows before creating anything.
- Preparation preflight matched these exact target values and DB integrity passed. Owner PHP PID 620 remains on `127.0.0.1:8101`, Owner MariaDB PID 409 and clone MariaDB PID 3828 remain present. No clone PHP listener exists on port 8102. The fixture performs no HTTP/network calls and starts no server.
- Before a later run, create and verify a fresh private mode-0600 backup of this clone DB; keep its identity and contents only in the sandbox.

## Planned behavior and barrier
The fixture creates one synthetic `step_up` challenge through the real `ZMP_Passkey_Store::create_challenge()` API. Its binding uses the current candidate's President subject-decision action, synthetic resource ID 1, decision `reject`, canonical subject ID 0, a synthetic session hash, synthetic WordPress nonce and a context digest following the current `ZMP_Passkeys::step_up_context_digest()` format. No proposal or credential row is created or changed.

It launches two WP-CLI workers against the exact clone through the private DB socket. Each worker verifies the stored user/session/ceremony/action/resource/decision/context/peer binding for the same challenge. Each writes a mode-0600 ready marker using atomic rename immediately before waiting at the barrier. The parent releases both only after both ready markers exist, using an atomic release-file rename. Each worker then invokes the real `ZMP_Passkey_Store::consume_challenge()` once. Expected result: exactly one accepted consume and one rejected consume.

The challenge token, synthetic route nonce and private credential-baseline fingerprint stay in mode-0600 files inside a mode-0700 unique child run directory. Worker command arguments contain only worker IDs and that clone-local directory path. Worker output is redirected; fixture output is limited to allowlisted status and counts. No token, nonce, session value, credential material, SQL text or user data is printed.

## Cleanup and limits
On bounded completion, the orchestrator closes or terminates only its own two WP-CLI worker handles, deletes only the exact fixture-owned challenge, verifies users/credentials/challenges return to 1/1/0, compares the protected credential fingerprint in memory, verifies schema compatibility, runs `wp db check` with output suppressed, and removes only known barrier/result files and its unique child directory. Unknown leftover files make cleanup fail rather than trigger broad recursive deletion. A fresh private DB backup is the recovery point for an interrupted process; the fixture does not perform a broad automatic DB import.

This is a storage-level concurrency check for a structurally valid synthetic President step-up binding. It does not exercise the HTTP decision route, President authorization/session issuance, UI, WebAuthn assertion, or production behavior. Before use, copy the exact fixture into the clone-private `tests` directory and verify the copy hash. The command must pass that clone-local fixture path both to `eval-file` and as its required self-path argument. Do not execute until an independent reviewer approves the exact fixture hash.
