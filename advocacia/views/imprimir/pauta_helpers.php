<?php
/**
 * Helpers para impressão da pauta de audiências.
 */

function pautaFormatarDataPorExtenso(?string $data): string
{
    $texto = trim((string) $data);
    if ($texto === '') {
        return '';
    }

    $dt = DateTime::createFromFormat('d/m/Y', $texto);
    if (!$dt && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $texto, $m)) {
        $dt = DateTime::createFromFormat('Y-m-d', $m[1] . '-' . $m[2] . '-' . $m[3]);
    }
    if (!$dt) {
        $ts = strtotime($texto);
        if ($ts !== false) {
            $dt = (new DateTime())->setTimestamp($ts);
        }
    }
    if (!$dt) {
        return $texto;
    }

    $dias = [
        'Sunday'    => 'Domingo',
        'Monday'    => 'Segunda-feira',
        'Tuesday'   => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday'  => 'Quinta-feira',
        'Friday'    => 'Sexta-feira',
        'Saturday'  => 'Sábado',
    ];

    $meses = [
        1  => 'janeiro', 2  => 'fevereiro', 3  => 'março',
        4  => 'abril', 5  => 'maio', 6  => 'junho',
        7  => 'julho', 8  => 'agosto', 9  => 'setembro',
        10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];

    $diaSemana = $dias[$dt->format('l')] ?? $dt->format('l');
    $mes = $meses[(int) $dt->format('n')] ?? $dt->format('F');

    return $diaSemana . ', ' . (int) $dt->format('j') . ' de ' . $mes . ' de ' . $dt->format('Y');
}

function pautaChaveData(?string $data): string
{
    $texto = trim((string) $data);
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $texto)) {
        return $texto;
    }

    $ts = strtotime($texto);
    return $ts !== false ? date('d/m/Y', $ts) : $texto;
}

function pautaAgruparPorData(array $registros): array
{
    $grupos = [];

    foreach ($registros as $reg) {
        $chave = pautaChaveData($reg['DIA_AUD'] ?? '');
        if ($chave === '') {
            continue;
        }
        $grupos[$chave][] = $reg;
    }

    uksort($grupos, function (string $a, string $b): int {
        $da = DateTime::createFromFormat('d/m/Y', $a);
        $db = DateTime::createFromFormat('d/m/Y', $b);
        if ($da && $db) {
            return $da <=> $db;
        }

        return strcmp($a, $b);
    });

    return $grupos;
}

function pautaFormatarJunta(?string $junta): string
{
    $texto = trim((string) $junta);
    if ($texto === '') {
        return '';
    }

    if (stripos($texto, 'VARA') !== false) {
        return mb_strtoupper($texto, 'UTF-8');
    }

    return $texto . ' VARA';
}

function pautaFormatarHora(?string $hora): string
{
    $texto = trim((string) $hora);
    if ($texto === '') {
        return '';
    }

    if (preg_match('/^(\d{1,2}):(\d{2})/', $texto, $m)) {
        return (int) $m[1] . ':' . $m[2] . ' hs';
    }

    return $texto . ' hs';
}

function pautaTextoMaiusculo(?string $valor): string
{
    return mb_strtoupper(trim((string) $valor), 'UTF-8');
}

function pautaEnriquecerComFotos(ProcessoModel $model, array $grupos): array
{
    $ids = [];
    foreach ($grupos as $itens) {
        foreach ($itens as $reg) {
            $id = (int) ($reg['CADASTRO'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    }

    $comFoto = $model->cadastrosComFoto($ids);

    foreach ($grupos as $data => $itens) {
        foreach ($itens as $idx => $reg) {
            $id = (int) ($reg['CADASTRO'] ?? 0);
            $grupos[$data][$idx]['tem_foto'] = $id > 0 && isset($comFoto[$id]);
            $grupos[$data][$idx]['foto_url'] = $grupos[$data][$idx]['tem_foto']
                ? 'midia.php?id=' . $id . '&tipo=foto'
                : null;
        }
    }

    return $grupos;
}
