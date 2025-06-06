<?php
/*
 * PagBank
 * 
 * Módulo Oficial para Integração com o PagBank via API v.4
 * Checkout Transparente para PrestaShop 1.6.x, 1.7.x e 8.x
 * Pagamento com Cartão de Crédito, Google Pay, Pix, Boleto e Pagar com PagBank
 * 
 * @author
 * 2011-2025 PrestaBR - https://prestabr.com.br
 * 
 * @copyright
 * 1996-2025 PagBank - https://pagbank.com.br
 * 
 * @license
 * Open Software License 3.0 (OSL 3.0) - https://opensource.org/license/osl-3-0-php/
 *
 */

if (!defined('_PS_VERSION_')) {
	exit;
}

class PagBankV6 extends Module
{
	public $module;

	/*
	 * Cria abas no menu e tabelas no banco
	 * Registra os Hooks da aplicação e define parâmetros de configuração
	 */
	public function installation()
	{
		$this->module = Module::getInstanceByName('pagbank');
		if (
			!$this->addStatus()
			|| !$this->installTabs()
			|| !$this->module->registerHook('displayBackOfficeHeader')
			|| !$this->module->registerHook('displayAdminOrder')
			|| !$this->module->registerHook('displayHeader')
			|| !$this->module->registerHook('displayPayment')
			|| !$this->module->registerHook('displayPaymentTop')
			|| !$this->module->registerHook('displayPaymentReturn')
		) {
			return false;
		}
		return true;
	}

