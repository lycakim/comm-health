<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Filament\Resources\ProgramResource;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(RoleEnum $role, ?int $barangayId = null): User
    {
        return User::factory()->create([
            'role'        => $role,
            'barangay_id' => $barangayId,
        ]);
    }

    private function makeProgramCreatedBy(User $creator): Program
    {
        return Program::factory()->create([
            'created_by'  => $creator->id,
            'coordinator' => $creator->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // canAccess()
    // -----------------------------------------------------------------------

    public function test_admin_can_access_program_resource(): void
    {
        $this->actingAs($this->makeUser(RoleEnum::ADMIN));
        $this->assertTrue(ProgramResource::canAccess());
    }

    public function test_mho_can_access_program_resource(): void
    {
        $this->actingAs($this->makeUser(RoleEnum::MHO));
        $this->assertTrue(ProgramResource::canAccess());
    }

    public function test_bhw_with_barangay_can_access_program_resource(): void
    {
        $barangay = Barangay::factory()->create();
        $this->actingAs($this->makeUser(RoleEnum::BHW, $barangay->id));
        $this->assertTrue(ProgramResource::canAccess());
    }

    public function test_bhw_without_barangay_cannot_access_program_resource(): void
    {
        $this->actingAs($this->makeUser(RoleEnum::BHW, null));
        $this->assertFalse(ProgramResource::canAccess());
    }

    public function test_resident_cannot_access_program_resource(): void
    {
        $this->actingAs($this->makeUser(RoleEnum::RESIDENT));
        $this->assertFalse(ProgramResource::canAccess());
    }

    // -----------------------------------------------------------------------
    // canCreate()
    // -----------------------------------------------------------------------

    public function test_admin_can_create_programs(): void
    {
        $this->actingAs($this->makeUser(RoleEnum::ADMIN));
        $this->assertTrue(ProgramResource::canCreate());
    }

    public function test_mho_can_create_programs(): void
    {
        $this->actingAs($this->makeUser(RoleEnum::MHO));
        $this->assertTrue(ProgramResource::canCreate());
    }

    public function test_bhw_with_barangay_can_create_programs(): void
    {
        $barangay = Barangay::factory()->create();
        $this->actingAs($this->makeUser(RoleEnum::BHW, $barangay->id));
        $this->assertTrue(ProgramResource::canCreate());
    }

    public function test_bhw_without_barangay_cannot_create_programs(): void
    {
        $this->actingAs($this->makeUser(RoleEnum::BHW, null));
        $this->assertFalse(ProgramResource::canCreate());
    }

    // -----------------------------------------------------------------------
    // canEdit()
    // -----------------------------------------------------------------------

    public function test_admin_can_edit_any_program(): void
    {
        $admin   = $this->makeUser(RoleEnum::ADMIN);
        $program = $this->makeProgramCreatedBy($this->makeUser(RoleEnum::BHW));

        $this->actingAs($admin);
        $this->assertTrue(ProgramResource::canEdit($program));
    }

    public function test_mho_can_edit_any_program(): void
    {
        $mho     = $this->makeUser(RoleEnum::MHO);
        $program = $this->makeProgramCreatedBy($this->makeUser(RoleEnum::BHW));

        $this->actingAs($mho);
        $this->assertTrue(ProgramResource::canEdit($program));
    }

    public function test_bhw_can_edit_program_they_created(): void
    {
        $barangay = Barangay::factory()->create();
        $bhw      = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $program  = $this->makeProgramCreatedBy($bhw);

        $this->actingAs($bhw);
        $this->assertTrue(ProgramResource::canEdit($program));
    }

    public function test_bhw_cannot_edit_program_created_by_another_user(): void
    {
        $barangay = Barangay::factory()->create();
        $bhw      = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $otherBhw = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $program  = $this->makeProgramCreatedBy($otherBhw);

        $this->actingAs($bhw);
        $this->assertFalse(ProgramResource::canEdit($program));
    }

    // -----------------------------------------------------------------------
    // canDelete()
    // -----------------------------------------------------------------------

    public function test_admin_can_delete_any_program(): void
    {
        $admin   = $this->makeUser(RoleEnum::ADMIN);
        $program = $this->makeProgramCreatedBy($this->makeUser(RoleEnum::BHW));

        $this->actingAs($admin);
        $this->assertTrue(ProgramResource::canDelete($program));
    }

    public function test_bhw_can_delete_only_their_own_program(): void
    {
        $barangay = Barangay::factory()->create();
        $bhw      = $this->makeUser(RoleEnum::BHW, $barangay->id);
        $own      = $this->makeProgramCreatedBy($bhw);
        $others   = $this->makeProgramCreatedBy($this->makeUser(RoleEnum::BHW, $barangay->id));

        $this->actingAs($bhw);
        $this->assertTrue(ProgramResource::canDelete($own));
        $this->assertFalse(ProgramResource::canDelete($others));
    }
}
