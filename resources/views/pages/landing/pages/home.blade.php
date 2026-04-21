@include('pages.landing.components.header')

<!-- Hero Section - Exam Portal -->
<section class="lp-hero">
  <div class="lp-hero-inner">
    <div class="lp-hero-content lp-animate is-visible">
      <div class="lp-hero-badge-wrapper">
        <div class="lp-hero-kicker">
          <span class="badge">
            <i class="fa-solid fa-circle-check"></i>
            Online
          </span>
          <span>Secure & reliable exam platform for institutions</span>
        </div>
      </div>
      
      <h1 class="lp-hero-title">
        Take exams <span class="highlight">anywhere</span>,<br/>
        anytime with confidence.
      </h1>
      
      <p class="lp-hero-sub">
        A comprehensive online examination system designed for educational institutions, coaching centers, and corporate training programs — secure, scalable, and easy to use.
      </p>
      
      <!-- CTA Buttons -->
      <div class="lp-hero-ctas">
        <a href="/register" class="lp-btn-primary lp-btn-large d-none">
          Get Started Free
          <i class="fa-solid fa-arrow-right"></i>
        </a>
        <a href="/demo" class="lp-btn-secondary lp-btn-large d-none">
          <i class="fa-solid fa-play-circle"></i>
          Watch Demo
        </a>
      </div>
      
      <!-- Trust badges -->
      <div class="lp-trust-badges">
        <div class="lp-trust-badge">
          <div class="trust-icon">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div class="trust-content">
            <strong>100% Secure</strong>
            <span>End-to-end encryption</span>
          </div>
        </div>
        <div class="lp-trust-badge">
          <div class="trust-icon">
            <i class="fa-solid fa-clock"></i>
          </div>
          <div class="trust-content">
            <strong>Auto-save</strong>
            <span>Never lose your answers</span>
          </div>
        </div>
        <div class="lp-trust-badge">
          <div class="trust-icon">
            <i class="fa-solid fa-chart-line"></i>
          </div>
          <div class="trust-content">
            <strong>Instant Results</strong>
            <span>Real-time scoring</span>
          </div>
        </div>
      </div>
      
      <!-- Stats -->
      <div class="lp-hero-stats">
        <div class="lp-hero-stat">
          <strong>50k+</strong>
          <span>Exams conducted</span>
        </div>
        <div class="lp-hero-stat">
          <strong>99.9%</strong>
          <span>Uptime guarantee</span>
        </div>
        <div class="lp-hero-stat">
          <strong>100+</strong>
          <span>Institutions trust us</span>
        </div>
      </div>
    </div>
    
    <!-- Right visuals: exam-related images -->
    <div class="lp-hero-visual lp-animate lp-animate-delay-1" data-lp-animate="fade">
  <div class="lp-hero-image-container">
    <div class="lp-hero-stack-wrap">
      <button type="button" class="lp-hero-nav lp-hero-nav-prev" id="heroPrevBtn">
        <i class="fa-solid fa-chevron-left"></i>
      </button>

      <!-- ✅ Dynamic stack: JS will inject .lp-hero-card-img here -->
      <div class="lp-hero-stack" id="heroImageStack"></div>

      <button type="button" class="lp-hero-nav lp-hero-nav-next" id="heroNextBtn">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
    </div>

    <!-- Floating cards for modern effect -->
    <div class="lp-floating-cards">
      <div class="float-card float-card-1">
        <i class="fa-solid fa-users"></i>
        <div>
          <strong>2M+</strong>
          <span>Active Users</span>
        </div>
      </div>
      <div class="float-card float-card-2">
        <i class="fa-solid fa-star"></i>
        <div>
          <strong>4.9/5</strong>
          <span>Rating</span>
        </div>
      </div>
    </div>
  </div>
</div>

  </div>
  
  <!-- Background decoration -->
  <div class="hero-bg-decoration">
    <div class="decoration-circle circle-1"></div>
    <div class="decoration-circle circle-2"></div>
    <div class="decoration-circle circle-3"></div>
  </div>
</section>

