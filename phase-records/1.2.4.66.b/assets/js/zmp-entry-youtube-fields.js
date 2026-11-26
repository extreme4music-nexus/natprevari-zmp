(function () {
    'use strict';

    var form = document.querySelector('.zmp-category-registration form.zmp-form');
    if (!form || form.dataset.zmpVideoAddon === '1') return;

    var fieldset = form.querySelector('#zmp-entry-video-fields');
    var slots = fieldset && fieldset.querySelector('.zmp-entry-video-slots');
    var guidance = fieldset && fieldset.querySelector('.zmp-entry-video-guidance');
    var progress = fieldset && fieldset.querySelector('.zmp-entry-video-progress');
    var category = form.querySelector('[name="category_id"]');
    var school = form.querySelector('[name="mentor_school_id"]');
    if (!fieldset || !slots || !category || !school) return;

    var config = {};
    try {
        config = JSON.parse(fieldset.getAttribute('data-config') || '{}');
    } catch (error) {
        config = {};
    }
    form.dataset.zmpVideoAddon = '1';

    var definitions = {
        vocal_interpretation: [['performance', 'Настап']],
        school_choir: [['required', 'Задолжителна композиција'], ['optional', 'Изборна композиција']],
        school_orchestra: [['required', 'Задолжителна композиција'], ['optional', 'Изборна композиција']],
        school_bands: [['optional_one', 'Изборна композиција 1'], ['optional_two', 'Изборна композиција 2']]
    };

    function refresh() {
        var selectedCategory = category.options[category.selectedIndex];
        var code = selectedCategory ? selectedCategory.dataset.code : '';
        var categorySlots = definitions[code];
        var schoolModes = (config.modes || {})[school.value] || {};
        var mode = schoolModes[category.value] || 'unconfigured';
        slots.replaceChildren();
        progress.textContent = '';

        if (!categorySlots) {
            fieldset.hidden = true;
            return;
        }

        fieldset.hidden = false;
        if (mode === 'online') {
            guidance.textContent = 'Онлајн регионално ниво: видеата се задолжителни. Пријавата останува нацрт додека сите видеа не се прикачат и проверат. Може да ги изберете сега или подоцна во „Мои пријави“. Видеата се Unlisted и секој со линкот може да ги гледа.';
        } else if (mode === 'physical') {
            guidance.textContent = 'Физичко регионално ниво: видеата се изборни; пријавата може да се поднесе и без нив.';
        } else {
            guidance.textContent = 'Начинот на регионалното натпреварување не е поставен. Секцијата е прикажана, но изборот на видео ќе се овозможи откако модераторот ќе го постави начинот.';
        }
        if (!config.connected) guidance.textContent += ' YouTube каналот не е поврзан, па upload нема да биде достапен додека администраторот не го поврзе.';

        categorySlots.forEach(function (item) {
            var label = document.createElement('label');
            label.className = 'zmp-entry-video-slot';
            var mandatoryOnline = mode === 'online' && (item[0] === 'performance' || item[0] === 'required');
            label.textContent = item[1] + (mandatoryOnline ? ' *' : (mode === 'physical' || (mode === 'online' && !mandatoryOnline) ? ' (по избор)' : ''));

            var input = document.createElement('input');
            input.type = 'file';
            input.name = 'zmp_initial_video_' + item[0];
            input.accept = 'video/mp4,video/quicktime,video/webm,video/x-msvideo,.mp4,.m4v,.mov,.webm,.avi,.mpeg,.mpg';
            input.dataset.slot = item[0];
            input.dataset.mode = mode;
            input.disabled = mode === 'unconfigured';
            label.appendChild(input);
            slots.appendChild(label);
        });
    }

    category.addEventListener('change', refresh);
    school.addEventListener('change', refresh);
    refresh();

    function setStatus(message) {
        progress.textContent = message;
    }

    function uploadVideo(uploadForm, file) {
        return new Promise(function (resolve, reject) {
            var formData = new FormData(uploadForm);
            formData.set('youtube_video', file, file.name);
            var request = new XMLHttpRequest();
            request.open('POST', uploadForm.action);
            request.withCredentials = true;
            request.upload.onprogress = function (event) {
                if (event.lengthComputable) setStatus('Прикачување на видео: ' + Math.round(event.loaded / event.total * 100) + '%');
            };
            request.onload = function () {
                if (request.status >= 200 && request.status < 400) resolve(request.responseURL || uploadForm.action);
                else reject(new Error('upload-http-' + request.status));
            };
            request.onerror = function () { reject(new Error('upload-network')); };
            request.send(formData);
        });
    }

    form.addEventListener('submit', function (event) {
        var selected = Array.prototype.slice.call(slots.querySelectorAll('input[type="file"]')).filter(function (input) {
            return input.files && input.files.length;
        });
        if (!selected.length) return;
        if (!config.connected) {
            setStatus('YouTube каналот не е поврзан. Отстранете ги избраните видеа; пријавата може да ја испратите без изборни видеа.');
            selected.forEach(function (input) { input.value = ''; });
            return;
        }

        event.preventDefault();
        var button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        setStatus('Ја зачувувам пријавата и подготвувам безбеден upload…');

        var participant = form.querySelector('[name="participant_name"]');
        var expectedName = participant ? participant.value.trim() : '';
        var selectedOption = category.options[category.selectedIndex];
        var expectedCategory = selectedOption ? selectedOption.textContent.trim() : '';
        var registrationData = new FormData(form);
        selected.forEach(function (input) { registrationData.delete(input.name); });

        fetch(form.action || window.location.href, {
            method: 'POST', body: registrationData, credentials: 'same-origin', redirect: 'follow'
        }).then(function (response) {
            if (!response.ok) throw new Error('registration-http-' + response.status);
            return {url: response.url};
        }).then(async function (result) {
            var entryUrl = new URL(result.url, window.location.href);
            entryUrl.searchParams.set('zmp_mentor_tab', 'students');
            entryUrl.searchParams.delete('tab');
            var entriesPageUrl = entryUrl.href;
            var entriesResponse = await fetch(entriesPageUrl, {credentials: 'same-origin', redirect: 'follow'});
            if (!entriesResponse.ok) throw new Error('entries-http-' + entriesResponse.status);
            var entriesDocument = new DOMParser().parseFromString(await entriesResponse.text(), 'text/html');
            var rows = Array.prototype.slice.call(entriesDocument.querySelectorAll('.zmp-table tbody tr'));
            var row = rows.find(function (item) {
                var cells = item.querySelectorAll('td');
                return cells.length > 8 && cells[0].textContent.trim().indexOf(expectedCategory) !== -1 && cells[2].textContent.trim().indexOf(expectedName) !== -1;
            });
            if (!row) throw new Error('entry-row-not-found');
            var uploadForms = Array.prototype.slice.call(row.querySelectorAll('td:nth-child(9) .zmp-youtube-upload-form'));
            for (var i = 0; i < selected.length; i++) {
                var input = selected[i];
                var uploadForm = uploadForms.find(function (item) {
                    var slot = item.querySelector('[name="slot_key"]');
                    return slot && slot.value === input.dataset.slot;
                });
                if (!uploadForm) throw new Error('upload-slot-unavailable');
                setStatus('Се прикачува: ' + input.files[0].name);
                await uploadVideo(uploadForm, input.files[0]);
            }
            setStatus('Видеата се испратени. Го отворам статусот на пријавата…');
            window.location.href = entriesPageUrl;
        }).catch(function () {
            setStatus('Пријавата можеби е зачувана како нацрт. Отворете „Мои пријави“ и проверете го статусот пред нов обид.');
            if (button) button.disabled = false;
        });
    });
})();
