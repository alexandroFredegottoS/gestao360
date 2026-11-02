<?php
require __DIR__ . '/includes/auth.php';
exigir_operacao();
exigir_estoque();

$lotes   = buscar_lotes($dados, 'validade');
$cont    = contar_por_status($lotes);
$entradas = entradas_recentes($dados);

// Alertas = lotes vencidos ou urgentes
$alertas_lista = array_filter($lotes, fn($l) => in_array(status_lote((int)$l['dias'])[0], ['vencido', 'urgente'], true));
$pedidos_online = $dados['pedidos'] ?? [];
usort($pedidos_online, fn($a, $b) => strcmp($b['criado_em'], $a['criado_em']));
$nomes_clientes = array_column($dados['usuarios'], 'nome', 'id');
$classes_pagamento = ['Pago'=>'normal', 'Pendente'=>'atencao', 'Pagamento não efetuado'=>'vencido'];

$pagina = 'dashboard'; $titulo = 'Home';
include __DIR__ . '/includes/header.php';
?>
<h1 class="titulo">Home</h1>
<p class="subtitulo">Controle de estoque para diferentes tipos de comércio — <?= e(data_extenso()) ?></p>

<section class="cards">
  <div class="card-num c-total"><span>Total de produtos</span><strong><?= count($lotes) ?></strong><small>itens em estoque</small></div>
  <div class="card-num c-normal"><span>Status normal</span><strong><?= $cont['normal'] ?></strong><small>validade &gt; 30 dias</small></div>
  <div class="card-num c-atencao"><span>Atenção</span><strong><?= $cont['atencao'] ?></strong><small>15 – 30 dias</small></div>
  <div class="card-num c-urgente"><span>Urgente</span><strong><?= $cont['urgente'] ?></strong><small>menos de 15 dias</small></div>
  <div class="card-num c-vencido"><span>Vencidos</span><strong><?= $cont['vencido'] ?></strong><small>bloqueados</small></div>
</section>

<section class="duas-colunas">
  <div>
  <div class="painel">
    <div class="painel-topo"><h2>Alertas ativos</h2><a href="monitoramento.php">Ver todos →</a></div>
    <?php if (!$alertas_lista): ?>
      <p class="vazio">Nenhum alerta no momento. Todos os lotes estão dentro da validade.</p>
    <?php endif; ?>
    <?php foreach ($alertas_lista as $l): [$cls, $rot] = status_lote((int)$l['dias']); ?>
      <div class="alerta alerta-<?= $cls ?>">
        <span class="ponto"></span>
        <div class="alerta-txt"><b><?= e($l['nome']) ?></b><small>Lote <?= e($l['codigo']) ?> · <?= e(fmt_qtd($l)) ?></small></div>
        <span class="pilula"><?= $cls === 'vencido' ? 'Vencido' : (int)$l['dias'] . 'd' ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="painel pedidos-online">
    <div class="painel-topo"><h2>Pedidos online</h2><span class="contador-pedidos"><?= count($pedidos_online) ?></span></div>
    <?php if (!$pedidos_online): ?><p class="vazio">Nenhum pedido online registrado.</p><?php endif; ?>
    <?php foreach ($pedidos_online as $pedido): $classe = $classes_pagamento[$pedido['status']] ?? 'atencao'; ?>
      <div class="linha-pedido-online">
        <div><b>Pedido #<?= $pedido['id'] ?> — <?= e($nomes_clientes[$pedido['usuario_id']] ?? 'Cliente') ?></b><small><?= count($pedido['itens']) ?> item(ns) · <?= e($pedido['pagamento']) ?> · <?= date('d/m H:i', strtotime($pedido['criado_em'])) ?></small></div>
        <div class="pedido-online-direita"><b>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></b><span class="selo selo-<?= $classe ?>"><?= e($pedido['status']) ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
  </div>

  <div>
    <div class="painel">
      <h2>Ações rápidas</h2>
      <a class="acao acao-1" href="entrada.php">+ &nbsp;Nova entrada de produtos</a>
      <a class="acao acao-2" href="saida.php">↗ &nbsp;Registrar saída</a>
      <a class="acao acao-3" href="monitoramento.php">◎ &nbsp;Ver monitoramento</a>
    </div>
    <div class="painel">
      <h2>Entradas recentes</h2>
      <?php if (!$entradas): ?><p class="vazio">Nenhuma entrada registrada ainda.</p><?php endif; ?>
      <?php foreach ($entradas as $m): ?>
        <div class="linha-entrada">
          <div><b><?= e($m['nome']) ?></b><small><?= e($m['quantidade'] . ' ' . $m['unidade']) ?></small></div>
          <small><?= date('d/m H:i', strtotime($m['criado_em'])) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
