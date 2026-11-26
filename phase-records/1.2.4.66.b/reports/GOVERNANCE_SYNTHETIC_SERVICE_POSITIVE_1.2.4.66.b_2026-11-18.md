# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President governance synthetic service check — `.66.b` — 2026-11-18

## Result: PASS (service-level only)

Re-ran `governance-document-service-positive.php` against the isolated local `.66.b` WordPress database. Synthetic administrator 119 uploaded a synthetic PDF for active synthetic President 116 / mandate 58; the exact President role/capability/active mandate, document-bound nonce, document authorization and decrypted bytes all passed. The helper removed the exact synthetic document/audit rows and PDF fixture and restored the original role metadata. Database integrity passed afterward.

Observed assertions: `exact_active_president_authority=true`, `route_nonce_valid=true`, `document_authorized=true`, `decrypted_content_matches_synthetic_pdf=true`, `document_row_removed=true`, `fixture_pdf_removed=true`, `document_count_restored=true`, `audit_count_restored=true`, `capabilities_restored=true`.

Candidate/runtime SHA-256 for `includes/class-zmp-president-governance.php`: `babf6a6310311eb97434acad7f41025bfd506822839797c91668b0ade5790b8d` (match). Reproducer: `governance-document-service-positive.php`, SHA-256 `3d7b6f837b9d646bd9ad71df02bcf9e9acbad526df66a461aaa6eb995a584ad9`.

This does not establish the actual HTTP download route. A separate CLI helper that called the streaming handler was invalid because the handler exits after sending headers/body; its output-buffer assertion and cleanup were therefore not a valid route test. The HTTP helper was inspected but not rerun because its target URL is taken from site configuration without a verified loopback-only restriction, and it can remove a same-named MU plugin. Do not treat either as route PASS. The local database was checked after the attempt; the leftover synthetic PDF was removed and the DB returned to baseline.
