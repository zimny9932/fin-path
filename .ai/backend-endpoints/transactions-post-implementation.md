# API Endpoint Implementation Plan: POST /api/transactions

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia uwierzytelnionemu użytkownikowi utworzenie nowej transakcji finansowej (przychodu lub wydatku). Każda transakcja jest powiązana z istniejącą podkategorią należącą do użytkownika i zawiera kwotę, datę oraz opcjonalny opis.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/transactions`
- **Request Body**:
  ```json
  {
    "subcategoryId": "string (uuid)",
    "amount": {
      "amount": "integer",
      "currency": "string (ISO 4217)"
    },
    "date": "string (YYYY-MM-DD)",
    "description": "string|null"
  }
  ```
  - `subcategoryId`: Identyfikator podkategorii, do której ma być przypisana transakcja.
  - `amount.amount`: Kwota transakcji w najmniejszej jednostce waluty (np. grosze).
  - `amount.currency`: Trzyliterowy kod waluty zgodny z ISO 4217 (np. "PLN").
  - `date`: Data transakcji.
  - `description`: Opcjonalny opis transakcji.

## 3. Wykorzystywane typy
- **`TransactionInput` (DTO)**: Klasa DTO do obsługi danych wejściowych i walidacji.
- **`MoneyInput` (DTO)**: Zagnieżdżona klasa DTO do walidacji obiektu kwoty (`amount`).
- **`Transaction` (Entity)**: Encja Doctrine, która będzie tworzona i zapisywana w bazie danych.
- **`TransactionOutput` (DTO)**: Klasa DTO używana do formatowania odpowiedzi, zapewniając spójność danych wyjściowych.

## 4. Szczegóły odpowiedzi
- **Success (`201 Created`)**:
  - W odpowiedzi zwracany jest nowo utworzony obiekt transakcji, zserializowany przy użyciu `TransactionOutput` DTO.
  ```json
  {
    "id": "string (uuid)",
    "description": "string|null",
    "date": "string (YYYY-MM-DDTHH:mm:ss+00:00)",
    "amount": {
      "amount": "integer",
      "currency": "string"
    },
    "subcategory": {
      "id": "string (uuid)",
      "name": "string",
      "mainCategory": "string"
    }
  }
  ```
- **Error**:
  - `400 Bad Request`: Odpowiedź z listą błędów walidacji w standardowym formacie API Platform (Hydra).
  - `401 Unauthorized`: Standardowa odpowiedź błędu z `lexik/jwt-authentication-bundle`.
  - `404 Not Found`: Standardowa odpowiedź błędu API Platform.

## 5. Przepływ danych
1. Klient wysyła żądanie `POST` na `/api/transactions` z poprawnym tokenem JWT i danymi transakcji w ciele żądania.
2. API Platform deserializuje ciało żądania do obiektu `TransactionInput` DTO.
3. Komponent Symfony Validator waliduje obiekt `TransactionInput`. W przypadku błędu, API Platform zwraca odpowiedź `400 Bad Request`.
4. Po pomyślnej walidacji, API Platform przekazuje DTO do dedykowanego procesora stanu: `TransactionProcessor`.
5. `TransactionProcessor` pobiera aktualnie zalogowanego użytkownika (`User`).
6. Procesor wyszukuje encję `Subcategory` na podstawie `subcategoryId` z DTO, upewniając się, że należy ona do zalogowanego użytkownika. Jeśli nie zostanie znaleziona, rzuca wyjątek `NotFoundHttpException`.
7. Procesor tworzy obiekt `Money` (Value Object) na podstawie danych z DTO.
8. Na podstawie DTO i pobranych encji, procesor tworzy nową instancję encji `Transaction`.
9. `EntityManager` zapisuje nową encję `Transaction` w bazie danych.
10. Procesor zwraca nowo utworzoną encję `Transaction`.
11. API Platform serializuje zwróconą encję do formatu JSON, używając `TransactionOutput` DTO, i wysyła odpowiedź `201 Created` do klienta.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint jest zabezpieczony za pomocą `lexik/jwt-authentication-bundle`. Wymagany jest prawidłowy i niewygasły token JWT w nagłówku `Authorization`.
- **Autoryzacja**: Operacja `Post` w `ApiResource` dla `Transaction` będzie zabezpieczona za pomocą `security: "is_granted('ROLE_USER')"`.
- **Izolacja danych**: `TransactionProcessor` musi zweryfikować, czy `subcategoryId` należy do zalogowanego użytkownika, aby uniemożliwić tworzenie transakcji w imieniu innych użytkowników.
- **Walidacja danych wejściowych**: Rygorystyczna walidacja na poziomie DTO zapobiega wprowadzeniu niepoprawnych danych do systemu.

## 7. Obsługa błędów
- **`400 Bad Request`**: Zwracany, gdy dane wejściowe nie przejdą walidacji (np. brak wymaganych pól, nieprawidłowy format).
- **`401 Unauthorized`**: Zwracany, gdy token JWT jest nieprawidłowy, wygasł lub nie został dostarczony.
- **`404 Not Found`**: Zwracany, gdy podana w `subcategoryId` podkategoria nie istnieje lub nie należy do zalogowanego użytkownika.
- **`500 Internal Server Error`**: Zwracany w przypadku nieoczekiwanych błędów po stronie serwera (np. błąd połączenia z bazą danych). Błędy te będą logowane przez Monolog.

## 8. Rozważania dotyczące wydajności
- Operacja polega na jednym zapytaniu `SELECT` (w celu weryfikacji podkategorii) i jednym `INSERT` (w celu utworzenia transakcji), co czyni ją wysoce wydajną.
- Indeksy na kluczach obcych (`user_id`, `subcategory_id`) zapewniają szybkie wyszukiwanie.
- Nie przewiduje się żadnych wąskich gardeł wydajnościowych dla tego punktu końcowego.

## 9. Etapy wdrożenia
1.  **Utworzenie DTOs**:
    -   Stworzyć plik `src/DTO/MoneyInput.php` z właściwościami `amount` i `currency` oraz odpowiednimi asercjami walidacyjnymi.
    -   Stworzyć plik `src/DTO/TransactionInput.php` z właściwościami `subcategoryId`, `amount` (jako `MoneyInput`), `date`, `description`. Dodać asercje, w tym `#[Assert\Valid]` dla `amount`.
