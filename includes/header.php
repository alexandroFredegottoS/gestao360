<?php
// Espera $pagina (dashboard|monitoramento|entrada|saida) e $titulo
$iniciais = strtoupper(substr($_SESSION['usuario_nome'], 0, 2));
$alertas = 0;
if (eh_cliente()) {
  $menu = ['loja'=>['◈', 'Produtos', 'loja.php'], 'pedidos'=>['▤', 'Meus pedidos', 'pedidos.php']];
} elseif (eh_caixa()) {
  $menu = ['caixa'=>['↗', 'Vendas no balcão', 'caixa.php']];
} else {
  $alertas = contar_alertas($dados);
  $menu = ['dashboard'=>['▦', 'Home', 'dashboard.php'], 'monitoramento'=>['◎', 'Monitoramento', 'monitoramento.php'],
           'entrada'=>['+', 'Nova Entrada', 'entrada.php'], 'saida'=>['↗', 'Saída', 'saida.php'],
           'caixa'=>['↗', 'Caixa', 'caixa.php'], 'usuarios'=>['☺', 'Usuários', 'usuarios.php']];
  if (!eh_admin()) {
    unset($menu['usuarios'], $menu['caixa']);
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?> — Gestão 360</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="icon" href="img/logo.png">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
  <a class="marca" href="<?= pagina_inicial() ?>"><img class="logo" src="img/logo.png" alt="" width="40" height="40"> Gestão 360</a>
  <nav class="menu">
    <?php foreach ($menu as $chave => [$icone, $rotulo, $link]): ?>
      <a href="<?= $link ?>" class="<?= $pagina === $chave ? 'ativo' : '' ?>"><span aria-hidden="true"><?= $icone ?></span> <?= $rotulo ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="topbar-dir">
    <?php if (eh_cliente()): ?><a href="carrinho.php" class="badge-carrinho">🛒 <?= total_itens_carrinho() ?> item(ns)</a>
    <?php elseif ($alertas > 0): ?><a href="monitoramento.php" class="badge-alerta">⚠ <?= $alertas ?> alertas</a><?php endif; ?>
    <a href="logout.php" class="avatar" title="Sair (<?= e($_SESSION['usuario_nome']) ?>)"><?= e($iniciais) ?></a>
  </div>
</header>
<main class="conteudo">
<?php mostrar_flash(); ?>
