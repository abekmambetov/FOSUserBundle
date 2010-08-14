<?php

namespace Bundle\DoctrineUserBundle\Tests\DAO;

use Bundle\DoctrineUserBundle\DAO\User;
use Bundle\DoctrineUserBundle\DAO\UserRepository;

// Kernel creation required namespaces
use Symfony\Components\Finder\Finder;

class UserRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUserRepo()
    {
        $userRepo = self::createKernel()->getContainer()->getDoctrineUser_UserRepoService();
        $this->assertTrue($userRepo instanceof UserRepository);

        return $userRepo;
    }

    /**
     * @depends testGetUserRepo
     */
    public function testCreateNewUser(UserRepository $userRepo)
    {
        $objectManager = $userRepo->getObjectManager();

        $userClass = $userRepo->getObjectClass();
        $user = new $userClass();
        $user->setUserName('harry_test');
        $user->setEmail('harry@mail.org');
        $user->setPassword('changeme');
        $objectManager->persist($user);

        $user2 = new $userClass();
        $user2->setUserName('harry_test2');
        $user2->setEmail('harry2@mail.org');
        $user2->setPassword('changeme2');
        $objectManager->persist($user2);

        $objectManager->flush();

        $this->assertNotNull($user->getId());
        $this->assertNotNull($user2->getId());

        return array($userRepo, $user, $user2);
    }

    /**
     * @depends testCreateNewUser
     */
    public function testTimestampable(array $dependencies)
    {
        list($userRepo, $user) = $dependencies;
        
        $this->assertTrue($user->getCreatedAt() instanceof \DateTime);
        $this->assertEquals(new \DateTime(), $user->getCreatedAt());
        
        $this->assertTrue($user->getUpdatedAt() instanceof \DateTime);
        $this->assertEquals(new \DateTime(), $user->getUpdatedAt());
    }

    /**
     * @depends testCreateNewUser
     */
    public function testFindOneById(array $dependencies)
    {
        list($userRepo, $user) = $dependencies;

        $fetchedUser = $userRepo->findOneById($user->getId());
        $this->assertSame($user, $fetchedUser);

        $nullUser = $userRepo->findOneById(0);
        $this->assertNull($nullUser);
    }

    /**
     * @depends testCreateNewUser
     */
    public function testFindOneByUsername(array $dependencies)
    {
        list($userRepo, $user) = $dependencies;

        $fetchedUser = $userRepo->findOneByUsername($user->getUsername());
        $this->assertEquals($user->getUsername(), $fetchedUser->getUsername());

        $nullUser = $userRepo->findOneByUsername('thisusernamedoesnotexist----thatsprettycertain');
        $this->assertNull($nullUser);
    }

    /**
     * @depends testCreateNewUser
     */
    public function testFindOneByEmail(array $dependencies)
    {
        list($userRepo, $user) = $dependencies;

        $fetchedUser = $userRepo->findOneByEmail($user->getEmail());
        $this->assertEquals($user->getEmail(), $fetchedUser->getEmail());

        $nullUser = $userRepo->findOneByEmail('thisemaildoesnotexist----thatsprettycertain');
        $this->assertNull($nullUser);
    }

    /**
     * @depends testCreateNewUser
     */
    public function testFindOneByUsernameOrEmail(array $dependencies)
    {
        list($userRepo, $user, $user2) = $dependencies;

        $fetchedUser = $userRepo->findOneByUsernameOrEmail($user->getUsername());
        $this->assertEquals($user->getUsername(), $fetchedUser->getUsername());

        $fetchedUser = $userRepo->findOneByUsernameOrEmail($user2->getUsername());
        $this->assertEquals($user2->getUsername(), $fetchedUser->getUsername());

        $fetchedUser = $userRepo->findOneByUsernameOrEmail($user->getEmail());
        $this->assertEquals($user->getEmail(), $fetchedUser->getEmail());

        $fetchedUser = $userRepo->findOneByUsernameOrEmail($user2->getEmail());
        $this->assertEquals($user2->getEmail(), $fetchedUser->getEmail());

        $nullUser = $userRepo->findOneByUsernameOrEmail('thisemaildoesnotexist----thatsprettycertain');
        $this->assertNull($nullUser);
    }

    /**
     * @depends testCreateNewUser
     */
    public function testFindOneByUsernameAndPassword(array $dependencies)
    {
        list($userRepo, $user) = $dependencies;

        $fetchedUser = $userRepo->findOneByUsernameAndPassword($user->getUsername(), 'changeme');
        $this->assertEquals($user->getUsername(), $fetchedUser->getUsername());

        $nullUser = $userRepo->findOneByUsernameAndPassword($user->getUsername(), 'badpassword');
        $this->assertNull($nullUser);

        $nullUser = $userRepo->findOneByUsernameAndPassword('thisusernamedoesnotexist----thatsprettycertain', 'changeme');
        $this->assertNull($nullUser);
    }

    /**
     * @depends testCreateNewUser
     */
    public function testFindOneByEmailAndPassword(array $dependencies)
    {
        list($userRepo, $user) = $dependencies;

        $fetchedUser = $userRepo->findOneByEmailAndPassword($user->getEmail(), 'changeme');
        $this->assertEquals($user->getEmail(), $fetchedUser->getEmail());

        $nullUser = $userRepo->findOneByEmailAndPassword($user->getEmail(), 'badpassword');
        $this->assertNull($nullUser);

        $nullUser = $userRepo->findOneByEmailAndPassword('thisemaildoesnotexist----thatsprettycertain', 'changeme');
        $this->assertNull($nullUser);
    }

    /**
     * @depends testCreateNewUser
     */
    public function testFindOneByUsernameOrEmailAndPassword(array $dependencies)
    {
        list($userRepo, $user, $user2) = $dependencies;

        $userClass = $userRepo->getObjectClass();

        $fetchedUser = $userRepo->findOneByUsernameOrEmailAndPassword($user->getUsername(), 'changeme');
        $this->assertEquals($user->getUsername(), $fetchedUser->getUsername());

        $fetchedUser = $userRepo->findOneByUsernameOrEmailAndPassword($user2->getUsername(), 'changeme2');
        $this->assertEquals($user2->getUsername(), $fetchedUser->getUsername());

        $fetchedUser = $userRepo->findOneByUsernameOrEmailAndPassword($user->getEmail(), 'changeme');
        $this->assertEquals($user->getEmail(), $fetchedUser->getEmail());

        $fetchedUser = $userRepo->findOneByUsernameOrEmailAndPassword($user2->getEmail(), 'changeme2');
        $this->assertEquals($user2->getEmail(), $fetchedUser->getEmail());

        $nullUser = $userRepo->findOneByUsernameOrEmailAndPassword('thisemaildoesnotexist----thatsprettycertain', 'changeme');
        $this->assertNull($nullUser);

        $nullUser = $userRepo->findOneByUsernameOrEmailAndPassword($user->getUsername(), 'badPassword');
        $this->assertNull($nullUser);

        $nullUser = $userRepo->findOneByUsernameOrEmailAndPassword($user->getEmail(), 'badPassword');
        $this->assertNull($nullUser);
    }

    static public function tearDownAfterClass()
    {
        $userRepo = self::createKernel()->getContainer()->getDoctrineUser_UserRepoService();
        $objectManager = $userRepo->getObjectManager();
        foreach(array('harry_test', 'harry_test2') as $username) {
            $objectManager->remove($userRepo->findOneByUsername($username));
        }
        $objectManager->flush();
    }

    /**
     * Creates a Kernel.
     *
     * If you run tests with the PHPUnit CLI tool, everything will work as expected.
     * If not, override this method in your test classes.
     *
     * Available options:
     *
     *  * environment
     *  * debug
     *
     * @param array $options An array of options
     *
     * @return HttpKernelInterface A HttpKernelInterface instance
     */
    static protected function createKernel(array $options = array())
    {
        // black magic below, you have been warned!
        $dir = getcwd();
        if (!isset($_SERVER['argv']) || false === strpos($_SERVER['argv'][0], 'phpunit')) {
            throw new \RuntimeException('You must override the WebTestCase::createKernel() method.');
        }

        // find the --configuration flag from PHPUnit
        $cli = implode(' ', $_SERVER['argv']);
        if (preg_match('/\-\-configuration[= ]+([^ ]+)/', $cli, $matches)) {
            $dir = $dir.'/'.$matches[1];
        } elseif (preg_match('/\-c +([^ ]+)/', $cli, $matches)) {
            $dir = $dir.'/'.$matches[1];
        } else {
            throw new \RuntimeException('Unable to guess the Kernel directory.');
        }

        if (!is_dir($dir)) {
            $dir = dirname($dir);
        }

        $finder = new Finder();
        $finder->name('*Kernel.php')->in($dir);
        if (!count($finder)) {
            throw new \RuntimeException('You must override the WebTestCase::createKernel() method.');
        }

        $file = current(iterator_to_array($finder));
        $class = $file->getBasename('.php');
        unset($finder);

        require_once $file;

        $kernel = new $class(
            isset($options['environment']) ? $options['environment'] : 'test',
            isset($options['debug']) ? $debug : true
        );
        $kernel->boot();

        return $kernel;
    }
}