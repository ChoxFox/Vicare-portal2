// =========================================================================
// VICARE TELEHEALTH SUITE - MULTI-CHANNEL LINK INTERFACE CONTROLLER
// =========================================================================

window.activeSelectedDoctorContact = "";
window.activeSelectedDoctorName = "";
window.activeCallTimerTracker = null;
window.activeSelectedRecipientHandle = "";
window.chatPollingIntervalTracker = null;
window.activeSelectedDoctorImage = ""; 

// Inside assets/js/telehealth-chat.js -> PART 1 -> openDirectDoctorChat block

window.openDirectDoctorChat = function(doctorEmail, doctorName, doctorSpecialty, doctorImage) {
    // FORCE GLOBAL ASSIGNMENT BINDINGS
    window.activeSelectedDoctorContact = doctorEmail;
    window.activeSelectedRecipientHandle = doctorEmail; // Sync active text chat channel target
    window.activeSelectedDoctorName = doctorName;
    window.activeSelectedDoctorImage = doctorImage; 

    const nameLabel = document.getElementById('pop_doc_name');
    const specLabel = document.getElementById('pop_doc_specialty');
    const avatarImg = document.getElementById('pop_doc_image');
    
    // HIDDEN SYNCHRONIZER: Explicitly forces value storage directly inside your dashboard html form layers
    const hiddenInputFormTrack = document.getElementById('chatTargetRecipientInput');
    if (hiddenInputFormTrack) {
        hiddenInputFormTrack.value = doctorEmail;
    }

    if (nameLabel) nameLabel.innerText = doctorName;
    if (specLabel) specLabel.innerText = doctorSpecialty || "General Consultant";
    
    if (avatarImg) {
        let cleanImg = doctorImage ? doctorImage.trim() : '';
        if (cleanImg === '149071.png' || cleanImg === '') {
            avatarImg.src = "assets/images/149071.png";
        } else {
            avatarImg.src = cleanImg.includes('uploads/') ? cleanImg : "uploads/" + cleanImg;
        }
    }

    const overlayHub = document.getElementById('doctorTelehealthModalOverlay');
    if (overlayHub) overlayHub.style.display = 'flex';
};


// ====== CHOSEN ACTION CHANNELS DISPATCH ROUTERS ======
window.triggerTelehealthAction = function(actionType) {
    const overlayHub = document.getElementById('doctorTelehealthModalOverlay');
    if (overlayHub) overlayHub.style.display = 'none';

    if (actionType === 'text') {
        toggleTelehealthChatModal(true);
        if (typeof initiateActiveConversationThread === 'function') {
            initiateActiveConversationThread(window.activeSelectedDoctorContact, window.activeSelectedDoctorName);
        }
    } else if (actionType === 'video') {
        launchSimulatedTelehealthCallTunnel("Active Video Consultation Session", "fa-video");
    } else if (actionType === 'voice') {
        launchSimulatedTelehealthCallTunnel("Secured Audio Voice Consultation", "fa-phone-volume");
    }
};
function launchSimulatedTelehealthCallTunnel(callTypeTitle, vectorClassName) {
    const callModal = document.getElementById('telehealthActiveCallOverlayModal');
    const typeLabel = document.getElementById('activeCallTypeLabel');
    const nameLabel = document.getElementById('activeCallPartnerNameLabel');
    const iconTag   = document.getElementById('activeCallLogoIcon');
    const timerText = document.getElementById('activeCallTimerDisplay');

    if (!callModal) return;

    if (typeLabel) typeLabel.innerText = callTypeTitle;
    if (nameLabel) nameLabel.innerText = window.activeSelectedDoctorName;
    if (iconTag) iconTag.className = `fa-solid ${vectorClassName}`;

    callModal.style.display = 'flex';
    
    let secondsCounter = 0;
    if (timerText) timerText.innerText = "00:00";
    
    clearInterval(window.activeCallTimerTracker);
    window.activeCallTimerTracker = setInterval(() => {
        secondsCounter++;
        let mins = Math.floor(secondsCounter / 60).toString().padStart(2, '0');
        let secs = (secondsCounter % 60).toString().padStart(2, '0');
        if (timerText) timerText.innerText = `${mins}:${secs}`;
    }, 1000);
}

function toggleTelehealthChatModal(visibilityState) {
    const modal = document.getElementById('telehealthChatOverlayModal');
    if (!modal) return;
    modal.style.display = visibilityState ? 'flex' : 'none';
    if (visibilityState) {
        loadChatUserDirectory();
    } else {
        clearInterval(window.chatPollingIntervalTracker);
    }
}

