# API Endpoint Implementation Plan: PUT /api/transactions/{id}

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia uwierzytelnionym użytkownikom aktualizację istniejącej transakcji finansowej. Użytkownik może modyfikować tylko transakcje, których jest właścicielem. Punkt końcowy zapewnia walidację danych wejściowych i zwraca zaktualizowany obiekt transakcji w przypadku powodzenia.

## 2. Szczegóły żądania
- **Metoda HTTP**: `PUT`
- **Struktura URL**: `/api/transactions/{id}`
- **Parametry**:
  - **Wymagane**:
    - `id` (w ścieżce, UUID): Identyfikator transakcji do aktualizacji.
  - **Opcjonalne**: Brak
- **Ciało żądania (Request Body)**: Oczekiwany jest obiekt JSON zgodny ze strukturą DTO `App\DTO\TransactionInput`.
  ```json
  {
    "subcategory_id": "018f3a33-6a83-7a38-92a1-8c460d3d5c55",
    "amount": {
      "amount": 15000,
      "currency": "PLN"
    },
    "date": "2025-11-15",
    "description": "Updated weekly groceries"
  }
  ```

## 3. Wykorzystywane typy
- **Encja**: `App\Entity\Transaction` - główna encja Doctrine reprezentująca transakcję.
- **DTO wejściowe**: `App\DTO\TransactionInput` - obiekt transferu danych używany do walidacji i mapowania danych przychodzących w żądaniu `PUT`.
- **DTO wyjściowe**: `App\DTO\TransactionOutput` - obiekt transferu danych definiujący strukturę odpowiedzi po pomyślnej aktualizacji.
- **Model zagnieżdżony**: `App\DTO\MoneyInput` - obiekt transferu danych dla pola `amount`.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (`200 OK`)**: Zwraca obiekt JSON reprezentujący zaktualizowaną transakcję, zgodnie ze strukturą `App\DTO\TransactionOutput`.
  ```json
  {
    "id": "018f3a2b-8b49-7c98-a532-3492576b291c",
    "subcategory": {
        "id": "018f3a33-6a83-7a38-92a1-8c460d3d5c55",
        "name": "Groceries"
    },
    "amount": {
      "amount": 15000,
      "currency": "PLN"
    },
    "date": "2025-11-15T00:00:00+00:00",
    "description": "Updated weekly groceries"
  }
  ```
- **Odpowiedzi błędów**: Zobacz sekcję "Obsługa błędów".

## 5. Przepływ danych
1.  Użytkownik wysyła żądanie `PUT` na adres `/api/transactions/{id}` z tokenem JWT w nagłówku `Authorization`.
2.  API Platform przechwytuje żądanie i na podstawie `id` pobiera z bazy danych encję `Transaction`.
3.  Mechanizm bezpieczeństwa API Platform weryfikuje uprawnienia, sprawdzając, czy zalogowany użytkownik jest właścicielem transakcji (`object.getUser() == user`). Jeśli nie, proces jest przerywany (odpowiedź `404 Not Found`).
4.  Dane z ciała żądania są deserializowane do obiektu `App\DTO\TransactionInput`.
5.  Komponent Symfony Validator uruchamia walidację na obiekcie DTO. W przypadku błędów zwracana jest odpowiedź `400 Bad Request` z listą naruszeń.
6.  Jeśli walidacja DTO przejdzie pomyślnie, API Platform przekazuje załadowaną encję `Transaction` oraz DTO `TransactionInput` do procesora `App\State\TransactionProcessor`.
7.  `TransactionProcessor` weryfikuje, czy podkategoria (`subcategory_id` z DTO) istnieje i należy do zalogowanego użytkownika. Jeśli nie, zwraca wyjątek `NotFoundHttpException` (odpowiedź `404 Not Found`).
8.  Procesor aktualizuje pola encji `Transaction` danymi z DTO.
9.  `EntityManager` zapisuje zmiany w bazie danych w ramach transakcji.
10. API Platform serializuje zaktualizowaną encję `Transaction` do formatu zdefiniowanego w `App\DTO\TransactionOutput` i zwraca odpowiedź `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego jest chroniony i wymaga prawidłowego tokenu JWT (`lexik/jwt-authentication-bundle`).
- **Autoryzacja**: Zastosowana zostanie reguła `security: "is_granted('ROLE_USER') and object.getUser() == user"` w definicji `#[ApiResource]` dla operacji `Put`. Gwarantuje to, że tylko właściciel może modyfikować swoją transakcję.
- **Walidacja danych**: Wszystkie dane wejściowe są walidowane za pomocą komponentu Symfony Validator, aby zapobiec atakom (np. XSS, SQL Injection) i zapewnić integralność danych.
- **Kontrola dostępu do zasobów powiązanych**: `TransactionProcessor` dodatkowo weryfikuje, czy `subcategory_id` podany w żądaniu należy do zalogowanego użytkownika, uniemożliwiając przypisanie transakcji do cudzej podkategorii.

