<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use setasign\Fpdi\Fpdi;

use Sleefs\Helpers\Shopify\ProductNameToDirectoryNormalizer;
use Sleefs\Helpers\Shopify\VariantTitleToFileNameNormalizer;
use Sleefs\Helpers\Shopify\ProductNameToDirectoryChecker;
use Sleefs\Helpers\SleefsPdfStickerGenerator;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;


class CreateBlankPdfFilesFromVariantsName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sleefs:createPdfFiles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It creates PDF files from product/variant definition, for those products that are pastable stickers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //It creates required object and it initialize variables
        $pathToFolder = env("APP_PATH_TO_DRPBOX");
        $productNameNormalizer = new ProductNameToDirectoryNormalizer();
        $productDirectoryChecker = new ProductNameToDirectoryChecker();
        $variantTitleNormalizer = new VariantTitleToFileNameNormalizer();
        $sleefsPdfGen = new SleefsPdfStickerGenerator();
        $normalizedVariantTitle = '';
        $clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");

        //It search for products, then creates PDF files
        $products = Product::whereRaw(" (product_type = 'Sticker' || product_type = 'Back Plate Decal' || product_type = 'Visor Skin')")->get();

        foreach ($products as $product)
        {
            echo $product->title." (".$product->product_type.")\n";
            $normalizedName=$product->title;

            //Normalize product.title to create directory name
            $normalizedName=$productNameNormalizer->normalizeProductName($normalizedName,array("/[^a-zA-Z0-9\ \-&]/","/&/"),array("","AND"));

            //It checks if there is an already directory created, if it isn't then, it created the directory.
            $isDirectoryCreated = $productDirectoryChecker->isDirectoryAlreadyCreated($pathToFolder.$normalizedName);
            if ($isDirectoryCreated->value == false)
            {
                mkdir($pathToFolder.$normalizedName);
            }

            //It creates for every variant a PDF file

            foreach ($product->variants as $variant)
            {
                $fpdi = new Fpdi();
                $normalizedVariantTitle = $variantTitleNormalizer->normalizeVariantTitle($variant->title,array("/[^a-zA-Z0-9\ \-&\/]/","/&/","/\//"),array("","AND","--"));
                $normalizedVariantTitle = $normalizedVariantTitle.' __blank';
                if (!file_exists($pathToFolder.$normalizedName."/".$normalizedVariantTitle.".pdf"))
                {
                    //It creates the new file
                    $resCreatePdf = $sleefsPdfGen->createPdfFile($fpdi,'',$normalizedVariantTitle,$pathToFolder.$normalizedName);


                    if ($resCreatePdf->status == true){
                        echo "Se ha creado el archivo PDF: ".$normalizedName."/".$normalizedVariantTitle.".pdf\n";
                        $clogger->writeToLog ("Se ha creado el archivo PDF: ".$normalizedName."/".$normalizedVariantTitle.".pdf","INFO");
                    }
                    else
                    {
                        echo "No se ha podido crear el archivo PDF: ".$normalizedName."/".$normalizedVariantTitle.".pdf\n";
                        $clogger->writeToLog ("Se ha creado el archivo PDF: ".$normalizedName."/".$normalizedVariantTitle.".pdf","INFO");
                    }
                }
                else
                {
                    echo "Ya existe el archivo PDF: ".$normalizedName."/".$normalizedVariantTitle.".pdf\n";
                    $clogger->writeToLog ("Ya existe el archivo PDF: ".$normalizedName."/".$normalizedVariantTitle.".pdf","INFO");
                }

            }
        }

    }
}
