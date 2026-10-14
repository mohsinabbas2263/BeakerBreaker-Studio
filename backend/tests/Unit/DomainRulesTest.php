<?php

namespace Tests\Unit;

use App\Enums\AssignmentState;
use App\Models\StudentProfile;
use PHPUnit\Framework\TestCase;

/**
 * The rules that are pure arithmetic or pure state, tested without a database.
 */
class DomainRulesTest extends TestCase
{
    /** FR-06.4: eight rank levels, by cumulative points. */
    public function test_rank_bands_are_continuous_and_ordered(): void
    {
        $this->assertCount(8, StudentProfile::RANKS);

        $this->assertSame(1, StudentProfile::rankFor(0));
        $this->assertSame(1, StudentProfile::rankFor(249));
        $this->assertSame(2, StudentProfile::rankFor(250));
        $this->assertSame(3, StudentProfile::rankFor(600));
        $this->assertSame(8, StudentProfile::rankFor(8000));
        $this->assertSame(8, StudentProfile::rankFor(999999));
    }

    /** A rank never falls as points rise. */
    public function test_rank_is_monotonic_in_points(): void
    {
        $previous = 0;

        for ($points = 0; $points <= 9000; $points += 50) {
            $rank = StudentProfile::rankFor($points);
            $this->assertGreaterThanOrEqual($previous, $rank);
            $previous = $rank;
        }
    }

    /** FR-09.5: the declared transitions, and no others. */
    public function test_assignment_state_transitions(): void
    {
        $this->assertTrue(AssignmentState::Assigned->canMoveTo(AssignmentState::InProgress));
        $this->assertTrue(AssignmentState::Assigned->canMoveTo(AssignmentState::Overdue));
        $this->assertTrue(AssignmentState::InProgress->canMoveTo(AssignmentState::Completed));
        $this->assertTrue(AssignmentState::InProgress->canMoveTo(AssignmentState::Overdue));

        // Overdue is recoverable: late work still counts.
        $this->assertTrue(AssignmentState::Overdue->canMoveTo(AssignmentState::InProgress));
        $this->assertTrue(AssignmentState::Overdue->canMoveTo(AssignmentState::Completed));

        // Completed is terminal.
        $this->assertFalse(AssignmentState::Completed->canMoveTo(AssignmentState::InProgress));
        $this->assertFalse(AssignmentState::Completed->canMoveTo(AssignmentState::Overdue));
        $this->assertFalse(AssignmentState::Completed->canMoveTo(AssignmentState::Assigned));

        // Nothing returns to assigned.
        $this->assertFalse(AssignmentState::InProgress->canMoveTo(AssignmentState::Assigned));
        $this->assertFalse(AssignmentState::Overdue->canMoveTo(AssignmentState::Assigned));
    }

    /** FR-01.1: each role has its own destination. */
    public function test_each_role_has_a_distinct_home_route(): void
    {
        $routes = array_map(
            fn (\App\Enums\UserRole $role) => $role->homeRoute(),
            \App\Enums\UserRole::cases()
        );

        $this->assertCount(3, $routes);
        $this->assertSame($routes, array_unique($routes));
    }
}
