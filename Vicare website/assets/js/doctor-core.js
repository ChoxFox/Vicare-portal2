// =========================================================================
// VICARE PRACTITIONER SUITE - CRASH-PROOF MAIN MODULE & VITALS TRANSCEIVER
// =========================================================================

function fetchDoctorIdentityAndSchedules() {
    fetch('api/get_doctor_session.php?t=' + new Date().getTime())
    .then(res => {
        if (!res.ok) throw new Error("Doctor session handshake network drop.");
        return res.json();
    })
    .then(data => {
        if (data.status === 'logged_out') {
            window.location.href = 'doctors-login-form.html';
            return;
        }

        // 1. INJECT PRACTITIONER NAME GREETING
        const docHeaderName = document.getElementById('welcomeNameDisplay') || document.querySelector('.topbar h2');
        if (docHeaderName) {
            docHeaderName.innerText = "Hello " + data.doctor_name + "!";
        }

        // 2. FIXED PATH ROUTER: Forces doctor avatar bubble image to render error-free
        const docAvatar = document.getElementById('doctorProfileImageDisplay') || document.getElementById('profileImageDisplay');
        if (docAvatar) {
            let imgFile = data.doctor_image ? data.doctor_image.trim() : '';
            if (imgFile === '149071.png' || imgFile === '') {
                docAvatar.src = "assets/images/149071.png"; // Static assets fallback folder
            } else {
                docAvatar.src = imgFile.includes('uploads/') ? imgFile : "uploads/" + imgFile;
            }
        }

        // 3. RENDER PENDING CALENDAR SCHEDULING TRAYS
        renderDoctorPendingAppointments(data.pending_appointments);
    })
    .catch(err => console.error("Doctor session sync exception:", err));
}

function renderDoctorPendingAppointments(appointments) {
    const tray = document.getElementById('doctorPendingAppointmentsTray') || document.getElementById('patientAppointmentsContainer');
    if (!tray) return;

    if (!appointments || appointments.length === 0) {
        tray.innerHTML = '<p style="color:#64748b; font-style:italic; font-size:13px; text-align:center; padding:20px;">No pending consultation requests found.</p>';
        return;
    }

    tray.innerHTML = '';
    appointments.forEach(app => {
        const rowItem = document.createElement('div');
        rowItem.className = 'appointment';
        rowItem.style.background = '#e0e7ff';
        rowItem.style.padding = '15px';
        rowItem.style.borderRadius = '12px';
        rowItem.style.marginBottom = '10px';
        rowItem.style.display = 'flex';
        rowItem.style.justifyContent = 'space-between';
        rowItem.style.alignItems = 'center';

        rowItem.innerHTML = `
            <div>
                <h4 style="margin:0; color:#1e293b; font-size:15px; font-weight:bold;">${app.patient_name}</h4>
                <p style="margin:4px 0 0 0; color:#64748b; font-size:12px;"><i class="fa-solid fa-clock" style="margin-right:5px;"></i>${app.app_date} at ${app.app_time}</p>
                <span style="font-size:11px; color:#475569; font-weight:500;">Contact: ${app.patient_contact}</span>
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" class="chat-btn" style="background:#00a63e; padding:8px 12px;" onclick="handleAppointmentAction(${app.appointment_id}, 'Accepted')">Accept</button>
                <button type="button" class="chat-btn" style="background:#ff5757; padding:8px 12px;" onclick="handleAppointmentAction(${app.appointment_id}, 'Denied')">Deny</button>
            </div>
        `;
        tray.appendChild(rowItem);
    });
}

function handleAppointmentAction(appId, actionType) {
    const fd = new FormData();
    fd.append('appointment_id', appId);
    fd.append('action', actionType);

    fetch('api/process_appointment_action.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            fetchDoctorIdentityAndSchedules();
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error("Scheduling workflow error:", err));
}

// Global initialization parameters orchestration loop
window.addEventListener('DOMContentLoaded', () => {
    fetchDoctorIdentityAndSchedules();
    setInterval(fetchDoctorIdentityAndSchedules, 6000);
    
    // ====== SAFE VITALS SUBMISSION CORES INSTANTIATOR ======
    const vitalsForm = document.getElementById('vitalsSubmissionForm');
    if (vitalsForm) {
        vitalsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // FIXED CASE SENSITIVITY: Matches your exact lowercase placeholders from doctor-dashboard.html flawlessly [INDEX]
            const nameInput    = document.getElementById('v_patient_name') || document.querySelector('input[placeholder="full name"]');
            const contactInput = document.getElementById('v_patient_contact') || document.querySelector('input[placeholder="mobile number or email"]');
            const bpInput      = document.getElementById('v_blood_pressure') || document.querySelector('input[placeholder="Blood Pressure"]');
            const hrInput      = document.getElementById('v_heart_rate') || document.querySelector('input[placeholder="Heart Rate"]');
            const respInput    = document.getElementById('v_respiration') || document.querySelector('input[placeholder="Respiration"]');
            const tempInput    = document.getElementById('v_temperature') || document.querySelector('input[placeholder="Temperature"]');

            if (!nameInput || !contactInput) {
                alert("Critical selection error: Check form input classes.");
                return;
            }

            if (!nameInput.value.trim() || !contactInput.value.trim()) {
                alert("🚨 Form Entry Blocked: Please complete all patient metadata fields.");
                return;
            }

            fetch('api/send_vitals.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') vitalsForm.reset();
            })
            .catch(err => alert("Network communication fault. Check XAMPP server logs."));
        });
    }
});
