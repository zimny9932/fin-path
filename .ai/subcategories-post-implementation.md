# API Endpoint Implementation Plan: POST /api/subcategories

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest umożliwienie uwierzytelnionym użytkownikom tworzenia nowych, spersonalizowanych podkategorii dla swoich transakcji. Każda podkategoria jest przypisana do typu transakcji (`income` lub `expense`) oraz do głównej kategorii, co pozwala na szczegółową kategoryzację finansów.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/subcategories`
- **Request Body**:
  ```json
  {
    "name": "string (max 100)",
    "type": "string (enum: 'income' | 'expense')",
    "mainCategory": "string (enum: 'BILLS' | ...)"
  }
  ```

## 3. Wykorzystywane typy
- **DTO Wejściowe**: `App\DTO\SubcategoryInput`
  - Odpowiedzialny za przyjmowanie i walidację danych wejściowych.
  - Właściwości: `name`, `type`, `mainCategory`.
- **DTO Wyjściowe**: `App\DTO\SubcategoryOutput`
  - Używany do serializacji odpowiedzi, aby uniknąć ujawniania wewnętrznej struktury encji.
  - Właściwości: `id`, `name`, `type`, `mainCategory`.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu**:
  - **Kod statusu**: `201 Created`
  - **Treść odpowiedzi**:
    ```json
    {
      "id": "a1b2c3d4-e5f6-a7b8-c9d0-e1f2a3b4c5d6",
      "name": "Internet Bill",
      "type": "expense",
      "mainCategory": "BILLS"
    }
    ```
- **Odpowiedzi błędów**:
  - `400 Bad Request`: Błędy walidacji.
  - `401 Unauthorized`: Brak lub nieprawidłowy token JWT.
  - `409 Conflict`: Podkategoria o tej nazwie już istnieje dla użytkownika.
  - `500 Internal Server Error`: Błędy serwera.

## 5. Przepływ danych
1.  Żądanie `POST` trafia do API Platform.
2.  API Platform deserializuje `request body` do instancji `App\DTO\SubcategoryInput`.
3.  Uruchamiany jest `Symfony Validator`, który weryfikuje dane w DTO na podstawie zdefiniowanych atrybutów.
4.  Jeśli walidacja przejdzie pomyślnie, API Platform przekazuje DTO do dedykowanego procesora stanu: `App\State\SubcategoryProcessor`.
5.  Procesor pobiera aktualnie zalogowanego użytkownika z serwisu `Security`.
6.  Procesor sprawdza w `SubcategoryRepository`, czy podkategoria o podanej nazwie już istnieje dla tego użytkownika. Jeśli tak, rzuca wyjątek `SubcategoryAlreadyExistsException`.
7.  Jeśli nie, procesor tworzy nową instancję encji `App\Entity\Subcategory`, wypełniając ją danymi z DTO i obiektem użytkownika.
8.  Nowa encja jest zapisywana w bazie danych za pomocą `EntityManager`.
9.  Procesor zwraca nowo utworzoną encję `Subcategory`.
10. API Platform serializuje encję (lub zmapowane DTO `SubcategoryOutput`) do formatu JSON i wysyła odpowiedź `201 Created` do klienta.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Punkt końcowy musi być chroniony i wymagać ważnego tokena JWT. Zabezpieczenie zostanie zaimplementowane na poziomie operacji API Platform za pomocą atrybutu `security: "is_granted('ROLE_USER')"`.
- **Autoryzacja**: Logika biznesowa musi zapewnić, że użytkownik tworzy podkategorię wyłącznie dla siebie. `SubcategoryProcessor` będzie odpowiedzialny za pobranie obiektu `User` z kontekstu bezpieczeństwa i przypisanie go do nowej encji. `SubcategoryInput` DTO nie będzie zawierać pola `userId`, aby zapobiec próbom manipulacji.
- **Walidacja danych**: Wszystkie dane wejściowe będą walidowane za pomocą `Symfony Validator` na poziomie DTO, aby zapobiec nieprawidłowym lub złośliwym danym (np. SQL Injection, XSS).

## 7. Obsługa błędów
- **Błędy walidacji (400)**: Automatycznie obsługiwane przez API Platform, które zwraca szczegółową odpowiedź z listą naruszeń.
- **Konflikt danych (409)**: `SubcategoryProcessor` rzuci niestandardowym wyjątkiem `App\Exception\SubcategoryAlreadyExistsException`, jeśli podkategoria już istnieje. Dedykowany `ExceptionListener` przechwyci ten wyjątek i zwróci odpowiedź z kodem `409 Conflict`.
- **Brak uwierzytelnienia (401)**: Automatycznie obsługiwane przez `LexikJwtAuthenticationBundle`.
- **Błędy serwera (500)**: Wszystkie nieoczekiwane wyjątki będą logowane przez Monolog i zwrócą generyczną odpowiedź `500 Internal Server Error`.

## 8. Rozważania dotyczące wydajności
- Operacja jest prostym zapisem do bazy danych i nie powinna generować problemów z wydajnością.
- Zapytanie sprawdzające unikalność nazwy podkategorii będzie wykonywane na zindeksowanej kolumnie (`user_id`, `name`), co zapewni jego szybkość.

## 9. Etapy wdrożenia
1.  **DTO**:
    -   Utworzyć `App\DTO\SubcategoryInput.php` z właściwościami `name`, `type`, `mainCategory`.
    -   Dodać atrybuty walidacyjne (`NotBlank`, `Length`, `Enum`) do właściwości DTO.
2.  **Wyjątek**:
    -   Utworzyć niestandardową klasę wyjątku `App\Exception\SubcategoryAlreadyExistsException.php`.
3.  **Procesor Stanu**:
    -   Utworzyć serwis `App\State\SubcategoryProcessor.php` implementujący `ApiPlatform\State\ProcessorInterface`.
    -   Wstrzyknąć zależności: `EntityManagerInterface`, `SubcategoryRepository`, `Security`.
    -   Zaimplementować logikę sprawdzania unikalności i tworzenia nowej encji `Subcategory`.
4.  **Konfiguracja API Resource**:
    -   W encji `App\Entity\Subcategory.php` dodać nową operację `Post` do atrybutu `#[ApiResource]`.
    -   Skonfigurować operację, aby używała `SubcategoryInput` jako `input` DTO, `SubcategoryOutput` jako `output` DTO, oraz wskazywała na `SubcategoryProcessor`.
    -   Dodać zabezpieczenie `security: "is_granted('ROLE_USER')"`.
5.  **Listener Wyjątków**:
    -   Utworzyć `App\EventSubscriber\SubcategoryAlreadyExistsExceptionSubscriber.php`, który nasłuchuje na zdarzenie `kernel.exception`.
    -   W subscriberze sprawdzić, czy wyjątek jest instancją `SubcategoryAlreadyExistsException` i jeśli tak, utworzyć odpowiedź `JsonResponse` z kodem `409`.
6.  **Testy**:
    -   Napisać testy API w `tests/Api/SubcategoryApiTest.php`, które pokryją następujące scenariusze:
        -   Pomyślne utworzenie podkategorii (oczekiwany kod `201`).
        -   Próba utworzenia podkategorii przez niezalogowanego użytkownika (oczekiwany kod `401`).
        -   Próba utworzenia podkategorii z nieprawidłowymi danymi (oczekiwany kod `400`).
        -   Próba utworzenia podkategorii o nazwie, która już istnieje (oczekiwany kod `409`).
