<?php
require __DIR__ . '/includes/auth.php';
exigir_operacao();
exigir_estoque();

// Fluxo em 3 etapas, guardado na sessão: 1 Nota Fiscal -> 2 Dados do Lote -> 3 Confirmação
$etapa = (int)($_GET['etapa'] ?? 1);
$nf    = $_SESSION['entrada']['nf']   ?? ['numero' => '', 'emissao' => date('Y-m-d'), 'fornecedor' => ''];
$lote  = $_SESSION['entrada']['lote'] ?? lote_vazio();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'nf') {                      // --- Etapa 1 enviada
        $nf = ler_nf($_POST);
        $erros = validar_nf($nf);
        if (!$erros) {
            $_SESSION['entrada']['nf'] = $nf;
            redirecionar('entrada.php?etapa=2');
        }
        $etapa = 1;

    } elseif ($acao === 'lote') {              // --- Etapa 2 enviada
        $lote = ler_lote($_POST);
        $erros = validar_lote($dados, $lote);
        if (!$erros) {
            $_SESSION['entrada']['lote'] = $lote;
            redirecionar('entrada.php?etapa=3');
        }
        $etapa = 2;

    } elseif ($acao === 'confirmar') {         // --- Etapa 3 enviada
        if (empty($_SESSION['entrada']['nf']) || empty($_SESSION['entrada']['lote'])) {
            flash('A sessão expirou. Preencha a entrada novamente.', 'erro');
            redirecionar('entrada.php?etapa=1');
        }
        try {
            salvar_entrada($dados, $nf, $lote, $_SESSION['usuario_nome']);
            unset($_SESSION['entrada']);
            flash('Entrada registrada: lote ' . $lote['codigo'] . ' adicionado ao estoque.');
            redirecionar('monitoramento.php');
        } catch (Exception $ex) {
            $erros[] = 'Não foi possível salvar a entrada. Tente novamente.';
            $etapa = 3;
        }
    }
}

// Impede pular etapas (ex.: abrir ?etapa=3 sem ter preenchido as anteriores)
if ($etapa < 1 || $etapa > 3) $etapa = 1;
if ($etapa >= 2 && empty($_SESSION['entrada']['nf'])) $etapa = 1;
if ($etapa == 3 && empty($_SESSION['entrada']['lote'])) $etapa = 2;

$produtos = array_values($dados['produtos']);
usort($produtos, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));

// Nome do produto para o resumo da etapa 3
$nome_produto = '';
if ($lote['produto_id'] === 'novo') {
    $nome_produto = $lote['novo_nome'] . ' (novo produto)';
} else {
    foreach ($produtos as $p) {
        if ($p['id'] == $lote['produto_id']) $nome_produto = $p['nome'];
    }
}

