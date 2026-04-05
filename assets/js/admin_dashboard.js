// ============================================================
// DANGPANAN Admin Dashboard JS — Full rewrite
// ============================================================

// ── Toast Notification System ──────────────────────────────
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slide-out 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
function showSuccess(msg) { showToast(msg, 'success'); }
function showError(msg)   { showToast(msg, 'error'); }

// ── Confirm Delete Modal ───────────────────────────────────
let _confirmCallback = null;
function showConfirm(title, msg, icon, btnLabel, callback) {
    document.getElementById('confirmTitle').textContent  = title;
    document.getElementById('confirmMsg').textContent    = msg;
    document.getElementById('confirmIcon').textContent   = icon || '🗑️';
    document.getElementById('confirmYes').textContent    = btnLabel || 'Yes, Delete';
    document.getElementById('confirmModal').style.display = 'block';
    _confirmCallback = callback;
}
function closeConfirm() {
    document.getElementById('confirmModal').style.display = 'none';
    _confirmCallback = null;
}
document.getElementById('confirmYes').onclick = function() {
    if (_confirmCallback) _confirmCallback();
    closeConfirm();
};

// ── Action Menu Dropdowns ──────────────────────────────────
function toggleActionMenu(e, btn) {
    e.stopPropagation();
    const menu = btn.nextElementSibling;
    const isActive = menu.classList.contains('active');
    
    // Close all other open menus
    document.querySelectorAll('.action-dropdown.active').forEach(m => m.classList.remove('active'));
    
    if (!isActive) {
        menu.classList.add('active');
    }
}

// Global click listener for closing menus and modals
window.addEventListener('click', e => {
    // Close Action Dropdowns
    if (!e.target.closest('.action-menu')) {
        document.querySelectorAll('.action-dropdown.active').forEach(m => m.classList.remove('active'));
    }
    
    // Close Modals
    ['userModal','shelterModal','alertModal','confirmModal'].forEach(id => {
        const m = document.getElementById(id);
        if (m && e.target === m) m.style.display = 'none';
    });
});

// ── Tab Switching ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns     = document.querySelectorAll('.tab-btn[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    const loaders = { users: false, shelters: false, alerts: false, requests: false, occupants: false, logs: false, verification: false };

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const tab = this.dataset.tab;
            const target = document.getElementById(tab + '-tab');
            if (target) target.classList.add('active');
            
            if (tab === 'overview') {
                refreshDashboardStats().then(() => initCharts());
            } else if (!loaders[tab]) {
                loadTab(tab); 
                loaders[tab] = true;
            }
            setTimeout(() => lucide.createIcons(), 50);
        });
    });

    // Load initial tabs
    loadTab('users');
    loadTab('shelters');
    loadTab('alerts');
    setTimeout(() => lucide.createIcons(), 100);

    // Wire buttons
    document.getElementById('newUserBtn')   ?.addEventListener('click', openNewUserModal);
    document.getElementById('newShelterBtn')?.addEventListener('click', openNewShelterModal);
    document.getElementById('newAlertBtn')  ?.addEventListener('click', openNewAlertModal);
    document.getElementById('syncCapacitiesBtn')?.addEventListener('click', repairCapacities);

    // Initialize Dashboard Charts and Ticker
    initCharts();
    initLiveTicker();
});

function loadTab(tab) {
    if (tab === 'users')     loadUsers();
    else if (tab === 'shelters')  loadShelters();
    else if (tab === 'alerts')    loadAlerts();
    else if (tab === 'requests')  loadRequests();
    else if (tab === 'occupants') loadOccupants();
    else if (tab === 'logs')      loadLogs();
    else if (tab === 'verification') loadVerification();
}

// ── Dashboard Charts (Chart.js) ────────────────────────────
window.adminCharts = { donut: null, bar: null };

function initCharts() {
    if (typeof Chart === 'undefined' || !window.SHELTER_CAPACITY_DATA || !document.getElementById('capacityDonutChart')) return;
    
    const data = window.SHELTER_CAPACITY_DATA;
    if (!data || data.length === 0) return;

    // Set globally to avoid re-setting on each call
    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = '"Inter", sans-serif';

    // 1. Capacity Donut Chart (Total vs Available)
    const totalMax  = data.reduce((sum, s) => sum + (parseInt(s.max_capacity) || 0), 0);
    const totalCurr = data.reduce((sum, s) => sum + (parseInt(s.current_capacity) || 0), 0);
    const available = Math.max(0, totalMax - totalCurr);

    const donutCtx = document.getElementById('capacityDonutChart').getContext('2d');
    if (window.adminCharts.donut) window.adminCharts.donut.destroy();

    window.adminCharts.donut = new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Occupied', 'Available'],
            datasets: [{
                data: [totalCurr, available],
                backgroundColor: ['#ef4444', '#10b981'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            },
            cutout: '75%'
        }
    });

    // 2. Top Shelters Bar Chart
    const topShelters = data.slice(0, 5); 
    const labels      = topShelters.map(s => s.shelter_name.length > 15 ? s.shelter_name.substring(0,15)+'...' : s.shelter_name);
    const chartData   = topShelters.map(s => parseInt(s.current_capacity) || 0);
    const maxData     = topShelters.map(s => parseInt(s.max_capacity) || 0);

    const barCtx = document.getElementById('topSheltersBarChart').getContext('2d');
    if (window.adminCharts.bar) window.adminCharts.bar.destroy();

    window.adminCharts.bar = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Current Occupancy',
                    data: chartData,
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                },
                {
                    label: 'Max Capacity',
                    data: maxData,
                    backgroundColor: '#e2e8f0',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            }
        }
    });
}

