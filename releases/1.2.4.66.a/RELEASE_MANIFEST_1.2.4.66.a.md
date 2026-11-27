# 🚨 CURRENT TEST CANDIDATE — 2026-11-27 — MESSENGER GLOBAL SCHEMA RETRY ROOT CAUSE

**OWNER DIAGNOSTIC: Messenger tables, columns and indexes are compatible, but the shared schema marker is stale at 1.2.4.53.2 and the global migration retry is pending. This is not evidence of a Messenger table mismatch. The global schema commit is blocked by an unidentified prerequisite; the old stored school-profile/email events are clues only.**

**SOURCE FIX:** stale database markers no longer bypass the five-minute cooldown. The migration commit now logs the first failing install/schema prerequisite or marker persistence step as schema_prerequisite_component. The diagnostic button remains read-only. No database marker was forced current, and no production message/user data was changed.

- ZIP: ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-messenger-root-cause-04.zip
- SHA-256: ab8acc203ba040f088c9ef75b70a066fb9779608af69881aec8db1dd96b34127; size 7,043,241 bytes; runtime files 2,244.
- GitHub source/documentation sync and readback: PASS at ersion/1.2.4.66.a, commit 2d102f95b3526ca24977d5f99d776fe2351e384c; the binary ZIP is local-only and is not present in the GitHub branch.
- Archive integrity: PASS; exact runtime path parity: 2,244/2,244 PASS.
- SHA-256 source/archive inventory: HASHES/SHA256_MESSENGER_ROOT_CAUSE_2026-11-27.txt.
- PHP syntax: 4/4 changed runtime files PASS.
- Existing local :8111 clone: future retry cooldown blocked; expired retry permitted (2/2 PASS); safe prerequisite event retained with school_profile_install (1/1 PASS); schema-control options and diagnostic log restored after checks.
- Live status remains unresolved until this build is installed and a fresh failed migration event identifies the blocker. Synthetic Moderator message exchange is owner-confirmed PASS; retention cleanup is not yet PASS.
- Evidence: 03_PHASES/zmp-competitions-1.2.4.66.a/reports/MESSENGER_LIVE_WARNING_DIAGNOSTIC_1.2.4.66.a_2026-11-27.md and MESSENGER_ROOT_CAUSE_RETRY_FIX_ZIP_BUILD_1.2.4.66.a_2026-11-27.md.

---

# HISTORICAL DIAGNOSTIC CANDIDATE — 2026-11-27 — SUPERSEDED

The previous read-only diagnostic showed stale shared marker and compatible Messenger tables, but did not identify the global migration blocker or fix stale-marker cooldown bypass. It is superseded by the candidate at the top of this manifest.

- ZIP: ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-messenger-diagnostic-03.zip
- SHA-256: 5b248c34e4819760466d82f2a1c7b884428cee57dde88f9c16f6c0a514b71ecd.

---

# HISTORICAL CANDIDATE — 2026-11-27 — MESSENGER SCHEMA AUTO-UPGRADE

**SUPERSEDED FOR LIVE DIAGNOSIS.** The owner installed this candidate and confirmed the warning remains on the live site. Its previous local PASS is not a live-site PASS.

**This ZIP included the .65 → .66.a schema-marker advance and Messenger legacy-index repair. Local :8111 migration and data-preservation checks passed, but the owner installed it on the live site and confirmed the warning remained. It is superseded by the diagnostic candidate at the top of this manifest and is not a live-site PASS.**

- ZIP: ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-messenger-schema-fix.zip
- SHA-256: 5d29342d42b83fee09ad9b628fbb4727a23f7b7b4568901b269510f4864855b2
- Size: 7,091,096 bytes; 2244 files.
- Exact package path parity: 2244/2244 PASS; archive integrity: PASS.
- Evidence: 03_PHASES/zmp-competitions-1.2.4.66.a/reports/MESSENGER_SCHEMA_AUTO_UPGRADE_FIX_1.2.4.66.a_2026-11-27.md and 03_PHASES/zmp-competitions-1.2.4.66.a/reports/MESSENGER_SCHEMA_FIX_ZIP_BUILD_1.2.4.66.a_2026-11-27.md.
- Do not use this historical candidate to investigate the remaining live warning. Install the current diagnostic candidate at the top of this manifest.

