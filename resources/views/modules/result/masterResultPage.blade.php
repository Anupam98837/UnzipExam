{{-- resources/views/modules/reports/masterResults.blade.php --}}
@extends('pages.users.layout.structure')

@section('title','Master Results')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

<style>
/* ✅ Minimal CSS (Bootstrap first) */
.mr-wrap{max-width:1180px;margin:16px auto 40px}
.mr-card{
  background:var(--surface);
  border:1px solid var(--line-strong);
  border-radius:16px;
  box-shadow:var(--shadow-2);
}
.mr-table-wrap{max-height:70vh;overflow:auto}
.table thead th{
  position:sticky;top:0;z-index:3;
  background:var(--surface-2);
  border-bottom:1px solid var(--line-strong);
  font-weight:800;
}
.mr-avatar{
  width:38px;height:38px;border-radius:999px;
  display:flex;align-items:center;justify-content:center;
  border:1px solid var(--line-strong);
  background:var(--surface-2);
  color:var(--accent-color);
  flex:0 0 auto;
}
.mr-band{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 10px;border-radius:999px;
  font-weight:900;border:1px solid var(--line-strong);
  background:var(--surface-2);
}
.mr-dot{width:8px;height:8px;border-radius:999px;background:var(--accent-color)}
.mr-mini-btn{
  width:32px;height:32px;
  border-radius:10px;
  border:1px solid var(--line-strong);
  background:var(--surface);
  box-shadow:var(--shadow-1);
  transition:.15s;
  display:inline-flex;align-items:center;justify-content:center;
}
.mr-mini-btn:hover{transform:translateY(-1px);box-shadow:var(--shadow-2)}
.mr-mini-btn[disabled]{opacity:.55;pointer-events:none}
</style>
@endpush

