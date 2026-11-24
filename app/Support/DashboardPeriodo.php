<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class DashboardPeriodo
{
    public const string HOJE = 'hoje';

    public const string ULTIMOS_7 = '7d';

    public const string ULTIMOS_30 = '30d';

    public const string ESTE_MES = 'este_mes';

    public const string MES_PASSADO = 'mes_passado';

    public const string MES = 'mes';

    public const string PERSONALIZADO = 'personalizado';

    /**
     * @param  list<array{valor: string, rotulo: string}>  $mesesOpcoes
     */
    public function __construct(
        public readonly string $chave,
        public readonly CarbonImmutable $inicio,
        public readonly CarbonImmutable $fim,
        public readonly string $rotulo,
        public readonly string $rotuloBotaoMes,
        public readonly ?string $mesRef,
        public readonly array $mesesOpcoes,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $agora = CarbonImmutable::now(config('app.timezone'));
        $chave = (string) $request->query('periodo', self::ESTE_MES);
        $mesRef = $request->query('mes');
        $mesRef = is_string($mesRef) && preg_match('/^\d{4}-\d{2}$/', $mesRef) === 1
            ? $mesRef
            : null;

        [$inicio, $fim, $chave, $mesRef] = match ($chave) {
            self::HOJE => [
                $agora->startOfDay(),
                $agora->endOfDay(),
                self::HOJE,
                null,
            ],
            self::ULTIMOS_7 => [
                $agora->subDays(6)->startOfDay(),
                $agora->endOfDay(),
                self::ULTIMOS_7,
                null,
            ],
            self::ULTIMOS_30 => [
                $agora->subDays(29)->startOfDay(),
                $agora->endOfDay(),
                self::ULTIMOS_30,
                null,
            ],
            self::MES_PASSADO => [
                $agora->subMonthNoOverflow()->startOfMonth(),
                $agora->subMonthNoOverflow()->endOfMonth(),
                self::MES_PASSADO,
                $agora->subMonthNoOverflow()->format('Y-m'),
            ],
            self::MES => self::intervaloDoMes($mesRef ?? $agora->format('Y-m'), $agora),
            self::PERSONALIZADO => self::intervaloPersonalizado($request, $agora),
            default => [
                $agora->startOfMonth(),
                $agora->endOfMonth(),
                self::ESTE_MES,
                $agora->format('Y-m'),
            ],
        };

        if ($inicio->greaterThan($fim)) {
            [$inicio, $fim] = [$fim->startOfDay(), $inicio->endOfDay()];
        }

        $mesesOpcoes = self::montarMesesOpcoes($agora);
        $rotuloBotaoMes = $mesRef
            ? self::rotuloMesCurto(CarbonImmutable::createFromFormat('Y-m', $mesRef)->startOfMonth())
            : self::rotuloMesCurto($agora->startOfMonth());

        return new self(
            chave: $chave,
            inicio: $inicio,
            fim: $fim,
            rotulo: self::montarRotulo($chave, $inicio, $fim, $mesRef),
            rotuloBotaoMes: $rotuloBotaoMes,
            mesRef: $mesRef,
            mesesOpcoes: $mesesOpcoes,
        );
    }

    public function estaAtivo(string $chave): bool
    {
        return $this->chave === $chave;
    }

    /**
     * @param  array{periodo?: string, mes?: string|null, inicio?: string|null, fim?: string|null}  $params
     */
    public function url(array $params = []): string
    {
        $query = [
            'periodo' => $params['periodo'] ?? $this->chave,
        ];

        if (($query['periodo'] ?? null) === self::MES) {
            $query['mes'] = $params['mes'] ?? $this->mesRef;
        }

        if (($query['periodo'] ?? null) === self::PERSONALIZADO) {
            $query['inicio'] = $params['inicio'] ?? $this->inicio->toDateString();
            $query['fim'] = $params['fim'] ?? $this->fim->toDateString();
        }

        return route('dashboard', array_filter(
            $query,
            static fn (mixed $valor): bool => $valor !== null && $valor !== '',
        ));
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string, 3: string}
     */
    private static function intervaloDoMes(string $mesRef, CarbonImmutable $agora): array
    {
        $base = CarbonImmutable::createFromFormat('Y-m', $mesRef, $agora->timezone)?->startOfMonth()
            ?? $agora->startOfMonth();

        return [
            $base->startOfMonth(),
            $base->endOfMonth(),
            self::MES,
            $base->format('Y-m'),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string, 3: null}
     */
    private static function intervaloPersonalizado(Request $request, CarbonImmutable $agora): array
    {
        $inicioRaw = $request->query('inicio');
        $fimRaw = $request->query('fim');

        $inicio = is_string($inicioRaw) && $inicioRaw !== ''
            ? CarbonImmutable::parse($inicioRaw, $agora->timezone)->startOfDay()
            : $agora->startOfMonth();

        $fim = is_string($fimRaw) && $fimRaw !== ''
            ? CarbonImmutable::parse($fimRaw, $agora->timezone)->endOfDay()
            : $agora->endOfDay();

        return [$inicio, $fim, self::PERSONALIZADO, null];
    }

    private static function montarRotulo(string $chave, CarbonImmutable $inicio, CarbonImmutable $fim, ?string $mesRef): string
    {
        return match ($chave) {
            self::HOJE => 'Hoje · '.$inicio->format('d/m/Y'),
            self::ULTIMOS_7 => 'Últimos 7 dias',
            self::ULTIMOS_30 => 'Últimos 30 dias',
            self::ESTE_MES => self::rotuloMes($inicio),
            self::MES_PASSADO => 'Mês passado · '.self::rotuloMes($inicio),
            self::MES => self::rotuloMes(
                $mesRef
                    ? CarbonImmutable::createFromFormat('Y-m', $mesRef)->startOfMonth()
                    : $inicio
            ),
            self::PERSONALIZADO => $inicio->format('d/m/Y').' — '.$fim->format('d/m/Y'),
            default => self::rotuloMes($inicio),
        };
    }

    private static function rotuloMes(CarbonImmutable $data): string
    {
        return self::nomeMes($data->month).' '.$data->year;
    }

    private static function rotuloMesCurto(CarbonImmutable $data): string
    {
        return self::nomeMes($data->month);
    }

    private static function nomeMes(int $mes): string
    {
        return match ($mes) {
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
            default => '',
        };
    }

    /**
     * @return list<array{valor: string, rotulo: string}>
     */
    private static function montarMesesOpcoes(CarbonImmutable $agora): array
    {
        $opcoes = [];

        for ($i = 0; $i < 18; $i++) {
            $mes = $agora->startOfMonth()->subMonthsNoOverflow($i);
            $opcoes[] = [
                'valor' => $mes->format('Y-m'),
                'rotulo' => self::rotuloMes($mes),
            ];
        }

        return $opcoes;
    }
}
