<?php

namespace App\Controller;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/admin')]
class AdminSecurityController extends AbstractController
{
    #[Route('/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'admin_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - intercepted by the firewall.');
    }

    // GET /admin/setup
    #[Route('/setup', name: 'admin_setup')]
    public function setup(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if ($em->getRepository(Admin::class)->count([]) > 0) {
            return $this->render('admin/setup.html.twig', [
                'created' => false,
            ], new Response('', Response::HTTP_FORBIDDEN));
        }

        $admin = new Admin();
        $admin->setUsername('admin');
        $admin->setPasswordHash($hasher->hashPassword($admin, 'Admin@1234'));
        $em->persist($admin);
        $em->flush();

        return $this->render('admin/setup.html.twig', [
            'created' => true,
        ]);
    }
}
