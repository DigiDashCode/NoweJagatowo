document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.querySelector('.carousel');
    if (!carousel) {
        return;
    }

    const slides = Array.from(carousel.querySelectorAll('.carousel-slide'));
    const dotsContainer = carousel.querySelector('.carousel-dots');
    let activeIndex = 0;
    let timer = null;

    function createDots() {
        slides.forEach((slide, index) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = index === 0 ? 'carousel-dot active' : 'carousel-dot';
            dot.setAttribute('aria-label', 'Slide ' + (index + 1));
            dot.addEventListener('click', function () {
                setActiveSlide(index);
                resetTimer();
            });
            dotsContainer.appendChild(dot);
        });
    }

    function setActiveSlide(index) {
        slides[activeIndex].classList.remove('active');
        dotsContainer.children[activeIndex].classList.remove('active');
        activeIndex = index;
        slides[activeIndex].classList.add('active');
        dotsContainer.children[activeIndex].classList.add('active');
    }

    function nextSlide() {
        const nextIndex = (activeIndex + 1) % slides.length;
        setActiveSlide(nextIndex);
    }

    function resetTimer() {
        if (timer) {
            clearInterval(timer);
        }
        timer = setInterval(nextSlide, 6000);
    }

    createDots();
    resetTimer();
});
