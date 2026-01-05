"use client";

import { Bell, User } from "lucide-react";
import { mockUser } from "@/constants/mock-data";

export function Header() {
  return (
    <header className="fixed left-64 right-0 top-0 z-30 h-16 border-b border-sage-100 bg-white/80 backdrop-blur-sm">
      <div className="flex h-full items-center justify-between px-8">
        <div className="flex-1" />

        <div className="flex items-center gap-4">
          <button className="relative rounded-full p-2 text-sage-600 hover:bg-sage-50">
            <Bell className="h-5 w-5" />
            <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500" />
          </button>

          <div className="flex items-center gap-3">
            <div className="h-9 w-9 rounded-full bg-sage-200 flex items-center justify-center">
              <User className="h-5 w-5 text-sage-700" />
            </div>
            <div className="text-sm">
              <p className="font-medium text-sage-900">{mockUser.name}</p>
              <p className="text-sage-600 capitalize">{mockUser.plan} Plan</p>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
