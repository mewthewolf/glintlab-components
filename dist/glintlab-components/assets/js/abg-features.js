(function () {
  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function buildList(items) {
    return (items || [])
      .map(function (t) {
        return (
          '<li><span class="glintlab-abg-modal__bullet">•</span><span>' +
          escapeHtml(t) +
          "</span></li>"
        );
      })
      .join("");
  }

  function init(rootId, data, options) {
    var root = document.getElementById(rootId);
    if (!root) return;

    var heading = (options && options.heading) || "Capabilities";
    var subheading = (options && options.subheading) || "Tap a card to see details.";
    var ariaLabel = (options && options.ariaLabel) || "ABG capabilities";

    root.innerHTML =
      '<section aria-label="' +
      escapeHtml(ariaLabel) +
      '">' +
      '  <header class="glintlab-abg-features__header">' +
      "    <h2>" +
      escapeHtml(heading) +
      "</h2>" +
      "    <p>" +
      escapeHtml(subheading) +
      "</p>" +
      "  </header>" +
      '  <div class="glintlab-abg-features__grid" role="list"></div>' +
      "</section>" +
      '<div class="glintlab-abg-modal" data-open="false" aria-hidden="true">' +
      '  <div class="glintlab-abg-modal__panel" role="dialog" aria-modal="true" aria-labelledby="' +
      escapeHtml(rootId) +
      '-title" tabindex="-1">' +
      '    <h3 class="glintlab-abg-modal__title" id="' +
      escapeHtml(rootId) +
      '-title"></h3>' +
      '    <p class="glintlab-abg-modal__desc" id="' +
      escapeHtml(rootId) +
      '-desc"></p>' +
      '    <h4 class="glintlab-abg-modal__section-title">Key Benefits:</h4>' +
      '    <ul class="glintlab-abg-modal__list" id="' +
      escapeHtml(rootId) +
      '-benefits"></ul>' +
      '    <h4 class="glintlab-abg-modal__section-title">Typical Outcomes:</h4>' +
      '    <ul class="glintlab-abg-modal__list" id="' +
      escapeHtml(rootId) +
      '-outcomes"></ul>' +
      '    <div class="glintlab-abg-modal__footer">Tap any open space to close</div>' +
      "  </div>" +
      "</div>";

    var grid = root.querySelector(".glintlab-abg-features__grid");
    var modal = root.querySelector(".glintlab-abg-modal");
    var panel = modal.querySelector(".glintlab-abg-modal__panel");
    var titleEl = document.getElementById(rootId + "-title");
    var descEl = document.getElementById(rootId + "-desc");
    var benefitsEl = document.getElementById(rootId + "-benefits");
    var outcomesEl = document.getElementById(rootId + "-outcomes");

    var lastActiveEl = null;

    function openModal(item) {
      lastActiveEl = document.activeElement;
      titleEl.textContent = item.title || "";
      descEl.textContent = item.description || "";
      benefitsEl.innerHTML = buildList(item.keyBenefits || []);
      outcomesEl.innerHTML = buildList(item.typicalOutcomes || []);
      modal.dataset.open = "true";
      modal.setAttribute("aria-hidden", "false");
      panel.focus();
      document.documentElement.style.overflow = "hidden";
    }

    function closeModal() {
      modal.dataset.open = "false";
      modal.setAttribute("aria-hidden", "true");
      document.documentElement.style.overflow = "";
      if (lastActiveEl && typeof lastActiveEl.focus === "function") lastActiveEl.focus();
      lastActiveEl = null;
    }

    function onKeyDown(e) {
      if (modal.dataset.open !== "true") return;
      if (e.key === "Escape") closeModal();
    }

    (data || []).forEach(function (item, idx) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "glintlab-abg-feature";
      btn.setAttribute("role", "listitem");
      btn.setAttribute("aria-haspopup", "dialog");
      btn.setAttribute("data-index", String(idx));
      btn.innerHTML =
        '<div class="glintlab-abg-feature__title">' +
        "<span>" +
        escapeHtml(item.title || "") +
        '</span><span class="glintlab-abg-feature__arrow" aria-hidden="true">→</span>' +
        "</div>" +
        '<p class="glintlab-abg-feature__desc">' +
        escapeHtml(item.description || "") +
        "</p>";
      btn.addEventListener("click", function () {
        openModal(item || {});
      });
      grid.appendChild(btn);
    });

    modal.addEventListener("click", function (e) {
      if (e.target === modal) closeModal();
    });
    panel.addEventListener("click", function (e) {
      e.stopPropagation();
    });
    document.addEventListener("keydown", onKeyDown);
  }

  window.GlintLabABGFeatures = window.GlintLabABGFeatures || {};
  window.GlintLabABGFeatures.init = init;
})();

