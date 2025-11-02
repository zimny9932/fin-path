# API Endpoint Implementation Plan: GET /api/transactions

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest dostarczenie listy transakcji dla uwierzytelnionego użytkownika. Lista jest paginowana i domyślnie obejmuje bieżący cykl rozliczeniowy. Umożliwia filtrowanie według zakresu dat oraz sortowanie według określonych pól.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/transactions`
- **Parametry**:
  - **Opcjonalne**:
    - `page` (int, default: 1): Numer strony do wyświetlenia.
    - `limit` (int, default: 30): Maksymalna liczba transakcji na stronie.
    - `sortBy` (string, default: "date"): Nazwa pola, po którym nastąpi sortowanie.
    - `sortOrder` (string, default: "desc"): Kierunek sortowania (`asc` lub `desc`).
    - `startDate` (string, `YYYY-MM-DD`): Data początkowa okresu filtrowania.
    - `endDate` (string, `YYYY-MM-DD`): Data końcowa okresu filtrowania.
- **Request Body**: Brak.

## 3. Wykorzystywane typy (DTOs)
Aby zapewnić zgodność struktury odpowiedzi ze specyfikacją, zostaną utworzone następujące obiekty DTO:

- **`MoneyOutput`**: Reprezentuje obiekt kwoty i waluty.
  ```php
  class MoneyOutput {
      public int $amount;
      public string $currency;
  }
  ```
- **`SubcategoryNestedOutput`**: Reprezentuje zagnieżdżony obiekt podkategorii.
  ```php
  class SubcategoryNestedOutput {
      public Uuid $id;
      public string $name;
  }
  ```
- **`TransactionOutput`**: Reprezentuje pojedynczą transakcję na liście.
  ```php
  class TransactionOutput {
      public Uuid $id;
      public MoneyOutput $amount;
      public string $date; // YYYY-MM-DD
      public ?string $description;
      public SubcategoryNestedOutput $subcategory;
  }
  ```
- **`PaginationDetails`**: Reprezentuje obiekt z informacjami o paginacji.
  ```php
  class PaginationDetails {
      public int $currentPage;
      public int $totalPages;
      public int $totalItems;
  }
  ```
- **`PaginatedTransactionOutput`**: Główny obiekt odpowiedzi.
  ```php
  class PaginatedTransactionOutput {
      /** @var TransactionOutput[] */
      public array $items;
      public PaginationDetails $pagination;
  }
  ```

## 4. Szczegóły odpowiedzi
- **Sukces**: `200 OK`
  - Odpowiedź będzie miała niestandardową strukturę JSON, zgodną ze specyfikacją. API Platform domyślnie generuje odpowiedź w formacie `Hydra/JSON-LD`. Aby dostosować format wyjściowy, wykorzystamy dedykowany `Normalizer`, który przekształci standardowy, paginowany wynik API Platform na wymaganą strukturę.
  ```json
  {
    "items": [
      {
        "id": "uuid-v7-string",
        "amount": { "amount": 5000, "currency": "PLN" },
        "date": "YYYY-MM-DD",
        "description": "Weekly shopping",
        "subcategory": {
          "id": "uuid-v7-string",
          "name": "Groceries"
        }
      }
    ],
    "pagination": {
      "currentPage": 1,
      "totalPages": 5,
      "totalItems": 150
    }
  }
  ```
- **Błędy**:
  - `400 Bad Request`: Nieprawidłowe parametry zapytania.
  - `401 Unauthorized`: Brak lub nieprawidłowy token uwierzytelniający.
  - `500 Internal Server Error`: Wewnętrzny błąd serwera.

## 5. Przepływ danych
1. Klient wysyła żądanie `GET` na adres `/api/transactions` z opcjonalnymi parametrami.
2. Warstwa bezpieczeństwa API Platform weryfikuje token JWT. W przypadku niepowodzenia zwraca `401 Unauthorized`.
3. API Platform uruchamia domyślny `State Provider` dla kolekcji encji `Transaction`.
4. Rozszerzenie `CurrentUserExtension` automatycznie modyfikuje zapytanie Doctrine, dodając warunek `WHERE` filtrujący transakcje należące do zalogowanego użytkownika.
5. Filtry API Platform (`DateFilter`, `OrderFilter`) dodają do zapytania warunki na podstawie parametrów `startDate`, `endDate`, `sortBy`, `sortOrder`.
6. Paginator API Platform wykonuje zapytanie i zwraca obiekt `Paginator` zawierający encje `Transaction`.
7. Dedykowany `Normalizer` (implementujący `NormalizerInterface`) przechwytuje obiekt `Paginator`.
8. Normalizer transformuje dane:
   - Tworzy obiekt `PaginatedTransactionOutput`.
   - Iteruje po encjach `Transaction`, mapując każdą z nich na `TransactionOutput` DTO.
   - Wypełnia obiekt `PaginationDetails` na podstawie metadanych z `Paginator`.
9. Zserializowana odpowiedź w niestandardowym formacie JSON jest zwracana do klienta ze statusem `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego będzie chroniony za pomocą JWT. W definicji `#[ApiResource]` dla encji `Transaction` zostanie dodany atrybut `security: "is_granted('ROLE_USER')"`.
- **Autoryzacja**: Izolacja danych między użytkownikami zostanie zapewniona przez `CurrentUserExtension`. Gwarantuje to, że żaden użytkownik nie będzie miał dostępu do transakcji innego użytkownika.
- **Walidacja danych wejściowych**: Parametry zapytania będą walidowane, aby zapobiec błędom i potencjalnym atakom (np. SQL Injection, chociaż ORM Doctrine w dużym stopniu przed tym chroni).

