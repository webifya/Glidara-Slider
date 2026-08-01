document.querySelectorAll('.glidara-slider').forEach((root) => {
  const settings = JSON.parse(root.dataset.settings || '{}');
  const slides = [...root.querySelectorAll('.glidara-slider__slide')];
  const dots = [...root.querySelectorAll('.glidara-slider__dots button')];
  const currentLabel = root.querySelector('.glidara-slider__counter span');
  let current = 0, timer, startX = 0, items = 1;
  if (!slides.length) return;
  const updateItems = () => {
    const carousel = ['carousel', 'logo'].includes(settings.layout_type);
    items = carousel ? (innerWidth <= 600 ? 1 : innerWidth <= 1024 ? Math.min(2, Number(settings.slides_per_view) || 1) : Number(settings.slides_per_view) || 1) : 1;
    if (settings.direction === 'vertical') items = 1;
    root.style.setProperty('--glidara-items', items);
    root.style.setProperty('--glidara-step', `${100 / items}%`);
  };
  const lastIndex = () => Math.max(0, slides.length - items);
  const show = (next, restart = false) => {
    const end = lastIndex();
    const shouldLoop = settings.loop && !settings.stop_last;
    current = shouldLoop ? (next + end + 1) % (end + 1) : Math.max(0, Math.min(next, end));
    root.style.setProperty('--glidara-slider-index', current);
    root.style.setProperty('--glidara-progress', `${((current + items) / slides.length) * 100}%`);
    slides.forEach((slide, index) => slide.setAttribute('aria-hidden', index >= current && index < current + items ? 'false' : 'true'));
    dots.forEach((dot, index) => dot.setAttribute('aria-selected', index === current ? 'true' : 'false'));
    if (currentLabel) currentLabel.textContent = String(current + 1).padStart(2, '0');
    if (settings.auto_height) root.style.height = `${Math.max(...slides.slice(current, current + items).map((slide) => slide.scrollHeight))}px`;
    root.dispatchEvent(new CustomEvent('glidaraSliderChange', { detail: { sliderId: Number(root.dataset.sliderId || 0), slide: current } }));
    if (restart) { stop(); play(); }
  };
  const play = () => { if (settings.autoplay && slides.length > items && !(settings.stop_last && current === lastIndex())) timer = window.setInterval(() => show(current + 1), Number(settings.duration) || 5200); };
  const stop = () => window.clearInterval(timer);
  const navigate = (next) => show(next, true);
  root.querySelector('.glidara-slider__prev')?.addEventListener('click', () => navigate(current - 1));
  root.querySelector('.glidara-slider__next')?.addEventListener('click', () => navigate(current + 1));
  dots.forEach((dot, index) => dot.addEventListener('click', () => navigate(index)));
  if (settings.keyboard) root.addEventListener('keydown', (event) => { if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') navigate(current - 1); if (event.key === 'ArrowRight' || event.key === 'ArrowDown') navigate(current + 1); });
  if (settings.wheel) root.addEventListener('wheel', (event) => { event.preventDefault(); navigate(current + (event.deltaY > 0 ? 1 : -1)); }, { passive: false });
  root.addEventListener('pointerdown', (event) => { startX = event.clientX; });
  root.addEventListener('pointerup', (event) => { if (Math.abs(event.clientX - startX) > 40) navigate(current + (event.clientX < startX ? 1 : -1)); });
  if (settings.pause_hover) { root.addEventListener('mouseenter', stop); root.addEventListener('mouseleave', play); root.addEventListener('focusin', stop); root.addEventListener('focusout', play); }
  let resizeTimer;
  addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(() => { updateItems(); show(current); }, 120); });
  updateItems();
  show(settings.random_start ? Math.floor(Math.random() * (lastIndex() + 1)) : 0);
  play();
});
