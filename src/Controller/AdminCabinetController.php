<?php

namespace App\Controller;

use App\Controller\Traits\AdminThemeAdminCabinetDataTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class AdminCabinetController
 * @package App\Controller
 */
class AdminCabinetController extends AbstractController
{
    use AdminThemeAdminCabinetDataTrait;

    /**
     * @Route("/admin/users", name="admin_cabinet_users_page", methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function users(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }
        else {
            return $this->redirectToRoute('default');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Користувачі';
        $this->data['asideMenu'][0]['itemClassActiveName'] = 'menu-item-active';

        return $this->render('pages/page_admin_users.html.twig', $this->data);
    }

    /**
     * @Route("/admin/documents", name="admin_cabinet_documents_page", methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function documents(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }
        else {
            return $this->redirectToRoute('default');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Документи';
        $this->data['asideMenu'][1]['itemClassActiveName'] = 'menu-item-active';

        return $this->render('pages/page_admin_documents.html.twig', $this->data);
    }

    /**
     * @Route("/admin/meters", name="admin_cabinet_meters_page", methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function meters(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }
        else {
            return $this->redirectToRoute('default');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Лічильники';
        $this->data['asideMenu'][2]['itemClassActiveName'] = 'menu-item-active';

        return $this->render('pages/page_admin_meters.html.twig', $this->data);
    }

    /**
     * @Route("/admin/meters/readings", name="admin_cabinet_meters_readings_page", methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function metersReadings(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }
        else {
            return $this->redirectToRoute('default');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Показання лічильників';
        $this->data['asideMenu'][3]['itemClassActiveName'] = 'menu-item-active';

        return $this->render('pages/page_admin_meters_readings.html.twig', $this->data);
    }

    /**
     * @Route("/admin/profile", name="admin_cabinet_profile_page",  methods={"GET"})
     * @param UserRepository $userRepository
     * @return Response
     */
    public function userProfile(UserRepository $userRepository): Response
    {
        $loggedUser = $this->getUser();

        if ($loggedUser) {
            if (!in_array('ROLE_ADMIN', $loggedUser->getRoles())) {
                return $this->redirectToRoute('user_cabinet_main_page');
            }
        }
        else {
            return $this->redirectToRoute('default');
        }

        $this->data['user'] = $this->getUserInfo($loggedUser);

        $this->data['pageTitle'] = 'Мої дані';
        $this->data['asideMenu'][4]['itemClassActiveName'] = 'menu-item-active';
        return $this->render('pages/page_admin_profile.html.twig', $this->data);
    }


}