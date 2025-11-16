# API Endpoint Implementation Plan: GET /api/transactions/{id}

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest umożliwienie uwierzytelnionym użytkownikom pobierania szczegółów pojedynczej transakcji na podstawie jej unikalnego identyfikatora (UUID). System musi zapewnić, że użytkownicy mają dostęp wyłącznie do własnych transakcji, zapobiegając w ten sposób nieautoryzowanemu dostępowi do danych.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/transactions/{id}`
- **Parametry**:
  - **Wymagane**:
    - `id`: `string` (UUID) - Identyfikator transakcji przekazywany w ścieżce URL.
- **Request Body**: Brak.

## 3. Wykorzystywane typy
- **Encja**: `App\Entity\Transaction`
- **DTO (Data Transfer Object)**:
  - `App\DTO\TransactionOutput`: Główny obiekt DTO używany do formatowania odpowiedzi. Powinien zawierać następujące pola:
    - `id`: `string` (UUID)
    - `date`: `string` (YYYY-MM-DD)
    - `description`: `string|null`
    - `subcategory`: `SubcategoryNestedOutput`
    - `amount`: `MoneyOutput`
  - `App\DTO\SubcategoryNestedOutput`: Zagnieżdżony DTO dla podkategorii.
  - `App\DTO\MoneyOutput`: Zagnieżdżony DTO dla kwoty i waluty.

## 4. Szczegóły odpowiedzi
- **Sukces (`200 OK`)**:
  - **Content-Type**: `application/ld+json`
  - **Body**: Obiekt JSON reprezentujący transakcję, zgodny ze strukturą `TransactionOutput` DTO.
  ```json
  {
    "@context": "/api/contexts/Transaction",
    "@id": "/api/transactions/018b8f88-466b-770b-a436-ce43341c3782",
    "@type": "Transaction",
    "id": "018b8f88-466b-770b-a436-ce43341c3782",
    "date": "2025-11-01",
    "description": "Przykładowy opis transakcji",
    "subcategory": {
      "id": "018b8f88-2510-739c-a81d-528247afa52a",
      "name": "Artykuły spożywcze",
      "mainCategory": "needs",
      "type": "expense"
    },
    "amount": {
      "amount": "150.50",
      "currency": "PLN"
    }
  }
  ```
- **Błędy**:
  - `401 Unauthorized`: Gdy użytkownik jest nieuwierzytelniony.
  - `404 Not Found`: Gdy transakcja o podanym `id` nie istnieje lub użytkownik nie ma do niej uprawnień.

## 5. Przepływ danych
1.  Klient wysyła żądanie `GET` na adres `/api/transactions/{id}`, dołączając ważny token JWT w nagłówku `Authorization`.
2.  Router Symfony kieruje żądanie do odpowiedniej operacji `Get` zdefiniowanej w zasobie `ApiResource` dla encji `Transaction`.
3.  Komponent `Security` Symfony weryfikuje token JWT i uwierzytelnia użytkownika.
4.  Domyślny dostawca stanu (State Provider) API Platform wykonuje zapytanie do bazy danych w celu znalezienia encji `Transaction` o podanym `id`.
5.  Po pobraniu obiektu, API Platform wykonuje sprawdzenie bezpieczeństwa zdefiniowane w atrybucie `security`. Porównuje `user` (zalogowany użytkownik) z `object.getUser()` (właściciel transakcji).
6.  Jeśli encja zostanie znaleziona i walidacja bezpieczeństwa przejdzie pomyślnie, obiekt jest przekazywany do serializatora.
7.  Serializator, korzystając ze zdefiniowanego `TransactionOutput` DTO, transformuje dane encji na format JSON.
8.  Do klienta wysyłana jest odpowiedź `200 OK` z serializowanymi danymi.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego musi być ograniczony do uwierzytelnionych użytkowników. Zostanie to osiągnięte poprzez dodanie warunku `is_granted('ROLE_USER')` w atrybucie `security` operacji.
- **Autoryzacja**: Aby zapobiec atakom typu IDOR, zostanie wdrożona reguła sprawdzająca, czy zalogowany użytkownik jest właścicielem żądanego zasobu. Zostanie to zrealizowane za pomocą warunku `object.getUser() == user`. Encja `Transaction` musi posiadać poprawną relację z encją `User` i implementować `UserOwnedInterface`.

## 7. Obsługa błędów
- **`401 Unauthorized`**: Obsługiwane automatycznie przez `lexik/jwt-authentication-bundle`, gdy token jest nieprawidłowy lub go brakuje.
- **`404 Not Found`**: Zwracane w dwóch przypadkach:
    1.  Gdy encja `Transaction` o podanym `id` nie zostanie znaleziona w bazie danych.
    2.  Gdy encja zostanie znaleziona, ale sprawdzenie bezpieczeństwa (`object.getUser() == user`) zakończy się niepowodzeniem. Jest to celowe działanie, aby nie ujawniać informacji o istnieniu zasobów, do których użytkownik nie ma dostępu.
- **`500 Internal Server Error`**: Obsługiwane przez Symfony. Błędy będą logowane przez Monolog, co ułatwi diagnozowanie problemów.

## 8. Rozważania dotyczące wydajności
- Zapytanie o pojedynczy zasób po kluczu głównym (`id`) jest z natury bardzo wydajne, pod warunkiem istnienia indeksu (co jest standardem dla kluczy głównych).
- Relacje z `Subcategory` i `User` zostaną obsłużone przez Doctrine. Domyślne strategie ładowania (lazy loading) są wystarczające dla tego przypadku użycia i nie powinny generować problemu N+1.
- Nie przewiduje się znaczących wąskich gardeł wydajnościowych dla tego punktu końcowego.

## 9. Etapy wdrożenia
1.  **Modyfikacja encji `Transaction`**:
    - Upewnić się, że encja `Transaction` istnieje (`src/Entity/Transaction.php`).
    - Zweryfikować, czy posiada ona poprawną relację `ManyToOne` z encją `User`.
    - Upewnić się, że encja implementuje interfejs `App\Entity\Contract\UserOwnedInterface` i posiada metodę `getUser(): User`.
2.  **Weryfikacja DTO**:
    - Sprawdzić, czy istnieją i są poprawnie zdefiniowane klasy `TransactionOutput`, `SubcategoryNestedOutput` oraz `MoneyOutput` w katalogu `src/DTO/`. W razie potrzeby uzupełnić brakujące.
3.  **Konfiguracja `ApiResource`**:
    - W pliku encji `Transaction` (`src/Entity/Transaction.php`), w atrybucie `#[ApiResource]`, dodać nową operację `#[Get]`.
4.  **Implementacja zabezpieczeń**:
    - Do atrybutu `#[Get]` dodać parametr `security`: `security: "is_granted('ROLE_USER') and object.getUser() == user"`.
5.  **Konfiguracja DTO wyjściowego**:
    - W atrybucie `#[Get]` dodać parametr `output`, aby wskazać DTO dla odpowiedzi: `output: TransactionOutput::class`.
6.  **Napisanie testów API**:
    - Stworzyć nową klasę testową w `tests/Api/TransactionGetApiTest.php`.
    - Zaimplementować następujące scenariusze testowe:
        - Pobranie istniejącej transakcji przez jej właściciela (oczekiwany status `200 OK` i poprawna struktura JSON).
        - Próba pobrania nieistniejącej transakcji (oczekiwany status `404 Not Found`).
        - Próba pobrania transakcji należącej do innego użytkownika (oczekiwany status `404 Not Found`).
        - Próba pobrania transakcji bez uwierzytelnienia (oczekiwany status `401 Unauthorized`).
