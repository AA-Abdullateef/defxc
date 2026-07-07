<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WeeklyReport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReportSeeder extends Seeder
{
    /**
     * Seed realistic staff weekly reports.
     *
     * The records mirror the app rules:
     * - staff submit one report per ISO week
     * - submissions happen only Thursday through Saturday
     * - Friday 8:00 PM and Saturday submissions are late
     * - admins can later move submitted reports to completed
     */
    public function run(): void
    {
        $staffRole = \App\Models\Role::where('name', 'staff')->firstOrFail();

        $staffMembers = collect([
            ['name' => 'Amina Yusuf', 'email' => 'amina.yusuf@zeltech.com', 'job_title' => 'Frontend Developer'],
            ['name' => 'David Okafor', 'email' => 'david.okafor@zeltech.com', 'job_title' => 'Backend Developer'],
            ['name' => 'Mariam Bello', 'email' => 'mariam.bello@zeltech.com', 'job_title' => 'Product Designer'],
            ['name' => 'Samuel Eze', 'email' => 'samuel.eze@zeltech.com', 'job_title' => 'QA Analyst'],
        ])->map(function (array $staff) use ($staffRole) {
            $user = User::updateOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'password' => Hash::make('password'),
                ]
            );
            $user->roles()->syncWithoutDetaching([$staffRole->id]);

            return $user->setAttribute('seed_job_title', $staff['job_title']);
        });

        $weeklySlots = [
            ['week_offset' => 4, 'day' => Carbon::THURSDAY, 'time' => '10:15:00', 'reviewed' => true],
            ['week_offset' => 3, 'day' => Carbon::FRIDAY, 'time' => '16:30:00', 'reviewed' => true],
            ['week_offset' => 2, 'day' => Carbon::FRIDAY, 'time' => '20:25:00', 'reviewed' => false],
            ['week_offset' => 1, 'day' => Carbon::SATURDAY, 'time' => '11:45:00', 'reviewed' => false],
        ];

        foreach ($staffMembers as $staffIndex => $staff) {
            foreach ($weeklySlots as $slotIndex => $slot) {
                $submittedAt = $this->submissionDate($slot, $staffIndex);
                $status = $this->statusFor($submittedAt, $slot['reviewed']);

                WeeklyReport::updateOrCreate(
                    [
                        'user_id' => $staff->id,
                        'created_at' => $submittedAt,
                    ],
                    [
                        'name' => $staff->name,
                        'job_title' => $staff->seed_job_title,
                        'report' => $this->reportHtml($staff->seed_job_title, $slotIndex),
                        'challenges' => $this->challengeHtml($slotIndex),
                        'status' => $status,
                        'updated_at' => $status === 'completed'
                            ? $submittedAt->copy()->addDay()->setTime(9, 0)
                            : $submittedAt,
                    ]
                );
            }
        }
    }

    private function submissionDate(array $slot, int $staffIndex): Carbon
    {
        [$hour, $minute, $second] = array_map('intval', explode(':', $slot['time']));

        return Carbon::now()
            ->subWeeks($slot['week_offset'])
            ->startOfWeek(Carbon::MONDAY)
            ->addDays($slot['day'] - Carbon::MONDAY)
            ->setTime($hour, $minute + ($staffIndex * 3), $second);
    }

    private function statusFor(Carbon $submittedAt, bool $reviewed): string
    {
        $isLate = $submittedAt->dayOfWeek === Carbon::SATURDAY
            || ($submittedAt->dayOfWeek === Carbon::FRIDAY && $submittedAt->hour >= 20);

        if ($isLate) {
            return 'late';
        }

        return $reviewed ? 'completed' : 'submitted';
    }

    private function reportHtml(string $jobTitle, int $slotIndex): string
    {
        $reports = [
            'Completed assigned sprint tasks, joined planning, and documented follow-up items for next week.',
            'Improved active project work, resolved review comments, and shared progress updates with the team.',
            'Wrapped up priority deliverables and prepared blockers for admin visibility.',
            'Submitted weekend progress covering completed work, pending dependencies, and next steps.',
        ];

        return sprintf(
            '<p><strong>%s update:</strong> %s</p>',
            e($jobTitle),
            e($reports[$slotIndex])
        );
    }

    private function challengeHtml(int $slotIndex): ?string
    {
        $challenges = [
            '<p>No major blockers this week.</p>',
            '<p>Waiting on final clarification for one cross-team dependency.</p>',
            '<p>Late submission caused by a delayed internal review.</p>',
            '<p>Some tasks carried into Saturday because of external feedback delays.</p>',
        ];

        return $challenges[$slotIndex] ?? null;
    }
}
