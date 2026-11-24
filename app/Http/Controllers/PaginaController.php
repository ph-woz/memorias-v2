<?php

namespace App\Http\Controllers;

use App\Http\Requests\Paginas\StorePaginaConteudoImagemRequest;
use App\Http\Requests\Paginas\StorePaginaRequest;
use App\Http\Requests\Paginas\UpdatePaginaRequest;
use App\Models\Pagina;
use App\Support\Images\WebpImageStore;
use App\Support\Slug\UniqueSlugGenerator;
use App\Support\SoftDeleteListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaginaController extends Controller
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
        private readonly WebpImageStore $webpImageStore,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Pagina::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos', 'ativos', 'desativados'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = Pagina::query()
            ->select([
                'id',
                'titulo',
                'slug',
                'ativo',
                'updated_at',
                'deleted_at',
            ])
            ->when(
                $busca !== '',
                fn ($query) => $query->whereAny(['titulo', 'slug'], 'like', '%'.$busca.'%'),
            )
            ->when($status === 'ativos', fn ($query) => $query->where('ativo', true))
            ->when($status === 'desativados', fn ($query) => $query->where('ativo', false))
            ->orderByDesc('updated_at');

        $paginas = SoftDeleteListing::apply($query, $excluidos)
            ->paginate(15)
            ->withQueryString();

        return view('paginas.index', [
            'paginas' => $paginas,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Pagina::class);

        return view('paginas.create');
    }

    public function store(StorePaginaRequest $request): RedirectResponse
    {
        $this->authorize('create', Pagina::class);

        $dados = $request->validated();

        $pagina = Pagina::query()->create([
            'titulo' => $dados['titulo'],
            'slug' => $this->slugGenerator->generate(Pagina::class, $dados['slug']),
            'conteudo' => $dados['conteudo'] ?? null,
            'ativo' => $dados['ativo'],
            'seo_titulo' => $dados['seo_titulo'] ?? null,
            'seo_descricao' => $dados['seo_descricao'] ?? null,
            'seo_palavras_chave' => $dados['seo_palavras_chave'] ?? null,
        ]);

        return redirect()
            ->route('paginas.edit', $pagina)
            ->with('status', 'pagina-criada');
    }

    public function edit(Pagina $pagina): View
    {
        $this->authorize('update', $pagina);

        return view('paginas.edit', [
            'pagina' => $pagina,
        ]);
    }

    public function update(UpdatePaginaRequest $request, Pagina $pagina): RedirectResponse
    {
        $dados = $request->validated();

        $slug = $pagina->slug === $dados['slug'] && $pagina->titulo === $dados['titulo']
            ? $pagina->slug
            : $this->slugGenerator->generate(Pagina::class, $dados['slug'], $pagina->id);

        $pagina->update([
            'titulo' => $dados['titulo'],
            'slug' => $slug,
            'conteudo' => $dados['conteudo'] ?? null,
            'ativo' => $dados['ativo'],
            'seo_titulo' => $dados['seo_titulo'] ?? null,
            'seo_descricao' => $dados['seo_descricao'] ?? null,
            'seo_palavras_chave' => $dados['seo_palavras_chave'] ?? null,
        ]);

        return redirect()
            ->route('paginas.edit', $pagina)
            ->with('status', 'pagina-atualizada');
    }

    public function destroy(Pagina $pagina): RedirectResponse
    {
        $this->authorize('delete', $pagina);

        $pagina->delete();

        return redirect()
            ->route('paginas.index')
            ->with('status', 'pagina-excluida');
    }

    public function restore(Pagina $pagina): RedirectResponse
    {
        $this->authorize('restore', $pagina);

        $pagina->restore();

        return redirect()
            ->route('paginas.index', ['status' => 'excluidos'])
            ->with('status', 'pagina-restaurada');
    }

    public function storeConteudoImagem(StorePaginaConteudoImagemRequest $request): JsonResponse
    {
        $caminho = $this->webpImageStore->store(
            $request->file('imagem'),
            'paginas/conteudo',
            1600,
        );

        return response()->json([
            'url' => Storage::disk('public')->url($caminho),
        ]);
    }
}