// ── Live Activity Ticker ───────────────────────────────────
function initLiveTicker() {
    const ticker = document.getElementById('liveActivityTicker');
    if (!ticker) return;

    function fetchTickerLogs() {
        fetch('index.php?route=api-logs')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.logs) {
                    const topLogs = data.logs.slice(0, 15);
                    if (topLogs.length === 0) {
                        ticker.innerHTML = '<p class="state-empty" style="padding: 1rem; text-align: center; color: var(--text-muted);">No recent activity.</p>';
                        return;
                    }
                    ticker.innerHTML = topLogs.map(log => {
                        return `
                        <div class="ticker-item" style="font-size: 0.82rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                            <div style="color: var(--text-muted); font-size: 0.72rem; margin-bottom: 0.2rem; display: flex; align-items: center; gap: 0.3rem;">
                                <i data-lucide="clock" style="width: 12px; height: 12px;"></i> ${timeAgo(log.created_at)}
                            </div>
                            <div style="font-weight: 500; color: var(--text); margin-bottom: 0.2rem;">${log.action}</div>
                            <div style="color: var(--primary); font-size: 0.75rem; font-weight: 600;">System User: ${log.user_name || 'System'}</div>
                        </div>`;
                    }).join('');
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            })
            .catch(() => {
                ticker.innerHTML = '<p class="text-danger" style="font-size:0.8rem; padding: 1rem; text-align: center;">Live feed disconnected.</p>';
            });
    }

    fetchTickerLogs();
    // Poll every 10 seconds
    setInterval(fetchTickerLogs, 10000);
}

// ── DataTables System ───────────────────────────────────────
function initDataTable(tableId) {
    if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') return;
    const table = $('#' + tableId);
    if (!table.length) return;
    
    if ($.fn.DataTable.isDataTable(table)) {
        table.DataTable().destroy();
    }
    
    const dt = table.DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        bLengthChange: true,
        bFilter: true,     
        bInfo: true,
        bAutoWidth: false,
        language: { search: "", searchPlaceholder: "Search records..." },
        dom: '<"table-top-actions"lf>rt<"table-bottom-actions"ip><"clear">',
        ordering: false // Disable default sort to keep SQL order initially 
    });

    if (tableId === 'sheltersTable') {
        const sf = $('#shelterStatusFilter');
        sf.off('change').on('change', function() { dt.columns(6).search(this.value).draw(); });
    } else if (tableId === 'requestsTable') {
        const rf = $('#reqStatusFilter');
        rf.off('change').on('change', function() { dt.columns(4).search(this.value).draw(); });
    }
}

