# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer — current-candidate evidence reconciliation — `.66.a`

Date: 2026-11-12. Documentary reconciliation only; no fixture, test, browser, server, or database operation was performed for this report.

## Candidate identity

- Current first-party source manifest: 67 files, SHA-256 `CEA9D405C3C64E686C72CDC1E1008A66B913DD9E2137B886DD2AA470A292C12F` (`reports/CODE_REVIEW_WHOLE_PHASE_CURRENT_1.2.4.66.a_SOURCE_HASHES_2026-11-12.txt`). The referenced Nov 11 functional suites are bound to their own exact source/site hashes; the current Nov 12 manifest has four changed files. Do not treat those suites as a blanket functional PASS for all 67 current files.
- Independent whole-phase static Code Review: scoped PASS/no confirmed findings, report SHA-256 `14FB2B9195689E600AE9AD45B3DA14AD5EA270FD75F835BC4B005882DF05F68F`. It reconciles four changed source files with the Nov 11 review of 63 unchanged files. Static review does not establish runtime/UI acceptance.

## Valid scoped test and Owner evidence

| Area | Status | Evidence and boundary |
|---|---|---|
| Task 3 registration and passkey management | Scoped PASS (49) | Test Engineer report SHA-256 `14154257191DA4A2596FB2CDAD0C0D641C4CDF74B783C15D577308FB2E7A54E5`; synthetic isolated clone and exact tested hashes in that report only. |
| Task 4 valid assertion/login backend | Scoped PASS (17) | Same report/hash; backend assertion/session behavior only, not a browser test. |
| Task 5 subject decision matrix | Scoped PASS (38) | Same report/hash, adjusted fixture; approve-new, alias approval, reject, and alias-target mismatch denial. Does not cover browser authenticator, cancellation/unavailable authenticator, or real concurrency. |
| Login quota regressions | PASS (7, 5, 9, 4) | Current-source regression report SHA-256 `0F8820BA638CD1B9954996C431A7965E907D0F4123A0E74E8F2696F205D1B17E`; context/rate, global quota, issuance churn and transient eviction only. |
| President teacher-profile service authorization | Scoped PASS | Green Test Engineer report SHA-256 `5989FE5B394DD5FB11D42D2EC049975088ABF816620DA0778A1C4E9083F622AC`; self read/write passed and other-user access was denied. Rendered UI is not covered. |
| Existing schema v3 compatibility, rollback/reinstall | Scoped PASS | Report SHA-256 `7E8C21A98B5C7F279665E485E096165CF0AA200A9366FC8D019DFA3F018B1C75`; not a full historical `.65` database migration proof. |
| Fresh passkey schema creation | Scoped PASS | Separate copied synthetic child DB report SHA-256 `E7F4FB749950255F3226345A4556537010BF75A8612EF6F0A9C9E6A186413136`; does not prove partial-failure recovery. |
| Browser registration and passkey sign-in | OWNER-CONFIRMED PASS | Registration report SHA-256 `87522A49B8617C60E05270FEC721C477FD7A1513A7A744C8E7E1E338BFFA402A`; local log corroborates registration finish HTTP 200. The Owner confirms successful passkey sign-in after logout; screenshot report SHA-256 `184B54893AEF96C8188EC984ECC64915E34282FBAE3996EC4929BBB2BD8D1238` and recording report SHA-256 `5C6F3BAC3E11EDAF35AE5A1A81CA65B140090B1B934BAC46BFB7FD66A63371D9` show the authenticated profile afterward. PIN entry/validation is not a separate evidence item. Do not repeat this flow. |
| President proposal queue UI | Scoped PASS | UI report SHA-256 `5CED8CD6D75B245FEACE201BC2EDE1978C823331E91E0F1571E3B251BD25235F`; 375×812 queue layout and normal-page keyboard reachability only. |
| Desktop login/profile-shell visuals | Owner-supplied scoped PASS | Screenshots/recording show login chooser and authenticated profile shell. The passkey-management and teacher-profile sections are not visible; no keyboard/mobile/interactions are established by these images. |

## Open, failed, or inconclusive evidence

| Criterion | Status | Evidence / disposition |
|---|---|---|
| President step-up browser ceremony and authenticator cancellation/unavailability | NOT RUN | Backend matrix PASS38 is scoped; browser proof and fail-closed UI cases remain open. |
| Current President passkey-management and teacher-profile rendered controls | NOT RUN / BLOCKED for automated review | The Chrome UI attempt report SHA-256 `45A50AA8C276171F20BF027C8BF028715337A76F77F4285B6545B47B5DB6DE91` records no page rendered because the supported Chrome connector was unavailable and native Chrome was minimized. Owner screenshots support only what is visibly shown; current controls are not shown. |
| Concurrent President route replay | FAIL / unresolved | Report SHA-256 `950C8430E9D8405A40EC29CD61F1E1B40B291015077560462C2D243CBC28B7C0`. One invocation failed post-handler assertions with unresolved subcause. Diagnostic follow-up SHA-256 `6C89EE99DCB140E0691F8843B445398F2F0F566FD1FFA3818C499AB8007F6400` also failed only `worker_exit`, whose subcause is indeterminate. No unchanged retry. |
| Partial-schema fail-closed injection | INCONCLUSIVE / NOT RUN | Report SHA-256 `26DC3E28CFAE0CC2262A5092511127C836C3B5687D8044C08CD077F3B5A327B2`; exact DDL filter matched zero statements. Observed partial state/restoration checks do not prove the intended injected failure. No broadening/retry. |
| Expired-challenge cleanup failure behavior | NOT RUN | Report SHA-256 `A74FA7E9FEC407189D7DCF22421EC1412A16B1FF11A189ED9A1BED295E0DF72E`; the reviewed clone fixture was not invoked because its final bounded preflight returned nonzero. No retry. |
| Whole-candidate Test Engineer acceptance | INCOMPLETE | This report completes only documentary reconciliation; missing acceptance behaviors remain unexecuted. |
| Master review, Auditor, package, GitHub hash sync/readback | NOT RUN / NOT STARTED | These require complete implementation and acceptance evidence first. |

