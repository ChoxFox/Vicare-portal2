// =========================================================================
// VICARE ASYNCHRONOUS AUTH ENGINE - PATIENT LOGIN MODULE CONTROLLER
// =========================================================================

// CLIENT-SIDE ENGINE: Automatically handles URL fallbacks if someone redirects with traditional GET parameters
window.addEventListener('DOMContentLoaded', () => {
    const urlParameters = new URLSearchParams(window.location.search);
    if (urlParameters.has('error')) {
        const errorType = urlParameters.get('error');
        const banner = document.getElementById('loginErrorBanner');
        if (banner) {
            if (errorType === 'emptyfields') {
                banner.innerText = "Please fill in all credential fields.";
            } else if (errorType === 'invalidcredentials') {
                banner.innerText = "Invalid login credentials.";
            }
            banner.style.display = 'block';
        }
    }
});

// INTERACTIVE AJAX SUBMISSION GATEWAY HANDLER
document.getElementById('patientLoginForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Halt traditional browser page refreshes to protect DOM tracking states

    const errorAlert = document.getElementById('loginErrorBanner');
    if (errorAlert) {
        errorAlert.style.display = 'none'; // Reset banner view block state before running a fresh transaction
    }

    const contact = document.getElementById('contact').value.trim();
    const password = document.getElementById('password').value;

    if (!contact || !password) {
        if (errorAlert) {
            errorAlert.innerText = "Please enter all authorization credentials.";
            errorAlert.style.display = 'block';
        }
        return;
    }

    const formData = new FormData(this);

    // Directs transaction payloads exactly to your active processing script file
    fetch('patient-login.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if (!res.ok) { throw new Error('Network response returned an execution exception.'); }
        return res.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // SUCCESS: Seamlessly drop the validated patient onto their dashboard canvas
            window.location.href = 'patient-dashboard.html';
        } else {
            // FAILURE: Dynamically show the custom database error message row cleanly on the login card
            if (errorAlert) {
                errorAlert.innerText = data.message || "Invalid credentials.";
                errorAlert.style.display = 'block';
            }
        }
    })
    .catch(err => {
        console.error("Authentication link breakdown:", err);
        if (errorAlert) {
            errorAlert.innerText = "Connection link error. Ensure your local XAMPP Apache & MySQL databases are active.";
            errorAlert.style.display = 'block';
        }
    });
});

// FIXED HOOK WRAPPER: Handles password clear text toggling matching your HTML click identifiers flawlessly
function togglePatientLoginPassword() {
    const passwordField = document.getElementById("password");
    const toggleIcon = document.getElementById("toggleLoginPasswordIcon");

    if (passwordField && toggleIcon) {
        if (passwordField.type === "password") {
            passwordField.type = "text";
            toggleIcon.classList.remove("fa-eye");
            toggleIcon.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            toggleIcon.classList.remove("fa-eye-slash");
            toggleIcon.classList.add("fa-eye");
        }
    }
}
