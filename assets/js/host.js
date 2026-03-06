// Host Portal - JavaScript Functionality

// Initialize Lucide icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Add event listener for shelter settings form
    const settingsForm = document.getElementById('shelter-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', updateShelterSettings);
    }

    // Add event listener for stock editor form
    const stockForm = document.getElementById('stock-editor');
    if (stockForm) {
        stockForm.addEventListener('submit', updateShelterStock);
    }
});

/**
 * Switch between tabs
 */
function switchTab(panelId, button) {
    // Get all panels
    const panels = document.querySelectorAll('[id^="panel-"]');
    panels.forEach(panel => {
        panel.classList.remove('active');
    });

    // Get all buttons
    const buttons = document.querySelectorAll('.adm-tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected panel and highlight button
    document.getElementById(panelId)?.classList.add('active');
    button?.classList.add('active');
}

/**
 * Toggle stock editor
 */
function toggleStockEditor() {
    const editor = document.getElementById('stock-editor');
    const display = document.getElementById('stock-display');

    if (editor && display) {
        const isEditing = editor.style.display !== 'none';
        editor.style.display = isEditing ? 'none' : 'flex';
        display.style.display = isEditing ? 'flex' : 'none';
    }
}

/**
 * Toggle operational status
 */
function toggleOperationalStatus(checkbox, shelterId) {
    const isActive = checkbox.checked ? 1 : 0;
    
    fetch('index.php?route=toggle_shelter_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            shelter_id: shelterId,
            is_active: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(
                isActive ? 'Shelter is now ACTIVE' : 'Shelter is now INACTIVE',
                'success'
            );
            // Reload page to update UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Error updating status: ' + (data.message || 'Unknown error'), 'error');
            // Revert checkbox
            checkbox.checked = !checkbox.checked;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Connection error. Please try again.', 'error');
        // Revert checkbox
        checkbox.checked = !checkbox.checked;
    });
}

/**
 * Approve a shelter request
 */
function approveRequest(requestId, name, groupSize) {
    if (!confirm(`Approve request from ${name} for ${groupSize} people?`)) {
        return;
    }

    fetch('index.php?route=approve_request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            request_id: requestId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Request from ${name} approved!`, 'success');
            // Reload page to update UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Error approving request: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Connection error. Please try again.', 'error');
    });
}

/**
 * Decline a shelter request
 */
function declineRequest(requestId) {
    if (!confirm('Are you sure you want to decline this request?')) {
        return;
    }

    fetch('index.php?route=decline_request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            request_id: requestId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Request declined', 'success');
            // Reload page to update UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Error declining request: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Connection error. Please try again.', 'error');
    });
}

/**
 * Check in an evacuee
 */
function checkInEvacuee(requestId) {
    if (!confirm('Check in this evacuee?')) {
        return;
    }

    // For now, just show a message
    // You can implement actual check-in logic later
    showToast('Check-in feature coming soon!', 'info');
}

/**
 * Update shelter settings
 */
