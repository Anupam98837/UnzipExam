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

  // Helper: publish role globally
  const publishRole = (role) => {
    document.body.setAttribute('data-role', role);
    window.__DASH_ACTIVE_ROLE__ = role;
    try {
      window.dispatchEvent(new CustomEvent('dash:role', { detail: { role } }));
    } catch (e) {}
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
        return String(data.role).trim().toLowerCase();
      }
      return '';
    } catch (e) {
      console.error('[DASH] Error fetching role:', e);
      return '';
    }
  };

  // Wait for DOM if needed
  if (document.readyState === 'loading') {
    await new Promise(resolve => document.addEventListener('DOMContentLoaded', resolve, { once: true }));
  }

  const dashAdmin = document.getElementById('dashAdmin');
  const dashExaminer = document.getElementById('dashExaminer');
  const dashStudent = document.getElementById('dashStudent');

  // Check if panels exist
  if (!dashAdmin || !dashExaminer || !dashStudent) {
    console.error('[DASH] Missing dashboard panels');
    return;
  }

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

  // Show appropriate dashboard and initialize
  if (role === 'admin') {
    dashAdmin.style.display = 'block';
    console.log('[DASH] Showing admin dashboard');
    
    // Wait a bit for the included script to load
    setTimeout(() => {
      if (typeof initializeAdminDashboard === 'function') {
        console.log('[DASH] Initializing admin dashboard');
        initializeAdminDashboard();
      } else {
        console.error('[DASH] initializeAdminDashboard not found');
      }
    }, 100);
    
  } else if (role === 'examiner') {
    dashExaminer.style.display = 'block';
    console.log('[DASH] Showing examiner dashboard');
    
    setTimeout(() => {
      if (typeof initializeExaminerDashboard === 'function') {
        console.log('[DASH] Initializing examiner dashboard');
        initializeExaminerDashboard();
      } else {
        console.error('[DASH] initializeExaminerDashboard not found');
      }
    }, 100);
    
  } else if (role === 'student') {
    dashStudent.style.display = 'block';
    console.log('[DASH] Showing student dashboard');
    
    setTimeout(() => {
      if (typeof initializeStudentDashboard === 'function') {
        console.log('[DASH] Initializing student dashboard');
        initializeStudentDashboard();
      } else {
        console.error('[DASH] initializeStudentDashboard not found');
      }
    }, 100);
    
  } else {
    window.location.replace('/');
  }

  console.log('[DASH] Dashboard initialized for role:', role);
})();
</script>
@endpush