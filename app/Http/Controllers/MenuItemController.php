<?php

namespace App\Http\Controllers;

use App\Http\Requests\Menu\ReorderMenuItemsRequest;
use App\Http\Requests\Menu\StoreMenuItemRequest;
use App\Http\Requests\Menu\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Support\SoftDeleteListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MenuItem::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos', 'ativos', 'desativados'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = MenuItem::query()
            ->select([
                'id',
                'parent_id',
                'rotulo',
                'url',
                'target_blank',
                'ordem',
                'ativo',
                'updated_at',
                'deleted_at',
            ])
            ->with([
                'filhos' => fn ($query) => $query
                    ->select([
                        'id',
                        'parent_id',
                        'rotulo',
                        'url',
                        'target_blank',
                        'ordem',
                        'ativo',
                        'updated_at',
                        'deleted_at',
                    ])
                    ->when($excluidos, fn ($filhos) => $filhos->withTrashed()),
            ])
            ->raizes()
            ->when(
                $busca !== '',
                fn ($query) => $query->where(function ($buscaQuery) use ($busca): void {
                    $buscaQuery
                        ->whereAny(['rotulo', 'url'], 'like', '%'.$busca.'%')
                        ->orWhereHas(
                            'filhos',
                            fn ($filhos) => $filhos->whereAny(['rotulo', 'url'], 'like', '%'.$busca.'%'),
                        );
                }),
            )
            ->when($status === 'ativos', fn ($query) => $query->where('ativo', true))
            ->when($status === 'desativados', fn ($query) => $query->where('ativo', false))
            ->ordenados();

        $itens = SoftDeleteListing::apply($query, $excluidos)->get();

        return view('menu.index', [
            'itens' => $itens,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MenuItem::class);

        return view('menu.create');
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $this->authorize('create', MenuItem::class);

        $dados = $request->validated();

        $menuItem = DB::transaction(function () use ($dados): MenuItem {
            $menuItem = MenuItem::query()->create([
                'parent_id' => null,
                'rotulo' => $dados['rotulo'],
                'url' => $dados['url'] ?? null,
                'target_blank' => $dados['target_blank'],
                'ordem' => $this->proximaOrdem(null),
                'ativo' => $dados['ativo'],
            ]);

            $this->sincronizarLinks($menuItem, $dados['links'] ?? []);

            return $menuItem;
        });

        return redirect()
            ->route('menu.edit', $menuItem)
            ->with('status', 'menu-item-criado');
    }

    public function edit(MenuItem $menuItem): View|RedirectResponse
    {
        $this->authorize('update', $menuItem);

        if ($menuItem->parent_id !== null) {
            return redirect()->route('menu.edit', $menuItem->parent_id);
        }

        $menuItem->load(['filhos']);

        return view('menu.edit', [
            'menuItem' => $menuItem,
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        if ($menuItem->parent_id !== null) {
            return redirect()->route('menu.edit', $menuItem->parent_id);
        }

        $dados = $request->validated();

        DB::transaction(function () use ($menuItem, $dados): void {
            $menuItem->update([
                'rotulo' => $dados['rotulo'],
                'url' => $dados['url'] ?? null,
                'target_blank' => $dados['target_blank'],
                'ativo' => $dados['ativo'],
            ]);

            $this->sincronizarLinks($menuItem, $dados['links'] ?? []);
        });

        return redirect()
            ->route('menu.edit', $menuItem)
            ->with('status', 'menu-item-atualizado');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('delete', $menuItem);

        DB::transaction(function () use ($menuItem): void {
            $menuItem->filhos()->each(fn (MenuItem $filho): bool => (bool) $filho->delete());
            $menuItem->delete();
        });

        return redirect()
            ->route('menu.index')
            ->with('status', 'menu-item-excluido');
    }

    public function restore(MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('restore', $menuItem);

        DB::transaction(function () use ($menuItem): void {
            $menuItem->restore();
            MenuItem::onlyTrashed()
                ->where('parent_id', $menuItem->id)
                ->get()
                ->each(fn (MenuItem $filho): bool => (bool) $filho->restore());
        });

        return redirect()
            ->route('menu.index', ['status' => 'excluidos'])
            ->with('status', 'menu-item-restaurado');
    }

    public function reorder(ReorderMenuItemsRequest $request): JsonResponse
    {
        $this->authorize('reorder', MenuItem::class);

        $itemIds = $request->validated('ordem');

        DB::transaction(function () use ($itemIds): void {
            foreach (array_values($itemIds) as $indice => $itemId) {
                MenuItem::query()
                    ->whereKey($itemId)
                    ->update(['ordem' => $indice + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * @param  list<array{id:?int, rotulo:string, url:string, target_blank:bool}>  $links
     */
    private function sincronizarLinks(MenuItem $menuItem, array $links): void
    {
        $idsMantidos = [];

        foreach (array_values($links) as $indice => $link) {
            $filho = null;

            if (! empty($link['id'])) {
                $filho = $menuItem->filhos()->whereKey($link['id'])->first();
            }

            $atributos = [
                'rotulo' => $link['rotulo'],
                'url' => $link['url'],
                'target_blank' => $link['target_blank'],
                'ordem' => $indice + 1,
                'ativo' => true,
                'parent_id' => $menuItem->id,
            ];

            if ($filho !== null) {
                $filho->update($atributos);
            } else {
                $filho = $menuItem->filhos()->create($atributos);
            }

            $idsMantidos[] = $filho->id;
        }

        $menuItem->filhos()
            ->when(
                $idsMantidos !== [],
                fn ($query) => $query->whereKeyNot($idsMantidos),
            )
            ->get()
            ->each(fn (MenuItem $filho): bool => (bool) $filho->delete());
    }

    private function proximaOrdem(?int $parentId): int
    {
        return ((int) MenuItem::query()
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId),
            )
            ->max('ordem')) + 1;
    }
}
