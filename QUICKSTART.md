# 🚀 QUANTUM AIRWAYS - QUICK START GUIDE

## ✅ **EVERYTHING IS READY!**

The frontend has been completely redesigned and built. Here's how to run it:

---

## 🏃 **Option 1: Run Flask Only (Recommended)**

The frontend is already built and ready to serve!

```bash
# From the project root (/home/xero/Dev/db)
python server.py
```

**Then visit:**
- **Landing Page:** http://localhost:5000/dist/
- **Features:** http://localhost:5000/dist/features.html
- **Booking:** http://localhost:5000/dist/booking.html
- **How It Works:** http://localhost:5000/dist/how-it-works.html
- **Education:** http://localhost:5000/dist/education.html

**That's it!** The built frontend is in `/public/dist/` and Flask serves it automatically.

---

## 🛠️ **Option 2: Development Mode (If Making Changes)**

If you want to make changes to the frontend:

```bash
# Terminal 1: Backend
cd /home/xero/Dev/db
python server.py

# Terminal 2: Frontend dev server
cd /home/xero/Dev/db/frontend
npm run dev
```

**Then visit:** http://localhost:3000/dist/

**Changes auto-reload!**

---

## 📦 **Rebuild Frontend (After Changes)**

If you modify frontend code:

```bash
cd /home/xero/Dev/db/frontend
npm run build
```

Output goes to `/public/dist/` automatically.

---

## 🎨 **What You'll See**

### **Landing Page** (`/dist/`)
- Animated geometric background
- Hero section with gradient text
- 3 feature cards (Kyber, Dilithium, QRNG)
- How It Works preview
- Theme toggle (top-right)

### **Features Page** (`/dist/features.html`)
- Expandable technical documentation
- Click on Kyber-512, Dilithium3, or QRNG sections
- Comprehensive details with code examples
- Links to NIST standards

### **Booking Page** (`/dist/booking.html`)
- Full booking functionality (unchanged backend)
- Flight selection
- Interactive seat map
- Passenger details form
- Ticket verification tool
- Works exactly as before!

### **How It Works** (`/dist/how-it-works.html`)
- Step-by-step booking process
- Quantum security explained
- FAQ section

### **Education** (`/dist/education.html`)
- Multi-tab interface
- Database architecture
- System architecture
- Quantum security
- Developer docs with API endpoints

---

## 🌓 **Theme System**

**Toggle between dark/light modes:**
1. Click the sun/moon icon in the top-right header
2. Theme persists across page visits
3. Auto-detects your system preference on first visit

**Dark Mode:** Minimalistic cyberpunk (muted colors, subtle glows)  
**Light Mode:** Clean professional design

---

## 📱 **Responsive Design**

All pages work on:
- 📱 Mobile (320px+)
- 📱 Tablet (768px+)
- 💻 Desktop (1024px+)

---

## 🔧 **Troubleshooting**

### **Frontend not showing?**
```bash
# Make sure Flask is running
python server.py

# Visit http://localhost:5000/dist/ (note the /dist/)
```

### **Changes not appearing?**
```bash
# Rebuild frontend
cd frontend
npm run build

# Restart Flask
# Ctrl+C to stop, then python server.py again
```

### **Need to install frontend dependencies?**
```bash
cd frontend
npm install
```

---

## 📂 **File Structure**

```
/home/xero/Dev/db/
├── frontend/              # Source code (make changes here)
│   ├── src/
│   ├── *.html
│   └── package.json
│
├── public/dist/           # Built files (Flask serves this)
│   ├── index.html
│   ├── features.html
│   ├── booking.html
│   ├── how-it-works.html
│   ├── education.html
│   └── assets/
│
├── server.py              # Flask backend (unchanged)
└── public/
    ├── css/style.css      # Old styles (still used by booking page)
    └── js/app.js          # Old booking logic (still works!)
```

---

## ✨ **Key Features**

✅ **Theme System** - Dark/light mode with auto-detection  
✅ **Animated Background** - Geometric particles and shapes  
✅ **Custom Icons** - 12 geometric SVG icons  
✅ **Web Components** - Modular, reusable UI  
✅ **Expandable Sections** - Scannable + detailed content  
✅ **Tab Navigation** - Multi-tab education page  
✅ **Responsive** - Mobile, tablet, desktop  
✅ **Fast** - Optimized build (~40KB gzipped)  
✅ **No Backend Changes** - Existing APIs work perfectly  

---

## 📖 **Documentation**

- **Full Technical Doc:** `/home/xero/Dev/db/FRONTEND_COMPLETE.md`
- **Frontend README:** `/home/xero/Dev/db/frontend/README.md`
- **This Guide:** `/home/xero/Dev/db/QUICKSTART.md`

---

## 🎯 **Quick Tips**

1. **Always visit `/dist/`** - That's where the new frontend lives
2. **Old `/public/index.html`** - Still exists, not used by new frontend
3. **Booking page** - Uses both new UI + old `/js/app.js` for compatibility
4. **Theme toggle** - Top-right corner of every page
5. **Navigation** - Click the header links to switch pages

---

## 🚀 **YOU'RE READY!**

Just run:
```bash
python server.py
```

Then open: **http://localhost:5000/dist/**

Enjoy your quantum-secured, modern, beautiful booking system! 🎉
