<?php
function bot_config(): array {
  return [
    // English name
    'FIELD_EN_TABLE' => 'dpl_field_data_field_english_name',
    'FIELD_EN_COL'   => 'field_english_name_value',

    // Scientific name
    'FIELD_SCI_TABLE' => 'dpl_field_data_field_species',
    'FIELD_SCI_COL'   => 'field_species_value',

    // Thai name (keep yours here)
    'FIELD_THAI_TABLE' => 'dpl_field_data_field_thai_name',
    'FIELD_THAI_COL'   => 'field_thai_name_value',

    // Photo count
    'FIELD_PHOTO_TABLE' => 'dpl_field_data_field_photo_count',
    'FIELD_PHOTO_COL'   => 'field_photo_count_count',

    // Order
    'ORDER_FIELD_TABLE' => 'dpl_field_data_field_sci_order',
    'ORDER_TID_COL'     => 'field_sci_order_tid',

    // Family
    'FAMILY_FIELD_TABLE' => 'dpl_field_data_field_sci_family',
    'FAMILY_TID_COL'     => 'field_sci_family_tid',

    // Taxon order (for sorting)
    'TAXON_ORDER_TABLE' => 'dpl_field_data_field_taxon_order',
    'TAXON_ORDER_COL'   => 'field_taxon_order_value',
  ];
}