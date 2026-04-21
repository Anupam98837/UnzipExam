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
    .question-badge-hint{font-size:.72rem;padding:.16rem .55rem;border-radius:999px;background:rgba(22,163,74,.07);color:#15803d;border:1px solid rgba(22,163,74,.25)}

    .opt{border-radius:12px;border:1px solid var(--line-soft,#e5e7eb);padding:.65rem .75rem;margin-bottom:.35rem;background:var(--surface,#fff);cursor:pointer;transition:background .16s ease,border-color .16s ease,box-shadow .16s ease}
    .opt:hover{background:var(--page-hover,#f7f8fc);border-color:var(--accent-color,#4f46e5);box-shadow:0 6px 16px rgba(15,23,42,.05)}
    .opt input.form-check-input{margin-top:0;cursor:pointer}
    .opt .form-check-label{cursor:pointer;font-size:.92rem}

    .fib-underline{display:inline-block;min-width:90px;border-bottom:2px solid #cbd5e1;margin:0 .22rem .2rem .22rem}
    .fib-fields .form-control{height:40px;border-radius:10px}

    mjx-container[display="block"]{display:block!important;margin:.5rem 0}
    mjx-container{overflow-x:auto}

    @media (min-width:992px){.col-fixed-260{flex:0 0 260px;max-width:260px}}

    .exam-locked{pointer-events:none;opacity:.85;filter:saturate(.85)}

    /* Fullscreen Warning Overlay */
    #fullscreen-warning-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.95);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(10px);
    }
    #fullscreen-warning-overlay.active {
      display: flex;
    }
    .warning-content {
      background: white;
      padding: 3rem;
      border-radius: 20px;
      text-align: center;
      max-width: 500px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    .warning-icon {
      font-size: 4rem;
      color: #ef4444;
      margin-bottom: 1rem;
    }
    .warning-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 1rem;
    }
    .warning-message {
      color: #6b7280;
      margin-bottom: 2rem;
      line-height: 1.6;
    }
    .warning-count {
      font-size: 2rem;
      font-weight: 700;
      color: #ef4444;
      margin-bottom: 1rem;
    }
    #violation-badge {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 1000;
      background: #fee;
      border: 2px solid #ef4444;
      padding: 0.5rem 1rem;
      border-radius: 10px;
      font-weight: 600;
      color: #dc2626;
      display: none;
      animation: pulse 2s infinite;
    }
    #violation-badge.show {
      display: block;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }

    /* Ready to start rules list */
    .exam-rules-list {
      text-align: left;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .exam-rules-list li {
      display: flex;
      align-items: flex-start;
      gap: .6rem;
      padding: .5rem .75rem;
      border-radius: 10px;
      margin-bottom: .4rem;
      font-size: .9rem;
      background: #fef2f2;
      color: #7f1d1d;
      border: 1px solid #fecaca;
    }
    .exam-rules-list li i {
      margin-top: .15rem;
      flex-shrink: 0;
      color: #ef4444;
    }
    .exam-rules-list li.rule-ok {
      background: #f0fdf4;
      color: #14532d;
      border-color: #bbf7d0;
    }
    .exam-rules-list li.rule-ok i { color: #16a34a; }
  </style>
</head>
<body>

<!-- Fullscreen Warning Overlay -->
<div id="fullscreen-warning-overlay">
  <div class="warning-content">
    <div class="warning-icon">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <div class="warning-title">Tab Switch Detected!</div>
    <div class="warning-message">
      You have left the exam window. This action has been logged.
      <br><br>
      <strong>Multiple violations may result in automatic submission.</strong>
    </div>
    <div class="warning-count">
      Violation <span id="violation-count">1</span> of <span id="max-violations">3</span>
    </div>
    <button id="return-to-exam-btn" class="btn btn-primary btn-lg">
      <i class="fa-solid fa-arrow-left me-2"></i>Return to Exam
    </button>
  </div>
</div>

<!-- Violation Badge -->
<div id="violation-badge">
  <i class="fa-solid fa-exclamation-triangle me-2"></i>
  Violations: <span id="badge-count">0</span>/3
</div>

<header class="exam-header sticky-top">
  <div class="container-xxl d-flex align-items-center justify-content-between py-3">
    <div class="d-flex align-items-center gap-2">
      <div class="exam-logo"><span>Exam</span> Portal</div>
      <span class="badge rounded-pill text-bg-light border">
        <i class="fa-solid fa-pencil me-1"></i> Live
      </span>
      <span id="fullscreen-status" class="badge rounded-pill text-bg-success">
        <i class="fa-solid fa-expand me-1"></i> Fullscreen
      </span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button id="fullscreen-btn" class="btn btn-light btn-sm" title="Enter Fullscreen" onclick="requestFullscreen()">
        <i class="fa-solid fa-expand me-1"></i> Fullscreen
      </button>
      <div id="timer-pill" class="timer-pill">
        <i class="fa-solid fa-clock"></i>
        <span id="time-left">--:--</span>
      </div>
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
/* ================== Fullscreen & Tab Switch Detection ================== */
const MAX_VIOLATIONS = 3;
let violationCount = 0;
let isFullscreenActive = false;
let tabSwitchLogged = false;

function requestFullscreen() {
  const elem = document.documentElement;
  if (elem.requestFullscreen) {
    elem.requestFullscreen().catch(err => {
      console.warn('Fullscreen request failed:', err);
    });
  } else if (elem.webkitRequestFullscreen) {
    elem.webkitRequestFullscreen();
  } else if (elem.msRequestFullscreen) {
    elem.msRequestFullscreen();
  }
}

function updateFullscreenStatus() {
  isFullscreenActive = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);

  const statusBadge = document.getElementById('fullscreen-status');
  const fsBtn = document.getElementById('fullscreen-btn');
  if (isFullscreenActive) {
    statusBadge.innerHTML = '<i class="fa-solid fa-expand me-1"></i> Fullscreen';
    statusBadge.className = 'badge rounded-pill text-bg-success';
    if (fsBtn) fsBtn.classList.add('d-none');
  } else {
    statusBadge.innerHTML = '<i class="fa-solid fa-compress me-1"></i> Exit Fullscreen';
    statusBadge.className = 'badge rounded-pill text-bg-warning';
    if (fsBtn) fsBtn.classList.remove('d-none');
  }
}

function logViolation(type) {
  violationCount++;

  // Update badge
  document.getElementById('badge-count').textContent = violationCount;
  document.getElementById('violation-badge').classList.add('show');

  console.warn(`Violation #${violationCount}: ${type}`);
  // api('/api/exam/log-violation', { method: 'POST', body: JSON.stringify({ type, count: violationCount }) });

  return violationCount;
}

function handleTabSwitch() {
  if (tabSwitchLogged || !EXAM_STARTED || isSubmitting) return;

  tabSwitchLogged = true;
  const currentCount = logViolation('Tab Switch');

  // Show warning overlay
  document.getElementById('violation-count').textContent = currentCount;
  document.getElementById('max-violations').textContent = MAX_VIOLATIONS;
  document.getElementById('fullscreen-warning-overlay').classList.add('active');

  // Auto-submit if max violations reached
  if (currentCount >= MAX_VIOLATIONS) {
    setTimeout(() => {
      Swal.fire({
        icon: 'warning',
        title: 'Maximum Violations Reached',
        text: 'Your exam will be auto-submitted due to multiple tab switches.',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false
      }).then(() => {
        doSubmit(true);
      });
    }, 2000);
  }
}

function handleFullscreenExit() {
  if (!isFullscreenActive || !EXAM_STARTED || isSubmitting) return;

  const currentCount = logViolation('Fullscreen Exit');

  Swal.fire({
    icon: 'warning',
    title: 'Fullscreen Required',
    text: `Please stay in fullscreen mode during the exam. Violation ${currentCount}/${MAX_VIOLATIONS} logged.`,
    confirmButtonText: 'Enter Fullscreen',
    allowOutsideClick: false,
    allowEscapeKey: false
  }).then(() => {
    requestFullscreen();
  });

  // Auto-submit if max violations reached
  if (currentCount >= MAX_VIOLATIONS) {
    setTimeout(() => {
      doSubmit(true);
    }, 2000);
  }
}

// Return to exam button
document.getElementById('return-to-exam-btn')?.addEventListener('click', () => {
  document.getElementById('fullscreen-warning-overlay').classList.remove('active');
  tabSwitchLogged = false;
  requestFullscreen();
});

// Detect visibility change (tab switch)
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    handleTabSwitch();
    const cur = questions[currentIndex];
    if (cur?.question_id) leaveQuestion(cur.question_id);
    cacheSave();
  } else {
    tabSwitchLogged = false;
  }
});

