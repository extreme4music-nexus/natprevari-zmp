# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Independent Design Review — ZMP 1.2.4.66.a

Date: 2026-11-11  
Scope: Read-only review of `DESIGN_1.2.4.66.a.md` against roadmap `.66.a` criteria and the Owner-approved active-President subject-proposal approve/reject flow. No source, tests, browser, database, or server state changed.

## Identity

- Design SHA-256: `5B6239799583E08F3FD2F60D725A73B6E97D62AEC769EB3074EF9C36DF1F4C40`
- `PROJECT_ROADMAP.md` SHA-256: `6FF57B9AC120E7982C1A0F2CE4E519C531F974ACBDA8A60213562D0627105074`

## Verdict

**SPEC FAIL / QUALITY FAIL — scoped to the current design document.** The threat model and approved first-flow trace are substantively aligned with the roadmap, but the design does not document a usable migration/rollback procedure required by `.66.a` criteria 2 and 4, and it contains material stale/internally contradictory status statements. This verdict is not a source-code or phase verdict.

## Findings

### P2 — Migration and rollback are acceptance requirements but have no design procedure

Roadmap `.66.a` criterion 2 explicitly requires compatibility assessment and documented migration and rollback; criterion 4 requires rollback coverage. The design proposes dedicated versioned credential/challenge tables and says passkey operations fail closed when the library is unavailable, but does not specify migration order/guard, how partial migration behaves, what disabling/rollback changes, or how existing credentials/challenges and unrelated WordPress/plugin data are preserved. The later first-flow trace identifies rollback scenarios as NOT RUN, but does not define the rollback behavior. Add a precise, fail-safe migration/rollback design before treating this criterion as satisfied. Test-plan mentions are not a substitute for the design required by the roadmap.

### P2 — Current status statements contradict phase evidence and the document itself

- Design line 3 says login and step-up remain unimplemented. Current `.66.a` plan and source-review evidence describe server-side login and subject-step-up implementation as present; browser ceremonies and acceptance remain open. The sentence conflates implementation with acceptance and should be corrected.
- Design line 13 says active web-SAPI PHP and SQL versions are pending from Owner, while the Owner-supplied PHP 8.3.36 and MySQL 8.0.41 are already recorded in the active phase test report. Reconcile the design with that supplied evidence; do not infer hosting verification beyond it.
- Design line 38 says the isolated vendor subtree contains `web-auth/webauthn-lib` and 32 locked dependencies; line 46 says “No dependency installed.” Clarify that the candidate is vendored locally while production installation/deployment has not occurred, if that is the intended distinction.

## Criteria checked

- Trust boundaries cover authenticator/browser, origin/RP ID, WordPress session/role/mandate, plugin validation, database/backups, recovery, CSRF, misbinding, replay/expiry, unavailable authenticator, and lockout risks.
- Owner-approved first flow is explicit: active President approves/rejects a synthetic Teacher subject proposal. The route trace records exact decision inputs, existing server-side authorization and transaction boundary, and required binding to user/session/origin/proposal/decision/canonical target/expiry/one-time challenge. It correctly states that WebAuthn proves authenticator control and does not authorize or sign the decision.
- Threat-model and first-flow coverage are **scoped PASS** at the design hash above. They do not compensate for the required migration/rollback design gap or constitute implementation/runtime acceptance.

No implementation, test, UI, runtime, release, or phase acceptance is claimed.
