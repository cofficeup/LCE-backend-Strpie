"use client";

import { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Play,
  Pause,
  Volume2,
  Maximize,
  Download,
  Share2,
  Sparkles,
  RotateCcw,
} from "lucide-react";
import { generatedSessions } from "@/constants/mock-data";

function VideoPlayerContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const sessionId = searchParams.get("id") || "session-1";
  const isNew = searchParams.get("new") === "true";

  const [isPlaying, setIsPlaying] = useState(false);
  const [showSuccess, setShowSuccess] = useState(isNew);

  const session = generatedSessions.find((s) => s.id === sessionId);

  useEffect(() => {
    if (isNew) {
      const timer = setTimeout(() => {
        setShowSuccess(false);
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [isNew]);

  if (!session) {
    return (
      <DashboardLayout>
        <div className="text-center">
          <h1 className="text-2xl font-bold text-sage-900">Session not found</h1>
          <Link href="/videos">
            <Button className="mt-4">View All Sessions</Button>
          </Link>
        </div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout>
      <div className="mx-auto max-w-6xl space-y-6">
        {showSuccess && (
          <Card className="border-green-200 bg-green-50">
            <CardContent className="flex items-center gap-3 p-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                <Sparkles className="h-5 w-5 text-green-600" />
              </div>
              <div>
                <p className="font-semibold text-green-900">
                  Video Generated Successfully!
                </p>
                <p className="text-sm text-green-700">
                  Your personalized yoga session is ready to watch.
                </p>
              </div>
            </CardContent>
          </Card>
        )}

        <div>
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-sage-900">
                {session.routine.name}
              </h1>
              <p className="mt-1 text-sage-600">
                Created on {new Date(session.createdAt).toLocaleDateString()}
              </p>
            </div>
            <Badge
              variant={
                session.routine.difficulty === "beginner"
                  ? "success"
                  : session.routine.difficulty === "intermediate"
                  ? "warning"
                  : "danger"
              }
            >
              {session.routine.difficulty}
            </Badge>
          </div>

          <Card className="overflow-hidden">
            <div className="relative aspect-video bg-gradient-to-br from-sage-900 via-sage-700 to-sage-900">
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="text-center">
                  <div className="mb-4 flex justify-center">
                    <div className="flex h-20 w-20 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                      <Play className="h-10 w-10 text-white" />
                    </div>
                  </div>
                  <p className="text-lg font-medium text-white">
                    {session.routine.name}
                  </p>
                  <p className="text-sm text-white/80">
                    {session.duration} minutes
                  </p>
                </div>
              </div>

              <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-6">
                <div className="mb-4">
                  <div className="h-1 w-full rounded-full bg-white/30">
                    <div className="h-1 w-0 rounded-full bg-white" />
                  </div>
                </div>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-4">
                    <button
                      onClick={() => setIsPlaying(!isPlaying)}
                      className="flex h-12 w-12 items-center justify-center rounded-full bg-white text-sage-900 hover:bg-cream-100"
                    >
                      {isPlaying ? (
                        <Pause className="h-6 w-6" />
                      ) : (
                        <Play className="h-6 w-6" />
                      )}
                    </button>
                    <div className="text-sm text-white">0:00 / {session.duration}:00</div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button className="p-2 text-white hover:text-cream-100">
                      <Volume2 className="h-5 w-5" />
                    </button>
                    <button className="p-2 text-white hover:text-cream-100">
                      <Maximize className="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          <Card>
            <CardContent className="p-6">
              <h3 className="mb-4 text-lg font-semibold text-sage-900">
                Session Details
              </h3>
              <div className="space-y-3 text-sm">
                <div>
                  <p className="text-sage-600">Routine</p>
                  <p className="font-medium text-sage-900">
                    {session.routine.name}
                  </p>
                </div>
                <div>
                  <p className="text-sage-600">Instructor</p>
                  <p className="font-medium text-sage-900">
                    {session.avatar.name}
                  </p>
                </div>
                <div>
                  <p className="text-sage-600">Voice</p>
                  <p className="font-medium text-sage-900">
                    {session.voice.name}
                  </p>
                </div>
                <div>
                  <p className="text-sage-600">Duration</p>
                  <p className="font-medium text-sage-900">
                    {session.duration} minutes
                  </p>
                </div>
                <div>
                  <p className="text-sage-600">Difficulty</p>
                  <p className="font-medium text-sage-900 capitalize">
                    {session.routine.difficulty}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <h3 className="mb-4 text-lg font-semibold text-sage-900">
                Key Poses
              </h3>
              <ul className="space-y-2">
                {session.routine.poses.map((pose, index) => (
                  <li
                    key={index}
                    className="flex items-center gap-2 text-sm text-sage-900"
                  >
                    <div className="h-1.5 w-1.5 rounded-full bg-sage-600" />
                    {pose}
                  </li>
                ))}
              </ul>
            </CardContent>
          </Card>
        </div>

        <div className="flex flex-wrap gap-4">
          <Button className="gap-2" variant="outline">
            <Share2 className="h-4 w-4" />
            Share
          </Button>
          <Button className="gap-2" variant="outline">
            <Download className="h-4 w-4" />
            Download
          </Button>
          <Button className="gap-2" variant="outline">
            <RotateCcw className="h-4 w-4" />
            Generate Similar
          </Button>
          <Link href="/session/builder">
            <Button className="gap-2">
              <Sparkles className="h-4 w-4" />
              Generate New Session
            </Button>
          </Link>
        </div>
      </div>
    </DashboardLayout>
  );
}

export default function VideoPlayerPage() {
  return (
    <Suspense fallback={<DashboardLayout><div>Loading...</div></DashboardLayout>}>
      <VideoPlayerContent />
    </Suspense>
  );
}