// Detect fullscreen change
document.addEventListener('fullscreenchange', updateFullscreenStatus);
document.addEventListener('webkitfullscreenchange', updateFullscreenStatus);
document.addEventListener('msfullscreenchange', updateFullscreenStatus);

// Detect fullscreen exit
document.addEventListener('fullscreenchange', () => {
  if (!isFullscreenActive) handleFullscreenExit();
});

// Prevent right-click during exam
document.addEventListener('contextmenu', (e) => {
  if (EXAM_STARTED && !isSubmitting) {
    e.preventDefault();
    return false;
  }
});

// Prevent common shortcuts
document.addEventListener('keydown', (e) => {
  if (!EXAM_STARTED || isSubmitting) return;

  if (e.key === 'F11') {
    e.preventDefault();
    return false;
  }

  if ((e.ctrlKey || e.metaKey) && ['w', 't', 'n'].includes(e.key.toLowerCase())) {
    e.preventDefault();
    return false;
  }

  if (e.altKey && e.key === 'Tab') {
    e.preventDefault();
    return false;
  }
});

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

let EXAM_STARTED  = false;

let AUTO_SUBMIT_FIRED = false;

/* ================== Utilities ================== */
function disableExamUI(lock=true){
  const wrap = $('#question-wrap');
  const nav  = $('#nav-grid');
  [wrap, nav, $('#prev-btn'), $('#next-btn'), $('#review-btn')].forEach(el=>{
    if(!el) return;
    if(lock) el.classList.add('exam-locked');
    else el.classList.remove('exam-locked');
    if (el.tagName === 'BUTTON') el.disabled = !!lock;
  });

  $$('#question-wrap input, #question-wrap textarea, #question-wrap select').forEach(i=>{
    i.disabled = !!lock;
  });
}

