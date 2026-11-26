# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 HISTORICAL CHECKPOINT — 2026-11-12. CURRENT SAME-SCOPE STATUS IS RECONCILED IN PROJECT_STATE_1.2.4.66.a.md AND reports/INHERITED_65_FIXES_AND_66A_OPEN_SCOPE_1.2.4.66.a_2026-11-25.md. President setup is now 10/10 PASS; partial-schema injection is 4/4 PASS; nonce-backed profile handler render/save/readback is 10/10 PASS. This dated snapshot preserves its historical outcomes and does not override those later results.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Current gate readiness — ZMP 1.2.4.66.a

Date: 2026-11-12 (Europe/Skopje)

This is an Engineering Lead readiness reconciliation, not Master/Auditor acceptance. It preserves the original `.66.a` scope and Owner-confirmed evidence.

- **Phase:** `.66.a` — **IN PROGRESS / NOT_READY**
- **Source reference:** 67-path candidate manifest `reports/SOURCE_HASHES_CURRENT_AFTER_EMPTY_PHONE_FIX_1.2.4.66.a_2026-11-12.txt`, SHA-256 `D17BA0A14462A5291D1FD32B50765799118A543DAB08BD7F54C2FB8AE7720D71`
- **Candidate artifacts:** verified candidate ZIP and GitHub candidate branch; not released or phase-accepted. ZIP SHA `9875FC6255D2CA17C76BB33E8F55544747BE5D0B7F6BFD8D75AD359A06CADED8`; GitHub branch `version/1.2.4.66.a`, commit `067059be2655109d9eb413344e1680bc48e85623`.

## Gate table

