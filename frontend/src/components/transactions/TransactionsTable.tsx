import type { TransactionRowVM } from '@/types';
import { Button } from '@/components/ui/button';

interface TransactionsTableProps {
  rows: TransactionRowVM[];
  onEdit: (id: string) => void;
  onDelete: (id: string) => void;
}

export const TransactionsTable = ({ rows, onEdit, onDelete }: TransactionsTableProps) => {
  return (
    <div className="hidden rounded-lg border border-border bg-card md:block">
      <div className="grid grid-cols-6 border-b border-border px-4 py-3 text-sm text-muted-foreground">
        <span>Data</span>
        <span>Kwota</span>
        <span>Kategoria</span>
        <span className="col-span-2">Opis</span>
        <span className="text-right">Akcje</span>
      </div>
      {rows.length === 0 ? (
        <div className="px-4 py-8 text-center text-muted-foreground">
          Brak transakcji do wyświetlenia.
        </div>
      ) : (
        <ul className="divide-y divide-border">
          {rows.map((tx) => (
            <li key={tx.id} className="grid grid-cols-6 items-center px-4 py-3 text-sm">
              <span>{tx.formattedDate}</span>
              <span className="font-medium">{tx.formattedAmount}</span>
              <span>{tx.subcategoryName}</span>
              <span className="col-span-2 text-muted-foreground">
                {tx.description || '—'}
              </span>
              <div className="flex justify-end gap-2">
                <Button size="sm" variant="outline" onClick={() => onEdit(tx.id)}>
                  Edytuj
                </Button>
                <Button size="sm" variant="destructive" onClick={() => onDelete(tx.id)}>
                  Usuń
                </Button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
};

export default TransactionsTable;
