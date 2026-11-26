## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — Subject Route Denial

**Version:** 1.2.4.65  
**Status:** FAIL — one assertion failed; no product defect established  
**Date:** 2026-11-04

## Candidate and environment

- Reviewed fixture: `tests/phase65-subject-president-route-denial-clone.php`
- Exact fixture SHA-256: `742C17BA22BD9ED103292A832CCFE1D3A6D102D3A42ADE32B6E9BCDBFE8C6425`
- Independent exact-hash Code Review: PASS for this hash; PHP lint PASS.
- Site: `/home/tonic/zmp-local-sandbox/phase65-subject-catalog/site`, `http://127.0.0.1:8100`
- Database/prefix: `zmp65_subject_catalog_test` / `z65_`
- Preflight: DB check PASS; one synthetic user/admin; only `zmp-competitions` active; subject proposal/event/catalog/alias tables empty.
- Existing processes were inspected and reused: PHP PID `218531` on 127.0.0.1:8100 and MariaDB PID `144183` on its private socket. No server was started or stopped; port 3319/PID 32192 was left untouched.

## Runtime result

The exact reviewed fixture was staged with a matching hash and run once through WP-CLI. It exited **1**, with **9/10 PASS**:

- **FAIL:** `teacher_direct_queue_url_returns_403`
- **PASS:** the response body did not disclose the synthetic subject name or decision controls
- **PASS:** valid-nonce Teacher decision POST returned 403 with a generic denial
- **PASS:** proposal remained pending with only its submission event; catalog and alias tables remained empty
- **PASS:** diagnostic option restoration, exact table baseline, request execution and cleanup

The fixture did not print the actual direct-queue HTTP status, so the observed output cannot distinguish an unexpected status with safe content from an assertion/route expectation mismatch. This result does **not** establish a product vulnerability or a test-fixture defect. No retry was made.

## Post-run cleanup

- `wp db check`: PASS.
- Users/admins: 1 / 1; only active plugin: `zmp-competitions`.
- Subject proposals, proposal events, catalog and alias rows: 0 each.
- Staged fixture copy was removed and verified absent.
- No external URL/email request was made. No product code or database schema changed.

## Next action

Debugger should determine the actual response status for the direct queue route and reconcile it with the active-President authorization contract. If the route correctly suppresses protected content without 403, update the acceptance assertion only after a bounded exact-response check and independent re-review; otherwise fix product behavior in `.65` and rerun the affected route acceptance on a new exact hash.
