# YogaAI Demo UI - Quick Reference

## 🚀 Start the Application

```bash
cd demo-ui
npm run dev
```

Open: http://localhost:3000

## 🔐 Login Credentials

**Email:** sarah.johnson@example.com  
**Password:** password123 (or anything)

## 📁 All Pages

| Route | Description |
|-------|-------------|
| `/` | Home (redirects to login) |
| `/auth/login` | Login page |
| `/auth/signup` | Signup page |
| `/auth/forgot-password` | Password reset |
| `/dashboard` | Main dashboard |
| `/session/builder` | 4-step session builder |
| `/videos` | Video library |
| `/videos/player?id=session-1` | Video player |
| `/subscription` | Subscription management |
| `/account` | Account settings |

## 🎨 Color Palette

| Color | Hex | Usage |
|-------|-----|-------|
| Sage 600 | `#557155` | Primary buttons, active states |
| Sage 100 | `#e8ede8` | Backgrounds, hover states |
| Cream 50 | `#fdfcfb` | Page background |
| Blue 600 | `#2563eb` | Accents |

## 📦 Components

### UI Components
- `Button` - 5 variants (primary, secondary, outline, ghost, danger)
- `Card` - Card container with header, content, footer
- `Input` - Text input field
- `Label` - Form label
- `Badge` - Status badge (4 variants)
- `Progress` - Progress bar
- `Spinner` - Loading spinner (3 sizes)

### Layout Components
- `Sidebar` - Left navigation sidebar
- `Header` - Top header bar
- `DashboardLayout` - Main layout wrapper

## 📊 Mock Data

### User
```typescript
name: "Sarah Johnson"
email: "sarah.johnson@example.com"
plan: "pro"
videosThisMonth: 8
videosLimit: 10
```

### Routines
1. Beginner Morning Flow (20 min)
2. Intermediate Stretch & Flex (30 min)
3. Advanced Power Yoga (45 min)
4. Evening Wind Down (25 min)

### Avatars
1. Maya Chen (Traditional)
2. Alex Rivera (Modern)
3. Jordan Lee (Holistic)

### Voices
1. Calm Sophia (Soothing)
2. Energetic Marcus (Motivating)

### Plans
1. Free - $0/mo, 1 video
2. Pro - $19/mo, 10 videos ⭐
3. Premium - $49/mo, unlimited

## 🛠️ Commands

```bash
# Install
npm install

# Development
npm run dev

# Build
npm run build

# Production
npm start

# Type check
npm run type-check

# Lint
npm run lint
```

## 📝 File Locations

```
demo-ui/
├── app/                    # Pages
├── components/             # Components
├── constants/              # Mock data
├── lib/                    # Utils
├── README.md              # Setup guide
├── FEATURE_CHECKLIST.md   # All features
└── DEMO_SCRIPT.md         # Recording script
```

## 🎬 Demo Flow

1. Login → Dashboard
2. Dashboard → Session Builder
3. Builder (4 steps) → Generate
4. Video Player
5. My Sessions
6. Subscription
7. Account Settings

## ⚡ Quick Tips

- All auth is mocked (any password works)
- No real API calls
- All navigation works
- Responsive design
- No console errors
- Production build ready

## 📚 Documentation

- `README.md` - Quick start
- `DEMO_SCRIPT.md` - Recording guide
- `FEATURE_CHECKLIST.md` - Complete features
- `../DEMO_UI_GUIDE.md` - Full documentation
- `../DEMO_UI_SUMMARY.md` - Project summary

## 🐛 Troubleshooting

**Port in use:**
```bash
lsof -ti:3000 | xargs kill -9
```

**Clear cache:**
```bash
rm -rf .next node_modules
npm install
```

**Build fails:**
```bash
npm run build
```

---

**Ready to demo!** 🎬
