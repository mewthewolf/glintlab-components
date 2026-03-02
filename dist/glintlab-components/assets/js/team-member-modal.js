/**
 * Team Member Modal
 * Self-contained: injects its own modal HTML and uses event delegation to handle clicks on any .c-team-member card.
 */
(function () {
  const MODAL_ID = "c-team-member-modal";

  const injectModal = () => {
    if (document.getElementById(MODAL_ID)) return;

    const modalHtml = `
      <div class="c-team-member-modal" id="${MODAL_ID}" data-open="false" aria-hidden="true">
        <div class="c-team-member-modal__panel" role="dialog" aria-modal="true" aria-labelledby="${MODAL_ID}-title" tabindex="-1">
          <div class="c-team-member-modal__layout">
            <div class="c-team-member-modal__aside">
              <img class="c-team-member-modal__photo" id="${MODAL_ID}-photo" alt="" />
              <div class="c-team-member-modal__link-wrapper" id="${MODAL_ID}-link-wrapper"></div>
            </div>
            <div class="c-team-member-modal__content">
              <h3 class="c-team-member-modal__title" id="${MODAL_ID}-title"></h3>
              <p class="c-team-member-modal__subtitle" id="${MODAL_ID}-subtitle"></p>
              <div class="c-team-member-modal__body" id="${MODAL_ID}-body"></div>
              <div class="c-team-member-modal__footer">Tap any open space to close</div>
            </div>
          </div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML("beforeend", modalHtml);
  };

  const init = () => {
    injectModal();

    const modal = document.getElementById(MODAL_ID);
    if (!modal) return;

    const panel = modal.querySelector(".c-team-member-modal__panel");
    const modalPhoto = document.getElementById(`${MODAL_ID}-photo`);
    const modalTitle = document.getElementById(`${MODAL_ID}-title`);
    const modalSubtitle = document.getElementById(`${MODAL_ID}-subtitle`);
    const modalLinkWrapper = document.getElementById(`${MODAL_ID}-link-wrapper`);
    const modalBody = document.getElementById(`${MODAL_ID}-body`);

    let lastActive = null;
    let activeCard = null;
    let activeWrapper = null;

    const toggleScrollLock = (lock) => {
      const state = lock ? "hidden" : "";
      document.documentElement.style.overflow = state;
      document.body.style.overflow = state;
    };

    const openModal = ({ titleText, subtitleText, bodyHtml, photoSrc, photoAlt, linkUrl, triggerCard, triggerWrapper }) => {
      lastActive = document.activeElement;

      panel.scrollTop = 0;
      modalPhoto.src = photoSrc || "";
      modalPhoto.alt = photoAlt || "";
      modalTitle.textContent = titleText || "";
      modalSubtitle.textContent = subtitleText || "";
      modalBody.innerHTML = bodyHtml || "";

      if (linkUrl && linkUrl !== "#") {
        modalLinkWrapper.innerHTML = `<a href="${linkUrl}" class="c-team-member-modal__link" target="_blank" rel="noopener noreferrer">View Profile</a>`;
        modalLinkWrapper.style.display = "flex";
      } else {
        modalLinkWrapper.innerHTML = "";
        modalLinkWrapper.style.display = "none";
      }

      activeCard?.classList.remove("is-expanded");
      activeWrapper?.setAttribute("aria-expanded", "false");

      activeCard = triggerCard;
      activeWrapper = triggerWrapper;
      activeCard.classList.add("is-expanded");
      activeWrapper.setAttribute("aria-expanded", "true");

      modal.dataset.open = "true";
      modal.setAttribute("aria-hidden", "false");
      toggleScrollLock(true);
      panel.focus();

      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          modal.classList.add("is-animating");
        });
      });
    };

    const closeModal = () => {
      if (modal.dataset.open !== "true") return;

      modal.dataset.open = "false";
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-animating");
      toggleScrollLock(false);

      activeCard?.classList.remove("is-expanded");
      activeWrapper?.setAttribute("aria-expanded", "false");
      activeCard = null;
      activeWrapper = null;

      if (lastActive && typeof lastActive.focus === "function") {
        lastActive.focus();
      }
      lastActive = null;
    };

    // Listeners
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
    panel.addEventListener("click", (e) => e.stopPropagation());
    document.addEventListener("keydown", (e) => {
      if (modal.dataset.open === "true" && e.key === "Escape") closeModal();
    });

    // Universal Event Delegation (scoped to this plugin's trigger wrapper)
    // Use capture so this still works if other scripts stop propagation.
    document.addEventListener(
      "click",
      function (e) {
        const wrapper = e.target.closest(".glintlab-team-member-trigger");
        if (!wrapper) return;

        const card = wrapper.querySelector(".c-team-member");
        if (!card) return;

        const linkUrl = wrapper.dataset.linkUrl;

        // Allow clicks on real links inside the card (if any remain)
        const link = e.target.closest("a");
        if (link && card.contains(link) && link !== wrapper) {
          const href = link.getAttribute("href");
          if (href && href !== "#" && !href.startsWith("javascript:")) {
            return;
          }
        }

        e.preventDefault();

        const title = card.querySelector(".c-team-member__name");
        const subtitle = card.querySelector(".c-team-member__bio");
        const description = card.querySelector(".c-team-member__description");
        const img = card.querySelector(".c-team-member__avatar img");

        if (!title) return;

        openModal({
          titleText: title.textContent.trim(),
          subtitleText: subtitle ? subtitle.textContent.trim() : "",
          bodyHtml: description ? description.innerHTML || "" : "",
          photoSrc: img ? img.src : "",
          photoAlt: img ? img.alt : "",
          linkUrl: linkUrl,
          triggerCard: card,
          triggerWrapper: wrapper,
        });
      },
      true
    );
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
