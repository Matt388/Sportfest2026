// Dark Mode Toggle
function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');

    if (!darkModeToggle) return;

    // Get system preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Check for saved user preference, if any, otherwise use system preference
    const savedTheme = localStorage.getItem('theme');
    const currentTheme = savedTheme || (prefersDark ? 'dark' : 'light');

    document.documentElement.setAttribute('data-theme', currentTheme);
    updateDarkModeIcon(currentTheme);

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        // Only auto-switch if user hasn't manually set a preference
        if (!localStorage.getItem('theme')) {
            const newTheme = e.matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            updateDarkModeIcon(newTheme);
        }
    });

    // Toggle dark mode
    darkModeToggle.addEventListener('click', function() {
        let theme = document.documentElement.getAttribute('data-theme');
        let newTheme = theme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateDarkModeIcon(newTheme);
    });

    function updateDarkModeIcon(theme) {
        if (darkModeIcon) {
            darkModeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dark mode
    initDarkMode();

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Wirklich löschen?');
}
