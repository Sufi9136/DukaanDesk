// common javascript for all pages
document.addEventListener('DOMContentLoaded', function () {

    // sidebar open / close
    var sidebar = document.getElementById('sidebar');
    var menuBtn = document.getElementById('menuBtn');
    var backdrop = document.getElementById('sidebarBackdrop');
    var closeBtn = document.getElementById('sidebarClose');

    function openMenu() {
        sidebar.classList.add('open');
        document.body.classList.add('menu-open');
    }
    function closeMenu() {
        sidebar.classList.remove('open');
        document.body.classList.remove('menu-open');
    }

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                // phone / tablet: slide the menu over the page
                if (sidebar.classList.contains('open')) { closeMenu(); } else { openMenu(); }
            } else {
                // laptop: hide or show the menu
                sidebar.classList.toggle('closed');
            }
        });

        // ways to close the menu on a phone: x button, tap the dark area, Esc key, swipe it to the left
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        if (backdrop) backdrop.addEventListener('click', closeMenu);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });

        var touchStartX = null;
        sidebar.addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });
        sidebar.addEventListener('touchend', function (e) {
            if (touchStartX !== null && e.changedTouches[0].clientX - touchStartX < -60) {
                closeMenu();
            }
            touchStartX = null;
        }, { passive: true });

        // if the window becomes wide (phone turned sideways), reset
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992) closeMenu();
        });
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js');
    }

    // tables with class js-table get search, sorting and paging
    if (window.jQuery && jQuery.fn.DataTable) {
        jQuery('.js-table').DataTable({
            pageLength: 10,
            order: [],
            language: { search: '', searchPlaceholder: 'Search...' }
        });
    }
});

// "Add" and "Edit" buttons that open a modal form.
// The edit button carries the row data as json in data-row,
// every form field whose name matches a key gets filled.
document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-form-modal]');
    if (!btn) return;

    var modal = document.querySelector(btn.getAttribute('data-bs-target'));
    if (!modal) return;
    var form = modal.querySelector('form');
    form.reset();

    // hidden id field must be cleared for "add"
    var idField = form.elements['id'];
    if (idField) idField.value = '';

    var title = modal.querySelector('.modal-title');
    if (title && btn.getAttribute('data-title')) {
        title.textContent = btn.getAttribute('data-title');
    }

    var rowData = btn.getAttribute('data-row');
    if (rowData) {
        var row = JSON.parse(rowData);
        Object.keys(row).forEach(function (key) {
            if (form.elements[key]) {
                form.elements[key].value = row[key] === null ? '' : row[key];
            }
        });
    }
});

// ask before submitting delete forms
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.classList.contains('js-confirm')) {
        var msg = form.getAttribute('data-msg') || 'Are you sure?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    }
});

// view bill details in a popup (loads html from ajax/bill_details.php)
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-bill-view');
    if (!btn) return;

    var body = document.getElementById('billModalBody');
    body.innerHTML = '<p class="text-center text-muted my-4">Loading...</p>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('billModal')).show();

    fetch('ajax/bill_details.php?id=' + encodeURIComponent(btn.getAttribute('data-id')))
        .then(function (r) { return r.text(); })
        .then(function (html) { body.innerHTML = html; })
        .catch(function () { body.innerHTML = '<p class="text-danger">Could not load the bill.</p>'; });
});
