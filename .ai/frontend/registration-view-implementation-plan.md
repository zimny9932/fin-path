# Plan implementacji widoku Rejestracja (Registration)

## 1. Przegląd
Widok Rejestracji umożliwia nowym użytkownikom założenie konta w aplikacji FinPath. Składa się z formularza, który zbiera adres e-mail oraz hasło, a następnie, po pomyślnej walidacji i komunikacji z API, automatycznie loguje użytkownika i przekierowuje go do ekranu wstępnej konfiguracji.

## 2. Routing widoku
Widok będzie dostępny pod następującą ścieżką:
- **Ścieżka:** `/register`

Implementacja zostanie zrealizowana poprzez utworzenie pliku `frontend/src/pages/register.astro`.

## 3. Struktura komponentów
Hierarchia komponentów dla widoku Rejestracji będzie następująca:

```
frontend/src/layouts/MainLayout.astro
└── frontend/src/pages/register.astro
    └── frontend/src/components/RegistrationForm.tsx (client:load)
        ├── frontend/src/components/ui/TextInput.tsx (dla e-mail)
        ├── frontend/src/components/ui/PasswordInput.tsx (dla hasła)
        ├── frontend/src/components/ui/PasswordInput.tsx (dla potwierdzenia hasła)
        └── frontend/src/components/ui/Button.tsx (do wysłania formularza)
```
Komponent `Toaster` z `shadcn/ui` zostanie dodany do głównego layoutu (`MainLayout.astro`), aby umożliwić globalne wyświetlanie powiadomień (Toast).

## 4. Szczegóły komponentów

### `RegistrationPage` (`register.astro`)
- **Opis komponentu**: Strona Astro renderująca statyczną zawartość oraz interaktywny formularz rejestracji. Odpowiada za strukturę strony i osadzenie komponentu React.
- **Główne elementy**:
  - `MainLayout.astro` jako główny szablon strony.
  - Nagłówek `<h1>` z tekstem "Stwórz konto".
  - Komponent `<RegistrationForm client:load />` do obsługi interaktywnej części widoku.
- **Obsługiwane interakcje**: Brak (komponent statyczny).
- **Obsługiwana walidacja**: Brak.
- **Typy**: Brak.
- **Propsy**: Brak.

### `RegistrationForm` (`RegistrationForm.tsx`)
- **Opis komponentu**: Kluczowy komponent React, który zarządza stanem formularza, walidacją danych wejściowych, obsługą zdarzeń oraz komunikacją z API backendowym.
- **Główne elementy**:
  - Znacznik `<form>` z obsługą zdarzenia `onSubmit`.
  - `TextInput` dla pola `email`.
  - Dwa komponenty `PasswordInput` dla pól `password` i `passwordConfirmation`.
  - `Button` do przesłania formularza, który będzie wyświetlał stan ładowania.
  - Miejsca na wyświetlanie komunikatów o błędach walidacji przy każdym polu.
- **Obsługiwane interakcje**:
  - `onChange`: Aktualizacja stanu formularza podczas wprowadzania danych przez użytkownika.
  - `onSubmit`: Uruchomienie walidacji po stronie klienta i wysłanie żądania do API.
- **Obsługiwana walidacja**:
  - **E-mail**: Musi być w poprawnym formacie (np. `user@example.com`).
  - **Hasło**: Musi mieć co najmniej 8 znaków.
  - **Potwierdzenie hasła**: Musi być identyczne z hasłem.
- **Typy**: `RegistrationRequestDto`, `RegistrationResponseDto`, `RegistrationFormViewModel`, `RegistrationFormValidationViewModel`.
- **Propsy**: Brak.

### `PasswordInput` (`PasswordInput.tsx`)
- **Opis komponentu**: Komponent do wprowadzania hasła, rozszerzający `TextInput`. Zawiera ikonę do przełączania widoczności wprowadzanego tekstu.
- **Główne elementy**:
  - Komponent `TextInput` z `type="password"`.
  - Ikona "oka" do przełączania typu pola między `password` a `text`.
- **Obsługiwane interakcje**:
  - `onClick` na ikonie przełączania widoczności.
- **Obsługiwana walidacja**: Brak (przekazywana z komponentu nadrzędnego).
- **Typy**: `HTMLInputElement` propsy.
- **Propsy**: Standardowe propsy dla pola input, takie jak `value`, `onChange`, `name`, `id`, `placeholder`, oraz `error?: string`.

## 5. Typy
Do implementacji widoku wymagane będą następujące typy:

- **`RegistrationRequestDto`**: Obiekt wysyłany do API.
  ```typescript
  interface RegistrationRequestDto {
    email: string;
    password: string;
    passwordConfirmation: string;
  }
  ```

- **`RegistrationResponseDto`**: Obiekt otrzymywany z API po pomyślnej rejestracji.
  ```typescript
  interface RegistrationResponseDto {
    token: string;
    refresh_token: string;
  }
  ```

- **`RegistrationFormViewModel`**: Typ dla stanu przechowującego dane formularza.
  ```typescript
  interface RegistrationFormViewModel {
    email: string;
    password: string;
    passwordConfirmation: string;
  }
  ```

- **`RegistrationFormValidationViewModel`**: Typ dla stanu przechowującego błędy walidacji.
  ```typescript
  interface RegistrationFormValidationViewModel {
    email?: string;
    password?: string;
    passwordConfirmation?: string;
    api?: string; // Błąd ogólny zwrócony przez API
  }
  ```

