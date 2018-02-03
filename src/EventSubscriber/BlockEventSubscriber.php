<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\P2P;
use App\Event\BlockAddEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BlockEventSubscriber
 * @package App\Event
 */
class BlockEventSubscriber implements EventSubscriberInterface {

    private $p2p = null;

    /**
     * BlockEventSubscriber constructor.
     */
    public function __construct()
    {
        $this->p2p = new P2P();
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
            BlockAddEvent::NAME => [
                ['syncBlock', 10],
            ]
        ];
    }

    /**
     * @param BlockAddEvent $event
     */
    public function syncBlock(BlockAddEvent $event)
    {
        foreach ($this->p2p->getPeers() as $peer) {
            $peer->syncBlock($event->block);
        }
    }


}
