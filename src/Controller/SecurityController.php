<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\Mapping\MappingException;

class SecurityController extends AbstractController
{
    public const URL = 'http://localhost';
    //public const URL = 'https://gg.bbrok.com';
    //public const EMAIL = 'zabolserg@gmail.com';
    public const EMAIL = 'noreply@gg.bbrok.com';

    /**
     * @Route("/login", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser) {
            if(in_array('ROLE_ADMIN', $loggedUser->getRoles())){
                return $this->redirectToRoute('admin_cabinet_users_page');
            }
            else{
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/page_login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'pageTitle' => 'Вхід у особистий кабінет'
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/register", name="app_register_page", methods={"GET"})
     * @return Response
     */
    public function registerPage(): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser) {
            if(in_array('ROLE_ADMIN', $loggedUser->getRoles())){
                return $this->redirectToRoute('admin_cabinet_users_page');
            }
            else{
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }

        $data =['pageTitle' => 'Реєстрація нового користувача'];
        return $this->render('security/page_register.html.twig', $data);
    }

    /**
     * @Route("/register", name="app_register",  methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param MailerInterface $mailer
     *
     * @return JsonResponse
     */
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        MailerInterface $mailer
    ) : JsonResponse {
        $requestParams = $request->request->all();

        $email = $requestParams['email'];
        $userPassword = $requestParams['userPassword'];
        $userPasswordConfirm = $requestParams['userPasswordConfirm'];
        $firstName = $requestParams['firstName'];
        $lastName = $requestParams['lastName'];
        $patronymic = $requestParams['patronymic'];
        $eicCode = $requestParams['eicCode'];
        $postcode = (int)$requestParams['postcode'];
        $city = $requestParams['city'];
        $street = $requestParams['street'];
        $building = $requestParams['building'];
        $apartment = $requestParams['apartment'];

        if(!$email){
            return new JsonResponse(['message' => 'Помилка. Не задано email.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }
        elseif(strlen($email) <= 3 || !preg_match('/@/', $email)){
            return new JsonResponse(['message' => 'Помилка! Некоректний email.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        if(!$userPassword || !$userPasswordConfirm){
            return new JsonResponse(['message' => 'Помилка. Не задано пароль.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }
        elseif($userPassword && $userPasswordConfirm && $userPassword != $userPasswordConfirm){
            return new JsonResponse(['message' => 'Помилка! Підтвердження паролю не співпадає з введеним паролем.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        if($email && $userRepository->findOneBy(['email' => $email])){
            return new JsonResponse(['message' => 'Помилка! Користувач з є-мейлом ' . $email . ' вже існує', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        if(!$eicCode){
            return new JsonResponse(['message' => 'Помилка. Не задано EIC.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }
        elseif($eicCode && $userRepository->findOneBy(['eicCode' => $eicCode])){
            return new JsonResponse(['message' => 'Помилка! Користувач з EIC ' . $eicCode . ' вже існує', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        $user = new User();
        $user->setEmail($email)
            ->setUserPassword($userPassword)
            ->setRoles(['ROLE_USER'])
            ->setStatus(2)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPatronymic($patronymic)
            ->setEicCode($eicCode)
            ->setPostcode($postcode)
            ->setCity($city)
            ->setStreet($street)
            ->setBuilding($building)
            ->setApartment($apartment)
            ->setTimeCreate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')))
            ->setTimeUpdate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')));
        $password = $passwordEncoder->encodePassword($user, $user->getUserPassword());
        $user->setPassword($password);

        $entityManager->persist($user);
        $entityManager->flush();
        $entityManager->clear();

        $oEmail = (new Email())
            ->from(self::EMAIL)
            ->to($user->getEmail())
            ->subject('Геліос Газ. Реєстрація користувача.')
            ->text('Доброго дня! Вітаємо! Ви успішно зареєструвались у особовому кабінеті користувача!');

        try {
            $mailer->send($oEmail);
        } catch(TransportExceptionInterface $e){
            return new JsonResponse(['message' => $e->getMessage(), 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        return new JsonResponse(['message' => 'Реєстрація успішна!', 'code' => JsonResponse::HTTP_CREATED]);
    }

    /**
     * @Route("/recovery", name="app_recovery_page", methods={"GET"})
     *
     * @return Response
     */
    public function recoveryPage(): Response
    {
        $loggedUser = $this->getUser();
        if ($loggedUser) {
            if(in_array('ROLE_ADMIN', $loggedUser->getRoles())){
                return $this->redirectToRoute('admin_cabinet_users_page');
            }
            else{
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }

        $data =['pageTitle' => 'Відновлення паролю користувача'];
        return $this->render('security/page_recovery.html.twig', $data);
    }

    /**
     * @Route("/recovery", name="app_recovery",  methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param MailerInterface $mailer
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return JsonResponse
     */
    public function recovery(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ) : JsonResponse
    {
        $requestParams = $request->request->all();

        $email = $requestParams['email'];
        $action = $requestParams['action'];

        $user = $userRepository->findOneBy(['email' => $email]);
        if(!$user){
            return new JsonResponse(['message' => 'Помилка. Користувача з таким email-м не існує.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
        }

        if($action == 'send-email'){
            $url = self::URL . $urlGenerator->generate('app_recovery_page') . '?code=' .
                sha1($user->getEmail() . $user->getPassword());
            $oEmail = (new Email())
                ->from(self::EMAIL)
                ->to($user->getEmail())
                ->subject('Геліос Газ. Відновлення пароля.')
                //->text('<p>Перейдіть по ссилці:</p><a href="' . $url . '">' . $url . '</a>')
                ->html('<p>Перейдіть по ссилці:</p><a href="' . $url . '">' . $url . '</a>');

            try {
                $mailer->send($oEmail);
            } catch(TransportExceptionInterface $e){
                return new JsonResponse(['message' => $e->getMessage(), 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
            }

            return new JsonResponse([
                'message' => 'На вказаний e-mail був відправлений лист з подальшими інструкціями по відновленню пароля.',
                'code' => JsonResponse::HTTP_OK]);
        }

        if($action == 'change-password'){
            $newPassword = $requestParams['newPassword'];
            $newPasswordConfirm = $requestParams['newPasswordConfirm'];
            $code = $requestParams['code'];

            if($code != sha1($user->getEmail() . $user->getPassword()) ||
                !$newPassword || !$newPasswordConfirm || $newPassword != $newPasswordConfirm){
                return new JsonResponse(['message' => 'Помилка. Не вдалося змінити пароль.', 'code' => JsonResponse::HTTP_NOT_ACCEPTABLE]);
            }

            $password = $passwordEncoder->encodePassword($user, $newPassword);
            $user->setPassword($password);
            $user->setTimeUpdate(new \DateTime('now', new \DateTimeZone('Europe/Kiev')));

            $entityManager->persist($user);
            $entityManager->flush();
            $entityManager->clear();

            return new JsonResponse(['message' => 'Пароль успішно змінено', 'code' => JsonResponse::HTTP_OK]);
        }

        return new JsonResponse([]);
    }

}
