<?php

declare(strict_types=1);

namespace Holerite\Models;

final class Company
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $document,
        private string $address,
        private string $city,
        private string $state,
        private string $zipCode,
        private string $phone,
        private string $email,
        private string $themeMode,
        private string $colorPalette,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function setDocument(string $document): void
    {
        $this->document = $document;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getThemeMode(): string
    {
        return $this->themeMode;
    }

    public function setThemeMode(string $themeMode): void
    {
        $this->themeMode = $themeMode;
    }

    public function getColorPalette(): string
    {
        return $this->colorPalette;
    }

    public function setColorPalette(string $colorPalette): void
    {
        $this->colorPalette = $colorPalette;
    }
}
