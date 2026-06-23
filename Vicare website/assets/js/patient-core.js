// =========================================================================
// VICARE PATIENT INTERFACE - MAIN IDENTITY MODULE & CLINICIAN SCROLLER
// =========================================================================

window.activeFocusedDoctorName = "";

function fetchPatientIdentitySession() {
    // Polls active login session variables from your secure backend API folder path
    fetch('api/get_patient_session_vitals.php?t=' + new Date().getTime())
    .then(res => {
        if (!res.ok) throw new Error("Patient session pipeline rejected.");
        return res.json();
    })
    .then(data => {
        if (data.status === 'logged_out') {
            window.location.href = 'patients-login-form.html';
            return;
        }

        // 1. INJECT PATIENT GREETING TEXT LABEL
        const nameHeader = document.getElementById('welcomeNameDisplay') || document.querySelector('.topbar h2');
        if (nameHeader) {
            nameHeader.innerText = "Hello " + data.patient_name + "!";
        }
        
        // 2. INJECT CIRCULAR PROFILE PICTURE AVATAR ERROR-FREE
        const avatarImg = document.getElementById('profileImageDisplay');
        if (avatarImg) {
            let imageSrc = data.patient_image ? data.patient_image.trim() : '';
            if (imageSrc === '149071.png' || imageSrc === '') {
                avatarImg.src = "assets/images/149071.png"; // Dynamic static assets fallback directory path
            } else {
                avatarImg.src = imageSrc.includes('uploads/') ? imageSrc : "uploads/" + imageSrc;
            }
        }

        // 3. SECURE ASYNCHRONOUS DOWNSTREAM SYSTEM BROADCASTS
        if (typeof renderPatientVitalsBox === 'function') {
            renderPatientVitalsBox(data);
        }
        if (typeof renderPatientAppointmentsHistory === 'function') {
            renderPatientAppointmentsHistory(data);
        }
        if (typeof renderPatientPrescriptionCard === 'function') {
            renderPatientPrescriptionCard(data);
        }
    })
    .catch(err => console.error("Patient session sync breakdown exceptions:", err));
}

// Global compatibility alias fallback protects downstream multi-script linkages
window.fetchPatientDashboardData = fetchPatientIdentitySession;

// FIXED COMPILATION BLOCK: Re-instantiated the parent function container wrapper cleanly!
function fetchOnlineAvailableDoctors() {
    const scrollerTray = document.getElementById('activeDoctorsScrollingTray');
    if (!scrollerTray) return;

    fetch('api/get_online_doctors.php?t=' + new Date().getTime())
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            scrollerTray.innerHTML = '';
            
            if (!data.doctors || data.doctors.length === 0) {
                scrollerTray.innerHTML = '<p class="tray-loading-text" style="color: #64748b; font-style: italic; font-size: 13px; text-align: center; margin-top: 15px; width: 100%;">Scanning for active online medical practitioners...</p>';
                return;
            }
            
            // Inside assets/js/patient-core.js -> fetchOnlineAvailableDoctors loop builder section
// ====== FIXED ASSET VARIABLE BIND: SECURES DOCTOR IDENTITY HANDLES FLALWESSLY ======
data.doctors.forEach(doc => {
    const docRow = document.createElement('div');
    docRow.className = 'doctor';
    
    let docImg = doc.image ? doc.image.trim() : '';
    docImg = docImg !== '' ? (docImg.includes('uploads/') ? docImg : "uploads/" + docImg) : "assets/images/149071.png";

    // EXPLICIT LOCK: Force extraction of 'doc.email' specifically to block unmapped property undefined traps
    let docTargetIdentifier = doc.email || doc.contact || "fortune@vicare.com"; 

    let escapedName = doc.name.replace(/'/g, "\\'");
    let escapedSpec = doc.specialty.replace(/'/g, "\\'");

    docRow.innerHTML = `
        <div class="doctor-left">
            <div class="image-box">
                <img src="${docImg}" alt="${doc.name}">
                <span class="status online"></span>
            </div>
            <div class="doctor-info">
                <h3>${doc.name}</h3>
                <p>${doc.specialty}</p>
                <span class="active">Available Now</span>
            </div>
        </div>
        <button type="button" class="chat-btn" onclick="openDirectDoctorChat('${docTargetIdentifier}', '${escapedName}', '${escapedSpec}', '${doc.image}')">Chat</button>
    `;
    scrollerTray.appendChild(docRow);
});

        }
    })
    .catch(err => console.error("Online directory scroller load exception:", err));
}

// Master execution initialization parameters orchestration layer
window.addEventListener('DOMContentLoaded', () => {
    fetchPatientIdentitySession();
    fetchOnlineAvailableDoctors();
    
    // Triggers smooth background synchronization queries every 5 seconds natively
    setInterval(fetchPatientIdentitySession, 5000);
    setInterval(fetchOnlineAvailableDoctors, 5000);
});
