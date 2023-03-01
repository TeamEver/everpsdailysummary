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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Everpsdailysummary extends Module
{
    private $html;
    private $postErrors = array();
    private $postSuccess = array();

    public function __construct()
    {
        $this->name = 'everpsdailysummary';
        $this->tab = 'administration';
        $this->version = '2.1.2';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Ever PS Daily Summary');
        $this->description = $this->l('Send the list of commands to administrators on cron');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $cron_url = _PS_BASE_URL_._MODULE_DIR_.'everpsdailysummary/everpsdailysummarycron.php?token=';
        $cron_token = Tools::substr(Tools::encrypt('everpsdailysummary/cron'), 0, 10);
        $id_shop = (int)$this->context->shop->id;
        $this->context->smarty->assign(array(
            'everpsdailysummary_cron' => $cron_url.$cron_token.'&id_shop='.(int)$id_shop,
        ));
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionFrontControllerAfterInit') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitEverpsdailysummaryModule')) == true) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $this->postProcess();
            }
        }

        // Display errors
        if (count($this->postErrors)) {
            foreach ($this->postErrors as $error) {
                $this->html .= $this->displayError($error);
            }
        }

        // Display confirmations
        if (count($this->postSuccess)) {
            foreach ($this->postSuccess as $success) {
                $this->html .= $this->displayConfirmation($success);
            }
        }

        $this->context->smarty->assign(array(
            'everpsdailysummary_dir' => $this->_path,
        ));

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        if ($this->checkLatestEverModuleVersion($this->name, $this->version)) {
            $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/upgrade.tpl');
        }
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEverpsdailysummaryModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $orderStates = OrderState::getOrderStates(
            (int)Context::getContext()->language->id
        );
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'hint' => $this->l('Will receive all daily orders'),
                        'name' => 'EVERPSDAILYSUMMARY_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Send only validated orders'),
                        'desc' => $this->l('Would you like to send only validated orders'),
                        'hint' => $this->l('Else all orders will be sent'),
                        'name' => 'EVERPSDAILYSUMMARY_VALIDATED_ONLY',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Validated order state'),
                        'name' => 'EVERPSDAILYSUMMARY_VALIDATED_STATE_ID',
                        'desc' => $this->l('Specify the validated order state'),
                        'hint' => $this->l('Only orders having this state will be sent by email'),
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Please set time for sending orders'),
                        'name' => 'EVERPSDAILYSUMMARY_TIME',
                        'desc' => $this->l('Specify the time when you want to receive orders by email'),
                        'hint' => $this->l('Orders will be sent by email ever day day at this selected time'),
                        'required' => true,
                        'options' => array(
                            'query' => $this->getTime(),
                            'id' => 'id_time',
                            'name' => 'name'
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EVERPSDAILYSUMMARY_ACCOUNT_EMAIL' => Configuration::get(
                'EVERPSDAILYSUMMARY_ACCOUNT_EMAIL',
                'noreply@team-ever.com'
            ),
            'EVERPSDAILYSUMMARY_VALIDATED_STATE_ID' => Configuration::get(
                'EVERPSDAILYSUMMARY_VALIDATED_STATE_ID'
            ),
            'EVERPSDAILYSUMMARY_VALIDATED_ONLY' => Configuration::get(
                'EVERPSDAILYSUMMARY_VALIDATED_ONLY'
            ),
            'EVERPSDAILYSUMMARY_TIME' => Configuration::get(
                'EVERPSDAILYSUMMARY_TIME'
            ),
        );
    }

    private function postValidation()
    {
        if (!Tools::getValue('EVERPSDAILYSUMMARY_VALIDATED_STATE_ID')
            || !Validate::isInt(Tools::getValue('EVERPSDAILYSUMMARY_VALIDATED_STATE_ID'))
        ) {
            $this->postErrors[] = $this->l('Error : [Validate order state] is not valid');
        }
        if (!Tools::getValue('EVERPSDAILYSUMMARY_ACCOUNT_EMAIL')
            || !Validate::isEmail(Tools::getValue('EVERPSDAILYSUMMARY_ACCOUNT_EMAIL'))
        ) {
            $this->postErrors[] = $this->l('Error : [Email] is not valid');
        }
        if (Tools::getValue('EVERPSDAILYSUMMARY_VALIDATED_ONLY')
            && !Validate::isBool(Tools::getValue('EVERPSDAILYSUMMARY_VALIDATED_ONLY'))
        ) {
            $this->postErrors[] = $this->l('Error : [Only validated] is not valid');
        }
        if (!Tools::getValue('EVERPSDAILYSUMMARY_TIME')
            || !Validate::isUnsignedInt(Tools::getValue('EVERPSDAILYSUMMARY_TIME'))
        ) {
            $this->postErrors[] = $this->l('Error : [Daily time] is not valid');
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookActionFrontControllerAfterInit()
    {
        $hour = date('H');
        $date = date('d');
        if ((int)$hour != (int)Configuration::get('EVERPSDAILYSUMMARY_TIME')) {
            return;
        }
        if ($date == (int)Configuration::get('EVERPSDAILYSUMMARY_SENT')) {
            return;
        }
        return $this->sendDailyOrders(
            (int)Context::getContext()->shop->id
        );
    }

    private function getDailyOrders($id_shop)
    {
        $only_validated = (bool)Configuration::get(
            'EVERPSDAILYSUMMARY_VALIDATED_ONLY'
        );
        $validated_state = (int)Configuration::get(
            'EVERPSDAILYSUMMARY_VALIDATED_STATE_ID'
        );
        // Get orders
        if ((bool)$only_validated === true) {
            $query = 'SELECT id_order
            FROM '._DB_PREFIX_.'orders
            WHERE date_add >= CURDATE()
            AND date_add < CURDATE() + INTERVAL 1 DAY
            AND current_state = '.(int)$validated_state.'
            AND id_shop = '.(int)$id_shop.'';
        } else {
            $query = 'SELECT id_order
            FROM '._DB_PREFIX_.'orders
            WHERE date_add >= CURDATE()
            AND date_add < CURDATE() + INTERVAL 1 DAY
            AND id_shop = '.(int)$id_shop.'';
        }
        $orders = Db::getInstance()->executeS($query);
        // Create array of obj containing all required orders infos
        $daily_orders = array();
        foreach ($orders as $value) {
            $daily = new stdClass;
            $order = new Order(
                (int)$value['id_order']
            );
            $customer = new Customer(
                (int)$order->id_customer
            );
            $address = new Address(
                (int)$order->id_address_delivery
            );
            $id_carrier = $order->getIdOrderCarrier();
            $order_carrier = new OrderCarrier(
                (int)$id_carrier
            );
            $carrier = new Carrier(
                (int)$order_carrier->id_carrier
            );
            // Add ordered products
            $daily->carrier_name = $carrier->name;
            $products = $order->getProductsDetail();
            $daily->id_order = $order->id;
            $daily->reference = $order->reference;
            $daily->payment = $order->payment;
            $daily->date_add = $order->date_add;
            $daily->total_paid = $order->total_paid;
            $daily->total_shipping = $order->total_shipping;
            // Customer infos
            $daily->customer_firstname = $customer->firstname;
            $daily->customer_lastname = $customer->lastname;
            // Address infos
            $daily->alias = $address->alias;
            $daily->address_firstname = $address->firstname;
            $daily->address_lastname = $address->lastname;
            $daily->address1 = $address->address1;
            $daily->address2 = $address->address2;
            $daily->postcode = $address->postcode;
            $daily->city = $address->city;
            $daily->phone = $address->phone;
            // Add ordered products
            $daily->products = $products;
            $daily_orders[] = $daily;
        }
        return $daily_orders;
    }

    public function sendDailyOrders($id_shop)
    {
        $orders = $this->getDailyOrders($id_shop);
        if (empty($orders)) {
            return false;
        }
        // Now create table of datas
        $tdStyle = 'style="padding:0.3rem 1rem 0.3rem 1rem;"';
        $tableStyle = 'style="border-collapse: collapse;width:100%;"';
        $items = '';
        foreach ($orders as $order) {
            $table = '<h4>'.$order->reference.'</h4>';
            // First global datas, as customer
            $table .= '<table '.$tableStyle.'>';
            // Global datas header
            $table .= '<tr style="background-color:#e3e3e3">';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Order reference');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Customer');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Address');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Postcode');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('City');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Phone');
            $table .=  '</td>';
            $table .=  '</tr>';
            // Global datas infos
            $table .= '<tr>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->reference;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->customer_firstname.' '.$order->customer_lastname;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->address1.' '.$order->address2;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->postcode;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->city;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->phone;
            $table .= '</td>';
            $table .= '</tr>';
            $table .= '<table>';
            // End global datas
            // Now products
            $table .= '<h4>'.$this->l('Ordered products').'</h4>';
            $table .= '<table '.$tableStyle.'>';
            // Products header
            $table .= '<tr style="background-color:#e3e3e3">';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Product name');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Product reference');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Product quantity');
            $table .=  '</td>';
            $table .=  '</tr>';
            // Products infos on a loop
            foreach ($order->products as $product) {
                $table .= '<tr>';
                $table .=  '<td '.$tdStyle.'>';
                $table .= $product['product_name'];
                $table .= '</td>';
                $table .=  '<td '.$tdStyle.'>';
                $table .= $product['product_reference'];
                $table .= '</td>';
                $table .=  '<td '.$tdStyle.'>';
                $table .= $product['product_quantity'];
                $table .= '</td>';
                $table .= '</tr>';
            }
            $table .= '<table>';
            // End products
            // Now others datas
            $table .= '<h4>'.$this->l('Order informations').'</h4>';
            $table .= '<table '.$tableStyle.'>';
            // Others datas header
            $table .= '<tr style="background-color:#e3e3e3">';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Payment method');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Order date add');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Total paid');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Shipping method');
            $table .=  '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $this->l('Total shipping');
            $table .=  '</td>';
            $table .=  '</tr>';
            // Others datas infos
            $table .= '<tr>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->payment;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->date_add;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->total_paid;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->carrier_name;
            $table .= '</td>';
            $table .=  '<td '.$tdStyle.'>';
            $table .= $order->total_shipping;
            $table .= '</td>';
            $table .= '</tr>';
            $table .= '<table>';
            $table .= '<hr>';
            $table .= '<hr>';
            // End other datas
            $items .= $table;
        }
        // Then send email
        $email = (string)Configuration::get(
            'EVERPSDAILYSUMMARY_ACCOUNT_EMAIL'
        );
        $subject = $this->l('Daily orders');
        $mailDir = _PS_MODULE_DIR_.'everpsdailysummary/mails/';
        $sent = Mail::send(
            (int)Context::getContext()->language->id,
            'everpsdailysummary',
            (string)$subject,
            array(
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                '{shop_logo}' => _PS_IMG_DIR_.Configuration::get(
                    'PS_LOGO',
                    null,
                    null,
                    (int)$id_shop
                ),
                '{message}' => $items,
            ),
            (string)$email,
            (string)Configuration::get('PS_SHOP_NAME'),
            (string)Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_SHOP_NAME'),
            null,
            null,
            $mailDir
        );
        if ($sent) {
            Configuration::updateValue('EVERPSDAILYSUMMARY_SENT', date('d'));
        }
        return $sent;
    }

    private function getTime()
    {
        $time = array(
            array(
                'id_time' => 1,
                'name' => '01:00'
            ),
            array(
                'id_time' => 2,
                'name' => '02:00'
            ),
            array(
                'id_time' => 3,
                'name' => '03:00'
            ),
            array(
                'id_time' => 4,
                'name' => '04:00'
            ),
            array(
                'id_time' => 5,
                'name' => '05:00'
            ),
            array(
                'id_time' => 6,
                'name' => '06:00',
            ),
            array(
                'id_time' => 7,
                'name' => '07:00',
            ),
            array(
                'id_time' => 8,
                'name' => '08:00'
            ),
            array(
                'id_time' => 9,
                'name' => '09:00'
            ),
            array(
                'id_time' => 10,
                'name' => '10:00'
            ),
            array(
                'id_time' => 11,
                'name' => '11:00'
            ),
            array(
                'id_time' => 12,
                'name' => '12:00',
            ),
            array(
                'id_time' => 13,
                'name' => '13:00'
            ),
            array(
                'id_time' => 14,
                'name' => '14:00'
            ),
            array(
                'id_time' => 15,
                'name' => '15:00'
            ),
            array(
                'id_time' => 16,
                'name' => '16:00'
            ),
            array(
                'id_time' => 17,
                'name' => '17:00'
            ),
            array(
                'id_time' => 18,
                'name' => '18:00'
            ),
            array(
                'id_time' => 19,
                'name' => '19:00'
            ),
            array(
                'id_time' => 20,
                'name' => '20:00'
            ),
            array(
                'id_time' => 21,
                'name' => '21:00'
            ),
            array(
                'id_time' => 22,
                'name' => '22:00'
            ),
            array(
                'id_time' => 23,
                'name' => '23:00'
            ),
            array(
                'id_time' => 24,
                'name' => '24:00'
            )

        );
        return $time;
    }

    public function checkLatestEverModuleVersion($module, $version)
    {
        $upgrade_link = 'https://upgrade.team-ever.com/upgrade.php?module='
        .$module
        .'&version='
        .$version;
        $handle = curl_init($upgrade_link);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            curl_close($handle);
            return false;
        }
        curl_close($handle);
        $module_version = Tools::file_get_contents(
            $upgrade_link
        );
        if ($module_version && $module_version > $version) {
            return true;
        }
        return false;
    }
}