/**
 * Team Member Modal - Indestructible Version
 * This script is self-contained: it injects its own modal HTML and uses 
 * event delegation to handle clicks on any .c-team-member card.
 */
(function () {
    const MODAL_ID = "c-team-member-modal";

    const injectModal = () => {
        if (document.getElementById(MODAL_ID)) return;

        const modalHtml = `
            <div class="c-team-member-modal" id="${MODAL_ID}" data-open="false" aria-hidden="true">
                <div class="c-team-member-modal__panel" role="dialog" aria-modal="true" aria-labelledby="${MODAL_ID}-title" tabindex="-1">
                    <div class="c-team-member-modal__layout">
                        <div class="c-team-member-modal__aside" aria-hidden="true">
                            <img class="c-team-member-modal__photo" id="${MODAL_ID}-photo" alt="" />
                        </div>
                        <div class="c-team-member-modal__content">
                            <h3 class="c-team-member-modal__title" id="${MODAL_ID}-title"></h3>
                            <p class="c-team-member-modal__subtitle" id="${MODAL_ID}-subtitle"></p>
                            <div class="c-team-member-modal__body" id="${MODAL_ID}-body"></div>
                        </div>
                    </div>
                    <div class="c-team-member-modal__footer">Tap any open space to close</div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML("beforeend", modalHtml);
        console.log("Team Member Modal: HTML injected into body.");
    };

    const init = () => {
        injectModal();

        const modal = document.getElementById(MODAL_ID);
        const panel = modal.querySelector(".c-team-member-modal__panel");
        const modalPhoto = document.getElementById(`${MODAL_ID}-photo`);
        const modalTitle = document.getElementById(`${MODAL_ID}-title`);
        const modalSubtitle = document.getElementById(`${MODAL_ID}-subtitle`);
        const modalBody = document.getElementById(`${MODAL_ID}-body`);

        let lastActive = null;
        let activeCard = null;
        let activeWrapper = null;

        const toggleScrollLock = (lock) => {
            const state = lock ? "hidden" : "";
            document.documentElement.style.overflow = state;
            document.body.style.overflow = state;
        };

        const openModal = ({ titleText, subtitleText, bodyHtml, photoSrc, photoAlt, triggerCard, triggerWrapper }) => {
            lastActive = document.activeElement;

            panel.scrollTop = 0;
            modalPhoto.src = photoSrc || "";
            modalPhoto.alt = photoAlt || "";
            modalTitle.textContent = titleText || "";
            modalSubtitle.textContent = subtitleText || "";
            modalBody.innerHTML = bodyHtml || "";

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

        // Universal Event Delegation
        document.addEventListener("click", function (e) {
            // Find the closest card or wrapper
            const card = e.target.closest(".c-team-member");
            if (!card) return;

            // Find the wrapper (might be the card itself or a parent)
            const wrapper = card.closest(".wp-block-tiptip-hyperlink-group-block") || card;

            // Check if clicking a real link inside (LinkedIn, etc.)
            const link = e.target.closest("a");
            if (link && card.contains(link)) {
                const href = link.getAttribute("href");
                if (href && href !== "#" && !href.startsWith("javascript:")) {
                    return; // Let browser handle it
                }
            }

            e.preventDefault();

            // Extract data
            const title = card.querySelector(".c-team-member__name");
            const subtitle = card.querySelector(".c-team-member__bio");
            const description = card.querySelector(".c-team-member__description");
            const img = card.querySelector(".c-team-member__avatar img");

            if (!title || !subtitle || !description || !img) {
                console.warn("Team Member Modal: Could not find all data in card.", { title, subtitle, description, img });
                return;
            }

            openModal({
                titleText: title.textContent.trim(),
                subtitleText: subtitle.textContent.trim(),
                bodyHtml: description.innerHTML,
                photoSrc: img.src,
                photoAlt: img.alt,
                triggerCard: card,
                triggerWrapper: wrapper
            });
        });

        console.log("Team Member Modal: Event delegation initialized.");
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
