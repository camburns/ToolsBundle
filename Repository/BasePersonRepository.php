<?php

namespace VisageFour\Bundle\ToolsBundle\Repository;
use App\VisageFour\Bundle\ToolsBundle\Exceptions\PersonNotFound;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use VisageFour\Bundle\ToolsBundle\Entity\BasePerson;

/**
 * BasePersonRepository
 *
 */
class BasePersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, $entityClassName)
    {
        // note: you must create a class that overrides this and passes in the correct $entityClassName parameter.
        // the commented out section below cannot be used:
//        parent::__construct($registry, BasePerson::class);

        parent::__construct($registry, $entityClassName);
    }
    public function doesPersonExist($email)
    {
        $person = $this->findOneByEmailCanonical($email, false);

        if (!empty($person)) {
            return true;
        }

        return false;
    }

    /**
     * Canonicalize email
     */
    public function findOneByEmailCanonical ($email, $throwExceptionIfNotFound = true) {
        $emailCanon = BasePerson::canonicalizeEmail($email);

        $result = $this->findOneBy(array(
            'emailCanonical'        => $emailCanon
        ));

        if (empty($result) && $throwExceptionIfNotFound){
            throw new PersonNotFound($email);
        }
        return $result;
    }
}