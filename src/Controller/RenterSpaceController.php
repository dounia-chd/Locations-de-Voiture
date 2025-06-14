<?php


namespace App\Controller;


use App\Entity\Car;
use App\Entity\User;
use App\Form\NewCarType;
use App\Form\BillsFilterType;
use App\Entity\SearchBillData;
use App\Service\Car\CarService;
use App\Service\User\UserService;
use App\Service\Bill\BillingService;
use App\Service\FileUpload\FileUploader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Billing;
use App\Form\BillingType;

/**
 * Class RenterSpaceController
 * @package App\Controller
 * @Route("/user-space/renter", name="user_space_renter_")
 * @IsGranted("ROLE_RENTER")
 */
class RenterSpaceController extends AbstractController
{
    private UserService $userService;
    private CarService $carService;
    private BillingService $billingService;

    public function __construct(UserService $userService, CarService $carService, BillingService $billingService)
    {
        $this->userService = $userService;
        $this->carService = $carService;
        $this->billingService = $billingService;
    }

    /**
     * @Route("", name="index")
     * @return Response
     */
    public function index(): Response
    {
        /**
         * @var User $renter
         */
        $renter = $this->getUser();
        $infos = $this->billingService->getDashboardInfo($renter, 'RENTER');
        return $this->render("user_space/dashboard.html.twig", [
            'infos' => $infos
        ]);
    }

    /**
     * @Route("/cars", name="cars")
     * @return Response
     */
    public function showCars() :Response
    {
        $renter = $this->getUser();
        $cars = $this->carService->getAllCarsByOwnerId($renter);
        return $this->render("user_space/renter/cars.html.twig", [
            'cars' => $cars
        ]);
    }

    private function arrangeBills(array $bills) :array
    {
        $filteredBills = array();
        foreach ($bills as $bill){
            $car = $bill->getIdCar();
            $renter = $bill->getIdUser();
            array_push($filteredBills, [$bill, $car, $renter]);
        }
        return $filteredBills;
    }

    /**
     * Show the rented cars of a renter
     * @Route("/rented/cars", name="cars_rented")
     * @param Request $request
     * @return Response
     */
    public function showRentedCars(Request $request) :Response
    {
        $searchData = new SearchBillData();
        $searchForm = $this->createForm(BillsFilterType::class, $searchData);

        $searchForm->handleRequest($request);
        if($searchForm->isSubmitted() && $searchForm->isValid()){
            $bills = $this->billingService->showBillsOfCustomer($searchData->getUser());
            $filteredBills = $this->arrangeBills($bills);
        }
        else {
            /**
             * @var User $user
             */
            $user = $this->getUser();

            $bills = $this->billingService->showBillsOfRenter($user);
            $filteredBills = $this->arrangeBills($bills);
        }

        return $this->render('user_space/renter/rentedCars.html.twig', [
            'bills' => $filteredBills,
            'form' => $searchForm->createView()
        ]);
    }

    /**
     * @Route("car/edit/{id}", name="car_edit")
     * @param Request $request
     * @param FileUploader $fileUploader
     * @param Car $car
     * @return Response
     */
    public function editCar(Request $request, FileUploader $fileUploader, Car $car) :Response
    {
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
            $this->addFlash('message',"Votre véhicule à bien été modifié");

            return $this->redirectToRoute('user_space_renter_cars', [
                'id' => $this->getUser()->getId()
            ]);

        }

        return $this->render("car/car.new.html.twig", [
            'form' => $form->createView()
        ]);
    }

    /** The renter can remove a car from his list
     * @Route("/car/delete/{id}", name="car_delete")
     * @param Car $car
     * @return Response
     */
    public function removeCar(Car $car) :Response
    {
        if($this->billingService->hasBillsRelated($car)){
            return $this->redirectToRoute('user_space_renter_cars',[
                'id' => $this->getUser()->getId()
            ]);
        }
        $this->carService->remove($car);
        $this->addFlash('message', "Votre véhicule a bien été supprimé");
        return $this->redirectToRoute("user_space_renter_cars", [
            'id' => $this->getUser()->getId()
        ]);
    }

    /**
     * @Route("/profile", name="profile")
     * @return Response
     */
    public function showProfile(): Response
    {
        $renter = $this->getUser();
        $infos = $this->billingService->getDashboardInfo($renter, 'RENTER');
        return $this->render("user_space/renter/profile.html.twig", [
            'infos' => $infos
        ]);
    }

    /**
     * @Route("/rental/edit/{id}", name="edit_rental")
     * @param Request $request
     * @param Billing $bill
     * @return Response
     */
    public function editRental(Request $request, Billing $bill): Response
    {
        $form = $this->createForm(BillingType::class, $bill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->billingService->update($bill);
            $this->addFlash('success', 'La location a été modifiée avec succès');
            return $this->redirectToRoute('user_space_renter_cars_rented');
        }

        return $this->render('user_space/renter/edit_rental.html.twig', [
            'form' => $form->createView(),
            'bill' => $bill
        ]);
    }

    /**
     * @Route("/rental/delete/{id}", name="delete_rental")
     * @param Billing $bill
     * @return Response
     */
    public function deleteRental(Billing $bill): Response
    {
        if ($bill->getReturned()) {
            $this->addFlash('error', 'Impossible de supprimer une location déjà retournée');
            return $this->redirectToRoute('user_space_renter_cars_rented');
        }

        $this->billingService->delete($bill);
        $this->addFlash('success', 'La location a été supprimée avec succès');
        return $this->redirectToRoute('user_space_renter_cars_rented');
    }

    /**
     * @Route("/profile/update", name="update_profile", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function updateProfile(Request $request): Response
    {
        $user = $this->getUser();
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        // Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $user->getPassword())) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect');
            return $this->redirectToRoute('user_space_renter_profile');
        }

        // Mettre à jour les informations de base
        $user->setName($request->request->get('name'));
        $user->setEmail($request->request->get('email'));

        // Mettre à jour le mot de passe si fourni
        if ($newPassword) {
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas');
                return $this->redirectToRoute('user_space_renter_profile');
            }
            $user->setPassword(password_hash($newPassword, PASSWORD_DEFAULT));
        }

        $this->userService->update($user);
        $this->addFlash('success', 'Votre profil a été mis à jour avec succès');
        return $this->redirectToRoute('user_space_renter_profile');
    }

}