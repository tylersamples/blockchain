<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\{
    P2P,
    Blockchain
};
use App\Event\PeerAddEvent;

use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PeerEventSubscriber
 *
 * @package App\Event
 */
class PeerEventSubscriber implements EventSubscriberInterface
{

    /** @var null|App\P2P */
    private $p2p = null;

    /**
     * PeerEventSubscriber constructor.
     *
     * @param P2P $p2p
     */
    public function __construct(P2P $p2p)
    {
        $this->p2p = $p2p;
    }

    /**
     * Define the events we are interested in.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        // return the subscribed events, their methods and priorities
        return [
            PeerAddEvent::NAME => [
                ['addPeer', 10],
            ],
        ];
    }

    /**
     * Add a Peer.
     *
     * @param PeerAddEvent $event
     */
    public function addPeer(PeerAddEvent $event)
    {
        try {
            $client = new \GuzzleHttp\Client();

            $newPeer = $event->peer;

            // Notify all our current peers about this new peer.
            foreach ($this->p2p->getPeers() as $currentPeer) {
                $request = $client->post("http://$currentPeer->endpoint/peer/sync", [
                    'json' => [
                        'peers' => [str_replace(':8081', '', $newPeer->endpoint)]
                    ]
                ]);
            }

            // Reset all the blocks the new peer has. Lets not presume the state the new peer's blockchain is in.
            $responseDelete = $client->delete("http://$newPeer->endpoint/block");
            if ($responseDelete->getStatusCode() === 200) {

                $peerEndpoints = array_map(function($storedPeer) {
                    return str_replace(':8081', '', $storedPeer->endpoint);
                    }, $this->p2p->getPeers());

                $peerEndpoints[] = str_replace(':8081', '', \getenv('HTTP_HOST'));

                // Send the new peer our peer info and send it to the sync endpoint so it doesn't dispatch and create an loop.
                $request = $client->post("http://$newPeer->endpoint/peer/sync", [
                    'json' => [
                        'peers' => $peerEndpoints
                    ]
                ]);

                $cache = RedisCache::createConnection(
                    \getenv('REDIS_HOST')
                );

                $blocksFromCache = $cache->get(Blockchain::CACHE_KEY) ?? "";

                $blocks = unserialize($blocksFromCache);
                foreach ($blocks as $block) {
                    $newPeer->syncBlock($block);
                }
            }
        }
        catch(\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}
