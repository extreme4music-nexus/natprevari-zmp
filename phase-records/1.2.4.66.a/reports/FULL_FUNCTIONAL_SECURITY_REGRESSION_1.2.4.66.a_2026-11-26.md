# 🚨 OWNER RULE — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

# .66.a functional and security regression — 2026-11-26

## Scope and environment

Testing continued on the existing owner-authorized local clone at `http://localhost:8111`, database `zmp_debug_prodclone_20261125`, prefix `wp_yqqdt5_`. No new server was started; no server was stopped; production and YouTube were not accessed. The active plugin remained `.66.a`.

## Result

**PASS for the current .66.a open functional/security scopes on the seven-item checklist.** No product-code defect was found in this regression. One stale test-fixture assumption was corrected; it was a harness compatibility problem, not an application failure. The existing concurrent replay V2 test remains an exact-hash PASS (details below).

### President decision / passkey matrix

`tests/phase66a-president-subject-step-up-matrix.php` completed **38 checks PASS**. The route was exercised end-to-end through WordPress `admin-post.php` on the running clone. Covered valid new-subject approval, valid alias approval, rejection, canonical-target mismatch with no proposal/catalog mutation, bad signature, client-Origin mismatch, RP-ID mismatch, missing user verification, expiry, one-use/replay, changed resource/decision/user/session, lost President role, inactive mandate, invalid nonce, and already-decided proposal/no-write behavior. The three valid decisions returned HTTP 302 with the expected persisted decision and audit event; the negative routes preserved the protected proposal state.

Synthetic users were stacked with the required `subscriber` role and additive `zmp_teacher`; the President retained those roles while President capability/mandate denial cases were exercised. Cleanup restored the pre-test user, credential, proposal, mandate, catalog, alias, debug-event, and role baselines. The protected credential was unchanged. `wp db check` passed. Private keys, assertions, cookies, and nonces were not printed.

Fixture corrections were confined to the phase test file: the old `127.0.0.1:8101` expectation now follows the exact authorized `localhost:8111` clone; the admin actor lookup uses the clone's seeded synthetic admin accounts; removing/restoring President capability preserves the member's required Subscriber/Teacher stack. A safe allowlist reports fixture failure reasons without disclosing sensitive data. The initial fixture aborts cleaned up successfully; the corrected run passed.

The matrix itself does not create a nondeterministic concurrent interleaving and explicitly reports that subcase as NOT RUN. The separate concurrent route-replay diagnostic V2 is still a PASS: fixture SHA-256 `E534058B473722EB26E199075900A3A64FDCC96F93341A0BA3DE291391A6D924`, and the current fixture SHA-256 matches exactly. Its recorded runtime test reached the consume boundary with two workers and passed the decision/replay, cleanup, and database-integrity checks (`reports/STEPUP_REPLAY_DIAGNOSTIC_V2_RUNTIME_1.2.4.66.a_2026-11-12.md`). Thus the older PASS applies to the same exact fixture; the matrix's explicit NOT RUN is not concealed or relabeled.

### Related current checks retained

- President/Teacher profile browser Submit → reload → database/UI readback: PASS; stale partial-save defect fixed and retested.
- Partial passkey-schema migration failure and fresh-table failure: PASS/fail-closed.
- Normal expired-challenge cleanup: PASS; expired record removed, live record preserved.
- Browser authenticator Cancel followed by user-entered PIN retry and successful approval: owner-confirmed and locally repeated PASS. PIN entry/wait is expected, not a defect.
- Profile keyboard/focus/selector/reverse-Tab and Narrator interaction: PASS; Narrator success was owner-confirmed.
- Existing role-stack, inherited `.65`, school-email, subject-route, school-profile partial-schema, valid passkey login, login rate/context, issuance churn, global quota, transient eviction, and passkey assertion checks remain PASS for their recorded exact scopes; see the corresponding dated reports and phase test output.
- Current local clone HTTP response: 200. Active plugin reports `.66.a`. Database integrity: PASS.

## Phase-folder and runtime parity

Compared plugin source files in the `.66.a` phase folder with the installed plugin on the existing local clone. All **67 runtime plugin files** have matching phase-folder hashes; no source-code difference was found. The additional 11 root-level phase documents are intentionally not installed into WordPress. The only file changed for this regression is the test fixture under `tests/`; runtime plugin source was not changed.

## Disposition

The full functional/security regression item on the current seven-item checklist is **PASS** for its enumerated `.66.a` scopes. Checklist items **1–7 are PASS**: the fresh ZIP was built from the phase folder, archive integrity and package-to-phase hashes passed, and GitHub content/ref readback passed on `main` and `version/1.2.4.66.a`. The owner may now install and validate this candidate on the site. Root project roadmap was not changed.

## Release notes follow-up — 2026-11-26

After the regression above, the admin dashboard What's New array was updated to include a `.66.a` entry and owner test checklist. This is release-note content; it does not change passkey, authorization, database, or profile behavior. The updated file was copied to the already-running local clone. The logged-in synthetic President account could not open the admin dashboard to verify its rendering, so dashboard visual confirmation remains pending. A refreshed ZIP and source hashes supersede the earlier same-day candidate; the earlier regression results remain scoped to the tested code and behaviors.
