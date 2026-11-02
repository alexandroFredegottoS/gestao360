// Comportamentos do lado do navegador, organizados por tela.
// Cada função só faz algo se os elementos da sua tela existirem na página.

/* ---------- Monitoramento: abas, busca e janela de detalhes ---------- */
function iniciarMonitoramento() {
  const linhas = document.querySelectorAll('tbody tr[data-status]');
  const campoBusca = document.getElementById('busca');
  const semResultado = document.getElementById('sem-resultado');
  if (!campoBusca || !semResultado) return;
  let statusSelecionado = 'todos';

  // Mostra só as linhas que combinam com a aba escolhida e com o texto buscado
  function filtrar() {
    const termo = campoBusca.value.trim().toLowerCase();
    let visiveis = 0;
    linhas.forEach(linha => {
      const combinaStatus = statusSelecionado === 'todos' || linha.dataset.status === statusSelecionado;
      const combinaBusca = linha.dataset.busca.includes(termo);
      linha.hidden = !(combinaStatus && combinaBusca);
      if (!linha.hidden) visiveis++;
    });
    semResultado.hidden = visiveis > 0;
  }

  document.querySelectorAll('.aba').forEach(aba => aba.addEventListener('click', () => {
    document.querySelectorAll('.aba').forEach(a => {
      a.classList.remove('ativa');
      a.setAttribute('aria-selected', 'false');
    });
    aba.classList.add('ativa');
    aba.setAttribute('aria-selected', 'true');
    statusSelecionado = aba.dataset.filtro;
    filtrar();
  }));
  campoBusca.addEventListener('input', filtrar);
  filtrar();

  // Janela de detalhes: copia os data-* do botão para o modal
  const modal = document.getElementById('modal-detalhes');
  const acaoSaida = document.getElementById('md-saida');
  document.querySelectorAll('.btn-detalhes').forEach(botao => botao.addEventListener('click', () => {
    const dados = botao.dataset;
    document.getElementById('md-produto').textContent = dados.produto;
    ['lote', 'qtd', 'validade', 'status', 'nf', 'fornecedor', 'emissao'].forEach(campo => {
      document.getElementById('md-' + campo).textContent = dados[campo];
    });
    acaoSaida.href = 'saida.php?lote=' + encodeURIComponent(dados.loteId);
    acaoSaida.textContent = dados.vencido === '1' ? 'Dar baixa do vencido' : 'Registrar saída';
    modal.showModal();
  }));
  document.getElementById('md-fechar').addEventListener('click', () => modal.close());
  modal.addEventListener('click', ev => { if (ev.target === modal) modal.close(); }); // clique fora fecha
}

/* ---------- Nova Entrada: novo produto e número da NF ---------- */
function iniciarEntrada() {
  const selectProduto = document.getElementById('produto_id');
  const blocoNovo = document.getElementById('bloco-novo');
  if (selectProduto && blocoNovo) {
    const atualizar = () => { blocoNovo.hidden = selectProduto.value !== 'novo'; };
    selectProduto.addEventListener('change', atualizar);
    atualizar();
  }

  const numeroNf = document.getElementById('numero');
  if (numeroNf) numeroNf.addEventListener('input', () => { numeroNf.value = numeroNf.value.toUpperCase(); });
}

/* ---------- Cadastro: alterna campos de pessoa física e jurídica ---------- */
function iniciarCadastro() {
  const tipo = document.getElementById('tipo_pessoa');
  const pf = document.getElementById('campos-pf');
  const pj = document.getElementById('campos-pj');
  const documento = document.getElementById('cpf_cnpj');
  const rotulo = document.getElementById('rotulo-documento');
  if (!tipo || !pf || !pj || !documento || !rotulo) return;
  function atualizar() {
    const ehPj = tipo.value === 'pj';
    pf.hidden = ehPj; pj.hidden = !ehPj;
    pf.querySelectorAll('input').forEach(campo => campo.required = !ehPj);
    pj.querySelectorAll('input').forEach(campo => campo.required = ehPj && campo.id !== 'nome_fantasia');
    rotulo.textContent = ehPj ? 'CNPJ *' : 'CPF *';
    documento.placeholder = ehPj ? '00.000.000/0000-00' : '000.000.000-00';
  }
  tipo.addEventListener('change', atualizar); atualizar();
}

