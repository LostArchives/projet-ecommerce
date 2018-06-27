<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CartController extends Controller
{
    /**
     * @Route("/add", name="add_to_cart")
     * @Method("POST")
     *
     * @param Product $product
     * @throws \Exception
     */
    public function addToCart(Request $request)
    {
        $productId = $request->get('product_id');
        $productQuantity = $request->get('product_quantity');

        if($productQuantity == null)
            $productQuantity = 1;

        // get the product if exists in return
        $product = $this->get('app.cart')->addProductToCart($productId, $productQuantity);

        $this->addFlash('info', 'Le produit ' . $product->getName() . ' a bien été ajouté '. $productQuantity . ' fois. <a href="' . $this->generateUrl('cart') . '">Voir le panier.</a>');

        return $this->redirectToRoute('product_details', ['id' => $product->getId()]);
    }

    /**
     * @Route("/cart", name="cart")
     */
    public function cartAction()
    {
        $cart = $this->get('session')->get('cart') ?? [];

        $display = $this->get('app.cart')->getProductsForDisplay($cart);

        return $this->render('cart/details.html.twig', [
            'products' => $display['products'],
            'most_viewed_products' => $display['most_viewed_products'],
            'totalAmount' => $display['totalAmount'],
        ]);
    }

    /**
     * @Route("/remove", name="remove_from_cart")
     * @Method("POST")
     */
    public function removeFromCart(Request $request)
    {
        $productRepository = $this->get('doctrine')->getRepository(Product::class);
        $product = $productRepository->find($request->get('product_id'));

        $this->get('app.cart')->removeProduct($product);

        return $this->redirectToRoute('cart');
    }

    /**
     * @Route("/cart/clean", name="clean_cart")
     * @Method("POST")
     */
    public function clearCart()
    {
        // Empty the cart session objects
        $this->get('app.cart')->emptyCart();

        // Tell to user the cart state
        $this->addFlash('info', 'Le panier a bien été vidé');

        return $this->redirectToRoute('cart');
    }
}