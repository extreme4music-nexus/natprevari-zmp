# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Pending document gate for jury finalization — local HTTP synthetic PASS

Date: 2026-11-25 (Europe/Skopje).
Scope: Close the current .66.b pending-document case using the retained local WordPress site and its existing HTTP listener at localhost:8103. No server was started. The test used existing isolated synthetic entry 1 and synthetic commission account 116; no live site or external service was contacted.

Candidate includes/class-zmp-category-jury.php SHA-256: $candidate; includes/class-zmp-document-review.php SHA-256: $review. Runtime copies matched the candidate before execution. Fixture 	ests/pending-document-finalization-gate-runtime.php SHA-256: $test.

Starting state had all required entry documents approved and an existing confirmed synthetic result. The fixture temporarily set the exact prior result to pending_review, changed only one exact document-review row to pending, and restored both afterward. It generated an ephemeral synthetic ES256 credential and valid signed step-up assertion against the product's actual AJAX challenge, without using a hardware/browser authenticator.

PASS evidence:
- With approved documents, the jury panel rendered the finalization form.
- With a required document review pending, documents_ready() returned false and the finalization form was absent.
- A direct HTTP finalization POST with the valid one-use assertion returned HTTP 302 to documents_not_approved.
- The result row remained exactly at its temporary pending_review baseline for the request and the scoped jury-audit rows were unchanged; the POST did not finalize or write an audit event.
- Cleanup restored the exact original result, review row, audit rows, entry, stage settings, user role/session/handle metadata, credential and challenge counts. The fixture reports xact_state_restored=true.
- PHP lint passed and wp db check reported all tables OK.

Run command: wp --path=/home/tonic/zmp-local-sandbox/phase66b-test/site --allow-root eval-file /home/tonic/zmp-local-sandbox/phase66b-test/tmp/pending-document-finalization-gate-runtime.php.

This closes only the current-hash pending-document UI/POST guard for this synthetic entry. Other finalization conditions, roles/resources, full browser authenticator interaction, broad route regression and overall .66.b acceptance remain separate. .66.b remains IN PROGRESS / NOT_READY.
