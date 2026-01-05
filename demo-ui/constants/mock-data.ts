export interface User {
  id: string;
  name: string;
  email: string;
  avatar?: string;
  plan: "free" | "pro" | "premium";
  videosThisMonth: number;
  videosLimit: number;
  memberSince: string;
}

export interface YogaRoutine {
  id: string;
  name: string;
  description: string;
  duration: number;
  difficulty: "beginner" | "intermediate" | "advanced";
  poses: string[];
  imageUrl: string;
}

export interface Avatar {
  id: string;
  name: string;
  gender: "female" | "male" | "non-binary";
  style: string;
  description: string;
  thumbnailUrl: string;
}

export interface Voice {
  id: string;
  name: string;
  gender: "female" | "male";
  tone: string;
  description: string;
  sampleUrl?: string;
}

export interface GeneratedSession {
  id: string;
  routine: YogaRoutine;
  avatar: Avatar;
  voice: Voice;
  createdAt: string;
  duration: number;
  thumbnailUrl: string;
  videoUrl: string;
}

export interface SubscriptionPlan {
  id: string;
  name: string;
  price: number;
  interval: "month" | "year";
  videosPerMonth: number;
  features: string[];
  popular?: boolean;
}

export const mockUser: User = {
  id: "user-1",
  name: "Sarah Johnson",
  email: "sarah.johnson@example.com",
  avatar: undefined,
  plan: "pro",
  videosThisMonth: 8,
  videosLimit: 10,
  memberSince: "2024-01-15",
};

export const yogaRoutines: YogaRoutine[] = [
  {
    id: "routine-1",
    name: "Beginner Morning Flow",
    description:
      "A gentle 20-minute flow perfect for starting your day. Focuses on basic poses and breathing techniques.",
    duration: 20,
    difficulty: "beginner",
    poses: [
      "Mountain Pose",
      "Cat-Cow",
      "Downward Dog",
      "Child's Pose",
      "Seated Forward Bend",
    ],
    imageUrl: "/routines/morning-flow.jpg",
  },
  {
    id: "routine-2",
    name: "Intermediate Stretch & Flex",
    description:
      "A 30-minute session designed to improve flexibility and strength with intermediate-level poses.",
    duration: 30,
    difficulty: "intermediate",
    poses: [
      "Warrior I & II",
      "Triangle Pose",
      "Half Moon",
      "Pigeon Pose",
      "Bridge Pose",
    ],
    imageUrl: "/routines/stretch-flex.jpg",
  },
  {
    id: "routine-3",
    name: "Advanced Power Yoga",
    description:
      "An intense 45-minute power yoga session that builds strength, endurance, and mental focus.",
    duration: 45,
    difficulty: "advanced",
    poses: [
      "Crow Pose",
      "Headstand",
      "Wheel Pose",
      "Side Plank",
      "Boat Pose",
    ],
    imageUrl: "/routines/power-yoga.jpg",
  },
  {
    id: "routine-4",
    name: "Evening Wind Down",
    description:
      "A relaxing 25-minute routine to help you unwind and prepare for restful sleep.",
    duration: 25,
    difficulty: "beginner",
    poses: [
      "Legs Up The Wall",
      "Reclining Butterfly",
      "Spinal Twist",
      "Happy Baby",
      "Savasana",
    ],
    imageUrl: "/routines/wind-down.jpg",
  },
];

export const avatars: Avatar[] = [
  {
    id: "avatar-1",
    name: "Maya Chen",
    gender: "female",
    style: "Traditional",
    description:
      "Certified yoga instructor with 10+ years of experience. Specializes in Hatha and Vinyasa styles.",
    thumbnailUrl: "/avatars/maya.jpg",
  },
  {
    id: "avatar-2",
    name: "Alex Rivera",
    gender: "male",
    style: "Modern",
    description:
      "Dynamic instructor focusing on power yoga and fitness integration. Former athlete turned yogi.",
    thumbnailUrl: "/avatars/alex.jpg",
  },
  {
    id: "avatar-3",
    name: "Jordan Lee",
    gender: "non-binary",
    style: "Holistic",
    description:
      "Mindfulness expert combining yoga with meditation and breathwork practices.",
    thumbnailUrl: "/avatars/jordan.jpg",
  },
];

