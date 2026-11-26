## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review correction R2 — whole-plugin migration fixture — `.65` — 2026-11-05

**Verdict: PASS** for exact candidate `tests/phase65-whole-plugin-migration-retained-rows.php`, SHA-256 `B651E0FAE63288CC914ED3E44F784D704B0C5CE4103276796C2712F9F7721529`.

This additive review covers the correction to the `.64` preparation precondition. The candidate no longer calls `ZMP_Mentor_Subjects::schema_is_compatible()` on `.64`, where that method is absent. Exact `.64` version markers and available school/profile/email readiness checks remain; the whole-plugin inventory covers actual baseline `zmp_` tables. The previously reviewed full-row comparison, timestamp privacy/backfill, exact row counts, repeat activation, and cleanup guards remain intact.

PHP lint: **PASS**. Review was read-only; no site or database was accessed. This report supersedes the prior fixture-source PASS only for the candidate hash; runtime acceptance remains separate and NOT RUN.
