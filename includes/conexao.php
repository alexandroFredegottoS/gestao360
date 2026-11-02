<?php
// Dados temporários do projeto. Tudo fica na sessão e some ao fechar o navegador.
// Não é necessário instalar ou configurar um banco de dados.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function iniciar_dados(): void {
    if (!isset($_SESSION['dados'])) {
        $hoje = new DateTimeImmutable('today');
        $_SESSION['dados'] = [
            'usuarios' => [
                ['id'=>1, 'nome'=>'Administrador', 'email'=>'admin@gestao360.com.br', 'senha'=>password_hash('password', PASSWORD_DEFAULT), 'perfil'=>'admin'],
                ['id'=>2, 'nome'=>'Operador Exemplo', 'email'=>'operador@gestao360.com.br', 'senha'=>password_hash('operador123', PASSWORD_DEFAULT), 'perfil'=>'operador'],
                ['id'=>3, 'nome'=>'Caixa Exemplo', 'email'=>'caixa@gestao360.com.br', 'senha'=>password_hash('caixa123', PASSWORD_DEFAULT), 'perfil'=>'caixa'],
                ['id'=>4, 'nome'=>'Cliente Exemplo', 'email'=>'cliente@gestao360.com.br', 'senha'=>password_hash('cliente123', PASSWORD_DEFAULT), 'perfil'=>'cliente',
                 'tipo_pessoa'=>'pf', 'cpf_cnpj'=>'12345678901', 'data_nascimento'=>'1995-06-15', 'razao_social'=>'', 'nome_fantasia'=>'', 'responsavel'=>'', 'telefone'=>'11999999999'],
            ],
            'produtos' => [
                1 => ['id' => 1, 'nome' => 'Sabonete neutro', 'categoria' => 'Higiene', 'preco'=>4.90],
                2 => ['id' => 2, 'nome' => 'Ração para cães 1 kg', 'categoria' => 'Pet shop', 'preco'=>29.90],
                3 => ['id' => 3, 'nome' => 'Café torrado 500 g', 'categoria' => 'Mercearia', 'preco'=>18.50],
                4 => ['id' => 4, 'nome' => 'Vitamina C 500 mg', 'categoria' => 'Farmácia', 'preco'=>24.90],
                5 => ['id' => 5, 'nome' => 'Shampoo para animais 500 ml', 'categoria' => 'Pet shop', 'preco'=>21.90],
                6 => ['id' => 6, 'nome' => 'Pilha alcalina', 'categoria' => 'Utilidades', 'preco'=>12.90],
                7 => ['id' => 7, 'nome' => 'Chocolate ao leite 90 g', 'categoria' => 'Mercearia', 'preco'=>7.50],
                8 => ['id' => 8, 'nome' => 'Álcool 70% 1 L', 'categoria' => 'Higiene', 'preco'=>10.90],
            ],
            'notas' => [
                1 => ['id' => 1, 'numero' => 'NF-00101', 'emissao' => $hoje->format('Y-m-d'), 'fornecedor' => 'Fornecedor Exemplo A'],
                2 => ['id' => 2, 'numero' => 'NF-00102', 'emissao' => $hoje->format('Y-m-d'), 'fornecedor' => 'Fornecedor Exemplo B'],
                3 => ['id' => 3, 'numero' => 'NF-00103', 'emissao' => $hoje->format('Y-m-d'), 'fornecedor' => 'Fornecedor Exemplo C'],
            ],
            'lotes' => [
                1 => ['id'=>1,'produto_id'=>1,'nota_id'=>1,'codigo'=>'LOT-001','quantidade'=>48,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('+90 days')->format('Y-m-d')],
                2 => ['id'=>2,'produto_id'=>2,'nota_id'=>2,'codigo'=>'LOT-002','quantidade'=>32,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('+25 days')->format('Y-m-d')],
                3 => ['id'=>3,'produto_id'=>3,'nota_id'=>3,'codigo'=>'LOT-003','quantidade'=>18,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('+10 days')->format('Y-m-d')],
                4 => ['id'=>4,'produto_id'=>4,'nota_id'=>3,'codigo'=>'LOT-004','quantidade'=>12,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('-3 days')->format('Y-m-d')],
                5 => ['id'=>5,'produto_id'=>5,'nota_id'=>3,'codigo'=>'LOT-005','quantidade'=>36,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('+180 days')->format('Y-m-d')],
                6 => ['id'=>6,'produto_id'=>6,'nota_id'=>3,'codigo'=>'LOT-006','quantidade'=>24,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('+7 days')->format('Y-m-d')],
                7 => ['id'=>7,'produto_id'=>7,'nota_id'=>2,'codigo'=>'LOT-007','quantidade'=>60,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('+120 days')->format('Y-m-d')],
                8 => ['id'=>8,'produto_id'=>8,'nota_id'=>3,'codigo'=>'LOT-008','quantidade'=>20,'unidade'=>'unidades','itens_embalagem'=>0,'validade'=>$hoje->modify('-5 days')->format('Y-m-d')],
            ],
            'movimentacoes' => [
                ['tipo'=>'entrada','lote_id'=>5,'quantidade'=>36,'responsavel'=>'Administrador','criado_em'=>date('Y-m-d H:i:s', time()-3600)],
                ['tipo'=>'entrada','lote_id'=>1,'quantidade'=>48,'responsavel'=>'Administrador','criado_em'=>date('Y-m-d H:i:s', time()-10800)],
                ['tipo'=>'entrada','lote_id'=>2,'quantidade'=>32,'responsavel'=>'Administrador','criado_em'=>date('Y-m-d H:i:s', time()-86400)],
            ],
            'proximo_produto' => 9,
            'proximo_lote' => 9,
            'pedidos' => [
                ['id'=>1, 'usuario_id'=>4, 'itens'=>[['produto_id'=>1, 'nome'=>'Sabonete neutro', 'quantidade'=>2, 'preco'=>4.90, 'subtotal'=>9.80]], 'total'=>9.80, 'pagamento'=>'Pix', 'endereco'=>[], 'status'=>'Pago', 'criado_em'=>date('Y-m-d H:i:s', time()-1800)],
                ['id'=>2, 'usuario_id'=>4, 'itens'=>[['produto_id'=>2, 'nome'=>'Ração para cães 1 kg', 'quantidade'=>1, 'preco'=>29.90, 'subtotal'=>29.90]], 'total'=>29.90, 'pagamento'=>'Boleto', 'endereco'=>[], 'status'=>'Pendente', 'criado_em'=>date('Y-m-d H:i:s', time()-7200)],
                ['id'=>3, 'usuario_id'=>4, 'itens'=>[['produto_id'=>7, 'nome'=>'Chocolate ao leite 90 g', 'quantidade'=>3, 'preco'=>7.50, 'subtotal'=>22.50]], 'total'=>22.50, 'pagamento'=>'Cartão de crédito', 'endereco'=>[], 'status'=>'Pagamento não efetuado', 'criado_em'=>date('Y-m-d H:i:s', time()-86400)],
            ],
            'proximo_pedido' => 4,
        ];
    }
}

