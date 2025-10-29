<?php
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
?>
<section class="report">
    <header class="section-header">
        <div class="section-header__content">
            <h2><?= htmlspecialchars($reportTitle); ?></h2>
            <p class="muted" style="margin: 0.35rem 0 0 0;"><?= htmlspecialchars($description); ?></p>
        </div>
        <div class="section-header__actions">
            <a class="button button-secondary" href="?action=dashboard">Voltar ao dashboard</a>
            <?php if ($scope === 'yearly'): ?>
                <a class="button" href="?action=dashboard_report&amp;scope=monthly">Ver relatório mensal</a>
            <?php else: ?>
                <a class="button" href="?action=dashboard_report&amp;scope=yearly">Ver relatório anual</a>
            <?php endif; ?>
            <button class="button" type="button" onclick="window.print();">Imprimir</button>
        </div>
    </header>

    <div class="card" style="margin-top: 1.5rem;">
        <?php if ($totals === []): ?>
            <p class="muted" style="margin: 0;">Nenhum pagamento registrado até o momento.</p>
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
    </div>
</section>
