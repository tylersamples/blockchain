<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Class Peer
 *
 * @package App\Entity
 */
class Peer
{
    /** @var string */
    public $endpoint;

    /** @var boolean */
    public $connected = false;


    /**
     * Peer constructor.
     *
     * @param string $host
     *
     * @param int port
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

            // Send the block to the sync so it doesn't dispatch and create a loop.
            $client->post("http://$this->endpoint/block/sync", ['json' => $block]);
        }
        catch(\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}
