<?php

namespace App\Tests;

use App\Manager\TransactionManager;
use App\Service\ExchangeRateConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommissionCalculatorTest extends TestCase
{
    /**
     * @test
     */
    public function commission_calculation(ExchangeRateConverter $converter)
    {
        if (($file = fopen('transactions.csv', "r")) !== false) {
            $transactionManager = new TransactionManager();
            $result = $transactionManager->calculateCommission($file, $converter);
            if (empty($result)) {
                $this->assertFalse(true, 'Commission calculation failed');
            }
            $this->assertTrue(true);
        } else {
            $this->assertFileIsNotReadable('transactions.csv', 'This file is not readable.');
        }
    }
}
