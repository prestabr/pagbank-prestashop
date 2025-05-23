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

function upgrade_module_1_5_0($module)
{
	$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '"._DB_PREFIX_."pagbank' AND column_name = 'nsu' AND table_schema = '"._DB_NAME_."'";
	$nsu = Db::getInstance()->getRow($sql);
	if (!$nsu) {
		if (!Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."pagbank` ADD `nsu` varchar(32) NULL AFTER `installments`;")){
			return false;
		}
	}
	return true;
}
