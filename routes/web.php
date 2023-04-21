<?php

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    $mode = config('gerencianet.mode');
    $certificate = config("gerencianet.{$mode}.certificate_name");

    $options = [
        'client_id' => config("gerencianet.{$mode}.client_id"),
        'client_secret' => config("gerencianet.{$mode}.client_secret"),
        'certificate' => base_path("certs/{$certificate}"),
        'sandbox' => $mode === 'sandbox',
        'debug' => config('gerencianet.debug'),
        'timeout' => 30,
    ];

    $body = [
        'calendario' => [
            'expiracao' => 3600,
        ],
        'devedor' => [
            'cpf' => '12345678909',
            'nome' => 'Fulano de Tal',
        ],
        'valor' => [
            'original' => '1.99',
        ],
        'chave' => config('gerencianet.default_key_pix'),
        'solicitacaoPagador' => 'Pagamento Plataforma XPTO',
        'infoAdicionais' => [
            [
                'nome' => 'Observação',
                'valor' => 'Compra direta e sem cupom de desconto.',
            ],
        ],
    ];

    try {
        $api = Gerencianet::getInstance($options);
        $pix = $api->pixCreateImmediateCharge([], $body);

        if (!isset($pix['txid'])) {
            throw new Exception('Erro ao realizar transação. Tente novamente.');
        }

        $params = [
            'id' => $pix['loc']['id'],
        ];

        $qrcode = $api->pixGenerateQRCode($params);

        echo "<b>Imagem:</b><br />";
        echo "<img src='{$qrcode['imagemQrcode']}' />";

        dd("Detalhes da Cobrança:", $qrcode, $pix);
    } catch (GerencianetException $gerencianetException) {
        dd($gerencianetException);
    } catch (Exception $exception) {
        dd($exception->getMessage());
    }
});

Route::get('/consultar-pix', function (\Illuminate\Http\Request $request) {
    $mode = config('gerencianet.mode');
    $certificate = config("gerencianet.{$mode}.certificate_name");

    $options = [
        'client_id' => config("gerencianet.{$mode}.client_id"),
        'client_secret' => config("gerencianet.{$mode}.client_secret"),
        'certificate' => base_path("certs/{$certificate}"),
        'sandbox' => $mode === 'sandbox',
        'debug' => config('gerencianet.debug'),
        'timeout' => 30,
    ];

    $params = [
        'txid' => $request->txid,
    ];

    try {
        $api = Gerencianet::getInstance($options);
        $response = $api->pixDetailCharge($params);

        dd($response);
    } catch (GerencianetException $gerencianetException) {
        dd($gerencianetException);
    } catch (Exception $exception) {
        dd($exception->getMessage());
    }
});
