import { Button } from '@/components/ui/button';

interface EmptyStateProps {
  onAdd: () => void;
}

export const EmptyState = ({ onAdd }: EmptyStateProps) => (
  <div className="rounded-lg border border-dashed border-border bg-muted/30 px-6 py-10 text-center">
    <p className="text-lg font-semibold">Brak transakcji w tym zakresie.</p>
    <p className="mt-2 text-sm text-muted-foreground">
      Dodaj nową transakcję, aby zacząć wypełniać listę.
    </p>
    <div className="mt-4 flex justify-center">
      <Button onClick={onAdd}>Dodaj transakcję</Button>
    </div>
  </div>
);

export default EmptyState;
