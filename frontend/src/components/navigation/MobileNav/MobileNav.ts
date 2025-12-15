/**
 * MobileNav Component - Slide-in mobile navigation drawer
 */

const template = document.createElement('template');
template.innerHTML = `
  <style>
    :host {
      display: block;
    }
    
    .backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      z-index: var(--z-modal);
      opacity: 0;
      visibility: hidden;
      transition: all var(--transition-base);
    }
    
    .backdrop.open {
      opacity: 1;
      visibility: visible;
    }
    
    .drawer {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: min(320px, 80vw);
      background: var(--bg-primary);
      border-left: 1px solid var(--border-primary);
      box-shadow: var(--shadow-lg);
      z-index: var(--z-modal);
      transform: translateX(100%);
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }
    
    .drawer.open {
      transform: translateX(0);
    }
    
    .drawer-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: var(--spacing-lg);
      border-bottom: 1px solid var(--border-primary);
    }
    
    .drawer-title {
      font-family: var(--font-display);
      font-size: var(--text-lg);
      font-weight: 700;
      color: var(--accent-primary);
      text-shadow: var(--glow-primary);
      letter-spacing: 0.1em;
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }
    
    .close-btn {
      width: 40px;
      height: 40px;
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-sm);
      background: var(--bg-tertiary);
      color: var(--text-primary);
      font-size: var(--text-xl);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all var(--transition-base);
    }
    
    .close-btn:hover {
      border-color: var(--accent-primary);
      color: var(--accent-primary);
      background: var(--bg-secondary);
    }
    
    .drawer-nav {
      flex: 1;
      padding: var(--spacing-lg);
      display: flex;
      flex-direction: column;
      gap: var(--spacing-lg);
    }
    
    .nav-link {
      font-family: var(--font-display);
      font-size: var(--text-base);
      color: var(--text-secondary);
      text-decoration: none;
      padding: var(--spacing-md) var(--spacing-lg);
      border-radius: var(--radius-md);
      transition: all var(--transition-base);
      letter-spacing: 0.05em;
      text-transform: uppercase;
      border: 1px solid transparent;
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
    }
    
    .nav-link:hover {
      color: var(--accent-primary);
      background: rgba(110, 181, 192, 0.1);
      border-color: var(--accent-primary);
    }
    
    .nav-link.active {
      color: var(--accent-primary);
      background: rgba(110, 181, 192, 0.15);
      border-color: var(--accent-primary);
    }
    
    .nav-link-icon {
      font-size: var(--text-lg);
    }
    
    .drawer-footer {
      padding: var(--spacing-lg);
      border-top: 1px solid var(--border-primary);
    }
    
    .theme-toggle-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: var(--spacing-md);
      border-radius: var(--radius-md);
      background: var(--bg-secondary);
    }
    
    .theme-label {
      font-family: var(--font-display);
      font-size: var(--text-sm);
      color: var(--text-secondary);
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }
  </style>
  
  <div class="backdrop" part="backdrop" role="presentation"></div>
  
  <nav class="drawer" part="drawer" role="navigation" aria-label="Mobile navigation">
    <div class="drawer-header">
      <div class="drawer-title">
        <span>◆</span>
        <span>Menu</span>
      </div>
      <button class="close-btn" aria-label="Close navigation menu" tabindex="0">✕</button>
    </div>
    
    <div class="drawer-nav">
      <a href="/dist/" class="nav-link">
        <span class="nav-link-icon">⌂</span>
        <span>Home</span>
      </a>
      <a href="/dist/features.html" class="nav-link">
        <span class="nav-link-icon">◆</span>
        <span>Features</span>
      </a>
      <a href="/dist/booking.html" class="nav-link">
        <span class="nav-link-icon">✈</span>
        <span>Book Now</span>
      </a>
      <a href="/dist/how-it-works.html" class="nav-link">
        <span class="nav-link-icon">⚙</span>
        <span>How It Works</span>
      </a>
      <a href="/dist/education.html" class="nav-link">
        <span class="nav-link-icon">⚛</span>
        <span>Architecture</span>
      </a>
    </div>
    
    <div class="drawer-footer">
      <div class="theme-toggle-container">
        <span class="theme-label">Theme</span>
        <slot name="theme-toggle"></slot>
      </div>
    </div>
  </nav>
`;

export class MobileNav extends HTMLElement {
  private isOpen = false;

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    if (!this.shadowRoot) return;
    
    this.shadowRoot.appendChild(template.content.cloneNode(true));
    
    // Set active nav link
    this.setActiveLink();
    
    // Close button
    const closeBtn = this.shadowRoot.querySelector('.close-btn');
    closeBtn?.addEventListener('click', () => this.close());
    
    // Backdrop click
    const backdrop = this.shadowRoot.querySelector('.backdrop');
    backdrop?.addEventListener('click', () => this.close());
    
    // Nav link clicks
    const navLinks = this.shadowRoot.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        // Close drawer on navigation
        setTimeout(() => this.close(), 150);
      });
    });
    
    // ESC key to close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isOpen) {
        this.close();
      }
    });
    
    // Focus trap: Tab key handling
    this.addEventListener('keydown', (e) => {
      if (e.key === 'Tab' && this.isOpen) {
        this.handleTabKey(e);
      }
    });
    
    // Prevent body scroll when open
    this.addEventListener('menu-opened', () => {
      document.body.style.overflow = 'hidden';
    });
    
    this.addEventListener('menu-closed', () => {
      document.body.style.overflow = '';
    });
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

  private handleTabKey(e: KeyboardEvent) {
    if (!this.shadowRoot) return;
    
    const focusableElements = this.shadowRoot.querySelectorAll(
      'button, a, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0] as HTMLElement;
    const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;
    
    if (e.shiftKey) {
      // Shift + Tab
      if (document.activeElement === firstElement || !this.shadowRoot.contains(document.activeElement as Node)) {
        e.preventDefault();
        lastElement?.focus();
      }
    } else {
      // Tab
      if (document.activeElement === lastElement) {
        e.preventDefault();
        firstElement?.focus();
      }
    }
  }

  public open() {
    if (!this.shadowRoot) return;
    
    const backdrop = this.shadowRoot.querySelector('.backdrop');
    const drawer = this.shadowRoot.querySelector('.drawer') as HTMLElement;
    
    backdrop?.classList.add('open');
    drawer?.classList.add('open');
    
    // Focus the close button when opened
    const closeBtn = this.shadowRoot.querySelector('.close-btn') as HTMLElement;
    setTimeout(() => closeBtn?.focus(), 100);
    
    this.isOpen = true;
    this.setAttribute('aria-hidden', 'false');
    this.dispatchEvent(new CustomEvent('menu-opened'));
  }

  public close() {
    if (!this.shadowRoot) return;
    
    const backdrop = this.shadowRoot.querySelector('.backdrop');
    const drawer = this.shadowRoot.querySelector('.drawer');
    
    backdrop?.classList.remove('open');
    drawer?.classList.remove('open');
    
    this.isOpen = false;
    this.setAttribute('aria-hidden', 'true');
    this.dispatchEvent(new CustomEvent('menu-closed'));
  }

  public toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }
}

if (!customElements.get('mobile-nav')) {
  customElements.define('mobile-nav', MobileNav);
}
