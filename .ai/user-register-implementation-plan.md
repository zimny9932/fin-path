# API Endpoint Implementation Plan: POST /api/register

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia nowym użytkownikom utworzenie konta w systemie. Po pomyślnej walidacji danych wejściowych i utworzeniu użytkownika, API zwraca tokeny JWT (`token` i `refresh_token`), które umożliwiają uwierzytelniony dostęp do innych zasobów API.

## 2. Szczegóły żądania
- **Metoda HTTP**: `POST`
- **Struktura URL**: `/api/register`
- **Nagłówki**:
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Request Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123",
    "passwordConfirmation": "password123"
  }
  ```
  - **`email`** (string, wymagane): Unikalny adres e-mail użytkownika.
  - **`password`** (string, wymagane): Hasło użytkownika (minimum 8 znaków).
  - **`passwordConfirmation`** (string, wymagane): Powtórzone hasło w celu potwierdzenia.

## 3. Wykorzystywane typy
Zostanie utworzona klasa DTO (Data Transfer Object) do obsługi i walidacji danych wejściowych.

- **`App\DTO\RegistrationInput`**
  ```php
  namespace App\DTO;

  use Symfony\Component\Validator\Constraints as Assert;

  class RegistrationInput
  {
      #[Assert\NotBlank]
      #[Assert\Email]
      public string $email;

      #[Assert\NotBlank]
      #[Assert\Length(min: 8, minMessage: 'Password should be at least {{ limit }} characters')]
      public string $password;

      #[Assert\NotBlank]
      #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match')]
      public string $passwordConfirmation;
  }
  ```

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (`201 Created`)**:
  ```json
  {
    "token": "...",
    "refresh_token": "..."
  }
  ```
- **Odpowiedzi błędów**:
  - **`400 Bad Request`**: Zwracana w przypadku błędów walidacji. Odpowiedź będzie zawierać szczegóły naruszeń.
    ```json
    {
      "type": "https://symfony.com/errors/validation",
      "title": "Validation Failed",
      "detail": "email: This value is not a valid email address.",
      "violations": [
        {
          "propertyPath": "email",
          "message": "This value is not a valid email address."
        }
      ]
    }
    ```
  - **`409 Conflict`**: Zwracana, gdy użytkownik o podanym adresie e-mail już istnieje.
    ```json
    {
      "error": "User with this email already exists."
    }
    ```

## 5. Przepływ danych
1.  Żądanie `POST` trafia do `RegistrationController`.
2.  Symfony deserializuje ciało żądania do obiektu `RegistrationInput` DTO.
3.  Komponent `Validator` automatycznie waliduje obiekt DTO. W przypadku niepowodzenia zwraca odpowiedź `400 Bad Request`.
4.  Kontroler wywołuje serwis `UserRegistrationProcessor`, przekazując mu DTO.
5.  `UserRegistrationProcessor` sprawdza w `UserRepository`, czy użytkownik o podanym e-mailu już istnieje. Jeśli tak, rzuca wyjątek `UserAlreadyExistsException`.
6.  Listener wyjątków przechwytuje `UserAlreadyExistsException` i zwraca odpowiedź `409 Conflict`.
7.  Serwis hashuje hasło użytkownika za pomocą `PasswordHasherInterface`.
8.  Serwis tworzy nową instancję encji `User` i zapisuje ją do bazy danych za pomocą `EntityManagerInterface`.
9.  Kontroler odbiera nowo utworzony obiekt `User`.
10. Kontroler używa serwisu z `lexik/jwt-authentication-bundle` do wygenerowania tokenów JWT dla użytkownika.
11. Kontroler zwraca odpowiedź `201 Created` z tokenami.

## 6. Względy bezpieczeństwa
- **Hashowanie haseł**: Wszystkie hasła będą hashowane przy użyciu `PasswordHasherInterface` z Symfony, co zapewni silne i bezpieczne przechowywanie poświadczeń.
- **Walidacja po stronie serwera**: Rygorystyczna walidacja wszystkich danych wejściowych zapobiegnie atakom typu injection i zapewni integralność danych.
- **Ograniczenie liczby żądań (Rate Limiting)**: Zaleca się zaimplementowanie mechanizmu rate limiting (np. za pomocą `symfony/rate-limiter-bundle`), aby chronić endpoint przed atakami typu brute-force.
- **HTTPS**: Cała komunikacja z API musi odbywać się przez szyfrowane połączenie HTTPS.

## 7. Obsługa błędów
- **Błędy walidacji (`400`)**: Obsługiwane automatycznie przez Symfony po skonfigurowaniu walidacji na DTO.
- **Konflikt danych (`409`)**: Obsługiwany przez dedykowany wyjątek `UserAlreadyExistsException` i listener, który konwertuje go na odpowiedź HTTP.
- **Błędy serwera (`500`)**: Standardowa obsługa błędów przez Symfony, z logowaniem szczegółów przy użyciu Monolog.

## 8. Rozważania dotyczące wydajności
- Główne obciążenie dla tego punktu końcowego to zapytanie do bazy danych w celu sprawdzenia unikalności adresu e-mail. Kolumna `email` w tabeli `users` musi mieć założony indeks `UNIQUE`, aby zapewnić wysoką wydajność tej operacji.
- Generowanie tokenu JWT jest operacją szybką i nie powinno stanowić wąskiego gardła.

## 9. Etapy wdrożenia
1.  **Utworzenie DTO**: Stwórz klasę `App\DTO\RegistrationInput` z właściwościami `email`, `password`, `passwordConfirmation` i dodaj odpowiednie atrybuty walidacyjne `Symfony\Component\Validator\Constraints`.
2.  **Utworzenie serwisu**: Stwórz serwis `App\Service\UserRegistrationProcessor` z metodą `register(RegistrationInput $input): User`.
3.  **Implementacja logiki serwisu**:
    - Wstrzyknij `UserRepository`, `PasswordHasherInterface` i `EntityManagerInterface` do serwisu.
    - Zaimplementuj logikę sprawdzania istnienia użytkownika. Jeśli użytkownik istnieje, rzuć niestandardowy wyjątek `App\Exception\UserAlreadyExistsException`.
    - Zaimplementuj logikę hashowania hasła i tworzenia nowej encji `User`.
4.  **Utworzenie kontrolera**: Stwórz `App\Controller\Api\RegistrationController`.
5.  **Implementacja akcji kontrolera**:
    - Utwórz metodę `register(#[MapRequestPayload] RegistrationInput $input)` z atrybutem routingu `#[Route('/api/register', methods: ['POST'])]`.
    - Wywołaj serwis `UserRegistrationProcessor`.
    - Zintegruj `lexik/jwt-authentication-bundle`, aby wygenerować tokeny dla nowo utworzonego użytkownika.
    - Zwróć odpowiedź `JsonResponse` ze statusem `201 Created` i tokenami.
6.  **Utworzenie Listenera Wyjątków**: Stwórz listener, który będzie nasłuchiwał na `KernelEvents::EXCEPTION` i w przypadku wystąpienia `UserAlreadyExistsException` zwróci odpowiedź `JsonResponse` ze statusem `409 Conflict`.
7.  **Konfiguracja `lexik/jwt-authentication-bundle`**: Upewnij się, że pakiet jest poprawnie skonfigurowany do generowania tokenów.
8.  **Napisanie testów**: Utwórz testy integracyjne dla endpointu, które pokryją scenariusze:
    - Pomyślnej rejestracji (`201`).
    - Błędów walidacji (`400`).
    - Próby rejestracji na istniejący e-mail (`409`).
