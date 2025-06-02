<?php


namespace App\Service\Bill;

use DateTime;
use App\Entity\Car;
use App\Entity\User;
use DateTimeInterface;
use App\Entity\Billing;
use App\Service\Car\CarService;
use App\Repository\BillingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\CarRepository;

class BillingService
{
    private EntityManagerInterface $entityManager;
    private BillingRepository $repository;
    private CarService $carService;
    private CarRepository $carRepository;
    private const NB_VIP_CAR = 10;
    private const REDUCE_PCT = 0.1;

    /**
     * BillingService constructor.
     * @param EntityManagerInterface $entityManager
     * @param BillingRepository $repository
     * @param CarService $carService
     * @param CarRepository $carRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager, 
        BillingRepository $repository, 
        CarService $carService,
        CarRepository $carRepository
    ) {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->carService = $carService;
        $this->carRepository = $carRepository;
    }

    public function getDashboardInfo(?User $user, string $role): array
    {
        $infos = array();
        
        if ($role == 'RENTER') {
            // Véhicules en location
            $rentedCars = $this->repository->findBy(['idUser' => $user, 'returned' => false]);
            $infos[] = count($rentedCars);

            // Véhicules retournés
            $returnedCars = $this->repository->findBy(['idUser' => $user, 'returned' => true]);
            $infos[] = count($returnedCars);

            // Véhicules disponibles
            $totalCars = $this->carRepository->findBy(['idOwner' => $user]);
            $availableCars = array_filter($totalCars, function($car) {
                return $car->getRent() === 'disponible';
            });
            $infos[] = count($availableCars);

            // Montant total payé
            $totalPaid = 0;
            $allBills = $this->repository->findBy(['idUser' => $user]);
            foreach ($allBills as $bill) {
                if ($bill->getPaid()) {
                    $totalPaid += $bill->getPrice();
                }
            }
            $infos[] = $totalPaid;

            // Locations impayées
            $unpaidBills = $this->repository->findBy(['idUser' => $user, 'paid' => false]);
            $infos[] = count($unpaidBills);

            // Revenus du mois
            $currentMonth = new \DateTime('first day of this month');
            $monthlyBills = $this->repository->findBy(['idUser' => $user]);
            $monthlyRevenue = 0;
            foreach ($monthlyBills as $bill) {
                if ($bill->getPaid() && $bill->getStartDate() >= $currentMonth) {
                    $monthlyRevenue += $bill->getPrice();
                }
            }
            $infos[] = $monthlyRevenue;
        } else {
            // Statistiques pour les clients
            // Locations en cours
            $activeRentals = $this->repository->findBy(['idUser' => $user, 'returned' => false]);
            $infos[] = count($activeRentals);

            // Locations terminées
            $completedRentals = $this->repository->findBy(['idUser' => $user, 'returned' => true]);
            $infos[] = count($completedRentals);

            // Total des locations
            $totalRentals = $this->repository->findBy(['idUser' => $user]);
            $infos[] = count($totalRentals);

            // Montant total payé
            $totalPaid = 0;
            foreach ($totalRentals as $bill) {
                if ($bill->getPaid()) {
                    $totalPaid += $bill->getPrice();
                }
            }
            $infos[] = $totalPaid;

            // Locations impayées
            $unpaidBills = $this->repository->findBy(['idUser' => $user, 'paid' => false]);
            $infos[] = count($unpaidBills);

            // Dépenses du mois
            $currentMonth = new \DateTime('first day of this month');
            $monthlySpent = 0;
            foreach ($totalRentals as $bill) {
                if ($bill->getPaid() && $bill->getStartDate() >= $currentMonth) {
                    $monthlySpent += $bill->getPrice();
                }
            }
            $infos[] = $monthlySpent;
        }
        
        return $infos;
    }

    public function createBill(UserInterface $user, Car $car, array $rentOptions)
    {
        $hasReduce = false;
        $hasEndDate = false;
        $isPaid = false;

        $user_tmp = $user;
        /**
         * @var User $user_tmp
         */
        $bills = $this->showBillsOfCustomer($user_tmp);
        $nbRent = count($bills);

        if($nbRent > self::NB_VIP_CAR){
            $hasReduce = true;
        }

        $nbDays = (int)date('t');
        if($rentOptions['endDate']){
            $hasEndDate = true;
            /**
             * @var DateTimeInterface $date
             */
            $date = $rentOptions['endDate'];
            $currDate = new DateTime();
            if($currDate->format('n') === $date->format('n') ) {
                $isPaid = true;
            }
            $nbDays = $rentOptions['startDate']->diff($rentOptions['endDate'])->days;
        }

        $bill = new Billing();
        $bill->setIdCar($car)
            ->setIdUser($user)
            ->setPrice($hasReduce ? ($car->getAmount()*$nbDays*(1-self::REDUCE_PCT)) :
                $car->getAmount()*$nbDays)
            ->setStartDate($rentOptions['startDate']);
        if($hasEndDate){
            $bill->setEndDate($rentOptions['endDate']);
        }
        $bill->setPaid($isPaid)
            ->setReturned(false);

        $this->entityManager->persist($bill);
    }

    public function removeBill (Billing $bill)
    {
        $this->entityManager->remove($bill);
    }

    /**
     * @param Billing $bill
     */
    public function returnCarBill (Billing $bill)
    {
        $bill->setReturned(true);
        $this->entityManager->flush();

    }

    public function flushBill()
    {
        $this->entityManager->flush();
    }

    public function showBills() :array
    {
        return $this->repository->findAllBills();
    }

    public function showBillsOfCustomer(User $customer) :array
    {
        return $this->repository->findAllBillsOfCustomer($customer->getId());
    }

    public function showBillsOfRenter(User $renter)
    {
        return $this->repository->findAllBillsOfRenter($renter->getId());

    }

    public function showBillsOfCustomerReturned(User $customer) :array
    {
        return $this->repository->findAllBillsOfCustomerWithOption($customer->getId(), true);
    }

    public function showBillsOfCustomerNotReturned(User $customer) :array
    {
        return $this->repository->findAllBillsOfCustomerWithOption($customer->getId(), false);
    }

    public function hasBillsRelated(Car $car): bool
    {
        return count($this->repository->findAllBillsOfCar($car->getId())) > 0;
    }

    public function payBill(Billing $bill)
    {
        $bill->setPaid(true);
        $this->entityManager->flush();
    }

    /**
     * Update a billing
     * @param Billing $bill
     */
    public function update(Billing $bill)
    {
        $this->entityManager->persist($bill);
        $this->entityManager->flush();
    }

    /**
     * Delete a billing
     * @param Billing $bill
     */
    public function delete(Billing $bill)
    {
        $this->entityManager->remove($bill);
        $this->entityManager->flush();
    }
}