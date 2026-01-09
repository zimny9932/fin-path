# Plan implementacji widoku Onboarding

## 1. Przegląd
Widok Onboarding jest pierwszym ekranem, który widzi nowy użytkownik po pomyślnej rejestracji i zalogowaniu. Jego jedynym celem jest jednorazowa konfiguracja kluczowego ustawienia dla konta: dnia rozpoczęcia miesięcznego cyklu rozliczeniowego. Proces ten jest obowiązkowy i zaprojektowany tak, aby był jak najprostszy i najszybszy, składając się z jednego kroku.

## 2. Routing widoku
Widok będzie dostępny pod ścieżką `/onboarding`. Dostęp do tej ścieżki powinien być chroniony przez middleware, aby zapewnić, że jest ona dostępna wyłącznie dla uwierzytelnionych użytkowników, którzy nie ukończyli jeszcze tego kroku konfiguracji. Po pomyślnym zakończeniu, użytkownik zostanie automatycznie przekierowany do widoku tworzenia pierwszego budżetu (`/budget/new`).

## 3. Struktura komponentów
Struktura będzie opierać się na architekturze "Astro Islands". Strona Astro będzie odpowiedzialna za layout, podczas gdy interaktywny formularz będzie komponentem Reactowym.

```
/frontend/src/pages/onboarding.astro
└── /frontend/src/layouts/MainLayout.astro
    └── /frontend/src/components/OnboardingForm.tsx (client:load)
        ├── h1 (Tytuł "Konfiguracja konta")
        ├── p (Opis wyjaśniający cel)
        ├── form
        │   ├── Select (z Shadcn/ui, do wyboru dnia)
        │   └── Button (z Shadcn/ui, do zapisu)
        └── p (Element na komunikaty o błędach)
```

## 4. Szczegóły komponentów
### `OnboardingForm.tsx`
- **Opis komponentu**: Interaktywny komponent React, który renderuje formularz, zarządza jego stanem (wybrany dzień, status ładowania, błędy) oraz obsługuje komunikację z API w celu zapisu ustawienia użytkownika.
- **Główne elementy**:
  - Formularz (`<form>`).
  - Etykieta (`<label>`) dla pola wyboru.
  - Komponent `Select` z `Shadcn/ui` do wyboru dnia.
  - Komponent `Button` z `Shadcn/ui` do wysłania formularza.
  - Paragraf (`<p>`) do warunkowego wyświetlania komunikatów o błędach.
- **Obsługiwane interakcje**:
  - `onValueChange` na komponencie `Select`: aktualizuje wewnętrzny stan komponentu o wybrany dzień.
  - `onSubmit` na elemencie `<form>`: uruchamia logikę walidacji i wysyłania danych do API.
- **Obsługiwana walidacja**:
  - Przycisk "Zapisz" jest nieaktywny (`disabled`), dopóki użytkownik nie wybierze dnia z listy.
- **Typy**: `OnboardingRequestDTO`, `OnboardingFormViewModel`.
- **Propsy**: Brak. Komponent jest w pełni samodzielny.

## 5. Typy
### `OnboardingRequestDTO`
Opisuje strukturę danych wysyłaną w ciele żądania `PATCH` do API.
```typescript
interface OnboardingRequestDTO {
  /**
   * Dzień rozpoczęcia cyklu rozliczeniowego, wybrany przez użytkownika (liczba od 1 do 31).
   */
  billingCycleStartDay: number;
}
```

### `OnboardingFormViewModel`
Opisuje wewnętrzny stan komponentu `OnboardingForm`.
```typescript
interface OnboardingFormViewModel {
  /**
   * Wybrany przez użytkownika dzień lub null, jeśli nic nie zostało wybrane.
   */
  selectedDay: number | null;
  /**
   * Flaga informująca, czy trwa proces komunikacji z API.
   */
  isLoading: boolean;
  /**
   * Komunikat błędu do wyświetlenia w przypadku niepowodzenia operacji.
   */
  error: string | null;
}
```

## 6. Zarządzanie stanem
Zarządzanie stanem będzie w całości zlokalizowane w komponencie `OnboardingForm.tsx` przy użyciu wbudowanych hooków Reacta. Nie ma potrzeby stosowania zewnętrznych bibliotek do zarządzania stanem ani tworzenia skomplikowanych hooków niestandardowych.

- `useState<number | null>(null)` do przechowywania wybranego dnia.
- `useState<boolean>(false)` do zarządzania stanem ładowania podczas wysyłania formularza.
- `useState<string | null>(null)` do przechowywania ewentualnych komunikatów o błędach z API.

## 7. Integracja API
Integracja z API będzie realizowana poprzez wywołanie endpointu `PATCH /api/users/me/onboarding`.

