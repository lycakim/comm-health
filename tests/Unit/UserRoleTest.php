<?php

namespace Tests\Unit;

use App\Enums\RoleEnum;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    private function makeUser(RoleEnum $role): User
    {
        $user = new User();
        $user->role = $role;
        return $user;
    }

    // -----------------------------------------------------------------------
    // isAdmin()
    // -----------------------------------------------------------------------

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $this->assertTrue($this->makeUser(RoleEnum::ADMIN)->isAdmin());
    }

    public function test_is_admin_returns_false_for_other_roles(): void
    {
        foreach ([RoleEnum::MHO, RoleEnum::BHW, RoleEnum::MIDWIFE, RoleEnum::RESIDENT] as $role) {
            $this->assertFalse($this->makeUser($role)->isAdmin(), "Failed for role: {$role->value}");
        }
    }

    // -----------------------------------------------------------------------
    // isMHO()
    // -----------------------------------------------------------------------

    public function test_is_mho_returns_true_for_mho_role(): void
    {
        $this->assertTrue($this->makeUser(RoleEnum::MHO)->isMHO());
    }

    public function test_is_mho_returns_false_for_other_roles(): void
    {
        foreach ([RoleEnum::ADMIN, RoleEnum::BHW, RoleEnum::MIDWIFE, RoleEnum::RESIDENT] as $role) {
            $this->assertFalse($this->makeUser($role)->isMHO(), "Failed for role: {$role->value}");
        }
    }

    // -----------------------------------------------------------------------
    // isBHW()
    // -----------------------------------------------------------------------

    public function test_is_bhw_returns_true_for_bhw_role(): void
    {
        $this->assertTrue($this->makeUser(RoleEnum::BHW)->isBHW());
    }

    public function test_is_bhw_returns_false_for_other_roles(): void
    {
        foreach ([RoleEnum::ADMIN, RoleEnum::MHO, RoleEnum::MIDWIFE, RoleEnum::RESIDENT] as $role) {
            $this->assertFalse($this->makeUser($role)->isBHW(), "Failed for role: {$role->value}");
        }
    }

    // -----------------------------------------------------------------------
    // isMidwife()
    // -----------------------------------------------------------------------

    public function test_is_midwife_returns_true_for_midwife_role(): void
    {
        $this->assertTrue($this->makeUser(RoleEnum::MIDWIFE)->isMidwife());
    }

    public function test_is_midwife_returns_false_for_other_roles(): void
    {
        foreach ([RoleEnum::ADMIN, RoleEnum::MHO, RoleEnum::BHW, RoleEnum::RESIDENT] as $role) {
            $this->assertFalse($this->makeUser($role)->isMidwife(), "Failed for role: {$role->value}");
        }
    }

    // -----------------------------------------------------------------------
    // isResident()
    // -----------------------------------------------------------------------

    public function test_is_resident_returns_true_for_resident_role(): void
    {
        $this->assertTrue($this->makeUser(RoleEnum::RESIDENT)->isResident());
    }

    public function test_is_resident_returns_false_for_other_roles(): void
    {
        foreach ([RoleEnum::ADMIN, RoleEnum::MHO, RoleEnum::BHW, RoleEnum::MIDWIFE] as $role) {
            $this->assertFalse($this->makeUser($role)->isResident(), "Failed for role: {$role->value}");
        }
    }

    // -----------------------------------------------------------------------
    // RoleEnum labels
    // -----------------------------------------------------------------------

    public function test_role_enum_labels_are_correct(): void
    {
        $this->assertSame('Administrator', RoleEnum::ADMIN->getLabel());
        $this->assertSame('Municipal Health Officer', RoleEnum::MHO->getLabel());
        $this->assertSame('Health Worker', RoleEnum::BHW->getLabel());
        $this->assertSame('Midwife', RoleEnum::MIDWIFE->getLabel());
        $this->assertSame('Resident', RoleEnum::RESIDENT->getLabel());
    }
}
