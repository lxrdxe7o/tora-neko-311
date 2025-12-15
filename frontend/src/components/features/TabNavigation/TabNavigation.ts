/**
 * Tab Navigation Component
 * URL-based tab switching with hash routing
 */

const template = document.createElement('template');
template.innerHTML = `
  <style>
    :host {
      display: block;
    }
    
    .tabs {
      display: flex;
      gap: var(--spacing-sm);
      border-bottom: 2px solid var(--border-primary);
      margin-bottom: var(--spacing-xl);
      overflow-x: auto;
    }
    
    .tab {
      padding: var(--spacing-md) var(--spacing-lg);
      font-family: var(--font-display);
      font-size: var(--text-sm);
      color: var(--text-secondary);
      text-decoration: none;
      border: none;
      background: transparent;
      cursor: pointer;
      transition: all var(--transition-base);
      white-space: nowrap;
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
      letter-spacing: 0.05em;
    }
    
    .tab:hover {
      color: var(--accent-primary);
      background: rgba(110, 181, 192, 0.05);
    }
    
    .tab.active {
      color: var(--accent-primary);
      border-bottom-color: var(--accent-primary);
    }
  </style>
  
  <div class="tabs">
    <slot></slot>
  </div>
`;

export class TabNavigation extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    if (!this.shadowRoot) return;
    
    this.shadowRoot.appendChild(template.content.cloneNode(true));
    
    // Update active tab based on hash
    this.updateActiveTab();
    
    // Listen to hash changes
    window.addEventListener('hashchange', () => this.updateActiveTab());
  }

  private updateActiveTab() {
    const hash = window.location.hash.slice(1) || this.getAttribute('default') || '';
    const tabs = this.querySelectorAll('[data-tab]');
    
    tabs.forEach(tab => {
      if (tab.getAttribute('data-tab') === hash) {
        tab.classList.add('active');
      } else {
        tab.classList.remove('active');
      }
    });
    
    // Dispatch event for content update
    this.dispatchEvent(new CustomEvent('tab-change', { detail: { tab: hash } }));
  }
}

if (!customElements.get('tab-navigation')) {
  customElements.define('tab-navigation', TabNavigation);
}
