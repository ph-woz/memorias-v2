<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorias\StoreCategoriaRequest;
use App\Http\Requests\Categorias\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Support\Images\WebpImageStore;
use App\Support\Slug\UniqueSlugGenerator;
use App\Support\SoftDeleteListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
        private readonly WebpImageStore $webpImageStore,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Categoria::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = Categoria::query()
            ->select([
                'id',
                'nome',
                'slug',
                'foto',
                'seo_titulo',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->withCount('artigos')
            ->when(
                $busca !== '',
                fn ($query) => $query->whereAny(['nome', 'slug'], 'like', '%'.$busca.'%'),
            )
            ->orderBy('nome');

        $categorias = SoftDeleteListing::apply($query, $excluidos)
            ->paginate(15)
            ->withQueryString();

        return view('categorias.index', [
            'categorias' => $categorias,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Categoria::class);

        return view('categorias.create');
    }

    public function store(StoreCategoriaRequest $request): RedirectResponse
    {
        $this->authorize('create', Categoria::class);

        $dados = $request->validated();

        $categoria = Categoria::query()->create([
            'nome' => $dados['nome'],
            'slug' => $this->slugGenerator->generate(Categoria::class, $dados['slug']),
            'foto' => isset($dados['foto']) && $dados['foto'] instanceof UploadedFile
                ? $this->webpImageStore->store($dados['foto'], 'categorias')
                : null,
            'seo_titulo' => $dados['seo_titulo'] ?? null,
            'seo_descricao' => $dados['seo_descricao'] ?? null,
            'seo_palavras_chave' => $dados['seo_palavras_chave'] ?? null,
        ]);

        return redirect()
            ->route('categorias.edit', $categoria)
            ->with('status', 'categoria-criada');
    }

    public function edit(Categoria $categoria): View
    {
        $this->authorize('update', $categoria);

        return view('categorias.edit', ['categoria' => $categoria]);
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $dados = $request->validated();

        $slug = $categoria->slug === $dados['slug'] && $categoria->nome === $dados['nome']
            ? $categoria->slug
            : $this->slugGenerator->generate(Categoria::class, $dados['slug'], $categoria->id);

        $atributos = [
            'nome' => $dados['nome'],
            'slug' => $slug,
            'seo_titulo' => $dados['seo_titulo'] ?? null,
            'seo_descricao' => $dados['seo_descricao'] ?? null,
            'seo_palavras_chave' => $dados['seo_palavras_chave'] ?? null,
        ];

        if (isset($dados['foto']) && $dados['foto'] instanceof UploadedFile) {
            $atributos['foto'] = $this->webpImageStore->store(
                $dados['foto'],
                'categorias',
                1200,
                $categoria->foto,
            );
        }

        $categoria->update($atributos);

        $categoria->refresh();

        $redirect = redirect()
            ->route('categorias.edit', $categoria)
            ->with('status', 'categoria-atualizada');

        if ($request->input('acao') === 'ver_no_site') {
            $redirect->with('abrir_site_url', route('site.categorias.show', $categoria));
        }

        return $redirect;
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $this->authorize('delete', $categoria);

        $categoria->delete();

        return redirect()
            ->route('categorias.index')
            ->with('status', 'categoria-excluida');
    }

    public function restore(Categoria $categoria): RedirectResponse
    {
        $this->authorize('restore', $categoria);

        $categoria->restore();

        return redirect()
            ->route('categorias.index', ['status' => 'excluidos'])
            ->with('status', 'categoria-restaurada');
    }
}
