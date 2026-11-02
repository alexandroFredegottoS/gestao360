<?php
require __DIR__ . '/includes/auth.php';
if (!eh_cliente()) redirecionar('dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $produtoId = (int)($_POST['produto_id'] ?? 0);
  if (isset($dados['produtos'][$produtoId]) && saldo_venda_produto($dados, $produtoId) > 0) {
    $carrinho = carrinho(); $carrinho[$produtoId] = min(($carrinho[$produtoId] ?? 0) + 1, saldo_venda_produto($dados, $produtoId)); salvar_carrinho($carrinho);
    flash('Produto adicionado ao carrinho.');
  }
  redirecionar('loja.php');
}
$produtos = produtos_loja($dados); $pagina = 'loja'; $titulo = 'Loja'; include __DIR__ . '/includes/header.php';
?>
<div class="cabecalho-pag"><div><h1 class="titulo">Produtos disponíveis</h1><p class="subtitulo">Escolha seus produtos e finalize uma compra demonstrativa.</p></div><a class="btn btn-primario" href="carrinho.php">🛒 Ver carrinho (<?= total_itens_carrinho() ?>)</a></div>
<section class="produtos-grid">
<?php foreach ($produtos as $produto): ?><article class="produto-card"><span class="produto-categoria"><?= e($produto['categoria']) ?></span><h2><?= e($produto['nome']) ?></h2><p class="produto-saldo"><?= $produto['saldo'] ?> unidade(s) disponível(is)</p><strong class="preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></strong><form method="post"><input type="hidden" name="produto_id" value="<?= $produto['id'] ?>"><button class="btn btn-primario btn-bloco">Adicionar ao carrinho</button></form></article><?php endforeach; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
