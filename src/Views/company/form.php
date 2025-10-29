<?php
/** @var string $title */
/** @var Holerite\Models\Company $company */
/** @var Holerite\Models\User[] $users */
/** @var array{id?: int, username?: string}|null $currentUser */
/** @var array<string, string> $themeModes */
/** @var array<string, string> $colorPalettes */
/** @var string $defaultTab */

$activeUserId = $currentUser['id'] ?? null;
$activeUsername = isset($currentUser['username']) ? (string) $currentUser['username'] : '';
$selectedThemeMode = $company->getThemeMode();
$selectedColorPalette = $company->getColorPalette();
$defaultTab = isset($defaultTab) && is_string($defaultTab) ? $defaultTab : 'company';
$allowedTabs = ['company', 'appearance', 'password', 'users'];
if (!in_array($defaultTab, $allowedTabs, true)) {
    $defaultTab = 'company';
}
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;">Configurações do sistema</h2>
        <p class="muted">Personalize os dados da empresa e gerencie o acesso dos usuários.</p>
    </header>

    <div class="tab-container" data-default-tab="<?= htmlspecialchars($defaultTab); ?>">
        <div class="tab-nav">
            <button type="button" class="tab-button<?php if ($defaultTab === 'company'): ?> active<?php endif; ?>" data-tab="company">Dados da empresa</button>
            <button type="button" class="tab-button<?php if ($defaultTab === 'appearance'): ?> active<?php endif; ?>" data-tab="appearance">Aparência</button>
            <button type="button" class="tab-button<?php if ($defaultTab === 'password'): ?> active<?php endif; ?>" data-tab="password">Alterar senha</button>
            <button type="button" class="tab-button<?php if ($defaultTab === 'users'): ?> active<?php endif; ?>" data-tab="users">Usuários</button>
        </div>

        <div class="tab-content<?php if ($defaultTab === 'company'): ?> active<?php endif; ?>" id="tab-company">
            <div class="card">
                <h3 style="margin-top:0;">Identidade da empresa</h3>
                <p class="muted" style="margin-top:0;">Essas informações aparecerão em todos os holerites emitidos.</p>

                <form method="post" action="?action=update_company" style="margin-top:1rem;">
                    <div class="grid">
                        <div>
                            <label for="name">Razão social</label>
                            <input type="text" name="name" id="name" value="<?= htmlspecialchars($company->getName()); ?>" required>
                        </div>
                        <div>
                            <label for="document">CNPJ</label>
                            <input type="text" name="document" id="document" value="<?= htmlspecialchars($company->getDocument()); ?>" required>
                        </div>
                    </div>

                    <div class="grid">
                        <div>
                            <label for="address">Endereço</label>
                            <input type="text" name="address" id="address" value="<?= htmlspecialchars($company->getAddress()); ?>" required>
                        </div>
                        <div>
                            <label for="zip_code">CEP</label>
                            <input type="text" name="zip_code" id="zip_code" value="<?= htmlspecialchars($company->getZipCode()); ?>" required>
                        </div>
                    </div>

                    <div class="grid">
                        <div>
                            <label for="city">Cidade</label>
                            <input type="text" name="city" id="city" value="<?= htmlspecialchars($company->getCity()); ?>" required>
                        </div>
                        <div>
                            <label for="state">UF</label>
                            <input type="text" name="state" id="state" value="<?= htmlspecialchars($company->getState()); ?>" maxlength="2" required>
                        </div>
                    </div>

                    <div class="grid">
                        <div>
                            <label for="phone">Telefone</label>
                            <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($company->getPhone()); ?>" required>
                        </div>
                        <div>
                            <label for="email">E-mail</label>
                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($company->getEmail()); ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="button">Salvar dados da empresa</button>
                </form>
            </div>
        </div>

        <div class="tab-content<?php if ($defaultTab === 'appearance'): ?> active<?php endif; ?>" id="tab-appearance">
            <div class="card">
                <h3 style="margin-top:0;">Modo de exibição</h3>
                <p class="muted" style="margin-top:0;">Defina se o painel deve usar tema claro ou escuro para todos os usuários.</p>

                <form method="post" action="?action=update_theme_mode" style="margin-top:1rem;" data-theme-mode-form>
                    <div class="appearance-options appearance-options--pills" data-theme-options>
                        <?php foreach ($themeModes as $key => $label): ?>
                            <?php $themeId = 'theme-mode-' . $key; ?>
                            <div class="option-pill">
                                <input type="radio" name="theme_mode" id="<?= htmlspecialchars($themeId); ?>" value="<?= htmlspecialchars($key); ?>" <?php if ($selectedThemeMode === $key): ?>checked<?php endif; ?>>
                                <label for="<?= htmlspecialchars($themeId); ?>"><?= htmlspecialchars($label); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="button">Aplicar modo</button>
                </form>
            </div>

            <div class="card" style="margin-top:1.5rem;">
                <h3 style="margin-top:0;">Paleta de cores</h3>
                <p class="muted" style="margin-top:0;">Escolha a cor principal que será utilizada nos destaques do sistema.</p>

                <form method="post" action="?action=update_color_palette" style="margin-top:1rem;" data-color-palette-form>
                    <div class="appearance-options appearance-options--swatches" data-palette-options>
                        <?php foreach ($colorPalettes as $key => $label): ?>
                            <?php $paletteId = 'palette-' . $key; ?>
                            <div class="swatch-option" data-palette-swatch="<?= htmlspecialchars($key); ?>">
                                <input type="radio" name="color_palette" id="<?= htmlspecialchars($paletteId); ?>" value="<?= htmlspecialchars($key); ?>" <?php if ($selectedColorPalette === $key): ?>checked<?php endif; ?>>
                                <label for="<?= htmlspecialchars($paletteId); ?>">
                                    <span class="swatch"></span>
                                    <span class="swatch-label"><?= htmlspecialchars($label); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="button">Aplicar paleta</button>
                </form>
            </div>
        </div>

        <div class="tab-content<?php if ($defaultTab === 'password'): ?> active<?php endif; ?>" id="tab-password">
            <div class="card">
                <h3 style="margin-top:0;">Editar nome de usuário</h3>
                <p class="muted" style="margin-top:0;">Atualize como você aparece ao entrar no sistema.</p>

                <form method="post" action="?action=update_username" style="margin-top:1rem;">
                    <div class="grid">
                        <div>
                            <label for="edit_username">Nome de usuário</label>
                            <input type="text" name="username" id="edit_username" minlength="3" value="<?= htmlspecialchars($activeUsername); ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="button">Salvar nome de usuário</button>
                </form>
            </div>

            <div class="card" style="margin-top:1.5rem;">
                <h3 style="margin-top:0;">Atualize sua senha</h3>
                <p class="muted" style="margin-top:0;">Altere a senha utilizada pelo usuário <strong><?= htmlspecialchars($activeUsername); ?></strong> para manter o acesso seguro.</p>

                <form method="post" action="?action=update_password" style="margin-top:1rem;">
                    <div class="grid">
                        <div>
                            <label for="current_password">Senha atual</label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        <div>
                            <label for="new_password">Nova senha</label>
                            <input type="password" name="new_password" id="new_password" minlength="6" required>
                        </div>
                        <div>
                            <label for="confirm_password">Confirme a nova senha</label>
                            <input type="password" name="confirm_password" id="confirm_password" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="button">Atualizar senha</button>
                </form>
            </div>
        </div>

        <div class="tab-content<?php if ($defaultTab === 'users'): ?> active<?php endif; ?>" id="tab-users">
            <div class="card">
                <h3 style="margin-top:0;">Contas de acesso</h3>
                <p class="muted" style="margin-top:0;">Crie novos usuários para compartilhar o sistema com outros responsáveis.</p>

                <form method="post" action="?action=create_user" style="margin-top:1rem;">
                    <div class="grid">
                        <div>
                            <label for="username">Nome de usuário</label>
                            <input type="text" name="username" id="username" minlength="3" required>
                        </div>
                        <div>
                            <label for="user_password">Senha</label>
                            <input type="password" name="password" id="user_password" minlength="6" required>
                        </div>
                        <div>
                            <label for="user_confirm_password">Confirmar senha</label>
                            <input type="password" name="confirm_password" id="user_confirm_password" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="button">Criar novo usuário</button>
                </form>
            </div>

            <div class="card" style="margin-top:1.5rem;">
                <h3 style="margin-top:0;">Usuários cadastrados</h3>
                <?php if ($users === []): ?>
                    <p class="muted">Nenhum usuário adicional cadastrado.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user->getUsername()); ?></td>
                                    <td>
                                        <?php if ($activeUserId !== null && $user->getId() === (int) $activeUserId): ?>
                                            <span class="muted">Sessão ativa</span>
                                        <?php else: ?>
                                            <span class="muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
