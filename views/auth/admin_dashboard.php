<?php
/**
 * views/auth/admin_dashboard.php
 * VIEW ONLY — no logic, no inline CSS, no inline JS.
 * Data prepared by AlertController::adminDashboard()
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <!-- Admin Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.datatables.net/v/dt/dt-2.0.2/datatables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/v/dt/dt-2.0.2/datatables.min.js"></script>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/portal.js" defer></script>
    
    <!-- PHP Data Injection for Chart.js -->
    <script>
        window.SHELTER_CAPACITY_DATA = <?php echo json_encode($shelterBars); ?>;
    </script>
</head>
<body class="light-portal">
    <?php require 'views/partials/nav_portal.php'; ?>

    <!-- Toast container -->
    <div id="toast-container"></div>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="confirm-icon" id="confirmIcon">🗑️</div>
            <h3 class="confirm-title" id="confirmTitle">Are you sure?</h3>
            <p class="confirm-msg" id="confirmMsg">This action cannot be undone.</p>
            <div class="confirm-btns">
                <button class="primary-btn" id="confirmYes">Yes, Delete</button>
                <button class="secondary-btn btn-confirm-cancel" onclick="closeConfirm()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2>Command Center</h2>
            </div>
            <div class="tabs-nav sidebar-nav">
                <button class="tab-btn active" data-tab="overview">
                    <i data-lucide="layout-dashboard"></i> Overview
                </button>
                <button class="tab-btn" data-tab="users">
                    <i data-lucide="users"></i> Users
                    <span class="tab-badge"><?= $totalUsers ?></span>
                </button>
                <button class="tab-btn" data-tab="shelters">
                    <i data-lucide="home"></i> Shelters
                </button>
                <button class="tab-btn" data-tab="alerts">
                    <i data-lucide="bell"></i> Alerts
                    <?php if ($totalAlerts): ?>
                        <span class="tab-badge"><?= $totalAlerts ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" data-tab="verification">
                    <i data-lucide="shield"></i> Verification
                    <span class="tab-badge amber" id="verifyBadge" style="display:none;"></span>
                </button>
                <button class="tab-btn" data-tab="requests">
                    <i data-lucide="file-text"></i> Requests
                    <?php if ($pendingCount): ?>
                        <span class="tab-badge amber"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" data-tab="occupants">
                    <i data-lucide="users-round"></i> Occupants
                    <?php if ($totalOccupants): ?>
                        <span class="tab-badge blue"><?= $totalOccupants ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" data-tab="logs">
                    <i data-lucide="scroll-text"></i> Activity Log
                </button>
            </div>
        </aside>

        <main class="portal-container admin-main">

            <!-- ── Header ── -->
            <header class="portal-header">
                <div>
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="user-id">DANGPANAN System Administration</p>
                </div>
                <div class="header-exports">
                    <a href="index.php?route=admin-export&export_type=users" class="export-btn">
                        <i data-lucide="download"></i> Export Users
                    </a>
                    <a href="index.php?route=admin-export&export_type=shelters" class="export-btn">
                        <i data-lucide="download"></i> Shelters
                    </a>
                    <a href="index.php?route=admin-export&export_type=requests" class="export-btn">
                        <i data-lucide="download"></i> Requests
                    </a>
                    <a href="index.php?route=system_summary" target="_blank" class="export-btn" style="background: var(--primary); color: #fff; border: none;">
                        <i data-lucide="file-text"></i> System Summary (PDF)
                    </a>
                </div>
            </header>



        <!-- ══ TAB: OVERVIEW ══ -->
        <div id="overview-tab" class="tab-content active">
            <!-- Stats Strip (Now inside Overview) -->
            <div class="stats-strip" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-red"><i data-lucide="users"></i></div>
                    <div>
                        <div class="stat-val"><?= $totalUsers ?></div>
                        <div class="stat-lbl">Total Users</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-green"><i data-lucide="home"></i></div>
                    <div>
                        <div class="stat-val"><?= $shelterStats['active_shelters'] ?></div>
                        <div class="stat-lbl">Active Shelters</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-amber"><i data-lucide="clock"></i></div>
                    <div>
                        <div class="stat-val"><?= $pendingCount ?></div>
                        <div class="stat-lbl">Pending Requests</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-blue"><i data-lucide="users-round"></i></div>
                    <div>
                        <div class="stat-val"><?= $totalOccupants ?></div>
                        <div class="stat-lbl">Current Occupants</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-red"><i data-lucide="bell"></i></div>
                    <div>
                        <div class="stat-val"><?= $totalAlerts ?></div>
                        <div class="stat-lbl">Active Alerts</div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h3 class="panel-section-title">System Overview</h3>
                <div class="overview-grid">
                    <div class="ov-card">
                        <p>Evacuees (Citizens)</p>
                        <h3><?= $evacueeCount ?></h3>
                    </div>
                    <div class="ov-card">
                        <p>Total Shelters</p>
                        <h3><?= $shelterStats['total_shelters'] ?></h3>
                    </div>
                    <div class="ov-card">
                        <p>Capacity Utilization</p>
                        <h3><?= $utilization ?>%</h3>
                    </div>
                    <div class="ov-card">
                        <p>Critical Alerts</p>
                        <h3 class="text-danger"><?= $alertCounts['critical'] ?></h3>
                    </div>
                    <div class="ov-card">
                        <p>Warnings</p>
                        <h3 class="text-warning"><?= $alertCounts['warning'] ?></h3>
                    </div>
                    <div class="ov-card">
                        <p>Pending Requests</p>
                        <h3 class="text-amber"><?= $pendingCount ?></h3>
                    </div>
                </div>

                <div class="overview-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 2rem;">
                    <!-- Left Column: Charts -->
                    <div class="charts-section">
                        <div class="charts-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="chart-container" style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; min-height: 320px; position: relative;">
                                <h4 class="section-label" style="margin-bottom: 1rem;">Capacity Allocation</h4>
                                <div style="position: relative; height: 230px; width: 100%;">
                                    <canvas id="capacityDonutChart"></canvas>
                                </div>
                            </div>
                            <div class="chart-container" style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; min-height: 320px; position: relative;">
                                <h4 class="section-label" style="margin-bottom: 1rem;">Top Active Shelters</h4>
                                <div style="position: relative; height: 230px; width: 100%;">
                                    <canvas id="topSheltersBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Live Ticker -->
                    <div class="ticker-section" style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; display: flex; flex-direction: column; overflow: hidden; height: 350px;">
                        <h3 class="panel-section-title" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                            <i data-lucide="radio" style="color: var(--primary);"></i> Live Activity Feed
                        </h3>
                        <div id="liveActivityTicker" class="ticker-container" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; padding-right: 0.5rem;">
                            <p class="text-muted" style="font-size: 0.85rem;">Listening for events...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ TAB: USERS ══ -->
        <div id="users-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>User Management</h3>
                    <div class="head-actions">
                        <a href="index.php?route=report_users" target="_blank" class="secondary-btn" style="margin-right: 0.5rem; text-decoration:none;">
                            <i data-lucide="file-down"></i> Export PDF
                        </a>
                        <button class="primary-btn" id="newUserBtn">
                            <i data-lucide="plus-circle"></i> New User
                        </button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>Name</th><th>Email</th>
                                <th>Phone</th><th>Role</th><th>ID Doc</th><th>Verified</th><th>Created</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="9" class="td-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ TAB: SHELTERS ══ -->
        <div id="shelters-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>Shelter Management</h3>
                    <div class="head-actions">
                        <select id="shelterStatusFilter" class="filter-select"
                                onchange="filterTable('sheltersTable',document.getElementById('shelterSearch').value)">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <a href="index.php?route=report_shelters" target="_blank" class="secondary-btn" style="text-decoration:none;">
                            <i data-lucide="file-down"></i> Export PDF
                        </a>
                        <button class="primary-btn" id="newShelterBtn">
                            <i data-lucide="plus-circle"></i> New Shelter
                        </button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="sheltersTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>Name</th><th>Location</th><th>Capacity</th>
                                <th>Occupancy</th><th>Host</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="8" class="td-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ TAB: ALERTS ══ -->
        <div id="alerts-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>Alert Management</h3>
                    <div class="head-actions">
                        <a href="index.php?route=report_alerts" target="_blank" class="secondary-btn" style="margin-right: 0.5rem; text-decoration:none;">
                            <i data-lucide="file-down"></i> Export PDF
                        </a>
                        <button class="primary-btn" id="newAlertBtn">
                            <i data-lucide="plus-circle"></i> New Alert
                        </button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="alertsTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>Type</th><th>Title</th><th>Source</th>
                                <th>Affected Area</th><th>Status</th><th>Created</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="8" class="td-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ TAB: REQUESTS ══ -->
        <div id="requests-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>All Shelter Requests</h3>
                    <div class="head-actions">
                        <a href="index.php?route=report_requests" target="_blank" class="secondary-btn" style="margin-right: 0.5rem; text-decoration:none;">
                            <i data-lucide="file-down"></i> Export PDF
                        </a>
                        <select id="reqStatusFilter" class="filter-select"
                                onchange="filterTable('requestsTable',document.getElementById('reqSearch').value)">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="declined">Declined</option>
                            <option value="checked_in">Checked In</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="requestsTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>Evacuee</th><th>Shelter</th><th>Group</th>
                                <th>Status</th><th>Approval Code</th><th>Created</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="8" class="td-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ TAB: VERIFICATION ══ -->
        <div id="verification-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>Host Verification Queue</h3>
                    <div class="head-actions">
                        <a href="index.php?route=report_verification" target="_blank" class="secondary-btn" style="text-decoration:none;">
                            <i data-lucide="file-down"></i> Export PDF
                        </a>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="verificationTable">
                        <thead>
                            <tr>
                                <th>Name</th><th>Email</th><th>Phone</th><th>ID Doc</th><th>Created</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6" class="td-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ TAB: OCCUPANTS ══ -->
        <div id="occupants-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>Occupant Management</h3>
                    <div class="head-actions">
                        <a href="index.php?route=report_occupants" target="_blank" class="secondary-btn" style="margin-right: 0.5rem; text-decoration:none;">
                            <i data-lucide="file-down"></i> Export PDF
                        </a>
                        <button class="secondary-btn" id="syncCapacitiesBtn" title="Repair capacity discrepancies">
                            <i data-lucide="refresh-cw"></i> Sync Database
                        </button>
                    </div>
                </div>
                <div id="occupantsGroupContainer" class="shelter-groups-wrapper">
                    <div class="state-empty" style="padding: 4rem 1rem;">
                        <i data-lucide="loader-2" class="spin" style="width: 48px; height: 48px; color: var(--primary); margin-bottom: 1rem;"></i>
                        <p>Loading occupancy data...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ TAB: LOGS ══ -->
        <div id="logs-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>Activity Log</h3>
                    <div class="head-actions">
                        <a href="index.php?route=report_logs" target="_blank" class="secondary-btn" style="text-decoration:none;">
                            <i data-lucide="file-down"></i> Export PDF
                        </a>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="logsTable">
                        <thead>
                            <tr>
                                <th>Time</th><th>User</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" class="td-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
    </div>

    <!-- ══ USER MODAL ══ -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="userModalTitle">New User</h2>
                <button class="modal-close" onclick="closeUserModal()">×</button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="firstName" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Initial</label>
                        <input type="text" id="middleInitial" maxlength="5" placeholder="e.g. A.">
                    </div>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="lastName">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" id="phone">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select id="role" required>
                        <option value="Citizen">Citizen</option>
                        <option value="Host">Host</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label>Password *</label>
                    <input type="password" id="password">
                </div>
                <div class="btn-group">
                    <button type="submit" class="primary-btn">Save</button>
                    <button type="button" class="secondary-btn" onclick="closeUserModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ SHELTER MODAL ══ -->
    <div id="shelterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="shelterModalTitle">New Shelter</h2>
                <button class="modal-close" onclick="closeShelterModal()">×</button>
            </div>
            <form id="shelterForm">
                <input type="hidden" id="shelterId">
                <div class="form-group">
                    <label>Shelter Name *</label>
                    <input type="text" id="shelterName" required>
                </div>
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" id="location" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Latitude</label>
                        <input type="text" id="latitude">
                    </div>
                    <div class="form-group">
                        <label>Longitude</label>
                        <input type="text" id="longitude">
                    </div>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" id="contactNumber">
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Max Capacity *</label>
                        <input type="number" id="maxCapacity" required value="50">
                    </div>
                    <div class="form-group">
                        <label>Current Occupancy</label>
                        <input type="number" id="currentCapacity" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="isActive">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" class="primary-btn">Save</button>
                    <button type="button" class="secondary-btn" onclick="closeShelterModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ ALERT MODAL ══ -->
    <div id="alertModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="alertModalTitle">New Alert</h2>
                <button class="modal-close" onclick="closeAlertModal()">×</button>
            </div>
            <form id="alertForm">
                <input type="hidden" id="alertId">
                <div class="form-group">
                    <label>Type *</label>
                    <select id="alertType" required>
                        <option value="critical">🔴 Critical</option>
                        <option value="warning">🟡 Warning</option>
                        <option value="info">🔵 Info / Update</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="alertTitle" required placeholder="e.g. Typhoon Signal No. 3">
                </div>
                <div class="form-group">
                    <label>Body / Details *</label>
                    <textarea id="alertBody" rows="4" required placeholder="Full details of the alert…"></textarea>
                </div>
                <div class="form-group">
                    <label>Source</label>
                    <input type="text" id="alertSource" placeholder="e.g. PAGASA, MDRRMO, City Gov">
                </div>
                <div class="form-group">
                    <label>Affected Areas (comma-separated)</label>
                    <input type="text" id="alertArea" placeholder="e.g. Tangub, Banago, Mandalagan">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="alertActive">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" class="primary-btn">Save Alert</button>
                    <button type="button" class="secondary-btn" onclick="closeAlertModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>