// ── Date formatter ─────────────────────────────────────────
function fmtDate(d) {
    if (!d) return 'N/A';
    return new Date(d).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
}
function fmtDateTime(d) {
    if (!d) return 'N/A';
    return new Date(d).toLocaleString('en-US', { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
}
function timeAgo(d) {
    if (!d) return 'N/A';
    const diff = Date.now() - new Date(d).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 1)  return 'Just now';
    if (m < 60) return m + 'm ago';
    const h = Math.floor(m / 60);
    if (h < 24) return h + 'h ago';
    return Math.floor(h / 24) + 'd ago';
}

// ── Role tag helper ────────────────────────────────────────
function roleTag(role) {
    const cls = { Citizen:'tag-citizen', Host:'tag-host', Admin:'tag-admin' }[role] || '';
    return `<span class="live-tag ${cls}">${role}</span>`;
}
function statusTag(s) {
    const cls = { pending:'tag-pending', approved:'tag-approved', declined:'tag-declined',
                  checked_in:'tag-checked_in', completed:'tag-completed',
                  active:'tag-active', inactive:'tag-inactive',
                  critical:'tag-critical', warning:'tag-warning', info:'tag-info' }[s] || 'tag-inactive';
    return `<span class="live-tag ${cls}">${s.replace('_',' ')}</span>`;
}

// ══════════════════════════════════════════
// USERS CRUD
// ══════════════════════════════════════════
let _allUsers = [];
function loadUsers() {
    fetch('index.php?route=admin-get-users')
        .then(r => r.json()).then(data => {
            if (data.success) { 
                _allUsers = data.users; 
                displayUsers(data.users); 
                updateVerificationBadge(data.users);
                // Also update verification table if it's currently loaded
                if (document.getElementById('verification-tab').classList.contains('active')) {
                    displayVerification(data.users);
                }
            }
            else showError('Failed to load users');
        }).catch(() => showError('Error loading users'));
}

function displayUsers(users) {
    const tbody = document.querySelector('#usersTable tbody');
    if (!users.length) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;">No users found</td></tr>'; return; }
    tbody.innerHTML = users.map(u => {
        const mi = u.middle_initial ? ' ' + u.middle_initial + ' ' : ' ';
        const fullName = (u.first_name + mi + (u.last_name || '')).trim();
        const verifiedTag = u.is_verified == 1
            ? `<span class="live-tag tag-active">✔ Verified</span>`
            : `<span class="live-tag tag-inactive">Unverified</span>`;
        const idLink = u.gov_id_url && u.gov_id_url !== 'pending'
            ? `<a href="${u.gov_id_url}" target="_blank" style="color:#3b82f6;font-size:0.78rem;text-decoration:underline;">View ID</a>`
            : `<span style="color:#94a3b8;font-size:0.78rem;">${u.gov_id_url === 'pending' ? 'Pending' : '—'}</span>`;
        return `<tr>
            <td style="color:#94a3b8;font-size:0.75rem;">#${u.user_id}</td>
            <td style="font-weight:600;">${fullName}</td>
            <td>${u.email}</td>
            <td>${u.phone_number || '<span style="color:#94a3b8;">—</span>'}</td>
            <td>${roleTag(u.role)}</td>
            <td>${idLink}</td>
            <td>${verifiedTag}</td>
            <td style="color:#94a3b8;font-size:0.8rem;">${fmtDate(u.created_at)}</td>
            <td>
                <div class="action-menu">
                    <button class="action-dot-btn" onclick="toggleActionMenu(event, this)">
                        <i data-lucide="more-vertical"></i>
                    </button>
                    <div class="action-dropdown">
                        <button class="action-dropdown-item" onclick="editUser(${u.user_id})">
                            <i data-lucide="edit-3"></i> Edit User
                        </button>
                        <button class="action-dropdown-item" onclick="toggleVerifyUser(${u.user_id},'${fullName.replace(/'/g,"\\'")}',${u.is_verified})">
                            <i data-lucide="${u.is_verified == 1 ? 'user-minus' : 'user-check'}"></i> ${u.is_verified == 1 ? 'Unverify' : 'Verify Host'}
                        </button>
                        ${u.role === 'Host' && u.is_verified == 1 ? `
                        <a href="index.php?route=generate_cert&user_id=${u.user_id}" target="_blank" class="action-dropdown-item">
                            <i data-lucide="award"></i> Host Certification (PDF)
                        </a>
                        ` : ''}
                        <button class="action-dropdown-item text-danger" onclick="confirmDeleteUser(${u.user_id},'${fullName.replace(/'/g,"\\'")}')">
                            <i data-lucide="trash-2"></i> Delete User
                        </button>
                    </div>
                </div>
            </td>
        </tr>`;
    }).join('');
    setTimeout(() => { lucide.createIcons(); initDataTable('usersTable'); }, 50);
}

function openNewUserModal() {
    document.getElementById('userModalTitle').textContent = 'New User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('middleInitial').value = '';
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('password').required = true;
    document.getElementById('userModal').style.display = 'block';
}
function editUser(id) {
    const u = _allUsers.find(x => x.user_id == id);
    if (!u) return;
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userId').value        = u.user_id;
    document.getElementById('firstName').value    = u.first_name;
    document.getElementById('middleInitial').value = u.middle_initial || '';
    document.getElementById('lastName').value      = u.last_name || '';
    document.getElementById('email').value         = u.email;
    document.getElementById('phone').value         = u.phone_number || '';
    document.getElementById('role').value          = u.role;
    document.getElementById('passwordGroup').style.display = 'none';
    document.getElementById('password').required = false;
    document.getElementById('userModal').style.display = 'block';
}
function closeUserModal() { document.getElementById('userModal').style.display = 'none'; }

function confirmDeleteUser(id, name) {
    showConfirm('Delete User?', `This will permanently delete "${name}". This cannot be undone.`, '👤', 'Delete User', () => deleteUser(id));
}
function toggleVerifyUser(id, name, currentState) {
    const action = currentState == 1 ? 'unverify' : 'verify';
    const icon   = currentState == 1 ? '🔓' : '🪪';
    const label  = currentState == 1 ? 'Yes, Unverify' : 'Yes, Verify';
    const msg    = currentState == 1
        ? `Remove ID verification for "${name}"?`
        : `Mark "${name}" as ID-verified? This confirms their government ID is valid.`;
    showConfirm(currentState == 1 ? 'Remove Verification?' : 'Verify User ID?', msg, icon, label, () => {
        fetch('index.php?route=admin-verify-user', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: id})
        }).then(r => r.json()).then(d => {
            if (d.success) {
                showSuccess(d.is_verified ? `${name} is now verified ✔` : `${name} verification removed`);
                loadUsers();
                refreshDashboardStats(); // Sync overview counts
            } else {
                showError(d.message || 'Failed');
            }
        });
    });
}
function deleteUser(id) {
    fetch('index.php?route=admin-delete-user', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({user_id:id})
    }).then(r=>r.json()).then(d => {
        if (d.success) { 
            showSuccess('User deleted'); 
            loadUsers();
            refreshDashboardStats();
        }
        else showError(d.message || 'Failed');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('userForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = {
            user_id:        document.getElementById('userId').value,
            first_name:     document.getElementById('firstName').value,
            middle_initial: document.getElementById('middleInitial').value,
            last_name:      document.getElementById('lastName').value,
            email:          document.getElementById('email').value,
            phone_number:   document.getElementById('phone').value,
            role:           document.getElementById('role').value,
        };
        const pwd = document.getElementById('password').value;
        if (pwd) data.password = pwd;
        const route = data.user_id ? 'admin-update-user' : 'admin-create-user';
        fetch(`index.php?route=${route}`, {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)
        }).then(r=>r.json()).then(d => {
            if (d.success) { 
                showSuccess(d.message); 
                closeUserModal(); 
                loadUsers();
                refreshDashboardStats(); 
            }
            else showError(d.message || 'Failed');
        });
    });
});
// ══════════════════════════════════════════
// HOST VERIFICATION QUEUE
// ══════════════════════════════════════════
function updateVerificationBadge(users) {
    const queueCount = users.filter(u => u.role === 'Host' && u.is_verified == 0).length;
    const badgeSpan = document.getElementById('verifyBadge');
    if (badgeSpan) {
        badgeSpan.textContent = queueCount > 0 ? queueCount : '';
        badgeSpan.style.display = queueCount > 0 ? 'inline-block' : 'none';
    }
}

