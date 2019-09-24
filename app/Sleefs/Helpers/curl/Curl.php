<?php

namespace Sleefs\Helpers\curl;

class Curl {

    public static function urlGet($url,$headers=null) {

        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        if ($headers != null && is_array($headers)){

            curl_setopt($c, CURLOPT_HTTPHEADER, $headers);

        }

        if($headers != null && in_array('Custom-SSL-Verification:false',$headers)){
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false); 
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        }

        $contents = curl_exec($c);

        if($errno = curl_errno($c)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $contents = "cURL error ({$errno}):\n {$error_message}";
        }
        curl_close($c);
        return utf8_encode($contents);
    }

    public static function urlPost($url, $content, $headers=null) {

        $isJsonForm = false;

        if ($headers == null){
            $headers = array("Content-type: application/json");
            $isJsonForm = true;
        }
        else {
            $stringFromHeaders = implode(" ",$headers);
            if (preg_match("/Content\-type:/i",$stringFromHeaders)){
                
                if (preg_match("/Content\-type:\ {0,4}application\/json/i",$stringFromHeaders)){
                    $isJsonForm = true;                
                    //array_push($headers,"Content-type: application/json");
                }

            }
            else{
                $isJsonForm = true;                
                array_push($headers,"Content-type: application/json");
            }
        }

        if ($isJsonForm){
            $content = json_encode($content);
            $contentString = $content;
        }
        else{

            $contentString = '';
            foreach ($content as $key => $value){
                if (preg_match("/[a-zA-Z_]{2,100}/",$key))
                    $contentString .= $key . "=" . $value ."&";
            }
            $contentString .= "\n";
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $contentString);

        if($headers != null && in_array('Custom-SSL-Verification:false',$headers)){
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); 
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        $contents = curl_exec($curl);
        
        if($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $contents = "cURL error ({$errno}):\n {$error_message}";
        }


        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        /*
        if ($status != 201) {
            die("Error: call to URL $url failed with status $status, response $contents, curl_error: " . curl_error($curl) . ", curl_errno: " . curl_errno($curl));
        }
        */
        curl_close($curl);
    
        return utf8_encode($contents);
    }

    public static function urlDelete($url,$data,$headers=null) {

        $isJsonForm = false;

        if ($headers == null){
            $headers = array("Content-type: application/json");
            $isJsonForm = true;
        }
        else {
            $stringFromHeaders = implode(" ",$headers);
            if (preg_match("/Content\-type:/i",$stringFromHeaders)){
                
                if (preg_match("/Content\-type:\ {0,4}application\/json/i",$stringFromHeaders)){
                    $isJsonForm = true;                
                    //array_push($headers,"Content-type: application/json");
                }

            }
            else{
                $isJsonForm = true;                
                array_push($headers,"Content-type: application/json");
            }
        }

        if ($isJsonForm){
            $data = json_encode($data);
            $dataString = $data;
        }
        else{

            $dataString = '';
            foreach ($data as $key => $value){
                if (preg_match("/[a-zA-Z_]{2,100}/",$key))
                    $dataString .= $key . "=" . $value ."&";
            }
            $dataString .= "\n";
        }



        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        array_push($headers,'Content-Length: ' . strlen($dataString));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if($headers != null && in_array('Custom-SSL-Verification:false',$headers)){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $contents = curl_exec($ch);


        if($errno = curl_errno($ch)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $contents = "cURL error ({$errno}):\n {$error_message}";
        }


        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return utf8_encode($contents);
    }

    public static function urlPUT($url, $data, $headers=null) {

        $isJsonForm = false;
        if ($headers == null){
            $headers = array("Content-type: application/json");
            $isJsonForm = true;
        }
        else {
            $stringFromHeaders = implode(" ",$headers);
            if (preg_match("/Content\-type:/i",$stringFromHeaders)){
                
                if (preg_match("/Content\-type:\ {0,4}application\/json/i",$stringFromHeaders)){
                    $isJsonForm = true;                
                    array_push($headers,"Content-type: application/json");
                }

            }
            else{
                $isJsonForm = true;                
                array_push($headers,"Content-type: application/json");
            }
        }

        if ($isJsonForm){
            $data = json_encode($data);
            $dataString = $data;
        }
        else{

            $dataString = '';
            $arrKeys = array_keys($data);
            foreach ($data as $key => $value){
                if (preg_match("/[a-zA-Z_]{2,100}/",$key))
                    $dataString .= $key . "=" . $value ."&";
            }
            $dataString .= "\n";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        array_push($headers,'Content-Length: ' . strlen($dataString));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        

        if($headers != null && in_array('Custom-SSL-Verification:false',$headers)){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $contents = curl_exec($ch);


        if($errno = curl_errno($ch)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $contents = "cURL error ({$errno}):\n {$error_message}";
        }


        curl_close($ch);

        return utf8_encode($contents);
    }

    public static function getUrlContent($url,$headers=null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($headers != null && is_array($headers)){

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        }


        if($headers != null && in_array('Custom-SSL-Verification:false',$headers)){
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);          
        }


        $contents = curl_exec($ch);
        if($errno = curl_errno($ch)) {
            $error_message = curl_strerror($errno);
            //echo "cURL error ({$errno}):\n {$error_message}";
            $contents = "cURL error ({$errno}):\n {$error_message}";
        }


        return $contents;
    }

}

?>
