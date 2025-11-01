# API Endpoint Implementation Plan: GET /api/users/me

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia pobranie danych profilu aktualnie uwierzytelnionego użytkownika. Dostęp jest chroniony i wymaga prawidłowego tokenu JWT. Zwraca podstawowe, bezpieczne do publicznego wglądu informacje o użytkowniku.

## 2. Szczegóły żądania
- **Metoda HTTP**: `GET`
- **Struktura URL**: `/api/users/me`
- **Nagłówki**:
  - `Authorization`: `Bearer <jwt_token>` (Wymagane)
  - `Accept`: `application/ld+json` (lub inny obsługiwany format)
- **Parametry**: Brak
- **Request Body**: Brak

## 3. Wykorzystywane typy

### `App\DTO\UserOutput`
Aby zapewnić, że API zwraca tylko niezbędne i bezpieczne dane, zostanie utworzona dedykowana klasa DTO. Zapobiegnie to przypadkowemu wyciekowi wrażliwych informacji z encji `User`.

```php
<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Uid\Uuid;

final readonly class UserOutput
{
    public function __construct(
        public Uuid $id,
        public string $email,
        public ?int $billingCycleStartDay,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt
    ) {
    }
}
```

## 4. Szczegóły odpowiedzi

### Sukces (`200 OK`)
Gdy użytkownik jest poprawnie uwierzytelniony, serwer zwróci odpowiedź z kodem `200 OK` i następującą strukturą JSON:
```json
{
  "@context": "/api/contexts/User",
  "@id": "/api/users/me",
  "@type": "User",
  "id": "01J8X2Q0Y4Z0Z0Z0Z0Z0Z0Z0Z0",
  "email": "user@example.com",
  "billingCycleStartDay": 25,
  "createdAt": "2025-11-01T10:00:00+00:00",
  "updatedAt": "2025-11-01T10:00:00+00:00"
}
```
*Uwaga: Pola `@context`, `@id`, `@type` są dodawane automatycznie przez API Platform (JSON-LD).*

### Błędy
- `401 Unauthorized`: W przypadku braku, nieważności lub wygaśnięcia tokenu JWT.
  ```json
  {
    "code": 401,
    "message": "Invalid JWT Token"
  }
  ```

## 5. Przepływ danych
1.  Żądanie `GET /api/users/me` trafia do aplikacji.
2.  Firewall Symfony (`security.firewall`) przechwytuje żądanie i weryfikuje token JWT za pomocą `lexik/jwt-authentication-bundle`.
3.  Jeśli token jest nieprawidłowy, proces jest przerywany i zwracany jest błąd `401 Unauthorized`.
4.  Jeśli token jest prawidłowy, API Platform przejmuje kontrolę i identyfikuje operację `GET` na zasobie `User`.
5.  Zgodnie z konfiguracją operacji, wywoływany jest dedykowany dostawca stanu: `App\State\MeProvider`.
6.  `MeProvider` używa serwisu `Symfony\Bundle\SecurityBundle\Security` do pobrania w pełni uwierzytelnionego obiektu `User`.
7.  Zwrócony obiekt `User` jest przekazywany do serializatora.
8.  API Platform, na podstawie konfiguracji `output: UserOutput::class`, mapuje dane z encji `User` na DTO `UserOutput`.
9.  Serializator konwertuje obiekt `UserOutput` na format JSON.
10. Odpowiedź `200 OK` z danymi w formacie JSON jest wysyłana do klienta.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego jest bezwzględnie chroniony przez mechanizm JWT. Każde żądanie musi zawierać prawidłowy token.
- **Autoryzacja**: Dostęp jest ograniczony do uwierzytelnionych użytkowników. W konfiguracji zasobu zostanie użyta dyrektywa `security: "is_granted('ROLE_USER')"` aby upewnić się, że tylko zalogowani użytkownicy mogą uzyskać dostęp.
- **Walidacja danych**: Nie ma danych wejściowych od użytkownika, więc walidacja nie jest wymagana.
- **Ochrona przed wyciekiem danych**: Użycie `UserOutput` DTO gwarantuje, że wrażliwe pola, takie jak `passwordHash`, nigdy nie zostaną zwrócone w odpowiedzi API.