	public function addStatus()
	{
		$os = array(
			'0' => $this->l('PagBank - Iniciado'),
			'1' => $this->l('PagBank - Em Análise'),
			'2' => $this->l('PagBank - Aguardando Pagamento'),
			'3' => $this->l('PagBank - Pagamento Autorizado')
		);

		foreach ($os as $k => $value) {
			$order_state = new OrderState();
			$order_state->name = array();
			$order_state->module_name = $this->module->name;
			$order_state->template = array();
			foreach (Language::getLanguages() as $key => $language) {
				$order_state->name[$language['id_lang']] = (string)$value;
			}
			$order_state->send_email = false;
			$order_state->invoice = false;
			$order_state->color = '#6495ED';
			$order_state->unremovable = true;
			$order_state->logable = false;
			$order_state->delivery = false;
			$order_state->hidden = false;
			$current_dir = dirname(__FILE__);
			if ($order_state->add()) {
				@copy(_PS_MODULE_DIR_ . '/' . $this->module->name . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif');
			}
			Configuration::updateValue('_PS_OS_PAGBANK_' . $k, $order_state->id);
		}
		return true;
	}

	/* 
	 * Adiciona Abas no menu do BackOffice
	 */
	public function installTabs()
	{
		$this->module = Module::getInstanceByName('pagbank');
		$menuItems = array(
			0 => array(
				'parent_class' => '',
				'class_name' => 'PagBank',
				'name' => 'PagBank',
			),
			1 => array(
				'parent_class' => 'PagBank',
				'class_name' => 'AdminPagBankRedirect',
				'name' => 'PagBank - Configurações',
			),
			2 => array(
				'parent_class' => 'PagBank',
				'class_name' => 'AdminPagBank',
				'name' => 'PagBank - Transações',
			),
			3 => array(
				'parent_class' => 'PagBank',
				'class_name' => 'AdminPagBankLogs',
				'name' => 'PagBank - Logs',
			),
		);

		foreach ($menuItems as $newMenu) {
			$tab = new Tab();
			$tab->module = $this->module->name;
			if (empty($newMenu['parent_class'])) {
				$tab->id_parent = 0;
			} else {
				$tab->id_parent = (int)Tab::getIdFromClassName($newMenu['parent_class']);
			}
			$tab->class_name = $newMenu['class_name'];

			foreach (Language::getLanguages(false) as $lang) {
				$tab->name[(int)$lang['id_lang']] = $newMenu['name'];
			}

			if (!$tab->add()) {
				return false;
			}
		}
		return true;
	}

	/* 
	 * Remove Abas no menu do BackOffice
	 */
	public function uninstallTabs()
	{
		$tabs = array(
			(int)Tab::getIdFromClassName('PagBank'),
			(int)Tab::getIdFromClassName('AdminPagBankRedirect'),
			(int)Tab::getIdFromClassName('AdminPagBank'),
			(int)Tab::getIdFromClassName('AdminPagBankLogs')
		);
		foreach ($tabs as $id_tab) {
			if ($id_tab) {
				$tab = new Tab($id_tab);
				if (Validate::isLoadedObject($tab)) {
					$result = $tab->delete();
				} else {
					return false;
				}
			}
		}
		return true;
	}

	/* 
	 * Gera os campos de configuração do módulo
	 */
	public function getConfigForm()
	{
		$this->module = Module::getInstanceByName('pagbank');
		$statuses = OrderState::getOrderStates($this->context->language->id);

		$array_installments = array();
		for ($x = 1; $x <= 12; $x++) {
			$array_installments[] = array(
				'id' => $x,
				'name' => $x . 'x',
			);
		}

		if ((int)Configuration::get('PAGBANK_ENVIRONMENT') == 1) {
			$tax = Configuration::get('PAGBANK_TOKEN_TAX');
			$d14 = Configuration::get('PAGBANK_TOKEN_D14');
			$d30 = Configuration::get('PAGBANK_TOKEN_D30');
		} else {
			$tax = Configuration::get('PAGBANK_TOKEN_SANDBOX_TAX');
			$d14 = Configuration::get('PAGBANK_TOKEN_SANDBOX_D14');
			$d30 = Configuration::get('PAGBANK_TOKEN_SANDBOX_D30');
		}

		$array_credentials = array(
			array(
				'id' => '',
				'name' => $this->l('Selectione o App')
			),
		);
		if (isset($tax) && $tax != '') {
			$array_credentials[] = array(
				'id' => 'TAX',
				'name' => $this->l('PrestaShop - App Tax')
			);
		}
		if (isset($d14) && $d14 != '') {
			$array_credentials[] =  array(
				'id' => 'D14',
				'name' => $this->l('PrestaShop - App D14')
			);
		}

		if (isset($d30) && $d30 != '') {
			$array_credentials[] =  array(
				'id' => 'D30',
				'name' => $this->l('PrestaShop - App D30')
			);
		}
		if (
			(!isset($d14) || $d14 == '') &&
			(!isset($d30) || $d30 == '') &&
			(!isset($tax) || $tax == '')
		) {
			$array_credentials = array(
				array(
					'id' => '',
					'name' => $this->l('Cadastre-se em algum dos Apps')
				),
			);
		}

		$user_credential = Configuration::get('PAGBANK_CREDENTIAL');
		$text_credential = '';
		if ($user_credential) {
			$text_credential .= '<br /><div class="alert alert-info"><p>' . $this->l('Você está utilizando a credencial ') . ' <b>PrestaShop - App ' . $user_credential . '.</b></p></div>';
		} else {
			$text_credential .= '<br /><div class="alert alert-info"><p>' . $this->l('Se você trocou de ambiente, por favor, confira a credencial e salve novamente.') . '</div>';
		}

		$text_sandbox = '';
		if ((int)Configuration::get('PAGBANK_ENVIRONMENT') == 0) {
			$text_sandbox .= '<br /><div class="alert alert-danger"><p>' . $this->l('Atenção, você está em ambiente SandBox') . '</p></div>';
		}
		$text_sandbox_google = '';
		if ((int)Configuration::get('PAGBANK_GOOGLE_ENVIRONMENT') == 0) {
			$text_sandbox_google .= '<br /><div class="alert alert-danger"><p>' . $this->l('Atenção, você está em ambiente SandBox') . '</p></div>';
		}

		$link_order_preferences = 'index.php?controller=AdminOrderPreferences&token=' . Tools::getAdminTokenLite('AdminOrderPreferences');
		$text_min_installments = '<br />Em complemento, se preferir, você pode ativar a opção para restringir o valor mínimo de pedido aceito pela loja. Tab Preferências > Pedidos ou <a href="' . $link_order_preferences . '">clicando aqui</a>.';
		$text_capture = '<br />Na captura manual (Pré-autorização) o pagamento só será debitado do cartão de crédito após ação manual no histórico do pedido. <br /> Veja mais informações na documentação <a href="https://github.com/pagseguro/pagseguro-modulo-prestashop?tab=readme-ov-file#5---configura%C3%A7%C3%B5es-de-pagamento-via-cart%C3%A3o-de-cr%C3%A9dito" target="_blank">clicando aqui</a>.';
		$text_doc_google = '<br />Veja mais informações na documentação <a href="https://github.com/pagseguro/pagseguro-modulo-prestashop?tab=readme-ov-file#4---pagamento-via-cart%C3%A3o-de-cr%C3%A9dito-com-google-pay" target="_blank">clicando aqui</a>.';

		$prefix_discount_value = null;
		$sufix_discount_value = null;
		if ((int)Configuration::get('PAGBANK_DISCOUNT_TYPE') == 1) {
			$sufix_discount_value = '%';
		} elseif ((int)Configuration::get('PAGBANK_DISCOUNT_TYPE') == 2) {
			$prefix_discount_value = 'R$';
		}

		$fields_form_0 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configurações do App'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Ambiente de Produção?'),
						'name' => 'PAGBANK_ENVIRONMENT',
						'is_bool' => true,
						'desc' => $this->l('Você pode utilizar o Ambiente de Testes (Sandbox) e testar tudo antes de colocar em Produção.') . $text_sandbox,
						'values' => array(
							array(
								'id' => 'PAGBANK_MODO_on',
								'value' => 1,
								'label' => $this->l('Produção'),
							),
							array(
								'id' => 'PAGBANK_MODO_off',
								'value' => 0,
								'label' => $this->l('Sandbox'),
							),
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Tipo de Credencial'),
						'name' => 'PAGBANK_CREDENTIAL',
						'desc' => $this->l('Defina o tipo de credential que a sua loja irá utilizar para processar os pagamentos.') . $text_credential,
						'class' => 'credentials',
						'options' => array(
							'query' => $array_credentials,
							'id' => 'id',
							'name' => 'name',
						),
					),
				),
			),
		);

		$fields_form_1 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Pagamento via Cartão de Crédito'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Cartão de Crédito'),
						'name' => 'PAGBANK_CREDIT_CARD',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'PAGBANK_CREDIT_CARD_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_CREDIT_CARD_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'switch',
						'class' => 'fixed-width-xs fixed-width-sm',
						'label' => $this->l('Compra com 1 Click'),
						'name' => 'PAGBANK_SAVE_CREDIT_CARD',
						'is_bool' => true,
						'desc' => $this->l('O cliente poderá salvar o Cartão de Crédito para futuras compras. 
							O Cartão é criptografado e armazenado pelo PagBank através do processo de Tokenização.
							Esta opção não é válida para o GooglePay.'),
						'values' => array(
							array(
								'id' => 'PAGBANK_SAVE_CREDIT_CARD_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_SAVE_CREDIT_CARD_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
				),
			),
		);

		$fields_form_2 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Pagamento via Cartão de Crédito com Google Pay'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Google Pay'),
						'name' => 'PAGBANK_GOOGLE_PAY',
						'is_bool' => true,
						'desc' => $text_doc_google,
						'values' => array(
							array(
								'id' => 'PAGBANK_GOOGLE_PAY_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_GOOGLE_PAY_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Ambiente de Produção?'),
						'name' => 'PAGBANK_GOOGLE_ENVIRONMENT',
						'is_bool' => true,
						'desc' => $this->l('Você deve utilizar o Ambiente de Testes (Sandbox) para solicitar a aprovação da integração na sua conta junto ao Google.') . $text_sandbox_google,
						'values' => array(
							array(
								'id' => 'PAGBANK_GOOGLE_ENVIRONMENT_on',
								'value' => 1,
								'label' => $this->l('Produção'),
							),
							array(
								'id' => 'PAGBANK_GOOGLE_ENVIRONMENT_off',
								'value' => 0,
								'label' => $this->l('Sandbox'),
							),
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Pedido Demo?'),
						'name' => 'PAGBANK_GOOGLE_ORDER_DEMO',
						'is_bool' => true,
						'desc' => $this->l('Permite criar pedido de demonstração, para solicitar a aprovação da integração na sua conta junto ao Google, enquanto a sua loja está trabalhando em modo de Produção com outras formas de pagamento.'),
						'values' => array(
							array(
								'id' => 'PAGBANK_GOOGLE_ORDER_DEMO_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_GOOGLE_ORDER_DEMO_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Google Merchant ID'),
						'name' => 'PAGBANK_GOOGLE_MERCHANT_ID',
					),
				),
			),
		);

		$fields_form_3 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configurações de Pagamento via Cartão de Crédito'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'select',
						'label' => $this->l('Quantidade máxima de parcelas'),
						'class' => 'fixed-width-xs',
						'name' => 'PAGBANK_MAX_INSTALLMENTS',
						'desc' => $this->l('Defina a quantidade máxima de parcelas para seus clientes.'),
						'options' => array(
							'query' => $array_installments,
							'id' => 'id',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Quantidade de parcelas sem juros'),
						'class' => 'fixed-width-xs',
						'name' => 'PAGBANK_NO_INTEREST',
						'desc' => $this->l('Defina a quantidade de parcelas sem juros para seus clientes.'),
						'options' => array(
							'query' => $array_installments,
							'id' => 'id',
							'name' => 'name',
						),
					),
					array(
						'type' => 'text',
						'class' => 'fixed-width-xs fixed-width-sm',
						'label' => $this->l('Valor da parcela mínima'),
						'name' => 'PAGBANK_MINIMUM_INSTALLMENTS',
						'desc' => $this->l('Defina o valor da parcela mínima, exemplo: 5.00 ou 15.00. Deixe como 0 (zero) para desativar este recurso. O valor mínimo da parcela no Cartão de Crédito é de R$ 1.00.'),
					),
					array(
						'type' => 'radio',
						'label' => $this->l('Comportamento da parcela mínima'),
						'name' => 'PAGBANK_INSTALLMENTS_TYPE',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'opcao_1',
								'value' => 0,
								'label' => $this->l('Não processar nada abaixo do valor mínimo estipulado.') . $text_min_installments
							),
							array(
								'id' => 'opcao_2',
								'value' => 1,
								'label' => $this->l('Oferecer pagamento a vista, em 1x parcela, para valores abaixo do mínimo estipulado.')
							),
						),
					),
					array(
						'type' => 'select',
						'class' => 'fixed-width-xs fixed-width-sm',
						'label' => $this->l('Tipo de Captura'),
						'name' => 'PAGBANK_CAPTURE_METHOD',
						'desc' => $this->l('Na captura automática o pagamento é debitado imeditamente do cartão de crédito.') . $text_capture,
						'options' => array(
							'query' => array(
								array(
									'id' => '1',
									'name' => $this->l('Automática'),
								),
								array(
									'id' => '0',
									'name' => $this->l('Manual'),
								),
							),
							'id' => 'id',
							'name' => 'name',
						),
					),
				),
			),
		);

		$fields_form_4 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Pagamento via PIX'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('PIX'),
						'name' => 'PAGBANK_PIX',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'PAGBANK_PIX_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_PIX_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
						'desc' => $this->l('Atenção: Não esqueça de cadastrar uma chave PIX no super app PagBank'),
					),
					array(
						'type' => 'text',
						'class' => 'fixed-width-xs fixed-width-sm',
						'label' => $this->l('Prazo limite de pagamento via PIX'),
						'name' => 'PAGBANK_PIX_TIME_LIMIT',
						'suffix' => $this->l('minutos'),
						'desc' => $this->l('Defina o tempo máximo, em minutos, que o usuário terá para realizar o pagamento via PIX.'),
					),
				),
			),
		);

		$fields_form_5 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Pagamento via Boleto Bancário'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Boleto Bancário'),
						'name' => 'PAGBANK_BANKSLIP',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'PAGBANK_BANKSLIP_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_BANKSLIP_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),

					),
					array(
						'type' => 'text',
						'class' => 'fixed-width-xs fixed-width-sm',
						'label' => $this->l('Prazo de vencimento do boleto'),
						'name' => 'PAGBANK_BANKSLIP_DATE_LIMIT',
						'suffix' => $this->l('dias'),
						'desc' => $this->l('Defina o prazo máximo, em dias, que o usuário terá para realizar o pagamento por Boleto. O prazo mínimo é 2 dias.'),
					),
					array(
						'type' => 'text',
						'label' => $this->l('Texto descritivo para o boleto'),
						'name' => 'PAGBANK_BANKSLIP_TEXT',
						'desc' => $this->l('Máximo de 128 caracteres.'),
					),
				),
			),
		);

		$fields_form_6 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Pagamento via Pagar com PagBank (Wallet)'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Pagar com PagBank'),
						'name' => 'PAGBANK_WALLET',
						'is_bool' => true,
						'desc' => $this->l('Pagamento com saldo ou cartão de crédito cadastrado no super app PagBank'),
						'values' => array(
							array(
								'id' => 'PAGBANK_WALLET_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_WALLET_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
				),
			),
		);

		$fields_form_7 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Opções de Descontos'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'select',
						'label' => $this->l('Desconto no Pagamento?'),
						'name' => 'PAGBANK_DISCOUNT_TYPE',
						'desc' => $this->l('Defina o tipo de desconto que será aplicado.'),
						'options' => array(
							'query' => array(
								array(
									'id' => '0',
									'name' => $this->l('Nenhum Desconto'),
								),
								array(
									'id' => '1',
									'name' => $this->l('Percentual'),
								),
								array(
									'id' => '2',
									'name' => $this->l('Valor Fixo'),
								),
							),
							'id' => 'id',
							'name' => 'name',
						),
					),
					array(
						'type' => 'text',
						'class' => 'fixed-width-xs fixed-width-sm',
						'label' => $this->l('Valor do desconto'),
						'prefix' => $prefix_discount_value,
						'suffix' => $sufix_discount_value,
						'name' => 'PAGBANK_DISCOUNT_VALUE',
						'desc' => $this->l('Defina o valor do desconto, exemplo: 5.00 ou 15.00. Deixe como 0 (zero) para desativar este recurso.'),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Desconto no Cartão de Crédito (1x)'),
						'name' => 'PAGBANK_DISCOUNT_CREDIT',
						'is_bool' => true,
						'desc' => $this->l('Atenção: O valor mínimo da transação via Cartão de Crédito é de R$ 1.00.'),
						'values' => array(
							array(
								'id' => 'PAGBANK_DISCOUNT_CREDIT_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_DISCOUNT_CREDIT_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Desconto no Boleto Bancário'),
						'name' => 'PAGBANK_DISCOUNT_BANKSLIP',
						'is_bool' => true,
						'desc' => $this->l('Atenção: O valor mínimo da transação via Boleto Bancário é de R$ 1.00.'),
						'values' => array(
							array(
								'id' => 'PAGBANK_DISCOUNT_BANKSLIP_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_DISCOUNT_BANKSLIP_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Desconto no Pix'),
						'name' => 'PAGBANK_DISCOUNT_PIX',
						'is_bool' => true,
						'desc' => $this->l('Atenção: O valor mínimo da transação via Pix é de R$ 1.00.'),
						'values' => array(
							array(
								'id' => 'PAGBANK_DISCOUNT_PIX_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_DISCOUNT_PIX_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Desconto no Google Pay (1x)'),
						'name' => 'PAGBANK_DISCOUNT_GOOGLE',
						'is_bool' => true,
						'desc' => $this->l('Atenção: O valor mínimo da transação via Pix é de R$ 1.00.'),
						'values' => array(
							array(
								'id' => 'PAGBANK_DISCOUNT_GOOGLE_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'PAGBANK_DISCOUNT_GOOGLE_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
				),
			),
		);

		$fields_form_8 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Mapeamento de Status'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'select',
						'label' => $this->l('Status de Pagamento Aceito'),
						'name' => 'PAGBANK_PAID',
						'desc' => $this->l('Defina o Status que sua loja utiliza como \"Pagamento Aceito\".'),
						'options' => array(
							'query' => $statuses,
							'id' => 'id_order_state',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Status de Pagamento Autorizado'),
						'name' => 'PAGBANK_AUTHORIZED',
						'desc' => $this->l('Defina o Status que sua loja utiliza como \"Pagamento Autorizado\".'),
						'options' => array(
							'query' => $statuses,
							'id' => 'id_order_state',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Status de Pedido Cancelado'),
						'name' => 'PAGBANK_CANCELED',
						'desc' => $this->l('Defina o Status que sua loja utiliza como \"Pedido Cancelado\".'),
						'options' => array(
							'query' => $statuses,
							'id' => 'id_order_state',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Status de Pedido Estornado'),
						'name' => 'PAGBANK_REFUNDED',
						'desc' => $this->l('Defina o Status que sua loja utiliza como \"Pedido Estornado\".'),
						'options' => array(
							'query' => $statuses,
							'id' => 'id_order_state',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Status de Pagamento Em Análise'),
						'name' => 'PAGBANK_IN_ANALYSIS',
						'desc' => $this->l('Defina o Status que sua loja utiliza como \"Pagamento Em Análise\".'),
						'options' => array(
							'query' => $statuses,
							'id' => 'id_order_state',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Status de Aguardando Pagamento'),
						'name' => 'PAGBANK_AWAITING_PAYMENT',
						'desc' => $this->l('Defina o Status que sua loja utiliza como \"Aguardando Pagamento\".'),
						'options' => array(
							'query' => $statuses,
							'id' => 'id_order_state',
							'name' => 'name',
						),
					),
				),
			),
		);

		$fields_form_9 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Debug & Logs'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'switch',
						'label' => $this->l('Exibir parâmetros no Console do navegador?'),
						'name' => 'PAGBANK_SHOW_CONSOLE',
						'is_bool' => true,
						'desc' => $this->l('Mostrar mensagens do JavaScript no console do navegador para fins de depuração no checkout.'),
						'values' => array(
							array(
								'id' => 'console_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'console_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Gerar LOGs completos?'),
						'name' => 'PAGBANK_FULL_LOG',
						'is_bool' => true,
						'desc' => $this->l('Logs completos registram tudo que é enviado e recebido pela loja.'),
						'values' => array(
							array(
								'id' => 'logs_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'logs_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Apagar tabelas do banco?'),
						'name' => 'PAGBANK_DELETE_DB',
						'is_bool' => true,
						'desc' => $this->l('Recomendamos deixar esta opção desabilitada. Ative apenas se tiver certeza de que não vai mais precisar das informações.'),
						'values' => array(
							array(
								'id' => 'deletebd_on',
								'value' => 1,
								'label' => $this->l('Sim'),
							),
							array(
								'id' => 'deletebd_off',
								'value' => 0,
								'label' => $this->l('Não'),
							),
						),
					),
				),
			),
		);

		$fields_form_10 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Salvar Configurações'),
					'icon' => 'icon-save',
				),
				'submit' => array(
					'title' => $this->l('Salvar'),
				),
			),
		);

		$helper = new HelperForm();
		$helper->name_controller = "pagbank-form";
		$helper->show_toolbar = false;
		$helper->table = $this->module->table;
		$helper->module = $this->module;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
		$helper->identifier = $this->module->identifier;
		$helper->submit_action = 'submitPagBankModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$fields_value = $this->module->getConfigFormValues();
		$helper->tpl_vars = array(
			'fields_value' => $fields_value,
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array(
					$fields_form_0,
					$fields_form_1,
					$fields_form_2,
					$fields_form_3,
					$fields_form_4,
					$fields_form_5,
					$fields_form_6,
					$fields_form_7,
					$fields_form_8,
					$fields_form_9,
					$fields_form_10
				));
	}
}
