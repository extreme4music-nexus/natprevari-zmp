# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Owner diagnostic export review — `.66.b` — 2026-11-18

## Evidence read

Reviewed `zmp-diagnostics-all-20261118-162422.json` (500 retained events; export ended at 17:24:21 Europe/Skopje). No message/profile row contents or credentials were needed for this review.

## Findings from the export

- Most of the older failures concern school engagement/profile readiness: 223 `School engagement schema is not ready`, 11 `School profile schema is not ready`, and 224 dependent school-email prerequisite failures. They continue through 17:04.
- At 17:18:59 the plugin logged `School engagement created.` This proves that one school engagement write passed at that time; it does not prove all profile read/write paths passed. Subsequent profile operations at 17:19–17:20 were denied with `forbidden`, not `schema_not_ready`.
- The latest schema migration commit at 17:23:45 independently marked only `messenger` and `admin_facsimile` as not ready. Neither school profile nor school email was marked by that commit.
- The export contains no `Messenger schema mismatch detail.` nor `Admin facsimile schema mismatch detail.` event. The Messenger failure may have occurred in the installer before its final metadata compatibility check (for example, a `dbDelta` SQL error or retry-intent storage failure); the export does not identify which. The facsimile record also lacked its table/column/index cause.

## Follow-up instrumentation

Added safe structured Messenger installer-failure records (database statement category and table only; raw SQL/error text is not logged), plus exact admin-facsimile table/column/index mismatch records. Normalized integer display widths for the two facsimile binding columns. The shared admin notice continues to name the component from these records rather than attributing the failure to Messenger alone.

Local isolated checks: Messenger and admin-facsimile schema predicates both return true on the current local DB. A controlled Messenger `trigger_type VARCHAR(20)` fixture is repaired to `VARCHAR(32)` by `ZMP_Role_Messenger::install()`; compatibility returns true and all Messenger row counts are unchanged. Injected Messenger installer-failure and admin-facsimile shape events were confirmed to survive Debug sanitization. Full local WordPress database check passes. Production schema remains untouched.

After installing the follow-up ZIP, trigger one plugin activation/retry and inspect `Messenger schema migration failure detail.`, `Messenger schema mismatch detail.`, and `Admin facsimile schema mismatch detail.` The first new records will identify any remaining exact production blocker.
