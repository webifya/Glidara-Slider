document.querySelectorAll('.my-slider').forEach((root) => {
  const settings = JSON.parse(root.dataset.settings || '{}');
  const slides = [...root.querySelectorAll('.my-slider__slide')];
  const dots = [...root.querySelectorAll('.my-slider__dots button')];
  let current = 0, timer, startX = 0;
  const show = (next) => {
    current = settings.loop ? (next + slides.length) % slides.length : Math.max(0, Math.min(next, slides.length - 1));
    root.style.setProperty('--my-slider-index', current);
    slides.forEach((slide, i) => slide.setAttribute('aria-hidden', i === current ? 'false' : 'true'));
    dots.forEach((dot, i) => dot.setAttribute('aria-selected', i === current ? 'true' : 'false'));
    if (settings.auto_height) root.style.height = `${slides[current].scrollHeight}px`;
    root.dispatchEvent(new CustomEvent('mySliderChange', { detail: { sliderId: Number(root.dataset.sliderId || 0), slide: current } }));
  };
  const play = () => { if (settings.autoplay) timer = window.setInterval(() => show(current + 1), Number(settings.duration) || 5000); };
  const stop = () => window.clearInterval(timer);
  root.querySelector('.my-slider__prev')?.addEventListener('click', () => show(current - 1));
  root.querySelector('.my-slider__next')?.addEventListener('click', () => show(current + 1));
  dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
  if (settings.keyboard) root.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') show(current - 1); if (e.key === 'ArrowRight') show(current + 1); });
  if (settings.wheel) root.addEventListener('wheel', (e) => { e.preventDefault(); show(current + (e.deltaY > 0 ? 1 : -1)); }, { passive: false });
  root.addEventListener('pointerdown', (e) => { startX = e.clientX; });
  root.addEventListener('pointerup', (e) => { if (Math.abs(e.clientX - startX) > 40) show(current + (e.clientX < startX ? 1 : -1)); });
  if (settings.pause_hover) { root.addEventListener('mouseenter', stop); root.addEventListener('mouseleave', play); }
  show(0); play();
});