function ensureAttemptUuid(){
  if (ATTEMPT_UUID) return ATTEMPT_UUID;

  ATTEMPT_UUID = localStorage.getItem(STORAGE_ATTEMPT_KEY) || null;
  if (ATTEMPT_UUID) return ATTEMPT_UUID;

  try{
    const raw = localStorage.getItem(STORAGE_CACHE_KEY);
    if(raw){
      const c = JSON.parse(raw);
      if(c?.attempt_uuid){
        ATTEMPT_UUID = c.attempt_uuid;
        localStorage.setItem(STORAGE_ATTEMPT_KEY, ATTEMPT_UUID);
        return ATTEMPT_UUID;
      }
    }
  }catch(_){}

  return null;
}

function parseServerDate(val){
  if(!val) return null;
  const s = String(val).trim();
  if(!s) return null;

  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:\.(\d+))?$/);
  if (m){
    const yyyy = Number(m[1]);
    const mm   = Number(m[2]) - 1;
    const dd   = Number(m[3]);
    const hh   = Number(m[4]);
    const mi   = Number(m[5]);
    const ss   = Number(m[6] || 0);
    const ms   = Number(String(m[7] || '0').slice(0,3));
    const d = new Date(yyyy, mm, dd, hh, mi, ss, ms);
    return isNaN(d.getTime()) ? null : d;
  }

  const d = new Date(s);
  return isNaN(d.getTime()) ? null : d;
}

function computeTimeLeft(){
  const endDate = parseServerDate(serverEndAt);
  if (!endDate) return null;
  return Math.max(0, Math.floor((endDate.getTime() - Date.now()) / 1000));
}

const mmss = s => {
  s = Math.max(0, Math.floor(s));
  const m = String(Math.floor(s/60)).padStart(2,'0');
  const n = String(s%60).padStart(2,'0');
  return `${m}:${n}`;
};

