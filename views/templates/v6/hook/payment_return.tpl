{*
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
 *}

<div id="pagbank-confirmation" class="container">
	<div class="content clearfix">
		<div class="row clearfix">
			<div class="col-xs-12 col-sm-8 col-lg-6 data_waiting" id="pay_links">
				{if ($payment_type == 'BOLETO')}
					<div class="panel panel-success">
						<div class="panel-heading heading-pix">
							{if $customer_name|strstr:' '}
							{$customer_name|strstr:' ':true}{else}{$customer_name}
								{l s=','}
							{/if}
							{l s='recebemos o seu pedido.' mod='pagbank'} <br />
							{l s='Para finalizar sua compra é só pagar o Boleto!' mod='pagbank'}
						</div>
						<div class="panel-body">
							<p align="center">
								<br />
								<a class="btn btn-lg btn-primary" href="{$pay_link}" id="btnBoleto"
									title="{l s='Imprimir o boleto' mod='pagbank'}" target="_blank">
									<i class="icon icon-barcode"></i>
									{l s='Clique para imprimir o boleto' mod='pagbank'}
								</a>
							</p>
							<p class="alert alert-warning text-center">
								{l s='Seu pedido só será processado após a confirmação do pagamento.' mod='pagbank'}
							</p>
						</div>
					</div>
				{elseif ($payment_type == 'PIX')}
					<div class="panel panel-success" id="pix_window">
						<div class="panel-heading heading-pix">
							{if $customer_name|strstr:' '}
							{$customer_name|strstr:' ':true}{else}{$customer_name}
								{l s=','}
							{/if}
							{l s='recebemos o seu pedido.' mod='pagbank'} <br />
							{l s='Para finalizar sua compra é só pagar com Pix!' mod='pagbank'}
						</div>
						<div class="panel-body">
							<p class="text-center">
								<img src="{$pix.link}" alt="{$pix.text}" class="img-responsive"
									style="margin:auto; max-width:220px;" />
								<br />
								<input type="text" id="pix_text" value="{$pix.text}" onClick="this.select();"
									style="width:50%" />
								<br /><br />
								<button id="pix_text_button" class="btn btn-info border" data-clipboard-target="#pix_text"
									data-clipboard-action="copy">Copiar código Pix</button>
							</p>
							<p class="alert alert-warning text-center">
								{if $alternate_time}
									{l s='Devido ao horário, o limite para pagamentos via pix pode ser reduzido, verifique junto ao seu banco.' mod='pagbank'}
									<br />
								{else}
									{l s='Efetue o PIX imediatamente, pois o pedido tem um prazo de' mod='pagbank'} <span
										class="prazo_pix">{if {$pix.deadline.hours} > 0}{$pix.deadline.hours}
											{l s='horas' mod='pagbank'}{/if}{if {$pix.deadline.minutes} > 0} {$pix.deadline.minutes}
										{l s='minutos' mod='pagbank'}{/if}</span>.
								{/if}
								<br>
								{l s='Você deve efetuar o pagamento até:' mod='pagbank'} <button id="pix_deadline"
									class="btn btn-default btn-sm">{$pix.expiration_date|date_format:"%d/%m/%Y %H:%M"}</button>
								<br>
								<br>
								{l s='Seu pedido só será processado pelo PagBank e pela loja após a confirmação do pagamento.' mod='pagbank'}
							</p>
						</div>
					</div>
				{elseif ($payment_type == 'WALLET')}
					<div class="panel panel-success" id="wallet_window">
						<div class="panel-heading heading-wallet">
							{if $customer_name|strstr:' '}
							{$customer_name|strstr:' ':true}{else}{$customer_name}
								{l s=','}
							{/if}
							{l s='recebemos o seu pedido.' mod='pagbank'} <br />
							{if $device == 'd' || $device == 't'}
								{l s='Para finalizar sua compra, escaneie o QR Code abaixo através do app PagBank e escolha se deseja pagar com o saldo ou cartão cadastrado.' mod='pagbank'}
							{else}
								{l s='Para finalizar sua compra, clique no botão abaixo para realizar o pagamento através do app PagBank, utilizando o seu saldo ou cartão cadastrado.' mod='pagbank'}
							{/if}
						</div>
						<div class="panel-body">
							<p class="text-center">
								{if $device == 'd'}
									<img src="{$wallet.link}" alt="{$wallet.text}" class="img-responsive"
										style="margin:auto; max-width:220px;" />
									<br />
									<input type="text" id="wallet_text" value="{$wallet.text}" onClick="this.select();"
										style="width:50%" />
									<br /><br />
									<button id="wallet_text_button" class="btn btn-info border" data-clipboard-target="#wallet_text"
										data-clipboard-action="copy">Copiar código</button>
								{else}
									<a href="{$wallet.link}" target="_blank" class="btn-pagbank">
										<img src="{$this_path}img/btn_green_pagbank.png" class="img-responsive" />
									</a>
								{/if}
							</p>
							<p class="alert alert-warning text-center">
								{l s='Efetue o pagamento imediatamente, pois o pedido tem um prazo de' mod='pagbank'}
								<span>{if {$wallet.prazo.hours} > 0}{$wallet.prazo.hours} {l s='horas' mod='pagbank'}{/if}{if {$wallet.prazo.minutes} > 0} {$wallet.prazo.minutes} {l s='minutos' mod='pagbank'}{/if}</span>.
								<br>
								{l s='Você deve efetuar o pagamento até:' mod='pagbank'} <button id="wallet_deadline"
									class="btn btn-default btn-sm">{$wallet.expiration_date|date_format:"%d/%m/%Y %H:%M"}</button>
								<br>
								<br>
								{l s='Seu pedido só será processado pelo PagBank e pela loja após a confirmação do pagamento.' mod='pagbank'}
							</p>
						</div>
					</div>
				{/if}
				<br />
				<div class="panel panel-primary col-xs-12 col-sm-12 col-lg-12 data_waiting nopadding">
					<div class="panel-heading">
						<h4 class="panel-title">
							{l s='Você receberá um e-mail com todos os detalhes do seu pedido.' mod='pagbank'}</h4>
					</div>
					<div class="panel-body">
						<h4 class="title-box">{l s='Abaixo os dados referente ao seu pagamento:' mod='pagbank'}</h4>
						<ul class="list clearfix">
							<li><b>Código da transação:</b> {$transaction_code}</li>
							<li><b>Número do pedido:</b> {$info.reference}</li>
							<li><b>Referência do pedido:</b> {$order_reference}</li>
							<li>
								<b>Valor do pedido:</b> {Tools::displayPrice($order_value)}
								{if (isset($transaction->charges)) && $transaction->charges[0]->payment_method->type == 'CREDIT_CARD'}
									{if $transaction->charges[0]->payment_method->installments >= 2}
										(parcelado em {$transaction->charges[0]->payment_method->installments}x)
									{/if}
								{/if}
							</li>
							<li><b>Status:</b> {$info.status_description} - {$info.payment_description}</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<br>
		{if ($payment_status != 'CANCELED' && $payment_status != 'DECLINED')}
			<div class="table_container clearfix">
				<h4><b>{l s='Resumo do pedido' mod='pagbank'}</b></h4>
				<table class="table">
					<thead>
						<th>{l s='ID' mod='pagbank'}</th>
						<th>{l s='Nome' mod='pagbank'}</th>
						<th>{l s='Preço' mod='pagbank'}</th>
						<th>{l s='Qtd' mod='pagbank'}</th>
						<th class="text-right">{l s='Total' mod='pagbank'}</th>
					</thead>
					<tbody>
						{foreach from=$order_products item='produto' name='prods'}
							<tr>
								<td>{$produto.product_id}</td>
								<td>{$produto.product_name}</td>
								<td>{displayPrice price=$produto.product_price}</td>
								<td>{$produto.product_quantity}</td>
								<td class="price text-right">{displayPrice price=$produto.total_price_tax_incl}</td>
							</tr>
						{/foreach}
					</tbody>
					<tfoot>
						<tr class="total_prods">
							<td colspan="3">{l s='Total de Produtos' mod='pagbank'}</td>
							<td colspan="2" class="price text-right">{displayPrice price=$order->total_products}</td>
						</tr>
						{if ($order->total_discounts) > 0}
							<tr class="extra">
								<td colspan="3">{l s='Descontos' mod='pagbank'}</td>
								<td colspan="2" class="price text-right">{displayPrice price=$order->total_discounts}</td>
							</tr>
						{/if}
						{if ($order->total_wrapping) > 0}
							<tr class="extra">
								<td colspan="3">{l s='Embalagem de presente' mod='pagbank'}</td>
								<td colspan="2" class="price text-right">{displayPrice price=$order->total_wrapping}</td>
							</tr>
						{/if}
						<tr class="frete">
							<td colspan="3">{l s='Frete' mod='pagbank'}</td>
							<td colspan="2" class="price text-right">{displayPrice price=$order->total_shipping}</td>
						</tr>
						<tr class="total">
							<td colspan="3">{l s='Total do Pedido' mod='pagbank'}</td>
							<td colspan="2" class="price text-right">{displayPrice price=$order->total_paid}</td>
						</tr>
					</tfoot>
				</table>
			</div>
		{else}
			<div class="alert alert-warning">
				<p>{l s='Houve um problema com o seu pedido.' mod='pagbank'}</p>
				<p>{l s='Recomendamos que confira os dados informados e tente novamente, clicando no botão abaixo' mod='pagbank'}
				</p>
				<p align="center"><a class="btn btn-lg btn-info"
						href="{$link->getPageLink('order')}?submitReorder=1&id_order={$order_id}"
						title="{l s='Refazer pedido' mod='pagbank'}">{l s='Refazer pedido' mod='pagbank'}</a></p>
			</div>
			<script type="text/javascript">
				window.onload = function() {
					setTimeout(function() {
						location.reload(true);
					}, 10000);
				}
			</script>
		{/if}
	</div>
	{if ($payment_type == 'BOLETO')}
		{literal}
			<script type="text/javascript">
				window.onload = function() {
					var PaymentWindow = window.open("{/literal}{$pay_link}{literal}", "Boleto para Pagamento", "toolbar=no,scrollbars=yes,resizable=yes,top=100,left=700,width=700,height=500");
					PaymentWindow.focus();
				}
			</script>
		{/literal}
	{/if}
	{if ($payment_type == 'PIX')}
		<div id="pix_success" class="form-group clearfix row" align="center">
			<div id="proccess_pix" style="height:auto; width:600px; max-width:100%; display:none;"
				class="container clearfix">
				<div class="row">
					<div class="col-xs-3 col-sm-2 nopadding" align="center">
						<img src="{$this_path}img/loading.gif" class="img-responsive" />
					</div>
					<div class="col-xs-6 col-sm-7 text-center" id="pagbankmsg">
						{l s='PIX Recebido! Redirecionando...' mod='pagbank'}
					</div>
					{if $device == 'd' || $device == 't'}
						<div class="col-sm-3 nopadding-left" id="pagbank_logo" align="center">
							<img src="{$this_path}img/pagbank-logo-animado_35px.gif" class="img-responsive" />
						</div>
					{else}
						<div class="col-xs-3 nopadding-left" id="pagbank_logo" align="center">
							<img src="{$this_path}img/logo_pagbank_mini_mobile.png" class="img-responsive" />
						</div>
					{/if}
				</div>
			</div>
		</div>

		{literal}
			<script type="text/javascript">
				var clipboard = new ClipboardJS('#pix_text_button');
				clipboard.on('success', function(e) {
					window.alert('Código Pix copiado!');
					console.log(e);
				});
				clipboard.on('error', function(e) {
					console.log(e);
				});

				function getOrderStatus() {
					var order_id = {/literal}{$order_id}{literal};
					var paid_state = {/literal}{$paid_state}{literal};
					var my_orders = '{/literal}{$link->getPageLink('history')}?id_order={$order_id}{literal}';
					$.ajax({
						url: '{/literal}{$url_update}{literal}?action=checkOrder&id_order='+order_id,
						cache: false,
						success: function(data) {
							var json = data;
							$.each(json, function(i, item) {
								if (item.id_order_state == paid_state) {
									document.getElementById('pix_success').classList.add('loading');
									document.getElementById('pix_success').style.width = window.innerWidth;
									document.getElementById('proccess_pix').style.display = 'block';
									setInterval(function() {
										window.location.href = my_orders;
										console.log(my_orders);
									}, 4000);
								}
							});
						},
						complete: function() {},
						error: function(xhr) {
							console.log(xhr.status);
						}
					});
				}

				window.onload = function() {
					setTimeout(function() {
						window.scroll(0, 200);
					}, 500);

					setInterval('getOrderStatus()', 10000);
				}
			</script>
		{/literal}
	{/if}
	{if ($payment_type == 'WALLET')}
		<div id="wallet_success" class="form-group clearfix row" align="center">
			<div id="proccess_wallet" style="height:auto; width:600px; max-width:100%; display:none;"
				class="container clearfix">
				<div class="row">
					<div class="col-xs-3 col-sm-2 nopadding" align="center">
						<img src="{$this_path}img/loading.gif" class="img-responsive" />
					</div>
					<div class="col-xs-6 col-sm-7 text-center" id="pagbankmsg">
						{l s='PIX Recebido! Redirecionando...' mod='pagbank'}
					</div>
					<div class="hidden-xs col-sm-3 nopadding-left" id="pagbank_logo" align="center">
						<img src="{$this_path}img/pagbank-logo-animado_35px.gif" class="img-responsive" />
					</div>
					<div class="hidden-lg hidden-md hidden-sm col-xs-3 nopadding-left" id="pagbank_logo" align="center">
						<img src="{$this_path}img/logo_pagbank_mini_mobile.png" class="img-responsive" />
					</div>
				</div>
			</div>
		</div>

		{literal}
			<script type="text/javascript">
				var clipboard = new ClipboardJS('#wallet_text_button');
				clipboard.on('success', function(e) {
					window.alert('Código Pix copiado!');
					console.log(e);
				});
				clipboard.on('error', function(e) {
					console.log(e);
				});

				function getOrderStatus() {
					var order_id = {/literal}{$order_id}{literal};
					var paid_state = {/literal}{$paid_state}{literal};
					var my_orders = '{/literal}{$link->getPageLink('history')}?id_order={$order_id}{literal}';
					$.ajax({
						url: '{/literal}{$url_update}{literal}?action=checkOrder&id_order='+order_id,
						cache: false,
						success: function(data) {
							//var json = $.parseJSON(data);
							var json = data;
							$.each(json, function(i, item) {
								if (item.id_order_state == paid_state) {
									document.getElementById('wallet_success').classList.add('loading');
									document.getElementById('wallet_success').style.width = window.innerWidth;
									document.getElementById('proccess_wallet').style.display = 'block';
									setInterval(function() {
										window.location.href = my_orders;
										console.log(my_orders);
									}, 4000);
								}
							});
						},
						complete: function() {},
						error: function(xhr) {
							console.log(xhr.status);
						}
					});
				}

				window.onload = function() {
					setTimeout(function() {
						window.scroll(0, 200);
					}, 500);

					setInterval('getOrderStatus()', 10000);
				}
			</script>
		{/literal}
	{/if}
</div>