<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Billing;
use App\Form\NewCarType;
use App\Form\RentCarType;
use App\Service\Car\CarService;
use App\Service\Cart\CartService;
use App\Service\User\UserService;
use App\Service\Bill\BillingService;
use App\Service\Comment\CommentService;
use App\Service\FileUpload\FileUploader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class CarController
 * @package App\Controller
 */
class CarController extends AbstractController
{
    private CarService $carService;
    private CommentService $commentService;
    private BillingService $billingService;
    private UserService $userService;

    public function __construct(CarService $carService,
                                BillingService $billingService,
                                UserService $userService,
                                CommentService $commentService)
    {
        $this->carService = $carService;
        $this->billingService = $billingService;
        $this->userService = $userService;
        $this->commentService = $commentService;
    }


    /**
     * @Route("/cars", name="cars")
     * @return Response
     */
    public function index() :Response
    {
        // Récupérer tous les véhicules des loueurs
        $cars = $this->carService->getAllCars();
        $cars = array_filter($cars, function($car) {
            return $car->getIdOwner()->getRole() === 'ROLE_RENTER';
        });
        // Filtrer les véhicules sans images
        $cars = array_filter($cars, function($car) {
            return !empty($car->getImage());
        });
        return $this->render('car/index.html.twig', [
            'cars' => $cars
        ]);
    }

    /**
     * @Route("/car/new", name="car_new")
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function createCar(Request $request, FileUploader $fileUploader) :Response
    {
        $car = new Car();

        $form = $this->createForm(NewCarType::class, $car);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $carFile = $form->get('attachment')->getData();

            $datasheet = array();

            $datasheet['category'] = $form->get('category')->getData();
            $datasheet['fuel'] = $form->get('fuel')->getData();
            $datasheet['engine'] = $form->get('engine')->getData();
            $datasheet['shift'] = $form->get('shift')->getData();
            $datasheet['nb_portes'] = $form->get('nb_portes')->getData();

            $car->setDataSheet($datasheet);

            if ($carFile) {
                $carFileName = $fileUploader->upload($carFile);
                $car->setImage($carFileName);
            }
            $car->setRent("disponible");
            $car->setIdOwner($this->getUser());

            $this->carService->add($car);
            
            return $this->redirectToRoute('user_space_renter_cars', [
                'id' => $this->getUser()->getId()
            ]);
        }

        return $this->render('car/car.new.html.twig', [
            'form' => $form->createView()
        ]);

    }

    /**
     * @Route("/car/{id}", name="car_show")
     * @param Car $car
     * @return Response
     */
    public function showCar(Car $car) :Response
    {
        return $this->render('car/car.show.html.twig', [
            'car' => $car,
        ]);
    }

    /**
     * @Route("/car/rent/{id}", name="car_rent")
     * @param Car $car
     * @param Request $request
     * @param CartService $cartService
     * @return Response
     */
    public function rentCar(Car $car, Request $request, CartService $cartService) :Response
    {
        $bill = new Billing();
        $form = $this->createForm(RentCarType::class, $bill);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $data= $form->getData();

            if($data->getStartDate() == $data->getEndDate() || $data->getEndDate() == NULL){
                $rentOptions = [
                    'startDate' => $data->getStartDate(),
                    'endDate' => $data->getEndDate(),
                    'quantity' => (int)$form->get('quantity')->getData(),
                    'paid' => false
                ];
            }
            else{
                $rentOptions = [
                    'startDate' => $data->getStartDate(),
                    'endDate' => $data->getEndDate(),
                    'quantity' => (int)$form->get('quantity')->getData(),
                    'paid' => true
                ];
            }

            $cartService->add($car->getId(), $rentOptions, $rentOptions['quantity']);

            $this->addFlash('notif', "Votre commande à été ajoutée au panier.");

            return $this->redirectToRoute('cars');
        }

        return $this->render('car/car.rent.html.twig', [
            'car' => $car,
            'form' => $form->createView()
        ]);
    }


}
