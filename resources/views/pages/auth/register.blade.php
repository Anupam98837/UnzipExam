{{-- resources/views/auth/student-register.blade.php (Unzip Examination) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Register — Unzip Examination</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <!-- Vendors -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <!-- Global tokens -->
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>

  <style>
    /* =========================
      Namespaced Register (ux-*)
      - SAME UI system as login page
      - ✅ FIX: don't clip submit button on small height / zoom
      ========================= */

    html, body { height:100%; }

    body.ux-auth-body{
      min-height:100%;
      height:auto;
      overflow:auto;
      background:var(--bg-body);
      color:var(--text-color);
      font-family:var(--font-sans);
    }

    .ux-grid{
      min-height:100vh;
      min-height:100svh;
      min-height:100dvh;
      height:auto;

      display:grid;
      grid-template-columns: minmax(420px,560px) 1fr;
      width:100%;
    }
    .ux-left, .ux-right{ min-width:0; }

    @media (max-width: 1440px){ .ux-grid{ grid-template-columns: minmax(400px,540px) 1fr; } }
    @media (max-width: 1366px){ .ux-grid{ grid-template-columns: minmax(380px,520px) 1fr; } }
    @media (max-width: 1280px){ .ux-grid{ grid-template-columns: minmax(360px,500px) 1fr; } }
    @media (max-width: 1200px){ .ux-grid{ grid-template-columns: minmax(340px,480px) 1fr; } }
    @media (max-width: 1100px){ .ux-grid{ grid-template-columns: minmax(320px,460px) 1fr; } }
    @media (max-width: 992px){ .ux-grid{ grid-template-columns: 1fr; } }

    .ux-left{
      min-height:100vh;
      min-height:100svh;
      min-height:100dvh;
      height:auto;

      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:flex-start;

      padding:clamp(18px,5vw,56px);
      padding-bottom:clamp(22px,5vw,64px);

      position:relative;
      isolation:isolate;
      overflow:visible;
    }

    .ux-brand{ margin-top:0; }
    #ux_form{ margin-bottom:0; }

    .ux-brand{
      display:grid;
      place-items:center;
      margin-bottom:18px;
      position:relative;
      z-index:1;
      max-width:100%;
    }
    .ux-brand img{
      height:70px;
      max-width:100%;
      object-fit:contain;
    }

    .ux-title{
      font-family:var(--font-head);
      font-weight:800;
      color:var(--ink);
      text-align:center;
      font-size:clamp(1.5rem, 2.6vw, 2.2rem);
      margin:.35rem 0 .25rem;
      position:relative;
      z-index:1;
      max-width:min(560px, 100%);
      line-height:1.2;
    }

    .ux-sub{
      text-align:center;
      color:var(--muted-color);
      margin-bottom:18px;
      position:relative;
      z-index:1;
      max-width:min(560px, 100%);
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
      max-width:min(460px, 100%);
      overflow:hidden;
      display:flex;
      flex-direction:column;
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
      max-width:100%;
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
      flex-wrap:wrap;
      row-gap:8px;
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
      margin-top:6px;
    }
    .ux-login:hover{
      filter:brightness(.98);
      transform:translateY(-1px);
    }

    .ux-field-err{
      font-size:12px;
      margin-top:6px;
      color:var(--danger-color, #dc3545);
      display:none;
    }
    .ux-field-err.show{ display:block; }

    /* RIGHT visuals */
    .ux-right{
      position:relative;
      min-height:100vh;
      min-height:100svh;
      min-height:100dvh;
      display:grid;
      place-items:center;
      background:
        radial-gradient(120% 100% at 5% 10%, rgba(20,184,166,.18) 0%, rgba(8,47,73,0) 55%),
        linear-gradient(180deg,#022c22,#020617);
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
      max-width:100%;
    }
    @media (max-width: 1366px){ .ux-hero{ width:min(600px, 96%); } }
    @media (max-width: 1200px){ .ux-hero{ width:min(560px, 96%); } }

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
    .ux-badge-pill:nth-child(2){ background:rgba(8,47,73,.9); }
    .ux-badge-pill:nth-child(3){
      background:rgba(234,179,8,.92);
      color:#0b1120;
    }

    @media (max-width: 576px){
      .ux-left{ padding:16px; padding-bottom:26px; }
      .ux-brand img{ height:60px; }
      .ux-card{ padding:18px; border-radius:16px; }
      .ux-control{ height:44px; }
      .ux-login{ height:46px; }
    }

    @keyframes ux-pop{
      from{opacity:0; transform:translateY(10px) scale(.98);}
      to{opacity:1; transform:none;}
    }
    @keyframes ux-zoom{ from{transform:scale(1);} to{transform:scale(1.06);} }
    @keyframes ux-drift{
      0%,100%{transform:translate3d(0,0,0);}
      50%{transform:translate3d(-2%,2%,0);}
    }
    @keyframes ux-spin{ 0%{transform:rotate(0deg);} 100%{transform:rotate(360deg);} }
    @keyframes ux-orbitA{ 0%{transform:translate(0,0);} 50%{transform:translate(6px, -6px);} 100%{transform:translate(0,0);} }
    @keyframes ux-orbitB{ 0%{transform:translate(-6px, 6px);} 100%{transform:translate(0,0);} }
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

@php
  $REGISTER_API    = url('/api/auth/student-register');
  $LOGIN_URL       = url('/login');
  $REDIRECT_AFTER  = url('/dashboard');
@endphp

<div class="ux-grid">

  <!-- LEFT -->
  <section class="ux-left">
    <div class="ux-brand">
      <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </div>

    {{-- ✅ Campaign Title auto-filled by UID --}}
    <h1 class="ux-title" id="ux_campaignTitle">Student Registration</h1>
    <p class="ux-sub" id="ux_campaignSub">Create your account to proceed.</p>

    <form class="ux-card" id="ux_form" novalidate>
      <span class="ux-float-chip"><i class="fa-solid fa-shield-halved me-1"></i>Secure • Token based</span>

      <!-- Inline alert -->
      <div id="ux_alert" class="alert d-none mb-3" role="alert"></div>

      {{-- ✅ Hidden fields (folder + campaign uid) --}}
      <input type="hidden" id="ux_folder_id" value="">
      <input type="hidden" id="ux_campaign_uid" value="">
      <div class="ux-field-err" id="err_user_folder_id"></div>

      <!-- Name -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_name">Full Name</label>
        <div class="ux-input-wrap">
          <input id="ux_name" type="text" class="ux-control form-control"
                 placeholder="Enter your full name" required>
        </div>
        <div class="ux-field-err" id="err_name"></div>
      </div>

      <!-- Email -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_email">Email</label>
        <div class="ux-input-wrap">
          <input id="ux_email" type="email" class="ux-control form-control"
                 placeholder="you@example.com" required>
        </div>
        <div class="ux-field-err" id="err_email"></div>
      </div>

      <!-- Phone -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_phone">Phone Number</label>
        <div class="ux-input-wrap">
          <input id="ux_phone" type="text" class="ux-control form-control"
                 placeholder="90000 00000" required>
        </div>
        <div class="ux-field-err" id="err_phone_number"></div>
      </div>

      <!-- Password -->
      <div class="mb-3">
        <label class="ux-label form-label" for="ux_pw">Password</label>
        <div class="ux-input-wrap">
          <input id="ux_pw" type="password" class="ux-control form-control"
                 placeholder="Minimum 8+ characters" minlength="8" required>
          <button type="button" class="ux-eye" id="ux_togglePw" aria-label="Toggle password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
        <div class="ux-field-err" id="err_password"></div>
      </div>

      <!-- Confirm Password -->
      <div class="mb-2">
        <label class="ux-label form-label" for="ux_pw2">Confirm Password</label>
        <div class="ux-input-wrap">
          <input id="ux_pw2" type="password" class="ux-control form-control"
                 placeholder="Re-type password" minlength="8" required>
          <button type="button" class="ux-eye" id="ux_togglePw2" aria-label="Toggle confirm password visibility">
            <i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
          </button>
        </div>
        <div class="ux-field-err" id="err_password_confirmation"></div>
      </div>

      <div class="ux-row mb-3">
        <div class="form-check m-0">
          <input class="form-check-input" type="checkbox" id="ux_keep">
          <label class="form-check-label" for="ux_keep">Keep me logged in</label>
        </div>

        <a class="text-decoration-none" href="{{ $LOGIN_URL }}">
          Already have account? Login
        </a>
      </div>

      <button class="ux-login" id="ux_btn" type="submit">
        <span class="me-2"><i class="fa-solid fa-user-plus"></i></span> Create Account
      </button>
    </form>
  </section>

  <!-- RIGHT -->
  <aside class="ux-right" id="ux_visual">
    <span class="ux-arc" aria-hidden="true"></span>
    <span class="ux-ring" aria-hidden="true"></span>

    <div class="ux-obj ux-badges" aria-hidden="true">
      <div class="ux-badge-pill">
        <i class="fa-solid fa-clipboard-check"></i> Quick signup
      </div>
      <div class="ux-badge-pill">
        <i class="fa-solid fa-user-graduate"></i> Student portal
      </div>
      <div class="ux-badge-pill">
        <i class="fa-solid fa-shield-halved"></i> Secure access
      </div>
    </div>

    <div class="ux-hero" id="ux_hero">
      <div class="ux-hero-frame">
        <div class="ux-hero-img">
          <img
            src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?w=1600&auto=format&fit=crop&q=80"
            alt="Student registration">
          <div class="ux-particles" aria-hidden="true"></div>
        </div>
      </div>
    </div>
  </aside>
</div>

<script>
(function(){
  // ✅ CONFIG
  const REGISTER_API   = @json($REGISTER_API);
  const LOGIN_URL      = @json($LOGIN_URL);
  const REDIRECT_AFTER = @json($REDIRECT_AFTER);

  // ✅ Campaign API candidates
  function campaignApiCandidates(uid){
    return [
      `/api/interview-registration-campaigns/public/${encodeURIComponent(uid)}`,
      `/api/interview-registration-campaigns/${encodeURIComponent(uid)}`
    ];
  }

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // DOM
  const form    = document.getElementById('ux_form');
  const btn     = document.getElementById('ux_btn');
  const alertEl = document.getElementById('ux_alert');

  const nameIn  = document.getElementById('ux_name');
  const email   = document.getElementById('ux_email');
  const phone   = document.getElementById('ux_phone');
  const pw1     = document.getElementById('ux_pw');
  const pw2     = document.getElementById('ux_pw2');
  const keepCb  = document.getElementById('ux_keep');

  const t1      = document.getElementById('ux_togglePw');
  const t2      = document.getElementById('ux_togglePw2');

  const folderHidden   = document.getElementById('ux_folder_id');
  const campaignHidden = document.getElementById('ux_campaign_uid');
  const titleEl        = document.getElementById('ux_campaignTitle');
  const subEl          = document.getElementById('ux_campaignSub');

  // UI helpers
  function setBusy(b){
    btn.disabled = b;
    btn.innerHTML = b
      ? '<i class="fa-solid fa-spinner fa-spin me-2"></i>Creating account…'
      : '<span class="me-2"><i class="fa-solid fa-user-plus"></i></span> Create Account';
  }

  function showAlert(kind, msg){
    alertEl.classList.remove('d-none','alert-danger','alert-success','alert-warning');
    alertEl.classList.add('alert', kind === 'error' ? 'alert-danger' : (kind === 'warn' ? 'alert-warning' : 'alert-success'));
    alertEl.textContent = msg;
  }

  function clearAlert(){
    alertEl.classList.add('d-none');
    alertEl.textContent = '';
  }

  function clearFieldErrors(){
    document.querySelectorAll('.ux-field-err').forEach(el => {
      el.classList.remove('show');
      el.textContent = '';
    });
  }

  function setFieldError(key, msg){
    const el = document.getElementById('err_' + key);
    if(el){
      el.textContent = msg || 'Invalid value';
      el.classList.add('show');
    }
  }

  // ✅ Get campaign UID from URL
  function getCampaignUid(){
    // supports:
    // /register/{uid}
    // /register?uid={uid}
    const u = new URL(window.location.href);

    const qp =
      u.searchParams.get('uid') ||
      u.searchParams.get('campaign') ||
      u.searchParams.get('c') ||
      u.searchParams.get('id');

    if (qp && String(qp).trim()) return String(qp).trim();

    const parts = (u.pathname || '').split('/').filter(Boolean);
    return parts.length ? parts[parts.length - 1] : '';
  }

  // ✅ Load campaign details by UID
  async function loadCampaign(){
    const uid = getCampaignUid();

    // ✅ REQUIRED: If uid missing -> redirect to login
    if (!uid){
      window.location.assign(LOGIN_URL);
      return;
    }

    campaignHidden.value = uid;

    // temporary UI
    titleEl.textContent = 'Loading campaign…';
    subEl.textContent = 'Please wait…';

    let lastErr = null;

    for (const apiUrl of campaignApiCandidates(uid)){
      try{
        const res = await fetch(apiUrl, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const j = await res.json().catch(()=> ({}));
        if (!res.ok) throw new Error(j?.message || 'Failed to load campaign');

        const row = j?.data || j?.campaign || j;

        const campTitle = row?.title || 'Student Registration';
        const folderId  = row?.user_folder_id ?? row?.folder_id ?? '';

        titleEl.textContent = campTitle;
        subEl.textContent = 'Create your student account to proceed.';

        if (!folderId){
          folderHidden.value = '';
          setFieldError('user_folder_id', 'Campaign folder not configured. Contact admin.');
          showAlert('error', 'Campaign is missing folder configuration.');
          btn.disabled = true;
          return;
        }

        folderHidden.value = String(folderId);
        return; // ✅ success

      }catch(e){
        lastErr = e;
      }
    }

    console.warn('Campaign API failed:', lastErr);
    titleEl.textContent = 'Student Registration';
    subEl.textContent = 'Campaign not found or expired.';
    folderHidden.value = '';
    showAlert('error', lastErr?.message || 'Campaign not found.');
    btn.disabled = true;
  }

  // ✅ Storage helpers
  const authStore = {
    set(token, role, keep){
      sessionStorage.setItem('token', token);
      sessionStorage.setItem('role', role);

      if(keep){
        localStorage.setItem('token', token);
        localStorage.setItem('role', role);
      } else {
        localStorage.removeItem('token');
        localStorage.removeItem('role');
      }
    }
  };

  // password toggles
  function togglePw(input, btn){
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.innerHTML = show
      ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
      : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
  }
  t1?.addEventListener('click', () => togglePw(pw1, t1));
  t2?.addEventListener('click', () => togglePw(pw2, t2));

  // submit
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();
    clearFieldErrors();

    const folderId = (folderHidden.value || '').trim();

    const payload = {
      user_folder_id: folderId,
      name: (nameIn.value || '').trim(),
      email: (email.value || '').trim(),
      phone_number: (phone.value || '').trim(),
      password: pw1.value || '',
      password_confirmation: pw2.value || ''
    };

    if(!payload.user_folder_id){
      setFieldError('user_folder_id', 'Folder missing in campaign. Contact admin.');
      showAlert('warn','Invalid campaign configuration.');
      return;
    }
    if(!payload.name || payload.name.length < 2){
      setFieldError('name', 'Please enter your full name');
      showAlert('warn','Please fix the errors below.');
      return;
    }
    if(!payload.email){
      setFieldError('email', 'Please enter your email');
      showAlert('warn','Please fix the errors below.');
      return;
    }
    if(!payload.phone_number){
      setFieldError('phone_number', 'Please enter your phone number');
      showAlert('warn','Please fix the errors below.');
      return;
    }
    if(!payload.password || payload.password.length < 8){
      setFieldError('password', 'Password must be at least 8 characters');
      showAlert('warn','Please fix the errors below.');
      return;
    }
    if(payload.password !== payload.password_confirmation){
      setFieldError('password_confirmation', 'Passwords do not match');
      showAlert('warn','Please fix the errors below.');
      return;
    }

    setBusy(true);
    try{
      const res = await fetch(REGISTER_API, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json().catch(() => ({}));

      if(res.status === 422){
        const errors = data?.errors || {};
        Object.keys(errors).forEach((k) => {
          const msg = Array.isArray(errors[k]) ? errors[k][0] : errors[k];
          setFieldError(k, msg);
        });
        showAlert('warn', data?.message || 'Please fix the highlighted fields.');
        setBusy(false);
        return;
      }

      if(!res.ok){
        const msg =
          data?.message || data?.error ||
          (data?.errors ? Object.values(data.errors).flat().join(', ') : 'Registration failed.');
        showAlert('error', msg);
        setBusy(false);
        return;
      }

      const token = data?.access_token || data?.token || '';
      const role  = (data?.user?.role || 'student').toString().toLowerCase();

      if(!token){
        showAlert('error', 'No token received from server.');
        setBusy(false);
        return;
      }

      authStore.set(token, role, !!keepCb.checked);
      showAlert('success', 'Registered successfully. Redirecting…');

      setTimeout(() => {
        window.location.assign(REDIRECT_AFTER);
      }, 650);

    } catch(err){
      showAlert('error','Network error. Please try again.');
    } finally {
      setBusy(false);
    }
  });

  // ✅ Load campaign on page load
  loadCampaign();

  // same parallax as login (desktop only)
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
    function onLeave(){ targetTX = targetTY = targetRX = targetRY = 0; }

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
