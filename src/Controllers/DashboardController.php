<?php

declare(strict_types=1);

namespace Holerite\Controllers;

use DateTimeImmutable;
use Holerite\Models\Employee;
use Holerite\Models\Payroll;
use Holerite\Repositories\EmployeeRepository;
use Holerite\Repositories\PayrollRepository;
use Holerite\Services\EmployeeBenefitService;
use Holerite\Services\HolidayService;

final class DashboardController extends Controller
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private PayrollRepository $payrollRepository,
        private EmployeeBenefitService $benefitService,
        private HolidayService $holidayService,
    ) {
    }

    public function index(): void
    {
        $employees = $this->employeeRepository->all();
        $allPayrolls = $this->payrollRepository->all();
        $payrolls = array_values(array_filter(
            $allPayrolls,
            static fn (Payroll $payroll): bool => $payroll->getType() !== 'advance',
        ));

        $payrollsByEmployee = [];
        foreach ($allPayrolls as $record) {
            $payrollsByEmployee[$record->getEmployeeId()][] = $record;
        }

        $currentMonth = new DateTimeImmutable('first day of this month');
        $monthParam = $_GET['month'] ?? null;

        if (is_string($monthParam)) {
            $normalizedMonth = trim($monthParam);

            if ($normalizedMonth !== '' && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $normalizedMonth) === 1) {
                $customMonth = DateTimeImmutable::createFromFormat('Y-m-d', $normalizedMonth . '-01');

                if ($customMonth instanceof DateTimeImmutable) {
                    $currentMonth = $customMonth;
                }
            }
        }

        $currentMonth = $currentMonth->setTime(0, 0);
        $previousMonth = $currentMonth->modify('-1 month');
        $nextMonthDate = $currentMonth->modify('+1 month');
        $currentMonthKey = $currentMonth->format('Y-m');
        $currentYearKey = $currentMonth->format('Y');
        $monthEnd = $currentMonth->modify('last day of this month');
        $today = new DateTimeImmutable('today');
        $currentMonthNet = 0.0;

        $totalNet = array_reduce($payrolls, fn (float $carry, $payroll): float => $carry + $payroll->getNetSalary(), 0.0);
        $totalManualValeDeductions = array_reduce($payrolls, fn (float $carry, Payroll $payroll): float => $carry + $payroll->getManualValeDeduction(), 0.0);
        $totalTransportValeDeductions = array_reduce($payrolls, fn (float $carry, Payroll $payroll): float => $carry + $payroll->getTransportDeduction(), 0.0);
        $totalTransportCost = array_reduce($payrolls, fn (float $carry, Payroll $payroll): float => $carry + $payroll->getTransportTotalCost(), 0.0);
        $currentMonthManualVale = 0.0;
        $currentMonthTransportVale = 0.0;
        $currentMonthTransportCost = 0.0;
        $currentYearManualVale = 0.0;
        $currentYearTransportVale = 0.0;
        $currentYearTransportCost = 0.0;
        $lastPayrolls = array_slice($payrolls, 0, 5);

        $monthlyTotals = $this->aggregateTotals($payrolls, 'monthly');
        $yearlyTotals = $this->aggregateTotals($payrolls, 'yearly');
        $monthlyTotalsSummary = $this->summarizeTotals($monthlyTotals);
        $yearlyTotalsSummary = $this->summarizeTotals($yearlyTotals);
        $monthlyValeTotals = $this->aggregateValeTotals($payrolls, 'monthly');
        $yearlyValeTotals = $this->aggregateValeTotals($payrolls, 'yearly');
        $monthlyValeSummary = $this->summarizeValeTotals($monthlyValeTotals);
        $yearlyValeSummary = $this->summarizeValeTotals($yearlyValeTotals);
        $monthlyValeComparisons = $this->buildValeComparisons($monthlyValeTotals);
        $yearlyValeComparisons = $this->buildValeComparisons($yearlyValeTotals);

        $calendarEvents = [];
        $paidRegularPayrolls = [];
        $thirteenthFirstInstallments = [];
        $thirteenthSecondInstallments = [];
        $vacationAlerts = [];
        $birthdayAlerts = [];
        $birthdayPopupAlerts = [];
        $serviceAnniversaryPopupAlerts = [];
        $vacationPopupAlerts = [];
        $holidaySettings = $this->holidayService->getSettings();
        $monthHolidays = $this->holidayService->getHolidaysForMonth($currentMonth);

        foreach ($payrolls as $payroll) {
            $paymentDate = $payroll->getPaymentDate();

            $monthKey = $paymentDate->format('Y-m');
            $yearKey = $paymentDate->format('Y');

            if ($monthKey === $currentMonthKey) {
                $currentMonthNet += $payroll->getNetSalary();
                $currentMonthManualVale += $payroll->getManualValeDeduction();
                $currentMonthTransportVale += $payroll->getTransportDeduction();
                $currentMonthTransportCost += $payroll->getTransportTotalCost();
                if ($payroll->getType() === 'regular') {
                    $paidRegularPayrolls[$payroll->getEmployeeId()] = $payroll;
                }

                if ($payroll->getType() === 'thirteenth') {
                    $installment = $payroll->getThirteenthInstallment() ?? 'first';

                    if ($installment === 'second') {
                        $thirteenthSecondInstallments[] = $payroll;
                    } else {
                        $thirteenthFirstInstallments[] = $payroll;
                    }
                }
            }

            if ($yearKey === $currentYearKey) {
                $currentYearManualVale += $payroll->getManualValeDeduction();
                $currentYearTransportVale += $payroll->getTransportDeduction();
                $currentYearTransportCost += $payroll->getTransportTotalCost();
            }
        }

        $calendarEvents = [];
        $calendarYearNumber = (int) $currentMonth->format('Y');
        $calendarMonthNumber = (int) $currentMonth->format('m');
        $daysInCalendarMonth = (int) $currentMonth->format('t');
        $nextMonthBirthdayAlerts = [];
        $nextMonthMonthNumber = (int) $nextMonthDate->format('m');
        $nextMonthYearNumber = (int) $nextMonthDate->format('Y');
        $daysInNextMonth = (int) $nextMonthDate->format('t');

        foreach ($allPayrolls as $calendarPayroll) {
            $paymentDate = $calendarPayroll->getPaymentDate();

            if ($paymentDate->format('Y-m') !== $currentMonthKey) {
                continue;
            }

            $dateKey = $paymentDate->format('Y-m-d');

            if (!isset($calendarEvents[$dateKey])) {
                $calendarEvents[$dateKey] = [
                    'payrolls' => [],
                    'birthdays' => [],
                    'vacations' => [],
                    'holidays' => [],
                ];
            }

            $calendarEvents[$dateKey]['payrolls'][] = $calendarPayroll;
        }

        $todayKey = $today->format('Y-m-d');

        foreach ($employees as $employee) {
            $terminationDate = $employee->getTerminationDate();

            if ($terminationDate !== null && $terminationDate < $currentMonth) {
                continue;
            }

            if ($employee->getHireDate() > $monthEnd) {
                continue;
            }

            $employeeId = $employee->getId();
            $employeePayrolls = $employeeId !== null ? ($payrollsByEmployee[$employeeId] ?? []) : [];
            $benefits = $this->benefitService->summarize($employee, $employeePayrolls, $today);
            $vacations = $benefits['vacations'] ?? [];
            $vacationBaseStart = $vacations['base_start'] ?? null;
            $customVacationBase = $employee->getVacationBaseDate();
            $vacationBaseOrigin = $customVacationBase instanceof DateTimeImmutable ? 'manual' : 'admission';
            $vacationBaseLabel = $vacationBaseStart instanceof DateTimeImmutable ? $vacationBaseStart : null;
            $vacationBaseOriginLabel = $vacationBaseOrigin === 'manual'
                ? 'data base ajustada'
                : 'data de admissão';
            $nextCycle = $vacations['next_cycle'] ?? null;

            if (is_array($nextCycle) && isset($nextCycle['available_from']) && $nextCycle['available_from'] instanceof DateTimeImmutable) {
                $availableFrom = $nextCycle['available_from'];
                $noticeFrom = $nextCycle['notice_from'] ?? null;
                $concessionEnd = $nextCycle['concession_end'] ?? null;
                $includeAlert = $availableFrom->format('Y-m') === $currentMonthKey;

                if (!$includeAlert && $noticeFrom instanceof DateTimeImmutable) {
                    $includeAlert = $noticeFrom <= $monthEnd && $availableFrom >= $currentMonth;
                }

                if ($includeAlert) {
                    $vacationAlerts[] = [
                        'employee' => $employee,
                        'available_from' => $availableFrom,
                        'concession_end' => $concessionEnd instanceof DateTimeImmutable ? $concessionEnd : null,
                        'status' => (string) ($nextCycle['status'] ?? ''),
                        'eligible' => (bool) ($nextCycle['eligible'] ?? false),
                        'notice_from' => $noticeFrom instanceof DateTimeImmutable ? $noticeFrom : null,
                    ];
                }

                if ($noticeFrom instanceof DateTimeImmutable && $availableFrom instanceof DateTimeImmutable) {
                    $popupWindowEnd = $concessionEnd instanceof DateTimeImmutable ? $concessionEnd : $availableFrom;

                    if ($noticeFrom <= $today && $today <= $popupWindowEnd) {
                        $vacationPopupAlerts[] = [
                            'employee' => $employee,
                            'available_from' => $availableFrom,
                            'notice_from' => $noticeFrom,
                            'status' => (string) ($nextCycle['status'] ?? ''),
                            'base_origin' => $vacationBaseOrigin,
                            'base_origin_label' => $vacationBaseOriginLabel,
                            'base_start' => $vacationBaseLabel,
                        ];
                    }
                }

                if ($availableFrom->format('Y-m') === $currentMonthKey) {
                    $vacationDateKey = $availableFrom->format('Y-m-d');

                    if (!isset($calendarEvents[$vacationDateKey])) {
                        $calendarEvents[$vacationDateKey] = [
                            'payrolls' => [],
                            'birthdays' => [],
                            'vacations' => [],
                            'holidays' => [],
                        ];
                    }

                    $calendarEvents[$vacationDateKey]['vacations'][] = [
                        'employee' => $employee,
                        'available_from' => $availableFrom,
                        'concession_end' => $concessionEnd instanceof DateTimeImmutable ? $concessionEnd : null,
                        'status' => (string) ($nextCycle['status'] ?? ''),
                        'notice_from' => $noticeFrom instanceof DateTimeImmutable ? $noticeFrom : null,
                    ];
                }
            }

            $birthDate = $employee->getBirthDate();
            $birthMonth = (int) $birthDate->format('m');
            $birthDay = (int) $birthDate->format('d');

            if ($birthMonth === $calendarMonthNumber) {
                $targetDay = min($birthDay, $daysInCalendarMonth);
                $birthdayDate = $currentMonth->setDate($calendarYearNumber, $calendarMonthNumber, $targetDay);
                $dateKey = $birthdayDate->format('Y-m-d');

                if (!isset($calendarEvents[$dateKey])) {
                    $calendarEvents[$dateKey] = [
                        'payrolls' => [],
                        'birthdays' => [],
                        'vacations' => [],
                        'holidays' => [],
                    ];
                }

                $calendarEvents[$dateKey]['birthdays'][] = $employee;
                $birthdayAlerts[] = [
                    'employee' => $employee,
                    'date' => $birthdayDate,
                ];

                if ($birthdayDate->format('Y-m-d') === $todayKey) {
                    $birthdayPopupAlerts[] = [
                        'employee' => $employee,
                        'date' => $birthdayDate,
                    ];
                }
            }

            if ($birthMonth === $nextMonthMonthNumber) {
                $nextTargetDay = min($birthDay, $daysInNextMonth);
                $nextBirthdayDate = $nextMonthDate->setDate($nextMonthYearNumber, $nextMonthMonthNumber, $nextTargetDay);

                $nextMonthBirthdayAlerts[] = [
                    'employee' => $employee,
                    'date' => $nextBirthdayDate,
                ];
            }

            $hireDate = $employee->getHireDate();
            if ($hireDate instanceof DateTimeImmutable) {
                $hireMonth = (int) $hireDate->format('m');
                if ($hireMonth === $calendarMonthNumber) {
                    $hireDay = (int) $hireDate->format('d');
                    $anniversaryDay = min($hireDay, $daysInCalendarMonth);
                    $anniversaryDate = $currentMonth->setDate($calendarYearNumber, $calendarMonthNumber, $anniversaryDay);

                    if ($anniversaryDate >= $hireDate) {
                        $serviceYears = $hireDate->diff($anniversaryDate)->y;

                        if ($serviceYears >= 1 && $anniversaryDate->format('Y-m-d') === $todayKey) {
                            $serviceAnniversaryPopupAlerts[] = [
                                'employee' => $employee,
                                'date' => $anniversaryDate,
                                'years' => $serviceYears,
                                'hire_date' => $hireDate,
                            ];
                        }
                    }
                }
            }
        }

        foreach ($monthHolidays as $holiday) {
            $dateKey = $holiday['date']->format('Y-m-d');

            if (!isset($calendarEvents[$dateKey])) {
                $calendarEvents[$dateKey] = [
                    'payrolls' => [],
                    'birthdays' => [],
                    'vacations' => [],
                    'holidays' => [],
                ];
            }

            $calendarEvents[$dateKey]['holidays'][] = $holiday;
        }

        usort($vacationAlerts, static function (array $a, array $b): int {
            $dateA = $a['available_from'] ?? null;
            $dateB = $b['available_from'] ?? null;

            if ($dateA instanceof DateTimeImmutable && $dateB instanceof DateTimeImmutable) {
                $comparison = $dateA <=> $dateB;
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcasecmp($a['employee']->getName(), $b['employee']->getName());
        });

        usort($birthdayAlerts, static function (array $a, array $b): int {
            $dateA = $a['date'] ?? null;
            $dateB = $b['date'] ?? null;

            if ($dateA instanceof DateTimeImmutable && $dateB instanceof DateTimeImmutable) {
                $comparison = $dateA <=> $dateB;
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcasecmp($a['employee']->getName(), $b['employee']->getName());
        });

        usort($nextMonthBirthdayAlerts, static function (array $a, array $b): int {
            $dateA = $a['date'] ?? null;
            $dateB = $b['date'] ?? null;

            if ($dateA instanceof DateTimeImmutable && $dateB instanceof DateTimeImmutable) {
                $comparison = $dateA <=> $dateB;
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcasecmp($a['employee']->getName(), $b['employee']->getName());
        });

        usort($vacationPopupAlerts, static function (array $a, array $b): int {
            $noticeA = $a['notice_from'] ?? null;
            $noticeB = $b['notice_from'] ?? null;

            if ($noticeA instanceof DateTimeImmutable && $noticeB instanceof DateTimeImmutable) {
                $comparison = $noticeA <=> $noticeB;
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcasecmp($a['employee']->getName(), $b['employee']->getName());
        });

        usort($birthdayPopupAlerts, static function (array $a, array $b): int {
            $dateA = $a['date'] ?? null;
            $dateB = $b['date'] ?? null;

            if ($dateA instanceof DateTimeImmutable && $dateB instanceof DateTimeImmutable) {
                $comparison = $dateA <=> $dateB;
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcasecmp($a['employee']->getName(), $b['employee']->getName());
        });

        usort($serviceAnniversaryPopupAlerts, static function (array $a, array $b): int {
            $dateA = $a['date'] ?? null;
            $dateB = $b['date'] ?? null;

            if ($dateA instanceof DateTimeImmutable && $dateB instanceof DateTimeImmutable) {
                $comparison = $dateA <=> $dateB;
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcasecmp($a['employee']->getName(), $b['employee']->getName());
        });

        $totalTransportCost = round($totalTransportCost, 2);
        $currentMonthTransportCost = round($currentMonthTransportCost, 2);
        $currentYearTransportCost = round($currentYearTransportCost, 2);

        $totalTransportValeDeductions = round($totalTransportValeDeductions, 2);
        $currentMonthTransportVale = round($currentMonthTransportVale, 2);
        $currentYearTransportVale = round($currentYearTransportVale, 2);

        $totalTransportCompanyShare = round(max(0.0, $totalTransportCost - $totalTransportValeDeductions), 2);
        $currentMonthTransportCompanyShare = round(max(0.0, $currentMonthTransportCost - $currentMonthTransportVale), 2);
        $currentYearTransportCompanyShare = round(max(0.0, $currentYearTransportCost - $currentYearTransportVale), 2);

        $monthlyChart = $this->buildChartPayload($monthlyTotals);
        $yearlyChart = $this->buildChartPayload($yearlyTotals);
        $monthlyValeManualChart = $this->buildChartPayload($this->extractValeSeries($monthlyValeTotals, 'manual'));
        $monthlyValeTransportChart = $this->buildChartPayload($this->extractValeSeries($monthlyValeTotals, 'transport'));
        $yearlyValeManualChart = $this->buildChartPayload($this->extractValeSeries($yearlyValeTotals, 'manual'));
        $yearlyValeTransportChart = $this->buildChartPayload($this->extractValeSeries($yearlyValeTotals, 'transport'));

        $calendarWeeks = $this->buildCalendarWeeks($currentMonth, $calendarEvents);

        $employeesById = [];
        foreach ($employees as $employee) {
            $id = $employee->getId();
            if ($id !== null) {
                $employeesById[$id] = $employee;
            }
        }

        $compareByEmployeeName = function (Payroll $a, Payroll $b) use ($employeesById): int {
            $nameA = isset($employeesById[$a->getEmployeeId()])
                ? $employeesById[$a->getEmployeeId()]->getName()
                : '';
            $nameB = isset($employeesById[$b->getEmployeeId()])
                ? $employeesById[$b->getEmployeeId()]->getName()
                : '';

            return strcasecmp($nameA, $nameB);
        };

        $paidMonthlyPayrolls = array_values($paidRegularPayrolls);
        usort($paidMonthlyPayrolls, $compareByEmployeeName);
        usort($thirteenthFirstInstallments, $compareByEmployeeName);
        usort($thirteenthSecondInstallments, $compareByEmployeeName);

        $pendingMonthlyEmployees = [];
        foreach ($employees as $employee) {
            $id = $employee->getId();
            if ($id === null) {
                continue;
            }

            $terminationDate = $employee->getTerminationDate();
            if ($terminationDate !== null && $terminationDate < $currentMonth) {
                continue;
            }

            if ($employee->getHireDate() > $monthEnd) {
                continue;
            }

            if (!isset($paidRegularPayrolls[$id])) {
                $pendingMonthlyEmployees[] = $employee;
            }
        }

        usort($pendingMonthlyEmployees, static fn ($a, $b): int => strcasecmp($a->getName(), $b->getName()));

        $calendarNavigation = [
            'current' => [
                'label' => sprintf('%s de %s', $this->getMonthDisplayName($currentMonth), $currentMonth->format('Y')),
                'month' => $currentMonth->format('Y-m'),
            ],
            'previous' => [
                'label' => sprintf('%s de %s', $this->getMonthDisplayName($previousMonth), $previousMonth->format('Y')),
                'month' => $previousMonth->format('Y-m'),
            ],
            'next' => [
                'label' => sprintf('%s de %s', $this->getMonthDisplayName($nextMonthDate), $nextMonthDate->format('Y')),
                'month' => $nextMonthDate->format('Y-m'),
            ],
        ];

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'totalEmployees' => count($employees),
            'totalPayrolls' => count($allPayrolls),
            'totalNet' => $totalNet,
            'lastPayrolls' => $lastPayrolls,
            'employees' => $employees,
            'monthlyTotals' => $monthlyTotals,
            'yearlyTotals' => $yearlyTotals,
            'monthlyTotalsSummary' => $monthlyTotalsSummary,
            'yearlyTotalsSummary' => $yearlyTotalsSummary,
            'monthlyValeTotals' => $monthlyValeTotals,
            'yearlyValeTotals' => $yearlyValeTotals,
            'monthlyValeSummary' => $monthlyValeSummary,
            'yearlyValeSummary' => $yearlyValeSummary,
            'monthlyValeComparisons' => $monthlyValeComparisons,
            'yearlyValeComparisons' => $yearlyValeComparisons,
            'monthlyChart' => $monthlyChart,
            'yearlyChart' => $yearlyChart,
            'monthlyValeManualChart' => $monthlyValeManualChart,
            'monthlyValeTransportChart' => $monthlyValeTransportChart,
            'yearlyValeManualChart' => $yearlyValeManualChart,
            'yearlyValeTransportChart' => $yearlyValeTransportChart,
            'calendarMonth' => $currentMonth,
            'calendarWeeks' => $calendarWeeks,
            'paidMonthlyPayrolls' => $paidMonthlyPayrolls,
            'pendingMonthlyEmployees' => $pendingMonthlyEmployees,
            'vacationAlerts' => $vacationAlerts,
            'birthdayAlerts' => $birthdayAlerts,
            'nextMonthBirthdayAlerts' => $nextMonthBirthdayAlerts,
            'vacationPopupAlerts' => $vacationPopupAlerts,
            'birthdayPopupAlerts' => $birthdayPopupAlerts,
            'serviceAnniversaryPopupAlerts' => $serviceAnniversaryPopupAlerts,
            'today' => $today,
            'holidaySettings' => $holidaySettings,
            'valeTotals' => [
                'manual' => [
                    'overall' => $totalManualValeDeductions,
                    'currentMonth' => $currentMonthManualVale,
                    'currentYear' => $currentYearManualVale,
                ],
                'transport' => [
                    'overall' => $totalTransportValeDeductions,
                    'currentMonth' => $currentMonthTransportVale,
                    'currentYear' => $currentYearTransportVale,
                    'costOverall' => $totalTransportCost,
                    'costCurrentMonth' => $currentMonthTransportCost,
                    'costCurrentYear' => $currentYearTransportCost,
                    'companyOverall' => $totalTransportCompanyShare,
                    'companyCurrentMonth' => $currentMonthTransportCompanyShare,
                    'companyCurrentYear' => $currentYearTransportCompanyShare,
                ],
            ],
            'currentMonthNet' => $currentMonthNet,
            'currentMonthLabel' => $this->getMonthDisplayName($currentMonth),
            'nextMonthLabel' => $this->getMonthDisplayName($nextMonthDate),
            'thirteenthFirstInstallments' => $thirteenthFirstInstallments,
            'thirteenthSecondInstallments' => $thirteenthSecondInstallments,
            'calendarNavigation' => $calendarNavigation,
        ]);
    }

    public function report(string $scope): void
    {
        $normalizedScope = $scope === 'yearly' ? 'yearly' : 'monthly';

        $allPayrolls = $this->payrollRepository->all();
        $payrolls = array_values(array_filter(
            $allPayrolls,
            static fn (Payroll $payroll): bool => $payroll->getType() !== 'advance',
        ));

        $totals = $this->aggregateReportTotals($payrolls, $normalizedScope);
        $valeBreakdown = $this->aggregateValeTotals($payrolls, $normalizedScope);
        $totalManualVale = array_reduce($payrolls, fn (float $carry, Payroll $payroll): float => $carry + $payroll->getManualValeDeduction(), 0.0);
        $totalTransportVale = array_reduce($payrolls, fn (float $carry, Payroll $payroll): float => $carry + $payroll->getTransportDeduction(), 0.0);

        $title = $normalizedScope === 'yearly'
            ? 'Relatório anual de pagamentos'
            : 'Relatório mensal de pagamentos';

        $description = $normalizedScope === 'yearly'
            ? 'Resumo anual dos pagamentos líquidos efetuados.'
            : 'Resumo mensal dos pagamentos líquidos efetuados.';

        $periodCount = count($totals);
        $totalAmount = 0.0;
        $highestRow = null;
        $lowestRow = null;

        foreach ($totals as $row) {
            $totalAmount += (float) $row['total'];

            if ($highestRow === null || (float) $row['total'] > (float) $highestRow['total']) {
                $highestRow = $row;
            }

            if ($lowestRow === null || (float) $row['total'] < (float) $lowestRow['total']) {
                $lowestRow = $row;
            }
        }

        $totalAmount = $this->roundMoney($totalAmount);
        $averageAmount = $periodCount > 0 ? $this->roundMoney($totalAmount / $periodCount) : 0.0;
        $latestRow = $periodCount > 0 ? $totals[0] : null;
        $earliestRow = $periodCount > 0 ? $totals[$periodCount - 1] : null;

        $payrollCount = count($payrolls);
        $employeeIds = [];

        foreach ($payrolls as $payroll) {
            $employeeIds[$payroll->getEmployeeId()] = true;
        }

        $employeeCount = count($employeeIds);
        $breakdown = [];

        foreach ($totals as $row) {
            $percentage = $totalAmount > 0.0
                ? round(((float) $row['total'] / $totalAmount) * 100, 2)
                : 0.0;

            $breakdown[] = [
                'period' => $row['period'],
                'total' => (float) $row['total'],
                'count' => (int) $row['count'],
                'percentage' => $percentage,
            ];
        }

        $this->render('dashboard/report', [
            'title' => $title,
            'scope' => $normalizedScope,
            'scopeLabel' => $normalizedScope === 'yearly' ? 'Anual' : 'Mensal',
            'totals' => $totals,
            'description' => $description,
            'summary' => [
                'totalAmount' => $totalAmount,
                'averageAmount' => $averageAmount,
                'periodCount' => $periodCount,
                'highest' => $highestRow,
                'lowest' => $lowestRow,
                'latest' => $latestRow,
                'earliest' => $earliestRow,
                'payrollCount' => $payrollCount,
                'employeeCount' => $employeeCount,
                'valeTotals' => [
                    'manual' => $totalManualVale,
                    'transport' => $totalTransportVale,
                    'combined' => $totalManualVale + $totalTransportVale,
                ],
            ],
            'breakdown' => $breakdown,
            'valeBreakdown' => $valeBreakdown,
        ]);
    }

    /**
     * @param array<int, array{period: string, total: float}> $totals
     * @return array{labels: array<int, string>, values: array<int, float>, percentages: array<int, float>, total: float}
     */
    private function buildChartPayload(array $totals): array
    {
        if ($totals === []) {
            return [
                'labels' => [],
                'values' => [],
                'percentages' => [],
                'total' => 0.0,
            ];
        }

        $labels = array_column($totals, 'period');
        $values = array_map(static fn (array $row): float => (float) $row['total'], $totals);
        $total = array_sum($values);

        $percentages = $total > 0.0
            ? array_map(function (float $value) use ($total): float {
                return round(($value / $total) * 100, 1);
            }, $values)
            : array_fill(0, count($values), 0.0);

        return [
            'labels' => $labels,
            'values' => $values,
            'percentages' => $percentages,
            'total' => $total,
        ];
    }

    /**
     * @param DateTimeImmutable $month
     * @param array<string, array{
     *     payrolls: array<int, Payroll>,
     *     birthdays: array<int, Employee>,
     *     vacations: array<int, array>,
     *     holidays: array<int, array>
     * }> $events
     * @return array<int, array<int, array{
     *     date: DateTimeImmutable|null,
     *     payrolls: array<int, Payroll>,
     *     birthdays: array<int, Employee>,
     *     vacations: array<int, array>,
     *     holidays: array<int, array>
     * }>>
     */
    private function buildCalendarWeeks(DateTimeImmutable $month, array $events): array
    {
        $weeks = [];
        $week = [];

        $firstWeekday = (int) $month->format('N');
        for ($i = 1; $i < $firstWeekday; $i++) {
            $week[] = ['date' => null, 'payrolls' => [], 'birthdays' => [], 'vacations' => [], 'holidays' => []];
        }

        $daysInMonth = (int) $month->format('t');
        $year = (int) $month->format('Y');
        $monthNumber = (int) $month->format('m');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month->setDate($year, $monthNumber, $day);
            $dateKey = $date->format('Y-m-d');

            $dayEvents = $events[$dateKey] ?? ['payrolls' => [], 'birthdays' => [], 'vacations' => [], 'holidays' => []];

            $week[] = [
                'date' => $date,
                'payrolls' => $dayEvents['payrolls'] ?? [],
                'birthdays' => $dayEvents['birthdays'] ?? [],
                'vacations' => $dayEvents['vacations'] ?? [],
                'holidays' => $dayEvents['holidays'] ?? [],
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        if ($week !== []) {
            while (count($week) < 7) {
                $week[] = ['date' => null, 'payrolls' => [], 'birthdays' => [], 'vacations' => [], 'holidays' => []];
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * @param array<int, Payroll> $payrolls
     * @return array<int, array{period: string, total: float, count: int}>
     */
    private function aggregateTotals(array $payrolls, string $scope): array
    {
        $normalizedScope = $scope === 'yearly' ? 'yearly' : 'monthly';

        $totals = [];

        foreach ($payrolls as $payroll) {
            $paymentDate = $payroll->getPaymentDate();

            if ($normalizedScope === 'yearly') {
                $key = $paymentDate->format('Y');
                $label = $key;
            } else {
                $key = $paymentDate->format('Y-m');
                $label = $paymentDate->format('m/Y');
            }

            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'period' => $label,
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $totals[$key]['total'] += $payroll->getNetSalary();
            $totals[$key]['count']++;
        }

        if ($totals === []) {
            return [];
        }

        uksort($totals, static fn (string $a, string $b): int => strcmp($b, $a));

        return array_values($totals);
    }

    /**
     * @param array<int, Payroll> $payrolls
     * @return array<int, array{period: string, total: float, count: int}>
     */
    private function aggregateReportTotals(array $payrolls, string $scope): array
    {
        $normalizedScope = $scope === 'yearly' ? 'yearly' : 'monthly';

        $totals = [];

        foreach ($payrolls as $payroll) {
            if ($payroll->getType() === 'advance') {
                continue;
            }

            $amount = $this->roundMoney($this->getReportPayoutAmount($payroll));

            $paymentDate = $payroll->getPaymentDate();
            $key = $normalizedScope === 'yearly' ? $paymentDate->format('Y') : $paymentDate->format('Y-m');
            $label = $normalizedScope === 'yearly' ? $key : $paymentDate->format('m/Y');

            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'period' => $label,
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $totals[$key]['total'] += $amount;
            $totals[$key]['count']++;
        }

        if ($totals === []) {
            return [];
        }

        foreach ($totals as &$row) {
            $row['total'] = $this->roundMoney($row['total']);
        }
        unset($row);

        uksort($totals, static fn (string $a, string $b): int => strcmp($b, $a));

        return array_values($totals);
    }

    /**
     * @param array<int, array{period: string, total: float, count: int}> $totals
     * @return array{total: float, count: int}
     */
    private function summarizeTotals(array $totals): array
    {
        $summary = [
            'total' => 0.0,
            'count' => 0,
        ];

        foreach ($totals as $row) {
            $summary['total'] += (float) $row['total'];
            $summary['count'] += (int) $row['count'];
        }

        $summary['total'] = round($summary['total'], 2);

        return $summary;
    }

    /**
     * @param array<int, Payroll> $payrolls
     * @return array<int, array{period: string, manual: float, transport: float, total: float, transport_days: int, transport_trips: int}>
     */
    private function aggregateValeTotals(array $payrolls, string $scope): array
    {
        $normalizedScope = $scope === 'yearly' ? 'yearly' : 'monthly';

        $totals = [];

        foreach ($payrolls as $payroll) {
            if ($payroll->getType() === 'advance') {
                continue;
            }

            $paymentDate = $payroll->getPaymentDate();
            $key = $normalizedScope === 'yearly' ? $paymentDate->format('Y') : $paymentDate->format('Y-m');
            $label = $normalizedScope === 'yearly' ? $key : $paymentDate->format('m/Y');

            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'period' => $label,
                    'manual' => 0.0,
                    'transport' => 0.0,
                    'total' => 0.0,
                    'transport_days' => 0,
                    'transport_trips' => 0,
                ];
            }

            $totals[$key]['manual'] += $payroll->getManualValeDeduction();
            $totals[$key]['transport'] += $payroll->getTransportDeduction();
            $totals[$key]['transport_days'] += $payroll->getTransportDays();
            $totals[$key]['transport_trips'] += $payroll->getTransportTrips();
            $totals[$key]['total'] = $totals[$key]['manual'] + $totals[$key]['transport'];
        }

        if ($totals === []) {
            return [];
        }

        uksort($totals, static fn (string $a, string $b): int => strcmp($b, $a));

        return array_values($totals);
    }

    /**
     * @param array<int, array{period: string, manual: float, transport: float, total: float, transport_days: int, transport_trips: int}> $totals
     * @return array{manual: float, transport: float, total: float, transport_days: int, transport_trips: int}
     */
    private function summarizeValeTotals(array $totals): array
    {
        $summary = [
            'manual' => 0.0,
            'transport' => 0.0,
            'total' => 0.0,
            'transport_days' => 0,
            'transport_trips' => 0,
        ];

        foreach ($totals as $row) {
            $summary['manual'] += (float) $row['manual'];
            $summary['transport'] += (float) $row['transport'];
            $summary['total'] += (float) $row['total'];
            $summary['transport_days'] += (int) $row['transport_days'];
            $summary['transport_trips'] += (int) $row['transport_trips'];
        }

        $summary['manual'] = round($summary['manual'], 2);
        $summary['transport'] = round($summary['transport'], 2);
        $summary['total'] = round($summary['total'], 2);

        return $summary;
    }

    /**
     * @param array<int, array{period: string, manual: float, transport: float, total: float, transport_days: int, transport_trips: int}> $totals
     * @return array<int, array{period: string, total: float}>
     */
    private function extractValeSeries(array $totals, string $key): array
    {
        if ($key !== 'manual' && $key !== 'transport') {
            return [];
        }

        return array_map(
            static fn (array $row): array => [
                'period' => $row['period'],
                'total' => (float) $row[$key],
            ],
            $totals
        );
    }

    /**
     * @param array<int, array{period: string, manual: float, transport: float, total: float}> $totals
     * @return array{
     *     manual: array{current: array{period: string, value: float|null}|null, previous: array{period: string, value: float|null}|null, difference: float|null, percentage: float|null},
     *     transport: array{current: array{period: string, value: float|null}|null, previous: array{period: string, value: float|null}|null, difference: float|null, percentage: float|null}
     * }
     */
    private function buildValeComparisons(array $totals): array
    {
        $current = $totals[0] ?? null;
        $previous = $totals[1] ?? null;

        $comparisons = [];

        foreach (['manual', 'transport'] as $key) {
            $currentValue = $current !== null ? round((float) $current[$key], 2) : null;
            $previousValue = $previous !== null ? round((float) $previous[$key], 2) : null;

            $difference = null;
            $percentage = null;

            if ($currentValue !== null && $previousValue !== null) {
                $difference = round($currentValue - $previousValue, 2);

                if (abs($previousValue) > 0.00001) {
                    $percentage = round((($currentValue - $previousValue) / $previousValue) * 100, 1);
                }
            }

            $comparisons[$key] = [
                'current' => $current !== null ? [
                    'period' => $current['period'],
                    'value' => $currentValue,
                ] : null,
                'previous' => $previous !== null ? [
                    'period' => $previous['period'],
                    'value' => $previousValue,
                ] : null,
                'difference' => $difference,
                'percentage' => $percentage,
            ];
        }

        return $comparisons;
    }

    private function getReportPayoutAmount(Payroll $payroll): float
    {
        if ($payroll->getType() === 'advance') {
            return max(0.0, round($payroll->getAdvanceAmount(), 2));
        }

        $remaining = $payroll->getRemainingAmount();

        if ($remaining <= 0.0 && $payroll->getAdvanceAmount() <= 0.0) {
            $remaining = $payroll->getNetSalary();
        }

        return max(0.0, round($remaining, 2));
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }

    private function getMonthDisplayName(DateTimeImmutable $date): string
    {
        $months = [
            '01' => 'janeiro',
            '02' => 'fevereiro',
            '03' => 'março',
            '04' => 'abril',
            '05' => 'maio',
            '06' => 'junho',
            '07' => 'julho',
            '08' => 'agosto',
            '09' => 'setembro',
            '10' => 'outubro',
            '11' => 'novembro',
            '12' => 'dezembro',
        ];

        $monthKey = $date->format('m');
        $name = $months[$monthKey] ?? $date->format('F');

        if (function_exists('mb_convert_case') && defined('MB_CASE_TITLE')) {
            return mb_convert_case($name, constant('MB_CASE_TITLE'), 'UTF-8');
        }

        return ucfirst($name);
    }
}
