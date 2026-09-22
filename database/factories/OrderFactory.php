<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'region_id' => $this->faker->numberBetween(1, 10),
            'merchant_id' => $this->faker->numberBetween(1, 10),
            'tracking_code' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_phone_alt' => $this->faker->optional()->phoneNumber(),
            'order_description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['pickup', 'delivery']),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'online']),
            'is_customer_paid' => $this->faker->boolean(),
            'pickup_address' => $this->faker->address(),
            'pickup_gps_link' => $this->faker->url(),
            'delivery_address' => $this->faker->address(),
            'delivery_gps_link' => $this->faker->url(),
            'delivery_cost' => $this->faker->randomFloat(2, 5, 50),
            'total_amount' => $this->faker->randomFloat(2, 10, 100),
        ];
    }
}
