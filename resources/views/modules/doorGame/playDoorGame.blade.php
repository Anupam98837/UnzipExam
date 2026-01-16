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
      Door Game Exam UI (Scoped) — UPDATED (Professional Game Feel)
      ✅ Changes applied:
        - Arrows are around the USER (not board edges)
        - Barriers are NOT shown (logic still blocks)
        - Arrow turns RED + disabled if barrier/boundary OR target cell already visited
        - Visited cells highlighted
        - Player after key: PURPLE (not multicolor)
        - Door icon color: BLACK (with visibility outline)
        - More game-like effects: glow/pulse, shake on blocked, sparkle on key, win confetti burst, smooth camera follow, subtle click sfx
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
      --dgx-warn: #f59e0b;

      --dgx-purple: #a855f7; /* ✅ player powered purple */
      --dgx-black: #0b0f18;  /* ✅ door black */

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
      overflow-x:hidden;
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

    .dgx-shell{ width:100%; max-width:1560px; display:flex; flex-direction:column; gap:14px; }

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

    .dgx-title{ display:flex; flex-direction:column; gap:4px; min-width:0; }
    .dgx-title h1{
      font-size: clamp(16px, 1.25vw, 20px);
      line-height: 1.15; margin:0; font-weight:950; letter-spacing:.2px;
      white-space: nowrap; overflow:hidden; text-overflow: ellipsis; max-width: 62vw;
    }
    .dgx-title .sub{ font-size:12px; opacity:.95; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

    .dgx-pill{
      display:inline-flex; align-items:center; gap:7px;
      padding:7px 10px; border-radius:999px;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      font-weight: 850; font-size:12px; white-space:nowrap;
    }

    .dgx-actions{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
    .dgx-btn{
      border: 1px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.14);
      color:#fff;
      border-radius: 14px;
      padding: 10px 14px;
      display:inline-flex; align-items:center; gap:9px;
      font-weight: 900; font-size:13px;
      transition: .15s ease;
      text-decoration:none; user-select:none;
    }
    .dgx-btn:hover{ transform: translateY(-1px); background: rgba(255,255,255,.20); }
    .dgx-btn:active{ transform: translateY(0px); }
    .dgx-btn.danger{ background: rgba(239,68,68,.18); border-color: rgba(239,68,68,.28); }

    .dgx-grid{ display:grid; grid-template-columns: 1fr 440px; gap:14px; align-items:start; }
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
      display:flex; align-items:flex-start; justify-content:space-between; gap:14px;
      border-bottom: 1px solid var(--dgx-line);
      background: linear-gradient(180deg, rgba(2,6,23,.03), transparent);
    }

    .dgx-instr{ display:flex; flex-direction:column; gap:6px; }
    .dgx-instr .kicker{
      font-size:12px; font-weight:950; letter-spacing:.55px;
      color: color-mix(in srgb, var(--dgx-brand) 70%, var(--dgx-ink));
      text-transform: uppercase;
    }
    .dgx-instr .text{ font-size:14px; color: var(--dgx-muted); font-weight:800; }

    .dgx-timer{ min-width:230px; display:flex; flex-direction:column; gap:8px; align-items:flex-end; }
    .dgx-timer .row1{ display:flex; align-items:center; gap:10px; font-weight:950; }
    .dgx-timer .row1 .label{ font-size:12px; color: var(--dgx-muted); font-weight:950; }
    .dgx-timer .row1 .time{
      font-size:14px; padding:6px 11px; border-radius:999px;
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      font-weight:950;
    }
    html.theme-dark .dgx-timer .row1 .time{ background: rgba(148,163,184,.08); }

    .dgx-progress{
      width:230px; height:10px; border-radius:999px;
      background: rgba(2,6,23,.08);
      overflow:hidden;
      border: 1px solid var(--dgx-line);
    }
    html.theme-dark .dgx-progress{ background: rgba(148,163,184,.10); }
    .dgx-progress > i{
      display:block; height:100%; width:100%;
      background: linear-gradient(90deg, #22c55e, #16a34a);
      border-radius: 999px;
      transition: width .35s ease;
    }

    .dgx-card-bd{ padding:16px; }

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
      display:flex; align-items:center; justify-content:space-between;
      gap: 10px; flex-wrap: wrap;
      margin-bottom: 12px;
      position:relative;
      overflow:hidden;
    }
    .dgx-mission:after{
      content:"";
      position:absolute; inset:-40px;
      background: radial-gradient(closest-side, rgba(255,255,255,.14), transparent 65%);
      animation: dgxGlow 3.2s ease-in-out infinite;
      pointer-events:none;
    }
    @keyframes dgxGlow{
      0%{ transform: translate(-8%, -6%) scale(1); opacity:.75; }
      50%{ transform: translate(8%, 6%) scale(1.05); opacity:1; }
      100%{ transform: translate(-8%, -6%) scale(1); opacity:.75; }
    }
    .dgx-mission .left{ display:flex; align-items:center; gap:10px; position:relative; z-index:1; }
    .dgx-mission .left i{ opacity:.95 }
    .dgx-mission .right{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; opacity:.95; font-size:12px; font-weight:900; position:relative; z-index:1; }
    .dgx-tag{
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      display:inline-flex; align-items:center; gap:8px; white-space:nowrap;
    }

    /* ===== Board ===== */
    .dgx-boardWrap{ display:grid; grid-template-columns: 1fr 260px; gap:14px; align-items:stretch; }
    @media (max-width: 900px){ .dgx-boardWrap{ grid-template-columns: 1fr; } }

    .dgx-board{
      position: relative;
      border-radius: 22px;
      background:
        radial-gradient(600px 260px at 20% 20%, rgba(155,77,255,.12), transparent 58%),
        radial-gradient(600px 260px at 80% 28%, rgba(91,91,214,.10), transparent 58%),
        color-mix(in srgb, var(--dgx-brand) 65%, white);
      padding: 14px;
      border: 1px solid rgba(255,255,255,.18);
      overflow:auto;
      min-height: 360px;
      scroll-behavior:smooth;
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
      z-index: 1;
      grid-template-columns: repeat(var(--n), var(--cell));
      grid-auto-rows: var(--cell);
      gap: 10px;
      justify-content: center;
      align-content: center;
      padding: 30px 30px; /* tighter because arrows are around user tile now */
      background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.22);
      border-radius: 18px;
      backdrop-filter: blur(10px);
      position: relative;
    }
    html.theme-dark .dgx-gridBoard{
      background: rgba(148,163,184,.08);
      border-color: rgba(148,163,184,.16);
    }

    .dgx-cell{
      position:relative;
      z-index:1;
      border-radius: 16px;
      background: #fff;
      border: 1px solid rgba(2,6,23,.12);
      box-shadow: 0 12px 28px rgba(2,6,23,.10);
      cursor: pointer;
      user-select: none;
      display:flex;
      align-items:center;
      justify-content:center;
      transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease, background .12s ease;
      overflow: visible; /* allow arrows to sit around user tile */
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
      position:absolute; top:8px; left:10px;
      font-size: 11px; font-weight:950; color:#8b97a8; opacity:.9;
      pointer-events:none;
    }
    html.theme-dark .dgx-idx{ color:#94a3b8; }

    .dgx-ico{
      font-size: 26px;
      filter: drop-shadow(0 10px 16px rgba(2,6,23,.16));
      pointer-events:none;
      z-index: 2;
    }

    /* ✅ Player default */
    .dgx-ico.user{
      color: color-mix(in srgb, var(--dgx-ink) 78%, #000);
      opacity: .92;
    }
    html.theme-dark .dgx-ico.user{ color: #cbd5e1; opacity:.95; }

    /* ✅ Player after key: PURPLE (professional glow) */
    .dgx-playerPowered .dgx-ico.user{
      /* color: var(--dgx-purple); */
      opacity: 1;
      /* filter:
        drop-shadow(0 16px 22px color-mix(in srgb, var(--dgx-purple) 35%, transparent))
        drop-shadow(0 10px 16px rgba(2,6,23,.18)); */
    }

    .dgx-ico.key{ color: #f59e0b; filter: drop-shadow(0 14px 18px rgba(245,158,11,.22)); }

    /* ✅ Door color BLACK (with outline for visibility) */
    .dgx-ico.doorClosed,
    .dgx-ico.doorOpen{
color: #8B5A2B !important; /* brown */      text-shadow:
        0 0 0 rgba(0,0,0,0),
        0 1px 0 rgba(255,255,255,.45),
        0 0 12px rgba(255,255,255,.20);
      filter: drop-shadow(0 14px 18px rgba(2,6,23,.22));
    }
    html.theme-dark .dgx-ico.doorClosed,
    html.theme-dark .dgx-ico.doorOpen{
      color: #000;
      text-shadow:
        0 0 0 rgba(0,0,0,0),
        0 1px 0 rgba(255,255,255,.65),
        0 0 14px rgba(255,255,255,.22);
    }

    /* ✅ current position pulse */
    .dgx-cell.is-current{
  outline: 3px solid color-mix(in srgb, var(--dgx-brand2) 38%, transparent);
  border-color: color-mix(in srgb, var(--dgx-brand2) 50%, rgba(2,6,23,.12));
  box-shadow: 0 16px 34px rgba(2,6,23,.16);
  animation: dgxPulse 1.35s ease-in-out infinite;

  /* ✅ CRITICAL: keep current tile above all others (so arrows don't hide) */
  z-index: 999 !important;

  /* ✅ CRITICAL: allow arrows to overflow without clipping */
  overflow: visible !important;

  /* ✅ stabilize stacking / */
  transform: translateZ(0);
}

    @keyframes dgxPulse{
      0%{ transform: translateY(0); }
      50%{ transform: translateY(-1px); }
      100%{ transform: translateY(0); }
    }
/* ✅ Do NOT move the current cell on hover/active
   because arrows are positioned relative to it */
.dgx-cell.is-current:hover,
.dgx-cell.is-current:active{
  transform: none !important;
}

    /* ✅ reachable neighbor */
    .dgx-cell.is-next{
      outline: 2px dashed color-mix(in srgb, #22c55e 40%, transparent);
      border-color: color-mix(in srgb, #22c55e 45%, rgba(2,6,23,.12));
    }

    /* ✅ visited path highlighting */
    /* ✅ visited path highlighting (WHITE tint) */
/* ✅ visited cells: white card + dashed inset border (like sample) */
.dgx-cell.is-visited{
  background: #ffffff !important;
  border: 2px solid rgba(255,255,255,.98) !important;

  /* dashed inset ring */
  outline: 2px dashed rgba(45, 212, 191, .95) !important; /* teal */
  outline-offset: -8px !important;

  box-shadow: 0 10px 22px rgba(2,6,23,.10) !important;
}

html.theme-dark .dgx-cell.is-visited{
  background: rgba(255,255,255,.10) !important;
  border: 2px solid rgba(255,255,255,.20) !important;
  outline: 2px dashed rgba(45, 212, 191, .85) !important;
  outline-offset: -8px !important;
}



    /* ===== Barriers (HIDDEN visuals, logic still works) ===== */
    .dgx-bar{ display:none !important; }

    /* ===== User-surrounding arrows (inside user cell) ===== */
    .dgx-uArrows{
      position:absolute;
      inset:0;
      z-index: 1025;
      pointer-events:none;
        transform: translateZ(0);

    }
    .dgx-uArrow{
  pointer-events:auto;
  position:absolute;
  width: 24px;
  height: 24px;
  border-radius: 14px;
  display:flex;
  align-items:center;
  justify-content:center;

  border: 1px solid rgba(255,255,255,.22);
  background: rgba(15,23,42,.55);
  color: #fff;

  z-index:1160;
  backdrop-filter: blur(10px);
  box-shadow: 0 12px 26px rgba(2,6,23,.18);
  transition: background .12s ease, opacity .12s ease, filter .12s ease, transform .12s ease;
  user-select:none;

  /* ✅ direction transform base (never overwritten) */
  --tx: 0px;
  --ty: 0px;
  transform: translate3d(var(--tx), var(--ty), 0);
}
html.theme-dark .dgx-uArrow{
  background: rgba(148,163,184,.14);
  border-color: rgba(148,163,184,.22);
  color:#e5e7eb;
  box-shadow: 0 16px 34px rgba(0,0,0,.55);
}

/* ✅ Direction anchors (use vars so disabled never breaks alignment) */
.dgx-uArrow.up{    top:-18px; left:50%;   --tx:-50%; --ty:0px; }
.dgx-uArrow.down{  bottom:-18px; left:50%;--tx:-50%; --ty:0px; }
.dgx-uArrow.left{  left:-18px; top:50%;   --tx:0px;  --ty:-50%; }
.dgx-uArrow.right{ right:-18px; top:50%;  --tx:0px;  --ty:-50%; }
    /* ✅ Hover/active KEEP direction transform */
.dgx-uArrow:hover{
  transform: translate3d(var(--tx), calc(var(--ty) - 1px), 0);
}
.dgx-uArrow:active{
  transform: translate3d(var(--tx), var(--ty), 0) scale(.98);
}
    /* blocked or visited => RED */
    .dgx-uArrow.blocked{
      background: rgba(239,68,68,.70);
      border-color: rgba(239,68,68,.35);
      color:#fff;
    }
    /* disabled */
   .dgx-uArrow.disabled{
  opacity: .48;
  cursor:not-allowed;
  box-shadow:none;
  filter: saturate(.95);
}
.dgx-uArrow.disabled:hover,
.dgx-uArrow.disabled:active{
  transform: translate3d(var(--tx), var(--ty), 0);
}

    /* ===== FX: shake + sparkle + win confetti ===== */
    .dgx-cell.fx-shake{ animation: dgxShake .35s ease; }
    @keyframes dgxShake{
      0%{ transform: translateX(0); }
      20%{ transform: translateX(-3px); }
      40%{ transform: translateX(3px); }
      60%{ transform: translateX(-2px); }
      80%{ transform: translateX(2px); }
      100%{ transform: translateX(0); }
    }

    .dgx-floatTxt{
      position:absolute;
      left:50%; top:50%;
      transform: translate(-50%,-50%);
      font-size:12px;
      font-weight:950;
      padding:6px 10px;
      border-radius: 999px;
      background: rgba(2,6,23,.76);
      color:#fff;
      border: 1px solid rgba(255,255,255,.18);
      pointer-events:none;
      z-index: 80;
      animation: dgxFloat .8s ease forwards;
      white-space:nowrap;
    }
    @keyframes dgxFloat{
      0%{ opacity:0; transform: translate(-50%,-40%) scale(.98); }
      20%{ opacity:1; transform: translate(-50%,-55%) scale(1); }
      100%{ opacity:0; transform: translate(-50%,-88%) scale(1.02); }
    }

    .dgx-spark{
      position:absolute;
      left:50%; top:50%;
      width:8px; height:8px;
      border-radius:999px;
      background: #fff;
      transform: translate(-50%,-50%);
      opacity: 0;
      pointer-events:none;
      z-index: 70;
    }

    .dgx-confettiLayer{
      position:absolute;
      inset:0;
      pointer-events:none;
      z-index: 90;
      overflow: visible;
    }

    /* ===== Sidebar ===== */
    .dgx-side{
      position: sticky;
      top: 86px;
      display:flex;
      flex-direction: column;
      gap: 14px;
    }
    @media (max-width: 1080px){ .dgx-side{ position: static; } }

    .dgx-panel{ padding:14px 16px; display:flex; flex-direction:column; gap:10px; }
    .dgx-panel .hd{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .dgx-panel .hd .t{ font-weight:950; font-size:14px; display:flex; align-items:center; gap:10px; }

    .dgx-mini{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    .dgx-stat{
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      border-radius: 16px;
      padding: 10px 12px;
      display:flex; flex-direction:column; gap:4px;
    }
    html.theme-dark .dgx-stat{ background: rgba(148,163,184,.08); }
    .dgx-stat .k{ color: var(--dgx-muted); font-size: 12px; font-weight: 900; }
    .dgx-stat .v{ font-weight: 950; font-size: 16px; }

    .dgx-actions2{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between; }
    .dgx-btn2{
      border-radius: 14px;
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      padding: 10px 14px;
      font-weight: 950;
      font-size: 13px;
      display:inline-flex; align-items:center; gap:9px;
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
    .dgx-btn2.danger{ background: rgba(239,68,68,.10); border-color: rgba(239,68,68,.20); color:#ef4444; }
    .dgx-btn2:disabled{ opacity:.55; cursor:not-allowed; transform:none !important; }

    .dgx-note{ color: var(--dgx-muted); font-size: 12px; font-weight: 850; line-height: 1.45; }
    .dgx-loader{ padding: 26px; display:flex; align-items:center; justify-content:center; gap:10px; color: var(--dgx-muted); font-weight: 950; }

    .dgx-keypill{
      display:inline-flex; align-items:center; gap:8px;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid var(--dgx-line);
      background: rgba(2,6,23,.03);
      font-weight: 950;
      font-size: 12px;
      white-space: nowrap;
    }
    html.theme-dark .dgx-keypill{ background: rgba(148,163,184,.08); }

    .dgx-footnote{ text-align:center; color: var(--dgx-muted); font-size: 12px; font-weight: 850; padding: 2px 0 10px; }
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
              <span class="dgx-tag"><i class="fa-solid fa-hand-pointer"></i> Tap cell or arrows</span>
              <span class="dgx-tag"><i class="fa-solid fa-keyboard"></i> Use ↑ ↓ ← →</span>
            </div>
          </div>

          <div class="dgx-boardWrap">
            <div class="dgx-board" id="dgxBoardWrap">
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
              <span class="dgx-keypill"><i class="fa-solid fa-gamepad"></i> Controls</span>
            </div>

            <div class="dgx-note" style="display:flex; flex-direction:column; gap:10px">
              <div><i class="fa-solid fa-user me-2"></i> Player</div>
              <div><i class="fa-solid fa-key me-2" style="color:#f59e0b"></i> Key (collect all)</div>
              <div><i class="fa-solid fa-door-closed me-2" style="color:#000"></i> Door (black)</div>
              <div><span class="badge" style="background:#ef4444">!</span> Red arrow = blocked / visited</div>
              <div><span class="badge" style="background:#a855f7">•</span> Visited path highlight</div>
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
              Collect all keys, then reach the door before time runs out. Barriers are hidden but still block movement.
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
    Door Game Play Script — UPDATED
      - User-surround arrows
      - Hidden barriers (logic blocks)
      - Arrow red+disabled for barrier/boundary OR visited target
      - No moving into visited cells (arrows + click)
      - Purple powered player
      - Door always black
      - Game FX: shake, sparkles, confetti, camera follow, subtle sfx
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
  const elBoardWrap   = document.getElementById('dgxBoardWrap');

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
    cells: [],
    userId: null,
    doorId: null,
    keys: new Set(),
    keysCollected: new Set(),

    visited: new Set(),

    keyEvent: null,
    doorEvent: null,

    doorUnlocked: false,

    status: 'in_progress', // in_progress | win | fail | timeout
    moves: [],
    startedAtMs: 0,
    lastMoveAtMs: 0,
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

  function nowMs(){ return Math.round(performance.now()); }

  // ===== Tiny SFX (no files, just WebAudio) =====
  let audioCtx = null;
  function sfx(freq=440, dur=0.06, type='sine', gain=0.035){
    try{
      if(!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const o = audioCtx.createOscillator();
      const g = audioCtx.createGain();
      o.type = type;
      o.frequency.value = freq;
      g.gain.value = gain;
      o.connect(g); g.connect(audioCtx.destination);
      o.start();
      o.stop(audioCtx.currentTime + dur);
    }catch(e){}
  }

  function vibrate(ms=20){
    try{ if(navigator.vibrate) navigator.vibrate(ms); }catch(e){}
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
      visited: Array.from(state.visited),

      keyEvent: state.keyEvent,
      doorEvent: state.doorEvent,
      doorUnlocked: !!state.doorUnlocked,

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
      state.visited = new Set(Array.isArray(p.visited) ? p.visited : []);

      state.keyEvent = p.keyEvent || null;
      state.doorEvent = p.doorEvent || null;
      state.doorUnlocked = !!p.doorUnlocked;

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

  function clearCache(){ sessionStorage.removeItem(CACHE_KEY); }

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

        if(!state.isSubmitting){
          notify('warning','Time up!', 'Auto-submitting…');
          submitAttempt(true);
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

  // ✅ direction helpers (for arrow logic)
  function nextIdByDir(fromId, dir){
    const { r, c } = idToRC(fromId);
    if(dir==='up')    return (r>0) ? rcToId(r-1,c) : null;
    if(dir==='down')  return (r<state.N-1) ? rcToId(r+1,c) : null;
    if(dir==='left')  return (c>0) ? rcToId(r,c-1) : null;
    if(dir==='right') return (c<state.N-1) ? rcToId(r,c+1) : null;
    return null;
  }

  function dirBlocked(fromId, dir){
    const toId = nextIdByDir(fromId, dir);
    if(!toId) return true; // boundary
    return !canMove(fromId, toId); // barrier or boundary
  }

  function updateSidebar(){
    elMoves.textContent = String(state.moves.length);
    elKeysGot.textContent = String(state.keysCollected.size);
    elKeysTotal.textContent = String(state.keys.size);
    const elapsed = state.startedAtMs ? Math.max(0, nowMs() - state.startedAtMs) : 0;
    elTimeMs.textContent = String(elapsed);
    elKeysNeed.textContent = String(state.keys.size);
  }

  function markReachableHints(){
    const userId = state.userId;
    state.cells.forEach(c => {
      const el = document.querySelector(`.dgx-cell[data-id="${c.id}"]`);
      if(!el) return;
      el.classList.remove('is-next');
      if(state.status !== 'in_progress') return;
      if(Number(c.id) === Number(userId)) return;

      // ✅ also don't hint already visited targets
      // if(state.visited.has(Number(c.id))) return;

      if(isAdjacent(userId, c.id) && canMove(userId, c.id)){
        el.classList.add('is-next');
      }
    });
  }

  // ===== FX helpers =====
  function floatText(cellEl, text){
    if(!cellEl) return;
    const t = document.createElement('div');
    t.className = 'dgx-floatTxt';
    t.textContent = String(text || '');
    cellEl.appendChild(t);
    setTimeout(()=> t.remove(), 900);
  }

  function shakeCell(cellEl){
    if(!cellEl) return;
    cellEl.classList.remove('fx-shake');
    void cellEl.offsetWidth;
    cellEl.classList.add('fx-shake');
    setTimeout(()=> cellEl.classList.remove('fx-shake'), 420);
  }

  function sparkle(cellEl, color='#fff'){
    if(!cellEl) return;
    const count = 12;
    for(let i=0;i<count;i++){
      const p = document.createElement('span');
      p.className = 'dgx-spark';
      p.style.background = color;
      cellEl.appendChild(p);

      const ang = Math.random() * Math.PI * 2;
      const dist = 18 + Math.random() * 22;
      const dx = Math.cos(ang) * dist;
      const dy = Math.sin(ang) * dist;

      const dur = 420 + Math.random() * 800;
      p.animate([
        { transform:'translate(-50%,-50%) scale(.7)', opacity:0 },
        { transform:'translate(-50%,-50%) scale(1)', opacity:1, offset:0.15 },
        { transform:`translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) scale(.9)`, opacity:0 }
      ], { duration: dur, easing:'cubic-bezier(.2,.8,.2,1)', fill:'forwards' });

      setTimeout(()=> p.remove(), dur + 120);
    }
  }

  function confettiBurst(anchorEl){
    const layer = document.createElement('div');
    layer.className = 'dgx-confettiLayer';
    (anchorEl || elBoardWrap).appendChild(layer);

    const colors = ['#a855f7','#60a5fa','#22c55e','#f59e0b','#ef4444'];
    const count = 42;

    for(let i=0;i<count;i++){
      const d = document.createElement('span');
      d.style.position='absolute';
      d.style.left='50%';
      d.style.top='50%';
      d.style.width = (6 + Math.random()*8) + 'px';
      d.style.height = (6 + Math.random()*10) + 'px';
      d.style.borderRadius = (Math.random()>.5 ? '999px' : '6px');
      d.style.background = colors[i % colors.length];
      d.style.opacity = '1';
      d.style.transform='translate(-50%,-50%)';
      layer.appendChild(d);

      const ang = Math.random()*Math.PI*2;
      const dist = 90 + Math.random()*140;
      const dx = Math.cos(ang)*dist;
      const dy = Math.sin(ang)*dist;
      const rot = (Math.random()*720 - 360);
      const dur = 700 + Math.random()*900;

      d.animate([
        { transform:'translate(-50%,-50%) rotate(0deg)', opacity:1 },
        { transform:`translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) rotate(${rot}deg)`, opacity:0 }
      ], { duration: dur, easing:'cubic-bezier(.15,.8,.2,1)', fill:'forwards' });

      setTimeout(()=> d.remove(), dur + 80);
    }

    setTimeout(()=> layer.remove(), 3600);
  }

  function centerOnCurrent(){
    const cur = document.querySelector('.dgx-cell.is-current');
    if(cur) cur.scrollIntoView({ behavior:'smooth', block:'center', inline:'center' });
  }

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
      t_ms: Number(tRel),
      dt_ms: Number(dt),
      ...meta
    });

    saveCache();
  }

  function lockAfterWin(){
    stopTick();
    state.status = 'win';
    setRunState('Completed', 'success');
    elResult.textContent = 'Win';
    elSubmitBtn.disabled = false;
    markReachableHints();
    saveCache();
  }

  function onBlocked(reason='Blocked'){
    const cellEl = document.querySelector(`.dgx-cell[data-id="${state.userId}"]`);
    shakeCell(cellEl);
    floatText(cellEl, reason);
    sfx(160, 0.06, 'square', 0.03);
    vibrate(18);
  }

  function onCellClick(targetId){
    if(state.status !== 'in_progress') return;
    if(!state.userId) return;

    const fromId = state.userId;
    const toId = Number(targetId);

    if(fromId === toId) return;

    if(!isAdjacent(fromId,toId)){
      onBlocked('Adjacent only');
      return;
    }

    // ✅ do not allow moving into visited tiles
    // if(state.visited.has(toId)){
    //   onBlocked('Visited');
    //   return;
    // }

    if(!canMove(fromId,toId)){
      onBlocked('Blocked');
      return;
    }

    // mark visited
    // state.visited.add(Number(fromId));
    state.visited.add(Number(toId));

    state.userId = toId;

    // key pickup
    let pickedKey = false;
    if(state.keys.has(toId) && !state.keysCollected.has(toId)){
      state.keysCollected.add(toId);
      pickedKey = true;

      if(!state.keyEvent){
        const tRel = state.startedAtMs ? Math.max(0, nowMs() - state.startedAtMs) : 0;
        state.keyEvent = { picked_at_index: Number(toId), t_ms: Number(tRel) };
      }

      const keyEl = document.querySelector(`.dgx-cell[data-id="${toId}"]`);
      sparkle(keyEl, '#f59e0b');
      floatText(keyEl, '+KEY');
      sfx(740, 0.07, 'triangle', 0.03);
      notify('success','Key collected', `${state.keysCollected.size}/${state.keys.size}`);
    }else{
      sfx(520, 0.04, 'sine', 0.02);
    }

    recordMove(fromId, toId, {
      action: 'move',
      picked_key: pickedKey ? 'yes' : 'no',
      keys_collected: state.keysCollected.size
    });

    setRunState('Playing', 'primary');
    elResult.textContent = '—';

    updateSidebar();
    renderBoard();
    centerOnCurrent();

    // ✅ WIN check: keys collected AND reached door
    if(state.keysCollected.size === state.keys.size && Number(toId) === Number(state.doorId)){
      if(!state.doorEvent){
        const tRel = state.startedAtMs ? Math.max(0, nowMs() - state.startedAtMs) : 0;
        state.doorEvent = { opened_at_index: Number(toId), t_ms: Number(tRel) };
      }

      state.doorUnlocked = true;

      // render first then effects
      updateSidebar();
      renderBoard();

      const doorEl = document.querySelector(`.dgx-cell[data-id="${state.doorId}"]`);
      sparkle(doorEl, '#a855f7');
      confettiBurst(doorEl);
      sfx(980, 0.08, 'sine', 0.03);
      sfx(620, 0.08, 'triangle', 0.02);
      vibrate(35);

      notify('success','Door unlocked ✅', 'You finished the game!');
setTimeout(lockAfterWin, 1800); // match the CSS duration (ms)
      return;
    }
  }

  function moveByArrow(dir){
    if(state.status !== 'in_progress') return;
    if(!state.userId) return;

    const toId = nextIdByDir(state.userId, dir);
    if(!toId){ onBlocked('Edge'); return; }

    // blocked by barrier or visited => same behavior
    if(dirBlocked(state.userId, dir)){ onBlocked('Blocked'); return; }
    // if(state.visited.has(Number(toId))){ onBlocked('Visited'); return; }

    onCellClick(toId);
  }
function userHasAnyKey(){
  return state.keysCollected && state.keysCollected.size > 0;
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

        // ✅ hint should not suggest stepping into visited tiles
        // if(state.visited.has(Number(nb))) continue;

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
      el.style.outline = '3px solid rgba(168,85,247,.55)';
      el.style.boxShadow = '0 0 0 6px rgba(168,85,247,.16)';
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

  async function submitAttempt(isAuto=false){
    if(state.isSubmitting) return;
    state.isSubmitting = true;

    try{
      elSubmitBtn.disabled = true;

      const endAbsMs = nowMs();
      const startedAbs = state.startedAtMs || endAbsMs;
      const timeTakenMs = Math.max(0, endAbsMs - startedAbs);

      const startIndex = Number(
        (state.moves.length ? state.moves[0]?.from : state.userId) || state.userId || 1
      );

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
        time_taken_ms: Number(timeTakenMs)
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
        timing: timing,
        visited: Array.from(state.visited)
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

  function renderBoard(){
    elBoard.style.setProperty('--n', String(state.N));
    elBoard.innerHTML = '';

    // power state styling
    const powered = (state.keysCollected.size > 0);
    elBoard.classList.toggle('dgx-playerPowered', powered);

    state.cells.forEach(cell => {
      const div = document.createElement('div');
      div.className = 'dgx-cell';
      div.dataset.id = String(cell.id);
      div.setAttribute('role','button');
      div.setAttribute('tabindex','0');

      if(Number(cell.id) === Number(state.userId)) div.classList.add('is-current');
      if(state.visited.has(Number(cell.id))) div.classList.add('is-visited');

      const idx = document.createElement('div');
      idx.className = 'dgx-idx';
      idx.textContent = String(cell.id);

      const ico = document.createElement('div');
      ico.className = 'dgx-ico';

      if(Number(cell.id) === Number(state.userId)){
  ico.classList.add('user');

  // ✅ after first key pickup: user stays purple AND shows key with user
  if(userHasAnyKey()){
    ico.innerHTML = `
      <span style="position:relative; display:inline-block; line-height:1;">
        <i class="fa-solid fa-user"></i>
        <i class="fa-solid fa-key" style="
          position:absolute;
          right:-10px;
          bottom:-8px;
          font-size:14px;
          color:#f59e0b;
          filter: drop-shadow(0 10px 16px rgba(2,6,23,.18));
        "></i>
      </span>
    `;
  }else{
    ico.innerHTML = '<i class="fa-solid fa-user"></i>';
  }
}
else if(Number(cell.id) === Number(state.doorId)){
        // ✅ door color stays black (closed/open icon changes only)
        if(state.doorUnlocked){
          ico.classList.add('doorOpen');
          ico.innerHTML = '<i class="fa-solid fa-door-open"></i>';
        }else{
          ico.classList.add('doorClosed');
          ico.innerHTML = '<i class="fa-solid fa-door-closed"></i>';
        }
      }else if(state.keys.has(cell.id) && !state.keysCollected.has(cell.id)){
        ico.classList.add('key');
        ico.innerHTML = '<i class="fa-solid fa-key"></i>';
      }else{
        ico.innerHTML = '';
      }

      div.appendChild(idx);
      div.appendChild(ico);

      // ✅ arrows around USER (not board)
      if(Number(cell.id) === Number(state.userId)){
        const wrap = document.createElement('div');
        wrap.className = 'dgx-uArrows';

        const ended = (state.status !== 'in_progress');

        const mk = (dir, icon) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = `dgx-uArrow ${dir}`;
          btn.innerHTML = `<i class="fa-solid ${icon}"></i>`;

          const toId = nextIdByDir(state.userId, dir);
          const blockedByBarrier = dirBlocked(state.userId, dir);
const shouldDisable = ended || blockedByBarrier;

if(blockedByBarrier) btn.classList.add('blocked');
if(shouldDisable) btn.classList.add('disabled');


          btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if(shouldDisable){
              onBlocked('Blocked');
              return;
            }
            moveByArrow(dir);
          });

          return btn;
        };

        wrap.appendChild(mk('up','fa-chevron-up'));
        wrap.appendChild(mk('down','fa-chevron-down'));
        wrap.appendChild(mk('left','fa-chevron-left'));
        wrap.appendChild(mk('right','fa-chevron-right'));

        div.appendChild(wrap);
      }

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

  function hydrateFromGame(game){
    state.game = game || {};
    state.N = Number(game?.grid_dim || 3);
    state.timeLimitSec = Number(game?.time_limit_sec || 30);
    state.timeLeft = state.timeLimitSec;

    elGameTitle.textContent = game?.title ? String(game.title) : 'Door Game';
    elDim.textContent = `${state.N}×${state.N}`;

    const instr = (game?.instructions_html || game?.description || '').toString().trim();
    elInstruction.textContent = instr ? instr.replace(/<[^>]*>?/gm, '').slice(0, 220) : 'Collect all keys, then reach the door. (Barriers are hidden but still block movement.)';

    let grid = game?.grid_json || null;
    try{ if(typeof grid === 'string') grid = JSON.parse(grid); }catch(e){ grid = null; }
    if(!Array.isArray(grid) || grid.length !== state.N*state.N){
      throw new Error('Invalid grid_json in game.');
    }

    state.cells = normalizeGrid(grid, state.N);

    state.userId = null;
    state.doorId = null;
    state.keys = new Set();
    state.keysCollected = new Set();
    state.visited = new Set();

    state.keyEvent = null;
    state.doorEvent = null;
    state.doorUnlocked = false;

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

    state.visited.add(Number(state.userId));

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

    // center initially
    setTimeout(()=> centerOnCurrent(), 150);
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
      setTimeout(()=> centerOnCurrent(), 180);
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

  // keyboard arrows
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
        notify('info','Hint','Highlighted a route to a key (avoiding visited tiles).');
        return;
      }
      notify('warning','Hint','No route to any key found.');
      return;
    }

    const p = findShortestPath(state.userId, target);
    flashHint(p);
    notify('info','Hint','Highlighted a route to the door.');
  });

  elGiveUpBtn?.addEventListener('click', async () => {
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

    state.moves.push({ t_ms: 0, dt_ms: 0, action: 'give_up' });
    saveCache();
    updateSidebar();
    renderBoard();
    notify('warning','Attempt ended','Marked as fail. You can submit now.');
  });

  elCenterBtn?.addEventListener('click', () => centerOnCurrent());

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

  init();
})();
</script>

</body>
</html>
