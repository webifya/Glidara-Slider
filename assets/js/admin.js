jQuery(($) => {
  document.body.classList.add('glidara-fullscreen-editor');
  const builder = $('#glidara-slider-builder');
  const uid = () => window.crypto?.randomUUID?.() || `glidara-slider-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const list = builder.find('.glidara-slider-slides').sortable({ handle: '.glidara-slider-handle', update: preview });
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
    const cards = list.find('.glidara-slider-slide').map(function () {
	  const row = $(this);
      const title = row.find('[name$="[title]"]').val() || 'Untitled slide';
      const content = row.find('[name$="[content]"]').val() || '';
      const button = row.find('[name$="[button_text]"]').val() || '';
      const image = row.find('[name$="[image]"]').val();
      const bg = row.find('[name$="[background]"]').val() || '#141525';
	  const opacity = Math.min(100, Number(row.find('[name$="[overlay_opacity]"]').val()) || 0) / 100;
	  const align = row.find('[name$="[align]"]').val() || 'left';
	  const safe = (value) => $('<div>').text(value).html();
      return `<article class="glidara-slider-preview__slide" style="background-color:${bg};${image ? `background-image:url('${image.replace(/'/g, '%27')}')` : ''};text-align:${align}"><span class="glidara-preview-overlay" style="opacity:${opacity}"></span><div class="glidara-preview-content"><strong>${safe(title)}</strong>${content ? `<p>${safe(content)}</p>` : ''}${button ? `<span class="glidara-preview-button">${safe(button)}</span>` : ''}</div></article>`;
    }).get();
    builder.find('.glidara-slider-preview').html(cards.join(''));
		health();
  }
  function addSlide(preset = {}) {
    const index = Number(builder.attr('data-next-index') || 0);
    const slide = $(wp.template('glidara-slider-slide')({ index }));
    slide.find('> input[name$="[uid]"]').val(uid());
    Object.entries(preset).forEach(([key, value]) => slide.find(`[name$="[${key}]"]`).val(value));
    list.append(slide);
    builder.attr('data-next-index', index + 1);
    preview();
  }
  builder.on('click', '.glidara-slider-add', () => addSlide())
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
