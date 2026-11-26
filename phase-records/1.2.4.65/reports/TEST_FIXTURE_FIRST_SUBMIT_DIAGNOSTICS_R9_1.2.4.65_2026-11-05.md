## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# R9 fixture diagnostic candidate — first Teacher submit

**Status: candidate prepared; runtime NOT RUN, pending independent exact-hash review and Lead assessment.**

R8 established that the first Teacher `admin-post.php` request returned HTTP 302, but its redirect status was discarded. The option contained two appended diagnostics and no proposal row. R9 narrows the fixture to that first Teacher submit so it can distinguish a schema precondition failure from a route-level outcome without following the President steps.

Only `tests/phase65-subject-register-admin-post.php` changed. Before any POST, it requires `ZMP_Mentor_Subject_Proposals::schema_is_compatible()` and reports only a fixed `stage_schema_preflight_*` code on failure. The fixture performs at most one POST. Its retained response projection is only the HTTP status code and `zmp_subject_status` mapped to the explicit enum (`submitted`, `forbidden`, `invalid_subject`, `schema_not_ready`, `proposal_pending`, `proposal_already_decided`, `database_error`, or `unrecognized`). It does not retain response body, full Location, cookie, or nonce.

After that request, the fixture records only allowlisted `(message, category, error_code)` diagnostic tuples. Unknown values become `unclassified`; no event text outside the fixed allowlist is emitted. It reports fixed route-substep names, and reports `mail` or `network` marker kind only after validating the fixture-owned marker. Outbound requests remain blocked. Diagnostics cleanup now derives the expected suffix from the completed first-submit status, requires an exact unchanged baseline prefix and exact expected tuple sequence, hashes the full suffix in memory, rechecks that suffix before cleanup, then conditionally restores the exact original option row or conditionally deletes an originally absent row. Unknown, incomplete, or changed suffixes remain untouched. R8's conditional database/cache/readback restoration safeguards are retained. Response-body privacy inspection is not part of this diagnostic candidate because it would require reading/retaining the response text.

- Candidate SHA-256: `23F73B9CA49956E776B559F9D8A3170D7E3BE49787C104F045FFF937190928B0`
- PHP lint: **PASS** — `php -l` reported no syntax errors.
- Runtime: **NOT RUN**. No site, database, service, or active 8094 state was touched for this preparation.
- Product files and gate ledger: unchanged. Preserve R3 FAIL and all President photo/consent PASS evidence.
