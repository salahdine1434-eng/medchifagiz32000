/* ================================================================
   MediSmart — Medical Dashboard  |  app.js
   ================================================================
   TABLE OF CONTENTS
   -----------------
   01. Translations  (AR / FR / EN) — LANGS object
   02. Wilaya & Commune Data
   03. App Bootstrap & Event Listeners
   04. Auth  (Login / Register / Logout)
   05. Navigation & View Switching
   06. Side Panels  (Notifications / Profile / Logo Editor)
   07. Notifications Logic  (markRead / markAllRead / badge)
   08. Profile  (updateProfileName / setProfImg / saveProfile)
   09. Logo Editor  (updateAppName / setLogoEmoji / setLogoImg)
   10. Daily Tracking — يومياتي  (analyzeDaily / shareDaily)
   11. AI Nurse Chat  (toggleAIChat / sendAIChat / getAIAnswer)
   12. Print / Share / Copy  (printRecord / shareResult / ...)
   13. Dark Mode  (toggleDark)
   14. Image Upload  (previewImg / saveImg / shareImg / printImg)
   ================================================================ */

// ==================== TRANSLATIONS ====================

var LANGS = {
  ar: {
    slogan:"تطبيقك الطبي الذكي", login:"تسجيل الدخول", newAccount:"حساب جديد",
    fullName:"الاسم الكامل *", phone:"رقم الهاتف *", password:"كلمة المرور *",
    enter:"دخول", noAccount:"ليس لديك حساب؟", createAccount:"أنشئ حساباً جديداً",
    firstName:"الاسم *", lastName:"اللقب *", confirmPass:"تأكيد كلمة المرور *",
    createBtn:"إنشاء الحساب", haveAccount:"لديك حساب؟", signIn:"سجّل الدخول",
    appSub:"التطبيق الطبي الذكي", dashboard:"لوحة تحكم المريض",
    alerts:"التنبيهات", notif1:"تذكير دواء: باراسيتامول 500mg",
    notif2:"موعد الغد - د. كريمي 10:00", notif3:"نتائج التحاليل جاهزة",
    ago5min:"منذ 5 دقائق", ago1h:"منذ ساعة", yesterday:"أمس",
    pharmacy:"الصيدلية", pharmacySub:"ابحث عن الأدوية واطلبها",
    wilaya:"📍 الولاية", commune:"البلدية",
    pharmacies:"الصيدليات", digital:"الصيدلية الرقمية", inquiry:"استفسار",
    open:"مفتوح", closed:"مغلق", medType:"نوع الدواء",
    medTypePH:"مثال: باراسيتامول 500mg", pharmacyLbl:"الصيدلية",
    deliveryAddr:"عنوان التوصيل", deliveryPH:"العنوان الكامل",
    confirmOrder:"تأكيد الطلب", orderSent:"تم إرسال طلبك بنجاح! ✅",
    writeMsgPH:"اكتب رسالتك...", you:"أنت",
    doctors:"الأطباء", doctorsSub:"احجز موعدك أو تواصل مع طبيبك",
    allSpecs:"🩺 جميع التخصصات", doctorsLbl:"الأطباء",
    bookAppt:"حجز موعد", contact:"التواصل",
    byAppt:"بالموعد", walkIn:"بدون موعد",
    genMed:"طب عام", cardiology:"أمراض القلب",
    pediatrics:"طب الأطفال", gynecology:"أمراض النساء", algiers:"الجزائر",
    doctorLbl:"الطبيب", specialty:"التخصص", date:"التاريخ", time:"الوقت",
    visitReason:"سبب الزيارة", visitReasonPH:"اذكر سبب الزيارة...",
    confirmAppt:"تأكيد الحجز", apptBooked:"تم حجز الموعد بنجاح! ✅",
    apptTomorrow:"موعدك غداً الساعة 10:00", resultsGood:"نتائج الفحص جيدة الحمد لله",
    back:"رجوع", doctor:"الطبيب",
    labs:"المخابر", labsSub:"التحاليل والفحوصات المخبرية",
    bloodUrine:"تحاليل الدم، البول", fullTests:"تحاليل شاملة",
    book:"حجز", receiveResult:"استقبال نتيجة",
    labBooked:"تم حجز التحليل! ✅", booked:"تم الحجز! ✅",
    nurses:"الممرضون للرعاية المنزلية", nursesSub:"رعاية طبية في منزلك",
    nurseServices:"حقن، ضغط، تضميد", chronicCare:"رعاية مزمنين، مسنين",
    nurseBooked:"تم حجز الممرض! ✅",
    blood:"التبرع بالدم", bloodSub:"ابحث عن متبرعين حسب الزمرة",
    allGroups:"🩸 جميع الزمر",
    clinics:"العيادات والمصحات", clinicsSub:"ابحث عن العيادة المناسبة",
    civil:"الحماية المدنية والإسعاف", civilSub:"أرقام الطوارئ",
    civilTlem:"الحماية المدنية - تلمسان", emergency247:"طوارئ 24/24", emergency:"طوارئ",
    sports:"الصحة والرياضة", sportsSub:"التغذية والحياة الصحية",
    nutrition:"تغذية", sport:"رياضة", health:"صحة",
    nutritionist:"أخصائية تغذية", howHelp:"كيف يمكنني مساعدتك؟",
    askPH:"اسأل...", askMePH:"اسألني...", aiSportsGreet:"مرحباً! اسألني عن الصحة والرياضة 💪",
    charity:"الجمعيات الخيرية", charitySub:"ابحث عن الجمعيات في منطقتك",
    charityName:"جمعية الرحمة للمرضى", active:"نشطة",
    elderly:"دار العجزة", elderlySub:"رعاية المسنين في جميع الولايات",
    elderlyTlem:"دار رعاية المسنين - تلمسان", capacity80:"طاقة: 80",
    orphanage:"دار الأيتام", orphanageSub:"دعم وكفالة الأيتام",
    orphanageTlem:"دار الأيتام الخيرية - تلمسان",
    aiTitle:"مساعدك الذكي الجاهز للعمل",
    aiSub:"استخدم قوة الذكاء الاصطناعي لتحسين صحتك",
    dailyTrack:"تتبع يومي", pregFollow:"متابعة الحمل",
    chronicDis:"أمراض مزمنة", smartConsult:"استشارة ذكية",
    howFeel:"كيف تشعر اليوم؟", descStatePH:"صف حالتك...",
    bloodPressure:"ضغط الدم", heartRate:"نبض القلب",
    aiAnalyze:"تحليل AI", analysisResult:"✅ نتيجة التحليل",
    goodHealth:"حالتك الصحية جيدة. القياسات ضمن المعدل الطبيعي. استمر في تناول أدويتك بانتظام.",
    week24:"الأسبوع 24", weightLbl:"الوزن",
    save:"حفظ", saved:"تم الحفظ ✅",
    pregGreet:"مرحباً، اسألني عن حملك 💙", pregAskPH:"اسألي عن حملك...",
    fastingSugar:"سكر الدم صيام", systolic:"الضغط الانقباضي",
    sugarLevel:"مستوى السكر", normal:"ضمن المعدل",
    readingsSaved:"تم حفظ القراءات ✅",
    chronicGreet:"أنا هنا لمتابعة مرضك المزمن.", askCondPH:"اسأل عن حالتك...",
    smartAI:"المساعد الطبي الذكي",
    aiGreet:"مرحباً! اسألني عن أي أعراض أو معلومات صحية. ⚠️ للاستعلام فقط.",
    medQuestionPH:"اكتب سؤالك الطبي...",
    medRecord:"السجل الطبي الرقمي",
    nameLbl:"الاسم", birthDate:"تاريخ الميلاد", bloodGroup:"الزمرة",
    weightHeight:"الوزن / الطول", currentMeds:"الأدوية الحالية",
    twiceDaily:"مرتين يومياً", morning:"صباحاً",
    scanQR:"امسح الرمز لعرض سجلك الكامل",
    print:"طباعة", share:"مشاركة", edit:"تعديل", editData:"تعديل البيانات",
    drugAlerts:"تنبيهات الدواء", addNewDrug:"إضافة دواء جديد",
    drugName:"اسم الدواء", drugNamePH:"باراسيتامول 500mg",
    dose:"الجرعة", takeTime:"وقت التناول", frequency:"التكرار",
    onceDaily:"مرة يومياً", thrice:"ثلاث مرات",
    addAlert:"إضافة تنبيه", myDrugs:"أدويتي الحالية",
    edited:"تم التعديل ✅",
    analyze:"تحليل", sugarLbl:"السكر",
    goodHealthSimple:"حالتك الصحية جيدة. استمر على نفس النظام.",
    pregChronicTitle:"متابعة الحوامل والأمراض المزمنة",
    pregnant:"الحوامل", nextAppt:"الموعد القادم",
    lastWeight:"آخر وزن", thisMonth:"هذا الشهر",
    notes:"ملاحظات", notesPhPH:"أدخل ملاحظاتك...", update:"تحديث",
    analysisResults:"استقبال نتائج التحاليل",
    qrTab:"📷 رمز QR", imgTab:"🖼️ صورة التحليل",
    scanQRLab:"امسح رمز QR من المخبر", tapSimulate:"اضغط لمحاكاة المسح",
    labResult:"📋 نتيجة التحليل - مخبر النخبة",
    cbcTest:"تحليل الدم الشامل CBC", hemoglobin:"الهيموغلوبين: ",
    platelets:"الصفيحات: ", bloodSugar:"سكر الدم", slightlyHigh:"مرتفع قليلاً",
    copy:"نسخ", remove:"حذف",
    uploadAnalysis:"ارفع صورة التحليل", uploadFormats:"JPG, PNG, PDF مسموح بها",
    uploadedResult:"نتيجة التحليل المرفوعة",
    comingSoon:"قريباً",
    comingSoonDesc:"قسم الخدمات الصحية المتخصصة قيد التطوير.<br>ستتضمن أمراض الجهاز العصبي وخدمات أخرى.",
    home:"الرئيسية", aiNav:"الذكاء AI", recordNav:"السجل",
    dailyNav:"يومياتي", drugsNav:"أدويتي", pregNav:"حوامل",
    analysisNav:"تحاليل", servicesNav:"خدمات",
    sosTitle:"نداء طوارئ SOS", sosSub:"سيتم إرسال رسالة طوارئ فورية للحماية المدنية وجهة الاتصال الطارئة.",
    personalEmergency:"رقم الطوارئ الشخصي", civilProt:"الحماية المدنية (14)", both:"كلاهما",
    sendSos:"إرسال SOS", sosSent:"تم إرسال SOS! 🆘 المساعدة في الطريق",
    sosCancelled:"تم إلغاء الإرسال ✅", mistake:"أخطأت ❌",
    logoutTitle:"تسجيل الخروج", logoutSub:"هل أنت متأكد أنك تريد الخروج من حسابك؟",
    yesLogout:"نعم، خروج", cancel:"إلغاء", ok:"حسناً",
    diabetes2:"السكري نوع 2", hypertension:"ارتفاع ضغط الدم",
    fullNamePH:"أدخل اسمك الكامل", firstNamePH:"الاسم", lastNamePH:"اللقب",
    errName:"⚠️ يرجى إدخال الاسم الكامل", errNameReg:"⚠️ يرجى إدخال الاسم واللقب",
    imageSaved:"تم حفظ صورة التحليل ✅", imageCopied:"تم نسخ رابط الصورة 📋", imageShared:"تم مشاركة التحليل ✅", imageRemoved:"تم حذف الصورة",
    markAllRead:"تحديد الكل كمقروء", personalInfo:"المعلومات الشخصية",
    medInfo:"المعلومات الطبية", emergencyContact:"جهة الاتصال الطارئة",
    emergencyName:"الاسم", allergies:"الحساسية", saveProfile:"حفظ البروفايل",
    logoEditor:"تعديل الشعار والاسم", appNameLbl:"اسم التطبيق",
    chooseEmoji:"اختر رمزاً", appSubLbl:"الوصف", tapToChangeLogo:"اضغط لتغيير الشعار",
  },
  fr: {
    slogan:"Votre application médicale intelligente", login:"Se connecter", newAccount:"Nouveau compte",
    fullName:"Nom complet *", phone:"Téléphone *", password:"Mot de passe *",
    enter:"Connexion", noAccount:"Pas de compte ?", createAccount:"Créer un compte",
    firstName:"Prénom *", lastName:"Nom *", confirmPass:"Confirmer le mot de passe *",
    createBtn:"Créer le compte", haveAccount:"Vous avez un compte ?", signIn:"Se connecter",
    appSub:"Application médicale intelligente", dashboard:"Tableau de bord patient",
    alerts:"Notifications", notif1:"Rappel médicament: Paracétamol 500mg",
    notif2:"RDV demain - Dr Karimi 10:00", notif3:"Résultats d'analyses prêts",
    ago5min:"Il y a 5 min", ago1h:"Il y a 1h", yesterday:"Hier",
    pharmacy:"Pharmacie", pharmacySub:"Recherchez et commandez vos médicaments",
    wilaya:"📍 Wilaya", commune:"Commune",
    pharmacies:"Pharmacies", digital:"Pharmacie numérique", inquiry:"Renseignements",
    open:"Ouvert", closed:"Fermé", medType:"Type de médicament",
    medTypePH:"Ex: Paracétamol 500mg", pharmacyLbl:"Pharmacie",
    deliveryAddr:"Adresse de livraison", deliveryPH:"Adresse complète",
    confirmOrder:"Confirmer la commande", orderSent:"Commande envoyée! ✅",
    writeMsgPH:"Écrire un message...", you:"Vous",
    doctors:"Médecins", doctorsSub:"Prenez rendez-vous ou contactez votre médecin",
    allSpecs:"🩺 Toutes spécialités", doctorsLbl:"Médecins",
    bookAppt:"Prendre RDV", contact:"Contact",
    byAppt:"Sur RDV", walkIn:"Sans RDV",
    genMed:"Médecine générale", cardiology:"Cardiologie",
    pediatrics:"Pédiatrie", gynecology:"Gynécologie", algiers:"Alger",
    doctorLbl:"Médecin", specialty:"Spécialité", date:"Date", time:"Heure",
    visitReason:"Motif de consultation", visitReasonPH:"Indiquez le motif...",
    confirmAppt:"Confirmer", apptBooked:"RDV pris avec succès! ✅",
    apptTomorrow:"RDV demain à 10:00", resultsGood:"Résultats bons, Alhamdulillah",
    back:"Retour", doctor:"Médecin",
    labs:"Laboratoires", labsSub:"Analyses et examens",
    bloodUrine:"Analyses sang, urines", fullTests:"Analyses complètes",
    book:"Réserver", receiveResult:"Recevoir résultat",
    labBooked:"Analyse réservée! ✅", booked:"Réservé! ✅",
    nurses:"Infirmiers à domicile", nursesSub:"Soins médicaux chez vous",
    nurseServices:"Injections, tension, pansements", chronicCare:"Soins chroniques, âgés",
    nurseBooked:"Infirmier réservé! ✅",
    blood:"Don de sang", bloodSub:"Chercher des donneurs par groupe",
    allGroups:"🩸 Tous groupes",
    clinics:"Cliniques et polycliniques", clinicsSub:"Trouvez la clinique adaptée",
    civil:"Protection civile & SAMU", civilSub:"Numéros d'urgence",
    civilTlem:"Protection civile - Tlemcen", emergency247:"Urgences 24/24", emergency:"Urgences",
    sports:"Santé & Sport", sportsSub:"Nutrition et vie saine",
    nutrition:"Nutrition", sport:"Sport", health:"Santé",
    nutritionist:"Nutritionniste", howHelp:"Comment puis-je vous aider ?",
    askPH:"Poser une question...", askMePH:"Demandez-moi...", aiSportsGreet:"Bonjour ! Posez vos questions santé & sport 💪",
    charity:"Associations caritatives", charitySub:"Trouvez des associations près de vous",
    charityName:"Association Rahma pour les malades", active:"Active",
    elderly:"Maison de retraite", elderlySub:"Soins des personnes âgées dans toutes les wilayas",
    elderlyTlem:"Maison de retraite - Tlemcen", capacity80:"Capacité: 80",
    orphanage:"Orphelinat", orphanageSub:"Soutien et parrainage d'orphelins",
    orphanageTlem:"Orphelinat caritatif - Tlemcen",
    aiTitle:"Votre assistant intelligent prêt",
    aiSub:"Utilisez l'IA pour améliorer votre santé",
    dailyTrack:"Suivi quotidien", pregFollow:"Suivi grossesse",
    chronicDis:"Maladies chroniques", smartConsult:"Consultation intelligente",
    howFeel:"Comment vous sentez-vous ?", descStatePH:"Décrivez votre état...",
    bloodPressure:"Tension artérielle", heartRate:"Fréquence cardiaque",
    aiAnalyze:"Analyser IA", analysisResult:"✅ Résultat d'analyse",
    goodHealth:"Votre santé est bonne. Les mesures sont normales. Continuez vos médicaments.",
    week24:"Semaine 24", weightLbl:"Poids",
    save:"Sauvegarder", saved:"Sauvegardé ✅",
    pregGreet:"Bonjour, posez-moi des questions sur votre grossesse 💙", pregAskPH:"Posez vos questions grossesse...",
    fastingSugar:"Glycémie à jeun", systolic:"Tension systolique",
    sugarLevel:"Niveau de sucre", normal:"Normal",
    readingsSaved:"Mesures sauvegardées ✅",
    chronicGreet:"Je suis là pour suivre votre maladie chronique.", askCondPH:"Question sur votre état...",
    smartAI:"Assistant médical intelligent",
    aiGreet:"Bonjour! Posez-moi des questions sur vos symptômes. ⚠️ Informatif seulement.",
    medQuestionPH:"Votre question médicale...",
    medRecord:"Dossier médical numérique",
    nameLbl:"Nom", birthDate:"Date de naissance", bloodGroup:"Groupe sanguin",
    weightHeight:"Poids / Taille", currentMeds:"Médicaments actuels",
    twiceDaily:"Deux fois par jour", morning:"Le matin",
    scanQR:"Scannez le code pour voir votre dossier complet",
    print:"Imprimer", share:"Partager", edit:"Modifier", editData:"Modifier les données",
    drugAlerts:"Rappels médicaments", addNewDrug:"Ajouter un médicament",
    drugName:"Nom du médicament", drugNamePH:"Paracétamol 500mg",
    dose:"Dose", takeTime:"Heure de prise", frequency:"Fréquence",
    onceDaily:"Une fois par jour", thrice:"Trois fois",
    addAlert:"Ajouter rappel", myDrugs:"Mes médicaments",
    edited:"Modifié ✅",
    analyze:"Analyser", sugarLbl:"Sucre",
    goodHealthSimple:"Votre santé est bonne. Continuez votre routine.",
    pregChronicTitle:"Suivi grossesse et maladies chroniques",
    pregnant:"Grossesse", nextAppt:"Prochain RDV",
    lastWeight:"Dernier poids", thisMonth:"ce mois",
    notes:"Notes", notesPhPH:"Entrez vos notes...", update:"Mettre à jour",
    analysisResults:"Réception résultats d'analyses",
    qrTab:"📷 Code QR", imgTab:"🖼️ Image analyse",
    scanQRLab:"Scannez le QR du laboratoire", tapSimulate:"Appuyez pour simuler",
    labResult:"📋 Résultat - Lab Élite",
    cbcTest:"Numération Formule Sanguine NFS", hemoglobin:"Hémoglobine: ",
    platelets:"Plaquettes: ", bloodSugar:"Glycémie", slightlyHigh:"Légèrement élevé",
    copy:"Copier", remove:"Supprimer",
    uploadAnalysis:"Télécharger une image d'analyse", uploadFormats:"JPG, PNG, PDF acceptés",
    uploadedResult:"Résultat d'analyse téléchargé",
    comingSoon:"Bientôt disponible",
    comingSoonDesc:"Section services spécialisés en développement.",
    home:"Accueil", aiNav:"IA", recordNav:"Dossier",
    dailyNav:"Mon journal", drugsNav:"Médicaments", pregNav:"Grossesse",
    analysisNav:"Analyses", servicesNav:"Services",
    sosTitle:"Appel SOS", sosSub:"Un message d'urgence sera envoyé immédiatement.",
    personalEmergency:"Numéro d'urgence personnel", civilProt:"Protection civile (14)", both:"Les deux",
    sendSos:"Envoyer SOS", sosSent:"SOS envoyé! 🆘 L'aide est en route",
    sosCancelled:"Envoi annulé ✅", mistake:"Erreur ❌",
    logoutTitle:"Déconnexion", logoutSub:"Êtes-vous sûr de vouloir vous déconnecter ?",
    yesLogout:"Oui, déconnecter", cancel:"Annuler", ok:"OK",
    diabetes2:"Diabète type 2", hypertension:"Hypertension artérielle",
    fullNamePH:"Entrez votre nom complet", firstNamePH:"Prénom", lastNamePH:"Nom",
    errName:"⚠️ Veuillez entrer votre nom complet", errNameReg:"⚠️ Veuillez entrer prénom et nom",
    imageSaved:"Image sauvegardée ✅", imageCopied:"Lien copié 📋", imageShared:"Analyse partagée ✅", imageRemoved:"Image supprimée",
    markAllRead:"Tout marquer comme lu", personalInfo:"Informations personnelles",
    medInfo:"Informations médicales", emergencyContact:"Contact d'urgence",
    emergencyName:"Nom", allergies:"Allergies", saveProfile:"Sauvegarder profil",
    logoEditor:"Modifier logo et nom", appNameLbl:"Nom de l'application",
    chooseEmoji:"Choisir une icône", appSubLbl:"Description", tapToChangeLogo:"Appuyez pour changer le logo",
  },
  en: {
    slogan:"Your smart medical app", login:"Sign In", newAccount:"New Account",
    fullName:"Full Name *", phone:"Phone *", password:"Password *",
    enter:"Login", noAccount:"Don't have an account?", createAccount:"Create Account",
    firstName:"First Name *", lastName:"Last Name *", confirmPass:"Confirm Password *",
    createBtn:"Create Account", haveAccount:"Have an account?", signIn:"Sign In",
    appSub:"Smart Medical Application", dashboard:"Patient Dashboard",
    alerts:"Notifications", notif1:"Drug reminder: Paracetamol 500mg",
    notif2:"Tomorrow's appt - Dr Karimi 10:00", notif3:"Lab results ready",
    ago5min:"5 minutes ago", ago1h:"1 hour ago", yesterday:"Yesterday",
    pharmacy:"Pharmacy", pharmacySub:"Find and order your medicines",
    wilaya:"📍 Province", commune:"Municipality",
    pharmacies:"Pharmacies", digital:"Digital Pharmacy", inquiry:"Inquiry",
    open:"Open", closed:"Closed", medType:"Medicine Type",
    medTypePH:"e.g. Paracetamol 500mg", pharmacyLbl:"Pharmacy",
    deliveryAddr:"Delivery Address", deliveryPH:"Full Address",
    confirmOrder:"Confirm Order", orderSent:"Order sent successfully! ✅",
    writeMsgPH:"Write a message...", you:"You",
    doctors:"Doctors", doctorsSub:"Book an appointment or contact your doctor",
    allSpecs:"🩺 All Specialties", doctorsLbl:"Doctors",
    bookAppt:"Book Appointment", contact:"Contact",
    byAppt:"By Appointment", walkIn:"Walk-in",
    genMed:"General Medicine", cardiology:"Cardiology",
    pediatrics:"Pediatrics", gynecology:"Gynecology", algiers:"Algiers",
    doctorLbl:"Doctor", specialty:"Specialty", date:"Date", time:"Time",
    visitReason:"Reason for Visit", visitReasonPH:"State your reason...",
    confirmAppt:"Confirm Booking", apptBooked:"Appointment booked! ✅",
    apptTomorrow:"Appt tomorrow at 10:00", resultsGood:"Exam results are good",
    back:"Back", doctor:"Doctor",
    labs:"Laboratories", labsSub:"Blood tests and examinations",
    bloodUrine:"Blood, urine tests", fullTests:"Comprehensive tests",
    book:"Book", receiveResult:"Receive Result",
    labBooked:"Lab booked! ✅", booked:"Booked! ✅",
    nurses:"Home Care Nurses", nursesSub:"Medical care at your home",
    nurseServices:"Injections, pressure, dressings", chronicCare:"Chronic, elderly care",
    nurseBooked:"Nurse booked! ✅",
    blood:"Blood Donation", bloodSub:"Find donors by blood type",
    allGroups:"🩸 All Groups",
    clinics:"Clinics & Polyclinics", clinicsSub:"Find the right clinic",
    civil:"Civil Protection & SAMU", civilSub:"Emergency numbers",
    civilTlem:"Civil Protection - Tlemcen", emergency247:"Emergency 24/7", emergency:"Emergency",
    sports:"Health & Sports", sportsSub:"Nutrition and healthy living",
    nutrition:"Nutrition", sport:"Sports", health:"Health",
    nutritionist:"Nutritionist", howHelp:"How can I help you?",
    askPH:"Ask...", askMePH:"Ask me...", aiSportsGreet:"Hello! Ask me about health & sports 💪",
    charity:"Charities", charitySub:"Find charities in your area",
    charityName:"Rahma Patient Association", active:"Active",
    elderly:"Retirement Home", elderlySub:"Elderly care across all provinces",
    elderlyTlem:"Retirement Home - Tlemcen", capacity80:"Capacity: 80",
    orphanage:"Orphanage", orphanageSub:"Support and sponsorship of orphans",
    orphanageTlem:"Charitable Orphanage - Tlemcen",
    aiTitle:"Your Smart Assistant Ready to Work",
    aiSub:"Use the power of AI to improve your health",
    dailyTrack:"Daily Tracking", pregFollow:"Pregnancy Follow-up",
    chronicDis:"Chronic Diseases", smartConsult:"Smart Consultation",
    howFeel:"How do you feel today?", descStatePH:"Describe your condition...",
    bloodPressure:"Blood Pressure", heartRate:"Heart Rate",
    aiAnalyze:"AI Analyze", analysisResult:"✅ Analysis Result",
    goodHealth:"Your health is good. Measurements are normal. Continue your medications.",
    week24:"Week 24", weightLbl:"Weight",
    save:"Save", saved:"Saved ✅",
    pregGreet:"Hello, ask me about your pregnancy 💙", pregAskPH:"Ask about your pregnancy...",
    fastingSugar:"Fasting Blood Sugar", systolic:"Systolic Pressure",
    sugarLevel:"Sugar Level", normal:"Normal",
    readingsSaved:"Readings saved ✅",
    chronicGreet:"I'm here to monitor your chronic condition.", askCondPH:"Ask about your condition...",
    smartAI:"Smart Medical Assistant",
    aiGreet:"Hello! Ask me about any symptoms or health info. ⚠️ Informational only.",
    medQuestionPH:"Write your medical question...",
    medRecord:"Digital Medical Record",
    nameLbl:"Name", birthDate:"Date of Birth", bloodGroup:"Blood Group",
    weightHeight:"Weight / Height", currentMeds:"Current Medications",
    twiceDaily:"Twice daily", morning:"Morning",
    scanQR:"Scan code to view your full record",
    print:"Print", share:"Share", edit:"Edit", editData:"Edit Data",
    drugAlerts:"Drug Reminders", addNewDrug:"Add New Medicine",
    drugName:"Drug Name", drugNamePH:"Paracetamol 500mg",
    dose:"Dose", takeTime:"Time", frequency:"Frequency",
    onceDaily:"Once daily", thrice:"Three times",
    addAlert:"Add Reminder", myDrugs:"My Medications",
    edited:"Edited ✅",
    analyze:"Analyze", sugarLbl:"Sugar",
    goodHealthSimple:"Your health is good. Continue your routine.",
    pregChronicTitle:"Pregnancy & Chronic Disease Follow-up",
    pregnant:"Pregnant Women", nextAppt:"Next Appointment",
    lastWeight:"Last Weight", thisMonth:"this month",
    notes:"Notes", notesPhPH:"Enter your notes...", update:"Update",
    analysisResults:"Receive Analysis Results",
    qrTab:"📷 QR Code", imgTab:"🖼️ Result Image",
    scanQRLab:"Scan QR from the lab", tapSimulate:"Tap to simulate scan",
    labResult:"📋 Test Result - Elite Lab",
    cbcTest:"Complete Blood Count CBC", hemoglobin:"Hemoglobin: ",
    platelets:"Platelets: ", bloodSugar:"Blood Sugar", slightlyHigh:"Slightly High",
    copy:"Copy", remove:"Remove",
    uploadAnalysis:"Upload analysis image", uploadFormats:"JPG, PNG, PDF allowed",
    uploadedResult:"Uploaded analysis result",
    comingSoon:"Coming Soon",
    comingSoonDesc:"Specialized health services section under development.",
    home:"Home", aiNav:"AI", recordNav:"Record",
    dailyNav:"My Journal", drugsNav:"Medicines", pregNav:"Pregnant",
    analysisNav:"Tests", servicesNav:"Services",
    sosTitle:"SOS Emergency Call", sosSub:"An emergency message will be sent immediately to Civil Protection.",
    personalEmergency:"Personal emergency number", civilProt:"Civil Protection (14)", both:"Both",
    sendSos:"Send SOS", sosSent:"SOS sent! 🆘 Help is on the way",
    sosCancelled:"Send cancelled ✅", mistake:"Mistake ❌",
    logoutTitle:"Sign Out", logoutSub:"Are you sure you want to sign out?",
    yesLogout:"Yes, sign out", cancel:"Cancel", ok:"OK",
    diabetes2:"Type 2 Diabetes", hypertension:"High Blood Pressure",
    fullNamePH:"Enter your full name", firstNamePH:"First Name", lastNamePH:"Last Name",
    errName:"⚠️ Please enter your full name", errNameReg:"⚠️ Please enter first and last name",
    imageSaved:"Analysis image saved ✅", imageCopied:"Image link copied 📋", imageShared:"Analysis shared ✅", imageRemoved:"Image removed",
    markAllRead:"Mark all as read", personalInfo:"Personal Information",
    medInfo:"Medical Information", emergencyContact:"Emergency Contact",
    emergencyName:"Name", allergies:"Allergies", saveProfile:"Save Profile",
    logoEditor:"Edit Logo & Name", appNameLbl:"App Name",
    chooseEmoji:"Choose icon", appSubLbl:"Description", tapToChangeLogo:"Tap to change logo",
  }
};

