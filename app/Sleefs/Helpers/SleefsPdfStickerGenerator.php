<?php

namespace Sleefs\Helpers;
use setasign\Fpdi\Fpdi;
use Sleefs\Helpers\Misc\Response;

class SleefsPdfStickerGenerator {

    public function createPdfFile(Fpdi $pdf,$pdfSourceTemplate,$orderId,$pdfDestPath){

        $res = new Response();
        //return 1;
        //New PDF file to copy
        $pdf->SetFont('Helvetica','',24);

        // set the source file
        //echo "\n[MMA]: ".$pathToPdfFile;
        if ($pdfSourceTemplate != '')
        {
            $pageCount = $pdf->setSourceFile($pdfSourceTemplate);

            // iterate through all pages
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // import a page
                $templateId = $pdf->importPage($pageNo);

                $pdf->AddPage("L",'A3');
                // use the imported page and adjust the page size
                $pdf->useTemplate($templateId, ['x' => 5,'y' => 12,'adjustPageSize' => false]);
                //$pdf->SetXY(5, 128);
                $pdf->SetXY(5,6);
                $pdf->Write(1,$orderId);
            }
        }
        else
        {
            $pdf->AddPage("L",'A3');
            $pdf->SetXY(5,6);
            $pdf->Write(1,$orderId);
        }


        //It sets new headers data
        if (!preg_match("/\/$/",$pdfDestPath))
            $pdfDestPath = $pdfDestPath."/";

        $dateNow = date("Y-m-d H:i:s");
        $newTitle = $orderId." ".$dateNow;
        $pdf->SetTitle($newTitle);
        $pdf->SetSubject($newTitle);
        $pdf->SetKeywords($orderId.",".$dateNow);
        $pdf->Output("F",$pdfDestPath.$orderId.".pdf");

        $res->notes = $pdfDestPath.$orderId.".pdf";
        return $res;
        
    }

}