# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Reviewer — whole-plugin migration fixture R5 — 1.2.4.65

Date: 2026-11-05  
Reviewed artifact: `tests/phase65-whole-plugin-migration-retained-rows.php`  
Exact SHA-256: `A95933304FD893711A1BA3627C464AACF2FFCB75651C039E500CD17BB0FBE160`  
Verdict: **PASS** (read-only source review)

## Findings

- Baseline table existence, baseline column presence and primary-key shape remain checked.
- For keyed and keyless tables, every baseline row fingerprint must remain present with at least its baseline multiplicity. This correctly allows additive migration rows while detecting a missing or changed baseline row.
- Existing subject-catalog timestamp backfill checks remain in place.
- The fixture still binds execution to its exact synthetic database, prefix, disposable site, plugin source manifests and hash-only external manifest. Raw row values are not emitted.

## Scope note

This comparator proves retention; it does not independently classify whether every added row is an intended migration seed. Runtime evidence must report observed per-table row deltas as such. This limitation is consistent with the `whole_plugin_migration_retained_rows` gate, whose criterion is retention of baseline rows.
