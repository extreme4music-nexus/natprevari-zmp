## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Communications publish — local synthetic PASS

Date: 2026-11-25 (Europe/Skopje).

The `.66.b` Communications source SHA-256 matched the isolated local WordPress runtime: `2A3BB1074CE446863F76ED0669CBB89582651FF4FED996C9C0661C9D7081F260`. Test fixture `tests/communications-publish-positive-runtime.php`, SHA-256 `7D1BD2B1F2B171A2373388275465B923447D1C880027D917AE4C6FC22E60A987`, passed PHP syntax validation.

In the isolated synthetic clone, the actual publish handler accepted a Subscriber+Moderator with a valid WordPress nonce; created one published general announcement; added exactly one unread notification for each of two pre-existing synthetic recipients (`@example.invalid`); created a published WordPress post and linked it to the announcement with expected scope metadata. No email was attempted. The temporary local HTTP guard blocked all outbound HTTP.

Cleanup removed only the test-owned announcement, notification rows, linked post, and synthetic moderator. Announcement, notification, post, and user counts returned to baseline. `wp db check` passed. WP-CLI emitted its normal redirect warning after the handler committed; shutdown assertions all passed.

This closes the new general-announcement publish path, recipient notification-row creation, and post synchronization only. Draft republishing, editing an existing linked announcement, category/phase recipient scope, result-publication gate, browser review, and actual email delivery remain distinct open checks.

Run log: `/home/tonic/zmp-local-sandbox/phase66b-test/logs/communications-publish-positive-20261125.txt`.
