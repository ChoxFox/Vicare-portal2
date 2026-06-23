// =========================================================================
// VICARE MODULAR LAYER: CLINICAL MEDICAL VITALS & APPOINTMENT TRAY CONTROLLER
// =========================================================================

function fetchDoctorAppointmentsData() {
    const listTray = document.getElementById('doctorAppointmentsGridList');
    if (!listTray) return;

    fetch('api/get_doctor_appointments.php?t=' + new Date().getTime())
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            listTray.innerHTML = '';
            if (data.appointments.length === 0) {
                listTray.innerHTML = '<p style="color: #475569; font-style: italic; font-size: 13px; text-align: center; font-weight: bold; margin-top: 15px;">No active scheduling requests logged.</p>';
                return;
            }
            data.appointments.forEach(app => {
                const box = document.createElement('div');
                box.className = 'appointment-box';
                box.innerHTML = `
                    <p><strong>Patient:</strong> ${app.patient_name} (${app.patient_contact})</p>
                    <p><strong>Schedule:</strong> ${app.date} at ${app.time}</p>
                    <div class="btn-group">
                        <button class="btn accept" onclick="processScheduleAction(${app.id}, 'Accepted')">Accept</button>
                        <button class="btn deny" onclick="processScheduleAction(${app.id}, 'Denied')">Deny</button>
                    </div>
                `;
                listTray.appendChild(box);
            });
        }
    })
    .catch(err => console.error("Appointments logging pool dropped:", err));
}

window.processScheduleAction = function(appId, actionType) {
    const fd = new FormData();
    fd.append('appointment_id', appId);
    fd.append('action', actionType);

    fetch('api/process_appointment_action.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert(`Appointment request successfully marked as ${actionType}!`);
            fetchDoctorAppointmentsData();
        }
    });
};

// Vitals Submission Handler Matrix
document.getElementById('vitalsSubmissionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('api/send_vitals.php', { method: 'POST', body: new FormData(this) })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Patient medical vitals pushed successfully!');
            this.reset();
        } else { alert(data.message); }
    });
});

// Orchestrate local synchronization loop interval clocks
window.addEventListener('DOMContentLoaded', () => {
    fetchDoctorAppointmentsData();
    setInterval(fetchDoctorAppointmentsData, 6000);
});
