# FinPath - Schemat Bazy Danych PostgreSQL

## 1. Lista tabel z kolumnami, typami danych i ograniczeniami

### Tabela: `users`
Przechowuje informacje o użytkownikach, ich dane uwierzytelniające oraz podstawowe ustawienia konta.

| Nazwa kolumny             | Typ danych      | Ograniczenia                                      | Opis                                                                 |
|---------------------------|-----------------|---------------------------------------------------|----------------------------------------------------------------------|
| `id`                      | UUID            | PRIMARY KEY                                       | Unikalny identyfikator użytkownika (UUID v7).                        |
| `email`                   | VARCHAR(255)    | UNIQUE, NOT NULL                                  | Adres e-mail użytkownika, używany do logowania.                      |
| `password_hash`           | VARCHAR(255)    | NOT NULL                                          | Hash hasła użytkownika.                                              |
| `billing_cycle_start_day` | SMALLINT        | NULL                                              | Dzień rozpoczęcia miesiąca rozliczeniowego (1-31). NULL oznacza, że użytkownik nie ukończył onboardingu. |
| `created_at`              | TIMESTAMPTZ     | NOT NULL, DEFAULT `NOW()`                         | Czas utworzenia konta.                                               |
| `updated_at`              | TIMESTAMPTZ     | NOT NULL, DEFAULT `NOW()`                         | Czas ostatniej modyfikacji konta.                                    |

---

### Tabela: `subcategories`
Definiuje podkategorie dla transakcji, które mogą być tworzone przez użytkowników. Każda podkategoria jest powiązana z główną kategorią i typem transakcji.

| Nazwa kolumny    | Typ danych   | Ograniczenia                                                                    | Opis                                                                 |
|------------------|--------------|---------------------------------------------------------------------------------|----------------------------------------------------------------------|
| `id`             | UUID         | PRIMARY KEY                                                                     | Unikalny identyfikator podkategorii (UUID v7).                       |
| `user_id`        | UUID         | NOT NULL, FOREIGN KEY (`users.id`) ON DELETE CASCADE                            | Identyfikator użytkownika, do którego należy podkategoria.           |
| `name`           | VARCHAR(100) | NOT NULL                                                                        | Nazwa podkategorii.                                                  |
| `type`           | VARCHAR(10)  | NOT NULL, CHECK (`type` IN ('income', 'expense'))                               | Typ transakcji ('income' lub 'expense').                             |
| `main_category`  | VARCHAR(50)  | NOT NULL                                                                        | Nazwa głównej kategorii (np. 'Jedzenie', 'Transport'), mapowana na enum w aplikacji. |
| `created_at`     | TIMESTAMPTZ  | NOT NULL, DEFAULT `NOW()`                                                       | Czas utworzenia podkategorii.                                        |
| `updated_at`     | TIMESTAMPTZ  | NOT NULL, DEFAULT `NOW()`                                                       | Czas ostatniej modyfikacji podkategorii.                             |
| -                | -            | UNIQUE (`user_id`, `name`)                                                      | Zapewnia unikalność nazw podkategorii dla danego użytkownika.        |

---

### Tabela: `transactions`
Rejestruje wszystkie operacje finansowe (przychody i wydatki) użytkowników.

| Nazwa kolumny      | Typ danych     | Ograniczenia                                                              | Opis                                                                 |
|--------------------|----------------|---------------------------------------------------------------------------|----------------------------------------------------------------------|
| `id`               | UUID           | PRIMARY KEY                                                               | Unikalny identyfikator transakcji (UUID v7).                         |
| `user_id`          | UUID           | NOT NULL, FOREIGN KEY (`users.id`) ON DELETE CASCADE                      | Identyfikator użytkownika, do którego należy transakcja.             |
| `subcategory_id`   | UUID           | NOT NULL, FOREIGN KEY (`subcategories.id`) ON DELETE CASCADE              | Identyfikator podkategorii, do której przypisana jest transakcja.    |
| `amount`           | JSONB          | NOT NULL                                                                  | Kwota i waluta transakcji (np. `{"amount": 10000, "currency": "PLN"}`). |
| `date`             | DATE           | NOT NULL                                                                  | Data transakcji.                                                     |
| `description`      | VARCHAR(200)   | NULL                                                                      | Opcjonalny opis transakcji.                                          |
| `created_at`       | TIMESTAMPTZ    | NOT NULL, DEFAULT `NOW()`                                                 | Czas utworzenia transakcji.                                          |
| `updated_at`       | TIMESTAMPTZ    | NOT NULL, DEFAULT `NOW()`                                                 | Czas ostatniej modyfikacji transakcji.                               |