export const voices: Voice[] = [
  {
    id: "voice-1",
    name: "Calm Sophia",
    gender: "female",
    tone: "Soothing & Warm",
    description:
      "A gentle, calming voice perfect for relaxation and beginner sessions.",
    sampleUrl: "/voices/sophia-sample.mp3",
  },
  {
    id: "voice-2",
    name: "Energetic Marcus",
    gender: "male",
    tone: "Motivating & Clear",
    description:
      "An encouraging, energetic voice ideal for power yoga and advanced practices.",
    sampleUrl: "/voices/marcus-sample.mp3",
  },
];

export const generatedSessions: GeneratedSession[] = [
  {
    id: "session-1",
    routine: yogaRoutines[0],
    avatar: avatars[0],
    voice: voices[0],
    createdAt: "2024-12-20T08:30:00Z",
    duration: 20,
    thumbnailUrl: "/sessions/session-1-thumb.jpg",
    videoUrl: "/sessions/session-1.mp4",
  },
  {
    id: "session-2",
    routine: yogaRoutines[1],
    avatar: avatars[1],
    voice: voices[1],
    createdAt: "2024-12-18T14:15:00Z",
    duration: 30,
    thumbnailUrl: "/sessions/session-2-thumb.jpg",
    videoUrl: "/sessions/session-2.mp4",
  },
  {
    id: "session-3",
    routine: yogaRoutines[3],
    avatar: avatars[0],
    voice: voices[0],
    createdAt: "2024-12-15T19:00:00Z",
    duration: 25,
    thumbnailUrl: "/sessions/session-3-thumb.jpg",
    videoUrl: "/sessions/session-3.mp4",
  },
  {
    id: "session-4",
    routine: yogaRoutines[2],
    avatar: avatars[2],
    voice: voices[1],
    createdAt: "2024-12-12T07:00:00Z",
    duration: 45,
    thumbnailUrl: "/sessions/session-4-thumb.jpg",
    videoUrl: "/sessions/session-4.mp4",
  },
  {
    id: "session-5",
    routine: yogaRoutines[0],
    avatar: avatars[1],
    voice: voices[0],
    createdAt: "2024-12-10T08:00:00Z",
    duration: 20,
    thumbnailUrl: "/sessions/session-5-thumb.jpg",
    videoUrl: "/sessions/session-5.mp4",
  },
];

export const subscriptionPlans: SubscriptionPlan[] = [
  {
    id: "plan-free",
    name: "Free",
    price: 0,
    interval: "month",
    videosPerMonth: 1,
    features: [
      "1 video per month",
      "Access to beginner routines",
      "2 avatar choices",
      "Standard voice quality",
    ],
  },
  {
    id: "plan-pro",
    name: "Pro",
    price: 19,
    interval: "month",
    videosPerMonth: 10,
    features: [
      "10 videos per month",
      "Access to all routines",
      "All avatar choices",
      "High-quality voices",
      "Download videos",
      "Priority generation",
    ],
    popular: true,
  },
  {
    id: "plan-premium",
    name: "Premium",
    price: 49,
    interval: "month",
    videosPerMonth: -1,
    features: [
      "Unlimited videos",
      "Access to all routines",
      "All avatar choices",
      "Premium voice quality",
      "Download videos",
      "Priority generation",
      "Custom routine builder",
      "1-on-1 consultation",
    ],
  },
];

export interface BillingHistoryItem {
  id: string;
  date: string;
  description: string;
  amount: number;
  status: "paid" | "pending" | "failed";
}

export const billingHistory: BillingHistoryItem[] = [
  {
    id: "invoice-1",
    date: "2024-12-01",
    description: "Pro Plan - Monthly",
    amount: 19,
    status: "paid",
  },
  {
    id: "invoice-2",
    date: "2024-11-01",
    description: "Pro Plan - Monthly",
    amount: 19,
    status: "paid",
  },
  {
    id: "invoice-3",
    date: "2024-10-01",
    description: "Pro Plan - Monthly",
    amount: 19,
    status: "paid",
  },
];
