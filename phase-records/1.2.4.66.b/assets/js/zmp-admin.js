jQuery(document).ready(function($) {
    // Вградување на WordPress Media Uploader за прикачување аудио и нотни слики
    $('.zmp-upload-media').on('click', function(e) {
        e.preventDefault();
        var targetInput = $($(this).data('target'));

        var customUploader = wp.media({
            title: 'Изберете или прикачете мултимедијален фајл (Аудио или Слика)',
            button: {
                text: 'Искористи го овој фајл'
            },
            multiple: false
        });

        customUploader.on('select', function() {
            var attachment = customUploader.state().get('selection').first().toJSON();
            targetInput.val(attachment.url);
        });

        customUploader.open();
    });
});

// Keep the administrator release notes in one small, accessible dropdown.
jQuery(function() {
    document.querySelectorAll('[data-zmp-whats-new]').forEach(function(widget) {
        var select = widget.querySelector('[data-zmp-whats-new-select]');
        var panels = widget.querySelectorAll('[data-zmp-whats-new-version]');
        if (!select || !panels.length) { return; }

        function showReleaseNotes() {
            panels.forEach(function(panel) {
                panel.hidden = panel.getAttribute('data-zmp-whats-new-version') !== select.value;
            });
        }

        select.addEventListener('change', showReleaseNotes);
        showReleaseNotes();
    });
});

