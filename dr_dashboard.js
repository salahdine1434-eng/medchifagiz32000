// متغيرات عامة
let currentLanguage = 'ar';
let isDarkMode = false;
let currentInterface = 'home';
let currentChatUser = null;
let chatMessages = {};
let currentFollowupPatient = null;
let currentPatientUserId = null; // user_id للمريض في المحادثة المفتوحة (لحالة الاتصال الحقيقية)
var drCurrentMessages = []; // آخر رسائل مُحمَّلة (للبحث عن الرسالة الأصلية في الرد)

// تتبّع آخر حالة تم عرضها فعلياً في نافذة محادثة المتابعة (Follow-up chat) لمنع
// أي Flicker: مفاتيح الرسائل المعروضة حالياً + عدد الرسائل "غير المؤكدة" (إن
// وُجدت) في نهاية القائمة، حتى نضيف الجديد فقط دون مسح المحادثة كاملة.
let medLastRenderedRecordId = null;
let medLastRenderedKeys = [];
let medLastRenderedPendingCount = 0;
// بيانات وهمية للمحادثات
const chatUsersData = {
    patients: [
        {
            id: 'p1',
            name: 'محمد علي',
            avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%234A90E2\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eم%3C/text%3E%3C/svg%3E',
            status: 'متصل الآن',
            lastMessage: 'شكراً دكتور على المتابعة',
            time: '10:30',
            unread: 2
        },
        {
            id: 'p2',
            name: 'فاطمة حسن',
            avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%2350C878\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eف%3C/text%3E%3C/svg%3E',
            status: 'آخر ظهور منذ 5 دقائق',
            lastMessage: 'هل يمكن تغيير الموعد؟',
            time: 'أمس',
            unread: 0
        },
        {
            id: 'p3',
            name: 'أحمد يوسف',
            avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%23FF6B6B\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eأ%3C/text%3E%3C/svg%3E',
            status: 'متصل الآن',
            lastMessage: 'متى موعد الفحص القادم؟',
            time: '14:20',
            unread: 1
        }
    ],
    doctors: [
        {
            id: 'd1',
            name: 'د. خالد محمود',
            avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%23FF6B6B\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eد%3C/text%3E%3C/svg%3E',
            status: 'متصل الآن',
            lastMessage: 'بخصوص المريض المشترك',
            time: '14:20',
            unread: 1
        },
        {
            id: 'd2',
            name: 'د. سارة أحمد',
            avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%23FFA500\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eد%3C/text%3E%3C/svg%3E',
            status: 'آخر ظهور منذ ساعة',
            lastMessage: 'شكراً على الاستشارة',
            time: 'أمس',
            unread: 0
        }
    ]
};

// رسائل وهمية
chatMessages = {
    'p1': [
        { sender: 'received', text: 'السلام عليكم دكتور', time: '10:15' },
        { sender: 'sent', text: 'وعليكم السلام، كيف حالك؟', time: '10:16' },
        { sender: 'received', text: 'الحمد لله، شعرت بتحسن كبير بعد الدواء', time: '10:20' },
        { sender: 'sent', text: 'ممتاز! استمر على الدواء كما وصفته لك', time: '10:22' },
        { sender: 'received', text: 'شكراً دكتور على المتابعة', time: '10:30' }
    ],
    'p2': [
        { sender: 'received', text: 'مساء الخير دكتور', time: 'أمس 15:30' },
        { sender: 'sent', text: 'مساء النور، تفضلي', time: 'أمس 15:32' },
        { sender: 'received', text: 'هل يمكن تغيير الموعد؟', time: 'أمس 15:35' }
    ],
    'p3': [
        { sender: 'received', text: 'دكتور، متى موعد الفحص القادم؟', time: '14:20' },
        { sender: 'sent', text: 'موعدك يوم الخميس الساعة 10 صباحاً', time: '14:25' }
    ],
    'd1': [
        { sender: 'received', text: 'مرحباً د. أحمد', time: '14:00' },
        { sender: 'sent', text: 'أهلاً د. خالد، كيف حالك؟', time: '14:05' },
        { sender: 'received', text: 'بخصوص المريض المشترك، هل استطعت فحصه؟', time: '14:20' }
    ],
    'd2': [
        { sender: 'received', text: 'شكراً على الاستشارة', time: 'أمس 16:00' },
        { sender: 'sent', text: 'العفو، دائماً في الخدمة', time: 'أمس 16:05' }
    ]
};

// بيانات السجل الطبي الوهمية
const medicalRecordsData = {
    'p1': {
        name: 'محمد علي أحمد',
        age: 38,
        gender: 'ذكر',
        bloodType: 'O+',
        phone: '0555111222',
        email: 'mohamed.ali@email.com',
        address: 'حي الوحدة، شارع الجمهورية',
        avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%234A90E2\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'40\' font-family=\'Arial\'%3Eم%3C/text%3E%3C/svg%3E',
        condition: 'حالة عاجلة',
        currentMedications: [
            'أموكسيسيلين 500mg - 3 مرات يومياً',
            'باراسيتامول 500mg - عند الحاجة',
            'فيتامين D 1000IU - مرة يومياً'
        ],
        allergies: [
            'حساسية من البنسلين',
            'حساسية من الفول السوداني'
        ],
        medicalHistory: [
            'التهاب اللوزتين المزمن - 2020',
            'كسر في الذراع اليسرى - 2018',
            'التهاب المعدة - 2019'
        ],
        visits: [
            {
                date: '2026-02-01',
                diagnosis: 'التهاب في الحلق',
                treatment: 'مضاد حيوي + مسكن للألم',
                notes: 'المريض يشكو من ألم شديد في الحلق وصعوبة في البلع'
            },
            {
                date: '2026-01-15',
                diagnosis: 'فحص دوري',
                treatment: 'فيتامينات',
                notes: 'الفحص العام جيد، ننصح بالمتابعة الدورية'
            }
        ],
        vitalSigns: {
            bloodPressure: '120/80',
            heartRate: '72 نبضة/دقيقة',
            temperature: '37.2°C',
            weight: '75 كجم',
            height: '175 سم'
        }
    },
    'p2': {
        name: 'فاطمة حسن محمد',
        age: 35,
        gender: 'أنثى',
        bloodType: 'A+',
        phone: '0555222333',
        email: 'fatima.hassan@email.com',
        address: 'حي السلام، شارع الملك فيصل',
        avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%2350C878\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'40\' font-family=\'Arial\'%3Eف%3C/text%3E%3C/svg%3E',
        condition: 'متابعة دورية',
        currentMedications: [
            'حبوب منع الحمل',
            'فيتامين B12 - مرة يومياً'
        ],
        allergies: [
            'لا توجد حساسية معروفة'
        ],
        medicalHistory: [
            'عملية جراحية في الزائدة الدودية - 2015'
        ],
        visits: [
            {
                date: '2026-01-20',
                diagnosis: 'فحص روتيني',
                treatment: 'فيتامينات',
                notes: 'الصحة العامة ممتازة'
            }
        ],
        vitalSigns: {
            bloodPressure: '115/75',
            heartRate: '68 نبضة/دقيقة',
            temperature: '36.8°C',
            weight: '62 كجم',
            height: '165 سم'
        }
    }
};

// بيانات التحاليل الطبية الوهمية
const labResultsData = {
    patientName: 'محمد علي أحمد',
    patientId: 'P-2026-001',
    testDate: '2026-02-07',
    labName: 'مختبر الشفاء الطبي',
    tests: [
        {
            category: 'تحليل الدم الشامل (CBC)',
            results: [
                { name: 'كريات الدم البيضاء (WBC)', value: '7.2', unit: '×10³/µL', range: '4.0-11.0', status: 'normal' },
                { name: 'كريات الدم الحمراء (RBC)', value: '5.1', unit: '×10⁶/µL', range: '4.5-5.9', status: 'normal' },
                { name: 'الهيموجلوبين (Hb)', value: '14.5', unit: 'g/dL', range: '13.0-17.0', status: 'normal' },
                { name: 'الهيماتوكريت (HCT)', value: '43.2', unit: '%', range: '38.0-50.0', status: 'normal' },
                { name: 'الصفائح الدموية', value: '245', unit: '×10³/µL', range: '150-400', status: 'normal' }
            ]
        },
        {
            category: 'وظائف الكلى',
            results: [
                { name: 'اليوريا (BUN)', value: '18', unit: 'mg/dL', range: '7-20', status: 'normal' },
                { name: 'الكرياتينين', value: '1.1', unit: 'mg/dL', range: '0.7-1.3', status: 'normal' },
                { name: 'حمض اليوريك', value: '5.8', unit: 'mg/dL', range: '3.5-7.2', status: 'normal' }
            ]
        },
        {
            category: 'وظائف الكبد',
            results: [
                { name: 'ALT (SGPT)', value: '28', unit: 'U/L', range: '0-41', status: 'normal' },
                { name: 'AST (SGOT)', value: '32', unit: 'U/L', range: '0-40', status: 'normal' },
                { name: 'البيليروبين الكلي', value: '0.8', unit: 'mg/dL', range: '0.1-1.2', status: 'normal' }
            ]
        },
        {
            category: 'السكر والدهون',
            results: [
                { name: 'الجلوكوز (صائم)', value: '95', unit: 'mg/dL', range: '70-100', status: 'normal' },
                { name: 'الكوليسترول الكلي', value: '185', unit: 'mg/dL', range: '<200', status: 'normal' },
                { name: 'الدهون الثلاثية', value: '140', unit: 'mg/dL', range: '<150', status: 'normal' },
                { name: 'HDL (الكوليسترول الجيد)', value: '55', unit: 'mg/dL', range: '>40', status: 'normal' },
                { name: 'LDL (الكوليسترول الضار)', value: '102', unit: 'mg/dL', range: '<130', status: 'normal' }
            ]
        }
    ]
};

// تهيئة التطبيق
document.addEventListener('DOMContentLoaded', function() {
    initializeChart();
    initializeLanguage();
    setDefaultDates();
    initializeChatInputs();
    loadFollowupPatients();
});

// التبديل بين الواجهات
function switchInterface(interfaceName) {
    document.querySelectorAll('.interface').forEach(interface => {
        interface.classList.remove('active');
    });
    
    // أزل active من كل nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelectorAll('.snav-direct, .snav-header').forEach(el => {
        el.classList.remove('snav-active-direct');
    });
    document.querySelectorAll('.snav-item').forEach(i => {
        i.classList.remove('snav-item-active');
    });
    
    document.getElementById(interfaceName + '-interface').classList.add('active');
    // فعّل العنصر الحالي
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    }
    
    closeAllCardContents();
    currentInterface = interfaceName;
}

