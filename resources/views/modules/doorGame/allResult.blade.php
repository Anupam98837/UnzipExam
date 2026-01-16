{{-- resources/views/modules/door_game/manageDoorGameResults.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Door Game Results')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ===== Shell ===== */
.qr-wrap{max-width:1140px;margin:16px auto 40px;overflow:visible}
.panel{background:var(--surface);border:1px solid var(--line-strong);border-radius:16px;box-shadow:var(--shadow-2);padding:14px}

/* Toolbar */
.mfa-toolbar .form-control{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .form-select{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .btn{height:40px;border-radius:12px}
.mfa-toolbar .btn-light{background:var(--surface);border:1px solid var(--line-strong)}
.mfa-toolbar .btn-primary{background:var(--primary-color);border:none}

/* Tabs */
.nav.nav-tabs{border-color:var(--line-strong)}
.nav-tabs .nav-link{color:var(--ink)}
.nav-tabs .nav-link.active{background:var(--surface);border-color:var(--line-strong) var(--line-strong) var(--surface)}
.tab-content,.tab-pane{overflow:visible}

/* Table Card */
.table-wrap.card{position:relative;border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:visible}
.table-wrap .card-body{overflow:visible}
.table-responsive{overflow:visible !important}
.table{--bs-table-bg:transparent}
.table thead th{font-weight:600;color:var(--muted-color);font-size:13px;border-bottom:1px solid var(--line-strong);background:var(--surface)}
.table thead.sticky-top{z-index:3}
.table tbody tr{border-top:1px solid var(--line-soft)}
.table tbody tr:hover{background:var(--page-hover)}
td .fw-semibold{color:var(--ink)}
.small{font-size:12.5px}

/* Badges */
.badge-soft{background:color-mix(in oklab, var(--muted-color) 12%, transparent);color:var(--ink)}
.badge-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .55rem;border-radius:999px;border:1px solid var(--line-strong);background:color-mix(in oklab, var(--muted-color) 10%, transparent)}
.badge-success{background:var(--success-color)!important;color:#fff!important;border:none!important}
.badge-warning{background:var(--warning-color)!important;color:#0b1324!important;border:none!important}
.badge-danger{background:var(--danger-color)!important;color:#fff!important;border:none!important}
.badge-secondary{background:#64748b!important;color:#fff!important;border:none!important}

/* Sorting */
.sortable{cursor:pointer;white-space:nowrap}
.sortable .caret{display:inline-block;margin-left:.35rem;opacity:.65}
.sortable.asc .caret::after{content:"▲";font-size:.7rem}
.sortable.desc .caret::after{content:"▼";font-size:.7rem}

/* Dropdowns inside table */
/* .table-wrap .dropdown{position:relative;z-index:6}
.table-wrap .dd-toggle{position:relative;z-index:7} */
/* .dropdown [data-bs-toggle="dropdown"]{border-radius:10px} */
/* .table-wrap .dropdown-menu{border-radius:12px;border:1px solid var(--line-strong);box-shadow:var(--shadow-2);min-width:220px;z-index:5000} */
/* .dropdown-menu.dd-portal{position:fixed!important;left:0;top:0;transform:none!important;z-index:5000;border-radius:12px;border:1px solid var(--line-strong);box-shadow:var(--shadow-2);min-width:220px;background:var(--surface)} */
.dropdown-item{display:flex;align-items:center;gap:.6rem}
.dropdown-item i{width:16px;text-align:center}
.dropdown-item.text-danger{color:var(--danger-color)!important}

/* Action icon style */
.icon-btn{display:inline-flex;align-items:center;justify-content:center;height:34px;min-width:34px;padding:0 10px;border:1px solid var(--line-strong);background:var(--surface);border-radius:10px}
.icon-btn:hover{box-shadow:var(--shadow-1)}

/* Empty & loader */
.empty{color:var(--muted-color)}
.placeholder{background:linear-gradient(90deg,#00000010,#00000005,#00000010);border-radius:8px}

/* Modals */
.modal-content{border-radius:16px;border:1px solid var(--line-strong);background:var(--surface)}
.modal-header{border-bottom:1px solid var(--line-strong)}
.modal-footer{border-top:1px solid var(--line-strong)}
.form-control,.form-select{border-radius:12px;border:1px solid var(--line-strong);background:#fff}
html.theme-dark .form-control,html.theme-dark .form-select{background:#0f172a;color:#e5e7eb;border-color:var(--line-strong)}

/* Dark tweaks */
html.theme-dark .panel,
html.theme-dark .table-wrap.card,
html.theme-dark .modal-content{background:#0f172a;border-color:var(--line-strong)}
html.theme-dark .table thead th{background:#0f172a;border-color:var(--line-strong);color:#94a3b8}
html.theme-dark .table tbody tr{border-color:var(--line-soft)}
html.theme-dark .dropdown-menu{background:#0f172a;border-color:var(--line-strong)}
</style>
@endpush

@section('content')
<div class="qr-wrap">

  {{-- ================= Tabs ================= --}}
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#tab-results" role="tab" aria-selected="true">
        <i class="fa-solid fa-square-poll-vertical me-2"></i>Results
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#tab-published" role="tab" aria-selected="false">
        <i class="fa-solid fa-eye me-2"></i>Published to Students
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#tab-unpublished" role="tab" aria-selected="false">
        <i class="fa-solid fa-eye-slash me-2"></i>Not Published
      </a>
    </li>
  </ul>

  <div class="tab-content mb-3">

    {{-- ========== TAB: Results ========== --}}
    <div class="tab-pane fade show active" id="tab-results" role="tabpanel">
      {{-- Toolbar --}}
      <div class="row align-items-center g-2 mb-3 mfa-toolbar panel">
        <div class="col-12 col-xl d-flex align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <label class="text-muted small mb-0">Per page</label>
            <select id="per_page" class="form-select" style="width:96px;">
              <option>10</option>
              <option selected>20</option>
              <option>30</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>

          <div class="position-relative" style="min-width:320px;">
            <input id="q" type="text" class="form-control ps-5" placeholder="Search student / email / game…">
            <i class="fa fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);opacity:.6;"></i>
          </div>

          <button id="btnFilter" class="btn btn-primary ms-1" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="fa fa-filter me-1"></i>Filter
          </button>
          <button id="btnReset" class="btn btn-primary">
            <i class="fa fa-rotate-left me-1"></i>Reset
          </button>
        </div>
      </div>

      {{-- Table --}}
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th class="sortable" data-col="student_name">STUDENT <span class="caret"></span></th>
                  <th class="sortable" data-col="game_title">GAME <span class="caret"></span></th>
                  <th style="width:110px;">ATTEMPT</th>
                  <th class="sortable" data-col="score" style="width:140px;">SCORE <span class="caret"></span></th>
                  <th class="sortable" data-col="accuracy" style="width:120px;">% <span class="caret"></span></th>
                  <th style="width:150px;">STATUS</th>
                  <th class="sortable" data-col="result_created_at" style="width:170px;">SUBMITTED <span class="caret"></span></th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-results">
                <tr id="loaderRow-results" style="display:none;">
                  <td colspan="8" class="p-0">
                    <div class="p-4">
                      <div class="placeholder-wave">
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div id="empty-results" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-circle-info mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No results found.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-results">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-results" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

    {{-- ========== TAB: Published ========== --}}
    <div class="tab-pane fade" id="tab-published" role="tabpanel">
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th>STUDENT</th>
                  <th>GAME</th>
                  <th style="width:110px;">ATTEMPT</th>
                  <th style="width:140px;">SCORE</th>
                  <th style="width:120px;">%</th>
                  <th style="width:170px;">SUBMITTED</th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-published">
                <tr id="loaderRow-published" style="display:none;">
                  <td colspan="7" class="p-0">
                    <div class="p-4">
                      <div class="placeholder-wave">
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div id="empty-published" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-eye mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No published results.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-published">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-published" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

    {{-- ========== TAB: Unpublished ========== --}}
    <div class="tab-pane fade" id="tab-unpublished" role="tabpanel">
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th>STUDENT</th>
                  <th>GAME</th>
                  <th style="width:110px;">ATTEMPT</th>
                  <th style="width:140px;">SCORE</th>
                  <th style="width:120px;">%</th>
                  <th style="width:170px;">SUBMITTED</th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-unpublished">
                <tr id="loaderRow-unpublished" style="display:none;">
                  <td colspan="7" class="p-0">
                    <div class="p-4">
                      <div class="placeholder-wave">
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                        <div class="placeholder col-12 mb-2" style="height:18px;"></div>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div id="empty-unpublished" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-eye-slash mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No unpublished results.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-unpublished">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-unpublished" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /.tab-content -->

</div>

{{-- Filter Modal --}}
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-filter me-2"></i>Filter Results</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Door Game (Select)</label>
            <select id="fGameId" class="form-select">
              <option value="">All games</option>
              {{-- Games will be loaded dynamically --}}
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Attempt status</label>
            <select id="fAttemptStatus" class="form-select">
              <option value="">All</option>
              <option value="submitted">Submitted</option>
              <option value="auto_submitted">Auto submitted</option>
              <option value="in_progress">In progress</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Published to student</label>
            <select id="fPublish" class="form-select">
              <option value="">All</option>
              <option value="1">Published</option>
              <option value="0">Not published</option>
            </select>
          </div>

          <div class="col-6">
            <label class="form-label">Min %</label>
            <input id="fMinPct" type="number" class="form-control" placeholder="0" min="0" max="100">
          </div>
          <div class="col-6">
            <label class="form-label">Max %</label>
            <input id="fMaxPct" type="number" class="form-control" placeholder="100" min="0" max="100">
          </div>

          <div class="col-6">
            <label class="form-label">From (YYYY-MM-DD)</label>
            <input id="fFrom" type="date" class="form-control">
          </div>
          <div class="col-6">
            <label class="form-label">To (YYYY-MM-DD)</label>
            <input id="fTo" type="date" class="form-control">
          </div>

          <div class="col-12">
            <label class="form-label">Game UUID (optional)</label>
            <input id="fGameUuid" type="text" class="form-control" placeholder="e.g. 6d2f...">
          </div>

          <div class="col-12">
            <label class="form-label">Student email (optional)</label>
            <input id="fStudentEmail" type="text" class="form-control" placeholder="student@example.com">
          </div>

        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button id="btnApplyFilters" type="button" class="btn btn-primary">
          <i class="fa fa-check me-1"></i>Apply Filters
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2100">
  <div id="okToast" class="toast text-bg-success border-0">
    <div class="d-flex">
      <div id="okMsg" class="toast-body">Done</div>
      <button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="errToast" class="toast text-bg-danger border-0 mt-2">
    <div class="d-flex">
      <div id="errMsg" class="toast-body">Something went wrong</div>
      <button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ===== Force dropdown overflows to body (portal) ===== */
(function(){
  let activePortal = null;
  const placeMenu = (menu, btnRect) => {
    const vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    const spaceRight = vw - btnRect.right;

    menu.classList.add('dd-portal');
    menu.style.display = 'block';
    menu.style.visibility = 'hidden';
    document.body.appendChild(menu);

    const mw = menu.offsetWidth, mh = menu.offsetHeight;
    let left = btnRect.left;
    if (spaceRight < mw && btnRect.right - mw > 8) left = btnRect.right - mw;

    let top = btnRect.bottom + 4;
    const vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
    if (top + mh > vh - 8) top = Math.max(8, vh - mh - 8);

    menu.style.left = left + 'px';
    menu.style.top  = top + 'px';
    menu.style.visibility = 'visible';
  };

  document.addEventListener('show.bs.dropdown', function(ev){
    const toggle = ev.target;
    const btn  = toggle.querySelector('.dd-toggle, [data-bs-toggle="dropdown"]');
    const menu = toggle.querySelector('.dropdown-menu');
    if (!btn || !menu) return;

    if (activePortal && activePortal.menu && activePortal.menu.isConnected) {
      activePortal.menu.classList.remove('dd-portal');
      activePortal.parent.appendChild(activePortal.menu);
      activePortal = null;
    }

    const rect = btn.getBoundingClientRect();
    menu.__ddParent = menu.parentElement;
    placeMenu(menu, rect);
    activePortal = { menu: menu, parent: menu.__ddParent };

    const closeOnEnv = () => { try { bootstrap.Dropdown.getOrCreateInstance(btn).hide(); } catch {} };
    menu.__ddListeners = [
      ['scroll', closeOnEnv, true],
      ['resize', closeOnEnv, false]
    ];
    window.addEventListener('resize', closeOnEnv);
    document.addEventListener('scroll', closeOnEnv, true);
  });

  document.addEventListener('hidden.bs.dropdown', function(ev){
    const toggle = ev.target;
    const menu = toggle.querySelector('.dropdown-menu.dd-portal') || activePortal?.menu;
    if (!menu) return;

    if (menu.__ddListeners) {
      document.removeEventListener('scroll', menu.__ddListeners[0][1], true);
      window.removeEventListener('resize', menu.__ddListeners[1][1]);
      menu.__ddListeners = null;
    }

    if (menu.__ddParent) {
      menu.classList.remove('dd-portal');
      menu.style.cssText = '';
      menu.__ddParent.appendChild(menu);
      activePortal = null;
    }
  });
})();

/* ================= Dropdown toggle handler ================= */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.dd-toggle');
  if (!btn) return;
  e.preventDefault(); e.stopPropagation();
  const inst = bootstrap.Dropdown.getOrCreateInstance(btn, { autoClose:'outside', boundary:'viewport' });
  inst.toggle();
});

(function(){
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  if (!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  // ✅ CHANGE THESE IF YOUR ROUTES DIFFER
  const DOOR_RESULTS_API_BASE = '/api/door-game-results/all';  // listing api
  const DOOR_GAMES_LIST_API   = '/api/door-games?per_page=100&status=active'; // filter dropdown list

  /* ========= Toasts ========= */
  const okToast  = new bootstrap.Toast(document.getElementById('okToast'));
  const errToast = new bootstrap.Toast(document.getElementById('errToast'));
  const ok  = (m)=>{ document.getElementById('okMsg').textContent  = m||'Done'; okToast.show(); };
  const err = (m)=>{ document.getElementById('errMsg').textContent = m||'Something went wrong'; errToast.show(); };

  /* ========= DOM ========= */
  const q = document.getElementById('q');
  const perPageSel = document.getElementById('per_page');
  const btnReset = document.getElementById('btnReset');
  const btnApplyFilters = document.getElementById('btnApplyFilters');

  const fGameId = document.getElementById('fGameId');
  const fAttemptStatus = document.getElementById('fAttemptStatus');
  const fPublish = document.getElementById('fPublish');
  const fMinPct = document.getElementById('fMinPct');
  const fMaxPct = document.getElementById('fMaxPct');
  const fFrom = document.getElementById('fFrom');
  const fTo = document.getElementById('fTo');
  const fGameUuid = document.getElementById('fGameUuid');
  const fStudentEmail = document.getElementById('fStudentEmail');

  const tabs = {
    results:     { rows:'#rows-results',     loader:'#loaderRow-results',     empty:'#empty-results',     meta:'#metaTxt-results',     pager:'#pager-results',     extra:{} },
    published:   { rows:'#rows-published',   loader:'#loaderRow-published',   empty:'#empty-published',   meta:'#metaTxt-published',   pager:'#pager-published',   extra:{ publish_to_student:'1' } },
    unpublished: { rows:'#rows-unpublished', loader:'#loaderRow-unpublished', empty:'#empty-unpublished', meta:'#metaTxt-unpublished', pager:'#pager-unpublished', extra:{ publish_to_student:'0' } },
  };

  const qs=(sel)=>document.querySelector(sel);
  const qsa=(sel)=>document.querySelectorAll(sel);
function esc(s){
  if (s === null || s === undefined) return '';
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
  const fmtDate=(iso)=>{ if(!iso) return '-'; const d=new Date(iso); if(isNaN(d)) return esc(iso); return d.toLocaleString(undefined,{year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit'}); };
  const showLoader=(scope, v)=>{ qs(tabs[scope].loader).style.display = v ? '' : 'none'; };
  const fmtPct = (n)=> (n==null || n==='') ? '—' : (Number(n).toFixed(2) + '%');

  // ✅ SAFETY: normalize list arrays from different API shapes
  function pickArray(json){
    if (Array.isArray(json?.data)) return json.data;
    if (Array.isArray(json?.items)) return json.items;
    if (Array.isArray(json?.data?.data)) return json.data.data;      // Laravel paginator
    if (Array.isArray(json?.data?.items)) return json.data.items;
    return [];
  }

  // ✅ SAFETY: normalize pagination from different API shapes
  function pickPagination(json, itemsLen, scope){
  const p = json?.pagination || json?.data?.pagination;

  if (p) {
    const total   = Number(p.total ?? itemsLen);
    const perPage = Number(p.per_page ?? perPageSel?.value ?? 20) || 20;
    const page    = Number(p.page ?? state?.[scope]?.page ?? 1) || 1;

    const totalPages =
      Number(p.total_pages) ||
      Math.max(1, Math.ceil((total || 0) / perPage));

    return {
      total,
      per_page: perPage,
      page,
      total_pages: totalPages,
    };
  }

  // Laravel paginator fields
  const total = Number(json?.total ?? json?.data?.total ?? itemsLen) || 0;
  const per   = Number(json?.per_page ?? json?.data?.per_page ?? perPageSel?.value ?? 20) || 20;
  const page  = Number(json?.current_page ?? json?.data?.current_page ?? state?.[scope]?.page ?? 1) || 1;
  const last  = Number(json?.last_page ?? json?.data?.last_page) || Math.max(1, Math.ceil(total / per));

  return { total, per_page: per, page, total_pages: last };
}


  function statusBadge(s){
    const v = String(s||'').toLowerCase();
    if (v==='submitted') return `<span class="badge badge-success text-uppercase">submitted</span>`;
    if (v==='auto_submitted') return `<span class="badge badge-warning text-uppercase">auto</span>`;
    if (v==='in_progress') return `<span class="badge badge-secondary text-uppercase">in progress</span>`;
    return `<span class="badge badge-secondary text-uppercase">${esc(s||'-')}</span>`;
  }

  function actionMenu(r){
    const rid = r?.result?.uuid ?? '';
    const viewUrl = rid ? `/decision-making-test/results/${encodeURIComponent(rid)}/view` : '#';
    return `
      <div class="dropdown text-end" data-bs-display="static">
        <button type="button" class="btn btn-light btn-sm dd-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Actions">
          <i class="fa fa-ellipsis-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="${viewUrl}">
              <i class="fa fa-eye"></i> View Result
            </a>
          </li>
          <li>
            <button class="dropdown-item" data-act="copy" data-id="${esc(rid)}">
              <i class="fa fa-copy"></i> Copy Result UUID
            </button>
          </li>
        </ul>
      </div>
    `;
  }
function rowHTML(scope, item){
  const r = item || {};
  const student = r.student || {};
  const game = r.game || r.door_game || r.doorGame || {};
  const attempt = r.attempt || {};
  const result = r.result || {};

  const tr = document.createElement('tr');

  const tdStudent = document.createElement('td');
  tdStudent.innerHTML =
    `<div class="fw-semibold"></div><div class="text-muted small"></div>`;
  tdStudent.querySelector('.fw-semibold').textContent = student.name || '-';
  tdStudent.querySelector('.text-muted.small').textContent = student.email || '-';

  const tdGame = document.createElement('td');
  tdGame.innerHTML = `<div class="fw-semibold"></div>`;
  tdGame.querySelector('.fw-semibold').textContent =
    (game.title || game.game_title || game.name || '-');

  const tdAttempt = document.createElement('td');
  tdAttempt.innerHTML = `<span class="badge-pill"><i class="fa fa-repeat"></i> <span class="att-no"></span></span>`;
  tdAttempt.querySelector('.att-no').textContent =
    `#${Number(result.attempt_no || result.attempt_number || 0)}`;

  const tdScore = document.createElement('td');
  tdScore.innerHTML = `<div class="fw-semibold"></div><div class="text-muted small">—</div>`;
  tdScore.querySelector('.fw-semibold').textContent = String(Number(result.score || 0));

  const tdPct = document.createElement('td');
  const pct = (result.accuracy ?? result.percentage);
  tdPct.innerHTML = `<div class="fw-semibold"></div><div class="text-muted small"></div>`;
  tdPct.querySelector('.fw-semibold').textContent =
    (pct == null || pct === '') ? '—' : (Number(pct).toFixed(2) + '%');
  tdPct.querySelector('.text-muted.small').textContent =
    (Number(result.publish_to_student || 0) === 1) ? 'Published' : 'Not published';

  const tdStatus = document.createElement('td');
  tdStatus.innerHTML = statusBadge(attempt.status);

  const tdSubmitted = document.createElement('td');
  tdSubmitted.textContent = fmtDate(result.created_at || result.result_created_at);

  const tdActions = document.createElement('td');
  tdActions.className = 'text-end';
  tdActions.innerHTML = actionMenu(r);

  tr.appendChild(tdStudent);
  tr.appendChild(tdGame);
  tr.appendChild(tdAttempt);
  tr.appendChild(tdScore);
  tr.appendChild(tdPct);
  tr.appendChild(tdStatus);
  tr.appendChild(tdSubmitted);
  tr.appendChild(tdActions);

  return tr;
}

  /* ========= State ========= */
  let sort = '-result_created_at';
  const state = { results:{page:1}, published:{page:1}, unpublished:{page:1} };

  /* ========= Load games for dropdown ========= */
  async function loadGamesForFilter() {
    try {
      const res = await fetch(DOOR_GAMES_LIST_API, {
        headers: { 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json?.message || 'Failed to load games');

      const games = pickArray(json);
      const gameSelect = fGameId;

      while (gameSelect.options.length > 1) gameSelect.remove(1);

      games.forEach(g => {
        const option = document.createElement('option');
        option.value = g.id || g.uuid || '';
        option.textContent = (g.title || g.game_title || g.name || 'Unnamed Game');
        gameSelect.appendChild(option);
      });
    } catch(e) {
      console.error('Failed to load door games:', e);
    }
  }

  function buildParams(scope){
    const usp = new URLSearchParams();
    usp.set('page', state[scope].page || 1);
    usp.set('per_page', Number(perPageSel?.value || 20));
    usp.set('sort', sort);

    if (q && q.value.trim()) usp.set('q', q.value.trim());

    if (fGameId && fGameId.value) usp.set('door_game_id', fGameId.value);

    if (fAttemptStatus && fAttemptStatus.value) usp.set('attempt_status', fAttemptStatus.value);
    if (fPublish && fPublish.value !== '') usp.set('publish_to_student', fPublish.value);

    if (fMinPct && fMinPct.value !== '') usp.set('min_percentage', fMinPct.value);
    if (fMaxPct && fMaxPct.value !== '') usp.set('max_percentage', fMaxPct.value);

    if (fFrom && fFrom.value) usp.set('from', fFrom.value);
    if (fTo && fTo.value) usp.set('to', fTo.value);

    if (fGameUuid && fGameUuid.value.trim()) usp.set('game_uuid', fGameUuid.value.trim());
    if (fStudentEmail && fStudentEmail.value.trim()) usp.set('student_email', fStudentEmail.value.trim());

    const extra = tabs[scope].extra || {};
    Object.keys(extra).forEach(k => usp.set(k, extra[k]));

    return usp.toString();
  }

  function urlFor(scope){
    return `${DOOR_RESULTS_API_BASE}?${buildParams(scope)}`;
  }

  async function load(scope){
    const refs = tabs[scope];
    const rowsEl = qs(refs.rows);
    const empty  = qs(refs.empty);
    const pager  = qs(refs.pager);
    const meta   = qs(refs.meta);

    rowsEl.querySelectorAll('tr:not([id^="loaderRow"])').forEach(n=>n.remove());
    empty.style.display='none';
    pager.innerHTML = '';
    meta.textContent = '—';
    showLoader(scope, true);

    try{
      const res = await fetch(urlFor(scope), {
        headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
      });
      const json = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(json?.message || 'Load failed');

      const items = pickArray(json);
      const pagination = pickPagination(json, items.length, scope);

      if (items.length===0) empty.style.display='';

      const frag = document.createDocumentFragment();
      items.forEach(it => frag.appendChild(rowHTML(scope, it)));
      rowsEl.appendChild(frag);

      const total   = Number(pagination.total||0);
      const perPage = Number(pagination.per_page||20);
      const current = Number(pagination.page||1);
      const totalPages = Math.max(1, Number(pagination.total_pages || Math.ceil(total/perPage) || 1));

      function li(disabled, active, label, target){
        const cls=['page-item',disabled?'disabled':'',active?'active':''].filter(Boolean).join(' ');
        const href=disabled?'#':'javascript:void(0)';
        return `<li class="${cls}"><a class="page-link" href="${href}" data-page="${target||''}">${label}</a></li>`;
      }

      let html='';
      html += li(current<=1,false,'Previous',current-1);
      const w=3, start=Math.max(1,current-w), end=Math.min(totalPages,current+w);
      if (start>1){
        html += li(false,false,1,1);
        if(start>2) html+='<li class="page-item disabled"><span class="page-link">…</span></li>';
      }
      for(let p=start;p<=end;p++) html += li(false,p===current,p,p);
      if (end<totalPages){
        if(end<totalPages-1) html+='<li class="page-item disabled"><span class="page-link">…</span></li>';
        html+=li(false,false,totalPages,totalPages);
      }
      html += li(current>=totalPages,false,'Next',current+1);
      pager.innerHTML = html;

      pager.querySelectorAll('a.page-link[data-page]').forEach(a=>{
        a.addEventListener('click',()=>{
          const target=Number(a.dataset.page);
          if(!target || target===state[scope].page) return;
          state[scope].page = Math.max(1,target);
          load(scope);
          window.scrollTo({top:0,behavior:'smooth'});
        });
      });

      meta.textContent = `Showing page ${current} of ${totalPages} — ${total} result(s)`;
    }catch(e){
      console.error(e);
      empty.style.display='';
      meta.textContent='Failed to load';
      err(e.message || 'Load error');
    }finally{
      showLoader(scope, false);
    }
  }

  /* ========= Sorting (main tab) ========= */
  qsa('#tab-results thead th.sortable').forEach(th=>{
    th.addEventListener('click', ()=>{
      const col = th.dataset.col;
      if (sort === col){ sort = '-'+col; }
      else if (sort === '-'+col){ sort = col; }
      else { sort = (col === 'result_created_at') ? '-result_created_at' : col; }

      state.results.page = 1;
      load('results');

      qsa('#tab-results thead th.sortable').forEach(t=>t.classList.remove('asc','desc'));
      if (sort === col) th.classList.add('asc');
      else if (sort === '-'+col) th.classList.add('desc');
    });
  });

  /* ========= Search debounce ========= */
  let srchT;
  q?.addEventListener('input', ()=>{
    clearTimeout(srchT);
    srchT=setTimeout(()=>{
      state.results.page=1;
      load('results');
    }, 350);
  });

  btnApplyFilters?.addEventListener('click', ()=>{
    const filterModal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
    filterModal.hide();
    Object.keys(state).forEach(k => state[k].page = 1);
    load('results');
  });

  btnReset?.addEventListener('click', ()=>{
    if (q) q.value='';
    if (perPageSel) perPageSel.value='20';
    if (fGameId) fGameId.value='';
    if (fAttemptStatus) fAttemptStatus.value='';
    if (fPublish) fPublish.value='';
    if (fMinPct) fMinPct.value='';
    if (fMaxPct) fMaxPct.value='';
    if (fFrom) fFrom.value='';
    if (fTo) fTo.value='';
    if (fGameUuid) fGameUuid.value='';
    if (fStudentEmail) fStudentEmail.value='';
    sort='-result_created_at';
    Object.keys(state).forEach(k => state[k].page = 1);
    load('results');
  });

  perPageSel?.addEventListener('change', ()=>{
    Object.keys(state).forEach(k => state[k].page = 1);
    load('results');
  });

  /* ========= Tabs load on demand ========= */
  document.querySelector('a[href="#tab-results"]').addEventListener('shown.bs.tab', ()=> load('results'));
  document.querySelector('a[href="#tab-published"]').addEventListener('shown.bs.tab', ()=> load('published'));
  document.querySelector('a[href="#tab-unpublished"]').addEventListener('shown.bs.tab', ()=> load('unpublished'));

  /* ========= Copy UUID ========= */
  document.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button.dropdown-item[data-act="copy"]');
    if(!btn) return;
    const id = btn.dataset.id || '';
    if(!id) return;
    try{
      await navigator.clipboard.writeText(id);
      ok('Copied result id');
    }catch{
      ok('Copy: '+id);
    }
  });

  /* ========= Initial load ========= */
  loadGamesForFilter().then(() => load('results'));
})();
</script>
@endpush
