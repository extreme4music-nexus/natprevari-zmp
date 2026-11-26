# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Task 5 President Subject Step-up Matrix — Round 2

**Date:** 2026-11-06  
**Verdict:** **SPEC PASS / QUALITY PASS — static fixture review only**  
**Scope:** Exact-hash, read-only review of the corrected matrix fixture, its report addendum, and the specifically assigned setup/cleanup/output criteria. No runtime, database, site, browser, source, or fixture mutation. No product or phase acceptance.

## Exact artifact hashes

- Fixture `tests/phase66a-president-subject-step-up-matrix.php`: `AF0652DAA0FC09F17962BF21350AE523461CDBE3253049C7F08571C7F647EC88` (recomputed; matches assigned hash).
- Related test evidence report `reports/PRESIDENT_SUBJECT_STEPUP_MATRIX_TEST_ENGINEER_1.2.4.66.a.md`: `65E13E73D08179442DD757BF0D2F8063447A0187FD45CAF8043920E263D61822` (recomputed; matches assigned hash).

## Round 1 finding resolution

1. **Alias mismatch no-write evidence — closed.** The fixture snapshots and compares proposal/event rows plus alias rows scoped to the fixture-owned alias key and catalog rows for both canonical target IDs (`:157-168`, `:330-348`). It emits only a boolean for the combined alias/catalog snapshot; no row, name, ID, or digest is printed.
2. **Home/admin origin equality — closed.** Before any fixture writes, the fixture derives the canonical Origin from `home_url()`, derives the decision endpoint from `admin_url()`, and requires matching scheme, host, effective port, and the expected loopback route (`:77-93`). The shared POST helper repeats the authority/port/path checks before sending and attaches that same Origin header (`:169-193`). Every actual decision POST call uses this helper. No configuration mismatch can fall through to fixture setup/network submission.
3. **Changed-session claim — closed and correctly bounded.** The fixture verifies a valid nonce in each of two sessions, issues under the fresh session, restores the original session and its nonce, and labels the expected denial as combined session/route-nonce challenge binding (`:394-406`). The assertion and report explicitly state this does **not** isolate the session hash; it makes no unsupported isolated-session claim.

## Additional assigned checks

- Route results are sanitized: only integer HTTP status, allowlisted decision/status values, event counts and booleans are emitted. Redirect URL and assertion/challenge/credential/cookie/nonce values are not printed (`:195-214`, final output `:513-514`).
- Route assertions match the intended HTTP behavior: successful decisions require HTTP 302 plus their expected status; alias-target mismatch requires 302 with `failed` plus unchanged scoped state (`:283-348`).
- Cleanup targets fixture-owned sessions, challenges, proposal IDs/keys/events, fixture-keyed aliases, returned subject IDs, exact credential and mandate IDs, and fixture-attributed debug events; it restores request/user globals and checks the pre/post synthetic baseline and protected credential fingerprints (`:449-512`).
- No remaining concrete issue found in the assigned fixture criteria.

## Limits

This is static fixture review, not execution evidence. Fixture PHP lint and all runtime behavior remain whatever the separately recorded report states; this review did not rerun or upgrade those results. Origin handling, cleanup behavior, and route state are not runtime-verified here. Concurrent interleaving remains explicitly NOT RUN. No phase acceptance, release, or package approval.
