## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# R11 fixture candidate — require cron-disabled synthetic runtime

**Status: test-only candidate prepared; runtime NOT RUN pending independent exact-hash Code Review.**

R10 passed the first Teacher-submit route assertions, restored the diagnostics option exactly, and recorded a blocked `network` marker. Debugger read-only tracing identified WordPress core's scheduled cron loopback (`wp-cron.php`) as a high-confidence possible source because core can spawn due cron work during request shutdown. This was not confirmed for the deleted R10 site; the caller remains unconfirmed.

Only `tests/phase65-subject-register-admin-post.php` changed. Before creating synthetic users or performing any POST, R11 requires `defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true`. Otherwise it stops with fixed privacy-safe stage `stage_wp_cron_disabled_required`. It reports fixed `wp_cron_preflight_*` route substeps. The runtime's disposable `wp-config.php` must define this constant before plugin activation, and the subsequent setup must verify the constant is true in both WP-CLI and the PHP HTTP server context. No product code, active site config, or outbound guard behavior changed; non-allowlisted HTTP remains blocked.

- Candidate SHA-256: `7540C73BC2ACB33399FE6FA49DFA3B6CD374ABF0593ED439AA635B394BE1F4A1`
- PHP lint: **PASS** — `php -l` reported no syntax errors.
- Runtime: **NOT RUN**, pending independent exact-hash review.
- Preserve the R10 runtime result (6/7; network marker blocked), R9 runtime FAIL, R3 FAIL, and all President photo/consent PASS evidence. Gate ledger unchanged.
