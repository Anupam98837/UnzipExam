{{-- resources/views/modules/pathGame/exam.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Path Game</title>

  {{-- Bootstrap + FontAwesome + Common UI --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    /* =========================================================
      Path Game Exam UI (Professional)
      ✅ Proper grid board (clean + symmetric)
      ✅ ONE rotate button BELOW whole grid (select tile first)
      ✅ Rocket + Earth INSIDE #pgxBoard (absolute overlays)
      ✅ Tap any mini-cell → Rocket travels along current arrow-trail to that cell
      ✅ NEW: Launch button enabled only when path reaches last column
      ✅ NEW: Auto land + confetti + sound
    ========================================================= */

    .pgx-exam{
      --pgx-ink: #0f172a;
      --pgx-muted: #64748b;
      --pgx-card: var(--surface, #ffffff);
      --pgx-line: rgba(2,6,23,.12);
      --pgx-soft: rgba(2,6,23,.06);

      --pgx-brand: var(--primary-color, #5b5bd6);
      --pgx-accent: var(--accent-color, #9b4dff);

      --pgx-danger: #ef4444;
      --pgx-success:#22c55e;
      --pgx-warn:  #f59e0b;

      --pgx-radius: 18px;
      --pgx-radius2: 26px;
      --pgx-shadow: 0 18px 45px rgba(2,6,23,.14);

      min-height:100vh;
      padding:18px 14px;
      display:flex;
      justify-content:center;
      background:
        radial-gradient(1000px 520px at 12% 8%, rgba(155,77,255,.16), transparent 60%),
        radial-gradient(1000px 600px at 88% 12%, rgba(91,91,214,.14), transparent 55%),
        linear-gradient(180deg, rgba(2,6,23,.02), rgba(2,6,23,.03));
      color: var(--pgx-ink);
      overflow-x:hidden;
    }

    html.theme-dark .pgx-exam{
      --pgx-card:#0f172a;
      --pgx-ink:#e5e7eb;
      --pgx-muted:#94a3b8;
      --pgx-line: rgba(148,163,184,.18);
      --pgx-soft: rgba(148,163,184,.10);
      background:
        radial-gradient(1000px 520px at 12% 8%, rgba(155,77,255,.14), transparent 60%),
        radial-gradient(1000px 600px at 88% 12%, rgba(91,91,214,.12), transparent 55%),
        linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.86));
    }

    .pgx-shell{ width:100%; max-width:1600px; display:flex; flex-direction:column; gap:14px; }

    /* Topbar */
    .pgx-topbar{
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--pgx-brand) 70%, white) 0%,
        color-mix(in srgb, var(--pgx-accent) 70%, white) 100%);
      border-radius: var(--pgx-radius2);
      box-shadow: var(--pgx-shadow);
      padding: 14px 16px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      position: sticky;
      top: 10px;
      z-index: 40;
      color:#fff;
    }
    html.theme-dark .pgx-topbar{
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--pgx-brand) 60%, #0b1220) 0%,
        color-mix(in srgb, var(--pgx-accent) 60%, #0b1220) 100%);
    }

    .pgx-title{ display:flex; flex-direction:column; gap:4px; min-width:0; }
    .pgx-title h1{
      margin:0;
      font-size: clamp(16px, 1.25vw, 20px);
      font-weight: 950;
      letter-spacing:.2px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      max-width: 62vw;
    }
    .pgx-title .sub{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      font-size:12px;
      font-weight:900;
      opacity:.95;
    }
    .pgx-pill{
      display:inline-flex;
      align-items:center;
      gap:7px;
      padding:7px 10px;
      border-radius:999px;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      white-space:nowrap;
    }

    .pgx-actions{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
    .pgx-btn{
      border: 1px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.14);
      color:#fff;
      border-radius: 14px;
      padding: 10px 14px;
      display:inline-flex;
      align-items:center;
      gap:9px;
      font-weight: 900;
      font-size:13px;
      transition:.15s ease;
      text-decoration:none;
      user-select:none;
    }
    .pgx-btn:hover{ transform: translateY(-1px); background: rgba(255,255,255,.20); }
    .pgx-btn:active{ transform: translateY(0px); }
    .pgx-btn.danger{
      background: rgba(239,68,68,.18);
      border-color: rgba(239,68,68,.28);
    }
    .pgx-btn.success{
      background: rgba(34,197,94,.18);
      border-color: rgba(34,197,94,.28);
    }

    /* Main Layout */
    .pgx-grid{
      display:grid;
      grid-template-columns: 1fr 320px;
      gap:14px;
      align-items:start;
    }
    @media (max-width: 1080px){
      .pgx-grid{ grid-template-columns: 1fr; }
      .pgx-title h1{ max-width:92vw; }
    }

    .pgx-card{
      background: var(--pgx-card);
      border: 1px solid var(--pgx-line);
      border-radius: var(--pgx-radius2);
      box-shadow: 0 12px 30px rgba(2,6,23,.08);
      overflow:hidden;
    }
    html.theme-dark .pgx-card{ box-shadow: 0 14px 38px rgba(0,0,0,.45); }

    .pgx-card-hd{
      padding: 14px 16px;
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:14px;
      border-bottom: 1px solid var(--pgx-line);
      background: linear-gradient(180deg, rgba(2,6,23,.03), transparent);
    }

    .pgx-instr{ display:flex; flex-direction:column; gap:6px; }
    .pgx-instr .kicker{
      font-size:12px;
      font-weight:950;
      letter-spacing:.55px;
      color: color-mix(in srgb, var(--pgx-accent) 70%, var(--pgx-ink));
      text-transform: uppercase;
    }
    .pgx-instr .text{
      font-size:14px;
      font-weight:800;
      color: var(--pgx-muted);
      line-height:1.4;
    }

    .pgx-timer{ min-width:230px; display:flex; flex-direction:column; gap:8px; align-items:flex-end; }
    .pgx-timer .row1{ display:flex; gap:10px; align-items:center; font-weight:950; }
    .pgx-timer .row1 .label{ font-size:12px; color:var(--pgx-muted); }
    .pgx-timer .row1 .time{
      font-size:14px;
      padding:6px 11px;
      border-radius:999px;
      border:1px solid var(--pgx-line);
      background: rgba(2,6,23,.03);
      font-weight:950;
    }
    html.theme-dark .pgx-timer .row1 .time{ background: rgba(148,163,184,.08); }

    .pgx-progress{
      width:230px;
      height:10px;
      border-radius:999px;
      background: rgba(2,6,23,.08);
      overflow:hidden;
      border:1px solid var(--pgx-line);
    }
    html.theme-dark .pgx-progress{ background: rgba(148,163,184,.10); }
    .pgx-progress > i{
      display:block;
      height:100%;
      width:100%;
      border-radius:999px;
      background: linear-gradient(90deg, #22c55e, #16a34a);
      transition: width .35s ease;
    }

    .pgx-card-bd{ padding:16px; }

    .pgx-mission{
      border-radius: 20px;
      padding: 14px 16px;
      color:#fff;
      font-weight: 950;
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--pgx-accent) 76%, white) 0%,
        color-mix(in srgb, var(--pgx-brand) 76%, white) 100%);
      box-shadow: 0 14px 30px rgba(2,6,23,.10);
      display:flex;
      justify-content:space-between;
      align-items:center;
      flex-wrap:wrap;
      gap:10px;
      margin-bottom: 12px;
      position:relative;
      overflow:hidden;
    }
    .pgx-mission:after{
      content:"";
      position:absolute;
      inset:-40px;
      background: radial-gradient(closest-side, rgba(255,255,255,.14), transparent 65%);
      animation: pgxGlow 3.2s ease-in-out infinite;
      pointer-events:none;
    }
    @keyframes pgxGlow{
      0%{ transform: translate(-8%,-6%) scale(1); opacity:.75; }
      50%{ transform: translate(8%,6%) scale(1.05); opacity:1; }
      100%{ transform: translate(-8%,-6%) scale(1); opacity:.75; }
    }

    .pgx-mission .left{ display:flex; align-items:center; gap:10px; position:relative; z-index:1; }
    .pgx-mission .right{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12px; opacity:.95; position:relative; z-index:1; }
    .pgx-tag{
      padding: 6px 10px;
      border-radius:999px;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      display:inline-flex;
      align-items:center;
      gap:8px;
      white-space:nowrap;
    }

    /* Board Container */
    .pgx-board{
      position: relative;
      border-radius: 22px;
  background: url("/assets/images/background-path-game/bg.jpg") center/cover no-repeat;
      border:1px solid rgba(255,255,255,.18);
      overflow:auto;
      padding: 14px;
      min-height: 340px;
      scroll-behavior:smooth;
    }
    html.theme-dark .pgx-board{
      background:
        radial-gradient(600px 260px at 20% 20%, rgba(155,77,255,.14), transparent 58%),
        radial-gradient(600px 260px at 80% 28%, rgba(91,91,214,.12), transparent 58%),
        #0b1220;
      border-color: rgba(148,163,184,.18);
    }

    /* === Main Board Layer (relative container) === */
    .pgx-gridBoard{
      position: relative;
      padding: 26px;
      /* background: rgba(255,255,255,.18); */
      border:1px solid rgba(255,255,255,.22);
      border-radius: 18px;
      /* backdrop-filter: blur(1px); */
      min-width: max-content;
    }
    html.theme-dark .pgx-gridBoard{
      background: rgba(148,163,184,.08);
      border-color: rgba(148,163,184,.16);
    }

    /* Tiles layer is the actual grid */
    .pgx-tilesLayer{
      position: relative;
      display:grid;
      justify-content:center;
      align-content:center;
      grid-template-columns: repeat(var(--N), 140px);
      grid-auto-rows: 140px;
    }

    @media(max-width: 980px){
      .pgx-tilesLayer{
        grid-template-columns: repeat(var(--N), 128px);
        grid-auto-rows: 128px;
      }
    }

    /* Tile */
    .pgx-tile{
      position:relative;
      border: 1px solid rgba(2,6,23,.14);
      background: #fff;
      box-shadow: 0 12px 28px rgba(2,6,23,.10);
      overflow:hidden;
      user-select:none;
      display:flex;
      flex-direction:column;
      cursor: pointer;
    }
    html.theme-dark .pgx-tile{
      background:#0f172a;
      border-color: rgba(148,163,184,.22);
      box-shadow: 0 18px 44px rgba(0,0,0,.55);
    }

    /* ✅ selected tile */
    .pgx-tile.is-selected{
      outline: 3px solid rgba(155,77,255,.90);
      outline-offset: -3px;
      box-shadow: 0 18px 46px rgba(155,77,255,.22);
    }

    /* tile badge (hidden by default) */
    .tile-badge{
      position:absolute;
      left:10px;
      top:10px;
      z-index:5;
      display:flex;
      align-items:center;
      gap:8px;
      padding: 6px 10px;
      border-radius: 999px;
      border:1px solid var(--pgx-line);
      background: rgba(2,6,23,.04);
      font-weight:950;
      font-size:12px;
      color: var(--pgx-muted);
      box-shadow: 0 10px 22px rgba(2,6,23,.10);
      pointer-events:none;
    }
    html.theme-dark .tile-badge{
      background: rgba(148,163,184,.10);
      color:#cbd5e1;
    }
    .tile-badge .rot{
      width:26px;height:26px;
      border-radius:999px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background: rgba(155,77,255,.14);
      border: 1px solid rgba(155,77,255,.22);
      color: var(--pgx-accent);
    }
    .tile-badge.locked .rot{
      background: rgba(148,163,184,.12);
      border-color: rgba(148,163,184,.18);
      color: #64748b;
    }
    .tile-badge .count{
      margin-left:2px;
      font-size:11px;
      opacity:.9;
      font-weight:950;
    }

    /* Mini grid inside tile */
    .pgx-miniGrid{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
      grid-auto-rows: 1fr;
      gap:6px;
      flex: 1;
      background: rgba(2,6,23,.02);
      border: 1px solid var(--pgx-line);
    }
    html.theme-dark .pgx-miniGrid{
      background: rgba(148,163,184,.06);
    }

    .pgx-miniCell{
      border: 1px solid rgba(2,6,23,.10);
      background: #fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:950;
      position:relative;
      overflow:hidden;
      transition: .12s ease;
      cursor: pointer;
    }
    .pgx-miniCell:hover{
      transform: translateY(-1px);
      box-shadow: 0 10px 20px rgba(2,6,23,.10);
    }
    html.theme-dark .pgx-miniCell{
      background:#0b1220;
      border-color: rgba(148,163,184,.18);
    }

    .pgx-miniCell .a{
      font-size:16px;
      color: #111827;
      opacity:.95;
      filter: drop-shadow(0 10px 14px rgba(2,6,23,.10));
    }
    .pgx-miniCell .fa-arrow-right-long{
      color: var(--pgx-accent) !important; /* ✅ purple */
    }

    html.theme-dark .pgx-miniCell .a{
      color:#e5e7eb;
      opacity:.9;
    }

    .pgx-miniCell.is-path{
      outline: 2px dashed rgba(45, 212, 191, .95);
      outline-offset: -5px;
      box-shadow: 0 10px 22px rgba(2,6,23,.10);
    }
    .pgx-miniCell.is-path:after{
      content:"";
      position:absolute; inset:-20px;
      background: radial-gradient(circle at 30% 30%, rgba(45, 212, 191, .22), transparent 60%);
      pointer-events:none;
    }

    .pgx-miniCell.is-dead{
      outline: 2px dashed rgba(239,68,68,.85);
      outline-offset: -5px;
    }

    /* ✅ Global tools under grid */
    .pgx-boardTools{
      margin-top: 12px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      flex-wrap:wrap;
    }

    .pgx-globalRotateBtn{
      border:none;
      outline:none;
      cursor:pointer;
      border-radius: 14px;
      padding: 10px 14px;
background: linear-gradient(135deg,
  rgba(55, 48, 163, 0.98),
  rgba(88, 28, 135, 0.98)
);
      border: 1px solid rgba(255,255,255,.22);
      color:#fff;
      font-weight:950;
      font-size:13px;
      display:inline-flex;
      align-items:center;
      gap:10px;
      transition:.15s ease;
      min-width: 220px;
      justify-content:center;
      box-shadow: 0 14px 28px rgba(2,6,23,.16);
    }

    html.theme-dark .pgx-globalRotateBtn{
      background: linear-gradient(135deg, rgba(91,91,214,.26), rgba(155,77,255,.26));
      border-color: rgba(148,163,184,.22);
      box-shadow: 0 18px 44px rgba(0,0,0,.35);
    }

    .pgx-globalRotateBtn:hover{ transform: translateY(-1px); }
    .pgx-globalRotateBtn:active{ transform: translateY(0px); }

    .pgx-globalRotateBtn:disabled{
      opacity:.55;
      cursor:not-allowed;
      transform:none !important;
      filter: grayscale(.2);
    }

    /* ✅ Launch button */
    .pgx-launchBtn{
      border:none;
      outline:none;
      cursor:pointer;
      border-radius: 14px;
      padding: 10px 14px;
background: linear-gradient(135deg,
  rgba(22, 163, 74, 0.92),
  rgba(13, 148, 136, 0.98)
);
      border: 1px solid rgba(255,255,255,.22);
      color:#fff;
      font-weight:950;
      font-size:13px;
      display:inline-flex;
      align-items:center;
      gap:10px;
      transition:.15s ease;
      min-width: 220px;
      justify-content:center;
      box-shadow: 0 14px 28px rgba(2,6,23,.16);
    }
    .pgx-launchBtn:hover{ transform: translateY(-1px); }
    .pgx-launchBtn:active{ transform: translateY(0px); }
    .pgx-launchBtn:disabled{
      opacity:.55;
      cursor:not-allowed;
      transform:none !important;
      filter: grayscale(.2);
    }

    /* ✅ Land button */
    .pgx-landBtn{
      border:none;
      outline:none;
      cursor:pointer;
      border-radius: 14px;
      padding: 10px 14px;
      background: linear-gradient(135deg, rgba(245,158,11,.28), rgba(249,115,22,.70));
      border: 1px solid rgba(255,255,255,.22);
      color:#fff;
      font-weight:950;
      font-size:13px;
      display:inline-flex;
      align-items:center;
      gap:10px;
      transition:.15s ease;
      min-width: 170px;
      justify-content:center;
      box-shadow: 0 14px 28px rgba(2,6,23,.16);
    }
    .pgx-landBtn:hover{ transform: translateY(-1px); }
    .pgx-landBtn:active{ transform: translateY(0px); }
    .pgx-landBtn:disabled{
      opacity:.55;
      cursor:not-allowed;
      transform:none !important;
      filter: grayscale(.2);
    }

    .pgx-boardHint{
      font-size:12px;
      font-weight:900;
      color: rgba(255,255,255,.92);
      background: rgba(0,0,0,.14);
      border: 1px solid rgba(255,255,255,.20);
      border-radius: 999px;
      padding: 8px 12px;
      backdrop-filter: blur(10px);
      display:inline-flex;
      align-items:center;
      gap:8px;
    }

    html.theme-dark .pgx-boardHint{
      background: rgba(0,0,0,.18);
    }

    /* Rocket + Earth overlays INSIDE #pgxBoard */
    .pgx-edgeNode{
      position:absolute;
      z-index:60;
      transform: translate(-50%, -50%);
      width: 72px;
      height: 72px;
      border-radius: 999px;
      display:flex;
      align-items:center;
      justify-content:center;
      border: 1px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.16);
      box-shadow: 0 18px 44px rgba(2,6,23,.18);
      backdrop-filter: blur(10px);
      pointer-events:none;
    }
    html.theme-dark .pgx-edgeNode{
      background: rgba(148,163,184,.14);
      border-color: rgba(148,163,184,.20);
    }
    .pgx-edgeNode.start{
      background: linear-gradient(135deg, rgba(91,91,214,.26), rgba(155,77,255,.26));
      pointer-events:auto;
      overflow:visible;
    }
    .pgx-edgeNode.end{
      background: linear-gradient(135deg, rgba(34,197,94,.26), rgba(16,185,129,.26));
    }
    .pgx-edgeNode i{
      color:#fff;
      font-size: 28px;
      filter: drop-shadow(0 14px 18px rgba(2,6,23,.22));
    }

    .pgx-edgeLabel{
      position:absolute;
      left:50%;
      top: 100%;
      transform: translate(-50%, 8px);
      font-size: 11px;
      font-weight: 950;
      color: rgba(255,255,255,.92);
      text-shadow: 0 10px 18px rgba(2,6,23,.35);
      white-space:nowrap;
      opacity:.9;
    }

    /* Rocket that moves along path */
    .pgx-rocket{
      position:absolute;
      z-index:70;
      width: 40px;
      height: 40px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius: 999px;
      background: rgba(255,255,255,.20);
      border: 1px solid rgba(255,255,255,.22);
      box-shadow: 0 18px 44px rgba(2,6,23,.18);
      backdrop-filter: blur(10px);
      transform: translate(-50%, -50%);
      transition: left .16s ease, top .16s ease;
      pointer-events:none;
      opacity: 0;
      visibility: hidden;
    }
    .pgx-rocket.show{
      opacity: 1;
      visibility: visible;
    }
    .pgx-rocket i{
      color:#fff;
      filter: drop-shadow(0 14px 18px rgba(2,6,23,.18));
      transform: rotate(35deg);
    }
    html.theme-dark .pgx-rocket{
      background: rgba(148,163,184,.14);
      border-color: rgba(148,163,184,.20);
    }

    /* Fire + Smoke trail */
    .pgx-rocket .trail{
      position:absolute;
      left:-16px;
      top:50%;
      transform: translateY(-50%) rotate(35deg);
      width: 20px;
      height: 12px;
      border-radius: 999px;
      background: radial-gradient(circle at 40% 50%, rgba(255,200,80,.95), rgba(255,95,50,.75) 60%, transparent 70%);
      filter: blur(.2px);
      opacity: 0;
    }

    .pgx-rocket .smoke{
      position:absolute;
      left:-28px;
      top:50%;
      transform: translateY(-50%) rotate(35deg);
      width: 24px;
      height: 24px;
      border-radius: 999px;
      background: radial-gradient(circle at 40% 50%, rgba(255,255,255,.55), rgba(255,255,255,.18) 55%, transparent 75%);
      opacity: 0;
      filter: blur(.8px);
    }

    .pgx-rocket.is-firing .trail{
      opacity: 1;
      animation: pgxFlame 260ms ease-in-out infinite;
    }
    .pgx-rocket.is-firing .smoke{
      opacity: .8;
      animation: pgxSmoke 520ms ease-in-out infinite;
    }

    .pgx-rocket.is-launch{
      animation: pgxLaunchPop 700ms cubic-bezier(.2,.9,.2,1) 1;
    }
    .pgx-rocket.is-launch .smoke{
      opacity: 1;
      animation: pgxSmokeBurst 900ms ease-out 1;
    }

    @keyframes pgxFlame{
      0%{ transform: translateY(-50%) rotate(35deg) scaleX(1); }
      50%{ transform: translateY(-50%) rotate(35deg) scaleX(1.25); }
      100%{ transform: translateY(-50%) rotate(35deg) scaleX(1); }
    }
    @keyframes pgxSmoke{
      0%{ transform: translateY(-50%) rotate(35deg) scale(.75); opacity:.55; }
      50%{ transform: translateY(-50%) rotate(35deg) scale(1); opacity:.85; }
      100%{ transform: translateY(-50%) rotate(35deg) scale(.75); opacity:.55; }
    }
    @keyframes pgxSmokeBurst{
      0%{ transform: translateY(-50%) rotate(35deg) scale(.75); opacity:.9; }
      100%{ transform: translateY(-50%) rotate(35deg) scale(1.65); opacity:0; }
    }
    @keyframes pgxLaunchPop{
      0%{ transform: translate(-50%,-50%) scale(.85); }
      55%{ transform: translate(-50%,-50%) scale(1.18); }
      100%{ transform: translate(-50%,-50%) scale(1); }
    }

    .pgx-validPill{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding: 8px 12px;
      border-radius: 999px;
      font-weight: 950;
      font-size: 12px;
      border: 1px solid rgba(34,197,94,.30);
      background: rgba(34,197,94,.12);
      color: #16a34a;
    }
    html.theme-dark .pgx-validPill{
      color:#34d399;
      background: rgba(34,197,94,.10);
    }

    /* Right panel */
    @media(max-width: 1080px){ .pgx-side{ position:static; } }

    .pgx-panel{ padding:14px 16px; display:flex; flex-direction:column; gap:10px; }
    .pgx-panel .hd{ display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .pgx-panel .hd .t{ font-weight:950; font-size:14px; display:flex; align-items:center; gap:10px; }

    .pgx-miniStats{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    .pgx-stat{
      border:1px solid var(--pgx-line);
      background: rgba(2,6,23,.03);
      border-radius: 16px;
      padding: 10px 12px;
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    html.theme-dark .pgx-stat{ background: rgba(148,163,184,.08); }
    .pgx-stat .k{ color: var(--pgx-muted); font-size:12px; font-weight:900; }
    .pgx-stat .v{ font-weight:950; font-size:16px; }

    .pgx-btn2{
      border-radius:14px;
      border:1px solid var(--pgx-line);
      background: rgba(2,6,23,.03);
      padding: 10px 14px;
      font-weight: 950;
      font-size: 13px;
      display:inline-flex;
      align-items:center;
      gap:9px;
      transition:.15s ease;
      user-select:none;
    }
    html.theme-dark .pgx-btn2{ background: rgba(148,163,184,.08); }
    .pgx-btn2:hover{ transform: translateY(-1px); }
    .pgx-btn2:active{ transform: translateY(0px); }
    .pgx-btn2.primary{
      background: linear-gradient(135deg, var(--pgx-brand), var(--pgx-accent));
      color:#fff;
      border-color: rgba(255,255,255,.08);
    }
    .pgx-btn2:disabled{ opacity:.55; cursor:not-allowed; transform:none !important; }

    .pgx-note{
      color: var(--pgx-muted);
      font-size: 12px;
      font-weight: 850;
      line-height: 1.45;
    }

    /* Intro modal cards */
    .pgx-introCard{
      border: 1px solid var(--pgx-line);
      background: rgba(2,6,23,.02);
      border-radius: 16px;
      padding: 12px 14px;
    }
    html.theme-dark .pgx-introCard{ background: rgba(148,163,184,.06); }
    .pgx-introTitle{
      display:flex; align-items:center; gap:10px;
      font-weight: 950; font-size: 13px;
      margin-bottom: 6px;
      color: color-mix(in srgb, var(--pgx-accent) 70%, var(--pgx-ink));
      text-transform: uppercase;
      letter-spacing:.35px;
    }
    .pgx-introBody{
      color: var(--pgx-muted);
      font-size: 14px;
      font-weight: 800;
      line-height: 1.5;
      word-break: break-word;
    }

    /* ✅ D-Pad arrows around START node */
    .pgx-startNav{
      position:absolute;
      width:30px;
      height:30px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.18);
      background: rgba(0,0,0,.22);
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      z-index:80;
      transition:.12s ease;
      box-shadow:0 10px 22px rgba(0,0,0,.18);
    }
    .pgx-startNav:hover{
      transform: scale(1.08);
      background: rgba(0,0,0,.30);
    }
    .pgx-startNav:active{
      transform: scale(0.98);
    }
    .pgx-startNav:disabled{
      opacity:.38;
      cursor:not-allowed;
      transform:none;
    }
    .pgx-startNav.up{
      top:-14px;
      left:50%;
      transform: translateX(-50%);
    }
    .pgx-startNav.down{
      bottom:-14px;
      left:50%;
      transform: translateX(-50%);
    }
    .pgx-startNav.left{
      left:-14px;
      top:50%;
      transform: translateY(-50%);
    }
    .pgx-startNav.right{
      right:-14px;
      top:50%;
      transform: translateY(-50%);
    }
  </style>
</head>
<body>

<!-- Tab Switch Overlay -->
<div id="pgxViolationOverlay" style="
  position:fixed; top:0; left:0; width:100%; height:100%;
  background:rgba(0,0,0,0.95); z-index:9999;
  display:none; align-items:center; justify-content:center;
  backdrop-filter:blur(10px);">
  <div style="background:white; padding:3rem; border-radius:20px;
    text-align:center; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
    <div style="font-size:4rem; color:#ef4444; margin-bottom:1rem;">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <div style="font-size:1.5rem; font-weight:700; color:#111827; margin-bottom:1rem;">Tab Switch Detected!</div>
    <div style="color:#6b7280; margin-bottom:2rem; line-height:1.6;">
      You have left the exam window. This action has been logged.<br><br>
      <strong>Please return to the exam and stay focused.</strong>
    </div>
    <div style="font-size:2rem; font-weight:700; color:#ef4444; margin-bottom:1rem;">
      Violation #<span id="pgxViolationCount">1</span>
    </div>
    <button id="pgxReturnBtn" onclick="document.getElementById('pgxViolationOverlay').style.display='none'; pgxTabLogged=false; pgxRequestFullscreen();"
      style="padding:12px 24px; font-size:15px; border-radius:14px; border:none; cursor:pointer;
        background:linear-gradient(135deg,#5b5bd6,#9b4dff); color:#fff; font-weight:900; display:inline-flex; align-items:center; gap:8px;">
      <i class="fa-solid fa-arrow-left"></i> Return to Game
    </button>
  </div>
</div>

<!-- Violation Badge -->
<div id="pgxViolationBadge" style="
  position:fixed; top:80px; right:20px; z-index:1000;
  background:#fee; border:2px solid #ef4444;
  padding:0.5rem 1rem; border-radius:10px;
  font-weight:600; color:#dc2626; display:none;">
  <i class="fa-solid fa-exclamation-triangle me-2"></i>
  Violations: <span id="pgxBadgeCount">0</span>
</div>

<div class="pgx-exam" id="pgxExam">
  <div class="pgx-shell">

    {{-- Topbar --}}
    <div class="pgx-topbar">
      <div class="pgx-title">
        <h1 id="pgxGameTitle">Loading path game…</h1>
        <div class="sub">
          <span class="pgx-pill"><i class="fa-solid fa-layer-group"></i> <span id="pgxDim">--×--</span></span>
          <span class="pgx-pill"><i class="fa-solid fa-rotate"></i> Rotatable: <span id="pgxRotTiles">--</span></span>
          <span class="pgx-pill"><i class="fa-solid fa-database"></i> Auto-saved</span>
        </div>
      </div>

      <div class="pgx-actions">
        <a class="pgx-btn" href="/quizzes" id="pgxQuitBtn"><i class="fa-solid fa-house"></i> Dashboard</a>
        <button class="pgx-btn success" id="pgxValidateBtn" type="button">
          <i class="fa-solid fa-check"></i> Validate
        </button>
      </div>
    </div>

    <div class="pgx-grid">
      {{-- Left: Board --}}
      <div class="pgx-card">
        <div class="pgx-card-hd">
          <div class="pgx-instr">
            <div class="kicker">Instructions</div>
            <div class="text" id="pgxInstruction">Loading…</div>
          </div>

          <div class="pgx-timer">
            <div class="row1">
              <span class="label">Time Left</span>
              <span class="time"><span id="pgxTimeLeft">--</span>s</span>
            </div>
            <div class="pgx-progress">
              <i id="pgxTimeBar" style="width:100%"></i>
            </div>
          </div>
        </div>

        <div class="pgx-card-bd">
          <div class="pgx-mission">
            <div class="left">
              <i class="fa-solid fa-rocket"></i>
              <span id="pgxMissionText">Make a valid path → Rocket reaches Earth</span>
            </div>
            <div class="right">
              <span class="pgx-tag"><i class="fa-solid fa-hand-pointer"></i> Tap cells to preview rocket travel</span>
              <span class="pgx-tag"><i class="fa-solid fa-rotate"></i> Select tile & rotate</span>
              <span class="pgx-tag"><i class="fa-solid fa-rocket"></i> Launch when ready</span>
            </div>
          </div>

          {{-- ✅ PRO GRID BOARD --}}
          <div class="pgx-board" id="pgxBoardWrap">
            <div id="pgxBoard" class="pgx-gridBoard" style="--N:3">
              {{-- Tiles layer only --}}
              <div id="pgxTilesLayer" class="pgx-tilesLayer" style="--N:3">
               <div class="text-white fw-bold d-flex align-items-center gap-2">
                  <span class="spinner-border spinner-border-sm"></span>
                  Loading tiles…
                </div>
              </div>

              {{-- Start + End markers --}}
              <div class="pgx-edgeNode start" id="pgxStartNode" style="left:60px;top:60px;">
                {{-- ✅ D-Pad arrows around start --}}
                <button type="button" class="pgx-startNav up" id="pgxStartUp" aria-label="Move Start Up">
                  <i class="fa-solid fa-chevron-up"></i>
                </button>

                <button type="button" class="pgx-startNav down" id="pgxStartDown" aria-label="Move Start Down">
                  <i class="fa-solid fa-chevron-down"></i>
                </button>

                <button type="button" class="pgx-startNav left d-none" id="pgxStartLeft" aria-label="Move Start Jump Up">
                  <i class="fa-solid fa-chevron-left"></i>
                </button>

                <button type="button" class="pgx-startNav right d-none" id="pgxStartRight" aria-label="Move Start Jump Down">
                  <i class="fa-solid fa-chevron-right"></i>
                </button>

                {{-- ✅ This icon will hide when launch --}}
                <i class="fa-solid fa-rocket" id="pgxStartIcon"></i>
                <div class="pgx-edgeLabel">START</div>
              </div>

              <div class="pgx-edgeNode end" id="pgxEndNode" style="right:220px;top:240px;">
                <i class="fa-solid fa-earth-asia"></i>
                <div class="pgx-edgeLabel">EARTH</div>
              </div>

              {{-- Moving Rocket --}}
              <div class="pgx-rocket" id="pgxRocket" style="left:20px;top:20px;">
                <span class="trail"></span>
                <span class="smoke"></span>
                <i class="fa-solid fa-rocket"></i>
              </div>

            </div>
          </div>

          {{-- ✅ Tools --}}
          <div class="pgx-boardTools">
            <button type="button" class="pgx-globalRotateBtn" id="pgxGlobalRotateBtn" disabled>
              <i class="fa-solid fa-rotate"></i> Rotate Selected Tile
            </button>

            {{-- ✅ NEW: Launch Rocket Button --}}
            <button type="button" class="pgx-launchBtn" id="pgxLaunchBtn" disabled>
              <i class="fa-solid fa-rocket"></i> Launch Rocket
            </button>

            {{-- ✅ Land button (optional, auto landing exists too) --}}
            <button type="button" class="pgx-landBtn d-none" id="pgxLandBtn" disabled>
              <i class="fa-solid fa-earth-asia"></i> Land
            </button>

            <span class="pgx-boardHint" id="pgxSelTileHint">
              <i class="fa-solid fa-hand-pointer"></i> Select a tile first
            </span>
          </div>

          <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
            <span id="pgxValidPill" class="pgx-validPill d-none">
              <i class="fa-solid fa-circle-check"></i> Launch Ready ✅ (Path reaches last column)
            </span>
          </div>

        </div>
      </div>

      {{-- Right: Info Panel --}}
      <div class="pgx-side">
        <div class="pgx-card">
          <div class="pgx-panel">
            <div class="hd">
              <div class="t"><i class="fa-solid fa-circle-info"></i> Status</div>
              <span class="badge text-bg-secondary" id="pgxRunState">Ready</span>
            </div>

            <div class="pgx-miniStats">
              <div class="pgx-stat">
                <div class="k">Rotations</div>
                <div class="v" id="pgxRotCount">0</div>
              </div>
              <div class="pgx-stat">
                <div class="k">Launch Ready</div>
                <div class="v" id="pgxValidText">No</div>
              </div>
              <div class="pgx-stat">
                <div class="k">Time (ms)</div>
                <div class="v" id="pgxTimeMs">0</div>
              </div>
              <div class="pgx-stat">
                <div class="k">Path Len</div>
                <div class="v" id="pgxPathLen">0</div>
              </div>
            </div>

            <div class="d-flex gap-10 flex-wrap mt-2" style="gap:10px">
              <button class="pgx-btn2 primary w-100" id="pgxSubmitBtn" type="button" disabled>
                <i class="fa-solid fa-paper-plane"></i> Submit
              </button>
              <button class="pgx-btn2 w-100" id="pgxResetBtn" type="button">
                <i class="fa-solid fa-rotate-left"></i> Reset
              </button>
            </div>

            <div class="pgx-note mt-1">
              ✔ Arrange arrows so path reaches LAST column.<br>
              ✔ Then Launch Rocket button enables.<br>
              ✔ Rocket auto lands + confetti 🎉
            </div>
          </div>
        </div>

        <div class="pgx-card">
          <div class="pgx-panel">
            <div class="hd">
              <div class="t"><i class="fa-solid fa-lightbulb"></i> Note</div>
              <span class="badge text-bg-light">Tip</span>
            </div>
            <div class="pgx-note">
              Tile badge is hidden by default — hover any mini cell inside a tile to show it.<br>
              If a tile is locked, rotate button will stay disabled for that tile.
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- ✅ CONFETTI --}}
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- ✅ CONFETTI --}}
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>