/* ---------- Saída: saldo disponível e limite de quantidade ---------- */
function iniciarSaida() {
  const selectLote = document.getElementById('lote_id');
  const campoQtd = document.getElementById('quantidade');
  const dica = document.getElementById('dica-saldo');
  const campoMotivo = document.getElementById('motivo');
  if (!selectLote || !campoQtd || !dica || !campoMotivo) return;

  function atualizarSaldo() {
    const opcao = selectLote.selectedOptions[0];
    const saldo = parseInt(opcao.dataset.max || '0', 10);
    const vencido = opcao.dataset.vencido === '1';
    [...campoMotivo.options].forEach(opcaoMotivo => {
      const permitido = !vencido || ['Perda / Avaria', 'Devolução ao Fornecedor'].includes(opcaoMotivo.value);
      opcaoMotivo.disabled = !permitido;
    });
    if (vencido && campoMotivo.selectedOptions[0]?.disabled) campoMotivo.value = 'Perda / Avaria';
    if (saldo > 0) {
      campoQtd.max = saldo;
      dica.hidden = false;
      dica.textContent = 'Saldo disponível: ' + saldo +
        (vencido ? ' — lote vencido, só é possível baixar por perda ou devolução.' : '.');
    } else {
      campoQtd.removeAttribute('max');
      dica.hidden = true;
    }
  }
  selectLote.addEventListener('change', atualizarSaldo);
  atualizarSaldo();

  document.getElementById('form-saida').addEventListener('submit', ev => {
    const saldo = parseInt(campoQtd.max || '0', 10);
    if (saldo && parseInt(campoQtd.value, 10) > saldo) {
      ev.preventDefault();
      alert('A quantidade não pode ser maior que o saldo (' + saldo + ').');
    }
  });
}

/* ---------- Usuários: confirmar senha e exclusão ---------- */
function iniciarUsuarios() {
  const form = document.getElementById('form-usuario');
  if (form) {
    form.querySelectorAll('input[type="email"], input[type="password"]').forEach(campo => { campo.value = ''; });
    window.addEventListener('pageshow', () => form.querySelectorAll('input[type="email"], input[type="password"]').forEach(campo => { campo.value = ''; }));
    const perfil = document.getElementById('perfil');
    const camposCliente = document.getElementById('campos-cliente-admin');
    const tipo = document.getElementById('tipo_pessoa');
    const pf = document.getElementById('campos-pf');
    const pj = document.getElementById('campos-pj');
    const documento = document.getElementById('cpf_cnpj');
    const rotulo = document.getElementById('rotulo-documento');
    function atualizarCliente() {
      const cliente = perfil.value === 'cliente';
      camposCliente.hidden = !cliente;
      camposCliente.querySelectorAll('input, select').forEach(campo => campo.required = cliente);
      const ehPj = tipo.value === 'pj';
      pf.hidden = ehPj; pj.hidden = !ehPj;
      pf.querySelectorAll('input').forEach(campo => campo.required = cliente && !ehPj);
      pj.querySelectorAll('input').forEach(campo => campo.required = cliente && ehPj && campo.id !== 'nome_fantasia');
      rotulo.textContent = ehPj ? 'CNPJ *' : 'CPF *';
      documento.placeholder = ehPj ? '00.000.000/0000-00' : '000.000.000-00';
    }
    perfil.addEventListener('change', atualizarCliente); tipo.addEventListener('change', atualizarCliente); atualizarCliente();
    form.addEventListener('submit', ev => {
      const senha = document.getElementById('senha');
      const confirmar = document.getElementById('confirmar');
      if (!ev.defaultPrevented && senha.value !== confirmar.value) {
        ev.preventDefault();
        confirmar.style.borderColor = '#dc2626';
        confirmar.focus();
        alert('As senhas não são iguais.');
      }
    });
  }
  document.querySelectorAll('.form-excluir').forEach(f => f.addEventListener('submit', ev => {
    if (!confirm('Excluir este usuário?')) ev.preventDefault();
  }));
}

