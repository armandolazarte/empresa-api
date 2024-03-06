<?php

namespace Database\Seeders;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\ArticleHelper;
use App\Http\Controllers\StockMovementController;
use App\Models\Article;
use App\Models\ArticleDiscount;
use App\Models\Category;
use App\Models\Description;
use App\Models\Image;
use App\Models\Provider;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{


    public $images = [
        'cubo' => 'http://empresa.local:8000/storage/cubo.jpeg',
        'cadena' => 'http://empresa.local:8000/storage/cadena.jpg',
        'mochila' => 'http://empresa.local:8000/storage/mochila.jpg',
        'martillo' => 'http://empresa.local:8000/storage/martillo.jpg',
    ];

    public function run()
    {
        $this->lucas();
    }

    function lucas() {
        $user = User::where('company_name', 'Autopartes Boxes')
                    ->first();
        $bsas = Provider::where('user_id', $user->id)
                            ->where('name', 'Buenos Aires')
                            ->first();
        $rosario = Provider::where('user_id', $user->id)
                            ->where('name', 'Rosario')
                            ->first();

        // require(database_path().'\seeders\articles\ferreteria.php');
        require(database_path().'\seeders\articles\auto_partes.php');

        $num = 1;
        $days = count($articles);
        // $id = 500000;
        // for ($i=0; $i < 4; $i++) { 
            foreach ($articles as $article) {
                $art = Article::create([
                    'num'                   => $num,
                    'bar_code'              => $article['bar_code'],
                    'provider_code'         => $article['provider_code'],
                    'name'                  => $article['name'],
                    'slug'                  => ArticleHelper::slug($article['name'], $user->id),
                    // 'cost'               => 50,
                    // 'cost'               => rand(1000, 100000),
                    'cost'                  => rand(10, 1000),
                    'costo_mano_de_obra'    => isset($article['costo_mano_de_obra']) ? $article['costo_mano_de_obra'] : null,
                    'status'                => isset($article['status']) ? $article['status'] : 'active',
                    'featured'              => isset($article['featured']) ? $article['featured'] : null,
                    // 'stock'              => $article['stock'] ,
                    'provider_id'           => isset($article['provider_id']) ? $article['provider_id'] : null,
                    'percentage_gain'       => isset($article['percentage_gain']) ? $article['percentage_gain'] : null,
                    'iva_id'                => isset($article['iva_id']) ? $article['iva_id'] : null,
                    'featured'              => isset($article['featured']) ? $article['featured'] : null,
                    // 'apply_provider_percentage_gain'     => 0,
                    'apply_provider_percentage_gain'    => 1,
                    'price'                 => isset($article['price']) ? $article['price'] : null,
                    'default_in_vender'     => isset($article['default_in_vender']) ? $article['default_in_vender'] : null,
                    'category_id'           => $this->getCategoryId($user, $article),
                    'sub_category_id'       => $this->getSubcategoryId($user, $article),
                    'created_at'            => Carbon::now()->subDays($days),
                    'updated_at'            => Carbon::now()->subDays($days),
                    'user_id'               => $user->id,
                ]);    
                $art->timestamps = false;
                $days--;
                $num++;
                // $id+;
                if (isset($article['images'])) {
                    foreach ($article['images'] as $image) { 
                        Image::create([
                            'imageable_type'                            => 'article',
                            'imageable_id'                              => $art->id,
                            env('IMAGE_URL_PROP_NAME', 'image_url')     => $image['url'],
                            // env('IMAGE_URL_PROP_NAME', 'image_url')     => env('APP_URL').'/storage/'.$image['url'],
                            'color_id'                                  => isset($image['color_id']) ? $image['color_id'] : null,
                        ]);
                    }    
                }
                if (isset($article['provider_id'])) {
                    $art->providers()->attach($article['provider_id'], [
                                                'cost'  => $article['cost'],
                                                'amount' => $article['stock'],
                                            ]);
                }
                $this->createDescriptions($art, $article); 
                $this->setColors($art, $article); 
                // $this->setAddresses($art, $article); 
                ArticleHelper::setFinalPrice($art, $user->id);
                $this->setStockMovement($art, $article);
                // ArticleHelper::setArticleStockFromAddresses($art);
            }
        // }
    }

    function setStockMovement($created_article, $article) {
        $ct = new StockMovementController();
        $request = new \Illuminate\Http\Request();
        
        $request->model_id = $created_article->id;
        $request->provider_id = $created_article->provider_id;

        if (isset($article['addresses'])) {
            foreach ($article['addresses'] as $address) {
                $request->to_address_id = $address['id'];
                $request->amount = $address['amount'];
                $request->from_create_article_addresses = true;
                $ct->store($request);
                sleep(1);
            }
        } else {
            $request->amount = $article['stock'];
            $ct->store($request);
        }
    }

    function createDiscount($article) {
        ArticleDiscount::create([
            'percentage' => '10',
            'article_id' => $article->id,
        ]);
        ArticleDiscount::create([
            'percentage' => '20',
            'article_id' => $article->id,
        ]);
    }

    function setColors($article, $_article) {
        if (isset($_article['colors'])) {
            foreach ($_article['colors'] as $color) {
                $article->colors()->attach($color['id'], [
                    'amount'    => $color['amount'],
                ]);
            }
        }
    }

    function setAddresses($article, $_article) {
        if (isset($_article['addresses'])) {
            foreach ($_article['addresses'] as $address) {
                $article->addresses()->attach($address['id'], [
                    'amount'    => $address['amount'],
                ]);
            }
        }
    }

    function getCategoryId($user, $article) {
        if (isset($article['category_name'])) {
            $category = Category::where('user_id', $user->id)
                                    ->where('name', $article['category_name'])
                                    ->first();
            if (!is_null($category)) {
                return $category->id;
            }
        }
        if (isset($article['sub_category_name'])) {
            $sub_category = SubCategory::where('user_id', $user->id)
                                        ->where('name', $article['sub_category_name'])
                                        ->first();
            if (!is_null($sub_category)) {
                return $sub_category->category_id;
            }
        }
        return null;
    }

    function getSubcategoryId($user, $article) {
        if (isset($article['sub_category_name'])) {
            $sub_category = SubCategory::where('user_id', $user->id)
                                        ->where('name', $article['sub_category_name'])
                                        ->first();
            return $sub_category->id;
        }
        return null;
    }

    function getColorId($article) {
        if (isset($article['colors']) && count($article['colors']) >= 1) {
            return $article['colors'][0];
        }
        return null;
    }

    function createDescriptions($created_article, $article) {
        if (isset($article['descriptions'])) {
            Description::create([
                'title'      => 'Almacentamiento',
                'content'    => 'Este modelo nos entrega una importante capacidad de almacenamiento.',
                'article_id' => $created_article->id,
            ]);
        }
        return;
        Description::create([
            'title'      => 'Almacentamiento',
            'content'    => 'Este modelo nos entrega una importante capacidad de almacenamiento.',
            'article_id' => $article->id,
        ]);
        Description::create([
            'title'      => 'Pantalla',
            'content'    => 'Tiene una pantalla muy linda',
            'article_id' => $article->id,
        ]);
        Description::create([
            'title'      => 'Materiales',
            'content'    => 'Esta hecho con los mejores materiales de construccion',
            'article_id' => $article->id,
        ]);
    }

    function subcategoryId($user_id, $i) {
        if ($user_id < 3) {
            return rand(1,40);
        } else {
            if ($i <= 10) {
                $sub_category = SubCategory::where('name', 'Iphones')->first();
                return $sub_category->id;
            }
            if ($i > 10 && $i <= 12) {
                $sub_category = SubCategory::where('name', 'Iphon')->first();
                return $sub_category->id;
            }
            if ($i > 12 && $i <= 14) {
                $sub_category = SubCategory::where('name', 'Android')->first();
                return $sub_category->id;
            }
            if ($i > 14 && $i <= 16) {
                $sub_category = SubCategory::where('name', 'Casco')->first();
                return $sub_category->id;
            }
            if ($i > 16 && $i <= 18) {
                $sub_category = SubCategory::where('name', 'Comunes')->first();
                return $sub_category->id;
            }
        }
    }

}
