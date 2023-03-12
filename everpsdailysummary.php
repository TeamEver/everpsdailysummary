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
    private $postErrors = [];
    private $postSuccess = [];

    public function __construct()
    {
        $this->name = 'everpsdailysummary';
        $this->tab = 'administration';
        $this->version = '3.1.1';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Ever PS Daily Summary');
        $this->description = $this->l('Send the list of commands to administrators on cron');
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_,
        );
        $cron_url = _PS_BASE_URL_._MODULE_DIR_.'everpsdailysummary/everpsdailysummarycron.php?token=';
        $cron_token = Tools::substr(Tools::encrypt('everpsdailysummary/cron'), 0, 10);
        $id_shop = (int) $this->context->shop->id;
        $this->context->smarty->assign(array(
            'everpsdailysummary_cron' => $cron_url . $cron_token . '&id_shop=' . (int) $id_shop,
        ));
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue(
            'EVERPSDAILYSUMMARY_MAILS',
            json_encode(
                [1]
            )
        );
        return parent::install() &&
            $this->registerHook('displayBackOfficeHeader');
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

        $this->html .= $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/header.tpl'
        );
        if ($this->checkLatestEverModuleVersion($this->name, $this->version)) {
            $this->html .= $this->context->smarty->fetch(
                $this->local_path . 'views/templates/admin/upgrade.tpl'
            );
        }
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch(
            $this->local_path . 'views/templates/admin/footer.tpl'
        );

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
        $employees = $this->getEmployees();
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-smile',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Allowed emails'),
                        'desc' => $this->l('Choose allowed emails'),
                        'hint' => $this->l('Those emails are your shop employees emails'),
                        'name' => 'EVERPSDAILYSUMMARY_MAILS[]',
                        'class' => 'chosen',
                        'identifier' => 'email',
                        'multiple' => true,
                        'options' => array(
                            'query' => $employees,
                            'id' => 'id_employee',
                            'name' => 'email',
                        ),
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
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
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
        $employeeEmails = Configuration::get(
            'EVERPSDAILYSUMMARY_MAILS'
        );
        if (!$employeeEmails) {
            Configuration::updateValue(
                'EVERPSDAILYSUMMARY_MAILS',
                json_encode(
                    [1]
                )
            );
        }
        return array(
            'EVERPSDAILYSUMMARY_VALIDATED_STATE_ID' => Configuration::get(
                'EVERPSDAILYSUMMARY_VALIDATED_STATE_ID'
            ),
            'EVERPSDAILYSUMMARY_VALIDATED_ONLY' => Configuration::get(
                'EVERPSDAILYSUMMARY_VALIDATED_ONLY'
            ),
            'EVERPSDAILYSUMMARY_MAILS[]' => Tools::getValue(
                'EVERPSDAILYSUMMARY_MAILS',
                json_decode(
                    Configuration::get(
                        'EVERPSDAILYSUMMARY_MAILS'
                    )
                )
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
        if (!Tools::getValue('EVERPSDAILYSUMMARY_MAILS')
            || !Validate::isArrayWithIds(Tools::getValue('EVERPSDAILYSUMMARY_MAILS'))
        ) {
            $this->postErrors[] = $this->l('Error: emails is not valid');
        }
        if (Tools::getValue('EVERPSDAILYSUMMARY_VALIDATED_ONLY')
            && !Validate::isBool(Tools::getValue('EVERPSDAILYSUMMARY_VALIDATED_ONLY'))
        ) {
            $this->postErrors[] = $this->l('Error : [Only validated] is not valid');
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            if ($key == 'EVERPSDAILYSUMMARY_MAILS[]') {
                Configuration::updateValue(
                    'EVERPSDAILYSUMMARY_MAILS',
                    json_encode(Tools::getValue('EVERPSDAILYSUMMARY_MAILS')),
                    true
                );
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    private function getDailyOrders($idShop)
    {
        $onlyValidated = (bool)Configuration::get(
            'EVERPSDAILYSUMMARY_VALIDATED_ONLY'
        );
        $validated_state = (int) Configuration::get(
            'EVERPSDAILYSUMMARY_VALIDATED_STATE_ID'
        );
        // Get orders
        if ((bool)$onlyValidated === true) {
            $query = 'SELECT id_order
            FROM ' . _DB_PREFIX_ . 'orders
            WHERE date_add >= CURDATE()
            AND date_add < CURDATE() + INTERVAL 1 DAY
            AND current_state = ' . (int) $validated_state.'
            AND id_shop = ' . (int) $idShop.'';
        } else {
            $query = 'SELECT id_order
            FROM ' . _DB_PREFIX_ . 'orders
            WHERE date_add >= CURDATE()
            AND date_add < CURDATE() + INTERVAL 1 DAY
            AND id_shop = ' . (int) $idShop.'';
        }
        $orders = Db::getInstance()->executeS($query);
        // Create array of obj containing all required orders infos
        $daily_orders = [];
        foreach ($orders as $value) {
            $daily = new stdClass();
            $order = new Order(
                (int) $value['id_order']
            );
            $customer = new Customer(
                (int) $order->id_customer
            );
            $address = new Address(
                (int) $order->id_address_delivery
            );
            $id_carrier = $order->getIdOrderCarrier();
            $order_carrier = new OrderCarrier(
                (int) $id_carrier
            );
            $carrier = new Carrier(
                (int) $order_carrier->id_carrier
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

    public function sendDailyOrders($idShop)
    {
        $orders = $this->getDailyOrders($idShop);
        if (empty($orders)) {
            return false;
        }
        // Now create table of datas
        $tdStyle = 'style="padding:0.3rem 1rem 0.3rem 1rem;"';
        $tableStyle = 'style="border-collapse: collapse;width:100%;"';
        $items = '';
        foreach ($orders as $order) {
            $table = '<h4>' . $order->reference . '</h4>';
            // First global datas, as customer
            $table .= '<table ' . $tableStyle . '>';
            // Global datas header
            $table .= '<tr style="background-color:#e3e3e3">';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Order reference');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Customer');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Address');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Postcode');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('City');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Phone');
            $table .=  '</td>';
            $table .=  '</tr>';
            // Global datas infos
            $table .= '<tr>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->reference;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->customer_firstname.' '.$order->customer_lastname;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->address1.' '.$order->address2;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->postcode;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->city;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->phone;
            $table .= '</td>';
            $table .= '</tr>';
            $table .= '<table>';
            // End global datas
            // Now products
            $table .= '<h4>'.$this->l('Ordered products').'</h4>';
            $table .= '<table ' . $tableStyle . '>';
            // Products header
            $table .= '<tr style="background-color:#e3e3e3">';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Product name');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Product reference');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Product quantity');
            $table .=  '</td>';
            $table .=  '</tr>';
            // Products infos on a loop
            foreach ($order->products as $product) {
                $table .= '<tr>';
                $table .=  '<td ' . $tdStyle . '>';
                $table .= $product['product_name'];
                $table .= '</td>';
                $table .=  '<td ' . $tdStyle . '>';
                $table .= $product['product_reference'];
                $table .= '</td>';
                $table .=  '<td ' . $tdStyle . '>';
                $table .= $product['product_quantity'];
                $table .= '</td>';
                $table .= '</tr>';
            }
            $table .= '<table>';
            // End products
            // Now others datas
            $table .= '<h4>'.$this->l('Order informations').'</h4>';
            $table .= '<table ' . $tableStyle . '>';
            // Others datas header
            $table .= '<tr style="background-color:#e3e3e3">';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Payment method');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Order date add');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Total paid');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Shipping method');
            $table .=  '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $this->l('Total shipping');
            $table .=  '</td>';
            $table .=  '</tr>';
            // Others datas infos
            $table .= '<tr>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->payment;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->date_add;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->total_paid;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
            $table .= $order->carrier_name;
            $table .= '</td>';
            $table .=  '<td ' . $tdStyle . '>';
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
        $subject = $this->l('Daily orders : ') . count($orders);
        $mailDir = _PS_MODULE_DIR_ . 'everpsdailysummary/mails/';

        $employeeEmails = Configuration::get(
            'EVERPSDAILYSUMMARY_MAILS'
        );
        if (!$employeeEmails) {
            return;
        }
        $employeeEmails = json_decode($employeeEmails);
        foreach ($employeeEmails as $employeeEmail) {
            $employee = new Employee(
                (int) $employeeEmail
            );
            if (!Validate::isLoadedObject($employee)) {
                continue;
            }
            $sent = Mail::send(
                (int)Context::getContext()->language->id,
                'everpsdailysummary',
                (string) $subject,
                [
                    '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                    '{shop_logo}' => _PS_IMG_DIR_ . Configuration::get(
                        'PS_LOGO',
                        null,
                        null,
                        (int) $idShop
                    ),
                    '{message}' => $items,
                ],
                (string)$employee->email,
                (string)Configuration::get('PS_SHOP_NAME'),
                (string)Configuration::get('PS_SHOP_EMAIL'),
                Configuration::get('PS_SHOP_NAME'),
                null,
                null,
                $mailDir
            );
        }    
        return $sent;
    }

    /**
     * Return list of employees.
     *
     * @param bool $activeOnly Filter employee by active status
     *
     * @return array|false Employees or false
     */
    protected function getEmployees($activeOnly = true)
    {
        return Db::getInstance()->executeS('
            SELECT *
            FROM `' . _DB_PREFIX_ . 'employee`
            ' . ($activeOnly ? ' WHERE `active` = 1' : '') . '
            ORDER BY `lastname` ASC
        ');
    }

    public function checkLatestEverModuleVersion($module, $version)
    {
        $upgradeLink = 'https://upgrade.team-ever.com/upgrade.php?module='
        . $module
        . '&version='
        . $version;
        $handle = curl_init($upgradeLink);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            curl_close($handle);
            return false;
        }
        curl_close($handle);
        $module_version = Tools::file_get_contents(
            $upgradeLink
        );
        if ($module_version && $module_version > $version) {
            return true;
        }
        return false;
    }
}