<!-- Trusted by -->
<section class="lp-trusted lp-animate" data-lp-animate="fade-up">
  <div class="lp-trusted-inner">
    <div class="lp-trusted-label">
      <i class="fa-solid fa-award"></i>
      Trusted by leading institutions
    </div>
    <div class="lp-trusted-logos">
      <div class="lp-logo-pill">
        <i class="fa-solid fa-building-columns"></i>
        <span>Universities</span>
      </div>
      <div class="lp-logo-pill">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>Coaching Institutes</span>
      </div>
      <div class="lp-logo-pill">
        <i class="fa-solid fa-briefcase"></i>
        <span>Corporate Training</span>
      </div>
      <div class="lp-logo-pill">
        <i class="fa-solid fa-landmark"></i>
        <span>Government Bodies</span>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="lp-section lp-how">
  <div class="lp-section-inner">
    <div class="lp-section-head">
      <div class="section-label">Process</div>
      <h2 class="lp-section-title">How UnzipExam works</h2>
      <div class="lp-section-sub">A streamlined process from registration to results in three simple steps.</div>
    </div>
    
    <div class="lp-how-grid">
      <div class="lp-how-card lp-animate lp-hover-lift" data-lp-animate="fade-up">
        <div class="how-card-header">
          <div class="lp-how-step">
            <span>01</span>
          </div>
          <div class="how-icon">
            <i class="fa-solid fa-user-plus"></i>
          </div>
        </div>
        <div class="lp-how-title">Register & Login</div>
        <div class="lp-how-text">
          Create your account or login with credentials provided by your institution. Access your personalized dashboard instantly.
        </div>
        <div class="how-card-footer">
          <a href="#" class="learn-more">
            Learn more <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>
      
      <div class="lp-how-card lp-animate lp-hover-lift lp-animate-delay-1" data-lp-animate="fade-up">
        <div class="how-card-header">
          <div class="lp-how-step">
            <span>02</span>
          </div>
          <div class="how-icon">
            <i class="fa-solid fa-laptop-code"></i>
          </div>
        </div>
        <div class="lp-how-title">Take the exam</div>
        <div class="lp-how-text">
          Access scheduled exams with secure browser technology. Auto-save ensures no answer is lost, even with connectivity issues.
        </div>
        <div class="how-card-footer">
          <a href="#" class="learn-more">
            Learn more <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>
      
      <div class="lp-how-card lp-animate lp-hover-lift lp-animate-delay-2" data-lp-animate="fade-up">
        <div class="how-card-header">
          <div class="lp-how-step">
            <span>03</span>
          </div>
          <div class="how-icon">
            <i class="fa-solid fa-chart-simple"></i>
          </div>
        </div>
        <div class="lp-how-title">Get instant results</div>
        <div class="lp-how-text">
          Receive immediate scores for objective tests. Detailed analytics help you understand performance and improve.
        </div>
        <div class="how-card-footer">
          <a href="#" class="learn-more">
            Learn more <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
    
    <!-- Process line connector -->
    <div class="process-connector"></div>
  </div>
</section>

<!-- Exam Categories -->
<section id="categories" class="lp-section lp-categories">
  <div class="lp-section-inner">
    <div class="lp-section-head">
      <div>
        <div class="section-label">Explore</div>
        <h2 class="lp-section-title">Exam categories</h2>
        <div class="lp-section-sub">Choose from various exam types and subjects tailored to your needs.</div>
      </div>
      <a href="/categories/all" class="lp-section-link">
        View all categories <i class="fa fa-arrow-right ms-1"></i>
      </a>
    </div>
    
    <div class="lp-cat-grid" id="lpCategoriesGrid">
      <div class="lp-cat-card lp-animate lp-hover-lift" style="cursor: pointer;">
        <div class="cat-card-inner">
          <div class="lp-cat-icon">
            <i class="fa-solid fa-graduation-cap"></i>
          </div>
          <div class="lp-cat-name">Academic Exams</div>
          <div class="lp-cat-meta">Semester exams, assignments, and quizzes</div>
          <div class="cat-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>
      </div>
      
      <div class="lp-cat-card lp-animate lp-hover-lift lp-animate-delay-1" style="cursor: pointer;">
        <div class="cat-card-inner">
          <div class="lp-cat-icon">
            <i class="fa-solid fa-trophy"></i>
          </div>
          <div class="lp-cat-name">Competitive Exams</div>
          <div class="lp-cat-meta">Entrance tests, aptitude, and placement exams</div>
          <div class="cat-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>
      </div>
      
      <div class="lp-cat-card lp-animate lp-hover-lift lp-animate-delay-2" style="cursor: pointer;">
        <div class="cat-card-inner">
          <div class="lp-cat-icon">
            <i class="fa-solid fa-certificate"></i>
          </div>
          <div class="lp-cat-name">Certification Tests</div>
          <div class="lp-cat-meta">Professional certifications and skill assessments</div>
          <div class="cat-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>
      </div>
      
      <div class="lp-cat-card lp-animate lp-hover-lift lp-animate-delay-3" style="cursor: pointer;">
        <div class="cat-card-inner">
          <div class="lp-cat-icon">
            <i class="fa-solid fa-clipboard-list"></i>
          </div>
          <div class="lp-cat-name">Mock Tests</div>
          <div class="lp-cat-meta">Practice exams with real-time simulation</div>
          <div class="cat-arrow">
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Exams -->
<section id="courses" class="lp-section lp-featured-exams">
  <div class="lp-section-inner">
    <div class="lp-section-head">
      <div>
        <div class="section-label">Popular</div>
        <h2 class="lp-section-title">Upcoming & featured exams</h2>
        <div class="lp-section-sub">Scheduled exams ready for you to take.</div>
      </div>
      <a href="/courses/all" class="lp-section-link">
        Browse all exams <i class="fa fa-arrow-right ms-1"></i>
      </a>
    </div>
    
    <div class="lp-course-grid" id="lpFeaturedCoursesGrid">
      <!-- Exam cards will be injected here via JS -->
    </div>
  </div>
</section>

