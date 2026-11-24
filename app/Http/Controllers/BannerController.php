<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banners\ReorderBannersRequest;
use App\Http\Requests\Banners\StoreBannerRequest;
use App\Http\Requests\Banners\UpdateBannerRequest;
use App\Models\Banner;
use App\Support\SoftDeleteListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Banner::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos', 'ativos', 'desativados'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = Banner::query()
            ->select([
                'id',
                'titulo',
                'slogan',
                'imagem',
                'overlay_cor',
                'overlay_opacidade',
                'posicao_horizontal',
                'posicao_vertical',
                'cta_texto',
                'cta_link',
                'ordem',
                'ativo',
                'updated_at',
                'deleted_at',
            ])
            ->when(
                $busca !== '',
                fn ($query) => $query->whereAny(['titulo', 'slogan'], 'like', '%'.$busca.'%'),
            )
            ->when($status === 'ativos', fn ($query) => $query->where('ativo', true))
            ->when($status === 'desativados', fn ($query) => $query->where('ativo', false))
            ->ordenados();

        $banners = SoftDeleteListing::apply($query, $excluidos)->get();

        return view('banners.index', [
            'banners' => $banners,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Banner::class);

        return view('banners.create');
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $this->authorize('create', Banner::class);

        $dados = $request->validated();
        $imagem = $dados['imagem'] ?? null;

        if (! $imagem instanceof UploadedFile) {
            throw new RuntimeException('A imagem do banner é obrigatória.');
        }

        $banner = Banner::query()->create([
            'titulo' => $dados['titulo'],
            'slogan' => $dados['slogan'] ?? null,
            'imagem' => $this->armazenarImagem($imagem),
            'overlay_cor' => $dados['overlay_cor'],
            'overlay_opacidade' => $dados['overlay_opacidade'],
            'posicao_horizontal' => $dados['posicao_horizontal'],
            'posicao_vertical' => $dados['posicao_vertical'],
            'cta_texto' => $dados['cta_texto'] ?? null,
            'cta_link' => $dados['cta_link'] ?? null,
            'ordem' => ((int) Banner::query()->max('ordem')) + 1,
            'ativo' => $dados['ativo'],
        ]);

        return redirect()
            ->route('banners.edit', $banner)
            ->with('status', 'banner-criado');
    }

    public function edit(Banner $banner): View
    {
        $this->authorize('update', $banner);

        return view('banners.edit', ['banner' => $banner]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $dados = $request->validated();

        $atributos = [
            'titulo' => $dados['titulo'],
            'slogan' => $dados['slogan'] ?? null,
            'overlay_cor' => $dados['overlay_cor'],
            'overlay_opacidade' => $dados['overlay_opacidade'],
            'posicao_horizontal' => $dados['posicao_horizontal'],
            'posicao_vertical' => $dados['posicao_vertical'],
            'cta_texto' => $dados['cta_texto'] ?? null,
            'cta_link' => $dados['cta_link'] ?? null,
            'ativo' => $dados['ativo'],
        ];

        if (isset($dados['imagem']) && $dados['imagem'] instanceof UploadedFile) {
            $atributos['imagem'] = $this->armazenarImagem($dados['imagem'], $banner->imagem);
        }

        $banner->update($atributos);

        return redirect()
            ->route('banners.edit', $banner)
            ->with('status', 'banner-atualizado');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);

        $banner->delete();

        return redirect()
            ->route('banners.index')
            ->with('status', 'banner-excluido');
    }

    public function restore(Banner $banner): RedirectResponse
    {
        $this->authorize('restore', $banner);

        $banner->restore();

        return redirect()
            ->route('banners.index', ['status' => 'excluidos'])
            ->with('status', 'banner-restaurado');
    }

    public function reorder(ReorderBannersRequest $request): JsonResponse
    {
        $this->authorize('reorder', Banner::class);

        $bannerIds = $request->validated('ordem');

        DB::transaction(function () use ($bannerIds): void {
            foreach (array_values($bannerIds) as $indice => $bannerId) {
                Banner::query()
                    ->whereKey($bannerId)
                    ->update(['ordem' => $indice + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function armazenarImagem(UploadedFile $arquivo, ?string $imagemAnterior = null): string
    {
        $caminho = $arquivo->store('banners', 'public');

        if ($imagemAnterior !== null && $imagemAnterior !== '' && ! $this->ehUrlExterna($imagemAnterior)) {
            Storage::disk('public')->delete($imagemAnterior);
        }

        return $caminho;
    }

    private function ehUrlExterna(string $caminho): bool
    {
        return str_starts_with($caminho, 'http://') || str_starts_with($caminho, 'https://');
    }
}
