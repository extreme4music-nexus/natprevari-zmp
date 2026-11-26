# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — subject-catalog TINYINT normalization

Date: 2026-11-04

- File: `includes/class-zmp-mentor-subject-proposals.php`
- SHA-256: `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`
- Verdict: **PASS**
- `normalize_column_type()` strips integer display widths while retaining base type and unsignedness. Expected `tinyint` therefore matches actual MySQL/MariaDB `tinyint(1)`; the correction leaves NOT NULL, auto-increment and index checks strict. Proposal/approval runtime code paths are unchanged.
- PHP lint: **PASS** per Backend Developer. Runtime: **NOT RUN**.
