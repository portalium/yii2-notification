// Notification panel: opens from the right edge. "Unread (n)" / "All" filter the list; checking rows shows
// "Mark as read" and "Delete" next to "Select all"; on hover a row offers the same two for itself. The icon next to
// the tabs opens the full list page.
// The list page's trash button deletes the rows checked in its grid.
(function () {
    function panel() {
        return document.getElementById('notification-panel');
    }

    function csrfBody() {
        var body = new FormData();
        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) {
            body.append(document.querySelector('meta[name="csrf-param"]').content, csrf.content);
        }
        return body;
    }

    function post(url, ids) {
        var body = csrfBody();
        ids.forEach(function (id) {
            body.append('selection[]', id);
        });
        return fetch(url, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            if (!data || !data.success) {
                throw new Error('notification request failed');
            }
            return data;
        });
    }

    // rows hidden by the "Unread" tab are never part of the selection
    function visibleRows(el) {
        var onlyUnread = el.classList.contains('only-unread');
        return Array.prototype.filter.call(el.querySelectorAll('.notification-row'), function (row) {
            return !onlyUnread || row.classList.contains('unread');
        });
    }

    function checkedIds(el) {
        return visibleRows(el).filter(function (row) {
            return row.querySelector('.notification-row-check').checked;
        }).map(function (row) {
            return row.dataset.key;
        });
    }

    function row(el, id) {
        return el.querySelector('.notification-row[data-key="' + id + '"]');
    }

    function refresh(el) {
        var rows = visibleRows(el);
        var count = checkedIds(el).length;

        var all = el.querySelector('#notification-select-all');
        all.disabled = rows.length === 0;
        all.checked = rows.length > 0 && count === rows.length;
        all.indeterminate = count > 0 && count < rows.length;
        el.querySelector('.notification-select-label').textContent = count > 0
            ? el.dataset.selectedLabel.replace('{n}', count)
            : el.dataset.selectLabel;
        el.querySelector('.notification-select-actions').hidden = count === 0;

        var anyRow = el.querySelector('.notification-row') !== null;
        el.querySelector('.notification-empty').hidden = anyRow;
        el.querySelector('.notification-empty-unread').hidden = !anyRow || !el.classList.contains('only-unread')
            || el.querySelector('.notification-row.unread') !== null;
    }

    function setCount(el, count) {
        var badge = document.querySelector('#notification .notification-count');
        var toggle = document.querySelector('#notification .notification-toggle');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
            } else {
                badge.remove();
            }
        }
        el.querySelector('.notification-tab-count').textContent = count;
        if (toggle) {
            var label = count > 0 ? el.dataset.labelUnread.replace('{n}', count) : el.dataset.label;
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
        }
    }

    function markRead(el, ids) {
        ids.forEach(function (id) {
            var r = row(el, id);
            if (r) {
                r.classList.remove('unread');
                var tick = r.querySelector('.notification-row-read');
                if (tick) {
                    tick.remove();
                }
                // in the "Unread" tab the row leaves the list, so it leaves the selection too
                if (el.classList.contains('only-unread')) {
                    r.querySelector('.notification-row-check').checked = false;
                }
            }
        });
    }

    function readIds(el, ids, button) {
        button.disabled = true;
        post(el.dataset.readUrl, ids)
            .then(function (data) {
                markRead(el, ids);
                setCount(el, data.unread);
            })
            .catch(function () {})
            .then(function () {
                button.disabled = false;
                refresh(el);
            });
    }

    function readAll(el, button) {
        button.disabled = true;
        post(el.dataset.readAllUrl, [])
            .then(function () {
                markRead(el, Array.prototype.map.call(el.querySelectorAll('.notification-row.unread'), function (r) {
                    return r.dataset.key;
                }));
                setCount(el, 0);
            })
            .catch(function () {})
            .then(function () {
                button.disabled = false;
                refresh(el);
            });
    }

    function deleteIds(el, ids, button, question) {
        if (!window.confirm(question)) {
            return;
        }
        button.disabled = true;
        post(el.dataset.deleteUrl, ids)
            .then(function (data) {
                ids.forEach(function (id) {
                    var r = row(el, id);
                    if (r) {
                        r.remove();
                    }
                });
                setCount(el, data.unread);
            })
            .catch(function () {})
            .then(function () {
                button.disabled = false;
                refresh(el);
            });
    }

    // list page: posts the grid's checked rows; the page reloads with the result message
    document.addEventListener('click', function (e) {
        var trash = e.target.closest('#notification-delete-select');
        if (!trash) {
            return;
        }
        var ids = Array.prototype.map.call(document.querySelectorAll('input[name="selection[]"]:checked'), function (box) {
            return box.value;
        });
        if (ids.length === 0 || !window.confirm(trash.dataset.confirmText)) {
            return;
        }
        var form = document.createElement('form');
        form.method = 'post';
        form.action = trash.dataset.url;
        form.hidden = true;
        var fields = csrfBody();
        ids.forEach(function (id) {
            fields.append('selection[]', id);
        });
        fields.forEach(function (value, name) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    });

    document.addEventListener('DOMContentLoaded', function () {
        var el = panel();
        if (!el) {
            return;
        }
        // the bell can sit inside the collapsed top menu on phones; the panel must not
        document.body.appendChild(el);

        el.querySelectorAll('.notification-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                var onlyUnread = tab.dataset.filter === 'unread';
                el.classList.toggle('only-unread', onlyUnread);
                el.querySelectorAll('.notification-tab').forEach(function (other) {
                    other.classList.toggle('active', other === tab);
                    other.setAttribute('aria-selected', other === tab ? 'true' : 'false');
                });
                if (onlyUnread) {
                    el.querySelectorAll('.notification-row:not(.unread) .notification-row-check').forEach(function (box) {
                        box.checked = false;
                    });
                }
                refresh(el);
            });
        });

        el.addEventListener('change', function (e) {
            if (e.target.id === 'notification-select-all') {
                var checked = e.target.checked;
                visibleRows(el).forEach(function (r) {
                    r.querySelector('.notification-row-check').checked = checked;
                });
            }
            refresh(el);
        });

        el.addEventListener('click', function (e) {
            var button = e.target.closest('button');
            if (!button || button.disabled) {
                return;
            }
            if (button.classList.contains('notification-read-selected')) {
                // everything checked in "Unread": the badge also counts unread ones beyond the list, clear them all
                if (el.classList.contains('only-unread') && el.querySelector('#notification-select-all').checked) {
                    readAll(el, button);
                } else {
                    readIds(el, checkedIds(el), button);
                }
            } else if (button.classList.contains('notification-delete-selected')) {
                deleteIds(el, checkedIds(el), button, el.dataset.deleteConfirm);
            } else if (button.classList.contains('notification-row-read')) {
                readIds(el, [button.dataset.id], button);
            } else if (button.classList.contains('notification-row-delete')) {
                deleteIds(el, [button.dataset.id], button, el.dataset.deleteOneConfirm);
            }
        });

        refresh(el);
    });
})();
