import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { formatMoney, formatPercent } from "@/lib/money";
import type { CurrencyCode, TableRow } from "@/types";

type SpendingTableProps = {
  rows: TableRow[];
  currency: CurrencyCode;
  isLoading?: boolean;
};

const SpendingTable = ({ rows, currency, isLoading = false }: SpendingTableProps) => {
  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Podział kategorii</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {[...Array(5)].map((_, idx) => (
            <div key={idx} className="h-6 animate-pulse rounded bg-muted/60" />
          ))}
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Podział kategorii</CardTitle>
      </CardHeader>
      <CardContent>
        {rows.length === 0 ? (
          <p className="text-sm text-muted-foreground">Brak danych do wyświetlenia.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b border-border text-left text-muted-foreground">
                  <th className="py-2 pr-4">Kategoria</th>
                  <th className="py-2 pr-4">Kwota</th>
                  <th className="py-2 pr-4">Udział</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => {
                  const share = Math.max(0, Math.min(100, row.percentage));
                  return (
                    <tr key={row.category} className="border-b border-border">
                      <td className="py-3 pr-4 font-medium">{row.category}</td>
                      <td className="py-3 pr-4">{formatMoney(row.amount, currency)}</td>
                      <td className="py-3 pr-4">
                        <div className="flex items-center gap-2">
                          <div className="h-2 flex-1 rounded-full bg-muted">
                            <div className="h-2 rounded-full bg-primary" style={{ width: `${share}%` }} />
                          </div>
                          <span className="w-14 text-right text-muted-foreground">
                            {formatPercent(share)}
                          </span>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default SpendingTable;
