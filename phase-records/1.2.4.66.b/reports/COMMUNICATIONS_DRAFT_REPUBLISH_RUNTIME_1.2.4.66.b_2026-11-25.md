## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Communications saved-draft publication — local synthetic PASS

Date: 2026-11-25 (Europe/Skopje).
Scope: The existing `ZMP_Communications::handle_publish_draft()` handler in the isolated `.66.b` WordPress/MariaDB clone. This is distinct from the earlier new-announcement publish test.

Candidate source `includes/class-zmp-communications.php` matched the active runtime at SHA-256 `2A3BB1074CE446863F76ED0669CBB89582651FF4FED996C9C0661C9D7081F260`. Fixture: `tests/communications-draft-republish-runtime.php`, SHA-256 `19E17568FA1334856D2D447CDBEC24539A290FD03F01B60D80682EA1DE071B48`; PHP syntax PASS. The fixture refused to proceed unless the exact two recipients were existing synthetic `zmp66...` users with `@example.invalid` addresses.

PASS: a synthetic Subscriber+Moderator-owned draft was confirmed as `draft` before handler invocation. The actual handler published that row, added exactly one unread notification per synthetic recipient, created and linked one published WordPress post with the expected author and scope metadata, and attempted no email. Cleanup deleted only fixture-owned rows/post/user and restored announcement, notification, post, and user counts. `wp db check` passed. WP-CLI emitted the expected redirect warning after commit; shutdown assertions all passed.

This closes the saved general-announcement draft publication path only. Result-announcement gating, updates to an already-linked post, narrower category/phase recipient scopes, browser review, and actual email transport remain separate checks.

Run log: `/home/tonic/zmp-local-sandbox/phase66b-test/logs/communications-draft-republish-20261125.txt`.
