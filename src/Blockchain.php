<?php

declare(strict_types=1);

namespace App;

use App\Entity\Block;
use App\Event\BlockAddEvent;
use App\EventSubscriber\BlockEventSubscriber;

use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Blockchain
 *
 * @package App
 */
class Blockchain
{
    const CACHE_KEY = 'blocks';

    /** @var bool|array */
    private $blocks = false;

    /** @var null|RedisCache */
    private $cache = null;

    /** @var null|EventDispatcher */
    private $dispatcher = null;

    /**
     * Blockchain constructor.
     */
    public function __construct()
    {
        $this->cache = RedisCache::createConnection(
            \getenv('REDIS_HOST')
        );

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new BlockEventSubscriber());

        // Load our blocks from Redis.
        $blocksFromCache = $this->cache->get(Blockchain::CACHE_KEY) ?? "";

        $this->blocks = unserialize($blocksFromCache);
    }

    /**
     * Check if we have a Block by a hash.
     *
     * @param string $hash
     *
     * @return Block
     */
    public function getBlockByHash(string $hash): Block
    {
        // Check if we have any blocks or if we actually received a hash.
        if ($this->blocks && !empty($hash)) {
            foreach ($this->blocks as $block) {
                if ($block->hash === $hash) {
                    return $block;
                }
            }
        }
        return null;
    }


    /**
     * Add a block to cache.
     *
     * @param Block $block
     *
     * @param $dispatch
     *
     * @return bool
     */
    public function addBlock(Block $block, $dispatch): bool
    {
        // Check if were adding our Genesis block.
        if (!$this->blocks && $block->index === 0) {
            $this->blocks = [$block];
            // Dispatch so our peers hear about the new Block.
            if ($dispatch) {
                $this->dispatcher->dispatch(BlockAddEvent::NAME, new BlockAddEvent($block));
            }

            $this->writeBlockchain();

            return true;
        }
        elseif ($block->index > 0) {
            // Get the last element in the array of blocks.
            $lastBlock = array_slice($this->blocks, -1);

            $blockHashes = array_map(function($storedBlocks) {return $storedBlocks->hash; }, $this->blocks);

            // Check if our new block has an index higher than the last block and has a previous hash.
            if ($block->index > $lastBlock->index && !empty($block->previousHash) && in_array($block->previousHash, $blockHashes)) {
                $this->blocks[] = $block;
                // Dispatch so our peers hear about the new Block.
                if ($dispatch) {
                    $this->dispatcher->dispatch(BlockAddEvent::NAME, new BlockAddEvent($block));
                }

                $this->writeBlockchain();

                return true;
            }
        }

        return false;
    }

    /**
     * Getter for Blocks.
     *
     * @return array
     */
    public function getBlocks(): array
    {
        return $this->blocks ? $this->blocks : [];
    }


    /**
     * Write our Blocks to cache.
     */
    private function writeBlockchain(): void
    {
        $this->cache->set(Blockchain::CACHE_KEY, serialize($this->blocks));
    }

    /**
     * Destroy our Blocks cache.
     */
    public function reset(): void {
        $this->cache->set(Blockchain::CACHE_KEY, false);
    }
}