## 7. Obsługa błędów
- `400 Bad Request`: Zostanie zwrócony, jeśli parametry zapytania będą nieprawidłowe (np. zły format daty, niepoprawna wartość `sortOrder`). Odpowiedzialność za to ponoszą wbudowane mechanizmy API Platform i Symfony Validator.
- `401 Unauthorized`: Zostanie zwrócony przez system bezpieczeństwa, jeśli użytkownik nie jest zalogowany.
- `500 Internal Server Error`: Błędy na poziomie aplikacji lub bazy danych będą logowane za pomocą Monologa i zwracane jako ogólny błąd serwera.

## 8. Rozważania dotyczące wydajności
- **Indeksowanie bazy danych**: Aby zapewnić szybkie filtrowanie i sortowanie, na tabeli `transactions` zostanie założony złożony indeks na kolumnach `(user_id, date)`.
- **Paginacja**: Operacja będzie korzystać z wbudowanego mechanizmu paginacji API Platform (`page` i `itemsPerPage`), co jest wydajne i zwalnia nas z implementacji własnej logiki. Domyślne nazwy parametrów (`page`, `itemsPerPage`) zostaną zmapowane na `page` i `limit` zgodnie ze specyfikacją.
- **Unikanie problemu N+1**: Podczas transformacji danych w normalizerze, relacja do `Subcategory` zostanie pobrana za pomocą `JOIN` w zapytaniu DQL, aby uniknąć dodatkowych zapytań do bazy danych dla każdej transakcji.

## 9. Etapy wdrożenia
1. **Model Danych**:
   - Utworzenie encji `App\Entity\Transaction` i zmapowanie jej na tabelę `transactions` za pomocą atrybutów Doctrine ORM.
   - Zdefiniowanie relacji `ManyToOne` do encji `User` i `Subcategory`.
   - Upewnienie się, że istnieje Value Object `Money` oraz dedykowany typ Doctrine (`MoneyType`) do obsługi kolumny `amount` (JSONB).
2. **DTOs**:
   - Implementacja wszystkich DTOs (`MoneyOutput`, `SubcategoryNestedOutput`, `TransactionOutput`, `PaginationDetails`, `PaginatedTransactionOutput`) w katalogu `src/DTO/`.
3. **Konfiguracja API Resource**:
   - Dodanie atrybutu `#[ApiResource]` do encji `Transaction`.
   - Skonfigurowanie operacji `GetCollection` z atrybutem `security: "is_granted('ROLE_USER')"` oraz ustawienie parametrów paginacji: `paginationItemsPerPage: 30` (dla `limit`) i `paginationParameterName: 'page'`.
4. **Filtry i Sortowanie**:
   - Dodanie wbudowanych filtrów API Platform do zasobu `Transaction`:
     - `#[ApiFilter(DateFilter::class, properties: ['date'])]`
     - `#[ApiFilter(OrderFilter::class, properties: ['date', 'amount.amount'], arguments: ['orderParameterName' => 'sortOrder', 'sortParameterName' => 'sortBy'])]`
5. **Normalizer**:
   - Stworzenie serwisu `App\Serializer\PaginatedTransactionNormalizer` implementującego `NormalizerInterface` i `CacheableSupportsInterface`.
   - Implementacja logiki transformującej obiekt `Paginator<Transaction>` na `PaginatedTransactionOutput`.
6. **Rozszerzenie Doctrine**:
   - Weryfikacja, czy istniejący `CurrentUserExtension` poprawnie obsługuje nową encję `Transaction`. Jeśli encja implementuje interfejs `UserOwnedInterface`, rozszerzenie powinno zadziałać automatycznie.
7. **Migracje i Indeksy**:
   - Wygenerowanie migracji Doctrine w celu utworzenia tabeli `transactions`.
   - Dodanie w migracji polecenia tworzącego indeks na `(user_id, date)`.
8. **Testy**:
   - Utworzenie testu `tests/Api/TransactionGetApiTest.php` dziedziczącego po `ApiTestCase`.
   - Zaimplementowanie scenariuszy testowych dla:
     - Poprawnego pobrania listy transakcji.
     - Działania paginacji (`page`, `limit`).
     - Działania filtrowania po dacie.
     - Działania sortowania.
     - Sprawdzenia izolacji danych (próba dostępu do transakcji innego użytkownika).
     - Obsługi błędów (`401 Unauthorized`, `400 Bad Request`).
