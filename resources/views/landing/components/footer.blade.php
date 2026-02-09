<!-- Footer -->
<footer class="lp-footer">
  <div class="lp-footer-top">
    <div class="lp-container">
      <div class="lp-footer-grid">
        <!-- Column 1 -->
        <div class="lp-footer-col">
          <h4>Platform</h4>
          <ul>
            <li><a href="/exams/all"><i class="fa-solid fa-chevron-right"></i> Browse exams</a></li>
            <li><a href="/categories/all"><i class="fa-solid fa-chevron-right"></i> Categories</a></li>
            <li><a href="/about-us"><i class="fa-solid fa-chevron-right"></i> About us</a></li>
            <li><a href="/features"><i class="fa-solid fa-chevron-right"></i> Features</a></li>
            <li><a href="/pricing"><i class="fa-solid fa-chevron-right"></i> Pricing</a></li>
          </ul>
        </div>
        
        <!-- Column 2 -->
        <div class="lp-footer-col">
          <h4>Support</h4>
          <ul>
            <li><a href="/help"><i class="fa-solid fa-chevron-right"></i> Help center</a></li>
            <li><a href="/contact-us"><i class="fa-solid fa-chevron-right"></i> Contact us</a></li>
            <li><a href="/faq"><i class="fa-solid fa-chevron-right"></i> FAQ</a></li>
            <li><a href="/technical-requirements"><i class="fa-solid fa-chevron-right"></i> System requirements</a></li>
            <li><a href="/tutorials"><i class="fa-solid fa-chevron-right"></i> Tutorials</a></li>
          </ul>
        </div>
        
        <!-- Column 3 -->
        <div class="lp-footer-col">
          <h4>Resources</h4>
          <ul>
            <li><a href="/blog"><i class="fa-solid fa-chevron-right"></i> Blog</a></li>
            <li><a href="/updates"><i class="fa-solid fa-chevron-right"></i> Updates</a></li>
            <li><a href="/guides"><i class="fa-solid fa-chevron-right"></i> Study guides</a></li>
            <li><a href="/exam-tips"><i class="fa-solid fa-chevron-right"></i> Exam tips</a></li>
            <li><a href="/newsletter"><i class="fa-solid fa-chevron-right"></i> Newsletter</a></li>
          </ul>
        </div>
        
        <!-- Column 4 -->
        <div class="lp-footer-col">
          <h4>Company</h4>
          <ul>
            <li><a href="/careers"><i class="fa-solid fa-chevron-right"></i> Careers</a></li>
            <li><a href="/partners"><i class="fa-solid fa-chevron-right"></i> Partners</a></li>
            <li><a href="/institutions"><i class="fa-solid fa-chevron-right"></i> For institutions</a></li>
            <li><a href="/press"><i class="fa-solid fa-chevron-right"></i> Press</a></li>
            <li><a href="/investors"><i class="fa-solid fa-chevron-right"></i> Investors</a></li>
          </ul>
        </div>
        
        <!-- Column 5 - Newsletter -->
        <div class="lp-footer-col lp-footer-newsletter">
          <h4>Stay Updated</h4>
          <p>Subscribe to our newsletter for exam updates and tips.</p>
          <form class="newsletter-form" onsubmit="return false;">
            <input type="email" placeholder="Your email address" required>
            <button type="submit">
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </form>
          <div class="lp-footer-social">
            <a href="#" aria-label="Instagram" target="_blank" rel="noopener">
              <i class="fa-brands fa-instagram"></i>
            </a>
            <a href="#" aria-label="YouTube" target="_blank" rel="noopener">
              <i class="fa-brands fa-youtube"></i>
            </a>
            <a href="#" aria-label="LinkedIn" target="_blank" rel="noopener">
              <i class="fa-brands fa-linkedin-in"></i>
            </a>
            <a href="#" aria-label="Twitter" target="_blank" rel="noopener">
              <i class="fa-brands fa-twitter"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="lp-footer-bottom">
    <div class="lp-container">
      <div class="lp-footer-bottom-inner">
        <!-- Logo & Info -->
        <div class="lp-footer-brand">
          <a href="/" class="lp-footer-logo">
            <img src="{{ asset('/assets/media/images/web/logo.png') }}" alt="UnzipExam">
            <span>UnzipExam</span>
          </a>
          <p>Secure, reliable online examination platform trusted by institutions worldwide.</p>
        </div>
        
        <!-- Legal Links -->
        <div class="lp-footer-legal">
          <a href="/terms&conditions">Terms of Service</a>
          <span class="separator">•</span>
          <a href="/privacypolicy">Privacy Policy</a>
          <span class="separator">•</span>
          <a href="/refundpolicy">Refund Policy</a>
          <span class="separator">•</span>
          <a href="/cookies">Cookies</a>
        </div>
        
        <!-- Language & Theme -->
        <div class="lp-footer-right">
          <button class="lp-language-btn">
            <i class="fa-solid fa-globe"></i>
            <span>English</span>
            <i class="fa-solid fa-chevron-down"></i>
          </button>
        </div>
      </div>
      
      <!-- Copyright -->
      <div class="lp-footer-copyright">
        <p>© {{ date('Y') }} UnzipExam. All rights reserved. Made with <i class="fa-solid fa-heart"></i> for better education.</p>
      </div>
    </div>
  </div>
