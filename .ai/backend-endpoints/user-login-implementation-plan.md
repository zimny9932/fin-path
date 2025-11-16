# API Endpoint Implementation Plan: POST /api/login

## 1. Przegląd punktu końcowego
Celem tego punktu końcowego jest uwierzytelnienie użytkownika na podstawie jego adresu e-mail i hasła. Po pomyślnej weryfikacji tożsamości, API zwraca token JWT oraz token odświeżający (`refresh_token`), które umożliwiają autoryzowany dostęp do chronionych zasobów aplikacji. Endpoint ten stanowi fundament systemu bezpieczeństwa API.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/login`
- **Nagłówki**:
  - `Content-Type`: `application/json`
  - `Accept`: `application/json`
- **Request Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```
- **Parametry**:
  - **Wymagane**: `email` (string), `password` (string).
  - **Opcjonalne**: Brak.

## 3. Wykorzystywane typy
Implementacja nie wymaga tworzenia niestandardowych klas DTO (Data Transfer Object) ani Command Models. Proces jest w pełni obsługiwany przez komponent `Symfony Security` i pakiet `lexik/jwt-authentication-bundle`, które automatycznie przetwarzają przychodzący JSON i generują odpowiedź.

## 4. Szczegóły odpowiedzi
- **Success Response (`200 OK`)**:
  ```json
  {
    "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "def502006421d513a16f5c87515cb21a..."
  }
  ```
- **Error Responses**:
  - `400 Bad Request`: Wysłano nieprawidłowy format danych (np. błędny JSON) lub brakuje wymaganych pól.
  - `401 Unauthorized`: Podano błędne dane uwierzytelniające (e-mail lub hasło).
  - `500 Internal Server Error`: Wystąpił błąd po stronie serwera (np. problem z konfiguracją, niedostępność bazy danych).

## 5. Przepływ danych
1. Klient wysyła żądanie `POST` na adres `/api/login` z `email` i `password` w ciele żądania.
2. Zapora (`firewall`) skonfigurowana w `security.yaml` przechwytuje żądanie.
3. Authenticator `json_login` odczytuje dane uwierzytelniające z ciała żądania.
4. `UserProvider` (oparty na Doctrine) wyszukuje użytkownika w bazie danych na podstawie podanego adresu e-mail.
5. Jeśli użytkownik zostanie znaleziony, `PasswordHasher` weryfikuje poprawność hasła.
6. Po pomyślnym uwierzytelnieniu, `lexik/jwt-authentication-bundle` generuje token JWT i (opcjonalnie, przy użyciu `gesdinet/jwt-refresh-token-bundle`) token odświeżający.
7. Tokeny są zwracane w odpowiedzi JSON z kodem statusu `200 OK`.
8. W przypadku niepowodzenia na którymkolwiek z kroków 4-5, zwracany jest błąd `401 Unauthorized`.

## 6. Względy bezpieczeństwa
- **Ochrona przed atakami Brute-Force**: Należy zaimplementować mechanizm `Rate Limiter` w Symfony, aby ograniczyć liczbę nieudanych prób logowania z danego adresu IP w określonym czasie.
- **Transport**: Cała komunikacja musi odbywać się wyłącznie przez szyfrowane połączenie HTTPS (TLS), aby chronić dane uwierzytelniające i tokeny przed przechwyceniem.
- **Zarządzanie kluczami**: Klucze prywatny i publiczny do podpisywania tokenów JWT (OpenSSL) muszą być generowane i przechowywane w bezpieczny sposób, poza repozytorium kodu, np. jako zmienne środowiskowe lub w systemie zarządzania sekretami.
- **Czas życia tokenów**:
  - **JWT**: Powinien mieć krótki czas życia (np. 15 minut), aby zminimalizować ryzyko w przypadku jego wycieku.
  - **Refresh Token**: Powinien mieć długi czas życia (np. 7 dni) i być przechowywany w bezpieczny sposób po stronie klienta (np. w ciasteczku `HttpOnly`).
- **Walidacja danych**: Dane wejściowe są walidowane przez mechanizmy bezpieczeństwa Symfony. Nie ma bezpośredniego ryzyka SQL Injection, ponieważ używane jest Doctrine ORM z parametryzowanymi zapytaniami.

## 7. Rozważania dotyczące wydajności
- **Zapytania do bazy danych**: Proces logowania generuje jedno proste zapytanie `SELECT` do tabeli `users` po indeksowanym polu `email`, co jest wysoce wydajne.
- **Generowanie tokenów**: Kryptograficzne operacje podpisywania tokenów mogą być obciążające dla procesora przy bardzo dużym natężeniu ruchu. Należy monitorować obciążenie CPU serwera. W większości przypadków nie stanowi to jednak wąskiego gardła.
- **Skalowanie**: Endpoint jest bezstanowy, co ułatwia jego skalowanie horyzontalne za pomocą load balancera.

## 8. Etapy wdrożenia
1.  **Weryfikacja zależności**: Upewnij się, że pakiety `lexik/jwt-authentication-bundle` i `gesdinet/jwt-refresh-token-bundle` są poprawnie zainstalowane i skonfigurowane w `composer.json`.
2.  **Generowanie kluczy SSH**: Wygeneruj klucze `private.pem` i `public.pem` za pomocą `openssl` i skonfiguruj ich ścieżki oraz hasło (`JWT_PASSPHRASE`) w pliku `.env`.
3.  **Konfiguracja `lexik_jwt_authentication.yaml`**: Zdefiniuj czas życia tokenu (`token_ttl`), algorytm podpisu (`signature_algorithm`) oraz ścieżki do kluczy.
4.  **Konfiguracja `security.yaml`**:
    - W sekcji `firewalls` dla `api` dodaj obsługę `json_login`.
    - Skonfiguruj `path` na `/api/login` oraz `check_path` na `/api/login`.
    - Ustaw `username_path` na `email`, aby `UserProvider` używał pola `email` zamiast domyślnego `username`.
    - Upewnij się, że `provider` wskazuje na dostawcę użytkowników opartego na encji `App\Entity\User`.
5.  **Konfiguracja `gesdinet_jwt_refresh_token.yaml`**: Skonfiguruj czas życia (`ttl`) i inne parametry dla tokenów odświeżających.
6.  **Definicja routingu**: Dodaj trasę `/api/login` w plikach konfiguracyjnych routingu, jeśli nie jest ona jeszcze zarządzana przez zaporę sieciową. Zazwyczaj `check_path` w `security.yaml` jest wystarczający.
7.  **Utworzenie testów API**:
    - Napisz test (`ApiTestCase`) sprawdzający pomyślne logowanie i otrzymanie tokenów (status `200 OK`).
    - Dodaj testy dla przypadków błędnych: nieprawidłowe dane uwierzytelniające (oczekiwany status `401`), brakujące pole w ciele żądania (oczekiwany status `400`), nieprawidłowy format JSON (oczekiwany status `400`).
8.  **Dokumentacja**: Zaktualizuj dokumentację API (np. OpenAPI/Swagger), aby odzwierciedlała strukturę żądania i odpowiedzi oraz możliwe kody błędów.
