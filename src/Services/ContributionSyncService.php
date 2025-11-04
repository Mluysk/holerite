<?php

declare(strict_types=1);

namespace Holerite\Services;

use Holerite\Models\ContributionSettings;
use Holerite\Repositories\ContributionSettingsRepository;
use RuntimeException;

final class ContributionSyncService
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private ContributionSettingsRepository $contributionRepository,
        private array $config = [],
    ) {
    }

    public function syncFromApi(): ContributionSettings
    {
        $endpoint = trim((string) ($this->config['endpoint'] ?? ''));

        if ($endpoint === '') {
            throw new RuntimeException('Nenhum endpoint foi configurado para atualização automática.');
        }

        $method = strtoupper((string) ($this->config['method'] ?? 'GET'));
        if (!in_array($method, ['GET', 'POST'], true)) {
            $method = 'GET';
        }

        $timeout = (int) ($this->config['timeout'] ?? 10);
        if ($timeout <= 0) {
            $timeout = 10;
        }

        $body = $this->prepareRequestBody($method);
        $headers = $this->buildHeaders($body !== null);

        $response = $this->performRequest($endpoint, $method, $timeout, $headers, $body);

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('A resposta da API de contribuições é inválida ou não está em JSON.');
        }

        $payload = $this->unwrapPayload($decoded);
        $current = $this->contributionRepository->get();
        $defaults = $this->contributionRepository->getDefault();

        $fgtsRate = $this->extractFgtsRate($payload, $current->getFgtsRate());
        $inss = $this->extractContributionTable(
            $payload,
            ['inss', 'inss_brackets', 'inss_table', 'inssRates'],
            $current->getInssBrackets(),
            $defaults->getInssBrackets(),
            false,
        );
        $irrf = $this->extractContributionTable(
            $payload,
            ['irrf', 'irrf_brackets', 'irrf_table', 'irrfRates'],
            $current->getIrrfBrackets(),
            $defaults->getIrrfBrackets(),
            true,
        );

        $settings = new ContributionSettings(
            $current->getId(),
            $fgtsRate,
            $inss,
            $irrf,
            $current->getManualAllowances(),
        );

        $this->contributionRepository->save($settings);

        return $this->contributionRepository->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractFgtsRate(array $payload, float $fallback): float
    {
        $candidates = [
            $payload['fgts_rate'] ?? null,
            $payload['fgtsPercent'] ?? null,
            $payload['fgts'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $rate = $this->parseRate($candidate);
            if ($rate !== null) {
                return $rate;
            }
        }

        if (isset($payload['fgts']) && is_array($payload['fgts'])) {
            $nestedCandidates = [
                $payload['fgts']['rate'] ?? null,
                $payload['fgts']['percent'] ?? null,
                $payload['fgts']['aliquota'] ?? null,
            ];

            foreach ($nestedCandidates as $candidate) {
                $rate = $this->parseRate($candidate);
                if ($rate !== null) {
                    return $rate;
                }
            }
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $payload
     * @param string[] $keys
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $fallback
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $defaults
     * @return array<int, array{limit: float|null, rate: float, deduction?: float}>
     */
    private function extractContributionTable(
        array $payload,
        array $keys,
        array $fallback,
        array $defaults,
        bool $withDeduction
    ): array {
        $raw = null;
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $raw = $payload[$key];
                break;
            }
        }

        if ($raw === null) {
            return $fallback;
        }

        $normalized = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $limit = $this->parseNumber($entry['limit'] ?? $entry['ceiling'] ?? $entry['max'] ?? null);
            if ($limit !== null) {
                $limit = round($limit, 2);
                if ($limit <= 0) {
                    $limit = null;
                }
            }

            $rateValue = $entry['rate'] ?? $entry['aliquota'] ?? $entry['percent'] ?? $entry['percentage'] ?? null;
            $rate = $this->parseRate($rateValue);
            if ($rate === null) {
                continue;
            }

            $row = [
                'limit' => $limit,
                'rate' => $rate,
            ];

            if ($withDeduction) {
                $deductionValue = $entry['deduction'] ?? $entry['parcel'] ?? $entry['parcela'] ?? $entry['deduct'] ?? null;
                $deduction = $this->parseNumber($deductionValue);
                $row['deduction'] = $deduction !== null ? round($deduction, 2) : 0.0;
            }

            $normalized[] = $row;
        }

        if ($normalized === []) {
            return $fallback;
        }

        $normalized = $this->finalizeContributionBrackets($normalized, $defaults, $withDeduction);

        return array_values($normalized);
    }

    /**
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $brackets
     * @param array<int, array{limit: float|null, rate: float, deduction?: float}> $defaults
     * @return array<int, array{limit: float|null, rate: float, deduction?: float}>
     */
    private function finalizeContributionBrackets(array $brackets, array $defaults, bool $withDeduction): array
    {
        usort($brackets, static function (array $a, array $b): int {
            $limitA = $a['limit'];
            $limitB = $b['limit'];

            if ($limitA === $limitB) {
                return 0;
            }

            if ($limitA === null) {
                return 1;
            }

            if ($limitB === null) {
                return -1;
            }

            return $limitA <=> $limitB;
        });

        $last = end($brackets);
        $fallback = end($defaults);

        $lastRate = is_array($last) && isset($last['rate']) ? (float) $last['rate'] : (is_array($fallback) && isset($fallback['rate']) ? (float) $fallback['rate'] : 0.0);
        $lastDeduction = 0.0;

        if ($withDeduction) {
            $lastDeduction = is_array($last) && isset($last['deduction']) ? (float) $last['deduction'] : 0.0;
            if (is_array($fallback) && isset($fallback['deduction'])) {
                $lastDeduction = (float) $fallback['deduction'];
            }
            if (is_array($last) && isset($last['deduction'])) {
                $lastDeduction = (float) $last['deduction'];
            }
        }

        if ($last === false || $last['limit'] !== null) {
            $newRow = [
                'limit' => null,
                'rate' => $lastRate,
            ];

            if ($withDeduction) {
                $newRow['deduction'] = $lastDeduction;
            }

            $brackets[] = $newRow;
        }

        if ($withDeduction) {
            foreach ($brackets as &$row) {
                if (!isset($row['deduction'])) {
                    $row['deduction'] = 0.0;
                }
            }
            unset($row);
        }

        return $brackets;
    }

    private function parseRate(mixed $value): ?float
    {
        $number = $this->parseNumber($value);
        if ($number === null) {
            return null;
        }

        if ($number > 1) {
            $number /= 100;
        }

        if ($number < 0) {
            $number = 0.0;
        }

        if ($number > 1) {
            $number = 1.0;
        }

        return round($number, 6);
    }

    private function parseNumber(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            foreach (['value', 'amount', 'number'] as $key) {
                if (isset($value[$key])) {
                    return $this->parseNumber($value[$key]);
                }
            }

            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace("\u{00A0}", ' ', $value));
        if (substr($normalized, -1) === '%') {
            $normalized = substr($normalized, 0, -1);
        }
        $normalized = trim($normalized);
        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }

        $normalized = str_replace(' ', '', $normalized);

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * @param array<string> $headers
     */
    private function performRequest(
        string $endpoint,
        string $method,
        int $timeout,
        array $headers,
        ?string $body
    ): string {
        if (function_exists('curl_init')) {
            $curl = curl_init($endpoint);
            if ($curl === false) {
                throw new RuntimeException('Não foi possível inicializar a requisição HTTP.');
            }

            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headers,
            ];

            if ($method === 'POST') {
                $options[CURLOPT_POSTFIELDS] = $body ?? '';
            }

            if (!curl_setopt_array($curl, $options)) {
                curl_close($curl);
                throw new RuntimeException('Falha ao preparar a requisição HTTP.');
            }

            $response = curl_exec($curl);
            if ($response === false) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new RuntimeException('Erro ao consultar a API de contribuições: ' . $error);
            }

            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);

            if ($statusCode >= 400) {
                throw new RuntimeException('A API retornou o status HTTP ' . $statusCode . '.');
            }

            return (string) $response;
        }

        $contextOptions = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'header' => implode("\r\n", $headers),
            ],
        ];

        if ($body !== null && strtoupper($method) !== 'GET') {
            $contextOptions['http']['content'] = $body;
        }

        $context = stream_context_create($contextOptions);
        $response = @file_get_contents($endpoint, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $message = $error['message'] ?? 'Erro desconhecido ao consultar a API de contribuições.';
            throw new RuntimeException($message);
        }

        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $headerLine) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $headerLine, $matches) === 1) {
                    $statusCode = (int) $matches[1];
                    if ($statusCode >= 400) {
                        throw new RuntimeException('A API retornou o status HTTP ' . $statusCode . '.');
                    }
                    break;
                }
            }
        }

        return (string) $response;
    }

    /**
     * @return string[]
     */
    private function buildHeaders(bool $hasBody): array
    {
        $headers = [];
        $configured = $this->config['headers'] ?? [];
        if (is_array($configured)) {
            foreach ($configured as $header) {
                if (is_string($header) && trim($header) !== '') {
                    $headers[] = trim($header);
                }
            }
        }

        $token = trim((string) ($this->config['token'] ?? ''));
        if ($token !== '') {
            $headerName = trim((string) ($this->config['token_header'] ?? 'Authorization'));
            if ($headerName === '') {
                $headerName = 'Authorization';
            }
            $prefix = (string) ($this->config['token_prefix'] ?? 'Bearer ');
            $headers[] = $headerName . ': ' . $prefix . $token;
        }

        if ($hasBody) {
            $hasContentType = false;
            foreach ($headers as $line) {
                if (stripos($line, 'content-type:') === 0) {
                    $hasContentType = true;
                    break;
                }
            }
            if (!$hasContentType) {
                $headers[] = 'Content-Type: application/json';
            }
        }

        return $headers;
    }

    private function prepareRequestBody(string $method): ?string
    {
        if ($method !== 'POST') {
            return null;
        }

        $body = $this->config['body'] ?? null;

        if (is_string($body)) {
            $trimmed = trim($body);
            if ($trimmed === '') {
                return null;
            }
            return $trimmed;
        }

        if (is_array($body)) {
            $encoded = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
            if ($encoded === false) {
                throw new RuntimeException('Não foi possível preparar o corpo da requisição para a API de contribuições.');
            }
            return $encoded;
        }

        if ($body === null) {
            return null;
        }

        throw new RuntimeException('Formato de corpo inválido para a requisição da API de contribuições.');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function unwrapPayload(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        if (isset($payload['attributes']) && is_array($payload['attributes'])) {
            return $payload['attributes'];
        }

        return $payload;
    }
}
