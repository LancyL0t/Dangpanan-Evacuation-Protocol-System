// =============================================
// DANGPANAN REQUEST PROGRESS TRACKING SYSTEM
// Enhanced Progress Modal with Navigation
// =============================================

/**
 * Show detailed progress modal for a specific request
 */
window.showRequestProgress = async function(requestId) {
    try {
        // Fetch request details
        const response = await fetch(`index.php?route=get_request_details&request_id=${requestId}`);
        const data = await response.json();
        
        if (!data.success || !data.request) {
            throw new Error('Failed to load request details');
        }
        
        const request = data.request;
        
        // Build and show modal
        buildProgressModal(request);
        
    } catch (error) {
        console.error('Error loading request progress:', error);
        alert('Failed to load request progress. Please try again.');
    }
};

/**
 * Build the progress modal HTML
 */
function buildProgressModal(request) {
    // Determine progress stage
    const stages = getProgressStages(request);
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'progress-modal-overlay';
    modal.id = 'requestProgressModal';
    
    modal.innerHTML = `
        <div class="progress-modal-container">
            <!-- Modal Header -->
            <div class="progress-modal-header">
                <h2 class="progress-modal-title">
                    <i data-lucide="clipboard-list"></i>
                    Request Progress
                </h2>
                <button class="modal-close-btn" onclick="closeProgressModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="progress-modal-body">
                <!-- Shelter Info Banner -->
                <div class="shelter-info-banner">
                    <h3>
                        <i data-lucide="home"></i>
                        ${escapeHtml(request.shelter_name)}
                    </h3>
                    <p>
                        <i data-lucide="map-pin"></i>
                        ${escapeHtml(request.location || 'Location not specified')}
                    </p>
                    ${request.contact_number ? `
                        <p style="margin-top: 0.5rem;">
                            <i data-lucide="phone"></i>
                            ${escapeHtml(request.contact_number)}
                        </p>
                    ` : ''}
                </div>
                
                <!-- Progress Timeline -->
                <div class="progress-timeline">
                    ${buildTimelineStages(stages, request)}
                </div>
                
                <!-- Actions -->
                ${buildActionButtons(request, stages)}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Animate in
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
    
    // Initialize icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Determine progress stages based on request status
 */
function getProgressStages(request) {
    const stages = {
        submitted: {
            status: 'completed',
            title: 'Request Submitted',
            description: 'Your shelter request has been successfully submitted to the host.',
            icon: 'check-circle',
            timestamp: request.created_at
        },
        reviewing: {
            status: request.reviewed_at ? 'completed' : (request.status === 'pending' ? 'active' : 'pending'),
            title: 'Under Review',
            description: 'The host is reviewing your request. This typically takes a few minutes.',
            icon: 'eye',
            timestamp: request.reviewed_at
        },
        decision: null // Will be set based on approval/rejection
    };
    
    if (request.status === 'approved') {
        stages.decision = {
            status: 'completed',
            title: 'Request Approved',
            description: 'Congratulations! Your request has been approved. You can now proceed to the shelter.',
            icon: 'check-circle-2',
            timestamp: request.approved_at,
            type: 'approved'
        };
    } else if (request.status === 'declined') {
        stages.decision = {
            status: 'rejected',
            title: 'Request Rejected',
            description: 'Unfortunately, the host has declined your request.',
            icon: 'x-circle',
            timestamp: request.reviewed_at,
            type: 'rejected'
        };
    } else {
        stages.decision = {
            status: 'pending',
            title: 'Awaiting Decision',
            description: 'The host will either approve or reject your request soon.',
            icon: 'clock',
            timestamp: null,
            type: 'pending'
        };
    }
    
    return stages;
}

/**
 * Build timeline stages HTML
 */
function buildTimelineStages(stages, request) {
    let html = '';
    
    // Submitted Stage
    html += buildStageHTML(stages.submitted);
    
    // Reviewing Stage
    html += buildStageHTML(stages.reviewing);
    
    // Decision Stage
    html += buildStageHTML(stages.decision);
    
    // Approval Code Section (if approved)
    if (request.status === 'approved' && request.approval_code) {
        html += `
            <div class="approval-code-section">
                <h4>
                    <i data-lucide="key"></i>
                    Your Approval Code
                </h4>
                <div class="approval-code-display">
                    ${escapeHtml(request.approval_code)}
                </div>
                <p class="approval-code-note">
                    <i data-lucide="info"></i>
                    Present this code at the shelter for check-in. Keep it safe!
                </p>
            </div>
        `;
    }
    
    // Rejection Message (if rejected)
    if (request.status === 'declined') {
        html += `
            <div class="rejection-message">
                <h4>
                    <i data-lucide="alert-triangle"></i>
                    Why was my request rejected?
                </h4>
                <p>${escapeHtml(request.rejection_reason || 'The host did not provide a specific reason.')}</p>
                <div class="alternative-action">
                    <p>What should I do now?</p>
                    <p style="font-weight: 400;">Please search for another available shelter on the map. You can submit a new request to a different location.</p>
                </div>
            </div>
        `;
    }
    
    return html;
}

/**
 * Build individual stage HTML
 */
function buildStageHTML(stage) {
    return `
        <div class="progress-stage ${stage.status}">
            <div class="stage-icon">
                <i data-lucide="${stage.icon}"></i>
            </div>
            <div class="stage-content">
                <div class="stage-header">
                    <h3 class="stage-title">${stage.title}</h3>
                    <span class="stage-badge ${stage.status}">
                        ${stage.status === 'completed' ? 'Completed' : 
                          stage.status === 'active' ? 'In Progress' : 
                          stage.status === 'rejected' ? 'Rejected' : 
                          'Pending'}
                    </span>
                </div>
                <p class="stage-description">${stage.description}</p>
                ${stage.timestamp ? `
                    <div class="stage-timestamp">
                        <i data-lucide="clock"></i>
                        ${formatTimestamp(stage.timestamp)}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

/**
 * Build action buttons based on request status
 */
function buildActionButtons(request, stages) {
    let html = '<div class="progress-actions">';
    
    if (request.status === 'approved' && request.latitude && request.longitude) {
        html += `
            <button class="btn-navigate" onclick="startNavigationToShelter(${request.latitude}, ${request.longitude}, '${escapeHtml(request.shelter_name)}')">
                <i data-lucide="navigation"></i>
                Navigate to Shelter
            </button>
        `;
    }
    
    html += `
        <button class="btn-close-modal" onclick="closeProgressModal()">
            Close
        </button>
    `;
    
    html += '</div>';
    
    return html;
}

/**
 * Close progress modal
 */
window.closeProgressModal = function() {
    const modal = document.getElementById('requestProgressModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 300);
    }
};

/**
 * Start navigation to shelter
 */
window.startNavigationToShelter = function(lat, lng, shelterName) {
    closeProgressModal();
    
    if (!map || !currentUserLocation) {
        alert('Unable to start navigation. Please ensure location services are enabled.');
        return;
    }
    
    // Clear existing route
    if (routePolyline) {
        map.removeLayer(routePolyline);
    }
    
    // Center map between user and shelter
    const bounds = L.latLngBounds(
        [currentUserLocation.lat, currentUserLocation.lng],
        [lat, lng]
    );
    map.fitBounds(bounds, { padding: [50, 50] });
    
    // Draw route line
    routePolyline = L.polyline(
        [[currentUserLocation.lat, currentUserLocation.lng], [lat, lng]],
        {
            color: '#3b82f6',
            weight: 4,
            opacity: 0.8,
            dashArray: '10, 10'
        }
    ).addTo(map);
    
    // Calculate distance
    const distance = calculateDistance(
        currentUserLocation.lat,
        currentUserLocation.lng,
        lat,
        lng
    );
    
    // Show navigation notification
    showNotification(`🧭 Navigation started to ${shelterName}. Distance: ${distance.toFixed(2)} km`);
    
    // Update active navigation flag
    activeNavigationMode = true;
};

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/**
 * Format timestamp for display
 */
function formatTimestamp(timestamp) {
    if (!timestamp) return '';
    
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show notification toast
 */
function showNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        font-weight: 600;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
