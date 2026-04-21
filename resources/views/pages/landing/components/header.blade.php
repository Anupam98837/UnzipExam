<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>UnzipExam - Secure Online Examination Platform</title>
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/images/web/favicon.png') }}">
  
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>
  
  <!-- Your global theme -->
  <link rel="stylesheet" href="{{ asset('/assets/css/common/main.css') }}"/>
  
  <style>
    /* ===========================
       MODERN HEADER STYLES
       Using existing CSS variables
       =========================== */
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: var(--ink);
      background: var(--surface);
    }
    
    /* Top Announcement Bar */
    .lp-announcement-bar {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
      color: #fff;
      padding: 12px 0;
      font-size: var(--fs-13);
      overflow: hidden;
      position: relative;
    }
    
    .lp-announcement-scroll {
      display: inline-flex;
      white-space: nowrap;
      gap: 60px;
      animation: scroll-announcement 30s linear infinite;
    }
    
    .lp-announcement-scroll span {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
    }
    
    .lp-announcement-scroll i {
      font-size: 12px;
    }
    
    @keyframes scroll-announcement {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(-50%);
      }
    }
    
    /* Header */
    .lp-header {
      background: var(--surface);
      border-bottom: 1px solid var(--line-soft);
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 12px rgba(0,0,0,0.04);
      transition: all 0.3s ease;
    }
    
    .lp-header.scrolled {
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .lp-header-inner {
      max-width: 1340px;
      margin: 0 auto;
      padding: 0 24px;
      display: flex;
      align-items: center;
      height: 76px;
      gap: 32px;
    }
    
    /* Logo */
    .lp-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      flex-shrink: 0;
      transition: transform 0.3s ease;
    }
    
    .lp-logo:hover {
      transform: scale(1.05);
    }
    
    .lp-logo img {
      height: 36px;
    }
    
    .lp-logo-text {
      font-size: var(--fs-20);
      font-weight: 700;
      color: var(--ink);
      letter-spacing: -0.02em;
    }
    
    /* Search */
    .lp-header-search {
      flex: 1;
      max-width: 500px;
    }
    
    .lp-search-form {
      display: flex;
      align-items: center;
      background: var(--bg-soft);
      border: 2px solid var(--line-soft);
      border-radius: 12px;
      padding: 10px 18px;
      transition: all 0.3s ease;
    }
    
    .lp-search-form:focus-within {
      background: var(--surface);
      border-color: var(--primary-color);
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .lp-search-form i {
      color: var(--muted-color);
      font-size: 16px;
      margin-right: 12px;
      transition: color 0.3s ease;
    }
    
    .lp-search-form:focus-within i {
      color: var(--primary-color);
    }
    
    .lp-search-form input {
      flex: 1;
      border: none;
      outline: none;
      background: transparent;
      font-size: var(--fs-14);
      color: var(--ink);
      font-weight: 500;
    }
    
    .lp-search-form input::placeholder {
      color: var(--muted-color);
    }
    
    /* Navigation */
    .lp-nav {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-left: auto;
    }
    
    .lp-nav-item {
      position: relative;
      padding: 10px 16px;
      color: var(--ink);
      text-decoration: none;
      font-size: var(--fs-14);
      font-weight: 600;
      border-radius: 10px;
      transition: all 0.3s ease;
    }
    
    .lp-nav-item::after {
      content: '';
      position: absolute;
      bottom: 6px;
      left: 50%;
      transform: translateX(-50%) scaleX(0);
      width: 20px;
      height: 3px;
      background: var(--primary-color);
      border-radius: 2px;
      transition: transform 0.3s ease;
    }
    
    .lp-nav-item:hover {
      background: var(--bg-soft);
      color: var(--primary-color);
    }
    
    .lp-nav-item:hover::after {
      transform: translateX(-50%) scaleX(1);
    }
    
    /* Buttons */
    .lp-header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }
    
    .lp-btn-login {
      padding: 11px 24px;
      border: 2px solid var(--line-soft);
      background: transparent;
      color: var(--ink);
      font-size: var(--fs-14);
      font-weight: 700;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .lp-btn-login:hover {
      background: var(--bg-soft);
      border-color: var(--primary-color);
      color: var(--primary-color);
      transform: translateY(-2px);
    }
    
    .lp-btn-signup {
      padding: 11px 24px;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
      color: #fff;
      border: none;
      font-size: var(--fs-14);
      font-weight: 700;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .lp-btn-signup:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }
    
    /* Mobile Menu Toggle */
    .lp-mobile-toggle {
      display: none;
      width: 44px;
      height: 44px;
      border: 2px solid var(--line-soft);
      background: var(--surface);
      border-radius: 10px;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      margin-left: auto;
      transition: all 0.3s ease;
    }
    
    .lp-mobile-toggle:hover {
      background: var(--bg-soft);
      border-color: var(--primary-color);
    }
    
    .lp-mobile-toggle i {
      font-size: 18px;
      color: var(--ink);
      transition: transform 0.3s ease;
    }
    
    .lp-mobile-toggle.active i {
      transform: rotate(90deg);
    }
    
    /* Mobile Menu */
    .lp-mobile-menu {
      display: none;
      position: fixed;
      top: 76px;
      left: 0;
      right: 0;
      background: var(--surface);
      border-bottom: 1px solid var(--line-soft);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      padding: 24px;
      z-index: 999;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.4s ease, padding 0.4s ease;
    }
    
    .lp-mobile-menu.is-open {
      display: block;
      max-height: 500px;
      padding: 24px;
    }
    
    .lp-mobile-nav {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 20px;
    }
    
    .lp-mobile-nav .lp-nav-item {
      padding: 14px 16px;
      border-radius: 10px;
      background: var(--bg-soft);
    }
    
    .lp-mobile-nav .lp-nav-item::after {
      display: none;
    }
    
    .lp-mobile-actions {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .lp-mobile-actions button {
      width: 100%;
      padding: 14px;
    }
    
    /* Quick Links */
    .lp-header-quick-links {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-left: 8px;
    }
    
    .lp-quick-link {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--bg-soft);
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      transition: all 0.3s ease;
      position: relative;
    }
    
    .lp-quick-link:hover {
      background: var(--primary-bg);
      transform: translateY(-2px);
    }
    
    .lp-quick-link i {
      font-size: 16px;
      color: var(--ink);
      transition: color 0.3s ease;
    }
    
    .lp-quick-link:hover i {
      color: var(--primary-color);
    }
    
    .lp-quick-link .badge-count {
      position: absolute;
      top: -4px;
      right: -4px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: var(--danger-color);
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
      .lp-header-search {
        max-width: 350px;
      }
      
      .lp-nav {
        gap: 2px;
      }
      
      .lp-nav-item {
        padding: 10px 12px;
        font-size: var(--fs-13);
      }
    }
    
    @media (max-width: 768px) {
      .lp-header-search,
      .lp-nav,
      .lp-header-actions,
      .lp-header-quick-links {
        display: none;
      }
      
      .lp-mobile-toggle {
        display: flex;
      }
      
      .lp-header-inner {
        height: 64px;
      }
      
      .lp-mobile-menu {
        top: 64px;
      }
    }
    
    /* Dark Theme Support */
    html.theme-dark .lp-announcement-bar {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
    }
    
    html.theme-dark .lp-header {
      background: var(--surface);
      border-bottom-color: var(--line-soft);
    }
    
    html.theme-dark .lp-logo-text {
      color: var(--ink);
    }
    
    html.theme-dark .lp-search-form {
      background: var(--bg-soft);
      border-color: var(--line-soft);
    }
    
    html.theme-dark .lp-search-form:focus-within {
      background: var(--surface);
      border-color: var(--primary-color);
    }
    
    html.theme-dark .lp-search-form i,
    html.theme-dark .lp-search-form input {
      color: var(--ink);
    }
    
    html.theme-dark .lp-search-form input::placeholder {
      color: var(--muted-color);
    }
    
    html.theme-dark .lp-nav-item {
      color: var(--ink);
    }
    
    html.theme-dark .lp-nav-item:hover {
      background: var(--bg-soft);
      color: var(--primary-color);
    }
    
    html.theme-dark .lp-btn-login {
      border-color: var(--line-soft);
      color: var(--ink);
    }
    
    html.theme-dark .lp-btn-login:hover {
      background: var(--bg-soft);
      border-color: var(--primary-color);
      color: var(--primary-color);
    }
    
    html.theme-dark .lp-mobile-toggle {
      border-color: var(--line-soft);
      background: var(--surface);
    }
    
    html.theme-dark .lp-mobile-toggle i {
      color: var(--ink);
    }
    
    html.theme-dark .lp-mobile-menu {
      background: var(--surface);
      border-bottom-color: var(--line-soft);
    }
    
  </style>
</head>

<body>
  <!-- Global Overlay -->
  <!-- @include('partials.overlay') -->
 {{-- Announcement Bar (Updates) --}}
<a href="{{ url('/updates/all') }}" class="lp-announcement-link" aria-label="View all updates">
  <div class="lp-announcement-bar">
    <div class="lp-announcement-scroll" id="lpUpdatesScroll">
      {{-- Fallback (shown until JS loads / if API fails) --}}
      <!-- <span><i class="fa-solid fa-sparkles"></i> Welcome to UnzipExam — stay tuned for updates!</span>
      <span><i class="fa-solid fa-calendar-check"></i> New quizzes and games added regularly</span>
      <span><i class="fa-solid fa-trophy"></i> Mock tests are live — start practicing today</span> -->
    </div>
  </div>
</a>

  
  <!-- Header -->
  <header class="lp-header" id="mainHeader">
    <div class="lp-header-inner">
      <!-- Logo -->
      <a href="/" class="lp-logo">
        <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="UnzipExam">
        <span class="lp-logo-text">UnzipExam</span>
      </a>
      
      <!-- Search -->
      <div class="lp-header-search">
        <form class="lp-search-form" onsubmit="return false;">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search for exams, categories..." autocomplete="off">
        </form>
      </div>
      
      <!-- Navigation -->
      <nav class="lp-nav">
        <a href="{{ url('/') }}" class="lp-nav-item">Home</a>
        <a href="{{ url('exams/all') }}" class="lp-nav-item">Exams</a>
        <a href="{{ url('categories/all') }}" class="lp-nav-item">Categories</a>
        <a href="{{ url('/about-us') }}" class="lp-nav-item">About</a>
        <a href="{{ url('/contact-us') }}" class="lp-nav-item">Contact</a>
      </nav>
      
      <!-- Quick Links (Optional) -->
      <div class="lp-header-quick-links">
        <a href="#" class="lp-quick-link" title="Notifications">
          <i class="fa-solid fa-bell"></i>
          <span class="badge-count">3</span>
        </a>
      </div>
      
      <!-- Actions -->
      <div class="lp-header-actions">
        <button class="lp-btn-login" id="lpLoginBtn">Log in</button>
        <button class="lp-btn-signup d-none" id="lpSignupBtn">Sign up</button>
      </div>
      
      <!-- Mobile Toggle -->
      <button class="lp-mobile-toggle" id="lpMobileToggle">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </header>
  
  <!-- Mobile Menu -->
  <div class="lp-mobile-menu" id="lpMobileMenu">
    <!-- Search in Mobile -->
    <div class="lp-header-search" style="display: block; max-width: 100%; margin-bottom: 20px;">
      <form class="lp-search-form" onsubmit="return false;">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" placeholder="Search for exams..." autocomplete="off">
      </form>
    </div>
    
    <nav class="lp-mobile-nav">
      <a href="{{ url('/') }}" class="lp-nav-item">
        <i class="fa-solid fa-house"></i> Home
      </a>
      <a href="{{ url('exams/all') }}" class="lp-nav-item">
        <i class="fa-solid fa-book"></i> Exams
      </a>
      <a href="{{ url('categories/all') }}" class="lp-nav-item">
        <i class="fa-solid fa-grid-2"></i> Categories
      </a>
      <a href="{{ url('/about-us') }}" class="lp-nav-item">
        <i class="fa-solid fa-info-circle"></i> About
      </a>
      <a href="{{ url('/contact-us') }}" class="lp-nav-item">
        <i class="fa-solid fa-envelope"></i> Contact
      </a>
    </nav>
    
    <div class="lp-mobile-actions">
      <button class="lp-btn-login">
        <i class="fa-solid fa-sign-in-alt"></i> Log in
      </button>
      <button class="lp-btn-signup">
        <i class="fa-solid fa-user-plus"></i> Sign up
      </button>
    </div>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Header scroll effect
      const header = document.getElementById('mainHeader');
      let lastScroll = 0;
      
      window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
          header.classList.add('scrolled');
        } else {
          header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
      });
      
      // Mobile menu toggle
      const toggle = document.getElementById('lpMobileToggle');
      const menu = document.getElementById('lpMobileMenu');
      const icon = toggle?.querySelector('i');
      
      if (toggle && menu) {
        toggle.addEventListener('click', () => {
          const isOpen = menu.classList.toggle('is-open');
          toggle.classList.toggle('active', isOpen);
          
          if (icon) {
            icon.classList.toggle('fa-bars', !isOpen);
            icon.classList.toggle('fa-xmark', isOpen);
          }
        });
        
        // Close menu on link click
        menu.querySelectorAll('a').forEach(link => {
          link.addEventListener('click', () => {
            menu.classList.remove('is-open');
            toggle.classList.remove('active');
            
            if (icon) {
              icon.classList.add('fa-bars');
              icon.classList.remove('fa-xmark');
            }
          });
        });
        
        // Close menu on outside click
        document.addEventListener('click', (e) => {
          if (!menu.contains(e.target) && !toggle.contains(e.target)) {
            menu.classList.remove('is-open');
            toggle.classList.remove('active');
            
            if (icon) {
              icon.classList.add('fa-bars');
              icon.classList.remove('fa-xmark');
            }
          }
        });
      }
      
      // Login/Signup buttons
      const loginBtn = document.getElementById('lpLoginBtn');
      const signupBtn = document.getElementById('lpSignupBtn');
      const mobileLoginBtn = document.querySelector('.lp-mobile-actions .lp-btn-login');
      const mobileSignupBtn = document.querySelector('.lp-mobile-actions .lp-btn-signup');
      
      const getMyRole = async (token) => {
        if (!token) return '';
        try {
          const res = await fetch('/api/auth/my-role', {
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
        } catch (err) {
          console.error('[Header] Role fetch error:', err);
        }
        return '';
      };
      
      const updateAuthButtons = async () => {
        const token = sessionStorage.getItem('token') || localStorage.getItem('token');
        
        if (!token) {
          if (loginBtn) loginBtn.innerHTML = '<i class="fa-solid fa-sign-in-alt"></i> Log in';
          if (signupBtn) signupBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Sign up';
          if (mobileLoginBtn) mobileLoginBtn.innerHTML = '<i class="fa-solid fa-sign-in-alt"></i> Log in';
          if (mobileSignupBtn) mobileSignupBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Sign up';
          return;
        }
        
        const role = await getMyRole(token);
        
        if (role) {
          if (loginBtn) loginBtn.innerHTML = '<i class="fa-solid fa-gauge-high"></i> Dashboard';
          if (mobileLoginBtn) mobileLoginBtn.innerHTML = '<i class="fa-solid fa-gauge-high"></i> Dashboard';
          if (signupBtn) signupBtn.style.display = 'none';
          if (mobileSignupBtn) mobileSignupBtn.style.display = 'none';
        } else {
          if (loginBtn) loginBtn.innerHTML = '<i class="fa-solid fa-sign-in-alt"></i> Log in';
          if (mobileLoginBtn) mobileLoginBtn.innerHTML = '<i class="fa-solid fa-sign-in-alt"></i> Log in';
          if (signupBtn) {
            signupBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Sign up';
            signupBtn.style.display = 'block';
          }
          if (mobileSignupBtn) {
            mobileSignupBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Sign up';
            mobileSignupBtn.style.display = 'block';
          }
        }
      };
      
      if (loginBtn) {
        loginBtn.addEventListener('click', () => {
          const token = sessionStorage.getItem('token') || localStorage.getItem('token');
          if (token) {
            window.location.href = '/dashboard';
          } else {
            window.location.href = '/login';
          }
        });
      }
      
      if (mobileLoginBtn) {
        mobileLoginBtn.addEventListener('click', () => {
          const token = sessionStorage.getItem('token') || localStorage.getItem('token');
          if (token) {
            window.location.href = '/dashboard';
          } else {
            window.location.href = '/login';
          }
        });
      }
      
      if (signupBtn) {
        signupBtn.addEventListener('click', () => {
          window.location.href = '/register';
        });
      }
      
      if (mobileSignupBtn) {
        mobileSignupBtn.addEventListener('click', () => {
          window.location.href = '/register';
        });
      }
      
      updateAuthButtons();
      
      // Search functionality (placeholder)
      const searchInputs = document.querySelectorAll('.lp-search-form input');
      searchInputs.forEach(input => {
        input.addEventListener('keypress', (e) => {
          if (e.key === 'Enter') {
            const query = e.target.value.trim();
            if (query) {
              window.location.href = `/search?q=${encodeURIComponent(query)}`;
            }
          }
        });
      });
    });
  </script>