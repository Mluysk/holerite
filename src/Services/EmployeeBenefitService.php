<?php

declare(strict_types=1);

namespace Holerite\Services;

use DateInterval;
use DateTimeImmutable;
use Holerite\Models\Employee;
use Holerite\Models\Payroll;

final class EmployeeBenefitService
{
    /**
     * @param Payroll[] $payrolls
     * @return array<string, mixed>
     */
    public function summarize(Employee $employee, array $payrolls, ?DateTimeImmutable $asOf = null): array
    {
        $asOf ??= new DateTimeImmutable('today');

        return [
            'thirteenth' => $this->calculateThirteenth($employee, $payrolls, $asOf),
            'vacations' => $this->calculateVacations($employee, $asOf),
        ];
    }

    /**
     * @param Payroll[] $payrolls
     * @return array<string, mixed>
     */
    private function calculateThirteenth(Employee $employee, array $payrolls, DateTimeImmutable $asOf): array
    {
        $currentYear = (int) $asOf->format('Y');
        $yearStart = $this->maxDate(new DateTimeImmutable(sprintf('%d-01-01', $currentYear)), $employee->getHireDate());

        $yearEnd = new DateTimeImmutable(sprintf('%d-12-31', $currentYear));
        $termination = $employee->getTerminationDate();
        if ($termination !== null && (int) $termination->format('Y') === $currentYear && $termination < $yearEnd) {
            $yearEnd = $termination;
        }

        $periodEnd = $asOf->modify('last day of this month');
        if ($periodEnd < $yearEnd) {
            $yearEnd = $periodEnd;
        }

        if ($yearEnd < $yearStart) {
            $monthsBreakdown = [];
            $monthsAccrued = 0;
        } else {
            $monthsBreakdown = $this->buildMonthBreakdown($yearStart, $yearEnd, $currentYear);
            $monthsAccrued = array_reduce($monthsBreakdown, fn (int $carry, array $month): int => $carry + ($month['counted'] ? 1 : 0), 0);
        }

        $baseSalary = $employee->getBaseSalary();
        $grossAccrued = $this->roundMoney($baseSalary * $monthsAccrued / 12);

        $monthsPaid = 0;
        $grossPaid = 0.0;
        $netPaid = 0.0;
        $firstInstallmentGross = 0.0;
        $secondInstallmentGross = 0.0;
        $thirteenthPayrolls = array_filter(
            $payrolls,
            fn (Payroll $payroll): bool => $payroll->getType() === 'thirteenth' && (int) $payroll->getPaymentDate()->format('Y') === $currentYear
        );

        foreach ($thirteenthPayrolls as $payroll) {
            $grossValue = $payroll->getBaseSalary() + $payroll->getTotalAllowances();
            $installment = $payroll->getThirteenthInstallment();

            if ($installment === 'first') {
                $firstInstallmentGross += $grossValue;
            } else {
                $secondInstallmentGross += $grossValue;
                $monthsPaid += $payroll->getThirteenthMonths() ?? 0;
            }

            $grossPaid += $grossValue;
            $netPaid += $payroll->getNetSalary();
        }

        $monthsPaid = min($monthsAccrued, $monthsPaid);
        $monthsPending = max(0, $monthsAccrued - $monthsPaid);
        $grossPending = $this->roundMoney($baseSalary * $monthsPending / 12);
        $firstInstallmentGross = $this->roundMoney($firstInstallmentGross);
        $secondInstallmentGross = $this->roundMoney($secondInstallmentGross);
        $grossPaid = $this->roundMoney($grossPaid);
        $netPaid = $this->roundMoney($netPaid);

        $status = 'Sem meses elegíveis no período atual.';
        if ($monthsPending > 0) {
            if ($firstInstallmentGross > 0 && $monthsPaid === 0) {
                $status = '2ª parcela pendente. Adiantamento já pago.';
            } elseif ($monthsAccrued >= 12 || ($termination !== null && (int) $termination->format('Y') === $currentYear)) {
                $status = 'Pagamento integral disponível para este ano.';
            } else {
                $status = 'Parcela proporcional liberada para adiantamento.';
            }
        } elseif ($monthsAccrued > 0) {
            $status = '13º quitado para o ano em curso.';
        }

        return [
            'year' => $currentYear,
            'months_accrued' => $monthsAccrued,
            'months_paid' => $monthsPaid,
            'months_pending' => $monthsPending,
            'gross_accrued' => $grossAccrued,
            'gross_paid' => $grossPaid,
            'net_paid' => $netPaid,
            'gross_pending' => $grossPending,
            'status' => $status,
            'eligible' => $monthsPending > 0,
            'months_breakdown' => $monthsBreakdown,
            'default_reference' => sprintf('%d-12', $currentYear),
            'first_installment_paid' => $firstInstallmentGross,
            'second_installment_paid' => $secondInstallmentGross,
            'suggested_installment' => $firstInstallmentGross > 0 ? 'second' : 'first',
            'suggested_months' => $firstInstallmentGross > 0 ? $monthsAccrued : $monthsPending,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateVacations(Employee $employee, DateTimeImmutable $asOf): array
    {
        $cycles = [];
        $upcomingNotices = [];
        $termination = $employee->getTerminationDate();
        $baseStart = $employee->getVacationBaseDate() ?? $employee->getHireDate();
        $cycleStart = $baseStart;
        $nextCycleCandidate = null;

        for ($index = 1; $index <= 4; $index++) {
            if ($termination !== null && $cycleStart > $termination) {
                break;
            }

            $acquisitionEnd = $cycleStart->add(new DateInterval('P1Y'))->modify('-1 day');
            $availableFrom = $acquisitionEnd->add(new DateInterval('P1D'));
            $concessionEnd = $availableFrom->add(new DateInterval('P1Y'))->modify('-1 day');

            $noticeFrom = $availableFrom->modify('-30 days');
            if ($noticeFrom < $cycleStart) {
                $noticeFrom = $cycleStart;
            }

            $preEligibilityNotice = $acquisitionEnd->modify('-30 days');
            if ($preEligibilityNotice < $cycleStart) {
                $preEligibilityNotice = $cycleStart;
            }

            $effectiveEnd = $acquisitionEnd;
            if ($termination !== null && $termination < $effectiveEnd) {
                $effectiveEnd = $termination;
            }
            if ($asOf < $effectiveEnd) {
                $effectiveEnd = $asOf;
            }

            $workedMonths = $this->countEligibleMonths($cycleStart, $effectiveEnd);
            $accruedDays = min(30.0, $workedMonths * 2.5);

            $status = 'Em aquisição';
            $eligible = false;
            $notice = false;

            if ($termination !== null && $termination <= $acquisitionEnd) {
                $status = 'Encerrado por desligamento';
            } elseif ($workedMonths >= 12) {
                if ($asOf > $concessionEnd) {
                    $status = 'Prazo para concessão vencido — pagar em dobro';
                } elseif ($asOf >= $availableFrom) {
                    $eligible = true;
                    $status = sprintf('Disponível para gozo até %s', $this->formatDate($concessionEnd));
                } else {
                    $status = sprintf('Disponível a partir de %s', $this->formatDate($availableFrom));
                    if ($asOf >= $noticeFrom) {
                        $notice = true;
                        $upcomingNotices[] = [
                            'index' => $index,
                            'available_on' => $availableFrom,
                            'notice_from' => $noticeFrom,
                            'concession_deadline' => $concessionEnd,
                        ];
                    }
                }

                if ($nextCycleCandidate === null) {
                    $nextCycleCandidate = [
                        'index' => $index,
                        'acquisition_start' => $cycleStart,
                        'acquisition_end' => $acquisitionEnd,
                        'concession_end' => $concessionEnd,
                        'available_from' => $availableFrom,
                        'notice_from' => $noticeFrom,
                        'status' => $status,
                        'eligible' => $eligible,
                    ];
                }
            } elseif ($asOf >= $preEligibilityNotice && $asOf <= $acquisitionEnd) {
                $notice = true;
                $upcomingNotices[] = [
                    'index' => $index,
                    'available_on' => $availableFrom,
                    'notice_from' => $preEligibilityNotice,
                    'concession_deadline' => $concessionEnd,
                ];
                if ($nextCycleCandidate === null) {
                    $nextCycleCandidate = [
                        'index' => $index,
                        'acquisition_start' => $cycleStart,
                        'acquisition_end' => $acquisitionEnd,
                        'concession_end' => $concessionEnd,
                        'available_from' => $availableFrom,
                        'notice_from' => $noticeFrom,
                        'status' => sprintf('Disponível a partir de %s', $this->formatDate($availableFrom)),
                        'eligible' => false,
                    ];
                }
            } elseif ($asOf < $cycleStart) {
                $status = 'Período futuro';
            }

            $cycles[] = [
                'index' => $index,
                'acquisition_start' => $cycleStart,
                'acquisition_end' => $acquisitionEnd,
                'concession_end' => $concessionEnd,
                'available_from' => $availableFrom,
                'notice_from' => $noticeFrom,
                'worked_months' => $workedMonths,
                'accrued_days' => $accruedDays,
                'status' => $status,
                'eligible' => $eligible,
                'suggested_reference' => $workedMonths >= 12 ? $availableFrom->format('Y-m') : null,
                'notice' => $notice,
            ];

            $cycleStart = $cycleStart->add(new DateInterval('P1Y'));
        }

        if ($nextCycleCandidate === null && $cycles !== []) {
            $nextCycleCandidate = $cycles[count($cycles) - 1];
        }

        $nextCycle = null;
        if ($nextCycleCandidate !== null) {
            $nextCycle = [
                'index' => $nextCycleCandidate['index'],
                'acquisition_start' => $nextCycleCandidate['acquisition_start'],
                'acquisition_end' => $nextCycleCandidate['acquisition_end'],
                'concession_end' => $nextCycleCandidate['concession_end'],
                'available_from' => $nextCycleCandidate['available_from'],
                'notice_from' => $nextCycleCandidate['notice_from'],
                'status' => $nextCycleCandidate['status'],
                'eligible' => (bool) $nextCycleCandidate['eligible'],
            ];
        }

        return [
            'cycles' => $cycles,
            'upcoming_notices' => $upcomingNotices,
            'next_cycle' => $nextCycle,
            'base_start' => $baseStart,
        ];
    }

    private function formatDate(DateTimeImmutable $date): string
    {
        return $date->format('d/m/Y');
    }

    private function countEligibleMonths(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        if ($end < $start) {
            return 0;
        }

        $months = 0;
        $current = new DateTimeImmutable($start->format('Y-m-01'));

        while ($current <= $end && $months < 12) {
            $monthStart = $current;
            $monthEnd = $current->modify('last day of this month');

            $effectiveStart = $monthStart < $start ? $start : $monthStart;
            $effectiveEnd = $monthEnd > $end ? $end : $monthEnd;

            if ($effectiveEnd >= $effectiveStart) {
                $workedDays = (int) $effectiveEnd->diff($effectiveStart)->format('%a') + 1;
                if ($workedDays >= 15) {
                    $months++;
                }
            }

            $current = $current->add(new DateInterval('P1M'));
        }

        return $months;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthBreakdown(DateTimeImmutable $start, DateTimeImmutable $end, int $year): array
    {
        $months = [];
        $current = new DateTimeImmutable($start->format('Y-m-01'));

        while ($current <= $end && (int) $current->format('Y') === $year) {
            $monthStart = $current;
            $monthEnd = $current->modify('last day of this month');

            $effectiveStart = $monthStart < $start ? $start : $monthStart;
            $effectiveEnd = $monthEnd > $end ? $end : $monthEnd;

            $workedDays = 0;
            $counted = false;

            if ($effectiveEnd >= $effectiveStart) {
                $workedDays = (int) $effectiveEnd->diff($effectiveStart)->format('%a') + 1;
                $counted = $workedDays >= 15;
            }

            $months[] = [
                'label' => $this->monthLabel($monthStart),
                'worked_days' => $workedDays,
                'counted' => $counted,
            ];

            $current = $current->add(new DateInterval('P1M'));
        }

        return $months;
    }

    private function monthLabel(DateTimeImmutable $date): string
    {
        $monthNames = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        $month = (int) $date->format('n');
        $name = $monthNames[$month] ?? $date->format('m');

        return sprintf('%s/%s', $name, $date->format('Y'));
    }

    private function maxDate(DateTimeImmutable $a, DateTimeImmutable $b): DateTimeImmutable
    {
        return $a > $b ? $a : $b;
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}
