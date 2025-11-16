# API Endpoint Implementation Plan: GET /api/subcategories/recent

## 1. Przegląd punktu końcowego
Ten punkt końcowy dostarcza listę ostatnio używanych podkategorii dla uwierzytelnionego użytkownika. "Ostatnio używane" jest definiowane na podstawie daty najnowszych transakcji powiązanych z daną podkategorią. Funkcjonalność ta ma na celu przyspieszenie procesu dodawania nowych transakcji poprzez sugerowanie najczęstszych wyborów.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/subcategories/recent`
- **Parametry**:
  - **Opcjonalne**:
    - `limit` (integer): Maksymalna liczba podkategorii do zwrócenia. Domyślna wartość to `5`.

## 3. Wykorzystywane typy
Nie ma potrzeby tworzenia nowych typów DTO. Odpowiedź będzie kolekcją encji `App\Entity\Subcategory`, które zostaną zserializowane do formatu JSON, prawdopodobnie przy użyciu istniejącego `App\Serializer\SubcategoryNormalizer`, aby zachować spójność z innymi punktami końcowymi zwracającymi podkategorie.

## 4. Szczegóły odpowiedzi
- **Sukces (200 OK)**:
  - **Content-Type**: `application/ld+json; charset=utf-8`
  - **Body**: Tablica obiektów JSON reprezentujących podkategorie, posortowana od ostatnio używanej.
  ```json
  [
    {
      "id": "0192e8a7-3373-7c85-a477-c1d5b6379c37",
      "name": "Zakupy spożywcze",
      "type": "expense",
      "mainCategory": "food"
    },
    {
      "id": "0192e8a7-33a9-7a54-b49b-73a763321595",
      "name": "Paliwo",
      "type": "expense",
      "mainCategory": "transport"
    }
  ]
  ```

## 5. Przepływ danych
1. Użytkownik wysyła żądanie `GET` na adres `/api/subcategories/recent`.
2. API Platform identyfikuje operację i wywołuje dedykowany `State Provider` (`App\State\RecentSubcategoriesProvider`).
3. `RecentSubcategoriesProvider` pobiera aktualnie zalogowanego użytkownika z serwisu `Security`.
4. Provider odczytuje parametr `limit` z kontekstu żądania, stosując wartość domyślną `5`, jeśli nie został podany.
5. Provider wykonuje zapytanie DQL do bazy danych, które:
   a. Wybiera encje `Subcategory`.
   b. Łączy je z encjami `Transaction`.
   c. Filtruje transakcje należące wyłącznie do zalogowanego użytkownika.
   d. Grupuje wyniki po podkategorii.
   e. Sortuje zgrupowane podkategorie malejąco według maksymalnej daty transakcji (`MAX(transaction.createdAt)`).
   f. Ogranicza liczbę wyników do podanego `limit`.
6. Provider zwraca kolekcję encji `Subcategory`.
7. API Platform serializuje wynik do formatu JSON i wysyła odpowiedź `200 OK` do klienta.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego musi być chroniony i wymagać od użytkownika bycia zalogowanym. Zostanie to zrealizowane przez dodanie `security: "is_granted('ROLE_USER')"` do definicji operacji w `#[ApiResource]`.
- **Autoryzacja i izolacja danych**: `RecentSubcategoriesProvider` musi rygorystycznie filtrować dane na podstawie `user_id` zalogowanego użytkownika, aby zapobiec dostępowi do danych innych użytkowników.

## 7. Obsługa błędów
- **400 Bad Request**: Zwracany, gdy parametr `limit` jest nieprawidłowy (np. jest to ciąg znaków, liczba ujemna lub zero). Walidacja będzie przeprowadzana wewnątrz providera.
- **401 Unauthorized**: Zwracany, gdy żądanie jest wykonywane przez nieuwierzytelnionego użytkownika (standardowa obsługa przez `lexik/jwt-authentication-bundle`).

## 8. Rozważania dotyczące wydajności
- Zapytanie do bazy danych może być zasobożerne, zwłaszcza przy dużej liczbie transakcji. Aby zoptymalizować jego działanie, należy upewnić się, że na tabeli `transactions` istnieje złożony indeks na kolumnach `(user_id, created_at)`.

## 9. Etapy wdrożenia
1.  **Modyfikacja Encji `Subcategory`**:
    -   Dodać nową operację `GetCollection` do atrybutu `#[ApiResource]` dla ścieżki `/subcategories/recent`.
    -   Skonfigurować operację, aby używała niestandardowego providera `App\State\RecentSubcategoriesProvider`.
    -   Zabezpieczyć operację za pomocą `security: "is_granted('ROLE_USER')"`.
2.  **Utworzenie `State Providera`**:
    -   Stworzyć nową klasę `App\State\RecentSubcategoriesProvider`, która implementuje `ApiPlatform\State\ProviderInterface`.
    -   Wstrzyknąć do konstruktora zależności: `Doctrine\ORM\EntityManagerInterface` oraz `Symfony\Component\Security\Core\Security`.
    -   Zaimplementować metodę `provide()`.
3.  **Implementacja logiki w `provide()`**:
    -   Pobrać aktualnego użytkownika. Jeśli brak, zwrócić `null`.
    -   Pobrać i zwalidować parametr `limit` z kontekstu żądania. W przypadku błędu rzucić `BadRequestHttpException`.
    -   Zbudować i wykonać zapytanie QueryBuilder, które pobierze ostatnio używane podkategorie zgodnie z opisanym przepływem danych.
    -   Zwrócić wynik zapytania.
4.  **Dodanie Testów API**:
    -   Utworzyć nową klasę testową `tests/Api/RecentSubcategoriesApiTest.php`, rozszerzającą `ApiTestCase`.
    -   Napisać testy sprawdzające:
        -   Odmowę dostępu dla nieuwierzytelnionych użytkowników (401).
        -   Poprawne zwracanie podkategorii z domyślnym `limit`.
        -   Poprawne działanie niestandardowego parametru `limit`.
        -   Prawidłową kolejność zwracanych podkategorii (od najnowszej transakcji).
        -   Zwrócenie pustej listy dla użytkownika bez transakcji.
        -   Błąd `400 Bad Request` dla nieprawidłowej wartości `limit`.
5.  **Weryfikacja Indeksów Bazy Danych**:
    -   Sprawdzić, czy na tabeli `transactions` istnieje odpowiedni indeks (`user_id`, `created_at`) w celu zapewnienia optymalnej wydajności. W razie potrzeby dodać nową migrację Doctrine.
