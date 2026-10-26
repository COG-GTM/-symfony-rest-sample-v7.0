<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AppFixturesTest extends TestCase
{
    public function testUsersGetUniqueRandomPasswordsWhenNoneIsConfigured(): void
    {
        $passwords = $this->loadUserPasswords(null);

        $this->assertSame(['jane_admin', 'tom_admin', 'john_user'], array_keys($passwords));
        $this->assertCount(3, array_unique($passwords));
        foreach ($passwords as $password) {
            $this->assertNotSame('kitten', $password);
            $this->assertGreaterThanOrEqual(32, mb_strlen($password));
        }
    }

    public function testEmptyConfiguredPasswordFallsBackToRandomPasswords(): void
    {
        $this->assertCount(3, array_unique($this->loadUserPasswords('')));
    }

    public function testUsersGetTheConfiguredPassword(): void
    {
        $passwords = $this->loadUserPasswords('s3cret-local-password');

        $this->assertSame(['s3cret-local-password'], array_values(array_unique($passwords)));
    }

    public function testWellKnownDemoPasswordIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->loadUserPasswords('kitten');
    }

    /**
     * @return array<string, string> plain passwords indexed by username
     */
    private function loadUserPasswords(?string $configuredPassword): array
    {
        $hasher = new class() implements UserPasswordHasherInterface {
            public function hashPassword(PasswordAuthenticatedUserInterface $user, string $plainPassword): string
            {
                return 'plain:'.$plainPassword;
            }

            public function isPasswordValid(PasswordAuthenticatedUserInterface $user, string $plainPassword): bool
            {
                return 'plain:'.$plainPassword === $user->getPassword();
            }

            public function needsRehash(PasswordAuthenticatedUserInterface $user): bool
            {
                return false;
            }
        };

        $passwords = [];
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(static function (object $entity) use (&$passwords): void {
            if ($entity instanceof User) {
                $hash = (string) $entity->getPassword();
                $passwords[$entity->getUserIdentifier()] = mb_substr($hash, mb_strlen('plain:'));
            }
        });

        $references = [];
        $referenceRepository = $this->createMock(ReferenceRepository::class);
        $addReference = static function (string $name, object $object) use (&$references): void {
            $references[$name] = $object;
        };
        $getReference = static function (string $name) use (&$references): object {
            return $references[$name];
        };
        $referenceRepository->method('addReference')->willReturnCallback($addReference);
        $referenceRepository->method('getReference')->willReturnCallback($getReference);

        $fixtures = new AppFixtures($hasher, new AsciiSlugger(), $configuredPassword);
        $fixtures->setReferenceRepository($referenceRepository);
        $fixtures->load($manager);

        return $passwords;
    }
}
