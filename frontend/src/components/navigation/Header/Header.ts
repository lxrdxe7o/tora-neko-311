/**
 * Header Component - Main navigation header
 */

import menuIcon from '@/assets/icons/menu.svg?raw';

const template = document.createElement('template');
template.innerHTML = `
  <style>
    :host {
      display: block;
    }
    
    .header {
      position: sticky;
      top: 0;
      background: var(--gradient-panel);
      border-bottom: 1px solid var(--border-primary);
      box-shadow: var(--shadow-md);
      z-index: var(--z-header);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    
    .header-content {
      max-width: var(--container-2xl);
      margin: 0 auto;
      padding: var(--spacing-md) var(--spacing-lg);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: var(--spacing-lg);
    }
    
    .logo {
      font-family: var(--font-display);
      font-size: var(--text-xl);
      font-weight: 900;
      color: var(--accent-primary);
      text-shadow: var(--glow-primary);
      letter-spacing: 0.1em;
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
      text-decoration: none;
      transition: all var(--transition-base);
    }
    
    .logo:hover {
      transform: scale(1.05);
    }
    
    .logo-icon {
      color: var(--accent-secondary);
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    
    .nav-desktop {
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
    }
    
    .nav-link {
      font-family: var(--font-display);
      font-size: var(--text-sm);
      color: var(--text-secondary);
      text-decoration: none;
      padding: var(--spacing-sm) var(--spacing-md);
      border-radius: var(--radius-sm);
      transition: all var(--transition-base);
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
    
    .nav-link:hover {
      color: var(--accent-primary);
      background: rgba(110, 181, 192, 0.1);
    }
    
    .nav-link.active {
      color: var(--accent-primary);
      border-bottom: 2px solid var(--accent-primary);
    }
    
    .actions {
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
    }
    
    .mobile-toggle {
      display: none;
      width: 40px;
      height: 40px;
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-sm);
      background: var(--bg-tertiary);
      cursor: pointer;
      align-items: center;
      justify-content: center;
      transition: all var(--transition-base);
    }
    
    .mobile-toggle:hover {
      border-color: var(--accent-primary);
      background: var(--bg-secondary);
    }
    
    .mobile-toggle svg {
      width: 20px;
      height: 20px;
      color: var(--accent-primary);
    }
    
    @media (max-width: 768px) {
      .nav-desktop {
        display: none;
      }
      
      .mobile-toggle {
        display: flex;
      }
    }
  </style>
  
  <header class="header">
    <div class="header-content">
      <a href="/dist/" class="logo">
        <span class="logo-icon">◆</span>
        <span>QUANTUM <span style="color: var(--text-primary)">AIRWAYS</span></span>
      </a>
      
      <nav class="nav-desktop">
        <a href="/dist/" class="nav-link">Home</a>
        <a href="/dist/features.html" class="nav-link">Features</a>
        <a href="/dist/booking.html" class="nav-link">Book Now</a>
        <a href="/dist/how-it-works.html" class="nav-link">How It Works</a>
        <a href="/dist/education.html" class="nav-link">Architecture</a>
      </nav>
      
      <div class="actions">
        <slot name="theme-toggle"></slot>
        <button class="mobile-toggle" aria-label="Toggle menu">
          ${menuIcon}
        </button>
      </div>
    </div>
  </header>
`;

export class Header extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    if (!this.shadowRoot) return;
    
    this.shadowRoot.appendChild(template.content.cloneNode(true));
    
    // Set active nav link based on current page
    this.setActiveLink();
    
    // Mobile menu toggle
    const toggle = this.shadowRoot.querySelector('.mobile-toggle');
    if (toggle) {
      toggle.addEventListener('click', () => {
        this.dispatchEvent(new CustomEvent('menu-toggle'));
      });
    }
  }

  private setActiveLink() {
    const currentPath = window.location.pathname;
    const links = this.shadowRoot?.querySelectorAll('.nav-link');
    
    links?.forEach(link => {
      const href = link.getAttribute('href');
      if (!href) return;
      
      // Clean paths for comparison
      const cleanHref = href.replace('/dist/', '').replace('.html', '');
      const cleanPath = currentPath.replace('/dist/', '').replace('.html', '');
      
      const isHome = cleanHref === '' || cleanHref === 'index';
      const isCurrentHome = cleanPath === '' || cleanPath === 'index';
      
      if (isHome && isCurrentHome) {
        link.classList.add('active');
      } else if (!isHome && cleanPath.includes(cleanHref) && cleanHref !== '') {
        link.classList.add('active');
      }
    });
  }
}

if (!customElements.get('quantum-header')) {
  customElements.define('quantum-header', Header);
}
