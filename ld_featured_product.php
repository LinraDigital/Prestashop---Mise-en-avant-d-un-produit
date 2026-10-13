<?php
/**
 * ld_featured_product - Linra Digital
 * Met en avant un produit choisi en admin sur la page d'accueil.
 * S'appuie exclusivement sur les données natives PrestaShop (Product / ProductLang / ProductShop, images),
 * accédées via _DB_PREFIX_ (jamais de préfixe de table en dur).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ld_Featured_Product extends Module implements WidgetInterface
{
    /** @var string */
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ld_featured_product';
        $this->tab = 'front_office_features';
        $this->version = '1.3.0';
        $this->author = 'Linra Digital';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('LD - Featured Product');
        $this->description = $this->l('Met en avant un produit choisi en admin sur la page d\'accueil (titre, description, prix, galerie).');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:ld_featured_product/ld_featured_product.tpl';
    }

    public function install()
    {
        $this->_clearCache('ld_featured_product.tpl');

        // Textes multilingues : la valeur doit être un tableau [id_lang => texte].
        $buttons = [];
        $eyebrows = [];
        foreach (Language::getLanguages(false) as $lang) {
            $buttons[(int) $lang['id_lang']] = 'Voir le produit';
            $eyebrows[(int) $lang['id_lang']] = 'Notre sélection';
        }

        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('actionFrontControllerSetMedia')
            && Configuration::updateValue('LD_FEATURED_BTN_TEXT', $buttons)
            && Configuration::updateValue('LD_FEATURED_EYEBROW', $eyebrows);
    }

    public function uninstall()
    {
        Configuration::deleteByName('LD_FEATURED_PRODUCT_ID');

        Configuration::deleteByName('LD_FEATURED_BTN_TEXT');
        Configuration::deleteByName('LD_FEATURED_EYEBROW');

        return parent::uninstall();
    }

    /**
     * ------------------------------------------------------------------
     * Back office : formulaire de configuration
     * ------------------------------------------------------------------
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitLdFeaturedProduct')) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Configuration mise à jour.'));
            $this->_clearCache('ld_featured_product.tpl');
        }

        return $output . $this->renderForm();
    }

    public function postProcess()
    {
        $id_product = (int) Tools::getValue('LD_FEATURED_PRODUCT_ID');
        Configuration::updateValue('LD_FEATURED_PRODUCT_ID', $id_product);

        // Valeurs multilingues : des tableaux [id_lang => texte] (et non une valeur par boutique).
        $buttons = [];
        $eyebrows = [];
        foreach (Language::getLanguages(false) as $lang) {
            $id_lang = (int) $lang['id_lang'];
            $buttons[$id_lang] = Tools::getValue('LD_FEATURED_BTN_TEXT_' . $id_lang);
            $eyebrows[$id_lang] = Tools::getValue('LD_FEATURED_EYEBROW_' . $id_lang);
        }
        Configuration::updateValue('LD_FEATURED_BTN_TEXT', $buttons);
        Configuration::updateValue('LD_FEATURED_EYEBROW', $eyebrows);
    }

    public function renderForm()
    {
        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) $this->context->shop->id;

        // Liste des produits actifs de la boutique courante, pour le select
        $sql = 'SELECT p.id_product, pl.name
                FROM `' . _DB_PREFIX_ . 'product` p
                INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps
                    ON (ps.id_product = p.id_product AND ps.id_shop = ' . $id_shop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON (pl.id_product = p.id_product AND pl.id_lang = ' . $id_lang . ' AND pl.id_shop = ' . $id_shop . ')
                WHERE p.active = 1
                ORDER BY pl.name ASC';

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('LD - Featured Product'),
                    'icon' => 'icon-star',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Produit à mettre en avant'),
                        'name' => 'LD_FEATURED_PRODUCT_ID',
                        'class' => 'chosen',
                        'options' => [
                            'query' => $products,
                            'id' => 'id_product',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Texte au-dessus du titre'),
                        'name' => 'LD_FEATURED_EYEBROW',
                        'lang' => true,
                        'desc' => $this->l('Ex : "Notre sélection" - affiché en petit, au-dessus du nom du produit.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Texte du bouton'),
                        'name' => 'LD_FEATURED_BTN_TEXT',
                        'lang' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLdFeaturedProduct';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $fields = [
            'LD_FEATURED_PRODUCT_ID' => Configuration::get('LD_FEATURED_PRODUCT_ID'),
        ];

        foreach (Language::getLanguages(false) as $lang) {
            $id_lang = (int) $lang['id_lang'];
            $fields['LD_FEATURED_BTN_TEXT'][$id_lang] = Configuration::get('LD_FEATURED_BTN_TEXT', $id_lang);
            $fields['LD_FEATURED_EYEBROW'][$id_lang] = Configuration::get('LD_FEATURED_EYEBROW', $id_lang);
        }

        return $fields;
    }

    /**
     * ------------------------------------------------------------------
     * Front office
     * ------------------------------------------------------------------
     */
    public function hookDisplayHome($params)
    {
        return $this->renderWidget('displayHome', $params);
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        $this->context->controller->registerStylesheet(
            'ld-featured-product-css',
            'modules/' . $this->name . '/views/css/ld_featured_product.css',
            ['media' => 'all', 'priority' => 100]
        );

        $this->context->controller->registerJavascript(
            'ld-featured-product-js',
            'modules/' . $this->name . '/views/js/ld_featured_product.js',
            ['position' => 'bottom', 'priority' => 100]
        );
    }

    /**
     * ------------------------------------------------------------------
     * WidgetInterface
     * ------------------------------------------------------------------
     */
    public function renderWidget($hookName, array $configuration)
    {
        // Pas de cache Smarty : le prix/la dispo du produit peuvent changer sans qu'on y pense
        $variables = $this->getWidgetVariables($hookName, $configuration);
        if (empty($variables['ld_featured'])) {
            return false;
        }
        $this->smarty->assign($variables);

        return $this->fetch($this->templateFile);
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $id_product = (int) Configuration::get('LD_FEATURED_PRODUCT_ID');

        if (!$id_product) {
            return [];
        }

        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) $this->context->shop->id;

        $product = new Product($id_product, true, $id_lang, $id_shop);

        if (!Validate::isLoadedObject($product) || !$product->active) {
            return [];
        }

        $images = $product->getImages($id_lang);

        if (empty($images)) {
            return [];
        }

        $cover = null;
        $gallery = [];

        foreach ($images as $img) {
            $large = $this->context->link->getImageLink($product->link_rewrite, $img['id_image'], 'large_default');
            $thumb = $this->context->link->getImageLink($product->link_rewrite, $img['id_image'], 'small_default');

            $gallery[] = [
                'large' => $large,
                'thumb' => $thumb,
            ];

            if (!empty($img['cover'])) {
                $cover = $large;
            }
        }

        if (!$cover) {
            $cover = $gallery[0]['large'];
        }

        // HT ou TTC selon le mode d'affichage des taxes du groupe du client (visiteur non connecté
        // compris) : même logique que partout ailleurs sur la boutique.
        $use_tax = !Product::getTaxCalculationMethod((int) $this->context->customer->id);

        // Prix avec réduction (celui affiché en avant) et prix plein (pour le barré)
        $price = Product::getPriceStatic($id_product, $use_tax, null, 2);
        $price_before = Product::getPriceStatic($id_product, $use_tax, null, 2, null, false, false);
        $has_discount = $price_before > ($price + 0.01);

        $tax_label = $use_tax ? $this->l('TTC') : $this->l('HT');

        $btn_text = Configuration::get('LD_FEATURED_BTN_TEXT', $id_lang);
        $eyebrow = Configuration::get('LD_FEATURED_EYEBROW', $id_lang);

        return [
            'ld_featured' => [
                'eyebrow' => $eyebrow,
                'name' => $product->name,
                'description_short' => $product->description_short,
                'price' => $this->formatPrice($price),
                'price_before' => $has_discount ? $this->formatPrice($price_before) : null,
                'tax_label' => $tax_label,
                'link' => $this->context->link->getProductLink($product),
                'cover' => $cover,
                'gallery' => $gallery,
                'btn_text' => $btn_text ?: $this->l('Voir le produit'),
            ],
        ];
    }

    private function formatPrice($price)
    {
        $price = (float) $price;

        // PrestaShop 1.7.6+ : formatage par la locale du contexte.
        if (isset($this->context->currentLocale) && $this->context->currentLocale) {
            return $this->context->currentLocale->formatPrice($price, $this->context->currency->iso_code);
        }

        // Versions antérieures : ancienne API.
        if (is_callable(['Tools', 'displayPrice'])) {
            return Tools::displayPrice($price);
        }

        return number_format($price, 2, ',', ' ') . ' €';
    }
}
