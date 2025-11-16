# API Endpoint Implementation Plan: PUT /api/subcategories/{id}

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia uwierzytelnionym użytkownikom aktualizację istniejącej podkategorii. Użytkownik może modyfikować nazwę, typ oraz główną kategorię podkategorii, pod warunkiem, że jest jej właścicielem. System zapewnia unikalność nazw podkategorii w obrębie konta jednego użytkownika.

## 2. Szczegóły żądania
- **Metoda HTTP**: `PUT`
- **Struktura URL**: `/api/subcategories/{id}`
- **Parametry**:
  - **Wymagane**:
    - `id` (w ścieżce): `UUID` - unikalny identyfikator podkategorii.
  - **Opcjonalne**: Brak
- **Request Body**: Ciało żądania musi zawierać kompletną reprezentację zasobu z nowymi danymi.
  ```json
  {
    "name": "Artykuły spożywcze",
    "type": "expense",
    "mainCategory": "FOOD"
  }
  ```

## 3. Wykorzystywane typy
- **`App\DTO\SubcategoryInput`**: DTO (Data Transfer Object) używany do walidacji danych wejściowych z żądania. Zapewnia, że wszystkie wymagane pola (`name`, `type`, `mainCategory`) są obecne i zgodne z oczekiwanym formatem.
- **`App\DTO\SubcategoryOutput`**: DTO używany do sformatowania odpowiedzi po pomyślnej aktualizacji zasobu.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu**:
  - **Kod stanu**: `200 OK`
  - **Treść odpowiedzi**: Zaktualizowany obiekt podkategorii w formacie JSON, zgodny z `SubcategoryOutput`.
    ```json
    {
      "id": "0192d326-b18a-7840-8353-28f0b3e56d1e",
      "name": "Artykuły spożywcze",
      "type": "expense",
      "mainCategory": "FOOD"
    }
    ```
- **Odpowiedzi błędów**:
  - `400 Bad Request`: Błędy walidacji (np. brakujące pole, nieprawidłowy typ).
  - `401 Unauthorized`: Użytkownik nie jest uwierzytelniony.
  - `403 Forbidden`: Użytkownik nie ma uprawnień do modyfikacji tego zasobu (nie jest jego właścicielem).
  - `404 Not Found`: Podkategoria o podanym `id` nie została znaleziona.
  - `409 Conflict`: Próba zmiany nazwy na taką, która już istnieje dla tego użytkownika.

## 5. Przepływ danych
1.  Żądanie `PUT` trafia do API Platform z `id` podkategorii w URL.
2.  System bezpieczeństwa Symfony weryfikuje token JWT. Jeśli jest nieprawidłowy, zwraca `401 Unauthorized`.
3.  API Platform identyfikuje zasób `Subcategory` i operację `Put`.
4.  `ReadListener` (wraz z `CurrentUserExtension`) próbuje pobrać encję `Subcategory` z bazy danych, automatycznie dodając warunek `WHERE user_id = :current_user_id`. Jeśli encja nie zostanie znaleziona, zwraca `404 Not Found`.
5.  `SecurityListener` sprawdza uprawnienia zdefiniowane w atrybucie `security: "is_granted('ROLE_USER') and object.getUser() == user"`. Jeśli warunek nie jest spełniony, zwraca `403 Forbidden`.
6.  `DeserializeListener` odczytuje ciało żądania i wypełnia DTO `SubcategoryInput`.
7.  `ValidateListener` uruchamia walidację na DTO `SubcategoryInput`. W przypadku błędów zwraca `400 Bad Request` ze szczegółami.
8.  Wywoływany jest `SubcategoryProcessor`, który otrzymuje załadowaną encję `Subcategory` oraz dane z DTO.
9.  W procesorze:
    a. Sprawdzana jest unikalność nowej nazwy. Jeśli inna podkategoria tego użytkownika ma już taką nazwę, rzucany jest wyjątek `SubcategoryAlreadyExistsException`.
    b. Właściwości encji `Subcategory` (`name`, `type`, `mainCategory`) są aktualizowane na podstawie danych z DTO.
