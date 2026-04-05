<?php

require_once 'config/auth_guard.php';

protect_page();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Evacuee Portal</title>
    
    <!-- Core Styles -->
    <link rel="stylesheet" href="assets/css/portal.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/progress_tracker.css">
    <link rel="stylesheet" href="assets/css/chat.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Scripts -->
    <script src="assets/js/nav.js" defer></script>
    <script src="assets/js/progress_tracker.js" defer></script>
    <script src="assets/js/evacuee.js" defer></script>
</head>
<body class="light-portal">

<?php require 'views/partials/nav_portal.php'; ?>

    <main class="portal-container">
        <div class="portal-layout">
            <div class="main-content">
                <!-- Portal Header -->
                <section class="portal-header">
                    <div class="header-info">
                        <h1 class="page-title">Evacuee Portal</h1>
                        <p class="user-id">
                            ID: #<?php echo $_SESSION['user_id']; ?> • 
                            STATUS: <span><?php echo $checkedInShelter ? 'CHECKED IN' : 'SEEKING SHELTER'; ?></span>
                        </p>
                    </div>
                    <div class="header-actions" style="display: flex; gap: 0.75rem;">
                        <!-- VIEW MY REQUESTS BUTTON (hidden when checked in) -->
                        <?php if (!$checkedInShelter): ?>
                        <button id="viewRequestsBtn" class="btn-view-requests-header btn-view-requests-pending" style="display: none;">
                            <i data-lucide="clipboard-list"></i>
                            <span>VIEW MY REQUESTS</span>
                        </button>
                        <?php
endif; ?>
                        
                        <button class="sos-btn" id="sosTrigger">
                            <i data-lucide="alert-circle"></i>
                            SOS SIGNAL
                        </button>
                    </div>
                </section>

                <?php if (isset($hostStatus) && $hostStatus === 'active_host'): ?>
                <!-- HOST STATUS WARNING -->
                <div class="host-status-warning">
                    <div class="host-status-warning-content">
                        <div class="host-status-warning-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="host-status-warning-text">
                            <h4>You Cannot Request Shelter While Hosting</h4>
                            <p>
                                You are currently registered as an <strong>Active Host</strong>. To request shelter as an evacuee, 
                                you must first relinquish your host status in the Host Portal.
                            </p>
                            <a href="index.php?route=host_portal" class="host-status-warning-link">
                                <i data-lucide="arrow-right"></i>
                                GO TO HOST PORTAL
                            </a>
                        </div>
                    </div>
                </div>
                <?php
