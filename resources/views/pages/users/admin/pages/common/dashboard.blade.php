@extends('pages.users.admin.layout.structure')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@push('styles')
<style>
  /* ===== Shell ===== */
  .dash-wrap{
    max-width:1180px;
    margin:16px auto 40px;
  }

  .dash-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
  }
  .dash-head-left{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .dash-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    box-shadow:var(--shadow-1);
  }
  .dash-pill-icon{
    width:26px;height:26px;
    border-radius:999px;
    display:flex;align-items:center;justify-content:center;
    background:var(--t-primary);
    color:var(--primary-color);
  }
  .dash-title-main{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    font-size:1.15rem;
  }
  .dash-title-sub{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }

  .dash-head-right{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  #dashboardPeriod{
    font-weight:500;
    color:var(--secondary-color);
  }

  /* Toolbar */
  .dash-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:16px;
    flex-wrap:wrap;
  }
  .dash-filters{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .filter-chip{
    border:1px dashed var(--line-strong);
    border-radius:999px;
    padding:6px 10px;
    background:var(--surface);
    display:flex;
    align-items:center;
    gap:8px;
  }
  .filter-chip-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  #periodFilter{
    min-width:130px;
    height:32px;
    padding:4px 8px;
    font-size:var(--fs-13);
    border-radius:999px;
  }

  /* Stats grid */
  .stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    margin-bottom:18px;
  }
  .stat-card{
    background:var(--surface);
    border:1px solid var(--line-strong);
    border-radius:16px;
    padding:14px 14px 12px;
    box-shadow:var(--shadow-2);
    display:flex;
    flex-direction:column;
    gap:4px;
    height:100%;
  }
  .stat-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:4px;
  }
  .stat-icon{
    width:40px;height:40px;
    border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    background:var(--surface-2);
    color:var(--secondary-color);
    flex-shrink:0;
  }
  .stat-kicker{
    font-size:var(--fs-12);
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted-color);
  }
  .stat-value{
    font-size:1.7rem;
    font-weight:700;
    color:var(--ink);
    line-height:1.1;
  }
  .stat-label{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .stat-meta{
    font-size:var(--fs-12);
    color:var(--secondary-color);
    margin-top:2px;
  }

  /* Panels */
  .dash-panel{
    background:var(--surface);
    border:1px solid var(--line-strong);
    border-radius:16px;
    box-shadow:var(--shadow-2);
    padding:14px 14px 12px;
    height:100%;
  }
  .dash-panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:8px;
  }
  .dash-panel-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
    font-size:var(--fs-15);
  }
  .dash-panel-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  /* Charts */
  .chart-shell{
    position:relative;
    height:260px;
  }

  /* Activity timeline */
  .activity-list{
    max-height:260px;
    overflow:auto;
  }
  .activity-item{
    display:flex;
    align-items:flex-start;
    gap:10px;
    padding:8px 0;
    border-bottom:1px solid var(--line-soft);
  }
  .activity-item:last-child{border-bottom:none;}
  .activity-icon{
    width:30px;height:30px;
    border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    background:var(--surface-2);
    color:var(--accent-color);
    flex-shrink:0;
  }
  .activity-main{ flex:1; }
  .activity-title{
    font-size:var(--fs-13);
    font-weight:500;
    color:var(--ink);
  }
  .activity-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  /* Metrics grid (system + engagement) */
  .metric-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:8px;
  }
  .metric-card{
    border-radius:12px;
    padding:10px 10px 8px;
    background:var(--surface-2);
  }
  .metric-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-bottom:2px;
  }
  .metric-value{
    font-size:1.15rem;
    font-weight:600;
    color:var(--ink);
  }

  /* Top lists */
  .top-list{
    max-height:230px;
    overflow:auto;
  }
  .top-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    padding:8px 0;
    border-bottom:1px solid var(--line-soft);
  }
  .top-item:last-child{border-bottom:none;}
  .top-item-main{ flex:1; }
  .top-item-title{
    font-size:var(--fs-13);
    font-weight:500;
    color:var(--ink);
    margin-bottom:2px;
  }
  .top-item-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .top-item-badge{
    font-size:var(--fs-12);
    font-weight:600;
    color:var(--secondary-color);
  }

  /* Responsive */
  @media (max-width: 768px){
    .dash-head{flex-direction:column;align-items:flex-start;}
    .metric-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  }
  @media (max-width: 576px){
    .metric-grid{grid-template-columns:1fr;}
  }
</style>
@endpush