10. `SubcategoryAlreadyExistsExceptionSubscriber` przechwytuje wyjątek i generuje odpowiedź `409 Conflict`.
11. `WriteListener` zapisuje zmiany w encji do bazy danych za pomocą `EntityManager`.
12. `SerializeListener` konwertuje zaktualizowaną encję `Subcategory` na format JSON przy użyciu `SubcategoryOutput`.
13. Odpowiedź `200 OK` jest wysyłana do klienta.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Wszystkie żądania do tego punktu końcowego muszą zawierać prawidłowy token JWT w nagłówku `Authorization`.
- **Autoryzacja**: Dostęp jest ograniczony do właściciela zasobu. Jest to realizowane przez warunek `security: "object.getUser() == user"` w definicji `ApiResource`, który gwarantuje, że tylko użytkownik będący właścicielem podkategorii może ją modyfikować.
- **Walidacja danych**: Dane wejściowe są rygorystycznie walidowane za pomocą DTO `SubcategoryInput` i adnotacji `Symfony Validator`, co zapobiega wprowadzeniu niepoprawnych danych.

## 7. Rozważania dotyczące wydajności
- Operacja jest atomowa i obejmuje jedno zapytanie `SELECT` w celu znalezienia obiektu oraz jedno zapytanie `UPDATE`.
- Dodatkowe zapytanie sprawdzające unikalność nazwy jest wykonywane tylko wtedy, gdy nazwa ulega zmianie. Zapytanie to będzie szybkie, ponieważ kolumny (`user_id`, `name`) są objęte unikalnym indeksem.
- Ogólny wpływ na wydajność jest minimalny.

## 8. Etapy wdrożenia
1.  **Aktualizacja encji `Subcategory`**:
    - W pliku `src/Entity/Subcategory.php`, w atrybucie `#[ApiResource]`, dodać nową operację `Put`.
    - Skonfigurować operację `Put`, określając `security`, `input`, `output` oraz `processor`, analogicznie do istniejącej operacji `Post`.
    ```php
    new Put(
        security: "is_granted('ROLE_USER') and object.getUser() == user",
        input: SubcategoryInput::class,
        output: SubcategoryOutput::class,
        processor: SubcategoryProcessor::class
    )
    ```

2.  **Modyfikacja `SubcategoryProcessor`**:
    - W metodzie `process()` w `src/State/SubcategoryProcessor.php` dodać logikę rozróżniającą operację `POST` od `PUT`. Można to zrobić, sprawdzając, czy operacja jest typu `UpdateOperationInterface`.
    - Zaimplementować logikę aktualizacji:
      - Sprawdzić, czy nazwa w DTO różni się od nazwy w encji.
      - Jeśli tak, wykonać zapytanie do `SubcategoryRepository` w celu sprawdzenia, czy nowa nazwa już istnieje dla bieżącego użytkownika. Jeśli tak, rzucić `SubcategoryAlreadyExistsException`.
      - Zaktualizować pola `name`, `type` i `mainCategory` w encji na podstawie danych z DTO.

3.  **Implementacja testów API**:
    - W pliku `tests/Api/SubcategoryApiTest.php` dodać nowe metody testowe dla operacji `PUT`.
    - **`testUpdateSubcategory()`**: Sprawdza pomyślną aktualizację podkategorii (status `200 OK`) i poprawność zwróconych danych.
    - **`testUpdateSubcategoryWithExistingName()`**: Weryfikuje, że próba ustawienia istniejącej nazwy zwraca status `409 Conflict`.
    - **`testUpdateSubcategoryOfAnotherUser()`**: Potwierdza, że próba modyfikacji cudzej podkategorii zwraca `403 Forbidden` lub `404 Not Found`.
    - **`testUpdateSubcategoryWithInvalidData()`**: Sprawdza, czy wysłanie nieprawidłowych danych (np. pustej nazwy) zwraca status `400 Bad Request`.
    - **`testUpdateNonExistentSubcategory()`**: Weryfikuje, że żądanie dla nieistniejącego `id` zwraca `404 Not Found`.
    - **`testUpdateSubcategoryUnauthenticated()`**: Sprawdza, czy niezalogowany użytkownik otrzymuje status `401 Unauthorized`.
