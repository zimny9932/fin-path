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


