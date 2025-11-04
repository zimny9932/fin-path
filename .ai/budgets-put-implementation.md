# API Endpoint Implementation Plan: PUT /api/budgets/{year}/{month}

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest umożliwienie użytkownikom aktualizacji istniejącego budżetu miesięcznego. Użytkownik może zmodyfikować planowane przychody oraz limity wydatków dla poszczególnych podkategorii. Operacja jest dostępna tylko dla uwierzytelnionych użytkowników i ograniczona do budżetów, których są właścicielami.

## 2. Szczegóły żądania
- **Metoda HTTP**: `PUT`
- **Struktura URL**: `/api/budgets/{year}/{month}`
- **Parametry**:
  - **Wymagane (w ścieżce)**:
    - `year` (`integer`): Rok budżetu (np. `2025`).
    - `month` (`integer`): Miesiąc budżetu (zakres `1-12`).
- **Request Body**:
  - **Content-Type**: `application/json`
  - **Struktura**: Obiekt JSON zgodny z `App\DTO\BudgetInput`.
    ```json
    {
      "plannedIncome": {
        "amount": 500000,
        "currency": "PLN"
      },
      "budgetLimits": [
        {
          "subcategory": "/api/subcategories/{id}",
          "limit": {
            "amount": 50000,
            "currency": "PLN"
          }
        }
      ]
    }
    ```

## 3. Wykorzystywane typy
- **DTO wejściowe**: `App\DTO\BudgetInput` - do deserializacji i walidacji ciała żądania.
- **DTO wejściowe (zagnieżdżone)**: `App\DTO\BudgetLimitInput`, `App\DTO\MoneyInput`.
- **DTO wyjściowe**: `App\DTO\BudgetOutput` - do serializacji zaktualizowanego zasobu w odpowiedzi.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (Success Response)**:
  - **Kod stanu**: `200 OK`
  - **Treść**: Zaktualizowany obiekt budżetu w formacie `BudgetOutput`.
- **Odpowiedzi błędu (Error Responses)**:
  - `400 Bad Request`: Błędy walidacji danych wejściowych.
  - `401 Unauthorized`: Brak lub nieprawidłowy token JWT.
  - `404 Not Found`: Budżet dla podanego `year`, `month` i zalogowanego użytkownika nie istnieje.
  - `500 Internal Server Error`: Wewnętrzny błąd serwera.

