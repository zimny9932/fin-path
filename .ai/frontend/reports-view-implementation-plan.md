# Plan implementacji widoku Raporty

## 1. Przegląd
Widok `Raporty` (`/reports`) prezentuje strukturę wydatków użytkownika w bieżącym lub wybranym zakresie dat. Łączy wykres udziału kategorii z tabelą procentową, obsługuje stany ładowania, brak danych i błędów, a dane są ograniczone do zalogowanego użytkownika.

## 2. Routing widoku
Ścieżka: `/reports` (publicznie dostępna wyłącznie po uwierzytelnieniu; ochrona routingu zgodna z istniejącą warstwą auth).

## 3. Struktura komponentów
- `ReportsPage` (React, client-only)
  - `CycleSwitcher` (reuse istniejącego komponentu przełączania cyklu / zakresu dat)
  - `Chart` (Pie/Bar, np. Recharts/Chart.js; lazy load)
  - `SpendingTable`
  - `EmptyState`
  - `SkeletonLoader` (placeholder na czas fetchu)
  - `ErrorBanner` (opcjonalny wrapper na błędy)

## 4. Szczegóły komponentów
### ReportsPage
- Opis: Kontener widoku; odpowiada za pobranie danych, zarządzanie stanem (loading/error/data/range), render warstw UI (switcher, wykres, tabela, empty/error/skeleton).
- Główne elementy: wrapper layoutu, sekcja nagłówka z tytułem + `CycleSwitcher`, sekcja treści z `Chart` i `SpendingTable`, bloki `EmptyState`/`ErrorBanner`/`SkeletonLoader`.
- Obsługiwane interakcje: zmiana zakresu dat (`onRangeChange`), przełączanie typu wykresu (opcjonalny toggle pie/bar), retry przy błędzie, CTA w empty state.
- Obsługiwana walidacja: weryfikacja dat przed fetchem (`YYYY-MM-DD`, `startDate <= endDate`); blokada fetchu przy niespełnieniu; obsługa pustej odpowiedzi jako stan „brak danych”.
- Typy: `SpendingCategoryReportDTO`, `ReportsViewModel`, `Range`, `ChartDatum`, `TableRow`.
- Propsy: brak (widok stronowy); wewnętrznie korzysta z hooków.

### CycleSwitcher
- Opis: Kontrola wyboru zakresu raportu (domyślnie bieżący cykl; opcjonalne szybkie zakresy np. bieżący/ostatni miesiąc).
- Główne elementy: dropdown/segmented control, pola dat (jeśli istnieją), przycisk “Zastosuj”.
- Obsługiwane interakcje: wybór predefiniowanego zakresu, ręczny wybór dat, submit `onRangeChange(range)`.
- Obsługiwana walidacja: format dat, `startDate <= endDate`; dezaktywacja przycisku zastosuj, komunikat inline przy błędzie.
- Typy: `Range`.
- Propsy: `value: Range`, `onChange(range)`, `isLoading?`, opcjonalnie `minDate/maxDate`.

### Chart
- Opis: Wykres udziału wydatków per główna kategoria; tryb Pie (domyślny) z opcją Bar (desktop).
- Główne elementy: `<ResponsiveContainer>`, `<PieChart>/<BarChart>`, legenda, tooltip.
- Obsługiwane interakcje: hover (tooltip), klik/legend highlight, opcjonalny toggle pie/bar.
- Obsługiwana walidacja: render tylko przy `data.length > 0` i `value > 0`; fallback do `EmptyState` jeśli brak danych.
- Typy: `ChartDatum`.
- Propsy: `data: ChartDatum[]`, `mode: 'pie' | 'bar'`, `onModeChange?`, `isLoading?`.

### SpendingTable
- Opis: Tabela udziałów per kategoria, z kwotą i procentem, posortowana malejąco po kwocie.
- Główne elementy: tabela, nagłówki (Kategoria, Kwota, Udział), wiersze danych, opcjonalny pasek postępu/percent bar.
- Obsługiwane interakcje: sortowanie (opcjonalne), copy-to-clipboard kwoty (opcjonalne), scroll w małych ekranach.
- Obsługiwana walidacja: kwoty ≥ 0, procenty 0–100; brak danych -> nie renderuj (obsłużone w `ReportsPage`).
- Typy: `TableRow`.
- Propsy: `rows: TableRow[]`, `currency: string`, `isLoading?`.

### EmptyState
- Opis: Informacja o braku danych z CTA (np. „Dodaj pierwszą transakcję”).
- Główne elementy: ikonka/ilustracja, tekst, przycisk CTA do dodania transakcji lub przejścia do budżetu.
- Obsługiwane interakcje: klik CTA -> nawigacja do `/transactions` lub `/budget`.
- Obsługiwana walidacja: brak.
- Typy: wbudowane.
- Propsy: `title`, `description`, `ctaLabel`, `onCtaClick`.

### SkeletonLoader
- Opis: Placeholder layoutu (karta wykresu + tabela) na czas fetchu.
- Główne elementy: skeleton koła/słupków, skeleton wierszy tabeli.
- Obsługiwane interakcje: brak.
- Obsługiwana walidacja: brak.
- Typy: wbudowane.
- Propsy: brak lub `variant`.

### ErrorBanner
- Opis: Pasek błędu z akcją retry.
- Główne elementy: alert (Shadcn/ui), komunikat, przycisk „Spróbuj ponownie”.
- Obsługiwane interakcje: klik retry -> refetch.
- Obsługiwana walidacja: brak.
- Typy: wbudowane.
- Propsy: `message`, `onRetry`.