/* ---------- Checkout: formas de pagamento demonstrativas ---------- */
function iniciarCheckout() {
  const form = document.getElementById('form-checkout');
  const cartao = document.getElementById('dados-cartao');
  const aviso = document.getElementById('aviso-pagamento');
  const pix = document.getElementById('preview-pix');
  const boleto = document.getElementById('preview-boleto');
  if (!form || !cartao || !aviso || !pix || !boleto) return;

  const digitos = tamanho => Array.from({ length: tamanho }, () => Math.floor(Math.random() * 10)).join('');
  function desenharQr() {
    const tela = document.getElementById('qr-pix');
    const ctx = tela.getContext('2d'); const tamanho = 21; const bloco = tela.width / tamanho;
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, tela.width, tela.height);
    const matriz = Array.from({ length: tamanho }, () => Array.from({ length: tamanho }, () => Math.random() > .53));
    [[0, 0], [14, 0], [0, 14]].forEach(([x, y]) => {
      for (let linha = 0; linha < 7; linha++) for (let coluna = 0; coluna < 7; coluna++) {
        matriz[y + linha][x + coluna] = linha === 0 || linha === 6 || coluna === 0 || coluna === 6 || (linha >= 2 && linha <= 4 && coluna >= 2 && coluna <= 4);
      }
    });
    ctx.fillStyle = '#111827'; matriz.forEach((linha, y) => linha.forEach((preto, x) => { if (preto) ctx.fillRect(x * bloco, y * bloco, bloco, bloco); }));
    document.getElementById('codigo-pix').textContent = '0002012658BR.GOV.BCB.PIX01' + digitos(24) + '6304DEMO';
  }
  function gerarBoleto() {
    const codigo = digitos(47);
    document.getElementById('linha-boleto').textContent = codigo.replace(/(\d{5})(\d{5})(\d{5})(\d{6})(\d{5})(\d{6})(\d)(\d{14})/, '$1.$2 $3.$4 $5.$6 $7 $8');
    const barras = document.getElementById('codigo-barras');
    barras.replaceChildren(...Array.from({ length: 72 }, (_, i) => {
      const barra = document.createElement('i'); barra.style.width = (1 + Math.floor(Math.random() * 3)) + 'px'; barra.className = i % 7 === 0 ? 'larga' : ''; return barra;
    }));
  }

  function atualizarPagamento() {
    const selecionado = form.querySelector('input[name="pagamento"]:checked')?.value;
    const ehCartao = selecionado === 'cartao';
    cartao.hidden = !ehCartao;
    pix.hidden = selecionado !== 'pix';
    boleto.hidden = selecionado !== 'boleto';
    cartao.querySelectorAll('input').forEach(campo => campo.required = ehCartao);
    aviso.hidden = !ehCartao;
    aviso.textContent = 'Dados do cartão usados apenas nesta simulação.';
    if (selecionado === 'pix') desenharQr();
    if (selecionado === 'boleto') gerarBoleto();
  }
  form.querySelectorAll('input[name="pagamento"]').forEach(opcao => opcao.addEventListener('change', atualizarPagamento));
  atualizarPagamento();
}