// Give the approved-audio catalog a per-tool tab layout on narrow screens.
(function() {
    var labels = ['Алатка', 'Функција', 'Автоматска проверка', 'Рачна одлука и белешка', 'Проверени услови', 'Дејства'];
    var nextCardId = 0;
    var catalogs = [];
    var mobileQuery = window.matchMedia('(max-width: 782px)');

    function panelHasControls(panel) {
        return !!panel.querySelector('a[href],button,input,select,textarea,[tabindex]:not([tabindex="-1"])');
    }

    function focusElement(element) {
        if (!element || typeof element.focus !== 'function') { return; }
        try { element.focus({ preventScroll: true }); }
        catch (error) { element.focus(); }
    }

    function firstFocusable(cell) {
        return cell && cell.querySelector('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])');
    }

    function recordForFocus(item, active) {
        return item.tabRecords.findIndex(function(record, index) {
            return record.tab === active || record.panel.contains(active) || record.content.contains(active) || item.cells[index].contains(active);
        });
    }

    function selectTab(tab, focusTab) {
        var card = tab.closest('.zmp-audio-tools-mobile-card');
        var tabs = Array.prototype.slice.call(card.querySelectorAll('[role="tab"]'));
        tabs.forEach(function(item) {
            var selected = item === tab;
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
            item.tabIndex = selected ? 0 : -1;
            var panel = document.getElementById(item.getAttribute('aria-controls'));
            if (panel) {
                panel.hidden = !selected;
                panel.tabIndex = panelHasControls(panel) ? -1 : 0;
            }
        });
        if (focusTab) { tab.focus(); }
    }

    function buildCard(catalog, row, rowIndex) {
        var cells = Array.prototype.slice.call(row.children).filter(function(cell) {
            return cell.tagName === 'TD';
        });
        if (cells.length !== 6) {
            var messageCard = document.createElement('div');
            messageCard.className = 'zmp-audio-tools-mobile-card zmp-audio-tools-mobile-card--message';
            var message = document.createElement('p');
            message.textContent = cells.length ? cells[0].textContent.trim() : '';
            messageCard.appendChild(message);
            catalog.cards.appendChild(messageCard);
            return { row: row, cells: cells, card: messageCard, isMessage: true };
        }

        var card = document.createElement('section');
        card.className = 'zmp-audio-tools-mobile-card';
        var title = document.createElement('h3');
        title.className = 'zmp-audio-tools-mobile-card__title';
        title.textContent = (cells[0].querySelector('strong') || cells[0]).textContent.trim();
        card.appendChild(title);

        var tablist = document.createElement('div');
        tablist.className = 'zmp-audio-tools-mobile-card__tabs';
        tablist.setAttribute('role', 'tablist');
        tablist.setAttribute('aria-label', 'Секции за ' + title.textContent);
        card.appendChild(tablist);
        var tabRecords = [];
        var cardKey = 'zmp-audio-mobile-' + (++nextCardId);

        cells.forEach(function(cell, cellIndex) {
            var tabId = cardKey + '-tab-' + cellIndex;
            var panelId = cardKey + '-panel-' + cellIndex;
            var tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'zmp-audio-tools-mobile-card__tab';
            tab.id = tabId;
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', panelId);
            tab.setAttribute('aria-selected', cellIndex === 0 ? 'true' : 'false');
            tab.tabIndex = cellIndex === 0 ? 0 : -1;
            tab.textContent = labels[cellIndex];

            var panel = document.createElement('div');
            panel.className = 'zmp-audio-tools-mobile-card__panel';
            panel.id = panelId;
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', tabId);
            panel.hidden = cellIndex !== 0;
            panel.tabIndex = 0;

            var originalNodes = Array.prototype.slice.call(cell.childNodes);
            var slots = originalNodes.map(function(node) {
                var slot = document.createComment('zmp-audio-mobile-content');
                cell.insertBefore(slot, node);
                return slot;
            });
            var content = document.createElement('div');
            content.className = 'zmp-audio-tools-mobile-card__content';
            originalNodes.forEach(function(node) { content.appendChild(node); });
            panel.appendChild(content);

            tab.addEventListener('click', function() { selectTab(tab, false); });
            tablist.appendChild(tab);
            card.appendChild(panel);
            tabRecords.push({ tab: tab, panel: panel, nodes: originalNodes, slots: slots, content: content });
        });

        tablist.addEventListener('keydown', function(event) {
            var currentIndex = tabRecords.findIndex(function(record) { return record.tab === event.target; });
            if (currentIndex < 0) { return; }
            var nextIndex = currentIndex;
            if (event.key === 'ArrowRight') { nextIndex = (currentIndex + 1) % tabRecords.length; }
            else if (event.key === 'ArrowLeft') { nextIndex = (currentIndex + tabRecords.length - 1) % tabRecords.length; }
            else if (event.key === 'Home') { nextIndex = 0; }
            else if (event.key === 'End') { nextIndex = tabRecords.length - 1; }
            else { return; }
            event.preventDefault();
            selectTab(tabRecords[nextIndex].tab, true);
        });

        catalog.cards.appendChild(card);
        row.hidden = true;
        return { row: row, cells: cells, card: card, tabRecords: tabRecords, isMessage: false, rowIndex: rowIndex };
    }

    function setMobile(catalog, isMobile) {
        var active = document.activeElement;
        var hadFocusInWrapper = catalog.wrapper.contains(active);
        var focusedItem = catalog.items.find(function(item) {
            return !item.isMessage && (item.card.contains(active) || item.row.contains(active) || item.cells.some(function(cell) { return cell.contains(active); }));
        });
        var focusedRecordIndex = focusedItem ? recordForFocus(focusedItem, active) : -1;
        var focusedRecord = focusedRecordIndex >= 0 && focusedItem ? focusedItem.tabRecords[focusedRecordIndex] : null;
        var focusWasTab = !!(focusedRecord && focusedRecord.tab === active);
        var focusWasInContent = !!(focusedRecord && (focusedRecord.content.contains(active) || focusedItem.cells[focusedRecordIndex].contains(active)));

        catalog.items.forEach(function(item) {
            if (item.isMessage) {
                item.card.hidden = !isMobile;
                return;
            }
            if (isMobile) {
                item.cells.forEach(function(cell) {
                    var record = item.tabRecords[item.cells.indexOf(cell)];
                    record.nodes.forEach(function(node) { record.content.appendChild(node); });
                });
                item.row.hidden = true;
                item.card.hidden = false;
            } else {
                item.cells.forEach(function(cell) {
                    var record = item.tabRecords[item.cells.indexOf(cell)];
                    record.slots.forEach(function(slot, nodeIndex) {
                        if (slot.parentNode) { slot.parentNode.insertBefore(record.nodes[nodeIndex], slot); }
                    });
                });
                item.row.hidden = false;
                item.card.hidden = true;
            }
        });

        if (isMobile && hadFocusInWrapper) {
            if (focusedRecord) {
                selectTab(focusedRecord.tab, false);
                if (focusWasInContent && active.isConnected) { focusElement(active); }
                else { focusElement(focusedRecord.tab); }
            } else {
                var firstItem = catalog.items.find(function(item) { return !item.isMessage; });
                if (firstItem && firstItem.tabRecords.length) { focusElement(firstItem.tabRecords[0].tab); }
            }
        } else if (!isMobile && focusedItem && focusWasInContent && active.isConnected) {
            focusElement(active);
        } else if (!isMobile && focusedItem && (focusedItem.card.contains(active) || focusWasTab)) {
            var destinationCell = focusedRecord ? focusedItem.cells[focusedRecordIndex] : null;
            focusElement(firstFocusable(destinationCell) || catalog.wrapper);
        }
    }

    function init() {
        document.querySelectorAll('.zmp-audio-tools-table-wrap').forEach(function(wrapper) {
            if (wrapper.dataset.zmpMobileTabsReady === '1') { return; }
            var table = wrapper.querySelector('table');
            var body = table && table.tBodies && table.tBodies[0];
            if (!body) { return; }
            wrapper.dataset.zmpMobileTabsReady = '1';
            var cards = document.createElement('div');
            cards.className = 'zmp-audio-tools-mobile-cards';
            cards.setAttribute('role', 'region');
            cards.setAttribute('aria-label', 'Каталог аудио-алатки');
            wrapper.insertAdjacentElement('afterend', cards);
            var catalog = { wrapper: wrapper, table: table, body: body, cards: cards, items: [] };
            Array.prototype.forEach.call(body.rows, function(row, rowIndex) {
                catalog.items.push(buildCard(catalog, row, rowIndex));
            });
            catalogs.push(catalog);
        });
        catalogs.forEach(function(catalog) { setMobile(catalog, mobileQuery.matches); });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
    else { init(); }
    if (mobileQuery.addEventListener) { mobileQuery.addEventListener('change', initAndResize); }
    else if (mobileQuery.addListener) { mobileQuery.addListener(initAndResize); }
    function initAndResize() {
        init();
    }
})();
