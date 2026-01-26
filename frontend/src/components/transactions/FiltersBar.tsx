import { Button } from '@/components/ui/button';
import type { TransactionFilters } from '@/types';

interface FiltersBarProps {
  value: TransactionFilters;
  onChange: (next: TransactionFilters) => void;
  onReset: () => void;
}

export const FiltersBar = ({ value, onChange, onReset }: FiltersBarProps) => {
  const handleDateChange = (key: 'startDate' | 'endDate') => (v: string) => {
    onChange({
      ...value,
      page: 1,
      [key]: v || undefined,
    });
  };

  const handleSortChange = (key: 'sortBy' | 'sortOrder', v: string) => {
    onChange({
      ...value,
      page: 1,
      [key]: v as TransactionFilters[keyof TransactionFilters],
    });
  };

  const handleLimitChange = (v: number) => {
    onChange({
      ...value,
      page: 1,
      limit: v,
    });
  };

  return (
    <div className="flex flex-wrap items-center gap-3">
      <div className="flex gap-3">
        <label className="flex items-center gap-2 text-sm text-muted-foreground">
          Od
          <input
            type="date"
            value={value.startDate ?? ''}
            onChange={(e) => handleDateChange('startDate')(e.target.value)}
            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
          />
        </label>
        <label className="flex items-center gap-2 text-sm text-muted-foreground">
          Do
          <input
            type="date"
            value={value.endDate ?? ''}
            onChange={(e) => handleDateChange('endDate')(e.target.value)}
            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
          />
        </label>
      </div>

      <div className="flex gap-3">
        <label className="flex items-center gap-2 text-sm text-muted-foreground">
          Sortuj po
          <select
            value={value.sortBy}
            onChange={(e) => handleSortChange('sortBy', e.target.value)}
            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
          >
            <option value="date">Dacie</option>
            <option value="amount">Kwocie</option>
          </select>
        </label>
        <label className="flex items-center gap-2 text-sm text-muted-foreground">
          Kolejność
          <select
            value={value.sortOrder}
            onChange={(e) => handleSortChange('sortOrder', e.target.value)}
            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
          >
            <option value="desc">Malejąco</option>
            <option value="asc">Rosnąco</option>
          </select>
        </label>
        <label className="flex items-center gap-2 text-sm text-muted-foreground">
          Na stronę
          <select
            value={value.limit}
            onChange={(e) => handleLimitChange(Number(e.target.value))}
            className="rounded-md border border-input bg-background px-3 py-2 text-sm"
          >
            <option value={10}>10</option>
            <option value={20}>20</option>
            <option value={30}>30</option>
            <option value={50}>50</option>
          </select>
        </label>
      </div>

      <div className="ml-auto flex items-center gap-3">
        <Button
          variant="secondary"
          size="sm"
          onClick={() => onReset()}
        >
          Resetuj
        </Button>
      </div>
    </div>
  );
};

export default FiltersBar;
