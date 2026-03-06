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

// ── Tab Switching ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns     = document.querySelectorAll('.tab-btn[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    const loaders = { users: false, shelters: false, alerts: false, requests: false, occupants: false, logs: false };

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const tab = this.dataset.tab;
            document.getElementById(tab + '-tab').classList.add('active');
            if (!loaders[tab]) { loadTab(tab); loaders[tab] = true; }
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

    // Close modals on backdrop click
    window.addEventListener('click', e => {
        ['userModal','shelterModal','alertModal','confirmModal'].forEach(id => {
            const m = document.getElementById(id);
            if (e.target === m) m.style.display = 'none';
        });
    });
});

function loadTab(tab) {
    if (tab === 'users')     loadUsers();
    else if (tab === 'shelters')  loadShelters();
    else if (tab === 'alerts')    loadAlerts();
    else if (tab === 'requests')  loadRequests();
    else if (tab === 'occupants') loadOccupants();
    else if (tab === 'logs')      loadLogs();
}

// ── Generic table search filter ───────────────────────────
function filterTable(tableId, query) {
    const table  = document.getElementById(tableId);
    if (!table) return;
    const rows   = table.querySelectorAll('tbody tr');
    const q      = query.toLowerCase();
    const statusFilter = document.getElementById(tableId === 'sheltersTable' ? 'shelterStatusFilter' :
                          tableId === 'requestsTable' ? 'reqStatusFilter' : null);
    const sv = statusFilter ? statusFilter.value.toLowerCase() : '';
    rows.forEach(row => {
        const text  = row.textContent.toLowerCase();
        const matchQ  = q === '' || text.includes(q);
        const matchSt = sv === '' || text.includes(sv);
        row.style.display = (matchQ && matchSt) ? '' : 'none';
    });
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
            if (data.success) { _allUsers = data.users; displayUsers(data.users); }
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
                <button class="action-btn btn-edit" onclick="editUser(${u.user_id})">Edit</button>
                <button class="action-btn ${u.is_verified == 1 ? 'btn-decline' : 'btn-approve'}" onclick="toggleVerifyUser(${u.user_id},'${fullName.replace(/'/g,"\\'")}',${u.is_verified})">${u.is_verified == 1 ? '✗ Unverify' : '✔ Verify'}</button>
                <button class="action-btn btn-delete" onclick="confirmDeleteUser(${u.user_id},'${fullName.replace(/'/g,"\\'")}')">Delete</button>
            </td>
        </tr>`;
    }).join('');
    setTimeout(() => lucide.createIcons(), 50);
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
        if (d.success) { showSuccess('User deleted'); loadUsers(); }
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
            if (d.success) { showSuccess(d.message); closeUserModal(); loadUsers(); }
            else showError(d.message || 'Failed');
        });
    });
});

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
                <button class="action-btn btn-edit" onclick="editShelter(${s.shelter_id})">Edit</button>
                <button class="action-btn btn-delete" onclick="confirmDeleteShelter(${s.shelter_id},'${s.shelter_name}')">Delete</button>
            </td>
        </tr>`;
    }).join('');
    setTimeout(() => lucide.createIcons(), 50);
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
        if (d.success) { showSuccess('Shelter deleted'); loadShelters(); }
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
            if (d.success) { showSuccess(d.message); closeShelterModal(); loadShelters(); }
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
            <button class="action-btn btn-edit" onclick="editAlert(${a.alert_id})">Edit</button>
            <button class="action-btn btn-delete" onclick="confirmDeleteAlert(${a.alert_id},'${a.title.replace(/'/g,"\\'")}')">Delete</button>
        </td>
    </tr>`).join('');
    setTimeout(() => lucide.createIcons(), 50);
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
            if (d.success) { showSuccess(d.message); closeAlertModal(); loadAlerts(); }
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
                <button class="action-btn btn-approve" onclick="adminApprove(${r.id})">✓ Approve</button>
                <button class="action-btn btn-decline" onclick="adminDecline(${r.id})">✗ Decline</button>
            ` : '<span style="color:#94a3b8;font-size:0.75rem;">—</span>'}
        </td>
    </tr>`).join('');
    setTimeout(() => lucide.createIcons(), 50);
}
function adminApprove(id) {
    showConfirm('Approve Request?', 'Force-approve this shelter request as admin?', '✅', 'Yes, Approve', () => {
        fetch('index.php?route=admin-force-approve', {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({request_id:id})
        }).then(r=>r.json()).then(d => {
            if (d.success) { showSuccess('Request approved'); loadRequests(); }
            else showError(d.message);
        });
    });
}
function adminDecline(id) {
    showConfirm('Decline Request?', 'Force-decline this shelter request as admin?', '❌', 'Yes, Decline', () => {
        fetch('index.php?route=admin-force-decline', {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({request_id:id})
        }).then(r=>r.json()).then(d => {
            if (d.success) { showSuccess('Request declined'); loadRequests(); }
            else showError(d.message);
        });
    });
}

// ══════════════════════════════════════════
// OCCUPANTS (admin view)
// ══════════════════════════════════════════
function loadOccupants() {
    fetch('index.php?route=admin-get-occupants')
        .then(r=>r.json()).then(data => {
            if (data.success) displayOccupants(data.occupants);
            else showError('Failed to load occupants');
        });
}
function displayOccupants(occs) {
    const tbody = document.querySelector('#occupantsTable tbody');
    if (!occs.length) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#94a3b8;">No active occupants</td></tr>'; return; }
    tbody.innerHTML = occs.map(o => {
        const dur = Math.floor((Date.now() - new Date(o.checked_in_at).getTime()) / 3600000);
        return `<tr>
            <td style="color:#94a3b8;font-size:0.75rem;">#${o.occupant_id}</td>
            <td style="font-weight:600;">${o.first_name} ${o.last_name || ''}<br><span style="font-size:0.75rem;color:#94a3b8;">${o.phone_number||''}</span></td>
            <td style="font-size:0.8rem;">${o.email}</td>
            <td style="font-weight:600;">${o.shelter_name}</td>
            <td style="text-align:center;">${o.group_size}</td>
            <td style="font-size:0.8rem;">${fmtDateTime(o.checked_in_at)}<br><span style="color:#94a3b8;font-size:0.72rem;">${dur}h ago</span></td>
            <td><button class="action-btn btn-delete" onclick="forceCheckout(${o.occupant_id},'${o.first_name}')">Check Out</button></td>
        </tr>`;
    }).join('');
    setTimeout(() => lucide.createIcons(), 50);
}
function forceCheckout(id, name) {
    showConfirm('Force Check-Out?', `Check out "${name}" from their shelter? This cannot be undone.`, '🚪', 'Yes, Check Out', () => {
        fetch('index.php?route=admin-force-checkout', {
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({occupant_id:id})
        }).then(r=>r.json()).then(d => {
            if (d.success) { showSuccess('Occupant checked out'); loadOccupants(); }
            else showError(d.message);
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
    const list = document.getElementById('logList');
    if (!logs.length) { list.innerHTML = '<p style="text-align:center;padding:2rem;color:#94a3b8;">No activity logs yet.</p>'; return; }
    list.innerHTML = logs.map(l => `
        <div class="log-row">
            <span class="log-time">${timeAgo(l.created_at)}</span>
            <span class="log-user">${l.user_name || 'System'}</span>
            <span class="log-action">${l.action}</span>
        </div>
    `).join('');
}
function filterLogList(q) {
    const filtered = _allLogs.filter(l =>
        (l.action||'').toLowerCase().includes(q.toLowerCase()) ||
        (l.user_name||'').toLowerCase().includes(q.toLowerCase())
    );
    displayLogs(filtered);
}
