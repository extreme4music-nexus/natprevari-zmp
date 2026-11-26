# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Task 5 President Subject Step-up Matrix — Round 1

**Date:** 2026-11-06  
**Verdict:** **SPEC FAIL / QUALITY FAIL — fixture static review**  
**Scope:** Read-only review of the corrected matrix fixture only. No runtime, DB/site/browser activity, or source/test edits. Product acceptance is not assessed.

## Exact reviewed artifact

- `tests/phase66a-president-subject-step-up-matrix.php` SHA-256: `2A4645C17DF2CC416CEC1A73A494947CD92E2B56A0F27F0C5C7C9FC1EA5D7955` (recomputed; matches assigned hash).
- Related preparation record consulted: `reports/PRESIDENT_SUBJECT_STEPUP_MATRIX_TEST_ENGINEER_1.2.4.66.a.md`, including its latest static-only addendum. This review did not rely on its runtime claims as current fixture evidence.

## Findings

### P2 — Changed-session assertion also changes the bound route nonce

**Location:** `tests/phase66a-president-subject-step-up-matrix.php:357-368`.

The fixture creates a fresh President session and nonce, issues the challenge while `$route_nonce` is the fresh nonce (`360-363`), then restores the original cookies and resets `$route_nonce` to the original-session nonce before calling `verify_step_up()` (`364-367`). Since the options and verify calls both receive the route nonce and the preparation record states that the challenge is bound to that exact nonce, the verification changes two inputs at once: session and nonce. A denial therefore does not isolate session binding; it may be caused only by the nonce mismatch.

**Acceptance condition:** Keep the challenge-bound nonce identical between issue and direct verification while changing only the session binding, or use a route-level design that independently establishes nonce validity and proves the session-binding failure specifically. Assert the issued and verification nonce relationship explicitly without printing nonce values.

### P2 — Alias-target mismatch no-write snapshot omits alias/catalog state

**Location:** `tests/phase66a-president-subject-step-up-matrix.php:296-311`.

The mismatch route captures and compares only `$proposal_snapshot()`, which hashes the proposal row and proposal events. The assertion and emitted outcome therefore establish only that those two data sets stayed unchanged. They do not establish that no alias row or catalog subject was partially written during the failed alias decision.

**Acceptance condition:** Capture and compare the fixture-owned alias rows and relevant catalog state around the mismatch route, or narrow the assertion/report label to the state actually checked and add a separate no-alias-write assertion.

### P2 — Origin header is consistently supplied, but fixture does not assert it matches the decision endpoint origin

**Location:** `tests/phase66a-president-subject-step-up-matrix.php:78-86,145-155`.

`$origin` is canonicalized from `home_url('/')` and the shared `$route()` helper attaches it to every `wp_remote_post()` decision call. The route URL comes from `admin_url('admin-post.php')`; the guard checks fixed loopback host, port, and path, but it does not compare the endpoint scheme/authority to `$origin`. If WordPress Home URL and Site/Admin URL differ, each decision request can carry an Origin that is not the route endpoint's configured origin. The fixture therefore cannot statically establish same-origin configuration for all supported site setups.

**Acceptance condition:** Before requests, assert the normalized `admin_url()` origin equals `$origin` (or derive both the request target and Origin from one verified configured origin), failing closed before any fixture writes on mismatch.

## Checks that passed statically

- Every actual decision POST call found in this fixture invokes the shared `$route()` closure; that closure attaches the `Origin` header. Direct `verify_step_up()` calls are lower-level verifier checks, not decision POSTs.
- `$safe_route_outcome()` emits only integer HTTP status and fixed/allowlisted decision categories (`approved`, `rejected`, `failed`, or constant transport/unknown labels); it never emits the redirect URL or raw response.
- Positive route checks require the expected HTTP 302 and allowlisted outcome, then compare the resulting proposal state, actor, event count, and where relevant canonical subject target/owner. The alias-mismatch case expects the route's 302 `failed` outcome.
- Fixture setup creates a new session token and verifies it exists; cleanup tracks issued session tokens and restores request globals. The current changed-session assertion remains insufficient to attribute denial specifically to the session because of the nonce change described above.
- Cleanup is scoped to captured fixture IDs/keys/hashes and the fixture compares table counts, credential fingerprints, protected credential digest, role/debug snapshots, and current user afterward. No runtime result is inferred.

## Review limits

No test was run. Exact runtime Home URL/Admin URL origin equality, request handling, cleanup outcome, and product route semantics remain unverified by this review. This report is fixture-level review evidence only; it is not product or phase acceptance.
