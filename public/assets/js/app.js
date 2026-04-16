const form = document.querySelector('#appointmentForm');
const formMessage = document.querySelector('#formMessage');
const slotFeedback = document.querySelector('#slotFeedback');
const dateInput = document.querySelector('#appointmentDate');
const timeSelect = document.querySelector('#appointmentTime');
const slides = Array.from(document.querySelectorAll('.slide'));
const dots = Array.from(document.querySelectorAll('#sliderDots button'));

async function loadSlots(dateValue) {
    if (!dateValue || !timeSelect) {
        return;
    }

    timeSelect.disabled = true;
    timeSelect.innerHTML = '<option value="">Yukleniyor...</option>';

    const payload = new FormData();
    payload.append('action', 'available_slots');
    payload.append('appointment_date', dateValue);

    try {
        const response = await fetch(window.appConfig.ajaxUrl, {
            method: 'POST',
            body: payload
        });
        const result = await response.json();

        if (!result.ok || !Array.isArray(result.slots) || result.slots.length === 0) {
            timeSelect.innerHTML = '<option value="">Uygun saat yok</option>';
            timeSelect.disabled = true;
            if (slotFeedback) {
                slotFeedback.textContent = result.message || 'Bu gun icin uygun saat bulunmuyor.';
                slotFeedback.className = 'helper-text error-text';
            }
            return;
        }

        timeSelect.innerHTML = '<option value="">Seciniz</option>';
        result.slots.forEach((slot) => {
            const option = document.createElement('option');
            option.value = slot;
            option.textContent = slot;
            timeSelect.appendChild(option);
        });
        timeSelect.disabled = false;
        if (slotFeedback) {
            slotFeedback.textContent = result.message;
            slotFeedback.className = 'helper-text success-text';
        }
    } catch (error) {
        timeSelect.innerHTML = '<option value="">Saatler getirilemedi</option>';
        timeSelect.disabled = true;
        if (slotFeedback) {
            slotFeedback.textContent = 'Saatler yuklenirken bir baglanti sorunu olustu.';
            slotFeedback.className = 'helper-text error-text';
        }
    }
}

if (dateInput) {
    dateInput.addEventListener('change', () => {
        loadSlots(dateInput.value);
    });
}

if (form) {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        formData.append('action', 'book_appointment');

        try {
            const response = await fetch(window.appConfig.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            formMessage.textContent = result.message;
            formMessage.className = result.ok ? 'helper-text success-text' : 'helper-text error-text';

            if (result.ok) {
                form.reset();
                if (timeSelect) {
                    timeSelect.innerHTML = '<option value="">Once tarih secin</option>';
                    timeSelect.disabled = true;
                }
                if (slotFeedback) {
                    slotFeedback.textContent = 'Gun secildiginde sadece uygun saatler listelenir.';
                    slotFeedback.className = 'helper-text';
                }
            }
        } catch (error) {
            formMessage.textContent = 'Bir baglanti hatasi olustu. Lutfen tekrar deneyin.';
            formMessage.className = 'helper-text error-text';
        }
    });
}

if (slides.length > 1) {
    let activeIndex = 0;

    const activateSlide = (index) => {
        slides.forEach((slide, idx) => {
            slide.classList.toggle('is-active', idx === index);
        });
        dots.forEach((dot, idx) => {
            dot.classList.toggle('is-active', idx === index);
        });
        activeIndex = index;
    };

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            activateSlide(Number(dot.dataset.slideIndex || 0));
        });
    });

    setInterval(() => {
        activateSlide((activeIndex + 1) % slides.length);
    }, 5000);
}
