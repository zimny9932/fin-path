# Plan implementacji widoku Logowanie

## 1. Przegląd
Widok logowania umożliwia zarejestrowanym użytkownikom uwierzytelnienie się w aplikacji. Celem jest zapewnienie bezpiecznego i prostego procesu logowania, który po pomyślnym zakończeniu przekierowuje użytkownika do głównego panelu aplikacji (dashboardu). Widok będzie obsługiwał walidację formularza po stronie klienta oraz komunikację z API w celu weryfikacji poświadczeń.

## 2. Routing widoku
Widok będzie dostępny pod następującą ścieżką:
- **Ścieżka:** `/login`
- **Plik:** `src/pages/login.astro`

## 3. Struktura komponentów
Hierarchia komponentów dla widoku logowania będzie następująca:

```
- LoginPage.astro
  - Layout.astro
    - LoginForm.tsx (client:load)
      - form
        - TextInput (dla adresu e-mail)
        - PasswordInput (dla hasła)
        - Button (przycisk wysłania formularza)
      - Toast (do wyświetlania błędów)
```

## 4. Szczegóły komponentów
### `LoginPage` (`src/pages/login.astro`)
- **Opis komponentu:** Główny plik strony Astro, który definiuje strukturę strony `/login`. Jego zadaniem jest renderowanie komponentu `Layout` oraz osadzenie w nim interaktywnego formularza `LoginForm`.
- **Główne elementy:** `Layout`, `LoginForm`.
- **Obsługiwane interakcje:** Brak.
- **Obsługiwana walidacja:** Brak.
- **Typy:** Brak.
- **Propsy:** Brak.

### `LoginForm` (`src/components/LoginForm.tsx`)
- **Opis komponentu:** Interaktywny komponent React odpowiedzialny za całą logikę formularza logowania. Zarządza stanem pól, obsługuje walidację, wysyłkę danych do API, a także wyświetla komunikaty o błędach i stanie ładowania.
- **Główne elementy:** `form`, `TextInput`, `PasswordInput`, `Button`, `Toast`.
- **Obsługiwane interakcje:**
    - Wprowadzanie tekstu w polach `email` i `password`.
    - Wysłanie formularza za pomocą przycisku.
- **Obsługiwana walidacja:**
    - `email`:
        - Pole nie może być puste.
        - Wartość musi być poprawnym formatem adresu e-mail.
    - `password`:
        - Pole nie może być puste.
- **Typy:** `LoginRequestDTO`, `LoginFormViewModel`.
- **Propsy:** Brak.

## 5. Typy
Do implementacji widoku wymagane będą następujące typy:

### `LoginRequestDTO`
Opisuje strukturę danych wysyłanych do endpointu API.
```typescript
interface LoginRequestDTO {
  email: string;
  password: string;
}
```

### `LoginResponseDTO`
Opisuje strukturę danych otrzymywanych z API po pomyślnym zalogowaniu.
```typescript
interface LoginResponseDTO {
  token: string;
  refresh_token: string;
}
```

### `LoginFormViewModel`
Opisuje stan formularza w komponencie `LoginForm`.
```typescript
interface LoginFormViewModel {
  email: string;
  password: string;
}
```

## 6. Zarządzanie stanem
Logika i stan formularza zostaną wyizolowane w dedykowanym customowym hooku `useLoginForm`, aby utrzymać komponent `LoginForm` czystym i czytelnym.

### `useLoginForm` (`src/lib/hooks/useLoginForm.ts`)
- **Cel:** Zarządzanie danymi formularza, stanem ładowania, walidacją oraz obsługą błędów API.
- **Zarządzany stan:**
    - `formData: LoginFormViewModel`: Przechowuje aktualne wartości pól formularza.
    - `errors: Record<string, string>`: Przechowuje błędy walidacji dla poszczególnych pól.
    - `isLoading: boolean`: Informuje, czy trwa proces wysyłania danych do API.
    - `apiError: string | null`: Przechowuje komunikat błędu zwrócony przez API.
- **Zwracane wartości i funkcje:**
    - `formData`, `errors`, `isLoading`, `apiError`.
    - `handleChange`: Funkcja do aktualizacji stanu `formData`.
    - `handleSubmit`: Funkcja do walidacji i wysłania formularza.

## 7. Integracja API
Integracja z backendem będzie realizowana poprzez wysłanie zapytania do poniższego endpointu:

