# Plan implementacji widoku: Planowanie Budżetu

## 1. Przegląd
Widok "Planowanie Budżetu" umożliwia użytkownikom tworzenie nowego oraz edycję istniejącego budżetu na wybrany cykl rozliczeniowy (miesiąc i rok). Użytkownik może zdefiniować planowane przychody oraz ustalić limity wydatków dla poszczególnych podkategorii. Widok zawiera również funkcję kopiowania budżetu z poprzedniego miesiąca w celu przyspieszenia procesu planowania.

## 2. Routing widoku
Widok będzie dostępny pod następującą ścieżką:
- `/budgets/plan`

Docelowo ścieżka może przyjmować parametry roku i miesiąca, np. `/budgets/plan/2025/10`, jednak w pierwszej wersji będzie operować na bieżącym cyklu rozliczeniowym.

## 3. Struktura komponentów
Hierarchia komponentów dla widoku będzie następująca. Komponenty z `Shadcn/ui` zostaną użyte do budowy UI.

```
/frontend/src/pages/budgets/plan.astro
/frontend/src/components/BudgetForm.tsx (client:load)
└── CurrencyInput.tsx (dla planowanych przychodów)
    ├── Button.tsx ("Kopiuj z poprzedniego miesiąca")
    ├── Accordion.tsx (grupowanie podkategorii)
    │   └── BudgetCategoryList.tsx (dla każdej głównej kategorii)
    │       ├── AccordionTrigger.tsx ("Nazwa głównej kategorii")
    │       └── AccordionContent.tsx
    │           └── CurrencyInput.tsx (dla każdej podkategorii)
    ├── <p> (Suma limitów wydatków)
    └── Button.tsx ("Zapisz")
```

## 4. Szczegóły komponentów

### `BudgetForm.tsx`
- **Opis komponentu**: Główny komponent React, który zarządza całym formularzem budżetu. Odpowiada za zarządzanie stanem, walidację, obsługę interakcji użytkownika oraz komunikację z API.
- **Główne elementy**: Komponent `CurrencyInput` dla planowanych przychodów, komponent `Accordion` do grupowania list podkategorii (`BudgetCategoryList`), oraz przyciski `Button` do zapisu i kopiowania budżetu.
- **Obsługiwane interakcje**:
  - `onSubmit`: Wysyła dane formularza do API (POST lub PUT).
  - `onCopyPreviousMonth`: Inicjuje proces kopiowania budżetu z poprzedniego miesiąca.
  - `onIncomeChange`: Aktualizuje stan planowanych przychodów.
  - `onLimitChange`: Aktualizuje stan limitu dla konkretnej podkategorii.
- **Obsługiwana walidacja**:
  - Sprawdza, czy wszystkie wprowadzone wartości pieniężne są poprawnymi, nieujemnymi liczbami.
  - Dezaktywuje przycisk "Zapisz", jeśli formularz jest w trakcie wysyłania lub dane są nieprawidłowe.
- **Typy**: `BudgetViewModel`, `BudgetLimitViewModel`, `SubcategoryDTO`, `MainCategoryDTO`.
- **Propsy**: `year: number`, `month: number` (określające, dla jakiego okresu jest budżet).

### `BudgetCategoryList.tsx`
- **Opis komponentu**: Wyświetla listę podkategorii w ramach jednej kategorii głównej (np. "Potrzeby"). Używa komponentu `Accordion` z Shadcn/ui do zwijania i rozwijania listy.
- **Główne elementy**: `AccordionItem`, `AccordionTrigger` z nazwą kategorii głównej, `AccordionContent` zawierający listę pól `CurrencyInput` dla każdej podkategorii.
- **Obsługiwane interakcje**:
  - `onLimitChange(subcategoryId: string, amount: number)`: Propaguje zmianę wartości limitu do komponentu nadrzędnego (`BudgetForm`).
- **Obsługiwana walidacja**: Walidacja jest delegowana do komponentu `CurrencyInput`.
- **Typy**: `SubcategoryDTO[]`, `BudgetLimitViewModel[]`.
- **Propsy**: `title: string`, `subcategories: SubcategoryDTO[]`, `limits: BudgetLimitViewModel[]`, `onLimitChange: (subcategoryId: string, amount: number) => void`.

### `CurrencyInput.tsx`
- **Opis komponentu**: Reużywalny komponent do wprowadzania wartości pieniężnych. Zapewnia odpowiednie formatowanie, walidację oraz konwersję wartości (np. z kwoty w PLN na grosze/centy).
- **Główne elementy**: Komponent `Input` z Shadcn/ui z dodatkowym elementem wyświetlającym symbol waluty (np. "PLN").
- **Obsługiwane interakcje**:
  - `onChange(valueInCents: number)`: Zwraca nową wartość w groszach/centach po każdej zmianie.
- **Obsługiwana walidacja**:
  - Akceptuje tylko wartości numeryczne.
  - Wartość musi być większa lub równa zero.
- **Typy**: `MoneyDTO`.
- **Propsy**: `value: number`, `onChange: (newValue: number) => void`, `currency: string`.