| Gate | Status | Evidence and disposition |
|---|---|---|
| Trust boundaries / first step-up action | PASS | Scoped design review `reports/` addendum SHA `9166BC8628CC99650758BC7A1592DCB720900AD37BC960359147C48A2E5DD4A7`; Owner approved active-President subject proposal approve/reject. |
| Owner-supplied runtime compatibility | PASS | Owner-reported HTTPS/REST/WP-Cron/ES256 checks and PHP 8.3.36/MySQL 8.0.41 compatibility are preserved in the dated phase evidence. |
| Passkey registration and management | PASS | Current backend behavior PASS49 in `CURRENT_HASH_FUNCTIONAL_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md` SHA `14154257191DA4A2596FB2CDAD0C0D641C4CDF74B783C15D577308FB2E7A54E5`; Owner-confirmed browser registration PASS in `OWNER_VERIFIED_PASSKEY_BROWSER_ACCEPTANCE_1.2.4.66.a_2026-11-12.md` SHA `87522A49B8617C60E05270FEC721C477FD7A1513A7A744C8E7E1E338BFFA402A`. |
| Passkey login | PASS | Backend login PASS17 in the current-hash functional report; Owner confirmed a successful repeat login after entering the device PIN. Treat this Owner confirmation as PASS; no separate agent-side PIN evidence is required. |
| President step-up browser attempt | FAIL — decision not submitted | Existing local Edge page displays “Проверката со passkey не може да се заврши. Одлуката не е испратена.” and still shows one pending synthetic proposal. The precise failing stage/status is undetermined from the page. No PIN/passkey attempt was made by the agent this turn. Owner-confirmed registration and login remain PASS; no separate PIN evidence requested. |
| Concurrent route replay | PASS, current exact hash | Current fixture SHA `E534058B473722EB26E199075900A3A64FDCC96F93341A0BA3DE291391A6D924`; exact-hash process-supervision review PASS and isolated runtime PASS. Two-worker route/status matrix, exits, proposal event, challenge consumption, credential counter, cleanup/artifact removal, and DB integrity all PASS. Detailed report `reports/STEPUP_REPLAY_DIAGNOSTIC_V2_RUNTIME_1.2.4.66.a_2026-11-12.md`. Earlier `worker_exit` FAIL is retained as historical for its prior candidate. |
| External-supervision preflight | Historical BLOCKED, resolved for current fixture | The earlier environment note remains recorded at report SHA `32857AAFA5BBCB0A86F041B6E71CEE26401E56C5BC44411A5760E12DC48D477B`. Current fixture uses reviewed bounded process-group supervision and completed its isolated run PASS; see the current-hash replay row. |
| Subject approvals mobile layout | SUGGESTION ONLY | UI Reviewer observation at 375×812, SHA `4E3CEFACA0F199E06A7F7D376FECBB06F5C141FDD2C1BBC7021AD0553BCC2D2F`; report indicates controls stack and no horizontal overflow. This is not an acceptance status; actual UI criterion is checked before RTM. |
| President teacher-profile mobile layout | SUGGESTION ONLY | UI Reviewer observation at 375×812, SHA `549B1FB496B38A2BD6D63C89417CCB8F09722E6E79BD1FE92BF7EC26C7D0352F`; rendering only, not saving. This is not an acceptance status; actual UI criterion is checked before RTM. |
| Subject approvals keyboard focus reachability | PASS, Lead-only | Tab reached the subject selector and all three decision buttons; none activated. Report SHA `87AF3724AE1A8268171F0FB36C8E9EC2671A52EBB951579E5F0EBB6ECF40AFE3`. Visible focus styling, selector operation, assistive tech and keyboard submission remain NOT RUN. |
| President teacher-profile service self-read/write | PASS, scoped | Isolated-clone Test Engineer report SHA `5989FE5B394DD5FB11D42D2EC049975088ABF816620DA0778A1C4E9083F622AC`: President self-profile read/write passed; another user's read/write was denied; cleanup and DB integrity passed. |
| President first-setup transaction | PASS, exact current hashes | Runtime report SHA `49A9F765F45AD67546096309E340AEB629A9679D1B71565EDC83DFF2FD2E2F0B`; corrected empty-phone readback, owner-scoped setup, collision refusal, rollback, cleanup and DB integrity all passed. Browser form submit/readback remains NOT RUN. |
| President browser form submit/readback | NOT RUN | Desktop/mobile screenshots establish rendering only; no Update Profile submission or browser readback was observed. |
| Schema rollback / fresh schema | PASS, scoped | Rollback/reinstall report SHA `7E8C21A98B5C7F279665E485E096165CF0AA200A9366FC8D019DFA3F018B1C75`; fresh schema report SHA `E7F4FB749950255F3226345A4556537010BF75A8612EF6F0A9C9E6A186413136`. |
| Partial-schema failure injection | NOT RUN | The previous attempt was INCONCLUSIVE because its exact DDL filter matched zero statements. The fail-closed behavior remains unverified; no broadened retry. |
| Expired-challenge cleanup failure path | PASS, scoped | Runtime report SHA `5CB84F082C3C526A8D5713994F632C1AB5664DA1D9564AB41B5AFBDADA2B082A`; fixture `A68FE9220B845EDB333862F15316A62C19698E6B5C33C8319DF0EDD37AC0E30F` and prior exact-hash clone-scope static review PASS `FD12A35B68A921CD1ACC8A2A7C80D9478F54DF047D9F91FED70B95DE691643AB`. One exact SQL interception, generic HTTP 400, no new challenge/session, cleanup and DB integrity PASS. |
| Whole-phase testing, Master review, Auditor decision | NOT RUN | Required phase-wide gates remain open. Existing whole-phase static source review PASS does not replace them. |
| ZIP / verified GitHub sync and readback | CANDIDATE VERIFIED | ZIP structure/file hashes and remote branch/ref readback verified; candidate only, not release or phase acceptance. See package SHA `9875FC6255D2CA17C76BB33E8F55544747BE5D0B7F6BFD8D75AD359A06CADED8` and sync report `reports/GITHUB_SYNC_READBACK_1.2.4.66.a_2026-11-12.md`. |

## Current synthetic environment

- Existing UI site: `/home/tonic/zmp-local-sandbox/phase66a-test/site`, `http://127.0.0.1:8101`, PHP PID 25642, main synthetic DB check PASS. UI still showed one pending synthetic proposal after the keyboard check.
- Isolated replay retest clone: `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site`, database `zmp66a_isolated` / prefix `z66a_`, private MariaDB PID 3828, port 8102 free, DB check PASS and pre-run baseline restored. The existing private clone backup remains inside its sandbox; do not copy it into project artifacts.
- No production access, live-site change, service shutdown, package, or GitHub operation occurred.

## Machine-readable summary

