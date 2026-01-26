export interface RegistrationRequestDto {
  email: string;
  password: string;
  passwordConfirmation: string;
}

export interface RegistrationResponseDto {
  token: string;
  refresh_token: string;
}

export interface RegistrationFormViewModel {
  email: string;
  password: string;
  passwordConfirmation: string;
}

export interface RegistrationFormValidationViewModel {
  email?: string;
  password?: string;
  passwordConfirmation?: string;
  api?: string;
}

export interface LoginRequestDTO {
  email: string;
  password: string;
}

export interface LoginResponseDTO {
  token: string;
  refresh_token: string;
}

export interface LoginFormViewModel {
  email: string;
  password: string;
}

export interface LoginFormValidationViewModel {
  email?: string;
  password?: string;
  api?: string;
}

export interface OnboardingRequestDTO {
  /**
   * Dzień rozpoczęcia cyklu rozliczeniowego, wybrany przez użytkownika (liczba od 1 do 31).
   */
  billingCycleStartDay: number;
}

export interface OnboardingFormViewModel {
  /**
   * Wybrany przez użytkownika dzień lub null, jeśli nic nie zostało wybrane.
   */
  selectedDay: number | null;
  /**
   * Flaga informująca, czy trwa proces komunikacji z API.
   */
  isLoading: boolean;
  /**
   * Komunikat błędu do wyświetlenia w przypadku niepowodzenia operacji.
   */
  error: string | null;
}

// Budget Planning DTOs and ViewModels

export type CurrencyCode = "PLN" | (string & {});

export interface MoneyDTO {
  amount: number; // kwota w groszach/centach
  currency: CurrencyCode; // np. "PLN"
}

export interface Range {
  startDate?: string;
  endDate?: string;
}

export interface SpendingCategoryReportDTO {
  mainCategory: string;
  totalAmount: MoneyDTO;
  percentageOfTotal: number;
}

export interface ReportsViewModel {
  range: Range;
  items: SpendingCategoryReportDTO[];
  totalSpent: MoneyDTO;
  hasData: boolean;
}

export interface ChartDatum {
  label: string;
  value: number;
  percentage: number;
}

export interface TableRow {
  category: string;
  amount: number;
  currency: CurrencyCode;
  percentage: number;
}

export interface MainCategoryDTO {
  name: string;
  value: string;
}

export interface SubcategoryDTO {
  id: string;
  name: string;
  type: "income" | "expense";
  mainCategory: string; // np. "FOOD"
}

export interface BudgetLimitInputDTO {
  subcategoryId: string;
  limitAmount: MoneyDTO;
}

export interface BudgetInputDTO {
  year: number;
  month: number;
  plannedIncome: MoneyDTO;
  limits: BudgetLimitInputDTO[];
}

export interface BudgetLimitResponseDTO {
  id: string;
  limitAmount: MoneyDTO;
  subcategory: {
    id: string;
    name: string;
  };
}

export interface BudgetResponseDTO {
  id: string;
  year: number;
  month: number;
  plannedIncome: MoneyDTO;
  limits: BudgetLimitResponseDTO[];
}

// ViewModels
export interface BudgetLimitViewModel {
  subcategoryId: string;
  subcategoryName: string;
  mainCategory: string;
  limitAmount: number; // kwota w groszach/centach
}

export interface BudgetViewModel {
  id: string | null;
  year: number;
  month: number;
  plannedIncome: number; // kwota w groszach/centach
  limits: BudgetLimitViewModel[];
  currency: string;
}

// Transactions
export interface MoneyAmount {
  amount: number;
  currency: "PLN";
}

export interface SubcategoryNestedDTO {
  id: string;
  name: string;
  mainCategory: string;
  type: "income" | "expense";
}

export interface TransactionDTO {
  id: string;
  amount: MoneyAmount;
  date: string; // YYYY-MM-DD
  description?: string | null;
  subcategory: SubcategoryNestedDTO;
}

export interface PaginationDTO {
  currentPage: number;
  totalPages: number;
  totalItems: number;
}

export interface TransactionsResponse {
  items: TransactionDTO[];
  pagination: PaginationDTO;
}

export interface TransactionFilters {
  page: number;
  limit: number;
  sortBy: "date" | "amount";
  sortOrder: "asc" | "desc";
  startDate?: string;
  endDate?: string;
}

export interface CycleRange {
  label: string;
  startDate: string;
  endDate: string;
}

export interface TransactionRowVM {
  id: string;
  date: string;
  formattedDate: string;
  amount: MoneyAmount;
  formattedAmount: string;
  subcategoryId: string;
  type: "income" | "expense";
  subcategoryName: string;
  description?: string | null;
}

export interface TransactionFormData {
  amount: number;
  currency: "PLN";
  subcategoryId: string;
  date: string;
  description?: string;
  type: "income" | "expense";
}

export type TransactionUpdateDTO = Partial<{
  amount: number;
  currency: "PLN";
  subcategoryId: string;
  date: string;
  description?: string;
  type: "income" | "expense";
}>;

export interface ErrorShape {
  message: string;
  fieldErrors?: Record<string, string>;
}
