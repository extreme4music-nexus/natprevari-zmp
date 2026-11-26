# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Test Engineer report — President teacher-profile authorization (`.66.a`)

**Run time:** 2026-11-12 05:29 UTC  
**Disposition:** **RED / FAIL** — the President self-profile authorization remains blocked in the current candidate. One invocation only; no retry.  
**Product-source edits:** none.

## Source and runtime binding

- Current whole-phase source manifest: SHA-256 `5A88B12F4AAA2D195F7E5C402C16FCCC12744E6FDA392CACAEA1ABD4E7FAE02B`.
- `includes/class-zmp-mentor-school-profile.php`: source and clone SHA-256 `8379706AB7C13553DECB45CE43CA35772C5735CB5D442459D15A54268AE4FC3B`.
- `includes/class-zmp-mentor-schools.php`: source and clone SHA-256 `72D8EC5C13817C9825539ABA015E765831A95D477432CF846DE05832F54F1B46`.
- `includes/class-zmp-database.php`: source and clone SHA-256 `ED0C587D041C06F00DBC122E3AFF0BF2A8C8708193630BA82E1FEE1A23762E16`.
- `includes/class-zmp-regions.php`: source and clone SHA-256 `3557AD3BFEC2F701627CB546B16650F3F08C3924AFFBA54B48A3A43450830222`.
- Plugin entrypoint source and clone SHA-256 `C0F6CD135F7FBEFB09847FA047F76828736C30C0FA802DAD3F06FDD9C3A4989F`.
- Test fixture `tests/phase66a-president-teacher-profile-authorization.php`: SHA-256 `7F17C49BFD2768BD8776EF5B446A0DEDA9A573BB6E1DE088D94C70608B4F9502`.

The inspected implementation of `ZMP_Mentor_School_Profile::is_teacher()` accepts only the exact role `zmp_teacher`. Both `get_profile()` and `save_profile()` call this authorization gate before using the mentor/profile mapping. The fixture therefore creates a President account with a fixture-owned mentor row and an active school, ensuring that a missing mapping cannot explain a self-access denial.

## Isolated environment and preflight

The only target was `/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site` with URL `http://127.0.0.1:8102`, database `zmp66a_isolated`, prefix `z66a_`, and its private socket. Only `zmp-competitions/zmp-competitions.php` was installed and active. Schema marker `3`, profile schema, school schema and the `zmp_president` role passed preflight. Five exact source/runtime hashes above matched.

Before the run, `wp db check` passed. Baseline counts were users/credentials/challenges `1/1/0`; mentors/schools/profiles/proposals/events/mandates/mandate-audit were all zero. The protected synthetic credential was present. A fresh clone-only database backup was created with mode `0600` and verified for compressed-stream integrity, readable table definitions and database integrity. Its identifying details remain private in the sandbox.

Clone MariaDB PID `3828` and private socket remained active. Owner MariaDB PID `409` was untouched. Owner PHP PID `25642` on port `8101` was present and left untouched; clone port `8102` had no listener. No browser, HTTP request, production site or external network was used.

## One-run behavior result

The exact fixture bytes were copied to the clone-private test path and their SHA-256 matched before this single invocation:

```text
wp --path=/home/tonic/zmp-local-sandbox/phase66a-retest-20261111/site --quiet eval-file /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-teacher-profile-authorization.php /home/tonic/zmp-local-sandbox/phase66a-retest-20261111/tests/phase66a-president-teacher-profile-authorization.php
```

- Fixture setup, President-to-mentor mapping, and active-school engagement: **PASS**.
- President `is_teacher()` authorization: **FAIL**.
- President self-profile read: **FAIL**, returned `forbidden`.
- President self-profile write: **FAIL**, returned `forbidden`.
- Other-user read and write of the President profile: **PASS**, both denied.
- Cleanup and database integrity: **PASS**.

This demonstrates a role-authorization failure with the expected mentor mapping and active engagement present. It does not prove an authorization fix; the current candidate has no such fix. With the current gate, profile mapping is not reached for the President self-read/write requests.

## Cleanup and final state

The fixture removed only its synthetic user's profile, active-school rows, mentor row and user, then restored the prior request user and debug options. Its full relevant-table snapshot comparison passed. Independent post-run counts returned to users/credentials/challenges `1/1/0`, mentor/school/profile/proposal/event/mandate tables returned to zero, and the protected credential remained present. A separate `wp db check` passed.

The exact clone-private fixture copy was removed. No test worker, temporary state directory, or clone listener on `8102` remained. Clone MariaDB PID `3828`, Owner MariaDB PID `409`, and Owner PHP PID `25642`/port `8101` remained unchanged; the private recovery backup remains in the sandbox.

## Limits

This is a synthetic WP-CLI behavior check of the profile service, not a browser/UI or HTTP/admin-post test. The profile write was denied before persistence under the current role gate. No product change was made, and no result is claimed for a future candidate after authorization code is updated.