---

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE FOR MEMBERS GENERALLY; THE PRESIDENT MUST HAVE BOTH `subscriber` AND `zmp_teacher`. OTHER AUTHORIZED ROLES MAY BE STACKED WITHOUT BREAKING SCHOOL PROFILE FUNCTIONS.**

# HISTORICAL CANDIDATE — 2026-11-27 — SUPERSEDED BY MESSENGER SCHEMA FIX

**Primary school and municipality are editable for linked Teacher/President profiles. Local existing-clone regression: 5/5 PASS. The editable-school-profile ZIP below was the candidate at that checkpoint and has since been superseded by the Messenger schema fix ZIP at the top of this manifest. Full phase acceptance remains separate.**

- ZIP: `ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-editable-school-profile.zip`
- SHA-256: `65e2f59611170897708816103d5691fea20e7dc1b27675ac22fc1ad6ffc73b95`
- Size: 7,039,339 bytes; 2,244 files.
- PHP lint: 1,968/1,968 PASS. Archive integrity and exact staged-source parity: 2,244/2,244 PASS.
- Source-hash list: `HASHES/SOURCE_SHA256_1.2.4.66.a-2026-11-27-editable-school-profile.txt`; ZIP hash: `HASHES/ZIP_SHA256_1.2.4.66.a-2026-11-27-editable-school-profile.txt`.
- Focused runtime report: `03_PHASES/zmp-competitions-1.2.4.66.a/reports/EDITABLE_PRIMARY_SCHOOL_PROFILE_1.2.4.66.a_2026-11-27.md`.
- Build report: `03_PHASES/zmp-competitions-1.2.4.66.a/reports/EDITABLE_PRIMARY_SCHOOL_PROFILE_ZIP_BUILD_1.2.4.66.a_2026-11-27.md`.
- GitHub sync/readback: **PASS** on `main` and `version/1.2.4.66.a`; both refs and the remote ZIP matched at commit `404a7db5f7d0f179b78ca69780dd590da5e4a275`. Report: `phase-records/1.2.4.66.a/reports/GITHUB_SYNC_READBACK_EDITABLE_PRIMARY_SCHOOL_1.2.4.66.a_2026-11-27.md`.
- Historical checkpoint note: owner installation of this superseded editable-school-profile candidate was pending then; the later consolidated `.66.a` candidate was installed by the owner, and its remaining live Messenger warning is recorded at the top of this manifest.

---
# Release manifest — ZMP Competitions 1.2.4.66.a

# HISTORICAL TEST CANDIDATE — 2026-11-27 — PRESIDENT ROLE STACK FIX — SUPERSEDED

**MANDATORY OWNER RULE: every member has `subscriber`; the President also has `zmp_teacher`; Administrator and allowed ZMP roles may be stacked on the same account. If this fails, the entire phase is FAIL regardless of other PASS results.**

- ZIP: `ZIP/zmp-competitions-1.2.4.66.a-2026-11-27-president-role-stack-fix.zip`.
- SHA-256: `23afe3a03b8687383036a10a7508d47ad6c80779023744e11686aa2bc3310ee2`; size 7,113,892 bytes; 2,244 files.
- Package integrity: **PASS**; path count matches stage **2,244/2,244**. Focused role eligibility/list test: **21/21 PASS**; full stacked Administrator appointment **PASS**; role/capability preservation and synthetic cleanup **PASS**; modified PHP source lint PASS.
- This is a focused test ZIP, not a full-phase acceptance claim. A matching `TypeError` was reproduced and fixed in the appointment grant path; owner installation/retry remains pending to confirm the live-site result.
- Evidence: `03_PHASES/zmp-competitions-1.2.4.66.a/reports/PRESIDENT_ADMIN_ROLE_STACK_CORRECTION_1.2.4.66.a_2026-11-27.md`.

## Superseded candidate

The 2026-11-27 `editable-school-profile.zip` and every earlier `.66.a` ZIP predate the Administrator + Subscriber + Teacher role-stack correction. Keep them for history; do not use them to test President appointment.

---

