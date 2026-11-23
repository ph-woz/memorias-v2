<?php

namespace App\Http\Controllers;

use App\Actions\Banners\CreateBannerAction;
use App\Actions\Banners\DeleteBannerAction;
use App\Actions\Banners\ReorderBannersAction;
use App\Actions\Banners\UpdateBannerAction;
use App\DataTransferObjects\BannerData;
use App\Http\Requests\Banners\ReorderBannersRequest;
use App\Http\Requests\Banners\StoreBannerRequest;
use App\Http\Requests\Banners\UpdateBannerRequest;
use App\Models\Banner;
use App\Queries\Banners\ListBannersQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(Request $request, ListBannersQuery $query): View
    {
        $this->authorize('viewAny', Banner::class);

        $status = $request->string('status')->toString();
        if (! in_array($status, ['todos', 'ativos', 'desativados'], true)) {
            $status = 'todos';
        }

        return view('banners.index', [
            'banners' => $query->all(
                $request->string('q')->toString() ?: null,
                $status,
            ),
            'busca' => $request->string('q')->toString(),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Banner::class);

        return view('banners.create');
    }

    public function store(StoreBannerRequest $request, CreateBannerAction $action): RedirectResponse
    {
        $this->authorize('create', Banner::class);

        $banner = $action->execute(BannerData::fromArray($request->validated()));

        return redirect()
            ->route('banners.edit', $banner)
            ->with('status', 'banner-criado');
    }

    public function edit(Banner $banner): View
    {
        $this->authorize('update', $banner);

        return view('banners.edit', ['banner' => $banner]);
    }

    public function update(
        UpdateBannerRequest $request,
        Banner $banner,
        UpdateBannerAction $action,
    ): RedirectResponse {
        $action->execute($banner, BannerData::fromArray($request->validated()));

        return redirect()
            ->route('banners.edit', $banner)
            ->with('status', 'banner-atualizado');
    }

    public function destroy(Banner $banner, DeleteBannerAction $action): RedirectResponse
    {
        $this->authorize('delete', $banner);

        $action->execute($banner);

        return redirect()
            ->route('banners.index')
            ->with('status', 'banner-excluido');
    }

    public function reorder(
        ReorderBannersRequest $request,
        ReorderBannersAction $action,
    ): JsonResponse {
        $this->authorize('reorder', Banner::class);

        $action->execute($request->validated('ordem'));

        return response()->json(['ok' => true]);
    }
}
