## HISTORICAL RECORD — STATUS AS OF ITS ORIGINAL DATE

This is dated phase evidence. Current `.66.a` checklist status is in `../1.2.4.66.a/reports/CURRENT_OPEN_SCOPE_RECONCILIATION_1.2.4.66.a_2026-11-26.md`; do not treat older status text below as current where a later exact-scope PASS exists.

# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# 🚨 OWNER POLICY — ROLE STACKING — READ FIRST

**EVERY ZMP MEMBER MUST HAVE `subscriber`. `zmp_teacher` IS OPTIONAL, BUT ANY MEMBER MAY ALSO HAVE IT. BOTH MUST COEXIST WITH OTHER AUTHORIZED ROLES WITHOUT BREAKING THE PLUGIN.**

**THE PRESIDENT MUST ALWAYS HAVE BOTH `subscriber` AND `zmp_teacher`. TEACHER-SCOPED FUNCTIONS REQUIRE `zmp_teacher`; OTHER MEMBER FUNCTIONS MUST NOT. EXTERNAL NON-MEMBER COMMISSION IS THE ONLY MEMBERSHIP EXCEPTION.**

**THIS 2026-11-25 OWNER POLICY OVERRIDES ANY CONFLICTING OLDER WORDING BELOW. HISTORICAL TEST RESULTS REMAIN LIMITED TO THE ROLE STACKS THEY ACTUALLY TESTED.** Canonical record: `00_START_HERE/ROLE_STACKING_OWNER_POLICY_2026-11-25.md`.

---
# Communications rollback при неуспешно создавање поврзан пост — .66.b — 2026-11-25

## Околина
Постојниот изолиран WordPress 127.0.0.1:8103, база zmp66b_isolated, префикс z66a_. Кандидатот и активниот runtime имаат ист hash за includes/class-zmp-communications.php: 59f2593ab5f1d5fc534e8b5661cccdbd7f1f785fa704e9895e4ee261504256f5.

## Проверка и резултат
Синтетички Subscriber+Moderator испрати валидно организациско известување. Само за синтетичкиот наслов, WordPress post insert беше намерно одбиен преку локален тест-филтер. Реалниот handle_publish() врати zmp_comms_status=post_create_error.

PASS: пред cleanup немаше announcement, notification или WordPress post од тестот; вкупните броеви останаа на почетната состојба. Не беше направен обид за е-пошта. Fixture потврди дека намерниот insert failure се активирал, очекуваниот redirect статус бил присутен и тест-корисникот е исчистен. wp db check помина.

Fixture: tests/communications-publish-post-failure-rollback-runtime.php; SHA-256 ca9a8be49dd74bbef591ecfa02fe0c36d4945692a76bcb1a94ff374330c2c11f. WP-CLI redirect warning е очекуван. Plugin source не е сменет.

Ова потврдува само rollback кога создавањето на поврзаниот пост не успева. Notification insert failure, категоријални/фазни scope варијанти, други rollback патеки и визуелен преглед остануваат отворени.

## Rollback при делумно внесување известувања — PASS
Дополнителен fixture предизвика неуспех на второто од две синтетички notification INSERT операции, откако првиот notification успешно бил внесен во рамки на трансакцијата. Реалниот handler врати save_error; пред cleanup не останаа announcement, прв notification, втор notification или WordPress post. Не беше испратена е-пошта. Точниот синтетички профил е исчистен и wp db check помина.

Вториот fixture: tests/communications-notification-insert-failure-rollback-runtime.php; SHA-256 ca731ac2684b14f41795f3372abb1cae0515e59518c3861bff8a5be6bcdda302. Source/runtime hash остана 59f2593ab5f1d5fc534e8b5661cccdbd7f1f785fa704e9895e4ee261504256f5. Локалниот HTTP guard marker е 372 бајти: 4 api.wordpress.org и 30 localhost записи. Guard ги блокира intercepted HTTP обидите; новиот запис е локален, без нов надворешен host.
