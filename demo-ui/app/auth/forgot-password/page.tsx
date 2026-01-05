"use client";

import { useState } from "react";
import Link from "next/link";
import { Sparkles, ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export default function ForgotPasswordPage() {
  const [isLoading, setIsLoading] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setIsLoading(true);

    setTimeout(() => {
      setIsLoading(false);
      setIsSubmitted(true);
    }, 1000);
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-sage-50 via-cream-50 to-blue-50 p-4">
      <div className="w-full max-w-md">
        <div className="mb-8 text-center">
          <div className="mb-4 flex items-center justify-center gap-2">
            <Sparkles className="h-8 w-8 text-sage-600" />
            <h1 className="text-3xl font-bold text-sage-900">YogaAI</h1>
          </div>
          <p className="text-sage-600">Reset your password</p>
        </div>

        <div className="rounded-2xl border border-sage-100 bg-white p-8 shadow-lg">
          {!isSubmitted ? (
            <>
              <h2 className="mb-2 text-2xl font-semibold text-sage-900">
                Forgot Password
              </h2>
              <p className="mb-6 text-sm text-sage-600">
                Enter your email address and we&apos;ll send you a link to reset
                your password.
              </p>

              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="sarah@example.com"
                    required
                  />
                </div>

                <Button
                  type="submit"
                  className="w-full"
                  size="lg"
                  disabled={isLoading}
                >
                  {isLoading ? "Sending..." : "Send Reset Link"}
                </Button>
              </form>
            </>
          ) : (
            <div className="text-center">
              <div className="mb-4 flex h-16 w-16 mx-auto items-center justify-center rounded-full bg-sage-100">
                <Sparkles className="h-8 w-8 text-sage-600" />
              </div>
              <h2 className="mb-2 text-2xl font-semibold text-sage-900">
                Check Your Email
              </h2>
              <p className="mb-6 text-sm text-sage-600">
                We&apos;ve sent password reset instructions to your email
                address.
              </p>
            </div>
          )}

          <div className="mt-6 text-center">
            <Link
              href="/auth/login"
              className="inline-flex items-center gap-2 text-sm text-sage-600 hover:text-sage-800"
            >
              <ArrowLeft className="h-4 w-4" />
              Back to sign in
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
