<?php

use Holerite\Models\Company;
use Holerite\Models\Employee;

/** @var DateTimeImmutable $issuedAt */
/** @var DateTimeImmutable $referenceDate */
/**
 * @var array<int, array{
 *     employee: Employee,
 *     tenure: string,
 *     tenureMonths: int,
 *     status: string,
 *     payrollSummary: array,
 *     benefits: array
 * }> $entries
 */

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

$totalEmployees = count($entries);
?>

<section class="report">
    <div class="report__toolbar">
        <a class="button button-secondary" href="?action=list_employees">Voltar à lista</a>
        <div class="report__toolbar-actions">
            <button class="button" type="button" onclick="window.print();">Imprimir</button>
        </div>
    </div>

    <article class="report__paper report__paper--compact">
        <header class="report__header report__header--compact">
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
                <span class="report__eyebrow">Relatório de colaboradores</span>
                <h2>Lista resumida para impressão</h2>
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
                        <dd><?= $totalEmployees; ?></dd>
                    </div>
                </dl>
            </div>
        </header>

        <section class="report__table" aria-label="Lista de colaboradores">
            <?php if ($entries === []): ?>
                <p class="report__empty">Nenhum colaborador cadastrado até o momento.</p>
            <?php else: ?>
                <div class="report-table-wrapper">
                    <table class="report-simple-table report-simple-table--wide">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>CPF</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Salário base</th>
                                <th>Admissão</th>
                                <th>Tempo de casa</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <?php /** @var Employee $employee */ $employee = $entry['employee']; ?>
                            <?php
                            $months = max(0, (int) ($entry['tenureMonths'] ?? 0));
                            $yearsPart = intdiv($months, 12);
                            $remainingMonths = $months % 12;
                            $yearsLabel = $yearsPart === 1 ? '1 ano' : $yearsPart . ' anos';
                            $monthsLabel = $remainingMonths === 1 ? '1 mês' : $remainingMonths . ' meses';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($employee->getName()); ?></td>
                                <td><?= htmlspecialchars($employee->getCpfFormatted()); ?></td>
                                <td><?= htmlspecialchars($employee->getDepartment()); ?></td>
                                <td><?= htmlspecialchars($employee->getPosition()); ?></td>
                                <td>R$ <?= number_format($employee->getBaseSalary(), 2, ',', '.'); ?></td>
                                <td><?= $employee->getHireDate()->format('d/m/Y'); ?></td>
                                <td>
                                    <?= htmlspecialchars($yearsLabel . ' e ' . $monthsLabel); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </article>
</section>
