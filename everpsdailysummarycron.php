<?php
/**
 * 2019-2021 Team Ever
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *  @author    Team Ever <https://www.team-ever.com/>
 *  @copyright 2019-2021 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

if (!Tools::getIsset('token')
    || Tools::substr(Tools::encrypt('everpsdailysummary/cron'), 0, 10) != Tools::getValue('token')
    || !Module::isInstalled('everpsdailysummary')
) {
    Tools::redirect('index.php');
}

$everpsdailysummary = Module::getInstanceByName('everpsdailysummary');

if (!$everpsdailysummary->active) {
    Tools::redirect('index.php');
}
/* Check if the requested shop exists */
$shops = Db::getInstance()->ExecuteS('SELECT id_shop FROM `' . _DB_PREFIX_ . 'shop`');

$listIdShop = array();
foreach ($shops as $shop) {
    $listIdShop[] = (int) $shop['id_shop'];
}

$id_shop = (Tools::getIsset('id_shop') && in_array(Tools::getValue('id_shop'), $listIdShop))
    ? (int) Tools::getValue('id_shop') : (int) Configuration::get('PS_SHOP_DEFAULT');
$everpsdailysummary->cron = true;

$everpsdailysummary->sendDailyOrders((int)$id_shop);

die('Email has been sent 🙂 !');
