# API Endpoint Implementation Plan: GET /api/dashboard

## 1. Przegląd punktu końcowego
Ten punkt końcowy dostarcza zagregowane dane finansowe dla pulpitu nawigacyjnego (dashboard) użytkownika. Zwraca kluczowe informacje podsumowujące dla bieżącego cyklu rozliczeniowego, w tym całkowite dochody, wydatki, saldo oraz postęp w realizacji budżetu. Dane są zawsze ograniczone do zalogowanego użytkownika.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/dashboard`
- **Parametry**:
  - Wymagane: Brak
  - Opcjonalne: Brak
- **Request Body**: Brak

## 3. Wykorzystywane typy

Do implementacji tego punktu końcowego zostaną utworzone następujące obiekty DTO w katalogu `src/DTO/`:

- **`DashboardOutput.php`**: Główny kontener na dane odpowiedzi.
  ```php
  class DashboardOutput {
      public BillingCycleOutput $billingCycle;
      public DashboardSummaryOutput $summary;
      public ?BudgetProgressOutput $budgetProgress; // Nullable, if no budget exists
  }
  ```

- **`BillingCycleOutput.php`**: Przechowuje daty początku i końca cyklu.
  ```php
  class BillingCycleOutput {
      public \DateTimeImmutable $startDate;
      public \DateTimeImmutable $endDate;
  }
  ```

- **`DashboardSummaryOutput.php`**: Przechowuje podsumowanie finansowe.
  ```php
  class DashboardSummaryOutput {
      public MoneyOutput $totalIncome;
      public MoneyOutput $totalExpenses;
      public MoneyOutput $balance;
  }
  ```

- **`BudgetProgressOutput.php`**: Przechowuje postęp w realizacji budżetu.
  ```php
  class BudgetProgressOutput {
      public MoneyOutput $planned;
      public MoneyOutput $spent;
      public int $percentage;
  }
  ```
Będzie również potrzebna klasa-znacznik, która nie będzie mapowana na tabelę w bazie danych, aby zdefiniować na niej operację API Platform.

- **`src/Model/Dashboard.php`**:
  ```php
  #[ApiResource(
      operations: [
          new Get(
              uriTemplate: '/dashboard',
              security: "is_granted('ROLE_USER')",
              output: DashboardOutput::class,
              provider: DashboardProvider::class
          )
      ]
  )]
  class Dashboard {}
  ```

## 4. Szczegóły odpowiedzi
- **Pomyślna odpowiedź**: `200 OK`
  ```json
  {
    "billingCycle": {
      "startDate": "YYYY-MM-DD",
      "endDate": "YYYY-MM-DD"
    },
    "summary": {
      "totalIncome": { "amount": 550000, "currency": "PLN" },
      "totalExpenses": { "amount": 320000, "currency": "PLN" },
      "balance": { "amount": 230000, "currency": "PLN" }
    },
    "budgetProgress": {
      "planned": { "amount": 400000, "currency": "PLN" },
      "spent": { "amount": 320000, "currency": "PLN" },
      "percentage": 80
    }
  }
  ```
- **Odpowiedź błędu**: Zobacz sekcję "Obsługa błędów".

## 5. Przepływ danych
1.  API Platform otrzymuje żądanie `GET /api/dashboard`.
2.  Na podstawie konfiguracji `ApiResource` dla modelu `Dashboard`, wywoływany jest `App\State\DashboardProvider`.
3.  `DashboardProvider` pobiera aktualnie zalogowanego użytkownika z `Security`.
4.  Sprawdza, czy `user->getBillingCycleStartDay()` nie jest `null`. Jeśli jest, rzuca wyjątek `OnboardingNotCompletedException`.
5.  Na podstawie dnia startowego i bieżącej daty, serwis pomocniczy `BillingCycleCalculator` oblicza `startDate` i `endDate` bieżącego cyklu rozliczeniowego.
6.  `DashboardProvider` wywołuje metody z `TransactionRepository` w celu pobrania sumy przychodów (`totalIncome`) i wydatków (`totalExpenses`) dla danego użytkownika w obliczonym zakresie dat.
7.  Następnie wywołuje `BudgetRepository`, aby znaleźć budżet dla bieżącego miesiąca i roku.
8.  Jeśli budżet istnieje, `DashboardProvider` oblicza sumę planowanych wydatków (`planned`) poprzez zsumowanie limitów z powiązanych encji `BudgetLimit`. Wartość `spent` jest równa `totalExpenses`. Oblicza również procent wykorzystania budżetu.
9.  Jeśli budżet nie istnieje, pole `budgetProgress` w odpowiedzi będzie miało wartość `null`.
10. `DashboardProvider` tworzy instancję `DashboardOutput` i wypełnia ją wszystkimi obliczonymi danymi.
11. API Platform serializuje obiekt `DashboardOutput` do formatu JSON i zwraca odpowiedź `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego jest chroniony i wymaga prawidłowego tokena JWT. Zapewnia to `lexik/jwt-authentication-bundle`.
- **Autoryzacja**: Operacja `Get` jest zabezpieczona za pomocą `security: "is_granted('ROLE_USER')"`, co gwarantuje, że tylko zalogowani użytkownicy mogą z niej korzystać.
- **Izolacja danych**: Wszystkie niestandardowe zapytania do bazy danych w `TransactionRepository` i `BudgetRepository` muszą zawierać warunek `WHERE user_id = :userId`, aby zapewnić, że użytkownik ma dostęp tylko do swoich danych.

