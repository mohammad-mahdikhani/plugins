(function ($) {
  "use strict";

  function post(action, extra) {
    return $.post(schemaNova.ajax, $.extend({ action: action, nonce: schemaNova.nonce }, extra || {}));
  }

  function flash($el, payload) {
    $el.removeAttr("hidden");
    if (typeof payload === "string") {
      $el.text(payload);
      return;
    }
    $el.text(JSON.stringify(payload, null, 2));
  }

  $(function () {
    $("#sn-test-connector").on("click", function () {
      var $r = $("#sn-connect-result");
      $r.text(schemaNova.i18n.running).removeAttr("hidden");
      post("schema_nova_test").done(function (res) {
        flash($r, res);
      }).fail(function () {
        flash($r, schemaNova.i18n.fail);
      });
    });

    $("#sn-sync-connector").on("click", function () {
      var $r = $("#sn-connect-result");
      $r.text(schemaNova.i18n.running).removeAttr("hidden");
      post("schema_nova_sync").done(function (res) {
        flash($r, res);
      }).fail(function () {
        flash($r, schemaNova.i18n.fail);
      });
    });

    $("#sn-run-optimizer").on("click", function () {
      var $btn = $(this);
      $btn.prop("disabled", true).text(schemaNova.i18n.running);
      post("schema_nova_optimize").done(function () {
        window.location.reload();
      }).fail(function () {
        $btn.prop("disabled", false).text(schemaNova.i18n.fail);
      });
    });

    $("#sn-title-input").on("input", function () {
      var v = $(this).val();
      if (v) {
        $("#sn-live-title").text(v);
      }
    });
    $("#sn-desc-input").on("input", function () {
      var v = $(this).val();
      if (v) {
        $("#sn-live-desc").text(v);
      }
    });

    $("#sn-add-faq").on("click", function () {
      var i = $("#sn-faq-rows .sn-faq-row").length;
      $("#sn-faq-rows").append(
        '<div class="sn-faq-row">' +
          '<input type="text" name="schema_nova[faq][' + i + '][question]" />' +
          '<textarea name="schema_nova[faq][' + i + '][answer]"></textarea>' +
          "</div>"
      );
    });
  });
})(jQuery);
