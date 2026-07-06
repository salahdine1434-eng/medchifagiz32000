/* ================================================================
   MedChifaGiz — dr_statistics.js
   صفحة الإحصائيات (رسوم بيانية فقط)
   - يعتمد على Chart.js المحمّل مسبقاً في الصفحة
   - يجلب البيانات الحقيقية من dr_statistics_api.php (AJAX/JSON)
   - يدعم الوضع الليلي/النهاري و RTL
   - لا يلمس أي دالة أو متغيّر موجود
   ضعه قبل </body> بعد dr_dashboard.js:
   <script src="dr_statistics.js"></script>
================================================================ */

(function () {
    'use strict';

    var STAT = {
        charts: {},      // مثيلات Chart.js
        loaded: false,   // هل جُلبت البيانات؟
        data: null
    };

    /* ── ألوان حسب الوضع (ليلي/نهاري) ── */
    function themeColors() {
        var dark = document.body.classList.contains('dark-mode');
        return {
            dark: dark,
            text:  dark ? '#cbd5e1' : '#475569',
            muted: dark ? '#64748b' : '#94a3b8',
            grid:  dark ? 'rgba(255,255,255,0.07)' : 'rgba(148,163,184,0.18)',
            tooltipBg: dark ? '#1e293b' : '#0f172a'
        };
    }

    /* ── لوحة ألوان النظام ── */
    var C = {
        primary: '#0ea5e9',
        accent:  '#06b6d4',
        emerald: '#10b981',
        violet:  '#8b5cf6',
        amber:   '#f59e0b',
        slate:   '#94a3b8'
    };

    /* ════════════════════════════════════════════════
       فتح صفحة الإحصائيات (تُستدعى من الـ sidebar)
    ════════════════════════════════════════════════ */
    window.openStatistics = function () {
        // إخفاء كل الواجهات وإظهار واجهة الإحصائيات
        document.querySelectorAll('.interface').forEach(function (i) {
            i.classList.remove('active');
        });
        var target = document.getElementById('stats-interface');
        if (target) target.classList.add('active');

        // عنوان الصفحة
        var pt = document.getElementById('pageTitle');
        if (pt) pt.textContent = 'الإحصائيات';

        // تفعيل عنصر الـ sidebar + إغلاق أي قوائم منسدلة مفتوحة
        document.querySelectorAll('.snav-direct, .snav-header').forEach(function (el) {
            el.classList.remove('snav-active-direct');
        });
        document.querySelectorAll('.snav-item').forEach(function (i) {
            i.classList.remove('snav-item-active');
        });
        document.querySelectorAll('.snav-body').forEach(function (b) { b.classList.remove('snb-open'); });
        document.querySelectorAll('.snav-header').forEach(function (h) { h.classList.remove('snav-open'); });
        var navEl = document.getElementById('sng-stats');
        if (navEl) navEl.classList.add('snav-active-direct');

        // إغلاق محتويات الكروت المفتوحة (توافقاً مع باقي الواجهات)
        if (typeof closeAllCardContents === 'function') closeAllCardContents();

        // جلب البيانات (مرة واحدة)، أو إعادة الرسم إن كانت موجودة
        if (!STAT.loaded) {
            loadStatistics();
        } else {
            renderAll(STAT.data);
        }

        if (window.innerWidth <= 768 && typeof closeSidebar === 'function') closeSidebar();
    };

    /* ── جلب البيانات عبر AJAX ── */
    function loadStatistics() {
        var loadingEl = document.getElementById('statsLoading');
        var errEl = document.getElementById('statsError');
        var wrapEl = document.getElementById('statsWrap');
        if (loadingEl) loadingEl.style.display = 'flex';
        if (errEl) errEl.style.display = 'none';
        if (wrapEl) wrapEl.style.display = 'none';

        fetch('dr_statistics_api.php', {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json || !json.ok) throw new Error('bad');
                STAT.data = json;
                STAT.loaded = true;
                if (loadingEl) loadingEl.style.display = 'none';
                if (wrapEl) wrapEl.style.display = 'flex';
                renderAll(json);
            })
            .catch(function () {
                if (loadingEl) loadingEl.style.display = 'none';
                if (errEl) errEl.style.display = 'flex';
            });
    }

    /* ── أداة: ضبط حالة "لا توجد بيانات" ── */
    function setEmpty(boxId, isEmpty) {
        var box = document.getElementById(boxId);
        if (!box) return;
        box.classList.toggle('is-empty', !!isEmpty);
    }

    /* ── أداة: إتلاف مخطط قديم قبل إعادة الرسم ── */
    function destroy(key) {
        if (STAT.charts[key]) {
            STAT.charts[key].destroy();
            delete STAT.charts[key];
        }
    }

    /* ════════════════════════════════════════════════
       رسم كل المخططات
    ════════════════════════════════════════════════ */
    function renderAll(d) {
        if (!d || typeof Chart === 'undefined') return;
        var t = themeColors();

        Chart.defaults.font.family = "'Cairo', sans-serif";

        renderFilesBar(d.files, t);
        renderWeeklyBar(d.weekly, t);
        renderDoughnut('statResidencyChart', 'boxResidency',
            ['مقيم', 'غير مقيم'], d.residency, [C.primary, C.amber], t);
        renderDoughnut('statMenChart', 'boxMen',
            ['رجال مقيمون', 'رجال غير مقيمين'], d.men, [C.primary, C.amber], t);
        renderDoughnut('statWomenChart', 'boxWomen',
            ['نساء مقيمات', 'نساء غير مقيمات'], d.women, [C.violet, C.amber], t);
    }

    /* القسم 1 — Bar: إحصائيات الملفات الطبية */
    function renderFilesBar(files, t) {
        var el = document.getElementById('statFilesChart');
        if (!el) return;
        files = files || {};
        var vals = [
            files.total || 0,
            files.created_week || 0,
            files.updated_month || 0,
            files.reviewed_today || 0
        ];
        setEmpty('boxFiles', vals.every(function (v) { return v === 0; }));
        destroy('files');
        STAT.charts.files = new Chart(el, {
            type: 'bar',
            data: {
                labels: [
                    'إجمالي الملفات',
                    'المنشأة هذا الأسبوع',
                    'المحدّثة هذا الشهر',
                    'المعاينة اليوم'
                ],
                datasets: [{
                    label: 'عدد الملفات',
                    data: vals,
                    backgroundColor: [C.primary, C.accent, C.violet, C.emerald],
                    borderRadius: 10,
                    maxBarThickness: 70
                }]
            },
            options: barOptions(t)
        });
    }

    /* القسم 2 — Bar: النشاط الأسبوعي */
    function renderWeeklyBar(weekly, t) {
        var el = document.getElementById('statWeeklyChart');
        if (!el) return;
        weekly = weekly || [0, 0, 0, 0, 0, 0, 0];
        setEmpty('boxWeekly', weekly.every(function (v) { return v === 0; }));
        destroy('weekly');
        STAT.charts.weekly = new Chart(el, {
            type: 'bar',
            data: {
                labels: ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
                datasets: [{
                    label: 'المرضى الذين تمت معاينتهم',
                    data: weekly,
                    backgroundColor: C.primary,
                    hoverBackgroundColor: C.accent,
                    borderRadius: 8,
                    maxBarThickness: 46
                }]
            },
            options: barOptions(t)
        });
    }

    /* خيارات مشتركة للـ Bar */
    function barOptions(t) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    rtl: true,
                    backgroundColor: t.tooltipBg,
                    titleFont: { family: "'Cairo', sans-serif" },
                    bodyFont: { family: "'Cairo', sans-serif" },
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: t.text, font: { family: "'Cairo', sans-serif" } },
                    grid: { color: t.grid }
                },
                x: {
                    ticks: { color: t.text, font: { family: "'Cairo', sans-serif" } },
                    grid: { display: false }
                }
            }
        };
    }

    /* أقسام 3/4/5 — Doughnut مع النسب المئوية */
    function renderDoughnut(canvasId, boxId, labels, values, colors, t) {
        var el = document.getElementById(canvasId);
        if (!el) return;
        values = values || [0, 0];
        var total = values.reduce(function (a, b) { return a + b; }, 0);
        setEmpty(boxId, total === 0);
        var key = canvasId;
        destroy(key);
        STAT.charts[key] = new Chart(el, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: t.dark ? '#0f172a' : '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            color: t.text,
                            font: { family: "'Cairo', sans-serif", size: 12 },
                            padding: 14,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        backgroundColor: t.tooltipBg,
                        bodyFont: { family: "'Cairo', sans-serif" },
                        titleFont: { family: "'Cairo', sans-serif" },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                var v = ctx.parsed || 0;
                                var pct = total > 0 ? Math.round((v / total) * 100) : 0;
                                return ' ' + ctx.label + ': ' + v + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /* ════════════════════════════════════════════════
       إعادة الرسم عند تبديل الوضع الليلي/النهاري
       (مراقبة class على <body> دون لمس toggleTheme)
    ════════════════════════════════════════════════ */
    var _lastDark = document.body.classList.contains('dark-mode');
    var observer = new MutationObserver(function () {
        var nowDark = document.body.classList.contains('dark-mode');
        if (nowDark === _lastDark) return;
        _lastDark = nowDark;
        var iface = document.getElementById('stats-interface');
        if (STAT.loaded && iface && iface.classList.contains('active')) {
            renderAll(STAT.data);
        }
    });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

})();
