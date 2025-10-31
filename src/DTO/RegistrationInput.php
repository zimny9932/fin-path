<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, minMessage: 'Password should be at least {{ limit }} characters')]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match')]
    public string $passwordConfirmation;
}