function loadChatUserDirectory() {
    const tray = document.getElementById('chatUserDirectoryTray');
    if (!tray) return;

    // FIXED RELATIVE PATHING: Overrides browser character folder space URL traps perfectly [INDEX]
    fetch('./api/get_chat_users.php?t=' + new Date().getTime())
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            tray.innerHTML = '';
            if (!data.users || data.users.length === 0) {
                tray.innerHTML = '<p style="color:#94a3b8; font-style:italic; font-size:12px; text-align:center; padding-top:20px;">No profiles found.</p>';
                return;
            }
            data.users.forEach(user => {
                const row = document.createElement('div');
                row.style.padding = '12px';
                row.style.background = window.activeSelectedRecipientHandle === user.contact ? '#e2e8f0' : '#ffffff';
                row.style.borderRadius = '8px'; row.style.cursor = 'pointer'; row.style.border = '1px solid #e2e8f0';
                row.style.display = 'flex'; row.style.alignItems = 'center'; row.style.gap = '10px';
                
                let avatar = user.img ? user.img.trim() : '';
                avatar = avatar !== '' ? (avatar.includes('uploads/') ? avatar : 'uploads/' + avatar) : 'assets/images/149071.png';

                row.innerHTML = `
                    <img src="${avatar}" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid #cbd5e1;">
                    <div style="flex:1; min-width:0;">
                        <h4 style="margin:0; font-size:13px; font-weight:bold; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${user.name}</h4>
                        <p style="margin:2px 0 0 0; font-size:11px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${user.subtitle || user.contact}</p>
                    </div>
                `;
                row.addEventListener('click', () => { initiateActiveConversationThread(user.contact, user.name); });
                tray.appendChild(row);
            });
        }
    });
}
function initiateActiveConversationThread(recipientContact, recipientName) {
    window.activeSelectedRecipientHandle = recipientContact;
    
    const activeForm = document.querySelector('form:has(#chatMessageTextInput)') || document.getElementById('chatMessageComposerForm');
    if (activeForm) {
        const targetInput = activeForm.querySelector('#chatTargetRecipientInput') || document.getElementById('chatTargetRecipientInput');
        const textInput = activeForm.querySelector('#chatMessageTextInput') || document.getElementById('chatMessageTextInput');
        const sendBtn = activeForm.querySelector('#dispatchChatMessageBtn') || document.getElementById('dispatchChatMessageBtn');
        
        if (targetInput) targetInput.value = recipientContact;
        if (textInput) {
            textInput.removeAttribute('disabled');
            textInput.placeholder = `Type a message...`;
        }
        if (sendBtn) sendBtn.removeAttribute('disabled');
    }
    
    const partnerLabel = document.getElementById('activeChatPartnerNameLabel');
    if (partnerLabel) partnerLabel.innerText = recipientName;
    
    const avatarWrapper = document.getElementById('chatPartnerAvatarWrapper');
    if (avatarWrapper) {
        let cleanImg = window.activeSelectedDoctorImage ? window.activeSelectedDoctorImage.trim() : '';
        
        if (cleanImg !== '' && cleanImg !== '149071.png' && cleanImg !== 'undefined' && cleanImg !== 'null') {
            let fullPath = cleanImg.includes('uploads/') ? cleanImg : "uploads/" + cleanImg;
            avatarWrapper.innerHTML = `<img src="${fullPath}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
            avatarWrapper.style.background = "transparent"; 
        } else {
            let nameClean = recipientName.replace("Dr. ", "");
            let nameParts = nameClean.split(" ");
            let initials = nameParts[0] ? nameParts[0].charAt(0) : "P";
            if (nameParts[1]) initials += nameParts[1].charAt(0);
            initials = initials.substring(0, 2).toUpperCase();
            
            avatarWrapper.innerHTML = `<span id="chatPartnerInitialsLabel">${initials || "PT"}</span>`;
            avatarWrapper.style.background = "#3b82f6"; 
        }
    }

    clearInterval(window.chatPollingIntervalTracker);
    pollActiveConversationLogs();
    window.chatPollingIntervalTracker = setInterval(pollActiveConversationLogs, 3000);
}

// Inside assets/js/telehealth-chat.js -> PART 4 -> Replace the log loop and submission handler completely

// Inside assets/js/telehealth-chat.js -> PART 3 -> Overwrite this function completely

function pollActiveConversationLogs() {
    if (!window.activeSelectedRecipientHandle) return;
    const tray = document.getElementById('chatConversationLogTray');
    if (!tray) return;

    // 1. AUTO-DETECT USER INTERFACE DESK
    const welcomeHeader = document.getElementById('welcomeNameDisplay') || document.querySelector('.topbar h2');
    const welcomeText = welcomeHeader ? welcomeHeader.innerText.trim().toLowerCase() : "";
    const isDoctorSide = welcomeText.includes("doctor");

    // 2. DYNAMICALLY CAPTURE ACTIVE USER IDENTIFIER STRING
    // Patient dashboard uses their own phone/email handle; Doctor dashboard sets theirs dynamically
    const myActiveIdentifier = isDoctorSide ? "0771810571" : document.getElementById('chatTargetRecipientInput').form ? "" : "";

    fetch(`./api/fetch_chat_logs.php?recipient=${encodeURIComponent(window.activeSelectedRecipientHandle)}&t=${new Date().getTime()}`)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const oldScrollHeight = tray.scrollHeight;
            const wasAtBottom = tray.scrollTop + tray.clientHeight >= tray.scrollHeight - 20;

            tray.innerHTML = '';
            data.logs.forEach(msg => {
                const wrap = document.createElement('div');
                wrap.style.width = '100%'; 
                wrap.style.display = 'flex';
                
                // ====== DEFLEXED DIRECTION BALANCE INTERLOCK ======
                // If on doctor side, a message is "me" only if it was NOT flagged as Patient sent.
                let isBubbleMine = false;
                if (isDoctorSide) {
                    // Doctor authored messages map to the right; Patient sent text drops left [INDEX]
                    isBubbleMine = !msg.is_me; 
                } else {
                    // Patient dashboard positions their own inputs right [INDEX]
                    isBubbleMine = msg.is_me;
                }

                wrap.style.justifyContent = isBubbleMine ? 'flex-end' : 'flex-start';

                const bubble = document.createElement('div');
                bubble.style.padding = '16px 20px'; 
                bubble.style.borderRadius = '12px'; 
                bubble.style.maxWidth = '80%';
                bubble.style.fontSize = '14px'; 
                bubble.style.lineHeight = '1.5';
                
                if (isBubbleMine) {
                    // Symmetrical Sent bubble style layout maps cleanly right [INDEX]
                    bubble.style.background = '#4f46e5'; 
                    bubble.style.color = '#ffffff'; 
                    bubble.style.borderTopRightRadius = '2px';
                } else {
                    // Symmetrical Received bubble style layout maps cleanly left [INDEX]
                    bubble.style.background = '#ffffff'; 
                    bubble.style.color = '#1e293b'; 
                    bubble.style.borderTopLeftRadius = '2px';
                    bubble.style.border = '1px solid #e2e8f0';
                }

                bubble.innerText = msg.text;
                wrap.appendChild(bubble); 
                tray.appendChild(wrap);
            });

            if (wasAtBottom || oldScrollHeight === 0) tray.scrollTop = tray.scrollHeight;
        }
    })
    .catch(err => console.error("Realtime conversation thread stream drop exception:", err));
}

// ====== FIXED FORM COMPOSER SUBMIT INTERCEPTOR MATRIX ======
document.addEventListener('submit', function(e) {
    if (e.target && (e.target.id === 'chatMessageComposerForm' || e.target.querySelector('#chatMessageTextInput'))) {
        e.preventDefault();
        
        const currentForm = e.target;
        const inputField = currentForm.querySelector('#chatMessageTextInput');
        if (!inputField || !inputField.value.trim() || !window.activeSelectedRecipientHandle) return;

        // AUTO-DETECT TYPE: Reads elements to force accurate type variables directly into your SQL cells! [INDEX]
        const isDocSide = document.getElementById('chatNotificationBadge') !== null;
        const enforcedType = isDocSide ? 'Doctor' : 'Patient';

        const payloadData = new FormData();
        payloadData.append('recipient', window.activeSelectedRecipientHandle);
        payloadData.append('message', inputField.value.trim());
        payloadData.append('forced_type', enforcedType); // FIXED: Forces the script to know exactly who clicked submit [INDEX]

        fetch('./api/send_chat_message.php', {
            method: 'POST',
            body: payloadData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                inputField.value = '';
                pollActiveConversationLogs(); 
                if (typeof pollDoctorUnreadChatBadges === 'function') pollDoctorUnreadChatBadges();
            }
        })
        .catch(err => console.error("Communication send network fault:", err));
    }
});


// =========================================================================
// ====== FIXED PATH MODULE: UNREAD BADGE TELEMETRY LONG-POLLING ENGINE ======
// =========================================================================
function pollDoctorUnreadChatBadges() {
    const badge = document.getElementById('chatNotificationBadge');
    const tray = document.getElementById('unreadChatsInboxContainerTray');
    if (!badge) return; 

    // FIXED RELATIVE PATHING: Adding './api/' bypasses Apache directory traps flawlessly [INDEX]
    fetch('./api/get_unread_chats.php?t=' + new Date().getTime())
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.total_unread > 0) {
                badge.innerText = data.total_unread;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }

            const unreadModal = document.getElementById('doctorUnreadChatsOverlayModal');
            if (tray && unreadModal && unreadModal.style.display === 'flex') {
                tray.innerHTML = '';
                if (!data.conversations || data.conversations.length === 0) {
                    tray.innerHTML = '<p style="color:#64748b; font-style:italic; font-size:13px; text-align:center; padding:20px 0;">No unread messages found.</p>';
                    return;
                }
                data.conversations.forEach(chat => {
                    const row = document.createElement('div');
                    row.style.padding = '12px'; row.style.background = '#f8fafc'; row.style.borderRadius = '10px';
                    row.style.cursor = 'pointer'; row.style.border = '1px solid #e2e8f0';
                    row.style.display = 'flex'; row.style.alignItems = 'center'; row.style.gap = '12px';

                    let avatar = chat.img ? chat.img.trim() : '';
                    avatar = avatar !== '' ? (avatar.includes('uploads/') ? avatar : 'uploads/' + avatar) : 'assets/images/149071.png';

                    row.innerHTML = `
                        <img src="${avatar}" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid #cbd5e1;">
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; justify-content:space-between; align-items:center;"><h4 style="margin:0; font-size:13px; font-weight:bold; color:#1e293b;">${chat.name}</h4><span style="font-size:10px; color:#94a3b8;">${chat.time}</span></div>
                            <p style="margin:4px 0 0 0; font-size:12px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${chat.msg}</p>
                        </div>
                    `;
                    row.addEventListener('click', () => {
                        if (unreadModal) unreadModal.style.display = 'none';
                        toggleTelehealthChatModal(true);
                        initiateActiveConversationThread(chat.contact, chat.name);
                    });
                    tray.appendChild(row);
                });
            }
        }
    })
    .catch(err => console.error("Unread loop network drop:", err));
}

document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.getElementById('topbarChatTriggerBtn');
    const closeBtn = document.getElementById('closeChatModalBtn');
    const closeUnreadBtn = document.getElementById('closeUnreadModalBtn');
    const closeHub = document.getElementById('closeTelehealthModalBtn') || document.getElementById('closeCommHubModalBtn');
    const terminateBtn = document.getElementById('terminateActiveCallBtn');
    
    if (trigger) {
        trigger.addEventListener('click', () => {
            const badge = document.getElementById('chatNotificationBadge');
            const unreadModal = document.getElementById('doctorUnreadChatsOverlayModal');
            if (badge && unreadModal) {
                unreadModal.style.display = 'flex';
                pollDoctorUnreadChatBadges();
            } else {
                toggleTelehealthChatModal(true);
            }
        });
    }
    if (closeBtn) closeBtn.addEventListener('click', () => toggleTelehealthChatModal(false));
    if (closeUnreadBtn) closeUnreadBtn.addEventListener('click', () => { document.getElementById('doctorUnreadChatsOverlayModal').style.display = 'none'; });
    if (closeHub) {
        closeHub.addEventListener('click', () => {
            const overlayHub = document.getElementById('doctorTelehealthModalOverlay') || document.getElementById('clinicianCommHubOverlayModal');
            if (overlayHub) overlayHub.style.display = 'none';
        });
    }
    if (terminateBtn) {
        terminateBtn.addEventListener('click', () => {
            clearInterval(window.activeCallTimerTracker);
            document.getElementById('telehealthActiveCallOverlayModal').style.display = 'none';
        });
    }

    pollDoctorUnreadChatBadges();
    setInterval(pollDoctorUnreadChatBadges, 4000);
});
