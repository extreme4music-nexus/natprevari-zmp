## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Претседателски мандат — stacked member UI и runtime — 2026-11-25

## Околина и извор
Тестирано на постојниот изолиран WordPress http://localhost:8103, база zmp66b_isolated, префикс z66a_. Кандидатот .66.b и активниот локален приклучок се совпаѓаа за двете изменети PHP-датотеки.
- admin/class-zmp-president-mandate-admin.php — SHA-256 88360f095deae0488125590c10fef7a42be6ad0e5aa24a62f6b7c3e2fc4062be.
- includes/class-zmp-president-mandate.php — SHA-256 1acf69b491112a2cc6d130d3a5334d9d6486da72e51e344367a15e517fd4f557.
- PHP lint помина за двете датотеки; candidate/runtime cmp е идентичен.

## Наод и измена
Формата за назначување Претседател претходно имаше порака дека наставник, модератор, тестатор или член на комисија не е подобен, иако кодниот сервис дозволува стекнати улоги. Текстот сега бара задолжителен subscriber и јасно кажува дека дозволените улоги на член се задржуваат, а назначувањето додава само улога Претседател.
Дополнително, филтерот за кандидати сега исклучува сметка со улога zmp_president ако таа не е тековниот Претседател, но не ги исклучува дозволените членски улоги. Пораката за невалидна сметка е усогласена со истото правило.

## Проверки
- Edge визуелен преглед — PASS: формата прикажа кандидат со subscriber, zmp_teacher, zmp_moderator, zmp_commission, zmp_testator и zmp_organizer, како и објаснување дека улогите се задржуваат.
- Назначување преку реалниот admin-post callback — PASS: стекнатата синтетичка сметка доби закажан мандат за 2030-11-06, по постојниот локален активен мандат; евидентиран е точно еден appointed audit-настан.
- Сите шест оригинални улоги се задржаа по закажувањето. Улогата Претседател не се доделува пред датумот на активирање на закажаниот мандат.
- Fixture пријави точно чистење на својот корисник, мандат и audit-ред. Потоа беа отстранети и двата привремени корисника за UI преглед (ID 218, 219). Постојниот мандат 58/116 не беше менуван.
- wp db check помина за сите табели.
- Fixture: tests/president-mandate-stacked-member-appointment-current-runtime.php — SHA-256 f764aee061e7dce4395f7704675f07d0968957eae89039ea6d0f752a9101ff04.

## Harness белешка
Првата верзија на runtime fixture погрешно очекуваше улогата Претседател да се додели уште при закажување. Callback-от правилно го остави корисникот со постојните улоги додека мандатот не стане активен; fixture-от исчисти сè, па очекувањето беше поправено. Финалниот run помина.

## Граница
Ова затвора само stacked-role подобност, текст и закажан appointment тек во локалниот .66.b. Не затвора замена на активен мандат со стекнат кандидат, активирање на закажан мандат, други негативни улоги, целиот President UI, или пошироките фазни/роудмап проверки. Нема продукциска промена, надворешна HTTP услуга или YouTube тест.


## Candidate filter runtime assertion — supplemental PASS 4/4
The real eligible_users() filter was invoked via reflection on the current local plugin. A synthetic Teacher+Subscriber+Moderator+Commission+Testator+Organizer account was included; a Teacher+Subscriber account with a stale zmp_president role was excluded; Administrator was excluded. Both synthetic users were deleted exactly. PHP lint and wp db check passed.
Fixture: tests/president-mandate-candidate-filter-current-runtime.php — SHA-256 7ea8bde9892f95f4a57d18e5b99692a5bd6236d2b7a847e549edade08db44ede.


## Active-mandate replacement with stacked member — current-runtime PASS 7/7
The actual ZMP_President_Mandate::appoint() service replaced existing active synthetic mandate 58/116 effective today with a synthetic successor carrying Teacher, Subscriber, Moderator, Commission, Testator and Organizer roles.
- Successor row became active for the exact four-calendar-year term; incumbent row became replaced.
- Successor received appointed audit; incumbent received replaced audit.
- All six original successor roles remained and President role was added.
- The fixture restored the exact prior mandate 58 row and prior audit-ID set, restored user 116's exact role stack, deleted the synthetic successor/mandate/audit rows, and restored mandate/audit counts. Database check passed.
- Test source admin/service hashes remained the documented synchronized values; no email, production or external service was used.
- Fixture: tests/president-mandate-stacked-replacement-current-runtime.php — SHA-256 6f71addc85fdc011483f75a1881d3d08a42dd71a6160fadba3f415c23864b107.

Harness note: the first fixture invocation tried to call a private schema helper and stopped before any mutation. Replacing the preflight with the public current_mandate() check allowed the final 7/7 run. No product change.
