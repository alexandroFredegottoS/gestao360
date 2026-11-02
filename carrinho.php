<?php
require __DIR__ . '/includes/auth.php'; if (!eh_cliente()) redirecionar('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $carrinho = carrinho();
  foreach ($_POST['qtd'] ?? [] as $id => $qtd) { $id=(int)$id; $qtd=(int)$qtd; if ($qtd < 1) unset($carrinho[$id]); else $carrinho[$id] = min($qtd, saldo_venda_produto($dados, $id)); }
  salvar_carrinho($carrinho); flash('Carrinho atualizado.'); redirecionar('carrinho.php');
}
$itens=itens_carrinho($dados); $pagina='carrinho'; $titulo='Carrinho'; include __DIR__ . '/includes/header.php';
?>
<h1 class="titulo">Seu carrinho</h1><p class="subtitulo">Revise as quantidades antes de ir ao pagamento.</p>
<?php if (!$itens): ?><div class="painel"><p class="vazio">Seu carrinho está vazio.</p><a class="btn btn-primario" href="loja.php">Ver produtos</a></div><?php else: ?><form method="post" class="painel form"><div class="tabela-rolagem"><table class="tabela"><thead><tr><th>Produto</th><th>Preço</th><th>Quantidade</th><th>Subtotal</th></tr></thead><tbody><?php foreach($itens as $item): ?><tr><td><b><?= e($item['nome']) ?></b><small>Disponível: <?= $item['saldo'] ?></small></td><td>R$ <?= number_format($item['preco'],2,',','.') ?></td><td><input class="qtd-carrinho" type="number" name="qtd[<?= $item['produto_id'] ?>]" min="0" max="<?= $item['saldo'] ?>" value="<?= $item['quantidade'] ?>"></td><td>R$ <?= number_format($item['subtotal'],2,',','.') ?></td></tr><?php endforeach; ?></tbody></table></div><div class="checkout-resumo"><b>Total: R$ <?= number_format(total_carrinho($dados),2,',','.') ?></b><div class="botoes"><button class="btn btn-suave">Atualizar carrinho</button><a class="btn btn-primario" href="checkout.php">Ir para pagamento</a></div></div></form><?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