$pagina = 'entrada'; $titulo = 'Nova Entrada';
include __DIR__ . '/includes/header.php';
$passos = [1 => 'Nota Fiscal', 2 => 'Dados do Lote', 3 => 'Confirmação'];
?>
<div class="estreito">
  <h1 class="titulo">Nova Entrada de Produtos</h1>
  <p class="subtitulo">Registre a nota fiscal, lote e validade dos itens recebidos</p>

  <ol class="passos">
    <?php foreach ($passos as $n => $rot): ?>
      <li class="<?= $n < $etapa ? 'feito' : ($n == $etapa ? 'atual' : '') ?>"><span><?= $n < $etapa ? '✓' : $n ?></span><?= $rot ?></li>
    <?php endforeach; ?>
  </ol>

  <?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>

  <form method="post" class="painel form" novalidate id="form-entrada">
  <?php if ($etapa == 1): ?>
    <input type="hidden" name="acao" value="nf">
    <h2>Dados da Nota Fiscal</h2>
    <div class="grade-2">
      <div><label for="numero">Número da NF *</label>
        <input id="numero" name="numero" placeholder="NF-00000" value="<?= e($nf['numero']) ?>" required></div>
      <div><label for="emissao">Data de Emissão *</label>
        <input type="date" id="emissao" name="emissao" value="<?= e($nf['emissao']) ?>" required></div>
    </div>
    <label for="fornecedor">Fornecedor *</label>
    <input id="fornecedor" name="fornecedor" placeholder="Nome do fornecedor" value="<?= e($nf['fornecedor']) ?>" required>
    <button class="btn btn-primario btn-bloco" type="submit">Registrar →</button>

  <?php elseif ($etapa == 2): ?>
    <input type="hidden" name="acao" value="lote">
    <h2>Dados do Lote</h2>
    <label for="produto_id">Produto *</label>
    <select id="produto_id" name="produto_id" required>
      <option value="">Selecione um produto…</option>
      <?php foreach ($produtos as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $lote['produto_id'] == $p['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
      <?php endforeach; ?>
      <option value="novo" <?= $lote['produto_id'] === 'novo' ? 'selected' : '' ?>>+ Cadastrar novo produto</option>
    </select>
    <div id="bloco-novo" class="grade-2" hidden>
      <div><label for="novo_nome">Nome do novo produto *</label><input id="novo_nome" name="novo_nome" value="<?= e($lote['novo_nome']) ?>"></div>
      <div><label for="categoria">Categoria *</label><input id="categoria" name="categoria" placeholder="Ex.: Higiene, medicamentos, alimentos" value="<?= e($lote['categoria']) ?>"></div>
    </div>
    <div class="grade-2">
      <div><label for="codigo">Código do lote *</label><input id="codigo" name="codigo" placeholder="BEB2026-009" value="<?= e($lote['codigo']) ?>" required></div>
      <div><label for="validade">Validade *</label><input type="date" id="validade" name="validade" value="<?= e($lote['validade']) ?>" required></div>
    </div>
    <div class="grade-3">
      <div><label for="quantidade">Quantidade *</label><input type="number" min="1" id="quantidade" name="quantidade" value="<?= e($lote['quantidade']) ?>" required></div>
      <div><label for="unidade">Unidade</label>
        <select id="unidade" name="unidade">
          <?php foreach (UNIDADES as $u): ?><option <?= $lote['unidade'] === $u ? 'selected' : '' ?>><?= $u ?></option><?php endforeach; ?>
        </select></div>
      <div><label for="itens">Itens por embalagem</label><input type="number" min="0" id="itens" name="itens" placeholder="Ex.: 24" value="<?= e($lote['itens'] ?: '') ?>"></div>
    </div>
    <div class="botoes">
      <a class="btn btn-suave" href="entrada.php?etapa=1">Voltar</a>
      <button class="btn btn-primario" type="submit">Continuar →</button>
    </div>

  <?php else: ?>
    <input type="hidden" name="acao" value="confirmar">
    <h2>Confirme os dados da entrada</h2>
    <dl class="resumo">
      <dt>Nota fiscal</dt><dd><?= e($nf['numero']) ?> — emitida em <?= data_br($nf['emissao']) ?></dd>
      <dt>Fornecedor</dt><dd><?= e($nf['fornecedor']) ?></dd>
      <dt>Produto</dt><dd><?= e($nome_produto) ?></dd>
      <dt>Lote</dt><dd class="mono"><?= e($lote['codigo']) ?></dd>
      <dt>Quantidade</dt><dd><?= e($lote['quantidade'] . ' ' . $lote['unidade'] . ($lote['itens'] ? ' c/ ' . $lote['itens'] : '')) ?></dd>
      <dt>Validade</dt><dd><?= data_br($lote['validade']) ?></dd>
    </dl>
    <div class="botoes">
      <a class="btn btn-suave" href="entrada.php?etapa=2">Voltar</a>
      <button class="btn btn-primario" type="submit">Confirmar entrada</button>
    </div>
  <?php endif; ?>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
