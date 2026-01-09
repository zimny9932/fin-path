import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, it, expect, vi } from "vitest";
import OnboardingForm from "@/components/OnboardingForm.tsx";
import * as api from "@/lib/api.ts";

// Mock `apiFetch`
vi.mock("@/lib/api", () => ({
  apiFetch: vi.fn(),
}));

describe("OnboardingForm", () => {
  // Mock window.location.href
  const { location } = window;
  beforeAll(() => {
    // @ts-ignore
    delete window.location;
    // @ts-ignore
    window.location = { href: "" };
  });
  afterAll(() => {
    window.location = location;
  });

  it("should render the form with a disabled submit button", () => {
    render(<OnboardingForm />);

    expect(
      screen.getByRole("heading", { name: /konfiguracja konta/i }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: /zapisz i kontynuuj/i }),
    ).toBeDisabled();
    expect(screen.getByText("Wybierz dzień...")).toBeInTheDocument();
  });

  it("should enable the submit button when a day is selected", async () => {
    render(<OnboardingForm />);
    const user = userEvent.setup();

    const submitButton = screen.getByRole("button", {
      name: /zapisz i kontynuuj/i,
    });
    expect(submitButton).toBeDisabled();

    // Open the select dropdown
    await user.click(screen.getByTestId("billing-day"));

    // Select an option
    await user.click(screen.getByRole("option", { name: "15" }));

    expect(submitButton).toBeEnabled();
  });

  it("should call apiFetch and redirect on successful submission", async () => {
    vi.mocked(api.apiFetch).mockResolvedValue(new Response(null, { status: 200 }));

    render(<OnboardingForm />);
    const user = userEvent.setup();

    // Select a day to enable the button
    await user.click(screen.getByTestId("billing-day"));
    await user.click(screen.getByRole("option", { name: "25" }));

    // Click submit
    const submitButton = screen.getByRole("button", {
      name: /zapisz i kontynuuj/i,
    });
    await user.click(submitButton);

    expect(api.apiFetch).toHaveBeenCalledWith("/api/users/me/onboarding", {
      method: "PATCH",
      body: JSON.stringify({
        billingCycleStartDay: 25,
      }),
    });

    await waitFor(() => {
        expect(window.location.href).toBe("/budget/new");
    });
  });

  it("should display an error message on failed submission", async () => {
    const errorMessage = "Wystąpił błąd.";
    vi.mocked(api.apiFetch).mockRejectedValue({ message: errorMessage });

    render(<OnboardingForm />);
    const user = userEvent.setup();

    // Select a day and submit
    await user.click(screen.getByTestId("billing-day"));
    await user.click(screen.getByRole("option", { name: "10" }));
    await user.click(screen.getByRole("button", { name: /zapisz i kontynuuj/i }));

    await waitFor(() => {
      expect(screen.getByText(errorMessage)).toBeInTheDocument();
    });

    // Button should be enabled again after error
    expect(screen.getByRole("button", { name: /zapisz i kontynuuj/i })).toBeEnabled();
  });
});
