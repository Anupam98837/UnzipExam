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
.table-wrap.card{position:relative;border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
.table-wrap .card-body{overflow:hidden}
.table{--bs-table-bg:transparent}
.table thead th{font-weight:600;color:var(--muted-color);font-size:13px;border-bottom:1px solid var(--line-strong);background:var(--surface);white-space:nowrap}
.table thead.sticky-top{z-index:3}
.table tbody tr{border-top:1px solid var(--line-soft)}
.table tbody tr:hover{background:var(--page-hover)}
td .fw-semibold{color:var(--ink)}
td{vertical-align:middle;white-space:nowrap}
.small{font-size:12.5px}

/* ✅ Responsive scroll wrappers */
.table-scrollwrap{position:relative}
.table-responsive{overflow-x:auto !important; overflow-y:visible !important}

/* ✅ Bottom X scrollbar (synced) */
.x-scrollbar{
  height:14px;
  overflow-x:auto;
  overflow-y:hidden;
  border-top:1px solid var(--line-strong);
  background:color-mix(in oklab, var(--muted-color) 6%, transparent);
}
.x-scrollbar.hidden{display:none}
.x-scrollbar-inner{height:1px}

/* Bulk */
.bulk-col{width:46px}
.bulk-check{width:18px;height:18px;cursor:pointer}
tr.row-selected{background:color-mix(in oklab, var(--accent-color) 10%, transparent)}

/* Badges */
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

/* Dropdowns */
.dropdown-item{display:flex;align-items:center;gap:.6rem}
.dropdown-item i{width:16px;text-align:center}
.dropdown-item.text-danger{color:var(--danger-color)!important}

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
/* ✅ Hide native horizontal scrollbar of table area (keep scroll working) */
.table-responsive{
  overflow-x:auto !important;
  scrollbar-width:none;          /* Firefox */
  -ms-overflow-style:none;       /* IE/Edge old */
}
.table-responsive::-webkit-scrollbar{
  height:0px;                    /* Chrome/Safari */
}
.table-responsive::-webkit-scrollbar-thumb{
  background:transparent;
}

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
          <button id="btnExport" class="btn btn-light">
  <i class="fa fa-file-arrow-down me-1"></i>Export
</button>

          {{-- ✅ Bulk publish --}}
          <button id="btnBulk" class="btn btn-primary">
            <i class="fa fa-bolt me-1"></i>Bulk Publish
          </button>

          <button id="btnBulkExit" class="btn btn-light" style="display:none;">
            <i class="fa fa-xmark me-1"></i>Exit Bulk
          </button>

          <span id="bulkHint" class="small text-muted" style="display:none;">
            <i class="fa fa-circle-info me-1"></i>
            Select students then Publish/Unpublish
          </span>
        </div>
      </div>

      {{-- Table --}}
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-scrollwrap">
            <div class="table-responsive" id="tr-results">
              <table class="table table-hover table-borderless align-middle mb-0" id="tbl-results">
                <thead class="sticky-top">
                  <tr>
                    <th id="thBulk" class="bulk-col" style="display:none;">
                      <input id="bulkAll" class="form-check-input bulk-check" type="checkbox" title="Select all">
                    </th>

                    <th class="sortable" data-col="student_name">STUDENT <span class="caret"></span></th>
                    <th class="sortable" data-col="game_title">GAME <span class="caret"></span></th>
                    <th style="width:110px;">ATTEMPT</th>
                    <th class="sortable" data-col="score" style="width:120px;">SCORE <span class="caret"></span></th>
                    <th class="sortable" data-col="accuracy" style="width:110px;">% <span class="caret"></span></th>
                    <th style="width:150px;">STATUS</th>

                    {{-- ✅ NEW --}}
                    <th style="width:150px;">PUBLISH</th>
                    <th style="width:200px;">FOLDER</th>

                    <th class="sortable" data-col="result_created_at" style="width:170px;">SUBMITTED <span class="caret"></span></th>
                    <th class="text-end" style="width:112px;">ACTIONS</th>
                  </tr>
                </thead>
                <tbody id="rows-results">
                  <tr id="loaderRow-results" style="display:none;">
                    <td id="loaderCol-results" colspan="10" class="p-0">
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

            {{-- ✅ bottom x-scroll --}}
            <div class="x-scrollbar" id="xs-results"><div class="x-scrollbar-inner"></div></div>
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
          <div class="table-scrollwrap">
            <div class="table-responsive" id="tr-published">
              <table class="table table-hover table-borderless align-middle mb-0" id="tbl-published">
                <thead class="sticky-top">
                  <tr>
                    <th>STUDENT</th>
                    <th>GAME</th>
                    <th style="width:110px;">ATTEMPT</th>
                    <th style="width:120px;">SCORE</th>
                    <th style="width:110px;">%</th>

                    {{-- ✅ NEW --}}
                    <th style="width:150px;">PUBLISH</th>
                    <th style="width:200px;">FOLDER</th>

                    <th style="width:170px;">SUBMITTED</th>
                    <th class="text-end" style="width:112px;">ACTIONS</th>
                  </tr>
                </thead>
                <tbody id="rows-published">
                  <tr id="loaderRow-published" style="display:none;">
                    <td colspan="9" class="p-0">
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

            <div class="x-scrollbar" id="xs-published"><div class="x-scrollbar-inner"></div></div>
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
          <div class="table-scrollwrap">
            <div class="table-responsive" id="tr-unpublished">
              <table class="table table-hover table-borderless align-middle mb-0" id="tbl-unpublished">
                <thead class="sticky-top">
                  <tr>
                    <th>STUDENT</th>
                    <th>GAME</th>
                    <th style="width:110px;">ATTEMPT</th>
                    <th style="width:120px;">SCORE</th>
                    <th style="width:110px;">%</th>

                    {{-- ✅ NEW --}}
                    <th style="width:150px;">PUBLISH</th>
                    <th style="width:200px;">FOLDER</th>

                    <th style="width:170px;">SUBMITTED</th>
                    <th class="text-end" style="width:112px;">ACTIONS</th>
                  </tr>
                </thead>
                <tbody id="rows-unpublished">
                  <tr id="loaderRow-unpublished" style="display:none;">
                    <td colspan="9" class="p-0">
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

            <div class="x-scrollbar d-none" style="display:none" id="xs-unpublished"><div class="x-scrollbar-inner d-none"></div></div>
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
        <h5 class="modal-title" id="filterTitle"><i class="fa fa-filter me-2"></i>Filter Results</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Door Game (Select)</label>
            <select id="fGameId" class="form-select">
              <option value="">All games</option>
            </select>
          </div>

          {{-- ✅ NEW: Folder filter (works for both normal & bulk) --}}
          <div class="col-12">
            <label class="form-label">Folder</label>
            <select id="fFolderId" class="form-select">
              <option value="">All folders</option>
              {{-- Folders will be loaded dynamically --}}
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Attempt status</label>
            <select id="fAttemptStatus" class="form-select">
              <option value="">All</option>
              <option value="win">Win</option>
              <option value="fail">Fail</option>
              <option value="timeout">Timeout</option>
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

          <div class="col-12 ">
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
          <i class="fa fa-check me-1"></i><span id="applyTxt">Apply Filters</span>
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
    window.addEventListener('resize', closeOnEnv);
    document.addEventListener('scroll', closeOnEnv, true);
  });

  document.addEventListener('hidden.bs.dropdown', function(ev){
    const toggle = ev.target;
    const menu = toggle.querySelector('.dropdown-menu.dd-portal') || activePortal?.menu;
    if (!menu) return;

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
  const DOOR_RESULTS_LIST_API   = '/api/door-game-results/all';
  const DOOR_RESULTS_ACTION_API = '/api/door-game-results';
  const DOOR_GAMES_LIST_API     = '/api/door-games?per_page=100&status=active';
  const DOOR_RESULTS_EXPORT_API = '/api/door-game/result/export';

  // ✅ Folder list API (adjust if needed)
  const DOOR_FOLDERS_LIST_API   = '/api/user-folders';

  /* ========= Toasts ========= */
  const okToast  = new bootstrap.Toast(document.getElementById('okToast'));
  const errToast = new bootstrap.Toast(document.getElementById('errToast'));
  const ok  = (m)=>{ document.getElementById('okMsg').textContent  = m||'Done'; okToast.show(); };
  const err = (m)=>{ document.getElementById('errMsg').textContent = m||'Something went wrong'; errToast.show(); };

  /* ========= DOM ========= */
  const q = document.getElementById('q');
  const perPageSel = document.getElementById('per_page');
  const btnReset = document.getElementById('btnReset');
  const btnExport = document.getElementById('btnExport');

  const btnApplyFilters = document.getElementById('btnApplyFilters');
  const btnBulk = document.getElementById('btnBulk');
  const btnBulkExit = document.getElementById('btnBulkExit');
  const bulkHint = document.getElementById('bulkHint');

  const thBulk = document.getElementById('thBulk');
  const bulkAll = document.getElementById('bulkAll');
  const loaderColResults = document.getElementById('loaderCol-results');

  const filterTitle = document.getElementById('filterTitle');
  const applyTxt = document.getElementById('applyTxt');

  const fGameId = document.getElementById('fGameId');
  const fFolderId = document.getElementById('fFolderId');
  const fAttemptStatus = document.getElementById('fAttemptStatus');
  const fPublish = document.getElementById('fPublish');
  const fMinPct = document.getElementById('fMinPct');
  const fMaxPct = document.getElementById('fMaxPct');
  const fFrom = document.getElementById('fFrom');
  const fTo = document.getElementById('fTo');
  const fGameUuid = document.getElementById('fGameUuid');
  const fStudentEmail = document.getElementById('fStudentEmail');

  const tabs = {
    results:     { rows:'#rows-results',     loader:'#loaderRow-results',     empty:'#empty-results',     meta:'#metaTxt-results',     pager:'#pager-results',     tr:'#tr-results',     xs:'#xs-results',     tbl:'#tbl-results',     extra:{} },
    published:   { rows:'#rows-published',   loader:'#loaderRow-published',   empty:'#empty-published',   meta:'#metaTxt-published',   pager:'#pager-published',   tr:'#tr-published',   xs:'#xs-published',   tbl:'#tbl-published',   extra:{ publish_to_student:'1' } },
    unpublished: { rows:'#rows-unpublished', loader:'#loaderRow-unpublished', empty:'#empty-unpublished', meta:'#metaTxt-unpublished', pager:'#pager-unpublished', tr:'#tr-unpublished', xs:'#xs-unpublished', tbl:'#tbl-unpublished', extra:{ publish_to_student:'0' } },
  };

  const qs=(sel)=>document.querySelector(sel);
  const qsa=(sel)=>document.querySelectorAll(sel);
  /* ✅ MODAL BACKDROP FIX (Filter Modal) */
const filterModalEl = document.getElementById('filterModal');
const filterModalInst = filterModalEl ? bootstrap.Modal.getOrCreateInstance(filterModalEl) : null;

let pendingFilterApply = false;

function cleanupModalBackdrops(){
  setTimeout(()=>{
    // ✅ If no modal is open, force cleanup leftovers
    if (!document.querySelector('.modal.show')) {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
    }
  }, 60);
}

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

  function pickArray(json){
    if (Array.isArray(json?.data)) return json.data;
    if (Array.isArray(json?.items)) return json.items;
    if (Array.isArray(json?.data?.data)) return json.data.data;
    if (Array.isArray(json?.data?.items)) return json.data.items;
    return [];
  }

  function pickPagination(json, itemsLen, scope){
    const p = json?.pagination || json?.data?.pagination;
    if (p) {
      const total   = Number(p.total ?? itemsLen);
      const perPage = Number(p.per_page ?? perPageSel?.value ?? 20) || 20;
      const page    = Number(p.page ?? state?.[scope]?.page ?? 1) || 1;
      const totalPages = Number(p.total_pages) || Math.max(1, Math.ceil((total || 0) / perPage));
      return { total, per_page: perPage, page, total_pages: totalPages };
    }
    const total = Number(json?.total ?? json?.data?.total ?? itemsLen) || 0;
    const per   = Number(json?.per_page ?? json?.data?.per_page ?? perPageSel?.value ?? 20) || 20;
    const page  = Number(json?.current_page ?? json?.data?.current_page ?? state?.[scope]?.page ?? 1) || 1;
    const last  = Number(json?.last_page ?? json?.data?.last_page) || Math.max(1, Math.ceil(total / per));
    return { total, per_page: per, page, total_pages: last };
  }

  function statusBadge(s){
    const v = String(s||'').toLowerCase();
    if (v==='win') return `<span class="badge badge-success text-uppercase">win</span>`;
    if (v==='fail') return `<span class="badge badge-danger text-uppercase">fail</span>`;
    if (v==='timeout') return `<span class="badge badge-warning text-uppercase">timeout</span>`;
    if (v==='in_progress') return `<span class="badge badge-secondary text-uppercase">in progress</span>`;
    return `<span class="badge badge-secondary text-uppercase">${esc(s||'-')}</span>`;
  }

  function publishBadge(pub){
    const v = Number(pub || 0) === 1;
    return v
      ? `<span class="badge badge-success text-uppercase">published</span>`
      : `<span class="badge badge-secondary text-uppercase">not published</span>`;
  }
async function exportResults(){
  const scope = activeScope || 'results';

  // ✅ take current filters (same as list)
  const usp = new URLSearchParams(buildParams(scope));

  // ✅ export should NOT depend on pagination
  usp.set('page', '1');
  usp.set('per_page', '100000'); // big number
  usp.set('export', '1');

  const url = `${DOOR_RESULTS_EXPORT_API}?${usp.toString()}`;

  const oldHtml = btnExport?.innerHTML || '';
  if (btnExport){
    btnExport.disabled = true;
    btnExport.innerHTML = `<i class="fa fa-spinner fa-spin me-1"></i>Exporting...`;
  }

  try{
    const res = await fetch(url, {
      method:'GET',
      headers:{
        'Authorization':'Bearer ' + TOKEN,
        'Accept':'text/csv, application/octet-stream, application/json'
      }
    });

    // ✅ backend might return json error
    if (!res.ok){
      const j = await res.json().catch(()=> ({}));
      throw new Error(j?.message || `Export failed (${res.status})`);
    }

    const blob = await res.blob();

    // ✅ filename from response header if exists
    let filename = `door-game-results-${scope}.csv`;
    const cd = res.headers.get('Content-Disposition') || res.headers.get('content-disposition') || '';

    // filename*=UTF-8''...
    const m1 = cd.match(/filename\*=UTF-8''([^;]+)/i);
    if (m1 && m1[1]) filename = decodeURIComponent(m1[1]);

    // filename="..."
    const m2 = cd.match(/filename="?([^"]+)"?/i);
    if (m2 && m2[1]) filename = m2[1];

    // ✅ download
    const a = document.createElement('a');
    const objUrl = URL.createObjectURL(blob);
    a.href = objUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(objUrl);

    ok('Export downloaded ✅');
  }catch(e){
    console.error(e);
    err(e.message || 'Export failed');
  }finally{
    if (btnExport){
      btnExport.disabled = false;
      btnExport.innerHTML = oldHtml || `<i class="fa fa-file-arrow-down me-1"></i>Export`;
    }
  }
}

  async function patchPublishAny(kind, payload){
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const headers = {
      'Accept':'application/json',
      'Content-Type':'application/json',
      ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      'Authorization':'Bearer ' + TOKEN
    };

    let url = '';
    let body = null;

    if (kind === 'single'){
      const uuid = payload?.uuid;
      const toPublish = Number(payload?.publish_to_student || 0) === 1;
      if (!uuid) throw new Error('Result UUID missing');

      url = toPublish
        ? `${DOOR_RESULTS_ACTION_API}/${encodeURIComponent(uuid)}/publish-to-student`
        : `${DOOR_RESULTS_ACTION_API}/${encodeURIComponent(uuid)}/unpublish-to-student`;

      body = null;
    }

    if (kind === 'bulk'){
      const uuids = Array.isArray(payload?.result_uuids) ? payload.result_uuids : [];
      const toPublish = Number(payload?.publish_to_student || 0) === 1;
      if (!uuids.length) throw new Error('No selected result UUIDs');

      url = `${DOOR_RESULTS_ACTION_API}/bulk/publish-any`;
      body = JSON.stringify({ result_uuids: uuids, publish_to_student: toPublish ? 1 : 0 });
    }

    const res = await fetch(url, { method:'PATCH', headers, body });
    const json = await res.json().catch(()=> ({}));
    if (!res.ok || json?.success === false) throw new Error(json?.message || `Request failed (${res.status})`);
    return json;
  }

  function actionMenu(item){
    const result = item?.result || {};
    const rid = result?.uuid || '';
    const published = Number(result?.publish_to_student || 0) === 1;
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
            <button class="dropdown-item" data-act="${published ? 'unpublish' : 'publish'}" data-id="${esc(rid)}">
              <i class="fa ${published ? 'fa-eye-slash' : 'fa-eye'}"></i>
              ${published ? 'Unpublish from student' : 'Publish to student'}
            </button>
          </li>

          <li><hr class="dropdown-divider"></li>

          <li>
            <button class="dropdown-item" data-act="copy" data-id="${esc(rid)}">
              <i class="fa fa-copy"></i> Copy Result UUID
            </button>
          </li>
        </ul>
      </div>
    `;
  }

  /* ========= State ========= */
  let sort = '-result_created_at';
  let activeScope = 'results';
  const state = { results:{page:1}, published:{page:1}, unpublished:{page:1} };

  // ✅ Bulk state
  const bulk = { pending:false, enabled:false, selected:new Map() };

  function syncBulkUI(){
    const isOn = bulk.enabled;
    thBulk.style.display = isOn ? '' : 'none';
    loaderColResults.setAttribute('colspan', isOn ? '11' : '10'); // ✅ new columns
    btnBulkExit.style.display = isOn ? '' : 'none';
    bulkHint.style.display = isOn ? '' : 'none';
    if (!isOn && bulkAll) bulkAll.checked = false;
    updateBulkButtonText();
  }

  function updateBulkButtonText(){
    if (!bulk.enabled){
      btnBulk.innerHTML = `<i class="fa fa-bolt me-1"></i>Bulk Publish`;
      btnBulk.disabled = false;
      return;
    }
    const count = bulk.selected.size;
    if (count <= 0){
      btnBulk.innerHTML = `<i class="fa fa-bolt me-1"></i>Publish Selected`;
      btnBulk.disabled = true;
      return;
    }
    let anyPublished = false;
    bulk.selected.forEach((pub)=>{ if (Number(pub) === 1) anyPublished = true; });

    btnBulk.innerHTML =
      anyPublished
        ? `<i class="fa fa-eye-slash me-1"></i>Unpublish Selected (${count})`
        : `<i class="fa fa-eye me-1"></i>Publish Selected (${count})`;

    btnBulk.disabled = false;
  }

  function clearBulkSelection(){
    bulk.selected.clear();
    bulkAll.checked = false;
    updateBulkButtonText();
  }

  function folderNameFrom(item){
  const r = item || {};
  const student = r.student || {};
  const result  = r.result  || {};

  // ✅ Your API gives folder name here
  const fromStudent =
    student.folder_title ||
    student.user_folder_name ||
    student.folder_name ||
    '';

  // ✅ fallback (if someday you return folder object)
  const folderObj =
    r.folder ||
    result.folder ||
    r.user_folder ||
    result.user_folder ||
    {};

  const fromObj = folderObj.title || folderObj.name || '';

  // ✅ final fallback
  const fromResult =
    result.folder_title ||
    result.folder_name ||
    '';

  return fromStudent || fromObj || fromResult || '-';
}


  function buildRowResults(item){
    const r = item || {};
    const student = r.student || {};
    const game = r.game || {};
    const attempt = r.attempt || {};
    const result = r.result || {};

    const tr = document.createElement('tr');

    if (bulk.enabled){
      const tdBulk = document.createElement('td');
      tdBulk.className = 'bulk-col';

      const uuid = result.uuid || '';
      const pub = Number(result.publish_to_student || 0);

      tdBulk.innerHTML = `
        <input class="form-check-input bulk-check js-bulk-check"
               type="checkbox"
               data-uuid="${esc(uuid)}"
               data-pub="${pub}">
      `;
      tr.appendChild(tdBulk);
    }

    const tdStudent = document.createElement('td');
    tdStudent.innerHTML = `<div class="fw-semibold"></div><div class="text-muted small"></div>`;
    tdStudent.querySelector('.fw-semibold').textContent = student.name || '-';
    tdStudent.querySelector('.text-muted.small').textContent = student.email || '-';

    const tdGame = document.createElement('td');
    tdGame.innerHTML = `<div class="fw-semibold"></div>`;
    tdGame.querySelector('.fw-semibold').textContent = (game.title || '-');

    const tdAttempt = document.createElement('td');
    tdAttempt.innerHTML = `<span class="badge-pill"><i class="fa fa-repeat"></i> <span class="att-no"></span></span>`;
    tdAttempt.querySelector('.att-no').textContent = `#${Number(result.attempt_no || 0)}`;

    const tdScore = document.createElement('td');
    tdScore.innerHTML = `<div class="fw-semibold"></div>`;
    tdScore.querySelector('.fw-semibold').textContent = String(Number(result.score || 0));

    const tdPct = document.createElement('td');
    const pct = (result.accuracy ?? null);
    tdPct.innerHTML = `<div class="fw-semibold"></div>`;
    tdPct.querySelector('.fw-semibold').textContent =
      (pct == null || pct === '') ? '—' : (Number(pct).toFixed(2) + '%');

    const tdStatus = document.createElement('td');
    tdStatus.innerHTML = statusBadge(attempt.status);

    // ✅ NEW
    const tdPublish = document.createElement('td');
    tdPublish.innerHTML = publishBadge(result.publish_to_student);

    const tdFolder = document.createElement('td');
    tdFolder.innerHTML = `<div class="fw-semibold"></div>`;
    tdFolder.querySelector('.fw-semibold').textContent = folderNameFrom(r);

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
    tr.appendChild(tdPublish);
    tr.appendChild(tdFolder);
    tr.appendChild(tdSubmitted);
    tr.appendChild(tdActions);

    return tr;
  }

  function buildRowLite(item){
    const r = item || {};
    const student = r.student || {};
    const game = r.game || {};
    const result = r.result || {};

    const tr = document.createElement('tr');

    const tdStudent = document.createElement('td');
    tdStudent.innerHTML = `<div class="fw-semibold"></div><div class="text-muted small"></div>`;
    tdStudent.querySelector('.fw-semibold').textContent = student.name || '-';
    tdStudent.querySelector('.text-muted.small').textContent = student.email || '-';

    const tdGame = document.createElement('td');
    tdGame.innerHTML = `<div class="fw-semibold"></div>`;
    tdGame.querySelector('.fw-semibold').textContent = (game.title || '-');

    const tdAttempt = document.createElement('td');
    tdAttempt.innerHTML = `<span class="badge-pill"><i class="fa fa-repeat"></i> <span class="att-no"></span></span>`;
    tdAttempt.querySelector('.att-no').textContent = `#${Number(result.attempt_no || 0)}`;

    const tdScore = document.createElement('td');
    tdScore.innerHTML = `<div class="fw-semibold"></div>`;
    tdScore.querySelector('.fw-semibold').textContent = String(Number(result.score || 0));

    const tdPct = document.createElement('td');
    const pct = (result.accuracy ?? null);
    tdPct.innerHTML = `<div class="fw-semibold"></div>`;
    tdPct.querySelector('.fw-semibold').textContent =
      (pct == null || pct === '') ? '—' : (Number(pct).toFixed(2) + '%');

    // ✅ NEW
    const tdPublish = document.createElement('td');
    tdPublish.innerHTML = publishBadge(result.publish_to_student);

    const tdFolder = document.createElement('td');
    tdFolder.innerHTML = `<div class="fw-semibold"></div>`;
    tdFolder.querySelector('.fw-semibold').textContent = folderNameFrom(r);

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
    tr.appendChild(tdPublish);
    tr.appendChild(tdFolder);
    tr.appendChild(tdSubmitted);
    tr.appendChild(tdActions);

    return tr;
  }

  /* ✅ Bottom X Scroll sync */
  function syncXScroll(scope){
    const tr = qs(tabs[scope].tr);
    const xs = qs(tabs[scope].xs);
    const tbl = qs(tabs[scope].tbl);
    if (!tr || !xs || !tbl) return;

    const inner = xs.querySelector('.x-scrollbar-inner');
    const need = tbl.scrollWidth > tr.clientWidth + 2;

    xs.classList.toggle('hidden', !need);
    if (!need) return;

    inner.style.width = tbl.scrollWidth + 'px';

    if (xs.__bound) return;
    xs.__bound = true;

    let lock = false;

    tr.addEventListener('scroll', ()=>{
      if (lock) return;
      lock = true;
      xs.scrollLeft = tr.scrollLeft;
      lock = false;
    });

    xs.addEventListener('scroll', ()=>{
      if (lock) return;
      lock = true;
      tr.scrollLeft = xs.scrollLeft;
      lock = false;
    });

    window.addEventListener('resize', ()=> {
      inner.style.width = tbl.scrollWidth + 'px';
      xs.classList.toggle('hidden', !(tbl.scrollWidth > tr.clientWidth + 2));
    });
  }

  /* ========= Load games & folders ========= */
  async function loadGamesForFilter(){
    try{
      const res = await fetch(DOOR_GAMES_LIST_API, {
        headers: { 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
      });
      const json = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(json?.message || 'Failed to load games');

      const games = pickArray(json);
      while (fGameId.options.length > 1) fGameId.remove(1);

      games.forEach(g=>{
        const op = document.createElement('option');
        op.value = g.id || g.uuid || '';
        op.textContent = g.title || g.name || 'Unnamed Game';
        fGameId.appendChild(op);
      });
    } catch(e){
      console.error(e);
    }
  }

  async function loadFoldersForFilter(){
    try{
      const res = await fetch(DOOR_FOLDERS_LIST_API, {
        headers: { 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
      });
      const json = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(json?.message || 'Failed to load folders');

      const folders = pickArray(json);
      while (fFolderId.options.length > 1) fFolderId.remove(1);

      folders.forEach(f=>{
        const op = document.createElement('option');
        op.value = f.id || f.uuid || '';
        op.textContent = f.title || f.name || 'Untitled Folder';
        fFolderId.appendChild(op);
      });
    } catch(e){
      console.warn('Folder list API not available:', e?.message || e);
      // Keep only "All folders"
    }
  }

  function buildParams(scope){
    const usp = new URLSearchParams();
    usp.set('page', state[scope].page || 1);
    usp.set('per_page', Number(perPageSel?.value || 20));
    usp.set('sort', sort);

    if (q && q.value.trim()) usp.set('q', q.value.trim());
    if (fGameId && fGameId.value) usp.set('door_game_id', fGameId.value);

    // ✅ NEW filter
if (fFolderId && fFolderId.value){
  usp.set('folder_id', fFolderId.value);
  usp.set('user_folder_id', fFolderId.value);
}

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
    return `${DOOR_RESULTS_LIST_API}?${buildParams(scope)}`;
  }
  function hydrateFolderDropdownFromItems(items){
  if (!fFolderId) return;

  // ✅ if already loaded, don't override
  if (fFolderId.options.length > 1) return;

  const map = new Map();

  (items || []).forEach(it=>{
    const st = it?.student || {};
    const fid = st.folder_id || st.user_folder_id;
    const title = st.folder_title || st.user_folder_name;

    if (fid && title) map.set(String(fid), String(title));
  });

  if (!map.size) return;

  // ✅ sort by title
  const sorted = Array.from(map.entries()).sort((a,b)=> a[1].localeCompare(b[1]));

  sorted.forEach(([id,title])=>{
    const op = document.createElement('option');
    op.value = id;
    op.textContent = title;
    fFolderId.appendChild(op);
  });
}

  async function load(scope){
    activeScope = scope;

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
      hydrateFolderDropdownFromItems(items);

      const pagination = pickPagination(json, items.length, scope);

      if (items.length===0) empty.style.display='';

      const frag = document.createDocumentFragment();
      items.forEach(it=>{
        const row = (scope === 'results') ? buildRowResults(it) : buildRowLite(it);
        frag.appendChild(row);
      });
      rowsEl.appendChild(frag);

      // ✅ bulk checkbox wiring only for results
      if (scope === 'results' && bulk.enabled){
        rowsEl.querySelectorAll('.js-bulk-check').forEach(chk=>{
          const uuid = chk.dataset.uuid;
          const pub = Number(chk.dataset.pub || 0);

          if (bulk.selected.has(uuid)) chk.checked = true;

          chk.addEventListener('change', ()=>{
            const tr = chk.closest('tr');
            if (chk.checked){
              bulk.selected.set(uuid, pub);
              tr?.classList.add('row-selected');
            } else {
              bulk.selected.delete(uuid);
              tr?.classList.remove('row-selected');
            }
            updateBulkButtonText();
          });

          if (chk.checked) chk.closest('tr')?.classList.add('row-selected');
        });

        bulkAll.checked = false;
      }

      // ✅ Pagination UI
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

      // ✅ bottom scrollbar sync
      syncXScroll(scope);

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

  /* ========= Bulk ========= */
  btnBulk?.addEventListener('click', async ()=>{
    if (!bulk.enabled){
      bulk.pending = true;
      filterTitle.innerHTML = `<i class="fa fa-bolt me-2"></i>Bulk Publish / Unpublish`;
      applyTxt.textContent = 'Load for Bulk';
      new bootstrap.Modal(document.getElementById('filterModal')).show();
      return;
    }

    if (bulk.selected.size <= 0) return;

    let anyPublished = false;
    bulk.selected.forEach(pub => { if (Number(pub) === 1) anyPublished = true; });

    const toPublish = anyPublished ? 0 : 1;
    const uuids = Array.from(bulk.selected.keys());

    const confirm = await Swal.fire({
      icon:'question',
      title: toPublish ? 'Publish selected results?' : 'Unpublish selected results?',
      text: `Selected: ${uuids.length} result(s)`,
      showCancelButton:true,
      confirmButtonText: toPublish ? 'Publish' : 'Unpublish',
      cancelButtonText:'Cancel'
    });

    if (!confirm.isConfirmed) return;

    try{
      await patchPublishAny('bulk', { result_uuids: uuids, publish_to_student: toPublish });
      ok(toPublish ? 'Bulk published!' : 'Bulk unpublished!');

      bulk.enabled = false;
      bulk.pending = false;
      clearBulkSelection();
      syncBulkUI();

      Object.keys(state).forEach(k => state[k].page = 1);
      await load('results');
      await load('published');
      await load('unpublished');

    }catch(e){
      console.error(e);
      err(e.message || 'Bulk update failed');
    }
  });

  btnBulkExit?.addEventListener('click', ()=>{
    bulk.enabled = false;
    bulk.pending = false;
    clearBulkSelection();
    syncBulkUI();
    load('results');
  });

  bulkAll?.addEventListener('change', ()=>{
    const rowsEl = document.querySelector('#rows-results');
    const checks = rowsEl.querySelectorAll('.js-bulk-check');

    checks.forEach(chk=>{
      chk.checked = bulkAll.checked;
      const uuid = chk.dataset.uuid;
      const pub = Number(chk.dataset.pub || 0);
      const tr = chk.closest('tr');

      if (chk.checked){
        bulk.selected.set(uuid, pub);
        tr?.classList.add('row-selected');
      } else {
        bulk.selected.delete(uuid);
        tr?.classList.remove('row-selected');
      }
    });

    updateBulkButtonText();
  });
  btnExport?.addEventListener('click', async ()=>{
  const confirm = await Swal.fire({
    icon:'question',
    title:'Export results?',
    text:'This will download a CSV using your current filters.',
    showCancelButton:true,
    confirmButtonText:'Export',
    cancelButtonText:'Cancel'
  });

  if (!confirm.isConfirmed) return;
  exportResults();
});

btnApplyFilters?.addEventListener('click', (e)=>{
  e.preventDefault();
  e.stopPropagation();

  pendingFilterApply = true;
  filterModalInst?.hide(); // ✅ close modal first
});

/* ✅ AFTER modal fully closes -> apply logic + load */
filterModalEl?.addEventListener('hidden.bs.modal', ()=>{
  if (!pendingFilterApply){
    cleanupModalBackdrops();
    return;
  }
  pendingFilterApply = false;

  // ✅ If it was opened for bulk selection
  if (bulk.pending){
    bulk.pending = false;
    bulk.enabled = true;
    clearBulkSelection();
    syncBulkUI();
  }

  // ✅ Reset modal UI text
  filterTitle.innerHTML = `<i class="fa fa-filter me-2"></i>Filter Results`;
  applyTxt.textContent = 'Apply Filters';

  // ✅ NOW load safely (no stuck backdrop)
  Object.keys(state).forEach(k => state[k].page = 1);
  load('results');
  load('published');
  load('unpublished');

  cleanupModalBackdrops(); // ✅ failsafe
});


  btnReset?.addEventListener('click', ()=>{
    if (q) q.value='';
    if (perPageSel) perPageSel.value='20';
    if (fGameId) fGameId.value='';
    if (fFolderId) fFolderId.value='';
    if (fAttemptStatus) fAttemptStatus.value='';
    if (fPublish) fPublish.value='';
    if (fMinPct) fMinPct.value='';
    if (fMaxPct) fMaxPct.value='';
    if (fFrom) fFrom.value='';
    if (fTo) fTo.value='';
    if (fGameUuid) fGameUuid.value='';
    if (fStudentEmail) fStudentEmail.value='';
    sort='-result_created_at';

    bulk.pending = false;
    bulk.enabled = false;
    clearBulkSelection();
    syncBulkUI();

    Object.keys(state).forEach(k => state[k].page = 1);
    load('results');
    load('published');
    load('unpublished');
  });

  perPageSel?.addEventListener('change', ()=>{
    Object.keys(state).forEach(k => state[k].page = 1);
    load(activeScope || 'results');
  });

  /* ========= Tabs ========= */
  document.querySelector('a[href="#tab-results"]').addEventListener('shown.bs.tab', ()=>{
    activeScope = 'results';
    load('results');
  });
  document.querySelector('a[href="#tab-published"]').addEventListener('shown.bs.tab', ()=>{
    activeScope = 'published';
    if (bulk.enabled){
      bulk.enabled = false;
      clearBulkSelection();
      syncBulkUI();
    }
    load('published');
  });
  document.querySelector('a[href="#tab-unpublished"]').addEventListener('shown.bs.tab', ()=>{
    activeScope = 'unpublished';
    if (bulk.enabled){
      bulk.enabled = false;
      clearBulkSelection();
      syncBulkUI();
    }
    load('unpublished');
  });

  /* ========= Copy + publish/unpublish ========= */
  document.addEventListener('click', async (e)=>{
    const copyBtn = e.target.closest('button.dropdown-item[data-act="copy"]');
    if (copyBtn){
      const id = copyBtn.dataset.id || '';
      if(!id) return;
      try{ await navigator.clipboard.writeText(id); ok('Copied result id'); }
      catch{ ok('Copy: '+id); }
      return;
    }

    const pubBtn = e.target.closest('button.dropdown-item[data-act="publish"], button.dropdown-item[data-act="unpublish"]');
    if (!pubBtn) return;

    const rid = pubBtn.dataset.id || '';
    if (!rid) return;

    const toPublish = pubBtn.dataset.act === 'publish' ? 1 : 0;

    try{
      await patchPublishAny('single', { uuid: rid, publish_to_student: toPublish });
      ok(toPublish ? 'Published to student' : 'Unpublished from student');
      load('results'); load('published'); load('unpublished');
    }catch(ex){
      console.error(ex);
      err(ex.message || 'Update failed');
    }
  });

  /* ========= Initial load ========= */
  syncBulkUI();
  Promise.all([loadGamesForFilter(), loadFoldersForFilter()]).then(()=>{
    load('results'); load('published'); load('unpublished');
  });

})();
</script>
@endpush
