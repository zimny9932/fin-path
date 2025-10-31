# Dokument wymagań produktu (PRD) - FinPath (MVP)

## 1. Przegląd produktu
FinPath (MVP) to aplikacja webowa zaprojektowana do uproszczenia zarządzania finansami domowymi. Umożliwia użytkownikom szybkie rejestrowanie przychodów i wydatków, kategoryzowanie ich oraz śledzenie postępów w realizacji miesięcznego budżetu. Głównym celem jest dostarczenie prostego narzędzia, które zastąpi skomplikowane arkusze kalkulacyjne i pomoże w świadomym kontrolowaniu domowych finansów.

## 2. Problem użytkownika
Wiele osób i rodzin ma trudności z systematycznym śledzeniem swoich wydatków oraz trzymaniem się założonego budżetu. Korzystanie z arkuszy kalkulacyjnych jest często czasochłonne, niewygodne na co dzień i nie dostarcza natychmiastowych informacji zwrotnych o stanie finansów. Brak łatwego wglądu w strukturę wydatków i stopień realizacji planu budżetowego prowadzi do niekontrolowanych nadwyżek lub deficytów, co utrudnia osiąganie celów finansowych. Aplikacja kierowana jest do osób w wieku 20-60 lat, z dochodem 2 000–10 000 zł na osobę, które poszukują prostego i efektywnego rozwiązania do zarządzania domowym budżetem.

## 3. Wymagania funkcjonalne
*   F-01: Uwierzytelnianie użytkownika:
    *   Możliwość rejestracji nowego konta za pomocą minimalnego zestawu danych (email, hasło).
    *   Możliwość logowania do istniejącego konta.
*   F-02: Zarządzanie transakcjami:
    *   Formularz do szybkiego dodawania przychodów i wydatków.
    *   Pola transakcji: kwota, podkategoria, data (domyślnie dzisiejsza), opcjonalny opis (do 200 znaków).
    *   Walidacja danych wejściowych po stronie frontendu i backendu.
*   F-03: Zarządzanie budżetem:
    *   Jednorazowa konfiguracja dnia rozpoczęcia miesiąca rozliczeniowego (1-31).
    *   Jeśli uzytkownik ustawił początek na 31 dzień a dany miesiąc ma 30 dni rozpocznamy od ostatniego dnia miesiąca
    *   Możliwość zdefiniowania planowanego budżetu na bieżący miesiąc, w tym planowanych przychodów i limitów wydatków dla poszczególnych podkategorii.
    *   Możliwość skopiowania budżetu z poprzedniego miesiąca przy planowaniu nowego.
*   F-04: Kategorie:
    *   Predefiniowana lista kategorii i podkategorii (np. jedzenie, transport, rachunki, rozrywka).
    *   Użytkownik podczas dodawania transakcji wybiera podkategorię z zagnieżdżonej listy.
    *   Interfejs wyboru kategorii powinien wspierać wyszukiwanie i pokazywać ostatnio używane podkategorie.
*   F-05: Dashboard:
    *   Wyświetlanie podsumowania dla bieżącego miesiąca rozliczeniowego.
    *   Karty z informacjami: "Suma wydatków", "Suma przychodów", "Saldo".
    *   Wskaźnik procentowy realizacji planu budżetowego.
*   F-06: Raporty i statystyki:
    *   Zestawienia miesięczne transakcji w formie listy lub wykresu słupkowego.
    *   Podstawowe statystyki: przegląd kategorii z największymi wydatkami, procentowy udział wydatków na kategorię.
    *   Widok ograniczony tylko do bieżącego miesiąca rozliczeniowego.
*   F-07: Powiadomienia:
    *   Powiadomienie email wysyłane 3 dni przed końcem miesiąca rozliczeniowego z przypomnieniem o zaplanowaniu budżetu na kolejny miesiąc.
    *   Banner w aplikacji na początku nowego miesiąca informujący o braku planu i oferujący opcję skopiowania 

## 4. Granice produktu
Następujące funkcjonalności nie wchodzą w zakres wersji MVP (Minimum Viable Product):
*   Integracje z systemami bankowymi, kartami płatniczymi lub zewnętrznymi API finansowymi.
*   Dedykowane aplikacje mobilne (natywne na iOS/Android).
*   Funkcje współdzielenia budżetu i dostępu dla wielu użytkowników.
*   Eksport danych do formatów zewnętrznych (CSV, PDF, Google Sheets).
*   Zaawansowane raporty historyczne, prognozowanie wydatków i analizy oparte na uczeniu maszynowym.

