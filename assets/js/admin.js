jQuery(($) => {
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
    editorial: { title: 'A story worth discovering', content: 'Use strong editorial imagery and concise supporting copy.', button_text: 'Read the feature', background: '#27354a', overlay_opacity: 52 }
  };
  function preview() {
    const cards = list.find('.glidara-slider-slide').map(function () {
      const title = $(this).find('[name$="[title]"]').val() || 'Untitled slide';
      const image = $(this).find('[name$="[image]"]').val();
      const bg = $(this).find('[name$="[background]"]').val() || '#141525';
      return `<div class="glidara-slider-preview__slide" style="background-color:${bg};${image ? `background-image:url('${image.replace(/'/g, '%27')}')` : ''}"><strong>${$('<div>').text(title).html()}</strong></div>`;
    }).get();
    builder.find('.glidara-slider-preview').html(cards.join(''));
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
