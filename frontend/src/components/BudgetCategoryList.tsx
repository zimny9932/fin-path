import React from 'react';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import CurrencyInput from "@/components/ui/CurrencyInput";
import type { MainCategoryDTO, BudgetLimitViewModel } from '@/types';

interface GroupedLimit {
    name: string;
    value: string;
    limits: BudgetLimitViewModel[];
}

interface BudgetCategoryListProps {
  groupedLimits: GroupedLimit[];
  onLimitChange: (subcategoryId: string, amount: number) => void;
}

const BudgetCategoryList: React.FC<BudgetCategoryListProps> = ({ groupedLimits, onLimitChange }) => {
  return (
    <Accordion type="multiple" className="w-full">
      {groupedLimits.map((group) => (
        <AccordionItem value={group.name} key={group.name}>
          <AccordionTrigger>{group.value}</AccordionTrigger>
          <AccordionContent>
            <div className="space-y-4 pl-2">
              {group.limits.map((limit) => (
                <div key={limit.subcategoryId} className="flex items-center justify-between">
                  <label htmlFor={`limit-${limit.subcategoryId}`} className="text-sm">
                    {limit.subcategoryName}
                  </label>
                  <CurrencyInput
                    value={limit.limitAmount}
                    onChange={(amount) => onLimitChange(limit.subcategoryId, amount)}
                    className="w-40"
                  />
                </div>
              ))}
            </div>
          </AccordionContent>
        </AccordionItem>
      ))}
    </Accordion>
  );
};

export default BudgetCategoryList;
