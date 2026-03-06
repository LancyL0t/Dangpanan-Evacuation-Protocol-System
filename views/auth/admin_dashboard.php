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
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/portal.js" defer></script>
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

    <main class="portal-container">

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
            </div>
        </header>

        <!-- ── Stats Strip ── -->
        <div class="stats-strip">
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

        <!-- ── Tab Navigation ── -->
        <div class="tabs-nav">
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

        <!-- ══ TAB: OVERVIEW ══ -->
        <div id="overview-tab" class="tab-content active">
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

                <!-- Shelter capacity bars -->
                <h4 class="section-label">Shelter Capacity</h4>
                <?php
                // $shelterBars provided by AlertController::adminDashboard()
                foreach ($shelterBars as $sb):
                    $pct      = $sb['max_capacity'] > 0 ? round($sb['current_capacity'] / $sb['max_capacity'] * 100) : 0;
                    $barColor = $pct >= 90 ? '#ef4444' : ($pct >= 60 ? '#f59e0b' : '#10b981');
                ?>
                <div class="shelter-bar-row">
                    <div class="shelter-bar-meta">
                        <span class="shelter-bar-name"><?= htmlspecialchars($sb['shelter_name']) ?></span>
                        <span class="shelter-bar-count"><?= $sb['current_capacity'] ?>/<?= $sb['max_capacity'] ?></span>
                    </div>
                    <div class="cap-bar">
                        <div class="cap-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══ TAB: USERS ══ -->
        <div id="users-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>User Management</h3>
                    <div class="head-actions">
                        <input type="search" class="table-search" placeholder="Search users..." id="userSearch">
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
                        <input type="search" class="table-search" placeholder="Search shelters…" id="shelterSearch"
                               oninput="filterTable('sheltersTable',this.value)">
                        <select id="shelterStatusFilter" class="filter-select"
                                onchange="filterTable('sheltersTable',document.getElementById('shelterSearch').value)">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
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
                        <input type="search" class="table-search" placeholder="Search alerts…" id="alertSearch"
                               oninput="filterTable('alertsTable',this.value)">
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
                        <input type="search" class="table-search" placeholder="Search requests…" id="reqSearch"
                               oninput="filterTable('requestsTable',this.value)">
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

        <!-- ══ TAB: OCCUPANTS ══ -->
        <div id="occupants-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>Current Occupants (System-Wide)</h3>
                    <input type="search" class="table-search" placeholder="Search occupants…" id="occSearch"
                           oninput="filterTable('occupantsTable',this.value)">
                </div>
                <div class="table-wrap">
                    <table id="occupantsTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>Name</th><th>Email</th>
                                <th>Shelter</th><th>Group Size</th><th>Checked In</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="7" class="td-empty">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ TAB: LOGS ══ -->
        <div id="logs-tab" class="tab-content">
            <div class="panel">
                <div class="panel-head">
                    <h3>Activity Log</h3>
                    <input type="search" class="table-search" placeholder="Search logs…" id="logSearch"
                           oninput="filterLogList(this.value)">
                </div>
                <div id="logList" class="log-list">
                    <p class="state-empty">Loading…</p>
                </div>
            </div>
        </div>

    </main>

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