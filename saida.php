<?php
require __DIR__ . '/includes/auth.php';
exigir_operacao();
if (eh_caixa()) redirecionar('caixa.php');

$erros = [];
$v = ['lote_id' => (int)($_GET['lote'] ?? 0), 'quantidade' => '', 'motivo' => MOTIVOS_SAIDA[0], 'responsavel' => ''];
if ($v['lote_id'] && isset($dados['lotes'][$v['lote_id']]) && dias_ate($dados['lotes'][$v['lote_id']]['validade']) < 0) {
    $v['motivo'] = MOTIVOS_LOTE_VENCIDO[0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = ler_saida($_POST);
    $resultado = registrar_saida($dados, $v, $_SESSION['usuario_nome']);

    if ($resultado['erro']) {
        $erros[] = $resultado['erro'];
    } else {
        flash('Saída registrada: ' . $v['quantidade'] . ' × ' . $resultado['produto'] . '. Estoque atualizado.');
        redirecionar('saida.php');
    }
}

$lotes = buscar_lotes($dados, 'nome');

$pagina = 'saida'; $titulo = 'Saída de Produtos';
include __DIR__ . '/includes/header.php';
?>
<div class="estreito">
  <h1 class="titulo">Saída de Produtos</h1>
  <p class="subtitulo">Registre a baixa de quantidade e atualize o saldo do estoque</p>

  <?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>

  <form method="post" class="painel form" novalidate id="form-saida">
    <label for="lote_id">Produto *</label>
    <select id="lote_id" name="lote_id" required>
      <option value="" data-max="0">Selecione um produto…</option>
      <?php foreach ($lotes as $l): ?>
        <option value="<?= $l['id'] ?>" data-max="<?= $l['quantidade'] ?>" data-vencido="<?= $l['dias'] < 0 ? 1 : 0 ?>"
          <?= $v['lote_id'] == $l['id'] ? 'selected' : '' ?>>
          <?= e($l['nome']) ?> — lote <?= e($l['codigo']) ?> (saldo: <?= $l['quantidade'] ?>)<?= $l['dias'] < 0 ? ' — VENCIDO' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="dica" id="dica-saldo" hidden></p>

    <div class="grade-2">
      <div><label for="quantidade">Quantidade a Baixar *</label>
        <input type="number" min="1" id="quantidade" name="quantidade" placeholder="0" value="<?= e($v['quantidade'] ?: '') ?>" required></div>
      <div><label for="motivo">Motivo da Saída</label>
        <select id="motivo" name="motivo">
          <?php foreach (MOTIVOS_SAIDA as $m): ?><option <?= $v['motivo'] === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <label for="responsavel">Responsável</label>
    <input id="responsavel" name="responsavel" placeholder="Nome do responsável (opcional)" value="<?= e($v['responsavel']) ?>">

    <div class="botoes">
      <a class="btn btn-suave" href="dashboard.php">Cancelar</a>
      <button class="btn btn-primario" type="submit">↗ Registrar Saída e Atualizar Quantidade</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
