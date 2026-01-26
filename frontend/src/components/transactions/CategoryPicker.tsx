import { useEffect, useState } from 'react';
import { apiFetch } from '@/lib/api';
import type { SubcategoryDTO } from '@/types';
import { Label } from '@/components/ui/label';

interface CategoryPickerProps {
  value?: string;
  onChange: (id: string) => void;
  type: 'income' | 'expense';
}

export const CategoryPicker = ({ value, onChange, type }: CategoryPickerProps) => {
  const [items, setItems] = useState<SubcategoryDTO[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      setError(null);
      try {
        const data = await apiFetch(`/api/subcategories?type=${type}`);
        setItems(Array.isArray(data) ? data : []);
      } catch (e: any) {
        setError(e?.message || 'Nie udało się pobrać podkategorii.');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [type]);

  return (
    <div className="space-y-2">
      <Label>Podkategoria</Label>
      {loading && <p className="text-sm text-muted-foreground">Ładowanie...</p>}
      {error && <p className="text-sm text-destructive">{error}</p>}
      <select
        value={value}
        onChange={(e) => {
          onChange(e.target.value);
        }}
        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
      >
        <option value="">Wybierz...</option>
        {items.map((item) => (
          <option key={item.id} value={item.id}>
            {item.name}
          </option>
        ))}
      </select>
    </div>
  );
};

export default CategoryPicker;
