# 🚨 OWNER RULE — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

**SUPERSEDED 2026-11-27: this 2026-11-26 checklist is a historical checkpoint, not the current acceptance. The owner live diagnostic now shows Messenger schema compatible, global marker 1.2.4.53.2 stale, and retry pending. The 2026-11-27 root-cause build fixes stale-marker retry cooldown and records the exact failed prerequisite; live cleanup remains unresolved until that blocker is identified and repaired. See PROJECT_STATE_1.2.4.66.a.md and the current release manifest.**

# .66.a current checklist — 2026-11-26

## Current seven-item checklist

1. President/Teacher profile browser Submit and reload/readback — **PASS**.
2. Partial passkey schema migration failure — **PASS, fail-closed**.
3. Normal expired-challenge cleanup — **PASS**.
4. Cancel/unavailable authenticator followed by owner-entered PIN retry — **PASS**; PIN entry/waiting is expected.
5. Focus, subject selection, reverse Tab, keyboard, and Narrator — **PASS**; Narrator success owner-confirmed.
6. Functional/security regression — **DEFERRED TO FINAL CLOSE-OUT**. The previous full regression PASS predates the 2026-11-26 President appointment role-policy source change and is not a full regression of the current source.
7. Fresh ZIP and current-source hash/package verification — **PASS** (new candidate built after the 2026-11-26 source change); GitHub readback PASS at commit ed434feabfcf965c0ed873a0eb39eaad892db803 on both refs; owner installation remains pending. Whole-phase ZIP acceptance remains part of final close-out after item 6.

### Release-notes follow-up — 2026-11-26

The dashboard's **Што е ново?** list includes a dedicated `.66.a` release entry and owner-facing test checklist. The previously built package `04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a-2026-11-26-whats-new.zip` predates the President appointment correction and is now superseded; do not treat it as the current candidate. The updated dashboard file is present on the existing `localhost:8111` installation. The logged-in synthetic President lacks permission to open this admin-only page, so browser visual confirmation remains part of final item 7.

## Remaining work

Items 1-5 retain PASS evidence for their exact scopes. The President appointment role-policy/UI correction has focused 18/18 PASS evidence. Item 6 (full current-source regression) and the owner installation/visual portion of item 7 remain for final close-out. GitHub readback for the current source and ZIP passed at commit ed434feabfcf965c0ed873a0eb39eaad892db803. Do not reopen older FAIL/NOT RUN text for the exact scopes in items 1-5 without contradictory current evidence. The root project roadmap was not changed.


## What's New admin render probe - 2026-11-26

**PASS - server-rendered dashboard callback contains the current .66.a release entry and owner-facing content.** On the existing local clone at :8111, WordPress rendered ZMP_Admin::view_dashboard() under the pre-existing synthetic administrator account (user 4). No account, role, database, or server state was changed.

Read-only output checks: 26,645 bytes rendered; the What's New section was present; the selected release entry for 1.2.4.66.a was present; passkey release text was present; and both required subscriber and additive zmp_teacher role terms were present. The live browser remained logged in as the synthetic President, which is correctly denied access to this administrator-only page; browser visual confirmation is deferred to the owner ZIP-install step (item 7), as requested. This probe does not replace the full regression/ZIP close-out (items 6 and 7).


## President appointment role-policy correction - 2026-11-26

SUPERSEDED 2026-11-27: the administrator appoints the first President, and an Administrator account is also eligible when it has `subscriber + zmp_teacher`. The 2026-11-26 result (18/18) covers the earlier non-administrator rule only; rerun that matrix against the corrected stacked-role policy. Items 6 and 7 remain subject to current-source evidence.
