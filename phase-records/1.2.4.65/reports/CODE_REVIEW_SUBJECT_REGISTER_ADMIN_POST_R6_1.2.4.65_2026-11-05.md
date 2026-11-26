# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Subject Register Admin-Post Candidate R6

Date: 2026-11-05  
Verdict: **PASS — exact candidate review only**  
Candidate: `tests/phase65-subject-register-admin-post.php`  
SHA-256: `06911F1CACD1683D582269DAA360E632945515B873518CC930BACC788C01888C`

## Scope

Read-only review of the exact-hash test candidate against the `.65` Teacher subject proposal → active-President `approve_new` admin-post route criterion. Review covered candidate preconditions, route assertions, role/nonce cases, synthetic fixture attribution and cleanup. This verdict is not runtime acceptance, product-code acceptance, or whole-phase acceptance.

## Result

**PASS for this candidate’s source and cleanup design.** The current six-event diagnostics matcher follows the exercised route order and actor identities: Teacher submission; anonymous denial; President request with invalid nonce; Teacher denial; President decision committed; President handler processed. It rejects unknown, additional, duplicate-extra, or out-of-sequence events.

The candidate requires a fresh synthetic `zmp65_subject_test` database with prefix `z65s_`, loopback host `127.0.0.1` on port `8097`, one administrator, empty feature tables, and an absent `zmp_debug_events` option before writes. Post-write setup aborts throw through `finally`. Cleanup is scoped to fixture-owned proposal/catalog rows, sessions, mentor rows, the synthetic President mandate and its audit event, synthetic users, and task-owned guard/marker files; the candidate checks captured tracked-table hashes and verifies the diagnostics option is absent after a conditional value/autoload-matched delete and option-cache invalidation. Unknown state fails closed rather than being deleted.

## Prior candidate findings and supersession

The following findings applied to earlier candidate hashes and are closed only for the current exact hash:

| Earlier candidate | Finding | Current disposition |
|---|---|---|
| `7286913590E35F2FB0665F069A8FA33B475ECDFDA3ACDC76898E2AD4BD952F88` | P1: successful cleanup omitted synthetic users, mentor rows, and mandate/audit rows; post-write `exit()` paths could bypass `finally`. | Fixed in current candidate: cleanup covers owned records and post-write setup failures throw through `finally`. |
| `FA922F658B01F84A5E67B63297C4FBA5BA1C11E32CB396FC5348995792E17148` | P1: captured diagnostics-option baseline was not restored or compared. | Fixed in current candidate: requires an absent baseline and conditionally removes only the expected run-owned event option, with cache clearing and absence verification. |
| `983B536A2380FA041012A3CC6460400699D08E434F7772B6A13EA3C84EE33B7A` | P1/P2: matcher rejected the two expected `President subject decision denied.` events for anonymous and Teacher actors. | Fixed in current candidate: ordered sequence explicitly accepts both events with actors `0` and the synthetic Teacher ID. |

No earlier candidate runtime result is promoted or changed by this review.

## Limits and status

No runtime was performed. No local site, database, server, or service was accessed or changed. The candidate’s behavior remains **NOT RUN**; the Teacher proposal → active-President route remains **NOT VERIFIED** until an independently authorized, exact-hash run and post-run evidence are completed. This review does not authorize a run by itself.
