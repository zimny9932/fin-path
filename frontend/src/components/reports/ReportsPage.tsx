import { useMemo, useState } from "react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { formatMoney } from "@/lib/money";
import CycleSwitcher from "@/components/reports/CycleSwitcher";
import EmptyState from "@/components/reports/EmptyState";
import ErrorBanner from "@/components/reports/ErrorBanner";
import ReportsSkeleton from "@/components/reports/ReportsSkeleton";
import SpendingChart from "@/components/reports/SpendingChart";
import SpendingTable from "@/components/reports/SpendingTable";
import { useSpendingByCategory } from "@/components/hooks/useSpendingByCategory";
import type { Range } from "@/types";

const ReportsPage = () => {
  const {
    range,
    setRange,
    chartData,
    rows,
    totalSpent,
    loading,
    error,
    hasData,
    viewModel,
    refetch,
  } = useSpendingByCategory();
  const [chartMode, setChartMode] = useState<"pie" | "bar">(() => {
    if (typeof window === "undefined") return "pie";
    const saved = window.localStorage.getItem("finpath_reports_chart_mode");
    return saved === "bar" ? "bar" : "pie";
  });

  const handleRangeChange = (next: Range) => setRange(next);

  const handleApply = async (next: Range) => {
    setRange(next);
    await refetch(next);
  };

  const totalLabel = useMemo(
    () => formatMoney(totalSpent.amount, totalSpent.currency),
    [totalSpent],
  );

  const handleChartModeChange = (mode: "pie" | "bar") => {
    setChartMode(mode);
    if (typeof window !== "undefined") {
      window.localStorage.setItem("finpath_reports_chart_mode", mode);
    }
  };

  return (
    <div className="flex flex-col gap-6">
      <header className="flex flex-col gap-2">
        <p className="text-sm text-muted-foreground">FinPath</p>
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 className="text-3xl font-semibold tracking-tight">Raporty</h1>
            <p className="text-muted-foreground">
              Struktura wydatków w wybranym zakresie dat.
            </p>
          </div>
          <Card className="w-full max-w-xs">
            <CardHeader className="pb-2">
              <CardTitle className="text-sm text-muted-foreground">Suma wydatków</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-semibold">{totalLabel}</p>
              <p className="text-xs text-muted-foreground">Zakres: {viewModel.range.startDate ?? "domyślny"} – {viewModel.range.endDate ?? "domyślny"}</p>
            </CardContent>
          </Card>
        </div>
        <CycleSwitcher
          value={range}
          onChange={handleRangeChange}
          onApply={handleApply}
          isLoading={loading}
          externalError={error}
        />
      </header>

      {loading && <ReportsSkeleton />}

      {error && !loading && <ErrorBanner message={error} onRetry={() => refetch(range)} />}

      {!loading && !error && !hasData && (
        <EmptyState onCtaClick={() => (window.location.href = "/transactions")} />
      )}

      {!loading && !error && hasData && (
        <div className="grid gap-4 md:grid-cols-2">
          <SpendingChart
            data={chartData}
            currency={totalSpent.currency}
            mode={chartMode}
            onModeChange={handleChartModeChange}
            isLoading={loading}
          />
          <SpendingTable rows={rows} currency={totalSpent.currency} isLoading={loading} />
        </div>
      )}
    </div>
  );
};

export default ReportsPage;
