import { getAuthToken } from "./auth";
import type { RegistrationRequestDto } from "@/types";

type ApiFetchOptions = RequestInit & {
  needsAuth?: boolean;
};

export const apiFetch = async (
  url: string,
  options: ApiFetchOptions = {},
) => {
  const headers: HeadersInit = {
    Accept: "application/ld+json",
    ...options.headers,
  };

  const hasBody = options.body !== undefined;
  if (hasBody) {
    headers["Content-Type"] = headers["Content-Type"] ?? "application/json";
  }

  const { needsAuth = true } = options;

  if (needsAuth) {
    const token = getAuthToken();
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    } else {
      // W przyszłości można tu dodać przekierowanie do logowania
      console.warn("Brak tokenu autoryzacyjnego dla żądania wymagającego uwierzytelnienia.");
    }
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({
      // Jeśli odpowiedź błędu nie jest JSON-em
      message: response.statusText,
    }));
    throw {
      status: response.status,
      message:
        errorData.message ||
        errorData.detail ||
        "Wystąpił nieoczekiwany błąd.",
    };
  }

  // Zwracaj samą odpowiedź, jeśli status to 204 No Content
  if (response.status === 204) {
    return response;
  }
  
  return response.json();
};

export const registerUser = async (data: RegistrationRequestDto) => {
  // Używamy apiFetch z wyłączoną potrzebą autoryzacji
  return apiFetch("/api/register", {
    method: "POST",
    body: JSON.stringify(data),
    needsAuth: false,
  });
};


