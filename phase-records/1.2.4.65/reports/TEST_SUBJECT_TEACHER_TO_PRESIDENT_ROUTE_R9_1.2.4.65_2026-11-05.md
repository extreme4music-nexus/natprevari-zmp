## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# Test Engineer runtime — R9 first Teacher submit diagnostic

**Result: FAIL for fixture execution; product route acceptance NOT ESTABLISHED.** The exact R9 fixture was invoked once. It uncovered missing synthetic inputs in the fixture itself; no retry was made.

## Candidate and setup

- R9 fixture SHA-256: `23F73B9CA49956E776B559F9D8A3170D7E3BE49787C104F045FFF937190928B0`.
- Independent exact-hash review: PASS, report `CODE_REVIEW_SUBJECT_REGISTER_ADMIN_POST_R9_1.2.4.65_2026-11-05.md`, SHA-256 `AD08148E013E82B891E172695987E9F6A3021AF8B7915065AEB29B201391018F`.
- Fresh synthetic target: `/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6`, DB `zmp65_subject_test` (`z65s_`), `127.0.0.1:8097`.
- Preflight: task site/tmp paths absent, DB absent, port unbound. Active site DB was `zmp65_isolated`, PHP PID `145525` on `8094`.
- Activation diagnostics baseline: one `zmp_debug_events` row, `autoload=off`, fingerprint `b0cc23d8e1ae9f7be224a92855fa658a4f48d10052589a91a972e2bbabe06d42` (SHA-256 over raw `option_value`, NUL, and `autoload`).

## One-shot route result

- Exact fixture invocation: `wp --path=/home/tonic/zmp-local-sandbox/phase65-test/site-subject-r6 eval-file /mnt/z/Documents/Natprevari_ZMP/zmp-competitions-1.2.4.65/tests/phase65-subject-register-admin-post.php`.
- Schema preflight: **PASS**.
- One Teacher POST: HTTP **302**; allowlisted `zmp_subject_status=invalid_subject`.
- Only safe diagnostic tuples were emitted: `ZMP test request reached WordPress init.` / category `teacher_subject_proposals` / error `none`; `Subject proposal validation failed.` / category `teacher_subject_proposals` / error `invalid_subject`.
- Proposal/persistence assertions failed. Source inspection after the run confirmed `$phrase` and `$subject_key` were referenced without definitions in R9. The undefined phrase reached the route as an empty name and produced `invalid_subject`; the undefined key also caused warnings in fixture lookup/cleanup. This is a fixture defect; behavior on valid proposal input was not tested.
- Fixture summary: `2/7 PASS`. Exact diagnostics restoration and no-outbound-activity checks failed. The outbound guard identified a `network` marker; it blocked the attempted non-allowlisted request. No external request success was claimed.
- Pre-disposal aggregate proposal/event/catalog/alias/user/mentor counts: `0/0/0/0/1/0`. Diagnostics remained one row with 3 events, `autoload=off`; fingerprint changed to `17c440b6b63131b9087e0812df17abecf3ac0cfc2c21691037413c3646d9115f`. The fixture did not restore the exact diagnostics baseline. Fixture-owned MU guard and marker files were absent after its cleanup. No proposal text, event text, user names, or credentials are retained here.

## Cleanup and protected state

- Stopped only task PHP PID `282928`; port `8097` released.
- Dropped only task DB `zmp65_subject_test`; removed only task site and temp paths. Verified task DB/site/tmp absent and `8097` unbound.
- Active `zmp65_isolated` normalized export hash before and after this R9 attempt: `7233a6d75584eff0c43aa350663785a0a9a9afc27b5aa1d5364e1425d2cc3703` (unchanged). Active PHP PID `145525` remained on `8094`.
- This run's `7233a6d...` active DB fingerprint is distinct from the earlier R6/R8 historical fingerprint `b0a08f61824049d80c7f0a350ca9f4d00fbcf317e1227928420b0497ba6064c`; no attempt was made to reconcile or modify active site data.
- Persistent production-clone sandbox was not accessed. No product source or gate-ledger change was made during this R9 run. Preserve R3 FAIL and all President photo/consent PASS evidence.
