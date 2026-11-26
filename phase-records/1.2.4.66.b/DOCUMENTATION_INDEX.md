## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---

- Direct stacked-President school-email approval route runtime evidence (2026-11-24): 
reports/SCHOOL_EMAIL_STACKED_PRESIDENT_ADMIN_POST_1_2_4_66_b_2026-11-24.md.

- Stacked Teacher subject proposal route runtime evidence (2026-11-24): 
reports/TEACHER_SUBJECT_ROUTE_STACKED_ADMIN_POST_1_2_4_66_b_2026-11-24.md.

- Moderator delivery-mode portal, scoped saves and rejection evidence (2026-11-24): 
reports/MODERATOR_DELIVERY_MODE_PORTAL_ROUTE_1.2.4.66.b_2026-11-24.md.

- Circular invitation positive send-state route with pre-transport email capture (2026-11-24): `reports/CIRCULAR_INVITATION_SEND_ROUTE_CAPTURE_1.2.4.66.b_2026-11-24.md`.
- Messenger moderator reply, notification and thread-retention route (2026-11-24): `reports/ROLE_MESSENGER_REPLY_ROUTE_1.2.4.66.b_2026-11-24.md`.
- Messenger private attachment upload/download, unauthorized denial and scoped CSV export (2026-11-24): `reports/ROLE_MESSENGER_ATTACHMENT_HTTP_1.2.4.66.b_2026-11-24.md`.
- Messenger limited-review request, exact administrator review, participant notice and acknowledgement (2026-11-24): `reports/ROLE_MESSENGER_LIMITED_REVIEW_REQUEST_1.2.4.66.b_2026-11-24.md`.

- Communications published announcement, synthetic recipient notices, and WordPress post synchronization (2026-11-25): `reports/COMMUNICATIONS_PUBLISH_POST_SYNC_RUNTIME_1.2.4.66.b_2026-11-25.md`.

- Communications saved-draft publication, recipient notifications, and WordPress post linkage (2026-11-25): `reports/COMMUNICATIONS_DRAFT_REPUBLISH_RUNTIME_1.2.4.66.b_2026-11-25.md`.

- Communications result-publication fail-closed gate (direct creation and existing draft) (2026-11-25): `reports/COMMUNICATIONS_RESULTS_PUBLICATION_GATE_1.2.4.66.b_2026-11-25.md`.

- Communications existing linked WordPress post update without duplicate (2026-11-25): `reports/COMMUNICATIONS_LINKED_POST_UPDATE_1.2.4.66.b_2026-11-25.md`.


- Communications positive confirmed-results publication, mentor notice and phase restoration (2026-11-25): `reports/COMMUNICATIONS_CONFIRMED_RESULTS_PUBLISH_1.2.4.66.b_2026-11-25.md`.
- Communications category-result exact phase-recipient scoping fix and synthetic runtime evidence (2026-11-25): `reports/COMMUNICATIONS_CATEGORY_RESULTS_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md` (runtime output: `reports/COMMUNICATIONS_CATEGORY_RESULTS_RECIPIENT_SCOPE_RUNTIME_2026-11-25.txt`).


- Jury qualification checkbox normalization and invalid finalization POST rejected before writes (2026-11-25): `reports/JURY_QUALIFICATION_NORMALIZATION_RUNTIME_1.2.4.66.b_2026-11-25.md` (runtime output: `reports/JURY_QUALIFICATION_NORMALIZATION_RUNTIME_2026-11-25.txt`).

- Regional-only commission passkey eligibility and registration-options challenge (2026-11-25): `reports/REGIONAL_ONLY_PASSKEY_REGISTRATION_OPTIONS_1.2.4.66.b_2026-11-25.md` (runtime output: `reports/REGIONAL_ONLY_PASSKEY_REGISTRATION_OPTIONS_RUNTIME_2026-11-25.txt`). Browser authenticator enrollment completion remains NOT RUN.

