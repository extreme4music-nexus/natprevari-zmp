jQuery(document).ready(function($) {

    // Commission portal horizontal sections; phase progress is the default view.
    $('[data-zmp-commission-dashboard]').each(function() {
        var $dashboard = $(this), $tabs = $dashboard.find('[data-commission-tab]'), $sections = $dashboard.find('[data-commission-panel]');
        function activateCommissionTab(key, moveFocus, updateUrl) {
            if (!$dashboard.find('[data-commission-tab="' + key + '"]').length) key = 'progress';
            $tabs.each(function() {
                var active = $(this).data('commission-tab') === key;
                if (active) $(this).attr('aria-current', 'page');
                else $(this).removeAttr('aria-current');
                if (active && moveFocus) this.focus();
            });
            $sections.each(function() { this.hidden = this.getAttribute('data-commission-panel') !== key; });
            if (updateUrl) {
                var activeLink = $tabs.filter('[data-commission-tab="' + key + '"]').get(0);
                if (activeLink && window.history && window.history.pushState) window.history.pushState({zmpCommissionTab:key}, '', activeLink.href);
            }
        }
        $dashboard.addClass('is-enhanced');
        var requestedTab = new URLSearchParams(window.location.search).get('zmp_commission_tab');
        var selectedTab = requestedTab || $tabs.filter('[aria-current="page"]').data('commission-tab') || 'progress';
        activateCommissionTab(selectedTab, false);
        $tabs.on('click', function(event) { event.preventDefault(); activateCommissionTab($(this).data('commission-tab'), false, true); });
        window.addEventListener('popstate', function() {
            var params = new URLSearchParams(window.location.search), requested = params.get('zmp_commission_tab') || 'progress';
            activateCommissionTab(requested, false);
        });
        $tabs.on('keydown', function(event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' && event.key !== 'Home' && event.key !== 'End') return;
            event.preventDefault();
            var index = $tabs.index(this), next = event.key === 'Home' ? 0 : (event.key === 'End' ? $tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + $tabs.length) % $tabs.length);
            var $next = $tabs.eq(next); activateCommissionTab($next.data('commission-tab'), true);
        });
        var $noticeTab = $dashboard.find('[data-commission-notifications]');
        function refreshCommissionNotifications() {
            if (!$noticeTab.length || document.hidden || !window.zmp_ajax) return;
            $.post(zmp_ajax.ajax_url, {action:'zmp_commission_notifications', nonce:zmp_ajax.nonce}, function(response) {
                if (!response || !response.success) return;
                var count = Math.max(0, parseInt(response.data.count, 10) || 0), $badge = $noticeTab.find('.zmp-commission-tab__badge');
                $noticeTab.attr('data-notification-count', count);
                if (!$badge.length) { $badge = $('<span class="zmp-commission-tab__badge">').appendTo($noticeTab); }
                $badge.text(count.toLocaleString()).prop('hidden', count === 0).attr('aria-label', count + ' непрочитани или нерешени ставки');
            });
        }
        window.setInterval(refreshCommissionNotifications, 30000);
        $(document).on('visibilitychange', refreshCommissionNotifications);
        refreshCommissionNotifications();
    });

    // Role workspace tabs should switch in place like commission tabs, while their hrefs
    // remain complete server-rendered fallbacks for JS-disabled clients and direct links.
    $('[data-zmp-workspace]').each(function() {
        var $workspace = $(this), $tabs = $workspace.find('[data-workspace-tab]'), $panels = $workspace.find('[data-workspace-panel]');
        if (!$tabs.length || !$panels.length) return;
        var queryKey = String($workspace.attr('data-zmp-workspace-query') || '');
        function activateWorkspaceTab(key, moveFocus, updateUrl) {
            var $selected = $tabs.filter(function() { return String($(this).attr('data-workspace-tab')) === key; }).first();
            if (!$selected.length) $selected = $tabs.first();
            key = String($selected.attr('data-workspace-tab'));
            $tabs.each(function() {
                var active = String($(this).attr('data-workspace-tab')) === key;
                if (active) $(this).attr('aria-current', 'page'); else $(this).removeAttr('aria-current');
                if (active && moveFocus) this.focus();
            });
            $panels.each(function() { this.hidden = String($(this).attr('data-workspace-panel')) !== key; });
            if (updateUrl && window.history && window.history.pushState && $selected.attr('href')) {
                window.history.pushState({zmpWorkspaceTab:key}, '', $selected.attr('href'));
            }
        }
        $workspace.addClass('is-enhanced');
        $tabs.on('click', function(event) {
            if (!queryKey) return;
            event.preventDefault();
            activateWorkspaceTab(String($(this).attr('data-workspace-tab') || ''), false, true);
        });
        $tabs.on('keydown', function(event) {
            if (!['ArrowLeft','ArrowRight','Home','End'].includes(event.key)) return;
            event.preventDefault();
            var index = $tabs.index(this), next = event.key === 'Home' ? 0 : (event.key === 'End' ? $tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + $tabs.length) % $tabs.length);
            activateWorkspaceTab(String($tabs.eq(next).attr('data-workspace-tab')), true, false);
        });
        window.addEventListener('popstate', function() {
            var selected = queryKey ? new URLSearchParams(window.location.search).get(queryKey) : null;
            activateWorkspaceTab(selected || String($tabs.first().attr('data-workspace-tab') || ''), false, false);
        });
    });

    // 1. Server-authoritative timer; moderator pause/resume is enforced by heartbeat.
    var $timer = $('#zmp-countdown');
    if ($timer.length) {
        var secondsLeft = parseInt($timer.data('seconds'), 10) || 0;
        var attemptId = $('input[name="attempt_id"]').val();
        var spentSeconds = parseInt($timer.data('spent'), 10) || 0;
        var isPaused = String($timer.data('paused')) === '1';
        var timerInterval = null;
        var heartbeatInterval = null;

        function paintTimer() {
            var mm = Math.floor(secondsLeft / 60), ss = secondsLeft % 60;
            $timer.text(String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0'));
        }
        function setPaused(paused, serverRemaining) {
            if (typeof serverRemaining === 'number') secondsLeft = serverRemaining;
            if (isPaused === paused) { paintTimer(); return; }
            isPaused = paused;
            if (isPaused) {
                clearInterval(timerInterval);
                $('.zmp-answer-radio, #zmp-btn-submit').prop('disabled', true);
                if (!$('#zmp-moderator-pause-notice').length) {
                    $('<div id="zmp-moderator-pause-notice" class="zmp-alert zmp-alert-warning">Тестот е привремено паузиран од модератор. Времето е запрено.</div>').insertBefore('#zmp-exam-form');
                }
            } else {
                $('#zmp-moderator-pause-notice').remove();
                $('.zmp-answer-radio, #zmp-btn-submit').prop('disabled', false);
                startTimer();
            }
            paintTimer();
        }
        function submitExam() {
            if (isPaused) return;
            $('#zmp-btn-submit').prop('disabled', true).text('Се пресметуваат резултатите...');
            $.post(zmp_ajax.ajax_url, {action:'zmp_submit_exam',nonce:zmp_ajax.nonce,attempt_id:attemptId}, function(){window.location.reload();}).fail(function(){window.location.reload();});
        }
        function startTimer() {
            clearInterval(timerInterval);
            if (isPaused) return;
            timerInterval = setInterval(function() {
                if (secondsLeft <= 0) {
                    secondsLeft = 0; paintTimer(); clearInterval(timerInterval); submitExam(); return;
                }
                secondsLeft--; spentSeconds++; paintTimer();
            }, 1000);
        }
        function sendHeartbeat() {
            $.post(zmp_ajax.ajax_url, {action:'zmp_heartbeat',nonce:zmp_ajax.nonce,attempt_id:attemptId,remaining_seconds:secondsLeft,time_spent_seconds:spentSeconds}, function(res) {
                if (res && res.success && res.data) setPaused(!!res.data.paused, parseInt(res.data.remaining_seconds,10));
            });
        }
        paintTimer();
        if (!isPaused) startTimer();
        else setPaused(true, secondsLeft);
        sendHeartbeat();
        heartbeatInterval = setInterval(sendHeartbeat, 5000);
        window.addEventListener('beforeunload', function() {
            if (!navigator.sendBeacon) return;
            var formData = new FormData();
            formData.append('action','zmp_heartbeat'); formData.append('nonce',zmp_ajax.nonce);
            formData.append('attempt_id',attemptId); formData.append('remaining_seconds',secondsLeft);
            formData.append('time_spent_seconds',spentSeconds); navigator.sendBeacon(zmp_ajax.ajax_url,formData);
        });
        $('#zmp-btn-submit').on('click', function(e) {
            e.preventDefault();
            if (!isPaused && confirm('Дали сте сигурни дека сакате да го завршите тестирањето? По кликнување, поените автоматски ќе се пресметаат.')) submitExam();
        });
    }

    // 2. Инстантно автоматско зачувување на одговорот при клик (AJAX)
    $('.zmp-answer-radio').on('change', function() {
        var $this = $(this);
        var qId = $this.data('qid');
        var answerIdx = $this.val();
        var attemptId = $('input[name="attempt_id"]').val();

        $this.closest('.zmp-options-list').find('.zmp-option-item').removeClass('zmp-selected');
        $this.closest('.zmp-option-item').addClass('zmp-selected');

        $.ajax({
            url: zmp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zmp_save_answer',
                nonce: zmp_ajax.nonce,
                attempt_id: attemptId,
                question_id: qId,
                answer_index: answerIdx
            }
        });
    });

    // 3. Exam submission is handled by the guarded timer block above.

    // 4. ТЕСТАТОРИ: Пријава на дефект или итен проблем
    $('#zmp-btn-send-incident').on('click', function() {
        var $btn = $(this);
        var msg = $('#zmp_inc_msg').val();
        var subject = $('#zmp_inc_subject').val();
        if (!subject) { alert('Внесете предмет на дописот.'); return; }
        if (!msg) {
            alert('Ве молиме внесете опис на проблемот.');
            return;
        }

        $btn.prop('disabled', true).text('Се испраќа...');
        $('#zmp-incident-status').text('');

        $.ajax({
            url: zmp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zmp_submit_incident',
                nonce: zmp_ajax.nonce,
                subject: subject,
                incident_type: $('#zmp_inc_type').val(),
                priority: $('#zmp_inc_priority').val(),
                student_code: $('#zmp_inc_code').val(),
                message: msg
            },
            success: function(res) {
                $btn.prop('disabled', false).text('🚀 Испрати итна порака до модераторите');
                if (res.success) {
                    $('#zmp-incident-status').css('color', '#16a34a').text('✔ Пораката е успешно испратена до модераторите!');
                    $('#zmp_inc_msg').val('');
                    $('#zmp_inc_code').val('');
                    setTimeout(function() { window.location.reload(); }, 1500);
                } else {
                    $('#zmp-incident-status').css('color', '#dc2626').text('Грешка при испраќање.');
                }
            }
        });
    });

    // 5. ТЕСТАТОРИ: Рестартирање тест на ученик
    $(document).on('click', '.zmp-reset-trigger', function() {
        var attemptId = $(this).data('attempt');
        var code = $(this).data('code');
        var reason = prompt('Внесете причина за рестартирање на тестот за ученикот ' + code + ':');
        if (reason) {
            $.ajax({
                url: zmp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zmp_reset_student_test',
                    nonce: zmp_ajax.nonce,
                    attempt_id: attemptId,
                    reason: reason
                },
                success: function(res) {
                    if (res.success) {
                        alert(res.data.message);
                        window.location.reload();
                    } else {
                        alert('Грешка: ' + (res.data ? res.data.message : 'Неуспешно.'));
                    }
                }
            });
        }
    });

    // 6. МОДЕРАТОРИ: Одговор на дефект
    $('.zmp-mod-reply-btn').on('click', function() {
        var incId = $(this).data('id');
        var reply = $('#reply-msg-' + incId).val();
        if (!reply) {
            alert('Внесете одговор.');
            return;
        }

        $.ajax({
            url: zmp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zmp_reply_incident',
                nonce: zmp_ajax.nonce,
                incident_id: incId,
                reply: reply
            },
            success: function(res) {
                if (res.success) {
                    window.location.reload();
                }
            }
        });
    });

    $('.zmp-testator-complaint-btn').on('click', function() {
        var $button = $(this);
        var notes = prompt('Белешка за одлуката (по избор):', '');
        if (notes === null) { return; }
        $.post(zmp_ajax.ajax_url, {
            action: 'zmp_testator_decide_complaint', nonce: zmp_ajax.nonce,
            complaint_id: $button.data('id'), decision: $button.data('decision'), notes: notes
        }, function(response) {
            if (response.success) { window.location.reload(); }
            else { alert(response.data && response.data.message ? response.data.message : 'Одлуката не е зачувана.'); }
        });
    });

    function submitDisqualification(values) {
        values.action = 'zmp_disqualify_target';
        values.nonce = zmp_ajax.nonce;
        $.post(zmp_ajax.ajax_url, values, function(response) {
            if (response.success) { window.location.reload(); }
            else { alert(response.data && response.data.message ? response.data.message : 'Дисквалификацијата не е зачувана.'); }
        });
    }

    $('#zmp-btn-disqualify').on('click', function() {
        var form = $('#zmp-disqualify-form');
        if (!form[0].checkValidity()) { form[0].reportValidity(); return; }
        if (confirm('Да се запише оваа дисквалификација?')) {
            submitDisqualification({target_type: $('#zmp_disq_type').val(), target_name: $('#zmp_disq_name').val(), target_code: $('#zmp_disq_code').val(), school_name: $('#zmp_disq_school').val(), detailed_reason: $('#zmp_disq_reason').val()});
        }
    });

    $('.zmp-disqualify-trigger').on('click', function() {
        var $button = $(this);
        var reason = prompt('Внесете детална причина за дисквалификација на ' + $button.data('name') + ':');
        if (reason) {
            submitDisqualification({target_type: $button.data('type'), target_id: $button.data('id'), target_name: $button.data('name'), target_code: $button.data('code'), school_name: $button.data('school'), detailed_reason: reason});
        }
    });

    var $liveBody = $('#zmp-live-students-body');
    if ($liveBody.length && window.zmp_ajax) {
        function refreshLiveStudents() {
            if (document.hidden) return;
            $.post(zmp_ajax.ajax_url, {action:'zmp_moderator_live_students', nonce:zmp_ajax.nonce}, function(res) {
                if (!res || !res.success) return;
                var rows = res.data.map(function(s) {
                    var mm=Math.floor(s.remaining/60), ss=s.remaining%60, $tr=$('<tr>');
                    $('<td>').append($('<code>').text(s.code)).appendTo($tr);
                    $('<td>').append($('<strong>').text(s.name)).appendTo($tr);
                    $('<td>').text(s.school).appendTo($tr); $('<td>').text(s.grade+' одд.').appendTo($tr);
                    $('<td>').append($('<strong>').text(String(mm).padStart(2,'0')+':'+String(ss).padStart(2,'0'))).appendTo($tr);
                    $('<td>').append($('<small>').text(s.active)).appendTo($tr);
                    var $actions=$('<td>');
                    if (s.incident_id) {
                        [[s.paused?'resume':'pause',s.paused?'▶ Продолжи':'⏸ Паузирај'],['stop','⏹ Запри'],['restart','↻ Рестартирај']].forEach(function(a){$actions.append($('<button type="button" class="button zmp-attempt-control">').text(a[1]).attr({'data-action':a[0],'data-incident':s.incident_id,'data-attempt':s.id,'data-code':s.code}));});
                    } else {
                        $actions.append($('<small>').text('Чека допис од тестатор.'));
                    }
                    $actions.appendTo($tr);
                    return $tr;
                });
                $liveBody.empty();
                if(rows.length) rows.forEach(function(r){$liveBody.append(r);});
                else $liveBody.append($('<tr>').append($('<td colspan="7">').text('Во моментов нема активни ученици на тест.')));
                $('#zmp-live-students-count').text(rows.length);
            });
        }
        setInterval(refreshLiveStudents, 10000);
    }

    $(document).on('click', '.zmp-attempt-control', function() {
        var $button=$(this), action=$button.data('action'), code=$button.data('code')||'ученикот';
        if (action==='stop' && !confirm('Запирањето ќе го заклучи обидот. Само ново рестартирање ќе му овозможи продолжување. Да продолжам?')) return;
        var reason=prompt('Внесете проверливо образложение (најмалку 20 знаци) за '+action+' на '+code+':');
        if (!reason || reason.trim().length<20) { if(reason!==null)alert('Образложението мора да содржи најмалку 20 знаци.'); return; }
        $button.prop('disabled',true);
        $.post(zmp_ajax.ajax_url,{action:'zmp_moderator_control_attempt',nonce:zmp_ajax.nonce,attempt_id:$button.data('attempt'),incident_id:$button.data('incident')||0,control_action:action,reason:reason},function(res){
            if(res&&res.success){alert(res.data.message);window.location.reload();}
            else{alert(res&&res.data&&res.data.message?res.data.message:'Акцијата не успеа.');$button.prop('disabled',false);}
        });
    });

    $(document).on('click', '.zmp-commission-complaint-decision', function() {
        var $form=$(this).closest('form'), $button=$(this), rationale=$form.find('[name="rationale"]').val(), newScore=$form.find('[name="new_score"]').val(), questionRef=$form.find('[name="question_ref"]').val(), markingBasis=$form.find('[name="marking_basis"]').val();
        if(!questionRef||!questionRef.trim()||!markingBasis||!markingBasis.trim()||!rationale||rationale.trim().length<30){alert('Внесете референца за прашањето, основа за бодување и фактичко образложение од најмалку 30 знаци.');return;}
        if($button.data('outcome')==='accepted'&&!confirm('Да се прифати жалбата? Ако е внесен нов резултат, тој ќе го замени тековниот резултат и ќе остане запишан во ревизорската трага.'))return;
        if($button.data('outcome')==='rejected'&&!confirm('Да се одбие жалбата со внесеното образложение?'))return;
        $.post(zmp_ajax.ajax_url,{action:'zmp_commission_decide_complaint',nonce:zmp_ajax.nonce,complaint_id:$form.find('[name="complaint_id"]').val(),outcome:$button.data('outcome'),new_score:newScore,question_ref:questionRef,marking_basis:markingBasis,rationale:rationale},function(res){if(res&&res.success){alert(res.data.message);window.location.reload();}else alert(res&&res.data&&res.data.message?res.data.message:'Одлуката не успеа.');});
    });

    $(document).on('click', '.zmp-close-phase', function() {
        var $form=$(this).closest('form'), reason=$form.find('[name="reason"]').val();
        if(!reason||reason.trim().length<30){alert('Внесете фактичко образложение од најмалку 30 знаци.');return;}
        if(!confirm('Ова натпреварувачко ниво ќе се затвори, резултатите ќе се снимат и натпреварот ќе премине на следното натпреварувачко ниво. Дали продолжувате?'))return;
        $.post(zmp_ajax.ajax_url,{action:'zmp_commission_close_phase',nonce:zmp_ajax.nonce,phase:$form.find('[name="phase"]').val(),reason:reason},function(res){if(res&&res.success){alert(res.data.message);window.location.reload();}else alert(res&&res.data&&res.data.message?res.data.message:'Натпреварувачкото ниво не е затворена.');});
    });

    $(document).on('click', '.zmp-finalize-competition', function() {
        var $form=$(this).closest('form'), reason=$form.find('[name="reason"]').val();
        if(!reason||reason.trim().length<30){alert('Внесете завршно образложение од најмалку 30 знаци.');return;}
        if(!confirm('Ова ќе ја означи учебната година како целосно завршена и ќе овозможи ZIP архива и годишен reset. Да продолжам?'))return;
        $.post(zmp_ajax.ajax_url,{action:'zmp_commission_finalize_competition',nonce:zmp_ajax.nonce,reason:reason},function(res){if(res&&res.success){alert(res.data.message);window.location.reload();}else alert(res&&res.data&&res.data.message?res.data.message:'Годината не е поднесена.');});
    });

    var $resultsTable = $('.zmp-sortable-results');
    if ($resultsTable.length) {
        var $resultRows=$resultsTable.find('tbody tr').filter(function(){return $(this).find('td').length>1;}), pageSize=parseInt($resultsTable.data('page-size'),10)||25, page=0;
        $resultsTable.find('thead th').attr('tabindex','0').css('cursor','pointer').on('click keypress',function(e){
            if(e.type==='keypress'&&e.which!==13)return;
            var col=$(this).index(), asc=!$(this).data('asc'); $(this).data('asc',asc);
            var sorted=$resultRows.get().sort(function(a,b){var x=$.trim($(a).children().eq(col).text()),y=$.trim($(b).children().eq(col).text()),nx=parseFloat(x.replace(/[^0-9.-]/g,'')),ny=parseFloat(y.replace(/[^0-9.-]/g,''));var cmp=(!isNaN(nx)&&!isNaN(ny))?nx-ny:x.localeCompare(y);return asc?cmp:-cmp;});
            $resultRows=$(sorted);page=0;drawResults();
        });
        function drawResults(){var pages=Math.max(1,Math.ceil($resultRows.length/pageSize));$resultRows.hide().slice(page*pageSize,(page+1)*pageSize).show();$('[data-results-page]').text((page+1)+' / '+pages);$('[data-results-prev]').prop('disabled',page===0);$('[data-results-next]').prop('disabled',page>=pages-1);}
        $('[data-results-prev]').on('click',function(){if(page>0){page--;drawResults();}});$('[data-results-next]').on('click',function(){if((page+1)*pageSize<$resultRows.length){page++;drawResults();}});drawResults();
    }

    // 7. НАСТАВНИЦИ: Поднесување жалба во рок од 24 часа
    $('#zmp-btn-submit-complaint').on('click', function() {
        var studentId = $('#zmp_complaint_student').val();
        var subject   = $('#zmp_complaint_subject').val();
        var text      = $('#zmp_complaint_text').val();

        if (!studentId || !subject || !text) {
            alert('Ве молиме пополнете ги сите полиња за жалбата.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Се поднесува...');
        $('#zmp-complaint-msg').text('');

        $.ajax({
            url: zmp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zmp_submit_complaint',
                nonce: zmp_ajax.nonce,
                student_id: studentId,
                subject: subject,
                complaint_text: text
            },
            success: function(res) {
                $btn.prop('disabled', false).text('Поднеси официјална жалба →');
                if (res.success) {
                    $('#zmp-complaint-msg').css('color', '#16a34a').text('✔ ' + res.data.message);
                    $('#zmp_complaint_subject').val('');
                    $('#zmp_complaint_text').val('');
                    setTimeout(function() { window.location.reload(); }, 1500);
                } else {
                    $('#zmp-complaint-msg').css('color', '#dc2626').text(res.data ? res.data.message : 'Грешка.');
                }
            }
        });
    });

});
