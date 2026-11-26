# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Debugger — subject-register route and test-trace follow-up

**Status: candidate instrumentation defect identified; product route behavior remains NOT VERIFIED.** Read-only source and report inspection only; no database/site access, execution, or edits to product code.

The previous revised subject-register candidate stopped after its first local Teacher `admin-post` POST with `per-request diagnostic trace could not be verified`. The run’s acceptance assertions for response, request persistence, President decision, canonical record, and rendered subject visibility were not reached. Its `FAIL 1/3` reflected trace/debug cleanup and baseline-restoration assertions, not a demonstrated product-route defect. Do not relabel this PASS or product FAIL.

Concrete test-only path mismatch: generated MU-plugin code writes the trace under `__DIR__` (which was the plugin/site test folder), but the harness reads and removes it under `WP_CONTENT_DIR/mu-plugins`. The trace sidecar was therefore created outside the expected reader/cleanup location, and exact debug-baseline correlation failed. A future candidate should inject one canonical absolute trace directory into writer, reader and cleanup; validate it is within the disposable clone; and use run-scoped ownership checks. Exact-hash Code Review is required before any next run.

Source review confirms the Teacher submission hook is `admin_post_zmp_submit_subject_proposal`; President decisions use `admin_post_zmp_president_subject_decision` with role/active-President and nonce checks. Source-only inspection cannot establish successful runtime behavior.

Still unverified: Teacher POST redirect and pending proposal/submitted event; anonymous, bad-nonce and non-President denials with unchanged state; active-President `approve_new` atomic canonical/proposal/event records; optional second-Teacher subject visibility; response privacy; exact session/debug/trace cleanup and no-mail/network guards. The intended first-catalog case requires an empty catalog plus fresh synthetic Teacher and active President/mandate in a clean clone. Do not reuse or mutate the active phase database’s ambiguous debug option, session-token record, or trace sidecar.
