let form = document.getElementById("form");

form.onsubmit = function(e) {
    e.preventDefault();

    let fio = document.getElementById("fio");
    let email = document.getElementById("email");
    let phone = document.getElementById("phone");
    let comment = document.getElementById("comment");

    let ok = true;

    [fio, email, phone, comment].forEach(el => el.classList.remove("error"));

    const fioParts = fio.value.trim().split(/\s+/);
    const fioRegex = /^[а-яА-Яa-zA-ZёЁ]+$/;
    if (fioParts.length !== 3 || fioParts.some(part => !fioRegex.test(part))) {
        fio.classList.add("error");
        ok = false;
        alert("🍑 ФИО должно состоять из трех слов (Фамилия Имя Отчество)");
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailRegex.test(email.value.trim())) {
        email.classList.add("error");
        ok = false;
        alert("🍑 Введите корректный email");
    }

    const phoneRegex = /^\+7\d{10}$/;
    if (!phoneRegex.test(phone.value.trim())) {
        phone.classList.add("error");
        ok = false;
        alert("🍑 Телефон должен быть в формате +7XXXXXXXXXX (10 цифр после +7)");
    }

    if (comment.value.trim().length < 5 || comment.value.trim().length > 500) {
        comment.classList.add("error");
        ok = false;
        alert("🍑 Комментарий должен быть от 5 до 500 символов");
    }

    if (!ok) return;

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "handler.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    let data = "fio=" + encodeURIComponent(fio.value) +
               "&email=" + encodeURIComponent(email.value) +
               "&phone=" + encodeURIComponent(phone.value) +
               "&comment=" + encodeURIComponent(comment.value);

    xhr.onload = function() {
        let res = JSON.parse(xhr.responseText);

        if (res.status == "error") {
            document.getElementById("result").innerHTML = '<div style="color: #FF6B6B; background: #FFF0F0; padding: 15px; border-radius: 10px;">❌ ' + res.message + '</div>';
            return;
        }

        document.getElementById("form-block").style.display = "none";
        
        let mailStatus = res.mail_sent ? 
            '<span style="color: #FF8C42;">✅ Письмо успешно отправлено!</span>' : 
            '<span style="color: #FFB347;">⚠️ Данные сохранены, но письмо не отправлено: ' + (res.mail_error || "ошибка") + '</span>';
        
        document.getElementById("result").innerHTML = `
            <div class="success-card">
                <h3>🍑 Заявка успешно отправлена!</h3>
                <p><strong>👤 Имя:</strong> ${res.name}</p>
                <p><strong>📋 Фамилия:</strong> ${res.surname}</p>
                <p><strong>👨‍👩‍👧 Отчество:</strong> ${res.patronymic}</p>
                <p><strong>📧 Email:</strong> ${res.email}</p>
                <p><strong>📞 Телефон:</strong> ${res.phone}</p>
                <p><strong>⏰ С Вами свяжутся после:</strong> ${res.time}</p>
                <hr>
                <p>${mailStatus}</p>
                <p style="margin-top: 10px; font-size: 12px; color: #FFB347;">🍑 Спасибо за обращение!</p>
            </div>
        `;
    };
    
    xhr.onerror = function() {
        document.getElementById("result").innerHTML = '<div style="color: #FF6B6B; background: #FFF0F0; padding: 15px; border-radius: 10px;">❌ Ошибка сети. Попробуйте еще раз.</div>';
    };

    xhr.send(data);
};