function loadVerification() {
    if (!_allUsers.length) {
        loadUsers(); // This will automatically call displayVerification if tab is active
    } else {
        displayVerification(_allUsers);
    }
}

function displayVerification(users) {
    const queue = users.filter(u => u.role === 'Host' && u.is_verified == 0);
    const tbody = document.querySelector('#verificationTable tbody');
    if (!queue.length) { 
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#94a3b8;">No hosts awaiting verification</td></tr>'; 
        return; 
    }
    
    tbody.innerHTML = queue.map(u => {
        const mi = u.middle_initial ? ' ' + u.middle_initial + ' ' : ' ';
        const fullName = (u.first_name + mi + (u.last_name || '')).trim();
        const idLink = u.gov_id_url && u.gov_id_url !== 'pending'
            ? `<a href="${u.gov_id_url}" target="_blank" style="color:#3b82f6;font-size:0.78rem;text-decoration:underline;">View ID Document</a>`
            : `<span style="color:#94a3b8;font-size:0.78rem;">${u.gov_id_url === 'pending' ? 'Pending Upload' : '—'}</span>`;
            
        return `<tr>
            <td style="font-weight:600;">${fullName}</td>
            <td>${u.email}</td>
            <td>${u.phone_number || '<span style="color:#94a3b8;">—</span>'}</td>
            <td>${idLink}</td>
            <td style="color:#94a3b8;font-size:0.8rem;">${fmtDate(u.created_at)}</td>
            <td>
                <div class="action-menu">
                    <button class="action-dot-btn" onclick="toggleActionMenu(event, this)">
                        <i data-lucide="more-vertical"></i>
                    </button>
                    <div class="action-dropdown">
                        <button class="action-dropdown-item" onclick="toggleVerifyUser(${u.user_id},'${fullName.replace(/'/g,"\\'")}',${u.is_verified})">
                            <i data-lucide="shield-check"></i> Verify Host
                        </button>
                    </div>
                </div>
            </td>
        </tr>`;
    }).join('');
    setTimeout(() => { lucide.createIcons(); initDataTable('verificationTable'); }, 50);
}
// ══════════════════════════════════════════
// SHELTERS CRUD
// ══════════════════════════════════════════
let _allShelters = [];
function loadShelters() {
    fetch('index.php?route=admin-get-shelters')
        .then(r=>r.json()).then(data => {
            if (data.success) { _allShelters = data.shelters; displayShelters(data.shelters); }
            else showError('Failed to load shelters');
        });
}
function displayShelters(shelters) {
    const tbody = document.querySelector('#sheltersTable tbody');
    if (!shelters.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#94a3b8;">No shelters found</td></tr>'; return; }
    tbody.innerHTML = shelters.map(s => {
        const pct = s.max_capacity > 0 ? Math.round(s.current_capacity/s.max_capacity*100) : 0;
        const barColor = pct>=90?'#ef4444':pct>=60?'#f59e0b':'#10b981';
        return `<tr>
            <td style="color:#94a3b8;font-size:0.75rem;">#${s.shelter_id}</td>
            <td style="font-weight:600;">${s.shelter_name}</td>
            <td style="font-size:0.8rem;color:#64748b;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${s.location || '—'}</td>
            <td>${s.max_capacity}</td>
            <td>
                <div style="font-weight:600;font-size:0.85rem;">${s.current_capacity}/${s.max_capacity}</div>
                <div style="height:4px;background:#e2e8f0;border-radius:2px;margin-top:3px;width:80px;">
                    <div style="height:100%;width:${pct}%;background:${barColor};border-radius:2px;"></div>
                </div>
            </td>
            <td style="font-size:0.82rem;">${s.first_name ? s.first_name+' '+(s.last_name||'') : '<span style="color:#94a3b8;">—</span>'}</td>
            <td>${statusTag(s.is_active==1?'active':'inactive')}</td>
            <td>
                <div class="action-menu">
                    <button class="action-dot-btn" onclick="toggleActionMenu(event, this)">
                        <i data-lucide="more-vertical"></i>
                    </button>
                    <div class="action-dropdown">
                        <button class="action-dropdown-item" onclick="editShelter(${s.shelter_id})">
                            <i data-lucide="edit-3"></i> Edit Shelter
                        </button>
                        <button class="action-dropdown-item text-danger" onclick="confirmDeleteShelter(${s.shelter_id},'${s.shelter_name.replace(/'/g,"\\'")}')">
                            <i data-lucide="trash-2"></i> Delete Shelter
                        </button>
                    </div>
                </div>
            </td>
        </tr>`;
    }).join('');
    setTimeout(() => { lucide.createIcons(); initDataTable('sheltersTable'); }, 50);
}
function openNewShelterModal() {
    document.getElementById('shelterModalTitle').textContent = 'New Shelter';
    document.getElementById('shelterForm').reset();
    document.getElementById('shelterId').value = '';
    document.getElementById('maxCapacity').value = '50';
    document.getElementById('currentCapacity').value = '0';
    document.getElementById('isActive').value = '1';
    document.getElementById('shelterModal').style.display = 'block';
}
function editShelter(id) {
    const s = _allShelters.find(x => x.shelter_id == id);
    if (!s) return;
    document.getElementById('shelterModalTitle').textContent = 'Edit Shelter';
    document.getElementById('shelterId').value      = s.shelter_id;
    document.getElementById('shelterName').value    = s.shelter_name;
    document.getElementById('location').value       = s.location || '';
    document.getElementById('latitude').value       = s.latitude || '';
    document.getElementById('longitude').value      = s.longitude || '';
    document.getElementById('contactNumber').value  = s.contact_number || '';
    document.getElementById('maxCapacity').value    = s.max_capacity;
    document.getElementById('currentCapacity').value= s.current_capacity;
    document.getElementById('isActive').value       = s.is_active;
    document.getElementById('shelterModal').style.display = 'block';
}
function closeShelterModal() { document.getElementById('shelterModal').style.display = 'none'; }
function confirmDeleteShelter(id, name) {
    showConfirm('Delete Shelter?', `This will permanently delete "${name}" and all its data.`, '🏠', 'Delete Shelter', () => deleteShelter(id));
}
function deleteShelter(id) {
    fetch('index.php?route=admin-delete-shelter', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({shelter_id:id})
    }).then(r=>r.json()).then(d => {
        if (d.success) { 
            showSuccess('Shelter deleted'); 
            loadShelters();
            refreshDashboardStats();
        }
        else showError(d.message || 'Failed');
    });
}
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('shelterForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = {
            shelter_id:      document.getElementById('shelterId').value,
            shelter_name:    document.getElementById('shelterName').value,
            location:        document.getElementById('location').value,
            latitude:        document.getElementById('latitude').value,
            longitude:       document.getElementById('longitude').value,
            contact_number:  document.getElementById('contactNumber').value,
            max_capacity:    document.getElementById('maxCapacity').value,
            current_capacity:document.getElementById('currentCapacity').value,
            is_active:       document.getElementById('isActive').value,
        };
        const route = data.shelter_id ? 'admin-update-shelter' : 'admin-create-shelter';
        fetch(`index.php?route=${route}`, {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)
        }).then(r=>r.json()).then(d => {
            if (d.success) { 
                showSuccess(d.message); 
                closeShelterModal(); 
                loadShelters(); 
                refreshDashboardStats();
            }
            else showError(d.message || 'Failed');
        });
    });
});

