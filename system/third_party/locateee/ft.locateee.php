<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'locateee/config.php';

/**
 * Locatee Fieldtype Class for EE2
 *
 * @package LocatEE
 * @author Chris Lock
 * @copyright Copyright (c) 2012 Paramore
 */
class Locateee_ft extends EE_Fieldtype {

	/**
	 * Info that is given to ExpressionEngine
	 * @var array
	 */
	public $info = array(
		'name' => LOCATEEE_LIST_NAME,
		'version' => LOCATEEE_LIST_VER
	);

	/**
	 * Tells EE that our replace_tag handles tag pairs
	 * @var boolean
	 */
	public $has_array_data = true;

	/**
	 * Reference to the EE superglobal
	 * @var object
	 */
	public $EE;

	/**
	 * The current field's ID
	 * @var int
	 */
	public $field_id;

	/**
	 * The current field's short_name
	 * @var string
	 */
	public $field_name;

	/**
	 * An array of field settings from EE
	 * @var array
	 */
	public $settings = array();

	/**
	 * The base URL for the current Google Maps API
	 */
	const GOOGLE_MAPS_API_SRC_BASE = 'http://maps.google.com/maps/api/js?sensor=false&key=';

	function Locateee_ft()
	{
		parent::EE_Fieldtype();

		$this->EE->lang->loadfile('locateee');

		// Prepare Cache
		if (! isset($this->EE->session->cache['locateee']))
			$this->EE->session->cache['locateee'] = array('includes' => array());
		
		$this->cache =& $this->EE->session->cache['locateee'];
	}

	/**
	 * Install the field
	 * @return array Settings
	 */
	function install()
	{
		return array(
			'google_maps_api_key' => ''
		);
	}

	/**
	 * Update the field
	 * @return array Settings
	 */
	function update()
	{
		return array(
			'google_maps_api_key' => ''
		);
	}

	/**
	 * Settings page for fieldtype
	 * @return string Markup for the global settings
	 */
	function display_global_settings()
	{
		$data = array_merge($this->settings, $_POST);
		
		return
			form_label(
				lang('google_maps_api_key'),
				'google_maps_api_key'
			) . 
			form_input(
				'google_maps_api_key',
				$data['google_maps_api_key']
			);
	}

	/**
	 * Save global settings for fieldtype
	 * @return array Settings
	 */
	function save_global_settings()
	{
	    return array_merge($this->settings, $_POST);
	}
	
	/**
	 * Channel field settings
	 * @return void
	 */
	function display_settings($data)
	{	
		$show_country_checked = (isset($data['show_country']))
			? $data['show_country'] 
			: false;
		$show_geolocate_checked = (isset($data['show_geolocate']))
			? $data['show_geolocate'] 
			: true;

		$this->EE->table->add_row(
			lang('show_country'),
			form_checkbox(
				'show_country',
				1,
				$show_country_checked
			)
		);
		$this->EE->table->add_row(
			lang('show_geolocate'),
			form_checkbox(
				'show_geolocate',
				1,
				$show_geolocate_checked
			)
		);
	}

	/**
	 * Save channel field page settings
	 * @return array Field settings
	 */
	function save_settings($data)
	{
		return array(
			'show_country' => $this->EE->input->post('show_country'),
			'show_geolocate' => $this->EE->input->post('show_geolocate')
		);
	}

	/**
	 * Publish page input field
	 * @param string $data Data returned by EE
	 * @return string Markup for the input field
	 */
	function display_field($data)
	{
		$this->include_theme_css('styles/locateee.css');
		$this->include_external_js($this->get_google_maps_api_src());
		$this->include_theme_js('scripts/locateee.js');

		return $this->build_field_table(
			$this->get_field_columns(
				$this->process_data($data)
			)
		);
	}

