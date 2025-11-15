<?php

declare(strict_types=1);

namespace Holerite\Services;

use DateInterval;
use DateTimeImmutable;
use Holerite\Models\HolidaySettings;
use Holerite\Repositories\HolidaySettingsRepository;

final class HolidayService
{
    public function __construct(private HolidaySettingsRepository $settingsRepository)
    {
    }

    public function getSettings(): HolidaySettings
    {
        return $this->settingsRepository->get();
    }

    /**
     * @return array<int, array{date: DateTimeImmutable, name: string, type: string, scope: string}>
     */
    public function getHolidaysForYear(int $year): array
    {
        $settings = $this->getSettings();
        $holidays = [];

        if ($settings->includeNational()) {
            $holidays = array_merge($holidays, $this->buildNationalHolidays($year));
        }

        if ($settings->includeOptional()) {
            $holidays = array_merge($holidays, $this->buildOptionalHolidays($year));
        }

        if ($settings->includeMunicipal()) {
            $holidays = array_merge($holidays, $this->buildMunicipalHolidays($settings, $year));
        }

        usort($holidays, static function (array $a, array $b): int {
            return $a['date'] <=> $b['date'];
        });

        return $holidays;
    }

    /**
     * @return array<int, array{date: DateTimeImmutable, name: string, scope: string, type: string}>
     */
    public function getHolidaysForMonth(DateTimeImmutable $month): array
    {
        $year = (int) $month->format('Y');
        $monthKey = $month->format('Y-m');
        $holidays = $this->getHolidaysForYear($year);

        return array_values(array_filter($holidays, static function (array $holiday) use ($monthKey): bool {
            return $holiday['date']->format('Y-m') === $monthKey;
        }));
    }

    /**
     * @return array<int, array{date: DateTimeImmutable, name: string, type: string, scope: string}>
     */
    private function buildNationalHolidays(int $year): array
    {
        $easter = $this->calculateEaster($year);

        return [
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-01-01', $year)), 'Confraternização Universal', 'national'),
            $this->makeHoliday($easter->sub(new DateInterval('P2D')), 'Sexta-feira Santa', 'national'),
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-04-21', $year)), 'Tiradentes', 'national'),
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-05-01', $year)), 'Dia do Trabalho', 'national'),
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-09-07', $year)), 'Independência do Brasil', 'national'),
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-10-12', $year)), 'Nossa Senhora Aparecida', 'national'),
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-11-02', $year)), 'Finados', 'national'),
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-11-15', $year)), 'Proclamação da República', 'national'),
            $this->makeHoliday(new DateTimeImmutable(sprintf('%d-12-25', $year)), 'Natal', 'national'),
        ];
    }

    /**
     * @return array<int, array{date: DateTimeImmutable, name: string, type: string, scope: string}>
     */
    private function buildOptionalHolidays(int $year): array
    {
        $easter = $this->calculateEaster($year);
        $carnavalTuesday = $easter->sub(new DateInterval('P47D'));
        $carnavalMonday = $carnavalTuesday->sub(new DateInterval('P1D'));
        $ashWednesday = $carnavalTuesday->add(new DateInterval('P1D'));
        $corpusChristi = $easter->add(new DateInterval('P60D'));

        return [
            $this->makeHoliday($carnavalMonday, 'Carnaval (segunda-feira)', 'optional'),
            $this->makeHoliday($carnavalTuesday, 'Carnaval (terça-feira)', 'optional'),
            $this->makeHoliday($ashWednesday, 'Quarta-feira de Cinzas', 'optional'),
            $this->makeHoliday($corpusChristi, 'Corpus Christi', 'optional'),
        ];
    }

    /**
     * @return array<int, array{date: DateTimeImmutable, name: string, type: string, scope: string}>
     */
    private function buildMunicipalHolidays(HolidaySettings $settings, int $year): array
    {
        $records = [];
        foreach ($settings->getMunicipalHolidays() as $holiday) {
            $month = (int) ($holiday['month'] ?? 0);
            $day = (int) ($holiday['day'] ?? 0);
            $name = (string) ($holiday['name'] ?? 'Feriado municipal');

            if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || trim($name) === '') {
                continue;
            }

            $records[] = $this->makeHoliday(
                new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $day)),
                $name,
                'municipal',
                $settings
            );
        }

        return $records;
    }

    private function makeHoliday(DateTimeImmutable $date, string $name, string $type, ?HolidaySettings $settings = null): array
    {
        $scope = match ($type) {
            'optional' => 'facultativo',
            'municipal' => 'municipal',
            default => 'nacional',
        };

        if ($type === 'municipal' && $settings instanceof HolidaySettings) {
            $city = $settings->getCity();
            $state = $settings->getState();
            if ($city !== '' || $state !== '') {
                $name .= sprintf(' — %s%s%s', $city, ($city !== '' && $state !== '' ? ' / ' : ''), $state);
            }
        }

        return [
            'date' => $date,
            'name' => $name,
            'type' => $type,
            'scope' => $scope,
        ];
    }

    private function calculateEaster(int $year): DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $day));
    }
}
