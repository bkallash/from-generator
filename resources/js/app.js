import "./bootstrap";
import Chart from "chart.js/auto";

// Expose for Livewire @script blocks (analytics charts, etc.)
window.Chart = Chart;

/**
 * Resolve whether dark mode should be active from localStorage / system pref.
 */
function shouldUseDarkMode() {
    const theme = localStorage.getItem("theme");
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    return theme === "dark" || (!theme && prefersDark);
}

/**
 * Apply dark/light classes + color-scheme.
 *
 * Critical: Livewire wire:navigate calls replaceHtmlAttributes() which
 * REMOVES class="dark" from <html> because the server HTML never has it.
 * Analytics also injects Chart.js assets, delaying livewire:navigated —
 * without an immediate re-apply the whole page paints light (flashbang).
 */
function setDarkMode(isDark) {
    const html = document.documentElement;

    if (isDark) {
        html.classList.add("dark");
        html.style.colorScheme = "dark";
    } else {
        html.classList.remove("dark");
        html.style.colorScheme = "light";
    }
}

function lockTransitions() {
    document.documentElement.classList.add("no-transitions");
}

function unlockTransitions() {
    // Double rAF: wait until after the browser has painted the new state
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.documentElement.classList.remove("no-transitions");
        });
    });
}

function applyTheme({ releaseTransitions = true } = {}) {
    lockTransitions();
    setDarkMode(shouldUseDarkMode());
    void document.documentElement.offsetHeight;

    if (releaseTransitions) {
        unlockTransitions();
    }
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
        const goingDark = !document.documentElement.classList.contains("dark");

        lockTransitions();
        setDarkMode(goingDark);
        localStorage.setItem("theme", goingDark ? "dark" : "light");
        void document.documentElement.offsetHeight;
        unlockTransitions();
    };

    themeToggle.addEventListener("click", currentToggleHandler);
}

// ─── Initial page load ───────────────────────────────────────────────────────
applyTheme();
document.addEventListener("DOMContentLoaded", bindThemeToggle);

// ─── Permanent guard ─────────────────────────────────────────────────────────
// Livewire wire:navigate runs replaceHtmlAttributes() and removes any class on
// <html> that the server HTML doesn't have — including our client-only `.dark`.
// This observer snaps it back the moment it's stripped (before paint settles).
new MutationObserver(() => {
    const wantDark = shouldUseDarkMode();
    const hasDark = document.documentElement.classList.contains("dark");

    if (wantDark && !hasDark) {
        document.documentElement.classList.add("dark");
        document.documentElement.style.colorScheme = "dark";
    } else if (!wantDark && hasDark) {
        // Don't fight an intentional light-mode toggle mid-click; only correct
        // strip-away of light when localStorage says light and class is wrong.
        // (Toggle handler sets localStorage first, so this stays consistent.)
    }
}).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"],
});

// ─── wire:navigate SPA transitions ───────────────────────────────────────────
//
// Livewire navigate timeline (simplified):
//   1. alpine:navigating / livewire:navigating
//   2. replaceHtmlAttributes()  → STRIPS class="dark" from <html>
//   3. document.body.replaceWith(newBody)
//   4. detail.onSwap callbacks  ← re-apply theme HERE (same paint cycle)
//   5. Wait for any NEW remote scripts to load
//   6. alpine:navigated / livewire:navigated
//
// Analytics was uniquely bad because Chart.js was loaded from a CDN via
// @assets — step 5 waited on the network with .dark already stripped.
// Chart.js is now bundled; onSwap + the MutationObserver cover the strip gap.

document.addEventListener("livewire:navigating", (event) => {
    lockTransitions();
    setDarkMode(shouldUseDarkMode());

    // Re-apply immediately after body swap — before remote scripts resolve
    event.detail?.onSwap?.(() => {
        setDarkMode(shouldUseDarkMode());
    });
});

document.addEventListener("livewire:navigated", () => {
    applyTheme({ releaseTransitions: true });
    bindThemeToggle();
});