var CL = 'ar'; // current language

function T(key){ return (LANGS[CL] && LANGS[CL][key]) ? LANGS[CL][key] : (LANGS['ar'][key] || key); }

function applyLang(l) {
  CL = l;
  var isRTL = (l === 'ar');
  document.documentElement.lang = l;
  document.documentElement.dir = isRTL ? 'rtl' : 'ltr';
  // Translate all data-t elements
  document.querySelectorAll('[data-t]').forEach(function(el){
    var k = el.getAttribute('data-t');
    var v = T(k);
    if(el.tagName === 'INPUT' || el.tagName === 'TEXTAREA'){
      el.placeholder = v;
    } else if(el.tagName === 'OPTION'){
      el.textContent = v;
    } else {
      el.innerHTML = v;
    }
  });
  // Translate placeholders by data-ph
  document.querySelectorAll('[data-ph]').forEach(function(el){
    var k = el.getAttribute('data-ph');
    el.placeholder = T(k);
  });
}

function setLang(l){
  applyLang(l);
  document.querySelectorAll('.DR').forEach(function(d){d.classList.remove('OP');});
}

// ==================== WILAYAS DATA ====================
var WL=[{n:"أدرار",c:["أدرار","رقان","تيميمون"]},{n:"الشلف",c:["الشلف","تنس","بني حواء"]},{n:"الأغواط",c:["الأغواط","آفلو"]},{n:"أم البواقي",c:["أم البواقي","عين البيضاء"]},{n:"باتنة",c:["باتنة","عين التوتة","آريس"]},{n:"بجاية",c:["بجاية","أكفادو","سيدي عيش"]},{n:"بسكرة",c:["بسكرة","طولقة","سيدي عقبة"]},{n:"بشار",c:["بشار","القنادسة"]},{n:"البليدة",c:["البليدة","بوفاريك","لاربعاء"]},{n:"البويرة",c:["البويرة","سور الغزلان"]},{n:"تمنراست",c:["تمنراست","إن صالح"]},{n:"تبسة",c:["تبسة","الشريعة"]},{n:"تلمسان",c:["تلمسان","غزوات","رمشي","مغنية","بني مستار","المنصورة","بني سنوس","حمام بوغرارة","هنين","الشيخلا"]},{n:"تيارت",c:["تيارت","فرندة","مهدية"]},{n:"تيزي وزو",c:["تيزي وزو","ذراع بن خدة","عزازقة"]},{n:"الجزائر",c:["الجزائر","باب الوادي","بئر مراد رايس","دار البيضاء"]},{n:"الجلفة",c:["الجلفة","عين وسارة","مسعد"]},{n:"جيجل",c:["جيجل","الطاهير"]},{n:"سطيف",c:["سطيف","العلمة","قجال"]},{n:"سعيدة",c:["سعيدة","يوب"]},{n:"سكيكدة",c:["سكيكدة","القل"]},{n:"سيدي بلعباس",c:["سيدي بلعباس","تلاغ"]},{n:"عنابة",c:["عنابة","الحجار"]},{n:"قالمة",c:["قالمة","هيليوبوليس"]},{n:"قسنطينة",c:["قسنطينة","الخروب","عين أبيد"]},{n:"المدية",c:["المدية","الأربعاء"]},{n:"مستغانم",c:["مستغانم","مزغران"]},{n:"المسيلة",c:["المسيلة","بوسعادة"]},{n:"معسكر",c:["معسكر","سيق"]},{n:"ورقلة",c:["ورقلة","حاسي مسعود"]},{n:"وهران",c:["وهران","عرزيو","المرسى الكبير"]},{n:"البيض",c:["البيض","القعقاع"]},{n:"إليزي",c:["إليزي","جانت"]},{n:"برج بوعريريج",c:["برج بوعريريج","بئر الأرش"]},{n:"بومرداس",c:["بومرداس","برج منايل"]},{n:"الطارف",c:["الطارف","القالة"]},{n:"تيندوف",c:["تيندوف"]},{n:"تسمسيلت",c:["تسمسيلت"]},{n:"الوادي",c:["الوادي","الرباح"]},{n:"خنشلة",c:["خنشلة","بابار"]},{n:"سوق أهراس",c:["سوق أهراس","مداوروش"]},{n:"تيبازة",c:["تيبازة","الشرفة"]},{n:"ميلة",c:["ميلة","فرجيوة"]},{n:"عين الدفلى",c:["عين الدفلى"]},{n:"النعامة",c:["النعامة","المشرية"]},{n:"عين تيموشنت",c:["عين تيموشنت"]},{n:"غرداية",c:["غرداية","المنيعة","القرارة"]},{n:"غليزان",c:["غليزان","جديوية"]},{n:"تيميمون",c:["تيميمون"]},{n:"برج باجي مختار",c:["برج باجي مختار"]},{n:"أولاد جلال",c:["أولاد جلال"]},{n:"بني عباس",c:["بني عباس"]},{n:"إن صالح",c:["إن صالح"]},{n:"إن قزام",c:["إن قزام"]},{n:"توقرت",c:["توقرت"]},{n:"جانت",c:["جانت"]},{n:"المغير",c:["المغير"]},{n:"المنيعة",c:["المنيعة"]}];
var SP=["طب عام","أمراض القلب","أمراض الجهاز الهضمي","طب الأطفال","أمراض النساء والتوليد","أمراض الجلدية","طب العيون","أمراض الأذن والأنف","أمراض الجهاز العصبي","أمراض الرئة","الجراحة العامة","جراحة العظام","جراحة القلب","طب الأسنان","الطب النفسي","طب الكلى","الغدد الصماء والسكري","أمراض الدم","طب الطوارئ","أمراض الروماتيزم","طب الأورام","جراحة تجميلية","طب الرياضة","التخدير","الأشعة الطبية"];
var AIR=["بناءً على ما ذكرته، أنصح بشرب الماء والراحة الكافية.","هذه الأعراض شائعة. راجع طبيبك إذا استمرت أكثر من يومين.","قياساتك ضمن المعدل الطبيعي. استمر في روتينك الصحي 👍","أنصح بتقليل السكريات وممارسة الرياضة 30 دقيقة يومياً.","التزم بمواعيد أدويتك بانتظام لنتائج أفضل."];
var USER={name:'المريض',av:'م'};
var currentImgData = null;

