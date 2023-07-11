<?php

namespace fostercommerce\variantmanager;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PetiteVueAssetBundle extends AssetBundle
{
    public function init()
    {

        $this->sourcePath = '@fostercommerce/commercereports/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'https://unpkg.com/petite-vue@0.4.1/dist/petite-vue.iife.js'
        ];

        parent::init();

    }

}