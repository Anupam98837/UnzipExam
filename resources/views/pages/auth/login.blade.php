{{-- resources/views/auth/login.blade.php (Unzip Examination) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login — Unzip Examination</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <!-- Vendors -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- Global tokens -->
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

  <style>
    /* =========================
       Namespaced Login (ux-*)
       ========================= */

    html, body { height:100%; }
    body.ux-auth-body{
      height:100%;
      overflow:hidden;
      background:var(--bg-body);
      color:var(--text-color);
      font-family:var(--font-sans);
    }

    .ux-grid{
      height:100vh;
      display:grid;
      grid-template-columns: minmax(420px,560px) 1fr;
    }
    @media (max-width: 992px){
      .ux-grid{ grid-template-columns: 1fr; }
    }

    /* LEFT: form column */
    .ux-left{
      height:100vh;
      display:flex;
      flex-direction:column;
      justify-content:center;
      align-items:center;
      padding:clamp(22px,5vw,56px);
      position:relative;
      isolation:isolate;
    }
    .ux-left::before,
    .ux-left::after{
      content:"";
      position:absolute;
      z-index:0;
      pointer-events:none;
      border-radius:50%;
      filter: blur(26px);
      opacity:.25;
      display:none;
    }
    .ux-left::before{
      width:320px; height:320px;
      left:-80px; top:10%;
      background: radial-gradient(closest-side, #facc15, transparent 70%);
      animation: ux-floatA 9s ease-in-out infinite;
    }
    .ux-left::after{
      width:280px; height:280px;
      right:-60px; bottom:14%;
      background: radial-gradient(closest-side, var(--accent-color), transparent 70%);
      animation: ux-floatB 11s ease-in-out infinite;
    }
    @media (max-width: 992px){
      .ux-left::before, .ux-left::after{ display:block; }
    }

    .ux-brand{
      display:grid;
      place-items:center;
      margin-bottom:18px;
      position:relative;
      z-index:1;
    }
    .ux-brand img{
      height:70px;
    }

    .ux-title{
      font-family:var(--font-head);
      font-weight:700;
      color:var(--ink);
      text-align:center;
      font-size:clamp(1.6rem, 2.6vw, 2.2rem);
      margin:.35rem 0 .25rem;
      position:relative;
      z-index:1;
    }
    .ux-sub{
      text-align:center;
      color:var(--muted-color);
      margin-bottom:18px;
      position:relative;
      z-index:1;
    }

    .ux-card{
      position:relative;
      z-index:1;
      background:var(--surface);
      border:1px solid var(--line-strong);
      border-radius:18px;
      padding:24px;
      box-shadow:var(--shadow-2);
      width:100%;
      max-width:430px;
      overflow:hidden;
    }
    .ux-card::before,
    .ux-card::after{
      content:"";
      position:absolute;
      border-radius:50%;
      filter: blur(18px);
      opacity:.25;
      pointer-events:none;
    }
    .ux-card::before{
      width:160px; height:160px;
      left:-40px; top:-40px;
      background: radial-gradient(closest-side, var(--accent-color), transparent 65%);
      animation: ux-orbitA 12s linear infinite;
    }
    .ux-card::after{
      width:140px; height:140px;
      right:-30px; bottom:-30px;
      background: radial-gradient(closest-side, var(--primary-color), transparent 65%);
      animation: ux-orbitB 14s linear infinite reverse;
    }
    .ux-float-chip{
      position:absolute;
      top:12px; right:12px;
      z-index:1;
      padding:6px 10px;
      border-radius:999px;
      font-size:.78rem;
      background:rgba(255,255,255,.7);
      color:var(--secondary-color);
      border:1px solid var(--line-strong);
      backdrop-filter: blur(4px);
      animation: ux-chip 7s ease-in-out infinite;
    }

    .ux-label{ font-weight:600; color:var(--ink); }
    .ux-input-wrap{ position:relative; }
    .ux-control{
      height:46px;
      border-radius:12px;
      padding-right:48px;
    }
    .ux-control::placeholder{ color:#aab2c2; }
    .ux-eye{
      position:absolute;
      top:50%; right:10px;
      transform:translateY(-50%);
      width:36px; height:36px;
      border:none;
      background:transparent;
      color:#8892a6;
      display:grid;
      place-items:center;
      cursor:pointer;
      border-radius:8px;
    }
    .ux-eye:focus-visible{
      outline:none;
      box-shadow: var(--ring);
    }

    .ux-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
    }
    .ux-login{
      width:100%;
      height:48px;
      border:none;
      border-radius:12px;
      font-weight:700;
      color:#fff;
      background:linear-gradient(
          180deg,
          color-mix(in oklab, var(--primary-color) 92%, #fff 8%),
          var(--primary-color)
      );
      box-shadow:0 10px 22px rgba(20,184,166,.26);
      transition:var(--transition);
    }
    .ux-login:hover{
      filter:brightness(.98);
      transform:translateY(-1px);
    }

    /* RIGHT visuals (hidden on mobile) */
    .ux-right{
      position:relative;
      height:100vh;
      display:grid;
      place-items:center;
      background:
        radial-gradient(120% 100% at 5% 10%, rgba(20,184,166,.18) 0%, rgba(8,47,73,0) 55%),
        linear-gradient(180deg,#022c22,#020617);
      padding: clamp(24px, 4vw, 60px);
      isolation:isolate;
      overflow:hidden;
    }
    @media (max-width: 992px){
      .ux-right{ display:none; }
    }

    .ux-arc{
      position:absolute;
      inset: -18% -10% auto auto;
      width:120%; height:140%;
      background:radial-gradient(110% 110% at 80% 20%,
                 rgba(45,212,191,.24) 0%,
                 rgba(15,118,110,.18) 35%,
                 rgba(15,23,42,0) 62%);
      border-bottom-left-radius:48% 44%;
      pointer-events:none;
      animation: ux-drift 16s ease-in-out infinite;
    }
    .ux-ring{
      position:absolute;
      inset:auto -120px -80px auto;
      width:420px; height:420px;
      border-radius:50%;
      background:
        radial-gradient(closest-side, rgba(255,255,255,.14), rgba(255,255,255,0) 70%),
        conic-gradient(from 0deg,
          rgba(20,184,166,.25),
          rgba(56,189,248,.25),
          rgba(20,184,166,.25));
      filter:blur(18px);
      opacity:.18;
      pointer-events:none;
      animation: ux-spin 24s linear infinite;
    }

    .ux-hero{
      position:relative;
      width:min(680px, 96%);
      aspect-ratio: 3/4;
      animation: ux-pop .7s ease-out both;
    }
    .ux-hero-frame{
      position:relative;
      width:100%; height:100%;
      padding:20px;
      border-radius:36px;
      background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
      box-shadow:
        0 24px 54px rgba(0,0,0,.35),
        0 0 0 1px rgba(255,255,255,.06) inset;
      transition: transform .25s ease, box-shadow .25s ease;
      will-change: transform;
    }
    .ux-hero-img{
      width:100%; height:100%;
      border-radius:24px;
      overflow:hidden;
      position:relative;
      box-shadow:0 18px 40px rgba(0,0,0,.35);
    }
    .ux-hero-img img{
      width:100%; height:100%;
      object-fit:cover;
      display:block;
      transform:translateZ(0);
      animation: ux-zoom 26s ease-in-out infinite alternate;
      will-change: transform;
    }
    .ux-particles{
      position:absolute;
      inset:0;
      pointer-events:none;
      opacity:.28;
      background:
        radial-gradient(#ffffff 1px, transparent 2px) 0 0/22px 22px,
        radial-gradient(#ffffff 1px, transparent 2px) 11px 11px/22px 22px;
      mix-blend-mode: overlay;
      animation: ux-twinkle 12s linear infinite;
    }

    .ux-hero:hover .ux-hero-frame{
      transform:translateY(-4px);
      box-shadow:
        0 30px 64px rgba(0,0,0,.42),
        0 0 0 1px rgba(255,255,255,.10) inset,
        0 0 0 8px rgba(20,184,166,.10);
    }

    /* Small decorative exam objects */
    .ux-obj{
      position:absolute;
      z-index:3;
      opacity:.9;
      filter: drop-shadow(0 8px 18px rgba(0,0,0,.28));
      user-select:none;
      pointer-events:none;
    }
    .ux-badges{
      top: clamp(18px, 3vw, 36px);
      left: clamp(12px, 2vw, 28px);
      display:grid;
      gap:6px;
    }
    .ux-badge-pill{
      min-width:120px; height:24px;
      padding:0 12px;
      border-radius:999px;
      font-size:11px;
      display:flex;align-items:center;gap:6px;
      background:rgba(15,118,110,.88);
      color:#e0f2f1;
    }
    .ux-badge-pill:nth-child(2){
      background:rgba(8,47,73,.9);
    }
    .ux-badge-pill:nth-child(3){
      background:rgba(234,179,8,.92);
      color:#0b1120;
    }

    .ux-cardstack{
      right: clamp(16px, 3vw, 36px);
      bottom: clamp(18px, 3vw, 36px);
      width:120px; height:110px;
      position:relative;
    }
    .ux-cardstack-slot{
      position:absolute;
      inset:auto 0 0 0;
      height:70px;
      border-radius:16px;
      background:linear-gradient(160deg,#022c22,#0f172a);
      border:1px solid rgba(255,255,255,.12);
    }
    .ux-ticket{
      position:absolute;
      left:8px; bottom:32px;
      width:86px; height:42px;
      border-radius:10px;
      background:linear-gradient(145deg,#22c55e,#15803d);
      box-shadow:0 8px 18px rgba(0,0,0,.4);
      transform-origin:bottom left;
      animation: ux-sway 5s ease-in-out infinite;
    }
    .ux-ticket:nth-child(3){
      left:32px; bottom:52px;
      background:linear-gradient(145deg,#38bdf8,#0ea5e9);
      animation-delay:.7s;
    }

    /* Animations */
    @keyframes ux-pop{
      from{opacity:0; transform:translateY(10px) scale(.98);}
      to{opacity:1; transform:none;}
    }
    @keyframes ux-zoom{
      from{transform:scale(1);}
      to{transform:scale(1.06);}
    }
    @keyframes ux-drift{
      0%,100%{transform:translate3d(0,0,0);}
      50%{transform:translate3d(-2%,2%,0);}
    }
    @keyframes ux-spin{
      0%{ transform:rotate(0deg);}
      100%{ transform:rotate(360deg);}
    }
    @keyframes ux-sway{
      0%,100%{ transform:rotate(-3deg);}
      50%{ transform:rotate(3deg);}
    }
    @keyframes ux-floatA{
      0%,100%{ transform:translate(0,0);}
      50%{ transform:translate(10px, -14px);}
    }
    @keyframes ux-floatB{
      0%,100%{ transform:translate(0,0);}
      50%{ transform:translate(-12px, 10px);}
    }
    @keyframes ux-orbitA{
      0%{transform:translate(0,0);}
      50%{transform:translate(6px, -6px);}
      100%{transform:translate(0,0);}
    }
    @keyframes ux-orbitB{
      0%{transform:translate(0,0);}
      50%{transform:translate(-6px, 6px);}
      100%{transform:translate(0,0);}
    }
    @keyframes ux-chip{
      0%,100%{ transform:translateY(0);}
      50%{ transform:translateY(-6px);}
    }
    @keyframes ux-twinkle{
      0%{opacity:.22;}
      50%{opacity:.34;}
      100%{opacity:.22;}
    }
  </style>
</head>
<body class="ux-auth-body">

<div class="ux-grid">
  <!-- LEFT: LOGIN FORM -->
  <section class="ux-left">
    <div class="ux-brand">
      {{-- Put your Unzip Exam logo here --}}
      <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </div>

    <h1 class="ux-title">Welcome to Unzip Examination</h1>
    <p class="ux-sub">Sign in to manage exams, students, and results.</p>

    <form class="ux-card" id="ux_form" action="/login" method="post" novalidate>
      {{-- <span class="ux-float-chip">Secure • Token based login</span> --}}
      @csrf

      <!-- Alerts -->
      <div id="ux_alert" class="alert d-none mb-3" role="alert"></div>

      <!-- Email (or phone label — API expects email) -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_id_or_email">Email or Phone Number</label>
        <div class="ux-input-wrap">
          <input id="ux_id_or_email" type="text" class="ux-control form-control" name="identifier"
                 placeholder="you@example.com or 90000 00000" required>
        </div>
      </div>

      <!-- Password -->
      <div class="mb-2">
        <label class="ux-label form-label" for="ux_pw">Password</label>
        <div class="ux-input-wrap">
          <input id="ux_pw" type="password" class="ux-control form-control" name="password"
                 placeholder="Enter at least 8+ characters" minlength="8" required>
          <button type="button" class="ux-eye" id="ux_togglePw" aria-label="Toggle password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <div class="ux-row mb-3">
        <div class="form-check m-0">
          <input class="form-check-input" type="checkbox" id="ux_keep">
          <label class="form-check-label" for="ux_keep">Keep me logged in</label>
        </div>
        <a class="text-decoration-none" href="/forgot-password">Forgot password?</a>
      </div>

      <button class="ux-login" id="ux_btn" type="submit">
        <span class="me-2"><i class="fa-solid fa-right-to-bracket"></i></span> Login
      </button>
    </form>
  </section>

  <!-- RIGHT: VISUAL (hidden on mobile) -->
  <aside class="ux-right" id="ux_visual">
    <span class="ux-arc" aria-hidden="true"></span>
    <span class="ux-ring" aria-hidden="true"></span>

    <div class="ux-obj ux-badges" aria-hidden="true">
      <div class="ux-badge-pill">
        <i class="fa-solid fa-clipboard-check"></i> Secure assessments
      </div>
      <div class="ux-badge-pill">
        <i class="fa-solid fa-user-graduate"></i> Student analytics
      </div>
      <div class="ux-badge-pill">
        <i class="fa-solid fa-clock"></i> Real-time monitoring
      </div>
    </div>

    <div class="ux-obj ux-cardstack" aria-hidden="true">
      <div class="ux-cardstack-slot"></div>
      <div class="ux-ticket"></div>
      <div class="ux-ticket"></div>
    </div>

    <div class="ux-hero" id="ux_hero">
      <div class="ux-hero-frame">
        <div class="ux-hero-img">
          <img
            src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?w=1600&auto=format&fit=crop&q=80"
            alt="Students taking an online examination">
          <div class="ux-particles" aria-hidden="true"></div>
        </div>
      </div>
    </div>
  </aside>
</div>

<script>
  (function(){
    // ---- CONFIG (uses your Unzip Exam UserController APIs) ----
    const LOGIN_API = "/api/auth/login";
    const CHECK_API = "/api/auth/check";

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ---- DOM ----
    const form    = document.getElementById('ux_form');
    const emailIn = document.getElementById('ux_id_or_email');
    const pwIn    = document.getElementById('ux_pw');
    const keepCb  = document.getElementById('ux_keep');
    const btn     = document.getElementById('ux_btn');
    const alertEl = document.getElementById('ux_alert');
    const toggle  = document.getElementById('ux_togglePw');

    // ---- UI helpers ----
    function setBusy(b){
      btn.disabled = b;
      btn.innerHTML = b
        ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Signing you in…'
        : '<span class="me-2"><i class="fa-solid fa-right-to-bracket"></i></span> Login';
    }
    function showAlert(kind, msg){
      alertEl.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-warning');
      alertEl.classList.add(
        'alert',
        kind === 'error'
          ? 'alert-danger'
          : (kind === 'warn' ? 'alert-warning' : 'alert-success')
      );
      alertEl.textContent = msg;
    }
    function clearAlert(){
      alertEl.classList.add('d-none');
      alertEl.textContent = '';
    }

    // ---- Storage helpers (keys EXACTLY "token" and "role") ----
    const authStore = {
      set(token, role, keep){
        sessionStorage.setItem('token', token);
        sessionStorage.setItem('role', role);
        if (keep){
          localStorage.setItem('token', token);
          localStorage.setItem('role', role);
        } else {
          localStorage.removeItem('token');
          localStorage.removeItem('role');
        }
      },
      clear(){
        sessionStorage.removeItem('token');
        sessionStorage.removeItem('role');
        localStorage.removeItem('token');
        localStorage.removeItem('role');
      },
      getLocal(){
        return {
          token: localStorage.getItem('token'),
          role:  localStorage.getItem('role')
        };
      }
    };

    // ---- Build role dashboard path ----
    function rolePath(role){
      const r = (role || '').toString().trim().toLowerCase();
      if(!r) return '/dashboard';
      return `/dashboard`;
    }

    // ---- Password eye toggle ----
    toggle?.addEventListener('click', () => {
      const show = pwIn.type === 'password';
      pwIn.type = show ? 'text' : 'password';
      toggle.innerHTML = show
        ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
        : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
    });

    // ---- Auto-redirect if a remembered token exists (verify via /auth/check) ----
    async function tryAutoLoginFromLocal(){
      const { token, role } = authStore.getLocal();
      if(!token) return;

      try{
        const res = await fetch(CHECK_API, {
          headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json().catch(() => ({}));
        if(res.ok && data && data.user){
          const resolvedRole = (data.user.role || role || '').toString().toLowerCase();
          authStore.set(token, resolvedRole, true);
          window.location.replace(rolePath(resolvedRole));
        } else {
          authStore.clear();
          showAlert('error', data?.message || 'Your session expired. Please log in again.');
        }
      } catch(e){
        // network error -> stay on login page silently
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      tryAutoLoginFromLocal();
    });

    // ---- Handle form submit -> call /api/auth/login ----
    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearAlert();

      const identifier = (emailIn.value || '').trim();
      const password   = pwIn.value || '';
      const keep       = !!keepCb.checked;

      if(!identifier || !password){
        showAlert('error','Please enter both email and password.');
        return;
      }

      setBusy(true);
      try{
        const res = await fetch(LOGIN_API, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf
          },
          body: JSON.stringify({ email: identifier, password, remember: keep })
        });

        const data = await res.json().catch(() => ({}));

        if(!res.ok){
          const msg = data?.message || data?.error ||
            (data?.errors ? Object.values(data.errors).flat().join(', ') : 'Unable to log in.');
          showAlert('error', msg);
          setBusy(false);
          return;
        }

        const token = data?.access_token || data?.token || '';
        const role  = (data?.user?.role || localStorage.getItem('role') || 'student').toLowerCase();

        if(!token){
          showAlert('error', 'No token received from server.');
          setBusy(false);
          return;
        }

        authStore.set(token, role, keep);

        showAlert('success', 'Login successful. Redirecting…');
        setTimeout(() => {
          window.location.assign(rolePath(role));
        }, 500);

      } catch(err){
        showAlert('error','Network error. Please try again.');
      } finally {
        setBusy(false);
      }
    });

    // ---- Parallax hero (desktop only) ----
    (function(){
      const stage  = document.getElementById('ux_visual');
      const hero   = document.getElementById('ux_hero');
      const frame  = document.querySelector('.ux-hero-frame');
      const img    = document.querySelector('.ux-hero-img img');
      if (!stage || !frame || !img || !hero) return;

      const mq = window.matchMedia('(max-width: 992px)');
      let targetTX = 0, targetTY = 0, targetRX = 0, targetRY = 0;
      let currTX = 0, currTY = 0, currRX = 0, currRY = 0;
      let rafId = null;

      const MAX_T = 18, MAX_RX = 6, MAX_RY = 8, LERP = 0.12;

      function onMove(e){
        const rect = stage.getBoundingClientRect();
        const cx = rect.left + rect.width/2;
        const cy = rect.top  + rect.height/2;
        const dx = (e.clientX - cx) / (rect.width/2);
        const dy = (e.clientY - cy) / (rect.height/2);
        const ndx = Math.max(-1, Math.min(1, dx));
        const ndy = Math.max(-1, Math.min(1, dy));

        targetTX = ndx * MAX_T;
        targetTY = ndy * MAX_T;
        targetRY = ndx * MAX_RY;
        targetRX = -ndy * MAX_RX;

        if (!hero.classList.contains('is-tracking')){
          hero.classList.add('is-tracking');
          tick();
        }
      }
      function onLeave(){
        targetTX = targetTY = targetRX = targetRY = 0;
      }
      function tick(){
        currTX += (targetTX - currTX) * LERP;
        currTY += (targetTY - currTY) * LERP;
        currRX += (targetRX - currRX) * LERP;
        currRY += (targetRY - currRY) * LERP;

        frame.style.transform =
          `translate3d(${currTX.toFixed(2)}px, ${currTY.toFixed(2)}px, 0)
           rotateX(${currRX.toFixed(2)}deg)
           rotateY(${currRY.toFixed(2)}deg)`;

        const ix = (-currTX * 0.6).toFixed(2);
        const iy = (-currTY * 0.6).toFixed(2);
        img.style.transform = `translate3d(${ix}px, ${iy}px, 0) scale(1.05)`;

        const nearZero =
          Math.abs(currTX) < 0.15 && Math.abs(currTY) < 0.15 &&
          Math.abs(currRX) < 0.08 && Math.abs(currRY) < 0.08 &&
          Math.abs(targetTX) < 0.15 && Math.abs(targetTY) < 0.15 &&
          Math.abs(targetRX) < 0.08 && Math.abs(targetRY) < 0.08;

        if (!nearZero){
          rafId = requestAnimationFrame(tick);
        } else {
          frame.style.transform = 'translate3d(0,0,0) rotateX(0) rotateY(0)';
          img.style.transform = 'translate3d(0,0,0) scale(1)';
          hero.classList.remove('is-tracking');
          rafId && cancelAnimationFrame(rafId);
          rafId = null;
        }
      }
      function attach(){
        if (mq.matches) return;
        stage.addEventListener('mousemove', onMove);
        stage.addEventListener('mouseleave', onLeave);
      }
      function detach(){
        stage.removeEventListener('mousemove', onMove);
        stage.removeEventListener('mouseleave', onLeave);
        onLeave();
      }

      attach();
      mq.addEventListener('change', () => { detach(); attach(); });
      window.addEventListener('blur', onLeave);
    })();
  })();
</script>
</body>
</html>
