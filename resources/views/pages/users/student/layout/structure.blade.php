{{-- resources/views/layouts/student/structure.blade.php (Unzip Examination) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>@yield('title','Unzip Examination Student')</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/favicons/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}">

  {{-- page-level styles from views (dashboard, manage quizzes, etc.) --}}
  @stack('styles')
  @yield('styles')

  <style>
    /* ================= Unzip Examination Student Layout ================= */
    :root{
      --w3-rail-w: 256px;
      --w3-rail-bg:       var(--surface);
      --w3-rail-text:     var(--text-color);
      --w3-rail-muted:    var(--muted-color);
      --w3-rail-border:   var(--line-strong);
      --w3-rail-hover:    rgba(15,35,32,.045);
      --w3-rail-active:   rgba(0,143,122,.12);

      --w3-rule-grad-l:   linear-gradient(90deg, rgba(2,6,23,0), rgba(2,6,23,.14), rgba(2,6,23,0));
      --w3-rule-grad-d:   linear-gradient(90deg, rgba(226,232,240,0), rgba(226,232,240,.22), rgba(226,232,240,0));
    }

    body{min-height:100dvh;background:var(--bg-body);color:var(--text-color)}

    /* Sidebar */
    .w3-sidebar{
      position:fixed; inset:0 auto 0 0; width:var(--w3-rail-w); background:var(--w3-rail-bg);
      border-right:1px solid var(--w3-rail-border); display:flex; flex-direction:column; z-index:1041;
      transform:translateX(0); transition:transform .28s ease;
    }
    .w3-sidebar-head{
      height:88px;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:12px 0;
      border-bottom:1px solid var(--w3-rail-border);
    }
    .w3-brand{
      display:flex;
      align-items:center;
      justify-content:center;
      text-decoration:none;
    }
    .w3-brand img{
      height:56px;
      width:auto;
      max-width:180px;
      display:block;
    }

    .w3-sidebar-scroll{flex:1; overflow:auto; padding:8px 10px}

    /* Section separators */
    .w3-nav-section{padding:10px 6px 6px}
    .w3-section-title{
      display:flex; align-items:center; gap:8px; color:var(--primary-color);
      font-size:.72rem; font-weight:700; letter-spacing:.12rem; text-transform:uppercase; padding:0 6px;
    }
    .w3-section-rule{height:10px; display:grid; align-items:center}
    .w3-section-rule::before{content:""; height:1px; width:100%; background:var(--w3-rule-grad-l)}
    html.theme-dark .w3-section-rule::before{ background:var(--w3-rule-grad-d) }

    /* Menu */
    .w3-menu{display:grid; gap:4px; padding:6px 4px}
    .w3-link{
      display:flex; align-items:center; gap:10px; padding:9px 10px;
      color:var(--w3-rail-text); border-radius:10px; transition:background .18s ease, transform .18s ease;
    }
    .w3-link i{opacity:.9; min-width:18px; text-align:center}
    .w3-link:hover{background:var(--w3-rail-hover); transform:translateX(2px)}
    .w3-link.active{background:var(--w3-rail-active); position:relative}
    .w3-link.active::before{
      content:""; position:absolute; left:-6px; top:8px; bottom:8px; width:3px; background:var(--accent-color); border-radius:4px;
    }

    .w3-sidebar-foot{border-top:1px solid var(--w3-rail-border); padding:8px 10px}

    /* Appbar */
    .w3-appbar{
      position:sticky; top:0; z-index:1030; height:64px; background:var(--surface);
      border-bottom:1px solid var(--line-strong); display:flex; align-items:center;
    }
    .w3-appbar-inner{
      width:100%;
      display:flex; align-items:center; gap:10px; padding:0 12px;
    }
    @media (min-width: 992px){
      .w3-appbar-inner{ margin-left: 0; }
    }

    .w3-app-logo{
      display:flex;
      align-items:center;
      text-decoration:none;
      gap:8px;
    }
    .w3-app-logo img{height:22px}
    .w3-app-logo span{
      font-family:var(--font-head);
      font-weight:700;
      color:var(--ink);
      font-size:.98rem;
    }

    .w3-icon-btn{
      width:36px; height:36px; display:inline-grid; place-items:center; border:1px solid var(--line-strong);
      background:#fff; color:var(--secondary-color); border-radius:999px; transition:transform .18s ease, background .18s ease;
    }
    .w3-icon-btn:hover{background:#f6f8fc; transform:translateY(-1px)}

    /* Hamburger */
    .w3-hamburger{width:40px; height:40px; border:1px solid var(--line-strong); border-radius:999px; background:#fff; display:inline-grid; place-items:center; cursor:pointer}
    .w3-bars{position:relative; width:18px; height:12px}
    .w3-bar{position:absolute; left:0; width:100%; height:2px; background:#1f2a44; border-radius:2px; transition:transform .25s ease, opacity .2s ease, top .25s ease}
    .w3-bar:nth-child(1){top:0}
    .w3-bar:nth-child(2){top:5px}
    .w3-bar:nth-child(3){top:10px}
    .w3-hamburger.is-active .w3-bar:nth-child(1){top:5px; transform:rotate(45deg)}
    .w3-hamburger.is-active .w3-bar:nth-child(2){opacity:0}
    .w3-hamburger.is-active .w3-bar:nth-child(3){top:5px; transform:rotate(-45deg)}

    /* Content */
    .w3-content{
      padding:16px;
      max-width:1280px;
      margin-inline:auto;
      transition:padding .28s ease;
    }
    @media (min-width: 992px){
      .w3-content{ padding-left: calc(16px + var(--w3-rail-w)); }
    }

    /* Overlay (mobile) */
    .w3-overlay{
      position:fixed; top:0; bottom:0; right:0; left:var(--w3-rail-w);
      background:rgba(0,0,0,.45); z-index:1040; opacity:0; visibility:hidden; pointer-events:none;
      transition:opacity .2s ease, visibility .2s ease;
    }
    .w3-overlay.w3-on{opacity:1; visibility:visible; pointer-events:auto}

    /* Utilities */
    .rounded-xs{ border-radius:6px; }

    /* Mobile */
    @media (max-width: 991px){
      .w3-sidebar{transform:translateX(-100%)}
      .w3-sidebar.w3-on{transform:translateX(0)}
      .w3-content{ padding-left:16px; }
      .w3-appbar-inner{margin-left:0; padding-inline:10px}
      .js-theme-btn{display:none!important}
      .w3-overlay{left:var(--w3-rail-w)}
      .w3-app-logo{display:flex}
    }
    @media (min-width: 992px){
      .w3-app-logo{display:none}
    }

    /* Dark mode */
    html.theme-dark .w3-sidebar{background:var(--surface); border-right-color:var(--line-strong)}
    html.theme-dark .w3-sidebar-head{border-bottom-color:var(--line-strong)}
    html.theme-dark .w3-link:hover{background:#0c172d}
    html.theme-dark .w3-link.active{background:rgba(0,143,122,.12)}
    html.theme-dark .w3-overlay{background:rgba(0,0,0,.55)}

    html.theme-dark .w3-appbar{background:var(--surface); border-bottom-color:var(--line-strong)}
    html.theme-dark .w3-icon-btn, html.theme-dark .w3-hamburger{
      background:var(--surface); border-color:var(--line-strong); color:var(--text-color)
    }
    html.theme-dark .w3-icon-btn:hover, html.theme-dark .w3-hamburger:hover{background:#0c172d}
    html.theme-dark .w3-bar{ background:#e8edf7; }

    html.theme-dark .dropdown-menu{ background:#0f172a; border-color:var(--line-strong); }
    html.theme-dark .dropdown-menu .dropdown-header{ color:var(--text-color); }
    html.theme-dark .dropdown-menu .dropdown-item{ color:var(--text-color); }
    html.theme-dark .dropdown-menu .dropdown-item:hover{ background:#13203a; color:var(--accent-color); }
  </style>

  <style>
    /* Dark mode scrollbars */
    html.theme-dark ::-webkit-scrollbar { width: 8px !important; }
    html.theme-dark ::-webkit-scrollbar-track {
      background: #1e293b !important;
      border-radius: 4px !important;
    }
    html.theme-dark ::-webkit-scrollbar-thumb {
      background: #475569 !important;
      border-radius: 4px !important;
    }
    html.theme-dark ::-webkit-scrollbar-thumb:hover { background: #64748b !important; }

    html.theme-dark .w3-sidebar-scroll::-webkit-scrollbar { width: 6px !important; }
    html.theme-dark .w3-sidebar-scroll::-webkit-scrollbar-track { background: #1e293b !important; }
    html.theme-dark .w3-sidebar-scroll::-webkit-scrollbar-thumb { background: #475569 !important; }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar" class="w3-sidebar" aria-label="Sidebar">
  <div class="w3-sidebar-head">
    <a href="/student/dashboard" class="w3-brand">
      <img id="logo" src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </a>
  </div>

  <div class="w3-sidebar-scroll">
    <!-- Overview -->
    <div class="w3-nav-section">
      <div class="w3-section-title text"><i class="fa-solid fa-chart-line"></i> OVERVIEW</div>
      <div class="w3-section-rule"></div>
    </div>
    <nav class="w3-menu" aria-label="Overview">
      <a href="/student/dashboard" class="w3-link">
        <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
      </a>
    </nav>

    <!-- My Learning -->
    <div class="w3-nav-section">
      <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> MY LEARNING</div>
      <div class="w3-section-rule"></div>
    </div>
    <nav class="w3-menu" aria-label="My Learning">
      <a href="/student/quizzes" class="w3-link">
        <i class="fa-solid fa-book-open"></i><span>My Quizzes</span>
      </a>
    </nav>

    <!-- Account (visible only on small screens) -->
    <div class="w3-nav-section d-lg-none">
      <div class="w3-section-title"><i class="fa-solid fa-user"></i> ACCOUNT</div>
      <div class="w3-section-rule"></div>
    </div>
    <nav class="w3-menu d-lg-none" aria-label="Account">
      <a href="/student/profile" class="w3-link"><i class="fa fa-id-badge"></i><span>Profile</span></a>
      <a href="/student/settings" class="w3-link"><i class="fa fa-gear"></i><span>Settings</span></a>
    </nav>
  </div>

  <div class="w3-sidebar-foot">
    <a href="#" id="logoutBtnSidebar" class="w3-link" style="padding:8px 10px">
      <i class="fa fa-right-from-bracket"></i><span>Logout</span>
    </a>
  </div>
</aside>

<!-- Appbar -->
<header class="w3-appbar">
  <div class="w3-appbar-inner">
    <button id="btnHamburger" class="w3-hamburger d-lg-none" aria-label="Open menu" aria-expanded="false" title="Menu">
      <span class="w3-bars" aria-hidden="true">
        <span class="w3-bar"></span><span class="w3-bar"></span><span class="w3-bar"></span>
      </span>
    </button>

    <!-- Mobile brand -->
    <a href="/student/dashboard" class="w3-app-logo d-lg-none">
      <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
      <span>Student</span>
    </a>

    <strong class="ms-1 d-none d-lg-inline" style="font-family:var(--font-head);color:var(--ink)">
      @yield('title','Unzip Examination Student')
    </strong>

    <div class="ms-auto d-flex align-items-center gap-2">
      <!-- Theme toggle (desktop only) -->
      <button id="btnTheme" class="w3-icon-btn js-theme-btn d-none d-lg-inline-grid" aria-label="Toggle theme" title="Toggle theme">
        <i class="fa-regular fa-moon" id="themeIcon"></i>
      </button>

      <!-- Notifications -->
      <div class="dropdown">
        <a href="#" class="w3-icon-btn" id="notificationsMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications" title="Notifications">
          <i class="fa-regular fa-bell"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end p-2 shadow" style="min-width:320px">
          <div class="d-flex align-items-center justify-content-between px-2 mb-2">
            <strong>Notifications</strong>
            <a class="text-muted" href="/student/notifications">View all</a>
          </div>
          <div class="w3-note rounded-xs">
            <div class="small">No new notifications.</div>
          </div>
        </div>
      </div>

      <!-- User (desktop only) -->
      <div class="dropdown d-none d-lg-block">
        <a href="#" class="btn btn-primary rounded-pill d-flex align-items-center gap-2 px-3" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fa-regular fa-user"></i>
          <span id="userRoleLabel" class="d-none d-xl-inline">Student</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li class="dropdown-header">Account</li>
          <li><a class="dropdown-item" href="/student/profile"><i class="fa fa-id-badge me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="/student/settings"><i class="fa fa-gear me-2"></i>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" id="logoutBtn"><i class="fa fa-right-from-bracket me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>

<!-- Overlay (mobile) -->
<div id="sidebarOverlay" class="w3-overlay" aria-hidden="true"></div>

<!-- Content -->
<main class="w3-content mx-auto">
  <section class="panel mx-auto">@yield('content')</section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@stack('scripts')
@yield('scripts')

<script>
document.addEventListener('DOMContentLoaded', () => {
  const html = document.documentElement;
  const THEME_KEY = 'theme';
  const btnTheme = document.getElementById('btnTheme');
  const themeIcon = document.getElementById('themeIcon');

  // ===== Theme
  function setTheme(mode){
    const isDark = mode === 'dark';
    html.classList.toggle('theme-dark', isDark);
    localStorage.setItem(THEME_KEY, mode);
    if (themeIcon) themeIcon.className = isDark ? 'fa-regular fa-sun' : 'fa-regular fa-moon';
  }
  setTheme(localStorage.getItem(THEME_KEY) || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
  btnTheme?.addEventListener('click', () => setTheme(html.classList.contains('theme-dark') ? 'light' : 'dark'));

  // ===== Sidebar toggle
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const btnHamburger = document.getElementById('btnHamburger');

  const openSidebar = () => {
    sidebar.classList.add('w3-on');
    overlay.classList.add('w3-on');
    btnHamburger?.classList.add('is-active');
    btnHamburger?.setAttribute('aria-expanded','true');
    btnHamburger?.setAttribute('aria-label','Close menu');
  };
  const closeSidebar = () => {
    sidebar.classList.remove('w3-on');
    overlay.classList.remove('w3-on');
    btnHamburger?.classList.remove('is-active');
    btnHamburger?.setAttribute('aria-expanded','false');
    btnHamburger?.setAttribute('aria-label','Open menu');
  };

  btnHamburger?.addEventListener('click', () => sidebar.classList.contains('w3-on') ? closeSidebar() : openSidebar());
  overlay?.addEventListener('click', closeSidebar);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });

  // ===== Active link + open parent
  const path = window.location.pathname.replace(/\/+$/, '');
  document.querySelectorAll('.w3-menu a[href]').forEach(a => {
    const href = a.getAttribute('href');
    if (href && href !== '#' && href.replace(/\/+$/, '') === path){
      a.classList.add('active');
    }
  });

  // ===== Role label ("Student")
  const roleLabelEl = document.getElementById('userRoleLabel');
  function titleizeRole(r){
    if (!r) return 'Student';
    return r.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  }
  const roleFromStorage = sessionStorage.getItem('role') || localStorage.getItem('role');
  roleLabelEl && (roleLabelEl.textContent = titleizeRole(roleFromStorage) || 'Student');

  // ===== Logout (uses /api/auth/logout)
  const API_LOGOUT = '/api/auth/logout';
  const LOGIN_PAGE = '/';

  function getBearerToken(){
    return sessionStorage.getItem('token') || localStorage.getItem('token') || null;
  }
  function clearAuthStorage(){
    try { sessionStorage.removeItem('token'); } catch(e){}
    try { sessionStorage.removeItem('role'); } catch(e){}
    try { localStorage.removeItem('token'); } catch(e){}
    try { localStorage.removeItem('role'); } catch(e){}
  }

  async function performLogout(){
    const token = getBearerToken();

    const confirm = await Swal.fire({
      title: 'Log out?',
      text: 'You will be signed out of Unzip Examination.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, logout',
      cancelButtonText: 'Cancel',
      focusCancel: true,
      confirmButtonColor: '#008f7a'
    });

    if (!confirm.isConfirmed) return;

    let ok = false;
    if (token){
      try{
        const res = await fetch(API_LOGOUT, {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
          body: ''
        });
        ok = res.ok;
      }catch(e){ ok = false; }
    }

    clearAuthStorage();

    await Swal.fire({
      title: ok ? 'Logged out' : 'Signed out locally',
      text: ok ? 'See you soon 👋' : 'Your session was cleared on this device.',
      icon: ok ? 'success' : 'info',
      timer: 1200,
      showConfirmButton: false
    });

    window.location.replace(LOGIN_PAGE);
  }

  document.getElementById('logoutBtn')?.addEventListener('click', (e) => { e.preventDefault(); performLogout(); });
  document.getElementById('logoutBtnSidebar')?.addEventListener('click', (e) => { e.preventDefault(); performLogout(); });
});
</script>
</body>
</html>
