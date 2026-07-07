<?php

namespace Tests\Unit;

use App\Models\WeeklyReport;
use Carbon\Carbon;
use Tests\TestCase;

class WeeklyReportTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_submission_is_open_only_from_thursday_through_saturday(): void
    {
        $this->assertFalse(WeeklyReport::isSubmissionOpen(Carbon::parse('2026-05-04 12:00:00')));
        $this->assertTrue(WeeklyReport::isSubmissionOpen(Carbon::parse('2026-05-07 12:00:00')));
        $this->assertTrue(WeeklyReport::isSubmissionOpen(Carbon::parse('2026-05-08 12:00:00')));
        $this->assertTrue(WeeklyReport::isSubmissionOpen(Carbon::parse('2026-05-09 12:00:00')));
        $this->assertFalse(WeeklyReport::isSubmissionOpen(Carbon::parse('2026-05-10 12:00:00')));
    }

    public function test_status_becomes_late_from_friday_8pm_through_saturday(): void
    {
        Carbon::setTestNow('2026-05-07 12:00:00');
        $this->assertSame('submitted', WeeklyReport::resolveStatus());

        Carbon::setTestNow('2026-05-08 19:59:59');
        $this->assertSame('submitted', WeeklyReport::resolveStatus());

        Carbon::setTestNow('2026-05-08 20:00:00');
        $this->assertSame('late', WeeklyReport::resolveStatus());

        Carbon::setTestNow('2026-05-09 12:00:00');
        $this->assertSame('late', WeeklyReport::resolveStatus());
    }
}
