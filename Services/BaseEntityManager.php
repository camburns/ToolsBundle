<?php

namespace VisageFour\Bundle\ToolsBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use VisageFour\Bundle\ToolsBundle\Interfaces\BaseEntityInterface;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use VisageFour\Bundle\ToolsBundle\Entity\BaseEntity;

abstract class BaseEntityManager
{
    /*
                === USAGE BELOW! ===
    /**
     * RegisteredCodesManager constructor.
     * @param EntityManager $em
     * @param $class
     * @param EventDispatcherInterface $dispatcher
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface $logger

    public function __construct(EntityManager $em, $class, EventDispatcherInterface $dispatcher, LoggerInterface $logger) {
        parent::__construct($em, $class, $dispatcher, $loggerw);
        // custom config
        // ...
    }

    public function customCreateNew ($persist = true) {
        // instantiate
        $newObj = parent::createNew(false);

        // configure
        // ...

        if ($persist) {
            $this->persist($newObj);
        }

        // log
        $persistStatus = ($persist) ? 'true' : 'false';
        $string = 'Created a new '. $this->class .' obj. Persist ('. $persistStatus .').';
        // custom notes
        $newObj =. ' with question caption:  "'.
            $question->getQuestionCaption() .'", for event series: "'.
            $eventSeries->getName() .'"'.

        $this->logString ($string);

        return $newObj;
    }

    ===== SERVICE DEFINITION in YML =====
        tools_bundle.event_series_manager:
        class: Twencha\Bundle\EventRegistrationBundle\Services\EventSeriesManager
        arguments:
            - "@doctrine.orm.entity_manager"
            - "EventSeriesBundle:EventSeries"
            - "@event_dispatcher"
            - "@logger"
    //*/
    
    protected $em;
    protected $dispatcher;
    protected $eventDispatcher;
    protected $logger;
    protected $repo;

    protected $class;

    // used to output result when using Doctrine fixtures > set to true when in LoadUserData.php (or other fixtures file)
    protected $outputLogToScreen;

    /**
     * BaseEntityManager constructor.
     * @param EntityManager $em
     * @param $class
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManagerInterface $em, $class, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger) {
        $this->em               = $em;
        $this->repo             = $this->em->getRepository($class);
//        dump($this->repo, $class);
        $this->dispatcher       = $eventDispatcher;     // this should be phased out as a variable
        $this->eventDispatcher  = $eventDispatcher;
        $this->logger           = $logger;

        $metadata               = $this->em->getClassMetadata($class);
        $this->class            = $metadata->getName();
    }

    /**
     * @param bool $persist
     * @param bool $logObjCreation
     * @param bool $provideObjValsInLog
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     *
     * Create a new obj based
     *
     * You must override this if you want a factory method that sets values.
     * remember to add $this->lobObjCreation() to overriden createNew.
     */
    public function createNew ($persist = true, $logObjCreation = true) {
        // instantiate
        /** @var BaseEntityInterface $obj */
        $obj = new $this->class();

        // persist?
        if ($persist) {
            $this->em->persist($obj);
        }

        if ($logObjCreation) {
            $this->logObjCreation($obj, false);
        }

        return $obj;
    }

    /**
     * @param $obj
     * @param $persist
     * @param bool $provideObjValsInLog
     *
     * Create a log for the objs creation
     */
    protected function logObjCreation (BaseEntityInterface $obj, $provideObjValsInLog = true) {
        $shortCN = substr($this->class, strrpos($this->class, '\\')+1);
        $logStr = 'Created a new '. $shortCN .' object ('. $this->class .').';

        $context = [$shortCN => $obj];
        $this->logger->info($logStr, $context);
    }

    /*
     * this method should be overwritten so that the inheriting class can return a type hint to correct
     * entity repository and allow for autocomplete when using: $this->repo->
     */
//    abstract protected function getRepo (); // todo: uncomment this once I know it works.

    public function getAllBy($criteriaArray) {
        return $this->findBy($criteriaArray);
    }

    public function findBy($criteriaArray) {
        $result = $this->repo->findBy(
            $criteriaArray
        );
        return $result;
    }

    public function getOneBy($criteriaArray) {
        return $this->findOneBy($criteriaArray);
    }

    public function findOneBy($criteriaArray) {
        $result = $this->repo->findOneBy(
            $criteriaArray
        );
        return $result;
    }

    public function getOneById($id) {
        $result = $this->repo->findOneBy(
            array ('id'        => $id)
        );

        return $result;
    }

    public function findAll () {
        return $this->repo->findall();
    }

    /**
     * @return mixed
     */
    public function getOutputLogToScreen()
    {
        return $this->outputLogToScreen;
    }

    /**
     * @param mixed $outputLogToScreen
     * used to output result when using Doctrine fixtures > set to true when in LoadUserData.php (or other fixtures file)
     */
    public function setOutputLogToScreen($outputLogToScreen)
    {
        $this->outputLogToScreen = $outputLogToScreen;
    }

    public function logString ($logString) {
        $this->logger->info($logString);

        if ($this->outputLogToScreen) {
            // used to output result when using Doctrine fixtures
            print $logString ."\n";
        }

        return true;
    }

    // use this instead of direct em call to apply the law of demeter
    // (may want to use a different persistence layer at some point)
    public function persist ($obj) {
        $this->em->persist($obj);
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}