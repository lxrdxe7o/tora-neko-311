# Quantum Airways - Frontend

Modern, modular frontend built with Vite + TypeScript + Web Components.

## 🎨 Features

- **✓ Theme System** - Auto-detecting dark/light mode with manual toggle
- **✓ Geometric Background** - Animated canvas with particles and shapes
- **✓ Web Components** - Modular, reusable UI components
- **✓ TypeScript** - Type-safe development
- **✓ Responsive Design** - Mobile-first approach
- **✓ Minimalistic Cyberpunk Design** - Muted colors, subtle glows

## 🚀 Quick Start

### Development
```bash
npm run dev
# Visit http://localhost:3000/dist/
```

### Production Build
```bash
npm run build
# Output: ../public/dist/
# Flask serves from http://localhost:5000/dist/
```

### Type Check
```bash
npm run type-check
```

## 📁 Project Structure

```
src/
├── main.ts                    # Entry point
├── styles/                    # CSS architecture
│   ├── base/                  # Reset, typography, utilities
│   ├── themes/                # Dark/light theme variables
│   └── main.css               # Main stylesheet
├── components/                # Web Components
│   ├── common/                # Reusable UI (Button, Card, etc.)
│   └── navigation/            # Header, NavMenu
├── animations/                # Animation systems
│   └── background/            # Geometric background
├── lib/                       # Core utilities
│   ├── state/                 # State management (theme store)
│   └── utils/                 # Helper functions
├── assets/                    # Static assets
│   └── icons/                 # Custom geometric SVG icons
└── types/                     # TypeScript definitions
```

## 🎨 Design System

### Color Palette

**Dark Mode (Default)**
- Primary: `#6eb5c0` (Muted cyan)
- Secondary: `#b794f6` (Muted purple)
- Success: `#7ed9a2` (Soft mint)
- Background: `#0d0d12` (Almost black)

**Light Mode**
- Primary: `#3b82c6` (Professional blue)
- Secondary: `#8b5cf6` (Professional purple)
- Background: `#fafafa` (Off-white)

### Typography
- Display: Orbitron (headings)
- Mono: JetBrains Mono (body)
- Scale: 12px → 48px

### Spacing
- xs: 4px
- sm: 8px
- md: 16px
- lg: 24px
- xl: 32px
- 2xl: 48px
- 3xl: 64px

## 🧩 Components

### Web Components
- `<theme-toggle>` - Dark/light mode switch
- `<quantum-button>` - Button (primary, secondary, ghost variants)
- `<quantum-card>` - Feature card with slots
- `<quantum-header>` - Navigation header

### Usage Example
```html
<quantum-card>
  <img slot="icon" src="icon.svg">
  <span slot="title">Title</span>
  <span slot="summary">Summary text</span>
  <p>Main content...</p>
  <quantum-button slot="footer">Action</quantum-button>
</quantum-card>
```

## 🎭 Animations

### Geometric Background
- 50 particles (25 on mobile) with connecting lines
- 8 floating shapes (hexagons, triangles, lines)
- Mouse parallax effect (desktop only)
- Theme-aware colors
- Respects `prefers-reduced-motion`

### Configuration
See `src/animations/background/GeometricBackground.ts`

## 🔧 Configuration

### Vite Config
- Multi-page setup (index, booking, features, etc.)
- Build output: `../public/dist/`
- Dev server port: 3000
- API proxy: `/api` → `http://localhost:5000`

### Path Aliases
- `@/` → `src/`
- `@components/` → `src/components/`
- `@lib/` → `src/lib/`
- `@styles/` → `src/styles/`
- `@assets/` → `src/assets/`
- `@animations/` → `src/animations/`

## 📦 Dependencies

### Production
- `gsap` - Animation library
- `lenis` - Smooth scroll
- `nanostores` - State management
- `date-fns` - Date formatting
- `clsx` - Class name utility

### Development
- `vite` - Build tool
- `typescript` - Type safety
- `postcss` - CSS processing
- `autoprefixer` - Vendor prefixes

## 🌐 Browser Support

- Modern browsers (ES2020+)
- Chrome, Firefox, Safari, Edge (latest 2 versions)
- Mobile: iOS Safari, Chrome Android

## ♿ Accessibility

- WCAG AA color contrast
- Keyboard navigation support
- Focus-visible indicators
- ARIA labels on interactive elements
- `prefers-reduced-motion` support

## 📝 TODO

- [ ] Add remaining illustrations (4 SVGs)
- [ ] Implement GSAP scroll animations
- [ ] Build features page with expandable sections
- [ ] Migrate booking functionality
- [ ] Build how-it-works page
- [ ] Build education page with tabs
- [ ] Add micro-interactions
- [ ] Accessibility audit

## 🔗 Integration with Flask

The frontend builds to `../public/dist/` which Flask serves automatically.

Flask is configured with:
```python
app = Flask(__name__, static_folder='public', static_url_path='')
```

Access pages at:
- http://localhost:5000/dist/
- http://localhost:5000/dist/booking.html
- etc.

## 📄 License

MIT
