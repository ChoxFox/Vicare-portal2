// =========================================================================
// VICARE MODULAR LAYER: MULTI-MEDICINE PDF COMPILER & PREVIEW ENGAGEMENT
// =========================================================================

// Initialize globally managed state tracking array matrix
window.accumulatedMedications = [];

// Handle dynamic medication accumulation lists on main workspace layout card
const addMedicineBtn = document.getElementById('addMedicine');
if (addMedicineBtn) {
    addMedicineBtn.addEventListener('click', () => {
        const medNameEl = document.getElementById('current_med_name');
        const medDoseEl = document.getElementById('current_med_dosage');
        const medFreqEl = document.getElementById('current_med_freq');
        const medDurEl  = document.getElementById('current_med_duration');

        if (!medNameEl || !medDoseEl || !medFreqEl || !medDurEl) {
            alert("Medication input element structural targets missing from doctor-dashboard.html");
            return;
        }

        const medName = medNameEl.value.trim();
        const medDose = medDoseEl.value.trim();
        const medFreq = medFreqEl.value.trim();
        const medDur  = medDurEl.value.trim();

        if (!medName || !medDose || !medFreq || !medDur) {
            alert("Please complete all fields for this specific medication item.");
            return;
        }

        // Push item details object cleanly onto memory stack channels
        window.accumulatedMedications.push({ name: medName, dose: medDose, freq: medFreq, dur: medDur });
        
        addMedicineBtn.innerText = `+ Add Medicine (${window.accumulatedMedications.length})`;
        addMedicineBtn.style.backgroundColor = "#00a63e"; 
        addMedicineBtn.style.color = "#ffffff";

        if (typeof window.renderPreviewMedsTray === 'function') {
            window.renderPreviewMedsTray();
        }

        // Clear local workspace fields back to placeholder visibility state
        medNameEl.value = '';
        medDoseEl.value = '';
        medFreqEl.value = '';
        medDurEl.value = '';
        
        alert(`Successfully added ${medName} to prescription build stack!`);
    });
}

// Render medications inside the glassmorphic confirmation panel overlay list
window.renderPreviewMedsTray = function() {
    const list = document.getElementById('previewMedsTrayList');
    if (!list) return; 
    list.innerHTML = '';
    window.accumulatedMedications.forEach(med => {
        const p = document.createElement('p');
        p.style.margin = '4px 0'; 
        p.style.background = '#f1f5f9'; 
        p.style.padding = '8px'; 
        p.style.borderRadius = '5px';
        p.style.fontSize = '13px';
        p.style.color = '#334155';
        p.innerHTML = `• <strong>${med.name}</strong> (${med.dose}) - ${med.freq} for ${med.dur}`;
        list.appendChild(p);
    });
};

// ====== INTERCONNECT BRIDGE: POPULATES & REVEALS PREVIEW VISIBILITY MODAL ======
// FIXED BINDINGS: Multi-barrel dynamic fallbacks automatically look up correct form element targets [INDEX]
const previewTriggerBtn = document.getElementById('triggerPreviewBtn') || document.getElementById('previewBtn');
if (previewTriggerBtn) {
    previewTriggerBtn.addEventListener('click', function() {
        const nameEl = document.getElementById('patient_name') || document.getElementById('p_name_input') || document.querySelector('input[name="patient_name"]');
        const contactEl = document.getElementById('patient_contact') || document.getElementById('p_contact_input') || document.querySelector('input[name="patient_contact"]');
        const diagnosisEl = document.getElementById('diagnosis') || document.getElementById('p_diagnosis_input') || document.querySelector('textarea[name="diagnosis"]') || document.querySelector('input[name="diagnosis"]');

        if (!nameEl || !contactEl || !diagnosisEl) {
            alert("Critical layout exception: Cannot bind form fields. Check your input tag elements layout.");
            return;
        }

        const pName = nameEl.value.trim();
        const pContact = contactEl.value.trim();
        const pDiagnosis = diagnosisEl.value.trim();

        if (!pName || !pContact || !pDiagnosis) {
            alert("Please complete Patient Name, Contact handle, and Diagnosis form fields first.");
            return;
        }
        if (window.accumulatedMedications.length === 0) {
            alert("Please append at least one medication item before initializing the layout preview document template.");
            return;
        }

        // Bind real-time layout rendering labels safely natively inside text layers
        const viewName = document.getElementById('view_p_name');
        const viewContact = document.getElementById('view_p_contact');
        const viewDiagnosis = document.getElementById('view_p_diagnosis');

        if (viewName) viewName.innerText = pName;
        if (viewContact) viewContact.innerText = pContact;
        if (viewDiagnosis) viewDiagnosis.innerText = pDiagnosis;

        const overlayCard = document.getElementById('prescriptionPreviewOverlay');
        if (overlayCard) overlayCard.style.display = 'flex';
    });
}

const closePreviewOverlayBtn = document.getElementById('closePreviewOverlayBtn') || document.getElementById('closePreviewBtn');
if (closePreviewOverlayBtn) {
    closePreviewOverlayBtn.addEventListener('click', function() {
        const overlayCard = document.getElementById('prescriptionPreviewOverlay');
        if (overlayCard) overlayCard.style.display = 'none';
    });
}

// CRITICAL ACTION LISTENERS DISPATCH PIPELINE EXECUTION HUB
const finalizeBtn = document.getElementById('finalizePushPrescriptionBtn');
if (finalizeBtn) {
    finalizeBtn.addEventListener('click', function() {
        const formElement = document.getElementById('prescriptionSubmissionForm') || document.querySelector('.card.prescription-card form') || document.querySelector('form');
        if (!formElement) {
            alert("Form wrapper prescriptionSubmissionForm container not found inside your doctor HTML dashboard!");
            return;
        }

        const formData = new FormData(formElement);

        // Bypasses local HTML limitations and injects payload items into submission bundle [INDEX]
        window.accumulatedMedications.forEach((med, idx) => {
            formData.append(`meds[${idx}][name]`, med.name);
            formData.append(`meds[${idx}][dose]`, med.dose);
            formData.append(`meds[${idx}][freq]`, med.freq);
            formData.append(`meds[${idx}][dur]`, med.dur);
        });

        // Double-check: Make sure patient_name and patient_contact are in the form payload [INDEX]
        const nameEl = document.getElementById('patient_name') || document.querySelector('input[name="patient_name"]');
        const contactEl = document.getElementById('patient_contact') || document.querySelector('input[name="patient_contact"]');
        const diagnosisEl = document.getElementById('diagnosis') || document.querySelector('textarea[name="diagnosis"]');
        
        if (nameEl && !formData.has('patient_name')) formData.append('patient_name', nameEl.value.trim());
        if (contactEl && !formData.has('patient_contact')) formData.append('patient_contact', contactEl.value.trim());
        if (diagnosisEl && !formData.has('diagnosis')) formData.append('diagnosis', diagnosisEl.value.trim());

        fetch('api/send_prescription.php', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP Error Status: ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                const overlayCard = document.getElementById('prescriptionPreviewOverlay');
                if (overlayCard) overlayCard.style.display = 'none';
                
                formElement.reset();
                window.accumulatedMedications = [];
                window.renderPreviewMedsTray();
                
                if (addMedicineBtn) {
                    addMedicineBtn.innerText = "+ Add Medicine";
                    addMedicineBtn.style.backgroundColor = ""; 
                    addMedicineBtn.style.color = "";
                }
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error("Prescription dispatch error:", err);
            alert("🚨 Fetch Processing Interrupted. Check browser console network status layers.");
        });
    });
}
