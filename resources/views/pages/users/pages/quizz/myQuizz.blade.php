{{-- resources/views/exam/my-quizzes-and-games.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','My Quizzes & Games')

@section('content')
<style>
  .qz-wrap{
    max-width:1100px;
    margin:16px auto 40px;
  }
  .qz-card-shell{
    border-radius:16px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    box-shadow:var(--shadow-2);
    overflow:hidden;
  }
  .qz-head{
    padding:16px 18px;
    border-bottom:1px solid var(--line-strong);
    background:var(--surface);
    display:flex;
    align-items:center;
    gap:12px;
  }
  .qz-head-icon{
    width:34px;height:34px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    display:flex;align-items:center;justify-content:center;
    color:var(--accent-color);
    background:var(--surface-2);
  }
  .qz-head-title{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    margin:0;
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .qz-head-sub{color:var(--muted-color); font-size:var(--fs-13);}

  .qz-count-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:26px;
    height:20px;
    padding:0 8px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    color:var(--muted-color);
    font-size:11px;
  }

  .qz-head-tools{
    margin-left:auto;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .qz-search{min-width:260px;}
  .qz-search .form-control{
    border-radius:999px;
    padding-left:32px;
  }
  .qz-search-icon{
    position:absolute;
    left:10px; top:50%;
    transform:translateY(-50%);
    color:var(--muted-color);
    font-size:13px;
  }

  .qz-body{padding:14px 16px 16px; position:relative;}

  .qz-loader-wrap{
    position:absolute;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    background:rgba(0,0,0,.03);
    z-index:2;
  }
  .qz-loader-wrap.show{display:flex;}
  .qz-loader{
    width:20px;height:20px;
    border:3px solid #0001;
    border-top-color:var(--accent-color);
    border-radius:50%;
    animation:rot 1s linear infinite;
  }
  @keyframes rot{to{transform:rotate(360deg)}}

  .qz-error{
    font-size:12px;
    color:var(--danger-color);
    margin-top:6px;
    display:none;
  }
  .qz-error.show{display:block;}

  .qz-empty{
    border:1px dashed var(--line-strong);
    border-radius:12px;
    padding:22px 16px;
    text-align:center;
    color:var(--muted-color);
    background:var(--surface-2);
    font-size:var(--fs-13);
  }

  /* ======= Table ======= */
  .qz-table-wrap{
    border:1px solid var(--line-strong);
    border-radius:14px;
    overflow:auto;
    background:var(--surface);
  }
  .qz-table{width:100%; margin:0;}
  .qz-table thead th{
    position:sticky;
    top:0;
    z-index:1;
    background:var(--surface);
    border-bottom:1px solid var(--line-strong);
    font-size:var(--fs-12);
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted-color);
    padding:10px 12px;
    white-space:nowrap;
  }
  .qz-table tbody td{
    border-color:var(--line-soft);
    padding:10px 12px;
    vertical-align:top;
    font-size:var(--fs-13);
    color:var(--ink);
  }

  .qz-title-cell{
    display:flex;
    gap:10px;
    align-items:flex-start;
    min-width:260px;
  }
  .qz-avatar{
    width:36px;height:36px;
    border-radius:14px;
    background:var(--surface-2);
    border:1px solid var(--line-strong);
    display:flex;align-items:center;justify-content:center;
    color:var(--accent-color);
    flex-shrink:0;
  }
  .qz-title-text{min-width:0; flex:1;}
  .qz-title{
    font-family:var(--font-head);
    font-weight:700;
    font-size:.98rem;
    color:var(--ink);
    margin:0;
    line-height:1.2;
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:6px;
  }
  .qz-submeta{
    margin-top:4px;
    font-size:11px;
    color:var(--muted-color);
  }

  .qz-chip{
    font-size:11px;
    padding:2px 7px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    color:var(--muted-color);
    display:inline-flex;
    align-items:center;
    gap:4px;
    white-space:nowrap;
  }
  .qz-chip i{font-size:10px;}
  .qz-chip-primary{
    background:var(--t-primary);
    border-color:rgba(20,184,166,.3);
    color:#0f766e;
  }
  .qz-chip-success{
    background:var(--t-success);
    border-color:rgba(22,163,74,.25);
    color:#15803d;
  }
  .qz-chip-warn{
    background:var(--t-warn);
    border-color:rgba(245,158,11,.25);
    color:#92400e;
  }

  .qz-status-stack{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
  }

  .qz-num{
    font-variant-numeric:tabular-nums;
    white-space:nowrap;
    color:var(--ink);
  }

  .qz-actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
    white-space:nowrap;
  }
  .qz-actions .btn{
    border-radius:999px;
    padding-inline:12px;
  }

  .qz-inst-btn{
    border-radius:999px;
    padding:5px 10px;
    border:1px dashed var(--line-strong);
    background:var(--surface-2);
    color:var(--muted-color);
    transition:transform .14s ease, border-color .14s ease, background .14s ease;
  }
  .qz-inst-btn:hover{
    transform:translateY(-1px);
    border-color:var(--accent-color);
    background:var(--surface);
    color:var(--ink);
  }
  .qz-inst-btn:disabled{
    opacity:.55;
    cursor:not-allowed;
    transform:none;
  }

  .qz-pagination{
    margin-top:14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .qz-pagination .btn{
    border-radius:999px;
    padding-inline:12px;
  }

  @media (max-width: 576px){
    .qz-head{
      flex-direction:column;
      align-items:flex-start;
    }
    .qz-head-tools{
      margin-left:0;
      width:100%;
      justify-content:space-between;
    }
    .qz-search{min-width:0; flex:1;}
  }

  /* =========================
   * Instructions Modal
   * ========================= */
  body.modal-open{overflow:hidden;}

  .qz-ins-modal{
    position:fixed;
    inset:0;
    z-index:1050;
    display:none;
    align-items:center;
    justify-content:center;
  }
  .qz-ins-modal.show{display:flex;}
  .qz-ins-backdrop{
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.45);
  }
  .qz-ins-dialog{
    position:relative;
    width:100%;
    max-width:860px;
    background:var(--surface);
    border-radius:16px;
    border:1px solid var(--line-strong);
    box-shadow:var(--shadow-3);
    padding:16px 18px 14px;
    max-height:calc(100vh - 80px);
    display:flex;
    flex-direction:column;
    overflow:hidden;
  }
  .qz-ins-head{
    display:flex;
    align-items:flex-start;
    gap:10px;
    margin-bottom:8px;
  }
  .qz-ins-eyebrow{
    font-size:var(--fs-11);
    text-transform:uppercase;
    letter-spacing:.08em;
    color:var(--muted-color);
    font-weight:600;
    margin-bottom:2px;
  }
  .qz-ins-title{
    font-family:var(--font-head);
    font-size:1.02rem;
    font-weight:700;
    color:var(--ink);
    margin:0;
  }
  .qz-ins-meta{
    margin-top:3px;
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .qz-ins-meta span+span::before{
    content:"•";
    margin:0 6px;
    opacity:.6;
  }
  .qz-ins-close{
    margin-left:auto;
    border:none;
    background:transparent;
    width:30px;height:30px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--muted-color);
    transition:background .15s ease, color .15s ease;
  }
  .qz-ins-close:hover{
    background:var(--surface-2);
    color:var(--ink);
  }
  .qz-ins-body{
    overflow:auto;
    padding-top:6px;
    padding-bottom:8px;
  }
  .qz-ins-box{
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    border-radius:14px;
    padding:12px 12px;
    font-size:var(--fs-13);
    color:var(--ink);
    line-height:1.45;
    white-space:normal;
  }
  .qz-ins-foot{
    display:flex;
    justify-content:flex-end;
    padding-top:6px;
    border-top:1px solid var(--line-strong);
  }
  .qz-ins-foot .btn{
    border-radius:999px;
    padding-inline:14px;
  }

  /* Dark tweaks */
  html.theme-dark .qz-card-shell{background:var(--surface);}
  html.theme-dark .qz-head{background:#020b13;}
  html.theme-dark .qz-table-wrap{background:#020b13;}
  html.theme-dark .qz-table thead th{background:#020b13;}
  html.theme-dark .qz-ins-dialog{background:#020b13;}
  html.theme-dark .qz-ins-box{background:#04151f;}
</style>

<div class="qz-wrap">
  <div class="qz-card-shell card">
    <div class="qz-head">
      <div class="qz-head-icon">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>

      <div class="flex-grow-1">
        <h1 class="qz-head-title">
          My Quizzes & Games
          <span class="qz-count-pill d-none" id="qzTotalCount">0</span>
        </h1>
        <div class="qz-head-sub">
          All your quizzes, bubble games, and door games are listed here in one place.
        </div>
      </div>

      <div class="qz-head-tools">
        <div class="position-relative qz-search">
          <span class="qz-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input type="text" id="qzSearch" class="form-control" placeholder="Search by title…">
        </div>
      </div>
    </div>

    <div class="qz-body">
      <div class="qz-loader-wrap" id="qzLoader">
        <div class="qz-loader"></div>
      </div>

      <div id="qzError" class="qz-error"></div>

      <div id="qzEmpty" class="qz-empty d-none">
        <i class="fa-regular fa-face-smile-beam mb-1"></i><br>
        No quizzes or games available for you right now.
      </div>

      <div class="qz-table-wrap d-none" id="qzTableWrap">
        <table class="table table-sm qz-table">
          <thead>
          <tr>
            <th style="min-width:320px;">Title</th>
            <th style="min-width:50px;">Duration</th>

            {{-- ✅ NEW COLUMN --}}
            <th style="display:none;min-width:170px;">Assigned At</th>

            <th style="min-width:50px;">Instructions</th>
            <th style="min-width:50px;">Status</th>
            <th style="min-width:50px;">Attempts</th>
            <!-- <th style="min-width:120px;">Remaining</th> -->
            <th style="min-width:30px; text-align:right;">Action</th>
          </tr>
          </thead>
          <tbody id="qzTbody"></tbody>
        </table>
      </div>

      <div id="qzPagination" class="qz-pagination d-none">
        <button type="button" class="btn btn-light btn-sm" id="qzPrev">
          <i class="fa-solid fa-arrow-left-long"></i> Previous
        </button>
        <span id="qzPageInfo"></span>
        <button type="button" class="btn btn-light btn-sm" id="qzNext">
          Next <i class="fa-solid fa-arrow-right-long"></i>
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ✅ Instructions Modal --}}
<div class="qz-ins-modal" id="qzInsModal" aria-hidden="true">
  <div class="qz-ins-backdrop" data-close="ins-modal"></div>

  <div class="qz-ins-dialog" role="dialog" aria-modal="true" aria-labelledby="qzInsTitle">
    <div class="qz-ins-head">
      <div>
        <div class="qz-ins-eyebrow" id="qzInsEyebrow">Instructions</div>
        <h2 class="qz-ins-title" id="qzInsTitle">Item</h2>
        <div class="qz-ins-meta" id="qzInsMeta"></div>
      </div>

      <button type="button" class="qz-ins-close" data-close="ins-modal" aria-label="Close instructions">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="qz-ins-body">
      <div class="qz-ins-box" id="qzInsContent"></div>
    </div>

    <div class="qz-ins-foot">
      <button type="button" class="btn btn-light btn-sm" data-close="ins-modal">Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

  // =======================
  // APIs (all in one list)
  // =======================
  const API_QUIZZES = '/api/quizz/my';
  const API_GAMES   = '/api/bubble-games/my';
  const API_DOOR    = '/api/door-games/my';

  // Start routes
  const START_QUIZ_ROUTE = '/exam/';                 // /exam/{quiz_uuid}
  const START_GAME_ROUTE = '/tests/play?game=';      // bubble games play
  const START_DOOR_ROUTE = '/door-tests/play?game='; // door games play

  // =======================
  // DOM
  // =======================
  const loader = document.getElementById('qzLoader');
  const errEl  = document.getElementById('qzError');
  const emptyEl = document.getElementById('qzEmpty');

  const tableWrap = document.getElementById('qzTableWrap');
  const tbody = document.getElementById('qzTbody');

  const searchInput = document.getElementById('qzSearch');
  const totalCountEl = document.getElementById('qzTotalCount');

  const pagWrap = document.getElementById('qzPagination');
  const btnPrev = document.getElementById('qzPrev');
  const btnNext = document.getElementById('qzNext');
  const pageInfo = document.getElementById('qzPageInfo');

  // Instructions modal DOM
  const insModal   = document.getElementById('qzInsModal');
  const insTitle   = document.getElementById('qzInsTitle');
  const insMeta    = document.getElementById('qzInsMeta');
  const insContent = document.getElementById('qzInsContent');

  // =======================
  // Auth helpers
  // =======================
  function getToken() {
    return sessionStorage.getItem('token') || localStorage.getItem('token') || null;
  }
  function clearAuth() {
    try { sessionStorage.removeItem('token'); } catch(e){}
    try { sessionStorage.removeItem('role'); } catch(e){}
    try { localStorage.removeItem('token'); } catch(e){}
    try { localStorage.removeItem('role'); } catch(e){}
  }
  function requireAuthToken() {
    const t = getToken();
    if (!t) {
      window.location.replace('/login');
      return null;
    }
    return t;
  }

  // =======================
  // ✅ Date helpers (Safari + Edge safe)
  // =======================
  function parseAnyDate(input) {
    if (!input) return null;

    if (input instanceof Date && !isNaN(input.getTime())) return input;

    const str = String(input).trim();
    if (!str) return null;

    // Laravel: "YYYY-MM-DD HH:mm:ss" OR "YYYY-MM-DDTHH:mm:ss"
    const m = str.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
    if (m) {
      const y  = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10) - 1;
      const d  = parseInt(m[3], 10);
      const h  = parseInt(m[4], 10);
      const mi = parseInt(m[5], 10);
      const s  = parseInt(m[6] || '0', 10);

      const dt = new Date(y, mo, d, h, mi, s); // local time
      if (!isNaN(dt.getTime())) return dt;
    }

    const d2 = new Date(str);
    return isNaN(d2.getTime()) ? null : d2;
  }

  function formatDateTime(d){
    if (!d) return '—';
    try{
      return d.toLocaleString();
    }catch(e){
      return '—';
    }
  }

  function getAssignmentDate(item) {
    // priority: assigned_at then created_at (fallback)
    const candidates = [
      item.assigned_at,
      item.assignment_time,
      item.assigned_on,
      item.assignedAt,
      item.created_at
    ];
    for (const c of candidates) {
      const d = parseAnyDate(c);
      if (d) return d;
    }
    return new Date(0);
  }

  // =======================
  // UI helpers
  // =======================
  function sanitize(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function nl2brSafe(text) {
    // escape then convert new lines to <br>
    const safe = sanitize(text || '');
    return safe.replace(/\n/g, '<br>');
  }

  function myStatusBadge(status) {
    if (status === 'completed') {
      return '<span class="qz-chip qz-chip-success"><i class="fa-solid fa-circle-check"></i>Completed</span>';
    }
    if (status === 'in_progress') {
      return '<span class="qz-chip qz-chip-primary"><i class="fa-solid fa-play"></i>In progress</span>';
    }
    return '<span class="qz-chip"><i class="fa-regular fa-clock"></i>Pending</span>';
  }

  function itemStatusBadge(status) {
    if (status === 'active') {
      return '<span class="qz-chip qz-chip-primary"><i class="fa-solid fa-signal"></i>Active</span>';
    }
    if (status === 'archived') {
      return '<span class="qz-chip qz-chip-warn"><i class="fa-solid fa-box-archive"></i>Archived</span>';
    }
    if (status === 'inactive') {
      return '<span class="qz-chip qz-chip-warn"><i class="fa-solid fa-ban"></i>Inactive</span>';
    }
    return '';
  }

  function typeChip(type) {
    if (type === 'door') return '<span class="qz-chip"><i class="fa-solid fa-door-open"></i>Door</span>';
    if (type === 'game') return '<span class="qz-chip"><i class="fa-solid fa-gamepad"></i>Game</span>';
    return '<span class="qz-chip"><i class="fa-solid fa-graduation-cap"></i>Quiz</span>';
  }

  function iconHtml(type) {
    if (type === 'door') return '<i class="fa-solid fa-door-open"></i>';
    if (type === 'game') return '<i class="fa-solid fa-gamepad"></i>';
    return '<i class="fa-solid fa-graduation-cap"></i>';
  }

  function toBool(v) {
    if (v === true || v === 1) return true;
    if (v === false || v === 0) return false;
    if (typeof v === 'string') {
      const s = v.trim().toLowerCase();
      if (s === '1' || s === 'true' || s === 'yes') return true;
      if (s === '0' || s === 'false' || s === 'no') return false;
    }
    return null;
  }

  function toDurationText(item) {
    const t = item.total_time ?? item.total_time_minutes ?? item.duration ?? null;
    if (t === null || t === undefined || t === '') return '—';
    const n = parseInt(t, 10);
    if (!isNaN(n) && n > 0) return n + ' min';
    return String(t);
  }

  function pickInstructions(item) {
    return item.instructions || item.excerpt || item.description || item.note || '';
  }

  function pickAllowedAttempts(item) {
    const v =
      item.max_attempts_allowed ??
      item.max_attempts ??
      item.max_attempt ??
      item.total_attempts_allowed ??
      item.attempts_allowed ??
      item.allowed_attempts ??
      item.total_attempts ??
      1;
    const n = parseInt(v, 10);
    return (isNaN(n) || n <= 0) ? 1 : n;
  }

  function pickUsedAttempts(item) {
    // Try numeric counters first (if API provides)
    const candidates = [
      item.my_attempts,
      item.attempts_used,
      item.attempts_taken,
      item.attempt_count,
      item.latest_attempt_no,
      item.used_attempts,
      (item.result && item.result.attempt_no ? item.result.attempt_no : null),
    ];

    for (const v of candidates) {
      if (v !== undefined && v !== null && v !== '') {
        const n = parseInt(v, 10);
        if (!isNaN(n)) return n;
      }
    }

    // ✅ QUIZ fallback: your quiz API uses `attempt` / `result` / `my_status`
    if (item.type === 'quiz') {
      if (item.my_status === 'completed') return 1;
      if (item.attempt && (item.attempt.id || item.attempt.status)) return 1;
      if (item.result && item.result.id) return 1;
    }

    return 0;
  }

  function computeRemainingAttempts(item, allowed, used) {
    if (item.remaining_attempts !== undefined && item.remaining_attempts !== null) {
      const n = parseInt(item.remaining_attempts, 10);
      return isNaN(n) ? 0 : Math.max(n, 0);
    }
    return Math.max((allowed || 0) - (used || 0), 0);
  }

  // ✅ "No Attempts left" ONLY for Games & Door (NOT quizzes)
  function computeActionMeta(type, item) {
    const myStatus = item.my_status || 'Pending';
    const status   = item.status || 'active';

    const allowed = pickAllowedAttempts(item);
    const used    = pickUsedAttempts(item);
    const remaining = computeRemainingAttempts(item, allowed, used);

    // ✅ "No Attempts left" for Quizzes + Games + Door
    const enforceAttemptLimit = (type === 'quiz' || type === 'game' || type === 'door');

    const allowContinueEvenIfMax = (enforceAttemptLimit && myStatus === 'in_progress');

    const apiMaxReached = toBool(item.max_attempt_reached);
    const apiCanAttempt = toBool(item.can_attempt);

    const maxAttemptReached = (enforceAttemptLimit && !allowContinueEvenIfMax && (
      apiMaxReached === true ||
      apiCanAttempt === false ||
      remaining <= 0 ||
      (allowed > 0 && used >= allowed)
    ));

    let label = 'Start';
    if (myStatus === 'in_progress') label = 'Continue';
    else if (myStatus === 'completed') label = 'Retake';

    if (enforceAttemptLimit && maxAttemptReached) label = 'Finished';

    const isDisabled = (status !== 'active') || (enforceAttemptLimit && maxAttemptReached);

    let title = '';
    if (status !== 'active') title = 'This item is not active';
    if (enforceAttemptLimit && maxAttemptReached) {
      title = `Maximum attempts reached (${used}/${allowed})`;
    }

    let startUrl = '#';
    if (type === 'quiz') startUrl = START_QUIZ_ROUTE + encodeURIComponent(item.uuid);
    if (type === 'game') startUrl = START_GAME_ROUTE + encodeURIComponent(item.uuid);
    if (type === 'door') startUrl = START_DOOR_ROUTE + encodeURIComponent(item.uuid);

    return { myStatus, status, allowed, used, remaining, label, isDisabled, title, startUrl, maxAttemptReached };
  }

  // =======================
  // Instructions Modal
  // =======================
  function nl2brWithDOMPurify(text) {
  if (!text) return '';
  
  // Convert newlines to <br>
  const withBreaks = text.replace(/\n/g, '<br>');
  
  // Sanitize while allowing safe tags
  return DOMPurify.sanitize(withBreaks, {
    ALLOWED_TAGS: ['b', 'strong', 'i', 'em', 'u', 'br', 'p', 'ul', 'ol', 'li'],
    ALLOWED_ATTR: []
  });
}

// ✅ UPDATE YOUR FUNCTION
function openInsModal(row) {
    if (!row) return;

    const duration = toDurationText(row);
    const created = row.created_at ? new Date(row.created_at).toLocaleDateString() : '';

    insTitle.innerHTML = `${sanitize(row.title || 'Item')} ${typeChip(row.type)}`;
    insMeta.innerHTML = `
      <span><i class="fa-regular fa-clock"></i> ${sanitize(duration)}</span>
      ${created ? `<span><i class="fa-regular fa-calendar"></i> Added ${sanitize(created)}</span>` : ''}
    `;

   const inst = pickInstructions(row);

if (inst) {
  // Just convert newlines to <br> and render the HTML as-is
  insContent.innerHTML = inst.replace(/\n/g, '<br>');
} else {
  insContent.innerHTML = '<span class="text-muted">No instructions available.</span>';
}
    insModal.classList.add('show');
    insModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
}

  function closeInsModal() {
    insModal.classList.remove('show');
    insModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }

  // Close clicks
  document.querySelectorAll('[data-close="ins-modal"]').forEach(el => {
    el.addEventListener('click', closeInsModal);
  });

  // ESC close
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && insModal.classList.contains('show')) closeInsModal();
  });

  // =======================
  // Client pagination + state
  // =======================
  const state = {
    loadedOnce: false,
    q: '',
    perPage: 10,
    page: 1,
    all: [],
    filtered: []
  };

  function applySearchAndPagination(resetPage) {
    const q = (state.q || '').trim().toLowerCase();

    state.filtered = state.all.filter(x => {
      const t = (x.title || '').toLowerCase();
      return q === '' ? true : t.includes(q);
    });

    if (resetPage) state.page = 1;

    renderTable();
    renderPagination();
    renderCount();
  }

  function renderCount() {
    const n = state.filtered.length || 0;
    totalCountEl.textContent = String(n);
    totalCountEl.classList.toggle('d-none', n <= 0);
  }

  function renderTable() {
    tbody.innerHTML = '';

    if (!state.filtered.length) {
      tableWrap.classList.add('d-none');
      pagWrap.classList.add('d-none');
      emptyEl.classList.remove('d-none');
      return;
    }

    emptyEl.classList.add('d-none');
    tableWrap.classList.remove('d-none');

    const start = (state.page - 1) * state.perPage;
    const end   = start + state.perPage;
    const pageItems = state.filtered.slice(start, end);

    const frag = document.createDocumentFragment();

    pageItems.forEach(row => {
      const tr = document.createElement('tr');

      const meta = computeActionMeta(row.type, row);
      const durationText = toDurationText(row);

      const allowed = meta.allowed;
      const used = meta.used;

      // ✅ Assigned At (date + time)
      const assignedDate = getAssignmentDate(row);
      const assignedText = formatDateTime(assignedDate);

      const addedText = row.created_at ? new Date(row.created_at).toLocaleDateString() : '';
      const hasInstructions = !!(pickInstructions(row) || '').trim();

      tr.innerHTML = `
        <td>
          <div class="qz-title-cell">
            <div class="qz-avatar">${iconHtml(row.type)}</div>
            <div class="qz-title-text">
              <div class="qz-title">
                ${sanitize(row.title || 'Item')}
                ${typeChip(row.type)}
              </div>
              <div class="qz-submeta">
                ${addedText ? ('Added ' + sanitize(addedText)) : ''}
              </div>
            </div>
          </div>
        </td>

        <td class="qz-num">${sanitize(durationText)}</td>

        {{-- ✅ NEW COLUMN CELL --}}
        <td class="qz-num d-none">${sanitize(assignedText)}</td>

        <td>
          <button type="button"
            class="btn btn-sm qz-inst-btn"
            data-action="view-instructions"
            ${hasInstructions ? '' : 'disabled'}
            title="${hasInstructions ? 'View instructions' : 'No instructions'}">
            <i class="fa-regular fa-eye"></i> View
          </button>
        </td>

        <td>
          <div class="qz-status-stack">
            ${myStatusBadge(meta.myStatus)}
          </div>
        </td>

        <td class="qz-num">${used}/${allowed}</td>

        <td>
          <div class="qz-actions">
            <button type="button"
              class="btn btn-primary btn-sm"
              data-action="start"
              ${meta.isDisabled ? 'disabled' : ''}
              ${meta.title ? `title="${sanitize(meta.title)}"` : ''}>
              <i class="fa-solid fa-arrow-right"></i>
              <span>${sanitize(meta.label)}</span>
            </button>
          </div>
        </td>
      `;

      // Start
      const startBtn = tr.querySelector('[data-action="start"]');
      if (startBtn) {
        startBtn.addEventListener('click', () => {
          if (!row.uuid) return;
          if (meta.isDisabled) return;
          window.location.href = meta.startUrl;
        });
      }

      // Instructions modal open
      const viewBtn = tr.querySelector('[data-action="view-instructions"]');
      if (viewBtn) {
        viewBtn.addEventListener('click', () => {
          if (!hasInstructions) return;
          openInsModal(row);
        });
      }

      frag.appendChild(tr);
    });

    tbody.appendChild(frag);
  }

  function renderPagination() {
    const total = state.filtered.length || 0;
    const lastPage = Math.max(Math.ceil(total / state.perPage), 1);

    if (!total || lastPage <= 1) {
      pagWrap.classList.add('d-none');
      return;
    }

    pagWrap.classList.remove('d-none');

    if (state.page > lastPage) state.page = lastPage;

    btnPrev.disabled = state.page <= 1;
    btnNext.disabled = state.page >= lastPage;

    pageInfo.textContent = `Page ${state.page} of ${lastPage} • ${total} item${total > 1 ? 's' : ''}`;
  }

  btnPrev.addEventListener('click', () => {
    if (state.page > 1) {
      state.page -= 1;
      renderTable();
      renderPagination();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  btnNext.addEventListener('click', () => {
    const total = state.filtered.length || 0;
    const lastPage = Math.max(Math.ceil(total / state.perPage), 1);
    if (state.page < lastPage) {
      state.page += 1;
      renderTable();
      renderPagination();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  // =======================
  // Fetch all items (merge)
  // =======================
  async function fetchOne(url, token) {
    const params = new URLSearchParams();
    params.set('page', 1);
    params.set('per_page', 1000);

    const res = await fetch(url + '?' + params.toString(), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token
      }
    });

    const json = await res.json().catch(() => ({}));

    if (res.status === 401 || res.status === 403) {
      clearAuth();
      window.location.replace('/login');
      return { ok: false, data: [] };
    }

    if (!res.ok) {
      const msg = json.message || json.error || 'Failed to load list.';
      throw new Error(msg);
    }

    return { ok: true, data: (json.data || []) };
  }

  function normalizeItem(item, type) {
    return {
      type: type, // quiz | game | door
      uuid: item.uuid || item.id || null,
      title: item.title || item.name || 'Item',
      instructions: pickInstructions(item),
      excerpt: item.excerpt || '',
      description: item.description || '',
      status: item.status || 'active',
      my_status: item.my_status || item.myStatus || 'Pending',
      is_public: item.is_public ?? item.public ?? 0,

      total_time: item.total_time ?? item.total_time_minutes ?? item.duration ?? null,

      // ✅ keep raw attempt + result for quiz logic
      attempt: item.attempt ?? null,
      result: item.result ?? null,

      // ✅ quizzes use total_attempts in API response
      total_attempts: item.total_attempts ?? null,

      max_attempts_allowed: item.max_attempts_allowed,
      max_attempts: item.max_attempts,
      max_attempt: item.max_attempt,
      attempts_allowed: item.attempts_allowed,
      total_attempts_allowed: item.total_attempts_allowed,

      my_attempts: item.my_attempts,
      attempts_used: item.attempts_used,
      attempts_taken: item.attempts_taken,
      attempt_count: item.attempt_count,
      latest_attempt_no: item.latest_attempt_no,
      used_attempts: item.used_attempts,
      remaining_attempts: item.remaining_attempts,

      max_attempt_reached: item.max_attempt_reached,
      can_attempt: item.can_attempt,

      // ✅ NEW: capture assigned_at from API
      assigned_at: item.assigned_at || null,

      created_at: item.created_at || null
    };
  }

  async function fetchAll() {
    const token = requireAuthToken();
    if (!token) return;

    loader.classList.add('show');
    errEl.classList.remove('show');
    errEl.textContent = '';

    try {
      const [qz, gm, dr] = await Promise.all([
        fetchOne(API_QUIZZES, token).catch(e => ({ ok:false, data:[], _err:e })),
        fetchOne(API_GAMES, token).catch(e => ({ ok:false, data:[], _err:e })),
        fetchOne(API_DOOR, token).catch(e => ({ ok:false, data:[], _err:e })),
      ]);

      if (!qz.ok && !gm.ok && !dr.ok) {
        const firstErr = (qz._err || gm._err || dr._err);
        throw firstErr || new Error('Failed to load items.');
      }

      const merged = [];
      (qz.data || []).forEach(i => merged.push(normalizeItem(i, 'quiz')));
      (gm.data || []).forEach(i => merged.push(normalizeItem(i, 'game')));
      (dr.data || []).forEach(i => merged.push(normalizeItem(i, 'door')));

      // ✅ SORT: assigned_at (oldest -> newest), fallback created_at
      merged.sort((a,b) => {
        const ta = getAssignmentDate(a).getTime();
        const tb = getAssignmentDate(b).getTime();
        return ta - tb;
      });

      state.all = merged;
      state.loadedOnce = true;

      applySearchAndPagination(true);

      // partial load warning (optional)
      const partialErrors = [];
      if (!qz.ok && qz._err) partialErrors.push('Quizzes');
      if (!gm.ok && gm._err) partialErrors.push('Games');
      if (!dr.ok && dr._err) partialErrors.push('Door Games');

      if (partialErrors.length) {
        errEl.textContent = partialErrors.join(', ') + ' failed to load (showing available items).';
        errEl.classList.add('show');
      }

    } catch (e) {
      console.error(e);
      state.all = [];
      state.filtered = [];
      applySearchAndPagination(true);
      errEl.textContent = e.message || 'Something went wrong while loading.';
      errEl.classList.add('show');
    } finally {
      loader.classList.remove('show');
    }
  }

  // =======================
  // Search (debounced)
  // =======================
  let searchTimer = null;
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      if (searchTimer) clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.q = searchInput.value || '';
        applySearchAndPagination(true);
      }, 300);
    });
  }

  // Initial load
  fetchAll();

});
</script>
@endpush
