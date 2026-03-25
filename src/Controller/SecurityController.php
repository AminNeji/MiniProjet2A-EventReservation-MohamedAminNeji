<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_event_index');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $username = trim($request->request->get('username', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm_password', '');

            if (empty($username) || empty($password)) {
                $error = 'Tous les champs sont obligatoires.';
            } elseif ($password !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } elseif (strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif ($userRepository->findOneBy(['username' => $username])) {
                $error = 'Ce nom d\'utilisateur est déjà utilisé.';
            } else {
                $user = new User();
                $user->setUsername($username);
                $user->setEmail($email ?: null);
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
        ]);
    }
}
