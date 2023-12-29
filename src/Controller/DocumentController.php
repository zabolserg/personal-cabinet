<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Class DocumentController
 * @package App\Controller
 */
class DocumentController extends AbstractController
{
    public const FILE_DOWNLOAD_PATH = '/storage/downloads/templates/';
    public const FILE_UPLOAD_PATH = '/storage/uploads/documents/';
    public const FILE_NAME = 'accession_application.pdf';

    /**
     * @Route("/document/get/file", name="document_get_file")
     * @param Request $request
     * @param DocumentRepository $documentRepository
     * @return BinaryFileResponse
     */
    public function documentGetFile(Request $request, DocumentRepository $documentRepository): BinaryFileResponse
    {
        $loggedUser = $this->getUser();
        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return new JsonResponse(['message' => 'Помилка! Недостатньо прав!', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
            }
        }

        $requestParams = $request->request->all();

        $id = $requestParams['documentId'];
        $name = $requestParams['documentName'];

        $document = $documentRepository->find($id);

        $file = $_SERVER['DOCUMENT_ROOT'] . self::FILE_UPLOAD_PATH . $document->getFilePath();
        $response = new BinaryFileResponse($file);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $name
        );
        $response->headers->set('Content-Type',$document->getFileMimeType());
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }

    /**
     * @Route("/document/download", name="document_download")
     */
    public function documentDownLoad()
    {
        $user = $this->getUser();

        $fullName = [];
        if($user->getLastName() != ''){
            array_push($fullName, $user->getLastName());
        }
        if($user->getFirstName() != ''){
            array_push($fullName, $user->getFirstName());
        }
        if($user->getPatronymic() != ''){
            array_push($fullName, $user->getPatronymic());
        }
        $phones = [];
        if($user->getHomePhone() != ''){
            array_push($phones, $user->getHomePhone());
        }
        if($user->getMobilePhone() != ''){
            array_push($phones, $user->getMobilePhone());
        }
        $email = $user->getEmail();
        $location = [];
        if($user->getPostcode() != 0 && $user->getPostcode() != ''){
            array_push($location, $user->getPostcode());
        }
        if($user->getCity() != ''){
            array_push($location, $user->getCity());
        }
        if($user->getStreet() != ''){
            array_push($location, $user->getStreet());
        }
        if($user->getBuilding() != ''){
            array_push($location, $user->getBuilding());
        }
        if($user->getApartment() != ''){
            array_push($location, $user->getApartment());
        }
        $eicCode = $user->getEicCode();

        $pdfOptions = new Options();

        //$pdfOptions->set('defaultFont', 'DejaVu Serif');
        $pdfOptions->set('defaultFont', 'DejaVu Sans');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('documents/accession_application_document.html.twig', [
            'img' => 'http://' . $_SERVER['HTTP_HOST'] . '/assets/images/logo-440x100.jpg',
            'fullName' => implode(' ', $fullName),
            'phone' => implode(', ', $phones),
            'email' => $email,
            'location' => implode(' ', $location),
            'eicCode' => $eicCode
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("Accession Application.pdf", [
            "Attachment" => true
        ]);
    }

    /**
     * @Route("/document/upload", name="document_upload")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param DocumentRepository $documentRepository
     * @return JsonResponse
     */
    public function documentUpload(
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository
    ): JsonResponse {
        $document = new Document();

        $files = $request->files->get('files');

        if(count($files) == 0){
            return new JsonResponse(['message' => 'Помилка. Не передано файли.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        $user = $this->getUser();
        $userId = $user->getId();
        $message = '';
        for($i=0; $i<count($files); $i++){
            $hash = md5_file($files[$i]->getPathname());
            $hashPath = substr($hash, 0, 2).'/'.substr($hash, 2, 2).'/'.substr($hash, 4);
            $filePath = $hashPath;
            $fileSize = $files[$i]->getSize();
            $originalFileName = $files[$i]->getClientOriginalName();
            $originalFileExtension = $files[$i]->getClientOriginalExtension();
            $fileMimeType = $files[$i]->getClientMimeType();
            $fileName = $originalFileName;
            $fileExtension = $originalFileExtension;

            $path = $_SERVER['DOCUMENT_ROOT'] . '/' . self::FILE_UPLOAD_PATH . '/' . explode('/', $hashPath)[0];
            if(!file_exists($path)){
                if(!mkdir($path)){
                    $message .= 'Помилка. Файл ' . $originalFileName . '. Не вдалося створити директорію(1).<br>';
                }
                else{
                    if(!chmod($path, 0777)){
                        $message .= 'Помилка. Файл ' . $originalFileName . '. Не вдалося змінити права доступу до директорії(1).<br>';
                    }
                }
            }

            $path .= '/' . explode('/', $hashPath)[1];
            if(!file_exists($path)){
                if(!mkdir($path)){
                    $message .= 'Помилка. Файл ' . $originalFileName . '. Не вдалося створити директорію(2).<br>';
                }
                else{
                    if(!chmod($path, 0777)){
                        $message .= 'Помилка. Файл ' . $originalFileName . '. Не вдалося змінити права доступу до директорії(2).<br>';
                    }
                }
            }

            //$path .= '/' . explode('/', $hashPath)[2];
            if(!file_exists($path . '/' . explode('/', $hashPath)[2])){
                try {
                    //$files[$i]->move($path, $fileName);
                    $files[$i]->move($path, explode('/', $hashPath)[2]);
                }
                catch(FileException $e){
                    return new JsonResponse(['message' => $e->getMessage(), 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
                }
                if(!chmod($path . '/' . explode('/', $hashPath)[2], 0777)){
                    $message .= 'Помилка. Файл ' . $originalFileName . '. Не вдалося змінити права доступу до файлу(3).<br>';
                }
            }

            if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . self::FILE_UPLOAD_PATH . '/' . $hashPath)){
                $existingDocument = $documentRepository->findOneBy(['filePath' => $filePath]);
                if($existingDocument){
                    $document = $existingDocument;
                    $document->setFileName($fileName)
                        ->setTimeUpdate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')));
                }
                else{
                    $document
                        //->setUserId($userId)
                        ->setUser($user)
                        ->setFileName($fileName)
                        ->setFileExtension($fileExtension)
                        ->setFilePath($filePath)
                        ->setFileSize($fileSize)
                        ->setFileMimeType($fileMimeType)
                        ->setStatus(0)
                        ->setTimeCreate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')))
                        ->setTimeUpdate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')));
                }

                $entityManager->persist($document);
                $entityManager->flush();
                $entityManager->clear();
            }
        }

        return new JsonResponse(['message' => 'Документ успішно завантажено!', 'code' => JsonResponse::HTTP_CREATED]);
    }

    /**
     * @Route("/documents/ssp", name="document_ssp", methods={"POST"})
     * @param Request $request
     * @param DocumentRepository $documentRepository
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    public function getDocuments(
        Request $request,
        DocumentRepository $documentRepository,
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

        //Total documents number
        $query = $entityManager->createQuery(
            "SELECT COUNT(d.id) FROM App\Entity\Document d"
        );
        $results = $query->getResult();
        $recordsTotal = $results[0][1];

        //Filtered documents number
        if($searchValue != ''){
            $query = $entityManager->createQuery(
                "SELECT COUNT(d.id) 
                FROM App\Entity\Document d 
                WHERE d.id LIKE :value OR d.userId LIKE :value OR d.fileName LIKE :value OR d.fileSize LIKE :value OR d.fileMimeType LIKE :value OR 
                    d.timeCreate LIKE :value OR d.timeUpdate LIKE :value 
                ORDER BY d.id ASC"
            );
            $query->setParameter('value', '%' . $searchValue . '%');
            $results = $query->getResult();
            $recordsFiltered = $results[0][1];
        }
        else{
            $recordsFiltered = $recordsTotal;
        }

        //Paginated, filtered, ordered documents
        $where = '';
        if($searchValue != ''){
            $where = 'WHERE d.id LIKE :value OR d.userId LIKE :value OR d.fileName LIKE :value OR d.fileSize LIKE :value OR d.fileMimeType LIKE :value OR 
                d.timeCreate LIKE :value OR d.timeUpdate LIKE :value';
        }

        $orderBy = '';
        if(isset($orderDirection) && isset($orderColumn)){
            switch($orderColumn){
                case 0:
                    $orderBy = 'ORDER BY d.id ' . $orderDirection;
                    break;
                case 1:
                    $orderBy = 'ORDER BY d.userId ' . $orderDirection;
                    break;
                case 2:
                    $orderBy = 'ORDER BY d.fileName ' . $orderDirection;
                    break;
                case 3:
                    $orderBy = 'ORDER BY d.fileSize ' . $orderDirection;
                    break;
                case 4:
                    $orderBy = 'ORDER BY d.fileMimeType ' . $orderDirection;
                    break;
                case 5:
                    $orderBy = 'ORDER BY d.timeCreate ' . $orderDirection;
                    break;
                case 6:
                    $orderBy = 'ORDER BY d.timeUpdate ' . $orderDirection;
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
            "SELECT d.id AS documentId, d.userId AS userId, d.fileName AS fileName, d.fileSize AS fileSize, 
                d.fileMimeType AS fileMimeType, d.timeCreate AS timeCreate, d.timeUpdate AS timeUpdate 
            FROM App\Entity\Document d " .
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
        $documents = $results;

        $tableData = [];
        for($i=0; $i<count($documents); $i++){
            $arr = [
                'id' => $documents[$i]['documentId'],
                'user_id' => $documents[$i]['userId'],
                //'user_id' => $documents[$i]->getUser()->getId(),
                'Назва файлу' => $documents[$i]['fileName'],
                'Розмір файлу' => $documents[$i]['fileSize'],
                'Тип файлу' => $documents[$i]['fileMimeType'],
                'Завантажено' => $documents[$i]['timeCreate']->format('Y-m-d H:i:s'),
                'Перезавантажено' => $documents[$i]['timeUpdate']->format('Y-m-d H:i:s'),
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