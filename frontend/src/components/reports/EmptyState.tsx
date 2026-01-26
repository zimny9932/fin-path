import { Button } from "@/components/ui/button";

interface EmptyStateProps {
  title?: string;
  description?: string;
  ctaLabel?: string;
  onCtaClick?: () => void;
}

const EmptyState = ({
  title = "Brak danych do wyświetlenia.",
  description = "Dodaj transakcje lub zmień zakres dat, aby zobaczyć raport.",
  ctaLabel = "Przejdź do transakcji",
  onCtaClick,
}: EmptyStateProps) => (
  <div className="rounded-lg border border-dashed border-border bg-muted/30 px-6 py-10 text-center">
    <p className="text-lg font-semibold">{title}</p>
    <p className="mt-2 text-sm text-muted-foreground">{description}</p>
    {onCtaClick && (
      <div className="mt-4 flex justify-center">
        <Button onClick={onCtaClick}>{ctaLabel}</Button>
      </div>
    )}
  </div>
);

export default EmptyState;
