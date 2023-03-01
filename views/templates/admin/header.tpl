{*
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
*}
<div class="panel row">
    <h3><i class="icon icon-smile"></i> {l s='Ever Daily Summary' mod='everpsdailysummary'}</h3>
    <div class="col-12 col-xs-12 col-md-6">
        <img id="everlogo" src="{$everpsdailysummary_dir|escape:'htmlall':'UTF-8'}/logo.png" style="max-width: 120px;">
        <p>
            <strong>{l s='Welcome to Ever Daily Summary !' mod='everpsdailysummary'}</strong><br />
        </p>
        <p>
            <strong>{l s='Please set this cron daily' mod='everpsdailysummary'}</strong><br />
            <code>{$everpsdailysummary_cron|escape:'htmlall':'UTF-8'}</code><br />
            <a href="{$everpsdailysummary_cron|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Trigger this cron now !' mod='everpsdailysummary'}</a>
        </p>
        <h4>{l s='How to be first on Google pages ?' mod='everpsdailysummary'}</h4>
        <p>{l s='We have created the best SEO module, by working with huge websites and SEO societies' mod='everpsdailysummary'}</p>
        <p>
            <a href="https://addons.prestashop.com/fr/seo-referencement-naturel/39489-ever-ultimate-seo.html" target="_blank">{l s='See the best SEO module on Prestashop Addons' mod='everpsdailysummary'}</a>
        </p>
    </div>
    <div class="col-12 col-xs-12 col-md-6">
        <p class="alert alert-warning">
            {l s='This module is free and will always be ! You can support our free modules by making a donation by clicking the button below' mod='everpsdailysummary'}
        </p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick" />
            <input type="hidden" name="hosted_button_id" value="3LE8ABFYJKP98" />
            <input type="image" src="https://www.team-ever.com/wp-content/uploads/2019/06/appel_a_dons-1.jpg" border="0" name="submit" title="Soutenez le développement des modules gratuits de Team Ever !" alt="Soutenez le développement des modules gratuits de Team Ever !" />
            <img alt="" border="0" src="https://www.paypal.com/fr_FR/i/scr/pixel.gif" width="1" height="1" />
        </form>
    </div>
</div>