---

### Tabela: `budgets`
Przechowuje informacje o miesięcznych budżetach użytkowników.

| Nazwa kolumny      | Typ danych   | Ograniczenia                                                              | Opis                                                                 |
|--------------------|--------------|---------------------------------------------------------------------------|----------------------------------------------------------------------|
| `id`               | UUID         | PRIMARY KEY                                                               | Unikalny identyfikator budżetu (UUID v7).                            |
| `user_id`          | UUID         | NOT NULL, FOREIGN KEY (`users.id`) ON DELETE CASCADE                      | Identyfikator użytkownika, do którego należy budżet.                 |
| `year`             | SMALLINT     | NOT NULL                                                                  | Rok, którego dotyczy budżet.                                         |
| `month`            | SMALLINT     | NOT NULL, CHECK (`month` BETWEEN 1 AND 12)                                | Miesiąc, którego dotyczy budżet.                                     |
| `planned_income`   | JSONB        | NOT NULL                                                                  | Suma planowanych przychodów (np. `{"amount": 500000, "currency": "PLN"}`). |
| `created_at`       | TIMESTAMPTZ  | NOT NULL, DEFAULT `NOW()`                                                 | Czas utworzenia budżetu.                                             |
| `updated_at`       | TIMESTAMPTZ  | NOT NULL, DEFAULT `NOW()`                                                 | Czas ostatniej modyfikacji budżetu.                                  |
| -                  | -            | UNIQUE (`user_id`, `year`, `month`)                                       | Zapewnia, że użytkownik może mieć tylko jeden budżet na dany miesiąc. |

---

### Tabela: `budget_limits`
Określa limity wydatków dla poszczególnych podkategorii w ramach danego budżetu.

| Nazwa kolumny    | Typ danych   | Ograniczenia                                                                    | Opis                                                                 |
|------------------|--------------|---------------------------------------------------------------------------------|----------------------------------------------------------------------|
| `id`             | UUID         | PRIMARY KEY                                                                     | Unikalny identyfikator limitu budżetowego (UUID v7).                 |
| `budget_id`      | UUID         | NOT NULL, FOREIGN KEY (`budgets.id`) ON DELETE CASCADE                          | Identyfikator budżetu, do którego należy limit.                      |
| `subcategory_id` | UUID         | NOT NULL, FOREIGN KEY (`subcategories.id`) ON DELETE CASCADE                    | Identyfikator podkategorii, dla której ustalono limit.               |
| `limit_amount`   | JSONB        | NOT NULL                                                                        | Kwota limitu (np. `{"amount": 50000, "currency": "PLN"}`).            |
| `created_at`     | TIMESTAMPTZ  | NOT NULL, DEFAULT `NOW()`                                                       | Czas utworzenia limitu.                                              |
| `updated_at`     | TIMESTAMPTZ  | NOT NULL, DEFAULT `NOW()`                                                       | Czas ostatniej modyfikacji limitu.                                   |
| -                | -            | UNIQUE (`budget_id`, `subcategory_id`)                                          | Zapewnia, że dla danej podkategorii w budżecie może istnieć tylko jeden limit. |

## 2. Relacje między tabelami

