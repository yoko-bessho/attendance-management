<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $workedAt = $this->faker->dateTimeThisMonth();
        $startTime = (clone $workedAt)->setTime(9, 0, 0);
        $endTime = (clone $workedAt)->setTime(18, 0, 0);

        return [
            'user_id' => User::factory(),
            'worked_at' => $workedAt,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}