## 5. Typy
- `type CurrencyCode = 'PLN' | string`
- `type MoneyDTO = { amount: number; currency: CurrencyCode }` (przyjmuje integer z API; prezentacja przez formatter dzielący do zł).
- `type Range = { startDate?: string; endDate?: string }`
- `type SpendingCategoryReportDTO = { mainCategory: string; totalAmount: MoneyDTO; percentageOfTotal: number }`
- `type ReportsViewModel = { range: Range; items: SpendingCategoryReportDTO[]; totalSpent: MoneyDTO; hasData: boolean }`
- `type ChartDatum = { label: string; value: number; percentage: number }`
- `type TableRow = { category: string; amount: number; currency: CurrencyCode; percentage: number }`

## 6. Zarządzanie stanem
- Lokalny stan w `ReportsPage`: `range`, `data`, `loading`, `error`, `viewMode` (pie/bar).
- Custom hook `useSpendingByCategory(range)`:
  - odpowiedzialny za fetch, walidację zakresu, mapowanie do `ChartDatum`/`TableRow`, zarządzanie `loading`/`error`.
  - zwraca `{ data, rows, chartData, totalSpent, loading, error, refetch }`.
- Integracja z globalnym kontekstem auth/fetch (reuse istniejącego klienta API, np. wrappera fetch z tokenem).
- Memoizacja wyników dla identycznych zakresów (opcjonalnie).

## 7. Integracja API
- Endpoint: `GET /api/reports/spending-by-category?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD`
- Request typy: `Range` (oba opcjonalne; brak = bieżący cykl po stronie backendu).
- Response typy: `SpendingCategoryReportDTO[]`
- Obsługa:
  - Walidacja zakresu przed wywołaniem; jeśli brak dat, wysyłamy bez parametrów (backend domyślnie używa bieżącego cyklu).
  - Mapowanie: `totalAmount.amount` (minor units) -> format waluty, `percentageOfTotal` -> 0–100.
  - Pusta tablica => `EmptyState`, brak błędu.
  - Błędy 400/401/5xx -> `ErrorBanner` + retry / redirect na login przy 401.

## 8. Interakcje użytkownika
- Zmiana zakresu w `CycleSwitcher` → walidacja → refetch → odświeżenie wykresu i tabeli.
- Hover/klik w wykresie → tooltip/akcent segmentu (UI-only).
- Sortowanie tabeli (opcjonalne) → aktualizacja układu wierszy.
- Klik CTA w `EmptyState` → nawigacja do dodania transakcji/budżetu.
- Klik „Spróbuj ponownie” w `ErrorBanner` → refetch.

## 9. Warunki i walidacja
- `startDate` i `endDate` w formacie `YYYY-MM-DD`; `startDate <= endDate`; brak → użyj domyślnego zakresu backendu.
- Procenty w odpowiedzi powinny być w zakresie 0–100; odchylenie >0.5 pp -> zaokrąglenie do 1 miejsca po przecinku.
- Kwoty nieujemne; prezentacja waluty przez wspólny formatter (sprawdź util w `frontend/src/lib` lub dodać `formatMoney`).
- Dane renderowane tylko gdy `data.length > 0` i `!loading && !error`.
- Autoryzacja: wywołanie przez klienta z tokenem; przy 401 redirect/doładowanie sesji.

## 10. Obsługa błędów
- 400 (zły zakres dat): pokaż komunikat walidacyjny przy kontrolce zakresu; nie wyświetlaj pustej tabeli.
- 401: przekierowanie na login / odświeżenie tokena, komunikat „Sesja wygasła”.
- 5xx / network timeout: `ErrorBanner` z możliwością ponów próbę.
- Pusta odpowiedź: `EmptyState` z CTA.
- Nieoczekiwany shape: fallback komunikat, log do konsoli/Sentry (jeśli skonfigurowane).

## 11. Kroki implementacji
1. Dodać typy do `frontend/src/types.ts` (MoneyDTO, Range, SpendingCategoryReportDTO, ChartDatum, TableRow, ReportsViewModel).
2. Utworzyć klienta `frontend/src/lib/api/reports.ts` z funkcją `getSpendingByCategory(range?: Range)`.
3. Dodać hook `useSpendingByCategory` w `frontend/src/lib/hooks/useSpendingByCategory.ts` obsługujący walidację zakresu, fetch, mapowanie i stany.
4. Zaimplementować `CycleSwitcher` (lub rozbudować istniejący) o walidację dat i `onRangeChange`.
5. Zaimplementować `Chart` (React, lazy, Recharts/Chart.js) z trybem pie/bar i tooltipami.
6. Zaimplementować `SpendingTable` z sortowaniem malejącym po kwocie, paskiem udziału i formatowaniem waluty.
7. Dodać `EmptyState`, `SkeletonLoader`, `ErrorBanner` (reuse komponentów z design systemu jeśli istnieją).
8. Stworzyć stronę `frontend/src/pages/reports.astro` lub React route (zgodnie z routerem) renderującą `ReportsPage`; podpiąć ochronę auth.
9. Połączyć komponenty w `ReportsPage`: zarządzanie stanem/hookiem, wyświetlanie stanów loading/empty/error, przekazywanie propsów do dzieci.
10. Zapewnić odświeżanie danych po dodaniu transakcji (np. event bus/query client invalidate jeśli istnieje mechanizm globalny; fallback: refetch na wejściu na stronę).
11. Dodać podstawowe testy jednostkowe komponentów (hook mapowanie, walidacja zakresu) oraz test e2e/contract jeśli framework dostępny.
