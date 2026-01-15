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
  }
  .qz-head-sub{color:var(--muted-color); font-size:var(--fs-13);}
  .qz-head-tools{
    margin-left:auto;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .qz-search{
    min-width:220px;
  }
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

  .qz-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
    gap:14px;
  }
  .qz-card{
    position:relative;
    border-radius:14px;
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    padding:12px 12px 10px;
    display:flex;
    flex-direction:column;
    gap:8px;
    min-height:160px;
    box-shadow:var(--shadow-1);
    transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
  }
  .qz-card:hover{
    transform:translateY(-3px);
    box-shadow:var(--shadow-2);
    border-color:var(--accent-color);
    background:var(--surface);
  }

  .qz-card-top{
    display:flex;
    align-items:flex-start;
    gap:10px;
  }
  .qz-avatar{
    width:40px;height:40px;
    border-radius:14px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    display:flex;align-items:center;justify-content:center;
    color:var(--accent-color);
    flex-shrink:0;
  }
  .qz-title{
    font-family:var(--font-head);
    font-size:.98rem;
    font-weight:600;
    margin:0;
    color:var(--ink);
  }
  .qz-excerpt{
    font-size:var(--fs-13);
    color:var(--muted-color);
    max-height:40px;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .qz-badges{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
    margin-top:2px;
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

  .qz-meta{
    display:flex;
    flex-wrap:wrap;
    gap:8px 14px;
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-top:4px;
  }
  .qz-meta span{
    display:inline-flex;
    align-items:center;
    gap:6px;
  }
  .qz-meta i{font-size:11px;}

  .qz-footer{
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:8px;
  }
  .qz-footer .btn{
    border-radius:999px;
    padding-inline:12px;
  }
  .qz-footer .btn-outline-primary{
    border-style:dashed;
  }
  .qz-footer .sub{
    margin-left:auto;
    font-size:11px;
    color:var(--muted-color);
  }

  .qz-empty{
    border:1px dashed var(--line-strong);
    border-radius:10px;
    padding:22px 16px;
    text-align:center;
    color:var(--muted-color);
    background:var(--surface-2);
    font-size:var(--fs-13);
  }

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

  /* Dark tweaks */
  html.theme-dark .qz-card-shell{background:var(--surface);}
  html.theme-dark .qz-card{background:#04151f;}
  html.theme-dark .qz-card:hover{background:#020b13;}
  html.theme-dark .qz-empty{background:#020b13;}
  html.theme-dark .qz-head{background:#020b13;}

  /* ==========================
   * Tabs (scoped)
   * ========================== */
  .qz-tabs{
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:10px;
    flex-wrap:wrap;
  }
  .qz-tab-btn{
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    color:var(--muted-color);
    border-radius:999px;
    padding:7px 12px;
    font-size:var(--fs-13);
    display:inline-flex;
    align-items:center;
    gap:8px;
    transition:background .15s ease, border-color .15s ease, transform .15s ease;
    user-select:none;
  }
  .qz-tab-btn:hover{
    transform:translateY(-1px);
    border-color:var(--accent-color);
    background:var(--surface);
  }
  .qz-tab-btn.active{
    background:var(--t-primary);
    border-color:rgba(20,184,166,.35);
    color:#0f766e;
    font-weight:600;
  }
  .qz-tab-count{
    min-width:22px;
    height:18px;
    padding:0 6px;
    border-radius:999px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:11px;
    color:var(--muted-color);
  }
  .qz-tab-btn.active .qz-tab-count{
    color:#0f766e;
    border-color:rgba(20,184,166,.25);
  }

  .qz-tab-panel{display:none;}
  .qz-tab-panel.active{display:block;}

  /* =========================
   * Attempts Modal (shared)
   * ========================= */
  body.modal-open{overflow:hidden;}
  .qz-attempt-modal{
    position:fixed;
    inset:0;
    z-index:1050;
    display:none;
    align-items:center;
    justify-content:center;
  }
  .qz-attempt-modal.show{display:flex;}
  .qz-attempt-backdrop{
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.45);
  }
  .qz-attempt-dialog{
    position:relative;
    width:100%;
    max-width:920px;
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
  html.theme-dark .qz-attempt-dialog{background:#020b13;}

  .qz-attempt-head{
    display:flex;
    align-items:flex-start;
    gap:10px;
    margin-bottom:8px;
  }
  .qz-attempt-eyebrow{
    font-size:var(--fs-11);
    text-transform:uppercase;
    letter-spacing:.08em;
    color:var(--muted-color);
    font-weight:600;
    margin-bottom:2px;
  }
  .qz-attempt-title{
    font-family:var(--font-head);
    font-size:1.02rem;
    font-weight:600;
    color:var(--ink);
    margin:0;
  }
  .qz-attempt-meta{
    margin-top:3px;
    font-size:var(--fs-12);
    color:var(--muted-color);
  }
  .qz-attempt-meta span+span::before{
    content:"•";
    margin:0 6px;
    opacity:.6;
  }

  .qz-attempt-close{
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
  .qz-attempt-close:hover{
    background:var(--surface-2);
    color:var(--ink);
  }

  .qz-attempt-body{
    position:relative;
    padding-top:6px;
    padding-bottom:8px;
    overflow:auto;
  }

  .qz-attempt-loader-wrap{
    position:absolute;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    background:rgba(0,0,0,.03);
    z-index:2;
  }
  .qz-attempt-loader-wrap.show{display:flex;}
  .qz-attempt-loader{
    width:22px;height:22px;
    border-radius:50%;
    border:3px solid #0001;
    border-top-color:var(--accent-color);
    animation:rot 1s linear infinite;
  }

  .qz-attempt-error{
    font-size:12px;
    color:var(--danger-color);
    margin-bottom:6px;
    display:none;
  }
  .qz-attempt-error.show{display:block;}

  .qz-attempt-empty{
    border-radius:10px;
    border:1px dashed var(--line-strong);
    padding:16px 12px;
    font-size:var(--fs-13);
    color:var(--muted-color);
    text-align:center;
    display:none;
    background:var(--surface-2);
  }

  .qz-attempt-table-wrap{
    max-height:360px;
    overflow:auto;
  }
  .qz-attempt-table{
    width:100%;
    margin-bottom:0;
  }
  .qz-attempt-table thead th{
    font-size:var(--fs-12);
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted-color);
    border-bottom:1px solid var(--line-strong);
    white-space:nowrap;
    padding-block:8px;
  }
  .qz-attempt-table tbody td{
    font-size:var(--fs-13);
    vertical-align:middle;
    border-color:var(--line-strong);
    padding-block:6px;
  }
  .qz-attempt-table tbody tr:last-child td{border-bottom:none;}
  .qz-attempt-col-idx{width:40px;}
  .qz-attempt-col-score{white-space:nowrap;}
  .qz-attempt-col-action{text-align:right; white-space:nowrap;}

  .qz-attempt-note{
    font-size:11px;
    color:var(--muted-color);
  }

  .qz-attempt-foot{
    display:flex;
    justify-content:flex-end;
    padding-top:6px;
    border-top:1px solid var(--line-strong);
  }
  .qz-attempt-foot .btn{
    border-radius:999px;
    padding-inline:14px;
  }
</style>

<div class="qz-wrap">
  <div class="qz-card-shell card">
    <div class="qz-head">
      <div class="qz-head-icon">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>

      <div class="flex-grow-1">
        <h1 class="qz-head-title">My Quizzes & Games</h1>
        <div class="qz-head-sub">
          Switch tabs to view your quizzes or graphical tests, and start/continue attempts.
        </div>

        <div class="qz-tabs" role="tablist" aria-label="My exams tabs">
          <button type="button" class="qz-tab-btn active" data-tab="quizzes" role="tab" aria-selected="true">
            <i class="fa-solid fa-graduation-cap"></i>
            Quizzes
            <span class="qz-tab-count d-none" id="tabCountQuizzes">0</span>
          </button>

          <button type="button" class="qz-tab-btn" data-tab="games" role="tab" aria-selected="false">
            <i class="fa-solid fa-gamepad"></i>
            Graphical Tests
            <span class="qz-tab-count d-none" id="tabCountGames">0</span>
          </button>

          {{-- ✅ NEW: Door Games tab --}}
          <button type="button" class="qz-tab-btn" data-tab="door" role="tab" aria-selected="false">
            <i class="fa-solid fa-door-open"></i>
            Door Games
            <span class="qz-tab-count d-none" id="tabCountDoor">0</span>
          </button>
        </div>
      </div>

      <div class="qz-head-tools">
        <div class="position-relative qz-search">
          <span class="qz-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input type="text" id="qzSearch" class="form-control" placeholder="Search…">
        </div>
      </div>
    </div>

    <div class="qz-body">

      {{-- ============ QUIZZES TAB ============ --}}
      <div class="qz-tab-panel active" id="panelQuizzes" data-panel="quizzes">
        <div class="qz-loader-wrap" id="qzLoaderQuizzes">
          <div class="qz-loader"></div>
        </div>

        <div id="qzErrorQuizzes" class="qz-error"></div>

        <div id="qzEmptyQuizzes" class="qz-empty d-none">
          <i class="fa-regular fa-face-smile-beam mb-1"></i><br>
          No quizzes available for you right now. Your upcoming quizzes will appear here.
        </div>

        <div id="qzListQuizzes" class="qz-grid"></div>

        <div id="qzPaginationQuizzes" class="qz-pagination d-none">
          <button type="button" class="btn btn-light btn-sm" id="qzPrevQuizzes">
            <i class="fa-solid fa-arrow-left-long"></i> Previous
          </button>
          <span id="qzPageInfoQuizzes"></span>
          <button type="button" class="btn btn-light btn-sm" id="qzNextQuizzes">
            Next <i class="fa-solid fa-arrow-right-long"></i>
          </button>
        </div>
      </div>

      {{-- ============ GAMES TAB ============ --}}
      <div class="qz-tab-panel" id="panelGames" data-panel="games">
        <div class="qz-loader-wrap" id="qzLoaderGames">
          <div class="qz-loader"></div>
        </div>

        <div id="qzErrorGames" class="qz-error"></div>

        <div id="qzEmptyGames" class="qz-empty d-none">
          <i class="fa-regular fa-face-smile-beam mb-1"></i><br>
          No games available for you right now. Your upcoming games will appear here.
        </div>

        <div id="qzListGames" class="qz-grid"></div>

        <div id="qzPaginationGames" class="qz-pagination d-none">
          <button type="button" class="btn btn-light btn-sm" id="qzPrevGames">
            <i class="fa-solid fa-arrow-left-long"></i> Previous
          </button>
          <span id="qzPageInfoGames"></span>
          <button type="button" class="btn btn-light btn-sm" id="qzNextGames">
            Next <i class="fa-solid fa-arrow-right-long"></i>
          </button>
        </div>
      </div>

      {{-- ✅ NEW: DOOR GAMES TAB --}}
      <div class="qz-tab-panel" id="panelDoor" data-panel="door">
        <div class="qz-loader-wrap" id="qzLoaderDoor">
          <div class="qz-loader"></div>
        </div>

        <div id="qzErrorDoor" class="qz-error"></div>

        <div id="qzEmptyDoor" class="qz-empty d-none">
          <i class="fa-regular fa-face-smile-beam mb-1"></i><br>
          No door games available for you right now. Your upcoming door games will appear here.
        </div>

        <div id="qzListDoor" class="qz-grid"></div>

        <div id="qzPaginationDoor" class="qz-pagination d-none">
          <button type="button" class="btn btn-light btn-sm" id="qzPrevDoor">
            <i class="fa-solid fa-arrow-left-long"></i> Previous
          </button>
          <span id="qzPageInfoDoor"></span>
          <button type="button" class="btn btn-light btn-sm" id="qzNextDoor">
            Next <i class="fa-solid fa-arrow-right-long"></i>
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- Attempts Modal (shared for both) --}}
<div class="qz-attempt-modal" id="qzAttemptModal" aria-hidden="true">
  <div class="qz-attempt-backdrop" data-close="attempt-modal"></div>
  <div class="qz-attempt-dialog" role="dialog" aria-modal="true" aria-labelledby="qzAttemptTitle">
    <div class="qz-attempt-head">
      <div>
        <div class="qz-attempt-eyebrow" id="qzAttemptEyebrow">Attempts</div>
        <h2 class="qz-attempt-title" id="qzAttemptTitle">Item</h2>
        <div class="qz-attempt-meta" id="qzAttemptMeta"></div>
      </div>
      <button type="button" class="qz-attempt-close" data-close="attempt-modal" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="qz-attempt-body">
      <div class="qz-attempt-loader-wrap" id="qzAttemptLoader">
        <div class="qz-attempt-loader"></div>
      </div>

      <div id="qzAttemptError" class="qz-attempt-error"></div>

      <div id="qzAttemptEmpty" class="qz-attempt-empty">
        No attempts yet. Start to see your attempts here.
      </div>

      <div class="table-responsive qz-attempt-table-wrap">
        <table class="table table-sm qz-attempt-table">
          <thead>
          <tr>
            <th class="qz-attempt-col-idx">#</th>
            <th>Attempted on</th>
            <th>Status</th>
            <th class="qz-attempt-col-score">Score</th>
            <th class="qz-attempt-col-action">Action</th>
          </tr>
          </thead>
          <tbody id="qzAttemptTbody"></tbody>
        </table>
      </div>
    </div>

    <div class="qz-attempt-foot">
      <button type="button" class="btn btn-light btn-sm" data-close="attempt-modal">Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // =======================
  // APIs (tabs)
  // =======================
  const API_QUIZZES = '/api/quizz/my';
  const API_GAMES   = '/api/bubble-games/my';
  const API_DOOR    = '/api/door-games/my'; // ✅ NEW: assigned door games (create this like bubble-games/my)

  // Attempts base
  const ATTEMPTS_QUIZZES = '/api/exam/quizzes';     // /{quizKey}/my-attempts
  const ATTEMPTS_GAMES   = '/api/bubble-games';     // /{gameKey}/my-attempts
  const ATTEMPTS_DOOR    = '/api/door-games';       // ✅ NEW: /{gameKey}/my-attempts (create route similar)

  // Exam start routes
  const START_QUIZ_ROUTE = '/exam/';                // /exam/{quiz_uuid}
  const START_GAME_ROUTE = '/tests/play?game=';     // bubble
  const START_DOOR_ROUTE = '/door-games/exam?game='; // ✅ NEW: door game play URL

  // =======================
  // Tabs
  // =======================
  const tabBtns = document.querySelectorAll('.qz-tab-btn');
  const panels  = document.querySelectorAll('.qz-tab-panel');
  const searchInput = document.getElementById('qzSearch');

  const tabCountQuizzes = document.getElementById('tabCountQuizzes');
  const tabCountGames   = document.getElementById('tabCountGames');
  const tabCountDoor    = document.getElementById('tabCountDoor'); // ✅ NEW

  let activeTab = 'quizzes'; // quizzes | games | door

  function setActiveTab(tab) {
    activeTab = tab;

    tabBtns.forEach(btn => {
      const isActive = btn.getAttribute('data-tab') === tab;
      btn.classList.toggle('active', isActive);
      btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    panels.forEach(p => {
      const isActive = p.getAttribute('data-panel') === tab;
      p.classList.toggle('active', isActive);
    });

    if (tab === 'quizzes' && !state.quizzes.loadedOnce) fetchList('quizzes', 1);
    if (tab === 'games'   && !state.games.loadedOnce)   fetchList('games', 1);
    if (tab === 'door'    && !state.door.loadedOnce)    fetchList('door', 1); // ✅ NEW
  }

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => setActiveTab(btn.getAttribute('data-tab')));
  });

  // =======================
  // Shared auth helpers
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
  // UI helpers
  // =======================
  function sanitize(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function myStatusBadge(status) {
    if (status === 'completed') {
      return '<span class="qz-chip qz-chip-success"><i class="fa-solid fa-circle-check"></i>Completed</span>';
    }
    if (status === 'in_progress') {
      return '<span class="qz-chip qz-chip-primary"><i class="fa-solid fa-play"></i>In progress</span>';
    }
    return '<span class="qz-chip"><i class="fa-regular fa-clock"></i>Upcoming</span>';
  }

  function statusBadge(status) {
    if (status === 'active') {
      return '<span class="qz-chip qz-chip-primary"><i class="fa-solid fa-signal"></i>Active</span>';
    }
    if (status === 'archived') {
      return '<span class="qz-chip qz-chip-warn"><i class="fa-solid fa-box-archive"></i>Archived</span>';
    }
    return '';
  }

  function publicBadge(isPublic) {
    return isPublic
      ? '<span class="qz-chip"><i class="fa-solid fa-globe"></i>Public</span>'
      : '<span class="qz-chip"><i class="fa-solid fa-lock"></i>Private</span>';
  }

  // =======================
  // State per tab
  // =======================
  const state = {
    quizzes: {
      loadedOnce: false,
      currentPage: 1,
      lastPage: 1,
      q: '',
      els: {
        list: document.getElementById('qzListQuizzes'),
        empty: document.getElementById('qzEmptyQuizzes'),
        loader: document.getElementById('qzLoaderQuizzes'),
        error: document.getElementById('qzErrorQuizzes'),
        pagination: document.getElementById('qzPaginationQuizzes'),
        prev: document.getElementById('qzPrevQuizzes'),
        next: document.getElementById('qzNextQuizzes'),
        info: document.getElementById('qzPageInfoQuizzes')
      }
    },
    games: {
      loadedOnce: false,
      currentPage: 1,
      lastPage: 1,
      q: '',
      els: {
        list: document.getElementById('qzListGames'),
        empty: document.getElementById('qzEmptyGames'),
        loader: document.getElementById('qzLoaderGames'),
        error: document.getElementById('qzErrorGames'),
        pagination: document.getElementById('qzPaginationGames'),
        prev: document.getElementById('qzPrevGames'),
        next: document.getElementById('qzNextGames'),
        info: document.getElementById('qzPageInfoGames')
      }
    },
    door: { // ✅ NEW
      loadedOnce: false,
      currentPage: 1,
      lastPage: 1,
      q: '',
      els: {
        list: document.getElementById('qzListDoor'),
        empty: document.getElementById('qzEmptyDoor'),
        loader: document.getElementById('qzLoaderDoor'),
        error: document.getElementById('qzErrorDoor'),
        pagination: document.getElementById('qzPaginationDoor'),
        prev: document.getElementById('qzPrevDoor'),
        next: document.getElementById('qzNextDoor'),
        info: document.getElementById('qzPageInfoDoor')
      }
    }
  };

  // =======================
  // Render cards (shared)
  // =======================
  function renderCards(tab, items) {
    const S = state[tab];
    const listEl = S.els.list;

    listEl.innerHTML = '';

    if (!items || !items.length) {
      S.els.empty.classList.remove('d-none');
      S.els.pagination.classList.add('d-none');
      return;
    }
    S.els.empty.classList.add('d-none');

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

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      const card = document.createElement('article');
      card.className = 'qz-card';

      const totalTime = item.total_time
        ? item.total_time + ' min'
        : (item.total_time_minutes ? (item.total_time_minutes + ' min') : '—');

      const totalQ = item.total_questions || 0;

      const myStatus = item.my_status || 'upcoming';

      const attemptsAllowed = parseInt(
        item.max_attempts_allowed ??
        item.max_attempts ??
        item.max_attempt ??
        item.total_attempts_allowed ??
        item.attempts_allowed ??
        item.allowed_attempts ??
        item.total_attempts ??
        1,
        10
      ) || 1;

      const attemptsUsed = parseInt(
        item.my_attempts ??
        item.attempts_used ??
        item.attempts_taken ??
        item.attempt_count ??
        item.latest_attempt_no ??
        (item.result && item.result.attempt_no ? item.result.attempt_no : null) ??
        item.used_attempts ??
        0,
        10
      ) || 0;

      const remainingAttempts = (item.remaining_attempts !== undefined && item.remaining_attempts !== null)
        ? (parseInt(item.remaining_attempts, 10) || 0)
        : Math.max(attemptsAllowed - attemptsUsed, 0);

      // allow continue for in_progress even if max reached
      const allowContinueEvenIfMax = ((tab === 'games' || tab === 'door') && myStatus === 'in_progress');

      const apiMaxReached = toBool(item.max_attempt_reached);
      const apiCanAttempt = toBool(item.can_attempt);

      const maxAttemptReached = ((tab === 'games' || tab === 'door') && !allowContinueEvenIfMax && (
        apiMaxReached === true ||
        apiCanAttempt === false ||
        remainingAttempts <= 0 ||
        (attemptsAllowed > 0 && attemptsUsed >= attemptsAllowed)
      ));

      // ✅ Labels
      let primaryLabel = 'Start';
      if (myStatus === 'in_progress') {
        primaryLabel = 'Continue';
      } else if (tab === 'games' || tab === 'door') {
        if (maxAttemptReached) primaryLabel = 'No Attempts left';
        else if (myStatus === 'completed') primaryLabel = 'Retake';
        else primaryLabel = 'Start';
      } else {
        if (myStatus === 'completed') primaryLabel = 'Retake';
        else primaryLabel = 'Start';
      }

      const isDisabled = (item.status !== 'active') || ((tab === 'games' || tab === 'door') && maxAttemptReached);
      const disabledAttr = isDisabled ? 'disabled' : '';

      let startTitle = '';
      if (item.status !== 'active') startTitle = 'This item is not active';
      if ((tab === 'games' || tab === 'door') && maxAttemptReached) {
        startTitle = `Maximum attempts reached (${attemptsUsed}/${attemptsAllowed})`;
      }

      const iconHtml = (tab === 'door')
        ? '<i class="fa-solid fa-door-open"></i>'
        : (tab === 'games')
          ? '<i class="fa-solid fa-gamepad"></i>'
          : '<i class="fa-solid fa-graduation-cap"></i>';

      card.innerHTML = `
        <div class="qz-card-top">
          <div class="qz-avatar">${iconHtml}</div>
          <div class="flex-grow-1">
            <h3 class="qz-title">${sanitize(item.title || 'Item')}</h3>
            <p class="qz-excerpt">${sanitize(item.excerpt || '')}</p>
            <div class="qz-badges">
              ${myStatusBadge(myStatus)}
              ${statusBadge(item.status)}
              ${publicBadge(!!item.is_public)}
            </div>
          </div>
        </div>

        <div class="qz-meta">
          <span><i class="fa-regular fa-circle-question"></i>${totalQ} questions</span>
          <span><i class="fa-regular fa-clock"></i>${totalTime}</span>
          <span><i class="fa-solid fa-rotate-right"></i>${attemptsAllowed} attempt${attemptsAllowed > 1 ? 's' : ''} allowed</span>
        </div>

        <div class="qz-footer">
          <button type="button"
            class="btn btn-primary btn-sm"
            data-action="start"
            ${disabledAttr}
            ${startTitle ? `title="${sanitize(startTitle)}"` : ''}>
            <i class="fa-solid fa-arrow-right"></i>
            <span>${primaryLabel}</span>
          </button>

          <span class="sub">
            Added ${item.created_at ? new Date(item.created_at).toLocaleDateString() : ''}
          </span>
        </div>
      `;

      const startBtn = card.querySelector('[data-action="start"]');
      if (startBtn) {
        startBtn.addEventListener('click', function () {
          if (!item.uuid) return;
          if (isDisabled) return;

          if (tab === 'quizzes') {
            window.location.href = START_QUIZ_ROUTE + encodeURIComponent(item.uuid);
          } else if (tab === 'games') {
            window.location.href = START_GAME_ROUTE + encodeURIComponent(item.uuid);
          } else {
            window.location.href = START_DOOR_ROUTE + encodeURIComponent(item.uuid);
          }
        });
      }

      frag.appendChild(card);
    });

    listEl.appendChild(frag);
  }

  function updatePagination(tab, total, perPage, current, last) {
    const S = state[tab];

    if (!total || last <= 1) {
      S.els.pagination.classList.add('d-none');
      return;
    }

    S.els.pagination.classList.remove('d-none');

    S.currentPage = current;
    S.lastPage    = last;

    S.els.prev.disabled = S.currentPage <= 1;
    S.els.next.disabled = S.currentPage >= S.lastPage;

    const label = tab === 'games' ? 'game' : (tab === 'door' ? 'door game' : 'quiz');
    S.els.info.textContent =
      `Page ${S.currentPage} of ${S.lastPage} • ${total} ${label}${total > 1 ? 's' : ''}`;
  }

  // =======================
  // Fetch list per tab
  // =======================
  async function fetchList(tab, page) {
    const token = requireAuthToken();
    if (!token) return;

    const S = state[tab];
    S.els.loader.classList.add('show');
    S.els.error.classList.remove('show');
    S.els.error.textContent = '';

    const api = (tab === 'games') ? API_GAMES : (tab === 'door') ? API_DOOR : API_QUIZZES;

    const params = new URLSearchParams();
    params.set('page', page);
    params.set('per_page', 9);
    if ((S.q || '').trim() !== '') params.set('q', (S.q || '').trim());

    try {
      const res = await fetch(api + '?' + params.toString(), {
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
        return;
      }

      if (!res.ok) {
        throw new Error(json.message || json.error || 'Failed to load list.');
      }

      const items      = json.data || [];
      const pagination = json.pagination || {};

      renderCards(tab, items);
      updatePagination(
        tab,
        pagination.total || items.length,
        pagination.per_page || 9,
        pagination.current_page || page,
        pagination.last_page || 1
      );

      S.loadedOnce = true;

      // update counts
      const totalCount = String(pagination.total || items.length || 0);
      if (tab === 'quizzes') tabCountQuizzes.textContent = totalCount;
      if (tab === 'games')   tabCountGames.textContent   = totalCount;
      if (tab === 'door')    tabCountDoor.textContent    = totalCount;

      // show badges only if >0
      if (tab === 'quizzes') tabCountQuizzes.classList.toggle('d-none', (Number(totalCount) <= 0));
      if (tab === 'games')   tabCountGames.classList.toggle('d-none',   (Number(totalCount) <= 0));
      if (tab === 'door')    tabCountDoor.classList.toggle('d-none',    (Number(totalCount) <= 0));

    } catch (err) {
      console.error(err);
      S.els.error.textContent = err.message || 'Something went wrong while loading.';
      S.els.error.classList.add('show');
      renderCards(tab, []);
    } finally {
      S.els.loader.classList.remove('show');
    }
  }

  // Pagination events
  state.quizzes.els.prev.addEventListener('click', () => {
    if (state.quizzes.currentPage > 1) fetchList('quizzes', state.quizzes.currentPage - 1);
  });
  state.quizzes.els.next.addEventListener('click', () => {
    if (state.quizzes.currentPage < state.quizzes.lastPage) fetchList('quizzes', state.quizzes.currentPage + 1);
  });

  state.games.els.prev.addEventListener('click', () => {
    if (state.games.currentPage > 1) fetchList('games', state.games.currentPage - 1);
  });
  state.games.els.next.addEventListener('click', () => {
    if (state.games.currentPage < state.games.lastPage) fetchList('games', state.games.currentPage + 1);
  });

  // ✅ NEW pagination for door
  state.door.els.prev.addEventListener('click', () => {
    if (state.door.currentPage > 1) fetchList('door', state.door.currentPage - 1);
  });
  state.door.els.next.addEventListener('click', () => {
    if (state.door.currentPage < state.door.lastPage) fetchList('door', state.door.currentPage + 1);
  });

  // =======================
  // Search (debounced)
  // =======================
  let searchTimer = null;
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      if (searchTimer) clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        const val = searchInput.value || '';
        state[activeTab].q = val;
        fetchList(activeTab, 1);
      }, 350);
    });
  }

  // Initial load
  setActiveTab('quizzes');
});
</script>
@endpush