// ══════════════════════════════════════════
// ALERTS CRUD
// ══════════════════════════════════════════
let _allAlerts = [];
function loadAlerts() {
    fetch('index.php?route=admin-get-alerts')
        .then(r=>r.json()).then(data => {
            if (data.success) { _allAlerts = data.alerts; displayAlerts(data.alerts); }
            else showError('Failed to load alerts');
        });
}
function displayAlerts(alerts) {
    const tbody = document.querySelector('#alertsTable tbody');
    if (!alerts.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#94a3b8;">No alerts yet. Click "New Alert" to create one.</td></tr>'; return; }
    tbody.innerHTML = alerts.map(a => `<tr>
        <td style="color:#94a3b8;font-size:0.75rem;">#${a.alert_id}</td>
        <td>${statusTag(a.type)}</td>
        <td style="font-weight:600;max-width:200px;">${a.title}</td>
        <td style="font-size:0.8rem;color:#64748b;">${a.source || '—'}</td>
        <td style="font-size:0.78rem;color:#64748b;">${a.affected_area || '—'}</td>
        <td>${a.is_active == 1 ? '<span class="live-tag tag-active">Active</span>' : '<span class="live-tag tag-inactive">Inactive</span>'}</td>
        <td style="color:#94a3b8;font-size:0.8rem;">${fmtDate(a.created_at)}</td>
        <td>
            <div class="action-menu">
                <button class="action-dot-btn" onclick="toggleActionMenu(event, this)">
                    <i data-lucide="more-vertical"></i>
                </button>
                <div class="action-dropdown">
                    <button class="action-dropdown-item" onclick="editAlert(${a.alert_id})">
                        <i data-lucide="edit-3"></i> Edit Alert
                    </button>
                    <button class="action-dropdown-item text-danger" onclick="confirmDeleteAlert(${a.alert_id},'${a.title.replace(/'/g,"\\'")}')">
                        <i data-lucide="trash-2"></i> Delete Alert
                    </button>
                </div>
            </div>
        </td>
    </tr>`).join('');
    setTimeout(() => { lucide.createIcons(); initDataTable('alertsTable'); }, 50);
}
function openNewAlertModal() {
    document.getElementById('alertModalTitle').textContent = 'New Alert';
    document.getElementById('alertForm').reset();
    document.getElementById('alertId').value = '';
    document.getElementById('alertActive').value = '1';
    document.getElementById('alertModal').style.display = 'block';
}
function editAlert(id) {
    const a = _allAlerts.find(x => x.alert_id == id);
    if (!a) return;
    document.getElementById('alertModalTitle').textContent = 'Edit Alert';
    document.getElementById('alertId').value     = a.alert_id;
    document.getElementById('alertType').value   = a.type;
    document.getElementById('alertTitle').value  = a.title;
    document.getElementById('alertBody').value   = a.body;
    document.getElementById('alertSource').value = a.source || '';
    document.getElementById('alertArea').value   = a.affected_area || '';
    document.getElementById('alertActive').value = a.is_active;
    document.getElementById('alertModal').style.display = 'block';
}
function closeAlertModal() { document.getElementById('alertModal').style.display = 'none'; }
function confirmDeleteAlert(id, title) {
    showConfirm('Delete Alert?', `Delete alert "${title}"? This removes it from the live feed immediately.`, '🔔', 'Delete Alert', () => deleteAlert(id));
}
function deleteAlert(id) {
    fetch('index.php?route=admin-delete-alert', {
        method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({alert_id:id})
    }).then(r=>r.json()).then(d => {
        if (d.success) { showSuccess('Alert deleted'); loadAlerts(); }
        else showError(d.message || 'Failed');
    });
}
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('alertForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = {
            alert_id:     document.getElementById('alertId').value,
            type:         document.getElementById('alertType').value,
            title:        document.getElementById('alertTitle').value,
            body:         document.getElementById('alertBody').value,
            source:       document.getElementById('alertSource').value,
            affected_area:document.getElementById('alertArea').value,
            is_active:    document.getElementById('alertActive').value,
        };
        const route = data.alert_id ? 'admin-update-alert' : 'admin-create-alert';
        fetch(`index.php?route=${route}`, {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)
        }).then(r=>r.json()).then(d => {
            if (d.success) { 
                showSuccess(d.message); 
                closeAlertModal(); 
                loadAlerts(); 
                refreshDashboardStats();
            }
            else showError(d.message || 'Failed');
        });
    });
});

