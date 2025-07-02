<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use App\Models\Licitacao;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComprasNetService
{
    protected $client;
    protected $baseUrl = 'http://comprasnet.gov.br/ConsultaLicitacoes/ConsLicitacaoDia.asp';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
            ]
        ]);
    }

    public function capturarLicitacoesDoDia($filtros = [])
    {
        try {
            $params = [
                'dt_pesquisa' => $filtros['data'] ?? date('d/m/Y'),
                'pagina' => $filtros['pagina'] ?? 1
            ];

            $response = $this->client->post($this->baseUrl, [
                'form_params' => $params
            ]);


            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Extrai todas as licitações dos formulários
            $licitacoes = $crawler->filter('form[name^="Form"]')->each(function (Crawler $form) {
                $texto = $form->filter('tr.tex3')->text();
                $texto = preg_replace('/\s+/', ' ', $texto); // Normaliza espaços

                // Extrai dados básicos
                preg_match('/^(.*?)Código da UASG:\s*(\d+)/', $texto, $orgaoMatches);
                preg_match('/Pregão Eletrônico N[°º].*?(\d+\/\d{4})/', $texto, $numeroMatches);
                preg_match('/Objeto:(.*?)Edital a partir de:/s', $texto, $objetoMatches);
                preg_match('/Edital a partir de:(.*?)Hs/', $texto, $dataMatches);
                preg_match('/Endereço:(.*?)\(([A-Z]{2})\)/', $texto, $enderecoMatches);
                preg_match('/Entrega da Proposta:(.*?)Hs/', $texto, $entregaMatches);
                preg_match('/\([^)]*Lei[^)]*([\d\.]+\/\d{4})[^)]*\)/i', $texto, $leiMatches);

                // Extrai link do edital
                $editalLink = $form->filter('input[type="button"][name="itens"]')->attr('onclick');
                preg_match("/\'(.*?)\'/", $editalLink, $linkMatches);

                Log::debug($leiMatches[0]);
                

                return [
                    'orgao' => trim($orgaoMatches[1] ?? ''),
                    'uasg_codigo' => $orgaoMatches[2] ?? null,
                    'modalidade' => 'Pregão Eletrônico',
                    'numero' => $numeroMatches[1] ?? null,
                    'lei' =>  (string)$leiMatches[0] ?? null,
                    'objeto' => trim($objetoMatches[1] ?? ''),
                    'data_abertura' => $this->parseDate($dataMatches[1] ?? ''),
                    'endereco' => trim($enderecoMatches[1] ?? ''),
                    'municipio' => $this->extrairMunicipio($enderecoMatches[1] ?? ''),
                    'uf' => $enderecoMatches[2] ?? '',
                    'data_entrega_proposta' => $this->parseDate($entregaMatches[1] ?? ''),
                    'edital_link' => $linkMatches[1],  
                    'situacao' => '0'
                ];
            });

            // Filtra por UASG se especificado
            if (!empty($filtros['uasg'])) {
                $licitacoes = array_filter($licitacoes, function($lic) use ($filtros) {
                    return $lic['uasg_codigo'] == $filtros['uasg'];
                });
            }

            // Salva no banco
            $count = 0;
            
            foreach ($licitacoes as $licitacao) {
                if ($this->salvarLicitacao($licitacao)) {
                    $count++;
                }
            }

            Log::warning([$licitacoes, 'licitações capturadas']);

            return [
            'count' => $count,
            'licitacoes' => $licitacoes
        ];

        } catch (\Exception $e) {
            Log::error('Erro ao capturar licitações: ' . $e->getMessage());
            return false;
        }
    }

    protected function parseDate($dateStr)
    {
       try {
        // Remove caracteres não visíveis e normaliza espaços
        $cleanDate = preg_replace('/\s+/', ' ', trim($dateStr));
        $cleanDate = str_replace(' ', ' ', $cleanDate); // Remove espaços não quebráveis
        $cleanDate = preg_replace('/\s*às\s*/', '', $cleanDate); // Remove "às"
        $cleanDate = preg_replace('/das.*$/i', '', $cleanDate); // Remove horários
        
        // Extrai apenas a parte da data (DD/MM/YYYY)
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $cleanDate, $matches)) {
            return Carbon::createFromFormat('d/m/Y', "{$matches[1]}/{$matches[2]}/{$matches[3]}")->format('Y-m-d');
        }
        
        return null;
    } catch (\Exception $e) {
        Log::warning("Não foi possível parsear a data: '$dateStr'");
        return null;
    }
    }

    protected function extrairMunicipio($endereco)
    {
        $parts = explode('-', $endereco);
        return trim($parts[count($parts) - 2] ?? '');
    }

    protected function salvarLicitacao(array $data)
    {
         try {
        // Se não tem número, cria um baseado no timestamp
        if (empty($data['numero'])) {
            $data['numero'] = 'SEM-NUM-' . time();
        }

        // Verifica se tem UASG, se não, não salva
        if (empty($data['uasg_codigo'])) {
            Log::warning('Licitação sem UASG', $data);
            return false;
        }

        Licitacao::updateOrCreate(
            [
                'uasg_codigo' => $data['uasg_codigo'],
                'numero' => $data['numero']
            ],
            $data
        );
        return true;
    } catch (\Exception $e) {
        Log::error('Erro ao salvar licitação: ' . $e->getMessage(), $data);
        return false;
    }
    }
}