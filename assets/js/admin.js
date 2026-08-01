jQuery(($) => {
  document.body.classList.add('glidara-fullscreen-editor');
  const builder = $('#glidara-slider-builder');
	if (document.querySelector('.glidara-template-modal[data-auto-open="1"]')) document.body.classList.add('glidara-modal-open');
  const uid = () => window.crypto?.randomUUID?.() || `glidara-slider-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const list = builder.find('.glidara-slider-slides').sortable({ handle: '.glidara-slider-handle', update: preview });
	const openSlide = (slide) => { list.find('.glidara-slider-slide').removeClass('is-open'); $(slide).addClass('is-open'); };
	if (list.find('.glidara-slider-slide').length) openSlide(list.find('.glidara-slider-slide').first());
  builder.find('.glidara-slider-layers').sortable({ handle: '.glidara-slider-layer-handle' });
  const templates = {
    hero: { title: 'Ideas that move people', content: 'Create a bold first impression with focused copy and elegant motion.', button_text: 'Explore Glidara', background: '#17152e', overlay_opacity: 55 },
    gallery: { title: 'Stories, beautifully framed', content: 'Pair immersive imagery with a short, memorable caption.', background: '#11131d', overlay_opacity: 42 },
    business: { title: 'Make your next move matter', content: 'Present the result your customers want with clarity and confidence.', button_text: 'Start a conversation', background: '#12343b', overlay_opacity: 50 },
    portfolio: { title: 'Selected work', content: 'Show the thinking, craft, and measurable result behind the project.', button_text: 'View the story', background: '#271b4f', overlay_opacity: 48 },
    logo: { title: 'Trusted by ambitious teams', content: 'Choose a transparent logo image and use the Logo Carousel layout.', background: '#171820', overlay_opacity: 12 },
    testimonial: { title: '“The result exceeded every expectation.”', content: 'Customer Name — Company', background: '#34235f', overlay_opacity: 30 },
    promo: { title: 'A better offer starts here', content: 'Give the campaign a clear deadline, benefit, and next action.', button_text: 'Claim the offer', background: '#7f1d3f', overlay_opacity: 46 },
    editorial: { title: 'A story worth discovering', content: 'Use strong editorial imagery and concise supporting copy.', button_text: 'Read the feature', background: '#27354a', overlay_opacity: 52 },
    saas: { title: 'One workspace. Remarkable momentum.', content: 'Help your team plan, create, and ship without the busywork.', button_text: 'Start free', background: '#35258b', overlay_opacity: 48 },
    restaurant: { title: 'An evening worth remembering', content: 'Seasonal ingredients, thoughtful hospitality, and a table waiting for you.', button_text: 'Reserve a table', background: '#512b24', overlay_opacity: 56 },
    realestate: { title: 'Find the place that feels like home', content: 'Explore distinctive homes in the neighborhoods you love.', button_text: 'View properties', background: '#183b45', overlay_opacity: 50 },
    event: { title: 'The ideas shaping what comes next', content: 'Join builders, leaders, and creators for one unforgettable day.', button_text: 'Get tickets', background: '#561943', overlay_opacity: 54 }
  };
	function health() {
	  const slides = list.find('.glidara-slider-slide');
	  let score = slides.length ? 100 : 0;
	  slides.each(function () {
		const row = $(this);
		if (row.find('[name$="[image]"]').val() && !row.find('[name$="[image_alt]"]').val()) score -= 8;
		const button = row.find('[name$="[button_text]"]').val();
		const url = row.find('[name$="[button_url]"]').val();
		if ((button && !url) || (!button && url)) score -= 6;
		if (!row.find('[name$="[title]"]').val()) score -= 4;
	  });
	  if (slides.length > 12) score -= 5;
	  score = Math.max(0, score);
	  const badge = builder.find('.glidara-editor-health');
	  badge.find('strong').text(score);
	  badge.toggleClass('has-warning', score < 80).attr('title', score < 80 ? 'Add titles and alt text, and complete CTA links.' : 'Content, accessibility, and conversion basics look healthy.');
	}
  function preview() {
	health();
  }
  function addSlide(preset = {}) {
    const index = Number(builder.attr('data-next-index') || 0);
    const slide = $(wp.template('glidara-slider-slide')({ index }));
    slide.find('> input[name$="[uid]"]').val(uid());
    Object.entries(preset).forEach(([key, value]) => slide.find(`[name$="[${key}]"]`).val(value));
    list.append(slide);
	openSlide(slide);
    builder.attr('data-next-index', index + 1);
    preview();
  }
  builder.on('click', '.glidara-slider-add', () => addSlide())
	.on('click', '.glidara-slider-handle', function (event) { if ($(event.target).closest('button,a').length) return; const slide = $(this).closest('.glidara-slider-slide'); if (slide.hasClass('is-open')) slide.removeClass('is-open'); else openSlide(slide); })
	.on('focusin click', '.glidara-slider-fields', function () { openSlide($(this).closest('.glidara-slider-slide')); })
	.on('input', '.glidara-editor-title', function () { $('#title').val(this.value); })
	.on('click', '.glidara-save-slider', function () { $('#title').val(builder.find('.glidara-editor-title').val()); $('#publish').trigger('click'); })
	.on('click', '.glidara-editor-tab', function () {
	  const tab = $(this).data('tab');
	  builder.find('.glidara-editor-tab').removeClass('is-active').attr('aria-selected', 'false');
	  $(this).addClass('is-active').attr('aria-selected', 'true');
	  builder.find('.glidara-editor-panel').removeClass('is-active').filter(`[data-panel="${tab}"]`).addClass('is-active');
	})
	.on('click', '.glidara-copy-code', function () {
	  navigator.clipboard?.writeText($(this).data('copy'));
	  const button = $(this); button.text('Copied'); setTimeout(() => button.text('Copy shortcode'), 1400);
	})
    .on('click', '.glidara-slider-media', function () {
      const row = $(this).closest('.glidara-slider-media-row');
      const frame = wp.media({ title: 'Choose slide image', button: { text: 'Use image' }, multiple: false });
      frame.on('select', () => {
        const image = frame.state().get('selection').first().toJSON();
        row.find('.glidara-slider-image-url').val(image.url).trigger('change');
        row.find('.glidara-slider-image-id').val(image.id);
		const fields = row.closest('.glidara-slider-fields');
		fields.find('[name$="[image_alt]"]').val(image.alt || '');
		if (!fields.find('[name$="[title]"]').val()) fields.find('[name$="[title]"]').val(image.title || '');
		if (!fields.find('[name$="[content]"]').val()) fields.find('[name$="[content]"]').val(image.description || '');
		if (!fields.find('[name$="[caption]"]').val()) fields.find('[name$="[caption]"]').val(image.caption || '');
      });
      frame.open();
    })
    .on('click', '.glidara-slider-remove', function () { $(this).closest('.glidara-slider-slide').remove(); preview(); })
    .on('click', '.glidara-slider-duplicate', function () {
      const original = $(this).closest('.glidara-slider-slide');
      const copy = original.clone(false, false);
      copy.find('> input[name$="[uid]"]').val(uid());
      copy.find('.glidara-slider-layer input[name$="[uid]"]').each(function () { $(this).val(uid()); });
      original.after(copy);
	  openSlide(copy);
      copy.find('.glidara-slider-layers').sortable({ handle: '.glidara-slider-layer-handle' });
      preview();
    })
    .on('click', '.glidara-slider-add-layer', function () {
      const slide = $(this).closest('.glidara-slider-slide');
      const slideIndex = slide.index();
      const layers = slide.find('.glidara-slider-layers');
      const layerIndex = Number(layers.attr('data-next-layer') || 0);
      const layer = $(wp.template('glidara-slider-layer')({ slide: slideIndex, layer: layerIndex }));
      layer.find('input[name$="[uid]"]').val(uid());
      layers.append(layer).sortable({ handle: '.glidara-slider-layer-handle' });
      layers.attr('data-next-layer', layerIndex + 1);
    })
    .on('click', '.glidara-slider-remove-layer', function () { $(this).closest('.glidara-slider-layer').remove(); })
    .on('click', '.glidara-slider-device', function () {
      builder.find('.glidara-slider-device').removeClass('is-active');
      $(this).addClass('is-active');
      builder.find('.glidara-slider-preview-wrap').attr('data-device', $(this).data('device'));
    })
    .on('change', '.glidara-slider-template', function () {
      if (templates[this.value]) addSlide(templates[this.value]);
      this.value = '';
    })
    .on('input change', 'input, textarea, select', preview);
	document.addEventListener('click', (event) => {
	  const previewLink = event.target.closest('.glidara-exact-preview');
	  if (previewLink) {
		event.preventDefault(); event.stopImmediatePropagation();
		const modal = document.querySelector('.glidara-preview-modal');
		const frame = modal?.querySelector('iframe');
		if (modal && frame) { frame.src = previewLink.href; modal.hidden = false; document.body.classList.add('glidara-modal-open'); }
		return;
	  }
	  const template = event.target.closest('.glidara-template-card');
	  if (template) { addSlide(templates[template.dataset.template] || {}); template.closest('.glidara-modal').hidden = true; document.body.classList.remove('glidara-modal-open'); return; }
	  const close = event.target.closest('[data-close-modal]');
	  if (close) { const modal = close.closest('.glidara-modal'); if (modal) modal.hidden = true; document.body.classList.remove('glidara-modal-open'); }
	  const device = event.target.closest('[data-preview-width]');
	  if (device) { const modal = device.closest('.glidara-preview-modal'); modal.querySelectorAll('[data-preview-width]').forEach((button) => button.classList.remove('is-active')); device.classList.add('is-active'); modal.querySelector('iframe').style.width = device.dataset.previewWidth; }
	}, true);
	document.addEventListener('keydown', (event) => { if (event.key === 'Escape') { document.querySelectorAll('.glidara-modal:not([hidden])').forEach((modal) => { modal.hidden = true; }); document.body.classList.remove('glidara-modal-open'); } });
  builder.closest('form').on('submit', () => {
    list.find('.glidara-slider-slide').each(function (slideIndex) {
      $(this).find(':input[name^="glidara_slider_slides["]').each(function () {
        this.name = this.name.replace(/^glidara_slider_slides\[[^\]]+\]/, `glidara_slider_slides[${slideIndex}]`);
      });
      $(this).find('.glidara-slider-layer').each(function (layerIndex) {
        $(this).find(':input').each(function () {
          this.name = this.name.replace(/\[layers\]\[[^\]]+\]/, `[layers][${layerIndex}]`);
        });
      });
    });
  });
  preview();
});
