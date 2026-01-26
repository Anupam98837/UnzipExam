{{-- resources/views/modules/pathGame/createPathGame.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Create Path Game')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
    .mini-cell.mini-set{
  background: var(--accent-color);
  color: #fff;
}

.mini-cell.mini-set .mini-arrow i,
.mini-cell.mini-set .mini-lbl{
  color: #fff;
}

.mini-cell.mini-set .mini-lbl{
  font-weight: 700;
}

  .pg-wrap{max-width:1100px;margin:14px auto 40px}
  .pg.card{border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
  .pg .card-header{background:var(--surface);border-bottom:1px solid var(--line-strong);padding:16px 18px}
  .pg-head{display:flex;align-items:center;gap:10px}
  .pg-head i{color:var(--accent-color)}
  .pg-head strong{color:var(--ink);font-family:var(--font-head);font-weight:700}
  .pg-head .hint{color:var(--muted-color);font-size:var(--fs-13)}

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

  /* ======================================
   * BIG MINI GRID (N*3 x N*3)
   * ====================================== */
  .grid-wrap{
    border:1px solid var(--line-strong);
    border-radius:16px;
    background:color-mix(in oklab, var(--surface) 88%, #fff);
    padding:12px;
  }
  .grid-scroll{overflow:auto;max-width:100%}
  .mini-table{
    border-collapse:collapse;
    margin:0 auto;
    background:#fff;
  }
  .mini-table td{
    border:1px solid var(--line-strong);
    padding:0;
    background:#fff;
  }
  .mini-table td.thick-right{ border-right:2px solid var(--line-strong) !important; }
  .mini-table td.thick-bottom{ border-bottom:2px solid var(--line-strong) !important; }

  .mini-cell{
    width:34px;height:34px;
    min-width:34px;min-height:34px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    user-select:none;
    position:relative;
    transition:background .12s ease;
  }
  .mini-cell:hover{
    background:color-mix(in oklab, var(--accent-color) 7%, #fff);
  }
  .mini-cell:active{
    background:color-mix(in oklab, var(--accent-color) 12%, #fff);
  }

  .mini-arrow{
    font-size:16px;
    color:var(--accent-color);
    line-height:1;
  }
  .mini-lbl{
    position:absolute;
    bottom:2px;right:4px;
    font-size:9px;
    color:#8b97a8;
    opacity:.9;
  }

  /* tile badge + rotation indicator shown on top-left mini cell of each tile */
  .tile-badge{
    position:absolute;
    top:2px;left:3px;
    font-size:9px;
    padding:2px 6px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    color:var(--ink);
    display:flex;
    align-items:center;
    gap:5px;
    box-shadow:var(--shadow-1);
    z-index:2;
  }
  .tile-badge .rot{
    font-size:11px;
    opacity:.9;
    color:var(--muted-color);
  }
  .tile-badge.enabled .rot{
    color:var(--accent-color);
  }

  .grid-legend{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
  .legend-item{display:flex;gap:8px;align-items:center;color:var(--muted-color);font-size:13px}

  /* Dark mode parity */
  html.theme-dark .grid-wrap{background:#0b1220;border-color:var(--line-strong)}
  html.theme-dark .mini-table{background:#0b1220}
  html.theme-dark .mini-table td{border-color:var(--line-strong);background:#0b1220}
  html.theme-dark .mini-cell{background:#0f172a}
  html.theme-dark .mini-cell:hover{background:color-mix(in oklab, var(--accent-color) 12%, #0f172a)}
  html.theme-dark .mini-lbl{color:#94a3b8}
  html.theme-dark .tool{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  html.theme-dark .rte{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  html.theme-dark .tile-badge{background:#0f172a;color:#e5e7eb;border-color:#334155}

  @media (max-width:520px){
    .mini-cell{width:30px;height:30px;min-width:30px;min-height:30px}
  }
</style>
@endpush

@section('content')
<div class="pg-wrap">
  <div class="card pg">
    <div class="card-header">
      <div class="pg-head">
        <i class="fa-solid fa-route"></i>
        <strong id="pageTitle">Create Path Game</strong>
        <span class="hint" id="hint">— Setup mini arrows + grid rotation per tile.</span>
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
          <input id="title" class="form-control" type="text" maxlength="180" placeholder="e.g., Arrow Path Challenge" autocomplete="off">
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
          <span class="tiny">Tip: each mini-cell arrow can be L/R/T/B.</span>
        </div>
        <div class="rte-wrap">
          <div id="instructions_html" class="rte" contenteditable="true" spellcheck="true"></div>
          <div class="rte-ph">Write the instructions to show players…</div>
        </div>
        <div class="err" data-for="instructions_html"></div>
      </div>

      {{-- Grid & Rules --}}
      <h3 class="section-title mt-4">Grid & Rules</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="grid_dim">Grid Dimension <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-border-all"></i></span>
            <input id="grid_dim" class="form-control" type="number" min="1" max="6" value="3">
          </div>
          <div class="tiny mt-1">
            N×N grids, each grid has 9 mini cells → total mini = (N×N×9).
          </div>
          <div class="err" data-for="grid_dim"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="time_limit_sec">Time Limit (seconds)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-regular fa-clock"></i></span>
            <input id="time_limit_sec" class="form-control" type="number" min="1" max="600" value="30">
          </div>
          <div class="tiny mt-1">Time limit per attempt.</div>
          <div class="err" data-for="time_limit_sec"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="max_attempts">Max Attempts</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-rotate-right"></i></span>
            <input id="max_attempts" class="form-control" type="number" min="1" max="50" value="1">
          </div>
          <div class="tiny mt-1">Attempts per user.</div>
          <div class="err" data-for="max_attempts"></div>
        </div>
      </div>

      {{-- Mini Grid Designer --}}
      <div class="mt-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div>
            <div class="form-label mb-0">Mini Cell Designer <span class="text-danger">*</span></div>
            <div class="tiny">
              Click any mini cell → set arrow (L/R/T/B). Rotation is configured per parent grid tile inside the same modal.
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-light" id="btnReset">
              <i class="fa-solid fa-rotate-left me-1"></i>Reset
            </button>
            <button type="button" class="btn btn-sm btn-light" id="btnAllR">
              <i class="fa-solid fa-arrow-right me-1"></i>All R
            </button>
            <button type="button" class="btn btn-sm btn-light" id="btnShuffle">
              <i class="fa-solid fa-shuffle me-1"></i>Shuffle
            </button>
          </div>
        </div>

        <div class="grid-wrap">
          <div class="grid-legend mb-2">
            <div class="legend-item"><i class="fa-solid fa-mouse-pointer"></i> Click mini cell → edit arrow</div>
            <div class="legend-item"><i class="fa-solid fa-rotate"></i> Rotation is for each grid tile</div>
            <div class="legend-item"><span class="mono">L/R/T/B</span> stored in JSON</div>
          </div>

          <div class="grid-scroll">
            <table class="mini-table" id="gridTable" aria-label="Path game mini grid"></table>
          </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
  <div class="tiny" id="rotPreviewHint">
    Hover a rotatable tile and click Rotate Preview to test rotation (preview only).
  </div>

  <div class="d-flex gap-2 flex-wrap">
    <button type="button" class="btn btn-sm btn-light" id="btnRotatePreview" disabled>
      <i class="fa-solid fa-rotate me-1"></i> Rotate Preview
    </button>

    <button type="button" class="btn btn-sm btn-light" id="btnResetPreviewRotation" disabled>
      <i class="fa-solid fa-arrow-rotate-left me-1"></i> Reset Rotation
    </button>
  </div>
</div>
          <div class="err" data-for="grid_json"></div>
        </div>

        <input type="hidden" id="grid_json" value="">
      </div>

      {{-- Policy --}}
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
        <a id="cancel" class="btn btn-light" href="/path-games/manage">Cancel</a>

        <button id="btnSave" class="btn btn-primary" type="button">
          <span class="btn-spinner" aria-hidden="true"></span>
          <span class="btn-label"><i class="fa fa-floppy-disk me-1"></i> <span id="saveBtnText">Create Path Game</span></span>
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

{{-- Mini Cell Modal --}}
<div class="modal fade" id="cellModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;border:1px solid var(--line-strong)">
      <div class="modal-header" style="border-bottom:1px solid var(--line-strong)">
        <h6 class="modal-title">
          <i class="fa-solid fa-compass me-1" style="color:var(--accent-color)"></i>
          Grid <span class="mono" id="mGridTitle">#</span> — Cell <span class="mono" id="mCellTitle">#</span>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
  <div class="tiny mb-2">
    Toggle arrow ON to place an arrow in this mini-cell.
  </div>

  <!-- ✅ Arrow Enable Toggle -->
  <div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" id="m_arrow_enable">
    <label class="form-check-label" for="m_arrow_enable">
      Enable Arrow for this mini cell
    </label>
  </div>

  <!-- ✅ Arrow options (disabled when toggle OFF) -->
  <div id="arrowOptions" class="d-flex flex-column gap-2" style="opacity:.55; pointer-events:none;">
    <label class="form-check">
      <input class="form-check-input" type="radio" name="m_arrow" value="T">
      <span class="form-check-label"><i class="fa-solid fa-arrow-up-long me-1"></i> Top (T)</span>
    </label>

    <label class="form-check">
      <input class="form-check-input" type="radio" name="m_arrow" value="B">
      <span class="form-check-label"><i class="fa-solid fa-arrow-down-long me-1"></i> Bottom (B)</span>
    </label>

    <label class="form-check">
      <input class="form-check-input" type="radio" name="m_arrow" value="L">
      <span class="form-check-label"><i class="fa-solid fa-arrow-left-long me-1"></i> Left (L)</span>
    </label>

    <label class="form-check">
      <input class="form-check-input" type="radio" name="m_arrow" value="R">
      <span class="form-check-label"><i class="fa-solid fa-arrow-right-long me-1"></i> Right (R)</span>
    </label>
  </div>

  <div class="divider-soft" style="margin:12px 0"></div>

  <!-- ✅ Rotation (tile-level) -->
  <div class="form-check form-switch mb-2">
    <input class="form-check-input" type="checkbox" id="m_rot_enable">
    <label class="form-check-label" for="m_rot_enable">
      Rotation Enabled for this Grid (tile)
    </label>
  </div>

  <select id="m_rot_type" class="form-select">
    <option value="cw">Clockwise</option>
    <option value="ccw">Anti-clockwise</option>
  </select>

  <div class="err mt-2" id="cellModalErr" style="display:none"></div>
</div>

      <div class="modal-footer" style="border-top:1px solid var(--line-strong)">
        <button type="button" class="btn btn-light btn-sm" id="btnCellDefault">
          <i class="fa-solid fa-eraser me-1"></i>Default (R)
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

  const backList = '/path-games/manage';
  const API_BASE = '/api/path-games';

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
    $('pageTitle').textContent = 'Edit Path Game';
    $('saveBtnText').textContent = 'Update Path Game';
    $('hint').textContent = '— Update mini arrows + grid rotation per tile.';
    loadGame(editKey).catch((e)=> {
      console.error(e);
      Swal.fire('Not found','Could not load path game for editing.','error')
        .then(()=> location.replace(backList));
    });
  }

  /* ======================================
   * GRID STATE
   * grid_dim = N tiles per row/col
   * mini_dim = 3 always (9 mini per tile)
   * rotation per tile, arrows per mini cell
   * ====================================== */
  const MINI = 3; // fixed 3x3 mini cells

  let N = 3;
  let tiles = [];
  let activeTileIndex = null;
  let activeCellIndex = null;

  // ======================================
  // ✅ Rotation Preview (UI only)
  // ======================================
  let previewRot = [];        // per tile: 0..3 (clockwise steps)
  let previewTile = null;     // last hovered tile index

  function initPreviewRot(){
    previewRot = Array.from({length: N*N}, ()=>0);
    previewTile = null;
    updatePreviewButtons();
  }

  function anyRotatableTile(){
    return tiles.some(t => !!t.rotation_enabled);
  }

  function anyPreviewRotated(){
    return previewRot.some(x => (Number(x)||0) !== 0);
  }

  function updatePreviewButtons(){
    const btnR = $('btnRotatePreview');
    const btnX = $('btnResetPreviewRotation');

    const canRotate = anyRotatableTile();
    if(btnR) btnR.disabled = !canRotate;
    if(btnX) btnX.disabled = !canRotate || !anyPreviewRotated();
  }

  // map new (after rotation) -> old (original) for CW rotation steps
  function mapNewToOldCoord(r, c, stepsCw){
    let rr = r, cc = c;
    const k = ((stepsCw % 4) + 4) % 4;

    for(let i=0;i<k;i++){
      // new(r,c) = old(2-c, r)  => old = (2-c, r)
      const oldR = 2 - cc;
      const oldC = rr;
      rr = oldR;
      cc = oldC;
    }
    return [rr, cc];
  }

  // rotate arrow CW 'k' times (backend-safe letters L/R/T/B)
  function rotateArrowCw(a, stepsCw){
    let v = backendArrow(a || ''); // normalize to L/R/T/B
    if(!v) return '';

    const k = ((stepsCw % 4) + 4) % 4;
    const map1 = { T:'R', R:'B', B:'L', L:'T' };

    for(let i=0;i<k;i++){
      v = map1[v] || v;
    }
    return v;
  }

  /* ===== Errors ===== */
  function fErr(field,msg){
    const el=document.querySelector(`.err[data-for="${field}"]`);
    if(el){ el.textContent=msg||''; el.style.display=msg?'block':'none'; }
  }
  function mErr(msg){
    const el = $('cellModalErr');
    if(!el) return;
    if(!msg){
      el.style.display='none';
      el.textContent='';
      return;
    }
    el.style.display='block';
    el.textContent = msg;
  }
  function clrErr(){
    document.querySelectorAll('.err').forEach(e=>{ e.textContent=''; e.style.display='none'; });
    mErr('');
  }

  /* ===== RTE ===== */
  function wireRTE(rootId, linkBtnId){
    const el = $(rootId);
    if(!el) return;

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

  // ✅ Backend accepts ONLY: L R T B (or null/empty)
  const ALLOWED_ARROWS = ['L','R','T','B']; // ✅ keep this name (your code uses it)

  // ✅ Show Up/Down in UI but store T/B for backend
  function uiArrow(a){
    const v = String(a || '').toUpperCase().trim();
    if(v === 'T') return 'U';
    if(v === 'B') return 'D';
    return v; // L/R/U/D
  }

  function backendArrow(a){
    const v = String(a || '').toUpperCase().trim();
    if(v === 'U') return 'T';
    if(v === 'D') return 'B';
    if(ALLOWED_ARROWS.includes(v)) return v; // L/R/T/B
    return '';
  }

  function arrowIcon(a){
    const v = uiArrow(a);

    if(!v) return `<span style="opacity:.25;font-size:12px;">•</span>`;

    if(v==='U') return '<i class="fa-solid fa-arrow-up-long"></i>';
    if(v==='D') return '<i class="fa-solid fa-arrow-down-long"></i>';
    if(v==='L') return '<i class="fa-solid fa-arrow-left-long"></i>';
    return '<i class="fa-solid fa-arrow-right-long"></i>';
  }

  function makeTiles(n){
    return Array.from({length:n*n}, (_,i)=>( {
      tile_index: i+1,
      rotation_enabled: false,
      rotation_type: 'cw', // cw / ccw
      cells: Array.from({length:9}, (_,j)=>( {
        cell_index: j+1,
        arrow: '' // ✅ empty initially (user will set via modal)
      }))
    }));
  }

  function initGrid(n){
    N = n;
    tiles = makeTiles(N);

    initPreviewRot(); // ✅ reset preview rotations

    renderGrid();
    syncHiddenGridJson();
    updatePreviewButtons();
  }

  function getTileIdxFromMiniPos(miniRow, miniCol){
    const tileRow = Math.floor(miniRow / MINI);
    const tileCol = Math.floor(miniCol / MINI);
    return tileRow * N + tileCol + 1; // 1-based
  }

  function getCellIdxInTile(miniRow, miniCol){
    const lr = miniRow % MINI;
    const lc = miniCol % MINI;
    return lr * MINI + lc + 1; // 1..9
  }

  function getTile(tileIndex){ return tiles[tileIndex-1]; }
  function getCell(tileIndex, cellIndex){
    const t = getTile(tileIndex);
    return t?.cells?.[cellIndex-1];
  }

  function setArrowEnabledUI(on){
    const box = $('arrowOptions');
    if(box){
      box.style.opacity = on ? '1' : '.55';
      box.style.pointerEvents = on ? 'auto' : 'none';
    }

    if(!on){
      document.querySelectorAll('input[name="m_arrow"]').forEach(r => r.checked = false);
    }else{
      // ✅ if turning ON and no arrow selected, auto default = R
      const anyChecked = !!document.querySelector('input[name="m_arrow"]:checked');
      if(!anyChecked){
        const r = document.querySelector('input[name="m_arrow"][value="R"]');
        if(r) r.checked = true;
      }
    }
  }

  // ✅ For display arrow on a mini-cell considering preview rotation
  function getDisplayArrow(tileIndex, miniRow, miniCol){
    const t = getTile(tileIndex);
    const step = Number(previewRot?.[tileIndex-1] || 0);

    // no preview rotation OR tile not rotatable => show original
    if(!step || !t?.rotation_enabled){
      const cellIndex = getCellIdxInTile(miniRow, miniCol);
      return backendArrow(getCell(tileIndex, cellIndex)?.arrow || '');
    }

    // local coords inside tile (0..2)
    const lr = miniRow % MINI;
    const lc = miniCol % MINI;

    // find original cell after CW preview rotation
    const [or, oc] = mapNewToOldCoord(lr, lc, step);
    const oldIndex = (or * MINI) + oc + 1;

    const base = backendArrow(getCell(tileIndex, oldIndex)?.arrow || '');
    return rotateArrowCw(base, step);
  }

  function renderGrid(){
    const table = $('gridTable');
    if(!table) return;

    table.innerHTML = '';
    const M = N * MINI; // total mini rows/cols

    for(let r=0;r<M;r++){
      const tr = document.createElement('tr');

      for(let c=0;c<M;c++){
        const td = document.createElement('td');

        // optional thick borders (make sure CSS class exists)
        if ((c+1) % MINI === 0 && c !== (M-1)) td.classList.add('thick-right');
        if ((r+1) % MINI === 0 && r !== (M-1)) td.classList.add('thick-bottom');

        const tileIndex = getTileIdxFromMiniPos(r,c);
        const cellIndex = getCellIdxInTile(r,c);

        const t = getTile(tileIndex);

        const div = document.createElement('div');
        div.className = 'mini-cell';
        div.dataset.tile = String(tileIndex);
        div.dataset.cell = String(cellIndex);

        // ✅ tile badge on top-left mini-cell of each big tile
        const isTileTopLeft = ((r % MINI) === 0) && ((c % MINI) === 0);
        if(isTileTopLeft){
          const badge = document.createElement('div');
          const rotOn = !!t?.rotation_enabled;
          const rotType = (t?.rotation_type || 'cw');

          // ✅ hidden by default
          badge.className = 'tile-badge d-none' + (rotOn ? ' enabled' : '');
          badge.dataset.tile = String(tileIndex);

          badge.innerHTML = `
            <span>#${tileIndex}</span>
            <i class="fa-solid ${rotType==='ccw' ? 'fa-rotate-left' : 'fa-rotate-right'} rot" title="Tile rotation"></i>
          `;
          div.appendChild(badge);
        }

        // ✅ preview-aware arrow
        const dispArrow = getDisplayArrow(tileIndex, r, c);

        // ✅ highlight mini cell if arrow exists
        if(dispArrow) div.classList.add('mini-set');

        const arrowDiv = document.createElement('div');
        arrowDiv.className = 'mini-arrow';
        arrowDiv.innerHTML = arrowIcon(dispArrow);

        const lbl = document.createElement('div');
        lbl.className = 'mini-lbl';
        lbl.textContent = uiArrow(dispArrow || '');

        div.appendChild(arrowDiv);
        div.appendChild(lbl);

        div.addEventListener('click', ()=> openCellModal(tileIndex, cellIndex));

        td.appendChild(div);
        tr.appendChild(td);
      }
      table.appendChild(tr);
    }
  }

  /* ✅ Tile badge show on hover (per tile group) */
  let hoverTile = null;

  function showBadge(tileIndex){
    if(!tileIndex) return;
    const badge = document.querySelector(`.tile-badge[data-tile="${tileIndex}"]`);
    if(badge) badge.classList.remove('d-none');
  }

  function hideBadge(tileIndex){
    if(!tileIndex) return;
    const badge = document.querySelector(`.tile-badge[data-tile="${tileIndex}"]`);
    if(badge) badge.classList.add('d-none');
  }

  function wireTileHover(){
    const table = $('gridTable');
    if(!table) return;

    table.addEventListener('mouseover', (e)=>{
      const cell = e.target.closest('.mini-cell');
      if(!cell) return;

      const tileIndex = cell.dataset.tile;
      if(!tileIndex) return;

      previewTile = Number(tileIndex || 0) || null; // ✅ for rotate preview button

      if(hoverTile !== tileIndex){
        hideBadge(hoverTile);
        showBadge(tileIndex);
        hoverTile = tileIndex;
      }
    });

    table.addEventListener('mouseout', (e)=>{
      const fromCell = e.target.closest('.mini-cell');
      if(!fromCell) return;

      const fromTile = fromCell.dataset.tile;
      const toCell = e.relatedTarget?.closest?.('.mini-cell');
      const toTile = toCell?.dataset.tile;

      if(fromTile && fromTile !== toTile){
        hideBadge(fromTile);
        hoverTile = toTile || null;
        if(toTile) showBadge(toTile);
      }
    });

    table.addEventListener('mouseleave', ()=>{
      hideBadge(hoverTile);
      hoverTile = null;
    });
  }

  /* ✅ FINAL JSON = grids[] (each grid contains 9 mini tiles) */
  function syncHiddenGridJson(){
    const obj = {
      grid_dim: N,
      mini_dim: MINI,
      grids: tiles.map(t => ({
        grid_index: Number(t.tile_index),                // ✅ big tile index
        rotatable: !!t.rotation_enabled,                 // ✅ rotation per big tile
        rotation_type: String(t.rotation_type || 'cw'),  // cw / ccw
        tiles: (t.cells || []).map(c => ({
          index: Number(c.cell_index),                   // ✅ mini index 1..9
          arrow: (c.arrow ? backendArrow(c.arrow) : null)
        }))
      }))
    };

    const hidden = $('grid_json');
    if(hidden) hidden.value = JSON.stringify(obj);
  }

  function gridHasAnyData(){
    return tiles.some(t =>
      !!t.rotation_enabled ||
      (t.cells || []).some(c => !!(c.arrow || '').trim())
    );
  }

  /* ===== Toolbar actions ===== */
  function resetAll(){
    tiles.forEach(t=>{
      t.rotation_enabled = false;
      t.rotation_type = 'cw';
      t.cells.forEach(c=> c.arrow = '');
    });

    initPreviewRot(); // ✅ reset preview rotations too
    renderGrid();
    syncHiddenGridJson();
    updatePreviewButtons();

    ok('Grid reset');
  }

  function allRight(){
    tiles.forEach(t=>{
      t.cells.forEach(c=> c.arrow = 'R');
    });
    renderGrid();
    syncHiddenGridJson();
    updatePreviewButtons();
    ok('All mini arrows set to Right');
  }

  function shuffle(){
    tiles.forEach(t=>{
      t.cells.forEach(c=>{
        c.arrow = ALLOWED_ARROWS[Math.floor(Math.random() * ALLOWED_ARROWS.length)];
      });
    });
    renderGrid();
    syncHiddenGridJson();
    updatePreviewButtons();
    ok('Shuffled mini arrows');
  }

  $('btnReset')?.addEventListener('click', resetAll);
  $('btnAllR')?.addEventListener('click', allRight);
  $('btnAllRight')?.addEventListener('click', allRight);
  $('btnShuffle')?.addEventListener('click', shuffle);

  // ======================================
  // ✅ Rotation Preview Buttons
  // ======================================
  $('btnRotatePreview')?.addEventListener('click', ()=>{
    if(!anyRotatableTile()){
      updatePreviewButtons();
      return;
    }

    // pick hovered tile if rotatable, else first rotatable tile
    let tileIndex = Number(previewTile || 0) || null;

// ✅ fallback: if no hovered tile, rotate last clicked cell's tile
if(!tileIndex && activeTileIndex){
  tileIndex = Number(activeTileIndex);
}

// ✅ if still invalid OR not rotatable, fallback to first rotatable tile
if(!tileIndex || !getTile(tileIndex)?.rotation_enabled){
  const first = tiles.find(t => !!t.rotation_enabled);
  tileIndex = first ? Number(first.tile_index) : null;
}


    if(!tileIndex) return;

    const t = getTile(tileIndex);
    const dir = String(t?.rotation_type || 'cw').toLowerCase();

    // cw = +1 step, ccw = -1 => +3 steps
    const delta = (dir === 'ccw') ? 3 : 1;

    previewRot[tileIndex - 1] = (Number(previewRot[tileIndex - 1] || 0) + delta) % 4;

    renderGrid();
    syncHiddenGridJson(); // ✅ payload unchanged; preview only
    updatePreviewButtons();

    ok(`Preview rotated Tile #${tileIndex}`);
  });

  $('btnResetPreviewRotation')?.addEventListener('click', ()=>{
    previewRot = previewRot.map(()=>0);

    renderGrid();
    syncHiddenGridJson(); // ✅ arrows unchanged
    updatePreviewButtons();

    ok('Rotation preview reset');
  });

  /* ===== Dimension change confirm ===== */
  let prevDim = 3;

  $('grid_dim')?.addEventListener('focus', ()=>{
    prevDim = Math.max(1, Math.min(6, Number($('grid_dim').value || 3)));
  });

  $('grid_dim')?.addEventListener('change', async ()=>{
    const newDim = Math.max(1, Math.min(6, Number($('grid_dim').value || 3)));
    $('grid_dim').value = newDim;

    if(newDim === prevDim) return;

    if(gridHasAnyData()){
      const res = await Swal.fire({
        title: 'Change grid dimension?',
        text: 'It will reset mini arrows and rotation settings.',
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
    ok(`Grid set to ${newDim}×${newDim} (mini total = ${newDim*newDim*9})`);
  });

  /* ===== Modal (mini cell + grid rotation) ===== */
  const cellModalEl = $('cellModal');
  const cellModal = cellModalEl ? new bootstrap.Modal(cellModalEl) : null;

  function setModalArrow(a){
    const backend = backendArrow(a);       // L/R/T/B
    const ui = uiArrow(backend);           // L/R/U/D

    const radios = [...document.querySelectorAll('input[name="m_arrow"]')];
    const values = radios.map(r => String(r.value||'').toUpperCase());

    // ✅ if your modal uses U/D pick that, else pick T/B
    const want = values.includes(ui) ? ui : backend;

    radios.forEach(r => r.checked = (String(r.value).toUpperCase() === want));
  }

  function getModalArrow(){
    const r = document.querySelector('input[name="m_arrow"]:checked');
    if(!r) return '';
    return backendArrow(r.value); // ✅ store as L/R/T/B
  }

  function openCellModal(tileIndex, cellIndex){
    activeTileIndex = tileIndex;
    activeCellIndex = cellIndex;

    $('mGridTitle').textContent = `#${tileIndex}`;
    $('mCellTitle').textContent = `#${cellIndex}`;
    mErr('');

    const t = getTile(tileIndex);
    const c = getCell(tileIndex, cellIndex);

    const existingArrow = backendArrow(c?.arrow || '');

    // ✅ Arrow toggle ON if arrow exists
    $('m_arrow_enable').checked = !!existingArrow;
    setArrowEnabledUI(!!existingArrow);

    if(existingArrow){
      setModalArrow(existingArrow);
    }else{
      document.querySelectorAll('input[name="m_arrow"]').forEach(r => r.checked = false);
    }

    // ✅ Rotation = tile level
    $('m_rot_enable').checked = !!t?.rotation_enabled;
    $('m_rot_type').value = (t?.rotation_type || 'cw');
    $('m_rot_type').disabled = !$('m_rot_enable').checked;

    cellModal.show();
  }

  $('m_arrow_enable')?.addEventListener('change', ()=>{
    setArrowEnabledUI(!!$('m_arrow_enable').checked);
  });

  $('m_rot_enable')?.addEventListener('change', ()=>{
    $('m_rot_type').disabled = !$('m_rot_enable').checked;
  });

  $('btnCellDefault')?.addEventListener('click', ()=>{
    $('m_arrow_enable').checked = false;
    setArrowEnabledUI(false);
    mErr('');
  });

  $('btnCellApply')?.addEventListener('click', ()=>{
    if(!activeTileIndex || !activeCellIndex) return;

    const arrowEnabled = !!$('m_arrow_enable').checked;

    let finalArrow = '';
    if(arrowEnabled){
      finalArrow = getModalArrow();
      if(!finalArrow){
        mErr('Please select an arrow direction.');
        return;
      }
    }

    // ✅ Save mini cell arrow (empty allowed when toggle OFF)
    const cell = getCell(activeTileIndex, activeCellIndex);
    if(cell) cell.arrow = finalArrow || '';

    // ✅ Save tile rotation settings
    const t = getTile(activeTileIndex);
    if(t){
      t.rotation_enabled = !!$('m_rot_enable').checked;
      t.rotation_type = $('m_rot_type').value || 'cw';
    }

    renderGrid();
    syncHiddenGridJson();
    updatePreviewButtons();
    cellModal.hide();

    ok(arrowEnabled ? 'Arrow saved' : 'Arrow removed');
  });

  /* ===== validate before save ===== */
  function validateGrid(){
    const raw = ($('grid_json').value || '').trim();
    if(!raw){
      fErr('grid_json','Grid is required.');
      return false;
    }

    let obj;
    try{ obj = JSON.parse(raw); }catch(e){
      fErr('grid_json','Grid JSON is invalid (internal).');
      return false;
    }

    if(!obj.grids || !Array.isArray(obj.grids) || obj.grids.length !== (N*N)){
      fErr('grid_json',`Grid must contain exactly ${N*N} grids.`);
      return false;
    }

    for(const g of obj.grids){
      if(typeof g.grid_index !== 'number'){
        fErr('grid_json','grid_index must be a number.');
        return false;
      }

      if(!g.tiles || !Array.isArray(g.tiles) || g.tiles.length !== 9){
        fErr('grid_json','Each grid must contain exactly 9 mini tiles.');
        return false;
      }

      for(const t of g.tiles){
        if(typeof t.index !== 'number'){
          fErr('grid_json','tiles[].index is required and must be a number.');
          return false;
        }

        if(t.arrow !== null && t.arrow !== undefined){
          const av = backendArrow(t.arrow || '');
          if(av && !ALLOWED_ARROWS.includes(av)){
            fErr('grid_json','Invalid arrow found. Allowed: L / R / T / B');
            return false;
          }
        }
      }

      if(g.rotatable){
        const rt = String(g.rotation_type || '').toLowerCase();
        if(rt !== 'cw' && rt !== 'ccw'){
          fErr('grid_json','Rotation type must be cw or ccw.');
          return false;
        }
      }
    }

    return true;
  }

  /* ===== compute global rotation fields (backend safe) ===== */
  function computeRotationMeta(){
    const anyRot = tiles.some(t => !!t.rotation_enabled);
    const anyCw  = tiles.some(t => !!t.rotation_enabled && String(t.rotation_type||'cw').toLowerCase() === 'cw');
    const anyCcw = tiles.some(t => !!t.rotation_enabled && String(t.rotation_type||'cw').toLowerCase() === 'ccw');

    let mode = 'cw';
    if(anyCw && anyCcw) mode = 'both';
    else if(anyCcw && !anyCw) mode = 'ccw';

    return { rotation_enabled: anyRot, rotation_mode: mode };
  }

  /* ===== load for edit ===== */
  async function loadGame(key){
    $('busy')?.classList.add('show');
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

          if(parsed?.grids && Array.isArray(parsed.grids) && parsed.grids.length === dim*dim){
            tiles = parsed.grids.map((gg, i)=>({
              tile_index: Number(gg.grid_index ?? (i+1)),
              rotation_enabled: !!gg.rotatable,
              rotation_type: String(gg.rotation_type || 'cw'),
              cells: Array.from({length:9}, (_,j)=>{
                const found = (gg.tiles || []).find(x => Number(x.index) === (j+1));
                return {
                  cell_index: j+1,
                  arrow: found?.arrow ? backendArrow(found.arrow) : ''
                };
              })
            }));

            initPreviewRot();
            renderGrid();
            syncHiddenGridJson();
            updatePreviewButtons();
          }
        }catch(e){}
      }

      document.querySelectorAll('.rte-ph').forEach(ph => {
        const editor = ph.previousElementSibling;
        const has = (editor.textContent || '').trim().length > 0 || (editor.innerHTML||'').trim().length > 0;
        editor.classList.toggle('has-content', has);
      });

    } finally {
      $('busy')?.classList.remove('show');
    }
  }

  /* ===== build payload ===== */
  function buildPayload(){
    const title = ($('title').value||'').trim();
    const descHtml = ($('description').innerHTML||'').trim();
    const instHtml = ($('instructions_html').innerHTML||'').trim();

    const gridObj = JSON.parse($('grid_json').value);

    // ✅ backend requires these (if your backend still has them)
    const rotMeta = computeRotationMeta();

    return {
      title,
      description: descHtml || null,
      instructions_html: instHtml || null,

      show_solution_after: $('show_solution_after').value,

      grid_dim: Number($('grid_dim').value || 3),
      grid_json: gridObj,

      time_limit_sec: Number($('time_limit_sec').value || 30),
      max_attempts: Number($('max_attempts').value || 1),

      rotation_enabled: rotMeta.rotation_enabled,
      rotation_mode: rotMeta.rotation_mode,

      status: $('status').value
    };
  }

  /* ===== submit ===== */
  function setSaving(on){
    const btn = $('btnSave');
    if(!btn) return;
    btn.classList.toggle('btn-loading', !!on);
    btn.disabled = !!on;
    $('busy')?.classList.toggle('show', !!on);
  }

  $('btnSave')?.addEventListener('click', async ()=>{
    clrErr();

    const title = ($('title').value||'').trim();
    if(!title){
      fErr('title','Game title is required.');
      $('title').focus();
      return;
    }

    syncHiddenGridJson();

    if(!validateGrid()){
      err('Please fix the highlighted grid configuration.');
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
        ok(isEdit ? 'Path game updated successfully' : 'Path game created successfully');
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

  // ✅ initial
  initGrid(3);
  wireTileHover();
  updatePreviewButtons();

})();
</script>
@endpush
