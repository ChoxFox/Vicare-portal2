// =========================================================================
// VICARE MODULAR LAYER: DOCTOR DASHBOARD INTERACTIVE ACTIONS SHEET 
// =========================================================================

// Event listener to dynamically construct medication lists before compiling the PDF
document.getElementById('addMedicine').addEventListener('click', () => {
    const medName = document.getElementById('current_med_name').value.trim();
    const medDose = document.getElementById('current_med_dosage').value.trim();
    const medFreq = document.getElementById('current_med_freq').value.trim();
    const medDur  = document.getElementById('current_med_duration').value.trim();

    if (!medName || !medDose || !medFreq || !medDur) {
        alert("Please complete all medication detail blocks before appending.");
        return;
    }

    // Push recipe block onto your master global memory index arrays
    window.accumulatedMedications.push({ name: medName, dose: medDose, freq: medFreq, dur: medDur });
    
    // Reset field elements to receive next entries smoothly
    document.getElementById('current_med_name').value = '';
    document.getElementById('current_med_dosage').value = '';
    document.getElementById('current_med_freq').value = '';
    document.getElementById('current_med_duration').value = '';

    alert(`Successfully added ${medName}! Total items: ${window.accumulatedMedications.length}`);
});

// Triggers modal previews programmatically before saving rows into MySQL database
document.getElementById('triggerPreviewBtn').addEventListener('click', function() {
    const pName = document.getElementById('p_name_input').value.trim();
    const pContact = document.getElementById('p_contact_input').value.trim();
    const pDiagnosis = document.getElementById('p_diagnosis_input').value.trim();

    if (!pName || !pContact || !pDiagnosis) {
        alert("Please ensure patient identifying profiles and diagnoses are complete.");
        return;
    }

    if (window.accumulatedMedications.length === 0) {
        alert("Please append at least one medication record into your recipe list.");
        return;
    }

    // Map content arrays instantly onto glassmorphic screen modals
    document.getElementById('view_p_name').innerText = pName;
    document.getElementById('view_p_contact').innerText = pContact;
    document.getElementById('view_p_diagnosis').innerText = pDiagnosis;

    if (typeof window.renderPreviewMedsTray === 'function') {
        window.renderPreviewMedsTray();
    }

    // Display preview confirmation dashboard wrapper block cleanly
    document.getElementById('prescriptionPreviewOverlay').style.display = 'flex';
});

// Close helper trigger event link
document.getElementById('closePreviewOverlayBtn').addEventListener('click', () => {
    document.getElementById('prescriptionPreviewOverlay').style.display = 'none';
});