@section('content')
<div class="mr-wrap">
  <div class="mr-card p-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h4 class="mb-0" style="font-family:var(--font-head)">Master Results</h4>
        <div class="text-muted" style="font-size:.9rem">
          Quiz / Bubble / Door — attempts + performance (one row per candidate)
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-light" id="mrRefreshBtn">
          <i class="fa-solid fa-rotate"></i> Refresh
        </button>
        <button class="btn btn-primary" id="mrExportBtn">
          <i class="fa-solid fa-file-export"></i> Export CSV
        </button>
      </div>
    </div>

    {{-- ✅ Toolbar (2–3 liner / Bootstrap grid / labeled filters) --}}
    <div class="mr-card p-2 mb-2">
      <div class="row g-2 align-items-end">

        {{-- Folder (Required) --}}
        <div class="col-12 col-md-4 col-lg-3">
          <label class="form-label mb-1 small fw-bold text-muted">Folder <span class="text-danger">*</span></label>
          <select class="form-select" id="mrFolder">
            <option value="">Select Folder</option>
          </select>
        </div>

        {{-- Search --}}
        <div class="col-12 col-md-8 col-lg-4">
          <label class="form-label mb-1 small fw-bold text-muted">Search</label>
          <input type="text" class="form-control" id="mrSearch"
                 placeholder="Name / Email / Phone...">
        </div>

        {{-- Attempt Mode (Clickable) --}}
        <div class="col-12 col-lg-3">
          <label class="form-label mb-1 small fw-bold text-muted">Attempt Mode</label>
          <div class="btn-group w-100" role="group" aria-label="Attempt Mode">
            <input type="radio" class="btn-check" name="mrAttemptMode" id="mrAttemptAll" value="all" checked>
            <label class="btn btn-outline-secondary fw-bold" for="mrAttemptAll">
              All (AVG)
            </label>

            <input type="radio" class="btn-check" name="mrAttemptMode" id="mrAttemptLatest" value="latest">
            <label class="btn btn-outline-secondary fw-bold" for="mrAttemptLatest">
              Latest
            </label>
          </div>
        </div>

        {{-- Exam Type Checkboxes --}}
        <div class="col-12 col-lg-2">
          <label class="form-label mb-1 small fw-bold text-muted">Exam Types</label>
          <div class="d-flex flex-wrap gap-2">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="mrIncQuiz" checked>
              <label class="form-check-label fw-bold" for="mrIncQuiz">
                <i class="fa-solid fa-clipboard-check text-muted"></i> Quiz
              </label>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="mrIncBubble" checked>
              <label class="form-check-label fw-bold" for="mrIncBubble">
                <i class="fa-solid fa-bahai text-muted"></i> Bubble
              </label>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="mrIncDoor" checked>
              <label class="form-check-label fw-bold" for="mrIncDoor">
                <i class="fa-solid fa-door-open text-muted"></i> Door
              </label>
            </div>
          </div>
        </div>

        {{-- Date Range --}}
        <div class="col-12 col-md-6 col-lg-2">
          <label class="form-label mb-1 small fw-bold text-muted">From</label>
          <input type="date" class="form-control" id="mrFrom">
        </div>

        <div class="col-12 col-md-6 col-lg-2">
          <label class="form-label mb-1 small fw-bold text-muted">To</label>
          <input type="date" class="form-control" id="mrTo">
        </div>

        {{-- Min / Max % --}}
        <div class="col-6 col-md-3 col-lg-1">
          <label class="form-label mb-1 small fw-bold text-muted">Min %</label>
          <input type="number" class="form-control" id="mrMinPct" min="0" max="100" step="1" placeholder="0">
        </div>

        <div class="col-6 col-md-3 col-lg-1">
          <label class="form-label mb-1 small fw-bold text-muted">Max %</label>
          <input type="number" class="form-control" id="mrMaxPct" min="0" max="100" step="1" placeholder="100">
        </div>

        {{-- Sort --}}
        <div class="col-12 col-md-6 col-lg-3">
          <label class="form-label mb-1 small fw-bold text-muted">Sort</label>
          <select class="form-select" id="mrSort">
            <option value="overall_desc">Overall (High → Low)</option>
            <option value="overall_asc">Overall (Low → High)</option>
            <option value="quiz_desc">Quiz (High → Low)</option>
            <option value="bubble_desc">Bubble (High → Low)</option>
            <option value="door_desc">Door (High → Low)</option>
            <option value="recent_desc">Most Recent Activity</option>
          </select>
        </div>

        {{-- Per Page --}}
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label mb-1 small fw-bold text-muted">Per Page</label>
          <select class="form-select" id="mrPerPage">
            <option value="10">10</option>
            <option value="20" selected>20</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>

        {{-- Clear --}}
        <div class="col-6 col-md-3 col-lg-1">
          <label class="form-label mb-1 small fw-bold text-muted">&nbsp;</label>
          <button class="btn btn-light w-100" id="mrClearBtn">
            <i class="fa-solid fa-broom"></i>
          </button>
        </div>

      </div>
    </div>

    <div class="text-muted mb-2" id="mrHint" style="font-size:.88rem">
      Select a folder and apply filters to view master results.
    </div>

    {{-- Table --}}
    <div class="mr-card overflow-hidden">
      <div id="mrSkeleton" class="p-3 d-none">
        <div class="placeholder-glow mb-2"><span class="placeholder col-12 rounded-3" style="height:38px"></span></div>
        <div class="placeholder-glow mb-2"><span class="placeholder col-12 rounded-3" style="height:38px"></span></div>
        <div class="placeholder-glow"><span class="placeholder col-12 rounded-3" style="height:38px"></span></div>
      </div>

      <div class="mr-table-wrap d-none" id="mrTableWrap">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th style="min-width:280px">Candidate</th>
              <th style="min-width:170px">Folder</th>
              <th style="min-width:260px"><i class="fa-solid fa-clipboard-check text-muted"></i> Quiz</th>
              <th style="min-width:260px"><i class="fa-solid fa-bahai text-muted"></i> Bubble</th>
              <th style="min-width:260px"><i class="fa-solid fa-door-open text-muted"></i> Door</th>
              <th style="min-width:140px">Total Attempts</th>
              <th style="min-width:170px">Overall</th>
              <th style="min-width:160px">Last Activity</th>
              <th style="min-width:90px" class="text-end">View</th>
            </tr>
          </thead>
          <tbody id="mrTbody"></tbody>
        </table>
      </div>

      <div class="p-4 text-center text-muted" id="mrEmpty">
        <i class="fa-regular fa-filter" style="font-size:1.6rem"></i>
        <div class="mt-2" id="mrEmptyText">Apply filters to load results.</div>
      </div>
    </div>

    {{-- Pagination --}}
    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
      <div class="text-muted" id="mrMeta" style="font-size:.9rem"></div>
      <div class="d-flex gap-2">
        <button class="btn btn-light" id="mrPrevBtn"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="btn btn-light" id="mrNextBtn"><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>

  </div>
