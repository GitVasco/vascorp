<?php

class JsonPeApi
{
    const API_URL = 'https://api.json.pe/api';

    private static function getToken()
    {
        if (!defined('TOKEN_JSON_PE')) {
            require_once __DIR__ . '/../controladores/config.php';
        }

        return TOKEN_JSON_PE;
    }

    private static function post($endpoint, $body)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::API_URL . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::getToken()
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return array(
                'success' => false,
                'message' => 'cURL Error: ' . $err
            );
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            return array(
                'success' => false,
                'message' => 'Respuesta inválida del servicio'
            );
        }

        return $decoded;
    }

    public static function consultarDni($dni)
    {
        return self::post('/dni', array('dni' => $dni));
    }

    public static function consultarRuc($ruc)
    {
        return self::post('/ruc', array('ruc' => $ruc));
    }

    public static function consultarTipoCambio($fecha = null)
    {
        if ($fecha === null) {
            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d');
        }

        $response = self::post('/tipo_de_cambio', array('fecha' => $fecha));

        if (empty($response['success']) || empty($response['data'])) {
            return array(
                'compra' => 0,
                'venta' => 'Fuera de plazo permitido',
                'fecha' => $fecha
            );
        }

        $data = $response['data'];

        return array(
            'compra' => $data['compra'],
            'venta' => $data['venta'],
            'fecha' => isset($data['fecha_sunat']) ? $data['fecha_sunat'] : (isset($data['date']) ? $data['date'] : $fecha)
        );
    }
}
