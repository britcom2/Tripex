(() => {
  const slider = document.querySelector('[data-home-slider]');
  if (!slider) return;

  const slides = Array.from(slider.querySelectorAll('[data-home-slide]'));
  const dots = Array.from(slider.querySelectorAll('[data-slide-to]'));
  const status = slider.querySelector('[data-slider-status]');
  if (slides.length < 2) return;

  let activeIndex = 0;
  let timer;
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const showSlide = (nextIndex) => {
    activeIndex = (nextIndex + slides.length) % slides.length;
    slides.forEach((slide, index) => {
      slide.hidden = index !== activeIndex;
      slide.setAttribute('aria-hidden', index === activeIndex ? 'false' : 'true');
    });
    dots.forEach((dot, index) => {
      if (index === activeIndex) {
        dot.setAttribute('aria-current', 'true');
      } else {
        dot.removeAttribute('aria-current');
      }
    });
    if (status) status.textContent = `Slide ${activeIndex + 1} of ${slides.length}`;
  };

  const stopAutoplay = () => window.clearInterval(timer);
  const startAutoplay = () => {
    stopAutoplay();
    if (!reducedMotion && !document.hidden) {
      timer = window.setInterval(() => showSlide(activeIndex + 1), 6500);
    }
  };

  slider.querySelector('[data-slide-previous]').addEventListener('click', () => {
    showSlide(activeIndex - 1);
    startAutoplay();
  });
  slider.querySelector('[data-slide-next]').addEventListener('click', () => {
    showSlide(activeIndex + 1);
    startAutoplay();
  });
  dots.forEach((dot) => {
    dot.addEventListener('click', () => {
      showSlide(Number(dot.dataset.slideTo));
      startAutoplay();
    });
  });
  slider.addEventListener('mouseenter', stopAutoplay);
  slider.addEventListener('mouseleave', startAutoplay);
  slider.addEventListener('focusin', stopAutoplay);
  slider.addEventListener('focusout', (event) => {
    if (!slider.contains(event.relatedTarget)) startAutoplay();
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) stopAutoplay();
    else startAutoplay();
  });

  showSlide(0);
  startAutoplay();
})();
