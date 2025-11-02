# API Endpoint Implementation Plan: GET Budget by Year and Month

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest umożliwienie zalogowanemu użytkownikowi pobrania szczegółowych informacji o swoim budżecie na określony rok i miesiąc. Endpoint zwróci dane budżetu, w tym planowane przychody oraz zdefiniowane limity wydatków dla poszczególnych podkategorii. Jeśli budżet na dany okres nie istnieje, zostanie zwrócony błąd 404.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/budgets/{year}/{month}`
- **Parametry**:
  - **Wymagane (w ścieżce URL)**:
    - `year` (`integer`): Rok, którego dotyczy budżet (np. 2025). Musi być prawidłową liczbą całkowitą.
    - `month` (`integer`): Miesiąc, którego dotyczy budżet (np. 10). Musi być liczbą całkowitą z zakresu 1-12.
- **Request Body**: Brak.

## 3. Wykorzystywane typy
W celu zapewnienia zgodności struktury odpowiedzi ze specyfikacją, zostaną utworzone następujące obiekty DTO (Data Transfer Objects):

- **`App\DTO\BudgetOutput`**
  ```php
  class BudgetOutput {
      public Uuid $id;
      public int $year;
      public int $month;
      public Money $plannedIncome;
      /** @var BudgetLimitOutput[] */
      public array $limits;
  }
  ```

- **`App\DTO\BudgetLimitOutput`**
  ```php
  class BudgetLimitOutput {
      public Uuid $id;
      public Money $limitAmount;
      public SubcategoryNestedOutput $subcategory;
  }
  ```

- **`App\DTO\SubcategoryNestedOutput`**
  ```php
  class SubcategoryNestedOutput {
      public Uuid $id;
      public string $name;
  }
  ```
Struktura `Money` będzie reużywana z `App\Model\ValueObject\Money`.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (`200 OK`)**:
  ```json
  {
    "id": "uuid-v7-string",
    "year": 2025,
    "month": 10,
    "plannedIncome": { "amount": 600000, "currency": "PLN" },
    "limits": [
      {
        "id": "uuid-v7-string",
        "limitAmount": { "amount": 50000, "currency": "PLN" },
        "subcategory": {
          "id": "uuid-v7-string",
          "name": "Groceries"
        }
      }
    ]
  }
  ```
- **Odpowiedzi błędów**:
  - `401 Unauthorized`: Użytkownik nie jest uwierzytelniony.
  - `404 Not Found`: Budżet dla podanego okresu i zalogowanego użytkownika nie został znaleziony.
  - `422 Unprocessable Entity`: Parametry `year` lub `month` są nieprawidłowe (np. `month` jest poza zakresem 1-12).

## 5. Przepływ danych
1.  Żądanie `GET` trafia do routera API Platform, pasując do wzorca `/api/budgets/{year}/{month}`.
2.  API Platform uruchamia walidację dla zmiennych ze ścieżki (`year`, `month`).
3.  Po pomyślnej walidacji, wywoływany jest dedykowany `State Provider`: `App\State\BudgetProvider`.
4.  `BudgetProvider` wstrzykuje zależności: `BudgetRepository` i `Security`.
5.  Provider pobiera aktualnie zalogowanego użytkownika (`User`) z serwisu `Security`. Jeśli użytkownik jest niezalogowany, API Platform zwróci błąd 401.
6.  Provider wywołuje metodę `findOneBy(['user' => $user, 'year' => $year, 'month' => $month])` na `BudgetRepository`.
7.  Jeśli repozytorium nie zwróci obiektu `Budget`, provider rzuca wyjątek `NotFoundHttpException`, co skutkuje odpowiedzią `404 Not Found`.
8.  Jeśli obiekt `Budget` zostanie znaleziony, provider go zwraca.
9.  API Platform (wraz z komponentem Serializer) automatycznie mapuje dane z encji `Budget` i jej relacji (`budgetLimits`, `subcategory`) na strukturę zdefiniowaną w DTO `App\DTO\BudgetOutput`.
10. Sformatowana odpowiedź JSON jest wysyłana do klienta z kodem statusu `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint będzie zabezpieczony za pomocą `lexik/jwt-authentication-bundle`. Dostęp będzie wymagał ważnego tokenu JWT w nagłówku `Authorization`.
- **Autoryzacja**: Dostęp do zasobu zostanie ograniczony do zalogowanych użytkowników poprzez atrybut `security: "is_granted('IS_AUTHENTICATED_FULLY')"`.
- **Izolacja danych**: Logika pobierania budżetu w `BudgetProvider` będzie ściśle powiązana z ID zalogowanego użytkownika, co zapobiega dostępowi do budżetów innych użytkowników.

