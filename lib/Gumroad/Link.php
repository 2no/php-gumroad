<?php

namespace Gumroad;

/**
 * Gumroad Link
 *
 * @package Gumroad
 * @author  Kazunori Ninomiya <kazunori.ninomiya@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class Link
{
    public $id;
    public $name;
    public $url;
    public $price;
    public $description;
    public $currency;
    public $shortUrl;
    public $views;
    public $previewUrl;
    public $purchases;
    public $balance;

    public function __construct(array $params = array())
    {
        $this->id          = null;
        $this->name        = '';
        $this->url         = '';
        $this->price       = 0;
        $this->description = '';
        $this->currency    = '';
        $this->shortUrl    = '';
        $this->views       = 0;
        $this->previewUrl  = null;
        $this->purchases   = 0;
        $this->balance     = '';

        foreach ($params as $key => $param) {
            if (property_exists($this, $key)) {
                $this->$key = $param;
            }
        }
    }
}
