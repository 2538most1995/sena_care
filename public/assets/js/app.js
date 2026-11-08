/**
 * ============================================================
 * ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร. (SENA Care)
 * Core Application JavaScript
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    initCsrfFetch();
    initSidebarToggle();
    initGlobalSearchShortcut();
    initLightbox();
    initTooltips();
    initThaiDateTimePickers();
    initGraduatePanel();
    initBackLinks();
    initStudentPhones();
    document.getElementById('termFilter')?.addEventListener('change', async function () {
        const body = new URLSearchParams({term_id:this.value});
        try {
            const response = await fetch('terms/switch', {method:'POST', body, headers:{'X-CSRF-TOKEN':window.csrfToken}});
            if (!response.ok) throw new Error('เปลี่ยนภาคเรียนไม่สำเร็จ');
            location.reload();
        } catch (error) { showToast('danger',error.message); }
    });

});

/** Fetch profile phones only for visible roster rows; retain the local fallback. */
function initStudentPhones() {
    const rows = document.querySelectorAll('[data-student-phone-url]');
    if (!rows.length) return;
    const queue = [];
    let running = 0;
    async function loadNext() {
        if (running >= 2 || !queue.length) return;
        const row = queue.shift();
        if (!row.isConnected) return;
        running++;
        row.setAttribute('aria-busy', 'true');
        try {
            const result = await apiFetch(row.dataset.studentPhoneUrl, { signal: AbortSignal.timeout(12000) });
            if (result?.success && typeof result.data?.display === 'string') row.textContent = result.data.display;
        } finally {
            row.removeAttribute('aria-busy');
            running--;
            loadNext();
        }
    }
    function enqueue(row) {
        queue.push(row);
        loadNext();
    }
    if (!('IntersectionObserver' in window)) {
        rows.forEach(enqueue);
        return;
    }
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);
            enqueue(entry.target);
        });
    }, { rootMargin: '150px' });
    rows.forEach(row => observer.observe(row));
}

/**
 * Return one browser navigation step, with a fallback for direct entry.
 */
function initBackLinks() {
    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-back-link]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        // A direct visit or an external referrer uses the link's safe fallback.
        if (!document.referrer || window.history.length < 2) return;
        const previous = new URL(document.referrer);
        if (previous.origin !== window.location.origin) return;
        event.preventDefault();
        window.history.back();
    });
}

/** Configure Fetch API to automatically send the CSRF token header. */
function initCsrfFetch() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        window.csrfToken = metaTag.getAttribute('content');
    }
}

/**
 * Helper to make JSON fetch request with CSRF token included
 */
async function apiFetch(url, options = {}) {
    const headers = options.headers || {};
    if (window.csrfToken) {
        headers['X-CSRF-TOKEN'] = window.csrfToken;
        headers['X-Requested-With'] = 'XMLHttpRequest';
    }

    options.headers = headers;
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (err) {
        console.error('API Fetch error:', err);
        return { success: false, message: 'เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย' };
    }
}

/**
 * Mobile Sidebar Toggle
 */
function initSidebarToggle() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.classList.add('sidebar-open');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (sidebar && sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (sidebar && sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function (e) {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            closeSidebar();
        });
    }
}

/**
 * Global search shortcut (Cmd+K / Ctrl+K)
 */
function initGlobalSearchShortcut() {
    window.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const modalEl = document.getElementById('globalSearchModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                setTimeout(() => {
                    const input = document.getElementById('globalSearchInput');
                    if (input) input.focus();
                }, 300);
            }
        }
    });
}

/**
 * Image lightbox: any element with [data-lightbox] opens its image in a
 * full-screen overlay, without leaving the current page.
 */