// التبديل بين البطاقات في الواجهة الرئيسية
function toggleCard(cardId) {
    // أغلق كارد "إضافة مريض" إذا كان مفتوحاً
    const apfSection = document.getElementById('addPatientFormSection');
    if (apfSection) apfSection.style.display = 'none';

    const cardContent = document.getElementById(cardId);
    const allCards = document.querySelectorAll('.card-content');
    const allMainCards = document.querySelectorAll('.main-card');
    
    allCards.forEach(card => {
        if (card.id !== cardId) {
            card.classList.remove('active');
        }
    });
    
    allMainCards.forEach(card => {
        card.classList.remove('active');
    });
    
    if (cardContent.classList.contains('active')) {
        cardContent.classList.remove('active');
    } else {
        cardContent.classList.add('active');
        event.currentTarget.classList.add('active');
        
        setTimeout(() => {
            cardContent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
}

// إغلاق محتوى البطاقة
function closeCardContent() {
    const allCards = document.querySelectorAll('.card-content');
    const allMainCards = document.querySelectorAll('.main-card');
    
    allCards.forEach(card => {
        card.classList.remove('active');
    });
    
    allMainCards.forEach(card => {
        card.classList.remove('active');
    });

    // أغلق كارد "إضافة مريض" أيضاً
    const apfSection = document.getElementById('addPatientFormSection');
    if (apfSection) apfSection.style.display = 'none';
}

// إغلاق جميع المحتويات
function closeAllCardContents() {
    closeCardContent();
    closeAIContent();
    closeMessageContent();
    closeServiceContent();
    closeMedicalRecordModal();
}

// التبديل بين بطاقات الذكاء الاصطناعي
function toggleAICard(cardId) {
    const cardContent = document.getElementById(cardId);
    const allContents = document.querySelectorAll('.ai-content');
    const allCards = document.querySelectorAll('.ai-card');
    
    allContents.forEach(content => {
        if (content.id !== cardId) {
            content.classList.remove('active');
        }
    });
    
    allCards.forEach(card => {
        card.classList.remove('active');
    });
    
    if (cardContent.classList.contains('active')) {
        cardContent.classList.remove('active');
    } else {
        cardContent.classList.add('active');
        event.currentTarget.classList.add('active');
        
        setTimeout(() => {
            cardContent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
}

// إغلاق محتوى الذكاء الاصطناعي
function closeAIContent() {
    const allContents = document.querySelectorAll('.ai-content');
    const allCards = document.querySelectorAll('.ai-card');
    
    allContents.forEach(content => {
        content.classList.remove('active');
    });
    
    allCards.forEach(card => {
        card.classList.remove('active');
    });
}

// تبديل المساعد الذكي
function toggleAssistant() {
    const assistantChat = document.getElementById('assistantChat');
    assistantChat.classList.toggle('active');
}

// إغلاق المساعد الذكي
function closeAssistant() {
    const assistantChat = document.getElementById('assistantChat');
    assistantChat.classList.remove('active');
}

// التبديل بين بطاقات المحادثات - تحديث مع واجهة Messenger كاملة
function toggleMessageCard(cardId) {
    const cardContent = document.getElementById(cardId);
    const allContents = document.querySelectorAll('.chat-content');
    const allCards = document.querySelectorAll('.message-card');
    
    allContents.forEach(content => {
        if (content.id !== cardId) {
            content.classList.remove('active');
        }
    });
    
    allCards.forEach(card => {
        card.classList.remove('active');
    });
    
    if (cardContent.classList.contains('active')) {
        cardContent.classList.remove('active');
        currentChatUser = null;
    } else {
        cardContent.classList.add('active');
        event.currentTarget.classList.add('active');
        
        // تحميل واجهة المحادثة الكاملة
        const chatType = cardId === 'patientChats' ? 'patients' : 'doctors';
        loadFullChatInterface(cardContent, chatType);
        
        setTimeout(() => {
            cardContent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
}

// تحميل واجهة المحادثة الكاملة
function loadFullChatInterface(container, type) {
    const users = chatUsersData[type];
    
    const chatHTML = `
        <div class="full-chat-interface">
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <h3>${type === 'patients' ? 'المرضى' : 'الأطباء'}</h3>
                    <button onclick="closeMessageContent()"><i class="fas fa-times"></i></button>
                </div>
                <div class="chat-search">
                    <input type="text" placeholder="بحث..." onkeyup="searchChats(this.value, '${type}')">
                </div>
                <div class="chat-list-container" id="chatListContainer-${type}">
                    ${users.map(user => `
                        <div class="chat-list-item" data-user-id="${user.id}" onclick="selectChatUser('${user.id}', '${type}')">
                            <img src="${user.avatar}" alt="${user.name}">
                            <div class="chat-list-item-info">
                                <h4>${user.name}</h4>
                                <p>${user.lastMessage}</p>
                            </div>
                            <div class="chat-meta">
                                <span class="time">${user.time}</span>
                                ${user.unread ? `<span class="unread-badge">${user.unread}</span>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="chat-main" id="chatMain-${type}">
                <div class="no-chat-selected">
                    <i class="fas fa-comments"></i>
                    <p>اختر محادثة لبدء المراسلة</p>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = chatHTML;
}

// اختيار مستخدم للمحادثة
function selectChatUser(userId, type) {
    currentChatUser = { id: userId, type: type };
    
    // تحديث القائمة الجانبية
    document.querySelectorAll(`#chatListContainer-${type} .chat-list-item`).forEach(item => {
        item.classList.remove('active');
    });
    
    const selectedItem = document.querySelector(`#chatListContainer-${type} [data-user-id="${userId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('active');
    }
    
    // تحميل المحادثة
    loadChatMessages(userId, type);
}

// تحميل رسائل المحادثة
function loadChatMessages(userId, type) {
    const users = chatUsersData[type];
    const user = users.find(u => u.id === userId);
    const messages = chatMessages[userId] || [];
    
    const chatMainHTML = `
        <div class="chat-main-header">
            <img src="${user.avatar}" alt="${user.name}">
            <div class="chat-main-header-info">
                <h3>${user.name}</h3>
                <p>${user.status}</p>
            </div>
        </div>
        <div class="chat-main-messages" id="chatMainMessages-${userId}">
            ${messages.map(msg => `
                <div class="chat-message ${msg.sender}">
                    <img src="${msg.sender === 'sent' ? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'35\' height=\'35\'%3E%3Ccircle cx=\'17.5\' cy=\'17.5\' r=\'17.5\' fill=\'%234A90E2\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'14\' font-family=\'Arial\'%3Eد%3C/text%3E%3C/svg%3E' : user.avatar}" class="chat-message-avatar" alt="avatar">
                    <div>
                        <div class="chat-message-bubble">
                            ${msg.text}
                        </div>
                        <div class="chat-message-time">${msg.time}</div>
                    </div>
                </div>
            `).join('')}
        </div>
        <div class="chat-main-input">
            <div class="chat-main-input-field">
                <span class="chat-input-icon"><i class="fas fa-smile"></i></span>
                <input type="text" placeholder="اكتب رسالة..." id="chatInput-${userId}" onkeypress="handleChatEnter(event, '${userId}')">
                <span class="chat-input-icon"><i class="fas fa-paperclip"></i></span>
            </div>
            <button class="chat-send-btn" onclick="sendChatMessage('${userId}')">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    `;
    
    const chatMain = document.getElementById(`chatMain-${type}`);
    if (chatMain) {
        chatMain.innerHTML = chatMainHTML;
        
        // التمرير لآخر رسالة
        setTimeout(() => {
            const messagesContainer = document.getElementById(`chatMainMessages-${userId}`);
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }, 100);
    }
}

// إرسال رسالة في المحادثة
function sendChatMessage(userId) {
    const input = document.getElementById(`chatInput-${userId}`);
    if (!input) return;
    
    const messageText = input.value.trim();
    
    if (!messageText) return;
    
    // إضافة الرسالة للبيانات
    if (!chatMessages[userId]) {
        chatMessages[userId] = [];
    }
    
    const now = new Date();
    const time = now.getHours() + ':' + (now.getMinutes() < 10 ? '0' : '') + now.getMinutes();
    
    chatMessages[userId].push({
        sender: 'sent',
        text: messageText,
        time: time
    });
    
    // إضافة الرسالة للواجهة
    const messagesContainer = document.getElementById(`chatMainMessages-${userId}`);
    if (!messagesContainer) return;
    
    const user = [...chatUsersData.patients, ...chatUsersData.doctors].find(u => u.id === userId);
    
    const messageHTML = `
        <div class="chat-message sent">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='35' height='35'%3E%3Ccircle cx='17.5' cy='17.5' r='17.5' fill='%234A90E2'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='white' font-size='14' font-family='Arial'%3Eد%3C/text%3E%3C/svg%3E" class="chat-message-avatar" alt="avatar">
            <div>
                <div class="chat-message-bubble">
                    ${messageText}
                </div>
                <div class="chat-message-time">${time}</div>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    input.value = '';
    
    // محاكاة رد تلقائي
    setTimeout(() => {
        const autoReply = getAutoReply();
        chatMessages[userId].push({
            sender: 'received',
            text: autoReply,
            time: time
        });
        
        const replyHTML = `
            <div class="chat-message received">
                <img src="${user.avatar}" class="chat-message-avatar" alt="avatar">
                <div>
                    <div class="chat-message-bubble">
                        ${autoReply}
                    </div>
                    <div class="chat-message-time">${time}</div>
                </div>
            </div>
        `;
        
        messagesContainer.insertAdjacentHTML('beforeend', replyHTML);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 2000);
}

// ردود تلقائية عشوائية
function getAutoReply() {
    const replies = [
        'شكراً دكتور',
        'حسناً، سأتبع تعليماتك',
        'متى الموعد القادم؟',
        'هل يمكنني أخذ الدواء مع الطعام؟',
        'الحمد لله، أشعر بتحسن',
        'سأحضر في الموعد المحدد',
        'شكراً على اهتمامك'
    ];
    return replies[Math.floor(Math.random() * replies.length)];
}

// التعامل مع Enter في المحادثة
function handleChatEnter(event, userId) {
    if (event.key === 'Enter') {
        sendChatMessage(userId);
    }
}

// بحث في المحادثات
function searchChats(query, type) {
    const items = document.querySelectorAll(`#chatListContainer-${type} .chat-list-item`);
    const searchLower = query.toLowerCase();
    
    items.forEach(item => {
        const name = item.querySelector('h4').textContent.toLowerCase();
        if (name.includes(searchLower)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// إغلاق محتوى المحادثات
function closeMessageContent() {
    const allContents = document.querySelectorAll('.chat-content');
    const allCards = document.querySelectorAll('.message-card');
    
    allContents.forEach(content => {
        content.classList.remove('active');
    });
    
    allCards.forEach(card => {
        card.classList.remove('active');
    });
    
    currentChatUser = null;
}

// عرض السجل الطبي
function showMedicalRecord(patientId) {
    const record = medicalRecordsData[patientId];
    if (!record) return;
    
    const modalHTML = `
        <div class="medical-record-modal active" id="medicalRecordModal" onclick="closeModalOnBackdrop(event)">
            <div class="medical-record-content" onclick="event.stopPropagation()">
                <div class="medical-record-header">
                    <h2>السجل الطبي</h2>
                    <button class="close-modal-btn" onclick="closeMedicalRecordModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="medical-record-body">
                    <div class="patient-header-info">
                        <img src="${record.avatar}" alt="${record.name}" class="patient-avatar-large">
                        <div class="patient-basic-info">
                            <h3>${record.name}</h3>
                            <p><i class="fas fa-calendar"></i> ${record.age} سنة | ${record.gender}</p>
                            <p><i class="fas fa-tint"></i> فصيلة الدم: ${record.bloodType}</p>
                            <p><i class="fas fa-phone"></i> ${record.phone}</p>
                            <p><i class="fas fa-envelope"></i> ${record.email}</p>
                            <span class="info-badge">${record.condition}</span>
                        </div>
                    </div>
                    
                    <div class="record-section">
                        <h3><i class="fas fa-heartbeat"></i> العلامات الحيوية</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>ضغط الدم</label>
                                <div class="value">${record.vitalSigns.bloodPressure}</div>
                            </div>
                            <div class="info-item">
                                <label>معدل النبض</label>
                                <div class="value">${record.vitalSigns.heartRate}</div>
                            </div>
                            <div class="info-item">
                                <label>درجة الحرارة</label>
                                <div class="value">${record.vitalSigns.temperature}</div>
                            </div>
                            <div class="info-item">
                                <label>الوزن</label>
                                <div class="value">${record.vitalSigns.weight}</div>
                            </div>
                            <div class="info-item">
                                <label>الطول</label>
                                <div class="value">${record.vitalSigns.height}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="record-section">
                        <h3><i class="fas fa-pills"></i> الأدوية الحالية</h3>
                        <ul class="medications-list">
                            ${record.currentMedications.map(med => `
                                <li><i class="fas fa-capsules"></i> ${med}</li>
                            `).join('')}
                        </ul>
                    </div>
                    
                    <div class="record-section">
                        <h3><i class="fas fa-exclamation-triangle"></i> الحساسية</h3>
                        <ul class="allergies-list">
                            ${record.allergies.map(allergy => `
                                <li><i class="fas fa-ban"></i> ${allergy}</li>
                            `).join('')}
                        </ul>
                    </div>
                    
                    <div class="record-section">
                        <h3><i class="fas fa-history"></i> التاريخ المرضي</h3>
                        <ul class="history-list">
                            ${record.medicalHistory.map(history => `
                                <li><i class="fas fa-file-medical"></i> ${history}</li>
                            `).join('')}
                        </ul>
                    </div>
                    
                    <div class="record-section">
                        <h3><i class="fas fa-calendar-alt"></i> سجل الزيارات</h3>
                        <div class="visit-timeline">
                            ${record.visits.map(visit => `
                                <div class="visit-item">
                                    <div class="visit-date">${visit.date}</div>
                                    <div class="visit-details">
                                        <p><strong>التشخيص:</strong> ${visit.diagnosis}</p>
                                        <p><strong>العلاج:</strong> ${visit.treatment}</p>
                                        <p><strong>ملاحظات:</strong> ${visit.notes}</p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // إزالة أي modal موجود
    const existingModal = document.getElementById('medicalRecordModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// إغلاق نافذة السجل الطبي
function closeMedicalRecordModal() {
    const modal = document.getElementById('medicalRecordModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

// إغلاق عند النقر على الخلفية
function closeModalOnBackdrop(event) {
    if (event.target.classList.contains('medical-record-modal')) {
        closeMedicalRecordModal();
    }
}

// إغلاق محتوى الخدمات
function closeServiceContent() {
    const allContents = document.querySelectorAll('.service-content');
    const allCards = document.querySelectorAll('.service-card');
    
    allContents.forEach(content => {
        content.classList.remove('active');
    });
    
    allCards.forEach(card => {
        card.classList.remove('active');
    });
}

// التبديل بين التنبيهات
function toggleNotifications() {
    const notificationsPanel = document.getElementById('notificationsPanel');
    notificationsPanel.classList.toggle('active');
}

// إغلاق التنبيهات
function closeNotifications() {
    const notificationsPanel = document.getElementById('notificationsPanel');
    notificationsPanel.classList.remove('active');
}

// التبديل بين اللغات
function toggleLanguage() {
    const languages = ['ar', 'fr', 'en'];
    const currentIndex = languages.indexOf(currentLanguage);
    const nextIndex = (currentIndex + 1) % languages.length;
    currentLanguage = languages[nextIndex];
    
    if (currentLanguage === 'ar') {
        document.documentElement.setAttribute('dir', 'rtl');
        document.documentElement.setAttribute('lang', 'ar');
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
        document.documentElement.setAttribute('lang', currentLanguage);
    }
    
    updateTexts();
}

// تحديث النصوص حسب اللغة
function updateTexts() {
    const elements = document.querySelectorAll('[data-ar]');
    
    elements.forEach(element => {
        const arText = element.getAttribute('data-ar');
        const frText = element.getAttribute('data-fr');
        const enText = element.getAttribute('data-en');
        
        if (currentLanguage === 'ar' && arText) {
            element.textContent = arText;
        } else if (currentLanguage === 'fr' && frText) {
            element.textContent = frText;
        } else if (currentLanguage === 'en' && enText) {
            element.textContent = enText;
        }
    });
    
    const placeholderElements = document.querySelectorAll('[data-ar-placeholder]');
    placeholderElements.forEach(element => {
        const arPlaceholder = element.getAttribute('data-ar-placeholder');
        const frPlaceholder = element.getAttribute('data-fr-placeholder');
        const enPlaceholder = element.getAttribute('data-en-placeholder');
        
        if (currentLanguage === 'ar' && arPlaceholder) {
            element.setAttribute('placeholder', arPlaceholder);
        } else if (currentLanguage === 'fr' && frPlaceholder) {
            element.setAttribute('placeholder', frPlaceholder);
        } else if (currentLanguage === 'en' && enPlaceholder) {
            element.setAttribute('placeholder', enPlaceholder);
        }
    });
}

// تهيئة اللغة
function initializeLanguage() {
    currentLanguage = 'ar';
    document.documentElement.setAttribute('dir', 'rtl');
    document.documentElement.setAttribute('lang', 'ar');
}

// التبديل بين الوضع النهاري والليلي
function toggleTheme() {
    isDarkMode = !isDarkMode;
    document.body.classList.toggle('dark-mode');
    
    const themeIcon = document.querySelector('.theme-toggle i');
    if (isDarkMode) {
        themeIcon.classList.remove('fa-sun');
        themeIcon.classList.add('fa-moon');
    } else {
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
    }
}

// تهيئة الرسم البياني
function initializeChart() {
    const ctx = document.getElementById('weeklyChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
            datasets: [{
                label: 'عدد المرضى',
                data: [12, 19, 15, 17, 14, 10, 8],
                backgroundColor: 'rgba(74, 144, 226, 0.6)',
                borderColor: 'rgba(74, 144, 226, 1)',
                borderWidth: 2,
                borderRadius: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5
                    }
                }
            }
        }
    });
}

// تعيين التواريخ الافتراضية
function setDefaultDates() {
    const dateInputs = document.querySelectorAll('.booking-date');
    const today = new Date().toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        input.value = today;
    });
    
    const timeInputs = document.querySelectorAll('.booking-time');
    timeInputs.forEach(input => {
        input.value = '09:00';
    });
}

// تهيئة حقول الإدخال في المحادثات
function initializeChatInputs() {
    // تهيئة جميع حقول الإدخال بعد تحميل الصفحة
    setTimeout(() => {
        const aiChatInputs = document.querySelectorAll('.assistant-chat .chat-input input, .supplies-chat .chat-input input');
        aiChatInputs.forEach(input => {
            // إزالة المستمع القديم إن وجد
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            
            newInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim()) {
                    const chatElement = this.closest('.assistant-chat, .supplies-chat');
                    if (chatElement) {
                        sendAIMessage(this.value.trim(), chatElement);
                        this.value = '';
                    }
                }
            });
        });
    }, 500);
}

// إعادة تهيئة عند فتح كارد الخدمات
function toggleServiceCard(cardId) {
    const cardContent = document.getElementById(cardId);
    const allContents = document.querySelectorAll('.service-content');
    const allCards = document.querySelectorAll('.service-card');
    
    allContents.forEach(content => {
        if (content.id !== cardId) {
            content.classList.remove('active');
        }
    });
    
    allCards.forEach(card => {
        card.classList.remove('active');
    });
    
    if (cardContent.classList.contains('active')) {
        cardContent.classList.remove('active');
    } else {
        cardContent.classList.add('active');
        event.currentTarget.classList.add('active');
        
        // إعادة تهيئة حقول الإدخال عند فتح الكارد
        if (cardId === 'suppliesAssistant') {
            setTimeout(() => {
                initializeChatInputs();
            }, 100);
        }
        
        setTimeout(() => {
            cardContent.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    }
}

// إرسال رسالة لمساعد المستلزمات
function sendSuppliesMessage() {
    const input = document.getElementById('suppliesInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    const messagesContainer = document.getElementById('suppliesChatMessages');
    
    // رسالة المستخدم
    const userMessage = document.createElement('div');
    userMessage.className = 'message user-message';
    userMessage.innerHTML = `<p>${message}</p>`;
    messagesContainer.appendChild(userMessage);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    input.value = '';
    
    // رد الذكاء الاصطناعي
    setTimeout(() => {
        const messageLower = message.toLowerCase();
        let aiReply = '';
        
        if (messageLower.includes('قفازات') || messageLower.includes('قفاز')) {
            aiReply = '🧤 <strong>قفازات طبية متوفرة:</strong><br><br>• قفازات لاتكس - صيدلية النور (2.5 كم)<br>• قفازات نيتريل - مركز الشفاء الطبي (3.8 كم)<br>• قفازات جراحية معقمة - صيدلية الأمل (1.2 كم)<br><br>هل تريد عنوان محدد؟';
        } else if (messageLower.includes('كمامات') || messageLower.includes('كمامة') || messageLower.includes('ماسك')) {
            aiReply = '😷 <strong>كمامات طبية:</strong><br><br>• كمامات N95 - صيدلية النور - 15 ريال/علبة<br>• كمامات جراحية 3 طبقات - مركز الشفاء - 8 ريال/علبة<br>• كمامات قماش - صيدلية الحياة - 12 ريال<br><br>جميعها متوفرة بكميات كبيرة.';
        } else if (messageLower.includes('حقن') || messageLower.includes('سرنجة') || messageLower.includes('إبر')) {
            aiReply = '💉 <strong>حقن وسرنجات:</strong><br><br>• سرنجات 5ml معقمة - صيدلية النور<br>• إبر حقن مختلفة المقاسات - مركز الشفاء<br>• سرنجات الأنسولين - صيدلية الأمل<br><br>متوفرة بكميات كبيرة.';
        } else if (messageLower.includes('ضماد') || messageLower.includes('شاش') || messageLower.includes('لاصق')) {
            aiReply = '🩹 <strong>ضمادات وشاش:</strong><br><br>• شاش طبي معقم - جميع الصيدليات<br>• ضمادات لاصقة - صيدلية النور<br>• شريط لاصق طبي - مركز الشفاء<br>• ضمادات مضادة للماء - صيدلية الحياة';
        } else if (messageLower.includes('معقم') || messageLower.includes('تعقيم') || messageLower.includes('كحول')) {
            aiReply = '🧴 <strong>معقمات ومطهرات:</strong><br><br>• معقم يدين (كحول 70%) - صيدلية النور - 12 ريال<br>• ديتول معقم للأسطح - مركز الشفاء - 18 ريال<br>• كحول طبي 95% - صيدلية الأمل - 8 ريال<br>• مناديل معقمة - جميع الصيدليات';
        } else if (messageLower.includes('جهاز ضغط') || messageLower.includes('قياس ضغط')) {
            aiReply = '🩺 <strong>أجهزة قياس ضغط الدم:</strong><br><br>• جهاز أومرون الرقمي - مركز الشفاء - 180 ريال<br>• جهاز بيورر الألماني - صيدلية النور - 220 ريال<br>• جهاز يدوي تقليدي - صيدلية الأمل - 85 ريال';
        } else if (messageLower.includes('سكر') || messageLower.includes('جلوكوز')) {
            aiReply = '🩸 <strong>أجهزة قياس السكر:</strong><br><br>• جهاز أكيوتشك - صيدلية النور - 120 ريال<br>• جهاز وان تاتش - مركز الشفاء - 95 ريال<br>• شرائط فحص السكر - من 40 ريال/علبة 50 شريط';
        } else if (messageLower.includes('حرارة') || messageLower.includes('ثرمومتر')) {
            aiReply = '🌡️ <strong>موازين الحرارة:</strong><br><br>• ثرمومتر رقمي سريع - 25 ريال<br>• ثرمومتر بالأشعة تحت الحمراء - 85 ريال<br>• ثرمومتر زئبقي - 15 ريال<br><br>متوفرة في صيدلية النور ومركز الشفاء.';
        } else if (messageLower.includes('شكرا') || messageLower.includes('شكراً')) {
            aiReply = '😊 العفو! سعيد بمساعدتك. إذا احتجت أي شيء آخر، أنا هنا!';
        } else {
            const generalReplies = [
                '🤖 يمكنني مساعدتك في العثور على أي مستلزمات طبية.<br><br>جرّب السؤال عن:<br>• قفازات<br>• كمامات<br>• حقن<br>• ضمادات<br>• معقمات<br>• أجهزة قياس',
                '📍 لدينا قاعدة بيانات شاملة بجميع الصيدليات القريبة.<br><br>ماذا تحتاج بالتحديد؟',
                '🔍 أخبرني عن المستلزمات التي تبحث عنها وسأجد لك أقرب الأماكن المتوفرة فيها مع الأسعار!',
                '💡 يمكنني مساعدتك في إيجاد أي مستلزمات طبية بالقرب منك. اسألني عن أي شيء!'
            ];
            aiReply = generalReplies[Math.floor(Math.random() * generalReplies.length)];
        }
        
        const aiMessage = document.createElement('div');
        aiMessage.className = 'message ai-message';
        aiMessage.innerHTML = `<p>${aiReply}</p>`;
        messagesContainer.appendChild(aiMessage);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 1000);
}

// معالجة Enter في مساعد المستلزمات
function handleSuppliesEnter(event) {
    if (event.key === 'Enter') {
        sendSuppliesMessage();
    }
}

// إرسال رسالة للذكاء الاصطناعي
function sendAIMessage(message, chatElement) {
    // هذه الدالة للمساعد الذكي العام فقط (ليس مساعد المستلزمات)
    const messagesContainer = chatElement.querySelector('.chat-messages');
    
    // رسالة المستخدم
    const userMessage = document.createElement('div');
    userMessage.className = 'message user-message';
    userMessage.innerHTML = `<p>${message}</p>`;
    messagesContainer.appendChild(userMessage);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // رد الذكاء الاصطناعي
    setTimeout(() => {
        const aiReplies = [
            'شكراً على سؤالك. كيف يمكنني مساعدتك أكثر؟',
            'يمكنني تنظيم ملفات المرضى حسب الأولوية والحالة.',
            'سأقوم بإنشاء تقرير طبي مفصل بناءً على المعلومات المتوفرة.',
            'أقترح عليك مراجعة السجلات الطبية للمريض قبل الموعد القادم.',
            'هل تريد مني مساعدتك في شيء محدد؟'
        ];
        
        const aiMessage = document.createElement('div');
        aiMessage.className = 'message ai-message';
        aiMessage.innerHTML = `<p>${aiReplies[Math.floor(Math.random() * aiReplies.length)]}</p>`;
        messagesContainer.appendChild(aiMessage);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 1000);
}

// معالجة أزرار إرسال الرسائل
document.addEventListener('click', function(e) {
    // زر إرسال في المحادثات الذكية
    if (e.target.closest('.chat-input button:not(.confirm-btn):not(.scan-btn):not(.view-file-btn):not(.view-records-btn):not(.generate-report-btn):not(.view-lab-results-btn)')) {
        const button = e.target.closest('button');
        const chatInput = button.closest('.chat-input');
        if (chatInput) {
            const input = chatInput.querySelector('input');
            if (input && input.value.trim()) {
                const chatElement = button.closest('.assistant-chat, .supplies-chat');
                if (chatElement) {
                    sendAIMessage(input.value.trim(), chatElement);
                    input.value = '';
                }
            }
        }
    }
});

// معالجة تأكيد المواعيد
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('confirm-btn')) {
        const bookingItem = e.target.closest('.booking-item');
        const dateInput = bookingItem.querySelector('.booking-date');
        const timeInput = bookingItem.querySelector('.booking-time');
        
        if (dateInput.value && timeInput.value) {
            e.target.textContent = '✓ تم التأكيد';
            e.target.style.background = '#50C878';
            e.target.disabled = true;
            
            // إضافة تأثير النجاح
            bookingItem.style.background = 'rgba(80, 200, 120, 0.1)';
        }
    }
});

// فتح سجل من أرشيف المرضى داخل Modal منبثق مركزي
// لا يُخفى أي عنصر، ولا يُنقل أي عنصر داخل DOM، ولا يُستخدم appendChild على القائمة.
// قائمة المرضى (.archive-search / .archive-list) تبقى ظاهرة في الخلفية كما هي.
function openArchiveRecord(id) {
    var modal = document.getElementById('archiveRecordModal');
    var body  = document.getElementById('archiveRecordBody');
    if (!modal || !body) return;

    // خزّن معرّف السجل الحالي (نفس مفتاح view_record.php) لاستخدامه في حفظ التعديلات
    window._armRecordId = id;
    // إعادة ضبط حالة أزرار التعديل/الحفظ إلى الوضع الافتراضي
    armResetEditButtons();

    // مؤشر تحميل داخل جسم الـ Modal فقط
    body.innerHTML = '<div style="text-align:center;padding:40px;color:#64748b;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    // فتح الـ Modal فقط (لا تغيير على layout الصفحة ولا على أي عنصر آخر)
    modal.style.display = 'flex';

    fetch('view_record.php?id=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            body.innerHTML = html;
            // إعادة تنفيذ أي <script> داخل محتوى view_record.php كما هو الآن
            body.querySelectorAll('script').forEach(function(oldScript) {
                var newScript = document.createElement('script');
                newScript.textContent = oldScript.textContent;
                document.body.appendChild(newScript);
                newScript.remove();
            });
        })
        .catch(function() {
            body.innerHTML = '<div style="text-align:center;padding:40px;color:#ef4444;">تعذّر تحميل السجل</div>';
        });
}

// إغلاق الـ Modal فقط — display:none فحسب، دون أي reload أو إعادة رسم أو إظهار/إخفاء أي عنصر آخر
function closeArchiveRecord() {
    var modal = document.getElementById('archiveRecordModal');
    if (!modal) return;
    modal.style.display = 'none';
    armResetEditButtons();
    var body = document.getElementById('archiveRecordBody');
    if (body) { body.classList.remove('arm-editing'); body.innerHTML = ''; }
}

// إغلاق عند الضغط على الخلفية المعتمة فقط (خارج صندوق الـ Modal)
function armBackdrop(e) {
    if (e && e.target && e.target.id === 'archiveRecordModal') closeArchiveRecord();
}

/* ══════════════════════════════════════════════════════════════
   تعديل الملف الطبي داخل نافذة الأرشيف — تحويل الحقول إلى Editable
   ثم حفظ التعديلات في نفس السجل عبر UPDATE (update_record.php)
   ▸ يخص نافذة الأرشيف فقط (archiveRecordModal) — مستقل عن الغرف.
   ▸ لا يمسّ التقرير الطبي (rapport_medical) ولا بطاقة العلاج (fiche).
══════════════════════════════════════════════════════════════ */

// إعادة الأزرار إلى وضعها الافتراضي (إظهار "تعديل"، إخفاء "حفظ")
function armResetEditButtons() {
    var editBtn = document.getElementById('armEditBtn');
    var saveBtn = document.getElementById('armSaveBtn');
    if (editBtn) editBtn.style.display = '';
    if (saveBtn) { saveBtn.style.display = 'none'; saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-save"></i><span>حفظ التعديلات</span>'; }
}

// تفعيل وضع التعديل: تحويل قيم الحقول إلى مدخلات قابلة للكتابة
function armToggleEdit() {
    var body = document.getElementById('archiveRecordBody');
    if (!body) return;

    // الحقول التي تحمل data-field-name فقط = أعمدة medical_records (دون التقرير/الفيش)
    var holders = body.querySelectorAll('[data-field-name]');
    holders.forEach(function(holder) {
        var fieldName = holder.getAttribute('data-field-name');
        if (!fieldName) return;
        var valEl = holder.querySelector('.vr-field-value, .chip-value');
        if (!valEl || valEl.querySelector('.arm-input')) return;

        // النص الحالي مع الحفاظ على الأسطر
        var current = (valEl.innerText || '').trim();
        var isWide  = holder.classList.contains('vr-wide'); // الحقول الطويلة → textarea

        var input;
        if (isWide) {
            input = document.createElement('textarea');
            var lines = current.split('\n').length;
            input.rows = Math.min(8, Math.max(2, lines + 1));
        } else {
            input = document.createElement('input');
            input.type = 'text';
        }
        input.className = 'arm-input';
        input.value = current;
        input.setAttribute('data-edit-field', fieldName);

        valEl.innerHTML = '';
        valEl.appendChild(input);
    });

    body.classList.add('arm-editing');
    var editBtn = document.getElementById('armEditBtn');
    var saveBtn = document.getElementById('armSaveBtn');
    if (editBtn) editBtn.style.display = 'none';
    if (saveBtn) saveBtn.style.display = '';
}

// حفظ التعديلات: UPDATE في نفس السجل الطبي (لا INSERT)
function armSaveEdits() {
    var body = document.getElementById('archiveRecordBody');
    var id = window._armRecordId;
    if (!body || !id) return;

    var inputs = body.querySelectorAll('[data-edit-field]');
    var fields = {};
    inputs.forEach(function(inp) { fields[inp.getAttribute('data-edit-field')] = inp.value; });

    var saveBtn = document.getElementById('armSaveBtn');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>جارٍ الحفظ...</span>'; }

    fetch('update_record.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, fields: fields })
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res && res.success) {
            armShowToast('تم حفظ التعديلات بنجاح', 'success');
            // أعد تحميل السجل لإظهار آخر نسخة محفوظة والخروج من وضع التعديل
            openArchiveRecord(id);
        } else {
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-save"></i><span>حفظ التعديلات</span>'; }
            armShowToast((res && res.message) ? res.message : 'تعذّر حفظ التعديلات', 'error');
        }
    })
    .catch(function() {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = '<i class="fas fa-save"></i><span>حفظ التعديلات</span>'; }
        armShowToast('تعذّر الاتصال بالخادم', 'error');
    });
}

// طباعة الملف الطبي فقط — صفحة A4، RTL، تصميم أزرق احترافي، دون Sidebar/أزرار/خلفية
function armPrint() {
    var body = document.getElementById('archiveRecordBody');
    if (!body) return;

    // إذا كان في وضع تعديل، اطبع القيم النصية الحالية
    var content = body.innerHTML;
    var printedDate = new Date().toLocaleDateString('ar-DZ');

    var css =
        '@page { size: A4; margin: 14mm; }' +
        '* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }' +
        'html, body { margin:0; padding:0; background:#fff; }' +
        'body { font-family:"Cairo","Segoe UI",Tahoma,sans-serif; direction:rtl; color:#0f172a; }' +
        '.arm-print-header { display:flex; align-items:center; justify-content:space-between; gap:12px;' +
            'padding:14px 18px; margin-bottom:16px; border-radius:12px;' +
            'background:linear-gradient(135deg,#0ea5e9,#06b6d4); color:#fff;' +
            'box-shadow:0 4px 14px rgba(14,165,233,0.25); }' +
        '.arm-print-header .aph-title { font-size:1.15rem; font-weight:800; display:flex; align-items:center; gap:10px; }' +
        '.arm-print-header .aph-title i { font-size:1.2rem; }' +
        '.arm-print-header .aph-meta { font-size:0.72rem; opacity:0.95; text-align:left; line-height:1.6; }' +
        '.arm-print-wrap .vr-header { display:none; }' +            /* تجنّب تكرار العنوان */
        '.arm-print-wrap .vr-group { break-inside: avoid; page-break-inside: avoid; }' +
        '.arm-print-wrap .arm-input { border:none !important; background:transparent !important; padding:0 !important;' +
            'font-weight:600; color:#0f172a; width:auto; resize:none; }' +
        '.arm-print-footer { margin-top:18px; padding-top:10px; border-top:1px dashed rgba(14,165,233,0.3);' +
            'font-size:0.66rem; color:#94a3b8; text-align:center; }';

    var headerHTML =
        '<div class="arm-print-header">' +
            '<div class="aph-title"><i class="fas fa-notes-medical"></i> الملف الطبي</div>' +
            '<div class="aph-meta">CHU — MedChifaGiz<br>تاريخ الطباعة: ' + printedDate + '</div>' +
        '</div>';

    var footerHTML = '<div class="arm-print-footer">وثيقة طبية — MedChifaGiz</div>';

    var win = window.open('', '_blank', 'width=900,height=1000');
    if (!win) { armShowToast('فضلاً اسمح بالنوافذ المنبثقة لإتمام الطباعة', 'error'); return; }

    var doc = win.document;
    doc.open();
    doc.write(
        '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8">' +
        '<title>الملف الطبي</title>' +
        '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">' +
        '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">' +
        '<style>' + css + '</style>' +
        '</head><body>' +
            headerHTML +
            '<div class="arm-print-wrap">' + content + '</div>' +
            footerHTML +
        '</body></html>'
    );
    doc.close();
    win.focus();
    // مهلة بسيطة لتحميل الخطوط/الأيقونات قبل الطباعة
    setTimeout(function() { try { win.print(); } catch (e) {} }, 450);
}

// تنبيه صغير مستقل (خاص بنافذة الأرشيف)
function armShowToast(message, type) {
    var bg = (type === 'error') ? 'linear-gradient(135deg,#ef4444,#f87171)' : 'linear-gradient(135deg,#10b981,#34d399)';
    var icon = (type === 'error') ? 'fa-circle-exclamation' : 'fa-circle-check';
    var t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);' +
        'z-index:100000;display:flex;align-items:center;gap:9px;padding:12px 20px;border-radius:12px;' +
        'background:' + bg + ';color:#fff;font-family:\'Cairo\',sans-serif;font-size:0.86rem;font-weight:700;' +
        'box-shadow:0 10px 30px rgba(15,23,42,0.28);opacity:0;transition:opacity .25s,transform .25s;direction:rtl;';
    t.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';
    document.body.appendChild(t);
    requestAnimationFrame(function() { t.style.opacity = '1'; t.style.transform = 'translateX(-50%) translateY(0)'; });
    setTimeout(function() {
        t.style.opacity = '0'; t.style.transform = 'translateX(-50%) translateY(20px)';
        setTimeout(function() { if (t.parentNode) t.parentNode.removeChild(t); }, 300);
    }, 2600);
}

// معالجة زر عرض السجلات - فتح الملف الطبي الحقيقي من قاعدة البيانات
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-records-btn')) {
        var id = parseInt(e.target.getAttribute('data-id'), 10);
        if (id > 0) {
            openArchiveRecord(id);
        }
    }
});

// معالجة زر مسح QR
document.querySelectorAll('.scan-btn').forEach(button => {
    button.addEventListener('click', function() {
        // محاكاة فتح الكاميرا
        this.textContent = 'جاري المسح...';
        this.disabled = true;
        
        setTimeout(() => {
            const scannerArea = this.closest('.qr-scanner, .lab-scanner');
            if (scannerArea) {
                const scannedResult = scannerArea.querySelector('.scanned-result');
                if (scannedResult) {
                    scannerArea.querySelector('.scanner-area').style.display = 'none';
                    scannedResult.style.display = 'block';
                }
            }
        }, 2000);
    });
});

// معالجة زر عرض نتائج التحاليل
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-lab-results-btn')) {
        showLabResults();
    }
});

// عرض نتائج التحاليل
function showLabResults() {
    const data = labResultsData;
    
    const modalHTML = `
        <div class="medical-record-modal active" id="labResultsModal" onclick="closeModalOnBackdrop(event)">
            <div class="medical-record-content" onclick="event.stopPropagation()">
                <div class="medical-record-header">
                    <h2>نتائج التحاليل الطبية</h2>
                    <button class="close-modal-btn" onclick="closeLabResultsModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="medical-record-body">
                    <div class="patient-header-info" style="background: linear-gradient(135deg, #4A90E2, #50C878); color: white; padding: 1.5rem; border-radius: 15px; margin-bottom: 2rem;">
                        <div style="text-align: center;">
                            <h3 style="color: white; font-size: 1.5rem; margin-bottom: 0.5rem;">${data.patientName}</h3>
                            <p style="color: rgba(255,255,255,0.9);"><strong>رقم المريض:</strong> ${data.patientId}</p>
                            <p style="color: rgba(255,255,255,0.9);"><strong>تاريخ التحليل:</strong> ${data.testDate}</p>
                            <p style="color: rgba(255,255,255,0.9);"><strong>المختبر:</strong> ${data.labName}</p>
                        </div>
                    </div>
                    
                    ${data.tests.map(category => `
                        <div class="record-section">
                            <h3><i class="fas fa-flask"></i> ${category.category}</h3>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                                    <thead>
                                        <tr style="background: #f8f9fa;">
                                            <th style="padding: 1rem; text-align: right; border: 1px solid #e5e7eb;">الفحص</th>
                                            <th style="padding: 1rem; text-align: center; border: 1px solid #e5e7eb;">النتيجة</th>
                                            <th style="padding: 1rem; text-align: center; border: 1px solid #e5e7eb;">الوحدة</th>
                                            <th style="padding: 1rem; text-align: center; border: 1px solid #e5e7eb;">المعدل الطبيعي</th>
                                            <th style="padding: 1rem; text-align: center; border: 1px solid #e5e7eb;">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${category.results.map(test => `
                                            <tr>
                                                <td style="padding: 0.8rem; border: 1px solid #e5e7eb;">${test.name}</td>
                                                <td style="padding: 0.8rem; text-align: center; border: 1px solid #e5e7eb; font-weight: 600;">${test.value}</td>
                                                <td style="padding: 0.8rem; text-align: center; border: 1px solid #e5e7eb;">${test.unit}</td>
                                                <td style="padding: 0.8rem; text-align: center; border: 1px solid #e5e7eb; color: #6B7280;">${test.range}</td>
                                                <td style="padding: 0.8rem; text-align: center; border: 1px solid #e5e7eb;">
                                                    <span style="display: inline-block; padding: 0.3rem 0.8rem; background: ${test.status === 'normal' ? '#50C878' : '#FF6B6B'}; color: white; border-radius: 15px; font-size: 0.85rem;">
                                                        ${test.status === 'normal' ? '✓ طبيعي' : '⚠ غير طبيعي'}
                                                    </span>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `).join('')}
                    
                    <div class="record-section" style="background: #f0f9ff; padding: 1.5rem; border-radius: 15px; border-right: 4px solid #4A90E2;">
                        <h3 style="color: #4A90E2; margin-bottom: 1rem;"><i class="fas fa-notes-medical"></i> ملاحظات الطبيب</h3>
                        <p style="color: #1F2937; line-height: 1.8;">
                            نتائج التحاليل تظهر أن جميع القيم ضمن المعدل الطبيعي. يُنصح بالاستمرار على نفس النظام الغذائي والدوائي الحالي. 
                            المتابعة الدورية بعد 3 أشهر للتأكد من استقرار الحالة الصحية.
                        </p>
                        <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 10px;">
                            <p style="color: #6B7280; font-size: 0.9rem;">
                                <i class="fas fa-user-md"></i> د. أحمد السعيدي - أخصائي طب عام
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // إزالة أي modal موجود
    const existingModal = document.getElementById('labResultsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// إغلاق نافذة نتائج التحاليل
function closeLabResultsModal() {
    const modal = document.getElementById('labResultsModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

/* ════════════════════════════════════════════════════════
   SVC RESULTS — النتائج المخبرية ونتائج الأشعة
   ──────────────────────────────────────────────────────
   • بيانات تجريبية (placeholder) تُعرض كبطاقات احترافية
   • لم يُعدَّل أي كود QR أو API أو Database
   ════════════════════════════════════════════════════════ */

// ── بيانات النتائج المخبرية الواردة ──
const svcLabResults = [
    {
        id: 'LR-001',
        patientName: 'محمد علي أحمد',
        fileNumber: 'P-2026-001',
        testName: 'تحليل الدم الشامل (CBC)',
        labName: 'مختبر الشفاء الطبي',
        sentAt: '2026-06-28T09:15:00',
        status: 'new',
        reportContent: labResultsData
    },
    {
        id: 'LR-002',
        patientName: 'فاطمة بن يوسف',
        fileNumber: 'P-2026-047',
        testName: 'وظائف الكبد والكلى',
        labName: 'مختبر النور المركزي',
        sentAt: '2026-06-27T14:30:00',
        status: 'reviewed',
        reportContent: labResultsData
    },
    {
        id: 'LR-003',
        patientName: 'عمر بلحسن',
        fileNumber: 'P-2026-083',
        testName: 'السكر والدهون (Lipid Profile)',
        labName: 'مختبر الأمل الطبي',
        sentAt: '2026-06-26T11:00:00',
        status: 'new',
        reportContent: labResultsData
    }
];

// ── بيانات نتائج الأشعة الواردة ──
const svcXrayResults = [
    {
        id: 'XR-001',
        patientName: 'محمد علي أحمد',
        fileNumber: 'P-2026-001',
        xrayType: 'أشعة صدر (Chest X-Ray)',
        center: 'مركز الأشعة الرقمي — حي السلام',
        sentAt: '2026-06-28T10:00:00',
        status: 'new'
    },
    {
        id: 'XR-002',
        patientName: 'نوال قاسم',
        fileNumber: 'P-2026-062',
        xrayType: 'إيكوغرافيا بطن (Échographie abdominale)',
        center: 'قسم الأشعة — مستشفى ابن سينا',
        sentAt: '2026-06-27T08:45:00',
        status: 'reviewed'
    }
];

// ── مساعد: تنسيق التاريخ والوقت ──
function svcFormatDate(isoStr) {
    try {
        const d = new Date(isoStr);
        return d.toLocaleDateString('ar-DZ', { year:'numeric', month:'short', day:'numeric' })
             + ' — ' + d.toLocaleTimeString('ar-DZ', { hour:'2-digit', minute:'2-digit' });
    } catch(e) { return isoStr; }
}

/* ════════════════════════════════════════════════════════
   SVC NOTES — ملاحظات الطبيب لكل تقرير (إضافة فقط)
   • كل تقرير يحتفظ بملاحظاته المنفصلة بـ key = type:id
   • الحفظ: localStorage كـ fallback + استعداد لربط API
   • الإرسال: يغير حالة البطاقة ويُشعر المريض (placeholder API)
════════════════════════════════════════════════════════ */

// مخزن الملاحظات في الذاكرة (يُعاد تحميله من localStorage)
const svcNotesStore = {};

function svcNotesKey(type, id) { return 'svc_note_' + type + '_' + id; }

function svcLoadNote(type, id) {
    const k = svcNotesKey(type, id);
    if (svcNotesStore[k] !== undefined) return svcNotesStore[k];
    try { return localStorage.getItem(k) || ''; } catch(e) { return ''; }
}

function svcPersistNote(type, id, text) {
    const k = svcNotesKey(type, id);
    svcNotesStore[k] = text;
    try { localStorage.setItem(k, text); } catch(e) {}
    // ── نقطة ربط API: عوّض هذا الكود بطلب fetch حقيقي ──
    // fetch('/api/save_result_note', {
    //     method: 'POST',
    //     headers: {'Content-Type':'application/json'},
    //     body: JSON.stringify({ type, id, note: text })
    // });
}

function svcSaveNote(type, id) {
    const textarea = document.getElementById('svc-note-' + type + '-' + id);
    if (!textarea) return;
    const text = textarea.value.trim();
    svcPersistNote(type, id, text);

    // تغذية راجعة بصرية على الزر ثم إغلاق القسم
    const btn = document.getElementById('svc-save-btn-' + type + '-' + id);
    if (btn) {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> تم الحفظ';
        btn.classList.add('svc-btn-saved');
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.classList.remove('svc-btn-saved');
            svcCloseNotes(type, id);
        }, 1200);
    } else {
        svcCloseNotes(type, id);
    }
}

function svcToggleNotes(type, id) {
    const wrap = document.getElementById('svc-notes-wrap-' + type + '-' + id);
    if (!wrap) return;
    if (wrap.classList.contains('svc-notes-open')) {
        svcCloseNotes(type, id);
    } else {
        // تحميل الملاحظات المحفوظة مسبقاً داخل الـ textarea
        const textarea = document.getElementById('svc-note-' + type + '-' + id);
        if (textarea) {
            textarea.value = svcLoadNote(type, id);
        }
        wrap.classList.add('svc-notes-open');
    }
}

function svcCloseNotes(type, id) {
    const wrap = document.getElementById('svc-notes-wrap-' + type + '-' + id);
    if (wrap) wrap.classList.remove('svc-notes-open');
}

function svcSendToPatient(type, id) {
    const data = type === 'lab'
        ? svcLabResults.find(i => i.id === id)
        : svcXrayResults.find(i => i.id === id);
    if (!data) return;

    const noteText = svcLoadNote(type, id);

    // ── نقطة ربط API: عوّض alert بطلب fetch حقيقي ──
    // fetch('/api/send_result_to_patient', {
    //     method: 'POST',
    //     headers: {'Content-Type':'application/json'},
    //     body: JSON.stringify({
    //         type, resultId: id,
    //         patientFileNumber: data.fileNumber,
    //         doctorNote: noteText,
    //         notificationMsg: 'أرسل إليك طبيبك نتيجة جديدة. يرجى الاطلاع عليها.'
    //     })
    // });

    // تحديث الحالة محلياً
    data.status = 'sent';
    const badge = document.getElementById('svc-badge-' + type + '-' + id);
    if (badge) {
        badge.textContent = 'تم إرسالها للمريض';
        badge.className = 'svc-result-badge svc-badge-sent';
    }
    const sendBtn = document.getElementById('svc-send-btn-' + type + '-' + id);
    if (sendBtn) {
        sendBtn.innerHTML = '<i class="fas fa-check-circle"></i> تم الإرسال';
        sendBtn.disabled = true;
        sendBtn.classList.add('svc-btn-done');
    }

    // إعادة رسم الشبكة بعد لحظة للحفاظ على حالة Badge
    setTimeout(() => renderSvcResults(type, 'all'), 2200);
}

// ── بناء قسم ملاحظات الطبيب (مشترك بين Lab وXray) ──
function buildNotesSection(type, id) {
    const saved = svcLoadNote(type, id);
    return `
    <div class="svc-notes-collapsible" id="svc-notes-wrap-${type}-${id}">
        <div class="svc-notes-inner">
            <div class="svc-notes-label">
                <i class="fas fa-notes-medical"></i>
                ملاحظات الطبيب
            </div>
            <textarea
                id="svc-note-${type}-${id}"
                class="svc-notes-textarea"
                placeholder="اكتب ملاحظاتك الطبية هنا..."
                rows="3"
            >${saved}</textarea>
            <div class="svc-notes-actions svc-notes-actions-inner">
                <button
                    id="svc-save-btn-${type}-${id}"
                    class="svc-btn svc-btn-ghost svc-btn-sm"
                    onclick="svcSaveNote('${type}','${id}')">
                    <i class="fas fa-save"></i> حفظ الملاحظات
                </button>
                <button
                    class="svc-btn svc-btn-ghost svc-btn-sm svc-btn-cancel"
                    onclick="svcCloseNotes('${type}','${id}')">
                    <i class="fas fa-times"></i> إلغاء
                </button>
            </div>
        </div>
    </div>`;
}

// ── بناء بطاقة نتيجة مخبرية ──
function buildLabCard(item) {
    const statusMap = { new:'جديدة', reviewed:'تمت المراجعة', sent:'تم إرسالها للمريض' };
    const classMap  = { new:'svc-badge-new', reviewed:'svc-badge-reviewed', sent:'svc-badge-sent' };
    const statusLabel = statusMap[item.status] || 'جديدة';
    const statusClass = classMap[item.status]  || 'svc-badge-new';
    return `
    <div class="svc-result-card" data-status="${item.status}" data-type="lab" data-id="${item.id}">
        <div class="svc-result-card-top">
            <div class="svc-result-avatar">
                <i class="fas fa-flask"></i>
            </div>
            <div class="svc-result-meta">
                <div class="svc-result-patient">${item.patientName}</div>
                <div class="svc-result-file"><i class="fas fa-id-card"></i> ${item.fileNumber}</div>
            </div>
            <span class="svc-result-badge ${statusClass}" id="svc-badge-lab-${item.id}">${statusLabel}</span>
        </div>
        <div class="svc-result-info">
            <div class="svc-result-info-row">
                <i class="fas fa-vials"></i>
                <span>${item.testName}</span>
            </div>
            <div class="svc-result-info-row">
                <i class="fas fa-hospital"></i>
                <span>${item.labName}</span>
            </div>
            <div class="svc-result-info-row">
                <i class="fas fa-clock"></i>
                <span>${svcFormatDate(item.sentAt)}</span>
            </div>
        </div>
        <div class="svc-result-actions">
            <button class="svc-btn svc-btn-primary" onclick="openSvcReport('lab','${item.id}')">
                <i class="fas fa-eye"></i> فتح التقرير
            </button>
            <button class="svc-btn svc-btn-ghost" onclick="downloadSvcReport('lab','${item.id}')">
                <i class="fas fa-file-pdf"></i> تحميل PDF
            </button>
            <button class="svc-btn svc-btn-ghost" onclick="svcToggleNotes('lab','${item.id}')">
                <i class="fas fa-notes-medical"></i> إضافة ملاحظات
            </button>
            <button
                id="svc-send-btn-lab-${item.id}"
                class="svc-btn svc-btn-send svc-btn-sm"
                ${item.status === 'sent' ? 'disabled' : ''}
                onclick="svcSendToPatient('lab','${item.id}')">
                ${item.status === 'sent' ? '<i class="fas fa-check-circle"></i> تم الإرسال' : '<i class="fas fa-paper-plane"></i> إرسال للمريض'}
            </button>
        </div>
        ${buildNotesSection('lab', item.id)}
    </div>`;
}

// ── بناء بطاقة نتيجة أشعة ──
function buildXrayCard(item) {
    const statusMap = { new:'جديدة', reviewed:'تمت المراجعة', sent:'تم إرسالها للمريض' };
    const classMap  = { new:'svc-badge-new', reviewed:'svc-badge-reviewed', sent:'svc-badge-sent' };
    const statusLabel = statusMap[item.status] || 'جديدة';
    const statusClass = classMap[item.status]  || 'svc-badge-new';
    return `
    <div class="svc-result-card" data-status="${item.status}" data-type="xray" data-id="${item.id}">
        <div class="svc-result-card-top">
            <div class="svc-result-avatar svc-result-avatar-xray">
                <i class="fas fa-x-ray"></i>
            </div>
            <div class="svc-result-meta">
                <div class="svc-result-patient">${item.patientName}</div>
                <div class="svc-result-file"><i class="fas fa-id-card"></i> ${item.fileNumber}</div>
            </div>
            <span class="svc-result-badge ${statusClass}" id="svc-badge-xray-${item.id}">${statusLabel}</span>
        </div>
        <div class="svc-result-info">
            <div class="svc-result-info-row">
                <i class="fas fa-radiation-alt"></i>
                <span>${item.xrayType}</span>
            </div>
            <div class="svc-result-info-row">
                <i class="fas fa-hospital-alt"></i>
                <span>${item.center}</span>
            </div>
            <div class="svc-result-info-row">
                <i class="fas fa-clock"></i>
                <span>${svcFormatDate(item.sentAt)}</span>
            </div>
        </div>
        <div class="svc-result-actions">
            <button class="svc-btn svc-btn-primary" onclick="openSvcReport('xray','${item.id}')">
                <i class="fas fa-eye"></i> فتح التقرير
            </button>
            <button class="svc-btn svc-btn-ghost" onclick="downloadSvcReport('xray','${item.id}')">
                <i class="fas fa-file-pdf"></i> تحميل PDF
            </button>
            <button class="svc-btn svc-btn-ghost" onclick="svcToggleNotes('xray','${item.id}')">
                <i class="fas fa-notes-medical"></i> إضافة ملاحظات
            </button>
            <button
                id="svc-send-btn-xray-${item.id}"
                class="svc-btn svc-btn-send svc-btn-sm"
                ${item.status === 'sent' ? 'disabled' : ''}
                onclick="svcSendToPatient('xray','${item.id}')">
                ${item.status === 'sent' ? '<i class="fas fa-check-circle"></i> تم الإرسال' : '<i class="fas fa-paper-plane"></i> إرسال للمريض'}
            </button>
        </div>
        ${buildNotesSection('xray', item.id)}
    </div>`;
}

// ── عرض النتائج في الشبكة ──
function renderSvcResults(type, filter) {
    const gridId = type === 'lab' ? 'labResultsGrid' : 'xrayResultsGrid';
    const grid = document.getElementById(gridId);
    if (!grid) return;

    const data = type === 'lab' ? svcLabResults : svcXrayResults;
    const filtered = filter === 'all' ? data : data.filter(i => i.status === filter);

    if (!filtered.length) {
        grid.innerHTML = '<div class="svc-empty"><i class="fas fa-inbox"></i><p>لا توجد نتائج</p></div>';
        return;
    }
    grid.innerHTML = filtered.map(item =>
        type === 'lab' ? buildLabCard(item) : buildXrayCard(item)
    ).join('');
}

// ── فلترة النتائج ──
function filterResults(type, filter, btn) {
    const parentSection = document.getElementById(type === 'lab' ? 'labResults' : 'xrayResults');
    if (parentSection) {
        parentSection.querySelectorAll('.svc-filter-btn').forEach(b => b.classList.remove('active'));
    }
    if (btn) btn.classList.add('active');
    renderSvcResults(type, filter);
}

// ── فتح modal التقرير ──
function openSvcReport(type, id) {
    const modal = document.getElementById('svcReportModal');
    const title = document.getElementById('svcReportModalTitle');
    const body  = document.getElementById('svcReportModalBody');
    if (!modal) return;

    if (type === 'lab') {
        const item = svcLabResults.find(i => i.id === id);
        if (!item) return;
        title.textContent = 'تقرير: ' + item.testName;
        const d = item.reportContent;
        body.innerHTML = `
            <div style="background:linear-gradient(135deg,#0ea5e9,#06b6d4);color:#fff;padding:18px 20px;border-radius:12px;margin-bottom:18px;">
                <div style="font-size:1.1rem;font-weight:800;margin-bottom:4px;">${item.patientName}</div>
                <div style="opacity:.85;font-size:0.82rem;">رقم الملف: ${item.fileNumber}</div>
                <div style="opacity:.85;font-size:0.82rem;">المختبر: ${item.labName}</div>
                <div style="opacity:.85;font-size:0.82rem;">${svcFormatDate(item.sentAt)}</div>
            </div>
            ${d.tests.map(cat => `
                <div style="margin-bottom:16px;">
                    <div style="font-weight:800;color:#0ea5e9;font-size:0.88rem;margin-bottom:10px;display:flex;align-items:center;gap:6px;"><i class="fas fa-flask"></i>${cat.category}</div>
                    ${cat.results.map(r => `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 14px;background:${r.status==='normal'?'#f0fdf4':'#fef2f2'};border-radius:9px;margin-bottom:6px;">
                            <span style="font-size:0.82rem;color:#334155;">${r.name}</span>
                            <span style="font-weight:700;font-size:0.84rem;color:${r.status==='normal'?'#10b981':'#ef4444'};">${r.value} ${r.unit}</span>
                        </div>
                    `).join('')}
                </div>
            `).join('')}`;
        // mark as reviewed
        item.status = 'reviewed';
        renderSvcResults('lab', 'all');
    } else {
        const item = svcXrayResults.find(i => i.id === id);
        if (!item) return;
        title.textContent = 'تقرير: ' + item.xrayType;
        body.innerHTML = `
            <div style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;padding:18px 20px;border-radius:12px;margin-bottom:18px;">
                <div style="font-size:1.1rem;font-weight:800;margin-bottom:4px;">${item.patientName}</div>
                <div style="opacity:.85;font-size:0.82rem;">رقم الملف: ${item.fileNumber}</div>
                <div style="opacity:.85;font-size:0.82rem;">المركز: ${item.center}</div>
                <div style="opacity:.85;font-size:0.82rem;">${svcFormatDate(item.sentAt)}</div>
            </div>
            <div style="background:#f8fafc;border-radius:12px;padding:20px;text-align:center;border:2px dashed rgba(139,92,246,.2);">
                <i class="fas fa-x-ray" style="font-size:3rem;color:#8b5cf6;margin-bottom:10px;display:block;"></i>
                <div style="font-weight:700;color:#334155;font-size:0.9rem;">${item.xrayType}</div>
                <div style="color:#64748b;font-size:0.8rem;margin-top:6px;">لعرض صورة الأشعة، يرجى مسح رمز QR أو تحميل الملف.</div>
            </div>`;
        item.status = 'reviewed';
        renderSvcResults('xray', 'all');
    }

    modal.classList.add('active');
}

function closeSvcReportModal(e) {
    if (e && e.target !== document.getElementById('svcReportModal')) return;
    const modal = document.getElementById('svcReportModal');
    if (modal) modal.classList.remove('active');
}

// إغلاق بضغط X
document.addEventListener('click', function(e) {
    if (e.target && e.target.closest && e.target.closest('.svc-report-close-btn')) {
        const modal = document.getElementById('svcReportModal');
        if (modal) modal.classList.remove('active');
    }
});

// ── تحميل PDF (وظيفة placeholder) ──
function downloadSvcReport(type, id) {
    const item = type === 'lab'
        ? svcLabResults.find(i => i.id === id)
        : svcXrayResults.find(i => i.id === id);
    if (!item) return;
    alert('سيتم تحميل تقرير: ' + (item.testName || item.xrayType) + '\n(يتطلب ربط API الخاص بالمشروع)');
}

// ── تهيئة عند فتح قسم الخدمات ──
(function() {
    const origToggle = window.toggleServiceCard;
    window.toggleServiceCard = function(cardId) {
        if (origToggle) origToggle(cardId);
        if (cardId === 'labResults')  renderSvcResults('lab',  'all');
        if (cardId === 'xrayResults') renderSvcResults('xray', 'all');
    };
})();

// معالجة زر عرض الملف الطبي من QR
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-file-btn')) {
        showMedicalRecord('p1');
    }
});

// معالجة زر توليد التقرير
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('generate-report-btn')) {
        const form = e.target.closest('.report-form');
        const patient = form.querySelector('select').value;
        const notes = form.querySelector('textarea').value;
        
        if (patient && notes.trim()) {
            e.target.textContent = 'جاري التوليد...';
            e.target.disabled = true;
            
            setTimeout(() => {
                // إنشاء تقرير وهمي
                const reportHTML = `
                    <div style="margin-top: 2rem; padding: 2rem; background: #f8f9fa; border-radius: 15px; border: 2px solid #50C878;">
                        <h3 style="color: #50C878; margin-bottom: 1rem;"><i class="fas fa-check-circle"></i> تم توليد التقرير بنجاح!</h3>
                        <div style="background: white; padding: 1.5rem; border-radius: 10px; margin-top: 1rem;">
                            <h4 style="color: #4A90E2;">التقرير الطبي - ${patient}</h4>
                            <p style="margin: 1rem 0;"><strong>التاريخ:</strong> ${new Date().toLocaleDateString('ar-SA')}</p>
                            <p style="margin: 1rem 0;"><strong>ملاحظات الطبيب:</strong></p>
                            <p style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">${notes}</p>
                            <p style="margin: 1rem 0;"><strong>التوصيات:</strong></p>
                            <p style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                - المتابعة الدورية كل أسبوعين<br>
                                - الالتزام بالأدوية الموصوفة<br>
                                - تجنب الإجهاد البدني الشديد<br>
                                - تناول غذاء صحي ومتوازن
                            </p>
                        </div>
                        <button style="margin-top: 1rem; padding: 0.8rem 2rem; background: #4A90E2; color: white; border: none; border-radius: 10px; cursor: pointer;" onclick="this.parentElement.remove()">
                            <i class="fas fa-download"></i> تحميل التقرير
                        </button>
                    </div>
                `;
                
                form.insertAdjacentHTML('afterend', reportHTML);
                e.target.textContent = 'توليد تقرير جديد';
                e.target.disabled = false;
                form.querySelector('textarea').value = '';
            }, 2000);
        }
    }
});

