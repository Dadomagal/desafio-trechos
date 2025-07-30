<?php

namespace App\Http\Controllers;

use App\Models\Rodovia;
use App\Models\Trecho;
use App\Models\Uf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TrechoController extends Controller
{
    public function index()
    {
        return Inertia::render('Trechos/Index', [
            'trechos' => Trecho::with(['uf', 'rodovia'])->latest()->get(),
            'ufs' => Uf::all(),
            'rodovias' => Rodovia::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'data_referencia' => 'required|date',
            'uf_id' => 'required|exists:ufs,id',
            'rodovia_id' => 'required|exists:rodovias,id',
            'tipo_trecho' => 'required|string',
            'quilometragem_inicial' => 'required|numeric|min:0',
            'quilometragem_final' => 'required|numeric|gt:quilometragem_inicial',
        ]);

        $uf = Uf::find($request->uf_id);
        $rodovia = Rodovia::find($request->rodovia_id);

        try {
            $params = [
                'br' => str_pad($rodovia->nome, 3, '0', STR_PAD_LEFT),
                'tipo' => $request->tipo_trecho,
                'uf' => $uf->sigla,
                'cd_tipo' => 'null',
                'data' => $request->data_referencia,
                'kmi' => number_format($request->quilometragem_inicial, 3, '.', ''),
                'kmf' => number_format($request->quilometragem_final, 3, '.', ''),
            ];

            $endpoints = [
                'https://servicos.dnit.gov.br/sgplan/apigeo/rotas/espacializarlinha',
                'https://servicos.dnit.gov.br/sgplan/apigeo/web/rotas/espacializarlinha'
            ];

            $geoJson = null;
            $lastResponse = null;

            foreach ($endpoints as $endpoint) {
                $response = Http::timeout(60)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/json, */*',
                        'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'Referer' => 'https://servicos.dnit.gov.br/sgplan/apigeo/web/rotas/espacializarlinha',
                        'Origin' => 'https://servicos.dnit.gov.br',
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Cache-Control' => 'no-cache',
                        'Pragma' => 'no-cache',
                        'Sec-Fetch-Dest' => 'empty',
                        'Sec-Fetch-Mode' => 'cors',
                        'Sec-Fetch-Site' => 'same-origin'
                    ])
                    ->get($endpoint, $params);

                $lastResponse = $response;
                
                $contentType = $response->header('Content-Type') ?? '';
                if (str_contains($contentType, 'application/json')) {
                    $geoJson = $response->json();
                    if (!empty($geoJson) && isset($geoJson['geometry'])) {
                        break;
                    }
                }
            }

            $response = $lastResponse;

            if (empty($geoJson)) {
                $contentType = $response->header('Content-Type') ?? '';
                $isJson = str_contains($contentType, 'application/json') || str_contains($contentType, 'text/json');
                
                if (!$isJson && $response->successful()) {
                    return Redirect::back()
                        ->withInput()
                        ->withErrors(['api' => 'A API do DNIT não está retornando dados no formato JSON. Pode ser uma limitação temporária da API.']);
                }

                try {
                    $geoJson = $response->json();
                } catch (\Exception $e) {
                    $geoJson = null;
                }
            }

            if (empty($geoJson) || !isset($geoJson['geometry'])) {
                $url = $endpoints[0] . '?' . http_build_query($params);
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json, */*',
                        'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
                        'Cache-Control: no-cache',
                        'Pragma: no-cache'
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);
                
                $curlResponse = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);
                
                if ($httpCode === 200 && str_contains($contentType, 'application/json')) {
                    $curlJson = json_decode($curlResponse, true);
                    if (!empty($curlJson) && isset($curlJson['geometry'])) {
                        $geoJson = $curlJson;
                    }
                }
            }

            if ($response->successful() && !empty($geoJson) && isset($geoJson['geometry'])) {
                Trecho::create([
                    'data_referencia' => $request->data_referencia,
                    'uf_id' => $request->uf_id,
                    'rodovia_id' => $request->rodovia_id,
                    'tipo_trecho' => $request->tipo_trecho,
                    'quilometragem_inicial' => $request->quilometragem_inicial,
                    'quilometragem_final' => $request->quilometragem_final,
                    'geo' => $geoJson,
                ]);

                return Redirect::route('trechos.index')->with('success', 'Trecho cadastrado com sucesso!');

            } else {
                return Redirect::back()
                    ->withInput()
                    ->withErrors(['api' => 'Não foi possível obter a geometria do trecho da API do DNIT. Tente novamente mais tarde ou verifique se os parâmetros estão corretos.']);
            }

        } catch (\Exception $e) {
            return Redirect::back()
                    ->withInput()
                    ->withErrors(['api' => 'Erro de comunicação com a API do DNIT. A resposta pode não ser um JSON válido.']);
        }
    }

    public function destroy(Trecho $trecho)
    {
        $trecho->delete();
        return Redirect::route('trechos.index')->with('success', 'Trecho removido.');
    }
}
