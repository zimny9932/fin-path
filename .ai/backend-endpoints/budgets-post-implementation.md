# API Endpoint Implementation Plan: POST /api/budgets

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia uwierzytelnionym użytkownikom utworzenie nowego budżetu na określony rok i miesiąc. Użytkownik może zdefiniować planowany całkowity dochód oraz opcjonalnie ustawić limity wydatków dla wybranych podkategorii. Endpoint zapewnia, że użytkownik nie może utworzyć więcej niż jednego budżetu na ten sam okres.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/budgets`
- **Request Body**:
  ```json
  {
    "year": 2025,
    "month": 11,
    "plannedIncome": { "amount": 650000, "currency": "PLN" },
    "limits": [
      {
        "subcategoryId": "a1b2c3d4-e5f6-7890-1234-567890abcdef",
        "limitAmount": { "amount": 40000, "currency": "PLN" }
      }
    ]
  }
  ```
- **Parametry**:
  - Wymagane: `year`, `month`, `plannedIncome`, `limits` (może być pustą tablicą).

## 3. Wykorzystywane typy
- **DTO wejściowe**:
  - `App\DTO\BudgetInput`: Główny obiekt transferu danych dla żądania.
    - `year: int`
    - `month: int`
    - `plannedIncome: MoneyInput`
    - `limits: BudgetLimitInput[]`
  - `App\DTO\BudgetLimitInput`: Reprezentuje pojedynczy limit w budżecie.
    - `subcategoryId: string (uuid)`
    - `limitAmount: MoneyInput`
  - `App\DTO\MoneyInput`: Istniejący DTO do reprezentacji kwot.
- **DTO wyjściowe**:
  - `App\DTO\BudgetOutput`: Istniejący DTO, który zostanie użyty do serializacji odpowiedzi.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (`201 Created`)**:
  ```json
  {
    "year": 2025,
    "month": 11,
    "plannedIncome": { "amount": 650000, "currency": "PLN" },
    "limits": [
      {
        "limitId": "f0e9d8c7-b6a5-4321-fedc-ba9876543210",
        "subcategory": {
          "id": "a1b2c3d4-e5f6-7890-1234-567890abcdef",
          "name": "Jedzenie na mieście",
          "mainCategory": "food"
        },
        "limitAmount": { "amount": 40000, "currency": "PLN" }
      }
    ]
  }
  ```
- **Odpowiedzi błędów**:
  - `400 Bad Request`: Błędy walidacji danych wejściowych.
  - `401 Unauthorized`: Użytkownik nie jest zalogowany.
  - `409 Conflict`: Budżet dla podanego roku i miesiąca już istnieje.
  - `500 Internal Server Error`: Wewnętrzny błąd serwera.

## 5. Przepływ danych
1.  Żądanie `POST /api/budgets` dociera do API Platform.
2.  Framework deserializuje ciało żądania do obiektu `App\DTO\BudgetInput`.
3.  Uruchamiany jest walidator Symfony, który sprawdza poprawność danych w DTO (`BudgetInput`, `BudgetLimitInput`, `MoneyInput`). W przypadku błędu zwracana jest odpowiedź `400`.
4.  Jeśli walidacja przejdzie pomyślnie, API Platform wywołuje dedykowany `State Processor` - `App\State\BudgetProcessor`.
5.  `BudgetProcessor` pobiera zalogowanego użytkownika z serwisu `Security`.
6.  Procesor sprawdza w repozytorium (`BudgetRepository`), czy użytkownik posiada już budżet na dany `year` i `month`. Jeśli tak, rzuca wyjątek `BudgetAlreadyExistsException`, co skutkuje odpowiedzią `409`.
7.  Procesor pobiera wszystkie podkategorie na podstawie `subcategoryId` z tablicy `limits` i weryfikuje, czy należą one do zalogowanego użytkownika.
8.  Tworzona jest nowa encja `App\Entity\Budget`.
9.  Dla każdego elementu w tablicy `limits` tworzona jest nowa encja `App\Entity\BudgetLimit` i jest ona powiązana z nowym budżetem oraz odpowiednią podkategorią.
10. `EntityManager` zapisuje w jednej transakcji encję `Budget` wraz z powiązanymi `BudgetLimit` (dzięki `cascade: ['persist']`).
11. `BudgetProcessor` zwraca nowo utworzoną encję `Budget`.
12. API Platform serializuje encję `Budget` do formatu JSON, używając `App\DTO\BudgetOutput`, i wysyła odpowiedź `201 Created`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint będzie zabezpieczony i dostępny tylko dla użytkowników z rolą `ROLE_USER`. W konfiguracji `#[ApiResource]` zostanie użyty atrybut `security: "is_granted('ROLE_USER')"`.
- **Autoryzacja**: Cała logika autoryzacji będzie zaimplementowana w `BudgetProcessor`. Procesor zapewni, że:
    - Budżet jest tworzony wyłącznie dla aktualnie zalogowanego użytkownika.
    - Wszystkie `subcategoryId` podane w `limits` należą do tego użytkownika.
