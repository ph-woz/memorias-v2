<?php

namespace App\Http\Requests\Menu\Concerns;

use App\Models\MenuItem;
use Illuminate\Validation\Validator;

trait ValidaMenuItem
{
    /**
     * @return array<string, mixed>
     */
    protected function regrasMenuItem(?int $ignoradoId = null): array
    {
        return [
            'rotulo' => ['required', 'string', 'max:120'],
            'url' => ['nullable', 'string', 'max:2048'],
            'target_blank' => ['required', 'boolean'],
            'ativo' => ['required', 'boolean'],
            'links' => ['nullable', 'array'],
            'links.*.id' => ['nullable', 'integer', 'exists:menu_itens,id'],
            'links.*.rotulo' => ['required', 'string', 'max:120'],
            'links.*.url' => ['required', 'string', 'max:2048'],
            'links.*.target_blank' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function atributosMenuItem(): array
    {
        return [
            'rotulo' => 'texto',
            'url' => 'URL',
            'target_blank' => 'abrir em nova aba',
            'ativo' => 'ativo',
            'links' => 'links',
            'links.*.rotulo' => 'texto do link',
            'links.*.url' => 'URL do link',
            'links.*.target_blank' => 'abrir em nova aba',
        ];
    }

    protected function prepararBooleansMenuItem(): void
    {
        $links = collect($this->input('links', []))
            ->filter(fn (mixed $link): bool => is_array($link))
            ->values()
            ->map(function (array $link): array {
                return [
                    'id' => filled($link['id'] ?? null) ? (int) $link['id'] : null,
                    'rotulo' => trim((string) ($link['rotulo'] ?? '')),
                    'url' => trim((string) ($link['url'] ?? '')),
                    'target_blank' => filter_var($link['target_blank'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            })
            ->all();

        $this->merge([
            'target_blank' => $this->boolean('target_blank'),
            'ativo' => $this->boolean('ativo'),
            'url' => $this->filled('url') ? trim((string) $this->input('url')) : null,
            'links' => $links,
        ]);
    }

    protected function validarRegrasDeNegocioMenuItem(Validator $validator, ?int $ignoradoId = null): void
    {
        $validator->after(function (Validator $validator) use ($ignoradoId): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $links = $this->input('links', []);
            $url = $this->input('url');

            if ($links === [] && blank($url)) {
                $validator->errors()->add('url', 'A URL é obrigatória quando o item não tem links.');
            }

            if ($ignoradoId === null || $links === []) {
                return;
            }

            foreach ($links as $indice => $link) {
                $linkId = $link['id'] ?? null;

                if ($linkId === null) {
                    continue;
                }

                $pertenceAoItem = MenuItem::query()
                    ->whereKey($linkId)
                    ->where('parent_id', $ignoradoId)
                    ->exists();

                if (! $pertenceAoItem) {
                    $validator->errors()->add("links.{$indice}.id", 'O link informado não pertence a este item.');
                }
            }
        });
    }
}
