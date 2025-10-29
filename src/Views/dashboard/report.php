<?php

use Holerite\Models\Company;

/** @var string $scope */
/** @var array<int, array{period: string, total: float, count: int}> $totals */
/** @var string $description */
/** @var array{totalAmount: float, averageAmount: float, periodCount: int, highest: array|null, lowest: array|null, latest: array|null, earliest: array|null, payrollCount: int, employeeCount: int, valeTotal: float} $summary */
/** @var array<int, array{period: string, total: float, count: int, percentage: float}> $breakdown */
/** @var array<int, array{period: string, total: float}> $valeBreakdown */
/** @var string $scopeLabel */

$reportTitle = $scope === 'yearly' ? 'Relatório anual' : 'Relatório mensal';
$columnLabel = $scope === 'yearly' ? 'Ano' : 'Mês';
$totalAmount = $summary['totalAmount'];
$periodCount = $summary['periodCount'];
$averageAmount = $summary['averageAmount'];
$highestRow = $summary['highest'];
$lowestRow = $summary['lowest'];
$latestRow = $summary['latest'];
$earliestRow = $summary['earliest'];
$payrollCount = $summary['payrollCount'];
$employeeCount = $summary['employeeCount'];
$valeTotal = $summary['valeTotal'];

$issuedAt = new \DateTimeImmutable('now');

$company = isset($GLOBALS['holerite_company']) && $GLOBALS['holerite_company'] instanceof Company
    ? $GLOBALS['holerite_company']
    : null;

$companyDocument = null;
$companyAddress = null;
$companyContact = null;

if ($company !== null) {
    $companyDocument = trim($company->getDocument());
    $addressSegments = [];
    $addressLine = trim($company->getAddress());
    if ($addressLine !== '') {
        $addressSegments[] = $addressLine;
    }

    $city = trim($company->getCity());
    $state = trim($company->getState());
    $cityState = $city;
    if ($state !== '') {
        $cityState = $city !== '' ? $city . ' - ' . $state : $state;
    }
    if (trim($cityState) !== '') {
        $addressSegments[] = trim($cityState);
    }

    $zip = trim($company->getZipCode());
    if ($zip !== '') {
        $addressSegments[] = 'CEP ' . $zip;
    }

    if ($addressSegments !== []) {
        $companyAddress = implode(' • ', $addressSegments);
    }

    $contactSegments = [];
    $phone = trim($company->getPhone());
    if ($phone !== '') {
        $contactSegments[] = $phone;
    }
    $email = trim($company->getEmail());
    if ($email !== '') {
        $contactSegments[] = $email;
    }

    if ($contactSegments !== []) {
        $companyContact = implode(' • ', $contactSegments);
    }
}
?>