window.onload = function(){
  // Populate wilayas
  document.querySelectorAll('.FS').forEach(function(s){
    if(s.options.length > 0 && s.options[0].text.indexOf('الولاية') > -1){
      WL.forEach(function(w){var o=document.createElement('option');o.value=w.n;o.textContent=w.n;s.appendChild(o);});
    }
  });
  var sf = document.getElementById('SPF');
  if(sf) SP.forEach(function(s){var o=document.createElement('option');o.value=s;o.textContent=s;sf.appendChild(o);});

  // Card toggle
  document.querySelectorAll('.CH').forEach(function(h){
    h.addEventListener('click',function(){
      var c=this.closest('.CD');
      var was=c.classList.contains('OP');
      document.querySelectorAll('.CD').forEach(function(x){x.classList.remove('OP');});
      if(!was)c.classList.add('OP');
    });
  });

  // Nav buttons
  document.querySelectorAll('.NV').forEach(function(b){
    b.addEventListener('click',function(){
      var v=this.getAttribute('data-v');
      document.querySelectorAll('.VW').forEach(function(x){x.classList.remove('A');});
      document.querySelectorAll('.NV').forEach(function(x){x.classList.remove('A');});
      document.getElementById(v).classList.add('A');
      this.classList.add('A');
    });
  });

  document.getElementById('BN').addEventListener('click',function(e){
    e.stopPropagation();
    var np=document.getElementById('NP');
    if(np && np.classList.contains('OP')){closeAllPanels();}
    else{opPanel('NP');}
  });
  document.getElementById('BLG').addEventListener('click',function(e){e.stopPropagation();tgD('LD');});
  document.getElementById('BDK').addEventListener('click',function(){toggleDark();});
  document.getElementById('BLO').addEventListener('click',function(){opM('MLGO');});
  document.getElementById('BSOS').addEventListener('click',function(){opM('MSOS');});
  document.addEventListener('click',function(){document.querySelectorAll('.DR').forEach(function(d){d.classList.remove('OP');});});

  // Close NP notification popup on outside click
  document.addEventListener('click', function(e){
    var np = document.getElementById('NP');
    var bellBtn = document.getElementById('BN');
    var ptBell = document.querySelector('.pt-action-btn[title="الإشعارات"]');
    if(!np || !np.classList.contains('OP')) return;
    var clickedInside = np.contains(e.target)
      || (bellBtn && bellBtn.contains(e.target))
      || (ptBell && ptBell.contains(e.target));
    if(!clickedInside) closeAllPanels();
  });

  // Init QR tab active
  var rcQr = document.getElementById('rc-qr');
  var rcImg = document.getElementById('rc-img');
  if(rcQr) rcQr.style.display = 'block';
  if(rcImg) rcImg.style.display = 'none';
};

