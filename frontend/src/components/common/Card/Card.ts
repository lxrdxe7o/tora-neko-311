/**
 * Card Component - Web Component
 * Reusable card container with hover effects
 */

const template = document.createElement('template');
template.innerHTML = `
  <style>
    :host {
      display: block;
    }
    
    .card {
      background: var(--bg-panel);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-lg);
      padding: var(--spacing-lg);
      transition: all var(--transition-base);
      height: 100%;
    }
    
    .card:hover {
      border-color: var(--accent-primary);
      box-shadow: var(--shadow-lg);
      transform: translateY(-4px);
    }
    
    .card-icon {
      width: 48px;
      height: 48px;
      margin-bottom: var(--spacing-md);
      color: var(--accent-primary);
    }
    
    .card-title {
      font-family: var(--font-display);
      font-size: var(--text-xl);
      color: var(--text-primary);
      margin-bottom: var(--spacing-sm);
      letter-spacing: 0.05em;
    }
    
    .card-summary {
      font-size: var(--text-base);
      color: var(--text-secondary);
      margin-bottom: var(--spacing-md);
    }
    
    .card-content {
      font-size: var(--text-sm);
      color: var(--text-secondary);
      line-height: 1.7;
    }
    
    .card-footer {
      margin-top: var(--spacing-lg);
      padding-top: var(--spacing-md);
      border-top: 1px solid var(--border-subtle);
    }
  </style>
  
  <article class="card" role="article">
    <div class="card-icon" aria-hidden="true">
      <slot name="icon"></slot>
    </div>
    <h3 class="card-title">
      <slot name="title"></slot>
    </h3>
    <p class="card-summary">
      <slot name="summary"></slot>
    </p>
    <div class="card-content">
      <slot></slot>
    </div>
    <div class="card-footer">
      <slot name="footer"></slot>
    </div>
  </article>
`;

export class Card extends HTMLElement {
  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    if (!this.shadowRoot) return;
    this.shadowRoot.appendChild(template.content.cloneNode(true));
  }
}

if (!customElements.get('quantum-card')) {
  customElements.define('quantum-card', Card);
}