<section class="report">
    <div class="report__toolbar">
        <a class="button button-secondary" href="?action=dashboard">Voltar ao dashboard</a>
        <div class="report__toolbar-actions">
            <?php if ($scope === 'yearly'): ?>
                <a class="button" href="?action=dashboard_report&amp;scope=monthly">Ver relatório mensal</a>
            <?php else: ?>
                <a class="button" href="?action=dashboard_report&amp;scope=yearly">Ver relatório anual</a>
            <?php endif; ?>
            <button class="button" type="button" onclick="window.print();">Imprimir</button>
        </div>
    </div>

    <article class="report__paper">
        <header class="report__header">
            <div class="report__identity">
                <div class="report__brand">
                    <div class="report__logo">
                        <img src="img/logo.png" alt="Logotipo da empresa">
                    </div>
                    <div class="report__company-text">
                        <h1><?= htmlspecialchars($company?->getName() ?? 'Empresa não cadastrada'); ?></h1>
                        <?php if ($companyDocument !== null && $companyDocument !== ''): ?>
                            <p><?= htmlspecialchars($companyDocument); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="report__company-meta">
                    <?php if ($companyAddress): ?>
                        <span><?= htmlspecialchars($companyAddress); ?></span>
                    <?php endif; ?>
                    <?php if ($companyContact): ?>
                        <span><?= htmlspecialchars($companyContact); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="report__meta">
                <span class="report__eyebrow">Relatório financeiro</span>
                <h2><?= htmlspecialchars($reportTitle); ?></h2>
                <p><?= htmlspecialchars($description); ?></p>
                <dl class="report__meta-list">
                    <div>
                        <dt>Emitido em</dt>
                        <dd><?= $issuedAt->format('d/m/Y H:i'); ?></dd>
                    </div>
                    <div>
                        <dt>Escopo</dt>
                        <dd><?= htmlspecialchars($scopeLabel); ?></dd>
                    </div>
                    <div>
                        <dt>Períodos analisados</dt>
                        <dd><?= $periodCount; ?></dd>
                    </div>
                    <?php if ($earliestRow !== null && $latestRow !== null): ?>
                        <div>
                            <dt>Intervalo</dt>
                            <dd><?= htmlspecialchars($earliestRow['period']); ?> — <?= htmlspecialchars($latestRow['period']); ?></dd>
                        </div>
                    <?php endif; ?>
                    <div>
                        <dt>Holerites considerados</dt>
                        <dd><?= $payrollCount; ?></dd>
                    </div>
                    <div>
                        <dt>Colaboradores impactados</dt>
                        <dd><?= $employeeCount; ?></dd>
                    </div>
                </dl>
            </div>
        </header>

        <section class="report-summary" aria-label="Resumo do relatório">
            <div class="report-summary__card">
                <span>Total pago</span>
                <strong>R$ <?= number_format($totalAmount, 2, ',', '.'); ?></strong>
            </div>
            <div class="report-summary__card">
                <span>Média por período</span>
                <strong>R$ <?= number_format($averageAmount, 2, ',', '.'); ?></strong>
            </div>
            <div class="report-summary__card">
                <span>Maior pagamento</span>
                <?php if ($highestRow !== null): ?>
                    <strong><?= htmlspecialchars($highestRow['period']); ?> — R$ <?= number_format($highestRow['total'], 2, ',', '.'); ?></strong>
                <?php else: ?>
                    <strong>—</strong>
                <?php endif; ?>
            </div>
            <div class="report-summary__card">
                <span>Menor pagamento</span>
                <?php if ($lowestRow !== null): ?>
                    <strong><?= htmlspecialchars($lowestRow['period']); ?> — R$ <?= number_format($lowestRow['total'], 2, ',', '.'); ?></strong>
                <?php else: ?>
                    <strong>—</strong>
                <?php endif; ?>
            </div>
            <div class="report-summary__card">
                <span>Descontos de vale</span>
                <strong>R$ <?= number_format($valeTotal, 2, ',', '.'); ?></strong>
            </div>
        </section>

        <?php if ($periodCount > 0): ?>
            <section class="report-details" aria-label="Contexto do relatório">
                <div class="report-details__column">
                    <h3>Contexto do relatório</h3>
                    <ul class="report-details__list">
                        <li>
                            <strong>Escopo</strong>
                            <span><?= htmlspecialchars($scopeLabel); ?></span>
                        </li>
                        <li>
                            <strong>Períodos avaliados</strong>
                            <span><?= $periodCount; ?></span>
                        </li>
                        <?php if ($earliestRow !== null && $latestRow !== null): ?>
                            <li>
                                <strong>Intervalo analisado</strong>
                                <span><?= htmlspecialchars($earliestRow['period']); ?> — <?= htmlspecialchars($latestRow['period']); ?></span>
                            </li>
                        <?php endif; ?>
                        <li>
                            <strong>Holerites considerados</strong>
                            <span><?= $payrollCount; ?></span>
                        </li>
                        <li>
                            <strong>Colaboradores impactados</strong>
                            <span><?= $employeeCount; ?></span>
                        </li>
                    </ul>
                </div>
                <div class="report-details__column">
                    <h3>Indicadores financeiros</h3>
                    <ul class="report-details__list">
                        <li>
                            <strong>Média por período</strong>
                            <span>R$ <?= number_format($averageAmount, 2, ',', '.'); ?></span>
                        </li>
                        <?php if ($highestRow !== null): ?>
                            <li>
                                <strong>Pico de pagamento</strong>
                                <span><?= htmlspecialchars($highestRow['period']); ?> — R$ <?= number_format($highestRow['total'], 2, ',', '.'); ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if ($lowestRow !== null): ?>
                            <li>
                                <strong>Menor desembolso</strong>
                                <span><?= htmlspecialchars($lowestRow['period']); ?> — R$ <?= number_format($lowestRow['total'], 2, ',', '.'); ?></span>
                            </li>
                        <?php endif; ?>
                        <li>
                            <strong>Participação média</strong>
                            <span><?= $periodCount > 0 ? number_format(100 / $periodCount, 2, ',', '.') : '0,00'; ?>%</span>
                        </li>
                        <li>
                            <strong>Vales descontados</strong>
                            <span>R$ <?= number_format($valeTotal, 2, ',', '.'); ?></span>
                        </li>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <section class="report__table" aria-label="Pagamentos líquidos por período">
            <?php if ($breakdown === []): ?>
                <p class="report__empty">Nenhum pagamento registrado até o momento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th><?= htmlspecialchars($columnLabel); ?></th>
                            <th class="text-right">Holerites</th>
                            <th class="text-right">Valor líquido pago</th>
                            <th class="text-right">Participação</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($breakdown as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['period']); ?></td>
                                <td class="text-right"><?= $row['count']; ?></td>
                                <td class="text-right"><strong>R$ <?= number_format($row['total'], 2, ',', '.'); ?></strong></td>
                                <td class="text-right"><?= number_format($row['percentage'], 2, ',', '.'); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>Total geral</th>
                            <th class="text-right"><?= $payrollCount; ?></th>
                            <th class="text-right">R$ <?= number_format($totalAmount, 2, ',', '.'); ?></th>
                            <th class="text-right">100%</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="report__table" aria-label="Descontos de vale por período">
            <?php if ($valeBreakdown === []): ?>
                <p class="report__empty">Nenhum desconto de vale registrado neste escopo.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th><?= htmlspecialchars($columnLabel); ?></th>
                            <th class="text-right">Total de vales</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($valeBreakdown as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['period']); ?></td>
                                <td class="text-right"><strong>R$ <?= number_format($row['total'], 2, ',', '.'); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>Total geral</th>
                            <th class="text-right">R$ <?= number_format($valeTotal, 2, ',', '.'); ?></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="report__notes" aria-label="Observações do relatório">
            <p><strong>Observação:</strong> Este documento reproduz integralmente os dados apresentados na aba “Relatório financeiro” do sistema para o escopo <?= strtolower($scopeLabel) === 'mensal' ? 'mensal' : 'anual'; ?>. Utilize a versão impressa para auditorias, apresentações e conferências internas, mantendo o histórico de pagamentos à disposição da gestão.</p>
        </section>

        <footer class="report__footer">
            <p>Documento gerado automaticamente pelo sistema de holerites. Para mais detalhes, consulte os relatórios individuais de cada colaborador.</p>
        </footer>
    </article>
</section>
