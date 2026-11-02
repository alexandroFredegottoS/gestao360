<?php
// Regras simples do estoque. Os dados são arrays guardados na sessão.

const UNIDADES = ['caixas', 'fardos', 'garrafas', 'unidades'];
const MOTIVOS_SAIDA = ['Venda no Balcão', 'Venda no Atacado', 'Perda / Avaria', 'Consumo Interno', 'Devolução ao Fornecedor'];
const MOTIVOS_LOTE_VENCIDO = ['Perda / Avaria', 'Devolução ao Fornecedor'];
const DIAS_URGENTE = 15;
const DIAS_ATENCAO = 30;

function status_lote(int $dias): array {
    if ($dias < 0) return ['vencido', 'Vencido'];
    if ($dias < DIAS_URGENTE) return ['urgente', 'Urgente'];
    if ($dias <= DIAS_ATENCAO) return ['atencao', 'Atenção'];
    return ['normal', 'Normal'];
}

function fmt_qtd(array $lote): string {
    return $lote['quantidade'] . ' ' . $lote['unidade'] . ($lote['itens_embalagem'] ? ' c/ ' . $lote['itens_embalagem'] : '');
}

function fmt_dias(int $dias): string { return $dias < 0 ? abs($dias) . 'd atrás' : $dias . 'd'; }

function dias_ate(string $validade): int {
    $hoje = new DateTimeImmutable('today');
    $data = new DateTimeImmutable($validade);
    return (int)$hoje->diff($data)->format('%r%a');
}

function buscar_lotes(array $dados, string $ordem = 'id'): array {
    $lotes = [];
    foreach ($dados['lotes'] as $lote) {
        if ($lote['quantidade'] <= 0) continue;
        $produto = $dados['produtos'][$lote['produto_id']];
        $nota = $dados['notas'][$lote['nota_id']];
        $lotes[] = array_merge($lote, $produto, [
            'nf' => $nota['numero'], 'fornecedor' => $nota['fornecedor'],
            'data_emissao' => $nota['emissao'], 'dias' => dias_ate($lote['validade']),
        ]);
    }
    if ($ordem === 'validade') usort($lotes, fn($a, $b) => strcmp($a['validade'], $b['validade']));
    if ($ordem === 'nome') usort($lotes, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));
    return $lotes;
}

function contar_por_status(array $lotes): array {
    $contagem = ['normal'=>0, 'atencao'=>0, 'urgente'=>0, 'vencido'=>0];
    foreach ($lotes as $lote) $contagem[status_lote((int)$lote['dias'])[0]]++;
    return $contagem;
}

function contar_alertas(array $dados): int {
    $total = 0;
    foreach ($dados['lotes'] as $lote) {
        if ($lote['quantidade'] > 0 && dias_ate($lote['validade']) < DIAS_URGENTE) $total++;
    }
    return $total;
}

function entradas_recentes(array $dados, int $limite = 4): array {
    $entradas = [];
    foreach ($dados['movimentacoes'] as $movimento) {
        if ($movimento['tipo'] !== 'entrada') continue;
        $lote = $dados['lotes'][$movimento['lote_id']];
        $produto = $dados['produtos'][$lote['produto_id']];
        $entradas[] = ['quantidade'=>$movimento['quantidade'], 'criado_em'=>$movimento['criado_em'],
                       'nome'=>$produto['nome'], 'unidade'=>$lote['unidade']];
    }
    usort($entradas, fn($a, $b) => strcmp($b['criado_em'], $a['criado_em']));
    return array_slice($entradas, 0, $limite);
}

function ler_nf(array $post): array {
    return ['numero'=>strtoupper(trim($post['numero'] ?? '')), 'emissao'=>$post['emissao'] ?? '', 'fornecedor'=>trim($post['fornecedor'] ?? '')];
}

function lote_vazio(): array {
    return ['produto_id'=>'', 'novo_nome'=>'', 'categoria'=>'', 'codigo'=>'', 'quantidade'=>'', 'unidade'=>'caixas', 'itens'=>'', 'validade'=>''];
}

function ler_lote(array $post): array {
    return ['produto_id'=>$post['produto_id'] ?? '', 'novo_nome'=>trim($post['novo_nome'] ?? ''),
            'categoria'=>trim($post['categoria'] ?? ''), 'codigo'=>strtoupper(trim($post['codigo'] ?? '')),
            'quantidade'=>(int)($post['quantidade'] ?? 0), 'unidade'=>$post['unidade'] ?? 'caixas',
            'itens'=>(int)($post['itens'] ?? 0), 'validade'=>$post['validade'] ?? ''];
}

function validar_nf(array $nf): array {
    $erros = [];
    if ($nf['numero'] === '') $erros[] = 'Informe o número da NF.';
    if (!strtotime($nf['emissao'])) $erros[] = 'Informe a data de emissão.';
    if ($nf['fornecedor'] === '') $erros[] = 'Informe o fornecedor.';
    return $erros;
}

