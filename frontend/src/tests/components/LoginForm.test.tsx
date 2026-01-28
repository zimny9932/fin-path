import React from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { vi } from "vitest";
import LoginForm from "@/components/LoginForm";
import * as useLoginFormHook from "@/components/hooks/useLoginForm";

vi.mock("@/components/hooks/useLoginForm");

const mockUseLoginForm = (isLoading = false, errors = {}, formData = { email: "", password: "" }) => {
  return {
    formData,
    errors,
    isLoading,
    handleChange: vi.fn(),
    handleSubmit: vi.fn((e) => e.preventDefault()),
  };
};

describe("LoginForm", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("should render the login form correctly", () => {
    (useLoginFormHook.useLoginForm as vi.Mock).mockReturnValue(mockUseLoginForm());
    render(<LoginForm />);

    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/hasło/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /zaloguj się/i })).toBeInTheDocument();
  });

  it("should display validation errors", () => {
    const errors = {
      email: "Pole jest wymagane",
      password: "Hasło jest za krótkie",
    };
    (useLoginFormHook.useLoginForm as vi.Mock).mockReturnValue(mockUseLoginForm(false, errors));
    render(<LoginForm />);

    expect(screen.getByText("Pole jest wymagane")).toBeInTheDocument();
    expect(screen.getByText("Hasło jest za krótkie")).toBeInTheDocument();
  });

  it("should call handleChange on input change", async () => {
    const handleChange = vi.fn();
    (useLoginFormHook.useLoginForm as vi.Mock).mockReturnValue({
      ...mockUseLoginForm(),
      handleChange,
    });
    render(<LoginForm />);

    const emailInput = screen.getByLabelText(/email/i);
    await userEvent.type(emailInput, "test@example.com");

    expect(handleChange).toHaveBeenCalled();
  });

  it("should call handleSubmit on form submission", async () => {
    const handleSubmit = vi.fn((e) => e.preventDefault());
    (useLoginFormHook.useLoginForm as vi.Mock).mockReturnValue({
      ...mockUseLoginForm(),
      handleSubmit,
    });
    render(<LoginForm />);

    const submitButton = screen.getByRole("button", { name: /zaloguj się/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(handleSubmit).toHaveBeenCalledTimes(1);
    });
  });

  it("should disable the submit button when isLoading is true", () => {
    (useLoginFormHook.useLoginForm as vi.Mock).mockReturnValue(mockUseLoginForm(true));
    render(<LoginForm />);

    const submitButton = screen.getByRole("button", { name: /logowanie.../i });
    expect(submitButton).toBeDisabled();
  });
});
