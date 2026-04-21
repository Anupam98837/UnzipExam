{{-- resources/views/modules/interviewRegistrationCampaigns/createInterviewRegistrationCampaign.blade.php --}}

@section('title','Create Interview Registration Campaign')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
  /* ===== Shell ===== */
  .irc-wrap{max-width:1100px;margin:14px auto 40px}
  .irc.card{border:1px solid var(--line-strong);border-radius:16px;background:var(--surface);box-shadow:var(--shadow-2);overflow:hidden}
  .irc .card-header{background:var(--surface);border-bottom:1px solid var(--line-strong);padding:16px 18px}
  .irc-head{display:flex;align-items:center;gap:10px}
  .irc-head i{color:var(--accent-color)}
  .irc-head strong{color:var(--ink);font-family:var(--font-head);font-weight:700}
  .irc-head .hint{color:var(--muted-color);font-size:var(--fs-13)}

  .section-title{font-weight:600;color:var(--ink);font-family:var(--font-head);margin:12px 2px 14px}
  .divider-soft{height:1px;background:var(--line-soft);margin:10px 0 16px}

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

  /* Button loading state */
  .btn-loading{pointer-events:none;opacity:.85}
  .btn-loading .btn-label{visibility:hidden}
  .btn-loading .btn-spinner{display:inline-block !important}

  .btn-spinner{
    display:none; width:1rem;height:1rem;border:.2rem solid #0001;border-top-color:#fff;border-radius:50%;
    vertical-align:-.125em;animation:rot 1s linear infinite
  }
  .btn-light .btn-spinner{border-top-color:#0009}

  .url-box{
    border:1px dashed var(--line-strong);
    border-radius:12px;
    padding:12px;
    background:color-mix(in oklab, var(--accent-color) 6%, var(--surface));
  }
  .url-box code{
    font-size:13px;
    word-break:break-all;
    color:var(--ink);
  }

  /* Dark mode parity */
  html.theme-dark .url-box{background:#0b1220;border-color:var(--line-strong)}
</style>
@endpush

@section('content')
<div class="irc-wrap">
  <div class="card irc">
    <div class="card-header">
      <div class="irc-head">
        <i class="fa-solid fa-bullhorn"></i>
        <strong id="pageTitle">Create Interview Registration Campaign</strong>
        <span class="hint" id="hint">— Create registration link for a folder with date validity.</span>
      </div>
    </div>

    <div class="card-body position-relative">
      <div class="dim" id="busy"><div class="spin" aria-label="Saving…"></div></div>

      {{-- Basics --}}
      <h3 class="section-title">Basics</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="user_folder_id">Folder <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-folder-open"></i></span>
            <select id="user_folder_id" class="form-select">
              <option value="">Select folder</option>
            </select>
          </div>
          <div class="err" data-for="user_folder_id"></div>
          <div class="tiny mt-1">This campaign will register users inside this folder.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label" for="status">Status</label>
          <select id="status" class="form-select">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div class="tiny mt-1">Inactive campaigns won't allow registration.</div>
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label" for="title">Campaign Title <span class="text-danger">*</span></label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-heading"></i></span>
          <input id="title" class="form-control" type="text" maxlength="180" placeholder="e.g., Interview Registration - Jan 2026">
        </div>
        <div class="err" data-for="title"></div>
      </div>

      <div class="mt-3">
        <label class="form-label" for="description">Campaign Description (optional)</label>
        <textarea id="description" class="form-control" rows="4" placeholder="Short description for this campaign…"></textarea>
        <div class="err" data-for="description"></div>
      </div>

      {{-- Date range --}}
      <h3 class="section-title mt-4">Validity</h3>
      <div class="divider-soft"></div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="start_date">Start Date <span class="text-danger">*</span></label>
          <input id="start_date" class="form-control" type="date">
          <div class="err" data-for="start_date"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="end_date">End Date <span class="text-danger">*</span></label>
          <input id="end_date" class="form-control" type="date">
          <div class="err" data-for="end_date"></div>
        </div>
      </div>

      {{-- URL preview --}}
      <div class="mt-4 url-box">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <div class="fw-semibold" style="color:var(--ink)">Registration URL</div>
            <div class="tiny">This is the public link students will use.</div>
          </div>
          <button class="btn btn-outline-primary btn-sm" id="btnCopyUrl" type="button">
            <i class="fa-solid fa-copy me-1"></i> Copy
          </button>
        </div>
        <div class="mt-2">
          <code id="urlPreview">{{ url('/register') }}/<span class="text-muted">campaign_uuid</span></code>
        </div>
      </div>

      {{-- Actions --}}
      <div class="d-flex justify-content-between align-items-center mt-4">
        <a id="cancel" class="btn btn-light" href="/interview-registration-campaigns/manage">Cancel</a>

        <button id="btnSave" class="btn btn-primary" type="button">
          <span class="btn-spinner" aria-hidden="true"></span>
          <span class="btn-label">
            <i class="fa fa-floppy-disk me-1"></i>
            <span id="saveBtnText">Create Campaign</span>
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

  const backList = '/interview-registration-campaigns/manage';
  const API_BASE = '/api/interview-registration-campaigns';
  const REGISTER_BASE = `{{ url('/register') }}`;

  if(!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  // detect Edit mode (?edit=<id/uuid>)
  const url_ = new URL(location.href);
  const editKey = url_.searchParams.get('edit');
  const isEdit = !!editKey;
  let currentId = editKey || null; // can be uuid or id
  let currentUuid = null;

  if(isEdit){
    $('pageTitle').textContent = 'Edit Interview Registration Campaign';
    $('saveBtnText').textContent = 'Update Campaign';
    $('hint').textContent = '— Update title, folder, validity and status.';
  }

  /* ===== enable/disable form during save ===== */
  function setFormDisabled(disabled){
    document.querySelectorAll('.card-body input, .card-body select, .card-body button, .card-body textarea')
      .forEach(el=>{
        if (el.id === 'cancel') return;
        if (el.id === 'btnSave') return;
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

  /* ===== url preview ===== */
  function setUrlPreview(uuid){
    const u = uuid || 'campaign_uuid';
    $('urlPreview').innerHTML = `${REGISTER_BASE}/<span class="${uuid ? '' : 'text-muted'}">${u}</span>`;
  }

  $('btnCopyUrl').addEventListener('click', async ()=>{
    const uuid = currentUuid;
    if(!uuid){
      err('URL available after save');
      return;
    }
    const url = `${REGISTER_BASE}/${uuid}`;
    try{
      await navigator.clipboard.writeText(url);
      ok('URL copied');
    }catch{
      ok('Copy not supported - please copy manually');
    }
  });

  /* ===== Folder dropdown ===== */
  async function loadFoldersDropdown(selectedId=null){
    try{
      const res = await fetch('/api/user-folders?show=all', {
        headers:{
          'Authorization':'Bearer '+TOKEN,
          'Accept':'application/json',
          'X-dropdown':'1'
        }
      });
      const j = await res.json().catch(()=> ({}));
      if(!res.ok) throw new Error(j?.message || 'Folder list failed');

      const folders = j?.data || [];
      const sel = $('user_folder_id');
      sel.innerHTML = `<option value="">Select folder</option>` + folders.map(f => {
        const id = String(f.id);
        const t = String(f.title || ('Folder #'+f.id));
        return `<option value="${id}">${escapeHtml(t)}</option>`;
      }).join('');

      if(selectedId){
        sel.value = String(selectedId);
      }
    }catch(ex){
      console.error(ex);
      err('Failed to load folders');
    }
  }

  function escapeHtml(s){
    const m={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;','`':'&#96;'};
    return (s==null?'':String(s)).replace(/[&<>"'`]/g,ch=>m[ch]);
  }

  /* ===== load (Edit mode) ===== */
  async function loadCampaign(key){
    $('busy').classList.add('show');
    try{
      const res = await fetch(`${API_BASE}/${encodeURIComponent(key)}`, {
        headers: {
          'Authorization':'Bearer '+TOKEN,
          'Accept':'application/json'
        }
      });
      const json = await res.json().catch(()=> ({}));
      if(!res.ok) throw new Error(json?.message || 'Load failed');

      const row = json?.data || json?.campaign || json;
      if(!row) throw new Error('Campaign not found');

      currentId = row.id || key;
      currentUuid = row.uuid || null;

      $('title').value = row.title || '';
      $('description').value = row.description || '';
      $('status').value = row.status || 'active';

      // normalize date format (YYYY-MM-DD)
      const sd = (row.start_date || '').slice(0,10);
      const ed = (row.end_date || '').slice(0,10);
      $('start_date').value = sd || '';
      $('end_date').value = ed || '';

      await loadFoldersDropdown(row.user_folder_id || row.folder_id || null);

      setUrlPreview(currentUuid);
    } finally {
      $('busy').classList.remove('show');
    }
  }

  /* ===== payload builder ===== */
  function buildPayload(){
    return {
      user_folder_id: String($('user_folder_id').value || '').trim(),
      title: ($('title').value||'').trim(),
      description: ($('description').value||'').trim() || null,
      start_date: ($('start_date').value||'').trim(),
      end_date: ($('end_date').value||'').trim(),
      status: ($('status').value||'active').trim()
    };
  }

  /* ===== simple validations ===== */
  function validate(payload){
    let okk = true;
    if(!payload.user_folder_id){ fErr('user_folder_id','Folder is required.'); okk=false; }
    if(!payload.title){ fErr('title','Campaign title is required.'); okk=false; }
    if(!payload.start_date){ fErr('start_date','Start date is required.'); okk=false; }
    if(!payload.end_date){ fErr('end_date','End date is required.'); okk=false; }

    if(payload.start_date && payload.end_date){
      const a = new Date(payload.start_date);
      const b = new Date(payload.end_date);
      if(!isNaN(a) && !isNaN(b) && a > b){
        fErr('end_date','End date must be after start date.');
        okk=false;
      }
    }
    return okk;
  }

  /* ===== submit ===== */
  $('btnSave').addEventListener('click', async ()=>{
    clrErr();

    const payload = buildPayload();
    if(!validate(payload)) return;

    setSaving(true);

    try{
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
        // store uuid for url preview
        const row = json?.data || json?.campaign || json?.item || null;
        currentUuid = row?.uuid || currentUuid;

        setUrlPreview(currentUuid);

        ok(isEdit ? 'Campaign updated successfully' : 'Campaign created successfully');
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

  /* ===== init ===== */
  (async function init(){
    await loadFoldersDropdown(null);
    setUrlPreview(null);

    if(isEdit){
      await loadCampaign(editKey);
    }
  })();

})();
</script>
@endpush
