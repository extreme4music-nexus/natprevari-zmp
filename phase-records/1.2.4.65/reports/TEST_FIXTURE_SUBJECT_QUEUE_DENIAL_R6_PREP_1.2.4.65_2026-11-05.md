## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Subject queue Teacher denial R6 — authenticated admin-cookie and diagnostics correction

## Candidate

- Test-only candidate: `tests/phase65-subject-president-route-denial-r6.php`.
- Exact SHA-256: `909934ADEAC32155F02424443C6E9B0FBAE95F91C5BEE62DC8BEAC2910039227`.
- PHP lint: **PASS** (`php -l`, no syntax errors).
- Runtime: **NOT RUN**. Awaiting independent exact-hash Code Review PASS.
- Product source, R3/R4/R5 candidates, and gate ledger: unchanged.

## R5 runtime findings carried into R6

R5's one-shot runtime report remains FAIL 13/17. Its same-cookie submit and decision POST init diagnostics established an authenticated Teacher session (`logged_in=1`, one role), but the queue GET returned 302/login. Debugger found R5 sent only `LOGGED_IN_COOKIE`; WordPress admin screens also require the admin `AUTH_COOKIE`. R5 also preserved its diagnostics suffix and failed cleanup because the decision POST adds a second `ZMP test request reached WordPress init.` event before the `President subject decision denied.` event.

## Scoped R6 changes

- The same synthetic session token now generates both `AUTH_COOKIE` and `LOGGED_IN_COOKIE`, following the existing `phase65-profile-authenticated-http.php` pattern. Both cookies are sent together on the positive Teacher submit, direct queue GET, and nonce-valid decision POST.
- The observed ordered diagnostics suffix is now exactly four events: authenticated Teacher submit init, submit success, authenticated President-decision init, and President decision denied. Both init events require `logged_in=1`, one role, expected action/path, and allowlisted context. The raw suffix hash, unchanged prefix, conditional raw-value/autoload restoration, cache invalidation, and exact readback remain fail-closed. Unexpected or changed diagnostics stay untouched and make cleanup fail.
- WP-Cron/update-core preflight, outbound guard, route status/disclosure/no-mutation assertions, and exact synthetic cleanup are otherwise carried forward from R5.

Do not run R6 until an independent reviewer passes the exact candidate hash above. Preserve R5's runtime outcome and diagnostics evidence; R6 is a new diagnostic candidate, not an acceptance result.
