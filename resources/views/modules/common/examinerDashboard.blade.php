
<style>
  /* ===== Examiner Dashboard Shell ===== */
  .exdash-wrap{
    max-width:1180px;
    margin:16px auto 40px;
  }

  .exdash-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
  }
  .exdash-head-left{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .exdash-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    box-shadow:var(--shadow-1);
  }
  .exdash-pill-icon{
    width:26px;height:26px;
    border-radius:999px;
    display:flex;align-items:center;justify-content:center;
    background:var(--t-primary);
    color:var(--primary-color);
  }
  .exdash-title-main{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    font-size:1.15rem;
  }
  .exdash-title-sub{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }

  .exdash-head-right{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  #exdashPeriodLabel{
    font-weight:500;
    color:var(--secondary-color);
  }

  /* Toolbar */
  .exdash-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:16px;
    flex-wrap:wrap;
  }
  .exdash-filters{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .exdash-filter-chip{
    border:1px dashed var(--line-strong);
    border-radius:999px;
    padding:6px 10px;
    background:var(--surface);
    display:flex;
    align-items:center;
    gap:8px;
  }
  .exdash-filter-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  #exdashPeriod{
    min-width:130px;
    height:32px;
    padding:4px 8px;
    font-size:var(--fs-13);
    border-radius:999px;
  }

  /* Stats grid */
  .exdash-stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    margin-bottom:18px;
  }
  .exdash-stat-card{
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
  .exdash-stat-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:4px;
  }
  .exdash-stat-icon{
    width:40px;height:40px;
    border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    background:var(--surface-2);
    color:var(--secondary-color);
    flex-shrink:0;
  }
  .exdash-stat-kicker{
    font-size:var(--fs-12);
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted-color);
  }
  .exdash-stat-value{
    font-size:1.7rem;
    font-weight:700;
    color:var(--ink);
    line-height:1.1;
  }
  .exdash-stat-label{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .exdash-stat-meta{
    font-size:var(--fs-12);
    color:var(--secondary-color);
    margin-top:2px;
  }

  /* Panels */
  .exdash-panel{
    background:var(--surface);
    border:1px solid var(--line-strong);
    border-radius:16px;
    box-shadow:var(--shadow-2);
    padding:14px 14px 12px;
    height:100%;
  }
  .exdash-panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:8px;
  }
  .exdash-panel-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
    font-size:var(--fs-15);
  }
  .exdash-panel-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  /* Charts */
  .exdash-chart-shell{
    position:relative;
    height:260px;
  }

  /* Activity timeline */
  .exdash-activity-list{
    max-height:260px;
    overflow:auto;
  }
  .exdash-activity-item{
    display:flex;
    align-items:flex-start;
    gap:10px;
    padding:8px 0;
    border-bottom:1px solid var(--line-soft);
  }
  .exdash-activity-item:last-child{border-bottom:none;}
  .exdash-activity-icon{
    width:30px;height:30px;
    border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    background:var(--surface-2);
    color:var(--accent-color);
    flex-shrink:0;
  }
  .exdash-activity-main{ flex:1; }
  .exdash-activity-title{
    font-size:var(--fs-13);
    font-weight:500;
    color:var(--ink);
  }
  .exdash-activity-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  /* Metrics grid */
  .exdash-metric-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:8px;
  }
  .exdash-metric-card{
    border-radius:12px;
    padding:10px 10px 8px;
    background:var(--surface-2);
  }
  .exdash-metric-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-bottom:2px;
  }
  .exdash-metric-value{
    font-size:1.15rem;
    font-weight:600;
    color:var(--ink);
  }

  /* Lists */
  .exdash-list{
    max-height:230px;
    overflow:auto;
  }
  .exdash-list-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    padding:8px 0;
    border-bottom:1px solid var(--line-soft);
  }
  .exdash-list-item:last-child{border-bottom:none;}
  .exdash-list-main{ flex:1; }
  .exdash-list-title{
    font-size:var(--fs-13);
    font-weight:500;
    color:var(--ink);
    margin-bottom:2px;
  }
  .exdash-list-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .exdash-list-badge{
    font-size:var(--fs-12);
    font-weight:600;
    color:var(--secondary-color);
  }

  @media (max-width: 768px){
    .exdash-head{flex-direction:column;align-items:flex-start;}
    .exdash-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  }
  @media (max-width: 576px){
    .exdash-metric-grid{grid-template-columns:1fr;}
  }