// ══════════════════════════════════════════
// REQUESTS (admin view)
// ══════════════════════════════════════════
function loadRequests() {
    fetch('index.php?route=admin-get-requests')
        .then(r=>r.json()).then(data => {
            if (data.success) displayRequests(data.requests);
            else showError('Failed to load requests');
        });
}
function displayRequests(reqs) {
    const tbody = document.querySelector('#requestsTable tbody');
    if (!reqs.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#94a3b8;">No requests found</td></tr>'; return; }
    tbody.innerHTML = reqs.map(r => `<tr>
        <td style="color:#94a3b8;font-size:0.75rem;">#${r.id}</td>
        <td style="font-weight:600;">${r.first_name} ${r.last_name || ''}<br><span style="font-size:0.75rem;color:#94a3b8;">${r.email}</span></td>
        <td style="font-size:0.82rem;">${r.shelter_name}</td>
        <td style="text-align:center;">${r.group_size}</td>
        <td>${statusTag(r.status)}</td>
        <td style="font-family:monospace;letter-spacing:2px;font-weight:700;">${r.approval_code || '—'}</td>
        <td style="color:#94a3b8;font-size:0.78rem;">${fmtDateTime(r.created_at)}</td>
        <td>
            ${r.status === 'pending' ? `
                <div class="action-menu">
                    <button class="action-dot-btn" onclick="toggleActionMenu(event, this)">
                        <i data-lucide="more-vertical"></i>
                    </button>
                    <div class="action-dropdown">
                        <button class="action-dropdown-item" onclick="adminApprove(${r.id})">
                            <i data-lucide="check-circle"></i> Approve
                        </button>
                        <button class="action-dropdown-item text-danger" onclick="adminDecline(${r.id})">
                            <i data-lucide="x-circle"></i> Decline
                        </button>
                    </div>
                </div>
            ` : '<span style="color:#94a3b8;font-size:0.75rem;">—</span>'}
        </td>
    </tr>`).join('');
    setTimeout(() => { lucide.createIcons(); initDataTable('requestsTable'); }, 50);
}
function adminApprove(id) {
    showConfirm('Approve Request?', 'Force-approve this shelter request as admin?', '✅', 'Yes, Approve', () => {
        fetch('index.php?route=admin-force-approve', {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({request_id:id})
        }).then(r=>r.json()).then(d => {
            if (d.success) { 
                showSuccess('Request approved'); 
                loadRequests(); 
                refreshDashboardStats();
            }
            else showError(d.message);
        });
    });
}
function adminDecline(id) {
    showConfirm('Decline Request?', 'Force-decline this shelter request as admin?', '❌', 'Yes, Decline', () => {
        fetch('index.php?route=admin-force-decline', {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({request_id:id})
        }).then(r=>r.json()).then(d => {
            if (d.success) { 
                showSuccess('Request declined'); 
                loadRequests(); 
                refreshDashboardStats();
            }
            else showError(d.message);
        });
    });
}

// ══════════════════════════════════════════
// OCCUPANTS (admin view)
// ══════════════════════════════════════════
function refreshDashboardStats() {
    return fetch('index.php?route=admin-get-shelter-stats')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.SHELTER_CAPACITY_DATA = data.shelters;
                // If overview charts are initialized, they will be updated on next tab switch
                // or we can manually trigger if overview is active
                if (document.getElementById('overview-tab').classList.contains('active')) {
                    initCharts();
                }
            }
        });
}

