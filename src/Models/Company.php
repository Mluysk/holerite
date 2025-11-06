<?php

declare(strict_types=1);

namespace Holerite\Models;

final class Company
{
    private ?int $id;

    private string $name = '';

    private string $brandName = '';

    private string $document;

    private string $address;

    private string $city;

    private string $state;

    private string $zipCode;

    private string $phone;

    private string $email;

    private string $themeMode;

    private string $colorPalette;

    private string $headerLogoPath;

    public function __construct(
        ?int $id,
        string $name,
        string $brandName,
        string $document,
        string $address,
        string $city,
        string $state,
        string $zipCode,
        string $phone,
        string $email,
        string $headerLogoPath,
        string $themeMode,
        string $colorPalette,
    ) {
        $this->id = $id;
        $this->setName($name);
        $this->document = $document;
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->zipCode = $zipCode;
        $this->phone = $phone;
        $this->email = $email;
        $this->setBrandName($brandName);
        $this->headerLogoPath = $this->sanitizeHeaderLogoPath($headerLogoPath);
        $this->themeMode = $themeMode;
        $this->colorPalette = $colorPalette;
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
        $name = trim($name);

        if ($name === '') {
            $name = 'Empresa';
        }

        $previousName = $this->name;
        $this->name = $name;

        if ($this->brandName === '' || $this->brandName === $previousName) {
            $this->brandName = $name;
        }
    }

    public function getBrandName(): string
    {
        return $this->brandName !== '' ? $this->brandName : $this->name;
    }

    public function setBrandName(string $brandName): void
    {
        $brandName = trim($brandName);

        if ($brandName === '') {
            $this->brandName = $this->name;
            return;
        }

        $this->brandName = $brandName;
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

    public function getHeaderLogoPath(): string
    {
        return $this->headerLogoPath;
    }

    public function setHeaderLogoPath(string $headerLogoPath): void
    {
        $this->headerLogoPath = $this->sanitizeHeaderLogoPath($headerLogoPath);
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

    private function sanitizeHeaderLogoPath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return 'img/logo.png';
        }

        if (!preg_match('/^[A-Za-z0-9._\/-]+$/', $path)) {
            return 'img/logo.png';
        }

        return $path;
    }
}
