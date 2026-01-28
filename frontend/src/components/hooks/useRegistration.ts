import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { useState } from "react";
import { registerUser } from "@/lib/api";
import { saveTokens } from "@/lib/auth";
import { toast } from "sonner";
import type { RegistrationResponseDto } from "@/types";

const registrationSchema = z
  .object({
    email: z
      .string()
      .min(1, { message: "Adres e-mail jest wymagany." })
      .email({ message: "Proszę podać poprawny adres e-mail." }),
    password: z.string().min(8, { message: "Hasło musi mieć co najmniej 8 znaków." }),
    passwordConfirmation: z.string().min(1, { message: "Potwierdzenie hasła jest wymagane." }),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    message: "Hasła muszą być identyczne.",
    path: ["passwordConfirmation"],
  });

export type RegistrationFormData = z.infer<typeof registrationSchema>;

export const useRegistration = () => {
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [apiError, setApiError] = useState<string | null>(null);

  const form = useForm<RegistrationFormData>({
    resolver: zodResolver(registrationSchema),
    defaultValues: {
      email: "",
      password: "",
      passwordConfirmation: "",
    },
  });

  const onSubmit = async (values: RegistrationFormData) => {
    setIsLoading(true);
    setApiError(null);
    try {
      const response: RegistrationResponseDto = await registerUser(values);
      saveTokens(response);
      toast.success("Konto zostało pomyślnie utworzone!");
      window.location.href = "/onboarding";
    } catch (error: unknown) {
      const normalizedError =
        error && typeof error === "object" ? (error as { status?: number; message?: string }) : undefined;
      const errorMessage =
        normalizedError?.status === 409
          ? "Ten adres e-mail jest już zajęty."
          : normalizedError?.message || "Wystąpił nieoczekiwany błąd.";
      setApiError(errorMessage);
      form.setError("email", { type: "manual", message: errorMessage });
      toast.error("Rejestracja nie powiodła się.");
    } finally {
      setIsLoading(false);
    }
  };

  return {
    form,
    onSubmit: form.handleSubmit(onSubmit),
    isLoading,
    apiError,
  };
};
