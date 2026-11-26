<?php
if (!defined('ABSPATH')) { exit; }
/** Student PIN hashing and reversible encryption for authorized envelope printing. */
class ZMP_Credentials {
    private static function key() { return hash('sha256', wp_salt('auth'), true); }
    public static function encrypt_pin($pin) {
        if (!function_exists('openssl_encrypt')) return false;
        $iv = random_bytes(16);
        $cipher = openssl_encrypt((string)$pin, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) return false;
        $mac = hash_hmac('sha256', $iv.$cipher, self::key(), true);
        return 'zmpenc:v1:'.base64_encode($iv.$mac.$cipher);
    }
    public static function reveal_pin($stored) {
        if (strpos((string)$stored, 'zmpenc:v1:') !== 0) return (string)$stored;
        if (!function_exists('openssl_decrypt')) return '';
        $raw = base64_decode(substr($stored, 10), true);
        if ($raw === false || strlen($raw) < 49) return '';
        $iv=substr($raw,0,16); $mac=substr($raw,16,32); $cipher=substr($raw,48);
        if (!hash_equals(hash_hmac('sha256',$iv.$cipher,self::key(),true),$mac)) return '';
        $plain=openssl_decrypt($cipher,'aes-256-cbc',self::key(),OPENSSL_RAW_DATA,$iv);
        return $plain === false ? '' : $plain;
    }
    public static function migrate_legacy_pins() {
        global $wpdb; $table=$wpdb->prefix.'zmp_students';
        $rows=$wpdb->get_results("SELECT id,access_pin,access_pin_hash FROM $table WHERE access_pin_hash='' OR access_pin_hash IS NULL");
        foreach($rows as $row) {
            $pin=self::reveal_pin($row->access_pin);
            if ($pin === '') continue;
            $encrypted=self::encrypt_pin($pin);
            if ($encrypted === false) continue;
            $wpdb->update($table,array('access_pin'=>$encrypted,'access_pin_hash'=>wp_hash_password($pin)),array('id'=>$row->id),array('%s','%s'),array('%d'));
        }
    }
    public static function verify_and_upgrade($student,$pin) {
        if (!empty($student->access_pin_hash) && wp_check_password($pin,$student->access_pin_hash)) return true;
        $legacy=self::reveal_pin($student->access_pin);
        if ($legacy === '' || !hash_equals($legacy,(string)$pin)) return false;
        $encrypted=self::encrypt_pin($pin);
        if ($encrypted !== false) {
            global $wpdb;
            $wpdb->update($wpdb->prefix.'zmp_students',array('access_pin'=>$encrypted,'access_pin_hash'=>wp_hash_password($pin)),array('id'=>$student->id),array('%s','%s'),array('%d'));
        }
        return true;
    }
}
