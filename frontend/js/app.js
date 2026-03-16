// frontend/js/app.js
// MediSec — Lógica principal del frontend

const API = '../../backend/api';   // ruta relativa desde /frontend/pages/

// ── Estado global ──────────────────────────────────────────────
const State = {
    user: null,
    activePanel: null,
};

// ── Helpers ────────────────────────────────────────────────────
async function api(path, opts = {}) {
    const res = await fetch(`${API}/${path}`, {
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', ...(opts.headers || {}) },
        ...opts,
    });
    return res.json();
}

function toast(msg, type = 'info', duration = 3500) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = `show ${type}`;
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), duration);
}

function roleBadge(role) {
    const labels = { admin: '👑 Admin', doctor: '🩺 Doctor', usuario: '👤 Usuario' };
    return `<span class="role-badge role-${role}">${labels[role] || role}</span>`;
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d + 'T00:00:00').toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ── Navegación ─────────────────────────────────────────────────
function showPage(id) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById(id)?.classList.add('active');
}

function showPanel(id) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.getElementById(id)?.classList.add('active');
    document.querySelectorAll('.nav-item').forEach(n => {
        n.classList.toggle('active', n.dataset.panel === id);
    });
    State.activePanel = id;
    document.getElementById('topbarTitle').textContent =
        document.querySelector(`.nav-item[data-panel="${id}"]`)?.querySelector('.nav-label')?.textContent || '';
}

// ── Login ──────────────────────────────────────────────────────
async function login(username, password) {
    const errEl = document.getElementById('loginError');
    errEl.classList.add('hidden');

    const data = await api('login.php', {
        method: 'POST',
        body: JSON.stringify({ username, password }),
    });

    if (!data.success) {
        errEl.textContent = data.error || 'Error de autenticación';
        errEl.classList.remove('hidden');
        return;
    }

    State.user = data.user;
    renderDashboard(data.user);
    showPage('dashboardPage');
}

function fillDemo(username, password) {
    document.getElementById('loginUsername').value = username;
    document.getElementById('loginPassword').value = password;
}

// ── Logout ─────────────────────────────────────────────────────
async function logout() {
    await api('logout.php');
    State.user = null;
    showPage('loginPage');
    document.getElementById('loginUsername').value = '';
    document.getElementById('loginPassword').value = '';
}

// ── Render Dashboard ───────────────────────────────────────────
function renderDashboard(user) {
    // User info en sidebar
    document.getElementById('sidebarName').textContent  = user.full_name;
    document.getElementById('sidebarRole').innerHTML    = roleBadge(user.role);
    document.getElementById('sidebarEmail').textContent = user.email;

    // Mostrar navs según rol
    document.querySelectorAll('.nav-section, .nav-item').forEach(el => {
        const allowedRoles = el.dataset.roles?.split(',') || [];
        if (allowedRoles.length === 0) { el.style.display = ''; return; }
        el.style.display = allowedRoles.includes(user.role) ? '' : 'none';
    });

    // Panel inicial según rol
    const defaults = { usuario: 'panelMyRecords', doctor: 'panelPatients', admin: 'panelAllUsers' };
    showPanel(defaults[user.role] || 'panelMyRecords');

    // Cargar contenido inicial
    loadPanelContent(defaults[user.role]);
}

function loadPanelContent(panelId) {
    switch (panelId) {
        case 'panelMyRecords':  loadMyRecords();    break;
        case 'panelPatients':   loadDoctorPatients(); break;
        case 'panelAllUsers':   loadAdminUsers();   break;
        case 'panelAllRecords': loadAdminRecords(); break;
        case 'panelNewDiag':    renderNewDiagForm(); break;
        case 'panelVuln':       /* renderizado estático */ break;
    }
}

