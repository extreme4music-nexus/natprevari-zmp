## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Messenger reply route — local synthetic runtime — 1.2.4.66.b — 2026-11-24

## Result

**PASS 7/7** for an actual moderator-room reply through ZMP_Role_Messenger::handle_post() on the isolated local WordPress site /home/tonic/zmp-local-sandbox/phase66b-test/site. Active plugin: zmp-competitions-66b. The phase source and loopback runtime includes/class-zmp-role-messenger.php matched exactly, SHA-256 $src.

A synthetic Subscriber+Moderator sender and recipient were created; a synthetic root thread was given an earlier expiry; the real callback saved the reply into that same exact moderator scope, retained the root thread's earlier expiry, and created the recipient's unread notification. The fixture checked the role/capability stack, root and reply, notification, restored message/notification/read/attachment counts and removed both users. Every assertion passed. WP-CLI showed its expected redirect trace because the route redirects and exits. Post-run DB integrity: PASS.

The first harness revision had a cleanup bug: it tried to pass an array of IDs to $wpdb->delete() as a scalar. The failed harness correctly reported cleanup failure. Its exact synthetic root, reply, and linked notification were identified and removed; follow-up query confirmed zero leftover matching rows and database integrity passed. The corrected fixture deletes exact IDs one at a time and the final run passed all assertions. This was a test-harness defect, not a plugin defect.

## Fixture and scope

	ests/role-messenger-reply-runtime.php — SHA-256 $fh; PHP lint: PASS. No file attachment was included, and this does not verify private-file persistence/download, commission/region assignment variants, export, review, or browser UI. No production site, account, message, email, or external service was used.