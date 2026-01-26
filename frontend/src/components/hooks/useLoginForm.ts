import { useState } from "react";
import { z } from "zod";
import { toast } from "sonner";
import type {
  LoginFormViewModel,
  LoginFormValidationViewModel,
  LoginRequestDTO,
  RegistrationResponseDto,
} from "@/types";
import { saveTokens } from "@/lib/auth";

const loginSchema = z.object({
  email: z.string().min(1, { message: "Pole jest wymagane" }).email({ message: "Nieprawidłowy format e-mail" }),
  password: z.string().min(1, { message: "Pole jest wymagane" }),
});

export const useLoginForm = () => {
  const [formData, setFormData] = useState<LoginFormViewModel>({
    email: "",
    password: "",
  });
  const [errors, setErrors] = useState<LoginFormValidationViewModel>({});
  const [isLoading, setIsLoading] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setErrors({});

    const validationResult = loginSchema.safeParse(formData);

    if (!validationResult.success) {
      const fieldErrors: LoginFormValidationViewModel = {};
      validationResult.error.errors.forEach((error) => {
        if (error.path[0]) {
          fieldErrors[error.path[0] as keyof LoginFormValidationViewModel] = error.message;
        }
      });
      setErrors(fieldErrors);
      return;
    }

    setIsLoading(true);

    const loginData: LoginRequestDTO = {
      email: formData.email,
      password: formData.password,
    };

    try {
      const response = await fetch("/api/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(loginData),
      });

      if (!response.ok) {
        if (response.status === 401) {
          toast.error("Nieprawidłowy e-mail lub hasło.");
        } else {
          const errorData = await response.json();
          toast.error(errorData.message || "Wystąpił nieoczekiwany błąd serwera.");
        }
        return;
      }

      const data: RegistrationResponseDto = await response.json();

      saveTokens(data);

      toast.success("Zalogowano pomyślnie!");
      window.location.href = "/";
    } catch {
      toast.error("Wystąpił błąd sieci. Spróbuj ponownie.");
    } finally {
      setIsLoading(false);
    }
  };

  return {
    formData,
    errors,
    isLoading,
    handleChange,
    handleSubmit,
  };
};
