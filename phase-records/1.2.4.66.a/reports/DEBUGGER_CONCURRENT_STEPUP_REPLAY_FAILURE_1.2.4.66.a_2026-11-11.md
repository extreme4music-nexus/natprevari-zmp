# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger report — concurrent President step-up replay — `.66.a`

**Disposition:** INDETERMINATE: the one runtime FAIL is valid evidence, but available output cannot distinguish a product defect from a worker/fixture assertion failure. No repeat or database/service access performed.

## Bound evidence

- Candidate manifest SHA-256: `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`.
- Test Engineer report SHA-256: `950C8430E9D8405A40EC29CD61F1E1B40B291015077560462C2D243CBC28B7C0`.
- Fixture SHA-256: `36B7D2295145C4B255FC11731302052377484685B1E828ACFF48D7D91AD067AC`; fixture static safety review SHA-256: `C01039A9254D80AE87D02490346097879C7A2588718093ADD3A8C1318668C26F`.

The single clone-only invocation reached the exact consume-query rendezvous in both workers and returned one `rejected`, one `failed` status. It exited 1. The report explicitly leaves proposal/event, challenge-consumption, credential-counter, and worker-exit assertions individually unresolved because the emitted failure summary suppresses assertion labels. Cleanup, pre/post baseline equality, protected synthetic credential, owned-artifact removal and DB integrity were independently PASS. No retry was made and no runtime state remains to identify which assertion failed.

## Relevant product behavior (read-only)

- `includes/class-zmp-passkey-store.php:382-411`: `consume_challenge()` performs one conditional `UPDATE ... consumed_at IS NULL ... expires_at > UTC_TIMESTAMP()` and returns the binding only when exactly one row changed. This supports a single-use challenge under contention.
- `includes/class-zmp-passkeys.php:100-166`: `verify_step_up()` consumes before assertion parsing/verification, then validates the credential and persists the updated authenticator record. A worker that loses the consume race returns a generic error before credential-counter mutation.
- `includes/class-zmp-passkey-store.php:197-273`: credential update uses the exact prior serialized row as a compare-and-swap predicate; a concurrently changed row fails closed.
- `includes/class-zmp-president-subject-approvals.php:190-245`: the handler maps a rejected proof/result to `failed` and a successful `reject` decision to `rejected`, then redirects and exits.
- `includes/class-zmp-mentor-subject-proposals.php:131-213`: decision handling locks the pending proposal in a transaction and commits exactly one status update plus event; a non-pending proposal is rejected.

These code paths are consistent with one valid rejection and one failed replay, but do not prove that the winning handler persisted its proposal/event, consumed challenge and counter as asserted by the fixture. No confirmed product defect is established.

## Likely diagnostic gap and next safe action

The fixture collapses every failed assertion to `FAIL/BLOCKED` and hides the assertion labels. Keep the runtime gate **FAIL**. A later, separately reviewed diagnostic revision could emit only a fixed allowlisted assertion identifier (for example `proposal_event`, `challenge_consumed`, `credential_counter`, `worker_exit`) after cleanup, and record private pre-cleanup booleans; it must not emit proof/session/challenge values or raw SQL. Do not rerun the unchanged fixture. If the owner later authorizes another scoped run, review the diagnostic-only fixture revision and its preparation first; then a single run may distinguish product state from child-process exit bookkeeping.

No code edit, test run, DB/service/browser access, backup access or state mutation was performed for this diagnosis.