function initLightbox() {
    if (document.getElementById('imageLightbox')) return;

    const overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.id = 'imageLightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'ดูรูปภาพขนาดเต็ม');
    overlay.innerHTML = `
        <button type="button" class="lightbox-close" aria-label="ปิด">&times;</button>
        <img class="lightbox-img" alt="">
    `;
    document.body.appendChild(overlay);

    const img = overlay.querySelector('.lightbox-img');
    let lastFocus = null;

    function open(src, alt) {
        if (!src) return;
        lastFocus = document.activeElement;
        // Keep focus inside Bootstrap's active modal when viewing its photos.
        (document.querySelector('.modal.show') || document.body).appendChild(overlay);
        img.src = src;
        img.alt = alt || '';
        overlay.classList.add('show');
        document.body.classList.add('lightbox-open');
        overlay.querySelector('.lightbox-close').focus();
    }

    function close() {
        overlay.classList.remove('show');
        document.body.classList.remove('lightbox-open');
        img.removeAttribute('src');
        document.body.appendChild(overlay);
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-lightbox]');
        if (trigger) {
            if (trigger.tagName === 'A') e.preventDefault();
            const src = trigger.getAttribute('data-lightbox') || trigger.getAttribute('href');
            const alt = trigger.getAttribute('data-alt') || (trigger.querySelector('img') ? trigger.querySelector('img').alt : '');
            open(src, alt);
            return;
        }
        if (e.target === overlay || e.target.closest('.lightbox-close')) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('show')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            close();
        }
    }, true);
}

