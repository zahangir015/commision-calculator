<?php

namespace App\Service;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateConverter
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Get exchange rate for EURO by api call
     * @return array
     */
    private function getRates(): array
    {
        try {
            $response = $this->client->request(
                'GET',
                //getenv('PAYSERA_CURRENCY_EXCHANGE_RATE_API')
                "https://developers.paysera.com/tasks/api/currency-exchange-rates"
            );
            $statusCode = $response->getStatusCode();
            if ($statusCode == 200) {
                return ['code' => $statusCode, 'data' => $response->toArray(), 'message' => 'Exchange rate successfully found.'];
            } else {
                return ['code' => $statusCode, 'message' => $response->getInfo()];
            }
        } catch (Exception $e) {
            return ['code' => $e->getCode(), 'message' => $e->getMessage()];
        }
    }

    /**
     * Convert other currencies in Euro
     * @return array
     */
    public function convertToEuro(string $currency, float $amount): array
    {
        $response = $this->getRates();
        if ($response['code'] == 200) {
            $transactionAmount = ($amount/$response['data']['rates'][$currency]);
            return ['code' => 200, 'amount' => $transactionAmount];
        }

        return $response;
    }
}