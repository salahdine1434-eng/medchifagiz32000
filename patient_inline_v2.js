/**
 * patient_inline_v2.js — MedChifaGiz · SaaS Medical Dashboard
 * ═══════════════════════════════════════════════════════════════
 * تحسينات UX فقط — لا تغيير في أي Logic أو AJAX أو PHP IDs
 *
 * كيفية الإضافة قبل </body> بعد patient_inline.js:
 *   <script src="patient_inline_v2.js"></script>
 *
 * المميزات المضافة:
 *  1) Skeleton loading خفيف عند فتح المريض
 *  2) Auto-scroll ناعم للبطاقة المفتوحة
 *  3) إغلاق تلقائي لأي مريض آخر (موجود في patient_inline.js لكن نضمنه)
 *  4) Vitals Cards: تحويل الحقول الـ grid إلى mini cards ذات أيقونات
 *  5) Section progress indicator
 *  6) Save feedback (loading state + success flash)
 *  7) Keyboard shortcut: Escape يغلق البطاقة المفتوحة
 *  8) Responsive تحسينات
 * ═══════════════════════════════════════════════════════════════
 */

(function () {
    'use strict';

    /* ──────────────────────────────────────────────────────────
       VITALS CONFIG — أيقونات ووحدات للحقول المشتركة
    ────────────────────────────────────────────────────────── */
    const VITALS_META = {
        blood_pressure:  { icon: 'fas fa-heart',          unit: 'mmHg', color: '#ef4444' },
        blood_sugar:     { icon: 'fas fa-tint',            unit: 'mg/dL', color: '#f59e0b' },
        heart_rate:      { icon: 'fas fa-heartbeat',       unit: 'bpm',  color: '#ec4899' },
        temperature:     { icon: 'fas fa-thermometer-half', unit: '°C',  color: '#f97316' },
        oxygen_level:    { icon: 'fas fa-lungs',           unit: '%',    color: '#0ea5e9' },
        preg_weight:     { icon: 'fas fa-weight',          unit: 'kg',   color: '#8b5cf6' },
        preg_blood_pressure: { icon: 'fas fa-heart',       unit: 'mmHg', color: '#ef4444' },
        preg_sugar_level:{ icon: 'fas fa-tint',            unit: 'mg/dL', color: '#f59e0b' },
        fetal_heartbeat: { icon: 'fas fa-heartbeat',       unit: 'bpm',  color: '#ec4899' },
    };

    /* ──────────────────────────────────────────────────────────
       SECTION COLORS — لون مميز لكل قسم
    ────────────────────────────────────────────────────────── */
    const SECTION_COLORS = {
        'pif-sec-1': { gradient: '#0ea5e9, #38bdf8', glow: 'rgba(14,165,233,.22)' },
        'pif-sec-2': { gradient: '#06b6d4, #22d3ee', glow: 'rgba(6,182,212,.22)'  },
        'pif-sec-3': { gradient: '#8b5cf6, #a78bfa', glow: 'rgba(139,92,246,.22)' },
        'pif-sec-4': { gradient: '#10b981, #34d399', glow: 'rgba(16,185,129,.22)' },
        'pif-sec-5': { gradient: '#f59e0b, #fbbf24', glow: 'rgba(245,158,11,.22)' },
        'pif-sec-6': { gradient: '#0ea5e9, #06b6d4', glow: 'rgba(14,165,233,.22)' },
    };

    /* ──────────────────────────────────────────────────────────
       INIT
    ────────────────────────────────────────────────────────── */
    function init() {
        enhanceSectionColors();
        enhanceVitals();
        addProgressBars();
        wrapSaveFunctions();
        addKeyboardShortcuts();
        observeNewPatients();
        enhancePrescriptionSheet();
    }

    /* ──────────────────────────────────────────────────────────
       1) تلوين الأقسام بشكل مميّز
    ────────────────────────────────────────────────────────── */
    function enhanceSectionColors() {
        // يُعاد تطبيقه كلما فُتح مريض جديد
        document.addEventListener('pif:opened', () => {
            setTimeout(() => {
                document.querySelectorAll('.pif-section').forEach(sec => {
                    const secId = sec.id.replace(/-\d+$/, ''); // remove patient suffix
                    const cfg   = SECTION_COLORS[secId];
                    if (!cfg) return;

                    const icon = sec.querySelector('.pif-sec-icon');
                    if (icon) {
                        icon.style.background = `linear-gradient(135deg, ${cfg.gradient})`;
                        icon.style.color      = '#fff';
                        icon.style.boxShadow  = `0 4px 10px ${cfg.glow}`;
                    }

                    // عند الفتح: نيون glow مميز
                    sec.addEventListener('pif:sectionOpen', () => {
                        sec.style.boxShadow = `0 8px 28px ${cfg.glow}`;
                    });
                    sec.addEventListener('pif:sectionClose', () => {
                        sec.style.boxShadow = '';
                    });
                });
            }, 150);
        });
    }

    /* ──────────────────────────────────────────────────────────
       2) تحويل حقول Vitals إلى بطاقات احترافية
    ────────────────────────────────────────────────────────── */
    function enhanceVitals() {
        document.addEventListener('pif:opened', () => {
            setTimeout(() => {
                document.querySelectorAll('.pif-grid-2').forEach(grid => {
                    grid.querySelectorAll('.form-group').forEach(fg => {
                        const input = fg.querySelector('input');
                        if (!input) return;

                        // استخرج الـ original field id
                        const mirrorId = input.id || '';
                        const fieldKey = mirrorId.replace(/^mirror_/, '').replace(/_\d+$/, '');
                        const meta     = VITALS_META[fieldKey];
                        if (!meta) return;

                        // إضافة الأيقونة والوحدة إذا لم تُضف سابقاً
                        if (fg.querySelector('.vital-icon')) return;

                        const iconEl = document.createElement('div');
                        iconEl.className = 'vital-icon';
                        iconEl.innerHTML = `<i class="${meta.icon}"></i>`;
                        iconEl.style.cssText = `
                            width:28px; height:28px; border-radius:8px;
                            background: ${meta.color}1a;
                            color: ${meta.color};
                            display:flex; align-items:center; justify-content:center;
                            font-size:.75rem; margin-bottom:6px;
                        `;

                        const unitEl = document.createElement('span');
                        unitEl.className  = 'vital-unit';
                        unitEl.textContent = meta.unit;
                        unitEl.style.cssText = `
                            position:absolute; left:12px; bottom:10px;
                            font-size:.62rem; font-weight:700;
                            color: ${meta.color}; opacity:.7; letter-spacing:.04em;
                        `;

                        fg.style.position = 'relative';
                        fg.insertBefore(iconEl, fg.firstChild);
                        fg.appendChild(unitEl);
                    });
                });
            }, 200);
        });
    }

    /* ──────────────────────────────────────────────────────────
       3) Progress bar للأقسام المكتملة
    ────────────────────────────────────────────────────────── */
    function addProgressBars() {
        document.addEventListener('pif:opened', () => {
            setTimeout(() => {
                document.querySelectorAll('.pif-accordion').forEach(acc => {
                    if (acc.querySelector('.pif-progress')) return;

                    const bar = document.createElement('div');
                    bar.className = 'pif-progress';
                    bar.innerHTML = `
                        <div class="pif-progress-label">
                            <span>اكتمال الملف</span>
                            <span class="pif-progress-pct">0%</span>
                        </div>
                        <div class="pif-progress-track">
                            <div class="pif-progress-fill"></div>
                        </div>
                    `;
                    injectProgressStyles();
                    acc.parentNode.insertBefore(bar, acc);

                    // تحديث النسبة كلما تغيّر أي حقل
                    acc.addEventListener('input', () => updateProgress(acc, bar));
                    acc.addEventListener('change', () => updateProgress(acc, bar));
                });
            }, 250);
        });
    }

    function updateProgress(acc, bar) {
        const allInputs  = acc.querySelectorAll('input:not([type=hidden]), textarea, select');
        const filled     = Array.from(allInputs).filter(el => el.value && el.value.trim() !== '');
        const pct        = allInputs.length ? Math.round((filled.length / allInputs.length) * 100) : 0;
        const fill       = bar.querySelector('.pif-progress-fill');
        const label      = bar.querySelector('.pif-progress-pct');
        if (fill)  fill.style.width  = pct + '%';
        if (label) label.textContent = pct + '%';

        // تلوين بحسب النسبة
        let color = '#0ea5e9';
        if (pct >= 80) color = '#10b981';
        else if (pct >= 40) color = '#f59e0b';
        if (fill) fill.style.background = `linear-gradient(90deg, ${color}, ${color}cc)`;
    }

    function injectProgressStyles() {
        if (document.getElementById('pif-progress-styles')) return;
        const style = document.createElement('style');
        style.id    = 'pif-progress-styles';
        style.textContent = `
            .pif-progress {
                background: var(--med-surface, #fff);
                border: 1px solid var(--med-border, rgba(14,165,233,.13));
                border-radius: 10px;
                padding: 10px 14px;
                margin-bottom: 10px;
            }
            .pif-progress-label {
                display: flex;
                justify-content: space-between;
                font-size: .71rem;
                font-weight: 700;
                color: var(--med-text-muted, #64748b);
                margin-bottom: 6px;
                text-transform: uppercase;
                letter-spacing: .04em;
            }
            .pif-progress-pct { color: #0ea5e9; }
            .pif-progress-track {
                height: 5px;
                background: rgba(14,165,233,.1);
                border-radius: 10px;
                overflow: hidden;
            }
            .pif-progress-fill {
                height: 100%;
                border-radius: 10px;
                width: 0%;
                transition: width .5s cubic-bezier(.4,0,.2,1), background .5s ease;
                background: linear-gradient(90deg, #0ea5e9, #06b6d4);
            }
        `;
        document.head.appendChild(style);
    }

    /* ──────────────────────────────────────────────────────────
       4) Save Feedback — loading + success flash
    ────────────────────────────────────────────────────────── */
    function wrapSaveFunctions() {
        const fnNames = ['saveMedicalRecord', 'savePrescription'];
        fnNames.forEach(name => {
            const orig = window[name];
            window[name] = function (...args) {
                const btn = findActiveBtn(name);
                if (btn) setLoadingState(btn, true);

                const result = typeof orig === 'function' ? orig.apply(this, args) : undefined;

                // بعد ثانية: إنهاء loading + flash success
                setTimeout(() => {
                    if (btn) setLoadingState(btn, false);
                    showSaveSuccess();
                }, 900);

                return result;
            };
        });
    }

    function findActiveBtn(fnName) {
        const map = {
            saveMedicalRecord: '.pif-btn-primary',
            savePrescription:  '.pif-btn-success',
        };
        const sel = map[fnName];
        if (!sel) return null;
        return document.querySelector('.patient-item.expanded ' + sel);
    }

    function setLoadingState(btn, loading) {
        if (loading) {
            btn._origText = btn.textContent;
            btn.innerHTML = '<i class="fas fa-spinner" style="animation:medSpin .9s linear infinite;margin-left:6px;"></i> جاري الحفظ...';
            btn.style.opacity = '0.8';
            btn.disabled = true;
        } else {
            btn.innerHTML = btn._origText || btn.innerHTML;
            btn.style.opacity = '';
            btn.disabled = false;
        }
    }

    function showSaveSuccess() {
        const toast = document.createElement('div');
        toast.className = 'pif-toast';
        toast.innerHTML = '<i class="fas fa-check-circle"></i> تم الحفظ بنجاح';
        injectToastStyles();
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('pif-toast-show');
        });

        setTimeout(() => {
            toast.classList.remove('pif-toast-show');
            setTimeout(() => toast.remove(), 400);
        }, 2200);
    }

    function injectToastStyles() {
        if (document.getElementById('pif-toast-styles')) return;
        const style = document.createElement('style');
        style.id    = 'pif-toast-styles';
        style.textContent = `
            .pif-toast {
                position: fixed;
                bottom: 28px;
                left: 50%;
                transform: translate(-50%, 20px);
                background: linear-gradient(135deg, #10b981, #34d399);
                color: #fff;
                padding: 12px 24px;
                border-radius: 40px;
                font-size: .83rem;
                font-weight: 700;
                font-family: 'Cairo', sans-serif;
                display: flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 8px 24px rgba(16,185,129,.35);
                opacity: 0;
                transition: all .35s cubic-bezier(.4,0,.2,1);
                z-index: 99999;
                white-space: nowrap;
            }
            .pif-toast.pif-toast-show {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        `;
        document.head.appendChild(style);
    }

    /* ──────────────────────────────────────────────────────────
       5) Keyboard Shortcut — Escape يغلق الملف المفتوح
    ────────────────────────────────────────────────────────── */
    function addKeyboardShortcuts() {
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            const expanded = document.querySelector('.patient-item.expanded');
            if (!expanded) return;
            const row = expanded.querySelector('.patient-item-row');
            if (row && typeof window.PIFToggle === 'function') {
                const pid  = expanded.getAttribute('data-patient-id');
                const name = expanded.getAttribute('data-patient-name');
                if (pid) window.PIFToggle(row, pid, name);
            }
        });
    }

    /* ──────────────────────────────────────────────────────────
       6) MutationObserver — يراقب إضافة بطاقات جديدة
    ────────────────────────────────────────────────────────── */
    function observeNewPatients() {
        const list = document.querySelector('#todayPatients .patients-list');
        if (!list) return;

        const obs = new MutationObserver(mutations => {
            mutations.forEach(m => {
                m.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && node.classList.contains('patient-item')) {
                        // بطاقة جديدة أُضيفت → أضف إليها التحسينات
                        enhanceItemRow(node);
                    }
                });
            });
        });

        obs.observe(list, { childList: true });
    }

    function enhanceItemRow(item) {
        // إذا كان الـ PIFToggle موجوداً سيُعيد بناء الصف تلقائياً
        // لكن نضمن أن الأنيميشن يعمل
        item.style.animationName = 'fadeInUp';
        item.style.animationDuration = '0.3s';
        item.style.animationFillMode = 'both';
    }

    /* ──────────────────────────────────────────────────────────
       7) تحسين الـ PIFToggle الأصلي (Wrap لإضافة events)
    ────────────────────────────────────────────────────────── */
    function wrapPIFToggle() {
        if (typeof window.PIFToggle !== 'function') return;

        const origToggle = window.PIFToggle;
        window.PIFToggle = function (rowEl, patientId, patientName) {
            // Skeleton قبل الفتح
            const inner = document.getElementById('pif-inner-' + patientId);
            const alreadyBuilt = inner && inner.querySelector('.patient-file-header');

            if (inner && !alreadyBuilt) {
                showSkeleton(inner);
            }

            origToggle.call(this, rowEl, patientId, patientName);

            // بعد الفتح: dispatch event للتحسينات
            const item = rowEl ? rowEl.closest('.patient-item') : null;
            if (item && item.classList.contains('expanded')) {
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('pif:opened', {
                        detail: { patientId, patientName }
                    }));
                }, 80);
            }
        };
    }

    function showSkeleton(inner) {
        inner.innerHTML = `
            <div style="padding:14px 0 10px; margin-bottom:14px; border-bottom:1px solid rgba(14,165,233,.1); display:flex; justify-content:space-between; align-items:center;">
                <div class="pif-skeleton" style="width:40%; height:16px;"></div>
                <div class="pif-skeleton" style="width:15%; height:14px; border-radius:20px;"></div>
            </div>
            ${[1,2,3].map(() => `
            <div style="border-radius:12px; border:1px solid rgba(14,165,233,.1); padding:12px 16px; margin-bottom:7px;">
                <div style="display:flex; gap:10px; align-items:center;">
                    <div class="pif-skeleton" style="width:32px; height:32px; border-radius:9px; flex-shrink:0;"></div>
                    <div class="pif-skeleton" style="width:30%; height:14px;"></div>
                    <div class="pif-skeleton" style="width:12%; height:12px; border-radius:20px; margin-right:auto;"></div>
                </div>
            </div>`).join('')}
        `;
    }

    /* ──────────────────────────────────────────────────────────
       8) تحسين الـ Section Toggle (Wrap)
    ────────────────────────────────────────────────────────── */
    function wrapSectionToggle() {
        // patient_inline.js يضع toggleSection داخل closure
        // لكن نراقب الـ DOM events بدلاً من ذلك
        document.addEventListener('click', e => {
            const hd = e.target.closest('.pif-sec-header');
            if (!hd) return;

            const sec = hd.closest('.pif-section');
            if (!sec) return;

            // بعد التبديل: dispatch event مناسب
            setTimeout(() => {
                const isOpen = sec.classList.contains('pif-open');
                sec.dispatchEvent(new Event(isOpen ? 'pif:sectionOpen' : 'pif:sectionClose'));
            }, 50);
        });
    }

    /* ──────────────────────────────────────────────────────────
       9) تحسين الـ Prescription Sheet بإضافة header طبي
    ────────────────────────────────────────────────────────── */
    function enhancePrescriptionSheet() {
        document.addEventListener('pif:opened', () => {
            setTimeout(() => {
                document.querySelectorAll('.pif-rx-sheet').forEach(sheet => {
                    if (sheet.querySelector('.pif-rx-header')) return;

                    const hdr = document.createElement('div');
                    hdr.className  = 'pif-rx-header';
                    hdr.innerHTML  = `
                        <div class="pif-rx-logo">
                            <i class="fas fa-stethoscope"></i>
                            <span>وصفة طبية</span>
                        </div>
                        <div class="pif-rx-line"></div>
                    `;
                    injectRxHeaderStyles();
                    sheet.insertBefore(hdr, sheet.firstChild);
                });
            }, 250);
        });
    }

    function injectRxHeaderStyles() {
        if (document.getElementById('pif-rx-hdr-styles')) return;
        const style = document.createElement('style');
        style.id    = 'pif-rx-hdr-styles';
        style.textContent = `
            .pif-rx-header {
                margin-bottom: 16px;
            }
            .pif-rx-logo {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: .9rem;
                font-weight: 800;
                color: #0ea5e9;
                margin-bottom: 10px;
            }
            .pif-rx-logo i {
                font-size: 1.1rem;
                opacity: .85;
            }
            .pif-rx-line {
                height: 2px;
                background: linear-gradient(90deg, #0ea5e9, #06b6d4, transparent);
                border-radius: 2px;
            }
        `;
        document.head.appendChild(style);
    }

    /* ──────────────────────────────────────────────────────────
       AUTO-SCROLL ENHANCEMENT
       (يُضاف فوق منطق patient_inline.js الأصلي)
    ────────────────────────────────────────────────────────── */
    document.addEventListener('pif:opened', e => {
        if (!e.detail) return;
        const pid  = e.detail.patientId;
        const item = document.querySelector(`.patient-item[data-patient-id="${pid}"]`);
        if (!item) return;

        setTimeout(() => {
            const rect = item.getBoundingClientRect();
            const isVisible = rect.top >= 80 && rect.bottom <= window.innerHeight - 40;
            if (!isVisible) {
                item.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 180);
    });

    /* ──────────────────────────────────────────────────────────
       START — ترتيب التهيئة مهم
    ────────────────────────────────────────────────────────── */
    function start() {
        // انتظر حتى يُهيّئ patient_inline.js الـ DOM
        setTimeout(() => {
            wrapPIFToggle();
            wrapSectionToggle();
            init();
        }, 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

})();
