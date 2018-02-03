<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Class Block
 * @package App\Entity
 */
class Block
{

    public $index;
    public $previousHash;
    public $data;
    public $timestamp;
    public $hash;


    /**
     * Block constructor.
     *
     * @param int $index
     *
     * @param string $previousHash
     *
     * @param int $timestamp
     *
     * @param string $data
     */
    public function __construct(int $index, string $previousHash, string $data, int $timestamp = 0, string $hash = '')
    {
        $this->index = $index;
        $this->previousHash = $previousHash;
        $this->data = $data;
        $this->timestamp = $timestamp === 0 ? \time() : $timestamp;

        $this->hash();
    }

    /**
     * Hash the block.
     */
    private function hash(): void
    {
        $this->hash = hash('sha256', "{$this->index}{$this->previousHash}{$this->timestamp}{$this->data}");
    }
}
