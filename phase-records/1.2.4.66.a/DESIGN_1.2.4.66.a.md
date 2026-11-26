# 🚨 CURRENT OWNER STATUS — 2026-11-26 — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

**CURRENT .66.a CHECKLIST: ITEMS 1–7 PASS. FRESH ZIP, PHASE HASH CHECK, RESTRICTIVE LICENSE, AND GITHUB SYNC/READBACK ARE COMPLETE. OWNER INSTALLATION/TEST OF THE ZIP IS THE NEXT USER-SIDE STEP. NEWER SAME-SCOPE PASS EVIDENCE SUPERSEDES EVERY OLDER NOT RUN/FAIL/OPEN STATEMENT BELOW. AUTHORITATIVE STATUS: `reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`.**

## REQUIRED RULE — READ BEFORE CONTINUING ANY PHASE

**EVERY ZMP MEMBER ACCOUNT MUST HAVE THE WORDPRESS `subscriber` ROLE.**

**`zmp_teacher` IS OPTIONAL FOR A MEMBER, BUT ANY MEMBER MAY ALSO HAVE IT. IT MUST WORK TOGETHER WITH `subscriber` AND WITH OTHER VALID ZMP ROLES WITHOUT BREAKING THE PLUGIN.**

- `subscriber` is the required baseline membership role.
- `zmp_teacher` is additive, not a replacement for `subscriber`, and is not required for every member.
- Members may also hold any authorized combination of `zmp_moderator`, `zmp_commission`, `zmp_testator`, `zmp_organizer`, and `zmp_president`. Role assignment/removal must preserve the other existing roles.
- The President must always retain both `subscriber` and `zmp_teacher`, in addition to `zmp_president` and any other authorized roles.
- An external Commission person who is not a ZMP member may lack `subscriber` and `zmp_teacher`; if that person is a ZMP member, `subscriber` is required and `zmp_teacher` remains optional.
- Teacher-only functions (school profile, teacher directory, teacher subject proposals, and other explicitly teacher-scoped features) require `zmp_teacher`. Missing `zmp_teacher` must not break unrelated plugin functions for a valid Subscriber member.
- Presence of `zmp_teacher` must not make a valid role stack fail, hide unrelated plugin features, replace roles, or crash the plugin. Authorization must check the capability/role needed for each operation rather than requiring an exact one-role array.
- Test fixtures for a ZMP member must include `subscriber`. Teacher-positive fixtures must include both `subscriber` and `zmp_teacher`; negative Teacher-scope fixtures may omit `zmp_teacher` while retaining `subscriber`.

**THIS OWNER POLICY, RECORDED 2026-11-25, SUPERSEDES ANY CONFLICTING HISTORICAL WORDING IN ROADMAPS, PLANS, REPORTS, TEST NOTES, OR PHASE COPIES. HISTORICAL PASS/FAIL RESULTS KEEP THEIR ORIGINAL SCOPE AND MUST NOT BE EXPANDED TO COVER ROLE COMBINATIONS THEY DID NOT TEST.**

---

# Preliminary design — ZMP 1.2.4.66.a

Status: **DESIGN CONFIRMED FOR THE FIRST FLOW / storage and registration-management implementation present; login and step-up remain unimplemented**  
Roadmap: `.66.a` — design and first passkey/step-up path.

## Intent
Introduce passkey registration, passkey login and a step-up check for one privileged operation while preserving WordPress identity, existing authorization boundaries and competition behavior. WebAuthn proves control of an authenticator; it does not itself grant a ZMP role or sign the decision.

## Current system and constraints
- The `.65` code uses WordPress sessions, capabilities/roles, explicit role/mandate checks, route nonces and server-side resource checks. See its jury, registration, document review and President governance classes.
- No WebAuthn implementation or Composer manifest was found in the `.65` source inventory.
- The local WSL site is a synthetic development target. Owner identified UNET as registrar, DreamHost as host, and WordPress 7.1.4; Owner-reported HTTPS, REST, WP-Cron and ES256/P-256 self-test results are recorded in the phase report.
- Current read-only WSL CLI check reports PHP 8.5.4 with JSON/OpenSSL/Sodium/mbstring loaded; the active WordPress web-SAPI PHP and SQL versions are pending from Owner.

