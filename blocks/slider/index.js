(() => {
  const { registerBlockType } = wp.blocks;
  const { createElement: el } = wp.element;
  const { SelectControl, Placeholder } = wp.components;
  registerBlockType('glidara/slider', {
    edit: ({ attributes, setAttributes }) => el(Placeholder, { icon: 'images-alt2', label: 'Glidara Slider', instructions: 'Choose a published slider.' }, el(SelectControl, { value: attributes.id, options: window.glidaraSliderBlock?.choices || [], onChange: (id) => setAttributes({ id: Number(id) }) })),
    save: () => null
  });
})();
