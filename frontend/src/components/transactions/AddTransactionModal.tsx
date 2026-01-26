import { Button } from "@/components/ui/button";
import TransactionForm from "./TransactionForm";
import type { TransactionFormData } from "@/types";

interface AddTransactionModalProps {
  open: boolean;
  onClose: () => void;
  onSubmit?: (data: TransactionFormData) => Promise<void> | void;
  mode?: "create" | "edit";
  initialData?: Partial<TransactionFormData>;
}

export const AddTransactionModal = ({
  open,
  onClose,
  onSubmit,
  mode = "create",
  initialData,
}: AddTransactionModalProps) => {
  if (!open) return null;

  const isEdit = mode === "edit";

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-lg rounded-lg border border-border bg-card p-6 shadow-xl">
        <div className="flex items-start justify-between gap-4">
          <div>
            <p className="text-sm text-muted-foreground">{isEdit ? "Edycja transakcji" : "Nowa transakcja"}</p>
            <h2 className="text-xl font-semibold">{isEdit ? "Edytuj transakcję" : "Dodaj transakcję"}</h2>
          </div>
          <Button variant="ghost" size="sm" onClick={onClose}>
            Zamknij
          </Button>
        </div>

        <div className="mt-4">
          <TransactionForm
            initialData={initialData}
            onSubmit={async (data) => {
              await onSubmit?.(data);
              onClose();
            }}
            onCancel={onClose}
          />
        </div>
      </div>
    </div>
  );
};

export default AddTransactionModal;