## 5. Przepływ danych
1. Użytkownik wysyła żądanie `PUT` na adres `/api/budgets/{year}/{month}` z danymi budżetu w ciele żądania.
2. API Platform autoryzuje użytkownika na podstawie tokenu JWT.
3. `BudgetProvider` jest wywoływany w celu pobrania z bazy danych encji `Budget` na podstawie parametrów `year`, `month` oraz ID zalogowanego użytkownika (dzięki `CurrentUserExtension`). Jeśli encja nie zostanie znaleziona, zwracany jest błąd `404 Not Found`.
4. API Platform weryfikuje uprawnienia za pomocą reguły `security: "object.getUser() == user"`.
5. Ciało żądania jest deserializowane do obiektu DTO `BudgetInput` i walidowane. W przypadku błędów walidacji zwracany jest błąd `400 Bad Request`.
6. `BudgetProcessor` otrzymuje pobraną encję `Budget` oraz zwalidowane DTO `BudgetInput`.
7. Procesor aktualizuje właściwości encji `Budget` (np. `plannedIncome`) oraz kolekcję `budgetLimits` na podstawie danych z DTO.
8. `EntityManager` (Doctrine) zapisuje zmiany w bazie danych w ramach transakcji.
9. Zaktualizowana encja `Budget` jest serializowana do formatu `BudgetOutput` i zwracana użytkownikowi z kodem statusu `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego jest chroniony za pomocą `lexik/jwt-authentication-bundle`. Każde żądanie musi zawierać prawidłowy token JWT w nagłówku `Authorization`.
- **Autoryzacja**: Operacja `Put` w encji `Budget` zostanie zabezpieczona za pomocą atrybutu `security`:
  ```php
  #[Put(security: "is_granted('ROLE_USER') and object.getUser() == user")]
  ```
  Zapewni to, że tylko właściciel budżetu może go modyfikować.
- **Walidacja danych**: Wszystkie dane wejściowe z DTO `BudgetInput` będą rygorystycznie walidowane przy użyciu komponentu Symfony Validator, aby zapobiec atakom takim jak SQL Injection czy Cross-Site Scripting (XSS).

## 7. Obsługa błędów
- **Błędy walidacji (400)**: API Platform automatycznie przechwytuje wyjątki `ValidationException` i zwraca szczegółową odpowiedź z listą błędów.
- **Zasób nieznaleziony (404)**: `BudgetProvider` zwróci `null`, co API Platform zinterpretuje jako `404 Not Found`.
- **Brak uwierzytelnienia (401)**: Obsługiwane przez `lexik/jwt-authentication-bundle`.
- **Brak uprawnień (403/404)**: Obsługiwane przez warunek `security` i `CurrentUserExtension`.
- **Błędy serwera (500)**: Wszelkie nieprzechwycone wyjątki zostaną obsłużone przez mechanizmy Symfony, a błędy zostaną zalogowane przy użyciu Monolog.

## 8. Rozważania dotyczące wydajności
- Operacja jest ograniczona do jednego użytkownika i konkretnego miesiąca, co minimalizuje obciążenie bazy danych.
- Zapytania do bazy danych będą proste i indeksowane (klucz unikalny na `user_id`, `year`, `month`).
- Aktualizacja limitów budżetowych (`budgetLimits`) może generować dodatkowe zapytania. Należy zadbać o optymalne zarządzanie kolekcją w Doctrine, aby uniknąć problemu N+1, np. przez odpowiednie użycie `cascade` i `orphanRemoval`.

## 9. Etapy wdrożenia
1. **Aktualizacja `ApiResource`**: Dodać nową operację `#[Put]` do atrybutu `#[ApiResource]` w klasie `App\Entity\Budget`.
   - Skonfigurować `uriTemplate`, `requirements`, `security`, `input`, `output`.
   - Wskazać `BudgetProvider` jako `provider` i `BudgetProcessor` jako `processor`.
2. **Rozszerzenie `BudgetProcessor`**: Zmodyfikować metodę `process` w `App\State\BudgetProcessor`, aby obsługiwała aktualizację istniejącej encji `Budget`.
   - Dodać logikę rozróżniającą operację `POST` (tworzenie) od `PUT` (aktualizacja).
   - Zaimplementować logikę aktualizacji pól encji na podstawie danych z `BudgetInput` DTO.
   - Upewnić się, że kolekcja `budgetLimits` jest poprawnie synchronizowana (usuwanie starych, dodawanie/aktualizowanie nowych).
3. **Weryfikacja walidacji**: Upewnić się, że DTO `BudgetInput` i zagnieżdżone w nim obiekty DTO posiadają odpowiednie asercje walidacyjne (np. `#[NotBlank]`, `#[Valid]`).
4. **Testy API**: Stworzyć nową klasę testową lub dodać metody testowe w `tests/Api/BudgetTest.php` w celu weryfikacji nowego punktu końcowego.
   - Test pomyślnej aktualizacji (`200 OK`).
   - Test próby aktualizacji nieistniejącego budżetu (`404 Not Found`).
   - Test z nieprawidłowymi danymi wejściowymi (`400 Bad Request`).
   - Test próby aktualizacji bez uwierzytelnienia (`401 Unauthorized`).
   - Test próby aktualizacji budżetu innego użytkownika (powinien zwrócić `404 Not Found`).
5. **Dokumentacja**: Sprawdzić, czy nowy punkt końcowy jest poprawnie udokumentowany w generowanej specyfikacji OpenAPI / Swagger UI.
