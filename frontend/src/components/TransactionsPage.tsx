import { useMemo, useState } from "react";
import { useTransactions } from "./hooks/useTransactions";
import type { TransactionFilters, TransactionFormData } from "@/types";
import { Button } from "@/components/ui/button";
import FiltersBar from "./transactions/FiltersBar";
import PaginationBar from "./transactions/PaginationBar";
import TransactionsTable from "./transactions/TransactionsTable";
import TransactionsList from "./transactions/TransactionsList";
import AddTransactionModal from "./transactions/AddTransactionModal";
import { apiFetch } from "@/lib/api";
import { toast } from "sonner";
import SkeletonLoader from "./transactions/SkeletonLoader";
import EmptyState from "./transactions/EmptyState";
import ErrorAlert from "./transactions/ErrorAlert";

const defaultFilters: TransactionFilters = {
  page: 1,
  limit: 30,
  sortBy: "date",
  sortOrder: "desc",
};

const TransactionsPage = () => {
  const { data, pagination, isLoading, error, filters, setFilters, refetch } = useTransactions(defaultFilters);
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [editId, setEditId] = useState<string | null>(null);
  const [editInitialData, setEditInitialData] = useState<TransactionFormData | null>(null);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  const summary = useMemo(() => {
    const total = data.reduce((sum, tx) => sum + tx.amount.amount, 0);
    return total;
  }, [data]);

  const getErrorMessage = (maybeError: unknown, fallback: string) => {
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

  const handlePageChange = (nextPage: number) => {
    setFilters((prev) => ({
      ...prev,
      page: Math.max(1, Math.min(nextPage, pagination.totalPages)),
    }));
  };

  const handleAdd = async (payload: TransactionFormData) => {
    setIsSubmitting(true);
    try {
      await apiFetch("/api/transactions", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          subcategoryId: payload.subcategoryId,
          amount: { amount: payload.amount, currency: payload.currency },
          date: payload.date,
          description: payload.description,
        }),
      });
      toast.success("Transakcja dodana.");
      await refetch();
    } catch (error: unknown) {
      toast.error(getErrorMessage(error, "Nie udało się dodać transakcji."));
      throw error;
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleEditSubmit = async (payload: TransactionFormData) => {
    if (!editId) return;
    setIsSubmitting(true);
    try {
      await apiFetch(`/api/transactions/${editId}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          subcategoryId: payload.subcategoryId,
          amount: { amount: payload.amount, currency: payload.currency },
          date: payload.date,
          description: payload.description,
          type: payload.type,
        }),
      });
      toast.success("Transakcja zaktualizowana.");
      await refetch();
    } catch (error: unknown) {
      toast.error(getErrorMessage(error, "Nie udało się zaktualizować transakcji."));
      throw error;
    } finally {
      setIsSubmitting(false);
      setIsEditOpen(false);
      setEditId(null);
      setEditInitialData(null);
    }
  };

  const openEditModal = (id: string) => {
    const tx = data.find((item) => item.id === id);
    if (!tx) return;
    setEditId(id);
    setEditInitialData({
      amount: tx.amount.amount,
      currency: tx.amount.currency,
      subcategoryId: tx.subcategoryId,
      date: tx.date,
      description: tx.description ?? "",
      type: tx.type,
    });
    setIsEditOpen(true);
  };

  const handleDelete = async () => {
    if (!deleteId) return;
    setIsDeleting(true);
    try {
      await apiFetch(`/api/transactions/${deleteId}`, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
      });
      toast.success("Transakcja usunięta.");
      await refetch();
    } catch (error: unknown) {
      toast.error(getErrorMessage(error, "Nie udało się usunąć transakcji."));
      throw error;
    } finally {
      setIsDeleting(false);
      setDeleteId(null);
    }
  };

  return (
    <div className="flex flex-col gap-6">
      <header className="flex flex-col gap-2">
        <p className="text-sm text-muted-foreground">FinPath</p>
        <div className="flex items-center justify-between gap-4">
          <h1 className="text-3xl font-semibold tracking-tight">Transakcje</h1>
          <div className="text-right text-sm text-muted-foreground">
            <p>Łącznie: {(summary / 100).toLocaleString("pl-PL", { style: "currency", currency: "PLN" })}</p>
            <p>
              Strona {pagination.currentPage} / {pagination.totalPages}
            </p>
          </div>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <FiltersBar
            value={filters}
            onChange={setFilters}
            onReset={() =>
              setFilters({
                ...defaultFilters,
                startDate: undefined,
                endDate: undefined,
              })
            }
          />
          <Button onClick={() => setIsAddOpen(true)}>Dodaj transakcję</Button>
        </div>
      </header>

      <AddTransactionModal open={isAddOpen} onClose={() => setIsAddOpen(false)} onSubmit={handleAdd} />

      {isLoading && <SkeletonLoader variant="table" />}

      {error && !isLoading && <ErrorAlert message={error} onRetry={refetch} />}

      {!isLoading && !error && data.length === 0 && <EmptyState onAdd={() => setIsAddOpen(true)} />}

      {!isLoading && !error && data.length > 0 && (
        <div className="grid gap-3">
          <TransactionsTable rows={data} onEdit={openEditModal} onDelete={(id) => setDeleteId(id)} />
          <TransactionsList items={data} onEdit={openEditModal} onDelete={(id) => setDeleteId(id)} />
        </div>
      )}

      {!isLoading && <PaginationBar pagination={pagination} onPageChange={handlePageChange} />}

      {isSubmitting && (
        <div className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
          Zapisywanie transakcji...
        </div>
      )}

      <AddTransactionModal
        open={isEditOpen}
        onClose={() => {
          setIsEditOpen(false);
          setEditId(null);
          setEditInitialData(null);
        }}
        onSubmit={handleEditSubmit}
        mode="edit"
        initialData={editInitialData ?? undefined}
      />

      {deleteId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-md rounded-lg border border-border bg-card p-6 shadow-xl">
            <h2 className="text-lg font-semibold">Usuń transakcję</h2>
            <p className="mt-2 text-sm text-muted-foreground">
              Czy na pewno chcesz usunąć tę transakcję? Tej operacji nie można cofnąć.
            </p>
            <div className="mt-4 flex justify-end gap-2">
              <Button variant="outline" onClick={() => setDeleteId(null)} disabled={isDeleting}>
                Anuluj
              </Button>
              <Button variant="destructive" onClick={handleDelete} disabled={isDeleting}>
                {isDeleting ? "Usuwanie..." : "Usuń"}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default TransactionsPage;