// ── PANEL: Mi historial médico (Usuario) ───────────────────────
async function loadMyRecords() {
    const container = document.getElementById('myRecordsContent');
    container.innerHTML = '<div class="flex items-center gap-2" style="padding:2rem;color:var(--text2)"><div class="spinner"></div> Cargando historial...</div>';

    const data = await api('my_records.php');

    if (data.error) {
        container.innerHTML = `<div class="empty-state"><div class="icon">🏥</div><p>${data.error}</p></div>`;
        return;
    }

    const p = data.patient;
    const patientAge = p.dob ? Math.floor((Date.now() - new Date(p.dob)) / 31557600000) : '?';

    container.innerHTML = `
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-header">
        <div class="card-title">👤 Mi Información</div>
      </div>
      <div class="flex gap-3" style="flex-wrap:wrap">
        <span class="info-chip">🩸 ${p.blood_type}</span>
        <span class="info-chip">🎂 ${patientAge} años</span>
        <span class="info-chip">📞 ${p.phone || '—'}</span>
        <span class="info-chip">📍 ${p.address || '—'}</span>
        <span class="info-chip">🚨 ${p.emergency_contact || '—'}</span>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">📋 Historial Médico</div>
        <span class="text-sm" style="color:var(--text2)">${data.records.length} consulta(s)</span>
      </div>
      ${data.records.length === 0
        ? '<div class="empty-state"><div class="icon">📭</div><p>Sin registros médicos aún.</p></div>'
        : data.records.map(r => `
          <div class="record-card">
            <div class="record-date">📅 ${formatDate(r.visit_date)} — Dr. ${r.doctor_name} · ${r.doctor_specialty}</div>
            <div class="record-diagnosis">🔴 ${r.diagnosis}</div>
            <div class="record-field"><strong>💊 Tratamiento:</strong> ${r.treatment}</div>
            ${r.notes ? `<div class="record-field"><strong>📝 Notas:</strong> ${r.notes}</div>` : ''}
          </div>`).join('')
      }
    </div>`;
}