- **Endpoint:** `POST /api/login`
- **Typ żądania (`Request Body`):** `LoginRequestDTO`
- **Typ odpowiedzi sukcesu (`Success Response`):** `LoginResponseDTO`

Po pomyślnym zalogowaniu (status `200 OK`), otrzymane tokeny (`token` i `refresh_token`) zostaną zapisane w `localStorage`, a użytkownik zostanie przekierowany na stronę `/dashboard`.

## 8. Interakcje użytkownika
- **Użytkownik wprowadza dane:** Stan `formData` jest na bieżąco aktualizowany.
- **Użytkownik klika "Zaloguj się" z niepoprawnymi danymi:** Walidacja po stronie klienta jest uruchamiana, a komunikaty o błędach wyświetlane są przy odpowiednich polach. Zapytanie do API nie jest wysyłane.
- **Użytkownik klika "Zaloguj się" z poprawnymi danymi:**
    1. Stan `isLoading` zmienia się na `true`.
    2. Przycisk logowania staje się nieaktywny i wyświetla wskaźnik ładowania.
    3. Zapytanie `POST` jest wysyłane na adres `/api/login`.
- **Logowanie w API kończy się sukcesem:** Użytkownik jest przekierowany na stronę `/dashboard`.
- **Logowanie w API kończy się błędem:** Stan `isLoading` zmienia się na `false`, a komunikat błędu jest wyświetlany za pomocą komponentu `Toast`.

## 9. Warunki i walidacja
- **Email:**
    - **Warunek:** Puste pole.
    - **Komunikat:** "Pole jest wymagane".
    - **Komponent:** `LoginForm`.
    - **Wpływ na UI:** Wyświetlenie komunikatu błędu pod polem, blokada wysłania formularza.
- **Email:**
    - **Warunek:** Nieprawidłowy format adresu.
    - **Komunikat:** "Nieprawidłowy format e-mail".
    - **Komponent:** `LoginForm`.
    - **Wpływ na UI:** Wyświetlenie komunikatu błędu pod polem, blokada wysłania formularza.
- **Hasło:**
    - **Warunek:** Puste pole.
    - **Komunikat:** "Pole jest wymagane".
    - **Komponent:** `LoginForm`.
    - **Wpływ na UI:** Wyświetlenie komunikatu błędu pod polem, blokada wysłania formularza.

## 10. Obsługa błędów
- **Nieprawidłowe dane logowania (Błąd 401 Unauthorized):**
    - **Obsługa:** Wyświetlenie komunikatu `Toast` o treści: "Nieprawidłowy e-mail lub hasło".
- **Błąd walidacji po stronie serwera (Błąd 400 Bad Request):**
    - **Obsługa:** Wyświetlenie generycznego komunikatu `Toast`: "Wystąpił błąd. Sprawdź poprawność wprowadzonych danych.".
- **Błąd serwera lub problem z siecią (Błąd 5xx lub brak połączenia):**
    - **Obsługa:** Wyświetlenie generycznego komunikatu `Toast`: "Wystąpił nieoczekiwany błąd. Prosimy spróbować ponownie.".

## 11. Kroki implementacji
1.  Utworzenie pliku strony `src/pages/login.astro` i osadzenie w nim komponentu `LoginForm` z dyrektywą `client:load`.
2.  Stworzenie interfejsów typów: `LoginRequestDTO`, `LoginResponseDTO`, `LoginFormViewModel` w pliku `src/types.ts`.
3.  Implementacja customowego hooka `useLoginForm.ts` w `src/lib/hooks/`, zawierającego całą logikę zarządzania stanem, walidacji i komunikacji z API.
4.  Stworzenie komponentu `LoginForm.tsx` w `src/components/`, który będzie wykorzystywał hook `useLoginForm` i renderował interfejs użytkownika za pomocą komponentów z `Shadcn/ui` (`TextInput`, `PasswordInput`, `Button`).
5.  Implementacja logiki zapisu tokenów w `localStorage` oraz przekierowania po pomyślnym logowaniu.
6.  Dodanie obsługi błędów i wyświetlanie komunikatów za pomocą komponentu `Toast`.
7.  Stylizacja komponentów przy użyciu `Tailwind CSS`, aby zapewnić spójność wizualną z resztą aplikacji.
8.  Dodanie testów w celu weryfikacji poprawności działania formularza, walidacji i obsługi odpowiedzi z API.
