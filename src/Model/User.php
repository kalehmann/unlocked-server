<?php

declare(strict_types=1);

namespace KaLehmann\UnlockedServer\Model;

class User
{
    private string $email;

    private string $handle;

    private ?string $mobile;

    private string $password;

    public function __construct(string $handle)
    {
        $this->handle = $handle;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
