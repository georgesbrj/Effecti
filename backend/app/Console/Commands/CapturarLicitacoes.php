<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ComprasNetService;

class CapturarLicitacoes extends Command
{
    protected $signature = 'licitacoes:capturar
                            {--data= : Data da licitação (DD/MM/YYYY)}
                            {--uasg= : Filtrar por código UASG específico}
                            {--pagina=1 : Página de resultados}
                            {--detalhes : Exibir detalhes das licitações capturadas}';

    protected $description = 'Captura licitações do portal ComprasNet';

    public function handle()
    {
        $filtros = [
            'data' => $this->option('data') ?? date('d/m/Y'),
            'uasg' => $this->option('uasg'),
            'pagina' => $this->option('pagina'),
            'detalhes' => $this->option('detalhes')
        ];

        $service = new ComprasNetService();

        $this->exibirConfiguracao($filtros);

        $resultado = $service->capturarLicitacoesDoDia($filtros);

        $this->processarResultado($resultado, $filtros['detalhes']);
    }

    protected function exibirConfiguracao(array $filtros)
    {
        if ($this->option('verbose')) {
            $this->info('🔍 Configuração da captura:');
            $this->table(
                ['Parâmetro', 'Valor'],
                [
                    ['Data', $filtros['data']],
                    ['UASG', $filtros['uasg'] ?? 'Todos'],
                    ['Página', $filtros['pagina']],
                    ['Modo detalhado', $filtros['detalhes'] ? 'Sim' : 'Não']
                ]
            );
            $this->line('');
        }
    }

    protected function processarResultado($resultado, $detalhes = false)
    {
        if ($resultado === false) {
            $this->error(' Ocorreu um erro ao capturar as licitações. Verifique os logs para mais detalhes.');
            return;
        }

        if ($resultado['count'] === 0) {
            $this->warn(' Nenhuma licitação encontrada para os critérios informados.');
            return;
        }

        $this->info(" {$resultado['count']} licitação(ões) capturada(s) com sucesso!");

        if ($detalhes && !empty($resultado['licitacoes'])) {
            $this->line('');
            $this->info('📋 Detalhes das licitações:');
            $this->table(
                ['Órgão', 'UASG', 'Número', 'Objeto (resumo)'],
                array_map(function($lic) {
                    return [
                        substr($lic['orgao'], 0, 30) . '...',
                        $lic['uasg_codigo'],
                        $lic['numero'],
                        substr($lic['objeto'], 0, 40) . '...'
                    ];
                }, $resultado['licitacoes'])
            );
        }
    }
}