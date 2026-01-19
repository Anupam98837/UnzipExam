{{-- resources/views/modules/student_results/myResults.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','My Results')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ===== Shell ===== */
.sr-wrap{max-width:1140px;margin:16px auto 40px;overflow:visible}
.panel{background:var(--surface);border:1px solid var(--line-strong);border-radius:16px;box-shadow:var(--shadow-2);padding:14px}

/* Toolbar */
.mfa-toolbar .form-control{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .form-select{height:40px;border-radius:12px;border:1px solid var(--line-strong);background:var(--surface)}
.mfa-toolbar .btn{height:40px;border-radius:12px}
.mfa-toolbar .btn-light{background:var(--surface);border:1px solid var(--line-strong)}
.mfa-toolbar .btn-primary{background:var(--primary-color);border:none}

/* Table Card */
.table-wrap.card{position:relative;border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
.table-wrap .card-body{overflow:hidden}
.table{--bs-table-bg:transparent}
.table thead th{font-weight:600;color:var(--muted-color);font-size:13px;border-bottom:1px solid var(--line-strong);background:var(--surface);white-space:nowrap}
.table thead.sticky-top{z-index:3}
.table tbody tr{border-top:1px solid var(--line-soft)}
.table tbody tr:hover{background:var(--page-hover)}
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

/* ✅ Hide native horizontal scrollbar of table area (keep scroll working) */
.table-responsive{
  overflow-x:auto !important;
  scrollbar-width:none;          /* Firefox */
  -ms-overflow-style:none;       /* IE/Edge old */
}
.table-responsive::-webkit-scrollbar{ height:0px; }
.table-responsive::-webkit-scrollbar-thumb{ background:transparent; }

/* Badges */
.badge-pill{
  display:inline-flex;align-items:center;gap:.35rem;
  padding:.25rem .55rem;border-radius:999px;
  border:1px solid var(--line-strong);
  background:color-mix(in oklab, var(--muted-color) 10%, transparent)
}
.badge-success{background:var(--success-color)!important;color:#fff!important;border:none!important}
.badge-secondary{background:#64748b!important;color:#fff!important;border:none!important}

/* Empty & loader */
.empty{color:var(--muted-color)}
.placeholder{background:linear-gradient(90deg,#00000010,#00000005,#00000010);border-radius:8px}

/* Dark */
html.theme-dark .panel,
html.theme-dark .table-wrap.card{background:#0f172a;border-color:var(--line-strong)}
html.theme-dark .table thead th{background:#0f172a;border-color:var(--line-strong);color:#94a3b8}
html.theme-dark .table tbody tr{border-color:var(--line-soft)}
</style>
@endpush

@section('content')
<div class="sr-wrap">

  {{-- Toolbar --}}
  <div class="row align-items-center g-2 mb-3 mfa-toolbar panel">
    <div class="col-12 d-flex align-items-center flex-wrap gap-2">

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
        <input id="q" type="text" class="form-control ps-5" placeholder="Search game / folder…">
        <i class="fa fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);opacity:.6;"></i>
      </div>

      <div style="min-width:220px;">
        <select id="type" class="form-select">
          <option value="">All Results</option>
          <option value="door_game">Door Game</option>
          <option value="quizz">Quizz</option>
          <option value="bubble_game">Bubble Game</option>
        </select>
      </div>

      <button id="btnReset" class="btn btn-primary">
        <i class="fa fa-rotate-left me-1"></i>Reset
      </button>

    </div>
  </div>

  {{-- Table --}}
  <div class="card table-wrap">
    <div class="card-body p-0">

      <div class="table-scrollwrap">
        <div class="table-responsive" id="tr-student">
          <table class="table table-hover table-borderless align-middle mb-0" id="tbl-student">
            <thead class="sticky-top">
              <tr>
                <th style="width:120px;">MODULE</th>
                <th>GAME / TEST</th>
                <th style="width:120px;">ATTEMPT</th>
                <th style="width:120px;">SCORE</th>
                <th style="display:none; width:110px;">%</th>
                <th style="display:none; width:220px;">FOLDER</th>
                <th style="width:170px;">SUBMITTED</th>
                <th class="text-end" style="width:140px;">ACTION</th>
              </tr>
            </thead>

            <tbody id="rows-student">
              <tr id="loaderRow-student" style="display:none;">
                <td colspan="8" class="p-0">
                  <div class="p-4">
                    <div class="placeholder-wave">
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
        <div class="x-scrollbar" id="xs-student">
          <div class="x-scrollbar-inner"></div>
        </div>
      </div>

      <div id="empty-student" class="empty p-4 text-center" style="display:none;">
        <i class="fa fa-circle-info mb-2" style="font-size:32px; opacity:.6;"></i>
        <div>No published results found.</div>
      </div>

      <div class="d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
        <div class="text-muted small" id="metaTxt-student">—</div>
        <nav style="position:relative; z-index:1;">
          <ul id="pager-student" class="pagination mb-0"></ul>
        </nav>
      </div>

    </div>
  </div>

</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2100">
  <div id="errToast" class="toast text-bg-danger border-0">
    <div class="d-flex">
      <div id="errMsg" class="toast-body">Something went wrong</div>
      <button class="btn-close btn-close-white m-auto me-2" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function(){
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  if (!TOKEN){
    alert('Session expired. Please login again.');
    location.href = '/';
    return;
  }

  // ✅ API (published only)
  const API_MY_RESULTS = '/api/student-results/my';

  // DOM
  const perPageSel = document.getElementById('per_page');
  const q = document.getElementById('q');
  const typeSel = document.getElementById('type');
  const btnReset = document.getElementById('btnReset');

  const rowsEl = document.getElementById('rows-student');
  const loaderRow = document.getElementById('loaderRow-student');
  const emptyEl = document.getElementById('empty-student');
  const metaEl = document.getElementById('metaTxt-student');
  const pagerEl = document.getElementById('pager-student');

  const tr = document.getElementById('tr-student');
  const xs = document.getElementById('xs-student');
  const tbl = document.getElementById('tbl-student');

  // Toast
  const errToast = new bootstrap.Toast(document.getElementById('errToast'));
  const err = (m)=>{ document.getElementById('errMsg').textContent = m || 'Something went wrong'; errToast.show(); };

  const state = { page: 1 };

  function esc(s){
    if (s === null || s === undefined) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function fmtDate(iso){
    if(!iso) return '-';
    const d=new Date(iso);
    if(isNaN(d)) return esc(iso);
    return d.toLocaleString(undefined,{year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit'});
  }

  function moduleBadge(mod){
    const v = String(mod||'').toLowerCase();
    if (v === 'door_game')   return `<span class="badge-pill"><i class="fa fa-door-open"></i> Door</span>`;
    if (v === 'quizz')       return `<span class="badge-pill"><i class="fa fa-clipboard-question"></i> Quizz</span>`;
    if (v === 'bubble_game') return `<span class="badge-pill"><i class="fa fa-circle"></i> Bubble</span>`;
    return `<span class="badge-pill"><i class="fa fa-layer-group"></i> ${esc(mod||'-')}</span>`;
  }

  // ✅ REQUIRED URL mapping (your exact paths)
  function viewUrlFor(item){
    const rid = item?.result?.uuid || '';
    const mod = String(item?.module || '').toLowerCase();

    if (!rid) return '#';

    if (mod === 'door_game')   return `/decision-making-test/results/${encodeURIComponent(rid)}/view`;
    if (mod === 'quizz')       return `/exam/results/${encodeURIComponent(rid)}/view`;
    if (mod === 'bubble_game') return `/test/results/${encodeURIComponent(rid)}/view`;

    return '#';
  }

  function folderName(item){
    const s = item?.student || {};
    return s.folder_title || s.user_folder_name || '-';
  }

  function buildParams(){
    const usp = new URLSearchParams();
    usp.set('page', state.page);
    usp.set('per_page', Number(perPageSel.value || 20));

    const qq = (q.value || '').trim();
    if (qq) usp.set('q', qq);

    const type = typeSel.value || '';
    if (type) usp.set('type', type);

    return usp.toString();
  }

  function showLoader(v){
    loaderRow.style.display = v ? '' : 'none';
  }

  function syncXScroll(){
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

    window.addEventListener('resize', ()=>{
      inner.style.width = tbl.scrollWidth + 'px';
      xs.classList.toggle('hidden', !(tbl.scrollWidth > tr.clientWidth + 2));
    });
  }

  async function load(){
    emptyEl.style.display = 'none';
    metaEl.textContent = '—';
    pagerEl.innerHTML = '';

    // clear rows except loader
    rowsEl.querySelectorAll('tr:not(#loaderRow-student)').forEach(n=>n.remove());

    showLoader(true);

    try{
      const url = `${API_MY_RESULTS}?${buildParams()}`;

      const res = await fetch(url, {
        headers:{
          'Authorization':'Bearer ' + TOKEN,
          'Accept':'application/json'
        }
      });

      const json = await res.json().catch(()=> ({}));
      if (!res.ok || json?.success === false) throw new Error(json?.message || 'Failed to load');

      const items = Array.isArray(json?.data) ? json.data : [];
      const p = json?.pagination || {};
      const total = Number(p.total ?? items.length ?? 0);
      const per = Number(p.per_page ?? perPageSel.value ?? 20);
      const page = Number(p.page ?? state.page ?? 1);
      const totalPages = Number(p.total_pages ?? Math.max(1, Math.ceil(total / per)));

      if (!items.length) emptyEl.style.display = '';

      const frag = document.createDocumentFragment();

      items.forEach(item=>{
        const mod = item?.module || '-';
        const title = item?.game?.title || '-';
        const result = item?.result || {};

        const tr = document.createElement('tr');

        tr.innerHTML = `
          <td>${moduleBadge(mod)}</td>
          <td>
            <div class="fw-semibold">${esc(title)}</div>
            <div class="text-muted small d-none">Result UUID: ${esc(result.uuid || '-')}</div>
          </td>
          <td><span class="badge-pill"><i class="fa fa-repeat"></i> #${Number(result.attempt_no || 0)}</span></td>
          <td><div class="fw-semibold">${Number(result.score || 0)}</div></td>
          <td>${esc(fmtDate(result.created_at || result.result_created_at))}</td>
          <td class="text-end">
            <a href="${viewUrlFor(item)}"
               class="btn btn-primary btn-sm"
               ${!result.uuid ? 'disabled' : ''}>
              <i class="fa fa-eye me-1"></i>View Result
            </a>
          </td>
        `;

        frag.appendChild(tr);
      });

      rowsEl.appendChild(frag);

      // pagination
      function li(disabled, active, label, target){
        const cls=['page-item',disabled?'disabled':'',active?'active':''].filter(Boolean).join(' ');
        const href=disabled?'#':'javascript:void(0)';
        return `<li class="${cls}"><a class="page-link" href="${href}" data-page="${target||''}">${label}</a></li>`;
      }

      let html='';
      html += li(page<=1,false,'Previous',page-1);

      const w=3, start=Math.max(1,page-w), end=Math.min(totalPages,page+w);
      if (start>1){
        html += li(false,false,1,1);
        if(start>2) html+='<li class="page-item disabled"><span class="page-link">…</span></li>';
      }
      for(let p2=start;p2<=end;p2++) html += li(false,p2===page,p2,p2);
      if (end<totalPages){
        if(end<totalPages-1) html+='<li class="page-item disabled"><span class="page-link">…</span></li>';
        html+=li(false,false,totalPages,totalPages);
      }

      html += li(page>=totalPages,false,'Next',page+1);
      pagerEl.innerHTML = html;

      pagerEl.querySelectorAll('a.page-link[data-page]').forEach(a=>{
        a.addEventListener('click', ()=>{
          const target = Number(a.dataset.page);
          if(!target || target === state.page) return;
          state.page = Math.max(1, target);
          load();
          window.scrollTo({top:0,behavior:'smooth'});
        });
      });

      metaEl.textContent = `Showing page ${page} of ${totalPages} — ${total} result(s)`;

      // bottom scrollbar sync
      syncXScroll();

    }catch(e){
      console.error(e);
      emptyEl.style.display = '';
      metaEl.textContent = 'Failed to load';
      err(e.message || 'Load failed');
    }finally{
      showLoader(false);
    }
  }

  // search debounce
  let t;
  q.addEventListener('input', ()=>{
    clearTimeout(t);
    t = setTimeout(()=>{
      state.page = 1;
      load();
    }, 350);
  });

  typeSel.addEventListener('change', ()=>{
    state.page = 1;
    load();
  });

  perPageSel.addEventListener('change', ()=>{
    state.page = 1;
    load();
  });

  btnReset.addEventListener('click', ()=>{
    q.value = '';
    typeSel.value = '';
    perPageSel.value = '20';
    state.page = 1;
    load();
  });

  // init
  load();

})();
</script>
@endpush
