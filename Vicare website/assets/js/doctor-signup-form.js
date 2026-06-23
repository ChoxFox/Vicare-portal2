// =========================================================================
// VICARE MULTI-STEP REGISTRATION MACHINE - DOCTOR WIZARD CONTROLLER ENGINE
// =========================================================================

// Step Navigation: Forward Step 1 -> Step 2
function goToStep2() {
    const first = document.getElementById("firstName").value.trim();
    const last = document.getElementById("lastName").value.trim();
    const contact = document.getElementById("contact").value.trim(); 

    if (!first || !last || !contact) {
        alert("Please fill all fields in this step.");
        return;
    }

    document.getElementById("step1").classList.remove("active");
    document.getElementById("step2").classList.add("active");
}

// Step Navigation: Backward Step 2 -> Step 1
function goToStep1() {
    document.getElementById("step2").classList.remove("active");
    document.getElementById("step1").classList.add("active");
}

// Final Form Submission Handler
document.getElementById('doctorSignupForm').addEventListener('submit', function(event) {
    event.preventDefault(); 

    const license = document.getElementById("license").value.trim();
    const password = document.getElementById("password").value;

    if (!license || !password) {
        alert("Please fill out all credentials.");
        return;
    }

    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return;
    }

    // Capture identities dynamically to build success dashboard cards instantly
    const first = document.getElementById("firstName").value.trim();
    const last = document.getElementById("lastName").value.trim();
    document.getElementById("doctorName").innerText = "Dr. " + first + " " + last;

    // LOCAL GRAPHIC PREVIEW: Renders file data streams into local canvas elements cleanly
    const imageInput = document.getElementById("imageInput");
    if (imageInput.files && imageInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById("previewImage").src = e.target.result;
        }
        reader.readAsDataURL(imageInput.files[0]); 
    } else {
        // FIXED: Swapped out flaticon handshake to prevent slow network connection timeouts [INDEX]
        document.getElementById("previewImage").src = "assets/images/149071.png";
    }

    const formData = new FormData(this);

    // FIXED API ROUTING: Adjusted endpoint to look relative inside your current root workspace folder directory [INDEX]
    fetch('doctors-signup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server returned an error response status.');
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            // Success State Transition: Expands card and hides background panels smoothly
            document.getElementById("doctorSignupForm").style.display = "none";
            document.getElementById("profileStep").classList.add("active");
            document.getElementById("heroBg").style.display = "none";
            document.getElementById("rightSection").style.width = "100%";
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Could not save your account. Please check your XAMPP database server connection.');
    });
});

// FIXED LINK ROUTING: Redirects smoothly inside your fresh 'Vicare website/' parent directory path [INDEX]
function goToDashboard() {
    window.location.href = 'doctor-dashboard.html';
}

// Password Text Stream Character Visibility Toggle Key Handler
function togglePasswordVisibility() {
    const passwordField = document.getElementById("password");
    const toggleIcon = document.getElementById("togglePasswordIcon");

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
