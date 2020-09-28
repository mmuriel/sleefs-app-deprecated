<?php

namespace Sleefs\Controllers\Shopify;


use App\Http\Controllers\Controller;
use Sleefs\Helpers\CustomLogger;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;

use Sleefs\Helpers\Shopify\ProductNameToDirectoryNormalizer;
use Sleefs\Helpers\Shopify\VariantTitleToFileNameNormalizer;
use Sleefs\Helpers\Shopify\ProductNameToDirectoryChecker;
use Sleefs\Helpers\Shopify\PdfTemplateSeeker;

use Sleefs\Helpers\SleefsPdfStickerGenerator;
use setasign\Fpdi\Fpdi;


class OrderCreateController extends Controller {

	public function __invoke(){

		$order = json_decode(file_get_contents('php://input'));
		$clogger = new CustomLogger("sleefs.log");
        $product = '';        
        $variant = '';
        $fpdi = '';

        $ctrlDirPrd = false;
        $ctrlDirBlank = false;
        $ctrlPdfCreatePrd = false;
        $ctrlPdfCreateBlank = false;
        $ctrlQtyStickers = 0;

        $pathToPrdFiles = env("APP_PATH_TO_DRPBOX")."PDFS/APP-PDFS/";// Path: /home/admin/app/dropbox/PDFS/APP-PDFS/
        $pathToBlankFiles = env("APP_PATH_TO_DRPBOX")."PDFS/APP-BLANK/";// Path: /home/admin/app/dropbox/PDFS/APP-BLANK/
        $pathToPdfOrderFiles = env("APP_PATH_TO_DRPBOX")."/ORDERS/".date("Ymd")."/";

        $pdfSeek = new PdfTemplateSeeker();
        $productNameNormalizer = new ProductNameToDirectoryNormalizer();
        $productDirectoryChecker = new ProductNameToDirectoryChecker();
        $variantTitleNormalizer = new VariantTitleToFileNameNormalizer();
        $dirChecker = new ProductNameToDirectoryChecker();
        $sleefsPdfGen = new SleefsPdfStickerGenerator();

        //Wich product types will be accepted
        $productTypesToPrint = array('Back Plate Decal','Visor Skin','Sticker');
		$clogger->writeToLog ("Procesando una orden creada en shopify: ".json_encode($order),"INFO");
        if (count($order->line_items) > 0 )
            foreach ($order->line_items as $index => $lineItem){

                $clogger->writeToLog ("--------------------------","INFO");
                $clogger->writeToLog ("Item Shopify ID: ".json_encode($lineItem->product_id),"INFO");
                //Recupera la variante y el producto
                $product = Product::where("idsp","=","shpfy_".$lineItem->product_id)->first();
                $variant = Variant::where("idsp","=","shpfy_".$lineItem->variant_id)->first();
                if ($product != null && $variant != null){

                    if (in_array($product->product_type,$productTypesToPrint))
                    {
                        //It defines if directory for date (YYYYMMDD) has been already created
                        if (!is_dir($pathToPdfOrderFiles))
                        {
                            mkdir($pathToPdfOrderFiles);
                        }
                        //Busca el archivo PDF correspondiente
                        $normalizedDirName=$product->title;
                        $normalizedDirName=$productNameNormalizer->normalizeProductName($normalizedDirName,array("/[^a-zA-Z0-9\ \-&]/","/&/"),array("","AND"));
                        $normalizedVariantTitle=$variantTitleNormalizer->normalizeVariantTitle($variant->title,array("/[^a-zA-Z0-9\ \-&\/]/","/&/","/\//"),array("","AND","--"));
                        
                        $resDirPrd = $dirChecker->isDirectoryAlreadyCreated($pathToPrdFiles.$normalizedDirName."/");
                        if ($resDirPrd->value == true)
                        {
                            //Look for pdf template file (production directory)
                            $seekerProdResp = $pdfSeek->seekForPdfTemplate($pathToPrdFiles.$normalizedDirName."/",$normalizedVariantTitle);
                            $ctrlDirPrd = true;
                            if ($seekerProdResp->value == true)
                            {
                                $fpdi = new Fpdi();
                                $ctrlQtyStickers++;
                                $resCreatePdfPrd = $sleefsPdfGen->createPdfFile($fpdi,$seekerProdResp->notes,$order->name."-".$ctrlQtyStickers,$pathToPdfOrderFiles);
                                $ctrlPdfCreatePrd = true; 
                            }
                        }
                        else
                        {
                            //echo "\n".$pathToPrdFiles.$normalizedDirName."/"." no es un directorio valido\n";
                        }

                        $resDirBlank = $dirChecker->isDirectoryAlreadyCreated($pathToBlankFiles.$normalizedDirName."/");

                        if ($resDirBlank->value==true && $ctrlPdfCreatePrd == false)
                        {                        

                            //Look for pdf template file (blank directory)
                            $seekerBlankResp = $pdfSeek->seekForPdfTemplate($pathToBlankFiles.$normalizedDirName."/",$normalizedVariantTitle);
                            //print_r($seekerBlankResp);
                            $ctrlDirBlank = true;
                            if ($seekerBlankResp->value == true)
                            {
                                $fpdi = new Fpdi();
                                $ctrlQtyStickers++;
                                $resCreatePdfBlank = $sleefsPdfGen->createPdfFile($fpdi,$seekerBlankResp->notes,$order->name."-".$ctrlQtyStickers,$pathToPdfOrderFiles);
                                $ctrlPdfCreateBlank = true; 
                            }

                        }
                        else
                        {
                            //echo "\n".$pathToBlankFiles.$normalizedDirName."/"." no es un directorio valido\n";
                        }

                        //==========================================================
                        //It reports errors
                        //==========================================================

                        if ($ctrlDirPrd == false && $ctrlDirBlank==false)
                        {
                            //There is no template for that product, must be created
                        }
                        elseif ($ctrlDirPrd == true && $ctrlDirBlank==false)
                        {
                            //

                        }
                        elseif ($ctrlDirPrd == false && $ctrlDirBlank == true)
                        {
                            //
                        }


                        $clogger->writeToLog ("Producto CON PDF para imprimir","INFO");
                        $clogger->writeToLog ("Product App Sleefs: ".$product->title." (".$product->idsp.", ".$product->product_type.")","INFO");
                        $clogger->writeToLog ("Variant App Sleefs: ".$variant->title." (".$variant->idsp.", ".$variant->sku.")","INFO");
                        if ($ctrlPdfCreateBlank)
                        {
                            $clogger->writeToLog ("El producto se han impreso desde una plantilla blank","WARNING");
                        }
                        elseif($ctrlPdfCreatePrd)
                        {
                            $clogger->writeToLog ("El producto se han impreso desde una plantilla de producción","INFO");
                        }
                        else
                        {
                            $clogger->writeToLog ("El producto no se ha impreso porque no existen plantilla definida","WARNING");
                        }
                    }
                }
                $ctrlDirPrd = false;
                $ctrlDirBlank = false;
                $ctrlPdfCreatePrd = false;
                $ctrlPdfCreateBlank = false;
            }
            

        //

		return response()->json(["code"=>200,"Message" => "Success"]);

	}	

}