## 7. Obsługa błędów
- **`400 Bad Request`**: Zwracany, gdy dane w ciele żądania nie przejdą walidacji (np. brakujące pole, nieprawidłowy format danych, przekroczona długość opisu).
- **`401 Unauthorized`**: Zwracany, gdy użytkownik nie jest uwierzytelniony (brak, nieważny lub wygasły token JWT).
- **`404 Not Found`**: Zwracany, gdy:
  - Transakcja o podanym `id` nie istnieje.
  - Użytkownik próbuje uzyskać dostęp do transakcji, której nie jest właścicielem.
  - Podkategoria o podanym `subcategory_id` nie istnieje lub nie należy do zalogowanego użytkownika.
- **`500 Internal Server Error`**: Zwracany w przypadku nieoczekiwanych błędów po stronie serwera, np. problem z połączeniem z bazą danych.

## 8. Rozważania dotyczące wydajności
Operacja `PUT` na pojedynczym zasobie nie powinna generować znaczących problemów z wydajnością. Najważniejsze operacje to:
1.  Pobranie transakcji na podstawie `id` (indeksowany klucz główny).
2.  Pobranie podkategorii na podstawie `id` (indeksowany klucz główny) w celu weryfikacji.
3.  Operacja `UPDATE` w bazie danych.

Wszystkie te operacje są wydajne. Należy upewnić się, że kolumny `id` i `user_id` w tabelach `transactions` i `subcategories` są poprawnie zindeksowane.

## 9. Etapy wdrożenia
1.  **Aktualizacja encji `Transaction`**:
    - Upewnić się, że encja `App\Entity\Transaction` implementuje `App\Entity\Contract\UserOwnedInterface`.
    - Sprawdzić, czy relacja z encją `User` istnieje i czy metoda `getUser(): User` jest zaimplementowana.
2.  **Konfiguracja DTO `TransactionInput`**:
    - Dodać lub zweryfikować atrybuty walidacji Symfony Validator dla wszystkich właściwości (`subcategory_id`, `amount`, `date`, `description`) zgodnie z wymaganiami.
    - Upewnić się, że dla `amount` używany jest zagnieżdżony DTO `MoneyInput` z własną walidacją.
3.  **Konfiguracja `#[ApiResource]`**:
    - W encji `App\Entity\Transaction` dodać lub zaktualizować operację `Put`.
    - Skonfigurować atrybuty:
      - `security: "is_granted('ROLE_USER') and object.getUser() == user"`
      - `input: TransactionInput::class`
      - `output: TransactionOutput::class`
      - `processor: TransactionProcessor::class`
4.  **Implementacja logiki w `TransactionProcessor`**:
    - Rozszerzyć metodę `process()` w `App\State\TransactionProcessor`, aby obsługiwała operację `Put`.
    - Wewnątrz `process()`, pobrać zalogowanego użytkownika.
    - Sprawdzić, czy `subcategory_id` z DTO wejściowego (`$data`) należy do pobranego użytkownika. W przypadku niepowodzenia rzucić `NotFoundHttpException`.
    - Zmapować dane z DTO (`$data`) na istniejącą encję transakcji (`$operation->getData()`).
    - Zapisać zmiany za pomocą `EntityManagerInterface`.
5.  **Napisanie testów API**:
    - Utworzyć lub rozszerzyć `tests/Api/TransactionApiTest.php`.
    - Dodać test dla pomyślnej aktualizacji transakcji (status `200 OK` i poprawna treść odpowiedzi).
    - Dodać testy dla przypadków błędnych:
      - Próba aktualizacji nieistniejącej transakcji (oczekiwany status `404 Not Found`).
      - Próba aktualizacji transakcji innego użytkownika (oczekiwany status `404 Not Found`).
      - Próba aktualizacji z nieprawidłowymi danymi (np. brak `date`, `amount` ujemne - oczekiwany status `400 Bad Request`).
      - Próba aktualizacji z `subcategory_id` nienależącym do użytkownika (oczekiwany status `404 Not Found`).
      - Próba aktualizacji bez uwierzytelnienia (oczekiwany status `401 Unauthorized`).
