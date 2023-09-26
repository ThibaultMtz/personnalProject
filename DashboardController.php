<?php

namespace App\Controller;

use App\Entity\Attribute;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Review;
use App\Entity\SubCategory;
use App\Entity\User;
use App\Form\AsinFormType;
use App\Form\AttributeFormType;
use App\Form\CategoryFormType;
use App\Form\RegistrationFormType;
use App\Form\ReviewFormType;
use App\Form\SubCategoryFormType;
use App\Repository\AttributeRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\UserRepository;
use App\Service\AmazonApi;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    private ManagerRegistry $doctrine;
    private AmazonApi $AmazonApi;
    const TOKEN = '_token';

    public function __construct(ManagerRegistry $doctrine, AmazonApi $amazonApi)
    {
        $this->doctrine = $doctrine;
        $this->AmazonApi = $amazonApi;

    }

    /**
     * @return Response
     */
    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }

    /**
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @return Response
     */
    #[Route('/category-list', name: 'category_list')]
    public function categoryList(Request $request, CategoryRepository $categoryRepository): Response
    {

        $categories = $categoryRepository->findAll();
        //Permet de retourner la liste des key
        //$keys = array_keys((array)$categories[0]);

        return $this->render('dashboard/category/category_list.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/add-category', name: 'add_category')]
    public function addCategory(Request $request): Response
    {
        $form = $this->createForm(CategoryFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->doctrine->getManager();
            $category = new Category();
            $category->setTitle($form->getData()->getTitle());
            $category->setPathCategory($form->getData()->getPathCategory());
            $category->setImage($form->getData()->getImage());
            $entityManager->persist($category);
            $entityManager->flush();

        }
        return $this->render('dashboard/category/add_category.html.twig', [
            'Form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Category $category
     * @param CategoryRepository $categoryRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/delete-category/{id}', name: 'delete_category', methods: ['POST'])]
    public function deleteCategory(Request $request,
                                   Category $category,
                                   CategoryRepository $categoryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get(self::TOKEN))) {
            $categoryRepository->remove($category);
        }
        return $this->redirectToRoute('category_list', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param Category $category
     * @param CategoryRepository $categoryRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/edit-category/{id}', name: 'edit_category', methods: ['GET', 'POST'])]
    public function editCategory(Request $request,
                                 Category $category,
                                 CategoryRepository $categoryRepository): Response
    {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categoryRepository->add($category);
            return $this->redirectToRoute('category_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dashboard/category/edit.html.twig', [
            'category' => $category,
            'Form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param SubCategoryRepository $subCategoryRepository
     * @return Response
     */
    #[Route('/subcategory-list', name: 'subcategory_list')]
    public function subcategoryList(Request $request,
                                    SubCategoryRepository $subCategoryRepository): Response
    {
        $subCategories = $subCategoryRepository->findAll();
        return $this->render('dashboard/subcategory/subcategory_list.html.twig', [
            'subcategories' => $subCategories,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/add-subcategory', name: 'add_subcategory')]
    public function addSubCategory(Request $request): Response
    {
        $form = $this->createForm(SubCategoryFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->doctrine->getManager();
            $subCategory = new SubCategory();
            $subCategory->setTitle($form->getData()->getTitle());
            $subCategory->setPathSubCategory($form->getData()->getPathSubCategory());
            $subCategory->setCategory($form->getData()->getCategory());
            $entityManager->persist($subCategory);
            $entityManager->flush();

        }
        return $this->render('dashboard/subcategory/add_subcategory.html.twig', [
            'Form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param SubCategory $subCategory
     * @param SubCategoryRepository $subCategoryRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/delete-subcategory/{id}', name: 'delete_subcategory', methods: ['POST'])]
    public function deleteSubCategory(Request $request,
                                      SubCategory $subCategory,
                                      SubCategoryRepository $subCategoryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $subCategory->getId(), $request->request->get(self::TOKEN))) {
            $subCategoryRepository->remove($subCategory);
        }

        return $this->redirectToRoute('subcategory_list', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param SubCategory $subCategory
     * @param SubCategoryRepository $subCategoryRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/edit-subcategory/{id}', name: 'edit_subcategory', methods: ['GET', 'POST'])]
    public function editSubCategory(Request $request,
                                    SubCategory $subCategory,
                                    SubCategoryRepository $subCategoryRepository): Response
    {
        $form = $this->createForm(SubCategoryFormType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $subCategoryRepository->add($subCategory);
            return $this->redirectToRoute('subcategory_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dashboard/subcategory/edit.html.twig', [
            'subcategory' => $subCategory,
            'Form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param ProductRepository $productRepository
     * @return Response
     */
    #[Route('/product-list', name: 'product_list')]
    public function productList(Request $request,
                                ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('dashboard/product/product_list.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/add-product', name: 'add_product')]
    public function addProduct(Request $request): Response
    {
        $form = $this->createForm(AsinFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $asin = $form->getData()['asin'];
            $subCat = $form->getData()['SubCategory'];
            $rank = $form->getData()['Rank'];
            $pathProduct = $form->getData()['pathProduct'];
            $aLink = $form->getData()['aLink'];
            $entityManager = $this->doctrine->getManager();
            if ($form->get('add')->isClicked()) {
                $product = new Product();
                $this->AmazonApi->amazonProduct($product, $asin, $subCat, $rank, $pathProduct, $aLink);
            }
            if ($form->get('update')->isClicked()) {
                $product = $this->doctrine->getRepository(Product::class)->findOneBy(['asin' => $asin]);
                $this->AmazonApi->amazonProduct($product, $asin, $subCat, $rank, $pathProduct, $aLink);
            }
        }
        return $this->render('dashboard/product/add_product.html.twig', [
            'Form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Product $product
     * @param ProductRepository $productRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/delete-product/{id}', name: 'delete_product', methods: ['POST'])]
    public function deleteProduct(Request $request,
                                  Product $product,
                                  ProductRepository $productRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get(self::TOKEN))) {
            $productRepository->remove($product);
        }

        return $this->redirectToRoute('product_list', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param Product $product
     * @param ProductRepository $productRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/edit-product/{id}', name: 'edit_product', methods: ['GET', 'POST'])]
    public function editProduct(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        $form = $this->createForm(AsinFormType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->add($product);
            return $this->redirectToRoute('product_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dashboard/product/edit.html.twig', [
            'product' => $product,
            'Form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param AttributeRepository $attributeRepository
     * @return Response
     */
    #[Route('/attribute-list', name: 'attribute_list')]
    public function attributeList(Request $request, AttributeRepository $attributeRepository): Response
    {
        $attributes = $attributeRepository->findAll();
        return $this->render('dashboard/attribute/attribute_list.html.twig', [
            'attributes' => $attributes,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/add-attribute', name: 'add_attribute')]
    public function addAttribute(Request $request): Response
    {
        $form = $this->createForm(AttributeFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->doctrine->getManager();
            $attribute = new Attribute();
            $attribute->setName($form->getData()->getName());
            $attribute->setValue($form->getData()->getValue());
            $attribute->setState($form->getData()->getState());
            $attribute->setProduct($form->getData()->getProduct());
            $entityManager->persist($attribute);
            $entityManager->flush();

        }
        return $this->render('dashboard/attribute/add_attribute.html.twig', [
            'Form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Attribute $attribute
     * @param AttributeRepository $attributeRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/delete-attribute/{id}', name: 'delete_attribute', methods: ['POST'])]
    public function deleteAttribute(Request $request,
                                    Attribute $attribute,
                                    AttributeRepository $attributeRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $attribute->getId(), $request->request->get(self::TOKEN))) {
            $attributeRepository->remove($attribute);
        }

        return $this->redirectToRoute('attribute_list', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param Attribute $attribute
     * @param AttributeRepository $attributeRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/edit-attribute/{id}', name: 'edit_attribute', methods: ['GET', 'POST'])]
    public function editAttribute(Request $request,
                                  Attribute $attribute,
                                  AttributeRepository $attributeRepository): Response
    {
        $form = $this->createForm(AttributeFormType::class, $attribute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attributeRepository->add($attribute);
            return $this->redirectToRoute('attribute_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dashboard/attribute/edit.html.twig', [
            'attribute' => $attribute,
            'Form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param ReviewRepository $reviewRepository
     * @return Response
     */
    #[Route('/review-list', name: 'review_list')]
    public function reviewList(Request $request, ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findAll();
        return $this->render('dashboard/review/review_list.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/add-review', name: 'add_review')]
    public function addReview(Request $request): Response
    {
        $form = $this->createForm(ReviewFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->doctrine->getManager();
            $review = new Review();
            $review->setTitle($form->getData()->getTitle());
            $review->setBody($form->getData()->getBody());
            $review->setRating($form->getData()->getRating());
            $review->setCountry($form->getData()->getCountry());
            $review->setDate($form->getData()->getDate());
            $review->setProfileName($form->getData()->getProfileName());
            $review->setProfilePicture($form->getData()->getProfilePicture());
            $review->setProduct($form->getData()->getProduct());
            $entityManager->persist($review);
            $entityManager->flush();

        }
        return $this->render('dashboard/review/add_review.html.twig', [
            'Form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param Review $review
     * @param ReviewRepository $reviewRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/delete-review/{id}', name: 'delete_review', methods: ['POST'])]
    public function deleteReview(Request $request,
                                 Review $review,
                                 ReviewRepository $reviewRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $review->getId(), $request->request->get(self::TOKEN))) {
            $reviewRepository->remove($review);
        }

        return $this->redirectToRoute('review_list', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param Request $request
     * @param Review $review
     * @param ReviewRepository $reviewRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/edit-review/{id}', name: 'edit_review', methods: ['GET', 'POST'])]
    public function editReview(Request $request,
                               Review $review,
                               ReviewRepository $reviewRepository): Response
    {
        $form = $this->createForm(ReviewFormType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reviewRepository->add($review);
            return $this->redirectToRoute('review_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dashboard/review/edit.html.twig', [
            'review' => $review,
            'Form' => $form,
        ]);
    }

    /**
     * @param Request $request
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/user-list', name: 'user_list')]
    public function userList(Request $request,
                             UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('dashboard/user/user_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/delete-user/{id}', name: 'delete_user', methods: ['POST'])]
    public function deleteUser(Request $request,
                               User $user,
                               UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get(self::TOKEN))) {
            $userRepository->remove($user);
        }

        return $this->redirectToRoute('user_list', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @todo Modification password exemple RegistrationController
     * @param Request $request
     * @param User $user
     * @param UserRepository $userRepository
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route('/edit-user/{id}', name: 'edit_user', methods: ['GET', 'POST'])]
    public function editUser(Request $request,
                             User $user,
                             UserRepository $userRepository): Response
    {
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->add($user);
            return $this->redirectToRoute('user_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('dashboard/user/edit.html.twig', [
            'user' => $user,
            'Form' => $form,
        ]);
    }
}
