import type { TransactionRowVM } from '@/types';
import { Button } from '@/components/ui/button';

interface TransactionsListProps {
  items: TransactionRowVM[];
  onEdit: (id: string) => void;
  onDelete: (id: string) => void;
}

export const TransactionsList = ({ items, onEdit, onDelete }: TransactionsListProps) => {
  return (
    <div className="grid gap-3 md:hidden">
      {items.length === 0 ? (
        <div className="rounded-lg border border-border bg-card px-4 py-6 text-center text-muted-foreground">
          Brak transakcji do wyświetlenia.
        </div>
      ) : (
        items.map((tx) => (
          <article
            key={tx.id}
            className="rounded-lg border border-border bg-card p-4 shadow-sm"
          >
            <div className="flex items-center justify-between">
              <div className="text-sm text-muted-foreground">{tx.formattedDate}</div>
              <div className="text-base font-semibold">{tx.formattedAmount}</div>
            </div>
            <div className="mt-2 text-sm font-medium">{tx.subcategoryName}</div>
            {tx.description && (
              <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                {tx.description}
              </p>
            )}
            <div className="mt-3 flex gap-2">
              <Button size="sm" variant="outline" className="flex-1" onClick={() => onEdit(tx.id)}>
                Edytuj
              </Button>
              <Button size="sm" variant="destructive" className="flex-1" onClick={() => onDelete(tx.id)}>
                Usuń
              </Button>
            </div>
          </article>
        ))
      )}
    </div>
  );
};

export default TransactionsList;
