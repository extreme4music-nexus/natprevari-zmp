# 🚨 HISTORICAL EVIDENCE — NOT CURRENT PHASE STATUS

This document is dated evidence for its original phase and scope. The current `.66.a` checklist is summarized in `../zmp-competitions-1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`. A later PASS supersedes older status only for the same exact scope.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

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
- Fixture tests/audio-catalog-admin-post-runtime.php SHA-256: 98da1f821e853d469a15ca4596686cc94bacea8ce7d21ff35e5cb9f6832352a4.
- Runtime излез: reports/AUDIO_CATALOG_ADMIN_POST_RUNTIME_2026-11-25.txt.
- URL-аудит и наставничка видливост: reports/AUDIO_CATALOG_AUDIT_VISIBILITY_1.2.4.66.b_2026-11-25.md. Тој доказ е од пред дополнителните archived guards; проверените URL/audit/visibility методи не се изменети.

## Преостанати граници
Проверени се конкретни позитивни и негативни admin-post случаи, но не целосната авторизациска матрица за секоја улога или сите malformed inputs. Визуелниот админ UI и responsive приказот остануваат NOT RUN. Овие аудио-проверки не затвораат пошироки .66.b route, review или release gates. Не е користен продукциски сајт, е-пошта, реален аудио-сервис или YouTube.


## Malformed audio route inputs — supplemental current-runtime PASS
Extended the existing actual admin-post fixture with malformed category and malformed identifier cases:
- Save with category="../../administrator" returned notice=invalid and did not mutate the catalog option.
- Archive with ID "!!" (sanitized to empty) and valid nonce returned notice=missing and did not mutate the catalog option.
The full existing lifecycle/negative fixture still passed, including mocked exact-URL audit, anonymous and invalid-nonce denial, archived locks, exact option restoration, HTTP guard restoration, synthetic admin removal and wp db check.
Updated fixture SHA-256: 98da1f821e853d469a15ca4596686cc94bacea8ce7d21ff35e5cb9f6832352a4.


## Supplemental Edge desktop UI check — 2026-11-25
The actual local WordPress audio-admin screen was opened in Edge after the admin-post tests. The form, required fields, category options, empty catalog state, and teacher-visibility guidance rendered; console errors/warnings: zero. No form was submitted and no data changed. Candidate/runtime hashes matched. Responsive layout and non-admin authorization remain open. See `AUDIO_CATALOG_ADMIN_UI_EDGE_2026-11-25.md`.
The earlier fixture hash in this report was stale; the final fixture hash is `98da1f821e853d469a15ca4596686cc94bacea8ce7d21ff35e5cb9f6832352a4`.
