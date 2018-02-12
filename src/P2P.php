<?php

declare(strict_types=1);

namespace App;

use App\Entity\Peer;
use App\Event\PeerAddEvent;
use App\EventSubscriber\PeerEventSubscriber;

use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class P2P
 *
 * @package App
 */
class P2P {

    const CACHE_KEY = 'peers';

    /** @var bool|array */
    private $peers = false;

    /** @var null|RedisCache */
    private $cache = null;

    /** @var null|EventDispatcher */
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
        $this->dispatcher->addSubscriber(new PeerEventSubscriber($this));

        // Load our blocks from Redis.
        $peersFromCache = $this->cache->get(P2P::CACHE_KEY) ?? "";

        $this->peers = unserialize($peersFromCache);
    }

    /**
     * Get the peers that have been loaded from cache.
     *
     * @return array
     */
    public function getPeers(): array {
        return $this->peers ? $this->peers : [];
    }

    /**
     * Return all loaded peers endpoints.
     *
     * @return array
     */
    public function getEndpoints(): array {
        $peers = $this->peers ? $this->peers : [];
        return array_map(function($storedPeer) {return $storedPeer->endpoint; }, $peers);
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
        $peerEndpoints = $this->getEndpoints();

        $ourEndpoint = \getenv('HTTP_HOST');

        // Check if we already know about this peer and if the peer is ourself.
        if (in_array($peer->endpoint, $peerEndpoints) || $peer->endpoint == $ourEndpoint)  {
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

            return true;
        }

        return false;
    }

    /**
     * Write peers to cache.
     */
    private function writePeers(): void
    {
        $this->cache->set(P2P::CACHE_KEY, serialize($this->peers));
    }

    /**
     * Delete all peers in cache.
     */
    public function reset(): void {
        $this->cache->set(P2P::CACHE_KEY, false);
    }
}