## Trust boundaries and threat model (draft)
1. **Browser/authenticator:** may be lost, unavailable, shared, or subject to compromised browser extensions. WebAuthn private keys remain in the authenticator/platform; the website never receives them.
2. **Web origin and RP ID:** must be fixed to an explicitly allowlisted HTTPS origin/RP ID. Development HTTP is allowed only for localhost. Never derive trust from an unvalidated Host header or accept arbitrary origins.
3. **WordPress account/session:** identifies the user after login. A passkey must map to one immutable WordPress user ID; roles and active mandates are rechecked at every operation and are never frozen into a credential as authority.
4. **Plugin/PHP process:** generates unpredictable single-use challenges and validates client data, origin, RP ID hash, ceremony type, user verification policy and signature before creating a WordPress session or marking step-up complete.
5. **Database/backups:** may be read or modified by a database or hosting administrator. Store credential IDs/public keys and bounded metadata only; never store authenticator private keys, challenge plaintext in ordinary options, or recovery secrets in logs. Challenge state must expire and be single-use.
6. **Recovery/support:** loss of all authenticators must not become a silent MFA bypass. Recovery must use a separately approved, auditable process with delay/notice and independent checkpoints; no production email or real-user recovery is part of local tests.

Threats to cover: session theft, CSRF, account/credential misbinding, credential replacement by a stolen session, cross-origin ceremony, challenge replay/expiry, duplicate submission, user/role changes after enrollment, unavailable authenticator, database disclosure/tampering, clock/cache behavior, and lockout/recovery abuse.

## Proposed approach (Engineering Lead recommendation)
Use the existing WordPress account as the identity anchor and a maintained PHP WebAuthn relying-party library for protocol parsing and cryptographic verification; do not hand-roll signature/CBOR verification. Keep the WebAuthn client code limited to `navigator.credentials` ceremony calls. Store credential public material in a dedicated versioned table with unique credential ID and user ID; store only a hash of each random one-use challenge plus user/session/action/expiry binding in dedicated short-lived state. Recheck WordPress capability, active mandate, resource scope and nonce on every protected request.

Registration is available only to the currently authenticated account owner with the exact `zmp_president` role and an active mandate, after verifying that account's current password. Registration options and finish both recheck the current role and active mandate. The credential record binds to the exact WordPress user ID; it does not snapshot role authority. Current roles/capabilities/mandates are checked again on every protected operation, so a passkey does not grant ZMP authorization. Credential addition/removal is audited. Removal is limited to the signed-in credential owner after current-password reauthentication and a dedicated valid nonce; it remains available if the owner is later demoted or loses the mandate. Passkey login uses a separate challenge and must not reveal whether a username exists. Existing password login remains a recovery-compatible route until an approved replacement policy exists. No external identity provider or real email dependency is assumed.

Candidate first step-up flow: active President approves or rejects a synthetic Teacher subject proposal, because it is a privileged existing route with an explicit active-mandate boundary. The Owner approved this as the first `.66.a` step-up flow on 2026-11-05. WebAuthn step-up will only confirm recent authenticator control; the existing server authorization and nonce remain mandatory, and decision signing stays out of `.66.a`.

## Alternatives considered
- **Self-hosted WordPress/WebAuthn (recommended):** reuses current WordPress account and route model; requires a maintained PHP dependency and careful local/production compatibility evidence.
- **External identity provider/passkey service:** shifts authentication/recovery operations to a provider but introduces a new account-linking, availability and contractual dependency not present in the current roadmap evidence.
- **Custom cryptographic implementation:** rejected because parsing/verifying WebAuthn ceremonies manually creates avoidable protocol risk.

## Dependency candidate and local CLI evidence
The isolated `vendor/webauthn` subtree contains `web-auth/webauthn-lib` 5.3.9 and its locked transitive dependencies (32 packages); the library is MIT-licensed and requires PHP >= 8.2, `ext-json`, and `ext-openssl`. The local synthetic WordPress web runtime reports PHP 8.5.4 with JSON/OpenSSL/Sodium/mbstring loaded, and the focused storage/registration behavior checks passed there. Owner-reported hosting results are recorded in the phase report. The plugin has no prior `Requires PHP` header; its WebAuthn autoloader is guarded below PHP 8.2 so unrelated plugin startup does not attempt to load that dependency. Passkey operations fail closed if the library is unavailable.

