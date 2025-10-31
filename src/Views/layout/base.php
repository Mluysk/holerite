<?php
/** @var string $content */
/** @var string|null $title */
/** @var string $pageTitle */
/** @var array<int, mixed> $pageScripts */
/** @var array<int, mixed> $pageStyles */

$styleEntries = is_array($pageStyles) ? $pageStyles : [];
$scriptEntries = is_array($pageScripts) ? $pageScripts : [];
$isAuthenticated = isset($_SESSION['user']) && is_array($_SESSION['user']);
$currentUserName = $isAuthenticated ? (string) ($_SESSION['user']['username'] ?? '') : '';
$mainClass = isset($layoutClass) && is_string($layoutClass) && trim($layoutClass) !== ''
    ? $layoutClass
    : 'layout-content';

$themeMode = isset($appearance['themeMode']) && is_string($appearance['themeMode']) ? $appearance['themeMode'] : 'light';
$colorPalette = isset($appearance['colorPalette']) && is_string($appearance['colorPalette']) ? $appearance['colorPalette'] : 'blue';

$themeMode = in_array($themeMode, ['light', 'dark'], true) ? $themeMode : 'light';
$colorPalette = preg_match('/^[a-z0-9_-]+$/', $colorPalette) ? $colorPalette : 'blue';

$bodyClasses = trim('theme-' . $themeMode . ' accent-' . $colorPalette);
$footerYear = (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y');
$footerSignature = sprintf('Holerite GO! © %s Created by Mystic Designer', $footerYear);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? ($title ?? 'Holerite')); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/app.css">
    <?php foreach ($styleEntries as $style): ?>
        <?php if (is_string($style)): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($style); ?>">
        <?php elseif (is_array($style) && isset($style['href'])): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars((string) $style['href']); ?>"
                <?php if (!empty($style['rel'])): ?> rel="<?= htmlspecialchars((string) $style['rel']); ?>"<?php endif; ?>
                <?php if (!empty($style['integrity'])): ?> integrity="<?= htmlspecialchars((string) $style['integrity']); ?>"<?php endif; ?>
                <?php if (!empty($style['crossorigin'])): ?> crossorigin="<?= htmlspecialchars((string) $style['crossorigin']); ?>"<?php endif; ?>
            >
        <?php endif; ?>
    <?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($bodyClasses); ?>" data-theme-mode="<?= htmlspecialchars($themeMode); ?>" data-color-palette="<?= htmlspecialchars($colorPalette); ?>">
<header class="layout-header">
    <div class="brand">
        <img src="img/logo.png" alt="JP Fábrica de Salgados" class="brand-logo">
        <div class="brand-text">
            <h1 class="brand-title">JP Fábrica de Salgados</h1>
            <p class="brand-subtitle">Sistema de Holerite GO v1.0</p>
        </div>
    </div>
    <?php if ($isAuthenticated): ?>
        <div class="layout-header-actions">
            <button type="button"
                    class="layout-nav-toggle"
                    data-nav-toggle
                    aria-expanded="false"
                    aria-controls="mainNavigation"
                    hidden>
                Menu
            </button>
            <nav id="mainNavigation" class="layout-nav is-open" data-nav>
                <a href="?action=dashboard">Dashboard</a>
                <a href="?action=list_employees">Colaboradores</a>
                <a href="?action=list_payrolls">Holerites</a>
                <a href="?action=edit_company">Configurações</a>
            </nav>
            <div class="layout-session">
                <span class="layout-session-user">Olá, <?= htmlspecialchars($currentUserName); ?></span>
                <a class="layout-session-logout" href="?action=logout">Sair</a>
            </div>
        </div>
    <?php endif; ?>
</header>
<main class="<?= htmlspecialchars($mainClass); ?>">
    <?php if (!empty($_SESSION['flash'])): ?>
        <?php foreach ($_SESSION['flash'] as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="flash flash-<?= htmlspecialchars($type); ?>"><?= htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <?= $content; ?>
</main>

<footer class="layout-footer">
    <img src="img/logo3.png" alt="Holerite GO" class="layout-footer-logo">
    <span class="layout-footer-text"><?= htmlspecialchars($footerSignature); ?></span>
</footer>

    <script src="js/navigation.js" defer></script>
    <?php foreach ($scriptEntries as $script): ?>
    <?php if (is_string($script)): ?>
        <script src="<?= htmlspecialchars($script); ?>" defer></script>
    <?php elseif (is_array($script) && isset($script['inline'])): ?>
        <script<?php if (!empty($script['type'])): ?> type="<?= htmlspecialchars((string) $script['type']); ?>"<?php endif; ?>><?= $script['inline']; ?></script>
    <?php elseif (is_array($script) && isset($script['src'])): ?>
        <script src="<?= htmlspecialchars((string) $script['src']); ?>"
            <?php if (!empty($script['type'])): ?> type="<?= htmlspecialchars((string) $script['type']); ?>"<?php endif; ?>
            <?php if (!empty($script['defer'])): ?> defer<?php endif; ?>
            <?php if (!empty($script['async'])): ?> async<?php endif; ?>
            <?php if (!empty($script['integrity'])): ?> integrity="<?= htmlspecialchars((string) $script['integrity']); ?>"<?php endif; ?>
            <?php if (!empty($script['crossorigin'])): ?> crossorigin="<?= htmlspecialchars((string) $script['crossorigin']); ?>"<?php endif; ?>
        ></script>
    <?php endif; ?>
<?php endforeach; ?>
</body>
</html>
