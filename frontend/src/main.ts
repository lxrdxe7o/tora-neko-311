/**
 * Main Entry Point - Quantum Airways Frontend
 */

import '@/styles/main.css';
import { initTheme } from '@/lib/state/theme.store';
import { initGeometricBackground } from '@/animations/background/GeometricBackground';
import { initScrollAnimations } from '@/animations/scroll/ScrollController';

// Import Web Components
import '@/components/common/ThemeToggle/ThemeToggle';
import '@/components/common/Button/Button';
import '@/components/common/Card/Card';
import '@/components/navigation/Header/Header';
import '@/components/navigation/MobileNav/MobileNav';
import '@/components/features/ExpandableSection/ExpandableSection';
import '@/components/features/TabNavigation/TabNavigation';

// Initialize theme system on app start
initTheme();

// Initialize geometric background
let background: ReturnType<typeof initGeometricBackground> | null = null;

// Check for reduced motion preference
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!prefersReducedMotion) {
  background = initGeometricBackground();
}

// Connect header menu toggle to mobile nav drawer
document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('quantum-header');
  const mobileNav = document.querySelector('mobile-nav');
  
  if (header && mobileNav) {
    header.addEventListener('menu-toggle', () => {
      (mobileNav as any).toggle();
    });
  }
  
  // Initialize scroll animations
  initScrollAnimations();
});

// Log to console
console.log('🚀 Quantum Airways - Frontend initialized');
console.log('📱 Theme system active');
console.log('🎨 Geometric background:', prefersReducedMotion ? 'disabled (reduced motion)' : 'active');