<script>
  /* ========== Fullscreen & Violation System ========== */
let pgxViolationCount = 0;
let pgxIsFullscreen   = false;
let pgxTabLogged      = false;
let pgxMonitoring     = false;

function pgxRequestFullscreen(){
  const el = document.documentElement;
  if(el.requestFullscreen) el.requestFullscreen().catch(()=>{});
  else if(el.webkitRequestFullscreen) el.webkitRequestFullscreen();
  else if(el.msRequestFullscreen) el.msRequestFullscreen();
}

function pgxUpdateFullscreen(){
  pgxIsFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
}

document.addEventListener('visibilitychange', ()=>{
  if(!pgxMonitoring) return;
  if(document.hidden){
    if(pgxTabLogged) return;
    pgxTabLogged = true;
    pgxViolationCount++;
    document.getElementById('pgxBadgeCount').textContent = pgxViolationCount;
    document.getElementById('pgxViolationBadge').style.display = 'block';
    document.getElementById('pgxViolationCount').textContent = pgxViolationCount;
    document.getElementById('pgxViolationOverlay').style.display = 'flex';
  } else {
    pgxTabLogged = false;
  }
});

document.addEventListener('fullscreenchange', ()=>{
  pgxUpdateFullscreen();
  if(!pgxIsFullscreen && pgxMonitoring) pgxRequestFullscreen();
});
document.addEventListener('webkitfullscreenchange', pgxUpdateFullscreen);

