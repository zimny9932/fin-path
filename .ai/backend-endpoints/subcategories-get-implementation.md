# API Endpoint Implementation Plan: GET /api/subcategories

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia uwierzytelnionym użytkownikom pobieranie listy swoich podkategorii. Zapewnia opcjonalne filtrowanie według typu transakcji (`income` lub `expense`), aby umożliwić klientom bardziej szczegółowe zapytania.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/subcategories`
- **Parametry**:
  - **Opcjonalne**:
    - `type` (string): Filtruje podkategorie według typu. Dozwolone wartości: `income`, `expense`.

## 3. Wykorzystywane typy
- **`SubcategoryOutput` (DTO)**: Będzie używany do strukturyzowania danych wyjściowych.
  ```php
  namespace App\DTO;

  final readonly class SubcategoryOutput
  {
      public function __construct(
          public string $id,
          public string $name,
          public string $type,
          public string $mainCategory
      ) {}
  }
  ```

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (`200 OK`)**: Zwraca tablicę obiektów podkategorii.
  ```json
  [
    {
      "id": "uuid-v7-string",
      "name": "Salary",
      "type": "income",
      "mainCategory": "INCOME"
    },
    {
      "id": "uuid-v7-string",
      "name": "Groceries",
      "type": "expense",
      "mainCategory": "FOOD"
    }
  ]
  ```
- **Odpowiedzi błędów**:
  - `401 Unauthorized`: Gdy użytkownik nie jest zalogowany.
  - `400 Bad Request`: Gdy parametr `type` ma nieprawidłową wartość.

## 5. Przepływ danych
1. Użytkownik wysyła żądanie `GET` na `/api/subcategories` z ważnym tokenem JWT.
2. API Platform przechwytuje żądanie i identyfikuje zasób `Subcategory`.
3. Uruchamiany jest dedykowany `State Provider` dla kolekcji zasobu `Subcategory`.
4. Provider pobiera aktualnie zalogowanego użytkownika z `Security`.
5. Provider wywołuje metodę w `SubcategoryRepository`, aby pobrać podkategorie należące *wyłącznie* do tego użytkownika.
6. API Platform `SearchFilter` automatycznie dodaje warunek `WHERE type = :type` do zapytania Doctrine, jeśli parametr `type` jest obecny w URL.
7. Wyniki z repozytorium są mapowane na DTO `SubcategoryOutput`.
8. Zserializowana odpowiedź JSON jest zwracana do klienta.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego będzie chroniony za pomocą JWT. Każde żądanie musi zawierać prawidłowy nagłówek `Authorization: Bearer <token>`.
- **Autoryzacja**: Zasób zostanie zabezpieczony za pomocą `security: "is_granted('ROLE_USER')"`. Logika w `State Provider` zapewni, że użytkownicy będą mogli pobierać tylko własne dane, zapobiegając wyciekowi danych między kontami.
- **Walidacja danych wejściowych**: Wartość parametru `type` będzie niejawnie walidowana przez `SearchFilter` (strategia `exact`), który dopuści tylko zdefiniowane wartości.

## 7. Rozważania dotyczące wydajności
- **Indeksowanie**: Należy upewnić się, że kolumny `user_id` i `type` w tabeli `subcategories` są zindeksowane, aby zapewnić szybkie wyszukiwanie i filtrowanie.
- **Paginacja**: API Platform domyślnie włącza paginację, co zapobiega problemom z wydajnością w przypadku dużej liczby podkategorii. Domyślny rozmiar strony (30) jest odpowiedni.

## 8. Etapy wdrożenia
1. **Utworzenie encji `Subcategory`**:
   - Zdefiniuj klasę `App\Entity\Subcategory` z mapowaniem Doctrine ORM zgodnie ze specyfikacją tabeli `subcategories`.
   - Dodaj relację `ManyToOne` do encji `User`.
   - Użyj `#[UniqueConstraint]` na poziomie tabeli, aby zapewnić unikalność kombinacji `user_id` i `name`.
2. **Utworzenie `SubcategoryRepository`**:
   - Wygeneruj repozytorium dla encji `Subcategory`. Będzie ono zawierało logikę pobierania danych.
3. **Utworzenie DTO `SubcategoryOutput`**:
   - Zdefiniuj klasę `App\DTO\SubcategoryOutput` jako `readonly` z polami `id`, `name`, `type` i `mainCategory`.
4. **Konfiguracja zasobu API Platform**:
   - W encji `Subcategory` dodaj atrybut `#[ApiResource]`.
   - Zdefiniuj operację `GetCollection` z następującymi właściwościami:
     - `output: SubcategoryOutput::class`
     - `security: "is_granted('ROLE_USER')"`
     - `provider: App\State\SubcategoryCollectionProvider::class` (nazwa do ustalenia)
5. **Implementacja `State Provider`**:
   - Utwórz `App\State\SubcategoryCollectionProvider`, który implementuje `ProviderInterface`.
   - W metodzie `provide` wstrzyknij `Security` i `SubcategoryRepository`.
   - Pobierz zalogowanego użytkownika. Jeśli go nie ma, zwróć `null`.
   - Wywołaj metodę repozytorium, aby pobrać podkategorie dla danego użytkownika.
6. **Dodanie filtra wyszukiwania**:
   - W atrybucie `#[ApiResource]` w encji `Subcategory` dodaj `#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact'])]`.
7. **Napisanie testów API**:
   - Utwórz `tests/Api/SubcategoryApiTest.php`, który rozszerza `ApiTestCase`.
   - Zaimplementuj testy sprawdzające:
     - Pomyślne pobranie listy podkategorii dla uwierzytelnionego użytkownika.
     - Poprawne filtrowanie po `type=income` i `type=expense`.
     - Otrzymanie błędu `401 Unauthorized` dla niezalogowanego użytkownika.
     - Upewnienie się, że użytkownik A nie widzi podkategorii użytkownika B.