// إغلاق التنبيهات عند النقر خارجها
document.addEventListener('click', function(e) {
    const notificationsPanel = document.getElementById('notificationsPanel');
    const notificationBtn = document.querySelector('.notification-btn');
    
    if (notificationsPanel && notificationsPanel.classList.contains('active')) {
        if (!notificationsPanel.contains(e.target) && !notificationBtn.contains(e.target)) {
            notificationsPanel.classList.remove('active');
        }
    }
});

// تهيئة الحركات عند تحميل الصفحة
window.addEventListener('load', function() {
    const cards = document.querySelectorAll('.main-card, .ai-card, .message-card, .service-card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

console.log('تم تحميل التطبيق بنجاح! 🎉');
console.log('جميع الميزات التفاعلية جاهزة للاستخدام');

// ==================== وظائف الإعدادات ====================

// بيانات المرضى
const patientsData = [
    { id: 1, name: 'سارة أحمد', age: 28, lastVisit: '2024-02-10', condition: 'متابعة دورية', avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%23FF6B9D\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eس%3C/text%3E%3C/svg%3E' },
    { id: 2, name: 'محمد علي', age: 45, lastVisit: '2024-02-08', condition: 'علاج ضغط الدم', avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%234A90E2\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eم%3C/text%3E%3C/svg%3E' },
    { id: 3, name: 'فاطمة حسن', age: 35, lastVisit: '2024-02-05', condition: 'فحص شامل', avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%2350E3C2\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eف%3C/text%3E%3C/svg%3E' },
    { id: 4, name: 'عبدالله محمود', age: 52, lastVisit: '2024-02-03', condition: 'مراجعة تحاليل', avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%23FFA500\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eع%3C/text%3E%3C/svg%3E' },
    { id: 5, name: 'ليلى عثمان', age: 31, lastVisit: '2024-01-28', condition: 'استشارة طبية', avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%239B59B6\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eل%3C/text%3E%3C/svg%3E' },
    { id: 6, name: 'خالد يوسف', age: 39, lastVisit: '2024-01-25', condition: 'علاج مزمن', avatar: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'50\' height=\'50\'%3E%3Ccircle cx=\'25\' cy=\'25\' r=\'25\' fill=\'%23E74C3C\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'white\' font-size=\'20\' font-family=\'Arial\'%3Eخ%3C/text%3E%3C/svg%3E' }
];

// فتح نافذة تعديل الملف الشخصي
function openEditProfile() {
    document.getElementById('editProfileModal').classList.add('active');
}

// فتح نافذة تغيير كلمة المرور
function openChangePassword() {
    document.getElementById('changePasswordModal').classList.add('active');
}

// فتح نافذة مرضاي
function openMyPatients() {
    document.getElementById('myPatientsModal').classList.add('active');
    loadPatients();
}

// إغلاق النوافذ
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// حفظ الملف الشخصي
function saveProfile() {
    alert('✅ تم حفظ التغييرات بنجاح!');
    closeModal('editProfileModal');
}

// تغيير كلمة المرور
function changePassword() {
    alert('✅ تم تغيير كلمة المرور بنجاح!');
    closeModal('changePasswordModal');
}

// تحميل قائمة المرضى
function loadPatients() {
    const list = document.getElementById('patientsList');
    list.innerHTML = patientsData.map(p => `
        <div class="patient-item">
            <img src="${p.avatar}" alt="${p.name}">
            <div class="patient-info">
                <h4>${p.name}</h4>
                <p>العمر: ${p.age} • آخر زيارة: ${p.lastVisit}</p>
                <p style="color: var(--primary-blue); margin-top: 0.2rem; font-size: 0.85rem;">${p.condition}</p>
            </div>
            <div class="patient-actions">
                <button class="action-btn edit" onclick="editPatient(${p.id})"><i class="fas fa-edit"></i></button>
                <button class="action-btn delete" onclick="deletePatient(${p.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');
}

// البحث في المرضى
function searchPatients(query) {
    const filtered = patientsData.filter(p => 
        p.name.toLowerCase().includes(query.toLowerCase()) ||
        p.condition.toLowerCase().includes(query.toLowerCase())
    );
    
    const list = document.getElementById('patientsList');
    if (filtered.length === 0) {
        list.innerHTML = '<p style="text-align: center; padding: 2rem; color: var(--gray);">لا توجد نتائج</p>';
    } else {
        list.innerHTML = filtered.map(p => `
            <div class="patient-item">
                <img src="${p.avatar}" alt="${p.name}">
                <div class="patient-info">
                    <h4>${p.name}</h4>
                    <p>العمر: ${p.age} • آخر زيارة: ${p.lastVisit}</p>
                    <p style="color: var(--primary-blue); margin-top: 0.2rem; font-size: 0.85rem;">${p.condition}</p>
                </div>
                <div class="patient-actions">
                    <button class="action-btn edit" onclick="editPatient(${p.id})"><i class="fas fa-edit"></i></button>
                    <button class="action-btn delete" onclick="deletePatient(${p.id})"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `).join('');
    }
}

// تعديل مريض
function editPatient(id) {
    const patient = patientsData.find(p => p.id === id);
    alert(`تعديل بيانات: ${patient.name}`);
}

// حذف مريض
function deletePatient(id) {
    const patient = patientsData.find(p => p.id === id);
    if (confirm(`هل أنت متأكد من حذف: ${patient.name}؟`)) {
        const index = patientsData.findIndex(p => p.id === id);
        patientsData.splice(index, 1);
        loadPatients();
        alert('✅ تم الحذف بنجاح!');
    }
}

// تسجيل الخروج
function logout() {
    if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
        alert('تم تسجيل الخروج بنجاح!');
        setTimeout(() => window.location.reload(), 1000);
    }
}

/* ================================================================
   BOOKING SYSTEM — نظام إدارة الحجوزات للطبيب
   ================================================================ */

/**
 * تحميل قائمة الحجوزات المعلقة وعرضها
 */
function loadAppointments() {
  var container = document.getElementById("appointmentsContainer");
  if (!container) return;

  container.innerHTML = '<p style="text-align:center;color:#888;">جاري التحميل...</p>';

  fetch("get_appointments.php?status=pending")
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (!Array.isArray(data) || data.length === 0) {
        container.innerHTML = '<p style="text-align:center;color:#888;padding:20px;">لا توجد حجوزات معلقة حالياً</p>';
        return;
      }

      var html = "";
      data.forEach(function(app) {
        var caseLabel = app.case_type ? app.case_type : '—';
        var caseBadge = '';
        if (app.case_type === 'مستعجلة') caseBadge = 'style="color:#dc2626;font-weight:bold;"';
        else if (app.case_type === 'مزمنة') caseBadge = 'style="color:#f59e0b;font-weight:bold;"';

        var createdDate = app.created_at ? app.created_at.substring(0, 16) : '—';

        html += `
          <div class="appointment-card" id="appt_card_${app.id}">
            <div class="booking-patient">
              <i class="fas fa-user-circle" style="font-size:28px;color:#3b82f6;margin-left:10px;"></i>
              <div>
                <h4 style="margin:0 0 2px;">${escapeHtml(app.patient_name)}</h4>
                <p style="margin:0;font-size:13px;color:#555;">${escapeHtml(app.phone)}</p>
                <p style="margin:2px 0 0;" ${caseBadge}>نوع الحالة: ${escapeHtml(caseLabel)}</p>
                <p style="margin:2px 0 0;font-size:12px;color:#999;">طُلب في: ${createdDate}</p>
              </div>
            </div>

            <div class="booking-datetime" style="margin:10px 0;">
              <label style="font-size:13px;color:#555;">تاريخ الموعد:</label>
              <input type="date" id="appt_date_${app.id}" style="margin:4px 6px;">
              <label style="font-size:13px;color:#555;">الساعة:</label>
              <input type="time" id="appt_time_${app.id}" style="margin:4px 6px;">
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <button
                onclick="confirmAppointment(${app.id})"
                style="background:#16a34a;color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:14px;">
                ✅ قبول وتأكيد
              </button>
              <button
                onclick="rejectAppointment(${app.id})"
                style="background:#dc2626;color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:14px;">
                ❌ رفض
              </button>
            </div>
          </div>
        `;
      });

      container.innerHTML = html;
    })
    .catch(function() {
      container.innerHTML = '<p style="color:#dc2626;text-align:center;">خطأ في تحميل الحجوزات</p>';
    });
}

/**
 * helper: escape HTML لمنع XSS
 */
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * قبول الموعد وتحديد التاريخ والوقت
 */
function confirmAppointment(id) {
  // FIX: البحث عن inputs بالـ ID الصحيح (appt_date_X) أو القديم (dateX) للتوافق مع PHP-rendered cards
  var dateInput = document.getElementById("appt_date_" + id) || document.getElementById("date" + id);
  var timeInput = document.getElementById("appt_time_" + id) || document.getElementById("time" + id);

  if (!dateInput || !timeInput) {
    alert("❌ خطأ: لم يُعثر على حقول التاريخ/الوقت، يرجى تحديث الصفحة.");
    return;
  }

  var date = (dateInput.value || "").trim();
  var time = (timeInput.value || "").trim();

  // FIX: رسائل validation واضحة
  if (!date) {
    alert("⚠️ يرجى تحديد تاريخ الموعد قبل التأكيد.");
    dateInput.focus();
    return;
  }
  if (!time) {
    alert("⚠️ يرجى تحديد ساعة الموعد قبل التأكيد.");
    timeInput.focus();
    return;
  }

  fetch("accept_appointment.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: id, action: "confirm", date: date, time: time })
  })
  .then(function(res) {
    if (!res.ok) throw new Error("HTTP " + res.status);
    return res.json();
  })
  .then(function(data) {
    if (data.success) {
      var card = document.getElementById("appt_card_" + id);
      // FIX: البحث أيضاً في booking-item (PHP-rendered cards)
      if (!card) {
        var items = document.querySelectorAll(".booking-item");
        items.forEach(function(item) {
          if (item.querySelector("#appt_date_" + id) || item.querySelector("#date" + id)) {
            card = item;
          }
        });
      }
      if (card) {
        card.style.opacity = "0.4";
        card.innerHTML = '<p style="text-align:center;padding:12px;color:#16a34a;">✅ تم تأكيد الموعد وإرسال إشعار للمريض</p>';
        setTimeout(function() { loadAppointments(); }, 1500);
      } else {
        loadAppointments();
      }
    } else {
      alert(data.message || "حدث خطأ في التأكيد");
    }
  })
  .catch(function(err) {
    console.error("confirmAppointment error:", err);
    alert("❌ خطأ في الاتصال بالخادم، حاول مرة أخرى.");
  });
}

/**
 * رفض طلب الموعد
 */
function rejectAppointment(id) {
  if (!confirm("هل أنت متأكد من رفض هذا الطلب؟")) return;

  fetch("accept_appointment.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: id, action: "reject" })
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    if (data.success) {
      var card = document.getElementById("appt_card_" + id);
      if (card) {
        card.style.opacity = "0.4";
        card.innerHTML = '<p style="text-align:center;padding:12px;color:#dc2626;">❌ تم رفض الطلب وإرسال إشعار للمريض</p>';
        setTimeout(function() { loadAppointments(); }, 1500);
      } else {
        loadAppointments();
      }
    } else {
      alert(data.message || "حدث خطأ");
    }
  })
  .catch(function() { alert("خطأ في الاتصال بالخادم"); });
}

// تحميل الحجوزات عند فتح الصفحة
loadAppointments();
function markCompleted(id) {
    fetch("update_appointment_status.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id=${id}&status=completed`
    })
    .then(res => res.text())
    .then(() => location.reload());
}

function markNoShow(id) {
    fetch("update_appointment_status.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id=${id}&status=no_show`
    })
    .then(res => res.text())
    .then(() => location.reload());
}
function openRescheduleModal(id) {
    document.getElementById("reschedule_id").value = id;
    document.getElementById("reschedule_date").value = "";
    document.getElementById("reschedule_time").value = "";
    document.getElementById("rescheduleModal").style.display = "flex";
}

function closeRescheduleModal() {
    document.getElementById("rescheduleModal").style.display = "none";
}

function saveReschedule() {
    const id = document.getElementById("reschedule_id").value;
    const date = document.getElementById("reschedule_date").value;
    const time = document.getElementById("reschedule_time").value;

    if (!date || !time) {
        alert("اختر التاريخ والوقت");
        return;
    }

    fetch("reschedule_appointment.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id=${id}&date=${date}&time=${time}`
    })
    .then(res => res.text())
    .then(() => location.reload());
}
function openPatientFile(patientId){
    document.getElementById("patientFileModal").style.display = "flex";
}

function closePatientFile(){
    document.getElementById("patientFileModal").style.display = "none";
}
function saveMedicalRecord() {



const data = new FormData();

let _mpid = (document.getElementById("medical_patient_id") || {}).value || "";
if (!_mpid) {
    const _expItem = document.querySelector('#todayPatients .patient-item.expanded');
    if (_expItem) _mpid = (_expItem.getAttribute('data-patient-id') || "").trim();
}
const getVal = (id) => {
    let el = document.getElementById(id);
    if (!el && _mpid) el = document.getElementById("mirror_" + id + "_" + _mpid);
    return el ? el.value : "";
};

data.append("patient_id", _mpid);
data.append("full_name", getVal("full_name"));
data.append("gender", getVal("gender"));
data.append("birth_info", getVal("birth_info"));
data.append("marital_status", getVal("marital_status"));
data.append("job", getVal("job"));
data.append("address", getVal("address"));
data.append("phone", getVal("phone"));

data.append("reason_exam", getVal("reason_exam"));
data.append("symptoms", getVal("symptoms"));

data.append("blood_pressure", getVal("blood_pressure"));
data.append("blood_sugar", getVal("blood_sugar"));
data.append("heart_rate", getVal("heart_rate"));
data.append("temperature", getVal("temperature"));
data.append("oxygen_level", getVal("oxygen_level"));

data.append("chronic_patient", getVal("chronic_patient"));
data.append("chronic_family", getVal("chronic_family"));

data.append("medical_tests", getVal("medical_tests"));
data.append("radiology", getVal("radiology"));

data.append("prescription", getVal("prescription"));

data.append("needs_appointment", getVal("needs_appointment"));
data.append("next_appointment_date", getVal("next_appointment_date"));
data.append("next_appointment_time", getVal("next_appointment_time"));

data.append("last_period_date", getVal("last_period_date"));
data.append("expected_delivery_date", getVal("expected_delivery_date"));
data.append("preg_blood_type", getVal("preg_blood_type"));
data.append("pregnancies_count", getVal("pregnancies_count"));
data.append("births_count", getVal("births_count"));
data.append("miscarriages_count", getVal("miscarriages_count"));
data.append("c_sections_count", getVal("c_sections_count"));
data.append("preg_chronic_diseases", getVal("preg_chronic_diseases"));
data.append("father_status", getVal("father_status"));
data.append("consanguinity", getVal("consanguinity"));
data.append("pregnancy_notes", getVal("pregnancy_notes"));

data.append("preg_weight", getVal("preg_weight"));
data.append("preg_blood_pressure", getVal("preg_blood_pressure"));
data.append("preg_sugar_level", getVal("preg_sugar_level"));
data.append("fetal_heartbeat", getVal("fetal_heartbeat"));
data.append("fetal_movement", getVal("fetal_movement"));
data.append("fetal_weight", getVal("fetal_weight"));
data.append("fetal_position", getVal("fetal_position"));
data.append("echo_notes", getVal("echo_notes"));
data.append("followup_notes", getVal("followup_notes"));
    fetch("save_doctor_record.php", {
        method: "POST",
        body: data
    })
    .then(res => res.text())
    .then(msg => {
        var _ok = false, _mrId = 0, _err = '';
        var _mp = _mpid;
        try {
            var _j = JSON.parse(msg);
            if (_j && _j.success) { _ok = true; _mrId = parseInt(_j.medical_record_id, 10) || 0; }
            else if (_j && _j.message) { _err = _j.message; }
        } catch (e) { _err = msg; }

        // إذا فشلت أي خطوة (حفظ/أرشفة) → لا تعتبرها ناجحة، أظهر الخطأ ولا تحذف المريض
        if (!_ok) {
            alert("تعذّر حفظ الملف: " + (_err || "خطأ غير معروف"));
            return;
        }

        alert("تم حفظ الملف الطبي بنجاح ✅");
        closePatientFile();

        // أُرشف وأُكمل موعده على الخادم → إخفاء المريض من "مرضى اليوم" وتحديث العدّاد فوراً (بدون Refresh)
        try {
            var _it = document.querySelector('#todayPatients .patient-item[data-patient-id="' + _mp + '"]');
            if (_it) {
                _it.remove();
                var _cnt = document.querySelector('.main-card[onclick*="todayPatients"] .card-count');
                if (_cnt) {
                    var _n = parseInt((_cnt.textContent || '').trim(), 10);
                    if (!isNaN(_n) && _n > 0) _cnt.textContent = (_n - 1);
                }
            }
        } catch (e) {}
    });
}
function nextStep(step){
    document.querySelectorAll(".medical-step").forEach(el=>{
        el.classList.remove("active-step");
    });

    document.getElementById("step"+step).classList.add("active-step");
}

function prevStep(step){
    document.querySelectorAll(".medical-step").forEach(el=>{
        el.classList.remove("active-step");
    });

    document.getElementById("step"+step).classList.add("active-step");
}

document.addEventListener("change", function(e){
    if(e.target.id === "needs_appointment"){
        const box = document.getElementById("appointmentFields");

        if(e.target.value === "yes"){
            box.style.display = "block";
        }else{
            box.style.display = "none";
        }
    }
});
function printPrescription() {
    const patientName = document.getElementById("rx_patient_name").value;
    const rxDate = document.getElementById("rx_date").value;
    const medicines = document.getElementById("prescription").value;
    const notes = document.getElementById("doctor_notes").value;
    const signature = document.getElementById("doctor_signature").value;

    const doctorName =
        document.querySelector(".doctor-box h2")?.innerText || "";
    const specialty =
        document.querySelector(".doctor-box p:nth-of-type(1)")?.innerText || "";
    const license =
        document.querySelector(".doctor-box p:nth-of-type(2)")?.innerText || "";
    const address =
        document.querySelector(".doctor-box p:nth-of-type(3)")?.innerText || "";

    const win = window.open("", "_blank");

    win.document.write(`
        <html>
        <head>
            <title>Prescription</title>
            <style>
                body{
                    font-family:Arial,sans-serif;
                    background:white;
                    margin:0;
                    padding:40px;
                    direction:rtl;
                }

                .paper{
                    max-width:900px;
                    margin:auto;
                    line-height:2;
                }

                .top{
                    display:flex;
                    justify-content:space-between;
                    margin-bottom:30px;
                }

                .doctor{
                    width:50%;
                }

                .doctor h2{
                    color:#0d47a1;
                    margin:0;
                }

                .patient{
                    width:35%;
                }

                .title{
                    text-align:center;
                    font-size:42px;
                    color:#0d47a1;
                    margin:30px 0;
                    letter-spacing:4px;
                    font-weight:bold;
                }

                .content{
                    min-height:250px;
                    white-space:pre-wrap;
                    font-size:20px;
                }

                .notes{
                    margin-top:30px;
                    white-space:pre-wrap;
                }

                .sign{
                    margin-top:70px;
                    text-align:left;
                    font-weight:bold;
                    font-size:22px;
                }

                hr{
                    margin:25px 0;
                }
            </style>
        </head>
        <body>
            <div class="paper">

                <div class="top">
                    <div class="doctor">
                        <h2>${doctorName}</h2>
                        <div>${specialty}</div>
                        <div>${license}</div>
                        <div>${address}</div>
                    </div>

                    <div class="patient">
                        <div><b>اسم المريض:</b> ${patientName}</div>
                        <br>
                        <div><b>التاريخ:</b> ${rxDate}</div>
                    </div>
                </div>

                <hr>

                <div class="title">ORDONNANCE</div>

                <div class="content">${medicines}</div>

                <div class="notes">${notes}</div>

                <div class="sign">${signature}</div>

            </div>
        </body>
        </html>
    `);

    win.document.close();
    win.focus();
    win.print();
}
function printMedicalRecord() {
    const modal = document.querySelector(".patient-modal-content").cloneNode(true);

    // نعمر القيم المكتوبة
    modal.querySelectorAll("input, textarea, select").forEach(el => {
        if (el.tagName === "TEXTAREA") {
            el.innerHTML = el.value;
        } else {
            el.setAttribute("value", el.value);
        }
    });

    const content = modal.innerHTML;

    const win = window.open("", "", "width=1000,height=800");

    win.document.write(`
        <html>
        <head>
            <title>Medical Record</title>
            <style>
                body{
                    font-family:Arial;
                    padding:35px;
                    line-height:1.8;
                }

                input, textarea, select{
                    width:100%;
                    padding:10px;
                    border:1px solid #ccc;
                    border-radius:8px;
                    margin-top:6px;
                    margin-bottom:14px;
                }

                h2,h3{
                    color:#0f766e;
                }

                button, .close-patient-modal{
                    display:none;
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);

    win.document.close();
    win.print();
}
function savePrescription() {
    const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value : "";
    };

    const data = new FormData();
    data.append("patient_id", getVal("medical_patient_id"));
    data.append("patient_name", getVal("rx_patient_name"));
    data.append("rx_date", getVal("rx_date"));
    data.append("medicines", getVal("prescription"));
    data.append("notes", getVal("doctor_notes"));
    data.append("signature", getVal("doctor_signature"));

    fetch("save_prescription.php", {
        method: "POST",
        body: data
    })
    .then(res => res.text())
    .then(msg => {
        console.log(msg);
        alert(msg);
    })
    .catch(err => console.error(err));
}
// ══════════════════════════════════════════
// ADD PATIENT — Inline Form (MedChifaGiz)
// Works for all specialties
// ══════════════════════════════════════════

function toggleAddPatientForm() {
    const section = document.getElementById('addPatientFormSection');
    if (!section) return;

    if (section.style.display === 'none' || section.style.display === '') {
        // أغلق أي panel مفتوح أولاً
        if (typeof closeAllCardContents === 'function') closeAllCardContents();
        else if (typeof closeCardContent === 'function') closeCardContent();

        section.style.display = 'block';
        section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // افتح القسم الأول تلقائياً
        const firstSec = section.querySelector('.pif-section');
        if (firstSec && !firstSec.classList.contains('pif-open')) {
            apfToggleSection(firstSec);
        }
    } else {
        section.style.display = 'none';
    }
}

/* ──────────────────────────────────────────────
   APF: Toggle أقسام الأكورديون (إضافة مريض)
────────────────────────────────────────────── */
function apfToggleSection(sectionEl) {
    const isOpen = sectionEl.classList.contains('pif-open');
    // أغلق كل الأقسام
    document.querySelectorAll('#addPatientFormSection .pif-section').forEach(s => {
        s.classList.remove('pif-open');
    });
    // افتح المطلوب إذا كان مغلقاً
    if (!isOpen) {
        sectionEl.classList.add('pif-open');
        setTimeout(() => {
            sectionEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 80);
    }
}

/* عرض/إخفاء حقول الموعد */
function apfToggleApptFields(val) {
    const fields = document.getElementById('apf_appt_fields');
    if (fields) fields.style.display = (val === 'yes') ? 'block' : 'none';
}

/* ──────────────────────────────────────────────
   APF: إعادة تعيين جميع الحقول
────────────────────────────────────────────── */
function apfResetAll() {
    const ids = [
        // المعلومات الشخصية
        'apf_full_name','apf_birth_info','apf_gender','apf_age',
        'apf_marital_status','apf_job','apf_address','apf_phone',
        // الفحص والأعراض
        'apf_reason_exam','apf_symptoms',
        'apf_blood_pressure','apf_blood_sugar','apf_heart_rate','apf_temperature','apf_oxygen_level',
        'apf_chronic_patient','apf_chronic_family',
        // متابعة الحمل (كاملة مطابقة buildPregnancyContent)
        'apf_last_period_date','apf_expected_delivery_date','apf_preg_blood_type',
        'apf_pregnancies_count','apf_births_count','apf_miscarriages_count',
        'apf_c_sections_count','apf_father_status','apf_preg_chronic_diseases',
        'apf_consanguinity','apf_pregnancy_notes',
        'apf_preg_weight','apf_preg_blood_pressure','apf_preg_sugar_level',
        'apf_fetal_heartbeat','apf_fetal_movement','apf_fetal_weight',
        'apf_fetal_position','apf_echo_notes','apf_followup_notes',
        // الفحوصات التكميلية
        'apf_medical_tests','apf_radiology',
        // Fiche de traitement (مطابق buildFicheContent)
        'apf_fiche_diagnostic','apf_fiche_medications',
        // الوصفة الطبية (مطابق buildRxContent)
        'apf_rx_patient_name','apf_rx_date','apf_rx_prescription','apf_rx_doctor_notes',
        // المواعيد القادمة (مطابق pif-sec-6)
        'apf_needs_appointment','apf_next_appointment_date','apf_next_appointment_time'
    ];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });
    // إعادة تاريخ الوصفة لليوم
    const rxDate = document.getElementById('apf_rx_date');
    if (rxDate) rxDate.value = new Date().toISOString().split('T')[0];
    // إخفاء حقول الموعد
    apfToggleApptFields('no');
    // إعادة status badges
    document.querySelectorAll('#addPatientFormSection .pif-sec-status').forEach(b => {
        b.textContent = 'فارغ';
        b.classList.remove('done');
    });
}
/* ──────────────────────────────────────────────
   APF: حفظ الملف الطبي في الأرشيف
────────────────────────────────────────────── */
function apfSaveRecord() {
    const name     = (document.getElementById('apf_full_name')   || {}).value?.trim();
    const age      = (document.getElementById('apf_age')         || {}).value?.trim();
    const gender   = (document.getElementById('apf_gender')      || {}).value?.trim();
    const reason   = (document.getElementById('apf_reason_exam') || {}).value?.trim();
    const phone    = (document.getElementById('apf_phone')       || {}).value?.trim();

    if (!name || !age || !gender || !reason) {
        showAddPatientToast('⚠️ يرجى تعبئة الحقول الإلزامية: الاسم، العمر، الجنس، سبب الفحص', 'warn');
        // افتح قسم المعلومات الشخصية
        const sec = document.getElementById('apf-sec-info');
        if (sec) apfToggleSection(sec);
        return;
    }

    const btn = document.getElementById('apfSaveBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left:6px;"></i> جاري الحفظ...'; }

    const formData = new FormData();
    formData.append('patient_name', name);
    formData.append('age', age);
    formData.append('gender', gender);
    formData.append('phone', phone || '');
    const email = (document.getElementById('apf_email') || {}).value?.trim();
formData.append('email', email || '');
    formData.append('reason', reason);

    // بيانات إضافية
    const extras = [
        // معلومات شخصية إضافية
        'apf_birth_info','apf_marital_status','apf_job','apf_address',
        'apf_admission_date','apf_residency_status',
        // فحص وأعراض
        'apf_symptoms','apf_blood_pressure','apf_blood_sugar','apf_heart_rate',
        'apf_temperature','apf_oxygen_level','apf_chronic_patient','apf_chronic_family',
        // متابعة الحمل كاملة
        'apf_last_period_date','apf_expected_delivery_date','apf_preg_blood_type',
        'apf_pregnancies_count','apf_births_count','apf_miscarriages_count',
        'apf_c_sections_count','apf_father_status','apf_preg_chronic_diseases',
        'apf_consanguinity','apf_pregnancy_notes',
        'apf_preg_weight','apf_preg_blood_pressure','apf_preg_sugar_level',
        'apf_fetal_heartbeat','apf_fetal_movement','apf_fetal_weight',
        'apf_fetal_position','apf_echo_notes','apf_followup_notes',
        // فحوصات تكميلية
        'apf_medical_tests','apf_radiology',
        // Fiche de traitement
        'apf_fiche_diagnostic','apf_fiche_medications',
        // وصفة طبية
        'apf_rx_patient_name','apf_rx_date','apf_rx_prescription','apf_rx_doctor_notes',
        // مواعيد
        'apf_needs_appointment','apf_next_appointment_date','apf_next_appointment_time'
    ];
    extras.forEach(id => {
        const el = document.getElementById(id);
        if (el) formData.append(id.replace('apf_', ''), el.value || '');
    });

    fetch('add_patient_today.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save" style="margin-left:6px;"></i> حفظ في الأرشيف'; }

        if (data.success) {
            // ✅ FIX 1: تعيين _apfCurrentRecordId فور نجاح الحفظ
            // حتى تعمل apfSaveFicheTraitement / apfSaveRapportMedical بـ ID حقيقي
            var savedRecordId = data.record_id || data.patient_id || 0;
            window._apfCurrentRecordId = savedRecordId;

            // ✅ FIX 1b: إذا كانت هناك بيانات فيش محفوظة مؤقتاً → أرسلها الآن
            var pendingFiche = sessionStorage.getItem('apf_fiche_pending');
            if (pendingFiche && savedRecordId) {
                try {
                    var fp = JSON.parse(pendingFiche);
                    sessionStorage.removeItem('apf_fiche_pending');
                    var ficheBtn = document.getElementById('apf-fiche-save-btn');
                    if (typeof window._doSaveFicheInternal === 'function') {
                        window._doSaveFicheInternal(savedRecordId, fp.fiche_diagnostic || '', fp.fiche_medications || '', ficheBtn, 'apf-fiche-save-btn');
                    } else {
                        var fd2 = new FormData();
                        fd2.append('action', 'save_fiche');
                        fd2.append('medical_record_id', savedRecordId);
                        fd2.append('fiche_diagnostic', fp.fiche_diagnostic || '');
                        fd2.append('fiche_medications', fp.fiche_medications || '');
                        fetch(window.FICHE_SAVE_URL || 'fiche_traitement_api.php', {
                            method: 'POST', body: fd2, credentials: 'same-origin'
                        }).catch(function(e){ console.warn('[APF] auto-save fiche error:', e); });
                    }
                } catch(e) { console.warn('[APF] pendingFiche parse error:', e); }
            }

            // ✅ FIX 1c: إذا كانت هناك بيانات تقرير محفوظة مؤقتاً → أرسلها الآن
            var pendingRapport = sessionStorage.getItem('apf_rapport_new');
            if (pendingRapport && savedRecordId) {
                try {
                    var rp = JSON.parse(pendingRapport);
                    sessionStorage.removeItem('apf_rapport_new');
                    var fd3 = new FormData();
                    fd3.append('action', 'save_rapport_medical');
                    fd3.append('patient_id', savedRecordId);
                    fd3.append('rapport_date', rp.rapport_date || '');
                    fd3.append('rapport_patient', rp.rapport_patient || '');
                    fd3.append('rapport_age', rp.rapport_age || '');
                    fd3.append('rapport_doctor', rp.rapport_doctor || '');
                    fd3.append('rapport_content', rp.rapport_content || '');
                    fetch(window.RAPPORT_SAVE_URL || 'rapport_medical_api.php', {
                        method: 'POST', body: fd3, credentials: 'same-origin'
                    }).catch(function(e){ console.warn('[APF] auto-save rapport error:', e); });
                } catch(e) { console.warn('[APF] pendingRapport parse error:', e); }
            }

            // إبقاء النموذج مفتوحاً وتفريغ الحقول فقط — جاهز للمريض التالي
            apfResetAll();
            // إعادة فتح القسم الأول تلقائياً بعد التفريغ
            const firstSec = document.querySelector('#addPatientFormSection .pif-section');
            if (firstSec) {
                setTimeout(() => apfToggleSection(firstSec), 80);
            }

            // ✅ إصلاح Bug "مرضى اليوم": يُحتسب المريض ضمن مرضى اليوم فقط إذا كان
            // تاريخ الدخول (admission_date) = تاريخ اليوم. ولا يعتمد إطلاقاً على
            // تاريخ الوصفة أو التحاليل أو الأشعة أو fiche أو أي تاريخ آخر في النموذج.
            const _admDateToday = (document.getElementById('apf_admission_date') || {}).value || '';
            const _todayStr     = new Date().toISOString().split('T')[0];
            if (false) { // ✅ المريض المُضاف عبر "إضافة مريض" لا يُنقل أبداً إلى مرضى اليوم، ولا يعتمد على تاريخ الدخول

            // تحديث عداد مرضى اليوم
            document.querySelectorAll('.main-card').forEach(card => {
                const h3 = card.querySelector('h3');
                if (h3 && h3.getAttribute('data-ar') === 'مرضى اليوم') {
                    const counter = card.querySelector('.card-count');
                    if (counter) counter.textContent = parseInt(counter.textContent || '0') + 1;
                }
            });

            // إضافة المريض لقائمة مرضى اليوم إذا كانت مفتوحة
            const patientsList = document.querySelector('#todayPatients .patients-list');
            if (patientsList) {
                const emptyMsg = patientsList.querySelector('p');
                if (emptyMsg) emptyMsg.remove();
                const now = new Date();
                const timeStr = now.toTimeString().slice(0,5);
                const admissionDate = (document.getElementById('apf_admission_date') || {}).value || '';
                const residencyStatus = (document.getElementById('apf_residency_status') || {}).value || '';
                const admissionInfo = admissionDate ? ` • دخول: ${admissionDate}` : '';
                const residencyBadge = residencyStatus ? ` • <span style="background:rgba(14,165,233,0.1);color:var(--primary);padding:2px 8px;border-radius:12px;font-size:0.76rem;font-weight:600;">${escapeHtml ? escapeHtml(residencyStatus) : residencyStatus}</span>` : '';
                const div = document.createElement('div');
                div.className = 'patient-item';
                div.style.animation = 'fadeIn 0.4s ease';
                div.innerHTML = `
                    <div class="patient-info">
                        <h4 style="color:var(--primary);">${escapeHtml ? escapeHtml(name) : name}</h4>
                        <p class="appointment-time">${timeStr} • ${escapeHtml ? escapeHtml(reason) : reason}${admissionInfo}${residencyBadge}</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <span style="background:rgba(14,165,233,0.1);color:var(--primary);padding:6px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;">🕐 في الانتظار</span>
                    </div>
                `;
                patientsList.appendChild(div);
                // إعادة بناء patient-items الجديدة إذا كان patient_inline مُحمّلاً
                if (typeof init === 'function') init();
            }
            } // ✅ نهاية شرط مرضى اليوم (admission_date == اليوم)

            // ✅ إصلاح: تحديث عداد + قائمة المرضى المقيمين إذا كانت الحالة = مقيم
            const savedResidency = (document.getElementById('apf_residency_status') || {}).value || '';
            const savedName      = name;
            const savedPhone     = (document.getElementById('apf_phone')          || {}).value || '';
            const savedAddress   = (document.getElementById('apf_address')        || {}).value || '';
            const savedAdmDate   = (document.getElementById('apf_admission_date') || {}).value || '';

            if (savedResidency === 'مقيم') {
                // تحديث عداد كارت "المرضى المقيمون"
                document.querySelectorAll('.main-card').forEach(card => {
                    const h3 = card.querySelector('h3');
                    if (h3 && h3.getAttribute('data-ar') === 'المرضى المقيمون') {
                        const counter = card.querySelector('.card-count');
                        if (counter) counter.textContent = parseInt(counter.textContent || '0') + 1;
                    }
                });

                // إضافة المريض لقائمة "المرضى المقيمون" إذا كانت مفتوحة
                const inpatientsList = document.querySelector('#inpatients .patients-list');
                if (inpatientsList) {
                    const emptyMsg = inpatientsList.querySelector('p');
                    if (emptyMsg) emptyMsg.remove();
                    const newId = (data && data.patient_id) ? data.patient_id : 0;
                    const div = document.createElement('div');
                    div.className = 'patient-item';
                    div.style.animation = 'fadeIn 0.4s ease';
                    div.setAttribute('data-residency-status', 'مقيم');
                    div.setAttribute('data-admission-date', savedAdmDate);
                    div.innerHTML = `
                        <div class="patient-info">
                            <h4 style="color:var(--primary);">${savedName}</h4>
                            <p class="appointment-time" style="font-size:0.78rem;color:#64748b;display:flex;flex-wrap:wrap;gap:10px;margin-top:3px;">
                                ${savedAdmDate ? `<span><i class="fas fa-calendar-check" style="color:#0ea5e9;margin-left:3px;"></i> تاريخ الدخول: ${savedAdmDate}</span>` : ''}
                                ${savedPhone   ? `<span><i class="fas fa-phone" style="color:#10b981;margin-left:3px;"></i> ${savedPhone}</span>` : ''}
                                ${savedAddress ? `<span><i class="fas fa-map-marker-alt" style="color:#f59e0b;margin-left:3px;"></i> ${savedAddress}</span>` : ''}
                            </p>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button onclick="openPatientFile(${newId})"
                                    style="background:linear-gradient(135deg,#0ea5e9,#06b6d4);color:white;border:none;padding:7px 14px;border-radius:8px;cursor:pointer;font-size:0.82rem;font-weight:600;transition:0.2s;font-family:'Cairo',sans-serif;">
                                <i class="fas fa-folder-open" style="margin-left:5px;"></i> فتح الملف
                            </button>
                        </div>
                    `;
                    inpatientsList.appendChild(div);
                }
            }

            showAddPatientToast('✅ تم حفظ ملف المريض في الأرشيف بنجاح', 'success');
        } else {
            showAddPatientToast('❌ ' + (data.message || 'حدث خطأ، حاول مرة أخرى'), 'error');
        }
    })
    .catch(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save" style="margin-left:6px;"></i> حفظ في الأرشيف'; }
        showAddPatientToast('❌ تعذر الاتصال بالخادم', 'error');
    });
}

/* دالة saveNewPatient القديمة — محتفظ بها للتوافق */
/* ═══════════════════════════════════════════════════════════
   APF: دوال مطابقة لـ patient_inline.js — معزولة في كارد إضافة مريض
═══════════════════════════════════════════════════════════ */

function apfGetVal(id) {
    const el = document.getElementById(id);
    return el ? el.value.trim() : '';
}

/* 💾 حفظ الوصفة (stub — تُحفظ مع الملف) */
function apfSavePrescription() {
    if (!apfGetVal('apf_rx_prescription')) {
        showAddPatientToast('⚠️ يرجى كتابة الأدوية أولاً', 'warn');
        return;
    }
    showAddPatientToast('💾 تم حفظ الوصفة — ستُرسل مع الملف عند الحفظ النهائي', 'success');
}

/* 📤 إرسال الوصفة للصيدلي (stub) */
function apfSendPrescriptionToPharmacy() {
    showAddPatientToast('📤 سيتم ربط إرسال الوصفة للصيدلي لاحقاً', 'warn');
}

/* 🖨️ طباعة الوصفة */
function apfPrintPrescription() {
    const name   = apfGetVal('apf_rx_patient_name') || apfGetVal('apf_full_name') || '—';
    const date   = apfGetVal('apf_rx_date') || new Date().toLocaleDateString('fr-DZ');
    const drugs  = apfGetVal('apf_rx_prescription') || '—';
    const notes  = apfGetVal('apf_rx_doctor_notes');
    const win = window.open('', '_blank');
    if (!win) return;
    win.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>الوصفة الطبية</title>
<style>body{font-family:Arial,sans-serif;padding:32px;direction:rtl;}h1{color:#0ea5e9;border-bottom:3px solid #0ea5e9;padding-bottom:8px;}.info{background:#f0f9ff;padding:10px 16px;border-radius:8px;margin-bottom:20px;}.sec h3{color:#0ea5e9;margin:14px 0 6px;}.sec p{background:#f8fafc;padding:10px;border-radius:8px;border-right:3px solid #0ea5e9;white-space:pre-wrap;min-height:40px;}@media print{body{padding:8px;}}</style></head><body>
<h1>📋 الوصفة الطبية</h1>
<div class="info"><strong>👤 المريض:</strong> ${name} &nbsp; <strong>📅 التاريخ:</strong> ${date}</div>
<div class="sec"><h3>💊 الأدوية</h3><p>${drugs}</p></div>
${notes ? `<div class="sec"><h3>📋 تعليمات الطبيب</h3><p>${notes}</p></div>` : ''}
<script>window.onload=()=>window.print()<\/script></body></html>`);
    win.document.close();
}

/* 📤 إرسال Fiche للممرض (stub) */
function apfSendFicheToNurse() {
    showAddPatientToast('📤 سيتم ربط إرسال الفيش للممرض لاحقاً', 'warn');
}

/* 🖨️ طباعة Fiche de traitement */
function apfPrintFicheTraitement() {
    const name     = apfGetVal('apf_rx_patient_name') || apfGetVal('apf_full_name') || '—';
    const date     = apfGetVal('apf_rx_date') || new Date().toLocaleDateString('fr-DZ');
    const diag     = apfGetVal('apf_fiche_diagnostic') || '—';
    const meds     = apfGetVal('apf_fiche_medications') || '—';
    const win = window.open('', '_blank');
    if (!win) return;
    win.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>Fiche de traitement</title>
<style>body{font-family:Arial,sans-serif;padding:28px;direction:rtl;}h1{color:#0ea5e9;border-bottom:3px solid #0ea5e9;padding-bottom:8px;}.pi{background:#f0f9ff;padding:8px 14px;border-radius:8px;margin-bottom:16px;}.sec h3{background:#0ea5e9;color:#fff;padding:5px 12px;border-radius:6px;margin-bottom:6px;}.sec p{white-space:pre-wrap;background:#f8fafc;padding:8px 12px;border-radius:6px;border-right:3px solid #0ea5e9;min-height:36px;margin:0 0 12px;}@media print{body{padding:8px;}}</style></head><body>
<h1>💉 Fiche de traitement</h1>
<div class="pi"><strong>👤 المريض:</strong> ${name} &nbsp; <strong>📅 التاريخ:</strong> ${date}</div>
<div class="sec"><h3>🩺 التشخيص / Diagnostic</h3><p>${diag}</p></div>
<div class="sec"><h3>💊 الأدوية والعلاجات / Médicaments &amp; traitements</h3><p>${meds}</p></div>
<script>window.onload=()=>window.print()<\/script></body></html>`);
    win.document.close();
}

/* 🖨️ طباعة السجل الطبي */
function apfPrintMedicalRecord() {
    const name = apfGetVal('apf_full_name') || '—';
    const age  = apfGetVal('apf_age');
    const reason = apfGetVal('apf_reason_exam') || '—';
    const drugs  = apfGetVal('apf_rx_prescription') || '—';
    const date   = new Date().toLocaleDateString('fr-DZ');
    const win = window.open('', '_blank');
    if (!win) return;
    win.document.write(`<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>السجل الطبي</title>
<style>body{font-family:Arial,sans-serif;padding:28px;direction:rtl;}h1{color:#0ea5e9;border-bottom:3px solid #0ea5e9;padding-bottom:8px;}.info{background:#f0f9ff;padding:10px 16px;border-radius:8px;margin-bottom:16px;}.sec h3{color:#0ea5e9;margin:12px 0 5px;}.sec p{background:#f8fafc;padding:8px;border-radius:6px;border-right:3px solid #0ea5e9;white-space:pre-wrap;min-height:32px;margin:0;}@media print{body{padding:8px;}}</style></head><body>
<h1>📂 السجل الطبي</h1>
<div class="info"><strong>👤 المريض:</strong> ${name} ${age ? '| العمر: '+age : ''} &nbsp; <strong>📅 التاريخ:</strong> ${date}</div>
<div class="sec"><h3>📋 سبب الفحص</h3><p>${reason}</p></div>
<div class="sec"><h3>💊 الوصفة الطبية</h3><p>${drugs}</p></div>
<script>window.onload=()=>window.print()<\/script></body></html>`);
    win.document.close();
}

function saveNewPatient() { apfSaveRecord(); }

function showAddPatientToast(message, type) {
    const existing = document.getElementById('addPatientToast');
    if (existing) existing.remove();

    const colors = {
        success: { bg: 'linear-gradient(135deg,#10b981,#34d399)', icon: '✅' },
        error:   { bg: 'linear-gradient(135deg,#ef4444,#f87171)', icon: '❌' },
        warn:    { bg: 'linear-gradient(135deg,#f59e0b,#fbbf24)', icon: '⚠️' }
    };
    const c = colors[type] || colors.success;

    const toast = document.createElement('div');
    toast.id = 'addPatientToast';
    toast.style.cssText = `
        position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px);
        background:${c.bg}; color:#fff; padding:14px 24px; border-radius:14px;
        font-size:0.9rem; font-weight:600; font-family:'Cairo',sans-serif;
        box-shadow:0 8px 32px rgba(0,0,0,0.2); z-index:99999;
        transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.4s ease;
        opacity:0; white-space:nowrap; max-width:90vw; text-align:center;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(-50%) translateY(0)';
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.transform = 'translateX(-50%) translateY(80px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ═══════════════════════════════════════════════════════
   MEDCOMM — التواصل الطبي
   Front-End فقط — بيانات وهمية
═══════════════════════════════════════════════════════ */

/* ── بيانات وهمية ── */
var MEDCOMM_CONSULTS = {
    1: {
        id:1, from:'د. سارة أحمد', specialty:'قلب وأوعية', subject:'مريض يعاني من ضيق تنفس مزمن',
        date:'2026-05-24', type:'inbox', avatar:'س', color:'#0ea5e9',
        patient:'أحمد بن صالح — 62 سنة',
        body:'السلام عليكم دكتور،\n\nأودّ الاستفسار بخصوص المريض أحمد بن صالح (62 سنة)، يعاني من قصور قلبي مع ضيق تنفس متزايد.\n\nنتائج Echocardiographie تظهر FE = 35%، Pro-BNP مرتفع.\n\nهل تنصح بتعديل جرعة Furosémide؟ وما رأيك في إضافة Spironolactone؟\n\nشكراً',
        conversation:[
            {from:'د. سارة أحمد', text:'السلام عليكم دكتور، أودّ الاستفسار بخصوص المريض أحمد بن صالح...', time:'2026-05-24 09:15', type:'received'},
            {from:'أنت', text:'وعليكم السلام دكتورة، شكراً على التواصل. سأراجع الملف وأرد عليك.', time:'2026-05-24 10:30', type:'sent'},
        ]
    },
    2: {
        id:2, from:'د. خالد محمود', specialty:'عظام', subject:'تقرير فحص مريض بعد عملية الركبة',
        date:'2026-05-23', type:'inbox', avatar:'خ', color:'#8b5cf6',
        patient:'محمد عمر — 55 سنة',
        body:'دكتور، مرفق تقرير متابعة المريض محمد عمر بعد عملية الركبة الاصطناعية.\n\nالمريض يشكو من آلام متوسطة الشدة وتورم خفيف.\n\nهل يمكن مراجعة جرعة مضاد الألم الحالية؟',
        conversation:[
            {from:'د. خالد محمود', text:'دكتور، مرفق تقرير متابعة المريض محمد عمر بعد عملية الركبة...', time:'2026-05-23 14:00', type:'received'},
        ]
    },
    3: {
        id:3, from:'د. ياسمين بن علي', specialty:'أمراض داخلية', subject:'استشارة عاجلة — مريضة بالطابق 3',
        date:'2026-05-22', type:'inbox', avatar:'ي', color:'#10b981',
        patient:'فاطمة بوزيدي — 38 سنة',
        body:'دكتور، حالة عاجلة.\n\nمريضة فاطمة بوزيدي — الطابق 3 — غرفة 8.\nضغط دم 180/110، صداع شديد، دوخة.\nهل تنصح بإعطاء Nicardipine IV الآن أم ننتظر؟',
        conversation:[
            {from:'د. ياسمين بن علي', text:'دكتور، حالة عاجلة. مريضة فاطمة بوزيدي — الطابق 3...', time:'2026-05-22 11:45', type:'received'},
            {from:'أنت', text:'أعطِها Nicardipine 5mg/h IV، مراقبة الضغط كل 15 دقيقة.', time:'2026-05-22 11:52', type:'sent'},
            {from:'د. ياسمين بن علي', text:'شكراً دكتور، الضغط بدأ ينخفض تدريجياً.', time:'2026-05-22 12:30', type:'received'},
        ]
    },
    4: {
        id:4, to:'د. كمال بوعزيز', specialty:'مسالك بولية', subject:'مريض بحاجة لتقييم جراحي — حصوات كلى',
        date:'2026-05-21', type:'sent', avatar:'ك', color:'#f59e0b',
        patient:'رشيد مزيان — 48 سنة',
        body:'دكتور كمال،\n\nأرسل إليك ملف المريض رشيد مزيان، يعاني من حصوات كلوية متعددة (أكبرها 12mm).\n\nهل ينصح بالتدخل الجراحي أم نبدأ بالعلاج الدوائي؟',
        conversation:[
            {from:'أنت', text:'دكتور كمال، أرسل إليك ملف المريض رشيد مزيان...', time:'2026-05-21 08:30', type:'sent'},
            {from:'د. كمال بوعزيز', text:'شكراً دكتور، الحالة تستدعي ESWL. سأتواصل مع المريض لحجز موعد.', time:'2026-05-21 15:00', type:'received'},
        ]
    },
    5: {
        id:5, to:'د. نادية شريف', specialty:'تغذية', subject:'خطة غذائية لمريض سكري مزمن',
        date:'2026-05-20', type:'sent', avatar:'ن', color:'#ec4899',
        patient:'عبد الرحمن قاسمي — 58 سنة',
        body:'دكتورة نادية،\n\nأحتاج خطة غذائية لمريضي عبد الرحمن قاسمي، سكري من النوع 2، HbA1c = 8.9%.\n\nالمريض يعاني من السمنة (BMI = 32). شكراً.',
        conversation:[
            {from:'أنت', text:'دكتورة نادية، أحتاج خطة غذائية لمريضي عبد الرحمن قاسمي...', time:'2026-05-20 10:00', type:'sent'},
        ]
    },
};

var MEDCOMM_FOLLOWUPS = {
    1: { name:'محمد بلعيد', age:45, condition:'التهاب زائدة — مراقبة ما بعد العملية', phone:'0555 123 456', timeline:[
        {date:'2026-05-15', note:'دخول المستشفى — جراحة الزائدة الدودية', type:'entry', icon:'fas fa-hospital'},
        {date:'2026-05-16', note:'اليوم الأول بعد العملية — الحالة مستقرة، لا نزيف', type:'ok', icon:'fas fa-check-circle'},
        {date:'2026-05-18', note:'رفع الصرف — شروع في التغذية السائلة', type:'progress', icon:'fas fa-utensils'},
        {date:'2026-05-20', note:'تحسن ملحوظ — الجرح يلتئم بشكل طبيعي', type:'ok', icon:'fas fa-heartbeat'},
        {date:'2026-05-27', note:'موعد المتابعة القادمة — فحص الجرح ونتائج التحاليل', type:'next', icon:'fas fa-calendar-check'},
    ]},
    2: { name:'أحمد بن صالح', age:62, condition:'قصور قلبي — متابعة شهرية', phone:'0661 789 012', timeline:[
        {date:'2026-04-10', note:'بداية العلاج — Bisoprolol + Furosémide', type:'entry', icon:'fas fa-pills'},
        {date:'2026-04-25', note:'متابعة شهرية — FE تحسنت من 35% إلى 40%', type:'ok', icon:'fas fa-chart-line'},
        {date:'2026-05-18', note:'دخول للطوارئ — ضيق تنفس حاد، تعديل الجرعة', type:'alert', icon:'fas fa-exclamation-circle'},
        {date:'2026-05-30', note:'موعد المتابعة القادمة — Echo + Pro-BNP', type:'next', icon:'fas fa-calendar-check'},
    ]},
    3: { name:'فاطمة حسن', age:35, condition:'سكري — فحص HbA1c', phone:'0555 222 333', timeline:[
        {date:'2026-03-01', note:'اكتشاف السكري من النوع 2 — HbA1c = 9.2%', type:'entry', icon:'fas fa-vial'},
        {date:'2026-04-01', note:'بداية العلاج — Metformine 500mg', type:'progress', icon:'fas fa-pills'},
        {date:'2026-05-01', note:'فحص متابعة — HbA1c = 8.1% — تحسن ملحوظ', type:'ok', icon:'fas fa-check-circle'},
        {date:'2026-06-05', note:'موعد المتابعة — قياس HbA1c و Glycémie à jeun', type:'next', icon:'fas fa-calendar-check'},
    ]},
    4: { name:'خالد عمار', age:52, condition:'ضغط دم — تعديل الجرعة', phone:'0770 456 789', timeline:[
        {date:'2026-04-15', note:'أول زيارة — ضغط 165/100', type:'alert', icon:'fas fa-exclamation-circle'},
        {date:'2026-04-20', note:'بداية Amlodipine 5mg — ضغط 150/90', type:'progress', icon:'fas fa-pills'},
        {date:'2026-05-20', note:'⚠️ لم يحضر للمتابعة — محاولة الاتصال به', type:'alert', icon:'fas fa-phone-slash'},
    ]},
    5: { name:'سارة بوزيدي', age:28, condition:'متابعة حمل — الأسبوع 32', phone:'0550 111 222', timeline:[
        {date:'2026-03-10', note:'أول زيارة — تأكيد الحمل، الأسبوع 18', type:'entry', icon:'fas fa-baby'},
        {date:'2026-04-07', note:'الأسبوع 24 — Echo طبيعي، وزن الجنين 600g', type:'ok', icon:'fas fa-heartbeat'},
        {date:'2026-05-05', note:'الأسبوع 28 — فحص السكر سلبي، وزن الجنين 1.1kg', type:'ok', icon:'fas fa-check-circle'},
        {date:'2026-06-10', note:'موعد الأسبوع 32 — Echo تفصيلي + تحاليل', type:'next', icon:'fas fa-calendar-check'},
    ]},
};

var fuIdCounter = 6;

/* ── Navigation ── */
function snavGoAI(cardId) {
    // أظهر واجهة مركز البيانات
    document.querySelectorAll('.interface').forEach(function(i){ i.classList.remove('active'); });
    var aiInterface = document.getElementById('ai-interface');
    if (aiInterface) aiInterface.classList.add('active');

    // تحديث عنوان الصفحة
    var pt = document.getElementById('pageTitle');
    if (pt) pt.textContent = 'مركز البيانات';

    // تفعيل accordion في sidebar
    var body = document.getElementById('snb-ai');
    if (body) body.classList.add('snb-open');
    var header = document.querySelector('#sng-ai .snav-header');
    if (header) header.classList.add('snav-open');

    // إزالة active من العناصر المباشرة الأخرى
    document.querySelectorAll('.snav-direct').forEach(function(el){ el.classList.remove('snav-active-direct'); });

    // فتح البطاقة المحددة داخل مركز البيانات
    var clickedItem = event && event.currentTarget;
    setTimeout(function() {
        // أغلق كل البطاقات أولاً
        document.querySelectorAll('.ai-content').forEach(function(c){ c.classList.remove('active'); });
        document.querySelectorAll('.ai-card').forEach(function(c){ c.classList.remove('active'); });

        // افتح البطاقة المطلوبة
        var target = document.getElementById(cardId);
        if (target) {
            target.classList.add('active');
            target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // تعيين active على عنصر الـ sidebar
        document.querySelectorAll('.snav-item').forEach(function(i){ i.classList.remove('snav-item-active'); });
        if (clickedItem) clickedItem.classList.add('snav-item-active');
    }, 40);

    if (window.innerWidth <= 768 && typeof closeSidebar === 'function') closeSidebar();
}

function snavGoMedComm(subSection) {
    // أغلق كل الواجهات وأظهر الهدف
    document.querySelectorAll('.interface').forEach(function(i){ i.classList.remove('active'); });
    var target = document.getElementById('medcomm-' + subSection + '-interface');
    if (target) target.classList.add('active');

    // تحديث الـ title
    var titles = { consultation:'الاستشارة الطبية', followup:'متابعة المرضى' };
    var pt = document.getElementById('pageTitle');
    if (pt) pt.textContent = titles[subSection] || 'التواصل الطبي';

    // تفعيل accordion في sidebar
    var body = document.getElementById('snb-medcomm');
    if (body) body.classList.add('snb-open');
    var header = document.querySelector('#sng-medcomm .snav-header');
    if (header) header.classList.add('snav-open');

    // تعطيل active من العناصر المباشرة الأخرى
    document.querySelectorAll('.snav-direct').forEach(function(el){ el.classList.remove('snav-active-direct'); });

    if (window.innerWidth <= 768 && typeof closeSidebar === 'function') closeSidebar();
}

/* ══════════════════════════════════════════════
   CONSULTATION TABS
══════════════════════════════════════════════ */
function switchConsultTab(tab, btn) {
    document.querySelectorAll('.medcomm-tab').forEach(function(b){ b.classList.remove('active'); });
    document.querySelectorAll('.medcomm-tab-content').forEach(function(c){ c.classList.remove('active'); });
    if (btn) btn.classList.add('active');
    var content = document.getElementById('consult-tab-' + tab);
    if (content) content.classList.add('active');
}

/* ══════════════════════════════════════════════
   CONSULTATION MODAL
══════════════════════════════════════════════ */
function openConsultModal(id, type) {
    var c = MEDCOMM_CONSULTS[id];
    if (!c) return;

    var title = type === 'inbox'
        ? ('من: ' + c.from)
        : ('إلى: ' + c.to);
    document.getElementById('consultModalTitle').textContent = c.subject;
    document.getElementById('consultModalSub').textContent = title;

    // بناء المحادثة
    var html = '';
    html += '<div class="consult-modal-info">';
    html += '<div class="cmi-row"><i class="fas fa-user-md"></i><span>' + (type==='inbox' ? ('من: ' + c.from + ' — ' + c.specialty) : ('إلى: ' + c.to + ' — ' + c.specialty)) + '</span></div>';
    html += '<div class="cmi-row"><i class="fas fa-user-injured"></i><span>المريض: ' + (c.patient||'—') + '</span></div>';
    html += '<div class="cmi-row"><i class="fas fa-calendar-alt"></i><span>' + c.date + '</span></div>';
    html += '</div>';

    // المحادثة
    html += '<div class="consult-conversation">';
    (c.conversation||[]).forEach(function(msg) {
        html += '<div class="consult-msg ' + msg.type + '">';
        html += '<div class="consult-msg-from">' + msg.from + ' <small>' + msg.time + '</small></div>';
        html += '<div class="consult-msg-text">' + msg.text.replace(/\n/g,'<br>') + '</div>';
        html += '</div>';
    });
    html += '</div>';

    if (type === 'inbox') {
        html += '<div class="consult-reply-area">';
        html += '<textarea id="consultReplyText" rows="3" placeholder="اكتب ردك هنا..."></textarea>';
        html += '</div>';
    }

    document.getElementById('consultModalBody').innerHTML = html;
    var replyBtn = document.getElementById('consultReplyBtn');
    if (replyBtn) replyBtn.style.display = type === 'inbox' ? '' : 'none';

    // Store current consult
    window._currentConsultId = id;
    window._currentConsultType = type;

    var modal = document.getElementById('consultModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Mark as read
    var card = document.querySelector('.consult-item.unread');
    // إزالة الـ unread من هذا العنصر تحديداً
    document.querySelectorAll('.consult-item').forEach(function(el) {
        if (el.getAttribute('onclick') && el.getAttribute('onclick').includes(id+',')) {
            el.classList.remove('unread');
            var badge = el.querySelector('.consult-new-badge');
            if (badge) badge.remove();
        }
    });
}

function closeConsultModal() {
    var modal = document.getElementById('consultModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function sendConsultReply() {
    var text = document.getElementById('consultReplyText') ? document.getElementById('consultReplyText').value.trim() : '';
    if (!text) { pfmShowToast && pfmShowToast('⚠️ الرجاء كتابة الرد أولاً', 'warn'); return; }

    var id = window._currentConsultId;
    if (id && MEDCOMM_CONSULTS[id]) {
        MEDCOMM_CONSULTS[id].conversation.push({
            from:'أنت', text:text,
            time: new Date().toLocaleDateString('ar-DZ') + ' ' + new Date().toLocaleTimeString('ar-DZ', {hour:'2-digit',minute:'2-digit'}),
            type:'sent'
        });
    }

    // إعادة رسم المحادثة
    openConsultModal(id, 'inbox');

    if (typeof pfmShowToast === 'function') pfmShowToast('✅ تم إرسال الرد بنجاح', 'success');
    else if (typeof inpShowToast === 'function') inpShowToast('✅ تم إرسال الرد بنجاح');
}

function resetConsultForm() {
    ['consult-to-doctor','consult-patient','consult-subject','consult-body'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    var radios = document.querySelectorAll('input[name="consult-priority"]');
    if (radios.length) radios[0].checked = true;
}

function sendConsultation() {
    var doctor  = document.getElementById('consult-to-doctor') ? document.getElementById('consult-to-doctor').value : '';
    var patient = document.getElementById('consult-patient')   ? document.getElementById('consult-patient').value   : '';
    var subject = document.getElementById('consult-subject')   ? document.getElementById('consult-subject').value.trim() : '';
    var body    = document.getElementById('consult-body')      ? document.getElementById('consult-body').value.trim()    : '';
    if (!doctor || !subject || !body) {
        if (typeof pfmShowToast === 'function') pfmShowToast('⚠️ الرجاء ملء جميع الحقول الإلزامية', 'warn');
        return;
    }
    resetConsultForm();
    if (typeof pfmShowToast === 'function') pfmShowToast('✅ تم إرسال الاستشارة بنجاح', 'success');
    else if (typeof inpShowToast === 'function') inpShowToast('✅ تم إرسال الاستشارة بنجاح');
    // الانتقال لتاب المرسلة
    var tab = document.querySelector('.medcomm-tab:nth-child(2)');
    switchConsultTab('sent', tab);
}

/* ══════════════════════════════════════════════
   FOLLOWUP TIMELINE MODAL
══════════════════════════════════════════════ */
function openFollowupTimeline(id) {
    var fu = MEDCOMM_FOLLOWUPS[id];
    if (!fu) return;

    document.getElementById('timelinePatientName').textContent = fu.name + ' — Timeline';

    var typeClasses = { entry:'entry', ok:'ok', progress:'progress', alert:'alert', next:'next' };
    var typeColors  = { entry:'#0ea5e9', ok:'#10b981', progress:'#8b5cf6', alert:'#ef4444', next:'#f59e0b' };

    var html = '<div class="fu-timeline">';
    fu.timeline.forEach(function(ev, i) {
        var color = typeColors[ev.type] || '#0ea5e9';
        html += '<div class="fu-tl-item">';
        html += '<div class="fu-tl-dot" style="background:' + color + ';box-shadow:0 0 0 4px ' + color + '22;">';
        html += '<i class="' + ev.icon + '" style="color:#fff;font-size:0.68rem;"></i>';
        html += '</div>';
        if (i < fu.timeline.length - 1) html += '<div class="fu-tl-line"></div>';
        html += '<div class="fu-tl-content">';
        html += '<div class="fu-tl-date" style="color:' + color + ';">' + ev.date + '</div>';
        html += '<div class="fu-tl-note">' + ev.note + '</div>';
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';

    document.getElementById('timelineBody').innerHTML = html;

    var modal = document.getElementById('followupTimelineModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeFollowupTimeline() {
    document.getElementById('followupTimelineModal').style.display = 'none';
    document.body.style.overflow = '';
}

/* ══════════════════════════════════════════════
   ADD FOLLOWUP MODAL
══════════════════════════════════════════════ */
function openAddFollowup() {
    // Set default date to tomorrow
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 7);
    var dateEl = document.getElementById('fu-new-date');
    if (dateEl) dateEl.value = tomorrow.toISOString().split('T')[0];

    document.getElementById('addFollowupModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAddFollowup() {
    document.getElementById('addFollowupModal').style.display = 'none';
    document.body.style.overflow = '';
}

function saveNewFollowup() {
    var name      = document.getElementById('fu-new-name')      ? document.getElementById('fu-new-name').value.trim()      : '';
    var age       = document.getElementById('fu-new-age')       ? document.getElementById('fu-new-age').value               : '';
    var phone     = document.getElementById('fu-new-phone')     ? document.getElementById('fu-new-phone').value.trim()      : '';
    var condition = document.getElementById('fu-new-condition') ? document.getElementById('fu-new-condition').value.trim()  : '';
    var date      = document.getElementById('fu-new-date')      ? document.getElementById('fu-new-date').value               : '';
    var priority  = document.getElementById('fu-new-priority')  ? document.getElementById('fu-new-priority').value          : 'عادية';
    var notes     = document.getElementById('fu-new-notes')     ? document.getElementById('fu-new-notes').value.trim()      : '';

    if (!name || !condition || !date) {
        if (typeof pfmShowToast === 'function') pfmShowToast('⚠️ الرجاء ملء الحقول الإلزامية', 'warn');
        return;
    }

    var colors  = ['#0ea5e9','#8b5cf6','#10b981','#f59e0b','#ec4899','#ef4444'];
    var color   = colors[fuIdCounter % colors.length];
    var avatar  = name.charAt(0);

    // إنشاء بطاقة جديدة في القائمة
    var list = document.getElementById('followup-list');
    if (list) {
        var card = document.createElement('div');
        card.className = 'followup-card';
        card.id = 'fu-card-' + fuIdCounter;
        card.innerHTML = [
            '<div class="fu-card-left">',
            '  <div class="fu-avatar" style="background:' + color + ';">' + avatar + '</div>',
            '  <div class="fu-info">',
            '    <div class="fu-name">' + escapeHtml(name) + (age ? ' <small>— ' + escapeHtml(age) + ' سنة</small>' : '') + '</div>',
            '    <div class="fu-condition"><i class="fas fa-notes-medical"></i> ' + escapeHtml(condition) + '</div>',
            (phone ? '    <div class="fu-phone"><i class="fas fa-phone"></i> ' + escapeHtml(phone) + '</div>' : ''),
            '  </div>',
            '</div>',
            '<div class="fu-card-right">',
            '  <div class="fu-next-date"><i class="fas fa-calendar-alt"></i><span>' + date + '</span></div>',
            '  <span class="fu-priority-badge ' + (priority==='عاجلة'?'urgent':priority==='متوسطة'?'medium':'normal') + '">' + priority + '</span>',
            '  <span class="fu-status-badge active">نشطة</span>',
            '  <button class="fu-timeline-btn" onclick="openFollowupTimeline(' + fuIdCounter + ')"><i class="fas fa-history"></i> Timeline</button>',
            '</div>',
        ].join('');

        // إضافة بيانات timeline وهمية للمريض الجديد
        MEDCOMM_FOLLOWUPS[fuIdCounter] = {
            name: name, age: age, condition: condition, phone: phone,
            timeline: [
                { date: new Date().toISOString().split('T')[0], note: 'إضافة المتابعة — ' + condition + (notes ? '. ملاحظات: ' + notes : ''), type:'entry', icon:'fas fa-plus-circle' },
                { date: date, note: 'الموعد القادم المحدد للمتابعة', type:'next', icon:'fas fa-calendar-check' },
            ]
        };

        list.appendChild(card);
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        fuIdCounter++;
    }

    // مسح الحقول وإغلاق
    ['fu-new-name','fu-new-age','fu-new-phone','fu-new-condition','fu-new-date','fu-new-notes'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    var priEl = document.getElementById('fu-new-priority');
    if (priEl) priEl.value = 'عادية';

    closeAddFollowup();
    if (typeof pfmShowToast === 'function') pfmShowToast('✅ تمت إضافة المتابعة بنجاح', 'success');
    else if (typeof inpShowToast === 'function') inpShowToast('✅ تمت إضافة المتابعة بنجاح');
}

/* ══════════════════════════════════════════════════════
   FU CHAT — نافذة محادثة متابعة المريض
   Front-End فقط — رسائل تجريبية — لا DB لا AJAX
   ══════════════════════════════════════════════════════ */
(function() {

    // رسائل تجريبية ثابتة لكل مريض
    var DUMMY_MSGS = {
        1: [
            { from:'patient', text:'صباح الخير دكتور، أحسست بألم خفيف عند الجرح أمس.', time:'09:14' },
            { from:'doctor',  text:'صباح النور. هذا طبيعي في مرحلة التعافي، هل يوجد احمرار أو إفراز؟', time:'09:21' },
            { from:'patient', text:'لا، الجرح نظيف. فقط ألم عند الحركة.', time:'09:25' },
            { from:'doctor',  text:'ممتاز. التزم بالراحة وتجنب الجهد لأسبوع آخر. نراك في الموعد القادم.', time:'09:28' },
        ],
        2: [
            { from:'patient', text:'دكتور، أحسست بضيق تنفس خفيف الليلة.', time:'22:05' },
            { from:'doctor',  text:'قس ضغطك الآن وأخبرني بالنتيجة.', time:'22:10' },
            { from:'patient', text:'الضغط 140/85 والنبض 88.', time:'22:13' },
            { from:'doctor',  text:'مقبول. خذ الجرعة المسائية وارفع وسادتك قليلاً. إذا زاد الضيق اتصل بالطوارئ.', time:'22:15' },
        ],
        3: [
            { from:'doctor',  text:'بنجير، كيف حال مستوى السكر هذا الأسبوع؟', time:'10:00' },
            { from:'patient', text:'الصوم بين 130 و145. أحسن من الشهر الماضي.', time:'10:08' },
            { from:'doctor',  text:'تحسن جيد جداً. واصلي الحمية وحركة 30 دقيقة يومياً.', time:'10:12' },
        ],
        4: [
            { from:'doctor',  text:'السلام عليكم أستاذ خالد، لم نرك في موعد الأسبوع الماضي. هل أنت بخير؟', time:'11:30' },
            { from:'patient', text:'معذرة دكتور، كنت مسافراً. سأحجز موعداً قريباً.', time:'14:20' },
            { from:'doctor',  text:'حسناً، مهم جداً لا تنقطع عن الدواء. حدد موعداً هذا الأسبوع.', time:'14:25' },
        ],
        5: [
            { from:'patient', text:'دكتور، طفلي أتحرك أكثر من المعتاد اليوم 🥰', time:'08:45' },
            { from:'doctor',  text:'هذا مؤشر صحي ممتاز! كمي تحركاته: يجب أن يكون 10 حركات كل ساعتين.', time:'09:00' },
            { from:'patient', text:'عددت 12 حركة في ساعة. الحمد لله.', time:'09:15' },
            { from:'doctor',  text:'رائع، استمري في المتابعة وتناولي حمض الفوليك بانتظام.', time:'09:18' },
        ],
    };

    var DEFAULT_MSGS = [
        { from:'doctor',  text:'أهلاً، كيف تشعر اليوم؟', time:'10:00' },
        { from:'patient', text:'الحمد لله أحسن من الأمس.', time:'10:05' },
        { from:'doctor',  text:'ممتاز. سأتابع حالتك عن كثب.', time:'10:07' },
    ];

    var fuChatMsgs = [];      // رسائل الجلسة الحالية
    var fuChatPatient = null; // بيانات المريض الحالي

    window.openFuChat = function(id) {
        var fu = MEDCOMM_FOLLOWUPS[id];
        if (!fu) return;
        fuChatPatient = fu;

        // Header
        document.getElementById('fuChatAvatarEl').textContent = fu.avatar || fu.name.charAt(0);
        document.getElementById('fuChatAvatarEl').style.background = fu.color || '#0ea5e9';
        document.getElementById('fuChatNameEl').textContent = fu.name + (fu.age ? ' — ' + fu.age + ' سنة' : '');

        // آخر موعد من timeline
        var lastEntry = fu.timeline && fu.timeline.length ? fu.timeline[fu.timeline.length - 1] : null;
        document.getElementById('fuChatSubEl').textContent = fu.condition || '';

        // Info card
        var info = document.getElementById('fuChatInfoCard');
        var statusColor = {'نشطة':'#10b981','متأخرة':'#ef4444'}[fu.status] || '#0ea5e9';
        info.innerHTML =
            '<div style="display:flex;align-items:center;gap:7px;font-size:.77rem;color:var(--text-secondary,#475569);font-family:Cairo,sans-serif;margin-bottom:5px;">' +
            '<i class="fas fa-notes-medical" style="color:#0ea5e9;width:14px;"></i>' + fu.condition + '</div>' +
            (lastEntry ? '<div style="display:flex;align-items:center;gap:7px;font-size:.75rem;color:var(--text-muted,#94a3b8);font-family:Cairo,sans-serif;margin-bottom:5px;">' +
            '<i class="fas fa-calendar-alt" style="color:#0ea5e9;width:14px;"></i>' + lastEntry.date + '</div>' : '') +
            (fu.status ? '<span style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:.68rem;font-weight:700;font-family:Cairo,sans-serif;background:' + statusColor + '22;color:' + statusColor + ';">' + fu.status + '</span>' : '');

        // رسائل تجريبية
        fuChatMsgs = (DUMMY_MSGS[id] || DEFAULT_MSGS).map(function(m){ return Object.assign({}, m); });
        renderFuMsgs();

        // افتح
        var overlay = document.getElementById('fuChatOverlay');
        overlay.style.display = 'flex';
        setTimeout(function(){ overlay.classList.add('fu-open'); }, 10);
        setTimeout(function(){ scrollFuMsgs(); }, 80);
        document.getElementById('fuChatInput').focus();
    };

    window.closeFuChat = function() {
        var overlay = document.getElementById('fuChatOverlay');
        overlay.classList.remove('fu-open');
        setTimeout(function(){ overlay.style.display = 'none'; }, 200);
    };

    window.sendFuChat = function() {
        var input = document.getElementById('fuChatInput');
        var text = input.value.trim();
        if (!text) return;
        fuChatMsgs.push({ from:'doctor', text:text, time:getNow() });
        renderFuMsgs();
        input.value = '';
        input.style.height = '';
        scrollFuMsgs();
    };

    function renderFuMsgs() {
        var container = document.getElementById('fuChatMessages');
        container.innerHTML = '';

        // فاصل التاريخ
        var sep = document.createElement('div');
        sep.style.cssText = 'text-align:center;font-size:.69rem;color:var(--text-muted,#94a3b8);font-family:Cairo,sans-serif;font-weight:600;padding:4px 0;';
        sep.textContent = 'اليوم';
        container.appendChild(sep);

        fuChatMsgs.forEach(function(msg, i) {
            var isSent = msg.from === 'doctor';
            var wrap = document.createElement('div');
            wrap.style.cssText = 'display:flex;gap:8px;max-width:85%;' +
                (isSent ? 'align-self:flex-start;flex-direction:row-reverse;margin-right:auto;' : 'align-self:flex-start;');

            var avatarBg = isSent ? (fuChatPatient && fuChatPatient.color ? fuChatPatient.color : '#0ea5e9') : '#64748b';
            var avatarLetter = isSent ? 'د' : (fuChatPatient ? fuChatPatient.name.charAt(0) : 'م');

            var avatar = document.createElement('div');
            avatar.style.cssText = 'width:27px;height:27px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#fff;flex-shrink:0;margin-top:2px;background:' + avatarBg + ';';
            avatar.textContent = avatarLetter;

            var body = document.createElement('div');
            body.style.cssText = 'display:flex;flex-direction:column;gap:2px;' + (isSent ? 'align-items:flex-end;' : 'align-items:flex-start;');

            var from = document.createElement('div');
            from.style.cssText = 'font-size:.66rem;font-weight:700;color:var(--text-muted,#94a3b8);font-family:Cairo,sans-serif;padding:0 4px;';
            from.textContent = isSent ? 'الطبيب' : (fuChatPatient ? fuChatPatient.name.split(' ')[0] : 'المريض');

            var bubble = document.createElement('div');
            bubble.style.cssText = isSent
                ? 'background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#fff;border-radius:16px 16px 4px 16px;padding:9px 13px;font-size:.82rem;line-height:1.55;box-shadow:0 3px 10px rgba(14,165,233,.25);font-family:Cairo,sans-serif;word-wrap:break-word;'
                : 'background:var(--bg-main,#f8fafc);color:var(--text-main,#1e293b);border:1px solid var(--border,rgba(148,163,184,.2));border-radius:16px 16px 16px 4px;padding:9px 13px;font-size:.82rem;line-height:1.55;font-family:Cairo,sans-serif;word-wrap:break-word;';
            bubble.textContent = msg.text;

            var time = document.createElement('div');
            time.style.cssText = 'font-size:.62rem;color:var(--text-muted,#94a3b8);font-family:Cairo,sans-serif;padding:0 4px;';
            time.textContent = msg.time || '';

            body.appendChild(from);
            body.appendChild(bubble);
            body.appendChild(time);
            wrap.appendChild(avatar);
            wrap.appendChild(body);

            // Animation للرسائل الجديدة
            if (i === fuChatMsgs.length - 1) {
                wrap.style.animation = 'fuMsgIn .22s cubic-bezier(.34,1.56,.64,1)';
            }

            container.appendChild(wrap);
        });
    }

    function scrollFuMsgs() {
        var c = document.getElementById('fuChatMessages');
        if (c) c.scrollTop = c.scrollHeight;
    }

    function getNow() {
        var d = new Date();
        return ('0'+d.getHours()).slice(-2) + ':' + ('0'+d.getMinutes()).slice(-2);
    }

    // Auto-resize textarea
    document.addEventListener('DOMContentLoaded', function() {
        var inp = document.getElementById('fuChatInput');
        if (inp) {
            inp.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 110) + 'px';
            });
        }
    });

    // إغلاق بـ Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var ov = document.getElementById('fuChatOverlay');
            if (ov && ov.style.display !== 'none') closeFuChat();
        }
    });

})();
/* ══ END FU CHAT ══ */

/* ══════════════════════════════════════════════════════════════
   1) حقل المصلحة — يظهر/يختفي ديناميكياً
   Front-End فقط — معزول — سهل الحذف
══════════════════════════════════════════════════════════════ */
(function() {
    // pfmDischargePanel — radio buttons
    document.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'pfmDischargeType') {
            var svcField = document.getElementById('pfmTransferSvcField');
            if (svcField) {
                var isTransfer = e.target.value === 'تحويل الى مصلحة اخرى';
                svcField.style.display = isTransfer ? 'block' : 'none';
                if (!isTransfer) {
                    var inp = document.getElementById('pfmTransferSvcName');
                    if (inp) inp.value = '';
                }
            }
        }
    });

    // inp-discharge-type select (inpatients modal)
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'inp-discharge-type') {
            var svcField = document.getElementById('inpTransferSvcField');
            if (svcField) {
                var isTransfer = e.target.value === 'تحويل الى مصلحة اخرى ';
                svcField.style.display = isTransfer ? 'block' : 'none';
                if (!isTransfer) {
                    var inp = document.getElementById('inpTransferSvcName');
                    if (inp) inp.value = '';
                }
            }
        }
    });

    // إخفاء الحقل عند إغلاق أي discharge
    var origPfmClose = window.pfmCloseDischarge;
    window.pfmCloseDischarge = function() {
        if (origPfmClose) origPfmClose();
        var f = document.getElementById('pfmTransferSvcField');
        if (f) f.style.display = 'none';
        var i = document.getElementById('pfmTransferSvcName');
        if (i) i.value = '';
        // reset radio
        var radios = document.querySelectorAll('input[name="pfmDischargeType"]');
        radios.forEach(function(r){ r.checked = false; });
    };

    var origInpClose = window.inpCloseDischarge;
    window.inpCloseDischarge = function() {
        if (origInpClose) origInpClose();
        var f = document.getElementById('inpTransferSvcField');
        if (f) f.style.display = 'none';
        var i = document.getElementById('inpTransferSvcName');
        if (i) i.value = '';
    };
})();

/* ══════════════════════════════════════════════════════════════
   2) تعديل الملف الطبي — Edit Mode Toggle
   يجعل حقول fileModalBody/patientFileContent قابلة للتعديل
   Front-End فقط — لا DB — لا AJAX
══════════════════════════════════════════════════════════════ */
(function() {
    var _editMode = false;

    window.pfmToggleEditMode = function() {
        _editMode = !_editMode;
        var btn = document.getElementById('pfmEditBtn');

        if (_editMode) {
            // وضعية التعديل: فعّل الحقول
            pfmSetFieldsEditable(true);
            // غيّر الزر لـ "حفظ التعديلات"
            if (btn) {
                btn.innerHTML = '<i class="fas fa-save"></i><span>حفظ التعديلات</span>';
                btn.style.background = 'linear-gradient(135deg,#10b981,#34d399)';
                btn.style.color = '#fff';
                btn.style.borderColor = 'transparent';
                btn.style.boxShadow = '0 4px 12px rgba(16,185,129,.25)';
            }
        } else {
            // وضعية الحفظ: أوقف التعديل
            pfmSetFieldsEditable(false);
            if (btn) {
                btn.innerHTML = '<i class="fas fa-edit"></i><span>تعديل الملف الطبي</span>';
                btn.style.background = 'rgba(14,165,233,.07)';
                btn.style.color = '#0ea5e9';
                btn.style.borderColor = 'rgba(14,165,233,.3)';
                btn.style.boxShadow = 'none';
            }
            // toast تأكيد
            if (typeof pfmShowToast === 'function') {
                pfmShowToast('✅ تم حفظ التعديلات في الملف الطبي', 'success');
            }
        }
    };

    function pfmSetFieldsEditable(editable) {
        // نبحث داخل الـ modal body فقط
        var containers = ['patientFileContent', 'fileModalBody', 'pfmBody'];
        containers.forEach(function(cId) {
            var container = document.getElementById(cId);
            if (!container) return;
            var fields = container.querySelectorAll('input, textarea, select');
            fields.forEach(function(field) {
                // تجاهل حقول الـ discharge نفسها
                if (field.id && (field.id.indexOf('pfmDischarge') === 0 || field.name === 'pfmDischargeType')) return;
                if (editable) {
                    field.removeAttribute('disabled');
                    field.removeAttribute('readonly');
                    field.style.borderColor = 'rgba(14,165,233,.4)';
                    field.style.background = '';
                    field.style.cursor = '';
                } else {
                    field.style.borderColor = '';
                    field.style.background = '';
                    field.style.cursor = '';
                }
            });
        });
    }

    // إعادة ضبط عند إغلاق الـ modal
    var origPfmClose = window.pfmClose;
    window.pfmClose = function() {
        _editMode = false;
        pfmSetFieldsEditable(false);
        var btn = document.getElementById('pfmEditBtn');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-edit"></i><span>تعديل الملف الطبي</span>';
            btn.style.cssText = '';
        }
        if (origPfmClose) origPfmClose();
    };
})();
/* ══ END EDIT MODE & TRANSFER FIELD ══ */

/* ════════════════════════════════════════════════════════════════
   QR SCANNER — Google Lens style  (Doctor Dashboard فقط)
   ----------------------------------------------------------------
   • إضافة فقط: لم يُحذف ولا يُعاد تسمية أي شيء أعلاه.
   • يعترض نقرة .scan-btn في طور الالتقاط (capture) ويوقف انتشارها،
     فلا يعمل كود المحاكاة القديم — مع بقائه موجوداً (غير محذوف).
   • يحمّل html5-qrcode عند أول استخدام، يفتح الكاميرا فعلياً،
     ثم يوجّه المحتوى المقروء:
        - رابط http/https      → يُفتح الرابط
        - بريد / هاتف          → يُعرض مع زر إجراء سريع
        - أي محتوى/نص آخر       → يُعرض كاملاً كما هو
   ════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  var HTML5QR_SRC = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';
  var libPromise = null;
  var scannerInstance = null;

  function loadLibrary() {
    if (window.Html5Qrcode) return Promise.resolve();
    if (libPromise) return libPromise;
    libPromise = new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = HTML5QR_SRC;
      s.onload = function () { resolve(); };
      s.onerror = function () { libPromise = null; reject(new Error('load_failed')); };
      document.head.appendChild(s);
    });
    return libPromise;
  }

  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  /* ---- نافذة الماسح (overlay) ---- */
  function buildOverlay() {
    var overlay = document.createElement('div');
    overlay.id = 'drQrOverlay';
    overlay.setAttribute('dir', 'rtl');
    overlay.style.cssText =
      'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.75);' +
      'display:flex;align-items:center;justify-content:center;padding:16px;';

    var box = document.createElement('div');
    box.style.cssText =
      'background:#fff;border-radius:16px;max-width:420px;width:100%;' +
      'box-shadow:0 20px 60px rgba(0,0,0,0.35);overflow:hidden;font-family:inherit;';

    var header = document.createElement('div');
    header.style.cssText =
      'display:flex;align-items:center;justify-content:space-between;' +
      'padding:14px 18px;border-bottom:1px solid #eee;';
    header.innerHTML = '<span style="font-weight:700;color:#111;">مسح كود QR</span>';

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.style.cssText =
      'border:none;background:#f3f4f6;width:34px;height:34px;border-radius:50%;' +
      'cursor:pointer;font-size:15px;color:#444;';
    closeBtn.onclick = closeOverlay;
    header.appendChild(closeBtn);

    var body = document.createElement('div');
    body.id = 'drQrBody';
    body.style.cssText = 'padding:16px;';

    var reader = document.createElement('div');
    reader.id = 'drQrReader';
    reader.style.cssText = 'width:100%;';

    var hint = document.createElement('p');
    hint.style.cssText = 'margin:12px 4px 4px;font-size:0.85rem;color:#6b7280;text-align:center;';
    hint.textContent = 'وجّه الكاميرا نحو كود QR';

    var fileWrap = document.createElement('div');
    fileWrap.style.cssText = 'margin-top:12px;text-align:center;';
    var fileLabel = document.createElement('label');
    fileLabel.style.cssText =
      'display:inline-flex;align-items:center;gap:8px;cursor:pointer;' +
      'font-size:0.85rem;color:var(--primary,#2563eb);font-weight:600;';
    fileLabel.innerHTML = '<i class="fas fa-image"></i><span>مسح من صورة</span>';
    var fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    fileInput.onchange = function () {
      if (fileInput.files && fileInput.files[0]) scanFromImage(fileInput.files[0]);
    };
    fileLabel.appendChild(fileInput);
    fileWrap.appendChild(fileLabel);

    body.appendChild(reader);
    body.appendChild(hint);
    body.appendChild(fileWrap);
    box.appendChild(header);
    box.appendChild(body);
    overlay.appendChild(box);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeOverlay(); });
    document.body.appendChild(overlay);
    return overlay;
  }

  function closeOverlay() {
    stopScanner().finally(function () {
      var o = document.getElementById('drQrOverlay');
      if (o) o.remove();
    });
  }

  function stopScanner() {
    if (scannerInstance) {
      var inst = scannerInstance;
      scannerInstance = null;
      try {
        return inst.stop().then(function () { try { inst.clear(); } catch (e) {} })
                         .catch(function () {});
      } catch (e) { return Promise.resolve(); }
    }
    return Promise.resolve();
  }

  /* ---- عرض النتيجة ---- */
  function showError(msg) {
    var body = document.getElementById('drQrBody');
    if (!body) return;
    body.innerHTML =
      '<div style="text-align:center;padding:8px 4px;">' +
        '<i class="fas fa-exclamation-triangle" style="color:#f59e0b;font-size:2.4rem;"></i>' +
        '<p style="margin-top:12px;color:#374151;">' + esc(msg) + '</p>' +
      '</div>';
  }

  function actionButton(label, icon, href) {
    return '<a href="' + esc(href) + '" target="_blank" rel="noopener" ' +
      'style="display:inline-flex;align-items:center;gap:8px;margin-top:14px;' +
      'padding:11px 22px;background:var(--primary,#2563eb);color:#fff;' +
      'text-decoration:none;border-radius:10px;font-weight:600;font-size:0.9rem;">' +
      '<i class="fas ' + icon + '"></i>' + esc(label) + '</a>';
  }

  function renderResult(text) {
    var body = document.getElementById('drQrBody');
    if (!body) return;
    var raw = String(text).trim();

    var typeLabel = 'محتوى';
    var actionHTML = '';
    var opened = false;

    if (/^https?:\/\//i.test(raw)) {
      typeLabel = 'رابط';
      try { window.open(raw, '_blank', 'noopener'); opened = true; } catch (e) {}
      actionHTML = actionButton('فتح الرابط', 'fa-up-right-from-square', raw);
    } else if (/^mailto:/i.test(raw)) {
      typeLabel = 'بريد إلكتروني';
      actionHTML = actionButton('إرسال بريد', 'fa-envelope', raw);
    } else if (/^tel:/i.test(raw)) {
      typeLabel = 'رقم هاتف';
      actionHTML = actionButton('اتصال', 'fa-phone', raw);
    } else if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(raw)) {
      typeLabel = 'بريد إلكتروني';
      actionHTML = actionButton('إرسال بريد', 'fa-envelope', 'mailto:' + raw);
    } else if (/^\+?[0-9][0-9\s\-()]{5,}$/.test(raw)) {
      typeLabel = 'رقم هاتف';
      actionHTML = actionButton('اتصال', 'fa-phone', 'tel:' + raw.replace(/[\s\-()]/g, ''));
    } else {
      typeLabel = 'نص';
    }

    var note = opened
      ? '<p style="margin:6px 0 0;font-size:0.8rem;color:#10b981;">تم فتح الرابط في نافذة جديدة</p>'
      : '';

    body.innerHTML =
      '<div style="text-align:center;">' +
        '<i class="fas fa-check-circle" style="color:#10b981;font-size:2.6rem;"></i>' +
        '<p style="margin:10px 0 4px;font-weight:700;color:#10b981;">تم المسح بنجاح</p>' +
        '<span style="display:inline-block;font-size:0.72rem;color:#6b7280;' +
          'background:#f3f4f6;padding:3px 10px;border-radius:20px;">' + esc(typeLabel) + '</span>' +
        '<div style="margin-top:14px;padding:12px 14px;background:#f9fafb;border:1px solid #eee;' +
          'border-radius:10px;word-break:break-word;white-space:pre-wrap;text-align:start;' +
          'color:#111;font-size:0.92rem;max-height:240px;overflow:auto;">' + esc(raw) + '</div>' +
        actionHTML + note +
        '<div><button type="button" id="drQrCopy" style="margin-top:10px;border:none;' +
          'background:transparent;color:var(--primary,#2563eb);cursor:pointer;font-size:0.82rem;' +
          'font-weight:600;"><i class="fas fa-copy"></i> نسخ المحتوى</button></div>' +
        '<div><button type="button" id="drQrAgain" style="margin-top:6px;border:none;' +
          'background:transparent;color:#6b7280;cursor:pointer;font-size:0.82rem;">' +
          '<i class="fas fa-rotate-right"></i> مسح كود آخر</button></div>' +
      '</div>';

    var copyBtn = document.getElementById('drQrCopy');
    if (copyBtn) copyBtn.onclick = function () {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(raw).then(function () {
          copyBtn.innerHTML = '<i class="fas fa-check"></i> تم النسخ';
        }).catch(function () {});
      }
    };
    var againBtn = document.getElementById('drQrAgain');
    if (againBtn) againBtn.onclick = function () { closeOverlay(); openScanner(); };
  }

  function onScanSuccess(decodedText) {
    stopScanner().finally(function () { renderResult(decodedText); });
  }

  /* ---- تشغيل الكاميرا / المسح من صورة ---- */
  function startCamera() {
    var inst = new Html5Qrcode('drQrReader');
    scannerInstance = inst;
    var config = { fps: 10, qrbox: { width: 230, height: 230 }, aspectRatio: 1.0 };
    inst.start({ facingMode: 'environment' }, config, onScanSuccess, function () {})
      .catch(function () {
        scannerInstance = null;
        showError('تعذّر فتح الكاميرا. تأكد من السماح بالوصول إلى الكاميرا، أو استخدم "مسح من صورة".');
      });
  }

  function scanFromImage(file) {
    loadLibrary()
      .then(function () { return stopScanner(); })
      .then(function () {
        var inst = new Html5Qrcode('drQrReader');
        inst.scanFile(file, true)
          .then(function (decodedText) { try { inst.clear(); } catch (e) {} renderResult(decodedText); })
          .catch(function () { showError('لم يتم العثور على كود QR في الصورة.'); });
      })
      .catch(function () { showError('تعذّر تحميل أداة المسح. تحقق من اتصال الإنترنت.'); });
  }
  function openScanner() {
    buildOverlay();
    loadLibrary().then(startCamera).catch(function () {
      showError('تعذّر تحميل أداة المسح. تحقق من اتصال الإنترنت.');
    });
  }

  /* ---- اعتراض نقرة .scan-btn في طور الالتقاط (capture) ---- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.scan-btn');
    if (!btn) return;
    e.preventDefault();
    e.stopImmediatePropagation();   // يمنع عمل كود المحاكاة القديم دون حذفه
    openScanner();
  }, true);
})();
/* ══ END QR SCANNER (Google Lens style) ══ */

