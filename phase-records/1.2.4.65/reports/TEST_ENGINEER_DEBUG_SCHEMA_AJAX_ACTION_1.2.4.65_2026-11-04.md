## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — schema readiness AJAX action attribution (`.65`)

Date: 2026-11-04

## Scope

Verify that a `schema_not_ready` diagnostic records only a bounded, sanitized WordPress AJAX `action` in `context.request_action`, and only when the request path matches WordPress's current `admin_url('admin-ajax.php')` path. The check uses the disposable synthetic `.65` WordPress database; it does not invoke a real endpoint, send email, or access the network.

## Exact candidates and review

- Product file `includes/class-zmp-debug.php`: SHA-256 `ED252BEF7763DB792AE684117586693A688EA952412966F48CBEB2B2F5BCBEC6` — PHP lint **PASS**; exact-hash Code Review **PASS**.
- Test file `tests/phase65-debug-schema-ajax-action.php`: SHA-256 `C3E72A63FCBA430248D3BB4042DA37F85BB599B9C279C4A4DDFC06C300C2A8AF` — PHP lint **PASS**; exact-hash Code Review **PASS**.

## Runtime

- Environment verified before execution: database `zmp65_isolated`, prefix `z65_`; only `zmp-competitions` 1.2.4.65 was active; `wp db check` **PASS**.
- Command: `wp eval-file /home/tonic/zmp-local-sandbox/phase65-test/run/phase65-debug-schema-ajax-action.php`
- Result: **PASS 14/14**, exit code `0`, JSON `failures=[]`.
- Verified POST-over-GET precedence, GET fallback, cookie exclusion, scalar and blank handling, `sanitize_key` output, raw/sanitized length bounds, payload/nonce/URL exclusion, omission on non-AJAX paths and unrelated error codes, removal of caller-supplied `request_action`, and a custom `/custom-admin/admin-ajax.php` path supplied through an in-memory `admin_url` filter.
- The test restored the original request globals and exact raw option state. Before and after, `zmp_debug_events` had SHA-256 `985d1cd58cff73b6e84e4f04b1bcb07a7e6d7c955b2b5a6bc30b4065876fbd19` with autoload `off`; `zmp_debug_capture` was absent. No raw option contents were emitted.
- Post-run `wp db check`: **PASS**. Installed debug PHP was restored to its pre-run SHA-256 `B52524DAF151459BDB58B008698966EC67B8EDBE56A813E1A024EC75FF3A2016`. The temporary test copy in the phase-owned `run` directory was removed and absence verified. Existing `.65` server PIDs 144183 and 145525 were reused/left running; no server was started.

## Result and limits

This verifies privacy-safe diagnostic attribution behavior for the reviewed source in the synthetic `.65` environment. It does not identify the action from the Owner's historical logs, establish their originating site/version, or prove an AJAX handler exists for the affected school/email flows. Those source logs retain their original unconfirmed status.
