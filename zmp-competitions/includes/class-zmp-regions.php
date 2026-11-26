<?php
if (!defined('ABSPATH')) { exit; }

/** Canonical planning regions and municipality lookup for competition records. */
class ZMP_Regions {
    const MAP_SOURCE = 'https://mls.gov.mk/mk-MK/opstini-i-regioni/planski-regioni';

    /** @return array<string,array{label:string,municipalities:array<int,string>}> */
    public static function regions() {
        return array(
            'skopje' => array('label' => 'Скопски', 'municipalities' => array('Центар','Карпош','Кисела Вода','Аеродром','Гази Баба','Бутел','Чаир','Ѓорче Петров','Сарај','Шуто Оризари','Сопиште','Студеничани','Зелениково','Петровец','Илинден','Арачиново','Чучер Сандево')),
            'polog' => array('label' => 'Полошки', 'municipalities' => array('Јегуновце','Теарце','Тетово','Желино','Боговиње','Брвеница','Врапчиште','Гостивар','Маврово и Ростуша')),
            'southwest' => array('label' => 'Југо-западен', 'municipalities' => array('Македонски Брод','Пласница','Кичево','Дебар','Центар Жупа','Дебарца','Струга','Вевчани','Охрид')),
            'pelagonia' => array('label' => 'Пелагонски', 'municipalities' => array('Долнени','Крушево','Кривогаштани','Демир Хисар','Ресен','Битола','Могила','Новаци','Прилеп')),
            'vardar' => array('label' => 'Повардарски', 'municipalities' => array('Чашка','Кавадарци','Росоман','Демир Капија','Неготино','Градско','Велес','Лозово','Свети Николе')),
            'southeast' => array('label' => 'Југо-источен', 'municipalities' => array('Гевгелија','Богданци','Дојран','Валандово','Струмица','Ново Село','Босилово','Василево','Конче','Радовиш')),
            'east' => array('label' => 'Источен', 'municipalities' => array('Берово','Пехчево','Делчево','Виница','Зрновци','Карбинци','Штип','Пробиштип','Чешиново','Кочани','Македонска Каменица')),
            'northeast' => array('label' => 'Северо-источен', 'municipalities' => array('Кратово','Крива Паланка','Ранковце','Старо Нагоричане','Куманово','Липково')),
        );
    }

    /** Normalize Cyrillic or Latin-script municipality names for lookup. */
    private static function key($value) {
        $value = trim((string)$value);
        if (function_exists('mb_strtolower')) { $value = mb_strtolower($value, 'UTF-8'); }
        else { $value = strtolower(strtr($value, array('А'=>'а','Б'=>'б','В'=>'в','Г'=>'г','Д'=>'д','Ѓ'=>'ѓ','Е'=>'е','Ж'=>'ж','З'=>'з','Ѕ'=>'ѕ','И'=>'и','Ј'=>'ј','К'=>'к','Л'=>'л','Љ'=>'љ','М'=>'м','Н'=>'н','Њ'=>'њ','О'=>'о','П'=>'п','Р'=>'р','С'=>'с','Т'=>'т','Ќ'=>'ќ','У'=>'у','Ф'=>'ф','Х'=>'х','Ц'=>'ц','Ч'=>'ч','Џ'=>'џ','Ш'=>'ш'))); }
        $value = preg_replace('/^(општина|op(?:s|š)tina|municipality|град|grad)\s+/u', '', $value);
        $value = strtr($value, array('ѓ'=>'gj','ќ'=>'kj','љ'=>'lj','њ'=>'nj','џ'=>'dzh','ж'=>'zh','ч'=>'ch','ш'=>'sh','ѕ'=>'dz','ј'=>'j','ц'=>'c','ć'=>'c','č'=>'ch','š'=>'sh','ž'=>'zh','đ'=>'dj'));
        $latin = array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','з'=>'z','и'=>'i','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h');
        $value = strtr($value, $latin);
        return preg_replace('/[^a-z0-9]+/u', '', $value);
    }

    public static function municipality_options() {
        $all = array();
        foreach (self::regions() as $region) { $all = array_merge($all, $region['municipalities']); }
        sort($all, SORT_LOCALE_STRING);
        return $all;
    }

    public static function client_lookup_map() {
        $map=array();foreach(self::regions() as $key=>$region){foreach($region['municipalities'] as $name){$map[self::key($name)]=$key;}}
        $map[self::key('Град Скопје')]='skopje';$map[self::key('Скопје')]='skopje';return $map;
    }

    public static function region_for_municipality($municipality) {
        $needle = self::key($municipality);
        if ($needle === self::key('Град Скопје') || $needle === self::key('Скопје')) { return 'skopje'; }
        foreach (self::regions() as $key => $region) {
            foreach ($region['municipalities'] as $name) {
                if ($needle !== '' && $needle === self::key($name)) { return $key; }
            }
        }
        return '';
    }

    public static function valid_region($region) { return array_key_exists(sanitize_key((string)$region), self::regions()); }
    public static function label($region) { $regions=self::regions(); $key=sanitize_key((string)$region); return isset($regions[$key]) ? $regions[$key]['label'] : ''; }

    /** Reconcile legacy rows once on schema upgrade; keep an explicit profile choice. */
    public static function migrate_existing_data() {
        global $wpdb;
        $tables = array(
            array('zmp_mentors','municipality'), array('zmp_students','municipality'),
            array('zmp_category_entries','municipality'), array('zmp_phase_results','municipality'),
        );
        foreach ($tables as $item) {
            $table = $wpdb->prefix . $item[0];
            $rows = $wpdb->get_results("SELECT id, municipality FROM {$table} WHERE region='' OR region IS NULL");
            foreach ((array)$rows as $row) {
                $region = self::region_for_municipality($row->municipality);
                if ($region !== '') { $wpdb->update($table, array('region'=>$region), array('id'=>(int)$row->id)); }
            }
        }
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_students s JOIN {$wpdb->prefix}zmp_mentors m ON m.id=s.mentor_id SET s.region=m.region WHERE (s.region='' OR s.region IS NULL) AND m.region<>''");
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_category_entries e JOIN {$wpdb->prefix}zmp_mentors m ON m.id=e.mentor_id SET e.region=m.region WHERE (e.region='' OR e.region IS NULL) AND m.region<>''");
        $wpdb->query("UPDATE {$wpdb->prefix}zmp_phase_results p JOIN {$wpdb->prefix}zmp_mentors m ON m.wp_user_id=p.mentor_user_id SET p.region=m.region WHERE (p.region='' OR p.region IS NULL) AND m.region<>''");
        // Keep WordPress profile metadata aligned with the authoritative mentor row.
        $profiles=$wpdb->get_results("SELECT wp_user_id,region FROM {$wpdb->prefix}zmp_mentors WHERE region<>'' AND region IS NOT NULL");
        foreach((array)$profiles as $profile){if((int)$profile->wp_user_id>0&&self::valid_region($profile->region)){update_user_meta((int)$profile->wp_user_id,'zmp_region',$profile->region);}}
    }
}
