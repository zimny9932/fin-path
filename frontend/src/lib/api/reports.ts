import { apiFetch } from "@/lib/api";
import type { Range, SpendingCategoryReportDTO } from "@/types";

const buildEndpoint = (range?: Range): string => {
  if (!range || (!range.startDate && !range.endDate)) {
    return "/api/reports/spending-by-category";
  }

  const params = new URLSearchParams();

  if (range.startDate) {
    params.set("startDate", range.startDate);
  }

  if (range.endDate) {
    params.set("endDate", range.endDate);
  }

  return `/api/reports/spending-by-category?${params.toString()}`;
};

const normalizeItem = (raw: unknown): SpendingCategoryReportDTO | null => {
  if (!raw || typeof raw !== "object") return null;

  const data = raw as Record<string, unknown>;

  const mainCategory =
    typeof data.mainCategory === "string"
      ? data.mainCategory
      : typeof data.category === "string"
        ? data.category
        : typeof data.name === "string"
          ? data.name
          : null;

  const amountValue =
    typeof (data.totalAmount as { amount?: unknown })?.amount === "number"
      ? (data.totalAmount as { amount?: number }).amount
      : typeof (data.totalAmount as { amount?: unknown })?.amount === "string"
        ? Number.parseFloat((data.totalAmount as { amount?: string }).amount)
        : typeof (data.amount as { amount?: unknown })?.amount === "number"
          ? (data.amount as { amount: number }).amount
          : typeof data.amount === "number"
            ? data.amount
            : null;

  const currency =
    typeof (data.totalAmount as { currency?: unknown })?.currency === "string"
      ? (data.totalAmount as { currency: string }).currency
      : typeof (data.amount as { currency?: unknown })?.currency === "string"
        ? (data.amount as { currency: string }).currency
        : "PLN";

  const percentage =
    typeof data.percentageOfTotal === "number"
      ? data.percentageOfTotal
      : typeof data.percentage === "number"
        ? data.percentage
        : typeof data.percentage === "string"
          ? Number.parseFloat(data.percentage)
          : null;

  if (
    !mainCategory ||
    amountValue === null ||
    Number.isNaN(amountValue) ||
    percentage === null ||
    Number.isNaN(percentage)
  ) {
    return null;
  }

  return {
    mainCategory,
    totalAmount: {
      amount: amountValue,
      currency,
    },
    percentageOfTotal: percentage,
  };
};

const extractItems = (response: unknown): SpendingCategoryReportDTO[] => {
  if (!response) return [];

  // plain array
  if (Array.isArray(response)) {
    return response.map(normalizeItem).filter(Boolean) as SpendingCategoryReportDTO[];
  }

  // ApiPlatform Hydra
  if (
    response &&
    typeof response === "object" &&
    Array.isArray((response as Record<string, unknown>)["hydra:member"])
  ) {
    return (response as Record<string, unknown>)["hydra:member"]
      .map(normalizeItem)
      .filter(Boolean) as SpendingCategoryReportDTO[];
  }

  // ApiPlatform short keys
  if (response && typeof response === "object" && Array.isArray((response as Record<string, unknown>).member)) {
    const members = (response as Record<string, unknown>).member;
    return members.map(normalizeItem).filter(Boolean) as SpendingCategoryReportDTO[];
  }

  // { items: [...] }
  if (response && typeof response === "object" && Array.isArray((response as Record<string, unknown>).items)) {
    const items = (response as Record<string, unknown>).items;
    return items.map(normalizeItem).filter(Boolean) as SpendingCategoryReportDTO[];
  }

  return [];
};

export const getSpendingByCategory = async (range?: Range): Promise<SpendingCategoryReportDTO[]> => {
  const response = await apiFetch(buildEndpoint(range));
  const items = extractItems(response);

  if (!items.length) {
    throw {
      status: 500,
      message: "Nie udało się odczytać danych raportu.",
    };
  }

  return items;
};
