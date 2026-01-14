# Plan implementacji widoku Transakcje

## 1. Przegląd
Widok `/transactions` umożliwia zalogowanemu użytkownikowi przeglądanie, filtrowanie i zarządzanie transakcjami w bieżącym cyklu rozliczeniowym. Oferuje listę z paginacją, przełączanie cykli, dodawanie nowych transakcji (przychód/wydatek) oraz responsywny układ (tabela na desktopie, karty na mobile).

## 2. Routing widoku
- Ścieżka: `/transactions`
- Ochrona: dostęp tylko dla zalogowanych (middleware/guard wykorzystywany w projekcie).
- Domyślne parametry zapytania: `page=1`, `limit=30`, `sortBy=date`, `sortOrder=desc`, zakres dat = bieżący cykl rozliczeniowy.

## 3. Struktura komponentów
- `TransactionsPage` (layout widoku)
  - `CycleSwitcher`
  - `FiltersBar` (startDate, endDate, sortBy, sortOrder)
  - CTA `AddTransactionButton` → `AddTransactionModal` z `TransactionForm`
  - Obszar danych:
    - Stan ładowania: `SkeletonLoader`
    - Stan błędu: `ErrorAlert`
    - Stan pusty: `EmptyState`
    - Dane:
      - Desktop: `TransactionsTable`
      - Mobile: `TransactionsList`
  - `Pagination`

## 4. Szczegóły komponentów
### TransactionsPage
- Opis: Kontener widoku, zarządza stanem filtrów/paginacji/cyklu, pobiera dane i renderuje odpowiedni stan.
- Główne elementy: układ sekcji nagłówka (cykl + filtry + CTA), sekcja wyników, paginacja.
- Interakcje: zmiana cyklu, filtrów, sortowania, paginacji; otwarcie modala dodawania; odświeżanie listy po mutacjach.
- Walidacja: weryfikacja zakresu dat (start ≤ end), numeryczne page/limit.
- Typy: `TransactionsViewState`, `TransactionFilters`, `TransactionsResponse`.
- Propsy: brak (widok routowany).

### CycleSwitcher
- Opis: Przełączanie bieżącego cyklu rozliczeniowego (np. bieżący/poprzedni lub wybór zakresu dat).
- Główne elementy: select lub przyciski segmentowe.
- Interakcje: `onChange(cycleRange)`.
- Walidacja: poprawny zakres dat (start ≤ end).
- Typy: `CycleRange`.
- Propsy: `value: CycleRange`, `options: CycleRange[]`, `onChange(CycleRange)`.

### FiltersBar
- Opis: Kontrolki filtrowania listy (daty, sortowanie).
- Główne elementy: pola `startDate`, `endDate`, select `sortBy`, select/btn `sortOrder`.
- Interakcje: zmiana pól wywołuje `onChange` z nowymi filtrami; reset.
- Walidacja: format daty `YYYY-MM-DD`, start ≤ end.
- Typy: `TransactionFilters`.
- Propsy: `value: TransactionFilters`, `onChange(next: TransactionFilters)`, `onReset()`.

### TransactionsTable (desktop)
- Opis: Tabela transakcji dla szerokich ekranów.
- Główne elementy: nagłówki sortowalne dla `date`, `amount`; wiersze z datą, kwotą, podkategorią, opisem; ewentualnie menu akcji (edit/delete).
- Interakcje: klik w nagłówek sortujący, klik w akcje wiersza.
- Walidacja: brak własnej (dane wyświetlane).
- Typy: `TransactionRowVM[]`.
- Propsy: `rows: TransactionRowVM[]`, `sortBy`, `sortOrder`, `onSortChange(sortBy, sortOrder)`, opcjonalnie `onEdit(id)`, `onDelete(id)`.

### TransactionsList (mobile)
- Opis: Lista kart dla małych ekranów.
- Główne elementy: karty z datą, kwotą, kategorią, opisem; akcje wtórne.
- Interakcje: analogicznie do tabeli (akcje, ewentualnie sort via global filters).
- Walidacja: brak własnej.
- Typy: `TransactionRowVM[]`.
- Propsy: `items: TransactionRowVM[]`, `onEdit?`, `onDelete?`.

### Pagination
- Opis: Nawigacja po stronach.
- Główne elementy: przyciski poprzednia/następna, numery stron (jeśli potrzebne).
- Interakcje: `onPageChange(page)`.
- Walidacja: `page >= 1 && page <= totalPages`.
- Typy: `PaginationDTO`.
- Propsy: `pagination: PaginationDTO`, `onPageChange(page: number)`.

