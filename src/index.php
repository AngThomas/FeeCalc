<?php

use App\Model\LoanProposal;
use App\Service\FeeCalculator;
use App\Service\FeeStructureLoader;

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

try {
    $request = ['term' => htmlspecialchars($_GET['term']), 'amount' => htmlspecialchars($_GET['amount'])];
    $feeStructureLoader = new FeeStructureLoader(__DIR__ . '/Resource/FeeConfig/feeStructure.json');
    $feeCalculator = new FeeCalculator($feeStructureLoader);
    $fee = $feeCalculator->calculate(new LoanProposal($request['term'], $request['amount']));
    echo json_encode(['fee' => $fee]);
} catch (Exception $exception) {
    echo json_encode(['error' => $exception->getMessage()]);
}