endif; ?>

                <!-- Metrics Grid -->
                <section class="metrics-grid">
                    <div class="metric-card location-card">
                        <div class="card-head">
                            <span class="metric-label">Current Location</span>
                            <i data-lucide="map-pin"></i>
                        </div>
                        <h3 class="metric-value">Bacolod City</h3>
                        <p class="metric-detail">GPS Signal: Strong</p>
                    </div>

                    <div class="metric-card safezone-card">
                        <div class="card-head">
                            <span class="metric-label">Nearest Safe Zone</span>
                            <i data-lucide="navigation"></i>
                        </div>
                        <h3 class="metric-value">Calculating...</h3>
                        <p class="metric-detail">ETA: Calculating...</p>
                    </div>

                    <div class="metric-card family-card">
                        <div class="card-head">
                            <span class="metric-label">Active Requests</span>
                            <i data-lucide="send"></i>
                        </div>
                        <h3 class="metric-value" id="activeRequestCount">0</h3>
                        <p class="metric-detail">Pending Approval</p>
                    </div>
                </section>

                <!-- Map Section -->
                <section class="map-section">
                    <div class="map-header">
                        <h2 class="section-title">Live Shelter Map with Navigation</h2>
                        <span class="live-tag">LIVE FEED • GPS ENABLED</span>
                    </div>
                    
                    <div class="map-frame" id="evacueeMap">
                        <!-- Leaflet map renders here -->
                        
                        <!-- Map Legend -->
                        <div class="map-legend">
                            <div class="legend-item">
                                <span class="dot red"></span>
                                YOUR LOCATION
                            </div>
                            <div class="legend-item">
                                <span class="dot green"></span>
                                AVAILABLE SHELTER
                            </div>
                            <div class="legend-item">
                                <span class="dot yellow"></span>
                                LIMITED CAPACITY
                            </div>
                            <div class="legend-item">
                                <span class="dot" style="background: #3b82f6;"></span>
                                ACTIVE ROUTE
                            </div>
                        </div>
                    </div>
                    
                    <div class="search-bar">
                        <div class="search-input-wrapper">
                            <input type="text" 
                                   placeholder="Search shelters by name or location..." 
                                   class="search-input" 
                                   id="shelterSearch">
                        </div>
                        <button class="filter-btn" id="nearestShelterBtn" onclick="findNearestShelter()" style="background: #3b82f6; color: white; border-color: #2563eb; margin-right: 0.5rem;" title="Find nearest shelter based on your GPS">
                            <i data-lucide="navigation"></i>
                            NEAREST SHELTER
                        </button>
                        <button class="filter-btn">
                            <i data-lucide="sliders-horizontal"></i>
                            FILTER
                        </button>
                    </div>
                </section>

                <!-- Available Shelters Feed -->
                <section class="shelter-feed">
                    <h2 class="section-title with-margin">Available Shelters</h2>
                    
                    <?php foreach ($sheltersData as $s): ?>
                    <div class="shelter-row-card">
                        <div class="shelter-main">
                            <div class="shelter-icon-box">
                                <i data-lucide="home"></i>
                            </div>
                            <div class="shelter-details">
                                <h4 class="shelter-name"><?php echo htmlspecialchars($s['shelter_name']); ?></h4>
                                <p class="shelter-sub">
                                    <i data-lucide="map-pin"></i>
                                    <?php echo htmlspecialchars($s['type'] ?? 'Public Shelter'); ?> • 
                                    <?php echo htmlspecialchars($s['location']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="shelter-meta">
                            <div class="capacity-info">
                                <?php
    $isFull = $s['current_capacity'] >= $s['max_capacity'];
?>
                                <span class="status-badge <?php echo $isFull ? 'full' : 'open'; ?>">
                                    <?php echo $isFull ? 'FULL' : 'OPEN'; ?>
                                </span>
                                <p class="capacity-text">
                                    <?php echo htmlspecialchars($s['current_capacity']); ?>/<?php echo htmlspecialchars($s['max_capacity']); ?> Capacity
                                </p>
                            </div>
                            
                            <button class="btn-request-action" 
                            <?php
    $isDisabled = $s['is_full'] || (isset($hostStatus) && $hostStatus === 'active_host') || $checkedInShelter;
    echo $isDisabled ? 'disabled' : '';
?> 
                            onclick="handleRequest('<?php echo htmlspecialchars($s['shelter_id']); ?>', '<?php echo htmlspecialchars($s['shelter_name'], ENT_QUOTES); ?>')"
                            <?php if (isset($hostStatus) && $hostStatus === 'active_host'): ?>
                                title="You must relinquish host status first"
                            <?php
    elseif ($checkedInShelter): ?>
                                title="Please check out from your current shelter first"
                            <?php
    endif; ?>>
                        <?php
    if (isset($hostStatus) && $hostStatus === 'active_host') {
        echo 'HOST STATUS ACTIVE';
    }
    elseif ($checkedInShelter) {
        echo 'CHECKED IN';
    }
    elseif ($s['is_full']) {
        echo 'SHELTER FULL';
    }
    else {
        echo 'REQUEST ENTRY';
    }
?>
                    </button>
                        </div>
                    </div>
                    <?php
endforeach; ?>

                    <!-- Pagination (rendered by JS when shelters > 10) -->
                    <div id="shelterPagination" class="shelter-pagination" style="display:none;"></div>
                </section>
            </div>

            <!-- Sidebar -->
<aside class="sidebar">
    <?php if ($checkedInShelter): ?>
    <!-- CHECKED IN PANEL: shown when evacuee is currently checked in -->
    <div class="sidebar-panel approval-code-panel" style="background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%); border: 2px solid #10b981;">
        <h3 class="panel-title" style="color: #065f46;">
            <i data-lucide="home" style="width: 18px; color: #10b981;"></i>
            Currently Checked In
        </h3>
        <div class="approval-code-display">
            <div class="code-shelter-info" style="font-size: 1.1rem; font-weight: 700; color: #065f46; margin-bottom: 1rem; background: white; padding: 0.75rem; border-radius: 8px; border: 1px solid #a7f3d0; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="map-pin" style="width: 16px; color: #10b981; flex-shrink: 0;"></i>
                <span><?php echo htmlspecialchars($checkedInShelter['shelter_name']); ?></span>
            </div>
            <p style="font-size: 0.8rem; color: #047857; margin-bottom: 1rem;">You are currently staying at this shelter.</p>
            <button onclick="showCheckOutModal()" class="btn-checkout" style="
                width: 100%;
                padding: 0.875rem;
                background: #dc2626;
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 700;
                font-size: 0.875rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                letter-spacing: 0.05em;
                transition: background 0.2s;
            ">
                <i data-lucide="log-out" style="width: 16px;"></i>
                CHECK OUT
            </button>
            <a href="index.php?route=messages" class="btn-chat" style="width: 100%; justify-content: center; margin-top: 0.5rem; padding: 0.75rem; font-size: 0.8rem;">
                <i data-lucide="message-circle"></i>
                CHAT WITH HOST
            </a>
        </div>
    </div>

    <?php
elseif (isset($approvedRequest) && $approvedRequest): ?>

    <!-- QR CODE HIGHLIGHT ABOVE EMERGENCY CONTACTS -->
    <div class="sidebar-panel" style="background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%); border: none; box-shadow: 0 4px 20px rgba(29,78,216,0.4);">
        <h3 class="panel-title" style="color: #bfdbfe; border-bottom-color: rgba(191,219,254,0.3);">
            <i data-lucide="qr-code" style="width: 18px; color: #93c5fd;"></i>
            <span style="color: #bfdbfe;">Ready for Check-In</span>
        </h3>
        <div style="text-align: center; padding: 0.5rem 0;">
            <div style="background: white; border-radius: 10px; padding: 0.75rem; display: inline-block; margin-bottom: 0.75rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode($approvedRequest['approval_code']); ?>"
                     alt="Check-In QR Code"
                     style="width: 220px; height: 220px; display: block; border-radius: 4px;">
            </div>
            <div style="background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.4); border-radius: 8px; padding: 0.6rem 1rem; display: inline-block; backdrop-filter: blur(4px); margin-bottom: 0.5rem;">
                <span style="font-size: 1.75rem; font-weight: 800; letter-spacing: 6px; color: white; font-family: 'Courier New', monospace;">
                    <?php echo htmlspecialchars($approvedRequest['approval_code']); ?>
                </span>
            </div>
            <p style="color: #bfdbfe; font-size: 0.75rem; margin: 0.4rem 0 0; font-weight: 500;">
                Show this to the host upon arrival
            </p>
            <p style="color: #93c5fd; font-size: 0.7rem; margin: 0.25rem 0 0; display: flex; align-items: center; justify-content: center; gap: 0.3rem;">
                <i data-lucide="home" style="width: 12px;"></i>
                <?php echo htmlspecialchars($approvedRequest['shelter_name']); ?>
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                <a href="index.php?route=generate_pass&request_id=<?php echo $approvedRequest['id']; ?>" 
                   target="_blank"
                   class="btn-download-pdf" 
                   style="width: 90%; justify-content: center; margin: 0 auto; padding: 0.75rem; font-size: 0.8rem; background: white; color: #1e3a8a; border: none; border-radius: 8px; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; transition: transform 0.2s;">
                    <i data-lucide="file-down"></i>
                    DOWNLOAD PDF PASS
                </a>
                <a href="index.php?route=chat&request_id=<?php echo $approvedRequest['id']; ?>" 
                   class="btn-chat" 
                   style="width: 90%; justify-content: center; margin: 0 auto; padding: 0.75rem; font-size: 0.8rem; border-color: rgba(147,197,253,0.4); color: #93c5fd; background: rgba(147,197,253,0.1);">
                    <i data-lucide="message-circle"></i>
                    CHAT WITH HOST
                </a>
            </div>
        </div>
    </div>
    <?php
endif; ?>

    <div class="sidebar-panel">
        <h3 class="panel-title">Emergency Contacts</h3>
                    
                    <a href="tel:911-00" class="contact-card" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: white; border: 2px solid #fee2e2; border-radius: 12px; text-decoration: none; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 40px; height: 40px; background: #dc2626; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="phone" style="color: white; width: 20px;"></i>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 1.125rem; color: #1e293b;">911-00</strong>
                                <p style="font-size: 0.7rem; margin: 0; color: #64748b;">EMERGENCY HOTLINE</p>
                            </div>
                        </div>
                        <span class="status-badge open" style="background: #fee2e2; color: #b91c1c; font-size: 0.6rem;">TAP TO CALL</span>
                    </a>
                    
                    <a href="tel:911-01" class="contact-card">
                        <span>
                            <img src="assets/img/redcross.png" alt="Red Cross" style="width: 24px; height: 24px; object-fit: contain;"> 
                            PH Red Cross
                        </span>
                        <strong>(034) 458 9798</strong>
                    </a>

                    <a href="tel:911-02" class="contact-card">
                        <span>
                            <img src="assets/img/BFP.jpg" alt="Fire Dept" style="width: 24px; height: 24px; object-fit: contain;">
                            Fire Station
                        </span>
                        <strong>(034) 434-5022</strong>
                    </a>
                </div>

                <div class="sidebar-panel">
                    <h3 class="panel-title">
                        <i data-lucide="info" style="width: 18px; vertical-align: middle; margin-right: 5px; color: #3b82f6;"></i>
                        Emergency Safety Tips
                    </h3>
                    
                    <div class="safety-tips-accordion" style="display: grid; gap: 0.75rem;">
                        <details class="tip-item" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <summary style="padding: 10px; font-weight: 500; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                                During a Flood <i data-lucide="chevron-down" style="width: 16px;"></i>
                            </summary>
                            <ul style="padding: 0 10px 10px 25px; font-size: 0.85rem; color: #475569; margin: 0;">
                                <li>Move to higher ground immediately</li>
                                <li>Avoid walking in moving water</li>
                                <li>Stay away from power lines</li>
                            </ul>
                        </details>

                        <details class="tip-item" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <summary style="padding: 10px; font-weight: 500; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                                During an Earthquake <i data-lucide="chevron-down" style="width: 16px;"></i>
                            </summary>
                            <ul style="padding: 0 10px 10px 25px; font-size: 0.85rem; color: #475569; margin: 0;">
                                <li>Drop, Cover, and Hold On</li>
                                <li>Stay away from windows and glass</li>
                                <li>If outdoors, move to an open area</li>
                            </ul>
                        </details>

                        <details class="tip-item" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <summary style="padding: 10px; font-weight: 500; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                                During a Typhoon <i data-lucide="chevron-down" style="width: 16px;"></i>
                            </summary>
                            <ul style="padding: 0 10px 10px 25px; font-size: 0.85rem; color: #475569; margin: 0;">
                                <li>Stay indoors and away from windows</li>
                                <li>Unplug electrical appliances</li>
                                <li>Monitor weather updates</li>
                            </ul>
                        </details>
                    </div>
                </div>

                <div class="sidebar-panel">
                    <h3 class="panel-title">Additional Resources</h3>
                    <div class="resources-grid">
                        <a href="https://www.pagasa.dost.gov.ph/" target="_blank" class="resource-btn weather">
                            <img src="assets/img/PAGASA.png" alt="PAGASA"> 
                            <strong>PAGASA</strong>
                            <small>Weather</small>
                        </a>
                        
                        <a href="https://www.phivolcs.dost.gov.ph/" target="_blank" class="resource-btn earthquake">
                            <img src="assets/img/DOST.png" alt="DOST"> 
                            <strong>PHIVOLCS</strong>
                            <small>Earthquake</small>
                        </a>
                    </div>
                </div>

                <?php if (isset($hostStatus) && $hostStatus === 'relinquished'): ?>
                <!-- RESTORE SHELTER SIDEBAR PANEL -->
                <div class="sidebar-panel restore-shelter-panel">
                    <h3 class="panel-title" style="color: #065f46;">
                        <i data-lucide="shield-check" style="color: #10b981;"></i>
                        Restore Your Shelter
                    </h3>
                    
                    <div class="restore-content">
                        <p style="font-size: 0.875rem; color: #064e3b; line-height: 1.6; margin-bottom: 1rem;">
                            Your shelter is currently inactive. Ready to host again?
                        </p>
                        
                        <div class="restore-list" style="background: #f0fdf4; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #a7f3d0;">
                            <p style="font-size: 0.75rem; font-weight: 700; color: #065f46; margin: 0 0 0.5rem;">Restoring will:</p>
                            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.75rem; color: #047857; line-height: 1.7;">
                                <li>Reactivate your shelter</li>
                                <li>Make it visible to evacuees</li>
                                <li>Allow new requests</li>
                            </ul>
                        </div>
                        
                        <button class="btn-restore-sidebar" onclick="confirmRestoreStatus()">
                            <i data-lucide="rotate-ccw"></i>
                            RESTORE HOST STATUS
                        </button>
                    </div>
                </div>
                <?php
endif; ?>
            </aside>
        </div>
    </main>

<!-- REQUEST MODAL -->
<div id="requestModal" class="modal-overlay">
    <div class="protocol-modal">
        <div class="modal-header">
            <h3 class="modal-title">
                <i data-lucide="shield-alert"></i>
                Shelter Request
            </h3>
            <button type="button" class="btn-close-icon" onclick="closeRequestModal()" aria-label="Close">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form id="shelterRequestForm" class="modal-body">
            <!-- Destination -->
            <div class="protocol-context">
                <small class="input-label">Sending to</small>
                <div id="displayShelterName" class="context-value">---</div>
            </div>

            <input type="hidden" id="modalShelterId">
            <input type="hidden" id="modalShelterName">

            <!-- Occupants -->
            <div class="input-group">
                <label class="input-label" for="group_size">Total Occupants</label>
                <input type="number" id="group_size" class="protocol-input"
                       value="1" min="1" max="20" required
                       placeholder="Number of people">
            </div>

            <!-- Notes -->
            <div class="input-group">
                <label class="input-label" for="request_note">Notes / Special Needs</label>
                <textarea id="request_note" class="protocol-input"
                          style="height: 62px; resize: none;"
                          placeholder="e.g. 2 elderly, 1 injury, pet..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeRequestModal()">CANCEL</button>
                <button type="submit" class="btn-confirm">
                    <i data-lucide="send"></i> SEND REQUEST
                </button>
            </div>
        </form>
    </div>
</div>

<!-- RESTORE HOST STATUS MODAL -->
<div id="restoreHostModal" class="modal-overlay" style="display: none;">
    <div class="protocol-modal" style="max-width: 500px;">
        <div class="modal-header restore-modal-header">
            <h3 class="modal-title restore-modal-title">
                <i data-lucide="shield-check"></i> 
                Restore Host Status
            </h3>
            <button type="button" class="btn-close-icon" onclick="closeRestoreModal()">
                <i data-lucide="x"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="restore-confirmation-box">
                <p>✅ Ready to Host Again?</p>
                <p>
                    Confirm that your shelter is now safe and ready to accept evacuees again.
                </p>
            </div>

            <div class="restore-info-box">
                <h4>What will happen:</h4>
                <ul>
                    <li>Your shelter will be <strong>reactivated</strong> immediately</li>
                    <li>Evacuees can see your shelter on the map</li>
                    <li>You can accept new admission requests</li>
                    <li>You'll return to the Host Portal</li>
                </ul>
            </div>

            <div class="restore-warning-box">
                <p>⚠️ <strong>Important:</strong> Only restore if your shelter is safe and you have adequate supplies to host evacuees.</p>
            </div>

            <div class="modal-actions restore-actions">
                <button type="button" class="btn-cancel" onclick="closeRestoreModal()">
                    NOT YET
                </button>
            <button type="button" 
                    class="btn-confirm" 
                    onclick="executeRestore(this)"> <i data-lucide="shield-check"></i> CONFIRM RESTORE
            </button>
            </div>
        </div>
    </div>
</div>

<?php require 'views/partials/footer.php'; ?>

<!-- CHECK OUT CONFIRMATION MODAL -->
<div id="checkOutModal" class="modal-overlay" style="display: none;">
    <div class="protocol-modal" style="max-width: 480px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #fee2e2 0%, #ffffff 100%); border-bottom: 2px solid #ef4444;">
            <h3 class="modal-title" style="color: #991b1b;">
                <i data-lucide="log-out" style="color: #ef4444;"></i>
                Confirm Check-Out
            </h3>
            <button type="button" class="btn-close-icon" onclick="closeCheckOutModal()">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1.75rem;">
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1.25rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <p style="font-weight: 700; color: #92400e; margin: 0 0 0.5rem; font-size: 1rem;">
                    ⚠️ Are you sure you want to check out?
                </p>
                <p style="font-size: 0.9rem; color: #78350f; margin: 0; line-height: 1.6;">
                    Please inform the host before checking out.
                </p>
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: #475569; line-height: 1.7;">
                After checking out:<br>
                • Your spot at the shelter will be released<br>
                • You will be able to request another shelter<br>
                • This action cannot be undone
            </div>

            <div class="modal-actions" style="gap: 0.75rem;">
                <button type="button" class="btn-cancel" onclick="closeCheckOutModal()" style="flex: 1;">
                    CANCEL
                </button>
                <button type="button" id="confirmCheckOutBtn" class="btn-confirm" 
                        onclick="executeCheckOut(this)"
                        style="flex: 1; background: #dc2626; border-color: #b91c1c;">
                    <i data-lucide="log-out"></i>
                    CONFIRM CHECK OUT
                </button>
            </div>
        </div>
    </div>
</div>

<!-- HOST RATING MODAL (shown after checkout) -->
<div id="ratingModal" class="modal-overlay" style="display: none;">
    <div class="protocol-modal" style="max-width: 480px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%); border-bottom: 2px solid #f59e0b;">
            <h3 class="modal-title" style="color: #92400e;">
                <i data-lucide="star" style="color: #f59e0b;"></i>
                Rate Your Host
            </h3>
            <button type="button" class="btn-close-icon" onclick="skipRating()">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1.75rem; text-align: center;">
            <p style="font-size: 0.95rem; color: #475569; margin-bottom: 0.5rem;">
                How was your stay at <strong id="ratingShelterName"></strong>?
            </p>
            <p style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 1.5rem;">
                Your feedback helps future evacuees make informed decisions.
            </p>

            <!-- Star Selector -->
            <div class="star-selector" id="starSelector">
                <button type="button" class="star-btn" data-rating="1" title="Poor">
                    <i data-lucide="star"></i>
                </button>
                <button type="button" class="star-btn" data-rating="2" title="Fair">
                    <i data-lucide="star"></i>
                </button>
                <button type="button" class="star-btn" data-rating="3" title="Good">
                    <i data-lucide="star"></i>
                </button>
                <button type="button" class="star-btn" data-rating="4" title="Very Good">
                    <i data-lucide="star"></i>
                </button>
                <button type="button" class="star-btn" data-rating="5" title="Excellent">
                    <i data-lucide="star"></i>
                </button>
            </div>
            <p class="star-label" id="starLabel" style="font-size: 0.85rem; color: #64748b; margin: 0.75rem 0 1.25rem; min-height: 1.2em;">
                Tap a star to rate
            </p>

            <!-- Review Text -->
            <textarea id="reviewText" class="protocol-input" 
                      placeholder="Share your experience (optional)..." 
                      style="height: 80px; resize: none; text-align: left; font-size: 0.875rem; margin-bottom: 1.25rem;"></textarea>

            <input type="hidden" id="ratingOccupantId" value="">
            <input type="hidden" id="selectedRating" value="0">

            <div class="modal-actions" style="gap: 0.75rem;">
                <button type="button" class="btn-cancel" onclick="skipRating()" style="flex: 1;">
                    SKIP
                </button>
                <button type="button" id="submitRatingBtn" class="btn-confirm" 
                        onclick="submitHostRating(this)"
                        style="flex: 1; background: #f59e0b; border-color: #d97706;" disabled>
                    <i data-lucide="star"></i>
                    SUBMIT RATING
                </button>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.sheltersData = <?php echo json_encode($sheltersData); ?>;
</script>
</body>
</html>