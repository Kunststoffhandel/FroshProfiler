<?php

namespace FroshProfiler\Components\Collectors;

use Enlight_Controller_Action;
use FroshProfiler\Components\Struct\Profile;
use FroshProfiler\Components\Struct\Tax;

class CartCollector implements CollectorInterface
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Cart';
    }

    /**
     * @param Enlight_Controller_Action $controller
     * @param Profile                   $profile
     */
    public function collect(Enlight_Controller_Action $controller, Profile $profile)
    {
        $cart = $controller->View()->getAssign('sBasket');

        if (empty($cart) || empty($cart['content'])) {
            return;
        }

        $taxes = $this->sumItemsByTaxRate($cart['content']);

        $profile->setAttributes([
            'taxes' => $taxes,
        ]);
    }

    /**
     * @return string
     */
    public function getToolbarTemplate()
    {
        return '@Toolbar/toolbar/cart.tpl';
    }

    /**
     * @param array $items
     * @param float $total
     *
     * @return array
     */
    private function sumItemsByTaxRate(array $items)
    {
        $sums = [];
        $total = 0;

        foreach ($items as $item) {
            if (!empty($item['modus'])) {
                continue;
            }

            $total += $this->makeNumeric($item['amount']);

            if (isset($item['proportion'])) {
                foreach ($item['proportion'] as $proportion) {
                    $key = (string) $proportion['tax_rate'];

                    if (array_key_exists($key, $sums)) {
                        $taxPrice = $sums[$key];
                    } else {
                        $taxPrice = 0;
                    }
                    $taxPrice += $this->makeNumeric($proportion['amount']);

                    $sums[$key] = $taxPrice;
                }
            } else {
                $key = (string) $item['tax_rate'];

                if (array_key_exists($key, $sums)) {
                    $taxPrice = $sums[$key];
                } else {
                    $taxPrice = 0;
                }
                $taxPrice += $this->makeNumeric($item['amount']);

                $sums[$key] = $taxPrice;
            }
        }

        foreach ($sums as $taxRate => &$sum) {
            $tax = new Tax();
            $tax->setTaxRate($taxRate);
            $tax->setSumOfCart($sum);
            $tax->setProportionOfCart($sum / $total * 100);
            $sum = $tax;
        }

        return $sums;
    }

    private function makeNumeric($num)
    {
        return (float) str_replace(',', '.', $num);
    }
}
