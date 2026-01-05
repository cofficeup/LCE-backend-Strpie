"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Spinner } from "@/components/ui/spinner";
import {
  ArrowRight,
  ArrowLeft,
  Check,
  User,
  Volume2,
  Sparkles,
} from "lucide-react";
import {
  yogaRoutines,
  avatars,
  voices,
  YogaRoutine,
  Avatar,
  Voice,
} from "@/constants/mock-data";

export default function SessionBuilderPage() {
  const router = useRouter();
  const [step, setStep] = useState(1);
  const [selectedRoutine, setSelectedRoutine] = useState<YogaRoutine | null>(
    null
  );
  const [selectedAvatar, setSelectedAvatar] = useState<Avatar | null>(null);
  const [selectedVoice, setSelectedVoice] = useState<Voice | null>(null);
  const [isGenerating, setIsGenerating] = useState(false);

  const handleNext = () => {
    if (step < 4) {
      setStep(step + 1);
    }
  };

  const handleBack = () => {
    if (step > 1) {
      setStep(step - 1);
    }
  };

  const handleGenerate = () => {
    setIsGenerating(true);
    setTimeout(() => {
      router.push("/videos/player?id=session-1&new=true");
    }, 3000);
  };

  const canProceed = () => {
    switch (step) {
      case 1:
        return selectedRoutine !== null;
      case 2:
        return selectedAvatar !== null;
      case 3:
        return selectedVoice !== null;
      case 4:
        return true;
      default:
        return false;
    }
  };

  return (
    <DashboardLayout>
      <div className="mx-auto max-w-5xl">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-sage-900">
            Build Your Session
          </h1>
          <p className="mt-2 text-sage-600">
            Customize your AI-powered yoga experience
          </p>
        </div>

        <div className="mb-8 flex items-center justify-center gap-2">
          {[1, 2, 3, 4].map((s) => (
            <div key={s} className="flex items-center">
              <div
                className={`flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold ${
                  s <= step
                    ? "bg-sage-600 text-white"
                    : "bg-sage-100 text-sage-400"
                }`}
              >
                {s < step ? <Check className="h-5 w-5" /> : s}
              </div>
              {s < 4 && (
                <div
                  className={`h-1 w-16 ${
                    s < step ? "bg-sage-600" : "bg-sage-100"
                  }`}
                />
              )}
            </div>
          ))}
        </div>

        <div className="mb-6">
          {step === 1 && (
            <>
              <h2 className="mb-4 text-2xl font-semibold text-sage-900">
                Select Your Yoga Routine
              </h2>
              <div className="grid gap-6 md:grid-cols-2">
                {yogaRoutines.map((routine) => (
                  <Card
                    key={routine.id}
                    className={`cursor-pointer transition-all hover:shadow-md ${
                      selectedRoutine?.id === routine.id
                        ? "border-2 border-sage-600 ring-2 ring-sage-200"
                        : ""
                    }`}
                    onClick={() => setSelectedRoutine(routine)}
                  >
                    <div className="aspect-video bg-gradient-to-br from-sage-100 to-blue-100 flex items-center justify-center">
                      <Sparkles className="h-12 w-12 text-sage-400" />
                    </div>
                    <CardContent className="p-6">
                      <div className="mb-2 flex items-center justify-between">
                        <h3 className="text-lg font-semibold text-sage-900">
                          {routine.name}
                        </h3>
                        <Badge
                          variant={
                            routine.difficulty === "beginner"
                              ? "success"
                              : routine.difficulty === "intermediate"
                              ? "warning"
                              : "danger"
                          }
                        >
                          {routine.difficulty}
                        </Badge>
                      </div>
                      <p className="mb-4 text-sm text-sage-600">
                        {routine.description}
                      </p>
                      <div className="space-y-2 text-sm">
                        <p className="text-sage-900">
                          <strong>Duration:</strong> {routine.duration} minutes
                        </p>
                        <p className="text-sage-900">
                          <strong>Key Poses:</strong>{" "}
                          {routine.poses.slice(0, 3).join(", ")}
                        </p>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </>
          )}

          {step === 2 && (
            <>
              <h2 className="mb-4 text-2xl font-semibold text-sage-900">
                Choose Your Instructor Avatar
              </h2>
              <div className="grid gap-6 md:grid-cols-3">
                {avatars.map((avatar) => (
                  <Card
                    key={avatar.id}
                    className={`cursor-pointer transition-all hover:shadow-md ${
                      selectedAvatar?.id === avatar.id
                        ? "border-2 border-sage-600 ring-2 ring-sage-200"
                        : ""
                    }`}
                    onClick={() => setSelectedAvatar(avatar)}
                  >
                    <div className="aspect-square bg-gradient-to-br from-cream-100 to-sage-100 flex items-center justify-center">
                      <User className="h-20 w-20 text-sage-400" />
                    </div>
                    <CardContent className="p-6">
                      <h3 className="mb-1 text-lg font-semibold text-sage-900">
                        {avatar.name}
                      </h3>
                      <Badge variant="default" className="mb-3">
                        {avatar.style} Style
                      </Badge>
                      <p className="text-sm text-sage-600">
                        {avatar.description}
                      </p>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </>
          )}

          {step === 3 && (
            <>
              <h2 className="mb-4 text-2xl font-semibold text-sage-900">
                Select Voice
              </h2>
              <div className="grid gap-6 md:grid-cols-2">
                {voices.map((voice) => (
                  <Card
                    key={voice.id}
                    className={`cursor-pointer transition-all hover:shadow-md ${
                      selectedVoice?.id === voice.id
                        ? "border-2 border-sage-600 ring-2 ring-sage-200"
                        : ""
                    }`}
                    onClick={() => setSelectedVoice(voice)}
                  >
                    <CardContent className="p-8">
                      <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sage-100">
                        <Volume2 className="h-8 w-8 text-sage-600" />
                      </div>
                      <h3 className="mb-2 text-xl font-semibold text-sage-900">
                        {voice.name}
                      </h3>
                      <p className="mb-2 text-sm font-medium text-sage-700">
                        {voice.tone}
                      </p>
                      <p className="mb-4 text-sm text-sage-600">
                        {voice.description}
                      </p>
                      <Button variant="outline" size="sm">
                        Play Sample
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </>
          )}

          {step === 4 && !isGenerating && (
            <>
              <h2 className="mb-4 text-2xl font-semibold text-sage-900">
                Review & Generate
              </h2>
              <Card>
                <CardContent className="p-8">
                  <div className="space-y-6">
                    <div>
                      <h3 className="mb-2 text-sm font-medium text-sage-600">
                        Yoga Routine
                      </h3>
                      <p className="text-lg font-semibold text-sage-900">
                        {selectedRoutine?.name}
                      </p>
                      <p className="mt-1 text-sm text-sage-600">
                        {selectedRoutine?.duration} minutes •{" "}
                        {selectedRoutine?.difficulty}
                      </p>
                    </div>

                    <div>
                      <h3 className="mb-2 text-sm font-medium text-sage-600">
                        Instructor Avatar
                      </h3>
                      <p className="text-lg font-semibold text-sage-900">
                        {selectedAvatar?.name}
                      </p>
                      <p className="mt-1 text-sm text-sage-600">
                        {selectedAvatar?.style} Style
                      </p>
                    </div>

                    <div>
                      <h3 className="mb-2 text-sm font-medium text-sage-600">
                        Voice
                      </h3>
                      <p className="text-lg font-semibold text-sage-900">
                        {selectedVoice?.name}
                      </p>
                      <p className="mt-1 text-sm text-sage-600">
                        {selectedVoice?.tone}
                      </p>
                    </div>

                    <div className="border-t border-sage-200 pt-6">
                      <p className="text-sm text-sage-600">
                        <strong>Estimated Generation Time:</strong> 2-3 minutes
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </>
          )}

          {isGenerating && (
            <Card>
              <CardContent className="flex flex-col items-center justify-center p-12">
                <Spinner size="lg" className="mb-6 text-sage-600" />
                <h3 className="mb-2 text-xl font-semibold text-sage-900">
                  Generating Your Video...
                </h3>
                <p className="text-center text-sage-600">
                  Our AI is creating your personalized yoga session.
                  <br />
                  This may take a few moments.
                </p>
              </CardContent>
            </Card>
          )}
        </div>

        <div className="flex items-center justify-between">
          <Button
            variant="ghost"
            onClick={handleBack}
            disabled={step === 1 || isGenerating}
            className="gap-2"
          >
            <ArrowLeft className="h-4 w-4" />
            Back
          </Button>

          {step < 4 ? (
            <Button
              onClick={handleNext}
              disabled={!canProceed()}
              className="gap-2"
            >
              Next
              <ArrowRight className="h-4 w-4" />
            </Button>
          ) : (
            !isGenerating && (
              <Button
                onClick={handleGenerate}
                disabled={!canProceed()}
                className="gap-2"
                size="lg"
              >
                <Sparkles className="h-5 w-5" />
                Generate Video
              </Button>
            )
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}
