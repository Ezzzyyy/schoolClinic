const passwordInput = document.getElementById("password");
const toggleButton = document.querySelector(".visibility-toggle");

if (passwordInput && toggleButton) {
    toggleButton.addEventListener("click", () => {
        const reveal = passwordInput.type === "password";
        passwordInput.type = reveal ? "text" : "password";
        toggleButton.setAttribute("aria-label", reveal ? "Hide password" : "Show password");
        toggleButton.setAttribute("aria-pressed", String(reveal));
        toggleButton.classList.toggle("is-visible", reveal);
    });
}
