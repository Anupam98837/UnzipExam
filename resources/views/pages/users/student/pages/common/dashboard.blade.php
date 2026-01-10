{{-- resources/views/pages/users/student/dashboard.blade.php --}}
@extends('pages.users.student.layout.structure')

@section('title', 'My Dashboard')
@section('header', 'My Dashboard')

@push('styles')
<style>
  /* Make page take full viewport height */
  .sd-page{
    min-height: calc(100vh - 64px); /* adjust if your layout header height differs */
    display:flex;
    align-items:center;
    justify-content:center;
    padding:18px 12px 28px;
  }

  .sd-wrap{
    width:100%;
    max-width:1180px;
  }

  .sd-hero{
    position:relative;
    overflow:hidden;
    border-radius:22px;
    background:var(--surface);
    border:1px solid var(--line-strong);
    box-shadow:var(--shadow-3);
    padding:22px;
    min-height: calc(100vh - 64px - 36px); /* full height feel inside wrapper */
    display:flex;
    align-items:center;
  }

  .sd-hero-grid{
    width:100%;
    display:grid;
    grid-template-columns: 1.15fr .85fr;
    gap:18px;
    align-items:center;
  }

  .sd-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:7px 12px;
    border-radius:999px;
    border:1px solid var(--line-strong);
    background:var(--surface-2);
    color:var(--secondary-color);
    font-size:var(--fs-13);
    font-weight:700;
    width:max-content;
  }

  .sd-title{
    font-family:var(--font-head);
    font-weight:900;
    color:var(--ink);
    font-size:clamp(1.6rem, 2.2vw, 2.2rem);
    line-height:1.12;
    margin:12px 0 8px;
  }

  .sd-subtitle{
    color:var(--muted-color);
    font-size:var(--fs-14);
    margin:0 0 16px;
    max-width:64ch;
  }

  .sd-actions{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:center;
    margin-top:6px;
  }

  .sd-btn{
    border-radius:999px;
    padding:10px 16px;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    gap:8px;
  }

  .sd-btn.btn-primary{
    background:var(--primary-color);
    border-color:var(--primary-color);
  }
  .sd-btn.btn-primary:hover{
    background:var(--secondary-color);
    border-color:var(--secondary-color);
  }

  .sd-btn.btn-light{
    background:var(--surface-2);
    border:1px solid var(--line-strong);
    color:var(--ink);
  }

  .sd-note{
    margin-top:14px;
    padding:12px 12px;
    border-radius:16px;
    border:1px dashed var(--line-strong);
    background:var(--surface-2);
    color:var(--muted-color);
    font-size:var(--fs-13);
    display:flex;
    gap:10px;
    align-items:flex-start;
  }
  .sd-note i{
    margin-top:2px;
    color:var(--secondary-color);
  }
  .sd-note strong{
    color:var(--ink);
    font-weight:900;
  }

  /* Right: floating visual */
  .sd-visual{
    position:relative;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:260px;
  }

  .sd-blob{
    position:absolute;
    width:min(420px, 80%);
    height:min(420px, 80%);
    border-radius:999px;
    background:radial-gradient(circle at 30% 30%,
      rgba(201,75,80,.35),
      rgba(158,54,58,.10) 55%,
      transparent 72%);
    opacity:.95;
    transform:translateY(-8px);
    pointer-events:none;
  }

  .sd-float-card{
    width:min(420px, 100%);
    border-radius:18px;
    border:1px solid var(--line-strong);
    background:var(--surface);
    box-shadow:var(--shadow-3);
    padding:16px;
    display:flex;
    align-items:center;
    gap:12px;
    animation: sdFloat 4.6s ease-in-out infinite;
  }

  .sd-float-icon{
    width:52px;height:52px;
    border-radius:18px;
    background:var(--t-primary);
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--primary-color);
    flex-shrink:0;
  }

  .sd-float-title{
    font-weight:900;
    font-family:var(--font-head);
    color:var(--ink);
    margin:0;
    font-size:1.05rem;
  }

  .sd-float-sub{
    margin:2px 0 0;
    color:var(--muted-color);
    font-size:var(--fs-13);
    line-height:1.4;
  }

  @keyframes sdFloat{
    0%,100%{ transform:translateY(0px) }
    50%{ transform:translateY(-12px) }
  }

  /* Decorative corner */
  .sd-corner{
    position:absolute;
    right:-100px;
    top:-100px;
    width:260px;
    height:260px;
    border-radius:70px;
    background:linear-gradient(135deg,
      rgba(201,75,80,.22),
      rgba(158,54,58,.10));
    transform:rotate(18deg);
    pointer-events:none;
  }

  /* Dark tweaks */
  html.theme-dark .sd-hero{ background:#020b13; }
  html.theme-dark .sd-note{ background:#04151f; }
  html.theme-dark .sd-float-card{ background:#04151f; }

  @media (max-width: 992px){
    .sd-hero{ padding:18px; }
    .sd-hero-grid{ grid-template-columns: 1fr; }
    .sd-visual{ min-height:220px; }
    .sd-page{ padding-top:14px; }
  }
</style>
@endpush

@section('content')
<div class="sd-page">
  <div class="sd-wrap">
    <div class="sd-hero">
      <div class="sd-corner"></div>

      <div class="sd-hero-grid">
        {{-- Left --}}
        <div>
          <div class="sd-badge">
            <i class="fa-solid fa-sparkles"></i>
            Student Dashboard
          </div>

          <h1 class="sd-title">
            Welcome back! 👋 <br>
            Continue your exams & improve daily.
          </h1>

          <p class="sd-subtitle">
            Jump straight into your quizzes, continue where you left off, and keep your preparation consistent.
            Everything you need is inside <b>My Quizzes</b>.
          </p>

          <div class="sd-actions">
            <a href="/student/quizzes" class="btn btn-primary sd-btn">
              <i class="fa-solid fa-clipboard-check"></i>
              Go to My Quizzes
            </a>

            <a href="/student/quizzes" class="btn btn-light sd-btn">
              <i class="fa-solid fa-play"></i>
              Continue Exam
            </a>
          </div>

          <div class="sd-note">
            <i class="fa-solid fa-circle-info"></i>
            <div>
              <strong>Quick tip:</strong> If you don’t see any quiz, contact your admin/instructor to assign one.
            </div>
          </div>
        </div>

        {{-- Right --}}
        <div class="sd-visual">
          <div class="sd-blob"></div>

          <div class="sd-float-card">
            <div class="sd-float-icon">
              <i class="fa-solid fa-trophy"></i>
            </div>
            <div>
              <p class="sd-float-title">Keep your streak alive</p>
              <p class="sd-float-sub">
                Do one quiz today — small effort, big result. ✅
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@endsection
