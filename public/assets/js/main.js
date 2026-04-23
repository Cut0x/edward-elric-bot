'use strict';

// ── TOAST ────────────────────────────────────────────────────
function showToast(msg, type = 'info', ms = 4000) {
    let c = document.querySelector('.toast-container');
    if (!c) {
        c = document.createElement('div');
        c.className = 'toast-container';
        document.body.appendChild(c);
    }
    const icons = { info: 'bi-info-circle-fill', success: 'bi-check-circle-fill', error: 'bi-x-circle-fill' };
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i><span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(16px)'; t.style.transition = '.2s ease'; setTimeout(() => t.remove(), 200); }, ms);
}

// ── NAVBAR MOBILE ─────────────────────────────────────────────
function initNavbar() {
    const toggle = document.querySelector('.navbar-toggle');
    const links  = document.querySelector('.navbar-links');
    if (!toggle || !links) return;

    toggle.addEventListener('click', () => {
        const open = links.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open);
    });

    document.addEventListener('click', e => {
        if (!toggle.contains(e.target) && !links.contains(e.target)) {
            links.classList.remove('open');
        }
    });
}

// ── XP BAR ANIMATION ─────────────────────────────────────────
function initXpBars() {
    document.querySelectorAll('[data-xp-width]').forEach(el => {
        setTimeout(() => { el.style.width = el.dataset.xpWidth + '%'; }, 200);
    });
    document.querySelectorAll('[data-progress]').forEach(el => {
        setTimeout(() => { el.style.width = el.dataset.progress + '%'; }, 200);
    });
}

// ── UPLOAD PREVIEW ────────────────────────────────────────────
function initUpload() {
    const zone    = document.querySelector('.upload-zone');
    const input   = zone?.querySelector('input[type="file"]');
    const preview = document.querySelector('.upload-preview');
    if (!zone || !input || !preview) return;

    const show = file => {
        if (!file.type.startsWith('image/')) return;
        const r = new FileReader();
        r.onload = e => { preview.innerHTML = `<img src="${e.target.result}" alt="Aperçu">`; };
        r.readAsDataURL(file);
    };

    ['dragenter','dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('dragover'); }));
    ['dragleave','drop'].forEach(ev => zone.addEventListener(ev, e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        if (ev === 'drop' && e.dataTransfer?.files[0]) { input.files = e.dataTransfer.files; show(e.dataTransfer.files[0]); }
    }));
    input.addEventListener('change', () => { if (input.files[0]) show(input.files[0]); });
}

// ── CONFIRM LINKS ─────────────────────────────────────────────
function initConfirm() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
    });
}

// ── AUTO-DISMISS ALERTS ───────────────────────────────────────
function initAutoDismiss() {
    document.querySelectorAll('.alert.auto-dismiss').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-4px)';
            setTimeout(() => el.remove(), 400);
        }, 4000);
    });
}

// ── SEARCH DEBOUNCE ───────────────────────────────────────────
function initSearch() {
    const el = document.querySelector('#search-live');
    if (!el) return;
    let t;
    el.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => el.closest('form')?.submit(), 600);
    });
}

// ── CARD THUMBNAILS: error fallback ──────────────────────────
function initCardImages() {
    document.querySelectorAll('.card-thumbnail').forEach(img => {
        img.addEventListener('error', () => {
            img.closest('.card-image-wrap, .admin-thumb')?.classList.add('img-error');
            img.style.display = 'none';
        });
    });
}

// ── LEVEL BADGE CLASS ─────────────────────────────────────────
function getLevelClass(lvl) {
    if (lvl >= 51) return 'lv-legendaire';
    if (lvl >= 36) return 'lv-grand';
    if (lvl >= 21) return 'lv-elite';
    if (lvl >= 11) return 'lv-etat';
    if (lvl >= 6)  return 'lv-apprenti';
    return 'lv-novice';
}

// ── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initXpBars();
    initUpload();
    initConfirm();
    initAutoDismiss();
    initSearch();
    initCardImages();
});
