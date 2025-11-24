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


