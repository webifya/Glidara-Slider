(() => {
  const { registerBlockType } = wp.blocks;
  const { createElement: el } = wp.element;
  const { SelectControl, Placeholder, Button, Notice } = wp.components;
  registerBlockType('glidara/slider', {
    edit: ({ attributes, setAttributes }) => {
      const data = window.glidaraSliderBlock || {};
      const choices = data.choices || [];
      const selected = choices.find((choice) => Number(choice.value) === Number(attributes.id));
      return el(Placeholder, {
        icon: 'images-alt2',
        label: 'Glidara Slider',
        instructions: selected ? `Displaying “${selected.label}”. The live slider is rendered on the site.` : 'Choose a published slider. Width and alignment can be changed from the block toolbar.'
      },
      choices.length <= 1 && el(Notice, { status: 'info', isDismissible: false }, 'No published sliders are available yet.'),
      el(SelectControl, { label: 'Slider', value: attributes.id, options: choices, onChange: (id) => setAttributes({ id: Number(id) }) }),
      el('div', { className: 'glidara-block-actions' },
        data.createUrl && el(Button, { variant: 'primary', href: data.createUrl, target: '_blank' }, 'Create slider'),
        selected && data.editBase && el(Button, { variant: 'secondary', href: `${data.editBase}${attributes.id}`, target: '_blank' }, 'Edit slider')
      ));
    },
    save: () => null
  });
})();