async function api(path, opts = {}) {
  const controller = new AbortController();
  const timeoutMs = Number(opts.timeoutMs || 20000);
  const t = setTimeout(() => controller.abort(), timeoutMs);

  try{
    const res = await fetch(path, {
      ...opts,
      signal: controller.signal,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
        ...(opts.headers || {})
      }
    });

    let data = {};
    try { data = await res.json(); } catch(e){ data = {}; }

    if (!res.ok || data.success === false) {
      const err = new Error(data.message || `HTTP ${res.status}`);
      err.status = res.status;
      err.payload = data;
      throw err;
    }

    return data;
  } catch (e){
    if (e?.name === 'AbortError') {
      const err = new Error('Request timed out. Please check your internet and try again.');
      err.status = 408;
      throw err;
    }
    throw e;
  } finally {
    clearTimeout(t);
  }
}

/* ================== Cache ================== */
function cacheLoad(){
  try{
    const raw = localStorage.getItem(STORAGE_CACHE_KEY);
    if (!raw) return false;
    const c = JSON.parse(raw);
    if (!c || typeof c !== 'object') return false;

    if(!ATTEMPT_UUID && c.attempt_uuid){
      ATTEMPT_UUID = c.attempt_uuid;
      localStorage.setItem(STORAGE_ATTEMPT_KEY, ATTEMPT_UUID);
    }

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
    AUTO_SUBMIT_FIRED = false;
  }catch(_){}
}

