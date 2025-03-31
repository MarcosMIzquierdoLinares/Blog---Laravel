<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as FakerFactory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = FakerFactory::create('es_ES');
        $status = $faker->randomElement(['published', 'draft', 'deleted'], [60, 30, 10]);

        $titles = require database_path('factories\post_titles.php');
        $contents = require database_path('factories\post_content.php');
        
        $id_categories = $faker->numberBetween(1, 10); // coge un id de una categoria y va rellenado de los arrays creados en posttiles y postcontent
        $title = $faker->randomElement($titles[$id_categories]); 
        $content = $contents[$id_categories];

        return [
            'id_categories' => $id_categories,
            'user_id' => null, // se asignará después
            'title' => $title,  // entre 50 60 caracs
            'content' => json_encode([
                'type' => 'yoopta',
                'content' => $content
            ]), 
            'status' => $status,
            'views' => in_array($status, ['published', 'deleted']) ? $faker->numberBetween(0, 200) : 0, // asigna vistas a published y deleted
            'created_at' => $faker->dateTimeBetween('2024-01-01', 'now')->format('Y-m-d H:i:s'), // genera fecha aleatoria desde enero de 2024 hasta la fecha actual
        ];
    }
}
