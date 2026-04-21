{{-- resources/views/modules/bubbleGame/createBubbleGame.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Create Bubble Game')

@push('styles')
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
  /* ===== Shell (matches your Quiz create UI DNA) ===== */
  .bg-wrap{max-width:1100px;margin:14px auto 40px}
  .bg.card{border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
  .bg .card-header{background:var(--surface);border-bottom:1px solid var(--line-strong);padding:16px 18px}
  .bg-head{display:flex;align-items:center;gap:10px}
  .bg-head i{color:var(--accent-color)}
  .bg-head strong{color:var(--ink);font-family:var(--font-head);font-weight:700}
  .bg-head .hint{color:var(--muted-color);font-size:var(--fs-13)}

  .section-title{font-weight:600;color:var(--ink);font-family:var(--font-head);margin:12px 2px 14px}
  .divider-soft{height:1px;background:var(--line-soft);margin:10px 0 16px}

  /* RTE */
  .toolbar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px}
  .tool{
    border:1px solid var(--line-strong);
    border-radius:10px;
    background:#fff;
    padding:6px 9px;
    cursor:pointer;
  }
  .tool:hover{background:var(--page-hover)}
  .rte-wrap{position:relative}
  .rte{
    min-height:180px;max-height:520px;overflow:auto;
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

  /* Button loading state */
  .btn-loading{pointer-events:none;opacity:.85}
  .btn-loading .btn-label{visibility:hidden}
  .btn-loading .btn-spinner{display:inline-block !important}
  .btn-spinner{display:none;width:1rem;height:1rem;border:.2rem solid #0001;border-top-color:#fff;border-radius:50%;vertical-align:-.125em;animation:rot 1s linear infinite}
  .btn-light .btn-spinner{border-top-color:#0009}

  /* Dark mode parity */
  html.theme-dark .rte{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  html.theme-dark .tool{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
</style>
@endpush

@section('content')
<div class="bg-wrap">
  <div class="card bg">
    <div class="card-header">
      <div class="bg-head">
        <i class="fa-solid fa-gamepad"></i>
        <strong id="pageTitle">Create Bubble Game</strong>
        <span class="hint" id="hint">— Setup rules, timing, randomization, scoring & instructions.</span>
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
          <input id="title" class="form-control" type="text" maxlength="180" placeholder="e.g., Bubble Math Sprint" autocomplete="off">
        </div>
        <div class="err" data-for="title"></div>
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Description</label>
        <div class="toolbar" aria-label="Description toolbar">
          <button class="tool" type="button" data-cmd="bold" aria-label="Bold"><i class="fa-solid fa-bold"></i></button>
          <button class="tool" type="button" data-cmd="italic" aria-label="Italic"><i class="fa-solid fa-italic"></i></button>
          <button class="tool" type="button" data-cmd="underline" aria-label="Underline"><i class="fa-solid fa-underline"></i></button>
          <button class="tool" type="button" data-format="H2">H2</button>
          <button class="tool" type="button" data-format="H3">H3</button>
          <button class="tool" type="button" data-cmd="insertUnorderedList" aria-label="Bulleted list"><i class="fa-solid fa-list-ul"></i></button>
          <button class="tool" type="button" data-cmd="insertOrderedList" aria-label="Numbered list"><i class="fa-solid fa-list-ol"></i></button>
          <button class="tool" type="button" id="btnLinkDesc" aria-label="Insert link"><i class="fa-solid fa-link"></i></button>
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
          <span class="tiny">Tip: rules, scoring, skip policy, timeouts, etc.</span>
        </div>
        <div class="rte-wrap">
          <div id="instructions_html" class="rte" contenteditable="true" spellcheck="true"></div>
          <div class="rte-ph">Write the instructions to show players…</div>
        </div>
        <div class="err" data-for="instructions_html"></div>
      </div>

      {{-- Rules & Timing --}}
      <h3 class="section-title mt-4">Rules & Timing</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="max_attempts">Max Attempts</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-rotate-right"></i></span>
            <input id="max_attempts" class="form-control" type="number" min="1" value="1">
          </div>
          <div class="tiny mt-1">How many times a user can play this game.</div>
          <div class="err" data-for="max_attempts"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="per_question_time_sec">Per Question Time (seconds)</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-regular fa-clock"></i></span>
            <input id="per_question_time_sec" class="form-control" type="number" min="1" value="30">
          </div>
          <div class="tiny mt-1">Timer per question bubble.</div>
          <div class="err" data-for="per_question_time_sec"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="allow_skip">Allow Skip?</label>
          <select id="allow_skip" class="form-select">
            <option value="no" selected>No</option>
            <option value="yes">Yes</option>
          </select>
          <div class="tiny mt-1">If yes, users can skip a question.</div>
          <div class="err" data-for="allow_skip"></div>
        </div>
      </div>

      {{-- Randomization --}}
      <h3 class="section-title mt-4">Randomization</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="is_question_random">Randomize Question Order?</label>
          <select id="is_question_random" class="form-select">
            <option value="no" selected>No</option>
            <option value="yes">Yes</option>
          </select>
          <div class="tiny mt-1">Shuffle question sequence for each play.</div>
          <div class="err" data-for="is_question_random"></div>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="is_bubble_positions_random">Randomize Bubble Positions?</label>
          <select id="is_bubble_positions_random" class="form-select">
            <option value="yes" selected>Yes</option>
            <option value="no">No</option>
          </select>
          <div class="tiny mt-1">Shuffle bubble layout positions.</div>
          <div class="err" data-for="is_bubble_positions_random"></div>
        </div>
      </div>

      {{-- Scoring --}}
      <h3 class="section-title mt-4">Scoring</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="points_correct">Points for Correct</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-circle-check"></i></span>
            <input id="points_correct" class="form-control" type="number" step="1" value="1">
          </div>
          <div class="err" data-for="points_correct"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="points_wrong">Points for Wrong</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-circle-xmark"></i></span>
            <input id="points_wrong" class="form-control" type="number" step="1" value="0">
          </div>
          <div class="tiny mt-1">Use 0 for no negative marking.</div>
          <div class="err" data-for="points_wrong"></div>
        </div>

        <div class="col-md-4">
          <label class="form-label" for="show_solution_after">Show Solution After</label>
          <select id="show_solution_after" class="form-select">
            <option value="never">Never</option>
            <option value="after_each">After Each</option>
            <option value="after_finish" selected>After Finish</option>
          </select>
          <div class="err" data-for="show_solution_after"></div>
        </div>
      </div>

      {{-- Status & Metadata --}}
      <h3 class="section-title mt-4">Status & Metadata</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="status">Status</label>
          <select id="status" class="form-select">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div class="err" data-for="status"></div>
        </div>

        <div class="col-md-8">
          <label class="form-label d-flex align-items-center justify-content-between" for="metadata">
            <span>Metadata (optional JSON)</span>
            <button type="button" class="btn btn-sm btn-light" id="btnMetaFormat">
              <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Format JSON
            </button>
          </label>
          <textarea id="metadata" class="form-control mono" rows="6" placeholder='Example:
{
  "difficulty": "easy",
  "tags": ["math","speed"],
  "ui_theme": "bubble-red"
}'></textarea>
          <div class="tiny mt-1">Must be a valid JSON <b>object/array</b>. Leave empty if not needed.</div>
          <div class="err" data-for="metadata"></div>
        </div>
      </div>

      {{-- Actions --}}
      <div class="d-flex justify-content-between align-items-center mt-4">
        {{-- change this link if your manage route is different --}}
        <a id="cancel" class="btn btn-light" href="/bubble-games/manage">Cancel</a>

        <button id="btnSave" class="btn btn-primary" type="button">
          <span class="btn-spinner" aria-hidden="true"></span>
          <span class="btn-label"><i class="fa fa-floppy-disk me-1"></i> <span id="saveBtnText">Create Bubble Game</span></span>
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

  // adjust if your admin route differs
  const backList = '/bubble-games/manage';
  const API_BASE = '/api/bubble-games';

  if(!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  // detect Edit mode (?edit=<uuid>)
  const url_ = new URL(location.href);
  const editKey = url_.searchParams.get('edit');
  const isEdit = !!editKey;
  let currentUUID = editKey || null;

  if(isEdit){
    $('pageTitle').textContent = 'Edit Bubble Game';
    $('saveBtnText').textContent = 'Update Bubble Game';
    $('hint').textContent = '— Update game rules, timing, scoring & instructions.';
    loadGame(editKey).catch((e)=> {
      console.error(e);
      Swal.fire('Not found','Could not load bubble game for editing.','error')
        .then(()=> location.replace(backList));
    });
  }

  /* ===== enable/disable form during save ===== */
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

  /* ===== RTE wiring ===== */
  function wireRTE(rootId, linkBtnId){
    const el = $(rootId), ph = el.nextElementSibling;
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

  /* ===== errors ===== */
  function fErr(field,msg){
    const el=document.querySelector(`.err[data-for="${field}"]`);
    if(el){ el.textContent=msg||''; el.style.display=msg?'block':'none'; }
  }
  function clrErr(){
    document.querySelectorAll('.err').forEach(e=>{ e.textContent=''; e.style.display='none'; });
  }

  /* ===== metadata helpers ===== */
  $('btnMetaFormat').addEventListener('click', ()=>{
    const raw = ($('metadata').value||'').trim();
    if(!raw) return;
    try{
      const obj = JSON.parse(raw);
      $('metadata').value = JSON.stringify(obj, null, 2);
      ok('Metadata formatted');
    }catch(e){
      fErr('metadata','Invalid JSON. Please correct it.');
      err('Invalid metadata JSON');
    }
  });

  function parseMetadata(){
    const raw = ($('metadata').value||'').trim();
    if(!raw){
      // IMPORTANT: controller update() uses isset(), so null won't clear.
      // Sending [] in edit mode clears metadata.
      return isEdit ? [] : null;
    }
    try{
      return JSON.parse(raw);
    }catch(e){
      throw new Error('Invalid metadata JSON');
    }
  }

  /* ===== payload builder ===== */
  function buildPayload(){
    const title = ($('title').value||'').trim();
    const descHtml = ($('description').innerHTML||'').trim();
    const instHtml = ($('instructions_html').innerHTML||'').trim();

    const payload = {
      title: title,
      is_question_random: $('is_question_random').value,
      is_bubble_positions_random: $('is_bubble_positions_random').value,
      allow_skip: $('allow_skip').value,
      show_solution_after: $('show_solution_after').value,
      status: $('status').value,
    };

    // Optional/number fields (send only if meaningful; server has defaults)
    const maxAtt = Number($('max_attempts').value||0);
    if(maxAtt > 0) payload.max_attempts = maxAtt;

    const pqt = Number($('per_question_time_sec').value||0);
    if(pqt > 0) payload.per_question_time_sec = pqt;

    // allow 0 / negatives for points if you want
    const pcRaw = $('points_correct').value;
    const pwRaw = $('points_wrong').value;
    if(pcRaw !== '') payload.points_correct = Number(pcRaw);
    if(pwRaw !== '') payload.points_wrong = Number(pwRaw);

    // Description/instructions:
    // In edit mode, send empty string to allow clearing (controller uses isset()).
    if(isEdit){
      payload.description = descHtml;           // '' allowed
      payload.instructions_html = instHtml;     // '' allowed
    }else{
      if(descHtml) payload.description = descHtml;
      if(instHtml) payload.instructions_html = instHtml;
    }

    // metadata (array/object)
    const meta = parseMetadata();
    if(meta !== null) payload.metadata = meta;

    return payload;
  }

  /* ===== load (Edit mode) ===== */
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

      $('max_attempts').value = g.max_attempts ?? 1;
      $('per_question_time_sec').value = g.per_question_time_sec ?? 30;

      $('is_question_random').value = g.is_question_random || 'no';
      $('is_bubble_positions_random').value = g.is_bubble_positions_random || 'yes';
      $('allow_skip').value = g.allow_skip || 'no';

      $('points_correct').value = (g.points_correct ?? 1);
      $('points_wrong').value = (g.points_wrong ?? 0);

      $('show_solution_after').value = g.show_solution_after || 'after_finish';
      $('status').value = g.status || 'active';

      // metadata
      if(g.metadata){
        try{ $('metadata').value = JSON.stringify(g.metadata, null, 2); }
        catch(e){ $('metadata').value = ''; }
      }else{
        $('metadata').value = '';
      }

      // update RTE placeholders
      document.querySelectorAll('.rte-ph').forEach(ph => {
        const editor = ph.previousElementSibling;
        const has = (editor.textContent || '').trim().length > 0 || (editor.innerHTML||'').trim().length > 0;
        editor.classList.toggle('has-content', has);
      });

    }finally{
      $('busy').classList.remove('show');
    }
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

    setSaving(true);
    try{
      let payload;
      try{
        payload = buildPayload();
      }catch(e){
        fErr('metadata', e.message || 'Invalid metadata JSON.');
        err(e.message || 'Invalid metadata JSON');
        setSaving(false);
        return;
      }

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
        ok(isEdit ? 'Bubble game updated successfully' : 'Bubble game created successfully');
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

})();
</script>
@endpush