## 7. Rozważania dotyczące wydajności
- **Zapytania do bazy danych**: Zapytanie o budżet będzie oparte o unikalny indeks (`uq_budget_user_year_month`), co zapewnia wysoką wydajność.
- **Problem N+1**: Podczas serializacji limitów budżetowych i ich podkategorii może wystąpić problem N+1 zapytań. Aby temu zapobiec, w `BudgetRepository` należy zastosować `LEFT JOIN` do powiązanych encji (`budgetLimits` oraz `subcategory`) w celu pobrania wszystkich potrzebnych danych w jednym zapytaniu (eager loading).

## 8. Etapy wdrożenia
1.  **Utworzenie DTO**:
    -   Stworzyć pliki `BudgetOutput.php`, `BudgetLimitOutput.php` i `SubcategoryNestedOutput.php` w katalogu `src/DTO/`.
    -   Zdefiniować w nich publiczne właściwości zgodnie z sekcją 3.

2.  **Utworzenie State Providera**:
    -   Stworzyć klasę `BudgetProvider` w `src/State/`, implementującą `ApiPlatform\State\ProviderInterface`.
    -   Wstrzyknąć `BudgetRepository` i `Security` do konstruktora.
    -   Zaimplementować metodę `provide()`, która pobiera `year`, `month` i `user`, a następnie odpytuje repozytorium.
    -   W przypadku braku wyniku, rzucić `NotFoundHttpException`.

3.  **Aktualizacja Encji `Budget`**:
    -   W pliku `src/Entity/Budget.php` dodać atrybut `#[ApiResource]` dla operacji `Get`.
    -   Skonfigurować `uriTemplate`, `provider` oraz `output` DTO:
        ```php
        #[ApiResource(
            operations: [
                new Get(
                    uriTemplate: '/budgets/{year}/{month}',
                    provider: BudgetProvider::class,
                    output: BudgetOutput::class,
                    security: "is_granted('IS_AUTHENTICATED_FULLY')",
                    uriVariables: [
                        'year' => new Link(fromClass: self::class, identifiers: ['year']),
                        'month' => new Link(fromClass: self::class, identifiers: ['month']),
                    ]
                )
            ]
        )]
        ```
    - Dodać walidację dla `year` i `month` w `uriVariables` za pomocą asercji:
        ```php
        'year' => new Link(fromClass: self::class, identifiers: ['year'], constraints: [new Assert\Range(min: 2000, max: 2100)])
        'month' => new Link(fromClass: self::class, identifiers: ['month'], constraints: [new Assert\Range(min: 1, max: 12)])
        ```
        *Uwaga: `Link` może nie wspierać `constraints` bezpośrednio. Alternatywnie walidację można przeprowadzić w providerze.*

4.  **Aktualizacja Repozytorium (Opcjonalnie)**:
    -   Jeśli problem N+1 będzie zauważalny, stworzyć w `BudgetRepository` dedykowaną metodę np. `findWithRelations(User $user, int $year, int $month)` wykorzystującą `QueryBuilder` z odpowiednimi `join` i `addSelect`.

5.  **Napisanie Testów API**:
    -   Stworzyć plik `BudgetGetApiTest.php` w `tests/Api/`.
    -   Zaimplementować testy sprawdzające:
        -   Pomyślne pobranie budżetu (status 200 i poprawna struktura JSON).
        -   Próbę pobrania nieistniejącego budżetu (oczekiwany status 404).
        -   Próbę dostępu bez uwierzytelnienia (oczekiwany status 401).
        -   Próbę pobrania budżetu innego użytkownika (oczekiwany status 404).
        -   Próbę z nieprawidłowymi parametrami (np. `month=13`, oczekiwany status 404 lub 400).


