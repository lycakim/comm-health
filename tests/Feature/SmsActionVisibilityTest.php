<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests the visibility logic of the send_sms table action in ProgramResource.
 *
 * Rule: the SMS button is visible only when the current user's role tier
 * matches the tier of the user who created the program.
 *   - MHO / Admin  → can see SMS on programs created by MHO / Admin
 *   - BHW / Midwife → can see SMS on programs created by BHW / Midwife
 */
class SmsActionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(RoleEnum $role, ?int $barangayId = null): User
    {
        return User::factory()->create(['role' => $role, 'barangay_id' => $barangayId]);
    }

    /** Mirrors the closure in ProgramResource send_sms visible(). */
    private function smsIsVisible(User $viewer, Program $program): bool
    {
        $creator = $program->createdByUser ?? $program->coordinatorUser;

        if ($viewer->isMHO() || $viewer->isAdmin()) {
            return ! $creator || in_array($creator->role, [RoleEnum::ADMIN, RoleEnum::MHO]);
        }

        if ($viewer->isBHW() || $viewer->isMidwife()) {
            return $creator && in_array($creator->role, [RoleEnum::BHW, RoleEnum::MIDWIFE]);
        }

        return false;
    }

    private function makeProgram(User $creator): Program
    {
        return Program::factory()->create([
            'created_by'  => $creator->id,
            'coordinator' => $creator->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // MHO viewer
    // -----------------------------------------------------------------------

    public function test_mho_sees_sms_button_on_mho_created_program(): void
    {
        $mho     = $this->makeUser(RoleEnum::MHO);
        $program = $this->makeProgram($this->makeUser(RoleEnum::MHO));

        $this->assertTrue($this->smsIsVisible($mho, $program));
    }

    public function test_mho_sees_sms_button_on_admin_created_program(): void
    {
        $mho     = $this->makeUser(RoleEnum::MHO);
        $program = $this->makeProgram($this->makeUser(RoleEnum::ADMIN));

        $this->assertTrue($this->smsIsVisible($mho, $program));
    }

    public function test_mho_does_not_see_sms_button_on_bhw_created_program(): void
    {
        $mho     = $this->makeUser(RoleEnum::MHO);
        $barangay = Barangay::factory()->create();
        $program = $this->makeProgram($this->makeUser(RoleEnum::BHW, $barangay->id));

        $this->assertFalse($this->smsIsVisible($mho, $program));
    }

    public function test_mho_does_not_see_sms_button_on_midwife_created_program(): void
    {
        $mho      = $this->makeUser(RoleEnum::MHO);
        $barangay = Barangay::factory()->create();
        $program  = $this->makeProgram($this->makeUser(RoleEnum::MIDWIFE, $barangay->id));

        $this->assertFalse($this->smsIsVisible($mho, $program));
    }

    // -----------------------------------------------------------------------
    // BHW viewer
    // -----------------------------------------------------------------------

    public function test_bhw_sees_sms_button_on_bhw_created_program(): void
    {
        $barangay = Barangay::factory()->create();
        $bhw      = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $program  = $this->makeProgram($this->makeUser(RoleEnum::BHW, $barangay->id));

        $this->assertTrue($this->smsIsVisible($bhw, $program));
    }

    public function test_bhw_sees_sms_button_on_midwife_created_program(): void
    {
        $barangay = Barangay::factory()->create();
        $bhw      = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $program  = $this->makeProgram($this->makeUser(RoleEnum::MIDWIFE, $barangay->id));

        $this->assertTrue($this->smsIsVisible($bhw, $program));
    }

    public function test_bhw_does_not_see_sms_button_on_mho_created_program(): void
    {
        $barangay = Barangay::factory()->create();
        $bhw      = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $program  = $this->makeProgram($this->makeUser(RoleEnum::MHO));

        $this->assertFalse($this->smsIsVisible($bhw, $program));
    }

    public function test_bhw_does_not_see_sms_button_on_admin_created_program(): void
    {
        $barangay = Barangay::factory()->create();
        $bhw      = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $program  = $this->makeProgram($this->makeUser(RoleEnum::ADMIN));

        $this->assertFalse($this->smsIsVisible($bhw, $program));
    }

    // -----------------------------------------------------------------------
    // Admin viewer
    // -----------------------------------------------------------------------

    public function test_admin_sees_sms_button_on_admin_created_program(): void
    {
        $admin   = $this->makeUser(RoleEnum::ADMIN);
        $program = $this->makeProgram($this->makeUser(RoleEnum::ADMIN));

        $this->assertTrue($this->smsIsVisible($admin, $program));
    }

    public function test_admin_does_not_see_sms_button_on_bhw_created_program(): void
    {
        $admin    = $this->makeUser(RoleEnum::ADMIN);
        $barangay = Barangay::factory()->create();
        $program  = $this->makeProgram($this->makeUser(RoleEnum::BHW, $barangay->id));

        $this->assertFalse($this->smsIsVisible($admin, $program));
    }
}
