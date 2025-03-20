<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for all about cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/all', name: 'List all cards', methods: ['GET'])]
    #[OA\Put(description: 'Return all cards in the database')]
    #[OA\Response(response: 200, description: 'List all cards')]
    public function cardAll(): Response
    {
        $offset = $_GET['page'] ?? 0;
        $search = $_GET['search'] ?? '';
        $setCode = $_GET['setCode'] ?? '';
        $qb = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Card::class, 'c');
        if (strlen($search) > 2) {
            $qb->where('upper(c.name) LIKE upper(:search)')
                ->setParameter('search', "%$search%");
        }
        if (strlen($setCode) > 0) {
            $qb->andWhere('c.setCode = :setCode')
                ->setParameter('setCode', $setCode);
        }
        $cards = $qb->orderBy('c.id', 'ASC')
            ->setMaxResults(20)
            ->setFirstResult($offset * 20)
            ->getQuery()
            ->getResult();
        $this->logger->info("List all cards, page $offset");
        return $this->json($cards);
    }

    #[Route('/setCodes', name: 'ListeCodes', methods: ['GET'])]
    #[OA\Put(description: 'Return all set codes in the database')]
    #[OA\Response(response: 200, description: 'List all set codes')]
    public function getSetCode(): Response
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT c.setCode')
            ->from(Card::class, 'c');
        $setCodes = $qb->getQuery()->getResult();
        $this->logger->info('List all set codes');
        return $this->json($setCodes);
    }

    #[Route('/{uuid}', name: 'Show card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'UUID of the card', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Put(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid): Response
    {
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        if (!$card) {
            return $this->json(['error' => 'Card not found'], 404);
        }
        $this->logger->info('Show card ' . $uuid);
        return $this->json($card);
    }
}
