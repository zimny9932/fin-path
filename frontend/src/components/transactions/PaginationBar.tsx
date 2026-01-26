import { Button } from "@/components/ui/button";
import type { PaginationDTO } from "@/types";

interface PaginationBarProps {
  pagination: PaginationDTO;
  onPageChange: (page: number) => void;
}

export const PaginationBar = ({ pagination, onPageChange }: PaginationBarProps) => {
  return (
    <div className="flex items-center justify-between gap-4 rounded-lg border border-border bg-card px-4 py-3 text-sm">
      <span className="text-muted-foreground">Razem {pagination.totalItems} pozycji</span>
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(pagination.currentPage - 1)}
          disabled={pagination.currentPage <= 1}
        >
          Poprzednia
        </Button>
        <span className="text-muted-foreground">
          Strona {pagination.currentPage} / {pagination.totalPages}
        </span>
        <Button
          variant="outline"
          size="sm"
          onClick={() => onPageChange(pagination.currentPage + 1)}
          disabled={pagination.currentPage >= pagination.totalPages}
        >
          Następna
        </Button>
      </div>
    </div>
  );
};

export default PaginationBar;
