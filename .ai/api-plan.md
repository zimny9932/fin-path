# REST API Plan

This document outlines the REST API for the FinPath application, based on the product requirements and database schema.

## 1. Resources

- **Users**: Represents user accounts and profile information. (Corresponds to `users` table)
- **Subcategories**: User-defined categories for transactions. (Corresponds to `subcategories` table)
- **Transactions**: Financial records (income/expense). (Corresponds to `transactions` table)
- **Budgets**: Monthly financial plans. (Corresponds to `budgets` and `budget_limits` tables)
- **Dashboard**: Aggregated data for the main user view. (Derived from other resources)
- **Enums**: Static application data (e.g., Main Categories). (Defined in backend code)

## 2. Endpoints

---

### Authentication

#### **POST** `/api/register`
- **Description**: Creates a new user account.
- **Request Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123",
    "passwordConfirmation": "password123"
  }
  ```
- **Success Response**: `201 Created` with JWT tokens.
  ```json
  {
    "token": "...",
    "refresh_token": "..."
  }
  ```
- **Error Responses**:
  - `400 Bad Request`: Validation failed (e.g., passwords don't match, invalid email).
  - `409 Conflict`: Email already exists.

#### **POST** `/api/login`
- **Description**: Authenticates a user and returns JWT tokens.
- **Request Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```
- **Success Response**: `200 OK` with JWT tokens.
  ```json
  {
    "token": "...",
    "refresh_token": "..."
  }
  ```
- **Error Responses**:
  - `400 Bad Request`: Validation failed.
  - `401 Unauthorized`: Invalid credentials.

#### **POST** `/api/token/refresh`
- **Description**: Refreshes an expired JWT token.
- **Request Body**:
  ```json
  {
    "refresh_token": "..."
  }
  ```
- **Success Response**: `200 OK` with a new JWT token.
  ```json
  {
    "token": "..."
  }
  ```
- **Error Responses**:
  - `401 Unauthorized`: Invalid refresh token.

---

### Users

#### **GET** `/api/users/me`
- **Description**: Retrieves the currently authenticated user's profile.
- **Success Response**: `200 OK`
  ```json
  {
    "id": "uuid-v7-string",
    "email": "user@example.com",
    "billingCycleStartDay": 25, // null if not set
    "createdAt": "YYYY-MM-DDTHH:MM:SSZ",
    "updatedAt": "YYYY-MM-DDTHH:MM:SSZ"
  }
  ```
- **Error Responses**:
  - `401 Unauthorized`: Not authenticated.

#### **PATCH** `/api/users/me/onboarding`
- **Description**: Sets the `billingCycleStartDay` for a new user. This is a one-time setup.
- **Request Body**:
  ```json
  {
    "billingCycleStartDay": 25
  }
  ```
- **Success Response**: `200 OK` with the updated user profile.
- **Error Responses**:
  - `400 Bad Request`: Validation failed (e.g., day out of range 1-31, or already set).
  - `401 Unauthorized`: Not authenticated.

---

### Subcategories

#### **GET** `/api/subcategories`
- **Description**: Lists all subcategories for the authenticated user.
- **Query Parameters**:
  - `type` (string, optional): Filter by `income` or `expense`.
- **Success Response**: `200 OK`
  ```json
  [
    {
      "id": "uuid-v7-string",
      "name": "Salary",
      "type": "income",
      "mainCategory": "INCOME"
    },
    {
      "id": "uuid-v7-string",
      "name": "Groceries",
      "type": "expense",
      "mainCategory": "FOOD"
    }
  ]
  ```

#### **POST** `/api/subcategories`
- **Description**: Creates a new subcategory.
- **Request Body**:
  ```json
  {
    "name": "Internet Bill",
    "type": "expense",
    "mainCategory": "BILLS"
  }
  ```
- **Success Response**: `201 Created` with the new subcategory object.
- **Error Responses**:
  - `400 Bad Request`: Validation failed.
  - `409 Conflict`: A subcategory with the same name already exists for the user.

#### **GET** `/api/subcategories/recent`
- **Description**: Lists the most recently used subcategories for the authenticated user. This supports the UI requirement to show recent categories for faster transaction entry.
- **Query Parameters**:
  - `limit` (int, default: 5): The maximum number of recent subcategories to return.
- **Success Response**: `200 OK` with an array of subcategory objects (structure is the same as in `GET /api/subcategories`).

#### **GET** `/api/subcategories/{id}`
- **Description**: Retrieves a single subcategory.
- **Success Response**: `200 OK` with the subcategory object.
- **Error Responses**:
  - `404 Not Found`: Subcategory not found or does not belong to the user.

