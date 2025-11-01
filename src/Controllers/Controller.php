<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use RuntimeException;

abstract class Controller
{
    protected function render(string $view, array $params = []): void
    {
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        $layoutPath = __DIR__ . '/../Views/layout/base.php';

        if (!file_exists($viewPath)) {
            throw new RuntimeException(sprintf('View "%s" não encontrada.', $view));
        }

        if (!file_exists($layoutPath)) {
            throw new RuntimeException('Layout padrão não encontrado.');
        }

        $pageTitle = $params['title'] ?? 'Holerite';
        $pageScripts = $params['pageScripts'] ?? [];
        $pageStyles = $params['pageStyles'] ?? [];
        $layoutClass = $params['layoutClass'] ?? 'layout-content';
        $appearanceParam = $params['appearance'] ?? null;

        extract($params, EXTR_OVERWRITE);

        if (!isset($pageTitle) || !is_string($pageTitle) || $pageTitle === '') {
            $pageTitle = isset($title) && is_string($title) && $title !== '' ? $title : 'Holerite';
        }

        if (!is_array($pageScripts)) {
            $pageScripts = [];
        }

        if (!is_array($pageStyles)) {
            $pageStyles = [];
        }

        if (!isset($layoutClass) || !is_string($layoutClass) || trim($layoutClass) === '') {
            $layoutClass = 'layout-content';
        }

        $appearance = $this->resolveAppearance($appearanceParam ?? ($GLOBALS['holerite_appearance'] ?? null));

        ob_start();
        require $viewPath;
        $content = ob_get_clean() ?: '';

        require $layoutPath;
    }

    /**
     * @param mixed $source
     * @return array{themeMode: string, colorPalette: string}
     */
    private function resolveAppearance($source): array
    {
        $defaults = [
            'themeMode' => 'light',
            'colorPalette' => 'dark-red',
        ];

        if (!is_array($source)) {
            return $defaults;
        }

        $theme = $source['themeMode'] ?? $source['theme_mode'] ?? $defaults['themeMode'];
        $palette = $source['colorPalette'] ?? $source['color_palette'] ?? $defaults['colorPalette'];

        $theme = is_string($theme) ? strtolower($theme) : $defaults['themeMode'];
        $palette = is_string($palette) ? strtolower($palette) : $defaults['colorPalette'];

        $allowedThemes = ['light', 'dark'];
        $allowedPalettes = [
            'blue',
            'emerald',
            'violet',
            'amber',
            'rose',
            'black',
            'gray',
            'red',
            'dark-red',
            'pink',
            'yellow',
            'gold',
            'rgb',
            'light-blue',
            'dark-blue',
            'wine',
        ];

        if (!in_array($theme, $allowedThemes, true)) {
            $theme = $defaults['themeMode'];
        }

        if (!in_array($palette, $allowedPalettes, true)) {
            $palette = $defaults['colorPalette'];
        }

        return [
            'themeMode' => $theme,
            'colorPalette' => $palette,
        ];
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }

        if (!isset($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [];
        }

        $_SESSION['flash'][$type][] = $message;
    }
}