@section('content')
<div class="dash-wrap">
  {{-- Header --}}
  <div class="dash-head">
    <div class="dash-head-left">
      <div class="dash-pill">
        <div class="dash-pill-icon">
          <i class="fa-solid fa-chart-line"></i>
        </div>
        <div>
          <div class="dash-title-main">Admin Dashboard</div>
          <div class="dash-title-sub">Live overview of users, quizzes, attempts &amp; performance</div>
        </div>
      </div>
    </div>
    <div class="dash-head-right">
      <span class="text-muted me-1">Range:</span>
      <span id="dashboardPeriod">Last 30 days</span>
    </div>
  </div>

  {{-- Toolbar --}}
  <div class="dash-toolbar">
    <div class="dash-filters">
      <div class="filter-chip">
        <span class="filter-chip-label">Period</span>
        <select id="periodFilter" class="form-select form-select-sm">
          <option value="7d">Last 7 days</option>
          <option value="30d" selected>Last 30 days</option>
          <option value="90d">Last 90 days</option>
          <option value="1y">Last 12 months</option>
        </select>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-light btn-sm" id="btnRefresh">
        <span class="me-1"><i class="fa-solid fa-rotate-right"></i></span>
        Refresh
      </button>
    </div>
  </div>

  {{-- Quick stats --}}
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon">
          <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div class="stat-kicker">Students</div>
      </div>
      <div class="stat-value" id="totalStudents">0</div>
      <div class="stat-label">Total Students</div>
      <div class="stat-meta" id="newStudentsToday">+0 new today</div>
    </div>

    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon">
          <i class="fa-solid fa-clipboard-question"></i>
        </div>
        <div class="stat-kicker">Quizzes</div>
      </div>
      <div class="stat-value" id="totalQuizzes">0</div>
      <div class="stat-label">Active / Total Quizzes</div>
      <div class="stat-meta" id="quizzesToday">0 created today</div>
    </div>

    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon">
          <i class="fa-solid fa-stopwatch"></i>
        </div>
        <div class="stat-kicker">Attempts</div>
      </div>
      <div class="stat-value" id="totalAttempts">0</div>
      <div class="stat-label">Total Attempts</div>
      <div class="stat-meta" id="attemptsStartedToday">0 started today</div>
    </div>

    <div class="stat-card">
      <div class="stat-top">
        <div class="stat-icon">
          <i class="fa-solid fa-square-check"></i>
        </div>
        <div class="stat-kicker">Completed</div>
      </div>
      <div class="stat-value" id="completedAttempts">0</div>
      <div class="stat-label">Completed Attempts</div>
      <div class="stat-meta" id="attemptsCompletedToday">0 completed today</div>
    </div>
  </div>

  {{-- Row 1 --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div>
            <div class="dash-panel-title">Attempts Over Time</div>
            <div class="dash-panel-sub">How many attempts students started each day</div>
          </div>
        </div>
        <div class="chart-shell">
          <canvas id="attemptsChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-4 d-flex flex-column gap-3">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">Recent Activity</div>
          <div class="dash-panel-sub">Last 10 admin actions</div>
        </div>
        <div id="activityList" class="activity-list">
          <div class="text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading activity…
          </div>
        </div>
      </div>

      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">System Health</div>
          <div class="dash-panel-sub">Sessions, failed jobs &amp; errors</div>
        </div>
        <div class="metric-grid">
          <div class="metric-card">
            <div class="metric-label">Active Sessions</div>
            <div class="metric-value" id="metricActiveSessions">0</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Failed Jobs</div>
            <div class="metric-value" id="metricFailedJobs">0</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Recent Errors</div>
            <div class="metric-value" id="metricRecentErrors">0</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Row 2 --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div>
            <div class="dash-panel-title">Average Score Over Time</div>
            <div class="dash-panel-sub">Daily average percentage across all submitted results</div>
          </div>
        </div>
        <div class="chart-shell">
          <canvas id="scoresChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div>
            <div class="dash-panel-title">Engagement Snapshot</div>
            <div class="dash-panel-sub">Attempts per quiz, per student &amp; completion rate</div>
          </div>
        </div>

        <div class="metric-grid">
          <div class="metric-card">
            <div class="metric-label">Attempts / Quiz</div>
            <div class="metric-value" id="kpiAttemptsPerQuiz">0</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Attempts / Student</div>
            <div class="metric-value" id="kpiAttemptsPerStudent">0</div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Completion Rate</div>
            <div class="metric-value" id="kpiCompletionRate">0%</div>
          </div>
        </div>

        <div class="mt-2 small text-muted">
          Based on total attempts, completed attempts, quizzes &amp; students in the selected period.
        </div>
      </div>
    </div>
  </div>

  {{-- Row 3 --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">Top Quizzes</div>
          <div class="dash-panel-sub">Most attempted quizzes in this period</div>
        </div>
        <div id="topQuizzes" class="top-list">
          <div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading quizzes…
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="dash-panel">
        <div class="dash-panel-head">
          <div class="dash-panel-title">Top Students</div>
          <div class="dash-panel-sub">Most active students &amp; their average scores</div>
        </div>
        <div id="topStudents" class="top-list">
          <div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading students…
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastSuccessText">Dashboard updated</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="toastError" class="toast align-items-center text-bg-danger border-0 mt-2" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastErrorText">Failed to load dashboard data</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
  const ENDPOINT = "{{ url('api/dashboard/admin') }}";

  let period = '30d';
  let dashboardData = null;
  let attemptsChart = null;
  let scoresChart = null;

  const periodFilter = document.getElementById('periodFilter');
  const dashboardPeriod = document.getElementById('dashboardPeriod');
  const btnRefresh = document.getElementById('btnRefresh');

  const totalStudentsEl = document.getElementById('totalStudents');
  const totalQuizzesEl = document.getElementById('totalQuizzes');
  const totalAttemptsEl = document.getElementById('totalAttempts');
  const completedAttemptsEl = document.getElementById('completedAttempts');
  const newStudentsTodayEl = document.getElementById('newStudentsToday');
  const quizzesTodayEl = document.getElementById('quizzesToday');
  const attemptsStartedTodayEl = document.getElementById('attemptsStartedToday');
  const attemptsCompletedTodayEl = document.getElementById('attemptsCompletedToday');
  const activityListEl = document.getElementById('activityList');
  const topQuizzesEl = document.getElementById('topQuizzes');
  const topStudentsEl = document.getElementById('topStudents');
  const metricActiveSessionsEl = document.getElementById('metricActiveSessions');
  const metricFailedJobsEl = document.getElementById('metricFailedJobs');
  const metricRecentErrorsEl = document.getElementById('metricRecentErrors');

  const kpiAttemptsPerQuizEl = document.getElementById('kpiAttemptsPerQuiz');
  const kpiAttemptsPerStudentEl = document.getElementById('kpiAttemptsPerStudent');
  const kpiCompletionRateEl = document.getElementById('kpiCompletionRate');

  const toastSuccessEl = document.getElementById('toastSuccess');
  const toastErrorEl = document.getElementById('toastError');
  const toastSuccessText = document.getElementById('toastSuccessText');
  const toastErrorText = document.getElementById('toastErrorText');

  const toastSuccess = new bootstrap.Toast(toastSuccessEl);
  const toastError = new bootstrap.Toast(toastErrorEl);

  function getToken() {
    return sessionStorage.getItem('token') || localStorage.getItem('token') || '';
  }

  function showSuccess(msg) {
    toastSuccessText.textContent = msg;
    toastSuccess.show();
  }

  function showError(msg) {
    toastErrorText.textContent = msg;
    toastError.show();
  }

  function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, s => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[s]));
  }

  function periodLabel(p) {
    switch (p) {
      case '7d': return 'Last 7 days';
      case '30d': return 'Last 30 days';
      case '90d': return 'Last 90 days';
      case '1y': return 'Last 12 months';
      default: return 'Custom range';
    }
  }

  function fmtDateShort(dStr) {
    if (!dStr) return '';
    const d = new Date(dStr);
    if (isNaN(d.getTime())) return dStr;
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
  }

  function fmtDateTime(dStr) {
    if (!dStr) return '';
    const d = new Date(dStr);
    if (isNaN(d.getTime())) return dStr;
    return d.toLocaleString('en-IN', {
      day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
  }

  function setLoadingState() {
    activityListEl.innerHTML =
      '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Loading activity…</div>';
    topQuizzesEl.innerHTML =
      '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading quizzes…</div>';
    topStudentsEl.innerHTML =
      '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading students…</div>';
  }

  function setErrorState() {
    activityListEl.innerHTML =
      '<div class="text-center text-danger py-4">Failed to load activity</div>';
    topQuizzesEl.innerHTML =
      '<div class="text-center text-danger py-3">Failed to load quizzes</div>';
    topStudentsEl.innerHTML =
      '<div class="text-center text-danger py-3">Failed to load students</div>';
  }

  async function fetchDashboard() {
    try {
      setLoadingState();

      const params = new URLSearchParams({ period });
      const res = await fetch(`${ENDPOINT}?${params.toString()}`, {
        headers: {
          'Authorization': 'Bearer ' + getToken(),
          'Accept': 'application/json'
        }
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok || json.status !== 'success') {
        throw new Error(json.message || 'Failed to load dashboard data');
      }

      dashboardData = json.data || {};
      updateRangeLabel();
      renderDashboard();
      showSuccess('Dashboard updated');
    } catch (err) {
      console.error('[Dashboard] error:', err);
      showError(err.message || 'Failed to load dashboard data');
      setErrorState();
    }
  }

  function updateRangeLabel() {
    if (!dashboardData || !dashboardData.date_range) {
      dashboardPeriod.textContent = periodLabel(period);
      return;
    }
    const dr = dashboardData.date_range;
    const label = periodLabel(dr.period || period);
    const start = fmtDateShort(dr.start);
    const end = fmtDateShort(dr.end);
    dashboardPeriod.textContent = `${label} · ${start} – ${end}`;
  }

  function renderDashboard() {
    if (!dashboardData) return;
    renderQuickStats();
    renderCharts();
    renderActivity();
    renderTopQuizzes();
    renderTopStudents();
    renderSystemHealth();
    renderEngagementSnapshot();
  }

  function renderQuickStats() {
    const sc = dashboardData.summary_counts || {};
    const qs = dashboardData.quick_stats || {};

    totalStudentsEl.textContent = sc.total_students ?? 0;
    totalQuizzesEl.textContent = sc.total_quizzes ?? 0;
    totalAttemptsEl.textContent = sc.total_attempts ?? 0;
    completedAttemptsEl.textContent = sc.completed_attempts ?? 0;

    newStudentsTodayEl.textContent = `+${qs.today_new_users ?? 0} new today`;
    quizzesTodayEl.textContent = `${qs.today_quizzes_created ?? 0} created today`;
    attemptsStartedTodayEl.textContent = `${qs.today_attempts_started ?? 0} started today`;
    attemptsCompletedTodayEl.textContent =
      `${qs.today_attempts_completed ?? 0} completed today`;
  }

  function renderCharts() {
    renderAttemptsChart();
    renderScoresChart();
  }

  function renderAttemptsChart() {
    const canvas = document.getElementById('attemptsChart');
    if (!canvas) return;

    const series = dashboardData.attempts_over_time || [];
    const labels = series.map(r => fmtDateShort(r.date));
    const values = series.map(r => Number(r.count) || 0);

    if (attemptsChart) attemptsChart.destroy();

    const ctx = canvas.getContext('2d');
    attemptsChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Attempts',
          data: values,
          borderColor: '#1f9790',
          backgroundColor: 'rgba(31,151,144,0.18)',
          borderWidth: 2,
          fill: true,
          tension: 0.35,
          pointRadius: 2.5,
          pointHoverRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  function renderScoresChart() {
    const canvas = document.getElementById('scoresChart');
    if (!canvas) return;

    const series = dashboardData.scores_over_time || [];
    const labels = series.map(r => fmtDateShort(r.date));
    const values = series.map(r => Number(r.avg_percentage) || 0);

    if (scoresChart) scoresChart.destroy();

    const ctx = canvas.getContext('2d');
    scoresChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Avg %',
          data: values,
          borderColor: '#0ea5e9',
          backgroundColor: 'rgba(14,165,233,0.16)',
          borderWidth: 2,
          fill: true,
          tension: 0.35,
          pointRadius: 2.5,
          pointHoverRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: { callback: v => v + '%', precision: 0 }
          },
          x: { grid: { display: false } }
        }
      }
    });
  }

  function renderActivity() {
    const list = dashboardData.recent_activities || [];
    if (!list.length) {
      activityListEl.innerHTML =
        '<div class="text-center text-muted py-4">No recent activity</div>';
      return;
    }

    activityListEl.innerHTML = list.map(item => {
      const title = escapeHtml(item.log_note || item.activity || 'Activity');
      const metaParts = [];
      if (item.module) metaParts.push(escapeHtml(item.module));
      if (item.performed_by_role) metaParts.push(escapeHtml(item.performed_by_role));
      if (item.created_at) metaParts.push(fmtDateTime(item.created_at));
      const meta = metaParts.join(' • ');

      const a = (item.activity || '').toLowerCase();
      let iconClass = 'fa-circle';
      if (a.includes('login')) iconClass = 'fa-right-to-bracket';
      else if (a.includes('logout')) iconClass = 'fa-right-from-bracket';
      else if (a.includes('create') || a.includes('store')) iconClass = 'fa-plus';
      else if (a.includes('update') || a.includes('edit')) iconClass = 'fa-pen-to-square';
      else if (a.includes('delete') || a.includes('destroy')) iconClass = 'fa-trash';
      else if (a.includes('assign')) iconClass = 'fa-user-plus';
      else if (a.includes('quiz') || a.includes('exam')) iconClass = 'fa-circle-question';

      return `
        <div class="activity-item">
          <div class="activity-icon">
            <i class="fa-solid ${iconClass}"></i>
          </div>
          <div class="activity-main">
            <div class="activity-title">${title}</div>
            <div class="activity-meta">${meta}</div>
          </div>
        </div>`;
    }).join('');
  }

  function renderTopQuizzes() {
    const list = dashboardData.top_quizzes || [];
    if (!list.length) {
      topQuizzesEl.innerHTML =
        '<div class="text-center text-muted py-3">No quiz data for this period</div>';
      return;
    }

    topQuizzesEl.innerHTML = list.map((q, idx) => {
      const name = escapeHtml(q.quiz_name || `Quiz #${q.id}`);
      const attempts = q.total_attempts ?? 0;
      const completed = q.completed_attempts ?? 0;

      return `
        <div class="top-item">
          <div class="top-item-main">
            <div class="top-item-title">${idx + 1}. ${name}</div>
            <div class="top-item-meta">
              ${attempts} attempts • ${completed} completed
            </div>
          </div>
          <div class="top-item-badge">${attempts}</div>
        </div>`;
    }).join('');
  }

  function renderTopStudents() {
    const list = dashboardData.top_students || [];
    if (!list.length) {
      topStudentsEl.innerHTML =
        '<div class="text-center text-muted py-3">No student data for this period</div>';
      return;
    }

    topStudentsEl.innerHTML = list.map((u, idx) => {
      const name = escapeHtml(u.name || `Student #${u.id}`);
      const email = escapeHtml(u.email || '');
      const attempts = u.attempts ?? 0;
      const avg = u.avg_percentage != null
        ? Number(u.avg_percentage).toFixed(1)
        : '0.0';

      return `
        <div class="top-item">
          <div class="top-item-main">
            <div class="top-item-title">${idx + 1}. ${name}</div>
            <div class="top-item-meta">
              ${attempts} attempts • Avg ${avg}%${email ? ' • ' + email : ''}
            </div>
          </div>
          <div class="top-item-badge">${avg}%</div>
        </div>`;
    }).join('');
  }

  function renderSystemHealth() {
    const system = dashboardData.system_health || {};
    const qs = dashboardData.quick_stats || {};

    metricActiveSessionsEl.textContent = qs.active_sessions ?? 0;
    metricFailedJobsEl.textContent = system.failed_jobs ?? 0;
    metricRecentErrorsEl.textContent = system.recent_errors ?? 0;
  }

  function renderEngagementSnapshot() {
    if (!dashboardData) return;
    const sc = dashboardData.summary_counts || {};

    const totalStudents = Number(sc.total_students) || 0;
    const totalQuizzes = Number(sc.total_quizzes) || 0;
    const totalAttempts = Number(sc.total_attempts) || 0;
    const completedAttempts = Number(sc.completed_attempts) || 0;

    const attemptsPerQuiz = totalQuizzes ? (totalAttempts / totalQuizzes) : 0;
    const attemptsPerStudent = totalStudents ? (totalAttempts / totalStudents) : 0;
    const completionRate = totalAttempts ? (completedAttempts * 100 / totalAttempts) : 0;

    if (kpiAttemptsPerQuizEl) {
      kpiAttemptsPerQuizEl.textContent = attemptsPerQuiz.toFixed(1);
    }
    if (kpiAttemptsPerStudentEl) {
      kpiAttemptsPerStudentEl.textContent = attemptsPerStudent.toFixed(1);
    }
    if (kpiCompletionRateEl) {
      kpiCompletionRateEl.textContent = completionRate.toFixed(1) + '%';
    }
  }

  periodFilter.addEventListener('change', () => {
    period = periodFilter.value || '30d';
    fetchDashboard();
  });

  btnRefresh.addEventListener('click', fetchDashboard);

  // Auto-refresh every 5 minutes
  setInterval(fetchDashboard, 5 * 60 * 1000);

  fetchDashboard();
})();
</script>
@endsection
