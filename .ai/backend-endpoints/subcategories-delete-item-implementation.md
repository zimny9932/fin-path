# API Endpoint Implementation Plan: DELETE /api/subcategories/{id}

## 1. Przegląd punktu końcowego
Ten dokument opisuje plan wdrożenia punktu końcowego `DELETE /api/subcategories/{id}`. Punkt końcowy umożliwia uwierzytelnionym użytkownikom usuwanie własnych podkategorii. Usunięcie jest możliwe tylko wtedy, gdy podkategoria nie jest powiązana z żadnymi istniejącymi transakcjami, co zapewnia integralność danych.

## 2. Szczegóły żądania
- **Metoda HTTP**: `DELETE`
- **Struktura URL**: `/api/subcategories/{id}`
- **Parametry**:
  - **Wymagane**:
    - `id` (parametr ścieżki): Unikalny identyfikator (UUID) podkategorii do usunięcia.
  - **Opcjonalne**: Brak.
- **Request Body**: Brak.

## 3. Wykorzystywane typy
Operacja usunięcia nie wymaga definicji żadnych dodatkowych struktur DTO (Data Transfer Object) ani modeli Command, ponieważ nie jest przesyłane ciało żądania.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu**:
  - **Kod stanu**: `204 No Content`
  - **Ciało odpowiedzi**: Brak.
- **Odpowiedzi błędów**:
  - **Kod stanu**: `401 Unauthorized` (problem `application/problem+json`) - Użytkownik nie jest uwierzytelniony.
  - **Kod stanu**: `403 Forbidden` (problem `application/problem+json`) - Użytkownik nie ma uprawnień do usunięcia tego zasobu (nie jest jego właścicielem).
  - **Kod stanu**: `404 Not Found` (problem `application/problem+json`) - Zasób o podanym `id` nie został znaleziony.
  - **Kod stanu**: `409 Conflict` (problem `application/problem+json`) - Nie można usunąć podkategorii, ponieważ jest powiązana z transakcjami.

## 5. Przepływ danych
1.  Użytkownik wysyła żądanie `DELETE` na adres `/api/subcategories/{id}` z prawidłowym tokenem JWT.
2.  Router Symfony przekazuje żądanie do API Platform.
3.  Warstwa bezpieczeństwa Symfony weryfikuje token JWT. Jeśli jest nieprawidłowy, zwraca `401 Unauthorized`.
4.  API Platform identyfikuje zasób `Subcategory` i operację `Delete`.
5.  Dostawca danych (Doctrine) próbuje pobrać encję `Subcategory` o podanym `id`. Jeśli encja nie istnieje, zwraca `404 Not Found`.
6.  Mechanizm bezpieczeństwa API Platform weryfikuje uprawnienia za pomocą reguły `security: "is_granted('ROLE_USER') and object.getUser() == user"`. Jeśli walidacja się nie powiedzie, zwraca `403 Forbidden`.
7.  Jeśli uprawnienia są poprawne, API Platform przekazuje sterowanie do procesora stanu `App\State\SubcategoryProcessor`.
8.  `SubcategoryProcessor` wstrzykuje `TransactionRepository` i sprawdza, czy istnieją jakiekolwiek transakcje powiązane z daną podkategorią.
9.  **Ścieżka błędu**: Jeśli znaleziono powiązane transakcje, procesor rzuca niestandardowy wyjątek `App\Exception\SubcategoryInUseException`.
10. Dedykowany `EventSubscriber` (`App\EventSubscriber\SubcategoryInUseExceptionSubscriber`) przechwytuje ten wyjątek i generuje odpowiedź `409 Conflict` z odpowiednim komunikatem.
11. **Ścieżka sukcesu**: Jeśli nie ma powiązanych transakcji, `SubcategoryProcessor` przekazuje operację usunięcia do domyślnego procesora Doctrine (`api_platform.doctrine.orm.state.remove_processor`), który usuwa encję z bazy danych.
12. API Platform zwraca odpowiedź `204 No Content`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego jest chroniony i wymaga prawidłowego tokena JWT.
- **Autoryzacja**: Zastosowana zostanie reguła na poziomie zasobu (`security: "object.getUser() == user"`), aby upewnić się, że użytkownicy mogą usuwać tylko własne podkategorie. Zapobiega to atakom typu IDOR (Insecure Direct Object Reference).

