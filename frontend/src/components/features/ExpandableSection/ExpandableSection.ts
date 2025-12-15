/**
 * Expandable Section Component
 * Scannable header + expandable detailed content
 */

const template = document.createElement('template');
template.innerHTML = `
  <style>
    :host {
      display: block;
      margin-bottom: var(--spacing-lg);
    }
    
    .section {
      background: var(--bg-panel);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-lg);
      overflow: hidden;
      transition: all var(--transition-base);
    }
    
    .section:hover {
      border-color: var(--accent-primary);
    }
    
    .header {
      padding: var(--spacing-lg);
      cursor: pointer;
      display: flex;
      align-items: flex-start;
      gap: var(--spacing-md);
      transition: background var(--transition-base);
    }
    
    .header:hover {
      background: rgba(110, 181, 192, 0.05);
    }
    
    .icon {
      width: 48px;
      height: 48px;
      flex-shrink: 0;
      color: var(--accent-primary);
    }
    
    .header-content {
      flex: 1;
    }
    
    .title {
      font-family: var(--font-display);
      font-size: var(--text-xl);
      color: var(--text-primary);
      margin-bottom: var(--spacing-sm);
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }
    
    .summary {
      font-size: var(--text-base);
      color: var(--text-secondary);
      margin-bottom: var(--spacing-sm);
    }
    
    .badges {
      display: flex;
      gap: var(--spacing-sm);
      flex-wrap: wrap;
    }
    
    .badge {
      font-size: var(--text-xs);
      padding: var(--spacing-xs) var(--spacing-sm);
      background: rgba(110, 181, 192, 0.1);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-sm);
      color: var(--accent-primary);
      font-family: var(--font-mono);
    }
    
    .expand-icon {
      width: 24px;
      height: 24px;
      flex-shrink: 0;
      color: var(--accent-primary);
      transition: transform var(--transition-base);
      font-size: var(--text-xl);
      font-weight: 300;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .section.expanded .expand-icon {
      transform: rotate(45deg);
    }
    
    .content {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.5s ease-out, opacity 0.3s ease-out;
      opacity: 0;
    }
    
    .section.expanded .content {
      max-height: 3000px;
      opacity: 1;
      transition: max-height 0.5s ease-in, opacity 0.5s ease-in 0.1s;
    }
    
    .content-inner {
      padding: var(--spacing-lg);
      border-top: 1px solid var(--border-subtle);
    }
    
    .subsection {
      margin-top: var(--spacing-lg);
    }
    
    .subsection-title {
      font-family: var(--font-display);
      font-size: var(--text-lg);
      color: var(--accent-primary);
      margin-bottom: var(--spacing-sm);
      letter-spacing: 0.05em;
    }
    
    .subsection-content {
      font-size: var(--text-sm);
      color: var(--text-secondary);
      line-height: 1.8;
    }
    
    .subsection-content ul {
      list-style: none;
      padding-left: 0;
    }
    
    .subsection-content li {
      padding-left: var(--spacing-lg);
      position: relative;
      margin-bottom: var(--spacing-xs);
    }
    
    .subsection-content li::before {
      content: '▸';
      position: absolute;
      left: 0;
      color: var(--accent-primary);
    }
    
    .actions {
      margin-top: var(--spacing-lg);
      padding-top: var(--spacing-lg);
      border-top: 1px solid var(--border-subtle);
      display: flex;
      gap: var(--spacing-md);
      flex-wrap: wrap;
    }
    
    .action-link {
      font-size: var(--text-sm);
      color: var(--accent-primary);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: var(--spacing-xs);
      transition: all var(--transition-fast);
      padding: var(--spacing-sm) var(--spacing-md);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-sm);
    }
    
    .action-link:hover {
      background: rgba(110, 181, 192, 0.1);
      border-color: var(--accent-primary);
      transform: translateX(2px);
    }
    
    .code-block {
      background: var(--bg-tertiary);
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-sm);
      padding: var(--spacing-md);
      font-family: var(--font-mono);
      font-size: var(--text-xs);
      color: var(--text-secondary);
      overflow-x: auto;
      margin: var(--spacing-md) 0;
    }
  </style>
  
  <div class="section">
    <div class="header">
      <div class="icon">
        <slot name="icon"></slot>
      </div>
      <div class="header-content">
        <h3 class="title">
          <slot name="title"></slot>
          <div class="expand-icon">+</div>
        </h3>
        <div class="summary">
          <slot name="summary"></slot>
        </div>
        <div class="badges">
          <slot name="badges"></slot>
        </div>
      </div>
    </div>
    
    <div class="content">
      <div class="content-inner">
        <slot></slot>
      </div>
    </div>
  </div>
`;

export class ExpandableSection extends HTMLElement {
  private section: HTMLElement | null = null;
  private header: HTMLElement | null = null;
  private isExpanded = false;

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    if (!this.shadowRoot) return;
    
    this.shadowRoot.appendChild(template.content.cloneNode(true));
    
    this.section = this.shadowRoot.querySelector('.section');
    this.header = this.shadowRoot.querySelector('.header');
    
    // Check if should be expanded by default (from hash)
    const id = this.getAttribute('id');
    if (id && window.location.hash === `#${id}`) {
      this.expand();
    }
    
    this.header?.addEventListener('click', () => this.toggle());
  }

  private toggle() {
    if (this.isExpanded) {
      this.collapse();
    } else {
      this.expand();
    }
  }

  private expand() {
    this.isExpanded = true;
    this.section?.classList.add('expanded');
    this.dispatchEvent(new CustomEvent('expand'));
  }

  private collapse() {
    this.isExpanded = false;
    this.section?.classList.remove('expanded');
    this.dispatchEvent(new CustomEvent('collapse'));
  }
}

if (!customElements.get('expandable-section')) {
  customElements.define('expandable-section', ExpandableSection);
}
