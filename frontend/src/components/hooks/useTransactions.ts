import { useCallback, useEffect, useState } from "react";
import { apiFetch } from "@/lib/api";
import type {
  PaginationDTO,
  TransactionDTO,
  TransactionFilters,
  TransactionRowVM,
  TransactionsResponse,
} from "@/types";

const formatAmount = (amountInMinor: number, currency: string) =>
  new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amountInMinor / 100);

const mapTransaction = (dto: TransactionDTO): TransactionRowVM => ({
  id: dto.id,
  date: dto.date,
  formattedDate: new Date(dto.date).toLocaleDateString("pl-PL"),
  amount: dto.amount,
  formattedAmount: formatAmount(dto.amount.amount, dto.amount.currency),
  subcategoryId: dto.subcategory.id,
  type: dto.subcategory.type,
  subcategoryName: dto.subcategory.name,
  description: dto.description ?? null,
});

const buildQuery = (filters: TransactionFilters): string => {
  const params = new URLSearchParams();

  params.set("page", filters.page.toString());
  params.set("itemsPerPage", filters.limit.toString());

  if (filters.startDate) {
    params.set("date[after]", filters.startDate);
  }

  if (filters.endDate) {
    params.set("date[before]", filters.endDate);
  }

  const sortField = filters.sortBy === "amount" ? "amount.amount" : "date";
  params.set(`order[${sortField}]`, filters.sortOrder);

  return `/api/transactions?${params.toString()}`;
};

const normalizeResponse = (apiData: unknown, filters: TransactionFilters): TransactionsResponse => {
  if (apiData && typeof apiData === "object") {
    const obj = apiData as Record<string, unknown>;

    // ApiPlatform Hydra (standard)
    if (Array.isArray(obj["hydra:member"])) {
      const items = obj["hydra:member"] as TransactionDTO[];
      const totalItems =
        typeof obj["hydra:totalItems"] === "number" ? obj["hydra:totalItems"] : (obj["hydra:member"] as []).length;
      const totalPages = Math.max(1, Math.ceil(totalItems / filters.limit));

      return {
        items,
        pagination: {
          currentPage: filters.page,
          totalPages,
          totalItems,
        },
      };
    }

    // ApiPlatform with @context/@id (Accept: application/ld+json) but short keys
    if (Array.isArray(obj.member)) {
      const items = obj.member as TransactionDTO[];
      const totalItems = typeof obj.totalItems === "number" ? obj.totalItems : items.length;
      const totalPages = Math.max(1, Math.ceil(totalItems / filters.limit));

      return {
        items,
        pagination: {
          currentPage: filters.page,
          totalPages,
          totalItems,
        },
      };
    }

    // Custom shape with items + pagination
    if (Array.isArray(obj.items) && obj.pagination && typeof obj.pagination === "object") {
      return obj as TransactionsResponse;
    }
  }

  // Array fallback
  if (Array.isArray(apiData)) {
    return {
      items: apiData as TransactionDTO[],
      pagination: {
        currentPage: filters.page,
        totalItems: apiData.length,
        totalPages: 1,
      },
    };
  }

  return {
    items: [],
    pagination: {
      currentPage: filters.page,
      totalItems: 0,
      totalPages: 1,
    },
  };
};

export const useTransactions = (initialFilters: TransactionFilters) => {
  const [filters, setFilters] = useState<TransactionFilters>(initialFilters);
  const [data, setData] = useState<TransactionRowVM[]>([]);
  const [pagination, setPagination] = useState<PaginationDTO>({
    currentPage: initialFilters.page,
    totalItems: 0,
    totalPages: 1,
  });
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const toErrorMessage = (maybeError: unknown, fallback: string) => {
    if (typeof maybeError === "string") return maybeError;
    if (
      maybeError &&
      typeof maybeError === "object" &&
      "message" in maybeError &&
      typeof maybeError.message === "string"
    ) {
      return maybeError.message;
    }
    return fallback;
  };

  const fetchData = useCallback(async () => {
    if (filters.startDate && filters.endDate) {
      const start = new Date(filters.startDate);
      const end = new Date(filters.endDate);
      if (start > end) {
        setError("Data początkowa nie może być późniejsza niż końcowa.");
        return;
      }
    }

    setIsLoading(true);
    setError(null);

    try {
      const endpoint = buildQuery(filters);
      const response = await apiFetch(endpoint);
      const normalized = normalizeResponse(response, filters);

      setData(normalized.items.map(mapTransaction));
      setPagination(normalized.pagination);
    } catch (error: unknown) {
      setError(toErrorMessage(error, "Nie udało się pobrać transakcji."));
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  return {
    data,
    pagination,
    isLoading,
    error,
    filters,
    setFilters,
    refetch: fetchData,
  };
};
