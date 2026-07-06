document.addEventListener('DOMContentLoaded', () => {

  /* ===== دخول الكارد ===== */
  const card = document.querySelector('.card');
  const logo = document.querySelector('.logo-area');

  if (card) {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    setTimeout(() => {
      card.style.transition = 'all 0.8s ease';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, 100);
  }

  if (logo) {
    logo.style.opacity = '0';
    logo.style.transform = 'translateY(20px)';
    setTimeout(() => {
      logo.style.transition = 'all 0.8s ease';
      logo.style.opacity = '1';
      logo.style.transform = 'translateY(0)';
    }, 100);
  }

  /* ===== نبض input ===== */
  document.querySelectorAll('input').forEach(input => {
    input.addEventListener('focus', () => {
      if (input.parentElement)
        input.parentElement.style.transform = 'scale(1.02)';
    });

    input.addEventListener('blur', () => {
      if (input.parentElement)
        input.parentElement.style.transform = 'scale(1)';
    });
  });

  /* ===== إظهار / إخفاء الباسورد ===== */
  document.querySelectorAll('.toggle-pass').forEach(toggle => {
    toggle.classList.add('fa-eye-slash');

    toggle.addEventListener('click', function () {
      const input = this.parentElement.querySelector('input');
      if (!input) return;

      if (input.type === 'password') {
        input.type = 'text';
        this.classList.replace('fa-eye-slash', 'fa-eye');
        this.classList.add('active');
      } else {
        input.type = 'password';
        this.classList.replace('fa-eye', 'fa-eye-slash');
        this.classList.remove('active');
      }
    });
  });

});

/* ===== اختيار نوع المستخدم ===== */


/* ===== بصمة ===== */
function startScan() {
  const line = document.getElementById('scannerLine');
  const btn = document.querySelector('.primary-btn');
  const icon = document.querySelector('.fingerprint-icon');

  if (!line || !btn) return;

  line.classList.add('run-animation');
  btn.innerText = "جاري المسح...";
  btn.disabled = true;

  setTimeout(() => {
    if (icon) icon.style.opacity = "0.7";
    btn.innerText = "تم التحقق ✅";

    setTimeout(() => {
      window.location.href = 'step3.html';
    }, 1200);
  }, 2200);
}