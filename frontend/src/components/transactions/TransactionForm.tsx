import { useState } from "react";
import type { TransactionFormData, ErrorShape } from "@/types";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import CategoryPicker from "./CategoryPicker";

interface TransactionFormProps {
  initialData?: Partial<TransactionFormData>;
  onSubmit: (data: TransactionFormData) => void;
  onCancel: () => void;
}

const defaultData: TransactionFormData = {
  amount: 0,
  currency: "PLN",
  subcategoryId: "",
  date: new Date().toISOString().slice(0, 10),
  description: "",
  type: "expense",
};

export const TransactionForm = ({ initialData, onSubmit, onCancel }: TransactionFormProps) => {
  const [form, setForm] = useState<TransactionFormData>({
    ...defaultData,
    ...initialData,
  });
  const [errors, setErrors] = useState<ErrorShape["fieldErrors"]>({});

  const validate = () => {
    const nextErrors: ErrorShape["fieldErrors"] = {};
    const isAmountValid = Number.isFinite(form.amount) && form.amount > 0;
    if (!form.subcategoryId) nextErrors.subcategoryId = "Wybierz podkategorię.";
    if (!isAmountValid) nextErrors.amount = "Kwota musi być dodatnia.";
    if (!form.date) nextErrors.date = "Podaj datę.";
    if (form.description && form.description.length > 200) nextErrors.description = "Maks. 200 znaków.";
    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!validate()) return;
    onSubmit(form);
  };

  const handleChange = <K extends keyof TransactionFormData>(key: K, value: TransactionFormData[K]) => {
    setForm((prev) => ({ ...prev, [key]: value }));
    setErrors((prev) => {
      const next = { ...prev };
      if (key === "amount") {
        const isAmountValid = Number.isFinite(value as number) && (value as number) > 0;
        if (isAmountValid) delete next.amount;
      }
      if (key === "subcategoryId" && value) delete next.subcategoryId;
      if (key === "date" && value) delete next.date;
      if (key === "description" && (value as string)?.length <= 200) delete next.description;
      return next;
    });
  };

  return (
    <form className="space-y-4" onSubmit={handleSubmit}>
      <div className="flex flex-col gap-2">
        <Label>Kwota (PLN)</Label>
        <Input
          type="number"
          inputMode="decimal"
          min={0}
          step="0.01"
          value={form.amount ? (form.amount / 100).toString() : ""}
          onChange={(e) => {
            const raw = e.target.value.replace(",", ".");
            const parsed = parseFloat(raw);
            const cents = Number.isFinite(parsed) ? Math.round(parsed * 100) : 0;
            handleChange("amount", cents);
          }}
        />
        {errors?.amount && <p className="text-sm text-destructive">{errors.amount}</p>}
      </div>

      <div className="flex flex-col gap-2">
        <Label>Typ</Label>
        <select
          value={form.type}
          onChange={(e) => handleChange("type", e.target.value as TransactionFormData["type"])}
          className="rounded-md border border-input bg-background px-3 py-2 text-sm"
        >
          <option value="income">Przychód</option>
          <option value="expense">Wydatek</option>
        </select>
      </div>

      <CategoryPicker
        value={form.subcategoryId}
        onChange={(id) => handleChange("subcategoryId", id)}
        type={form.type}
      />
      {errors?.subcategoryId && <p className="text-sm text-destructive">{errors.subcategoryId}</p>}

      <div className="flex flex-col gap-2">
        <Label>Data</Label>
        <Input type="date" value={form.date} onChange={(e) => handleChange("date", e.target.value)} />
        {errors?.date && <p className="text-sm text-destructive">{errors.date}</p>}
      </div>

      <div className="flex flex-col gap-2">
        <Label>Opis</Label>
        <Textarea
          value={form.description}
          maxLength={200}
          onChange={(e) => handleChange("description", e.target.value)}
          placeholder="Opcjonalny opis (max 200 znaków)"
        />
        {errors?.description && <p className="text-sm text-destructive">{errors.description}</p>}
      </div>

      <div className="flex justify-end gap-2 pt-2">
        <Button variant="outline" type="button" onClick={onCancel}>
          Anuluj
        </Button>
        <Button type="submit">Zapisz</Button>
      </div>
    </form>
  );
};

export default TransactionForm;
