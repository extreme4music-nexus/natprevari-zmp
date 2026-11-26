# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review correction R3 — whole-plugin migration fixture — `.65` — 2026-11-05

**Verdict: PASS** for exact candidate `tests/phase65-whole-plugin-migration-retained-rows.php`, SHA-256 `33B6134BB3FD9F86DBE5B218A30A94C7921441FD29B4975941A5028B9B4D6153`.

The `.64` MariaDB inventory query now uses portable `SHOW INDEX FROM <validated table>` without `ORDER BY`; the fixture filters PRIMARY indexes in PHP and sorts their columns by `Seq_in_index`. The reviewer confirmed the full retained-row comparison, privacy/backfill handling, site/database/source/path guards, and duplicate-safe cleanup remain intact.

PHP lint: **PASS**. Review was read-only; no site/database runtime was performed. This exact hash is eligible for a fresh disposable runtime; gate remains NOT RUN until runtime and cleanup pass.
