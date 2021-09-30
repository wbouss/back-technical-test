<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

class AppFixtures extends Fixture
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i < 30; $i++) {
            $order = new Order();
            $order->setContactEmail($this->faker->randomElement(['', $this->faker->email]));
            $order->setName('#'. mt_rand(10, 100000));
            $order->setShippingAddress('8 rue de la paix');
            $order->setShippingZipcode((string) (mt_rand(10, 95) * 1000));
            $order->setShippingCountry($this->faker->randomElement(['France', $this->faker->country]));

            $nbLines = mt_rand(1, 10);
            for ($j = 1; $j < $nbLines; $j++) {
                $qty = mt_rand(1, 4);
                $price = mt_rand(100, 5000);

                $orderLine = new OrderLine();
                $orderLine->setQuantity($qty);
                $orderLine->setTotal($qty * $price);

                $product = new Product();
                $product->setName($this->faker->safeColorName . ' nuts');
                $product->setWeight(mt_rand(100, 5000));
                $manager->persist($product);

                $orderLine->setProduct($product);
                $orderLine->setOrder($order);

                $manager->persist($product);
                $manager->persist($orderLine);
            }

            $manager->persist($order);
        }

        $manager->flush();
    }
}
