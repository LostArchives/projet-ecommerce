<?php

namespace AppBundle\Manager;

use AppBundle\Entity\Product;
use Exception;
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

    /**
     * CartManager constructor.
     * @param RegistryInterface $doctrine
     * @param SessionInterface $session
     */
    public function __construct(RegistryInterface $doctrine, SessionInterface $session)
    {
        $this->doctrine = $doctrine;
        $this->session = $session;
    }

    /**
     * @param array $cart
     * @return array
     */
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

    /**
     * @param Product $product
     */
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

    /**
     * @param $productId
     * @return int|mixed
     */
    public function quantityOfProductInCart($productId)
    {
        $cart = $this->session->get('cart') ?? [];

        $quantity = 0;

        foreach ($cart as $id => $qty) {
            if ($productId === $id) {
                // Le produit est présent dans le panier
                $quantity = $qty;
                break;
            }
        }

        return $quantity;
    }

    /**
     * Add product to cart if product is available and if stock is enough
     * @param $productId
     * @param $quantity
     * @throws Exception : Product not found
     */
    public function addProductToCart($productId, $quantity)
    {
        $productRepository = $this->doctrine->getRepository(Product::class);
        $product = $productRepository->find($productId);

        // gestion du stock
        $currentStock = $product->getStock();

        if ($currentStock === 0) {
            throw new Exception('Produit indisponible');
        }

        if ($quantity >= $currentStock) {
            $product->decrementStock($currentStock);
        } else {
            $product->decrementStock($quantity);
        }

        $em = $this->doctrine->getManager();
        $em->persist($product);
        $em->flush();

        $cart = $this->session->get('cart');

        // Create new product key if does not exist
        $currentQty = $cart[$product->getId()] ?? 0;

        // Add product into cart (update of quantity)
        $cart[$product->getId()] = $currentQty + $quantity;

        // Update Cart object in session and save session
        $this->session->set('cart', $cart);
        $this->session->save();

        // return product to give user information in controller when cart updated
        return $product;
    }

    /**
     * Empty the Cart session object of user and update before all products stock related to this cart
     */
    public function emptyCart()
    {
        // Before remove, increment for each product stock used
        $productRepository = $this->doctrine->getRepository(Product::class);
        $em = $this->doctrine->getManager();

        // getting cart object in session
        $cart = $this->session->get('cart');

        // update each product stock
        foreach ($cart as $id => $qty) {
            $product = $productRepository->find($id);

            // add provision stock used in cart
            $product->provisionStock($qty);

            // update object in entity manager
            $em->persist($product);
        }

        // flush all updates
        $em->flush();

        // Initialize cart session object to an empty array and update session
        $this->session->set('cart', []);
        $this->session->save();
    }
}