</div>

{{-- Details Modal --}}
<div class="modal fade" id="mrDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:18px;border:1px solid var(--line-strong);background:var(--surface)">
      <div class="modal-header" style="background:var(--surface-2);border-bottom:1px solid var(--line-strong)">
        <div>
          <h5 class="modal-title mb-0" id="mrModalTitle">Student Details</h5>
          <div class="text-muted" style="font-size:.9rem" id="mrModalSub">Attempts breakdown</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <ul class="nav nav-pills gap-2 mb-3">
          <li class="nav-item">
            <button class="nav-link active fw-bold" data-bs-toggle="pill" data-bs-target="#mrTabQuiz" type="button">
              <i class="fa-solid fa-clipboard-check"></i> Quiz
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold" data-bs-toggle="pill" data-bs-target="#mrTabBubble" type="button">
              <i class="fa-solid fa-bahai"></i> Bubble Game
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link fw-bold" data-bs-toggle="pill" data-bs-target="#mrTabDoor" type="button">
              <i class="fa-solid fa-door-open"></i> Door Game
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="mrTabQuiz">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead style="background:var(--surface-2)">
                  <tr>
                    <th>#</th><th>Quiz</th><th>%</th><th>Score</th><th>Attempted</th>
                    <th class="text-end">View</th>
                  </tr>
                </thead>
                <tbody id="mrQuizBody"></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="mrTabBubble">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead style="background:var(--surface-2)">
                  <tr>
                    <th>#</th><th>Game</th><th>%</th><th>Score</th><th>Attempted</th>
                    <th class="text-end">View</th>
                  </tr>
                </thead>
                <tbody id="mrBubbleBody"></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="mrTabDoor">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead style="background:var(--surface-2)">
                  <tr>
                    <th>#</th><th>Game</th><th>%</th><th>Score</th><th>Attempted</th>
                    <th class="text-end">View</th>
                  </tr>
                </thead>
                <tbody id="mrDoorBody"></tbody>
              </table>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
