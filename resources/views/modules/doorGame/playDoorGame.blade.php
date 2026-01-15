{{-- resources/views/modules/doorGame/exam.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Door Game</title>

  {{-- Bootstrap + FontAwesome + Common UI --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    /* =========================================================
      Door Game Exam UI (Scoped)
      - Professional layout (like your screenshot)
      - Barriers are RED
      - Moves tracked in ms (sessionStorage cache)
      - Submit sends cached move-log + summary
      - No global body/:root edits
    ========================================================= */

    .dgx-exam{
      --dgx-ink: #0f172a;
      --dgx-muted: #64748b;
      --dgx-card: var(--surface, #ffffff);
      --dgx-line: rgba(2,6,23,.12);
      --dgx-soft: rgba(2,6,23,.06);

      --dgx-brand: var(--primary-color, #5b5bd6);
      --dgx-brand2: var(--accent-color, #9b4dff);

      --dgx-danger: #ef4444;
      --dgx-success: #22c55e;

      --dgx-radius: 18px;
      --dgx-radius2: 26px;
      --dgx-shadow: 0 18px 45px rgba(2,6,23,.14);

      min-height: 100vh;
      display:flex;
      align-items: stretch;
      justify-content: center;
      padding: 18px 14px;
      background:
        radial-gradient(1000px 520px at 12% 8%, rgba(155,77,255,.16), transparent 60%),
        radial-gradient(1000px 600px at 88% 12%, rgba(91,91,214,.14), transparent 55%),
        linear-gradient(180deg, rgba(2,6,23,.02), rgba(2,6,23,.03));
      color: var(--dgx-ink);
    }

    html.theme-dark .dgx-exam{
      --dgx-card: #0f172a;
      --dgx-ink: #e5e7eb;
      --dgx-muted: #94a3b8;
      --dgx-line: rgba(148,163,184,.18);
      --dgx-soft: rgba(148,163,184,.10);
      background:
        radial-gradient(1000px 520px at 12% 8%, rgba(155,77,255,.14), transparent 60%),
        radial-gradient(1000px 600px at 88% 12%, rgba(91,91,214,.12), transparent 55%),
        linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.86));
    }

    .dgx-shell{
      width: 100%;
      max-width: 1560px;
      display:flex;
      flex-direction: column;
      gap: 14px;
    }

    .dgx-topbar{
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--dgx-brand) 70%, white) 0%,
        color-mix(in srgb, var(--dgx-brand2) 70%, white) 100%);
      border-radius: var(--dgx-radius2);
      box-shadow: var(--dgx-shadow);
      padding: 14px 16px;
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 12px;
      position: sticky;
      top: 10px;
      z-index: 40;
      color: #fff;
    }
    html.theme-dark .dgx-topbar{
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--dgx-brand) 60%, #0b1220) 0%,
        color-mix(in srgb, var(--dgx-brand2) 60%, #0b1220) 100%);
    }

    .dgx-title{ display:flex; flex-direction: column; gap: 4px; min-width: 0; }
    .dgx-title h1{
      font-size: clamp(16px, 1.25vw, 20px);
      line-height: 1.15;
      margin: 0;
      font-weight: 950;
      letter-spacing: .2px;
      white-space: nowrap;
      overflow:hidden;
      text-overflow: ellipsis;
      max-width: 62vw;
    }
    .dgx-title .sub{
      font-size: 12px;
      opacity: .95;
      display:flex;
      align-items:center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .dgx-pill{
      display:inline-flex;
      align-items:center;
      gap: 7px;
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      font-weight: 850;
      font-size: 12px;
      white-space: nowrap;
    }

    .dgx-actions{ display:flex; align-items:center; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
    .dgx-btn{
      border: 1px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.14);
      color: #fff;
      border-radius: 14px;
      padding: 10px 14px;
      display:inline-flex;
      align-items:center;
      gap: 9px;
      font-weight: 900;
      font-size: 13px;
      transition: .15s ease;
      text-decoration:none;
      user-select:none;
    }
    .dgx-btn:hover{ transform: translateY(-1px); background: rgba(255,255,255,.20); }
    .dgx-btn:active{ transform: translateY(0px); }
    .dgx-btn.danger{ background: rgba(239,68,68,.18); border-color: rgba(239,68,68,.28); }

    .dgx-grid{
      display:grid;
      grid-template-columns: 1fr 440px;
      gap: 14px;
      align-items: start;
    }
    @media (max-width: 1080px){
      .dgx-grid{ grid-template-columns: 1fr; }
      .dgx-title h1{ max-width: 92vw; }
    }

    .dgx-card{
      background: var(--dgx-card);
      border: 1px solid var(--dgx-line);
      border-radius: var(--dgx-radius2);
      box-shadow: 0 12px 30px rgba(2,6,23,.08);
      overflow: hidden;
    }
    html.theme-dark .dgx-card{ box-shadow: 0 14px 38px rgba(0,0,0,.45); }

    .dgx-card-hd{
      padding: 14px 16px;
      display:flex;
      align-items:flex-start;
      justify-content: space-between;
      gap: 14px;
      border-bottom: 1px solid var(--dgx-line);
      background: linear-gradient(180deg, rgba(2,6,23,.03), transparent);
    }

    .dgx-instr{ display:flex; flex-direction: column; gap: 6px; }
    .dgx-instr .kicker{
      font-size: 12px;
      font-weight: 950;
      letter-spacing: .55px;
      color: color-mix(in srgb, var(--dgx-brand) 70%, var(--dgx-ink));
      text-transform: uppercase;
    }
    .dgx-instr .text{
      font-size: 14px;
      color: var(--dgx-muted);
      font-weight: 800;
    }

    .dgx-timer{
      min-width: 230px;
      display:flex;
      flex-direction: column;
      gap: 8px;
      align-items: flex-end;
    }
    .dgx-timer .row1{
      display:flex;
      align-items:center;
      gap: 10px;
      font-weight: 950;
    }
    .dgx-timer .row1 .label{ font-size: 12px; color: var(--dgx-muted); font-weight: 950; }
    .dgx-timer .row1 .time{
      font-size: 14px;
      padding: 6px 11px;
      border-radius: 999px;
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      font-weight: 950;
    }
    html.theme-dark .dgx-timer .row1 .time{ background: rgba(148,163,184,.08); }

    .dgx-progress{
      width: 230px;
      height: 10px;
      border-radius: 999px;
      background: rgba(2,6,23,.08);
      overflow:hidden;
      border: 1px solid var(--dgx-line);
    }
    html.theme-dark .dgx-progress{ background: rgba(148,163,184,.10); }
    .dgx-progress > i{
      display:block;
      height: 100%;
      width: 100%;
      background: linear-gradient(90deg, #22c55e, #16a34a);
      border-radius: 999px;
      transition: width .35s ease;
    }

    .dgx-card-bd{ padding: 16px; }

    /* ===== Mission banner (like screenshot) ===== */
    .dgx-mission{
      border-radius: 20px;
      padding: 14px 16px;
      color: #fff;
      font-weight: 950;
      letter-spacing: .2px;
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--dgx-brand2) 76%, white) 0%,
        color-mix(in srgb, var(--dgx-brand) 76%, white) 100%);
      box-shadow: 0 14px 30px rgba(2,6,23,.10);
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }
    .dgx-mission .left{ display:flex; align-items:center; gap: 10px; }
    .dgx-mission .left i{ opacity:.95 }
    .dgx-mission .right{
      display:flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items:center;
      opacity:.95;
      font-size: 12px;
      font-weight: 900;
    }
    .dgx-tag{
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      display:inline-flex;
      align-items:center;
      gap: 8px;
      white-space: nowrap;
    }

    /* ===== Board ===== */
    .dgx-boardWrap{
      display:grid;
      grid-template-columns: 1fr 260px;
      gap: 14px;
      align-items: stretch;
    }
    @media (max-width: 900px){
      .dgx-boardWrap{ grid-template-columns: 1fr; }
    }

    .dgx-board{
      border-radius: 22px;
      background:
        radial-gradient(600px 260px at 20% 20%, rgba(155,77,255,.12), transparent 58%),
        radial-gradient(600px 260px at 80% 28%, rgba(91,91,214,.10), transparent 58%),
        color-mix(in srgb, var(--dgx-brand) 65%, white);
      padding: 14px;
      border: 1px solid rgba(255,255,255,.18);
      overflow:auto;
      min-height: 320px;
    }
    html.theme-dark .dgx-board{
      background:
        radial-gradient(600px 260px at 20% 20%, rgba(155,77,255,.14), transparent 58%),
        radial-gradient(600px 260px at 80% 28%, rgba(91,91,214,.12), transparent 58%),
        #0b1220;
      border-color: rgba(148,163,184,.18);
    }

    .dgx-gridBoard{
      --cell: 84px;
      display:grid;
      grid-template-columns: repeat(var(--n), var(--cell));
      grid-auto-rows: var(--cell);
      gap: 10px;
      justify-content: center;
      align-content: center;
      padding: 10px;
      background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.22);
      border-radius: 18px;
      backdrop-filter: blur(10px);
    }
    html.theme-dark .dgx-gridBoard{
      background: rgba(148,163,184,.08);
      border-color: rgba(148,163,184,.16);
    }

    .dgx-cell{
      position:relative;
      border-radius: 16px;
      background: #fff;
      border: 1px solid rgba(2,6,23,.12);
      box-shadow: 0 12px 28px rgba(2,6,23,.10);
      cursor: pointer;
      user-select: none;
      display:flex;
      align-items:center;
      justify-content:center;
      transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
      overflow: hidden;
    }
    html.theme-dark .dgx-cell{
      background: #0f172a;
      border-color: rgba(148,163,184,.22);
      box-shadow: 0 18px 44px rgba(0,0,0,.55);
    }
    .dgx-cell:hover{
      transform: translateY(-2px);
      border-color: color-mix(in srgb, var(--dgx-brand2) 45%, rgba(2,6,23,.12));
      box-shadow: 0 16px 34px rgba(2,6,23,.14);
    }
    .dgx-cell:active{ transform: translateY(-1px) scale(.99); }

    .dgx-idx{
      position:absolute;
      top: 8px; left: 10px;
      font-size: 11px;
      font-weight: 950;
      color: #8b97a8;
      opacity: .9;
      pointer-events:none;
    }
    html.theme-dark .dgx-idx{ color:#94a3b8; }

    .dgx-ico{
      font-size: 26px;
      color: color-mix(in srgb, var(--dgx-brand2) 80%, #0f172a);
      filter: drop-shadow(0 10px 16px rgba(2,6,23,.16));
      pointer-events:none;
    }
    .dgx-ico.key{ color: #f59e0b; }
    .dgx-ico.door{ color: #10b981; }
    .dgx-ico.user{ color: color-mix(in srgb, var(--dgx-brand2) 75%, #0f172a); }

    /* current position highlight */
    .dgx-cell.is-current{
      outline: 3px solid color-mix(in srgb, var(--dgx-brand2) 38%, transparent);
      border-color: color-mix(in srgb, var(--dgx-brand2) 50%, rgba(2,6,23,.12));
    }

    /* reachable neighbor hint */
    .dgx-cell.is-next{
      outline: 2px dashed color-mix(in srgb, #22c55e 40%, transparent);
      border-color: color-mix(in srgb, #22c55e 45%, rgba(2,6,23,.12));
    }

    /* ===== Barriers (RED) — drawn via pseudo elements inside each cell =====
       We render canonical edges to avoid duplicates:
       - always render TOP and LEFT when true
       - render BOTTOM only for last row
       - render RIGHT only for last col
    */
    .dgx-bar{ position:absolute; background: var(--dgx-danger); border-radius:999px; opacity:.95; pointer-events:none; display:none; }
    .dgx-bar.top{ height: 5px; left: 14px; right: 14px; top: 9px; }
    .dgx-bar.bottom{ height: 5px; left: 14px; right: 14px; bottom: 9px; }
    .dgx-bar.left{ width: 5px; top: 14px; bottom: 14px; left: 9px; }
    .dgx-bar.right{ width: 5px; top: 14px; bottom: 14px; right: 9px; }

    .dgx-cell.b-top .dgx-bar.top{display:block}
    .dgx-cell.b-bottom .dgx-bar.bottom{display:block}
    .dgx-cell.b-left .dgx-bar.left{display:block}
    .dgx-cell.b-right .dgx-bar.right{display:block}

    /* ===== Sidebar ===== */
    .dgx-side{
      position: sticky;
      top: 86px;
      display:flex;
      flex-direction: column;
      gap: 14px;
    }
    @media (max-width: 1080px){
      .dgx-side{ position: static; }
    }

    .dgx-panel{
      padding: 14px 16px;
      display:flex;
      flex-direction: column;
      gap: 10px;
    }
    .dgx-panel .hd{
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
    }
    .dgx-panel .hd .t{
      font-weight: 950;
      font-size: 14px;
      display:flex;
      align-items:center;
      gap: 10px;
    }

    .dgx-mini{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .dgx-stat{
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      border-radius: 16px;
      padding: 10px 12px;
      display:flex;
      flex-direction: column;
      gap: 4px;
    }
    html.theme-dark .dgx-stat{ background: rgba(148,163,184,.08); }
    .dgx-stat .k{ color: var(--dgx-muted); font-size: 12px; font-weight: 900; }
    .dgx-stat .v{ font-weight: 950; font-size: 16px; }

    .dgx-actions2{
      display:flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items:center;
      justify-content: space-between;
    }
    .dgx-btn2{
      border-radius: 14px;
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      padding: 10px 14px;
      font-weight: 950;
      font-size: 13px;
      display:inline-flex;
      align-items:center;
      gap: 9px;
      transition: .15s ease;
      user-select:none;
    }
    html.theme-dark .dgx-btn2{ background: rgba(148,163,184,.08); }
    .dgx-btn2:hover{ transform: translateY(-1px); }
    .dgx-btn2:active{ transform: translateY(0px); }
    .dgx-btn2.primary{
      background: linear-gradient(135deg, var(--dgx-brand), var(--dgx-brand2));
      color:#fff;
      border-color: rgba(255,255,255,.08);
    }
    .dgx-btn2.danger{
      background: rgba(239,68,68,.10);
      border-color: rgba(239,68,68,.20);
      color: #ef4444;
    }
    .dgx-btn2:disabled{ opacity: .55; cursor: not-allowed; transform: none !important; }

    .dgx-note{
      color: var(--dgx-muted);
      font-size: 12px;
      font-weight: 850;
      line-height: 1.45;
    }

    .dgx-loader{
      padding: 26px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap: 10px;
      color: var(--dgx-muted);
      font-weight: 950;
    }

    /* subtle key collected badge */
    .dgx-keypill{
      display:inline-flex;
      align-items:center;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      font-weight: 950;
      font-size: 12px;
      white-space: nowrap;
    }
    html.theme-dark .dgx-keypill{ background: rgba(148,163,184,.08); }

    .dgx-footnote{
      text-align:center;
      color: var(--dgx-muted);
      font-size: 12px;
      font-weight: 850;
      padding: 2px 0 10px;
    }
  </style>
</head>

<body>
<div class="dgx-exam" id="dgxExam">
  <div class="dgx-shell">

    {{-- Topbar --}}
    <div class="dgx-topbar">
      <div class="dgx-title">
        <h1 id="dgxGameTitle">Loading door game…</h1>
        <div class="sub">
          <span class="dgx-pill"><i class="fa-solid fa-border-all"></i> <span id="dgxDim">--×--</span></span>
          <span class="dgx-pill"><i class="fa-solid fa-key"></i> Keys: <span id="dgxKeysNeed">--</span></span>
          <span class="dgx-pill"><i class="fa-solid fa-database"></i> Auto-saved</span>
        </div>
      </div>

      <div class="dgx-actions">
        <a class="dgx-btn" href="/dashboard" id="dgxQuitBtn"><i class="fa-solid fa-house"></i> Dashboard</a>
        <button class="dgx-btn danger" id="dgxResetBtn" type="button" style="display:none">
          <i class="fa-solid fa-rotate-left"></i> Reset Attempt
        </button>
      </div>
    </div>

    {{-- Main --}}
    <div class="dgx-grid">

      {{-- Left: Board Card --}}
      <div class="dgx-card">
        <div class="dgx-card-hd">
          <div class="dgx-instr">
            <div class="kicker">Instructions</div>
            <div class="text" id="dgxInstruction">Loading…</div>
          </div>

          <div class="dgx-timer">
            <div class="row1">
              <span class="label">Time Left</span>
              <span class="time"><span id="dgxTimeLeft">--</span>s</span>
            </div>
            <div class="dgx-progress" aria-label="timer progress">
              <i id="dgxTimeBar" style="width:100%"></i>
            </div>
          </div>
        </div>

        <div class="dgx-card-bd">
          <div class="dgx-mission">
            <div class="left">
              <i class="fa-solid fa-bullseye"></i>
              <span id="dgxMissionText">Collect KEY → Reach DOOR</span>
            </div>
            <div class="right">
              <span class="dgx-tag"><i class="fa-solid fa-person-walking"></i> Click adjacent cell to move</span>
              <span class="dgx-tag"><i class="fa-solid fa-bars-staggered"></i> Red lines are barriers</span>
            </div>
          </div>

          <div class="dgx-boardWrap">
            <div class="dgx-board">
              <div id="dgxBoard" class="dgx-gridBoard" style="--n:3">
                <div class="dgx-loader">
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  Loading board…
                </div>
              </div>
            </div>

            <div class="dgx-card" style="border-radius:22px">
              <div class="dgx-panel">
                <div class="hd">
                  <div class="t"><i class="fa-solid fa-gauge-high"></i> Status</div>
                  <span class="dgx-keypill" id="dgxRunState"><i class="fa-solid fa-circle-play"></i> Ready</span>
                </div>

                <div class="dgx-mini">
                  <div class="dgx-stat">
                    <div class="k">Moves</div>
                    <div class="v" id="dgxMoves">0</div>
                  </div>
                  <div class="dgx-stat">
                    <div class="k">Collected</div>
                    <div class="v"><span id="dgxKeysGot">0</span>/<span id="dgxKeysTotal">0</span></div>
                  </div>
                  <div class="dgx-stat">
                    <div class="k">Time (ms)</div>
                    <div class="v" id="dgxTimeMs">0</div>
                  </div>
                  <div class="dgx-stat">
                    <div class="k">Result</div>
                    <div class="v" id="dgxResult">—</div>
                  </div>
                </div>

                <div class="dgx-actions2">
                  <button class="dgx-btn2 d-none" id="dgxHintBtn" type="button">
                    <i class="fa-solid fa-lightbulb"></i> Hint
                  </button>
                  <button class="dgx-btn2 primary" id="dgxSubmitBtn" type="button" disabled>
                    <i class="fa-solid fa-paper-plane"></i> Submit
                  </button>
                </div>

                <div class="dgx-note">
                  Moves are cached in sessionStorage with millisecond timing. On submit, the full move-log + summary is saved.
                  Use arrow keys too (optional): ↑ ↓ ← →.
                </div>
              </div>
            </div>
          </div>

          <div class="dgx-footnote">
            Tip: You can refresh — your attempt stays in sessionStorage until Submit.
          </div>
        </div>
      </div>

      {{-- Right: Info Panel --}}
      <div class="dgx-side">
        <div class="dgx-card">
          <div class="dgx-panel">
            <div class="hd">
              <div class="t"><i class="fa-solid fa-map-location-dot"></i> Legend</div>
              <span class="dgx-keypill"><i class="fa-solid fa-shield-halved"></i> Barriers</span>
            </div>

            <div class="dgx-note" style="display:flex; flex-direction:column; gap:10px">
              <div><i class="fa-solid fa-user me-2" style="color:color-mix(in srgb, var(--dgx-brand2) 75%, #0f172a)"></i> Player</div>
              <div><i class="fa-solid fa-key me-2" style="color:#f59e0b"></i> Key (collect all)</div>
              <div><i class="fa-solid fa-door-open me-2" style="color:#10b981"></i> Door (finish)</div>
              <div><span class="badge" style="background:#ef4444">—</span> Red lines block movement</div>
            </div>
          </div>
        </div>

        <div class="dgx-card">
          <div class="dgx-panel">
            <div class="hd">
              <div class="t"><i class="fa-solid fa-clipboard-check"></i> Attempt</div>
              <span class="dgx-keypill" id="dgxAttemptNo"><i class="fa-solid fa-hashtag"></i> 1</span>
            </div>
            <div class="dgx-note" id="dgxAttemptNote">
              Collect all keys, then reach the door before time runs out.
            </div>
          </div>
        </div>

        <div class="dgx-card d-none">
          <div class="dgx-panel">
            <div class="hd">
              <div class="t"><i class="fa-solid fa-wand-magic-sparkles"></i> Quick Actions</div>
              <span class="dgx-keypill"><i class="fa-solid fa-keyboard"></i> Keyboard</span>
            </div>
            <div class="dgx-actions2">
              <button class="dgx-btn2 danger" id="dgxGiveUpBtn" type="button">
                <i class="fa-solid fa-flag"></i> Give Up
              </button>
              <button class="dgx-btn2" id="dgxCenterBtn" type="button">
                <i class="fa-solid fa-crosshairs"></i> Center
              </button>
            </div>
            <div class="dgx-note">
              Give Up will mark as fail and enable submit (still saves your move-log).
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(() => {
  /* =========================================================
    Door Game Play Script (UPDATED)
    ✅ Aligns with submit API validation:
      user_answer_json = {
        grid_dim, start_index, path, moves, events, timing{time_taken_ms}
      }
    ✅ Fixes timing: move.t_ms is RELATIVE (ms since start)
    ✅ Tracks one-time key + door events
    ✅ Keeps your UI/logic intact (no breaking changes)
  ========================================================= */

  function getGameUuidFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return (urlParams.get('game') || urlParams.get('game_uuid') || urlParams.get('uuid') || '').trim();
  }

  const GAME_UUID = getGameUuidFromUrl();
  const DASHBOARD_URL = '/dashboard';

  const API = {
    game:   `/api/door-games/${encodeURIComponent(GAME_UUID)}`,
    submit: `/api/door-games-results/submit/${encodeURIComponent(GAME_UUID)}`,
  };

  const CACHE_KEY = `dg_exam_${GAME_UUID}`;

  // Swal toast
  const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 2600,
    timerProgressBar: true,
  });
  const notify = (icon, title, text='') => Toast.fire({ icon, title: title || '', text: text || '' });

  // DOM
  const elGameTitle   = document.getElementById('dgxGameTitle');
  const elDim         = document.getElementById('dgxDim');
  const elKeysNeed    = document.getElementById('dgxKeysNeed');

  const elInstruction = document.getElementById('dgxInstruction');
  const elTimeLeft    = document.getElementById('dgxTimeLeft');
  const elTimeBar     = document.getElementById('dgxTimeBar');

  const elBoard       = document.getElementById('dgxBoard');

  const elRunState    = document.getElementById('dgxRunState');
  const elMoves       = document.getElementById('dgxMoves');
  const elKeysGot     = document.getElementById('dgxKeysGot');
  const elKeysTotal   = document.getElementById('dgxKeysTotal');
  const elTimeMs      = document.getElementById('dgxTimeMs');
  const elResult      = document.getElementById('dgxResult');

  const elSubmitBtn   = document.getElementById('dgxSubmitBtn');
  const elHintBtn     = document.getElementById('dgxHintBtn');
  const elResetBtn    = document.getElementById('dgxResetBtn');
  const elQuitBtn     = document.getElementById('dgxQuitBtn');
  const elGiveUpBtn   = document.getElementById('dgxGiveUpBtn');
  const elCenterBtn   = document.getElementById('dgxCenterBtn');

  // state
  let state = {
    game: null,
    N: 3,
    cells: [], // normalized cells {id,row,col,is_user,is_key,is_door,barriers:{t,b,l,r}}
    userId: null,      // cell id
    doorId: null,      // cell id
    keys: new Set(),   // all key cell ids
    keysCollected: new Set(),

    // ✅ one-time events
    keyEvent: null,    // { picked_at_index, t_ms }
    doorEvent: null,   // { opened_at_index, t_ms }

    status: 'in_progress', // in_progress | win | fail | timeout
    moves: [],         // move events
    startedAtMs: 0,    // absolute perf ms
    lastMoveAtMs: 0,   // absolute perf ms
    timeLimitSec: 30,
    tick: null,
    timeLeft: 30,
    suppressUnloadPrompt: false,
    isSubmitting: false
  };

  function getToken() {
    return localStorage.getItem('token') || sessionStorage.getItem('token');
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function nowMs(){
    return Math.round(performance.now());
  }

  function pad2(n){ return String(n).padStart(2,'0'); }
  function toSqlDatetime(d){
    return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())} ${pad2(d.getHours())}:${pad2(d.getMinutes())}:${pad2(d.getSeconds())}`;
  }

  function saveCache(){
    const payload = {
      game: state.game,
      N: state.N,
      cells: state.cells,
      userId: state.userId,
      doorId: state.doorId,
      keys: Array.from(state.keys),
      keysCollected: Array.from(state.keysCollected),

      keyEvent: state.keyEvent,
      doorEvent: state.doorEvent,

      status: state.status,
      moves: state.moves,
      startedAtMs: state.startedAtMs,
      lastMoveAtMs: state.lastMoveAtMs,
      timeLimitSec: state.timeLimitSec,
      timeLeft: state.timeLeft,
      savedAt: Date.now()
    };
    sessionStorage.setItem(CACHE_KEY, JSON.stringify(payload));
  }

  function loadCache(){
    try{
      const raw = sessionStorage.getItem(CACHE_KEY);
      if(!raw) return false;
      const p = JSON.parse(raw);
      if(!p || !p.cells || !Array.isArray(p.cells) || !p.cells.length) return false;

      state.game = p.game || null;
      state.N = Number(p.N || 3);
      state.cells = p.cells || [];
      state.userId = p.userId || null;
      state.doorId = p.doorId || null;
      state.keys = new Set(Array.isArray(p.keys) ? p.keys : []);
      state.keysCollected = new Set(Array.isArray(p.keysCollected) ? p.keysCollected : []);

      state.keyEvent = p.keyEvent || null;
      state.doorEvent = p.doorEvent || null;

      state.status = p.status || 'in_progress';
      state.moves = Array.isArray(p.moves) ? p.moves : [];
      state.startedAtMs = Number(p.startedAtMs || 0);
      state.lastMoveAtMs = Number(p.lastMoveAtMs || 0);
      state.timeLimitSec = Number(p.timeLimitSec || 30);

      const savedAt = Number(p.savedAt || Date.now());
      const elapsedSec = Math.floor((Date.now() - savedAt) / 1000);
      const tl = Number(p.timeLeft ?? state.timeLimitSec);
      state.timeLeft = Math.max(0, tl - elapsedSec);

      return true;
    }catch(e){
      return false;
    }
  }

  function clearCache(){
    sessionStorage.removeItem(CACHE_KEY);
  }

  async function fetchJson(url){
    const token = getToken();
    const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch(url, { method:'GET', headers });

    let json = {};
    try { json = await res.json(); } catch(e) { json = {}; }

    if (res.status === 401 || res.status === 419) throw new Error('Session expired. Please login again.');
    if (!res.ok || json.success === false) throw new Error(json.message || `Request failed (${res.status})`);

    if (json && json.success === true && json.data !== undefined) return json.data;
    return json.data !== undefined ? json.data : json;
  }

  function stopTick(){
    if(state.tick){
      clearInterval(state.tick);
      state.tick = null;
    }
  }

  function renderTimer(){
    elTimeLeft.textContent = String(state.timeLeft);
    const pct = state.timeLimitSec > 0 ? (state.timeLeft / state.timeLimitSec) * 100 : 0;
    elTimeBar.style.width = `${Math.max(0, Math.min(100, pct))}%`;
  }

  function startTimer(){
  stopTick();
  renderTimer();

  state.tick = setInterval(() => {
    if(state.status !== 'in_progress') return;

    state.timeLeft = Math.max(0, Number(state.timeLeft || 0) - 1);
    renderTimer();
    saveCache();

    if(state.timeLeft <= 0){
      state.status = 'timeout';
      setRunState('Timeout', 'danger');
      elResult.textContent = 'Timeout';
      stopTick();

      // ✅ Auto-submit once, then redirect (submitAttempt already redirects)
      if(!state.isSubmitting){
        notify('warning','Time up!', 'Auto-submitting…');
        submitAttempt(true);   // ✅ no await needed
      }
    }
  }, 1000);
}

  function setRunState(text, tone){
    const icon = tone === 'success'
      ? 'fa-circle-check'
      : tone === 'danger'
        ? 'fa-circle-xmark'
        : tone === 'primary'
          ? 'fa-circle-play'
          : 'fa-circle-dot';

    elRunState.innerHTML = `<i class="fa-solid ${icon}"></i> ${escapeHtml(text)}`;
  }

  function idToRC(id){
    const i = id - 1;
    return { r: Math.floor(i / state.N), c: i % state.N };
  }
  function rcToId(r,c){ return r*state.N + c + 1; }

  function getCell(id){
    return state.cells.find(x => Number(x.id) === Number(id)) || null;
  }

  function isAdjacent(aId, bId){
    const a = idToRC(aId);
    const b = idToRC(bId);
    const dr = Math.abs(a.r - b.r);
    const dc = Math.abs(a.c - b.c);
    return (dr + dc) === 1;
  }

  function canMove(fromId, toId){
    if(!isAdjacent(fromId,toId)) return false;

    const from = getCell(fromId);
    const to = getCell(toId);
    if(!from || !to) return false;

    const a = idToRC(fromId);
    const b = idToRC(toId);

    if(b.r === a.r - 1 && b.c === a.c) return !(from.barriers?.top || to.barriers?.bottom);
    if(b.r === a.r + 1 && b.c === a.c) return !(from.barriers?.bottom || to.barriers?.top);
    if(b.c === a.c - 1 && b.r === a.r) return !(from.barriers?.left || to.barriers?.right);
    if(b.c === a.c + 1 && b.r === a.r) return !(from.barriers?.right || to.barriers?.left);
    return false;
  }

  function updateSidebar(){
    elMoves.textContent = String(state.moves.length);
    elKeysGot.textContent = String(state.keysCollected.size);
    elKeysTotal.textContent = String(state.keys.size);
    const elapsed = state.startedAtMs ? Math.max(0, nowMs() - state.startedAtMs) : 0;
    elTimeMs.textContent = String(elapsed);
    elKeysNeed.textContent = String(state.keys.size);
  }

  function shouldRenderBarrier(cell, edge){
    if(edge === 'top') return !!cell.barriers?.top;
    if(edge === 'left') return !!cell.barriers?.left;
    if(edge === 'bottom') return (cell.row === state.N-1) && !!cell.barriers?.bottom;
    if(edge === 'right')  return (cell.col === state.N-1) && !!cell.barriers?.right;
    return false;
  }

  function markReachableHints(){
    const userId = state.userId;
    state.cells.forEach(c => {
      const el = document.querySelector(`.dgx-cell[data-id="${c.id}"]`);
      if(!el) return;
      el.classList.remove('is-next');
      if(state.status !== 'in_progress') return;
      if(Number(c.id) === Number(userId)) return;
      if(isAdjacent(userId, c.id) && canMove(userId, c.id)){
        el.classList.add('is-next');
      }
    });
  }

  function renderBoard(){
    elBoard.style.setProperty('--n', String(state.N));
    elBoard.innerHTML = '';

    state.cells.forEach(cell => {
      const div = document.createElement('div');
      div.className = 'dgx-cell';
      div.dataset.id = String(cell.id);
      div.setAttribute('role','button');
      div.setAttribute('tabindex','0');

      if(Number(cell.id) === Number(state.userId)) div.classList.add('is-current');

      if(shouldRenderBarrier(cell,'top')) div.classList.add('b-top');
      if(shouldRenderBarrier(cell,'bottom')) div.classList.add('b-bottom');
      if(shouldRenderBarrier(cell,'left')) div.classList.add('b-left');
      if(shouldRenderBarrier(cell,'right')) div.classList.add('b-right');

      const idx = document.createElement('div');
      idx.className = 'dgx-idx';
      idx.textContent = String(cell.id);

      const ico = document.createElement('div');
      ico.className = 'dgx-ico';

      if(Number(cell.id) === Number(state.userId)){
        ico.classList.add('user');
        ico.innerHTML = '<i class="fa-solid fa-user"></i>';
      }else if(Number(cell.id) === Number(state.doorId)){
        ico.classList.add('door');
        ico.innerHTML = '<i class="fa-solid fa-door-open"></i>';
      }else if(state.keys.has(cell.id) && !state.keysCollected.has(cell.id)){
        ico.classList.add('key');
        ico.innerHTML = '<i class="fa-solid fa-key"></i>';
      }else{
        ico.innerHTML = '';
      }

      const bTop = document.createElement('span'); bTop.className = 'dgx-bar top';
      const bBottom = document.createElement('span'); bBottom.className = 'dgx-bar bottom';
      const bLeft = document.createElement('span'); bLeft.className = 'dgx-bar left';
      const bRight = document.createElement('span'); bRight.className = 'dgx-bar right';

      div.appendChild(idx);
      div.appendChild(ico);
      div.appendChild(bTop);
      div.appendChild(bBottom);
      div.appendChild(bLeft);
      div.appendChild(bRight);

      div.addEventListener('click', () => onCellClick(cell.id));
      div.addEventListener('keydown', (e) => {
        if(e.key === 'Enter' || e.key === ' '){
          e.preventDefault();
          onCellClick(cell.id);
        }
      });

      elBoard.appendChild(div);
    });

    markReachableHints();
  }

  // ✅ UPDATED: t_ms is RELATIVE since start (submit API friendly)
  function recordMove(fromId, toId, meta = {}){
    const tAbs = nowMs();

    if(!state.startedAtMs){
      state.startedAtMs = tAbs;
      state.lastMoveAtMs = tAbs;
    }

    const tRel = Math.max(0, tAbs - state.startedAtMs);
    const dt   = Math.max(0, tAbs - (state.lastMoveAtMs || tAbs));
    state.lastMoveAtMs = tAbs;

    state.moves.push({
      from: Number(fromId),
      to: Number(toId),
      t_ms: Number(tRel), // ✅ required format
      dt_ms: Number(dt),  // extra (ok)
      ...meta
    });

    saveCache();
  }

  function onCellClick(targetId){
    if(state.status !== 'in_progress') return;
    if(!state.userId) return;

    const fromId = state.userId;
    const toId = Number(targetId);

    if(fromId === toId) return;

    if(!isAdjacent(fromId,toId)){
      notify('info','Move', 'Click an adjacent cell.');
      return;
    }

    if(!canMove(fromId,toId)){
      notify('error','Blocked', 'A barrier blocks this move.');
      return;
    }

    state.userId = toId;

    // key pickup
    let pickedKey = false;
    if(state.keys.has(toId) && !state.keysCollected.has(toId)){
      state.keysCollected.add(toId);
      pickedKey = true;

      // ✅ one-time key event (your rule: only one key allowed)
      if(!state.keyEvent){
        const tRel = state.startedAtMs ? Math.max(0, nowMs() - state.startedAtMs) : 0;
        state.keyEvent = { picked_at_index: Number(toId), t_ms: Number(tRel) };
      }

      notify('success','Key collected', `${state.keysCollected.size}/${state.keys.size}`);
    }

    recordMove(fromId, toId, {
      action: 'move',
      picked_key: pickedKey ? 'yes' : 'no',
      keys_collected: state.keysCollected.size
    });

    // win check
    if(state.keysCollected.size === state.keys.size && Number(toId) === Number(state.doorId)){
      state.status = 'win';

      // ✅ one-time door event
      if(!state.doorEvent){
        const tRel = state.startedAtMs ? Math.max(0, nowMs() - state.startedAtMs) : 0;
        state.doorEvent = { opened_at_index: Number(toId), t_ms: Number(tRel) };
      }

      setRunState('Completed', 'success');
      elResult.textContent = 'Win';
      elSubmitBtn.disabled = false;
      stopTick();
      notify('success','You win!', 'All keys collected and door reached.');
    }else{
      setRunState('Playing', 'primary');
      elResult.textContent = '—';
    }

    updateSidebar();
    renderBoard();
  }

  function moveByArrow(dir){
    if(state.status !== 'in_progress') return;
    const { r, c } = idToRC(state.userId);
    let nr=r, nc=c;
    if(dir==='up') nr = r-1;
    if(dir==='down') nr = r+1;
    if(dir==='left') nc = c-1;
    if(dir==='right') nc = c+1;
    if(nr<0 || nc<0 || nr>=state.N || nc>=state.N) return;
    onCellClick(rcToId(nr,nc));
  }

  function findShortestPath(startId, goalId){
    const q = [];
    const prev = new Map();
    q.push(startId);
    prev.set(startId, null);

    while(q.length){
      const cur = q.shift();
      if(cur === goalId) break;

      const { r, c } = idToRC(cur);
      const neighbors = [];
      if(r>0) neighbors.push(rcToId(r-1,c));
      if(r<state.N-1) neighbors.push(rcToId(r+1,c));
      if(c>0) neighbors.push(rcToId(r,c-1));
      if(c<state.N-1) neighbors.push(rcToId(r,c+1));

      for(const nb of neighbors){
        if(prev.has(nb)) continue;
        if(!canMove(cur, nb)) continue;
        prev.set(nb, cur);
        q.push(nb);
      }
    }

    if(!prev.has(goalId)) return [];
    const path = [];
    let cur = goalId;
    while(cur != null){
      path.push(cur);
      cur = prev.get(cur);
    }
    path.reverse();
    return path;
  }

  function flashHint(path){
    if(!Array.isArray(path) || path.length < 2){
      notify('warning','Hint', 'No route found (barriers block).');
      return;
    }
    const els = path.map(id => document.querySelector(`.dgx-cell[data-id="${id}"]`)).filter(Boolean);
    els.forEach(el => {
      el.style.transition = 'outline-color .15s ease, box-shadow .15s ease';
      el.style.outline = '3px solid rgba(34,197,94,.55)';
      el.style.boxShadow = '0 0 0 6px rgba(34,197,94,.16)';
    });
    setTimeout(() => {
      els.forEach(el => {
        el.style.outline = '';
        el.style.boxShadow = '';
      });
    }, 900);
  }

  async function postJson(url, payload){
    const token = getToken();
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
    };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch(url, { method: 'POST', headers, body: JSON.stringify(payload) });

    let json = {};
    try { json = await res.json(); } catch(e) { json = {}; }

    if (res.status === 401 || res.status === 419) throw new Error('Session expired. Please login again.');
    if (!res.ok || json.success === false) throw new Error(json.message || `Submit failed (${res.status})`);

    return json;
  }

  // ✅ UPDATED submit: builds API-aligned user_answer_json
  async function submitAttempt(isAuto=false){
    if(state.isSubmitting) return;
    state.isSubmitting = true;

    try{
      elSubmitBtn.disabled = true;

      const endAbsMs = nowMs();
      const startedAbs = state.startedAtMs || endAbsMs;
      const timeTakenMs = Math.max(0, endAbsMs - startedAbs);

      // start_index: first move "from" else current user
      const startIndex = Number(
        (state.moves.length ? state.moves[0]?.from : state.userId) || state.userId || 1
      );

      // path: [start, ...to...]
      const path = [startIndex];
      state.moves.forEach(m => path.push(Number(m.to)));

      const moves = state.moves.map(m => ({
        from: Number(m.from),
        to: Number(m.to),
        t_ms: Number(m.t_ms ?? 0)
      }));

      const timing = {
        started_at: toSqlDatetime(new Date(Date.now() - timeTakenMs)),
        finished_at: toSqlDatetime(new Date()),
        time_taken_ms: Number(timeTakenMs) // ✅ required
      };

      const events = {};
      if(state.keyEvent){
        events.key = {
          picked_at_index: Number(state.keyEvent.picked_at_index),
          t_ms: Number(state.keyEvent.t_ms ?? 0)
        };
      }
      if(state.doorEvent){
        events.door = {
          opened_at_index: Number(state.doorEvent.opened_at_index),
          t_ms: Number(state.doorEvent.t_ms ?? 0)
        };
      }

      const user_answer_json = {
        grid_dim: Number(state.N),
        start_index: Number(startIndex),
        path: path,
        moves: moves,
        events: events,
        timing: timing
      };

      const score = (state.status === 'win') ? 100 : 0;

      const payload = {
        game_uuid: GAME_UUID,
        door_game_uuid: GAME_UUID,
        status: state.status,
        score: Number(score),
        time_taken_ms: Number(timeTakenMs),
        user_answer_json: user_answer_json
      };

      Swal.fire({
        title: isAuto ? 'Submitting…' : 'Submitting…',
        text: 'Please wait',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
      });

      await postJson(API.submit, payload);

      Swal.close();
      clearCache();
      notify('success','Submitted successfully','Redirecting…');
      setTimeout(() => window.location.href = DASHBOARD_URL, 900);

    }catch(err){
      Swal.close();
      elSubmitBtn.disabled = false;
      await Swal.fire({ icon:'error', title:'Submit failed', text: err.message || 'Please try again' });

      if((err.message || '').toLowerCase().includes('login')){
        setTimeout(() => window.location.href = '/login', 900);
      }
    }finally{
      state.isSubmitting = false;
    }
  }

  function beforeUnloadHandler(e){
    if(state.suppressUnloadPrompt === true) return;
    const has = (state.moves && state.moves.length > 0) || (state.keysCollected && state.keysCollected.size > 0);
    if(!has) return;
    e.preventDefault();
    e.returnValue = '';
  }

  function normalizeGrid(rawGrid, N){
    const out = [];
    const arr = Array.isArray(rawGrid) ? rawGrid : [];
    for(let i=0;i<N*N;i++){
      const r = Math.floor(i / N);
      const c = i % N;

      const p = arr[i] || {};
      out.push({
        id: Number(p.id || (i+1)),
        row: r,
        col: c,
        is_user: !!p.is_user,
        is_key: !!p.is_key,
        is_door: !!p.is_door,
        barriers: {
          top: !!(p.barriers?.top),
          bottom: !!(p.barriers?.bottom),
          left: !!(p.barriers?.left),
          right: !!(p.barriers?.right)
        }
      });
    }
    return out;
  }

  function hydrateFromGame(game){
    state.game = game || {};
    state.N = Number(game?.grid_dim || 3);
    state.timeLimitSec = Number(game?.time_limit_sec || 30);
    state.timeLeft = state.timeLimitSec;

    elGameTitle.textContent = game?.title ? String(game.title) : 'Door Game';
    elDim.textContent = `${state.N}×${state.N}`;

    const instr = (game?.instructions_html || game?.description || '').toString().trim();
    elInstruction.textContent = instr ? instr.replace(/<[^>]*>?/gm, '').slice(0, 220) : 'Collect all keys, then reach the door. Barriers block movement.';

    let grid = game?.grid_json || null;
    try{
      if(typeof grid === 'string') grid = JSON.parse(grid);
    }catch(e){
      grid = null;
    }
    if(!Array.isArray(grid) || grid.length !== state.N*state.N){
      throw new Error('Invalid grid_json in game.');
    }

    state.cells = normalizeGrid(grid, state.N);

    state.userId = null;
    state.doorId = null;
    state.keys = new Set();
    state.keysCollected = new Set();

    state.keyEvent = null;
    state.doorEvent = null;

    state.moves = [];
    state.startedAtMs = 0;
    state.lastMoveAtMs = 0;
    state.status = 'in_progress';

    state.cells.forEach(c => {
      if(c.is_user) state.userId = c.id;
      if(c.is_door) state.doorId = c.id;
      if(c.is_key) state.keys.add(c.id);
    });

    if(!state.userId) throw new Error('User start cell missing in grid.');
    if(!state.doorId) throw new Error('Door cell missing in grid.');

    elKeysNeed.textContent = String(state.keys.size);
    elKeysTotal.textContent = String(state.keys.size);
    elKeysGot.textContent = '0';
    elMoves.textContent = '0';
    elResult.textContent = '—';
    elTimeMs.textContent = '0';
    setRunState('Ready', 'muted');
    elSubmitBtn.disabled = true;

    renderTimer();
    renderBoard();
    updateSidebar();
    saveCache();
  }

  async function init(){
    window.addEventListener('beforeunload', beforeUnloadHandler);

    if(!GAME_UUID){
      elBoard.innerHTML = `
        <div class="dgx-loader">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Game UUID missing. Open with <b>?game=&lt;uuid&gt;</b>
        </div>
      `;
      elInstruction.textContent = 'Cannot start without game uuid.';
      await Swal.fire({ icon:'error', title:'Game UUID missing', text:'Use URL like /door-games/exam?game=<uuid>' });
      return;
    }

    const restored = loadCache();
    if(restored){
      elResetBtn.style.display = '';
      elGameTitle.textContent = state.game?.title ? String(state.game.title) : 'Door Game';
      elDim.textContent = `${state.N}×${state.N}`;
      elKeysNeed.textContent = String(state.keys.size);
      elKeysTotal.textContent = String(state.keys.size);
      elKeysGot.textContent = String(state.keysCollected.size);

      setRunState(
        state.status === 'in_progress' ? 'Playing' : (state.status === 'win' ? 'Completed' : 'Stopped'),
        state.status === 'win' ? 'success' : (state.status === 'in_progress' ? 'primary' : 'danger')
      );

      elResult.textContent =
        (state.status === 'win') ? 'Win' :
        (state.status === 'timeout') ? 'Timeout' :
        (state.status === 'fail') ? 'Fail' : '—';

      elSubmitBtn.disabled = (state.status === 'in_progress');
      renderTimer();
      renderBoard();
      updateSidebar();
      if(state.status === 'in_progress') startTimer();
      notify('success','Attempt restored','Loaded from sessionStorage.');
      return;
    }

    try{
      const game = await fetchJson(API.game);
      hydrateFromGame(game);
      elResetBtn.style.display = '';
      setRunState('Playing', 'primary');
      startTimer();
      notify('success','Game loaded','Good luck!');
    }catch(err){
      elBoard.innerHTML = `
        <div class="dgx-loader">
          <i class="fa-solid fa-triangle-exclamation"></i>
          ${escapeHtml(err.message || 'Failed to load door game')}
        </div>
      `;
      elInstruction.textContent = 'Failed to load.';
      await Swal.fire({ icon:'error', title:'Failed to load', text: err.message || '' });

      if((err.message || '').toLowerCase().includes('login')){
        setTimeout(() => window.location.href = '/login', 900);
      }
    }
  }

  /* ================= Events ================= */

  document.addEventListener('keydown', (e) => {
    if(state.status !== 'in_progress') return;
    if(['INPUT','TEXTAREA'].includes((e.target?.tagName || '').toUpperCase())) return;

    if(e.key === 'ArrowUp') { e.preventDefault(); moveByArrow('up'); }
    if(e.key === 'ArrowDown') { e.preventDefault(); moveByArrow('down'); }
    if(e.key === 'ArrowLeft') { e.preventDefault(); moveByArrow('left'); }
    if(e.key === 'ArrowRight') { e.preventDefault(); moveByArrow('right'); }
  });

  elHintBtn.addEventListener('click', async () => {
    if(!state.userId || !state.doorId) return;

    let target = state.doorId;
    if(state.keysCollected.size < state.keys.size){
      const remaining = Array.from(state.keys).filter(k => !state.keysCollected.has(k));
      let best = [];
      for(const k of remaining){
        const p = findShortestPath(state.userId, k);
        if(p.length && (!best.length || p.length < best.length)) best = p;
      }
      if(best.length){
        flashHint(best);
        notify('info','Hint','Highlighted a shortest route to a key.');
        return;
      }
      notify('warning','Hint','No route to any key found.');
      return;
    }

    const p = findShortestPath(state.userId, target);
    flashHint(p);
    notify('info','Hint','Highlighted a shortest route to the door.');
  });

  elGiveUpBtn.addEventListener('click', async () => {
    if(state.status !== 'in_progress') return;

    const r = await Swal.fire({
      icon: 'warning',
      title: 'Give up?',
      text: 'This will mark your attempt as FAIL (move-log will still be saved).',
      showCancelButton: true,
      confirmButtonText: 'Yes, give up',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#ef4444'
    });
    if(!r.isConfirmed) return;

    state.status = 'fail';
    stopTick();
    setRunState('Stopped', 'danger');
    elResult.textContent = 'Fail';
    elSubmitBtn.disabled = false;

    // record final event
    state.moves.push({ t_ms: 0, dt_ms: 0, action: 'give_up' }); // harmless extra
    saveCache();
    updateSidebar();
    notify('warning','Attempt ended','Marked as fail. You can submit now.');
  });

  elCenterBtn.addEventListener('click', () => {
    const cur = document.querySelector(`.dgx-cell.is-current`);
    if(cur) cur.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
  });

  elResetBtn.addEventListener('click', async () => {
    const r = await Swal.fire({
      icon: 'warning',
      title: 'Reset attempt?',
      text: 'This will clear locally saved progress for this game.',
      showCancelButton: true,
      confirmButtonText: 'Yes, reset',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#ef4444'
    });
    if(!r.isConfirmed) return;

    clearCache();
    location.reload();
  });

  elQuitBtn.addEventListener('click', async (e) => {
    const has = (state.moves && state.moves.length > 0) || (state.keysCollected && state.keysCollected.size > 0);
    if(!has) return;

    e.preventDefault();
    const r = await Swal.fire({
      icon: 'question',
      title: 'Leave game?',
      text: 'Your attempt is saved locally until you Submit.',
      showCancelButton: true,
      confirmButtonText: 'Leave',
      cancelButtonText: 'Stay',
    });
    if(r.isConfirmed) window.location.href = DASHBOARD_URL;
  });

  elSubmitBtn.addEventListener('click', async () => {
    if(state.status === 'in_progress'){
      const r = await Swal.fire({
        icon: 'question',
        title: 'Submit now?',
        text: 'You are still in progress. Submit will save current move-log.',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#22c55e'
      });
      if(!r.isConfirmed) return;
    }else{
      const r = await Swal.fire({
        icon: 'question',
        title: 'Submit attempt?',
        text: 'This will save to database and finish your attempt.',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#22c55e'
      });
      if(!r.isConfirmed) return;
    }

    await submitAttempt(false);
  });

  /* ============ init ============ */
  init();

})();
</script>

</body>
</html>
