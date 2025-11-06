<?php
/** @var string $title */
/** @var Holerite\Models\Company $company */
/** @var Holerite\Models\ContributionSettings $contributionSettings */
/** @var Holerite\Models\User[] $users */
/** @var array{id?: int, username?: string, role?: string, theme_mode?: string, color_palette?: string}|null $currentUser */
/** @var array<string, string> $themeModes */
/** @var array<string, string> $colorPalettes */
/** @var array<string, string> $userRoles */
/** @var string $defaultTab */
/** @var string[] $availableTabs */
/** @var bool $isAdmin */
/** @var string $userThemeMode */
/** @var string $userColorPalette */
/** @var string $companyThemeMode */
/** @var string $companyColorPalette */
/** @var array<string, mixed> $contributionApi */
/** @var Holerite\Models\AuditLog[] $auditLogs */

use Holerite\Models\AuditLog;
use Holerite\Models\User;

$activeUserId = $currentUser['id'] ?? null;
$activeUsername = isset($currentUser['username']) ? (string) $currentUser['username'] : '';
$selectedThemeMode = isset($userThemeMode) && is_string($userThemeMode) ? $userThemeMode : $company->getThemeMode();
$selectedColorPalette = isset($userColorPalette) && is_string($userColorPalette) ? $userColorPalette : $company->getColorPalette();
$companyThemeMode = isset($companyThemeMode) && is_string($companyThemeMode) ? $companyThemeMode : $company->getThemeMode();
$companyColorPalette = isset($companyColorPalette) && is_string($companyColorPalette) ? $companyColorPalette : $company->getColorPalette();
$defaultTab = isset($defaultTab) && is_string($defaultTab) ? $defaultTab : 'company';
$availableTabs = isset($availableTabs) && is_array($availableTabs) ? $availableTabs : ['company', 'appearance', 'password', 'users', 'backups'];
if (!in_array($defaultTab, $availableTabs, true)) {
    $defaultTab = in_array('company', $availableTabs, true) ? 'company' : (in_array('password', $availableTabs, true) ? 'password' : $availableTabs[0]);
}
$fgtsRatePercent = number_format($contributionSettings->getFgtsRatePercent(), 2, '.', '');
$inssBrackets = $contributionSettings->getInssBrackets();
$irrfBrackets = $contributionSettings->getIrrfBrackets();
$inssFormRows = array_values(array_merge($inssBrackets, [['limit' => null, 'rate' => null]]));
$irrfFormRows = array_values(array_merge($irrfBrackets, [['limit' => null, 'rate' => null, 'deduction' => null]]));
$contributionsUpdatedAt = $contributionSettings->getUpdatedAt();
$contributionApi = isset($contributionApi) && is_array($contributionApi) ? $contributionApi : [];
$apiEnabled = !empty($contributionApi['enabled']);
$apiEndpoint = trim((string) ($contributionApi['endpoint'] ?? ''));
$apiProvider = trim((string) ($contributionApi['provider'] ?? ''));
$apiHasToken = !empty($contributionApi['hasToken']);
$apiTimeout = isset($contributionApi['timeout']) ? (int) $contributionApi['timeout'] : 10;
$auditLogs = isset($auditLogs) && is_array($auditLogs) ? $auditLogs : [];
$manualAllowanceOptions = $contributionSettings->getManualAllowances();
if ($manualAllowanceOptions === []) {
    $manualAllowanceOptions = [''];
}
$brandNameValue = $company->getBrandName();
$headerLogoPath = $company->getHeaderLogoPath();
?>
<section>
    <header style="margin-bottom:1.5rem;">
        <h2 style="margin:0 0 0.25rem 0;">Configurações do sistema</h2>
        <p class="muted">
            <?php if (!empty($isAdmin)): ?>
                Personalize os dados da empresa e gerencie o acesso dos usuários.
            <?php else: ?>
                Atualize as informações da sua conta e mantenha seus dados de acesso seguros.
            <?php endif; ?>
        </p>
    </header>

    <div class="tab-container" data-default-tab="<?= htmlspecialchars($defaultTab); ?>">
        <div class="tab-nav">
            <?php if (in_array('company', $availableTabs, true)): ?>
                <button type="button" class="tab-button<?php if ($defaultTab === 'company'): ?> active<?php endif; ?>" data-tab="company">Dados da empresa</button>
            <?php endif; ?>
            <?php if (in_array('appearance', $availableTabs, true)): ?>
                <button type="button" class="tab-button<?php if ($defaultTab === 'appearance'): ?> active<?php endif; ?>" data-tab="appearance">Aparência</button>
            <?php endif; ?>
            <?php if (in_array('discounts', $availableTabs, true)): ?>
                <button type="button" class="tab-button<?php if ($defaultTab === 'discounts'): ?> active<?php endif; ?>" data-tab="discounts">Ajuste de descontos</button>
            <?php endif; ?>
            <?php if (in_array('password', $availableTabs, true)): ?>
                <button type="button" class="tab-button<?php if ($defaultTab === 'password'): ?> active<?php endif; ?>" data-tab="password">Segurança</button>
            <?php endif; ?>
            <?php if (in_array('users', $availableTabs, true)): ?>
                <button type="button" class="tab-button<?php if ($defaultTab === 'users'): ?> active<?php endif; ?>" data-tab="users">Usuários</button>
            <?php endif; ?>
            <?php if (in_array('backups', $availableTabs, true)): ?>
                <button type="button" class="tab-button<?php if ($defaultTab === 'backups'): ?> active<?php endif; ?>" data-tab="backups">Backups</button>
            <?php endif; ?>
        </div>

        <?php if (in_array('company', $availableTabs, true)): ?>
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
        <?php endif; ?>

        <?php if (in_array('appearance', $availableTabs, true)): ?>
        <div class="tab-content<?php if ($defaultTab === 'appearance'): ?> active<?php endif; ?>" id="tab-appearance">
            <?php if (!empty($isAdmin)): ?>
            <div class="card">
                <h3 style="margin-top:0;">Identidade visual</h3>
                <p class="muted" style="margin-top:0;">Atualize o nome exibido e a logo do cabeçalho para refletir a marca da empresa.</p>

                <form method="post" action="?action=update_branding" style="margin-top:1rem;" enctype="multipart/form-data">
                    <div class="grid">
                        <div>
                            <label for="brand_name">Nome exibido no cabeçalho</label>
                            <input type="text" name="brand_name" id="brand_name" value="<?= htmlspecialchars($brandNameValue); ?>" maxlength="255">
                            <small class="muted">Esse texto aparece ao lado da logo na barra superior e no login.</small>
                        </div>
                        <div>
                            <label for="header_logo">Logo do cabeçalho</label>
                            <div class="company-logo-field">
                                <div class="company-logo-field__preview" aria-hidden="true">
                                    <img src="<?= htmlspecialchars($headerLogoPath); ?>" alt="Pré-visualização da logo" data-header-logo-preview data-default-src="<?= htmlspecialchars($headerLogoPath); ?>">
                                </div>
                                <div class="input-icon input-icon--file company-logo-field__input">
                                    <i class="bi bi-image-fill" aria-hidden="true"></i>
                                    <input type="file" name="header_logo" id="header_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                </div>
                                <small class="muted">Formatos aceitos: PNG, JPG, WEBP ou SVG com até 2&nbsp;MB.</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="button">Salvar identidade visual</button>
                </form>
            </div>
            <?php else: ?>
            <div class="card">
                <h3 style="margin-top:0;">Identidade visual</h3>
                <p class="muted" style="margin-top:0;">Visualize como a marca da empresa aparece para todos os usuários.</p>
                <div class="company-logo-field" style="margin-top:1rem;">
                    <div class="company-logo-field__preview" aria-hidden="true" style="max-width:180px;">
                        <img src="<?= htmlspecialchars($headerLogoPath); ?>" alt="Logo atual da empresa">
                    </div>
                    <dl style="margin:1rem 0 0;">
                        <dt style="font-weight:600;">Nome exibido</dt>
                        <dd style="margin:0.25rem 0 0;"><?= htmlspecialchars($brandNameValue !== '' ? $brandNameValue : $company->getName()); ?></dd>
                    </dl>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <h3 style="margin-top:0;">Modo de exibição</h3>
                <p class="muted" style="margin-top:0;">Personalize se a sua conta utiliza o modo claro ou escuro ao navegar pelo sistema.</p>
                <?php if (!empty($isAdmin)): ?>
                    <p class="muted" style="margin:0.5rem 0 0 0;">
                        Padrão da empresa: <strong><?= htmlspecialchars($themeModes[$companyThemeMode] ?? ucfirst($companyThemeMode)); ?></strong>
                    </p>
                <?php endif; ?>

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
                <p class="muted" style="margin-top:0;">Escolha a cor de destaque que será aplicada somente à sua conta.</p>
                <?php if (!empty($isAdmin)): ?>
                    <p class="muted" style="margin:0.5rem 0 0 0;">
                        Paleta padrão da empresa: <strong><?= htmlspecialchars($colorPalettes[$companyColorPalette] ?? ucfirst($companyColorPalette)); ?></strong>
                    </p>
                <?php endif; ?>

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
        <?php endif; ?>

        <?php if (in_array('discounts', $availableTabs, true)): ?>
        <div class="tab-content<?php if ($defaultTab === 'discounts'): ?> active<?php endif; ?>" id="tab-discounts">
            <div class="card">
                <h3 style="margin-top:0;">Tabelas de descontos e contribuições</h3>
                <p class="muted" style="margin-top:0;">Atualize as alíquotas de FGTS, INSS e IRRF conforme as mudanças legais. Deixe o limite em branco para representar a faixa acima do teto.</p>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-top:1.25rem;padding:0.75rem 1rem;border:1px solid var(--color-border);border-radius:12px;background:var(--color-surface-soft);">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem;min-width:240px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:rgba(var(--color-primary-rgb),0.12);color:var(--color-primary);"><i class="bi bi-cloud-arrow-down" aria-hidden="true"></i></span>
                        <div style="flex:1;min-width:200px;">
                            <strong style="display:block;margin-bottom:0.25rem;">Atualização automática</strong>
                            <?php if ($apiEnabled): ?>
                                <p class="muted" style="margin:0;">Sincronize as tabelas oficiais com um clique.<?php if ($apiProvider !== ''): ?> Fonte: <strong><?= htmlspecialchars($apiProvider); ?></strong>.<?php endif; ?></p>
                                <?php if ($apiEndpoint !== ''): ?>
                                    <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem;word-break:break-all;">Endpoint: <span style="font-family:var(--font-mono, monospace);"><?= htmlspecialchars($apiEndpoint); ?></span></p>
                                <?php endif; ?>
                                <p class="muted" style="margin:0.35rem 0 0;font-size:0.85rem;">Tempo limite: <?= htmlspecialchars((string) $apiTimeout); ?>s · Token <?= $apiHasToken ? '<span class="badge" style="background:var(--color-primary);color:#fff;padding:0.1rem 0.5rem;border-radius:999px;font-size:0.7rem;">configurado</span>' : '<span class="badge" style="background:var(--color-surface-alt);color:var(--color-text-muted);padding:0.1rem 0.5rem;border-radius:999px;font-size:0.7rem;">opcional</span>'; ?></p>
                            <?php else: ?>
                                <p class="muted" style="margin:0;">Informe o endpoint da API em <code>config/config.php</code> para habilitar a sincronização automática das alíquotas.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <?php if ($apiEnabled): ?>
                            <form method="post" action="?action=sync_contributions" style="margin:0;">
                                <button type="submit" class="button button-secondary">
                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Buscar na API
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="muted" style="max-width:260px;font-size:0.9rem;">Sem endpoint configurado. Ajuste manualmente ou cadastre uma API para automatizar.</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($contributionsUpdatedAt instanceof DateTimeImmutable): ?>
                    <p class="muted" style="margin:0.5rem 0 0;">Última atualização: <strong><?= htmlspecialchars($contributionsUpdatedAt->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i')); ?></strong></p>
                <?php endif; ?>

                <form method="post" action="?action=update_contributions" style="margin-top:1.5rem; display:flex; flex-direction:column; gap:1.5rem;">
                    <section>
                        <h4 style="margin:0 0 0.5rem;">FGTS</h4>
                        <p class="muted" style="margin:0 0 1rem;">Informe a alíquota padrão do FGTS aplicada aos vínculos ativos.</p>
                        <label for="fgts_rate">Alíquota (%):</label>
                        <input type="number" id="fgts_rate" name="fgts_rate" min="0" max="100" step="0.01" value="<?= htmlspecialchars($fgtsRatePercent); ?>" required>
                    </section>

                    <section>
                        <h4 style="margin:0 0 0.5rem;">Tabela do INSS</h4>
                        <p class="muted" style="margin:0 0 1rem;">Defina as faixas salariais progressivas e suas alíquotas. A última linha em branco permite incluir novas faixas quando necessário.</p>
                        <div class="table-wrapper" style="overflow-x:auto;">
                            <table class="table" style="min-width:540px;">
                                <thead>
                                    <tr>
                                        <th style="width:15%;">Faixa</th>
                                        <th style="width:35%;">Limite (R$)</th>
                                        <th style="width:35%;">Alíquota (%)</th>
                                        <th style="width:15%;">Observação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inssFormRows as $index => $row): ?>
                                        <?php $isFinal = $row['limit'] === null && isset($row['rate']) && $row['rate'] !== null; ?>
                                        <tr>
                                            <td style="text-align:center;">Faixa <?= htmlspecialchars((string) ($index + 1)); ?></td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="inss[<?= $index; ?>][limit]" value="<?= $row['limit'] !== null ? htmlspecialchars(number_format((float) $row['limit'], 2, '.', '')) : ''; ?>" placeholder="Informe o teto da faixa">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100" name="inss[<?= $index; ?>][rate]" value="<?= isset($row['rate']) ? htmlspecialchars(number_format((float) $row['rate'] * 100, 2, '.', '')) : ''; ?>" placeholder="Ex.: 7.50">
                                            </td>
                                            <td style="text-align:center;">
                                                <?php if ($isFinal): ?>
                                                    <span class="badge" style="display:inline-block;padding:0.25rem 0.5rem;border-radius:999px;background:var(--accent-muted);color:var(--accent-strong);font-size:0.75rem;">Acima do teto</span>
                                                <?php else: ?>
                                                    <span class="muted" style="font-size:0.75rem;">Até o limite indicado</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section>
                        <h4 style="margin:0 0 0.5rem;">Tabela do IRRF</h4>
                        <p class="muted" style="margin:0 0 1rem;">Informe as faixas do Imposto de Renda Retido na Fonte, com alíquota e parcela a deduzir.</p>
                        <div class="table-wrapper" style="overflow-x:auto;">
                            <table class="table" style="min-width:640px;">
                                <thead>
                                    <tr>
                                        <th style="width:15%;">Faixa</th>
                                        <th style="width:30%;">Limite (R$)</th>
                                        <th style="width:25%;">Alíquota (%)</th>
                                        <th style="width:30%;">Parcela a deduzir (R$)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($irrfFormRows as $index => $row): ?>
                                        <?php $isFinal = $row['limit'] === null && isset($row['rate']) && $row['rate'] !== null; ?>
                                        <tr>
                                            <td style="text-align:center;">Faixa <?= htmlspecialchars((string) ($index + 1)); ?></td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="irrf[<?= $index; ?>][limit]" value="<?= $row['limit'] !== null ? htmlspecialchars(number_format((float) $row['limit'], 2, '.', '')) : ''; ?>" placeholder="Informe o teto da faixa">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100" name="irrf[<?= $index; ?>][rate]" value="<?= isset($row['rate']) ? htmlspecialchars(number_format((float) $row['rate'] * 100, 2, '.', '')) : ''; ?>" placeholder="Ex.: 7.50">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="irrf[<?= $index; ?>][deduction]" value="<?= isset($row['deduction']) ? htmlspecialchars(number_format((float) $row['deduction'], 2, '.', '')) : ''; ?>" placeholder="Ex.: 142.80">
                                                <?php if ($isFinal): ?>
                                                    <span class="muted" style="display:block;font-size:0.75rem;margin-top:0.25rem;">Aplicada aos valores acima do teto</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section>
                        <h4 style="margin:0 0 0.5rem;">Descrições padrão de proventos</h4>
                        <p class="muted" style="margin:0 0 1rem;">Cadastre as descrições exibidas no formulário de holerites para proventos manuais.</p>
                        <div class="allowance-settings" data-allowance-list>
                            <?php foreach ($manualAllowanceOptions as $option): ?>
                                <div class="allowance-settings__row" data-allowance-row>
                                    <input type="text" name="manual_allowances[]" value="<?= htmlspecialchars($option); ?>" placeholder="Ex.: Hora extra 50%">
                                    <button type="button" class="button button-secondary" data-remove-allowance>
                                        <i class="bi bi-x-circle"></i>
                                        <span>Remover</span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;justify-content:flex-start;margin-top:0.5rem;">
                            <button type="button" class="button button-secondary" data-add-allowance>
                                <i class="bi bi-plus-circle"></i> Adicionar descrição
                            </button>
                        </div>
                        <p class="muted" style="margin:0.75rem 0 0;">As opções definidas aqui aparecem na lista suspensa de proventos manuais ao gerar um holerite.</p>
                    </section>

                    <button type="submit" class="button">Salvar ajustes de descontos</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array('password', $availableTabs, true)): ?>
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

            <?php if (!empty($isAdmin)): ?>
                <div class="card" style="margin-top:1.5rem;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                        <div>
                            <h3 style="margin:0;">Auditoria do sistema</h3>
                            <p class="muted" style="margin:0.25rem 0 0 0;">Acompanhe as últimas ações administrativas registradas no sistema.</p>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <?php if ($auditLogs !== []): ?>
                                <button type="button" class="button button-secondary" data-print-audit data-print-target="audit-log-print" title="Imprimir auditoria">
                                    <i class="bi bi-printer" aria-hidden="true" style="margin-right:0.35rem;"></i>
                                    <span>Imprimir</span>
                                </button>
                            <?php endif; ?>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:rgba(var(--color-primary-rgb),0.12);color:var(--color-primary);">
                                <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                            </span>
                        </div>
                    </div>

                    <?php if ($auditLogs === []): ?>
                        <p class="muted" style="margin-top:1rem;">Nenhum evento de auditoria foi registrado até o momento.</p>
                    <?php else: ?>
                        <?php
                            $auditTimezone = new DateTimeZone('America/Sao_Paulo');
                            $auditGeneratedAt = new DateTimeImmutable('now', $auditTimezone);
                        ?>
                        <div
                            class="table-responsive audit-log__table"
                            id="audit-log-print"
                            style="margin-top:1rem;"
                            data-company-name="<?= htmlspecialchars($company->getName()); ?>"
                            data-company-document="<?= htmlspecialchars($company->getDocument()); ?>"
                            data-generated-at="<?= htmlspecialchars($auditGeneratedAt->format('d/m/Y H:i')); ?>"
                        >
                            <table>
                                <thead>
                                <tr>
                                    <th style="width:20%;">Data</th>
                                    <th style="width:20%;">Responsável</th>
                                    <th style="width:20%;">Ação</th>
                                    <th>Detalhes</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($auditLogs as $log): ?>
                                    <?php if (!$log instanceof AuditLog) { continue; } ?>
                                    <?php $loggedAt = $log->getCreatedAt()->setTimezone($auditTimezone); ?>
                                    <tr>
                                        <td><?= $loggedAt->format('d/m/Y H:i'); ?></td>
                                        <td><?= htmlspecialchars($log->getUsername() ?? 'Administrador'); ?></td>
                                        <td><?= htmlspecialchars($log->getAction()); ?></td>
                                        <td><?= htmlspecialchars($log->getDescription()); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (in_array('users', $availableTabs, true)): ?>
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
                        <div>
                            <label for="user_role">Perfil de acesso</label>
                            <select name="role" id="user_role" required>
                                <?php foreach ($userRoles as $roleKey => $label): ?>
                                    <option value="<?= htmlspecialchars($roleKey); ?>"<?php if ($roleKey === User::ROLE_OPERATOR): ?> selected<?php endif; ?>>
                                        <?= htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                    <div class="table-responsive">
                        <table>
                            <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Perfil</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user->getUsername()); ?></td>
                                    <td>
                                        <?php if ($user->getRole() === User::ROLE_ADMINISTRATOR): ?>
                                            <span class="tag tag--admin">Administrador</span>
                                        <?php else: ?>
                                            <span class="tag">Operador</span>
                                        <?php endif; ?>
                                    </td>
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
        <?php endif; ?>

        <?php if (in_array('backups', $availableTabs, true)): ?>
        <div class="tab-content<?php if ($defaultTab === 'backups'): ?> active<?php endif; ?>" id="tab-backups">
            <div class="card">
                <h3 style="margin-top:0;">Backups do sistema</h3>
                <p class="muted" style="margin-top:0;">Gere arquivos JSON com uma cópia dos dados principais e faça upload aqui quando precisar restaurar.</p>

                <div class="backup-actions">
                    <div class="backup-option">
                        <div class="backup-option__icon" aria-hidden="true"><i class="bi bi-database-fill-down"></i></div>
                        <div class="backup-option__body">
                            <h4>Banco de dados completo</h4>
                            <p class="muted">Exporta colaboradores, holerites e configurações persistidas no MySQL.</p>
                            <form method="post" action="?action=backup_database">
                                <button type="submit" class="button">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    <span>Baixar backup do banco</span>
                                </button>
                            </form>
                            <form method="post" action="?action=restore_database" enctype="multipart/form-data" class="backup-upload" data-backup-form data-backup-type="backup do banco">
                                <label for="database_backup" class="backup-upload__label">Arquivo de backup do banco</label>
                                <div class="input-icon input-icon--file">
                                    <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                                    <input type="file" name="database_backup" id="database_backup" accept="application/json" aria-label="Selecionar backup do banco" required>
                                </div>
                                <label for="database_restore_password" class="backup-upload__label">Senha de administrador</label>
                                <div class="input-icon input-icon--password">
                                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                    <input type="password" name="restore_password" id="database_restore_password" placeholder="Confirme a senha de administrador" autocomplete="current-password" required>
                                </div>
                                <small class="muted">Você precisará digitar a senha de administrador para concluir a restauração.</small>
                                <button type="submit" class="button button-secondary">
                                    <i class="bi bi-upload" aria-hidden="true"></i>
                                    <span>Restaurar backup do banco</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="backup-option">
                        <div class="backup-option__icon" aria-hidden="true"><i class="bi bi-gear-fill"></i></div>
                        <div class="backup-option__body">
                            <h4>Configurações do sistema</h4>
                            <p class="muted">Gera um snapshot com o arquivo de configuração e dados atuais da empresa.</p>
                            <form method="post" action="?action=backup_configuration">
                                <button type="submit" class="button">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    <span>Baixar backup de configuração</span>
                                </button>
                            </form>
                            <form method="post" action="?action=restore_configuration" enctype="multipart/form-data" class="backup-upload" data-backup-form data-backup-type="configuração">
                                <label for="configuration_backup" class="backup-upload__label">Arquivo de backup de configuração</label>
                                <div class="input-icon input-icon--file">
                                    <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                                    <input type="file" name="configuration_backup" id="configuration_backup" accept="application/json" aria-label="Selecionar backup de configuração" required>
                                </div>
                                <label for="configuration_restore_password" class="backup-upload__label">Senha de administrador</label>
                                <div class="input-icon input-icon--password">
                                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                    <input type="password" name="restore_password" id="configuration_restore_password" placeholder="Confirme a senha de administrador" autocomplete="current-password" required>
                                </div>
                                <small class="muted">Digite a senha de administrador para continuar.</small>
                                <button type="submit" class="button button-secondary">
                                    <i class="bi bi-upload" aria-hidden="true"></i>
                                    <span>Restaurar configuração</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="backup-option">
                        <div class="backup-option__icon" aria-hidden="true"><i class="bi bi-people-fill"></i></div>
                        <div class="backup-option__body">
                            <h4>Contas de acesso</h4>
                            <p class="muted">Lista usuários cadastrados com seus perfis para restauração futura.</p>
                            <form method="post" action="?action=backup_users">
                                <button type="submit" class="button">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    <span>Baixar usuários</span>
                                </button>
                            </form>
                            <form method="post" action="?action=restore_users" enctype="multipart/form-data" class="backup-upload" data-backup-form data-backup-type="lista de usuários">
                                <label for="users_backup" class="backup-upload__label">Arquivo de backup de usuários</label>
                                <div class="input-icon input-icon--file">
                                    <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                                    <input type="file" name="users_backup" id="users_backup" accept="application/json" aria-label="Selecionar backup de usuários" required>
                                </div>
                                <label for="users_restore_password" class="backup-upload__label">Senha de administrador</label>
                                <div class="input-icon input-icon--password">
                                    <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                    <input type="password" name="restore_password" id="users_restore_password" placeholder="Confirme a senha de administrador" autocomplete="current-password" required>
                                </div>
                                <small class="muted">A restauração só continua após confirmar a senha de administrador.</small>
                                <button type="submit" class="button button-secondary">
                                    <i class="bi bi-upload" aria-hidden="true"></i>
                                    <span>Restaurar usuários</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
