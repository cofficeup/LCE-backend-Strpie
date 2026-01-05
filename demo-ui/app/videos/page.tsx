import Link from "next/link";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Video, Sparkles, Trash2, Play } from "lucide-react";
import { generatedSessions } from "@/constants/mock-data";

export default function VideosPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-sage-900">My Sessions</h1>
            <p className="mt-2 text-sage-600">
              All your generated yoga sessions
            </p>
          </div>
          <Link href="/session/builder">
            <Button className="gap-2">
              <Sparkles className="h-5 w-5" />
              New Session
            </Button>
          </Link>
        </div>

        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {generatedSessions.map((session) => (
            <Card key={session.id} className="overflow-hidden">
              <div className="aspect-video bg-gradient-to-br from-sage-100 to-blue-100 flex items-center justify-center relative group">
                <Video className="h-12 w-12 text-sage-400" />
                <Link
                  href={`/videos/player?id=${session.id}`}
                  className="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center"
                >
                  <Play className="h-12 w-12 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                </Link>
              </div>
              <CardHeader>
                <div className="flex items-start justify-between gap-2">
                  <CardTitle className="text-base">
                    {session.routine.name}
                  </CardTitle>
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
              </CardHeader>
              <CardContent>
                <div className="space-y-2 text-sm text-sage-600">
                  <p>
                    <strong>Instructor:</strong> {session.avatar.name}
                  </p>
                  <p>
                    <strong>Voice:</strong> {session.voice.name}
                  </p>
                  <p>
                    <strong>Duration:</strong> {session.duration} minutes
                  </p>
                  <p className="text-xs">
                    {new Date(session.createdAt).toLocaleDateString("en-US", {
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                    })}
                  </p>
                </div>
                <div className="mt-4 flex gap-2">
                  <Link href={`/videos/player?id=${session.id}`} className="flex-1">
                    <Button variant="outline" size="sm" className="w-full">
                      Watch
                    </Button>
                  </Link>
                  <Button variant="ghost" size="sm">
                    <Trash2 className="h-4 w-4 text-red-600" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </DashboardLayout>
  );
}
