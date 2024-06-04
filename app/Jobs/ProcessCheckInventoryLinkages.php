<?php

namespace App\Jobs;

use App\Http\Controllers\CommonLaravel\Helpers\Numbers;
use App\Http\Controllers\Helpers\ArticleHelper;
use App\Models\Article;
use App\Models\InventoryLinkage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCheckInventoryLinkages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 9999999999;
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
        Log::info('Se llamo al __construct ProcessCheckInventoryLinkages');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $inventory_linkages = InventoryLinkage::where('user_id', $this->user->id)
                                            ->get();

        Log::info('Se llamo al handle ProcessCheckInventoryLinkages');

        $articles = Article::where('user_id', $this->user->id)
                            ->where('status', 'active')
                            ->get();

        Log::info(count($articles).' articulos');
        
        $vuelta = 1;
        $actualizados = 0;
        foreach ($inventory_linkages as $inventory_linkage) {

            $articulos_con_nombre_distintos = [];
            $articulos_con_precio_distintos = [];

            $client_comerciocity_user = $inventory_linkage->client->comercio_city_user;

            foreach ($articles as $article) {

                $vuelta++;

                $client_article = Article::where('provider_article_id', $article->id)
                                        ->where('user_id', $inventory_linkage->client->comercio_city_user_id)
                                        ->first();

                if (is_null($client_article)) {
                    Log::info($inventory_linkage->client->name.' no tiene '.$article->name);
                } else {

                    if ($client_article->name != $article->name || $client_article->cost != $article->final_price) {

                        if ($client_article->cost != $article->final_price) {
                            $articulos_con_precio_distintos[] = $article;

                            $previus_cost = $client_article->cost;
                            
                            $client_article->stock = $article->stock;

                            $client_article->cost = $article->final_price;

                            $client_article->save();

                            ArticleHelper::setFinalPrice($client_article, $inventory_linkage->client->comercio_city_user_id, $client_comerciocity_user);

                            Log::info('Se actualizo el precio de '.$client_article->name.' paso de $'.Numbers::price($previus_cost).' a '.Numbers::price($client_article->cost));

                            $actualizados++;
                        }
                    }

                }


            }

            Log::info('------------------------------------------------------- ');
            Log::info('Se actualizaron '.$actualizados.' articulos');

        }
    }
}