2.  **Konfiguracja `ApiResource`**:
    -   W encji `Transaction` (`src/Entity/Transaction.php`) dodać nową operację `Post`.
    -   Skonfigurować operację `Post`, aby używała `TransactionInput` jako DTO wejściowego (`input: TransactionInput::class`) oraz `TransactionOutput` jako DTO wyjściowego (`output: TransactionOutput::class`).
    -   Dodać zabezpieczenie `security: "is_granted('ROLE_USER')"`.
3.  **Implementacja `State Processor`**:
    -   Utworzyć nową klasę `TransactionProcessor` w `src/State/`.
    -   Zaimplementować w niej interfejs `ApiPlatform\State\ProcessorInterface`.
    -   W metodzie `supports()` określić, że procesor obsługuje `TransactionInput` i operacje typu `post`.
    -   W metodzie `process()` zaimplementować logikę opisaną w sekcji "Przepływ danych". Wstrzyknąć wymagane zależności (`Security`, `SubcategoryRepository`, `EntityManagerInterface`).
4.  **Przypisanie Procesora**:
    -   W konfiguracji operacji `Post` w `ApiResource` wskazać nowo utworzony procesor (`processor: TransactionProcessor::class`).
5.  **Testy API**:
    -   Stworzyć nową klasę testową `tests/Api/TransactionPostApiTest.php`.
    -   Dodać testy sprawdzające:
        -   Pomyślne utworzenie transakcji (status `201`).
        -   Próbę utworzenia transakcji bez uwierzytelnienia (status `401`).
        -   Próbę utworzenia transakcji z nieprawidłowymi danymi (status `400`).
        -   Próbę utworzenia transakcji dla nieistniejącej podkategorii (status `404`).
        -   Próbę utworzenia transakcji dla podkategorii należącej do innego użytkownika (status `404`).
