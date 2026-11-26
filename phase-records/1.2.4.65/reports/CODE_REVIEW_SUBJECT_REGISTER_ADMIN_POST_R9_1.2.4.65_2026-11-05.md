## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Subject Register Admin-Post Diagnostic Fixture R9

Date: 2026-11-05  
Reviewer: Independent Code Reviewer  
Verdict: **PASS — exact-hash test-fixture review only**  
Candidate: `tests/phase65-subject-register-admin-post.php`  
SHA-256: `23F73B9CA49956E776B559F9D8A3170D7E3BE49787C104F045FFF937190928B0`  
Focused note: `reports/TEST_FIXTURE_FIRST_SUBMIT_DIAGNOSTICS_R9_1.2.4.65_2026-11-05.md`  
Focused note SHA-256: `1C3DAA878E99D8AC4A2ABE7F20FAD0AE65B64C5F4FFC71B16F94E01EA9255721`

## Scope and evidence

Read-only review of the exact R9 fixture hash and its focused note. Reviewed the first-submit schema preflight, bounded route request, status/diagnostic projections, outbound guard, conditional diagnostics cleanup, and synthetic data cleanup. The candidate hash and focused-note hash match the assigned values. The fixture was not run and no product or test source was edited.

R8 evidence was also reviewed: the exact R8 fixture received one HTTP 302 but failed route acceptance and cleanup assertions; its outbound guard detected and blocked a non-allowlisted HTTP attempt. R8 did not establish route behavior PASS. The R9 note records PHP lint PASS, runtime NOT RUN; this review did not independently rerun lint.

## Findings

No correctness or safety finding was identified in the reviewed R9 diagnostic changes.

- **Preflight and request bound** — `tests/phase65-subject-register-admin-post.php:538-551` calls `schema_is_compatible()` and aborts with a fixed stage code if unavailable/incompatible before setting `fixture_started` and before the sole `$post_route()` call. A search found exactly one invocation of `$post_route()`. The preflight is before the POST; synthetic fixture setup occurs earlier.
- **Status and response handling** — `:434-470` sends only to the generated local admin-post endpoint, does not follow redirects, stores only the numeric HTTP response and an explicit allowlisted `zmp_subject_status` enum, and maps other values to `unrecognized`. It does not retain or emit response body, full Location, cookie, or nonce. Transport errors are reduced to an allowlisted code or `unclassified`; raw error text is not printed.
- **Diagnostic privacy** — `:480-524` maps route events to fixed `(message, category, error_code)` tuples. It validates event shape and allowlisted context keys, but output is restricted to those tuples; unrecognized values become `unclassified`. The diagnostics suffix is hashed in memory for ownership comparison; raw events, context, subject text, and identifiers are not emitted. Route-stage output uses fixed labels.
- **Outbound guard** — the fixture's `pre_http_request` filters allow only the exact generated local admin-post URL with POST; the temporary MU guard independently permits only loopback `127.0.0.1:8097/wp-admin/admin-post.php` and otherwise returns a blocking `WP_Error` (`:291-394`). Mail attempts are short-circuited and marked. A triggered or unowned marker causes cleanup/no-outbound acceptance to fail rather than being treated as success.
- **Diagnostics restore** — `:772-835` restores only if no request began and the option is exactly at baseline, or after a response when the captured baseline is an exact prefix and the suffix is the expected event sequence, valid under the allowlist, and matches the in-memory suffix hash. The update/delete is conditional on the exact current raw value and `autoload`; caches are cleared and the original row/absence is verified. Incomplete, unknown, changed, or ambiguous states are left untouched and cleanup fails closed.
- **Other cleanup** — `:625-770` removes only matching fixture-owned proposal/event/canonical rows and session tokens, verifies table snapshots and captured user state, removes the guard by identity/content ownership, and removes only the validated run marker. Any uncertain canonical row or state change turns cleanup into failure.
- **SQL and data scope** — table identifiers are fixed from WordPress/plugin table names and validated before snapshot interpolation; row values use `$wpdb->prepare()` or `$wpdb` APIs. The preflight is bound to the synthetic DB/prefix and loopback site URL, and requires a fresh site with only one administrator and empty feature tables (`:7-88`).

## Disposition and limits

**PASS for the assigned R9 fixture hash as a privacy-safe, bounded first-submit diagnostic candidate.** This does not establish runtime behavior, response privacy empirically, route acceptance, or whole-phase acceptance. R8's runtime remains **FAIL for route acceptance** with its outbound-attempt and cleanup evidence preserved; the route remains unverified. R9 runtime is **NOT RUN**. No phase release or product-code acceptance is implied. Preserve the prior President photo/consent PASS evidence unchanged.
