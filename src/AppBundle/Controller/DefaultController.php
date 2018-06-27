<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Contact;
use AppBundle\Entity\Product;
use AppBundle\Form\CommentType;
use AppBundle\Form\ContactType;
use AppBundle\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    const NUMBER_ITEMS_HOMEPAGE = 5;
    const MAX_VIEWED_PRODUCTS = 3;

    /**
     * @Route("/", name="homepage")
     * @return Response
     */
    public function homepageAction()
    {
        $productRepository = $this->get('doctrine')->getRepository(Product::class);

        $products = $productRepository->getLastActiveProducts(self::NUMBER_ITEMS_HOMEPAGE);

        return $this->render('default/index.html.twig', [
            'products' => $products,
        ]);
    }

    public function showCategoriesMenuAction()
    {
        $categoryRepository = $this->get('doctrine')->getRepository(Category::class);
        $categories = $categoryRepository->findAll();

        return $this->render('default/menu.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/product/{id}", requirements={"page"="\d+"}, name="product_details")
     */
    public function showProductAction(Request $request, Product $product)
    {
        $em = $this->get('doctrine')->getManager();

        // Incrémentation du compteur
        $product->addHit();
        $em->persist($product);
        $em->flush();

        $comment = new Comment($product);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('default/product.html.twig', [
            'product' => $product,
            'comment_form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/category/{id}", requirements={"page"="\d+"}, name="category_details")
     */
    public function showCategoryAction(Category $category)
    {
        return $this->render('default/category.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     */
    public function contactAction(Request $request)
    {
        $contact = new Contact();

        // Formulaire
        $form = $this->createForm(ContactType::class, $contact);

        // Gestion de la requête
        $form->handleRequest($request);

        // Redirection
        if ($form->isValid() && $form->isSubmitted())
        {
            $em = $this->get('doctrine')->getManager();
            $em->persist($contact);
            $em->flush();

            $this->addFlash('info', 'confirmation_success');

            return $this->redirectToRoute('homepage');
        }

        // Affichage
        return $this->render('default/contact.html.twig', [
            'contact_form' => $form->createView(),
        ]);
    }

    public function showProductInCartAction($productId)
    {
        $quantity = $this->get('app.cart')->quantityOfProductInCart($productId);

        return $this->render('_show_product_in_cart.html.twig', [
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param $productId
     * @return Response
     */
    public function showMostViewedProductsAction($productId)
    {
        $productRepository = $this->get('doctrine')->getRepository(Product::class);

        $product = $productRepository->find($productId);

        $mostViewedProducts = $productRepository->getMostViewedProducts($product, self::MAX_VIEWED_PRODUCTS);

        $most_viewed_title = $this->get('translator')->trans('product_most_viewed_title');

        return $this->render('_show_lateral_products.html.twig', [
            'lateral_products' => $mostViewedProducts,
            'lateral_title' => $most_viewed_title
        ]);
    }

    /**
     * @param $productId
     * @return Response
     */
    public function showOtherProductsAction($productId)
    {
        $productRepository = $this->get('doctrine')->getRepository(Product::class);

        $product = $productRepository->find($productId);

        $otherProducts = $productRepository->getRandomProducts($product);

        shuffle($otherProducts);
        $otherProducts = array_slice($otherProducts, null, 2);

        // get title for other products
        $other_products_title = $this->get('translator')->trans('product_most_viewed_title');

        return $this->render('_show_lateral_products.html.twig', [
            'lateral_products' => $otherProducts,
            'lateral_title' => $other_products_title
        ]);
    }
}
