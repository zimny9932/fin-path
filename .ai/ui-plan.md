# Architektura UI dla FinPath

## 1. Przegląd struktury UI

Architektura UI aplikacji FinPath została zaprojektowana w oparciu o podejście "island architecture" z wykorzystaniem Astro dla statycznych layoutów i stron oraz React dla dynamicznych, interaktywnych komponentów (wysp). Celem jest zapewnienie wysokiej wydajności, responsywności i intuicyjnego interfejsu użytkownika. Architektura jest zorientowana na komponenty, z silnym naciskiem na ich reużywalność.

Struktura opiera się na jasno zdefiniowanych widokach, z których każdy odpowiada za konkretną funkcjonalność (np. Dashboard, Transakcje). Zarządzanie stanem jest podzielone: Zustand będzie zarządzał lekkim, globalnym stanem klienta (np. stan uwierzytelnienia), podczas gdy TanStack Query (React Query) obsłuży stan serwera, w tym pobieranie, buforowanie i synchronizację danych z API. Taki podział upraszcza logikę i poprawia ogólną wydajność aplikacji. Nawigacja jest dostosowana do urządzeń (pasek boczny na desktopie, menu hamburgerowe/dolny pasek na mobile'u), a kluczowe akcje, jak dodawanie transakcji, są dostępne globalnie poprzez modal, co wspiera główny cel biznesowy - szybkość i łatwość obsługi.

## 2. Lista widoków

### Widok: Logowanie (Login)
- **Ścieżka:** `/login`
- **Główny cel:** Uwierzytelnienie istniejącego użytkownika.
- **Kluczowe informacje:** Formularz z polami na e-mail i hasło.
- **Kluczowe komponenty:** `LoginForm`, `TextInput`, `PasswordInput`, `Button`, `Toast` (do obsługi błędów).
- **UX, dostępność, bezpieczeństwo:** Czytelne komunikaty o błędach walidacji (np. "Nieprawidłowy e-mail lub hasło"). Pola formularza mają odpowiednie etykiety. Przekierowanie do dashboardu po pomyślnym logowaniu.

### Widok: Rejestracja (Registration)
- **Ścieżka:** `/register`
- **Główny cel:** Umożliwienie nowym użytkownikom założenia konta.
- **Kluczowe informacje:** Formularz z polami na e-mail, hasło i powtórzenie hasła.
- **Kluczowe komponenty:** `RegistrationForm`, `TextInput`, `PasswordInput`, `Button`, `Toast`.
- **UX, dostępność, bezpieczeństwo:** Walidacja hasła po stronie klienta (minimalna długość, zgodność). Jasne komunikaty o błędach (np. "Adres e-mail jest już zajęty"). Automatyczne logowanie i przekierowanie do onboardingu po pomyślnej rejestracji.

### Widok: Onboarding
- **Ścieżka:** `/onboarding` (dostępny tylko dla nowych użytkowników)
- **Główny cel:** Jednorazowa konfiguracja konta użytkownika.
- **Kluczowe informacje:** Wybór dnia rozpoczęcia cyklu rozliczeniowego (1-31).
- **Kluczowe komponenty:** `OnboardingForm`, `Select`, `Button`.
- **UX, dostępność, bezpieczeństwo:** Prosty, jednoetapowy proces. Użytkownik jest automatycznie przekierowywany do tego widoku po rejestracji. Middleware chroni ten widok przed dostępem przez użytkowników, którzy już go ukończyli. Po zakończeniu następuje przekierowanie do tworzenia pierwszego budżetu.

### Widok: Pulpit (Dashboard)
- **Ścieżka:** `/` (główny widok po zalogowaniu)
- **Główny cel:** Prezentacja kluczowych informacji o stanie finansów w bieżącym cyklu rozliczeniowym.
- **Kluczowe informacje:**
  - Karty podsumowujące: "Suma przychodów", "Suma wydatków", "Saldo".
  - Wskaźnik postępu realizacji budżetu.
  - Lista ostatnich transakcji.
- **Kluczowe komponenty:** `SummaryCard`, `BudgetProgressBar`, `RecentTransactionsList`, `Banner` (dla wezwania do akcji, np. "Stwórz budżet"), `SkeletonLoader`.
- **UX, dostępność, bezpieczeństwo:** Widok jest dynamiczny i dostosowuje się do stanu użytkownika:
  1. **Stan ładowania:** Wyświetla szkielety interfejsu.
  2. **Brak budżetu:** Wyświetla baner zachęcający do stworzenia budżetu z opcją kopiowania z poprzedniego miesiąca.
  3. **Brak transakcji:** Wyświetla komponent "Empty State".
  4. **Stan z danymi:** Wyświetla pełne podsumowanie.

### Widok: Transakcje (Transactions)
- **Ścieżka:** `/transactions`
- **Główny cel:** Przeglądanie, filtrowanie i zarządzanie transakcjami.
- **Kluczowe informacje:** Lista transakcji z paginacją (data, kwota, podkategoria, opis).
- **Kluczowe komponenty:** `TransactionsTable` (desktop), `TransactionsList` (karty na mobile), `Pagination`, `CycleSwitcher`, `EmptyState`, `SkeletonLoader`.
- **UX, dostępność, bezpieczeństwo:** Interfejs jest w pełni responsywny. Umożliwia przełączanie widoku między cyklami rozliczeniowymi. Dostęp tylko dla zalogowanych użytkowników, dane filtrowane po stronie serwera.

### Widok: Planowanie Budżetu (Budget Planning)
- **Ścieżka:** `/budgets/plan`
- **Główny cel:** Tworzenie i edycja budżetu na wybrany cykl rozliczeniowy.
- **Kluczowe informacje:**
  - Pole na planowane przychody.
  - Lista podkategorii wydatków z polami na limity.
- **Kluczowe komponenty:** `BudgetForm`, `CurrencyInput`, `Accordion` (do grupowania podkategorii), `Button` ("Zapisz", "Kopiuj z poprzedniego miesiąca"), `Toast`.
- **UX, dostępność, bezpieczeństwo:** Złożony formularz jest uproszczony dzięki użyciu akordeonu. Funkcja kopiowania budżetu znacząco przyspiesza proces planowania. Walidacja danych wejściowych i obsługa błędów API (np. konflikt przy próbie stworzenia istniejącego budżetu).

### Widok: Raporty (Reports)
- **Ścieżka:** `/reports`
- **Główny cel:** Wizualizacja i analiza struktury wydatków.
- **Kluczowe informacje:**
  - Wykres (kołowy lub słupkowy) przedstawiający wydatki według głównych kategorii.
  - Tabela z procentowym udziałem wydatków na kategorię.
- **Kluczowe komponenty:** `Chart` (Recharts/Chart.js), `SpendingTable`, `CycleSwitcher`, `EmptyState`, `SkeletonLoader`.
- **UX, dostępność, bezpieczeństwo:** Umożliwia szybkie zrozumienie, na co wydawane są pieniądze. Dane są ograniczone do bieżącego użytkownika. Widok jasno komunikuje brak danych do wyświetlenia.

## 3. Mapa podróży użytkownika

1.  **Rejestracja i Onboarding:**
    - Użytkownik trafia na `/register`.
    - Po wypełnieniu formularza i pomyślnej walidacji zostaje automatycznie zalogowany i przekierowany na `/onboarding`.
    - Wprowadza dzień rozpoczęcia cyklu i zatwierdza.
    - Zostaje przekierowany na `/budgets/plan` w celu zdefiniowania pierwszego budżetu.
    - Po zapisaniu budżetu trafia na główny pulpit (`/`).

2.  **Codzienne użytkowanie:**
    - Użytkownik loguje się na `/login` i trafia na pulpit (`/`).
    - Widzi podsumowanie swoich finansów.
    - Klika globalny przycisk "Dodaj transakcję", co otwiera modal.
    - Wypełnia formularz transakcji i go zapisuje. Dane na pulpicie aktualizują się automatycznie (dzięki unieważnieniu zapytań przez TanStack Query i optimistic UI).
    - Nawiguje do `/transactions`, aby przejrzeć historię operacji w bieżącym cyklu.
    - Przechodzi do `/reports`, aby zobaczyć wykres swoich wydatków.

3.  **Początek nowego cyklu rozliczeniowego:**
    - Użytkownik loguje się na początku nowego miesiąca.
    - Na pulpicie (`/`) widzi baner informujący o braku budżetu na bieżący cykl.
    - Klika przycisk "Skopiuj budżet z poprzedniego miesiąca".
    - Zostaje przeniesiony na `/budgets/plan` z formularzem wstępnie wypełnionym danymi z poprzedniego okresu.
    - Dokonuje ewentualnych korekt i zapisuje budżet.

## 4. Układ i struktura nawigacji

- **Główny Layout:** Aplikacja posiada główny, spójny layout dla wszystkich widoków dostępnych po zalogowaniu. Składa się on z nagłówka, obszaru na treść oraz nawigacji.
- **Nawigacja na desktopie:**
  - **Pasek boczny (Sidebar):** Stały, widoczny po lewej stronie. Zawiera linki do: `Pulpit`, `Transakcje`, `Budżety`, `Raporty`.
  - **Nagłówek (Header):** Zawiera przycisk "Dodaj transakcję" oraz menu użytkownika (ustawienia, wylogowanie).
- **Nawigacja na mobile'u:**
  - **Menu hamburgerowe:** Zastępuje pasek boczny i zawiera te same linki nawigacyjne.
  - **Pływający przycisk akcji (FAB):** Umieszczony w prawym dolnym rogu, służy do szybkiego otwierania modala dodawania transakcji.
- **Routing:** Za routing odpowiada Astro. Dostęp do chronionych ścieżek jest kontrolowany przez middleware, który sprawdza status uwierzytelnienia użytkownika.

## 5. Kluczowe komponenty

Poniżej znajduje się lista kluczowych, reużywalnych komponentów, które będą stanowić podstawę interfejsu użytkownika:

- **`AddTransactionModal`:** Globalnie dostępny modal z formularzem do dodawania nowej transakcji. Wykorzystuje TanStack Query do mutacji danych i "optimistic updates".
- **`CycleSwitcher`:** Komponent UI pozwalający na przełączanie widoku danych (np. na liście transakcji lub w raportach) pomiędzy bieżącym, poprzednim i następnym cyklem rozliczeniowym.
- **`CurrencyInput`:** Specjalistyczne pole formularza do wprowadzania wartości pieniężnych, które wewnętrznie obsługuje konwersję między formatem dziesiętnym (widocznym dla użytkownika) a integerem (wymaganym przez API).
- **`SubcategorySelect`:** Zaawansowany komponent wyboru podkategorii z funkcją wyszukiwania oraz listą ostatnio używanych opcji dla przyspieszenia wyboru.
- **`SkeletonLoader`:** Komponent wyświetlany w miejscach, gdzie dane są w trakcie ładowania z API. Poprawia postrzeganą wydajność.
- **`EmptyState`:** Komponent wyświetlany, gdy brakuje danych do pokazania w danym widoku (np. brak transakcji, brak danych do raportu).
- **`Toast`:** Komponent do wyświetlania krótkich, nieinwazyjnych powiadomień o sukcesie operacji (np. "Transakcja dodana pomyślnie") lub błędach.
- **`Banner`:** Komponent używany do wyświetlania ważnych, kontekstowych komunikatów i wezwań do działania (np. na pulpicie, gdy nie ma zdefiniowanego budżetu).



