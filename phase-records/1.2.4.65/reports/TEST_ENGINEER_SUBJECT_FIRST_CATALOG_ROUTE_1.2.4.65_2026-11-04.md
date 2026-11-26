## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer Report — `.65` first-catalog Teacher submission route

## Result

**PASS 7/7**, one runtime attempt, exit code 0.

- Fixture: `tests/phase65-subject-first-catalog-admin-post-clone.php`
- SHA-256: `8E2B6F6780FF81FA52B0F7764A0F364E01F11F716767E1C361CE1C4AF9A5F0B3`
- PHP lint: PASS
- Exact-hash Code Review: PASS, `reports/CODE_REVIEW_SUBJECT_FIRST_CATALOG_ROUTE_1.2.4.65_2026-11-04.md`

## Preflight and route

Used disposable site `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`, `http://127.0.0.1:8100`, DB `zmp65_subject_catalog_test`, prefix `z65_`. The DB uses the project-owned private MariaDB socket `/home/tonic/zmp-local-sandbox/phase65-test/run/mariadb.sock` (MariaDB PID `144183`). Existing phase PHP PID `218531` on loopback port 8100 was reused. Pre-run DB integrity passed; only ZMP `.65` was installed/active; one synthetic administrator and compatible proposal schema were present; catalog, alias, proposal and event tables were empty. Required product source hashes matched the workspace candidate.

The fixture created a temporary synthetic Teacher and sent one nonce-authenticated loopback POST to the actual `zmp_submit_subject_proposal` admin-post route. The HTTP result was a 302 submitted redirect without subject text. Exactly one pending proposal and one matching `submitted` event were recorded for that Teacher; no canonical catalog or alias row was created before President review.

## Cleanup and limits

All **7/7** assertions passed, including exact cleanup and row/option/user baseline restoration. Post-run `wp db check` passed, proposal schema remained ready, and catalog/proposal/event/alias/mentor counts returned to zero. Only the original synthetic administrator and ZMP plugin remained. The staged fixture copy was removed and verified absent. No server was started/stopped and no product source changed.

This is positive first-catalog submission only. It does not cover negative-role submission or render the Teacher UI. The runner's `pre_http_request` guard allows only its loopback request; it is not a server-process egress monitor. These scope limits match the independent review.