document.addEventListener('mouseover', ()=>{ if(pgxMonitoring && !pgxIsFullscreen) pgxRequestFullscreen(); });
document.addEventListener('click',     ()=>{ if(pgxMonitoring && !pgxIsFullscreen) pgxRequestFullscreen(); });
document.addEventListener('contextmenu',(e)=>{ if(!pgxMonitoring) return; e.preventDefault(); if(!pgxIsFullscreen) pgxRequestFullscreen(); });
document.addEventListener('keydown',(e)=>{
  if(!pgxMonitoring) return;
  if(e.key==='F11'){ e.preventDefault(); return; }
  if((e.ctrlKey||e.metaKey) && ['w','t','n'].includes(e.key.toLowerCase())){ e.preventDefault(); return; }
  if(e.altKey && e.key==='Tab'){ e.preventDefault(); return; }
});

(() => {
  /* =========================================================
    PATH GAME PLAY SCRIPT (Updated + Replay v2 Patch)
    ✅ Launch button enabled only if path reaches LAST COLUMN
    ✅ Rocket launch + travel sound
    ✅ Start icon hides when launched
    ✅ Auto land on earth + confetti + sound
    ✅ Replay v2 timeline + snapshots
    ✅ Fixed submit API: /api/path-games/{uuid}/submit
    ✅ Refresh-safe timeTaken via startedAtWall
    ✅ Debounced refreshLaunchReady for smoother UI
  ========================================================= */

  const $ = (id)=>document.getElementById(id);
  const sleep = (ms)=> new Promise(r => setTimeout(r, ms));

  function getGameUuidFromUrl(){
    const p = new URLSearchParams(location.search);
    return (p.get('game') || p.get('uuid') || p.get('game_uuid') || '').trim();
  }

  const GAME_UUID = getGameUuidFromUrl();
  const TOKEN = localStorage.getItem('token') || sessionStorage.getItem('token') || '';

  // ✅ FIXED SUBMIT API ROUTE
  const API = {
    game:   `/api/path-games/${encodeURIComponent(GAME_UUID)}`,
    submit: `/api/path-game-results/${encodeURIComponent(GAME_UUID)}/submit`
  };

  const CACHE_KEY = `pg_exam_${GAME_UUID}`;

  // UI refs
  const elTitle = $('pgxGameTitle');
  const elDim = $('pgxDim');
  const elRotTiles = $('pgxRotTiles');
  const elInstr = $('pgxInstruction');

  const elTimeLeft = $('pgxTimeLeft');
  const elTimeBar  = $('pgxTimeBar');

  const elRunState = $('pgxRunState');
  const elRotCount = $('pgxRotCount');
  const elValidText= $('pgxValidText');
  const elTimeMs   = $('pgxTimeMs');
  const elPathLen  = $('pgxPathLen');

  const elBoard = $('pgxBoard');
  const elTilesLayer = $('pgxTilesLayer');
  const elBoardWrap = $('pgxBoardWrap');

  const elStartNode = $('pgxStartNode');
  const elStartIcon = $('pgxStartIcon');
  const elEndNode   = $('pgxEndNode');
  const elRocket    = $('pgxRocket');

  // ✅ D-Pad button refs
  const elStartUp    = $('pgxStartUp');
  const elStartDown  = $('pgxStartDown');
  const elStartLeft  = $('pgxStartLeft');
  const elStartRight = $('pgxStartRight');

  const elValidateBtn = $('pgxValidateBtn');
  const elSubmitBtn = $('pgxSubmitBtn');
  const elResetBtn  = $('pgxResetBtn');

  const elValidPill = $('pgxValidPill');

  // ✅ Global rotate controls
  const elGlobalRotateBtn = $('pgxGlobalRotateBtn');
  const elSelTileHint     = $('pgxSelTileHint');

  // ✅ Launch & Land controls
  const elLaunchBtn = $('pgxLaunchBtn');
  const elLandBtn   = $('pgxLandBtn');

  // Intro modal refs
  const introEl = $('pgxIntroModal');
  const introTitleEl = $('pgxIntroTitle');
  const introDescEl  = $('pgxIntroDesc');
  const introInstrEl = $('pgxIntroInstr');
  const introDimEl   = $('pgxIntroDim');
  const introTimeEl  = $('pgxIntroTime');
  const introBackBtn = $('pgxIntroBack');
  const introStartBtn= $('pgxIntroStart');

  const Toast = Swal.mixin({
    toast:true,
    position:'bottom-end',
    showConfirmButton:false,
    timer: 2400,
    timerProgressBar:true
  });
  const notify = (icon,title,text='') => Toast.fire({icon,title,text});

  if(!TOKEN){
    Swal.fire('Login needed','Your session expired. Please login again.','warning')
      .then(()=> location.href='/');
    return;
  }

  if(!GAME_UUID){
    Swal.fire('Game UUID missing','Open page like ?game=<uuid>','error');
    return;
  }

  const MINI = 3;
  let GAME_ACTIVE = false;
  let tick = null;

  let state = {
    meta: null,
    N: 3,
    tiles: [],
    time_limit_sec: 60,
    timeLeft: 60,

    startedAtMs: 0,      // perf clock
    startedAtWall: 0,    // ✅ wall clock timestamp for refresh-safe timing

    rotationsTotal: 0,
    rotationLog: [],

    // ✅ Replay v2
    replay: {
      version: "2.0",
      timeline: [],
      state_snapshots: [],
      initial_deg_by_index: {},
      final_deg_by_index: {}
    },

    lastValidation: null,
    valid: false,

    isSubmitting: false,

    // ✅ selected tile for global rotate
    selectedTile: null,

    // ✅ start row move
    startRow: null,

    // ✅ prevent multi auto-submit
    autoSubmitted: false,

    // ✅ Launch flow
    launchReady: false,
    launchRes: null,
    rocketLaunched: false,
    rocketLanded: false,
    landingIdx: null,
  };

  function nowMs(){ return Math.round(performance.now()); }
  function stopTick(){ if(tick){ clearInterval(tick); tick=null; } }

  /* =========================
    ✅ Replay Helpers
  ========================= */
  function relTms(){
    if(!state.startedAtMs) return 0;
    return Math.max(0, nowMs() - state.startedAtMs);
  }

  function pushTimeline(type, payload = {}){
    state.replay.timeline.push({
      id: state.replay.timeline.length + 1,
      type,
      t_ms: relTms(),
      payload
    });
  }

  function buildDegByIndex(){
    const mp = {};
    for(const t of state.tiles || []){
      mp[String(t.grid_index)] = (Number(t.rotation_count || 0) * 90) % 360;
    }
    return mp;
  }

  function ensureLaunchRes(){
    if(state.launchRes && typeof state.launchRes === 'object') return state.launchRes;
    const r = validatePathToLastColumn();
    state.launchRes = r;
    return r;
  }

  function pushSnapshot(){
    const r = ensureLaunchRes();
    const snap = {
      at_t_ms: relTms(),
      current_index: Number(r?.endIdx || 0),
      path: Array.isArray(r?.path) ? r.path.map(Number) : [],
      deg_by_index: buildDegByIndex(),
      inventory: { has_key: false }
    };
    state.replay.state_snapshots.push(snap);
  }

  /* =========================
    ✅ Debounced refreshLaunchReady
  ========================= */
  let launchDebounce = null;
  function refreshLaunchReadyDebounced(){
    clearTimeout(launchDebounce);
    launchDebounce = setTimeout(refreshLaunchReady, 90);
  }

  function renderTimer(){
    elTimeLeft.textContent = String(state.timeLeft);
    const pct = state.time_limit_sec > 0 ? (state.timeLeft/state.time_limit_sec)*100 : 0;
    elTimeBar.style.width = `${Math.max(0, Math.min(100, pct))}%`;
  }

  function setRunState(text, tone='secondary'){
    elRunState.className = `badge text-bg-${tone}`;
    elRunState.textContent = text;
  }

  function startTimer(){
    stopTick();
    renderTimer();
    tick = setInterval(() => {
      if(!GAME_ACTIVE) return;

      state.timeLeft = Math.max(0, Number(state.timeLeft || 0) - 1);
      renderTimer();
      saveCache();

      if(state.timeLeft <= 0){
        stopTick();
        setRunState('Timeout','danger');

        if(state.autoSubmitted) return;
        state.autoSubmitted = true;
        saveCache();

        submitAttempt(true);
      }
    }, 1000);
  }

  function saveCache(){
    const payload = {
      meta: state.meta,
      N: state.N,
      tiles: state.tiles,
      time_limit_sec: state.time_limit_sec,
      timeLeft: state.timeLeft,

      startedAtMs: state.startedAtMs,
      startedAtWall: state.startedAtWall,

      rotationsTotal: state.rotationsTotal,
      rotationLog: state.rotationLog,

      // ✅ save replay too
      replay: state.replay,

      lastValidation: state.lastValidation,
      valid: state.valid,
      selectedTile: state.selectedTile,
      startRow: state.startRow,
      autoSubmitted: state.autoSubmitted,

      launchReady: state.launchReady,
      launchRes: state.launchRes,
      rocketLaunched: state.rocketLaunched,
      rocketLanded: state.rocketLanded,
      landingIdx: state.landingIdx,

      savedAt: Date.now()
    };
    sessionStorage.setItem(CACHE_KEY, JSON.stringify(payload));
  }

  function loadCache(){
    try{
      const raw = sessionStorage.getItem(CACHE_KEY);
      if(!raw) return false;
      const p = JSON.parse(raw);
      if(!p || !p.tiles || !Array.isArray(p.tiles)) return false;

      state.meta = p.meta || null;
      state.N = Number(p.N || 3);
      state.tiles = p.tiles || [];
      state.time_limit_sec = Number(p.time_limit_sec || 60);

      const savedAt = Number(p.savedAt || Date.now());
      const elapsed = Math.floor((Date.now() - savedAt)/1000);
      const tl = Number(p.timeLeft ?? state.time_limit_sec);
      state.timeLeft = Math.max(0, tl - elapsed);

      state.startedAtMs = Number(p.startedAtMs || 0);
      state.startedAtWall = Number(p.startedAtWall || 0);

      state.rotationsTotal = Number(p.rotationsTotal || 0);
      state.rotationLog = Array.isArray(p.rotationLog) ? p.rotationLog : [];

      // ✅ restore replay
      state.replay = (p.replay && typeof p.replay === 'object')
        ? {
            version: String(p.replay.version || "2.0"),
            timeline: Array.isArray(p.replay.timeline) ? p.replay.timeline : [],
            state_snapshots: Array.isArray(p.replay.state_snapshots) ? p.replay.state_snapshots : [],
            initial_deg_by_index: p.replay.initial_deg_by_index || {},
            final_deg_by_index: p.replay.final_deg_by_index || {}
          }
        : {
            version:"2.0", timeline:[], state_snapshots:[], initial_deg_by_index:{}, final_deg_by_index:{}
          };

      state.lastValidation = p.lastValidation || null;
      state.valid = !!p.valid;

      state.selectedTile = p.selectedTile ?? null;
      state.startRow = Number.isFinite(p.startRow) ? Number(p.startRow) : null;
      state.autoSubmitted = !!p.autoSubmitted;

      state.launchReady = !!p.launchReady;
      state.launchRes = p.launchRes || null;
      state.rocketLaunched = !!p.rocketLaunched;
      state.rocketLanded = !!p.rocketLanded;
      state.landingIdx = Number.isFinite(p.landingIdx) ? Number(p.landingIdx) : null;

      return true;
    }catch(e){
      return false;
    }
  }

  function clearCache(){ sessionStorage.removeItem(CACHE_KEY); }

  async function fetchJson(url){
    const res = await fetch(url, {
      headers:{
        'Authorization':'Bearer '+TOKEN,
        'Accept':'application/json',
        'X-Requested-With':'XMLHttpRequest'
      }
    });
    const json = await res.json().catch(()=> ({}));
    if(!res.ok) throw new Error(json?.message || ('HTTP '+res.status));
    return (json?.data !== undefined) ? json.data : json;
  }

  async function postJson(url, payload){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Authorization':'Bearer '+TOKEN,
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-Requested-With':'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify(payload)
    });
    const json = await res.json().catch(()=> ({}));
    if(!res.ok) throw new Error(json?.message || ('HTTP '+res.status));
    return json;
  }

  /* ========= Sound Effects (WebAudio) ========= */
  let AC = null;
  let travelNode = null;

  function audioCtx(){
    try{
      if(!AC) AC = new (window.AudioContext || window.webkitAudioContext)();
      return AC;
    }catch(e){
      return null;
    }
  }

  function playLaunchSound(){
    const ctx = audioCtx();
    if(!ctx) return;
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'sawtooth';
    o.frequency.setValueAtTime(180, ctx.currentTime);
    o.frequency.exponentialRampToValueAtTime(620, ctx.currentTime + 0.35);
    g.gain.setValueAtTime(0.0001, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.22, ctx.currentTime + 0.04);
    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.55);
    o.connect(g); g.connect(ctx.destination);
    o.start();
    o.stop(ctx.currentTime + 0.58);
  }

  function startTravelSound(){
    const ctx = audioCtx();
    if(!ctx) return;
    stopTravelSound();
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'triangle';
    o.frequency.setValueAtTime(260, ctx.currentTime);
    g.gain.setValueAtTime(0.0001, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.08, ctx.currentTime + 0.08);
    o.connect(g); g.connect(ctx.destination);
    o.start();
    travelNode = { o, g };
  }

  function stopTravelSound(){
    try{
      const ctx = audioCtx();
      if(!ctx || !travelNode) return;
      const { o, g } = travelNode;
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.08);
      o.stop(ctx.currentTime + 0.10);
      travelNode = null;
    }catch(e){}
  }

  function playLandSound(){
    const ctx = audioCtx();
    if(!ctx) return;
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'sine';
    o.frequency.setValueAtTime(420, ctx.currentTime);
    o.frequency.exponentialRampToValueAtTime(260, ctx.currentTime + 0.18);
    g.gain.setValueAtTime(0.0001, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.03);
    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.26);
    o.connect(g); g.connect(ctx.destination);
    o.start();
    o.stop(ctx.currentTime + 0.30);
  }

  function playWinSound(){
    const ctx = audioCtx();
    if(!ctx) return;
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'square';
    o.frequency.setValueAtTime(520, ctx.currentTime);
    o.frequency.setValueAtTime(660, ctx.currentTime + 0.10);
    o.frequency.setValueAtTime(840, ctx.currentTime + 0.20);
    g.gain.setValueAtTime(0.0001, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.14, ctx.currentTime + 0.03);
    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
    o.connect(g); g.connect(ctx.destination);
    o.start();
    o.stop(ctx.currentTime + 0.38);
  }

  function confettiBurst(){
    try{
      if(typeof confetti !== 'function') return;
      confetti({ particleCount: 180, spread: 85, origin: { y: 0.65 } });
      setTimeout(()=> confetti({ particleCount: 120, spread: 70, origin:{y:0.55} }), 220);
    }catch(e){}
  }

  /* ========= Helpers: rotation math ========= */
  function normArrow(a){
    const v = String(a||'').toUpperCase().trim();
    if(v === 'T') return 'U';
    if(v === 'B') return 'D';
    return v;
  }

  function arrowToIcon(v){
    const a = normArrow(v);
    if(!a) return `<span style="opacity:.25;font-size:12px;">•</span>`;
    if(a==='U') return `<i class="fa-solid fa-arrow-up-long"></i>`;
    if(a==='D') return `<i class="fa-solid fa-arrow-down-long"></i>`;
    if(a==='L') return `<i class="fa-solid fa-arrow-left-long"></i>`;
    return `<i class="fa-solid fa-arrow-right-long"></i>`;
  }

  function rotateDirCw(a){
    const v = normArrow(a);
    if(v==='U') return 'R';
    if(v==='R') return 'D';
    if(v==='D') return 'L';
    if(v==='L') return 'U';
    return '';
  }
  function rotateDirCcw(a){
    const v = normArrow(a);
    if(v==='U') return 'L';
    if(v==='L') return 'D';
    if(v==='D') return 'R';
    if(v==='R') return 'U';
    return '';
  }

  function rotateTileCells(tileCells, direction){
    const mat = Array.from({length:3}, ()=> Array.from({length:3}, ()=> ({ arrow:'' })));
    for(let i=0;i<9;i++){
      const r = Math.floor(i/3), c = i%3;
      mat[r][c] = { arrow: normArrow(tileCells[i]?.arrow || '') };
    }

    const out = Array.from({length:3}, ()=> Array.from({length:3}, ()=> ({ arrow:'' })));

    if(direction === 'cw'){
      for(let r=0;r<3;r++){
        for(let c=0;c<3;c++){
          out[r][c] = { arrow: mat[2-c][r].arrow };
        }
      }
      for(let r=0;r<3;r++){
        for(let c=0;c<3;c++){
          out[r][c].arrow = out[r][c].arrow ? rotateDirCw(out[r][c].arrow) : '';
        }
      }
    }else{
      for(let r=0;r<3;r++){
        for(let c=0;c<3;c++){
          out[r][c] = { arrow: mat[c][2-r].arrow };
        }
      }
      for(let r=0;r<3;r++){
        for(let c=0;c<3;c++){
          out[r][c].arrow = out[r][c].arrow ? rotateDirCcw(out[r][c].arrow) : '';
        }
      }
    }

    const flat = [];
    for(let r=0;r<3;r++){
      for(let c=0;c<3;c++){
        flat.push({
          index: (r*3 + c + 1),
          arrow: out[r][c].arrow || null
        });
      }
    }
    return flat;
  }

  /* ========= Grid math ========= */
  function globalMiniIndex(tileIndex, cellIndex){
    const N = state.N;
    const M = N * MINI;

    const tile0 = tileIndex - 1;
    const tr = Math.floor(tile0 / N);
    const tc = tile0 % N;

    const c0 = cellIndex - 1;
    const lr = Math.floor(c0 / MINI);
    const lc = c0 % MINI;

    const gr = tr*MINI + lr;
    const gc = tc*MINI + lc;

    return (gr * M) + gc + 1;
  }

  function getGlobalArrowMap(){
    const N = state.N;
    const M = N * MINI;

    const mp = new Map();
    for(const t of state.tiles){
      const tileIndex = Number(t.grid_index);
      const list = Array.isArray(t.tiles) ? t.tiles : [];
      for(let i=0;i<9;i++){
        const idx = globalMiniIndex(tileIndex, i+1);
        const a = normArrow(list[i]?.arrow || '');
        mp.set(idx, a);
      }
    }
    return { M, mp };
  }

  function moveGlobal(idx, dir, M){
    const i0 = idx - 1;
    const r = Math.floor(i0 / M);
    const c = i0 % M;

    if(dir==='U') return (r>0) ? (idx - M) : null;
    if(dir==='D') return (r<M-1) ? (idx + M) : null;
    if(dir==='L') return (c>0) ? (idx - 1) : null;
    if(dir==='R') return (c<M-1) ? (idx + 1) : null;
    return null;
  }

  // ✅ startRow controlled by D-Pad
  function getStartIdx(M){
    let sr = Number.isFinite(state.startRow) ? Number(state.startRow) : Math.floor(M/2);
    sr = Math.max(0, Math.min(M - 1, sr));
    state.startRow = sr;
    const startIdx = sr * M + 1;
    return { startRow: sr, startIdx };
  }

  function updateStartNavUI(){
    if(!state.tiles?.length) return;
    const { M } = getGlobalArrowMap();
    const sr = Number.isFinite(state.startRow) ? Number(state.startRow) : Math.floor(M/2);

    if(elStartUp)   elStartUp.disabled   = (sr <= 0);
    if(elStartDown) elStartDown.disabled = (sr >= M-1);

    if(elStartLeft)  elStartLeft.disabled  = false;
    if(elStartRight) elStartRight.disabled = false;

    if(elStartNode) elStartNode.title = `Start Row: ${sr + 1}`;
  }

  function setStartRow(nextRow){
    if(!state.tiles?.length) return;
    const { M } = getGlobalArrowMap();

    const r = Math.max(0, Math.min(M - 1, Number(nextRow)));
    state.startRow = r;

    state.valid = false;
    state.lastValidation = null;

    clearPathHighlights();
    setRocketVisible(false);

    positionEdgeNodes();
    updateStartNavUI();
    updateSidebar();
    refreshLaunchReadyDebounced();
    saveCache();
  }

  if(elStartUp){
    elStartUp.addEventListener('click', (e)=>{
      e.preventDefault(); e.stopPropagation();
      setStartRow((state.startRow ?? 0) - 1);
    });
  }
  if(elStartDown){
    elStartDown.addEventListener('click', (e)=>{
      e.preventDefault(); e.stopPropagation();
      setStartRow((state.startRow ?? 0) + 1);
    });
  }
  if(elStartLeft){
    elStartLeft.addEventListener('click', (e)=>{
      e.preventDefault(); e.stopPropagation();
      setStartRow((state.startRow ?? 0) - 3);
    });
  }
  if(elStartRight){
    elStartRight.addEventListener('click', (e)=>{
      e.preventDefault(); e.stopPropagation();
      setStartRow((state.startRow ?? 0) + 3);
    });
  }

  /* =========================================================
    ✅ VALIDATION FOR LAUNCH:
    "Valid if the arrow-trail reaches ANY cell of the LAST column"
  ========================================================= */
  function validatePathToLastColumn(){
    const { M, mp } = getGlobalArrowMap();
    const { startIdx } = getStartIdx(M);

    const MAX_STEPS = M*M + 10;
    const visited = new Set();
    const path = [];

    let cur = startIdx;

    for(let step=0; step<MAX_STEPS; step++){
      if(visited.has(cur)){
        return { ok:false, path, reason:'Loop detected' };
      }
      visited.add(cur);
      path.push(cur);

      const col = (cur - 1) % M;
      const row = Math.floor((cur - 1) / M);

      // ✅ Success condition: REACH LAST COLUMN ANY ROW
      if(col === (M - 1)){
        return { ok:true, path, reason:'Reached last column ✅', endIdx: cur, endRow: row, M };
      }

      const a = normArrow(mp.get(cur) || '');
      if(!a){
        return { ok:false, path, reason:'Missing arrow in path' };
      }

      const nxt = moveGlobal(cur, a, M);
      if(!nxt){
        return { ok:false, path, reason:'Arrow goes out of boundary' };
      }

      cur = nxt;
    }

    return { ok:false, path, reason:'Too long / infinite path' };
  }

  /* ========= Highlights ========= */
  function clearPathHighlights(){
    elBoard.querySelectorAll('.pgx-miniCell').forEach(c=>{
      c.classList.remove('is-path','is-dead');
    });
  }

  function markPath(path, ok){
    const set = new Set((path||[]).map(x=>String(x)));
    elBoard.querySelectorAll('.pgx-miniCell').forEach(c=>{
      const g = c.dataset.global;
      if(set.has(g)){
        c.classList.add(ok ? 'is-path' : 'is-dead');
      }
    });
  }

  /* ========= Rocket positioning helpers ========= */
  function getMiniCellCenter(globalIdx){
    const el = elBoard.querySelector(`.pgx-miniCell[data-global="${globalIdx}"]`);
    if(!el) return null;

    const r1 = el.getBoundingClientRect();
    const r2 = elBoard.getBoundingClientRect();

    const cx = (r1.left + r1.right)/2 - r2.left;
    const cy = (r1.top + r1.bottom)/2 - r2.top;
    return { x: cx, y: cy };
  }

  function getElementCenterInBoard(el){
    if(!el) return null;
    const r1 = el.getBoundingClientRect();
    const r2 = elBoard.getBoundingClientRect();
    return {
      x: ((r1.left + r1.right) / 2) - r2.left,
      y: ((r1.top + r1.bottom) / 2) - r2.top
    };
  }

  function setRocketVisible(on){
    elRocket.classList.toggle('show', !!on);
  }

  function setRocketFiring(on, launch=false){
    elRocket.classList.toggle('is-firing', !!on);
    elRocket.classList.toggle('is-launch', !!launch);
  }

  function placeRocketAt(globalIdx){
    const p = getMiniCellCenter(globalIdx);
    if(!p) return false;
    setRocketVisible(true);
    elRocket.style.left = p.x + 'px';
    elRocket.style.top  = p.y + 'px';
    return true;
  }

  function ensureVisible(pos){
    if(!pos) return;
    const pad = 110;

    const viewL = elBoardWrap.scrollLeft;
    const viewT = elBoardWrap.scrollTop;
    const viewR = viewL + elBoardWrap.clientWidth;
    const viewB = viewT + elBoardWrap.clientHeight;

    const x = pos.x;
    const y = pos.y;

    let nextL = viewL;
    let nextT = viewT;

    if(x < viewL + pad) nextL = Math.max(0, x - pad);
    if(x > viewR - pad) nextL = Math.max(0, x - (elBoardWrap.clientWidth - pad));
    if(y < viewT + pad) nextT = Math.max(0, y - pad);
    if(y > viewB - pad) nextT = Math.max(0, y - (elBoardWrap.clientHeight - pad));

    if(nextL !== viewL || nextT !== viewT){
      elBoardWrap.scrollTo({ left: nextL, top: nextT, behavior: 'smooth' });
    }
  }

  async function animateRocketToIndex(path, stopAtGlobalIdx=null, speedMs=110){
    if(!path || !path.length) return;

    // ✅ no rocket movement until trail ready OR already launched
    if(!state.launchReady && !state.rocketLaunched){
      return;
    }

    setRocketVisible(true);
    setRocketFiring(true, false);

    const steps = path.slice();
    startTravelSound();

    for(let i=0;i<steps.length;i++){
      const pos = getMiniCellCenter(steps[i]);
      if(pos){
        elRocket.style.left = pos.x + 'px';
        elRocket.style.top  = pos.y + 'px';
        ensureVisible(pos);
      }
      await sleep(speedMs);

      if(stopAtGlobalIdx && Number(steps[i]) === Number(stopAtGlobalIdx)){
        break;
      }
    }

    stopTravelSound();
    setTimeout(()=> setRocketFiring(false,false), 320);
  }

  async function rocketLaunchSequence(){
    const { M } = getGlobalArrowMap();
    const { startIdx } = getStartIdx(M);

    placeRocketAt(startIdx);
    setRocketVisible(true);
    setRocketFiring(true, true);

    playLaunchSound();
    await sleep(750);

    setRocketFiring(true, false);
    setTimeout(()=> setRocketFiring(false,false), 650);
  }

  function positionEdgeNodes(){
    try{
      const { M } = getGlobalArrowMap();
      const { startIdx } = getStartIdx(M);

      const sp = getMiniCellCenter(startIdx);
      const rect = elBoard.getBoundingClientRect();
      const W = rect.width;

      if(sp){
        const sx = Math.max(40, Math.min(W - 40, sp.x - 70));
        elStartNode.style.left = sx + 'px';
        elStartNode.style.top  = sp.y + 'px';
      }
    }catch(e){}
  }

  // ✅ Earth positioning fix
  function positionEarthAtGlobalIdx(globalIdx){
    try{
      const p = getMiniCellCenter(globalIdx);
      if(!p) return;

      const rect = elBoard.getBoundingClientRect();
      const W = rect.width;

      const ex = Math.max(40, Math.min(W - 40, p.x + 70));

      elEndNode.style.right = 'auto';
      elEndNode.style.left  = ex + 'px';
      elEndNode.style.top   = p.y + 'px';
    }catch(e){}
  }

  /* ========= Global rotate helpers ========= */
  function canRotateTile(t){
    if(!t || !t.rotatable) return false;
    const lim = Number(t.rotation_limit || 0);
    const done = Number(t.rotation_count || 0);
    if(lim > 0 && done >= lim) return false;
    return true;
  }

  function updateGlobalRotateUI(){
    const sel = Number(state.selectedTile || 0);

    if(!sel){
      elGlobalRotateBtn.disabled = true;
      elGlobalRotateBtn.innerHTML = `<i class="fa-solid fa-rotate"></i> Rotate Selected Tile`;
      elSelTileHint.innerHTML = `<i class="fa-solid fa-hand-pointer"></i> Select a tile first`;
      return;
    }

    const t = state.tiles.find(x => Number(x.grid_index) === sel);
    const ok = canRotateTile(t);

    const rotType = String(t?.rotation_type || 'cw').toLowerCase();
    const dirText = (rotType === 'ccw') ? 'Rotate Left' : 'Rotate Right';

    elGlobalRotateBtn.disabled = !ok;

    if(ok){
      elGlobalRotateBtn.innerHTML = `<i class="fa-solid fa-rotate"></i> ${dirText}`;
      elSelTileHint.innerHTML = `<i class="fa-solid fa-layer-group"></i> Selected: #${sel} • Rot: ${Number(t?.rotation_count || 0)}`;
    }else{
      elGlobalRotateBtn.innerHTML = `<i class="fa-solid fa-lock"></i> Tile #${sel} Locked`;
      elSelTileHint.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> Selected: #${sel} (Not rotatable)`;
    }
  }

  function setSelectedTile(tileIndex){
    state.selectedTile = Number(tileIndex);

    elTilesLayer.querySelectorAll('.pgx-tile').forEach(t=>{
      t.classList.toggle('is-selected', t.dataset.tile === String(state.selectedTile));
    });

    updateGlobalRotateUI();
    saveCache();
  }

  /* ========= Launch button logic ========= */
  function refreshLaunchReady(){
    if(state.rocketLaunched){
      state.launchReady = false;
      elLaunchBtn.disabled = true;
      elValidPill.classList.add('d-none');
      elValidText.textContent = 'No';
      saveCache();
      return;
    }

    if(!GAME_ACTIVE){
      state.launchReady = false;
      elLaunchBtn.disabled = true;
      elValidPill.classList.add('d-none');
      elValidText.textContent = 'No';
      saveCache();
      return;
    }

    const res = validatePathToLastColumn();
    state.launchRes = res;
    state.launchReady = !!res.ok;

    elLaunchBtn.disabled = !state.launchReady;
    elValidPill.classList.toggle('d-none', !state.launchReady);

    elValidText.textContent = state.launchReady ? 'Yes' : 'No';
    elPathLen.textContent = String(res?.path?.length || 0);

    saveCache();
  }

  async function doAutoLand(endIdx){
    state.rocketLanded = true;
    state.landingIdx = Number(endIdx || 0);

    positionEarthAtGlobalIdx(endIdx);

    const earthPos = getElementCenterInBoard(elEndNode);
    if(earthPos){
      elRocket.style.left = earthPos.x + 'px';
      elRocket.style.top  = earthPos.y + 'px';
      ensureVisible(earthPos);
      await sleep(160);
    }

    playLandSound();
    playWinSound();
    confettiBurst();

    // ✅ Replay Events
    pushTimeline("event", { name: "earth_reached", at_index: Number(endIdx) });
    pushSnapshot();

    pushTimeline("finish", { result: "win", current_index: Number(endIdx) });
    pushSnapshot();

    // ✅ store final deg snapshot
    state.replay.final_deg_by_index = buildDegByIndex();

    setRunState('Landed 🎉','success');
    notify('success','Mission Complete 🚀','Rocket landed on Earth!');

    elSubmitBtn.disabled = false;
    elLandBtn.disabled = true;

    saveCache();
  }

  async function launchRocket(){
    if(!GAME_ACTIVE){
      notify('warning','Not started','Start the game first.');
      return;
    }
    if(state.rocketLaunched){
      notify('info','Already launched','Rocket is already launched.');
      return;
    }

    refreshLaunchReady();
    if(!state.launchReady){
      notify('error','Not Ready','Arrange path till last column to enable launch.');
      return;
    }

    const res = state.launchRes;
    if(!res?.ok){
      notify('error','Invalid','Path is not ready for launch.');
      return;
    }

    state.rocketLaunched = true;
    state.rocketLanded = false;

    if(elStartIcon) elStartIcon.style.display = 'none';

    setRunState('Launching…','primary');
    elLaunchBtn.disabled = true;
    elLandBtn.disabled = true;

    // ✅ Replay launch event
    pushTimeline("event", { name: "rocket_launch" });
    pushSnapshot();

    await rocketLaunchSequence();

    setRunState('Flying…','secondary');
    setRocketFiring(true, false);

    elLandBtn.disabled = false;

    await animateRocketToIndex(res.path, res.endIdx, 110);

    await doAutoLand(res.endIdx);
  }

  elLaunchBtn.addEventListener('click', async (e)=>{
    e.preventDefault();
    e.stopPropagation();
    await launchRocket();
  });

  elLandBtn.addEventListener('click', async (e)=>{
    e.preventDefault();
    e.stopPropagation();
    if(!state.rocketLaunched || state.rocketLanded) return;
    if(!state.launchRes?.endIdx){
      notify('warning','No landing point','Make path reach last column first.');
      return;
    }
    await doAutoLand(state.launchRes.endIdx);
  });

  /* ========= Render board ========= */
  function renderBoard(){
    elBoard.style.setProperty('--N', String(state.N));
    elTilesLayer.style.setProperty('--N', String(state.N));
    elTilesLayer.innerHTML = '';

    const total = state.N * state.N;

    for(let i=0;i<total;i++){
      const t = state.tiles[i];
      const tileIndex = Number(t.grid_index || (i+1));

      const tile = document.createElement('div');
      tile.className = 'pgx-tile';
      tile.dataset.tile = String(tileIndex);

      const badge = document.createElement('div');
      badge.className = 'tile-badge d-none ' + (t.rotatable ? '' : 'locked');
      badge.dataset.tile = String(tileIndex);

      const iconClass = t.rotatable
        ? (String(t.rotation_type||'cw').toLowerCase()==='ccw' ? 'fa-rotate-left' : 'fa-rotate-right')
        : 'fa-lock';

      const count = Number(t.rotation_count || 0);

      badge.innerHTML = `
        <span>#${tileIndex}</span>
        <span class="rot"><i class="fa-solid ${iconClass}"></i></span>
        <span class="count">${count ? `x${count}` : ''}</span>
      `;
      tile.appendChild(badge);

      const mini = document.createElement('div');
      mini.className = 'pgx-miniGrid';

      const cells = Array.isArray(t.tiles) ? t.tiles : [];

      for(let ci=0;ci<9;ci++){
        const cell = document.createElement('div');
        cell.className = 'pgx-miniCell';

        const a = normArrow(cells[ci]?.arrow || '');
        const gIdx = globalMiniIndex(tileIndex, ci+1);
        cell.dataset.global = String(gIdx);

        cell.innerHTML = `<span class="a">${arrowToIcon(a)}</span>`;

        cell.addEventListener('click', async (e)=>{
          e.preventDefault();
          e.stopPropagation();

          setSelectedTile(tileIndex);

          if(!GAME_ACTIVE){
            notify('warning','Not started','Start the game first.');
            return;
          }

          clearPathHighlights();
          const res = validatePathToLastColumn();

          state.lastValidation = res;
          state.valid = !!res.ok;

          markPath(res.path, res.ok);
          updateSidebar();
          refreshLaunchReadyDebounced();

          // ❌ If path NOT ready, do NOT move rocket at all
          if(!res.ok){
            notify('warning','Trail not ready','First arrange full trail till LAST column, then rocket can move.');
            setRocketVisible(false);
            return;
          }

          // ✅ Only now rocket can visit cells (because path reaches last column)
          const k = res.path.indexOf(gIdx);
          if(k >= 0){
            // ✅ Replay move preview event
            pushTimeline("move", {
              step: Number(k),
              to: Number(gIdx),
              allowed: true,
              current_index: Number(gIdx)
            });
            pushSnapshot();

            setRunState('Preview','secondary');
            await animateRocketToIndex(res.path.slice(0, k+1), null, 85);
            setRunState('Playing','primary');
          }else{
            notify('info','Not on trail','Tap a cell that lies on the valid trail.');
          }
        });

        mini.appendChild(cell);
      }

      tile.appendChild(mini);

      tile.addEventListener('click', (e)=>{
        if(e.target.closest('.pgx-miniCell')) return;
        setSelectedTile(tileIndex);
      });

      tile.addEventListener('mouseenter', ()=> badge.classList.remove('d-none'));
      tile.addEventListener('mouseleave', ()=> badge.classList.add('d-none'));

      elTilesLayer.appendChild(tile);
    }

    if(state.selectedTile){
      elTilesLayer.querySelectorAll('.pgx-tile').forEach(t=>{
        t.classList.toggle('is-selected', t.dataset.tile === String(state.selectedTile));
      });
    }

    updateGlobalRotateUI();
    positionEdgeNodes();
    updateStartNavUI();
    updateSidebar();
    refreshLaunchReadyDebounced();
  }

  function updateSidebar(){
    elDim.textContent = `${state.N}×${state.N}`;
    if(introDimEl) introDimEl.textContent = `${state.N}×${state.N}`;

    const rotCountTiles = state.tiles.filter(t=> !!t.rotatable).length;
    elRotTiles.textContent = String(rotCountTiles);

    elRotCount.textContent = String(state.rotationsTotal);

    const elapsed = state.startedAtMs ? Math.max(0, nowMs() - state.startedAtMs) : 0;
    elTimeMs.textContent = String(elapsed);

    elSubmitBtn.disabled = !(state.rocketLanded || state.autoSubmitted);

    saveCache();
  }

  /* ========= Tile rotation ========= */
  function rotateTile(tileIndex){
    if(!GAME_ACTIVE) return;
    if(state.rocketLaunched){
      notify('warning','Locked','Cannot rotate after rocket launch.');
      return;
    }

    const t = state.tiles.find(x => Number(x.grid_index) === Number(tileIndex));
    if(!t) return;

    if(!canRotateTile(t)){
      notify('warning','Rotation blocked','This tile cannot be rotated.');
      updateGlobalRotateUI();
      return;
    }

    const rotType = String(t.rotation_type || 'cw').toLowerCase();
    const beforeDeg = (Number(t.rotation_count || 0) * 90) % 360;

    const newTiles = rotateTileCells(t.tiles || [], rotType);

    t.tiles = newTiles.map(x => ({ index: Number(x.index), arrow: x.arrow || null }));
    t.rotation_count = Number(t.rotation_count || 0) + 1;

    const afterDeg = (Number(t.rotation_count || 0) * 90) % 360;

    state.rotationsTotal += 1;

    if(!state.startedAtMs){
      state.startedAtMs = nowMs();
    }
    if(!state.startedAtWall){
      state.startedAtWall = Date.now();
    }

    state.rotationLog.push({
      tile_index: Number(tileIndex),
      rotation_type: rotType,
      rotation_count: Number(t.rotation_count || 0),
      t_ms: Math.max(0, nowMs() - state.startedAtMs)
    });

    // ✅ Replay rotate event
    pushTimeline("rotate", {
      tile_index: Number(tileIndex),
      dir: rotType,
      rotate_by_deg: 90,
      before_deg: Number(beforeDeg),
      after_deg: Number(afterDeg),
      rotation_step: Number(t.rotation_count || 0)
    });
    pushSnapshot();

    state.valid = false;
    state.lastValidation = null;

    renderBoard();
    notify('success','Rotated', `Tile #${tileIndex} rotated (${rotType.toUpperCase()})`);
    refreshLaunchReadyDebounced();
  }

  elGlobalRotateBtn.addEventListener('click', (e)=>{
    e.preventDefault();
    e.stopPropagation();

    if(!GAME_ACTIVE){
      notify('warning','Not started','Start the game first.');
      return;
    }
    if(!state.selectedTile){
      notify('info','No tile selected','Select a tile first.');
      return;
    }
    rotateTile(state.selectedTile);
  });

  /* ========= Validate button ========= */
  elValidateBtn.addEventListener('click', async ()=>{
    if(!GAME_ACTIVE){
      notify('warning','Not started','Start the game first.');
      return;
    }

    setRunState('Validating…','secondary');
    clearPathHighlights();

    const res = validatePathToLastColumn();
    state.lastValidation = res;
    state.valid = !!res.ok;

    markPath(res.path, res.ok);
    refreshLaunchReadyDebounced();

    if(res.ok){
      setRunState('Launch Ready ✅','success');
      notify('success','Valid Path ✅', 'Launch Rocket is enabled!');
    }else{
      setRunState('Invalid ❌','danger');
      notify('error','Invalid Path', res.reason || 'Try rotating tiles.');
    }

    if(res.ok){
      await animateRocketToIndex(res.path, res.endIdx, 100);
    }else{
      setRocketVisible(false);
    }

    saveCache();
  });

  /* ========= Reset ========= */
  elResetBtn.addEventListener('click', async ()=>{
    const r = await Swal.fire({
      icon:'warning',
      title:'Reset attempt?',
      text:'This will reset rotations & validation (game config stays same).',
      showCancelButton:true,
      confirmButtonText:'Yes, reset',
      cancelButtonText:'Cancel',
      confirmButtonColor:'#ef4444'
    });
    if(!r.isConfirmed) return;

    clearCache();
    location.reload();
  });

  /* ========= Submit ========= */
  async function submitAttempt(auto=false){
    if(state.isSubmitting) return;

    clearPathHighlights();
    const res = validatePathToLastColumn();
    state.lastValidation = res;
    state.valid = !!res.ok;
    markPath(res.path, res.ok);
    refreshLaunchReadyDebounced();

    if(!auto){
      const confirm = await Swal.fire({
        icon: (state.rocketLanded ? 'question' : 'warning'),
        title: state.rocketLanded ? 'Confirm Submit' : 'Rocket not landed yet',
        text: state.rocketLanded
          ? 'Submit your final answer?'
          : 'Rocket not landed. You can still submit, but recommended to Launch + Land first. Submit anyway?',
        showCancelButton:true,
        confirmButtonText: 'Submit Now',
        cancelButtonText:'Cancel',
        confirmButtonColor: '#22c55e'
      });
      if(!confirm.isConfirmed) return;
    }

    state.isSubmitting = true;

    try{
      const timeTaken = Math.max(0, Date.now() - (state.startedAtWall || Date.now()));
      const status = auto ? 'timeout' : (state.rocketLanded ? 'win' : (res.ok ? 'fail' : 'fail'));

      // ✅ final rotations snapshot for replay
      state.replay.final_deg_by_index = buildDegByIndex();

      const payload = {
        game_uuid: GAME_UUID,
        path_game_uuid: GAME_UUID,
        status: status,
        time_taken_ms: Number(timeTaken),

        user_answer_json: {
          grid_dim: Number(state.N),
          mini_dim: MINI,
          start_row: Number(state.startRow ?? 0),

          rotations_total: Number(state.rotationsTotal),
          rotation_log: state.rotationLog || [],
          last_validation: res || null,

          final_path: (res?.path || []).map(x => Number(x)),
          reached_last_column: !!res?.ok,
          last_column_cell: Number(res?.endIdx || 0),

          rocket_launched: !!state.rocketLaunched,
          rocket_landed: !!state.rocketLanded,

          // ✅ Replay v2
          replay: state.replay,
          deg_by_index_final: state.replay.final_deg_by_index || buildDegByIndex(),

          submitted_at_ms: Number(timeTaken),
          timed_out: !!auto
        }
      };

      Swal.fire({
        title: auto ? 'Time up! Auto submitting…' : 'Submitting…',
        text:'Please wait',
        allowOutsideClick:false,
        allowEscapeKey:false,
        didOpen: ()=> Swal.showLoading()
      });

      await postJson(API.submit, payload);

      Swal.close();
      clearCache();
            pgxMonitoring = false;
      notify('success','Submitted successfully','Redirecting…');
      setTimeout(()=> location.href='/quizzes', 900);

    }catch(e){
      Swal.close();
      notify('error','Submit failed', e.message || 'Try again');
      elSubmitBtn.disabled = false;
    }finally{
      state.isSubmitting = false;
    }
  }

  elSubmitBtn.addEventListener('click', ()=> submitAttempt(false));

  /* ========= Intro modal + Start gating ========= */
