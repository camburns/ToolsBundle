<?php

namespace App\VisageFour\Bundle\ToolsBundle\Classes;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Person;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use VisageFour\Bundle\ToolsBundle\Services\TerminalColors;
use Twencha\Bundle\EventRegistrationBundle\Services\PersonManager;

/**
 * Class CustomApiTestCase
 * @package App\VisageFour\Bundle\ToolsBundle\Classes
 *
 * this class reduces boiler plate such as: creating users, sending requents (finctional testing) setup and tear down etc.
 */
abstract class CustomApiTestCase extends ApiTestCase
{
    /** @var PersonManager */
    private $personMan;

    /** @var ObjectManager */
    private $manager;

    // the email address of the most recently created user.
    protected $userEmail;

    // the password of the most recently created user.
    protected $userPassword;

    // the target url of the test case. There should only be one as there should only be one test class per endpoint.
    protected $url;

    protected $client;

    protected $debugOutputOn;

    /**
     * @var Factory
     */
    private $faker;

    static protected $terminalColors;

    // setup that is specific to the test case that subclasses this class.
    abstract protected function specificSetUp ();

    protected function setUp(): void
    {
        // this is needed, as tearDown() shuts down the kernel each time.
        // see (for more info): https://stackoverflow.com/questions/59964480/symfony-phpunit-selfkernel-is-null-in-second-test#
//        $kernel = self::bootKernel();

        $client = static::createClient();
        $client->enableProfiler();

        $this->em = $client->getKernel()->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->client = $client;

        $this->faker = Factory::create();
        $this->getServices();

//        $this->outputRedTextToTerminal('hi there!');
//        parent::setUp();

        $this->specificSetUp();
    }

    protected function getServices()
    {
        $this->manager          = self::$container->get('doctrine.orm.default_entity_manager');
        $this->personMan        = self::$container->get('twencha.person_man');
    }

    static public function setUpBeforeClass(): void
    {
        // start the symfony kernel
        $kernel = static::createKernel();
        $kernel->boot();

        // get the DI container
        self::$container = $kernel->getContainer();

//        self::$terminalColors = self::$container->get('TerminalColors');
        self::$terminalColors = new TerminalColors();

        return;
    }

    /**
     * Sends a "http request" to the $url specified. This just reduces boilerplate in the testcase methods.
     */
    protected function sendJSONRequest(string $method, $data) {
        $json = ['json' => $data];

        if (empty($this->url)) {
            throw new \Exception('$this->url cannot be empty. Please set it via: specificSetUp().');
        }
        $crawler = $this->client->request($method, $this->url, $json);

        return $crawler;
    }

    /**
     * specifically for debugging outputs. Can be turned on (while developing a single test case) and turned off when doing mass tests.
     */
    protected function outputDebugToTerminal($msg)
    {
        if ($this->debugOutputOn) {
            $this->outputRedTextToTerminal('DEBUG: '. $msg);
        }
    }

    protected function outputRedTextToTerminal($msg)
    {
        $text = self::$terminalColors->getColoredString($msg, 'red', 'black');
        print "\n";
        print $text;
    }

    /**
     * @param $email
     * @param $password
     * @return Person
     * @throws \Doctrine\ORM\ORMException
     *
     * Create a new person and store the password and email address (for later access - this is cleaner than statically writing both in the sub class.)
     */
    protected function createNewUser(): Person
    {
        $this->userEmail        = $this->faker->email();
        $this->userPassword     = $this->faker->password;

        $this->outputDebugToTerminal(
            'creating person with email: '. $this->userEmail .' and password: '. $this->userPassword
        );

        $person     = $this->personMan->createNewPerson($this->userEmail, $this->userPassword);
        $this->manager->persist($person);
        $this->manager->flush();

        return $person;
    }

    protected function removeUser (Person $person) {
        $this->manager->remove($person);
        $this->manager->flush();
    }
}