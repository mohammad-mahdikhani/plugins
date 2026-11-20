(function (blocks, element, blockEditor, components, i18n) {
  var el = element.createElement;
  var __ = i18n.__;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var TextControl = components.TextControl;
  var TextareaControl = components.TextareaControl;
  var Button = components.Button;

  blocks.registerBlockType("schema-nova/faq", {
    title: __("Schema Nova FAQ", "schema-nova"),
    icon: "editor-help",
    category: "widgets",
    attributes: {
      questions: { type: "array", default: [] },
    },
    edit: function (props) {
      var questions = props.attributes.questions || [];

      function update(i, key, value) {
        var next = questions.slice();
        next[i] = Object.assign({}, next[i] || {}, { [key]: value });
        props.setAttributes({ questions: next });
      }

      function add() {
        props.setAttributes({
          questions: questions.concat([{ question: "", answer: "" }]),
        });
      }

      return el(
        "div",
        { className: "schema-nova-faq-editor" },
        el(InspectorControls, {},
          el(PanelBody, { title: __("FAQ", "schema-nova") },
            el(Button, { isSecondary: true, onClick: add }, __("Add question", "schema-nova"))
          )
        ),
        questions.length === 0
          ? el("p", {}, __("Add FAQ items in the sidebar or with the button below.", "schema-nova"))
          : null,
        questions.map(function (row, i) {
          return el(
            "div",
            { key: i, style: { marginBottom: "12px", padding: "12px", background: "#f6f1e7" } },
            el(TextControl, {
              label: __("Question", "schema-nova"),
              value: row.question || "",
              onChange: function (v) { update(i, "question", v); },
            }),
            el(TextareaControl, {
              label: __("Answer", "schema-nova"),
              value: row.answer || "",
              onChange: function (v) { update(i, "answer", v); },
            })
          );
        }),
        el(Button, { isPrimary: true, onClick: add }, __("Add FAQ", "schema-nova"))
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
