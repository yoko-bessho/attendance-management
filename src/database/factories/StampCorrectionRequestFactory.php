<?php

namespace Database\Factories;

use App\Models\StampCorrectionRequest;
use App\Models\User;
use App\Models\Attendance;
use App\Enums\StampCorrectionRequestsStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class StampCorrectionRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StampCorrectionRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $attendance = Attendance::factory()->create();
        $date = $attendance->worked_at;

        return [
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'request_date' => $date,
            'reason' => $this->faker->realText(50),
            'revised_start_time' => $date->copy()->setTime(9, 0, 0),
            'revised_end_time' => $date->copy()->setTime(18, 0, 0),
            'revised_breaks' => null,
            'status' => StampCorrectionRequestsStatus::PENDING,
        ];
    }
}
