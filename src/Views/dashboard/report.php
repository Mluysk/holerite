<?php

use Holerite\Models\Company;

/** @var string $scope */
/** @var array<int, array{period: string, total: float}> $totals */
/** @var string $description */

$reportTitle = $scope === 'yearly' ? 'Relatório anual' : 'Relatório mensal';
$columnLabel = $scope === 'yearly' ? 'Ano' : 'Mês';
$totalAmount = array_reduce(
    $totals,
    static fn (float $carry, array $row): float => $carry + (float) $row['total'],
    0.0
);
$periodCount = count($totals);
$averageAmount = $periodCount > 0 ? $totalAmount / $periodCount : 0.0;

$highestRow = null;
foreach ($totals as $row) {
    if ($highestRow === null || (float) $row['total'] > (float) $highestRow['total']) {
        $highestRow = $row;
    }
}

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
                        <dd><?= $scope === 'yearly' ? 'Anual' : 'Mensal'; ?></dd>
                    </div>
                    <div>
                        <dt>Períodos analisados</dt>
                        <dd><?= $periodCount; ?></dd>
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
        </section>

        <section class="report__table" aria-label="Pagamentos líquidos por período">
            <?php if ($totals === []): ?>
                <p class="report__empty">Nenhum pagamento registrado até o momento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th><?= htmlspecialchars($columnLabel); ?></th>
                            <th class="text-right">Valor líquido pago</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($totals as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['period']); ?></td>
                                <td class="text-right"><strong>R$ <?= number_format($row['total'], 2, ',', '.'); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>Total geral</th>
                            <th class="text-right">R$ <?= number_format($totalAmount, 2, ',', '.'); ?></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <footer class="report__footer">
            <p>Documento gerado automaticamente pelo sistema de holerites. Para mais detalhes, consulte os relatórios individuais de cada colaborador.</p>
        </footer>
    </article>
</section>