/** Graduate details, editing and uploads stay on the filtered roster. */
function initGraduatePanel() {
    const modalEl = document.getElementById('graduatePanelModal');
    const panel = document.getElementById('graduatePanelBody');
    let requestVersion = 0;
    let trigger = null;
    let saving = false;
    let previewUrl = null;

    function clearPreview() {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    }

    // Delegation also supports the standalone form and dynamically loaded edits.
    document.addEventListener('change', function (event) {
        const input = event.target.closest('[data-graduate-photo]');
        if (!input) return;
        clearPreview();
        const preview = input.form.querySelector('[data-graduate-preview]');
        const file = input.files[0];
        preview.classList.toggle('d-none', !file);
        preview.removeAttribute('src');
        if (file) {
            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
        }
    });

    if (!modalEl || !panel || typeof bootstrap === 'undefined') return;
    // The modal must sit above the page's stacking contexts and mobile nav.
    document.body.appendChild(modalEl);
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const footer = modalEl.querySelector('.modal-footer');

    function clearFooter() {
        footer.replaceChildren();
        footer.classList.add('d-none');
    }

    function render(data) {
        clearPreview();
        clearFooter();
        panel.innerHTML = data.html;
        const form = panel.querySelector('.tracking-form');
        modalEl.classList.toggle('graduate-panel-form', Boolean(form));
        if (form) {
            // Move the existing controls into the fixed footer; native form
            // association preserves validation, uploads and keyboard submission.
            form.id = 'graduateTrackingForm';
            const actions = form.querySelector('.tracking-form-actions');
            const column = actions.parentElement;
            const submit = actions.querySelector('button[type="submit"]');
            submit.setAttribute('form', form.id);
            actions.classList.remove('pt-3', 'border-top');
            actions.classList.add('w-100');
            actions.appendChild(submit);
            footer.appendChild(actions);
            column.remove();
            footer.classList.remove('d-none');
        }
        panel.scrollTop = 0;
        panel.removeAttribute('aria-busy');
        modal.handleUpdate();
        panel.focus({ preventScroll: true });
    }

    async function load(url) {
        const version = ++requestVersion;
        clearFooter();
        panel.setAttribute('aria-busy', 'true');
        panel.innerHTML = '<div class="text-center py-5" role="status"><span class="spinner-border text-primary mb-3" aria-hidden="true"></span><p class="text-muted mb-0">กำลังโหลดผลการติดตาม…</p></div>';
        const result = await apiFetch(url, { signal: AbortSignal.timeout(20000) });
        if (version !== requestVersion) return;
        panel.removeAttribute('aria-busy');
        if (result.success && result.data?.html) {
            render(result.data);
        } else {
            const error = document.createElement('div');
            error.className = 'alert alert-danger';
            error.setAttribute('role', 'alert');
            error.textContent = result.message || 'โหลดข้อมูลไม่สำเร็จ กรุณาลองอีกครั้ง';
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-outline-primary';
            retry.textContent = 'ลองอีกครั้ง';
            retry.addEventListener('click', () => load(url));
            panel.replaceChildren(error, retry);
        }
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-panel-url]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        if (saving) return;
        if (!modalEl.contains(link)) {
            trigger = link;
            bootstrap.Tooltip.getInstance(link)?.hide();
        }
        modal.show();
        load(link.dataset.panelUrl);
    });

    modalEl.addEventListener('hide.bs.modal', function (event) {
        if (saving) event.preventDefault();
    });
    modalEl.addEventListener('hidden.bs.modal', function () {
        ++requestVersion;
        clearPreview();
        panel.replaceChildren();
        clearFooter();
        modalEl.classList.remove('graduate-panel-form');
        trigger?.focus();
    });

    panel.addEventListener('submit', async function (event) {
        const form = event.target.closest('.tracking-form');
        if (!form) return;
        event.preventDefault();
        if (saving || !form.reportValidity()) return;
        const error = form.querySelector('[data-tracking-error]');
        const button = event.submitter || footer.querySelector('button[type="submit"]');
        const label = button.innerHTML;
        const body = new FormData(form);
        error.classList.add('d-none');
        saving = true;
        button.disabled = true;
        button.textContent = 'กำลังบันทึก…';
        form.setAttribute('aria-busy', 'true');
        try {
            const result = await apiFetch(form.action, { method: 'POST', body });
            if (!result.success || !result.data?.html) {
                error.textContent = result.message || 'บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง';
                error.classList.remove('d-none');
                error.focus();
                return;
            }
            render(result.data);
            const row = document.querySelector(`[data-graduate-id="${Number(result.data.graduate_id)}"]`);
            if (row) {
                const cell = row.querySelector('[data-tracking-status] .record-value') || row.querySelector('[data-tracking-status]');
                const badge = document.createElement('span');
                badge.className = 'badge bg-success';
                badge.textContent = `ติดตามแล้ว ${result.data.tracking_count} ครั้ง`;
                const date = document.createElement('small');
                date.className = 'text-muted';
                date.textContent = `ล่าสุด ${result.data.latest_tracked_at}`;
                cell.replaceChildren(badge, document.createElement('br'), date);
                const link = row.querySelector('[data-panel-url]');
                link.classList.replace('btn-primary', 'btn-outline-primary');
            }
            // Show the server's message as text inside the panel.
            const notice = document.createElement('div');
            notice.className = 'alert alert-success';
            notice.setAttribute('role', 'status');
            notice.textContent = result.message;
            panel.prepend(notice);
        } finally {
            saving = false;
            button.disabled = false;
            button.innerHTML = label;
            form.removeAttribute('aria-busy');
        }
    });
}

/**
 * Bootstrap tooltips for elements using [data-bs-toggle="tooltip"].
 * Uses the native title as fallback when Bootstrap is unavailable.
 */
function initTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    if (initTooltips.bound) return;
    initTooltips.bound = true;
    const selector = '[data-bs-toggle="tooltip"]';
    let active = null;
    let keyboardNavigation = false;

    function instance(el) {
        return bootstrap.Tooltip.getOrCreateInstance(el, {
            trigger: 'manual', animation: false, container: 'body'
        });
    }

    function hide() {
        if (active) bootstrap.Tooltip.getInstance(active)?.hide();
        active = null;
    }

    function show(el) {
        if (!el || el.matches(':disabled, [aria-disabled="true"]')) return;
        if (active === el) return;
        hide();
        active = el;
        instance(el).show();
    }

    // Manual triggers prevent Bootstrap's mouse and focus states from keeping
    // each other open after a click or focus restoration from a modal.
    document.querySelectorAll(selector).forEach(instance);
    document.addEventListener('pointerover', function (event) {
        if (event.pointerType === 'touch') return;
        const el = event.target.closest(selector);
        if (el && !el.contains(event.relatedTarget)) show(el);
    });
    document.addEventListener('pointerout', function (event) {
        const el = event.target.closest(selector);
        if (el && el === active && !el.contains(event.relatedTarget)) hide();
    });
    document.addEventListener('focusin', function (event) {
        const el = event.target.closest(selector);
        if (keyboardNavigation && el?.matches(':focus-visible')) show(el);
    });
    document.addEventListener('focusout', function (event) {
        if (event.target.closest(selector) === active) hide();
    });
    document.addEventListener('pointerdown', function () {
        keyboardNavigation = false;
        hide();
    }, true);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Tab') keyboardNavigation = true;
        if (event.key === 'Escape' || event.key === 'Enter' || event.key === ' ') hide();
    }, true);
    document.addEventListener('click', hide, true);
    document.addEventListener('scroll', hide, { capture: true, passive: true });
    window.addEventListener('blur', hide);
    window.addEventListener('resize', hide);
    document.addEventListener('visibilitychange', hide);
    ['show.bs.modal', 'hide.bs.modal', 'show.bs.offcanvas', 'hide.bs.offcanvas'].forEach(function (name) {
        document.addEventListener(name, hide);
    });
    new MutationObserver(function () {
        if (active && !active.isConnected) {
            bootstrap.Tooltip.getInstance(active)?.dispose();
            active = null;
        }
    }).observe(document.body, { childList: true, subtree: true });
}


/**
 * Thai Buddhist-era date-time picker.
 * Usage:
 *   <input class="thai-datetime" data-target="hiddenInputId">
 *   <input type="hidden" id="hiddenInputId" name="field" value="2026-11-01T08:00">
 * The visible input shows Thai text (พ.ศ.); the hidden input carries YYYY-MM-DDTHH:mm.
 */
