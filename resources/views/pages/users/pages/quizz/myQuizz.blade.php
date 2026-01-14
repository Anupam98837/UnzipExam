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
          Switch tabs to view your quizzes or bubble games, and start/continue attempts.
        </div>

        <div class="qz-tabs" role="tablist" aria-label="My exams tabs">
          <button type="button" class="qz-tab-btn active" data-tab="quizzes" role="tab" aria-selected="true">
            <i class="fa-solid fa-graduation-cap"></i>
            Quizzes
            <span class="qz-tab-count" id="tabCountQuizzes">0</span>
          </button>
          <button type="button" class="qz-tab-btn" data-tab="games" role="tab" aria-selected="false">
            <i class="fa-solid fa-gamepad"></i>
            Games
            <span class="qz-tab-count" id="tabCountGames">0</span>
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

  // Attempts base (keep your existing quiz attempts URL)
  const ATTEMPTS_QUIZZES = '/api/exam/quizzes';     // /{quizKey}/my-attempts
  const ATTEMPTS_GAMES   = '/api/bubble-games';     // /{gameKey}/my-attempts  (adjust if your route differs)

  // Exam start routes
  const START_QUIZ_ROUTE = '/exam/';               // /exam/{quiz_uuid}
  const START_GAME_ROUTE = '/bubble-games/play?game='; // change if your game start URL differs

  // =======================
  // Tabs
  // =======================
  const tabBtns = document.querySelectorAll('.qz-tab-btn');
  const panels  = document.querySelectorAll('.qz-tab-panel');
  const searchInput = document.getElementById('qzSearch');

  const tabCountQuizzes = document.getElementById('tabCountQuizzes');
  const tabCountGames   = document.getElementById('tabCountGames');

  let activeTab = 'quizzes'; // quizzes | games

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

    // trigger fetch for that tab if first time
    if (tab === 'quizzes' && !state.quizzes.loadedOnce) fetchList('quizzes', 1);
    if (tab === 'games'   && !state.games.loadedOnce)   fetchList('games', 1);
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
  // UI helpers (same)
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

    const frag = document.createDocumentFragment();

    items.forEach(item => {
      const card = document.createElement('article');
      card.className = 'qz-card';

      const totalTime = item.total_time ? item.total_time + ' min' : '—';
      const totalQ    = item.total_questions || 0;
      const attempts  = item.total_attempts || 1;

      const myStatus  = item.my_status || 'upcoming';
      const hasResult = item.result && item.result.id;

      let primaryLabel = 'Start';
      if (myStatus === 'in_progress') primaryLabel = 'Continue';
      if (myStatus === 'completed')   primaryLabel = 'Retake';

      const disabledStart = (item.status !== 'active') ? 'disabled' : '';

      const iconHtml = tab === 'games'
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
          <span><i class="fa-solid fa-rotate-right"></i>${attempts} attempt${attempts > 1 ? 's' : ''} allowed</span>
        </div>

        <div class="qz-footer">
          <button type="button" class="btn btn-primary btn-sm" data-action="start" ${disabledStart}>
            <i class="fa-solid fa-arrow-right"></i>
            <span>${primaryLabel}</span>
          </button>

          ${hasResult ? `
            <button type="button" class="btn btn-outline-primary btn-sm d-none" data-action="result">
              <i class="fa-solid fa-chart-line"></i> View result
            </button>
          ` : ''}

          <span class="sub">
            Added ${item.created_at ? new Date(item.created_at).toLocaleDateString() : ''}
          </span>
        </div>
      `;

      const startBtn  = card.querySelector('[data-action="start"]');
      const resultBtn = card.querySelector('[data-action="result"]');

      if (startBtn) {
        startBtn.addEventListener('click', function () {
          if (!item.uuid) return;

          if (tab === 'quizzes') {
            window.location.href = START_QUIZ_ROUTE + encodeURIComponent(item.uuid);
          } else {
            // bubble game start URL
            window.location.href = START_GAME_ROUTE + encodeURIComponent(item.uuid);
          }
        });
      }

      if (resultBtn && hasResult) {
        resultBtn.addEventListener('click', function () {
          openAttempts(tab, item);
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

    const label = tab === 'games' ? 'game' : 'quiz';
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

    const api = (tab === 'games') ? API_GAMES : API_QUIZZES;

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

      // update counts on tabs (nice UX)
      if (tab === 'quizzes') tabCountQuizzes.textContent = String(pagination.total || items.length || 0);
      if (tab === 'games')   tabCountGames.textContent   = String(pagination.total || items.length || 0);

    } catch (err) {
      console.error(err);
      S.els.error.textContent = err.message || 'Something went wrong while loading.';
      S.els.error.classList.add('show');
      renderCards(tab, []);
    } finally {
      S.els.loader.classList.remove('show');
    }
  }

  // Pagination events per tab
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

  // ======================
  // Attempts modal (shared)
  // ======================
  const attemptsModalEl   = document.getElementById('qzAttemptModal');
  const attemptsEyebrowEl = document.getElementById('qzAttemptEyebrow');
  const attemptsTitleEl   = document.getElementById('qzAttemptTitle');
  const attemptsMetaEl    = document.getElementById('qzAttemptMeta');
  const attemptsTbodyEl   = document.getElementById('qzAttemptTbody');
  const attemptsEmptyEl   = document.getElementById('qzAttemptEmpty');
  const attemptsLoaderEl  = document.getElementById('qzAttemptLoader');
  const attemptsErrorEl   = document.getElementById('qzAttemptError');
  const attemptsCloseBtns = attemptsModalEl
    ? attemptsModalEl.querySelectorAll('[data-close="attempt-modal"]')
    : [];

  function openAttemptsModal() {
    if (!attemptsModalEl) return;
    attemptsModalEl.classList.add('show');
    document.body.classList.add('modal-open');
  }
  function closeAttemptsModal() {
    if (!attemptsModalEl) return;
    attemptsModalEl.classList.remove('show');
    document.body.classList.remove('modal-open');
  }

  attemptsCloseBtns.forEach(btn => btn.addEventListener('click', closeAttemptsModal));
  if (attemptsModalEl) {
    attemptsModalEl.addEventListener('click', function (e) {
      if (e.target && e.target.getAttribute('data-close') === 'attempt-modal') closeAttemptsModal();
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && attemptsModalEl && attemptsModalEl.classList.contains('show')) closeAttemptsModal();
  });

  function formatDateTime(value) {
    if (!value) return '—';
    const safe = typeof value === 'string' ? value.replace(' ', 'T') : value;
    const d = new Date(safe);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleString();
  }

  function attemptStatusBadge(status) {
    const s = (status || '').toLowerCase();
    if (s === 'in_progress' || s === 'started') {
      return '<span class="qz-chip qz-chip-primary"><i class="fa-solid fa-play"></i>In progress</span>';
    }
    if (s === 'submitted' || s === 'finished' || s === 'completed' || s === 'graded') {
      return '<span class="qz-chip qz-chip-success"><i class="fa-solid fa-circle-check"></i>Submitted</span>';
    }
    if (s === 'auto_submitted') {
      return '<span class="qz-chip qz-chip-warn"><i class="fa-solid fa-bolt"></i>Auto submitted</span>';
    }
    if (s === 'abandoned') {
      return '<span class="qz-chip"><i class="fa-regular fa-circle-xmark"></i>Abandoned</span>';
    }
    return '<span class="qz-chip"><i class="fa-regular fa-circle-question"></i>' + sanitize(status || 'Unknown') + '</span>';
  }

  function renderAttemptsList(attempts) {
    attemptsTbodyEl.innerHTML = '';

    if (!attempts || !attempts.length) {
      attemptsEmptyEl.style.display = 'block';
      return;
    }

    attemptsEmptyEl.style.display = 'none';

    attempts.forEach(function (a, index) {
      const tr = document.createElement('tr');

      const tdIdx     = document.createElement('td');
      const tdDate    = document.createElement('td');
      const tdStatus  = document.createElement('td');
      const tdScore   = document.createElement('td');
      const tdAction  = document.createElement('td');

      tdIdx.className    = 'qz-attempt-col-idx';
      tdScore.className  = 'qz-attempt-col-score';
      tdAction.className = 'qz-attempt-col-action';

      tdIdx.textContent  = index + 1;
      tdDate.textContent = formatDateTime(a.started_at || a.created_at);

      tdStatus.innerHTML = attemptStatusBadge(a.status);

      // Score UI: keep same logic (if your bubble attempts API returns result.total_marks etc.)
      let scoreText = '—';
      if (a.result && a.result.total_marks) {
        const obtained = a.result.marks_obtained || 0;
        const total    = a.result.total_marks || 0;
        let pct        = a.result.percentage;
        if (pct === null || pct === undefined) {
          pct = total ? (obtained * 100 / total) : 0;
        }
        scoreText = obtained + '/' + total + ' (' + pct.toFixed(2) + '%)';
      }
      tdScore.textContent = scoreText;

      // Action: keep same view result behavior for quizzes.
      // For games: you can later point to your bubble result view route.
      if (a.result && a.result.result_id && a.result.can_view_detail) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-primary btn-sm';
        btn.innerHTML = '<i class="fa-solid fa-chart-line"></i> View result';
        btn.addEventListener('click', function () {
          window.open('/exam/results/' + encodeURIComponent(a.result.result_id) + '/view', '_blank');
        });
        tdAction.appendChild(btn);
      } else if (a.result && a.result.result_id && !a.result.can_view_detail) {
        const span = document.createElement('span');
        span.className = 'qz-attempt-note';
        span.textContent = 'Not published yet';
        tdAction.appendChild(span);
      } else if ((a.status || '').toLowerCase() === 'in_progress') {
        const span = document.createElement('span');
        span.className = 'qz-attempt-note';
        span.textContent = 'Ongoing';
        tdAction.appendChild(span);
      } else {
        const span = document.createElement('span');
        span.className = 'qz-attempt-note';
        span.textContent = 'No result';
        tdAction.appendChild(span);
      }

      tr.appendChild(tdIdx);
      tr.appendChild(tdDate);
      tr.appendChild(tdStatus);
      tr.appendChild(tdScore);
      tr.appendChild(tdAction);

      attemptsTbodyEl.appendChild(tr);
    });
  }

  async function openAttempts(tab, item) {
    const token = requireAuthToken();
    if (!token || !attemptsModalEl) return;

    attemptsEyebrowEl.textContent = tab === 'games' ? 'Game Attempts' : 'Quiz Attempts';
    attemptsTitleEl.textContent   = sanitize(item.title || (tab === 'games' ? 'Game' : 'Quiz'));
    attemptsMetaEl.innerHTML      = '<span>Loading details…</span>';

    attemptsErrorEl.textContent = '';
    attemptsErrorEl.classList.remove('show');
    attemptsTbodyEl.innerHTML = '';
    attemptsEmptyEl.style.display = 'none';

    attemptsLoaderEl.classList.add('show');
    openAttemptsModal();

    const key = item.uuid || item.id;
    if (!key) {
      attemptsLoaderEl.classList.remove('show');
      attemptsErrorEl.textContent = 'Identifier missing.';
      attemptsErrorEl.classList.add('show');
      return;
    }

    const base = (tab === 'games') ? ATTEMPTS_GAMES : ATTEMPTS_QUIZZES;

    try {
      const res = await fetch(base + '/' + encodeURIComponent(key) + '/my-attempts', {
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

      if (!res.ok || json.success === false) {
        throw new Error(json.message || json.error || 'Failed to load attempts.');
      }

      const meta = json.quiz || json.game || json.meta || {};
      const attempts = json.attempts || [];

      const pieces = [];
      if (meta.total_marks !== undefined && meta.total_marks !== null) pieces.push('Total marks: ' + meta.total_marks);
      if (meta.total_attempts_allowed !== undefined && meta.total_attempts_allowed !== null) pieces.push('Attempts allowed: ' + meta.total_attempts_allowed);
      if (meta.total_time !== undefined && meta.total_time !== null) pieces.push('Time: ' + meta.total_time + ' min');

      attemptsMetaEl.innerHTML = pieces.length
        ? pieces.map(p => '<span>' + sanitize(p) + '</span>').join(' ')
        : '';

      renderAttemptsList(attempts);

    } catch (err) {
      console.error(err);
      attemptsErrorEl.textContent = err.message || 'Something went wrong while loading attempts.';
      attemptsErrorEl.classList.add('show');
      attemptsTbodyEl.innerHTML = '';
      attemptsEmptyEl.style.display = 'none';
    } finally {
      attemptsLoaderEl.classList.remove('show');
    }
  }

  // =======================
  // Search (debounced, per active tab)
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

  // Initial load (Quizzes tab)
  setActiveTab('quizzes');
});
</script>
@endpush