<!-- PARALLAX - Platform Features -->
<section class="lp-parallax lp-animate" data-lp-animate="fade-up">
  <div class="lp-parallax-inner">
    <div class="lp-parallax-content">
      <div class="section-label light">Security</div>
      <h2 class="lp-parallax-title">Built for security and reliability.</h2>
      <p class="lp-parallax-sub">
        UnzipExam provides enterprise-grade security with advanced proctoring, anti-cheating measures, and robust infrastructure to ensure fair and secure examinations.
      </p>
      <div class="lp-parallax-pills">
        <span><i class="fa-solid fa-circle-check me-2"></i> Browser lockdown</span>
        <span><i class="fa-solid fa-circle-check me-2"></i> AI-powered proctoring</span>
        <span><i class="fa-solid fa-circle-check me-2"></i> Encrypted data</span>
        <span><i class="fa-solid fa-circle-check me-2"></i> 24/7 Monitoring</span>
      </div>
    </div>
    
    <div class="lp-parallax-cardGrid">
      <div class="lp-parallax-mini">
        <div class="parallax-mini-icon">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="parallax-mini-content">
          <h4>Advanced Security</h4>
          <p>
            Advanced security features including screen recording, tab switching detection, and face recognition prevent malpractice.
          </p>
        </div>
      </div>
      
      <div class="lp-parallax-mini">
        <div class="parallax-mini-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="parallax-mini-content">
          <h4>Time Management</h4>
          <p>
            Automatic time management with countdown timers, auto-submission, and time warnings keep exams on schedule.
          </p>
        </div>
      </div>
      
      <div class="lp-parallax-mini">
        <div class="parallax-mini-icon">
          <i class="fa-solid fa-chart-line"></i>
        </div>
        <div class="parallax-mini-content">
          <h4>Analytics & Reporting</h4>
          <p>
            Comprehensive analytics and reporting help institutions track performance, identify trends, and improve outcomes.
          </p>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Parallax Background -->
  <div class="parallax-bg"></div>
</section>

<!-- Platform Benefits -->
<section class="lp-section lp-outcomes" id="outcomes">
  <div class="lp-outcomes-inner">
    <div class="lp-section-head">
      <div class="section-label">Benefits</div>
      <h2 class="lp-section-title">Why choose UnzipExam?</h2>
      <div class="lp-section-sub">Benefits for students, educators, and institutions.</div>
    </div>
    
    <div class="lp-outcomes-grid">
      <div class="lp-animate lp-hover-lift" data-lp-animate="fade-up">
        <div class="lp-outcomes-logos">
          <span class="lp-company-pill">
            <i class="fa-solid fa-user-graduate"></i>
            <span>For Students</span>
          </span>
          <span class="lp-company-pill">
            <i class="fa-solid fa-chalkboard-user"></i>
            <span>For Educators</span>
          </span>
          <span class="lp-company-pill">
            <i class="fa-solid fa-building-columns"></i>
            <span>For Institutions</span>
          </span>
        </div>
      </div>
      
      <div class="lp-animate lp-animate-delay-1" data-lp-animate="fade-up">
        <ul class="lp-outcomes-list">
          <li>
            <i class="fa-solid fa-check-circle"></i>
            <span>Take exams from anywhere with a stable internet connection and compatible device.</span>
          </li>
          <li>
            <i class="fa-solid fa-check-circle"></i>
            <span>Instant results for objective tests with detailed performance analytics and insights.</span>
          </li>
          <li>
            <i class="fa-solid fa-check-circle"></i>
            <span>User-friendly interface with intuitive navigation and accessibility features.</span>
          </li>
          <li>
            <i class="fa-solid fa-check-circle"></i>
            <span>24/7 technical support to resolve issues before, during, and after exams.</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Stats band -->