	/**
	 * Add a theme css file and include it in the cache
	 * @author Brandon Kelly, brandon@pixelandtonic.com
	 * @param string $file Name of css file with no path 
	 * @return void
	 */
	private function include_theme_css($file)
	{
		if (in_array($file, $this->cache['includes']))
			return;

		$this->cache['includes'][] = $file;
		$this->EE->cp->add_to_head(
			'<link rel="stylesheet" type="text/css" href="' . $this->theme_url() . $file . '" />'
		);
	}

	/**
	 * Returns the theme url and adds in to the cache
	 * @author Brandon Kelly, brandon@pixelandtonic.com
	 * @return string Path to the themes folder
	 */
	private function theme_url()
	{
		if (! isset($this->cache['theme_url'])) {
			$theme_folder_url = $this->EE->config->item('theme_folder_url');

			if (substr($theme_folder_url, -1) != '/')
				$theme_folder_url .= '/';

			$this->cache['theme_url'] = $theme_folder_url . 'third_party/locateee/';
		}

		return $this->cache['theme_url'];
	}

	/**
	 * Returns the url for Google Maps API with their API key
	 * @return string Path to Google Maps API
	 */
	private function get_google_maps_api_src()
	{
		$google_maps_api_key =
			self::GOOGLE_MAPS_API_SRC_BASE . 
			$this->settings['google_maps_api_key'];

		return $google_maps_api_key;
	}

