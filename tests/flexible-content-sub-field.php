<?php

class acf_field {
	public $name, $label, $category, $defaults;
	public function add_field_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['filters'][ $tag ] = $callback;
	}
}

function __( $text, $domain ) { return $text; }
function acf_register_field_type( $class ) {}
function acf_get_loop( $which ) { return $GLOBALS['loop']; }
function acf_search_fields( $id, $fields ) {
	foreach ( array( 'key', 'name', '_name', '__name' ) as $key ) {
		foreach ( $fields as $field ) {
			if ( isset( $field[ $key ] ) && $field[ $key ] === $id ) {
				return $field;
			}
		}
	}
	return false;
}

require __DIR__ . '/../includes/fields/class-acf-field-flexible-content.php';

$handler = new acf_field_flexible_content();
$handler->initialize();
$field = array(
	'key' => 'field_flexible',
	'layouts' => array(
		array( 'name' => 'first', 'sub_fields' => array( array( 'key' => 'field_first', 'name' => 'image' ) ) ),
		array( 'name' => 'second', 'sub_fields' => array( array( 'key' => 'field_second', 'name' => 'image' ), array( 'key' => 'field_nested', 'name' => 'nested' ) ) ),
	),
);
$GLOBALS['loop'] = array( 'field' => $field, 'i' => 0, 'value' => array( array( 'acf_fc_layout' => 'second' ) ) );

if ( ! isset( $GLOBALS['filters']['acf/get_sub_field'] ) ) {
	fwrite( STDERR, "Flexible Content lookup filter is missing\n" );
	exit( 1 );
}
$lookup = $GLOBALS['filters']['acf/get_sub_field'];
$cases = array( 'image' => 'field_second', 'field_second' => 'field_second', 'nested' => 'field_nested', 'field_first' => false, 'missing' => false );
foreach ( $cases as $selector => $expected ) {
	$result = $lookup( false, $selector, $field );
	if ( ( $result['key'] ?? false ) !== $expected ) {
		fwrite( STDERR, "Unexpected result for $selector\n" );
		exit( 1 );
	}
}
$GLOBALS['loop']['value'][0]['acf_fc_layout'] = 'unknown';
if ( $lookup( false, 'image', $field ) !== false ) {
	fwrite( STDERR, "Unknown layout matched a field\n" );
	exit( 1 );
}
$GLOBALS['loop'] = false;
if ( ( $lookup( false, 'field_second', $field )['key'] ?? false ) !== 'field_second' ) {
	fwrite( STDERR, "Lookup without an active loop failed\n" );
	exit( 1 );
}
fwrite( STDOUT, "Flexible Content lookup tests passed\n" );