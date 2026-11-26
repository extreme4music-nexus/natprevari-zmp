## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# President certificate key-loss acceptance — `.65`

Date: 2026-11-04  
PLAN scope: work package 2 — integrated President/register/privacy review  
Status: **OPEN — source design PASS; authenticated HTTP replacement NOT RUN after browser upload refusal**

## Supported behavior and source review

- Independent Backend Developer reviewed `includes/class-zmp-president-facsimile-certificates.php`, SHA-256 `153B2E4A73214B9B40660F98FF6C217ADBE687417EE950B1889513C37DC3C587`.
- **Source design PASS; no defect found.** Private signing keys are intentionally never stored by the server. An authenticated current President can register a replacement certificate using a newly generated off-server keypair and signed manifest. Successful registration supersedes the prior active certificate while retaining history. Administrator revocation and handover boundaries remain as implemented.
- Existing service-level rotation/revocation evidence remains PASS. It does not exercise the authenticated browser HTTP replacement after key loss.

## Runtime attempt and exact boundary

- Test Engineer performed a bounded read-only preflight on the disposable `.65` WordPress site: synthetic DB integrity, only the candidate plugin active, current mandate, and zero-row certificate/facsimile/event baseline all PASS.
- Authenticated replacement was **NOT RUN**. The Test Engineer's task tab reached the WordPress login form; no credentials or test records were entered. The task tab was closed, DB integrity was rechecked PASS, and no source, database, account, server or unrelated tab was changed.
- This is not a product FAIL and is not a runtime PASS. The route's HTTP response, persisted replacement certificate, superseded certificate state and audit event remain unverified.
- Never describe this as restoring the lost private key. The criterion is fresh-key replacement after the old private key is unavailable.

### Test Engineer runtime attempt — 2026-11-04

- Independent Test Engineer authenticated synthetic current President #26 and reached the certificate workflow using its own task tab. Its documented `waitForEvent("filechooser")` / `setFiles(path)` call was refused because the ChatGPT extension's Chrome profile does not have **Allow access to file URLs** enabled. The exact refusal and evidence are in `reports/TEST_ENGINEER_PRESIDENT_KEY_LOSS_HTTP_1.2.4.65_2026-11-04.md`.
- No application form was submitted. The certificate replacement and all route assertions remain **NOT RUN**; no product defect is inferred. Do not switch to an equivalent upload path or change the extension setting without Owner action.
- Cleanup PASS: original synthetic account hash restored byte-exact; task session logged out and task tab closed; exact synthetic PNG/directory removed; `wp db check` PASS; facsimile/certificate/event counts remain 0/0/0. One residual session token was preserved because its ownership could not be established.

### Lead Chrome handoff checkpoint — 2026-11-04

- Fresh inventory selected Chrome Desktop browser ID `7` for this session; a new task-owned login tab was created directly without a `visible` option. Browser ID is session-specific.
- Owner-authorized temporary synthetic President #26 credentials were generated/set through the local `.65` WSL/WP-CLI path and entered only on `127.0.0.1:8094`. The page authenticated successfully and rendered `/wp-admin/profile.php`. No certificate action or profile submission occurred.
- The task session was signed out through the local WordPress logout route. Original user #26 password hash was restored byte-exact from in-memory capture; DB integrity check PASS. The Lead-owned tab was closed and verified absent; other task/Owner tabs were left untouched.
- This is a successful Chrome/login handoff, not certificate-route acceptance. The Test Engineer has a separate task-owned local login tab and explicit authorization to continue the one bounded route scenario. Do not duplicate it.

## Browser handoff for the next bounded run

Use the verified workflow in `reports/LOCAL_CHROME_TASK_TAB_RECOVERY_1.2.4.65_2026-11-04.md`: refresh Chrome inventory with `cua.getState()`, select the currently listed desktop Chrome browser ID, create a new task tab at `http://127.0.0.1:8094/wp-login.php` without a `visible` option, and bind by exact tab ID plus browser ID. Browser IDs and tab IDs are session-specific; never reuse IDs from this report without fresh inventory. The Owner authorizes creating and entering temporary synthetic credentials solely on this local disposable site. Keep them in memory, do not save them in Chrome, and do not include them in reports or logs. Close only the task-created tab and verify it is absent after the run.