<section class="lp-stats-band" id="features">
  <div class="lp-stats-inner">
    <div class="lp-stat-card lp-animate" data-lp-animate="fade-up">
      <div class="stat-icon">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <div class="stat-content">
        <strong>Secure platform</strong>
        <span>Advanced anti-cheating mechanisms with browser lockdown and AI proctoring.</span>
      </div>
    </div>
    
    <div class="lp-stat-card lp-animate lp-animate-delay-1" data-lp-animate="fade-up">
      <div class="stat-icon">
        <i class="fa-solid fa-gauge-high"></i>
      </div>
      <div class="stat-content">
        <strong>Instant grading</strong>
        <span>Automated evaluation for objective questions with immediate score release.</span>
      </div>
    </div>
    
    <div class="lp-stat-card lp-animate lp-animate-delay-2" data-lp-animate="fade-up">
      <div class="stat-icon">
        <i class="fa-solid fa-chart-line"></i>
      </div>
      <div class="stat-content">
        <strong>Detailed analytics</strong>
        <span>Comprehensive reports on performance, question-wise analysis, and trends.</span>
      </div>
    </div>
    
    <div class="lp-stat-card lp-animate lp-animate-delay-3" data-lp-animate="fade-up">
      <div class="stat-icon">
        <i class="fa-solid fa-mobile-screen"></i>
      </div>
      <div class="stat-content">
        <strong>Multi-device support</strong>
        <span>Access exams on desktop, laptop, or tablet with responsive design.</span>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section id="reviews" class="lp-section lp-testimonials">
  <div class="lp-section-inner">
    <div class="lp-section-head">
      <div class="section-label">Testimonials</div>
      <h2 class="lp-section-title">What users say</h2>
      <div class="lp-section-sub">Feedback from students and institutions using UnzipExam.</div>
    </div>
    
    <div class="lp-test-grid">
      <article class="lp-test-card lp-animate" data-lp-animate="fade-up">
        <div class="test-rating">
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
        </div>
        <p class="lp-test-text">
          "The platform is incredibly user-friendly. I had no issues navigating through the exam, and the auto-save feature gave me peace of mind."
        </p>
        <div class="lp-test-author">
          <div class="lp-avatar">P</div>
          <div>
            <span class="name">Priya Sharma</span><br/>
            <span class="role">Engineering Student</span>
          </div>
        </div>
      </article>
      
      <article class="lp-test-card lp-animate lp-animate-delay-1" data-lp-animate="fade-up">
        <div class="test-rating">
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
        </div>
        <p class="lp-test-text">
          "UnzipExam helped us conduct semester exams seamlessly during the pandemic. The proctoring features ensure exam integrity."
        </p>
        <div class="lp-test-author">
          <div class="lp-avatar">R</div>
          <div>
            <span class="name">Dr. Rajesh Kumar</span><br/>
            <span class="role">HOD, Computer Science</span>
          </div>
        </div>
      </article>
      
      <article class="lp-test-card lp-animate lp-animate-delay-2" data-lp-animate="fade-up">
        <div class="test-rating">
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
        </div>
        <p class="lp-test-text">
          "The analytics dashboard provides excellent insights into student performance. It helps us identify weak areas and improve teaching."
        </p>
        <div class="lp-test-author">
          <div class="lp-avatar">M</div>
          <div>
            <span class="name">Meera Patel</span><br/>
            <span class="role">Academic Coordinator</span>
          </div>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="lp-section lp-faq">
  <div class="lp-section-inner">
    <div class="lp-section-head">
      <div class="section-label">Support</div>
      <h2 class="lp-section-title">Frequently asked questions</h2>
      <div class="lp-section-sub">Common questions about the exam platform.</div>
    </div>
    
    <div class="lp-faq-layout">
      <div class="lp-faq-intro lp-animate" data-lp-animate="fade-up">
        <h3>Need help?</h3>
        <p>
          Have questions about how the exam platform works? We've compiled answers to the most common queries from students and institutions.
        </p>
        <ul class="lp-faq-intro-list">
          <li><i class="fa-solid fa-circle-check"></i> What technical requirements do I need?</li>
          <li><i class="fa-solid fa-circle-check"></i> How secure is the exam platform?</li>
          <li><i class="fa-solid fa-circle-check"></i> What happens if my internet disconnects?</li>
        </ul>
        <div class="lp-faq-contact">
          <div class="faq-contact-icon">
            <i class="fa-solid fa-headset"></i>
          </div>
          <div>
            <strong>Need more help?</strong>
            <p>Contact our support team via email or phone — we're available 24/7 during exam periods.</p>
            <a href="/contact-us" class="contact-link">Contact Support <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      </div>
      
      <div class="lp-animate lp-animate-delay-1" data-lp-animate="fade-up">
        <div class="accordion" id="faqAccordion">
          <div class="accordion-item lp-animate" data-lp-animate="fade-up">
            <h2 class="accordion-header" id="faqOne">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqOneBody" aria-expanded="false" aria-controls="faqOneBody">
                <i class="fa-solid fa-desktop"></i>
                What are the system requirements to take an exam?
              </button>
            </h2>
            <div id="faqOneBody" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                You need a computer or laptop with a stable internet connection (minimum 2 Mbps), updated web browser (Chrome, Firefox, or Edge), webcam for proctored exams, and a quiet environment. Mobile phones are not recommended for exams.
              </div>
            </div>
          </div>
          
          <div class="accordion-item lp-animate lp-animate-delay-1" data-lp-animate="fade-up">
            <h2 class="accordion-header" id="faqTwo">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwoBody" aria-expanded="false" aria-controls="faqTwoBody">
                <i class="fa-solid fa-shield-halved"></i>
                How does the platform prevent cheating?
              </button>
            </h2>
            <div id="faqTwoBody" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                We use multiple security measures including browser lockdown (disabling copy-paste, screenshots), tab-switching detection, webcam monitoring, randomized question banks, and AI-powered behavior analysis to ensure exam integrity.
              </div>
            </div>
          </div>
          
          <div class="accordion-item lp-animate lp-animate-delay-2" data-lp-animate="fade-up">
            <h2 class="accordion-header" id="faqThree">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqThreeBody" aria-expanded="false" aria-controls="faqThreeBody">
                <i class="fa-solid fa-wifi"></i>
                What if my internet disconnects during the exam?
              </button>
            </h2>
            <div id="faqThreeBody" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                The platform auto-saves your answers every few seconds. If disconnected, simply refresh and log back in — you'll resume from where you left off with all previous answers intact. The timer continues during disconnection.
              </div>
            </div>
          </div>
          
          <div class="accordion-item lp-animate" data-lp-animate="fade-up">
            <h2 class="accordion-header" id="faqFour">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqFourBody" aria-expanded="false" aria-controls="faqFourBody">
                <i class="fa-solid fa-chart-simple"></i>
                When will I get my results?
              </button>
            </h2>
            <div id="faqFourBody" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
              <div class="accordion-body">
                For objective (MCQ) exams, results are available immediately after submission. For subjective exams with manual evaluation, results are published according to the timeline set by your institution, typically within 3-7 days.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="lp-cta lp-animate" data-lp-animate="fade-up">
  <div class="lp-cta-inner">
    <div class="lp-cta-content">
      <div class="cta-icon">
        <i class="fa-solid fa-rocket"></i>
      </div>
      <div class="lp-cta-text">
        <h3 class="lp-cta-title">Ready to take your exam?</h3>
        <p class="lp-cta-sub">
          Login to your account to view scheduled exams. Need institutional access? Contact us for a demo.
        </p>
      </div>
    </div>
    <div class="lp-cta-actions">
      <a href="/login" class="lp-btn-primary lp-btn-large">
        <i class="fa-solid fa-sign-in-alt"></i>
        Student Login
      </a>
      <a href="/contact-us" class="lp-btn-outline lp-btn-large">
        <i class="fa-solid fa-envelope"></i>
        Contact Us
      </a>
    </div>
  </div>
  
  <!-- CTA Background -->
  <div class="cta-bg-decoration">
    <div class="cta-circle cta-circle-1"></div>
    <div class="cta-circle cta-circle-2"></div>
  </div>
