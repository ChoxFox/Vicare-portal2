// =========================================================================
// VICARE PATIENT SUITE - PRESCRIPTION CONTROLLER & CALENDAR MODAL TUNNELS
// =========================================================================

window.renderPatientPrescriptionCard = function(data) {
    const statusText = document.getElementById('prescriptionNoticeText');
    const detailsBox = document.getElementById('prescriptionDiagnosisBox');
    const downloadBtn = document.getElementById('downloadPrescriptionBtn');
    const confirmOkBtn = document.getElementById('okPrescriptionBtn');
    const pastLinkBox = document.getElementById('prevPrescriptionLinkWrapper');
    const pastDownloadBtn = document.getElementById('downloadPreviousPrescriptionBtn');

    if (!statusText || !detailsBox || !downloadBtn || !confirmOkBtn) return;

    if (!data.prescription_exist) {
        statusText.innerText = "wait for your practicioner to send your prescription";
        detailsBox.style.display = 'none'; downloadBtn.style.display = 'none'; confirmOkBtn.style.display = 'none';
        
        if (pastLinkBox && pastDownloadBtn) {
            if (data.has_previous_prescription) {
                pastDownloadBtn.href = data.prev_file_path;
                pastLinkBox.style.display = 'block';
            } else {
                pastLinkBox.style.display = 'none';
            }
        }
    } else {
        statusText.innerText = "New Medical Prescription Released by " + (data.p_doctor_name || "Clinician") + ".";
        document.getElementById('prescriptionDiagnosisText').innerText = data.p_diagnosis;
        detailsBox.style.display = 'block'; downloadBtn.style.display = 'inline-block'; confirmOkBtn.style.display = 'none';
        downloadBtn.href = data.file_path;
        if (pastLinkBox) pastLinkBox.style.display = 'none';
    }
};

// State 1 Transiting smoothly into State 2
document.getElementById('downloadPrescriptionBtn').addEventListener('click', function() {
    document.getElementById('prescriptionNoticeText').innerText = "file downloaded successfuly";
    document.getElementById('prescriptionDiagnosisBox').style.display = 'none';
    this.style.display = 'none';
    document.getElementById('okPrescriptionBtn').style.display = 'inline-block';
});

// State 2 Transiting back into State 3 Standby
document.getElementById('okPrescriptionBtn').addEventListener('click', function() {
    this.style.display = 'none';
    fetch('api/clear_prescription_alert.php', { method: 'POST' })
    .then(() => { if (typeof fetchPatientIdentitySession === 'function') fetchPatientIdentitySession(); });
});

// ====== INTERACTIVE APPOINTMENT MODAL OVERLAY SHEET WINDOW CLICKERS ======
document.getElementById('bookAppointmentBtn').addEventListener('click', () => {
    document.getElementById('appointmentModalOverlay').style.display = 'flex';
    fetch('api/get_online_doctors.php')
    .then(res => res.json())
    .then(data => {
        const dropMenu = document.getElementById('bookDoctorSelect');
        if (dropMenu && data.status === 'success') {
            dropMenu.innerHTML = '<option value="" disabled selected>Choose a clinician...</option>';
            data.doctors.forEach(doc => {
                dropMenu.innerHTML += `<option value="${doc.name}">${doc.name} (${doc.specialty})</option>`;
            });
        }
    });
});

document.getElementById('closeBookingModalBtn').addEventListener('click', () => {
    document.getElementById('appointmentModalOverlay').style.display = 'none';
});

document.getElementById('appointmentBookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/book_appointment.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Consultation request pushed to doctor panel successfully!');
            document.getElementById('appointmentModalOverlay').style.display = 'none';
            this.reset();
            if (typeof fetchPatientIdentitySession === 'function') fetchPatientIdentitySession();
        }
    });
});