## 5. Typy

### DTO (Data Transfer Objects - zgodne z API)
```typescript
// Obiekt reprezentujący wartość pieniężną
interface MoneyDTO {
  amount: number; // kwota w groszach/centach
  currency: string; // np. "PLN"
}

// Obiekt dla kategorii głównej
interface MainCategoryDTO {
  value: string; // np. "FOOD"
  label: string; // np. "Food"
}

// Obiekt dla podkategorii
interface SubcategoryDTO {
  id: string;
  name: string;
  type: 'income' | 'expense';
  mainCategory: string; // np. "FOOD"
}

// Obiekt wejściowy dla limitu budżetowego
interface BudgetLimitInputDTO {
  subcategoryId: string;
  limitAmount: MoneyDTO;
}

// Obiekt wejściowy do tworzenia/aktualizacji budżetu
interface BudgetInputDTO {
  year: number;
  month: number;
  plannedIncome: MoneyDTO;
  limits: BudgetLimitInputDTO[];
}

// Obiekt odpowiedzi dla pobranego budżetu
interface BudgetResponseDTO {
  id: string;
  year: number;
  month: number;
  plannedIncome: MoneyDTO;
  limits: {
    id: string;
    limitAmount: MoneyDTO;
    subcategory: {
      id: string;
      name: string;
    };
  }[];
}
```

### ViewModels (Typy dla stanu komponentów)
```typescript
// Reprezentuje limit dla pojedynczej podkategorii w stanie formularza
interface BudgetLimitViewModel {
  subcategoryId: string;
  subcategoryName: string;
  mainCategory: string;
  limitAmount: number; // kwota w groszach/centach
}

// Główny obiekt stanu dla formularza budżetu
interface BudgetViewModel {
  id: string | null; // null, jeśli budżet jest nowo tworzony
  year: number;
  month: number;
  plannedIncome: number; // kwota w groszach/centach
  limits: BudgetLimitViewModel[];
  currency: string;
}
```

## 6. Zarządzanie stanem
Zarządzanie stanem formularza zostanie zrealizowane za pomocą dedykowanego customowego hooka `useBudgetForm`.

### `useBudgetForm(year: number, month: number)`
- **Cel**: Enkapsulacja logiki związanej z formularzem: pobieranie danych (budżet, kategorie, podkategorie), aktualizacja stanu, obsługa zapisu i kopiowania.
- **Stan wewnętrzny**:
  - `budget: BudgetViewModel | null`: Aktualny stan danych formularza.
  - `mainCategories: MainCategoryDTO[]`: Lista dostępnych kategorii głównych.
  - `subcategories: SubcategoryDTO[]`: Lista dostępnych podkategorii.
  - `isLoading: boolean`: Flaga informująca o ładowaniu danych początkowych.
  - `isSaving: boolean`: Flaga informująca o procesie zapisu.
  - `error: Error | null`: Przechowuje ewentualne błędy z API.
- **Zwracane wartości i funkcje**:
  - `budget`, `mainCategories`, `isLoading`, `isSaving`: Wartości do renderowania UI.
  - `groupedLimits`: Limity pogrupowane według kategorii głównej.
  - `totalLimits: number`: Suma wszystkich limitów.
  - `updatePlannedIncome(amount: number)`: Funkcja do aktualizacji planowanych przychodów.
  - `updateLimit(subcategoryId: string, amount: number)`: Funkcja do aktualizacji limitu.
  - `handleSave()`: Funkcja do zapisu budżetu.
  - `handleCopyFromPreviousMonth()`: Funkcja do kopiowania budżetu.

## 7. Integracja API
Integracja z API będzie realizowana wewnątrz hooka `useBudgetForm` przy użyciu `fetch` API lub biblioteki typu TanStack Query.

- **Pobieranie danych**:
  - Przy pierwszym renderowaniu komponentu, `useEffect` hook wywoła równolegle:
    - `GET /api/enums/main-categories`: W celu pobrania listy kategorii głównych.
    - `GET /api/subcategories?type=expense`: W celu pobrania listy podkategorii wydatków.
    - `GET /api/budgets/{year}/{month}`: W celu pobrania istniejącego budżetu.
  - Po otrzymaniu odpowiedzi, formularz zostanie zainicjalizowany:
    - Jeśli budżet na dany miesiąc istnieje (`200 OK`), formularz zostanie wypełniony jego danymi.
    - Jeśli budżet nie istnieje (`404 Not Found`), formularz zostanie zainicjalizowany z pustymi wartościami (limity ustawione na 0) dla wszystkich pobranych podkategorii wydatków.
- **Zapis danych**:
  - Funkcja `handleSave` sprawdzi, czy stan `budget.id` istnieje.
  - Jeśli `id` istnieje, zostanie wykonane żądanie `PUT /api/budgets/{year}/{month}`.
  - Jeśli `id` jest `null`, zostanie wykonane żądanie `POST /api/budgets`.
  - **Typ żądania**: `BudgetInputDTO`.