- Jury finalization form suppression and direct POST fail-closed behavior with pending documents (2026-11-25): `reports/PENDING_DOCUMENT_FINALIZATION_GATE_1.2.4.66.b_2026-11-25.md` (runtime output: `reports/PENDING_DOCUMENT_FINALIZATION_GATE_RUNTIME_2026-11-25.txt`).

- Saved category-results draft publication, exact qualified recipient, linked WordPress post and exact cleanup (2026-11-25): `reports/COMMUNICATIONS_CATEGORY_RESULTS_DRAFT_RECIPIENT_SCOPE_1.2.4.66.b_2026-11-25.md` (runtime output: `reports/COMMUNICATIONS_CATEGORY_RESULTS_DRAFT_RECIPIENT_SCOPE_RUNTIME_2026-11-25.txt`).
# Аудио-каталог: локален аудит и видливост — .66.b
Датум: 2026-11-25
Околина: постојниот изолиран WordPress на `localhost:8103`, база `zmp66b_isolated`, префикс `z66a_`. Не е стартуван нов сервер.

## Проверени делови
На точниот runtime код се повикаа `public_url()`, `check_tool_url()` и `teacher_visible_tools()`. HTTP транспортот беше mock-иран преку `pre_http_request`: само `https://8.8.8.8/synthetic-audio-tool` доби синтетички HTTP 200, ETag и body; секоја друга HTTP адреса ќе беше одбиена.

## Резултат
- Валидирање на синтетички HTTPS URL: PASS.
- Автоматски аудит со mock одговор: PASS (`available`, HTTP 200, отпечаток создаден).
- Контрола на мрежа: PASS — точно еден mock повик, 0 неочекувани HTTP повици.
- Свежа, рачно одобрена synthetic алатка се појавува во наставничкиот каталог: PASS.
- Иста алатка со аудит постар од 14 дена е скриена: PASS.
- Неодобрена алатка е скриена: PASS.
- Поставката `zmp_approved_audio_tools` е вратена точно според претходниот DB ред (ист option ID, сериализирана вредност и autoload): PASS.
- `wp db check`: PASS, сите табели се OK.

## Идентификација
- Runtime извор `includes/class-zmp-approved-audio-tools.php`: SHA-256 `10641166B008302FD12A05CD8AD0372DB30ECB5B8B7B31AF626475C17FA8F1E7`.
- Тест-фискстура `tests/audio-catalog-audit-visibility-runtime.php`: SHA-256 `ff554034b52ee2b91a1145f21d29892ef27bb3d58777974aa106bd994428584c`.
- Полниот stdout: `reports/AUDIO_CATALOG_AUDIT_VISIBILITY_RUNTIME_2026-11-25.txt`.

Ова затвора само позитивната проверка на серверскиот URL аудит и правилата за наставничка видливост. Не ги затвора admin-post тековите за креирање, одобрување, одбивање и архивирање, ниту визуелната проверка на админ-страницата. Тие остануваат отворени. Не се испраќаше е-пошта и не се посетуваше јавна адреса.
# Аудио-каталог: администраторски текови — локален PASS (.66.b)
Датум: 2026-11-25
Околина: постојниот изолиран WordPress на `127.0.0.1:8103`, база `zmp66b_isolated`, префикс `z66a_`. Не е стартуван нов сервер.

## Вистински рути проверени
Синтетички администратор со еднократна локална сесија ги испрати формите до тековните `admin-post.php` обработувачи:
1. `zmp_audio_tool_save`: кандидатот е зачуван како `pending`.
2. `zmp_audio_tool_audit`: точниот синтетички HTTPS URL доби mock HTTP 200/ETag/body и е зачуван свеж резултат `available`.
3. `zmp_audio_tool_approve`: со задолжителните полиња, осум означени проверки и белешка, записот стана `approved`; наставничкиот каталог го прикажа.
4. Повторно `save` со изменет URL: статусот се врати на `pending`, аудитот на `not_checked`, а алатката исчезна од наставничкиот каталог.
5. `zmp_audio_tool_archive`: кандидатот доби статус `archived`.

