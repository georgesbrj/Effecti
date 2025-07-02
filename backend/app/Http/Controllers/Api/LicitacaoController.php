<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Licitacao;

/**
 * @OA\Info(
 *     title="API de Licitações",
 *     version="1.0.0",
 *     description="API para gerenciamento de licitações"
 * )
 *
 * @OA\Tag(
 *     name="Licitações",
 *     description="Operações relacionadas a licitações"
 * )
 */
class LicitacaoController extends Controller
{


        /**
     * @OA\Get(
     *     path="/api/licitacoes",
     *     summary="Lista todas as licitações com possibilidade de filtro",
     *     tags={"Licitações"},
     *     @OA\Parameter(
     *         name="uasg_codigo",
     *         in="query",
     *         description="Código UASG para filtro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="numero",
     *         in="query",
     *         description="Número da licitação para filtro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Quantidade de itens por página (máximo 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de licitações retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Licitacao")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro no servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro no servidor"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="trace", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {

        try {
            $query = Licitacao::query();

            // Filtro por UASG - corrigido para usar 'uasg_codigo' 
            $query->when($request->has('uasg_codigo') && !empty($request->uasg_codigo), function ($q) use ($request) {
                return $q->where('uasg_codigo', $request->uasg_codigo);
            });

            // Filtro por número - só aplica se o parâmetro foi enviado e não é vazio
            $query->when($request->has('numero') && !empty($request->numero), function ($q) use ($request) {
                return $q->where('numero', $request->numero);
            });

            // Paginação  
            $perPage = min($request->input('limit', 10), 100);
            $page = max($request->input('page', 1), 1);

            $licitacoes = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $licitacoes->items(),
                'pagination' => [
                    'total' => $licitacoes->total(),
                    'per_page' => $licitacoes->perPage(),
                    'current_page' => $licitacoes->currentPage(),
                    'last_page' => $licitacoes->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro no servidor',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }


        /**
     * @OA\Put(
     *     path="/api/licitacoes/{id}",
     *     summary="Atualiza a situação de uma licitação",
     *     tags={"Licitações"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da licitação",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"situacao"},
     *             @OA\Property(property="situacao", type="string", example="Cancelada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Licitação atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Licitacao")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Licitação não encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Licitação não encontrada")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $licitacao = Licitacao::findOrFail($id);

        if ($request->has('situacao')) {
            $licitacao->situacao = $request->situacao;
            $licitacao->save();
        }

        return response()->json([
            'success' => true,
            'data' => $licitacao
        ]);
    }
}