function initThaiDateTimePickers() {
    const inputs = document.querySelectorAll('.thai-datetime');
    if (!inputs.length) return;

    const MONTHS = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const MONTHS_SHORT = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const WEEK = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

    let pop = document.getElementById('thaiDatePop');
    if (!pop) {
        pop = document.createElement('div');
        pop.id = 'thaiDatePop';
        pop.className = 'thai-date-pop';
        pop.innerHTML =
            '<div class="tdp-head">' +
                '<button type="button" class="tdp-nav" data-dir="-1" aria-label="เดือนก่อน">&#8249;</button>' +
                '<span class="tdp-title"></span>' +
                '<button type="button" class="tdp-nav" data-dir="1" aria-label="เดือนถัดไป">&#8250;</button>' +
            '</div>' +
            '<div class="tdp-week">' + WEEK.map(function (w) { return '<span>' + w + '</span>'; }).join('') + '</div>' +
            '<div class="tdp-grid"></div>' +
            '<div class="tdp-time"><span class="tdp-time-label">เวลา</span>' +
                '<select class="tdp-hour form-select form-select-sm" aria-label="ชั่วโมง"></select>' +
                '<span>:</span>' +
                '<select class="tdp-min form-select form-select-sm" aria-label="นาที"></select>' +
            '</div>' +
            '<div class="tdp-actions">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" data-act="clear">ล้าง</button>' +
                '<span class="d-flex gap-2">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" data-act="today">วันนี้</button>' +
                    '<button type="button" class="btn btn-sm btn-primary" data-act="ok">ตกลง</button>' +
                '</span>' +
            '</div>';
        document.body.appendChild(pop);
    }
    pop.addEventListener('click', function (e) { e.stopPropagation(); });

    const titleEl = pop.querySelector('.tdp-title');
    const gridEl = pop.querySelector('.tdp-grid');
    const hourEl = pop.querySelector('.tdp-hour');
    const minEl = pop.querySelector('.tdp-min');

    if (!hourEl.dataset.ready) {
        for (let h = 0; h < 24; h++) hourEl.add(new Option(String(h).padStart(2, '0'), String(h)));
        for (let m = 0; m < 60; m++) minEl.add(new Option(String(m).padStart(2, '0'), String(m)));
        hourEl.dataset.ready = '1';
    }

    let active = null;
    let viewY = 0, viewM = 0;
    let selY = null, selM = null, selD = null;

    function pad(n) { return String(n).padStart(2, '0'); }

    function parseValue(v) {
        if (!v) return null;
        const m = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/.exec(v);
        return m ? { y: +m[1], m: +m[2] - 1, d: +m[3], h: +m[4], mi: +m[5] } : null;
    }

    function formatThai(y, m, d, h, mi) {
        return d + ' ' + MONTHS_SHORT[m] + ' ' + (y + 543) + ' ' + pad(h) + ':' + pad(mi) + ' น.';
    }

    function render() {
        titleEl.textContent = MONTHS[viewM] + ' ' + (viewY + 543);
        gridEl.innerHTML = '';
        const first = new Date(viewY, viewM, 1).getDay();
        const days = new Date(viewY, viewM + 1, 0).getDate();
        const today = new Date();
        for (let i = 0; i < first; i++) {
            const blank = document.createElement('span');
            blank.className = 'tdp-empty';
            gridEl.appendChild(blank);
        }
        for (let d = 1; d <= days; d++) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'tdp-day';
            b.textContent = String(d);
            if (d === today.getDate() && viewM === today.getMonth() && viewY === today.getFullYear()) b.classList.add('is-today');
            if (selY === viewY && selM === viewM && selD === d) b.classList.add('is-selected');
            b.addEventListener('click', function () { selY = viewY; selM = viewM; selD = d; render(); });
            gridEl.appendChild(b);
        }
    }

    function open(input) {
        active = input;
        const target = document.getElementById(input.dataset.target);
        const parsed = parseValue(target ? target.value : '');
        const now = new Date();
        if (parsed) {
            viewY = parsed.y; viewM = parsed.m;
            selY = parsed.y; selM = parsed.m; selD = parsed.d;
            hourEl.value = String(parsed.h); minEl.value = String(parsed.mi);
        } else {
            viewY = now.getFullYear(); viewM = now.getMonth();
            selY = null; selM = null; selD = null;
            hourEl.value = String(now.getHours()); minEl.value = String(Math.floor(now.getMinutes() / 5) * 5);
        }
        render();
        pop.classList.add('show');
        const r = input.getBoundingClientRect();
        pop.style.top = (r.bottom + window.scrollY + 6) + 'px';
        pop.style.left = (r.left + window.scrollX) + 'px';
        requestAnimationFrame(function () {
            const pr = pop.getBoundingClientRect();
            if (pr.right > window.innerWidth - 8) {
                pop.style.left = Math.max(8, window.innerWidth - pr.width - 8 + window.scrollX) + 'px';
            }
            if (pr.bottom > window.innerHeight) {
                pop.style.top = Math.max(8, r.top + window.scrollY - pr.height - 6) + 'px';
            }
        });
    }

    function close() { pop.classList.remove('show'); active = null; }

    function commit() {
        if (!active || selY === null) { close(); return; }
        const h = +hourEl.value, mi = +minEl.value;
        const iso = selY + '-' + pad(selM + 1) + '-' + pad(selD) + 'T' + pad(h) + ':' + pad(mi);
        const hidden = document.getElementById(active.dataset.target);
        if (hidden) hidden.value = iso;
        active.value = formatThai(selY, selM, selD, h, mi);
        close();
    }

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.thai-datetime');
        if (trigger) {
            e.preventDefault();
            open(trigger);
            return;
        }
        if (!e.target.closest('#thaiDatePop')) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && pop.classList.contains('show')) { e.preventDefault(); close(); }
    });

    window.addEventListener('scroll', function () {
        if (pop.classList.contains('show')) close();
    }, { passive: true });

    pop.addEventListener('click', function (e) {
        const nav = e.target.closest('.tdp-nav');
        if (nav) {
            viewM += +nav.dataset.dir;
            if (viewM < 0) { viewM = 11; viewY--; }
            if (viewM > 11) { viewM = 0; viewY++; }
            render();
            return;
        }
        const act = e.target.closest('[data-act]');
        if (!act) return;
        if (act.dataset.act === 'clear') {
            if (active) {
                const hidden = document.getElementById(active.dataset.target);
                if (hidden) hidden.value = '';
                active.value = '';
            }
            close();
        } else if (act.dataset.act === 'today') {
            const n = new Date();
            viewY = n.getFullYear(); viewM = n.getMonth();
            selY = n.getFullYear(); selM = n.getMonth(); selD = n.getDate();
            hourEl.value = String(n.getHours()); minEl.value = String(Math.floor(n.getMinutes() / 5) * 5);
            render();
        } else if (act.dataset.act === 'ok') {
            commit();
        }
    });
}

