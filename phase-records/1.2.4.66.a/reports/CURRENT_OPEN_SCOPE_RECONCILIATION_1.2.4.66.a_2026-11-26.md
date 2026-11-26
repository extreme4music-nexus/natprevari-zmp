# 🚨 OWNER RULE — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL AND ADDITIVE; ANY MEMBER MAY ALSO HAVE IT WITHOUT BREAKING OTHER ROLES OR PLUGIN FEATURES. THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`.**

# .66.a current checklist — 2026-11-26

## Current seven-item checklist

1. President/Teacher profile browser Submit and reload/readback — **PASS**.
2. Partial passkey schema migration failure — **PASS, fail-closed**.
3. Normal expired-challenge cleanup — **PASS**.
4. Cancel/unavailable authenticator followed by owner-entered PIN retry — **PASS**; PIN entry/waiting is expected.
5. Focus, subject selection, reverse Tab, keyboard, and Narrator — **PASS**; Narrator success owner-confirmed.
6. Functional/security regression for the open `.66.a` scopes — **PASS, 2026-11-26**. Actual decision routes and negative security cases passed 38 checks; exact-hash concurrent V2 diagnostic remains PASS. See [full regression report](FULL_FUNCTIONAL_SECURITY_REGRESSION_1.2.4.66.a_2026-11-26.md).
7. Fresh ZIP from the phase folder, package-to-phase hash verification, and GitHub sync/readback — **PASS, 2026-11-26**. Owner installation validation is the next step.

### Release-notes follow-up — 2026-11-26

The dashboard's **Што е ново?** list now includes a dedicated `.66.a` release entry and an owner-facing test checklist for passkey enrollment/login/subject decisions, profile readback, role stacking, fail-closed schema handling, and privacy-safe diagnostics. The current package is `04_RELEASES/VERSIONS/1.2.4.66.a/ZIP/zmp-competitions-1.2.4.66.a-2026-11-26-whats-new.zip`; its earlier same-day package is superseded. The updated dashboard file was copied to the existing `localhost:8111` installation. A direct dashboard view could not be confirmed in-browser because the currently logged-in synthetic President account lacks permission to open that admin page; no database or user data was changed. Owner install/visual confirmation remains pending.

## Remaining work

All seven listed checks now have PASS evidence. No test item in this checklist remains open. Owner installation/visual confirmation of the provided ZIP remains the next user-side step. Do not reopen any exact scope from older FAIL/NOT RUN text without contradictory current evidence. The 2026-11-25 register was superseded. The root project roadmap was not changed.
