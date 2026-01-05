# YogaAI Demo UI

A complete, production-ready demo UI for an AI-powered yoga instruction platform. Built with Next.js 14, TypeScript, and Tailwind CSS.

## Features

### Authentication
- Login page with email/password
- Sign-up page
- Forgot password flow
- Automatic redirect to dashboard after login

### Dashboard
- Welcome banner with user name
- Quick stats (videos generated, subscription status, remaining videos)
- Navigation sidebar with: Dashboard, My Sessions, Subscription, Account, Logout
- Quick action to generate new session
- Recent sessions display

### Session Builder (Multi-step Wizard)
- **Step 1:** Select Yoga Routine (4 predefined routines with different difficulty levels)
- **Step 2:** Choose Instructor Avatar (3 avatar options with different styles)
- **Step 3:** Choose Voice (2 voice options with different tones)
- **Step 4:** Review & Generate with loading state

### Video Player
- Full-screen video player interface
- Video controls (play/pause/progress)
- Session details sidebar
- Share/download buttons
- Generate another session option

### My Sessions / Video History
- Grid view of all generated sessions
- Thumbnail, routine name, date created, duration
- Click to play or delete videos

### Subscription Management
- Current plan display with usage progress
- 3 tier options (Free, Pro, Premium)
- Billing history table
- Upgrade/downgrade buttons

### Account Settings
- User profile management
- Email preferences
- Password change option
- Account deletion (danger zone)

## Tech Stack

- **Framework:** Next.js 14 (App Router)
- **Language:** TypeScript
- **Styling:** Tailwind CSS
- **Icons:** Lucide React
- **State:** React Hooks

## Getting Started

### Prerequisites
- Node.js 18+ installed
- npm or yarn

### Installation

1. Navigate to the demo-ui directory:
```bash
cd demo-ui
```

2. Install dependencies:
```bash
npm install
```

3. Run the development server:
```bash
npm run dev
```

4. Open [http://localhost:3000](http://localhost:3000) in your browser

### Demo Login Credentials
- **Email:** sarah.johnson@example.com
- **Password:** password123 (or any password)

## Project Structure

```
demo-ui/
├── app/                      # Next.js App Router pages
│   ├── auth/                 # Authentication pages
│   │   ├── login/
│   │   ├── signup/
│   │   └── forgot-password/
│   ├── dashboard/            # Main dashboard
│   ├── session/              # Session builder
│   │   └── builder/
│   ├── videos/               # Video history & player
│   │   └── player/
│   ├── subscription/         # Subscription management
│   └── account/              # Account settings
├── components/               # Reusable components
│   ├── ui/                   # UI primitives
│   └── layout/               # Layout components
├── constants/                # Mock data
│   └── mock-data.ts
├── lib/                      # Utilities
│   └── utils.ts
└── public/                   # Static assets
```

## Color Palette

The demo uses a calming, yoga-focused color scheme:

- **Sage Green:** Primary brand color (#557155 and variants)
- **Cream:** Background and accents (#fdfcfb, #f9f7f4)
- **Blue:** Secondary accents (#2563eb)
- **White:** Base background (#ffffff)

## Design Principles

- **Calming & Minimal:** Soft colors, generous whitespace
- **Mobile-First:** Responsive design across all viewports
- **Accessibility:** WCAG AA compliant with proper contrast and keyboard navigation
- **Consistent:** Reusable components with predictable behavior
- **Professional:** App-store-quality appearance

## Demo Recording Tips

1. Start at the login page
2. Show the signup flow briefly
3. Login to reach the dashboard
4. Highlight the stats and navigation
5. Walk through the session builder (all 4 steps)
6. Show the video generation loading state
7. Display the video player with controls
8. Navigate to My Sessions to show history
9. Visit the Subscription page to show plans
10. End on the Account settings page

## Build for Production

```bash
npm run build
npm start
```

## Notes

- All data is mocked (no backend required)
- Video player is a mockup (no actual video files)
- All navigation flows work correctly
- Ready for demo recording with no console errors
