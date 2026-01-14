import { useMemo, useState } from 'react';
import { useTransactions } from './hooks/useTransactions';
import type { TransactionFilters, TransactionFormData } from '@/types';
import { Button } from '@/components/ui/button';
import FiltersBar from './transactions/FiltersBar';
import PaginationBar from './transactions/PaginationBar';
import TransactionsTable from './transactions/TransactionsTable';
import TransactionsList from './transactions/TransactionsList';
import AddTransactionModal from './transactions/AddTransactionModal';
import { apiFetch } from '@/lib/api';
import { toast } from 'sonner';
import SkeletonLoader from './transactions/SkeletonLoader';
import EmptyState from './transactions/EmptyState';
import ErrorAlert from './transactions/ErrorAlert';

const defaultFilters: TransactionFilters = {
  page: 1,
  limit: 30,
  sortBy: 'date',
  sortOrder: 'desc',
};

const TransactionsPage = () => {
  const {
    data,
    pagination,
    isLoading,
    error,
    filters,
    setFilters,
    refetch,
  } = useTransactions(defaultFilters);
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const summary = useMemo(() => {
    const total = data.reduce((sum, tx) => sum + tx.amount.amount, 0);
    return total;
  }, [data]);

  const handlePageChange = (nextPage: number) => {
    setFilters((prev) => ({
      ...prev,
      page: Math.max(1, Math.min(nextPage, pagination.totalPages)),
    }));
  };

  const handleAdd = async (payload: TransactionFormData) => {
    setIsSubmitting(true);
    try {
      await apiFetch('/api/transactions', {
        method: 'POST',
        body: JSON.stringify({
          subcategoryId: payload.subcategoryId,
          amount: { amount: payload.amount, currency: payload.currency },
          date: payload.date,
          description: payload.description,
        }),
      });
      toast.success('Transakcja dodana.');
      await refetch();
    } catch (e: any) {
      toast.error(e?.message || 'Nie udało się dodać transakcji.');
      throw e;
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="flex flex-col gap-6">
      <header className="flex flex-col gap-2">
        <p className="text-sm text-muted-foreground">FinPath</p>
        <div className="flex items-center justify-between gap-4">
          <h1 className="text-3xl font-semibold tracking-tight">Transakcje</h1>
          <div className="text-right text-sm text-muted-foreground">
            <p>
              Łącznie: {(summary / 100).toLocaleString('pl-PL', { style: 'currency', currency: 'PLN' })}
            </p>
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

      <AddTransactionModal
        open={isAddOpen}
        onClose={() => setIsAddOpen(false)}
        onSubmit={handleAdd}
      />

      {isLoading && <SkeletonLoader variant="table" />}

      {error && !isLoading && (
        <ErrorAlert message={error} onRetry={refetch} />
      )}

      {!isLoading && !error && data.length === 0 && (
        <EmptyState onAdd={() => setIsAddOpen(true)} />
      )}

      {!isLoading && !error && data.length > 0 && (
        <div className="grid gap-3">
          <TransactionsTable rows={data} />
          <TransactionsList items={data} />
        </div>
      )}

      {!isLoading && (
        <PaginationBar pagination={pagination} onPageChange={handlePageChange} />
      )}

      {isSubmitting && (
        <div className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
          Zapisywanie transakcji...
        </div>
      )}
    </div>
  );
};

export default TransactionsPage;
