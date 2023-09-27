<?php

namespace App\Http\Controllers\Pdf; 

use App\Http\Controllers\CommonLaravel\Helpers\Numbers;
use App\Http\Controllers\CommonLaravel\Helpers\PdfHelper;
use App\Http\Controllers\CommonLaravel\Helpers\StringHelper;
use App\Http\Controllers\Helpers\BudgetHelper;
use App\Http\Controllers\Helpers\ImageHelper;
use App\Http\Controllers\Helpers\UserHelper;
use App\Models\Article;
use Illuminate\Support\Facades\Log;
use fpdf;
require(__DIR__.'/../CommonLaravel/fpdf/fpdf.php');

class ArticlePdf extends fpdf {

	function __construct($ids) {
        set_time_limit(0);
		parent::__construct();
		$this->SetAutoPageBreak(true, 1);
		$this->b = 0;
		$this->line_height = 7;

		$this->setArticles($ids);
		
		$this->user = UserHelper::getFullModel();

		$this->image_width = 45;
		$this->name_width = 110;
		$this->price_width = 50;

		$this->AddPage();
		$this->print();
        $this->Output();
        exit;
	}

	function setArticles($ids) {
		$this->articles = [];
		foreach (explode('-', $ids) as $id) {
			$this->articles[] = Article::find($id);
		}
	}

	function Header() {
		if (env('APP_ENV') == 'local') {
    		$this->Image('https://api.freelogodesign.org/assets/thumb/logo/ad95beb06c4e4958a08bf8ca8a278bad_400.png', 2, 2, 45, 45);
    	} else {
    		$this->Image($this->user->image_url, 2, 2, 45, 45);
    	}

		$this->SetFont('Arial', 'B', 18);

		$this->x = 50;
		$this->y = 20;
		$this->Cell(70, 8, $this->user->company_name, $this->b, 0, 'L');	
		
		$this->SetFont('Arial', '', 14);
		$this->Cell(80, 8, date('d/m/Y'), $this->b, 0, 'R');

		$this->tableHeader();	
	}

	function tableHeader() {
		$this->y = 50;
		$this->x = 2;

		$this->SetFillColor(221,211,211);
		$this->Cell($this->image_width, 8, 'Imagen', 1, 0, 'L');
		$this->Cell($this->name_width, 8, 'Nombre', 1, 0, 'L');
		$this->Cell($this->price_width, 8, 'Precio', 1, 0, 'L');
		$this->y += 9;
	}

	function print() {
		$this->SetFont('Arial', '', 12);
		foreach ($this->articles as $article) {
			$this->printArticle($article);
		}
	}

	function printImage($article) {
        $img_url = $this->getJpgImage($article);
        $this->Image($img_url, 2, $this->y, $this->image_width, $this->image_width);

	}

	function getJpgImage($article) {
		$img_url = $article->images[0]->{env('IMAGE_URL_PROP_NAME', 'image_url')};

        $array = explode('/', $img_url); 
        $array_name = $array[count($array)-1];
        $name = explode('.', $array_name)[0];
        $extencion = explode('.', $array_name)[1];

        if (env('APP_ENV') == 'local') {
        	$jpg_file_url = storage_path().'/app/'.$name.'.jpg';
        } else {
        	$jpg_file_url = storage_path().'/app/public/'.$name.'.jpg';
        }

        if (!file_exists($jpg_file_url)) {
        	Log::info('Se va a crear jpg con el nombre de '.$jpg_file_url);
            $img = imagecreatefromwebp($img_url);
            imagejpeg($img, $jpg_file_url, 100);
        }
        return $jpg_file_url;
	}

	function printArticle($article) {

		if ($this->y >= 270) {
			$this->AddPage();
		}

		if (count($article->images) >= 1) {
			if (env('APP_ENV') == 'local') {
        		$this->printImage($article);
        		// $this->Image('https://api.freelogodesign.org/assets/thumb/logo/ad95beb06c4e4958a08bf8ca8a278bad_400.png', 2, $this->y, $this->image_width, $this->image_width);
        	} else {
        		$this->printImage($article);
        		// $img = imagecreatefromwebp($article->images[0]->{env('IMAGE_URL_PROP_NAME', 'image_url')});
        	}
		}

		$this->x = 50;
		$this->MultiCell($this->name_width, 8, $article->name, $this->b, 'L', false);

		$this->y -= 8;
		$this->x += $this->name_width + 35;
		$this->Cell($this->price_width, 8, '$'.Numbers::price($article->final_price), $this->b, 0, 'L');	

		$this->y += 48;
	}

}