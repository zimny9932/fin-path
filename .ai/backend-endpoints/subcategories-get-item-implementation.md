# API Endpoint Implementation Plan: GET /api/subcategories/{id}

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia pobranie pojedynczego zasobu podkategorii na podstawie jej unikalnego identyfikatora (UUID). Endpoint zapewnia, że uwierzytelniony użytkownik może uzyskać dostęp wyłącznie do podkategorii, których jest właścicielem, co gwarantuje izolację danych.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/subcategories/{id}`
- **Parametry**:
  - **Wymagane**:
    - `id` (parametr ścieżki): Unikalny identyfikator (UUID) podkategorii do pobrania.
  - **Opcjonalne**: Brak.
- **Request Body**: Brak.

## 3. Wykorzystywane typy
- **DTO Wyjściowe**: `App\DTO\SubcategoryOutput`
  - Zostanie użyte do serializacji odpowiedzi, aby zapewnić spójny i kontrolowany format danych zwracanych przez API. Struktura DTO będzie odzwierciedlać kluczowe pola encji `Subcategory`.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu**:
  - **Kod stanu**: `200 OK`
  - **Struktura odpowiedzi**:
    ```json
    {
      "id": "018f3a35-5368-7918-868c-7353a33d752c",
      "name": "Groceries",
      "type": "expense",
      "mainCategory": "Food"
    }
    ```
- **Odpowiedzi błędów**:
  - **Kod stanu**: `401 Unauthorized` - Gdy żądanie jest wykonywane bez ważnego tokenu uwierzytelnienia JWT.
  - **Kod stanu**: `404 Not Found` - Gdy podkategoria o podanym `id` nie istnieje lub nie należy do uwierzytelnionego użytkownika.

## 5. Przepływ danych
1. Użytkownik wysyła żądanie `GET` na adres `/api/subcategories/{id}` z prawidłowym tokenem JWT w nagłówku.
2. Komponent `Security` w Symfony weryfikuje token JWT i uwierzytelnia użytkownika.
3. API Platform identyfikuje zasób `Subcategory` i operację `Get`.
4. Wywoływany jest domyślny `State Provider` Doctrine w API Platform w celu pobrania encji `Subcategory`.
5. Istniejące rozszerzenie Doctrine `CurrentUserExtension` przechwytuje zapytanie i automatycznie dodaje warunek `WHERE user_id = :current_user_id`, filtrując wyniki tylko do zasobów należących do zalogowanego użytkownika.
6. **Scenariusz sukcesu**: Jeśli encja o podanym `id` i należąca do użytkownika zostanie znaleziona, jest ona mapowana na DTO `SubcategoryOutput`, serializowana do formatu JSON i zwracana w odpowiedzi z kodem `200 OK`.
7. **Scenariusz błędu**: Jeśli encja nie zostanie znaleziona (z powodu nieprawidłowego `id` lub przynależności do innego użytkownika), API Platform zwraca odpowiedź z kodem `404 Not Found`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego musi być ograniczony tylko do uwierzytelnionych użytkowników. Zostanie to zrealizowane poprzez zabezpieczenie operacji za pomocą atrybutu `security: "is_granted('ROLE_USER')"`.
- **Autoryzacja**: Ochrona przed atakami typu IDOR (Insecure Direct Object Reference) jest zapewniona przez `CurrentUserExtension`. To rozszerzenie gwarantuje, że użytkownicy nie mogą uzyskać dostępu do zasobów innych użytkowników, nawet jeśli znają ich identyfikatory.
- **Walidacja danych wejściowych**: Parametr `id` jest automatycznie walidowany przez framework pod kątem formatu UUID. Próba użycia nieprawidłowego formatu spowoduje zwrócenie błędu `404 Not Found`.

## 7. Rozważania dotyczące wydajności
- Operacja opiera się na wyszukiwaniu po kluczu głównym (`id`) oraz kluczu obcym (`user_id`), które są indeksowane w bazie danych. W związku z tym zapytanie będzie bardzo wydajne.
- Nie przewiduje się problemów z wydajnością dla tego punktu końcowego.

## 8. Etapy wdrożenia
1. **Modyfikacja Encji `Subcategory`**:
   - Zlokalizuj plik `src/Entity/Subcategory.php`.
   - W atrybucie `#[ApiResource]` dodaj nową operację `Get`.
   - Skonfiguruj operację `Get`, podając `security: "is_granted('ROLE_USER')"` oraz `output: SubcategoryOutput::class`.
   ```php
   // src/Entity/Subcategory.php

   #[ApiResource(
       operations: [
           new Get(
               security: "is_granted('ROLE_USER')",
               output: SubcategoryOutput::class
           ),
           // ...istniejące operacje
       ],
       // ...
   )]
   ```
2. **Weryfikacja `CurrentUserExtension`**:
   - Upewnij się, że `CurrentUserExtension` jest poprawnie skonfigurowany i działa dla encji `Subcategory`. Nie powinno być wymaganych żadnych zmian, jeśli encja posiada pole `user` z relacją do encji `User`.

3. **Stworzenie Testów API**:
   - Utwórz nowy plik testowy `tests/Api/SubcategoryGetApiTest.php`, rozszerzający `ApiTestCase`.
   - Zaimplementuj następujące scenariusze testowe:
     - `test_get_own_subcategory_returns_200_ok()`: Testuje pomyślne pobranie własnej podkategorii.
     - `test_get_another_user_subcategory_returns_404_not_found()`: Testuje próbę pobrania podkategorii innego użytkownika.
     - `test_get_non_existent_subcategory_returns_404_not_found()`: Testuje próbę pobrania nieistniejącej podkategorii.
     - `test_get_subcategory_without_authentication_returns_401_unauthorized()`: Testuje próbę dostępu bez uwierzytelnienia.
     - `test_get_subcategory_with_invalid_uuid_returns_404_not_found()`: Testuje próbę użycia nieprawidłowego formatu UUID.

4. **Uruchomienie Testów**:
   - Uruchom nowo utworzone testy, aby zweryfikować poprawność implementacji.
   ```bash
   docker-compose exec -T php bin/phpunit tests/Api/SubcategoryGetApiTest.php
   ```
