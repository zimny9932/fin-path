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

const normalizeItem = (raw: any): SpendingCategoryReportDTO | null => {
  if (!raw) return null;

  const mainCategory = typeof raw.mainCategory === "string" ? raw.mainCategory : raw.category ?? raw.name;
  const amountValue =
    typeof raw.totalAmount?.amount === "number"
      ? raw.totalAmount.amount
      : typeof raw.totalAmount?.amount === "string"
        ? Number.parseFloat(raw.totalAmount.amount)
        : typeof raw.amount?.amount === "number"
          ? raw.amount.amount
          : typeof raw.amount === "number"
            ? raw.amount
            : null;

  const currency =
    typeof raw.totalAmount?.currency === "string"
      ? raw.totalAmount.currency
      : typeof raw.amount?.currency === "string"
        ? raw.amount.currency
        : "PLN";

  const percentage =
    typeof raw.percentageOfTotal === "number"
      ? raw.percentageOfTotal
      : typeof raw.percentage === "number"
        ? raw.percentage
        : typeof raw.percentage === "string"
          ? Number.parseFloat(raw.percentage)
          : null;

  if (!mainCategory || amountValue === null || Number.isNaN(amountValue) || percentage === null || Number.isNaN(percentage)) {
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

const extractItems = (response: any): SpendingCategoryReportDTO[] => {
  if (!response) return [];

  // plain array
  if (Array.isArray(response)) {
    return response.map(normalizeItem).filter(Boolean) as SpendingCategoryReportDTO[];
  }

  // ApiPlatform Hydra
  if (Array.isArray(response["hydra:member"])) {
    return response["hydra:member"].map(normalizeItem).filter(Boolean) as SpendingCategoryReportDTO[];
  }

  // ApiPlatform short keys
  if (Array.isArray(response.member)) {
    return response.member.map(normalizeItem).filter(Boolean) as SpendingCategoryReportDTO[];
  }

  // { items: [...] }
  if (Array.isArray(response.items)) {
    return response.items.map(normalizeItem).filter(Boolean) as SpendingCategoryReportDTO[];
  }

  return [];
};

export const getSpendingByCategory = async (
  range?: Range,
): Promise<SpendingCategoryReportDTO[]> => {
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