async function openIntroModal(meta){
    await Swal.fire({
      icon: 'info',
      title: meta?.title ? `${meta.title}` : 'Path Game',
      html: `
        <p style="color:#6b7280; margin-bottom:1rem; font-size:.92rem;">
          ${stripHtml(meta?.description || 'Arrange tiles to create a valid path from Rocket to Earth.')}
        </p>
        <ul style="text-align:left; list-style:none; margin:0; padding:0;">
          <li style="display:flex; align-items:flex-start; gap:.6rem; padding:.5rem .75rem;
            border-radius:10px; margin-bottom:.4rem; font-size:.9rem;
            background:#fef2f2; color:#7f1d1d; border:1px solid #fecaca;">
            <i class="fa-solid fa-expand" style="margin-top:.15rem; flex-shrink:0; color:#ef4444;"></i>
            <span>You <strong>must stay in fullscreen</strong> mode. Exiting triggers automatic re-entry.</span>
          </li>
          <li style="display:flex; align-items:flex-start; gap:.6rem; padding:.5rem .75rem;
            border-radius:10px; margin-bottom:.4rem; font-size:.9rem;
            background:#fef2f2; color:#7f1d1d; border:1px solid #fecaca;">
            <i class="fa-solid fa-arrow-right-from-bracket" style="margin-top:.15rem; flex-shrink:0; color:#ef4444;"></i>
            <span>Do <strong>not switch tabs</strong>. Each switch is recorded as a violation.</span>
          </li>
          <li style="display:flex; align-items:flex-start; gap:.6rem; padding:.5rem .75rem;
            border-radius:10px; margin-bottom:.4rem; font-size:.9rem;
            background:#fef2f2; color:#7f1d1d; border:1px solid #fecaca;">
            <i class="fa-solid fa-rotate" style="margin-top:.15rem; flex-shrink:0; color:#ef4444;"></i>
            <span>Grid: <strong>${state.N}×${state.N}</strong> — Time: <strong>${state.time_limit_sec}s</strong> — Rotate tiles to build the path.</span>
          </li>
          <li style="display:flex; align-items:flex-start; gap:.6rem; padding:.5rem .75rem;
            border-radius:10px; margin-bottom:.4rem; font-size:.9rem;
            background:#f0fdf4; color:#14532d; border:1px solid #bbf7d0;">
            <i class="fa-solid fa-circle-check" style="margin-top:.15rem; flex-shrink:0; color:#16a34a;"></i>
            <span>Click <strong>"Start Game"</strong> to enter fullscreen and start the timer. Good luck!</span>
          </li>
        </ul>`, 
      confirmButtonText: '<i class="fa-solid fa-play me-2"></i>Start Game',
      allowOutsideClick: false,
      allowEscapeKey: false,
      customClass: { confirmButton: 'btn btn-primary px-4', cancelButton: 'btn btn-light px-4' },
      buttonsStyling: true,
    }).then(result => {
      if(!result.isConfirmed){ history.back(); return; }

      GAME_ACTIVE = true;
      if(!state.startedAtMs) state.startedAtMs = nowMs();
      if(!state.startedAtWall) state.startedAtWall = Date.now();

      state.replay.initial_deg_by_index = buildDegByIndex();
      pushTimeline("start", { current_index: 0 });
      pushSnapshot();

      // Activate violation monitoring
      pgxMonitoring = true;
      pgxRequestFullscreen();
      document.getElementById('pgxViolationBadge').style.display = 'block';

      startTimer();
      setRunState('Playing','primary');
      if(elStartIcon) elStartIcon.style.display = '';
      notify('success','Started','Arrange path till last column, then Launch 🚀');
      refreshLaunchReadyDebounced();
    });
  }
  function stripHtml(s){
    return String(s||'').replace(/<[^>]*>?/gm,'').trim();
  }

  /* ========= Boot ========= */
  async function boot(){
    const restored = loadCache();

    if(restored && state.tiles.length){
      elTitle.textContent = state.meta?.title || 'Path Game';
      elInstr.textContent = stripHtml(
        state.meta?.instructions_html || state.meta?.instructions || state.meta?.description ||
        'Rotate allowed tiles to create a valid path.'
      );

      const { M } = getGlobalArrowMap();
      if(!Number.isFinite(state.startRow)) state.startRow = Math.floor(M/2);
      state.startRow = Math.max(0, Math.min(M-1, state.startRow));

      renderTimer();
      renderBoard();
      setRunState('Ready','secondary');

      if(state.lastValidation?.path?.length){
        clearPathHighlights();
        markPath(state.lastValidation.path, !!state.lastValidation.ok);
      }

      positionEdgeNodes();
      updateStartNavUI();
      setRocketVisible(false);

      if(elStartIcon){
        elStartIcon.style.display = state.rocketLaunched ? 'none' : '';
      }

      elSubmitBtn.disabled = !(state.rocketLanded || state.autoSubmitted);

      saveCache();

    }else{
      const meta = await fetchJson(API.game);
      state.meta = meta;

      state.N = Number(meta?.grid_dim || 3);
      state.time_limit_sec = Number(meta?.time_limit_sec || 60);
      state.timeLeft = state.time_limit_sec;

      elTitle.textContent = meta?.title || 'Path Game';
      elInstr.textContent = stripHtml(
        meta?.instructions_html || meta?.instructions || meta?.description ||
        'Rotate allowed tiles to create a valid path.'
      );

      let gj = meta?.grid_json;
      try{ if(typeof gj === 'string') gj = JSON.parse(gj); }catch(e){ gj = null; }

      if(!gj || !Array.isArray(gj.grids)){
        throw new Error('grid_json missing or invalid for path game.');
      }

      state.tiles = gj.grids.map((g, i)=>( {
        grid_index: Number(g.grid_index ?? (i+1)),
        rotatable: !!g.rotatable,
        rotation_type: String(g.rotation_type || 'cw'),
        rotation_count: 0,
        tiles: (g.tiles || []).map(t=>( {
          index: Number(t.index),
          arrow: t.arrow ? normArrow(t.arrow) : null
        }))
      }));

      const { M } = getGlobalArrowMap();
      state.startRow = Math.floor(M/2);

      state.startedAtMs = 0;
      state.startedAtWall = 0;

      state.rotationsTotal = 0;
      state.rotationLog = [];
      state.valid = false;
      state.lastValidation = null;
      state.selectedTile = null;
      state.autoSubmitted = false;

      state.launchReady = false;
      state.launchRes = null;
      state.rocketLaunched = false;
      state.rocketLanded = false;
      state.landingIdx = null;

      // ✅ reset replay
      state.replay = {
        version:"2.0",
        timeline: [],
        state_snapshots: [],
        initial_deg_by_index: {},
        final_deg_by_index: {}
      };
elBoardWrap.style.opacity = '0.4';
      elBoardWrap.style.pointerEvents = 'none';
      renderTimer();
      renderBoard();
      setRunState('Ready','secondary');
      saveCache();
      setRocketVisible(false);

      // Reveal board smoothly once ready
      requestAnimationFrame(()=>{
        elBoardWrap.style.transition = 'opacity .4s ease';
        elBoardWrap.style.opacity = '1';
        elBoardWrap.style.pointerEvents = '';
      });
    }

    window.addEventListener('resize', ()=>{
      positionEdgeNodes();
      if(state.launchRes?.endIdx) positionEarthAtGlobalIdx(state.launchRes.endIdx);
    });

    await openIntroModal(state.meta || {});
  }

  boot().catch((e)=>{
    console.error(e);
    Swal.fire('Failed to load', e.message || 'Something went wrong', 'error');
  });

})();
</script>

</body>
</html>
