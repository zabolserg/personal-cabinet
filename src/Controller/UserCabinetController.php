<?php

namespace App\Controller;

use App\Controller\Traits\AdminThemeUserCabinetDataTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Entity\User;

/**
 * Class UserCabinetController
 * @package App\Controller
 */
class UserCabinetController extends AbstractController
{
    use AdminThemeUserCabinetDataTrait;

    /**
     * @Route("/account", name="user_cabinet_main_page", methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function index(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if(in_array('ROLE_ADMIN', $loggedUser->getRoles())){
            return $this->redirectToRoute('admin_cabinet_users_page');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Головна';
        $this->data['asideMenu'][0]['itemClassActiveName'] = 'menu-item-active';

        return $this->render('pages/page_main.html.twig', $this->data);
    }

    /**
     * @Route("/account/accruals_payments", name="user_cabinet_accruals_payments_page",  methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function accrualsPayments(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if(in_array('ROLE_ADMIN', $loggedUser->getRoles())){
            return $this->redirectToRoute('admin_cabinet_users_page');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Нарахування та оплати';
        $this->data['asideMenu'][1]['itemClassActiveName'] = 'menu-item-active';
        return $this->render('pages/page_accruals_payments.html.twig', $this->data);
    }

    /**
     * @Route("/account/meter_reading", name="user_cabinet_meter_reading_page",  methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function meterReading(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if(in_array('ROLE_ADMIN', $loggedUser->getRoles())){
            return $this->redirectToRoute('admin_cabinet_users_page');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Показання лічильника';
        $this->data['asideMenu'][2]['itemClassActiveName'] = 'menu-item-active';
        return $this->render('pages/page_meter_reading.html.twig', $this->data);
    }

    /**
     * @Route("/account/profile", name="user_cabinet_profile_page",  methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function userProfile(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if(in_array('ROLE_ADMIN', $loggedUser->getRoles())){
            return $this->redirectToRoute('admin_cabinet_users_page');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Мої дані';
        $this->data['asideMenu'][3]['itemClassActiveName'] = 'menu-item-active';
        return $this->render('pages/page_profile.html.twig', $this->data);
    }

}
