import "./bootstrap";

/**
 * Apply the stored theme (or system preference) to the document WITHOUT
 * triggering any CSS transitions (avoids the animated light→dark flash).
 */
function applyTheme() {
    const html = document.documentElement;
    const theme = localStorage.getItem("theme");
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const shouldBeDark = theme === "dark" || (!theme && prefersDark);

    // Disable all CSS transitions temporarily to prevent visual flashing/fades
    html.classList.add("no-transitions");

    if (shouldBeDark) {
        html.classList.add("dark");
    } else {
        html.classList.remove("dark");
    }

    // Force a paint/reflow to ensure class changes apply instantly without transitions
    const _ = html.offsetHeight;

    // Re-enable transitions on next animation frame
    requestAnimationFrame(() => {
        html.classList.remove("no-transitions");
    });
}

/**
 * Bind the click handler for the #themeToggle button.
 * Safe to call multiple times — removes any previous listener first.
 */
let currentToggleHandler = null;

function bindThemeToggle() {
    const themeToggle = document.getElementById("themeToggle");
    if (!themeToggle) return;

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

// ─── Initial page load ───────────────────────────────────────────────────────
// The inline <head> script already added .dark synchronously, but we still
// call applyTheme() so the toggle-suppression logic runs once for safety.
applyTheme();
document.addEventListener("DOMContentLoaded", bindThemeToggle);

// ─── wire:navigate SPA transitions ───────────────────────────────────────────
// livewire:navigate fires BEFORE the DOM swap — lock the class so the
// incoming page immediately inherits the right state, with no flash window.
document.addEventListener("livewire:navigate", () => {
    applyTheme();
});

// livewire:navigated fires AFTER the swap — re-assert the theme (Livewire's
// DOM morph strips the JS-added `dark` class from <html>) and rebind toggle.
document.addEventListener("livewire:navigated", () => {
    applyTheme();
    bindThemeToggle();
});
