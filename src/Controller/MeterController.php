<?php

namespace App\Controller;

use App\Entity\Meter;
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
 * Class MeterController
 * @package App\Controller
 */
class MeterController extends AbstractController
{
    /**
     * @Route("/user/meter/create", name="user_meter_create", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param MeterRepository $meterRepository
     * @return JsonResponse
     */
    public function meterCreate(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        MeterRepository $meterRepository
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

        $params = $request->request->all();

        $userId = $params['id'];
        $meterNumber = $params['number'];

        $newMeter = new Meter();
        $newMeter->setNumber($meterNumber)
            ->setStatus(1)
            ->setTimeCreate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')))
            ->setTimeUpdate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')));

        $user = $userRepository->find($userId);
        $meters = $user->getMeters()->getValues();

        for($i=0; $i<count($meters); $i++){
            $meters[$i]->setStatus(0);
            $entityManager->persist($meters[$i]);
            $entityManager->flush();
            //$entityManager->clear();
        }

        $user->addMeter($newMeter);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User meter updated successful!', 'code' => JsonResponse::HTTP_OK]);
    }

    /**
     * @Route("/meters/ssp", name="meter_ssp", methods={"POST"})
     * @param Request $request
     * @param MeterRepository $meterRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function getMeters(
        Request $request,
        MeterRepository $meterRepository,
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

        //Total meters number
        $query = $entityManager->createQuery(
            "SELECT COUNT(m.id) FROM App\Entity\Meter m"
        );
        $results = $query->getResult();
        $recordsTotal = $results[0][1];

        //Filtered meters number
        if($searchValue != ''){
            $query = $entityManager->createQuery(
                "SELECT COUNT(m.id) 
                FROM App\Entity\Meter m 
                WHERE m.id LIKE :value OR m.number LIKE :value OR m.status LIKE :value OR m.timeCreate LIKE :value OR m.timeUpdate LIKE :value 
                ORDER BY m.id ASC"
            );
            $query->setParameter('value', '%' . $searchValue . '%');
            $results = $query->getResult();
            $recordsFiltered = $results[0][1];
        }
        else{
            $recordsFiltered = $recordsTotal;
        }

        //Paginated, filtered, ordered meters
        $where = '';
        if($searchValue != ''){
            $where = 'WHERE m.id LIKE :value OR m.number LIKE :value OR m.status LIKE :value OR m.timeCreate LIKE :value OR m.timeUpdate LIKE :value';
        }

        $orderBy = '';
        if(isset($orderDirection) && isset($orderColumn)){
            switch($orderColumn){
                case 0:
                    $orderBy = 'ORDER BY m.id ' . $orderDirection;
                    break;
                case 1:
                    $orderBy = 'ORDER BY m.number ' . $orderDirection;
                    break;
                case 2:
                    $orderBy = 'ORDER BY m.status ' . $orderDirection;
                    break;
                case 3:
                    $orderBy = 'ORDER BY m.timeCreate ' . $orderDirection;
                    break;
                case 4:
                    $orderBy = 'ORDER BY m.timeUpdate ' . $orderDirection;
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
            "SELECT m.id AS meterId, m.number AS meterNumber, m.status AS meterStatus, m.timeCreate AS meterTimeCreate, m.timeUpdate AS meterTimeUpdate 
            FROM App\Entity\Meter m " .
            $where . " " .
            $orderBy
        );

        if($searchValue != '') {
            $query->setParameter('value', '%' . $searchValue . '%');
        }
        if($limitStart != 0 && $limitLength != 0){
            $query->setFirstResult($limitStart);
            $query->setMaxResults($limitLength);
        }

        $results = $query->getResult();
        $meters = $results;

        $tableData = [];
        for($i=0; $i<count($meters); $i++){
            $arr = [
                'id' => $meters[$i]['meterId'],
                'Номер' => $meters[$i]['meterNumber'],
                'Статус' => $meters[$i]['meterStatus'],
                'Додано' => $meters[$i]['meterTimeCreate']->format('Y-m-d H:i:s'),
                'Обновлено' => $meters[$i]['meterTimeUpdate']->format('Y-m-d H:i:s'),
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

