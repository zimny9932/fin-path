import { useMemo, useState } from "react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { formatMoney, formatPercent } from "@/lib/money";
import type { ChartDatum, CurrencyCode } from "@/types";

type ChartMode = "pie" | "bar";

interface SpendingChartProps {
  data: ChartDatum[];
  currency: CurrencyCode;
  mode?: ChartMode;
  onModeChange?: (mode: ChartMode) => void;
  isLoading?: boolean;
}

interface PieSegment {
  startAngle: number;
  endAngle: number;
  color: string;
  label: string;
  percentage: number;
  value: number;
}

const palette = ["#6366f1", "#22c55e", "#f59e0b", "#ef4444", "#14b8a6", "#8b5cf6", "#0ea5e9", "#f97316"];

const polarToCartesian = (radius: number, angleInDegrees: number) => {
  const angleInRadians = ((angleInDegrees - 90) * Math.PI) / 180.0;
  return {
    x: 16 + radius * Math.cos(angleInRadians),
    y: 16 + radius * Math.sin(angleInRadians),
  };
};

const describeArc = (startAngle: number, endAngle: number, radius: number) => {
  const start = polarToCartesian(radius, endAngle);
  const end = polarToCartesian(radius, startAngle);
  const largeArcFlag = endAngle - startAngle <= 180 ? "0" : "1";
  return ["M", 16, 16, "L", start.x, start.y, "A", radius, radius, 0, largeArcFlag, 0, end.x, end.y, "Z"].join(" ");
};

const buildPieSegments = (data: ChartDatum[]): PieSegment[] => {
  const filtered = data.filter((item) => item.value > 0);
  const total = filtered.reduce((acc, item) => acc + item.value, 0);
  if (total <= 0) return [];

  let currentAngle = 0;
  return filtered.map((item, idx) => {
    const sliceAngle = (item.value / total) * 360;
    const segment: PieSegment = {
      startAngle: currentAngle,
      endAngle: currentAngle + sliceAngle,
      color: palette[idx % palette.length],
      label: item.label,
      percentage: item.percentage ?? (item.value / total) * 100,
      value: item.value,
    };
    currentAngle += sliceAngle;
    return segment;
  });
};

const SpendingChart = ({ data, currency, mode, onModeChange, isLoading = false }: SpendingChartProps) => {
  const [internalMode, setInternalMode] = useState<ChartMode>(mode ?? "pie");
  const activeMode = mode ?? internalMode;

  const totalValue = useMemo(() => data.filter((d) => d.value > 0).reduce((acc, item) => acc + item.value, 0), [data]);

  const segments = useMemo(() => buildPieSegments(data), [data]);

  const handleModeChange = (next: ChartMode) => {
    setInternalMode(next);
    onModeChange?.(next);
  };

  const renderPie = () => {
    if (!segments.length || totalValue <= 0) {
      return <p className="text-sm text-muted-foreground">Brak danych do wykresu.</p>;
    }

    return (
      <div className="flex flex-col gap-4 md:flex-row md:items-center">
        <svg viewBox="0 0 32 32" className="h-56 w-56">
          {segments.map((segment, idx) => (
            <path
              key={`${segment.label}-${idx}`}
              d={describeArc(segment.startAngle, segment.endAngle, 15)}
              fill={segment.color}
              className="transition-opacity hover:opacity-80"
            />
          ))}
        </svg>
        <div className="flex-1 space-y-3">
          {segments.map((segment, idx) => (
            <div
              key={`${segment.label}-legend-${idx}`}
              className="flex items-center justify-between rounded-md border border-border px-3 py-2"
            >
              <div className="flex items-center gap-2">
                <span className="h-3 w-3 rounded-sm" style={{ backgroundColor: segment.color }} />
                <span className="text-sm font-medium">{segment.label}</span>
              </div>
              <div className="text-right text-sm text-muted-foreground">
                <p>{formatPercent(segment.percentage)}</p>
                <p>{formatMoney(segment.value, currency)}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  };

  const renderBars = () => {
    if (!data.length || totalValue <= 0) {
      return <p className="text-sm text-muted-foreground">Brak danych do wykresu.</p>;
    }

    return (
      <div className="space-y-3">
        {data
          .filter((item) => item.value > 0)
          .map((item, idx) => {
            const share = Math.max(0, Math.min(100, (item.value / totalValue) * 100));
            const percentLabel = item.percentage ?? share;
            return (
              <div key={`${item.label}-bar-${idx}`} className="space-y-1">
                <div className="flex items-center justify-between text-sm">
                  <span className="font-medium">{item.label}</span>
                  <span className="text-muted-foreground">
                    {formatPercent(percentLabel)} • {formatMoney(item.value, currency)}
                  </span>
                </div>
                <div className="h-2 rounded-full bg-muted">
                  <div
                    className="h-2 rounded-full"
                    style={{
                      width: `${share}%`,
                      backgroundColor: palette[idx % palette.length],
                    }}
                  />
                </div>
              </div>
            );
          })}
      </div>
    );
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between gap-4">
        <CardTitle>Udział wydatków</CardTitle>
        <div className="flex gap-2">
          <Button
            variant={activeMode === "pie" ? "secondary" : "ghost"}
            size="sm"
            disabled={isLoading}
            onClick={() => handleModeChange("pie")}
          >
            Koło
          </Button>
          <Button
            variant={activeMode === "bar" ? "secondary" : "ghost"}
            size="sm"
            disabled={isLoading}
            onClick={() => handleModeChange("bar")}
          >
            Słupki
          </Button>
        </div>
      </CardHeader>
      <CardContent>{activeMode === "pie" ? renderPie() : renderBars()}</CardContent>
    </Card>
  );
};

export default SpendingChart;
