/**
 * Theme Toggle Component - Web Component
 * Switches between light and dark themes
 */

import { themeStore, toggleTheme } from '@/lib/state/theme.store';
import moonIcon from '@/assets/icons/moon.svg?raw';
import sunIcon from '@/assets/icons/sun.svg?raw';

const template = document.createElement('template');
template.innerHTML = `
  <style>
    :host {
      display: inline-block;
    }
    
    .theme-toggle {
      width: 40px;
      height: 40px;
      border: 1px solid var(--border-primary);
      border-radius: var(--radius-md);
      background: var(--bg-tertiary);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all var(--transition-base);
      position: relative;
      overflow: hidden;
    }
    
    .theme-toggle:hover {
      background: var(--bg-secondary);
      border-color: var(--accent-primary);
      box-shadow: var(--glow-primary);
      transform: translateY(-2px);
    }
    
    .theme-toggle:active {
      transform: translateY(0);
    }
    
    .icon {
      width: 20px;
      height: 20px;
      color: var(--accent-primary);
      transition: all var(--transition-base);
      position: absolute;
    }
    
    .icon.moon {
      opacity: 1;
      transform: rotate(0deg) scale(1);
    }
    
    .icon.sun {
      opacity: 0;
      transform: rotate(90deg) scale(0.5);
    }
    
    :host([theme="light"]) .icon.moon {
      opacity: 0;
      transform: rotate(-90deg) scale(0.5);
    }
    
    :host([theme="light"]) .icon.sun {
      opacity: 1;
      transform: rotate(0deg) scale(1);
    }
  </style>
  
  <button class="theme-toggle" aria-label="Toggle theme" title="Switch theme">
    <span class="icon moon"></span>
    <span class="icon sun"></span>
  </button>
`;

export class ThemeToggle extends HTMLElement {
  private button: HTMLButtonElement | null = null;
  private moonIconEl: HTMLElement | null = null;
  private sunIconEl: HTMLElement | null = null;

  constructor() {
    super();
    this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {
    if (!this.shadowRoot) return;
    
    this.shadowRoot.appendChild(template.content.cloneNode(true));
    
    this.button = this.shadowRoot.querySelector('.theme-toggle');
    this.moonIconEl = this.shadowRoot.querySelector('.icon.moon');
    this.sunIconEl = this.shadowRoot.querySelector('.icon.sun');
    
    // Set initial icons
    if (this.moonIconEl) this.moonIconEl.innerHTML = moonIcon;
    if (this.sunIconEl) this.sunIconEl.innerHTML = sunIcon;
    
    // Set initial theme
    this.updateTheme(themeStore.get());
    
    // Subscribe to theme changes
    themeStore.subscribe((theme) => {
      this.updateTheme(theme);
    });
    
    // Handle click
    if (this.button) {
      this.button.addEventListener('click', () => {
        toggleTheme();
      });
    }
  }

  private updateTheme(theme: 'light' | 'dark') {
    this.setAttribute('theme', theme);
  }
}

// Register custom element
if (!customElements.get('theme-toggle')) {
  customElements.define('theme-toggle', ThemeToggle);
}
