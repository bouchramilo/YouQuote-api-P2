<?php
namespace Database\Factories;

use App\Models\Category;
use App\Models\Quote;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;



    public function definition()
    {
        $content = $this->faker->paragraph();

        return [
            'content'    => $content,
            'user_id'    => User::inRandomOrder()->first()?->id ?? User::factory(),
            'is_valide'  => $this->faker->boolean(70),
            'popularite' => $this->faker->numberBetween(0, 100),
            'nbr_mots'   => str_word_count($content),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Quote $quote) {
            $categories = Category::inRandomOrder()->take(rand(1, 2))->pluck('id');
            $tags = Tag::inRandomOrder()->take(rand(1, 3))->pluck('id');

            $quote->categories()->attach($categories);
            $quote->tags()->attach($tags);
        });
    }
}
