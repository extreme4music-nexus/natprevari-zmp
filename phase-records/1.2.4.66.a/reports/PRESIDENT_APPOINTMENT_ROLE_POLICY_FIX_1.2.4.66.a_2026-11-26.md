# President appointment role policy and form wording - .66.a - 2026-11-26

# OWNER RULE — READ FIRST

**EVERY ZMP MEMBER MUST HAVE subscriber. zmp_teacher IS OPTIONAL FOR MEMBERS GENERALLY, BUT THE PRESIDENT MUST ALWAYS HAVE BOTH subscriber AND zmp_teacher. OTHER ALLOWED ZMP ROLES MAY BE STACKED AND MUST BE PRESERVED.**

## Finding

The administrator is the authorized actor who records the President's mandate. The administrator's own account is not eligible to become President; that restriction does not prevent the administrator from appointing another registered account.

The existing notice incorrectly said that Teacher, Moderator, Testator, and Commission accounts were ineligible. The form help text also said that only a Subscriber account could be selected. Actual code allowed several stacked ZMP roles but only required Subscriber, so it did not enforce the Owner's mandatory President Teacher role.

## Correction

- Added one shared President-account eligibility check in zmp-competitions.php. It requires Subscriber and Teacher, rejects administrator/manage_options, rejects unapproved capabilities/roles, and preserves allowed stacked roles.
- The mandate service, role-grant path, and appointment candidate list now use that shared check.
- Rewrote the administrator notice and form help text: an administrator records the mandate for a separate candidate; the candidate must already have Subscriber and Teacher; allowed Moderator, Commission, Testator, Organizer, and existing President roles are retained; no role is silently changed.

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