-   **`users` do `subcategories`**: Jeden-do-wielu. Jeden użytkownik może mieć wiele podkategorii.
-   **`users` do `transactions`**: Jeden-do-wielu. Jeden użytkownik może mieć wiele transakcji.
-   **`users` do `budgets`**: Jeden-do-wielu. Jeden użytkownik może mieć wiele budżetów.
-   **`subcategories` do `transactions`**: Jeden-do-wielu. Jedna podkategoria może być przypisana do wielu transakcji.
-   **`budgets` do `budget_limits`**: Jeden-do-wielu. Jeden budżet może mieć wiele limitów dla podkategorii.
-   **`subcategories` do `budget_limits`**: Jeden-do-wielu. Jedna podkategoria może mieć wiele limitów w różnych budżetach.

## 3. Indeksy

W celu optymalizacji wydajności zapytań, oprócz indeksów tworzonych automatycznie dla kluczy głównych, obcych i ograniczeń unikalności, zostanie utworzony następujący indeks złożony:

-   **Tabela `transactions`**:
    -   `CREATE INDEX idx_transactions_user_date ON transactions (user_id, date DESC);`
    -   **Uzasadnienie**: Ten indeks znacząco przyspieszy zapytania filtrujące transakcje dla zalogowanego użytkownika w określonym zakresie czasowym, co jest kluczowe dla działania dashboardu i raportów.

## 4. Zasady PostgreSQL (Row-Level Security)

W celu zapewnienia ścisłej izolacji danych między użytkownikami, na wszystkich tabelach zawierających dane użytkownika zostanie włączone zabezpieczenie na poziomie wiersza (RLS). Aplikacja będzie odpowiedzialna za ustawienie zmiennej sesyjnej `app.current_user_id` po zalogowaniu użytkownika.

```sql
-- Włącz RLS dla tabel
ALTER TABLE subcategories ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE budgets ENABLE ROW LEVEL SECURITY;
ALTER TABLE budget_limits ENABLE ROW LEVEL SECURITY;

-- Polityka dla tabel z bezpośrednim kluczem obcym do `users`
CREATE POLICY user_data_isolation_policy ON subcategories
    FOR ALL
    USING (user_id = current_setting('app.current_user_id')::UUID);

CREATE POLICY user_data_isolation_policy ON transactions
    FOR ALL
    USING (user_id = current_setting('app.current_user_id')::UUID);

CREATE POLICY user_data_isolation_policy ON budgets
    FOR ALL
    USING (user_id = current_setting('app.current_user_id')::UUID);

-- Polityka dla `budget_limits` (dostęp przez relację z `budgets`)
CREATE POLICY user_data_isolation_policy ON budget_limits
    FOR ALL
    USING (
        EXISTS (
            SELECT 1 FROM budgets
            WHERE id = budget_limits.budget_id
            AND user_id = current_setting('app.current_user_id')::UUID
        )
    );
```

## 5. Dodatkowe uwagi i decyzje projektowe

1.  **Klucze główne UUID v7**: Wybór UUID v7 jako kluczy głównych ma na celu poprawę wydajności indeksowania i zmniejszenie fragmentacji w porównaniu do losowych UUID v4, co jest korzystne dla skalowalności aplikacji.
2.  **Typ danych `JSONB` dla wartości pieniężnych**: Przechowywanie wartości pieniężnych jako obiektów JSON (np. `{"amount": 10000, "currency": "PLN"}`) zapewnia elastyczność i unika problemów z precyzją liczb zmiennoprzecinkowych. Wartość `amount` będzie przechowywana jako integer (np. w groszach), aby uniknąć błędów zaokrągleń. W warstwie aplikacji (Symfony/Doctrine) zostanie zaimplementowany niestandardowy typ danych do mapowania tych obiektów na obiekty `Money`.
3.  **Onboarding użytkownika**: Kolumna `billing_cycle_start_day` w tabeli `users` z wartością `NULL` jednoznacznie wskazuje, że użytkownik nie ukończył jeszcze procesu wstępnej konfiguracji konta.
4.  **Integralność danych**: Zastosowanie `ON DELETE CASCADE` w kluczach obcych powiązanych z `user_id` zapewnia, że usunięcie konta użytkownika automatycznie usunie wszystkie jego powiązane dane (podkategorie, transakcje, budżety), utrzymując spójność bazy danych.

