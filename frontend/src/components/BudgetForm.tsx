import React, { useEffect, useMemo } from "react";
import { useBudgetForm } from "./hooks/useBudgetForm";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import CurrencyInput from "@/components/ui/CurrencyInput";
import BudgetCategoryList from "@/components/BudgetCategoryList";
import { Skeleton } from "@/components/ui/skeleton";
import { getAuthToken } from "@/lib/auth";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { AlertTriangle } from "lucide-react";

const BudgetForm = () => {
  useEffect(() => {
    const token = getAuthToken();
    if (!token) {
      window.location.href = "/login";
    }
  }, []);

  // Na razie na sztywno, docelowo z URL'a lub globalnego stanu
  const year = new Date().getFullYear();
  const month = new Date().getMonth() + 1;

  const {
    budget,
    isLoading,
    isSaving,
    groupedLimits,
    totalLimits,
    updatePlannedIncome,
    updateLimit,
    handleSave,
    handleCopyFromPreviousMonth,
    error,
  } = useBudgetForm(year, month);

  const hasOverspending = useMemo(() => {
    if (!budget) return false;
    return totalLimits > budget.plannedIncome;
  }, [budget, totalLimits]);

  if (error) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Wystąpił błąd</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-red-500">{error}</p>
          <p className="mt-4">Spróbuj odświeżyć stronę lub zaloguj się ponownie.</p>
          <Button asChild className="mt-4">
            <a href="/login">Przejdź do logowania</a>
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (isLoading || !budget) {
    return (
      <Card>
        <CardHeader>
          <Skeleton className="h-8 w-1/2" />
          <Skeleton className="h-4 w-1/3" />
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="space-y-2">
            <Skeleton className="h-4 w-1/4" />
            <Skeleton className="h-10 w-full" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-1/4" />
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
          </div>
        </CardContent>
        <CardFooter className="flex justify-between">
          <Skeleton className="h-10 w-48" />
          <Skeleton className="h-10 w-24" />
        </CardFooter>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>
          Plan budżetu na {month}/{year}
        </CardTitle>
        <CardDescription>Zaplanuj swoje przychody i zdefiniuj limity wydatków na ten miesiąc.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="space-y-2">
          <label htmlFor="plannedIncome" className="font-medium">
            Planowane przychody
          </label>
          <CurrencyInput value={budget.plannedIncome} onChange={updatePlannedIncome} />
        </div>

        <div>
          <h3 className="text-lg font-medium mb-2">Limity wydatków</h3>
          <BudgetCategoryList groupedLimits={groupedLimits} onLimitChange={updateLimit} />
        </div>

        <div className="text-right font-semibold">
          Suma limitów: {(totalLimits / 100).toFixed(2)} {budget.currency}
        </div>

        {hasOverspending && (
          <Alert variant="destructive">
            <AlertTriangle className="h-4 w-4" />
            <AlertTitle>Ostrzeżenie</AlertTitle>
            <AlertDescription>Suma planowanych wydatków przekracza Twoje planowane przychody.</AlertDescription>
          </Alert>
        )}
      </CardContent>
      <CardFooter className="flex justify-between">
        <Button variant="outline" onClick={handleCopyFromPreviousMonth} disabled={isSaving}>
          Kopiuj z poprzedniego miesiąca
        </Button>
        <Button onClick={handleSave} disabled={isSaving}>
          {isSaving ? "Zapisywanie..." : "Zapisz"}
        </Button>
      </CardFooter>
    </Card>
  );
};

export default BudgetForm;
