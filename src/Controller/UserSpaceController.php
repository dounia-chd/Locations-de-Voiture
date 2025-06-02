<?php

namespace App\Controller;

use App\Entity\Billing;
use App\Entity\User;
use App\Service\Car\CarService;
use App\Service\User\UserService;
use App\Service\Bill\BillingService;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class UserSpaceController
 * @package App\Controller
 * @Route("/user-space", name="user_space_")
 */
class UserSpaceController extends AbstractController
{
    private UserService $userService;
    private CarService $carService;
    private BillingService $billingService;
    private UserRepository $userRepository;

    public function __construct(
        UserService $userService, 
        CarService $carService, 
        BillingService $billingService,
        UserRepository $userRepository
    ) {
        $this->userService = $userService;
        $this->carService = $carService;
        $this->billingService = $billingService;
        $this->userRepository = $userRepository;
    }

    /**
     * Dashboard of the user
     * @Route("", name="index")
     * @return Response
     */
    public function index() :Response
    {
        /**
         * @var User $customer
         */
        $customer = $this->getUser();
        $infos = $this->billingService->getDashboardInfo($customer, 'CUSTOMER');
        return $this->render("user_space/dashboard.html.twig", [
            'infos' => $infos
        ]);
    }

    /**
     * Show actually cars rented ( not returned )
     * @Route("/client/rentals-{id}", name="client_rentals")
     * @param User $customer
     * @return Response
     */
    public function showRentals(User $customer) :Response
    {
        $bills = $this->billingService->showBillsOfCustomerNotReturned($customer);
        $billsFormatted = array();
        foreach ($bills as $bill) {
            $car = $bill->getIdCar();
            array_push($billsFormatted, [$bill, $car]);
        }
        return $this->render('user_space/client/rentals.html.twig', [
            'bills' => $billsFormatted,
            'id' => $customer->getId()
        ]);
    }

    /**
     * @Route("/client/bills-{id}", name="client_bills")
     * @param int $id
     * @return Response
     */
    public function showBills(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $bills = $this->billingService->showBillsOfCustomer($user);
        $billsFormatted = array();
        foreach ($bills as $bill) {
            $car = $bill->getIdCar();
            array_push($billsFormatted, [$bill, $car]);
        }

        return $this->render('user_space/client/bills.html.twig', [
            'bills' => $billsFormatted
        ]);
    }

    /**
     * @Route("/car/return/{id}", name="car_return")
     * @param Billing $bill
     * @return Response
     */
    public function returnCar(Billing $bill) :Response
    {
        $this->carService->return($bill->getIdCar());
        $this->billingService->returnCarBill($bill);

        $this->addFlash('message', "Le véhicule à bien été rendu");
        return $this->redirectToRoute('comment_add', [
            'id' => $bill->getId()
        ]);
    }

    /**
     * @Route("/client/profile-{id}", name="client_profile")
     * @param int $id
     * @return Response
     */
    public function showProfile(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        return $this->render('user_space/client/profile.html.twig', [
            'user' => $user
        ]);
    }
}
