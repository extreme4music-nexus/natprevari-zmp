## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# R10 fixture correction — define synthetic Teacher subject input

**Status: test-only correction prepared; runtime NOT RUN pending independent exact-hash review.**

The one-shot R9 runtime returned `zmp_subject_status=invalid_subject`. Subsequent source inspection found `$phrase` and `$subject_key` used by the Teacher POST, fixture lookup, and cleanup without any definitions. The undefined phrase caused the route to receive an empty name; the undefined key also made lookup and cleanup unreliable. R9 remains a fixture FAIL and provides no valid-input route acceptance evidence.

Only `tests/phase65-subject-register-admin-post.php` changed from R9. R10 now creates one bounded synthetic phrase using the existing per-run random suffix before closures/usages, then derives its key with the same sanitization, whitespace normalization, accent removal, lowercase, and SHA-256 steps as `ZMP_Mentor_Subject_Proposals::normalize_name()`. This adds no debug events and changes no product files. The R9 response/status allowlist, one-POST limit, safe diagnostic tuple capture, exact-prefix/suffix restoration guard, and outbound blocker remain unchanged.

- Candidate SHA-256: `06DD604E63C0357FFCCBC4D57FB4F837BDF0AED171D879508D35669549CC4692`
- PHP lint: **PASS** — `php -l` reported no syntax errors.
- Runtime: **NOT RUN**, pending independent exact-hash Code Review and Lead decision.
- Gate ledger: unchanged. Preserve the R9 runtime FAIL, prior R3 FAIL, and all President photo/consent PASS evidence.