## Hosting information provided by Owner
Owner-reported HTTPS, REST, WP-Cron and ES256/P-256 self-test results are recorded as PASS. PHP and SQL version details are pending from Owner.

## Standards and library references
- W3C Web Authentication Level 3: https://www.w3.org/TR/webauthn-3/ (HTTPS/origin/RP ID constraints, server-generated fresh challenges, exact origin validation).
- WordPress Transients API: https://developer.wordpress.org/apis/transients/ (transients may be backed by `wp_options` and may expire early; therefore they are not a reliable challenge ledger for this design).
- Reviewed candidate PHP library: `web-auth/webauthn-lib` 5.3.9. Official package metadata and release: https://github.com/web-auth/webauthn-lib/blob/5.3.x/composer.json and https://github.com/web-auth/webauthn-framework/releases/tag/5.3.9. It is MIT licensed and requires PHP >= 8.2, `ext-json`, `ext-openssl`; its transitive dependency set and WordPress integration still require exact review. No dependency installed.

## Non-goals
No decision signature, no changes to competition scoring, no passkey private-key escrow, no production deployment, no real-user migration, no change to existing password login policy, and no claim that WebAuthn supplies authorization.





## Exact first-flow route trace — 2026-11-05 (read-only)

This trace was performed against the `.66.a` source only. No route was invoked and no database or proposal state was changed. It defines the context that future step-up verification must bind; it is not an implementation or test PASS.

- Owner-approved route: active President subject-proposal decision at `admin-post.php?action=zmp_president_subject_decision`; class `ZMP_President_Subject_Approvals::handle_post()` in `includes/class-zmp-president-subject-approvals.php`.
- UI provides `proposal_id`, route nonce, one of `approve_new`, `approve_alias`, or `reject`, and (for alias approval) `canonical_subject_id`. These values together define the decision context; a generic “President authenticated” flag is insufficient.
- Route gate `can_review()` requires an authenticated user, capability `view_zmp_president_dashboard`, exact sole role `zmp_president`, no `manage_options`, and an active mandate. `handle_post()` additionally requires POST, exact action, and valid route nonce; IDs are strictly parsed. The service `ZMP_Mentor_Subject_Proposals::decide()` repeats current-user identity and active-President checks.
- The decision service checks compatible schema, starts a transaction, locks the exact proposal row with `FOR UPDATE`, and requires `pending`. `approve_new` rechecks normalized canonical/alias duplicates before creating a subject. `approve_alias` validates and links the selected canonical subject. All accepted decisions update the proposal and append an audit event inside the transaction; failed writes roll back. This is the existing authorization/write boundary step-up must precede, not replace.
- Required future binding: exact user ID, current WordPress session, origin/RP ID, purpose/ceremony, exact proposal ID, exact decision, canonical subject ID when applicable, short expiry and one-time challenge. On assertion completion and again immediately before commit, recheck active President role/mandate, nonce, proposal still pending and all resource-specific rules. Never reuse an approval for another proposal, decision, or canonical subject. WebAuthn proves authenticator control only; it does not itself sign/authorize the decision.
- Route source hashes read during trace: `class-zmp-president-subject-approvals.php` `720e0692d8534dd261c68457101e574701198b49c233cf98072b2b8cfdd8a16c4`; `class-zmp-mentor-subject-proposals.php` `8dd89aa7aaFe5a7ab1fc6f83a2b65112865842e9354578bb5a1be18061c89f69`; `class-zmp-mentor.php` `897298c52a8317378392245a68a3dcfd09b60e06a35edddb32d0e1433fc03fc2`. Recompute before implementation because hashes are case-insensitive but must match exact files.
- Unresolved design/acceptance: actual WebAuthn challenge-to-action binding and ceremony endpoints remain unimplemented; active-President success plus denial, expiry, replay, changed-decision/resource, mandate-loss, unavailable-authenticator and rollback scenarios remain NOT RUN.

## Additive design correction and migration/rollback procedure — 2026-11-11

