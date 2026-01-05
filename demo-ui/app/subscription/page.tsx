import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Check, Crown, Zap } from "lucide-react";
import {
  mockUser,
  subscriptionPlans,
  billingHistory,
} from "@/constants/mock-data";

export default function SubscriptionPage() {
  const currentPlan = subscriptionPlans.find(
    (plan) => plan.name.toLowerCase() === mockUser.plan
  );

  return (
    <DashboardLayout>
      <div className="space-y-8">
        <div>
          <h1 className="text-3xl font-bold text-sage-900">Subscription</h1>
          <p className="mt-2 text-sage-600">
            Manage your plan and billing information
          </p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Current Plan</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between">
              <div>
                <div className="flex items-center gap-2">
                  <h3 className="text-2xl font-bold text-sage-900 capitalize">
                    {mockUser.plan}
                  </h3>
                  <Badge variant="success">Active</Badge>
                </div>
                <p className="mt-1 text-sage-600">
                  ${currentPlan?.price}/month
                </p>
              </div>
              <Button variant="outline">Manage Billing</Button>
            </div>

            <div className="mt-6">
              <div className="mb-2 flex items-center justify-between text-sm">
                <span className="text-sage-600">Videos this month</span>
                <span className="font-medium text-sage-900">
                  {mockUser.videosThisMonth} / {mockUser.videosLimit}
                </span>
              </div>
              <Progress
                value={mockUser.videosThisMonth}
                max={mockUser.videosLimit}
              />
              <p className="mt-2 text-xs text-sage-600">
                Resets on the 1st of each month
              </p>
            </div>
          </CardContent>
        </Card>

        <div>
          <h2 className="mb-4 text-2xl font-semibold text-sage-900">
            Available Plans
          </h2>
          <div className="grid gap-6 md:grid-cols-3">
            {subscriptionPlans.map((plan) => {
              const isCurrent = plan.name.toLowerCase() === mockUser.plan;

              return (
                <Card
                  key={plan.id}
                  className={`relative ${
                    plan.popular
                      ? "border-2 border-sage-600 shadow-lg"
                      : ""
                  }`}
                >
                  {plan.popular && (
                    <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                      <Badge className="gap-1 bg-sage-600">
                        <Crown className="h-3 w-3" />
                        Most Popular
                      </Badge>
                    </div>
                  )}
                  <CardHeader>
                    <CardTitle className="text-2xl">{plan.name}</CardTitle>
                    <div className="mt-2">
                      <span className="text-4xl font-bold text-sage-900">
                        ${plan.price}
                      </span>
                      <span className="text-sage-600">/month</span>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div className="mb-6">
                      <p className="text-sm font-medium text-sage-900">
                        {plan.videosPerMonth === -1
                          ? "Unlimited videos"
                          : `${plan.videosPerMonth} ${
                              plan.videosPerMonth === 1 ? "video" : "videos"
                            } per month`}
                      </p>
                    </div>

                    <ul className="mb-6 space-y-3">
                      {plan.features.map((feature, index) => (
                        <li
                          key={index}
                          className="flex items-start gap-2 text-sm text-sage-600"
                        >
                          <Check className="h-5 w-5 shrink-0 text-sage-600" />
                          <span>{feature}</span>
                        </li>
                      ))}
                    </ul>

                    {isCurrent ? (
                      <Button variant="outline" disabled className="w-full">
                        Current Plan
                      </Button>
                    ) : (
                      <Button
                        variant={plan.popular ? "primary" : "outline"}
                        className="w-full"
                      >
                        {plan.price > (currentPlan?.price || 0)
                          ? "Upgrade"
                          : "Downgrade"}
                      </Button>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Billing History</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-sage-200">
                    <th className="pb-3 text-left text-sm font-medium text-sage-600">
                      Date
                    </th>
                    <th className="pb-3 text-left text-sm font-medium text-sage-600">
                      Description
                    </th>
                    <th className="pb-3 text-left text-sm font-medium text-sage-600">
                      Amount
                    </th>
                    <th className="pb-3 text-left text-sm font-medium text-sage-600">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {billingHistory.map((item) => (
                    <tr key={item.id} className="border-b border-sage-100">
                      <td className="py-3 text-sm text-sage-900">
                        {new Date(item.date).toLocaleDateString()}
                      </td>
                      <td className="py-3 text-sm text-sage-900">
                        {item.description}
                      </td>
                      <td className="py-3 text-sm text-sage-900">
                        ${item.amount.toFixed(2)}
                      </td>
                      <td className="py-3">
                        <Badge
                          variant={
                            item.status === "paid"
                              ? "success"
                              : item.status === "pending"
                              ? "warning"
                              : "danger"
                          }
                        >
                          {item.status}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  );
}
