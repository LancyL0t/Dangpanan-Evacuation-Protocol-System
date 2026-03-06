// Shelter Setup - JavaScript Functionality

// Track current step
let currentStep = 1;
const totalSteps = 3;

// Form data object to store all information
let formData = {
    step1: {},
    step2: {},
    step3: {}
};

// Initialize Lucide icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Add event listeners for form inputs to update review data
    updateReviewData();
});

/**
 * Navigate to the next step
 */
function nextStep() {
    if (currentStep < totalSteps) {
        currentStep++;
        updateStepDisplay();
        window.scrollTo(0, 0);
    }
}

/**
 * Navigate to the previous step
 */
function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepDisplay();
        window.scrollTo(0, 0);
    }
}

/**
 * Validate current step and proceed to next
 */
function validateAndNext(step) {
    if (validateStep(step)) {
        saveStepData(step);
        nextStep();
    } else {
        showToast('Please fill in all required fields', 'error');
    }
}

/**
 * Validate the current step
 */
function validateStep(step) {
    const form = document.getElementById(`form-step-${step}`);
    
    if (!form) return false;

    // Get all required inputs
    const requiredInputs = form.querySelectorAll('[required]');
    
    for (let input of requiredInputs) {
        if (!input.value.trim()) {
            input.focus();
            input.style.borderColor = 'var(--danger)';
            setTimeout(() => {
                input.style.borderColor = '';
            }, 2000);
            return false;
        }
    }

    return true;
}

/**
 * Save step data
 */
function saveStepData(step) {
    const form = document.getElementById(`form-step-${step}`);
    const formElements = form.querySelectorAll('input, select, textarea');
    
    const stepData = {};
    
    formElements.forEach(element => {
        if (element.type === 'checkbox') {
            stepData[element.name || element.id] = element.checked;
        } else if (element.type === 'radio') {
            if (element.checked) {
                stepData[element.name] = element.value;
            }
        } else {
            stepData[element.name || element.id] = element.value;
        }
    });

    formData[`step${step}`] = stepData;
}

/**
 * Update the step display
 */
function updateStepDisplay() {
    // Hide all steps
    document.querySelectorAll('.setup-step').forEach(step => {
        step.classList.remove('active');
    });

    // Show current step
    document.getElementById(`step-${currentStep}`).classList.add('active');

    // Update progress indicators
    updateProgressIndicators();

    // Update back button state
    const backBtn = document.querySelector('.btn-back');
    if (backBtn) {
        backBtn.disabled = currentStep === 1;
    }

    // Update review data
    updateReviewData();
}

/**
 * Update progress indicators
 */
function updateProgressIndicators() {
    const steps = document.querySelectorAll('.progress-step');
    const currentStepSpan = document.querySelector('.current-step');

    steps.forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');

        if (stepNum === currentStep) {
            step.classList.add('active');
        } else if (stepNum < currentStep) {
            step.classList.add('completed');
        }
    });

    if (currentStepSpan) {
        currentStepSpan.textContent = currentStep;
    }
}

/**
 * Update review section with form data
 */
function updateReviewData() {
    // Get shelter name from step 1
    const shelterNameInput = document.getElementById('shelter-name');
    const reviewName = document.getElementById('review-name');
    if (shelterNameInput && reviewName) {
        reviewName.textContent = shelterNameInput.value || '-';
    }

    // Get location from step 1
    const reviewLocation = document.getElementById('review-location');
    if (reviewLocation) {
        const cityInput = document.querySelector('input[placeholder="Bacolod City"]');
        const provinceInput = document.querySelector('input[placeholder="Negros Occidental"]');
        if (cityInput && provinceInput) {
            reviewLocation.textContent = `${cityInput.value}, ${provinceInput.value}` || '-';
        }
    }

    // Get capacity from step 2
    const maxCapacityInput = document.getElementById('max-capacity');
    const reviewCapacity = document.getElementById('review-capacity');
    if (maxCapacityInput && reviewCapacity) {
        reviewCapacity.textContent = (maxCapacityInput.value || '-') + ' Persons';
    }

    // Get phone from step 1
    const phoneInput = document.getElementById('contact-phone');
    const reviewPhone = document.getElementById('review-phone');
    if (phoneInput && reviewPhone) {
        reviewPhone.textContent = phoneInput.value || '-';
    }
}

/**
 * Complete the shelter setup
 */
/**
 * Complete the shelter setup - CONNECTED TO BACKEND
 */