#### **PUT** `/api/subcategories/{id}`
- **Description**: Updates a subcategory.
- **Request Body**:
  ```json
  {
    "name": "Groceries & Food",
    "type": "expense",
    "mainCategory": "FOOD"
  }
  ```
- **Success Response**: `200 OK` with the updated subcategory object.
- **Error Responses**:
  - `400 Bad Request`: Validation failed.
  - `404 Not Found`: Subcategory not found.
  - `409 Conflict`: Name conflict.

#### **DELETE** `/api/subcategories/{id}`
- **Description**: Deletes a subcategory.
- **Success Response**: `204 No Content`
- **Error Responses**:
  - `404 Not Found`: Subcategory not found.
  - `409 Conflict`: Cannot delete subcategory if it's associated with transactions.

---

### Transactions

#### **GET** `/api/transactions`
- **Description**: Lists transactions for the authenticated user, paginated. Defaults to the current billing cycle.
- **Query Parameters**:
  - `page` (int, default: 1): Page number for pagination.
  - `limit` (int, default: 30): Items per page.
  - `sortBy` (string, default: "date"): Field to sort by.
  - `sortOrder` (string, default: "desc"): `asc` or `desc`.
  - `startDate` (string, `YYYY-MM-DD`): Filter by start date.
  - `endDate` (string, `YYYY-MM-DD`): Filter by end date.
- **Success Response**: `200 OK`
  ```json
  {
    "items": [
      {
        "id": "uuid-v7-string",
        "amount": { "amount": 5000, "currency": "PLN" },
        "date": "YYYY-MM-DD",
        "description": "Weekly shopping",
        "subcategory": {
          "id": "uuid-v7-string",
          "name": "Groceries"
        }
      }
    ],
    "pagination": {
      "currentPage": 1,
      "totalPages": 5,
      "totalItems": 150
    }
  }
  ```

#### **POST** `/api/transactions`
- **Description**: Creates a new transaction.
- **Request Body**:
  ```json
  {
    "subcategoryId": "uuid-v7-string",
    "amount": { "amount": 12345, "currency": "PLN" },
    "date": "YYYY-MM-DD",
    "description": "New headphones"
  }
  ```
- **Success Response**: `201 Created` with the new transaction object.
- **Error Responses**:
  - `400 Bad Request`: Validation failed.

#### **GET** `/api/transactions/{id}`
- **Description**: Retrieves a single transaction.
- **Success Response**: `200 OK` with the transaction object.
- **Error Responses**:
  - `404 Not Found`: Transaction not found.

#### **PUT** `/api/transactions/{id}`
- **Description**: Updates a transaction.
- **Success Response**: `200 OK` with the updated transaction object.
- **Error Responses**:
  - `400 Bad Request`: Validation failed.
  - `404 Not Found`: Transaction not found.

#### **DELETE** `/api/transactions/{id}`
- **Description**: Deletes a transaction.
- **Success Response**: `204 No Content`
- **Error Responses**:
  - `404 Not Found`: Transaction not found.

---

### Budgets

#### **GET** `/api/budgets/{year}/{month}`
- **Description**: Retrieves the budget for a specific year and month.
- **Success Response**: `200 OK`
  ```json
  {
    "id": "uuid-v7-string",
    "year": 2025,
    "month": 10,
    "plannedIncome": { "amount": 600000, "currency": "PLN" },
    "limits": [
      {
        "id": "uuid-v7-string",
        "limitAmount": { "amount": 50000, "currency": "PLN" },
        "subcategory": {
          "id": "uuid-v7-string",
          "name": "Groceries"
        }
      }
    ]
  }
  ```
- **Error Responses**:
  - `404 Not Found`: Budget for the given period not found.

#### **POST** `/api/budgets`
- **Description**: Creates a new budget for a specific year and month.
- **Request Body**:
  ```json
  {
    "year": 2025,
    "month": 11,
    "plannedIncome": { "amount": 650000, "currency": "PLN" },
    "limits": [
      {
        "subcategoryId": "uuid-v7-string",
        "limitAmount": { "amount": 40000, "currency": "PLN" }
      }
    ]
  }
  ```
- **Success Response**: `201 Created` with the new budget object.
- **Error Responses**:
  - `400 Bad Request`: Validation failed.
  - `409 Conflict`: A budget for this year/month already exists.

#### **POST** `/api/budgets/{year}/{month}/copy`
- **Description**: Creates a new budget for the specified `{year}` and `{month}` by copying the limits and planned income from an existing source budget.
- **Request Body**:
  ```json
  {
    "sourceYear": 2025,
    "sourceMonth": 10
  }
  ```
