# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Code Review — Teacher Denial of President Subject Routes R5

Date: 2026-11-05  
Reviewer: Independent Code Reviewer  
Verdict: **PASS — exact-hash test-fixture review only**  
Candidate: `tests/phase65-subject-president-route-denial-r5.php`  
SHA-256: `51494D483C0C98D9081CE4C78920AF5F1C7A72FEB3453385AB8799C7C9282246`  
Preparation note SHA-256: `A407A2725C60EC3E242BB61CEB4005D67CB964E2737D89F99B1FEFF4D25E2DC7`

## Scope

Read-only exact-hash review of the R5 diagnostics cleanup correction and retained synthetic Teacher positive-control / President-route denial fixture. Candidate and prep-note hashes match the assigned values. Reviewed authorization cookie/nonce, ordered diagnostic evidence, route/status/privacy assertions, outbound guard, conditional diagnostics restoration, and ownership-bound cleanup. No fixture execution or edits were performed.

## Findings

No correctness or safety finding was identified in the reviewed R5 candidate.

- **R4 diagnostics cleanup finding fixed** — `tests/phase65-subject-president-route-denial-r5.php:395-450` requires either an unchanged captured diagnostics row before any route request, or (after requests) a captured baseline prefix plus the exact ordered three-event suffix, validated by event assertions and the in-memory suffix hash. A present `zmp_debug_capture` row is only compared to its captured value and is never rewritten; unexpected changes fail restoration.
- **Conditional restoration and verification** — `:416-440` updates/deletes `zmp_debug_events` only when the current raw `option_value` and `autoload` match the row just inspected. It clears option/alloptions/notoptions caches and reads back the exact original row or absence. Unknown, incomplete, or changed suffixes are left untouched and make cleanup fail.
- **Authenticated positive control** — `:142-181, 185-216` creates a synthetic Teacher session token and auth cookie, creates both nonces under that Teacher context, and submits the valid Teacher proposal with that same cookie. The assertions require `302/submitted`, a unique owned pending proposal, and the init diagnostic to report `logged_in=1` and exactly one role before the negative route assertions are considered.
- **Same-cookie denials and disclosure** — `:227-261` sends the queue GET and nonce-valid decision POST with the same Teacher cookie. Redirects are disabled. It requires queue `403` with no redirect, checks the response body for the synthetic subject and decision controls, and requires a generic decision `403` that does not contain the synthetic subject. Bodies and full Location values are not emitted; output contains only status and coarse location classes.
- **Diagnostic classification** — `:266-287` requires the exact ordered init, submit, and `teacher_forbidden` events, including the Teacher actor, expected request/action class, `logged_in` and one-role context, and allowlisted context keys. Failure to establish the exact suffix prevents cleanup from claiming restoration success.
- **Outbound guard** — `:108-113` allows only the three expected loopback paths at `127.0.0.1:8097`; other WordPress HTTP requests return a blocking `WP_Error`. Output records only whether the guard blocked an attempt, not a URL or destination.
- **Ownership and state restoration** — `:315-393, 452-458` removes only the exact synthetic pending proposal/submission event and synthetic Teacher, destroys the created session token, restores process cookie/current-user state, verifies captured user and subject-table snapshots, and checks the HTTP guard is removed. Ambiguous or mismatched rows fail cleanup rather than being swept.

## Disposition and limits

**PASS for the exact R5 fixture hash and the R4 diagnostics-cleanup remediation.** Runtime remains **NOT RUN**; this is not route runtime acceptance or product-code approval. Preserve the historical R3 runtime **FAIL** and R4 Code Review **FAIL** as originating evidence; R5 is a new reviewed candidate and does not rewrite either result. President photo/consent PASS evidence remains unchanged. No phase release is authorized.
