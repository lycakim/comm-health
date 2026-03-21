<?php

namespace Tests\Unit;

use App\Models\Category;
use PHPUnit\Framework\TestCase;

class CategoryModelTest extends TestCase
{
    // -----------------------------------------------------------------------
    // isProfiledRegisteredMembers()
    // -----------------------------------------------------------------------

    public function test_is_profiled_registered_members_returns_true_for_exact_name(): void
    {
        $category = new Category();
        $category->name = Category::PROFILED_REGISTERED_MEMBERS_NAME;

        $this->assertTrue($category->isProfiledRegisteredMembers());
    }

    public function test_is_profiled_registered_members_returns_false_for_other_name(): void
    {
        $category = new Category();
        $category->name = 'Senior Citizens';

        $this->assertFalse($category->isProfiledRegisteredMembers());
    }

    public function test_is_profiled_registered_members_is_case_sensitive(): void
    {
        $category = new Category();
        $category->name = strtolower(Category::PROFILED_REGISTERED_MEMBERS_NAME);

        $this->assertFalse($category->isProfiledRegisteredMembers());
    }

    // -----------------------------------------------------------------------
    // containsAge()
    // -----------------------------------------------------------------------

    public function test_contains_age_returns_true_when_no_bounds_set(): void
    {
        $category = new Category();
        $category->age_min = null;
        $category->age_max = null;

        $this->assertTrue($category->containsAge(30));
    }

    public function test_contains_age_returns_true_within_range(): void
    {
        $category = new Category();
        $category->age_min = 18;
        $category->age_max = 60;

        $this->assertTrue($category->containsAge(30));
        $this->assertTrue($category->containsAge(18));
        $this->assertTrue($category->containsAge(60));
    }

    public function test_contains_age_returns_false_below_min(): void
    {
        $category = new Category();
        $category->age_min = 18;
        $category->age_max = 60;

        $this->assertFalse($category->containsAge(17));
    }

    public function test_contains_age_returns_false_above_max(): void
    {
        $category = new Category();
        $category->age_min = 18;
        $category->age_max = 60;

        $this->assertFalse($category->containsAge(61));
    }

    public function test_contains_age_with_only_min_bound(): void
    {
        $category = new Category();
        $category->age_min = 60;
        $category->age_max = null;

        $this->assertTrue($category->containsAge(60));
        $this->assertTrue($category->containsAge(100));
        $this->assertFalse($category->containsAge(59));
    }

    public function test_contains_age_with_only_max_bound(): void
    {
        $category = new Category();
        $category->age_min = null;
        $category->age_max = 5;

        $this->assertTrue($category->containsAge(0));
        $this->assertTrue($category->containsAge(5));
        $this->assertFalse($category->containsAge(6));
    }
}
