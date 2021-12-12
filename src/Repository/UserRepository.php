<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
	{
	public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
		{
		parent::__construct($registry, User::class);
		$this->em = $em;
		}

	/**
	 * Used to upgrade (rehash) the user's password automatically over time.
	 */
	public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
		{
		if (!$user instanceof User)
			{
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
			}

		$user->setPassword($newHashedPassword);
		$this->_em->persist($user);
		$this->_em->flush();
		}

	// Find User by id
	public function findUserById($id): ?User
		{
		return $this->createQueryBuilder('u')
			->andWhere('u.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getOneOrNullResult();
		}

	// Find User by username
	public function findUserByUsername($username): ?User
		{
		return $this->createQueryBuilder('u')
			->andWhere('u.username = :username')
			->setParameter('username', $username)
			->getQuery()
			->getOneOrNullResult();
		}

	// Find User by email
	public function findUserByEmail($email): ?User
		{
		return $this->createQueryBuilder('u')
			->andWhere('u.email = :email')
			->setParameter('email', $email)
			->getQuery()
			->getOneOrNullResult();
		}

	// Update User's Api-KEY
	public function updateUsersApiKey($apiKey, $userId)
		{
		$query = $this->em->createQueryBuilder()
			->update(User::class, 'u')
			->set('u.apiKey', ':apiKey')
			->where('u.id = :userId')
			->setParameter('apiKey', $apiKey)
			->setParameter('userId', $userId)
			->getQuery();

		return $query->execute();
		}

	// Find User with Api key
	public function findUserByApiKey($apiKey)
		{
		return $this->createQueryBuilder('u')
			->andWhere('u.userApiKey = :apiKey')
			->setParameter('apiKey', $apiKey)
			->getQuery()
			->getOneOrNullResult();
		}
	// /**
	//  * @return User[] Returns an array of User objects
	//  */
	/*
	public function findByExampleField($value)
	{
		return $this->createQueryBuilder('u')
			->andWhere('u.exampleField = :val')
			->setParameter('val', $value)
			->orderBy('u.id', 'ASC')
			->setMaxResults(10)
			->getQuery()
			->getResult()
		;
	}
	*/

	/*
	public function findOneBySomeField($value): ?User
	{
		return $this->createQueryBuilder('u')
			->andWhere('u.exampleField = :val')
			->setParameter('val', $value)
			->getQuery()
			->getOneOrNullResult()
		;
	}
	*/
	}
