<?php

namespace App\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\Validator\Constraints\Json;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController
{
    /**
     * @Route("/user/profile/update", name="user_profile_update", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function userProfileUpdate(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ) : JsonResponse {
        $requestParams = $request->request->all();
        $user = $this->getUser();

        if($requestParams['id'] != $user->getId()){
            return new JsonResponse(['message' => 'Помилка. Безпека під загрозою.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        $user->setFirstName($requestParams['firstName']);
        $user->setLastName($requestParams['lastName']);
        $user->setPatronymic($requestParams['patronymic']);
        $user->setHomePhone($requestParams['homePhone']);
        $user->setMobilePhone($requestParams['mobilePhone']);

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        return new JsonResponse(['message' => 'User updated successful!', 'code' => JsonResponse::HTTP_OK]);
    }

    /**
     * @Route("/user/status/update", name="user_status_update", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function userStatusUpdate(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): JsonResponse {
        $loggedUser = $this->getUser();
        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return new JsonResponse(['message' => 'Помилка! Недостатньо прав!', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
            }
        }

        $params = $request->request->all();

        $userId = $params['id'];
        $userStatus = $params['status'];

        $user = $userRepository->find($userId);

        $user->setStatus($userStatus);
        $user->setTimeUpdate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')));

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        return new JsonResponse(['message' => 'User status updated successful!', 'code' => JsonResponse::HTTP_OK]);
    }

    /**
     * @Route("/users/ssp", name="user_ssp", methods={"POST"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    public function getUsers(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): JsonResponse {
        $loggedUser = $this->getUser();
        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return new JsonResponse(['message' => 'Помилка! Недостатньо прав!', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
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

        //Total users number
        $query = $entityManager->createQuery(
            "SELECT COUNT(u.id) FROM App\Entity\User u"
        );
        $results = $query->getResult();
        $recordsTotal = $results[0][1];

        //Filtered users number
        if($searchValue != ''){
            /*$query = $entityManager->createQuery(
                "SELECT COUNT(u.id) 
                FROM App\Entity\User u 
                WHERE u.id LIKE :value OR u.email LIKE :value OR 
                    u.firstName LIKE :value OR u.lastName LIKE :value OR u.patronymic LIKE :value OR 
                    u.eicCode LIKE :value OR u.postcode LIKE :value OR 
                    u.city LIKE :value OR u.street LIKE :value OR u.building LIKE :value OR u.apartment LIKE :value OR 
                    u.personalAccount LIKE :value OR u.homePhone LIKE :value OR u.mobilePhone LIKE :value 
                ORDER BY u.id ASC"
            );*/
            $query = $entityManager->createQuery(
                "SELECT COUNT(u.id) 
                FROM App\Entity\User u 
                WHERE u.id LIKE :value OR u.email LIKE :value OR 
                    CONCAT(u.firstName, ' ', u.lastName, ' ', u.patronymic) LIKE :value OR 
                    u.eicCode LIKE :value OR 
                    CONCAT(u.postcode, ', ', u.city, ', ', u.street, ', ', u.building, ', ', u.apartment) LIKE :value OR 
                    u.personalAccount LIKE :value OR 
                    CONCAT(u.homePhone, ', ', u.mobilePhone) LIKE :value 
                ORDER BY u.id ASC"
            );
            $query->setParameter('value', '%' . $searchValue . '%');
            $results = $query->getResult();
            $recordsFiltered = $results[0][1];
        }
        else{
            $recordsFiltered = $recordsTotal;
        }
        //dd($recordsTotal, $recordsFiltered);

        //Paginated, filtered, ordered users
        $where = '';
        if($searchValue != ''){
            $where = "WHERE u.id LIKE :value OR u.email LIKE :value OR 
                CONCAT(u.lastName, ' ', u.firstName, ' ', u.patronymic) LIKE :value OR 
                u.eicCode LIKE :value OR 
                CONCAT(u.postcode, ', ', u.city, ', ', u.street, ', ', u.building, ', ', u.apartment) LIKE :value OR 
                u.personalAccount LIKE :value OR 
                CONCAT(u.homePhone, ', ', u.mobilePhone) LIKE :value";
        }

        $orderBy = '';
        if(isset($orderDirection) && isset($orderColumn)){
            switch($orderColumn){
                case 0:
                    $orderBy = 'ORDER BY u.id ' . $orderDirection;
                    break;
                case 1:
                    $orderBy = 'ORDER BY u.email ' . $orderDirection;
                    break;
                case 2:
                    $orderBy = 'ORDER BY u.roles ' . $orderDirection;
                    break;
                case 3:
                    $orderBy = 'ORDER BY u.status ' . $orderDirection;
                    break;
                case 4:
                    $orderBy = "ORDER BY CONCAT(u.lastName, ' ', u.firstName, ' ', u.patronymic) " . $orderDirection;
                    break;
                case 5:
                    $orderBy = 'ORDER BY u.eicCode ' . $orderDirection;
                    break;
                case 6:
                    $orderBy = "ORDER BY CONCAT(u.postcode, ', ', u.city, ', ', u.street, ', ', u.building, ', ', u.apartment) " . $orderDirection;
                    break;
                case 7:
                    $orderBy = 'ORDER BY u.personalAccount ' . $orderDirection;
                    break;
                case 8:
                    $orderBy = "ORDER BY CONCAT(u.homePhone, ', ', u.mobilePhone) " . $orderDirection;
                    break;
                //case 9:
                    //break;
                //case 10:
                    //break;
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
            "SELECT u.id AS userId, u.email AS userEmail, u.roles AS userRoles, u.status AS userStatus, 
                u.lastName AS userLastName, u.firstName AS userFirstName, u.patronymic AS userPatronymic, 
                u.eicCode AS eicCode, u.postcode AS postcode, 
                u.city AS city, u.street AS street, u.building AS building, u.apartment AS apartment, 
                u.personalAccount AS personalAccount, u.homePhone AS homePhone, u.mobilePhone AS mobilePhone, 
                d.id AS documentId, d.fileExtension AS fileExtension                 
            FROM App\Entity\User u 
            LEFT JOIN u.documents d " .
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
        $users = $results;
        //dd($users);

        $data = [];
        $documents = [];
        for($i=0; $i<count($users); $i++){
            if(!isset($data[$users[$i]['userId']])){
                $data[$users[$i]['userId']] = [];
            }
            if($users[$i]['documentId'] && !isset($documents[$users[$i]['userId']])){
                $documents[$users[$i]['userId']] = [];
            }
            $name = [];
            if($users[$i]['userLastName'] != ''){
                array_push($name, $users[$i]['userLastName']);
            }
            if($users[$i]['userFirstName'] != ''){
                array_push($name, $users[$i]['userFirstName']);
            }
            if($users[$i]['userPatronymic'] != ''){
                array_push($name, $users[$i]['userPatronymic']);
            }
            $location = [];
            if($users[$i]['postcode'] != 0 && $users[$i]['postcode'] != ''){
                array_push($location, $users[$i]['postcode']);
            }
            if($users[$i]['city'] != ''){
                array_push($location, $users[$i]['city']);
            }
            if($users[$i]['street'] != ''){
                array_push($location, $users[$i]['street']);
            }
            if($users[$i]['building'] != ''){
                array_push($location, $users[$i]['building']);
            }
            if($users[$i]['apartment'] != ''){
                array_push($location, $users[$i]['apartment']);
            }
            $phones = [];
            if($users[$i]['homePhone'] != ''){
                array_push($phones, $users[$i]['homePhone']);
            }
            if($users[$i]['mobilePhone'] != ''){
                array_push($phones, $users[$i]['mobilePhone']);
            }
            $data[$users[$i]['userId']] = [
                'id' => $users[$i]['userId'],
                'Email' => $users[$i]['userEmail'],
                'Ролі' => $users[$i]['userRoles'],
                //'Статус' => User::STATUS_MSG[$users[$i]['userStatus']]],
                'Статус' => $users[$i]['userStatus'],
                'ПІБ' => implode(' ', $name),
                //'Прізвище' => $users[$i]['userLastName'],
                //'Ім\'я' => $users[$i]['userFirstName'],
                //'По батькові' => $users[$i]['userPatronymic'],
                'EIC' => $users[$i]['eicCode'],
                'Адреса' => implode(', ', $location),
                //'Поштовий індекс' => $users[$i]['postcode'],
                //'Місто' => $users[$i]['city'],
                //'Вулиця' => $users[$i]['street'],
                //'Будинок' => $users[$i]['building'],
                //'Квартира' => $users[$i]['apartment'],
                'Особовий Рахунок' => $users[$i]['personalAccount'],
                'Телефони' => implode(', ', $phones),
                //'Домашній телефон' => $users[$i]['homePhone'],
                //'Мобільний телефон' => $users[$i]['mobilePhone'],
                //'Документи' => count($users[$i]->getDocuments()->getValues()),
                'Документи' => [],
                '' => '',
            ];
            if($users[$i]['documentId']){
                array_push($documents[$users[$i]['userId']], [
                    'documentId' => $users[$i]['documentId'],
                    'documentName' => '' . (count($documents[$users[$i]['userId']]) + 1) . '.' . $users[$i]['fileExtension'],
                ]);
            }
        }
        //dd($documents);

        $tableData = [];
        foreach($data as $key => $obj){
            if(isset($documents[$key])){
                $obj['Документи'] = $documents[$key];
                $data[$key] = $obj;
            }
            array_push($tableData, $obj);
        }
        //dd($tableData);

        $output = [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $tableData,
        ];

        return new JsonResponse($output);
    }

}
