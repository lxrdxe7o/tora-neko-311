/**
 * Button Component - Web Component
 * Variants: primary, secondary, ghost
 */

const template = document.createElement('template');
template.innerHTML = `
  <style>
    :host {
      display: inline-block;
    }
    
    .button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: var(--spacing-sm);
      padding: var(--spacing-md) var(--spacing-xl);
      font-family: var(--font-display);
      font-size: var(--text-sm);
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      border: 2px solid;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: all var(--transition-base);
      position: relative;
      overflow: hidden;
      background: transparent;
    }
    
    .button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
      transition: left 0.5s;
    }
    
    .button:hover::before {
      left: 100%;
    }
    
    .button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }
    
    /* Primary variant */
    .button.primary {
      background: var(--gradient-primary);
      border-color: var(--accent-primary);
      color: var(--accent-primary);
    }
    
    .button.primary:hover:not(:disabled) {
      box-shadow: var(--glow-primary);
      transform: translateY(-2px);
    }
    
    .button.primary:active:not(:disabled) {
      transform: translateY(0);
    }
    
    /* Secondary variant */
    .button.secondary {
      background: transparent;
      border-color: var(--accent-secondary);
      color: var(--accent-secondary);
    }
    
    .button.secondary:hover:not(:disabled) {
      background: rgba(183, 148, 246, 0.1);
      box-shadow: var(--glow-secondary);
    }
    
    /* Ghost variant */
    .button.ghost {
      background: transparent;
      border-color: var(--border-primary);
      color: var(--text-primary);
      border-width: 1px;
    }
    
    .button.ghost:hover:not(:disabled) {
      border-color: var(--accent-primary);
      color: var(--accent-primary);
    }
    
    /* Icon */
    .icon {
      width: 16px;
      height: 16px;
      display: inline-flex;
    }
  </style>
  
  <button class="button" type="button">
    <slot name="icon-left"></slot>
    <slot></slot>
    <slot name="icon-right"></slot>
  </button>
`;

export class Button extends HTMLElement {
  private button: HTMLButtonElement | null = null;

  static get observedAttributes() {
    return ['variant', 'disabled', 'aria-label'];
  }

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    if (!this.shadowRoot) return;
    
    this.shadowRoot.appendChild(template.content.cloneNode(true));
    this.button = this.shadowRoot.querySelector('.button');
    
    this.updateVariant();
    this.updateDisabled();
    this.updateAriaLabel();
    
    // Support keyboard navigation
    this.button?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.button?.click();
      }
    });
  }

  attributeChangedCallback(name: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) return;
    
    if (name === 'variant') {
      this.updateVariant();
    } else if (name === 'disabled') {
      this.updateDisabled();
    } else if (name === 'aria-label') {
      this.updateAriaLabel();
    }
  }

  private updateVariant() {
    if (!this.button) return;
    
    const variant = this.getAttribute('variant') || 'primary';
    this.button.className = `button ${variant}`;
  }

  private updateDisabled() {
    if (!this.button) return;
    
    const disabled = this.hasAttribute('disabled');
    this.button.disabled = disabled;
    this.button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
  }

  private updateAriaLabel() {
    if (!this.button) return;
    
    const label = this.getAttribute('aria-label');
    if (label) {
      this.button.setAttribute('aria-label', label);
    }
  }
}

if (!customElements.get('quantum-button')) {
  customElements.define('quantum-button', Button);
}
