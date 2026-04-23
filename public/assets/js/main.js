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
    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translateX(16px)';
        t.style.transition = '.2s ease';
        setTimeout(() => t.remove(), 200);
    }, ms);
}

// ── ANIMATED BARS ─────────────────────────────────────────────
function initAnimatedBars() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(el => {
            if (!el.isIntersecting) return;
            const target = el.target;

            // XP bars
            if (target.dataset.xpWidth !== undefined) {
                setTimeout(() => { target.style.width = target.dataset.xpWidth + '%'; }, 100);
            }
            // Progress / rarity bars
            if (target.dataset.progress !== undefined) {
                setTimeout(() => { target.style.width = target.dataset.progress + '%'; }, 100);
            }
            observer.unobserve(target);
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-xp-width], [data-progress]').forEach(el => observer.observe(el));
}

// ── UPLOAD PREVIEW ────────────────────────────────────────────
function initUpload() {
    document.querySelectorAll('.upload-zone').forEach(zone => {
        const input   = zone.querySelector('input[type="file"]');
        const preview = zone.closest('form, .community-image-field, .panel-body')?.querySelector('.upload-preview');
        if (!input) return;

        const show = file => {
            if (!file.type.startsWith('image/') || !preview) return;
            const r = new FileReader();
            r.onload = e => { preview.innerHTML = `<img src="${e.target.result}" alt="Aperçu">`; };
            r.readAsDataURL(file);
        };

        ['dragenter','dragover'].forEach(ev =>
            zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('dragover'); })
        );
        ['dragleave','drop'].forEach(ev =>
            zone.addEventListener(ev, e => {
                e.preventDefault();
                zone.classList.remove('dragover');
                if (ev === 'drop' && e.dataTransfer?.files[0]) {
                    input.files = e.dataTransfer.files;
                    show(e.dataTransfer.files[0]);
                }
            })
        );
        input.addEventListener('change', () => { if (input.files[0]) show(input.files[0]); });
    });
}

// ── CONFIRM LINKS ─────────────────────────────────────────────
function initConfirm() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
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

// ── CARD IMAGES: error fallback ───────────────────────────────
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

// ── NAVBAR: close dropdowns on outside click ──────────────────
function initNavDropdowns() {
    // Close user dropdown when clicking outside
    document.addEventListener('click', e => {
        const userGroup = e.target.closest('.navbar-user');
        document.querySelectorAll('.navbar-user').forEach(el => {
            if (el !== userGroup) el.classList.remove('force-open');
        });
    });

    // Keyboard navigation for dropdowns
    document.querySelectorAll('.nav-link.has-dropdown').forEach(btn => {
        btn.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const group = btn.closest('.navbar-group');
                if (group) group.classList.toggle('force-open');
            }
            if (e.key === 'Escape') {
                const group = btn.closest('.navbar-group');
                if (group) group.classList.remove('force-open');
            }
        });
    });
}

// ── CARD HOVER SOUND (optional subtle) ───────────────────────
function initCardEffects() {
    // Add subtle entrance animation to cards when they enter viewport
    if (!window.IntersectionObserver) return;
    const obs = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.animationDelay = '0s';
                    entry.target.classList.add('card-visible');
                }, i * 30);
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05, rootMargin: '50px' });

    document.querySelectorAll('.card-item').forEach(card => obs.observe(card));
}

// ── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initAnimatedBars();
    initUpload();
    initConfirm();
    initAutoDismiss();
    initSearch();
    initCardImages();
    initNavDropdowns();
    initCardEffects();
});
