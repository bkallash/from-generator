import "./bootstrap";

/**
 * Apply the stored theme (or system preference) to the document.
 * Called on initial load AND after every wire:navigate transition.
 */
function applyTheme() {
    const theme = localStorage.getItem("theme");
    if (
        theme === "dark" ||
        (!theme && window.matchMedia("(prefers-color-scheme: dark)").matches)
    ) {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }
}

/**
 * Bind the click handler for the #themeToggle button.
 * Safe to call multiple times — it removes any previous listener first.
 */
let currentToggleHandler = null;

function bindThemeToggle() {
    const themeToggle = document.getElementById("themeToggle");
    if (!themeToggle) return;

    // Remove previous listener to avoid duplicates after re-navigation
    if (currentToggleHandler) {
        themeToggle.removeEventListener("click", currentToggleHandler);
    }

    currentToggleHandler = () => {
        const html = document.documentElement;
        if (html.classList.contains("dark")) {
            html.classList.remove("dark");
            localStorage.setItem("theme", "light");
        } else {
            html.classList.add("dark");
            localStorage.setItem("theme", "dark");
        }
    };

    themeToggle.addEventListener("click", currentToggleHandler);
}

// Initial page load
applyTheme();
document.addEventListener("DOMContentLoaded", bindThemeToggle);

// After Livewire wire:navigate SPA transitions
document.addEventListener("livewire:navigated", () => {
    applyTheme();
    bindThemeToggle();
});
