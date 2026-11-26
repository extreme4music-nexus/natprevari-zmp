# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — President Step-Up Concurrent Route Replay Fixture — `.66.a`

**Date:** 2026-11-11  
**Review type:** Independent, read-only static review of a test-only fixture  
**Verdict:** **PASS for fixture safety/design only**  
**Runtime validation:** **NOT RUN**; this report does not establish product behavior or phase acceptance.

## Exact reviewed artifact

- `tests/phase66a-president-step-up-concurrent-route-replay.php`
- SHA-256: `36B7D2295145C4B255FC11731302052377484685B1E828ACFF48D7D91AD067AC`
- Size: 34,014 bytes

The fixture hash was recomputed from the reviewed file and matched the assigned hash.

## Review findings

No static fixture safety blocker was found.

- **Clone and source guards:** The fixture binds execution to the isolated clone path, expected loopback origin/port, database name and private socket, table prefix, fixture path, sole installed/active plugin, schema marker/compatibility, and exact hashes for the plugin entrypoint and relevant passkey, store, President approval, and proposal service sources (lines 10–42). Worker mode also requires a uniquely named child directory directly under the expected run root with mode `0700`, plus a mode `0600` private request envelope (lines 43–61).
- **Exact step-up proof:** Each worker validates the same real synthetic President session token, route nonce, active mandate, and unconsumed challenge binding to the exact President subject-decision action, proposal ID, `reject` decision, and context hash (lines 64–98). It then prepares the exact `consume_challenge()` SQL predicate. The test filter pauses only when the SQL equals that exact prepared query (lines 106–114).
- **Concurrency synchronization:** Two WP-CLI workers are started. The parent waits until both report reaching the exact consume-query boundary before writing one shared release marker (lines 238–254). Each worker must report one boundary hit and a handler result; the parent requires exactly one rejected route and one failed route, then checks one proposal decision/event, one consumed challenge, and one credential counter advance. This is a deterministic interleaving immediately before the database's atomic consume update, rather than concurrent fixture setup alone.
- **Synthetic data and privacy:** The parent creates isolated synthetic teacher/President users, a temporary mandate, passkey credential/key, proposal, session, nonce, and signed assertion (lines 174–235). Worker stdout/stderr are redirected to `/dev/null`; the proof/session envelope and coordination artifacts are private. Emitted summaries omit assertion, cookie, nonce, challenge, and key values.
- **Cleanup and recovery boundary:** Cleanup is keyed to fixture-created IDs/hashes, releases or terminates owned worker processes, removes only the fixture's allowlisted files in its own run directory, destroys its session, restores request/server globals and owned debug-option changes, and compares database/options/credential snapshots with the pre-run baseline. It performs a bounded clone-local database integrity check. The exact site/database/socket guards prevent intentional routing to another site or database.

## Required preparation before execution

Because this draft launches two WP-CLI child processes and mutates the isolated clone database, create a preparation note before running it. The note should bind this exact fixture hash and the current source manifest/hashes; record the exact clone path, database/socket, plugin inventory, and baseline/verified clone-local recovery backup; specify the invocation and expected two-worker barrier and route outcomes; and document bounded timeout, worker termination, artifact cleanup, and baseline-restore steps. Keep runtime status **NOT RUN** until that separately authorized execution occurs. A fixture review is not product-behavior evidence or phase acceptance.

## Limits

This review was static only. The fixture was not executed; no database, service, browser, or site was accessed or mutated. No claim is made that the plugin race behavior passes, that cleanup succeeds at runtime, or that `.66.a` is accepted.
