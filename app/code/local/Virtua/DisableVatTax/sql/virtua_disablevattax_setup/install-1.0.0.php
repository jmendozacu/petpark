<?php

/**
 * Adding new attribute to customer which holds result of vat number validation.
 */
$installer = $this;

$installer->startSetup();

$installer->addAttribute(
    'customer', 'is_vat_id_valid',
    [
        'label'   => 'Is vat id valid',
        'visible' => 1,
        'required'=> 0,
        'default' => 0,
    ]
);

$installer->endSetup();