import { useState, useEffect, useMemo } from 'react';
import type {
  BudgetViewModel,
  MainCategoryDTO,
  SubcategoryDTO,
  BudgetLimitViewModel as BudgetLimitResponseDTO,
  BudgetInputDTO,
} from '@/types';
import { apiFetch } from '@/lib/api';
import { toast } from 'sonner';

export const useBudgetForm = (year: number, month: number) => {
  const [budget, setBudget] = useState<BudgetViewModel | null>(null);
  const [mainCategories, setMainCategories] = useState<MainCategoryDTO[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const toArray = <T,>(data: any): T[] => {
      if (Array.isArray(data)) return data as T[];
      if (Array.isArray(data?.['hydra:member'])) return data['hydra:member'] as T[];
      if (Array.isArray(data?.member)) return data.member as T[];
      if (Array.isArray(data?.items)) return data.items as T[];
      return [];
    };

    const fetchData = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const [mainCatsRaw, subCatsRaw, budgetData] = await Promise.all([
          apiFetch('/api/enums/main-categories'),
          apiFetch('/api/subcategories?type=expense'),
          apiFetch(`/api/budgets/${year}/${month}`).catch(err => {
            if (err.status === 404) return null;
            throw err;
          })
        ]);

        const mainCats = toArray<MainCategoryDTO>(mainCatsRaw);
        const subCats = toArray<SubcategoryDTO>(subCatsRaw);

        setMainCategories(mainCats);

        let initialLimits;
        if (budgetData) {
          const existingLimits = Array.isArray(budgetData.budgetLimits)
            ? budgetData.budgetLimits
            : Array.isArray(budgetData.limits)
              ? budgetData.limits
              : [];

          initialLimits = subCats.map((sub: SubcategoryDTO) => {
            const existingLimit = existingLimits.find(
              (limit: BudgetLimitResponseDTO) => limit.subcategory.id === sub.id
            );

            return {
              subcategoryId: sub.id,
              subcategoryName: sub.name,
              mainCategory: sub.mainCategory,
              limitAmount: existingLimit ? existingLimit.limitAmount.amount : 0,
            };
          });
        } else {
          initialLimits = subCats.map((sub: SubcategoryDTO) => ({
            subcategoryId: sub.id,
            subcategoryName: sub.name,
            mainCategory: sub.mainCategory,
            limitAmount: 0,
          }));
        }

        setBudget({
          id: budgetData?.id || null,
          year,
          month,
          plannedIncome: budgetData?.plannedIncome?.amount || 0,
          limits: initialLimits,
          currency: budgetData?.plannedIncome?.currency || 'PLN',
        });

      } catch (e: any) {
        setError(e.message || 'Wystąpił błąd podczas ładowania danych.');
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [year, month]);

  const updatePlannedIncome = (amount: number) => {
    setBudget(prev => prev ? { ...prev, plannedIncome: amount } : null);
  };

  const updateLimit = (subcategoryId: string, amount: number) => {
    setBudget(prev => {
      if (!prev) return null;
      const newLimits = prev.limits.map(limit =>
        limit.subcategoryId === subcategoryId ? { ...limit, limitAmount: amount } : limit
      );
      return { ...prev, limits: newLimits };
    });
  };

  const groupedLimits = useMemo(() => 
    mainCategories.map(mainCat => ({
      ...mainCat,
      limits: budget?.limits.filter(limit => limit.mainCategory.toUpperCase() === mainCat.name.toUpperCase()) || [],
  })), [mainCategories, budget?.limits]);

  const totalLimits = budget?.limits.reduce((sum, limit) => sum + limit.limitAmount, 0) || 0;

  const handleSave = async () => {
    if (!budget) return;

    setIsSaving(true);
    setError(null);

    const payload: BudgetInputDTO = {
        year: budget.year,
        month: budget.month,
        plannedIncome: {
            amount: budget.plannedIncome,
            currency: budget.currency,
        },
        limits: budget.limits.map(l => ({
            subcategoryId: l.subcategoryId,
            limitAmount: {
                amount: l.limitAmount,
                currency: budget.currency,
            }
        })),
    };

    try {
        let savedBudget;
        if (budget.id) {
            // Update
            savedBudget = await apiFetch(`/api/budgets/${budget.year}/${budget.month}`, {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
        } else {
            // Create
            savedBudget = await apiFetch('/api/budgets', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
        }

        // Aktualizacja ID w stanie po utworzeniu nowego budżetu
        setBudget(prev => prev ? { ...prev, id: savedBudget.id } : null);

        toast.success('Budżet został pomyślnie zapisany!');

    } catch (e: any) {
        const errorMessage = e.message || 'Wystąpił błąd podczas zapisu.';
        setError(errorMessage);
        toast.error(errorMessage);
    } finally {
        setIsSaving(false);
    }
  };

  const handleCopyFromPreviousMonth = async () => {
    if (!budget) return;

    const sourceDate = new Date(budget.year, budget.month - 2, 1); // miesiące są 0-indeksowane, -2 żeby cofnąć o 1
    const sourceYear = sourceDate.getFullYear();
    const sourceMonth = sourceDate.getMonth() + 1;

    setIsSaving(true);
    setError(null);

    try {
        await apiFetch(`/api/budgets/${budget.year}/${budget.month}/copy`, {
            method: 'POST',
            body: JSON.stringify({ sourceYear, sourceMonth }),
        });

        // Po udanym kopiowaniu, musimy ponownie załadować dane dla bieżącego miesiąca
        // Robimy to przez ponowne wywołanie fetchData. Można by to opakować w funkcję.
        const budgetData = await apiFetch(`/api/budgets/${budget.year}/${budget.month}`);

        const existingLimitsMap = new Map<string, BudgetLimitResponseDTO>(
            budgetData.limits.map((limit: BudgetLimitResponseDTO) => [limit.subcategory.id, limit])
        );

        const subCats = await apiFetch('/api/subcategories?type=expense');

        const updatedLimits = subCats.map((sub: SubcategoryDTO) => {
            const existingLimit = existingLimitsMap.get(sub.id);
            return {
                subcategoryId: sub.id,
                subcategoryName: sub.name,
                mainCategory: sub.mainCategory,
                limitAmount: existingLimit ? existingLimit.limitAmount.amount : 0,
            };
        });

        setBudget(prev => prev ? {
            ...prev,
            id: budgetData.id,
            plannedIncome: budgetData.plannedIncome.amount,
            limits: updatedLimits,
        } : null);
        
        toast.success('Budżet z poprzedniego miesiąca został pomyślnie skopiowany!');

    } catch (e: any) {
         const errorMessage = e.message || 'Wystąpił błąd podczas kopiowania budżetu. Upewnij się, że budżet za poprzedni miesiąc istnieje.';
         setError(errorMessage);
         toast.error(errorMessage);
    } finally {
        setIsSaving(false);
    }
  };

  return {
    budget,
    error,
    isLoading,
    isSaving,
    groupedLimits,
    totalLimits,
    updatePlannedIncome,
    updateLimit,
    handleSave,
    handleCopyFromPreviousMonth,
  };
};