</footer>

<!-- Back to Top -->
<button class="lp-back-to-top" id="lpBackToTop" aria-label="Back to top">
  <i class="fa-solid fa-arrow-up"></i>
</button>

<style>
/* ===========================
   MODERN FOOTER STYLES
   Using existing CSS variables
   =========================== */

.lp-footer {
  background: var(--surface);
  color: var(--ink);
  margin-top: auto;
  border-top: 1px solid var(--line-soft);
}

.lp-container {
  max-width: 1340px;
  margin: 0 auto;
  padding: 0 24px;
}

/* Footer Top */
.lp-footer-top {
  padding: 80px 0 60px;
  background: linear-gradient(180deg, var(--bg-soft) 0%, var(--surface) 100%);
}

.lp-footer-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 40px;
}

.lp-footer-col h4 {
  font-size: var(--fs-15);
  font-weight: 700;
  color: var(--ink);
  margin-bottom: 20px;
  position: relative;
  padding-bottom: 12px;
}

.lp-footer-col h4::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 40px;
  height: 3px;
  background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
  border-radius: 2px;
}

.lp-footer-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.lp-footer-col ul li {
  margin-bottom: 12px;
}

.lp-footer-col ul li a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--muted-color);
  text-decoration: none;
  font-size: var(--fs-14);
  transition: all 0.3s ease;
  position: relative;
  padding-left: 0;
}

.lp-footer-col ul li a i {
  font-size: 10px;
  opacity: 0;
  transform: translateX(-10px);
  transition: all 0.3s ease;
}

.lp-footer-col ul li a:hover {
  color: var(--primary-color);
  padding-left: 8px;
}

.lp-footer-col ul li a:hover i {
  opacity: 1;
  transform: translateX(0);
}

/* Newsletter Column */
.lp-footer-newsletter {
  grid-column: span 1;
}

.lp-footer-newsletter p {
  font-size: var(--fs-13);
  color: var(--muted-color);
  line-height: 1.6;
  margin-bottom: 16px;
}

.newsletter-form {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
}

.newsletter-form input {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid var(--line-soft);
  border-radius: 10px;
  background: var(--surface);
  color: var(--ink);
  font-size: var(--fs-13);
  transition: all 0.3s ease;
}

.newsletter-form input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px var(--primary-bg);
}

.newsletter-form input::placeholder {
  color: var(--muted-color);
}

.newsletter-form button {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  color: #fff;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.newsletter-form button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

/* Social Links */
.lp-footer-social {
  display: flex;
  gap: 12px;
}

.lp-footer-social a {
  width: 44px;
  height: 44px;
  background: var(--bg-soft);
  color: var(--ink);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: all 0.3s ease;
  border: 2px solid var(--line-soft);
}

.lp-footer-social a:hover {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  color: #fff;
  border-color: transparent;
  transform: translateY(-4px);
}

.lp-footer-social i {
  font-size: 18px;
}

/* Footer Bottom */
.lp-footer-bottom {
  padding: 32px 0;
  border-top: 1px solid var(--line-soft);
}

.lp-footer-bottom-inner {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 32px;
  margin-bottom: 24px;
}

/* Footer Brand */
.lp-footer-brand {
  max-width: 400px;
}

.lp-footer-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  margin-bottom: 12px;
  transition: transform 0.3s ease;
}

.lp-footer-logo:hover {
  transform: scale(1.05);
}

.lp-footer-logo img {
  height: 32px;
}

.lp-footer-logo span {
  font-size: var(--fs-18);
  font-weight: 700;
  color: var(--ink);
}

.lp-footer-brand p {
  font-size: var(--fs-13);
  color: var(--muted-color);
  line-height: 1.6;
  margin: 0;
}

