{{-- resources/views/pages/users/student/dashboard.blade.php --}}
@extends('pages.users.student.layout.structure')

@section('title', 'My Dashboard')
@section('header', 'My Dashboard')

@push('styles')
<style>
  /* ===== Shell ===== */
  .sd-wrap{
    max-width:1180px;
    margin:16px auto 40px;
  }

  .sd-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
  }
  .sd-head-left{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .sd-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    box-shadow:var(--shadow-1);
  }
  .sd-pill-icon{
    width:26px;height:26px;
    border-radius:999px;
    display:flex;align-items:center;justify-content:center;
    background:var(--t-primary);
    color:var(--primary-color);
  }
  .sd-title-main{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    font-size:1.15rem;
  }
  .sd-title-sub{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }

  .sd-head-right{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  #sdPeriodLabel{
    font-weight:500;
    color:var(--secondary-color);
  }

  /* Toolbar */
  .sd-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:16px;
    flex-wrap:wrap;
  }
  .sd-filters{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .sd-filter-chip{
    border:1px dashed var(--line-strong);
    border-radius:999px;
    padding:6px 10px;
    background:var(--surface);
    display:flex;
    align-items:center;
    gap:8px;
  }
  .sd-filter-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  #sdPeriodSelect{
    min-width:130px;
    height:32px;
    padding:4px 8px;
    font-size:var(--fs-13);
    border-radius:999px;
  }

  /* Quick stats */
  .sd-stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    margin-bottom:18px;
  }
  .sd-stat-card{
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
  .sd-stat-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:4px;
  }
  .sd-stat-icon{
    width:40px;height:40px;
    border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    background:var(--surface-2);
    color:var(--secondary-color);
    flex-shrink:0;
  }
  .sd-stat-kicker{
    font-size:var(--fs-12);
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted-color);
  }
  .sd-stat-value{
    font-size:1.7rem;
    font-weight:700;
    color:var(--ink);
    line-height:1.1;
  }
  .sd-stat-label{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .sd-stat-meta{
    font-size:var(--fs-12);
    color:var(--secondary-color);
    margin-top:2px;
  }

  /* Panels */
  .sd-panel{
    background:var(--surface);
    border:1px solid var(--line-strong);
    border-radius:16px;
    box-shadow:var(--shadow-2);
    padding:14px 14px 12px;
    height:100%;
  }
  .sd-panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:8px;
  }
  .sd-panel-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
    font-size:var(--fs-15);
  }
  .sd-panel-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  /* Charts */
  .sd-chart-shell{
    position:relative;
    height:260px;
  }

  /* Today strip */
  .sd-today-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:8px;
  }
  .sd-today-card{
    border-radius:12px;
    padding:8px 10px;
    background:var(--surface-2);
  }
  .sd-today-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .sd-today-value{
    font-size:1.1rem;
    font-weight:600;
    color:var(--ink);
  }

  /* Lists */
  .sd-list{
    max-height:260px;
    overflow:auto;
  }
  .sd-list-item{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:8px;
    padding:8px 0;
    border-bottom:1px solid var(--line-soft);
  }
  .sd-list-item:last-child{border-bottom:none;}
  .sd-list-main{ flex:1; }
  .sd-list-title{
    font-size:var(--fs-13);
    font-weight:500;
    color:var(--ink);
    margin-bottom:2px;
  }
  .sd-list-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .sd-list-badge{
    font-size:var(--fs-12);
    font-weight:600;
    color:var(--secondary-color);
    white-space:nowrap;
  }

  .sd-status-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:2px 8px;
    border-radius:999px;
    border:1px solid var(--line-soft);
    font-size:var(--fs-11);
    color:var(--muted-color);
  }
  .sd-status-pill-dot{
    width:6px;height:6px;
    border-radius:999px;
    background:var(--muted-color);
  }
  .sd-status-pill-dot.sd-status-in-progress{background:#f97316;}
  .sd-status-pill-dot.sd-status-submitted{background:#22c55e;}
  .sd-status-pill-dot.sd-status-auto_submitted{background:#0ea5e9;}

  /* Responsive */
  @media (max-width: 768px){
    .sd-head{flex-direction:column;align-items:flex-start;}
  }
</style>
@endpush

@section('content')
<div class="sd-wrap">
  {{-- Header --}}
  <div class="sd-head">
    <div class="sd-head-left">
      <div class="sd-pill">
        <div class="sd-pill-icon">
          <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div>
          <div class="sd-title-main">My Learning Dashboard</div>
          <div class="sd-title-sub">Track your quizzes, attempts &amp; progress over time</div>
        </div>
      </div>
    </div>
    <div class="sd-head-right">
      <span class="text-muted me-1">Range:</span>
      <span id="sdPeriodLabel">Last 30 days</span>
    </div>
  </div>

  {{-- Toolbar --}}
  <div class="sd-toolbar">
    <div class="sd-filters">
      <div class="sd-filter-chip">
        <span class="sd-filter-label">Period</span>
        <select id="sdPeriodSelect" class="form-select form-select-sm">
          <option value="7d">Last 7 days</option>
          <option value="30d" selected>Last 30 days</option>
          <option value="90d">Last 90 days</option>
          <option value="1y">Last 12 months</option>
        </select>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-light btn-sm" id="sdBtnRefresh">
        <span class="me-1"><i class="fa-solid fa-rotate-right"></i></span>
        Refresh
      </button>
    </div>
  </div>

  {{-- Quick stats --}}
  <div class="sd-stats-grid">
    <div class="sd-stat-card">
      <div class="sd-stat-top">
        <div class="sd-stat-icon">
          <i class="fa-solid fa-clipboard-check"></i>
        </div>
        <div class="sd-stat-kicker">Assigned</div>
      </div>
      <div class="sd-stat-value" id="sdAssignedQuizzes">0</div>
      <div class="sd-stat-label">Active quizzes assigned to you</div>
      <div class="sd-stat-meta" id="sdAssignedMeta">Keep your streak going</div>
    </div>

    <div class="sd-stat-card">
      <div class="sd-stat-top">
        <div class="sd-stat-icon">
          <i class="fa-solid fa-pen-to-square"></i>
        </div>
        <div class="sd-stat-kicker">Attempts</div>
      </div>
      <div class="sd-stat-value" id="sdTotalAttempts">0</div>
      <div class="sd-stat-label">Total attempts (all time)</div>
      <div class="sd-stat-meta" id="sdAttemptsMeta">0 completed</div>
    </div>

    <div class="sd-stat-card">
      <div class="sd-stat-top">
        <div class="sd-stat-icon">
          <i class="fa-solid fa-chart-column"></i>
        </div>
        <div class="sd-stat-kicker">Average Score</div>
      </div>
      <div class="sd-stat-value" id="sdAvgScore">0%</div>
      <div class="sd-stat-label">Average across all results</div>
      <div class="sd-stat-meta" id="sdBestScoreMeta">Best quiz: –</div>
    </div>

    <div class="sd-stat-card">
      <div class="sd-stat-top">
        <div class="sd-stat-icon">
          <i class="fa-solid fa-bullseye"></i>
        </div>
        <div class="sd-stat-kicker">Completion</div>
      </div>
      <div class="sd-stat-value" id="sdCompletionRate">0%</div>
      <div class="sd-stat-label">Attempts that you finished</div>
      <div class="sd-stat-meta" id="sdCompletionMeta">0 of 0 attempts completed</div>
    </div>
  </div>

  {{-- Today strip --}}
  <div class="sd-panel mb-3">
    <div class="sd-panel-head">
      <div>
        <div class="sd-panel-title">Today</div>
        <div class="sd-panel-sub">What you’ve done so far today</div>
      </div>
    </div>
    <div class="sd-today-grid">
      <div class="sd-today-card">
        <div class="sd-today-label">Attempts started</div>
        <div class="sd-today-value" id="sdTodayStarted">0</div>
      </div>
      <div class="sd-today-card">
        <div class="sd-today-label">Attempts completed</div>
        <div class="sd-today-value" id="sdTodayCompleted">0</div>
      </div>
      <div class="sd-today-card">
        <div class="sd-today-label">Time spent in quizzes</div>
        <div class="sd-today-value" id="sdTodayTime">0 min</div>
      </div>
    </div>
  </div>

  {{-- Row 1: Charts --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="sd-panel">
        <div class="sd-panel-head">
          <div>
            <div class="sd-panel-title">Attempts Over Time</div>
            <div class="sd-panel-sub">How often you started quizzes in this period</div>
          </div>
        </div>
        <div class="sd-chart-shell">
          <canvas id="sdAttemptsChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="sd-panel">
        <div class="sd-panel-head">
          <div>
            <div class="sd-panel-title">Score Trend</div>
            <div class="sd-panel-sub">Your average percentage per day</div>
          </div>
        </div>
        <div class="sd-chart-shell">
          <canvas id="sdScoresChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Row 2: Recent & Upcoming --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="sd-panel">
        <div class="sd-panel-head">
          <div class="sd-panel-title">Recent Attempts</div>
          <div class="sd-panel-sub">Last few quizzes you interacted with</div>
        </div>
        <div id="sdRecentAttempts" class="sd-list">
          <div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading attempts…
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="sd-panel">
        <div class="sd-panel-head">
          <div class="sd-panel-title">Upcoming Quizzes</div>
          <div class="sd-panel-sub">Assigned to you &amp; not completed yet</div>
        </div>
        <div id="sdUpcomingQuizzes" class="sd-list">
          <div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading quizzes…
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div id="sdToastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="sdToastSuccessText">Dashboard updated</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="sdToastError" class="toast align-items-center text-bg-danger border-0 mt-2" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="sdToastErrorText">Failed to load dashboard data</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
  const ENDPOINT = "{{ url('api/dashboard/student') }}";

  let period = '30d';
  let dataPayload = null;
  let attemptsChart = null;
  let scoresChart = null;

  const periodSelect = document.getElementById('sdPeriodSelect');
  const periodLabel = document.getElementById('sdPeriodLabel');
  const btnRefresh = document.getElementById('sdBtnRefresh');

  const assignedQuizzesEl = document.getElementById('sdAssignedQuizzes');
  const assignedMetaEl = document.getElementById('sdAssignedMeta');
  const totalAttemptsEl = document.getElementById('sdTotalAttempts');
  const attemptsMetaEl = document.getElementById('sdAttemptsMeta');
  const avgScoreEl = document.getElementById('sdAvgScore');
  const bestScoreMetaEl = document.getElementById('sdBestScoreMeta');
  const completionRateEl = document.getElementById('sdCompletionRate');
  const completionMetaEl = document.getElementById('sdCompletionMeta');

  const todayStartedEl = document.getElementById('sdTodayStarted');
  const todayCompletedEl = document.getElementById('sdTodayCompleted');
  const todayTimeEl = document.getElementById('sdTodayTime');

  const recentAttemptsEl = document.getElementById('sdRecentAttempts');
  const upcomingQuizzesEl = document.getElementById('sdUpcomingQuizzes');

  const toastSuccessEl = document.getElementById('sdToastSuccess');
  const toastErrorEl = document.getElementById('sdToastError');
  const toastSuccessText = document.getElementById('sdToastSuccessText');
  const toastErrorText = document.getElementById('sdToastErrorText');

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

  function periodLabelText(p) {
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

  function fmtDurationFromSeconds(sec) {
    const s = Number(sec) || 0;
    if (!s) return '0 min';
    const mins = Math.floor(s / 60);
    const remSec = s % 60;
    if (mins >= 60) {
      const hours = Math.floor(mins / 60);
      const m2 = mins % 60;
      return `${hours}h ${m2}m`;
    }
    if (mins === 0) return `${remSec}s`;
    return remSec ? `${mins}m ${remSec}s` : `${mins}m`;
  }

  function setLoadingState() {
    recentAttemptsEl.innerHTML =
      '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading attempts…</div>';
    upcomingQuizzesEl.innerHTML =
      '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading quizzes…</div>';
  }

  function setErrorState() {
    recentAttemptsEl.innerHTML =
      '<div class="text-center text-danger py-3">Failed to load attempts</div>';
    upcomingQuizzesEl.innerHTML =
      '<div class="text-center text-danger py-3">Failed to load quizzes</div>';
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

      dataPayload = json.data || {};
      updatePeriodLabel();
      renderDashboard();
      showSuccess('Dashboard updated');
    } catch (err) {
      console.error('[Student Dashboard] error:', err);
      showError(err.message || 'Failed to load dashboard data');
      setErrorState();
    }
  }

  function updatePeriodLabel() {
    if (!dataPayload || !dataPayload.date_range) {
      periodLabel.textContent = periodLabelText(period);
      return;
    }
    const dr = dataPayload.date_range;
    const label = periodLabelText(dr.period || period);
    const start = fmtDateShort(dr.start);
    const end = fmtDateShort(dr.end);
    periodLabel.textContent = `${label} · ${start} – ${end}`;
  }

  function renderDashboard() {
    if (!dataPayload) return;
    renderSummary();
    renderTodayStrip();
    renderCharts();
    renderRecentAttempts();
    renderUpcomingQuizzes();
  }

  function renderSummary() {
    const sc = dataPayload.summary_counts || {};
    const assigned = Number(sc.assigned_quizzes) || 0;
    const totalAttempts = Number(sc.total_attempts) || 0;
    const completed = Number(sc.completed_attempts) || 0;
    const avg = Number(sc.average_percentage) || 0;
    const best = sc.best_performance || null;

    const completionRate = totalAttempts ? (completed * 100 / totalAttempts) : 0;

    if (assignedQuizzesEl) assignedQuizzesEl.textContent = assigned;
    if (assignedMetaEl) {
      assignedMetaEl.textContent = assigned
        ? `${assigned} quiz${assigned > 1 ? 'zes' : ''} currently active`
        : 'No active quizzes assigned';
    }

    if (totalAttemptsEl) totalAttemptsEl.textContent = totalAttempts;
    if (attemptsMetaEl) {
      attemptsMetaEl.textContent = `${completed} completed`;
    }

    if (avgScoreEl) avgScoreEl.textContent = avg.toFixed(1) + '%';

    if (bestScoreMetaEl) {
      if (best && best.percentage != null) {
        const perc = Number(best.percentage).toFixed(1);
        bestScoreMetaEl.textContent =
          `Best quiz: ${perc}% (${best.marks_obtained}/${best.total_marks})`;
      } else {
        bestScoreMetaEl.textContent = 'Best quiz: –';
      }
    }

    if (completionRateEl) completionRateEl.textContent = completionRate.toFixed(1) + '%';
    if (completionMetaEl) {
      completionMetaEl.textContent =
        `${completed} of ${totalAttempts} attempts completed`;
    }
  }

  function renderTodayStrip() {
    const qs = dataPayload.quick_stats || {};
    const todayStarted = Number(qs.today_attempts_started) || 0;
    const todayCompleted = Number(qs.today_attempts_completed) || 0;
    const todayTimeSec = Number(qs.today_time_spent_sec) || 0;

    if (todayStartedEl) todayStartedEl.textContent = todayStarted;
    if (todayCompletedEl) todayCompletedEl.textContent = todayCompleted;
    if (todayTimeEl) todayTimeEl.textContent = fmtDurationFromSeconds(todayTimeSec);
  }

  function renderCharts() {
    renderAttemptsChart();
    renderScoresChart();
  }

  function renderAttemptsChart() {
    const canvas = document.getElementById('sdAttemptsChart');
    if (!canvas) return;

    const series = dataPayload.attempts_over_time || [];
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
    const canvas = document.getElementById('sdScoresChart');
    if (!canvas) return;

    const series = dataPayload.scores_over_time || [];
    const labels = series.map(r => fmtDateShort(r.date));
    const values = series.map(r => Number(r.avg_percentage) || 0);

    if (scoresChart) scoresChart.destroy();

    const ctx = canvas.getContext('2d');
    scoresChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Average %',
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

  function statusLabel(status) {
    const s = (status || '').toLowerCase();
    if (s === 'in_progress') return 'In progress';
    if (s === 'submitted') return 'Submitted';
    if (s === 'auto_submitted') return 'Auto submitted';
    return s || '-';
  }

  function statusDotClass(status) {
    const s = (status || '').toLowerCase();
    if (s === 'in_progress') return 'sd-status-in-progress';
    if (s === 'submitted') return 'sd-status-submitted';
    if (s === 'auto_submitted') return 'sd-status-auto_submitted';
    return '';
  }

  function renderRecentAttempts() {
    const list = dataPayload.recent_attempts || [];
    if (!list.length) {
      recentAttemptsEl.innerHTML =
        '<div class="text-center text-muted py-3">No recent attempts</div>';
      return;
    }

    recentAttemptsEl.innerHTML = list.map(item => {
      const quizName = escapeHtml(item.quiz_name || `Quiz #${item.quiz_id}`);
      const status = item.attempt_status || '';
      const statusNice = statusLabel(status);
      const dotCls = statusDotClass(status);

      const started = item.started_at ? fmtDateTime(item.started_at) : null;
      const finished = item.finished_at ? fmtDateTime(item.finished_at) : null;

      const pct = item.result_percentage != null
        ? Number(item.result_percentage).toFixed(1) + '%'
        : '–';

      const marks = (item.marks_obtained != null && item.total_marks != null)
        ? `${item.marks_obtained}/${item.total_marks}`
        : null;

      const metaParts = [];
      if (started) metaParts.push('Started: ' + started);
      if (finished) metaParts.push('Finished: ' + finished);
      if (marks) metaParts.push('Marks: ' + marks);

      const metaLine = metaParts.join(' • ');

      return `
        <div class="sd-list-item">
          <div class="sd-list-main">
            <div class="sd-list-title">${quizName}</div>
            <div class="sd-list-meta">
              <span class="sd-status-pill">
                <span class="sd-status-pill-dot ${dotCls}"></span>
                <span>${statusNice}</span>
              </span>
              ${metaLine ? ' • ' + metaLine : ''}
            </div>
          </div>
          <div class="sd-list-badge">${pct}</div>
        </div>`;
    }).join('');
  }

  function renderUpcomingQuizzes() {
    const list = dataPayload.upcoming_quizzes || [];
    if (!list.length) {
      upcomingQuizzesEl.innerHTML =
        '<div class="text-center text-muted py-3">No upcoming quizzes – you’re all caught up!</div>';
      return;
    }

    upcomingQuizzesEl.innerHTML = list.map(item => {
      const name = escapeHtml(item.quiz_name || `Quiz #${item.quiz_id}`);
      const totalQ = Number(item.total_questions) || 0;
      const totalTime = Number(item.total_time) || 0;
      const assignmentCode = escapeHtml(item.assignment_code || '');
      const assignedAt = item.assigned_at ? fmtDateTime(item.assigned_at) : null;

      const metaParts = [];
      if (totalQ) metaParts.push(`${totalQ} questions`);
      if (totalTime) metaParts.push(`${totalTime} min`);
      if (assignmentCode) metaParts.push(`Code: ${assignmentCode}`);
      if (assignedAt) metaParts.push(`Assigned: ${assignedAt}`);

      const metaLine = metaParts.join(' • ');

      return `
        <div class="sd-list-item">
          <div class="sd-list-main">
            <div class="sd-list-title">${name}</div>
            <div class="sd-list-meta">${metaLine}</div>
          </div>
        </div>`;
    }).join('');
  }

  periodSelect.addEventListener('change', () => {
    period = periodSelect.value || '30d';
    fetchDashboard();
  });

  btnRefresh.addEventListener('click', fetchDashboard);

  // Auto-refresh every 5 minutes
  setInterval(fetchDashboard, 5 * 60 * 1000);

  fetchDashboard();
})();
</script>
@endsection
