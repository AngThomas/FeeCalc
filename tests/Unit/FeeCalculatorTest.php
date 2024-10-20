<?php

namespace Unit;

use App\Model\Bounds;
use App\Model\LoanProposal;
use App\Service\FeeCalculator;
use App\Service\FeeStructureLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeeCalculatorTest extends TestCase
{
    private MockObject $feeStructureLoaderMock;
    private FeeCalculator $feeCalculator;

    protected function setUp(): void
    {
        $this->feeStructureLoaderMock = $this->createMock(FeeStructureLoader::class);
        $feeStructure = $this->loadFeeStructureFromFile();

        $this->feeStructureLoaderMock->method('load')
            ->willReturn($feeStructure);

        $this->feeCalculator = new FeeCalculator($this->feeStructureLoaderMock);
    }
    private function loadFeeStructureFromFile(): array
    {
        $jsonFilePath = __DIR__ . '/../Resource/feeStructure.json';

        if (!file_exists($jsonFilePath)) {
            throw new \RuntimeException("JSON file not found: " . $jsonFilePath);
        }

        $jsonContent = file_get_contents($jsonFilePath);
        return json_decode($jsonContent, true);
    }

    /**
     * @dataProvider exactAmountDataProvider
     */
    public function testCalculateExactAmount(int $amount, int $term, float $expectedFee)
    {
        $loanProposal = new LoanProposal($term, $amount);

        $result = $this->feeCalculator->calculate($loanProposal);
        $this->assertEquals($expectedFee, $result);
    }

    /**
     * @dataProvider interpolatedFeeDataProvider
     */
    public function testCalculateInterpolatedFee(int $amount, int $term, float $expectedFee)
    {
        $loanProposal = new LoanProposal($term, $amount);

        $result = $this->feeCalculator->calculate($loanProposal);

        $this->assertEquals($expectedFee, $result);
    }

    /**
     * @dataProvider roundUpFeeProvider
     */
    public function testRoundUpFee(float $fee, float $amount, float $expectedFee)
    {
        $result = $this->invokeMethod($this->feeCalculator, 'roundUpFee', [$fee, $amount]);
        $this->assertEquals($expectedFee, $result);
    }

    /**
     * @dataProvider outOfBoundsDataProvider
     */
    public function testCalculateOutOfBounds($amount, $term)
    {
        $loanProposal = new LoanProposal($term, $amount);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount is out of bounds for the available fee structure.');

        $this->feeCalculator->calculate($loanProposal);
    }

    /**
     * @dataProvider correctAmountsCorrectBoundsDataProvider
     */
    public function testCorrectAmountGivesCorrectBounds(float $amount, int $term,  float $lowerBound, float $upperBound)
    {
        $mockFees = $this->feeStructureLoaderMock->load()[$term];
        $result = $this->invokeMethod($this->feeCalculator, 'getBounds', [$amount, $mockFees]);
        $this->assertEquals($lowerBound, $result->lowerBound());
        $this->assertEquals($upperBound, $result->upperBound());
    }

    /**
     * @dataProvider interpolateProvider
     */
    public function testInterpolate(float $amount, Bounds $bounds, float $lowerFee, float $upperFee, float $expectedFee)
    {
        $result = $this->invokeMethod($this->feeCalculator, 'interpolate', [$amount, $bounds, $lowerFee, $upperFee]);
        $this->assertEquals($expectedFee, $result);
    }

    public static function exactAmountDataProvider(): array
    {
        return [
            [1000, 12, 50],
            [2000, 12, 90],
            [1000, 24, 70],
        ];
    }


    public static function interpolatedFeeDataProvider(): array
    {
        return [
            [1500, 12, 70],
            [2500, 24, 110.0],
            [11500, 24, 460],
            [19250, 12, 385],
        ];
    }

    public static function roundUpFeeProvider(): array
    {
        return [
            [460, 11500, 460],
            [457, 11500, 460],
            [453, 11500, 455],
            [1, 1000, 5],
            [799, 20000, 800],
            [396, 19600, 400],
        ];
    }
    public static function outOfBoundsDataProvider(): array
    {
        return [
            [50000, 12],
            [50000, 24],
        ];
    }

    public static function correctAmountsCorrectBoundsDataProvider(): array
    {
        return [
            [1000, 12, 1000, 2000],
            [1500.94, 12, 1000, 2000],
            [2137, 24, 2000, 3000],
            [18240, 24, 18000, 19000]
        ];
    }

    public static function interpolateProvider(): array
    {
        return [
            [1500, new Bounds(1000, 2000), 50, 90, 70],
            [2500, new Bounds(2000, 3000), 90, 90, 90],
            [3500, new Bounds(3000, 4000), 90, 115, 102.5],
            [17500, new Bounds(17000, 18000), 340, 360, 350],
            [12500, new Bounds(12000, 13000), 240, 260, 250],
        ];
    }

    private function invokeMethod(&$object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $parameters);
    }
}

