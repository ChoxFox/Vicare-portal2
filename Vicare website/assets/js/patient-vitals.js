// =========================================================================
// VICARE PATIENT SUITE - DEFINITIVE DATA UNBOXING RENDERING ENGINE
// =========================================================================

window.renderPatientVitalsBox = function(data) {
    const banner = document.getElementById('vitalsNoticeBlock');
    const label = document.getElementById('noticeText');
    const actions = document.getElementById('vitalsActions');
    const layoutGrid = document.getElementById('vitalGridData');

    if (!banner || !actions || !layoutGrid) return;

    if (!data.vitals_exist) {
        banner.style.display = 'block';
        if (label) label.innerText = "Visit a healthcare professional to initialize your medical vitals tracking telemetry panels.";
        actions.style.display = 'none';
        layoutGrid.style.setProperty('display', 'none', 'important');
    } else if (data.vitals_exist && data.vitals_status === 'Pending') {
        banner.style.display = 'block';
        if (label) label.innerText = "Doctor " + data.doctor_name + " has updated your metrics. Please verify details.";
        actions.style.display = 'flex';
        layoutGrid.style.setProperty('display', 'none', 'important');
    } else if (data.vitals_exist && data.vitals_status === 'Accepted') {
        // Hides the prompt warning banners cleanly from your card view
        banner.style.display = 'none';
        actions.style.display = 'none';
        
        // FIXED DATA INJECTION: Forces text numbers straight into your elements layout slots
        const bpVal = document.getElementById('bp_val');
        const hrVal = document.getElementById('hr_val');
        const respVal = document.getElementById('resp_val');
        const tempVal = document.getElementById('temp_val');
        
        if (bpVal) bpVal.innerText = data.blood_pressure || "120/80";
        if (hrVal) hrVal.innerText = data.heart_rate || "72";
        if (respVal) respVal.innerText = data.respiration || "16";
        if (tempVal) tempVal.innerText = data.temperature || "36.8";
        
        // FIXED FORCING: Bypasses any layout display conflicts to make your boxes visible instantly
        layoutGrid.style.setProperty('display', 'grid', 'important');
    }
};

window.renderPatientAppointmentsHistory = function(data) {
    const tray = document.getElementById('patientAppointmentsContainer');
    if (!tray) return;

    if (!data.appointments_list || data.appointments_list.length === 0) {
        tray.innerHTML = '<p style="color:#45546b; font-style:italic; font-size:13px; text-align:center; padding:10px;">No custom consultation history found.</p>';
        return;
    }

    tray.innerHTML = '';
    data.appointments_list.forEach(app => {
        let stateBorder = app.app_status === 'Accepted' ? '#00a63e' : (app.app_status === 'Denied' ? '#ff5757' : '#eab308');
        const elementRow = document.createElement('div');
        elementRow.className = 'appointment';
        elementRow.style.borderLeft = `4px solid ${stateBorder}`;
        
        elementRow.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                <div>
                    <h4 style="margin:0; font-size:14px; font-weight:bold; color:#1e293b;">${app.app_doctor_name}</h4>
                    <p style="margin:4px 0 0 0; font-size:12px; color:#64748b;"><i class="fa-solid fa-clock" style="margin-right:4px;"></i>${app.app_date} at ${app.app_time}</p>
                </div>
                <span style="font-size:11px; font-weight:bold; text-transform:uppercase; color:${stateBorder};">${app.app_status}</span>
            </div>
        `;
        tray.appendChild(elementRow);
    });
};

window.renderPatientAppointmentsSummary = window.renderPatientAppointmentsHistory;

// INTERACTIVE CLICK ANCHOR ACTION LISTENERS
document.getElementById('acceptVitalsBtn').addEventListener('click', () => { processVitalsConfirmation('Accepted'); });
document.getElementById('rejectVitalsBtn').addEventListener('click', () => { processVitalsConfirmation('Rejected'); });

function processVitalsConfirmation(decision) {
    const fd = new FormData();
    fd.append('status', decision);
    
    fetch('api/update_vitals_status.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert(`Vitals successfully marked as ${decision}!`);
            if (typeof fetchPatientIdentitySession === 'function') {
                fetchPatientIdentitySession();
            } else if (typeof fetchPatientDashboardData === 'function') {
                fetchPatientDashboardData();
            }
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error("Vitals confirmation error:", err));
}