### EmptyState
- Opis: Komponent pustego stanu z CTA dodania transakcji.
- Główne elementy: ikona/tekst, przycisk "Dodaj transakcję".
- Interakcje: `onAdd`.
- Walidacja: brak.
- Typy: brak specjalnych.
- Propsy: `onAdd()`.

### SkeletonLoader
- Opis: Placeholder podczas ładowania listy.
- Główne elementy: wiersze/karteczki skeleton.
- Interakcje: brak.
- Walidacja: brak.
- Typy: brak.
- Propsy: opcjonalnie `variant: 'table' | 'list'`.

### AddTransactionModal + TransactionForm
- Opis: Modal z formularzem dodawania przychodu/ wydatku.
- Główne elementy: pola `amount`, `currency` (pre-set PLN), `type` (income/expense), `subcategory` (picker), `date` (default dziś), `description` (textarea 200 max), przyciski `Zapisz`/`Anuluj`.
- Interakcje: submit (POST), cancel, zmiana pola, wybór kategorii, przełącznik typu.
- Walidacja: `amount > 0`, `subcategoryId` wymagane, `description.length <= 200`, `date` w formacie `YYYY-MM-DD`.
- Typy: `TransactionFormData`, `SubcategoryNode`.
- Propsy: `open: boolean`, `onClose()`, `onSuccess()` (refetch), `mode?: 'create'|'edit'`, `initialData?: TransactionFormData`.

### CategoryTreePicker
- Opis: Wybór podkategorii z drzewem i wyszukiwaniem + sekcja ostatnio używanych.
- Główne elementy: input search (debounce), lista drzewiasta, sekcja "Ostatnio używane".
- Interakcje: wybór węzła → `onSelect(id)`, klik w recent → `onSelect`.
- Walidacja: wymagany wybór.
- Typy: `SubcategoryNode`, `RecentSubcategory`.
- Propsy: `value?: string`, `tree: SubcategoryNode[]`, `recents: RecentSubcategory[]`, `onSelect(id: string)`.

### ErrorAlert
- Opis: Wyświetla błąd pobierania listy lub akcji.
- Główne elementy: komunikat, przycisk retry.
- Interakcje: `onRetry`.
- Walidacja: brak.
- Typy: `ErrorShape`.
- Propsy: `error: ErrorShape`, `onRetry()`.

## 5. Typy
- `MoneyAmount { amount: number; currency: 'PLN' }`
- `Subcategory { id: string; name: string; categoryName?: string }`
- `SubcategoryNode { id: string; name: string; children?: SubcategoryNode[] }`
- `RecentSubcategory { id: string; name: string }`
- `TransactionDTO { id: string; amount: MoneyAmount; date: string; description?: string; subcategory: Subcategory }`
- `TransactionRowVM { id: string; date: string; formattedDate: string; amount: MoneyAmount; formattedAmount: string; subcategoryName: string; description?: string }`
- `PaginationDTO { currentPage: number; totalPages: number; totalItems: number }`
- `TransactionsResponse { items: TransactionDTO[]; pagination: PaginationDTO }`
- `TransactionFilters { page: number; limit: number; sortBy: 'date' | 'amount'; sortOrder: 'asc' | 'desc'; startDate?: string; endDate?: string }`
- `CycleRange { label: string; startDate: string; endDate: string }`
- `TransactionFormData { amount: number; currency: 'PLN'; subcategoryId: string; date: string; description?: string; type: 'income' | 'expense' }`
- `ErrorShape { message: string; fieldErrors?: Record<string, string> }`
Sprawdź czy jakiś typ już przypadkiem nie istnieje

## 6. Zarządzanie stanem
- Źródła stanu lokalnego w `TransactionsPage`: `filters`, `currentCycle`, `showAddModal`, `pendingAction`, `selectedLimit` (opcjonalnie).
- Hook do pobierania: `useTransactions(filters: TransactionFilters)` → zwraca `{ data, isLoading, error, refetch }`; wykorzystuje istniejący klient HTTP lub TanStack Query, jeśli dostępny w projekcie.
- Hook do formularza: `useTransactionForm` (obsługa walidacji, submit POST).
- Inwalidacja/odświeżenie: po `POST/PUT/DELETE` wywołaj `refetch` listy oraz (jeśli istnieje) emit/invalidacja cache dashboardu.
- Responsywność: przełączanie `TransactionsTable` / `TransactionsList` na podstawie breakpointu CSS (Tailwind) bez dodatkowego stanu.

