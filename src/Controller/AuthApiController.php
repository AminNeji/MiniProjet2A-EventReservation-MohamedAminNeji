<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {}

    // Register
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $email    = trim($data['email'] ?? '');

        if (empty($username) || empty($password)) {
            return $this->json(['error' => 'username and password required'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userRepository->findOneBy(['username' => $username])) {
            return $this->json(['error' => 'Username already taken'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email ?: null);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => (string) $user->getId(), 'username' => $user->getUsername()],
        ], Response::HTTP_CREATED);
    }

    // Login
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user || !$hasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success'  => true,
            'token'    => $token,
            'user'     => ['id' => (string) $user->getId(), 'username' => $user->getUsername()],
        ]);
    }

    //Current user
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id'       => (string) $user->getId(),
            'username' => $user->getUsername(),
            'email'    => $user->getEmail(),
            'roles'    => $user->getRoles(),
        ]);
    }

    //Passkey: Registration options
    #[Route('/passkey/register/options', name: 'api_passkey_register_options', methods: ['POST'])]
    public function passkeyRegisterOptions(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');

        if (empty($username)) {
            return $this->json(['error' => 'username required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $challenge = base64_encode(random_bytes(32));

        
        $request->getSession()->set('webauthn_register_challenge', $challenge);
        $request->getSession()->set('webauthn_register_username', $username);

        return $this->json([
            'challenge' => $challenge,
            'rp'        => [
                'name' => $_ENV['WEBAUTHN_RP_NAME'] ?? 'EventReservation',
                'id'   => $_ENV['APP_DOMAIN'] ?? 'localhost',
            ],
            'user' => [
                'id'          => base64_encode($user->getId()->toBinary()),
                'name'        => $user->getUsername(),
                'displayName' => $user->getDisplayName(),
            ],
            'pubKeyCredParams' => [
                ['alg' => -7,   'type' => 'public-key'],
                ['alg' => -257, 'type' => 'public-key'], 
            ],
            'timeout'               => 60000,
            'attestation'           => 'none',
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey'      => 'preferred',
            ],
        ]);
    }

    //Passkey: Registration verify
    #[Route('/passkey/register/verify', name: 'api_passkey_register_verify', methods: ['POST'])]
    public function passkeyRegisterVerify(
        Request $request,
        WebauthnCredentialRepository $credRepo
    ): JsonResponse {
        $data       = json_decode($request->getContent(), true);
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Credential missing'], Response::HTTP_BAD_REQUEST);
        }

        $username = $request->getSession()->get('webauthn_register_username');
        $user     = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            return $this->json(['error' => 'Session expired or user not found'], Response::HTTP_BAD_REQUEST);
        }

        // Store credential
        $credentialId   = $credential['id'];
        $credentialData = json_encode($credential);

        $credRepo->saveCredential($user, $credentialId, $credentialData, 'Passkey');

        $request->getSession()->remove('webauthn_register_challenge');
        $request->getSession()->remove('webauthn_register_username');

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => (string) $user->getId(), 'username' => $user->getUsername()],
        ]);
    }

    //Passkey: Login options 
    #[Route('/passkey/login/options', name: 'api_passkey_login_options', methods: ['POST'])]
    public function passkeyLoginOptions(Request $request): JsonResponse
    {
        $challenge = base64_encode(random_bytes(32));
        $request->getSession()->set('webauthn_login_challenge', $challenge);

        return $this->json([
            'challenge'        => $challenge,
            'timeout'          => 60000,
            'rpId'             => $_ENV['APP_DOMAIN'] ?? 'localhost',
            'userVerification' => 'preferred',
            'allowCredentials' => [], 
        ]);
    }

    // Passkey: Login verify 
    #[Route('/passkey/login/verify', name: 'api_passkey_login_verify', methods: ['POST'])]
    public function passkeyLoginVerify(
        Request $request,
        WebauthnCredentialRepository $credRepo
    ): JsonResponse {
        $data       = json_decode($request->getContent(), true);
        $credential = $data['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Credential missing'], Response::HTTP_BAD_REQUEST);
        }

        $credentialId = $credential['id'] ?? null;
        $stored       = $credRepo->findByCredentialId($credentialId);

        if (!$stored) {
            return $this->json(['error' => 'Passkey not found'], Response::HTTP_UNAUTHORIZED);
        }

        $stored->touch();
        $this->em->flush();

        $user  = $stored->getUser();
        $token = $this->jwtManager->create($user);

        $request->getSession()->remove('webauthn_login_challenge');

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => (string) $user->getId(), 'username' => $user->getUsername()],
        ]);
    }
}
