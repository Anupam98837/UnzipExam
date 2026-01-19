{{-- resources/views/examiner/door_game/viewAssignedStudentResult.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Assigned Students Results')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
  .asr-layout{
    display:grid;
    grid-template-columns: minmax(0,2.2fr) minmax(0,1.3fr);
    gap:18px;
  }
  @media (max-width: 992px){
    .asr-layout{ grid-template-columns: minmax(0,1fr); }
  }

  /* Header */
  .asr-header-main{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    flex-wrap:wrap;
  }
  .asr-head-left{
    display:flex;align-items:flex-start;gap:10px;
    min-width:240px;
  }
  .asr-head-icon{
    width:40px;height:40px;border-radius:14px;
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    display:flex;align-items:center;justify-content:center;
    color:var(--accent-color);
    flex-shrink:0;
  }
  .asr-head-title{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    font-size:1.1rem;
  }
  .asr-head-sub{
    color:var(--muted-color);
    font-size:var(--fs-13);
  }
  .asr-head-meta{
    display:flex;align-items:center;gap:6px;
    color:var(--muted-color);
    font-size:var(--fs-12);
    white-space:nowrap;
    padding-top:6px;
  }
  @media (max-width: 576px){
    .asr-head-meta{white-space:normal;}
  }

  /* Filters */
  .asr-filters{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:10px;
    margin-top:16px;
  }

  /* ✅ same height for all controls */
  .asr-filters .form-control,
  .asr-filters .form-select{
    border-radius:999px;
    padding-left:14px;
    padding-right:14px;
    height:42px;
    min-height:42px;
  }
  .asr-filters .btn{
    border-radius:999px;
    height:42px;
    min-height:42px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:0 14px;
    white-space:nowrap;
  }

  .asr-filters-quiz{
    min-width:260px;
    flex:0 0 260px;
  }
  .asr-search-wrap{
    flex:1 1 320px;
    min-width:240px;
  }
  .asr-status-wrap{
    flex:0 0 160px;
    min-width:150px;
  }
  .asr-sort-wrap{
    flex:0 0 220px;
    min-width:200px;
  }
  .asr-refresh-wrap{
    flex:0 0 auto;
  }

  @media (max-width: 992px){
    .asr-filters-quiz{flex:1 1 260px;}
    .asr-status-wrap{flex:1 1 180px;}
    .asr-sort-wrap{flex:1 1 220px;}
    .asr-refresh-wrap{flex:1 1 160px;}
  }

  /* Metrics */
  .asr-metrics-row{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin:18px 0 14px;
  }
  @media (max-width: 992px){
    .asr-metrics-row{ grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
  @media (max-width: 576px){
    .asr-metrics-row{ grid-template-columns:repeat(1,minmax(0,1fr)); }
  }
  .asr-metric{
    border-radius:14px;
    border:1px solid var(--line-soft);
    background:var(--surface-2);
    padding:10px 12px;
    display:flex;
    flex-direction:column;
    gap:4px;
    min-height:86px;
  }
  .asr-metric-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    display:flex;align-items:center;gap:6px;
  }
  .asr-metric-value{
    font-size:1.1rem;
    font-weight:700;
    color:var(--ink);
    line-height:1.2;
  }
  .asr-metric-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  /* Table Card */
  .asr-table-card{
    border-radius:16px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    box-shadow:var(--shadow-2);
    overflow:hidden;
  }
  .asr-table-head{
    display:flex;align-items:center;justify-content:space-between;
    gap:10px;
    padding:10px 14px;
    border-bottom:1px solid var(--line-strong);
    background:var(--surface-2);
  }
  .asr-table-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
  }

  /* ✅ better scroll + stable width */
  .asr-table-body-wrap{
    max-height:420px;
    overflow:auto;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }
  .asr-table-body-wrap table{
    margin-bottom:0;
    min-width:980px; /* ✅ prevents column collapsing */
  }

  .asr-table-body-wrap thead th{
    position:sticky;
    top:0;
    background:var(--surface-2);
    z-index:2;
  }
  .asr-table-body-wrap tbody tr:hover{ background:var(--surface-3); }

  .asr-table-body-wrap th,
  .asr-table-body-wrap td{
    vertical-align:middle !important;
  }

  /* Column tweaks */
  .asr-table-body-wrap thead th:nth-child(1),
  .asr-table-body-wrap tbody td:nth-child(1){
    width:52px;
    text-align:center;
    color:var(--muted-color);
  }
  .asr-table-body-wrap thead th:nth-child(3),
  .asr-table-body-wrap tbody td:nth-child(3){
    width:120px;
    white-space:nowrap;
  }
  .asr-table-body-wrap thead th:nth-child(4),
  .asr-table-body-wrap tbody td:nth-child(4){
    width:95px;
    text-align:center;
    white-space:nowrap;
  }
  .asr-table-body-wrap thead th:nth-child(5),
  .asr-table-body-wrap tbody td:nth-child(5){
    width:170px;
    white-space:nowrap;
  }
  .asr-table-body-wrap thead th:nth-child(6),
  .asr-table-body-wrap tbody td:nth-child(6){
    width:120px;
    white-space:nowrap;
  }
  .asr-table-body-wrap thead th:nth-child(7),
  .asr-table-body-wrap tbody td:nth-child(7){
    width:260px;
    white-space:nowrap;
  }

  .asr-student-cell{ display:flex;flex-direction:column;gap:2px; }
  .asr-student-name{ font-weight:700;color:var(--ink); }
  .asr-student-meta{ font-size:var(--fs-12);color:var(--muted-color); }

  /* Status badge */
  .asr-status-badge{
    display:inline-flex;align-items:center;gap:6px;
    padding:4px 10px;
    border-radius:999px;
    font-size:var(--fs-12);
    font-weight:700;
    white-space:nowrap;
  }
  .asr-status-badge span{
    width:8px;height:8px;border-radius:50%;
    background:currentColor;
  }
  .asr-status-pass{
    background:var(--t-success);
    color:#15803d;
    border:1px solid rgba(22,163,74,.2);
  }
  .asr-status-fail{
    background:var(--t-danger);
    color:#b91c1c;
    border:1px solid rgba(220,38,38,.2);
  }
  .asr-status-na{
    background:var(--surface-3);
    color:var(--muted-color);
    border:1px solid var(--line-soft);
  }

  /* ✅ Actions aligned */
  .asr-actions{
    display:flex;
    gap:8px;
    justify-content:flex-end;
    align-items:center;
    flex-wrap:nowrap;
  }
  .asr-actions select{
    min-width:170px;
    height:34px;
  }
  .asr-actions .btn{
    height:34px;
    display:inline-flex;
    align-items:center;
    gap:6px;
    white-space:nowrap;
  }

  @media (max-width: 768px){
    .asr-actions{
      justify-content:flex-start;
      flex-wrap:wrap;
    }
    .asr-actions select{
      min-width:160px;
      flex:1 1 160px;
    }
    .asr-actions .btn{
      flex:0 0 auto;
    }
  }

  .asr-empty{
    border-radius:10px;border:1px dashed var(--line-strong);
    padding:18px 14px;text-align:center;
    color:var(--muted-color);background:var(--surface-2);
    font-size:var(--fs-13);
  }
  .asr-loading{
    display:inline-flex;align-items:center;gap:8px;
    font-size:var(--fs-13);color:var(--muted-color);
  }
  .asr-spinner{
    width:16px;height:16px;border-radius:50%;
    border:2px solid #0001;border-top-color:var(--accent-color);
    animation:asr-spin 1s linear infinite;
  }
  @keyframes asr-spin{to{transform:rotate(360deg)}}

  /* Right side */
  .asr-side-card{
    border-radius:16px;border:1px solid var(--line-strong);
    background:var(--surface);box-shadow:var(--shadow-2);
    padding:14px 15px 12px;
  }
  .asr-side-head{
    display:flex;align-items:center;justify-content:space-between;
    gap:8px;margin-bottom:8px;
  }
  .asr-side-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
  }
  .asr-side-small{ font-size:var(--fs-12);color:var(--muted-color); }

  .asr-dist-label{ font-weight:700;font-size:var(--fs-13);margin-top:4px; }
  .asr-dist-bar{
    margin-top:6px;border-radius:999px;background:var(--surface-2);
    border:1px solid var(--line-soft);height:12px;overflow:hidden;display:flex;
  }
  .asr-dist-seg{ height:100%; }
  .asr-dist-pass{ background:var(--t-success); }
  .asr-dist-fail{ background:var(--t-danger); }
  .asr-dist-pending{ background:var(--t-info); }

  .asr-dist-legend{
    display:flex;flex-wrap:wrap;gap:10px;
    margin-top:8px;font-size:var(--fs-12);color:var(--muted-color);
  }
  .asr-dist-legend span{ display:inline-flex;align-items:center;gap:6px; }
  .asr-dot{ width:8px;height:8px;border-radius:50%;background:currentColor; }

  .asr-snap{ margin-top:14px;font-size:var(--fs-13); }
  .asr-snap-row{
    display:flex;justify-content:space-between;
    padding:6px 0;border-bottom:1px dashed var(--line-soft);
  }
  .asr-snap-row:last-child{ border-bottom:none; }

  .asr-side-error{ margin-top:10px;font-size:var(--fs-12);display:none; }

  /* Detail */
  .asr-detail-card{
    border-radius:14px;border:1px solid var(--line-soft);
    background:var(--surface-2);
    padding:10px 12px;margin-top:12px;
    max-height:330px;overflow:auto;
  }
  .asr-detail-head{
    display:flex;align-items:center;justify-content:space-between;
    gap:10px;margin-bottom:8px;
  }
  .asr-detail-title{ font-weight:700;color:var(--ink); }
  .asr-detail-sub{ font-size:var(--fs-12);color:var(--muted-color); }
  .asr-detail-body{ display:flex;flex-direction:column;gap:10px; }

  .asr-q-item{
    border-radius:10px;border:1px solid var(--line-soft);
    background:var(--surface);
    padding:8px 9px;
  }
  .asr-q-head{
    display:flex;justify-content:space-between;align-items:center;
    gap:8px;margin-bottom:4px;
  }
  .asr-q-title{
    font-weight:700;font-size:var(--fs-13);color:var(--ink);
  }
  .asr-chip-correct,
  .asr-chip-wrong{
    padding:3px 8px;border-radius:999px;
    font-size:var(--fs-12);font-weight:800;
  }
  .asr-chip-correct{
    background:var(--t-success);color:#15803d;
    border:1px solid rgba(22,163,74,.2);
  }
  .asr-chip-wrong{
    background:var(--t-danger);color:#b91c1c;
    border:1px solid rgba(220,38,38,.2);
  }
  .asr-q-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-bottom:4px;
  }
  .asr-q-body{ font-size:var(--fs-13); }

  /* Dark */
  html.theme-dark .asr-metric{background:#04151f;border-color:var(--line-strong);}
  html.theme-dark .asr-empty{background:#04151f;}
  html.theme-dark .asr-side-card{background:#04151f;}
  html.theme-dark .asr-detail-card{background:#020b13;}
  html.theme-dark .asr-q-item{background:#04151f;}
</style>

<div class="sm-wrap" id="asrRoot" data-game-key="{{ $gameUuid ?? '' }}">
  <div class="card sm">
    <div class="card-header">
      <div class="asr-header-main">
        <div class="asr-head-left">
          <div class="asr-head-icon">
            <i class="fa-solid fa-user-graduate"></i>
          </div>
          <div>
            <div class="asr-head-title" id="asrGameTitle">Assigned students results</div>
            <div class="asr-head-sub" id="asrGameSub">
              View student-wise performance and overall analytics.
            </div>
          </div>
        </div>
        <div class="asr-head-meta" id="asrGameMeta">
          <i class="fa-regular fa-circle-question"></i>
          <span>Select a game to view analytics</span>
        </div>
      </div>

      <div class="asr-filters">
        <div class="asr-filters-quiz">
          <select id="asrGameSelect" class="form-select">
            <option value="">Loading games…</option>
          </select>
        </div>

        <div class="asr-search-wrap">
          <input id="asrSearch" type="text" class="form-control"
                 placeholder="Search by student name, email, roll…">
        </div>

        <div class="asr-status-wrap">
          <select id="asrStatusFilter" class="form-select">
            <option value="all">All statuses</option>
            <option value="win">Win</option>
            <option value="fail">Fail</option>
            <option value="timeout">Timeout</option>
          </select>
        </div>

        <div class="asr-sort-wrap">
          <select id="asrSort" class="form-select">
            <option value="last_attempt">Last attempt (newest → oldest)</option>
            <option value="name">Name (A → Z)</option>
            <option value="best_desc">Best score (high → low)</option>
            <option value="best_asc">Best score (low → high)</option>
            <option value="attempts">Most attempts</option>
          </select>
        </div>

        <div class="asr-refresh-wrap">
          <button id="asrRefresh" type="button" class="btn btn-light">
            <i class="fa-solid fa-rotate"></i>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <div class="card-body">
      <div class="asr-metrics-row">
        <div class="asr-metric">
          <div class="asr-metric-label">
            <i class="fa-regular fa-user"></i> Total Students
          </div>
          <div class="asr-metric-value" id="asrMetricStudents">—</div>
          <div class="asr-metric-sub" id="asrMetricAttempted">Attempted: —</div>
        </div>
        <div class="asr-metric">
          <div class="asr-metric-label">
            <i class="fa-regular fa-chart-bar"></i> Average Score
          </div>
          <div class="asr-metric-value" id="asrMetricAvgPercent">—</div>
          <div class="asr-metric-sub" id="asrMetricAvgMarks">Score: —</div>
        </div>
        <div class="asr-metric">
          <div class="asr-metric-label">
            <i class="fa-solid fa-medal"></i> Best Performer
          </div>
          <div class="asr-metric-value" id="asrMetricTopPercent">—</div>
          <div class="asr-metric-sub" id="asrMetricTopName">—</div>
        </div>
        <div class="asr-metric">
          <div class="asr-metric-label">
            <i class="fa-solid fa-flag-checkered"></i> Win Rate
          </div>
          <div class="asr-metric-value" id="asrMetricPassRate">—</div>
          <div class="asr-metric-sub" id="asrMetricPassNumbers">—</div>
        </div>
      </div>

      <div class="asr-layout">
        {{-- Left: students table --}}
        <div>
          <div class="asr-table-card">
            <div class="asr-table-head">
              <div class="asr-table-title">Students &amp; Attempts</div>
              <div id="asrTableInfo" class="text-muted" style="font-size:var(--fs-12);">
                Select a game to view results
              </div>
            </div>
            <div class="asr-table-body-wrap table-wrap">
              <table class="table table-hover table-sm mb-0">
                <thead>
                  <tr>
                    <th style="width:40px;">#</th>
                    <th>Student</th>
                    <th>Best Score</th>
                    <th>Attempts</th>
                    <th>Last attempt</th>
                    <th>Status</th>
                    <th style="width:260px;">Actions</th>
                  </tr>
                </thead>
                <tbody id="asrTableBody">
                  <tr>
                    <td colspan="7">
                      <div class="asr-empty">
                        <div class="asr-loading">
                          <span class="asr-spinner"></span>
                          <span>Loading assigned student results…</span>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- Right: analytics + detail --}}
        <div>
          <div class="asr-side-card">
            <div class="asr-side-head">
              <div>
                <div class="asr-side-title">Overall Analytics</div>
                <div class="asr-side-small" id="asrSideSmallMeta">
                  Select a game to see distribution.
                </div>
              </div>
            </div>

            <div class="asr-dist">
              <div class="asr-dist-label">Distribution</div>
              <div class="asr-dist-bar">
                <div id="asrSegPass" class="asr-dist-seg asr-dist-pass" style="width:0%;"></div>
                <div id="asrSegFail" class="asr-dist-seg asr-dist-fail" style="width:0%;"></div>
                <div id="asrSegPending" class="asr-dist-seg asr-dist-pending" style="width:0%;"></div>
              </div>
              <div class="asr-dist-legend">
                <span><span class="asr-dot" style="color:#16a34a;"></span>
                  <span id="asrLegPass">Win: —</span></span>
                <span><span class="asr-dot" style="color:#dc2626;"></span>
                  <span id="asrLegFail">Fail: —</span></span>
                <span><span class="asr-dot" style="color:#6366f1;"></span>
                  <span id="asrLegPending">Not attempted: —</span></span>
              </div>
            </div>

            <div class="asr-snap">
              <div class="section-title" style="margin-top:8px;">Snapshot</div>
              <div class="asr-snap-row">
                <span>Total attempts</span>
                <span id="asrSnapAttempts">—</span>
              </div>
              <div class="asr-snap-row">
                <span>Avg. time used</span>
                <span id="asrSnapAvgTime">—</span>
              </div>
              <div class="asr-snap-row">
                <span>Avg. moves</span>
                <span id="asrSnapAvgCorrect">—</span>
              </div>
              <div class="asr-snap-row">
                <span>Avg. timeouts</span>
                <span id="asrSnapAvgWrong">—</span>
              </div>
            </div>

            <div id="asrSideError" class="asr-side-error alert alert-danger"></div>

            <div id="asrDetailContainer" class="mt-2">
              <div class="asr-empty" style="margin-top:10px;">
                Select a student &amp; attempt to see move-wise breakdown.
              </div>
            </div>
          </div>
        </div>
      </div>{{-- /.asr-layout --}}
    </div>
  </div>
</div>

<script>
(function(){
  const rootEl = document.getElementById('asrRoot');
  if (!rootEl) {
    console.warn('asrRoot not found; Assigned Students Results JS skipped.');
    return;
  }

  const urlParams = new URLSearchParams(window.location.search);

  const datasetGameKey    = (rootEl.dataset.gameKey || '').trim(); // ideally game UUID
  const urlGameUuidParam  = (urlParams.get('game_uuid') || '').trim();
  const urlGameParam      = (urlParams.get('game') || '').trim();

  let gameKey = datasetGameKey || urlGameUuidParam || '';
  let gameRowsCache = [];

  const state = {
    raw: null,
    filtered: [],
    filters: { search: '', status: 'all', sort: 'last_attempt' }
  };

  const els = {
    gameSelect: document.getElementById('asrGameSelect'),

    gameTitle: document.getElementById('asrGameTitle'),
    gameSub: document.getElementById('asrGameSub'),
    gameMeta: document.getElementById('asrGameMeta'),

    metricStudents: document.getElementById('asrMetricStudents'),
    metricAttempted: document.getElementById('asrMetricAttempted'),
    metricAvgPercent: document.getElementById('asrMetricAvgPercent'),
    metricAvgMarks: document.getElementById('asrMetricAvgMarks'),
    metricTopPercent: document.getElementById('asrMetricTopPercent'),
    metricTopName: document.getElementById('asrMetricTopName'),
    metricPassRate: document.getElementById('asrMetricPassRate'),
    metricPassNumbers: document.getElementById('asrMetricPassNumbers'),

    tableInfo: document.getElementById('asrTableInfo'),
    tableBody: document.getElementById('asrTableBody'),

    segPass: document.getElementById('asrSegPass'),
    segFail: document.getElementById('asrSegFail'),
    segPending: document.getElementById('asrSegPending'),
    legPass: document.getElementById('asrLegPass'),
    legFail: document.getElementById('asrLegFail'),
    legPending: document.getElementById('asrLegPending'),
    sideSmallMeta: document.getElementById('asrSideSmallMeta'),

    snapAttempts: document.getElementById('asrSnapAttempts'),
    snapAvgTime: document.getElementById('asrSnapAvgTime'),
    snapAvgCorrect: document.getElementById('asrSnapAvgCorrect'),
    snapAvgWrong: document.getElementById('asrSnapAvgWrong'),

    detailContainer: document.getElementById('asrDetailContainer'),
    sideError: document.getElementById('asrSideError'),

    search: document.getElementById('asrSearch'),
    statusFilter: document.getElementById('asrStatusFilter'),
    sort: document.getElementById('asrSort'),
    refresh: document.getElementById('asrRefresh'),
  };

  function getToken(){
    return sessionStorage.getItem('token')
        || sessionStorage.getItem('auth_token')
        || localStorage.getItem('token')
        || '';
  }

  function updateUrlGame(key){
    try{
      const url = new URL(window.location.href);
      if (key) {
        url.searchParams.set('game', key);
        url.searchParams.set('game_uuid', key);
      } else {
        url.searchParams.delete('game');
        url.searchParams.delete('game_uuid');
      }
      window.history.replaceState({}, '', url.toString());
    }catch(e){}
  }

  function fmtPercent(val){
    if (val === null || val === undefined || isNaN(val)) return '—';
    return Number(val).toFixed(1) + '%';
  }
  function fmtNumber(val){
    if (val === null || val === undefined || isNaN(val)) return '—';
    return Number(val).toString();
  }
  function fmtDate(val){
    if (!val) return '—';
    try{
      const d = new Date(val);
      if (isNaN(d.getTime())) return val;
      return d.toLocaleString();
    }catch(e){ return val; }
  }
  function msToPretty(ms){
    ms = Number(ms || 0);
    if (ms <= 0) return '—';
    const sec = Math.round(ms / 1000);
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    if (!m) return s + 's';
    return m + 'm ' + (s ? s + 's' : '');
  }

  /* ---------------- Core normalisation helpers ---------------- */

  function extractGameKey(row){
    const game = (row && (row.game || row.door_game)) ? (row.game || row.door_game) : null;
    const key =
      row.game_uuid ||
      row.door_game_uuid ||
      row.game_key ||
      row.door_game_key ||
      (game && (game.uuid || game.key)) ||
      row.door_game_id ||
      row.game_id ||
      row.uuid ||
      row.id;
    return key ? String(key) : '';
  }

  function decideInitialGameKey(rows){
    if (!rows || !rows.length) return '';
    if (gameKey){
      const keyStr = String(gameKey);
      const byKey = rows.find(r => {
        const k   = extractGameKey(r);
        const gid = (r.door_game_id != null) ? String(r.door_game_id) : ((r.game_id != null) ? String(r.game_id) : null);
        return (k && String(k) === keyStr) || (gid && gid === keyStr);
      });
      if (byKey) return extractGameKey(byKey);
    }

    if (urlGameParam){
      const idStr = String(urlGameParam);
      const byAssign = rows.find(r => {
        const rId  = (r.id != null) ? String(r.id) : null;
        const gid  = (r.door_game_id != null) ? String(r.door_game_id) : ((r.game_id != null) ? String(r.game_id) : null);
        const gu   = (r.door_game_uuid != null) ? String(r.door_game_uuid) : ((r.game_uuid != null) ? String(r.game_uuid) : null);
        return (rId && rId === idStr) || (gid && gid === idStr) || (gu && gu === idStr);
      });
      if (byAssign) return extractGameKey(byAssign);
    }

    return extractGameKey(rows[0]);
  }

  function extractRowsFromGameResponse(json){
    if (!json || typeof json !== 'object') return [];
    let rows = [];

    if (Array.isArray(json.data)) rows = json.data;
    else if (json.data && Array.isArray(json.data.data)) rows = json.data.data;
    else if (json.data && Array.isArray(json.data.items)) rows = json.data.items;
    else if (Array.isArray(json.games)) rows = json.games;
    else if (Array.isArray(json.door_games)) rows = json.door_games;
    else if (Array.isArray(json.rows)) rows = json.rows;
    else if (Array.isArray(json)) rows = json;

    if (!rows.length){
      const candidates = [json.data, json];
      for (const lvl of candidates){
        if (!lvl || typeof lvl !== 'object') continue;
        for (const k in lvl){
          if (Array.isArray(lvl[k])) { rows = lvl[k]; break; }
        }
        if (rows.length) break;
      }
    }
    return rows;
  }

  function safeParseJson(raw){
    try{
      if (raw == null) return null;
      if (typeof raw === 'object') return raw;
      const s = String(raw).trim();
      if (!s) return null;
      return JSON.parse(s);
    }catch(e){ return null; }
  }

  function normalizeFromAttempts(){
    if (!state.raw) return;

    const attempts = state.raw.attempts || [];
    const stats   = state.raw.stats || {};
    const game    = state.raw.game || state.raw.door_game || {};

    const studentMap = new Map();

    let sumScore = 0;
    let sumPct   = 0;
    let countPct = 0;

    let topStudentName = null;
    let topStudentPercent = null;

    let timeoutCount = 0;

    attempts.forEach(a => {
      const id = a.student_id != null ? a.student_id : (a.user_id != null ? a.user_id : null);
      if (id == null) return;

      if (!studentMap.has(id)) {
        studentMap.set(id, {
          id,
          name: a.student_name || a.name || a.user_name || 'Student',
          email: a.student_email || a.email || a.user_email || '',
          roll_no: a.student_roll_no || a.roll_no || '',
          attempts_count: 0,
          last_attempt_at: null,
          best_result: null,
          attempts: []
        });
      }
      const st = studentMap.get(id);
      st.attempts_count++;

      const lastAt = a.result_created_at || a.created_at || a.finished_at || a.submitted_at || a.started_at;
      if (lastAt && (!st.last_attempt_at || new Date(lastAt) > new Date(st.last_attempt_at))) {
        st.last_attempt_at = lastAt;
      }

      const score = (a.score != null) ? Number(a.score) : null;
      const pct = (a.percentage != null) ? Number(a.percentage)
               : (score != null ? (score * 100) : null);

      const status = String(a.status || '').toLowerCase();
      if (status === 'timeout') timeoutCount++;

      st.attempts.push({
        result_id: a.result_id || a.uuid || a.id,
        attempt_number: a.attempt_no || a.attempt_number,
        percentage: pct,
        score: score,
        status: status,
        result_created_at: lastAt
      });

      const currentBest = st.best_result;
      if (pct != null) {
        if (!currentBest || pct > (currentBest.percentage ?? -1)) {
          const isWin = status === 'win' || score === 1;
          st.best_result = {
            result_id: a.result_id || a.uuid || a.id,
            score,
            percentage: pct,
            is_pass: isWin,
            status
          };

          if (topStudentPercent === null || pct > topStudentPercent) {
            topStudentPercent = pct;
            topStudentName = st.name;
          }
        }
        sumPct += pct;
        countPct++;
      }

      if (score != null) sumScore += score;
    });

    studentMap.forEach(st => {
      st.attempts.sort((a,b) => (b.attempt_number || 0) - (a.attempt_number || 0));
    });

    const totalAttempts   = stats.total_attempts ?? attempts.length;
    const uniqueAttempted = stats.unique_attempted ?? stats.unique_students ?? studentMap.size;
    const assignedStudents= stats.total_assigned_students ?? stats.assigned_students ??
                            stats.total_students ?? stats.total_assigned ?? null;

    const avgPct = (stats.avg_percentage != null)
      ? stats.avg_percentage
      : (countPct ? (sumPct / countPct) : null);

    const avgScore = totalAttempts ? (sumScore / totalAttempts) : null;

    let winCount = 0;
    let failCount = 0;

    studentMap.forEach(st => {
      const br = st.best_result;
      if (!br || br.percentage == null) return;
      if (br.is_pass) winCount++;
      else failCount++;
    });

    state.raw.students = Array.from(studentMap.values());
    state.raw.statsNorm = {
      totalAttempts,
      uniqueStudents: uniqueAttempted,
      uniqueAttempted,
      assignedStudents,
      avgPct,
      avgScore,
      passCount: winCount,
      failCount: failCount,
      timeoutCount,
      topStudentName,
      topStudentPercent
    };
  }

  function applyFilters(){
    if (!state.raw || !state.raw.students) { state.filtered = []; return; }

    const students = state.raw.students;
    const search = state.filters.search.toLowerCase();
    const status = state.filters.status;
    const sort   = state.filters.sort;

    let arr = students.slice();

    if (search){
      arr = arr.filter(st => {
        const name  = (st.name || '').toLowerCase();
        const email = (st.email || '').toLowerCase();
        const roll  = (st.roll_no || '').toLowerCase();
        return name.includes(search) || email.includes(search) || roll.includes(search);
      });
    }

    if (status !== 'all'){
      arr = arr.filter(st => {
        const best = st.best_result || null;
        if (!best) return false;

        if (status === 'win') return !!best.is_pass;
        if (status === 'fail') return (best.percentage != null) && !best.is_pass;
        if (status === 'timeout') return String(best.status || '').toLowerCase() === 'timeout';

        return true;
      });
    }

    arr.sort((a,b) => {
      const bestA = a.best_result || {};
      const bestB = b.best_result || {};
      switch(sort){
        case 'name': return (a.name || '').localeCompare(b.name || '');
        case 'best_desc': return (bestB.percentage || 0) - (bestA.percentage || 0);
        case 'best_asc': return (bestA.percentage || 0) - (bestB.percentage || 0);
        case 'attempts': return (b.attempts_count || 0) - (a.attempts_count || 0);
        case 'last_attempt':
        default:
          return new Date(b.last_attempt_at || 0) - new Date(a.last_attempt_at || 0);
      }
    });

    state.filtered = arr;
  }

  function renderHeader(){
    if (!state.raw) return;
    const game = state.raw.game || state.raw.door_game || {};
    if (els.gameTitle) els.gameTitle.textContent = game.title || game.game_title || game.name || 'Assigned Students Results';
    if (els.gameSub) els.gameSub.textContent = 'View student-wise performance and analytics for this door game.';

    const grid = (game.grid_dim != null) ? (game.grid_dim + '×' + game.grid_dim) : '—';
    const timeLimit = (game.time_limit_sec != null) ? (game.time_limit_sec + 's') : '—';

    if (els.gameMeta) {
      els.gameMeta.innerHTML =
        `<i class="fa-regular fa-circle-question"></i>
         <span>Grid: ${grid} • Time limit: ${timeLimit}</span>`;
    }
  }

  function renderMetrics(){
    if (!state.raw) return;
    const s = state.raw.statsNorm || {};

    const uniqueAttempted = s.uniqueAttempted ?? s.uniqueStudents ?? 0;
    const totalStudents   = s.assignedStudents ?? uniqueAttempted;
    const avgPercent      = s.avgPct;
    const avgScore        = s.avgScore;
    const bestPercent     = s.topStudentPercent;
    const bestName        = s.topStudentName || '—';
    const winCount        = s.passCount ?? 0;
    const failCount       = s.failCount ?? 0;
    const winRate         = uniqueAttempted ? (winCount / uniqueAttempted * 100) : null;

    if (els.metricStudents)  els.metricStudents.textContent  = fmtNumber(totalStudents);
    if (els.metricAttempted) els.metricAttempted.textContent = `Attempted: ${fmtNumber(uniqueAttempted)}`;

    if (els.metricAvgPercent) els.metricAvgPercent.textContent = fmtPercent(avgPercent);
    if (els.metricAvgMarks) {
      els.metricAvgMarks.textContent =
        avgScore !== null && avgScore !== undefined ? `Score: ${avgScore.toFixed(2)}` : 'Score: —';
    }

    if (els.metricTopPercent) els.metricTopPercent.textContent = fmtPercent(bestPercent);
    if (els.metricTopName)    els.metricTopName.textContent    = bestName;

    if (els.metricPassRate) els.metricPassRate.textContent = fmtPercent(winRate);
    if (els.metricPassNumbers) els.metricPassNumbers.textContent = `Win: ${fmtNumber(winCount)} • Fail: ${fmtNumber(failCount)}`;
  }

  function renderDistributionAndSnapshot(){
    if (!state.raw) return;
    const s = state.raw.statsNorm || {};
    const attempts = state.raw.attempts || [];

    const uniqueAttempted = s.uniqueAttempted ?? s.uniqueStudents ?? 0;
    const totalStudents   = s.assignedStudents ?? uniqueAttempted;
    const winCount        = s.passCount ?? 0;
    const failCount       = s.failCount ?? 0;
    const totalAttempts   = s.totalAttempts ?? attempts.length;

    const notAttempted = Math.max(0, totalStudents - (winCount + failCount));
    const base = totalStudents || (winCount + failCount + notAttempted) || 1;

    const pctWin     = winCount / base * 100;
    const pctFail    = failCount / base * 100;
    const pctPending = notAttempted / base * 100;

    if (els.segPass)    els.segPass.style.width    = pctWin.toFixed(1) + '%';
    if (els.segFail)    els.segFail.style.width    = pctFail.toFixed(1) + '%';
    if (els.segPending) els.segPending.style.width = pctPending.toFixed(1) + '%';

    if (els.legPass)    els.legPass.textContent    = `Win: ${fmtNumber(winCount)}`;
    if (els.legFail)    els.legFail.textContent    = `Fail: ${fmtNumber(failCount)}`;
    if (els.legPending) els.legPending.textContent = `Not attempted: ${fmtNumber(notAttempted)}`;

    let totalTimeMs = 0;
    let countTime = 0;
    let sumMoves = 0;
    let sumTimeout = 0;

    attempts.forEach(a => {
      if (a.time_taken_ms != null){
        totalTimeMs += Number(a.time_taken_ms);
        countTime++;
      }
      const snap = safeParseJson(a.user_answer_json);
      if (snap && Array.isArray(snap.moves)) sumMoves += snap.moves.length;
      if (String(a.status || '').toLowerCase() === 'timeout') sumTimeout++;
    });

    const avgTimeMs = countTime ? (totalTimeMs / countTime) : null;
    const avgMoves  = attempts.length ? (sumMoves / attempts.length) : null;

    if (els.snapAttempts)   els.snapAttempts.textContent   = fmtNumber(totalAttempts);
    if (els.snapAvgTime)    els.snapAvgTime.textContent    = avgTimeMs !== null ? msToPretty(avgTimeMs) : '—';
    if (els.snapAvgCorrect) els.snapAvgCorrect.textContent = avgMoves !== null ? avgMoves.toFixed(1) : '—';
    if (els.snapAvgWrong)   els.snapAvgWrong.textContent   = attempts.length ? (sumTimeout / attempts.length).toFixed(2) : '—';

    if (els.sideSmallMeta) els.sideSmallMeta.textContent = `${fmtNumber(totalStudents)} students • ${fmtNumber(totalAttempts)} attempts`;
  }

  function renderTable(){
    const tbody = els.tableBody;
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!state.raw){
      tbody.innerHTML = `<tr><td colspan="7">
        <div class="asr-empty"><div class="asr-loading"><span class="asr-spinner"></span><span>Loading assigned student results…</span></div></div>
      </td></tr>`;
      if (els.tableInfo) els.tableInfo.textContent = 'Loading…';
      return;
    }

    if (!state.filtered.length){
      tbody.innerHTML = `<tr><td colspan="7"><div class="asr-empty">No students match the current filters.</div></td></tr>`;
      const total = (state.raw.students || []).length;
      if (els.tableInfo) els.tableInfo.textContent = `${total} students • 0 visible`;
      return;
    }

    const total = (state.raw.students || []).length;
    if (els.tableInfo) els.tableInfo.textContent = `${total} students • ${state.filtered.length} visible`;

    state.filtered.forEach((st, idx) => {
      const best = st.best_result || {};
      const pct  = best.percentage ?? null;

      const scoreStr = (best.score !== undefined && best.score !== null) ? String(best.score) : '—';
      const pctStr   = fmtPercent(pct);

      let statusLabel = 'Not attempted';
      let statusClass = 'asr-status-na';
      if (pct !== null && pct !== undefined){
        const isWin = !!best.is_pass;
        statusLabel = isWin ? 'Win' : (String(best.status||'').toLowerCase()==='timeout' ? 'Timeout' : 'Fail');
        statusClass = isWin ? 'asr-status-pass' : 'asr-status-fail';
      }

      const attemptsArr = st.attempts || [];
      let attemptOptionsHtml = '<option value="">Select attempt…</option>';
      let defaultResultId = '';
      if (attemptsArr.length){
        attemptsArr.forEach(att => {
          const apct = att.percentage;
          const pctLabel = (apct !== null && apct !== undefined && !isNaN(apct)) ? apct.toFixed(1) + '%' : '—';
          const stLabel = att.status ? String(att.status).toUpperCase() : '—';
          const label = `Attempt ${att.attempt_number || ''} • ${pctLabel} • ${stLabel}`;
          const isDefault = !defaultResultId && att.result_id;
          if (isDefault) defaultResultId = att.result_id;

          attemptOptionsHtml += `<option value="${att.result_id || ''}"${isDefault ? ' selected' : ''}>${label}</option>`;
        });
      }
      const hasAttempts = !!defaultResultId;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${idx + 1}</td>
        <td>
          <div class="asr-student-cell">
            <div class="asr-student-name">${st.name || 'Unnamed'}</div>
            <div class="asr-student-meta">
              ${(st.email || '')} ${st.roll_no ? ' • ' + st.roll_no : ''}
            </div>
          </div>
        </td>
        <td>
          <div style="display:flex;flex-direction:column;gap:2px;">
            <span>${scoreStr}</span>
            <span class="text-muted" style="font-size:var(--fs-12);">${pctStr}</span>
          </div>
        </td>
        <td>${fmtNumber(st.attempts_count || 0)}</td>
        <td style="font-size:var(--fs-12);color:var(--muted-color);">
          ${fmtDate(st.last_attempt_at)}
        </td>
        <td>
          <span class="asr-status-badge ${statusClass}">
            <span></span>${statusLabel}
          </span>
        </td>
        <td>
          <div class="asr-actions">
            <select class="form-select form-select-sm js-asr-attempt-select">
              ${attemptOptionsHtml}
            </select>
            <button type="button"
                    class="btn btn-light btn-sm js-asr-view"
                    data-result-id="${defaultResultId || ''}"
                    ${hasAttempts ? '' : 'disabled'}>
              <i class="fa-regular fa-eye"></i>
              View
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.js-asr-attempt-select').forEach(sel => {
      sel.addEventListener('change', () => {
        const tr = sel.closest('tr');
        if (!tr) return;
        const btn = tr.querySelector('.js-asr-view');
        if (!btn) return;
        const rid = sel.value || '';
        btn.dataset.resultId = rid;
        btn.disabled = !rid;
      });
    });

    tbody.querySelectorAll('.js-asr-view').forEach(btn => {
      btn.addEventListener('click', () => {
        const rid = btn.dataset.resultId;
        const nameEl = btn.closest('tr')?.querySelector('.asr-student-name');
        const name = (nameEl && nameEl.textContent) ? nameEl.textContent.trim() : 'Student';
        if (!rid){
          renderDetailEmpty('No attempt selected for this student.');
          return;
        }
        loadResultDetail(rid, name);
      });
    });
  }

  function renderDetailEmpty(msg){
    if (!els.detailContainer) return;
    els.detailContainer.innerHTML = `<div class="asr-empty" style="padding:12px;">${msg || 'Select a student row to see move-wise breakdown.'}</div>`;
  }

  function renderDetailLoading(name){
    if (!els.detailContainer) return;
    els.detailContainer.innerHTML = `
      <div class="asr-detail-card">
        <div class="asr-detail-head">
          <div>
            <div class="asr-detail-title">Move-wise breakdown</div>
            <div class="asr-detail-sub">${name}</div>
          </div>
          <div class="asr-loading">
            <span class="asr-spinner"></span>
            <span>Loading…</span>
          </div>
        </div>
      </div>
    `;
  }

  function renderDetail(data, studentName){
    if (!els.detailContainer) return;

    const payload = (data && data.data) ? data.data : (data || {});
    if (!payload) { renderDetailEmpty('No data available.'); return; }

    const game    = payload.game || payload.door_game || {};
    const result  = payload.result || payload || {};

const snap = safeParseJson(result.user_answer_json || result.user_answer || result.snapshot || null) || {};
    const timing = snap.timing || {};
    const moves = Array.isArray(snap.moves) ? snap.moves : [];
    const path  = Array.isArray(snap.path)  ? snap.path  : [];
    const events = snap.events || {};
    const status = String(result.status || '').toLowerCase();
    const score = (result.score != null) ? result.score : '—';
    const perc = (result.percentage != null) ? result.percentage : ((result.score != null) ? (Number(result.score)*100) : null);

    const timeMs = result.time_taken_ms ?? timing.time_taken_ms ?? null;

    const container = document.createElement('div');
    container.className = 'asr-detail-card';

    const isWin = status === 'win' || Number(result.score) === 1;
    const chipClass = isWin ? 'asr-chip-correct' : 'asr-chip-wrong';
    const chipLabel = isWin ? 'WIN' : (status ? status.toUpperCase() : 'ATTEMPT');

    container.innerHTML = `
      <div class="asr-detail-head">
        <div>
          <div class="asr-detail-title">
            ${studentName || 'Student'} — ${fmtPercent(perc)}
          </div>
          <div class="asr-detail-sub">
            ${(game.title || game.game_title || game.name || 'Door Game')}
            • Score: ${score}
            • Time: ${timeMs != null ? msToPretty(timeMs) : '—'}
            • Moves: ${moves.length}
          </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
          <div class="${chipClass}">${chipLabel}</div>
          <button type="button" class="btn btn-light btn-sm" id="asrClearDetail">
            <i class="fa-regular fa-circle-xmark"></i>
          </button>
        </div>
      </div>
      <div class="asr-detail-body" id="asrDetailBodyInner"></div>
    `;

    els.detailContainer.innerHTML = '';
    els.detailContainer.appendChild(container);

    const inner = container.querySelector('#asrDetailBodyInner');

    const snapCard = document.createElement('div');
    snapCard.className = 'asr-q-item';
    snapCard.innerHTML = `
      <div class="asr-q-head">
        <div class="asr-q-title">Attempt Snapshot</div>
        <div class="asr-q-meta" style="margin:0;">
          Grid: ${snap.grid_dim ?? '—'} • Start: ${snap.start_index ?? '—'}
        </div>
      </div>
      <div class="asr-q-body">
        <div><strong>Key:</strong> ${events.key ? ('Picked at ' + (events.key.picked_at_index ?? '—')) : 'Not picked'}</div>
        <div><strong>Door:</strong> ${events.door ? ('Opened at ' + (events.door.opened_at_index ?? '—')) : 'Not opened'}</div>
        <div><strong>Path:</strong> ${path.length ? path.join(' → ') : '—'}</div>
      </div>
    `;
    inner.appendChild(snapCard);

    if (!moves.length){
      const div = document.createElement('div');
      div.className = 'asr-q-item';
      div.innerHTML = `<div class="asr-q-title">Moves</div><div class="asr-q-body">No moves recorded.</div>`;
      inner.appendChild(div);
    } else {
      moves.forEach((m, idx) => {
        const from = (m && m.from != null) ? m.from : '—';
        const to   = (m && m.to != null) ? m.to : '—';
        const tms  = (m && m.t_ms != null) ? m.t_ms : null;

        const div = document.createElement('div');
        div.className = 'asr-q-item';
        div.innerHTML = `
          <div class="asr-q-head">
            <div class="asr-q-title">Move #${idx+1}: ${from} → ${to}</div>
            <div class="asr-q-meta" style="margin:0;">t_ms: ${tms != null ? tms : '—'}</div>
          </div>
          <div class="asr-q-body">
            <div><strong>From:</strong> ${from}</div>
            <div><strong>To:</strong> ${to}</div>
          </div>
        `;
        inner.appendChild(div);
      });
    }

    const clearBtn = container.querySelector('#asrClearDetail');
    if (clearBtn){
      clearBtn.addEventListener('click', () => {
        renderDetailEmpty('Select a student & attempt to see move-wise breakdown.');
      });
    }
  }

  function loadResultDetail(resultId, studentName){
    if (els.sideError){
      els.sideError.style.display = 'none';
      els.sideError.textContent = '';
    }
    renderDetailLoading(studentName);

    const token = getToken();
    if (!token){
      renderDetailEmpty('No auth token found. Please log in again.');
      return;
    }

    const url = `/api/door-game-results/detail/${encodeURIComponent(resultId)}`;

    fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token
      }
    })
      .then(res => res.json().catch(() => ({})))
      .then(json => {
        if (!json || json.success === false){
          const msg = (json && (json.message || json.error)) || 'Failed to load result detail.';
          if (els.sideError){
            els.sideError.style.display = 'block';
            els.sideError.textContent = msg;
          }
          renderDetailEmpty(msg);
          return;
        }
        renderDetail(json, studentName);
      })
      .catch(err => {
        console.error(err);
        if (els.sideError){
          els.sideError.style.display = 'block';
          els.sideError.textContent = 'Unexpected error while loading result detail.';
        }
        renderDetailEmpty('Unexpected error while loading result detail.');
      });
  }

  function loadOverview(){
    if (els.sideError){
      els.sideError.style.display = 'none';
      els.sideError.textContent = '';
    }
    renderDetailEmpty('Select a student & attempt to see move-wise breakdown.');

    const token = getToken();
    if (!token){
      if (els.tableBody){
        els.tableBody.innerHTML = `
          <tr><td colspan="7">
            <div class="asr-empty">Missing auth token. Please log in again.</div>
          </td></tr>`;
      }
      if (els.tableInfo) els.tableInfo.textContent = 'Auth error';
      return;
    }

    if (!gameKey){
      if (els.tableBody){
        els.tableBody.innerHTML = `
          <tr><td colspan="7">
            <div class="asr-empty">Select a game from the dropdown to view assigned student results.</div>
          </td></tr>`;
      }
      if (els.tableInfo) els.tableInfo.textContent = 'No game selected';
      if (els.gameMeta){
        els.gameMeta.innerHTML = `<i class="fa-regular fa-circle-question"></i><span>Select a game to view analytics</span>`;
      }
      return;
    }

    if (els.tableBody){
      els.tableBody.innerHTML = `
        <tr><td colspan="7">
          <div class="asr-empty">
            <div class="asr-loading"><span class="asr-spinner"></span><span>Loading assigned student results…</span></div>
          </div>
        </td></tr>`;
    }
    if (els.tableInfo) els.tableInfo.textContent = 'Loading…';
    if (els.gameMeta){
      els.gameMeta.innerHTML = `<i class="fa-regular fa-circle-question"></i><span>Loading game info…</span>`;
    }

    const url = `/api/door-game-results/assigned/${encodeURIComponent(gameKey)}`;

    fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token
      }
    })
      .then(res => res.json().catch(() => ({})))
      .then(json => {
        if (!json || json.success === false){
          const msg = (json && (json.message || json.error)) || 'Failed to load results.';
          if (els.tableBody){
            els.tableBody.innerHTML = `<tr><td colspan="7"><div class="asr-empty">${msg}</div></td></tr>`;
          }
          if (els.tableInfo) els.tableInfo.textContent = 'Error';
          return;
        }

        const payload = json.data || json;
        state.raw = payload;
        normalizeFromAttempts();
        applyFilters();
        renderHeader();
        renderMetrics();
        renderDistributionAndSnapshot();
        renderTable();
      })
      .catch(err => {
        console.error(err);
        if (els.tableBody){
          els.tableBody.innerHTML = `<tr><td colspan="7"><div class="asr-empty">Unexpected error while loading results.</div></td></tr>`;
        }
        if (els.tableInfo) els.tableInfo.textContent = 'Error';
      });
  }

  function loadGameOptions(){
    const sel = els.gameSelect;
    if (!sel){
      if (gameKey) loadOverview();
      return;
    }

    const token = getToken();
    if (!token){
      sel.innerHTML = '<option value="">Missing auth token</option>';
      sel.disabled = true;
      if (gameKey) loadOverview();
      return;
    }

    sel.innerHTML = '<option value="">Loading games…</option>';
    sel.disabled  = true;

    const url = '/api/door-games?per_page=100&status=active';

    fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token
      }
    })
      .then(res => res.json().catch(() => ({})))
      .then(json => {
        console.log('Examiner door games list response:', json);

        const rows = extractRowsFromGameResponse(json);
        gameRowsCache = rows || [];

        sel.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select game…';
        sel.appendChild(placeholder);

        if (!rows || !rows.length){
          placeholder.textContent = 'No assigned games found';
          sel.disabled = true;

          if (els.tableBody){
            els.tableBody.innerHTML = `
              <tr><td colspan="7"><div class="asr-empty">No assigned door games found for your examiner account.</div></td></tr>`;
          }
          if (els.tableInfo) els.tableInfo.textContent = 'No data';
          return;
        }

        rows.forEach(row => {
          const key = extractGameKey(row);
          if (!key) return;

          const opt = document.createElement('option');
          opt.value = key;
          opt.textContent =
            row.title ||
            row.game_title ||
            row.name ||
            ('Game #' + (row.door_game_id || row.game_id || row.id || ''));
          sel.appendChild(opt);
        });

        const decidedKey = decideInitialGameKey(rows);
        gameKey = decidedKey || '';

        sel.value = gameKey || '';
        updateUrlGame(gameKey);
        sel.disabled = false;

        if (gameKey) loadOverview();
        else {
          if (els.tableBody){
            els.tableBody.innerHTML = `<tr><td colspan="7"><div class="asr-empty">Select a game to view assigned student results.</div></td></tr>`;
          }
          if (els.tableInfo) els.tableInfo.textContent = 'No game selected';
        }
      })
      .catch(err => {
        console.error(err);
        sel.innerHTML = '<option value="">Failed to load games</option>';
        sel.disabled = true;
        if (gameKey) loadOverview();
      });
  }

  /* ---------------- Events ---------------- */
  if (els.search){
    els.search.addEventListener('input', e => {
      state.filters.search = e.target.value || '';
      applyFilters();
      renderTable();
    });
  }
  if (els.statusFilter){
    els.statusFilter.addEventListener('change', e => {
      state.filters.status = e.target.value || 'all';
      applyFilters();
      renderTable();
    });
  }
  if (els.sort){
    els.sort.addEventListener('change', e => {
      state.filters.sort = e.target.value || 'last_attempt';
      applyFilters();
      renderTable();
    });
  }
  if (els.refresh){
    els.refresh.addEventListener('click', () => loadOverview());
  }
  if (els.gameSelect){
    els.gameSelect.addEventListener('change', e => {
      gameKey = e.target.value || '';
      updateUrlGame(gameKey);
      loadOverview();
    });
  }

  loadGameOptions();
})();
</script>
@endsection
