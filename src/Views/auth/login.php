<?php
/** @var string|null $title */
?>
<section class="auth-wrapper">
    <div class="auth-card">
        <h2>Entrar</h2>
        <form method="post" action="?action=authenticate" autocomplete="off">
            <div class="form-group">
                <label for="username">Usuário</label>
                <input type="text" name="username" id="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" class="button">Acessar</button>
        </form>
    </div>
</section>
