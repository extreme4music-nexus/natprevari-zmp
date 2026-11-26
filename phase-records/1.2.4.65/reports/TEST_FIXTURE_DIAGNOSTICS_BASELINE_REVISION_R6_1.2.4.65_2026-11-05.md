## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# R6 fixture revision — diagnostics baseline preservation

**Status: fixture-only correction completed; runtime NOT RUN.**

The single R6 runtime attempt was blocked before route assertions because plugin activation created the `zmp_debug_events` option, while the previous fixture required that option to be absent. This was a test-fixture precondition mismatch, not a route result.

Only `tests/phase65-subject-register-admin-post.php` changed. The revised fixture now accepts an absent diagnostics option or an existing row whose value unserializes to an array with capacity for all six expected route events. Cleanup verifies that an existing baseline is an exact array prefix and that exactly six known route events form the suffix. It then conditionally restores the original raw `option_value` and `autoload`, invalidates option caches, and verifies exact readback. For an absent baseline it retains conditional deletion. Any unknown, changed, truncated, or concurrently extended event sequence fails cleanup and is preserved.

- Candidate SHA-256: `9DC8F632FAA57CEF4FA0A8051FA45C94D91A857006E3DA905883EA1A95EE4544`
- PHP lint: **PASS** — `php -l` reported no syntax errors.
- Revised candidate runtime: **NOT RUN**, pending independent exact-hash Code Review.
- Product source: unchanged.
- Gate ledger: unchanged. Preserve the prior R3 FAIL and all President photo/consent PASS evidence.
