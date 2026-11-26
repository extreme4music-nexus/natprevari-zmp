# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# Passkey schema partial-migration failure — .66.a — 2026-11-25

## Result

**PASS — v2-to-v3 partial migration failure is rejected fail-closed.** The exact v3 `ALTER TABLE ... ADD COLUMN peer_hash` statement was intercepted once while exercising the actual `.66.a` `ZMP_Passkey_Store::install_schema()` on the existing local WordPress/MariaDB instance at `:8111`. No PHP or database server was started or stopped.

## Isolated fixture and procedure

- Used a unique temporary table prefix inside the already-running local clone database; created empty scratch copies of its passkey tables and transformed the challenge table to the prior v2 shape by removing `peer_hash` and `ceremony_peer_expiry`.
- Confirmed the normal v2-to-v3 migration emits `ALTER TABLE <scratch challenge table> ADD COLUMN peer_hash char(64) NOT NULL DEFAULT ''`; then repeated on a fresh scratch fixture while replacing that exact table-specific DDL with a deliberate failing SQL statement.
- Invoked the real `ZMP_Passkey_Store::install_schema()` and `create_challenge()` paths. The injected failure was caught exactly once.
- Removed only the scratch tables and confirmed the primary local clone was unchanged.

## Observed results

| Check | Result |
|---|---|
| v2 fixture created | PASS |
| Exact `peer_hash` ALTER intercepted once | PASS |
| `install_schema()` returned false | PASS |
| Partial schema compatibility returned false | PASS |
| `peer_hash` remained absent after injected failure | PASS |
| `create_challenge()` refused to issue a challenge | PASS |
| Schema marker update attempted | 0 |
| Main schema compatibility before/after | PASS / PASS |
| Existing main credential row count before/after | unchanged |
| Scratch tables removed | PASS |
| Main clone `wp db check` | PASS |

A supplementary fresh-schema test also intercepted challenge-table creation once: install returned false, the partial schema remained incompatible, challenge issuance was blocked, scratch tables were removed, and the main schema/credential count stayed unchanged.

## Source identity and impact

The phase source and installed `:8111` runtime `includes/class-zmp-passkey-store.php` matched SHA-256 `f9038de6499b844529118fe98ab8bd32fe30414bd1061bc35650f638308d02db`. No product source change was needed for this item. The temporary test scripts were kept under `/tmp` only and are not part of the release.

This closes original `.66.a` open-scope item 3 only. Expiration cleanup timing remains separately open; the existing expired-cleanup failure-path PASS is not being reused as proof of ordinary expiry cleanup.
