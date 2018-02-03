<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Class Block
 * @package App\Entity
 */
class Peer
{

    public $endpoint;
    public $connected = false;


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
    public function __construct(string $host, int $port = 8081)
    {
        $this->endpoint = "$host:$port";

        $this->testConnection();
    }

    /**
     * Test if a peer can be contacted.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->head($this->endpoint);
            $code = $response->getStatusCode();
            $this->connected = $code === 200;

            return true;
        }
        catch(\Exception $e) {
            $this->connected = false;
        }

        return false;
    }

    /**
     * Sync a block to the peer.
     *
     * @param Block $block
     */
    public function syncBlock(Block $block)
    {
        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->post("http://$this->endpoint/block/sync", ['json' => $block]);
        }
        catch(\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}
