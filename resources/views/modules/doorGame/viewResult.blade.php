{{-- resources/views/door-game/doorGameResultStandalone.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <title>Door Game Result</title>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/favicons/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}">

  <style id="erStyles">
    .er-wrap{max-width:1100px;margin:16px auto 40px;}
    .er-shell{
      border-radius:16px;border:1px solid var(--line-strong);
      background:var(--surface);box-shadow:var(--shadow-2);
      padding:16px 18px 18px;position:relative;
    }
    .er-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:12px;}
    .er-title{font-family:var(--font-head);font-weight:700;color:var(--ink);font-size:1.25rem;margin:0;}
    .er-sub{font-size:var(--fs-13);color:var(--muted-color);margin-top:3px;}
    .er-actions{margin-left:auto;display:flex;flex-wrap:wrap;gap:8px;}
    .er-actions .btn{border-radius:999px;padding-inline:12px;}
    .er-actions .btn i{margin-right:6px;}
    .er-row{margin-top:10px;}

    .er-card{
      border-radius:14px;border:1px solid var(--line-strong);
      background:var(--surface-2);padding:12px 12px 10px;
      box-shadow:var(--shadow-1);
    }
    .er-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;}
    .er-card-title{font-family:var(--font-head);font-weight:600;color:var(--ink);font-size:.95rem;margin:0;}

    .er-chip{
      display:inline-flex;align-items:center;gap:6px;
      padding:3px 8px;border-radius:999px;font-size:11px;
      border:1px solid var(--line-strong);background:var(--surface);
      color:var(--muted-color);
    }
    .er-chip i{font-size:10px;}
    .er-chip-primary{
      background:var(--t-primary);
      border-color:rgba(20,184,166,.25);
      color:#0f766e;
    }

    .er-score-main{display:flex;align-items:center;gap:14px;margin-bottom:10px;}
    .er-score-circle{
      width:72px;height:72px;border-radius:50%;
      border:5px solid rgba(20,184,166,.16);
      display:flex;align-items:center;justify-content:center;flex-direction:column;
      font-family:var(--font-head);color:var(--ink);position:relative;
    }
    .er-score-circle::after{
      content:"";position:absolute;inset:6px;border-radius:inherit;
      border:3px solid var(--accent-color);opacity:.5;
    }
    .er-score-value{font-size:1.25rem;font-weight:700;}
    .er-score-label{font-size:11px;color:var(--muted-color);}
    .er-score-text{font-size:var(--fs-13);color:var(--muted-color);}
    .er-score-text strong{font-weight:600;}

    .er-metrics{
      display:grid;grid-template-columns:repeat(3,minmax(0,1fr));
      gap:8px;margin-top:4px;
    }
    .er-metric{
      border-radius:10px;background:var(--surface);
      border:1px dashed var(--line-strong);
      padding:6px 8px;font-size:var(--fs-12);
    }
    .er-metric-label{color:var(--muted-color);margin-bottom:3px;}
    .er-metric-value{font-weight:600;color:var(--ink);}

    .er-bar-wrap{margin-top:4px;}
    .er-bar-bg{width:100%;height:8px;border-radius:999px;background:#e5eff0;overflow:hidden;}
    .er-bar-fill{height:100%;border-radius:inherit;background:var(--accent-color);width:0%;transition:width .4s ease;}
    .er-bar-label{font-size:var(--fs-12);color:var(--muted-color);margin-top:2px;}

    .er-pill-row{display:flex;flex-wrap:wrap;gap:6px;font-size:var(--fs-12);}
    .er-pill{
      padding:4px 8px;border-radius:999px;border:1px solid var(--line-strong);
      display:inline-flex;align-items:center;gap:5px;background:var(--surface);
    }
    .er-pill i{font-size:11px;}
    .er-pill-green{background:var(--t-success);border-color:rgba(22,163,74,.25);color:#15803d;}
    .er-pill-red{background:var(--t-danger);border-color:rgba(220,38,38,.25);color:#b91c1c;}
    .er-pill-gray{background:var(--surface-3);}

    .er-table-card{
      margin-top:14px;border-radius:14px;border:1px solid var(--line-strong);
      background:var(--surface-2);box-shadow:var(--shadow-1);
      padding:10px 12px 12px;
    }
    .er-table-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;}
    .er-table-title{font-family:var(--font-head);font-weight:600;color:var(--ink);font-size:1rem;margin:0;}
    .er-table-sub{font-size:var(--fs-13);color:var(--muted-color);}

    .er-empty{
      margin-top:8px;border:1px dashed var(--line-strong);
      border-radius:10px;padding:16px;text-align:center;
      font-size:var(--fs-13);color:var(--muted-color);
      background:var(--surface-2);
    }

    .er-loader-wrap{
      position:absolute;inset:0;display:none;align-items:center;justify-content:center;
      background:rgba(0,0,0,.04);z-index:5;
    }
    .er-loader-wrap.show{display:flex;}
    .er-loader{
      width:22px;height:22px;border-radius:50%;
      border:3px solid #0001;border-top-color:var(--accent-color);
      animation:er-rot 1s linear infinite;
    }
    @keyframes er-rot{to{transform:rotate(360deg)}}
    .er-error{margin-top:8px;font-size:12px;color:var(--danger-color);display:none;}
    .er-error.show{display:block;}

    /* === Move list cards === */
    .er-q-list{margin-top:8px;display:flex;flex-direction:column;gap:8px;}
    .er-qcard{
      border-radius:12px;border:1px solid var(--line-strong);
      background:var(--surface);padding:8px 9px 8px;box-shadow:var(--shadow-1);
    }
    .er-qcard-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;}
    .er-q-left{display:flex;align-items:center;gap:8px;}
    .er-q-badge{
      min-width:44px;height:22px;border-radius:999px;border:1px solid var(--line-strong);
      padding: 5px;
      background:var(--surface-3);display:flex;align-items:center;justify-content:center;
      font-size:11px;color:var(--muted-color);
    }
    .er-q-meta{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;font-size:11px;color:var(--muted-color);}
    .er-q-meta span strong{color:var(--ink);}

    .er-q-question-main{font-size:var(--fs-13);color:var(--text-color);}
    .er-qcard-answers{
      margin-top:6px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;
    }
    @media (max-width: 768px){ .er-qcard-answers{grid-template-columns:1fr;} }
    .er-q-answer-block{
      border-radius:10px;border:1px solid var(--line-strong);
      background:var(--surface-2);padding:6px 8px;font-size:11px;
    }
    .er-q-answer-block.correct{
      background:var(--t-success);border-color:rgba(22,163,74,.35);color:#14532d;
    }
    .er-q-answer-block.your{border-style:dashed;}
    .er-q-answer-label{
      font-weight:600;text-transform:uppercase;letter-spacing:.03em;
      margin-bottom:2px;color:var(--muted-color);
    }
    .er-q-answer-text{font-size:var(--fs-12);color:var(--text-color);word-break:break-word;}

    /* ===========================================
        ✅ Graphical Analysis (GRID + Replay + Charts)
    ============================================ */
    .er-viz-row{margin-top:12px;}
    .er-replay-controls{
      display:flex;align-items:center;gap:8px;flex-wrap:wrap;
      width:100%;
    }
    .er-replay-controls .btn{
      border-radius:999px;
      padding:4px 10px;
      font-size:12px;
    }
    .er-replay-controls .btn i{margin-right:6px;}
    .er-replay-controls .form-select{
      border-radius:999px;
      border:1px solid var(--line-strong);
      background:var(--surface);
      font-size:12px;
      padding-block:2px;
    }
    .er-replay-controls input[type="range"]{
      flex:1;
      min-width:160px;
      accent-color: var(--accent-color);
    }

    .er-grid-stage{
      margin-top:8px;
      display:flex;
      flex-direction:column;
      gap:8px;
    }
    .er-grid{
      --dim: 5;
      display:grid;
      grid-template-columns:repeat(var(--dim), minmax(0,1fr));
      gap:6px;
      padding:10px;
      border-radius:14px;
      border:1px dashed var(--line-strong);
      background:var(--surface);
    }
    .er-cell{
      position:relative;
      aspect-ratio: 1/1;
      border-radius:12px;
      border:1px solid var(--line-strong);
      background:var(--surface-2);
      overflow:hidden;

      /* barrier shadow vars */
      --sh-top: inset 0 0 0 transparent;
      --sh-bottom: inset 0 0 0 transparent;
      --sh-left: inset 0 0 0 transparent;
      --sh-right: inset 0 0 0 transparent;
      box-shadow: var(--shadow-1), var(--sh-top), var(--sh-bottom), var(--sh-left), var(--sh-right);
      transition: transform .12s ease, background .12s ease, outline .12s ease;
    }
    .er-cell:hover{transform:translateY(-1px);}
    .er-cell.bar-top{--sh-top: inset 0 4px 0 rgba(220,38,38,.70);}
    .er-cell.bar-bottom{--sh-bottom: inset 0 -4px 0 rgba(220,38,38,.70);}
    .er-cell.bar-left{--sh-left: inset 4px 0 0 rgba(220,38,38,.70);}
    .er-cell.bar-right{--sh-right: inset -4px 0 0 rgba(220,38,38,.70);}

    .er-cell-inner{
      position:absolute; inset:0;
      display:flex; align-items:center; justify-content:center;
      padding:6px;
    }
    .er-cell-num{
      position:absolute; top:6px; left:6px;
      font-size:10px; color:var(--muted-color);
      padding:2px 6px;
      border-radius:999px;
      border:1px solid var(--line-strong);
      background:rgba(255,255,255,.65);
      backdrop-filter: blur(6px);
    }
    html.theme-dark .er-cell-num{background:rgba(0,0,0,.25);}

    .er-cell-step{
      position:absolute; bottom:6px; right:6px;
      font-size:10px; color:var(--muted-color);
      padding:2px 6px;
      border-radius:999px;
      border:1px dashed var(--line-strong);
      background:var(--surface);
      display:none;
    }
    .er-cell.show-step .er-cell-step{display:inline-flex;}

    .er-cell-icons{
      display:flex; align-items:center; justify-content:center;
      gap:10px;
      font-size:20px;
      color:var(--ink);
    }
    .er-ico-user{display:none; color: var(--primary-color, #9E363A);}
    .er-ico-key{display:none; color: var(--warning-color, #d97706);}
    .er-ico-door{display:none; color: var(--accent-color);}

    .er-cell.is-current .er-ico-user{display:inline-block;}
    .er-cell.is-key .er-ico-key{display:inline-block;}
    .er-cell.is-door .er-ico-door{display:inline-block;}
    .er-cell.key-picked .er-ico-key{display:none;}
    .er-cell.door-open .er-ico-door{color: var(--success-color, #16a34a);}

    .er-cell.is-visited{
      background: color-mix(in oklab, var(--accent-color) 10%, var(--surface-2));
    }
    .er-cell.is-current{
      outline: 2px solid color-mix(in oklab, var(--primary-color, #9E363A) 55%, transparent);
      outline-offset:-3px;
    }
    .er-cell.is-optimal{
      outline: 2px dashed color-mix(in oklab, var(--primary-color, #9E363A) 65%, transparent);
      outline-offset:-5px;
    }

    .er-legend{
      display:flex; flex-wrap:wrap;
      gap:10px;
      font-size:12px;
      color:var(--muted-color);
      align-items:center;
    }
    .er-legend span{
      display:inline-flex;align-items:center;gap:6px;
      padding:4px 8px;border-radius:999px;
      border:1px solid var(--line-strong);
      background:var(--surface);
    }
    .er-legend i{font-size:13px;}

    /* Pace chart */
    .er-pace{
      height:115px;
      border-radius:14px;
      border:1px dashed var(--line-strong);
      background:var(--surface);
      padding:10px;
      display:flex;
      align-items:flex-end;
      gap:6px;
      overflow:visible;
    }
    .er-pace-bar{
      flex:1;
      min-width:8px;
      border-radius:10px;
      border:1px solid var(--line-strong);
      background: color-mix(in oklab, var(--accent-color) 30%, var(--surface));
      position:relative;
      transition: filter .15s ease;
    }
    .er-pace-bar:hover{filter:brightness(1.05);}
    .er-pace-bar::after{
      content: attr(data-ms);
      position:absolute;
      left:50%;
      transform:translateX(-50%);
      bottom:calc(100% + 6px);
      font-size:10px;
      color:var(--muted-color);
      background:var(--surface);
      border:1px solid var(--line-strong);
      padding:2px 6px;
      border-radius:999px;
      white-space:nowrap;
      opacity:0;
      pointer-events:none;
      transition: opacity .12s ease;
    }
    .er-pace-bar:hover::after{opacity:1;}

    .er-mini-metrics{
      margin-top:10px;
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:8px;
    }
    .er-mini{
      border-radius:12px;
      border:1px solid var(--line-strong);
      background:var(--surface);
      padding:8px;
      font-size:12px;
    }
    .er-mini .k{color:var(--muted-color);font-size:11px;margin-bottom:2px;}
    .er-mini .v{color:var(--ink);font-weight:700;}

    @media (max-width: 992px){
      .er-replay-controls input[type="range"]{min-width:140px;}
    }

    @media print{
      #sidebar,.w3-sidebar,.w3-appbar,#sidebarOverlay{display:none!important;}
      body{background:#fff!important;}
      main.w3-content{max-width:100%!important;padding:0!important;margin:0!important;}
      .panel{border:none!important;box-shadow:none!important;padding:0!important;}
      .er-wrap{margin:0!important;max-width:100%!important;}
      .er-actions{display:none!important;}
      .er-replay-controls{display:none!important;}
    }

    html.theme-dark .er-shell,
    html.theme-dark .er-card,
    html.theme-dark .er-table-card{background:#04151f;}
    html.theme-dark .er-empty{background:#020b13;}
    html.theme-dark .er-qcard{background:#020b13;}
    html.theme-dark .er-grid{background:#020b13;}
  </style>
</head>

<body>
  <div class="er-wrap">
    <div id="resultShell"
         class="er-shell"
         data-result-id="{{ $resultId }}">

      <div class="er-loader-wrap" id="erLoader">
        <div class="er-loader"></div>
      </div>

      <div class="er-head">
        <img id="logo" src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination" style="height:50px;width:auto;">

        <div>
          <h1 class="er-title" id="erGameTitle">Door Game Result</h1>
          <div class="er-sub" id="erAttemptMeta">Loading attempt details...</div>
        </div>

        <div class="er-actions">
          <button type="button" class="btn btn-primary btn-sm" id="erPdfExport">
            <i class="fa-regular fa-file-pdf"></i> Export PDF
          </button>
        </div>
      </div>

      <div class="row g-3 er-row">
        <div class="col-md-7">
          <div class="er-card">
            <div class="er-card-head">
              <h2 class="er-card-title">Attempt summary</h2>
              <span class="er-chip er-chip-primary" id="erScoreChip">
                <i class="fa-solid fa-award"></i> Overall
              </span>
            </div>

            <div class="er-score-main">
              <div class="er-score-circle">
                <div class="er-score-value" id="erPercent">0%</div>
                <div class="er-score-label">Success</div>
              </div>
              <div class="er-score-text" id="erScoreText">
                Your attempt summary will appear here once the data is loaded.
              </div>
            </div>

            <div class="er-metrics">
              <div class="er-metric">
                <div class="er-metric-label">Score</div>
                <div class="er-metric-value" id="erMarks">0</div>
              </div>
              <div class="er-metric">
                <div class="er-metric-label">Moves</div>
                <div class="er-metric-value" id="erAttempted">0</div>
              </div>
              <div class="er-metric">
                <div class="er-metric-label">Time spent</div>
                <div class="er-metric-value" id="erTimeSpent">-</div>
              </div>
            </div>

            <div class="er-bar-wrap">
              <div class="er-bar-bg">
                <div class="er-bar-fill" id="erScoreBar"></div>
              </div>
              <div class="er-bar-label" id="erBarLabel">
                Success: 0%
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-5">
          <div class="er-card">
            <div class="er-card-head">
              <h2 class="er-card-title">Status & key events</h2>
              <span class="er-chip" id="erAttemptChip">
                <i class="fa-regular fa-circle-check"></i>
                Attempt
              </span>
            </div>

            <div class="er-pill-row mb-2">
              <span class="er-pill er-pill-green">
                <i class="fa-solid fa-key"></i>
                <span id="erKeyPicked">Key: -</span>
              </span>
              <span class="er-pill er-pill-red">
                <i class="fa-solid fa-door-open"></i>
                <span id="erDoorOpened">Door: -</span>
              </span>
              <span class="er-pill er-pill-gray">
                <i class="fa-regular fa-clock"></i>
                <span id="erTimeoutInfo">Timeout: -</span>
              </span>
            </div>

            <ul class="mb-0 small text-muted" id="erMetaList">
              <li>Result ID: <span id="erResultId">-</span></li>
              <li>Attempt no: <span id="erAttemptNo">-</span></li>
              <li>Submitted at: <span id="erSubmittedAt">-</span></li>
            </ul>
          </div>
        </div>
      </div>

      {{-- ✅ Graphical analysis row --}}
      <div class="row g-3 er-viz-row">
        <div class="col-lg-7">
          <div class="er-card">
            <div class="er-card-head">
              <h2 class="er-card-title">Grid replay & path analysis</h2>
              <span class="er-chip" id="erStepMeta">
                <i class="fa-solid fa-route"></i> Step: -
              </span>
            </div>

            <div class="er-replay-controls">
              <button class="btn btn-outline-secondary btn-sm" id="erReplayPrev" type="button" title="Previous step">
                <i class="fa-solid fa-backward-step"></i> Prev
              </button>

              <button class="btn btn-outline-secondary btn-sm" id="erReplayPlay" type="button" title="Play / Pause">
                <i class="fa-solid fa-play"></i> Play
              </button>

              <button class="btn btn-outline-secondary btn-sm" id="erReplayNext" type="button" title="Next step">
                <i class="fa-solid fa-forward-step"></i> Next
              </button>

              <input type="range" min="0" max="0" value="0" id="erReplaySeek" />

              <select id="erReplaySpeed" class="form-select form-select-sm" style="width:auto">
                <option value="1">1×</option>
                <option value="2">2×</option>
                <option value="4">4×</option>
              </select>

              <div class="form-check form-switch ms-auto">
                <input class="form-check-input" type="checkbox" id="erShowOptimal" checked>
                <label class="form-check-label small text-muted" for="erShowOptimal">Optimal path</label>
              </div>

              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="erShowStepNos" checked>
                <label class="form-check-label small text-muted" for="erShowStepNos">Step nos</label>
              </div>
            </div>

            <div class="er-grid-stage">
              <div id="erGrid" class="er-grid" style="--dim:5">
                {{-- grid filled by JS --}}
              </div>

              <div class="er-legend">
                <span><i class="fa-solid fa-person-walking" style="color:var(--primary-color,#9E363A)"></i> User</span>
                <span><i class="fa-solid fa-key" style="color:var(--warning-color,#d97706)"></i> Key</span>
                <span><i class="fa-solid fa-door-open" style="color:var(--accent-color)"></i> Door</span>
                <span><i class="fa-solid fa-ban" style="color:rgba(220,38,38,.8)"></i> Red wall = barrier</span>
              </div>

              <div id="erGridHint" class="small text-muted">
                Replay will load after attempt snapshot is fetched.
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="er-card">
            <div class="er-card-head">
              <h2 class="er-card-title">Move pace (ms per move)</h2>
              <span class="er-chip" id="erPaceMeta">
                <i class="fa-regular fa-clock"></i> -
              </span>
            </div>

            <div id="erPaceChart" class="er-pace">
              {{-- bars filled by JS --}}
            </div>

            <div class="er-mini-metrics" id="erPathStats">
              {{-- stats filled by JS --}}
            </div>

            <div id="erMoveWarnings" class="mt-2 small text-muted" style="display:none"></div>
          </div>
        </div>
      </div>

      <div class="er-table-card mt-3">
        <div class="er-table-head">
          <div>
            <h2 class="er-table-title">Move-wise analysis</h2>
            <div class="er-table-sub">
              Path + moves + key/door events from attempt snapshot.
            </div>
          </div>
        </div>

        <div id="erNoQuestions" class="er-empty d-none">
          No move-level data is available for this attempt.
        </div>

        <div id="erQuestionList" class="er-q-list">
          {{-- Filled by JS --}}
        </div>
      </div>

      <div id="erError" class="er-error"></div>

    </div>
  </div>

  <script>
  (function () {
    "use strict";

    function initResultPage() {
      var resultShell = document.getElementById("resultShell");
      if (!resultShell) return;

      var RESULT_ID = resultShell.dataset.resultId;
      if (!RESULT_ID) {
        var errEl = document.getElementById("erError");
        if (errEl) {
          errEl.textContent = "Result reference is missing.";
          errEl.classList.add("show");
        }
        console.error("Missing result id on resultShell");
        return;
      }

      var loaderEl    = document.getElementById("erLoader");
      var errorEl     = document.getElementById("erError");

      var gameTitleEl   = document.getElementById("erGameTitle");
      var attemptMetaEl = document.getElementById("erAttemptMeta");
      var scoreChipEl   = document.getElementById("erScoreChip");

      var percentEl   = document.getElementById("erPercent");
      var scoreTextEl = document.getElementById("erScoreText");
      var marksEl     = document.getElementById("erMarks");
      var attemptedEl = document.getElementById("erAttempted");
      var timeSpentEl = document.getElementById("erTimeSpent");
      var scoreBarEl  = document.getElementById("erScoreBar");
      var barLabelEl  = document.getElementById("erBarLabel");

      var keyPickedEl   = document.getElementById("erKeyPicked");
      var doorOpenedEl  = document.getElementById("erDoorOpened");
      var timeoutInfoEl = document.getElementById("erTimeoutInfo");

      var resultIdEl    = document.getElementById("erResultId");
      var attemptNoEl   = document.getElementById("erAttemptNo");
      var submittedAtEl = document.getElementById("erSubmittedAt");

      var questionListEl = document.getElementById("erQuestionList");
      var noQuestionsEl  = document.getElementById("erNoQuestions");

      var pdfBtn  = document.getElementById("erPdfExport");

      /* ====== Graphical analysis refs ====== */
      var gridEl = document.getElementById("erGrid");
      var gridHintEl = document.getElementById("erGridHint");
      var stepMetaEl = document.getElementById("erStepMeta");

      var btnPrev = document.getElementById("erReplayPrev");
      var btnPlay = document.getElementById("erReplayPlay");
      var btnNext = document.getElementById("erReplayNext");
      var seekEl  = document.getElementById("erReplaySeek");
      var speedEl = document.getElementById("erReplaySpeed");
      var showOptimalEl = document.getElementById("erShowOptimal");
      var showStepNosEl = document.getElementById("erShowStepNos");

      var paceChartEl = document.getElementById("erPaceChart");
      var paceMetaEl  = document.getElementById("erPaceMeta");
      var pathStatsEl = document.getElementById("erPathStats");
      var moveWarnEl  = document.getElementById("erMoveWarnings");

      var replay = {
        ready: false,
        dim: 0,
        grid: [],
        cellMap: {},
        startId: null,
        keyId: null,
        doorId: null,
        frames: [],
        currentStep: 0,
        playing: false,
        timer: null,
        optimalPath: [],
        actualPath: [],
        events: {},
        timeLimitMs: 0,
        timeTakenMs: 0,
        moves: []
      };

      function getToken() {
        try { return sessionStorage.getItem("token") || localStorage.getItem("token") || null; }
        catch (e) { return null; }
      }

      function clearAuthStorage() {
        try { sessionStorage.removeItem("token"); } catch (e) {}
        try { sessionStorage.removeItem("role"); } catch (e) {}
        try { localStorage.removeItem("token"); } catch (e) {}
        try { localStorage.removeItem("role"); } catch (e) {}
      }

      function showLoader(show) {
        if (!loaderEl) return;
        if (show) loaderEl.classList.add("show");
        else loaderEl.classList.remove("show");
      }

      function showError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg || "Something went wrong while loading the result.";
        errorEl.classList.add("show");
      }

      function formatDateTime(str) {
        if (!str) return "-";
        // Support "YYYY-MM-DD HH:mm:ss"
        var normalized = String(str).replace(" ", "T");
        var d = new Date(normalized);
        if (isNaN(d.getTime())) return str;
        return d.toLocaleString();
      }

      function formatDurationMs(ms) {
        var v = Number(ms || 0);
        if (!v) return "-";
        var sec = Math.round(v / 1000);
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        if (m && s) return m + "m " + s + "s";
        if (m) return m + "m";
        return s + "s";
      }

      function safeJsonParse(raw, fallback) {
        try {
          if (raw == null) return fallback;
          if (typeof raw === "object") return raw;
          var s = String(raw).trim();
          if (!s) return fallback;
          return JSON.parse(s);
        } catch (e) {
          return fallback;
        }
      }

      function statusToChip(status) {
        var s = String(status || "").toLowerCase();
        if (s === "win") return { label: "WIN", icon: "trophy", pct: 100 };
        if (s === "fail") return { label: "FAIL", icon: "circle-xmark", pct: 0 };
        if (s === "timeout") return { label: "TIMEOUT", icon: "clock", pct: 0 };
        if (s === "auto_submitted") return { label: "AUTO", icon: "bolt", pct: 0 };
        return { label: (status || "ATTEMPT"), icon: "circle-check", pct: null };
      }

      /* =============================
          ✅ GRAPHICAL ANALYSIS HELPERS
      ============================== */
      function toRC(id, dim) {
        var idx = Number(id || 1) - 1;
        return [Math.floor(idx / dim), idx % dim];
      }
      function fromRC(r, c, dim) {
        return (r * dim) + c + 1;
      }
      function dirBetween(aId, bId, dim) {
        var a = toRC(aId, dim), b = toRC(bId, dim);
        var dr = b[0] - a[0];
        var dc = b[1] - a[1];
        if (dr === -1 && dc === 0) return "top";
        if (dr === 1 && dc === 0) return "bottom";
        if (dr === 0 && dc === -1) return "left";
        if (dr === 0 && dc === 1) return "right";
        return null;
      }
      function oppositeDir(dir) {
        if (dir === "top") return "bottom";
        if (dir === "bottom") return "top";
        if (dir === "left") return "right";
        if (dir === "right") return "left";
        return null;
      }
      function barrierOf(cell, dir) {
        if (!cell || !cell.barriers) return false;
        return !!cell.barriers[dir];
      }
      function isBlockedMove(aId, bId) {
        var dim = replay.dim;
        var dir = dirBetween(aId, bId, dim);
        if (!dir) return true; // non-adjacent considered invalid/blocked for analysis
        var a = replay.cellMap[aId];
        var b = replay.cellMap[bId];
        var opp = oppositeDir(dir);
        return barrierOf(a, dir) || barrierOf(b, opp);
      }
      function neighbors(id) {
        var dim = replay.dim;
        var rc = toRC(id, dim);
        var r = rc[0], c = rc[1];
        var list = [];
        if (r > 0) list.push(fromRC(r - 1, c, dim));
        if (r < dim - 1) list.push(fromRC(r + 1, c, dim));
        if (c > 0) list.push(fromRC(r, c - 1, dim));
        if (c < dim - 1) list.push(fromRC(r, c + 1, dim));
        // filter barriers
        return list.filter(function (nid) {
          return !isBlockedMove(id, nid);
        });
      }
      function bfsPath(startId, goalId) {
        startId = Number(startId);
        goalId  = Number(goalId);
        if (!startId || !goalId) return [];

        var q = [startId];
        var prev = {};
        var seen = {};
        seen[startId] = true;

        while (q.length) {
          var cur = q.shift();
          if (cur === goalId) break;

          var nbs = neighbors(cur);
          for (var i = 0; i < nbs.length; i++) {
            var nx = nbs[i];
            if (seen[nx]) continue;
            seen[nx] = true;
            prev[nx] = cur;
            q.push(nx);
          }
        }

        if (!seen[goalId]) return [];

        var path = [];
        var p = goalId;
        while (p != null) {
          path.push(p);
          if (p === startId) break;
          p = prev[p];
        }
        path.reverse();
        return path;
      }

      function buildFramesFromSnapshot(snap) {
        var frames = [];
        var moves = Array.isArray(snap.moves) ? snap.moves : [];
        var path  = Array.isArray(snap.path) ? snap.path : [];

        // frame 0 from start (t=0)
        if (path.length) {
          frames.push({ cellId: Number(path[0]), t_ms: 0 });
        }

        // subsequent frames from moves
        moves.forEach(function (m) {
          if (!m) return;
          var to = (m.to != null) ? Number(m.to) : null;
          var t  = (m.t_ms != null) ? Number(m.t_ms) : null;
          if (to != null) frames.push({ cellId: to, t_ms: (t != null ? t : 0) });
        });

        // fallback if no frames but have path
        if (!frames.length && path.length) {
          frames = path.map(function (cid, i) {
            return { cellId: Number(cid), t_ms: i * 500 };
          });
        }

        return frames;
      }

      function buildGrid(game, snap) {
        var dim = Number(game.grid_dim || snap.grid_dim || 0);
        if (!dim || dim < 2) dim = 5;

        var grid = safeJsonParse(game.grid_json, []);
        if (!Array.isArray(grid) || !grid.length) {
          // build empty grid if missing
          grid = [];
          for (var i = 1; i <= dim*dim; i++) {
            grid.push({
              id: i,
              label: "Cell " + i,
              barriers: {top:false,bottom:false,left:false,right:false},
              is_user: false,
              is_key: false,
              is_door: false
            });
          }
        }

        replay.dim = dim;
        replay.grid = grid;
        replay.cellMap = {};
        replay.startId = null;
        replay.keyId = null;
        replay.doorId = null;

        grid.forEach(function (cell) {
          if (!cell || cell.id == null) return;
          replay.cellMap[Number(cell.id)] = cell;

          if (cell.is_user) replay.startId = Number(cell.id);
          if (cell.is_key)  replay.keyId = Number(cell.id);
          if (cell.is_door) replay.doorId = Number(cell.id);
        });

        // fallback start from snapshot
        if (!replay.startId) replay.startId = Number(snap.start_index || 1);
        // fallback key/door if not in grid flags
        if (!replay.keyId) {
          for (var k = 1; k <= dim*dim; k++){
            if (replay.cellMap[k] && replay.cellMap[k].is_key) { replay.keyId = k; break; }
          }
        }
        if (!replay.doorId) {
          for (var d = 1; d <= dim*dim; d++){
            if (replay.cellMap[d] && replay.cellMap[d].is_door) { replay.doorId = d; break; }
          }
        }

        // actual path & moves
        replay.actualPath = Array.isArray(snap.path) ? snap.path.map(Number) : [];
        replay.moves = Array.isArray(snap.moves) ? snap.moves : [];
        replay.events = snap.events || {};
        replay.frames = buildFramesFromSnapshot(snap);
        replay.currentStep = 0;

        // optimal path (BFS: start->key + key->door)
        var best = [];
        var p1 = (replay.keyId ? bfsPath(replay.startId, replay.keyId) : []);
        var p2 = (replay.keyId && replay.doorId ? bfsPath(replay.keyId, replay.doorId) : []);
        if (p1.length && p2.length) {
          best = p1.concat(p2.slice(1));
        } else if (p1.length) {
          best = p1;
        } else if (replay.doorId) {
          best = bfsPath(replay.startId, replay.doorId);
        }
        replay.optimalPath = best;

        // build DOM grid
        if (gridEl) {
          gridEl.style.setProperty("--dim", String(dim));
          gridEl.innerHTML = "";

          var frag = document.createDocumentFragment();
          for (var id = 1; id <= dim*dim; id++) {
            var cellData = replay.cellMap[id] || { barriers:{top:false,bottom:false,left:false,right:false} };

            var cell = document.createElement("div");
            cell.className = "er-cell";
            cell.dataset.cellId = String(id);

            if (cellData.barriers && cellData.barriers.top) cell.classList.add("bar-top");
            if (cellData.barriers && cellData.barriers.bottom) cell.classList.add("bar-bottom");
            if (cellData.barriers && cellData.barriers.left) cell.classList.add("bar-left");
            if (cellData.barriers && cellData.barriers.right) cell.classList.add("bar-right");

            // mark static key/door
            if (id === replay.keyId) cell.classList.add("is-key");
            if (id === replay.doorId) cell.classList.add("is-door");

            cell.innerHTML = `
              <div class="er-cell-inner">
                <span class="er-cell-num">${id}</span>
                <div class="er-cell-icons">
                  <i class="fa-solid fa-person-walking er-ico-user"></i>
                  <i class="fa-solid fa-key er-ico-key"></i>
                  <i class="fa-solid fa-door-open er-ico-door"></i>
                </div>
                <span class="er-cell-step"></span>
              </div>
            `;
            frag.appendChild(cell);
          }
          gridEl.appendChild(frag);
        }

        replay.ready = true;
        if (gridHintEl) gridHintEl.textContent = "Use the replay controls to inspect the attempt visually.";

        // seek range
        if (seekEl) {
          seekEl.min = "0";
          seekEl.max = String(Math.max(0, replay.frames.length - 1));
          seekEl.value = "0";
        }

        applyStep(0);
        renderPaceChart(replay.moves);
        renderPathStats();
        detectMoveAnomalies();
      }

      function setPlayButton(statePlaying) {
        if (!btnPlay) return;
        btnPlay.innerHTML = statePlaying
          ? '<i class="fa-solid fa-pause"></i> Pause'
          : '<i class="fa-solid fa-play"></i> Play';
      }

      function stopReplay() {
        replay.playing = false;
        setPlayButton(false);
        if (replay.timer) {
          clearTimeout(replay.timer);
          replay.timer = null;
        }
      }

      function playReplay() {
        if (!replay.ready) return;
        stopReplay();
        replay.playing = true;
        setPlayButton(true);

        function tick() {
          if (!replay.playing) return;

          var step = replay.currentStep;
          if (step >= replay.frames.length - 1) {
            stopReplay();
            return;
          }

          var speed = Number(speedEl && speedEl.value ? speedEl.value : 1);
          var cur = replay.frames[step];
          var next = replay.frames[step + 1];

          var delta = Math.max(0, (next.t_ms || 0) - (cur.t_ms || 0));
          var delay = Math.max(120, Math.min(1200, Math.round(delta / speed)));

          applyStep(step + 1);
          replay.timer = setTimeout(tick, delay);
        }

        replay.timer = setTimeout(tick, 120);
      }

      function applyStep(stepIndex) {
        if (!replay.ready) return;

        stepIndex = Math.max(0, Math.min(stepIndex, replay.frames.length - 1));
        replay.currentStep = stepIndex;

        if (seekEl) seekEl.value = String(stepIndex);

        var now = replay.frames[stepIndex] || { cellId: replay.startId, t_ms: 0 };
        var nowCellId = Number(now.cellId);
        var nowT = Number(now.t_ms || 0);

        // events
        var ev = replay.events || {};
        var keyEv = ev.key || null;
        var doorEv = ev.door || null;

        var keyPicked = !!(keyEv && keyEv.t_ms != null && nowT >= Number(keyEv.t_ms));
        var doorOpened = !!(doorEv && doorEv.t_ms != null && nowT >= Number(doorEv.t_ms));

        // visited / step numbers (first visit)
        var firstVisit = {};
        var counts = {};
        replay.actualPath.forEach(function (cid, idx) {
          cid = Number(cid);
          counts[cid] = (counts[cid] || 0) + 1;
          if (firstVisit[cid] == null) firstVisit[cid] = idx;
        });

        // reset classes
        var cells = gridEl ? gridEl.querySelectorAll(".er-cell") : [];
        cells.forEach(function (el) {
          el.classList.remove("is-current","is-visited","key-picked","door-open","show-step","is-optimal");
          var stepBadge = el.querySelector(".er-cell-step");
          if (stepBadge) stepBadge.textContent = "";
        });

        // mark optimal path
        var showOptimal = !!(showOptimalEl && showOptimalEl.checked);
        if (showOptimal && replay.optimalPath && replay.optimalPath.length) {
          replay.optimalPath.forEach(function (cid) {
            var node = gridEl ? gridEl.querySelector('.er-cell[data-cell-id="'+cid+'"]') : null;
            if (node) node.classList.add("is-optimal");
          });
        }

        // mark visited up to stepIndex based on frames
        // We use actualPath order, and highlight everything up to the current visited frame count
        // frame 0 corresponds to path[0], frame i corresponds to path[i] if lengths match
        var upto = Math.min(stepIndex, replay.actualPath.length - 1);
        for (var i = 0; i <= upto; i++) {
          var cid = replay.actualPath[i];
          var nodeV = gridEl ? gridEl.querySelector('.er-cell[data-cell-id="'+cid+'"]') : null;
          if (nodeV) {
            nodeV.classList.add("is-visited");
            var badge = nodeV.querySelector(".er-cell-step");
            if (badge && showStepNosEl && showStepNosEl.checked) {
              nodeV.classList.add("show-step");
              badge.textContent = "#" + i;
            }
          }
        }

        // current
        var curEl = gridEl ? gridEl.querySelector('.er-cell[data-cell-id="'+nowCellId+'"]') : null;
        if (curEl) curEl.classList.add("is-current");

        // key picked -> hide key icon (mark key cell)
        if (keyPicked && replay.keyId) {
          var keyCell = gridEl ? gridEl.querySelector('.er-cell[data-cell-id="'+replay.keyId+'"]') : null;
          if (keyCell) keyCell.classList.add("key-picked");
        }

        // door opened -> highlight door
        if (doorOpened && replay.doorId) {
          var doorCell = gridEl ? gridEl.querySelector('.er-cell[data-cell-id="'+replay.doorId+'"]') : null;
          if (doorCell) doorCell.classList.add("door-open");
        }

        // meta
        if (stepMetaEl) {
          stepMetaEl.innerHTML = '<i class="fa-solid fa-route"></i> Step: <strong>' + stepIndex +
            '</strong> / ' + Math.max(0, replay.frames.length - 1) +
            ' &nbsp;•&nbsp; t(ms): <strong>' + nowT + '</strong>';
        }
      }

      function renderPaceChart(moves) {
        if (!paceChartEl) return;
        paceChartEl.innerHTML = "";

        if (!Array.isArray(moves) || !moves.length) {
          paceChartEl.innerHTML = '<div class="small text-muted">No move timing data.</div>';
          if (paceMetaEl) paceMetaEl.innerHTML = '<i class="fa-regular fa-clock"></i> -';
          return;
        }

        // deltas based on t_ms
        var deltas = [];
        var prev = 0;
        moves.forEach(function (m) {
          var t = Number(m && m.t_ms != null ? m.t_ms : 0);
          deltas.push(Math.max(0, t - prev));
          prev = t;
        });

        var maxD = Math.max.apply(null, deltas.concat([1]));
        var sum = deltas.reduce(function (a,b){return a+b;},0);
        var avg = Math.round(sum / deltas.length);
        var slow = Math.max.apply(null, deltas);
        var slowIdx = deltas.indexOf(slow);

        deltas.forEach(function (d) {
          var bar = document.createElement("div");
          bar.className = "er-pace-bar";
          var pct = Math.max(6, Math.round((d / maxD) * 100));
          bar.style.height = pct + "%";
          bar.setAttribute("data-ms", d + " ms");
          bar.title = "Δt: " + d + " ms";
          paceChartEl.appendChild(bar);
        });

        if (paceMetaEl) {
          paceMetaEl.innerHTML = '<i class="fa-regular fa-clock"></i> Avg: <strong>' + avg +
            'ms</strong> • Slowest: <strong>M' + (slowIdx+1) + '</strong> (' + slow + 'ms)';
        }
      }
    function clampPct(v){
  return Math.max(0, Math.min(100, v));
}

function computeEfficiencies(optimalMoves, actualMoves, timeLimitMs, timeTakenMs) {
  // Path Efficiency
  var pathEff = null;
  if (optimalMoves != null && actualMoves > 0) {
    pathEff = clampPct(Math.round((optimalMoves / actualMoves) * 100));
  }

  // Time Efficiency (Remaining time%)
  var timeEff = null;
  if (timeLimitMs > 0 && timeTakenMs != null) {
    timeEff = clampPct(Math.round(((timeLimitMs - timeTakenMs) / timeLimitMs) * 100));
  }

  // Final/Combined Efficiency
  var finalEff = null;

  if (pathEff != null && timeEff != null) {
    // weights: Path 70%, Time 30%
    finalEff = Math.round((0.5 * pathEff) + (0.5 * timeEff));
  } else if (pathEff != null) {
    finalEff = pathEff;
  } else if (timeEff != null) {
    finalEff = timeEff;
  }

  return { pathEff: pathEff, timeEff: timeEff, finalEff: finalEff };
}

      function renderPathStats() {
  if (!pathStatsEl) return;

  var path = replay.actualPath || [];
  var moves = replay.moves || [];
  var opt  = replay.optimalPath || [];

  if (!path.length) {
    pathStatsEl.innerHTML = `
      <div class="er-mini"><div class="k">Visited</div><div class="v">-</div></div>
      <div class="er-mini"><div class="k">Unique</div><div class="v">-</div></div>
      <div class="er-mini"><div class="k">Optimal moves</div><div class="v">-</div></div>
      <div class="er-mini"><div class="k">Path efficiency</div><div class="v">-</div></div>
      <div class="er-mini"><div class="k">Time efficiency</div><div class="v">-</div></div>
      <div class="er-mini"><div class="k">Total efficiency</div><div class="v">-</div></div>
    `;
    return;
  }

  var uniq = new Set(path.map(Number)).size;
  var revisits = path.length - uniq;

  var actualMoves = Array.isArray(moves) ? moves.length : 0;
  var optimalMoves = (opt.length ? (opt.length - 1) : null);

  var effObj = computeEfficiencies(
    optimalMoves,
    actualMoves,
    Number(replay.timeLimitMs || 0),
    Number(replay.timeTakenMs || 0)
  );

  var pathEffTxt  = (effObj.pathEff  != null) ? (effObj.pathEff + "%")  : "—";
  var timeEffTxt  = (effObj.timeEff  != null) ? (effObj.timeEff + "%")  : "—";
  var totalEffTxt = (effObj.finalEff != null) ? (effObj.finalEff + "%") : "—";

  pathStatsEl.innerHTML = `
    <div class="er-mini">
      <div class="k">Visited cells</div>
      <div class="v">${path.length}</div>
    </div>

    <div class="er-mini">
      <div class="k">Unique cells</div>
      <div class="v">${uniq}
        <span style="font-weight:600;color:var(--muted-color)">(${revisits} revisits)</span>
      </div>
    </div>

    <div class="er-mini">
      <div class="k">Optimal moves</div>
      <div class="v">${optimalMoves != null ? optimalMoves : "—"}</div>
    </div>

    <div class="er-mini">
      <div class="k">Path efficiency</div>
      <div class="v">${pathEffTxt}</div>
    </div>

    <div class="er-mini">
      <div class="k">Time efficiency</div>
      <div class="v">${timeEffTxt}</div>
    </div>

    <div class="er-mini">
      <div class="k">Total efficiency</div>
      <div class="v">${totalEffTxt}</div>
    </div>
  `;
}


      function detectMoveAnomalies() {
        if (!moveWarnEl) return;
        moveWarnEl.style.display = "none";
        moveWarnEl.innerHTML = "";

        var moves = replay.moves || [];
        if (!moves.length) return;

        var blocked = [];
        moves.forEach(function (m, idx) {
          var from = Number(m && m.from != null ? m.from : 0);
          var to   = Number(m && m.to != null ? m.to : 0);
          if (!from || !to) return;
          if (isBlockedMove(from, to)) {
            blocked.push({ idx: idx+1, from: from, to: to });
          }
        });

        if (!blocked.length) return;

        moveWarnEl.style.display = "block";
        moveWarnEl.innerHTML =
          '<i class="fa-solid fa-triangle-exclamation"></i> ' +
          'Detected moves crossing a barrier (for review): ' +
          blocked.map(function (b) {
            return '<strong>M'+b.idx+'</strong> ('+b.from+'→'+b.to+')';
          }).join(", ");
      }

      function bindReplayControls() {
        if (btnPrev) btnPrev.addEventListener("click", function () {
          stopReplay();
          applyStep(replay.currentStep - 1);
        });

        if (btnNext) btnNext.addEventListener("click", function () {
          stopReplay();
          applyStep(replay.currentStep + 1);
        });

        if (btnPlay) btnPlay.addEventListener("click", function () {
          if (!replay.ready) return;
          if (replay.playing) stopReplay();
          else playReplay();
        });

        if (seekEl) seekEl.addEventListener("input", function () {
          stopReplay();
          applyStep(Number(seekEl.value || 0));
        });

        if (showOptimalEl) showOptimalEl.addEventListener("change", function () {
          applyStep(replay.currentStep);
        });

        if (showStepNosEl) showStepNosEl.addEventListener("change", function () {
          applyStep(replay.currentStep);
        });

        if (speedEl) speedEl.addEventListener("change", function () {
          // no-op, speed is applied during play
        });
      }

      bindReplayControls();

      function renderMoves(snapshot) {
        questionListEl.innerHTML = "";

        var moves = snapshot && Array.isArray(snapshot.moves) ? snapshot.moves : [];
        var path  = snapshot && Array.isArray(snapshot.path) ? snapshot.path : [];
        var events = snapshot && snapshot.events ? snapshot.events : {};

        if ((!moves || !moves.length) && (!path || !path.length) && (!events || !Object.keys(events).length)) {
          noQuestionsEl.classList.remove("d-none");
          questionListEl.classList.add("d-none");
          return;
        }

        noQuestionsEl.classList.add("d-none");
        questionListEl.classList.remove("d-none");

        var frag = document.createDocumentFragment();

        // Card: Path
        if (path && path.length) {
          var cardP = document.createElement("div");
          cardP.className = "er-qcard";
          cardP.innerHTML = `
            <div class="er-qcard-head">
              <div class="er-q-left">
                <span class="er-q-badge">PATH</span>
              </div>
              <div class="er-q-meta">
                <span>Steps: <strong>${path.length}</strong></span>
              </div>
            </div>
            <div class="er-q-question-main">Visited cells:</div>
            <div class="er-qcard-answers">
              <div class="er-q-answer-block correct">
                <div class="er-q-answer-label">Sequence</div>
                <div class="er-q-answer-text">${path.join(" → ")}</div>
              </div>
              <div class="er-q-answer-block your">
                <div class="er-q-answer-label">Start index</div>
                <div class="er-q-answer-text">${snapshot.start_index != null ? snapshot.start_index : "—"}</div>
              </div>
            </div>
          `;
          frag.appendChild(cardP);
        }

        // Cards: Moves
        if (moves && moves.length) {
          moves.forEach(function (m, i) {
            var from = (m && m.from != null) ? m.from : "—";
            var to   = (m && m.to != null) ? m.to : "—";
            var tms  = (m && m.t_ms != null) ? m.t_ms : null;

            var card = document.createElement("div");
            card.className = "er-qcard";

            card.innerHTML = `
              <div class="er-qcard-head">
                <div class="er-q-left">
                  <span class="er-q-badge">MOVE ${i+1}</span>
                </div>
                <div class="er-q-meta">
                  <span>t(ms): <strong>${tms != null ? tms : "—"}</strong></span>
                </div>
              </div>

              <div class="er-q-question-main">
                Movement: <strong>${from}</strong> → <strong>${to}</strong>
              </div>

              <div class="er-qcard-answers">
                <div class="er-q-answer-block correct">
                  <div class="er-q-answer-label">From</div>
                  <div class="er-q-answer-text">${from}</div>
                </div>
                <div class="er-q-answer-block your">
                  <div class="er-q-answer-label">To</div>
                  <div class="er-q-answer-text">${to}</div>
                </div>
              </div>
            `;
            frag.appendChild(card);
          });
        }

        // Card: Events
        var hasKey = events && events.key;
        var hasDoor = events && events.door;
        if (hasKey || hasDoor) {
          var keyTxt = "—";
          var doorTxt = "—";
          if (hasKey) keyTxt = "Picked at index " + (events.key.picked_at_index ?? "—") + " (t_ms " + (events.key.t_ms ?? "—") + ")";
          if (hasDoor) doorTxt = "Opened at index " + (events.door.opened_at_index ?? "—") + " (t_ms " + (events.door.t_ms ?? "—") + ")";

          var cardE = document.createElement("div");
          cardE.className = "er-qcard";
          cardE.innerHTML = `
            <div class="er-qcard-head">
              <div class="er-q-left">
                <span class="er-q-badge">EVENTS</span>
              </div>
              <div class="er-q-meta"></div>
            </div>
            <div class="er-qcard-answers">
              <div class="er-q-answer-block correct">
                <div class="er-q-answer-label">Key</div>
                <div class="er-q-answer-text">${keyTxt}</div>
              </div>
              <div class="er-q-answer-block your">
                <div class="er-q-answer-label">Door</div>
                <div class="er-q-answer-text">${doorTxt}</div>
              </div>
            </div>
          `;
          frag.appendChild(cardE);
        }

        questionListEl.appendChild(frag);
      }

      function renderResult(payloadRaw) {
        var payload = payloadRaw && payloadRaw.data ? payloadRaw.data : (payloadRaw || {});
        var game    = payload.game || payload.door_game || payload.quiz || {};
        var result  = payload.result || payload || {};

        var gameTitle = game.title || game.game_title || game.name || "Door Game Result";
        gameTitleEl.textContent = gameTitle;

        var submittedAt = result.result_created_at || result.created_at || payload.result_created_at || null;
        var attemptNo   = result.attempt_no || result.attempt_number || "-";
        var status      = result.status || result.attempt_status || "-";

        attemptMetaEl.textContent =
          "Attempt no " + attemptNo + " | Submitted at " + formatDateTime(submittedAt);

        // ✅ include user_answer object too
        var snap = safeJsonParse(
          result.user_answer_json ||
          result.user_answer ||
          result.snapshot ||
          result.payload,
          {}
        );

        var timing = (snap && snap.timing) ? snap.timing : {};
        var timeTakenMs = Number(result.time_taken_ms || timing.time_taken_ms || 0);
        // ✅ for efficiencies
replay.timeLimitMs = Number(game.time_limit_sec || 0) * 1000;
replay.timeTakenMs = Number(timeTakenMs || 0);

        // Moves count
        var movesCount = (snap && Array.isArray(snap.moves)) ? snap.moves.length : 0;

        // Key/Door completion based success (graphical objective)
        var ev = snap && snap.events ? snap.events : {};
        var keyOk = !!(ev && ev.key);
        var doorOk = !!(ev && ev.door);

        var objectivePct = doorOk ? 100 : (keyOk ? 50 : 0);

        // Score can be 0/1, but objective might still be complete
        var score = Number(result.score || 0);

        var chip = statusToChip(status);
        scoreChipEl.innerHTML = '<i class="fa-solid fa-' + chip.icon + '"></i> ' + chip.label;

        percentEl.textContent = objectivePct + "%";
        marksEl.textContent   = String(score);
        attemptedEl.textContent = String(movesCount);
        timeSpentEl.textContent  = formatDurationMs(timeTakenMs);

        scoreTextEl.innerHTML =
          "Objective: <strong>" + (doorOk ? "Door opened" : (keyOk ? "Key picked" : "Not complete")) +
          "</strong>. Moves: <strong>" + movesCount +
          "</strong>. Time: <strong>" + (formatDurationMs(timeTakenMs)) + "</strong>.";

        requestAnimationFrame(function () {
          scoreBarEl.style.width = objectivePct + "%";
        });
        barLabelEl.textContent = "Objective progress: " + objectivePct + "%";

        // Events pills
        keyPickedEl.textContent  = "Key: " + (keyOk ? "Picked" : "Not picked");
        doorOpenedEl.textContent = "Door: " + (doorOk ? "Opened" : "Not opened");
        timeoutInfoEl.textContent = "Timeout: " + (String(status).toLowerCase() === "timeout" ? "Yes" : "No");

        resultIdEl.textContent  = result.result_id || result.id || result.result_uuid || result.uuid || "-";
        attemptNoEl.textContent = attemptNo;
        submittedAtEl.textContent = formatDateTime(submittedAt);

        renderMoves(snap);

        // ✅ Build grid visualization
        try{
          buildGrid(game, snap);
        }catch(e){
          console.warn("Grid analysis failed:", e);
          if (gridHintEl) gridHintEl.textContent = "Grid analysis unavailable for this attempt.";
        }
      }

      async function loadResult() {
        var token = getToken();
        if (!token) {
          clearAuthStorage();
          window.location.replace("/login");
          return;
        }

        showLoader(true);
        if (errorEl) { errorEl.classList.remove("show"); errorEl.textContent = ""; }

        try {
          // ✅ Door Game Result detail API
          var res = await fetch("/api/door-game-results/detail/" + encodeURIComponent(RESULT_ID), {
            method: "GET",
            headers: {
              "Accept": "application/json",
              "Authorization": "Bearer " + token
            }
          });

          var json;
          try { json = await res.json(); } catch (e) { json = {}; }

          if (res.status === 401 || res.status === 403) {
            clearAuthStorage();
            window.location.replace("/login");
            return;
          }

          if (!res.ok) throw new Error(json.message || json.error || "Failed to load result.");

          renderResult(json);
        } catch (err) {
          console.error(err);
          showError(err.message || "Failed to load result.");
        } finally {
          showLoader(false);
        }
      }

      if (pdfBtn) {
        pdfBtn.addEventListener("click", function () { window.print(); });
      }

      loadResult();
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initResultPage);
    } else {
      initResultPage();
    }
  })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
