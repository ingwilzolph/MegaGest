/* Interacciones públicas propias de AlianzaPro, sin Bootstrap ni jQuery. */
document.addEventListener("DOMContentLoaded", () => {
    const preloader = document.getElementById("preloader");
    if (preloader) preloader.hidden = true;

    document.querySelectorAll(".navbar-toggler").forEach((button) => {
        button.addEventListener("click", () => {
            const selector = button.getAttribute("data-target") || "#navbarNavDropdown";
            const target = document.querySelector(selector);
            if (!target) return;
            const abierto = target.classList.toggle("show");
            button.setAttribute("aria-expanded", String(abierto));
        });
    });

    document.querySelectorAll(".dropdown-toggle").forEach((button) => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            button.closest(".dropdown")?.classList.toggle("is-open");
        });
    });

    document.addEventListener("click", () => {
        document.querySelectorAll(".dropdown.is-open").forEach((item) => item.classList.remove("is-open"));
    });

    document.getElementById("back-to-top")?.addEventListener("click", (event) => {
        event.preventDefault();
        window.scrollTo({top: 0, behavior: "smooth"});
    });
});
