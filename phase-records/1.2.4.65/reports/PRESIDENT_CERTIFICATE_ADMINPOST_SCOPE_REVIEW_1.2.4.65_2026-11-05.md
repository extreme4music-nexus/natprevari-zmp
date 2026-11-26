# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President facsimile/certificate admin-post scope — `.65`

Date: 2026-11-05  
Scope: the President-only proposal and certificate-registration routes plus fresh-key certificate replacement. This is not a whole-phase verdict.

## Code Review — PASS

- Candidate: `includes/class-zmp-president-dashboard.php`, SHA-256 `FA16E74B46E33770F61505973E753BB8B6D38125D9F529A06EE71F2DCDA734EB`.
- The independent reviewer verified that `admin_screen_policy()` adds only `ZMP_Registration_Requirements::FACSIMILE_SUBMIT_ACTION` and `ZMP_President_Facsimile_Certificates::REGISTER_ACTION` to the explicit allowlist. It does not make the President an administrator, add wildcard access, or remove the existing current-President/nonce/manifest checks.
- PHP lint PASS; source/installed file hash match PASS.

## Runtime and independent verification

- The existing authenticated President #26 session on the disposable local `.65` site was reused. The proposal form submitted successfully through `admin-post.php`; UI and DB confirmed pending facsimile #19, active mandate #24, user #26, and matching synthetic file hash.
- Certificate A was submitted through the real President registration UI and became active. Its private key existed only in a one-shot local process and was discarded without output/storage.
- After key A was discarded, a bounded direct DB fixture update moved only synthetic facsimile #19 from active to pending to expose the re-registration context. Certificate B, signed with a distinct newly generated key, was submitted through the actual UI and became active. This tests the certificate replacement route after private-key loss, but it does not exercise a second facsimile upload through UI.
- Independent Test Engineer read-only verification PASS: database integrity; facsimile #19 active; certificate #17/A superseded and certificate #18/B active; both certificates bound to mandate 24, President 26 and the exact same synthetic image SHA; distinct key IDs; correct registration/activation/supersession events. No data was changed by the Test Engineer.

## Scoped Master review — PARTIAL

- Master Reviewer accepted the source allowlist fix, the first actual facsimile upload, certificate registration and fresh-key certificate replacement evidence.
- Master Reviewer did not accept the complete key-loss lifecycle as fully closed because after key loss the second pending facsimile state was prepared directly in the synthetic DB instead of submitting a second upload through the UI. Preserve this narrow gap as **NOT RUN**; do not repeat the accepted source, first upload, first certificate registration, key replacement, or independent DB verification.
- To close that gap, use the same authenticated tab and manually attach the existing clearly synthetic PNG, submit one new inactive proposal, then register one further fresh key through the visible form. The first new UI upload must be observed before the registration context is used.

## Current environment and limits

- Site: `http://127.0.0.1:8094`; WSL site `/home/tonic/zmp-local-sandbox/phase65-test/site`; DB `zmp65_isolated`, prefix `z65_`.
- Existing Chrome tab `2090199808` is the authenticated synthetic President #26 oversight page. No new tab was created. Current visible result shows certificate B active and the registration success message.
- Final synthetic DB state currently retains one facsimile, two certificates and five certificate audit events for subsequent phase work. Exact cleanup is deferred until these records are no longer needed for `.65`; database integrity PASS. No production data/site, real credential, or private key was used.
- `.65` remains IN PROGRESS / NOT READY FOR whole-phase Master/Auditor. No ZIP, GitHub sync, Actions or Auditor was run for this scoped review.
