{{-- resources/views/layouts/structure.blade.php (Unzip Examination - Multi Role: admin/examiner/student) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  @php
    // Detect role from URL: /admin/*, /examiner/*, /student/*
    $roleSeg = request()->segment(1);
    $role = in_array($roleSeg, ['admin','examiner','student']) ? $roleSeg : 'admin';

    $prefix = $role === 'admin' ? '/admin' : ($role === 'examiner' ? '/examiner' : '/student');

    $brandTitle = $role === 'admin'
      ? 'Unzip Examination Admin'
      : ($role === 'examiner' ? 'Unzip Examination Examiner' : 'Unzip Examination Student');

    $dashboardUrl      = '/dashboard';
    $notificationsUrl  = $prefix . '/notifications';

    // profile/settings differ for student in your current setup
    $profileUrl  = ($role === 'student') ? '/student/profile'  : '/profile';
    $settingsUrl = ($role === 'student') ? '/student/settings' : '/settings';

    $roleLabel = ucfirst($role);

    // ✅ Dynamic Sidebar API (change only if your endpoint differs)
    // Expected response shapes supported:
    // 1 { data: [...] } OR { menus: [...] } OR [...]
    // Each item: { label, href, icon, children:[...] }
    $apiSidebarMenu = url('/api/my/sidebar-menus');
  @endphp

  <title>@yield('title', $brandTitle)</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/favicons/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}">

  {{-- page-level styles from views --}}
  @stack('styles')
  @yield('styles')

  <style>
    /* ================= Unzip Examination Layout (namespaced; no overrides of main.css) ================= */
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
      text-decoration:none;
    }
    .w3-link i{opacity:.9; min-width:18px; text-align:center}
    .w3-link:hover{background:var(--w3-rail-hover); transform:translateX(2px)}
    .w3-link.active{background:var(--w3-rail-active); position:relative}
    .w3-link.active::before{
      content:""; position:absolute; left:-6px; top:8px; bottom:8px; width:3px; background:var(--accent-color); border-radius:4px;
    }

    /* Group / Submenu */
    .w3-group{display:grid; gap:4px; margin-top:2px}
    .w3-toggle{cursor:pointer}
    .w3-toggle .w3-chev{
      margin-left:auto; margin-right:2px; padding-left:6px;
      transition:transform .18s ease; opacity:.85;
    }
    .w3-toggle.w3-open .w3-chev{transform:rotate(180deg)}

    .w3-submenu{
      display:grid; gap:2px; margin-left:8px; padding-left:8px; border-left:1px dashed var(--w3-rail-border);
      max-height:0; overflow:hidden; transition:max-height .24s ease;
    }
    .w3-submenu.w3-open{max-height:600px}
    .w3-submenu .w3-link{padding:8px 10px 8px 34px; font-size:.86rem}

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

    .w3-icon-btn{
      width:36px; height:36px; display:inline-grid; place-items:center; border:1px solid var(--line-strong);
      background:#fff; color:var(--secondary-color); border-radius:999px; transition:transform .18s ease, background .18s ease;
    }
    .w3-icon-btn:hover{background:#f6f8fc; transform:translateY(-1px)}

    /* Hamburger (morph) */
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

    /* Dark flips */
    html.theme-dark .w3-sidebar{background:var(--surface); border-right-color:var(--line-strong)}
    html.theme-dark .w3-sidebar-head{border-bottom-color:var(--line-strong)}
    html.theme-dark .w3-link:hover{background:#0c172d}
    html.theme-dark .w3-link.active{background:rgba(0,143,122,.12)}
    html.theme-dark .w3-overlay{background:rgba(0,0,0,.55)}

    html.theme-dark .w3-appbar{background:var(--surface); border-bottom-color:var(--line-strong)}
    html.theme-dark .w3-icon-btn, html.theme-dark .w3-hamburger{background:var(--surface); border-color:var(--line-strong); color:var(--text-color)}
    html.theme-dark .w3-icon-btn:hover, html.theme-dark .w3-hamburger:hover{background:#0c172d}
    html.theme-dark .w3-bar{ background:#e8edf7; }

    html.theme-dark .dropdown-menu{ background:#0f172a; border-color:var(--line-strong); }
    html.theme-dark .dropdown-menu .dropdown-header{ color:var(--text-color); }
    html.theme-dark .dropdown-menu .dropdown-item{ color:var(--text-color); }
    html.theme-dark .dropdown-menu .dropdown-item:hover{ background:#13203a; color:var(--accent-color); }

    /* ===== RTE styles (safe for all roles) ===== */
    .toolbar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px}
    .tool{border:1px solid var(--line-strong);border-radius:10px;background:#fff;padding:6px 9px;cursor:pointer}
    .tool:hover{background:var(--page-hover)}
    .rte-wrap{position:relative}
    .rte{
      min-height:200px;max-height:600px;overflow:auto;
      border:1px solid var(--line-strong);border-radius:12px;background:#fff;padding:12px;line-height:1.6;outline:none
    }
    .rte:focus{box-shadow:var(--ring);border-color:var(--accent-color)}
    .rte-ph{position:absolute;top:12px;left:12px;color:#9aa3b2;pointer-events:none;font-size:var(--fs-14)}

    #rte, .rte, .notice-editor #rte {
      display:block !important;
      min-height:160px !important;
      max-height:600px !important;
      overflow:auto !important;
      padding:12px 14px !important;
      border-radius:12px !important;
      border:1px solid var(--line-strong,#d1d5db) !important;
      background:var(--surface,#ffffff) !important;
      color:var(--ink,#111827) !important;
      line-height:1.6 !important;
      font-size:15px !important;
      box-sizing:border-box !important;
      -webkit-font-smoothing:antialiased !important;
    }
    #rte:focus, .rte:focus {
      outline: none !important;
      box-shadow: 0 0 0 4px color-mix(in oklab, var(--accent-color,#059669) 12%, transparent) !important;
      border-color: var(--accent-color,#059669) !important;
    }
    #rte.has-content + .rte-ph,
    .rte.has-content + .rte-ph { display: none !important; }
    .rte-ph {
      position:absolute; top:12px; left:12px; pointer-events:none;
      color:var(--muted-color,#9aa3b2) !important; font-size:0.95rem;
    }
    #rte_toolbar, .rte-toolbar, .notice-editor #rte_toolbar {
      display:flex !important;
      gap:6px !important;
      flex-wrap:wrap !important;
      margin-bottom:8px !important;
      align-items:center !important;
    }
    #rte_toolbar [data-cmd], #rte_toolbar .tool {
      border:1px solid var(--line-strong,#e6e9ef) !important;
      background:var(--surface-2,#fff) !important;
      padding:6px 9px !important;
      border-radius:10px !important;
      cursor:pointer !important;
      font-size:14px !important;
      color:var(--ink,#111827) !important;
    }
    #rte_toolbar [data-cmd]:hover { background: var(--page-hover,#f3f4f6) !important; }
    #rte_toolbar [data-cmd].active,
    #rte_toolbar [data-cmd][aria-pressed="true"] {
      background: var(--accent-color,#059669) !important;
      color: #fff !important;
      border-color: transparent !important;
    }
    #rte p, #rte div { margin: 0 0 0.75rem !important; }

    html.theme-dark #rte, html.theme-dark .rte {
      background:#0f172a !important;
      color:#e5e7eb !important;
      border-color:var(--line-strong,#1f2937) !important;
    }
    html.theme-dark #rte_toolbar [data-cmd], html.theme-dark .tool {
      background:#0b1220 !important;
      color:#e5e7eb !important;
      border-color:var(--line-strong,#1f2937) !important;
    }
    #rte:empty:before, .rte:empty:before { content: "" !important; display:block; }
    #rte, .rte { user-select: text !important; -moz-user-select: text !important; -webkit-user-select: text !important; }
    html.theme-dark .rte{background:#0f172a;border-color:var(--line-strong);color:#e5e7eb}
  </style>

  <style>
    /* Dark mode scrollbars */
    html.theme-dark ::-webkit-scrollbar { width: 8px !important; }
    html.theme-dark ::-webkit-scrollbar-track { background: #1e293b !important; border-radius: 4px !important; }
    html.theme-dark ::-webkit-scrollbar-thumb { background: #475569 !important; border-radius: 4px !important; }
    html.theme-dark ::-webkit-scrollbar-thumb:hover { background: #64748b !important; }

    html.theme-dark .w3-sidebar-scroll::-webkit-scrollbar { width: 6px !important; }
    html.theme-dark .w3-sidebar-scroll::-webkit-scrollbar-track { background: #1e293b !important; }
    html.theme-dark .w3-sidebar-scroll::-webkit-scrollbar-thumb { background: #475569 !important; }
  </style>
</head>
<body data-role="{{ $role }}" data-sidebar-api="{{ $apiSidebarMenu }}">

<!-- Sidebar -->
<aside id="sidebar" class="w3-sidebar" aria-label="Sidebar">
  <div class="w3-sidebar-head">
    <a href="{{ $dashboardUrl }}" class="w3-brand">
      <img id="logo" src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </a>
  </div>

  <div class="w3-sidebar-scroll">
    {{-- ✅ Dynamic sidebar host (JS renders here) --}}
    <div id="w3MenuDynamic" class="d-none" aria-label="Dynamic Sidebar Menu"></div>

    {{-- ✅ Static fallback (keeps working if API fails) --}}
    <div id="w3MenuStatic">
      <!-- Overview -->
      <div class="w3-nav-section">
        <div class="w3-section-title"><i class="fa-solid fa-chart-line"></i> OVERVIEW</div>
        <div class="w3-section-rule"></div>
      </div>
      <nav class="w3-menu" aria-label="Overview">
        <a href="{{ $dashboardUrl }}" class="w3-link">
          <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
        </a>
      </nav>

      @if($role === 'admin')
        <!-- Exam Management -->
        <div class="w3-nav-section">
          <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> EXAM MANAGEMENT</div>
          <div class="w3-section-rule"></div>
        </div>
        <nav class="w3-menu" aria-label="Exam Management">
          {{-- Users --}}
          <div class="w3-group">
            <a href="#" class="w3-link w3-toggle" data-target="sm-users" aria-expanded="false">
              <i class="fa-solid fa-users"></i><span>Users</span>
              <i class="fa fa-chevron-down w3-chev"></i>
            </a>
            <div id="sm-users" class="w3-submenu" role="group" aria-label="Users submenu">
              <a href="/users/manage" class="w3-link">Manage Users</a>
            </div>
          </div>

          {{-- Quizzes / Exams --}}
          <div class="w3-group">
            <a href="#" class="w3-link w3-toggle" data-target="sm-quizzes" aria-expanded="false">
              <i class="fa-solid fa-clipboard-list"></i><span>Quizzes & Exams</span>
              <i class="fa fa-chevron-down w3-chev"></i>
            </a>
            <div id="sm-quizzes" class="w3-submenu" role="group" aria-label="Quizzes submenu">
              <a href="/quizz/create" class="w3-link">Create Quiz</a>
              <a href="/quizz/manage" class="w3-link">Manage Quizzes</a>
              <a href="/quizz/results" class="w3-link">Results</a>
            </div>
          </div>
        </nav>

        <!-- Admin Tools -->
        <div class="w3-nav-section">
          <div class="w3-section-title"><i class="fa-solid fa-screwdriver-wrench"></i> ADMIN TOOLS</div>
          <div class="w3-section-rule"></div>
        </div>
        <nav class="w3-menu" aria-label="Admin Tools">
          <div class="w3-group">
            <a href="#" class="w3-link w3-toggle" data-target="sm-dashmenu" aria-expanded="false">
              <i class="fa-solid fa-sitemap"></i><span>Dashboard Menus</span>
              <i class="fa fa-chevron-down w3-chev"></i>
            </a>
            <div id="sm-dashmenu" class="w3-submenu" role="group" aria-label="Dashboard Menus submenu">
              <a href="/dashboard-menu/manage" class="w3-link">Manage</a>
              <a href="/dashboard-menu/create" class="w3-link">Create</a>
            </div>
          </div>

          <div class="w3-group">
            <a href="#" class="w3-link w3-toggle" data-target="sm-priv" aria-expanded="false">
              <i class="fa-solid fa-user-shield"></i><span>Page Privileges</span>
              <i class="fa fa-chevron-down w3-chev"></i>
            </a>
            <div id="sm-priv" class="w3-submenu" role="group" aria-label="Page Privileges submenu">
              <a href="/page-privilege/manage" class="w3-link">Manage</a>
              <a href="/page-privilege/create" class="w3-link">Create</a>
            </div>
          </div>
        </nav>

      @elseif($role === 'examiner')
        <!-- Exam Management -->
        <div class="w3-nav-section">
          <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> EXAM MANAGEMENT</div>
          <div class="w3-section-rule"></div>
        </div>
        <nav class="w3-menu" aria-label="Exam Management">
          {{-- Quizzes --}}
          <div class="w3-group">
            <a href="#" class="w3-link w3-toggle" data-target="sm-quizzes" aria-expanded="false">
              <i class="fa-solid fa-clipboard-list"></i><span>Quizzes</span>
              <i class="fa fa-chevron-down w3-chev"></i>
            </a>
            <div id="sm-quizzes" class="w3-submenu" role="group" aria-label="Quizzes submenu">
              <a href="/quizz/create" class="w3-link">Create Quiz</a>
              <a href="/quizz/manage" class="w3-link">Manage Quizzes</a>
              <a href="/quizz/result/manage" class="w3-link">Results</a>
            </div>
          </div>
        </nav>

      @else
        <!-- My Learning -->
        <div class="w3-nav-section">
          <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> MY LEARNING</div>
          <div class="w3-section-rule"></div>
        </div>
        <nav class="w3-menu" aria-label="My Learning">
          <a href="/quizzes" class="w3-link">
            <i class="fa-solid fa-book-open"></i><span>My Quizzes</span>
          </a>
        </nav>
      @endif

      <!-- Account (visible only on small screens) -->
      <div class="w3-nav-section d-lg-none" style="display:none">
        <div class="w3-section-title"><i class="fa-solid fa-user"></i> ACCOUNT</div>
        <div class="w3-section-rule"></div>
      </div>
      <nav class="w3-menu d-lg-none" aria-label="Account">
        <a href="{{ $profileUrl }}" class="w3-link"><i class="fa fa-id-badge"></i><span>Profile</span></a>
        <a href="{{ $settingsUrl }}" class="w3-link"><i class="fa fa-gear"></i><span>Settings</span></a>
      </nav>
    </div>
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
    <a href="{{ $dashboardUrl }}" class="w3-app-logo d-lg-none">
      <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
      @if($role === 'student')
        <span>Student</span>
      @endif
    </a>

    <strong class="ms-1 d-none d-lg-inline" style="font-family:var(--font-head);color:var(--ink)">
      @yield('title', $brandTitle)
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
            <a class="text-muted" href="{{ $notificationsUrl }}">View all</a>
          </div>
          <div class="w3-note rounded-xs">
            <div class="small">
              @if($role === 'admin')
                <strong>Exam schedule update</strong> — One of today’s exams has been rescheduled.
              @elseif($role === 'examiner')
                <strong>Exam update</strong> — One of your assigned exams has been updated.
              @else
                No new notifications.
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- User (desktop only) -->
      <div class="dropdown d-none d-lg-block">
        <a href="#" class="btn btn-primary rounded-pill d-flex align-items-center gap-2 px-3" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fa-regular fa-user"></i>
          <span id="userRoleLabel" class="d-none d-xl-inline">{{ $roleLabel }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li class="dropdown-header">Account</li>
          <li><a class="dropdown-item" href="{{ $profileUrl }}"><i class="fa fa-id-badge me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="{{ $settingsUrl }}"><i class="fa fa-gear me-2"></i>Settings</a></li>
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

  // ===== Submenus (event delegation => works for dynamic render too)
  document.addEventListener('click', (e) => {
    const tg = e.target.closest('.w3-toggle');
    if (!tg) return;

    const id = tg.dataset.target;
    if (!id) return;

    e.preventDefault();

    const el = document.getElementById(id);
    if (!el) return;

    const open = el.classList.toggle('w3-open');
    tg.classList.toggle('w3-open', open);
    tg.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // ===== Active link + open parent
  function markActiveLinks(scope){
    const root = scope || document;
    const path = window.location.pathname.replace(/\/+$/, '');

    root.querySelectorAll('.w3-menu a[href], .w3-submenu a[href]').forEach(a => {
      const href = a.getAttribute('href');
      if (!href || href === '#') return;

      // compare pathname only
      let aPath = href;
      try{
        const u = new URL(href, window.location.origin);
        aPath = u.pathname;
      }catch(e){}
      aPath = (aPath || '').replace(/\/+$/, '');

      if (aPath === path){
        a.classList.add('active');
        const sub = a.closest('.w3-submenu');
        if (sub){
          sub.classList.add('w3-open');
          const toggle = sub.previousElementSibling;
          toggle?.classList.add('w3-open');
          toggle?.setAttribute('aria-expanded','true');
        }
      }
    });
  }

  // ===== Role label (storage wins; fallback is server role)
  const roleLabelEl = document.getElementById('userRoleLabel');
  function titleizeRole(r){
    if (!r) return roleLabelEl?.textContent || 'User';
    return r.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  }
  const roleFromStorage = sessionStorage.getItem('role') || localStorage.getItem('role');
  if (roleLabelEl) roleLabelEl.textContent = titleizeRole(roleFromStorage);

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

  // ===========================
  // ✅ Dynamic Sidebar Rendering
  // ===========================
  const menuDynamic = document.getElementById('w3MenuDynamic');
  const menuStatic  = document.getElementById('w3MenuStatic');

  function esc(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function normalizeIcon(icon){
    const raw = String(icon || '').trim();
    if (!raw) return 'fa-regular fa-circle';
    // If they store "fa-users" etc
    if (raw.startsWith('fa-')) return 'fa-solid ' + raw;
    // If they store full class string, use as-is
    if (raw.includes('fa-')) return raw;
    return 'fa-solid ' + raw;
  }

  function renderSection(title, icon, items){
    const id = 'sec_' + Math.random().toString(16).slice(2);
    let out = '';
    out += `
      <div class="w3-nav-section">
        <div class="w3-section-title"><i class="${esc(normalizeIcon(icon))}"></i> ${esc(title)}</div>
        <div class="w3-section-rule"></div>
      </div>
      <nav class="w3-menu" aria-label="${esc(title)}" data-sec="${id}">
    `;

    for (const it of (items || [])){
      out += renderItem(it);
    }

    out += `</nav>`;
    return out;
  }

  function renderItem(it){
    const label = it?.label ?? it?.title ?? 'Untitled';
    const href  = it?.href ?? it?.url ?? '#';
    const icon  = normalizeIcon(it?.icon || it?.fa || it?.icon_class);
    const kids  = Array.isArray(it?.children) ? it.children : (Array.isArray(it?.items) ? it.items : []);

    // submenu
    if (kids.length){
      const smId = 'sm_' + Math.random().toString(16).slice(2);
      let out = '';
      out += `
        <div class="w3-group">
          <a href="#" class="w3-link w3-toggle" data-target="${esc(smId)}" aria-expanded="false">
            <i class="${esc(icon)}"></i><span>${esc(label)}</span>
            <i class="fa fa-chevron-down w3-chev"></i>
          </a>
          <div id="${esc(smId)}" class="w3-submenu" role="group" aria-label="${esc(label)} submenu">
      `;
      for (const k of kids){
        const kLabel = k?.label ?? k?.title ?? 'Item';
        const kHref  = k?.href ?? k?.url ?? '#';
        out += `<a href="${esc(kHref)}" class="w3-link">${esc(kLabel)}</a>`;
      }
      out += `
          </div>
        </div>
      `;
      return out;
    }

    // normal link
    return `
      <a href="${esc(href)}" class="w3-link">
        <i class="${esc(icon)}"></i><span>${esc(label)}</span>
      </a>
    `;
  }

  function buildDynamicMenu(menus){
    // If API gives "flat menu", we show one section
    // If API gives "sections", support: [{section:'Overview', icon:'...', items:[...]}]
    const role = (document.body.dataset.role || 'admin').toLowerCase();

    let html = '';

    const hasSections = Array.isArray(menus) && menus.some(x => x && (x.section || x.title) && Array.isArray(x.items));
    if (hasSections){
      for (const sec of menus){
        html += renderSection(sec.section || sec.title || 'Menu', sec.icon || 'fa-layer-group', sec.items || []);
      }
    }else{
      html += renderSection('Overview', 'fa-chart-line', [
        { label:'Dashboard', href:'{{ $dashboardUrl }}', icon:'fa-gauge-high' },
      ]);

      html += renderSection('Menu', 'fa-bars', menus || []);
    }

    // Always add admin tools for admin role (unless your API already includes them)
    if (role === 'admin'){
      html += renderSection('Admin Tools', 'fa-screwdriver-wrench', [
        {
          label:'Dashboard Menus',
          icon:'fa-sitemap',
          children:[
            { label:'Manage', href:'/dashboard-menu/manage' },
            { label:'Create', href:'/dashboard-menu/create' },
          ]
        },
        {
          label:'Page Privileges',
          icon:'fa-user-shield',
          children:[
            { label:'Manage', href:'/page-privilege/manage' },
            { label:'Create', href:'/page-privilege/create' },
          ]
        },
      ]);
    }

    // Small-screen account block
    html += `
      <div class="w3-nav-section d-lg-none">
        <div class="w3-section-title"><i class="fa-solid fa-user"></i> ACCOUNT</div>
        <div class="w3-section-rule"></div>
      </div>
      <nav class="w3-menu d-lg-none" aria-label="Account">
        <a href="{{ $profileUrl }}" class="w3-link"><i class="fa fa-id-badge"></i><span>Profile</span></a>
        <a href="{{ $settingsUrl }}" class="w3-link"><i class="fa fa-gear"></i><span>Settings</span></a>
      </nav>
    `;

    return html;
  }

  async function loadSidebarMenu(){
    const api = document.body.dataset.sidebarApi || '';
    if (!api) { markActiveLinks(); return; }

    const role = (document.body.dataset.role || 'admin').toLowerCase();
    const token = getBearerToken();

    try{
      const url = api.includes('?') ? `${api}&role=${encodeURIComponent(role)}` : `${api}?role=${encodeURIComponent(role)}`;

      const res = await fetch(url, {
        headers: {
          'Accept':'application/json',
          ...(token ? { 'Authorization':'Bearer ' + token } : {})
        }
      });

      if (!res.ok) throw new Error('sidebar api failed');

      const json = await res.json();
      const menus = json?.data || json?.menus || json;

      if (!Array.isArray(menus)) throw new Error('sidebar api invalid shape');

      // render + swap
      menuDynamic.innerHTML = buildDynamicMenu(menus);
      menuDynamic.classList.remove('d-none');
      if (menuStatic) menuStatic.classList.add('d-none');

      // mark active on new DOM
      markActiveLinks(menuDynamic);
    }catch(err){
      // keep static
      menuDynamic.classList.add('d-none');
      if (menuStatic) menuStatic.classList.remove('d-none');
      markActiveLinks(menuStatic || document);
    }
  }

  // init dynamic sidebar
  loadSidebarMenu();
});
</script>
</body>
</html>
