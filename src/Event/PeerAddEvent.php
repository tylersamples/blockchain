<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PeerAddEvent
 *
 * @package App\Event
 */
class PeerAddEvent extends Event {

    const NAME = 'peer.add';

    public $peer;

    public function __construct($peer)
    {
        $this->peer = $peer;
    }
}