(function(){
  const apiBase = "{{ url('/api') }}";

  // ✅ view result routes (your requirement)
  const quizViewBase   = "{{ url('/exam/results') }}";
  const bubbleViewBase = "{{ url('/test/results') }}";
  const doorViewBase   = "{{ url('/decision-making-test/results') }}";

  function getToken(){
    return localStorage.getItem('token') || sessionStorage.getItem('token') || '';
  }

  async function apiFetch(url, opts = {}){
    const token = getToken();
    const headers = Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    }, opts.headers || {});
    if(token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch(url, Object.assign({}, opts, {headers}));
    const data = await res.json().catch(()=>null);
    if(!res.ok){
      const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'Request failed';
      throw new Error(msg);
    }
    return data;
  }

  function el(id){ return document.getElementById(id); }

  function safeText(s){
    return (s ?? '').toString().replace(/[<>&"']/g, (c) => ({
      '<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'
    }[c]));
  }

  function fmtPct(v){
    if(v === null || v === undefined) return '—';
    const n = Number(v);
    if(Number.isNaN(n)) return '—';
    return `${n.toFixed(2)}%`;
  }

  function fmtSeconds(sec){
    const n = Number(sec);
    if(!n || Number.isNaN(n)) return '—';
    const m = Math.floor(n / 60);
    const s = Math.floor(n % 60);
    return m > 0 ? `${m}m ${s}s` : `${s}s`;
  }

  function band(overall){
    const n = Number(overall);
    if(Number.isNaN(n)) return {label:'No Data'};
    if(n >= 85) return {label:'Excellent'};
    if(n >= 70) return {label:'Good'};
    if(n >= 50) return {label:'Average'};
    return {label:'Needs Work'};
  }

  function viewUrl(type, resultId){
    if(!resultId) return '';
    if(type === 'quiz')   return `${quizViewBase}/${resultId}/view`;
    if(type === 'bubble') return `${bubbleViewBase}/${resultId}/view`;
    if(type === 'door')   return `${doorViewBase}/${resultId}/view`;
    return '';
  }

  // =========================
  // State
  // =========================
  let page = 1;
  let lastPage = 1;

  // =========================
  // UI helpers
  // =========================
  function showLoading(){
    el('mrSkeleton')?.classList.remove('d-none');
    el('mrTableWrap')?.classList.add('d-none');
    el('mrEmpty')?.classList.add('d-none');
  }

  function showTable(){
    el('mrSkeleton')?.classList.add('d-none');
    el('mrTableWrap')?.classList.remove('d-none');
    el('mrEmpty')?.classList.add('d-none');
  }

  function showEmpty(msg='Apply filters to load results.'){
    el('mrSkeleton')?.classList.add('d-none');
    el('mrTableWrap')?.classList.add('d-none');
    el('mrEmpty')?.classList.remove('d-none');
    if(el('mrEmptyText')) el('mrEmptyText').textContent = msg;
  }

  function setHint(text){
    if(el('mrHint')) el('mrHint').textContent = text || '';
  }

  function setMeta(text){
    if(el('mrMeta')) el('mrMeta').textContent = text || '';
  }

  function updatePagerButtons(){
    const prev = el('mrPrevBtn');
    const next = el('mrNextBtn');
    if(prev) prev.disabled = (page <= 1);
    if(next) next.disabled = (page >= lastPage);
  }

  function getAttemptMode(){
    // ✅ expects radio buttons: name="mrAttemptMode" value="all|latest"
    return document.querySelector('input[name="mrAttemptMode"]:checked')?.value || 'all';
  }

  function isFiltered(){
    // ✅ your rule: show only when filtered
    const folder = el('mrFolder')?.value || '';
    if(!folder) return false;

    const incCount =
      (el('mrIncQuiz')?.checked ? 1 : 0) +
      (el('mrIncBubble')?.checked ? 1 : 0) +
      (el('mrIncDoor')?.checked ? 1 : 0);

    return incCount > 0;
  }

  function toggleColumns(){
    const q = !!el('mrIncQuiz')?.checked;
    const b = !!el('mrIncBubble')?.checked;
    const d = !!el('mrIncDoor')?.checked;

    // Table headings
    document.querySelectorAll('[data-col="quiz"]').forEach(x => x.classList.toggle('d-none', !q));
    document.querySelectorAll('[data-col="bubble"]').forEach(x => x.classList.toggle('d-none', !b));
    document.querySelectorAll('[data-col="door"]').forEach(x => x.classList.toggle('d-none', !d));

    // Modal tabs (optional)
    const quizTabBtn = document.querySelector('[data-bs-target="#mrTabQuiz"]');
    const bubbleTabBtn = document.querySelector('[data-bs-target="#mrTabBubble"]');
    const doorTabBtn = document.querySelector('[data-bs-target="#mrTabDoor"]');

    if(quizTabBtn) quizTabBtn.closest('li')?.classList.toggle('d-none', !q);
    if(bubbleTabBtn) bubbleTabBtn.closest('li')?.classList.toggle('d-none', !b);
    if(doorTabBtn) doorTabBtn.closest('li')?.classList.toggle('d-none', !d);
  }

  // =========================
  // Filters -> Query
  // =========================
  async function loadFolders(){
    try{
      const res = await apiFetch(`${apiBase}/user-folders?role=student`);
      const items = (res.data || res || []);
      const select = el('mrFolder');
      if(!select) return;

      items.forEach(f=>{
        const opt = document.createElement('option');
        opt.value = f.id;
        opt.textContent = f.title || f.name || `Folder #${f.id}`;
        select.appendChild(opt);
      });
    }catch(e){
      // ignore
    }
  }

  function buildQuery(){
    const include = [];
    if(el('mrIncQuiz')?.checked) include.push('quiz');
    if(el('mrIncBubble')?.checked) include.push('bubble');
    if(el('mrIncDoor')?.checked) include.push('door');

    return {
      folder_id: el('mrFolder')?.value || '',
      search: el('mrSearch')?.value?.trim() || '',
      include: include.join(','),
      attempt_mode: getAttemptMode(), // ✅ all | latest
      date_from: el('mrFrom')?.value || '',
      date_to: el('mrTo')?.value || '',
      min_pct: el('mrMinPct')?.value || '',
      max_pct: el('mrMaxPct')?.value || '',
      sort: el('mrSort')?.value || 'overall_desc',
      per_page: el('mrPerPage')?.value || 20,
      page: page
    };
  }

  function toQueryString(obj){
    const q = new URLSearchParams();
    Object.keys(obj).forEach(k=>{
      const v = obj[k];
      if(v !== null && v !== undefined && v !== '') q.append(k, v);
    });
    return q.toString();
  }

  // =========================
  // Render
  // =========================
  function overallCell(overall){
    const b = band(overall);
    return `
      <span class="badge rounded-pill bg-light text-dark border fw-bold px-3 py-2">
        <i class="fa-solid fa-circle text-danger me-2" style="font-size:8px"></i>
        ${fmtPct(overall)}
        <span class="text-muted ms-1">(${b.label})</span>
      </span>
    `;
  }

  function totalAttemptsCell(n){
    const val = Number(n || 0);
    return `
      <span class="badge rounded-pill bg-light text-dark border fw-bold px-3 py-2">
        <i class="fa-solid fa-hashtag text-muted me-1"></i>${val}
      </span>
    `;
  }

  function metricCell(meta){
    const {
      type, pct, attempts, timeValue,
      scoreText, timeEff, totalEff,
      userUuid, lastResultId
    } = meta;

    const tries = Number(attempts || 0);
    const disabled = (tries <= 0);

    const pills = [];
    pills.push(`
      <span class="badge rounded-pill bg-light text-dark border fw-bold">
        <i class="fa-solid fa-repeat text-muted me-1"></i>${tries}
      </span>
    `);

    if(timeValue !== null && timeValue !== undefined && Number(timeValue) > 0){
      pills.push(`
        <span class="badge rounded-pill bg-light text-dark border fw-bold">
          <i class="fa-regular fa-clock text-muted me-1"></i>${safeText(fmtSeconds(timeValue))}
        </span>
      `);
    }

    if(scoreText){
      pills.push(`
        <span class="badge rounded-pill bg-light text-dark border fw-bold">
          <i class="fa-solid fa-chart-simple text-muted me-1"></i>${safeText(scoreText)}
        </span>
      `);
    }

    if(type === 'door'){
      if(timeEff){
        pills.push(`
          <span class="badge rounded-pill bg-light text-dark border fw-bold">
            <i class="fa-solid fa-stopwatch text-muted me-1"></i>${safeText(timeEff)}
          </span>
        `);
      }
      if(totalEff){
        pills.push(`
          <span class="badge rounded-pill bg-light text-dark border fw-bold">
            <i class="fa-solid fa-bolt text-muted me-1"></i>${safeText(totalEff)}
          </span>
        `);
      }
    }

    const directUrl = viewUrl(type, lastResultId);

    return `
      <div class="d-flex align-items-start justify-content-between gap-2">
        <div>
          <div class="fw-bold">${fmtPct(pct)}</div>
          <div class="d-flex flex-wrap gap-1 mt-1">${pills.join(' ')}</div>
        </div>

        <div class="d-flex gap-1">
          <button class="btn btn-light btn-sm border" title="See attempts list"
            ${disabled ? 'disabled' : ''}
            data-action="open_modal"
            data-type="${type}"
            data-uuid="${safeText(userUuid)}">
            <i class="fa-solid fa-eye"></i>
          </button>

          <button class="btn btn-light btn-sm border" title="View latest result"
            ${(!directUrl || disabled) ? 'disabled' : ''}
            data-action="view_latest"
            data-url="${safeText(directUrl)}">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
          </button>
        </div>
      </div>
    `;
  }

  function renderRows(items){
    const tbody = el('mrTbody');
    if(!tbody) return;

    tbody.innerHTML = '';
    const mode = getAttemptMode(); // all | latest

    items.forEach(row=>{
      const name  = row.name || 'Unknown';
      const email = row.email || '';
      const phone = row.phone_number || '';
      const folder = row.folder_name || '—';

      const lastAt = row.last_activity_at
        ? new Date(row.last_activity_at).toLocaleString()
        : '—';

      // ✅ All = AVG values | Latest = LAST values
      const quizPct   = (mode === 'latest') ? (row.quiz_last_pct ?? row.quiz_avg_pct) : (row.quiz_avg_pct ?? row.quiz_last_pct);
      const quizTry   = row.quiz_attempts ?? 0;
      const quizTime  = (mode === 'latest') ? (row.quiz_last_time ?? 0) : (row.quiz_total_time ?? 0);
      const quizScore = (mode === 'latest') ? (row.quiz_last_score_text ?? '') : (row.quiz_score_text ?? '');
      const quizLastId = row.quiz_last_result_id ?? null;

      const bubblePct   = (mode === 'latest') ? (row.bubble_last_pct ?? row.bubble_avg_pct) : (row.bubble_avg_pct ?? row.bubble_last_pct);
      const bubbleTry   = row.bubble_attempts ?? 0;
      const bubbleTime  = (mode === 'latest') ? (row.bubble_last_time ?? 0) : (row.bubble_total_time ?? 0);
      const bubbleScore = (mode === 'latest') ? (row.bubble_last_score_text ?? '') : (row.bubble_score_text ?? '');
      const bubbleLastId = row.bubble_last_result_id ?? null;

      const doorPct   = (mode === 'latest') ? (row.door_last_pct ?? row.door_avg_pct) : (row.door_avg_pct ?? row.door_last_pct);
      const doorTry   = row.door_attempts ?? 0;
      const doorTime  = (mode === 'latest') ? (row.door_last_time ?? 0) : (row.door_total_time ?? 0);
      const doorTimeEff = row.door_time_eff ?? '';
      const doorTotalEff = row.door_total_eff ?? '';
      const doorLastId = row.door_last_result_id ?? null;

      const totalAttempts = row.total_attempts ?? (Number(quizTry||0) + Number(bubbleTry||0) + Number(doorTry||0));

      const overall =
        (mode === 'latest')
          ? (row.overall_last_pct ?? row.overall_avg_pct)
          : (row.overall_avg_pct ?? row.overall_last_pct);

      const qOn = !!el('mrIncQuiz')?.checked;
      const bOn = !!el('mrIncBubble')?.checked;
      const dOn = !!el('mrIncDoor')?.checked;

      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2" style="min-width:260px">
              <div class="mr-avatar"><i class="fa-solid fa-user"></i></div>
              <div>
                <div class="fw-bold">${safeText(name)}</div>
                <div class="text-muted" style="font-size:.82rem">
                  ${safeText(email)}${phone ? ' • ' + safeText(phone) : ''}
                </div>
              </div>
            </div>
          </td>

          <td>${safeText(folder)}</td>

          <td class="${qOn ? '' : 'd-none'}" data-col="quiz">
            ${metricCell({
              type:'quiz',
              pct: quizPct,
              attempts: quizTry,
              timeValue: quizTime,
              scoreText: quizScore,
              userUuid: row.user_uuid,
              lastResultId: quizLastId
            })}
          </td>

          <td class="${bOn ? '' : 'd-none'}" data-col="bubble">
            ${metricCell({
              type:'bubble',
              pct: bubblePct,
              attempts: bubbleTry,
              timeValue: bubbleTime,
              scoreText: bubbleScore,
              userUuid: row.user_uuid,
              lastResultId: bubbleLastId
            })}
          </td>

          <td class="${dOn ? '' : 'd-none'}" data-col="door">
            ${metricCell({
              type:'door',
              pct: doorPct,
              attempts: doorTry,
              timeValue: doorTime,
              timeEff: doorTimeEff,
              totalEff: doorTotalEff,
              userUuid: row.user_uuid,
              lastResultId: doorLastId
            })}
          </td>

          <td>${totalAttemptsCell(totalAttempts)}</td>
          <td>${overallCell(overall)}</td>
          <td>${safeText(lastAt)}</td>

          <td class="text-end">
            <button class="btn btn-light btn-sm border" title="View all details"
              data-action="open_modal"
              data-type="quiz"
              data-uuid="${safeText(row.user_uuid)}">
              <i class="fa-solid fa-chart-line"></i>
            </button>
          </td>
        </tr>
      `);
    });

    tbody.querySelectorAll('[data-action]').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const action = btn.dataset.action;

        if(action === 'view_latest'){
          const url = btn.dataset.url || '';
          if(url) window.open(url, '_blank');
          return;
        }

        const uuid = btn.dataset.uuid;
        const type = btn.dataset.type || 'quiz';
        await openStudentModal(uuid, type);
      });
    });
  }

  // =========================
  // Data load
  // =========================
  async function loadData(reset=false){
    toggleColumns();

    if(reset) page = 1;

    if(!isFiltered()){
      setMeta('');
      setHint('Select a folder and choose at least one exam type.');
      showEmpty('Select folder + exam type to load results.');
      updatePagerButtons();
      return;
    }

    showLoading();
    setHint('Loading results for selected filters...');

    try{
      const query = buildQuery();
      const qs = toQueryString(query);

      const res = await apiFetch(`${apiBase}/reports/master-results?${qs}`);
      const payload = res.data || res;

      const items = payload.items || payload.data || [];
      const meta  = payload.meta || {};

      lastPage = meta.total_pages ?? meta.last_page ?? payload.total_pages ?? payload.last_page ?? 1;

      if(!items.length){
        setHint('No candidates found for selected filters.');
        showEmpty('No candidates found for selected filters.');
        setMeta('0 results');
        updatePagerButtons();
        return;
      }

      showTable();
      renderRows(items);

      const total = meta.total ?? payload.total ?? items.length;
      const from  = ((page - 1) * Number(query.per_page)) + 1;
      const to    = Math.min(from + items.length - 1, total);

      setMeta(`Showing ${from}–${to} of ${total}`);
      setHint(`Showing results (${getAttemptMode().toUpperCase()}).`);
      updatePagerButtons();
    }catch(e){
      showEmpty('Failed to load results.');
      setMeta('Failed to load');
      setHint('Something went wrong while loading results.');
      updatePagerButtons();
      alert(e.message || 'Failed to load master results');
    }
  }

  // =========================
  // Modal (attempt lists)
  // =========================
  async function openStudentModal(userUuid, openTab='quiz'){
    try{
      const res = await apiFetch(`${apiBase}/reports/master-results/${encodeURIComponent(userUuid)}`);
      const d = res.data || res;

      if(el('mrModalTitle')) el('mrModalTitle').textContent = `${d.student?.name || 'Student'} — Attempts`;
      if(el('mrModalSub')) el('mrModalSub').textContent =
        `${d.student?.email || ''} ${d.student?.phone_number ? '• ' + d.student.phone_number : ''}`.trim();

      function viewBtn(type, resultId){
        const url = viewUrl(type, resultId);
        if(!url) return `<button class="btn btn-light btn-sm border" disabled title="No Result ID"><i class="fa-solid fa-ban"></i></button>`;
        return `<button class="btn btn-light btn-sm border" title="Open Result" onclick="window.open('${url}','_blank')">
          <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </button>`;
      }

      // QUIZ
      const quizBody = el('mrQuizBody');
      if(quizBody){
        quizBody.innerHTML = '';
        (d.quiz_attempts || []).forEach((a, idx)=>{
          quizBody.insertAdjacentHTML('beforeend', `
            <tr>
              <td>${idx+1}</td>
              <td>${safeText(a.title || 'Quiz')}</td>
              <td><b>${fmtPct(a.percentage)}</b></td>
              <td>${safeText(a.score_text || '—')}</td>
              <td>${safeText(a.attempted_at || '—')}</td>
              <td class="text-end">${viewBtn('quiz', a.result_id || a.id)}</td>
            </tr>
          `);
        });
        if(!(d.quiz_attempts||[]).length){
          quizBody.innerHTML = `<tr><td colspan="6" class="text-muted text-center py-3">No quiz attempts</td></tr>`;
        }
      }

      // BUBBLE
      const bubbleBody = el('mrBubbleBody');
      if(bubbleBody){
        bubbleBody.innerHTML = '';
        (d.bubble_attempts || []).forEach((a, idx)=>{
          bubbleBody.insertAdjacentHTML('beforeend', `
            <tr>
              <td>${idx+1}</td>
              <td>${safeText(a.title || 'Bubble Game')}</td>
              <td><b>${fmtPct(a.percentage)}</b></td>
              <td>${safeText(a.score_text || '—')}</td>
              <td>${safeText(a.attempted_at || '—')}</td>
              <td class="text-end">${viewBtn('bubble', a.result_id || a.id)}</td>
            </tr>
          `);
        });
        if(!(d.bubble_attempts||[]).length){
          bubbleBody.innerHTML = `<tr><td colspan="6" class="text-muted text-center py-3">No bubble game attempts</td></tr>`;
        }
      }

      // DOOR
      const doorBody = el('mrDoorBody');
      if(doorBody){
        doorBody.innerHTML = '';
        (d.door_attempts || []).forEach((a, idx)=>{
          doorBody.insertAdjacentHTML('beforeend', `
            <tr>
              <td>${idx+1}</td>
              <td>${safeText(a.title || 'Door Game')}</td>
              <td><b>${fmtPct(a.percentage)}</b></td>
              <td>${safeText(a.score_text || '—')}</td>
              <td>${safeText(a.attempted_at || '—')}</td>
              <td class="text-end">${viewBtn('door', a.result_id || a.id)}</td>
            </tr>
          `);
        });
        if(!(d.door_attempts||[]).length){
          doorBody.innerHTML = `<tr><td colspan="6" class="text-muted text-center py-3">No door game attempts</td></tr>`;
        }
      }

      const modalEl = document.getElementById('mrDetailModal');
      const modal = new bootstrap.Modal(modalEl);
      modal.show();

      // switch tab
      const map = {quiz:'#mrTabQuiz', bubble:'#mrTabBubble', door:'#mrTabDoor'};
      const target = map[openTab] || '#mrTabQuiz';
      const btn = document.querySelector(`[data-bs-target="${target}"]`);
      if(btn) btn.click();

    }catch(e){
      alert(e.message || 'Failed to open details');
    }
  }

  // =========================
  // Export
  // =========================
  async function exportCsv(){
    if(!isFiltered()){
      alert('Select a folder + exam types before exporting.');
      return;
    }

    const q = Object.assign({}, buildQuery(), {export:'csv', page:1, per_page:999999});
    const url = `${apiBase}/reports/master-results?${toQueryString(q)}`;

    const token = getToken();
    if(!token){
      alert('Login token missing');
      return;
    }

    try{
      const res = await fetch(url, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'text/csv' }
      });
      if(!res.ok) throw new Error('Export failed');

      const blob = await res.blob();
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = `master_results_${new Date().toISOString().slice(0,10)}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
    }catch(e){
      alert(e.message || 'Export failed');
    }
  }

  // =========================
  // Events
  // =========================
  el('mrRefreshBtn')?.addEventListener('click', ()=>loadData(true));
  el('mrExportBtn')?.addEventListener('click', exportCsv);

  el('mrClearBtn')?.addEventListener('click', ()=>{
    if(el('mrSearch')) el('mrSearch').value = '';
    if(el('mrFolder')) el('mrFolder').value = '';
    if(el('mrIncQuiz')) el('mrIncQuiz').checked = true;
    if(el('mrIncBubble')) el('mrIncBubble').checked = true;
    if(el('mrIncDoor')) el('mrIncDoor').checked = true;

    // attempt mode radio -> ALL default
    const allRadio = document.getElementById('mrAttemptAll');
    if(allRadio) allRadio.checked = true;

    if(el('mrFrom')) el('mrFrom').value = '';
    if(el('mrTo')) el('mrTo').value = '';
    if(el('mrMinPct')) el('mrMinPct').value = '';
    if(el('mrMaxPct')) el('mrMaxPct').value = '';
    if(el('mrSort')) el('mrSort').value = 'overall_desc';
    if(el('mrPerPage')) el('mrPerPage').value = '20';

    loadData(true);
  });

  let t = null;
  el('mrSearch')?.addEventListener('input', ()=>{
    clearTimeout(t);
    t = setTimeout(()=>loadData(true), 350);
  });

  [
    'mrFolder','mrSort','mrPerPage',
    'mrFrom','mrTo','mrMinPct','mrMaxPct',
    'mrIncQuiz','mrIncBubble','mrIncDoor'
  ].forEach(id=>{
    el(id)?.addEventListener('change', ()=>loadData(true));
  });

  // attempt mode (radio click)
  document.querySelectorAll('input[name="mrAttemptMode"]').forEach(r=>{
    r.addEventListener('change', ()=>loadData(true));
  });

  el('mrPrevBtn')?.addEventListener('click', ()=>{
    if(page <= 1) return;
    page--;
    loadData(false);
  });

  el('mrNextBtn')?.addEventListener('click', ()=>{
    if(page >= lastPage) return;
    page++;
    loadData(false);
  });

  // =========================
  // Init
  // =========================
  loadFolders();
  loadData(true);

})();
</script>
@endpush
