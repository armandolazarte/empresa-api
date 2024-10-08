<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Helpers\SaleHelper;
use App\Models\CurrentAcount;
use App\Models\CurrentAcountPaymentMethod;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CajaReportsHelper {
	
	static function reports($instance, $from_date, $until_date, $employee_id) {
		$ingresos = Self::ingresos($instance, $from_date, $until_date, $employee_id);
		$egresos = Self::egresos($instance, $from_date, $until_date);
		$devoluciones = Self::devoluciones($instance, $from_date, $until_date);

		return [
			'ingresos' 			=> $ingresos,
			'egresos' 			=> $egresos,
			'devoluciones' 		=> $devoluciones,
		];

	}

	static function ingresos($instance, $from_date, $until_date, $employee_id) {
		$sale_payment_methods = [];
		$total = 0;

		$payment_methods = CurrentAcountPaymentMethod::all();

		foreach ($payment_methods as $payment_method) {
			$sale_payment_methods[$payment_method->name] = 0;
		}

		$sales = Sale::where('user_id', $instance->userId())
                        ->orderBy('created_at', 'ASC')
                        ->where('terminada', 1);
        if ($until_date != 0) {
            $sales = $sales->whereDate('created_at', '>=', $from_date)
                            ->whereDate('created_at', '<=', $until_date);
        } else {
            $sales = $sales->whereDate('created_at', $from_date);
        }

        if ($employee_id != 'todos') {
        	$sales = $sales->where('employee_id', $employee_id);
        }
		$sales = $sales->get();
		foreach ($sales as $sale) {

			/* 
				si omitir_en_cuenta_corriente es true, es porque puede
				que la venta se alla omitido en cuenta corriente, pero este facturada y 
				se alla querido eliminar, entonces se el adjunta una nota de creito.
				Por eso es que podria tener una cuenta corriente
			*/
			if (is_null($sale->current_acount) || $sale->omitir_en_cuenta_corriente) {
				if (is_null($sale->current_acount_payment_method)) {
					if (count($sale->current_acount_payment_methods) >= 1) {
						foreach ($sale->current_acount_payment_methods as $current_acount_payment_method) {
							$amount = (float)$current_acount_payment_method->pivot->amount;

							if (!is_null($current_acount_payment_method->pivot->discount_amount)) {
								$amount -= (float)$current_acount_payment_method->pivot->discount_amount;
							}
							$sale_payment_methods[$current_acount_payment_method->name] += $amount;
						}
					} else {
						$sale_payment_methods['Efectivo'] += SaleHelper::getTotalSale($sale);
					}
				} else {
					$sale_payment_methods[$sale->current_acount_payment_method->name] += SaleHelper::getTotalSale($sale);
				}
			}
    	}

		$current_acounts = CurrentAcount::where('user_id', $instance->userId())
										->whereNotNull('haber')
                                		->where('status', 'pago_from_client')
										->whereNotNull('client_id');
		if ($until_date != 0) {
            $current_acounts = $current_acounts->whereDate('created_at', '>=', $from_date)
                            					->whereDate('created_at', '<=', $until_date);
        } else {
            $current_acounts = $current_acounts->whereDate('created_at', $from_date);
        }				
        if ($employee_id != 'todos') {
        	$current_acounts = $current_acounts->where('employee_id', $employee_id);
        }			
		$current_acounts = $current_acounts->get();
		foreach ($current_acounts as $current_acount) {
			if (count($current_acount->current_acount_payment_methods) >= 1) {
				foreach ($current_acount->current_acount_payment_methods as $current_acount_payment_method) {
					$sale_payment_methods[$current_acount_payment_method->name] += $current_acount_payment_method->pivot->amount;
				}
			} else {
				$sale_payment_methods['Efectivo'] += $current_acount->haber;
				// $total += $current_acount->haber;
			}
		}

		$result = [];
		foreach ($sale_payment_methods as $key => $value) {
			$result[] = [
				'payment_method' => $key,
				'total' => $value,
			];
			$total += $value;
		}
		return [
			'payment_methods' 	=> $result,
			'total'				=> $total,
		];
	}

	static function egresos($instance, $from_date, $until_date) {
		$sale_payment_methods = [];
		$total = 0;

		$payment_methods = CurrentAcountPaymentMethod::all();

		foreach ($payment_methods as $payment_method) {
			$sale_payment_methods[$payment_method->name] = 0;
		}

		$current_acounts = CurrentAcount::where('user_id', $instance->userId())
										->whereNotNull('haber')
										->whereNotNull('provider_id');
		if (!is_null($until_date) && $until_date != 0) {
            $current_acounts = $current_acounts->whereDate('created_at', '>=', $from_date)
                            					->whereDate('created_at', '<=', $until_date);
        } else {
            $current_acounts = $current_acounts->whereDate('created_at', $from_date);
        }							
		$current_acounts = $current_acounts->get();
		foreach ($current_acounts as $current_acount) {
			if (count($current_acount->current_acount_payment_methods) >= 1) {
				foreach ($current_acount->current_acount_payment_methods as $current_acount_payment_method) {
					Log::info('sumando '.$current_acount_payment_method->pivot->amount.' a '.$current_acount_payment_method->name);
					$sale_payment_methods[$current_acount_payment_method->name] += $current_acount_payment_method->pivot->amount;
					$total += $current_acount_payment_method->pivot->amount;
				}
			} else {
				$sale_payment_methods['Efectivo'] += $current_acount->haber;
				$total += $current_acount->haber;
			}
		}

		$result = [];
		foreach ($sale_payment_methods as $key => $value) {
			$result[] = [
				'payment_method' => $key,
				'total' => $value,
			];
		}
		return [
			'payment_methods' 	=> $result,
			'total'				=> $total,
		];
	}

	static function devoluciones($instance, $from_date, $until_date) {
		
		$total = 0;

		$notas_de_credito = CurrentAcount::where('user_id', $instance->userId())
											->where('status', 'nota_credito');
		if (!is_null($until_date) && $until_date != 0) {
            $notas_de_credito = $notas_de_credito->whereDate('created_at', '>=', $from_date)
                            					->whereDate('created_at', '<=', $until_date);
        } else {
            $notas_de_credito = $notas_de_credito->whereDate('created_at', $from_date);
        }							
		$notas_de_credito = $notas_de_credito->get();

		foreach ($notas_de_credito as $nota_credito) {
			$total += $nota_credito->haber;
		}

		return $total;
	}

}