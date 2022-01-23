<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Tag;
use App\Services\TagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;

/**
 * OrderController
 * @Route("/api",name="api_")
 */
class OrderController extends AbstractController
{
    /**
     * @Route("/orders",name="orders")
     * @return Response
     */
    public function getOrders()
    {
        return new Response('ok');
    }

    /**
     * @Route("/order/{id}",name="order")
     * @return Response
     */
    public function getOrder(int $id, ManagerRegistry $doctrine)
    {
        $repository = $doctrine->getRepository(Order::class);

        try {
            /** @var Order $order */
            $order = $repository->find($id);
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('The order does not exist');
        }
        return new Response(json_encode([
            'id' => $order->getId()
        ]));
    }

    /**
     * @Route("/addTag/{id}/{tag}",name="addTag")
     * @return Response
     */
    public function addTagToOrder(int $id, string $tag, ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();

        $repositoryOrder = $doctrine->getRepository(Order::class);
        $repositoryTag = $doctrine->getRepository(Tag::class);

        try {
            /** @var Order $order */
            $order = $repositoryOrder->find($id);

            /** @var Tag $hasTag */
            $hasTag = $repositoryTag->findOneBy([
                'name' => $tag
            ]);

            if ($hasTag === null) {
                $tag = (new Tag())->setName($tag);

                $entityManager->persist($tag);
                $entityManager->flush();
            }

            // on récupère l'existant ou le nouveau crée
            $tag = $hasTag ?? $tag;

            $order->addTag($tag);

            $entityManager->persist($order);
            $entityManager->flush();
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('The order does not exist');
        }

        /** @TODO Gérer une réponse sérializé en json */
        return new Response('ok');
    }

    /**
     * @Route("/addAutomaticTag/{id}",name="addAutomaticTag")
     * @return Response
     */
    public function addAutomaticTag(int $id, ManagerRegistry $doctrine, TagService $tagService)
    {
        $entityManager = $doctrine->getManager();

        $repositoryOrder = $doctrine->getRepository(Order::class);
        $repositoryTag = $doctrine->getRepository(Tag::class);

        try {
            /** @var Order $order */
            $order = $repositoryOrder->find($id);
            $tags = $tagService->generatedTag($order);

        } catch (\Exception $exception) {
            throw $this->createNotFoundException('The order does not exist');
        }

        return new Response('ok');
    }
}