/* Legal Links */
.lp-footer-legal {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.lp-footer-legal a {
  font-size: var(--fs-13);
  color: var(--muted-color);
  text-decoration: none;
  transition: color 0.3s ease;
}

.lp-footer-legal a:hover {
  color: var(--primary-color);
}

.lp-footer-legal .separator {
  color: var(--line-soft);
  font-size: var(--fs-12);
}

/* Footer Right */
.lp-footer-right {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 16px;
}

/* Language Selector */
.lp-language-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-soft);
  border: 2px solid var(--line-soft);
  color: var(--ink);
  padding: 10px 18px;
  border-radius: 10px;
  font-size: var(--fs-13);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.lp-language-btn:hover {
  background: var(--surface);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.lp-language-btn i {
  font-size: 14px;
}

.lp-language-btn .fa-chevron-down {
  font-size: 11px;
  margin-left: 4px;
}

/* Copyright */
.lp-footer-copyright {
  padding-top: 24px;
  border-top: 1px solid var(--line-soft);
}

.lp-footer-copyright p {
  font-size: var(--fs-12);
  color: var(--muted-color);
  margin: 0;
  text-align: center;
}

.lp-footer-copyright .fa-heart {
  color: var(--danger-color);
  animation: heartbeat 1.5s ease-in-out infinite;
}

@keyframes heartbeat {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

/* Back to Top */
.lp-back-to-top {
  position: fixed;
  bottom: 32px;
  right: 32px;
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  color: #fff;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transform: translateY(20px);
  transition: all 0.3s ease;
  z-index: 999;
  box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}

.lp-back-to-top.visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.lp-back-to-top:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.lp-back-to-top i {
  font-size: 18px;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .lp-footer-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .lp-footer-newsletter {
    grid-column: span 3;
  }
}

@media (max-width: 768px) {
  .lp-footer-top {
    padding: 60px 0 40px;
  }
  
  .lp-footer-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
  }
  
  .lp-footer-newsletter {
    grid-column: span 2;
  }
  
  .lp-footer-bottom-inner {
    grid-template-columns: 1fr;
    gap: 24px;
    text-align: center;
  }
  
  .lp-footer-brand {
    max-width: 100%;
  }
  
  .lp-footer-legal {
    justify-content: center;
  }
  
  .lp-footer-right {
    justify-content: center;
  }
  
  .lp-back-to-top {
    bottom: 24px;
    right: 24px;
    width: 48px;
    height: 48px;
  }
}

@media (max-width: 480px) {
  .lp-footer-grid {
    grid-template-columns: 1fr;
  }
  
  .lp-footer-newsletter {
    grid-column: span 1;
  }
  
  .lp-footer-social {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .lp-footer-legal {
    flex-direction: column;
    gap: 8px;
  }
  
  .lp-footer-legal .separator {
    display: none;
  }
  
  .newsletter-form {
    flex-direction: column;
  }
  
  .newsletter-form button {
    width: 100%;
  }
}

/* Dark Theme Support */
html.theme-dark .lp-footer {
  background: var(--surface);
  border-top-color: var(--line-soft);
}

html.theme-dark .lp-footer-top {
  background: linear-gradient(180deg, var(--bg-soft) 0%, var(--surface) 100%);
}

html.theme-dark .lp-footer-col h4 {
  color: var(--ink);
}

html.theme-dark .lp-footer-col ul li a {
  color: var(--muted-color);
}

html.theme-dark .lp-footer-col ul li a:hover {
  color: var(--primary-color);
}

html.theme-dark .lp-footer-logo span {
  color: var(--ink);
}

html.theme-dark .lp-footer-brand p {
  color: var(--muted-color);
}

html.theme-dark .lp-footer-social a {
  background: var(--bg-soft);
  color: var(--ink);
  border-color: var(--line-soft);
}

html.theme-dark .lp-footer-social a:hover {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
  color: #fff;
  border-color: transparent;
}

html.theme-dark .lp-language-btn {
  background: var(--bg-soft);
  border-color: var(--line-soft);
  color: var(--ink);
}

html.theme-dark .lp-language-btn:hover {
  background: var(--surface);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

html.theme-dark .lp-footer-copyright {
  border-top-color: var(--line-soft);
}

html.theme-dark .lp-footer-copyright p {
  color: var(--muted-color);
}

html.theme-dark .newsletter-form input {
  background: var(--bg-soft);
  border-color: var(--line-soft);
  color: var(--ink);
}

html.theme-dark .newsletter-form input:focus {
  border-color: var(--primary-color);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Back to top button
  const backToTop = document.getElementById('lpBackToTop');
  
  if (backToTop) {
    // Show/hide based on scroll position
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) {
        backToTop.classList.add('visible');
      } else {
        backToTop.classList.remove('visible');
      }
    });
    
    // Scroll to top on click with smooth animation
    backToTop.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }
  
  // Language selector (placeholder functionality)
  const languageBtn = document.querySelector('.lp-language-btn');
  if (languageBtn) {
    languageBtn.addEventListener('click', () => {
      // Add language selection modal or dropdown here
      console.log('Language selector clicked');
      // You can implement a dropdown menu here
    });
  }
  
  // Newsletter form submission
  const newsletterForm = document.querySelector('.newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const email = newsletterForm.querySelector('input[type="email"]').value;
      
      // Add your newsletter subscription logic here
      console.log('Newsletter subscription:', email);
      
      // Show success message (you can replace this with a toast notification)
      alert('Thank you for subscribing to our newsletter!');
      newsletterForm.reset();
    });
  }
  
  // Track footer link clicks (analytics)
  const footerLinks = document.querySelectorAll('.lp-footer a');
  footerLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const linkText = e.target.textContent.trim();
      const linkHref = e.target.getAttribute('href');
      console.log('Footer link clicked:', linkText, linkHref);
      // Add your analytics tracking here
    });
  });
  
  // Social media hover effect
  const socialLinks = document.querySelectorAll('.lp-footer-social a');
  socialLinks.forEach(link => {
    link.addEventListener('mouseenter', () => {
      const icon = link.querySelector('i');
      icon.style.transform = 'scale(1.2) rotate(5deg)';
    });
    
    link.addEventListener('mouseleave', () => {
      const icon = link.querySelector('i');
      icon.style.transform = 'scale(1) rotate(0deg)';
    });
  });
});
</script>

</body>
</html>