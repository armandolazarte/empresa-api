<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Helpers\ArticleHelper;
use App\Http\Controllers\Helpers\GeneralHelper;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecipeHelper {

	static function attachArticles($recipe, $articles) {
		$recipe->articles()->sync([]);
		Log::info('vinieron '.count($articles).' articulos a recipe');
		foreach ($articles as $article) {
			Log::info($article['name']);
			if ($article['status'] == 'inactive') {
				$art = Article::find($article['id']);
				$art->bar_code = $article['bar_code'];
				$art->provider_code = $article['provider_code'];
				$art->name = $article['name'];
				$art->save();
			} 
			$recipe->articles()->attach($article['id'], [
											'amount' 	=> GeneralHelper::getPivotValue($article, 'amount'),
											'notes' 	=> GeneralHelper::getPivotValue($article, 'notes'),
											'address_id' 	=> GeneralHelper::getPivotValue($article, 'address_id'),
											'order_production_status_id' => GeneralHelper::getPivotValue($article, 'order_production_status_id'),
										]);
		}
	}

	static function checkCostFromRecipe($recipe, $instance, $user_id = null) {
		if ($recipe->article_cost_from_recipe) {
			$cost = 0;
			foreach ($recipe->articles as $article) {
				$mano_de_obra = 0;
				$materiales = (float)$article->final_price * (float)$article->pivot->amount;

				if (!is_null($article->costo_mano_de_obra)) {
					$mano_de_obra = (float)$article->costo_mano_de_obra * (float)$article->pivot->amount;
				}
				$cost += $materiales + $mano_de_obra;
			}
			$article = $recipe->article;
			$article->cost = $cost;
			$article->save();
			ArticleHelper::setFinalPrice($article, $user_id);
        	// $instance->sendAddModelNotification('Article', $article->id, false);
		}
	}

}