function updateShelterSettings(e) {
    e.preventDefault();

    const formData = {
        shelter_name: document.getElementById('shelter-name').value,
        max_capacity: parseInt(document.getElementById('max-capacity').value),
        contact_number: document.getElementById('contact-number').value,
        location: document.getElementById('location').value,
        amenities: Array.from(document.querySelectorAll('input[name="amenities[]"]:checked')).map(cb => cb.value)
    };

    const btn = e.target.querySelector('.btn-update');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> SAVING...';
    btn.disabled = true;
    if (typeof lucide !== 'undefined') lucide.createIcons();

    fetch('index.php?route=update_shelter_settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Settings updated successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Error updating settings: ' + (data.message || 'Unknown error'), 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Connection error. Please try again.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

/**
 * Update shelter stock
 */
function updateShelterStock(e) {
    e.preventDefault();

    const supplies = {
        water: {
            qty: parseInt(document.querySelector('input[name="water_qty"]').value) || 0,
            unit: document.querySelector('select[name="water_unit"]').value
        },
        food: {
            qty: parseInt(document.querySelector('input[name="food_qty"]').value) || 0,
            unit: document.querySelector('select[name="food_unit"]').value
        },
        medical: {
            qty: parseInt(document.querySelector('input[name="medical_qty"]').value) || 0,
            unit: document.querySelector('select[name="medical_unit"]').value
        },
        bedding: {
            qty: parseInt(document.querySelector('input[name="bedding_qty"]').value) || 0,
            unit: document.querySelector('select[name="bedding_unit"]').value
        }
    };

    const btn = e.target.querySelector('.btn-save-stock');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> SAVING...';
    btn.disabled = true;
    if (typeof lucide !== 'undefined') lucide.createIcons();

    fetch('index.php?route=update_shelter_stock', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ supplies })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Stock levels updated!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Error updating stock: ' + (data.message || 'Unknown error'), 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Connection error. Please try again.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    
    let icon = 'info';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'alert-circle';

    toast.innerHTML = `
        <i data-lucide="${icon}" style="width: 20px; height: 20px;"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(toast);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutDown 0.4s ease forwards';
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 4000);
}

// Add CSS for toast notifications and loading spinner
const style = document.createElement('style');
style.textContent = `
    .toast-notification {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: slideInUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        z-index: 1000;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        font-weight: 600;
        max-width: 400px;
    }

    .toast-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .toast-error {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .toast-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideOutDown {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(30px);
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    .spin {
        animation: spin 1s linear infinite;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        text-align: center;
        color: #94a3b8;
    }

    .empty-state p {
        margin-top: 1rem;
        font-size: 0.95rem;
    }

    .status-badge.inactive {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .status-badge.inactive .status-dot {
        background: #ef4444;
    }

    @media (max-width: 768px) {
        .toast-notification {
            bottom: 1rem;
            right: 1rem;
            max-width: 300px;
        }
    }
`;
document.head.appendChild(style);


function handleManualCheckIn(e) {
    e.preventDefault();
    
    const input = document.getElementById('manual-checkin-id');
    const checkInId = input.value.trim();
    
    if (!checkInId) {
        showCheckinMessage('Please enter a valid ID', 'error');
        return;
    }

    // Show loading state
    const btn = e.target.querySelector('.btn-manual-checkin');
    const originalHTML = btn.innerHTML;
    btn.classList.add('loading');
    btn.innerHTML = '<i data-lucide="loader-2" style="width: 16px; height: 16px;"></i> CHECKING IN...';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Send request to backend
    fetch('index.php?route=manual_checkin', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            checkin_id: checkInId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCheckinMessage(
                `Successfully checked in: ${data.name || 'Evacuee'}`, 
                'success'
            );
            input.value = ''; // Clear input
            
            // Reload page after 2 seconds to update counts
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showCheckinMessage(
                data.message || 'Check-in failed. Please verify the ID.', 
                'error'
            );
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCheckinMessage('Connection error. Please try again.', 'error');
    })
    .finally(() => {
        // Restore button state
        btn.classList.remove('loading');
        btn.innerHTML = originalHTML;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
}

/**
 * Show check-in success/error message
 */
function showCheckinMessage(message, type = 'info') {
    // Remove existing message if any
    const existing = document.querySelector('.checkin-message');
    if (existing) {
        existing.remove();
    }

    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `checkin-message ${type}`;
    
    const icon = type === 'success' ? 'check-circle' : 'alert-circle';
    
    messageDiv.innerHTML = `
        <i data-lucide="${icon}" style="width: 20px; height: 20px;"></i>
        <span>${message}</span>
    `;

    // Insert after form
    const form = document.querySelector('.manual-entry-form');
    form.parentNode.insertBefore(messageDiv, form.nextSibling);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Auto remove after 5 seconds
    setTimeout(() => {
        messageDiv.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => {
            messageDiv.remove();
        }, 300);
    }, 5000);
}

// Add slideOut animation
style.textContent += `
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);

/* =====================================================
   INLINE JAVASCRIPT FROM PHP (Modal & Relinquish Functions)
   ===================================================== */

// Read shelter ID from <meta name="shelter-id"> set in the PHP view (no inline script needed)
const SHELTER_ID = parseInt(document.querySelector('meta[name="shelter-id"]')?.content || '0', 10);

/**
 * Relinquish Host Status Functions
 */
function confirmRelinquishStatus() {
    document.getElementById('relinquishModal').style.display = 'flex';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeRelinquishModal() {
    document.getElementById('relinquishModal').style.display = 'none';
}

async function executeRelinquish(btn) {
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> PROCESSING...';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    try {
        const response = await fetch('index.php?route=relinquish-host-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = data.redirect || 'index.php?route=evacuee_portal';
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

// Close modal on outside click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('relinquishModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeRelinquishModal();
        }
    });
});
// =============================================
// QR SCANNER MODAL
// =============================================

let html5QrCodeInstance = null;
let qrScannerActive = false;

/**
 * Open QR scanner modal and start camera
 */
function openQrScannerModal() {
    const modal = document.getElementById('qrScannerModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Reset result panel
    const resultPanel = document.getElementById('qrResultPanel');
    if (resultPanel) resultPanel.style.display = 'none';

    // Start scanner after a short delay to let the modal render
    setTimeout(() => startQrScanner(), 150);
}

/**
 * Close QR scanner modal and stop camera
 */
function closeQrScannerModal() {
    stopQrScanner();
    const modal = document.getElementById('qrScannerModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

/**
 * Start the QR scanner
 */
function startQrScanner() {
    const readerEl = document.getElementById('qrReader');
    if (!readerEl) return;

    // Clean up any previous instance
    if (html5QrCodeInstance) {
        html5QrCodeInstance.stop().catch(() => {}).finally(() => {
            html5QrCodeInstance = null;
            initQrScanner();
        });
    } else {
        initQrScanner();
    }
}

function initQrScanner() {
    html5QrCodeInstance = new Html5Qrcode('qrReader');
    qrScannerActive = true;

    const config = { fps: 10, qrbox: { width: 240, height: 240 } };

    html5QrCodeInstance.start(
        { facingMode: 'environment' },
        config,
        onQrScanSuccess,
        () => {} // suppress per-frame errors
    ).catch(err => {
        console.error('QR Scanner start error:', err);
        const readerEl = document.getElementById('qrReader');
        if (readerEl) {
            readerEl.innerHTML = `
                <div style="padding: 2rem; text-align: center; color: #94a3b8; background: #1e293b; border-radius: 12px;">
                    <i data-lucide="camera-off" style="width: 48px; height: 48px; margin-bottom: 1rem; color: #64748b;"></i>
                    <p style="font-size: 0.9rem;">Camera access denied or unavailable.<br>Use the manual code entry instead.</p>
                </div>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    });
}

/**
 * Stop the QR scanner
 */
function stopQrScanner() {
    qrScannerActive = false;
    if (html5QrCodeInstance) {
        html5QrCodeInstance.stop().catch(() => {});
        html5QrCodeInstance = null;
    }
}

/**
 * Called when QR code is successfully scanned
 */
async function onQrScanSuccess(decodedText) {
    if (!qrScannerActive) return;
    qrScannerActive = false; // prevent duplicate scans

    // Pause scanner
    if (html5QrCodeInstance) {
        html5QrCodeInstance.pause();
    }

    const code = decodedText.trim();

    // Show loading in result panel
    const resultPanel = document.getElementById('qrResultPanel');
    const resultContent = document.getElementById('qrResultContent');
    if (resultPanel && resultContent) {
        resultPanel.style.display = 'block';
        resultContent.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; color: #94a3b8; padding: 0.5rem;">
                <i data-lucide="loader-2" class="spin" style="width: 24px; height: 24px;"></i>
                <span style="font-size: 0.9rem;">Verifying code: <strong style="color: #2dd4bf;">${code}</strong></span>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // Validate it's a 6-digit code
    if (!/^\d{6}$/.test(code)) {
        showQrScanResult('invalid', null, 'Invalid QR code format. Expected a 6-digit code.');
        return;
    }

    // Verify against backend
    try {
        const response = await fetch('index.php?route=verify_approval_code', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ approval_code: code, shelter_id: currentShelterId })
        });
        const data = await response.json();

        if (data.success) {
            // Store evacuee data with code for check-in
            verifiedEvacueeData = data.evacuee;
            verifiedEvacueeData._code = code;
            showQrScanResult('valid', data.evacuee, null, code);
        } else {
            showQrScanResult('invalid', null, data.message || 'Invalid or expired QR code.');
        }
    } catch (err) {
        showQrScanResult('invalid', null, 'Network error. Please try again.');
    }
}

/**
 * Show scan result in the modal
 */
function showQrScanResult(type, evacuee, errorMessage, code) {
    const resultContent = document.getElementById('qrResultContent');
    if (!resultContent) return;

    if (type === 'valid') {
        resultContent.innerHTML = `
            <div>
                <div style="display: flex; justify-content: center; margin-bottom: 0.75rem;">
                    <div style="background: #064e3b; border-radius: 50%; padding: 0.75rem;">
                        <i data-lucide="check-circle" style="width: 40px; height: 40px; color: #2dd4bf;"></i>
                    </div>
                </div>
                <h4 style="color: #2dd4bf; margin: 0 0 0.5rem; font-size: 1rem;">Code Verified!</h4>
                <div style="background: #1a2744; border: 1px solid #2dd4bf44; border-radius: 8px; padding: 0.75rem; margin: 0.75rem 0; text-align: left;">
                    <p style="color: #e2e8f0; margin: 0.25rem 0; font-size: 0.875rem;"><strong style="color: #94a3b8;">Name:</strong> ${evacuee.name}</p>
                    <p style="color: #e2e8f0; margin: 0.25rem 0; font-size: 0.875rem;"><strong style="color: #94a3b8;">Group Size:</strong> ${evacuee.group_size} ${evacuee.group_size > 1 ? 'people' : 'person'}</p>
                    <p style="color: #e2e8f0; margin: 0.25rem 0; font-size: 0.875rem;"><strong style="color: #94a3b8;">Phone:</strong> ${evacuee.phone}</p>
                    ${evacuee.notes ? `<p style="color: #e2e8f0; margin: 0.25rem 0; font-size: 0.875rem;"><strong style="color: #94a3b8;">Notes:</strong> ${evacuee.notes}</p>` : ''}
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 1rem;">
                    <button onclick="resetQrScanner()" style="padding: 0.75rem; background: #334155; color: #e2e8f0; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.8rem;">
                        SCAN AGAIN
                    </button>
                    <button onclick="confirmQrCheckIn('${code}')" style="padding: 0.75rem; background: linear-gradient(135deg, #0d9488, #059669); color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.8rem;">
                        CONFIRM CHECK-IN
                    </button>
                </div>
            </div>
        `;
    } else {
        resultContent.innerHTML = `
            <div>
                <div style="display: flex; justify-content: center; margin-bottom: 0.75rem;">
                    <div style="background: #7f1d1d; border-radius: 50%; padding: 0.75rem;">
                        <i data-lucide="x-circle" style="width: 40px; height: 40px; color: #f87171;"></i>
                    </div>
                </div>
                <h4 style="color: #f87171; margin: 0 0 0.5rem;">Invalid QR Code</h4>
                <p style="color: #94a3b8; font-size: 0.875rem; margin: 0.5rem 0;">${errorMessage || 'Invalid or expired QR code.'}</p>
                <button onclick="resetQrScanner()" style="margin-top: 1rem; padding: 0.75rem 2rem; background: #334155; color: #e2e8f0; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    TRY AGAIN
                </button>
            </div>
        `;
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Reset scanner to scan again
 */
function resetQrScanner() {
    const resultPanel = document.getElementById('qrResultPanel');
    if (resultPanel) resultPanel.style.display = 'none';
    verifiedEvacueeData = null;
    qrScannerActive = true;
    if (html5QrCodeInstance) {
        html5QrCodeInstance.resume();
    }
}

/**
 * Confirm check-in from QR scanner
 */
async function confirmQrCheckIn(code) {
    const resultContent = document.getElementById('qrResultContent');
    if (resultContent) {
        resultContent.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; color: #94a3b8; padding: 1rem;">
                <i data-lucide="loader-2" class="spin" style="width: 24px; height: 24px;"></i>
                <span>Processing check-in...</span>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    try {
        const response = await fetch('index.php?route=process_checkin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ approval_code: code, shelter_id: currentShelterId })
        });
        const data = await response.json();

        if (data.success) {
            if (resultContent) {
                resultContent.innerHTML = `
                    <div>
                        <div style="display: flex; justify-content: center; margin-bottom: 0.75rem;">
                            <div style="background: #064e3b; border-radius: 50%; padding: 0.75rem;">
                                <i data-lucide="check-circle-2" style="width: 48px; height: 48px; color: #2dd4bf;"></i>
                            </div>
                        </div>
                        <h4 style="color: #2dd4bf; font-size: 1.25rem; margin: 0 0 0.5rem;">Check-In Complete!</h4>
                        <p style="color: #94a3b8; font-size: 0.9rem;">${data.evacuee ? data.evacuee.name : 'Evacuee'} has been successfully checked in.</p>
                        <p style="color: #64748b; font-size: 0.8rem; margin-top: 0.5rem;">Refreshing page...</p>
                    </div>
                `;
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
            stopQrScanner();
            setTimeout(() => {
                closeQrScannerModal();
                location.reload();
            }, 1800);
        } else {
            showQrScanResult('invalid', null, data.message || 'Check-in failed. Please try again.');
        }
    } catch (err) {
        showQrScanResult('invalid', null, 'Network error during check-in.');
    }
}

// Close QR modal on backdrop click
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('qrScannerModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeQrScannerModal();
    });
});

let currentShelterId = null;
let verifiedEvacueeData = null;

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get shelter ID from meta tag or PHP variable
    const shelterIdMeta = document.querySelector('meta[name="shelter-id"]');
    if (shelterIdMeta) {
        currentShelterId = shelterIdMeta.content;
    }
});

/**
 * Initiate check-in from Expecting tab
 * Switches to Check-In tab and pre-fills the approval code
 */
function initiateCheckIn(approvalCode) {
    // Switch to Check-In tab
    const checkinTab = document.querySelector('.adm-tab-btn.checkin');
    if (checkinTab) {
        switchTab('panel-checkin', checkinTab);
    }
    
    // Pre-fill the approval code
    const codeInput = document.getElementById('approval-code-input');
    if (codeInput) {
        codeInput.value = approvalCode;
        codeInput.focus();
        
        // Highlight the input briefly
        codeInput.style.animation = 'highlight-pulse 0.5s ease';
        setTimeout(() => {
            codeInput.style.animation = '';
        }, 500);
        
        // Automatically trigger verification
        setTimeout(() => {
            verifyApprovalCode();
        }, 300);
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Copy approval code to clipboard
 */
function copyCodeToClipboard(code, button) {
    // Copy to clipboard
    navigator.clipboard.writeText(code).then(() => {
        // Show success feedback
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i data-lucide="check" style="width: 14px; height: 14px;"></i>';
        button.style.color = '#10b981';
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Reset after 2 seconds
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.color = '';
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 2000);
        
        // Show tooltip
        showNotification('✓ Code copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showNotification('❌ Failed to copy code');
    });
}

/**
 * Verify approval code before check-in
 */
async function verifyApprovalCode() {
    const input = document.getElementById('approval-code-input');
    const code = input.value.trim();
    
    if (!code) {
        showVerificationError('Please enter an approval code');
        return;
    }
    
    if (!/^\d{6}$/.test(code)) {
        showVerificationError('Approval code must be exactly 6 digits');
        return;
    }
    
    // Show loading
    showVerificationLoading();
    
    try {
        const response = await fetch('index.php?route=verify_approval_code', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_code: code,
                shelter_id: currentShelterId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            verifiedEvacueeData = data.evacuee;
            showVerificationSuccess(data.evacuee);
        } else {
            showVerificationError(data.message);
            verifiedEvacueeData = null;
        }
    } catch (error) {
        console.error('Error:', error);
        showVerificationError('Network error. Please try again.');
        verifiedEvacueeData = null;
    }
}

/**
 * Handle approval code check-in form submission
 */
async function handleApprovalCodeCheckIn(event) {
    event.preventDefault();
    
    if (!verifiedEvacueeData) {
        alert('Please verify the approval code first');
        return;
    }
    
    const input = document.getElementById('approval-code-input');
    const code = input ? input.value.trim() : (verifiedEvacueeData._code || '');
    
    // Show loading
    const resultDiv = document.getElementById('verificationResult');
    resultDiv.innerHTML = `
        <div class="verification-loading">
            <i data-lucide="loader-2" class="spin"></i>
            <p>Processing check-in...</p>
        </div>
    `;
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    try {
        const response = await fetch('index.php?route=process_checkin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                approval_code: code,
                shelter_id: currentShelterId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showCheckInSuccess(data.evacuee);
            input.value = '';
            verifiedEvacueeData = null;
            
            // Refresh stats
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showVerificationError(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showVerificationError('Check-in failed. Please try again.');
    }
}

/**
 * Show verification loading state
 */
function showVerificationLoading() {
    const resultDiv = document.getElementById('verificationResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = `
        <div class="verification-loading">
            <i data-lucide="loader-2" class="spin"></i>
            <p>Verifying code...</p>
        </div>
    `;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Show verification success
 */
function showVerificationSuccess(evacuee) {
    const resultDiv = document.getElementById('verificationResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = `
        <div class="verification-success">
            <div class="verification-icon">
                <i data-lucide="check-circle" style="width: 48px; height: 48px; color: #10b981;"></i>
            </div>
            <h3 style="color: #065f46; margin: 0.75rem 0 0.5rem; font-size: 1.125rem;">Code Verified!</h3>
            <div class="evacuee-details">
                <p><strong>Name:</strong> ${evacuee.name}</p>
                <p><strong>Group Size:</strong> ${evacuee.group_size} ${evacuee.group_size > 1 ? 'people' : 'person'}</p>
                <p><strong>Phone:</strong> ${evacuee.phone}</p>
                ${evacuee.notes ? `<p><strong>Notes:</strong> ${evacuee.notes}</p>` : ''}
            </div>
            <button class="btn-confirm-checkin" onclick="handleApprovalCodeCheckIn(event)">
                <i data-lucide="user-check"></i>
                CONFIRM CHECK-IN
            </button>
        </div>
    `;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Show verification error
 */
function showVerificationError(message) {
    const resultDiv = document.getElementById('verificationResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = `
        <div class="verification-error">
            <i data-lucide="x-circle" style="width: 32px; height: 32px; color: #ef4444;"></i>
            <p>${message}</p>
        </div>
    `;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Show check-in success
 */
function showCheckInSuccess(evacuee) {
    const resultDiv = document.getElementById('verificationResult');
    resultDiv.innerHTML = `
        <div class="verification-success checkin-complete">
            <div class="verification-icon">
                <i data-lucide="check-circle-2" style="width: 64px; height: 64px; color: #10b981;"></i>
            </div>
            <h3 style="color: #065f46; margin: 0.75rem 0 0.5rem; font-size: 1.25rem;">Check-In Complete!</h3>
            <p style="color: #047857; font-size: 0.9375rem; margin: 0.5rem 0;">
                ${evacuee.name} has been successfully checked in.
            </p>
            <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.75rem;">
                Redirecting...
            </p>
        </div>
    `;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Load occupants list
 */
async function loadOccupants() {
    const listContainer = document.getElementById('occupantsList');
    
    // Show loading
    listContainer.innerHTML = `
        <div class="loading-state">
            <i data-lucide="loader-2" class="spin"></i>
            <p>Loading occupants...</p>
        </div>
    `;
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    try {
        const response = await fetch(`index.php?route=get_occupants&shelter_id=${currentShelterId}`);
        const data = await response.json();
        
        if (data.success) {
            displayOccupants(data.occupants, data.count);
        } else {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <i data-lucide="alert-circle" style="width: 48px; height: 48px; color: #ef4444;"></i>
                    <p>Failed to load occupants</p>
                </div>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    } catch (error) {
        console.error('Error:', error);
        listContainer.innerHTML = `
            <div class="empty-state">
                <i data-lucide="wifi-off" style="width: 48px; height: 48px; color: #ef4444;"></i>
                <p>Network error. Please try again.</p>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

/**
 * Display occupants in the list
 */
function displayOccupants(occupants, count) {
    const listContainer = document.getElementById('occupantsList');
    
    // Update summary
    document.getElementById('totalOccupants').textContent = occupants.length;
    document.getElementById('totalPeople').textContent = count.total_people || 0;
    document.getElementById('occupantCountBadge').textContent = occupants.length;
    
    if (occupants.length === 0) {
        listContainer.innerHTML = `
            <div class="empty-state">
                <i data-lucide="users" style="width: 48px; height: 48px; color: #cbd5e1;"></i>
                <p>No occupants currently checked in</p>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }
    
    let html = '';
    occupants.forEach(occupant => {
        const checkedInDate = new Date(occupant.checked_in_at);
        const timeAgo = getTimeAgo(checkedInDate);
        
        html += `
            <div class="occupant-card">
                <div class="occupant-info">
                    <div class="occupant-avatar">
                        <i data-lucide="user"></i>
                    </div>
                    <div class="occupant-details">
                        <h4 class="occupant-name">${occupant.first_name} ${occupant.last_name}</h4>
                        <div class="occupant-meta">
                            <span class="meta-item">
                                <i data-lucide="users"></i>
                                ${occupant.group_size} ${occupant.group_size > 1 ? 'people' : 'person'}
                            </span>
                            <span class="meta-item">
                                <i data-lucide="phone"></i>
                                ${occupant.phone_number}
                            </span>
                            <span class="meta-item">
                                <i data-lucide="clock"></i>
                                Checked in ${timeAgo}
                            </span>
                        </div>
                        <div class="occupant-code">
                            <i data-lucide="shield-check"></i>
                            <code>${occupant.approval_code}</code>
                        </div>
                        ${occupant.request_notes ? `
                            <div class="occupant-notes">
                                <i data-lucide="message-square"></i>
                                ${occupant.request_notes}
                            </div>
                        ` : ''}
                    </div>
                </div>
                <div class="occupant-actions">
                    <button class="btn-remove-occupant" onclick="confirmRemoveOccupant(${occupant.occupant_id}, '${occupant.first_name} ${occupant.last_name}')">
                        <i data-lucide="x-circle"></i>
                        Remove
                    </button>
                </div>
            </div>
        `;
    });
    
    listContainer.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Confirm remove occupant
 */
function confirmRemoveOccupant(occupantId, name) {
    if (!confirm(`Are you sure you want to remove ${name} from the shelter?\n\nThis will:\n• Free up ${1} space(s) in your shelter\n• Mark them as checked out\n• Update capacity automatically`)) {
        return;
    }
    
    removeOccupant(occupantId);
}

/**
 * Remove occupant
 */
async function removeOccupant(occupantId) {
    try {
        const response = await fetch('index.php?route=remove_occupant', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                occupant_id: occupantId,
                shelter_id: currentShelterId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            showNotification('Occupant removed successfully');
            
            // Reload occupants list
            loadOccupants();
            
            // Reload page to update stats
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to remove occupant. Please try again.');
    }
}

/**
 * Get time ago string
 */
function getTimeAgo(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins} min${diffMins !== 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
    return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
}

/**
 * Show notification
 */
function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        z-index: 10000;s
        font-weight: 600;
        animation: slideInRight 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
