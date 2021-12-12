<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
	{
	#[Route('/test', name: 'test')]
	public function test(): Response
		{
		return $this->render('user/index.html.twig', [
			'controller_name' => 'UserController',
		]);
		}

	#[Route('/api/login', name: 'login', methods: 'POST')]
	public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
		{
		$response = new Response();

		$data = $request->getContent();
		$data = json_decode($data, true, 512);

//		$email = $data['email']; //For Api
		$email = $request->get('email'); //For postman tests

		if ($userRepository->findUserByEmail($email) !== null)
			{
			$user = $userRepository->findUserByEmail($email);

//			$plaintextPassword = $data['password']; // For Api
			$plaintextPassword = $request->get('password'); // For postman tests

			// Compare hashed password with plaintext password
			$compare = $passwordHasher->isPasswordValid($user, $plaintextPassword);
			if ($compare)
				{

				// User Api Key
				$userApiKey = $user->getApiKey();
				if (!$userApiKey)
					{

					// Generate User's new Api-key
					$userId           = $user->getId();
					$characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
					$charactersLength = strlen($characters);
					$randomString     = '';
					for ($i = 0; $i < $charactersLength; $i++)
						{
						$randomString .= $characters[rand(0, $charactersLength - 1)];
						}
					$apiKey = $randomString . strval($user->getId());
					$userRepository->updateUsersApiKey($apiKey, $userId);
					}

				return $response->setContent(json_encode([
					'userApiKey' => $user->getApiKey()
				]));
				}

			return $response->setContent(json_encode([
				'passwordError' => 'Wrong password!'
			]));
			}

		return $response->setContent(json_encode([
			'emailError' => 'There is no user with provided email!'
		]));
		}

	#[Route('/api/register', name: 'api_register', methods: 'POST')]
	public function register(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
		{
		$response      = new Response();
		$user          = new User();
		$data          = $request->getContent();
		$data          = json_decode($data, true);
		$emailError    = '';
		$usernameError = '';
		$error         = false;

		// For API
//		$email = $data['email'];
//		$username = $data['username'];
//		$fname = $data['fname'];
//		$lname = $data['lname'];
//		$password = $data['password'];

		// For PostMan Tests
		$email = $request->get('email');
		$username = $request->get('username');
		$fname = $request->get('fname');
		$lname = $request->get('lname');
		$password = $request->get('password');

		//ToDo do not hardcode these variables
		$posX = 'posX';
		$posY = 'posY';
		$avatar = 'default';

		// Email exist checker
		if ($userRepository->findUserByEmail($email))
			{
			$error      = true;
			$emailError = 'The user with this email is already registered!';
			}

		// Username exist checker
		if ($userRepository->findUserByUsername($username))
			{
			$error         = true;
			$usernameError = 'The user with this username is already registered!';
			}

		$response->setContent(json_encode([
			'emailError'    => $emailError,
			'usernameError' => $usernameError
		]));

		if ($error == false)
			{
			$user->setEmail($email);
			$user->setUsername($username);
			$user->setFirstName($fname);
			$user->setLastName($lname);
			$user->setPosX($posX);
			$user->setPosY($posY);
			$user->setAvatar($avatar);

			// Password Hashing
			$plaintextPassword = $password;
			$hashedPassword    = $passwordHasher->hashPassword(
				$user,
				$plaintextPassword
			);
			$user->setPassword($hashedPassword);

			$em->persist($user);
			$em->flush();

			return $this->json($user);
			}

		return $response;
		}
	}