Сите рути вратија очекувано HTTP 302 со соодветното известување. Mock-филтерот дозволуваше само точниот тест URL; постојниот локален HTTP-заштитник остана активен и секој неочекуван HTTP повик беше одбиван.

## Чистење и резултат
- Претходниот ред на `zmp_approved_audio_tools` е вратен со ист option ID, сурова сериализирана вредност и autoload: PASS.
- Синтетичкиот администратор и привремениот MU-филтер се отстранети: PASS.
- Дијагностичкиот marker на постојниот HTTP-заштитник е вратен точно на претходните бајти: PASS.
- `wp db check`: PASS, сите табели се OK.

## Идентификација
- `includes/class-zmp-approved-audio-tools.php` SHA-256: `10641166B008302FD12A05CD8AD0372DB30ECB5B8B7B31AF626475C17FA8F1E7`.
- Fixture `tests/audio-catalog-admin-post-runtime.php` SHA-256: `820878a9752cdd00563ac1512fa46b07800babd1c6a4fc8dbe9a322c31068a95`.
- Претходната проверка на URL-аудит/видливост е во `reports/AUDIO_CATALOG_AUDIT_VISIBILITY_1.2.4.66.b_2026-11-25.md`.
- Runtime резултат: `reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt`.

Два рани тест-harness обиди беа прекинати без промена на кодот: првиот наиде на локалната политика што намерно блокира и HTTP loopback од WP-CLI; вториот прочита застарен process-local option cache и mock-филтер вчитан пред заштитниот филтер. Fixture-от беше поправен да го користи постојниот localhost сервер преку curl, да чита свежа DB-вредност и да се вчитува по заштитникот. Рутите потоа поминаа. Ова беа ограничувања на тест-harness-от, не дефекти на додатокот.

Овој доказ затвора позитивните административни create/audit/approve/edit/archive текови за аудио-каталогот. Не ги проверува негативните авторизациски/невалидни-влез матрици или визуелниот админ UI; тие остануваат одделни отворени проверки. Не беше користен реален аудио-сервис, е-пошта, продукциски сајт или YouTube.
# Аудио-каталог: admin-post текови и архивирање — .66.b
Датум: 2026-11-25
Околина: постојниот изолиран WordPress на 127.0.0.1:8103, база zmp66b_isolated, префикс z66a_. Не е стартуван нов сервер.

## Дефект најден и поправен
Директен валиден admin POST можеше повторно да одобри алатка веднаш по архивирање, ако автоматскиот аудит сè уште беше свеж. UI-то ги криеше дејствата кај архивирани записи, но серверскиот approve() не ја проверуваше состојбата. Тестот доби notice=approved и статусот се смени од archived во approved.

Во includes/class-zmp-approved-audio-tools.php, approve() сега одбива архивиран запис со archived_locked пред да обработи одобрување. Админ-известувањето го објаснува одбивањето. За повторно разгледување, администраторот прво го уредува записот; промена на URL го поставува на pending и го ресетира аудитот.

## Повторена проверка на вистински локални рути
По копирањето на поправката во стварно активната папка wp-content/plugins/zmp-competitions-66b, истата проверка помина:
- Save: нов кандидат зачуван како pending.
- Audit: точниот тест URL доби mock HTTP 200/ETag/body; available и отпечаток се зачувани.
- Approve: осумте потребни проверки и синтетичката белешка се зачувани; кандидатот се појави во наставничкиот каталог.
- Archive, потоа директен approve со свеж аудит: notice=archived_locked; записот остана archived.
- Edit со нов URL: pending, not_checked, скриен од наставници.
- Archive повторно: archived.

Сите POST одговори ги имаа очекуваните HTTP 302 и известувања. По меѓупроцесните POST се читаа суровите тековни DB-вредности, за да не се потпираме на застарен WP-CLI cache.

