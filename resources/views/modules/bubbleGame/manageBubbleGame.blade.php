{{-- resources/views/modules/bubbleGame/manageBubbleGames.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Manage Bubble Games')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ===== COMPLETE DROPDOWN FIX (same as Quiz page) ===== */
.bg-wrap,
.table-wrap,
.card,
.card-body,
.table-responsive,
.tab-content,
.tab-pane {
  position: relative !important;
  overflow: visible !important;
  transform: none !important;
  filter: none !important;
  perspective: none !important;
}
.table { position: relative; overflow: visible !important; }
.table tbody tr { position: relative; overflow: visible !important; }
.table tbody tr td { overflow: visible !important; }
.table tbody tr td:last-child { position: relative; z-index: 1; overflow: visible !important; }

.table td:has(.dropdown),
.table th:has(.dropdown){
  position: static !important;
  overflow: visible !important;
}

.dropdown{ position: static !important; }

.dd-toggle,
[data-bs-toggle="dropdown"]{
  position: relative;
  z-index: 10;
  border-radius: 8px;
  transition: all 0.15s ease;
}
.dd-toggle:hover,
[data-bs-toggle="dropdown"]:hover{
  box-shadow: 0 2px 8px rgba(0,0,0,.1);
}

/* non-portaled dropdown */
.dropdown-menu:not(.dd-portal){
  position: fixed !important;
  z-index: 999999 !important;
  min-width: 220px;
  max-width: 320px;
  border-radius: 12px;
  border: 1px solid var(--line-strong, #e5e7eb);
  box-shadow: 0 20px 40px rgba(15,23,42,.2);
  background: var(--surface, #ffffff);
  padding: .5rem 0;
}

/* portaled dropdown */
.dropdown-menu.dd-portal{
  position: fixed !important;
  left: 0 !important;
  top: 0 !important;
  transform: none !important;
  z-index: 999999 !important;
  min-width: 220px;
  max-width: 320px;
  border-radius: 12px;
  border: 1px solid var(--line-strong, #e5e7eb);
  box-shadow: 0 20px 40px rgba(15,23,42,.2),
              0 0 0 1px rgba(0,0,0,.05);
  background: var(--surface, #ffffff);
  overflow: visible !important;
  padding: .5rem 0;
  margin: 0 !important;
  will-change: transform;
}

.dropdown-menu.show{
  display:block !important;
  visibility:visible !important;
  opacity:1 !important;
  z-index:999999 !important;
  overflow:visible !important;
}
.dropdown-menu[data-bs-popper]{ position: fixed !important; }

.dropdown-item{
  display:flex;
  align-items:center;
  gap:.6rem;
  padding:.5rem 1rem;
  cursor:pointer;
  transition: background-color .15s ease;
  border:none;
  background:none;
  text-decoration:none;
  color: var(--ink, #1f2937);
  width:100%;
  text-align:left;
  white-space:nowrap;
}
.dropdown-item:hover{
  background-color: rgba(0,0,0,.05);
  color: var(--ink, #1f2937);
}
.dropdown-item i{
  width:18px;
  text-align:center;
  flex-shrink:0;
  opacity:.85;
}
.dropdown-item.text-danger{ color:#ef4444 !important; }
.dropdown-item.text-danger:hover{
  background-color: rgba(239,68,68,.08);
  color:#dc2626 !important;
}
.dropdown-divider{
  margin:.375rem 0;
  border-top:1px solid var(--line-strong, #e5e7eb);
}

/* Dark mode */
html.theme-dark .dropdown-menu,
html.theme-dark .dropdown-menu.dd-portal{
  background:#0f172a;
  border-color:#334155;
  box-shadow:0 20px 40px rgba(0,0,0,.6),
             0 0 0 1px rgba(255,255,255,.1);
}
html.theme-dark .dropdown-item{ color:#e5e7eb; }
html.theme-dark .dropdown-item:hover{
  background-color: rgba(255,255,255,.05);
  color:#f3f4f6;
}
html.theme-dark .dropdown-item.text-danger{ color:#f87171 !important; }
html.theme-dark .dropdown-item.text-danger:hover{
  background-color: rgba(239,68,68,.15);
  color:#fca5a5 !important;
}
html.theme-dark .dropdown-divider{ border-color:#334155; }

.pagination{ position:relative; z-index:1; }
.card-body > .d-flex:last-child{ padding:1rem; overflow:visible !important; }
.nav-tabs{ position:relative; z-index:1; }
.table tbody tr:nth-last-child(-n+3) .dropdown-menu{ margin-bottom:0 !important; }

/* ===== Page shell ===== */
.bg-wrap{ max-width: 1280px; margin: 16px auto 44px; padding: 0 6px; }

/* ===== Status badges ===== */
.badge-soft{
  background: color-mix(in oklab, var(--muted-color) 12%, transparent);
  color: var(--ink);
}
.table .badge.badge-success{ background: var(--success-color) !important; color:#fff !important; }
.table .badge.badge-secondary{ background:#64748b !important; color:#fff !important; }
.table .badge.badge-warning{ background:#f59e0b !important; color:#111827 !important; }
.table .badge.badge-danger{ background:#ef4444 !important; color:#fff !important; }

/* ===== Sorting ===== */
.sortable{
  cursor:pointer;
  white-space:nowrap;
  user-select:none;
}
.sortable .caret{
  display:inline-block;
  margin-left:.35rem;
  opacity:.65;
}
.sortable.asc .caret::after{ content:"▲"; font-size:.7rem; }
.sortable.desc .caret::after{ content:"▼"; font-size:.7rem; }

/* ===== Row cues ===== */
tr.is-archived td{ background: color-mix(in oklab, var(--muted-color) 6%, transparent); }
tr.is-deleted  td{ background: color-mix(in oklab, var(--danger-color) 6%, transparent); }

.icon-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  height:34px;
  min-width:34px;
  padding:0 10px;
  border:1px solid var(--line-strong);
  background: var(--surface);
  border-radius:10px;
  transition: box-shadow .2s ease;
}
.icon-btn:hover{ box-shadow: var(--shadow-1); }

.empty{ color: var(--muted-color); }

.placeholder{
  background: linear-gradient(90deg, #00000010, #00000005, #00000010);
  border-radius: 8px;
  animation: placeholder-wave 2s ease-in-out infinite;
}
@keyframes placeholder-wave{
  0%{ background-position:-200px 0; }
  100%{ background-position: calc(200px + 100%) 0; }
}
.placeholder-wave{
  animation: placeholder-wave 2s linear infinite;
  background-size: 200px 100%;
}

/* Modals */
.modal-content{
  border-radius:16px;
  border:1px solid var(--line-strong);
  background: var(--surface);
}
.modal-header{ border-bottom:1px solid var(--line-strong); }
.modal-footer{ border-top:1px solid var(--line-strong); }

.form-control,
.form-select{
  border-radius:12px;
  border:1px solid var(--line-strong);
  background:#fff;
  transition: border-color .15s ease, box-shadow .15s ease;
}
.form-control:focus,
.form-select:focus{
  border-color: var(--primary-color, #3b82f6);
  box-shadow: 0 0 0 .25rem rgba(59,130,246,.1);
  outline:none;
}
html.theme-dark .form-control,
html.theme-dark .form-select{
  background:#0f172a;
  color:#e5e7eb;
  border-color: var(--line-strong);
}
html.theme-dark .table thead th{
  background:#0f172a;
  border-color: var(--line-strong);
  color:#94a3b8;
}
html.theme-dark .table tbody tr{ border-color: var(--line-soft); }

/* Table */
.table{ margin-bottom:0; }
.table thead{
  position: sticky;
  top: 0;
  z-index: 10;
  background: var(--surface);
}
.table-hover tbody tr:hover{ background-color: rgba(0,0,0,.02); }
html.theme-dark .table-hover tbody tr:hover{ background-color: rgba(255,255,255,.05); }

.pagination{ gap:.25rem; }
.page-link{
  border-radius:8px;
  border:1px solid var(--line-strong);
  transition: all .2s ease;
}
.page-link:hover{ background-color: rgba(0,0,0,.05); }
.page-item.active .page-link{
  background-color: var(--primary-color, #3b82f6);
  border-color: var(--primary-color, #3b82f6);
}
html.theme-dark .page-link{ background:#0f172a; color:#e5e7eb; }
html.theme-dark .page-link:hover{ background-color: rgba(255,255,255,.1); }

@media (max-width:768px){
  .dropdown-menu,
  .dropdown-menu.dd-portal{ min-width:180px; max-width:260px; }
  .dropdown-item{ padding:.4rem .8rem; font-size:.9rem; }
  .table-responsive{ font-size:.9rem; }
  .bg-wrap{ margin:12px; }
}
</style>
@endpush

@section('content')
<div class="bg-wrap">

  {{-- ================= Tabs ================= --}}
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#tab-games" role="tab" aria-selected="true">
        <i class="fa-solid fa-gamepad me-2"></i>Games
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#tab-archived" role="tab" aria-selected="false">
        <i class="fa-solid fa-folder me-2"></i>Archived
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#tab-deleted" role="tab" aria-selected="false">
        <i class="fa-solid fa-trash me-2"></i>Bin
      </a>
    </li>
  </ul>

  <div class="tab-content mb-3">

    {{-- ========== TAB: Games (default active) ========== --}}
    <div class="tab-pane fade show active" id="tab-games" role="tabpanel">
      {{-- Toolbar --}}
      <div class="row align-items-center g-2 mb-3 mfa-toolbar panel">
        <div class="col-12 col-xl d-flex align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <label class="text-muted small mb-0">Per page</label>
            <select id="per_page" class="form-select" style="width:96px;">
              <option>10</option><option selected>20</option><option>30</option><option>50</option><option>100</option>
            </select>
          </div>

          <div class="position-relative" style="min-width:300px;">
            <input id="q" type="text" class="form-control ps-5" placeholder="Search by title…">
            <i class="fa fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);opacity:.6;"></i>
          </div>

          <button id="btnFilter" class="btn btn-primary ms-1" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="fa fa-filter me-1"></i>Filter
          </button>
          <button id="btnReset" class="btn btn-primary">
            <i class="fa fa-rotate-left me-1"></i>Reset
          </button>
        </div>

        <div class="col-12 col-xl-auto ms-xl-auto d-flex justify-content-xl-end">
          <a id="btnCreate" href="/bubble-games/create" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i>New Game
          </a>
        </div>
      </div>

      {{-- Table --}}
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th class="sortable" data-col="title">GAME <span class="caret"></span></th>
                  <th class="sortable" data-col="status" style="width:130px;">STATUS <span class="caret"></span></th>
                  <th class="sortable" data-col="max_attempts" style="width:140px;">MAX ATTEMPTS <span class="caret"></span></th>
                  <th class="sortable" data-col="per_question_time_sec" style="width:170px;">TIME/Q <span class="caret"></span></th>
                  <th style="width:220px;">SETTINGS</th>
                  <th class="sortable" data-col="created_at" style="width:180px;">CREATED <span class="caret"></span></th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-games">
                <tr id="loaderRow-games" style="display:none;">
                  <td colspan="7" class="p-0">
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

          <div id="empty-games" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-folder-open mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No bubble games found.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-games">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-games" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

    {{-- ========== TAB: Archived ========== --}}
    <div class="tab-pane fade" id="tab-archived" role="tabpanel">
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th>GAME</th>
                  <th style="width:140px;">MAX ATTEMPTS</th>
                  <th style="width:170px;">TIME/Q</th>
                  <th style="width:220px;">SETTINGS</th>
                  <th style="width:180px;">CREATED</th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-archived">
                <tr id="loaderRow-archived" style="display:none;">
                  <td colspan="6" class="p-0">
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

          <div id="empty-archived" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-box-archive mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No archived games.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-archived">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-archived" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

    {{-- ========== TAB: Bin (Deleted) ========== --}}
    <div class="tab-pane fade" id="tab-deleted" role="tabpanel">
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th>GAME</th>
                  <th style="width:170px;">DELETED AT</th>
                  <th class="text-end" style="width:160px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-deleted">
                <tr id="loaderRow-deleted" style="display:none;">
                  <td colspan="3" class="p-0">
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

          <div id="empty-deleted" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-trash mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No items in Bin.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-deleted">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-deleted" class="pagination mb-0"></ul></nav>
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
        <h5 class="modal-title"><i class="fa fa-filter me-2"></i>Filter Games</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="filterStatus" class="form-label">Status</label>
          <select id="filterStatus" class="form-select">
            <option value="active" selected>Active</option>
            <option value="archived">Archived</option>
            <option value="inactive">Inactive</option>
            <option value="">All (no status filter)</option>
          </select>
          <div class="form-text">This applies to the <b>Games</b> tab.</div>
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

{{-- Toasts (success/error only) --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2100">
  <div id="okToast" class="toast text-bg-success border-0"><div class="d-flex">
    <div id="okMsg" class="toast-body">Done</div><button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
  </div></div>
  <div id="errToast" class="toast text-bg-danger border-0 mt-2"><div class="d-flex">
    <div id="errMsg" class="toast-body">Something went wrong</div><button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
  </div></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ===== Force dropdown overflows to body (portal) ===== */
(function() {
  let activePortal = null;

  function placeMenuInBody(menu, btnRect) {
    const vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    const vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

    menu.classList.add('dd-portal');
    menu.style.display = 'block';
    menu.style.visibility = 'hidden';
    document.body.appendChild(menu);

    const menuWidth  = menu.offsetWidth;
    const menuHeight = menu.offsetHeight;

    let left = btnRect.left;
    const spaceRight = vw - btnRect.right;
    if (spaceRight < menuWidth && (btnRect.right - menuWidth) > 8) left = btnRect.right - menuWidth;
    left = Math.max(8, Math.min(left, vw - menuWidth - 8));

    let top = btnRect.bottom + 4;
    const spaceBelow = vh - btnRect.bottom;
    const spaceAbove = btnRect.top;

    if (spaceBelow < menuHeight + 20 && spaceAbove > menuHeight + 20) top = btnRect.top - menuHeight - 4;
    else if (spaceBelow < menuHeight + 20) top = Math.max(8, Math.min(top, vh - menuHeight - 8));

    menu.style.left = left + 'px';
    menu.style.top  = top + 'px';
    menu.style.visibility = 'visible';
  }

  function closeActivePortal() {
    if (activePortal && activePortal.menu && activePortal.menu.isConnected) {
      activePortal.menu.classList.remove('dd-portal');
      activePortal.menu.style.cssText = '';
      if (activePortal.parent) activePortal.parent.appendChild(activePortal.menu);
      activePortal = null;
    }
  }

  document.addEventListener('show.bs.dropdown', function(event) {
    const dropdownElement = event.target;
    const toggleBtn = dropdownElement.querySelector('.dd-toggle, [data-bs-toggle="dropdown"]');
    const menu = dropdownElement.querySelector('.dropdown-menu');
    if (!toggleBtn || !menu) return;

    closeActivePortal();
    menu.__originalParent = menu.parentElement;

    const rect = toggleBtn.getBoundingClientRect();
    placeMenuInBody(menu, rect);

    activePortal = { menu: menu, parent: menu.__originalParent };

    const closeHandler = () => {
      try {
        const instance = bootstrap.Dropdown.getInstance(toggleBtn);
        if (instance) instance.hide();
      } catch(e) {}
    };

    menu.__closeHandler = closeHandler;
    window.addEventListener('scroll', closeHandler, true);
    window.addEventListener('resize', closeHandler);
  });

  document.addEventListener('hidden.bs.dropdown', function(event) {
    const dropdownElement = event.target;
    const menu = dropdownElement.querySelector('.dropdown-menu.dd-portal');
    if (!menu && !activePortal) return;

    const targetMenu = menu || (activePortal && activePortal.menu);
    if (targetMenu) {
      if (targetMenu.__closeHandler) {
        window.removeEventListener('scroll', targetMenu.__closeHandler, true);
        window.removeEventListener('resize', targetMenu.__closeHandler);
        targetMenu.__closeHandler = null;
      }
      targetMenu.classList.remove('dd-portal');
      targetMenu.style.cssText = '';
      if (targetMenu.__originalParent) {
        targetMenu.__originalParent.appendChild(targetMenu);
        targetMenu.__originalParent = null;
      }
    }
    activePortal = null;
  });

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.dd-toggle');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    document.querySelectorAll('.dd-toggle').forEach(otherBtn => {
      if (otherBtn !== btn) {
        try {
          const instance = bootstrap.Dropdown.getInstance(otherBtn);
          if (instance) instance.hide();
        } catch(e) {}
      }
    });

    const instance = bootstrap.Dropdown.getOrCreateInstance(btn, {
      autoClose: true,
      boundary: 'viewport'
    });
    instance.toggle();
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown-menu') && !e.target.closest('.dd-toggle')) closeActivePortal();
  });
})();

(function(){
  /* ========= Auth / base panel ========= */
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  const ROLE  = (localStorage.getItem('role') || sessionStorage.getItem('role') || '').toLowerCase();
  const basePanel = (ROLE.includes('super') ? '/super_admin' : '/admin');

  if (!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  /* ========= Routes (API) ========= */
  const API_BASE = '/api/bubble-games';

  /* ========= Toast helpers ========= */
  const okToast  = new bootstrap.Toast(document.getElementById('okToast'));
  const errToast = new bootstrap.Toast(document.getElementById('errToast'));
  const ok  = (m)=>{ document.getElementById('okMsg').textContent  = m||'Done'; okToast.show(); };
  const err = (m)=>{ document.getElementById('errMsg').textContent = m||'Something went wrong'; errToast.show(); };

  /* ========= DOM refs per tab ========= */
  const tabs = {
    games:    { rows:'#rows-games',    loader:'#loaderRow-games',    empty:'#empty-games',    meta:'#metaTxt-games',    pager:'#pager-games'    },
    archived: { rows:'#rows-archived', loader:'#loaderRow-archived', empty:'#empty-archived', meta:'#metaTxt-archived', pager:'#pager-archived' },
    deleted:  { rows:'#rows-deleted',  loader:'#loaderRow-deleted',  empty:'#empty-deleted',  meta:'#metaTxt-deleted',  pager:'#pager-deleted'  },
  };

  /* ========= Shared controls (Games tab) ========= */
  const q = document.getElementById('q');
  const perPageSel = document.getElementById('per_page');
  const btnReset   = document.getElementById('btnReset');

  const filterStatusSel = document.getElementById('filterStatus');
  const btnApplyFilters = document.getElementById('btnApplyFilters');

  const btnCreate = document.getElementById('btnCreate');
  if (btnCreate) btnCreate.setAttribute('href','/bubble-games/create');

  /* ========= State ========= */
  const state = { games:{page:1}, archived:{page:1}, deleted:{page:1} };

  // sortKey is UI key; mapped to safe DB column names below
  let sortKey = 'created_at';
  let sortDir = 'desc';

  const sortMap = {
    title: 'bubble_game.title',
    status: 'bubble_game.status',
    max_attempts: 'bubble_game.max_attempts',
    per_question_time_sec: 'bubble_game.per_question_time_sec',
    created_at: 'bubble_game.created_at'
  };

  /* ========= Utils ========= */
  const esc=(s)=>{const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;','`':'&#96;'}; return (s==null?'':String(s)).replace(/[&<>"'`]/g,ch=>m[ch]); };
  const fmtDate=(iso)=>{ if(!iso) return '-'; const d=new Date(iso); if(isNaN(d)) return esc(iso); return d.toLocaleString(undefined,{year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit'}); };

  function badgeStatus(s){
    const v = String(s||'').toLowerCase();
    const map = { active:'success', archived:'secondary', inactive:'warning' };
    const cls = map[v] || 'secondary';
    return `<span class="badge badge-${cls} text-uppercase">${esc(v || '-')}</span>`;
  }

  const qs=(sel)=>document.querySelector(sel);
  const qsa=(sel)=>document.querySelectorAll(sel);
  const showLoader=(scope, v)=>{ const el = qs(tabs[scope].loader); if (el) el.style.display = v ? '' : 'none'; };

  function paramsBase(scope){
    const usp = new URLSearchParams();
    const p  = state[scope].page || 1;
    const pp = Number(perPageSel?.value || 20);

    usp.set('page', p);
    usp.set('per_page', pp);

    // safe sorting
    usp.set('order_by', sortMap[sortKey] || 'bubble_game.created_at');
    usp.set('order_dir', (sortDir === 'asc') ? 'asc' : 'desc');

    if (scope === 'games'){
      if (q && q.value.trim()) usp.set('search', q.value.trim());

      // status filter (optional)
      const st = (filterStatusSel?.value ?? 'active');
      if (st) usp.set('status', st);
    }

    if (scope === 'archived'){
      usp.set('status', 'archived');
    }

    // ✅ Bin list supported by API now
    if (scope === 'deleted'){
      usp.set('only_deleted', '1');
      usp.set('order_by', 'bubble_game.deleted_at');
      usp.set('order_dir', 'desc');
    }

    return usp.toString();
  }

  function urlFor(scope){
    return `${API_BASE}?${paramsBase(scope)}`;
  }

  function settingsMini(r){
    const qRand = String(r.is_question_random||'no').toLowerCase()==='yes';
    const bRand = String(r.is_bubble_positions_random||'no').toLowerCase()==='yes';
    const skip  = String(r.allow_skip||'no').toLowerCase()==='yes';
    const sol   = String(r.show_solution_after||'').toLowerCase() || '-';

    const parts = [
      `Q Random: <b>${qRand ? 'Yes' : 'No'}</b>`,
      `Bubbles: <b>${bRand ? 'Random' : 'Fixed'}</b>`,
      `Skip: <b>${skip ? 'Yes' : 'No'}</b>`,
      `Solution: <b>${esc(sol)}</b>`
    ];
    return `<div class="small text-muted" style="line-height:1.25;">${parts.join('<br>')}</div>`;
  }

  function actionMenu(scope, r){
    const key = r.uuid || r.id;

    if (scope === 'deleted'){
      return `
        <div class="dropdown text-end" data-bs-display="static">
          <button type="button" class="btn btn-light btn-sm dd-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
            <i class="fa fa-ellipsis-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><button class="dropdown-item" data-act="restore" data-key="${esc(key)}" data-name="${esc(r.title||'')}">
              <i class="fa fa-rotate-left"></i> Restore
            </button></li>
            <li><button class="dropdown-item text-danger" data-act="force" data-key="${esc(key)}" data-name="${esc(r.title||'')}">
              <i class="fa fa-skull-crossbones"></i> Delete Permanently
            </button></li>
          </ul>
        </div>`;
    }

    const status = String(r.status||'').toLowerCase();
    const isArchived = (status === 'archived');

    return `
      <div class="dropdown text-end" data-bs-display="static">
        <button type="button" class="btn btn-light btn-sm dd-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Actions">
          <i class="fa fa-ellipsis-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><button class="dropdown-item" data-act="edit" data-key="${esc(key)}" data-name="${esc(r.title||'')}">
            <i class="fa fa-pen-to-square"></i> Edit
          </button></li>

          <li><button class="dropdown-item" data-act="duplicate" data-key="${esc(key)}" data-name="${esc(r.title||'')}">
            <i class="fa fa-copy"></i> Duplicate
          </button></li>

          <li><hr class="dropdown-divider"></li>

          <li><a class="dropdown-item" href="/bubble-games/questions/manage?game=${esc(key)}">
            <i class="fa fa-list-check"></i> Manage Questions
          </a></li>

          <li><hr class="dropdown-divider"></li>

          ${isArchived
            ? `<li><button class="dropdown-item" data-act="unarchive" data-key="${esc(key)}" data-name="${esc(r.title||'')}">
                <i class="fa fa-box-open"></i> Unarchive
              </button></li>`
            : `<li><button class="dropdown-item" data-act="archive" data-key="${esc(key)}" data-name="${esc(r.title||'')}">
                <i class="fa fa-box-archive"></i> Archive
              </button></li>`
          }

          <li><button class="dropdown-item text-danger" data-act="delete" data-key="${esc(key)}" data-name="${esc(r.title||'')}">
            <i class="fa fa-trash"></i> Delete
          </button></li>
        </ul>
      </div>`;
  }

  function rowHTML(scope, r){
    const isArchived = String(r.status||'').toLowerCase()==='archived';
    const isDeleted  = !!r.deleted_at;

    const title = esc(r.title || '-');
    const desc  = (r.description ? esc(r.description) : '');
    const created = fmtDate(r.created_at);
    const deleted = fmtDate(r.deleted_at);

    const creator = (r.creator_name || r.creator_email)
      ? `<div class="text-muted small">by ${esc(r.creator_name || r.creator_email)}</div>`
      : '';

    let tr = document.createElement('tr');
    if (isArchived && scope !== 'deleted') tr.classList.add('is-archived');
    if (isDeleted || scope === 'deleted') tr.classList.add('is-deleted');

    if (scope === 'deleted'){
      tr.innerHTML = `
        <td>
          <div class="fw-semibold">${title}</div>
          ${creator}
          ${desc ? `<div class="text-muted small">${desc}</div>` : ``}
        </td>
        <td>${deleted}</td>
        <td class="text-end">${actionMenu(scope, r)}</td>
      `;
      return tr;
    }

    if (scope === 'archived'){
      tr.innerHTML = `
        <td>
          <div class="fw-semibold">
            <a href="${basePanel}/bubble-games/${encodeURIComponent(r.uuid || r.id)}" class="link-offset-2 link-underline-opacity-0">${title}</a>
          </div>
          ${creator}
          ${desc ? `<div class="text-muted small">${desc}</div>` : ``}
        </td>
        <td>${Number(r.max_attempts ?? 1)} attempt(s)</td>
        <td>${Number(r.per_question_time_sec ?? 30)} sec</td>
        <td>${settingsMini(r)}</td>
        <td>${created}</td>
        <td class="text-end">${actionMenu(scope, r)}</td>
      `;
      return tr;
    }

    // games
    tr.innerHTML = `
      <td>
        <div class="fw-semibold">
          <a href="${basePanel}/bubble-games/${encodeURIComponent(r.uuid || r.id)}" class="link-offset-2 link-underline-opacity-0">${title}</a>
        </div>
        ${creator}
        ${desc ? `<div class="text-muted small">${desc}</div>` : ``}
      </td>
      <td>${badgeStatus(r.status || '-')}</td>
      <td>${Number(r.max_attempts ?? 1)} attempt(s)</td>
      <td>${Number(r.per_question_time_sec ?? 30)} sec</td>
      <td>${settingsMini(r)}</td>
      <td>${created}</td>
      <td class="text-end">${actionMenu(scope, r)}</td>
    `;
    tr.dataset.key = r.uuid || r.id;
    tr.dataset.status = String(r.status||'').toLowerCase();
    tr.dataset.name = r.title || '';
    return tr;
  }

  async function load(scope){
    const refs  = tabs[scope];
    const rowsEl= qs(refs.rows);
    const empty = qs(refs.empty);
    const pager = qs(refs.pager);
    const meta  = qs(refs.meta);

    rowsEl.querySelectorAll('tr:not([id^="loaderRow"])').forEach(n=>n.remove());
    if (empty) empty.style.display='none';
    if (pager) pager.innerHTML='';
    if (meta) meta.textContent='—';
    showLoader(scope, true);

    try{
      const res = await fetch(urlFor(scope), {
        headers:{
          'Authorization':'Bearer '+TOKEN,
          'Accept':'application/json'
        }
      });

      const json = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(json?.message || 'Load failed');

      const items = Array.isArray(json?.data) ? json.data : [];
      const pg    = json?.pagination || {};

      if (items.length === 0 && empty) empty.style.display='';

      const frag = document.createDocumentFragment();
      items.forEach(r => frag.appendChild(rowHTML(scope, r)));
      rowsEl.appendChild(frag);

      const total      = Number(pg.total ?? items.length ?? 0);
      const perPage    = Number(pg.per_page ?? 20);
      const current    = Number(pg.current_page ?? 1);
      const totalPages = Math.max(1, Number(pg.last_page ?? Math.ceil(total/perPage) ?? 1));

      function li(disabled, active, label, target){
        const cls=['page-item',disabled?'disabled':'',active?'active':''].filter(Boolean).join(' ');
        const href=disabled?'#':'javascript:void(0)';
        return `<li class="${cls}"><a class="page-link" href="${href}" data-page="${target||''}">${label}</a></li>`;
      }

      let html='';
      html += li(current<=1,false,'Previous',current-1);

      const w=3, start=Math.max(1,current-w), end=Math.min(totalPages,current+w);
      if (start>1){ html += li(false,false,1,1); if(start>2) html+='<li class="page-item disabled"><span class="page-link">…</span></li>'; }
      for(let p=start;p<=end;p++) html += li(false,p===current,p,p);
      if (end<totalPages){ if(end<totalPages-1) html+='<li class="page-item disabled"><span class="page-link">…</span></li>'; html+=li(false,false,totalPages,totalPages); }

      html += li(current>=totalPages,false,'Next',current+1);

      if (pager){
        pager.innerHTML = html;
        pager.querySelectorAll('a.page-link[data-page]').forEach(a=>{
          a.addEventListener('click',()=>{
            const target = Number(a.dataset.page);
            if(!target || target===state[scope].page) return;
            state[scope].page = Math.max(1,target);
            load(scope);
            window.scrollTo({top:0,behavior:'smooth'});
          });
        });
      }

      if (meta){
        const from = pg.from ?? ((current-1)*perPage + 1);
        const to   = pg.to ?? Math.min(current*perPage, total);
        meta.textContent = total
          ? `Showing ${from}–${to} of ${total} (page ${current} of ${totalPages})`
          : `Showing 0–0 of 0`;
      }

    }catch(e){
      console.error(e);
      if (empty) empty.style.display='';
      if (meta) meta.textContent='Failed to load';
      err(e.message || 'Load error');
    }finally{
      showLoader(scope, false);
    }
  }

  /* ========= Sorting (Games table) ========= */
  qsa('#tab-games thead th.sortable').forEach(th=>{
    th.addEventListener('click', ()=>{
      const key = th.dataset.col;
      if (!sortMap[key]) return;

      if (sortKey === key) sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
      else { sortKey = key; sortDir = (key === 'title') ? 'asc' : 'desc'; }

      state.games.page = 1;
      load('games');

      qsa('#tab-games thead th.sortable').forEach(t=>t.classList.remove('asc','desc'));
      th.classList.add(sortDir === 'asc' ? 'asc' : 'desc');
    });
  });

  /* ========= Filters ========= */
  let srchT;
  q?.addEventListener('input', ()=>{
    clearTimeout(srchT);
    srchT = setTimeout(()=>{ state.games.page=1; load('games'); }, 350);
  });

  perPageSel?.addEventListener('change', ()=>{
    state.games.page=1;
    load('games');
  });

  btnApplyFilters?.addEventListener('click', ()=>{
    const m = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
    m?.hide();
    state.games.page = 1;
    load('games');
  });

  btnReset?.addEventListener('click', ()=>{
    if (q) q.value = '';
    if (perPageSel) perPageSel.value = '20';
    if (filterStatusSel) filterStatusSel.value = 'active';
    sortKey = 'created_at';
    sortDir = 'desc';
    state.games.page=1;
    load('games');
  });

  /* ========= Tab change => load on demand ========= */
  document.querySelector('a[href="#tab-games"]')?.addEventListener('shown.bs.tab', ()=> load('games'));
  document.querySelector('a[href="#tab-archived"]')?.addEventListener('shown.bs.tab', ()=> load('archived'));
  document.querySelector('a[href="#tab-deleted"]')?.addEventListener('shown.bs.tab', ()=> load('deleted'));

  /* ========= Initial load ========= */
  load('games');

  /* ========= Row action handlers ========= */
  document.addEventListener('click', async (e)=>{
    const it = e.target.closest('.dropdown-item[data-act]');
    if (!it) return;

    const act  = it.dataset.act;
    const key  = it.dataset.key;
    const name = it.dataset.name || 'this game';

    if (act === 'edit'){
      location.href = `/bubble-games/create?edit=${encodeURIComponent(key)}`;
      return;
    }

    if (act === 'archive'){
      const {isConfirmed} = await Swal.fire({
        icon:'question',
        title:'Archive game?',
        html:`"${esc(name)}"`,
        showCancelButton:true,
        confirmButtonText:'Archive',
        confirmButtonColor:'#8b5cf6'
      });
      if (!isConfirmed) return;

      await callUpdate(key, { status:'archived' }, 'Game archived');
      load('games');
      return;
    }

    if (act === 'unarchive'){
      const {isConfirmed} = await Swal.fire({
        icon:'question',
        title:'Unarchive game?',
        html:`"${esc(name)}"`,
        showCancelButton:true,
        confirmButtonText:'Unarchive',
        confirmButtonColor:'#10b981'
      });
      if (!isConfirmed) return;

      await callUpdate(key, { status:'active' }, 'Game unarchived');
      load('archived');
      return;
    }

    if (act === 'delete'){
      const {isConfirmed} = await Swal.fire({
        icon:'warning',
        title:'Delete (soft)?',
        html:`This moves "${esc(name)}" to Bin.`,
        showCancelButton:true,
        confirmButtonText:'Delete',
        confirmButtonColor:'#ef4444'
      });
      if (!isConfirmed) return;

      try{
        const res = await fetch(`${API_BASE}/${encodeURIComponent(key)}`, {
          method:'DELETE',
          headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
        });
        const j = await res.json().catch(()=> ({}));
        if (!res.ok) throw new Error(j?.message || 'Delete failed');
        ok('Moved to Bin');
        load('games');
      }catch(ex){
        err(ex.message || 'Delete failed');
      }
      return;
    }

    if (act === 'restore'){
      const {isConfirmed} = await Swal.fire({
        icon:'question',
        title:'Restore game?',
        html:`"${esc(name)}" will be restored.`,
        showCancelButton:true,
        confirmButtonText:'Restore',
        confirmButtonColor:'#0ea5e9'
      });
      if (!isConfirmed) return;

      try{
        const res = await fetch(`${API_BASE}/${encodeURIComponent(key)}/restore`, {
          method:'POST',
          headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
        });
        const j = await res.json().catch(()=> ({}));
        if (!res.ok) throw new Error(j?.message || 'Restore failed');
        ok('Game restored');
        load('deleted');
        load('games');
      }catch(ex){
        err(ex.message || 'Restore failed');
      }
      return;
    }

    if (act === 'force'){
      const {isConfirmed} = await Swal.fire({
        icon:'warning',
        title:'Delete permanently?',
        html:`This cannot be undone.<br>"${esc(name)}"`,
        showCancelButton:true,
        confirmButtonText:'Delete permanently',
        confirmButtonColor:'#dc2626'
      });
      if (!isConfirmed) return;

      try{
        const res = await fetch(`${API_BASE}/${encodeURIComponent(key)}/force`, {
          method:'DELETE',
          headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
        });
        const j = await res.json().catch(()=> ({}));
        if (!res.ok) throw new Error(j?.message || 'Force delete failed');
        ok('Permanently deleted');
        load('deleted');
      }catch(ex){
        err(ex.message || 'Force delete failed');
      }
      return;
    }

    if (act === 'duplicate'){
      const {isConfirmed} = await Swal.fire({
        icon:'question',
        title:'Duplicate game?',
        html:`Create a copy of "${esc(name)}"`,
        showCancelButton:true,
        confirmButtonText:'Duplicate',
        confirmButtonColor:'#22c55e'
      });
      if (!isConfirmed) return;

      try{
        const res = await fetch(`${API_BASE}/${encodeURIComponent(key)}/duplicate`, {
          method:'POST',
          headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
        });
        const j = await res.json().catch(()=> ({}));
        if (!res.ok) throw new Error(j?.message || 'Duplicate failed');
        ok('Duplicated');
        load('games');
      }catch(ex){
        err(ex.message || 'Duplicate failed');
      }
      return;
    }
  });

  async function callUpdate(key, payload, doneMsg){
    try{
      const res = await fetch(`${API_BASE}/${encodeURIComponent(key)}`, {
        method:'PATCH',
        headers:{
          'Authorization':'Bearer '+TOKEN,
          'Content-Type':'application/json',
          'Accept':'application/json'
        },
        body: JSON.stringify(payload || {})
      });
      const j = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(j?.message || 'Update failed');
      ok(doneMsg || 'Updated');
    }catch(e){
      err(e.message || 'Update failed');
    }
  }

})();
</script>
@endpush
