<?php
/** @var string|null $title */
/** @var string $greeting */
/** @var string|null $loginName */
/** @var string|null $loginUsername */

$displayName = $loginName !== null && $loginName !== '' ? $loginName : null;
$welcomeHeadline = $greeting . ($displayName !== null ? ', ' . $displayName : '!');
?>
<section class="auth-wrapper">
    <div class="auth-brand" aria-label="Logos do sistema">
        <img src="img/logo.png" alt="JP Fábrica de Salgados" class="auth-brand__logo auth-brand__logo--primary">
        <span class="auth-brand__separator" aria-hidden="true">&bull;</span>
        <img src="img/logo3.png" alt="Holerite GO" class="auth-brand__logo auth-brand__logo--secondary">
    </div>
    <div class="auth-card">
        <div class="auth-card__header">
            <h2>Entrar</h2>
            <img src="img/logo2.png" alt="JP Fábrica de Salgados" class="auth-card__logo">
        </div>
        <p class="auth-card__welcome">
            <strong><?= htmlspecialchars($welcomeHeadline) ?></strong>
            <span>
                Bem-vindo<?= $displayName !== null ? ', ' . htmlspecialchars($displayName) : '' ?> ao Sistema de Holerite GO.
            </span>
        </p>
        <form method="post" action="?action=authenticate" autocomplete="off" data-auth-form>
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
        <div class="auth-card__loading" data-auth-loading hidden>
            <div class="auth-card__spinner" role="status" aria-live="polite" aria-label="Entrando"></div>
            <p>Entrando...</p>
        </div>
    </div>
</section>