iniciar_dados();

// Atualiza sessões criadas antes da inclusão da loja virtual com dados de demonstração.
$emailsExemplo = array_column($_SESSION['dados']['usuarios'], 'id', 'email');
if (!isset($emailsExemplo['operador@gestao360.com.br'])) {
    $operadorId = max(array_column($_SESSION['dados']['usuarios'], 'id')) + 1;
    $_SESSION['dados']['usuarios'][] = ['id'=>$operadorId, 'nome'=>'Operador Exemplo', 'email'=>'operador@gestao360.com.br', 'senha'=>password_hash('operador123', PASSWORD_DEFAULT), 'perfil'=>'operador'];
}
$emailsExemplo = array_column($_SESSION['dados']['usuarios'], 'id', 'email');
if (!isset($emailsExemplo['caixa@gestao360.com.br'])) {
    $caixaId = max(array_column($_SESSION['dados']['usuarios'], 'id')) + 1;
    $_SESSION['dados']['usuarios'][] = ['id'=>$caixaId, 'nome'=>'Caixa Exemplo', 'email'=>'caixa@gestao360.com.br', 'senha'=>password_hash('caixa123', PASSWORD_DEFAULT), 'perfil'=>'caixa'];
}
$emailsExemplo = array_column($_SESSION['dados']['usuarios'], 'id', 'email');
$clienteExemploId = $emailsExemplo['cliente@gestao360.com.br'] ?? null;
if ($clienteExemploId === null) {
    $clienteExemploId = max(array_column($_SESSION['dados']['usuarios'], 'id')) + 1;
    $_SESSION['dados']['usuarios'][] = ['id'=>$clienteExemploId, 'nome'=>'Cliente Exemplo', 'email'=>'cliente@gestao360.com.br',
        'senha'=>password_hash('cliente123', PASSWORD_DEFAULT), 'perfil'=>'cliente', 'tipo_pessoa'=>'pf', 'cpf_cnpj'=>'12345678901',
        'data_nascimento'=>'1995-06-15', 'razao_social'=>'', 'nome_fantasia'=>'', 'responsavel'=>'', 'telefone'=>'11999999999'];
}
if (empty($_SESSION['dados']['pedidos'])) {
    $_SESSION['dados']['pedidos'] = [
        ['id'=>1, 'usuario_id'=>$clienteExemploId, 'itens'=>[['produto_id'=>1, 'nome'=>'Sabonete neutro', 'quantidade'=>2, 'preco'=>4.90, 'subtotal'=>9.80]], 'total'=>9.80, 'pagamento'=>'Pix', 'endereco'=>[], 'status'=>'Pago', 'criado_em'=>date('Y-m-d H:i:s', time()-1800)],
        ['id'=>2, 'usuario_id'=>$clienteExemploId, 'itens'=>[['produto_id'=>2, 'nome'=>'Ração para cães 1 kg', 'quantidade'=>1, 'preco'=>29.90, 'subtotal'=>29.90]], 'total'=>29.90, 'pagamento'=>'Boleto', 'endereco'=>[], 'status'=>'Pendente', 'criado_em'=>date('Y-m-d H:i:s', time()-7200)],
        ['id'=>3, 'usuario_id'=>$clienteExemploId, 'itens'=>[['produto_id'=>7, 'nome'=>'Chocolate ao leite 90 g', 'quantidade'=>3, 'preco'=>7.50, 'subtotal'=>22.50]], 'total'=>22.50, 'pagamento'=>'Cartão de crédito', 'endereco'=>[], 'status'=>'Pagamento não efetuado', 'criado_em'=>date('Y-m-d H:i:s', time()-86400)],
    ];
    $_SESSION['dados']['proximo_pedido'] = 4;
}

// Os dados continuam disponíveis para todas as páginas durante a sessão.
$dados =& $_SESSION['dados'];
