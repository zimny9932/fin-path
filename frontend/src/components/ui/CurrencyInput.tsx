import React, { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";

interface CurrencyInputProps {
  value: number; // Wartość w groszach/centach
  onChange: (valueInCents: number) => void;
  currency?: string;
  className?: string;
  placeholder?: string;
}

const CurrencyInput: React.FC<CurrencyInputProps> = ({
  value,
  onChange,
  currency = "PLN",
  className,
  placeholder = "0.00",
}) => {
  const [displayValue, setDisplayValue] = useState<string>("");
  const [isFocused, setIsFocused] = useState(false);

  useEffect(() => {
    // Aktualizuj wartość tylko, gdy pole nie jest aktywnie edytowane
    if (!isFocused) {
      // Unikaj wyświetlania "0.00" dla pustego pola, jeśli wartość to 0
      const formattedValue = value === 0 ? "" : (value / 100).toFixed(2);
      setDisplayValue(formattedValue);
    }
  }, [value, isFocused]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const inputValue = e.target.value;
    const sanitizedValue = inputValue.replace(/[^0-9.,]/g, "").replace(",", ".");

    if (/^\d*\.?\d{0,2}$/.test(sanitizedValue) || sanitizedValue === "") {
      setDisplayValue(sanitizedValue);
      const centsValue = sanitizedValue ? Math.round(parseFloat(sanitizedValue) * 100) : 0;
      if (!isNaN(centsValue)) {
        onChange(centsValue);
      }
    }
  };

  const handleFocus = () => {
    setIsFocused(true);
    // Gdy użytkownik klika w pole, pokazujemy mu wartość bez formatowania, jeśli jest to 0
    if (value === 0) {
      setDisplayValue("");
    } else {
      setDisplayValue((value / 100).toString().replace(".", ","));
    }
  };

  const handleBlur = () => {
    setIsFocused(false);
    // Po opuszczeniu pola, formatujemy wartość do dwóch miejsc po przecinku
    const numericValue = value / 100;
    const formattedValue = numericValue === 0 ? "" : numericValue.toFixed(2);
    setDisplayValue(formattedValue);
  };

  return (
    <div className={cn("relative", className)}>
      <Input
        type="text"
        value={displayValue}
        onChange={handleInputChange}
        onFocus={handleFocus}
        onBlur={handleBlur}
        className="pr-14"
        placeholder={placeholder}
      />
      <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <span className="text-gray-500 sm:text-sm">{currency}</span>
      </div>
    </div>
  );
};

export default CurrencyInput;
