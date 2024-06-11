<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CommonLaravel\Helpers\UserHelper;
use App\Http\Controllers\Helpers\ArticleHelper;
use App\Http\Controllers\Helpers\CartArticleAmountInsificienteHelper;
use App\Http\Controllers\Helpers\InventoryLinkageHelper;
use App\Models\Article;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockMovementController extends Controller
{

    function index($article_id) {
        $models = StockMovement::where('article_id', $article_id)
                                ->orderBy('created_at', 'DESC')
                                // ->select('temporal_id', 'article_id', 'from_address_id', 'to_address_id', 'provider_id', 'sale_id', 'nota_credito_id', 'concepto', 'observations', 'amount', 'employee_id', 'user_id')
                                ->get();
                                
        return response()->json(['models' => $models], 200);
    }

    function store(Request $request, $set_updated_at = true) {
        $this->set_updated_at = $set_updated_at;
        $this->request = $request;
        $this->article_id = $request->model_id;
        $this->article = Article::find($this->article_id);
        $this->stock_movement = StockMovement::create([
            'temporal_id'       => $this->getTemporalId($request),
            'article_id'        => $request->model_id,
            'amount'            => $request->amount,
            'concepto'          => isset($request->concepto) && !is_null($request->concepto) ? $request->concepto : null,
            'sale_id'           => isset($request->sale_id) && $request->sale_id != 0 ? $request->sale_id : null,
            'nota_credito_id'   => isset($request->nota_credito_id) && $request->nota_credito_id != 0 ? $request->nota_credito_id : null,
            'provider_id'       => $request->amount >= 1 && isset($request->provider_id) && $request->provider_id != 0 ? $request->provider_id : null,
            'from_address_id'   => $this->getFromAddressId(),
            'to_address_id'     => $this->getToAddressId(),
            'observations'      => isset($request->observations) ? $request->observations : '',
            'employee_id'       => UserHelper::userId(false),
            'user_id'           => UserHelper::userId(),
        ]);


        // if (!is_null($this->article)) {
        //     Log::info('StockMovement para '.$this->article->name);
        //     Log::info('Stock previo '.$this->article->stock);
        // }
        // Log::info('Cantidad del Movimiento '.$this->stock_movement->amount);

        $this->setConcepto();

        $this->setArticleStock();

        $this->setStockResultante();

        // Log::info('Stock resultante '.$this->stock_movement->stock_resultante);

        $this->setArticleProvider();

        $this->sendUpdateNotification();

        if (!is_null($this->article)) {
            // Log::info('Se creo stock_movement para '.$this->article->name.' con '.$this->stock_movement->amount);
        }
        
        // Log::info('------------------------------------------------');
        return response()->json(['model' => $this->stock_movement], 201);
    }

    function setStockResultante() {
        if (!is_null($this->article)) {

            $stock_movement_anterior = StockMovement::where('article_id', $this->article->id)
                                                    ->orderBy('created_at', 'DESC')
                                                    ->where('id', '!=', $this->stock_movement->id)
                                                    ->first();

            if (!is_null($stock_movement_anterior)) {

                $stock_resultante = (float)$stock_movement_anterior->stock_resultante + (float)$this->stock_movement->amount;

                $this->stock_movement->stock_resultante = $stock_resultante;

            } else {
                $this->stock_movement->stock_resultante = $this->stock_movement->amount;
            }

            $this->stock_movement->save();
        } else {
            $this->stock_movement->stock_resultante = $this->stock_movement->amount;
            $this->stock_movement->save();
        }

        $this->set_stock_actual_in_observations();

    }

    function set_stock_actual_in_observations() {

        if (!is_null($this->stock_movement->observations)) {
            $this->stock_movement->observations .= ' '.$this->article->stock;
        } else {
            $this->stock_movement->observations = $this->article->stock;
        }
        $this->stock_movement->save();

    }

    function getFromAddressId() {
        if (isset($this->request->from_address_id) && $this->request->from_address_id != 0 && $this->articleHasAddresses()) {
            return $this->request->from_address_id;
        }
        return null;
    }

    function getToAddressId() {
        if (isset($this->request->to_address_id) && $this->request->to_address_id != 0 && $this->articleHasAddresses()) {
            return $this->request->to_address_id;
        }
        return null;
    }

    function articleHasAddresses() {
        if (is_null($this->article) || count($this->article->addresses) >= 1 || isset($this->request->from_create_article_addresses)) {
            // Log::info('Tiene articleHasAddresses');
            return true;
        }
        // Log::info('isset '.isset($this->request->from_create_article_addresses));
        return false;
    }

    function sendUpdateNotification() {
        if (!is_null($this->article) && !is_null(Auth()->user()) && (!isset($this->request->from_excel_import))) {
            $this->sendAddModelNotification('Article', $this->article->id, false);
        }
    }

    function setConcepto() {
        if (is_null($this->stock_movement->concepto)) {
            if (isset($this->request->from_excel_import)) {
                $this->stock_movement->concepto = 'Importacion Excel';
            } else if (!is_null($this->stock_movement->provider_id)) {
                $this->stock_movement->concepto = 'Compra a proveedor';
            } else if (!is_null($this->stock_movement->sale_id)) {
                if (!is_null($this->stock_movement->to_address_id)) {
                    $this->stock_movement->concepto = 'Eliminacion de Venta N° '.$this->stock_movement->sale->num;
                } else {
                    $this->stock_movement->concepto = 'Venta N° '.$this->stock_movement->sale->num;
                }
            } else if (!is_null($this->stock_movement->from_address_id)) {
                $this->stock_movement->concepto = 'Movimiento de depositos';
            } else if (!is_null($this->stock_movement->amount) && $this->stock_movement->amount < 0) {
                $this->stock_movement->concepto = 'Resta de Stock';
            }
            $this->stock_movement->save();
        }
    }

    function setArticleStock() {
        if (!is_null($this->article)) {
            $this->checkFromAddress();

            $this->checkToAddress();

            $this->checkGlobalStock();
    
            ArticleHelper::checkAdvises($this->article);

            ArticleHelper::setArticleStockFromAddresses($this->article);

            if ($this->stock_movement->concepto != 'Movimiento de depositos') {
                CartArticleAmountInsificienteHelper::checkCartsAmounts($this->article);
            }
        } 
    }

    function setArticleProvider() {
        if (!is_null($this->article) && !is_null($this->stock_movement->provider)
            && !isset($this->request->not_save_provider)) {
            $this->article->provider_id = $this->stock_movement->provider_id;
            $this->article->save();
        }
    }

    function checkGlobalStock() {

        if (is_null($this->article->stock)) {
            $this->article->stock = 0;
            $this->article->save();
        }

        if (!is_null($this->article->stock) && !count($this->article->addresses) >= 1) {

            /*
                Se aumenta el stock del articulo con la amount del stock_movement
                Ya que, si es una venta, amount va a ser negativo
            */
                
            $this->article->stock += (float)$this->stock_movement->amount;

            if (!$this->set_updated_at) {
                $this->article->timestamps = false;
            }
            
            $this->article->save();

            $ct = new InventoryLinkageHelper();
            $ct->check_is_agotado($this->article);
        }
    }


    /*
        *   Aca se aumenta el stock del deposito destino
            En caso de que sea:

            * Modificacion de stock (desde el modal de article)
            * Un pedido de proveedor
            * Movimiento de depositos
            * Importacion de excel
    */
    function checkToAddress() {

        if (!is_null($this->stock_movement->to_address_id) && $this->articleHasAddresses()) {
            
            $this->article->load('addresses');
            
            $to_address = null;
            foreach ($this->article->addresses as $address) {
                if ($address->id == $this->stock_movement->to_address_id) {
                    $to_address = $address;
                }
            }
            
            if (is_null($to_address)) {

                /*
                    Si la direccion destino es null  
                    se le attach esa direccion y se le pone como cantidad inicial la cantidad del movimineto 
                    de deposito
                    Ya que siempre va a ser positiva
                */
                $this->article->addresses()->attach($this->stock_movement->to_address_id, [
                    'amount'    => $this->stock_movement->amount,
                ]);

            } else {

                $new_amount = $to_address->pivot->amount + $this->stock_movement->amount;
                $this->article->addresses()->updateExistingPivot($this->stock_movement->to_address_id, [
                    'amount'    => $new_amount,
                ]);
            }
        }
    }


    /*
        *   Aca se descuenta el stock del deposito de origen
            En caso de que sea:

            * Una venta
            * Un movimiento de deposito
            * Movimiento de produccion
    */
    function checkFromAddress() {
        if (!is_null($this->stock_movement->from_address_id) && count($this->article->addresses) >= 1) {
            
            $this->article->load('addresses');

            Log::info('checkFromAddress addresses: ');
            
            $from_address = null;

            foreach ($this->article->addresses as $address) {
                Log::info($address->street.': '.$address->pivot->amount);
                if ($address->id == $this->stock_movement->from_address_id) {
                    $from_address = $address;
                }
            }

            if (!is_null($from_address)) {

                /* 
                    Ahora se va a sumar la cantidad
                    Porque si es una venta, va a ser un valor negativo
                */
                $new_amount = (float)$from_address->pivot->amount + (float)$this->stock_movement->amount;
                // $new_amount = (float)$from_address->pivot->amount - (float)$this->stock_movement->amount;

                Log::info('from_address: '.$from_address->pivot->amount);
                Log::info('+ : '.$this->stock_movement->amount);

                $this->article->addresses()->updateExistingPivot($from_address->id, [
                    'amount'    => $new_amount,
                ]);
            }
        }
    }
}