# HISTORICAL TEST CANDIDATE — 2026-11-26

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE FOR MEMBERS GENERALLY; THE PRESIDENT MUST HAVE BOTH `subscriber` AND `zmp_teacher`. THE ROLE-POLICY-FIX ZIP WAS THE INSTALLABLE TEST CANDIDATE AT THAT CHECKPOINT; IT IS NOW SUPERSEDED BY THE MESSENGER SCHEMA FIX ZIP AT THE TOP. ITEMS 1–5 RETAIN EXACT-SCOPE PASS; FOCUSED PRESIDENT APPOINTMENT MATRIX IS 18/18 PASS. FULL CURRENT-SOURCE REGRESSION, GITHUB READBACK, AND OWNER INSTALLATION REMAIN OPEN.**

- Candidate archive: `ZIP/zmp-competitions-1.2.4.66.a-2026-11-26-whats-new.zip` (supersedes the same-day ZIP below).
- ZIP SHA-256: `C01DB41036A72C0372A7AAEB86D67B638E48FDA29F68AA290DF95601C47752C0`; size 7,021,558 bytes; 2,223 entries including the plugin license.
- Package verification: archive integrity **PASS**; all 2,222 plugin files match the `.66.a` phase-folder source **PASS**; the packaged restricted `LICENSE.md` matches the project-root license **PASS**. The dashboard now lists `1.2.4.66.a` with owner-facing feature and test notes.
- Source parity: all 67 runtime plugin files match the active local `.66.a` installation on `localhost:8111`.
- PHP syntax: 89 project PHP files outside `vendor/`, **0 errors**. Local clone database check **PASS**; HTTP **200**; active version `1.2.4.66.a`.
- Current functional/security evidence: `reports/FULL_FUNCTIONAL_SECURITY_REGRESSION_1.2.4.66.a_2026-11-26.md`.
- Source file hashes: `HASHES/SOURCE_SHA256_1.2.4.66.a-2026-11-26-whats-new.txt`.
- Archive hash file: `HASHES/ZIP_SHA256_1.2.4.66.a-2026-11-26-whats-new.txt`.
- License: restricted proprietary terms in the phase root `LICENSE.md`; third-party dependencies under `vendor/` keep their upstream licenses.
- The earlier `ZIP/zmp-competitions-1.2.4.66.a.zip` is preserved as the old 2026-11-12 archive. It is not the current candidate.
- `ZIP/zmp-competitions-1.2.4.66.a-2026-11-26.zip` is preserved as the first same-day candidate; it does not include the new dashboard What's New entry and is superseded by the `-whats-new` archive.
- Historical GitHub readback at that checkpoint: **PASS** on `main` and `version/1.2.4.66.a` at commit `a1e64831979608f9773334768c66799e71383cd9`. The remote has since advanced; this does not represent the current diagnostic source. Current diagnostic synchronization is tracked with this release candidate.


## Supersession notice - 2026-11-26

This manifest's ZIP and full-regression evidence predate the President appointment eligibility/UI correction in `.66.a`. Do not install or call it the current candidate for the updated source. A refreshed ZIP and final regression belong to close-out items 6 and 7.


## Historical role-policy candidate — 2026-11-26 — superseded

- ZIP: ZIP/zmp-competitions-1.2.4.66.a-2026-11-26-role-policy-fix.zip — SHA-256 $sha; 7137152 bytes; 2,244 files.
- Built from the current .66.a phase folder after the President appointment eligibility/UI correction. Archive listing/integrity PASS; exact path-set parity 2,244/2,244; PHP syntax PASS 1,968/1,968; key packaged source hashes match the phase source.
- Focused President eligibility test PASS 18/18. This is an installable owner test candidate, not a claim that the full current-source phase regression is complete. Item 6 and owner installation/visual validation remain open. GitHub readback PASS at commit ed434feabfcf965c0ed873a0eb39eaad892db803; the remote ZIP SHA-256 matches the candidate.
- Detailed evidence: `03_PHASES/zmp-competitions-1.2.4.66.a/reports/PRESIDENT_APPOINTMENT_ROLE_POLICY_ZIP_BUILD_1.2.4.66.a_2026-11-26.md`.
- An intermediate archive named zmp-competitions-1.2.4.66.a-2026-11-26-president-appointment.zip is not the current candidate. That role-policy candidate is superseded; use the current candidate at the top of this manifest.
