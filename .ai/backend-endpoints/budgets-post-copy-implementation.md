# API Endpoint Implementation Plan: POST /api/budgets/{year}/{month}/copy

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest umożliwienie użytkownikom tworzenia nowego miesięcznego budżetu poprzez skopiowanie planowanych dochodów i limitów wydatków z istniejącego budżetu. Upraszcza to proces konfiguracji budżetu na kolejne miesiące, zachowując spójność planowania finansowego.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/budgets/{year}/{month}/copy`
- **Parametry ścieżki**:
  - `year` (wymagany, integer, `\d{4}`): Rok docelowego budżetu.
  - `month` (wymagany, integer, `\d{1,2}`): Miesiąc docelowego budżetu.
- **Ciało żądania** (`application/ld+json`):
  ```json
  {
    "sourceYear": 2025,
    "sourceMonth": 10
  }
  ```

## 3. Wykorzystywane typy
- **DTO Wejściowe**:
  - `App\DTO\CopyBudgetInput`: Nowa klasa DTO do walidacji i deserializacji ciała żądania.
    ```php
    class CopyBudgetInput
    {
        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        public int $sourceYear;

        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        #[Assert\Range(min: 1, max: 12)]
        public int $sourceMonth;
    }
    ```
- **DTO Wyjściowe**:
  - `App\DTO\BudgetOutput`: Istniejąca klasa DTO, używana do serializacji odpowiedzi, zawierająca szczegóły nowo utworzonego budżetu.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu**:
  - **Kod stanu**: `201 Created`
  - **Ciało odpowiedzi**: Obiekt JSON reprezentujący nowo utworzony budżet, zserializowany przy użyciu `BudgetOutput`.
- **Odpowiedzi błędów**:
  - `400 Bad Request`: Błąd walidacji danych wejściowych (np. brakujące pole w ciele żądania).
  - `401 Unauthorized`: Użytkownik nie jest uwierzytelniony.
  - `404 Not Found`: Budżet źródłowy (dla `sourceYear` i `sourceMonth`) nie został znaleziony dla zalogowanego użytkownika.
  - `409 Conflict`: Budżet dla docelowego `year` i `month` już istnieje.

## 5. Przepływ danych
1.  Żądanie `POST` trafia do API Platform.
2.  Symfony Security sprawdza, czy użytkownik jest uwierzytelniony (`is_granted('ROLE_USER')`).
3.  API Platform deserializuje ciało żądania do obiektu `App\DTO\CopyBudgetInput` i uruchamia walidator Symfony.
4.  Jeśli walidacja DTO przejdzie pomyślnie, API Platform wywołuje dedykowany `State Processor`: `App\State\CopyBudgetProcessor`.
5.  **Wewnątrz `CopyBudgetProcessor`**:
    a. Pobierany jest bieżąco zalogowany użytkownik z `Security`.
    b. Wykonywane jest zapytanie do `BudgetRepository` w celu znalezienia budżetu źródłowego dla `sourceYear`, `sourceMonth` i ID użytkownika. Zapytanie jest automatycznie zabezpieczone przez `CurrentUserExtension`.
    c. Jeśli budżet źródłowy nie zostanie znaleziony, rzucany jest `NotFoundHttpException` (404).
    d. Wykonywane jest zapytanie do `BudgetRepository` w celu sprawdzenia, czy budżet docelowy (`year`, `month`, ID użytkownika) już istnieje.
    e. Jeśli budżet docelowy istnieje, rzucany jest wyjątek `App\Exception\BudgetAlreadyExistsException`, który jest mapowany na odpowiedź `409 Conflict`.
    f. Tworzona jest nowa instancja encji `App\Entity\Budget` z danymi docelowymi (`user`, `year`, `month`).
    g. Wartość `plannedIncome` jest kopiowana z budżetu źródłowego do nowego.
    h. Procesor iteruje po kolekcji `budgetLimits` budżetu źródłowego. Dla każdego limitu tworzony jest nowy obiekt `App\Entity\BudgetLimit`, powiązany z nowym budżetem, z tymi samymi wartościami `subcategory` i `limit`.
    i. `EntityManager` jest używany do zapisania (`persist`) nowej encji `Budget`. Dzięki relacji `cascade: ['persist']`, wszystkie nowe obiekty `BudgetLimit` zostaną zapisane w tej samej transakcji.
6.  API Platform serializuje nowo utworzoną encję `Budget` do formatu JSON przy użyciu `BudgetOutput` i wysyła odpowiedź `201 Created`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Endpoint musi być chroniony i dostępny tylko dla zalogowanych użytkowników. Zostanie to osiągnięte przez dodanie `security: "is_granted('ROLE_USER')"` w definicji operacji `ApiResource`.
- **Autoryzacja**: Użytkownicy mogą kopiować tylko własne budżety. `CurrentUserExtension` zapewni, że zapytania do `BudgetRepository` będą automatycznie ograniczone do zasobów należących do zalogowanego użytkownika, co zapobiega wyciekom danych między kontami.
- **Walidacja**: Wszystkie dane wejściowe (parametry ścieżki i ciało żądania) muszą być rygorystycznie walidowane, aby zapobiec błędom i potencjalnym atakom (np. SQL Injection, chociaż ORM Doctrine w dużej mierze przed tym chroni).

## 7. Rozważania dotyczące wydajności
- Operacja kopiowania budżetu i jego limitów powinna być wykonana w ramach pojedynczej transakcji bazy danych, aby zapewnić atomowość. Domyślne zachowanie `EntityManager::flush()` w Symfony gwarantuje to.
- Liczba zapytań do bazy danych będzie niewielka (odczyt budżetu źródłowego, sprawdzenie istnienia docelowego, zapis nowego budżetu i jego limitów), więc nie przewiduje się problemów z wydajnością.

## 8. Etapy wdrożenia
1.  **Utworzenie DTO**: Stworzyć plik `src/DTO/CopyBudgetInput.php` z właściwościami `sourceYear` i `sourceMonth` oraz odpowiednimi asercjami walidacyjnymi.
2.  **Aktualizacja Encji**: Zaktualizować atrybut `#[ApiResource]` w klasie `src/Entity/Budget.php`, dodając nową operację `POST` dla ścieżki `/budgets/{year}/{month}/copy`. Skonfigurować `input` na `CopyBudgetInput::class` i `processor` na `CopyBudgetProcessor::class`.
3.  **Implementacja Procesora**: Stworzyć klasę `src/State/CopyBudgetProcessor.php` implementującą `ProcessorInterface` z API Platform.
4.  **Logika Procesora**: W metodzie `process()` zaimplementować pełen przepływ danych opisany w sekcji 5, włączając w to pobieranie encji, walidację biznesową, tworzenie nowych obiektów i zapis do bazy danych.
5.  **Testy API**: Utworzyć nową klasę testową `tests/Api/BudgetCopyApiTest.php` dziedziczącą po `ApiTestCase`.
6.  **Scenariusze testowe**: Zaimplementować testy dla:
    - Pomyślnego skopiowania budżetu (oczekiwany status `201 Created` i poprawna struktura odpowiedzi).
    - Próby skopiowania nieistniejącego budżetu źródłowego (oczekiwany status `404 Not Found`).
    - Próby utworzenia budżetu, który już istnieje (oczekiwany status `409 Conflict`).
    - Próby wykonania operacji przez nieuwierzytelnionego użytkownika (oczekiwany status `401 Unauthorized`).
    - Przesłania nieprawidłowych danych w ciele żądania (oczekiwany status `400 Bad Request`).
