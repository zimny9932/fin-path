# API Endpoint Implementation Plan: GET /api/reports/spending-by-category

## 1. Przegląd punktu końcowego
Ten punkt końcowy dostarcza raport podsumowujący wydatki użytkownika pogrupowane według głównych kategorii (`MainCategory`). Umożliwia analizę struktury wydatków w określonym przedziale czasowym, a domyślnie obejmuje bieżący cykl rozliczeniowy użytkownika.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/reports/spending-by-category`
- **Parametry zapytania (Query Parameters)**:
  - **Opcjonalne**:
    - `startDate` (string, format: `YYYY-MM-DD`): Data rozpoczęcia okresu raportowania.
    - `endDate` (string, format: `YYYY-MM-DD`): Data zakończenia okresu raportowania.

## 3. Wykorzystywane typy
- **DTO wyjściowe**:
  - `SpendingByCategoryOutput`: Nowy DTO reprezentujący pojedynczy wiersz w raporcie.
    - `mainCategory: string`
    - `totalAmount: MoneyOutput`
    - `percentageOfTotal: float`
- **Model API**:
  - `SpendingReport`: Nowa klasa-model (niebędąca encją Doctrine) w `src/Model/`, na której zostanie zdefiniowany `ApiResource` dla tej operacji. Posłuży jako "kotwica" dla niestandardowego providera.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (`200 OK`)**: Zwraca tablicę obiektów `SpendingByCategoryOutput`.
  ```json
  [
    {
      "mainCategory": "FOOD",
      "totalAmount": { "amount": 125000, "currency": "PLN" },
      "percentageOfTotal": 45.5
    },
    {
      "mainCategory": "BILLS",
      "totalAmount": { "amount": 80000, "currency": "PLN" },
      "percentageOfTotal": 29.1
    }
  ]
  ```
- **Pusta odpowiedź (`200 OK`)**: Jeśli w danym okresie nie ma żadnych wydatków, zwracana jest pusta tablica `[]`.

## 5. Przepływ danych
1.  Żądanie `GET` trafia do API Platform.
2.  API Platform identyfikuje operację na modelu `SpendingReport` i wywołuje dedykowany `SpendingByCategoryProvider`.
3.  **`SpendingByCategoryProvider`**:
    a.  Pobiera parametry `startDate` i `endDate` z żądania.
    b.  Sprawdza, czy daty zostały podane. Jeśli nie, wywołuje serwis `BillingCycleCalculator`, aby uzyskać datę początkową i końcową dla bieżącego cyklu rozliczeniowego zalogowanego użytkownika.
    c.  Waliduje poprawność dat (format, kolejność chronologiczna). W przypadku błędu rzuca wyjątek `HttpException` z kodem 400.
    d.  Wywołuje metodę `TransactionRepository::findSpendingByCategory()` przekazując obiekt `User` oraz obliczony zakres dat.
    e.  Otrzymuje zagregowane dane z repozytorium (np. `[['mainCategory' => 'FOOD', 'total' => 125000], ...]`).
    f.  Oblicza sumę wszystkich wydatków (`grandTotal`) w celu wyznaczenia procentów.
    g.  Iteruje po wynikach, tworząc obiekty DTO `SpendingByCategoryOutput`, obliczając `percentageOfTotal` (`(item['total'] / grandTotal) * 100`) dla każdej kategorii.
    h.  Zwraca tablicę wypełnionych DTO.
4.  API Platform serializuje DTO do formatu JSON i wysyła odpowiedź.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Operacja `Get` na `ApiResource` zostanie zabezpieczona za pomocą atrybutu `security: "is_granted('ROLE_USER')"`, co zapewni dostęp tylko dla zalogowanych użytkowników.
- **Autoryzacja i izolacja danych**: Metoda `TransactionRepository::findSpendingByCategory` musi przyjmować obiekt `User` jako argument i bezwzględnie dodawać warunek `WHERE t.user = :user` do zapytania DQL. Zapobiegnie to wyciekowi danych finansowych pomiędzy użytkownikami.

## 7. Obsługa błędów
- **`400 Bad Request`**:
  - Zwracany, gdy `startDate` lub `endDate` mają nieprawidłowy format.
  - Zwracany, gdy `startDate` jest datą późniejszą niż `endDate`.
  - Logika walidacji zostanie zaimplementowana w `SpendingByCategoryProvider`.
- **`401 Unauthorized`**: Zwracany przez framework Symfony/API Platform, gdy użytkownik nie jest uwierzytelniony.

## 8. Rozważania dotyczące wydajności
- **Zapytanie do bazy danych**: Kluczowe dla wydajności jest zoptymalizowane zapytanie agregujące. Zapytanie powinno być wykonane jako pojedyncze zapytanie DQL w `TransactionRepository`, wykorzystujące `SUM()` i `GROUP BY`.
- **Indeksy bazy danych**: Wydajność zapytania będzie zależeć od istniejącego indeksu na kolumnach `(user_id, date)` w tabeli `transactions`. Zgodnie z `db-plan.md`, taki indeks (`idx_transactions_user_date`) już istnieje, co jest kluczowe dla szybkiego filtrowania transakcji.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Stworzyć klasę `SpendingByCategoryOutput` w katalogu `src/DTO/`. Będzie ona zawierać publiczne właściwości: `string $mainCategory`, `MoneyOutput $totalAmount`, `float $percentageOfTotal`.
2.  **Utworzenie Modelu API**: Stworzyć klasę `SpendingReport` w `src/Model/`. Dodać do niej atrybut `#[ApiResource]` z definicją operacji `GET`, wskazującą na niestandardowy provider.
3.  **Rozszerzenie Repozytorium**: W `src/Repository/TransactionRepository.php` zaimplementować publiczną metodę `findSpendingByCategory(User $user, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array`. Metoda ta wykona zapytanie DQL, które zwróci sumę wydatków pogrupowaną po `subcategory.mainCategory`.
4.  **Implementacja Providera**: Stworzyć klasę `SpendingByCategoryProvider` w `src/State/`, implementującą `ProviderInterface`. W metodzie `provide()` zaimplementować całą logikę opisaną w sekcji "Przepływ danych".
5.  **Konfiguracja `ApiResource`**: W klasie `SpendingReport` skonfigurować operację `GET`, ustawiając `uriTemplate`, `output` na `SpendingByCategoryOutput::class`, `provider` na `SpendingByCategoryProvider::class` oraz `security`.
6.  **Testy API**: Stworzyć nową klasę testową `tests/Api/GetSpendingByCategoryReportTest.php`. Testy powinny obejmować:
    - Przypadek domyślny (bez parametrów daty).
    - Przypadek z poprawnymi parametrami `startDate` i `endDate`.
    - Przypadek, gdzie w danym okresie nie ma wydatków (oczekiwana pusta tablica).
    - Przypadek z niepoprawnym zakresem dat (`startDate` > `endDate`), oczekiwany status 400.
    - Próbę dostępu przez nieuwierzytelnionego użytkownika (oczekiwany status 401).
