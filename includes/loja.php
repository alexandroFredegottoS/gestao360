<?php
// Recursos da vitrine. O pagamento é propositalmente demonstrativo: nenhum dado de cartão é salvo.

function saldo_venda_produto(array $dados, int $produtoId): int {
    $saldo = 0;
    foreach ($dados['lotes'] as $lote) {
        if ($lote['produto_id'] === $produtoId && $lote['quantidade'] > 0 && dias_ate($lote['validade']) >= 0) $saldo += $lote['quantidade'];
    }
    return $saldo;
}

function produtos_loja(array $dados): array {
    $produtos = [];
    foreach ($dados['produtos'] as $produto) {
        $saldo = saldo_venda_produto($dados, (int)$produto['id']);
        if ($saldo > 0) {
            $produto['saldo'] = $saldo;
            $produto['preco'] = (float)($produto['preco'] ?? 9.90);
            $produtos[] = $produto;
        }
    }
    usort($produtos, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));
    return $produtos;
}

function carrinho(): array { return $_SESSION['carrinho'] ?? []; }
function salvar_carrinho(array $itens): void { $_SESSION['carrinho'] = $itens; }
function total_itens_carrinho(): int { return array_sum(carrinho()); }

function itens_carrinho(array $dados): array {
    $itens = [];
    foreach (carrinho() as $produtoId => $quantidade) {
        if (!isset($dados['produtos'][$produtoId]) || $quantidade < 1) continue;
        $produto = $dados['produtos'][$produtoId];
        $preco = (float)($produto['preco'] ?? 9.90);
        $itens[] = ['produto_id'=>(int)$produtoId, 'nome'=>$produto['nome'], 'quantidade'=>(int)$quantidade,
                    'preco'=>$preco, 'subtotal'=>$preco * $quantidade, 'saldo'=>saldo_venda_produto($dados, (int)$produtoId)];
    }
    return $itens;
}

function total_carrinho(array $dados): float { return array_sum(array_column(itens_carrinho($dados), 'subtotal')); }

function finalizar_pedido(array &$dados, int $usuarioId, string $pagamento, array $endereco): array {
    $itens = itens_carrinho($dados);
    if (!$itens) return ['erro'=>'Seu carrinho está vazio.'];
    foreach ($itens as $item) {
        if ($item['quantidade'] > $item['saldo']) return ['erro'=>'O produto "' . $item['nome'] . '" não possui mais estoque suficiente.'];
    }
    foreach ($itens as $item) {
        $restante = $item['quantidade'];
        $lotes = array_filter($dados['lotes'], fn($l) => $l['produto_id'] === $item['produto_id'] && $l['quantidade'] > 0 && dias_ate($l['validade']) >= 0);
        uasort($lotes, fn($a, $b) => strcmp($a['validade'], $b['validade']));
        foreach ($lotes as $loteId => $lote) {
            $baixa = min($restante, $lote['quantidade']);
            $dados['lotes'][$loteId]['quantidade'] -= $baixa;
            $dados['movimentacoes'][] = ['tipo'=>'saida', 'lote_id'=>$loteId, 'quantidade'=>$baixa, 'motivo'=>'Venda Online', 'responsavel'=>'Loja virtual', 'criado_em'=>date('Y-m-d H:i:s')];
            $restante -= $baixa;
            if ($restante === 0) break;
        }
    }
    $pedidoId = ($dados['proximo_pedido'] ?? 1);
    $dados['proximo_pedido'] = $pedidoId + 1;
    $dados['pedidos'] ??= [];
    $rotulosPagamento = ['cartao'=>'Cartão de crédito', 'pix'=>'Pix', 'boleto'=>'Boleto'];
    $dados['pedidos'][] = ['id'=>$pedidoId, 'usuario_id'=>$usuarioId, 'itens'=>$itens, 'total'=>array_sum(array_column($itens, 'subtotal')),
        'pagamento'=>$rotulosPagamento[$pagamento], 'endereco'=>$endereco, 'status'=>'Pagamento aprovado (simulado)', 'criado_em'=>date('Y-m-d H:i:s')];
    salvar_carrinho([]);
    return ['erro'=>'', 'pedido_id'=>$pedidoId];
}
