/**
 * Geometric Background - Animated Canvas Background
 * Features: Particle network, floating geometric shapes, mouse parallax
 */

interface Particle {
  x: number;
  y: number;
  vx: number;
  vy: number;
  radius: number;
}

interface Shape {
  x: number;
  y: number;
  rotation: number;
  rotationSpeed: number;
  scale: number;
  scaleDirection: number;
  type: 'hexagon' | 'triangle' | 'line';
  opacity: number;
}

export class GeometricBackground {
  private canvas: HTMLCanvasElement;
  private ctx: CanvasRenderingContext2D;
  private particles: Particle[] = [];
  private shapes: Shape[] = [];
  private mouse = { x: 0, y: 0 };
  private animationId: number | null = null;
  private isDark = true;
  
  private config = {
    particles: {
      count: window.innerWidth < 768 ? 25 : 50,
      speed: 0.3,
      connectionDistance: 120,
      opacity: 0.15,
    },
    shapes: {
      count: window.innerWidth < 768 ? 4 : 8,
      rotationSpeed: 0.001,
      opacity: 0.08,
      size: window.innerWidth < 768 ? 80 : 150,
    },
    colors: {
      dark: {
        particle: 'rgba(110, 181, 192, 0.15)',
        line: 'rgba(110, 181, 192, 0.12)',
        shape: 'rgba(183, 148, 246, 0.08)',
      },
      light: {
        particle: 'rgba(59, 130, 198, 0.12)',
        line: 'rgba(59, 130, 198, 0.1)',
        shape: 'rgba(139, 92, 246, 0.06)',
      },
    },
  };

  constructor(canvas: HTMLCanvasElement) {
    this.canvas = canvas;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Failed to get 2d context');
    this.ctx = ctx;
    
    this.init();
  }

  private init() {
    this.resize();
    this.initParticles();
    this.initShapes();
    this.bindEvents();
    this.detectTheme();
    this.animate();
  }

  private resize() {
    this.canvas.width = window.innerWidth;
    this.canvas.height = window.innerHeight;
  }

  private initParticles() {
    this.particles = [];
    for (let i = 0; i < this.config.particles.count; i++) {
      this.particles.push({
        x: Math.random() * this.canvas.width,
        y: Math.random() * this.canvas.height,
        vx: (Math.random() - 0.5) * this.config.particles.speed,
        vy: (Math.random() - 0.5) * this.config.particles.speed,
        radius: 2,
      });
    }
  }

  private initShapes() {
    this.shapes = [];
    const types: Shape['type'][] = ['hexagon', 'triangle', 'line'];
    
    for (let i = 0; i < this.config.shapes.count; i++) {
      this.shapes.push({
        x: Math.random() * this.canvas.width,
        y: Math.random() * this.canvas.height,
        rotation: Math.random() * Math.PI * 2,
        rotationSpeed: (Math.random() - 0.5) * this.config.shapes.rotationSpeed,
        scale: 1,
        scaleDirection: Math.random() > 0.5 ? 1 : -1,
        type: types[Math.floor(Math.random() * types.length)],
        opacity: this.config.shapes.opacity,
      });
    }
  }

