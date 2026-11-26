## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Subject queue Teacher denial R5 — diagnostics restoration correction

## Candidate

- Test-only candidate: `tests/phase65-subject-president-route-denial-r5.php`.
- Exact SHA-256: `51494D483C0C98D9081CE4C78920AF5F1C7A72FEB3453385AB8799C7C9282246`.
- PHP lint: **PASS** (`php -l`, no syntax errors).
- Runtime: **NOT RUN**. Awaiting a new independent exact-hash Code Review PASS.
- Product source, R3/R4 candidates, and gate ledger: unchanged.

## R4 review finding and scoped fix

R4 Code Review found that its `finally` block unconditionally restored/deleted diagnostics rows, which could overwrite concurrent or unknown changes. R5 replaces that behavior with fail-closed ownership checks:

- Before any route request, diagnostics must still equal their exact captured option rows; no writes are made.
- After requests, `zmp_debug_events` is restored only if the captured baseline is an unchanged exact prefix and the entire suffix is exactly the validated ordered three-event route sequence: authenticated Teacher init, Teacher submit, and President decision forbidden. The event context requires the synthetic Teacher identity, `logged_in=1`, one role, the expected route/action class, and allowlisted fields. A hash of the captured raw suffix is rechecked before cleanup.
- Restoration uses conditional SQL predicates over the current raw `option_value` and `autoload`, then invalidates option caches and reads back the exact original row (or confirms exact absence when the option was initially absent).
- `zmp_debug_capture` is never written by this fixture. It must remain byte-for-byte equal to its captured row; any changed or unknown state is left untouched and makes cleanup fail.
- Incomplete requests, unknown/reordered/extra events, raw suffix changes, or restoration readback mismatches are preserved and fail cleanup rather than being rewritten.

The R4 same-cookie submit positive control, authenticated init evidence, President queue GET, valid-nonce denial POST, outbound guard behavior, and synthetic data cleanup scope are otherwise unchanged. R3's original FAIL and the R4 review FAIL remain historical evidence. Do not run R5 before independent review passes the exact candidate hash above.
