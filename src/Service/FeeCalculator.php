<?php

namespace App\Service;

use App\Interface\FeeCalculatorInterface;
use App\Model\Bounds;
use App\Model\LoanProposal;

class FeeCalculator implements FeeCalculatorInterface
{
    private array $feeStructure;
    public function __construct(FeeStructureLoader $loader)
    {
        $this->feeStructure = $loader->load();
    }
    public function calculate(LoanProposal $application): float
    {
        $amount = $application->amount();
        $term = $application->term();
        $fee = $this->interpolateFee($amount, $term);
        return $this->roundUpFee($fee, $amount);
    }

    private function interpolateFee(float $amount, int $term): float
    {
        $fees = $this->feeStructure[$term];

        if (isset($fees[$amount])) {
            return $fees[$amount];
        }

        $bounds = $this->getBounds($amount, $fees);
        $lowerFee = $fees[$bounds->lowerBound()];
        $upperFee = $fees[$bounds->upperBound()];

        return $this->interpolate($amount, $bounds, $lowerFee, $upperFee);
    }
    private function getBounds(float $amount, array $fees): Bounds
    {
        $amounts = array_keys($fees);
        sort($amounts);

        $lowerBound = null;
        $upperBound = null;

        foreach ($amounts as $amt) {
            if ($amt <= $amount) {
                $lowerBound = $amt;
            } elseif ($upperBound === null) {
                $upperBound = $amt;
                break;
            }
        }

        if ($lowerBound === null || $upperBound === null) {
            throw new \InvalidArgumentException('The amount is out of bounds for the available fee structure.');
        }

        return new Bounds($lowerBound, $upperBound);
    }


    private function roundUpFee(float $fee, float $amount): float
    {
        $total = $fee + $amount;
        return ceil($total / 5) * 5 - $amount;
    }

    private function interpolate(
        float $amount,
        Bounds $bounds,
        float $lowerFee,
        float $upperFee
    ): float
    {
        return $lowerFee + (($amount - $bounds->lowerBound()) * ($upperFee - $lowerFee)) / ($bounds->upperBound() - $bounds->lowerBound());
    }

}
