<?php

declare(strict_types=1);

namespace App;

use App\Entity\Peer;
//use App\Event\PeerAddEvent;
use App\Event\PeerAddEvent;
use App\EventSubscriber\PeerEventSubscriber;

use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\EventDispatcher\EventDispatcher;



/**
 * Class P2P
 * @package App
 */
class P2P {

    const CACHE_KEY = 'peers';

    private $cache = null;

    private $peers = false;

    private $dispatcher = null;

    /**
     * P2P constructor.
     */
    public function __construct()
    {
        $this->cache = RedisCache::createConnection(
            \getenv('REDIS_HOST')
        );

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new PeerEventSubscriber());

        // Load our blocks from Redis.
        $peersFromCache = $this->cache->get(P2P::CACHE_KEY) ?? "";

        $this->peers = unserialize($peersFromCache);
    }

    /**
     * @return array
     */
    public function getPeers(): array {
        return $this->peers ? $this->peers : [];
    }

    /**
     * Add a peer to cache.
     *
     * @param Peer $peer
     *
     * @param $dispatch
     *
     * @return bool
     */
    public function addPeer(Peer $peer, $dispatch): bool {

        $peer->testConnection();
        $peers = $this->peers ? $this->peers : [];
        $peerEndpoints = array_map(function($storedPeer) {return $storedPeer->endpoint; }, $peers);

        if (in_array($peer->endpoint, $peerEndpoints)) {
            return true;
        }

        // Only add the peer if it is accessible.
        if ($peer->testConnection()) {
            $this->peers[] = $peer;
            // Dispatch so we can sync to them.
            if ($dispatch) {
                $this->dispatcher->dispatch(PeerAddEvent::NAME, new PeerAddEvent($peer));
            }
            $this->writePeers();
        }

        return true;
    }

    /**
     * Write peers to cache.
     */
    private function writePeers(): void
    {
        $this->cache->set(P2P::CACHE_KEY, serialize($this->peers));
    }

    /**
     *
     */
    public function reset(): void {
        $this->cache->set(P2P::CACHE_KEY, false);
    }
}