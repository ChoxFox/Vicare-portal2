// ====== VICARE MAIN ENTRY PORTAL GATEWAY HANDLERS ======

function toggleDropdown() {
    const dropdown = document.getElementById('dropdown');
    // Safety check: Prevents code execution breaks if element is missing on screen
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Global click event dispatcher intercepts taps outside the button to close panels
window.onclick = function (e) {
    if (!e.target.matches('.login-btn')) {
        const dropdown = document.getElementById('dropdown');

        // Combined conditional safety checks ensure code never throws null errors
        if (dropdown && dropdown.classList.contains('active')) {
            dropdown.classList.remove('active');
        }
    }
};
