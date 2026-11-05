<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_full_name_accessor(): void
    {
        $user = new User(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->assertSame('John Doe', $user->full_name);
    }

    public function test_initials_accessor_uppercases_and_truncates(): void
    {
        $user = new User(['first_name' => 'john', 'last_name' => 'doe']);

        $this->assertSame('JD', $user->initials);
    }

    public function test_is_admin_checks_admin_role(): void
    {
        $admin = new User();
        $adminRole = new Role();
        $adminRole->name = 'admin';
        $admin->setRelation('role', $adminRole);

        $member = new User();
        $memberRole = new Role();
        $memberRole->name = 'member';
        $member->setRelation('role', $memberRole);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($member->isAdmin());
    }
}
