
@section('title','My Quizzes')

@section('content')
<style>
  .qz-wrap{
    max-width:1100px;
    margin:16px auto 40px;
  }
  .qz-card-shell{
    border-radius:16px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    box-shadow:var(--shadow-2);
    overflow:hidden;
  }
  .qz-head{
    padding:16px 18px;
    border-bottom:1px solid var(--line-strong);
    background:var(--surface);
    display:flex;
    align-items:center;
    gap:12px;
  }
  .qz-head-icon{
    width:34px;height:34px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    display:flex;align-items:center;justify-content:center;
    color:var(--accent-color);
    background:var(--surface-2);
  }
  .qz-head-title{
    font-family:var(--font-head);
    font-weight:700;
    color:var(--ink);
    margin:0;
  }
  .qz-head-sub{color:var(--muted-color); font-size:var(--fs-13);}
  .qz-head-tools{
    margin-left:auto;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .qz-search{
    min-width:220px;
  }
  .qz-search .form-control{
    border-radius:999px;
    padding-left:32px;
  }
  .qz-search-icon{
    position:absolute;
    left:10px; top:50%;
    transform:translateY(-50%);
    color:var(--muted-color);
    font-size:13px;
  }

  .qz-body{padding:14px 16px 16px; position:relative;}

  .qz-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
    gap:14px;
  }
  .qz-card{
    position:relative;
    border-radius:14px;
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    padding:12px 12px 10px;
    display:flex;
    flex-direction:column;
    gap:8px;
    min-height:160px;
    box-shadow:var(--shadow-1);
    transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
  }
  .qz-card:hover{
    transform:translateY(-3px);
    box-shadow:var(--shadow-2);
    border-color:var(--accent-color);
    background:var(--surface);
  }

  .qz-card-top{
    display:flex;
    align-items:flex-start;
    gap:10px;
  }
  .qz-avatar{
    width:40px;height:40px;
    border-radius:14px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    display:flex;align-items:center;justify-content:center;
    color:var(--accent-color);
    flex-shrink:0;
  }
  .qz-title{
    font-family:var(--font-head);
    font-size:.98rem;
    font-weight:600;
    margin:0;
    color:var(--ink);
  }
  .qz-excerpt{
    font-size:var(--fs-13);
    color:var(--muted-color);
    max-height:40px;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .qz-badges{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
    margin-top:2px;
  }
  .qz-chip{
    font-size:11px;
    padding:2px 7px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    color:var(--muted-color);
    display:inline-flex;
    align-items:center;
    gap:4px;
  }
  .qz-chip i{font-size:10px;}
  .qz-chip-primary{
    background:var(--t-primary);
    border-color:rgba(20,184,166,.3);
    color:#0f766e;
  }
  .qz-chip-success{
    background:var(--t-success);
    border-color:rgba(22,163,74,.25);
    color:#15803d;
  }
  .qz-chip-warn{
    background:var(--t-warn);
    border-color:rgba(245,158,11,.25);
    color:#92400e;
  }

  .qz-meta{
    display:flex;
    flex-wrap:wrap;
    gap:8px 14px;
    font-size:var(--fs-12);
    color:var(--muted-color);
    margin-top:4px;
  }
  .qz-meta span{
    display:inline-flex;
    align-items:center;
    gap:6px;
  }
  .qz-meta i{font-size:11px;}

  .qz-footer{
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:8px;
  }
  .qz-footer .btn{
    border-radius:999px;
    padding-inline:12px;
  }
  .qz-footer .btn-outline-primary{
    border-style:dashed;
  }
  .qz-footer .sub{
    margin-left:auto;
    font-size:11px;
    color:var(--muted-color);
  }

  .qz-empty{
    border:1px dashed var(--line-strong);
    border-radius:10px;
    padding:22px 16px;
    text-align:center;
    color:var(--muted-color);
    background:var(--surface-2);
    font-size:var(--fs-13);
  }

  .qz-loader-wrap{
    position:absolute;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    background:rgba(0,0,0,.03);
    z-index:2;
  }
  .qz-loader-wrap.show{display:flex;}
  .qz-loader{
    width:20px;height:20px;
    border:3px solid #0001;
    border-top-color:var(--accent-color);
    border-radius:50%;
    animation:rot 1s linear infinite;
  }
  @keyframes rot{to{transform:rotate(360deg)}}

  .qz-error{
    font-size:12px;
    color:var(--danger-color);
    margin-top:6px;
    display:none;
  }
  .qz-error.show{display:block;}

  .qz-pagination{
    margin-top:14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    font-size:var(--fs-13);
    color:var(--muted-color);
  }
  .qz-pagination .btn{
    border-radius:999px;
    padding-inline:12px;
  }

  @media (max-width: 576px){
    .qz-head{
      flex-direction:column;
      align-items:flex-start;
    }
    .qz-head-tools{
      margin-left:0;
      width:100%;
      justify-content:space-between;
    }
    .qz-search{min-width:0; flex:1;}
  }

  /* Dark tweaks */
  html.theme-dark .qz-card-shell{background:var(--surface);}
  html.theme-dark .qz-card{background:#04151f;}
  html.theme-dark .qz-card:hover{background:#020b13;}
  html.theme-dark .qz-empty{background:#020b13;}
  html.theme-dark .qz-head{background:#020b13;}
</style>

<div class="qz-wrap">
  <div class="qz-card-shell card">
    <div class="qz-head">
      <div class="qz-head-icon">
        <i class="fa-solid fa-clipboard-check"></i>
      </div>
      <div>
        <h1 class="qz-head-title">My Quizzes</h1>
        <div class="qz-head-sub">
          View the quizzes assigned to you and start or continue your attempts.
        </div>
      </div>

      <div class="qz-head-tools">
        <div class="position-relative qz-search">
          <span class="qz-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input type="text" id="qzSearch" class="form-control"
                 placeholder="Search quizzes…">
        </div>
      </div>
    </div>

    <div class="qz-body">
      <div class="qz-loader-wrap" id="qzLoader">
        <div class="qz-loader"></div>
      </div>

      <div id="qzError" class="qz-error"></div>

      <div id="qzEmpty" class="qz-empty d-none">
        <i class="fa-regular fa-face-smile-beam mb-1"></i><br>
        No quizzes available for you right now. Your upcoming quizzes will appear here.
      </div>

      <div id="qzList" class="qz-grid"></div>

      <div id="qzPagination" class="qz-pagination d-none">
        <button type="button" class="btn btn-light btn-sm" id="qzPrev">
          <i class="fa-solid fa-arrow-left-long"></i> Previous
        </button>
        <span id="qzPageInfo"></span>
        <button type="button" class="btn btn-light btn-sm" id="qzNext">
          Next <i class="fa-solid fa-arrow-right-long"></i>
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const API_URL      = '/api/quizz/my';

  const listEl       = document.getElementById('qzList');
  const emptyEl      = document.getElementById('qzEmpty');
  const loaderEl     = document.getElementById('qzLoader');
  const errorEl      = document.getElementById('qzError');
  const paginationEl = document.getElementById('qzPagination');
  const prevBtn      = document.getElementById('qzPrev');
  const nextBtn      = document.getElementById('qzNext');
  const pageInfoEl   = document.getElementById('qzPageInfo');
  const searchInput  = document.getElementById('qzSearch');

  let currentPage = 1;
  let lastPage    = 1;
  let currentQ    = '';

  function getToken() {
    return sessionStorage.getItem('token') || localStorage.getItem('token') || null;
  }

  function clearAuth() {
    try { sessionStorage.removeItem('token'); } catch(e){}
    try { sessionStorage.removeItem('role'); } catch(e){}
    try { localStorage.removeItem('token'); } catch(e){}
    try { localStorage.removeItem('role'); } catch(e){}
  }

  function requireAuthToken() {
    const t = getToken();
    if (!t) {
      // no token → send to login
      window.location.replace('/login');
      return null;
    }
    return t;
  }

  function myStatusBadge(status) {
    if (status === 'completed') {
      return '<span class="qz-chip qz-chip-success"><i class="fa-solid fa-circle-check"></i>Completed</span>';
    }
    if (status === 'in_progress') {
      return '<span class="qz-chip qz-chip-primary"><i class="fa-solid fa-play"></i>In progress</span>';
    }
    return '<span class="qz-chip"><i class="fa-regular fa-clock"></i>Upcoming</span>';
  }

  function quizStatusBadge(status) {
    if (status === 'active') {
      return '<span class="qz-chip qz-chip-primary"><i class="fa-solid fa-signal"></i>Active</span>';
    }
    if (status === 'archived') {
      return '<span class="qz-chip qz-chip-warn"><i class="fa-solid fa-box-archive"></i>Archived</span>';
    }
    return '';
  }

  function publicBadge(isPublic) {
    return isPublic
      ? '<span class="qz-chip"><i class="fa-solid fa-globe"></i>Public</span>'
      : '<span class="qz-chip"><i class="fa-solid fa-lock"></i>Private</span>';
  }

  function sanitize(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function renderQuizzes(items) {
    listEl.innerHTML = '';

    if (!items || !items.length) {
      emptyEl.classList.remove('d-none');
      paginationEl.classList.add('d-none');
      return;
    }

    emptyEl.classList.add('d-none');

    const frag = document.createDocumentFragment();

    items.forEach(q => {
      const card = document.createElement('article');
      card.className = 'qz-card';

      const totalTime = q.total_time ? q.total_time + ' min' : '—';
      const totalQ    = q.total_questions || 0;
      const attempts  = q.total_attempts || 1;

      const myStatus  = q.my_status || 'upcoming';
      const hasResult = q.result && q.result.id;

      let primaryLabel = 'Start quiz';
      if (myStatus === 'in_progress') primaryLabel = 'Continue';
      if (myStatus === 'completed')   primaryLabel = 'Retake quiz';

      const disabledStart = (q.status !== 'active') ? 'disabled' : '';

      card.innerHTML = `
        <div class="qz-card-top">
          <div class="qz-avatar">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <div class="flex-grow-1">
            <h3 class="qz-title">${sanitize(q.title || q.quiz_name || 'Quiz')}</h3>
            <p class="qz-excerpt">${sanitize(q.excerpt || q.quiz_description || '')}</p>
            <div class="qz-badges">
              ${myStatusBadge(myStatus)}
              ${quizStatusBadge(q.status)}
              ${publicBadge(!!q.is_public)}
            </div>
          </div>
        </div>

        <div class="qz-meta">
          <span><i class="fa-regular fa-circle-question"></i>${totalQ} questions</span>
          <span><i class="fa-regular fa-clock"></i>${totalTime}</span>
          <span><i class="fa-solid fa-rotate-right"></i>${attempts} attempt${attempts > 1 ? 's' : ''} allowed</span>
        </div>

        <div class="qz-footer">
          <button type="button"
                  class="btn btn-primary btn-sm"
                  data-action="start"
                  ${disabledStart}>
            <i class="fa-solid fa-arrow-right"></i>
            <span>${primaryLabel}</span>
          </button>

          ${hasResult ? `
            <button type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-action="result">
              <i class="fa-solid fa-chart-line"></i> View result
            </button>
          ` : ''}

          <span class="sub">
            Added ${q.created_at ? new Date(q.created_at).toLocaleDateString() : ''}
          </span>
        </div>
      `;

      const startBtn  = card.querySelector('[data-action="start"]');
      const resultBtn = card.querySelector('[data-action="result"]');

      if (startBtn) {
        startBtn.addEventListener('click', function () {
          if (!q.uuid) return;
          window.location.href = '/exam/' + encodeURIComponent(q.uuid);
        });
      }

      if (resultBtn && hasResult) {
        resultBtn.addEventListener('click', function () {
          window.location.href = '/exam/results/' + encodeURIComponent(q.result.id) + '/view';
        });
      }

      frag.appendChild(card);
    });

    listEl.appendChild(frag);
  }

  function updatePaginationUI(total, perPage, current, last) {
    if (!total || last <= 1) {
      paginationEl.classList.add('d-none');
      return;
    }
    paginationEl.classList.remove('d-none');

    currentPage = current;
    lastPage    = last;

    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= lastPage;

    pageInfoEl.textContent =
      `Page ${currentPage} of ${lastPage} • ${total} quiz${total > 1 ? 'zes' : ''}`;
  }

  async function fetchQuizzes(page = 1) {
    const token = requireAuthToken();
    if (!token) return;

    loaderEl.classList.add('show');
    errorEl.classList.remove('show');
    errorEl.textContent = '';

    const params = new URLSearchParams();
    params.set('page', page);
    params.set('per_page', 9);
    if (currentQ.trim() !== '') params.set('q', currentQ.trim());

    try {
      const res = await fetch(API_URL + '?' + params.toString(), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer ' + token
        }
      });

      const json = await res.json().catch(() => ({}));

      if (res.status === 401 || res.status === 403) {
        clearAuth();
        window.location.replace('/login');
        return;
      }

      if (!res.ok) {
        throw new Error(json.message || json.error || 'Failed to load quizzes.');
      }

      const items      = json.data || [];
      const pagination = json.pagination || {};

      renderQuizzes(items);
      updatePaginationUI(
        pagination.total || items.length,
        pagination.per_page || 9,
        pagination.current_page || page,
        pagination.last_page || 1
      );
    } catch (err) {
      console.error(err);
      errorEl.textContent = err.message || 'Something went wrong while loading quizzes.';
      errorEl.classList.add('show');
      renderQuizzes([]);
    } finally {
      loaderEl.classList.remove('show');
    }
  }

  // Pagination events
  prevBtn.addEventListener('click', function () {
    if (currentPage > 1) fetchQuizzes(currentPage - 1);
  });
  nextBtn.addEventListener('click', function () {
    if (currentPage < lastPage) fetchQuizzes(currentPage + 1);
  });

  // Search (debounced)
  let searchTimer = null;
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      currentQ = this.value || '';
      if (searchTimer) clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        fetchQuizzes(1);
      }, 350);
    });
  }

  // Initial load
  fetchQuizzes(1);
});
</script>
@endpush
