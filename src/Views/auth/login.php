<?php
/** @var string|null $title */
/** @var string $greeting */
/** @var string|null $loginName */
/** @var string|null $loginUsername */

$displayName = $loginName !== null && $loginName !== '' ? $loginName : null;
$welcomeHeadline = $greeting . ($displayName !== null ? ', ' . $displayName : '!');
$companyContext = isset($GLOBALS['holerite_company']) && $GLOBALS['holerite_company'] instanceof \Holerite\Models\Company
    ? $GLOBALS['holerite_company']
    : null;
$brandName = $companyContext !== null ? $companyContext->getBrandName() : 'JP Fábrica de Salgados';
?>
<section class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-card__header">
            <h2>Entrar</h2>
            <img src="img/logo2.png" alt="<?= htmlspecialchars($brandName); ?>" class="auth-card__logo">
        </div>
        <p class="auth-card__welcome">
            <strong><?= htmlspecialchars($welcomeHeadline) ?></strong>
            <span>
                Bem-vindo<?= $displayName !== null ? ', ' . htmlspecialchars($displayName) : '' ?> ao Sistema de Holerite GO.
            </span>
        </p>
        <form method="post" action="?action=authenticate" autocomplete="off">
            <div class="form-group form-group--with-icon">
                <label for="username">Usuário</label>
                <div class="input-icon">
                    <span class="input-icon__symbol" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false" role="img">
                            <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4 0-7 2-7 4.5V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1.5C19 16 16 14 12 14z" fill="currentColor"/>
                        </svg>
                    </span>
                    <input type="text" name="username" id="username" required autofocus value="<?= htmlspecialchars($loginUsername ?? '') ?>">
                </div>
            </div>
            <div class="form-group form-group--with-icon">
                <label for="password">Senha</label>
                <div class="input-icon">
                    <span class="input-icon__symbol" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false" role="img">
                            <path d="M17 11h-1V8a4 4 0 0 0-8 0v3H7a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1zm-7-3a2 2 0 0 1 4 0v3h-4zm5 11H9v-5h6z" fill="currentColor"/>
                        </svg>
                    </span>
                    <input type="password" name="password" id="password" required>
                </div>
            </div>
            <button type="submit" class="button">Acessar</button>
        </form>
    </div>
</section>