/* ---------- Caixa: abas, busca e validação de vendas presenciais ---------- */
function iniciarCaixa() {
  const abas = document.querySelectorAll('[data-caixa-aba]');
  const paineis = document.querySelectorAll('[data-caixa-painel]');
  if (!abas.length || !paineis.length) return;

  abas.forEach(aba => {
    aba.addEventListener('click', () => {
      const alvo = aba.dataset.caixaAba;
      abas.forEach(a => a.classList.toggle('ativa', a === aba));
      paineis.forEach(painel => {
        painel.hidden = painel.dataset.caixaPainel !== alvo;
      });
      // Atualiza a URL sem recarregar a tela para manter histórico
      const url = new URL(window.location);
      url.searchParams.set('aba', alvo);
      window.history.replaceState({}, '', url);
    });
  });

  // Busca em tempo real na lista de clientes
  const campoBusca = document.getElementById('busca-cliente-caixa');
  const semCliente = document.getElementById('sem-cliente-caixa');
  const linhasClientes = document.querySelectorAll('tr[data-cliente-busca]');
  if (campoBusca && semCliente && linhasClientes.length) {
    campoBusca.addEventListener('input', () => {
      const termo = campoBusca.value.trim().toLowerCase();
      let visiveis = 0;
      linhasClientes.forEach(linha => {
        const texto = linha.dataset.clienteBusca || '';
        const combina = texto.includes(termo);
        linha.hidden = !combina;
        if (combina) visiveis++;
      });
      semCliente.hidden = visiveis > 0;
    });
  }

  // Validação de saldo, total e meios de pagamento na venda presencial
  const selectLote = document.getElementById('lote_id');
  const campoQtd = document.getElementById('quantidade');
  const dica = document.getElementById('dica-saldo');
  const formVenda = document.getElementById('form-caixa-venda');
  const totalExibicao = document.getElementById('caixa-total-exibicao');
  const blocoPix = document.getElementById('preview-pix');
  const blocoTroco = document.getElementById('caixa-troco');
  const blocoCartao = document.getElementById('caixa-cartao');
  const campoValorRecebido = document.getElementById('valor_recebido');
  const dicaTroco = document.getElementById('dica-troco');
  const selectParcelas = document.getElementById('parcelas');
  const dicaParcela = document.getElementById('dica-parcela');

  if (selectLote && campoQtd && dica && formVenda) {
    function obterTotal() {
      const preco = parseFloat(selectLote.selectedOptions[0]?.dataset?.preco || '0');
      const qtd = parseInt(campoQtd.value || '1', 10);
      return Math.max(0, preco * qtd);
    }

    function atualizarCalculos() {
      const total = obterTotal();
      if (totalExibicao) {
        totalExibicao.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
      }

      // Atualiza valores nas opções de parcelas
      if (selectParcelas) {
        [...selectParcelas.options].forEach(opt => {
          const vezes = parseInt(opt.dataset.vezes || '1', 10);
          const valorParc = total > 0 ? (total / vezes).toFixed(2).replace('.', ',') : '0,00';
          opt.textContent = vezes + 'x de R$ ' + valorParc + (vezes === 1 ? ' (à vista sem juros)' : ' sem juros');
        });
      }

      // Atualiza troco se aplicável
      if (campoValorRecebido && dicaTroco) {
        campoValorRecebido.min = total;
        const recebido = parseFloat(campoValorRecebido.value || '0');
        if (recebido >= total && total > 0) {
          const troco = recebido - total;
          dicaTroco.style.color = 'var(--verde)';
          dicaTroco.innerHTML = 'Troco a devolver: <b>R$ ' + troco.toFixed(2).replace('.', ',') + '</b>';
        } else if (campoValorRecebido.value) {
          dicaTroco.style.color = '#dc2626';
          dicaTroco.innerHTML = 'Valor menor que o total da venda.';
        } else {
          dicaTroco.style.color = '';
          dicaTroco.innerHTML = 'Troco a devolver: <b>R$ 0,00</b>';
        }
      }
    }

    function desenharQrCaixa() {
      const tela = document.getElementById('qr-pix');
      if (!tela) return;
      const ctx = tela.getContext('2d'); const tamanho = 21; const bloco = tela.width / tamanho;
      ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, tela.width, tela.height);
      const matriz = Array.from({ length: tamanho }, () => Array.from({ length: tamanho }, () => Math.random() > .53));
      [[0, 0], [14, 0], [0, 14]].forEach(([x, y]) => {
        for (let linha = 0; linha < 7; linha++) for (let coluna = 0; coluna < 7; coluna++) {
          matriz[y + linha][x + coluna] = linha === 0 || linha === 6 || coluna === 0 || coluna === 6 || (linha >= 2 && linha <= 4 && coluna >= 2 && coluna <= 4);
        }
      });
      ctx.fillStyle = '#111827'; matriz.forEach((linha, y) => linha.forEach((preto, x) => { if (preto) ctx.fillRect(x * bloco, y * bloco, bloco, bloco); }));
      const codigoPix = document.getElementById('codigo-pix');
      if (codigoPix) {
        const digitos = tamanho => Array.from({ length: tamanho }, () => Math.floor(Math.random() * 10)).join('');
        codigoPix.textContent = '0002012658BR.GOV.BCB.PIX01' + digitos(24) + '6304DEMO';
      }
    }

    function atualizarFormaPagamento() {
      const selecionado = formVenda.querySelector('input[name="forma_pagamento"]:checked')?.value;
      const ehDinheiro = selecionado === 'dinheiro';
      const ehPix = selecionado === 'pix';
      const ehCartao = selecionado === 'credito' || selecionado === 'debito';

      if (blocoTroco) blocoTroco.hidden = !ehDinheiro;
      if (blocoCartao) {
        blocoCartao.hidden = !ehCartao;
        if (selectParcelas) {
          if (selecionado === 'debito') {
            selectParcelas.value = '1';
            selectParcelas.disabled = true;
          } else {
            selectParcelas.disabled = false;
          }
        }
      }
      if (blocoPix) {
        blocoPix.hidden = !ehPix;
        if (ehPix) {
          setTimeout(desenharQrCaixa, 10);
        }
      }
      atualizarCalculos();
    }

    function atualizarSaldoVenda() {
      const opcao = selectLote.selectedOptions[0];
      const saldo = parseInt(opcao?.dataset?.max || '0', 10);
      const vencido = opcao?.dataset?.vencido === '1';

      if (vencido) {
        dica.hidden = false;
        dica.style.color = '#dc2626';
        dica.textContent = 'Atenção: Este lote está VENCIDO! A venda é bloqueada pelas regras de estoque.';
        campoQtd.max = 0;
      } else if (saldo > 0) {
        campoQtd.max = saldo;
        dica.hidden = false;
        dica.style.color = '';
        dica.textContent = 'Saldo disponível para venda: ' + saldo + '.';
      } else {
        campoQtd.removeAttribute('max');
        dica.hidden = true;
        dica.style.color = '';
      }
      atualizarCalculos();
    }

    selectLote.addEventListener('change', atualizarSaldoVenda);
    campoQtd.addEventListener('input', atualizarCalculos);
    if (campoValorRecebido) campoValorRecebido.addEventListener('input', atualizarCalculos);

    formVenda.querySelectorAll('input[name="forma_pagamento"]').forEach(radio => {
      radio.addEventListener('change', atualizarFormaPagamento);
    });

    atualizarSaldoVenda();
    atualizarFormaPagamento();

    formVenda.addEventListener('submit', ev => {
      const opcao = selectLote.selectedOptions[0];
      if (opcao?.dataset?.vencido === '1') {
        ev.preventDefault();
        alert('Este lote está vencido e não pode ser vendido.');
        return;
      }
      const saldo = parseInt(campoQtd.max || '0', 10);
      if (saldo && parseInt(campoQtd.value, 10) > saldo) {
        ev.preventDefault();
        alert('A quantidade não pode ser maior que o saldo (' + saldo + ').');
      }
    });
  }
}

