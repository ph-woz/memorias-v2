<?php

namespace App\Http\Controllers;

use App\Enums\StatusArtigo;
use App\Enums\StatusImportacaoArtigo;
use App\Http\Requests\Artigos\ImportarArtigoPdfRequest;
use App\Http\Requests\Artigos\StoreArtigoConteudoImagemRequest;
use App\Http\Requests\Artigos\StoreArtigoRequest;
use App\Http\Requests\Artigos\UpdateArtigoRequest;
use App\Jobs\ProcessarImportacaoArtigoPdf;
use App\Models\AreaTematica;
use App\Models\Artigo;
use App\Models\ArtigoImportacao;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\PalavraChave;
use App\Models\User;
use App\Support\Images\WebpImageStore;
use App\Support\Slug\UniqueSlugGenerator;
use App\Support\SoftDeleteListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArtigoController extends Controller
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
        private readonly WebpImageStore $webpImageStore,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Artigo::class);

        $busca = $request->string('q')->toString();
        $status = $this->statusDoFiltro($request->string('status', 'todos')->toString());
        $atencao = $this->atencaoDoFiltro($request->string('atencao')->toString());
        $ordenacao = $this->ordenacaoDoFiltro($request->string('ordenar', 'atualizado_desc')->toString());
        $categoriaIds = $this->idsDoFiltro($request->input('categorias'));
        $areaTematicaIds = $this->idsDoFiltro($request->input('areas_tematicas'));
        $etiquetaIds = $this->idsDoFiltro($request->input('etiquetas'));
        $excluidos = $status === 'excluidos' || $atencao === 'excluidos';

        if ($atencao === 'rascunhos_antigos' && $status === 'todos') {
            $status = 'rascunhos';
        }

        if ($atencao === 'excluidos') {
            $status = 'excluidos';
        }

        $query = Artigo::query()
            ->select([
                'id',
                'cadastrante_id',
                'titulo',
                'slug',
                'foto',
                'status',
                'publicado_em',
                'created_at',
                'updated_at',
                'deleted_at',
                'deletado_por',
            ])
            ->with([
                'autores:id,artigo_id,nome,ordem',
                'categorias:id,nome',
                'etiquetas:id,nome,cor,slug',
                'deletadoPor:id,nome',
            ])
            ->when(
                $busca !== '',
                fn ($query) => $query->whereAny(['titulo', 'slug'], 'like', '%'.$busca.'%'),
            )
            ->when(
                $categoriaIds !== [],
                fn ($query) => $query->whereHas(
                    'categorias',
                    fn ($categorias) => $categorias->whereIn('categorias.id', $categoriaIds),
                ),
            )
            ->when(
                $areaTematicaIds !== [],
                fn ($query) => $query->whereHas(
                    'areasTematicas',
                    fn ($areas) => $areas->whereIn('areas_tematicas.id', $areaTematicaIds),
                ),
            )
            ->when(
                $etiquetaIds !== [],
                fn ($query) => $query->whereHas(
                    'etiquetas',
                    fn ($etiquetas) => $etiquetas->whereIn('etiquetas.id', $etiquetaIds),
                ),
            )
            ->when(
                $status === 'publicados',
                fn ($query) => $query->where('status', StatusArtigo::Publicado),
            )
            ->when(
                $status === 'rascunhos',
                fn ($query) => $query->where('status', StatusArtigo::Rascunho),
            )
            ->when(
                $atencao === 'sem_foto',
                fn ($query) => $query->where(fn ($semFoto) => $semFoto->whereNull('foto')->orWhere('foto', '')),
            )
            ->when(
                $atencao === 'sem_categoria',
                fn ($query) => $query->whereDoesntHave('categorias'),
            )
            ->when(
                $atencao === 'rascunhos_antigos',
                fn ($query) => $query
                    ->where('status', StatusArtigo::Rascunho)
                    ->where('updated_at', '<', now()->subDays(14)),
            );

        $query = match ($ordenacao) {
            'atualizado_asc' => $query->oldest('updated_at'),
            'titulo_asc' => $query->orderBy('titulo'),
            'titulo_desc' => $query->orderByDesc('titulo'),
            'publicado_asc' => $query->orderBy('publicado_em'),
            'publicado_desc' => $query->orderByDesc('publicado_em'),
            default => $query->latest('updated_at'),
        };

        $artigos = SoftDeleteListing::apply($query, $excluidos)
            ->paginate(15)
            ->withQueryString();

        return view('artigos.index', [
            'artigos' => $artigos,
            'busca' => $busca,
            'status' => $status,
            'atencao' => $atencao,
            'categoriaIds' => $categoriaIds,
            'areaTematicaIds' => $areaTematicaIds,
            'etiquetaIds' => $etiquetaIds,
            'ordenacao' => $ordenacao,
            'categoriasFiltro' => Categoria::query()->orderBy('nome')->get(['id', 'nome']),
            'areasTematicasFiltro' => AreaTematica::query()->orderBy('nome')->get(['id', 'nome']),
            'etiquetasFiltro' => Etiqueta::query()->orderBy('nome')->get(['id', 'nome', 'cor']),
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Artigo::class);

        return view('artigos.create', $this->dadosFormulario());
    }

    public function store(StoreArtigoRequest $request): RedirectResponse
    {
        $this->authorize('create', Artigo::class);

        $dados = $request->dadosArtigo();

        $artigo = DB::transaction(function () use ($request, $dados): Artigo {
            $artigo = Artigo::query()->create([
                'cadastrante_id' => $request->user()->id,
                'titulo' => $dados['titulo'],
                'slug' => $this->slugGenerator->generate(Artigo::class, $dados['slug']),
                'doi' => $dados['doi'],
                'foto' => $dados['foto'] instanceof UploadedFile
                    ? $this->webpImageStore->store($dados['foto'], 'artigos')
                    : null,
                'resumo' => $dados['resumo'],
                'conteudo' => $dados['conteudo'],
                'endereco_instituicoes' => $dados['endereco_instituicoes'],
                'autor_correspondente' => $dados['autor_correspondente'],
                'seo_titulo' => $dados['seo_titulo'],
                'seo_descricao' => $dados['seo_descricao'],
                'seo_palavras_chave' => $dados['seo_palavras_chave'],
                'status' => $dados['status'],
                'publicado_em' => $dados['publicado_em'],
            ]);

            $this->sincronizarRelacoes($artigo, $dados);

            return $artigo;
        });

        return redirect()
            ->route('artigos.edit', $artigo)
            ->with('status', 'artigo-criado');
    }

    public function importarPdf(ImportarArtigoPdfRequest $request): RedirectResponse
    {
        $this->authorize('create', Artigo::class);

        /** @var UploadedFile $pdf */
        $pdf = $request->file('pdf');
        $caminho = 'artigos/importacoes/'.Str::uuid()->toString().'.pdf';

        Storage::disk('public')->putFileAs(
            dirname($caminho),
            $pdf,
            basename($caminho),
        );

        $importacao = ArtigoImportacao::query()->create([
            'user_id' => $request->user()->id,
            'pdf_path' => $caminho,
            'pdf_nome_original' => $pdf->getClientOriginalName(),
            'status' => StatusImportacaoArtigo::Pendente,
            'progresso' => 0,
            'etapa' => 'Na fila...',
        ]);

        ProcessarImportacaoArtigoPdf::dispatch($importacao);

        return redirect()
            ->route('artigos.importacoes.show', $importacao)
            ->with('status', 'artigo-importacao-iniciada');
    }

    public function showImportacao(ArtigoImportacao $importacao): View|RedirectResponse
    {
        $this->authorize('create', Artigo::class);

        abort_unless($importacao->user_id === auth()->id(), 403);

        $importacao->load(['artigo:id,titulo,slug']);

        if (
            $importacao->status === StatusImportacaoArtigo::Concluida
            && $importacao->artigo !== null
        ) {
            return redirect()
                ->route('artigos.edit', $importacao->artigo)
                ->with('status', 'artigo-importado');
        }

        return view('artigos.importacao', [
            'importacao' => $importacao,
        ]);
    }

    public function statusImportacao(ArtigoImportacao $importacao): JsonResponse
    {
        $this->authorize('create', Artigo::class);

        abort_unless($importacao->user_id === auth()->id(), 403);

        $importacao->load(['artigo:id,titulo,slug']);

        return response()->json([
            'status' => $importacao->status->value,
            'label' => $importacao->status->label(),
            'progresso' => (int) $importacao->progresso,
            'etapa' => $importacao->etapa
                ?? match ($importacao->status) {
                    StatusImportacaoArtigo::Pendente => 'Na fila...',
                    StatusImportacaoArtigo::Processando => 'Extraindo dados com IA...',
                    StatusImportacaoArtigo::Concluida => 'Concluída',
                    StatusImportacaoArtigo::Falhou => 'Falhou',
                },
            'erro' => $importacao->erro,
            'finalizada' => $importacao->status->estaFinalizada(),
            'artigo_id' => $importacao->artigo_id,
            'edit_url' => $importacao->artigo !== null
                ? route('artigos.edit', $importacao->artigo)
                : null,
        ]);
    }

    public function storeConteudoImagem(StoreArtigoConteudoImagemRequest $request): JsonResponse
    {
        $caminho = $this->webpImageStore->store(
            $request->file('imagem'),
            'artigos/conteudo',
            1600,
        );

        return response()->json([
            'url' => Storage::disk('public')->url($caminho),
        ]);
    }

    public function edit(Artigo $artigo): View
    {
        $this->authorize('update', $artigo);

        $artigo->load([
            'autores:id,artigo_id,nome,ordem',
            'referencias:id,artigo_id,texto,ordem',
            'documentos:id,artigo_id,titulo,arquivo,url,ordem',
            'categorias:id,nome',
            'palavrasChave:id,nome',
            'areasTematicas:id,nome',
            'etiquetas:id,nome,cor,slug',
        ]);

        return view('artigos.edit', [
            'artigo' => $artigo,
            ...$this->dadosFormulario(),
        ]);
    }

    public function update(UpdateArtigoRequest $request, Artigo $artigo): RedirectResponse
    {
        $dados = $request->dadosArtigo();

        DB::transaction(function () use ($artigo, $dados): void {
            $slug = $artigo->slug === $dados['slug'] && $artigo->titulo === $dados['titulo']
                ? $artigo->slug
                : $this->slugGenerator->generate(Artigo::class, $dados['slug'], $artigo->id);

            $atributos = [
                'titulo' => $dados['titulo'],
                'slug' => $slug,
                'doi' => $dados['doi'],
                'resumo' => $dados['resumo'],
                'conteudo' => $dados['conteudo'],
                'endereco_instituicoes' => $dados['endereco_instituicoes'],
                'autor_correspondente' => $dados['autor_correspondente'],
                'seo_titulo' => $dados['seo_titulo'],
                'seo_descricao' => $dados['seo_descricao'],
                'seo_palavras_chave' => $dados['seo_palavras_chave'],
                'status' => $dados['status'],
                'publicado_em' => $dados['publicado_em'],
            ];

            if ($dados['foto'] instanceof UploadedFile) {
                $atributos['foto'] = $this->webpImageStore->store(
                    $dados['foto'],
                    'artigos',
                    1200,
                    $artigo->foto,
                );
            }

            $artigo->update($atributos);
            $this->sincronizarRelacoes($artigo, $dados);
        });

        $artigo->refresh();

        $redirect = redirect()
            ->route('artigos.edit', $artigo)
            ->with('status', 'artigo-atualizado');

        if ($request->input('acao') === 'ver_no_site') {
            $redirect->with('abrir_site_url', route('site.artigos.show', $artigo));
        }

        return $redirect;
    }

    public function destroy(Request $request, Artigo $artigo): RedirectResponse
    {
        $this->authorize('delete', $artigo);

        /** @var User $usuario */
        $usuario = $request->user();
        $artigo->excluirPor($usuario);

        return redirect()
            ->route('artigos.index')
            ->with('status', 'artigo-excluido');
    }

    public function restore(Artigo $artigo): RedirectResponse
    {
        $this->authorize('restore', $artigo);

        $artigo->restaurar();

        return redirect()
            ->route('artigos.index', ['status' => 'excluidos'])
            ->with('status', 'artigo-restaurado');
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function sincronizarRelacoes(Artigo $artigo, array $dados): void
    {
        $artigo->sincronizarCategorias($dados['categorias']);
        $artigo->sincronizarAutores($dados['autores']);
        $artigo->sincronizarPalavrasChave($dados['palavras_chave']);
        $artigo->sincronizarAreasTematicas($dados['areas_tematicas']);
        $artigo->sincronizarEtiquetas($dados['etiquetas']);
        $artigo->sincronizarReferencias($dados['referencias']);
        $artigo->sincronizarDocumentos($dados['documentos']);
    }

    /**
     * @return array{
     *     categorias: Collection<int, Categoria>,
     *     palavrasChave: Collection<int, PalavraChave>,
     *     areasTematicas: Collection<int, AreaTematica>,
     *     etiquetas: Collection<int, Etiqueta>,
     *     statuses: list<StatusArtigo>
     * }
     */
    private function dadosFormulario(): array
    {
        return [
            'categorias' => Categoria::query()->orderBy('nome')->get(['id', 'nome']),
            'palavrasChave' => PalavraChave::query()->orderBy('nome')->get(['id', 'nome']),
            'areasTematicas' => AreaTematica::query()->orderBy('nome')->get(['id', 'nome']),
            'etiquetas' => Etiqueta::query()->orderBy('nome')->get(['id', 'nome', 'cor', 'slug']),
            'statuses' => StatusArtigo::cases(),
        ];
    }

    /**
     * @return list<int>
     */
    private function idsDoFiltro(mixed $valores): array
    {
        return array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $id): int => (int) $id,
                (array) $valores,
            ),
            static fn (int $id): bool => $id > 0,
        )));
    }

    private function statusDoFiltro(string $status): string
    {
        return in_array($status, ['excluidos', 'todos', 'publicados', 'rascunhos'], true)
            ? $status
            : 'todos';
    }

    private function atencaoDoFiltro(string $atencao): ?string
    {
        return in_array($atencao, ['sem_foto', 'sem_categoria', 'rascunhos_antigos', 'excluidos'], true)
            ? $atencao
            : null;
    }

    private function ordenacaoDoFiltro(string $ordenacao): string
    {
        return in_array($ordenacao, [
            'atualizado_desc',
            'atualizado_asc',
            'titulo_asc',
            'titulo_desc',
            'publicado_desc',
            'publicado_asc',
        ], true)
            ? $ordenacao
            : 'atualizado_desc';
    }
}
