# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — President post-key-loss facsimile upload and certificate C — `.65`

Date: 2026-11-05  
Role: Independent Test Engineer (read-only verification)  
Scope: Only the remaining post-key-loss second-upload and dependent fresh-certificate gap from the scoped Master PARTIAL.

## Verdict — PASS for this scoped criterion

The Lead exercised the existing authenticated President #26 UI on the authorized local `.65` site: the second synthetic inactive facsimile form was submitted and the visible page returned proposal ID 20; a fresh Ed25519 certificate C was submitted through the visible registration form and the page returned the registration/reissue success message. I did not control or inspect the active Chrome tab. I independently verified the resulting DB state below without modifying it.

## Read-only verification

Target: disposable WordPress site `/home/tonic/zmp-local-sandbox/phase65-test/site`, database `zmp65_isolated`, prefix `z65_`. No production site, account credentials, secret key, manifest bytes or signature were accessed by this verification.

Commands run from Windows PowerShell using WSL WP-CLI:

```text
wsl.exe --cd /home/tonic/zmp-local-sandbox/phase65-test/site -d Ubuntu -- wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site db check
wsl.exe --cd /home/tonic/zmp-local-sandbox/phase65-test/site -d Ubuntu -- wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site db query "SELECT id,president_name,status,mandate_id,president_user_id,sha256,valid_from,valid_to FROM z65_zmp_admin_facsimiles WHERE id IN (19,20) ORDER BY id"
wsl.exe --cd /home/tonic/zmp-local-sandbox/phase65-test/site -d Ubuntu -- wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site db query "SELECT id,mandate_id,president_user_id,serial,public_key_id,facsimile_id,facsimile_sha256,valid_from,valid_until,status,superseded_by FROM z65_zmp_president_facsimile_certificates WHERE id IN (17,18,19) ORDER BY id"
wsl.exe --cd /home/tonic/zmp-local-sandbox/phase65-test/site -d Ubuntu -- wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site db query "SELECT id,certificate_id,event_type,actor_user_id,created_at FROM z65_zmp_president_facsimile_certificate_events ORDER BY id DESC LIMIT 8"
```

Results:

- `wp db check`: **PASS**, all tables including the three relevant facsimile/certificate/event tables returned `OK`.
- Facsimile #19 is now `archived`; new facsimile #20 is `active`, bound to mandate 24 and President #26, with validity `2026-11-05` through `2026-11-12` and SHA-256 `33bb15d6a9e2559c540346ad3d9112ecf0f5f3d9499f37128f8bb7ef75afc906`.
- Certificate #17/A is `superseded`; #18/B is `superseded`; #19/C is `active` and bound to facsimile #20, mandate 24 and President #26. C's public-key ID differs from A and B. Its facsimile hash and validity match proposal #20.
- Events 45–52 show A registration/activation, A supersession and B registration/activation, then B supersession and C registration/activation, all by synthetic President #26. The newest events are #51 (`registered`) and #52 (`facsimile_activated`) for certificate #19/C.
- No `UPDATE`, `INSERT`, `DELETE`, credential change, file operation, or service lifecycle action was performed by this Test Engineer.

## Evidence boundary

The actual browser form submit and success page were observed/reported by the Engineering Lead in this turn; this Test Engineer performed an independent read-only DB integrity, binding, status and audit-event verification. The existing scoped source-review PASS and earlier A/B route PASS remain accepted and were not repeated. This report closes only the one post-key-loss upload/C registration gap; it is not a whole-phase Master review or Auditor verdict. `.65` broader acceptance, release artifacts and other open phase gates remain outside this scope.
