import Link from "next/link";
import { Video, TrendingUp, Calendar, Sparkles } from "lucide-react";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { mockUser, generatedSessions } from "@/constants/mock-data";

export default function DashboardPage() {
  const recentSessions = generatedSessions.slice(0, 3);
  const usagePercentage = (mockUser.videosThisMonth / mockUser.videosLimit) * 100;

  return (
    <DashboardLayout>
      <div className="space-y-8">
        <div>
          <h1 className="text-3xl font-bold text-sage-900">
            Welcome back, {mockUser.name.split(" ")[0]}! 🧘‍♀️
          </h1>
          <p className="mt-2 text-sage-600">
            Ready to continue your practice?
          </p>
        </div>

        <div className="grid gap-6 md:grid-cols-3">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-sage-600">
                Videos This Month
              </CardTitle>
              <Video className="h-4 w-4 text-sage-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-sage-900">
                {mockUser.videosThisMonth} / {mockUser.videosLimit}
              </div>
              <Progress
                value={mockUser.videosThisMonth}
                max={mockUser.videosLimit}
                className="mt-3"
              />
              <p className="mt-2 text-xs text-sage-600">
                {mockUser.videosLimit - mockUser.videosThisMonth} videos
                remaining
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-sage-600">
                Current Plan
              </CardTitle>
              <TrendingUp className="h-4 w-4 text-sage-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-sage-900 capitalize">
                {mockUser.plan}
              </div>
              <p className="mt-2 text-xs text-sage-600">
                Member since {new Date(mockUser.memberSince).toLocaleDateString()}
              </p>
              <Link href="/subscription">
                <Button variant="outline" size="sm" className="mt-3 w-full">
                  Manage Plan
                </Button>
              </Link>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-sage-600">
                Total Sessions
              </CardTitle>
              <Calendar className="h-4 w-4 text-sage-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-sage-900">
                {generatedSessions.length}
              </div>
              <p className="mt-2 text-xs text-sage-600">
                All-time generated videos
              </p>
            </CardContent>
          </Card>
        </div>

        <Card className="border-2 border-sage-200 bg-gradient-to-br from-sage-50 to-cream-50">
          <CardContent className="flex items-center justify-between p-8">
            <div>
              <h3 className="text-xl font-semibold text-sage-900">
                Generate a New Session
              </h3>
              <p className="mt-2 text-sage-600">
                Create a personalized yoga video with AI
              </p>
            </div>
            <Link href="/session/builder">
              <Button size="lg" className="gap-2">
                <Sparkles className="h-5 w-5" />
                Start Building
              </Button>
            </Link>
          </CardContent>
        </Card>

        <div>
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-xl font-semibold text-sage-900">
              Recent Sessions
            </h2>
            <Link href="/videos">
              <Button variant="ghost" size="sm">
                View All
              </Button>
            </Link>
          </div>

          <div className="grid gap-6 md:grid-cols-3">
            {recentSessions.map((session) => (
              <Card key={session.id} className="overflow-hidden">
                <div className="aspect-video bg-gradient-to-br from-sage-100 to-blue-100 flex items-center justify-center">
                  <Video className="h-12 w-12 text-sage-400" />
                </div>
                <CardHeader>
                  <div className="flex items-start justify-between gap-2">
                    <CardTitle className="text-base">
                      {session.routine.name}
                    </CardTitle>
                    <Badge variant="default">
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
                      <strong>Duration:</strong> {session.duration} minutes
                    </p>
                    <p className="text-xs">
                      {new Date(session.createdAt).toLocaleDateString()}
                    </p>
                  </div>
                  <Link href={`/videos/player?id=${session.id}`}>
                    <Button variant="outline" size="sm" className="mt-4 w-full">
                      Watch Video
                    </Button>
                  </Link>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}
