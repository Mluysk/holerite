<?php

use Holerite\Models\Company;
use Holerite\Models\Employee;

/** @var DateTimeImmutable $issuedAt */
/** @var DateTimeImmutable $referenceDate */
/** @var array<int, array{employee: Employee, tenure: string, status: string, payrollSummary: array, benefits: array}> $entries */
/** @var array{totalEmployees: int, activeEmployees: int, inactiveEmployees: int, employeesWithPayrolls: int, averageSalary: float, totalNetPaid: float, totalAllowances: float, totalDeductions: float, totalPayrolls: int} $summary */

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

$thirteenthLabel = static function (array $thirteenth): string {
    $status = (string) ($thirteenth['status'] ?? '');
    if ($status !== '') {
        return $status;
    }

    $monthsAccrued = (int) ($thirteenth['months_accrued'] ?? 0);
    if ($monthsAccrued === 0) {
        return 'Sem meses elegíveis';
    }

    return sprintf('%d mês(es) elegíveis', $monthsAccrued);
};
?>

<section class="report">
    <div class="report__toolbar">
        <a class="button button-secondary" href="?action=list_employees">Voltar à lista</a>
        <div class="report__toolbar-actions">
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
                <span class="report__eyebrow">Relatório institucional</span>
                <h2>Relatório de colaboradores</h2>
                <p>Resumo completo dos profissionais cadastrados, vínculos, benefícios e holerites emitidos pela empresa.</p>
                <dl class="report__meta-list">
                    <div>
                        <dt>Emitido em</dt>
                        <dd><?= $issuedAt->format('d/m/Y H:i'); ?></dd>
                    </div>
                    <div>
                        <dt>Referência</dt>
                        <dd><?= $referenceDate->format('d/m/Y'); ?></dd>
                    </div>
                    <div>
                        <dt>Total de colaboradores</dt>
                        <dd><?= $summary['totalEmployees']; ?></dd>
                    </div>
                    <div>
                        <dt>Colaboradores com holerite</dt>
                        <dd><?= $summary['employeesWithPayrolls']; ?></dd>
                    </div>
                    <div>
                        <dt>Holerites emitidos</dt>
                        <dd><?= $summary['totalPayrolls']; ?></dd>
                    </div>
                </dl>
            </div>
        </header>

        <section class="report-summary" aria-label="Resumo geral">
            <div class="report-summary__card">
                <span>Colaboradores ativos</span>
                <strong><?= $summary['activeEmployees']; ?></strong>
            </div>
            <div class="report-summary__card">
                <span>Colaboradores desligados</span>
                <strong><?= $summary['inactiveEmployees']; ?></strong>
            </div>
            <div class="report-summary__card">
                <span>Média salarial</span>
                <strong>R$ <?= number_format($summary['averageSalary'], 2, ',', '.'); ?></strong>
            </div>
            <div class="report-summary__card">
                <span>Total líquido pago</span>
                <strong>R$ <?= number_format($summary['totalNetPaid'], 2, ',', '.'); ?></strong>
            </div>
        </section>

        <section class="report-details" aria-label="Indicadores complementares">
            <div class="report-details__column">
                <h3>Valores consolidados</h3>
                <ul class="report-details__list">
                    <li>
                        <strong>Holerites emitidos</strong>
                        <span><?= $summary['totalPayrolls']; ?></span>
                    </li>
                    <li>
                        <strong>Total de proventos</strong>
                        <span>R$ <?= number_format($summary['totalAllowances'], 2, ',', '.'); ?></span>
                    </li>
                    <li>
                        <strong>Total de descontos</strong>
                        <span>R$ <?= number_format($summary['totalDeductions'], 2, ',', '.'); ?></span>
                    </li>
                </ul>
            </div>
            <div class="report-details__column">
                <h3>Observações</h3>
                <p>
                    Este relatório consolida todas as informações cadastradas pelos operadores do sistema,
                    incluindo dados pessoais, funções, vínculos e benefícios legais.
                    Utilize-o como base para auditorias internas, envio de informações a escritórios contábeis
                    e registro histórico de pagamentos.
                </p>
            </div>
        </section>

        <section class="report-employees" aria-label="Detalhamento por colaborador">
            <h3>Relação de colaboradores</h3>

            <?php if ($entries === []): ?>
                <p class="muted">Nenhum colaborador cadastrado até o momento.</p>
            <?php else: ?>
                <ol class="report-employees__list">
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        /** @var Employee $employee */
                        $employee = $entry['employee'];
                        $payrollSummary = $entry['payrollSummary'];
                        $benefits = $entry['benefits'];
                        $thirteenth = $benefits['thirteenth'] ?? [];
                        $vacations = $benefits['vacations']['cycles'] ?? [];
                        ?>
                        <li class="report-employee">
                            <header class="report-employee__header">
                                <div>
                                    <h4><?= htmlspecialchars($employee->getName()); ?></h4>
                                    <span class="report-employee__subtitle">Departamento: <?= htmlspecialchars($employee->getDepartment() !== '' ? $employee->getDepartment() : '—'); ?> · Cargo: <?= htmlspecialchars($employee->getPosition() !== '' ? $employee->getPosition() : '—'); ?></span>
                                </div>
                                <span class="report-employee__status"><?= htmlspecialchars($entry['status']); ?></span>
                            </header>

                            <div class="report-employee__body">
                                <dl class="report-employee__grid">
                                    <div>
                                        <dt>E-mail</dt>
                                        <dd><?= htmlspecialchars($employee->getEmail()); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Admissão</dt>
                                        <dd><?= $employee->getHireDate()->format('d/m/Y'); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Tempo de casa</dt>
                                        <dd><?= htmlspecialchars($entry['tenure']); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Salário base</dt>
                                        <dd>R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Desligamento</dt>
                                        <dd>
                                            <?php if ($employee->getTerminationDate() !== null): ?>
                                                <?= $employee->getTerminationDate()->format('d/m/Y'); ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Holerites emitidos</dt>
                                        <dd><?= $payrollSummary['count']; ?></dd>
                                    </div>
                                </dl>

                                <div class="report-employee__sections">
                                    <section>
                                        <h5>Resumo de holerites</h5>
                                        <ul class="report-employee__list">
                                            <li>
                                                <span>Período do primeiro registro</span>
                                                <strong>
                                                    <?php if ($payrollSummary['earliest_payment'] instanceof DateTimeImmutable): ?>
                                                        <?= $payrollSummary['earliest_payment']->format('d/m/Y'); ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </strong>
                                            </li>
                                            <li>
                                                <span>Último pagamento</span>
                                                <strong>
                                                    <?php if ($payrollSummary['latest_payment'] instanceof DateTimeImmutable): ?>
                                                        <?= $payrollSummary['latest_payment']->format('d/m/Y'); ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </strong>
                                            </li>
                                            <li>
                                                <span>Total bruto acumulado</span>
                                                <strong>R$ <?= number_format($payrollSummary['total_gross'], 2, ',', '.'); ?></strong>
                                            </li>
                                            <li>
                                                <span>Proventos registrados</span>
                                                <strong>R$ <?= number_format($payrollSummary['total_allowances'], 2, ',', '.'); ?></strong>
                                            </li>
                                            <li>
                                                <span>Descontos aplicados</span>
                                                <strong>R$ <?= number_format($payrollSummary['total_deductions'], 2, ',', '.'); ?></strong>
                                            </li>
                                            <li>
                                                <span>Total líquido recebido</span>
                                                <strong>R$ <?= number_format($payrollSummary['total_net'], 2, ',', '.'); ?></strong>
                                            </li>
                                        </ul>

                                        <?php if ($payrollSummary['types'] !== []): ?>
                                            <div class="report-employee__pill-group" aria-label="Distribuição de holerites por tipo">
                                                <?php foreach ($payrollSummary['types'] as $typeRow): ?>
                                                    <span class="report-employee__pill">
                                                        <?= htmlspecialchars($typeRow['label']); ?> · <?= $typeRow['count']; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </section>

                                    <section>
                                        <h5>13º salário</h5>
                                        <ul class="report-employee__list">
                                            <li>
                                                <span>Status</span>
                                                <strong><?= htmlspecialchars($thirteenthLabel($thirteenth)); ?></strong>
                                            </li>
                                            <li>
                                                <span>Meses acumulados</span>
                                                <strong><?= (int) ($thirteenth['months_accrued'] ?? 0); ?></strong>
                                            </li>
                                            <li>
                                                <span>Bruto acumulado</span>
                                                <strong>R$ <?= number_format((float) ($thirteenth['gross_accrued'] ?? 0), 2, ',', '.'); ?></strong>
                                            </li>
                                            <li>
                                                <span>Pago em 1ª parcela</span>
                                                <strong>R$ <?= number_format((float) ($thirteenth['first_installment_paid'] ?? 0), 2, ',', '.'); ?></strong>
                                            </li>
                                            <li>
                                                <span>Pago em 2ª parcela</span>
                                                <strong>R$ <?= number_format((float) ($thirteenth['second_installment_paid'] ?? 0), 2, ',', '.'); ?></strong>
                                            </li>
                                            <li>
                                                <span>Pendente</span>
                                                <strong>R$ <?= number_format((float) ($thirteenth['gross_pending'] ?? 0), 2, ',', '.'); ?></strong>
                                            </li>
                                        </ul>
                                    </section>

                                    <section>
                                        <h5>Férias</h5>
                                        <?php if ($vacations === []): ?>
                                            <p class="muted">Sem ciclos elegíveis até o momento.</p>
                                        <?php else: ?>
                                            <table class="report-employee__table">
                                                <thead>
                                                    <tr>
                                                        <th>Período aquisitivo</th>
                                                        <th>Meses</th>
                                                        <th>Dias acumulados</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($vacations as $cycle): ?>
                                                    <tr>
                                                        <td><?= $cycle['acquisition_start']->format('d/m/Y'); ?> — <?= $cycle['acquisition_end']->format('d/m/Y'); ?></td>
                                                        <td><?= $cycle['worked_months']; ?></td>
                                                        <td><?= number_format((float) $cycle['accrued_days'], 1, ',', '.'); ?></td>
                                                        <td><?= htmlspecialchars((string) $cycle['status']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </section>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </article>
</section>