  private bindEvents() {
    window.addEventListener('resize', () => {
      this.resize();
      this.initParticles();
      this.initShapes();
    });
    
    // Mouse parallax (desktop only)
    if (window.innerWidth > 768) {
      window.addEventListener('mousemove', (e) => {
        this.mouse.x = e.clientX;
        this.mouse.y = e.clientY;
      });
    }
    
    // Detect theme changes
    const observer = new MutationObserver(() => {
      this.detectTheme();
    });
    
    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['data-theme'],
    });
  }

  private detectTheme() {
    const theme = document.documentElement.getAttribute('data-theme');
    this.isDark = theme !== 'light';
  }

  private animate = () => {
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    
    this.updateParticles();
    this.drawParticles();
    this.drawConnections();
    this.updateShapes();
    this.drawShapes();
    
    this.animationId = requestAnimationFrame(this.animate);
  };

  private updateParticles() {
    this.particles.forEach(particle => {
      particle.x += particle.vx;
      particle.y += particle.vy;
      
      // Bounce off edges
      if (particle.x < 0 || particle.x > this.canvas.width) particle.vx *= -1;
      if (particle.y < 0 || particle.y > this.canvas.height) particle.vy *= -1;
      
      // Keep within bounds
      particle.x = Math.max(0, Math.min(this.canvas.width, particle.x));
      particle.y = Math.max(0, Math.min(this.canvas.height, particle.y));
    });
  }

  private drawParticles() {
    const colors = this.isDark ? this.config.colors.dark : this.config.colors.light;
    this.ctx.fillStyle = colors.particle;
    
    this.particles.forEach(particle => {
      this.ctx.beginPath();
      this.ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
      this.ctx.fill();
    });
  }

  private drawConnections() {
    const colors = this.isDark ? this.config.colors.dark : this.config.colors.light;
    
    this.particles.forEach((p1, i) => {
      this.particles.slice(i + 1).forEach(p2 => {
        const dx = p1.x - p2.x;
        const dy = p1.y - p2.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < this.config.particles.connectionDistance) {
          const opacity = (1 - distance / this.config.particles.connectionDistance) * this.config.particles.opacity;
          this.ctx.strokeStyle = colors.line.replace('0.12', opacity.toString());
          this.ctx.lineWidth = 1;
          this.ctx.beginPath();
          this.ctx.moveTo(p1.x, p1.y);
          this.ctx.lineTo(p2.x, p2.y);
          this.ctx.stroke();
        }
      });
    });
  }

  private updateShapes() {
    this.shapes.forEach(shape => {
      shape.rotation += shape.rotationSpeed;
      
      // Subtle scale pulsing
      shape.scale += 0.0005 * shape.scaleDirection;
      if (shape.scale > 1.02 || shape.scale < 0.98) {
        shape.scaleDirection *= -1;
      }
    });
  }

  private drawShapes() {
    const colors = this.isDark ? this.config.colors.dark : this.config.colors.light;
    this.ctx.strokeStyle = colors.shape;
    this.ctx.lineWidth = 2;
    
    this.shapes.forEach(shape => {
      this.ctx.save();
      
      // Apply mouse parallax (subtle)
      let offsetX = 0;
      let offsetY = 0;
      if (window.innerWidth > 768) {
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        offsetX = (this.mouse.x - centerX) * 0.02;
        offsetY = (this.mouse.y - centerY) * 0.02;
      }
      
      this.ctx.translate(shape.x + offsetX, shape.y + offsetY);
      this.ctx.rotate(shape.rotation);
      this.ctx.scale(shape.scale, shape.scale);
      
      this.ctx.globalAlpha = shape.opacity;
      
      switch (shape.type) {
        case 'hexagon':
          this.drawHexagon();
          break;
        case 'triangle':
          this.drawTriangle();
          break;
        case 'line':
          this.drawDiagonalLine();
          break;
      }
      
      this.ctx.restore();
    });
  }

  private drawHexagon() {
    const size = this.config.shapes.size;
    this.ctx.beginPath();
    for (let i = 0; i < 6; i++) {
      const angle = (Math.PI / 3) * i;
      const x = size * Math.cos(angle);
      const y = size * Math.sin(angle);
      if (i === 0) {
        this.ctx.moveTo(x, y);
      } else {
        this.ctx.lineTo(x, y);
      }
    }
    this.ctx.closePath();
    this.ctx.stroke();
  }

  private drawTriangle() {
    const size = this.config.shapes.size;
    this.ctx.beginPath();
    this.ctx.moveTo(0, -size);
    this.ctx.lineTo(size * 0.866, size * 0.5);
    this.ctx.lineTo(-size * 0.866, size * 0.5);
    this.ctx.closePath();
    this.ctx.stroke();
  }

  private drawDiagonalLine() {
    const size = this.config.shapes.size;
    this.ctx.beginPath();
    this.ctx.moveTo(-size, -size);
    this.ctx.lineTo(size, size);
    this.ctx.stroke();
  }

  public destroy() {
    if (this.animationId) {
      cancelAnimationFrame(this.animationId);
    }
  }
}

// Initialize background on page load
export function initGeometricBackground() {
  const canvas = document.createElement('canvas');
  canvas.id = 'geometric-background';
  canvas.style.position = 'fixed';
  canvas.style.top = '0';
  canvas.style.left = '0';
  canvas.style.width = '100%';
  canvas.style.height = '100%';
  canvas.style.pointerEvents = 'none';
  canvas.style.zIndex = '0';
  
  document.body.prepend(canvas);
  
  return new GeometricBackground(canvas);
}
