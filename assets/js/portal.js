/* Dangpanan/assets/js/portal.js */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Initialize Lucide icons
    if (typeof lucide !== "undefined") {
        lucide.createIcons();
    }

    // --- SOS SIGNAL LOGIC ---
    const sosBtn = document.getElementById("sosTrigger");
    if (sosBtn) {
        sosBtn.addEventListener("click", function () {
            const originalContent = sosBtn.innerHTML;
            sosBtn.innerHTML = "<span>SENDING...</span>";
            sosBtn.style.background = "var(--primary-red)";
            sosBtn.style.color = "white";

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        console.log("SOS Signal Dispatched at:", position.coords);
                        alert("EMERGENCY SIGNAL SENT!\nHelp is being routed to your coordinates.");
                        sosBtn.innerHTML = originalContent;
                    },
                    (error) => {
                        alert("Unable to get location. Signal sent without coordinates.");
                        sosBtn.innerHTML = originalContent;
                    }
                );
            }
        });
    }

    // --- SHELTER REQUEST FORM SUBMISSION ---
    const requestForm = document.getElementById('shelterRequestForm');
    if (requestForm) {
        requestForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = requestForm.querySelector('.btn-confirm');
            const originalBtnText = submitBtn.innerHTML;

            // 1. Capture Data
            const payload = {
                shelter_id: document.getElementById('modalShelterId').value,
                group_size: document.getElementById('group_size').value,
                notes: document.getElementById('request_note').value
            };

            // 2. Validate Data
            if (!payload.shelter_id) {
                alert("System Error: Shelter ID missing. Please refresh and try again.");
                return;
            }

            // UI Loading State
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> TRANSMITTING...';
            lucide.createIcons(); // Refresh to show loading spinner

            // 3. Transmit to Backend
            fetch('index.php?route=submit_request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`PROTOCOL SUCCESS: Request for ${payload.group_size} people sent.\nWaiting for host approval.`);
                    closeRequestModal();
                    requestForm.reset(); // Clear form for next use
                } else {
                    alert("PROTOCOL FAILED: Database could not save request.");
                }
            })
            .catch(err => {
                console.error("Transmission Error:", err);
                alert("CONNECTION ERROR: Could not reach the server.");
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                lucide.createIcons();
            });
        });
    }

    // --- REAL-TIME NOTIFICATION POLLING ---
    setInterval(function () {
        fetch('index.php?route=check_updates')
            .then(response => response.json())
            .then(data => {
                if (data.new_update) {
                    showNotification(`URGENT: Your request for ${data.facility} is APPROVED!`);
                }
            })
            .catch(() => { /* Silent fail for polling */ });
    }, 10000);
});

/* =========================================
   GLOBAL FUNCTIONS (Accessible by HTML)
   ========================================= */

/**
 * Opens the request modal (called from the PHP loop)
 */
function handleRequest(shelterId, shelterName) {
    const modal = document.getElementById('requestModal');
    
    // 1. Set the hidden inputs for the form submission
    document.getElementById('modalShelterId').value = shelterId;
    document.getElementById('modalShelterName').value = shelterName;
    
    // 2. Update the VISUAL display (The part that was missing)
    const displayLabel = document.getElementById('displayShelterName');
    if (displayLabel) {
        displayLabel.innerText = shelterName; // This fills the "---" with the actual name
    }

    // 3. Show the modal
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
        document.body.style.overflow = 'hidden';
    }
}
/**
 * Closes the request modal
 */
function closeRequestModal() {
    const modal = document.getElementById('requestModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }
}

/**
 * UI helper to show a temporary notification banner
 */
function showNotification(message) {
    const banner = document.createElement('div');
    banner.className = 'toast-notification active';
    banner.innerHTML = `<strong>System Alert:</strong> ${message}`;
    document.body.appendChild(banner);

    setTimeout(() => {
        banner.classList.remove('active');
        setTimeout(() => banner.remove(), 500);
    }, 5000);
}