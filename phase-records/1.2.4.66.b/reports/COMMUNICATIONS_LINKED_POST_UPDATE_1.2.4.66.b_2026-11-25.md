## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Communications linked WordPress post update — local synthetic PASS

Date: 2026-11-25 (Europe/Skopje).
Scope: Publishing an existing Communications draft that already links to a WordPress draft post, in the isolated `.66.b` clone.

The exact `.66.b` Communications source matched runtime SHA-256 `2A3BB1074CE446863F76ED0669CBB89582651FF4FED996C9C0661C9D7081F260`. Fixture `tests/communications-linked-post-update-runtime.php`, SHA-256 `AA073CC1C2B52C77E330F7797DB9E579593C4763F8E01FFCC985DFD8F243E78E`, passed PHP syntax validation. It used a synthetic Subscriber+Moderator and required the two notification recipients to be synthetic `zmp66...` users with `.invalid` mailboxes.

PASS: the handler published the exact draft with updated title/body, changed the linked WordPress post in place to `publish`, preserved the same post ID, updated its title/content and scope metadata, and left exactly one WordPress `post` associated with the announcement. Exactly one unread notification was created for each synthetic recipient. No email was attempted. Cleanup removed only the synthetic announcement, notices, linked post and user; tracked counts returned to baseline and `wp db check` passed.

The first harness version's no-duplicate assertion counted all WordPress post rows and incorrectly assumed no revision row would be created. It reported false even though the exact linked post had updated in place. The fixture was narrowed to count `post` records with the exact announcement metadata; the corrected rerun passed all assertions. This was a test assertion issue, not a product defect.

Run log: `/home/tonic/zmp-local-sandbox/phase66b-test/logs/communications-linked-post-update-20261125.txt`.

This closes the existing-linked-post update case for a general announcement. Category/phase recipient scopes, positive confirmed-results release, browser presentation, and external mail delivery remain separate checks.
