<?php
$installer = $this;

$installer->startSetup();

$installer->addAttribute('customer', 'is_vat_id_valid', array(
    'label'        => 'Is vat id valid',
    'visible'      => 1,
    'required'     => 0,
    'default' => 0,
));

$installer->endSetup();