## Test Engineer disposition

The current evidence is **not adequate for Master/Auditor acceptance**. Preserve all scoped PASS and Owner-verified PASS evidence above; do not re-run successful registration/login or unchanged failed/inconclusive fixtures. The phase remains **IN PROGRESS / NOT_READY**.

Next safe gate: complete the current President profile/passkey-control and teacher-profile visual review from Owner-supplied screenshots when those sections are visible; treat screenshots as visual evidence only. Then run focused functional UI/step-up checks only where a reviewed fixture and safe synthetic preflight exist. Do not infer interaction, authenticator-cancellation, replay, or migration-failure PASS from screenshots or static review.



## Owner authority and status boundary — 2026-11-12
The Owner's explicit confirmation is authoritative for the confirmed passkey events. Current passkey criterion statuses are **registration: OWNER-VERIFIED PASS** and **logout → passkey → successful sign-in: OWNER-CONFIRMED PASS**, supported by the Owner-supplied screenshot/recording evidence referenced above. No log gap or earlier distinct provider-error attempt downgrades these confirmed events. “IN PROGRESS / NOT_READY” in this report applies only to the enclosing `.66.a` phase/release gates; it does not apply to passkey registration or login.

## Owner's final-word rule — 2026-11-12
The Owner's latest explicit confirmation/correction is final for project status. Prior contradictory or withdrawn statements are superseded and non-authoritative for current status; retain them only as clearly marked history and never use them to oppose or reopen the Owner's final disposition. Accordingly, passkey registration and logout → passkey → successful sign-in are **OWNER-VERIFIED PASS**. This criterion status is separate from overall phase gates.

### Owner correction — no separate PIN criterion
The Owner confirms successful passkey sign-in after logout. Record the criterion as **OWNER-CONFIRMED PASS**. The screenshot shows the PIN prompt and the authenticated end state; PIN entry/validation is not a separate evidence item and is not claimed as agent-verified. Current screenshot report SHA-256 `184B54893AEF96C8188EC984ECC64915E34282FBAE3996EC4929BBB2BD8D1238`.
## Additive update — President self-profile setup final exact-hash review — 2026-11-12

- Candidate scope: `includes/class-zmp-support.php` SHA-256 `CFFB2531DEACF4986FF5C6F6E6EFDAED8F08B84C38F00574788B7D247A33F515`; `includes/class-zmp-mentor-subjects.php` SHA-256 `D9D6BA3B5DD56C20E4CBB1E830A44A3210824B18DA3F7C3A9E920CDEEF87C245`; focused fixture `tests/phase66a-president-teacher-profile-self-setup.php` SHA-256 `5C2C588EE752F2038D9835522E90B070706D4F1518C3F8A5A20733402EF10736`.
- Independent final exact-hash static review: **PASS, scoped** to the listed implementation files and setup path; fixture embedded source-hash consistency **PASS**. Report `reports/CODE_REVIEW_PRESIDENT_TEACHER_PROFILE_SELF_SETUP_FINAL_RECHECK_1.2.4.66.a_2026-11-12.md`, SHA-256 `1E1979573ED9372DD3C4FD266BFFCB7DD3A30851A241210A8215F336AE07663A`. The review confirms fail-closed mapping-query errors, transaction-bound writes after InnoDB checks, checked/read-back profile-meta writes, deterministic lock ordering and surfaced acquisition/release errors. Its limits include no runtime verification of concurrent lock interleavings, lock timeout/release failure or non-InnoDB rejection. Earlier SPEC/QUALITY findings remain as history; this final review supersedes only their source findings for these exact hashes.
- Focused setup fixture result: **NOT RUN**; runtime behavior remains **NOT RUN / BLOCKED before writes**. The historical invocations are preserved unchanged: three invocations were blocked before fixture behavior and reported zero writes, including the latest generic exact-source-hash guard block; no further retry was made. These are preflight/guard BLOCKED results, not feature FAIL/PASS. Do not infer functional success, rollback success, or no-partial-write behavior from lint or static review.
- Verification recorded for this candidate: **PHP lint PASS only**. No test, fixture, browser, server, or database operation was performed for this addendum.
- Rendered President teacher-profile UI: **OWNER ACTION REQUIRED**. Existing Owner visuals do not show the self-setup form/result; no UI PASS is claimed. No ZIP or release/package action is authorized or recorded; `.66.a` remains IN PROGRESS / NOT_READY for phase acceptance.

## Additive update — Owner-supplied President profile screenshots — 2026-11-12

- **OWNER-SUPPLIED VISUAL RENDER PASS**, scoped to the sections visible in two screenshots from the existing Edge tab at `localhost:8101/wp-admin/profile.php`, viewport 1440×900: the President page renders its ZMP school/subjects section, active school, and per-school teacher-profile fields and visibility controls. Do not transcribe or retain visible school, contact, or personal values.
- This visual evidence does not show a submit/save success message. It therefore does **not** establish saved data or write behavior. Mobile viewport and keyboard interaction are not shown and remain unverified. No submit/write action was claimed or performed by this reconciliation.
- This updates the visual scope only; the focused self-profile fixture remains **NOT RUN / BLOCKED before writes** following the prior generic guard, with the three blocked invocations and zero-write record preserved. Do not retry unchanged. Static-review and PHP-lint statuses remain as stated above.
