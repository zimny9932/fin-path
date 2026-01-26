import { expect, test } from "@playwright/test";

test.describe("Logowanie - happy path", () => {
  test.beforeEach(async ({ page }) => {
    // Wyczyść stan, żeby kolejne uruchomienia nie korzystały z już zapisanych tokenów.
    await page.goto("/");
    await page.context().clearCookies();
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });
  });

  test("pozwala zalogować istniejącego użytkownika i przejść do raportów", async ({ page }) => {
    await page.goto("/login");
    await page.waitForLoadState("networkidle");

    const emailInput = page.getByLabel("Email", { exact: true });
    const passwordInput = page.getByLabel("Hasło", { exact: true });

    await emailInput.fill("user@example.com");
    await passwordInput.fill("password");
    await expect(emailInput).toHaveValue("user@example.com");
    await expect(passwordInput).toHaveValue("password");

    const loginRequestPredicate = (request: { url: () => string; method: () => string }) =>
      request.url().includes("/api/login") && request.method() === "POST";

    const loginRequestPromise = page.waitForRequest(loginRequestPredicate, { timeout: 15000 });
    const loginResponsePromise = page.waitForResponse((response) => loginRequestPredicate(response.request()), {
      timeout: 20000,
    });

    await page.getByRole("button", { name: "Zaloguj się" }).click();

    const loginRequest = await loginRequestPromise.catch(() => null);
    if (!loginRequest) {
      const fieldErrors = await page
        .locator("p.text-sm.text-red-500")
        .allInnerTexts()
        .catch(() => []);
      throw new Error(`Żądanie /api/login nie zostało wysłane. Błędy walidacji: ${fieldErrors.join(" | ")}`);
    }

    const failedRequest = await page
      .waitForEvent("requestfailed", {
        predicate: loginRequestPredicate,
        timeout: 15000,
      })
      .catch(() => null);

    if (failedRequest) {
      throw new Error(`Login request failed: ${failedRequest.failure()?.errorText ?? "unknown error"}`);
    }

    const loginResponse = await loginResponsePromise;
    expect(loginResponse.ok()).toBeTruthy();

    await page.waitForURL(/\/reports/);
    await expect(page.getByRole("heading", { name: "Raporty" })).toBeVisible();

    const tokens = await page.evaluate(() => ({
      access: localStorage.getItem("finpath_jwt_token"),
      refresh: localStorage.getItem("finpath_jwt_refresh_token"),
    }));

    expect(tokens.access).toBeTruthy();
    expect(tokens.refresh).toBeTruthy();
  });
});