</section>

<style>
/* ===========================
   MODERN LANDING PAGE STYLES
   Using existing CSS variables
   =========================== */

/* Hero Section Enhancements */
.lp-hero {
  position: relative;
  padding: 100px 0 80px;
  overflow: hidden;
  background: linear-gradient(135deg, var(--surface) 0%, var(--bg-soft) 100%);
}

.lp-hero-inner {
  max-width: 1340px;
  margin: 0 auto;
  padding: 0 24px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
  position: relative;
  z-index: 2;
}

.lp-hero-content {
  max-width: 600px;
}

.lp-hero-badge-wrapper {
  margin-bottom: 24px;
}

.lp-hero-kicker {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  font-size: var(--fs-13);
  color: var(--muted-color);
}

.lp-hero-kicker .badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: var(--success-bg);
  color: var(--success-color);
  border-radius: 20px;
  font-weight: 600;
  font-size: var(--fs-12);
  border: 1px solid var(--success-color);
}

.lp-hero-kicker .badge i {
  font-size: 10px;
}

.lp-hero-title {
  font-size: clamp(2.5rem, 5vw, 3.5rem);
  font-weight: 800;
  line-height: 1.15;
  color: var(--ink);
  margin-bottom: 20px;
  letter-spacing: -0.02em;
}

.lp-hero-title .highlight {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  position: relative;
}

.lp-hero-sub {
  font-size: var(--fs-16);
  line-height: 1.7;
  color: var(--muted-color);
  margin-bottom: 32px;
  max-width: 540px;
}

/* CTA Buttons */
.lp-hero-ctas {
  display: flex;
  gap: 16px;
  margin-bottom: 40px;
  flex-wrap: wrap;
}

.lp-btn-primary,
.lp-btn-secondary,
.lp-btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 600;
  font-size: var(--fs-15);
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  border: none;
}

.lp-btn-large {
  padding: 16px 32px;
  font-size: var(--fs-16);
}

.lp-btn-primary {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  color: #fff;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
}

.lp-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  color: #fff;
}

.lp-btn-secondary {
  background: var(--surface);
  color: var(--ink);
  border: 2px solid var(--line-soft);
}

.lp-btn-secondary:hover {
  background: var(--bg-soft);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.lp-btn-outline {
  background: transparent;
  color: var(--ink);
  border: 2px solid var(--line-soft);
}

.lp-btn-outline:hover {
  background: var(--surface);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

/* Trust Badges */
.lp-trust-badges {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 32px;
}

.lp-trust-badge {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border-radius: 16px;
  background: var(--surface);
  border: 1px solid var(--line-soft);
  transition: all 0.3s ease;
}

.lp-trust-badge:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  border-color: var(--primary-color);
}

.trust-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.trust-icon i {
  font-size: 18px;
  color: #fff;
}

.trust-content strong {
  display: block;
  font-size: var(--fs-14);
  color: var(--ink);
  font-weight: 600;
  margin-bottom: 4px;
}

.trust-content span {
  display: block;
  font-size: var(--fs-12);
  color: var(--muted-color);
  line-height: 1.4;
}

/* Hero Stats */
.lp-hero-stats {
  display: flex;
  gap: 32px;
  padding-top: 20px;
  border-top: 1px solid var(--line-soft);
}

.lp-hero-stat {
  flex: 1;
}

.lp-hero-stat strong {
  display: block;
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 4px;
}

.lp-hero-stat span {
  display: block;
  font-size: var(--fs-13);
  color: var(--muted-color);
}

/* Hero Visual */
.lp-hero-image-container {
  position: relative;
}

.lp-hero-stack-wrap {
  position: relative;
  aspect-ratio: 4/3;
  border-radius: 24px;
  overflow: hidden;
}

.lp-hero-card-img {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 24px;
  overflow: hidden;
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.lp-hero-card-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.lp-hero-card-img:hover img {
  transform: scale(1.05);
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.3) 100%);
  pointer-events: none;
}

/* Navigation Buttons */
.lp-hero-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s ease;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.lp-hero-nav:hover {
  background: var(--primary-color);
  color: #fff;
  transform: translateY(-50%) scale(1.1);
}

.lp-hero-nav-prev {
  left: -24px;
}

.lp-hero-nav-next {
  right: -24px;
}

/* Floating Cards */
.lp-floating-cards {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  pointer-events: none;
}

.float-card {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.8);
  animation: float 3s ease-in-out infinite;
}

.float-card i {
  font-size: 24px;
  color: var(--primary-color);
}

.float-card strong {
  display: block;
  font-size: var(--fs-16);
  font-weight: 700;
  color: var(--ink);
}

.float-card span {
  display: block;
  font-size: var(--fs-12);
  color: var(--muted-color);
}

