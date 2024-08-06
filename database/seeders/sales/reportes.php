<?php 

use Carbon\Carbon;

$ventas_desde_principio_de_mes = [];

for ($dia_del_mes = 0; $dia_del_mes < Carbon::now()->day ; $dia_del_mes++) { 

	for ($employee_id=503; $employee_id <= 504; $employee_id++) { 

		if ($employee_id == 503) {

			$price_vender = 1000;
			$address_id = 1;
		} else {

			$price_vender = 2000;
			$address_id = 2;
		}

		$amount = $dia_del_mes + 1;
		$total = $price_vender * $amount;

		$ventas_desde_principio_de_mes[] = [
			'num'				=> 1,
			'employee_id'		=> $employee_id,
			'address_id'		=> $address_id,
			'client_id'			=> null,
			'articles'			=> [
				[
					'id'			=> 1,
					'price_vender'	=> $price_vender,
					'amount'		=> $amount,
				],
			],
			'payment_methods'	=> [
				[
					'id'		=> 1,
					'amount'	=> $total / 2,
				],
				[
					'id'		=> 2,
					'amount'	=> $total / 2,
				],
			],
			'created_at'	=> Carbon::now()->startOfMonth()->addDays($dia_del_mes),
		];
	}

}





$ventas_meses_anterioires = [];

for ($mes=4; $mes > 1; $mes--) { 

	for ($dia_del_mes = 0; $dia_del_mes < 6 ; $dia_del_mes++) { 

		$price_vender = 1000;
		$amount = $dia_del_mes + 1;
		$total = $price_vender * $amount;

		$ventas_meses_anterioires[] = [
			'num'				=> 1,
			'employee_id'		=> 504,
			'address_id'		=> 2,
			'client_id'			=> null,
			'articles'			=> [
				[
					'id'			=> 1,
					'price_vender'	=> $price_vender,
					'amount'		=> $amount,
				],
			],
			'payment_methods'	=> [
				[
					'id'		=> 1,
					'amount'	=> $total / 2,
				],
				[
					'id'		=> 2,
					'amount'	=> $total / 2,
				],
			],
			'created_at'	=> Carbon::now()->startOfMonth()->subMonths($mes)->addDays($dia_del_mes),
		];
	}
}