function shTab(t){
  document.getElementById('TF1').style.display=t==='in'?'block':'none';
  document.getElementById('TF2').style.display=t==='up'?'block':'none';
  document.getElementById('TL1').classList.toggle('A',t==='in');
  document.getElementById('TL2').classList.toggle('A',t==='up');
}

function doLin(){
  var n=(document.getElementById('LN').value||'').trim();
  var e=document.getElementById('LERR');
  if(!n){e.textContent=T('errName');e.style.display='block';return;}
  e.style.display='none';
  USER.name=n; USER.av=n.charAt(0).toUpperCase();
  document.getElementById('TAV').textContent=USER.av;
  document.getElementById('TNM').textContent=USER.name;
  document.getElementById('RAV').textContent=USER.av;
  document.getElementById('RNM').textContent=USER.name;
  document.getElementById('RFN').textContent=USER.name;
  document.getElementById('LS').style.display='none';
  document.getElementById('APP').style.display='block';
}

function doReg(){
  var f=(document.getElementById('SF').value||'').trim();
  var l=(document.getElementById('SL').value||'').trim();
  var e=document.getElementById('SERR');
  if(!f||!l){e.textContent=T('errNameReg');e.style.display='block';return;}
  e.style.display='none';
  var n=f+' '+l;
  USER.name=n; USER.av=f.charAt(0).toUpperCase();
  document.getElementById('TAV').textContent=USER.av;
  document.getElementById('TNM').textContent=USER.name;
  document.getElementById('RAV').textContent=USER.av;
  document.getElementById('RNM').textContent=USER.name;
  document.getElementById('RFN').textContent=USER.name;
  document.getElementById('LS').style.display='none';
  document.getElementById('APP').style.display='block';
}

function doLgo(){
  clM('MLGO');
  window.location.reload();
}

function tgD(id){var d=document.getElementById(id);var o=d.classList.contains('OP');document.querySelectorAll('.DR').forEach(function(x){x.classList.remove('OP');});if(!o)d.classList.add('OP');}
function opM(id){document.getElementById(id).classList.add('OP');}
function clM(id){document.getElementById(id).classList.remove('OP');}
function sa(msg){document.getElementById('ALTXT').textContent=msg;opM('MAL');}
function goV(v){document.querySelectorAll('.VW').forEach(function(x){x.classList.remove('A');});document.querySelectorAll('.NV').forEach(function(x){x.classList.remove('A');});document.getElementById(v).classList.add('A');var nb=document.querySelector('.NV[data-v="'+v+'"]');if(nb)nb.classList.add('A');}
function sw(btn,sec,tab){var p=btn.closest('.CB');if(!p)p=btn.closest('.VW');p.querySelectorAll('[id^="'+sec+'-"]').forEach(function(e){e.style.display='none';});var el=document.getElementById(sec+'-'+tab);if(el)el.style.display='block';var ts=btn.closest('.TS')||btn.closest('.RES-TABS');if(ts)ts.querySelectorAll('.TB2').forEach(function(b){b.classList.remove('A');});btn.classList.add('A');}
function ldCom(sel,cardId){var cid='COM_'+cardId;var c=document.getElementById(cid);if(!c)return;c.innerHTML='<option value="">'+T('commune')+'</option>';var w=WL.find(function(x){return x.n===sel.value;});if(w)w.c.forEach(function(n){var o=document.createElement('option');o.value=n;o.textContent=n;c.appendChild(o);});}
function bkD(name,spec){var n=document.getElementById('BKN');var s=document.getElementById('BKS');if(n)n.value=name;if(s)s.value=spec;var c=document.getElementById('CDO');c.classList.add('OP');var btn=c.querySelector('.TB2:nth-child(2)');if(btn)btn.click();}
function opCV(scr,lst){document.getElementById(lst).style.display='none';document.getElementById(scr).classList.add('A');}
function clCV(scr,lst){document.getElementById(scr).classList.remove('A');document.getElementById(lst).style.display='block';}
function opAI(s){['da','pr','ch','gn'].forEach(function(x){var el=document.getElementById('AI_'+x.toUpperCase());if(el)el.style.display=x===s?'block':'none';});}
function sc(inId,msId,sndr){var i=document.getElementById(inId);var t=(i.value||'').trim();if(!t)return;var m=document.getElementById(msId);var el=document.createElement('div');el.className='MS S';el.innerHTML='<div class="MN2">'+T('you')+'</div>'+t;m.appendChild(el);i.value='';m.scrollTop=m.scrollHeight;setTimeout(function(){var r=document.createElement('div');r.className='MS R';r.innerHTML='<div class="MN2">'+sndr+'</div>'+T('saved').replace('✅','')+'👍';m.appendChild(r);m.scrollTop=m.scrollHeight;},700);}
function ai(inId,msId){var i=document.getElementById(inId);var t=(i.value||'').trim();if(!t)return;var m=document.getElementById(msId);var el=document.createElement('div');el.className='MS S';el.innerHTML='<div class="MN2">'+T('you')+'</div>'+t;m.appendChild(el);i.value='';m.scrollTop=m.scrollHeight;setTimeout(function(){var r=document.createElement('div');r.className='MS R';r.innerHTML='<div class="MN2">🤖 AI</div>'+AIR[Math.floor(Math.random()*AIR.length)];m.appendChild(r);m.scrollTop=m.scrollHeight;},900);}
function addDr(){var n=(document.getElementById('DN2').value||'').trim();if(!n){sa(T('drugName')+' ?');return;}var d=document.getElementById('DD2').value||'';var t=document.getElementById('DT2').value||'---';var f=document.getElementById('DF2').value||'';var el=document.createElement('div');el.className='DI';el.innerHTML='<div><div class="DN">💊 '+n+' '+d+'</div><div class="DT">🕗 '+t+' - '+f+'</div></div><div class="DA"><button class="BT BO" style="padding:4px 8px;" onclick="sa(T(\'edited\'))"><i class="fas fa-edit"></i></button><button class="BT BR" style="padding:4px 8px;" onclick="this.closest(\'.DI\').remove()"><i class="fas fa-trash"></i></button></div>';document.getElementById('DRL').appendChild(el);document.getElementById('DN2').value='';document.getElementById('DD2').value='';sa(T('saved'));}
function shRC(){document.getElementById('RCR').style.display=document.getElementById('RCR').style.display==='none'?'block':'none';}
function anD(){var r=document.getElementById('DAR')||document.getElementById('DLR');if(r)r.style.display='block';}

