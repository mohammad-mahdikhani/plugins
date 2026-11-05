(function () {
  var btn = document.getElementById("nm-add-sp");
  var box = document.getElementById("nm-specialists");
  if (!btn || !box) return;
  btn.addEventListener("click", function () {
    var div = document.createElement("div");
    div.className = "nm-repeat";
    div.innerHTML =
      '<input type="hidden" name="sp_id[]" value="" />' +
      '<p><label>نام</label><input type="text" name="sp_name[]" value="" /></p>' +
      '<p><label>توضیح</label><input type="text" name="sp_bio[]" value="" /></p>';
    box.appendChild(div);
  });
})();