## 7. Rozważania dotyczące wydajności
- Zapytanie do bazy danych jest bardzo proste – polega na pobraniu jednego rekordu po kluczu głównym (`id`), który jest domyślnie indeksowany.
- Nie przewiduje się problemów z wydajnością. Operacja powinna być bardzo szybka.

## 8. Etapy wdrożenia
1.  **Utworzenie `UserOutput` DTO**:
    -   Stwórz plik `src/DTO/UserOutput.php`.
    -   Zdefiniuj w nim publiczne, niemutowalne właściwości: `id`, `email`, `billingCycleStartDay`, `createdAt`, `updatedAt`, zgodnie z definicją w sekcji 3.

2.  **Utworzenie `MeProvider`**:
    -   Stwórz plik `src/State/MeProvider.php`.
    -   Zaimplementuj interfejs `ApiPlatform\State\ProviderInterface`.
    -   Wstrzyknij serwis `Symfony\Bundle\SecurityBundle\Security` do konstruktora.
    -   W metodzie `provide()`, pobierz aktualnego użytkownika za pomocą `$this->security->getUser()`.
    -   Jeśli użytkownik nie jest zalogowany lub nie jest instancją `User`, zwróć `null`. W przeciwnym razie zwróć obiekt użytkownika.

    ```php
    <?php

    declare(strict_types=1);
    
    namespace App\State;
    
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ProviderInterface;
    use App\Entity\User;
    use Symfony\Bundle\SecurityBundle\Security;
    
    final readonly class MeProvider implements ProviderInterface
    {
        public function __construct(private Security $security)
        {
        }
    
        public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?User
        {
            $user = $this->security->getUser();
    
            if (!$user instanceof User) {
                return null;
            }
    
            return $user;
        }
    }
    ```

3.  **Aktualizacja encji `User`**:
    -   W pliku `src/Entity/User.php`, dodaj nową operację `Get` do atrybutu `#[ApiResource]`.
    -   Skonfiguruj operację, podając `uriTemplate`, `provider`, `output`, `security` oraz opcjonalnie `openapi` dla lepszej dokumentacji.
    
    ```php
    // src/Entity/User.php

    // ...
    use ApiPlatform\Metadata\Get;
    use App\DTO\UserOutput;
    use App\State\MeProvider;

    #[ApiResource(
        operations: [
            new Get(
                uriTemplate: '/users/me',
                provider: MeProvider::class,
                output: UserOutput::class,
                security: "is_granted('ROLE_USER')",
                openapiContext: [
                    'summary' => "Retrieves the currently authenticated user's profile.",
                    'description' => "Retrieves the currently authenticated user's profile.",
                    'responses' => [
                        '200' => [
                            'description' => 'User profile retrieved successfully.',
                        ],
                        '401' => [
                            'description' => 'Unauthorized. Invalid or missing authentication token.',
                        ],
                    ],
                ]
            ),
            new Post(
            // ... reszta operacji
    // ...
    ```

4.  **Napisanie testów API**:
    -   Utwórz nowy plik testowy `tests/Api/UserApiTest.php`.
    -   Dodaj test `testGetMeUnauthorized()` sprawdzający, czy żądanie bez tokenu zwraca `401 Unauthorized`.
    -   Dodaj test `testGetMeSuccessfully()`:
        -   Utwórz użytkownika w bazie danych.
        -   Wykonaj żądanie logowania, aby uzyskać token JWT.
        -   Wykonaj żądanie `GET /api/users/me` z uzyskanym tokenem.
        -   Sprawdź, czy odpowiedź ma status `200 OK`.
        -   Sprawdź, czy struktura i dane JSON w odpowiedzi są zgodne z oczekiwaniami (email, id, etc.).
