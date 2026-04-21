{{-- resources/views/path-games/pathGameResultStandalone.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <title>Path Game Result</title>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/favicons/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}">

  <style id="prStyles">
    /* ===============================
        PAGE BASE
    ================================ */
    body{
      min-height:100vh;
      background:
        radial-gradient(1200px 600px at 25% 0%, rgba(73, 200, 195,.18), transparent 60%),
        radial-gradient(1200px 700px at 90% 40%, rgba(23, 162, 184,.18), transparent 55%),
        linear-gradient(180deg, rgba(2, 20, 24, .03), rgba(2, 20, 24, .00));
    }
/* ✅ Rocket animation inside grid */
.pr-stage-grid{
  position:relative; /* ✅ important for rocket positioning */
}

.pr-rocket{
  position:absolute;
  left:0; top:0;
  width:36px; height:36px;
  border-radius:999px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(255,255,255,.92);
  border:1px solid rgba(0,0,0,.08);
  box-shadow: 0 14px 24px rgba(0,0,0,.15);
  z-index:9;
  pointer-events:none;
  transform: translate(-50%, -50%);
  transition: transform .22s ease;
}

.pr-rocket i{
  font-size:16px;
  color:#0ea5e9;
  filter: drop-shadow(0 10px 12px rgba(0,0,0,.12));
}

    .pr-wrap{max-width:1280px;margin:18px auto 40px;padding:0 10px;}
    .pr-shell{
      border-radius:18px;border:1px solid var(--line-strong);
      background:var(--surface);box-shadow:var(--shadow-2);
      padding:16px 18px 18px;position:relative;
    }

    .pr-loader-wrap{
      position:absolute;inset:0;display:none;align-items:center;justify-content:center;
      background:rgba(0,0,0,.05);z-index:8;border-radius:18px;
      backdrop-filter: blur(3px);
    }
    .pr-loader-wrap.show{display:flex;}
    .pr-loader{
      width:22px;height:22px;border-radius:50%;
      border:3px solid #0001;border-top-color:var(--accent-color);
      animation:pr-rot 1s linear infinite;
    }
    @keyframes pr-rot{to{transform:rotate(360deg)}}
    .pr-error{margin-top:8px;font-size:12px;color:var(--danger-color);display:none;}
    .pr-error.show{display:block;}

    /* ===============================
        HEADER
    ================================ */
    .pr-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:12px;}
    .pr-title{font-family:var(--font-head);font-weight:900;color:var(--ink);font-size:1.25rem;margin:0;}
    .pr-sub{font-size:var(--fs-13);color:var(--muted-color);margin-top:3px;}
    .pr-actions{margin-left:auto;display:flex;flex-wrap:wrap;gap:8px;}
    .pr-actions .btn{border-radius:999px;padding-inline:12px;}
    .pr-actions .btn i{margin-right:6px;}
    .pr-row{margin-top:10px;}

    /* ===============================
        CARDS
    ================================ */
    .pr-card{
      border-radius:16px;border:1px solid var(--line-strong);
      background:var(--surface-2);
      padding:12px 12px 10px;
      box-shadow:var(--shadow-1);
    }
    .pr-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;}
    .pr-card-title{font-family:var(--font-head);font-weight:800;color:var(--ink);font-size:.95rem;margin:0;}

    .pr-chip{
      display:inline-flex;align-items:center;gap:6px;
      padding:4px 10px;border-radius:999px;font-size:11px;
      border:1px solid var(--line-strong);background:var(--surface);
      color:var(--muted-color);
      white-space:nowrap;
    }
    .pr-chip i{font-size:11px;}
    .pr-chip-primary{
      background:var(--t-primary);
      border-color:rgba(20,184,166,.25);
      color:#0f766e;
    }

    /* ===============================
        SCORE
    ================================ */
    .pr-score-main{display:flex;align-items:center;gap:14px;margin-bottom:10px;}
    .pr-score-circle{
      width:72px;height:72px;border-radius:50%;
      border:5px solid rgba(20,184,166,.16);
      display:flex;align-items:center;justify-content:center;flex-direction:column;
      font-family:var(--font-head);color:var(--ink);position:relative;
      background:linear-gradient(180deg, rgba(255,255,255,.85), rgba(255,255,255,.65));
    }
    .pr-score-circle::after{
      content:"";position:absolute;inset:6px;border-radius:inherit;
      border:3px solid var(--accent-color);opacity:.45;
    }
    .pr-score-value{font-size:1.25rem;font-weight:900;}
    .pr-score-label{font-size:11px;color:var(--muted-color);}
    .pr-score-text{font-size:var(--fs-13);color:var(--muted-color);}
    .pr-score-text strong{font-weight:800;color:var(--ink);}

    .pr-metrics{
      display:grid;grid-template-columns:repeat(3,minmax(0,1fr));
      gap:8px;margin-top:4px;
    }
    .pr-metric{
      border-radius:12px;background:var(--surface);
      border:1px dashed var(--line-strong);
      padding:7px 9px;font-size:var(--fs-12);
    }
    .pr-metric-label{color:var(--muted-color);margin-bottom:3px;}
    .pr-metric-value{font-weight:900;color:var(--ink);}

    .pr-bar-wrap{margin-top:6px;}
    .pr-bar-bg{width:100%;height:9px;border-radius:999px;background:#e7f3f1;overflow:hidden;}
    .pr-bar-fill{height:100%;border-radius:inherit;background:var(--accent-color);width:0%;transition:width .4s ease;}
    .pr-bar-label{font-size:var(--fs-12);color:var(--muted-color);margin-top:3px;}

    .pr-pill-row{display:flex;flex-wrap:wrap;gap:6px;font-size:var(--fs-12);}
    .pr-pill{
      padding:5px 10px;border-radius:999px;border:1px solid var(--line-strong);
      display:inline-flex;align-items:center;gap:6px;background:var(--surface);
    }
    .pr-pill i{font-size:12px;}
    .pr-pill-green{background:var(--t-success);border-color:rgba(22,163,74,.25);color:#15803d;}
    .pr-pill-red{background:var(--t-danger);border-color:rgba(220,38,38,.25);color:#b91c1c;}
    .pr-pill-gray{background:var(--surface-3);}

    /* ===============================
        TABLE / MOVES
    ================================ */
    .pr-table-card{
      margin-top:14px;border-radius:16px;border:1px solid var(--line-strong);
      background:var(--surface-2);box-shadow:var(--shadow-1);
      padding:10px 12px 12px;
    }
    .pr-table-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;}
    .pr-table-title{font-family:var(--font-head);font-weight:900;color:var(--ink);font-size:1.05rem;margin:0;}
    .pr-table-sub{font-size:var(--fs-13);color:var(--muted-color);}

    .pr-empty{
      margin-top:8px;border:1px dashed var(--line-strong);
      border-radius:12px;padding:16px;text-align:center;
      font-size:var(--fs-13);color:var(--muted-color);
      background:var(--surface-2);
    }

    .pr-q-list{margin-top:8px;display:flex;flex-direction:column;gap:10px;}
    .pr-qcard{
      border-radius:14px;border:1px solid var(--line-strong);
      background:var(--surface);padding:10px 10px 10px;box-shadow:var(--shadow-1);
    }
    .pr-qcard-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;}
    .pr-q-left{display:flex;align-items:center;gap:8px;}
    .pr-q-badge{
      min-width:54px;height:24px;border-radius:999px;border:1px solid var(--line-strong);
      padding: 5px 10px;
      background:var(--surface-3);
      display:flex;align-items:center;justify-content:center;
      font-size:11px;font-weight:800;color:var(--muted-color);
      letter-spacing:.02em;
    }
    .pr-q-meta{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end;font-size:11px;color:var(--muted-color);}
    .pr-q-meta span strong{color:var(--ink);}
    .pr-q-question-main{font-size:var(--fs-13);color:var(--text-color);}
    .pr-qcard-answers{
      margin-top:8px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;
    }
    @media (max-width: 768px){ .pr-qcard-answers{grid-template-columns:1fr;} }
    .pr-q-answer-block{
      border-radius:12px;border:1px solid var(--line-strong);
      background:var(--surface-2);padding:8px 10px;font-size:11px;
    }
    .pr-q-answer-block.correct{
      background:var(--t-success);border-color:rgba(22,163,74,.35);color:#14532d;
    }
    .pr-q-answer-block.your{border-style:dashed;}
    .pr-q-answer-label{
      font-weight:900;text-transform:uppercase;letter-spacing:.05em;
      margin-bottom:3px;color:var(--muted-color);
      font-size:10px;
    }
    .pr-q-answer-text{font-size:var(--fs-12);color:var(--text-color);word-break:break-word;}

    /* ===============================
        ✅ PREVIEW BOARD (MATCH SCREENSHOT)
    ================================ */
    .pr-viz-row{margin-top:12px;}

    .pr-replay-controls{
      display:flex;align-items:center;gap:8px;flex-wrap:wrap;width:100%;
      background:var(--surface);
      border:1px solid var(--line-strong);
      border-radius:999px;
      padding:8px 10px;
      box-shadow:var(--shadow-1);
      margin-bottom:10px;
    }
    .pr-replay-controls .btn{
      border-radius:999px;
      padding:5px 10px;
      font-size:12px;
    }
    .pr-replay-controls .btn i{margin-right:6px;}
    .pr-replay-controls .form-select{
      border-radius:999px;
      border:1px solid var(--line-strong);
      background:var(--surface);
      font-size:12px;
      padding-block:2px;
    }
    .pr-replay-controls input[type="range"]{
      flex:1;
      min-width:160px;
      accent-color: var(--accent-color);
    }

    /* Big teal stage container */
    .pr-stage{
      position:relative;
      border-radius:28px;
      padding:20px;
      background:linear-gradient(135deg, #86c7c8, #7dc8b3);
      box-shadow: 0 18px 40px rgba(0,0,0,.10);
      border:2px solid rgba(255,255,255,.45);
      overflow:hidden;
    }
    .pr-stage::before{
      content:"";
      position:absolute;
      inset:14px;
      border-radius:22px;
      border:1px solid rgba(255,255,255,.35);
      pointer-events:none;
    }

    .pr-stage-inner{
      display:flex;
      align-items:center;
      justify-content:center;
      gap:22px;
      min-height:560px;
    }

    .pr-side{
      width:150px;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:10px;
      user-select:none;
    }

    .pr-side-circle{
      width:88px;height:88px;border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      border:1px solid rgba(255,255,255,.55);
      box-shadow: 0 14px 24px rgba(0,0,0,.12);
      backdrop-filter: blur(10px);
    }

    .pr-side-start .pr-side-circle{
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.35), rgba(255,255,255,.08)),
                  linear-gradient(145deg, rgba(122, 132, 255,.45), rgba(100, 180, 210,.20));
    }
    .pr-side-end .pr-side-circle{
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.35), rgba(255,255,255,.08)),
                  linear-gradient(145deg, rgba(60, 220, 170,.45), rgba(60, 160, 210,.18));
    }

    .pr-side-circle i{
      font-size:30px;
      color:#fff;
      opacity:.95;
      filter: drop-shadow(0 8px 10px rgba(0,0,0,.15));
    }

    .pr-side-label{
      font-size:13px;
      font-weight:900;
      letter-spacing:.08em;
      color:rgba(255,255,255,.95);
      text-transform:uppercase;
      text-shadow: 0 10px 14px rgba(0,0,0,.18);
    }

    .pr-stage-grid{
      width:min(560px, 92vw);
      aspect-ratio:1/1;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    /* Grid looks like screenshot */
    .pr-grid{
      --dim: 9;
      display:grid;
      grid-template-columns:repeat(var(--dim), minmax(0,1fr));
      width:100%;
      height:100%;
      background:#d6dadd;
      padding:4px;
      gap:2px;
      border-radius:10px;
      box-shadow: 0 14px 26px rgba(0,0,0,.12);
      overflow:hidden;
      border:2px solid rgba(255,255,255,.70);
    }

    .pr-cell{
      position:relative;
      aspect-ratio:1/1;
      background:#fff;
      border-radius:6px;
      overflow:hidden;
      box-shadow:none;
      border:none;
      transition: transform .12s ease, outline .12s ease;
    }
    .pr-cell:hover{transform:translateY(-1px);}

    /* Major separators (3x3 block lines) */
    .pr-cell.pr-major-left{box-shadow: inset 4px 0 0 #cfd4d7;}
    .pr-cell.pr-major-top{box-shadow: inset 0 4px 0 #cfd4d7;}
    .pr-cell.pr-major-left.pr-major-top{box-shadow: inset 4px 0 0 #cfd4d7, inset 0 4px 0 #cfd4d7;}

    /* ✅ remove inner dots completely */
    .pr-cell::after{ display:none !important; }

    /* Current highlight */
    .pr-cell.is-current{
      outline: 3px solid rgba(10, 120, 120, .40);
      outline-offset:-3px;
    }

    /* Path highlight (soft) */
    .pr-cell.is-path{
      background:rgba(255,255,255,.92);
    }

    /* Optimal path (dotted border) */
    .pr-cell.is-optimal{
      outline:2px dashed rgba(40, 50, 60, .22);
      outline-offset:-6px;
    }

    /* Remove internal start/end icons (we show side bubbles) */
    .pr-ico-start,.pr-ico-end,.pr-lock,.pr-cell-num{display:none!important;}

    .pr-cell-inner{position:absolute; inset:0; display:flex; align-items:center; justify-content:center;}

    /* Arrow glyph */
    .pr-glyph{
      width:auto;height:auto;
      border:none;background:transparent;
      font-size:26px;
      font-weight:900;
      color:#09b3a4;
      line-height:1;
      transform:rotate(0deg);
      transition: transform .18s ease;
      user-select:none;
    }
    .pr-cell.is-current .pr-glyph{filter: drop-shadow(0 10px 14px rgba(0,0,0,.10));}
    .pr-cell.is-path .pr-glyph{color:#06b6d4;}

    .pr-cell-step{
      position:absolute; bottom:6px; right:6px;
      font-size:10px; color:#667085;
      padding:2px 6px;border-radius:999px;
      border:1px dashed rgba(0,0,0,.12);
      background:rgba(255,255,255,.85);
      display:none;
    }
    .pr-cell.show-step .pr-cell-step{display:inline-flex;}

    /* Pace chart */
    .pr-pace{
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
    .pr-pace-bar{
      flex:1;
      min-width:8px;
      border-radius:10px;
      border:1px solid var(--line-strong);
      background: color-mix(in oklab, var(--accent-color) 30%, var(--surface));
      position:relative;
      transition: filter .15s ease;
    }
    .pr-pace-bar:hover{filter:brightness(1.05);}
    .pr-pace-bar::after{
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
    .pr-pace-bar:hover::after{opacity:1;}

    .pr-mini-metrics{
      margin-top:10px;
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:8px;
    }
    .pr-mini{
      border-radius:12px;
      border:1px solid var(--line-strong);
      background:var(--surface);
      padding:8px;
      font-size:12px;
    }
    .pr-mini .k{color:var(--muted-color);font-size:11px;margin-bottom:2px;}
    .pr-mini .v{color:var(--ink);font-weight:900;}

    @media (max-width: 992px){
      .pr-stage-inner{flex-direction:column;min-height:auto;}
      .pr-side{width:auto;flex-direction:row;gap:12px;}
      .pr-side-label{margin-top:0;}
      .pr-stage{padding:16px;}
      .pr-replay-controls input[type="range"]{min-width:140px;}
    }

    @media print{
      #sidebar,.w3-sidebar,.w3-appbar,#sidebarOverlay{display:none!important;}
      body{background:#fff!important;}
      .pr-actions{display:none!important;}
      .pr-replay-controls{display:none!important;}
      .pr-stage{box-shadow:none!important;border:1px solid #ddd!important;background:#fff!important;}
      .pr-stage::before{display:none!important;}
      .pr-grid{box-shadow:none!important;border:1px solid #ddd!important;}
    }

    html.theme-dark .pr-shell,
    html.theme-dark .pr-card,
    html.theme-dark .pr-table-card{background:#04151f;}
    html.theme-dark .pr-empty{background:#020b13;}
    html.theme-dark .pr-qcard{background:#020b13;}
  </style>
</head>

<body>
  <div class="pr-wrap">
    <div id="resultShell" class="pr-shell" data-result-id="{{ $resultId }}">

      <div class="pr-loader-wrap" id="prLoader">
        <div class="pr-loader"></div>
      </div>

      <div class="pr-head">
        <img id="logo" src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination" style="height:50px;width:auto;">

        <div>
          <h1 class="pr-title" id="prGameTitle">Path Game Result</h1>
          <div class="pr-sub" id="prAttemptMeta">Loading attempt details...</div>
        </div>

        <div class="pr-actions">
          <button type="button" class="btn btn-primary btn-sm" id="prPdfExport">
            <i class="fa-regular fa-file-pdf"></i> Export PDF
          </button>
        </div>
      </div>

      <div class="row g-3 pr-row">
        <div class="col-md-7">
          <div class="pr-card">
            <div class="pr-card-head">
              <h2 class="pr-card-title">Attempt summary</h2>
              <span class="pr-chip pr-chip-primary" id="prScoreChip">
                <i class="fa-solid fa-award"></i> Overall
              </span>
            </div>

            <div class="pr-score-main">
              <div class="pr-score-circle">
                <div class="pr-score-value" id="prPercent">0%</div>
                <div class="pr-score-label">Completion</div>
              </div>
              <div class="pr-score-text" id="prScoreText">
                Your attempt summary will appear here once the data is loaded.
              </div>
            </div>

            <div class="pr-metrics">
              <div class="pr-metric">
                <div class="pr-metric-label">Score</div>
                <div class="pr-metric-value" id="prMarks">0</div>
              </div>
              <div class="pr-metric">
                <div class="pr-metric-label">Rotations</div>
                <div class="pr-metric-value" id="prRotations">0</div>
              </div>
              <div class="pr-metric">
                <div class="pr-metric-label">Time spent</div>
                <div class="pr-metric-value" id="prTimeSpent">-</div>
              </div>
            </div>

            <div class="pr-bar-wrap">
              <div class="pr-bar-bg">
                <div class="pr-bar-fill" id="prScoreBar"></div>
              </div>
              <div class="pr-bar-label" id="prBarLabel">Completion: 0%</div>
            </div>
          </div>
        </div>

        <div class="col-md-5">
          <div class="pr-card">
            <div class="pr-card-head">
              <h2 class="pr-card-title">Status & key stats</h2>
              <span class="pr-chip" id="prAttemptChip">
                <i class="fa-regular fa-circle-check"></i>
                Attempt
              </span>
            </div>

            <div class="pr-pill-row mb-2">
              <span class="pr-pill pr-pill-green">
                <i class="fa-solid fa-rocket"></i>
                <span id="prStartInfo">Start: -</span>
              </span>
              <span class="pr-pill pr-pill-red">
                <i class="fa-solid fa-earth-asia"></i>
                <span id="prEndInfo">End: -</span>
              </span>
              <span class="pr-pill pr-pill-gray">
                <i class="fa-regular fa-clock"></i>
                <span id="prTimeoutInfo">Timeout: -</span>
              </span>
            </div>

            <ul class="mb-0 small text-muted" id="prMetaList">
              <li>Result ID: <span id="prResultId">-</span></li>
              <li>Attempt no: <span id="prAttemptNo">-</span></li>
              <li>Submitted at: <span id="prSubmittedAt">-</span></li>
            </ul>
          </div>
        </div>
      </div>

      {{-- ✅ VISUAL PREVIEW MATCHING YOUR IMAGE --}}
      <div class="row g-3 pr-viz-row">
        <div class="col-lg-7">
          <div class="pr-card">
            <div class="pr-card-head">
              <h2 class="pr-card-title">Preview replay & rotation analysis</h2>
              <span class="pr-chip" id="prStepMeta">
                <i class="fa-solid fa-rotate"></i> Step: -
              </span>
            </div>

            <div class="pr-replay-controls">
              <button class="btn btn-outline-secondary btn-sm" id="prReplayPrev" type="button">
                <i class="fa-solid fa-backward-step"></i> Prev
              </button>

              <button class="btn btn-outline-secondary btn-sm" id="prReplayPlay" type="button">
                <i class="fa-solid fa-play"></i> Play
              </button>

              <button class="btn btn-outline-secondary btn-sm" id="prReplayNext" type="button">
                <i class="fa-solid fa-forward-step"></i> Next
              </button>

              <input type="range" min="0" max="0" value="0" id="prReplaySeek" />

              <select id="prReplaySpeed" class="form-select form-select-sm" style="width:auto">
                <option value="1">1×</option>
                <option value="2">2×</option>
                <option value="4">4×</option>
              </select>

              <div class="form-check form-switch ms-auto d-none">
                <input class="form-check-input" type="checkbox" id="prShowOptimal" checked>
                <label class="form-check-label small text-muted" for="prShowOptimal">Optimal path</label>
              </div>

              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="prShowStepNos" checked>
                <label class="form-check-label small text-muted" for="prShowStepNos">Step nos</label>
              </div>
            </div>

            {{-- ✅ Screenshot-like stage --}}
            <div class="pr-stage">
              <div class="pr-stage-inner">
                <div class="pr-side pr-side-start">
                  <div class="pr-side-circle"><i class="fa-solid fa-rocket"></i></div>
                  <div class="pr-side-label">Start</div>
                </div>

                <div class="pr-stage-grid">
  <div id="prRocket" class="pr-rocket d-none">
    <i class="fa-solid fa-rocket"></i>
  </div>

  <div id="prGrid" class="pr-grid" style="--dim:9">
    {{-- grid filled by JS --}}
  </div>
</div>


                <div class="pr-side pr-side-end">
                  <div class="pr-side-circle"><i class="fa-solid fa-earth-asia"></i></div>
                  <div class="pr-side-label">Earth</div>
                </div>
              </div>
            </div>

            <div id="prGridHint" class="small text-muted mt-2">
              Replay will load after attempt snapshot is fetched.
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="pr-card">
            <div class="pr-card-head">
              <h2 class="pr-card-title">Rotation pace (ms per action)</h2>
              <span class="pr-chip" id="prPaceMeta">
                <i class="fa-regular fa-clock"></i> -
              </span>
            </div>

            <div id="prPaceChart" class="pr-pace"></div>

            <div class="pr-mini-metrics" id="prPathStats"></div>

            <div id="prMoveWarnings" class="mt-2 small text-muted" style="display:none"></div>
          </div>
        </div>
      </div>

      <div class="pr-table-card mt-3">
        <div class="pr-table-head">
          <div>
            <h2 class="pr-table-title">Move-wise analysis</h2>
            <div class="pr-table-sub">Rotations + timing + final path info (from attempt snapshot).</div>
          </div>
        </div>

        <div id="prNoQuestions" class="pr-empty d-none">
          No move-level data is available for this attempt.
        </div>

        <div id="prQuestionList" class="pr-q-list"></div>
      </div>

      <div id="prError" class="pr-error"></div>
    </div>
  </div>
<script>
(function () {
  "use strict";

  function initResultPage() {
    const resultShell = document.getElementById("resultShell");
    if (!resultShell) return;

    const RESULT_ID = resultShell.dataset.resultId;
    if (!RESULT_ID) {
      const errEl = document.getElementById("prError");
      if (errEl) {
        errEl.textContent = "Result reference is missing.";
        errEl.classList.add("show");
      }
      return;
    }

    const DETAIL_API_PREFIX = "/api/path-game-results/detail/";

    // UI
    const loaderEl    = document.getElementById("prLoader");
    const errorEl     = document.getElementById("prError");

    const gameTitleEl   = document.getElementById("prGameTitle");
    const attemptMetaEl = document.getElementById("prAttemptMeta");
    const scoreChipEl   = document.getElementById("prScoreChip");

    const percentEl   = document.getElementById("prPercent");
    const scoreTextEl = document.getElementById("prScoreText");
    const marksEl     = document.getElementById("prMarks");
    const rotationsEl = document.getElementById("prRotations");
    const timeSpentEl = document.getElementById("prTimeSpent");
    const scoreBarEl  = document.getElementById("prScoreBar");
    const barLabelEl  = document.getElementById("prBarLabel");

    const startInfoEl   = document.getElementById("prStartInfo");
    const endInfoEl     = document.getElementById("prEndInfo");
    const timeoutInfoEl = document.getElementById("prTimeoutInfo");

    const resultIdEl    = document.getElementById("prResultId");
    const attemptNoEl   = document.getElementById("prAttemptNo");
    const submittedAtEl = document.getElementById("prSubmittedAt");

    const questionListEl = document.getElementById("prQuestionList");
    const noQuestionsEl  = document.getElementById("prNoQuestions");

    const pdfBtn  = document.getElementById("prPdfExport");

    const gridEl = document.getElementById("prGrid");
    const rocketEl = document.getElementById("prRocket"); // ✅ ROCKET
    const gridHintEl = document.getElementById("prGridHint");
    const stepMetaEl = document.getElementById("prStepMeta");

    const btnPrev = document.getElementById("prReplayPrev");
    const btnPlay = document.getElementById("prReplayPlay");
    const btnNext = document.getElementById("prReplayNext");
    const seekEl  = document.getElementById("prReplaySeek");
    const speedEl = document.getElementById("prReplaySpeed");
    const showOptimalEl = document.getElementById("prShowOptimal");
    const showStepNosEl = document.getElementById("prShowStepNos");

    const paceChartEl = document.getElementById("prPaceChart");
    const paceMetaEl  = document.getElementById("prPaceMeta");
    const pathStatsEl = document.getElementById("prPathStats");
    const moveWarnEl  = document.getElementById("prMoveWarnings");

    const replay = {
      ready: false,
      dim: 0,
      major: 3,
      gridDim: 0,
      startId: null,
      endId: null,

      baseRot: {},
      rotNow: {},

      actualPath: [],
      optimalPath: [],
      arrowMap: {},

      moves: [],
      frames: [],
      currentStep: 0,
      maxStep: 0, // ✅ NEW MASTER TIMELINE (path + rotations)

      playing: false,
      timer: null,

      timeLimitMs: 0,
      timeTakenMs: 0,

      eventsMap: {}
    };

    /* =========================
       Helpers
    ========================== */
    function getToken() {
      try {
        return sessionStorage.getItem("token") ||
          sessionStorage.getItem("auth_token") ||
          localStorage.getItem("token") ||
          localStorage.getItem("auth_token") ||
          null;
      } catch (e) {
        return null;
      }
    }

    function clearAuthStorage() {
      try { sessionStorage.removeItem("token"); } catch (e) {}
      try { sessionStorage.removeItem("auth_token"); } catch (e) {}
      try { sessionStorage.removeItem("role"); } catch (e) {}
      try { localStorage.removeItem("token"); } catch (e) {}
      try { localStorage.removeItem("auth_token"); } catch (e) {}
      try { localStorage.removeItem("role"); } catch (e) {}
    }

    function showLoader(show) {
      if (!loaderEl) return;
      loaderEl.classList.toggle("show", !!show);
    }

    function showError(msg) {
      if (!errorEl) return;
      errorEl.textContent = msg || "Something went wrong while loading the result.";
      errorEl.classList.add("show");
    }

    function safeJsonParse(raw, fallback) {
      try {
        if (raw == null) return fallback;
        if (typeof raw === "object") return raw;
        const s = String(raw).trim();
        if (!s) return fallback;
        return JSON.parse(s);
      } catch (e) {
        return fallback;
      }
    }

    function clampPct(v) { return Math.max(0, Math.min(100, v)); }

    function formatDateTime(str) {
      if (!str) return "-";
      const normalized = String(str).replace(" ", "T");
      const d = new Date(normalized);
      if (isNaN(d.getTime())) return str;
      return d.toLocaleString();
    }

    function formatDurationMs(ms) {
      const v = Number(ms || 0);
      if (!v) return "-";
      const sec = Math.round(v / 1000);
      const m = Math.floor(sec / 60);
      const s = sec % 60;
      if (m && s) return m + "m " + s + "s";
      if (m) return m + "m";
      return s + "s";
    }

    function statusToChip(status) {
      const s = String(status || "").toLowerCase();
      if (s === "win") return { label: "SOLVED", icon: "trophy", pct: 100 };
      if (s === "fail") return { label: "FAILED", icon: "circle-xmark", pct: 0 };
      if (s === "timeout") return { label: "TIMEOUT", icon: "clock", pct: 0 };
      if (s === "in_progress") return { label: "IN PROGRESS", icon: "hourglass-half", pct: null };
      if (s === "auto_submitted") return { label: "AUTO", icon: "bolt", pct: 0 };
      return { label: (status || "ATTEMPT"), icon: "circle-check", pct: null };
    }

    function guessDim(game, snap) {
      const g = Number((snap && snap.grid_dim) || (game && game.grid_dim) || 0);
      const m = Number((snap && snap.mini_dim) || (game && game.mini_dim) || 3);
      if (g >= 2 && m >= 1) return g * m;
      return 9;
    }

    function guessGridDim(game, snap) {
      const g = Number((snap && snap.grid_dim) || (game && game.grid_dim) || 3);
      return (g >= 2 ? g : 3);
    }

    /* ✅ normalize path properly (supports 0-based also)
       ✅ NO AUTO PUSH LAST CELL (NO DEFAULT 81) */
    function normalizePath(rawPath, dim) {
      let p = Array.isArray(rawPath) ? rawPath.map(Number).filter(Number.isFinite) : [];
      if (!p.length) return [];

      const max = Math.max.apply(null, p);
      const min = Math.min.apply(null, p);

      // if it's 0-based index, shift +1
      if (min === 0 && max === (dim * dim - 1)) {
        p = p.map(v => v + 1);
      }

      // clamp valid range
      p = p.filter(v => v >= 1 && v <= dim * dim);
      return p;
    }

    /* ✅ arrows calculated by row/col (NOT diff) */
    function buildArrowMap(path, dim) {
      const map = {};
      if (!Array.isArray(path) || path.length < 2 || !dim) return map;

      function rc(id) {
        const r = Math.floor((id - 1) / dim) + 1;
        const c = ((id - 1) % dim) + 1;
        return { r, c };
      }

      for (let i = 0; i < path.length - 1; i++) {
        const a = Number(path[i]);
        const b = Number(path[i + 1]);
        if (!a || !b) continue;

        const A = rc(a);
        const B = rc(b);

        if (B.r === A.r && B.c === A.c + 1) map[a] = "→";
        else if (B.r === A.r && B.c === A.c - 1) map[a] = "←";
        else if (B.c === A.c && B.r === A.r + 1) map[a] = "↓";
        else if (B.c === A.c && B.r === A.r - 1) map[a] = "↑";
        else map[a] = "";
      }
      return map;
    }

    function normalizeMoves(snap) {
      if (!snap) return [];
      const arr = Array.isArray(snap.rotation_log) ? snap.rotation_log : [];

      return arr.map((m, idx) => {
        const tileIndex = Number(m.tile_index ?? m.tileIndex ?? m.cellId ?? 0) || null;

        const dir = String(m.dir || m.rotation_type || m.rotationType || "cw").toLowerCase();
        const isCcw = (dir === "ccw" || dir === "left");

        const rotateBy = Number(m.rotate_by_deg ?? 90);
        const delta = isCcw ? -Math.abs(rotateBy) : Math.abs(rotateBy);

        const beforeDeg = (m.before_deg != null) ? Number(m.before_deg) : null;
        const afterDeg  = (m.after_deg  != null) ? Number(m.after_deg)  : null;

        const tms = Number(m.t_ms ?? m.time_ms ?? 0) || 0;

        let toRot = afterDeg;
        if ((toRot == null || isNaN(toRot)) && m.rotation_count != null) {
          const cnt = Number(m.rotation_count || 0);
          let deg = (cnt * 90) % 360;
          if (isCcw) deg = ((360 - deg) % 360);
          toRot = deg;
        }

        return {
          index: Number(m.rotation_step || (idx + 1)),
          tileId: tileIndex,
          delta: delta,
          beforeDeg: (beforeDeg != null && !isNaN(beforeDeg)) ? beforeDeg : null,
          toRot: (toRot != null && !isNaN(toRot)) ? ((toRot % 360 + 360) % 360) : null,
          t_ms: tms,
          dir: isCcw ? "ccw" : "cw",
          rotate_by_deg: Math.abs(rotateBy) || 90
        };
      }).filter(x => x.tileId != null);
    }

    function buildFramesFromMoves(moves) {
      const frames = [{ step: 0, tileId: null, t_ms: 0, move: null }];
      moves.forEach(m => {
        frames.push({ step: m.index, tileId: m.tileId, t_ms: Number(m.t_ms || 0), move: m });
      });
      return frames;
    }

    /* ✅ ROCKET MOVE */
    function moveRocketToCell(cellId){
      if (!rocketEl || !gridEl || !cellId) return;

      const cell = gridEl.querySelector('.pr-cell[data-cell-id="' + cellId + '"]');
      if (!cell) return;

      rocketEl.classList.remove("d-none");

      const gridRect = gridEl.getBoundingClientRect();
      const cellRect = cell.getBoundingClientRect();

      const x = (cellRect.left - gridRect.left) + (cellRect.width / 2);
      const y = (cellRect.top  - gridRect.top ) + (cellRect.height / 2);

      rocketEl.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%)`;
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
      if (replay.timer) { clearTimeout(replay.timer); replay.timer = null; }
    }

    /* ✅ UPDATED: play based on replay.maxStep (path+rotations) */
    function playReplay() {
      if (!replay.ready) return;
      stopReplay();
      replay.playing = true;
      setPlayButton(true);

      function tick() {
        if (!replay.playing) return;

        const step = replay.currentStep;
        if (step >= replay.maxStep) {
          stopReplay();
          return;
        }

        const speed = Number(speedEl && speedEl.value ? speedEl.value : 1);

        // ✅ timing from rotation frames if available else smooth constant
        let delay = Math.max(140, Math.round(220 / speed));
        const curF = replay.frames[Math.min(step, replay.frames.length - 1)] || null;
        const nxtF = replay.frames[Math.min(step + 1, replay.frames.length - 1)] || null;

        if (curF && nxtF) {
          const delta = Math.max(0, (nxtF.t_ms || 0) - (curF.t_ms || 0));
          if (delta > 0) delay = Math.max(140, Math.min(900, Math.round(delta / speed)));
        }

        applyStep(step + 1);
        replay.timer = setTimeout(tick, delay);
      }

      replay.timer = setTimeout(tick, 160);
    }

    function applyRotationStateFromFrame(stepIndex) {
      replay.rotNow = Object.assign({}, replay.baseRot);

      // ✅ rotations only exist upto frames.length-1
      const capped = Math.min(stepIndex, replay.frames.length - 1);

      for (let i = 1; i <= capped; i++) {
        const f = replay.frames[i];
        if (!f || !f.move) continue;

        const mv = f.move;
        const tileId = Number(mv.tileId || 0);
        if (!tileId) continue;

        const current = Number(replay.rotNow[tileId] || 0);

        if (mv.toRot != null && !isNaN(mv.toRot)) {
          replay.rotNow[tileId] = ((mv.toRot % 360) + 360) % 360;
        } else if (mv.delta != null && !isNaN(mv.delta)) {
          replay.rotNow[tileId] = ((current + mv.delta) % 360 + 360) % 360;
        } else {
          replay.rotNow[tileId] = ((current + 90) % 360);
        }
      }
    }

    /* ✅ UPDATED: Rocket follows actualPath STEP-BY-STEP */
    function applyStep(stepIndex) {
      if (!replay.ready) return;

      stepIndex = Math.max(0, Math.min(stepIndex, replay.maxStep));
      replay.currentStep = stepIndex;

      if (seekEl) seekEl.value = String(stepIndex);

      applyRotationStateFromFrame(stepIndex);

      const nodes = gridEl ? gridEl.querySelectorAll(".pr-cell") : [];
      nodes.forEach(el => {
        el.classList.remove("is-current", "is-path", "is-optimal", "show-step");
        const badge = el.querySelector(".pr-cell-step");
        if (badge) badge.textContent = "";
        const glyph = el.querySelector(".pr-glyph");
        if (glyph) glyph.textContent = "";
      });

      // paint path arrows
      if (Array.isArray(replay.actualPath) && replay.actualPath.length) {
        replay.actualPath.forEach((cid, idx) => {
          const n = gridEl ? gridEl.querySelector('.pr-cell[data-cell-id="' + cid + '"]') : null;
          if (!n) return;

          n.classList.add("is-path");

          const g = n.querySelector(".pr-glyph");
          if (g) g.textContent = replay.arrowMap[cid] || "";

          if (showStepNosEl && showStepNosEl.checked) {
            n.classList.add("show-step");
            const b = n.querySelector(".pr-cell-step");
            if (b) b.textContent = "#" + idx;
          }
        });
      }

      // optimal overlay
      const showOpt = !!(showOptimalEl && showOptimalEl.checked);
      if (showOpt && Array.isArray(replay.optimalPath) && replay.optimalPath.length) {
        replay.optimalPath.forEach(cid => {
          const n2 = gridEl ? gridEl.querySelector('.pr-cell[data-cell-id="' + cid + '"]') : null;
          if (n2) n2.classList.add("is-optimal");
        });
      }

      // ✅ Rocket strictly follows PATH stepIndex (no ratio mapping)
      let curCellId = null;
      if (replay.actualPath && replay.actualPath.length) {
        const pathIndex = Math.min(stepIndex, replay.actualPath.length - 1);
        curCellId = replay.actualPath[pathIndex] || null;
      }

      if (curCellId) {
        const nCur = gridEl ? gridEl.querySelector('.pr-cell[data-cell-id="' + curCellId + '"]') : null;
        if (nCur) nCur.classList.add("is-current");
        moveRocketToCell(curCellId);
      } else {
        if (rocketEl) rocketEl.classList.add("d-none");
      }

      // rotate visual by tile rotation
      nodes.forEach(el => {
        const tileId = Number(el.dataset.tileId || 0);
        const rot = Number(replay.rotNow[tileId] || 0);
        const glyph = el.querySelector(".pr-glyph");
        if (glyph) glyph.style.transform = "rotate(" + rot + "deg)";
      });

      // step meta
      const f = replay.frames[Math.min(stepIndex, replay.frames.length - 1)] || null;
      const nowT = f ? Number(f.t_ms || 0) : 0;

      if (stepMetaEl) {
        stepMetaEl.innerHTML =
          '<i class="fa-solid fa-rotate"></i> Step: <strong>' + stepIndex +
          '</strong> / ' + replay.maxStep +
          (nowT ? (' &nbsp;•&nbsp; t(ms): <strong>' + nowT + '</strong>') : '');
      }
    }

    // ✅ Keep rocket aligned on resize
    window.addEventListener("resize", function(){
      if (!replay.ready || !replay.actualPath || !replay.actualPath.length) return;
      const idx = Math.min(replay.currentStep, replay.actualPath.length - 1);
      moveRocketToCell(replay.actualPath[idx]);
    });

    function renderPaceChart(moves) {
      if (!paceChartEl) return;
      paceChartEl.innerHTML = "";

      if (!Array.isArray(moves) || !moves.length) {
        paceChartEl.innerHTML = '<div class="small text-muted">No action timing data.</div>';
        if (paceMetaEl) paceMetaEl.innerHTML = '<i class="fa-regular fa-clock"></i> -';
        return;
      }

      const deltas = [];
      let prev = 0;

      moves.forEach(m => {
        const t = Number(m && m.t_ms != null ? m.t_ms : 0);
        deltas.push(Math.max(0, t - prev));
        prev = t;
      });

      const maxD = Math.max.apply(null, deltas.concat([1]));
      const sum = deltas.reduce((a, b) => a + b, 0);
      const avg = Math.round(sum / deltas.length);
      const slow = Math.max.apply(null, deltas);
      const slowIdx = deltas.indexOf(slow);

      deltas.forEach(d => {
        const bar = document.createElement("div");
        bar.className = "pr-pace-bar";
        const pct = Math.max(6, Math.round((d / maxD) * 100));
        bar.style.height = pct + "%";
        bar.setAttribute("data-ms", d + " ms");
        bar.title = "Δt: " + d + " ms";
        paceChartEl.appendChild(bar);
      });

      if (paceMetaEl) {
        paceMetaEl.innerHTML =
          '<i class="fa-regular fa-clock"></i> Avg: <strong>' + avg +
          'ms</strong> • Slowest: <strong>A' + (slowIdx + 1) + '</strong> (' + slow + 'ms)';
      }
    }

    function computeEfficiencies(optimalMoves, actualMoves, timeLimitMs, timeTakenMs) {
      let pathEff = null;
      if (optimalMoves != null && actualMoves > 0) {
        pathEff = clampPct(Math.round((optimalMoves / actualMoves) * 100));
      }

      let timeEff = null;
      if (timeLimitMs > 0 && timeTakenMs != null) {
        timeEff = clampPct(Math.round(((timeLimitMs - timeTakenMs) / timeLimitMs) * 100));
      }

      let finalEff = null;
      if (pathEff != null && timeEff != null) finalEff = Math.round((0.5 * pathEff) + (0.5 * timeEff));
      else if (pathEff != null) finalEff = pathEff;
      else if (timeEff != null) finalEff = timeEff;

      return { pathEff, timeEff, finalEff };
    }

    function renderPathStats(game, snap) {
      if (!pathStatsEl) return;

      const path = replay.actualPath || [];
      const moves = replay.moves || [];

      const timeLimitMs = Number(replay.timeLimitMs || 0);
      const timeTakenMs = Number(replay.timeTakenMs || 0);

      const optimalMoves =
        (game.optimal_moves != null ? Number(game.optimal_moves) :
        (game.min_moves != null ? Number(game.min_moves) :
        (Array.isArray(replay.optimalPath) && replay.optimalPath.length ? (replay.optimalPath.length - 1) : null)));

      const actualMoves = moves.length;

      const uniq = new Set(path.map(Number)).size;
      const revisits = path.length - uniq;

      const effObj = computeEfficiencies(optimalMoves, actualMoves, timeLimitMs, timeTakenMs);

      const pathEffTxt  = (effObj.pathEff  != null) ? (effObj.pathEff + "%")  : "—";
      const timeEffTxt  = (effObj.timeEff  != null) ? (effObj.timeEff + "%")  : "—";
      const totalEffTxt = (effObj.finalEff != null) ? (effObj.finalEff + "%") : "—";

      pathStatsEl.innerHTML = `
        <div class="pr-mini"><div class="k">Visited cells</div><div class="v">${path.length || "—"}</div></div>
        <div class="pr-mini"><div class="k">Unique cells</div><div class="v">${path.length ? (uniq + " (" + revisits + " revisits)") : "—"}</div></div>
        <div class="pr-mini d-none"><div class="k">Optimal moves</div><div class="v">${optimalMoves != null ? optimalMoves : "—"}</div></div>
        <div class="pr-mini"><div class="k">Rotations</div><div class="v">${actualMoves}</div></div>
        <div class="pr-mini d-none"><div class="k">Path efficiency</div><div class="v">${pathEffTxt}</div></div>
        <div class="pr-mini"><div class="k">Time efficiency</div><div class="v">${timeEffTxt}</div></div>
        <div class="pr-mini d-none"><div class="k">Total efficiency</div><div class="v">${totalEffTxt}</div></div>
      `;
    }

    function detectMoveAnomalies() {
      if (!moveWarnEl) return;
      moveWarnEl.style.display = "none";
      moveWarnEl.innerHTML = "";

      const moves = replay.moves || [];
      if (!moves.length) return;

      const counts = {};
      moves.forEach(m => {
        counts[m.tileId] = (counts[m.tileId] || 0) + 1;
      });

      const noisy = Object.keys(counts).filter(k => counts[k] >= 4);
      if (!noisy.length) return;

      moveWarnEl.style.display = "block";
      moveWarnEl.innerHTML =
        '<i class="fa-solid fa-triangle-exclamation"></i> ' +
        'High re-rotations detected: ' +
        noisy.map(cid => '<strong>Tile ' + cid + '</strong> (' + counts[cid] + '×)').join(", ");
    }

    function buildGrid(game, snap) {
      const dim = guessDim(game, snap);
      const major = Number((snap && snap.mini_dim) || (game && game.mini_dim) || 3);
      const gridDim = guessGridDim(game, snap);

      replay.dim = dim;
      replay.major = major;
      replay.gridDim = gridDim;

      replay.baseRot = {};
      replay.rotNow  = {};

      const initDeg = (snap.replay && snap.replay.initial_deg_by_index) ? snap.replay.initial_deg_by_index : null;

      for (let i = 1; i <= gridDim * gridDim; i++) {
        const deg = initDeg && initDeg[String(i)] != null ? Number(initDeg[String(i)]) : 0;
        replay.baseRot[i] = ((deg % 360) + 360) % 360;
        replay.rotNow[i]  = replay.baseRot[i];
      }

      // actual path
      const lv = (snap.last_validation && typeof snap.last_validation === "object") ? snap.last_validation : {};
      let actualPath = Array.isArray(lv.path) ? lv.path.map(Number) : [];
      actualPath = normalizePath(actualPath, dim);

      replay.actualPath = actualPath || [];
      replay.arrowMap = buildArrowMap(replay.actualPath, dim);

      const optRaw = safeJsonParse(
        game.optimal_path_json || game.solution_path_json || game.optimal_path || game.solution_path,
        null
      );
      replay.optimalPath = Array.isArray(optRaw) ? optRaw.map(Number).filter(Number.isFinite) : [];

      replay.moves = normalizeMoves(snap);
      replay.frames = buildFramesFromMoves(replay.moves);
      replay.currentStep = 0;

      // ✅ MASTER timeline length
      const pathSteps = Math.max(0, replay.actualPath.length - 1);
      const rotSteps  = Math.max(0, replay.frames.length - 1);
      replay.maxStep  = Math.max(pathSteps, rotSteps);

      if (gridEl) {
        gridEl.style.setProperty("--dim", String(dim));
        gridEl.innerHTML = "";

        const frag = document.createDocumentFragment();

        for (let id = 1; id <= dim * dim; id++) {
          const row = Math.floor((id - 1) / dim) + 1;
          const col = ((id - 1) % dim) + 1;

          const tileR = Math.floor((row - 1) / major);
          const tileC = Math.floor((col - 1) / major);
          const tileId = (tileR * gridDim) + tileC + 1;

          const cell = document.createElement("div");
          cell.className = "pr-cell";
          cell.dataset.cellId = String(id);
          cell.dataset.tileId = String(tileId);

          if (major > 1) {
            if (col > 1 && ((col - 1) % major === 0)) cell.classList.add("pr-major-left");
            if (row > 1 && ((row - 1) % major === 0)) cell.classList.add("pr-major-top");
          }

          cell.innerHTML = `
            <div class="pr-cell-inner">
              <div class="pr-glyph" style="transform:rotate(${Number(replay.baseRot[tileId] || 0)}deg)"></div>
              <span class="pr-cell-step"></span>
            </div>
          `;
          frag.appendChild(cell);
        }

        gridEl.appendChild(frag);
      }

      replay.ready = true;
      if (gridHintEl) gridHintEl.textContent = "Preview loaded. Use replay controls to see actions step-by-step.";

      if (seekEl) {
        seekEl.min = "0";
        seekEl.max = String(replay.maxStep);
        seekEl.value = "0";
      }

      applyStep(0);
      renderPaceChart(replay.moves);
      renderPathStats(game, snap);
      detectMoveAnomalies();
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
    }
    bindReplayControls();

    function renderMoves(snapshot) {
      questionListEl.innerHTML = "";

      const moves = replay.moves || [];
      const path = replay.actualPath || [];
      const landed = !!(snapshot.rocket_landed || snapshot.reached_earth || (snapshot.meta && snapshot.meta.reached_earth));

      if ((!moves || !moves.length) && (!path || !path.length)) {
        noQuestionsEl.classList.remove("d-none");
        questionListEl.classList.add("d-none");
        return;
      }

      noQuestionsEl.classList.add("d-none");
      questionListEl.classList.remove("d-none");

      const frag = document.createDocumentFragment();

      if (path && path.length) {
        const cardP = document.createElement("div");
        cardP.className = "pr-qcard";

        const endTxt = landed ? "EARTH" : String(path[path.length - 1]);

        cardP.innerHTML = `
          <div class="pr-qcard-head">
            <div class="pr-q-left">
              <span class="pr-q-badge">PATH</span>
            </div>
            <div class="pr-q-meta">
              <span>Cells: <strong>${path.length}</strong></span>
              <span>End: <strong>${endTxt}</strong></span>
            </div>
          </div>
          <div class="pr-q-question-main">Recorded numeric trail (from last_validation.path):</div>
          <div class="pr-qcard-answers">
            <div class="pr-q-answer-block correct">
              <div class="pr-q-answer-label">Sequence</div>
              <div class="pr-q-answer-text">${path.join(" → ")}</div>
            </div>
            <div class="pr-q-answer-block your">
              <div class="pr-q-answer-label">Start / End</div>
              <div class="pr-q-answer-text">${path[0]} → ${endTxt}</div>
            </div>
          </div>
        `;
        frag.appendChild(cardP);
      }

      if (moves && moves.length) {
        moves.forEach(m => {
          const cid = (m.tileId != null ? m.tileId : "—");
          const tms = (m.t_ms != null) ? m.t_ms : null;

          const before = (m.beforeDeg != null ? (m.beforeDeg + "°") : "—");
          const after  = (m.toRot != null ? (m.toRot + "°") : "—");
          const delta  = (m.delta != null ? (m.delta + "°") : "—");

          const dirTxt = (String(m.dir || "").toLowerCase() === "ccw") ? "CCW" : "CW";

          const card = document.createElement("div");
          card.className = "pr-qcard";
          card.innerHTML = `
            <div class="pr-qcard-head">
              <div class="pr-q-left">
                <span class="pr-q-badge">ACT ${m.index}</span>
              </div>
              <div class="pr-q-meta">
                <span>t(ms): <strong>${tms != null ? tms : "—"}</strong></span>
                <span>Dir: <strong>${dirTxt}</strong></span>
              </div>
            </div>

            <div class="pr-q-question-main">
              Rotation action on <strong>Tile ${cid}</strong>
            </div>

            <div class="pr-qcard-answers">
              <div class="pr-q-answer-block correct">
                <div class="pr-q-answer-label">Before → After</div>
                <div class="pr-q-answer-text">${before} → ${after}</div>
              </div>
              <div class="pr-q-answer-block your">
                <div class="pr-q-answer-label">Rotate by</div>
                <div class="pr-q-answer-text">${delta}</div>
              </div>
            </div>
          `;
          frag.appendChild(card);
        });
      }

      questionListEl.appendChild(frag);
    }

    function renderResult(json) {
      const game = (json && json.game) ? json.game : {};
      const result = (json && json.result) ? json.result : {};

      const gameTitle = game.title || game.game_title || game.name || "Path Game Result";
      gameTitleEl.textContent = gameTitle;

      const submittedAt = result.result_created_at || result.created_at || null;
      const attemptNo = result.attempt_no || "-";
      const status = result.status || "-";

      attemptMetaEl.textContent = "Attempt no " + attemptNo + " | Submitted at " + formatDateTime(submittedAt);

      const snap = safeJsonParse(result.user_answer || result.user_answer_json || {}, {});
      const dim = guessDim(game, snap);

      const lv = (snap.last_validation && typeof snap.last_validation === "object") ? snap.last_validation : {};
      let numericTrail = Array.isArray(lv.path) ? lv.path.map(Number).filter(Number.isFinite) : [];

      const earthReached = !!(
        snap.rocket_landed ||
        (snap.meta && snap.meta.reached_earth) ||
        (snap.replay_summary && snap.replay_summary.reached_earth)
      );

      numericTrail = normalizePath(numericTrail, dim);

      const timeTakenMs = Number(result.time_taken_ms || snap.submitted_at_ms || 0);
      replay.timeLimitMs = Number(game.time_limit_sec || snap?.game?.time_limit_sec || 0) * 1000;
      replay.timeTakenMs = timeTakenMs;

      const moves = normalizeMoves(snap);
      replay.moves = moves;
      replay.frames = buildFramesFromMoves(moves);
      replay.currentStep = 0;

      const rotationsCount =
        (snap.rotations_total != null ? Number(snap.rotations_total) :
        (Array.isArray(snap.rotation_log) ? snap.rotation_log.length : moves.length));

      const score = (result.score != null ? Number(result.score) : 0);

      let pct = 0;
      const st = String(status).toLowerCase();

      if (st === "win" || earthReached) pct = 100;
      else if (numericTrail && numericTrail.length) {
        const totalCells = dim * dim;
        pct = clampPct(Math.round((numericTrail.length / Math.max(1, totalCells)) * 100));
      }

      const chip = statusToChip(status);
      scoreChipEl.innerHTML = '<i class="fa-solid fa-' + chip.icon + '"></i> ' + chip.label;

      percentEl.textContent = pct + "%";
      marksEl.textContent = String(score);
      rotationsEl.textContent = String(rotationsCount);
      timeSpentEl.textContent = formatDurationMs(timeTakenMs);

      const lastVisited = (numericTrail && numericTrail.length) ? numericTrail[numericTrail.length - 1] : "-";

      startInfoEl.textContent = "Start: " + (numericTrail.length ? numericTrail[0] : "-");
      endInfoEl.textContent = earthReached ? "End: EARTH" : ("End: " + String(lastVisited));

      scoreTextEl.innerHTML =
        "Status: <strong>" + chip.label +
        "</strong>. Rotations: <strong>" + rotationsCount +
        "</strong>. Time: <strong>" + formatDurationMs(timeTakenMs) + "</strong>.";

      requestAnimationFrame(() => { scoreBarEl.style.width = pct + "%"; });
      barLabelEl.textContent = "Completion: " + pct + "%";

      timeoutInfoEl.textContent = "Timeout: " + (st === "timeout" ? "Yes" : "No");

      resultIdEl.textContent = result.result_uuid || result.result_id || "-";
      attemptNoEl.textContent = attemptNo;
      submittedAtEl.textContent = formatDateTime(submittedAt);

      replay.actualPath = numericTrail || [];
      replay.startId = replay.actualPath.length ? replay.actualPath[0] : null;
      replay.endId = replay.actualPath.length ? replay.actualPath[replay.actualPath.length - 1] : null;
      replay.arrowMap = buildArrowMap(replay.actualPath, dim);

      renderMoves(snap);

      try {
        buildGrid(game, snap);
      } catch (e) {
        console.warn("Grid analysis failed:", e);
        if (gridHintEl) gridHintEl.textContent = "Grid analysis unavailable for this attempt.";
      }
    }

    async function loadResult() {
      const token = getToken();
      if (!token) {
        clearAuthStorage();
        window.location.replace("/login");
        return;
      }

      showLoader(true);
      if (errorEl) { errorEl.classList.remove("show"); errorEl.textContent = ""; }

      try {
        const res = await fetch(DETAIL_API_PREFIX + encodeURIComponent(RESULT_ID), {
          method: "GET",
          headers: {
            "Accept": "application/json",
            "Authorization": "Bearer " + token
          }
        });

        let json;
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

    if (pdfBtn) pdfBtn.addEventListener("click", function () { window.print(); });

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
