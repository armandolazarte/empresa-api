<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where('company_name', 'Autopartes Boxes')->first();
        $article = Article::where('user_id', $user->id)
                            ->where('name', 'Prensa Espirales Universal')
                            ->first();
        $recipe = Recipe::create([
            'num'           => 1,
            'article_id'    => $article->id,
            'address_id'    => 1,
            'user_id'       => $user->id,
        ]);
        $articles = [
            [
                'name'                          => 'Kit de Cazoleta con rodamiento, Tope Y Fuelle AX044.1488',
                'order_production_status_id'    => 1,
                'address_id'                    => 1,
                'amount'                        => 1,
            ],
            [
                'name'                          => 'Kit de Cazoleta con rodamiento Tope Y Fuelle AX044.1654',
                'order_production_status_id'    => 1,
                'address_id'                    => 1,
                'amount'                        => 1,
            ],
            [
                'name'                          => 'Kit de Buje, Tope Y Fuelle Amortiguador AX044.1142',
                'order_production_status_id'    => 1,
                'address_id'                    => 1,
                'amount'                        => 1,
            ],
            [
                'name'                          => 'Axial Cremallera MO-0064',
                'order_production_status_id'    => 2,
                'address_id'                    => 1,
                'amount'                        => 1,
            ],
        ];
        foreach ($articles as $article) {
            $art = Article::where('user_id', $user->id)
                            ->where('name', $article['name'])
                            ->first();
            $recipe->articles()->attach($art->id, [
                                    'order_production_status_id'    => $article['order_production_status_id'],
                                    'address_id'                    => $article['address_id'],
                                    'amount'                        => $article['amount'],
                                ]);
        }
    }
}