For acceptance, use the already-preflighted site and the Test Engineer's owned tab. Authenticate synthetic active President #26, create a fresh Ed25519 keypair outside the site, register through the actual UI/admin-post route, then verify HTTP outcome, active/superseded certificate records and matching audit event. The private key must never be sent to or stored by WordPress. Discard it after verification. Exact cleanup must restore the account's original credential/session state and the zero-row fixture baseline; run the existing DB integrity check. Any synthetic rows/files must carry a unique run tag and be removed only after the completed request state is unambiguous.

Do not repeat the 23/23 lifecycle service fixture, Audio UI checks, R1–R3 subject routes, or unrelated President download scenario. Preserve those results.

## Evidence index

- Source reviewer: completed independent Backend Developer findings summarized above; source and hash are recorded here.
- Runtime preflight: bounded Test Engineer report delivered to the Lead for this checkpoint; authenticated route remains NOT RUN.
- Chrome procedure: `reports/LOCAL_CHROME_TASK_TAB_RECOVERY_1.2.4.65_2026-11-04.md`.

`.65` remains **IN PROGRESS / NOT READY FOR AUDITOR**. This scoped source PASS does not close the President lifecycle item or authorize packaging/GitHub/Auditor.

## Superseding runtime and scoped Master update — 2026-11-05

- The earlier `NOT RUN` runtime status above is historical and is superseded by the exact-hash source review and actual President UI route results in `reports/PRESIDENT_CERTIFICATE_ADMINPOST_SCOPE_REVIEW_1.2.4.65_2026-11-05.md` and `reports/TEST_ENGINEER_PRESIDENT_CERTIFICATE_ADMINPOST_1.2.4.65_2026-11-05.md`.
- President #26 submitted inactive facsimile #19 through the visible UI; the `admin-post.php` action now succeeds without elevating the role. A signed certificate A was registered through the visible UI. After its private key was discarded, a distinct-key certificate B was registered through the same UI and became active while A became superseded. Independent Test Engineer read-only verification passed.
- Scoped Master result: **PARTIAL**. The source fix, first upload and certificate registration, and fresh-key certificate replacement are accepted. A second upload after key loss was not exercised through UI because its pending state was prepared by a bounded synthetic DB status transition. Keep only that repeated-upload gap open; preserve all accepted evidence and do not repeat it.
- Current final test DB state is facsimile #19 active, certificate #17 superseded, certificate #18 active, and five audit events; DB integrity PASS. Keep records until no longer needed for remaining `.65` acceptance, then perform exact synthetic cleanup.
- This does not close the broader President lifecycle package or the `.65` phase. No ZIP/GitHub/Actions/whole-phase Master/Auditor.

## Lead runtime continuation — current President session and facsimile upload handoff — 2026-11-05

- Owner manually authenticated the existing Chrome tab as synthetic current President #26 (`Synthetic President 2`); no new tab was opened. The President oversight page rendered the certificate panel and stated no registered certificate for the current mandate.
- Read-only WP-CLI preflight on the disposable site confirmed DB `zmp65_isolated`, active synthetic mandate ID 24 (2026-11-03 through 2026-12-03), and zero facsimile/certificate/certificate-event rows. A first read-only query had a quoting error; its corrected query succeeded. No mutation occurred.
- The real oversight UI was populated with synthetic President name and active-mandate dates. One documented Chrome filechooser `setFiles` attempt was refused because ChatGPT extension access to file URLs is not enabled for the selected Chrome profile. No form was submitted; no database, server-side upload, facsimile, certificate or event changed.
- Generated a clearly marked synthetic 640x220 PNG at root temp path `.phase65-keyloss-run-20261105/synthetic-president-facsimile.png` (SHA-256 `33bb15d6a9e2559c540346ad3d9112ecf0f5f3d9499f37128f8bb7ef75afc906`). It contains only generic synthetic strokes and “SYNTHETIC TEST ONLY — NOT VALID”. The Owner will attach it manually in the existing authenticated tab. Do not use another upload surface; continue the same form after the selected file is visibly confirmed.
- Fresh-key replacement remains **NOT RUN**. Existing source-design and rotation/revocation PASS evidence remains valid. No ZIP, GitHub sync, Master or Auditor. `PROJECT_ROADMAP.md` remains read-only.
