jQuery(($) => {
  const builder = $('#my-slider-builder');
  const uid = () => window.crypto?.randomUUID?.() || `my-slider-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const list = builder.find('.my-slider-slides').sortable({ handle: '.my-slider-handle', update: preview });
  builder.find('.my-slider-layers').sortable({ handle: '.my-slider-layer-handle' });
  const templates = {
    hero: { title: 'Build something remarkable', content: 'A clear promise, concise explanation, and one strong call to action.', button_text: 'Get started', background: '#172554' },
    gallery: { title: 'Featured image', content: 'Add a short caption for this photograph.', background: '#111827' },
    business: { title: 'Grow your business', content: 'Present the outcome customers can expect.', button_text: 'Book a consultation', background: '#0f766e' },
    portfolio: { title: 'Selected work', content: 'Showcase the idea, craft, and measurable result.', button_text: 'View project', background: '#4c1d95' }
  };
  function preview() {
    const cards = list.find('.my-slider-slide').map(function () {
      const title = $(this).find('[name$="[title]"]').val() || 'Untitled slide';
      const image = $(this).find('[name$="[image]"]').val();
      const bg = $(this).find('[name$="[background]"]').val() || '#1d2327';
      return `<div class="my-slider-preview__slide" style="background-color:${bg};${image ? `background-image:url('${image.replace(/'/g, '%27')}')` : ''}"><strong>${$('<div>').text(title).html()}</strong></div>`;
    }).get();
    builder.find('.my-slider-preview').html(cards.join(''));
  }
  function addSlide(preset = {}) {
    const index = Number(builder.attr('data-next-index') || 0);
    const slide = $(wp.template('my-slider-slide')({ index }));
    slide.find('> input[name$="[uid]"]').val(uid());
    Object.entries(preset).forEach(([key, value]) => slide.find(`[name$="[${key}]"]`).val(value));
    list.append(slide);
    builder.attr('data-next-index', index + 1);
    preview();
  }
  builder.on('click', '.my-slider-add', () => addSlide())
    .on('click', '.my-slider-media', function () {
      const row = $(this).closest('.my-slider-media-row');
      const frame = wp.media({ title: 'Choose slide image', button: { text: 'Use image' }, multiple: false });
      frame.on('select', () => {
        const image = frame.state().get('selection').first().toJSON();
        row.find('.my-slider-image-url').val(image.url).trigger('change');
        row.find('.my-slider-image-id').val(image.id);
        row.closest('.my-slider-fields').find('[name$="[image_alt]"]').val(image.alt || '');
      });
      frame.open();
    })
    .on('click', '.my-slider-remove', function () { $(this).closest('.my-slider-slide').remove(); preview(); })
    .on('click', '.my-slider-add-layer', function () {
      const slide = $(this).closest('.my-slider-slide');
      const slideIndex = slide.index();
      const layers = slide.find('.my-slider-layers');
      const layerIndex = Number(layers.attr('data-next-layer') || 0);
      const layer = $(wp.template('my-slider-layer')({ slide: slideIndex, layer: layerIndex }));
      layer.find('input[name$="[uid]"]').val(uid());
      layers.append(layer).sortable({ handle: '.my-slider-layer-handle' });
      layers.attr('data-next-layer', layerIndex + 1);
    })
    .on('click', '.my-slider-remove-layer', function () { $(this).closest('.my-slider-layer').remove(); })
    .on('click', '.my-slider-device', function () {
      builder.find('.my-slider-device').removeClass('is-active');
      $(this).addClass('is-active');
      builder.find('.my-slider-preview-wrap').attr('data-device', $(this).data('device'));
    })
    .on('change', '.my-slider-template', function () {
      if (templates[this.value]) addSlide(templates[this.value]);
      this.value = '';
    })
    .on('input change', 'input, textarea, select', preview);
  builder.closest('form').on('submit', () => {
    list.find('.my-slider-slide').each(function (slideIndex) {
      $(this).find(':input[name^="my_slider_slides["]').each(function () {
        this.name = this.name.replace(/^my_slider_slides\[[^\]]+\]/, `my_slider_slides[${slideIndex}]`);
      });
      $(this).find('.my-slider-layer').each(function (layerIndex) {
        $(this).find(':input').each(function () {
          this.name = this.name.replace(/\[layers\]\[[^\]]+\]/, `[layers][${layerIndex}]`);
        });
      });
    });
  });
  preview();
});
