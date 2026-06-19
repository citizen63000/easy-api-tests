<?php

namespace EasyApiTests\Core;

class CommandOutput
{
    private int $statusCode;

    private string $data;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    /**
     * Return lines into array.
     *
     * @param bool $cleanEmptyLines delete empty lines
     */
    public function getArrayData(bool $cleanEmptyLines = false): array
    {
        $lines = explode("\n", $this->data);

        if ($cleanEmptyLines) {
            foreach ($lines as $k => $lineContent) {
                if (empty(mb_trim($lineContent))) {
                    unset($lines[$k]);
                }
            }
        }

        return $lines;
    }
}