function loadOccupants() {
    // Sync stats first, then load occupants to ensure headers are fresh
    refreshDashboardStats().then(() => {
        fetch('index.php?route=admin-get-occupants')
            .then(r=>r.json()).then(data => {
                if (data.success) displayOccupants(data.occupants);
                else showError('Failed to load occupants');
            });
    });
}
function displayOccupants(occs) {
    const container = document.getElementById('occupantsGroupContainer');
    if (!container) return;

    // Use current capacity data as the baseline for sections
    const shelters = window.SHELTER_CAPACITY_DATA || [];
    
    // Group occupants by shelter_id
    const grouped = occs.reduce((acc, o) => {
        if (!acc[o.shelter_id]) acc[o.shelter_id] = [];
        acc[o.shelter_id].push(o);
        return acc;
    }, {});

    if (shelters.length === 0 && occs.length === 0) {
        container.innerHTML = '<div class="panel"><div class="state-empty">No shelter or occupancy data available.</div></div>';
        return;
    }

    container.innerHTML = shelters.map(s => {
        const shelterOccs = grouped[s.shelter_id] || [];
        // Calculate real-time capacity from actual occupants in the list
        const current = shelterOccs.reduce((sum, o) => sum + parseInt(o.group_size || 0), 0);
        const max = parseInt(s.max_capacity) || 1;
        const pct = Math.min(100, Math.round((current / max) * 100));
        const colorClass = pct > 85 ? 'high' : (pct > 50 ? 'mid' : 'low');

        return `
        <div class="shelter-occupant-group">
            <div class="group-header">
                <div class="group-info">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <h3><i data-lucide="home"></i> ${s.shelter_name}</h3>
                        <a href="index.php?route=report_occupants&shelter_id=${s.shelter_id}" target="_blank" class="secondary-btn" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; text-decoration:none;">
                            <i data-lucide="file-text" style="width:14px;height:14px;"></i> Export PDF
                        </a>
                    </div>
                    <p><i data-lucide="map-pin" style="width:12px;height:12px;"></i> Facility ID: #${s.shelter_id}</p>
                </div>
                <div class="group-stats">
                    <div class="stat-item">
                        <span class="stat-label">Verified Occupants</span>
                        <span class="stat-value">${current}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Max Capacity</span>
                        <span class="stat-value">${max}</span>
                    </div>
                    <div class="occupancy-progress-wrapper">
                        <div class="occupancy-progress">
                            <div class="progress-bar ${colorClass}" style="width: ${pct}%"></div>
                        </div>
                        <span class="progress-text">${pct}% Full</span>
                    </div>
                </div>
            </div>
            <div class="group-content">
                ${shelterOccs.length > 0 ? `
                    <div class="occupant-grid">
                        ${shelterOccs.map(o => {
                            const dur = Math.floor((Date.now() - new Date(o.checked_in_at).getTime()) / 3600000);
                            return `
                            <div class="occupant-card">
                                <div class="card-top">
                                    <div class="occupant-main">
                                        <h4>${o.first_name} ${o.last_name || ''}</h4>
                                        <span class="occ-id">ID #${o.occupant_id}</span>
                                    </div>
                                    <div class="action-menu">
                                        <button class="action-dot-btn" onclick="toggleActionMenu(event, this)">
                                            <i data-lucide="more-vertical"></i>
                                        </button>
                                        <div class="action-dropdown">
                                            <button class="action-dropdown-item text-danger" onclick="forceCheckout(${o.occupant_id},'${o.first_name}')">
                                                <i data-lucide="log-out"></i> Force Checkout
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <i data-lucide="mail"></i>
                                        <span>${o.email}</span>
                                    </div>
                                    <div class="info-row">
                                        <i data-lucide="phone"></i>
                                        <span>${o.phone_number || 'No Phone'}</span>
                                    </div>
                                    <div class="info-row">
                                        <i data-lucide="clock"></i>
                                        <span>Checked in ${dur}h ago</span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <span class="card-badge">${o.group_size} Person${o.group_size > 1 ? 's' : ''}</span>
                                    <span class="checkin-time">${fmtDateTime(o.checked_in_at)}</span>
                                </div>
                            </div>`;
                        }).join('')}
                    </div>
                ` : `<div class="group-empty">No active occupants in this facility.</div>`}
            </div>
        </div>`;
    }).join('');

    setTimeout(() => { 
        lucide.createIcons(); 
        // No DataTables for card grid
    }, 50);
}
function forceCheckout(id, name) {
    showConfirm('Force Check-Out?', `Check out "${name}" from their shelter? This cannot be undone.`, '🚪', 'Yes, Check Out', () => {
        fetch('index.php?route=admin-force-checkout', {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({occupant_id:id})
        }).then(r=>r.json()).then(d => {
            if (d.success) { 
                showSuccess('Occupant checked out'); 
                loadOccupants(); // This now calls refreshDashboardStats internally
            }
            else showError(d.message);
        });
    });
}

