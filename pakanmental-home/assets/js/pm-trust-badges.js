(function () {
  var slot = document.getElementById("pm-enamad");
  if (!slot) return;

  fetch("/wp-json/pm/v1/trust-badges", { credentials: "same-origin" })
    .then(function (res) {
      return res.ok ? res.json() : null;
    })
    .then(function (data) {
      if (!data || !data.html || !String(data.html).trim()) return;
      slot.innerHTML = data.html;
      slot.classList.add("is-loaded");
    })
    .catch(function () {
      /* keep placeholder */
    });
})();
