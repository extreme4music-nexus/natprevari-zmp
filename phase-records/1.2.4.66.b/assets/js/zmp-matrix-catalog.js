(function () {
    'use strict';

    var root = document.querySelector('.zmp-matrix-picker');
    if (!root || !window.zmpMatrixCatalog) return;
    var config = window.zmpMatrixCatalog;
    var dialog = document.getElementById('zmp-matrix-dialog');
    var results = document.getElementById('zmp-matrix-results');
    var search = document.getElementById('zmp-matrix-search');
    var status = document.getElementById('zmp-matrix-status');
    var more = document.getElementById('zmp-matrix-more');
    var selected = document.getElementById('zmp-matrix-id');
    var selectedLabel = document.getElementById('zmp-matrix-selected');
    var openButton = document.getElementById('zmp-matrix-open');
    var closeButton = document.getElementById('zmp-matrix-close');
    var refreshButton = document.getElementById('zmp-matrix-refresh');
    var uploadOpenButton = document.getElementById('zmp-matrix-upload-open');
    var uploadButton = document.getElementById('zmp-matrix-upload-submit');
    var uploadPanel = document.getElementById('zmp-matrix-upload-panel');
    var category = document.getElementById('zmp-entry-category');
    var form = root.closest('form');
    var ttl = (Number(config.cacheTtl) || 300) * 1000;
    var timer = 0;
    var page = 0;
    var requestSequence = 0;
    var previewSequence = 0;
    var activeAudio = null;
    var opener = null;

    function categoryId() { return category ? category.value : '0'; }
    function cacheKey(query, pageNumber) {
        return 'zmp-matrix-48:' + categoryId() + ':' + encodeURIComponent(query) + ':' + pageNumber;
    }
    function readCache(query, pageNumber) {
        try {
            var cached = JSON.parse(sessionStorage.getItem(cacheKey(query, pageNumber)) || 'null');
            if (cached && cached.expires > Date.now() && Array.isArray(cached.items)) return cached;
        } catch (error) {}
        return null;
    }
    function writeCache(query, pageNumber, data) {
        try {
            sessionStorage.setItem(cacheKey(query, pageNumber), JSON.stringify({ expires: Date.now() + ttl, items: data.items, has_more: !!data.has_more }));
        } catch (error) {}
    }
    function clearCache() {
        try {
            Object.keys(sessionStorage).forEach(function (key) {
                if (key.indexOf('zmp-matrix-48:') === 0) sessionStorage.removeItem(key);
            });
        } catch (error) {}
    }
    function request(action, values) {
        var body = new FormData();
        body.append('action', action);
        body.append('_ajax_nonce', config.nonce);
        Object.keys(values || {}).forEach(function (key) { body.append(key, values[key]); });
        return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body }).then(function (response) { return response.json(); });
    }
    function setUpload(enabled) {
        if (!uploadPanel) return;
        uploadPanel.hidden = !enabled;
        uploadPanel.querySelectorAll('input,button,select,textarea').forEach(function (element) { element.disabled = !enabled; });
    }
    function stopAudio() {
        if (!activeAudio) return;
        activeAudio.pause();
        activeAudio.removeAttribute('src');
        activeAudio.load();
        activeAudio.hidden = true;
        activeAudio = null;
    }
    function focusableElements() {
        return Array.prototype.slice.call(dialog.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),audio[controls]'))
            .filter(function (element) {
                return !element.closest('[hidden]') && element.getAttribute('aria-hidden') !== 'true' && window.getComputedStyle(element).visibility !== 'hidden';
            });
    }
    function closeDialog() {
        window.clearTimeout(timer);
        timer = 0;
        requestSequence += 1;
        previewSequence += 1;
        dialog.hidden = true;
        results.removeAttribute('aria-busy');
        setUpload(false);
        stopAudio();
        if (opener && document.contains(opener)) opener.focus();
    }
    function syncCategory() {
        var option = category && category.options[category.selectedIndex];
        var needed = !!(option && option.dataset.type === 'individual');
        if (!needed) {
            selected.value = '';
            if (!dialog.hidden) {
                closeDialog();
                var focusTarget = document.getElementById('zmp-registration-category') || category;
            if (focusTarget && !focusTarget.disabled && !focusTarget.hidden && !(focusTarget.closest && focusTarget.closest('[hidden]'))) {
                focusTarget.focus();
            } else if (category && !category.disabled && !category.hidden && !(category.closest && category.closest('[hidden]'))) {
                category.focus();
            }
            }
            setUpload(false);
        }
        root.hidden = !needed;
        return needed;
    }
    function render(items, append) {
        if (!append) { stopAudio(); results.replaceChildren(); }
        if (!items.length && !append) {
            results.textContent = 'Нема матрици во каталогот. Побарајте администраторот да го освежи каталогот од Drive.';
            return;
        }
        items.forEach(function (item) {
            var row = document.createElement('article');
            var title = document.createElement('strong');
            var play = document.createElement('button');
            var choose = document.createElement('button');
            var audio = document.createElement('audio');
            row.className = 'zmp-matrix-result';
            title.textContent = item.title + (item.artist ? ' — ' + item.artist : '');
            play.type = 'button';
            play.className = 'zmp-btn zmp-btn-secondary';
            play.textContent = 'Преслушај';
            play.setAttribute('aria-label', 'Преслушај: ' + title.textContent);
            choose.type = 'button';
            choose.className = 'zmp-btn zmp-btn-primary';
            choose.textContent = 'Избери';
            audio.controls = true;
            audio.preload = 'none';
            audio.hidden = true;
            audio.setAttribute('aria-label', 'Привремен плеер: ' + title.textContent);
            play.addEventListener('click', function () {
                var previewRequest = ++previewSequence;
                var requestedCategory = categoryId();
                status.textContent = 'Се подготвува плеерот...';
                request('zmp_matrix_preview_url', { id: String(item.id), category_id: requestedCategory })
                    .then(function (response) {
                        if (previewRequest !== previewSequence || dialog.hidden || requestedCategory !== categoryId()) return;
                        if (!response.success) throw new Error('Матрицата моментално не може да се отвори.');
                        stopAudio();
                        activeAudio = audio;
                        audio.hidden = false;
                        audio.src = response.data.url;
                        audio.load();
                        status.textContent = 'Плеерот е подготвен.';
                        var playback = audio.play();
                        if (playback && playback.catch) playback.catch(function () {
                            status.textContent = 'Притиснете Play во плеерот за да започне преслушувањето.';
                        });
                    })
                    .catch(function (error) {
                        if (previewRequest === previewSequence && !dialog.hidden) status.textContent = error.message;
                    });
            });
            choose.addEventListener('click', function () {
                selected.value = String(item.id);
                selectedLabel.textContent = 'Избрана матрица: ' + title.textContent;
                closeDialog();
            });
            row.append(title, play, choose, audio);
            results.appendChild(row);
        });
    }
    function load(force, append) {
        var query = search.value.trim();
        var pageNumber = append ? page + 1 : 0;
        var requestedCategory = categoryId();
        var cached = !force && !append ? readCache(query, pageNumber) : null;
        var sequence = ++requestSequence;
        if (cached) {
            page = pageNumber;
            render(cached.items, false);
            more.hidden = !cached.has_more;
            results.removeAttribute('aria-busy');
            status.textContent = 'Резултатите се од привремениот кеш. Освежете за најновиот каталог.';
            return;
        }
        status.textContent = 'Се пребарува каталогот...';
        results.setAttribute('aria-busy', 'true');
        request('zmp_matrix_search', { q: query, category_id: requestedCategory, page: String(pageNumber) })
            .then(function (response) {
                if (sequence !== requestSequence || query !== search.value.trim() || requestedCategory !== categoryId()) return;
                if (!response.success) throw new Error(response.data && response.data.message || 'Каталогот моментално не е достапен.');
                var data = response.data || {};
                if (!append) writeCache(query, pageNumber, data);
                page = pageNumber;
                render(data.items || [], append);
                more.hidden = !data.has_more;
                status.textContent = (data.items || []).length
                    ? 'Прикажани се резултатите. Новите Drive-файлови ги внесува администраторот со „Освежи каталог“. '
                    : 'Нема резултати за ова пребарување.';
            })
            .catch(function (error) {
                if (sequence === requestSequence) status.textContent = error.message;
            })
            .finally(function () {
                if (sequence === requestSequence) results.removeAttribute('aria-busy');
            });
    }
    function openDialog(upload) {
        opener = document.activeElement;
        setUpload(!!upload);
        dialog.hidden = false;
        if (upload) document.getElementById('zmp-matrix-title-input').focus();
        else { search.focus(); load(false, false); }
    }

    dialog.setAttribute('tabindex', '-1');
    openButton.addEventListener('click', function () { openDialog(false); });
    uploadOpenButton.addEventListener('click', function () { openDialog(true); });
    closeButton.addEventListener('click', closeDialog);
    dialog.addEventListener('click', function (event) { if (event.target === dialog) closeDialog(); });
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { event.preventDefault(); closeDialog(); return; }
        if (event.key !== 'Tab') return;
        var focusable = focusableElements();
        if (!focusable.length) { event.preventDefault(); dialog.focus(); return; }
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) { event.preventDefault(); first.focus(); }
    });
    if (category) {
        category.addEventListener('change', function () {
            previewSequence += 1;
            stopAudio();
            selected.value = '';
            syncCategory();
            if (!root.hidden && !dialog.hidden) {
                page = 0;
                results.replaceChildren();
                more.hidden = true;
                requestSequence += 1;
                results.removeAttribute('aria-busy');
            }
        });
        syncCategory();
    }
    search.addEventListener('input', function () {
        requestSequence += 1;
        results.removeAttribute('aria-busy');
        window.clearTimeout(timer);
        timer = window.setTimeout(function () { page = 0; load(false, false); }, 350);
    });
    refreshButton.addEventListener('click', function () { clearCache(); page = 0; load(true, false); });
    more.addEventListener('click', function () { load(true, true); });
    if (form) form.addEventListener('submit', function (event) {
        var option = category && category.options[category.selectedIndex];
        if (option && option.dataset.type === 'individual' && !selected.value) {
            event.preventDefault();
            alert('За вокален интерпретатор изберете матрица од каталогот.');
        }
    });
    document.getElementById('zmp-matrix-file').addEventListener('change', function () {
        var input = this;
        var file = input.files[0];
        if (!file) return;
        var audio = document.createElement('audio');
        audio.preload = 'metadata';
        audio.onloadedmetadata = function () {
            input.dataset.duration = String(Math.min(3600, Math.round(audio.duration || 0)));
            URL.revokeObjectURL(audio.src);
        };
        audio.src = URL.createObjectURL(file);
    });
    uploadButton.addEventListener('click', function () {
        var input = document.getElementById('zmp-matrix-file');
        var file = input.files[0];
        var uploadStatus = document.getElementById('zmp-matrix-upload-status');
        if (!file) { uploadStatus.textContent = 'Изберете аудио фајл.'; return; }
        var maxAudioBytes = Number(config.maxAudioBytes) || 0;
        if (maxAudioBytes > 0 && file.size > maxAudioBytes) {
            uploadStatus.textContent = 'Фајлот ја надминува тековната серверски дозволена големина од ' + (config.maxAudioLabel || 'дозволениот лимит') + '.';
            return;
        }
        var body = new FormData();
        body.append('action', 'zmp_matrix_upload');
        body.append('_ajax_nonce', config.nonce);
        body.append('title', document.getElementById('zmp-matrix-title-input').value);
        body.append('artist', document.getElementById('zmp-matrix-artist').value);
        body.append('rights_note', document.getElementById('zmp-matrix-rights').value);
        body.append('language', document.getElementById('zmp-matrix-language').value);
        body.append('duration_seconds', input.dataset.duration || '0');
        body.append('category_id', categoryId());
        body.append('file', file);
        uploadStatus.textContent = 'Се прикачува...';
        uploadButton.disabled = true;
        fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (!response.success) throw new Error(response.data && response.data.message || 'Прикачувањето не успеа.');
                selected.value = String(response.data.id);
                selectedLabel.textContent = 'Избрана матрица: ' + response.data.title;
                uploadStatus.textContent = 'Матрицата е зачувана приватно во Drive.';
                clearCache();
                closeDialog();
            })
            .catch(function (error) { uploadStatus.textContent = error.message; })
            .finally(function () { uploadButton.disabled = false; });
    });
}());