function repairCapacities() {
    showConfirm('Sync Database Capacities?', 'This will re-calculate all shelter occupancy numbers from actual occupant records. Use this to fix discrepancies.', '🔄', 'Sync Now', () => {
        fetch('index.php?route=admin-repair-capacities', { method: 'POST' })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    showSuccess(d.message);
                    loadOccupants(); // Refresh UI
                } else showError(d.message);
            });
    });
}

// ══════════════════════════════════════════
// ACTIVITY LOGS
// ══════════════════════════════════════════
let _allLogs = [];
function loadLogs() {
    fetch('index.php?route=admin-get-logs')
        .then(r=>r.json()).then(data => {
            if (data.success) { _allLogs = data.logs; displayLogs(data.logs); }
            else showError('Failed to load logs');
        });
}
function displayLogs(logs) {
    const tbody = document.querySelector('#logsTable tbody');
    if (!logs.length) { tbody.innerHTML = '<tr><td colspan="3" class="td-empty">No activity logs yet.</td></tr>'; return; }
    tbody.innerHTML = logs.map(l => `
        <tr>
            <td style="color:#94a3b8;font-size:0.8rem;">${fmtDateTime(l.created_at)}</td>
            <td style="font-weight:600;">${l.user_name || 'System'}</td>
            <td>${l.action}</td>
        </tr>
    `).join('');
    setTimeout(() => initDataTable('logsTable'), 50);
}
