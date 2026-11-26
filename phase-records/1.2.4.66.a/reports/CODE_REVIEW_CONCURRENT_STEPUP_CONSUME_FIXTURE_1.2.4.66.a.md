# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Concurrent Step-up Challenge Consume Fixture

**Date:** 2026-11-11  
**Verdict:** **FIXTURE SAFETY PASS; DETERMINISTIC UPDATE-LEVEL RACE COVERAGE INCOMPLETE**  
**Scope:** Read-only review of the exact fixture and preparation note. No fixture execution, source edits, DB access, browser use, or server operations. No phase acceptance.

## Exact artifact hashes

- `tests/phase66a-passkey-concurrent-consume.php`: `D1FD6152F88DBF754DCC85FD4A406AD2A43FB08DE322309DD29AA956F153CEE1` (recomputed; matches assigned hash).
- `reports/PASSKEY_CONCURRENT_STEPUP_CONSUME_FIXTURE_PREPARATION_1.2.4.66.a_2026-11-11.md`: `78EA44D9A26F20B6A5BD600C156DF8CE0DA1CFDB495BD16FDFADDD8B26D59B09` (recomputed; matches assigned hash).
- Expected source hashes checked in the fixture: entrypoint `c0f6cd135f7fbefb09847fa047f76828736c30c0fa802dad3f06fdd9c3a4989f`; passkeys PHP `acee4133dee295e2bdd401ef22b7cd304626a53f0f09d698027c69e34dcdc2ae`; passkey store PHP `f9038de6499b844529118fe98ab8bd32fe30414bd1061bc35650f638308d02db`.

## Safety review

- **Clone/database scope is strongly restricted.** The fixture requires WP-CLI, exact clone site and fixture paths, clone-private socket path, DB name, prefix, Home/Site URL `http://127.0.0.1:8102`, sole active ZMP plugin, schema marker/compatibility, and exact relevant plugin hashes. It also requires the one-user/one-credential/zero-challenge baseline and the protected synthetic credential row. Workers repeat these checks. This cannot reach the Owner DB through the specified clone-private socket; no HTTP call or PHP server is used.
- **The tested operation is the real store method.** Two separate WP-CLI worker processes call `ZMP_Passkey_Store::consume_challenge()` with the same challenge and same normalized binding after a common file release gate. The stored row and each worker's binding check require `ceremony=step_up`, `action=president_subject_decision`, resource `1`, decision `reject`, canonical-target-bound context digest, empty peer hash, unconsumed state and unexpired TTL. The digest format matches `ZMP_Passkeys::step_up_context_digest()`'s current domain-separated field sequence.
- **Worker launch and private artifacts are bounded.** `proc_open()` receives an argument array with `bypass_shell`; it starts only two WP-CLI commands using the clone path. Tokens/nonces/fingerprint are in files under a unique mode-0700 run directory with mode-0600 files; worker stdout/stderr is redirected. Output contains only fixed labels, booleans and counts. Cleanup deletes only the exact challenge and known files, refuses to remove an unexpected nonempty directory, restores WPDB error suppression, and checks credential fingerprint, user/credential/challenge counts and DB integrity.
- **No direct change to the protected credential is expected.** Challenge creation/consumption and exact challenge-row deletion are the only intended table mutations. The fixture does not submit an assertion or call the President route.

## Important scope and determinism limits

1. **This is a storage-layer consume test, not a valid end-to-end President proof.** The fixture selects the sole baseline account `zmp66a_admin`, which prior UI evidence identifies as role `administrator`; it does not establish an active `zmp_president` session or call `step_up_options()` / `verify_step_up()`. It constructs an internally consistent step-up-shaped binding with a synthetic session hash and nonce. That is sufficient to exercise `consume_challenge()`'s stored binding and one-use predicate, but does not show the exact challenge could pass production step-up authorization/assertion verification. The preparation note should describe this as a synthetic bound-store challenge, not as an authenticated President proof.
2. **The barrier is before the PHP method call, not immediately before its atomic SQL UPDATE.** Both workers complete matching preflight and block until both ready files exist; then the parent releases them together. Each `consume_challenge()` still performs schema checks before its `UPDATE`. The workers are genuine concurrent callers and this can expose duplicate-consume bugs, but the fixture cannot guarantee that both DB UPDATE statements overlap. A passing run is evidence for two synchronized concurrent calls, not a deterministic schedule at the exact row-lock boundary.
3. **False return is not separately categorized.** `consume_challenge()` returns `null` for a consumed/mismatched challenge and for SQL/schema failure. The final one-consumed-row check plus matching preflights support the expected result, but the fixture does not directly assert that the losing worker's UPDATE returned zero specifically because the other worker consumed the row.
4. **Process timeout is bounded for workers but not for the final DB-check subprocess.** Worker readiness/result waits have deadlines and child handles are closed/terminated. The `wp db check` subprocess is closed without an explicit timeout; a stuck clone database could make the parent hang during cleanup. This is a non-data-safety operational limitation.

## Minimal strengthening if deterministic race evidence is required

For exact SQL-boundary scheduling, add a temporary test-only `wpdb` query filter inside each worker immediately before `consume_challenge()`. It must match only the exact prepared `UPDATE` against the child challenge table and the in-memory hash for this owned challenge; each worker signals ready and waits until both have reached that filter. Then pass the original query unchanged to MySQL after atomic release. Keep this hook in fixture-only code, verify exactly two matches, suppress SQL text, and retain the same child-only preflight/backup/cleanup. To cover a genuine active-President context too, create and clean up a synthetic President plus mandate/session, then issue the challenge through `step_up_options()` and reuse the exact stored binding. These extensions are recommendations only and were not implemented or run.

## Conclusion and limits

**Fixture safety: PASS** for a clone-only, no-network, two-process call to the real `consume_challenge()` method, subject to fresh preflight and recovery backup before execution. **Race-evidence limit: PARTIAL** because the barrier does not force both UPDATE statements to the SQL boundary and the context is not a production-valid active-President proof. Any eventual result must retain these limits. This review does not establish runtime behavior, Task5 acceptance, or phase/package/release acceptance.