	/**
	 * Add an external js file 
	 * @param string $file Name of js file with path 
	 * @return void
	 */
	private function include_external_js($file)
	{
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $file . '"></script>');
	}

	/**
	 * Add a theme js file and include it in the cache
	 * @author Brandon Kelly, brandon@pixelandtonic.com
	 * @param string $file Name of js file with no path 
	 * @return void
	 */
	private function include_theme_js($file)
	{
		if (in_array($file, $this->cache['includes']))
			return;
		
		$this->cache['includes'][] = $file;
		$this->EE->cp->add_to_foot('<script type="text/javascript" src="' . $this->theme_url() . $file . '"></script>');
	}

	/**
	 * Add an external js file 
	 * @param string $data Data returned by EE
	 * @return string Markup for inputs in the input field
	 */
	private function get_field_columns($data)
	{
		$columns = array();
		$columns['street'] = array(
			'field' => $this->build_field_input(
				$data,
				lang('street'),
				'street',
				true
			),
			'heading' => lang('street'),
			'is_required' => true,
			'width' => 35
		);
		$columns['city'] = array(
			'field' => $this->build_field_input(
				$data,
				lang('city'),
				'city',
				true
			),
			'heading' => lang('city'),
			'is_required' => true,
			'width' => 16
		);
		$columns['state'] = array(
			'field' => $this->build_field_input(
				$data,
				lang('state'),
				'state',
				true
			),
			'heading' => lang('state'),
			'is_required' => true,
			'width' => 7
		);
		$columns['zip'] = array(
			'field' => $this->build_field_input(
				$data,
				lang('zip'),
				'zip',
				true
			),
			'heading' => lang('zip'),
			'is_required' => true,
			'width' => 10
		);
		$columns['country'] = array(
			'field' => $this->build_field_input(
				$data,
				lang('country'),
				'country',
				true
			),
			'heading' => lang('country'),
			'is_required' => true,
			'width' => 10
		);
		$columns['location'] = array(
			'field' => $this->build_location_button(),
			'heading' => lang('location'),
			'is_button' => true,
			'width' => 10
		);
		$columns['lat'] = array(
			'field' => $this->build_field_input(
				$data,
				lang('lat'),
				'lat'
			),
			'heading' => lang('lat'),
			'width' => 11
		);
		$columns['lng'] = array(
			'field' => $this->build_field_input(
				$data,
				lang('lng'),
				'lng'
			),
			'heading' => lang('lng'),
			'width' => 11
		);

		if (! $this->settings['show_country'])
			unset($columns['country']);

		if (! $this->settings['show_geolocate'])
			unset(
				$columns['location'],
				$columns['lat'],
				$columns['lng']
			);

		return $this->set_column_widths($columns);
	}

	/**
	 * Builds input for field
	 * @param string $data Data returned by EE
	 * @param string $data Label for column
	 * @param string $name Name used for input
	 * @param string $is_required If the field is required
	 * @return string Markup of input
	 */
	private function build_field_input($data, $label, $name, $is_required = false)
	{
		$value = (isset($data[$name]))
			? $data[$name] 
			: null;

		return $this->EE->load->view(
			'form_fields/default',
			array(
				'field_name' => $this->field_name,
				'is_required' => $is_required,
				'label' => $label,
				'name' => $name,
				'value' => $value
			),
			true
		);
	}

	/**
	 * Sets column widths as a percent of the total widths
	 * @param array $columns Field columns.
	 * @return array Field olumns
	 */
	private function set_column_widths($columns)
	{
		$columns_width_total = 0;
		$column_widths = array();

		foreach ($columns as $name => $column) {
			$columns_width_total += $column['width'];
			$column_widths[$name] = $column['width'];
		}

		$column_widths = array_reverse($column_widths);
		$column_itteration = 0;
		$column_count = count($columns);
		$column_width_percent_remainder = 100;

		foreach ($column_widths as $name => $column_width) {
			$column_width_percent = ($column_itteration != $column_count)
				? floor(($column_width / $columns_width_total * 100)) 
				: $column_width_percent;
			$column_width_percent_remainder -= $column_width_percent;
			$column_itteration++;

			$columns[$name]['width'] = $column_width_percent . '%';
		}

		return $columns;
	}

	/**
	 * Builds location button
	 * @return string Markup for location button
	 */
	private function build_location_button()
	{
		return $this->EE->load->view(
			'form_fields/location_button',
			array(
				'button_text' => lang('location_button')
			),
			true
		);
	}

	/**
	 * Replaces html quotes and unserializes the string saved in EE
	 * @param string $data String returned from EE
	 * @return mixed empty If no data saved | array Address fields
	 */
	private function process_data($data)
	{
		if (empty($data))
			return $data;

		$data = str_replace('&quot;', '"', $data);
		$data = @unserialize($data);
		
		return $data;
	}

	/**
	 * Builds table for input field
	 * @return string Markup of entire table
	 */
	private function build_field_table($columns)
	{
		return $this->EE->load->view(
			'publish_table',
			array(
				'columns' => $columns,
				'error_data_later' => lang('error_message_later'),
				'error_data_address' => lang('error_message_address'),
				'field_id' => $this->field_id
			),
			true
		);
	}

	/**
	 * Called by EE to check required fields
	 * @param  array $data The raw form data
	 * @return boolean Is the field not empty
	 */
	function validate($data)
	{
		$is_valid = false;

		foreach($data as $key => $field)
			if (! empty($field))
				$is_valid = true;

		if ($is_valid)
			return true;

		return lang('required');
	}
	
	/**
	 * Called by EE to save the field value
	 * @param  array $data The raw form data
	 * @return string What to store in the EE table
	 */
	function save($data)
	{
		return serialize($data);
	}

	/**
	 * Called by EE to replace the contents of a template tag with the price
	 * @param  string $data String returned from EE
	 * @param  array $params
	 *         string var_prefix Prefix used before template tag
	 * @param  boolean $tagdata
	 * @return string Tag pair if not empty
	 */
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->process_data($data);
		
		if (! is_array($data) OR empty($data))
			return false;
		
		$var_prefix = isset($params['var_prefix'])
			? rtrim($params['var_prefix'], ':') . ':' 
			: null;

		$return_data = array();
		$has_results = false;

		foreach($data as $key => $value) {
			$return_data[$var_prefix . $key] = $value;

			if (! empty($value))
				$has_results = true;
		}

		return ($has_results)
			? $this->EE->TMPL->parse_variables($tagdata, array($return_data))
			: false;
	}
}

/* End of file ft.locateee.php */
/* Location: ./system/expressionengine/third_party/locateee/ft.locateee.php */