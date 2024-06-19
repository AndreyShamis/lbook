<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Service\NodeService;

class NodeController extends AbstractController
{
    private $nodeService;

    public function __construct(NodeService $nodeService)
    {
        $this->nodeService = $nodeService;
    }

    /**
     * @Route("/node/create", name="create_node", methods={"POST"})
     */
    public function createNode(): Response
    {
        $jsonData = file_get_contents('path_to_your_json_file.json');
        $data = json_decode($jsonData, true);

        foreach ($data as $nodeData) {
            $this->nodeService->createNodeFromJson($nodeData);
        }

        return new Response('Nodes created successfully');
    }
}

