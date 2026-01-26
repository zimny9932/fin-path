interface SkeletonLoaderProps {
  variant?: 'table' | 'list';
}

export const SkeletonLoader = ({ variant = 'table' }: SkeletonLoaderProps) => {
  if (variant === 'list') {
    return (
      <div className="grid gap-3">
        {[...Array(4)].map((_, idx) => (
          <div
            key={idx}
            className="h-20 animate-pulse rounded-lg border border-border bg-muted/40"
          />
        ))}
      </div>
    );
  }

  return (
    <div className="rounded-lg border border-border bg-card">
      <div className="grid grid-cols-5 border-b border-border px-4 py-3 text-sm text-muted-foreground">
        <span>Data</span>
        <span>Kwota</span>
        <span>Kategoria</span>
        <span className="col-span-2">Opis</span>
      </div>
      <ul className="divide-y divide-border">
        {[...Array(5)].map((_, idx) => (
          <li key={idx} className="grid grid-cols-5 px-4 py-3">
            <div className="h-4 w-20 animate-pulse rounded bg-muted/60" />
            <div className="h-4 w-24 animate-pulse rounded bg-muted/60" />
            <div className="h-4 w-28 animate-pulse rounded bg-muted/60" />
            <div className="col-span-2 h-4 w-full max-w-xs animate-pulse rounded bg-muted/60" />
          </li>
        ))}
      </ul>
    </div>
  );
};

export default SkeletonLoader;
