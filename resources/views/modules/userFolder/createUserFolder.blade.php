{{-- resources/views/modules/userFolders/createUserFolder.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Create User Folder')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
  /* ===== Shell ===== */
  .uf-wrap{max-width:1100px;margin:14px auto 40px}
  .uf.card{border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
  .uf .card-header{background:var(--surface);border-bottom:1px solid var(--line-strong);padding:16px 18px}
  .uf-head{display:flex;align-items:center;gap:10px}
  .uf-head i{color:var(--accent-color)}
  .uf-head strong{color:var(--ink);font-family:var(--font-head);font-weight:700}
  .uf-head .hint{color:var(--muted-color);font-size:var(--fs-13)}

  .section-title{font-weight:600;color:var(--ink);font-family:var(--font-head);margin:12px 2px 14px}
  .divider-soft{height:1px;background:var(--line-soft);margin:10px 0 16px}

  /* RTE */
  .toolbar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px}
  .tool{border:1px solid var(--line-strong);border-radius:10px;background:#fff;padding:6px 9px;cursor:pointer}
  .tool:hover{background:var(--page-hover)}
  .rte-wrap{position:relative}
  .rte{
    min-height:160px;max-height:520px;overflow:auto;
    border:1px solid var(--line-strong);border-radius:12px;background:#fff;padding:12px;line-height:1.6;outline:none
  }
  .rte:focus{box-shadow:var(--ring);border-color:var(--accent-color)}
  .rte-ph{position:absolute;top:12px;left:12px;color:#9aa3b2;pointer-events:none;font-size:var(--fs-14)}
  .rte.has-content + .rte-ph{display:none}

  /* Inputs polish */
  .form-control:focus, .form-select:focus, textarea:focus{
    box-shadow:0 0 0 3px color-mix(in oklab, var(--accent-color) 20%, transparent);
    border-color:var(--accent-color)
  }
  .input-group-text{background:var(--surface);border-color:var(--line-strong)}
  .tiny{font-size:12px;color:#6b7280}

  /* Errors */
  .err{font-size:12px;color:var(--danger-color);display:none;margin-top:6px}
  .err:not(:empty){display:block}

  /* Busy overlay */
  .dim{position:absolute;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.06);z-index:2}
  .dim.show{display:flex}
  .spin{width:18px;height:18px;border:3px solid #0001;border-top-color:var(--accent-color);border-radius:50%;animation:rot 1s linear infinite}
  @keyframes rot{to{transform:rotate(360deg)}}

  /* Metadata box */
  .meta-box{
    border:1px solid var(--line-strong);
    border-radius:12px;
    background:var(--surface);
    padding:12px;
  }
  .meta-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
  .meta-actions .btn{border-radius:10px}

  textarea.meta{
    width:100%;
    min-height:160px;
    border:1px solid var(--line-strong);
    border-radius:12px;
    padding:12px;
    font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size:13px;
    background:#fff;
  }

  /* Button loading state */
  .btn-loading{pointer-events:none;opacity:.85}
  .btn-loading .btn-label{visibility:hidden}
  .btn-loading .btn-spinner{display:inline-block !important}

  .btn-spinner{
    display:none; width:1rem;height:1rem;border:.2rem solid #0001;border-top-color:#fff;border-radius:50%;
    vertical-align:-.125em;animation:rot 1s linear infinite
  }
  .btn-light .btn-spinner{border-top-color:#0009}

  /* Dark mode parity */
  html.theme-dark .rte{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  html.theme-dark .tool{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  html.theme-dark textarea.meta{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  html.theme-dark .meta-box{background:#0f172a}
</style>
@endpush

@section('content')
<div class="uf-wrap">
  <div class="card uf">
    <div class="card-header">
      <div class="uf-head">
        <i class="fa-solid fa-folder-open"></i>
        <strong id="pageTitle">Create User Folder</strong>
        <span class="hint" id="hint">— Create folder to group users & organize access.</span>
      </div>
    </div>

    <div class="card-body position-relative">
      <div class="dim" id="busy"><div class="spin" aria-label="Saving…"></div></div>

      {{-- Basics --}}
      <h3 class="section-title">Basics</h3>
      <div class="divider-soft"></div>

      <div class="mb-3">
        <label class="form-label" for="title">Folder Title <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-folder"></i></span>
          <input id="title" class="form-control" type="text" maxlength="180" placeholder="e.g., CSE Students (Batch 2026)" autocomplete="off">
        </div>
        <div class="err" data-for="title"></div>
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Description (optional)</label>
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
          <div class="rte-ph">Write a short description for this folder (HTML allowed)…</div>
        </div>
        <div class="err" data-for="description"></div>
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Reason (optional)</label>
        <div class="toolbar" aria-label="Reason toolbar">
          <button class="tool" type="button" data-cmd="bold"><i class="fa-solid fa-bold"></i></button>
          <button class="tool" type="button" data-cmd="italic"><i class="fa-solid fa-italic"></i></button>
          <button class="tool" type="button" data-cmd="underline"><i class="fa-solid fa-underline"></i></button>
          <button class="tool" type="button" data-format="H2">H2</button>
          <button class="tool" type="button" data-format="H3">H3</button>
          <button class="tool" type="button" data-cmd="insertUnorderedList"><i class="fa-solid fa-list-ul"></i></button>
          <button class="tool" type="button" data-cmd="insertOrderedList"><i class="fa-solid fa-list-ol"></i></button>
          <button class="tool" type="button" id="btnLinkReason"><i class="fa-solid fa-link"></i></button>
          <span class="tiny">Tip: why this folder exists (audit purpose).</span>
        </div>

        <div class="rte-wrap">
          <div id="reason" class="rte" contenteditable="true" spellcheck="true"></div>
          <div class="rte-ph">Write the reason / context (optional)…</div>
        </div>
        <div class="err" data-for="reason"></div>
      </div>

      {{-- Settings --}}
      <h3 class="section-title mt-4">Settings</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="status">Status</label>
          <select id="status" class="form-select">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div class="tiny mt-1">Inactive folders are hidden/unused logically.</div>
        </div>
      </div>

      <div class="mt-3 meta-box">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <div class="fw-semibold" style="color:var(--ink)">Metadata (JSON)</div>
            <div class="tiny">Optional: store extra config like UI color, tags, access flags etc.</div>
          </div>
          <div class="meta-actions">
            <button class="btn btn-outline-primary btn-sm" id="btnFormatJson" type="button">
              <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Format JSON
            </button>
            <button class="btn btn-light btn-sm" id="btnResetJson" type="button">
              <i class="fa-solid fa-rotate-left me-1"></i> Reset
            </button>
          </div>
        </div>

        <textarea id="metadata" class="meta mt-2" placeholder='Example:
{
  "color": "wine-red",
  "priority": 1,
  "tags": ["cse","batch-2026"]
}'></textarea>
        <div class="err" data-for="metadata"></div>
      </div>

      {{-- Actions --}}
      <div class="d-flex justify-content-between align-items-center mt-4">
        <a id="cancel" class="btn btn-light" href="/user-folders/manage">Cancel</a>

        <button id="btnSave" class="btn btn-primary" type="button">
          <span class="btn-spinner" aria-hidden="true"></span>
          <span class="btn-label">
            <i class="fa fa-floppy-disk me-1"></i>
            <span id="saveBtnText">Create Folder</span>
          </span>
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
  /* ===== helpers & auth ===== */
  const $ = id => document.getElementById(id);
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';

  const okToast  = new bootstrap.Toast($('okToast'));
  const errToast = new bootstrap.Toast($('errToast'));
  const ok  = (m)=>{ $('okMsg').textContent  = m||'Done'; okToast.show(); };
  const err = (m)=>{ $('errMsg').textContent = m||'Something went wrong'; errToast.show(); };

  const backList = '/user-folders/manage';
  const API_BASE = '/api/user-folders';

  if(!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  // detect Edit mode (?edit=<id>)
  const url_ = new URL(location.href);
  const editKey = url_.searchParams.get('edit');
  const isEdit = !!editKey;
  let currentId = editKey || null;

  if(isEdit){
    $('pageTitle').textContent = 'Edit User Folder';
    $('saveBtnText').textContent = 'Update Folder';
    $('hint').textContent = '— Update folder details, status & metadata.';
    loadFolder(editKey).catch((e)=> {
      console.error(e);
      Swal.fire('Not found','Could not load folder for editing.','error')
        .then(()=> location.replace(backList));
    });
  }

  /* ===== enable/disable form during save ===== */
  function setFormDisabled(disabled){
    document.querySelectorAll('.card-body input, .card-body select, .card-body button, .card-body textarea, .card-body .tool')
      .forEach(el=>{
        if (el.id === 'cancel') return; // keep Cancel clickable
        if (el.id === 'btnSave') return; // handled separately
        el.disabled = !!disabled;
      });
  }

  function setSaving(on){
    const btn = $('btnSave');
    btn.classList.toggle('btn-loading', !!on);
    btn.disabled = !!on;
    $('busy').classList.toggle('show', !!on);
    setFormDisabled(!!on);
  }

  /* ===== wire RTE (description + reason) ===== */
  function wireRTE(rootId, linkBtnId){
    const el = $(rootId);
    const hasContent = () => (el.textContent || '').trim().length > 0 || (el.innerHTML||'').trim().length > 0;

    function togglePh(){
      el.classList.toggle('has-content', hasContent());
    }

    ['input','keyup','paste','blur'].forEach(ev => el.addEventListener(ev, togglePh));
    togglePh();

    const parent = el.closest('.mb-3') || document;

    parent.querySelectorAll('.tool[data-cmd]').forEach(b => b.addEventListener('click',()=>{
      document.execCommand(b.dataset.cmd,false,null);
      el.focus(); togglePh();
    }));

    parent.querySelectorAll('.tool[data-format]').forEach(b => b.addEventListener('click',()=>{
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
  wireRTE('reason','btnLinkReason');

  /* ===== errors ===== */
  function fErr(field,msg){
    const el=document.querySelector(`.err[data-for="${field}"]`);
    if(el){
      el.textContent = msg || '';
      el.style.display = msg ? 'block' : 'none';
    }
  }
  function clrErr(){
    document.querySelectorAll('.err').forEach(e=>{
      e.textContent='';
      e.style.display='none';
    });
  }

  /* ===== metadata JSON helpers ===== */
  function safeParseJson(txt){
    const t = (txt || '').trim();
    if(!t) return null;
    try{
      return JSON.parse(t);
    }catch(e){
      return { __invalid_json__: true, __raw__: t };
    }
  }

  $('btnFormatJson').addEventListener('click', ()=>{
    const t = $('metadata').value || '';
    if(!t.trim()){
      $('metadata').value = JSON.stringify({}, null, 2);
      return;
    }
    try{
      const obj = JSON.parse(t);
      $('metadata').value = JSON.stringify(obj, null, 2);
      ok('JSON formatted');
    }catch(e){
      err('Invalid JSON: Please fix syntax first');
    }
  });

  $('btnResetJson').addEventListener('click', ()=>{
    $('metadata').value = '';
  });

  /* ===== payload builder ===== */
  function buildPayload(){
    const title = ($('title').value||'').trim();

    // metadata should be array/object (controller validates as array)
    let metaObj = null;
    const metaTxt = ($('metadata').value || '').trim();
    if(metaTxt){
      try{
        metaObj = JSON.parse(metaTxt);
        // only allow object/array
        if(metaObj !== null && typeof metaObj !== 'object'){
          metaObj = null;
        }
      }catch(e){
        metaObj = { __invalid_json__: true }; // will trigger 422 on server if you validate strictly
      }
    }

    return {
      title: title,
      description: ($('description').innerHTML||'').trim() || null,
      reason: ($('reason').innerHTML||'').trim() || null,
      status: $('status').value,
      metadata: metaObj
    };
  }

  /* ===== load (Edit mode) ===== */
  async function loadFolder(id){
    $('busy').classList.add('show');
    try{
      const res = await fetch(`${API_BASE}/${encodeURIComponent(id)}`, {
        headers: {
          'Authorization':'Bearer '+TOKEN,
          'Accept':'application/json'
        }
      });
      const json = await res.json().catch(()=> ({}));
      if(!res.ok) throw new Error(json?.message || 'Load failed');

      const folder = json?.folder || json?.data || json;
      if(!folder) throw new Error('Folder not found');

      currentId = folder.id || id;

      $('title').value = folder.title || '';
      $('description').innerHTML = folder.description || '';
      $('reason').innerHTML = folder.reason || '';
      $('status').value = folder.status || 'active';

      // metadata may come as string JSON or object
      if(folder.metadata){
        try{
          const m = typeof folder.metadata === 'string' ? JSON.parse(folder.metadata) : folder.metadata;
          $('metadata').value = JSON.stringify(m, null, 2);
        }catch(e){
          $('metadata').value = String(folder.metadata);
        }
      } else {
        $('metadata').value = '';
      }

      // Update placeholders
      document.querySelectorAll('.rte-ph').forEach(ph => {
        const editor = ph.previousElementSibling;
        const hasContent = (editor.textContent || '').trim().length > 0 || (editor.innerHTML||'').trim().length > 0;
        editor.classList.toggle('has-content', hasContent);
      });

    } finally {
      $('busy').classList.remove('show');
    }
  }

  /* ===== submit ===== */
  $('btnSave').addEventListener('click', async ()=>{
    clrErr();

    const title = ($('title').value||'').trim();
    if(!title){
      fErr('title','Folder title is required.');
      $('title').focus();
      return;
    }

    // metadata validation (client)
    const metaTxt = ($('metadata').value||'').trim();
    if(metaTxt){
      try{
        JSON.parse(metaTxt);
      }catch(e){
        fErr('metadata','Metadata JSON is invalid. Click "Format JSON" or fix syntax.');
        err('Invalid JSON in metadata');
        return;
      }
    }

    setSaving(true);

    try{
      const payload = buildPayload();

      const url = isEdit ? `${API_BASE}/${encodeURIComponent(currentId)}` : API_BASE;
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
        ok(isEdit ? 'Folder updated successfully' : 'Folder created successfully');
        setTimeout(()=> location.replace(backList), 700);
        return;
      }

      if(res.status === 422){
        const e = json.errors || {};
        Object.entries(e).forEach(([k,v])=>{
          fErr(k, Array.isArray(v) ? v[0] : String(v));
        });
        err(json.message || 'Please fix the highlighted fields.');
        return;
      }

      if(res.status === 403){
        Swal.fire({icon:'error',title:'Unauthorized',html:'Token/role lacks permission for this endpoint.'});
        return;
      }

      Swal.fire(isEdit ? 'Update failed' : 'Save failed', json.message || ('HTTP '+res.status), 'error');

    } catch(ex){
      console.error(ex);
      Swal.fire('Network error','Please check your connection and try again.','error');
    } finally {
      setSaving(false);
    }
  });

})();
</script>
@endpush