## 6. Zarządzanie stanem
Zarządzanie stanem zostanie zaimplementowane wewnątrz komponentu `RegistrationForm.tsx` przy użyciu hooków React. W celu separacji logiki, zostanie stworzony dedykowany custom hook `useRegistration`.

- **`useRegistration`**:
  - **Cel**: Hermetyzacja logiki związanej ze stanem formularza, walidacją i komunikacją z API.
  - **Zarządzane stany**:
    - `formData (useState<RegistrationFormViewModel>)`: Przechowuje aktualne wartości pól formularza.
    - `errors (useState<RegistrationFormValidationViewModel>)`: Przechowuje komunikaty o błędach walidacji.
    - `isLoading (useState<boolean>)`: Śledzi stan wysyłania formularza do API.
  - **Udostępniane funkcje**:
    - `handleChange`: Funkcja do aktualizacji `formData`.
    - `handleSubmit`: Funkcja do walidacji i wysyłania danych.

## 7. Integracja API
Integracja z backendem będzie realizowana poprzez wysłanie żądania `POST` na endpoint `/api/register`.

- **Endpoint**: `POST /api/register`
- **Typ żądania (Request)**: `RegistrationRequestDto`
- **Typ odpowiedzi (Response)**:
  - **`201 Created`**: `RegistrationResponseDto`. Po otrzymaniu odpowiedzi, tokeny JWT zostaną zapisane w `localStorage`, a użytkownik zostanie przekierowany na stronę `/onboarding`.
  - **`400 Bad Request`**: Odpowiedź z błędem walidacji. Błędy zostaną wyświetlone w formularzu.
  - **`409 Conflict`**: Odpowiedź informująca, że adres e-mail jest już zajęty. Stosowny komunikat zostanie wyświetlony przy polu e-mail.

## 8. Interakcje użytkownika
- **Wprowadzanie danych**: Użytkownik wpisuje e-mail i hasło w odpowiednie pola. Stan komponentu jest na bieżąco aktualizowany.
- **Wysyłanie formularza**: Użytkownik klika przycisk "Zarejestruj się".
- **Przełączanie widoczności hasła**: Użytkownik klika ikonę "oka" w polu hasła, co powoduje pokazanie lub ukrycie wpisanego tekstu.

## 9. Warunki i walidacja
Walidacja będzie przeprowadzana po stronie klienta przed wysłaniem żądania do API.

- **Pole `email`**:
  - **Warunek**: Musi być prawidłowym adresem e-mail.
  - **Komunikat**: "Proszę podać poprawny adres e-mail."
- **Pole `password`**:
  - **Warunek**: Musi zawierać co najmniej 8 znaków.
  - **Komunikat**: "Hasło musi mieć co najmniej 8 znaków."
- **Pole `passwordConfirmation`**:
  - **Warunek**: Wartość musi być identyczna jak w polu `password`.
  - **Komunikat**: "Hasła muszą być identyczne."

Jeśli walidacja nie powiedzie się, formularz nie zostanie wysłany, a odpowiednie komunikaty pojawią się pod polami.

## 10. Obsługa błędów
- **Błędy walidacji klienta**: Komunikaty o błędach są wyświetlane bezpośrednio pod polami formularza, blokując wysyłkę.
- **Błąd zajętego e-maila (`409 Conflict`)**: Po otrzymaniu błędu z API, pod polem e-mail pojawi się komunikat "Ten adres e-mail jest już zajęty.", a także zostanie wyświetlony ogólny Toast z informacją o niepowodzeniu.
- **Inne błędy API (np. `400`, `500`)**: W przypadku innych błędów serwera lub problemów z siecią, pod formularzem zostanie wyświetlony ogólny komunikat błędu (np. "Wystąpił nieoczekiwany błąd. Prosimy spróbować ponownie."), a także zostanie pokazany Toast.

## 11. Kroki implementacji
1.  **Utworzenie strony**: Stworzyć plik `frontend/src/pages/register.astro` i dodać podstawową strukturę z `MainLayout`.
2.  **Definicja typów**: Zdefiniować wszystkie wymagane typy (`DTO`, `ViewModel`) w dedykowanym pliku, np. `frontend/src/types/auth.ts`.
3.  **Stworzenie komponentów UI**: Jeśli nie istnieją, stworzyć reużywalne komponenty `TextInput.tsx` i `PasswordInput.tsx` w oparciu o bibliotekę `shadcn/ui`.
4.  **Implementacja `RegistrationForm`**:
    -   Zbudować strukturę JSX formularza.
    -   Zaimplementować custom hook `useRegistration` do zarządzania logiką stanu i walidacji.
5.  **Integracja API**:
    -   Napisać funkcję serwisu API (np. w `frontend/src/lib/api.ts`), która będzie odpowiedzialna za wysyłanie żądania `POST /api/register`.
    -   Zintegrować funkcję z hookiem `useRegistration`.
6.  **Obsługa tokenów i przekierowania**:
    -   Stworzyć moduł `frontend/src/lib/auth.ts` do obsługi zapisu i odczytu tokenów z `localStorage`.
    -   Zaimplementować logikę zapisu tokenów i przekierowania po pomyślnej rejestracji.
7.  **Obsługa błędów i powiadomień**:
    -   Dodać komponent `Toaster` do `MainLayout.astro`.
    -   Zaimplementować wyświetlanie powiadomień Toast dla sukcesu i porażki.
    -   Upewnić się, że wszystkie komunikaty o błędach są poprawnie wyświetlane w interfejsie użytkownika.
8.  **Stylowanie**: Dopracować wygląd formularza i komunikatów o błędach przy użyciu Tailwind CSS, zgodnie z systemem designu aplikacji.