</style>

<div class="exdash-wrap">
  {{-- Header --}}
  <div class="exdash-head">
    <div class="exdash-head-left">
      <div class="exdash-pill">
        <div class="exdash-pill-icon">
          <i class="fa-solid fa-clipboard-check"></i>
        </div>
        <div>
          <div class="exdash-title-main">Examiner Dashboard</div>
          <div class="exdash-title-sub">
            Overview of your quizzes, assignments &amp; student performance
          </div>
        </div>
      </div>
    </div>
    <div class="exdash-head-right">
      <span class="text-muted me-1">Range:</span>
      <span id="exdashPeriodLabel">Last 30 days</span>
    </div>
  </div>

  {{-- Toolbar --}}
  <div class="exdash-toolbar">
    <div class="exdash-filters">
      <div class="exdash-filter-chip">
        <span class="exdash-filter-label">Period</span>
        <select id="exdashPeriod" class="form-select form-select-sm">
          <option value="7d">Last 7 days</option>
          <option value="30d" selected>Last 30 days</option>
          <option value="90d">Last 90 days</option>
          <option value="1y">Last 12 months</option>
        </select>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-light btn-sm" id="exdashRefresh">
        <span class="me-1"><i class="fa-solid fa-rotate-right"></i></span>
        Refresh
      </button>
    </div>
  </div>

  {{-- Primary stats: quizzes, assignments, attempts, performance --}}
  <div class="exdash-stats-grid">
    <div class="exdash-stat-card">
      <div class="exdash-stat-top">
        <div class="exdash-stat-icon">
          <i class="fa-solid fa-clipboard-question"></i>
        </div>
        <div class="exdash-stat-kicker">Quizzes</div>
      </div>
      <div class="exdash-stat-value" id="exdashCreatedQuizzesTotal">0</div>
      <div class="exdash-stat-label">Quizzes created by you</div>
      <div class="exdash-stat-meta" id="exdashCreatedQuizzesMeta">0 active · 0 archived</div>
    </div>

    <div class="exdash-stat-card">
      <div class="exdash-stat-top">
        <div class="exdash-stat-icon">
          <i class="fa-solid fa-user-check"></i>
        </div>
        <div class="exdash-stat-kicker">Assignments</div>
      </div>
      <div class="exdash-stat-value" id="exdashAssignmentsTotal">0</div>
      <div class="exdash-stat-label">Quiz assignments you created</div>
      <div class="exdash-stat-meta" id="exdashAssignmentsMeta">0 active · 0 students assigned</div>
    </div>

    <div class="exdash-stat-card">
      <div class="exdash-stat-top">
        <div class="exdash-stat-icon">
          <i class="fa-solid fa-stopwatch"></i>
        </div>
        <div class="exdash-stat-kicker">Attempts</div>
      </div>
      <div class="exdash-stat-value" id="exdashAttemptsTotal">0</div>
      <div class="exdash-stat-label">Attempts on your quizzes</div>
      <div class="exdash-stat-meta" id="exdashAttemptsMeta">0 completed · 0 students attempted</div>
    </div>

    <div class="exdash-stat-card">
      <div class="exdash-stat-top">
        <div class="exdash-stat-icon">
          <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="exdash-stat-kicker">Performance</div>
      </div>
      <div class="exdash-stat-value" id="exdashAveragePercentage">0%</div>
      <div class="exdash-stat-label">Average score across your quizzes</div>
      <div class="exdash-stat-meta" id="exdashTodayMeta">
        0 quizzes &amp; 0 assignments created today
      </div>
    </div>
  </div>

  {{-- Row 1: Attempts chart + activity/notifications --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="exdash-panel">
        <div class="exdash-panel-head">
          <div>
            <div class="exdash-panel-title">Attempts On Your Quizzes</div>
            <div class="exdash-panel-sub">Daily attempts started in the selected period</div>
          </div>
        </div>
        <div class="exdash-chart-shell">
          <canvas id="exdashAttemptsChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-4 d-flex flex-column gap-3">
      <div class="exdash-panel">
        <div class="exdash-panel-head">
          <div class="exdash-panel-title">Your Recent Activity</div>
          <div class="exdash-panel-sub">Last 10 actions performed by you</div>
        </div>
        <div id="exdashActivityList" class="exdash-activity-list">
          <div class="text-center text-muted py-4">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading activity…
          </div>
        </div>
      </div>

      <div class="exdash-panel">
        <div class="exdash-panel-head">
          <div class="exdash-panel-title">Notifications</div>
          <div class="exdash-panel-sub">
            <span id="exdashNotifCount">0</span> active announcement(s)
          </div>
        </div>
        <div id="exdashNotifications" class="exdash-list">
          <div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading notifications…
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Row 2: Scores & engagement metrics --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="exdash-panel">
        <div class="exdash-panel-head">
          <div>
            <div class="exdash-panel-title">Average Score Over Time</div>
            <div class="exdash-panel-sub">
              Daily average percentage on results from your quizzes
            </div>
          </div>
        </div>
        <div class="exdash-chart-shell">
          <canvas id="exdashScoresChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="exdash-panel">
        <div class="exdash-panel-head">
          <div>
            <div class="exdash-panel-title">Engagement Snapshot</div>
            <div class="exdash-panel-sub">
              How students interact with your quizzes in this period
            </div>
          </div>
        </div>

        <div class="exdash-metric-grid">
          <div class="exdash-metric-card">
            <div class="exdash-metric-label">Students Assigned</div>
            <div class="exdash-metric-value" id="exdashStudentsAssigned">0</div>
          </div>
          <div class="exdash-metric-card">
            <div class="exdash-metric-label">Students Attempted</div>
            <div class="exdash-metric-value" id="exdashStudentsAttempted">0</div>
          </div>
          <div class="exdash-metric-card">
            <div class="exdash-metric-label">Completion Rate</div>
            <div class="exdash-metric-value" id="exdashCompletionRate">0%</div>
          </div>
        </div>

        <div class="mt-2 small text-muted">
          Completion rate is based on attempts vs completed attempts on your quizzes
          in the selected period.
        </div>
      </div>
    </div>
  </div>

  {{-- Row 3: Quizzes created over time & top quizzes --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="exdash-panel">
        <div class="exdash-panel-head">
          <div class="exdash-panel-title">Quizzes Created Over Time</div>
          <div class="exdash-panel-sub">Creation trend of your quizzes</div>
        </div>
        <div class="exdash-chart-shell">
          <canvas id="exdashQuizzesChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="exdash-panel">
        <div class="exdash-panel-head">
          <div class="exdash-panel-title">Top Quizzes</div>
          <div class="exdash-panel-sub">Most attempted quizzes created by you</div>
        </div>
        <div id="exdashTopQuizzes" class="exdash-list">
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
  <div id="exdashToastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="exdashToastSuccessText">Dashboard updated</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="exdashToastError" class="toast align-items-center text-bg-danger border-0 mt-2" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="exdashToastErrorText">Failed to load dashboard data</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  window.initializeExaminerDashboard = function() {

  if (document.getElementById('dashExaminer') && 
    document.getElementById('dashExaminer').style.display !== 'none') {

(function () {
  const ENDPOINT = "{{ url('api/dashboard/examiner') }}";

  let period = '30d';
  let data = null;
  let attemptsChart = null;
  let scoresChart = null;
  let quizzesChart = null;

  const periodSelect = document.getElementById('exdashPeriod');
  const periodLabel = document.getElementById('exdashPeriodLabel');
  const refreshBtn = document.getElementById('exdashRefresh');

  const createdQuizzesTotalEl = document.getElementById('exdashCreatedQuizzesTotal');
  const createdQuizzesMetaEl = document.getElementById('exdashCreatedQuizzesMeta');
  const assignmentsTotalEl = document.getElementById('exdashAssignmentsTotal');
  const assignmentsMetaEl = document.getElementById('exdashAssignmentsMeta');
  const attemptsTotalEl = document.getElementById('exdashAttemptsTotal');
  const attemptsMetaEl = document.getElementById('exdashAttemptsMeta');
  const avgPercentageEl = document.getElementById('exdashAveragePercentage');
  const todayMetaEl = document.getElementById('exdashTodayMeta');

  const studentsAssignedEl = document.getElementById('exdashStudentsAssigned');
  const studentsAttemptedEl = document.getElementById('exdashStudentsAttempted');
  const completionRateEl = document.getElementById('exdashCompletionRate');

  const activityListEl = document.getElementById('exdashActivityList');
  const notificationsEl = document.getElementById('exdashNotifications');
  const notifCountEl = document.getElementById('exdashNotifCount');
  const topQuizzesEl = document.getElementById('exdashTopQuizzes');

  const toastSuccessEl = document.getElementById('exdashToastSuccess');
  const toastErrorEl = document.getElementById('exdashToastError');
  const toastSuccessText = document.getElementById('exdashToastSuccessText');
  const toastErrorText = document.getElementById('exdashToastErrorText');
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
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
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

  function setLoadingState() {
    activityListEl.innerHTML =
      '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Loading activity…</div>';
    notificationsEl.innerHTML =
      '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading notifications…</div>';
    topQuizzesEl.innerHTML =
      '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading quizzes…</div>';
  }

  function setErrorState() {
    activityListEl.innerHTML =
      '<div class="text-center text-danger py-4">Failed to load activity</div>';
    notificationsEl.innerHTML =
      '<div class="text-center text-danger py-3">Failed to load notifications</div>';
    topQuizzesEl.innerHTML =
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

      data = json.data || {};
      updateRangeLabel();
      renderAll();
      showSuccess('Dashboard updated');
    } catch (err) {
      console.error('[Examiner Dashboard] error:', err);
      showError(err.message || 'Failed to load dashboard data');
      setErrorState();
    }
  }

  function updateRangeLabel() {
    if (!data || !data.date_range) {
      periodLabel.textContent = periodLabelText(period);
      return;
    }
    const dr = data.date_range;
    const label = periodLabelText(dr.period || period);
    const start = fmtDateShort(dr.start);
    const end = fmtDateShort(dr.end);
    periodLabel.textContent = `${label} · ${start} – ${end}`;
  }

  function renderAll() {
    renderSummary();
    renderEngagement();
    renderActivity();
    renderNotifications();
    renderTopQuizzes();
    renderCharts();
  }

  function renderSummary() {
    const sc = data.summary_counts || {};
    const qs = data.quick_stats || {};

    const createdTotal = Number(sc.created_quizzes_total ?? 0);
    const createdActive = Number(sc.created_quizzes_active ?? 0);
    const createdArchived = Number(sc.created_quizzes_archived ?? 0);

    createdQuizzesTotalEl.textContent = createdTotal;
    createdQuizzesMetaEl.textContent =
      `${createdActive} active · ${createdArchived} archived`;

    const assignmentsTotal = Number(sc.total_assignments ?? 0);
    const assignmentsActive = Number(sc.active_assignments ?? 0);
    const studentsAssigned = Number(sc.students_assigned ?? 0);

    assignmentsTotalEl.textContent = assignmentsTotal;
    assignmentsMetaEl.textContent =
      `${assignmentsActive} active · ${studentsAssigned} students assigned`;

    const attemptsTotal = Number(sc.total_attempts_on_my_quizzes ?? 0);
    const attemptsCompleted = Number(sc.completed_attempts_on_my_quizzes ?? 0);
    const studentsAttempted = Number(sc.students_attempted ?? 0);

    attemptsTotalEl.textContent = attemptsTotal;
    attemptsMetaEl.textContent =
      `${attemptsCompleted} completed · ${studentsAttempted} students attempted`;

    const avgPct = sc.average_percentage != null
      ? Number(sc.average_percentage).toFixed(1)
      : '0.0';
    avgPercentageEl.textContent = `${avgPct}%`;

    const todayQuizzes = Number(qs.today_quizzes_created ?? 0);
    const todayAssignments = Number(qs.today_assignments_created ?? 0);
    todayMetaEl.textContent =
      `${todayQuizzes} quizzes & ${todayAssignments} assignments created today`;
  }

  function renderEngagement() {
    const sc = data.summary_counts || {};
    const qs = data.quick_stats || {};

    const studentsAssigned = Number(sc.students_assigned ?? 0);
    const studentsAttempted = Number(sc.students_attempted ?? 0);
    const attemptsTotal = Number(sc.total_attempts_on_my_quizzes ?? 0);
    const attemptsCompleted = Number(sc.completed_attempts_on_my_quizzes ?? 0);

    const completionRate = attemptsTotal
      ? (attemptsCompleted * 100 / attemptsTotal)
      : 0;

    studentsAssignedEl.textContent = studentsAssigned;
    studentsAttemptedEl.textContent = studentsAttempted;
    completionRateEl.textContent = completionRate.toFixed(1) + '%';
  }

  function renderActivity() {
    const list = data.recent_activities || [];
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
      if (a.includes('create') || a.includes('store')) iconClass = 'fa-plus';
      else if (a.includes('update') || a.includes('edit')) iconClass = 'fa-pen-to-square';
      else if (a.includes('delete') || a.includes('destroy')) iconClass = 'fa-trash';
      else if (a.includes('assign')) iconClass = 'fa-user-plus';
      else if (a.includes('quiz') || a.includes('exam')) iconClass = 'fa-clipboard-question';

      return `
        <div class="exdash-activity-item">
          <div class="exdash-activity-icon">
            <i class="fa-solid ${iconClass}"></i>
          </div>
          <div class="exdash-activity-main">
            <div class="exdash-activity-title">${title}</div>
            <div class="exdash-activity-meta">${meta}</div>
          </div>
        </div>`;
    }).join('');
  }

  function renderNotifications() {
    const notif = data.notifications || {};
    const list = notif.latest || [];
    const totalActive = Number(notif.total_active ?? 0);
    notifCountEl.textContent = totalActive;

    if (!list.length) {
      notificationsEl.innerHTML =
        '<div class="text-center text-muted py-3">No notifications</div>';
      return;
    }

    notificationsEl.innerHTML = list.map(n => {
      const title = escapeHtml(n.title || 'Notification');
      const msg = escapeHtml(n.message || '');
      const created = fmtDateTime(n.created_at);
      const priority = escapeHtml(n.priority || '').toUpperCase();
      const status = escapeHtml(n.status || '');

      return `
        <div class="exdash-list-item">
          <div class="exdash-list-main">
            <div class="exdash-list-title">${title}</div>
            <div class="exdash-list-meta">
              ${msg ? msg + ' • ' : ''}${created}
            </div>
          </div>
          <div class="exdash-list-badge">
            ${priority || status || ''}
          </div>
        </div>`;
    }).join('');
  }

  function renderTopQuizzes() {
    const list = data.top_quizzes || [];
    if (!list.length) {
      topQuizzesEl.innerHTML =
        '<div class="text-center text-muted py-3">No quiz data for this period</div>';
      return;
    }

    topQuizzesEl.innerHTML = list.map((q, idx) => {
      const name = escapeHtml(q.quiz_name || `Quiz #${q.id}`);
      const totalAttempts = Number(q.total_attempts ?? 0);
      const completed = Number(q.completed_attempts ?? 0);

      return `
        <div class="exdash-list-item">
          <div class="exdash-list-main">
            <div class="exdash-list-title">${idx + 1}. ${name}</div>
            <div class="exdash-list-meta">
              ${totalAttempts} attempts • ${completed} completed
            </div>
          </div>
          <div class="exdash-list-badge">${totalAttempts}</div>
        </div>`;
    }).join('');
  }

  function renderCharts() {
    renderAttemptsChart();
    renderScoresChart();
    renderQuizzesChart();
  }

  function renderAttemptsChart() {
    const canvas = document.getElementById('exdashAttemptsChart');
    if (!canvas) return;

    const series = data.attempts_over_time || [];
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
    const canvas = document.getElementById('exdashScoresChart');
    if (!canvas) return;

    const series = data.scores_over_time || [];
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

  function renderQuizzesChart() {
    const canvas = document.getElementById('exdashQuizzesChart');
    if (!canvas) return;

    const series = data.quizzes_over_time || [];
    const labels = series.map(r => fmtDateShort(r.date));
    const values = series.map(r => Number(r.count) || 0);

    if (quizzesChart) quizzesChart.destroy();

    const ctx = canvas.getContext('2d');
    quizzesChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Quizzes created',
          data: values,
          backgroundColor: 'rgba(59,130,246,0.35)',
          borderColor: '#3b82f6',
          borderWidth: 1.5,
          borderRadius: 6,
          maxBarThickness: 26
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

  periodSelect.addEventListener('change', () => {
    period = periodSelect.value || '30d';
    fetchDashboard();
  });

  refreshBtn.addEventListener('click', fetchDashboard);

  // Auto-refresh every 5 minutes
  setInterval(fetchDashboard, 5 * 60 * 1000);

  fetchDashboard();
})();

} else {
  console.log('[Examiner Dashboard] Skipped - not active role');
}
  }
</script>
