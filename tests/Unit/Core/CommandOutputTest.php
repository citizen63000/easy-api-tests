<?php

namespace EasyApiTests\Tests\Unit\Core;

use EasyApiTests\Core\CommandOutput;
use PHPUnit\Framework\TestCase;

class CommandOutputTest extends TestCase
{
    public function testGetAndSetStatusCode(): void
    {
        $commandOutput = new CommandOutput();
        $commandOutput->setStatusCode(200);

        $this->assertSame(200, $commandOutput->getStatusCode());
    }

    public function testGetAndSetData(): void
    {
        $commandOutput = new CommandOutput();
        $data = 'test output data';
        $commandOutput->setData($data);

        $this->assertSame($data, $commandOutput->getData());
    }

    public function testGetArrayDataBasic(): void
    {
        $commandOutput = new CommandOutput();
        $data = "line1\nline2\nline3";
        $commandOutput->setData($data);

        $arrayData = $commandOutput->getArrayData();

        $this->assertSame(['line1', 'line2', 'line3'], $arrayData);
    }

    public function testGetArrayDataWithEmptyLines(): void
    {
        $commandOutput = new CommandOutput();
        $data = "line1\n\nline2\n   \nline3\n";
        $commandOutput->setData($data);

        $arrayData = $commandOutput->getArrayData();

        $this->assertSame(['line1', '', 'line2', '   ', 'line3', ''], $arrayData);
    }

    public function testGetArrayDataCleanEmptyLines(): void
    {
        $commandOutput = new CommandOutput();
        $data = "line1\n\nline2\n   \nline3\n";
        $commandOutput->setData($data);

        $arrayData = $commandOutput->getArrayData(true);

        // Should remove empty lines and lines with only whitespace
        $expectedKeys = [0, 2, 4]; // line1, line2, line3 positions
        $this->assertSame(array_keys($arrayData), $expectedKeys);
        $this->assertSame('line1', $arrayData[0]);
        $this->assertSame('line2', $arrayData[2]);
        $this->assertSame('line3', $arrayData[4]);
    }

    public function testGetArrayDataSingleLine(): void
    {
        $commandOutput = new CommandOutput();
        $data = 'single line';
        $commandOutput->setData($data);

        $arrayData = $commandOutput->getArrayData();

        $this->assertSame(['single line'], $arrayData);
    }

    public function testGetArrayDataEmptyString(): void
    {
        $commandOutput = new CommandOutput();
        $commandOutput->setData('');

        $arrayData = $commandOutput->getArrayData();

        $this->assertSame([''], $arrayData);
    }

    public function testGetArrayDataOnlyNewlines(): void
    {
        $commandOutput = new CommandOutput();
        $commandOutput->setData("\n\n\n");

        $arrayData = $commandOutput->getArrayData();
        $this->assertSame(['', '', '', ''], $arrayData);

        $arrayDataCleaned = $commandOutput->getArrayData(true);
        $this->assertEmpty($arrayDataCleaned);
    }

    public function testChainedMethodCalls(): void
    {
        $commandOutput = new CommandOutput();
        $commandOutput->setStatusCode(404);
        $commandOutput->setData("Error\nNot found");

        $this->assertSame(404, $commandOutput->getStatusCode());
        $this->assertSame(['Error', 'Not found'], $commandOutput->getArrayData());
    }
}
