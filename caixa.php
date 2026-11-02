<?php
require __DIR__ . '/includes/auth.php';
exigir_caixa();

$abasPermitidas = ['venda', 'consultar', 'pf', 'pj'];
$aba = $_GET['aba'] ?? 'venda';
if (!in_array($aba, $abasPermitidas, true)) $aba = 'venda';
$erros = [];

function cliente_por_documento(array $usuarios, string $documento): bool {
    foreach ($usuarios as $usuario) if (($usuario['perfil'] ?? '') === 'cliente' && preg_replace('/\D/', '', $usuario['cpf_cnpj'] ?? '') === $documento) return true;
    return false;
}

function cadastrar_cliente_caixa(array &$dados, array $cliente): void {
    $id = max(array_column($dados['usuarios'], 'id')) + 1;
    $dados['usuarios'][] = array_merge(['id'=>$id, 'email'=>'', 'senha'=>'', 'perfil'=>'cliente', 'data_nascimento'=>'', 'razao_social'=>'', 'nome_fantasia'=>'', 'responsavel'=>''], $cliente);
}

$lotes = buscar_lotes($dados, 'nome');
$lotesPorId = array_column($lotes, null, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'pf' || $acao === 'pj') {
        $aba = $acao;
        $nome = trim($_POST['nome'] ?? ''); $telefone = trim($_POST['telefone'] ?? '');
        $documento = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
        if ($nome === '') $erros[] = 'Informe o nome do cliente.';
        if ($telefone === '') $erros[] = 'Informe o telefone do cliente.';
        if ($acao === 'pf' && strlen($documento) !== 11) $erros[] = 'Informe um CPF com 11 dígitos.';
        if ($acao === 'pj' && strlen($documento) !== 14) $erros[] = 'Informe um CNPJ com 14 dígitos.';
        if ($documento !== '' && cliente_por_documento($dados['usuarios'], $documento)) $erros[] = 'Já existe um cliente cadastrado com este documento.';
        $email = strtolower(trim($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Informe um e-mail válido ou deixe o campo em branco.';
        if (!$erros) {
            $cliente = ['nome'=>$nome, 'email'=>$email, 'tipo_pessoa'=>$acao, 'cpf_cnpj'=>$documento, 'telefone'=>$telefone];
            if ($acao === 'pf') $cliente['data_nascimento'] = $_POST['data_nascimento'] ?? '';
            else { $cliente['razao_social'] = trim($_POST['razao_social'] ?? ''); $cliente['nome_fantasia'] = trim($_POST['nome_empresa'] ?? ''); $cliente['responsavel'] = trim($_POST['responsavel'] ?? ''); }
            if ($acao === 'pj' && ($cliente['razao_social'] === '' || $cliente['responsavel'] === '')) $erros[] = 'Informe a razão social e o responsável.';
            if (!$erros) { cadastrar_cliente_caixa($dados, $cliente); flash('Cliente cadastrado com sucesso.'); redirecionar('caixa.php?aba=consultar'); }
        }
    }

    if ($acao === 'venda_direta') {
        $aba = 'venda';
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $loteId = (int)($_POST['lote_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $formaPagamento = trim($_POST['forma_pagamento'] ?? 'dinheiro');
        $parcelas = max(1, min(12, (int)($_POST['parcelas'] ?? 1)));
        $formasPermitidas = ['dinheiro'=>'Dinheiro', 'pix'=>'Pix', 'debito'=>'Cartão de Débito', 'credito'=>'Cartão de Crédito'];

        $clienteEncontrado = null;
        foreach ($dados['usuarios'] as $usuario) {
            if ($usuario['id'] === $clienteId && ($usuario['perfil'] ?? '') === 'cliente') {
                $clienteEncontrado = $usuario;
                break;
            }
        }
        if (!$clienteEncontrado) $erros[] = 'Selecione um cliente cadastrado.';
        if (!isset($lotesPorId[$loteId])) $erros[] = 'Selecione um lote válido.';
        elseif ($lotesPorId[$loteId]['dias'] < 0) $erros[] = 'Este lote está vencido e a venda está bloqueada.';
        elseif ($quantidade < 1) $erros[] = 'A quantidade deve ser de pelo menos 1 unidade.';
        elseif ($quantidade > $lotesPorId[$loteId]['quantidade']) $erros[] = 'Quantidade maior que o saldo em estoque (' . $lotesPorId[$loteId]['quantidade'] . ').';
        if (!array_key_exists($formaPagamento, $formasPermitidas)) $erros[] = 'Forma de pagamento inválida.';

        if (!$erros) {
            $precoUnitario = (float)($lotesPorId[$loteId]['preco'] ?? 9.90);
            $totalVenda = $precoUnitario * $quantidade;
            $saida = [
                'lote_id' => $loteId,
                'quantidade' => $quantidade,
                'motivo' => 'Venda no Balcão',
                'responsavel' => ''
            ];

            $resultado = registrar_saida($dados, $saida, $_SESSION['usuario_nome'], $clienteId);
            if ($resultado['erro']) {
                $erros[] = $resultado['erro'];
            } else {
                $descPagamento = $formasPermitidas[$formaPagamento];
                if ($formaPagamento === 'credito') {
                    $descPagamento .= ' em ' . $parcelas . 'x de R$ ' . number_format($totalVenda / $parcelas, 2, ',', '.');
                }

                $pedidoId = ($dados['proximo_pedido'] ?? 1);
                $dados['proximo_pedido'] = $pedidoId + 1;
                $dados['pedidos'] ??= [];
                $dados['pedidos'][] = [
                    'id' => $pedidoId,
                    'usuario_id' => $clienteId,
                    'itens' => [[
                        'produto_id' => $lotesPorId[$loteId]['produto_id'],
                        'nome' => $lotesPorId[$loteId]['nome'],
                        'quantidade' => $quantidade,
                        'preco' => $precoUnitario,
                        'subtotal' => $totalVenda
                    ]],
                    'total' => $totalVenda,
                    'pagamento' => $descPagamento . ' (Balcão)',
                    'endereco' => ['tipo' => 'Venda presencial'],
                    'status' => 'Concluído (Balcão)',
                    'criado_em' => date('Y-m-d H:i:s')
                ];

                flash('Venda de ' . $quantidade . 'x ' . $lotesPorId[$loteId]['nome'] . ' finalizada com sucesso via ' . $descPagamento . '! Total: R$ ' . number_format($totalVenda, 2, ',', '.'));
                redirecionar('caixa.php?aba=venda');
            }
        }
    }
}

$vendaPendente = $_SESSION['venda_caixa_pendente'] ?? null;
$clientes = array_values(array_filter($dados['usuarios'], fn($u) => ($u['perfil'] ?? '') === 'cliente'));
usort($clientes, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));
$pagina = 'caixa'; $titulo = 'Vendas no balcão';
include __DIR__ . '/includes/header.php';
?>
<h1 class="titulo">Vendas no balcão</h1><p class="subtitulo">Cadastre, consulte clientes e registre vendas presenciais.</p>

<div class="abas caixa-abas" role="tablist">
  <button type="button" class="aba <?= $aba === 'venda' ? 'ativa' : '' ?>" data-caixa-aba="venda">Efetuar venda</button>
  <button type="button" class="aba <?= $aba === 'consultar' ? 'ativa' : '' ?>" data-caixa-aba="consultar">Consultar clientes</button>
  <button type="button" class="aba <?= $aba === 'pf' ? 'ativa' : '' ?>" data-caixa-aba="pf">Cadastrar PF</button>
  <button type="button" class="aba <?= $aba === 'pj' ? 'ativa' : '' ?>" data-caixa-aba="pj">Cadastrar PJ</button>
</div>
<?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>

<section class="caixa-painel" data-caixa-painel="venda" <?= $aba === 'venda' ? '' : 'hidden' ?>>
  <form method="post" id="form-caixa-venda" class="painel form estreito">
    <input type="hidden" name="acao" value="venda_direta">
    <h2>Nova venda presencial</h2>

    <label for="cliente_id">Cliente *</label>
    <select id="cliente_id" name="cliente_id" required>
      <option value="">Selecione um cliente…</option>
      <?php foreach ($clientes as $cliente): ?>
        <option value="<?= $cliente['id'] ?>"><?= e($cliente['nome']) ?> — <?= ($cliente['tipo_pessoa'] ?? '') === 'pj' ? 'PJ' : 'PF' ?> · <?= e($cliente['cpf_cnpj'] ?? '') ?></option>
      <?php endforeach; ?>
    </select>

    <label for="lote_id">Produto / lote *</label>
    <select id="lote_id" name="lote_id" required>
      <option value="" data-preco="0">Selecione o produto…</option>
      <?php foreach ($lotes as $l): ?>
        <option value="<?= $l['id'] ?>" data-max="<?= $l['quantidade'] ?>" data-preco="<?= $l['preco'] ?? 9.90 ?>" data-vencido="<?= $l['dias'] < 0 ? 1 : 0 ?>">
          <?= e($l['nome']) ?> — R$ <?= number_format($l['preco'] ?? 9.90, 2, ',', '.') ?> (saldo: <?= $l['quantidade'] ?>)<?= $l['dias'] < 0 ? ' — VENCIDO' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <p class="dica" id="dica-saldo" hidden></p>

    <div class="grade-2">
      <div>
        <label for="quantidade">Quantidade *</label>
        <input type="number" id="quantidade" name="quantidade" min="1" value="1" required>
      </div>
      <div>
        <label>Total estimado</label>
        <div id="caixa-total-exibicao" style="font-size: 1.4rem; font-weight: 700; color: var(--verde); padding-top: 10px;">
          R$ 0,00
        </div>
      </div>
    </div>

    <label style="margin-top: 20px;">Forma de pagamento *</label>
    <div class="opcoes-pagamento">
      <label><input type="radio" name="forma_pagamento" value="dinheiro" checked> 💵 Dinheiro</label>
      <label><input type="radio" name="forma_pagamento" value="pix"> ⚡ Pix</label>
      <label><input type="radio" name="forma_pagamento" value="debito"> 💳 Débito</label>
      <label><input type="radio" name="forma_pagamento" value="credito"> 💳 Crédito</label>
    </div>

    <!-- Seção Dinheiro / Troco -->
    <div id="caixa-troco" style="margin-top: 14px;">
      <label for="valor_recebido">Valor entregue em dinheiro (R$)</label>
      <input type="number" step="0.01" id="valor_recebido" placeholder="0,00">
      <p class="dica" id="dica-troco">Troco a devolver: <b>R$ 0,00</b></p>
    </div>

    <!-- Seção Pix com QR Code Fictício -->
    <div id="preview-pix" class="preview-pagamento" hidden>
      <canvas id="qr-pix" width="180" height="180" role="img" aria-label="QR Code Pix"></canvas>
      <div>
        <b>Chave Pix rápida da loja</b>
        <code id="codigo-pix"></code>
        <small>Aponte a câmera ou maquininha do cliente para ler o Pix fictício.</small>
      </div>
    </div>

    <!-- Seção Cartão e Parcelas -->
    <div id="caixa-cartao" style="margin-top: 14px;" hidden>
      <label for="parcelas">Número de parcelas *</label>
      <select id="parcelas" name="parcelas">
        <?php for ($i = 1; $i <= 12; $i++): ?>
          <option value="<?= $i ?>" data-vezes="<?= $i ?>"><?= $i ?>x <?= $i === 1 ? '(à vista sem juros)' : 'sem juros' ?></option>
        <?php endfor; ?>
      </select>
      <p class="dica" id="dica-parcela" style="margin-top: 6px;"></p>
    </div>

    <p class="dica" style="margin-top: 20px;">Operador do caixa: <?= e($_SESSION['usuario_nome']) ?></p>
    <button class="btn btn-primario btn-bloco" id="btn-finalizar-caixa">✓ Finalizar venda no balcão</button>
  </form>
</section>

<section class="caixa-painel" data-caixa-painel="consultar" <?= $aba === 'consultar' ? '' : 'hidden' ?>>
  <div class="painel">
    <div class="painel-topo">
      <h2>Clientes cadastrados</h2>
      <span class="contador-pedidos"><?= count($clientes) ?></span>
    </div>
    <input type="search" id="busca-cliente-caixa" placeholder="Buscar por nome, CPF, CNPJ ou telefone…">
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Documento</th>
            <th>Telefone</th>
            <th>E-mail</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clientes as $cliente): ?>
            <tr data-cliente-busca="<?= e(strtolower($cliente['nome'] . ' ' . ($cliente['cpf_cnpj'] ?? '') . ' ' . ($cliente['telefone'] ?? ''))) ?>">
              <td><b><?= e($cliente['nome']) ?></b><?php if (($cliente['tipo_pessoa'] ?? '') === 'pj'): ?><small><?= e($cliente['razao_social'] ?? '') ?></small><?php endif; ?></td>
              <td><?= strtoupper(e($cliente['tipo_pessoa'] ?? '')) ?></td>
              <td><?= e($cliente['cpf_cnpj'] ?? '') ?></td>
              <td><?= e($cliente['telefone'] ?? '') ?></td>
              <td><?= e($cliente['email'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="vazio" id="sem-cliente-caixa" hidden>Nenhum cliente encontrado.</p>
  </div>
</section>

<section class="caixa-painel" data-caixa-painel="pf" <?= $aba === 'pf' ? '' : 'hidden' ?>>
  <form method="post" id="form-caixa-pf" class="painel form estreito">
    <input type="hidden" name="acao" value="pf">
    <h2>Cadastrar pessoa física</h2>
    <label for="nome_pf">Nome *</label>
    <input id="nome_pf" name="nome" required>
    <label for="cpf_pf">CPF *</label>
    <input id="cpf_pf" name="cpf_cnpj" inputmode="numeric" placeholder="000.000.000-00" required>
    <div class="grade-2">
      <div>
        <label for="nascimento_pf">Data de nascimento</label>
        <input type="date" id="nascimento_pf" name="data_nascimento">
      </div>
      <div>
        <label for="telefone_pf">Telefone *</label>
        <input id="telefone_pf" name="telefone" inputmode="tel" required>
      </div>
    </div>
    <label for="email_pf">E-mail (opcional)</label>
    <input type="email" id="email_pf" name="email">
    <button class="btn btn-primario btn-bloco">Cadastrar cliente PF</button>
  </form>
</section>

<section class="caixa-painel" data-caixa-painel="pj" <?= $aba === 'pj' ? '' : 'hidden' ?>>
  <form method="post" id="form-caixa-pj" class="painel form estreito">
    <input type="hidden" name="acao" value="pj">
    <h2>Cadastrar pessoa jurídica</h2>
    <label for="nome_pj">Nome *</label>
    <input id="nome_pj" name="nome" required>
    <label for="cnpj_pj">CNPJ *</label>
    <input id="cnpj_pj" name="cpf_cnpj" inputmode="numeric" placeholder="00.000.000/0000-00" required>
    <label for="razao_pj">Razão social *</label>
    <input id="razao_pj" name="razao_social" required>
    <label for="nome_empresa_pj">Nome fantasia</label>
    <input id="nome_empresa_pj" name="nome_empresa">
    <div class="grade-2">
      <div>
        <label for="responsavel_pj">Responsável *</label>
        <input id="responsavel_pj" name="responsavel" required>
      </div>
      <div>
        <label for="telefone_pj">Telefone *</label>
        <input id="telefone_pj" name="telefone" inputmode="tel" required>
      </div>
    </div>
    <label for="email_pj">E-mail (opcional)</label>
    <input type="email" id="email_pj" name="email">
    <button class="btn btn-primario btn-bloco">Cadastrar cliente PJ</button>
  </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
