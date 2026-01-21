{{-- resources/views/modules/bubble_game/exam.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Bubble Game Exam</title>

  {{-- Bootstrap + FontAwesome + Common UI --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('assets/css/common/main.css') }}">

  <style>
    /* =========================================================
      Bubble Game Exam UI (Scoped)
      - Full-screen + more “pro” layout
      - Uses SweetAlert2 (Swal) for dialogs & toast
      - Skip button appears only when allow_skip = "yes"
      - ✅ NEW: Bootstrap Start Modal auto-opens before exam starts
      - No global body/:root edits
    ========================================================= */

    .bgx-exam{
      --bgx-ink: #0f172a;
      --bgx-muted: #64748b;
      --bgx-card: var(--surface, #ffffff);
      --bgx-line: rgba(2,6,23,.12);
      --bgx-soft: rgba(2,6,23,.06);

      --bgx-brand: var(--primary-color, #951eaa);
      --bgx-brand2: var(--accent-color, #c94ff0);

      --bgx-radius: 18px;
      --bgx-radius2: 26px;
      --bgx-shadow: 0 18px 45px rgba(2,6,23,.14);

      min-height: 100vh;
      display:flex;
      align-items: stretch;
      justify-content: center;
      padding: 18px 14px;
      background:
        radial-gradient(1000px 520px at 12% 8%, rgba(201,79,240,.18), transparent 60%),
        radial-gradient(1000px 600px at 88% 12%, rgba(149,30,170,.15), transparent 55%),
        linear-gradient(180deg, rgba(2,6,23,.02), rgba(2,6,23,.03));
      color: var(--bgx-ink);
    }

    html.theme-dark .bgx-exam{
      --bgx-card: #0f172a;
      --bgx-ink: #e5e7eb;
      --bgx-muted: #94a3b8;
      --bgx-line: rgba(148,163,184,.18);
      --bgx-soft: rgba(148,163,184,.10);
      background:
        radial-gradient(1000px 520px at 12% 8%, rgba(201,79,240,.14), transparent 60%),
        radial-gradient(1000px 600px at 88% 12%, rgba(149,30,170,.12), transparent 55%),
        linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.86));
    }

    /* ✅ more fullscreen: allow wider shell */
    .bgx-shell{
      width: 100%;
      max-width: 1560px;
      display:flex;
      flex-direction: column;
      gap: 14px;
    }
    @media (min-width: 1700px){
      .bgx-shell{ max-width: 1700px; }
    }

    .bgx-topbar{
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--bgx-brand) 70%, white) 0%,
        color-mix(in srgb, var(--bgx-brand2) 70%, white) 100%);
      border-radius: var(--bgx-radius2);
      box-shadow: var(--bgx-shadow);
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
    html.theme-dark .bgx-topbar{
      background: linear-gradient(135deg,
        color-mix(in srgb, var(--bgx-brand) 60%, #0b1220) 0%,
        color-mix(in srgb, var(--bgx-brand2) 60%, #0b1220) 100%);
    }

    .bgx-title{ display:flex; flex-direction: column; gap: 4px; min-width: 0; }
    .bgx-title h1{
      font-size: clamp(16px, 1.25vw, 20px);
      line-height: 1.15;
      margin: 0;
      font-weight: 900;
      letter-spacing: .2px;
      white-space: nowrap;
      overflow:hidden;
      text-overflow: ellipsis;
      max-width: 62vw;
    }
    .bgx-title .sub{
      font-size: 12px;
      opacity: .95;
      display:flex;
      align-items:center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .bgx-pill{
      display:inline-flex;
      align-items:center;
      gap: 7px;
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      font-weight: 700;
      font-size: 12px;
      white-space: nowrap;
    }

    .bgx-actions{ display:flex; align-items:center; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
    .bgx-btn{
      border: 1px solid rgba(255,255,255,.22);
      background: rgba(255,255,255,.14);
      color: #fff;
      border-radius: 14px;
      padding: 10px 14px;
      display:inline-flex;
      align-items:center;
      gap: 9px;
      font-weight: 800;
      font-size: 13px;
      transition: .15s ease;
      text-decoration:none;
      user-select:none;
    }
    .bgx-btn:hover{ transform: translateY(-1px); background: rgba(255,255,255,.20); }
    .bgx-btn:active{ transform: translateY(0px); }
    .bgx-btn.danger{ background: rgba(239,68,68,.18); border-color: rgba(239,68,68,.28); }

    /* ✅ wider + more “app-like” grid */
    .bgx-grid{
      display:grid;
      grid-template-columns: 1fr 440px;
      gap: 14px;
      align-items: start;
    }
    @media (max-width: 1080px){
      .bgx-grid{ grid-template-columns: 1fr; }
      .bgx-title h1{ max-width: 92vw; }
    }

    .bgx-card{
      background: var(--bgx-card);
      border: 1px solid var(--bgx-line);
      border-radius: var(--bgx-radius2);
      box-shadow: 0 12px 30px rgba(2,6,23,.08);
      overflow: hidden;
    }
    html.theme-dark .bgx-card{ box-shadow: 0 14px 38px rgba(0,0,0,.45); }

    .bgx-card-hd{
      padding: 14px 16px;
      display:flex;
      align-items:flex-start;
      justify-content: space-between;
      gap: 14px;
      border-bottom: 1px solid var(--bgx-line);
      background: linear-gradient(180deg, rgba(2,6,23,.03), transparent);
    }

    .bgx-instr{ display:flex; flex-direction: column; gap: 6px; }
    .bgx-instr .kicker{
      font-size: 12px;
      font-weight: 900;
      letter-spacing: .55px;
      color: color-mix(in srgb, var(--bgx-brand) 70%, var(--bgx-ink));
      text-transform: uppercase;
    }
    .bgx-instr .text{
      font-size: 14px;
      color: var(--bgx-muted);
      font-weight: 700;
    }

    .bgx-timer{
      min-width: 210px;
      display:flex;
      flex-direction: column;
      gap: 8px;
      align-items: flex-end;
    }
    .bgx-timer .row1{
      display:flex;
      align-items:center;
      gap: 10px;
      font-weight: 900;
    }
    .bgx-timer .row1 .label{ font-size: 12px; color: var(--bgx-muted); font-weight: 900; }
    .bgx-timer .row1 .time{
      font-size: 14px;
      padding: 6px 11px;
      border-radius: 999px;
      border: 1px solid var(--bgx-line);
      background: rgba(2,6,23,.03);
      font-weight: 900;
    }
    html.theme-dark .bgx-timer .row1 .time{ background: rgba(148,163,184,.08); }

    .bgx-progress{
      width: 210px;
      height: 10px;
      border-radius: 999px;
      background: rgba(2,6,23,.08);
      overflow:hidden;
      border: 1px solid var(--bgx-line);
    }
    html.theme-dark .bgx-progress{ background: rgba(148,163,184,.10); }
    .bgx-progress > i{
      display:block;
      height: 100%;
      width: 100%;
      background: linear-gradient(90deg, #22c55e, #16a34a);
      border-radius: 999px;
      transition: width .35s ease;
    }

    .bgx-card-bd{ padding: 16px; }

    .bgx-question{
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 10px;
    }
    .bgx-qtitle{ display:flex; align-items: center; gap: 10px; min-width: 0; }
    .bgx-qnum{
      width: 42px; height: 42px;
      border-radius: 16px;
      display:flex; align-items:center; justify-content:center;
      background: color-mix(in srgb, var(--bgx-brand) 18%, transparent);
      border: 1px solid color-mix(in srgb, var(--bgx-brand) 25%, var(--bgx-line));
      font-weight: 900;
      font-size: 14px;
    }
    .bgx-qmeta{ min-width:0; display:flex; flex-direction: column; gap: 2px; }
    .bgx-qmeta .ttl{
      font-weight: 950;
      font-size: 14px;
      white-space: nowrap;
      overflow:hidden;
      text-overflow: ellipsis;
      max-width: 64vw;
    }
    .bgx-qmeta .sub{
      font-size: 12px;
      color: var(--bgx-muted);
      font-weight: 700;
    }

    .bgx-bubbles{
      display:flex;
      flex-wrap: wrap;
      gap: 18px;
      padding: 14px 6px 8px;
      justify-content: center;
      align-items: center;
      min-height: 260px;
    }

    /* ✅ more “pro” bubble sizing */
    .bgx-bubble{
      width: clamp(108px, 10vw, 152px);
      height: clamp(108px, 10vw, 152px);
      border-radius: 999px;
      border: 3px solid rgba(2,6,23,.12);
      background: #fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight: 950;
      font-size: clamp(16px, 1.35vw, 22px);
      box-shadow: 0 16px 34px rgba(2,6,23,.14);
      position: relative;
      cursor: pointer;
      user-select: none;
      transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease, background .14s ease;
      padding: 12px;
      text-align:center;
      line-height: 1.1;
      word-break: break-word;
    }

    html.theme-dark .bgx-bubble{
      background: #0b1220;
      border-color: rgba(148,163,184,.20);
      box-shadow: 0 18px 44px rgba(0,0,0,.55);
    }
    .bgx-bubble:hover{ transform: translateY(-2px) scale(1.01); }
    .bgx-bubble:active{ transform: translateY(0px) scale(.99); }

    /* =========================================================
      ✅ Intro animation: bubbles “drop from sky” on first load
    ========================================================= */
    @keyframes bgxBubbleDrop{
      0%{
        opacity: 0;
        transform: translate3d(var(--drop-x, 0px), -170px, 0) rotate(var(--drop-rot, -8deg)) scale(.92);
        filter: blur(1px);
      }
      70%{
        opacity: 1;
        filter: blur(0);
      }
      85%{
        transform: translate3d(0px, 12px, 0) rotate(0deg) scale(1.02);
      }
      100%{
        opacity: 1;
        transform: translate3d(0px, 0px, 0) rotate(0deg) scale(1);
      }
    }
    .bgx-bubble.bgx-drop{
      animation: bgxBubbleDrop 650ms cubic-bezier(.2,.95,.2,1) both;
      will-change: transform, opacity;
    }
    @media (prefers-reduced-motion: reduce){
      .bgx-bubble.bgx-drop{ animation: none !important; }
    }

    .bgx-bubble.is-selected{
      border-color: color-mix(in srgb, var(--bgx-brand2) 55%, #111827);
      background:
        radial-gradient(160px 160px at 30% 25%, rgba(201,79,240,.20), transparent 55%),
        radial-gradient(160px 160px at 75% 70%, rgba(149,30,170,.18), transparent 55%),
        #fff;
    }
    html.theme-dark .bgx-bubble.is-selected{
      background:
        radial-gradient(160px 160px at 30% 25%, rgba(201,79,240,.18), transparent 55%),
        radial-gradient(160px 160px at 75% 70%, rgba(149,30,170,.14), transparent 55%),
        #0b1220;
    }

    .bgx-badge{
      position:absolute;
      top: -10px;
      right: -8px;
      width: 36px;
      height: 36px;
      border-radius: 999px;
      display:flex;
      align-items:center;
      justify-content:center;
      background: linear-gradient(135deg, var(--bgx-brand), var(--bgx-brand2));
      color:#fff;
      font-weight: 950;
      border: 2px solid rgba(255,255,255,.9);
      box-shadow: 0 10px 20px rgba(2,6,23,.18);
      font-size: 14px;
    }

    .bgx-side{
      position: sticky;
      top: 86px;
      display:flex;
      flex-direction: column;
      gap: 14px;
    }
    @media (max-width: 1080px){
      .bgx-side{ position: static; }
    }

    .bgx-selected{
      padding: 14px 16px;
      display:flex;
      flex-direction: column;
      gap: 10px;
    }
    .bgx-selected .hd{
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
    }
    .bgx-selected .hd .t{
      font-weight: 950;
      font-size: 14px;
      display:flex;
      align-items:center;
      gap: 10px;
    }

    .bgx-chiprow{ display:flex; flex-wrap: wrap; gap: 8px; min-height: 28px; }
    .bgx-chip{
      display:inline-flex;
      align-items:center;
      gap: 8px;
      padding: 7px 10px;
      border-radius: 999px;
      background: rgba(2,6,23,.04);
      border: 1px solid var(--bgx-line);
      font-weight: 900;
      font-size: 12px;
    }
    html.theme-dark .bgx-chip{ background: rgba(148,163,184,.08); }
    .bgx-chip b{
      width: 22px;
      height: 22px;
      border-radius: 999px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background: color-mix(in srgb, var(--bgx-brand) 30%, transparent);
      border: 1px solid color-mix(in srgb, var(--bgx-brand) 30%, var(--bgx-line));
      font-size: 12px;
    }

    .bgx-muted{ color: var(--bgx-muted); font-size: 12px; font-weight: 800; }

    .bgx-controls{
      padding: 14px 16px;
      border-top: 1px solid var(--bgx-line);
      display:flex;
      align-items:center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }

    .bgx-ctl{ display:flex; gap: 10px; flex-wrap: wrap; }
    .bgx-cta{ display:flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end; margin-left:auto; }

    .bgx-btn2{
      border-radius: 14px;
      border: 1px solid var(--bgx-line);
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
    html.theme-dark .bgx-btn2{ background: rgba(148,163,184,.08); }
    .bgx-btn2:hover{ transform: translateY(-1px); }
    .bgx-btn2:active{ transform: translateY(0px); }

    .bgx-btn2.primary{
      background: linear-gradient(135deg, var(--bgx-brand), var(--bgx-brand2));
      color:#fff;
      border-color: rgba(255,255,255,.08);
    }
    .bgx-btn2.danger{
      background: rgba(239,68,68,.10);
      border-color: rgba(239,68,68,.20);
      color: #ef4444;
    }
    .bgx-btn2.warn{
      background: rgba(245,158,11,.10);
      border-color: rgba(245,158,11,.22);
      color: color-mix(in srgb, #f59e0b 70%, var(--bgx-ink));
    }
    html.theme-dark .bgx-btn2.warn{ color: #fbbf24; }

    .bgx-btn2:disabled{ opacity: .55; cursor: not-allowed; transform: none !important; }

    .bgx-loader{
      padding: 22px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap: 10px;
      color: var(--bgx-muted);
      font-weight: 900;
    }

    .bgx-footnote{
      text-align:center;
      color: var(--bgx-muted);
      font-size: 12px;
      font-weight: 800;
      padding: 2px 0 10px;
    }

    /* Always disable Previous */
    #bgxPrevBtn{
      pointer-events: none !important;
      opacity: .55 !important;
      filter: grayscale(1) !important;
      cursor: not-allowed !important;
    }

    /* =========================================================
      ✅ Start Exam Modal (Bootstrap) — professional
    ========================================================= */
    .bgx-start-modal .modal-dialog{ max-width: 860px; }
    .bgx-start-modal .modal-content{
      border-radius: 22px;
      border: 1px solid color-mix(in srgb, var(--bgx-brand) 25%, var(--bgx-line));
      overflow: hidden;
      box-shadow: 0 26px 70px rgba(2,6,23,.28);
      background: var(--bgx-card);
      color: var(--bgx-ink);
    }
    html.theme-dark .bgx-start-modal .modal-content{
      box-shadow: 0 30px 80px rgba(0,0,0,.62);
      border-color: rgba(148,163,184,.22);
    }
    .bgx-start-modal .modal-header{
      border-bottom: 1px solid var(--bgx-line);
      padding: 16px 18px;
      background:
        radial-gradient(900px 240px at 14% 0%, rgba(201,79,240,.22), transparent 55%),
        radial-gradient(900px 240px at 86% 0%, rgba(149,30,170,.18), transparent 55%),
        /* linear-gradient(135deg,
          color-mix(in srgb, var(--bgx-brand) 72%, white) 0%,
          color-mix(in srgb, var(--bgx-brand2) 72%, white) 100%);
      color: #fff; */
    }
    html.theme-dark .bgx-start-modal .modal-header{
      background:
        radial-gradient(900px 240px at 14% 0%, rgba(201,79,240,.16), transparent 55%),
        radial-gradient(900px 240px at 86% 0%, rgba(149,30,170,.14), transparent 55%),
        linear-gradient(135deg,
          color-mix(in srgb, var(--bgx-brand) 58%, #0b1220) 0%,
          color-mix(in srgb, var(--bgx-brand2) 58%, #0b1220) 100%);
    }

    .bgx-modal-title{ display:flex; align-items:center; gap: 12px; min-width:0; }
    .bgx-modal-ico{
      width: 44px;
      height: 44px;
      border-radius: 16px;
      display:flex;
      align-items:center;
      justify-content:center;
      background: rgba(255,255,255,.16);
      border: 1px solid rgba(255,255,255,.22);
      backdrop-filter: blur(10px);
      flex: 0 0 auto;
    }
    .bgx-modal-title h5{
      margin:0;
      font-weight: 950;
      letter-spacing: .2px;
      font-size: 16px;
      white-space: nowrap;
      overflow:hidden;
      text-overflow: ellipsis;
      max-width: 64vw;
    }
    .bgx-modal-title .small{
      opacity: .92;
      font-size: 12px;
      font-weight: 700;
      margin-top: 2px;
    }

    .bgx-start-modal .modal-body{ background: white; padding: 16px 18px 6px; }
    .bgx-modal-grid{
      display:grid;
      grid-template-columns: 1.35fr .85fr;
      gap: 14px;
      align-items:start;
    }
    @media (max-width: 992px){ .bgx-modal-grid{ grid-template-columns: 1fr; } }

    .bgx-modal-box{
      border: 1px solid var(--bgx-line);
      border-radius: 18px;
      padding: 14px 14px;
      background: white;
    }
    html.theme-dark .bgx-modal-box{
      background: linear-gradient(180deg, rgba(148,163,184,.06), transparent);
    }

    .bgx-modal-kicker{
      font-size: 12px;
      font-weight: 950;
      letter-spacing: .55px;
      text-transform: uppercase;
      color: color-mix(in srgb, var(--bgx-brand) 70%, var(--bgx-ink));
      display:flex;
      align-items:center;
      gap: 8px;
      margin-bottom: 6px;
    }
    .bgx-modal-text{
      color: var(--bgx-muted);
      font-weight: 750;
      font-size: 13px;
      line-height: 1.45;
    }

    .bgx-modal-stats{ display:flex; flex-direction: column; gap: 10px; }
    .bgx-stat{
      display:flex;
      align-items:flex-start;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 16px;
      border: 1px solid var(--bgx-line);
      background: rgba(2,6,23,.02);
    }
    html.theme-dark .bgx-stat{ background: rgba(148,163,184,.06); }
    .bgx-stat i{
      width: 34px;
      height: 34px;
      border-radius: 14px;
      display:flex;
      align-items:center;
      justify-content:center;
      background: color-mix(in srgb, var(--bgx-brand2) 16%, transparent);
      border: 1px solid color-mix(in srgb, var(--bgx-brand2) 24%, var(--bgx-line));
      flex: 0 0 auto;
    }
    .bgx-stat .t{
      font-weight: 950;
      font-size: 13px;
      line-height: 1.2;
      margin-bottom: 2px;
    }
    .bgx-stat .s{
      color: var(--bgx-muted);
      font-weight: 750;
      font-size: 12px;
      line-height: 1.25;
    }

    .bgx-modal-list{
      margin: 10px 0 0;
      padding-left: 16px;
      color: var(--bgx-muted);
      font-weight: 750;
      font-size: 13px;
      line-height: 1.5;
    }
    .bgx-modal-list li{ margin-bottom: 6px; }

    .bgx-start-modal .modal-footer{
      border-top: 1px solid var(--bgx-line);
      padding: 12px 18px 16px;
      display:flex;
      align-items:center;
      gap: 10px;
      justify-content: space-between;
      flex-wrap: wrap;
      /* background: linear-gradient(180deg, transparent, rgba(2,6,23,.02)); */
    }
    html.theme-dark .bgx-start-modal .modal-footer{
      background: linear-gradient(180deg, transparent, rgba(148,163,184,.05));
    }

    .bgx-modal-footnote{
      color: var(--bgx-muted);
      font-weight: 750;
      font-size: 12px;
      display:flex;
      align-items:center;
      gap: 8px;
      max-width: 560px;
    }

    .bgx-modal-actions{
      display:flex;
      align-items:center;
      gap: 10px;
      flex-wrap: wrap;
      margin-left:auto;
    }

    .bgx-modal-btn{
      border-radius: 14px;
      border: 1px solid var(--bgx-line);
      background: rgba(2,6,23,.03);
      padding: 10px 14px;
      font-weight: 950;
      font-size: 13px;
      display:inline-flex;
      align-items:center;
      gap: 9px;
      transition: .15s ease;
      user-select:none;
      text-decoration:none;
      color: var(--bgx-ink);
    }
    html.theme-dark .bgx-modal-btn{ background: rgba(148,163,184,.08); }
    .bgx-modal-btn:hover{ transform: translateY(-1px); }
    .bgx-modal-btn:active{ transform: translateY(0px); }

    .bgx-modal-btn.primary{
      /* background: linear-gradient(135deg, var(--bgx-brand), var(--bgx-brand2));
      color:#fff; */
      border-color: rgba(255,255,255,.08);
    }
    .bgx-modal-btn.danger{
      background: rgba(239,68,68,.10);
      border-color: rgba(239,68,68,.20);
      color: #ef4444;
    }
  </style>
</head>

<body>
<div class="bgx-exam" id="bgxExam">
  <div class="bgx-shell">

    {{-- Topbar --}}
    <div class="bgx-topbar">
      <div class="bgx-title">
        <h1 id="bgxGameTitle">Loading game…</h1>
        <div class="sub">
          <span class="bgx-pill"><i class="fa-solid fa-layer-group"></i> <span id="bgxRound">Round 0/0</span></span>
          <span class="bgx-pill"><i class="fa-solid fa-clock"></i> Per Q: <span id="bgxPerQ">--</span>s</span>
          <span class="bgx-pill"><i class="fa-solid fa-database"></i> Auto-saved</span>
        </div>
      </div>

      <div class="bgx-actions">
        <a class="bgx-btn" href="/dashboard" id="bgxQuitBtn">
          <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <button class="bgx-btn danger d-none" id="bgxClearLocal" type="button" style="display:none">
          <i class="fa-solid fa-rotate-left"></i> Reset Attempt
        </button>
      </div>
    </div>

    {{-- Main --}}
    <div class="bgx-grid">
      {{-- Left: Exam Card --}}
      <div class="bgx-card">
        <div class="bgx-card-hd">
          <div class="bgx-instr">
            <div class="kicker">Instructions</div>
            <div class="text" id="bgxInstruction">Loading…</div>
          </div>

          <div class="bgx-timer">
            <div class="row1">
              <span class="label">Time Left</span>
              <span class="time"><span id="bgxTimeLeft">--</span>s</span>
            </div>
            <div class="bgx-progress" aria-label="timer progress">
              <i id="bgxTimeBar" style="width:100%"></i>
            </div>
          </div>
        </div>

        <div class="bgx-card-bd">
          <div class="bgx-question">
            <div class="bgx-qtitle">
              <div class="bgx-qnum" id="bgxQNum">1</div>
              <div class="bgx-qmeta">
                <div class="ttl" id="bgxQTitle">Question</div>
                <div class="sub" id="bgxQSub">Select bubbles in correct order</div>
              </div>
            </div>

            <div class="bgx-muted" id="bgxQHint">
              <i class="fa-solid fa-circle-info"></i> Pick all bubbles in order.
            </div>
          </div>

          <div id="bgxBubblesWrap">
            <div class="bgx-loader">
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              Loading exam…
            </div>
          </div>
        </div>

        <div class="bgx-controls">
          <div class="bgx-ctl">
            <button class="bgx-btn2" id="bgxPrevBtn" type="button">
              <i class="fa-solid fa-chevron-left"></i> Previous
            </button>
            <button class="bgx-btn2" id="bgxUndoBtn" type="button">
              <i class="fa-solid fa-rotate-left"></i> Undo
            </button>
            <button class="bgx-btn2" id="bgxClearBtn" type="button">
              <i class="fa-solid fa-trash"></i> Clear
            </button>
          </div>

          <div class="bgx-cta">
            {{-- ✅ Skip only if allow_skip = yes --}}
            <button class="bgx-btn2 warn d-none" id="bgxSkipBtn" type="button">
              <i class="fa-solid fa-forward"></i> Skip
            </button>

            <button class="bgx-btn2" id="bgxNextBtn" type="button">
              Next <i class="fa-solid fa-chevron-right"></i>
            </button>

            <button class="bgx-btn2 primary d-none" id="bgxSubmitBtn" type="button">
              <i class="fa-solid fa-paper-plane"></i> Submit Exam
            </button>
          </div>
        </div>
      </div>

      {{-- Right: Selected & Status --}}
      <div class="bgx-side">
        <div class="bgx-card">
          <div class="bgx-selected">
            <div class="hd">
              <div class="t"><i class="fa-solid fa-check-double"></i> Your Selection</div>
              <span class="bgx-pill" id="bgxSelCount">0 selected</span>
            </div>

            <div class="bgx-chiprow" id="bgxSelectedChips">
              <span class="bgx-muted">Nothing selected yet.</span>
            </div>

            <div class="bgx-muted" id="bgxSelectTip">
              Tip: Select <b>all</b> bubbles in the exact order.
            </div>
          </div>
        </div>

        <div class="bgx-card">
          <div class="bgx-selected">
            <div class="hd">
              <div class="t"><i class="fa-solid fa-list-check"></i> Attempt Progress</div>
              <span class="bgx-pill"><span id="bgxAnswered">0</span>/<span id="bgxTotal">0</span> answered</span>
            </div>

            <div class="bgx-muted" id="bgxProgressNote">
              You can move with Next/Previous. Submit only when ready.
            </div>
          </div>
        </div>

        <div class="bgx-footnote">
          Refresh keeps answers + per-question timer in sessionStorage (attempt cache).
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ✅ Start Exam Modal (Bootstrap) --}}
<div class="modal fade bgx-start-modal" id="bgxStartModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div class="bgx-modal-title">
          <div class="bgx-modal-ico">
            <i class="fa-solid fa-circle-play"></i>
          </div>
          <div style="min-width:0">
            <h5 id="bgxStartModalTitle">Bubble Game Exam</h5>
            <div class="small" id="bgxStartModalSub">Please read the instructions before starting.</div>
          </div>
        </div>
      </div>

      <div class="modal-body">
        <div class="bgx-modal-grid">
          <div class="bgx-modal-box">
            <div class="bgx-modal-kicker">
              <i class="fa-solid fa-book-open"></i> Description & Instructions
            </div>

            {{-- ✅ dynamic injected --}}
            <div class="bgx-modal-text" id="bgxStartModalDesc">
              Loading details…
            </div>

            {{-- ✅ NO STATIC ITEMS ANYMORE (filled dynamically if API provides list) --}}
            <ul class="bgx-modal-list" id="bgxStartModalRules" style="display:none"></ul>
          </div>

          <div class="bgx-modal-stats">
            <div class="bgx-stat">
              <i class="fa-solid fa-layer-group"></i>
              <div>
                <div class="t">Total Questions</div>
                <div class="s"><span id="bgxStartModalTotalQ">0</span> rounds</div>
              </div>
            </div>

            <div class="bgx-stat">
              <i class="fa-solid fa-clock"></i>
              <div>
                <div class="t">Time per Question</div>
                <div class="s"><span id="bgxStartModalPerQ">--</span> seconds</div>
              </div>
            </div>

            <div class="bgx-stat">
              <i class="fa-solid fa-forward"></i>
              <div>
                <div class="t">Skip</div>
                <div class="s" id="bgxStartModalSkip">Disabled</div>
              </div>
            </div>

            <div class="bgx-stat">
              <i class="fa-solid fa-trophy"></i>
              <div>
                <div class="t">Total Points</div>
                <div class="s"><span id="bgxStartModalPoints">0</span> max</div>
              </div>
            </div>

            <div class="bgx-stat" id="bgxStartModalResumeCard" style="display:none">
              <i class="fa-solid fa-rotate"></i>
              <div>
                <div class="t">Resume Attempt</div>
                <div class="s"><span id="bgxStartModalResumeTxt">0</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <div class="bgx-modal-footnote">
          <i class="fa-solid fa-shield-halved"></i>
          Once you start, the timer begins. Submit only when you are finished.
        </div>

        <div class="bgx-modal-actions">
          <a href="/dashboard" class="bgx-modal-btn">
            <i class="fa-solid fa-arrow-left"></i> Back
          </a>
          <button type="button" class="bgx-modal-btn danger d-none" style="display:none;" id="bgxModalResetBtn">
            <i class="fa-solid fa-rotate-left"></i> Reset Attempt
          </button>
          <button type="button" class="bgx-modal-btn primary" id="bgxModalStartBtn">
            <i class="fa-solid fa-circle-play"></i> Start Exam
          </button>
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
    ✅ SWAL EDITION + SKIP SUPPORT
    - ✅ Start modal Description + Instructions now fully DYNAMIC from API
  ========================================================= */

  function getGameUuidFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return (urlParams.get('game') || urlParams.get('game_uuid') || urlParams.get('uuid') || '').trim();
  }

  const GAME_UUID = getGameUuidFromUrl();
  const DASHBOARD_URL = '/dashboard';

  const API = {
    game:      `/api/bubble-games/${GAME_UUID}`,
    questions: `/api/bubble-games/${GAME_UUID}/questions?paginate=false`,
    submit:    `/api/bubble-games-results/submit/${GAME_UUID}`,
  };

  const CACHE_KEY = `bg_exam_${GAME_UUID}`;

  const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 2600,
    timerProgressBar: true,
  });

  function notify(icon, title, text=''){
    Toast.fire({ icon, title: title || '', text: text || '' });
  }

  const elGameTitle = document.getElementById('bgxGameTitle');
  const elRound = document.getElementById('bgxRound');
  const elPerQ = document.getElementById('bgxPerQ');

  const elInstruction = document.getElementById('bgxInstruction');
  const elTimeLeft = document.getElementById('bgxTimeLeft');
  const elTimeBar = document.getElementById('bgxTimeBar');

  const elQNum = document.getElementById('bgxQNum');
  const elQTitle = document.getElementById('bgxQTitle');
  const elQSub = document.getElementById('bgxQSub');

  const elBubblesWrap = document.getElementById('bgxBubblesWrap');

  const elPrevBtn = document.getElementById('bgxPrevBtn');
  const elNextBtn = document.getElementById('bgxNextBtn');
  const elUndoBtn = document.getElementById('bgxUndoBtn');
  const elClearBtn = document.getElementById('bgxClearBtn');
  const elSkipBtn = document.getElementById('bgxSkipBtn');
  const elSubmitBtn = document.getElementById('bgxSubmitBtn');

  const elSelCount = document.getElementById('bgxSelCount');
  const elSelectedChips = document.getElementById('bgxSelectedChips');

  const elAnswered = document.getElementById('bgxAnswered');
  const elTotal = document.getElementById('bgxTotal');

  const elQuitBtn = document.getElementById('bgxQuitBtn');
  const elClearLocal = document.getElementById('bgxClearLocal');

  const elSelectTip = document.getElementById('bgxSelectTip');
  const elQHint = document.getElementById('bgxQHint');

  // ✅ Start Modal DOM
  const elStartModal = document.getElementById('bgxStartModal');
  const elStartModalTitle = document.getElementById('bgxStartModalTitle');
  const elStartModalSub = document.getElementById('bgxStartModalSub');
  const elStartModalDesc = document.getElementById('bgxStartModalDesc');
  const elStartModalRules = document.getElementById('bgxStartModalRules');

  const elStartModalTotalQ = document.getElementById('bgxStartModalTotalQ');
  const elStartModalPerQ = document.getElementById('bgxStartModalPerQ');
  const elStartModalSkip = document.getElementById('bgxStartModalSkip');
  const elStartModalPoints = document.getElementById('bgxStartModalPoints');

  const elStartModalResumeCard = document.getElementById('bgxStartModalResumeCard');
  const elStartModalResumeTxt = document.getElementById('bgxStartModalResumeTxt');

  const elModalStartBtn = document.getElementById('bgxModalStartBtn');
  const elModalResetBtn = document.getElementById('bgxModalResetBtn');

  let startModal = null;

  // State
  let state = {
    game: null,
    questions: [],
    qIndex: 0,
    answers: {},
    currentSelection: [],
    perQTime: 30,
    timers: {},
    tick: null,
    isSubmitting: false,
    autoSubmitted: false,
    suppressUnloadPrompt: false,
    introDropDone: false,

    examStarted: false,
    startModalShown: false,
    isRestoredAttempt: false,
  };

  function clampQIndex(){
    const total = Array.isArray(state.questions) ? state.questions.length : 0;

    let qi = parseInt(state.qIndex, 10);
    if (!Number.isFinite(qi)) qi = 0;

    if (total > 0) qi = Math.max(0, Math.min(qi, total - 1));
    else qi = 0;

    state.qIndex = qi;
  }

  function getToken() {
    return localStorage.getItem('token') || sessionStorage.getItem('token');
  }

  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function nl2br(s){
    return escapeHtml(String(s ?? '')).replace(/\n/g, '<br>');
  }

  function stopTick() {
    if (state.tick) {
      clearInterval(state.tick);
      state.tick = null;
    }
  }

  function currentQuestion() {
    clampQIndex();
    return state.questions[state.qIndex] || null;
  }

  function qKey(q) {
    if (!q) return '';
    return String(q.id ?? q.uuid ?? state.qIndex);
  }

  function countAnswered() {
    return Object.keys(state.answers || {}).length;
  }

  function saveCache() {
    sessionStorage.setItem(CACHE_KEY, JSON.stringify({
      game: state.game,
      questions: state.questions,
      qIndex: state.qIndex,
      answers: state.answers,
      currentSelection: state.currentSelection,
      perQTime: state.perQTime,
      timers: state.timers,
      savedAt: Date.now(),
    }));
  }

  function loadCache() {
    try {
      const raw = sessionStorage.getItem(CACHE_KEY);
      if (!raw) return false;
      const parsed = JSON.parse(raw);
      if (!parsed || !Array.isArray(parsed.questions) || !parsed.questions.length) return false;

      state.game = parsed.game || null;
      state.questions = parsed.questions || [];
      state.qIndex = Number(parsed.qIndex || 0);
      state.answers = parsed.answers || {};
      state.currentSelection = Array.isArray(parsed.currentSelection) ? parsed.currentSelection : [];
      state.perQTime = Number(parsed.perQTime || 30);
      state.timers = parsed.timers || {};

      const now = Date.now();
      Object.keys(state.timers || {}).forEach(k => {
        const t = state.timers[k];
        const lastAt = Number(t?.lastAt || now);
        const tl = Number(t?.timeLeft ?? state.perQTime);
        const elapsed = Math.floor((now - lastAt) / 1000);
        state.timers[k] = { timeLeft: Math.max(0, tl - elapsed), lastAt: now };
      });

      return true;
    } catch(e) {
      return false;
    }
  }

  function clearCache() {
    sessionStorage.removeItem(CACHE_KEY);
  }

  function unwrapPayload(json) {
    if (!json) return null;
    if (json.success === true && json.data !== undefined) return json.data;
    if (json.data !== undefined && json.success === undefined) return json.data;
    return json;
  }

  function unwrapList(json) {
    const p = unwrapPayload(json);
    if (Array.isArray(p)) return p;
    if (p && Array.isArray(p.data)) return p.data;
    if (json && Array.isArray(json.data)) return json.data;
    return [];
  }

  function shuffle(arr) {
    const a = arr.slice();
    for (let i=a.length-1;i>0;i--){
      const j = Math.floor(Math.random()*(i+1));
      [a[i],a[j]] = [a[j],a[i]];
    }
    return a;
  }

  function safeEval(expr) {
    const s = String(expr ?? '').trim().replace(/×/g,'*').replace(/÷/g,'/').replace(/\^/g,'**');
    if (!/^[0-9+\-*/().\s*]+$/.test(s)) return NaN;
    try {
      // eslint-disable-next-line no-new-func
      const v = Function('"use strict"; return (' + s + ');')();
      return (typeof v === 'number' && isFinite(v)) ? v : NaN;
    } catch(e) { return NaN; }
  }

  function computeExpectedIndices(q) {
    const n = q?.bubbles_original?.length || 0;
    const seq = q?.answer_sequence_json;

    if (Array.isArray(seq) && seq.length) {
      return seq
        .map(x => parseInt(x, 10))
        .filter(i => Number.isInteger(i) && i >= 0 && i < n);
    }

    const items = (q.bubbles_original || []).map((label, idx) => ({ idx, val: safeEval(label) }));
    items.sort((a,b) => {
      const av = isNaN(a.val) ? Number.POSITIVE_INFINITY : a.val;
      const bv = isNaN(b.val) ? Number.POSITIVE_INFINITY : b.val;
      if (av !== bv) return av - bv;
      return a.idx - b.idx;
    });

    let order = items.map(x => x.idx);
    const type = (q.select_type || 'ascending').toLowerCase();
    if (type === 'descending') order = order.slice().reverse();
    return order;
  }

  function normalizeQuestions(rawList) {
    const list = rawList || [];
    return list
      .filter(q => String(q?.status || 'active') !== 'inactive')
      .map((q, idx) => {
        let bubbles = q.bubbles_json ?? q.bubbles ?? q.options ?? [];
        if (typeof bubbles === 'string') { try { bubbles = JSON.parse(bubbles); } catch(e) { bubbles = []; } }
        if (!Array.isArray(bubbles)) bubbles = [];

        const bubbles_original = bubbles.map(b => {
          if (b && typeof b === 'object') return String(b.label ?? b.value ?? '');
          return String(b ?? '');
        }).filter(x => x.trim() !== '');

        const display = bubbles_original.map((label, i) => ({ i, label }));

        return {
          id: q.id ?? q.question_id ?? q.uuid ?? String(idx + 1),
          uuid: q.uuid ?? null,
          order_no: Number(q.order_no ?? (idx + 1)),
          title: q.title ?? q.question ?? q.prompt ?? `Question ${idx + 1}`,
          select_type: (q.select_type ?? 'ascending'),
          points: Number(q.points ?? 1),
          instruction: q.instruction ?? q.note ?? null,
          bubbles_original,
          bubbles_display: display,
          answer_sequence_json: q.answer_sequence_json ?? null,
          answer_value_json: q.answer_value_json ?? null,
        };
      })
      .sort((a,b) => (a.order_no||0) - (b.order_no||0));
  }

  async function fetchJson(url) {
    const token = getToken();
    const headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const res = await fetch(url, { method:'GET', headers });

    let json = {};
    try { json = await res.json(); } catch(e) { json = {}; }

    if (res.status === 401 || res.status === 419) throw new Error('Session expired. Please login again.');
    if (!res.ok || json.success === false) throw new Error(json.message || `Request failed (${res.status})`);
    return json;
  }

  async function fetchExamData() {
    const [g, q] = await Promise.all([
      fetchJson(API.game),
      (async () => {
        try { return await fetchJson(API.questions); }
        catch(e){ return await fetchJson(`/api/bubble-games/${GAME_UUID}/questions`); }
      })()
    ]);
    return { game: unwrapPayload(g), questionsRaw: unwrapList(q) };
  }

  function applyGameConfig(game) {
    state.game = game || {};
    state.perQTime = Number(game?.per_question_time_sec || 30);

    elGameTitle.textContent = game?.title ? String(game.title) : 'Bubble Game Exam';
    elPerQ.textContent = String(state.perQTime);

    const randomQ = String(game?.is_question_random || 'no') === 'yes';
    const randomB = String(game?.is_bubble_positions_random || 'no') === 'yes';

    if (randomQ) state.questions = shuffle(state.questions);
    if (randomB){
      state.questions = state.questions.map(q => ({ ...q, bubbles_display: shuffle(q.bubbles_display) }));
    }
  }

  function renderTimer(qk) {
    const t = state.timers[qk] || { timeLeft: state.perQTime, lastAt: Date.now() };
    elTimeLeft.textContent = String(t.timeLeft);
    const pct = state.perQTime > 0 ? (t.timeLeft / state.perQTime) * 100 : 0;
    elTimeBar.style.width = `${Math.max(0, Math.min(100, pct))}%`;
  }

  function startTimerFor(qk) {
    stopTick();

    if (!state.timers[qk]) {
      state.timers[qk] = { timeLeft: state.perQTime, lastAt: Date.now() };
    } else {
      const now = Date.now();
      const lastAt = Number(state.timers[qk].lastAt || now);
      const elapsed = Math.floor((now - lastAt) / 1000);
      state.timers[qk].timeLeft = Math.max(0, Number(state.timers[qk].timeLeft ?? state.perQTime) - elapsed);
      state.timers[qk].lastAt = now;
    }

    renderTimer(qk);

    state.tick = setInterval(() => {
      const now = Date.now();
      const t = state.timers[qk];
      if (!t) return;

      const lastAt = Number(t.lastAt || now);
      const elapsed = Math.max(1, Math.floor((now - lastAt) / 1000));
      t.timeLeft = Math.max(0, Number(t.timeLeft ?? state.perQTime) - elapsed);
      t.lastAt = now;

      renderTimer(qk);
      saveCache();

      if (t.timeLeft <= 0) {
        stopTick();
        autoAdvanceOnTimeout();
      }
    }, 1000);
  }

  function pauseTimer() {
    stopTick();
    saveCache();
  }

  function renderRightPanel() {
    elTotal.textContent = String(state.questions.length);
    elAnswered.textContent = String(countAnswered());
  }

  function renderSelectedChips() {
    const q = currentQuestion();
    const n = state.currentSelection.length;

    elSelCount.textContent = `${n} selected`;

    if (!q || !n) {
      elSelectedChips.innerHTML = `<span class="bgx-muted">Nothing selected yet.</span>`;
      return;
    }

    elSelectedChips.innerHTML = state.currentSelection.map((origIdx, idx) => {
      const label = q.bubbles_original?.[origIdx] ?? '';
      return `<span class="bgx-chip"><b>${idx+1}</b> ${escapeHtml(label)}</span>`;
    }).join('');
  }

  function renderBubbles(animate=false) {
    const q = currentQuestion();
    if (!q) {
      elBubblesWrap.innerHTML = `<div class="bgx-loader">No question found.</div>`;
      return;
    }

    const selectedOrder = new Map();
    state.currentSelection.forEach((origIdx, idx) => selectedOrder.set(Number(origIdx), idx + 1));

    const wrap = document.createElement('div');
    wrap.className = 'bgx-bubbles';

    (q.bubbles_display || []).forEach((b, idx) => {
      const origIdx = Number(b.i);
      const label = String(b.label ?? '');

      const div = document.createElement('div');
      div.className = 'bgx-bubble';
      div.setAttribute('data-i', String(origIdx));

      if (animate) {
        div.classList.add('bgx-drop');
        div.style.animationDelay = `${Math.min(900, idx * 65)}ms`;
        div.style.setProperty('--drop-x', `${(Math.random() * 80 - 40).toFixed(1)}px`);
        div.style.setProperty('--drop-rot', `${(Math.random() * 18 - 9).toFixed(1)}deg`);
      }

      const ord = selectedOrder.get(origIdx);
      if (ord) {
        div.classList.add('is-selected');
        const badge = document.createElement('span');
        badge.className = 'bgx-badge';
        badge.textContent = String(ord);
        div.appendChild(badge);
      }

      div.appendChild(document.createTextNode(label));

      div.addEventListener('click', () => {
        if (state.examStarted !== true) return;

        const pos = state.currentSelection.indexOf(origIdx);
        if (pos !== -1) {
          state.currentSelection = state.currentSelection.slice(0, pos);
          renderBubbles(false);
          renderSelectedChips();
          updateNavButtons();
          saveCache();
          return;
        }

        state.currentSelection.push(origIdx);

        const need = (q.bubbles_original || []).length;
        if (state.currentSelection.length === need){
          persistCurrentAnswer(false, true);
        } else {
          saveCache();
        }

        renderBubbles(false);
        renderSelectedChips();
        updateNavButtons();
      });

      wrap.appendChild(div);
    });

    elBubblesWrap.innerHTML = '';
    elBubblesWrap.appendChild(wrap);
  }

  function restoreSelectionForCurrent() {
    const q = currentQuestion();
    if (!q) { state.currentSelection = []; return; }

    const key = qKey(q);
    const saved = state.answers[key];
    state.currentSelection = [];

    if (saved?.selected_index_json){
      try{
        const arr = JSON.parse(saved.selected_index_json);
        if (Array.isArray(arr)){
          state.currentSelection = arr.map(x => parseInt(x, 10)).filter(n => Number.isInteger(n));
          return;
        }
      }catch(e){}
    }
  }

  function persistCurrentAnswer(isAutoTimeout=false, silent=false) {
    const q = currentQuestion();
    if (!q) return;

    const key = qKey(q);
    const need = (q.bubbles_original || []).length;

    const t = state.timers[key] || { timeLeft: state.perQTime, lastAt: Date.now() };
    const timeLeft = Math.max(0, Number(t.timeLeft ?? state.perQTime));
    const spent = Math.max(0, Math.min(state.perQTime, state.perQTime - timeLeft));

    const selectedIdx = state.currentSelection.slice();
    const selectedLabels = selectedIdx.map(i => q.bubbles_original?.[i] ?? '');

    const isSkipped = selectedIdx.length === 0 ? 'yes' : 'no';

    const expectedIdx = computeExpectedIndices(q);
    const isCorrect = (
      selectedIdx.length === need &&
      expectedIdx.length === need &&
      selectedIdx.every((v, i) => Number(v) === Number(expectedIdx[i]))
    ) ? 'yes' : 'no';

    state.answers[key] = {
      question_uuid: q.uuid || null,
      selected: selectedLabels.length ? String(selectedLabels[selectedLabels.length - 1]) : null,
      is_correct: (isAutoTimeout && selectedIdx.length === 0) ? 'no' : isCorrect,
      spent_time_sec: Math.round(spent),
      is_skipped: (isAutoTimeout && selectedIdx.length === 0) ? 'yes' : isSkipped,
      selected_row_json: selectedIdx.length ? JSON.stringify(selectedLabels) : null,
      selected_index_json: selectedIdx.length ? JSON.stringify(selectedIdx) : null,
    };

    saveCache();
    renderRightPanel();

    if (!silent){
      if (selectedIdx.length === need){
        notify('success', 'Answer saved', isCorrect === 'yes' ? 'Looks correct ✅' : 'Saved (may be incorrect)');
      } else if (selectedIdx.length > 0){
        notify('warning', 'Saved partial', 'You did not select all bubbles.');
      }
    }
  }

  function instructionTextFor(q) {
    const type = String(q?.select_type || 'ascending').toLowerCase();
    return type === 'descending' ? 'Tap answers from Largest to Smallest' : 'Tap answers from Smallest to Largest';
  }

  function updateNavButtons() {
    clampQIndex();

    const total = state.questions.length;
    const last = total ? total - 1 : 0;
    const q = currentQuestion();

    elPrevBtn.disabled = state.qIndex <= 0;

    if (state.qIndex >= last){
      elSubmitBtn.classList.remove('d-none');
      elNextBtn.classList.add('d-none');
    } else {
      elSubmitBtn.classList.add('d-none');
      elNextBtn.classList.remove('d-none');
    }

    const allowSkip = String(state.game?.allow_skip ?? 'no');
    const need = (q?.bubbles_original || []).length;

    if (allowSkip === 'yes'){
      elSkipBtn.classList.remove('d-none');
    } else {
      elSkipBtn.classList.add('d-none');
    }

    if (state.examStarted !== true){
      elNextBtn.disabled = true;
      elSubmitBtn.disabled = true;
      elUndoBtn.disabled = true;
      elClearBtn.disabled = true;
      if (elSkipBtn) elSkipBtn.disabled = true;

      elSelectTip.innerHTML = `Tip: Click <b>Start Exam</b> to begin.`;
      elQHint.innerHTML = `<i class="fa-solid fa-circle-info"></i> Exam will start after accepting instructions.`;
      return;
    } else {
      elUndoBtn.disabled = false;
      elClearBtn.disabled = false;
      if (elSkipBtn) elSkipBtn.disabled = false;
    }

    if (allowSkip === 'no'){
      const ok = state.currentSelection.length === need;
      elNextBtn.disabled = !ok;
      elSubmitBtn.disabled = (state.qIndex >= last) ? !ok : false;
      elSelectTip.innerHTML = `Tip: Select <b>all ${need}</b> bubbles in the exact order.`;
      elQHint.innerHTML = `<i class="fa-solid fa-circle-info"></i> Must select all bubbles to continue.`;
    } else {
      elNextBtn.disabled = false;
      elSubmitBtn.disabled = false;
      elSelectTip.innerHTML = `Tip: You can skip, but wrong/blank answers reduce score.`;
      elQHint.innerHTML = `<i class="fa-solid fa-circle-info"></i> You may skip if needed.`;
    }
  }

  function autoAdvanceOnTimeout() {
    const q = currentQuestion();
    if (!q) return;
    if (state.examStarted !== true) return;

    persistCurrentAnswer(true, true);

    const lastIndex = state.questions.length - 1;

    if (state.qIndex < lastIndex){
      state.qIndex += 1;
      loadQuestion();
      notify('warning', 'Time up!', 'Moved to next question.');
      return;
    }

    if (state.autoSubmitted) return;
    state.autoSubmitted = true;

    submitExamNow(true).catch(() => {});
  }

  function loadQuestion() {
    const q = currentQuestion();
    if (!q) return;

    pauseTimer();

    elRound.textContent = `Round ${state.qIndex + 1}/${state.questions.length}`;
    elQNum.textContent = String(state.qIndex + 1);

    elQTitle.textContent = q.title || `Question ${state.qIndex + 1}`;
    elQSub.textContent = instructionTextFor(q);

    elInstruction.textContent = q.instruction ? String(q.instruction) : instructionTextFor(q);

    restoreSelectionForCurrent();

    const doIntroDrop = (state.introDropDone !== true);
    renderBubbles(doIntroDrop);
    if (doIntroDrop) state.introDropDone = true;

    renderSelectedChips();
    renderRightPanel();
    updateNavButtons();

    if (state.examStarted === true){
      startTimerFor(qKey(q));
    } else {
      renderTimer(qKey(q));
    }
  }

  function buildSubmitAnswersArray(){
    const out = [];
    for (const q of (state.questions || [])) {
      const key = qKey(q);
      const saved = state.answers[key];

      out.push({
        question_uuid: (saved && saved.question_uuid) ? saved.question_uuid : (q.uuid || null),
        selected: saved?.selected ?? null,
        is_correct: saved?.is_correct ?? 'no',
        spent_time_sec: Number.isFinite(+saved?.spent_time_sec) ? parseInt(saved.spent_time_sec, 10) : 0,
        is_skipped: saved?.is_skipped ?? 'yes',
        selected_row_json: saved?.selected_row_json ?? null,
        selected_index_json: saved?.selected_index_json ?? null,
      });
    }
    return out.filter(a => !!a.question_uuid);
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

  /* =========================================================
      ✅ Start Modal — Dynamic Description + Instructions (API)
  ========================================================= */
  function computeTotalPoints(){
    return (state.questions || []).reduce((sum, q) => sum + (parseInt(q?.points || 1, 10) || 0), 0);
  }

  function sanitizeBasicHtml(html){
    return String(html || '')
      .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
      .replace(/\son\w+="[^"]*"/gi, '')
      .replace(/\son\w+='[^']*'/gi, '');
  }

  function isProbablyHtml(str){
    const s = String(str || '').trim();
    if (!s) return false;
    return /<\/?[a-z][\s\S]*>/i.test(s);
  }

  function pickFirstNonEmpty(...vals){
    for (const v of vals){
      const s = String(v ?? '').trim();
      if (s) return s;
    }
    return '';
  }

  function parseRulesArrayFromGame(game){
    const candidates = [
      game?.rules_json,
      game?.instructions_points_json,
      game?.instruction_points_json,
      game?.rules,
      game?.instructions_points,
    ];

    let raw = null;
    for (const c of candidates){
      if (!c) continue;
      if (Array.isArray(c) && c.length) return c.map(x => String(x ?? '').trim()).filter(Boolean);
      const s = String(c).trim();
      if (s) { raw = s; break; }
    }

    if (!raw) return [];

    // JSON array
    try{
      const j = JSON.parse(raw);
      if (Array.isArray(j)){
        return j.map(x => String(x ?? '').trim()).filter(Boolean);
      }
    }catch(e){}

    // newline bullets
    const lines = raw.split(/\r?\n/).map(x => x.trim()).filter(Boolean);
    if (lines.length >= 2) return lines;

    // separators
    return raw.split(/•|\-|;|\|/).map(x => String(x).trim()).filter(Boolean);
  }
  function decodeEntities(str){
  const t = document.createElement('textarea');
  t.innerHTML = String(str ?? '');
  return t.value;
}

function renderSafeHtmlOrText(raw){
  const decoded = decodeEntities(raw);

  // If it looks like HTML, render as HTML (sanitized)
  if (isProbablyHtml(decoded)){
    return sanitizeBasicHtml(decoded);
  }

  // Otherwise render as safe text with <br>
  return nl2br(decoded);
}

function buildModalTextFromGame(game){
  const descRaw = pickFirstNonEmpty(
    game?.description,
    game?.game_description,
    game?.desc,
    game?.about
  );

  const instrRaw = pickFirstNonEmpty(
    game?.instructions_html,
    game?.instruction_html,
    game?.instructions,
    game?.instruction,
    game?.instructions_text
  );

  const blocks = [];

  // ✅ Description (HTML supported)
  if (descRaw) {
    const descHtml = renderSafeHtmlOrText(descRaw);
    blocks.push(`
      <div>
        <b>Game Description</b>
        <div style="margin-top:6px">${descHtml}</div>
      </div>
    `);
  }

  // ✅ Instructions (HTML supported)
  if (instrRaw) {
    const instrHtml = renderSafeHtmlOrText(instrRaw);
    blocks.push(`
      <div style="margin-top:12px">
        <b>Game Instructions</b>
        <div style="margin-top:6px">${instrHtml}</div>
      </div>
    `);
  }

  if (!blocks.length) {
    blocks.push(`
      <div>
        <b>Details</b>
        <div style="margin-top:6px">No description/instructions found for this game.</div>
      </div>
    `);
  }

  return blocks.join('');
}


  function fillStartModalUI(){
    const g = state.game || {};
    const totalQ = (state.questions || []).length;
    const allowSkip = String(g?.allow_skip ?? 'no') === 'yes';

    elStartModalTitle.textContent = g?.title ? String(g.title) : 'Bubble Game Exam';
    elStartModalSub.textContent = state.isRestoredAttempt ? 'Your previous attempt is available. You can resume now.' : 'Please read the instructions before starting.';

    // ✅ 100% dynamic description + instructions from API
    elStartModalDesc.innerHTML = buildModalTextFromGame(g);

    elStartModalTotalQ.textContent = String(totalQ);
    elStartModalPerQ.textContent = String(state.perQTime || 30);
    elStartModalSkip.textContent = allowSkip ? 'Enabled (You can skip)' : 'Disabled (Must answer)';
    elStartModalPoints.textContent = String(computeTotalPoints());

    // ✅ optional dynamic rules list (ONLY IF API provides)
    const rulesArr = parseRulesArrayFromGame(g);
    if (rulesArr.length){
      elStartModalRules.style.display = '';
      elStartModalRules.innerHTML = rulesArr.map(r => `<li>${escapeHtml(r)}</li>`).join('');
    } else {
      elStartModalRules.style.display = 'none';
      elStartModalRules.innerHTML = '';
    }

    // resume card
    if (state.isRestoredAttempt){
      elStartModalResumeCard.style.display = '';
      elStartModalResumeTxt.textContent = `${countAnswered()} answered out of ${totalQ}`;
      elModalStartBtn.innerHTML = `<i class="fa-solid fa-circle-play"></i> Resume Exam`;
      elModalResetBtn.classList.remove('d-none');
    } else {
      elStartModalResumeCard.style.display = 'none';
      elModalStartBtn.innerHTML = `<i class="fa-solid fa-circle-play"></i> Start Exam`;
      elModalResetBtn.classList.add('d-none');
    }
  }

  function showStartModal(){
    if (!elStartModal) return;

    if (!startModal){
      startModal = new bootstrap.Modal(elStartModal, { backdrop: 'static', keyboard: false });
    }

    fillStartModalUI();
    state.startModalShown = true;
    state.examStarted = false;
    pauseTimer();
    updateNavButtons();

    elBubblesWrap.innerHTML = `
      <div class="bgx-loader">
        <i class="fa-solid fa-circle-play"></i>
        Waiting to start… Please read the instructions.
      </div>
    `;

    startModal.show();
  }

  function beginExam(){
    state.examStarted = true;
    state.startModalShown = false;

    if (state.isRestoredAttempt){
      elClearLocal.style.display = '';
    }

    loadQuestion();
    notify('success', 'Exam started', 'Good luck!');
  }

  /* ============ events ============ */
  elPrevBtn.addEventListener('click', () => {
    if (state.examStarted !== true) return;

    persistCurrentAnswer(false, true);
    pauseTimer();

    clampQIndex();
    if (state.qIndex > 0){
      state.qIndex = parseInt(state.qIndex, 10) - 1;
      clampQIndex();
      loadQuestion();
      saveCache();
    }
  });

  elNextBtn.addEventListener('click', () => {
    if (state.examStarted !== true) return;

    persistCurrentAnswer(false, true);
    pauseTimer();

    clampQIndex();
    if (state.qIndex < state.questions.length - 1){
      state.qIndex = parseInt(state.qIndex, 10) + 1;
      clampQIndex();
      loadQuestion();
      saveCache();
    }
  });

  elSkipBtn.addEventListener('click', () => {
    if (state.examStarted !== true) return;

    const allowSkip = String(state.game?.allow_skip ?? 'no');
    if (allowSkip !== 'yes') return;

    state.currentSelection = [];
    persistCurrentAnswer(false, true);
    pauseTimer();

    if (state.qIndex < state.questions.length - 1){
      state.qIndex += 1;
      loadQuestion();
      saveCache();
      notify('info', 'Skipped', 'Moved to next question.');
    } else {
      updateNavButtons();
      notify('info', 'Skipped', 'You can submit now.');
    }
  });

  elUndoBtn.addEventListener('click', () => {
    if (state.examStarted !== true) return;
    if (!state.currentSelection.length) return;
    state.currentSelection.pop();
    renderBubbles(false);
    renderSelectedChips();
    updateNavButtons();
    saveCache();
  });

  elClearBtn.addEventListener('click', () => {
    if (state.examStarted !== true) return;
    state.currentSelection = [];
    renderBubbles(false);
    renderSelectedChips();
    updateNavButtons();
    saveCache();
  });

  elClearLocal.addEventListener('click', async () => {
    const r = await Swal.fire({
      icon: 'warning',
      title: 'Reset attempt?',
      text: 'This will clear locally saved answers for this game.',
      showCancelButton: true,
      confirmButtonText: 'Yes, reset',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#ef4444'
    });
    if (!r.isConfirmed) return;
    clearCache();
    location.reload();
  });

  elQuitBtn.addEventListener('click', async (e) => {
    if (countAnswered() === 0) return;

    e.preventDefault();
    const r = await Swal.fire({
      icon: 'question',
      title: 'Leave exam?',
      text: 'Your attempt is saved locally until you Submit.',
      showCancelButton: true,
      confirmButtonText: 'Leave',
      cancelButtonText: 'Stay',
    });
    if (r.isConfirmed) 
      state.suppressUnloadPrompt = true;
      window.location.href = DASHBOARD_URL;
  });

  elModalStartBtn.addEventListener('click', () => {
    if (startModal){ startModal.hide(); }
    beginExam();
  });

  elModalResetBtn.addEventListener('click', async () => {
    const r = await Swal.fire({
      icon: 'warning',
      title: 'Reset attempt?',
      text: 'This will clear locally saved answers for this game.',
      showCancelButton: true,
      confirmButtonText: 'Yes, reset',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#ef4444'
    });
    if (!r.isConfirmed) return;
    clearCache();
    state.suppressUnloadPrompt = true;

    location.reload();
  });

  async function submitExamNow(isAuto=false){
    if (state.isSubmitting) return;
    state.isSubmitting = true;
  state.suppressUnloadPrompt = true;


    try{
      [elPrevBtn, elNextBtn, elUndoBtn, elClearBtn, elSkipBtn, elSubmitBtn].forEach(b => {
        if (b) b.disabled = true;
      });

      persistCurrentAnswer(isAuto, true);
      pauseTimer();

      const answersArray = buildSubmitAnswersArray();
      const totalTime = answersArray.reduce((sum, a) => sum + (parseInt(a.spent_time_sec || 0, 10) || 0), 0);

      const payload = {
        game_uuid: GAME_UUID,
        answers: answersArray,
        bubble_game_uuid: GAME_UUID,
        user_answer_json: answersArray,
        time_taken_sec: totalTime,
      };

      Swal.fire({
        title: isAuto ? 'Time up! Submitting...' : 'Submitting...',
        text: 'Please wait',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
      });

      await postJson(API.submit, payload);

      Swal.close();
      clearCache();
      notify('success', 'Submitted successfully', 'Redirecting to dashboard…');
      setTimeout(() => window.location.href = DASHBOARD_URL, 900);

    } catch(err){
            state.suppressUnloadPrompt = false;

      Swal.close();

      [elPrevBtn, elNextBtn, elUndoBtn, elClearBtn, elSkipBtn, elSubmitBtn].forEach(b => {
        if (b) b.disabled = false;
      });

      await Swal.fire({
        icon: 'error',
        title: 'Submit failed',
        text: err.message || 'Please try again'
      });

      if ((err.message || '').toLowerCase().includes('login')){
        setTimeout(() => window.location.href = '/login', 900);
      }

    } finally {
      state.isSubmitting = false;
    }
  }

  elSubmitBtn.addEventListener('click', async () => {
    if (state.examStarted !== true) return;

    persistCurrentAnswer(false, true);
    pauseTimer();

    const r = await Swal.fire({
      icon: 'question',
      title: 'Submit exam now?',
      text: 'This will save to database and finish your attempt.',
      showCancelButton: true,
      confirmButtonText: 'Submit',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#22c55e'
    });
    if (!r.isConfirmed) return;

    await submitExamNow(false);
  });

  function beforeUnloadHandler(e) {
  // ✅ Do NOT show browser alert during submit/auto-submit/redirect actions
  if (state.suppressUnloadPrompt === true) return;

  const has = countAnswered() > 0 || state.currentSelection.length > 0;
  if (!has) return;

  e.preventDefault();
  e.returnValue = '';
}
window.addEventListener('beforeunload', beforeUnloadHandler);

  async function init(){
    if (!GAME_UUID){
      elBubblesWrap.innerHTML = `
        <div class="bgx-loader">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Game UUID missing. Open with <b>?game=&lt;uuid&gt;</b>
        </div>
      `;
      elInstruction.textContent = 'Cannot start exam without game uuid.';
      await Swal.fire({ icon:'error', title:'Game UUID missing', text:'Use URL like /bubble-games/exam?game=<uuid>' });
      return;
    }

    if (elStartModal){
      startModal = new bootstrap.Modal(elStartModal, { backdrop: 'static', keyboard: false });
    }

    const restored = loadCache();
    if (restored){
      state.isRestoredAttempt = true;

      elGameTitle.textContent = state.game?.title ? String(state.game.title) : 'Bubble Game Exam';
      elPerQ.textContent = String(state.perQTime);
      renderRightPanel();

      showStartModal();
      loadQuestion();

      elClearLocal.style.display = '';
      notify('success', 'Attempt restored', 'Loaded from sessionStorage.');
      return;
    }

    try{
      const { game, questionsRaw } = await fetchExamData();
      const questions = normalizeQuestions(questionsRaw);

      if (!questions.length){
        elBubblesWrap.innerHTML = `<div class="bgx-loader">No active questions found for this game.</div>`;
        elInstruction.textContent = 'No questions available.';
        notify('warning', 'No questions found', 'Please add active questions to this game.');
        return;
      }

      state.questions = questions;
      applyGameConfig(game);

      saveCache();
      renderRightPanel();

      showStartModal();
      loadQuestion();

    }catch(err){
      elBubblesWrap.innerHTML = `
        <div class="bgx-loader">
          <i class="fa-solid fa-triangle-exclamation"></i>
          ${escapeHtml(err.message || 'Failed to load exam')}
        </div>
      `;
      elInstruction.textContent = 'Failed to load exam.';
      await Swal.fire({ icon:'error', title:'Failed to load exam', text: err.message || '' });

      if ((err.message || '').toLowerCase().includes('login')){
        setTimeout(() => window.location.href = '/login', 900);
      }
    }
  }

  init();
})();
</script>

</body>
</html>
