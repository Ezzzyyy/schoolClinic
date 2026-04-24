const shell = document.getElementById("dashboard-shell");
const sidebarToggle = document.getElementById("sidebar-toggle");
const mobileMenuButton = document.getElementById("mobile-menu");
const sidebarBrand = document.querySelector(".sidebar .brand");
const mobileQuery = window.matchMedia("(max-width: 980px)");

const renderIcons = () => {
    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    } else {
        // Fallback: reload lucide script if not loaded
        const script = document.createElement('script');
        script.src = '../../assets/vendor/lucide/lucide.js';
        script.onload = () => window.lucide && window.lucide.createIcons();
        document.body.appendChild(script);
    }
};

renderIcons();

if (sidebarToggle && shell) {
    sidebarToggle.addEventListener("click", () => {
        if (mobileQuery.matches) {
            return;
        }

        shell.classList.add("sidebar-collapsed");
    });
}

if (sidebarBrand && shell) {
    sidebarBrand.addEventListener("click", (event) => {
        if (mobileQuery.matches) {
            return;
        }

        if (shell.classList.contains("sidebar-collapsed")) {
            event.preventDefault();
            shell.classList.remove("sidebar-collapsed");
        }
    });
}

if (mobileMenuButton && shell) {
    mobileMenuButton.addEventListener("click", () => {
        shell.classList.toggle("sidebar-open");
    });

    document.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Node)) {
            return;
        }

        if (mobileQuery.matches) {
            const clickedInsideSidebar = target.closest(".sidebar");
            const clickedMenuButton = target.closest("#mobile-menu");
            if (!clickedInsideSidebar && !clickedMenuButton) {
                shell.classList.remove("sidebar-open");
            }
        }
    });
}

mobileQuery.addEventListener("change", () => {
    if (mobileQuery.matches) {
        shell?.classList.remove("sidebar-collapsed");
    } else {
        shell?.classList.remove("sidebar-open");
    }
});
