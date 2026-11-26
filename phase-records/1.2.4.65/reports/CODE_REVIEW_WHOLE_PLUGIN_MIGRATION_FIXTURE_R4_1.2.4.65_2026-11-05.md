## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review correction R4 — whole-plugin migration fixture — `.65` — 2026-11-05

**Verdict: PASS** for exact candidate `tests/phase65-whole-plugin-migration-retained-rows.php`, SHA-256 `AC93CAB62B0867BF25FF888FBDF917979653D88521169E76E2B05B8C2C660257`.

The `.65` readiness assertion no longer calls the nonexistent public `ZMP_Mentor_Subjects::schema_is_compatible()` method. It uses the public `approved_subjects()` query and fails if that returns `WP_Error`; the database version marker is still required, and the activation path commits it only if subject schema installation succeeds. The `.64` baseline branch checks only APIs available there. Existing whole-table content/count comparisons, source/site/database/path guards, timestamp privacy and cleanup safeguards remain intact.

PHP lint: **PASS**. Review was read-only; no site/database runtime was performed. This exact hash is eligible for a fresh disposable runtime after the previously created task sandbox is confirmed removed.
