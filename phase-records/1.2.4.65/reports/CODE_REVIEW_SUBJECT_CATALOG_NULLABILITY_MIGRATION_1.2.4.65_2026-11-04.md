# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — subject-catalog NULLability migration

Date: 2026-11-04

## Product source

- File: `includes/class-zmp-mentor-subjects.php`
- SHA-256: `C861F19A9C5D8B06C2F600F3F1084E678BBFC2F31FDC7775335BDDFA1CFF22D7`
- Verdict: **PASS**
- Reviewer confirmed `install()` backfills only NULL `created_at`/`updated_at` values before `dbDelta` tightens the columns; non-NULL timestamps and other fields stay untouched. Errors abort migration, the change is idempotent, and readiness is checked after `dbDelta`.
- Runtime: **NOT RUN**.
- Follow-up at the same exact hash rechecked the combined `approved_subjects()` behavior: healthy empty results remain `[]`, query failures return generic `schema_not_ready`; no regression from the migration change. **PASS**; no execution.

## Migration fixture

- File: `tests/phase65-subject-catalog-nullability-migration.php`
- Initial SHA-256: `526C9FEDBE8749656E4282B893212C037C96286F4F5F54778DAF0DF37886D847`
- Verdict: **FAIL (P2)**
- The fixture correctly targets the isolated synthetic site, tests nullable drift and row preservation, but if it aborts after changing the columns to nullable and before the repair call, `finally` deletes only the probe row and does not restore/verify canonical schema readiness.
- Test Engineer's independent assessment also found the fixture lacked an explicit empty-catalog precondition and did not actually prove creation from an absent catalog table. No runtime was performed.

## Corrected fixture candidate

- SHA-256: `7721B5C671E33533C19F59A1951BEA185330FFDF0C58A1B030D35CE2D5A1F3A5`
- It now requires an empty catalog/no mentor rows, explicitly drops only the scoped empty catalog and checks `install()` recreates the table as NOT NULL, then tests nullable upgrade repair and data preservation. Its `finally` reruns install after any successful drop/drift and verifies canonical schema readiness and NOT NULL state.
- PHP lint: **PASS**. Independent exact-hash re-review: **PASS** for empty baseline, scoped table recreation, nullable upgrade repair, data preservation and finally schema restoration. Reviewer noted the fixture's preserved-field assertion omits `created_by`, `approved_by` and `approved_at`; reviewed migration code updates only the timestamp columns. Runtime: **NOT RUN**.

## Runtime result — 2026-11-04

- Exact reviewed source `C861F19A9C5D8B06C2F600F3F1084E678BBFC2F31FDC7775335BDDFA1CFF22D7` and fixture `7721B5C671E33533C19F59A1951BEA185330FFDF0C58A1B030D35CE2D5A1F3A5` were staged and run once on the guarded disposable clone.
- Fixture result: **FAIL** on the readiness assertion; its cleanup also reported readiness false. This is not a timestamp migration PASS. The clone's physical catalog columns were restored to NOT NULL and post-run DB integrity PASS; probe row count 0 and mentor count 0.
- Read-only follow-up showed `ZMP_Mentor_Subjects::schema_is_ready()` true but catalog/proposal readiness false. Independent code inspection identified the separately roadmap-recorded `tinyint(1)` normalization mismatch in the proposal catalog predicate. The timestamp DDL migration did create NOT NULL columns; compatibility remains incomplete until the follow-up predicate correction is reviewed and verified.
- No repeat of this fixture hash. The second mismatch was recorded in the roadmap before its fix.
