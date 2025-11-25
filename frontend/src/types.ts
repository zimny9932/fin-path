export interface RegistrationRequestDto {
  email: string;
  password: string;
  passwordConfirmation: string;
}

export interface RegistrationResponseDto {
  token: string;
  refresh_token: string;
}

export interface RegistrationFormViewModel {
  email: string;
  password: string;
  passwordConfirmation: string;
}

export interface RegistrationFormValidationViewModel {
  email?: string;
  password?: string;
  passwordConfirmation?: string;
  api?: string;
}

export interface LoginRequestDTO {
  email: string;
  password: string;
}

export interface LoginResponseDTO {
  token: string;
  refresh_token: string;
}

export interface LoginFormViewModel {
  email: string;
  password: string;
}

export interface LoginFormValidationViewModel {
  email?: string;
  password?: string;
  api?: string;
}

export interface OnboardingRequestDTO {
  /**
   * Dzień rozpoczęcia cyklu rozliczeniowego, wybrany przez użytkownika (liczba od 1 do 31).
   */
  billingCycleStartDay: number;
}

export interface OnboardingFormViewModel {
  /**
   * Wybrany przez użytkownika dzień lub null, jeśli nic nie zostało wybrane.
   */
  selectedDay: number | null;
  /**
   * Flaga informująca, czy trwa proces komunikacji z API.
   */
  isLoading: boolean;
  /**
   * Komunikat błędu do wyświetlenia w przypadku niepowodzenia operacji.
   */
  error: string | null;
}


