<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Blockchain;
use App\Event\PeerAddEvent;

use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PeerEventSubscriber
 *
 * @package App\Event
 */
class PeerEventSubscriber implements EventSubscriberInterface {

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
        $peer = $event->peer;
        try {
            $client = new \GuzzleHttp\Client();

            // Reset all the blocks the peer has. Lets not presume the state the peer's blockchain is in.
            $responseDelete = $client->delete("http://$peer->endpoint/block");
            if ($responseDelete->getStatusCode() === 200) {

                // Send the peer to the sync endpoint so it doesn't dispatch and create an loop.
                $client->post("http://$peer->endpoint/peer/sync", [
                    'json' => [
                        'peers' => [
                            str_replace(':8081', '', \getenv('HTTP_HOST'))
                        ],
                    ]
                ]);

                $cache = RedisCache::createConnection(
                    \getenv('REDIS_HOST')
                );

                $blocksFromCache = $cache->get(Blockchain::CACHE_KEY) ?? "";

                $blocks = unserialize($blocksFromCache);
                foreach ($blocks as $block) {
                    $peer->syncBlock($block);
                }
            }
        }
        catch(\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}