// ── PANEL: Pacientes del Doctor ────────────────────────────────
async function loadDoctorPatients() {
    const container = document.getElementById('doctorPatientsContent');
    container.innerHTML = '<div class="flex items-center gap-2" style="padding:2rem;color:var(--text2)"><div class="spinner"></div> Cargando pacientes...</div>';

    const data = await api('doctor_patients.php');

    if (data.error) {
        container.innerHTML = `<div class="empty-state"><div class="icon">🚫</div><p>${data.error}</p></div>`;
        return;
    }

    const patients = data.patients;

    container.innerHTML = `
    <div class="card" style="margin-bottom:1.5rem">
      <div class="card-header">
        <div class="card-title">🩺 ${data.doctor.name}</div>
        <div class="flex gap-2">
          <span class="info-chip">🏥 ${data.doctor.specialty}</span>
          <span class="info-chip">🪪 ${data.doctor.license_number}</span>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">👥 Pacientes Asignados</div>
        <span class="text-sm" style="color:var(--text2)">${patients.length} paciente(s)</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Paciente</th>
              <th>Sangre</th>
              <th>Teléfono</th>
              <th>Último Diagnóstico</th>
              <th>Última Visita</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            ${patients.map((p, i) => `
              <tr>
                <td style="color:var(--text3)">${i + 1}</td>
                <td>
                  <div style="font-weight:600">${p.full_name}</div>
                  <div style="color:var(--text3);font-size:.8rem">${p.email}</div>
                </td>
                <td><span class="info-chip">🩸 ${p.blood_type}</span></td>
                <td style="color:var(--text2)">${p.phone || '—'}</td>
                <td style="max-width:200px">
                  <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.85rem">
                    ${p.last_diagnosis || '<span style="color:var(--text3)">Sin registros</span>'}
                  </div>
                </td>
                <td style="color:var(--text2);font-size:.85rem">${formatDate(p.last_visit) || '—'}</td>
                <td>
                  <button class="btn btn-success text-sm"
                    onclick="openDiagModal(${p.id}, '${p.full_name.replace(/'/g,"\\'")}')">
                    ✏️ Diagnóstico
                  </button>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>`;
}

// ── Abrir modal nuevo diagnóstico ──────────────────────────────
function openDiagModal(patientId, patientName) {
    document.getElementById('diagPatientId').value   = patientId;
    document.getElementById('diagPatientName').textContent = patientName;
    document.getElementById('diagModal').style.display = 'flex';
}
function closeDiagModal() {
    document.getElementById('diagModal').style.display = 'none';
}
async function submitDiagnosis() {
    const payload = {
        patient_id: parseInt(document.getElementById('diagPatientId').value),
        visit_date:  document.getElementById('diagDate').value    || new Date().toISOString().slice(0,10),
        diagnosis:   document.getElementById('diagDiagnosis').value.trim(),
        treatment:   document.getElementById('diagTreatment').value.trim(),
        notes:       document.getElementById('diagNotes').value.trim(),
    };
    if (!payload.diagnosis || !payload.treatment) {
        toast('Diagnóstico y tratamiento son requeridos', 'error'); return;
    }
    const data = await api('update_diagnosis.php', { method: 'POST', body: JSON.stringify(payload) });
    if (data.success) {
        toast('✅ Diagnóstico registrado correctamente', 'success');
        closeDiagModal();
        loadDoctorPatients();
    } else {
        toast(data.error || 'Error al guardar', 'error');
    }
}

// ── PANEL: Admin — todos los usuarios (requiere admin) ──────────
async function loadAdminUsers() {
    const container = document.getElementById('adminUsersContent');
    container.innerHTML = '<div class="flex items-center gap-2" style="padding:2rem;color:var(--text2)"><div class="spinner"></div> Cargando usuarios...</div>';

    const data = await api('admin/users.php');

    if (data.error) {
        container.innerHTML = `<div class="empty-state"><div class="icon">🔒</div><p>${data.error}</p></div>`;
        return;
    }

    const s = data.stats;
    container.innerHTML = `
    <div class="stats-grid">
      <div class="stat-card stat-total"><div class="stat-val">${s.total_users}</div><div class="stat-lbl">Total usuarios</div></div>
      <div class="stat-card stat-admin"><div class="stat-val">${s.admins}</div><div class="stat-lbl">Administradores</div></div>
      <div class="stat-card stat-doctor"><div class="stat-val">${s.doctors}</div><div class="stat-lbl">Doctores</div></div>
      <div class="stat-card stat-user"><div class="stat-val">${s.patients}</div><div class="stat-lbl">Pacientes</div></div>
    </div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">👥 Todos los Usuarios</div>
        <button class="btn btn-success text-sm" onclick="toggleCreateUserForm()">➕ Crear Usuario</button>
      </div>

      <div id="createUserForm" class="hidden" style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.25rem">
        <div class="form-row" style="margin-bottom:1rem">
          <div class="form-group-dash"><label>Usuario</label><input id="nuUsername" placeholder="ej: dr_nuevo"></div>
          <div class="form-group-dash"><label>Contraseña</label><input id="nuPassword" type="password" placeholder="••••••••"></div>
        </div>
        <div class="form-row" style="margin-bottom:1rem">
          <div class="form-group-dash"><label>Nombre completo</label><input id="nuFullname" placeholder="Dr. Juan Nuevo"></div>
          <div class="form-group-dash"><label>Email</label><input id="nuEmail" type="email" placeholder="correo@ejemplo.com"></div>
        </div>
        <div class="form-row" style="margin-bottom:1rem">
          <div class="form-group-dash">
            <label>Rol</label>
            <select id="nuRole">
              <option value="usuario">👤 Usuario</option>
              <option value="doctor">🩺 Doctor</option>
              <option value="admin">👑 Administrador</option>
            </select>
          </div>
        </div>
        <button class="btn btn-primary" style="width:auto;padding:.65rem 1.5rem" onclick="createUser()">Crear Usuario</button>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Usuario</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Contraseña ⚠️</th><th>Creado</th></tr>
          </thead>
          <tbody>
            ${data.users.map((u, i) => `
              <tr>
                <td style="color:var(--text3)">${u.id}</td>
                <td><code style="font-family:monospace;color:var(--accent)">${u.username}</code></td>
                <td>${u.full_name}</td>
                <td style="color:var(--text2);font-size:.85rem">${u.email}</td>
                <td>${roleBadge(u.role)}</td>
                <td><span class="password-cell">${u.password}</span></td>
                <td style="color:var(--text3);font-size:.8rem">${u.created_at?.slice(0,10) || '—'}</td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>`;
}

function toggleCreateUserForm() {
    document.getElementById('createUserForm').classList.toggle('hidden');
}

async function createUser() {
    const payload = {
        username:  document.getElementById('nuUsername').value.trim(),
        password:  document.getElementById('nuPassword').value.trim(),
        full_name: document.getElementById('nuFullname').value.trim(),
        email:     document.getElementById('nuEmail').value.trim(),
        role:      document.getElementById('nuRole').value,
    };
    const data = await api('admin/create_user.php', { method: 'POST', body: JSON.stringify(payload) });
    if (data.success) {
        toast('✅ ' + data.message, 'success');
        loadAdminUsers();
    } else {
        toast(data.error || 'Error al crear', 'error');
    }
}

// ── PANEL: Admin — todos los registros médicos ──────────────────
async function loadAdminRecords() {
    const container = document.getElementById('adminRecordsContent');
    container.innerHTML = '<div class="flex items-center gap-2" style="padding:2rem;color:var(--text2)"><div class="spinner"></div> Cargando registros...</div>';

    const data = await api('admin/all_records.php');

    if (data.error) {
        container.innerHTML = `<div class="empty-state"><div class="icon">🔒</div><p>${data.error}</p></div>`;
        return;
    }

    container.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div class="card-title">📋 Todos los Registros Médicos</div>
        <span style="color:var(--text2);font-size:.85rem">${data.total} registro(s)</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Fecha</th><th>Paciente</th><th>Doctor</th><th>Especialidad</th><th>Diagnóstico</th><th>Tratamiento</th></tr>
          </thead>
          <tbody>
            ${data.records.map((r, i) => `
              <tr>
                <td style="color:var(--text3)">${r.id}</td>
                <td style="font-size:.85rem;white-space:nowrap">${formatDate(r.visit_date)}</td>
                <td style="font-weight:600">${r.patient_name}</td>
                <td style="color:var(--text2)">${r.doctor_name}</td>
                <td><span class="info-chip">${r.specialty}</span></td>
                <td style="max-width:200px;font-size:.85rem">${r.diagnosis}</td>
                <td style="max-width:200px;font-size:.85rem;color:var(--text2)">${r.treatment}</td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>`;
}

// ── PANEL: Vulnerabilidad Demo ─────────────────────────────────
async function runExploit() {
    const resultEl = document.getElementById('vulnResult');
    const btn      = document.getElementById('exploitBtn');
    btn.disabled   = true;
    btn.textContent = '⏳ Ejecutando exploit...';

    // Llamada directa a la API "de admin" desde un usuario normal
    const data = await api('admin/users.php');
    resultEl.classList.remove('hidden');
    resultEl.textContent = JSON.stringify(data, null, 2);
    btn.disabled   = false;
    btn.textContent = '🔓 Ejecutar exploit de nuevo';
    toast('🚨 Datos de admin accedidos como usuario normal!', 'error', 6000);
}

// ── Init ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    showPage('loginPage');

    // Login form
    document.getElementById('loginForm').addEventListener('submit', e => {
        e.preventDefault();
        const u = document.getElementById('loginUsername').value.trim();
        const p = document.getElementById('loginPassword').value.trim();
        if (u && p) login(u, p);
    });

    // Nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            const panel = item.dataset.panel;
            if (!panel) return;
            showPanel(panel);
            loadPanelContent(panel);
        });
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', logout);

    // Modal backdrop
    document.getElementById('diagModal').addEventListener('click', e => {
        if (e.target === document.getElementById('diagModal')) closeDiagModal();
    });
});