/* ================== Timer ================== */
function startTimerFromServerEnd(){
  const tick = async () => {
    const left = computeTimeLeft();

    if (left === null) {
      $('#time-left').textContent = '--:--';
      return;
    }

    $('#time-left').textContent = mmss(left);

    if (left <= 0 && !AUTO_SUBMIT_FIRED) {
      AUTO_SUBMIT_FIRED = true;
      if (timerHandle) { clearInterval(timerHandle); timerHandle = null; }
      disableExamUI(true);

      Swal.fire({
        icon: 'info',
        title: 'Time\'s Up!',
        text: 'Your exam time has ended. Submitting your answers automatically...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
      });

      await new Promise(resolve => setTimeout(resolve, 1500));
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

  // Determine the hint badge text
  let hintBadge = '';
  if (rawType !== 'fill_in_the_blank' && rawType !== 'true_false') {
    if (multi) {
      hintBadge = `<span class="question-badge-hint ms-1"><i class="fa-solid fa-list-check me-1"></i>One or more answers</span>`;
    } else {
      hintBadge = `<span class="question-badge-hint ms-1"><i class="fa-solid fa-circle-dot me-1"></i>One answer only</span>`;
    }
  }

  const toDisplay = s =>
    normalizeTeX(String(s || '')).replace(/\{dash\}/gi, '<span class="fib-underline">&nbsp;</span>');

  const titleHTML = toDisplay(q.question_title);
  const descHTML  = q.question_description ? toDisplay(q.question_description) : '';

  let html = `
    <div class="d-flex align-items-start justify-content-between gap-3">
      <div class="flex-grow-1">
        <div class="question-title mb-1">Q${currentIndex + 1}. ${titleHTML}</div>
        ${descHTML ? `<div class="small text-muted mb-2">${descHTML}</div>` : ``}
        <div class="question-meta d-flex align-items-center flex-wrap gap-1">
          Marks: <b>${q.question_mark ?? 1}</b>
          <span class="mx-1">•</span>
          <span class="question-badge">${label}</span>${hintBadge}
        </div>
      </div>
      <span class="badge rounded-pill text-bg-info ${reviews[q.question_id] ? '' : 'invisible'}">Review</span>
    </div>
    <div class="mt-3" id="options">`;

  const sel = selections[q.question_id];

  const escapeHtml = (str) => (str ?? '').toString()
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');

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

function isAttemptMissingError(e){
  const msg = String(e?.message || '').toLowerCase();
  const payloadMsg = String(e?.payload?.message || '').toLowerCase();
  return (
    e?.status === 404 ||
    msg.includes('attempt not found') ||
    payloadMsg.includes('attempt not found') ||
    msg.includes('not found')
  );
}

async function doSubmit(auto){
  if (isSubmitting) return;

  const au = ensureAttemptUuid();
  if (!au) {
    await Swal.fire({icon:'error', title:'Cannot submit', text:'Attempt id missing. Please refresh once and try again.'});
    return;
  }

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
    disableExamUI(true);

    $('#submit-btn').disabled = true;
    $('#submit-btn .btn-label').classList.add('d-none');
    $('#submit-btn .btn-spinner').classList.remove('d-none');

    if (!auto) {
      showSubmitting();
    }

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

    try {
      await api(`/api/exam/attempts/${encodeURIComponent(au)}/bulk-answer`, {
        method:'POST',
        body: JSON.stringify({ answers }),
        timeoutMs: 25000
      });
    } catch (bulkErr) {
      if (isAttemptMissingError(bulkErr)) {
        throw bulkErr;
      }
      console.warn('Bulk answer error:', bulkErr);
    }

    await api(`/api/exam/attempts/${encodeURIComponent(au)}/submit`, {
      method:'POST',
      timeoutMs: 25000
    });

    if (timerHandle) clearInterval(timerHandle);
    Swal.close();
    clearAllExamClientState();

    await Swal.fire({
      icon:'success',
      title: auto ? 'Exam Auto-Submitted' : 'Exam Submitted Successfully',
      text: auto ? 'Your exam time ended and your responses have been automatically recorded.' : 'Your responses have been recorded.',
      confirmButtonText:'OK',
      allowOutsideClick: false,
      allowEscapeKey: false
    });

    window.location.replace(`/quizzes`);

  }catch(e){
    console.error('Submit error:', e);
    Swal.close();

    if (isAttemptMissingError(e)) {
      clearAllExamClientState();

      await Swal.fire({
        icon:'info',
        title: auto ? 'Exam Time Ended' : 'Exam Already Closed',
        text: auto
          ? 'Your exam time has ended. Your responses have been recorded automatically by the system.'
          : 'This attempt is no longer active. Your responses may have already been recorded.',
        confirmButtonText:'Go to Dashboard',
        allowOutsideClick: false,
        allowEscapeKey: false
      });

      window.location.replace('/quizzes');
      return;
    }

    if (auto && (e?.status === 408 || e?.name === 'AbortError' || e?.message?.includes('timeout'))) {
      clearAllExamClientState();
      await Swal.fire({
        icon:'warning',
        title:'Connection Issue',
        text: 'There was a connection issue while submitting. Your responses were saved and will be processed. Please check your dashboard.',
        confirmButtonText:'Go to Dashboard',
        allowOutsideClick: false,
        allowEscapeKey: false
      });
      window.location.replace('/quizzes');
      return;
    }

    if (!auto) {
      Swal.fire({
        icon:'error',
        title:'Submit Failed',
        text: e.message || 'Failed to submit exam. Please try again.',
        confirmButtonText:'OK'
      });
      return;
    }

    clearAllExamClientState();
    await Swal.fire({
      icon:'info',
      title:'Exam Ended',
      text: 'Your exam time has ended. Please check your dashboard to verify your submission.',
      confirmButtonText:'Go to Dashboard',
      allowOutsideClick: false,
      allowEscapeKey: false
    });
    window.location.replace('/quizzes');

  }finally{
    isSubmitting = false;
    $('#submit-btn').disabled = false;
    $('#submit-btn .btn-label').classList.remove('d-none');
    $('#submit-btn .btn-spinner').classList.add('d-none');
    if (!auto) disableExamUI(false);
  }
}

/* ================== BOOT EXAM ================== */
async function bootExam(){
  try{
    showSkeleton(true);

    const hasCache = cacheLoad();

    if (ATTEMPT_UUID) {
      try{
        const data = await api(`/api/exam/attempts/${encodeURIComponent(ATTEMPT_UUID)}/questions`, { method:'GET', timeoutMs: 20000 });
        const pack = data.data || data;

        if (pack?.attempt?.server_end_at) serverEndAt = pack.attempt.server_end_at;

        if (!hasCache || !questions.length) {
          questions  = pack.questions || [];
          selections = pack.selections || {};
        }

        const left = computeTimeLeft();
        if (left !== null && left <= 0) {
          clearAllExamClientState();
        } else {
          cacheSave();
        }
      }catch(e){
        if (isAttemptMissingError(e) || e?.status === 401 || e?.status === 403) {
          clearAllExamClientState();
        } else {
          console.warn('Attempt validate warning:', e);
        }
      }
    }

    if (!ATTEMPT_UUID) {
      const started = await api(`/api/exam/quizzes/${encodeURIComponent(QUIZ_KEY)}/start`, { method:'POST', timeoutMs: 20000 });

      const attempt = started.attempt || started.data?.attempt || started.data || {};
      ATTEMPT_UUID  = attempt.attempt_uuid || null;

      if (!ATTEMPT_UUID) throw new Error('Attempt id missing from start API.');

      localStorage.setItem(STORAGE_ATTEMPT_KEY, ATTEMPT_UUID);

      serverEndAt = attempt.server_end_at || attempt.serverEndAt || null;

      if (attempt.quiz_name) document.title = attempt.quiz_name + ' • Exam';

      cacheSave();
    }

    if (!hasCache || !questions.length) {
      const data = await api(`/api/exam/attempts/${encodeURIComponent(ATTEMPT_UUID)}/questions`, { method:'GET', timeoutMs: 20000 });
      const pack = data.data || data;

      questions  = pack.questions || [];
      selections = pack.selections || {};

      if (pack?.attempt?.server_end_at) serverEndAt = pack.attempt.server_end_at;

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

    if (!questions.length) {
      showSkeleton(false);
      throw new Error('No questions found for this attempt.');
    }

    showSkeleton(false);

    buildNavigator();
    currentIndex = Math.min(Math.max(0, currentIndex), Math.max(0, questions.length - 1));
    renderQuestion();

    if (parseServerDate(serverEndAt)) startTimerFromServerEnd();
    else {
      const timeEl = document.getElementById('time-left');
      if (timeEl) timeEl.textContent = '--:--';
      console.warn('serverEndAt missing/invalid → timer not started');
    }

    const q = questions[currentIndex];
    if (q?.question_id) enterQuestion(q.question_id);

    if (!bootExam.__bound) {
      bootExam.__bound = true;

      $('#prev-btn').addEventListener('click', onPrev);
      $('#next-btn').addEventListener('click', onNext);
      $('#review-btn').addEventListener('click', onToggleReview);
      $('#submit-btn').addEventListener('click', () => doSubmit(false));

      window.addEventListener('beforeunload', () => {
        const cur = questions[currentIndex];
        if (cur?.question_id) leaveQuestion(cur.question_id);
        cacheSave();
      });
    }

  }catch(e){
    console.error(e);
    showSkeleton(false);
    Swal.fire({icon:'error',title:'Cannot start exam',text:e.message || 'Please try again.'});
  }
}

/* ================== STARTUP ================== */
let fullscreenTriggered = false;

async function enterFullscreenAndStartExam() {
  if (fullscreenTriggered) return;
  fullscreenTriggered = true;

  Swal.close();
  requestFullscreen();

  EXAM_STARTED = true;
  await bootExam();
}

document.addEventListener('DOMContentLoaded', () => {
  Swal.fire({
    icon: 'info',
    title: 'Before You Begin',
    html: `
      <p class="text-muted mb-3" style="font-size:.92rem;">Please read the exam rules carefully before starting.</p>
      <ul class="exam-rules-list">
        <li>
          <i class="fa-solid fa-expand"></i>
          <span>You <strong>must stay in fullscreen</strong> mode for the entire exam. Exiting fullscreen will be logged as a violation.</span>
        </li>
        <li>
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
          <span>Do <strong>not switch tabs</strong> or minimize the browser window. Each tab switch is recorded as a violation.</span>
        </li>
        <li>
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>You are allowed a maximum of <strong>3 violations</strong>. Exceeding this limit will trigger <strong>automatic submission</strong> of your exam.</span>
        </li>
        <li class="rule-ok">
          <i class="fa-solid fa-circle-check"></i>
          <span>Click <strong>"Start Exam"</strong> below to enter fullscreen and begin. Good luck!</span>
        </li>
      </ul>`,
    confirmButtonText: '<i class="fa-solid fa-play me-2"></i>Start Exam',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: true,
    customClass: {
      confirmButton: 'btn btn-primary px-4'
    },
    buttonsStyling: false
  }).then((result) => {
    if (result.isConfirmed) {
      enterFullscreenAndStartExam();
    }
  });
});
</script>
</body>
</html>