/**
 * Show Toast Notification
 */
function showToast(type, message, title = '') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const bgMap = {
        success: 'text-bg-success',
        danger: 'text-bg-danger',
        warning: 'text-bg-warning',
        info: 'text-bg-info'
    };

    const iconMap = {
        success: 'bi-check-circle-fill',
        danger: 'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info: 'bi-info-circle-fill'
    };

    const toastId = 'toast_' + Date.now();
    const bgClass = bgMap[type] || 'text-bg-primary';
    const iconClass = iconMap[type] || 'bi-info-circle-fill';
    const closeClass = ['success', 'danger', 'warning'].includes(type) ? '' : 'btn-close-white';

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow-lg rounded-3 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${iconClass} fs-5"></i>
                    <div>
                        ${title ? `<div class="fw-bold">${title}</div>` : ''}
                        <div>${message}</div>
                    </div>
                </div>
                <button type="button" class="btn-close ${closeClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="ปิดข้อความแจ้งเตือน"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

/**
 * Confirmation Prompt (Modal or native confirm fallback)
 */
function confirmAction(message, onConfirm) {
    if (confirm(message)) {
        if (typeof onConfirm === 'function') onConfirm();
    }
}

/**
 * Browser Geolocation Helper
 * Returns Promise<{lat: number, lng: number, accuracy: number}>
 */
function getCurrentPosition() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('เบราว์เซอร์ของคุณไม่รองรับการระบุพิกัด GPS'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                resolve({
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    accuracy: pos.coords.accuracy
                });
            },
            (err) => {
                let msg = 'ไม่สามารถดึงตำแหน่งพิกัด GPS ได้';
                if (err.code === err.PERMISSION_DENIED) {
                    msg = 'กรุณาอนุญาตให้เข้าถึงตำแหน่งที่ตั้งในเบราว์เซอร์';
                }
                reject(new Error(msg));
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
}

/**
 * Calculate Distance in Meters between two Lat/Lng points (Haversine Formula)
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // metres
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return Math.round(R * c);
}

/**
 * Leaflet Map Helper: Create standard map instance
 */
function initLeafletMap(elementId, centerLat = 14.3284, centerLng = 100.4042, zoom = 13) {
    const map = L.map(elementId).setView([centerLat, centerLng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    return map;
}

/**
 * Leaflet Custom Marker Icons
 */
function createMapMarkerIcon(color = null) {
    color ||= getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
    return L.divIcon({
        className: 'custom-div-icon',
        html: `<div style="background-color: ${color}; width: 28px; height: 28px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"><i class="bi bi-house-door-fill" style="color: white; transform: rotate(45deg); font-size: 13px;"></i></div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 28],
        popupAnchor: [0, -28]
    });
}

// Label compact record cards from the real table headings; no duplicated row data.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.records-table').forEach(table => {
        const labels = [...table.querySelectorAll('thead th')].map(h => h.textContent.trim());
        table.querySelectorAll('tbody tr').forEach(row => [...row.cells].forEach((cell,i) => {
            cell.dataset.label = labels[i] || '';
            const value = document.createElement('div'); value.className='record-value';
            while (cell.firstChild) value.appendChild(cell.firstChild);
            cell.appendChild(value);
        }));
    });
});
