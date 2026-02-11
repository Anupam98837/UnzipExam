
<style>
  /* ===== Student Dashboard Shell ===== */
  .stdash-wrap{
    max-width:1180px;
    margin:16px auto 40px;
  }

  .stdash-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
  }
  .stdash-head-left{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .stdash-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    box-shadow:var(--shadow-1);
  }
  .stdash-pill-icon{
    width:26px;height:26px;
    border-radius:999px;
    display:flex;align-items:center;justify-content:center;
    background:var(--t-primary);
    color:var(--primary-color);
  }
  .stdash-title-main{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    font-size:1.15rem;
  }
  .stdash-title-sub{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }

  .stdash-head-right{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  #stdashPeriodLabel{
    font-weight:500;
    color:var(--secondary-color);
  }

  /* Toolbar */
  .stdash-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:16px;
    flex-wrap:wrap;
  }
  .stdash-filters{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .stdash-filter-chip{
    border:1px dashed var(--line-strong);
    border-radius:999px;
    padding:6px 10px;
    background:var(--surface);
    display:flex;
    align-items:center;
    gap:8px;
  }
  .stdash-filter-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  #stdashPeriod{
    min-width:130px;
    height:32px;
    padding:4px 8px;
    font-size:var(--fs-13);
    border-radius:999px;
  }

  /* Stats grid */
  .stdash-stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    margin-bottom:18px;
  }
  .stdash-stat-card{
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
  .stdash-stat-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:4px;
  }
  .stdash-stat-icon{
    width:40px;height:40px;
    border-radius:14px;
    display:flex;align-items:center;justify-content:center;
    background:var(--surface-2);
    color:var(--secondary-color);
    flex-shrink:0;
  }
  .stdash-stat-kicker{
    font-size:var(--fs-12);
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted-color);
  }
  .stdash-stat-value{
    font-size:1.7rem;
    font-weight:700;
    color:var(--ink);
    line-height:1.1;
  }
  .stdash-stat-label{
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .stdash-stat-meta{
    font-size:var(--fs-12);
    color:var(--secondary-color);
    margin-top:2px;
  }

  /* Panels */
  .stdash-panel{
    background:var(--surface);
    border:1px solid var(--line-strong);
    border-radius:16px;
    box-shadow:var(--shadow-2);
    padding:14px 14px 12px;
    height:100%;
  }
  .stdash-panel-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:8px;
  }
  .stdash-panel-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
    font-size:var(--fs-15);
  }
  .stdash-panel-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  /* Charts */
  .stdash-chart-shell{
    position:relative;
    height:260px;
  }

  /* Metrics grid */
  .stdash-metric-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:8px;
  }
  .stdash-metric-card{
    border-radius:12px;
    padding:10px 10px 8px;
    background:var(--surface-2);
  }
  .stdash-metric-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-bottom:2px;
  }
  .stdash-metric-value{
    font-size:1.15rem;
    font-weight:600;
    color:var(--ink);
  }

  /* Lists */
  .stdash-list{
    max-height:260px;
    overflow:auto;
  }
  .stdash-list-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    padding:8px 0;
    border-bottom:1px solid var(--line-soft);
  }
  .stdash-list-item:last-child{border-bottom:none;}
  .stdash-list-main{ flex:1; }
  .stdash-list-title{
    font-size:var(--fs-13);
    font-weight:500;
    color:var(--ink);
    margin-bottom:2px;
  }
  .stdash-list-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .stdash-list-badge{
    font-size:var(--fs-12);
    font-weight:600;
    color:var(--secondary-color);
    text-align:right;
    white-space:nowrap;
  }
  .stdash-badge-soft{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 8px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  @media (max-width: 768px){
    .stdash-head{flex-direction:column;align-items:flex-start;}
    .stdash-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  }
  @media (max-width: 576px){
    .stdash-metric-grid{grid-template-columns:1fr;}
  }
</style>

<div class="stdash-wrap">
  {{-- Header --}}
  <div class="stdash-head">
    <div class="stdash-head-left">
      <div class="stdash-pill">
        <div class="stdash-pill-icon">
          <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
          <div class="stdash-title-main">Student Dashboard</div>
          <div class="stdash-title-sub">
            Your progress, attempts &amp; upcoming quizzes
          </div>
        </div>
      </div>
    </div>
    <div class="stdash-head-right">
      <span class="text-muted me-1">Range:</span>
      <span id="stdashPeriodLabel">Last 30 days</span>
    </div>
  </div>

  {{-- Toolbar --}}
  <div class="stdash-toolbar">
    <div class="stdash-filters">
      <div class="stdash-filter-chip">
        <span class="stdash-filter-label">Period</span>
        <select id="stdashPeriod" class="form-select form-select-sm">
          <option value="7d">Last 7 days</option>
          <option value="30d" selected>Last 30 days</option>
          <option value="90d">Last 90 days</option>
          <option value="1y">Last 12 months</option>
        </select>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-light btn-sm" id="stdashRefresh">
        <span class="me-1"><i class="fa-solid fa-rotate-right"></i></span>
        Refresh
      </button>
    </div>
  </div>

  {{-- Primary stats --}}
  <div class="stdash-stats-grid">
    <div class="stdash-stat-card">
      <div class="stdash-stat-top">
        <div class="stdash-stat-icon">
          <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="stdash-stat-kicker">Assigned</div>
      </div>
      <div class="stdash-stat-value" id="stdashAssignedQuizzes">0</div>
      <div class="stdash-stat-label">Active quizzes assigned to you</div>
      <div class="stdash-stat-meta" id="stdashAssignedMeta">Keep going — you’ve got this.</div>
    </div>

    <div class="stdash-stat-card">
      <div class="stdash-stat-top">
        <div class="stdash-stat-icon">
          <i class="fa-solid fa-stopwatch"></i>
        </div>
        <div class="stdash-stat-kicker">Attempts</div>
      </div>
      <div class="stdash-stat-value" id="stdashTotalAttempts">0</div>
      <div class="stdash-stat-label">Attempts started by you</div>
      <div class="stdash-stat-meta" id="stdashAttemptsMeta">0 completed · 0% completion</div>
    </div>

    <div class="stdash-stat-card">
      <div class="stdash-stat-top">
        <div class="stdash-stat-icon">
          <i class="fa-solid fa-file-lines"></i>
        </div>
        <div class="stdash-stat-kicker">Results</div>
      </div>
      <div class="stdash-stat-value" id="stdashTotalResults">0</div>
      <div class="stdash-stat-label">Results generated for you</div>
      <div class="stdash-stat-meta" id="stdashBestMeta">Best: —</div>
    </div>

    <div class="stdash-stat-card">
      <div class="stdash-stat-top">
        <div class="stdash-stat-icon">
          <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="stdash-stat-kicker">Performance</div>
      </div>
      <div class="stdash-stat-value" id="stdashAvgPercentage">0%</div>
      <div class="stdash-stat-label">Your average score</div>
      <div class="stdash-stat-meta" id="stdashTodayMeta">Today: 0 started · 0 completed · 0m spent</div>
    </div>
  </div>

  {{-- Row 1: Attempts chart + right panels --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="stdash-panel">
        <div class="stdash-panel-head">
          <div>
            <div class="stdash-panel-title">Attempts Over Time</div>
            <div class="stdash-panel-sub">Daily attempts started in the selected period</div>
          </div>
        </div>
        <div class="stdash-chart-shell">
          <canvas id="stdashAttemptsChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-4 d-flex flex-column gap-3">
      <div class="stdash-panel">
        <div class="stdash-panel-head">
          <div class="stdash-panel-title">Today Snapshot</div>
          <div class="stdash-panel-sub">Your activity today</div>
        </div>

        <div class="stdash-metric-grid">
          <div class="stdash-metric-card">
            <div class="stdash-metric-label">Attempts Started</div>
            <div class="stdash-metric-value" id="stdashTodayStarted">0</div>
          </div>
          <div class="stdash-metric-card">
            <div class="stdash-metric-label">Attempts Completed</div>
            <div class="stdash-metric-value" id="stdashTodayCompleted">0</div>
          </div>
          <div class="stdash-metric-card">
            <div class="stdash-metric-label">Time Spent</div>
            <div class="stdash-metric-value" id="stdashTodayTime">0m</div>
          </div>
        </div>

        <div class="mt-2 small text-muted">
          Time spent is based on your answer-level time tracking for today.
        </div>
      </div>

      <div class="stdash-panel">
        <div class="stdash-panel-head">
          <div class="stdash-panel-title">Upcoming / Active Quizzes</div>
          <div class="stdash-panel-sub">Assigned quizzes not yet completed</div>
        </div>
        <div id="stdashUpcoming" class="stdash-list">
          <div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading upcoming quizzes…
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Row 2: Scores chart + recent attempts --}}
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="stdash-panel">
        <div class="stdash-panel-head">
          <div>
            <div class="stdash-panel-title">Average Score Over Time</div>
            <div class="stdash-panel-sub">Daily average percentage (your results)</div>
          </div>
        </div>
        <div class="stdash-chart-shell">
          <canvas id="stdashScoresChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="stdash-panel">
        <div class="stdash-panel-head">
          <div class="stdash-panel-title">Recent Attempts</div>
          <div class="stdash-panel-sub">Your last 5 attempts</div>
        </div>
        <div id="stdashRecentAttempts" class="stdash-list">
          <div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading attempts…
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080">
  <div id="stdashToastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="stdashToastSuccessText">Dashboard updated</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="stdashToastError" class="toast align-items-center text-bg-danger border-0 mt-2" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="stdashToastErrorText">Failed to load dashboard data</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(function () {
  // ========= Public initializer (called by your main /dashboard script) =========
window.initializeStudentDashboard = function() {
    // Prevent double init
    if (window.__STDASH_BOOTED__) return;
    window.__STDASH_BOOTED__ = true;

    // If student dashboard DOM isn't present, bail quietly
    if (!document.getElementById('stdashPeriod')) {
      console.log('[Student Dashboard] DOM not present; init skipped');
      return;
    }

    const ENDPOINT = "{{ url('api/dashboard/student') }}";

    let period = '30d';
    let data = null;
    let attemptsChart = null;
    let scoresChart = null;
    let autoRefreshTimer = null;

    const periodSelect = document.getElementById('stdashPeriod');
    const periodLabel  = document.getElementById('stdashPeriodLabel');
    const refreshBtn   = document.getElementById('stdashRefresh');

    const assignedEl    = document.getElementById('stdashAssignedQuizzes');
    const assignedMeta  = document.getElementById('stdashAssignedMeta');
    const totalAttempts = document.getElementById('stdashTotalAttempts');
    const attemptsMeta  = document.getElementById('stdashAttemptsMeta');
    const totalResults  = document.getElementById('stdashTotalResults');
    const bestMeta      = document.getElementById('stdashBestMeta');
    const avgPctEl      = document.getElementById('stdashAvgPercentage');
    const todayMetaEl   = document.getElementById('stdashTodayMeta');

    const todayStartedEl   = document.getElementById('stdashTodayStarted');
    const todayCompletedEl = document.getElementById('stdashTodayCompleted');
    const todayTimeEl      = document.getElementById('stdashTodayTime');

    const upcomingEl       = document.getElementById('stdashUpcoming');
    const recentAttemptsEl = document.getElementById('stdashRecentAttempts');

    const toastSuccessEl   = document.getElementById('stdashToastSuccess');
    const toastErrorEl     = document.getElementById('stdashToastError');
    const toastSuccessText = document.getElementById('stdashToastSuccessText');
    const toastErrorText   = document.getElementById('stdashToastErrorText');

    const toastSuccess = (window.bootstrap && window.bootstrap.Toast && toastSuccessEl)
      ? new bootstrap.Toast(toastSuccessEl)
      : null;

    const toastError = (window.bootstrap && window.bootstrap.Toast && toastErrorEl)
      ? new bootstrap.Toast(toastErrorEl)
      : null;

    function getToken() {
      return sessionStorage.getItem('token') || localStorage.getItem('token') || '';
    }

    function showSuccess(msg) {
      if (!toastSuccess) return;
      toastSuccessText.textContent = msg;
      toastSuccess.show();
    }

    function showError(msg) {
      if (!toastError) return;
      toastErrorText.textContent = msg;
      toastError.show();
    }
// PATCH: make escapeHtml safe for non-strings (objects/numbers/null)
function escapeHtml(str) {
  if (str === null || str === undefined) str = '';
  else if (typeof str === 'object') {
    try { str = JSON.stringify(str); } catch (e) { str = String(str); }
  } else {
    str = String(str);
  }

  return str.replace(/[&<>"']/g, s => ({
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

    function secToPretty(sec) {
      sec = Number(sec || 0);
      if (!sec) return '0m';
      const m = Math.floor(sec / 60);
      const h = Math.floor(m / 60);
      const mm = m % 60;
      if (h > 0) return `${h}h ${mm}m`;
      return `${m}m`;
    }

    function setLoadingState() {
      upcomingEl.innerHTML =
        '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading upcoming quizzes…</div>';
      recentAttemptsEl.innerHTML =
        '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-2"></div>Loading attempts…</div>';
    }

    function setErrorState() {
      upcomingEl.innerHTML =
        '<div class="text-center text-danger py-3">Failed to load upcoming quizzes</div>';
      recentAttemptsEl.innerHTML =
        '<div class="text-center text-danger py-3">Failed to load attempts</div>';
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
        console.error('[Student Dashboard] error:', err);
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
      renderToday();
      renderUpcoming();
      renderRecentAttempts();
      renderCharts();
    }

    function renderSummary() {
      const sc = data.summary_counts || {};
      const qs = data.quick_stats || {};

      const assigned = Number(sc.assigned_quizzes ?? 0);
      const attempts = Number(sc.total_attempts ?? 0);
      const completed = Number(sc.completed_attempts ?? 0);
      const results = Number(sc.total_results ?? 0);

      const avgPct = sc.average_percentage != null
        ? Number(sc.average_percentage).toFixed(1)
        : '0.0';

      const completionRate = attempts ? (completed * 100 / attempts) : 0;

      assignedEl.textContent = assigned;
      assignedMeta.textContent = assigned
        ? 'You have quizzes waiting — start one today.'
        : 'No active assignments right now.';

      totalAttempts.textContent = attempts;
      attemptsMeta.textContent = `${completed} completed · ${completionRate.toFixed(1)}% completion`;

      totalResults.textContent = results;

      const best = sc.best_performance || null;
      if (best && best.percentage != null) {
        const bPct = Number(best.percentage).toFixed(1);
        const mo = (best.marks_obtained != null) ? Number(best.marks_obtained) : null;
        const tm = (best.total_marks != null) ? Number(best.total_marks) : null;
        bestMeta.textContent = (mo != null && tm != null)
          ? `Best: ${bPct}% (${mo}/${tm})`
          : `Best: ${bPct}%`;
      } else {
        bestMeta.textContent = 'Best: —';
      }

      avgPctEl.textContent = `${avgPct}%`;

      const tStarted = Number(qs.today_attempts_started ?? 0);
      const tCompleted = Number(qs.today_attempts_completed ?? 0);
      const tTime = secToPretty(qs.today_time_spent_sec ?? 0);
      todayMetaEl.textContent = `Today: ${tStarted} started · ${tCompleted} completed · ${tTime} spent`;
    }

    function renderToday() {
      const qs = data.quick_stats || {};
      todayStartedEl.textContent = Number(qs.today_attempts_started ?? 0);
      todayCompletedEl.textContent = Number(qs.today_attempts_completed ?? 0);
      todayTimeEl.textContent = secToPretty(qs.today_time_spent_sec ?? 0);
    }

    function renderUpcoming() {
      const list = data.upcoming_quizzes || [];
      if (!list.length) {
        upcomingEl.innerHTML = '<div class="text-center text-muted py-3">No upcoming quizzes</div>';
        return;
      }

      upcomingEl.innerHTML = list.map((q, idx) => {
        const name = escapeHtml(q.quiz_name || `Quiz #${q.quiz_id || (idx+1)}`);
        const assignedAt = q.assigned_at ? fmtDateTime(q.assigned_at) : '';
        const code = escapeHtml(q.assignment_code || '');
        const tq = Number(q.total_questions ?? 0);
        const ttime = escapeHtml(q.total_time ?? '');
        const right = code ? `<span class="stdash-badge-soft"><i class="fa-solid fa-hashtag"></i>${code}</span>` : '';

        return `
g            <div class="stdash-list-main">
              <div class="stdash-list-title">${idx + 1}. ${name}</div>
              <div class="stdash-list-meta">
                ${tq} questions${ttime ? ' • ' + ttime : ''}${assignedAt ? ' • Assigned ' + assignedAt : ''}
              </div>
            </div>
            <div class="stdash-list-badge">${right}</div>
          </div>`;
      }).join('');
    }

    function renderRecentAttempts() {
      const list = data.recent_attempts || [];
      if (!list.length) {
        recentAttemptsEl.innerHTML = '<div class="text-center text-muted py-3">No attempts yet</div>';
        return;
      }

      recentAttemptsEl.innerHTML = list.map((a, idx) => {
        const quizName = escapeHtml(a.quiz_name || `Quiz #${a.quiz_id || (idx+1)}`);
        const status = escapeHtml(a.attempt_status || '');
        const started = a.started_at ? fmtDateTime(a.started_at) : (a.created_at ? fmtDateTime(a.created_at) : '');
        const finished = a.finished_at ? fmtDateTime(a.finished_at) : '';
        const pct = (a.result_percentage != null && a.result_percentage !== '')
          ? Number(a.result_percentage).toFixed(1) + '%'
          : '';

        const marks = (a.marks_obtained != null && a.total_marks != null)
          ? `${Number(a.marks_obtained)}/${Number(a.total_marks)}`
          : '';

        const right = pct || marks || status
          ? `<div class="stdash-list-badge">${pct || ''}${(pct && marks) ? '<br>' : ''}${marks || ''}<div class="small text-muted">${status}</div></div>`
          : `<div class="stdash-list-badge">${status}</div>`;

        const metaParts = [];
        if (started) metaParts.push('Started ' + started);
        if (finished) metaParts.push('Finished ' + finished);

        return `
          <div class="stdash-list-item">
            <div class="stdash-list-main">
              <div class="stdash-list-title">${idx + 1}. ${quizName}</div>
              <div class="stdash-list-meta">${metaParts.join(' • ') || '—'}</div>
            </div>
            ${right}
          </div>`;
      }).join('');
    }

    function renderCharts() {
      if (typeof Chart === 'undefined') {
        console.warn('[Student Dashboard] Chart.js missing; charts skipped');
        return;
      }
      renderAttemptsChart();
      renderScoresChart();
    }

    function renderAttemptsChart() {
      const canvas = document.getElementById('stdashAttemptsChart');
      if (!canvas) return;

      const series = data.attempts_over_time || [];
      const labels = series.map(r => fmtDateShort(r.date));
      const values = series.map(r => Number(r.count) || 0);

      if (attemptsChart) attemptsChart.destroy();

      attemptsChart = new Chart(canvas.getContext('2d'), {
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
      const canvas = document.getElementById('stdashScoresChart');
      if (!canvas) return;

      const series = data.scores_over_time || [];
      const labels = series.map(r => fmtDateShort(r.date));
      const values = series.map(r => Number(r.avg_percentage) || 0);

      if (scoresChart) scoresChart.destroy();

      scoresChart = new Chart(canvas.getContext('2d'), {
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
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%', precision: 0 } },
            x: { grid: { display: false } }
          }
        }
      });
    }

    // Controls
    periodSelect.addEventListener('change', () => {
      period = periodSelect.value || '30d';
      fetchDashboard();
    });

    refreshBtn.addEventListener('click', fetchDashboard);

    // Auto-refresh every 5 minutes (only once)
    autoRefreshTimer = setInterval(fetchDashboard, 5 * 60 * 1000);

    // Initial load
    fetchDashboard();

    console.log('[Student Dashboard] Initialized ✅');
  };

  // ========= Optional: auto-boot when role event fires =========
  window.addEventListener('dash:role', function (e) {
    const role = e && e.detail && e.detail.role ? String(e.detail.role) : '';
    if (role === 'student') window.initializeStudentDashboard();
  });

  // ========= Standalone student dashboard page support =========
  // If this page is opened directly (no multi-role shell), initialize on DOM ready.
  document.addEventListener('DOMContentLoaded', function () {
    const dashStudent = document.getElementById('dashStudent');
    const standalone = !dashStudent; // no role-switch wrapper => standalone page
    if (standalone) window.initializeStudentDashboard();
  }, { once: true });

})();
</script>
