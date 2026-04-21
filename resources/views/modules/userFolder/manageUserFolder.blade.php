{{-- resources/views/modules/userFolders/manageUserFolders.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Manage User Folders')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ===== Shell ===== */
.uf-wrap{max-width:1140px;margin:16px auto 40px;overflow:visible}
.panel{background:var(--surface);border:1px solid var(--line-strong);border-radius:16px;box-shadow:var(--shadow-2);padding:14px}

/* Toolbar */
.mfa-toolbar .form-control{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .form-select{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
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

/* Status badges */
.table .badge.badge-success{background:var(--success-color)!important;color:#fff!important}
.table .badge.badge-secondary{background:#64748b!important;color:#fff!important}

/* Sorting */
.sortable{cursor:pointer;white-space:nowrap}
.sortable .caret{display:inline-block;margin-left:.35rem;opacity:.65}
.sortable.asc .caret::after{content:"▲";font-size:.7rem}
.sortable.desc .caret::after{content:"▼";font-size:.7rem}

/* Row cues */
tr.is-inactive td{background:color-mix(in oklab, var(--muted-color) 6%, transparent)}

/* Dropdowns inside table */
.table-wrap .dropdown{position:relative;z-index:6}
.table-wrap .dd-toggle{position:relative;z-index:7}
.dropdown [data-bs-toggle="dropdown"]{border-radius:10px}

/* Default dropdown menu */
.table-wrap .dropdown-menu{border-radius:12px;border:1px solid var(--line-strong);box-shadow:var(--shadow-2);min-width:220px;z-index:5000}

/* Portaled dropdown menu */
.dropdown-menu.dd-portal{
  position:fixed!important;left:0;top:0;transform:none!important;z-index:5000;
  border-radius:12px;border:1px solid var(--line-strong);box-shadow:var(--shadow-2);
  min-width:220px;background:var(--surface)
}
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
<div class="uf-wrap">

  {{-- ================= Tabs ================= --}}
  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#tab-active" role="tab" aria-selected="true">
        <i class="fa-solid fa-folder-open me-2"></i>Active
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#tab-inactive" role="tab" aria-selected="false">
        <i class="fa-solid fa-folder me-2"></i>Inactive
      </a>
    </li>
  </ul>

  <div class="tab-content mb-3">

    {{-- ========== TAB: Active ========== --}}
    <div class="tab-pane fade show active" id="tab-active" role="tabpanel">

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
          <a id="btnCreate" href="/user-folders/create" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i>New Folder
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
                  <th class="sortable" data-col="title">FOLDER <span class="caret"></span></th>
                  <th style="width:150px;">STATUS</th>
                  <th style="width:160px;">CREATED BY</th>
                  <th class="sortable" data-col="created_at" style="width:190px;">CREATED <span class="caret"></span></th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-active">
                <tr id="loaderRow-active" style="display:none;">
                  <td colspan="5" class="p-0">
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

          <div id="empty-active" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-folder-open mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No active folders found.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-active">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-active" class="pagination mb-0"></ul></nav>
          </div>
        </div>
      </div>
    </div>

    {{-- ========== TAB: Inactive ========== --}}
    <div class="tab-pane fade" id="tab-inactive" role="tabpanel">
      <div class="card table-wrap">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-borderless align-middle mb-0">
              <thead class="sticky-top">
                <tr>
                  <th class="sortable" data-col="title">FOLDER <span class="caret"></span></th>
                  <th style="width:160px;">CREATED BY</th>
                  <th class="sortable" data-col="created_at" style="width:190px;">CREATED <span class="caret"></span></th>
                  <th class="text-end" style="width:112px;">ACTIONS</th>
                </tr>
              </thead>
              <tbody id="rows-inactive">
                <tr id="loaderRow-inactive" style="display:none;">
                  <td colspan="4" class="p-0">
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

          <div id="empty-inactive" class="empty p-4 text-center" style="display:none;">
            <i class="fa fa-folder mb-2" style="font-size:32px; opacity:.6;"></i>
            <div>No inactive folders.</div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
            <div class="text-muted small" id="metaTxt-inactive">—</div>
            <nav style="position:relative; z-index:1;"><ul id="pager-inactive" class="pagination mb-0"></ul></nav>
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
        <h5 class="modal-title"><i class="fa fa-filter me-2"></i>Filter Folders</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label for="filterCreatedBy" class="form-label">Created By (User ID)</label>
          <input id="filterCreatedBy" type="number" class="form-control" placeholder="e.g., 1">
          <div class="text-muted small mt-1">Optional. Useful for audit/filtering folders created by a user.</div>
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

{{-- Assigned Users Modal --}}
<div class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fa fa-users me-2"></i>
          Folder Users
          <small class="text-muted d-block fs-6" id="usersFolderTitle"></small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="usersList" class="small text-muted">Loading…</div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
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
  /* ========= Auth ========= */
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  if (!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  /* ========= Toast helpers ========= */
  const okToast  = new bootstrap.Toast(document.getElementById('okToast'));
  const errToast = new bootstrap.Toast(document.getElementById('errToast'));
  const ok  = (m)=>{ document.getElementById('okMsg').textContent  = m||'Done'; okToast.show(); };
  const err = (m)=>{ document.getElementById('errMsg').textContent = m||'Something went wrong'; errToast.show(); };

  /* ========= DOM refs per tab ========= */
  const tabs = {
    active:   { rows:'#rows-active',   loader:'#loaderRow-active',   empty:'#empty-active',   meta:'#metaTxt-active',   pager:'#pager-active'   },
    inactive: { rows:'#rows-inactive', loader:'#loaderRow-inactive', empty:'#empty-inactive', meta:'#metaTxt-inactive', pager:'#pager-inactive' },
  };

  /* ========= Shared filter elements (Active tab only) ========= */
  const q = document.getElementById('q');
  const perPageSel      = document.getElementById('per_page');
  const btnReset        = document.getElementById('btnReset');

  const filterCreatedBy = document.getElementById('filterCreatedBy');
  const btnApplyFilters = document.getElementById('btnApplyFilters');

  /* ========= State ========= */
  let sort = '-created_at';
  const state = { active:{page:1}, inactive:{page:1} };

  /* ========= Utils ========= */
  const esc=(s)=>{const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;','`':'&#96;'}; return (s==null?'':String(s)).replace(/[&<>"'`]/g,ch=>m[ch]); };
  const fmtDate=(iso)=>{ if(!iso) return '-'; const d=new Date(iso); if(isNaN(d)) return esc(iso); return d.toLocaleString(undefined,{year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit'}); };
  const badgeStatus=(s)=>{
    const st = String(s||'').toLowerCase();
    if (st==='active') return `<span class="badge badge-success text-uppercase">ACTIVE</span>`;
    return `<span class="badge badge-secondary text-uppercase">${esc(st||'-')}</span>`;
  };

  const qs=(sel)=>document.querySelector(sel);
  const qsa=(sel)=>document.querySelectorAll(sel);
  const showLoader=(scope, v)=>{ qs(tabs[scope].loader).style.display = v ? '' : 'none'; };

  function paramsBase(scope){
    const usp = new URLSearchParams();
    const p  = state[scope].page || 1;
    const pp = Number(perPageSel?.value || 20);
    usp.set('page', p);
    usp.set('per_page', pp);
    usp.set('sort', sort);

    if (scope === 'active'){
      usp.set('status','active');
      if (q && q.value.trim()) usp.set('q', q.value.trim());
      if (filterCreatedBy && String(filterCreatedBy.value||'').trim()) usp.set('created_by', String(filterCreatedBy.value).trim());
    } else if (scope === 'inactive'){
      usp.set('status','inactive');
      // keep same search to help find in inactive too (optional)
      if (q && q.value.trim()) usp.set('q', q.value.trim());
      if (filterCreatedBy && String(filterCreatedBy.value||'').trim()) usp.set('created_by', String(filterCreatedBy.value).trim());
    }
    return usp.toString();
  }

  function urlFor(scope){
    return '/api/user-folders?' + paramsBase(scope);
  }

  /* ========= Row builders ========= */
  function actionMenu(scope, r){
    const key  = r.id; // use ID for API endpoints
    const name = esc(r.title || '');
    const status = String(r.status||'').toLowerCase();

    return `
      <div class="dropdown text-end" data-bs-display="static">
        <button type="button" class="btn btn-light btn-sm dd-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Actions">
          <i class="fa fa-ellipsis-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <button class="dropdown-item" data-act="edit" data-key="${key}" data-name="${name}">
              <i class="fa fa-pen-to-square"></i> Edit
            </button>
          </li>
          <li>
            <button class="dropdown-item" data-act="users" data-key="${key}" data-name="${name}">
              <i class="fa fa-users"></i> View Users
            </button>
          </li>
          <li><hr class="dropdown-divider"></li>

          ${status==='inactive'
            ? `<li>
                <button class="dropdown-item" data-act="activate" data-key="${key}" data-name="${name}">
                  <i class="fa fa-toggle-on"></i> Activate
                </button>
              </li>`
            : `<li>
                <button class="dropdown-item" data-act="deactivate" data-key="${key}" data-name="${name}">
                  <i class="fa fa-toggle-off"></i> Deactivate
                </button>
              </li>`
          }

          <li>
            <button class="dropdown-item text-danger" data-act="delete" data-key="${key}" data-name="${name}">
              <i class="fa fa-trash"></i> Delete
            </button>
          </li>
        </ul>
      </div>`;
  }

  function rowHTML(scope, r){
    const title = esc(r.title || '-');
    const created = fmtDate(r.created_at);
    const createdBy = r.created_by ? `#${esc(r.created_by)}` : '-';

    const tr = document.createElement('tr');
    if (String(r.status||'').toLowerCase()==='inactive') tr.classList.add('is-inactive');

    if (scope==='inactive'){
      tr.innerHTML = `
        <td>
          <div class="fw-semibold">${title}</div>
          <div class="text-muted small">${r.uuid ? esc(r.uuid) : ''}</div>
        </td>
        <td>${createdBy}</td>
        <td>${created}</td>
        <td class="text-end">${actionMenu(scope, r)}</td>`;
      return tr;
    }

    tr.innerHTML = `
      <td>
        <div class="fw-semibold">${title}</div>
        <div class="text-muted small">${r.uuid ? esc(r.uuid) : ''}</div>
      </td>
      <td>${badgeStatus(r.status)}</td>
      <td>${createdBy}</td>
      <td>${created}</td>
      <td class="text-end">${actionMenu(scope, r)}</td>`;
    return tr;
  }

  /* ========= Fetch & render ========= */
  async function load(scope){
    const refs = tabs[scope];
    const rowsEl = qs(refs.rows);
    const empty  = qs(refs.empty);
    const pager  = qs(refs.pager);
    const meta   = qs(refs.meta);

    rowsEl.querySelectorAll('tr:not([id^="loaderRow"])').forEach(n=>n.remove());
    empty.style.display='none';
    pager.innerHTML='';
    meta.textContent='—';
    showLoader(scope, true);

    try{
      const res = await fetch(urlFor(scope), {
        headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
      });
      const json = await res.json().catch(()=> ({}));

      if (!res.ok) throw new Error(json?.message || 'Load failed');

      const items = json?.data || [];
      const pagination = json?.pagination || json?.meta || {page:1, per_page:20, total:items.length};

      if (items.length===0) empty.style.display='';

      const frag = document.createDocumentFragment();
      items.forEach(r => frag.appendChild(rowHTML(scope, r)));
      rowsEl.appendChild(frag);

      const total   = Number(pagination.total||0);
      const perPage = Number(pagination.per_page||20);
      const current = Number(pagination.page||1);
      const totalPages = Math.max(1, Math.ceil(total / perPage));

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
        if(start>2) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
      }

      for(let p=start;p<=end;p++) html += li(false,p===current,p,p);

      if (end<totalPages){
        if(end<totalPages-1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
        html += li(false,false,totalPages,totalPages);
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

  /* ========= Sorting ========= */
  qsa('#tab-active thead th.sortable').forEach(th=>{
    th.addEventListener('click', ()=>{
      const col = th.dataset.col;
      if (sort === col) sort = '-'+col;
      else if (sort === '-'+col) sort = col;
      else sort = (col === 'created_at') ? '-created_at' : col;

      state.active.page = 1;
      load('active');

      qsa('#tab-active thead th.sortable').forEach(t=>t.classList.remove('asc','desc'));
      if (sort === col) th.classList.add('asc');
      else if (sort === '-'+col) th.classList.add('desc');
    });
  });

  /* ========= Filters ========= */
  let srchT;
  q?.addEventListener('input', ()=>{
    clearTimeout(srchT);
    srchT=setTimeout(()=>{
      state.active.page=1;
      state.inactive.page=1;
      load('active');
      // optional: update inactive too while searching
      load('inactive');
    }, 350);
  });

  btnApplyFilters?.addEventListener('click', ()=>{
    const filterModal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
    filterModal.hide();
    state.active.page=1;
    state.inactive.page=1;
    load('active');
    load('inactive');
  });

  btnReset?.addEventListener('click', ()=>{
    if (q) q.value='';
    if (filterCreatedBy) filterCreatedBy.value='';
    if (perPageSel) perPageSel.value='20';
    sort='-created_at';
    state.active.page=1;
    state.inactive.page=1;
    load('active');
    load('inactive');
  });

  perPageSel?.addEventListener('change', ()=>{
    state.active.page=1;
    state.inactive.page=1;
    load('active');
    load('inactive');
  });

  /* ========= Tab change => load on demand ========= */
  document.querySelector('a[href="#tab-active"]').addEventListener('shown.bs.tab', ()=> load('active'));
  document.querySelector('a[href="#tab-inactive"]').addEventListener('shown.bs.tab', ()=> load('inactive'));

  /* ========= Initial load ========= */
  load('active');

  /* ========= Row actions ========= */
  const usersModal = new bootstrap.Modal(document.getElementById('usersModal'));
  const usersList  = document.getElementById('usersList');
  const usersTitle = document.getElementById('usersFolderTitle');

  document.addEventListener('click', async (e)=>{
    const it = e.target.closest('.dropdown-item[data-act]');
    if(!it) return;

    const act  = it.dataset.act;
    const key  = it.dataset.key;
    const name = it.dataset.name || 'this folder';

    if (act === 'edit'){
      location.href = `/user-folders/create?edit=${encodeURIComponent(key)}`;
      return;
    }

    if (act === 'users'){
      await openUsers(key, name);
      return;
    }

    if (act === 'activate'){
      const {isConfirmed} = await Swal.fire({
        icon:'question',
        title:'Activate folder?',
        html:`"${esc(name)}" will become active.`,
        showCancelButton:true,
        confirmButtonText:'Activate',
        confirmButtonColor:'#10b981'
      });
      if(!isConfirmed) return;

      await updateFolderStatus(key, 'active', 'Folder activated');
      load('active'); load('inactive');
      return;
    }

    if (act === 'deactivate'){
      const {isConfirmed} = await Swal.fire({
        icon:'question',
        title:'Deactivate folder?',
        html:`"${esc(name)}" will become inactive.`,
        showCancelButton:true,
        confirmButtonText:'Deactivate',
        confirmButtonColor:'#64748b'
      });
      if(!isConfirmed) return;

      await updateFolderStatus(key, 'inactive', 'Folder deactivated');
      load('active'); load('inactive');
      return;
    }

    if (act === 'delete'){
      const {isConfirmed} = await Swal.fire({
        icon:'warning',
        title:'Delete folder?',
        html:`Folder "${esc(name)}" will be removed (soft delete) and users will be unlinked.`,
        showCancelButton:true,
        confirmButtonText:'Delete',
        confirmButtonColor:'#ef4444'
      });
      if(!isConfirmed) return;

      try{
        const res = await fetch(`/api/user-folders/${encodeURIComponent(key)}`, {
          method:'DELETE',
          headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
        });
        const j = await res.json().catch(()=> ({}));
        if(!res.ok) throw new Error(j?.message || 'Delete failed');
        ok('Folder deleted');
        load('active'); load('inactive');
      }catch(ex){
        console.error(ex);
        err(ex.message || 'Delete failed');
      }
      return;
    }
  });

  async function updateFolderStatus(id, statusVal, doneMsg){
    try{
      const res = await fetch(`/api/user-folders/${encodeURIComponent(id)}`, {
        method: 'PUT',
        headers:{
          'Authorization':'Bearer '+TOKEN,
          'Content-Type':'application/json',
          'Accept':'application/json'
        },
        body: JSON.stringify({ status: statusVal })
      });
      const j = await res.json().catch(()=> ({}));
      if(!res.ok) throw new Error(j?.message || 'Status update failed');
      ok(doneMsg || 'Updated');
    }catch(ex){
      console.error(ex);
      err(ex.message || 'Status update failed');
    }
  }

  async function openUsers(id, name){
    usersTitle.textContent = name ? String(name) : 'Folder';
    usersList.innerHTML = `<div class="small text-muted">Loading users…</div>`;
    usersModal.show();

    try{
      const res = await fetch(`/api/user-folders/${encodeURIComponent(id)}`, {
        headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
      });
      const j = await res.json().catch(()=> ({}));
      if(!res.ok) throw new Error(j?.message || 'Failed to load users');

      const users = j.assigned_users || [];
      if(!users.length){
        usersList.innerHTML = `<div class="small text-muted">No users assigned to this folder.</div>`;
        return;
      }

      const html = users.map(u => `
        <div class="border rounded-3 p-2 mb-2">
          <div class="fw-semibold">${esc(u.name || 'User')}</div>
          <div class="small text-muted">${esc(u.email || '-')} • #${esc(u.id)}</div>
        </div>
      `).join('');
      usersList.innerHTML = html;

    }catch(ex){
      console.error(ex);
      usersList.innerHTML = `<div class="small text-danger">Failed to load users.</div>`;
      err(ex.message || 'Failed to load users');
    }
  }

})();
</script>
@endpush
