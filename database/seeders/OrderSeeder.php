<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where('company_name', 'lucas')->first();
        $buyer = Buyer::where('user_id', $user->id)->first();
        $models = [
            [
                'buyer_id'          => $buyer->id,
                'order_status_id'   => 1,
                'deliver'           => 0,
                // 'created_at'        => Carbon::now(),
                'created_at'        => Carbon::now()->subDays(2),
            ],
            [
                'buyer_id'          => $buyer->id,
                'order_status_id'   => 2,
                'deliver'           => 0,
                'created_at'        => Carbon::now(),
            ],
        ];
        foreach ($models as $model) {
            $order = Order::create([
                'num'                   => 1,
                'buyer_id'              => $model['buyer_id'],
                'order_status_id'       => $model['order_status_id'],
                'deliver'               => $model['deliver'],
                'created_at'            => $model['created_at'],
                'user_id'               => $user->id,
            ]);
            $articles = Article::where('user_id', $user->id)
                                ->get();
            foreach ($articles as $article) {
                $order->articles()->attach($article->id, [
                    'amount'    => 2,
                    'price'     => $article->final_price,
                ]); 
             }     
        }
    }
}