/* analyzeDaily & shareDaily are now handled by daily_journal.js (يومياتي module).
   These stubs remain for backward-compatibility with any legacy callers. */
function analyzeDaily(){
  if(typeof window._djAnalyze==='function') window._djAnalyze();
}
function shareDaily(){
  if(typeof window._djShare==='function') window._djShare();
}
function addNotification(title, sub, colorHex){
  var list=document.getElementById('NP-LIST');
  if(!list)return;
  var el=document.createElement('div');
  el.className='NI2 unread';
  el.onclick=function(){markRead(el);};
  el.innerHTML='<div class="NI2-IC" style="background:rgba(245,158,11,.12);color:#d97706;"><i class="fas fa-heartbeat"></i></div><div style="flex:1;min-width:0;"><div class="NI2-TT">'+title+'</div><div class="NI2-ST">'+sub+'</div><div class="NI2-TM">الآن</div></div><span class="NMARK"></span>';
  list.insertBefore(el, list.firstChild);
  notifCount++;
  updateBadge();
}

// ===== PRINT / SHARE / COPY =====
function printRecord(){
  var win=window.open('','_blank');
  win.document.write('<html><head><meta charset="UTF-8"><title>'+T('medRecord')+'</title><style>body{font-family:Arial,sans-serif;padding:20px;direction:'+( CL==='ar'?'rtl':'ltr')+';}h2{color:#00b4d8;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #ddd;padding:8px;}th{background:#f0f9ff;}</style></head><body>');
  win.document.write('<h2>🏥 MediSmart - '+T('medRecord')+'</h2>');
  win.document.write('<table><tr><th>'+T('nameLbl')+'</th><td>'+USER.name+'</td></tr>');
  win.document.write('<tr><th>'+T('birthDate')+'</th><td>15/03/1985</td></tr>');
  win.document.write('<tr><th>'+T('bloodGroup')+'</th><td style="color:red;font-weight:bold;">O+</td></tr>');
  win.document.write('<tr><th>'+T('weightHeight')+'</th><td>78kg / 178cm</td></tr>');
  win.document.write('<tr><th>'+T('chronicDis')+'</th><td>'+T('diabetes2')+', '+T('hypertension')+'</td></tr>');
  win.document.write('<tr><th>'+T('currentMeds')+'</th><td>ميتفورمين 500mg, أملوديبين 5mg</td></tr></table>');
  win.document.write('<p style="margin-top:20px;color:#9ca3af;font-size:12px;">MediSmart © 2025</p></body></html>');
  win.document.close(); win.print();
}

function shareRecord(){
  var text = T('medRecord')+'\n'+T('nameLbl')+': '+USER.name+'\n'+T('bloodGroup')+': O+\n'+T('chronicDis')+': '+T('diabetes2')+'\nMediSmart App';
  if(navigator.share){navigator.share({title:'MediSmart',text:text}).catch(function(){});}
  else{navigator.clipboard.writeText(text).then(function(){sa(T('imageCopied'));}).catch(function(){sa(T('share')+' ✅');});}
}

function printResult(){
  var win=window.open('','_blank');
  win.document.write('<html><head><meta charset="UTF-8"><title>'+T('labResult')+'</title><style>body{font-family:Arial,sans-serif;padding:20px;direction:'+(CL==='ar'?'rtl':'ltr')+';}h2{color:#00b4d8;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #ddd;padding:8px;}th{background:#f0f9ff;}</style></head><body>');
  win.document.write('<h2>🔬 '+T('labResult')+'</h2>');
  win.document.write('<table><tr><th>'+T('cbcTest')+'</th><th>'+T('normal')+'</th></tr>');
  win.document.write('<tr><td>'+T('hemoglobin')+'</td><td>14.5 g/dL</td></tr>');
  win.document.write('<tr><td>'+T('platelets')+'</td><td>250 K/μL</td></tr>');
  win.document.write('<tr><td>'+T('bloodSugar')+'</td><td style="color:#d97706;font-weight:bold;">118 mg/dL ('+T('slightlyHigh')+')</td></tr></table>');
  win.document.write('<p style="margin-top:20px;color:#9ca3af;font-size:12px;">MediSmart © 2025</p></body></html>');
  win.document.close(); win.print();
}

function shareResult(){
  var text=T('labResult')+'\n'+T('cbcTest')+': '+T('normal')+'\n'+T('hemoglobin')+'14.5 g/dL\n'+T('bloodSugar')+': 118 mg/dL\nMediSmart App';
  if(navigator.share){navigator.share({title:'MediSmart',text:text}).catch(function(){});}
  else{navigator.clipboard.writeText(text).then(function(){sa(T('imageCopied'));}).catch(function(){sa(T('share')+' ✅');});}
}

function copyResult(){
  var text=T('labResult')+'\n'+T('cbcTest')+': '+T('normal')+'\n'+T('hemoglobin')+'14.5 g/dL\n'+T('platelets')+'250 K/μL\n'+T('bloodSugar')+': 118 mg/dL ('+T('slightlyHigh')+')';
  navigator.clipboard.writeText(text).then(function(){sa(T('imageCopied'));}).catch(function(){sa(T('copy')+' ✅');});
}

// ===== IMAGE UPLOAD =====
function previewImg(input){
  var file=input.files[0];
  if(!file)return;
  var reader=new FileReader();
  reader.onload=function(e){
    currentImgData=e.target.result;
    var prv=document.getElementById('IMG_PRV');
    prv.src=currentImgData;
    prv.style.display='block';
    document.getElementById('IMG_ACTIONS').style.display='block';
    document.querySelector('.IMG-UP').style.display='none';
  };
  reader.readAsDataURL(file);
}

function saveImg(){sa(T('imageSaved'));}

function shareImg(){
  if(!currentImgData){return;}
  if(navigator.share){
    fetch(currentImgData).then(function(r){return r.blob();}).then(function(blob){
      var file=new File([blob],'analysis.jpg',{type:'image/jpeg'});
      navigator.share({files:[file],title:'MediSmart '+T('analysisResults')}).catch(function(){sa(T('imageShared'));});
    }).catch(function(){sa(T('imageShared'));});
  } else {sa(T('imageShared'));}
}

function printImg(){
  if(!currentImgData)return;
  var win=window.open('','_blank');
  win.document.write('<html><head><title>'+T('analysisResults')+'</title></head><body style="text-align:center;padding:20px;">');
  win.document.write('<h2 style="color:#00b4d8;">🔬 MediSmart - '+T('analysisResults')+'</h2>');
  win.document.write('<img src="'+currentImgData+'" style="max-width:100%;border-radius:8px;">');
  win.document.write('<p style="color:#9ca3af;font-size:12px;margin-top:20px;">MediSmart © 2025</p></body></html>');
  win.document.close(); win.print();
}

function copyImg(){
  if(!currentImgData)return;
  navigator.clipboard.writeText(T('analysisResults')+' - MediSmart').then(function(){sa(T('imageCopied'));}).catch(function(){sa(T('copy')+' ✅');});
}

function removeImg(){
  currentImgData=null;
  document.getElementById('IMG_PRV').style.display='none';
  document.getElementById('IMG_PRV').src='';
  document.getElementById('IMG_ACTIONS').style.display='none';
  document.querySelector('.IMG-UP').style.display='block';
  document.getElementById('IMG_INPUT').value='';
  sa(T('imageRemoved'));
}

// ==================== DARK MODE ====================
function toggleDark(){
  var isDark = document.body.classList.toggle('DK');
  var btn = document.getElementById('BDK');
  btn.innerHTML = isDark ? '<i class="fas fa-sun" style="color:#f59e0b;"></i>' : '<i class="fas fa-moon"></i>';
  localStorage.setItem('medismart_dark', isDark ? '1' : '0');
}

// ==================== PANELS (Notifications / Profile / Logo) ====================
function opPanel(id){
  closeAllPanels();
  document.getElementById(id).classList.add('OP');
  // Overlay only for full-panel slides (profile, logo editor), not notification popup
  if(id !== 'NP'){
    document.getElementById('OVLAY').classList.add('OP');
    document.body.style.overflow='hidden';
  }
}
function closeAllPanels(){
  ['NP','PP2','LE'].forEach(function(id){
    var el=document.getElementById(id);
    if(el) el.classList.remove('OP');
  });
  var ov=document.getElementById('OVLAY');
  if(ov) ov.classList.remove('OP');
  document.body.style.overflow='';
}

// ==================== NOTIFICATIONS ====================
var notifCount = 3;
function markRead(el){
  if(el.classList.contains('unread')){
    el.classList.remove('unread');
    el.classList.add('read');
    var dot = el.querySelector('.NMARK');
    if(dot) dot.remove();
    notifCount = Math.max(0, notifCount - 1);
    updateBadge();
  }
}
function markAllRead(){
  document.querySelectorAll('.NI2.unread').forEach(function(n){ markRead(n); });
}
function updateBadge(){
  var b = document.getElementById('NBADGE');
  if(!b) return;
  if(notifCount > 0){
    b.textContent = notifCount;
    b.style.display='flex';
  } else {
    b.style.display='none';
  }
}

// ==================== PROFILE ====================
function updateProfileName(val){
  if(!val.trim()) return;
  USER.name = val;
  // Update topbar
  document.getElementById('TAV').textContent = val.trim().charAt(0).toUpperCase();
  document.getElementById('TNM').textContent = val;
  // Update profile panel header
  document.getElementById('PP2-NM').textContent = val;
  document.getElementById('PROF-AV-TXT').textContent = val.trim().charAt(0).toUpperCase();
  // Update record section
  var rav=document.getElementById('RAV'); if(rav) rav.textContent=val.trim().charAt(0).toUpperCase();
  var rnm=document.getElementById('RNM'); if(rnm) rnm.textContent=val;
  var rfn=document.getElementById('RFN'); if(rfn) rfn.textContent=val;
}

function setProfImg(input){
  var file = input.files[0]; if(!file) return;
  var reader = new FileReader();
  reader.onload = function(e){
    var img = document.getElementById('PROF-IMG-DISP');
    var txt = document.getElementById('PROF-AV-TXT');
    img.src = e.target.result;
    img.style.display = 'block';
    txt.style.display = 'none';
    // Also update topbar avatar with image
    var tav = document.getElementById('TAV');
    tav.style.backgroundImage = 'url('+e.target.result+')';
    tav.style.backgroundSize = 'cover';
    tav.textContent = '';
  };
  reader.readAsDataURL(file);
}

function saveProfile(){
  var name = document.getElementById('PP2-FN').value.trim();
  if(name) updateProfileName(name);
  closeAllPanels();
  sa(T('saved'));
}

// ==================== LOGO EDITOR ====================
function updateAppName(val){
  document.getElementById('APP-NAME').textContent = val || 'MediSmart';
  document.title = val || 'MediSmart';
}

function setLogoEmoji(btn, emoji){
  // Remove active state from all emoji buttons
  document.querySelectorAll('.LE-BD button').forEach(function(b){ b.style.borderColor='rgba(0,180,216,.2)'; b.style.background='rgba(0,180,216,.06)'; });
  btn.style.borderColor='#00b4d8'; btn.style.background='rgba(0,180,216,.18)';
  // Update header logo
  document.getElementById('LOGO-EMJ').textContent = emoji;
  document.getElementById('LOGO-EMJ').style.display = 'block';
  document.getElementById('LOGO-IMG').style.display = 'none';
  // Update logo editor preview
  document.getElementById('LE-LGP-EMJ').textContent = emoji;
  document.getElementById('LE-LGP-EMJ').style.display = 'block';
  document.getElementById('LE-LGP-IMG').style.display = 'none';
}

