<?php

namespace App\Controller;

use App\Entity\MeterReading;
use App\Repository\MeterReadingRepository;
use App\Repository\MeterRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class MeterReadingController
 * @package App\Controller
 */
class MeterReadingController extends AbstractController
{
    /**
     * @Route("/user/meter/reading/create", name="user_meter_reading_create", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param MeterRepository $meterRepository
     * @param MeterReadingRepository $meterReadingRepository
     * @return JsonResponse
     */
    public function meterReadingCreate(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MeterRepository $meterRepository,
        MeterReadingRepository $meterReadingRepository
    ): JsonResponse
    {
        $params = $request->request->all();

        $userId = $params['userId'];
        //$meterId = $params['meterId'];
        $meterReadingValue = $params['meterReadingValue'];

        $user = $userRepository->find($userId);
        $meters = $user->getMeters()->getValues();

        $meter = [];
        for($i=0; $i<count($meters); $i++){
            if($meters[$i]->getStatus() == 1){
                $meter = $meters[$i];
            }
        }

        if(!$meter){
            return new JsonResponse(['message' => 'User meter reading was not created. There is no active meter for user!', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        $meterReading = new MeterReading();
        $meterReading->setMeterId($meter->getId())
            ->setValue($meterReadingValue)
            ->setDate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')))
            ->setStatus(0)
            ->setTimeCreate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')))
            ->setTimeUpdate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')))
            ->setMeter($meter);

        $entityManager->persist($meterReading);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User meter reading created successful!', 'code' => JsonResponse::HTTP_OK]);
    }

    /**
     * @Route("/user/meter/reading/update/{id}", name="user_meter_reading_update", methods={"GET"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param MeterRepository $meterRepository
     * @param MeterReadingRepository $meterReadingRepository
     * @return JsonResponse
     */
    public function meterReadingUpdate(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MeterRepository $meterRepository,
        MeterReadingRepository $meterReadingRepository
    ): JsonResponse
    {
        $params = $request->request->all();

        return new JsonResponse(['message' => 'User meter reading updated successful!', 'code' => JsonResponse::HTTP_OK]);
    }

    /**
     * @Route("/user/meter/reading/delete/{id}", name="user_meter_reading_delete", methods={"GET"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param MeterRepository $meterRepository
     * @param MeterReadingRepository $meterReadingRepository
     * @return JsonResponse
     */
    public function meterReadingDelete(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MeterRepository $meterRepository,
        MeterReadingRepository $meterReadingRepository
    ): JsonResponse
    {
        $params = $request->request->all();

        return new JsonResponse(['message' => 'User meter reading deleted successful!', 'code' => JsonResponse::HTTP_OK]);
    }

    /**
     * @Route("/meters/readings/ssp", name="meter_reading_ssp", methods={"POST"})
     * @param Request $request
     * @param MeterReadingRepository $meterReadingRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function getMetersReadings(
        Request $request,
        MeterReadingRepository $meterReadingRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $loggedUser = $this->getUser();
        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return new JsonResponse(
                    ['message' => 'Помилка! Недостатньо прав!', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]
                );
            }
        }

        $requestParams = $request->request->all();

        $draw = trim($requestParams['draw']);
        if(isset($requestParams['order'][0]['column'])){
            $orderColumn = trim($requestParams['order'][0]['column']);
        }
        if(isset($requestParams['order'][0]['dir'])){
            $orderDirection = trim($requestParams['order'][0]['dir']);
        }
        $startNumber = intval(trim($requestParams['start']));
        $length = intval(trim($requestParams['length']));
        $searchValue = strval(trim($requestParams['search']['value']));

        //Total meters readings number
        $query = $entityManager->createQuery(
            "SELECT COUNT(mr.id) FROM App\Entity\MeterReading mr"
        );
        $results = $query->getResult();
        $recordsTotal = $results[0][1];

        //Filtered meters readings number
        if($searchValue != ''){
            $query = $entityManager->createQuery(
                "SELECT COUNT(mr.id) 
                FROM App\Entity\MeterReading mr 
                WHERE mr.id LIKE :value OR mr.meterId LIKE :value OR mr.value LIKE :value OR mr.date LIKE :value OR 
                    mr.status LIKE :value OR mr.timeCreate LIKE :value OR mr.timeUpdate LIKE :value 
                ORDER BY mr.id ASC"
            );
            $query->setParameter('value', '%' . $searchValue . '%');
            $results = $query->getResult();
            $recordsFiltered = $results[0][1];
        }
        else{
            $recordsFiltered = $recordsTotal;
        }

        //Paginated, filtered, ordered meters readings
        $where = '';
        if($searchValue != ''){
            $where = 'WHERE mr.id LIKE :value OR mr.meterId LIKE :value OR mr.value LIKE :value OR mr.date LIKE :value OR
                mr.status LIKE :value OR mr.timeCreate LIKE :value OR mr.timeUpdate LIKE :value';
        }

        $orderBy = '';
        if(isset($orderDirection) && isset($orderColumn)){
            switch($orderColumn){
                case 0:
                    $orderBy = 'ORDER BY mr.id ' . $orderDirection;
                    break;
                case 1:
                    $orderBy = 'ORDER BY mr.meterId ' . $orderDirection;
                    break;
                case 2:
                    $orderBy = 'ORDER BY mr.value ' . $orderDirection;
                    break;
                case 3:
                    $orderBy = 'ORDER BY mr.date ' . $orderDirection;
                    break;
                case 4:
                    $orderBy = 'ORDER BY mr.status ' . $orderDirection;
                    break;
                case 5:
                    $orderBy = 'ORDER BY mr.timeCreate ' . $orderDirection;
                    break;
                case 6:
                    $orderBy = 'ORDER BY mr.timeUpdate ' . $orderDirection;
                    break;
                default:
                    break;
            }
        }

        if($length != -1) {
            if ($recordsFiltered == 0) {
                $begin = 0;
                $end = 0;
            }
            if ($recordsFiltered <= $length) {
                $begin = 0;
                $end = $recordsFiltered - 1;
            }
            if ($recordsFiltered > $length) {
                if ($recordsFiltered % $length == 0) {
                    if ($startNumber > $recordsFiltered - 1) {
                        $begin = $recordsFiltered - $length;
                        $end = $recordsFiltered - 1;
                    } else {
                        $begin = $startNumber;
                        $end = $startNumber + $length - 1;
                    }
                } else {
                    if ($startNumber > $recordsFiltered - 1 - ($recordsFiltered % $length)) {
                        $begin = $recordsFiltered - ($recordsFiltered % $length);
                        $end = $recordsFiltered - 1;
                    } else {
                        $begin = $startNumber;
                        $end = $startNumber + $length - 1;
                    }
                }
            }
            $limitStart = $begin;
            $limitLength = $length;
        }
        else{
            $limitStart = 0;
            $limitLength = 0;
        }

        $query = $entityManager->createQuery(
            "SELECT mr.id AS meterReadingId, mr.meterId AS meterId, mr.value AS meterReadingValue, mr.date AS meterReadingDate,  
                mr.status AS meterReadingStatus, mr.timeCreate AS meterReadingTimeCreate, mr.timeUpdate AS meterReadingTimeUpdate 
            FROM App\Entity\MeterReading mr " .
            $where . " " .
            $orderBy
        );

        if($searchValue != ''){
            $query->setParameter('value', '%' . $searchValue . '%');
        }
        if($limitStart != 0 && $limitLength != 0){
            $query->setFirstResult($limitStart);
            $query->setMaxResults($limitLength);
        }

        $results = $query->getResult();
        $metersReadings = $results;

        $tableData = [];
        for($i=0; $i<count($metersReadings); $i++){
            $arr = [
                'id' => $metersReadings[$i]['meterReadingId'],
                'meter_id' => $metersReadings[$i]['meterId'],
                'Показання' => $metersReadings[$i]['meterReadingValue'],
                'Дата' => $metersReadings[$i]['meterReadingDate']->format('Y-m-d'),
                'Статус' => $metersReadings[$i]['meterReadingStatus'],
                'Передано' => $metersReadings[$i]['meterReadingTimeCreate']->format('Y-m-d H:i:s'),
                'Обновлено' => $metersReadings[$i]['meterReadingTimeUpdate']->format('Y-m-d H:i:s'),
            ];
            array_push($tableData, $arr);
        }
        $output = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $tableData
        ];

        return new JsonResponse($output);
    }

}