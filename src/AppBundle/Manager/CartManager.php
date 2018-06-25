<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartManager
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(RegistryInterface $doctrine, SessionInterface $session)
    {
        $this->doctrine = $doctrine;
        $this->session = $session;
    }

    public function getProductsForDisplay(array $cart)
    {
        $productRepository = $this->doctrine->getRepository(Product::class);

        $products = [];
        $totalAmount = 0;

        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);

            $products[$id]['product'] = $product;
            $products[$id]['qty'] = $quantity;

            $totalAmount += $product->getPrice() * $quantity;
        }

        return [
            'products' => $products,
            'totalAmount' => $totalAmount,
        ];
    }

    public function removeProduct(Product $product)
    {
        $cart = $this->session->get('cart');

        // remettre le stock en base
        $product->provisionStock($cart[$product->getId()]);

        $em = $this->doctrine->getManager();
        $em->persist($product);
        $em->flush();

        // supprimer le produit du panier en session
        unset($cart[$product->getId()]);
        $this->session->set('cart', $cart);
        $this->session->save();
    }
}