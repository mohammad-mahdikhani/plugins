(function () {
  var cfg = window.NobatMan || {};
  var form = document.getElementById("nm-booking");
  if (!form) return;

  var year = cfg.year;
  var month = cfg.month;
  var grid = cfg.grid || [];
  var catsEl = document.getElementById("nm-cats");
  var qEl = document.getElementById("nm-cat-questions");
  var calEl = document.getElementById("nm-cal");
  var slotsEl = document.getElementById("nm-slots");
  var specEl = document.getElementById("nm-specialist");
  var monthLabel = document.getElementById("nm-month-label");
  var priceLabel = document.getElementById("nm-price-label");
  var selectedCat = null;

  priceLabel.textContent = Number(cfg.price || 0).toLocaleString("fa-IR") + " تومان";

  (cfg.specialists || []).forEach(function (sp) {
    var o = document.createElement("option");
    o.value = sp.id;
    o.textContent = sp.name;
    specEl.appendChild(o);
  });

  function renderCats() {
    catsEl.innerHTML = "";
    (cfg.categories || []).forEach(function (c) {
      var b = document.createElement("button");
      b.type = "button";
      b.className = "nm-cat";
      b.innerHTML = "<strong>" + escapeHtml(c.title) + "</strong><br><span>" + escapeHtml(c.description || "") + "</span>";
      b.addEventListener("click", function () {
        selectedCat = c;
        document.getElementById("nm-category").value = c.id;
        Array.prototype.forEach.call(catsEl.querySelectorAll(".nm-cat"), function (el) {
          el.classList.remove("is-on");
        });
        b.classList.add("is-on");
        renderQuestions(c);
      });
      catsEl.appendChild(b);
    });
  }

  function renderQuestions(cat) {
    qEl.innerHTML = "";
    (cat.questions || []).forEach(function (q) {
      var label = document.createElement("label");
      label.textContent = q.label;
      var field =
        q.type === "textarea"
          ? document.createElement("textarea")
          : document.createElement("input");
      field.name = "answers[" + q.id + "]";
      qEl.appendChild(label);
      qEl.appendChild(field);
    });
  }

  function renderCal() {
    monthLabel.textContent = (cfg.monthName || "") + " " + year;
    calEl.innerHTML = "";
    ["ش", "ی", "د", "س", "چ", "پ", "ج"].forEach(function (d) {
      var h = document.createElement("div");
      h.className = "nm-dow";
      h.textContent = d;
      calEl.appendChild(h);
    });
    grid.forEach(function (week) {
      week.forEach(function (day, idx) {
        var b = document.createElement("button");
        b.type = "button";
        if (!day) {
          b.disabled = true;
          b.textContent = "";
        } else {
          b.textContent = day;
          var jalali = pad(year) + "/" + pad(month) + "/" + pad(day);
          var workOk = (cfg.workingDays || []).indexOf(idx) !== -1;
          var holiday = (cfg.holidays || []).indexOf(jalali) !== -1;
          if (!workOk || holiday) {
            b.disabled = true;
          }
          b.addEventListener("click", function () {
            document.getElementById("nm-date").value = jalali;
            Array.prototype.forEach.call(calEl.querySelectorAll("button"), function (el) {
              el.classList.remove("is-on");
            });
            b.classList.add("is-on");
            renderSlots();
          });
        }
        calEl.appendChild(b);
      });
    });
  }

  function renderSlots() {
    slotsEl.innerHTML = "";
    var start = parseTime(cfg.slotStart || "09:00");
    var end = parseTime(cfg.slotEnd || "18:00");
    var dur = parseInt(cfg.duration || 45, 10);
    for (var t = start; t + dur <= end; t += dur) {
      var label = minutesToTime(t);
      var b = document.createElement("button");
      b.type = "button";
      b.textContent = label;
      b.addEventListener("click", function (hh) {
        return function () {
          document.getElementById("nm-start").value = hh;
          Array.prototype.forEach.call(slotsEl.querySelectorAll("button"), function (el) {
            el.classList.remove("is-on");
          });
          b.classList.add("is-on");
        };
      }(label));
      slotsEl.appendChild(b);
    }
  }

  function loadMonth(y, m) {
    var body = new FormData();
    body.append("action", "nobat_man_month");
    body.append("nonce", cfg.nonce);
    body.append("year", y);
    body.append("month", m);
    fetch(cfg.ajax, { method: "POST", body: body, credentials: "same-origin" })
      .then(function (r) {
        return r.json();
      })
      .then(function (res) {
        if (!res || !res.success) return;
        year = res.data.year;
        month = res.data.month;
        grid = res.data.grid;
        cfg.monthName = res.data.monthName;
        renderCal();
      });
  }

  document.getElementById("nm-prev").addEventListener("click", function () {
    loadMonth(year, month - 1);
  });
  document.getElementById("nm-next").addEventListener("click", function () {
    loadMonth(year, month + 1);
  });

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var fd = new FormData(form);
    fd.append("action", "nobat_man_book");
    fd.append("nonce", cfg.nonce);
    fetch(cfg.ajax, { method: "POST", body: fd, credentials: "same-origin" })
      .then(function (r) {
        return r.json();
      })
      .then(function (res) {
        var box = document.getElementById("nm-result");
        box.hidden = false;
        if (!res.success) {
          box.textContent = (res.data && res.data.message) || "خطا";
          return;
        }
        box.innerHTML = res.data.message || "ثبت شد";
        if (res.data.pay_url) {
          box.innerHTML += '<p><a href="' + res.data.pay_url + '">پرداخت</a></p>';
        }
      });
  });

  function parseTime(t) {
    var p = String(t).split(":");
    return parseInt(p[0], 10) * 60 + parseInt(p[1] || "0", 10);
  }
  function minutesToTime(m) {
    var h = Math.floor(m / 60);
    var mm = m % 60;
    return pad(h) + ":" + pad(mm);
  }
  function pad(n) {
    n = String(n);
    return n.length < 2 ? "0" + n : n;
  }
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  renderCats();
  renderCal();
})();
