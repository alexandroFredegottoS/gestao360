<?php
require __DIR__ . '/includes/auth.php';
exigir_operacao();
exigir_estoque();

$lotes = buscar_lotes($dados, 'id');
$cont  = contar_por_status($lotes);
$normalizarBusca = static function (string $texto): string {
  return function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);
};

$pagina = 'monitoramento'; $titulo = 'Monitoramento';
include __DIR__ . '/includes/header.php';
?>
<div class="cabecalho-pag">
  <div>
    <h1 class="titulo">Monitoramento de Validade</h1>
    <p class="subtitulo">Acompanhamento automático por faixa de vencimento</p>
  </div>
  <a class="btn btn-primario" href="entrada.php">+ Nova Entrada</a>
</div>

<p class="legenda">
  <span><i class="bolinha b-normal"></i>Mais de 30 dias — Status Normal</span>
  <span><i class="bolinha b-atencao"></i>15 a 30 dias — Atenção</span>
  <span><i class="bolinha b-urgente"></i>Menos de 15 dias — Urgente</span>
  <span><i class="bolinha b-vencido"></i>Vencido — Venda bloqueada</span>
</p>

<div class="painel painel-tabela">
  <div class="abas-barra">
    <div class="abas" role="tablist">
      <button type="button" class="aba ativa" data-filtro="todos" aria-selected="true">Todos (<?= count($lotes) ?>)</button>
      <button type="button" class="aba" data-filtro="normal" aria-selected="false">Normal (<?= $cont['normal'] ?>)</button>
      <button type="button" class="aba" data-filtro="atencao" aria-selected="false">Atenção (<?= $cont['atencao'] ?>)</button>
      <button type="button" class="aba" data-filtro="urgente" aria-selected="false">Urgente (<?= $cont['urgente'] ?>)</button>
      <button type="button" class="aba" data-filtro="vencido" aria-selected="false">Vencidos (<?= $cont['vencido'] ?>)</button>
    </div>
    <input type="search" id="busca" placeholder="Buscar produto, lote ou categoria..." aria-label="Buscar produto, lote ou categoria">
  </div>

  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr><th>Produto</th><th>Lote</th><th>Qtd.</th><th>Validade</th><th>Dias</th><th>Status</th><th>Ação</th></tr></thead>
    <tbody>
    <?php foreach ($lotes as $l): [$cls, $rot] = status_lote((int)$l['dias']); ?>
      <tr data-status="<?= $cls ?>" data-busca="<?= e($normalizarBusca($l['nome'] . ' ' . $l['codigo'] . ' ' . $l['categoria'])) ?>">
        <td><b><?= e($l['nome']) ?></b><small><?= e($l['categoria']) ?></small></td>
        <td class="mono"><?= e($l['codigo']) ?></td>
        <td><?= e(fmt_qtd($l)) ?></td>
        <td><?= data_br($l['validade']) ?></td>
        <td class="dias dias-<?= $cls ?>"><?= fmt_dias((int)$l['dias']) ?></td>
        <td><span class="selo selo-<?= $cls ?>">● <?= $rot ?></span></td>
        <td><button type="button" class="btn btn-suave btn-detalhes"
              data-produto="<?= e($l['nome']) ?>" data-lote="<?= e($l['codigo']) ?>"
              data-qtd="<?= e(fmt_qtd($l)) ?>" data-validade="<?= data_br($l['validade']) ?>"
              data-nf="<?= e($l['nf']) ?>" data-fornecedor="<?= e($l['fornecedor']) ?>"
              data-emissao="<?= data_br($l['data_emissao']) ?>" data-status="<?= $rot ?>"
              data-lote-id="<?= $l['id'] ?>" data-vencido="<?= $cls === 'vencido' ? '1' : '0' ?>">Detalhes</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p class="vazio" id="sem-resultado" hidden>Nenhum lote encontrado nesse filtro.</p>
  </div>
</div>

<dialog id="modal-detalhes">
  <h2 id="md-produto"></h2>
  <dl>
    <dt>Lote</dt><dd id="md-lote"></dd>
    <dt>Quantidade</dt><dd id="md-qtd"></dd>
    <dt>Validade</dt><dd id="md-validade"></dd>
    <dt>Status</dt><dd id="md-status"></dd>
    <dt>Nota fiscal</dt><dd id="md-nf"></dd>
    <dt>Fornecedor</dt><dd id="md-fornecedor"></dd>
    <dt>Emissão da NF</dt><dd id="md-emissao"></dd>
  </dl>
  <div class="modal-acoes">
    <a class="btn btn-suave" id="md-saida" href="saida.php">Registrar saída</a>
    <button type="button" class="btn btn-primario" id="md-fechar">Fechar</button>
  </div>
</dialog>
<?php include __DIR__ . '/includes/footer.php'; ?>
