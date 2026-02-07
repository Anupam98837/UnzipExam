{{-- resources/views/pages/users/textexam.blade.php --}}
@section('title','Test Exam')

@php
  $quizKey = $quizKey ?? request()->route('quiz') ?? request()->query('quiz');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="quiz-key" content="{{ $quizKey }}">
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Test Exam</title>

  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}"/>
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">

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
    .mode-pill{padding:.45rem .9rem;border-radius:999px;font-weight:700;font-size:.9rem;background:rgba(79,70,229,.10);color:var(--accent-color,#4f46e5);display:flex;align-items:center;gap:.5rem;border:1px solid rgba(79,70,229,.25)}
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

    /* Minimal correctness styling (still clean) */
    .opt.correct{border-color:rgba(22,163,74,.55);background:rgba(22,163,74,.06)}
    .opt.wrong{border-color:rgba(239,68,68,.55);background:rgba(239,68,68,.06)}
    .opt.selected{box-shadow:0 0 0 2px rgba(79,70,229,.15)}

    .fib-underline{display:inline-block;min-width:90px;border-bottom:2px solid #cbd5e1;margin:0 .22rem .2rem .22rem}
    .fib-fields .form-control{height:40px;border-radius:10px}

    mjx-container[display="block"]{display:block!important;margin:.5rem 0}
    mjx-container{overflow-x:auto}

    @media (min-width:992px){.col-fixed-260{flex:0 0 260px;max-width:260px}}

    .answer-box{
      margin-top:14px;
      border:1px dashed rgba(79,70,229,.35);
      background:rgba(79,70,229,.04);
      border-radius:14px;
      padding:12px 12px;
    }
    .answer-title{
      font-weight:800;
      font-size:.92rem;
      display:flex;
      align-items:center;
      gap:8px;
      margin-bottom:8px;
      color:var(--accent-color,#4f46e5);
    }
    .answer-chip{
      display:inline-flex;
      align-items:center;
      gap:7px;
      padding:.35rem .6rem;
      border-radius:999px;
      border:1px solid rgba(22,163,74,.35);
      background:rgba(22,163,74,.08);
      font-weight:700;
      font-size:.82rem;
      margin:0 .35rem .35rem 0;
    }
    .explain-box{
      margin-top:10px;
      padding:10px 12px;
      border-radius:12px;
      background:rgba(17,24,39,.03);
      border:1px solid rgba(17,24,39,.08);
      color:var(--muted,#4b5563);
      font-size:.92rem;
      line-height:1.45;
    }
  </style>
</head>
<body>

<header class="exam-header sticky-top">
  <div class="container-xxl d-flex align-items-center justify-content-between py-3">
    <div class="d-flex align-items-center gap-2">
      <div class="exam-logo"><span>Test</span> Portal</div>
      <span class="badge rounded-pill text-bg-light border">
        <i class="fa-solid fa-flask me-1"></i> Practice
      </span>
    </div>
    <div class="mode-pill">
      <i class="fa-solid fa-circle-check"></i>
      <span>No time limit</span>
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

        <button id="finish-btn" class="btn btn-primary w-100 mt-3">
          <i class="fa-solid fa-flag-checkered me-2"></i>Finish
        </button>

        <button id="reset-btn" class="btn btn-light w-100 mt-2">
          <i class="fa-solid fa-rotate me-2"></i>Reset Answers
        </button>

        <button id="back-btn" class="btn btn-light w-100 mt-2">
          <i class="fa-solid fa-arrow-left me-2"></i>Back
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
/* ================== Globals ================== */
const $  = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

const QUIZ_KEY =
  (document.querySelector('meta[name="quiz-key"]')?.content || '').trim() ||
  new URLSearchParams(location.search).get('quiz') || '';

/**
 * ✅ IMPORTANT:
 * From manageQuizz (admin), your token is usually in localStorage('token').
 * From student side, it may be in sessionStorage('student_token').
 */
const token =
  sessionStorage.student_token ||
  sessionStorage.token ||
  localStorage.getItem('token') ||
  '';

if (!QUIZ_KEY) {
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({icon:'error',title:'Missing quiz key',text:'No quiz id/uuid provided in URL.'})
      .then(() => history.back());
  });
}

if (!token) {
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({icon:'error',title:'Not authenticated',text:'Please login again.'})
      .then(() => window.location.href = '/');
  });
}

let questions    = [];
let selections   = {};
let reviews      = {};
let visited      = {};
let currentIndex = 0;

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
    const msg = data.message || `HTTP ${res.status}`;
    // show exact failing path to avoid blind 404 debugging
    throw new Error(`${msg} @ ${path}`);
  }
  return data;
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

