import type { RegistrationRequestDto } from "@/types";

export const registerUser = async (data: RegistrationRequestDto) => {
  const response = await fetch("/api/register", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(data),
  });

  if (!response.ok) {
    const errorData = await response.json();
    throw {
      status: response.status,
      message:
        errorData.message ||
        "Wystąpił nieoczekiwany błąd. Prosimy spróbować ponownie.",
    };
  }

  return await response.json();
};