- **Walidacja danych**: Ścisła walidacja na poziomie DTO zapobiegnie atakom typu Mass Assignment oraz zapewni integralność danych.

## 7. Rozważania dotyczące wydajności
- Operacja jest transakcyjna i obejmuje kilka zapytań do bazy danych:
    1.  Jedno zapytanie `SELECT` w celu sprawdzenia istnienia budżetu.
    2.  Jedno zapytanie `SELECT` w celu pobrania wszystkich podkategorii (użycie `WHERE id IN (...)` w celu uniknięcia problemu N+1).
    3.  Jedna operacja `INSERT` dla budżetu i `N` operacji `INSERT` dla limitów, wszystko w ramach jednej transakcji.
- Przy rozsądnej liczbie limitów w pojedynczym żądaniu, operacja powinna być wysoce wydajna. Nie przewiduje się wąskich gardeł.

## 8. Etapy wdrożenia
1.  **Utworzenie DTO**:
    - Stworzyć plik `src/DTO/BudgetLimitInput.php` z właściwościami `subcategoryId` i `limitAmount` oraz odpowiednimi asercjami walidacji (`#[Assert\NotBlank]`, `#[Assert\Uuid]`, `#[Assert\Valid]`).
    - Stworzyć plik `src/DTO/BudgetInput.php` z właściwościami `year`, `month`, `plannedIncome`, `limits` oraz asercjami (`#[Assert\NotBlank]`, `#[Assert\Range]`, `#[Assert\Valid]`).

2.  **Obsługa błędu konfliktu**:
    - Stworzyć klasę wyjątku `src/Exception/BudgetAlreadyExistsException.php`.
    - Stworzyć `src/EventSubscriber/BudgetAlreadyExistsExceptionSubscriber.php`, który nasłuchuje na ten wyjątek i zwraca odpowiedź `JsonResponse` z kodem statusu `409 Conflict`.

3.  **Implementacja State Processor**:
    - Stworzyć klasę `src/State/BudgetProcessor.php` implementującą `ApiPlatform\State\ProcessorInterface`.
    - Wstrzyknąć do konstruktora zależności: `BudgetRepository`, `SubcategoryRepository`, `Security`, `EntityManagerInterface`.
    - Zaimplementować metodę `process()`, która realizuje logikę opisaną w sekcji "Przepływ danych".

4.  **Aktualizacja encji `Budget`**:
    - Zaktualizować atrybut `#[ApiResource]` w klasie `src/Entity/Budget.php`, dodając nową operację `Post`.
    - Skonfigurować operację `Post`, aby używała `BudgetInput` jako DTO wejściowego oraz `BudgetProcessor` jako procesora stanu:
      ```php
      new Post(
          security: "is_granted('ROLE_USER')",
          input: BudgetInput::class,
          output: BudgetOutput::class,
          processor: BudgetProcessor::class
      )
      ```
    
5.  **Testy API**:
    - Stworzyć nową klasę testową `tests/Api/BudgetPostApiTest.php`.
    - Zaimplementować testy pokrywające następujące scenariusze:
        - Pomyślne utworzenie budżetu bez limitów (`201 Created`).
        - Pomyślne utworzenie budżetu z limitami (`201 Created`).
        - Próba utworzenia budżetu dla istniejącego miesiąca (`409 Conflict`).
        - Próba utworzenia budżetu z niepoprawnymi danymi (np. miesiąc 13) (`400 Bad Request`).
        - Próba utworzenia budżetu przez niezalogowanego użytkownika (`401 Unauthorized`).
        - Próba utworzenia budżetu z limitem dla podkategorii nienależącej do użytkownika (`400 Bad Request`).