function validar_lote(array $dados, array $lote): array {
    $erros = [];
    if ($lote['produto_id'] === 'novo') {
        if ($lote['novo_nome'] === '' || $lote['categoria'] === '') $erros[] = 'Informe o nome e a categoria do novo produto.';
    } elseif (!isset($dados['produtos'][(int)$lote['produto_id']])) {
        $erros[] = 'Selecione um produto.';
    }
    if ($lote['codigo'] === '') $erros[] = 'Informe o código do lote.';
    foreach ($dados['lotes'] as $existente) {
        if (strcasecmp($existente['codigo'], $lote['codigo']) === 0) { $erros[] = 'Já existe um lote com esse código.'; break; }
    }
    if ($lote['quantidade'] < 1) $erros[] = 'A quantidade deve ser maior que zero.';
    if (!in_array($lote['unidade'], UNIDADES, true)) $erros[] = 'Unidade inválida.';
    if (!strtotime($lote['validade'])) $erros[] = 'Informe a validade do lote.';
    return $erros;
}

function salvar_entrada(array &$dados, array $nf, array $lote, string $responsavel): void {
    if ($lote['produto_id'] === 'novo') {
        $produtoId = $dados['proximo_produto']++;
        $dados['produtos'][$produtoId] = ['id'=>$produtoId, 'nome'=>$lote['novo_nome'], 'categoria'=>$lote['categoria']];
    } else {
        $produtoId = (int)$lote['produto_id'];
    }

    $notaId = 0;
    foreach ($dados['notas'] as $id => $nota) {
        if (strcasecmp($nota['numero'], $nf['numero']) === 0) { $notaId = $id; break; }
    }
    if (!$notaId) {
        $notaId = count($dados['notas']) + 1;
        $dados['notas'][$notaId] = ['id'=>$notaId, 'numero'=>$nf['numero'], 'emissao'=>$nf['emissao'], 'fornecedor'=>$nf['fornecedor']];
    }

    $loteId = $dados['proximo_lote']++;
    $dados['lotes'][$loteId] = ['id'=>$loteId, 'produto_id'=>$produtoId, 'nota_id'=>$notaId,
        'codigo'=>$lote['codigo'], 'quantidade'=>$lote['quantidade'], 'unidade'=>$lote['unidade'],
        'itens_embalagem'=>$lote['itens'], 'validade'=>$lote['validade']];
    $dados['movimentacoes'][] = ['tipo'=>'entrada', 'lote_id'=>$loteId, 'quantidade'=>$lote['quantidade'],
        'responsavel'=>$responsavel, 'criado_em'=>date('Y-m-d H:i:s')];
}

function ler_saida(array $post): array {
    return ['lote_id'=>(int)($post['lote_id'] ?? 0), 'quantidade'=>(int)($post['quantidade'] ?? 0),
            'motivo'=>$post['motivo'] ?? '', 'responsavel'=>trim($post['responsavel'] ?? '')];
}

function registrar_saida(array &$dados, array $saida, string $usuario_logado, int $clienteId = 0): array {
    if (!$saida['lote_id']) return ['erro'=>'Selecione um produto.'];
    if ($saida['quantidade'] < 1) return ['erro'=>'A quantidade a baixar deve ser maior que zero.'];
    if (!in_array($saida['motivo'], MOTIVOS_SAIDA, true)) return ['erro'=>'Motivo inválido.'];
    if (!isset($dados['lotes'][$saida['lote_id']])) return ['erro'=>'Lote não encontrado.'];

    $lote =& $dados['lotes'][$saida['lote_id']];
    $dias = dias_ate($lote['validade']);
    if ($dias < 0 && !in_array($saida['motivo'], MOTIVOS_LOTE_VENCIDO, true)) {
        return ['erro'=>'Lote vencido: venda bloqueada. Só é possível dar baixa por perda ou devolução.'];
    }
    if ($saida['quantidade'] > $lote['quantidade']) return ['erro'=>'Quantidade maior que o saldo disponível (' . $lote['quantidade'] . ').'];

    $lote['quantidade'] -= $saida['quantidade'];
    $nome = $dados['produtos'][$lote['produto_id']]['nome'];
    $movimentacao = ['tipo'=>'saida', 'lote_id'=>$lote['id'], 'quantidade'=>$saida['quantidade'],
        'motivo'=>$saida['motivo'], 'responsavel'=>$saida['responsavel'] ?: $usuario_logado, 'criado_em'=>date('Y-m-d H:i:s')];
    if ($clienteId) $movimentacao['cliente_id'] = $clienteId;
    $dados['movimentacoes'][] = $movimentacao;
    return ['erro'=>'', 'produto'=>$nome];
}
