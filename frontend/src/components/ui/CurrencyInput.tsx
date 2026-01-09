import React, { useState, useEffect } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";

interface CurrencyInputProps {
  value: number; // Wartość w groszach/centach
  onChange: (valueInCents: number) => void;
  currency?: string;
  className?: string;
}

const CurrencyInput: React.FC<CurrencyInputProps> = ({
  value,
  onChange,
  currency = "PLN",
  className,
}) => {
  const [displayValue, setDisplayValue] = useState<string>(
    (value / 100).toFixed(2)
  );

  useEffect(() => {
    setDisplayValue((value / 100).toFixed(2));
  }, [value]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const inputValue = e.target.value;
    // Pozwala na wprowadzanie tylko cyfr, przecinków i kropek
    const sanitizedValue = inputValue.replace(/[^0-9.,]/g, "").replace(",", ".");

    // Ograniczenie do maksymalnie dwóch miejsc po przecinku
    if (/^\d*\.?\d{0,2}$/.test(sanitizedValue) || sanitizedValue === "") {
      setDisplayValue(sanitizedValue);
      if (sanitizedValue !== "" && !isNaN(parseFloat(sanitizedValue))) {
        const centsValue = Math.round(parseFloat(sanitizedValue) * 100);
        onChange(centsValue);
      } else {
        onChange(0);
      }
    }
  };

  const handleBlur = () => {
    if (displayValue !== "") {
      const numericValue = parseFloat(displayValue);
      if (!isNaN(numericValue)) {
        setDisplayValue(numericValue.toFixed(2));
      }
    }
  };

  return (
    <div className={cn("relative", className)}>
      <Input
        type="text"
        value={displayValue}
        onChange={handleInputChange}
        onBlur={handleBlur}
        className="pr-14"
        placeholder="0.00"
      />
      <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <span className="text-gray-500 sm:text-sm">{currency}</span>
      </div>
    </div>
  );
};

export default CurrencyInput;
