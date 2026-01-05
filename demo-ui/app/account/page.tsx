"use client";

import { useState } from "react";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { User, Mail, Calendar, CreditCard, Trash2 } from "lucide-react";
import { mockUser } from "@/constants/mock-data";

export default function AccountPage() {
  const [isEditing, setIsEditing] = useState(false);
  const [name, setName] = useState(mockUser.name);
  const [email, setEmail] = useState(mockUser.email);

  const handleSave = () => {
    setIsEditing(false);
  };

  return (
    <DashboardLayout>
      <div className="mx-auto max-w-4xl space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-sage-900">Account Settings</h1>
          <p className="mt-2 text-sage-600">
            Manage your account information and preferences
          </p>
        </div>

        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>Profile Information</CardTitle>
              {!isEditing ? (
                <Button variant="outline" onClick={() => setIsEditing(true)}>
                  Edit
                </Button>
              ) : (
                <div className="flex gap-2">
                  <Button
                    variant="ghost"
                    onClick={() => {
                      setIsEditing(false);
                      setName(mockUser.name);
                      setEmail(mockUser.email);
                    }}
                  >
                    Cancel
                  </Button>
                  <Button onClick={handleSave}>Save</Button>
                </div>
              )}
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center gap-4">
              <div className="flex h-20 w-20 items-center justify-center rounded-full bg-sage-200">
                <User className="h-10 w-10 text-sage-700" />
              </div>
              <div>
                <p className="font-medium text-sage-900">{mockUser.name}</p>
                <p className="text-sm text-sage-600">{mockUser.email}</p>
              </div>
            </div>

            <div className="space-y-4 border-t border-sage-100 pt-4">
              <div className="space-y-2">
                <Label htmlFor="name">Full Name</Label>
                <Input
                  id="name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  disabled={!isEditing}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="email">Email Address</Label>
                <Input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  disabled={!isEditing}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Account Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center gap-3">
              <CreditCard className="h-5 w-5 text-sage-600" />
              <div className="flex-1">
                <p className="text-sm font-medium text-sage-900">
                  Subscription Plan
                </p>
                <p className="text-sm text-sage-600 capitalize">
                  {mockUser.plan}
                </p>
              </div>
              <Badge variant="success">Active</Badge>
            </div>

            <div className="flex items-center gap-3">
              <Calendar className="h-5 w-5 text-sage-600" />
              <div className="flex-1">
                <p className="text-sm font-medium text-sage-900">
                  Member Since
                </p>
                <p className="text-sm text-sage-600">
                  {new Date(mockUser.memberSince).toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                  })}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <User className="h-5 w-5 text-sage-600" />
              <div className="flex-1">
                <p className="text-sm font-medium text-sage-900">User ID</p>
                <p className="text-sm text-sage-600">{mockUser.id}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Email Preferences</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <label className="flex items-center justify-between">
              <div>
                <p className="font-medium text-sage-900">
                  Video Generation Notifications
                </p>
                <p className="text-sm text-sage-600">
                  Receive emails when your videos are ready
                </p>
              </div>
              <input
                type="checkbox"
                defaultChecked
                className="h-5 w-5 rounded border-sage-300 text-sage-600 focus:ring-sage-500"
              />
            </label>

            <label className="flex items-center justify-between">
              <div>
                <p className="font-medium text-sage-900">Weekly Yoga Tips</p>
                <p className="text-sm text-sage-600">
                  Get weekly tips and routine suggestions
                </p>
              </div>
              <input
                type="checkbox"
                defaultChecked
                className="h-5 w-5 rounded border-sage-300 text-sage-600 focus:ring-sage-500"
              />
            </label>

            <label className="flex items-center justify-between">
              <div>
                <p className="font-medium text-sage-900">
                  Product Updates
                </p>
                <p className="text-sm text-sage-600">
                  Stay informed about new features and updates
                </p>
              </div>
              <input
                type="checkbox"
                defaultChecked
                className="h-5 w-5 rounded border-sage-300 text-sage-600 focus:ring-sage-500"
              />
            </label>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Password</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <p className="text-sm text-sage-600">
                Update your password to keep your account secure
              </p>
              <Button variant="outline">Change Password</Button>
            </div>
          </CardContent>
        </Card>

        <Card className="border-red-200">
          <CardHeader>
            <CardTitle className="text-red-900">Danger Zone</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <p className="text-sm text-sage-600">
                Once you delete your account, there is no going back. Please be
                certain.
              </p>
              <Button variant="danger" className="gap-2">
                <Trash2 className="h-4 w-4" />
                Delete Account
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  );
}
