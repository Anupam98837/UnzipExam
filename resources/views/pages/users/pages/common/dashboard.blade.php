{{-- resources/views/pages/users/dashboard.blade.php --}}
@extends('pages.users.layout.structure')

@section('title', 'Dashboard')

@section('content')
  <div id="dash-shell">
    {{-- Dashboards (all rendered; only 1 will be shown) --}}
    <div id="dashAdmin" style="display:none">
      @include('modules.common.adminDashboard')
    </div>

    <div id="dashExaminer" style="display:none">
      @include('modules.common.examinerDashboard')
    </div>

    <div id="dashStudent" style="display:none">
      @include('modules.common.studentDashboard')
    </div>
  </div>
@endsection

@push('scripts')
{{-- Chart.js needed for all dashboards --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(async function initDashboard() {
  const shell = document.getElementById('dash-shell');
  if (!shell) {
    console.warn('[DASH] dash-shell not found. Exiting.');
    return;
  }

  // Wait for DOM if needed
  if (document.readyState === 'loading') {
    await new Promise(resolve => document.addEventListener('DOMContentLoaded', resolve, { once: true }));
  }

  const dashAdmin    = document.getElementById('dashAdmin');
  const dashExaminer = document.getElementById('dashExaminer');
  const dashStudent  = document.getElementById('dashStudent');

  // Check if panels exist
  if (!dashAdmin || !dashExaminer || !dashStudent) {
    console.error('[DASH] Missing dashboard panels');
    return;
  }

  // Helper: show only one panel
  const showPanel = (panel) => {
    dashAdmin.style.display = 'none';
    dashExaminer.style.display = 'none';
    dashStudent.style.display = 'none';
    panel.style.display = 'block';
  };

  // Helper: publish role globally
  const publishRole = (role) => {
    document.body.setAttribute('data-role', role);
    window.__DASH_ACTIVE_ROLE__ = role;
    try {
      window.dispatchEvent(new CustomEvent('dash:role', { detail: { role } }));
    } catch (e) {}
  };

  // Normalize role (important fix ✅)
  const normalizeRole = (role) => {
    const r = String(role || '').trim().toLowerCase();

    // ✅ super_admin should behave like admin
    if (r === 'super_admin' || r === 'superadmin' || r === 'super-admin') return 'admin';

    return r;
  };

  // Get role from API
  const getMyRole = async (token) => {
    if (!token) return '';
    try {
      const res = await fetch('/api/auth/me-role', {
        method: 'GET',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json'
        }
      });

      if (!res.ok) return '';

      const data = await res.json();
      if (data?.status === 'success' && data?.role) {
        return normalizeRole(data.role);
      }
      return '';
    } catch (e) {
      console.error('[DASH] Error fetching role:', e);
      return '';
    }
  };

  // Get token
  const token = sessionStorage.getItem('token') || localStorage.getItem('token');
  if (!token) {
    window.location.replace('/');
    return;
  }

  // Get role
  const role = await getMyRole(token);
  if (!role) {
    sessionStorage.removeItem('token');
    localStorage.removeItem('token');
    window.location.replace('/');
    return;
  }

  // Publish role
  publishRole(role);

  // Small safe wait (ensures DOM + Blade pushed scripts are ready)
  await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));

  // Show appropriate dashboard and initialize
  if (role === 'admin') {
    showPanel(dashAdmin);
    console.log('[DASH] Showing admin dashboard');

    if (typeof window.initializeAdminDashboard === 'function') {
      console.log('[DASH] Initializing admin dashboard');
      window.initializeAdminDashboard();
    } else {
      console.error('[DASH] initializeAdminDashboard not found');
    }

  } else if (role === 'examiner') {
    showPanel(dashExaminer);
    console.log('[DASH] Showing examiner dashboard');

    if (typeof window.initializeExaminerDashboard === 'function') {
      console.log('[DASH] Initializing examiner dashboard');
      window.initializeExaminerDashboard();
    } else {
      console.error('[DASH] initializeExaminerDashboard not found');
    }

  } else if (role === 'student') {
    showPanel(dashStudent);
    console.log('[DASH] Showing student dashboard');

    if (typeof window.initializeStudentDashboard === 'function') {
      console.log('[DASH] Initializing student dashboard');
      window.initializeStudentDashboard();
    } else {
      console.error('[DASH] initializeStudentDashboard not found');
    }

  } else {
    window.location.replace('/');
    return;
  }

  console.log('[DASH] Dashboard initialized for role:', role);
})();
</script> 
@endpush
