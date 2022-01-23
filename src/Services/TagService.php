<?php

namespace App\Services;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TagService
{
    const THRESHOLD_WEIGHT = 40;
    const THRESHOLD_WEIGHT_ISSUE = 60;
    const THRESHOLD_GEO_SCORE_ISSUE = 0.6;

    const TAG_WEIGHT = "heavy";
    const TAG_FOREIGN = "foreignWarehouse";
    const TAG_ISSUE = "hasIssues";

    const SHIPPING_FRANCE = "France";

    public Tag $tag_weight;
    public Tag $tag_foreign;
    public Tag $tag_issue;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry, HttpClientInterface $client)
    {
        $this->managerRegistry = $managerRegistry;
        $this->client = $client;

        $this->tag_weight = $this->initTag(TagService::TAG_WEIGHT);
        $this->tag_foreign = $this->initTag(TagService::TAG_FOREIGN);
        $this->tag_issue = $this->initTag(TagService::TAG_ISSUE);
    }

    /**
     * Retourne l'ensemble des tags généré automatiquement pour une commande
     *
     * @param Order|null $order
     * @return Order[]
     */
    public function generatedTag(?Order $order): array
    {
        $tags = [];

        if ($order === null) {
            return $tags;
        }

        if ($tag = TagService::weightTag($order)) {
            $tags[] = $tag;
        }

        if ($tag = TagService::foreignTag($order)) {
            $tags[] = $tag;
        }

        if ($tag = TagService::issueTag($order)) {
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Retourne le tag issue en fonction du poids/ livraison / score d'adresse
     * @param Order $order
     * @return Tag|null
     */
    public function issueTag(Order $order): ?Tag
    {
        if ($this->getWeight($order) >= TagService::THRESHOLD_WEIGHT_ISSUE) {
            return $this->tag_issue;
        }

        if ($order->getContactEmail() === '') {
            return $this->tag_issue;
        }

        if (TagService::scoreAdress($order->getShippingAddress()) >= TagService::THRESHOLD_GEO_SCORE_ISSUE) {
            return $this->tag_issue;
        }

        return null;
    }

    /**
     * Retour le score pour une adresse
     * @param string $adress
     * @return int
     */
    public function scoreAdress(string $adress): int
    {
        $url = $_ENV['API_GEO'] . $adress;

        $response = $this->client->request(
            'GET',
            $url
        );

        $content = json_decode($response->getContent());

        /**  @TODO  revoir accès du stdclass */
        $score = $content->features[0] !== null
        && $content->features[0]->properties !== null
            ? $content->features[0]->properties->score
            : 0;

        return $score;
    }

    /**
     * Check si la commande doit avoir le tag weight
     * @param Order $order
     * @return Tag|null
     */
    public function weightTag(Order $order): ?Tag
    {
        if ($this->getWeight($order) >= TagService::THRESHOLD_WEIGHT) {
            return $this->tag_weight;
        }

        return null;
    }

    /**
     * Calcul le poids d'une commande
     * @param Order $order
     * @return int
     */
    public function getWeight(Order $order): int
    {
        $weight = 0;

        $repositoryLine = $this->managerRegistry->getRepository(OrderLine::class);
        $lines = $repositoryLine->findBy(['order' => $order]);

        /** @var OrderLine $line */
        foreach ($lines as $line) {
            $weight += $line->getProduct()->getWeight();
        }

        return $weight;
    }

    /**
     * @param Order $order
     * @return Tag|null
     */
    public function foreignTag(Order $order): ?Tag
    {
        return $order->getShippingCountry() === TagService::SHIPPING_FRANCE ? $this->tag_foreign : null;
    }

    /**
     * Initialise un tag en le créant en base de données s'il n"existe pas
     * @param string $tag
     * @return Tag|null
     */
    public function initTag(string $tag): ?Tag
    {
        $repository = $this->managerRegistry->getRepository(Tag::class);
        $tagExist = $repository->findOneBy([
            'name' => $tag
        ]);

        if ($tagExist === null) {
            $tag = (new Tag())->setName($tag);

            $this->managerRegistry->getManager()->persist($tag);
            $this->managerRegistry->getManager()->flush();
        }

        return $tagExist ?? $tag;
    }
}
