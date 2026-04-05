<?php 
require_once 'config/auth_guard.php'; 
protect_page(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="shelter-id" content="<?php echo $shelter['shelter_id'] ?? ''; ?>">
    <title>DANGPANAN | Host Command</title>
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="stylesheet" href="assets/css/host.css">    
    <link rel="stylesheet" href="assets/css/chat.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="light-portal host-theme">
 <?php require __DIR__ . '/../partials/nav_portal.php'; ?>

    <main class="portal-container">
        <!-- HEADER -->
        <header class="portal-header">
            <div class="header-left">
                <h1 class="page-title">
                    <?php echo htmlspecialchars($shelter['shelter_name'] ?? 'HOST COMMAND'); ?>
                </h1>
                <p class="user-id">
                    <?php echo htmlspecialchars($shelter['location'] ?? ''); ?>
                </p>
            </div>
            <div class="header-actions" style="display: flex; gap: 0.75rem;">
                <!-- Host Certification Access -->
                <a href="index.php?route=generate_cert&user_id=<?php echo $_SESSION['user_id']; ?>" 
                   target="_blank"
                   class="sos-btn" 
                   style="background: var(--primary); color: white; border: none; padding: 0 1.2rem; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.85rem; height: 42px;">
                    <i data-lucide="award"></i> MY CERTIFICATE
                </a>
                
                <button class="sos-btn" onclick="openQrScannerModal()">
                    <i data-lucide="qr-code"></i> SCAN QR
                </button>
            </div>
        </header>

        <!-- METRICS -->
        <div class="host-metrics-summary">
            <div class="summary-card cap">
                <span class="summary-label">TOTAL CAPACITY <i data-lucide="users"></i></span>
                <h2 class="summary-value"><?php echo $shelter['max_capacity'] ?? 0; ?></h2>
            </div>
            <div class="summary-card occ">
                <span class="summary-label">OCCUPIED <i data-lucide="circle"></i></span>
                <h2 class="summary-value"><?php echo $shelter['current_capacity'] ?? 0; ?></h2>
                <p class="summary-subtext">
                    <?php 
                    $maxCap = $shelter['max_capacity'] ?? 1;
                    $currentCap = $shelter['current_capacity'] ?? 0;
                    $percentage = $maxCap > 0 ? round(($currentCap / $maxCap) * 100) : 0;
                    echo $percentage . '% FULL';
                    ?>
                </p>
            </div>
            <div class="summary-card pen">
                <span class="summary-label">PENDING <i data-lucide="clock"></i></span>
                <h2 class="summary-value"><?php echo str_pad($pendingCount ?? 0, 2, '0', STR_PAD_LEFT); ?></h2>
                <p class="summary-subtext">REQUESTS</p>
            </div>
            <div class="summary-card sup">
                <span class="summary-label">STATUS <i data-lucide="activity"></i></span>
                <h2 class="summary-value">
                    <?php echo ($shelter['is_active'] ?? 0) ? 'ACTIVE' : 'INACTIVE'; ?>
                </h2>
                <p class="summary-subtext">
                    <?php echo ($shelter['is_full'] ?? 0) ? 'FULL' : 'ACCEPTING'; ?>
                </p>
            </div>
        </div>

        <!-- GRID LAYOUT -->
        <div class="host-grid-layout">
            <div class="host-main-content">
                
                <!-- OPERATIONAL STATUS -->
                <section class="dashboard-card">
                    <div class="card-header-strip">
                        <h3 class="card-title">
                            <i data-lucide="activity" class="title-icon"></i> OPERATIONAL STATUS
                        </h3>
                    </div>
                    <div class="op-status-section">
                        <div class="op-status-left">
                            <div>
                                <div class="status-badge <?php echo ($shelter['is_active'] ?? 0) ? '' : 'inactive'; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo ($shelter['is_active'] ?? 0) ? 'ACTIVE' : 'INACTIVE'; ?>
                                </div>
                                <p class="op-status-text">
                                    <?php 
                                    if ($shelter['is_active'] ?? 0) {
                                        echo 'Shelter is visible. Accepting admissions.';
                                    } else {
                                        echo 'Shelter is not accepting admissions.';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="op-status-actions">
                            <label class="toggle-label">
                                <span>Shelter Status</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           <?php echo ($shelter['is_active'] ?? 0) ? 'checked' : ''; ?> 
                                           onchange="toggleOperationalStatus(this, <?php echo $shelter['shelter_id'] ?? 0; ?>)">
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                    </div>
                </section>

                <!-- ADMISSIONS CONSOLE -->
                <section class="dashboard-card">
                    <div class="card-header-strip">
                        <h3 class="card-title">
                            <i data-lucide="users" class="title-icon"></i> ADMISSIONS CONSOLE
                        </h3>
                    </div>
                    
                    <!-- TABS -->
                    <div class="adm-tabs-header">
                        <button class="adm-tab-btn pending active" onclick="switchTab('panel-pending', this)">
                            <i data-lucide="clock"></i> PENDING (<?php echo $pendingCount ?? 0; ?>)
                        </button>
                        <button class="adm-tab-btn approved" onclick="switchTab('panel-approved', this)">
                            <i data-lucide="arrow-right-circle"></i> EXPECTING (<?php echo $approvedCount ?? 0; ?>)
                        </button>
                        <button class="adm-tab-btn checkin" onclick="switchTab('panel-checkin', this)">
                            <i data-lucide="check-square"></i> CHECK-IN
                        </button>
                        <button class="adm-tab-btn occupants" onclick="switchTab('panel-occupants', this); loadOccupants();">
                            <i data-lucide="users"></i> OCCUPANTS <span id="occupantCountBadge" class="count-badge">0</span>
                        </button>
                        <button class="adm-tab-btn history" onclick="switchTab('panel-history', this)">
                            <i data-lucide="history"></i> HISTORY
                        </button>
                    </div>
                    
                    <!-- PENDING TAB -->
                    <div id="panel-pending" class="adm-tab-panel active">
                        <div class="tab-list-container">
                            <h4 class="tab-section-label">New Shelter Requests</h4>

                            <?php if (!empty($pendingRequests)): ?>
                                <?php foreach ($pendingRequests as $request): ?>
                                    <div class="list-item <?php echo ($request['is_urgent'] ?? false) ? 'urgent' : ''; ?>">
                                        <div class="user-row">
                                            <div class="avatar" style="<?php echo ($request['is_urgent'] ?? false) ? 'background: #fee2e2; color: #ef4444;' : ''; ?>">
                                                <?php 
                                                $name = $request['first_name'] . ' ' . $request['last_name'];
                                                $initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                                                echo $initials;
                                                ?>
                                            </div>
                                            <div class="u-info">
                                                <h4><?php echo htmlspecialchars($name); ?></h4>
                                                <p>
                                                    <?php echo $request['group_size']; ?> People
                                                    <?php if (!empty($request['notes'])): ?>
                                                        • <strong class="text-danger"><?php echo htmlspecialchars($request['notes']); ?></strong>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="action-row">
                                            <button class="btn-base btn-approve" onclick="approveRequest(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($name); ?>', <?php echo $request['group_size']; ?>)">APPROVE</button>
                                            <button class="btn-base btn-decline" onclick="declineRequest(<?php echo $request['id']; ?>)">DECLINE</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i data-lucide="inbox"></i>
                                    <p>No pending requests</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- EXPECTING TAB -->
                    <div id="panel-approved" class="adm-tab-panel">
                        <div class="tab-list-container">
                            <h4 class="tab-section-label">Approved Evacuees Awaiting Check-In</h4>
                            
                            <?php if (!empty($approvedRequests)): ?>
                                <?php foreach ($approvedRequests as $request): ?>
                                    <div class="list-item-approved expecting-card">
                                        <div class="expecting-main">
                                            <div class="user-row">
                                                <div class="avatar">
                                                    <?php 
                                                    $name = $request['first_name'] . ' ' . $request['last_name'];
                                                    $initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                                                    echo $initials;
                                                    ?>
                                                </div>
                                                <div class="u-info">
                                                    <h4><?php echo htmlspecialchars($name); ?></h4>
                                                    <p class="evacuee-meta">
                                                        <i data-lucide="users" class="icon-sm"></i>
                                                        <?php echo $request['group_size']; ?> <?php echo $request['group_size'] > 1 ? 'people' : 'person'; ?>
                                                        <span class="meta-separator">•</span>
                                                        <i data-lucide="phone" class="icon-sm"></i>
                                                        <?php echo htmlspecialchars($request['phone_number'] ?? 'N/A'); ?>
                                                    </p>
                                                    <?php if (!empty($request['notes'])): ?>
                                                        <p class="evacuee-notes">
                                                            <i data-lucide="message-square" class="icon-sm"></i>
                                                            <?php echo htmlspecialchars($request['notes']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            

                                        </div>
                                        
                                        <div class="action-row">
                                            <a href="index.php?route=chat&request_id=<?php echo $request['id']; ?>" class="btn-chat">
                                                <i data-lucide="message-circle"></i>
                                                CHAT
                                            </a>
                                            <button class="btn-base btn-checkin" onclick="initiateCheckIn('<?php echo $request['approval_code']; ?>')">
                                                <i data-lucide="user-check"></i>
                                                CHECK IN NOW
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i data-lucide="inbox"></i>
                                    <p>No approved evacuees expected</p>
                                    <p class="empty-state-sub-note">
                                        Evacuees will appear here after you approve their requests
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CHECK-IN TAB -->
                    <div id="panel-checkin" class="adm-tab-panel">
    <div class="tab-list-container">
        <h4 class="tab-section-label">Check-In Options</h4>
        
        <!-- Manual Code Entry Option -->
        <div class="checkin-code-wrapper">
            <div class="checkin-icon-wrapper">
                <i data-lucide="shield-check" class="checkin-big-icon"></i>
            </div>
            <h3 class="checkin-title">Approval Code Check-In</h3>
            <p class="checkin-instruction">
                Enter the unique 6-digit approval code provided by the evacuee
            </p>
            
            <form class="approval-code-form" onsubmit="handleApprovalCodeCheckIn(event)">
                <div class="code-input-group">
                    <input 
                        type="text" 
                        class="approval-code-input" 
                        placeholder="Enter 6-digit Code (e.g., 482901)"
                        id="approval-code-input"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        required
                        
                    >
                    <button type="button" class="btn-verify-code" onclick="verifyApprovalCode()">
                        <i data-lucide="search"></i> VERIFY
                    </button>
                </div>
            </form>

            <!-- Verification Result -->
            <div id="verificationResult" class="verification-result-wrap"></div>
        </div>

        <!-- Divider -->
        <div class="checkin-divider">
            <span>OR</span>
        </div>

        <!-- QR Scanner Option -->
        <div class="checkin-manual-wrapper">
            <div class="checkin-icon-wrapper">
                <i data-lucide="qr-code" class="checkin-big-icon"></i>
            </div>
            <h3 class="checkin-title">QR Code Scanner</h3>
            <p class="checkin-instruction">
                Scan evacuee QR code for instant check-in
            </p>
            <button class="btn-scan-qr" onclick="openQrScannerModal()">
                <i data-lucide="qr-code"></i> OPEN QR SCANNER
            </button>
        </div>
    </div>
</div>

                    <!-- OCCUPANTS TAB -->
                    <div id="panel-occupants" class="adm-tab-panel">
                        <div class="tab-list-container">
                            <div class="occupants-header">
                                <h4 class="tab-section-label">Current Occupants</h4>
                                <div class="occupants-summary">
                                    <span class="summary-item">
                                        <i data-lucide="users"></i>
                                        <strong id="totalOccupants">0</strong> Evacuees
                                    </span>
                                    <span class="summary-item">
                                        <i data-lucide="user"></i>
                                        <strong id="totalPeople">0</strong> Total People
                                    </span>
                                </div>
                            </div>

                            <div id="occupantsList" class="occupants-list">
                                <!-- Occupants will be loaded here -->
                                <div class="loading-state">
                                    <i data-lucide="loader-2" class="spin"></i>
                                    <p>Loading occupants...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HISTORY TAB -->
                    <div id="panel-history" class="adm-tab-panel">
                        <div class="tab-list-container">
                            <div class="card-header-strip" style="background: transparent; border-bottom: none; padding: 0 0 1rem 0; justify-content: space-between; display: flex;">
                                <h4 class="tab-section-label" style="margin: 0; padding: 0;">Past Evacuees</h4>
                                <?php if (isset($hostRating) && $hostRating['total_ratings'] > 0): ?>
                                <div class="stars-display" title="<?php echo $hostRating['avg_rating']; ?> / 5 based on <?php echo $hostRating['total_ratings']; ?> ratings">
                                    <?php 
                                    $avgInfo = round($hostRating['avg_rating']); 
                                    for ($i=1; $i<=5; $i++): 
                                        if ($i <= $avgInfo): ?>
                                            <i data-lucide="star" class="star-filled"></i>
                                        <?php else: ?>
                                            <i data-lucide="star" class="star-empty"></i>
                                        <?php endif; 
                                    endfor; 
                                    ?>
                                    <span class="rating-text"><?php echo number_format($hostRating['avg_rating'], 1); ?> (<?php echo $hostRating['total_ratings']; ?>)</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($evacueeHistory)): ?>
                                <div class="shelter-feed">
                                    <?php foreach ($evacueeHistory as $history): 
                                        $ci = new DateTime($history['checked_in_at']);
                                        $coStr = $history['checked_out_at'] ? (new DateTime($history['checked_out_at']))->format('M j, Y, h:i A') : '—';
                                        
                                        // Calculate duration if checked out
                                        $durationStr = '';
                                        if ($history['checked_out_at']) {
                                            $co = new DateTime($history['checked_out_at']);
                                            $diff = $ci->diff($co);
                                            if ($diff->d > 0) $durationStr = $diff->d . 'd ' . $diff->h . 'h';
                                            elseif ($diff->h > 0) $durationStr = $diff->h . 'h ' . $diff->i . 'm';
                                            else $durationStr = $diff->i . 'm';
                                        }
                                        
                                        $name = $history['first_name'] . ' ' . $history['last_name'];
                                        $initials = strtoupper(substr($history['first_name'], 0, 1) . substr($history['last_name'], 0, 1));
                                    ?>
                                    <div class="list-item history-card" style="display: flex; flex-direction: column; gap: 12px; position: relative;">
                                        
                                        <!-- Top Row: User Info & Status -->
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                                            <div class="user-row" style="margin-bottom: 0;">
                                                <div class="avatar" style="background: #f1f5f9; color: #475569;">
                                                    <?php echo $initials; ?>
                                                </div>
                                                <div class="u-info">
                                                    <h4 style="margin: 0; font-size: 0.95rem; color: #0f172a;"><?php echo htmlspecialchars($name); ?></h4>
                                                    <p class="evacuee-meta" style="margin: 4px 0 0; font-size: 0.75rem; color: #64748b; display: flex; gap: 10px; align-items: center;">
                                                        <span><i data-lucide="users" style="width:12px; height:12px; vertical-align: -2px;"></i> <?php echo $history['group_size']; ?></span>
                                                        <span><i data-lucide="phone" style="width:12px; height:12px; vertical-align: -2px;"></i> <?php echo htmlspecialchars($history['phone_number'] ?? 'N/A'); ?></span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <span class="status-pill <?php echo $history['status']; ?>" style="font-size: 0.65rem; padding: 4px 10px; border-radius: 99px;">
                                                    <?php echo strtoupper(str_replace('_', ' ', $history['status'])); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Middle Row: Timeline -->
                                        <div style="background: #f8fafc; border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0;">
                                            <div style="flex: 1;">
                                                <div style="font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px;">Check-In</div>
                                                <div style="font-size: 0.8rem; font-weight: 600; color: #1e293b;"><?php echo $ci->format('M j, Y'); ?></div>
                                                <div style="font-size: 0.75rem; color: #64748b;"><?php echo $ci->format('h:i A'); ?></div>
                                            </div>
                                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 15px;">
                                                <div style="height: 1px; background: #cbd5e1; width: 40px; position: relative; display: flex; align-items: center; justify-content: center;">
                                                    <i data-lucide="arrow-right" style="width:14px; height:14px; color: #94a3b8; background: #f8fafc; padding: 0 4px;"></i>
                                                </div>
                                                <?php if ($durationStr): ?>
                                                    <span style="font-size: 0.65rem; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 2px 8px; border-radius: 6px; margin-top: 6px;">
                                                        <?php echo $durationStr; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="flex: 1; text-align: right;">
                                                <div style="font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px;">Check-Out</div>
                                                <div style="font-size: 0.8rem; font-weight: 600; color: #1e293b;">
                                                    <?php echo $history['checked_out_at'] ? (new DateTime($history['checked_out_at']))->format('M j, Y') : '—'; ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: #64748b;">
                                                    <?php echo $history['checked_out_at'] ? (new DateTime($history['checked_out_at']))->format('h:i A') : ''; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bottom Row: Rating -->
                                        <div style="display: flex; align-items: center; gap: 12px; padding-top: 4px; border-top: 1px dashed #e2e8f0; margin-top: 2px;">
                                            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Rating:</div>
                                            <?php if ($history['rating']): ?>
                                                <div class="stars-display" style="gap: 4px;">
                                                    <?php for ($i=1; $i<=5; $i++): 
                                                        if ($i <= $history['rating']): ?>
                                                            <i data-lucide="star" style="width: 16px; height: 16px; color: #f59e0b; fill: #f59e0b;"></i>
                                                        <?php else: ?>
                                                            <i data-lucide="star" style="width: 16px; height: 16px; color: #e2e8f0;"></i>
                                                        <?php endif; 
                                                    endfor; ?>
                                                </div>
                                                <?php if (!empty($history['review_text'])): ?>
                                                    <div style="flex: 1; font-size: 0.8rem; color: #475569; font-style: italic; background: #fffbeb; padding: 6px 12px; border-radius: 6px; border: 1px solid #fef3c7; border-left: 3px solid #f59e0b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($history['review_text']); ?>">
                                                        "<?php echo htmlspecialchars($history['review_text']); ?>"
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">No rating provided</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="history-empty">
                                    <i data-lucide="history"></i>
                                    <h4>No History Yet</h4>
                                    <p>Past evacuees will appear here once they check out.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- ESSENTIAL STOCK LEVELS (MOVED HERE) -->
                <section class="dashboard-card">
                    <div class="card-header-strip">
                        <h3 class="card-title">
                            <i data-lucide="package" class="title-icon"></i> STOCK LEVELS
                        </h3>
                        <button type="button" class="btn-edit-stock" onclick="toggleStockEditor()" title="Edit stock levels">
                            <i data-lucide="edit-2"></i>
                        </button>
                    </div>
                    
                    <!-- STOCK DISPLAY -->
                    <div id="stock-display" class="stock-display-view">
                        <?php 
                        $supplies = !empty($shelter['supplies']) ? json_decode($shelter['supplies'], true) : [];
                        $stockItems = [
                            'water' => ['icon' => 'droplet', 'color' => 'var(--accent-blue)', 'label' => 'Water', 'unit' => 'Gal', 'default' => 0],
                            'food' => ['icon' => 'utensils', 'color' => 'var(--accent-orange)', 'label' => 'Food', 'unit' => 'Packs', 'default' => 0],
                            'medical' => ['icon' => 'first-aid-kit', 'color' => 'var(--danger)', 'label' => 'Medical', 'unit' => 'Items', 'default' => 0],
                            'bedding' => ['icon' => 'blanket', 'color' => 'var(--accent-purple)', 'label' => 'Bedding', 'unit' => 'Sets', 'default' => 0]
                        ];
                        
                        foreach ($stockItems as $key => $item):
                            $qty = $supplies[$key]['qty'] ?? $item['default'];
                            $unit = $supplies[$key]['unit'] ?? $item['unit'];
                            $percentage = min(100, ($qty / 100) * 100); // Simple percentage calculation
                        ?>
                       <div class="stock-item-display">
    <div class="stock-name">
        <i data-lucide="<?php echo $item['icon']; ?>" style="color: <?php echo $item['color']; ?>"></i> 
        <?php echo $item['label']; ?>
    </div>
    <div class="stock-value"><?php echo $qty; ?> <span><?php echo $unit; ?></span></div>
    
    <div class="stock-progress-container">
        <div class="stock-bar-mini" style="background: <?php echo $item['color']; ?>; width: <?php echo $percentage; ?>%;"></div>
    </div>
</div>
                        <?php endforeach; ?>
                    </div>

                    <!-- STOCK EDITOR -->
                    <form id="stock-editor" class="stock-editor-form" style="display: none;">
                        <?php foreach ($stockItems as $key => $item): 
                            $qty = $supplies[$key]['qty'] ?? $item['default'];
                            $unit = $supplies[$key]['unit'] ?? $item['unit'];
                        ?>
                            <div class="stock-edit-group">
                                <label><?php echo $item['label']; ?></label>
                                <div class="stock-input-group">
                                    <input type="number" 
                                           id="stock-<?php echo $key; ?>-qty" 
                                           name="<?php echo $key; ?>_qty"
                                           value="<?php echo $qty; ?>" 
                                           min="0" 
                                           placeholder="Quantity">
                                    <select id="stock-<?php echo $key; ?>-unit" name="<?php echo $key; ?>_unit">
                                        <option <?php echo $unit == $item['unit'] ? 'selected' : ''; ?>><?php echo $item['unit']; ?></option>
                                        <?php if ($key == 'water'): ?>
                                            <option <?php echo $unit == 'Liters' ? 'selected' : ''; ?>>Liters</option>
                                        <?php elseif ($key == 'food'): ?>
                                            <option <?php echo $unit == 'Meals' ? 'selected' : ''; ?>>Meals</option>
                                            <option <?php echo $unit == 'Boxes' ? 'selected' : ''; ?>>Boxes</option>
                                        <?php elseif ($key == 'medical'): ?>
                                            <option <?php echo $unit == 'Sets' ? 'selected' : ''; ?>>Sets</option>
                                            <option <?php echo $unit == 'Units' ? 'selected' : ''; ?>>Units</option>
                                        <?php elseif ($key == 'bedding'): ?>
                                            <option <?php echo $unit == 'Pieces' ? 'selected' : ''; ?>>Pieces</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="stock-editor-actions">
                            <button type="button" class="btn-cancel-stock" onclick="toggleStockEditor()">
                                <i data-lucide="x"></i>CANCEL
                            </button>
                            <button type="submit" class="btn-save-stock">
                                <i data-lucide="check"></i>SAVE
                            </button>
                        </div>
                    </form>
                </section>

            </div>

            <!-- SIDEBAR -->
            <aside class="host-sidebar">
                <!-- SHELTER SETTINGS CONFIGURATION -->
                <div class="settings-panel config-panel">
                    <h3 class="panel-title">
                        <i data-lucide="settings"></i> Shelter Settings
                    </h3>
                    <form class="settings-form" id="shelter-settings-form">
                        <div class="input-group">
                            <label>SHELTER NAME</label>
                            <input type="text" 
                                   id="shelter-name" 
                                   value="<?php echo htmlspecialchars($shelter['shelter_name'] ?? ''); ?>" 
                                   placeholder="Enter shelter name">
                        </div>
                        
                        <div class="input-group">
                            <label>MAX CAPACITY</label>
                            <input type="number" 
                                   id="max-capacity" 
                                   value="<?php echo $shelter['max_capacity'] ?? 0; ?>" 
                                   placeholder="0" 
                                   min="0">
                        </div>
                        
                        <div class="input-group">
                            <label>CONTACT NUMBER</label>
                            <input type="tel" 
                                   id="contact-number" 
                                   value="<?php echo htmlspecialchars($shelter['contact_number'] ?? ''); ?>" 
                                   placeholder="+63 XXX XXX XXXX">
                        </div>

                        <div class="input-group">
                            <label>LOCATION</label>
                            <textarea id="location" 
                                      rows="3" 
                                      placeholder="Full address"><?php echo htmlspecialchars($shelter['location'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="input-group">
                            <label>AVAILABLE AMENITIES</label>
                            <div class="amenities-grid">
                                <?php 
                                $amenitiesList = !empty($shelter['amenities']) ? json_decode($shelter['amenities'], true) : [];
                                $allAmenities = ['Food', 'Medical', 'WiFi', 'Laundry', 'Shower', 'Beds'];
                                foreach ($allAmenities as $amenity):
                                ?>
                                    <label class="amenity-item">
                                        <input type="checkbox" 
                                               name="amenities[]" 
                                               value="<?php echo $amenity; ?>"
                                               <?php echo in_array($amenity, $amenitiesList) ? 'checked' : ''; ?>>
                                        <span>
                                            <?php 
                                            $icons = ['Food' => '🍔', 'Medical' => '🏥', 'WiFi' => '📡', 
                                                     'Laundry' => '👕', 'Shower' => '🚿', 'Beds' => '🛏️'];
                                            echo $icons[$amenity] . ' ' . $amenity;
                                            ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-update">
                            <i data-lucide="check"></i>
                            UPDATE SETTINGS
                        </button>
                    </form>
                </div>

                <!-- EMERGENCY STATUS PANEL -->
                <div class="settings-panel emergency-panel">
                    <h3 class="panel-title danger">
                        <i data-lucide="alert-triangle"></i> Emergency Options
                    </h3>
                    <div class="emergency-content">
                        <p class="emergency-description">
                            If your shelter becomes unsafe (rising flood levels, structural damage, etc.), you can relinquish your host status to request shelter elsewhere.
                        </p>
                        <div class="emergency-warning-box">
                            <p class="emergency-warning-title">⚠️ This action will:</p>
                            <ul class="emergency-warning-list">
                                <li>Deactivate your shelter</li>
                                <li>Stop accepting new evacuees</li>
                                <li>Allow you to request shelter</li>
                            </ul>
                        </div>
                        <button type="button"
                                id="btn-relinquish-host"
                                onclick="confirmRelinquishStatus()"
                                class="btn-update btn-danger">
                            <i data-lucide="shield-off"></i>
                            RELINQUISH HOST STATUS
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- RELINQUISH CONFIRMATION MODAL -->
<div id="relinquishModal" class="modal-overlay" style="display: none;">
    <div class="protocol-modal">
        <div class="modal-header">
            <h3 class="modal-title">
                <i data-lucide="alert-triangle"></i> Confirm Emergency Action
            </h3>
            <button type="button" onclick="closeRelinquishModal()">
                <i data-lucide="x"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="checkout-warning-box">
                <p class="checkout-warning-title">Emergency Relinquishment</p>
                <p class="checkout-warning-sub">This will immediately deactivate your shelter and allow you to seek safety as an evacuee.</p>
            </div>

            <div class="checkout-info-box">
                <ul >
                    <li><strong>Deactivate</strong> shelter visibility</li>
                    <li><strong>Notify</strong> current occupants</li>
                    <li><strong>Enable</strong> your request for other shelters</li>
                </ul>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeRelinquishModal()">
                    CANCEL
                </button>
                <button type="button" 
                        class="btn-confirm" 
                        onclick="executeRelinquish(this)">
                    <i data-lucide="shield-off"></i> CONFIRM
                </button>
            </div>
        </div>
    </div>
</div>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

<!-- QR SCANNER MODAL -->
<div id="qrScannerModal" class="modal-overlay" style="display: none;">
    <div class="protocol-modal">
        <div class="qr-modal-header">
            <h3 class="qr-modal-title">
                <i data-lucide="qr-code"></i> QR CODE SCANNER
            </h3>
            <button type="button" class="qr-modal-close-btn" onclick="closeQrScannerModal()">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="qr-modal-body">
            <div id="qrReader" class="qr-reader-wrapper"></div>
            <p class="qr-hint">Align the evacuee's QR code within the frame to scan</p>
            <div id="qrResultPanel" class="qr-result-panel">
                <div id="qrResultContent"></div>
            </div>
        </div>
    </div>
</div>
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/host.js"></script>

</body>
</html>