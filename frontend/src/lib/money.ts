export const formatMoney = (amountInMinor: number, currency: string): string =>
  new Intl.NumberFormat("pl-PL", {
    style: "currency",
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format((amountInMinor ?? 0) / 100);

export const formatPercent = (value: number, fractionDigits = 1): string => `${(value ?? 0).toFixed(fractionDigits)}%`;
