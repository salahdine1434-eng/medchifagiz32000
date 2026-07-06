/* ================================================================
   MedChifaGiz — dashboard_fixes.js
   أضف هذا السكريبت قبل </body> مباشرة، بعد dr_dashboard.js

   <script src="dashboard_fixes.js"></script>
================================================================ */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        fixSettingsNavItem();
        buildAccordion();
    });

    /* ══════════════════════════════════════════════════════
       1) إصلاح إيقونة الإعدادات في الـ Sidebar
          — تأكد من عدم التكرار فقط، بدون إضافة أي عنصر
            لأن الـ HTML يحتوي بالفعل على sng-settings
    ══════════════════════════════════════════════════════ */
    function fixSettingsNavItem() {
        var nav = document.querySelector('.sidebar-nav');
        if (!nav) return;

        // حذف أي عنصر إعدادات مكرر أضيف بواسطة هذه الدالة سابقاً
        // (يتعرف عليه بكونه .nav-item وليس .snav-header وليس .logout-item)
        var duplicates = Array.from(nav.querySelectorAll('.nav-item')).filter(function(el) {
            return !el.classList.contains('logout-item') &&
                   el.textContent.trim().includes('الإعدادات');
        });
        duplicates.forEach(function(el) { el.remove(); });

        // حذف أي nav-section-label مكرر "الحساب" أضيف بواسطة هذه الدالة
        var dupLabels = Array.from(nav.querySelectorAll('.nav-section-label')).filter(function(el) {
            return el.textContent.trim() === 'الحساب';
        });
        dupLabels.forEach(function(el) { el.remove(); });

        // الـ HTML يحتوي بالفعل على sng-settings — لا نضيف شيئاً
    }

    /* ══════════════════════════════════════════════════════
       2) تحويل Wizard إلى Accordion داخل الـ Patient Modal
    ══════════════════════════════════════════════════════ */
    function buildAccordion() {
        var modal = document.getElementById('patientFileModal');
        if (!modal) return;

        // تعريف الـ Sections (مرتبطة بالـ steps الأصلية)
        var sections = [
            {
                id: 'acc-step1',
                stepId: 'step1',
                icon: 'fas fa-user',
                title: 'المعلومات الشخصية',
                color: '#0ea5e9',
                actions: []
            },
            {
                id: 'acc-step2',
                stepId: 'step2',
                icon: 'fas fa-stethoscope',
                title: 'الفحص والأعراض',
                color: '#06b6d4',
                actions: []
            },
            {
                id: 'acc-step3',
                stepId: 'step3',
                icon: 'fas fa-flask',
                title: 'الفحوصات التكميلية',
                color: '#8b5cf6',
                actions: []
            },
            {
                id: 'acc-step4',
                stepId: 'step4',
                icon: 'fas fa-prescription',
                title: 'الوصفة الطبية',
                color: '#10b981',
                actions: [
                    { label: '💾 حفظ الوصفة', fn: 'savePrescription()', cls: 'acc-btn-success' },
                    { label: '🖨️ طباعة', fn: 'printPrescription()', cls: 'acc-btn-print' }
                ]
            },
            {
                id: 'acc-step5',
                stepId: 'step5',
                icon: 'fas fa-baby',
                title: 'متابعة الحمل',
                color: '#f59e0b',
                conditional: true,
                actions: []
            },
            {
                id: 'acc-step6',
                stepId: 'step6',
                icon: 'fas fa-calendar-plus',
                title: 'المواعيد القادمة',
                color: '#0ea5e9',
                actions: [
                    { label: '🖨️ طباعة السجل', fn: 'printMedicalRecord()', cls: 'acc-btn-print' },
                    { label: '💾 حفظ الملف', fn: 'saveMedicalRecord()', cls: 'acc-btn-primary' }
                ]
            }
        ];

        // بناء هيكل الـ Modal الجديد
        var content = modal.querySelector('.patient-modal-content');
        if (!content) return;

        // احتفظ بالـ steps الأصلية في مكانها (مخفية بـ CSS)
        // نبني الـ accordion كطبقة جديدة

        // 1) إنشاء الـ header الجديد
        var oldClose = content.querySelector('.close-patient-modal');
        var oldH2 = content.querySelector('h2');

        var header = document.createElement('div');
        header.className = 'patient-modal-header';
        header.innerHTML =
            '<div class="patient-modal-title">' +
            '<i class="fas fa-notes-medical"></i>' +
            '<span>الملف الطبي للمريض</span>' +
            '</div>' +
            '<button class="patient-modal-close" onclick="closePatientFile()">' +
            '<i class="fas fa-times"></i>' +
            '</button>';

        // 2) حاوية الـ Accordion
        var scrollArea = document.createElement('div');
        scrollArea.className = 'accordion-scroll-area';

        var accordion = document.createElement('div');
        accordion.className = 'accordion-container';
        accordion.id = 'patientAccordion';

        sections.forEach(function (sec, idx) {
            // هل الـ step موجودة في الـ DOM؟
            var stepEl = document.getElementById(sec.stepId);
            if (!stepEl) return;

            // إذا كانت conditional (الحمل) وغير موجودة skip
            if (sec.conditional && !stepEl) return;

            // نسخ محتوى الـ step (بدون أزرار التنقل القديمة)
            var clone = stepEl.cloneNode(true);
            // نزيل أزرار الـ wizard القديمة من الـ clone
            var stepBtns = clone.querySelector('.step-buttons');
            if (stepBtns) stepBtns.remove();
            var nextBtns = clone.querySelectorAll('.next-btn, .prev-btn');
            nextBtns.forEach(function(b){ b.remove(); });
            // نزيل H3 الأصلية (سيُعرض في الهيدر)
            var h3 = clone.querySelector('h3');
            if (h3) h3.remove();

            // بناء الـ Section
            var section = document.createElement('div');
            section.className = 'accordion-section';
            section.id = 'acc-' + sec.stepId;

            // Header
            var hd = document.createElement('div');
            hd.className = 'accordion-header' + (idx === 0 ? ' open' : '');
            hd.setAttribute('onclick', 'toggleAccordion("acc-' + sec.stepId + '")');
            hd.innerHTML =
                '<div class="accordion-icon" style="background:' + sec.color + '1a; color:' + sec.color + '">' +
                '<i class="' + sec.icon + '"></i>' +
                '</div>' +
                '<span class="accordion-title">' + sec.title + '</span>' +
                '<span class="accordion-status" id="status-acc-' + sec.stepId + '">جديد</span>' +
                '<i class="fas fa-chevron-down accordion-arrow"></i>';

            // Body
            var body = document.createElement('div');
            body.className = 'accordion-body' + (idx === 0 ? ' open' : '');
            body.id = 'body-acc-' + sec.stepId;

            var inner = document.createElement('div');
            inner.className = 'accordion-body-inner';

            // نقل محتوى الـ step (الـ form-groups فقط)
            inner.innerHTML = clone.innerHTML;

            body.appendChild(inner);

            // الأزرار الخاصة بهذا الـ section
            if (sec.actions && sec.actions.length > 0) {
                var actionsDiv = document.createElement('div');
                actionsDiv.className = 'accordion-actions';
                sec.actions.forEach(function(act){
                    var btn = document.createElement('button');
                    btn.className = act.cls;
                    btn.textContent = act.label;
                    btn.setAttribute('onclick', act.fn);
                    actionsDiv.appendChild(btn);
                });
                body.appendChild(actionsDiv);
            }

            section.appendChild(hd);
            section.appendChild(body);
            accordion.appendChild(section);
        });

        scrollArea.appendChild(accordion);

        // 3) إعادة بناء الـ modal content
        // احتفظ بكل شيء داخل content (الـ steps الأصلية ستبقى مخفية)
        // نضع الـ header الجديد أولاً
        if (oldClose) oldClose.style.display = 'none';
        if (oldH2) oldH2.style.display = 'none';

        content.insertBefore(scrollArea, content.firstChild);
        content.insertBefore(header, content.firstChild);

        // ربط الـ inputs الجديدة (الـ clones) بالـ originals
        syncClonedInputs();
    }

    /* مزامنة الـ inputs المنسوخة مع الأصلية */
    function syncClonedInputs() {
        var accordion = document.getElementById('patientAccordion');
        if (!accordion) return;

        var inputs = accordion.querySelectorAll('input, textarea, select');
        inputs.forEach(function (inp) {
            var id = inp.id;
            if (!id) return;
            inp.addEventListener('input', function () {
                var orig = document.querySelector('.medical-step #' + id);
                if (orig && orig !== inp) orig.value = inp.value;
                markSectionFilled(inp);
            });
            inp.addEventListener('change', function () {
                var orig = document.querySelector('.medical-step #' + id);
                if (orig && orig !== inp) orig.value = inp.value;
                markSectionFilled(inp);
            });
        });
    }

    /* تحديث مؤشر الحالة */
    function markSectionFilled(inp) {
        var section = inp.closest('.accordion-section');
        if (!section) return;
        var statusEl = section.querySelector('.accordion-status');
        if (!statusEl) return;
        var hasValue = Array.from(section.querySelectorAll('input, textarea'))
            .some(function(el){ return el.value && el.value.trim() !== ''; });
        if (hasValue) {
            statusEl.textContent = '✓ مكتمل';
            statusEl.classList.add('filled');
        }
    }

    /* ══════════════════════════════════════════════════════
       وظيفة فتح/إغلاق الـ Accordion (global)
    ══════════════════════════════════════════════════════ */
    window.toggleAccordion = function (sectionId) {
        var allHeaders = document.querySelectorAll('#patientAccordion .accordion-header');
        var allBodies = document.querySelectorAll('#patientAccordion .accordion-body');

        var targetHeader = document.querySelector('#' + sectionId + ' .accordion-header');
        var targetBody = document.getElementById('body-' + sectionId);

        var isOpen = targetHeader && targetHeader.classList.contains('open');

        // أغلق الكل
        allHeaders.forEach(function (h) { h.classList.remove('open'); });
        allBodies.forEach(function (b) { b.classList.remove('open'); });

        // إذا لم يكن مفتوحاً، افتحه
        if (!isOpen && targetHeader && targetBody) {
            targetHeader.classList.add('open');
            targetBody.classList.add('open');
        }
    };

    /* إعادة تعيين الـ Accordion عند فتح الـ modal */
    var origOpenPatientFile = window.openPatientFile;
    window.openPatientFile = function (patientId) {
        if (typeof origOpenPatientFile === 'function') {
            origOpenPatientFile(patientId);
        }
        // افتح أول section تلقائياً
        setTimeout(function () {
            var first = document.querySelector('#patientAccordion .accordion-section');
            if (first) {
                var fHeader = first.querySelector('.accordion-header');
                var fBody = first.querySelector('.accordion-body');
                if (fHeader) fHeader.classList.add('open');
                if (fBody) fBody.classList.add('open');
            }
            // مزامنة الـ inputs إذا ملأ الـ AJAX أي شيء
            syncClonedInputs();
        }, 150);
    };

})();