- **Trigger**: Wysłanie formularza w komponencie `OnboardingForm`.
- **Metoda**: `PATCH`.
- **URL**: `/api/users/me/onboarding`.
- **Nagłówki**:
  - `Content-Type: application/json`
  - `Authorization: Bearer <JWT_TOKEN>` (token musi być pobrany z mechanizmu uwierzytelniania).
- **Ciało żądania (Request Body)**: Obiekt zgodny z typem `OnboardingRequestDTO`.
  ```json
  {
    "billingCycleStartDay": 25
  }
  ```
- **Obsługa odpowiedzi**:
  - `200 OK`: Przekierowanie użytkownika na stronę `/budget/new`.
  - `400 Bad Request`: Wyświetlenie komunikatu o błędzie w interfejsie (np. "Ustawienie zostało już skonfigurowane.").
  - `401 Unauthorized`: Przekierowanie użytkownika na stronę logowania (`/login`).
  - `5xx Server Error`: Wyświetlenie generycznego komunikatu o błędzie serwera.

## 8. Interakcje użytkownika
1.  **Wejście na stronę**: Użytkownik widzi formularz z nieaktywnym przyciskiem zapisu.
2.  **Wybór dnia**: Użytkownik klika w pole `Select` i wybiera jeden z dni (1-31).
3.  **Aktywacja przycisku**: Po wybraniu dnia przycisk "Zapisz" staje się aktywny.
4.  **Wysłanie formularza**: Użytkownik klika przycisk "Zapisz".
5.  **Feedback**: Przycisk przechodzi w stan ładowania (np. pokazuje spinner).
6.  **Wynik operacji**:
    - **Sukces**: Użytkownik zostaje przekierowany na kolejną stronę.
    - **Błąd**: Stan ładowania kończy się, a pod formularzem pojawia się komunikat błędu.

## 9. Warunki i walidacja
- **Warunek**: Wybór dnia jest obowiązkowy.
- **Weryfikacja**: Stan przycisku "Zapisz" jest uzależniony od stanu `selectedDay`. Jeśli `selectedDay` jest `null`, przycisk ma atrybut `disabled`. Zapobiega to wysłaniu formularza bez wymaganych danych.
- **Weryfikacja zakresu (1-31)**: Jest zapewniona przez sam komponent `Select`, który będzie zawierał tylko predefiniowane, prawidłowe opcje.

## 10. Obsługa błędów
- **Błąd walidacji (400)**: Komponent `OnboardingForm` przechwytuje błąd, ustawia stan `error` na odpowiedni komunikat (np. "Nie udało się zapisać ustawień. Spróbuj ponownie.") i wyświetla go użytkownikowi.
- **Problem z uwierzytelnieniem (401)**: Globalny mechanizm obsługi API (lub dedykowana logika w komponencie) powinien przechwycić ten status i przekierować użytkownika na stronę logowania.
- **Błąd serwera (5xx)**: Komponent wyświetla generyczny komunikat, np. "Wystąpił błąd serwera. Prosimy spróbować ponownie za chwilę.".
- **Błąd sieci**: W bloku `catch` wywołania `fetch` należy obsłużyć błędy sieciowe (np. brak połączenia z internetem) i wyświetlić stosowny komunikat.

## 11. Kroki implementacji
1.  Utworzenie pliku strony Astro: `frontend/src/pages/onboarding.astro`. Wewnątrz należy zaimplementować podstawową strukturę strony, używając istniejącego layoutu.
2.  Utworzenie pliku komponentu React: `frontend/src/components/OnboardingForm.tsx`.
3.  Zdefiniowanie typów `OnboardingRequestDTO` i `OnboardingFormViewModel` w odpowiednim pliku (np. `frontend/src/types.ts` lub lokalnie w komponencie).
4.  W komponencie `OnboardingForm.tsx`:
    - Zaimplementować logikę stanu (dla `selectedDay`, `isLoading`, `error`).
    - Zbudować interfejs przy użyciu komponentów `Select` i `Button` z biblioteki Shadcn/ui.
    - Wypełnić komponent `Select` opcjami od 1 do 31.
    - Zaimplementować logikę `onSubmit`, która będzie wywoływać API.
    - Dodać obsługę stanu ładowania oraz wyświetlanie komunikatów o błędach.
5.  Osadzenie komponentu `OnboardingForm.tsx` na stronie `onboarding.astro` z dyrektywą `client:load`.
6.  Zaimplementowanie logiki przekierowania po pomyślnym zapisaniu danych (do `/budget/new`).
7.  Sprawdzenie i ewentualne dostosowanie routingu/middleware, aby upewnić się, że strona jest dostępna tylko dla nowych użytkowników.
8.  Ręczne przetestowanie wszystkich ścieżek interakcji użytkownika, w tym przypadków błędów.