function setLogoImg(input){
  var file = input.files[0]; if(!file) return;
  var reader = new FileReader();
  reader.onload = function(e){
    // Update header
    var hImg = document.getElementById('LOGO-IMG');
    var hEmj = document.getElementById('LOGO-EMJ');
    hImg.src = e.target.result;
    hImg.style.display = 'block';
    hEmj.style.display = 'none';
    // Update editor preview
    var leImg = document.getElementById('LE-LGP-IMG');
    var leEmj = document.getElementById('LE-LGP-EMJ');
    leImg.src = e.target.result;
    leImg.style.display = 'block';
    leEmj.style.display = 'none';
  };
  reader.readAsDataURL(file);
}


// ==================== AI NURSE CHAT ====================
var ANC_OPEN = false;

function toggleAIChat(){
  ANC_OPEN = !ANC_OPEN;
  var el = document.getElementById('ANC');
  if(ANC_OPEN){ el.classList.add('OP'); scrollANC(); }
  else { el.classList.remove('OP'); }
}

function scrollANC(){
  var m = document.getElementById('ANC-MSGS');
  if(m) setTimeout(function(){ m.scrollTop = m.scrollHeight; }, 80);
}

function askChip(btn, question){
  // Hide chips after use to save space
  btn.style.display='none';
  addANCMsg(question, 'U');
  answerAI(question);
}

function sendAIChat(){
  var inp = document.getElementById('ANC-IN');
  var q = (inp.value||'').trim();
  if(!q) return;
  inp.value = '';
  addANCMsg(q, 'U');
  answerAI(q);
}

function addANCMsg(text, side){
  var m = document.getElementById('ANC-MSGS');
  var el = document.createElement('div');
  el.className = 'ANC-MSG ' + side;
  var lbl = side==='U' ? '<div class="lbl">🧠 الممرض الذكي</div>' : '<div class="lbl">👤 أنت</div>';
  // For bot messages show label, for user no
  if(side==='U'){
    el.innerHTML = '<div class="lbl">👤 أنت</div>' + text;
  } else {
    el.innerHTML = '<div class="lbl">🧠 الممرض الذكي</div>' + text;
  }
  m.appendChild(el);
  scrollANC();
}

function addANCTyping(){
  var m = document.getElementById('ANC-MSGS');
  var el = document.createElement('div');
  el.className = 'ANC-MSG U';
  el.id = 'ANC-TYPING';
  el.innerHTML = '<div class="lbl">🧠 الممرض الذكي</div><span style="letter-spacing:3px;font-size:16px;">•••</span>';
  m.appendChild(el);
  scrollANC();
  return el;
}

function answerAI(q){
  var typing = addANCTyping();
  setTimeout(function(){
    if(typing) typing.remove();
    var ans = getAIAnswer(q);
    addANCMsg(ans, 'U');
  }, 900 + Math.random()*600);
}

function getAIAnswer(q){
  var ql = q.toLowerCase();

  // ===== SPECIALTY ROUTING =====
  if(ql.indexOf('تخصص') > -1 || ql.indexOf('طبيب') > -1 || ql.indexOf('أذهب') > -1 || ql.indexOf('اذهب') > -1){
    if(ql.indexOf('قلب') > -1 || ql.indexOf('صدر') > -1 || ql.indexOf('ضغط') > -1)
      return '❤️ <strong>أمراض القلب والأوعية الدموية</strong><br>للضغط المرتفع، ضربات القلب غير المنتظمة، ألم الصدر.<br>🔎 ابحث عن: <em>طبيب قلب (Cardiologue)</em>';
    if(ql.indexOf('سكر') > -1 || ql.indexOf('غدة') > -1 || ql.indexOf('درقية') > -1)
      return '🍬 <strong>الغدد الصماء والسكري</strong><br>لمرض السكري، مشاكل الغدة الدرقية، الهرمونات.<br>🔎 ابحث عن: <em>طبيب غدد (Endocrinologue)</em>';
    if(ql.indexOf('طفل') > -1 || ql.indexOf('رضيع') > -1 || ql.indexOf('أطفال') > -1)
      return '👶 <strong>طب الأطفال</strong><br>للأطفال من الولادة حتى 16 سنة، الحمى، النمو، التطعيمات.<br>🔎 ابحث عن: <em>طبيب أطفال (Pédiatre)</em>';
    if(ql.indexOf('جلد') > -1 || ql.indexOf('حساسية') > -1 || ql.indexOf('طفح') > -1)
      return '🌿 <strong>أمراض الجلدية</strong><br>للطفح الجلدي، الحساسية، حب الشباب، الأكزيما.<br>🔎 ابحث عن: <em>طبيب جلدية (Dermatologue)</em>';
    if(ql.indexOf('عظم') > -1 || ql.indexOf('مفصل') > -1 || ql.indexOf('ظهر') > -1 || ql.indexOf('ركبة') > -1)
      return '🦴 <strong>جراحة العظام والمفاصل</strong><br>لآلام الظهر، الركبة، كسور العظام، مشاكل المفاصل.<br>🔎 ابحث عن: <em>طبيب عظام (Orthopédiste)</em>';
    if(ql.indexOf('عين') > -1 || ql.indexOf('نظر') > -1 || ql.indexOf('بصر') > -1)
      return '👁️ <strong>طب العيون</strong><br>لمشاكل النظر، التهاب العيون، الماء الأزرق/الأبيض.<br>🔎 ابحث عن: <em>طبيب عيون (Ophtalmologue)</em>';
    if(ql.indexOf('أنف') > -1 || ql.indexOf('أذن') > -1 || ql.indexOf('حلق') > -1 || ql.indexOf('سمع') > -1)
      return '👂 <strong>أمراض الأذن والأنف والحنجرة</strong><br>للسمع، الأنف المسدود، التهاب الحلق المزمن.<br>🔎 ابحث عن: <em>طبيب أنف وأذن وحنجرة (ORL)</em>';
    if(ql.indexOf('معدة') > -1 || ql.indexOf('هضم') > -1 || ql.indexOf('أمعاء') > -1 || ql.indexOf('كبد') > -1)
      return '🫀 <strong>أمراض الجهاز الهضمي</strong><br>لآلام البطن، القولون، الكبد، مشاكل الهضم.<br>🔎 ابحث عن: <em>طبيب هضمية (Gastro-entérologue)</em>';
    if(ql.indexOf('كلى') > -1 || ql.indexOf('بول') > -1 || ql.indexOf('مثانة') > -1)
      return '💧 <strong>طب الكلى والمسالك البولية</strong><br>لمشاكل الكلى، التبول المتكرر، حصى الكلى.<br>🔎 ابحث عن: <em>طبيب كلى (Néphrologue) أو مسالك (Urologue)</em>';
    if(ql.indexOf('رئة') > -1 || ql.indexOf('تنفس') > -1 || ql.indexOf('ربو') > -1 || ql.indexOf('سعال') > -1)
      return '🫁 <strong>أمراض الرئة والتنفس</strong><br>للربو، ضيق التنفس، السعال المزمن، التدخين.<br>🔎 ابحث عن: <em>طبيب رئة (Pneumologue)</em>';
    if(ql.indexOf('نفسي') > -1 || ql.indexOf('اكتئاب') > -1 || ql.indexOf('قلق') > -1 || ql.indexOf('نوم') > -1)
      return '🧠 <strong>الطب النفسي والصحة النفسية</strong><br>للاكتئاب، القلق، اضطرابات النوم، التوتر الشديد.<br>🔎 ابحث عن: <em>طبيب نفساني (Psychiatre)</em>';
    if(ql.indexOf('أعصاب') > -1 || ql.indexOf('عصب') > -1 || ql.indexOf('شلل') > -1 || ql.indexOf('رأس') > -1)
      return '🧬 <strong>أمراض الجهاز العصبي</strong><br>للصداع الشديد، الدوار المزمن، الشلل، التنميل.<br>🔎 ابحث عن: <em>طبيب أعصاب (Neurologue)</em>';
    if(ql.indexOf('نساء') > -1 || ql.indexOf('حمل') > -1 || ql.indexOf('دورة') > -1 || ql.indexOf('رحم') > -1)
      return '👩‍⚕️ <strong>أمراض النساء والتوليد</strong><br>للحمل، الدورة الشهرية، مشاكل الجهاز التناسلي.<br>🔎 ابحث عن: <em>طبيب نساء (Gynécologue)</em>';
    if(ql.indexOf('أسنان') > -1 || ql.indexOf('سن') > -1 || ql.indexOf('لثة') > -1)
      return '🦷 <strong>طب الأسنان</strong><br>لآلام الأسنان، التسوس، مشاكل اللثة.<br>🔎 ابحث عن: <em>طبيب أسنان (Dentiste)</em>';
    // Generic specialty guide
    return '🩺 <strong>دليل اختيار التخصص</strong><br>أخبرني عن أعراضك أو المنطقة المتألمة وسأرشدك للتخصص الصحيح! مثلاً: "عندي ألم في المعدة" أو "عندي مشكلة في الجلد"';
  }

  // ===== EMERGENCY SIGNS =====
  if(ql.indexOf('طوارئ') > -1 || ql.indexOf('مستعجل') > -1 || ql.indexOf('خطر') > -1){
    return '🚨 <strong>متى تذهب للطوارئ فوراً؟</strong><br>• ألم شديد في الصدر أو ضيق تنفس مفاجئ<br>• فقدان الوعي أو الإغماء<br>• شلل مفاجئ في وجه أو يد أو رجل<br>• نزيف لا يتوقف<br>• انخفاض حاد في السكر (أقل من 60)<br>• ضغط دم فوق 180/120<br>• حمى شديدة عند الرضع (فوق 38.5°)<br><br>📞 اتصل: <strong>14 (الحماية المدنية)</strong> أو <strong>1021 (SAMU)</strong>';
  }

  // ===== BLOOD PRESSURE =====
  if(ql.indexOf('ضغط') > -1){
    if(ql.indexOf('قياس') > -1 || ql.indexOf('كيف') > -1)
      return '📊 <strong>كيف تقيس ضغط الدم بشكل صحيح؟</strong><br>1️⃣ استرح 5 دقائق قبل القياس<br>2️⃣ اجلس مع دعم الظهر والقدمين على الأرض<br>3️⃣ ضع الكفة على مستوى القلب<br>4️⃣ لا تتكلم أثناء القياس<br>5️⃣ خذ قياسين بفارق دقيقتين<br><br>✅ الطبيعي: <strong>أقل من 120/80</strong><br>⚠️ مرتفع: <strong>فوق 130/85</strong><br>🚨 خطر: <strong>فوق 160/100</strong>';
    return '❤️ <strong>ارتفاع ضغط الدم</strong><br><strong>الأعراض:</strong> صداع، دوار، طنين الأذن، تعب<br><strong>الأسباب:</strong> التوتر، الملح الزائد، السمنة، التدخين<br><strong>النصائح:</strong><br>• قلّل الملح في طعامك<br>• مارس رياضة خفيفة 30 دقيقة/يوم<br>• تجنب التوتر والقهوة المفرطة<br>• خذ أدويتك بانتظام<br><br>🩺 راجع <em>طبيب قلب أو طب عام</em>';
  }

  // ===== SUGAR/DIABETES =====
  if(ql.indexOf('سكر') > -1 || ql.indexOf('سكري') > -1){
    if(ql.indexOf('تحكم') > -1 || ql.indexOf('إدارة') > -1 || ql.indexOf('ادارة') > -1)
      return '💉 <strong>التحكم في سكر الدم</strong><br>🥗 النظام الغذائي: تجنب السكريات البسيطة، كل وجبات منتظمة<br>🚶 الرياضة: 30 دقيقة مشي يومياً تخفض السكر 20-30%<br>💊 الأدوية: التزم بمواعيد الميتفورمين/الأنسولين<br>📊 المراقبة: قس السكر قبل الأكل وبعد ساعتين<br><br>🎯 الهدف: صيام <strong>70-130</strong> • بعد أكل <strong>أقل من 180</strong> mg/dL';
    return '🍬 <strong>أعراض السكري</strong><br>• كثرة التبول خاصة ليلاً<br>• عطش مستمر<br>• جوع متواصل رغم الأكل<br>• تعب وإرهاق<br>• بطء التئام الجروح<br>• تنميل في القدمين<br>• ضبابية في النظر<br><br>⚠️ إذا كان لديك 3 أعراض أو أكثر، راجع طبيبك لإجراء تحليل السكر.<br>🩺 راجع: <em>طبيب غدد أو طب عام</em>';
  }

  // ===== HEADACHE =====
  if(ql.indexOf('صداع') > -1 || ql.indexOf('وجع رأس') > -1){
    return '🤕 <strong>أسباب الصداع المستمر</strong><br>• التوتر والإجهاد الذهني (الأكثر شيوعاً)<br>• الجفاف - قلة شرب الماء<br>• اضطرابات النوم<br>• ضغط الدم المرتفع<br>• مشاكل في الرؤية (تحتاج نظارة)<br>• الشقيقة (الميغرين)<br><br>💡 <strong>نصائح سريعة:</strong><br>• اشرب كوب ماء فوراً<br>• استرح في غرفة هادئة مظلمة<br>• ضع كمادة باردة على الجبهة<br><br>🚨 اذهب للطوارئ إذا كان الصداع مفاجئاً وشديداً جداً مع تصلب الرقبة.';
  }

  // ===== FEVER =====
  if(ql.indexOf('حمى') > -1 || ql.indexOf('حرارة') > -1 || ql.indexOf('سخانة') > -1){
    return '🌡️ <strong>الحمى - متى تقلق؟</strong><br>• <strong>37-37.9°</strong> : طبيعي، راقب الوضع<br>• <strong>38-38.9°</strong> : حمى خفيفة، خافض الحرارة والراحة<br>• <strong>39-39.9°</strong> : حمى متوسطة، طبيب خلال 24 ساعة<br>• <strong>فوق 40°</strong> : 🚨 اذهب للطوارئ فوراً<br><br>⚠️ <strong>عند الأطفال فوق 38.5°: اذهب للطبيب</strong><br><br>💊 خافض الحرارة: باراسيتامول 500mg للبالغين';
  }

  // ===== SLEEP =====
  if(ql.indexOf('نوم') > -1 || ql.indexOf('أرق') > -1){
    return '😴 <strong>نصائح لنوم أفضل</strong><br>• نم واستيقظ في نفس الوقت يومياً<br>• تجنب الشاشات ساعة قبل النوم<br>• لا تشرب قهوة بعد الظهر<br>• اجعل غرفتك باردة وهادئة ومظلمة<br>• تجنب الأكل الثقيل قبل النوم بساعتين<br>• مارس الرياضة صباحاً لا مساءً<br><br>⏰ البالغون يحتاجون <strong>7-8 ساعات</strong> نوم/ليلة<br><br>🩺 إذا استمر الأرق أكثر من شهر: راجع <em>طبيب نفساني</em>';
  }

  // ===== DIET / NUTRITION =====
  if(ql.indexOf('أكل') > -1 || ql.indexOf('تغذية') > -1 || ql.indexOf('وزن') > -1 || ql.indexOf('حمية') > -1){
    return '🥗 <strong>نصائح غذائية أساسية</strong><br>• 🥛 3 وجبات رئيسية + 2 وجبات خفيفة<br>• 💧 8 أكواب ماء/يوم على الأقل<br>• 🥦 نصف الطبق: خضار وفواكه<br>• 🐟 بروتين قليل الدهون: دجاج، سمك، بيض<br>• ❌ قلل: السكر، الملح، الدهون المشبعة<br>• 🚶 30 دقيقة نشاط يومي<br><br>🩺 للنظام الغذائي المتخصص: راجع <em>أخصائية تغذية (Nutritionniste)</em>';
  }

  // ===== GENERAL / FALLBACK =====
  var general = [
    '💡 سؤال جيد! هل يمكنك إخباري بالأعراض التي تشعر بها بالتحديد؟ سأرشدك للتخصص المناسب.',
    '🩺 لمساعدتك بشكل أفضل، أخبرني: أين تشعر بألم أو انزعاج؟ وكم مدة الأعراض؟',
    '💊 أنصحك بزيارة طبيب عام أولاً لتقييم حالتك. هو سيحيلك للتخصص المناسب إذا لزم الأمر.',
    '⚠️ تذكير: أنا أداة استعلام فقط. للتشخيص الدقيق والعلاج، يجب زيارة طبيب متخصص.',
    '🌟 هل تريد معرفة التخصص المناسب؟ أخبرني بأعراضك مثل: "عندي ألم في..." أو "أعاني من..."'
  ];
  return general[Math.floor(Math.random() * general.length)];
}

