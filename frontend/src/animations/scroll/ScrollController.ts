/**
 * Scroll Controller - GSAP scroll-triggered animations
 */

import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export function initScrollAnimations() {
  // Check for reduced motion preference
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (prefersReducedMotion) {
    console.log('🎬 Scroll animations disabled (reduced motion preference)');
    return;
  }

  // Fade in sections on scroll
  gsap.utils.toArray('.fade-in-section').forEach((section: any) => {
    gsap.from(section, {
      opacity: 0,
      y: 50,
      duration: 0.8,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: section,
        start: 'top 80%',
        end: 'bottom 20%',
        toggleActions: 'play none none reverse'
      }
    });
  });

  // Stagger animation for card grids
  gsap.utils.toArray('.stagger-container').forEach((container: any) => {
    const cards = container.querySelectorAll('quantum-card, .card-item');
    
    gsap.from(cards, {
      opacity: 0,
      y: 60,
      duration: 0.6,
      stagger: 0.15,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: container,
        start: 'top 75%',
        toggleActions: 'play none none reverse'
      }
    });
  });

  // Parallax effect for hero section
  const hero = document.querySelector('.hero');
  if (hero) {
    gsap.to(hero, {
      yPercent: 20,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: 1
      }
    });
  }

  // Scale up on scroll for stats
  gsap.utils.toArray('.stat-item').forEach((stat: any) => {
    gsap.from(stat, {
      scale: 0.8,
      opacity: 0,
      duration: 0.6,
      ease: 'back.out(1.7)',
      scrollTrigger: {
        trigger: stat,
        start: 'top 85%',
        toggleActions: 'play none none reverse'
      }
    });
  });

  // Number counter animation
  gsap.utils.toArray('.counter').forEach((counter: any) => {
    const target = parseFloat(counter.getAttribute('data-target') || '0');
    const suffix = counter.getAttribute('data-suffix') || '';
    
    gsap.from(counter, {
      textContent: 0,
      duration: 2,
      ease: 'power1.inOut',
      snap: { textContent: 1 },
      scrollTrigger: {
        trigger: counter,
        start: 'top 80%',
        toggleActions: 'play none none none'
      },
      onUpdate: function() {
        const current = parseFloat(counter.textContent);
        counter.textContent = Math.ceil(current) + suffix;
      }
    });
  });

  // Slide in from left/right
  gsap.utils.toArray('.slide-left').forEach((element: any) => {
    gsap.from(element, {
      x: -100,
      opacity: 0,
      duration: 0.8,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: element,
        start: 'top 80%',
        toggleActions: 'play none none reverse'
      }
    });
  });

  gsap.utils.toArray('.slide-right').forEach((element: any) => {
    gsap.from(element, {
      x: 100,
      opacity: 0,
      duration: 0.8,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: element,
        start: 'top 80%',
        toggleActions: 'play none none reverse'
      }
    });
  });

  // Rotate in animation for icons
  gsap.utils.toArray('.rotate-in').forEach((element: any) => {
    gsap.from(element, {
      rotation: 180,
      opacity: 0,
      duration: 0.8,
      ease: 'back.out(2)',
      scrollTrigger: {
        trigger: element,
        start: 'top 80%',
        toggleActions: 'play none none reverse'
      }
    });
  });

  console.log('🎬 Scroll animations initialized');
}

export function destroyScrollAnimations() {
  ScrollTrigger.getAll().forEach(trigger => trigger.kill());
  console.log('🎬 Scroll animations destroyed');
}