This addendum supplements the preliminary design above. Where an earlier status sentence conflicts with this addendum or the current phase ledgers, this dated status reconciliation is authoritative; the earlier wording is retained as history. This is a design procedure, not evidence that migration, rollback, or browser acceptance has run.

### Status and compatibility reconciliation

- The current `.66.a` candidate implements passkey storage/registration management, login, and the active-President subject-proposal step-up path in server-side code. Focused historical functional PASS evidence remains bound to its exact older source hashes; current-candidate functional/browser acceptance and authenticator ceremonies remain open. “Implemented” does not mean phase-accepted.
- Owner-reported target-host data is recorded in `TEST_REPORT_1.2.4.66.a.md`: WordPress 7.1.4, PHP 8.3.36 (`cgi-fcgi`), MySQL-compatible 8.0.41, HTTPS settings and REST/WP-Cron/ES256 self-tests. Those are supplied results, not an independent production-host inspection or deployment test. A distinct earlier synthetic WSL test record reports PHP 8.5.4 `cli-server` and WordPress 7.1.3 at that test checkpoint; do not conflate it with the Owner-reported target hosting values or the current UI display.
- The candidate includes the pinned WebAuthn dependency under `vendor/webauthn/vendor/` (local plugin-bundled dependency). No global Composer installation or production deployment is evidenced. This resolves the earlier ambiguous “No dependency installed” wording without implying production installation.

### Passkey schema migration design

Current source reference: `includes/class-zmp-passkey-store.php` SHA-256 `F9038DE6499B844529118FE98AB8BD32FE30414BD1061BC35650F638308D02DB`; plugin entrypoint SHA-256 `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`. The store schema marker is `zmp_passkey_schema_version = 3`. The migration is additive and separate from the existing competition schema marker `ZMP_SCHEMA_VERSION`.

1. **Before release or migration:** preserve the exact prior plugin artifact and make a verified, access-controlled database backup using the authorized hosting owner’s deployment procedure. First rehearse on the phase-scoped synthetic site/database with synthetic-only records. Record candidate source hashes, database identity and pre-migration structural/integrity baseline. Do not use production SSH or copy real data into local tests.
2. **Install and migrate:** install the exact reviewed candidate through the approved owner-controlled release path. `ZMP_Passkey_Store::init()` schedules `install_schema()` on `plugins_loaded`; it does not replace the core competition schema marker. `install_schema()` uses WordPress `dbDelta()` for the dedicated credential and challenge tables, checks the exact expected structural shape/indexes, and writes the passkey schema marker only after structural validation succeeds. Do not manually pre-set the marker.
3. **Partial migration/failure behavior:** table creation or index changes may partially apply before the structural check. If either table/index set is invalid, or the marker cannot be confirmed, the method returns failure and passkey operations remain unavailable because `schema_is_compatible()` requires both the expected structure and marker. Leave existing WordPress/competition tables and records untouched. Correct the underlying compatible-schema issue and retry through the normal idempotent migration; do not drop/recreate tables or mark the schema current by hand. Verify both tables, expected indexes, marker, database integrity, plugin inventory and passkey routes on the synthetic site before release consideration.
4. **Rollback/disable:** the current candidate has no passkey-table drop in its ordinary deactivation path and no phase-local `uninstall.php`; routine rollback must not delete passkey tables or alter the marker. Revert the plugin files to the preserved prior version using the approved owner-controlled deployment procedure. Passkey login/enrollment/step-up routes then come from the prior version and are unavailable; existing WordPress password login and unrelated ZMP data remain under that prior version. The additive credential/challenge tables and marker remain inert and preserved for a later compatible forward repair. Pending challenges are short-lived (300 seconds) and are not authorization after expiry; never reuse them after rollback.
5. **Recovery boundary:** restore a database backup only when the owner’s diagnosis shows broader database corruption and after explicitly accounting for all unrelated writes since the backup; a full restore can discard those writes. Prefer forward schema repair for this additive feature. Any future credential-data purge/uninstall policy needs separate approved retention and deletion criteria; it is not part of routine rollback.
6. **Rollback verification:** on the disposable synthetic site, verify the prior plugin loads, normal password login and representative unrelated ZMP reads/workflows remain available, passkey routes are unavailable, no credential/challenge row or option is dropped by deactivation/reversion, and database integrity remains valid. Then reinstall the candidate and verify idempotent migration/schema checks. This runtime sequence is required before `.66.a` acceptance and remains **NOT RUN** today.