// ==================== INIT EXTRAS ====================
(function initExtras(){
  // Dark mode from storage
  if(localStorage.getItem('medismart_dark') === '1'){
    document.body.classList.add('DK');
    setTimeout(function(){
      var btn = document.getElementById('BDK');
      if(btn) btn.innerHTML = '<i class="fas fa-sun" style="color:#f59e0b;"></i>';
    }, 100);
  }
  // Wire dark mode button properly
  setTimeout(function(){
    var bdk = document.getElementById('BDK');
    if(bdk){
      bdk.replaceWith(bdk.cloneNode(true)); // remove old listener
      document.getElementById('BDK').addEventListener('click', toggleDark);
    }
    // Wire notification close on escape
    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') closeAllPanels();
    });
  }, 200);
  
})();
window.getNearbyDoctors = function () {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            console.log("user location:", userLat, userLng);
        }, function() {
            alert("فشل تحديد الموقع");
        });
    } else {
        alert("GPS غير مدعوم");
    }
};
document.querySelectorAll('.NV').forEach(btn=>{
  btn.onclick = ()=>{
    
    // نحيو A من كل الصفحات
    document.querySelectorAll('.VW').forEach(v=>v.classList.remove('A'));

    // نجيبو الصفحة المطلوبة
    let id = btn.getAttribute('data-v');
    document.getElementById(id).classList.add('A');

    // نحيو A من الأزرار
    document.querySelectorAll('.NV').forEach(b=>b.classList.remove('A'));
    btn.classList.add('A');
  }
  function forceViews(){
    document.querySelectorAll('.VW').forEach(function(v){
        if(v.classList.contains('A')){
            v.style.display = 'block';
        }else{
            v.style.display = 'none';
        }
    });
}

document.querySelectorAll('.NV').forEach(function(b){
    b.addEventListener('click', forceViews);
});

window.addEventListener('load', forceViews);
});
/* =========================
   MEDICAL RECORD WIZARD
========================= */

let currentRecordStep = 0;
const recordSteps = document.querySelectorAll(".record-step");

function showRecordStep(index) {
  recordSteps.forEach((step, i) => {
    step.style.display = i === index ? "block" : "none";
  });

  const bar = document.getElementById("wizardBar");
  if (bar) {
    const percent = ((index + 1) / recordSteps.length) * 100;
    bar.style.width = percent + "%";
  }
}

function nextRecordStep() {
  if (currentRecordStep < recordSteps.length - 1) {
    currentRecordStep++;
    showRecordStep(currentRecordStep);
  }
}

function prevRecordStep() {
  if (currentRecordStep > 0) {
    currentRecordStep--;
    showRecordStep(currentRecordStep);
  }
}

showRecordStep(0);


/* =========================
   SAVE TO DATABASE
========================= */

async function saveMedicalRecord() {
  const data = {
    first_name: document.getElementById("nom").value,
    last_name: document.getElementById("prenom").value,
    birth_date: document.getElementById("birth").value,
    gender: document.getElementById("gender").value,
    blood_type: document.getElementById("blood").value,
    weight: document.getElementById("weight").value,
    height: document.getElementById("height").value,
    phone: document.getElementById("phone").value,
    chronic_diseases: document.getElementById("chronic").value,
    allergies: document.getElementById("allergy").value,
    medications: document.getElementById("meds").value,
    health_notes: document.getElementById("notes").value,
    emergency_name: document.getElementById("urgentName").value,
    emergency_phone: document.getElementById("urgentPhone").value
  };

  try {
    const res = await fetch("save_medical_record.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(data)
    });

    const result = await res.json();

    if (result.success) {
      alert("تم حفظ السجل الطبي بنجاح");
      location.reload();
    } else {
      alert("خطأ: " + result.message);
    }

  } catch (err) {
    console.error(err);
    alert("فشل الاتصال بالسيرفر");
  }
}
/* ===== MEDICAL RECORD ACTIONS ===== */



function generateMedicalCard(){
  const card = document.getElementById("MCARD");

  if(card){
    card.classList.add("OP");
  }else{
    alert("MCARD غير موجود");
  }
}

function showMedicalQR() {
  alert("ميزة QR Code سنفعّلها الآن");
}
async function saveEditedMedicalRecord() {
  const data = {
    first_name: document.getElementById("edit_first_name").value,
    last_name: document.getElementById("edit_last_name").value,
    birth_date: document.getElementById("edit_birth_date").value,
    blood_type: document.getElementById("edit_blood_type").value,
    weight: document.getElementById("edit_weight").value,
    height: document.getElementById("edit_height").value,
    chronic_diseases: document.getElementById("edit_chronic").value,
    allergies: document.getElementById("edit_allergies").value,
    medications: document.getElementById("edit_medications").value,
    health_notes: document.getElementById("edit_notes").value,
    emergency_name: document.getElementById("edit_emergency_name").value,
    emergency_phone: document.getElementById("edit_emergency_phone").value
  };

  try {
    const res = await fetch("update_medical_record.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(data)
    });

    const result = await res.json();

    if (result.success) {
      alert("تم حفظ التعديل بنجاح");
      location.reload();
    } else {
      alert("خطأ: " + result.message);
    }

  } catch (e) {
    alert("فشل الاتصال بالسيرفر");
    console.error(e);
  }
}
async function saveEditedMedicalRecord() {
  const data = {
    first_name: document.getElementById("edit_first_name").value,
    last_name: document.getElementById("edit_last_name").value,
    birth_date: document.getElementById("edit_birth_date").value,
    blood_type: document.getElementById("edit_blood_type").value,
    weight: document.getElementById("edit_weight").value,
    height: document.getElementById("edit_height").value,
    chronic_diseases: document.getElementById("edit_chronic").value,
    allergies: document.getElementById("edit_allergies").value,
    medications: document.getElementById("edit_medications").value,
    health_notes: document.getElementById("edit_notes").value,
    emergency_name: document.getElementById("edit_emergency_name").value,
    emergency_phone: document.getElementById("edit_emergency_phone").value
  };

  const res = await fetch("update_medical_record.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  });

  const result = await res.json();

  if(result.success){
    clM("MEDIT");
    sa("✅ تم حفظ التعديل بنجاح");
    setTimeout(() => location.reload(), 1000);
  }else{
    sa("❌ فشل حفظ التعديل");
  }
}
function resetMedicalForm() {
  if (!confirm("هل تريد مسح السجل الطبي؟")) return;

  fetch("reset_medical_record.php", {
    method: "POST"
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      location.reload();
    } else {
      alert("فشل مسح السجل");
    }
  });
}
// ==================== SIDEBAR DROPDOWNS (الحالات الصحية / النتائج) ====================
function ptToggleGroup(groupId) {
  var grp = document.getElementById(groupId);
  if (!grp) return;
  var isOpen = grp.classList.contains('pt-grp-open');
  // Close all groups first
  document.querySelectorAll('.pt-snav-group').forEach(function(g) {
    g.classList.remove('pt-grp-open');
  });
  if (!isOpen) {
    grp.classList.add('pt-grp-open');
  }
}

