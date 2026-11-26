(function () {
    'use strict';

    var containers = document.querySelectorAll('.zmp-president-public-display[data-endpoint][data-action]');
    if (!containers.length || typeof window.fetch !== 'function') {
        return;
    }

    function isDate(value) {
        if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return false;
        }
        var parts = value.split('-').map(Number);
        var date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
        return date.getUTCFullYear() === parts[0]
            && date.getUTCMonth() === parts[1] - 1
            && date.getUTCDate() === parts[2];
    }

    function validPresident(value) {
        return value && typeof value === 'object'
            && typeof value.display_name === 'string'
            && value.display_name.trim() !== ''
            && isDate(value.start_date)
            && isDate(value.end_date);
    }

    function sameOriginImageUrl(value) {
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

    function createPresidentCard(president, termLabel) {
        var card = document.createElement('article');
        card.className = 'zmp-president-public-display__card';
        card.setAttribute('role', 'listitem');

        var photoUrl = sameOriginImageUrl(president.photo_url);
        if (photoUrl) {
            card.classList.add('has-photo');
            var photo = document.createElement('img');
            photo.className = 'zmp-president-public-display__photo';
            photo.src = photoUrl;
            photo.alt = 'Профилна слика на ' + president.display_name;
            photo.loading = 'lazy';
            photo.decoding = 'async';
            photo.referrerPolicy = 'no-referrer';
            photo.addEventListener('error', function () {
                photo.remove();
            }, { once: true });
            card.appendChild(photo);
        }

        var details = document.createElement('div');
        details.className = 'zmp-president-public-display__details';

        var name = document.createElement('p');
        name.className = 'zmp-president-public-display__name';
        var strong = document.createElement('strong');
        strong.textContent = president.display_name;
        name.appendChild(strong);

        var term = document.createElement('p');
        term.className = 'zmp-president-public-display__term';
        term.textContent = termLabel
            .replace('%1$s', president.start_date)
            .replace('%2$s', president.end_date);

        details.appendChild(name);
        details.appendChild(term);
        card.appendChild(details);
        return card;
    }

    containers.forEach(function (container) {
        var endpoint = container.getAttribute('data-endpoint');
        var action = container.getAttribute('data-action');
        if (!endpoint || !action) {
            return;
        }

        var termLabel = container.getAttribute('data-term-label') || '';
        var transitionLabel = container.getAttribute('data-transition-label') || '';
        var body = new URLSearchParams();
        body.set('action', action);

        window.fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('President display request failed.');
            }
            return response.json();
        }).then(function (payload) {
            if (!payload || payload.success !== true || !payload.data || typeof payload.data !== 'object') {
                return;
            }

            var data = payload.data;
            var list = container.querySelector('.zmp-president-public-display__list');
            var legacyName = container.querySelector('.zmp-president-public-display__name strong');
            var legacyTerm = container.querySelector('.zmp-president-public-display__term');
            var transition = container.querySelector('.zmp-president-public-display__transition');
            if (legacyName) { legacyName.textContent = ''; }
            if (legacyTerm) { legacyTerm.textContent = ''; }
            if (transition) { transition.textContent = ''; }
            if (list) {
                list.setAttribute('role', 'list');
                list.replaceChildren();
            }

            var presidents = Array.isArray(data.presidents)
                ? data.presidents.filter(validPresident).slice(0, 2)
                : [];

            if (data.state === 'president' && presidents.length && list) {
                presidents.forEach(function (president) {
                    list.appendChild(createPresidentCard(president, termLabel));
                });
                if (legacyName && legacyName.parentElement) { legacyName.parentElement.hidden = true; }
                if (legacyTerm) { legacyTerm.hidden = true; }
                container.hidden = false;
            } else if (data.state === 'president'
                && validPresident(data)
                && legacyName
                && legacyTerm) {
                // Compatibility for older shells and response payloads.
                legacyName.textContent = data.display_name;
                legacyTerm.textContent = termLabel
                    .replace('%1$s', data.start_date)
                    .replace('%2$s', data.end_date);
                if (legacyName.parentElement) { legacyName.parentElement.hidden = false; }
                legacyTerm.hidden = false;
                container.hidden = false;
            } else if (data.state === 'transition' && transition) {
                transition.textContent = transitionLabel;
                container.hidden = false;
            } else if (data.state === 'hidden') {
                container.hidden = true;
            }
        }).catch(function () {
            // Fail closed: the neutral shell remains hidden if the current state cannot be fetched.
            container.hidden = true;
        });
    });
}());
