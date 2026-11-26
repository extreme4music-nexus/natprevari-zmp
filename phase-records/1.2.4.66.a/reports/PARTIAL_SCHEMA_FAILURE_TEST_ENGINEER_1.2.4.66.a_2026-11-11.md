# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Partial passkey schema fail-closed test — Test Engineer

**Date:** 2026-11-11  
**Overall verdict:** **INCONCLUSIVE / NOT PASS** — the narrowly matched failure injection did not intercept the DDL. No broader retry was made. The child was restored and verified.

## Review and exact candidate identity

- Independent injection-method review: `CODE_REVIEW_PARTIAL_SCHEMA_FAILURE_INJECTION_METHOD_1.2.4.66.a.md`, SHA-256 `84CD1B38632F8192729C001BB85AA7EECFF02342031CE77E29F9319B93AB5487`.
- Current passkey store source SHA-256: `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`.
- Candidate tested: `.66.a`; child reported only `zmp-competitions` active. No source, test fixture, or report outside this report was modified for the injection.

## Scope and safeguards

- Target was the synthetic child site `phase66a-schema-create-20261111/site`, DB `zmp66a_schema_create`, prefix `z66a_`. Main clone `zmp66a_isolated` and Owner PHP/MariaDB processes were outside the target and left unchanged.
- Preflight: marker `3`; `schema_is_compatible()` true; passkey credential/challenge row counts `0/0`; `wp db check` passed; no child PHP listener on port 8102; clone MariaDB PID 3828 present. Main clone remained marker `3` with its pre-existing one synthetic credential and zero challenges; Owner PHP PID 620 and MariaDB PID 409 remained present.
- A new whole-child database recovery point was verified readable, nonempty, and mode `0600` before mutation. Its identifiers and derived values remain only in the sandbox. A private pre-mutation scope record was also saved there.
- No browser, production site, external network, real account, or other phase was used.

## Commands and execution

All commands were run from the child WordPress path with its configured database connection; the backup/import command details are retained only in the private sandbox record.

1. Read-only preflight: `wp config get DB_NAME`, `wp config get table_prefix`, marker and passkey row-count queries, `wp eval` for `ZMP_Passkey_Store::schema_is_compatible()`, and `wp db check`.
2. One reviewed child-only preparation: drop only the two confirmed-empty passkey tables and delete only `zmp_passkey_schema_version`; recheck their absence and database integrity.
3. Add a temporary child `mu-plugins` injector. During `plugins_loaded` priority 10 it attempted to remove only `ZMP_Passkey_Store::install_schema` at priority 20 and registered a `$wpdb` query filter for only the exact challenge-table `CREATE TABLE` statement. The filter replaced a match with `SELECT 1` and counted matches in memory. No broad match was used.
4. One explicit call: `wp eval 'ZMP_Passkey_Store::install_schema();'`, with safe booleans/count only. Recorded: automatic callback removal `true`; exact DDL intercept count `0`; install result `false`; compatibility `false`; marker absent; credentials table present; challenges table absent; `create_challenge()` unavailable (`null`) with a structurally valid synthetic registration binding.
5. Because the exact filter did not match, the injection criterion failed. No retry or broader matcher was attempted. The temporary injector was removed and the verified backup restored using a database-only import, without WordPress bootstrap between injector removal and restore.

## Outcomes

| Criterion | Result | Evidence |
|---|---|---|
| Exact challenge DDL intercepted once | **FAIL** | In-memory intercept count was `0`; no broadening/retry. |
| Direct `install_schema()` fails closed under the intended injected fault | **NOT RUN / INCONCLUSIVE** | The method returned false and compatibility was false in the partial state, but the exact fault was not injected; these observations do not establish the reviewed scenario. |
| Marker absent, credential table present, challenge table absent in partial state | **PASS (state observation only)** | Safe boolean query immediately after the one attempt. |
| Guarded `create_challenge()` unavailable in partial state | **PASS (state observation only)** | Returned null with a valid synthetic registration binding; no challenge row was created. |
| Partial-state database integrity | **PASS** | `wp db check` passed before restore. |
| No retry or broadened filter | **PASS** | Stopped after the zero-intercept result. |
| Injector removed and child restored | **PASS** | Database-only restore completed; injector absence verified. |
| Restored schema and compatibility | **PASS** | Marker `3`; compatibility true; both passkey tables present. |
| Restored indexes | **PASS** | Credential indexes: `PRIMARY`, `credential_id_hash`, `user_id`; challenge indexes: `PRIMARY`, `challenge_hash`, `expires_at`, `user_id`, `ceremony_peer_expiry`. |
| Restored database integrity | **PASS** | `wp db check` passed. |
| Baseline/unrelated synthetic data | **PASS** | Pre/post checksums for categories, competitions, seasons, and season categories matched; users/categories/competitions counts remained `1/5/1`; passkey row counts returned to `0/0`. |
| Services and isolation | **PASS** | Child MariaDB PID 3828 remained; no PHP listener on 8102; main clone and Owner PIDs remained present. |

## Limitations and follow-up

This run does **not** verify actual fail-closed behavior when challenge-table creation fails, because the exact `dbDelta()` query did not equal the reviewed filter string. The cause was not investigated by broadening or instrumenting additional SQL. A future attempt would need a newly reviewed, narrowly scoped method for observing the exact query shape. The child is back at `.66.a` with marker `3`, both tables/indexes, compatible schema, baseline data, and no injector; no PHP server is running.

