/**
 * Armour Martial Arts – Gujju Karate Association
 * Frontend JavaScript Foundation
 * public/assets/js/main.js
 *
 * Provides:
 *  - Navbar scroll effect (dark blur on scroll)
 *  - IntersectionObserver for scroll animations (.animate-on-scroll)
 *  - Animated stat counters with easeOutQuart easing
 *  - Portal sidebar mobile drawer toggle
 *  - CSRF-aware AJAX fetch helper
 *  - Auto-dismiss for flash alerts
 *  - Gallery client-side category filter foundation
 */

(function () {
  'use strict';

  // ─── 1. Navbar Scroll Effect ──────────────────────────────────────────────
  const initNavbar = () => {
    const navbar = document.querySelector('.public-navbar');
    if (!navbar) return;

    const handleScroll = () => {
      if (window.scrollY > 30) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll(); // Initial check
  };

  // ─── 2. Scroll Animation Observer ─────────────────────────────────────────
  const initScrollAnimations = () => {
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    if (!animatedElements.length) return;

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('in-view');
              observer.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.15 }
      );

      animatedElements.forEach((el) => observer.observe(el));
    } else {
      // Fallback: immediately show elements
      animatedElements.forEach((el) => el.classList.add('in-view'));
    }
  };

  // ─── 3. Animated Stat Counters ────────────────────────────────────────────
  const easeOutQuart = (t) => 1 - --t * t * t * t;

  const animateCounter = (element, target, duration = 2000) => {
    let startTimestamp = null;
    const startVal = 0;

    const step = (timestamp) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      const easedProgress = easeOutQuart(progress);
      const current = Math.floor(easedProgress * (target - startVal) + startVal);

      element.textContent = current.toLocaleString();

      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        element.textContent = target.toLocaleString();
      }
    };

    window.requestAnimationFrame(step);
  };

  const initCounters = () => {
    const counterElements = document.querySelectorAll('[data-counter-target]');
    if (!counterElements.length) return;

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const el = entry.target;
              const target = parseInt(el.getAttribute('data-counter-target'), 10);
              if (!isNaN(target)) {
                animateCounter(el, target);
              }
              observer.unobserve(el);
            }
          });
        },
        { threshold: 0.25 }
      );

      counterElements.forEach((el) => observer.observe(el));
    } else {
      counterElements.forEach((el) => {
        el.textContent = el.getAttribute('data-counter-target');
      });
    }
  };

  // ─── 4. Portal Sidebar Mobile Toggle ──────────────────────────────────────
  const initSidebar = () => {
    const toggleBtn = document.querySelector('.btn-sidebar-toggle');
    const sidebar = document.querySelector('.app-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!toggleBtn || !sidebar) return;

    const openSidebar = () => {
      sidebar.classList.add('open');
      if (overlay) overlay.classList.add('visible');
      document.body.style.overflow = 'hidden';
    };

    const closeSidebar = () => {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('visible');
      document.body.style.overflow = '';
    };

    toggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar on ESC key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        closeSidebar();
      }
    });
  };

  // ─── 5. Flash Alert Auto-Dismiss ──────────────────────────────────────────
  const initFlashDismiss = () => {
    const alerts = document.querySelectorAll('.flash-container .alert');
    alerts.forEach((alert) => {
      setTimeout(() => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
          const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
          bsAlert.close();
        } else {
          alert.classList.remove('show');
          setTimeout(() => alert.remove(), 250);
        }
      }, 5000);
    });
  };

  // ─── 6. CSRF-Aware Fetch Utility ──────────────────────────────────────────
  window.armourFetch = async (url, options = {}) => {
    const csrfToken =
      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
      document.querySelector('input[name="_csrf_token"]')?.value ||
      '';

    const headers = {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': csrfToken,
      ...(options.headers || {}),
    };

    return fetch(url, { ...options, headers });
  };

  // ─── 7. Initialize on DOMContentLoaded ────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initScrollAnimations();
    initCounters();
    initSidebar();
    initFlashDismiss();
  });
})();
