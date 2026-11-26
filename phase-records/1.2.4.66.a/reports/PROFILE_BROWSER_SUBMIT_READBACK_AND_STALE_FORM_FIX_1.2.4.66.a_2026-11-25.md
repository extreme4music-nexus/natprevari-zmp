# 🚨 HISTORICAL EVIDENCE — NOT THE CURRENT PHASE STATUS

This report records its original dated and scoped observation. It is not a current open-item register. If its FAIL, NOT RUN, BLOCKED, IN PROGRESS, or NOT_READY text conflicts with newer evidence for the same exact scope, use the 2026-11-26 current register and regression report instead. Do not reopen an exact scope that has a newer PASS absent contradictory evidence.

# President/Teacher profile browser submit and readback — .66.a — 2026-11-25

## Result

**PASS — synthetic local browser submit and reload/readback completed on the existing `http://localhost:8111` clone.** The profile was submitted through the WordPress Update Profile form, then reloaded. The saved school employment term and subject/grade assignment remained visible and matched database readback.

## Defect found and fixed during the check

The form was initially rendered before a newly approved subject appeared in the approved catalog. Submitting that stale form produced HTTP 400. The school-profile save hook (priority 10) ran before the ZMP teacher-profile hook (also priority 10), so an employment field could be written before the teacher-profile handler rejected the request for lacking the assignment-present marker. This was a partial-save defect as well as stale-form UX ambiguity.

The phase source and the running `:8111` plugin were fixed identically:

- Run the ZMP profile guard/save handler at priority 9, before the school-profile hook at priority 10.
- Detect a stale form with approved subjects but no assignment-present marker before any profile write, and tell the user to refresh and select an approved subject/grade.
- Put the support-profile writes in an InnoDB transaction so a later validation or database failure cannot leave a partial profile update.
- Preserve the stacked-role policy: `subscriber` remains required; Teacher can coexist with President, Moderator, Commission, Testator, Organizer, and other allowed roles without preventing the plugin from operating.

`class-zmp-support.php` SHA-256 in both phase source and running plugin: `b85dc54ffbc65a67c324baadfaa4f48b7f3bcf61824d8f6f152d96ea3f1b2e74`.

## Evidence

1. **Stale-form negative check:** submitted an old-form-equivalent request without the assignment-present marker while approved subjects existed. It was blocked before the school-profile callback; the persisted employment term remained unchanged. Hook priorities read back as teacher handler `9`, school-profile handler `10`.
2. **Browser positive check:** on the existing `:8111` clone, signed in as synthetic President/Teacher/Subscriber `codex66a_president_2511` (user 5), selected the synthetic approved subject `Codex Synthetic Subject for .66.a PASS 20261125`, grade 1, and submitted Update Profile. The page displayed “Профилите по училиште се зачувани.” and “Profile updated.”
3. **Reload/readback:** after reload, the form showed employment term “На определено време”, workload “Неполн фонд”, and the synthetic subject with “1 одделение” checked. Database readback confirmed `mentor_id=3`, `school_id=3`, `employment_term=fixed_term`, `workload=part`, and exactly the intended synthetic assignment (`subject_id=1`, `grade_key=960445e869678e8efaf385a30f6c345be114f8e329742327d88927bb4c68571b`, `grade_label=grade_1`).
4. No other subject assignment was added or changed. The unrelated proposal `id=2` was not touched. No production site was accessed.

## Scope and next work

This closes original open-scope item 2 (browser form submission and readback). It does not close the remaining subject/profile keyboard and assistive-technology interaction checks, passkey schema migration failure behavior, expired-challenge cleanup timing, authenticator cancellation/unavailability, whole-phase regression, or final package/repository synchronization. Those remain separately tracked in `CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-25.md`.
