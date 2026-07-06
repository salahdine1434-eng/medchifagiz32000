/* ================================================================
   patient_medcomm.js
   قسم "التواصل الطبي" — Front-End فقط، Dummy Data فقط
   لا يوجد أي Fetch / Ajax / API / WebSocket / LocalStorage
   أضفه قبل إغلاق </body>:
   <script src="patient_medcomm.js?v=1"></script>
================================================================ */

(function () {

  /* ── Dummy Doctors + Conversations ───────────────────────── */
  // TODO: سيتم استبدال البيانات الوهمية ببيانات قاعدة البيانات
  var MEDCOMM_DOCTORS = [];

  var MEDCOMM_UNREAD = { d1: 2, d2: 0, d3: 1, d4: 0, d5: 3, d6: 0 };
  var medcommActiveId = null;
  var medcommEmojiList = ["😊", "🙏", "👍", "❤️", "😢", "😷", "🩺", "💊", "🤒", "✅", "❓", "📅"];
  // عدّاد لتمييز كل طلب تحميل رسائل عن الذي قبله (يُستخدم في medcommSelectDoctor
  // و medcommRefreshActiveMessages معاً) — يمنع تطبيق رد "قديم" يصل متأخراً بعد رد أحدث منه.
  var medcommMessagesRequestId = 0;

  // تتبّع آخر حالة تم عرضها فعلياً في الـ DOM لمنع أي Flicker: نحتفظ بمفاتيح
  // الرسائل المعروضة حالياً وعدد الرسائل "غير المؤكدة" (المرسلة محلياً ولم يصل
  // بعد ردّ قاعدة البيانات بها) في نهاية القائمة، حتى نستطيع عند كل تحديث إضافة
  // الجديد فقط دون مسح المحادثة كاملة وإعادة بنائها.
  var medcommLastRenderedId = null;
  var medcommLastRenderedKeys = [];
  var medcommLastRenderedPendingCount = 0;

  function medcommEl(id) { return document.getElementById(id); }

  /* ── عرض قائمة الأطباء ─────────────────────────────────── */
  function medcommRenderList(filterText) {
    var wrap = medcommEl("medcommConvos");
    if (!wrap) return;
    wrap.innerHTML = "";

    var q = (filterText || "").trim().toLowerCase();
    var list = MEDCOMM_DOCTORS.filter(function (d) {
      if (!q) return true;
      return d.name.toLowerCase().indexOf(q) !== -1 || d.spec.toLowerCase().indexOf(q) !== -1;
    });

    if (!list.length) {
      wrap.innerHTML = '<div class="medcomm-convo-empty"><i class="fas fa-user-doctor"></i><br>لا توجد نتائج مطابقة</div>';
      return;
    }

    list.forEach(function (d) {
      // معاينة آخر رسالة في القائمة: إذا كانت المحادثة قد فُتحت فعلاً وتم تحميل
      // رسائلها الحقيقية من قاعدة البيانات نعرض آخر رسالة منها، وإلا نعرض المعاينة
      // القادمة أصلاً من get_patient_conversations.php (last_message) فقط كنص عرض
      // في القائمة — دون استخدامها إطلاقاً كأساس لبناء محتوى المحادثة نفسها.
      var last = d.messages.length
        ? d.messages[d.messages.length - 1]
        : (d.previewText ? { text: d.previewText, time: d.previewTime } : null);
      var unread = MEDCOMM_UNREAD[d.id] || 0;

      var item = document.createElement("div");
      item.className = "medcomm-convo-item" + (d.id === medcommActiveId ? " medcomm-active" : "");
      item.setAttribute("data-doc", d.id);
      item.onclick = function () { medcommSelectDoctor(d.id); };

      item.innerHTML =
        '<div class="medcomm-avatar-wrap">' +
          '<div class="medcomm-avatar">' + d.initials + '</div>' +
          '<span class="medcomm-online-dot' + (d.online ? '' : ' medcomm-offline') + '"></span>' +
        '</div>' +
        '<div class="medcomm-convo-meta">' +
          '<div class="medcomm-doc-name">' + d.name + '</div>' +
          '<div class="medcomm-doc-spec">' + d.spec + '</div>' +
          '<div class="medcomm-last-msg">' + (last ? last.text : "") + '</div>' +
        '</div>' +
        '<div class="medcomm-convo-side">' +
          '<span class="medcomm-last-time">' + (last ? last.time : "") + '</span>' +
          (unread > 0 ? '<span class="medcomm-unread-badge">' + unread + '</span>' : '') +
        '</div>';

      wrap.appendChild(item);
    });
  }

  /* ── اختيار طبيب وفتح المحادثة ─────────────────────────── */
  async function medcommSelectDoctor(id) {

    medcommActiveId = id;
    MEDCOMM_UNREAD[id] = 0;

    // إظهار شريط الكتابة السفلي بمجرد تحديد طبيب (كان مخفياً في حالة الترحيب).
    // نعيد قيمة display الأصلية من CSS (flex) بترك السمة السطرية فارغة.
    var medcommInputBar = document.querySelector(".medcomm-input-row");
    if (medcommInputBar) {
        medcommInputBar.style.display = "";
    }

    medcommRenderList(
        medcommEl("medcommSearchInput")
        ? medcommEl("medcommSearchInput").value
        : ""
    );

    medcommRenderChatHeader(id);

    const requestId = ++medcommMessagesRequestId;

    const response = await fetch("get_medical_messages.php?doctor_id=" + id);
    const messages = await response.json();

    // تجاهل الرد إذا وصل طلب أحدث منه، أو إذا بدّل المريض المحادثة قبل وصول الرد
    if (requestId !== medcommMessagesRequestId || id !== medcommActiveId) {
        return;
    }

    const doctor = medcommGetDoctor(id);

    if (doctor) {
        doctor.messages = messages.map(medcommMapServerMessage);
    }

    medcommRenderMessages(id);

    // Read Receipts: بما أنّ المريض فتح هذه المحادثة الآن، نعلّم رسائل الطبيب
    // الموجَّهة إليه كمقروءة (فقط إن وُجدت رسائل غير مقروءة موجَّهة إليه).
    medcommMaybeMarkRead(id, messages);

}

/* يستدعي التعليم كمقروء فقط إذا كانت هناك رسالة غير مقروءة موجَّهة للمريض،
   لتفادي طلبات لا لزوم لها. me = patient_user_id في هذه المحادثة. */
function medcommMaybeMarkRead(id, rawMessages) {
    if (id !== medcommActiveId || !rawMessages || !rawMessages.length) return;
    var needs = false;
    for (var i = 0; i < rawMessages.length; i++) {
        var m = rawMessages[i];
        if (m.is_read == 0 && String(m.receiver_id) === String(m.patient_user_id)) { needs = true; break; }
    }
    if (!needs) return;
    fetch("mark_messages_read.php?doctor_id=" + id).catch(function () {});
}

/* تحويل الحقول القادمة من قاعدة البيانات (sender_role, message, created_at)
   إلى الشكل الذي تستخدمه الواجهة داخلياً (from, text, time)
   ملاحظة: الاتجاه (from) يُبنى فقط من sender_role القادم من قاعدة البيانات،
   وليس من هوية من فتح الصفحة — بذلك يبقى ثابتاً وصحيحاً دائماً بغض النظر
   عن الطرف الذي يعرض المحادثة. */
function medcommMapServerMessage(m) {
    return {
        id: m.id,
        pending: false,
        senderId: m.sender_id,
        // اتجاه الرسالة يُحدَّد من بيانات الصف نفسه، بترتيب موثوقية تنازلي:
        //  1) إن كان sender_id = doctor_id  => الطبيب هو المُرسِل (doc)
        //  2) إن كان sender_id = patient_user_id => المريض هو المُرسِل (patient)
        //  3) خطة بديلة: sender_role (مُطبَّعة: trim + حروف صغيرة)
        // هكذا يعمل الفرز سواء كان الحقل السليم في القاعدة هو sender_id أو
        // sender_role، وبمعزل تام عن هوية من فتح الصفحة (لا CURRENT_USER_ID).
        from: medcommResolveSide(m) ? "doc" : "patient",
        text: m.message,
        // حقول المرفق (تبقى null للرسائل النصية العادية فلا تؤثر عليها)
        attachmentPath: m.attachment_path || null,
        attachmentName: m.attachment_name || null,
        attachmentType: m.attachment_type || null,
        // حقول الرسالة الصوتية (تبقى null للرسائل الأخرى)
        voicePath: m.voice_path || null,
        voiceDuration: m.voice_duration || 0,
        isDeleted: (m.is_deleted == 1),
        isEdited: (m.is_edited == 1),
        isPinned: (m.is_pinned == 1),
        isRead: (m.is_read == 1),
        replyToId: (m.reply_to_message_id ? parseInt(m.reply_to_message_id, 10) : null),
        time: medcommFormatTime(m.created_at)
    };
}

/* يُعيد true إذا كانت الرسالة من الطبيب، false إذا كانت من المريض.
   يعتمد فقط على البيانات القادمة من قاعدة البيانات داخل نفس صف الرسالة. */
function medcommResolveSide(m) {
    var sid = (m.sender_id === null || m.sender_id === undefined) ? null : String(m.sender_id);
    var did = (m.doctor_id === null || m.doctor_id === undefined) ? null : String(m.doctor_id);
    var pid = (m.patient_user_id === null || m.patient_user_id === undefined) ? null : String(m.patient_user_id);

    if (sid !== null && did !== null && sid === did) return true;   // مُرسِلها الطبيب
    if (sid !== null && pid !== null && sid === pid) return false;  // مُرسِلها المريض

    var role = String(m.sender_role || "").trim().toLowerCase();
    return role === "doctor";
}

/* مفتاح ثابت لكل رسالة يُستخدم لمقارنة ما هو معروض حالياً بما تم جلبه حديثاً،
   حتى نضيف الرسائل الجديدة فقط دون إعادة بناء المحادثة بالكامل (منع الوميض) */
function medcommMsgKey(m) {
    return (m.id !== undefined && m.id !== null && !m.pending)
        ? ("id:" + m.id + (m.isDeleted ? ":d" : "") + (m.isEdited ? ":e" : "") + (m.isPinned ? ":p" : "") + (m.isRead ? ":r" : ""))
        : ("pending:" + m.from + "|" + m.text);
}

function medcommFormatTime(dateStr) {
    if (!dateStr) return "";
    var d = new Date(dateStr.replace(" ", "T"));
    if (isNaN(d.getTime())) return "";
    var h = d.getHours(), m = d.getMinutes();
    return (h < 10 ? "0" + h : h) + ":" + (m < 10 ? "0" + m : m);
}

window.medcommSelectDoctor = medcommSelectDoctor;
  function medcommGetDoctor(id) {
    for (var i = 0; i < MEDCOMM_DOCTORS.length; i++) {
      if (MEDCOMM_DOCTORS[i].id === id) return MEDCOMM_DOCTORS[i];
    }
    return null;
  }

  /* ── رأس نافذة المحادثة ────────────────────────────────── */
  function medcommRenderChatHeader(id) {
    var d = medcommGetDoctor(id);
    var headerWrap = medcommEl("medcommChatHeaderWrap");
    if (!headerWrap || !d) return;

    headerWrap.innerHTML =
      '<div class="medcomm-avatar-wrap">' +
        '<div class="medcomm-avatar">' + d.initials + '</div>' +
        '<span class="medcomm-online-dot' + (d.online ? '' : ' medcomm-offline') + '"></span>' +
      '</div>' +
      '<div class="medcomm-chat-header-meta">' +
        '<div class="medcomm-chat-header-name">' + d.name + '</div>' +
        '<div class="medcomm-chat-header-status' + (d.online ? '' : ' medcomm-offline') + '">' +
          '<span class="medcomm-dot-inline"></span>' + (d.online ? 'متصل الآن' : 'غير متصل') +
          ' • ' + d.spec +
        '</div>' +
      '</div>';
  }

  window.medcommDoctorInfo = function (id) {
    var d = medcommGetDoctor(id);
    if (!d) return;
    if (typeof sa === "function") {
      sa(d.name + " — " + d.spec + (d.online ? " (متصل الآن)" : " (غير متصل حالياً)"));
    } else {
      alert(d.name + "\n" + d.spec + "\n" + (d.online ? "متصل الآن" : "غير متصل حالياً"));
    }
  };

  /* ── عرض الرسائل (بدون Flicker) ────────────────────────────
     بدل مسح المحادثة كاملة (box.innerHTML = "") وإعادة بنائها في كل تحديث،
     نقارن الرسائل الجديدة بما هو معروض حالياً:
     - إذا لم يتغيّر شيء: لا نلمس الـ DOM إطلاقاً.
     - إذا كانت الإضافة الوحيدة رسائل جديدة في النهاية: نضيفها فقط.
     - نزيل فقط الرسائل "غير المؤكدة" (المرسلة محلياً بانتظار تأكيد
       قاعدة البيانات) من نهاية العرض عند استبدالها بالنسخة الحقيقية،
       دون المساس بأي رسالة سابقة في تاريخ المحادثة.
     - نحافظ على موضع التمرير: لا نُنزّل الشاشة تلقائياً إلا إذا كان
       المستخدم أصلاً قرب آخر المحادثة، أو عند فتح محادثة جديدة تماماً. */
  function medcommRenderMessages(id) {
    var d = medcommGetDoctor(id);
    var box = medcommEl("medcommMessages");
    if (!box || !d) return;

    var newKeys = d.messages.map(medcommMsgKey);
    var sameConversation = (id === medcommLastRenderedId);

    var confirmedPrefixLen = sameConversation
      ? Math.max(0, medcommLastRenderedKeys.length - medcommLastRenderedPendingCount)
      : 0;

    var confirmedPrefixMatches = sameConversation;
    if (confirmedPrefixMatches) {
      for (var i = 0; i < confirmedPrefixLen; i++) {
        if (newKeys[i] !== medcommLastRenderedKeys[i]) { confirmedPrefixMatches = false; break; }
      }
    }

    var newPendingCount = 0;
    for (var k = d.messages.length - 1; k >= 0 && d.messages[k].pending; k--) newPendingCount++;

    var wasNearBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 60;
    var changed = false;

    if (confirmedPrefixMatches) {
      // إزالة الرسائل غير المؤكدة السابقة فقط (إن وُجدت) من نهاية العرض
      for (var r = 0; r < medcommLastRenderedPendingCount; r++) {
        if (box.lastElementChild) { box.removeChild(box.lastElementChild); changed = true; }
      }
      // إضافة أي رسالة جديدة بعد الجزء المؤكد فقط، دون لمس ما قبلها
      for (var j = confirmedPrefixLen; j < d.messages.length; j++) {
        box.appendChild(medcommBuildMsgRow(d.messages[j], d));
        changed = true;
      }
    } else {
      // تغيّر حقيقي في المحادثة (فتح محادثة أخرى) — إعادة بناء كاملة مرة واحدة فقط
      box.innerHTML = "";
      d.messages.forEach(function (m) {
        box.appendChild(medcommBuildMsgRow(m, d));
      });
      changed = true;
    }

    medcommLastRenderedId = id;
    medcommLastRenderedKeys = newKeys;
    medcommLastRenderedPendingCount = newPendingCount;

    if (changed && (wasNearBottom || !sameConversation)) {
      medcommScrollBottom();
    }

    medcommUpdatePinnedBar(id);
  }

  /* شريط الرسالة المثبتة أعلى المحادثة (المريض يراه فقط، بلا أزرار تحكّم).
     يُحدَّث من d.messages في كل تحديث، فيظهر/يختفي فوراً مع الـ Polling.
     يتجاهل الرسالة المحذوفة (فيختفي الشريط تلقائياً عند حذف المثبتة). */
  function medcommUpdatePinnedBar(id) {
    var d = medcommGetDoctor(id);
    var box = medcommEl("medcommMessages");
    if (!box) return;

    var pinned = null;
    if (d && d.messages) {
      for (var i = 0; i < d.messages.length; i++) {
        if (d.messages[i].isPinned && !d.messages[i].isDeleted) { pinned = d.messages[i]; break; }
      }
    }

    var bar = document.getElementById("medcommPinnedBar");
    if (!pinned) { if (bar) bar.remove(); return; }

    if (!bar) {
      bar = document.createElement("div");
      bar.id = "medcommPinnedBar";
      bar.style.cssText = "display:flex;align-items:center;gap:8px;padding:8px 14px;margin:0;" +
        "background:rgba(14,165,233,.08);border-bottom:1px solid var(--pt-border-card,#e2e8f0);" +
        "cursor:pointer;font-family:inherit;flex-shrink:0;";
      box.parentNode.insertBefore(bar, box);
    }

    var preview = pinned.text ? pinned.text
      : (pinned.voicePath ? 'رسالة صوتية' : (pinned.attachmentName || 'مرفق'));

    bar.innerHTML =
      '<i class="fas fa-thumbtack" style="color:var(--pt-primary,#0ea5e9);font-size:.8rem;flex-shrink:0;"></i>' +
      '<div style="flex:1;min-width:0;">' +
        '<div style="font-size:.68rem;font-weight:800;color:var(--pt-primary,#0ea5e9);">رسالة مثبتة</div>' +
        '<div style="font-size:.76rem;color:var(--pt-text-secondary,#475569);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
          medcommEscape(preview) + '</div>' +
      '</div>';
    bar.onclick = function () { medcommScrollToMsg(pinned.id); };
  }

  function medcommScrollToMsg(id) {
    var el = document.querySelector('.medcomm-bubble[data-mid="' + id + '"]');
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    var prev = el.style.boxShadow;
    el.style.transition = 'box-shadow .3s';
    el.style.boxShadow = '0 0 0 3px rgba(14,165,233,.55)';
    setTimeout(function () { el.style.boxShadow = prev; }, 1200);
  }

  /* اتجاه ولون الرسالة يُبنيان فقط من صاحب الرسالة الحقيقي المستنتَج داخل
     medcommMapServerMessage/medcommResolveSide من بيانات الصف (sender_id مقابل
     doctor_id/patient_user_id، ثم sender_role) — لا من CURRENT_USER_ID ولا من
     الترتيب ولا من المحادثة النشطة. في واجهة المريض: رسالة المريض
     (from === "patient") دائماً يمين ولون المريض، ورسالة الطبيب (from === "doc")
     دائماً يسار ولون الطبيب. الرسالة قيد التأكيد (pending) تحمل from:"patient".
     الغلاف direction:ltr لضبط الجهة الفيزيائية، ثم rtl داخل الفقاعة للنص العربي. */
  function medcommBuildMsgRow(m, d) {
    var isDoc = m.from === "doc"; // صاحب الرسالة: يحدّد الأفتار والجهة واللون معاً

    // في واجهة المريض تكون الرسالة "لي" (يمين، لون المريض) عندما تكون من المريض.
    var isMine = !isDoc;

    var wrapper = document.createElement("div");
    wrapper.style.display = "flex";
    wrapper.style.width = "100%";
    wrapper.style.direction = "ltr";
    wrapper.style.justifyContent = isMine ? "flex-end" : "flex-start";

    var bubbleColor = isMine
      ? "background-color:var(--primary, #2f6fed); color:#ffffff;"
      : "background-color:#ffffff; color:#111111;";

    // ملاحظة: لا نستخدم class باسم "medcomm-from-doc"/"medcomm-from-patient"
    // لتحديد اللون/الاتجاه، لأن هذه الأصناف مرتبطة بـ sender_role/from داخل
    // patient_medcomm.css (align-self, flex-direction: row-reverse). نستخدم
    // class ثابت "medcomm-msg-row" فقط، ونضبط flexDirection هنا بالاعتماد
    // فقط على isMine حتى يبقى ثابتاً بعد أي Polling أو Refresh.
    var row = document.createElement("div");
    row.className = "medcomm-msg-row";
    row.style.direction = "rtl";
    row.style.flexDirection = isMine ? "row-reverse" : "row";

    var avatarText = isDoc ? d.initials : (medcommPatientInitials());

    var bubbleInner;
    if (m.isDeleted) {
      bubbleInner = '<span style="opacity:.75;font-style:italic;"><i class="fas fa-ban" style="font-size:.72rem;margin-left:5px;"></i>تم حذف هذه الرسالة</span>';
    } else {
      var medcommAttachHtml = medcommAttachmentHtml(m);
      var medcommVoiceHtmlStr = medcommVoiceHtml(m);
      var medcommTextHtml = m.text ? medcommEscape(m.text) : '';
      bubbleInner = medcommReplyQuoteHtml(m, d) + medcommVoiceHtmlStr + medcommAttachHtml + medcommTextHtml;
    }

    // رسالة قابلة للتعديل = نصية فقط (بلا مرفق/صوت) وغير محذوفة وملك المريض
    var medcommEditable = (!m.isDeleted && isMine && m.id !== undefined && m.id !== null &&
                           !m.attachmentPath && !m.voicePath && !!m.text);
    var medcommMidAttr = (m.id !== undefined && m.id !== null) ? (' data-mid="' + m.id + '"') : '';
    var medcommTextAttr = medcommEditable ? (' data-text="' + medcommAttr(m.text) + '"') : '';

    row.innerHTML =
      '<div class="medcomm-msg-avatar">' + avatarText + '</div>' +
      '<div class="medcomm-bubble-col">' +
        '<div class="medcomm-bubble" style="' + bubbleColor + '"' + medcommMidAttr + medcommTextAttr + '>' + bubbleInner + '</div>' +
        '<div class="medcomm-msg-time">' + m.time + (m.isEdited ? ' · تم التعديل' : '') +
          (isMine ? (m.isRead ? ' <span style="color:#38bdf8;">✓✓</span>' : ' <span style="opacity:.7;">✓✓</span>') : '') +
        '</div>' +
      '</div>';

    // زر الخيارات (⋮): يظهر على كل الرسائل غير المحذوفة (للرد على أي رسالة).
    // خيارات التعديل/الحذف تبقى لرسائل المريض نفسه فقط (داخل القائمة).
    if (!m.isDeleted && m.id !== undefined && m.id !== null) {
      var col = row.querySelector('.medcomm-bubble-col');
      if (col) {
        col.style.position = 'relative';
        var delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.innerHTML = '<i class="fas fa-ellipsis-v"></i>';
        delBtn.style.cssText = 'position:absolute;top:0;left:-4px;border:none;background:transparent;' +
          'color:' + (isMine ? '#ffffff' : '#94a3b8') + ';cursor:pointer;opacity:0;transition:opacity .15s;padding:2px 6px;font-size:.78rem;';
        delBtn.setAttribute('onclick', "medcommMsgMenu(this, '" + m.id + "', " + (isMine ? '1' : '0') + ", " + (medcommEditable ? '1' : '0') + ")");
        col.appendChild(delBtn);
        wrapper.addEventListener('mouseenter', function () { delBtn.style.opacity = '1'; });
        wrapper.addEventListener('mouseleave', function () { delBtn.style.opacity = '0'; });
      }
    }

    wrapper.appendChild(row);
    return wrapper;
  }

  function medcommPatientInitials() {
    var av = document.getElementById("TAV");
    return av && av.textContent ? av.textContent.trim().slice(0, 2) : "م";
  }

  function medcommEscape(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  function medcommScrollBottom() {
    var box = medcommEl("medcommMessages");
    if (box) box.scrollTop = box.scrollHeight;
  }

  function medcommNowTime() {
    var now = new Date();
    var h = now.getHours(), m = now.getMinutes();
    return (h < 10 ? "0" + h : h) + ":" + (m < 10 ? "0" + m : m);
  }

  /* ── إرسال رسالة (Front-End فقط، بدون أي اتصال شبكي) ─────── */
  var MEDCOMM_AUTO_REPLIES = [
    "تم استلام رسالتك، سأراجعها وأرد عليك قريباً.",
    "شكراً على المعلومة، هل هناك أعراض أخرى تودّ ذكرها؟",
    "حسناً، حافظ على الجرعة الحالية وأخبرني بأي تغيير.",
    "جيد، يبدو الوضع مستقراً. تابع معي في الموعد القادم.",
    "تمام، سجّلت ملاحظتك في ملفك الطبي."
  ];

  async function medcommSend() {
    if (!medcommActiveId) return;
    var input = medcommEl("medcommInput");
    if (!input) return;
    var text = input.value.trim();
    if (!text) return;

    var d = medcommGetDoctor(medcommActiveId);
    if (!d) return;
    if (!d.recordId) return;

    // pending:true تعني رسالة أُرسلت محلياً ولم يصل بعد تأكيد قاعدة البيانات؛
    // تُعرض فوراً عبر medcommRenderMessages (إضافة فقط، دون مسح المحادثة) ثم
    // تُستبدل بنسخة قاعدة البيانات الحقيقية بعد الحفظ دون أي وميض للمحادثة كاملة.
    var msg = { id: null, pending: true, from: "patient", text: text, time: medcommNowTime() };
    d.messages.push(msg);
    medcommRenderMessages(medcommActiveId);
    medcommScrollBottom();
    input.value = "";
    medcommRenderList(medcommEl("medcommSearchInput") ? medcommEl("medcommSearchInput").value : "");

    var formData = new FormData();
    formData.append("record_id", d.recordId);
    formData.append("message", text);
    if (medcommReplyTo && medcommReplyTo.id) {
      formData.append("reply_to", medcommReplyTo.id);
    }
    if (window.medcommCancelReply) window.medcommCancelReply();

    try {
      const response = await fetch("send_medical_message.php", {
        method: "POST",
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        // إعادة تحميل الرسائل فوراً من قاعدة البيانات بعد نجاح الحفظ
        await medcommRefreshActiveMessages();
      } else {
        console.error("فشل إرسال الرسالة:", result.message);
      }
    } catch (err) {
      console.error("فشل إرسال الرسالة:", err);
    }
  }
  window.medcommSend = medcommSend;

  /* ════════════════════════════════════════════════════════════
     حذف الرسالة (Soft Delete) — قائمة ⋮ ← "حذف للجميع" ← تأكيد.
     تظهر فقط على رسائل المريض نفسه (تُبنى في medcommBuildMsgRow).
  ════════════════════════════════════════════════════════════ */
  function medcommCloseMenus() {
    var mm = document.querySelectorAll('.medcomm-msg-menu');
    mm.forEach(function (x) { x.remove(); });
  }

  window.medcommMsgMenu = function (btn, id, isMine, editable) {
    medcommCloseMenus();
    var menu = document.createElement('div');
    menu.className = 'medcomm-msg-menu';
    menu.style.cssText = 'position:fixed;z-index:100000;background:#fff;border:1px solid #e2e8f0;' +
      'border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);padding:4px;font-family:inherit;direction:rtl;';

    var itemCss = 'display:flex;align-items:center;border:none;background:transparent;cursor:pointer;' +
      'padding:8px 14px;font-family:inherit;font-size:.82rem;color:#0f172a;white-space:nowrap;border-radius:6px;width:100%;';

    var addItem = function (html, onclick) {
      var it = document.createElement('button');
      it.type = 'button';
      it.innerHTML = html;
      it.style.cssText = itemCss;
      it.onmouseenter = function () { it.style.background = '#f1f5f9'; };
      it.onmouseleave = function () { it.style.background = 'transparent'; };
      it.onclick = function () { medcommCloseMenus(); onclick(); };
      menu.appendChild(it);
    };

    // الرد على أي رسالة
    addItem('<i class="fas fa-reply" style="margin-left:6px;color:#0ea5e9;"></i> الرد على الرسالة', function () { medcommStartReply(id); });

    // تعديل (نصية + ملك المريض)
    if (editable && isMine) {
      addItem('<i class="fas fa-pen" style="margin-left:6px;color:#0ea5e9;"></i> تعديل', function () { medcommStartEdit(id); });
    }

    // حذف (ملك المريض)
    if (isMine) {
      addItem('<i class="fas fa-trash" style="margin-left:6px;color:#ef4444;"></i> حذف للجميع', function () { medcommConfirmDelete(id); });
    }

    document.body.appendChild(menu);
    var r = btn.getBoundingClientRect();
    menu.style.top = (r.bottom + 4) + 'px';
    menu.style.left = Math.max(8, r.left - 40) + 'px';
    setTimeout(function () { document.addEventListener('click', medcommCloseMenus, { once: true }); }, 0);
  };

  /* ── اقتباس الرد (Reply) ─────────────────────────────────────
     يبني معاينة الرسالة الأصلية داخل الفقاعة، بالبحث عنها في رسائل
     المحادثة الحالية. يعمل مع كل الأنواع، ويعرض "لم تعد متوفرة" إن
     كانت الأصلية محذوفة أو غير موجودة. */
  function medcommFindMsgById(d, id) {
    if (!d || !d.messages) return null;
    for (var i = 0; i < d.messages.length; i++) {
      if (String(d.messages[i].id) === String(id)) return d.messages[i];
    }
    return null;
  }

  function medcommMsgPreview(msg) {
    if (!msg) return null;
    if (msg.isDeleted) return null;
    if (msg.voicePath) return '🎤 رسالة صوتية';
    if (msg.attachmentPath) {
      var name = msg.attachmentName || '';
      var ext = (String(name).split('.').pop() || '').toLowerCase();
      var isImg = /^(jpg|jpeg|png|gif|webp)$/.test(ext) || (String(msg.attachmentType || '').indexOf('image/') === 0);
      return isImg ? '🖼️ صورة' : '📄 ملف';
    }
    var t = msg.text ? String(msg.text) : '';
    return t.length > 80 ? (t.slice(0, 80) + '…') : t;
  }

  function medcommReplyAuthor(d, original) {
    // على شاشة المريض: رسالة الطبيب => اسم الطبيب، رسالة المريض => "أنت"
    return (original.from === 'doc') ? (d ? d.name : 'الطبيب') : 'أنت';
  }

  function medcommReplyQuoteHtml(m, d) {
    if (!m.replyToId) return '';
    var original = medcommFindMsgById(d, m.replyToId);
    var preview = medcommMsgPreview(original);
    var inner;
    if (!original || preview === null) {
      inner = '<div style="font-size:.72rem;color:#94a3b8;">🚫 هذه الرسالة لم تعد متوفرة</div>';
    } else {
      inner =
        '<div style="font-size:.68rem;font-weight:800;color:#0ea5e9;">' +
          '<i class="fas fa-reply" style="font-size:.6rem;margin-left:3px;"></i>' + medcommEscape(medcommReplyAuthor(d, original)) + '</div>' +
        '<div style="font-size:.72rem;color:inherit;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;">' +
          medcommEscape(preview) + '</div>';
    }
    return '<div class="medcomm-reply-quote" onclick="medcommScrollToMsg(\'' + m.replyToId + '\')" ' +
      'style="cursor:pointer;border-right:3px solid rgba(14,165,233,.7);padding:4px 8px;margin-bottom:5px;' +
      'background:rgba(0,0,0,.06);border-radius:6px;">' + inner + '</div>';
  }

  /* ── حالة "الرد على" فوق مربع الكتابة ─────────────────────── */
  var medcommReplyTo = null;

  window.medcommStartReply = function (id) {
    var d = medcommGetDoctor(medcommActiveId);
    var original = medcommFindMsgById(d, id);
    if (!original) return;
    var preview = medcommMsgPreview(original);
    medcommReplyTo = { id: id };
    medcommShowReplyBar(medcommReplyAuthor(d, original), preview === null ? 'رسالة' : preview);
    var input = medcommEl('medcommInput');
    if (input) input.focus();
  };

  window.medcommCancelReply = function () {
    medcommReplyTo = null;
    var bar = document.getElementById('medcommReplyBar');
    if (bar) bar.remove();
  };

  function medcommShowReplyBar(author, preview) {
    var row = document.querySelector('.medcomm-input-row');
    if (!row) return;
    var bar = document.getElementById('medcommReplyBar');
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'medcommReplyBar';
      bar.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 14px;' +
        'background:rgba(14,165,233,.08);border-top:1px solid var(--pt-border-card,#e2e8f0);font-family:inherit;';
      row.parentNode.insertBefore(bar, row);
    }
    bar.innerHTML =
      '<i class="fas fa-reply" style="color:#0ea5e9;flex-shrink:0;"></i>' +
      '<div style="flex:1;min-width:0;">' +
        '<div style="font-size:.7rem;font-weight:800;color:#0ea5e9;">الرد على ' + medcommEscape(author) + '</div>' +
        '<div style="font-size:.74rem;color:var(--pt-text-secondary,#475569);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + medcommEscape(preview) + '</div>' +
      '</div>' +
      '<button type="button" onclick="medcommCancelReply()" style="border:none;background:transparent;color:#94a3b8;font-size:1.1rem;cursor:pointer;flex-shrink:0;">&times;</button>';
  }

  /* تحرير الرسالة النصية داخل نفس الفقاعة (حفظ/إلغاء) */
  window.medcommStartEdit = function (id) {
    var bubble = document.querySelector('.medcomm-bubble[data-mid="' + id + '"]');
    if (!bubble || bubble.__editing) return;
    bubble.__editing = true;

    var original = bubble.getAttribute('data-text');
    if (original === null) original = bubble.textContent;
    var savedHtml = bubble.innerHTML;

    bubble.innerHTML = '';
    var ta = document.createElement('textarea');
    ta.value = original;
    ta.style.cssText = 'width:100%;min-width:180px;box-sizing:border-box;border:none;border-radius:8px;' +
      'padding:6px 8px;font-family:inherit;font-size:.82rem;resize:vertical;min-height:38px;color:#111;';
    var bar = document.createElement('div');
    bar.style.cssText = 'display:flex;gap:6px;justify-content:flex-end;margin-top:6px;';
    var cancel = document.createElement('button');
    cancel.type = 'button'; cancel.textContent = 'إلغاء';
    cancel.style.cssText = 'border:none;background:rgba(255,255,255,.85);color:#334155;border-radius:6px;padding:5px 12px;cursor:pointer;font-family:inherit;font-size:.75rem;';
    var save = document.createElement('button');
    save.type = 'button'; save.textContent = 'حفظ';
    save.style.cssText = 'border:none;background:#0ea5e9;color:#fff;border-radius:6px;padding:5px 14px;cursor:pointer;font-family:inherit;font-size:.75rem;';

    var restore = function () { bubble.__editing = false; bubble.innerHTML = savedHtml; };
    cancel.onclick = restore;
    save.onclick = function () { medcommDoEdit(id, ta.value, restore); };

    bar.appendChild(cancel); bar.appendChild(save);
    bubble.appendChild(ta); bubble.appendChild(bar);
    ta.focus();
  };

  async function medcommDoEdit(id, text, restore) {
    text = (text || '').trim();
    if (text === '') { restore(); return; }
    try {
      var fd = new FormData();
      fd.append('message_id', id);
      fd.append('message', text);
      var resp = await fetch('edit_medical_message.php', { method: 'POST', body: fd });
      var res = await resp.json();
      if (res.success) { await medcommRefreshActiveMessages(); }
      else { alert(res.message || 'تعذّر تعديل الرسالة'); restore(); }
    } catch (e) { alert('تعذّر تعديل الرسالة'); restore(); }
  }

  function medcommConfirmDelete(id) {
    var ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:100001;' +
      'display:flex;align-items:center;justify-content:center;';
    var box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:14px;padding:22px;width:min(340px,90vw);' +
      'text-align:center;font-family:inherit;box-shadow:0 20px 60px rgba(0,0,0,.3);direction:rtl;';
    box.innerHTML = '<div style="font-size:.95rem;color:#0f172a;margin-bottom:18px;">هل تريد حذف هذه الرسالة؟</div>';
    var actions = document.createElement('div');
    actions.style.cssText = 'display:flex;gap:10px;justify-content:center;';
    var cancel = document.createElement('button');
    cancel.textContent = 'إلغاء';
    cancel.style.cssText = 'flex:1;padding:9px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;cursor:pointer;font-family:inherit;';
    cancel.onclick = function () { ov.remove(); };
    var del = document.createElement('button');
    del.textContent = 'حذف';
    del.style.cssText = 'flex:1;padding:9px;border:none;background:#ef4444;color:#fff;border-radius:8px;cursor:pointer;font-family:inherit;';
    del.onclick = function () { ov.remove(); medcommDoDelete(id); };
    actions.appendChild(cancel); actions.appendChild(del);
    box.appendChild(actions); ov.appendChild(box); document.body.appendChild(ov);
    ov.addEventListener('click', function (e) { if (e.target === ov) ov.remove(); });
  }

  async function medcommDoDelete(id) {
    try {
      var fd = new FormData();
      fd.append('message_id', id);
      var resp = await fetch('delete_medical_message.php', { method: 'POST', body: fd });
      var res = await resp.json();
      if (res.success) { await medcommRefreshActiveMessages(); }
      else alert(res.message || 'تعذّر حذف الرسالة');
    } catch (e) { alert('تعذّر حذف الرسالة'); }
  }

  /* ════════════════════════════════════════════════════════════
     دعم المرفقات (صور / PDF / Office / نصوص / مضغوطة / أي ملف)
  ════════════════════════════════════════════════════════════ */
  function medcommAttr(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
      .replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function medcommFileIcon(ext) {
    if (ext === 'pdf') return { cls: 'fa-file-pdf', color: '#e11d48' };
    if (ext === 'doc' || ext === 'docx') return { cls: 'fa-file-word', color: '#2563eb' };
    if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return { cls: 'fa-file-excel', color: '#16a34a' };
    if (ext === 'ppt' || ext === 'pptx') return { cls: 'fa-file-powerpoint', color: '#ea580c' };
    if (ext === 'zip' || ext === 'rar' || ext === '7z') return { cls: 'fa-file-archive', color: '#a855f7' };
    if (ext === 'txt') return { cls: 'fa-file-lines', color: '#64748b' };
    return { cls: 'fa-file', color: '#64748b' };
  }

  function medcommAttachmentHtml(m) {
    if (!m || !m.attachmentPath) return '';
    var url = m.attachmentPath;
    var name = m.attachmentName || 'ملف';
    var ext = (String(name).split('.').pop() || '').toLowerCase();
    var isImg = /^(jpg|jpeg|png|gif|webp)$/.test(ext) ||
                (String(m.attachmentType || '').indexOf('image/') === 0);

    if (isImg) {
      return '<img src="' + medcommAttr(url) + '" alt="' + medcommAttr(name) + '" ' +
             'style="max-width:220px;max-height:220px;border-radius:10px;display:block;' +
             'cursor:zoom-in;margin-bottom:4px;" ' +
             'onclick="medcommOpenLightbox(\'' + medcommAttr(url) + '\')">';
    }

    var ic = medcommFileIcon(ext);
    return '<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;' +
             'background:rgba(0,0,0,.08);border-radius:10px;margin-bottom:4px;min-width:190px;">' +
             '<i class="fas ' + ic.cls + '" style="font-size:1.7rem;color:' + ic.color + ';"></i>' +
             '<div style="flex:1;min-width:0;overflow:hidden;">' +
               '<div style="font-size:.78rem;font-weight:700;white-space:nowrap;overflow:hidden;' +
                 'text-overflow:ellipsis;">' + medcommEscape(name) + '</div>' +
               '<a href="' + medcommAttr(url) + '" download="' + medcommAttr(name) + '" ' +
                 'style="font-size:.72rem;color:inherit;text-decoration:underline;">' +
                 'تحميل <i class="fas fa-download"></i></a>' +
             '</div>' +
           '</div>';
  }

  window.medcommOpenLightbox = function (url) {
    var ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.82);display:flex;' +
      'align-items:center;justify-content:center;z-index:99999;cursor:zoom-out;';
    var img = document.createElement('img');
    img.src = url;
    img.style.cssText = 'max-width:92%;max-height:92%;border-radius:8px;' +
      'box-shadow:0 10px 40px rgba(0,0,0,.5);';
    ov.appendChild(img);
    ov.addEventListener('click', function () { ov.remove(); });
    document.body.appendChild(ov);
  };

  var medcommFileInput = null;
  function medcommEnsureFileInput() {
    if (medcommFileInput) return medcommFileInput;
    medcommFileInput = document.createElement('input');
    medcommFileInput.type = 'file';
    medcommFileInput.style.display = 'none';
    medcommFileInput.addEventListener('change', function () {
      if (medcommFileInput.files && medcommFileInput.files[0]) {
        medcommSendFile(medcommFileInput.files[0]);
        medcommFileInput.value = '';
      }
    });
    document.body.appendChild(medcommFileInput);
    return medcommFileInput;
  }

  var medcommUploading = false;
  function medcommShowUploading() {
    var box = medcommEl("medcommMessages");
    if (!box) return;
    var t = document.createElement("div");
    t.id = "medcommUploadingRow";
    t.style.cssText = 'align-self:center;font-size:.76rem;color:var(--pt-text-muted,#94a3b8);' +
      'padding:6px 12px;display:flex;align-items:center;gap:8px;';
    t.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ رفع الملف...';
    box.appendChild(t);
    medcommScrollBottom();
  }
  function medcommHideUploading() {
    var t = medcommEl("medcommUploadingRow");
    if (t) t.remove();
  }

  async function medcommSendFile(file) {
    if (!medcommActiveId) return;
    if (medcommUploading) return;
    var d = medcommGetDoctor(medcommActiveId);
    if (!d || !d.recordId) return;

    medcommUploading = true;
    medcommShowUploading();

    try {
      var fd = new FormData();
      fd.append("record_id", d.recordId);
      fd.append("message", "");
      fd.append("file", file);

      var resp = await fetch("upload_chat_file.php", { method: "POST", body: fd });
      var res = await resp.json();

      if (res.success) {
        await medcommRefreshActiveMessages();
      } else {
        alert(res.message || "فشل رفع الملف");
      }
    } catch (err) {
      console.error("فشل رفع الملف:", err);
      alert("فشل رفع الملف");
    } finally {
      medcommUploading = false;
      medcommHideUploading();
    }
  }

  function medcommWireAttachButton() {
    var btn = document.querySelector('.medcomm-input-row button[title="إرفاق ملف"]');
    if (btn && !btn.__medcommWired) {
      btn.__medcommWired = true;
      btn.addEventListener("click", function () {
        if (!medcommActiveId) return;
        if (medcommUploading) return;
        medcommEnsureFileInput().click();
      });
    }
  }

  /* ════════════════════════════════════════════════════════════
     الرسائل الصوتية (تسجيل عبر MediaRecorder + رفع + مشغّل) —
     طبقة مستقلة لا تمسّ النص ولا المرفقات ولا المحاذاة/الألوان.
  ════════════════════════════════════════════════════════════ */
  function medVoiceFmt(s) {
    s = Math.floor(s || 0);
    var m = Math.floor(s / 60), ss = s % 60;
    return m + ':' + (ss < 10 ? '0' : '') + ss;
  }

  // مشغّل صوت (زر تشغيل/إيقاف + شريط تقدم + المدة + إعادة تشغيل)
  function medcommVoiceHtml(m) {
    if (!m || !m.voicePath) return '';
    return '<div class="med-voice" style="display:flex;align-items:center;gap:10px;min-width:200px;padding:2px 0;">' +
      '<button type="button" onclick="medVoiceToggle(this)" ' +
        'style="width:34px;height:34px;border-radius:50%;border:none;cursor:pointer;flex-shrink:0;' +
        'background:rgba(0,0,0,.14);display:flex;align-items:center;justify-content:center;color:inherit;">' +
        '<i class="fas fa-play"></i></button>' +
      '<input type="range" class="med-voice-seek" value="0" min="0" max="100" ' +
        'style="flex:1;height:4px;cursor:pointer;accent-color:currentColor;">' +
      '<span class="med-voice-time" style="font-size:.7rem;min-width:34px;text-align:center;">' +
        medVoiceFmt(m.voiceDuration) + '</span>' +
      '<audio class="med-voice-audio" src="' + medcommAttr(m.voicePath) + '" preload="metadata"></audio>' +
      '</div>';
  }

  // تشغيل/إيقاف — تُستدعى من زر المشغّل داخل الفقاعة
  window.medVoiceToggle = function (btn) {
    var wrap = btn.parentNode;
    var audio = wrap.querySelector('.med-voice-audio');
    var seek = wrap.querySelector('.med-voice-seek');
    var timeEl = wrap.querySelector('.med-voice-time');
    var icon = btn.querySelector('i');
    if (!audio) return;

    if (!audio.__wired) {
      audio.__wired = true;
      audio.addEventListener('timeupdate', function () {
        if (audio.duration && isFinite(audio.duration)) {
          seek.value = (audio.currentTime / audio.duration) * 100;
          timeEl.textContent = medVoiceFmt(audio.currentTime);
        }
      });
      audio.addEventListener('ended', function () {
        icon.className = 'fas fa-play';
        seek.value = 0;
        timeEl.textContent = medVoiceFmt(audio.duration || 0);
      });
      seek.addEventListener('input', function () {
        if (audio.duration && isFinite(audio.duration)) audio.currentTime = (seek.value / 100) * audio.duration;
      });
    }

    if (audio.paused) { audio.play(); icon.className = 'fas fa-pause'; }
    else { audio.pause(); icon.className = 'fas fa-play'; }
  };

  // حالة التسجيل
  var medVoiceRec = { rec: null, chunks: [], stream: null, start: 0, timer: null, recording: false, busy: false };

  async function medcommMicToggle() {
    if (!medcommActiveId) return;
    if (medVoiceRec.recording) { medcommStopRecording(); return; }
    if (medVoiceRec.busy) return;
    try {
      var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      var rec = new MediaRecorder(stream);
      medVoiceRec.chunks = [];
      rec.ondataavailable = function (e) { if (e.data && e.data.size) medVoiceRec.chunks.push(e.data); };
      rec.onstop = function () { medcommUploadVoice(); };
      rec.start();
      medVoiceRec.rec = rec; medVoiceRec.stream = stream;
      medVoiceRec.recording = true; medVoiceRec.start = Date.now();
      medcommShowRecordingUI();
    } catch (e) {
      alert('تعذّر الوصول إلى الميكروفون');
    }
  }

  function medcommStopRecording() {
    if (medVoiceRec.rec && medVoiceRec.recording) {
      medVoiceRec.recording = false;
      try { medVoiceRec.rec.stop(); } catch (e) {}
      if (medVoiceRec.stream) medVoiceRec.stream.getTracks().forEach(function (t) { t.stop(); });
      medcommHideRecordingUI();
    }
  }

  async function medcommUploadVoice() {
    var duration = Math.round((Date.now() - medVoiceRec.start) / 1000);
    var d = medcommGetDoctor(medcommActiveId);
    if (!d || !d.recordId) return;
    var type = (medVoiceRec.rec && medVoiceRec.rec.mimeType) || 'audio/webm';
    var blob = new Blob(medVoiceRec.chunks, { type: type });

    medVoiceRec.busy = true;
    medcommShowUploading();
    try {
      var fd = new FormData();
      fd.append('record_id', d.recordId);
      fd.append('duration', duration);
      fd.append('voice', blob, 'voice.webm');
      var resp = await fetch('upload_voice.php', { method: 'POST', body: fd });
      var res = await resp.json();
      if (res.success) await medcommRefreshActiveMessages();
      else alert(res.message || 'فشل إرسال التسجيل');
    } catch (e) {
      console.error('فشل إرسال التسجيل:', e);
    } finally {
      medVoiceRec.busy = false;
      medcommHideUploading();
    }
  }

  function medcommShowRecordingUI() {
    var btn = document.getElementById('medcommMicBtn');
    if (btn) { var ic = btn.querySelector('i'); if (ic) ic.className = 'fas fa-stop'; btn.style.color = '#ef4444'; }
    var box = medcommEl('medcommMessages');
    if (box) {
      var t = document.createElement('div');
      t.id = 'medcommRecRow';
      t.style.cssText = 'align-self:center;font-size:.76rem;color:#ef4444;padding:6px 12px;display:flex;align-items:center;gap:8px;';
      t.innerHTML = '<i class="fas fa-circle" style="font-size:.6rem;"></i> جارٍ التسجيل... <span id="medcommRecTime">0:00</span>';
      box.appendChild(t);
      medcommScrollBottom();
      medVoiceRec.timer = setInterval(function () {
        var el = document.getElementById('medcommRecTime');
        if (el) el.textContent = medVoiceFmt((Date.now() - medVoiceRec.start) / 1000);
      }, 500);
    }
  }

  function medcommHideRecordingUI() {
    var btn = document.getElementById('medcommMicBtn');
    if (btn) { var ic = btn.querySelector('i'); if (ic) ic.className = 'fas fa-microphone'; btn.style.color = ''; }
    if (medVoiceRec.timer) { clearInterval(medVoiceRec.timer); medVoiceRec.timer = null; }
    var t = document.getElementById('medcommRecRow');
    if (t) t.remove();
  }

  function medcommWireMicButton() {
    var btn = document.getElementById('medcommMicBtn');
    if (btn && !btn.__medcommMicWired) {
      btn.__medcommMicWired = true;
      btn.addEventListener('click', medcommMicToggle);
    }
  }

  function medcommShowTyping() {
    var box = medcommEl("medcommMessages");
    if (!box) return;
    var t = document.createElement("div");
    t.className = "medcomm-typing";
    t.id = "medcommTypingIndicator";
    t.innerHTML = "<span></span><span></span><span></span>";
    box.appendChild(t);
    medcommScrollBottom();
  }

  function medcommHideTyping() {
    var t = medcommEl("medcommTypingIndicator");
    if (t) t.remove();
  }

  /* ── بحث ───────────────────────────────────────────────── */
  window.medcommFilter = function (val) {
    medcommRenderList(val);
  };

  /* ── Emoji Picker بسيط ─────────────────────────────────── */
  window.medcommToggleEmoji = function () {
    var picker = medcommEl("medcommEmojiPicker");
    if (!picker) return;
    picker.classList.toggle("medcomm-open");
  };

  function medcommBuildEmojiPicker() {
    var picker = medcommEl("medcommEmojiPicker");
    if (!picker) return;
    picker.innerHTML = "";
    medcommEmojiList.forEach(function (em) {
      var span = document.createElement("span");
      span.textContent = em;
      span.onclick = function () {
        var input = medcommEl("medcommInput");
        if (input) { input.value += em; input.focus(); }
        picker.classList.remove("medcomm-open");
      };
      picker.appendChild(span);
    });
  }

  /* ── تهيئة القسم عند أول دخول ──────────────────────────── */
  var medcommInitialized = false;

async function loadPatientConversations() {

    const response = await fetch("get_patient_conversations.php");
    const doctors = await response.json();

    MEDCOMM_DOCTORS = doctors.map(d => ({
        id: d.doctor_id,
        recordId: d.record_id,
        name: d.full_name,
        spec: d.specialty,
        initials: d.full_name.substring(0,2),
        online: false,

        // معاينة فقط لعرضها في قائمة المحادثات قبل فتحها — لا تُستخدم كأساس
        // لبناء المحادثة الفعلية. المحادثة الحقيقية الكاملة (لكل الطرفين)
        // تُجلب دائماً من get_medical_messages.php عبر medcommSelectDoctor.
        previewText: d.last_message || "",
        previewTime: d.last_time || "",

        messages: []
    }));

    medcommRenderList("");
}

/* تحديث دوري لرسائل المحادثة المفتوحة حالياً حتى تظهر رسائل الطبيب الجديدة بدون Refresh
   السبب الحقيقي للاختفاء: هذا المؤقّت (كل 4 ثوانٍ) كان يتصادم مع الاستدعاء الذي يحدث
   مباشرة بعد medcommSend() — فإذا تأخّر وصول رد الطلب الدوري "القديم" (المنطلق قبل الإرسال)
   عن رد طلب الإرسال، كان يستبدل الرسالة المُرسلة حديثاً (والمحادثة كاملة أحياناً) بنسخة قديمة.
   نفس المشكلة تحدث عند التبديل السريع بين الأطباء. الحل: عدّاد طلبات مشترك (medcommMessagesRequestId)
   يجعل الكود يتجاهل أي رد ليس هو آخر رد منتظر، ويتجاهل الرد إذا لم يعد نفس الطبيب هو المحادثة النشطة. */
async function medcommRefreshActiveMessages() {
    if (!medcommActiveId) return;
    var activeIdAtStart = medcommActiveId;
    var d = medcommGetDoctor(activeIdAtStart);
    if (!d) return;

    var requestId = ++medcommMessagesRequestId;

    try {
        const response = await fetch("get_medical_messages.php?doctor_id=" + activeIdAtStart);
        const messages = await response.json();

        // تجاهل الرد إذا وصل طلب أحدث منه، أو إذا بدّل المريض المحادثة قبل وصول الرد
        if (requestId !== medcommMessagesRequestId || activeIdAtStart !== medcommActiveId) {
            return;
        }

        d.messages = messages.map(medcommMapServerMessage);
        medcommRenderMessages(activeIdAtStart);

        // Read Receipts: المحادثة نشطة أمام المريض الآن، فنعلّم أي رسالة جديدة
        // موجَّهة إليه كمقروءة (فقط عند وجود غير مقروء موجَّه إليه).
        medcommMaybeMarkRead(activeIdAtStart, messages);
    } catch (err) {
        console.error("فشل تحديث الرسائل:", err);
    }
}

setInterval(medcommRefreshActiveMessages, 4000);
 async function medcommInit() {
    if (medcommInitialized) {
      medcommRenderList("");
      return;
    }
    medcommInitialized = true;
    medcommBuildEmojiPicker();
    await loadPatientConversations();
    medcommRefreshOnline();

if (MEDCOMM_DOCTORS.length > 0) {
    medcommRenderList("");
}

// إلغاء الفتح التلقائي لأول محادثة (ليصبح سلوك المريض مطابقاً لسلوك الطبيب):
// عند أول دخول لا نختار أي طبيب تلقائياً، ولا نستدعي medcommSelectDoctor،
// ولا نُعيّن medcommActiveId، ولا نجلب أو نعرض أي رسالة. نُبقي منطقة المحادثة
// فارغة ونعرض رسالة ترحيبية في الوسط باستخدام صنف التصميم الموجود مسبقاً
// (.medcomm-chat-empty). بمجرد ضغط المريض على طبيب من القائمة يعمل
// medcommSelectDoctor كالمعتاد فيستبدل هذه الرسالة بالمحادثة ويحمّل الرسائل.
var medcommEmptyBox = medcommEl("medcommMessages");
if (medcommEmptyBox) {
    medcommEmptyBox.innerHTML =
        '<div class="medcomm-chat-empty">' +
        '<i class="fas fa-comments"></i>' +
        '<h3 style="margin:0 0 6px;">مرحباً بك في التواصل الطبي</h3>' +
        '<p style="margin:0;">اختر طبيباً من القائمة لبدء المحادثة.</p>' +
        '</div>';
}

// إخفاء شريط الكتابة السفلي بالكامل (الإدخال + الإرسال + الإيموجي + المرفقات)
// ما دام لا يوجد طبيب محدّد. يعود للظهور عند اختيار طبيب داخل medcommSelectDoctor.
var medcommInputBar = document.querySelector(".medcomm-input-row");
if (medcommInputBar) {
    medcommInputBar.style.display = "none";
}
    var input = medcommEl("medcommInput");
    if (input) {
      input.addEventListener("keydown", function (e) {
        if (e.key === "Enter") medcommSend();
      });
    }

    // ربط زر المرفقات (المشبك) لفتح مُنتقي الملفات
    medcommWireAttachButton();
    // ربط زر الميكروفون (الرسائل الصوتية)
    medcommWireMicButton();

    document.addEventListener("click", function (e) {
      var picker = medcommEl("medcommEmojiPicker");
      var emojiBtn = medcommEl("medcommEmojiBtn");
      if (!picker || !emojiBtn) return;
      if (picker.classList.contains("medcomm-open") &&
          !picker.contains(e.target) && e.target !== emojiBtn && !emojiBtn.contains(e.target)) {
        picker.classList.remove("medcomm-open");
      }
    });
  }

  /* يتم استدعاؤها من ptNavTo عند فتح القسم من الـ Sidebar */
  window.medcommInit = medcommInit;

  /* ════════════════════════════════════════════════════════════
     حالة الاتصال الحقيقية (Online) — تعتمد على last_seen في القاعدة،
     لا على بيانات ثابتة. نبض كل 30 ثانية يحدّث آخر ظهور للمريض،
     واستطلاع كل 30 ثانية يجلب حالة الأطباء ويحدّث النقاط الموجودة
     (بلا أي تغيير في التصميم — فقط نجعل النقطة الحالية حقيقية).
  ════════════════════════════════════════════════════════════ */
  function medcommHeartbeat() {
    fetch("heartbeat.php", { method: "POST" }).catch(function () {});
  }

  async function medcommRefreshOnline() {
    if (!MEDCOMM_DOCTORS || !MEDCOMM_DOCTORS.length) return;
    var ids = MEDCOMM_DOCTORS.map(function (d) { return d.id; }).join(",");
    try {
      var resp = await fetch("get_online_status.php?user_ids=" + encodeURIComponent(ids));
      var map = await resp.json();
      var changed = false;
      MEDCOMM_DOCTORS.forEach(function (d) {
        var on = !!map[String(d.id)];
        if (d.online !== on) { d.online = on; changed = true; }
      });
      if (changed) {
        medcommRenderList(medcommEl("medcommSearchInput") ? medcommEl("medcommSearchInput").value : "");
        if (medcommActiveId) medcommRenderChatHeader(medcommActiveId);
      }
    } catch (e) {}
  }

  window.medcommRefreshOnline = medcommRefreshOnline;

  medcommHeartbeat();
  setInterval(medcommHeartbeat, 30000);
  setInterval(medcommRefreshOnline, 30000);

})();
