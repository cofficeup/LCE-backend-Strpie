# YogaAI Demo UI - Complete Guide

## Overview

A fully functional, production-ready demo UI for an AI-powered yoga instruction platform. This demo application showcases a complete user experience from authentication through video generation and subscription management.

## Location

The demo UI is located in the `/demo-ui` directory at the root of this project.

## Quick Start

```bash
cd demo-ui
npm install
npm run dev
```

Visit `http://localhost:3000` to see the application.

### Demo Credentials
- **Email:** sarah.johnson@example.com
- **Password:** Any password (auth is mocked)

## Technology Stack

- **Next.js 14.x** - App Router (latest stable)
- **TypeScript** - Full type safety
- **Tailwind CSS** - Utility-first styling with custom theme
- **Lucide React** - Beautiful, consistent icons
- **React Hooks** - Modern state management

## Architecture

### Pages Structure

```
/                           → Redirects to /auth/login
/auth/login                 → Login page
/auth/signup                → Registration page
/auth/forgot-password       → Password reset flow
/dashboard                  → Main dashboard (protected)
/session/builder            → Multi-step session builder
/videos                     → Video history/library
/videos/player?id=X         → Video player page
/subscription               → Plan management
/account                    → User settings
```

### Component Structure

```
components/
├── ui/                     # Reusable UI primitives
│   ├── button.tsx         # Button with variants
│   ├── card.tsx           # Card components
│   ├── input.tsx          # Form input
│   ├── label.tsx          # Form label
│   ├── badge.tsx          # Status badges
│   ├── progress.tsx       # Progress bar
│   └── spinner.tsx        # Loading spinner
└── layout/                 # Layout components
    ├── sidebar.tsx        # Navigation sidebar
    ├── header.tsx         # Top header
    └── dashboard-layout.tsx # Main layout wrapper
```

### Data Structure

All mock data is centralized in `constants/mock-data.ts`:

- **User Data:** Profile, subscription, usage stats
- **Yoga Routines:** 4 routines (beginner to advanced)
- **Avatars:** 3 instructor options
- **Voices:** 2 voice options
- **Generated Sessions:** 5 sample videos
- **Subscription Plans:** Free, Pro, Premium tiers
- **Billing History:** Payment records

## Features Walkthrough

### 1. Authentication Flow
**Location:** `/app/auth/`

- **Login:** Simple email/password form
- **Signup:** New account creation with validation
- **Forgot Password:** Password reset with confirmation
- **Auto-redirect:** Successful login redirects to dashboard

### 2. Dashboard
**Location:** `/app/dashboard/page.tsx`

Key features:
- Personalized welcome message
- Usage statistics cards:
  - Videos this month (with progress bar)
  - Current subscription plan
  - Total sessions count
- Quick action: "Generate New Session" CTA
- Recent sessions grid (3 most recent)
- Full navigation sidebar

### 3. Session Builder (4-Step Wizard)
**Location:** `/app/session/builder/page.tsx`

**Step 1: Select Yoga Routine**
- 4 routine options with different difficulties
- Each shows: duration, difficulty badge, description, key poses
- Visual cards with hover effects

**Step 2: Choose Instructor Avatar**
- 3 avatar options (different genders and styles)
- Each avatar has: name, style badge, description
- Avatar preview placeholder

**Step 3: Select Voice**
- 2 voice options (different tones)
- Voice characteristics and descriptions
- "Play Sample" button (UI only)

**Step 4: Review & Generate**
- Summary of all selections
- Estimated generation time
- "Generate Video" button
- Loading state with spinner and message (3-second delay)
- Auto-redirect to video player

Progress indicator at top shows current step with visual feedback.

### 4. Video Player
**Location:** `/app/videos/player/page.tsx`

Features:
- Mock video player interface with gradient background
- Video controls: play/pause, progress bar, volume, fullscreen
- Success notification for newly generated videos
- Session details card:
  - Routine name, instructor, voice, duration, difficulty
- Key poses list
- Action buttons:
  - Share (UI only)
  - Download (UI only)
  - Generate Similar (UI only)
  - Generate New Session (links to builder)

### 5. Video Library
**Location:** `/app/videos/page.tsx`

Features:
- Grid layout of all generated sessions
- Each card shows:
  - Video thumbnail (gradient placeholder)
  - Routine name and difficulty badge
  - Instructor and voice info
  - Duration and creation date
- Hover effect reveals play button
- Actions: Watch, Delete (UI only)
- "New Session" CTA button

### 6. Subscription Management
**Location:** `/app/subscription/page.tsx`

Features:
- **Current Plan Card:**
  - Plan name with active badge
  - Monthly price
  - Usage progress bar
  - Videos used vs. limit
  - Reset date info
  - "Manage Billing" button

- **Plan Comparison:**
  - 3 tiers: Free, Pro (Popular), Premium
  - Feature lists for each plan
  - Upgrade/Downgrade buttons
  - "Most Popular" badge on Pro plan

- **Billing History Table:**
  - Date, description, amount, status
  - Status badges (Paid, Pending, Failed)
  - Clean table design

### 7. Account Settings
**Location:** `/app/account/page.tsx`

Sections:
- **Profile Information:**
  - Avatar placeholder
  - Editable name and email
  - Edit/Save/Cancel workflow

