# API Endpoint Implementation Plan: PATCH /api/users/me/onboarding

## 1. Przegląd punktu końcowego
Ten punkt końcowy umożliwia zalogowanemu użytkownikowi jednorazowe ustawienie dnia rozpoczęcia cyklu rozliczeniowego (`billingCycleStartDay`). Jest to kluczowy krok w procesie onboardingu, który pozwala na personalizację działania aplikacji do cyklu finansowego użytkownika. Operacja ta może być wykonana tylko raz.

## 2. Szczegóły żądania
- **Metoda HTTP**: `PATCH`
- **Struktura URL**: `/api/users/me/onboarding`
- **Parametry**: Brak parametrów w URL.
- **Request Body**:
  ```json
  {
    "billingCycleStartDay": 25
  }
  ```
  - `billingCycleStartDay`: `integer`, wymagane. Wartość musi znajdować się w przedziale od 1 do 31.

## 3. Wykorzystywane typy
- **`App\DTO\OnboardingInput`** (do utworzenia): DTO dla danych wejściowych, zawierające pole `billingCycleStartDay` wraz z regułami walidacji.
- **`App\DTO\UserOutput`** (istniejący): DTO używane do serializacji odpowiedzi, zawierające zaktualizowane dane użytkownika.

## 4. Szczegóły odpowiedzi
- **Odpowiedź sukcesu (200 OK)**: Zwraca zaktualizowany obiekt użytkownika w formacie `UserOutput`.
  ```json
  {
    "id": "018f2b25-a7b7-786e-8b9f-6e8e819f0f7a",
    "email": "user@example.com",
    "billingCycleStartDay": 25,
    "onboardingCompleted": true
  }
  ```
- **Odpowiedzi błędów**:
  - `400 Bad Request`: Błędy walidacji lub próba ponownego wykonania onboardingu.
  - `401 Unauthorized`: Użytkownik nie jest uwierzytelniony.
  - `500 Internal Server Error`: Wewnętrzne błędy serwera.

## 5. Przepływ danych
1. Użytkownik wysyła żądanie `PATCH` na adres `/api/users/me/onboarding` z `billingCycleStartDay` w ciele żądania.
2. API Platform deserializuje ciało żądania do obiektu DTO `OnboardingInput` i uruchamia walidację (wartość w zakresie 1-31).
3. Dedykowany `State Processor` (`OnboardingProcessor`) jest uruchamiany.
4. Procesor pobiera aktualnie zalogowanego użytkownika z serwisu `Security`.
5. Sprawdza, czy pole `billingCycleStartDay` w encji `User` jest już ustawione. Jeśli tak, rzuca wyjątek `OnboardingAlreadyCompletedException`.
6. Jeśli pole jest puste, procesor aktualizuje encję `User`, ustawiając nową wartość `billingCycleStartDay`.
7. `EntityManager` zapisuje zmiany w bazie danych.
8. API Platform serializuje zaktualizowaną encję `User` do formatu `UserOutput` i zwraca odpowiedź `200 OK`.

## 6. Względy bezpieczeństwa
- **Uwierzytelnianie**: Dostęp do punktu końcowego jest chroniony i wymaga, aby użytkownik był zalogowany. Zostanie to zapewnione przez `security: "is_granted('ROLE_USER')"`.
- **Autoryzacja**: Logika operacji jest ograniczona do modyfikacji wyłącznie danych zalogowanego użytkownika. Identyfikator użytkownika jest pobierany bezpośrednio z kontekstu bezpieczeństwa, co eliminuje ryzyko modyfikacji danych innego konta.

## 7. Obsługa błędów
- **`OnboardingAlreadyCompletedException`** (do utworzenia): Niestandardowy wyjątek rzucany, gdy użytkownik próbuje ponownie ustawić `billingCycleStartDay`. Zostanie zmapowany na kod statusu `400 Bad Request`.
- **Błędy walidacji**: Symfony Validator obsłuży walidację DTO (`OnboardingInput`). W przypadku niepowodzenia (np. wartość poza zakresem, brak wartości) zostanie zwrócony standardowy błąd `400 Bad Request` z listą naruszeń.

## 8. Rozważania dotyczące wydajności
Operacja jest prosta (aktualizacja jednego pola w jednym wierszu) i będzie wykonywana tylko raz na użytkownika. Nie przewiduje się żadnych problemów z wydajnością. Indeks na kluczu głównym (`id`) użytkownika jest wystarczający.

## 9. Etapy wdrożenia
1. **Utworzenie DTO**: Stworzyć plik `src/DTO/OnboardingInput.php` z publiczną właściwością `billingCycleStartDay` i adnotacjami walidacyjnymi `#[Assert\NotBlank]` oraz `#[Assert\Range(min: 1, max: 31)]`.
2. **Utworzenie wyjątku**: Stworzyć klasę wyjątku `src/Exception/OnboardingAlreadyCompletedException.php`, która rozszerza `\Exception`.
3. **Utworzenie Procesora**: Stworzyć klasę `src/State/OnboardingProcessor.php` implementującą `ApiPlatform\State\ProcessorInterface`.
    - Wstrzyknąć do konstruktora `ProcessorInterface $persistProcessor`, `Security` oraz `EntityManagerInterface`.
    - Zaimplementować metodę `process()`:
        - Pobranie użytkownika z `Security::getUser()`.
        - Sprawdzenie, czy `$user->getBillingCycleStartDay()` nie jest `null`. Jeśli tak, rzucić `OnboardingAlreadyCompletedException`.
        - Ustawienie nowej wartości: `$user->setBillingCycleStartDay($data->billingCycleStartDay)`.
        - Wywołanie `EntityManager::flush()` w celu zapisania zmian.
        - Zwrócenie zaktualizowanej encji `$user`.
4. **Aktualizacja encji `User`**: W pliku `src/Entity/User.php`, w atrybucie `#[ApiResource]`, dodać nową operację `PATCH`.
    ```php
    new Patch(
        uriTemplate: '/users/me/onboarding',
        security: "is_granted('ROLE_USER')",
        input: OnboardingInput::class,
        output: UserOutput::class,
        processor: OnboardingProcessor::class,
        exceptionToStatus: [OnboardingAlreadyCompletedException::class => 400]
    ),
    ```
5. **Napisanie testu API**: W katalogu `tests/Api/` stworzyć nowy test, który weryfikuje:
    - Pomyślne ustawienie `billingCycleStartDay` i kod `200 OK`.
    - Próbę dostępu przez nieuwierzytelnionego użytkownika (oczekiwany kod `401 Unauthorized`).
    - Próbę ponownego ustawienia wartości (oczekiwany kod `400 Bad Request`).
    - Przesłanie nieprawidłowych danych (np. wartość "abc", 0, 32) i weryfikację kodu `400 Bad Request`.
