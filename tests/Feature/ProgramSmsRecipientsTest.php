<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Category;
use App\Models\Patient;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramSmsRecipientsTest extends TestCase
{
    use RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();
        $this->barangay = Barangay::factory()->create();
    }

    public function test_returns_all_barangay_patients_for_profiled_registered_members_category(): void
    {
        $profiledCategory = Category::factory()->profiledRegisteredMembers()->create();
        $otherCategory    = Category::factory()->create();

        // 2 patients in the target barangay (different categories)
        Patient::factory()->inBarangay($this->barangay->id)->inCategory($profiledCategory->id)->create();
        Patient::factory()->inBarangay($this->barangay->id)->inCategory($otherCategory->id)->create();

        // 1 patient in a different barangay
        Patient::factory()->inCategory($profiledCategory->id)->create();

        $program = Program::factory()->create([
            'barangay_id' => $this->barangay->id,
            'category_id' => $profiledCategory->id,
        ]);

        $recipients = $program->getSmsRecipientsQuery()->get();

        $this->assertCount(2, $recipients);
        $recipients->each(fn ($p) => $this->assertEquals($this->barangay->id, $p->barangay_id));
    }

    public function test_filters_by_category_for_non_profiled_categories(): void
    {
        $targetCategory = Category::factory()->create();
        $otherCategory  = Category::factory()->create();

        Patient::factory()->inBarangay($this->barangay->id)->inCategory($targetCategory->id)->create();
        Patient::factory()->inBarangay($this->barangay->id)->inCategory($targetCategory->id)->create();
        Patient::factory()->inBarangay($this->barangay->id)->inCategory($otherCategory->id)->create(); // should be excluded

        $program = Program::factory()->create([
            'barangay_id' => $this->barangay->id,
            'category_id' => $targetCategory->id,
        ]);

        $recipients = $program->getSmsRecipientsQuery()->get();

        $this->assertCount(2, $recipients);
        $recipients->each(fn ($p) => $this->assertEquals($targetCategory->id, $p->category_id));
    }

    public function test_excludes_patients_from_other_barangays(): void
    {
        $category      = Category::factory()->create();
        $otherBarangay = Barangay::factory()->create();

        Patient::factory()->inBarangay($this->barangay->id)->inCategory($category->id)->create();
        Patient::factory()->inBarangay($otherBarangay->id)->inCategory($category->id)->create(); // excluded

        $program = Program::factory()->create([
            'barangay_id' => $this->barangay->id,
            'category_id' => $category->id,
        ]);

        $recipients = $program->getSmsRecipientsQuery()->get();

        $this->assertCount(1, $recipients);
        $this->assertEquals($this->barangay->id, $recipients->first()->barangay_id);
    }

    public function test_returns_empty_when_no_patients_match(): void
    {
        $category = Category::factory()->create();

        $program = Program::factory()->create([
            'barangay_id' => $this->barangay->id,
            'category_id' => $category->id,
        ]);

        $this->assertCount(0, $program->getSmsRecipientsQuery()->get());
    }
}
