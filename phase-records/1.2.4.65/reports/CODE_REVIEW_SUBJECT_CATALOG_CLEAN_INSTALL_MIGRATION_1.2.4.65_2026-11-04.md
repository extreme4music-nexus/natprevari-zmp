## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — `.65` subject catalog clean-install and timestamp migration fixture

Reviewer: independent Code Reviewer  
Review mode: static, read-only exact-hash review

## Decision

**PASS** for `tests/phase65-subject-catalog-clean-install-migration.php`, SHA-256 `12760A55752042F9BE4E9AF82E116AC321EE3CDF49A5CC209A0EFFC453ADFFFA`.

The fixture binds the loaded classes to the reviewed source hashes:

- `ZMP_Mentor_Subjects`: `A350933909C8046AB98B6F6CA9DC21DBAC68CD090ECC00F4BCCF89EBBF5475F6`
- `ZMP_Mentor_Subject_Proposals`: `8DD89AA7AAFE5A7AB1FC6F83A2B65112865842E9354578BB5A1BE18061C89F69`

It also checks the dedicated site path, DB, prefix, loopback port, plugin version, one synthetic admin, and empty catalog/mentor baseline. It tests catalog-table creation, required column types/nullability/indexes/readiness, repair after controlled nullable timestamp drift, preservation of existing non-NULL timestamps, and backfill of NULL timestamps.

Cleanup deletes only generated rows identified by unique keys, restores canonical catalog schema through the product installer, and checks baseline row counts. It restores the canonical empty catalog schema rather than arbitrary pre-run DDL; this is acceptable only on the verified disposable clone.

Scope is `ZMP_Mentor_Subjects::install()` catalog behavior. This is not whole-plugin installation acceptance. The reviewer did not execute the fixture or operate on the database; caller must verify the synthetic DB and local DB socket before runtime.
