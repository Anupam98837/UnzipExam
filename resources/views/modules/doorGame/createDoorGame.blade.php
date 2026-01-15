{{-- resources/views/modules/doorGame/createDoorGame.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Create Door Game')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
  .dg-wrap{max-width:1100px;margin:14px auto 40px}
  .dg.card{border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
  .dg .card-header{background:var(--surface);border-bottom:1px solid var(--line-strong);padding:16px 18px}
  .dg-head{display:flex;align-items:center;gap:10px}
  .dg-head i{color:var(--accent-color)}
  .dg-head strong{color:var(--ink);font-family:var(--font-head);font-weight:700}
  .dg-head .hint{color:var(--muted-color);font-size:var(--fs-13)}

  .section-title{font-weight:600;color:var(--ink);font-family:var(--font-head);margin:12px 2px 14px}
  .divider-soft{height:1px;background:var(--line-soft);margin:10px 0 16px}

  /* RTE */
  .toolbar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px}
  .tool{border:1px solid var(--line-strong);border-radius:10px;background:#fff;padding:6px 9px;cursor:pointer}
  .tool:hover{background:var(--page-hover)}
  .rte-wrap{position:relative}
  .rte{
    min-height:160px;max-height:520px;overflow:auto;
    border:1px solid var(--line-strong);border-radius:12px;background:#fff;
    padding:12px;line-height:1.6;outline:none
  }
  .rte:focus{box-shadow:var(--ring);border-color:var(--accent-color)}
  .rte-ph{position:absolute;top:12px;left:12px;color:#9aa3b2;pointer-events:none;font-size:var(--fs-14)}
  .rte.has-content + .rte-ph{display:none}

  /* Inputs polish */
  .form-control:focus, .form-select:focus{
    box-shadow:0 0 0 3px color-mix(in oklab, var(--accent-color) 20%, transparent);
    border-color:var(--accent-color)
  }
  .input-group-text{background:var(--surface);border-color:var(--line-strong)}
  .tiny{font-size:12px;color:#6b7280}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}

  /* Errors */
  .err{font-size:12px;color:var(--danger-color);display:none;margin-top:6px}
  .err:not(:empty){display:block}

  /* Busy overlay */
  .dim{position:absolute;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.06);z-index:2}
  .dim.show{display:flex}
  .spin{width:18px;height:18px;border:3px solid #0001;border-top-color:var(--accent-color);border-radius:50%;animation:rot 1s linear infinite}
  @keyframes rot{to{transform:rotate(360deg)}}

  /* Button loading */
  .btn-loading{pointer-events:none;opacity:.85}
  .btn-loading .btn-label{visibility:hidden}
  .btn-loading .btn-spinner{display:inline-block !important}
  .btn-spinner{display:none;width:1rem;height:1rem;border:.2rem solid #0001;border-top-color:#fff;border-radius:50%;vertical-align:-.125em;animation:rot 1s linear infinite}

  /* ===== Grid Table UI ===== */
  .grid-wrap{
    border:1px solid var(--line-strong);
    /* border-radius:14px; */
    background:color-mix(in oklab, var(--surface) 85%, #fff);
    padding:12px;
  }
  .grid-scroll{overflow:auto;max-width:100%}
  .grid-table{
    border-collapse:separate;
    border-spacing:0;
    margin:0 auto;
    background:transparent;
  }
  .grid-cell{
    width:64px;height:64px;
    min-width:64px;min-height:64px;
    border:1px solid var(--line-soft);
    background:#fff;
    border-radius:12px;
    position:relative;
    cursor:pointer;
    user-select:none;
    transition:transform .08s ease, box-shadow .08s ease, border-color .08s ease;
  }
  .grid-cell:hover{
    transform:translateY(-1px);
    box-shadow:var(--shadow-1);
    border-color:color-mix(in oklab, var(--accent-color) 35%, var(--line-soft));
  }
  .grid-cell .idx{
    position:absolute;top:6px;left:8px;
    font-size:11px;color:#8b97a8;
  }
  .grid-cell .ico{
    position:absolute;inset:0;
    display:flex;align-items:center;justify-content:center;
    font-size:20px;
    color:var(--accent-color);
  }

  /* Barrier visuals (thin lines inside cell) */
 /* ===== Better Professional Grid UI ===== */
.grid-wrap{
  border:1px solid var(--line-strong);
  border-radius:16px;
  background:color-mix(in oklab, var(--surface) 85%, #fff);
  padding:14px;
}

.grid-scroll{
  overflow:auto;
  max-width:100%;
  padding:6px;
}

.grid-cell{
  width:72px;height:72px;
  min-width:72px;min-height:72px;
  border:1px solid color-mix(in oklab, var(--line-strong) 55%, transparent);
  background:linear-gradient(180deg, #fff, color-mix(in oklab, #fff 70%, var(--surface)));
  /* border-radius:16px; */
  position:relative;
  cursor:pointer;
  user-select:none;
  transition:transform .10s ease, box-shadow .12s ease, border-color .12s ease;
  box-shadow:0 8px 18px rgba(15,23,42,.06);
}

.grid-cell:hover{
  transform:translateY(-2px);
  border-color:color-mix(in oklab, var(--accent-color) 40%, var(--line-strong));
  box-shadow:0 12px 26px rgba(15,23,42,.10);
}

.grid-cell:active{ transform:translateY(-1px) scale(.99); }

.grid-cell .idx{
  position:absolute;
  top:7px;left:9px;
  font-size:11px;
  color:#8b97a8;
}

.grid-cell .ico{
  position:absolute;inset:0;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;
  color:var(--accent-color);
}

.grid-cell.has-user{ outline:2px solid color-mix(in oklab, var(--accent-color) 35%, transparent); }
.grid-cell.has-key { outline:2px solid color-mix(in oklab, #f59e0b 35%, transparent); }
.grid-cell.has-door{ outline:2px solid color-mix(in oklab, #10b981 35%, transparent); }

/* ===== Barrier lines (drawn via spans — no duplicates) ===== */
.bar{
  position:absolute;
  background:color-mix(in oklab, var(--accent-color) 78%, #0f172a);
  border-radius:999px;
  opacity:.95;
  display:none; /* default hidden */
}

.bar.top{ height:4px; left:12px; right:12px; top:7px; }
.bar.bottom{ height:4px; left:12px; right:12px; bottom:7px; }
.bar.left{ width:4px; top:12px; bottom:12px; left:7px; }
.bar.right{ width:4px; top:12px; bottom:12px; right:7px; }

.grid-cell.b-top .bar.top{display:block}
.grid-cell.b-bottom .bar.bottom{display:block}
.grid-cell.b-left .bar.left{display:block}
.grid-cell.b-right .bar.right{display:block}

/* Dark mode parity */
html.theme-dark .grid-wrap{
  background:#0b1220;
  border-color:var(--line-strong);
}
html.theme-dark .grid-cell{
  background:linear-gradient(180deg, #0f172a, #0b1220);
  border-color:var(--line-strong);
  box-shadow:0 10px 24px rgba(0,0,0,.25);
}
html.theme-dark .grid-cell .idx{color:#94a3b8}

  .grid-legend{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
  .legend-item{display:flex;gap:8px;align-items:center;color:var(--muted-color);font-size:13px}
  .legend-dot{width:10px;height:10px;border-radius:50%;background:var(--accent-color);opacity:.85}

  /* Dark mode */
  html.theme-dark .grid-wrap{background:#0b1220;border-color:var(--line-strong)}
  html.theme-dark .grid-cell{background:#0f172a;border-color:var(--line-strong)}
  html.theme-dark .grid-cell .idx{color:#94a3b8}
  html.theme-dark .tool{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  html.theme-dark .rte{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
</style>
@endpush

@section('content')
<div class="dg-wrap">
  <div class="card dg">
    <div class="card-header">
      <div class="dg-head">
        <i class="fa-solid fa-door-open"></i>
        <strong id="pageTitle">Create Door Game</strong>
        <span class="hint" id="hint">— Setup grid, timing, attempts & instructions.</span>
      </div>
    </div>

    <div class="card-body position-relative">
      <div class="dim" id="busy"><div class="spin" aria-label="Saving…"></div></div>

      {{-- Basics --}}
      <h3 class="section-title">Basics</h3>
      <div class="divider-soft"></div>

      <div class="mb-3">
        <label class="form-label" for="title">Game Title <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-heading"></i></span>
          <input id="title" class="form-control" type="text" maxlength="180" placeholder="e.g., Find the Key Door" autocomplete="off">
        </div>
        <div class="err" data-for="title"></div>
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Description</label>
        <div class="toolbar" aria-label="Description toolbar">
          <button class="tool" type="button" data-cmd="bold"><i class="fa-solid fa-bold"></i></button>
          <button class="tool" type="button" data-cmd="italic"><i class="fa-solid fa-italic"></i></button>
          <button class="tool" type="button" data-cmd="underline"><i class="fa-solid fa-underline"></i></button>
          <button class="tool" type="button" data-format="H2">H2</button>
          <button class="tool" type="button" data-format="H3">H3</button>
          <button class="tool" type="button" data-cmd="insertUnorderedList"><i class="fa-solid fa-list-ul"></i></button>
          <button class="tool" type="button" data-cmd="insertOrderedList"><i class="fa-solid fa-list-ol"></i></button>
          <button class="tool" type="button" id="btnLinkDesc"><i class="fa-solid fa-link"></i></button>
        </div>
        <div class="rte-wrap">
          <div id="description" class="rte" contenteditable="true" spellcheck="true"></div>
          <div class="rte-ph">Write a short description (HTML allowed)…</div>
        </div>
        <div class="err" data-for="description"></div>
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Instructions (shown before start)</label>
        <div class="toolbar" aria-label="Instructions toolbar">
          <button class="tool" type="button" data-cmd="bold"><i class="fa-solid fa-bold"></i></button>
          <button class="tool" type="button" data-cmd="italic"><i class="fa-solid fa-italic"></i></button>
          <button class="tool" type="button" data-cmd="underline"><i class="fa-solid fa-underline"></i></button>
          <button class="tool" type="button" data-format="H2">H2</button>
          <button class="tool" type="button" data-format="H3">H3</button>
          <button class="tool" type="button" data-cmd="insertUnorderedList"><i class="fa-solid fa-list-ul"></i></button>
          <button class="tool" type="button" data-cmd="insertOrderedList"><i class="fa-solid fa-list-ol"></i></button>
          <button class="tool" type="button" id="btnLinkInst"><i class="fa-solid fa-link"></i></button>
          <span class="tiny">Tip: how to win, barriers, timeouts, retries.</span>
        </div>
        <div class="rte-wrap">
          <div id="instructions_html" class="rte" contenteditable="true" spellcheck="true"></div>
          <div class="rte-ph">Write the instructions to show players…</div>
        </div>
        <div class="err" data-for="instructions_html"></div>
      </div>

      {{-- Grid & Timing --}}
      <h3 class="section-title mt-4">Grid & Timing</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="grid_dim">Grid Dimension <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-border-all"></i></span>
            <input id="grid_dim" class="form-control" type="number" min="1" max="10" value="3">
          </div>
          <div class="tiny mt-1">N x N grid. Default 3 → 9 cells.</div>
          <div class="err" data-for="grid_dim"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="time_limit_sec">Time Limit (seconds)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-regular fa-clock"></i></span>
            <input id="time_limit_sec" class="form-control" type="number" min="1" value="30">
          </div>
          <div class="tiny mt-1">Total time limit for the game.</div>
          <div class="err" data-for="time_limit_sec"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="max_attempts">Max Attempts</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-rotate-right"></i></span>
            <input id="max_attempts" class="form-control" type="number" min="1" value="1">
          </div>
          <div class="tiny mt-1">How many times a user can play.</div>
          <div class="err" data-for="max_attempts"></div>
        </div>
      </div>

      {{-- Grid Table --}}
      <div class="mt-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div>
            <div class="form-label mb-0">Grid Designer <span class="text-danger">*</span></div>
            <div class="tiny">Click a cell to configure (user/key/door + barriers).</div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-light" id="btnClearAll">
              <i class="fa-solid fa-eraser me-1"></i>Clear All
            </button>
            <button type="button" class="btn btn-sm btn-light" id="btnAutoUser">
              <i class="fa-solid fa-user me-1"></i>Place User (Cell 1)
            </button>
            <button type="button" class="btn btn-sm btn-light" id="btnAutoKey">
              <i class="fa-solid fa-key me-1"></i>Place Key (Cell 2)
            </button>
          </div>
        </div>

        <div class="grid-wrap">
          <div class="grid-legend mb-2">
            <div class="legend-item"><span class="legend-dot"></span> Click a cell → configure</div>
            <div class="legend-item"><i class="fa-solid fa-user"></i> User (only 1 allowed)</div>
            <div class="legend-item"><i class="fa-solid fa-key"></i> Key</div>
            <div class="legend-item"><i class="fa-solid fa-door-open"></i> Door</div>
          </div>

          <div class="grid-scroll">
            <table class="grid-table" id="gridTable" aria-label="Door game grid"></table>
          </div>

          <div class="err" data-for="grid_json"></div>
        </div>

        {{-- hidden grid_json (sent to API) --}}
        <input type="hidden" id="grid_json" value="">
      </div>

      {{-- Solution / Status --}}
      <h3 class="section-title mt-4">Solution Policy</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="show_solution_after">Show Solution After</label>
          <select id="show_solution_after" class="form-select">
            <option value="never">Never</option>
            <option value="after_each">After Each</option>
            <option value="after_finish" selected>After Finish</option>
          </select>
          <div class="err" data-for="show_solution_after"></div>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="status">Status</label>
          <select id="status" class="form-select">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div class="err" data-for="status"></div>
        </div>
      </div>

      {{-- Actions --}}
      <div class="d-flex justify-content-between align-items-center mt-4">
        <a id="cancel" class="btn btn-light" href="/door-games/manage">Cancel</a>

        <button id="btnSave" class="btn btn-primary" type="button">
          <span class="btn-spinner" aria-hidden="true"></span>
          <span class="btn-label"><i class="fa fa-floppy-disk me-1"></i> <span id="saveBtnText">Create Door Game</span></span>
        </button>
      </div>
    </div>
  </div>

  {{-- toasts --}}
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080">
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
</div>

{{-- Cell Config Modal --}}
<div class="modal fade" id="cellModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;border:1px solid var(--line-strong)">
      <div class="modal-header" style="border-bottom:1px solid var(--line-strong)">
        <h6 class="modal-title">
          <i class="fa-solid fa-border-all me-1" style="color:var(--accent-color)"></i>
          Configure Cell <span class="mono" id="cellModalTitle">#</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="tiny mb-2">Choose one of User / Key / Door (User is exclusive and only one allowed).</div>

        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" id="m_is_user">
          <label class="form-check-label" for="m_is_user"><i class="fa-solid fa-user me-1"></i> is-user?</label>
        </div>

        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" id="m_is_key">
          <label class="form-check-label" for="m_is_key"><i class="fa-solid fa-key me-1"></i> is-key?</label>
        </div>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="m_is_door">
          <label class="form-check-label" for="m_is_door"><i class="fa-solid fa-door-open me-1"></i> is-door?</label>
        </div>

        <div class="divider-soft" style="margin:10px 0 12px"></div>
        <div class="tiny mb-2">Barriers (toggles are synced with neighbor edges).</div>

        <div class="row g-2">
          <div class="col-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="m_b_top">
              <label class="form-check-label" for="m_b_top">top?</label>
            </div>
          </div>
          <div class="col-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="m_b_bottom">
              <label class="form-check-label" for="m_b_bottom">bottom?</label>
            </div>
          </div>
          <div class="col-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="m_b_left">
              <label class="form-check-label" for="m_b_left">left?</label>
            </div>
          </div>
          <div class="col-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="m_b_right">
              <label class="form-check-label" for="m_b_right">right?</label>
            </div>
          </div>
        </div>

        <div class="err mt-2" id="cellModalErr" style="display:none"></div>
      </div>

      <div class="modal-footer" style="border-top:1px solid var(--line-strong)">
        <button type="button" class="btn btn-light btn-sm" id="btnCellClear">
          <i class="fa-solid fa-eraser me-1"></i>Clear Cell
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="btnCellApply">
          <i class="fa-solid fa-check me-1"></i>Apply
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const $ = id => document.getElementById(id);

  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  const okToast  = new bootstrap.Toast($('okToast'));
  const errToast = new bootstrap.Toast($('errToast'));
  const ok  = (m)=>{ $('okMsg').textContent  = m||'Done'; okToast.show(); };
  const err = (m)=>{ $('errMsg').textContent = m||'Something went wrong'; errToast.show(); };

  const backList = '/door-games/manage';
  const API_BASE = '/api/door-games';

  if(!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  // Edit mode (?edit=<uuid>)
  const url_ = new URL(location.href);
  const editKey = url_.searchParams.get('edit');
  const isEdit = !!editKey;
  let currentUUID = editKey || null;

  if(isEdit){
    $('pageTitle').textContent = 'Edit Door Game';
    $('saveBtnText').textContent = 'Update Door Game';
    $('hint').textContent = '— Update grid, timing & instructions.';
    loadGame(editKey).catch((e)=> {
      console.error(e);
      Swal.fire('Not found','Could not load door game for editing.','error')
        .then(()=> location.replace(backList));
    });
  }

  /* ===== UI helpers ===== */
  function setFormDisabled(disabled){
    document.querySelectorAll('.card-body input, .card-body select, .card-body button, .card-body textarea, .card-body .tool')
      .forEach(el=>{
        if (el.id === 'cancel') return;
        if (el.id === 'btnSave') return;
        el.disabled = !!disabled;
        if(el.classList.contains('tool')){
          el.style.pointerEvents = disabled ? 'none' : '';
          el.style.opacity = disabled ? '.65' : '';
        }
      });
  }

  function setSaving(on){
    const btn = $('btnSave');
    btn.classList.toggle('btn-loading', !!on);
    btn.disabled = !!on;
    $('busy').classList.toggle('show', !!on);
    setFormDisabled(!!on);
  }

  function fErr(field,msg){
    const el=document.querySelector(`.err[data-for="${field}"]`);
    if(el){ el.textContent=msg||''; el.style.display=msg?'block':'none'; }
  }
  function clrErr(){
    document.querySelectorAll('.err').forEach(e=>{ e.textContent=''; e.style.display='none'; });
  }

  /* ===== RTE ===== */
  function wireRTE(rootId, linkBtnId){
    const el = $(rootId);
    const hasContent = () => (el.textContent || '').trim().length > 0 || (el.innerHTML||'').trim().length > 0;
    function togglePh(){ el.classList.toggle('has-content', hasContent()); }
    ['input','keyup','paste','blur'].forEach(ev => el.addEventListener(ev, togglePh));
    togglePh();

    const parent = el.closest('.mb-3') || document;
    parent.querySelectorAll('.tool[data-cmd]').forEach(b=> b.addEventListener('click',()=>{
      document.execCommand(b.dataset.cmd,false,null);
      el.focus(); togglePh();
    }));
    parent.querySelectorAll('.tool[data-format]').forEach(b=> b.addEventListener('click',()=>{
      document.execCommand('formatBlock',false,b.dataset.format);
      el.focus(); togglePh();
    }));
    if(linkBtnId){
      const lb = $(linkBtnId);
      lb && lb.addEventListener('click',()=>{
        const u = prompt('Enter URL (https://…)');
        if(u && /^https?:\/\//i.test(u)){
          document.execCommand('createLink',false,u);
          el.focus(); togglePh();
        }
      });
    }
  }
  wireRTE('description','btnLinkDesc');
  wireRTE('instructions_html','btnLinkInst');

  /* =========================================================
   * GRID STATE
   * Each cell: {id,row,col,is_user,is_key,is_door,barriers:{top,bottom,left,right}}
   * grid_json will be JSON.stringify(flatCells)
   * ========================================================= */
  let N = 3;
  let cells = []; // flat array length N*N
  let activeCellId = null;

  function idxToId(r,c){ return r*N + c + 1; }
  function idToRC(id){
    const i = id - 1;
    return { r: Math.floor(i / N), c: i % N };
  }
  function getCellById(id){ return cells.find(x => x.id === id); }

  function gridHasAnyData(){
    return cells.some(c =>
      c.is_user || c.is_key || c.is_door ||
      c.barriers.top || c.barriers.bottom || c.barriers.left || c.barriers.right
    );
  }

  function initGrid(n){
    N = n;
    cells = [];
    for(let r=0;r<N;r++){
      for(let c=0;c<N;c++){
        cells.push({
          id: idxToId(r,c),
          row: r,
          col: c,
          is_user: false,
          is_key: false,
          is_door: false,
          barriers: { top:false, bottom:false, left:false, right:false }
        });
      }
    }
    renderGrid();
    syncHiddenGridJson();
  }

  function shouldRenderBarrier(cell, edge){
    // canonical rendering: TOP + LEFT always; BOTTOM only last row; RIGHT only last col
    if(edge === 'top') return !!cell.barriers.top;
    if(edge === 'left') return !!cell.barriers.left;
    if(edge === 'bottom') return (cell.row === N-1) && !!cell.barriers.bottom;
    if(edge === 'right')  return (cell.col === N-1) && !!cell.barriers.right;
    return false;
  }

  function renderGrid(){
    const table = $('gridTable');
    table.innerHTML = '';

    for(let r=0;r<N;r++){
      const tr = document.createElement('tr');

      for(let c=0;c<N;c++){
        const td = document.createElement('td');
        const id = idxToId(r,c);
        const cell = getCellById(id);

        const div = document.createElement('div');
        div.className = 'grid-cell';
        div.setAttribute('role','button');
        div.setAttribute('tabindex','0');
        div.dataset.id = String(id);

        if(cell.is_user) div.classList.add('has-user');
        if(cell.is_key)  div.classList.add('has-key');
        if(cell.is_door) div.classList.add('has-door');

        if(shouldRenderBarrier(cell,'top')) div.classList.add('b-top');
        if(shouldRenderBarrier(cell,'bottom')) div.classList.add('b-bottom');
        if(shouldRenderBarrier(cell,'left')) div.classList.add('b-left');
        if(shouldRenderBarrier(cell,'right')) div.classList.add('b-right');

        const idx = document.createElement('div');
        idx.className = 'idx';
        idx.textContent = id;

        const ico = document.createElement('div');
        ico.className = 'ico';

        // icon priority: user > key > door
        if(cell.is_user){
          ico.innerHTML = '<i class="fa-solid fa-user"></i>';
        }else if(cell.is_key){
          ico.innerHTML = '<i class="fa-solid fa-key"></i>';
        }else if(cell.is_door){
          ico.innerHTML = '<i class="fa-solid fa-door-open"></i>';
        }else{
          ico.innerHTML = '';
        }

        const bTop = document.createElement('span'); bTop.className = 'bar top';
        const bBottom = document.createElement('span'); bBottom.className = 'bar bottom';
        const bLeft = document.createElement('span'); bLeft.className = 'bar left';
        const bRight = document.createElement('span'); bRight.className = 'bar right';

        div.appendChild(idx);
        div.appendChild(ico);
        div.appendChild(bTop);
        div.appendChild(bBottom);
        div.appendChild(bLeft);
        div.appendChild(bRight);

        div.addEventListener('click', ()=> openCellModal(id));
        div.addEventListener('keydown', (e)=>{
          if(e.key==='Enter' || e.key===' '){
            e.preventDefault();
            openCellModal(id);
          }
        });

        td.appendChild(div);
        tr.appendChild(td);
      }

      table.appendChild(tr);
    }
  }

  function syncHiddenGridJson(){
    const flat = cells.map(c => ({
      id: c.id,
      label: `Cell ${c.id}`,
      type: 'door_game_cell',
      is_user: !!c.is_user,
      is_key: !!c.is_key,
      is_door: !!c.is_door,
      barriers: {
        top: !!c.barriers.top,
        bottom: !!c.barriers.bottom,
        left: !!c.barriers.left,
        right: !!c.barriers.right
      }
    }));
    $('grid_json').value = JSON.stringify(flat);
  }

  function clearAll(){
    cells.forEach(c=>{
      c.is_user=false; c.is_key=false; c.is_door=false;
      c.barriers.top=false; c.barriers.bottom=false; c.barriers.left=false; c.barriers.right=false;
    });
    renderGrid();
    syncHiddenGridJson();
    ok('Grid cleared');
  }

  function clearOthers(flagKey, keepId){
    cells.forEach(c => { if(c.id !== keepId) c[flagKey] = false; });
  }

  function placeSingle(kind, cellId){
    const cell = getCellById(cellId);
    if(!cell) return;

    if(kind === 'user') clearOthers('is_user', cellId);
    if(kind === 'door') clearOthers('is_door', cellId);

    if(kind === 'user'){
      cell.is_user = true;
      cell.is_key  = false;
      cell.is_door = false;
    }else if(kind === 'key'){
      // ✅ multiple keys allowed now (only restriction: cannot coexist with user)
      if(cell.is_user) return;
      cell.is_key  = true;
      cell.is_door = false;
    }else if(kind === 'door'){
      if(cell.is_user) return;
      cell.is_door = true;
      cell.is_key  = false;
    }

    renderGrid();
    syncHiddenGridJson();
  }

  function setExclusive(cell, kind, value){
    // ✅ Only ONE user globally, ONE door globally, but ✅ MULTIPLE keys allowed
    if(kind === 'user'){
      if(value){
        clearOthers('is_user', cell.id);
        cell.is_user = true;
        cell.is_key  = false;
        cell.is_door = false;
      }else{
        cell.is_user = false;
      }
      return;
    }

    if(kind === 'key'){
      if(value){
        if(cell.is_user) { cell.is_key = false; return; }
        // ✅ no global clearOthers for keys anymore
        cell.is_key  = true;
        cell.is_door = false; // mutual with door in same cell
      }else{
        cell.is_key = false;
      }
      return;
    }

    // kind === 'door'
    if(value){
      if(cell.is_user) { cell.is_door = false; return; }
      clearOthers('is_door', cell.id);
      cell.is_door = true;
      cell.is_key  = false; // mutual with key in same cell
    }else{
      cell.is_door = false;
    }
  }

  function neighborId(id, dir){
    const {r,c} = idToRC(id);
    if(dir==='top' && r>0) return idxToId(r-1,c);
    if(dir==='bottom' && r<N-1) return idxToId(r+1,c);
    if(dir==='left' && c>0) return idxToId(r,c-1);
    if(dir==='right' && c<N-1) return idxToId(r,c+1);
    return null;
  }

  function syncBarrierEdge(cellId, edge, value){
    const cell = getCellById(cellId);
    if(!cell) return;
    cell.barriers[edge] = !!value;

    const nId = neighborId(cellId, edge);
    if(!nId) return;

    const nb = getCellById(nId);
    if(!nb) return;

    const opposite = (edge==='top') ? 'bottom'
                  : (edge==='bottom') ? 'top'
                  : (edge==='left') ? 'right'
                  : 'left';

    nb.barriers[opposite] = !!value;
  }

  /* ===== Cell Modal ===== */
  const cellModal = new bootstrap.Modal(document.getElementById('cellModal'));
  function mErr(msg){
    const el = $('cellModalErr');
    if(!msg){
      el.style.display='none';
      el.textContent='';
      return;
    }
    el.style.display='block';
    el.textContent = msg;
  }

  function openCellModal(cellId){
    activeCellId = cellId;
    const cell = getCellById(cellId);
    if(!cell) return;

    $('cellModalTitle').textContent = `#${cellId}`;
    mErr('');

    $('m_is_user').checked = !!cell.is_user;
    $('m_is_key').checked = !!cell.is_key;
    $('m_is_door').checked = !!cell.is_door;

    $('m_b_top').checked = !!cell.barriers.top;
    $('m_b_bottom').checked = !!cell.barriers.bottom;
    $('m_b_left').checked = !!cell.barriers.left;
    $('m_b_right').checked = !!cell.barriers.right;

    const lock = !!cell.is_user;
    $('m_is_key').disabled = lock;
    $('m_is_door').disabled = lock;

    cellModal.show();
  }

  function clearCell(cellId){
    const cell = getCellById(cellId);
    if(!cell) return;

    cell.is_user=false; cell.is_key=false; cell.is_door=false;

    ['top','bottom','left','right'].forEach(edge=>{
      if(cell.barriers[edge]){
        syncBarrierEdge(cellId, edge, false);
      }
    });

    renderGrid();
    syncHiddenGridJson();
  }

  $('btnCellClear').addEventListener('click', ()=>{
    if(!activeCellId) return;
    clearCell(activeCellId);
    cellModal.hide();
    ok('Cell cleared');
  });

  $('btnCellApply').addEventListener('click', ()=>{
    if(!activeCellId) return;
    const cell = getCellById(activeCellId);
    if(!cell) return;
    mErr('');

    const wantUser = $('m_is_user').checked;
    const wantKey  = $('m_is_key').checked;
    const wantDoor = $('m_is_door').checked;

    if(wantUser){
      setExclusive(cell,'user',true);
      $('m_is_key').checked = false;
      $('m_is_door').checked = false;
    }else{
      setExclusive(cell,'user',false);

      // keys can be many now
      setExclusive(cell,'key', !!wantKey);
      setExclusive(cell,'door', !!wantDoor);

      if(wantKey && wantDoor){
        // keep door, remove key (consistent with your previous choice)
        cell.is_key = false;
        cell.is_door = true;
      }
    }

    syncBarrierEdge(activeCellId, 'top', $('m_b_top').checked);
    syncBarrierEdge(activeCellId, 'bottom', $('m_b_bottom').checked);
    syncBarrierEdge(activeCellId, 'left', $('m_b_left').checked);
    syncBarrierEdge(activeCellId, 'right', $('m_b_right').checked);

    $('m_is_key').disabled = !!cell.is_user;
    $('m_is_door').disabled = !!cell.is_user;

    renderGrid();
    syncHiddenGridJson();
    cellModal.hide();
    ok('Cell updated');
  });

  $('m_is_user').addEventListener('change', ()=>{
    const lock = $('m_is_user').checked;
    if(lock){
      $('m_is_key').checked = false;
      $('m_is_door').checked = false;
    }
    $('m_is_key').disabled = lock;
    $('m_is_door').disabled = lock;
  });

  /* ===== Grid dimension change (with Swal confirm if data exists) ===== */
  let prevDim = 3;

  $('grid_dim').addEventListener('focus', ()=>{
    prevDim = Math.max(1, Math.min(10, Number($('grid_dim').value || 3)));
  });

  $('grid_dim').addEventListener('change', async ()=>{
    const newDim = Math.max(1, Math.min(10, Number($('grid_dim').value || 3)));
    $('grid_dim').value = newDim;

    // if same, do nothing
    if(newDim === prevDim) return;

    // confirm only when there is data placed
    if(gridHasAnyData()){
      const res = await Swal.fire({
        title: 'Change grid dimension?',
        text: 'Do you want to change dimension? It will alter your value.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, change',
        cancelButtonText: 'No, keep'
      });

      if(!res.isConfirmed){
        $('grid_dim').value = prevDim;
        return;
      }
    }

    initGrid(newDim);
    prevDim = newDim;
    ok(`Grid set to ${newDim}×${newDim}`);
  });

  $('btnClearAll').addEventListener('click', clearAll);

  $('btnAutoUser').addEventListener('click', ()=>{
    placeSingle('user', 1);
    ok('User placed at Cell 1');
  });

  $('btnAutoKey').addEventListener('click', ()=>{
    placeSingle('key', 2);
    ok('Key placed at Cell 2');
  });

  /* ===== Validate grid before save ===== */
  function validateGrid(){
    const raw = ($('grid_json').value || '').trim();
    if(!raw){
      fErr('grid_json','Grid is required.');
      return false;
    }

    let arr;
    try{ arr = JSON.parse(raw); }catch(e){
      fErr('grid_json','Grid JSON is invalid (internal).');
      return false;
    }

    if(!Array.isArray(arr) || arr.length !== N*N){
      fErr('grid_json',`Grid must contain exactly ${N*N} cells.`);
      return false;
    }

    const users = arr.filter(x => x.is_user);
    if(users.length > 1){
      fErr('grid_json','Only one user is allowed in the grid.');
      return false;
    }

    return true;
  }

  /* ===== load for edit ===== */
  async function loadGame(key){
    $('busy').classList.add('show');
    try{
      const res = await fetch(`${API_BASE}/${encodeURIComponent(key)}`, {
        headers:{ 'Authorization':'Bearer '+TOKEN, 'Accept':'application/json' }
      });
      const json = await res.json().catch(()=> ({}));
      if(!res.ok) throw new Error(json?.message || 'Load failed');

      const g = json?.data || json;
      if(!g) throw new Error('Game not found');
      currentUUID = g.uuid || key;

      $('title').value = g.title || '';
      $('description').innerHTML = g.description || '';
      $('instructions_html').innerHTML = g.instructions_html || '';

      $('time_limit_sec').value = g.time_limit_sec ?? 30;
      $('max_attempts').value = g.max_attempts ?? 1;

      $('show_solution_after').value = g.show_solution_after || 'after_finish';
      $('status').value = g.status || 'active';

      const dim = Number(g.grid_dim ?? 3);
      $('grid_dim').value = dim;
      prevDim = dim;
      initGrid(dim);

      if(g.grid_json){
        try{
          const parsed = (typeof g.grid_json === 'string') ? JSON.parse(g.grid_json) : g.grid_json;
          if(Array.isArray(parsed) && parsed.length === dim*dim){
            parsed.forEach((p, i)=>{
              const cell = cells[i];
              if(!cell) return;
              cell.is_user = !!p.is_user;
              cell.is_key  = !!p.is_key;
              cell.is_door = !!p.is_door;
              cell.barriers.top = !!(p.barriers?.top);
              cell.barriers.bottom = !!(p.barriers?.bottom);
              cell.barriers.left = !!(p.barriers?.left);
              cell.barriers.right = !!(p.barriers?.right);
            });
            renderGrid();
            syncHiddenGridJson();
          }
        }catch(e){
          // ignore
        }
      }

      document.querySelectorAll('.rte-ph').forEach(ph => {
        const editor = ph.previousElementSibling;
        const has = (editor.textContent || '').trim().length > 0 || (editor.innerHTML||'').trim().length > 0;
        editor.classList.toggle('has-content', has);
      });

    } finally {
      $('busy').classList.remove('show');
    }
  }

  /* ===== build payload ===== */
  function buildPayload(){
    const title = ($('title').value||'').trim();
    const descHtml = ($('description').innerHTML||'').trim();
    const instHtml = ($('instructions_html').innerHTML||'').trim();

    const payload = {
      title,
      show_solution_after: $('show_solution_after').value,
      grid_dim: Number($('grid_dim').value || 3),
      grid_json: $('grid_json').value,
      status: $('status').value,
    };

    const tlim = Number($('time_limit_sec').value||0);
    if(tlim > 0) payload.time_limit_sec = tlim;

    const maxAtt = Number($('max_attempts').value||0);
    if(maxAtt > 0) payload.max_attempts = maxAtt;

    if(isEdit){
      payload.description = descHtml;
      payload.instructions_html = instHtml;
    }else{
      if(descHtml) payload.description = descHtml;
      if(instHtml) payload.instructions_html = instHtml;
    }

    return payload;
  }

  /* ===== submit ===== */
  $('btnSave').addEventListener('click', async ()=>{
    clrErr();

    const title = ($('title').value||'').trim();
    if(!title){
      fErr('title','Game title is required.');
      $('title').focus();
      return;
    }

    if(!validateGrid()){
      err('Please fix the grid configuration.');
      return;
    }

    setSaving(true);
    try{
      const payload = buildPayload();

      const url = isEdit ? `${API_BASE}/${encodeURIComponent(currentUUID)}` : API_BASE;
      const method = isEdit ? 'PUT' : 'POST';

      const res = await fetch(url, {
        method,
        headers:{
          'Authorization':'Bearer '+TOKEN,
          'Accept':'application/json',
          'Content-Type':'application/json'
        },
        body: JSON.stringify(payload)
      });

      const json = await res.json().catch(()=> ({}));

      if(res.ok){
        ok(isEdit ? 'Door game updated successfully' : 'Door game created successfully');
        setTimeout(()=> location.replace(backList), 800);
        return;
      }

      if(res.status===422){
        const e = json.errors || {};
        Object.entries(e).forEach(([k,v])=> fErr(k, Array.isArray(v)? v[0] : String(v)));
        err(json.message || 'Please fix the highlighted fields.');
        return;
      }

      if(res.status===403){
        Swal.fire({icon:'error',title:'Unauthorized',html:'Token/role lacks permission for this endpoint.'});
        return;
      }

      Swal.fire(isEdit ? 'Update failed' : 'Save failed', json.message || ('HTTP '+res.status), 'error');
    }catch(ex){
      console.error(ex);
      Swal.fire('Network error','Please check your connection and try again.','error');
    }finally{
      setSaving(false);
    }
  });

  // initial grid
  initGrid(3);

})();
</script>

@endpush