## 5. Historyjki użytkowników
### US-001: Rejestracja nowego użytkownika
*   Tytuł: Rejestracja nowego użytkownika
*   Opis: Jako nowy użytkownik, chcę móc założyć konto w aplikacji, podając swój adres e-mail i hasło, aby uzyskać dostęp do jej funkcjonalności.
*   Kryteria akceptacji:
    1.  Formularz rejestracji zawiera pola: adres e-mail, hasło, powtórz hasło.
    2.  System waliduje poprawność formatu adresu e-mail.
    3.  System sprawdza, czy hasła w obu polach są identyczne.
    4.  Hasło musi spełniać minimalne wymagania bezpieczeństwa (np. 8 znaków).
    5.  Po pomyślnej rejestracji użytkownik jest automatycznie zalogowany i przekierowany do ekranu początkowej konfiguracji.
    6.  W przypadku, gdy e-mail jest już zajęty, wyświetlany jest odpowiedni komunikat.

### US-002: Logowanie użytkownika
*   Tytuł: Logowanie do aplikacji
*   Opis: Jako zarejestrowany użytkownik, chcę móc zalogować się do aplikacji przy użyciu mojego adresu e-mail i hasła, aby uzyskać dostęp do moich danych finansowych.
*   Kryteria akceptacji:
    1.  Formularz logowania zawiera pola: adres e-mail, hasło.
    2.  Po poprawnym wprowadzeniu danych użytkownik zostaje przekierowany do głównego panelu (Dashboard).
    3.  W przypadku podania błędnych danych, wyświetlany jest komunikat o nieprawidłowym loginie lub haśle.

### US-003: Wstępna konfiguracja konta
*   Tytuł: Konfiguracja miesiąca rozliczeniowego
*   Opis: Jako nowy użytkownik, po pierwszym zalogowaniu, chcę ustawić dzień rozpoczęcia mojego miesiąca rozliczeniowego, aby aplikacja poprawnie agregowała moje dane finansowe.
*   Kryteria akceptacji:
    1.  Po pierwszym zalogowaniu wyświetlany jest ekran konfiguracji.
    2.  Użytkownik może wybrać dzień z listy rozwijanej (dropdown) zawierającej wartości od 1 do 31.
    3.  Po dokonaniu wyboru i zatwierdzeniu, ustawienie jest zapisywane na stałe dla konta użytkownika.
    4.  Użytkownik jest następnie przekierowywany do ekranu definiowania pierwszego budżetu.

### US-004: Definiowanie budżetu miesięcznego
*   Tytuł: Definiowanie budżetu miesięcznego
*   Opis: Jako użytkownik, chcę móc zdefiniować swój budżet na bieżący miesiąc rozliczeniowy, określając planowane przychody oraz limity wydatków dla poszczególnych podkategorii.
*   Kryteria akceptacji:
    1.  Użytkownik ma dostęp do ekranu planowania budżetu.
    2.  Możliwe jest wprowadzenie sumy planowanych przychodów.
    3.  Dla każdej podkategorii wydatków można zdefiniować planowany limit kwotowy.
    4.  System sumuje wszystkie limity wydatków.
    5.  Po zapisaniu, budżet staje się aktywny dla bieżącego miesiąca rozliczeniowego.

### US-005: Dodawanie transakcji wydatku
*   Tytuł: Dodawanie nowego wydatku
*   Opis: Jako użytkownik, chcę szybko dodać nową transakcję wydatku, podając kwotę, kategorię i opcjonalnie opis, aby na bieżąco śledzić moje finanse.
*   Kryteria akceptacji:
    1.  Dostępny jest formularz dodawania transakcji.
    2.  Pola formularza: kwota (pole numeryczne, wymagane), podkategoria (wybór z listy, wymagane), data (domyślnie dzisiejsza), opis (opcjonalny, max 200 znaków).
    3.  Wybór podkategorii odbywa się za pomocą drzewa kategorii z funkcją wyszukiwania.
    4.  System wyświetla ostatnio używane podkategorie dla szybszego dostępu.
    5.  Walidacja front-end i back-end sprawdza, czy kwota jest liczbą dodatnią.
    6.  Dodanie wydatku zajmuje mniej niż 10 sekund.
    7.  Po dodaniu transakcji dane na dashboardzie są natychmiast aktualizowane.

