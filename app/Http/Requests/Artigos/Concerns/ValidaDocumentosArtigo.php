<?php

namespace App\Http\Requests\Artigos\Concerns;

use App\Models\Artigo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

trait ValidaDocumentosArtigo
{
    /**
     * @return array<string, list<ValidationRule|string>>
     */
    protected function regrasDocumentos(): array
    {
        return [
            'documentos' => ['nullable', 'array'],
            'documentos.*.id' => ['nullable', 'integer'],
            'documentos.*.titulo' => ['nullable', 'string', 'max:255'],
            'documentos.*.url' => ['nullable', 'string', 'url', 'max:2048'],
            'documentos.*.arquivo' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,odt,txt,rtf,zip,jpg,jpeg,png,webp',
                'max:20480',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function atributosDocumentos(): array
    {
        return [
            'documentos' => 'documentos',
            'documentos.*.id' => 'documento',
            'documentos.*.titulo' => 'título do documento',
            'documentos.*.url' => 'URL do documento',
            'documentos.*.arquivo' => 'arquivo do documento',
        ];
    }

    protected function validarOrigemDocumentos(Validator $validator, ?Artigo $artigo = null): void
    {
        $documentos = $this->input('documentos', []);

        if (! is_array($documentos)) {
            return;
        }

        foreach ($documentos as $indice => $documento) {
            if (! is_array($documento)) {
                continue;
            }

            $titulo = trim((string) ($documento['titulo'] ?? ''));
            $arquivo = $this->file("documentos.$indice.arquivo");
            $url = trim((string) ($documento['url'] ?? ''));
            $id = isset($documento['id']) && $documento['id'] !== ''
                ? (int) $documento['id']
                : null;

            $temTitulo = $titulo !== '';
            $temArquivoNovo = $arquivo !== null;
            $temUrl = $url !== '';
            $temExistente = false;

            if ($id !== null && $artigo !== null) {
                $existente = $artigo->documentos()->whereKey($id)->first();
                $temExistente = $existente !== null
                    && (
                        ($existente->arquivo !== null && $existente->arquivo !== '')
                        || ($existente->url !== null && $existente->url !== '')
                    );
            }

            $linhaVazia = ! $temTitulo && ! $temArquivoNovo && ! $temUrl && ! $temExistente;

            if ($linhaVazia) {
                continue;
            }

            if (! $temTitulo) {
                $validator->errors()->add(
                    "documentos.$indice.titulo",
                    __('O campo título do documento é obrigatório.'),
                );
            }

            if (! $temArquivoNovo && ! $temUrl && ! $temExistente) {
                $validator->errors()->add(
                    "documentos.$indice.arquivo",
                    __('Informe um arquivo ou uma URL.'),
                );
            }
        }
    }
}