## Runtime/кандидатска усогласеност и чистење
Првичниот ретест ја ажурирал неактивната runtime-папка zmp-competitions, додека WordPress ја вчитуваше zmp-competitions-66b. Класата беше проверена од стварно вчитаната runtime патека, активната копија синхронизирана и тестот повторен. Тековниот кандидатски и активен runtime фајл имаат ист хеш.

Опцијата е вратена со ист DB option ID, сурова сериализирана вредност и autoload; синтетичкиот администратор и привремениот mock се отстранети; постојниот HTTP-заштитен marker е вратен точно; wp db check помина за сите табели.

## Идентификација
- Кандидат и активен runtime class-zmp-approved-audio-tools.php SHA-256: C09694FEAB8F162F1C56B95A496653168CDC7D9D2523724118646BB68F15A9DF.
- Fixture tests/audio-catalog-admin-post-runtime.php SHA-256: 139af40851304da8cdfba1a11504a53bde3918959e46f387f3c7dfbe841fabcd.
- Резултат: reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt.
- Поврзана проверка на URL аудит и наставничка видливост: reports/AUDIO_CATALOG_AUDIT_VISIBILITY_1.2.4.66.b_2026-11-25.md. Таа е од пред archived-state заштитата; проверените URL/audit/visibility методи не се изменети, а тековната целосна класа е врзана за хешот погоре.

Негативната авторизациска и невалиден-влез матрица и визуелниот админ UI остануваат отворени. Ова не е целосно затворање на .66.b. Не е контактиран реален аудио-сервис, продукциски сајт, е-пошта или YouTube.
# Аудио-каталог: admin-post текови и fail-closed проверки — .66.b
Датум: 2026-11-25
Околина: постојниот изолиран WordPress на 127.0.0.1:8103, база zmp66b_isolated, префикс z66a_. Не е стартуван нов сервер.

## Дефекти најдени и поправени
Директен валиден admin POST можеше повторно да одобри алатка веднаш по архивирање, ако аудитот сè уште беше свеж. Тестот доби notice=approved и ја смени состојбата archived во approved. И директно одбивање на архивиран запис ја смени состојбата archived во rejected.

Во includes/class-zmp-approved-audio-tools.php, approve() и reject() сега одбиваат archived записи со archived_locked пред да запишат одлука. Известувањето кажува дека архивиран кандидат мора прво да се уреди за нов преглед. Промената на URL преку save го враќа во pending и го ресетира аудитот.

## Тековен runtime тест
Проверени се стварните admin-post рути на тековниот активен додаток:
- Save: кандидатот се зачувува како pending.
- Audit: точниот синтетички HTTPS URL добива mock HTTP 200/ETag/body; се зачувуваат available и отпечаток.
- Approve: со задолжителните полиња и осум рачни проверки кандидатот се одобрува и се појавува во наставничкиот каталог.
- Archive: кандидатот станува archived.
- Директно approve или reject на архивиран кандидат: notice=archived_locked; статусот останува archived.
- Edit со изменет URL: статус pending, аудит not_checked и кандидатот се крие од наставници; потоа може повторно да се архивира.
- Approve пред свеж аудит: audit_required и суровиот DB option останува непроменет.
- Невалиден YouTube URL во privacy поле: invalid и нема DB промена.
- Неавтентициран save повик и save со невалиден nonce: одбиени, без промена на option.

Сите POST одговори ги имаа очекуваните HTTP статуси и известувања. Mock-от одговараше само на точниот синтетички URL; локалниот HTTP-заштитник блокираше неочекувани повици. Нема реален надворешен HTTP контакт.

## Усогласеност и чистење
WordPress ја вчитува активната датотека од wp-content/plugins/zmp-competitions-66b. Кандидатот и активниот runtime ја имаат истата SHA-256 вредност. Заштитниот marker е вратен точно, како и опцијата со истиот option ID, сурова серијализирана вредност и autoload. Синтетичкиот администратор и привремениот mock се отстранети. wp db check помина за сите табели.

