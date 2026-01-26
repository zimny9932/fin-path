import { useCallback, useEffect, useMemo, useState } from "react";

import { getSpendingByCategory } from "@/lib/api/reports";
import type {
  ChartDatum,
  MoneyDTO,
  Range,
  ReportsViewModel,
  SpendingCategoryReportDTO,
  TableRow,
} from "@/types";

const DATE_REGEX = /^\d{4}-\d{2}-\d{2}$/;

const isValidDate = (value?: string): boolean => {
  if (!value) return true;
  return DATE_REGEX.test(value) && !Number.isNaN(Date.parse(value));
};

const validateRange = (range: Range): string | null => {
  const { startDate, endDate } = range;

  if (!isValidDate(startDate) || !isValidDate(endDate)) {
    return "Daty muszą być w formacie RRRR-MM-DD.";
  }

  if (startDate && endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    if (start > end) {
      return "Data początkowa nie może być późniejsza niż końcowa.";
    }
  }

  return null;
};

const mapRows = (items: SpendingCategoryReportDTO[], currency: MoneyDTO["currency"]): TableRow[] =>
  items.map((item) => ({
    category: item.mainCategory,
    amount: Math.max(0, item.totalAmount?.amount ?? 0),
    currency,
    percentage: Math.max(0, item.percentageOfTotal ?? 0),
  }));

const mapChartData = (rows: TableRow[]): ChartDatum[] =>
  rows
    .filter((row) => row.amount > 0)
    .map((row) => ({
      label: row.category,
      value: row.amount,
      percentage: row.percentage,
    }));

export const useSpendingByCategory = (initialRange: Range = {}) => {
  const [range, setRange] = useState<Range>(initialRange);
  const [data, setData] = useState<SpendingCategoryReportDTO[]>([]);
  const [rows, setRows] = useState<TableRow[]>([]);
  const [chartData, setChartData] = useState<ChartDatum[]>([]);
  const [totalSpent, setTotalSpent] = useState<MoneyDTO>({
    amount: 0,
    currency: "PLN",
  });
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const fetchData = useCallback(
    async (overrideRange?: Range) => {
      const nextRange = overrideRange ?? range;
      const validationError = validateRange(nextRange);
      if (validationError) {
        setError(validationError);
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const response = await getSpendingByCategory(nextRange);
        const sanitizedItems = response.filter(
          (item) =>
            item &&
            typeof item.mainCategory === "string" &&
            typeof item.totalAmount?.amount === "number" &&
            typeof item.percentageOfTotal === "number",
        );

        const currency = sanitizedItems[0]?.totalAmount?.currency ?? "PLN";
        const computedTotal = sanitizedItems.reduce(
          (acc, item) => acc + Math.max(0, item.totalAmount.amount ?? 0),
          0,
        );

        const mappedRows = mapRows(sanitizedItems, currency).sort(
          (a, b) => b.amount - a.amount,
        );

        setData(sanitizedItems);
        setRows(mappedRows);
        setChartData(mapChartData(mappedRows));
        setTotalSpent({ amount: computedTotal, currency });
      } catch (e: any) {
        if (e?.status === 401) {
          setError("Sesja wygasła. Zaloguj się ponownie.");
          return;
        }
        if (e?.status === 400) {
          setError(e?.message ?? "Zakres dat jest nieprawidłowy.");
          return;
        }
        setError(e?.message ?? "Nie udało się pobrać raportu.");
      } finally {
        setLoading(false);
      }
    },
    [range],
  );

  useEffect(() => {
    void fetchData();
  }, [fetchData]);

  const hasData = useMemo(
    () => rows.some((row) => row.amount > 0),
    [rows],
  );

  const viewModel: ReportsViewModel = useMemo(
    () => ({
      range,
      items: data,
      totalSpent,
      hasData,
    }),
    [data, hasData, range, totalSpent],
  );

  return {
    range,
    setRange,
    data,
    rows,
    chartData,
    totalSpent,
    loading,
    error,
    hasData,
    viewModel,
    refetch: fetchData,
  };
};
