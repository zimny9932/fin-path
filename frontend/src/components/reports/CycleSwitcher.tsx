import { useEffect, useMemo, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import type { Range } from "@/types";

const DATE_REGEX = /^\d{4}-\d{2}-\d{2}$/;

type QuickRange = {
  label: string;
  value: Range;
};

type CycleSwitcherProps = {
  value: Range;
  onChange: (next: Range) => void;
  onApply: (next: Range) => void;
  isLoading?: boolean;
  minDate?: string;
  maxDate?: string;
  externalError?: string | null;
};

const isValidDate = (value?: string): boolean => {
  if (!value) return true;
  return DATE_REGEX.test(value) && !Number.isNaN(Date.parse(value));
};

const validateRange = (range: Range): string | null => {
  const { startDate, endDate } = range;

  if (!isValidDate(startDate) || !isValidDate(endDate)) {
    return "Daty muszą być w formacie RRRR-MM-DD.";
  }

  if (startDate && endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    if (start > end) {
      return "Data początkowa nie może być późniejsza niż końcowa.";
    }
  }

  return null;
};

const getCurrentMonthRange = (): Range => {
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);

  return {
    startDate: start.toISOString().slice(0, 10),
    endDate: end.toISOString().slice(0, 10),
  };
};

const getPreviousMonthRange = (): Range => {
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
  const end = new Date(now.getFullYear(), now.getMonth(), 0);

  return {
    startDate: start.toISOString().slice(0, 10),
    endDate: end.toISOString().slice(0, 10),
  };
};

const quickRanges: QuickRange[] = [
  { label: "Bieżący miesiąc", value: getCurrentMonthRange() },
  { label: "Poprzedni miesiąc", value: getPreviousMonthRange() },
];

const CycleSwitcher = ({
  value,
  onChange,
  onApply,
  isLoading = false,
  minDate,
  maxDate,
  externalError,
}: CycleSwitcherProps) => {
  const [localRange, setLocalRange] = useState<Range>(value);
  const [localError, setLocalError] = useState<string | null>(null);

  useEffect(() => {
    setLocalRange(value);
  }, [value]);

  const error = externalError ?? localError;

  const handleUpdate = (key: keyof Range, v?: string) => {
    const nextRange = { ...localRange, [key]: v || undefined };
    setLocalRange(nextRange);
    onChange(nextRange);
    setLocalError(validateRange(nextRange));
  };

  const applyRange = () => {
    const validationError = validateRange(localRange);
    setLocalError(validationError);
    if (validationError) return;
    onApply(localRange);
  };

  const canApply = useMemo(() => !error && !isLoading, [error, isLoading]);

  return (
    <div className="flex flex-col gap-3 rounded-lg border border-border bg-card p-4">
      <div className="flex flex-wrap items-center gap-3">
        <div className="flex flex-wrap items-center gap-3">
          <label className="flex items-center gap-2 text-sm text-muted-foreground">
            Od
            <Input
              type="date"
              value={localRange.startDate ?? ""}
              min={minDate}
              max={maxDate}
              onChange={(e) => handleUpdate("startDate", e.target.value)}
            />
          </label>
          <label className="flex items-center gap-2 text-sm text-muted-foreground">
            Do
            <Input
              type="date"
              value={localRange.endDate ?? ""}
              min={minDate}
              max={maxDate}
              onChange={(e) => handleUpdate("endDate", e.target.value)}
            />
          </label>
        </div>

        <div className="flex items-center gap-2">
          <Button variant="secondary" size="sm" disabled={!canApply} onClick={applyRange}>
            Zastosuj
          </Button>
          <Button
            variant="ghost"
            size="sm"
            disabled={isLoading}
            onClick={() => {
              setLocalRange({});
              setLocalError(null);
              onChange({});
              onApply({});
            }}
          >
            Wyczyść
          </Button>
        </div>
      </div>

      <div className="flex flex-wrap gap-2">
        {quickRanges.map((qr) => (
          <Button
            key={qr.label}
            variant="outline"
            size="sm"
            disabled={isLoading}
            onClick={() => {
              setLocalRange(qr.value);
              setLocalError(null);
              onChange(qr.value);
              onApply(qr.value);
            }}
          >
            {qr.label}
          </Button>
        ))}
      </div>

      {error && <p className="text-sm text-destructive">{error}</p>}
    </div>
  );
};

export default CycleSwitcher;
