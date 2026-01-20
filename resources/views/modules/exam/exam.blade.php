@section('title','Exam')

@php
  $quizKey = $quizKey ?? request()->route('quiz') ?? request()->query('quiz');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="quiz-key" content="{{ $quizKey }}">
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Exam</title>

  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- MathJax --}}
  <script>
    window.MathJax = {
      tex: {
        inlineMath: [['$', '$'], ['\\(', '\\)']],
        displayMath: [['\\[','\\]'], ['$$','$$']],
        processEscapes: true
      },
      options: { skipHtmlTags: ['script','noscript','style','textarea','pre','code'] },
      startup: { typeset: false }
    };
  </script>
  <script id="MathJax-script" defer
          src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml-full.js"></script>

  <style>
    body{background:var(--page-bg,#f4f5fb);color:var(--ink,#111827);font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Inter,sans-serif}
    header.exam-header{background:var(--surface,#fff);border-bottom:1px solid var(--line-strong,#e5e7eb);box-shadow:var(--shadow-1,0 4px 10px rgba(15,23,42,.04));z-index:20}
    .exam-logo{font-weight:700;letter-spacing:.02em;font-size:1.05rem}
    .exam-logo span{color:var(--accent-color,#4f46e5)}
    .timer-pill{padding:.45rem .9rem;border-radius:999px;font-weight:600;font-size:.9rem;background:var(--accent-color,#4f46e5);color:#fff;display:flex;align-items:center;gap:.5rem;box-shadow:0 8px 18px rgba(79,70,229,.45)}
    .exam-card{background:var(--surface,#fff);border-radius:16px;border:1px solid var(--line-strong,#e5e7eb);box-shadow:var(--shadow-2,0 10px 30px rgba(15,23,42,.08))}
    .exam-card-slim{background:var(--surface,#fff);border-radius:16px;border:1px solid var(--line-strong,#e5e7eb);box-shadow:var(--shadow-1,0 6px 18px rgba(15,23,42,.05))}
    .btn-primary{background:var(--accent-color,#4f46e5);border-color:var(--accent-color,#4f46e5);font-weight:600;border-radius:.8rem}
    .btn-primary:hover{filter:brightness(.96);border-color:var(--accent-color,#4f46e5)}
    .btn-light{background:var(--surface,#fff);border-radius:.8rem;border:1px solid var(--line-strong,#e5e7eb);font-weight:500;color:var(--muted,#4b5563)}
    .btn-light:hover{background:var(--page-hover,#f7f8fc)}

    .nav-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.5rem}
    @media (min-width:576px){.nav-grid{grid-template-columns:repeat(6,1fr)}}
    @media (min-width:992px){.nav-grid{grid-template-columns:repeat(5,1fr)}}

    .nav-btn{width:38px;height:38px;border-radius:999px;border:1px solid var(--line-strong,#e5e7eb);background:var(--surface,#fff);font-size:.82rem;font-weight:600;display:flex;align-items:center;justify-content:center;transition:all .16s ease}
    .nav-btn.current{background:var(--accent-color,#4f46e5);border-color:var(--accent-color,#4f46e5);color:#fff;box-shadow:0 0 0 1px rgba(79,70,229,.3)}
    .nav-btn.answered{background:var(--t-success,#16a34a);border-color:var(--t-success,#16a34a);color:#111827}
    .nav-btn.review{background:var(--t-warn,#f59e0b);border-color:var(--t-warn,#f59e0b);color:#111827}
    .nav-btn.visited{background:var(--page-hover,#f7f8fc)}

    .w3-progress{height:10px;border-radius:999px;background:var(--line-soft,#e5e7eb);overflow:hidden}
    .w3-progress>div{height:100%;width:0%;background:var(--accent-color,#4f46e5);transition:width .2s ease}

    .legend-dot{width:10px;height:10px;border-radius:999px;display:inline-block;margin-right:.35rem}
    .legend-dot-answered{background:var(--t-success,#16a34a)}
    .legend-dot-review{background:var(--t-warn,#f59e0b)}
    .legend-dot-visited{background:var(--page-hover,#e5e7eb)}
    .legend-dot-current{background:var(--accent-color,#4f46e5)}

    .skeleton{position:relative;overflow:hidden;background:var(--line-soft,#e5e7eb);border-radius:10px}
    .skeleton::after{content:"";position:absolute;inset:0;transform:translateX(-100%);background:linear-gradient(90deg,rgba(255,255,255,0) 0%,rgba(255,255,255,.55) 50%,rgba(255,255,255,0) 100%);animation:shimmer 1.2s infinite}
    @keyframes shimmer{100%{transform:translateX(100%)}}

    .question-title{font-family:Poppins,system-ui,sans-serif;font-size:1.02rem;font-weight:600}
    .question-meta{font-size:.8rem;color:var(--muted,#6b7280)}
    .question-badge{font-size:.72rem;padding:.16rem .55rem;border-radius:999px;background:rgba(79,70,229,.06);color:var(--accent-color,#4f46e5);border:1px solid rgba(79,70,229,.2)}

    .opt{border-radius:12px;border:1px solid var(--line-soft,#e5e7eb);padding:.65rem .75rem;margin-bottom:.35rem;background:var(--surface,#fff);cursor:pointer;transition:background .16s ease,border-color .16s ease,box-shadow .16s ease}
    .opt:hover{background:var(--page-hover,#f7f8fc);border-color:var(--accent-color,#4f46e5);box-shadow:0 6px 16px rgba(15,23,42,.05)}
    .opt input.form-check-input{margin-top:0;cursor:pointer}
    .opt .form-check-label{cursor:pointer;font-size:.92rem}

    .fib-underline{display:inline-block;min-width:90px;border-bottom:2px solid #cbd5e1;margin:0 .22rem .2rem .22rem}
    .fib-fields .form-control{height:40px;border-radius:10px}

    mjx-container[display="block"]{display:block!important;margin:.5rem 0}
    mjx-container{overflow-x:auto}

    @media (min-width:992px){.col-fixed-260{flex:0 0 260px;max-width:260px}}

    /* ✅ Intro Modal - Bubble Game Like */
    .intro-card{
      border:1px solid var(--line-strong,#e5e7eb);
      border-radius:14px;
      background:var(--surface,#fff);
      padding:12px 14px;
    }
    .intro-title{
      font-weight:800;
      font-size:.95rem;
      margin-bottom:6px;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .intro-body{
      color:var(--muted,#6b7280);
      font-size:.9rem;
      line-height:1.45;
    }
    .intro-body ul, .intro-body ol{margin:8px 0 0 18px}
    .intro-body li{margin:4px 0}
  </style>
</head>
<body>

<header class="exam-header sticky-top">
  <div class="container-xxl d-flex align-items-center justify-content-between py-3">
    <div class="d-flex align-items-center gap-2">
      <div class="exam-logo"><span>Exam</span> Portal</div>
      <span class="badge rounded-pill text-bg-light border">
        <i class="fa-solid fa-pencil me-1"></i> Live
      </span>
    </div>
    <div id="timer-pill" class="timer-pill">
      <i class="fa-solid fa-clock"></i>
      <span id="time-left">--:--</span>
    </div>
  </div>
</header>

<main class="container-xxl py-4">
  <div class="row g-3 g-lg-4">
    <aside class="col-12 col-lg-3 col-fixed-260">
      <div class="exam-card-slim p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h2 class="fs-6 mb-0 fw-semibold">Question Navigator</h2>
          <small class="text-muted">Jump to…</small>
        </div>

        <div id="nav-grid" class="nav-grid mb-3"></div>

        <div class="mb-2">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <small class="text-muted"><i class="fa-solid fa-chart-line me-1"></i>Progress</small>
            <strong id="progress-pct" style="color:var(--accent-color,#4f46e5)">0%</strong>
          </div>
          <div class="w3-progress"><div id="progress-bar-fill"></div></div>
          <div class="mt-1">
            <small class="text-muted">
              <span id="progress-count">0</span> of <span id="progress-total">0</span> answered
            </small>
          </div>
        </div>

        <div class="mt-3 small text-muted">
          <div class="d-flex flex-wrap gap-2">
            <span><span class="legend-dot legend-dot-current"></span>Current</span>
            <span><span class="legend-dot legend-dot-answered"></span>Answered</span>
            <span><span class="legend-dot legend-dot-review"></span>Marked</span>
            <span><span class="legend-dot legend-dot-visited"></span>Visited</span>
          </div>
        </div>

        <button id="submit-btn" class="btn btn-primary w-100 mt-3">
          <span class="btn-label"><i class="fa-solid fa-paper-plane me-2"></i>Submit Exam</span>
          <span class="btn-spinner d-none">
            <span class="spinner-border spinner-border-sm me-1"></span>Submitting…
          </span>
        </button>
      </div>
    </aside>

    <section class="col-12 col-lg">
      <div id="question-wrap" class="exam-card p-4">
        <div id="q-skeleton">
          <div class="skeleton mb-3" style="height:22px;width:60%"></div>
          <div class="skeleton mb-2" style="height:15px;width:40%"></div>
          <div class="skeleton mb-4" style="height:15px;width:30%"></div>
          <div class="skeleton" style="height:120px;width:100%"></div>
        </div>
      </div>

      <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
        <button id="prev-btn" class="btn btn-light" disabled>
          <i class="fa-solid fa-arrow-left me-2"></i>Previous
        </button>
        <button id="review-btn" class="btn btn-light">
          <i class="fa-solid fa-flag me-2"></i>Mark Review
        </button>
        <button id="next-btn" class="btn btn-primary">
          <span class="lbl">Next<i class="fa-solid fa-arrow-right ms-2"></i></span>
        </button>
      </div>
    </section>
  </div>
</main>

{{-- ✅ INTRO MODAL (Bubble Game Style) --}}
<div class="modal fade" id="examIntroModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:16px;border:1px solid var(--line-strong,#e5e7eb);box-shadow:0 18px 60px rgba(15,23,42,.2);">
      <div class="modal-header" style="border-bottom:1px solid var(--line-strong,#e5e7eb);background:var(--surface,#fff);border-top-left-radius:16px;border-top-right-radius:16px;">
        <div class="d-flex align-items-center gap-2">
          <div style="width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:rgba(79,70,229,.08);border:1px solid rgba(79,70,229,.2);">
            <i class="fa-solid fa-circle-info" style="color:var(--accent-color,#4f46e5)"></i>
          </div>
          <div>
            <div id="introQuizTitle" style="font-weight:900;font-size:1.02rem;line-height:1.15;">Exam Instructions</div>
            <div class="small text-muted">Read carefully before starting</div>
          </div>
        </div>
      </div>

      <div class="modal-body" style="background:var(--surface,#fff);">
        <div class="intro-card mb-3">
          <div class="intro-title">
            <i class="fa-solid fa-note-sticky" style="color:var(--accent-color,#4f46e5)"></i>
            Description
          </div>
          <div id="introDesc" class="intro-body">
            <div class="skeleton" style="height:14px;width:70%;margin-bottom:8px;"></div>
            <div class="skeleton" style="height:14px;width:92%;margin-bottom:8px;"></div>
            <div class="skeleton" style="height:14px;width:85%;"></div>
          </div>
        </div>

        <div class="intro-card">
          <div class="intro-title">
            <i class="fa-solid fa-book-open-reader" style="color:var(--accent-color,#4f46e5)"></i>
            Instructions
          </div>
          <div id="introInstr" class="intro-body">
            <div class="skeleton" style="height:14px;width:80%;margin-bottom:8px;"></div>
            <div class="skeleton" style="height:14px;width:90%;margin-bottom:8px;"></div>
            <div class="skeleton" style="height:14px;width:72%;"></div>
          </div>
        </div>

        <div class="mt-3 small text-muted">
          <i class="fa-solid fa-clock me-1"></i>
          Timer will start only after you press <b>Start Exam</b>.
        </div>
      </div>

      <div class="modal-footer" style="border-top:1px solid var(--line-strong,#e5e7eb);background:var(--surface,#fff);border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
        <button type="button" id="introBackBtn" class="btn btn-light">
          <i class="fa-solid fa-arrow-left me-2"></i>Back
        </button>
        <button type="button" id="introStartBtn" class="btn btn-primary">
          <i class="fa-solid fa-play me-2"></i>Start Exam
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function typeset(el){
  if (!el) return;
  const go = () => {
    try {
      if (window.MathJax?.typesetClear) MathJax.typesetClear([el]);
      return MathJax.typesetPromise([el]);
    } catch (e) { console.error('MathJax typeset error:', e); }
  };
  if (window.MathJax?.startup?.promise) MathJax.startup.promise.then(go);
  else document.getElementById('MathJax-script')?.addEventListener('load', go, { once:true });
}
</script>

<script>
/* ================== Globals ================== */
const $  = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

const token = sessionStorage.student_token || sessionStorage.token || '';
const QUIZ_KEY =
  (document.querySelector('meta[name="quiz-key"]')?.content || '').trim() ||
  new URLSearchParams(location.search).get('quiz') || '';

const STORAGE_ATTEMPT_KEY = 'attempt_uuid:' + QUIZ_KEY;
const STORAGE_CACHE_KEY   = 'exam_cache:'   + QUIZ_KEY;

if (!QUIZ_KEY) {
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({icon:'error',title:'Missing quiz key',text:'No quiz id/uuid provided in URL.'})
      .then(() => history.back());
  });
}

if (!token) {
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({icon:'error',title:'Not authenticated',text:'Please log in again to continue the exam.'})
      .then(() => window.location.href = '/login');
  });
}

let ATTEMPT_UUID = localStorage.getItem(STORAGE_ATTEMPT_KEY) || null;

let questions     = [];
let selections    = {};
let reviews       = {};
let visited       = {};
let timeSpentSec  = {};
let currentIndex  = 0;

let serverEndAt   = null;
let timerHandle   = null;
let isSubmitting  = false;

let activeQid     = null;
let activeStartMs = null;

/* ✅ exam will not start until modal Start pressed */
let EXAM_STARTED  = false;

/* ================== API helper ================== */
async function api(path, opts = {}) {
  const res = await fetch(path, {
    ...opts,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
      ...(opts.headers || {})
    }
  });

  let data = {};
  try { data = await res.json(); } catch(e){ data = {}; }

  if (!res.ok || data.success === false) {
    throw new Error(data.message || `HTTP ${res.status}`);
  }
  return data;
}

/* ================== Intro Modal (Bootstrap) ================== */
function pickFirstNonEmpty(...vals){
  for(const v of vals){
    if(v === null || v === undefined) continue;
    const s = String(v).trim();
    if(s !== '') return s;
  }
  return '';
}

function escapeHtml(str){
  return (str ?? '').toString()
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}

/* ✅ allowlist sanitizer */
function sanitizeHtmlAllowList(inputHtml){
  const html = String(inputHtml ?? '').trim();
  if(!html) return '';

  const allowed = new Set([
    'B','I','EM','STRONG','U','BR',
    'P','DIV','SPAN','UL','OL','LI',
    'A','CODE','PRE','HR','BLOCKQUOTE',
    'SMALL','SUP','SUB','H1','H2','H3','H4','H5','H6'
  ]);

  const doc = new DOMParser().parseFromString(html, 'text/html');

  const cleanNode = (node) => {
    if (node.nodeType === Node.COMMENT_NODE) {
      node.remove(); return;
    }
    if (node.nodeType === Node.ELEMENT_NODE) {
      const tag = node.tagName;

      if (!allowed.has(tag)) {
        const parent = node.parentNode;
        if (!parent) return;
        while (node.firstChild) parent.insertBefore(node.firstChild, node);
        parent.removeChild(node);
        return;
      }

      [...node.attributes].forEach(attr => {
        const name = attr.name.toLowerCase();
        const val  = String(attr.value || '');

        if (name.startsWith('on') || name === 'style') {
          node.removeAttribute(attr.name); return;
        }

        if (tag === 'A') {
          if (name === 'href') {
            const href = val.trim();
            const safe = /^https?:\/\//i.test(href) || /^mailto:/i.test(href) || href.startsWith('#');
            if (!safe) node.removeAttribute('href');
            return;
          }
          if (!['href','target','rel'].includes(name)) node.removeAttribute(attr.name);
          return;
        }

        node.removeAttribute(attr.name);
      });

      if (tag === 'A') {
        node.setAttribute('target','_blank');
        node.setAttribute('rel','noopener noreferrer');
      }
    }
    [...node.childNodes].forEach(cleanNode);
  };

  [...doc.body.childNodes].forEach(cleanNode);
  return doc.body.innerHTML.trim();
}

function renderSafeHtmlOrText(raw){
  const s = String(raw ?? '').trim();
  if(!s) return '';

  const looksHtml = /<\/?[a-z][\s\S]*>/i.test(s);
  if (looksHtml) {
    const cleaned = sanitizeHtmlAllowList(s);
    return cleaned || `<div>${escapeHtml(s)}</div>`;
  }

  return `<div>${escapeHtml(s).replace(/\n/g,'<br>')}</div>`;
}
async function fetchQuizMeta(){
  try{
    const res = await api(`/api/exam/quizzes/${encodeURIComponent(QUIZ_KEY)}`, { method:'GET' });

    // ✅ Your API returns: { data: { quiz_description, instructions, quiz_name ... } }
    const meta = res?.data || res?.quiz || res;

    return meta && typeof meta === 'object' ? meta : null;
  }catch(e){
    console.error('fetchQuizMeta failed:', e);
    return null;
  }
}

async function openIntroModal(){
  const el = document.getElementById('examIntroModal');
  const modal = new bootstrap.Modal(el, { backdrop:'static', keyboard:false });

  // ✅ show modal first (skeleton / loading text)
  modal.show();

  // ✅ default placeholders while loading
  document.getElementById('introQuizTitle').textContent = 'Loading…';
  document.getElementById('introDesc').innerHTML  = `<div class="text-muted small">Loading description…</div>`;
  document.getElementById('introInstr').innerHTML = `<div class="text-muted small">Loading instructions…</div>`;

  // ✅ pull live from API
  const meta = await fetchQuizMeta();

  // ✅ Title (quiz_name is your correct key)
  const title = pickFirstNonEmpty(
    meta?.quiz_name,
    meta?.title,
    meta?.name,
    'Exam'
  );

  // ✅ Description (quiz_description is your correct key)
  const descRaw = pickFirstNonEmpty(
    meta?.quiz_description,
    meta?.description_html,
    meta?.description,
    meta?.desc
  );

  // ✅ Instructions (instructions is your correct key)
  const instRaw = pickFirstNonEmpty(
    meta?.instructions,
    meta?.instructions_html,
    meta?.instruction,
    meta?.rules
  );

  document.getElementById('introQuizTitle').textContent = `${title} • Instructions`;
  document.getElementById('introDesc').innerHTML  = renderSafeHtmlOrText(descRaw || 'No description provided.');
  document.getElementById('introInstr').innerHTML = renderSafeHtmlOrText(instRaw || 'No instructions provided.');

  // ✅ buttons
  document.getElementById('introBackBtn').onclick = () => {
    modal.hide();
    history.back();
  };

  document.getElementById('introStartBtn').onclick = async () => {
    modal.hide();
    EXAM_STARTED = true;
    await bootExam();
  };
}


/* ================== Cache ================== */
function cacheLoad(){
  try{
    const raw = localStorage.getItem(STORAGE_CACHE_KEY);
    if (!raw) return false;
    const c = JSON.parse(raw);

    if (!c || typeof c !== 'object') return false;
    if (c.attempt_uuid && ATTEMPT_UUID && c.attempt_uuid !== ATTEMPT_UUID) return false;

    questions     = Array.isArray(c.questions) ? c.questions : [];
    selections    = (c.selections && typeof c.selections === 'object') ? c.selections : {};
    reviews       = (c.reviews   && typeof c.reviews === 'object') ? c.reviews   : {};
    visited       = (c.visited   && typeof c.visited === 'object') ? c.visited   : {};
    timeSpentSec  = (c.timeSpentSec && typeof c.timeSpentSec === 'object') ? c.timeSpentSec : {};
    currentIndex  = Number.isFinite(Number(c.currentIndex)) ? Number(c.currentIndex) : 0;
    serverEndAt   = c.serverEndAt || null;

    questions.forEach(q => {
      if (String(q.question_type).toLowerCase() === 'fill_in_the_blank') {
        const cur = selections[q.question_id];
        if (cur == null) selections[q.question_id] = [];
        else if (!Array.isArray(cur)) {
          const val = String(cur).trim();
          selections[q.question_id] = val ? [val] : [];
        }
      }
    });

    return questions.length > 0;
  }catch(_){
    return false;
  }
}

let cacheSaveTimer = null;
function cacheSaveDebounced(){
  if (cacheSaveTimer) clearTimeout(cacheSaveTimer);
  cacheSaveTimer = setTimeout(cacheSave, 250);
}
function cacheSave(){
  try{
    const payload = {
      attempt_uuid: ATTEMPT_UUID,
      serverEndAt,
      currentIndex,
      questions,
      selections,
      reviews,
      visited,
      timeSpentSec,
      savedAt: Date.now()
    };
    localStorage.setItem(STORAGE_CACHE_KEY, JSON.stringify(payload));
  }catch(_){}
}

function clearAllExamClientState(){
  try{
    if (timerHandle) { clearInterval(timerHandle); timerHandle = null; }
    if (cacheSaveTimer) { clearTimeout(cacheSaveTimer); cacheSaveTimer = null; }

    if (QUIZ_KEY) {
      localStorage.removeItem(STORAGE_ATTEMPT_KEY);
      localStorage.removeItem(STORAGE_CACHE_KEY);
      sessionStorage.removeItem(STORAGE_ATTEMPT_KEY);
      sessionStorage.removeItem(STORAGE_CACHE_KEY);
    }

    ATTEMPT_UUID = null;
    questions = [];
    selections = {};
    reviews = {};
    visited = {};
    timeSpentSec = {};
    currentIndex = 0;
    serverEndAt = null;
    activeQid = null;
    activeStartMs = null;
  }catch(_){}
}

/* ================== Timer ================== */
const mmss = s => {
  s = Math.max(0, Math.floor(s));
  const m = String(Math.floor(s/60)).padStart(2,'0');
  const n = String(s%60).padStart(2,'0');
  return `${m}:${n}`;
};

function computeTimeLeft(){
  if (!serverEndAt) return 0;
  const end = new Date(serverEndAt).getTime();
  const now = Date.now();
  return Math.max(0, Math.floor((end - now) / 1000));
}

function startTimerFromServerEnd(){
  const tick = async () => {
    const left = computeTimeLeft();
    $('#time-left').textContent = mmss(left);

    if (left <= 0) {
      clearInterval(timerHandle);
      timerHandle = null;
      await doSubmit(true);
    }
  };

  tick();
  if (timerHandle) clearInterval(timerHandle);
  timerHandle = setInterval(tick, 1000);
}

/* ================== UI helpers ================== */
function showSkeleton(on=true){ $('#q-skeleton')?.classList.toggle('d-none', !on); }

function typeLabel(t){
  t = String(t || '').toLowerCase();
  if (t === 'fill_in_the_blank') return 'Fill in the blanks';
  if (t === 'true_false')       return 'True / False';
  if (t === 'mcq')              return 'Single choice';
  return 'Single choice';
}

function normalizeTeX(s) {
  return String(s ?? '')
    .replace(/\\\\\[/g, '\\[')
    .replace(/\\\\\]/g, '\\]')
    .replace(/\\\\\(/g, '\\(')
    .replace(/\\\\\)/g, '\\)');
}

function answeredVal(qid){
  const sel = selections[qid];
  if (sel == null) return false;
  if (Array.isArray(sel)) return sel.filter(v => String(v).trim() !== '').length > 0;
  return String(sel).trim() !== '';
}

function updateProgress(){
  const done  = questions.filter(q => answeredVal(q.question_id)).length;
  const total = questions.length || 1;
  const pct   = Math.round((done / total) * 100);
  $('#progress-count').textContent = String(done);
  $('#progress-total').textContent = String(questions.length);
  $('#progress-pct').textContent   = pct + '%';
  $('#progress-bar-fill').style.width = pct + '%';
}

function refreshNav(){
  const grid = $('#nav-grid').children;
  questions.forEach((q, idx) => {
    const btn = grid[idx];
    btn.className = 'nav-btn';
    if (idx === currentIndex) btn.classList.add('current');
    else if (reviews[q.question_id]) btn.classList.add('review');
    else if (answeredVal(q.question_id)) btn.classList.add('answered');
    else if (visited[q.question_id]) btn.classList.add('visited');
  });
}

/* ================== Time spent ================== */
function enterQuestion(qid){
  if (!qid) return;
  if (activeQid && activeQid !== qid) leaveQuestion(activeQid);
  activeQid = Number(qid);
  activeStartMs = Date.now();
}
function leaveQuestion(qid){
  qid = Number(qid);
  if (!qid) return;
  if (activeQid !== qid || !activeStartMs) return;

  const diffSec = Math.max(1, Math.round((Date.now() - activeStartMs) / 1000));
  timeSpentSec[qid] = (Number(timeSpentSec[qid] || 0) + diffSec);

  activeQid = null;
  activeStartMs = null;

  cacheSaveDebounced();
}

/* ================== Render ================== */
function countGaps(q){
  const title = String(q.question_title || '');
  const desc  = String(q.question_description || '');
  const re    = /\{dash\}/gi;
  const n1    = (title.match(re) || []).length;
  const n2    = (desc.match(re)  || []).length;
  if (n1 + n2 > 0) return n1 + n2;
  const ansLen = Array.isArray(q.answers) ? q.answers.length : 0;
  return ansLen > 0 ? ansLen : 1;
}

function collectSelectionFor(q){
  const multi = !!q.has_multiple_correct_answer;
  const type  = String(q.question_type || '').toLowerCase();

  if (type === 'fill_in_the_blank') {
    return $$('#options input[data-fib-index]').map(i => i.value || '');
  }
  const checked = $$('#options input:checked').map(i => Number(i.value));
  return multi ? checked : (checked[0] ?? null);
}

function renderQuestion(){
  const q = questions[currentIndex];
  if (!q) return;

  visited[q.question_id] = true;
  cacheSaveDebounced();

  const wrap = $('#question-wrap');
  const rawType = String(q.question_type || '').toLowerCase();
  const multi   = !!q.has_multiple_correct_answer;
  const label   = multi && rawType !== 'fill_in_the_blank' ? 'Multiple choice' : typeLabel(rawType);

  const toDisplay = s =>
    normalizeTeX(String(s || '')).replace(/\{dash\}/gi, '<span class="fib-underline">&nbsp;</span>');

  const titleHTML = toDisplay(q.question_title);
  const descHTML  = q.question_description ? toDisplay(q.question_description) : '';

  let html = `
    <div class="d-flex align-items-start justify-content-between gap-3">
      <div class="flex-grow-1">
        <div class="question-title mb-1">Q${currentIndex + 1}. ${titleHTML}</div>
        ${descHTML ? `<div class="small text-muted mb-2">${descHTML}</div>` : ``}
        <div class="question-meta">
          Marks: <b>${q.question_mark ?? 1}</b>
          <span class="mx-1">•</span>
          <span class="question-badge">${label}</span>
        </div>
      </div>
      <span class="badge rounded-pill text-bg-info ${reviews[q.question_id] ? '' : 'invisible'}">Review</span>
    </div>
    <div class="mt-3" id="options">`;

  const sel = selections[q.question_id];

  if (rawType === 'fill_in_the_blank') {
    const gaps = countGaps(q);
    const values = Array.isArray(sel) ? sel.slice(0, gaps).map(v => String(v)) : [];
    while (values.length < gaps) values.push('');

    html += `
      <div class="opt p-3 fib-fields">
        <label class="form-label small mb-2">Your answers</label>
        <div class="row g-2">`;
    for (let i = 0; i < gaps; i++) {
      html += `
        <div class="col-12 col-sm-6 col-md-4">
          <input class="form-control" data-fib-index="${i}" placeholder="Answer ${i+1}"
                 value="${escapeHtml(values[i] || '')}">
        </div>`;
    }
    html += `
        </div>
        <div class="form-text">Enter each blank separately. Answers are case-insensitive.</div>
      </div>`;
  } else {
    (q.answers || []).forEach(a => {
      const checked = multi
        ? Array.isArray(sel) && sel.map(Number).includes(Number(a.answer_id))
        : (!Array.isArray(sel) && Number(sel) === Number(a.answer_id));

      html += `
        <label class="opt form-check d-flex align-items-center gap-2">
          <input class="form-check-input" type="${multi ? 'checkbox' : 'radio'}"
                 name="q_${q.question_id}${multi ? '[]' : ''}" value="${a.answer_id}" ${checked ? 'checked' : ''}/>
          <span class="form-check-label">${a.answer_title ?? ''}</span>
        </label>`;
    });
  }

  html += `</div>`;
  wrap.innerHTML = html;

  wrap.querySelector('.question-title')?.classList.add('tex2jax_process');
  wrap.querySelectorAll('.form-check-label').forEach(n => n.classList.add('tex2jax_process'));
  wrap.querySelectorAll('.fib-fields').forEach(n => n.classList.add('tex2jax_process'));
  typeset(wrap);

  if (rawType === 'fill_in_the_blank') {
    $$('#options input[data-fib-index]').forEach(inp => {
      const updateLocal = () => {
        const arr = $$('#options input[data-fib-index]').map(i => i.value || '');
        selections[q.question_id] = arr;
        cacheSaveDebounced();
        updateProgress();
        refreshNav();
      };
      inp.addEventListener('input', updateLocal);
      inp.addEventListener('blur', updateLocal);
    });
  } else {
    $$('#options input').forEach(inp => {
      inp.addEventListener('change', () => {
        selections[q.question_id] = collectSelectionFor(q);
        cacheSaveDebounced();
        updateProgress();
        refreshNav();
      });
    });
  }

  $('#prev-btn').disabled = currentIndex === 0;
  $('#next-btn .lbl').innerHTML =
    (currentIndex < questions.length - 1)
      ? `Next<i class="fa-solid fa-arrow-right ms-2"></i>`
      : `Submit<i class="fa-solid fa-paper-plane ms-2"></i>`;

  $('#review-btn').innerHTML = reviews[q.question_id]
    ? `<i class="fa-solid fa-flag me-2"></i>Unmark Review`
    : `<i class="fa-solid fa-flag me-2"></i>Mark Review`;

  refreshNav();
  updateProgress();
}

/* ================== Navigator ================== */
function buildNavigator(){
  const grid = $('#nav-grid');
  grid.innerHTML = '';

  questions.forEach((q, idx) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'nav-btn';
    b.textContent = String(idx + 1);
    b.addEventListener('click', () => navigateTo(idx));
    grid.appendChild(b);
  });

  refreshNav();
}

function navigateTo(targetIdx){
  if (!EXAM_STARTED) return;
  if (targetIdx < 0 || targetIdx >= questions.length) return;
  if (targetIdx === currentIndex) return;

  const prevQ = questions[currentIndex];
  if (prevQ?.question_id) leaveQuestion(prevQ.question_id);

  currentIndex = targetIdx;
  cacheSaveDebounced();

  renderQuestion();

  const nextQ = questions[currentIndex];
  if (nextQ?.question_id) enterQuestion(nextQ.question_id);
}

/* ================== Actions ================== */
function onPrev(){
  if (!EXAM_STARTED) return;
  if (currentIndex <= 0) return;
  navigateTo(currentIndex - 1);
}

function onNext(){
  if (!EXAM_STARTED) return;
  if (currentIndex < questions.length - 1) navigateTo(currentIndex + 1);
  else doSubmit(false);
}

function onToggleReview(){
  if (!EXAM_STARTED) return;
  const q = questions[currentIndex];
  if (!q) return;
  reviews[q.question_id] = !reviews[q.question_id];
  cacheSaveDebounced();
  renderQuestion();
}

/* ================== Submit ================== */
function showSubmitting(){
  Swal.fire({
    title: 'Submitting…',
    html: 'Saving your answers in bulk, please wait.',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading()
  });
}

async function doSubmit(auto){
  if (isSubmitting) return;

  if (!auto) {
    const res = await Swal.fire({
      title:'Submit exam?',
      text:'Once submitted, answers cannot be changed.',
      icon:'question',
      showCancelButton:true,
      confirmButtonText:'Submit',
      cancelButtonText:'Cancel',
      reverseButtons:true
    });
    if (!res.isConfirmed) return;
  }

  try{
    isSubmitting = true;

    $('#submit-btn').disabled = true;
    $('#submit-btn .btn-label').classList.add('d-none');
    $('#submit-btn .btn-spinner').classList.remove('d-none');

    showSubmitting();

    const curQ = questions[currentIndex];
    if (curQ?.question_id) leaveQuestion(curQ.question_id);

    const answers = questions.map(q => {
      const qid = Number(q.question_id);
      return {
        question_id: qid,
        selected: (selections[qid] ?? null),
        time_spent_sec: Number(timeSpentSec[qid] || 0)
      };
    });

    await api(`/api/exam/attempts/${ATTEMPT_UUID}/bulk-answer`, {
      method:'POST',
      body: JSON.stringify({ answers })
    });

    await api(`/api/exam/attempts/${ATTEMPT_UUID}/submit`, { method:'POST' });

    if (timerHandle) clearInterval(timerHandle);

    Swal.close();
    clearAllExamClientState();

    await Swal.fire({
      icon:'success',
      title:'Exam submitted successfully',
      text:'Your responses have been recorded.',
      confirmButtonText:'OK'
    });

    window.location.replace(`/dashboard`);

  }catch(e){
    console.error(e);
    Swal.close();
    Swal.fire({icon:'error',title:'Submit failed',text:e.message || 'Please try again.'});
  }finally{
    isSubmitting = false;
    $('#submit-btn').disabled = false;
    $('#submit-btn .btn-label').classList.remove('d-none');
    $('#submit-btn .btn-spinner').classList.add('d-none');
  }
}

/* ================== BOOT EXAM (after modal start) ================== */
async function bootExam(){
  try{
    showSkeleton(true);

    const hasCache = cacheLoad();

    if (!ATTEMPT_UUID) {
      const started = await api(`/api/exam/quizzes/${encodeURIComponent(QUIZ_KEY)}/start`, { method:'POST' });
      const attempt = started.attempt || started.data?.attempt || started.data || {};
      ATTEMPT_UUID  = attempt.attempt_uuid || null;

      localStorage.setItem(STORAGE_ATTEMPT_KEY, ATTEMPT_UUID);
      serverEndAt = attempt.server_end_at || null;

      if (attempt.quiz_name) document.title = attempt.quiz_name + ' • Exam';
      cacheSave();
    }

    if (!hasCache || !questions.length) {
      const data = await api(`/api/exam/attempts/${ATTEMPT_UUID}/questions`);
      const pack = data.data || data;

      questions  = pack.questions || [];
      selections = pack.selections || {};

      if (pack.attempt?.server_end_at) serverEndAt = pack.attempt.server_end_at;

      questions.forEach(q => {
        if (String(q.question_type).toLowerCase() === 'fill_in_the_blank') {
          const cur = selections[q.question_id];
          if (cur == null) selections[q.question_id] = [];
          else if (!Array.isArray(cur)) {
            const val = String(cur).trim();
            selections[q.question_id] = val ? [val] : [];
          }
        }
      });

      currentIndex = 0;
      cacheSave();
    }

    showSkeleton(false);
    buildNavigator();
    currentIndex = Math.min(Math.max(0, currentIndex), Math.max(0, questions.length - 1));
    renderQuestion();

    startTimerFromServerEnd();

    const q = questions[currentIndex];
    if (q?.question_id) enterQuestion(q.question_id);

    $('#prev-btn').addEventListener('click', onPrev);
    $('#next-btn').addEventListener('click', onNext);
    $('#review-btn').addEventListener('click', onToggleReview);
    $('#submit-btn').addEventListener('click', () => doSubmit(false));

    window.addEventListener('beforeunload', () => {
      const cur = questions[currentIndex];
      if (cur?.question_id) leaveQuestion(cur.question_id);
      cacheSave();
    });

  }catch(e){
    console.error(e);
    showSkeleton(false);
    Swal.fire({icon:'error',title:'Cannot start exam',text:e.message || 'Please try again.'});
  }
}

/* ================== Boot (Modal first) ================== */
document.addEventListener('DOMContentLoaded', async () => {
  await openIntroModal(); // ✅ shows normal bootstrap modal first
});
</script>
</body>
</html>