.float-card-1 {
  top: 10%;
  right: -10%;
  animation-delay: 0s;
}

.float-card-2 {
  bottom: 15%;
  left: -10%;
  animation-delay: 1.5s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-20px);
  }
}

/* Background Decoration */
.hero-bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
  z-index: 1;
  pointer-events: none;
}

.decoration-circle {
  position: absolute;
  border-radius: 50%;
  opacity: 0.1;
}

.circle-1 {
  width: 400px;
  height: 400px;
  background: var(--primary-color);
  top: -200px;
  right: -100px;
}

.circle-2 {
  width: 300px;
  height: 300px;
  background: var(--accent-color);
  bottom: -150px;
  left: -100px;
}

.circle-3 {
  width: 200px;
  height: 200px;
  background: var(--primary-color);
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

/* Trusted Section */
.lp-trusted {
  padding: 60px 0;
  background: var(--bg-soft);
  border-top: 1px solid var(--line-soft);
  border-bottom: 1px solid var(--line-soft);
}

.lp-trusted-inner {
  max-width: 1340px;
  margin: 0 auto;
  padding: 0 24px;
  text-align: center;
}

.lp-trusted-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--fs-14);
  color: var(--muted-color);
  margin-bottom: 24px;
  font-weight: 600;
}

.lp-trusted-label i {
  color: var(--primary-color);
}

.lp-trusted-logos {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.lp-logo-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 30px;
  font-size: var(--fs-14);
  font-weight: 600;
  color: var(--ink);
  transition: all 0.3s ease;
}

.lp-logo-pill:hover {
  background: var(--primary-color);
  color: #fff;
  border-color: var(--primary-color);
  transform: translateY(-2px);
}

.lp-logo-pill i {
  font-size: 16px;
}

/* Section Label */
.section-label {
  display: inline-block;
  padding: 6px 16px;
  background: var(--primary-bg);
  color: var(--primary-color);
  border-radius: 20px;
  font-size: var(--fs-12);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 16px;
}

.section-label.light {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}

/* How It Works */
.lp-how {
  position: relative;
  padding: 100px 0;
}

.lp-section-inner {
  max-width: 1340px;
  margin: 0 auto;
  padding: 0 24px;
}

.lp-section-head {
  text-align: center;
  margin-bottom: 60px;
}

.lp-section-title {
  font-size: clamp(2rem, 4vw, 2.75rem);
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 12px;
  letter-spacing: -0.02em;
}

.lp-section-sub {
  font-size: var(--fs-16);
  color: var(--muted-color);
  max-width: 600px;
  margin: 0 auto;
}

.lp-how-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
  position: relative;
}

.lp-how-card {
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 20px;
  padding: 32px;
  transition: all 0.3s ease;
  position: relative;
}

.lp-how-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  border-color: var(--primary-color);
}

.how-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.lp-how-step {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--fs-18);
  font-weight: 700;
  color: #fff;
}

.how-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--bg-soft);
  display: flex;
  align-items: center;
  justify-content: center;
}

.how-icon i {
  font-size: 20px;
  color: var(--primary-color);
}

.lp-how-title {
  font-size: var(--fs-20);
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 12px;
}

.lp-how-text {
  font-size: var(--fs-14);
  color: var(--muted-color);
  line-height: 1.7;
  margin-bottom: 20px;
}

.how-card-footer .learn-more {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: var(--fs-13);
  font-weight: 600;
  color: var(--primary-color);
  text-decoration: none;
  transition: gap 0.3s ease;
}

.how-card-footer .learn-more:hover {
  gap: 10px;
}

/* Categories */
.lp-cat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
}

.lp-cat-card {
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 20px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.lp-cat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  border-color: var(--primary-color);
}

.cat-card-inner {
  padding: 32px 24px;
  position: relative;
}

.lp-cat-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.lp-cat-icon i {
  font-size: 28px;
  color: #fff;
}

.lp-cat-name {
  font-size: var(--fs-18);
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 8px;
}

.lp-cat-meta {
  font-size: var(--fs-13);
  color: var(--muted-color);
  line-height: 1.6;
  margin-bottom: 16px;
}

.cat-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: var(--bg-soft);
  transition: all 0.3s ease;
}

.cat-arrow i {
  font-size: 14px;
  color: var(--primary-color);
  transition: transform 0.3s ease;
}

.lp-cat-card:hover .cat-arrow {
  background: var(--primary-color);
}

.lp-cat-card:hover .cat-arrow i {
  color: #fff;
  transform: translateX(4px);
}

/* Parallax Section */
.lp-parallax {
  position: relative;
  padding: 100px 0;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  color: #fff;
  overflow: hidden;
}

.lp-parallax-inner {
  max-width: 1340px;
  margin: 0 auto;
  padding: 0 24px;
  position: relative;
  z-index: 2;
}

.lp-parallax-content {
  max-width: 600px;
  margin-bottom: 60px;
}

.lp-parallax-title {
  font-size: clamp(2rem, 4vw, 2.75rem);
  font-weight: 700;
  margin-bottom: 20px;
  color: #fff;
}

.lp-parallax-sub {
  font-size: var(--fs-16);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 24px;
}

.lp-parallax-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.lp-parallax-pills span {
  display: inline-flex;
  align-items: center;
  padding: 10px 18px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: 24px;
  font-size: var(--fs-13);
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.lp-parallax-cardGrid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.lp-parallax-mini {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 32px;
  transition: all 0.3s ease;
}

.lp-parallax-mini:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-8px);
}

