<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\User;
use Illuminate\Database\Seeder;

class BuyerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('company_name', 'Autopartes Boxes')
                        ->get();
        foreach ($users as $user) {
            $lucas = Buyer::create([
                'num'                       => 1,
                'name'                      => 'Lucas',
                'surname'                   => 'Gonzalez',
                'city'                      => 'Gualeguay',
                'phone'                     => '+5493444622139',
                'email'                     => 'lucasgonzalez5500@gmail.com',
                'password'                  => bcrypt('1234'),
                'comercio_city_client_id'   => 1,
                'user_id'                   => $user->id,
            ]);
            $marcos = Buyer::create([
                'num'                       => 1,
                'name'                      => 'Marcos',
                'surname'                   => 'Gonzalez',
                'city'                      => 'Gualeguay',
                'phone'                     => '+5493444622139',
                'email'                     => 'lucasgonzalez210200@gmail.com',
                'password'                  => bcrypt('1234'),
                'comercio_city_client_id'   => 1,
                'user_id'                   => $user->id,
            ]);
        }
    }
}