```json
{
  "phase": "1.2.4.66.a",
  "source_ref": "reports/SOURCE_HASHES_CURRENT_AFTER_EMPTY_PHONE_FIX_1.2.4.66.a_2026-11-12.txt#sha256:D17BA0A14462A5291D1FD32B50765799118A543DAB08BD7F54C2FB8AE7720D71",
  "artifact_sha256": null,
  "overall": "NOT_READY",
  "gates": [
    {"id":"design","status":"PASS","evidence":"scoped design addendum SHA 9166BC8628CC99650758BC7A1592DCB720900AD37BC960359147C48A2E5DD4A7","reason":"Owner-approved subject-proposal route."},
    {"id":"registration_management","status":"PASS","evidence":"backend PASS49; Owner browser acceptance SHA 87522A49B8617C60E05270FEC721C477FD7A1513A7A744C8E7E1E338BFFA402A","reason":"Scoped current-hash behavior and Owner-confirmed browser registration."},
    {"id":"login","status":"PASS","evidence":"backend PASS17; Owner confirmed repeat passkey login after device PIN entry","reason":"Owner confirmation is accepted; no separate agent-side PIN proof required."},
    {"id":"step_up_browser","status":"FAIL","evidence":"Existing same-tab Edge UI displays the generic passkey verification failure and the synthetic proposal remains pending; no decision submitted","reason":"The browser flow did not complete; exact failing stage and HTTP result are not available from this page observation. Owner-confirmed passkey registration/login remain PASS."},
    {"id":"concurrent_replay","status":"PASS","evidence":"current fixture SHA E534058B473722EB26E199075900A3A64FDCC96F93341A0BA3DE291391A6D924; runtime report SHA 587EE76054AF6FEC3FB8B97932B264E1C4CDF74B783C15D577308FB2E7A54E5","reason":"Current exact-hash isolated two-worker route matrix, worker exits, event/challenge/counter, cleanup and DB integrity passed; older worker_exit failure is historical."},
    {"id":"concurrent_replay_safe_rerun_preflight","status":"RESOLVED","evidence":"historical preflight SHA 32857AAFA5BBCB0A86F041B6E71CEE26401E56C5BC44411A5760E12DC48D477B; current fixture/runtime report SHA 587EE76054AF6FEC3FB8B97932B264E1C4CDF74B783C15D577308FB2E7A54E5","reason":"The old supervisor availability limitation remains historical; the current fixture uses its reviewed bounded process-group handling and passed its isolated run."},
    {"id":"approvals_mobile_layout","status":"SUGGESTION_ONLY","evidence":"UI Reviewer observation SHA 4E3CEFACA0F199E06A7F7D376FECBB06F5C141FDD2C1BBC7021AD0553BCC2D2F","reason":"Informational rendering suggestion; actual UI criterion checked before RTM."},
    {"id":"teacher_profile_mobile_layout","status":"SUGGESTION_ONLY","evidence":"UI Reviewer observation SHA 549B1FB496B38A2BD6D63C89417CCB8F09722E6E79BD1FE92BF7EC26C7D0352F","reason":"Informational rendering suggestion only; actual UI criterion checked before RTM."},
    {"id":"keyboard_focus_reachability","status":"PASS","evidence":"Lead report SHA 87AF3724AE1A8268171F0FB36C8E9EC2671A52EBB951579E5F0EBB6ECF40AFE3","reason":"Focus reached selector and three actions; no activation; visual focus styling not verified."},
    {"id":"teacher_profile_service_self_read_write","status":"PASS","evidence":"Test Engineer report SHA 5989FE5B394DD5FB11D42D2EC049975088ABF816620DA0778A1C4E9083F622AC","reason":"Isolated-clone President self-profile read/write passed; other-user read/write denied; cleanup and DB integrity passed."},
    {"id":"teacher_profile_first_setup_transaction","status":"PASS","evidence":"exact-hash isolated runtime report SHA 49A9F765F45AD67546096309E340AEB629A9679D1B71565EDC83DFF2FD2E2F0B; fixture SHA 8E311A23572CF2E867FF41FE77D0D9FFCA86CF7A7F602B23F6314F70932AE8E4","reason":"First setup, empty phone, mapping, authorization denial, email collision refusal, forced rollback, cleanup and DB integrity passed; browser submit/readback remains NOT RUN."},
    {"id":"teacher_profile_browser_submit_readback","status":"NOT RUN","evidence":"Owner screenshots and UI reviews show rendering only; Update Profile was not submitted","reason":"No browser form write/readback result."},
    {"id":"migration_partial_failure","status":"NOT RUN","evidence":"prior exact DDL injector attempt was inconclusive","reason":"Fail-closed migration path remains unverified."},
    {"id":"expired_challenge_cleanup_failure_path","status":"PASS","evidence":"runtime SHA 5CB84F082C3C526A8D5713994F632C1AB5664DA1D9564AB41B5AFBDADA2B082A; fixture A68FE9220B845EDB333862F15316A62C19698E6B5C33C8319DF0EDD37AC0E30F; code-review SHA FD12A35B68A921CD1ACC8A2A7C80D9478F54DF047D9F91FED70B95DE691643AB","reason":"Scoped fail-closed error behavior: exact cleanup query intercepted once, generic 400/no new challenge/session, cleanup and DB integrity passed. Normal cleanup timing remains separate."},
    {"id":"master_auditor_release","status":"NOT RUN","evidence":"no phase-wide Master/Auditor/package/sync decision","reason":"Required implementation and acceptance evidence remain open."}
  ]
}
```

