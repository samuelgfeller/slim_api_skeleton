<?php


namespace App\Domain\Exceptions;


use App\Domain\Exceptions\AuthenticationException;
use App\Domain\User\User;

class InvalidCredentialsException extends AuthenticationException
{

    // Voluntarily not more information
    private string $userEmail;

    public function __construct(string $email, $message = 'Invalid credentials')
    {
        parent::__construct($message);
        $this->userEmail = $email;
    }

    /**
     * @return string
     */
    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    /**
     * @param string $userEmail
     */
    public function setUserEmail(string $userEmail): void
    {
        $this->userEmail = $userEmail;
    }

}