.parallax-mini-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.parallax-mini-icon i {
  font-size: 24px;
  color: #fff;
}

.parallax-mini-content h4 {
  font-size: var(--fs-18);
  font-weight: 700;
  color: #fff;
  margin-bottom: 12px;
}

.parallax-mini-content p {
  font-size: var(--fs-14);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.85);
  margin: 0;
}

.parallax-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  opacity: 0.05;
  background-image: radial-gradient(circle, rgba(255,255,255,0.3) 1px, transparent 1px);
  background-size: 30px 30px;
}

/* Outcomes */
.lp-outcomes-grid {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 60px;
  align-items: center;
}

.lp-outcomes-logos {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.lp-company-pill {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 16px;
  transition: all 0.3s ease;
}

.lp-company-pill:hover {
  background: var(--primary-bg);
  border-color: var(--primary-color);
  transform: translateX(8px);
}

.lp-company-pill i {
  font-size: 24px;
  color: var(--primary-color);
}

.lp-company-pill span {
  font-size: var(--fs-15);
  font-weight: 600;
  color: var(--ink);
}

.lp-outcomes-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.lp-outcomes-list li {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 16px;
  transition: all 0.3s ease;
}

.lp-outcomes-list li:hover {
  background: var(--bg-soft);
  border-color: var(--primary-color);
  transform: translateX(8px);
}

.lp-outcomes-list li i {
  font-size: 20px;
  color: var(--success-color);
  flex-shrink: 0;
  margin-top: 2px;
}

.lp-outcomes-list li span {
  font-size: var(--fs-14);
  line-height: 1.7;
  color: var(--ink);
}

/* Stats Band */
.lp-stats-band {
  padding: 80px 0;
  background: var(--bg-soft);
}

.lp-stats-inner {
  max-width: 1340px;
  margin: 0 auto;
  padding: 0 24px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 32px;
}

.lp-stat-card {
  display: flex;
  gap: 20px;
  padding: 32px;
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 20px;
  transition: all 0.3s ease;
}

.lp-stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  border-color: var(--primary-color);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-icon i {
  font-size: 24px;
  color: #fff;
}

.stat-content strong {
  display: block;
  font-size: var(--fs-16);
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 6px;
}

.stat-content span {
  display: block;
  font-size: var(--fs-13);
  line-height: 1.6;
  color: var(--muted-color);
}

/* Testimonials */
.lp-testimonials {
  padding: 100px 0;
}

.lp-test-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 32px;
}

.lp-test-card {
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 20px;
  padding: 32px;
  transition: all 0.3s ease;
}

.lp-test-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  border-color: var(--primary-color);
}

.test-rating {
  display: flex;
  gap: 4px;
  margin-bottom: 20px;
}

.test-rating i {
  color: #ffa500;
  font-size: 16px;
}

.lp-test-text {
  font-size: var(--fs-15);
  line-height: 1.8;
  color: var(--ink);
  margin-bottom: 24px;
  font-style: italic;
}

.lp-test-author {
  display: flex;
  align-items: center;
  gap: 14px;
}

.lp-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--fs-18);
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
}

.lp-test-author .name {
  font-size: var(--fs-14);
  font-weight: 600;
  color: var(--ink);
}

.lp-test-author .role {
  font-size: var(--fs-12);
  color: var(--muted-color);
}

/* FAQ */
.lp-faq {
  padding: 100px 0;
  background: var(--bg-soft);
}

.lp-faq-layout {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 60px;
}

.lp-faq-intro h3 {
  font-size: var(--fs-24);
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 16px;
}

.lp-faq-intro p {
  font-size: var(--fs-15);
  line-height: 1.7;
  color: var(--muted-color);
  margin-bottom: 24px;
}

.lp-faq-intro-list {
  list-style: none;
  padding: 0;
  margin: 0 0 32px 0;
}

.lp-faq-intro-list li {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  font-size: var(--fs-14);
  color: var(--ink);
}

.lp-faq-intro-list li i {
  color: var(--success-color);
  font-size: 16px;
}

.lp-faq-contact {
  display: flex;
  gap: 16px;
  padding: 24px;
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 16px;
}

.faq-contact-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: var(--primary-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.faq-contact-icon i {
  font-size: 20px;
  color: var(--primary-color);
}

.lp-faq-contact strong {
  display: block;
  font-size: var(--fs-15);
  color: var(--ink);
  margin-bottom: 6px;
}

.lp-faq-contact p {
  font-size: var(--fs-13);
  color: var(--muted-color);
  margin-bottom: 12px;
  line-height: 1.6;
}

.contact-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: var(--fs-13);
  font-weight: 600;
  color: var(--primary-color);
  text-decoration: none;
  transition: gap 0.3s ease;
}

.contact-link:hover {
  gap: 10px;
}

/* Accordion Enhancements */
.accordion-item {
  background: var(--surface);
  border: 1px solid var(--line-soft);
  border-radius: 16px;
  margin-bottom: 16px;
  overflow: hidden;
}

.accordion-button {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 24px;
  background: var(--surface);
  color: var(--ink);
  font-size: var(--fs-15);
  font-weight: 600;
  border: none;
}

.accordion-button:not(.collapsed) {
  background: var(--primary-bg);
  color: var(--primary-color);
}