/* ---------- Geral: campos obrigatórios e avisos ---------- */
function iniciarValidacaoObrigatorios() {
  document.querySelectorAll('#form-entrada, #form-saida, #form-usuario, #form-caixa-venda, #form-caixa-pf, #form-caixa-pj').forEach(form => {
    form.addEventListener('submit', ev => {
      let primeiroVazio = null;
      form.querySelectorAll('[required]').forEach(campo => {
        const escondido = campo.closest('[hidden]');
        const vazio = !escondido && campo.value.trim() === '';
        campo.style.borderColor = vazio ? '#dc2626' : '';
        if (vazio && !primeiroVazio) primeiroVazio = campo;
      });
      if (primeiroVazio) {
        ev.preventDefault();
        primeiroVazio.focus();
        alert('Preencha os campos obrigatórios (*).');
      }
    });
  });
}

function esconderAvisosDeSucesso() {
  document.querySelectorAll('.aviso-ok').forEach(aviso => setTimeout(() => { aviso.style.display = 'none'; }, 5000));
}

function limparCredenciaisPreenchidas() {
  if (!document.body.hasAttribute('data-limpar-credenciais')) return;
  const limpar = () => document.querySelectorAll('input[type="email"], input[type="password"]').forEach(campo => { campo.value = ''; });
  limpar();
  window.addEventListener('pageshow', limpar);
}

document.addEventListener('DOMContentLoaded', () => {
  iniciarValidacaoObrigatorios();   // registrado primeiro: roda antes das validações específicas
  iniciarMonitoramento();
  iniciarEntrada();
  iniciarCadastro();
  iniciarSaida();
  iniciarCaixa();
  iniciarUsuarios();
  iniciarCheckout();
  esconderAvisosDeSucesso();
  limparCredenciaisPreenchidas();
});

