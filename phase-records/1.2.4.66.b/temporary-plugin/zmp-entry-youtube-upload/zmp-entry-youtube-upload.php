<?php
/**
 * Plugin Name: ZMP Entry Video Fields — temporary compatibility add-on
 * Description: Adds category-aware video selectors to the existing ZMP competition registration form and hands selected files to the main plugin's existing upload flow.
 * Version: 0.1.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

final class ZMP_Entry_YouTube_Upload_Addon {
    const VERSION = '0.1.0';

    public static function init() {
        add_filter('do_shortcode_tag', array(__CLASS__, 'inject_registration_video_fields'), 20, 4);
    }

    public static function inject_registration_video_fields($output, $tag, $attr, $match) {
        if ($tag !== 'zmp_category_entries' || !is_string($output) || strpos($output, 'zmp-category-registration') === false) {
            return $output;
        }
        if (!class_exists('ZMP_YouTube') || !class_exists('ZMP_Category_Registrations') || !is_user_logged_in()) {
            return $output;
        }

        $form_end = strpos($output, '</form>');
        if ($form_end === false) { return $output; }
        $form_start = strrpos(substr($output, 0, $form_end), '<form');
        if ($form_start === false) { return $output; }
        $form_markup = substr($output, $form_start, $form_end - $form_start);
        if (strpos($form_markup, 'name="category_id"') === false) { return $output; }

        $season = ZMP_Category_Registrations::active_season();
        if (!$season) { return $output; }
        $schools = self::owned_schools();
        $categories = self::active_music_categories((int) $season->id);
        $modes = array();
        foreach ($schools as $school) {
            foreach ($categories as $category) {
                $modes[(string) $school->id][(string) $category->id] = ZMP_YouTube::regional_delivery_mode((int) $season->id, (int) $category->id, (string) $school->region);
            }
        }

        $connected = ZMP_YouTube::is_connected();
        $markup = '<fieldset class="zmp-entry-video-fields" id="zmp-entry-video-fields" hidden>'
            . '<legend>Видеа за натпреварот</legend>'
            . '<p class="zmp-entry-video-guidance" aria-live="polite"></p>'
            . '<div class="zmp-entry-video-slots"></div>'
            . '<p class="zmp-entry-video-progress" aria-live="polite"></p>'
            . '</fieldset>';
        $markup .= self::script($modes, $connected);
        $output = substr_replace($output, $markup, $form_end, 0);
        return $output;
    }

    private static function owned_schools() {
        if (!class_exists('ZMP_Mentor_Schools')) { return array(); }
        global $wpdb;
        $mentor = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}zmp_mentors WHERE wp_user_id=%d", get_current_user_id()));
        return $mentor ? (array) ZMP_Mentor_Schools::for_mentor((int) $mentor->id) : array();
    }

    private static function active_music_categories($season_id) {
        global $wpdb;
        return (array) $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.id,c.category_code FROM {$wpdb->prefix}zmp_categories c
             JOIN {$wpdb->prefix}zmp_season_categories sc ON sc.category_id=c.id AND sc.season_id=%d AND sc.is_enabled=1
             WHERE c.is_active=1 AND c.category_code<>'knowledge_test'",
            $season_id
        ));
    }

    private static function script($modes, $connected) {
        $config = wp_json_encode(array('modes' => $modes, 'connected' => (bool) $connected));
        $config = str_replace('</', '<\\/', (string) $config);
        return '<style>.zmp-entry-video-fields{margin:1.25rem 0;padding:1rem;border:1px solid #c3c4c7;border-radius:8px}.zmp-entry-video-fields legend{font-weight:600}.zmp-entry-video-slot{display:block;margin:.75rem 0}.zmp-entry-video-slot input{display:block;max-width:100%;margin-top:.35rem}.zmp-entry-video-progress{font-weight:600}</style>'
            . '<script>(function(){var cfg=' . $config . ';var form=document.querySelector(".zmp-category-registration form.zmp-form");if(!form||form.dataset.zmpVideoAddon==="1")return;form.dataset.zmpVideoAddon="1";var fieldset=form.querySelector("#zmp-entry-video-fields"),slots=fieldset&&fieldset.querySelector(".zmp-entry-video-slots"),guidance=fieldset&&fieldset.querySelector(".zmp-entry-video-guidance"),progress=fieldset&&fieldset.querySelector(".zmp-entry-video-progress"),category=form.querySelector("[name=category_id]"),school=form.querySelector("[name=mentor_school_id]");if(!fieldset||!slots||!category||!school)return;var defs={vocal_interpretation:[["performance","Настап"]],school_choir:[["required","Задолжителна композиција"],["optional","Изборна композиција"]],school_orchestra:[["required","Задолжителна композиција"],["optional","Изборна композиција"]],school_bands:[["optional_one","Изборна композиција 1"],["optional_two","Изборна композиција 2"]]};function refresh(){var opt=category.options[category.selectedIndex],code=opt?opt.dataset.code:"",slotsFor=defs[code],mode=(cfg.modes[school.value]||{})[category.value]||"unconfigured";slots.replaceChildren();progress.textContent="";if(!slotsFor){fieldset.hidden=true;return;}fieldset.hidden=false;guidance.textContent=mode==="online"?"Онлајн регионално ниво: пријавата останува нацрт додека не се прикачат и проверат потребните видеа. Може да ги изберете сега или подоцна во „Мои пријави“. Видеата се Unlisted и секој со линкот може да ги гледа.":(mode==="physical"?"Физичко регионално ниво: видеата се изборни; пријавата може да се поднесе и без нив.":"Начинот на регионалното натпреварување не е поставен. Пријавата нема да може да продолжи додека администраторот не го постави.");if(!cfg.connected)guidance.textContent+=" YouTube каналот не е поврзан, па upload нема да биде достапен додека администраторот не го поврзе.";slotsFor.forEach(function(item){var label=document.createElement("label");label.className="zmp-entry-video-slot";label.textContent=item[1]+(mode==="online"?" *":" (по избор)");var input=document.createElement("input");input.type="file";input.name="zmp_initial_video_"+item[0];input.accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,.mp4,.m4v,.mov,.webm,.avi,.mpeg,.mpg";input.dataset.slot=item[0];input.dataset.mode=mode;input.disabled=mode==="unconfigured";label.appendChild(input);slots.appendChild(label);});}category.addEventListener("change",refresh);school.addEventListener("change",refresh);refresh();function setStatus(text){progress.textContent=text;}function xhrUpload(uploadForm,file){return new Promise(function(resolve,reject){var fd=new FormData(uploadForm);fd.set("youtube_video",file,file.name);var xhr=new XMLHttpRequest();xhr.open("POST",uploadForm.action);xhr.withCredentials=true;xhr.upload.onprogress=function(e){if(e.lengthComputable)setStatus("Прикачување на видео: "+Math.round(e.loaded/e.total*100)+"%");};xhr.onload=function(){if(xhr.status>=200&&xhr.status<400)resolve(xhr.responseURL||uploadForm.action);else reject(new Error("upload-http-"+xhr.status));};xhr.onerror=function(){reject(new Error("upload-network"));};xhr.send(fd);});}form.addEventListener("submit",function(event){var selected=Array.prototype.slice.call(slots.querySelectorAll("input[type=file]")).filter(function(i){return i.files&&i.files.length;});if(!selected.length)return;if(!cfg.connected){setStatus("YouTube каналот не е поврзан. Отстранете ги избраните видеа; пријавата може да ја испратите без изборни видеа.");selected.forEach(function(i){i.value="";});return;}event.preventDefault();var button=form.querySelector("button[type=submit]");if(button)button.disabled=true;setStatus("Ја зачувувам пријавата и подготвувам безбеден upload…");var participant=form.querySelector("[name=participant_name]");var expectedName=participant?participant.value.trim():"";var selectedCategory=category.options[category.selectedIndex];var expectedCategory=selectedCategory?selectedCategory.textContent.trim():"";var registrationData=new FormData(form);selected.forEach(function(i){registrationData.delete(i.name);});fetch(form.action||window.location.href,{method:"POST",body:registrationData,credentials:"same-origin",redirect:"follow"}).then(function(response){if(!response.ok)throw new Error("registration-http-"+response.status);return response.text().then(function(html){return {html:html,url:response.url};});}).then(async function(result){var entryUrl=new URL(result.url,window.location.href);entryUrl.searchParams.set("zmp_mentor_tab","students");entryUrl.searchParams.delete("tab");var entriesPageUrl=entryUrl.href;var entriesResponse=await fetch(entriesPageUrl,{credentials:"same-origin",redirect:"follow"});if(!entriesResponse.ok)throw new Error("entries-http-"+entriesResponse.status);var entriesHtml=await entriesResponse.text();var doc=new DOMParser().parseFromString(entriesHtml,"text/html");var rows=Array.prototype.slice.call(doc.querySelectorAll(".zmp-table tbody tr"));var row=rows.find(function(r){var cells=r.querySelectorAll("td");return cells.length>8&&cells[0].textContent.trim().indexOf(expectedCategory)!==-1&&cells[2].textContent.trim().indexOf(expectedName)!==-1;});if(!row)throw new Error("entry-row-not-found");var forms=Array.prototype.slice.call(row.querySelectorAll("td:nth-child(9) .zmp-youtube-upload-form"));for(var i=0;i<selected.length;i++){var input=selected[i],slotForm=forms.find(function(f){var s=f.querySelector("[name=slot_key]");return s&&s.value===input.dataset.slot;});if(!slotForm)throw new Error("upload-slot-unavailable");setStatus("Се прикачува: "+input.files[0].name);await xhrUpload(slotForm,input.files[0]);}setStatus("Видеата се испратени. Го отворам статусот на пријавата…");window.location.href=entriesPageUrl;}).catch(function(error){setStatus("Пријавата можеби е зачувана како нацрт. Отворете „Мои пријави“ и проверете го статусот пред нов обид.");if(button)button.disabled=false;});});})();</script>';
    }
}

add_action('plugins_loaded', array('ZMP_Entry_YouTube_Upload_Addon', 'init'), 30);