/* ── (أُزيلت رقعة إعادة إظهار قائمة الأرشيف القديمة: لم تعد قائمة المرضى تُخفى، إذ يُعرض الملف الطبي الآن داخل Modal منبثق مستقل) ── */

/* ══ إصلاح: محرك البحث المباشر في أرشيف المرضى ══
   كانت الدالة سابقًا محصورة داخل IIFE ماسح QR، فلم تكن متاحة في النطاق العام
   الذي يستدعيها من oninput، ما أدى إلى ReferenceError وتعطّل البحث كليًا.
   هنا أُعيد تعريفها في النطاق العام لتعمل مع: oninput="searchArchivePatients(this.value)".
   تعتمد على اسم المريض الحقيقي داخل <h4> الموجود في .archive-item (patient_name). */
function searchArchivePatients(query) {
    query = (query || '').toLowerCase().trim();

    var list = document.querySelector('.archive-list');
    if (!list) return;

    var items = list.querySelectorAll('.archive-item');
    var visibleCount = 0;

    items.forEach(function (item) {
        var nameElement = item.querySelector('h4');
        var name = nameElement ? nameElement.textContent.toLowerCase().trim() : '';

        if (name.indexOf(query) !== -1) {
            item.style.display = '';   // إظهار العنصر المطابق (وعند حذف النص تظهر كل العناصر)
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // رسالة "لا توجد نتائج" — تُنشأ مرة واحدة وتظهر فقط عند وجود نص بحث بلا أي تطابق
    var emptyMsg = list.querySelector('#archiveNoResults');
    if (!emptyMsg) {
        emptyMsg = document.createElement('div');
        emptyMsg.id = 'archiveNoResults';
        emptyMsg.textContent = 'لا توجد نتائج';
        emptyMsg.style.textAlign = 'center';
        emptyMsg.style.padding = '20px';
        emptyMsg.style.color = '#6b7280';
        list.appendChild(emptyMsg);
    }
    emptyMsg.style.display = (visibleCount === 0 && query !== '') ? '' : 'none';
}

let medSidebarSignature = null; // توقيع آخر قائمة مرسومة (لمنع إعادة البناء والوميض)
async function loadFollowupPatients() {

    const response = await fetch('get_doctor_followup_patients.php');
    const patients = await response.json();

    const container = document.querySelector('.followup-sidebar');

    if (!container) return;

    // توقيع القائمة: نعيد بناء الـ DOM فقط إذا تغيّر المرضى فعلاً (إضافة/حذف/تعديل).
    // بذلك لا تُعاد النقاط للحالة الافتراضية كل 5 ثوانٍ، فيختفي الوميض.
    var medSidebarSig = patients.map(function (p) {
        return p.id + '|' + (p.has_account ? 1 : 0) + '|' + p.full_name + '|' + p.reason_exam;
    }).join('#');

    if (medSidebarSig !== medSidebarSignature || !container.querySelector('.followup-search')) {

        let html = `
        <div class="followup-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="ابحث عن مريض...">
        </div>
   ` ;

        patients.forEach(patient => {

            html += `
            <div class="patient-chat-item"
               onclick="openFollowupPatient(
    ${patient.id},
    ${patient.has_account ? 1 : 0},
    '${patient.full_name}',
    '${patient.reason_exam}'
)">

                <div class="patient-avatar dr-offline" data-uid="${patient.patient_id || 0}">
                    ${patient.full_name.charAt(0)}
                </div>

                <div class="patient-details">
                    <h4>${patient.full_name}</h4>
                    <span>${patient.reason_exam}</span>
                </div>

            </div>
       ` ;


        });

        container.innerHTML = html;
        medSidebarSignature = medSidebarSig;
    }
    // تحديث نقاط الاتصال في القائمة الجانبية اعتماداً على نفس نظام Online
    // الموجود (get_online_status.php). loadFollowupPatients تُستدعى دورياً أصلاً
    // فتتحدّث النقاط تلقائياً دون أي مؤقّت أو نظام جديد.
    drRefreshSidebarOnline();
    // إلغاء الفتح التلقائي لأول محادثة: عند أول دخول لا نختار أي مريض تلقائياً،
    // ولا نستدعي loadMedicalMessages، ونترك منطقة المحادثة فارغة مع رسالة
    // "اختر مريضًا من القائمة لبدء المحادثة" (عنصر emptyChat الظاهر افتراضياً).
    // يبقى currentFollowupPatient = null حتى يضغط الطبيب على مريض بنفسه عبر
    // openFollowupPatient. أي كتلة الإسناد التلقائي التالية أُزيلت عمداً:
    //   if (currentFollowupPatient === null && patients.length > 0) {
    //       currentFollowupPatient = patients[0].id;
    //   }

if (currentFollowupPatient !== null) {

    const patient = patients.find(p => p.id == currentFollowupPatient);

    if (patient) {

    document.getElementById("chatPatientName").textContent = patient.full_name;
    document.getElementById("chatPatientAvatar").textContent =
        patient.full_name.charAt(0);

}

}

}
document.querySelector(".chat-header").style.display = "none";
document.querySelector(".chat-input").style.display = "none";


function openFollowupPatient(recordId, hasAccount, patientName, patientInfo) {
console.log("openFollowupPatient تعمل");
    currentFollowupPatient = recordId;
    // مريض جديد: نُصفّر معرّفه ونجعل النقطة "غير متصل" حتى تصل بياناته الحقيقية
    currentPatientUserId = null;
    drRefreshPatientOnline();
const emptyChat = document.getElementById("emptyChat");

if (emptyChat) {
    emptyChat.style.display = "none";
}

document.querySelector(".chat-header").style.display = "flex";
    const chatMessages = document.querySelector('.chat-messages');
    const chatInput    = document.querySelector('.chat-input');
    
    chatInput.style.display = "none";
    const noAccountBox = document.getElementById('noAccountBox');
    const inviteBtn    = document.getElementById('invitePatientBtn');

    if (hasAccount == 1) {

        // إظهار الشات
        chatMessages.style.display = 'block';
        chatInput.style.display = 'flex';

        // إخفاء رسالة عدم وجود حساب
        noAccountBox.style.display = 'none';
        document.getElementById("chatPatientName").textContent = patientName;
        document.getElementById("chatPatientAvatar").textContent =
    patientName.charAt(0);


        console.log("فتح الشات للمريض:", recordId);


        loadMedicalMessages(recordId);
    } else {
document.querySelector(".chat-header").style.display = "none";
        // إخفاء الشات
        chatMessages.style.display = 'none';
        chatInput.style.display = 'none';

        // إظهار صندوق الدعوة
        noAccountBox.style.display = 'block';

        // ربط زر الدعوة بهذا المريض
        inviteBtn.onclick = function () {
            sendPatientInvitation(recordId);
        };

    }

}
function sendPatientInvitation(recordId) {

    if (!confirm("هل تريد إرسال دعوة لهذا المريض؟")) {
        return;
    }

    const formData = new FormData();
    formData.append("record_id", recordId);

    fetch("send_patient_invitation.php", {
        method: "POST",
        body: formData
    })
   .then(async response => {

    const text = await response.text();

    console.log(text);

    return JSON.parse(text);

})
    .then(data => {

        if (data.success) {

            alert("✅ " + data.message);

        } else {

            alert("❌ " + data.message);

        }

    })
 .catch(error => {

    console.error(error);

    alert(error);

});

}
// تحديث قائمة المرضى كل 5 ثوانٍ
setInterval(function () {
    loadFollowupPatients();
}, 5000);
setInterval(function () {

    if (currentFollowupPatient !== null) {
        loadMedicalMessages(currentFollowupPatient);
    }

}, 1500);
async function sendDoctorMessage() {

    const input = document.getElementById("doctorMessageInput");

    const message = input.value.trim();

    if (message === "") return;

    const recordId = currentFollowupPatient;

    const formData = new FormData();
    formData.append("record_id", recordId);
    formData.append("message", message);
    if (drReplyTo && drReplyTo.id) {
        formData.append("reply_to", drReplyTo.id);
    }
    if (window.drCancelReply) window.drCancelReply();

    try {

        const response = await fetch("send_medical_message.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {

            input.value = "";

            console.log("✅ تم حفظ الرسالة");

            // إظهار الرسالة مباشرة عند الطبيب بدون Refresh
            await loadMedicalMessages(recordId);

        } else {

            alert(result.message);

        }

    } catch (error) {

        console.error("فشل إرسال الرسالة:", error);

        // حتى في حال فشل قراءة الرد، نحدّث المحادثة من قاعدة البيانات
        // للتأكد من أن أي رسالة تم حفظها فعلياً تظهر مباشرة
        await loadMedicalMessages(recordId);

    }

}

/* ════════════════════════════════════════════════════════════
   دعم المرفقات في واجهة الطبيب (صور / PDF / Office / نصوص /
   مضغوطة / أي ملف)
════════════════════════════════════════════════════════════ */
function drAttr(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;')
        .replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function drFileIcon(ext) {
    if (ext === 'pdf') return { cls: 'fa-file-pdf', color: '#e11d48' };
    if (ext === 'doc' || ext === 'docx') return { cls: 'fa-file-word', color: '#2563eb' };
    if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return { cls: 'fa-file-excel', color: '#16a34a' };
    if (ext === 'ppt' || ext === 'pptx') return { cls: 'fa-file-powerpoint', color: '#ea580c' };
    if (ext === 'zip' || ext === 'rar' || ext === '7z') return { cls: 'fa-file-archive', color: '#a855f7' };
    if (ext === 'txt') return { cls: 'fa-file-lines', color: '#64748b' };
    return { cls: 'fa-file', color: '#64748b' };
}

function drAttachmentHtml(msg) {
    if (!msg || !msg.attachment_path) return '';
    var url = msg.attachment_path;
    var name = msg.attachment_name || 'ملف';
    var ext = (String(name).split('.').pop() || '').toLowerCase();
    var isImg = /^(jpg|jpeg|png|gif|webp)$/.test(ext) ||
                (String(msg.attachment_type || '').indexOf('image/') === 0);

    if (isImg) {
        return '<img src="' + drAttr(url) + '" alt="' + drAttr(name) + '" ' +
               'style="max-width:220px;max-height:220px;border-radius:10px;display:block;' +
               'cursor:zoom-in;margin-bottom:4px;" ' +
               'onclick="drOpenLightbox(\'' + drAttr(url) + '\')">';
    }

    var ic = drFileIcon(ext);
    return '<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;' +
             'background:rgba(0,0,0,.08);border-radius:10px;margin-bottom:4px;min-width:190px;">' +
             '<i class="fas ' + ic.cls + '" style="font-size:1.7rem;color:' + ic.color + ';"></i>' +
             '<div style="flex:1;min-width:0;overflow:hidden;">' +
               '<div style="font-size:.78rem;font-weight:700;white-space:nowrap;overflow:hidden;' +
                 'text-overflow:ellipsis;">' + drAttr(name) + '</div>' +
               '<a href="' + drAttr(url) + '" download="' + drAttr(name) + '" ' +
                 'style="font-size:.72rem;color:inherit;text-decoration:underline;">' +
                 'تحميل <i class="fas fa-download"></i></a>' +
             '</div>' +
           '</div>';
}

window.drOpenLightbox = function (url) {
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

var drFileInput = null;
function drEnsureFileInput() {
    if (drFileInput) return drFileInput;
    drFileInput = document.createElement('input');
    drFileInput.type = 'file';
    drFileInput.style.display = 'none';
    drFileInput.addEventListener('change', function () {
        if (drFileInput.files && drFileInput.files[0]) {
            sendDoctorFile(drFileInput.files[0]);
            drFileInput.value = '';
        }
    });
    document.body.appendChild(drFileInput);
    return drFileInput;
}

var drUploading = false;
function drShowUploading() {
    var box = document.querySelector('.chat-messages');
    if (!box) return;
    var t = document.createElement('div');
    t.id = 'drUploadingRow';
    t.style.cssText = 'text-align:center;font-size:.8rem;color:#94a3b8;padding:6px 12px;';
    t.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ رفع الملف...';
    box.appendChild(t);
    box.scrollTop = box.scrollHeight;
}
function drHideUploading() {
    var t = document.getElementById('drUploadingRow');
    if (t) t.remove();
}

async function sendDoctorFile(file) {
    if (currentFollowupPatient === null) return;
    if (drUploading) return;

    drUploading = true;
    drShowUploading();

    try {
        var fd = new FormData();
        fd.append('record_id', currentFollowupPatient);
        fd.append('message', '');
        fd.append('file', file);

        var resp = await fetch('upload_chat_file.php', { method: 'POST', body: fd });
        var res = await resp.json();

        if (res.success) {
            await loadMedicalMessages(currentFollowupPatient);
        } else {
            alert(res.message || 'فشل رفع الملف');
        }
    } catch (err) {
        console.error('فشل رفع الملف:', err);
        alert('فشل رفع الملف');
    } finally {
        drUploading = false;
        drHideUploading();
    }
}

function drWireAttachButton() {
    var btn = document.querySelector('.chat-input button:not(.send-btn)');
    if (btn && !btn.__drWired) {
        btn.__drWired = true;
        btn.addEventListener('click', function () {
            if (currentFollowupPatient === null) return;
            if (drUploading) return;
            drEnsureFileInput().click();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', drWireAttachButton);
} else {
    drWireAttachButton();
}

/* ════════════════════════════════════════════════════════════
   الرسائل الصوتية في واجهة الطبيب (تسجيل MediaRecorder + رفع + مشغّل)
   طبقة مستقلة لا تمسّ النص/المرفقات/المحاذاة/الألوان.
════════════════════════════════════════════════════════════ */
function drVoiceFmt(s) {
    s = Math.floor(s || 0);
    var m = Math.floor(s / 60), ss = s % 60;
    return m + ':' + (ss < 10 ? '0' : '') + ss;
}

function drVoiceHtml(msg) {
    if (!msg || !msg.voice_path) return '';
    return '<div class="med-voice" style="display:flex;align-items:center;gap:10px;min-width:200px;padding:2px 0;">' +
      '<button type="button" onclick="drVoiceToggle(this)" ' +
        'style="width:34px;height:34px;border-radius:50%;border:none;cursor:pointer;flex-shrink:0;' +
        'background:rgba(0,0,0,.14);display:flex;align-items:center;justify-content:center;color:inherit;">' +
        '<i class="fas fa-play"></i></button>' +
      '<input type="range" class="med-voice-seek" value="0" min="0" max="100" ' +
        'style="flex:1;height:4px;cursor:pointer;accent-color:currentColor;">' +
      '<span class="med-voice-time" style="font-size:.7rem;min-width:34px;text-align:center;">' +
        drVoiceFmt(msg.voice_duration) + '</span>' +
      '<audio class="med-voice-audio" src="' + drAttr(msg.voice_path) + '" preload="metadata"></audio>' +
      '</div>';
}

window.drVoiceToggle = function (btn) {
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
                timeEl.textContent = drVoiceFmt(audio.currentTime);
            }
        });
        audio.addEventListener('ended', function () {
            icon.className = 'fas fa-play';
            seek.value = 0;
            timeEl.textContent = drVoiceFmt(audio.duration || 0);
        });
        seek.addEventListener('input', function () {
            if (audio.duration && isFinite(audio.duration)) audio.currentTime = (seek.value / 100) * audio.duration;
        });
    }

    if (audio.paused) { audio.play(); icon.className = 'fas fa-pause'; }
    else { audio.pause(); icon.className = 'fas fa-play'; }
};

var drVoiceRec = { rec: null, chunks: [], stream: null, start: 0, timer: null, recording: false, busy: false };

async function drMicToggle() {
    if (currentFollowupPatient === null) return;
    if (drVoiceRec.recording) { drStopRecording(); return; }
    if (drVoiceRec.busy) return;
    try {
        var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        var rec = new MediaRecorder(stream);
        drVoiceRec.chunks = [];
        rec.ondataavailable = function (e) { if (e.data && e.data.size) drVoiceRec.chunks.push(e.data); };
        rec.onstop = function () { drUploadVoice(); };
        rec.start();
        drVoiceRec.rec = rec; drVoiceRec.stream = stream;
        drVoiceRec.recording = true; drVoiceRec.start = Date.now();
        drShowRecordingUI();
    } catch (e) {
        alert('تعذّر الوصول إلى الميكروفون');
    }
}

function drStopRecording() {
    if (drVoiceRec.rec && drVoiceRec.recording) {
        drVoiceRec.recording = false;
        try { drVoiceRec.rec.stop(); } catch (e) {}
        if (drVoiceRec.stream) drVoiceRec.stream.getTracks().forEach(function (t) { t.stop(); });
        drHideRecordingUI();
    }
}

async function drUploadVoice() {
    var duration = Math.round((Date.now() - drVoiceRec.start) / 1000);
    var type = (drVoiceRec.rec && drVoiceRec.rec.mimeType) || 'audio/webm';
    var blob = new Blob(drVoiceRec.chunks, { type: type });

    drVoiceRec.busy = true;
    drShowUploading();
    try {
        var fd = new FormData();
        fd.append('record_id', currentFollowupPatient);
        fd.append('duration', duration);
        fd.append('voice', blob, 'voice.webm');
        var resp = await fetch('upload_voice.php', { method: 'POST', body: fd });
        var res = await resp.json();
        if (res.success) await loadMedicalMessages(currentFollowupPatient);
        else alert(res.message || 'فشل إرسال التسجيل');
    } catch (e) {
        console.error('فشل إرسال التسجيل:', e);
    } finally {
        drVoiceRec.busy = false;
        drHideUploading();
    }
}

function drShowRecordingUI() {
    var btn = document.getElementById('doctorMicBtn');
    if (btn) { var ic = btn.querySelector('i'); if (ic) ic.className = 'fas fa-stop'; btn.style.color = '#ef4444'; }
    var box = document.querySelector('.chat-messages');
    if (box) {
        var t = document.createElement('div');
        t.id = 'drRecRow';
        t.style.cssText = 'text-align:center;font-size:.8rem;color:#ef4444;padding:6px 12px;';
        t.innerHTML = '<i class="fas fa-circle" style="font-size:.6rem;"></i> جارٍ التسجيل... <span id="drRecTime">0:00</span>';
        box.appendChild(t);
        box.scrollTop = box.scrollHeight;
        drVoiceRec.timer = setInterval(function () {
            var el = document.getElementById('drRecTime');
            if (el) el.textContent = drVoiceFmt((Date.now() - drVoiceRec.start) / 1000);
        }, 500);
    }
}

function drHideRecordingUI() {
    var btn = document.getElementById('doctorMicBtn');
    if (btn) { var ic = btn.querySelector('i'); if (ic) ic.className = 'fas fa-microphone'; btn.style.color = ''; }
    if (drVoiceRec.timer) { clearInterval(drVoiceRec.timer); drVoiceRec.timer = null; }
    var t = document.getElementById('drRecRow');
    if (t) t.remove();
}

function drWireMicButton() {
    var btn = document.getElementById('doctorMicBtn');
    if (btn && !btn.__drMicWired) {
        btn.__drMicWired = true;
        btn.addEventListener('click', drMicToggle);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', drWireMicButton);
} else {
    drWireMicButton();
}

/* ════════════════════════════════════════════════════════════
   إرفاق الملف الطبي (واجهة الطبيب فقط)
   نافذة تعرض الوثائق الطبية الموجودة أصلاً للمريض المفتوح، وزر "إرسال"
   يُدرج مرجع الوثيقة كمرفق (بلا رفع/نسخ). أنماط سطرية — بلا تعديل CSS.
════════════════════════════════════════════════════════════ */
function drMedFmtDate(s) {
    if (!s) return '';
    var d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    var dd = ('0' + d.getDate()).slice(-2), mm = ('0' + (d.getMonth() + 1)).slice(-2);
    return dd + '/' + mm + '/' + d.getFullYear();
}

function drCloseMedFilesModal() {
    var ov = document.getElementById('drMedFilesOverlay');
    if (ov) ov.remove();
}

async function drSendMedicalFile(type, btn) {
    if (currentFollowupPatient === null) return;
    if (btn) { btn.disabled = true; btn.textContent = '...'; }
    try {
        var fd = new FormData();
        fd.append('record_id', currentFollowupPatient);
        fd.append('type', type);
        var resp = await fetch('send_medical_file_ref.php', { method: 'POST', body: fd });
        var res = await resp.json();
        if (res.success) {
            drCloseMedFilesModal();
            await loadMedicalMessages(currentFollowupPatient);
        } else {
            alert(res.message || 'تعذّر إرسال الوثيقة');
            if (btn) { btn.disabled = false; btn.textContent = 'إرسال'; }
        }
    } catch (e) {
        alert('تعذّر إرسال الوثيقة');
        if (btn) { btn.disabled = false; btn.textContent = 'إرسال'; }
    }
}
window.drSendMedicalFile = drSendMedicalFile;
window.drCloseMedFilesModal = drCloseMedFilesModal;

async function drOpenMedFilesModal() {
    if (currentFollowupPatient === null) return;

    drCloseMedFilesModal();
    var ov = document.createElement('div');
    ov.id = 'drMedFilesOverlay';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:100000;display:flex;align-items:center;justify-content:center;';
    ov.addEventListener('click', function (e) { if (e.target === ov) drCloseMedFilesModal(); });

    var box = document.createElement('div');
    box.style.cssText = 'background:#fff;width:min(560px,92vw);max-height:82vh;overflow:auto;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);font-family:inherit;direction:rtl;';
    box.innerHTML =
        '<div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eef2f7;">' +
          '<strong style="font-size:1rem;color:#0f172a;">إرفاق الملف الطبي</strong>' +
          '<button onclick="drCloseMedFilesModal()" style="border:none;background:transparent;font-size:1.3rem;cursor:pointer;color:#64748b;">&times;</button>' +
        '</div>' +
        '<div id="drMedFilesBody" style="padding:14px 18px;">' +
          '<div style="text-align:center;color:#64748b;padding:20px;"><i class="fas fa-spinner fa-spin"></i> جارٍ التحميل...</div>' +
        '</div>';
    ov.appendChild(box);
    document.body.appendChild(ov);

    try {
        var resp = await fetch('get_patient_medical_files.php?record_id=' + currentFollowupPatient);
        var files = await resp.json();
        var body = document.getElementById('drMedFilesBody');
        if (!body) return;

        if (!files || !files.length) {
            body.innerHTML = '<div style="text-align:center;color:#64748b;padding:26px;">لا توجد ملفات طبية لهذا المريض.</div>';
            return;
        }

        var html = '';
        files.forEach(function (f) {
            html +=
                '<div style="display:flex;align-items:center;gap:12px;padding:12px 10px;border:1px solid #eef2f7;border-radius:10px;margin-bottom:8px;">' +
                  '<i class="fas fa-file-medical" style="font-size:1.6rem;color:#0ea5e9;"></i>' +
                  '<div style="flex:1;min-width:0;">' +
                    '<div style="font-weight:700;font-size:.9rem;color:#0f172a;">' + drAttr(f.name) + '</div>' +
                    '<div style="font-size:.72rem;color:#94a3b8;">وثيقة طبية' + (f.date ? ' • ' + drMedFmtDate(f.date) : '') + '</div>' +
                  '</div>' +
                  '<button onclick="drSendMedicalFile(\'' + drAttr(f.type) + '\', this)" ' +
                    'style="background:#0ea5e9;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-family:inherit;font-size:.82rem;">إرسال</button>' +
                '</div>';
        });
        body.innerHTML = html;
    } catch (e) {
        var b = document.getElementById('drMedFilesBody');
        if (b) b.innerHTML = '<div style="text-align:center;color:#ef4444;padding:24px;">تعذّر جلب الملفات الطبية.</div>';
    }
}
window.drOpenMedFilesModal = drOpenMedFilesModal;

function drWireMedFileButton() {
    var btn = document.getElementById('doctorMedFileBtn');
    if (btn && !btn.__drMedWired) {
        btn.__drMedWired = true;
        btn.addEventListener('click', function () {
            if (currentFollowupPatient === null) return;
            drOpenMedFilesModal();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', drWireMedFileButton);
} else {
    drWireMedFileButton();
}

/* ════════════════════════════════════════════════════════════
   حذف الرسالة (Soft Delete) في واجهة الطبيب — قائمة ⋮ ← "حذف
   للجميع" ← تأكيد. تظهر فقط على رسائل الطبيب نفسه.
════════════════════════════════════════════════════════════ */
function drCloseMsgMenus() {
    var mm = document.querySelectorAll('.dr-msg-menu');
    mm.forEach(function (x) { x.remove(); });
}

window.drMsgMenu = function (btn, id, isMine, editable, pinned) {
    drCloseMsgMenus();
    var menu = document.createElement('div');
    menu.className = 'dr-msg-menu';
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
        it.onclick = function () { drCloseMsgMenus(); onclick(); };
        menu.appendChild(it);
    };

    // الرد على أي رسالة
    addItem('<i class="fas fa-reply" style="margin-left:6px;color:#0ea5e9;"></i> الرد على الرسالة', function () { drStartReply(id); });

    // تعديل (لصاحب الرسالة النصية فقط)
    if (editable && isMine) {
        addItem('<i class="fas fa-pen" style="margin-left:6px;color:#0ea5e9;"></i> تعديل', function () { drStartEdit(id); });
    }

    // تثبيت / إلغاء تثبيت (الطبيب على أي رسالة)
    if (pinned) {
        addItem('<i class="fas fa-thumbtack" style="margin-left:6px;color:#f59e0b;"></i> إلغاء تثبيت الرسالة', function () { drPinMessage(id, 'unpin'); });
    } else {
        addItem('<i class="fas fa-thumbtack" style="margin-left:6px;color:#0ea5e9;"></i> تثبيت الرسالة', function () { drPinMessage(id, 'pin'); });
    }

    // حذف (لصاحب الرسالة فقط)
    if (isMine) {
        addItem('<i class="fas fa-trash" style="margin-left:6px;color:#ef4444;"></i> حذف للجميع', function () { drConfirmDelete(id); });
    }

    document.body.appendChild(menu);
    var r = btn.getBoundingClientRect();
    menu.style.top = (r.bottom + 4) + 'px';
    menu.style.left = Math.max(8, r.left - 40) + 'px';
    setTimeout(function () { document.addEventListener('click', drCloseMsgMenus, { once: true }); }, 0);
};

async function drPinMessage(id, action) {
    try {
        var fd = new FormData();
        fd.append('message_id', id);
        fd.append('action', action);
        var resp = await fetch('pin_medical_message.php', { method: 'POST', body: fd });
        var res = await resp.json();
        if (res.success) { await loadMedicalMessages(currentFollowupPatient); }
        else alert(res.message || 'تعذّر تحديث التثبيت');
    } catch (e) { alert('تعذّر تحديث التثبيت'); }
}

/* شريط الرسالة المثبتة أعلى محادثة الطبيب — يُحدَّث من مصفوفة الرسائل في كل
   تحديث (Polling)، ويتجاهل الرسالة المحذوفة فيختفي تلقائياً عند حذف المثبتة. */
function drUpdatePinnedBar(messages) {
    var box = document.querySelector('.chat-messages');
    if (!box) return;

    var pinned = null;
    if (messages) {
        for (var i = 0; i < messages.length; i++) {
            if (messages[i].is_pinned == 1 && messages[i].is_deleted != 1) { pinned = messages[i]; break; }
        }
    }

    var bar = document.getElementById('drPinnedBar');
    if (!pinned) { if (bar) bar.remove(); return; }

    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'drPinnedBar';
        bar.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 14px;' +
            'background:rgba(14,165,233,.08);border-bottom:1px solid #e2e8f0;cursor:pointer;font-family:inherit;flex-shrink:0;';
        box.parentNode.insertBefore(bar, box);
    }

    var hasAttach = pinned.attachment_path && String(pinned.attachment_path).trim() !== '';
    var hasVoice = pinned.voice_path && String(pinned.voice_path).trim() !== '';
    var preview = (pinned.message && String(pinned.message).trim() !== '') ? String(pinned.message)
        : (hasVoice ? 'رسالة صوتية' : (hasAttach ? (pinned.attachment_name || 'مرفق') : ''));

    bar.innerHTML =
        '<i class="fas fa-thumbtack" style="color:#0ea5e9;font-size:.8rem;flex-shrink:0;"></i>' +
        '<div style="flex:1;min-width:0;">' +
          '<div style="font-size:.68rem;font-weight:800;color:#0ea5e9;">رسالة مثبتة</div>' +
          '<div style="font-size:.76rem;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
            drAttr(preview) + '</div>' +
        '</div>';
    bar.onclick = function () { drScrollToMsg(pinned.id); };
}

function drScrollToMsg(id) {
    var el = document.querySelector('.bubble[data-mid="' + id + '"]');
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    var prev = el.style.boxShadow;
    el.style.transition = 'box-shadow .3s';
    el.style.boxShadow = '0 0 0 3px rgba(14,165,233,.55)';
    setTimeout(function () { el.style.boxShadow = prev; }, 1200);
}

/* تحرير الرسالة النصية داخل نفس الفقاعة (حفظ/إلغاء) */
window.drStartEdit = function (id) {
    var bubble = document.querySelector('.bubble[data-mid="' + id + '"]');
    if (!bubble || bubble.__editing) return;
    bubble.__editing = true;

    var original = bubble.getAttribute('data-text');
    if (original === null) original = bubble.textContent.trim();
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
    save.onclick = function () { drDoEdit(id, ta.value, restore); };

    bar.appendChild(cancel); bar.appendChild(save);
    bubble.appendChild(ta); bubble.appendChild(bar);
    ta.focus();
};

async function drDoEdit(id, text, restore) {
    text = (text || '').trim();
    if (text === '') { restore(); return; }
    try {
        var fd = new FormData();
        fd.append('message_id', id);
        fd.append('message', text);
        var resp = await fetch('edit_medical_message.php', { method: 'POST', body: fd });
        var res = await resp.json();
        if (res.success) { await loadMedicalMessages(currentFollowupPatient); }
        else { alert(res.message || 'تعذّر تعديل الرسالة'); restore(); }
    } catch (e) { alert('تعذّر تعديل الرسالة'); restore(); }
}

function drConfirmDelete(id) {
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
    del.onclick = function () { ov.remove(); drDoDelete(id); };
    actions.appendChild(cancel); actions.appendChild(del);
    box.appendChild(actions); ov.appendChild(box); document.body.appendChild(ov);
    ov.addEventListener('click', function (e) { if (e.target === ov) ov.remove(); });
}

async function drDoDelete(id) {
    try {
        var fd = new FormData();
        fd.append('message_id', id);
        var resp = await fetch('delete_medical_message.php', { method: 'POST', body: fd });
        var res = await resp.json();
        if (res.success) { await loadMedicalMessages(currentFollowupPatient); }
        else alert(res.message || 'تعذّر حذف الرسالة');
    } catch (e) { alert('تعذّر حذف الرسالة'); }
}

/* ════════════════════════════════════════════════════════════
   الرد على رسالة (Reply) — اقتباس أعلى الفقاعة + شريط أعلى الكتابة.
════════════════════════════════════════════════════════════ */
function drFindMsgById(id) {
    if (!drCurrentMessages) return null;
    for (var i = 0; i < drCurrentMessages.length; i++) {
        if (String(drCurrentMessages[i].id) === String(id)) return drCurrentMessages[i];
    }
    return null;
}

function drMsgPreview(msg) {
    if (!msg) return null;
    if (msg.is_deleted == 1) return null;
    if (msg.voice_path && String(msg.voice_path).trim() !== '') return '🎤 رسالة صوتية';
    if (msg.attachment_path && String(msg.attachment_path).trim() !== '') {
        var name = msg.attachment_name || '';
        var ext = (String(name).split('.').pop() || '').toLowerCase();
        var isImg = /^(jpg|jpeg|png|gif|webp)$/.test(ext) || (String(msg.attachment_type || '').indexOf('image/') === 0);
        return isImg ? '🖼️ صورة' : '📄 ملف';
    }
    var t = msg.message ? String(msg.message) : '';
    return t.length > 80 ? (t.slice(0, 80) + '…') : t;
}

function drReplyAuthor(original) {
    if (doctorMsgIsFromDoctor(original)) return 'أنت';
    var el = document.getElementById('chatPatientName');
    return (el && el.textContent.trim()) ? el.textContent.trim() : 'المريض';
}

function drReplyQuoteHtml(msg) {
    if (!msg.reply_to_message_id) return '';
    var original = drFindMsgById(msg.reply_to_message_id);
    var preview = drMsgPreview(original);
    var inner;
    if (!original || preview === null) {
        inner = '<div style="font-size:.72rem;color:#94a3b8;">🚫 هذه الرسالة لم تعد متوفرة</div>';
    } else {
        inner =
            '<div style="font-size:.68rem;font-weight:800;color:#0ea5e9;">' +
              '<i class="fas fa-reply" style="font-size:.6rem;margin-left:3px;"></i>' + drAttr(drReplyAuthor(original)) + '</div>' +
            '<div style="font-size:.72rem;color:inherit;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px;">' +
              drAttr(preview) + '</div>';
    }
    return '<div class="dr-reply-quote" onclick="drScrollToMsg(\'' + msg.reply_to_message_id + '\')" ' +
        'style="cursor:pointer;border-right:3px solid rgba(14,165,233,.7);padding:4px 8px;margin-bottom:5px;' +
        'background:rgba(0,0,0,.06);border-radius:6px;">' + inner + '</div>';
}

var drReplyTo = null;

window.drStartReply = function (id) {
    var original = drFindMsgById(id);
    if (!original) return;
    var preview = drMsgPreview(original);
    drReplyTo = { id: id };
    drShowReplyBar(drReplyAuthor(original), preview === null ? 'رسالة' : preview);
    var input = document.getElementById('doctorMessageInput');
    if (input) input.focus();
};

window.drCancelReply = function () {
    drReplyTo = null;
    var bar = document.getElementById('drReplyBar');
    if (bar) bar.remove();
};

function drShowReplyBar(author, preview) {
    var row = document.querySelector('.chat-input');
    if (!row) return;
    var bar = document.getElementById('drReplyBar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'drReplyBar';
        bar.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 14px;' +
            'background:rgba(14,165,233,.08);border-top:1px solid #e2e8f0;font-family:inherit;direction:rtl;';
        row.parentNode.insertBefore(bar, row);
    }
    bar.innerHTML =
        '<i class="fas fa-reply" style="color:#0ea5e9;flex-shrink:0;"></i>' +
        '<div style="flex:1;min-width:0;">' +
          '<div style="font-size:.7rem;font-weight:800;color:#0ea5e9;">الرد على ' + drAttr(author) + '</div>' +
          '<div style="font-size:.74rem;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + drAttr(preview) + '</div>' +
        '</div>' +
        '<button type="button" onclick="drCancelReply()" style="border:none;background:transparent;color:#94a3b8;font-size:1.1rem;cursor:pointer;flex-shrink:0;">&times;</button>';
}

// عدّاد لتمييز كل طلب تحميل رسائل عن الذي قبله.
// السبب الحقيقي للمشكل: هناك مؤقّت (setInterval) يستدعي loadMedicalMessages كل 5 ثوانٍ
// بالتوازي مع الاستدعاء الذي يحدث مباشرة بعد إرسال رسالة (sendDoctorMessage).
// عندما يصل رد الطلب "القديم" (الذي انطلق قبل الإرسال ولا يحتوي على الرسالة الجديدة)
// متأخراً بعد رد الطلب "الجديد"، كان يقوم باستبدال الرسائل المعروضة بنسخة قديمة/فارغة
// فتظهر المحادثة فارغة أو تختفي الرسالة المُرسلة حديثاً — رغم أن البيانات في القاعدة صحيحة.
// الحل: تجاهل أي رد يصل من طلب ليس هو آخر طلب تم إطلاقه، وتجاهل أي رد وصل بعد
// أن يكون الطبيب قد بدّل المحادثة إلى مريض آخر.
let medicalMessagesRequestId = 0;

// مفتاح ثابت لكل رسالة (يعتمد على id الحقيقي القادم من قاعدة البيانات عبر
// get_medical_messages.php) يُستخدم لمقارنة ما هو معروض حالياً بما تم جلبه
// حديثاً، حتى نضيف الرسائل الجديدة فقط دون إعادة بناء المحادثة بالكامل.
function medMsgKey(msg) {
    return "id:" + msg.id + (msg.is_deleted == 1 ? ":d" : "") + (msg.is_edited == 1 ? ":e" : "") + (msg.is_pinned == 1 ? ":p" : "") + (msg.is_read == 1 ? ":r" : "");
}

// اتجاه ولون الرسالة يُبنيان فقط من صاحب الرسالة الحقيقي المستنتَج من بيانات
// الصف نفسه، بترتيب موثوقية تنازلي: (1) sender_id = doctor_id => الطبيب،
// (2) sender_id = patient_user_id => المريض، (3) خطة بديلة sender_role
// (مُطبَّعة). لا اعتماد على window.CURRENT_USER_ID (غير معرّف في صفحة الطبيب
// أصلاً) ولا على الترتيب ولا على المحادثة النشطة. في واجهة الطبيب: رسالة
// الطبيب دائماً يمين ولون الطبيب، ورسالة المريض دائماً يسار ولون المريض.
function doctorMsgIsFromDoctor(msg) {
    var sid = (msg.sender_id === null || msg.sender_id === undefined) ? null : String(msg.sender_id);
    var did = (msg.doctor_id === null || msg.doctor_id === undefined) ? null : String(msg.doctor_id);
    var pid = (msg.patient_user_id === null || msg.patient_user_id === undefined) ? null : String(msg.patient_user_id);

    if (sid !== null && did !== null && sid === did) return true;
    if (sid !== null && pid !== null && sid === pid) return false;

    return String(msg.sender_role || "").trim().toLowerCase() === "doctor";
}

function buildDoctorMsgRow(msg) {
    // في واجهة الطبيب تكون الرسالة "لي" (يمين، لون الطبيب) عندما تكون من الطبيب.
    const isMine = doctorMsgIsFromDoctor(msg);

    const wrapper = document.createElement("div");
    wrapper.style.display = "flex";
    wrapper.style.width = "100%";
    wrapper.style.direction = "ltr";
    wrapper.style.justifyContent = isMine ? "flex-end" : "flex-start";

    const bubbleColor = isMine
        ? "background-color:var(--primary, #2f6fed); color:#ffffff;"
        : "background-color:#ffffff; color:#111111;";

    // ملاحظة: لا نستخدم class باسم "doctor"/"patient" لأن CSS الموجود يربط
    // .message.doctor بقاعدة margin-left:auto + margin-right:auto التي كانت
    // تُلغي تأثير justify-content الخاص بـ wrapper (وهي السبب الحقيقي لتبدّل
    // جهة ولون الرسائل بعد الإرسال/الـ Polling، خصوصاً عند تغيّر عرض الحاوية
    // بسبب ظهور/اختفاء شريط التمرير مع الرسائل الجديدة). نستخدم class ثابت
    // "message" فقط، ونضبط alignItems مباشرة هنا بالاعتماد فقط على isMine.
    const row = document.createElement("div");
    row.className = "message";
    row.style.direction = "rtl";
    row.style.alignItems = isMine ? "flex-end" : "flex-start";

    var drBubbleInner;
    if (msg.is_deleted == 1) {
        drBubbleInner = '<span style="opacity:.75;font-style:italic;"><i class="fas fa-ban" style="font-size:.72rem;margin-left:5px;"></i>تم حذف هذه الرسالة</span>';
    } else {
        var drAttachHtml = drAttachmentHtml(msg);
        var drVoiceHtmlStr = drVoiceHtml(msg);
        drBubbleInner = drReplyQuoteHtml(msg) + drVoiceHtmlStr + drAttachHtml + (msg.message ? msg.message : '');
    }

    // رسالة قابلة للتعديل = نصية فقط (بلا مرفق/صوت) وغير محذوفة وملك الطبيب
    var drHasAttach = msg.attachment_path && String(msg.attachment_path).trim() !== '';
    var drHasVoice  = msg.voice_path && String(msg.voice_path).trim() !== '';
    var drEditable = (msg.is_deleted != 1 && isMine && msg.id !== undefined && msg.id !== null &&
                      !drHasAttach && !drHasVoice && !!(msg.message && String(msg.message).trim() !== ''));
    var drMidAttr = (msg.id !== undefined && msg.id !== null) ? (' data-mid="' + msg.id + '"') : '';
    var drTextAttr = drEditable ? (' data-text="' + drAttr(msg.message) + '"') : '';

    row.innerHTML = `
                <div class="bubble" style="${bubbleColor}"${drMidAttr}${drTextAttr}>
                    ${drBubbleInner}
                </div>

                <span class="msg-time">
                    ${msg.created_at.substring(11,16)}${msg.is_edited == 1 ? ' · تم التعديل' : ''}${isMine ? (msg.is_read == 1 ? ' <span style="color:#38bdf8;">✓✓</span>' : ' <span style="opacity:.7;">✓✓</span>') : ''}
                </span>
    `;

    // زر الخيارات (⋮): يظهر على كل الرسائل غير المحذوفة في واجهة الطبيب عند
    // Hover. محتوى القائمة يتحدّد حسب الملكية (تعديل/حذف لصاحبها)، والتثبيت
    // متاح للطبيب على أي رسالة.
    if (msg.is_deleted != 1 && msg.id !== undefined && msg.id !== null) {
        row.style.position = 'relative';
        var drDelBtn = document.createElement('button');
        drDelBtn.type = 'button';
        drDelBtn.innerHTML = '<i class="fas fa-ellipsis-v"></i>';
        drDelBtn.style.cssText = 'position:absolute;top:0;left:-4px;border:none;background:transparent;' +
            'color:#94a3b8;cursor:pointer;opacity:0;transition:opacity .15s;padding:2px 6px;font-size:.78rem;';
        drDelBtn.setAttribute('onclick', "drMsgMenu(this, '" + msg.id + "', " + (isMine ? '1' : '0') + ", " + (drEditable ? '1' : '0') + ", " + (msg.is_pinned == 1 ? '1' : '0') + ")");
        row.appendChild(drDelBtn);
        wrapper.addEventListener('mouseenter', function () { drDelBtn.style.opacity = '1'; });
        wrapper.addEventListener('mouseleave', function () { drDelBtn.style.opacity = '0'; });
    }

    wrapper.appendChild(row);
    return wrapper;
}

async function loadMedicalMessages(recordId) {

    const requestId = ++medicalMessagesRequestId;

    const response = await fetch("get_medical_messages.php?record_id=" + recordId);

    const messages = await response.json();

    // تجاهل الرد إذا وصل طلب أحدث منه (Race Condition بين الإرسال والتحديث الدوري)
    if (requestId !== medicalMessagesRequestId) {
        return;
    }

    // تجاهل الرد إذا لم يعد الطبيب فاتحاً نفس محادثة هذا المريض
    if (recordId !== currentFollowupPatient) {
        return;
    }

    // التقاط user_id للمريض من الرسائل (كلها تحمل نفس patient_user_id) لاستعماله
    // في حالة الاتصال الحقيقية. عند تغيّر المريض نُحدّث النقطة فوراً.
    if (messages.length > 0 && messages[0].patient_user_id) {
        var pid = parseInt(messages[0].patient_user_id, 10) || null;
        if (pid !== currentPatientUserId) {
            currentPatientUserId = pid;
            drRefreshPatientOnline();
        }
    }

    const container = document.querySelector(".chat-messages");

    if (!container) return;

    drCurrentMessages = messages; // متاحة لبناء اقتباس الرد داخل buildDoctorMsgRow

    // تحديث شريط الرسالة المثبتة في كل تحديث (حتى لو لم تتغيّر الصفوف)
    drUpdatePinnedBar(messages);

    // Read Receipts: هذه محادثة المريض مفتوحة أمام الطبيب الآن، فنعلّم رسائل
    // المريض الموجَّهة إلى الطبيب كمقروءة (فقط عند وجود غير مقروء موجَّه إليه).
    (function () {
        for (var i = 0; i < messages.length; i++) {
            if (messages[i].is_read == 0 && String(messages[i].receiver_id) === String(messages[i].doctor_id)) {
                fetch("mark_messages_read.php?record_id=" + recordId).catch(function () {});
                break;
            }
        }
    })();

    /* ── عرض بدون Flicker ────────────────────────────────────
       بدل مسح المحادثة كاملة (container.innerHTML = html) في كل تحديث، نقارن
       الرسائل الجديدة بما هو معروض حالياً عبر مفاتيحها (id): إذا كانت النتيجة
       الجديدة هي نفس المعروض بالضبط لا نلمس الـ DOM إطلاقاً، وإذا كانت مجرد
       رسائل جديدة أُضيفت في النهاية نضيفها فقط دون مسح التاريخ السابق، ونحافظ
       على موضع التمرير الحالي إلا إذا كان الطبيب أصلاً قرب آخر المحادثة أو
       عند فتح محادثة مريض آخر تماماً. */
    const newKeys = messages.map(medMsgKey);
    const sameConversation = (recordId === medLastRenderedRecordId);

    let sameAsRendered = sameConversation && newKeys.length === medLastRenderedKeys.length;
    if (sameAsRendered) {
        for (let i = 0; i < newKeys.length; i++) {
            if (newKeys[i] !== medLastRenderedKeys[i]) { sameAsRendered = false; break; }
        }
    }

    if (sameAsRendered) {
        // لا تغيير فعلي في المحادثة: لا نلمس الـ DOM إطلاقاً (يمنع أي وميض)
        return;
    }

    const wasNearBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) < 60;

    let isPureAppend = false;
    if (sameConversation && newKeys.length >= medLastRenderedKeys.length) {
        isPureAppend = true;
        for (let i = 0; i < medLastRenderedKeys.length; i++) {
            if (medLastRenderedKeys[i] !== newKeys[i]) { isPureAppend = false; break; }
        }
    }

    if (isPureAppend) {
        // إضافة الرسائل الجديدة فقط في نهاية المحادثة دون مسح ما قبلها
        const fragment = document.createDocumentFragment();
        for (let i = medLastRenderedKeys.length; i < messages.length; i++) {
            fragment.appendChild(buildDoctorMsgRow(messages[i]));
        }
        container.appendChild(fragment);
    } else {
        // تغيّر حقيقي (فتح محادثة مريض آخر، أو بيانات مختلفة فعلياً) — إعادة بناء كاملة مرة واحدة فقط
        container.innerHTML = "";
        const fragment = document.createDocumentFragment();
        messages.forEach(msg => fragment.appendChild(buildDoctorMsgRow(msg)));
        container.appendChild(fragment);
    }

    medLastRenderedRecordId = recordId;
    medLastRenderedKeys = newKeys;
    medLastRenderedPendingCount = 0;

    if (wasNearBottom || !sameConversation) {
        container.scrollTop = container.scrollHeight;
    }

}
/* ════════════════════════════════════════════════════════════
   نبض حالة الاتصال (Online) — يحدّث last_seen للطبيب كل 30 ثانية
   ليظهر "متصلاً" للمريض اعتماداً على بيانات حقيقية. لا يغيّر أي
   شيء آخر في واجهة الطبيب.
════════════════════════════════════════════════════════════ */
function drHeartbeat() {
    fetch("heartbeat.php", { method: "POST" }).catch(function () {});
}
drHeartbeat();
setInterval(drHeartbeat, 30000);

/* ════════════════════════════════════════════════════════════
   حالة اتصال المريض الحقيقية في هيدر الطبيب — تبدّل لون النقطة
   على صورة المريض اعتماداً على last_seen (عبر get_online_status.php).
   خضراء إذا كان المريض متصلاً، رمادية إذا لم يكن. لا تضيف أي عنصر
   جديد للهيدر ولا تعرض تخصصاً — فقط تجعل النقطة الموجودة حقيقية.
════════════════════════════════════════════════════════════ */
/* يعرض نص "متصل / غير متصل" تحت اسم المريض في هيدر المحادثة — مثل واجهة
   المريض تماماً. يستفيد من تنسيق ".chat-user span" الموجود أصلاً (لا CSS جديد).
   يُنشأ عنصر النص مرة واحدة بجانب اسم المريض. */
function drSetHeaderStatus(known, online) {
    var nameEl = document.getElementById("chatPatientName");
    if (!nameEl) return;
    var st = document.getElementById("chatPatientStatus");
    if (!st) {
        st = document.createElement("span");
        st.id = "chatPatientStatus";
        nameEl.parentNode.appendChild(st); // يوضع تحت الاسم مباشرة
    }
    if (!known) { st.textContent = ""; return; }
    st.textContent = online ? "متصل" : "غير متصل";
    st.style.color = online ? "#16a34a" : "#94a3b8";
}

async function drRefreshPatientOnline() {
    var avatar = document.getElementById("chatPatientAvatar");
    if (!avatar) return;

    if (!currentPatientUserId) {
        avatar.classList.add("dr-offline"); // لا معرّف => رمادية
        drSetHeaderStatus(false, false);    // لا نص قبل فتح محادثة مريض
        return;
    }

    try {
        var resp = await fetch("get_online_status.php?user_id=" + currentPatientUserId);
        var map = await resp.json();
        var online = !!map[String(currentPatientUserId)];
        if (online) avatar.classList.remove("dr-offline");
        else avatar.classList.add("dr-offline");
        drSetHeaderStatus(true, online);    // نفس نص واجهة المريض
    } catch (e) {}
}
setInterval(drRefreshPatientOnline, 30000);

/* تحديث نقاط الاتصال في قائمة المرضى الجانبية اعتماداً على نفس نظام Online
   (get_online_status.php + الحقل is_online). كل صورة تحمل data-uid = user_id
   المريض. من لا حساب له (uid=0) تبقى نقطته رمادية. لا API جديد ولا مؤقّت جديد. */
async function drRefreshSidebarOnline() {
    var avatars = document.querySelectorAll('.followup-sidebar .patient-avatar[data-uid]');
    if (!avatars.length) return;

    var ids = [];
    avatars.forEach(function (a) {
        var uid = parseInt(a.getAttribute('data-uid'), 10) || 0;
        // بلا حساب => رمادية (لا نلمس الـ DOM إن كانت رمادية أصلاً)
        if (uid <= 0) {
            if (!a.classList.contains('dr-offline')) a.classList.add('dr-offline');
        } else if (ids.indexOf(uid) === -1) {
            ids.push(uid);
        }
    });

    if (!ids.length) return;

    try {
        var resp = await fetch('get_online_status.php?user_ids=' + ids.join(','));
        var map = await resp.json();
        avatars.forEach(function (a) {
            var uid = parseInt(a.getAttribute('data-uid'), 10) || 0;
            if (uid <= 0) return;
            var wantOffline = !map[String(uid)];
            var isOffline = a.classList.contains('dr-offline');
            // نغيّر اللون فقط إذا اختلفت الحالة فعلاً — وإلا لا نلمس الـ DOM إطلاقاً
            if (wantOffline !== isOffline) {
                a.classList.toggle('dr-offline', wantOffline);
            }
        });
    } catch (e) {}
}
/* ════════════════════════════════════════════════════════════
   استشارة جديدة — تحميل الأطباء (داخلي/خارجي) والمرضى، وتبديل
   قائمة الأطباء عند تغيير نطاق الاستشارة بدون إعادة تحميل الصفحة.
════════════════════════════════════════════════════════════ */
let cnsFormData = { internal_doctors: [], external_doctors: [], patients: [] };

function cnsFillDoctorSelect(list) {
    const doctorSelect = document.getElementById("consultationDoctor");
    if (!doctorSelect) return;

    const previousValue = doctorSelect.value;
    doctorSelect.innerHTML = '<option value="">اختر الطبيب</option>';

    (list || []).forEach(function (doctor) {
        const option = document.createElement("option");
        option.value = doctor.id;
        option.textContent = doctor.full_name;
        doctorSelect.appendChild(option);
    });

    if (previousValue && list && list.some(d => String(d.id) === String(previousValue))) {
        doctorSelect.value = previousValue;
    }
}

function cnsGetSelectedScope() {
    const checked = document.querySelector('input[name="cnc-scope"]:checked');
    return checked ? checked.value : 'internal';
}

function cnsRenderDoctorsForCurrentScope() {
    const scope = cnsGetSelectedScope();
    if (scope === 'external') {
        cnsFillDoctorSelect(cnsFormData.external_doctors);
    } else {
        cnsFillDoctorSelect(cnsFormData.internal_doctors);
    }
}

document.addEventListener("DOMContentLoaded", async function () {

    try {

        const response = await fetch("get_consultation_form_data.php");
        const data = await response.json();

        if (!data.success) return;

        cnsFormData.internal_doctors = data.internal_doctors || [];
        cnsFormData.external_doctors = data.external_doctors || [];
        cnsFormData.patients = data.patients || [];

        /* ===== قائمة الأطباء (حسب النطاق الحالي) ===== */
        cnsRenderDoctorsForCurrentScope();

        /* ===== قائمة المرضى ===== */
        const patientSelect = document.getElementById("consultationPatient");
        if (patientSelect) {
            data.patients.forEach(function (patient) {
                const option = document.createElement("option");
                option.value = patient.id;
                option.textContent = patient.full_name;
                patientSelect.appendChild(option);
            });
        }

        /* ===== تبديل قائمة الأطباء عند تغيير نطاق الاستشارة ===== */
        document.querySelectorAll('input[name="cnc-scope"]').forEach(function (radio) {
            radio.addEventListener("change", cnsRenderDoctorsForCurrentScope);
        });

    } catch (e) {

        console.error(e);

    }

});

document.addEventListener("DOMContentLoaded", function () {

    const createBtn = document.getElementById("createConsultationBtn");

    if (!createBtn) return;


createBtn.addEventListener("click", function () {

    const title = document.getElementById("consultationTitle").value.trim();
    const description = document.getElementById("consultationDescription").value.trim();
    const hidePatientCheckbox = document.querySelector('.cnc-privacy-row input[type="checkbox"]');

    fetch("create_consultation.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
       body: JSON.stringify({

    patient_id: document.getElementById("consultationPatient").value,

    assigned_doctor_id: document.getElementById("consultationDoctor").value,

    consultation_scope: document.querySelector('input[name="cnc-scope"]:checked')?.value,

    consultation_type: document.querySelector('input[name="cnc-type"]:checked')?.value,

    title: title,

    description: description,

    priority: document.querySelector('input[name="cnc-priority"]:checked')?.value,

    hide_patient_identity: hidePatientCheckbox?.checked ? 1 : 0

})
    })
    .then(response => response.json())
    .then(data => {
        console.log(data);
    })
    .catch(error => {
        console.error(error);
    });

});

    });
/* ════════════════════════════════════════════════════════════
   بحث حي عن الأطباء (استشارة خارجية) — بطاقات بدل القائمة المنسدلة.
   لا يغيّر أي منطق موجود لإنشاء الاستشارة: يكتفي بمزامنة القيمة
   المختارة مع select#consultationDoctor المستخدم أصلاً عند الإرسال،
   لذا لا حاجة لأي تعديل على زر الإنشاء أو على create_consultation.php.
════════════════════════════════════════════════════════════ */
(function () {
    let cncExtDebounce = null;
    let cncExtAbortCtrl = null;

    function cncExtDefaultAvatar() {
        return '<div class="cnc-ext-doc-avatar"><i class="fas fa-user-doctor"></i></div>';
    }

    function cncExtAvatarHtml(doc) {
        if (doc.avatar) {
            return '<div class="cnc-ext-doc-avatar"><img src="' + doc.avatar + '" alt=""></div>';
        }
        return cncExtDefaultAvatar();
    }

    function cncExtMetaHtml(doc) {
        const parts = [];
        if (doc.specialty) parts.push('<span><i class="fas fa-stethoscope"></i>' + doc.specialty + '</span>');
        if (doc.wilaya) parts.push('<span><i class="fas fa-location-dot"></i>' + doc.wilaya + '</span>');
        if (doc.clinic_name) parts.push('<span><i class="fas fa-hospital"></i>' + doc.clinic_name + '</span>');
        return parts.join('');
    }

    function cncExtSyncSelect(doctorId, fullName) {
        const select = document.getElementById("consultationDoctor");
        if (!select) return;
        let exists = false;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value === doctorId) { exists = true; break; }
        }
        if (!exists) {
            const opt = document.createElement("option");
            opt.value = doctorId;
            opt.textContent = fullName || doctorId;
            select.appendChild(opt);
        }
        select.value = doctorId;
    }

    function cncExtRenderResults(doctors) {
        const container = document.getElementById("cncExtDoctorResults");
        if (!container) return;

        if (!doctors.length) {
            container.innerHTML = '<div class="cnc-ext-doc-empty"><i class="fas fa-user-slash"></i><br>لا توجد نتائج مطابقة.</div>';
            return;
        }

        const selectedIdEl = document.getElementById("cncExtSelectedDoctorId");
        const selectedId = selectedIdEl ? selectedIdEl.value : "";
        container.innerHTML = "";

        doctors.forEach(function (doc) {
            const card = document.createElement("div");
            card.className = "cnc-ext-doc-card" + (doc.id === selectedId ? " cnc-ext-doc-selected" : "");
            card.setAttribute("data-doctor-id", doc.id);
            card.innerHTML =
                cncExtAvatarHtml(doc) +
                '<div class="cnc-ext-doc-info">' +
                    '<div class="cnc-ext-doc-name">' + doc.full_name + '</div>' +
                    '<div class="cnc-ext-doc-meta">' + cncExtMetaHtml(doc) + '</div>' +
                '</div>' +
                '<div class="cnc-ext-doc-check"><i class="fas fa-check"></i></div>';

            card.addEventListener("click", function () {
                document.querySelectorAll('.cnc-ext-doc-card').forEach(function (c) {
                    c.classList.remove('cnc-ext-doc-selected');
                });
                card.classList.add('cnc-ext-doc-selected');

                const hidden = document.getElementById("cncExtSelectedDoctorId");
                if (hidden) hidden.value = doc.id;

                cncExtSyncSelect(doc.id, doc.full_name);
            });

            container.appendChild(card);
        });
    }

    async function cncExtRunSearch(query) {
        const container = document.getElementById("cncExtDoctorResults");
        if (!container) return;

        if (cncExtAbortCtrl) cncExtAbortCtrl.abort();
        cncExtAbortCtrl = new AbortController();

        try {
            const resp = await fetch("search_doctors.php?q=" + encodeURIComponent(query), {
                signal: cncExtAbortCtrl.signal
            });
            const data = await resp.json();
            if (!data.success) return;
            cncExtRenderResults(data.doctors || []);
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const input = document.getElementById("cncExtDoctorSearch");
        const container = document.getElementById("cncExtDoctorResults");
        if (!input || !container) return;

        input.addEventListener("input", function () {
            const query = input.value.trim();

            clearTimeout(cncExtDebounce);

            if (query === "") {
                container.innerHTML = '<div class="cnc-ext-doc-hint"><i class="fas fa-circle-info"></i> ابدأ بكتابة اسم الطبيب أو التخصص أو الولاية.</div>';
                return;
            }

            cncExtDebounce = setTimeout(function () {
                cncExtRunSearch(query);
            }, 300);
        });
    });
})();

/* ════════════════════════════════════════════════════════════
   وحدة مشتركة لبحث/عرض بطاقات الأطباء (نتائج search_doctors.php).
   استُخرجت من قسم "الأطباء المشاركون" أدناه لتُستخدم من مكانين دون أي
   تكرار للكود: (1) هنا، في نافذة إنشاء الاستشارة، و(2) من
   consultation_details.js عند الضغط على "إضافة طبيب مشارك" داخل تفاصيل
   استشارة موجودة بالفعل. نفس نداء البحث تماماً (search_doctors.php)،
   ونفس شكل/تصميم بطاقات النتائج — بدون أي نظام بحث جديد.
════════════════════════════════════════════════════════════ */
window.cnsCreateDoctorSearchWidget = function (opts) {
    // opts:
    //   inputEl         : حقل البحث النصي
    //   resultsEl       : حاوية عرض بطاقات النتائج
    //   getExcludedIds  : fn() -> Set<String> من معرّفات الأطباء الممنوع اختيارهم (تُعرض معطّلة)
    //   excludedLabel   : fn(id, doc) -> نص الشارة للطبيب الممنوع (اختياري)
    //   isAdded         : fn(id) -> Bool هل هذا الطبيب مُضاف بالفعل (يُعرض بشارة "مُضاف" دون تعطيل النقر)
    //   onPick          : fn(doc) تُستدعى عند الضغط على بطاقة طبيب غير ممنوع وغير مُضاف
    let debounce = null;
    let abortCtrl = null;
    let lastDoctors = [];

    function avatarHtml(doc) {
        if (doc.avatar) {
            return '<div class="cnc-ext-doc-avatar"><img src="' + doc.avatar + '" alt=""></div>';
        }
        return '<div class="cnc-ext-doc-avatar"><i class="fas fa-user-doctor"></i></div>';
    }

    function metaHtml(doc) {
        const parts = [];
        if (doc.specialty) parts.push('<span><i class="fas fa-stethoscope"></i>' + doc.specialty + '</span>');
        if (doc.wilaya) parts.push('<span><i class="fas fa-location-dot"></i>' + doc.wilaya + '</span>');
        if (doc.clinic_name) parts.push('<span><i class="fas fa-hospital"></i>' + doc.clinic_name + '</span>');
        return parts.join('');
    }

    function renderResults(doctors) {
        lastDoctors = doctors || [];
        if (!opts.resultsEl) return;

        if (!lastDoctors.length) {
            opts.resultsEl.innerHTML = '<div class="cnc-ext-doc-empty"><i class="fas fa-user-slash"></i><br>لا توجد نتائج مطابقة.</div>';
            return;
        }

        const excludedIds = opts.getExcludedIds ? opts.getExcludedIds() : new Set();
        opts.resultsEl.innerHTML = "";

        lastDoctors.forEach(function (doc) {
            const id = String(doc.id);
            const isExcluded = excludedIds.has(id);
            const isAdded = (!isExcluded && opts.isAdded) ? opts.isAdded(id) : false;

            const card = document.createElement("div");
            card.className = "cnc-ext-doc-card" +
                (isAdded ? " cnc-ext-doc-selected" : "") +
                (isExcluded ? " cnc-colab-doc-disabled" : "");
            card.setAttribute("data-doctor-id", id);

            let badge = "";
            if (isExcluded) {
                const label = (opts.excludedLabel ? opts.excludedLabel(id, doc) : null) || "غير متاح";
                badge = '<span class="cnc-colab-doc-badge">' + label + '</span>';
            } else if (isAdded) {
                badge = '<span class="cnc-colab-doc-badge cnc-colab-doc-badge-added">مُضاف</span>';
            }

            card.innerHTML =
                avatarHtml(doc) +
                '<div class="cnc-ext-doc-info">' +
                    '<div class="cnc-ext-doc-name">' + doc.full_name + '</div>' +
                    '<div class="cnc-ext-doc-meta">' + metaHtml(doc) + '</div>' +
                '</div>' +
                badge;

            if (!isExcluded && !isAdded) {
                card.addEventListener("click", function () {
                    opts.onPick(doc);
                });
            }

            opts.resultsEl.appendChild(card);
        });
    }

    function refresh() { renderResults(lastDoctors); }

    async function runSearch(query) {
        if (!opts.resultsEl) return;

        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();

        try {
            const resp = await fetch("search_doctors.php?q=" + encodeURIComponent(query), {
                signal: abortCtrl.signal
            });
            const data = await resp.json();
            if (!data.success) return;
            renderResults(data.doctors || []);
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        }
    }

    function bindInput(hintHtml) {
        if (!opts.inputEl) return;
        opts.inputEl.addEventListener("input", function () {
            const query = opts.inputEl.value.trim();

            clearTimeout(debounce);

            if (query === "") {
                if (opts.resultsEl) opts.resultsEl.innerHTML = hintHtml;
                return;
            }

            debounce = setTimeout(function () {
                runSearch(query);
            }, 300);
        });
    }

    return { bindInput: bindInput, renderResults: renderResults, runSearch: runSearch, refresh: refresh };
};

/* ════════════════════════════════════════════════════════════
   قسم "الأطباء المشاركون" داخل نافذة إنشاء استشارة جديدة —
   إعادة استخدام نفس نداء البحث الموجود مسبقًا (search_doctors.php)
   عبر الوحدة المشتركة أعلاه (cnsCreateDoctorSearchWidget)، لإضافة طبيب
   واحد أو أكثر كمشاركين على شكل بطاقات (Chips).
   حقل اختياري بالكامل — Front-End فقط، بدون أي حفظ أو Backend
   جديد، ولا يمسّ أي منطق آخر في النافذة (لا اختيار الطبيب
   الرئيسي، ولا المريض، ولا المرفقات، ولا الخصوصية، ولا الأولوية).
════════════════════════════════════════════════════════════ */
(function () {
    const cncColabSelected = new Map(); // doctorId (String) -> full_name

    // معرف الطبيب الرئيسي الحالي — نفس مصدر الحقيقة المستخدم عند الإرسال
    // (نفس select#consultationDoctor الذي يُزامن معه بحث الطبيب الرئيسي).
    function cncColabGetPrimaryDoctorId() {
        const select = document.getElementById("consultationDoctor");
        return select ? String(select.value || "") : "";
    }

    function cncColabSyncHiddenField() {
        const hidden = document.getElementById("cncColabSelectedIds");
        if (hidden) hidden.value = Array.from(cncColabSelected.keys()).join(',');
    }

    function cncColabRenderChips() {
        const container = document.getElementById("cncColabChips");
        if (!container) return;

        container.innerHTML = "";

        cncColabSelected.forEach(function (fullName, id) {
            const chip = document.createElement("div");
            chip.className = "cnc-colab-chip";
            chip.setAttribute("data-doctor-id", id);
            chip.innerHTML =
                '<i class="fas fa-user-doctor"></i>' +
                '<span class="cnc-colab-chip-name">' + fullName + '</span>' +
                '<button type="button" class="cnc-colab-chip-remove" aria-label="حذف"><i class="fas fa-times"></i></button>';

            chip.querySelector(".cnc-colab-chip-remove").addEventListener("click", function () {
                cncColabSelected.delete(id);
                cncColabSyncHiddenField();
                cncColabRenderChips();
                if (widget) widget.refresh();
            });

            container.appendChild(chip);
        });
    }

    let widget = null;

    document.addEventListener("DOMContentLoaded", function () {
        const input = document.getElementById("cncColabDoctorSearch");
        const results = document.getElementById("cncColabDoctorResults");
        if (!input || !results) return;

        widget = window.cnsCreateDoctorSearchWidget({
            inputEl: input,
            resultsEl: results,
            getExcludedIds: function () {
                const primaryId = cncColabGetPrimaryDoctorId();
                return primaryId ? new Set([primaryId]) : new Set();
            },
            excludedLabel: function () { return "الطبيب الرئيسي"; },
            isAdded: function (id) { return cncColabSelected.has(id); },
            onPick: function (doc) {
                const id = String(doc.id);
                if (cncColabSelected.has(id)) return; // يمنع تكرار نفس الطبيب
                cncColabSelected.set(id, doc.full_name);
                cncColabSyncHiddenField();
                cncColabRenderChips();
                widget.refresh();
            }
        });

        widget.bindInput('<div class="cnc-ext-doc-hint"><i class="fas fa-circle-info"></i> ابدأ بكتابة اسم الطبيب أو التخصص أو الولاية لإضافته كمشارك.</div>');
    });
})();

/* ════════════════════════════════════════════════════════════
   قسم "المرفقات" داخل نافذة إنشاء استشارة جديدة — جلب مرفقات
   الملف الطبي الحقيقي للمريض المختار حسب الأنواع المحددة، وعرضها
   كبطاقات قابلة للمعاينة والتحديد. لا يمس أي منطق آخر في النافذة.
════════════════════════════════════════════════════════════ */
(function () {
    let cncAttachAbortCtrl = null;
    const cncAttachSelected = new Set();

    function cncAttachGetPatientId() {
        const sel = document.getElementById("consultationPatient");
        return sel ? sel.value : "";
    }

    function cncAttachGetCheckedTypes() {
        const boxes = document.querySelectorAll('.cnc-attach-type-cb:checked');
        return Array.from(boxes).map(function (b) { return b.getAttribute('data-attach-type'); });
    }

    function cncAttachFormatDate(raw) {
        if (!raw) return '';
        const d = new Date(raw.replace(' ', 'T'));
        if (isNaN(d.getTime())) return raw;
        return d.toLocaleDateString('ar-DZ', { year: 'numeric', month: '2-digit', day: '2-digit' });
    }

    function cncAttachSyncHiddenField() {
        const hidden = document.getElementById("cncAttachSelectedIds");
        if (hidden) hidden.value = Array.from(cncAttachSelected).join(',');
    }

    function cncAttachRenderEmpty(message) {
        const container = document.getElementById("cncAttachResults");
        if (!container) return;
        container.innerHTML = '<div class="cnc-attach-empty"><i class="fas fa-circle-info"></i> ' + message + '</div>';
    }

    function cncAttachRenderHint(message) {
        const container = document.getElementById("cncAttachResults");
        if (!container) return;
        container.innerHTML = '<div class="cnc-attach-hint"><i class="fas fa-circle-info"></i> ' + message + '</div>';
    }

    function cncAttachRenderResults(items) {
        const container = document.getElementById("cncAttachResults");
        if (!container) return;

        if (!items.length) {
            cncAttachRenderEmpty("لا توجد مرفقات لهذا النوع داخل الملف الطبي.");
            return;
        }

        container.innerHTML = "";

        items.forEach(function (item) {
            const card = document.createElement("div");
            card.className = "cnc-attach-card" + (cncAttachSelected.has(item.id) ? " cnc-attach-selected" : "");

            const pick = document.createElement("input");
            pick.type = "checkbox";
            pick.className = "cnc-attach-pick";
            pick.checked = cncAttachSelected.has(item.id);
            pick.addEventListener("change", function () {
                if (pick.checked) {
                    cncAttachSelected.add(item.id);
                    card.classList.add("cnc-attach-selected");
                } else {
                    cncAttachSelected.delete(item.id);
                    card.classList.remove("cnc-attach-selected");
                }
                cncAttachSyncHiddenField();
            });

            const icon = document.createElement("div");
            icon.className = "cnc-attach-icon";
            icon.innerHTML = '<i class="fas ' + item.icon + '"></i>';

            const body = document.createElement("div");
            body.className = "cnc-attach-body";

            const title = document.createElement("div");
            title.className = "cnc-attach-title";
            title.textContent = item.title;

            const meta = document.createElement("div");
            meta.className = "cnc-attach-meta";
            meta.innerHTML =
                '<span><i class="fas fa-tag"></i>' + item.type_label + '</span>' +
                '<span><i class="fas fa-calendar"></i>' + cncAttachFormatDate(item.date) + '</span>' +
                '<span><i class="fas fa-database"></i>' + item.size_label + '</span>';

            const actions = document.createElement("div");
            actions.className = "cnc-attach-actions";
            const previewBtn = document.createElement("button");
            previewBtn.type = "button";
            previewBtn.className = "cnc-attach-preview-btn";
            previewBtn.innerHTML = '<i class="fas fa-eye"></i> معاينة';

            const previewBox = document.createElement("div");
            previewBox.className = "cnc-attach-preview-box";
            previewBox.textContent = item.preview_text || "لا يوجد محتوى نصي لعرضه.";

            previewBtn.addEventListener("click", function () {
                previewBox.classList.toggle("cnc-open");
            });

            actions.appendChild(previewBtn);

            body.appendChild(title);
            body.appendChild(meta);
            body.appendChild(actions);
            body.appendChild(previewBox);

            card.appendChild(pick);
            card.appendChild(icon);
            card.appendChild(body);

            container.appendChild(card);
        });
    }

    async function cncAttachFetchAndRender() {
        const patientId = cncAttachGetPatientId();
        const types = cncAttachGetCheckedTypes();

        if (!patientId) {
            cncAttachRenderHint("اختر المريض أولاً، ثم حدّد نوع المرفقات لعرضها من الملف الطبي.");
            return;
        }

        if (!types.length) {
            cncAttachRenderHint("حدّد نوع مرفق واحد على الأقل لعرضه من الملف الطبي.");
            return;
        }

        if (cncAttachAbortCtrl) cncAttachAbortCtrl.abort();
        cncAttachAbortCtrl = new AbortController();

        cncAttachRenderHint("جارٍ تحميل المرفقات...");

        try {
            const url = "get_patient_attachments.php?patient_id=" + encodeURIComponent(patientId) +
                "&types=" + encodeURIComponent(types.join(','));
            const resp = await fetch(url, { signal: cncAttachAbortCtrl.signal });
            const data = await resp.json();
            if (!data.success) {
                cncAttachRenderEmpty("تعذّر جلب المرفقات.");
                return;
            }
            cncAttachRenderResults(data.attachments || []);
        } catch (e) {
            if (e.name !== 'AbortError') {
                console.error(e);
                cncAttachRenderEmpty("تعذّر جلب المرفقات.");
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const patientSelect = document.getElementById("consultationPatient");
        const typeBoxes = document.querySelectorAll('.cnc-attach-type-cb');
        const openBtn = document.getElementById("cncAttachOpenBtn");

        if (!patientSelect || !typeBoxes.length) return;

        patientSelect.addEventListener("change", cncAttachFetchAndRender);

        typeBoxes.forEach(function (box) {
            box.addEventListener("change", cncAttachFetchAndRender);
        });

        if (openBtn) {
            openBtn.addEventListener("click", function () {
                cncAttachFetchAndRender();
            });
        }
    });
})();