- **Account Details:**
  - Subscription plan with active badge
  - Member since date
  - User ID

- **Email Preferences:**
  - Toggle notifications
  - Weekly tips subscription
  - Product updates

- **Password Management:**
  - Change password button

- **Danger Zone:**
  - Delete account option with warning
  - Red-themed card for emphasis

## Design System

### Color Palette

```css
Sage Green (Primary):
- 50:  #f6f8f6
- 100: #e8ede8
- 200: #d1dbd1
- 300: #b0c4b0
- 400: #8aaa8a
- 500: #6b8e6b
- 600: #557155 (main brand color)
- 700: #445944
- 800: #384a38
- 900: #2f3e2f

Cream (Background):
- 50:  #fdfcfb
- 100: #f9f7f4
- 200: #f5f1eb
- 300: #ede7de

Blue (Accent):
- 600: #2563eb
- 700: #1d4ed8
```

### Component Variants

**Button:**
- Primary: Sage green background
- Secondary: Cream background
- Outline: Sage green border
- Ghost: No background
- Danger: Red background

**Badge:**
- Default: Sage
- Success: Green
- Warning: Yellow
- Danger: Red

### Typography

- Headings: Bold, sage-900
- Body: Regular, sage-600
- Small text: sage-600
- Font: Geist Sans (Next.js default)

### Layout Principles

1. **Generous Whitespace:** 8-unit spacing scale
2. **Card-Based:** Most content in cards with rounded corners
3. **Responsive Grid:** Flexbox and CSS Grid
4. **Mobile-First:** Breakpoints: sm (640px), md (768px), lg (1024px)
5. **Consistent Padding:** p-4, p-6, p-8 throughout

## Responsive Design

All pages are fully responsive:

- **Mobile (<768px):** Single column, stacked cards
- **Tablet (768-1024px):** 2 columns where appropriate
- **Desktop (>1024px):** 3 columns, full sidebar

Key responsive features:
- Collapsible navigation on mobile (not implemented, desktop sidebar only)
- Responsive grids (1/2/3 columns)
- Touch-friendly buttons (min 44px tap targets)
- Readable text sizes at all breakpoints

## Mock Data Details

### Mock User
```typescript
{
  name: "Sarah Johnson",
  email: "sarah.johnson@example.com",
  plan: "pro",
  videosThisMonth: 8,
  videosLimit: 10,
  memberSince: "2024-01-15"
}
```

### Subscription Plans
1. **Free:** $0/mo, 1 video/month
2. **Pro:** $19/mo, 10 videos/month (Most Popular)
3. **Premium:** $49/mo, Unlimited videos

### Sample Sessions
- 5 pre-generated sessions
- Mix of difficulty levels
- Different avatar and voice combinations
- Dates spanning last 2 weeks

## Development Commands

```bash
# Install dependencies
npm install

# Run development server
npm run dev

# Build for production
npm run build

# Start production server
npm start

# Type checking
npm run type-check

# Linting
npm run lint
```

## Production Build

The application builds successfully with:
- Static page generation for most routes
- Optimized bundle size
- TypeScript compilation
- ESLint validation

All routes are statically generated except dynamic video player (uses search params).

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Accessibility

- Semantic HTML elements
- ARIA labels where needed
- Keyboard navigation support
- Focus states on interactive elements
- Color contrast meets WCAG AA standards
- Alt text for icons (screen reader text)

## Recording a Demo Video

### Suggested Flow (5-10 minutes)

1. **Introduction (30s)**
   - Show login page
   - Explain the YogaAI platform concept

2. **Authentication (30s)**
   - Quick signup flow demo
   - Login to dashboard

3. **Dashboard Tour (1min)**
   - Highlight stats cards
   - Show recent sessions
   - Point out navigation

4. **Session Builder (3min)**
   - Walk through Step 1: Select routine (explain options)
   - Step 2: Choose avatar (show different styles)
   - Step 3: Select voice (mention voice options)
   - Step 4: Review and generate
   - Show loading state
   - Reveal completed video

5. **Video Player (1min)**
   - Demonstrate player controls
   - Show session details
   - Mention sharing/download features

6. **Video Library (1min)**
   - Browse generated sessions
   - Show grid layout
   - Mention organization

7. **Subscription (1.5min)**
   - Show current plan and usage
   - Compare plan tiers
   - Highlight billing history

8. **Account Settings (1min)**
   - Profile management
   - Email preferences
   - Settings organization

9. **Conclusion (30s)**
   - Recap key features
   - Mention tech stack

## Future Enhancements (Not Implemented)

Potential additions for a real application:
- Real backend API integration
- Actual video generation with AI
- Stripe payment integration
- Video upload/storage
- User authentication with JWT
- Real-time progress updates
- Mobile app companion
- Social sharing features
- Custom routine builder
- Analytics dashboard

## Troubleshooting

### Port Already in Use
```bash
# Kill process on port 3000
lsof -ti:3000 | xargs kill -9
```

### Build Errors
```bash
# Clear Next.js cache
rm -rf .next
npm run build
```

### Module Not Found
```bash
# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install
```

## Credits

Built with:
- Next.js by Vercel
- Tailwind CSS
- Lucide Icons
- TypeScript

---

**Ready for demo recording!** 🎬🧘‍♀️