## Идентификација
- Кандидат и активен runtime class-zmp-approved-audio-tools.php SHA-256: C3DD0B13D5D1D4A5FCEA540279C884C0F1B871BC2216300A972B0DBECA4662A1.
- Fixture tests/audio-catalog-admin-post-runtime.php SHA-256: 2407ea4d3756037604131c6ac9a27eb7461cb938edbd22403e3ffbf7475bc8d2.
- Runtime излез: reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt.
- URL-аудит и наставничка видливост: reports/AUDIO_CATALOG_AUDIT_VISIBILITY_1.2.4.66.b_2026-11-25.md. Тој доказ е од пред дополнителните archived guards; проверените URL/audit/visibility методи не се изменети.

## Преостанати граници
Проверени се конкретни позитивни и негативни admin-post случаи, но не целосната авторизациска матрица за секоја улога или сите malformed inputs. Визуелниот админ UI и responsive приказот остануваат NOT RUN. Овие аудио-проверки не затвораат пошироки .66.b route, review или release gates. Не е користен продукциски сајт, е-пошта, реален аудио-сервис или YouTube.
# Активниот локален runtime е усогласен со .66.b — 2026-11-25

## Наод и дејство
Активниот локален WordPress на 127.0.0.1:8103 го вчитува plugin директориумот wp-content/plugins/zmp-competitions-66b. Hash споредба со тековниот кандидат откри 14 различни PHP-файлови, покрај еден кандидатски source во одделната папка temporary-plugin/. За да ги проверувам тековните .66.b текови, ги синхронизирав 14-те различни source фајлови во активната локална plugin папка. Пред синхронизацијата направив копии во /home/tonic/zmp-local-sandbox/phase66b-test/tmp/runtime-pre-candidate-sync-20261125.

Синхронизирани фајлови:
- zmp-competitions.php
- admin/class-zmp-president-mandate-admin.php
- includes/class-zmp-president-mandate.php
- includes/class-zmp-mentor-school-profile.php
- includes/class-zmp-mentor.php
- includes/class-zmp-school-email-access.php
- includes/class-zmp-support.php
- includes/class-zmp-school-email-access-ui.php
- includes/class-zmp-president-governance.php
- includes/class-zmp-teacher-register.php
- includes/class-zmp-president-subject-approvals.php
- includes/class-zmp-mentor-subjects.php
- includes/class-zmp-mentor-subject-proposals.php
- includes/class-zmp-president-facsimile-certificates.php

## Проверка по синхронизацијата
Hash споредбата на 69 PHP/CSS/JS изворни фајлови во plugin директориумот покажа 69/69 совпаѓање со кандидатот; останати разлики: 0. Кандидатската папка temporary-plugin/zmp-entry-youtube-upload содржи еден фајл што не е во активниот plugin директориум. Овој извештај не заклучува дали тој одделен кандидатски додаток треба да биде посебен дел од пакетот; неговата намена/пакетирање останува за проверка пред release.

Не е стартуван нов сервер, не е активиран друг plugin, не е менувана база при самата синхронизација и production не е допрен. Локалните резервни копии остануваат во tmp додека продолжуваат фазните проверки.
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

## 2026-11-25 — current-hash President mandate runtime
- reports/PRESIDENT_MANDATE_CURRENT_HASH_RUNTIME_1.2.4.66.b_2026-11-25.md records appointment, scheduled cancellation and active revocation callback PASS on synchronized code; corresponding .txt preserves concise results. The appointment subscriber/valid-nonce and administrator/invalid-nonce denial/no-mutation cases also PASS; other negative variants and visual admin checks remain open.

## 2026-11-25 — Communications rollback
- reports/COMMUNICATIONS_POST_FAILURE_ROLLBACK_1.2.4.66.b_2026-11-25.md records the synthetic linked-post insert failure and rollback PASS; partial notification-insert rollback also passes; other category/phase and failure variants remain open.

## 2026-11-25 — school-email decision denial
- reports/SCHOOL_EMAIL_DECISION_DENIAL_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md documents 5/5 current-runtime denials and exact cleanup; submit/disclosure negative cases and UI remain open.

