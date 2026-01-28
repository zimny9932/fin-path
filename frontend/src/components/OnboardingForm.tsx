import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { apiFetch } from "@/lib/api";
import type { OnboardingFormViewModel } from "@/types";
import { useState } from "react";

const OnboardingForm = () => {
  const [viewModel, setViewModel] = useState<OnboardingFormViewModel>({
    selectedDay: null,
    isLoading: false,
    error: null,
  });

  const days = Array.from({ length: 31 }, (_, i) => i + 1);

  const handleDayChange = (value: string) => {
    setViewModel((prev) => ({
      ...prev,
      selectedDay: parseInt(value, 10),
      error: null,
    }));
  };

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!viewModel.selectedDay) {
      return;
    }

    setViewModel((prev) => ({ ...prev, isLoading: true, error: null }));

    try {
      await apiFetch("/api/users/me/onboarding", {
        method: "PATCH",
        body: JSON.stringify({
          billingCycleStartDay: viewModel.selectedDay,
        }),
      });

      window.location.href = "/budgets/plan";
    } catch (error: unknown) {
      const maybeError =
        typeof error === "object" && error !== null
          ? (error as { status?: number; message?: string })
          : { status: undefined, message: undefined };

      if (maybeError.status === 401) {
        // Potencjalnie przekierowanie na stronę logowania
        setViewModel((prev) => ({
          ...prev,
          error: "Sesja wygasła. Zaloguj się ponownie.",
        }));
      } else {
        setViewModel((prev) => ({
          ...prev,
          error: maybeError.message || "Wystąpił błąd. Spróbuj ponownie.",
        }));
      }
    } finally {
      setViewModel((prev) => ({ ...prev, isLoading: false }));
    }
  };

  return (
    <div className="space-y-6">
      <div className="text-center">
        <h1 className="text-3xl font-bold">Konfiguracja konta</h1>
        <p className="text-muted-foreground mt-2">
          Wybierz dzień, w którym rozpoczyna się Twój miesięczny cykl rozliczeniowy. To pomoże nam dokładnie śledzić
          Twoje finanse.
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="billing-day" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Dzień rozpoczęcia cyklu
          </label>
          <Select
            onValueChange={handleDayChange}
            value={viewModel.selectedDay?.toString() ?? ""}
            disabled={viewModel.isLoading}
          >
            <SelectTrigger id="billing-day" data-testid="billing-day">
              <SelectValue placeholder="Wybierz dzień..." />
            </SelectTrigger>
            <SelectContent>
              {days.map((day) => (
                <SelectItem key={day} value={day.toString()} data-testid={`billing-day-${day.toString()}`}>
                  {day}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <Button type="submit" className="w-full" disabled={!viewModel.selectedDay || viewModel.isLoading}>
          {viewModel.isLoading ? "Zapisywanie..." : "Zapisz i kontynuuj"}
        </Button>
      </form>

      {viewModel.error && <p className="text-sm text-red-500 text-center">{viewModel.error}</p>}
    </div>
  );
};

export default OnboardingForm;
