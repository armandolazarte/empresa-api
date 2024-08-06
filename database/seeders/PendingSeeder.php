<?php

namespace Database\Seeders;

use App\Models\Pending;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PendingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $user = User::where('company_name', 'Autopartes Boxes')->first();

        $hace_un_mes = Carbon::now()->subMonth();

        $models = [
            [
                'detalle'                => 'Pagar impuestos',
                'fecha_realizacion'      => $hace_un_mes->subWeeks(2)->startOfWeek(),
                'es_recurrente'          => 1,
                'unidad_frecuencia_id'   => 2,
                'cantidad_frecuencia'    => 2,
                'expense_concept_id'     => 2,
                'notas'                  => 'Mandar comporbante de pago',
                'created_at'             =>  $hace_un_mes,
            ],
        ];

        foreach ($models as $model) {
            
            $model['user_id'] = $user->id;

            Pending::create($model);
        }
    }
}
