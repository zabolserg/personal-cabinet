<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 * @package App\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default")
     * @return Response
     */
    public function index(): Response
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
        else{
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/admin", name="admin")
     * @return Response
     */
    public function admin(): Response
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
        else{
            return $this->redirectToRoute('app_login');
        }
    }

}
