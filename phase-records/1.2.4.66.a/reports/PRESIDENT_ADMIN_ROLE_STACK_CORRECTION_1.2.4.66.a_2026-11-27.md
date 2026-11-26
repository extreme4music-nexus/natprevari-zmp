# President administrator role-stack correction — 2026-11-27

<h1><strong>🔴🔴🔴 ОБАВЕЗНО: ПРОЧИТАЈ ГО ОВА ПРАВИЛО 5 ПАТИ ПРЕД ДА ПОЧНЕШ РАБОТА 🔴🔴🔴</strong></h1>

**СЕКОЈ ЧЛЕН МОРА ДА ИМА `subscriber`. ПРЕТСЕДАТЕЛОТ МОРА ДА ИМА `subscriber` И `zmp_teacher`. АДМИНИСТРАТОР, НАСТАВНИК, МОДЕРАТОР, ТЕСТАТОР, КОМИСИЈА, ОРГАНИЗАТОР И ПРЕТСЕДАТЕЛ МОЖЕ ДА СЕ КОМБИНИРААТ НА ИСТА СМЕТКА. НЕ ОТФРЛАЈ КАНДИДАТ ЗАРАДИ ОВИЕ КОМБИНАЦИИ. НЕ ОТСТРАНУВАЈ ПОСТОЕЧКИ УЛОГИ. СТАРОТО ПРАВИЛО „ПРЕТСЕДАТЕЛОТ НЕ СМЕЕ ДА БИДЕ АДМИНИСТРАТОР“ Е ПОВЛЕЧЕНО НА 2026-11-27.**

## Correction

The owner clarified that Administrator, Subscriber, Teacher, Moderator, Testator, Commission, Organizer, and President privileges can coexist on one member account. `subscriber` is mandatory for every member; `zmp_teacher` is mandatory for the President. The appointment eligibility code incorrectly rejected `administrator` and `manage_options`, contradicting that rule.

Updated `.66.a` source in `zmp-competitions.php`, `includes/class-zmp-president-mandate.php`, and the President appointment/admin UI. Removed Administrator/manage_options exclusions from eligibility, role grant, mandate creation, and the form's candidate list. The form preserves existing roles.

## Verification

The first local matrix run found that the candidate dropdown still excluded `manage_options` accounts. The full synthetic appointment then exposed a `TypeError` in `zmp_competitions_grant_president_role()`: `$roles` was undefined when building the post-grant role list, and the postcondition also rejected `manage_options`. Captured the existing role stack before granting President and removed the administrator rejection from the postcondition.

Repeated checks on the existing local clone at `http://localhost:8111`: eligibility/list matrix **21/21 PASS**, full Administrator + Subscriber + Teacher + Moderator + Commission + Testator + Organizer appointment **PASS**, existing roles and capabilities preserved **PASS**, synthetic cleanup **PASS**. PHP syntax checks passed for all four modified PHP files.

## Fatal-error status

The local clone reproduced a fatal `TypeError` in the same President appointment grant path, and the corrected path now passes the full synthetic appointment. This is a strong matching root cause for the reported critical error. The production PHP stack itself was unavailable, so the live occurrence cannot be attributed with certainty until the owner tests the refreshed ZIP or supplies that stack.

The fixed top-level `PROJECT_ROADMAP.md` now includes the owner's role-stack stop-condition. The previously recorded 2026-11-26 exclusion of Administrator candidates is superseded.