### Acceptance status

Threat model and exact first-flow design are scoped PASS per the independent design review of the preliminary text. That review returned **SPEC FAIL / QUALITY FAIL** for the preliminary document because this required procedure and status corrections were absent. This additive revision addresses those document findings; it does not retroactively change that review verdict. An independent exact-hash review of this revision is still required. Migration/rollback runtime, current-hash functional checks, enrollment/login/step-up browser ceremonies, Master, Auditor and release gates remain open.


## Current review status reconciliation — 2026-11-11
The additive migration/rollback design correction received scoped exact-hash independent PASS at design hash E78FB709EEAC00A7E6C02DBDA6E98907B82FED1FA49C4F5D6C03E8E1BF62C3A9, report SHA-256 9166BC8628CC99650758BC7A1592DCB720900AD37BC960359147C48A2E5DD4A7. This supersedes only the earlier statement that review of the addendum was pending; the preliminary design SPEC/QUALITY FAIL stays preserved. Migration/partial-failure/rollback runtime remains NOT RUN.

## Migration runtime status — 2026-11-11
- Added independent runtime report `reports/PASSKEY_SCHEMA_MIGRATION_ROLLBACK_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `7E8C21A98B5C7F279665E485E096165CF0AA200A9366FC8D019DFA3F018B1C75`. Current v3 idempotence and exact `.65` rollback/reinstall behavior PASS; fresh pre-passkey schema creation and partial-failure injection NOT RUN. It is partial runtime evidence only.
- Current readiness follow-up `reports/GATE_READINESS_RUNTIME_FOLLOWUP_1.2.4.66.a_2026-11-11.md`, SHA-256 `E697323C98CF552C0344114AF3C40E2B0E4B2FA8E156502FCD97E532F0CDAF48`, keeps overall NOT_READY. No package/GitHub sync or phase acceptance.

## Fresh passkey schema creation evidence — 2026-11-11
- Independent Test Engineer report `reports/PASSKEY_FRESH_SCHEMA_CREATION_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `E7F4FB749950255F3226345A4556537010BF75A8612EF6F0A9C9E6A186413136`: first creation on a separate copied synthetic child DB **PASS**. `.66.a` normal plugin loading created both passkey tables, expected indexes and marker 3; compatibility and DB integrity passed; unrelated synthetic competition data stayed unchanged. The child models missing passkey schema on copied data, not a full historical `.65` database.
- Readiness follow-up `reports/GATE_READINESS_AFTER_FRESH_SCHEMA_1.2.4.66.a_2026-11-11.md`, SHA-256 `F19445728E230492298B94AEFDDBA3577721742936ECFA879B45DBE28CADAB5C`: overall remains NOT_READY. Partial migration failure injection and all browser/Master/Auditor/release gates remain open/NOT RUN.

## Partial migration failure injection attempt — 2026-11-11
- Test Engineer report `reports/PARTIAL_SCHEMA_FAILURE_TEST_ENGINEER_1.2.4.66.a_2026-11-11.md`, SHA-256 `26DC3E28CFAE0CC2262A5092511127C836C3B5687D8044C08CD077F3B5A327B2`: the reviewed exact DDL filter intercepted zero queries. The attempt is **INCONCLUSIVE / NOT PASS**; no broad retry occurred, and it establishes no product defect. The intended fail-closed scenario remains **NOT RUN / unresolved**.
- The injector was removed and the child DB restored/verified (schema marker3, compatible, both tables/indexes, baseline, no passkey rows and integrity PASS). Main clone and Owner site/services remained unchanged. Current gate report `reports/GATE_READINESS_AFTER_PARTIAL_SCHEMA_ATTEMPT_1.2.4.66.a_2026-11-11.md`, SHA-256 `549AFDE977B7906C0DA84BE9BC7BDEBFA5EABAD1335AEF950A4A3DC954339587`, overall NOT_READY.