- **Kopiowanie budżetu**:
  - Funkcja `handleCopyFromPreviousMonth` wywoła `POST /api/budgets/{year}/{month}/copy`.
  - W ciele żądania znajdą się `sourceYear` i `sourceMonth`.
  - Po pomyślnym wykonaniu operacji, dane dla bieżącego miesiąca zostaną ponownie pobrane, aby odświeżyć formularz.

## 8. Interakcje użytkownika
- **Wejście na stronę**: Użytkownik widzi formularz (początkowo w stanie ładowania), który po chwili wypełnia się danymi istniejącego budżetu lub wyświetla puste pola do stworzenia nowego na podstawie pobranych podkategorii.
- **Wprowadzanie danych**: Zmiana wartości w polu przychodów lub w dowolnym polu limitu natychmiast aktualizuje stan komponentu.
- **Kliknięcie "Kopiuj z poprzedniego miesiąca"**: Wyświetlany jest stan ładowania. Po pomyślnym skopiowaniu, formularz wypełnia się danymi z poprzedniego miesiąca, a użytkownik otrzymuje powiadomienie (`Toast`).
- **Kliknięcie "Zapisz"**: Przycisk przechodzi w stan ładowania. Po pomyślnym zapisie użytkownik otrzymuje powiadomienie (`Toast`). W przypadku błędu, również wyświetlane jest odpowiednie powiadomienie.

## 9. Warunki i walidacja
- **Poziom komponentu `CurrencyInput`**:
  - Dopuszcza wprowadzanie tylko cyfr i separatora dziesiętnego.
  - Wartość nie może być ujemna.
- **Poziom komponentu `BudgetForm`**:
  - Przycisk "Zapisz" jest nieaktywny, jeśli:
    - Trwa operacja zapisu (`isSaving` jest `true`).
    - Wprowadzone dane są nieprawidłowe (np. któreś pole zawiera tekst zamiast liczby).
- **Formatowanie danych**: Wszystkie kwoty przed wysłaniem do API są konwertowane na liczbę całkowitą reprezentującą grosze/centy.

## 10. Obsługa błędów
- **Błąd pobierania danych (np. błąd serwera 500)**: Zamiast formularza, zostanie wyświetlony komunikat o błędzie z prośbą o spróbowanie ponownie później.
- **Błąd walidacji (`400 Bad Request`) przy zapisie**: Użytkownik zobaczy powiadomienie `Toast` z ogólną informacją o błędnych danych. W idealnym przypadku, pola powodujące błąd zostaną podświetlone.
- **Konflikt (`409 Conflict`) przy tworzeniu/kopiowaniu**: Użytkownik zobaczy powiadomienie `Toast` z informacją, że budżet na dany miesiąc już istnieje. Może zostać zaproponowane przeładowanie strony w celu edycji istniejącego budżetu.
- **Brak budżetu do skopiowania (`404 Not Found`)**: Użytkownik zobaczy powiadomienie `Toast` z informacją, że nie znaleziono budżetu dla poprzedniego miesiąca.

## 11. Kroki implementacji
1.  **Stworzenie struktury plików**: Utworzenie pliku strony `plan.astro` oraz komponentów React: `BudgetForm.tsx`, `BudgetCategoryList.tsx` i `CurrencyInput.tsx`.
2.  **Zdefiniowanie typów**: Zaimplementowanie wszystkich wymaganych typów (DTO i ViewModel) w pliku `frontend/src/types.ts`, w tym `MainCategoryDTO` i `SubcategoryDTO`.
3.  **Implementacja `CurrencyInput`**: Stworzenie w pełni funkcjonalnego i walidującego komponentu do wprowadzania kwot.
4.  **Implementacja `useBudgetForm`**: Zaimplementowanie logiki hooka. Najpierw pobieranie kategorii i podkategorii z mockowymi danymi, następnie dodanie logiki do budowania stanu formularza.
5.  **Budowa UI komponentu `BudgetForm`**: Złożenie interfejsu użytkownika z przygotowanych komponentów, podpięcie stanu i handlerów z hooka `useBudgetForm`. Użycie `mainCategories` do stworzenia sekcji w akordeonie.
6.  **Integracja z API**: Zastąpienie mockowych danych w `useBudgetForm` rzeczywistymi wywołaniami `fetch` do API dla wszystkich endpointów (`GET`, `POST`, `PUT`), w tym pobieranie kategorii.
7.  **Implementacja obsługi błędów**: Dodanie logiki obsługi błędów API, w tym wyświetlanie powiadomień `Toast` dla użytkownika.
8.  **Stylowanie i dopracowanie UX**: Dopracowanie wyglądu, responsywności oraz dodanie wskaźników ładowania dla lepszego doświadczenia użytkownika.
9.  **Testy manualne**: Przetestowanie wszystkich ścieżek użytkownika: tworzenie nowego budżetu, edycja istniejącego, kopiowanie z poprzedniego miesiąca oraz obsługa wszystkich zdefiniowanych scenariuszy błędów.
