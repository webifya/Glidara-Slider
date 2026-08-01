document.querySelectorAll('.glidara-slider').forEach((root) => {
  const settings = JSON.parse(root.dataset.settings || '{}');
  const slides = [...root.querySelectorAll('.glidara-slider__slide')];
  const dots = [...root.querySelectorAll('.glidara-slider__dots button')];
  const currentLabel = root.querySelector('.glidara-slider__counter span');
  let current = 0;
  let timer;
  let startX = 0;
  if (!slides.length) return;

  const show = (next, restart = false) => {
    current = settings.loop ? (next + slides.length) % slides.length : Math.max(0, Math.min(next, slides.length - 1));
    root.style.setProperty('--glidara-slider-index', current);
    root.style.setProperty('--glidara-progress', `${((current + 1) / slides.length) * 100}%`);
    slides.forEach((slide, index) => slide.setAttribute('aria-hidden', index === current ? 'false' : 'true'));
    dots.forEach((dot, index) => dot.setAttribute('aria-selected', index === current ? 'true' : 'false'));
    if (currentLabel) currentLabel.textContent = String(current + 1).padStart(2, '0');
    if (settings.auto_height) root.style.height = `${slides[current].scrollHeight}px`;
    root.dispatchEvent(new CustomEvent('glidaraSliderChange', { detail: { sliderId: Number(root.dataset.sliderId || 0), slide: current } }));
    if (restart) { stop(); play(); }
  };
  const play = () => { if (settings.autoplay && slides.length > 1) timer = window.setInterval(() => show(current + 1), Number(settings.duration) || 5000); };
  const stop = () => window.clearInterval(timer);
  const navigate = (next) => show(next, true);

  root.querySelector('.glidara-slider__prev')?.addEventListener('click', () => navigate(current - 1));
  root.querySelector('.glidara-slider__next')?.addEventListener('click', () => navigate(current + 1));
  dots.forEach((dot, index) => dot.addEventListener('click', () => navigate(index)));
  if (settings.keyboard) root.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') navigate(current - 1);
    if (event.key === 'ArrowRight') navigate(current + 1);
  });
  if (settings.wheel) root.addEventListener('wheel', (event) => {
    event.preventDefault();
    navigate(current + (event.deltaY > 0 ? 1 : -1));
  }, { passive: false });
  root.addEventListener('pointerdown', (event) => { startX = event.clientX; });
  root.addEventListener('pointerup', (event) => {
    if (Math.abs(event.clientX - startX) > 40) navigate(current + (event.clientX < startX ? 1 : -1));
  });
  if (settings.pause_hover) {
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', play);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', play);
  }
  show(0);
  play();
});