function escapeHtml(str){
  return (str ?? '').toString()
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
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

function countGaps(q){
  const title = String(q.question_title || '');
  const desc  = String(q.question_description || '');
  const re    = /\{dash\}/gi;
  const n1    = (title.match(re) || []).length;
  const n2    = (desc.match(re)  || []).length;
  if (n1 + n2 > 0) return n1 + n2;

  const ca = Array.isArray(q.correct_answers) ? q.correct_answers.length : 0;
  if (ca > 0) return ca;

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

/* ================== Correct answer helpers (robust keys) ================== */
function isAnswerCorrectFlag(a){
  return !!(
    a?.is_correct === true ||
    a?.correct === true ||
    a?.is_right === true ||
    a?.is_correct_answer === true ||
    a?.is_correct_answer === 1 ||
    a?.is_correct === 1 ||
    a?.correct === 1
  );
}

function correctAnswerIds(q){
  if (Array.isArray(q.correct_answer_ids)) return q.correct_answer_ids.map(Number).filter(Number.isFinite);
  if (Array.isArray(q.correct_answers_ids)) return q.correct_answers_ids.map(Number).filter(Number.isFinite);

  if (Array.isArray(q.answers)) {
    const ids = q.answers.filter(isAnswerCorrectFlag).map(a => Number(a.answer_id));
    return ids.filter(Number.isFinite);
  }

  if (q.correct_answer_id != null) {
    const n = Number(q.correct_answer_id);
    return Number.isFinite(n) ? [n] : [];
  }

  return [];
}

function correctAnswerTexts(q){
  if (Array.isArray(q.correct_answers)) {
    return q.correct_answers.map(x => String(x ?? '').trim()).filter(Boolean);
  }

  if (Array.isArray(q.answers)) {
    const texts = q.answers
      .filter(isAnswerCorrectFlag)
      .map(a => String(a.answer_title ?? a.title ?? a.answer_text ?? '').trim())
      .filter(Boolean);
    if (texts.length) return texts;
  }

  const one = String(q.correct_answer ?? q.correct_text ?? '').trim();
  return one ? [one] : [];
}

function arraysEqualAsSets(a, b){
  const A = (a||[]).map(Number).filter(Number.isFinite).sort((x,y)=>x-y);
  const B = (b||[]).map(Number).filter(Number.isFinite).sort((x,y)=>x-y);
  if (A.length !== B.length) return false;
  for (let i=0;i<A.length;i++) if (A[i] !== B[i]) return false;
  return true;
}

/* ================== Render ================== */
function renderQuestion(){
  const q = questions[currentIndex];
  if (!q) return;

  visited[q.question_id] = true;

  const wrap = $('#question-wrap');
  const rawType = String(q.question_type || '').toLowerCase();
  const multi   = !!q.has_multiple_correct_answer;
  const label   = multi && rawType !== 'fill_in_the_blank' ? 'Multiple choice' : typeLabel(rawType);

  const toDisplay = s =>
    normalizeTeX(String(s || '')).replace(/\{dash\}/gi, '<span class="fib-underline">&nbsp;</span>');

  const titleHTML = toDisplay(q.question_title);
  const descHTML  = q.question_description ? toDisplay(q.question_description) : '';

  const qid = Number(q.question_id);
  const sel = selections[qid];

  const correctIds   = correctAnswerIds(q);
  const correctTexts = correctAnswerTexts(q);

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
      <span class="badge rounded-pill text-bg-info ${reviews[qid] ? '' : 'invisible'}">Review</span>
    </div>

    <div class="mt-3" id="options">`;

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
        <div class="form-text">Enter each blank separately.</div>
      </div>`;
  } else {
    (q.answers || []).forEach(a => {
      const aid = Number(a.answer_id);
      const checked = multi
        ? Array.isArray(sel) && sel.map(Number).includes(aid)
        : (!Array.isArray(sel) && Number(sel) === aid);

      const isCorrect  = correctIds.includes(aid);
      const isSelected = !!checked;

      let extraClass = '';
      if (isCorrect) extraClass = 'correct';
      if (isSelected && !isCorrect) extraClass = 'wrong';
      if (isSelected) extraClass += (extraClass ? ' ' : '') + 'selected';

      html += `
        <label class="opt form-check d-flex align-items-center gap-2 ${extraClass}">
          <input class="form-check-input" type="${multi ? 'checkbox' : 'radio'}"
                 name="q_${qid}${multi ? '[]' : ''}" value="${aid}" ${checked ? 'checked' : ''}/>
          <span class="form-check-label">${a.answer_title ?? ''}</span>
        </label>`;
    });
  }

  html += `</div>`;

  // Correct Answer box
  const chips = (rawType === 'fill_in_the_blank')
    ? (correctTexts.length ? correctTexts : [])
    : (correctTexts.length ? correctTexts : []);

  const explanation = String(q.explanation ?? q.solution ?? q.answer_explanation ?? '').trim();

  html += `
    <div class="answer-box">
      <div class="answer-title"><i class="fa-solid fa-check"></i> Correct Answer</div>
      <div id="correct-answers">`;

  if (rawType === 'fill_in_the_blank') {
    if (chips.length) {
      chips.forEach(t => html += `<span class="answer-chip"><i class="fa-solid fa-circle-check"></i> ${escapeHtml(t)}</span>`);
    } else {
      html += `<div class="small text-muted">Correct answers not provided by API.</div>`;
    }
  } else {
    if (correctIds.length) {
      const idToTitle = new Map((q.answers||[]).map(a => [Number(a.answer_id), String(a.answer_title ?? '').trim()]));
      correctIds.forEach(id => {
        const title = idToTitle.get(Number(id)) || ('Answer ID: ' + id);
        html += `<span class="answer-chip"><i class="fa-solid fa-circle-check"></i> ${escapeHtml(title)}</span>`;
      });
    } else if (chips.length) {
      chips.forEach(t => html += `<span class="answer-chip"><i class="fa-solid fa-circle-check"></i> ${escapeHtml(t)}</span>`);
    } else {
      html += `<div class="small text-muted">Correct answer not provided by API.</div>`;
    }
  }

  html += `</div>`;

  if (rawType !== 'fill_in_the_blank') {
    const selIds = (multi ? (Array.isArray(sel) ? sel.map(Number) : []) : (sel != null ? [Number(sel)] : []))
      .filter(Number.isFinite);

    if (selIds.length && correctIds.length) {
      const ok = arraysEqualAsSets(selIds, correctIds);
      html += `
        <div class="mt-2 small ${ok ? 'text-success' : 'text-danger'}">
          <i class="fa-solid ${ok ? 'fa-circle-check' : 'fa-circle-xmark'} me-1"></i>
          ${ok ? 'Your selection is correct.' : 'Your selection is incorrect.'}
        </div>`;
    }
  }

  if (explanation) {
    html += `<div class="explain-box"><b>Explanation:</b><br>${escapeHtml(explanation).replace(/\n/g,'<br>')}</div>`;
  }

  html += `</div>`;

  wrap.innerHTML = html;

  wrap.querySelector('.question-title')?.classList.add('tex2jax_process');
  wrap.querySelectorAll('.form-check-label').forEach(n => n.classList.add('tex2jax_process'));
  wrap.querySelectorAll('.fib-fields').forEach(n => n.classList.add('tex2jax_process'));
  wrap.querySelectorAll('.answer-box').forEach(n => n.classList.add('tex2jax_process'));
  typeset(wrap);

  // listeners
  if (rawType === 'fill_in_the_blank') {
    $$('#options input[data-fib-index]').forEach(inp => {
      const updateLocal = () => {
        const arr = $$('#options input[data-fib-index]').map(i => i.value || '');
        selections[qid] = arr;
        updateProgress();
        refreshNav();
        renderQuestion();
      };
      inp.addEventListener('input', updateLocal);
      inp.addEventListener('blur', updateLocal);
    });
  } else {
    $$('#options input').forEach(inp => {
      inp.addEventListener('change', () => {
        selections[qid] = collectSelectionFor(q);
        updateProgress();
        refreshNav();
        renderQuestion();
      });
    });
  }

  $('#prev-btn').disabled = currentIndex === 0;
  $('#next-btn .lbl').innerHTML =
    (currentIndex < questions.length - 1)
      ? `Next<i class="fa-solid fa-arrow-right ms-2"></i>`
      : `Finish<i class="fa-solid fa-flag-checkered ms-2"></i>`;

  $('#review-btn').innerHTML = reviews[qid]
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
  if (targetIdx < 0 || targetIdx >= questions.length) return;
  if (targetIdx === currentIndex) return;
  currentIndex = targetIdx;
  renderQuestion();
}

/* ================== Actions ================== */
function onPrev(){
  if (currentIndex <= 0) return;
  navigateTo(currentIndex - 1);
}
function onNext(){
  if (currentIndex < questions.length - 1) navigateTo(currentIndex + 1);
  else finishTest();
}
function onToggleReview(){
  const q = questions[currentIndex];
  if (!q) return;
  const qid = Number(q.question_id);
  reviews[qid] = !reviews[qid];
  renderQuestion();
}
async function finishTest(){
  const done = questions.filter(q => answeredVal(q.question_id)).length;
  const total = questions.length;

  const res = await Swal.fire({
    title: 'Finish practice?',
    html: `<div class="text-muted">You answered <b>${done}</b> out of <b>${total}</b> questions.</div>`,
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'Exit',
    cancelButtonText: 'Stay',
    reverseButtons: true
  });
  if (res.isConfirmed) history.back();
}

/* ================== Fetch questions (404-safe) ==================
   It will try admin quiz-questions endpoints first (best for correct answers),
   if they 404 then it will fallback to your existing exam attempt APIs. */
function unwrapQuestions(payload){
  const p = payload?.data?.data || payload?.data || payload || {};
  const qs = p.questions || p.data?.questions || p.quiz?.questions || p.items || [];
  return Array.isArray(qs) ? qs : [];
}

async function tryAdminQuestionApis(){
  const key = encodeURIComponent(QUIZ_KEY);
  const tries = [
    `/api/quizz/${key}/questions?include_correct=1`,
    `/api/quizz/${key}/questions`,
    `/api/quizz/questions?quiz=${key}&include_correct=1`,
    `/api/quizz/questions?quiz=${key}`,
  ];

  for (const url of tries) {
    try {
      const res = await api(url, { method:'GET' });
      const qs  = unwrapQuestions(res);
      if (qs.length) return qs;
    } catch (e) {
      // continue (most common: 404)
    }
  }
  return null;
}

async function tryExamAttemptApis(){
  const key = encodeURIComponent(QUIZ_KEY);

  // ✅ same as your real exam page
  const started = await api(`/api/exam/quizzes/${key}/start`, { method:'POST' });
  const attempt = started.attempt || started.data?.attempt || started.data || {};
  const attemptUuid = attempt.attempt_uuid || null;
  if (!attemptUuid) throw new Error('Attempt UUID not received @ /api/exam/quizzes/{quiz}/start');

  const data = await api(`/api/exam/attempts/${encodeURIComponent(attemptUuid)}/questions`, { method:'GET' });
  const pack = data.data || data;
  const qs   = pack.questions || [];

  return Array.isArray(qs) ? qs : [];
}

/* ================== Boot ================== */
async function bootTest(){
  try{
    showSkeleton(true);

    let qs = await tryAdminQuestionApis();

    // fallback to exam APIs (if admin APIs not present)
    if (!qs || !qs.length) {
      qs = await tryExamAttemptApis();
    }

    if (!qs || !qs.length) {
      throw new Error('No questions returned from any API.');
    }

    questions    = qs;
    selections   = {};
    reviews      = {};
    visited      = {};
    currentIndex = 0;

    showSkeleton(false);
    buildNavigator();
    renderQuestion();

    $('#prev-btn').addEventListener('click', onPrev);
    $('#next-btn').addEventListener('click', onNext);
    $('#review-btn').addEventListener('click', onToggleReview);

    $('#finish-btn').addEventListener('click', finishTest);

    $('#reset-btn').addEventListener('click', async () => {
      const r = await Swal.fire({
        title:'Reset all answers?',
        text:'This will clear your selections and review marks.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Reset',
        cancelButtonText:'Cancel',
        reverseButtons:true
      });
      if (!r.isConfirmed) return;
      selections = {};
      reviews = {};
      visited = {};
      currentIndex = 0;
      buildNavigator();
      renderQuestion();
    });

    $('#back-btn').addEventListener('click', () => history.back());

  }catch(e){
    console.error(e);
    showSkeleton(false);
    Swal.fire({
      icon:'error',
      title:'Cannot load test',
      html: `<div class="text-muted">${escapeHtml(e.message || 'Please try again.')}</div>`
    });
  }
}

document.addEventListener('DOMContentLoaded', bootTest);
</script>

</body>
</html>