.accordion-button i {
  font-size: 18px;
}

.accordion-body {
  padding: 20px 24px 24px;
  font-size: var(--fs-14);
  line-height: 1.8;
  color: var(--muted-color);
}

/* CTA Section */
.lp-cta {
  position: relative;
  padding: 100px 0;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  overflow: hidden;
}

.lp-cta-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 60px;
  position: relative;
  z-index: 2;
}

.lp-cta-content {
  display: flex;
  gap: 24px;
  align-items: center;
  flex: 1;
}

.cta-icon {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.cta-icon i {
  font-size: 36px;
  color: #fff;
}

.lp-cta-title {
  font-size: clamp(1.75rem, 3vw, 2.25rem);
  font-weight: 700;
  color: #fff;
  margin-bottom: 12px;
}

.lp-cta-sub {
  font-size: var(--fs-16);
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.7;
  margin: 0;
}

.lp-cta-actions {
  display: flex;
  gap: 16px;
  flex-shrink: 0;
}

.lp-cta-actions .lp-btn-primary {
  background: #fff;
  color: var(--primary-color);
}

.lp-cta-actions .lp-btn-primary:hover {
  background: rgba(255, 255, 255, 0.9);
}

.lp-cta-actions .lp-btn-outline {
  background: transparent;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.5);
}

.lp-cta-actions .lp-btn-outline:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: #fff;
}

.cta-bg-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  pointer-events: none;
}

.cta-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
}

.cta-circle-1 {
  width: 300px;
  height: 300px;
  top: -150px;
  right: -100px;
}

.cta-circle-2 {
  width: 200px;
  height: 200px;
  bottom: -100px;
  left: -50px;
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.lp-animate {
  opacity: 0;
  animation: fadeInUp 0.8s ease forwards;
}

.lp-animate-delay-1 {
  animation-delay: 0.2s;
}

.lp-animate-delay-2 {
  animation-delay: 0.4s;
}

.lp-animate-delay-3 {
  animation-delay: 0.6s;
}

/* Hover Lift */
.lp-hover-lift {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Responsive */
@media (max-width: 1200px) {
  .lp-hero-inner {
    gap: 40px;
  }
  
  .lp-how-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .lp-cat-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .lp-stats-inner {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 992px) {
  .lp-hero-inner {
    grid-template-columns: 1fr;
    gap: 60px;
  }
  
  .lp-hero-content {
    max-width: 100%;
    text-align: center;
  }
  
  .lp-hero-ctas {
    justify-content: center;
  }
  
  .lp-trust-badges {
    max-width: 100%;
  }
  
  .lp-hero-stats {
    justify-content: center;
  }
  
  .float-card-1,
  .float-card-2 {
    display: none;
  }
  
  .lp-parallax-cardGrid {
    grid-template-columns: 1fr;
  }
  
  .lp-outcomes-grid {
    grid-template-columns: 1fr;
  }
  
  .lp-test-grid {
    grid-template-columns: 1fr;
  }
  
  .lp-faq-layout {
    grid-template-columns: 1fr;
  }
  
  .lp-cta-inner {
    flex-direction: column;
    text-align: center;
  }
  
  .lp-cta-content {
    flex-direction: column;
  }
  
  .lp-cta-actions {
    width: 100%;
    flex-direction: column;
  }
  
  .lp-cta-actions a {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .lp-hero {
    padding: 60px 0 40px;
  }
  
  .lp-trust-badges {
    grid-template-columns: 1fr;
  }
  
  .lp-hero-stats {
    flex-direction: column;
    gap: 20px;
  }
  
  .lp-how-grid {
    grid-template-columns: 1fr;
  }
  
  .lp-cat-grid {
    grid-template-columns: 1fr;
  }
  
  .lp-stats-inner {
    grid-template-columns: 1fr;
  }
  
  .lp-section-head {
    text-align: left;
  }
}
.lp-hero-nav-prev{left:1px;}
.lp-hero-nav-next{right:1px;}
/* =========================================================
   Hero Image Stack — Slide States
   ========================================================= */

/* Hide all cards by default */
.lp-hero-card-img {
  opacity: 0;
  transform: scale(0.92) translateX(60px);
  pointer-events: none;
  z-index: 0;
}

/* Active (center/front) slide */
.lp-hero-card-img.is-active {
  opacity: 1;
  transform: scale(1) translateX(0);
  pointer-events: auto;
  z-index: 3;
}

/* Next slide (peeking from right) */
.lp-hero-card-img.is-next {
  opacity: 0.45;
  transform: scale(0.94) translateX(48px);
  z-index: 2;
}

/* Prev slide (peeking from left) */
.lp-hero-card-img.is-prev {
  opacity: 0.45;
  transform: scale(0.94) translateX(-48px);
  z-index: 2;
}

/* All other slides — fully hidden */
.lp-hero-card-img.is-far {
  opacity: 0;
  transform: scale(0.88) translateX(80px);
  z-index: 0;
  pointer-events: none;
}

/* Smooth transitions */
.lp-hero-card-img {
  transition: opacity 0.55s cubic-bezier(0.4, 0, 0.2, 1),
              transform 0.55s cubic-bezier(0.4, 0, 0.2, 1);
}
/* Fix float cards appearing above the image stack */
.lp-floating-cards {
  z-index: 10;
}

.float-card {
  z-index: 10;
}
</style>

@include('pages.landing.components.footer')