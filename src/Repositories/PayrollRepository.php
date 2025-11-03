<?php

declare(strict_types=1);

namespace Holerite\Repositories;

use DateTimeImmutable;
use Holerite\Database\Connection;
use Holerite\Models\Payroll;
use Holerite\Models\PayrollItem;
use PDO;
use RuntimeException;
use Throwable;

final class PayrollRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * @return Payroll[]
     */
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM payrolls ORDER BY payment_date DESC');
        $rows = $statement->fetchAll();

        return array_map(fn (array $row): Payroll => $this->hydrate($row), $rows);
    }

    /**
     * @return Payroll[]
     */
    public function findByEmployee(int $employeeId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM payrolls WHERE employee_id = :employee_id ORDER BY payment_date DESC');
        $statement->execute(['employee_id' => $employeeId]);
        $rows = $statement->fetchAll();

        return array_map(fn (array $row): Payroll => $this->hydrate($row), $rows);
    }

    public function find(int $id): ?Payroll
    {
        $statement = $this->pdo->prepare('SELECT * FROM payrolls WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function create(Payroll $payroll): Payroll
    {
        $statement = $this->pdo->prepare('INSERT INTO payrolls (employee_id, reference_month, type, base_salary, total_allowances, total_deductions, net_salary, advance_amount, remaining_amount, vale_deduction, uses_transport, transport_deduction, transport_days, transport_trip_cost, payment_date, is_just_cause, vacation_days, worked_days, thirteenth_months, thirteenth_installment, thirteenth_accrual, inss_base, inss_amount, irrf_base, irrf_amount, fgts_base, fgts_amount, notes) VALUES (:employee_id, :reference_month, :type, :base_salary, :total_allowances, :total_deductions, :net_salary, :advance_amount, :remaining_amount, :vale_deduction, :uses_transport, :transport_deduction, :transport_days, :transport_trip_cost, :payment_date, :is_just_cause, :vacation_days, :worked_days, :thirteenth_months, :thirteenth_installment, :thirteenth_accrual, :inss_base, :inss_amount, :irrf_base, :irrf_amount, :fgts_base, :fgts_amount, :notes)');
        $statement->execute([
            'employee_id' => $payroll->getEmployeeId(),
            'reference_month' => $payroll->getReferenceMonth(),
            'type' => $payroll->getType(),
            'base_salary' => $payroll->getBaseSalary(),
            'total_allowances' => $payroll->getTotalAllowances(),
            'total_deductions' => $payroll->getTotalDeductions(),
            'net_salary' => $payroll->getNetSalary(),
            'advance_amount' => $payroll->getAdvanceAmount(),
            'remaining_amount' => $payroll->getRemainingAmount(),
            'vale_deduction' => $payroll->getManualValeDeduction(),
            'uses_transport' => $payroll->usesTransport() ? 1 : 0,
            'transport_deduction' => $payroll->getTransportDeduction(),
            'transport_days' => $payroll->getTransportDays(),
            'transport_trip_cost' => $payroll->getTransportTripCost(),
            'payment_date' => $payroll->getPaymentDate()->format('Y-m-d'),
            'is_just_cause' => $payroll->isJustCause() ? 1 : 0,
            'vacation_days' => $payroll->getVacationDays(),
            'worked_days' => $payroll->getWorkedDays(),
            'thirteenth_months' => $payroll->getThirteenthMonths(),
            'thirteenth_installment' => $payroll->getThirteenthInstallment(),
            'thirteenth_accrual' => $payroll->getThirteenthAccrual(),
            'inss_base' => $payroll->getInssBase(),
            'inss_amount' => $payroll->getInssAmount(),
            'irrf_base' => $payroll->getIrrfBase(),
            'irrf_amount' => $payroll->getIrrfAmount(),
            'fgts_base' => $payroll->getFgtsBase(),
            'fgts_amount' => $payroll->getFgtsAmount(),
            'notes' => $payroll->getNotes(),
        ]);

        $payrollId = (int) $this->pdo->lastInsertId();
        $payroll->setId($payrollId);

        foreach ($payroll->getItems() as $item) {
            $this->createItem($payrollId, $item);
        }

        return $payroll;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Payroll
    {
        $payroll = new Payroll(
            (int) $row['id'],
            (int) $row['employee_id'],
            (string) $row['reference_month'],
            (string) $row['type'],
            (float) $row['base_salary'],
            (float) $row['total_allowances'],
            (float) $row['total_deductions'],
            (float) $row['net_salary'],
            (float) ($row['advance_amount'] ?? 0),
            (float) ($row['remaining_amount'] ?? 0),
            (float) ($row['vale_deduction'] ?? 0),
            (bool) ($row['uses_transport'] ?? false),
            (float) ($row['transport_deduction'] ?? 0),
            isset($row['transport_days']) ? (int) $row['transport_days'] : 0,
            (float) ($row['transport_trip_cost'] ?? 0),
            new DateTimeImmutable((string) $row['payment_date']),
            (bool) $row['is_just_cause'],
            isset($row['vacation_days']) ? ($row['vacation_days'] !== null ? (int) $row['vacation_days'] : null) : null,
            isset($row['worked_days']) ? ($row['worked_days'] !== null ? (int) $row['worked_days'] : null) : null,
            isset($row['thirteenth_months']) ? ($row['thirteenth_months'] !== null ? (int) $row['thirteenth_months'] : null) : null,
            isset($row['thirteenth_installment']) ? ($row['thirteenth_installment'] !== null ? (string) $row['thirteenth_installment'] : null) : null,
            (float) ($row['thirteenth_accrual'] ?? 0),
            (float) ($row['inss_base'] ?? 0),
            (float) ($row['inss_amount'] ?? 0),
            (float) ($row['irrf_base'] ?? 0),
            (float) ($row['irrf_amount'] ?? 0),
            (float) ($row['fgts_base'] ?? 0),
            (float) ($row['fgts_amount'] ?? 0),
            (string) $row['notes'],
            []
        );

        $payroll->setItems($this->findItems($payroll->getId()));

        return $payroll;
    }

    /**
     * @return PayrollItem[]
     */
    private function findItems(?int $payrollId): array
    {
        if ($payrollId === null) {
            return [];
        }

        $statement = $this->pdo->prepare('SELECT * FROM payroll_items WHERE payroll_id = :payroll_id');
        $statement->execute(['payroll_id' => $payrollId]);
        $rows = $statement->fetchAll();

        return array_map(fn (array $row): PayrollItem => new PayrollItem(
            (int) $row['id'],
            (int) $row['payroll_id'],
            (string) $row['description'],
            (float) $row['amount'],
            (string) $row['type'],
        ), $rows);
    }

    private function createItem(int $payrollId, PayrollItem $item): void
    {
        $statement = $this->pdo->prepare('INSERT INTO payroll_items (payroll_id, description, amount, type) VALUES (:payroll_id, :description, :amount, :type)');
        $statement->execute([
            'payroll_id' => $payrollId,
            'description' => $item->getDescription(),
            'amount' => $item->getAmount(),
            'type' => $item->getType(),
        ]);
    }

    public function delete(int $id): void
    {
        try {
            $this->pdo->beginTransaction();

            $statement = $this->pdo->prepare('DELETE FROM payroll_items WHERE payroll_id = :id');
            $statement->execute(['id' => $id]);

            $statement = $this->pdo->prepare('DELETE FROM payrolls WHERE id = :id');
            $statement->execute(['id' => $id]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException('Não foi possível excluir o holerite.', 0, $exception);
        }
    }
}
