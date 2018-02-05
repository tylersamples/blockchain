<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class BlockAddEvent
 *
 * @package App\Event
 */
class BlockAddEvent extends Event {

    const NAME = 'block.mine';

    /** @var App\Entity\Block */
    public $block;

    public function __construct($block)
    {
        $this->block = $block;
    }
}
