<?php

declare(strict_types=1);

namespace App\Controller;

use App\P2P;
use App\Entity\Peer;
use App\EventSubscriber\PeerEventSubscriber;
use Symfony\Component\HttpFoundation\{
    Request,
    Response,
    JsonResponse
};
use Symfony\Component\Cache\Simple\RedisCache;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class PeerController
 * @package App\Controller
 */
class PeerController
{
    private $p2p = null;

    /**
     * PeerController constructor.
     */
    public function __construct()
    {
        $this->p2p = new P2P();
    }
    /**
     * GET /peer
     *
     * @return Response
     */
    public function index(): Response
    {
        $response = new JsonResponse();

        return $response->setData([
            'peers' => $this->p2p->getPeers()
        ]);
    }

    /**
     * POST /peer
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

        if (isset($json['peers'])) {
            foreach ($json['peers'] as $host) {
                $peer = new Peer($host);

                if (!$this->p2p->addPeer($peer, $dispatch)) {
                    return $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                }
            }
        }

        return $response->setStatusCode(Response::HTTP_OK);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function sync(Request $request): Response
    {
        var_dump("don't dispatch");
        return $this->add($request, false);
    }

    /**
     * DELETE /peer
     *
     * @return Response
     */
    public function remove(Request $request): Response
    {
        $response = new Response();

        $this->p2p->reset();

        return $response->setStatusCode(Response::HTTP_OK);
    }
}
