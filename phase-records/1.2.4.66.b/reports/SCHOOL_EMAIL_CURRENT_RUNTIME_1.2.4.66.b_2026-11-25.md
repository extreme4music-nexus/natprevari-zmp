## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Училишна е-пошта: тековниот .66.b runtime — 2026-11-25

Околина: постојниот изолиран WordPress 127.0.0.1:8103, база zmp66b_isolated, префикс z66a_. Локалниот runtime беше синхронизиран со тековниот кандидат пред тестирањето. Нема нов сервер, production пристап или вистинско испраќање е-пошта.

## Резултати
- Директен admin-post approval со автентициран активен синтетички Претседател и привремено стекнати teacher/subscriber/moderator/commission/testator/organizer улоги: PASS 10/10. Одговорот беше HTTP 302 со очекуван статус; адресата не се појави во локацијата или телото; approval actor/hash/profile epoch и submitted/approved настаните се совпаднаа; requester readback не откри адреса или hash.
- Сервисен тек со истиот stacked President: PASS 8/8. Проверени се редот за pending барања без адреса, одлуката, hash и profile epoch, audit actors и приватниот status readback.
- Постоечката активна синтетичка мандатна евиденција не беше сменета во конечниот тест. Привремените улоги на usermeta беа вратени на суровата почетна вредност; сите синтетички requester/owner податоци беа отстранети.
- Локалниот wp-config.php беше привремено поставен со DISABLE_WP_CRON само за POST тестот и потоа вратен по оригинални бајти; постојниот HTTP marker беше вратен на почетниот запис. Никаква е-пошта не беше испратена. wp db check помина.

## Хешеви
Кандидатот и стварно вчитаниот runtime се исти за:
- class-zmp-school-email-access.php: d8855bc141d5e1f854b751a10420eb90d04af9b6cbcd2f7a3e0da7664be796ce
- class-zmp-president-mandate.php: 42bcba1b7be30beceb38c150da2e7b40dc3cc1aaef90d0c88239c0bc89b292dd
- class-zmp-mentor-school-profile.php: be9a3fe7048f24a1288e2ff70f11613b2c6282cbdd7b8428241e29dc5ed6ae41
- class-zmp-mentor-schools.php: 0d3605d351e4c27a053036abd72b944716e06a9e884e39978b89ea3fc7d56d95
- zmp-competitions.php: 173c81c33139e84463a76256709bd4d44eb1c64046118f5b3e17429f3fd08adb

Fixtures: tests/school-email-stacked-president-admin-post-current-runtime.php (5e5179a336fa3e16f7015d0dcf8f85cffe20deddcb84447d79316e1e8941107a) and tests/school-email-stacked-president-service-current-runtime.php (cbca7ace5717af7d3a69a22014c68605fe14df060c8cfc82730f354e49a1e2cc). Runtime outputs are reports/SCHOOL_EMAIL_CURRENT_RUNTIME_ADMIN_POST_2026-11-25.txt and reports/SCHOOL_EMAIL_CURRENT_RUNTIME_SERVICE_2026-11-25.txt.

## Отворени граници
Ова затвора само активниот President read/approve/submit тек во тековниот runtime. Не го повторува тековниот hash на appointment/replacement маршрутата; appointment тестот од 24-ти беше на друга sandbox инстанца. Тековниот appointment/replacement тек, негативни улоги/nonce/no-mutation cases, визуелен приказ, надворешна испорака и продукциска шема остануваат одделни проверки.
