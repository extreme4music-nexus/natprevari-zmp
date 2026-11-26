# President appointment role policy and form wording - .66.a - 2026-11-26

# OWNER RULE — READ FIRST

**EVERY ZMP MEMBER MUST HAVE subscriber. zmp_teacher IS OPTIONAL FOR MEMBERS GENERALLY, BUT THE PRESIDENT MUST ALWAYS HAVE BOTH subscriber AND zmp_teacher. OTHER ALLOWED ZMP ROLES MAY BE STACKED AND MUST BE PRESERVED.**

## Finding

SUPERSEDED 2026-11-27: The administrator is authorized to record the President's mandate. An Administrator account may also be the appointee when it has `subscriber + zmp_teacher`; allowed stacked roles are preserved.

The existing notice incorrectly said that Teacher, Moderator, Testator, and Commission accounts were ineligible. The form help text also said that only a Subscriber account could be selected. Actual code allowed several stacked ZMP roles but only required Subscriber, so it did not enforce the Owner's mandatory President Teacher role.

## Correction

- HISTORICAL AND SUPERSEDED 2026-11-27: this snapshot rejected administrator/manage_options. The current rule allows Administrator + Subscriber + Teacher and the explicitly allowed stacked ZMP roles; see [the correction report](PRESIDENT_ADMIN_ROLE_STACK_CORRECTION_1.2.4.66.a_2026-11-27.md).
- The mandate service, role-grant path, and appointment candidate list now use that shared check.
- HISTORICAL AND SUPERSEDED 2026-11-27: the earlier form text required a separate non-administrator candidate. Current help text allows Administrator + Subscriber + Teacher and preserves allowed stacked roles.

## Verification

- Existing local clone only: http://localhost:8111, database zmp_debug_prodclone_20261125, prefix wp_yqqdt5_. No server was started or stopped; production was not accessed.
- Focused appointment eligibility matrix: 18/18 PASS across central validation, mandate-service eligibility, and form candidate listing.
- Accepted: Subscriber+Teacher; stacked Subscriber+Teacher+President+Moderator+Commission+Testator+Organizer.
- Rejected: Subscriber-only; Teacher-only; Administrator stack; unapproved Editor stack.
- Synthetic account cleanup: PASS; database check: PASS.
- PHP syntax checks: PASS for the three changed runtime files and the new fixture.
- The three changed runtime files were copied to the existing local clone and their phase/runtime SHA-256 values matched.

## Current release status

The older full-phase regression and candidate ZIP predate this source change. Items 1-5 retain their exact-scope PASS evidence. Whole-phase regression (item 6) and a fresh ZIP/readback/owner installation check (item 7) must be completed for the current source at the final close-out, as the Owner requested. No production site or wp-config.php was changed.