// Navigate to a view and switch its internal tab (for VPR: preg/chronic)
function ptNavToTab(viewId, tabName, el) {
  // Navigate to the view
  ptNavTo(viewId, null);
  // Switch the internal tab
  if (viewId === 'VPR' && typeof pcSwitchTab === 'function') {
    pcSwitchTab(tabName);
  }
  // Mark sub-item active
  document.querySelectorAll('.pt-snav-sub-item').forEach(function(s) {
    s.classList.remove('pt-sub-active');
  });
  if (el) el.classList.add('pt-sub-active');
  // Open the parent group
  var grp = el ? el.closest('.pt-snav-group') : null;
  if (grp) {
    document.querySelectorAll('.pt-snav-group').forEach(function(g) { g.classList.remove('pt-grp-open'); });
    grp.classList.add('pt-grp-open');
  }
}

// Auto-open group when a sub-item's view is navigated to via ptNavTo
var _origPtNavTo = typeof ptNavTo === 'function' ? ptNavTo : null;

// Override ptNavTo to also handle sub-items and group highlighting
(function() {
  var _orig = window.ptNavTo;
  window.ptNavTo = function(viewId, clickedEl) {
    // Clear sub-item active states when navigating directly
    if (clickedEl && !clickedEl.classList.contains('pt-snav-sub-item')) {
      document.querySelectorAll('.pt-snav-sub-item').forEach(function(s) {
        s.classList.remove('pt-sub-active');
      });
      // Close all groups unless the navigated view belongs to one
      var belongsToGroup = document.querySelector('.pt-snav-sub-item[data-v="'+viewId+'"]');
      if (!belongsToGroup) {
        document.querySelectorAll('.pt-snav-group').forEach(function(g) { g.classList.remove('pt-grp-open'); });
      }
    }
    // Auto-open group if navigating to a grouped view
    var subItem = document.querySelector('.pt-snav-sub-item[data-v="'+viewId+'"]');
    if (subItem) {
      var grp = subItem.closest('.pt-snav-group');
      if (grp) grp.classList.add('pt-grp-open');
    }
    if (_orig) _orig(viewId, clickedEl);
  };
})();

// ==================== XRAY PREVIEW ====================
function previewXray(input) {
  var file = input.files[0];
  if (!file) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var img = document.getElementById('XRAY_PRV');
    img.src = e.target.result;
    img.style.display = 'block';
    document.getElementById('XRAY_ACTIONS').style.display = 'block';
  };
  reader.readAsDataURL(file);
}


/* ============================================================
   PREGNANCY CARD JS — بطاقة الحامل
   Front-End only — no Backend
   ============================================================ */

(function() {
  'use strict';

  /* ── Children ── */
  var pregChildCount = 0;

  window.pregToggleChildren = function() {
    var p = parseInt(document.getElementById('pf-parite').value) || 0;
    var wrap = document.getElementById('preg-children-wrap');
    wrap.style.display = p > 0 ? 'block' : 'none';
    // init with correct number of rows
    var list = document.getElementById('preg-children-list');
    var existing = list.querySelectorAll('.preg-child-row').length;
    for (var i = existing; i < p; i++) pregAddChild();
    while (list.querySelectorAll('.preg-child-row').length > p) {
      list.removeChild(list.lastChild);
    }
  };

  window.pregAddChild = function() {
    pregChildCount++;
    var id = pregChildCount;
    var list = document.getElementById('preg-children-list');
    var row = document.createElement('div');
    row.className = 'preg-child-row';
    row.innerHTML =
      '<div class="preg-field"><label>الاسم</label><input type="text" class="preg-input preg-child-first" placeholder="اسم الطفل"></div>' +
      '<div class="preg-field"><label>اللقب</label><input type="text" class="preg-input preg-child-last" placeholder="اللقب"></div>' +
      '<div class="preg-field"><label>تاريخ الميلاد</label><input type="date" class="preg-input preg-child-dob"></div>' +
      '<button class="preg-remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>';
    list.appendChild(row);
  };

  /* ── Medications ── */
  window.pregToggleMeds = function(show) {
    var wrap = document.getElementById('preg-meds-wrap');
    wrap.style.display = show ? 'block' : 'none';
    if (show && document.getElementById('preg-meds-list').children.length === 0) {
      pregAddMed();
    }
  };

  window.pregAddMed = function() {
    var list = document.getElementById('preg-meds-list');
    var row = document.createElement('div');
    row.className = 'preg-med-row';
    row.innerHTML =
      '<div class="preg-field"><label>اسم الدواء</label><input type="text" class="preg-input preg-med-name" placeholder="مثال: أموكسيسيلين"></div>' +
      '<div class="preg-field"><label>الجرعة</label><input type="text" class="preg-input preg-med-dose" placeholder="500 مغ / مرتين يومياً"></div>' +
      '<button class="preg-remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>';
    list.appendChild(row);
  };

  /* ── Save Card ── */
  window.pregSaveCard = function() {
    // Collect data
    var data = {
      grossesse:   document.getElementById('pf-grossesse').value || '—',
      parite:      document.getElementById('pf-parite').value || '—',
      avortement:  document.getElementById('pf-avortement').value || '—',
      birthType:   document.getElementById('pf-birth-type').value || '—',
      birthTiming: document.getElementById('pf-birth-timing').value || '—',
      allergy:     document.getElementById('pf-allergy').value || '—',
      chronic:     document.getElementById('pf-chronic').value || '—',
      blood:       document.getElementById('pf-blood').value || '—',
      vaccine:     (document.querySelector('input[name="pf-vaccine"]:checked') || {}).value || '—',
      meds:        (document.querySelector('input[name="pf-meds"]:checked') || {}).value || '—',
      smoke:       (document.querySelector('input[name="pf-smoke"]:checked') || {}).value || '—',
      alcohol:     (document.querySelector('input[name="pf-alcohol"]:checked') || {}).value || '—',
      activity:    document.getElementById('pf-activity').value || '—',
      children:    []
    };

    // Collect children
    document.querySelectorAll('.preg-child-row').forEach(function(row) {
      data.children.push({
        first: row.querySelector('.preg-child-first').value || '—',
        last:  row.querySelector('.preg-child-last').value || '—',
        dob:   row.querySelector('.preg-child-dob').value || '—'
      });
    });

    // Collect meds list
    data.medsList = [];
    document.querySelectorAll('.preg-med-row').forEach(function(row) {
      data.medsList.push({
        name: row.querySelector('.preg-med-name').value || '—',
        dose: row.querySelector('.preg-med-dose').value || '—'
      });
    });

    // Build summary grid
    var items = [
      { label: '🤰 عدد مرات الحمل', value: data.grossesse },
      { label: '🍼 عدد الولادات', value: data.parite },
      { label: '⚠️ عدد الإجهاضات', value: data.avortement },
      { label: '🩸 زمرة الدم', value: data.blood },
      { label: '🏥 نوع الولادة', value: data.birthType },
      { label: '⏱ توقيت الولادة', value: data.birthTiming },
      { label: '💉 اللقاحات', value: data.vaccine },
      { label: '🌿 التدخين', value: data.smoke },
      { label: '🏃 النشاط البدني', value: data.activity },
      { label: '🌾 الحساسية', value: data.allergy },
      { label: '💊 أمراض مزمنة', value: data.chronic },
      { label: '🔬 الأدوية الحالية', value: data.meds }
    ];

    if (data.meds === 'نعم' && data.medsList.length > 0) {
      var medStr = data.medsList.map(function(m) { return m.name + ' (' + m.dose + ')'; }).join('، ');
      items.push({ label: '💊 قائمة الأدوية', value: medStr });
    }

    if (data.children.length > 0) {
      var chStr = data.children.map(function(c) { return c.first + ' ' + c.last; }).join('، ');
      items.push({ label: '👶 الأطفال', value: chStr });
    }

    var grid = document.getElementById('preg-summary-grid');
    grid.innerHTML = items.map(function(it) {
      return '<div class="preg-summary-item">' +
        '<div class="si-label">' + it.label + '</div>' +
        '<div class="si-value">' + it.value + '</div>' +
        '</div>';
    }).join('');

    // Show view, hide form
    document.getElementById('preg-card-view').style.display = 'block';
    document.getElementById('preg-card-form').style.display = 'none';

    // Smooth scroll to top of section
    var vpr = document.getElementById('VPR');
    if (vpr) vpr.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  window.pregShowForm = function() {
    document.getElementById('preg-card-view').style.display = 'none';
    document.getElementById('preg-card-form').style.display = 'block';
  };

  /* ── Daily tracking ── */
  window.pregSaveDaily = function() {
    var w = document.getElementById('pd-weight').value;
    var bp = document.getElementById('pd-bp').value;
    var note = document.getElementById('pd-note').value;
    if (!w && !bp && !note) return;

    var today = new Date().toLocaleDateString('ar-DZ', { year: 'numeric', month: 'long', day: 'numeric' });
    var timeline = document.getElementById('preg-daily-timeline');
    var item = document.createElement('div');
    item.className = 'preg-tl-item';
    item.innerHTML =
      '<div class="preg-tl-dot"></div>' +
      '<div class="preg-tl-content">' +
      '<div class="preg-tl-date">' + today + '</div>' +
      '<div class="preg-tl-vals">' +
        (w ? '⚖️ ' + w + ' كغ &nbsp;|&nbsp;' : '') +
        (bp ? '🩸 ' + bp + ' &nbsp;|&nbsp;' : '') +
        (note ? '📝 ' + note : '') +
      '</div>' +
      '</div>';
    timeline.insertBefore(item, timeline.firstChild);

    document.getElementById('pd-weight').value = '';
    document.getElementById('pd-bp').value = '';
    document.getElementById('pd-note').value = '';
  };

  /* ── EDD Calculator ── */
  window.pregCalcEDD = function() {
    var lmp = document.getElementById('pd-lmp').value;
    if (!lmp) return;
    var lmpDate = new Date(lmp);
    var edd = new Date(lmpDate.getTime() + 280 * 24 * 60 * 60 * 1000); // Naegele's rule

    var today = new Date();
    var diffDays = Math.round((edd - today) / (24 * 60 * 60 * 1000));
    var weeksPregnant = Math.round((today - lmpDate) / (7 * 24 * 60 * 60 * 1000));

    var eddStr = edd.toLocaleDateString('ar-DZ', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    var weeksLeft = Math.round(diffDays / 7);

    document.getElementById('preg-edd-date').textContent = eddStr;
    document.getElementById('preg-edd-weeks').textContent =
      'الأسبوع ' + weeksPregnant + ' من الحمل — يتبقى حوالي ' + (weeksLeft > 0 ? weeksLeft + ' أسبوع' : 'موعد الولادة قريب!');
    document.getElementById('preg-edd-result').style.display = 'block';
  };

})();