- **Success Response**: `201 Created` with the new budget object. The user can then proceed to edit this new budget if needed.
- **Error Responses**:
  - `400 Bad Request`: Validation failed (e.g., source year/month invalid).
  - `404 Not Found`: The source budget to copy from does not exist.
  - `409 Conflict`: A budget for the target `{year}` and `{month}` already exists.

#### **PUT** `/api/budgets/{year}/{month}`
- **Description**: Updates an existing budget.
- **Request Body**: (Same as POST, without `copyFrom`)
- **Success Response**: `200 OK` with the updated budget object.
- **Error Responses**:
  - `400 Bad Request`: Validation failed.
  - `404 Not Found`: Budget not found.

---

### Dashboard

#### **GET** `/api/dashboard`
- **Description**: Retrieves aggregated data for the dashboard for the current billing cycle.
- **Success Response**: `200 OK`
  ```json
  {
    "billingCycle": {
      "startDate": "YYYY-MM-DD",
      "endDate": "YYYY-MM-DD"
    },
    "summary": {
      "totalIncome": { "amount": 550000, "currency": "PLN" },
      "totalExpenses": { "amount": 320000, "currency": "PLN" },
      "balance": { "amount": 230000, "currency": "PLN" }
    },
    "budgetProgress": {
      "planned": { "amount": 400000, "currency": "PLN" },
      "spent": { "amount": 320000, "currency": "PLN" },
      "percentage": 80
    }
  }
  ```

---

### Reports

#### **GET** `/api/reports/spending-by-category`
- **Description**: Retrieves a summary of spending grouped by main category for a given period. Defaults to the current billing cycle.
- **Query Parameters**:
  - `startDate` (string, `YYYY-MM-DD`): Filter by start date.
  - `endDate` (string, `YYYY-MM-DD`): Filter by end date.
- **Success Response**: `200 OK`
  ```json
  [
    {
      "mainCategory": "FOOD",
      "totalAmount": { "amount": 125000, "currency": "PLN" },
      "percentageOfTotal": 45.5
    },
    {
      "mainCategory": "BILLS",
      "totalAmount": { "amount": 80000, "currency": "PLN" },
      "percentageOfTotal": 29.1
    }
  ]
  ```

---

### Enums

#### **GET** `/api/enums/main-categories`
- **Description**: Returns the list of available main categories.
- **Success Response**: `200 OK`
  ```json
  [
    { "value": "FOOD", "label": "Food" },
    { "value": "TRANSPORT", "label": "Transport" },
    { "value": "BILLS", "label": "Bills" }
  ]
  ```

#### **GET** `/api/enums/transaction-types`
- **Description**: Returns the list of available transaction types.
- **Success Response**: `200 OK`
  ```json
  [
    { "value": "income", "label": "Income" },
    { "value": "expense", "label": "Expense" }
  ]
  ```

## 3. Authentication and Authorization

- **Authentication**: The API will use JSON Web Tokens (JWT). A short-lived access token will be sent in the `Authorization: Bearer <token>` header with each request. A long-lived refresh token will be used to obtain new access tokens.
- **Authorization**: All endpoints (except for `/register`, `/login`, and `/token/refresh`) are protected and require a valid JWT. Data access is strictly scoped to the authenticated user using Row-Level Security (RLS) policies at the database level, ensuring users can only access their own data.

## 4. Validation and Business Logic

- **Validation**:
  - Input validation will be performed at the controller layer using Symfony's Validator component.
  - Rules are derived from the database schema (e.g., `NOT NULL`, `UNIQUE`) and PRD (e.g., password strength, positive amounts).
  - **Users**: `email` must be unique and valid. `billingCycleStartDay` must be between 1-31.
  - **Subcategories**: `name` must be unique per user. `type` must be `income` or `expense`.
  - **Transactions**: `amount` must be a positive integer. `subcategoryId` must exist and belong to the user.
  - **Budgets**: `year`/`month` combination must be unique per user.

- **Business Logic**:
  - **Onboarding**: The `PATCH /api/users/me/onboarding` endpoint is designed specifically for the one-time action of setting the billing cycle start day.
  - **Budget Copying**: The `POST /api/budgets/{year}/{month}/copy` endpoint implements the "copy budget from previous month" feature. The service layer will create a new budget for the target period, populating its planned income and limits based on the source budget.
  - **Dashboard Aggregation**: The `GET /api/dashboard` endpoint will have a dedicated service to calculate the current billing cycle dates and aggregate transaction and budget data efficiently, leveraging database indexes.
