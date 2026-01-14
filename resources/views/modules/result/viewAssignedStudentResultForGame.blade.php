{{-- resources/views/examiner/bubble_game/viewAssignedStudentResult.blade.php --}}
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

  .asr-header-main{
    display:flex;justify-content:space-between;align-items:flex-start;
    gap:10px;
  }
  .asr-head-left{
    display:flex;align-items:flex-start;gap:10px;
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
  }

  .asr-filters{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:16px;
  }
  .asr-filters .form-control,
  .asr-filters .form-select{
    border-radius:999px;
    padding-left:14px;
    padding-right:14px;
  }
  .asr-filters .btn{
    border-radius:999px;
  }
  .asr-filters-quiz{
    min-width:220px;
  }

  .asr-metrics-row{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin:18px 0 14px;
  }
  @media (max-width: 992px){
    .asr-metrics-row{ grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
  .asr-metric{
    border-radius:14px;
    border:1px solid var(--line-soft);
    background:var(--surface-2);
    padding:10px 12px;
    display:flex;
    flex-direction:column;
    gap:4px;
  }
  .asr-metric-label{
    font-size:var(--fs-12);
    color:var(--muted-color);
    display:flex;align-items:center;gap:6px;
  }
  .asr-metric-value{
    font-size:1.1rem;
    font-weight:600;
    color:var(--ink);
    line-height:1.2;
  }
  .asr-metric-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  .asr-table-card{
    border-radius:16px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    box-shadow:var(--shadow-2);
    overflow:hidden;
  }
  .asr-table-head{
    display:flex;align-items:center;justify-content:space-between;
    padding:10px 14px;
    border-bottom:1px solid var(--line-strong);
    background:var(--surface-2);
  }
  .asr-table-title{
    font-family:var(--font-head);
    font-weight:600;
    color:var(--ink);
  }
  .asr-table-body-wrap{
    max-height:420px;
    overflow:auto;
  }
  .asr-table-body-wrap table{
    margin-bottom:0;
  }
  .asr-table-body-wrap thead th{
    position:sticky;
    top:0;
    background:var(--surface-2);
    z-index:2;
  }
  .asr-table-body-wrap tbody tr:hover{
    background:var(--surface-3);
  }

  .asr-student-cell{
    display:flex;flex-direction:column;gap:2px;
  }
  .asr-student-name{
    font-weight:600;
    color:var(--ink);
  }
  .asr-student-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  .asr-status-badge{
    display:inline-flex;align-items:center;gap:5px;
    padding:3px 8px;
    border-radius:999px;
    font-size:var(--fs-12);
    font-weight:600;
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

  .asr-actions{
    display:flex;gap:6px;justify-content:flex-end;align-items:center;
  }
  .asr-actions select{
    min-width:180px;
  }

  .asr-empty{
    border-radius:10px;
    border:1px dashed var(--line-strong);
    padding:18px 14px;
    text-align:center;
    color:var(--muted-color);
    background:var(--surface-2);
    font-size:var(--fs-13);
  }

  .asr-loading{
    display:inline-flex;align-items:center;gap:8px;
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .asr-spinner{
    width:16px;height:16px;
    border-radius:50%;
    border:2px solid #0001;
    border-top-color:var(--accent-color);
    animation:asr-spin 1s linear infinite;
  }
  @keyframes asr-spin{to{transform:rotate(360deg)}}

  .asr-side-card{
    border-radius:16px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    box-shadow:var(--shadow-2);
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
  .asr-side-small{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }

  .asr-dist-label{
    font-weight:600;
    font-size:var(--fs-13);
    margin-top:4px;
  }
  .asr-dist-bar{
    margin-top:6px;
    border-radius:999px;
    background:var(--surface-2);
    border:1px solid var(--line-soft);
    height:12px;
    overflow:hidden;
    display:flex;
  }
  .asr-dist-seg{
    height:100%;
  }
  .asr-dist-pass{ background:var(--t-success); }
  .asr-dist-fail{ background:var(--t-danger); }
  .asr-dist-pending{ background:var(--t-info); }

  .asr-dist-legend{
    display:flex;flex-wrap:wrap;gap:8px;
    margin-top:8px;
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .asr-dist-legend span{
    display:inline-flex;align-items:center;gap:5px;
  }
  .asr-dot{
    width:8px;height:8px;border-radius:50%;
    background:currentColor;
  }

  .asr-snap{
    margin-top:14px;
    font-size:var(--fs-13);
  }
  .asr-snap-row{
    display:flex;justify-content:space-between;
    padding:4px 0;
    border-bottom:1px dashed var(--line-soft);
  }
  .asr-snap-row:last-child{ border-bottom:none; }

  .asr-side-error{
    margin-top:10px;
    font-size:var(--fs-12);
    display:none;
  }

  .asr-detail-card{
    border-radius:14px;
    border:1px solid var(--line-soft);
    background:var(--surface-2);
    padding:10px 12px;
    margin-top:12px;
    max-height:330px;
    overflow:auto;
  }
  .asr-detail-head{
    display:flex;align-items:center;justify-content:space-between;
    gap:10px;margin-bottom:8px;
  }
  .asr-detail-title{
    font-weight:600;
    color:var(--ink);
  }
  .asr-detail-sub{
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .asr-detail-body{
    display:flex;flex-direction:column;gap:10px;
  }

  .asr-q-item{
    border-radius:10px;
    border:1px solid var(--line-soft);
    background:var(--surface);
    padding:8px 9px;
  }
  .asr-q-head{
    display:flex;justify-content:space-between;align-items:center;
    gap:8px;margin-bottom:4px;
  }
  .asr-q-title{
    font-weight:600;
    font-size:var(--fs-13);
    color:var(--ink);
  }
  .asr-chip-correct,
  .asr-chip-wrong{
    padding:3px 8px;
    border-radius:999px;
    font-size:var(--fs-12);
    font-weight:600;
  }
  .asr-chip-correct{
    background:var(--t-success);
    color:#15803d;
    border:1px solid rgba(22,163,74,.2);
  }
  .asr-chip-wrong{
    background:var(--t-danger);
    color:#b91c1c;
    border:1px solid rgba(220,38,38,.2);
  }
  .asr-q-meta{
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-bottom:4px;
  }
  .asr-q-body{
    font-size:var(--fs-13);
  }

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
        <div class="flex-grow-1">
          <input id="asrSearch" type="text" class="form-control"
                 placeholder="Search by student name, email, roll…">
        </div>
        <div>
          <select id="asrStatusFilter" class="form-select">
            <option value="all">All statuses</option>
            <option value="pass">Passed</option>
            <option value="fail">Failed</option>
          </select>
        </div>
        <div>
          <select id="asrSort" class="form-select">
            <option value="last_attempt">Last attempt (newest → oldest)</option>
            <option value="name">Name (A → Z)</option>
            <option value="best_desc">Best score (high → low)</option>
            <option value="best_asc">Best score (low → high)</option>
            <option value="attempts">Most attempts</option>
          </select>
        </div>
        <div>
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
            <i class="fa-solid fa-flag-checkered"></i> Pass Rate
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
                    <th style="width:220px;">Actions</th>
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
                  <span id="asrLegPass">Pass: —</span></span>
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
                <span>Avg. correct Qs</span>
                <span id="asrSnapAvgCorrect">—</span>
              </div>
              <div class="asr-snap-row">
                <span>Avg. incorrect Qs</span>
                <span id="asrSnapAvgWrong">—</span>
              </div>
            </div>

            <div id="asrSideError" class="asr-side-error alert alert-danger"></div>

            <div id="asrDetailContainer" class="mt-2">
              <div class="asr-empty" style="margin-top:10px;">
                Select a student &amp; attempt to see question-wise breakdown.
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

  // Hints from controller / URL
  const datasetGameKey    = (rootEl.dataset.gameKey || '').trim(); // ideally game UUID
  const urlGameUuidParam  = (urlParams.get('game_uuid') || '').trim();
  const urlGameParam      = (urlParams.get('game') || '').trim();  // may be assignment id

  // Final key used for /api/exam/bubble-games/{gameKey}/assigned-results
  let gameKey = datasetGameKey || urlGameUuidParam || '';

  // cache (currently unused, but kept for future)
  let gameRowsCache = [];

  const state = {
    raw: null,
    filtered: [],
    filters: {
      search: '',
      status: 'all',
      sort: 'last_attempt',
    }
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
    }catch(e){
      return val;
    }
  }
  function secondsToPretty(sec){
    sec = Number(sec || 0);
    if (sec <= 0) return '—';
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    if (m === 0) return s + 's';
    if (m < 60) return m + 'm ' + (s>0 ? s+'s' : '');
    const h = Math.floor(m/60);
    const mm = m%60;
    return h + 'h ' + (mm>0 ? mm+'m' : '');
  }

  /* ---------------- Core normalisation helpers ---------------- */

  // Prefer game uuid/id over assignment id for key
  function extractGameKey(row){
    const game = (row && (row.game || row.bubble_game)) ? (row.game || row.bubble_game) : null;

    const key =
      row.game_uuid ||
      row.bubble_game_uuid ||
      row.game_key ||
      row.bubble_game_key ||
      (game && (game.uuid || game.key)) ||
      row.bubble_game_id ||
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
        const gid = (r.bubble_game_id != null) ? String(r.bubble_game_id) : ((r.game_id != null) ? String(r.game_id) : null);
        return (k && String(k) === keyStr) || (gid && gid === keyStr);
      });
      if (byKey){
        return extractGameKey(byKey);
      }
    }

    if (urlGameParam){
      const idStr = String(urlGameParam);
      const byAssign = rows.find(r => {
        const rId  = (r.id != null) ? String(r.id) : null;
        const gid  = (r.bubble_game_id != null) ? String(r.bubble_game_id) : ((r.game_id != null) ? String(r.game_id) : null);
        const gu   = (r.bubble_game_uuid != null) ? String(r.bubble_game_uuid) : ((r.game_uuid != null) ? String(r.game_uuid) : null);
        return (rId && rId === idStr) || (gid && gid === idStr) || (gu && gu === idStr);
      });
      if (byAssign){
        return extractGameKey(byAssign);
      }
    }

    return extractGameKey(rows[0]);
  }

  function extractRowsFromGameResponse(json){
    if (!json || typeof json !== 'object') return [];

    let rows = [];

    if (Array.isArray(json.data)) {
      rows = json.data;
    } else if (json.data && Array.isArray(json.data.data)) {
      rows = json.data.data;
    } else if (json.data && Array.isArray(json.data.items)) {
      rows = json.data.items;
    } else if (Array.isArray(json.games)) {
      rows = json.games;
    } else if (Array.isArray(json.bubble_games)) {
      rows = json.bubble_games;
    } else if (Array.isArray(json.rows)) {
      rows = json.rows;
    } else if (Array.isArray(json)) {
      rows = json;
    }

    if (!rows.length){
      const candidates = [json.data, json];
      for (const lvl of candidates){
        if (!lvl || typeof lvl !== 'object') continue;
        for (const k in lvl){
          if (Array.isArray(lvl[k])){
            rows = lvl[k];
            break;
          }
        }
        if (rows.length) break;
      }
    }

    return rows;
  }

  function normalizeFromAttempts(){
    if (!state.raw) return;

    const attempts = state.raw.attempts || [];
    const stats   = state.raw.stats || {};
    const game    = state.raw.game || state.raw.bubble_game || {};

    const studentMap = new Map();

    // If you have a pass_threshold / pass_percentage for bubble game, it will use it; else defaults 40
    const PASS_THRESHOLD =
      typeof game.pass_percentage === 'number' ? game.pass_percentage :
      typeof game.pass_percent === 'number'    ? game.pass_percent :
      typeof stats.pass_percentage === 'number'? stats.pass_percentage :
      40;

    let sumScore = 0;
    let sumPct   = 0;
    let countPct = 0;

    let topStudentName = null;
    let topStudentPercent = null;

    attempts.forEach(a => {
      const id = a.student_id != null ? a.student_id : (a.user_id != null ? a.user_id : null);
      if (id == null) return;

      if (!studentMap.has(id)) {
        studentMap.set(id, {
          id: id,
          name: a.student_name || a.name || 'Student',
          email: a.student_email || a.email || '',
          roll_no: a.student_roll_no || a.roll_no || '',
          attempts_count: 0,
          last_attempt_at: null,
          best_result: null,
          attempts: []
        });
      }
      const st = studentMap.get(id);
      st.attempts_count++;

      const lastAt = a.result_created_at || a.created_at || a.finished_at || a.completed_at || a.submitted_at || a.started_at;
      if (lastAt && (!st.last_attempt_at || new Date(lastAt) > new Date(st.last_attempt_at))) {
        st.last_attempt_at = lastAt;
      }

      const pct = (a.accuracy != null) ? Number(a.accuracy)
                : (a.percentage != null) ? Number(a.percentage)
                : null;

      const score = (a.score != null) ? Number(a.score)
                  : (a.marks_obtained != null) ? Number(a.marks_obtained)
                  : null;

      st.attempts.push({
        result_id: a.result_id || a.uuid || a.id,
        attempt_number: a.attempt_no || a.attempt_number,
        percentage: pct,
        score: score,
        result_created_at: lastAt
      });

      const currentBest = st.best_result;
      if (pct != null) {
        if (!currentBest || pct > (currentBest.percentage ?? 0)) {
          const isPass = (a.is_pass != null)
            ? !!a.is_pass
            : pct >= PASS_THRESHOLD;

          st.best_result = {
            result_id: a.result_id || a.uuid || a.id,
            score: score,
            percentage: pct,
            is_pass: isPass
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
      st.attempts.sort((a, b) => (b.attempt_number || 0) - (a.attempt_number || 0));
    });

    const totalAttempts   = stats.total_attempts ?? attempts.length;
    const uniqueAttempted = stats.unique_attempted ?? stats.unique_students ?? studentMap.size;
    const assignedStudents= stats.total_assigned_students ?? stats.assigned_students ??
                            stats.total_students ?? stats.total_assigned ?? null;

    const avgPct = (stats.avg_percentage != null)
      ? stats.avg_percentage
      : (countPct ? (sumPct / countPct) : null);

    const avgScore = totalAttempts ? (sumScore / totalAttempts) : null;

    let passCount = 0;
    let failCount = 0;
    studentMap.forEach(st => {
      const br = st.best_result;
      if (!br || br.percentage == null) return;
      if (br.is_pass) passCount++;
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
      passCount,
      failCount,
      topStudentName,
      topStudentPercent,
      passThreshold: PASS_THRESHOLD
    };
  }

  function applyFilters(){
    if (!state.raw || !state.raw.students) {
      state.filtered = [];
      return;
    }
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
        if (!best || best.percentage == null){
          return false;
        }
        const pass = !!best.is_pass;
        if (status === 'pass') return pass;
        if (status === 'fail') return !pass;
        return true;
      });
    }

    arr.sort((a,b) => {
      const bestA = a.best_result || {};
      const bestB = b.best_result || {};
      switch(sort){
        case 'name':
          return (a.name || '').localeCompare(b.name || '');
        case 'best_desc':
          return (bestB.percentage || 0) - (bestA.percentage || 0);
        case 'best_asc':
          return (bestA.percentage || 0) - (bestB.percentage || 0);
        case 'attempts':
          return (b.attempts_count || 0) - (a.attempts_count || 0);
        case 'last_attempt':
        default:
          return new Date(b.last_attempt_at || 0) - new Date(a.last_attempt_at || 0);
      }
    });

    state.filtered = arr;
  }

  function renderHeader(){
    if (!state.raw) return;
    const game = state.raw.game || state.raw.bubble_game || {};
    if (els.gameTitle) els.gameTitle.textContent = game.title || game.game_title || game.name || 'Assigned Students Results';
    if (els.gameSub) els.gameSub.textContent = 'View student-wise performance and analytics for this bubble game.';

    const totalTimeMin =
      (typeof game.total_time_minutes === 'number' && game.total_time_minutes > 0) ? game.total_time_minutes :
      (typeof game.total_time === 'number'         && game.total_time > 0)         ? game.total_time :
      (typeof game.total_time_sec === 'number'     && game.total_time_sec > 0)     ? Math.round(game.total_time_sec / 60) :
      null;

    const totalTime = totalTimeMin != null ? totalTimeMin + ' min' : '—';

    if (els.gameMeta) {
      els.gameMeta.innerHTML =
        `<i class="fa-regular fa-circle-question"></i>
         <span>Total time: ${totalTime}</span>`;
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
    const passCount       = s.passCount ?? 0;
    const failCount       = s.failCount ?? 0;
    const passRate        = uniqueAttempted ? (passCount / uniqueAttempted * 100) : null;

    if (els.metricStudents)  els.metricStudents.textContent  = fmtNumber(totalStudents);
    if (els.metricAttempted) els.metricAttempted.textContent = `Attempted: ${fmtNumber(uniqueAttempted)}`;

    if (els.metricAvgPercent) els.metricAvgPercent.textContent = fmtPercent(avgPercent);
    if (els.metricAvgMarks) {
      els.metricAvgMarks.textContent =
        avgScore !== null && avgScore !== undefined ? `Score: ${avgScore.toFixed(1)}` : 'Score: —';
    }

    if (els.metricTopPercent) els.metricTopPercent.textContent = fmtPercent(bestPercent);
    if (els.metricTopName)    els.metricTopName.textContent    = bestName;

    if (els.metricPassRate) els.metricPassRate.textContent = fmtPercent(passRate);
    if (els.metricPassNumbers) els.metricPassNumbers.textContent = `Pass: ${fmtNumber(passCount)} • Fail: ${fmtNumber(failCount)}`;
  }

  function renderDistributionAndSnapshot(){
    if (!state.raw) return;
    const s = state.raw.statsNorm || {};
    const attempts = state.raw.attempts || [];

    const uniqueAttempted = s.uniqueAttempted ?? s.uniqueStudents ?? 0;
    const totalStudents   = s.assignedStudents ?? uniqueAttempted;
    const passCount       = s.passCount ?? 0;
    const failCount       = s.failCount ?? 0;
    const totalAttempts   = s.totalAttempts ?? attempts.length;

    const notAttempted = Math.max(0, totalStudents - (passCount + failCount));
    const base = totalStudents || (passCount + failCount + notAttempted) || 1;

    const pctPass    = passCount / base * 100;
    const pctFail    = failCount / base * 100;
    const pctPending = notAttempted / base * 100;

    if (els.segPass)    els.segPass.style.width    = pctPass.toFixed(1) + '%';
    if (els.segFail)    els.segFail.style.width    = pctFail.toFixed(1) + '%';
    if (els.segPending) els.segPending.style.width = pctPending.toFixed(1) + '%';

    if (els.legPass)    els.legPass.textContent    = `Pass: ${fmtNumber(passCount)}`;
    if (els.legFail)    els.legFail.textContent    = `Fail: ${fmtNumber(failCount)}`;
    if (els.legPending) els.legPending.textContent = `Not attempted: ${fmtNumber(notAttempted)}`;

    let totalTimeUsedSec = 0;
    let countTime = 0;
    let sumCorrect = 0;
    let sumIncorrect = 0;

    attempts.forEach(a => {
      if (a.time_used_sec != null){
        totalTimeUsedSec += Number(a.time_used_sec);
        countTime++;
      }
      if (a.total_correct != null)   sumCorrect += Number(a.total_correct);
      if (a.total_incorrect != null) sumIncorrect += Number(a.total_incorrect);
    });

    const avgTimeSec = countTime ? (totalTimeUsedSec / countTime) : null;
    const avgCorrect = attempts.length ? (sumCorrect / attempts.length) : null;
    const avgWrong   = attempts.length ? (sumIncorrect / attempts.length) : null;

    if (els.snapAttempts)   els.snapAttempts.textContent   = fmtNumber(totalAttempts);
    if (els.snapAvgTime)    els.snapAvgTime.textContent    = avgTimeSec !== null ? secondsToPretty(avgTimeSec) : '—';
    if (els.snapAvgCorrect) els.snapAvgCorrect.textContent = avgCorrect !== null ? avgCorrect.toFixed(1) : '—';
    if (els.snapAvgWrong)   els.snapAvgWrong.textContent   = avgWrong !== null ? avgWrong.toFixed(1) : '—';

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
        const isPass = !!best.is_pass;
        statusLabel = isPass ? 'Passed' : 'Failed';
        statusClass = isPass ? 'asr-status-pass' : 'asr-status-fail';
      }

      const attemptsArr = st.attempts || [];
      let attemptOptionsHtml = '<option value="">Select attempt…</option>';
      let defaultResultId = '';
      if (attemptsArr.length){
        attemptsArr.forEach(att => {
          const apct = att.percentage;
          const pctLabel = (apct !== null && apct !== undefined && !isNaN(apct)) ? apct.toFixed(1) + '%' : '—';
          const label = `Attempt ${att.attempt_number || ''} • ${pctLabel}`;
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
    els.detailContainer.innerHTML = `<div class="asr-empty" style="padding:12px;">${msg || 'Select a student row to see question-wise breakdown.'}</div>`;
  }

  function renderDetailLoading(name){
    if (!els.detailContainer) return;
    els.detailContainer.innerHTML = `
      <div class="asr-detail-card">
        <div class="asr-detail-head">
          <div>
            <div class="asr-detail-title">Question-wise breakdown</div>
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

    const game    = payload.game || payload.bubble_game || {};
    const result  = payload.result || {};
    const questions = payload.questions || payload.items || [];

    if (!questions || !questions.length){
      renderDetailEmpty('No question-level data available for this attempt.');
      return;
    }

    const container = document.createElement('div');
    container.className = 'asr-detail-card';

    const perc = result.accuracy ?? result.percentage ?? null;
    const score = result.score ?? result.marks_obtained ?? null;

    container.innerHTML = `
      <div class="asr-detail-head">
        <div>
          <div class="asr-detail-title">
            ${studentName || 'Student'} — ${fmtPercent(perc)}
          </div>
          <div class="asr-detail-sub">
            ${(game.title || game.game_title || game.name || 'Bubble Game')} • Score: ${score ?? '—'}
          </div>
        </div>
        <button type="button" class="btn btn-light btn-sm" id="asrClearDetail">
          <i class="fa-regular fa-circle-xmark"></i>
        </button>
      </div>
      <div class="asr-detail-body" id="asrDetailBodyInner"></div>
    `;

    els.detailContainer.innerHTML = '';
    els.detailContainer.appendChild(container);

    const inner = container.querySelector('#asrDetailBodyInner');

    questions.forEach(q => {
      const isCorrect = (q.is_correct || 0) === 1;
      const chipClass = isCorrect ? 'asr-chip-correct' : 'asr-chip-wrong';
      const chipLabel = isCorrect ? 'Correct' : 'Incorrect';

      const timePretty = secondsToPretty(q.time_spent_sec || q.time_used_sec || 0);

      const selText = q.selected_text ?? q.user_answer_text ?? q.user_answer ?? '—';
      const corrText = q.correct_text ?? q.correct_answer_text ?? q.correct_answer ?? '—';

      const title = q.title || q.question || q.question_title || '';
      const desc  = q.description || q.question_text || '';

      const div = document.createElement('div');
      div.className = 'asr-q-item';
      div.innerHTML = `
        <div class="asr-q-head">
          <div class="asr-q-title">
            Q${q.order || ''}. <span class="mathjax-q-title">${title}</span>
          </div>
          <div class="${chipClass}">${chipLabel}</div>
        </div>
        <div class="asr-q-meta">
          Marks: ${q.awarded_mark ?? q.awarded_marks ?? '—'}/${q.mark ?? q.marks ?? '—'} • Time: ${timePretty}
        </div>
        <div class="asr-q-body">
          ${desc ? `<div class="mathjax-q-desc">${desc}</div>` : ''}
          <div><strong>Your answer:</strong> <span class="mathjax-q-sel">${selText}</span></div>
          <div><strong>Correct:</strong> <span class="mathjax-q-corr">${corrText}</span></div>
        </div>
      `;
      inner.appendChild(div);
    });

    const clearBtn = container.querySelector('#asrClearDetail');
    if (clearBtn){
      clearBtn.addEventListener('click', () => {
        renderDetailEmpty('Select a student & attempt to see question-wise breakdown.');
      });
    }

    if (window.MathJax && window.MathJax.typesetPromise){
      window.MathJax.typesetPromise([inner]).catch(function(err){
        console.error('MathJax typeset error', err);
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

    // ✅ Bubble game detail API (change only if your route differs)
    const url = `/api/bubble-game/results/${encodeURIComponent(resultId)}/instructor-detail`;

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
    renderDetailEmpty('Select a student & attempt to see question-wise breakdown.');

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

    // ✅ Bubble game assigned-results API (change only if your route differs)
    const url = `/api/bubble-game-results/assigned/${encodeURIComponent(gameKey)}`;

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
            els.tableBody.innerHTML = `
              <tr><td colspan="7"><div class="asr-empty">${msg}</div></td></tr>`;
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
          els.tableBody.innerHTML = `
            <tr><td colspan="7"><div class="asr-empty">Unexpected error while loading results.</div></td></tr>`;
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

    // ✅ Games list API (change only if your route differs)
    const url = '/api/bubble-games?per_page=100&status=active';

    fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token
      }
    })
      .then(res => res.json().catch(() => ({})))
      .then(json => {
        console.log('Examiner bubble games list response:', json);

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
              <tr><td colspan="7"><div class="asr-empty">No assigned bubble games found for your examiner account.</div></td></tr>`;
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
            ('Game #' + (row.bubble_game_id || row.game_id || row.id || ''));
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
            els.tableBody.innerHTML = `
              <tr><td colspan="7"><div class="asr-empty">Select a game to view assigned student results.</div></td></tr>`;
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

  // init
  loadGameOptions();
})();
</script>
@endsection