## 7. Integracja API
- Lista: `GET /api/transactions` z query param: `page`, `limit`, `sortBy`, `sortOrder`, `startDate`, `endDate` (domyślnie bieżący cykl).
- Tworzenie: `POST /api/transactions` body: `{ subcategoryId, amount: { amount, currency: 'PLN' }, date, description? }`.
- Aktualizacja: `PUT /api/transactions/{id}` (opcjonalna obsługa w UI).
- Usuwanie: `DELETE /api/transactions/{id}` (opcjonalna obsługa w UI).
- Mapowanie danych: DTO → VM z formatowaniem daty/kwoty po stronie FE.
- Autoryzacja: korzystać z istniejących mechanizmów (cookies/bearer) – endpoint wymaga zalogowanego użytkownika.

## 8. Interakcje użytkownika
- Zmiana cyklu lub zakresu dat → refetch listy.
- Zmiana sortowania → aktualizacja filtrów, refetch.
- Paginacja → `onPageChange` aktualizuje filtr `page`, refetch.
- Klik CTA „Dodaj transakcję” → otwarcie modala; submit → walidacja; sukces: toast + refetch listy + zamknięcie modala.
- (Opcjonalnie) Edycja/Usuwanie wiersza → modal/confirm → `PUT/DELETE` → refetch.
- Responsywne zachowanie: tabela na >= md, lista kart na < md.

## 9. Warunki i walidacja
- `amount > 0` (frontend + obsługa błędu 400 z backendu).
- `subcategoryId` wymagane.
- `description` maks. 200 znaków.
- `date` format `YYYY-MM-DD`; domyślnie dzisiejsza; filtry startDate ≤ endDate.
- `page`/`limit` dodatnie liczby całkowite; `sortBy` w dozwolonym zbiorze; `sortOrder` asc/desc.
- Dostęp tylko po zalogowaniu – przekierowanie lub blokada widoku bez tokenu/sesji.

## 10. Obsługa błędów
- Błędy sieci/500: `ErrorAlert` z akcją „Spróbuj ponownie”.
- Błędy walidacji 400: mapowanie `fieldErrors` na pola formularza, pokazanie komunikatów.
- 404 przy edycji/usuwaniu: toast + automatyczny refetch listy.
- Timeout/spowolnienie: wskaźnik ładowania; blokada wielokrotnych submitów; informacja o stanie.
- Pusty rezultat: `EmptyState` z CTA dodania transakcji.

## 11. Kroki implementacji
1. Skonfiguruj routing `/transactions` z ochroną przed dostępem niezalogowanych.
2. Dodaj typy w `frontend/src/types.ts` (MoneyAmount, TransactionDTO, TransactionFilters, PaginationDTO, TransactionFormData, CycleRange, VM).
3. Utwórz hook `useTransactions` (GET) oraz util do mapowania DTO → VM.
4. Dodaj `TransactionsPage` z bazowym stanem filtrów (domyślne sortowanie, page=1, limit=30, daty z bieżącego cyklu) i integracją hooka.
5. Zaimplementuj `CycleSwitcher` i `FiltersBar` z walidacją zakresu dat oraz aktualizacją filtrów.
6. Dodaj `AddTransactionModal` z `TransactionForm` (walidacja, submit POST) oraz `CategoryTreePicker` (drzewo + „Ostatnio używane”).
7. Zaimplementuj renderowanie stanów: `SkeletonLoader`, `ErrorAlert` (retry), `EmptyState`.
8. Zaimplementuj `TransactionsTable` (desktop) i `TransactionsList` (mobile) z obsługą sortowania i akcji wierszy (opcjonalnie edit/delete).
9. Dodaj `Pagination` i powiąż z filtrem page.
10. Po sukcesie POST/PUT/DELETE wykonaj refetch listy i (jeśli dostępne) invalidację danych dashboardu.
11. Dodaj toasty/komunikaty UX (sukces, błąd), upewnij się, że formularz spełnia limit 200 znaków i kwota >0.
12. Przegląd dostępności (aria dla tabeli, modal focus trap) i responsywności (Tailwind breakpoints), krótki smoke test na desktop/mobile.