function completeShelterSetup() {
    // 1. Validate Terms
    const agreeCheckbox = document.getElementById('agree-terms');
    if (!agreeCheckbox.checked) {
        showToast('Please agree to the terms before completing setup', 'error');
        return;
    }

    // 2. Save final step data into the global formData object
    saveStepData(3);
    
    // Add specific address fields from Step 1 inputs explicitly if they weren't caught by generic saver
    // (Ensure your inputs in PHP have name attributes or IDs matching these keys)
    formData.step1['city'] = document.querySelector('input[placeholder="Bacolod City"]').value;
    formData.step1['province'] = document.querySelector('input[placeholder="Negros Occidental"]').value;
    formData.step1['barangay'] = document.querySelector('input[placeholder="Mansilingan"]').value;
    formData.step1['street-address'] = document.querySelector('input[placeholder="123 Main Street"]').value;

    // 3. Show loading state
    const btn = document.querySelector('.btn-complete');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> SAVING...';
    btn.disabled = true;
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // 4. Send Data to PHP Controller
    fetch('index.php?route=save_shelter_setup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Shelter setup saved successfully!', 'success');
            
            // Show the "Success" UI Card
            showCompletionState();
        } else {
            showToast('Error: ' + (data.message || 'Could not save data'), 'error');
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
 * Show completion state
 */
function showCompletionState() {
    const setupWizard = document.querySelector('.setup-wizard-container');
    
    setupWizard.innerHTML = `
        <div class="completion-state">
            <div class="completion-card">
                <div class="completion-icon">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="40" cy="40" r="38" stroke="#10b981" stroke-width="4"/>
                        <path d="M25 42L35 52L55 32" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="completion-title">Setup Complete!</h2>
                <p class="completion-text">Your shelter has been successfully added to the DANGPANAN network. You can now start accepting evacuees and managing your shelter.</p>
                
                <div class="completion-next-steps">
                    <h3>Next Steps:</h3>
                    <ul>
                        <li>✓ Set up your shelter profile information</li>
                        <li>✓ Configure initial stock levels</li>
                        <li>✓ You're ready to accept evacuee requests</li>
                    </ul>
                </div>

                <div class="completion-actions">
                   <a href="index.php?route=host_portal" class="btn-go-dashboard">
                        <i data-lucide="arrow-right"></i> GO TO HOST DASHBOARD
                    </a>
                </div>
            </div>
        </div>
    `;

    lucide.createIcons();
    window.scrollTo(0, 0);
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

/**
 * Switch between tabs (for future enhancement)
 */
function switchTab(panelId, button) {
    // Get all panels
    const panels = document.querySelectorAll('[id^="panel-"]');
    panels.forEach(panel => {
        panel.classList.remove('active');
    });

    // Get all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected panel and highlight button
    document.getElementById(panelId)?.classList.add('active');
    button?.classList.add('active');
}

/**
 * Toggle stock editor (for future enhancement)
 */
function toggleStockEditor() {
    const editor = document.getElementById('stock-editor');
    const display = document.getElementById('stock-display');

    if (editor && display) {
        editor.style.display = editor.style.display === 'none' ? 'flex' : 'none';
        display.style.display = display.style.display === 'none' ? 'flex' : 'none';
    }
}

// Add keyboard navigation support
document.addEventListener('keydown', function(e) {
    // Prevent if user is typing in an input
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
        return;
    }

    if (e.key === 'ArrowLeft' && currentStep > 1) {
        previousStep();
    } else if (e.key === 'ArrowRight' && currentStep < totalSteps) {
        nextStep();
    }
});

// Add CSS for toast notifications dynamically
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

    .completion-state {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 500px;
    }

    .completion-card {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        border: 1px solid #e2e8f0;
        max-width: 500px;
        animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .completion-icon {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: center;
        animation: bounceIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s backwards;
    }

    @keyframes bounceIn {
        from {
            opacity: 0;
            transform: scale(0);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .completion-title {
        font-size: 2rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.75rem;
        font-family: 'Space Mono', monospace;
    }

    .completion-text {
        font-size: 0.95rem;
        color: #64748b;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .completion-next-steps {
        background: linear-gradient(135deg, #f0fdf4 0%, #eff6ff 100%);
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        text-align: left;
        border: 2px solid rgba(13, 148, 136, 0.2);
    }

    .completion-next-steps h3 {
        font-size: 0.9rem;
        font-weight: 700;
        color: #0d9488;
        margin-bottom: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .completion-next-steps ul {
        list-style: none;
    }

    .completion-next-steps li {
        font-size: 0.9rem;
        color: #1e293b;
        padding: 0.5rem 0;
        font-weight: 500;
    }

    .completion-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-go-dashboard {
        flex: 1;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #0d9488 0%, #10b981 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 15px rgba(13, 148, 136, 0.2);
    }

    .btn-go-dashboard:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(13, 148, 136, 0.3);
    }

    @media (max-width: 768px) {
        .completion-card {
            padding: 1.5rem;
        }

        .completion-title {
            font-size: 1.5rem;
        }

        .toast-notification {
            bottom: 1rem;
            right: 1rem;
            max-width: 300px;
        }
    }
`;
document.head.appendChild(style);


// ==========================================
// MAP FUNCTIONALITY
// ==========================================

let map;
let marker;

// Initialize the map when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});

function initMap() {
    // Default view: Bacolod City (same as your map.js)
    // You can change these numbers to center anywhere else by default
    const defaultLat = 10.6765;
    const defaultLng = 122.9509;

    map = L.map('setup-map').setView([defaultLat, defaultLng], 13);

    // Add the map tiles (skin)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Event Listener: When user clicks the map
    map.on('click', function(e) {
        updateMarker(e.latlng.lat, e.latlng.lng);
    });

    // Fix for map not rendering correctly if hidden initially
    setTimeout(() => { map.invalidateSize(); }, 100);
}

// Function to place marker and fill inputs
function updateMarker(lat, lng) {
    // If marker exists, move it. If not, create it.
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map);
    }

    // Update the read-only input fields
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
}

// Function for the "Get Current Location" button
function useCurrentLocation() {
    if ("geolocation" in navigator) {
        // Change button text to show it's working
        const btn = document.querySelector('button[onclick="useCurrentLocation()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'LOCATING...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Zoom to the user's location
                map.setView([lat, lng], 16);
                
                // Drop the pin there
                updateMarker(lat, lng);

                // Restore button text
                btn.innerHTML = originalText;
                showToast('Location found!', 'success');
            },
            (error) => {
                showToast('Could not get your location. Please check browser permissions.', 'error');
                console.error(error);
                btn.innerHTML = originalText;
            }
        );
    } else {
        showToast('Geolocation is not supported by this browser.', 'error');
    }
}