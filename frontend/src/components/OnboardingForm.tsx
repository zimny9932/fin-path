import { useState } from "react";
import type { OnboardingFormViewModel, OnboardingRequestDTO } from "@/types";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

export default function OnboardingForm() {
  const [viewModel, setViewModel] = useState<OnboardingFormViewModel>({
    selectedDay: null,
    isLoading: false,
    error: null,
  });

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!viewModel.selectedDay) {
      return;
    }

    setViewModel((prev) => ({ ...prev, isLoading: true, error: null }));

    try {
      const body: OnboardingRequestDTO = {
        billingCycleStartDay: viewModel.selectedDay,
      };

      const response = await fetch("/api/users/me/onboarding", {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(body),
      });

      if (response.ok) {
        window.location.href = "/budget/new";
      } else {
        const errorData = await response.json();
        setViewModel((prev) => ({
          ...prev,
          isLoading: false,
          error:
            errorData.message ||
            "An unexpected error occurred. Please try again.",
        }));
      }
    } catch (error) {
      setViewModel((prev) => ({
        ...prev,
        isLoading: false,
        error: "A network error occurred. Please check your connection.",
      }));
    }
  };

  const handleDayChange = (value: string) => {
    setViewModel((prev) => ({
      ...prev,
      selectedDay: Number(value),
      error: null,
    }));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <label htmlFor="billing-day-select" className="text-sm font-medium">
          Billing cycle start day
        </label>
        <Select onValueChange={handleDayChange}>
          <SelectTrigger id="billing-day-select">
            <SelectValue placeholder="Select a day" />
          </SelectTrigger>
          <SelectContent>
            {Array.from({ length: 31 }, (_, i) => (
              <SelectItem key={i + 1} value={String(i + 1)}>
                {i + 1}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      <Button
        type="submit"
        className="w-full"
        disabled={!viewModel.selectedDay || viewModel.isLoading}
      >
        {viewModel.isLoading ? "Saving..." : "Save"}
      </Button>
      {viewModel.error && (
        <p className="text-sm text-center text-red-500">{viewModel.error}</p>
      )}
    </form>
  );
}
