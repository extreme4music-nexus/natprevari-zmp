# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Independent Code Review — multi-school directory-scope fixture — 1.2.4.65 — 2026-11-05

## Verdict
**PASS** — read-only source review of the test-only fixture at exact SHA-256 `467C7C473252C2DB1ED04961A0360D7688FF4604E83B6EA049C1840CE8DA06FB`.

The fixture's candidate binding targets the reviewed service source SHA-256 `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`. Both hashes were recomputed at review time and matched. The fixture was **not executed** by this Code Reviewer.

## Review evidence

- Runtime guards require database `zmp65_isolated` and prefix `z65_` (lines 35–39), exactly one active plugin `zmp-competitions/zmp-competitions.php` (40–47), ZMP version `1.2.4.65` and required classes (49–57), and the exact reviewed installed service hash (58–65). A mismatch exits or emits `BLOCKED` before current-user switching and before profile or directory reads.
- Teacher #127 is checked against exact ID, username `zmp65multischooltest_20261105`, sole `zmp_teacher` role, `example.invalid` email domain, and display label `Synthetic Multi School Teacher` (67–79). Existing synthetic Subscriber #111 is checked against exact ID, username `zmp65_uipresident_20261103_224731_1bb27f`, sole `subscriber` role, and `example.invalid` domain (81–92). Both identity checks hard-stop before the fixture impersonates either account. The denial route later uses only literal ID 111 (292–298); no `get_users()` or username-prefix lookup remains.
- The fixture checks the two expected engagement IDs and distinct school keys (166–191), per-school values and visibility, scope for the unfiltered and explicit directory calls, and the denial of an unrelated active school (211–289). Missing unrelated-school data is reported `BLOCKED`, not PASS. It checks the rendered shortcode for official-email patterns and disabled employment fields (301–339).
- The reviewed code does not write user, mentor, profile, engagement, request, page, or upload records. It snapshots four named schema/debug options before exercising calls and restores the prior option existence, value and autoload state with readback verification (95–159; restoration in `finally` at 335–338). It restores the original in-memory current user in `finally` and verifies `$wpdb->last_error` is empty (335–342). No broad fixture search, data cleanup, or destructive operation is present.
- Output contains generic assertion labels, candidate hash, database name, and privacy flags only (345–354); no account identities, email values, profile field values, or personal names are emitted. Error messages are generic.

## Limits

This PASS is limited to the safety and correctness of the fixture source and its binding to the exact service candidate. It does not prove that the fixture has run, that all preconditions exist in the live isolated site, or that runtime assertions pass. The actual runtime outcome and post-run database/service evidence remain the Test Engineer's responsibility. This report does not replace the `.65` whole-phase Code Review, Test Engineer results, Master Review, or Auditor decision.

## Change and execution status

No product code or tests were edited. The fixture was not run. This standalone review report is the only file created by this review task.