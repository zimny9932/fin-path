import React from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { vi } from "vitest";
import RegistrationForm from "@/components/RegistrationForm";
import * as api from "@/lib/api";
import * as auth from "@/lib/auth";

// Mock API and auth functions
vi.mock("@/lib/api", () => ({
  registerUser: vi.fn(),
}));

vi.mock("@/lib/auth", () => ({
  saveTokens: vi.fn(),
}));

// Mock window.location
const originalLocation = window.location;
beforeAll(() => {
  delete (window as unknown as { location?: Location }).location;
  window.location = { ...originalLocation, href: "" };
});
afterAll(() => {
  window.location = originalLocation;
});

describe("RegistrationForm", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.location.href = "";
  });

  it("should render the registration form correctly", () => {
    render(<RegistrationForm />);
    expect(screen.getByLabelText(/adres e-mail/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/^hasło$/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/potwierdź hasło/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /stwórz konto/i })).toBeInTheDocument();
  });

  it("should display validation errors for empty fields", async () => {
    render(<RegistrationForm />);
    const submitButton = screen.getByRole("button", { name: /stwórz konto/i });
    await userEvent.click(submitButton);

    expect(await screen.findByText(/adres e-mail jest wymagany/i)).toBeInTheDocument();
    expect(await screen.findByText(/hasło musi mieć co najmniej 8 znaków/i)).toBeInTheDocument();
    expect(await screen.findByText(/potwierdzenie hasła jest wymagane/i)).toBeInTheDocument();
  });

  it("should display validation error for mismatching passwords", async () => {
    render(<RegistrationForm />);
    const passwordInput = screen.getByLabelText(/^hasło$/i);
    const confirmPasswordInput = screen.getByLabelText(/potwierdź hasło/i);
    const submitButton = screen.getByRole("button", { name: /stwórz konto/i });

    await userEvent.type(passwordInput, "password123");
    await userEvent.type(confirmPasswordInput, "password456");
    await userEvent.click(submitButton);

    expect(await screen.findByText(/hasła muszą być identyczne/i)).toBeInTheDocument();
  });

  it("should successfully register a user and redirect", async () => {
    const mockResponse = { token: "fake-token", refresh_token: "fake-refresh" };
    (api.registerUser as vi.Mock).mockResolvedValue(mockResponse);

    render(<RegistrationForm />);

    await userEvent.type(screen.getByLabelText(/adres e-mail/i), "test@example.com");
    await userEvent.type(screen.getByLabelText(/^hasło$/i), "password123");
    await userEvent.type(screen.getByLabelText(/potwierdź hasło/i), "password123");
    await userEvent.click(screen.getByRole("button", { name: /stwórz konto/i }));

    await waitFor(() => {
      expect(api.registerUser).toHaveBeenCalledWith({
        email: "test@example.com",
        password: "password123",
        passwordConfirmation: "password123",
      });
      expect(auth.saveTokens).toHaveBeenCalledWith(mockResponse);
      expect(window.location.href).toBe("/onboarding");
    });
  });

  it("should display an error message if the email is already taken", async () => {
    (api.registerUser as vi.Mock).mockRejectedValue({ status: 409 });

    render(<RegistrationForm />);

    await userEvent.type(screen.getByLabelText(/adres e-mail/i), "taken@example.com");
    await userEvent.type(screen.getByLabelText(/^hasło$/i), "password123");
    await userEvent.type(screen.getByLabelText(/potwierdź hasło/i), "password123");
    await userEvent.click(screen.getByRole("button", { name: /stwórz konto/i }));

    expect(await screen.findByText(/ten adres e-mail jest już zajęty/i)).toBeInTheDocument();
  });
});
