<?php

namespace App\Manager;

class TransactionManager
{
    const DEPOSIT_COMMISSION_RATE = 0.0003;
    const WITHDRAW_COMMISSION_RATE = 0.005;
    const PRIVATE_WITHDRAW_COMMISSION_RATE = 0.003;
    const PRIVATE_WITHDRAW_IN_A_WEEK_LIMIT = 1000;

    public static function calculateCommission($file, $converter): array
    {
        $privateTransactions = [];
        $finalResult = [];

        // Open the file
        if (($handle = fopen($file->getPathname(), "r")) !== false) {
            // Read and process the lines.
            while (($data = fgetcsv($handle)) !== false) {
                if (!empty($data)) {
                    // Variable initialization
                    $transactionDate = $data[0];
                    $userId = $data[1];
                    $userType = $data[2];
                    $transactionType = $data[3];
                    $transactionAmount = $data[4];
                    $currency = $data[5];
                    // Calculate commission for deposit
                    if ($transactionType == 'deposit') {
                        $commissionFee = self::calculate($currency, $transactionAmount, self::DEPOSIT_COMMISSION_RATE, $converter);
                        $finalResult[implode(',', $data)] = $commissionFee;
                    } elseif ($transactionType == 'withdraw') { // Calculation for withdrawal
                        if ($userType == 'business') { // Business users commission calculation
                            $commissionFee = self::calculate($currency, $transactionAmount, self::WITHDRAW_COMMISSION_RATE, $converter);
                            $finalResult[implode(',', $data)] = $commissionFee;
                        } elseif ($userType == 'private') { // Private users commission calculation
                            // User wise unique key creation
                            $key = $userId . '-' . date("oW", strtotime($transactionDate));
                            if ($currency !== 'EUR') {
                                $response = $converter->convertToEuro($currency, $transactionAmount);
                                if ($response['code'] == 200) {
                                    $transactionAmount = number_format($response['amount'], 2);
                                } else {
                                    $finalResult[implode(',', $data)] = $response['message'];
                                    break;
                                }
                            }

                            if (isset($privateTransactions[$key])) {
                                if ($privateTransactions[$key]['amount'] > self::PRIVATE_WITHDRAW_IN_A_WEEK_LIMIT) {
                                    $commissionFee = self::calculate($currency, $transactionAmount, self::PRIVATE_WITHDRAW_COMMISSION_RATE, $converter);
                                } else {
                                    if ($privateTransactions[$key]['count'] > 3) {
                                        $commissionFee = self::calculate($currency, $transactionAmount, self::PRIVATE_WITHDRAW_COMMISSION_RATE, $converter);
                                    } else {
                                        $differenceBetweenTransactionAmount = ($privateTransactions[$key]['amount'] + (double)$transactionAmount) - self::PRIVATE_WITHDRAW_IN_A_WEEK_LIMIT;
                                        if ($differenceBetweenTransactionAmount > 0) {
                                            $commissionFee = self::calculate($currency, $transactionAmount, self::PRIVATE_WITHDRAW_COMMISSION_RATE, $converter);
                                        } else {
                                            $commissionFee = 0;
                                        }
                                    }
                                }
                                $finalResult[implode(',', $data)] = $commissionFee;
                                $privateTransactions[$key]['amount'] += $transactionAmount;
                                $privateTransactions[$key]['count'] += 1;
                            } else {
                                $differenceBetweenTransactionAmount = (double)$transactionAmount - self::PRIVATE_WITHDRAW_IN_A_WEEK_LIMIT;
                                if ($differenceBetweenTransactionAmount > 0) {
                                    $commissionFee = self::calculate($currency, $differenceBetweenTransactionAmount, self::PRIVATE_WITHDRAW_COMMISSION_RATE, $converter);
                                } else {
                                    $commissionFee = 0;
                                }

                                $finalResult[implode(',', $data)] = $commissionFee;
                                $privateTransactions[$key]['amount'] = $transactionAmount;
                                $privateTransactions[$key]['count'] = 1;
                            }
                        }
                    }

                }
            }
            fclose($handle);
        }

        return $finalResult;
    }

    private static function calculate($currency, $transactionAmount, $rate, $converter): float
    {
        $commissionFee = 0;

        if ($currency == 'EUR') {
            $commissionFee = number_format(($transactionAmount * $rate), 2);
        } else {
            $response = $converter->convertToEuro($currency, $transactionAmount);
            if ($response['code'] == 200) {
                $commissionFee = number_format(($response['amount'] * $rate), 2);
            }
        }

        return $commissionFee;
    }
}