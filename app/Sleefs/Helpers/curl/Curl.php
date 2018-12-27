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
        $contents = curl_exec($c);
        curl_close($c);
        return utf8_encode($contents);
    }

    public static function urlPost($url, $content, $headers=null) {

        if ($headers == null){

            $headers = array("Content-type: application/json");

        }
        else {

            array_push($headers,"Content-type: application/json");

        }

        $content = json_encode($content);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

        $contents = curl_exec($curl);
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($headers != null && is_array($headers)){

            array_push($headers,'Content-Type: application/json');
            array_push($headers,'Content-Length: ' . strlen($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        } else {

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
            );

        }

        $contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return utf8_encode($contents);
    }

    public static function urlPUT($url, $data, $headers=null) {

        $data_string = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($headers != null && is_array($headers)){

            array_push($headers,'Content-Type: application/json');
            array_push($headers,'Content-Length: ' . strlen($data_string));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        } else {

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
            );

        }
        

        $contents = curl_exec($ch);
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
        return curl_exec($ch);
    }

}

?>
