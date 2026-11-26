## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Stacked active President school-email service — 1.2.4.66.b — 2026-11-24

## Purpose
Recheck the school-email service with the role combination required by the Owner: a President who remains a Teacher and Subscriber and also carries Moderator, Commission, Testator and Organizer roles. This is scoped service/runtime evidence for the exact phase snapshot.

## Results
**PASS 8/8** using a synthetic-only WordPress DB `zmp65_subject_test`, prefix `z66s_`, and loopback URL `http://127.0.0.1:8098`.

- The synthetic candidate retained Teacher, Subscriber, Moderator, Commission, Testator and Organizer roles.
- The real President appointment service activated the mandate and added President without removing the other roles.
- Teacher+Subscriber submitted a request tied to two matching active synthetic school engagements and a reserved `example.test` address.
- The complete stacked active President read the pending queue without receiving the email address.
- The President approval service accepted the decision, stored the correct actor, email hash and profile epoch, and recorded the submitted/approved events.
- Requester status readback showed approval state without disclosing the address or its hash.
- Synthetic users, mandate/audit entries, request/events, school/profile/mentor rows and debug-option state were restored to their exact baselines.

Fixture: `tests/school-email-stacked-president-service.php`; SHA-256 `73484807B6536FCE4CCA80BC3325F466E9B31F935F42D56D228F2DD7C9758BC8`. PHP lint passed. The four route/service source files (`class-zmp-school-email-access.php`, `class-zmp-president-mandate.php`, `class-zmp-mentor-school-profile.php`, `zmp-competitions.php`) were linted and their runtime copies matched the corresponding phase sources (16/16 phase/file pairs). All four local WordPress DB checks passed.

## Boundary
This validates the service-level appointment, pending queue, approval and private status-readback path. It does **not** repeat the historical HTTP `admin-post.php` instrumented test that failed 15/25 on temporary MU-guard markers. That historical failure remains preserved; the marker instrumentation issue is not converted to PASS by this service-level result. No mail, external HTTP, YouTube or production site was accessed.