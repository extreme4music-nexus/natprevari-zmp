(function () {
    'use strict';

    var fields = document.querySelectorAll('.zmp-president-public-photo-field');
    if (!fields.length) {
        return;
    }

    function localImageUrl(value) {
        if (typeof value !== 'string' || value.trim() === '') {
            return '';
        }
        try {
            var parsed = new window.URL(value, window.location.href);
            if (parsed.origin !== window.location.origin
                || (parsed.protocol !== 'https:' && parsed.protocol !== 'http:')
                || parsed.username !== ''
                || parsed.password !== '') {
                return '';
            }
            return parsed.href;
        } catch (error) {
            return '';
        }
    }

    fields.forEach(function (field, index) {
        var input = field.querySelector('#zmp-president-public-photo-attachment-id');
        var selectButton = field.querySelector('.zmp-president-photo-select');
        var removeButton = field.querySelector('.zmp-president-photo-remove');
        var preview = field.querySelector('.zmp-president-photo-preview');
        if (!input || !selectButton || !removeButton || !preview) {
            return;
        }

        var status = field.querySelector('.zmp-president-photo-status');
        if (!status) {
            status = document.createElement('p');
            status.className = 'zmp-president-photo-status';
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            field.appendChild(status);
        }
        status.classList.remove('screen-reader-text');
        if (!status.id) {
            status.id = 'zmp-president-photo-status-' + String(index + 1);
        }
        selectButton.setAttribute('aria-describedby', status.id);
        removeButton.setAttribute('aria-describedby', status.id);

        var initialPreview = localImageUrl(preview.getAttribute('src') || '');
        if (initialPreview) {
            preview.src = initialPreview;
            preview.alt = 'Преглед на избраната профилна слика';
            preview.hidden = false;
        } else {
            preview.removeAttribute('src');
            preview.hidden = true;
        }

        function report(message) {
            status.textContent = message;
        }

        function clearSelection() {
            input.value = '';
            preview.removeAttribute('src');
            preview.hidden = true;
            report('Избраната слика е отстранета.');
        }

        selectButton.addEventListener('click', function (event) {
            event.preventDefault();
            if (!window.wp || !window.wp.media) {
                report('Библиотеката за слики не е достапна. Освежете ја страницата и обидете се повторно.');
                return;
            }

            var frame;
            try {
                frame = window.wp.media({
                    title: 'Изберете профилна слика',
                    button: { text: 'Користи ја оваа слика' },
                    library: { type: 'image' },
                    multiple: false
                });
            } catch (error) {
                report('Библиотеката за слики не можеше да се отвори. Обидете се повторно.');
                return;
            }

            frame.on('select', function () {
                var selected = frame.state().get('selection').first();
                var attachment = selected && selected.toJSON ? selected.toJSON() : null;
                var attachmentId = attachment && Number(attachment.id);
                var imageUrl = attachment ? localImageUrl(attachment.url || '') : '';
                var isImage = attachment && (attachment.type === 'image'
                    || (typeof attachment.mime === 'string' && attachment.mime.indexOf('image/') === 0));

                if (!attachment || !Number.isInteger(attachmentId) || attachmentId < 1 || !isImage || !imageUrl) {
                    report('Изберете локална слика од медиумската библиотека.');
                    selectButton.focus();
                    return;
                }

                input.value = String(attachmentId);
                preview.src = imageUrl;
                preview.alt = 'Преглед на избраната профилна слика';
                preview.hidden = false;
                report('Профилната слика е избрана. Зачувајте го профилот за да ја примените промената.');
                selectButton.focus();
            });

            frame.on('close', function () {
                selectButton.focus();
            });
            frame.open();
        });

        removeButton.addEventListener('click', function (event) {
            event.preventDefault();
            clearSelection();
            selectButton.focus();
        });

        preview.addEventListener('error', function () {
            preview.removeAttribute('src');
            preview.hidden = true;
            report('Прегледот на сликата не е достапен. Изберете друга слика или отстранете ја.');
        });
    });
}());
