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

        ob_start();
        require $viewPath;
        $content = ob_get_clean() ?: '';

        require $layoutPath;
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
