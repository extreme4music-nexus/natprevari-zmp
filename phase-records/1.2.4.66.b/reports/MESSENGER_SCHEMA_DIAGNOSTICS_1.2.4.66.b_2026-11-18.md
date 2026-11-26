# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Messenger schema warning investigation — `.66.b` — 2026-11-18

## Finding

The admin notice used the shared `zmp_role_messenger_schema_retry` state to decide whether message retention was paused. The same state is set when any of the schema prerequisites in `zmp_competitions_commit_messenger_schema()` fail, including school profile, school email access, subject proposals, and admin facsimile. The old notice therefore attributed every failed plugin-schema migration to Messenger, even when Messenger itself was compatible. The previously observed production debug events for school profile readiness make that a concrete alternative cause; the notice alone could not distinguish them.

## Change

- The migration commit now evaluates the prerequisites independently and logs the failing component as `Plugin schema prerequisite not ready.`
- Messenger metadata checks log the first exact missing table/column/index or incompatible table/column/index definition as `Messenger schema mismatch detail.` The log includes only schema names and shape summaries, not message/profile row contents or credentials.
- The debug context sanitizer now retains these bounded schema fields and up to 40 structured mismatch details.
- The admin notice now says plugin schema migration is incomplete and directs the administrator to the exact component/table diagnostics. Retention still fails closed until all required checks pass.
- Existing Messenger installer behavior remains the repair mechanism: its idempotent `dbDelta()` migration attempts to bring the declared Messenger tables to the required shape; the commit marker advances only after all prerequisites pass.

## Local evidence

- Healthy current `.66.b` MariaDB schema check: Messenger and retention audit compatibility both returned `true`.
- Controlled mismatch: changed only synthetic local `z66a_zmp_role_retention_audit.trigger_type` from `VARCHAR(32)` to `VARCHAR(20)`. Compatibility returned `false`; the debug event recorded table, column, issue (`column_definition_mismatch`), expected capacity, and actual type. The original `VARCHAR(32)` was restored and compatibility returned `true`.
- Repair-path check: repeated the controlled narrow-column fixture and ran `ZMP_Role_Messenger::install()`. It returned `true`, restored `VARCHAR(32)`, and compatibility returned `true`. Row counts across all nine Messenger tables were unchanged. Retry/version/debug options were restored to their prior values.
- Prerequisite attribution check: invoked the migration commit with synthetic false readiness flags for Messenger, school profile, and school email. It returned false and emitted separate component records for `messenger`, `school_profile`, and `school_email_access`; options were restored.
- PHP syntax checks passed for the modified Messenger class, debug class, and plugin bootstrap. Local WordPress database integrity check passed before the targeted tests. No production request or database was accessed for this work.

Candidate source SHA-256: `class-zmp-role-messenger.php` `A8B2784E06D394B4795C6CA3C1DEC5EC99F280ACE1879F3601E8CAC27F49DCB5`; `class-zmp-debug.php` `CA41268813D592471C7633882F23FB14A6E7B273FCCFD6661862C6B1A3A6B047`; `zmp-competitions.php` `55D865DEA539FCB89A627F24A803E80DE2084716B95A7004B07308C3A86BB0DD`.

## Boundary

This resolves the false Messenger attribution and provides a safe local repro/repair path. It does not identify which production prerequisite is currently failing. Install this build, allow the normal retry request (or reactivate the plugin to force an immediate attempt), then inspect the Debugging log for `Plugin schema prerequisite not ready.`, `Messenger schema mismatch detail.`, and the school-profile schema detail events. If the Messenger installer cannot repair the reported production shape, the event identifies the exact remaining database mismatch for a targeted migration.
