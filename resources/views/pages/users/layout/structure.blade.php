{{-- resources/views/layouts/structure.blade.php (Unzip Examination - Multi Role: admin/examiner/student) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>

  @php
    // Fallback role from URL segment (client-side will override using /api/auth/me-role)
    $roleSeg = request()->segment(1);
    $role = in_array($roleSeg, ['admin','examiner','student']) ? $roleSeg : 'admin';

    $prefix = $role === 'admin' ? '/admin' : ($role === 'examiner' ? '/examiner' : '/student');

    $brandTitle = $role === 'admin'
      ? 'Unzip Examination Admin'
      : ($role === 'examiner' ? 'Unzip Examination Examiner' : 'Unzip Examination Student');

    $dashboardUrl      = '/dashboard';
    $notificationsUrl  = $prefix . '/notifications';

    // fallback (JS will override these links based on detected role)
    $profileUrl  = ($role === 'student') ? '/profile'  : '/profile';
    $settingsUrl = ($role === 'student') ? '/student/settings' : '/settings';

    $roleLabel = ucfirst($role);
  @endphp

  <title>@yield('title', $brandTitle)</title>

  <meta name="csrf-token" content="{{ csrf_token() }}"/>

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/favicons/favicon.png') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}">

  @stack('styles')
  @yield('styles')

  <style>
    /* ================= Unzip Layout (namespaced; no overrides of main.css) ================= */
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

    /* ================= BOOT LOADING OVERLAY (METHOD) ================= */
    #w3BootOverlay{
      position:fixed; inset:0; z-index:2000;
      background:color-mix(in oklab, var(--bg-body,#f6f7fb) 92%, #000 8%);
      display:flex; align-items:center; justify-content:center;
      transition:opacity .2s ease, visibility .2s ease;
    }
    html.theme-dark #w3BootOverlay{ background:rgba(2,6,23,.92); }
    #w3BootOverlay.w3-hide{ opacity:0; visibility:hidden; pointer-events:none; }

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
      height:56px; width:auto; max-width:180px; display:block;
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

    /* Overlay (mobile sidebar) */
    .w3-overlay{
      position:fixed; top:0; bottom:0; right:0; left:var(--w3-rail-w);
      background:rgba(0,0,0,.45); z-index:1040; opacity:0; visibility:hidden; pointer-events:none;
      transition:opacity .2s ease, visibility .2s ease;
    }
    .w3-overlay.w3-on{opacity:1; visibility:visible; pointer-events:auto}

    /* Mobile */
    @media (max-width: 991px){
      .w3-sidebar{transform:translateX(-100%)}
      .w3-sidebar.w3-on{transform:translateX(0)}
      .w3-content{ padding-left:16px; }
      .w3-appbar-inner{padding-inline:10px}
      .js-theme-btn{display:none!important}
      .w3-overlay{left:var(--w3-rail-w)}
    }

    /* Dark flips */
    html.theme-dark .w3-sidebar{background:var(--surface); border-right-color:var(--line-strong)}
    html.theme-dark .w3-link:hover{background:#0c172d}
    html.theme-dark .w3-link.active{background:rgba(0,143,122,.12)}
    html.theme-dark .w3-overlay{background:rgba(0,0,0,.55)}
    html.theme-dark .w3-appbar{background:var(--surface); border-bottom-color:var(--line-strong)}
    html.theme-dark .w3-icon-btn, html.theme-dark .w3-hamburger{background:var(--surface); border-color:var(--line-strong); color:var(--text-color)}
    html.theme-dark .w3-icon-btn:hover, html.theme-dark .w3-hamburger:hover{background:#0c172d}
    html.theme-dark .w3-bar{ background:#e8edf7; }

    /* ✅ No-access message */
    html.theme-dark #noAcademicAccess .alert{
      background:#0b1220;
      border-color:var(--line-strong,#1f2937);
      color:#e5e7eb;
    }
    html.theme-dark #noAcademicAccess .alert a{color:#e5e7eb}
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

<body data-role="{{ $role }}">

<!-- ✅ BOOT LOADING OVERLAY (METHOD) -->
<div id="w3BootOverlay" aria-live="polite" aria-busy="true">
  @include('partials.overlay')
</div>

<!-- Sidebar -->
<aside id="sidebar" class="w3-sidebar" aria-label="Sidebar">
  <div class="w3-sidebar-head">
    <a href="{{ $dashboardUrl }}" class="w3-brand">
      <img id="logo" src="{{ asset('/assets/media/images/web/logo.png') }}" alt="Unzip Examination">
    </a>
  </div>

  <div class="w3-sidebar-scroll">

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

    <!-- MODULES -->
    <div class="w3-nav-section">
      <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> USERS</div>
      <div class="w3-section-rule"></div>
    </div>

    {{-- 1 ALL MENU (default population; shown only if API returns "all") --}}
    <div id="allMenuWrap" style="display:none">
      {{-- ADMIN --}}
<div id="allMenuAdmin" style="display:none">
  <nav class="w3-menu" aria-label="Admin Modules">

    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-admin-users" aria-expanded="false">
        <i class="fa-solid fa-users"></i><span>Users</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-admin-users" class="w3-submenu" role="group" aria-label="Users submenu">
        <a href="/users/manage" class="w3-link">Manage Users</a>
      </div>
    </div>

    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-admin-user-folders" aria-expanded="false">
        <i class="fa-solid fa-folder"></i><span>User Folders</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-admin-user-folders" class="w3-submenu" role="group" aria-label="User Folders submenu">
        <a href="/user-folders/create" class="w3-link">Create Folder</a>
        <a href="/user-folders/manage" class="w3-link">Manage Folders</a>
      </div>
    </div>
    {{-- ✅ Registration Campaigns --}}
<div class="w3-nav-section">
<div class="w3-section-title">
<i class="fa-solid fa-bullhorn"></i> REGISTRATION CAMPAIGNS
</div>
<div class="w3-section-rule"></div>
</div>
 
<div class="w3-group">
<a href="#" class="w3-link w3-toggle" data-target="sm-admin-reg-campaign" aria-expanded="false">
<i class="fa-solid fa-rectangle-ad"></i><span>Registration Campaign</span>
<i class="fa fa-chevron-down w3-chev"></i>
</a>
 
  <div id="sm-admin-reg-campaign" class="w3-submenu" role="group" aria-label="Registration Campaign submenu">
<a href="/registration-campaign/create" class="w3-link">
<span>Create Campaign</span>
</a>
 
    <a href="/interview-registration-campaigns/manage" class="w3-link">
<span>Manage Campaigns</span>
</a>
</div>
</div>

 
<div class="w3-nav-section">
      <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> Quizzes & Exam</div>
      <div class="w3-section-rule"></div>
    </div>

    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-admin-quizzes" aria-expanded="false">
        <i class="fa-solid fa-clipboard-list"></i><span>Quizzes & Exams</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-admin-quizzes" class="w3-submenu" role="group" aria-label="Quizzes submenu">
        <a href="/quizz/create" class="w3-link">Create Quiz</a>
        <a href="/quizz/manage" class="w3-link">Manage Quizzes</a>
        <a href="/quizz/results" class="w3-link">Results</a>
      </div>
    </div>
  <div class="w3-nav-section">
      <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> GAMES & RESULTS </div>
      <div class="w3-section-rule"></div>
    </div>

    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-admin-bubble-games" aria-expanded="false">
        <i class="fa-solid fa-gamepad"></i><span>Bubble Games</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-admin-bubble-games" class="w3-submenu" role="group" aria-label="Bubble Games submenu">
        <a href="/bubble-games/create" class="w3-link">Create Game</a>
        <a href="/bubble-games/manage" class="w3-link">Manage Games</a>
        <a href="/graphical-test/results" class="w3-link">Results</a>
      </div>
    </div>

    <!-- ✅ NEW: Door Games -->
    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-admin-door-games" aria-expanded="false">
        <i class="fa-solid fa-door-open"></i><span>Door Games</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-admin-door-games" class="w3-submenu" role="group" aria-label="Door Games submenu">
        <a href="/door-games/create" class="w3-link">Create Game</a>
        <a href="/door-games/manage" class="w3-link">Manage Games</a>
        <a href="/decision-making-test/results" class="w3-link">Results</a>
      </div>
    </div>
    
    <!-- ✅ END NEW -->
    <div class="w3-nav-section">
      <div class="w3-section-title"><i class="fa-solid fa-graduation-cap"></i> MENU PRIVILEGES</div>
      <div class="w3-section-rule"></div>
    </div>

    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-admin-dashboard-menu" aria-expanded="false">
        <i class="fa-solid fa-puzzle-piece"></i><span>Dashboard Menu</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-admin-dashboard-menu" class="w3-submenu" role="group" aria-label="Dashboard Menu submenu">
        <a href="/dashboard-menu/create" class="w3-link">Create Menu</a>
        <a href="/dashboard-menu/manage" class="w3-link">Manage Menu</a>
      </div>
    </div>

    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-admin-page-privilege" aria-expanded="false">
        <i class="fa-solid fa-shield-halved"></i><span>Page Privilege</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-admin-page-privilege" class="w3-submenu" role="group" aria-label="Page Privilege submenu">
        <a href="/page-privilege/create" class="w3-link">Create Privilege</a>
        <a href="/page-privilege/manage" class="w3-link">Manage Privilege</a>
      </div>
    </div>

  </nav>
</div>
{{-- EXAMINER --}}
<div id="allMenuExaminer" style="display:none">
  <nav class="w3-menu" aria-label="Examiner Modules">

    <div class="w3-group">
      <a href="#" class="w3-link w3-toggle" data-target="sm-examiner-quizzes" aria-expanded="false">
        <i class="fa-solid fa-clipboard-list"></i><span>Quizzes</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>
      <div id="sm-examiner-quizzes" class="w3-submenu" role="group" aria-label="Quizzes submenu">
        <a href="/quizz/create" class="w3-link">Create Quiz</a>
        <a href="/quizz/manage" class="w3-link">Manage Quizzes</a>
        <a href="/quizz/result/manage" class="w3-link">Results</a>
      </div>
    </div>

    <div class="w3-group">
      <a href="javascript:void(0)"
         class="w3-link w3-toggle"
         data-target="sm-examiner-bubble-games"
         aria-expanded="false"
         aria-controls="sm-examiner-bubble-games">
        <i class="fa-solid fa-gamepad"></i><span>Bubble Games</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>

      <div id="sm-examiner-bubble-games" class="w3-submenu" role="group" aria-label="Bubble Games submenu">
        <a href="/bubble-games/create" class="w3-link">Create Game</a>
        <a href="/bubble-games/manage" class="w3-link">Manage Games</a>
        <a href="/test/result/manage" class="w3-link">Results</a>
      </div>
    </div>

    <!-- ✅ NEW: Door Games -->
    <div class="w3-group">
      <a href="javascript:void(0)"
         class="w3-link w3-toggle"
         data-target="sm-examiner-door-games"
         aria-expanded="false"
         aria-controls="sm-examiner-door-games">
        <i class="fa-solid fa-door-open"></i><span>Door Games</span>
        <i class="fa fa-chevron-down w3-chev"></i>
      </a>

      <div id="sm-examiner-door-games" class="w3-submenu" role="group" aria-label="Door Games submenu">
        <a href="/door-games/create" class="w3-link">Create Game</a>
        <a href="/door-games/manage" class="w3-link">Manage Games</a>
        <a href="/decision-making-test/result/manage" class="w3-link">Results</a>
      </div>
    </div>
    <!-- ✅ END NEW -->

  </nav>
</div>

      {{-- STUDENT --}}
      <div id="allMenuStudent" style="display:none">
        <nav class="w3-menu" aria-label="Student Modules">
          <a href="/quizzes" class="w3-link">
            <i class="fa-solid fa-book-open"></i><span>My Quizzes</span>
          </a>
           <a href="/my/result" class="w3-link">
           <i class="fa-duotone fa-solid fa-square-poll-horizontal"></i><span>My Results</span>
          </a>
        </nav>
      </div>

      {{-- Account (visible only on small screens) --}}
      <div class="w3-nav-section d-lg-none">
        <div class="w3-section-title"><i class="fa-solid fa-user"></i> ACCOUNT</div>
        <div class="w3-section-rule"></div>
      </div>
      <nav class="w3-menu d-lg-none" aria-label="Account">
        <a data-link="profile" href="{{ $profileUrl }}" class="w3-link"><i class="fa fa-id-badge"></i><span>Profile</span></a>
        <a data-link="settings" href="{{ $settingsUrl }}" class="w3-link"><i class="fa fa-gear"></i><span>Settings</span></a>
      </nav>
    </div>

    {{-- 2) DYNAMIC MENU (shown only if API returns tree[]) --}}
    <div id="dynamicMenuWrap" style="display:none">
      <nav id="dynamicMenu" class="w3-menu" aria-label="Dynamic Menu"></nav>
    </div>

    {{-- 3) NO ACCESS MESSAGE (shown only if tree is empty) --}}
    <div id="noAcademicAccess d-none" style="display:none;">
      <div class="px-2 pt-2">
        <div class="alert alert-warning small mb-0">
          <i class="fa-solid fa-lock me-2"></i>
          To have access to any modules. Ask admin/instructor to grant access.
        </div>
      </div>
    </div>

  </div>

  <div class="w3-sidebar-foot">
    <a data-link="profile" href="{{ $profileUrl }}" class="w3-link"><i class="fa-regular fa-circle-user"></i><span>Profile</span></a>
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
              <strong>Update</strong> — Check the latest notifications.
            </div>
          </div>
        </div>
      </div>

      <!-- User menu (desktop) -->
      <div class="dropdown d-none d-lg-block">
        <a href="#" class="btn btn-primary rounded-pill d-flex align-items-center gap-2 px-3"
           id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fa-regular fa-user"></i>
          <span id="userRoleLabel" class="d-none d-xl-inline">{{ $roleLabel }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li class="dropdown-header">Account</li>
          <li><a class="dropdown-item" data-link="profile" href="{{ $profileUrl }}"><i class="fa fa-id-badge me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" data-link="settings" href="{{ $settingsUrl }}"><i class="fa fa-gear me-2"></i>Settings</a></li>
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

  // ✅ overlay helpers (METHOD)
  const bootOverlay = document.getElementById('w3BootOverlay');
  const showBoot = () => { try{ bootOverlay?.classList.remove('w3-hide'); }catch(e){} };
  const hideBoot = () => { try{ bootOverlay?.classList.add('w3-hide'); }catch(e){} };
  showBoot();

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

  // ===== Submenus (bind once)
  function bindSubmenuToggles(root=document){
    root.querySelectorAll('.w3-toggle').forEach(tg => {
      if (tg.__bound) return;
      tg.__bound = true;

      tg.addEventListener('click', (e) => {
        e.preventDefault();
        const id = tg.dataset.target;
        const el = document.getElementById(id);
        if (!el) return;
        const open = el.classList.toggle('w3-open');
        tg.classList.toggle('w3-open', open);
        tg.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });
  }

  // ===== Active link + open parent
  function markActiveLinks(){
    const path = window.location.pathname.replace(/\/+$/, '');
    document.querySelectorAll('.w3-menu a[href]').forEach(a => {
      const href = a.getAttribute('href');
      if (href && href !== '#'){
        let aPath = href;
        try{ aPath = new URL(href, location.origin).pathname; }catch(e){}
        if ((aPath || '').replace(/\/+$/, '') === path){
          a.classList.add('active');
          const sub = a.closest('.w3-submenu');
          if (sub){
            sub.classList.add('w3-open');
            const toggle = sub.previousElementSibling;
            toggle?.classList.add('w3-open');
            toggle?.setAttribute('aria-expanded','true');
          }
        }
      }
    });
  }

  // ===== Auth helpers
  function getBearerToken(){
    return sessionStorage.getItem('token') || localStorage.getItem('token') || null;
  }

  // ✅ Get role from API (YOUR METHOD)
  const getMyRole = async (token) => {
    if (!token) return '';
    try {
      const res = await fetch('/api/auth/me-role', {
        method: 'GET',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json'
        }
      });

      if (!res.ok) return '';

      const data = await res.json();
      if (data?.status === 'success' && data?.role) {
        return String(data.role).trim().toLowerCase();
      }
      return '';
    } catch (e) {
      console.error('[DASH] Error fetching role:', e);
      return '';
    }
  };

  // ===== Role label + link patching
  const roleLabelEl = document.getElementById('userRoleLabel');
  function titleizeRole(r){
    if (!r) return 'Admin';
    return r.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  function applyRoleLinks(role){
    const profileHref  = role === 'student' ? '/profile'  : '/profile';
    const settingsHref = role === 'student' ? '/student/settings' : '/settings';

    document.querySelectorAll('[data-link="profile"]').forEach(a => a.setAttribute('href', profileHref));
    document.querySelectorAll('[data-link="settings"]').forEach(a => a.setAttribute('href', settingsHref));
  }

  function showRoleDefaultMenu(role){
    const adminBox = document.getElementById('allMenuAdmin');
    const exBox    = document.getElementById('allMenuExaminer');
    const stBox    = document.getElementById('allMenuStudent');

    [adminBox, exBox, stBox].forEach(el => { if (el) el.style.display = 'none'; });

    if (role === 'examiner') exBox && (exBox.style.display = '');
    else if (role === 'student') stBox && (stBox.style.display = '');
    else adminBox && (adminBox.style.display = '');
  }

  async function detectAndPersistRole(){
    const token = getBearerToken();
    const serverRole = (document.body?.dataset?.role || '').toLowerCase();
    let role = (sessionStorage.getItem('role') || localStorage.getItem('role') || serverRole || 'admin').toLowerCase();

    if (token){
      const apiRole = await getMyRole(token);
      if (apiRole){
        role = apiRole;
        try{ sessionStorage.setItem('role', role); }catch(e){}
        try{ localStorage.setItem('role', role); }catch(e){}
      }
    }

    document.body.dataset.role = role || 'admin';
    if (roleLabelEl) roleLabelEl.textContent = titleizeRole(role);
    applyRoleLinks(role);

    return role;
  }

  // ===== Sidebar API logic (METHOD)
  const allMenuWrap = document.getElementById('allMenuWrap');
  const dynamicMenuWrap = document.getElementById('dynamicMenuWrap');
  const dynamicMenu = document.getElementById('dynamicMenu');
  const noAcademicAccess = document.getElementById('noAcademicAccess');

  function safeText(v){ return (v ?? '').toString(); }
  function iconHtml(iconClass, fallback='fa-solid fa-circle'){
    const cls = safeText(iconClass).trim();
    return `<i class="${cls || fallback}"></i>`;
  }

  function renderDynamicTree(tree){
    if (!dynamicMenu) return;
    dynamicMenu.innerHTML = '';

    (tree || []).forEach((header, hi) => {
      const hid = parseInt(header?.id || 0, 10);
      if (!hid) return;

      const headerName = safeText(header?.name || 'Menu');
      const headerIcon = header?.icon_class || 'fa-solid fa-folder';
      const subId = `dyn-sub-${hid}-${hi}`;

      const wrap = document.createElement('div');
      wrap.className = 'w3-group';

      wrap.innerHTML = `
        <a href="#" class="w3-link w3-toggle" data-target="${subId}" aria-expanded="false">
          ${iconHtml(headerIcon, 'fa-solid fa-folder')}<span>${headerName}</span>
          <i class="fa fa-chevron-down w3-chev"></i>
        </a>
        <div id="${subId}" class="w3-submenu" role="group" aria-label="${headerName} submenu"></div>
      `;

      const sub = wrap.querySelector('#' + subId);
      const pages = Array.isArray(header?.children) ? header.children : [];

      pages.forEach((p) => {
        const href = safeText(p?.href || '#');
        const name = safeText(p?.name || 'Page');
        const pIcon = safeText(p?.icon_class || '');

        const a = document.createElement('a');
        a.className = 'w3-link';
        a.href = href === '' ? '#' : href;
        a.innerHTML = pIcon ? `${iconHtml(pIcon)}<span>${name}</span>` : `<span>${name}</span>`;
        sub.appendChild(a);
      });

      if (sub.children.length) dynamicMenu.appendChild(wrap);
    });

    bindSubmenuToggles(dynamicMenu);
  }

  async function loadSidebarFromNewApi(detectedRole){
  const token = getBearerToken();

  const role = String(detectedRole || document.body.dataset.role || 'admin')
    .trim().toLowerCase();

  const showDefaultMenu = (showNoAccessMessage=false) => {
    allMenuWrap && (allMenuWrap.style.display = '');
    dynamicMenuWrap && (dynamicMenuWrap.style.display = 'none');

    // ✅ show correct default menu for role
    showRoleDefaultMenu(role);

    // ✅ bind submenu toggles for default menu
    bindSubmenuToggles(allMenuWrap || document);

    // ✅ optional message
    noAcademicAccess && (noAcademicAccess.style.display = showNoAccessMessage ? '' : 'none');
  };

  // reset
  noAcademicAccess && (noAcademicAccess.style.display = 'none');

  if (!token){
    allMenuWrap && (allMenuWrap.style.display = 'none');
    dynamicMenuWrap && (dynamicMenuWrap.style.display = 'none');
    noAcademicAccess && (noAcademicAccess.style.display = 'none');
    return;
  }

  try{
    const res = await fetch('/api/my/sidebar-menus', {
      method: 'GET',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
      }
    });

    // ✅ If API fails, still show default menus (no warning message)
    if (!res.ok){
      showDefaultMenu(false);
      return;
    }

    const data = await res.json();

    // ✅ "all" => show default sidebar (role-based)
    if (data === 'all' || data?.tree === 'all') {
      showDefaultMenu(false);
      return;
    }

    const tree = Array.isArray(data?.tree) ? data.tree : (Array.isArray(data) ? data : []);

    // ✅ Has dynamic routes => show dynamic menu
    if (tree.length) {
      allMenuWrap && (allMenuWrap.style.display = 'none');
      dynamicMenuWrap && (dynamicMenuWrap.style.display = '');
      noAcademicAccess && (noAcademicAccess.style.display = 'none');
      renderDynamicTree(tree);
      return;
    }

    // ✅ EMPTY TREE => show default menu + show no-access message
    showDefaultMenu(true);

  } catch (e) {
    // ✅ On unexpected error, still show default menus
    showDefaultMenu(false);
  }
}


  // ===== Logout (uses /api/auth/logout)
  const API_LOGOUT = '/api/auth/logout';
  const LOGIN_PAGE = '/';

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

  // ===== INIT (METHOD: overlay stays until ready)
  (async () => {
    try{
      bindSubmenuToggles(document);

      // ✅ detect role via API first
      const detectedRole = await detectAndPersistRole();

      // ✅ then load sidebar menus (all/tree/empty)
      await loadSidebarFromNewApi(detectedRole);

      // ✅ then mark active
      markActiveLinks();
    } finally {
      hideBoot();
    }
  })();
});
</script>

<script>
/* =========================================================
   GLOBAL: Portal dropdown menus out of overflow containers
   ========================================================= */
(function(){
  let active = null;

  function cleanup(){
    if (!active) return;

    window.removeEventListener('resize', active.onEnv);
    document.removeEventListener('scroll', active.onEnv, true);

    const { menu, parent } = active;
    if (menu && parent && parent.isConnected) {
      menu.classList.remove('dd-portal');
      menu.style.cssText = '';
      parent.appendChild(menu);
    }
    active = null;
  }

  function positionMenu(toggleEl, menuEl){
    const rect = toggleEl.getBoundingClientRect();
    if (!rect || (rect.width === 0 && rect.height === 0)) return;

    menuEl.style.visibility = 'hidden';
    menuEl.style.display = 'block';

    const mw = menuEl.offsetWidth;
    const mh = menuEl.offsetHeight;

    const vw = document.documentElement.clientWidth;
    const vh = document.documentElement.clientHeight;

    let left = rect.left;
    if (left + mw > vw - 8) left = Math.max(8, rect.right - mw);
    if (left < 8) left = 8;

    let top = rect.bottom + 6;
    if (top + mh > vh - 8) top = Math.max(8, rect.top - mh - 6);

    menuEl.style.left = left + 'px';
    menuEl.style.top  = top  + 'px';
    menuEl.style.visibility = 'visible';
  }

  document.addEventListener('shown.bs.dropdown', function(e){
    const dropdownEl = e.target;
    const toggleEl   = e.relatedTarget || dropdownEl.querySelector('[data-bs-toggle="dropdown"], .dd-toggle');

    if (!dropdownEl || !toggleEl) return;

    const menuEl = dropdownEl.querySelector('.dropdown-menu');
    if (!menuEl) return;

    if (!dropdownEl.closest('.table-responsive, .table-wrap')) return;

    cleanup();

    const parent = menuEl.parentElement;

    menuEl.classList.add('dd-portal');
    document.body.appendChild(menuEl);

    menuEl.style.position  = 'fixed';
    menuEl.style.inset     = 'auto';
    menuEl.style.transform = 'none';
    menuEl.style.margin    = '0';

    positionMenu(toggleEl, menuEl);

    const inst = bootstrap.Dropdown.getOrCreateInstance(toggleEl);
    const onEnv = () => { try { inst.hide(); } catch(_){} };

    window.addEventListener('resize', onEnv);
    document.addEventListener('scroll', onEnv, true);

    active = { menu: menuEl, parent, onEnv };
  }, true);

  document.addEventListener('hidden.bs.dropdown', function(){
    cleanup();
  }, true);
})();
</script>

</body>
</html>