### US-006: Dodawanie transakcji przychodu
*   Tytuł: Dodawanie nowego przychodu
*   Opis: Jako użytkownik, chcę dodać nową transakcję przychodu, podając kwotę i źródło, aby uwzględnić ją w moim bilansie finansowym.
*   Kryteria akceptacji:
    1.  Dostępny jest formularz dodawania transakcji z opcją przełączenia na "Przychód".
    2.  Pola formularza: kwota (pole numeryczne, wymagane), podkategoria (np. "Wynagrodzenie", "Premia", wymagane), data (domyślnie dzisiejsza), opis (opcjonalny, max 200 znaków).
    3.  Po dodaniu przychodu, dane na dashboardzie (Suma przychodów, Saldo) są natychmiast aktualizowane.

### US-007: Przeglądanie Dashboardu
*   Tytuł: Przeglądanie dashboardu
*   Opis: Jako użytkownik, chcę mieć dostęp do prostego dashboardu, który pokazuje kluczowe informacje o moim budżecie w bieżącym miesiącu rozliczeniowym.
*   Kryteria akceptacji:
    1.  Dashboard jest domyślnym widokiem po zalogowaniu.
    2.  Wyświetlane są trzy główne karty: "Suma wydatków", "Suma przychodów" i "Saldo" (Przychody - Wydatki) dla bieżącego miesiąca rozliczeniowego.
    3.  Widoczny jest wskaźnik procentowy, pokazujący stosunek sumy wydatków do sumy zaplanowanych limitów.
    4.  Dane na dashboardzie aktualizują się w czasie rzeczywistym po dodaniu nowej transakcji.

### US-008: Przeglądanie listy transakcji
*   Tytuł: Przeglądanie listy transakcji
*   Opis: Jako użytkownik, chcę móc przeglądać listę wszystkich moich transakcji z bieżącego miesiąca, aby analizować szczegóły moich finansów.
*   Kryteria akceptacji:
    1.  Dostępny jest widok listy transakcji dla bieżącego miesiąca rozliczeniowego.
    2.  Każdy wpis na liście pokazuje: datę, kwotę, kategorię i opis.
    3.  Transakcje są domyślnie sortowane chronologicznie (od najnowszej).

### US-009: Kopiowanie budżetu z poprzedniego miesiąca
*   Tytuł: Kopiowanie budżetu
*   Opis: Jako użytkownik, na początku nowego miesiąca rozliczeniowego, chcę mieć możliwość skopiowania planu budżetowego z poprzedniego miesiąca, aby zaoszczędzić czas na ponownej konfiguracji.
*   Kryteria akceptacji:
    1.  Jeśli na początku nowego miesiąca użytkownik nie ma zdefiniowanego budżetu, w aplikacji pojawia się banner z przyciskiem "Skopiuj budżet z poprzedniego miesiąca".
    2.  Po kliknięciu przycisku, użytkownik jest przenoszony do ekranu edycji budżetu, gdzie pola są wstępnie wypełnione danymi z poprzedniego miesiąca.
    3.  Użytkownik może zmodyfikować skopiowane wartości przed ostatecznym zapisaniem budżetu na nowy miesiąc.

### US-010: Otrzymywanie powiadomienia email
*   Tytuł: Przypomnienie o planowaniu budżetu
*   Opis: Jako użytkownik, chcę otrzymać powiadomienie e-mail pod koniec miesiąca, które przypomni mi o konieczności zaplanowania budżetu na kolejny okres.
*   Kryteria akceptacji:
    1.  System automatycznie wysyła e-mail na adres użytkownika na 3 dni przed końcem bieżącego miesiąca rozliczeniowego.
    2.  Wiadomość zawiera przypomnienie o zaplanowaniu budżetu.
    3.  Powiadomienie jest wysyłane tylko wtedy, gdy budżet na kolejny miesiąc nie został jeszcze zdefiniowany.

## 6. Metryki sukcesu
*   Użyteczność: Dodanie nowego wydatku zajmuje użytkownikowi mniej niż 10 sekund.
*   Aktywność użytkowników: Minimum 70% nowo zarejestrowanych użytkowników wprowadza co najmniej jedną transakcję w ciągu pierwszego tygodnia od rejestracji.
*   Retencja: Co najmniej 40% użytkowników powraca do aplikacji po pierwszym miesiącu użytkowania.
*   Stabilność: 99% sesji użytkowników odbywa się bez błędów krytycznych i awarii aplikacji.
*   Zrozumienie danych: Użytkownik jest w stanie w mniej niż 1 minutę od zalogowania odnaleźć informację o aktualnym poziomie realizacji budżetu miesięcznego.