## 2026-11-25 — school-email approved disclosure
- reports/SCHOOL_EMAIL_APPROVED_DISCLOSURE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md records 7/7 current-runtime disclosure/privacy cases; stale-contact, submit-request negatives and UI remain open.


## 2026-11-25 — President mandate stacked roles
- reports/PRESIDENT_MANDATE_STACKED_MEMBER_UI_AND_RUNTIME_1.2.4.66.b_2026-11-25.md: corrected form guidance and candidate filtering; Edge UI and current callback scheduled stacked-role PASS, exact cleanup and DB check.
- Fixture: tests/president-mandate-stacked-member-appointment-current-runtime.php. Scope is limited; broader mandate and release checks remain open.


## 2026-11-25 — school-email submit denials
- reports/SCHOOL_EMAIL_SUBMIT_NEGATIVE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md: focused current-runtime request submission denials PASS 6/6 with no-mutation assertions, exact cleanup and DB check.
- Fixture: tests/school-email-submit-negative-current-runtime.php. Other submission variants and UI remain open.

- Supplemental fixture tests/president-mandate-candidate-filter-current-runtime.php verifies eligible stacked member, stale President exclusion and Administrator exclusion (4/4).

- reports/SCHOOL_EMAIL_SUBMIT_POSITIVE_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md: authenticated positive submit callback, pending record, single submitted event, requester readback, no address disclosure, exact cleanup and DB check.
- Fixture tests/school-email-submit-positive-current-runtime.php.

- Additional fixture tests/school-email-submit-boundary-current-runtime.php covers missing contact, owner/school relationship, contact role, foreign school ID and long reason denials (6/6 including cleanup).

- reports/SCHOOL_EMAIL_SUBMIT_IDEMPOTENCY_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md: repeated same-scope request reuses existing pending request without duplicate event (4/4); concurrent race remains open.
- Fixture tests/school-email-submit-idempotent-current-runtime.php.

- Supplemental fixture tests/president-mandate-stacked-replacement-current-runtime.php verifies current-hash active replacement with stacked successor, audit, role retention, exact baseline restoration and DB integrity (7/7).

- reports/SCHOOL_EMAIL_ACCESS_UI_RENDER_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md: local shortcode rendering of teacher request form and President review queue, no pre-approval address disclosure (7/7); not browser/mobile visual acceptance.
- Fixture tests/school-email-access-ui-render-current-runtime.php.

- reports/SCHOOL_EMAIL_DISCLOSURE_ADMIN_POST_CURRENT_RUNTIME_1.2.4.66.b_2026-11-25.md: actual local disclosure endpoint, request-bound nonce, no-store/noindex, audit privacy and exact cleanup (7/7).
- Fixture tests/school-email-disclosure-admin-post-current-runtime.php.

- Updated current fixture tests/school-email-disclosure-admin-post-current-runtime.php (SHA-256 54003d155f54ed7583a844a2f94b435e4e5f5df0f909496b166559cccfebb60c) now covers positive disclosure plus other-Teacher and invalid-nonce HTTP denials (9/9 combined).

- Updated tests/audio-catalog-admin-post-runtime.php adds malformed-category and malformed-ID no-mutation cases; final SHA-256 98da1f821e853d469a15ca4596686cc94bacea8ce7d21ff35e5cb9f6832352a4.

## Current audio catalog follow-up — 2026-11-25
- Admin-post lifecycle and focused malformed-input cases: `reports/AUDIO_CATALOG_ADMIN_POST_1.2.4.66.b_2026-11-25.md` and `reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt` (final fixture SHA-256 `98da1f821e853d469a15ca4596686cc94bacea8ce7d21ff35e5cb9f6832352a4`).
- Actual Edge desktop admin render: `reports/AUDIO_CATALOG_ADMIN_UI_EDGE_2026-11-25.md`.
- Desktop render PASS; responsive layout, role authorization matrix, remaining malformed values, and wider release gates remain open.