## 7. Obsługa błędów
- **`400 Bad Request`**:
  - **Scenariusz**: Użytkownik nie ukończył procesu onboardingu (`billing_cycle_start_day` jest `null`).
  - **Implementacja**: Należy utworzyć niestandardowy wyjątek `OnboardingNotCompletedException` i subskrybenta zdarzeń (`ExceptionSubscriber`), który przechwyci ten wyjątek i zwróci odpowiedź 400 z czytelnym komunikatem błędu.
    ```json
    {
      "type": "https://errors.example.com/onboarding-not-completed",
      "title": "An error occurred",
      "detail": "User has not completed the onboarding process."
    }
    ```
- **`401 Unauthorized`**:
  - **Scenariusz**: Brak lub nieprawidłowy token JWT.
  - **Implementacja**: Obsługiwane automatycznie przez Symfony Security i `lexik/jwt-authentication-bundle`.
- **`500 Internal Server Error`**:
  - **Scenariusz**: Wystąpienie nieoczekiwanego błędu podczas przetwarzania żądania.
  - **Implementacja**: Obsługiwane automatycznie przez Symfony. Błędy będą logowane przez Monolog.

## 8. Rozważania dotyczące wydajności
- **Zapytania do bazy danych**: Aby uniknąć problemów z wydajnością, zapytania agregujące (SUM) sumujące transakcje i limity budżetowe powinny być wykonane bezpośrednio na poziomie bazy danych za pomocą DQL. Należy unikać pobierania kolekcji encji do aplikacji i sumowania ich w PHP.
- **Indeksy**: Należy upewnić się, że kolumny `user_id` i `date` w tabeli `transactions` są objęte indeksem złożonym (`idx_transactions_user_date`), co jest kluczowe dla szybkiego filtrowania transakcji.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Zaimplementować klasy DTO: `DashboardOutput`, `BillingCycleOutput`, `DashboardSummaryOutput`, `BudgetProgressOutput` w `src/DTO/`.
2.  **Utworzenie modelu**: Stworzyć klasę `src/Model/Dashboard.php` i dodać do niej adnotację `#[ApiResource]` z definicją operacji `GET`.
3.  **Serwis kalkulatora cyklu**: Stworzyć serwis `App\Service\BillingCycleCalculator` z logiką do obliczania dat początku i końca cyklu rozliczeniowego.
4.  **Aktualizacja repozytoriów**:
    - W `TransactionRepository` dodać metodę, np. `getTotalsInDateRange(User $user, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate)`, która zwróci sumę przychodów i wydatków za pomocą jednego zapytania DQL.
    - W `BudgetRepository` dodać metodę do pobierania sumy limitów dla danego budżetu.
5.  **Implementacja `DashboardProvider`**: Stworzyć `App\State\DashboardProvider` implementujący `ProviderInterface`. Wstrzyknąć do niego `Security`, `BillingCycleCalculator` i repozytoria, a następnie zaimplementować logikę opisaną w sekcji "Przepływ danych".
6.  **Obsługa błędu onboardingu**:
    - Stworzyć wyjątek `App\Exception\OnboardingNotCompletedException`.
    - Stworzyć `App\EventSubscriber\OnboardingNotCompletedExceptionSubscriber`, który nasłuchuje na `kernel.exception` i transformuje wyjątek na odpowiedź `400 Bad Request`.
7.  **Testy**: Napisać testy integracyjne (`ApiTestCase`) dla nowego punktu końcowego, które zweryfikują:
    - Poprawność zwracanych danych dla użytkownika z transakcjami i budżetem.
    - Poprawność danych, gdy budżet na dany miesiąc nie istnieje (`budgetProgress` jest `null`).
    - Zwrócenie błędu `400` dla użytkownika, który nie ukończył onboardingu.
    - Zwrócenie błędu `401` dla niezalogowanego użytkownika.

