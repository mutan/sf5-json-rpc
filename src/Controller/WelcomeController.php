<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class WelcomeController extends AbstractController
{
    /**
     * @Route("/", name="welcome")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->connect();
        $connected = $em->getConnection()->isConnected();

        /**
         * Set mapping in File/Settings/PHP/Servers
         * Add breakpoint to the next line
         */
        dump($connected);
        phpinfo();
        die;

        return $this->render(
            'welcome/index.html.twig',
            [
                'controller_name' => 'WelcomeController',
            ]
        );
    }
}