## School-phase results closure — 2026-11-25
- Finding, source hashes, regression evidence and remaining limits: `reports/COMMUNICATIONS_KNOWLEDGE_SCHOOL_PHASE_1.2.4.66.b_2026-11-25.md`.
- Captured runtime outputs: `reports/COMMUNICATIONS_KNOWLEDGE_SCHOOL_PHASE_RUNTIME_2026-11-25.txt`.
- Local category-scoped school publication and Teacher filter are PASS. Full passkey ceremony for school result finalization remains NOT RUN.


## Messenger role/scope matrix — 2026-11-25
- Runtime result: 
eports/ROLE_MESSENGER_SCOPE_MATRIX_1.2.4.66.b_2026-11-25.md; captured output: 
eports/ROLE_MESSENGER_SCOPE_MATRIX_RUNTIME_2026-11-25.txt.
- PASS 13/13 for focused room authorization; broader Messenger flows and whole-phase gates remain open.



## Messenger readiness and role/scope evidence — 2026-11-25
- Read-only current schema/retry snapshot: 
eports/MESSENGER_SCHEMA_READINESS_RUNTIME_1.2.4.66.b_2026-11-25.md.
- Role and category/region authorization matrix: 
eports/ROLE_MESSENGER_SCOPE_MATRIX_1.2.4.66.b_2026-11-25.md.
- These scoped passes do not close the Messenger route/UI/production gates.



## Messenger regional-only scope evidence — 2026-11-25
- 
eports/ROLE_MESSENGER_REGIONAL_ONLY_SCOPE_1.2.4.66.b_2026-11-25.md — PASS 5/5, regional-only exact assignment; does not close UI/message lifecycle.



## President Teacher + Subscriber invariant — 2026-11-25
- 
eports/PRESIDENT_TEACHER_SUBSCRIBER_RULE_1.2.4.66.b_2026-11-25.md — candidate and appointment guard correction with local runtime evidence.



## President dashboard role stack — 2026-11-25
- 
eports/PRESIDENT_STACKED_ROLE_DASHBOARD_1.2.4.66.b_2026-11-25.md — supplemental current-candidate role/menu/render evidence on retained 8103 clone; Owner's prior 8100 evidence is preserved separately.



## Owner-directed Drive video flow — 2026-11-25

Owner-directed design: replace the teacher-facing YouTube upload/link flow with plugin-managed upload to restricted ZMP Google Drive. The teacher sees the selected filename and size; client and server validate the actual video; upload begins only after “Поднеси пријава”. The form stays in place with animated staged progress and no refresh until Drive upload, object/metadata verification, and jury playback authorization all pass. The entry remains a draft on any failure and supports an exact retry.

The jury sees a small thumbnail card, not a Drive URL or file ID. Clicking it opens playback through the authenticated ZMP stream after exact commission assignment checks. Reduce file size with a quality-preserving validated compression profile before upload; keep the original privately until success. Preserve category slot rules and physical/online/combined requiredness. The full design and acceptance checklist are in reports/DRIVE_VIDEO_SUBMISSION_AND_JURY_THUMBNAIL_DESIGN_1.2.4.66.b_2026-11-25.md.

Status: DOCUMENTED / NOT IMPLEMENTED OR TESTED. Existing Drive OAuth, resumable upload, private media storage, and authenticated player are the starting mechanism; the current upload handler redirects and the jury view has no thumbnail card. Do not count the design note as a runtime PASS. The main PROJECT_ROADMAP.md remains unchanged.
- `.65` inherited-fix reconciliation and `.66.b` test results (2026-11-25): `reports/INHERITED_65_FIXES_AND_66B_OPEN_SCOPE_1.2.4.66.b_2026-11-25.md`.
- President + Teacher + Subscriber first-time self-setup, owner-only guard and safe diagnostic regression: `tests/phase66b-president-teacher-stacked-profile-self-setup.php` (**10/10 PASS**, 2026-11-25).
- Focused role/profile regression fixture: `tests/phase66b-inherited65-regression.php`.
