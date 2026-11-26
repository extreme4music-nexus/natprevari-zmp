# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Subject Register Admin-Post Diagnostic Fixture R10

Date: 2026-11-05  
Reviewer: Independent Code Reviewer  
Verdict: **PASS — exact-hash test-fixture review only**  
Candidate: `tests/phase65-subject-register-admin-post.php`  
SHA-256: `06DD604E63C0357FFCCBC4D57FB4F837BDF0AED171D879508D35669549CC4692`  
Focused note: `reports/TEST_FIXTURE_SUBJECT_INPUT_DIAGNOSTICS_R10_1.2.4.65_2026-11-05.md`  
Focused note SHA-256: `4475EB97A23D8BFF7C371E3C26BCEC97487A62ABC170C3B17EA0802D8AF76AE2`

## Scope and evidence

Read-only review of the assigned R10 candidate and focused note. Verified both expected hashes. Reviewed R10's synthetic phrase/key construction against the product normalizer, variable availability to closures and cleanup, bounded request count, diagnostic privacy/restoration, network guard, and synthetic fixture cleanup. No fixture execution or source edits were performed.

R9 runtime evidence remains a fixture FAIL caused by undefined `$phrase` and `$subject_key`, which produced an invalid-subject response and made fixture lookup/cleanup unreliable. The R10 note reports PHP lint PASS and runtime NOT RUN; lint was not independently rerun here.

## Findings

No concrete correctness or safety finding was identified in R10.

- **Phrase/key parity** — `tests/phase65-subject-register-admin-post.php:99-110` creates a synthetic per-run phrase, applies `sanitize_text_field`, whitespace collapse and trim, `remove_accents`, lowercase with the same `mb_strtolower`/`strtolower` fallback, then a second whitespace collapse and trim before SHA-256. This matches `ZMP_Mentor_Subject_Proposals::normalize_name()` at `includes/class-zmp-mentor-subject-proposals.php:367-376` for this generated string. The key is defined before the fixture lookup and cleanup closures that capture it.
- **Defined closure inputs** — `$phrase` and `$subject_key` are set before `$find_fixture` and before the later route and cleanup code use them (`tests/phase65-subject-register-admin-post.php:99-110, 486-494, 563-578, 653-665`). The subject is synthetic and run-specific; no existing catalog, alias, proposal, or event rows are allowed by preflight.
- **Bounded behavior** — the fixture performs a schema preflight before POST and contains one `$post_route()` invocation. It disables redirect following and projects the response to HTTP code plus the explicit status enum; unknown values become `unrecognized` (`:446-482, 550-564`).
- **Privacy and diagnostics restoration** — diagnostic events are converted to fixed allowlisted tuples, unknown fields/codes fail validation, and only sanitized tuples/stage names are emitted (`:496-536, 601-620, 858-869`). The response body, full Location, cookie, nonce, and raw event context are not retained in fixture output. Restoration requires exact baseline-prefix and expected-suffix validation plus the captured suffix hash, conditionally updates/deletes the exact current row, clears caches, and verifies the original value or absence (`:786-846`). Incomplete or changed diagnostics are preserved and cause cleanup failure.
- **Network and synthetic cleanup** — local POST is constrained to the expected admin-post URL; a second outbound guard permits only loopback port 8097 and blocks other HTTP, while mail is short-circuited (`:291-404, 484-490`). Cleanup is restricted to run-owned rows, users, sessions, guard, and marker, with table-state snapshots used to verify restoration (`:625-780`).

## Disposition and limits

**PASS for the assigned R10 fixture hash and reviewed diagnostic-candidate logic.** This review does not establish runtime behavior, response privacy empirically, or route acceptance. R9's fixture FAIL remains preserved; R10 runtime remains **NOT RUN**. The Teacher submission/President decision route is still not accepted. Preserve prior R3 FAIL and all President photo/consent PASS evidence unchanged. This report does not authorize a phase release.