## 7. Obsługa błędów
- **`SubcategoryInUseException`**: Należy stworzyć niestandardowy wyjątek, który będzie rzucany, gdy próbuje się usunąć podkategorię powiązaną z transakcjami.
- **`SubcategoryInUseExceptionSubscriber`**: Należy zaimplementować subskrybenta zdarzeń, który nasłuchuje na `kernel.exception`, przechwytuje `SubcategoryInUseException` i zwraca odpowiedź `409 Conflict` w formacie `application/problem+json`.

## 8. Rozważania dotyczące wydajności
Sprawdzenie istnienia powiązanych transakcji powinno być zoptymalizowane. Zamiast pobierać całą kolekcję transakcji, należy wykonać zapytanie `COUNT` lub `EXISTS`, które jest znacznie szybsze i zużywa mniej zasobów. Zapytanie to będzie indeksowane przez klucz obcy, co zapewni wysoką wydajność.

## 9. Etapy wdrożenia
1.  **Aktualizacja encji `Subcategory`**:
    - W pliku `src/Entity/Subcategory.php`, w atrybucie `#[ApiResource]`, dodaj nową operację `Delete`.
    - Skonfiguruj operację, podając `security` oraz wskazując na `SubcategoryProcessor`.
    ```php
    new Delete(
        security: "is_granted('ROLE_USER') and object.getUser() == user",
        processor: SubcategoryProcessor::class
    ),
    ```
2.  **Utworzenie niestandardowego wyjątku**:
    - Stwórz nowy plik `src/Exception/SubcategoryInUseException.php`.
    - Zdefiniuj klasę `SubcategoryInUseException` rozszerzającą `\RuntimeException`.
3.  **Modyfikacja `SubcategoryProcessor`**:
    - W pliku `src/State/SubcategoryProcessor.php` wstrzyknij w konstruktorze `TransactionRepository` oraz domyślny procesor do usuwania (`ProcessorInterface $removeProcessor`).
    - W metodzie `process()`, dodaj logikę obsługującą operację usuwania.
    - Sprawdź, czy operacja jest typu `Delete`.
    - Użyj `TransactionRepository`, aby sprawdzić, czy istnieją transakcje dla danej podkategorii.
    - Jeśli tak, rzuć `SubcategoryInUseException`.
    - Jeśli nie, wywołaj metodę `process()` na wstrzykniętym `$removeProcessor`, aby usunąć encję.
4.  **Implementacja `EventSubscriber`**:
    - Stwórz nowy plik `src/EventSubscriber/SubcategoryInUseExceptionSubscriber.php`.
    - Zaimplementuj `EventSubscriberInterface` i subskrybuj zdarzenie `KernelEvents::EXCEPTION`.
    - W metodzie obsługującej zdarzenie sprawdź, czy wyjątek jest instancją `SubcategoryInUseException`.
    - Jeśli tak, utwórz `JsonResponse` z kodem stanu `409` i odpowiednią treścią błędu, a następnie ustaw go w obiekcie zdarzenia.
5.  **Testy API**:
    - W pliku `tests/Api/SubcategoryApiTest.php` dodaj nowe testy weryfikujące:
      - Pomyślne usunięcie podkategorii (oczekiwany status `204`).
      - Próbę usunięcia podkategorii powiązanej z transakcją (oczekiwany status `409`).
      - Próbę usunięcia podkategorii przez nieuprawnionego użytkownika (oczekiwany status `403`).
      - Próbę usunięcia nieistniejącej podkategorii (oczekiwany status `404`).
      - Próbę usunięcia podkategorii przez nieuwierzytelnionego użytkownika (oczekiwany status `401`).
