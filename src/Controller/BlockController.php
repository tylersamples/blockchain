<?php

/**
 *
 */

declare(strict_types=1);

namespace App\Controller;

use App\Blockchain;
use App\Entity\Block;

use Symfony\Component\HttpFoundation\{
    Request,
    Response,
    JsonResponse
};

/**
 * Class BlockController
 *
 * @package App\Controller
 */
class BlockController
{
    /** @var null|Blockchain */
    private $blockchain = null;

    /**
     * BlockController constructor.
     */
    public function __construct()
    {
        $this->blockchain = new Blockchain();
    }

    /**
     * GET /block
     *
     * @return Response
     */
    public function index(): Response
    {
        $response = new JsonResponse();

        return $response->setData([
            'blocks' => $this->blockchain->getBlocks()
        ]);
    }

    /**
     * GET /block/$hash
     *
     * @param $hash
     *
     * @return Response
     */
    public function detail(string $hash = ""): Response
    {
        $response = new JsonResponse();

        $block = $this->blockchain->getBlockByHash($hash);

        if ($block) {
            return $response->setData([
                'block' => $block
            ]);
        }

        return $response->setStatusCode(Response::HTTP_NOT_FOUND);
    }


    /**
     * POST /block
     *
     * @param Request $request
     *
     * @param bool $dispatch
     *
     * @return Response
     */
    public function add(Request $request, $dispatch = true): Response
    {
        $response = new Response();

        $json = \json_decode(
            $request->getContent(), true
        );

        if (empty($json)) {
            return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        $block = new Block(...array_values($json));

        if (!$this->blockchain->addBlock($block, $dispatch)) {
            return $response->setStatusCode(Response::HTTP_CONFLICT);
        }

        return $response->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * POST /block/sync
     *
     * @param Request $request
     *
     * @return Response
     */
    public function sync(Request $request): Response
    {
        return $this->add($request, false);
    }

    /**
     * DELETE /block
     *
     * @return Response
     */
    public function remove(): Response
    {
        $response = new Response();

        $this->blockchain->reset();

        return $response->setStatusCode(Response::HTTP_OK);
    }
}