Next safe action: continue a separate `.66.a` acceptance item that does not rely on the rejected worker-supervision fixture. Preserve Owner-confirmed passkey registration/login as PASS; do not repeat the PIN ceremony. Keep `.66.a` IN PROGRESS / NOT_READY until all required gates pass.


## Owner ruling ZMP-GOV-2026-11-12-02 — UI Reviewer is non-gating — 2026-11-12
UI Reviewer reports and observations in this file are suggestions only. The role continues checking as before when used, but cannot evaluate or issue PASS/FAIL/BLOCKED/NOT RUN and has no veto/gate authority. If unable to check, record only “UI Reviewer agent failed to check — irrelevant to the project.” Remaining actual UI criteria are checked before RTM. This ruling does not alter functional acceptance criteria owned by other applicable checks or the current phase status: IN PROGRESS / NOT_READY.


## President teacher-profile empty-phone verification fix — 2026-11-12
- Runtime and focused code review reports are recorded separately. Exact support hash A44FFAF243E185ABB32B0E1B4706EF9FEBEE6EC1764710D09ECAF8DE60487D81; exact fixture hash 8E311A23572CF2E867FF41FE77D0D9FFCA86CF7A7F602B23F6314F70932AE8E4. Runtime PASS covers first setup, scoped denial, collision refusal, rollback, cleanup and DB integrity. Owner browser profile save remains NOT RUN.
- The prior guarded invocations and pre-fix empty-phone FAIL remain historical in the test report; current behavior row reflects only the corrected exact-hash run.
- Overall .66.a remains IN PROGRESS / NOT_READY; no ZIP, GitHub sync, Master/Auditor acceptance, or release claim.


## Expired-challenge cleanup failure path — 2026-11-12
The current clone-specific exact-hash fixture ran once after a passing fresh preflight and private backup. It returned fixed generic HTTP 400, created no login challenge/session, restored baseline and passed DB integrity. Runtime report SHA `5CB84F082C3C526A8D5713994F632C1AB5664DA1D9564AB41B5AFBDADA2B082A`; static fixture review SHA `FD12A35B68A921CD1ACC8A2A7C80D9478F54DF047D9F91FED70B95DE691643AB`. Earlier preflight NOT RUN/BLOCKED evidence remains historical. This scoped PASS does not claim normal cleanup timing or overall phase acceptance.



## Owner-directed deadline update — 2026-11-12 21:21 Europe/Skopje
Auditor decision: **NOT ACCEPTED**; .66.a stays **IN PROGRESS / NOT_READY**. Auditor report SHA 84B63D8C190E99A2D87D151BDAC3EF46CE7BA629763F88768DC48E12ABB2ABE6. Candidate ZIP is structurally and hash verified only; SHA 9875FC6255D2CA17C76BB33E8F55544747BE5D0B7F6BFD8D75AD359A06CADED8, 2,222 entries, one root. GitHub sync is still NOT RUN. Step-up browser attempt FAIL/no decision (stage unknown); profile-browser submit/readback and partial-schema injection NOT RUN; whole-phase reconciliation/Master and remaining release gates open. Owner passkey registration/login remain PASS. UI Reviewer is suggestion-only.


## Owner-directed final sprint update — 2026-11-12
Auditor: **NOT ACCEPTED**; `.66.a` remains **IN PROGRESS / NOT_READY**. Verified candidate ZIP and GitHub branch are synchronized, but neither changes the Auditor decision or release status. Remote `.66.a` branch readback matches commit `067059be2655109d9eb413344e1680bc48e85623`; source subtree contains 2,222 files and the ZIP Git blob matches the committed archive. See `GITHUB_SYNC_READBACK_1.2.4.66.a_2026-11-12.md`, SHA `C91D4F869C4D83F89E07BBD840A8D12999812562BFEA8B393798CBD5DF44AB67`. Work has continued in a separate `.66.b` folder per Owner instruction; no `.66.b` product code or tests are complete.
