import type { RegistrationResponseDto } from "@/types";

const TOKEN_KEY = "finpath_jwt_token";
const REFRESH_TOKEN_KEY = "finpath_jwt_refresh_token";

export const saveTokens = (tokens: RegistrationResponseDto): void => {
  if (typeof window !== "undefined") {
    localStorage.setItem(TOKEN_KEY, tokens.token);
    localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refresh_token);
  }
};

export const getAuthToken = (): string | null => {
  if (typeof window !== "undefined") {
    return localStorage.getItem(TOKEN_KEY);
  }
  return null;
};

export const clearTokens = (): void => {
  if (typeof window !== "undefined") {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(REFRESH_TOKEN_KEY);
  }
};


