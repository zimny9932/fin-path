# API Endpoint Implementation Plan: DELETE /api/transactions/{id}

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest umożliwienie uwierzytelnionemu użytkownikowi trwałego usunięcia jego własnej transakcji finansowej na podstawie jej unikalnego identyfikatora (UUID). Operacja jest idempotentna.

## 2. Szczegóły żądania
- **Metoda HTTP**: `DELETE`
- **Struktura URL**: `/api/transactions/{id}`
- **Parametry**:
  - **Wymagane**:
    - `id` (parametr ścieżki): Unikalny identyfikator (UUID) transakcji, która ma zostać usunięta.
  - **Opcjonalne**: Brak.
- **Request Body**: Brak.

## 3. Wykorzystywane typy
- **Encja**: `App\Entity\Transaction`. Operacja będzie wykonywana bezpośrednio na tej encji.
- **DTOs / Modele**: Nie są wymagane dla tej operacji.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu**:
  - **Kod stanu**: `204 No Content`
  - **Treść**: Pusta.
- **Odpowiedzi błędów**:
  - **Kod stanu**: `401 Unauthorized` (Brak uwierzytelnienia).
  - **Kod stanu**: `403 Forbidden` (Brak uprawnień do zasobu).
  - **Kod stanu**: `404 Not Found` (Zasób nie istnieje).
  - **Kod stanu**: `500 Internal Server Error` (Wewnętrzny błąd serwera).

## 5. Przepływ danych
1. Żądanie `DELETE` jest wysyłane na adres `/api/transactions/{id}`.
2. Firewall Symfony weryfikuje token JWT. Jeśli jest nieprawidłowy lub go brakuje, zwraca odpowiedź `401 Unauthorized`.
3. Router Symfony dopasowuje trasę i przekazuje żądanie do API Platform.
4. API Platform próbuje pobrać encję `Transaction` o podanym `id` za pomocą dostawcy danych (Doctrine).
5.  - Jeśli encja nie zostanie znaleziona, API Platform zwraca odpowiedź `404 Not Found`.
6.  - Jeśli encja zostanie znaleziona, system zabezpieczeń API Platform weryfikuje wyrażenie `security` zdefiniowane dla operacji `Delete`.
7.  - Jeśli weryfikacja uprawnień (`object.getUser() == user`) zakończy się niepowodzeniem, API Platform zwraca odpowiedź `403 Forbidden`.
8.  - Jeśli weryfikacja uprawnień zakończy się sukcesem, żądanie jest przekazywane do domyślnego procesora stanu `RemoveProcessor`.
9. `RemoveProcessor` używa `EntityManager` Doctrine do usunięcia encji z bazy danych.
10. Po pomyślnym usunięciu, API Platform wysyła odpowiedź `204 No Content`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Punkt końcowy musi być chroniony przez firewall `lexik_jwt_authentication`, zezwalając na dostęp tylko użytkownikom z ważnym tokenem JWT.
- **Autoryzacja**: Należy zastosować sprawdzanie na poziomie zasobu, aby zapewnić, że użytkownicy mogą usuwać wyłącznie własne transakcje. Zostanie to zrealizowane poprzez dodanie wyrażenia `security: "is_granted('ROLE_USER') and object.getUser() == user"` do definicji operacji `Delete` w atrybucie `#[ApiResource]`.
- **Walidacja danych wejściowych**: Format identyfikatora `id` jako UUID jest automatycznie walidowany przez mechanizmy routingu Symfony i API Platform.

## 7. Obsługa błędów
- **`401 Unauthorized`**: Zwracany przez firewall JWT, gdy token jest nieobecny, nieważny lub wygasł.
- **`403 Forbidden`**: Zwracany przez system bezpieczeństwa API Platform, gdy użytkownik próbuje usunąć transakcję, której nie jest właścicielem.
- **`404 Not Found`**: Zwracany, gdy transakcja o podanym `id` nie istnieje lub `id` ma nieprawidłowy format UUID.
- **`500 Internal Server Error`**: Zwracany w przypadku nieoczekiwanych błędów, np. problemów z połączeniem z bazą danych. Błędy te będą logowane przez Monolog.

## 8. Rozważania dotyczące wydajności
- Operacja usuwania pojedynczego rekordu na podstawie klucza głównego jest wysoce wydajna dzięki indeksowi `PRIMARY KEY` na kolumnie `id`.
- Nie przewiduje się żadnych wąskich gardeł wydajnościowych. Operacja składa się z jednego zapytania `SELECT` w celu znalezienia encji i jednego zapytania `DELETE`.

## 9. Etapy wdrożenia
1.  **Modyfikacja `src/Entity/Transaction.php`**:
    - W atrybucie `#[ApiResource]` dodać nową operację `Delete`.
    - Dodać do niej parametr `security` w celu zapewnienia, że tylko właściciel może usunąć transakcję.
2.  **Utworzenie testu API (`tests/Api/TransactionDeleteApiTest.php` lub rozszerzenie istniejącego)**:
    - Zaimplementować test weryfikujący pomyślne usunięcie transakcji przez jej właściciela (oczekiwany status `204`).
    - Zaimplementować test weryfikujący, że dane zostały faktycznie usunięte z bazy danych.
    - Zaimplementować test próby usunięcia transakcji przez nieuwierzytelnionego użytkownika (oczekiwany status `401`).
    - Zaimplementować test próby usunięcia transakcji należącej do innego użytkownika (oczekiwany status `403`).
    - Zaimplementować test próby usunięcia nieistniejącej transakcji (oczekiwany status `404`).
    - Zaimplementować test próby usunięcia transakcji z nieprawidłowym formatem UUID w URL (oczekiwany status `404`).
3.  **Weryfikacja zmian**:
    - Uruchomić zestaw testów, aby upewnić się, że nowa funkcjonalność działa zgodnie z oczekiwaniami i nie narusza istniejących testów.
