<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Category;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientHouseholdHeadTest extends TestCase
{
    use RefreshDatabase;

    private Barangay $barangay;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->barangay = Barangay::factory()->create();
        $this->category = Category::factory()->create();
    }

    // -----------------------------------------------------------------------
    // uniqueHouseholdHeadsForSms()
    // -----------------------------------------------------------------------

    public function test_returns_single_patient_when_they_are_their_own_head(): void
    {
        $patient = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create([
            'household_head_id' => null,
        ]);

        $result = Patient::uniqueHouseholdHeadsForSms(collect([$patient]));

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($patient));
    }

    public function test_deduplicates_members_of_the_same_household(): void
    {
        $head = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create([
            'household_head_id' => null,
        ]);

        $member1 = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create([
            'household_head_id' => $head->id,
        ]);

        $member2 = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create([
            'household_head_id' => $head->id,
        ]);

        $result = Patient::uniqueHouseholdHeadsForSms(collect([$head, $member1, $member2]));

        // Only the head should come back — one SMS per household
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($head));
    }

    public function test_returns_multiple_heads_for_different_households(): void
    {
        $head1 = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create(['household_head_id' => null]);
        $head2 = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create(['household_head_id' => null]);

        $member1 = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create(['household_head_id' => $head1->id]);
        $member2 = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create(['household_head_id' => $head2->id]);

        $result = Patient::uniqueHouseholdHeadsForSms(collect([$head1, $head2, $member1, $member2]));

        $this->assertCount(2, $result);
        $resultIds = $result->pluck('id')->sort()->values();
        $this->assertEquals(collect([$head1->id, $head2->id])->sort()->values(), $resultIds);
    }

    public function test_returns_empty_collection_for_empty_input(): void
    {
        $result = Patient::uniqueHouseholdHeadsForSms(collect([]));

        $this->assertCount(0, $result);
    }

    public function test_uses_patient_itself_when_household_head_id_is_null(): void
    {
        $patient = Patient::factory()->inBarangay($this->barangay->id)->inCategory($this->category->id)->create([
            'household_head_id' => null,
        ]);

        $result = Patient::uniqueHouseholdHeadsForSms(collect([$patient]));

        $this->assertTrue($result->first()->is($patient));
    }
}
