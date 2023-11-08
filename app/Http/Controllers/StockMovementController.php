<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Helpers\ArticleHelper;
use App\Models\Article;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StockMovementController extends Controller
{

    function index($article_id) {
        $models = StockMovement::where('article_id', $article_id)
                                ->orderBy('created_at', 'DESC')
                                ->get();
        return response()->json(['models' => $models], 200);
    }

    function store(Request $request) {
        $this->request = $request;
        $this->stock_movement = StockMovement::create([
            'temporal_id'       => $this->getTemporalId($request),
            'article_id'        => $request->model_id,
            'amount'            => $request->amount,
            'concepto'          => isset($request->concepto) && !is_null($request->concepto) ? $request->concepto : null,
            'sale_id'           => isset($request->sale_id) && $request->sale_id != 0 ? $request->sale_id : null,
            'provider_id'       => $request->amount >= 1 && isset($request->provider_id) && $request->provider_id != 0 ? $request->provider_id : null,
            'from_address_id'   => $this->getFromAddressId($request),
            'to_address_id'     => $this->getToAddressId($request),
            'observations'     => isset($request->observations) && $request->observations != 0 ? $request->observations : null,
        ]);

        $this->article = $this->stock_movement->article;

        $this->setConcepto();

        $this->setArticleStock();

        $this->setArticleProvider();

        $this->sendUpdateNotification($request);

        if (!is_null($this->article)) {
            Log::info('Se creo stock_movement para '.$this->article->name.' con '.$this->stock_movement->amount);
        }
        
        return response()->json(['model' => $this->stock_movement], 201);
    }

    function getFromAddressId($request) {
        if (isset($request->from_address_id) && $request->from_address_id != 0 && $this->articleHasAddresses($request->model_id)) {
            return $request->from_address_id;
        }
        return null;
    }

    function getToAddressId($request) {
        if (isset($request->to_address_id) && $request->to_address_id != 0 && $this->articleHasAddresses($request->model_id)) {
            return $request->to_address_id;
        }
        return null;
    }

    function articleHasAddresses($article_id) {
        $article = Article::find($article_id);
        if (is_null($article) || count($article->addresses) >= 1) {
            return true;
        }
        return false;
    }

    function sendUpdateNotification($request) {
        if (!is_null($this->article) && !is_null(Auth()->user()) && (!isset($request->from_excel_import))) {
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
            }
            $this->stock_movement->save();
        }
    }

    function setArticleStock() {
        if (!is_null($this->article)) {
            $this->checkFromAddress();

            $this->checkToAddress();

            $this->checkGlobalStock();

            ArticleHelper::setArticleStockFromAddresses($this->article);
        } 
    }

    function setArticleProvider() {
        if (!is_null($this->article) && !is_null($this->stock_movement->provider)) {
            $this->article->provider_id = $this->stock_movement->provider_id;
            $this->article->save();
        }
    }

    function checkGlobalStock() {
        if (!is_null($this->article->stock) && !count($this->article->addresses) >= 1) {
            if (!is_null($this->stock_movement->sale)) {
                Log::info('Descontando stock global por venta');
                $this->article->stock -= $this->stock_movement->amount;
            } else {
                $this->article->stock += $this->stock_movement->amount;
            }
            $this->article->save();
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
        if (!is_null($this->stock_movement->to_address_id) && count($this->article->addresses) >= 1) {
            Log::info('checkToAddress para '.$this->article->name);
            $to_address = null;
            foreach ($this->article->addresses as $address) {
                if ($address->id == $this->stock_movement->to_address_id) {
                    $to_address = $address;
                }
            }
            if (is_null($to_address)) {
                $this->article->addresses()->attach($this->stock_movement->to_address_id, [
                    'amount'    => $this->stock_movement->amount,
                ]);
                Log::info('Se agrego la direccion '.$this->stock_movement->to_address->street.' con la cantidad de '.$this->stock_movement->amount.' al articulo '.$this->article->name);
            } else {
                $new_amount = $to_address->pivot->amount + $this->stock_movement->amount;
                $this->article->addresses()->updateExistingPivot($this->stock_movement->to_address_id,[
                    'amount'    => $new_amount,
                ]);
                Log::info('Se actualizo la direccion '.$this->stock_movement->to_address->street.' al articulo '.$this->article->name. '. Quedo en '.$new_amount);
            }
            Log::info('---------------------------------------');
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
            Log::info('checkFromAddress para '.$this->article->name);
            $from_address = null;
            foreach ($this->article->addresses as $address) {
                if ($address->id == $this->stock_movement->from_address_id) {
                    $from_address = $address;
                }
            }
            if (!is_null($from_address)) {
                $new_amount = (float)$from_address->pivot->amount - (float)$this->stock_movement->amount;

                $this->article->addresses()->updateExistingPivot($from_address->id, [
                    'amount'    => $new_amount,
                ]);
                Log::info('Se actualizo la direccion '.$from_address->street.' de '.$from_address->pivot->amount. ' a '.$new_amount);
            } else {
                Log::info('no se encontro la direccion from_address');
            }
            Log::info('------------------------------------------');
        }
    }
}
