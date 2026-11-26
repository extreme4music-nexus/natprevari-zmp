## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# OWNER MANDATE — READ FIRST: THE OWNER-CONFIRMED 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. DO NOT REOPEN IT OR COUNT OLDER NOT RUN/FAIL RESULTS FOR THAT SAME SCOPE AS OPEN WITHOUT NEW CONTRADICTORY EVIDENCE.
# ROLE STACK — EVERY ZMP MEMBER MUST HAVE SUBSCRIBER. TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE BOTH TEACHER AND SUBSCRIBER.

# Messenger retry notice follow-up — 2026-11-25

## Scope

The owner reports the Messenger schema/retention warning is still shown on the site. This follow-up tests the current .65 package on the isolated clone derived from zmp-database-20261125-162548.sql. Production was not accessed.

## Local evidence

1. Initial local state: zmp_db_version=1.2.4.65; retry and retry_after options absent; Messenger schema compatibility=true; retention audit compatibility=true; local HTTP server returned 200.
2. Full forward retry regression: set the local schema marker to 1.2.4.64 with retry pending and a future retry deadline. A normal request returned HTTP 200, migrated the marker to 1.2.4.65, cleared both retry options, and left Messenger/retention compatibility true.
3. Legacy Messenger index regression: replaced the local scoped_threads index with the historical shape missing region_key. Before repair, the validator returned false. ZMP_Role_Messenger::install() returned true and replaced the index with scope_type,season_id,category_id,region_key,thread_id,id. The validator then returned true.
4. wp db check succeeded after restoration.
5. The local .65 notice condition is now false because the marker is current and no retry is pending.

## Interpretation

The code path for the previously recorded duplicate_key/legacy scoped_threads failure passes locally in the current .65 package. The notice is rendered when the stored schema marker is not current or a schema retry remains pending; a new production notice alone does not identify which state applies. The latest available SQL remains the 16:25 dump, and the later site snapshot contains production DB credentials/host so it was not booted or queried.

The production notice is therefore not marked closed by this local PASS. In the production plugin UI, reactivation starts the migration immediately as the notice says. If the notice remains after that attempt, the new Debugging entries named “Messenger schema migration failure detail” or “Plugin schema prerequisite not ready” are needed to identify the live table/object/issue. No production changes were made.


## 2026-11-25 — focused Messenger lifecycle test

- Read-only inspection of the already-open Edge tab showed the existing Messenger schema warning on the live admin page. No production control was clicked and no production data was changed.
- Ran one focused lifecycle check against the isolated local clone at 127.0.0.1:8111, database zmp_debug_prodclone_20261125 on 127.0.0.1 only. The installed Messenger class and plugin entrypoint SHA-256 matched the .65 phase files exactly: BABFE20C900A4421DFD5154939EEE84BEB9824A80FF3B56639B8E9FC202BC655 and D5960724718861AD579643D664C6C5FB51BFC507C7A0D1871D58CF322C715034.
- Calling the low-level Messenger installer alone returned true and preserved schema compatibility; as designed, it left retry intent pending until the shared upgrade dispatcher committed all prerequisites. Then the normal zmp_competitions_init() dispatcher ran on the local clone: schema compatibility true, schema marker 1.2.4.65, retry intent cleared, retry-pending false, and the admin notice condition false. No dbDelta errors were recorded.
- Disposition: local .65 Messenger lifecycle check PASS for this cloned schema and exact code hash. This does not establish that production has the same physical schema or that its live warning is cleared; the live tab was observed read-only and production remains unmodified.
