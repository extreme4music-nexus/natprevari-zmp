## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Role Messenger moderator send — local synthetic runtime evidence

Date: 2026-11-18 (Europe/Skopje)
Scope: Positive send path for a new moderator-room thread in the isolated `.66.b` clone. No real account, message, attachment, email, or external service was used.

## Candidate and test

- Runtime: `/home/tonic/zmp-local-sandbox/phase66b-test/site`; synthetic database `zmp66b_isolated`, prefix `z66a_`.
- Helper `role-messenger-moderator-send-positive.php`, SHA-256 `F6DE3628B232D81EBC799371AD6F34336C1028901B0D5510E60FCBB5E1E4E43E`; PHP syntax PASS.
- Created two disposable synthetic users, each with Subscriber+Moderator roles. Invoked the actual `ZMP_Role_Messenger::handle_post()` callback with a valid `zmp_role_messenger` `_wpnonce`, new moderator-room subject, and synthetic body. No attachment was included.

## Result

PASS: current moderator capability and room access were recognized; the message was saved under the exact sender and moderator-only scope; the first message linked its thread ID to its own message ID; retention expiry was populated; and the other synthetic moderator received one unread scoped notification. Cleanup deleted the synthetic message, notification/read/attachment rows and both users. Messenger table counts returned to baseline and `wp db check` passed.

WP-CLI displayed its expected redirect warning because the actual handler redirects and exits; the shutdown assertions verified the committed row and exact cleanup. No product code changed.

## Limits

This closes one positive new-thread send path. Replies, attachments, commission/region assignment boundaries, export/download, review requests, recipient-read state, and browser UI remain open. No live service or external mail transport was called.
