# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — President facsimile/certificate admin-post verification — `.65`

Date: 2026-11-05  
Scope: scoped runtime acceptance of the President-only facsimile proposal and certificate registration actions; not a whole-phase verdict.

## Candidate and code review

- Candidate `includes/class-zmp-president-dashboard.php` SHA-256: `FA16E74B46E33770F61505973E753BB8B6D38125D9F529A06EE71F2DCDA734EB`.
- PHP lint: **PASS**. Source and installed `.65` plugin file hashes matched.
- Independent Code Reviewer: **PASS** for this exact hash. Only the explicit facsimile-submit and certificate-register action constants were added to the President admin-post allowlist. President remains non-admin; current-President, nonce, and manifest-binding checks remain in force.

## Actual local UI behavior and persisted state

- Existing authenticated local Chrome tab was reused; no new tab or credential change. The synthetic President-only account was user #26, active mandate #24. The local disposable WordPress site was `127.0.0.1:8094`, database `zmp65_isolated`.
- President submitted inactive facsimile proposal #19 through the rendered UI. The visible page showed it as pending and provided the matching registration context. Database row bound President 26 / mandate 24 to the synthetic image SHA-256 `33bb15d6a9e2559c540346ad3d9112ecf0f5f3d9499f37128f8bb7ef75afc906`.
- A signed Ed25519 certificate was submitted through the rendered President form. UI showed **Active** and the registration success notice. The database showed certificate #17 (`ZMP65-KL-20261105-A`) active, plus `registered` and `facsimile_activated` events.
- The first private key was generated in a one-shot local PHP process, never emitted or written, and discarded. To expose re-registration for a fresh-key replacement without uploading a second image, the synthetic test row #19 was moved `active` → `pending` with a bounded direct DB fixture update. This was test setup, not a user-facing state transition, and is a scope limitation: a second file-upload flow was not tested.
- Certificate #18 (`ZMP65-KL-20261105-B`) was then submitted through the actual President UI using a second, distinct Ed25519 public key. The visible page showed B **Active** with a success notice. The independent read-only follow-up verified database integrity and final state: facsimile #19 active; certificate #17/A superseded; certificate #18/B active; both bind user 26, mandate 24, and the same image hash. Audit events record A registered/activated, A superseded, and B registered/activated. Key IDs differ.

## Disposition

**PASS** for the focused real UI/admin-post path, certificate registration and fresh-key replacement after the first private key was discarded. Independent read-only Test Engineer database verification: **PASS**. The source-scope Code Review is **PASS**. The reviewer did not operate Chrome or mutate data.

The disposable test site currently retains synthetic facsimile #19 and certificates/events #17–18 / #45–49 for subsequent phase acceptance; exact cleanup and DB integrity verification remain part of phase cleanup. No product database or production site was touched. No ZIP, GitHub synchronization, whole-phase Master verdict, or Auditor was run by this scoped test.
