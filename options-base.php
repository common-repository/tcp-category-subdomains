<?php
defined('ABSPATH') or exit;

class TCP_category_subdomains_options_base {

	protected $options;
	protected $LIMIT = 5;
	protected $IS_SHOWN_ERROR_MSG = true;
	protected $VERIFICATION_ID = "";
	protected $SUBDOMAIN_LICENSE_ERROR = "";
	protected $PREMIUM_LINK = 'http://app.thecartpress.com/plugin/tcp-category-subdomains';
	protected $WC_SUBDOMAIN_POST_CAT = "wc_subdomain_post_cat";
	protected $WC_SUBDOMAIN_PRODUCT_CAT = "wc_subdomain_cat";

	public function __construct() {
		global $tcp_category_subdomains;

		if (class_exists('TCP_Menu') && method_exists('TCP_Menu', 'add_submenu')) {
			TCP_Menu::add_submenu([
				'plugin_id' => 'tcp-category-subdomains',
				'page_title' => __('Settings Admin', 'tcp-subdomains'),
				'menu_title' => __('Category Subdomains', 'tcp-subdomains'),
				'menu_slug' => 'tcp-subdomain-admin',
				'function' => [$this, 'create_admin_page'],
			]);
		} else {
			add_action('admin_menu', array(&$this, 'add_plugin_page'));
		}
		add_action('admin_init', array(&$this, 'page_init'));
		add_action('add_option_wc_subdomain_api_option', array(&$this, 'filter_add_cat'), 10, 2);
		add_filter('pre_update_option_wc_subdomain_api_option', array(&$this, 'filter_update_cat'), 10, 2);

		// add premium action link to plugin page
		$this->options = get_option('wc_subdomain_api_option');
		if ($this->options && $this->isSelectedCatReached()) {
			$SELECTED_PRODUCT_CAT = &$this->options[$this->WC_SUBDOMAIN_PRODUCT_CAT];
			$SELECTED_POST_CAT = &$this->options[$this->WC_SUBDOMAIN_POST_CAT];

			if (sizeof($SELECTED_PRODUCT_CAT) > $this->LIMIT) {
				$SELECTED_PRODUCT_CAT = array_slice($SELECTED_PRODUCT_CAT, 0, $this->LIMIT);
				$SELECTED_POST_CAT = array();
			}

			if (sizeof($SELECTED_POST_CAT) > 0) {
				$remaining_selected = $this->LIMIT - sizeof($SELECTED_PRODUCT_CAT);
				$SELECTED_POST_CAT = array_slice($SELECTED_POST_CAT, 0, $remaining_selected);
			}
		}
		add_filter('plugin_action_links_' . $tcp_category_subdomains->basename, array(&$this, 'wc_subdomains_plugin_pro_links'), 10, 1);
		add_filter('plugin_action_links_' . $tcp_category_subdomains->basename, array(&$this, 'wc_subdomains_plugin_settings_links'));
	}

	public function isSelectedCatReached() {
		$option_size = 0;
		$selected = $this->options;
		if (is_array($selected)) {
			if ($selected[$this->WC_SUBDOMAIN_PRODUCT_CAT] != null) {
				$option_size += sizeof($selected[$this->WC_SUBDOMAIN_PRODUCT_CAT]);
			}
			if ($selected[$this->WC_SUBDOMAIN_POST_CAT] != null) {
				$option_size += sizeof($selected[$this->WC_SUBDOMAIN_POST_CAT]);
			}
		}
		return $option_size > $this->LIMIT;
	}

	public function wc_subdomains_plugin_settings_links($links) {
		$plugin_links = array(
			'<a href="' . esc_url(admin_url('/admin.php?page=tcp-subdomain-admin')) . '">' . __('Settings', 'tcp-subdomains') . '</a>'
		);
		return array_merge($plugin_links, $links);
	}

	public function wc_subdomains_plugin_update_links($links) {
		$plugin_links = [
			'<a href="' . esc_url($this->PREMIUM_LINK) . '" target="_blank">' . __('Update', 'tcp-subdomains') . '</a>'
		];
		return array_merge($plugin_links, $links);
	}

	public function wc_subdomains_plugin_pro_links($links) {
		$plugin_links = [
			'<a href="' . esc_url($this->PREMIUM_LINK) . '" target="_blank">' . __('Premium', 'tcp-subdomains') . '</a>'
		];
		return array_merge($plugin_links, $links);
	}

	public function filter_add_cat($option_name, $option_value) {
		if ($this->isSelectedCatReached() > $this->LIMIT) {
			$this->IS_SHOWN_ERROR_MSG = false;
			update_option($option_name, $option_value, __('yes', 'tcp-subdomains'));
		}
	}

	public function filter_update_cat($new_value, $old_value) {
		if ($this->isSelectedCatReached() <= $this->LIMIT) {
			return $new_value;
		} else if ($this->IS_SHOWN_ERROR_MSG) {
			add_settings_error('wc_excess_cat_error', "", "You should only add/update 5 categories in lite version.");
		}
		$new_value[$this->WC_SUBDOMAIN_PRODUCT_CAT] = array_slice($new_value[$this->WC_SUBDOMAIN_PRODUCT_CAT], 0, $this->LIMIT);
		$this->IS_SHOWN_ERROR_MSG = false;
		return $new_value;
	}

	public function add_plugin_page() {
		// This page will be under "Settings"
		add_submenu_page(
			'thecartpress',
			__('Settings Admin', 'tcp-subdomains'),
			__('Category Subdomains', 'tcp-subdomains'),
			'manage_options',
			'tcp-subdomain-admin',
			array(&$this, 'create_admin_page')
		);
	}

	public function create_admin_page() {
		?>
		<div class="wrap">
			<h2>Product/Post Category Subdomain</h2>
			<h3>Lite version only support up to 5 categories domain. Upgrade to <a href=" <?php echo esc_url($this->PREMIUM_LINK) ?>" target="_blank">PREMIUM</a> to enjoy unlimited domains.</h3>
			<br/>
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('wc_subdomain_option_group');
				do_settings_sections('tcp-subdomain-admin');
				submit_button();
				?>
			</form>
			<script>
				// if checked checkboxes are more then 5 then disable all other checkboxes
				var user_checked = 0;
				var limit = 5;
				var checkbox_disabled = false;
				var checkboxes = document.querySelectorAll(".wrap input[type='checkbox']");
				for (var checkbox of checkboxes) {
					if (checkbox.checked) {
						++user_checked;
					}
					checkbox.addEventListener('change', function () {
						if (this.checked) {
							++user_checked;
						} else {
							--user_checked;
						}
						verifyCheckbox();
					});
				}

				verifyCheckbox();

				function verifyCheckbox() {
					if (user_checked >= limit) {
						disableCheckbox(true);
						checkbox_disabled = true;
					} else if (checkbox_disabled) {
						disableCheckbox(false);
						checkbox_disabled = false;
					}
				}

				function disableCheckbox(disable) {
					var checkboxes = document.querySelectorAll(".wrap input[type='checkbox']");
					for (var checkbox of checkboxes) {
						if (!checkbox.checked) {
							checkbox.disabled = disable;
						}
					}
				}
			</script>
		</div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		global $tcp_category_subdomains;

		register_setting(
			'wc_subdomain_option_group', // Option group
			'wc_subdomain_api_option', // Option name
			array($this, 'sanitize') // Sanitize
		);

		add_settings_section(
			'wc_subdomain_setting_section', // ID
			'', // Title
			array($this, 'print_section_info'), // Callback
			'tcp-subdomain-admin' // Page
		);

		if ($tcp_category_subdomains->is_wc_active) {
			add_settings_field(
				'wc_subdomain_cat', // ID
				__('Select Product Categories', 'tcp-subdomains'), // Title
				array($this, 'subdomain_cat_callback'), // Callback
				'tcp-subdomain-admin', // Page
				'wc_subdomain_setting_section' // Section
			);
		}

		add_settings_field(
			'wc_subdomain_post_cat', // ID
			__('Select Post Categories', 'tcp-subdomains'), // Title
			array($this, 'subdomain_post_cat_callback'), // Callback
			'tcp-subdomain-admin', // Page
			'wc_subdomain_setting_section' // Section
		);
	}

	public function sanitize($input) {
		$input[$this->WC_SUBDOMAIN_PRODUCT_CAT] = $this->tcpsudomain_sanitize_field($_REQUEST['tax_input']['product_cat']); //$_REQUEST['post_category'];
		$input[$this->WC_SUBDOMAIN_POST_CAT] = $this->tcpsudomain_sanitize_field($_REQUEST['post_category']);
		return $input;
	}

	public function print_section_info() {
		print __('Please select one or more categories:', 'tcp-subdomains');
	}

	public function subdomain_cat_callback() {
		$selected = $this->options ?: [];
		$args = [
			'selected_cats' => isset($selected[$this->WC_SUBDOMAIN_PRODUCT_CAT]) ? $selected[$this->WC_SUBDOMAIN_PRODUCT_CAT] : '',
			'checked_ontop' => false,
			'taxonomy' => 'product_cat'
		];
		wp_terms_checklist(0, $args);
	}

	public function subdomain_post_cat_callback() {
		$selected = $this->options ?: [];
		$post_cat_args = [
			'selected_cats' => isset($selected[$this->WC_SUBDOMAIN_POST_CAT]) ? $selected[$this->WC_SUBDOMAIN_POST_CAT] : '',
			'checked_ontop' => false,
			'taxonomy' => 'category'
		];
		wp_terms_checklist(0, $post_cat_args);
	}

	/**
	 * Recursive sanitation for text or array
	 *
	 * @param $array_or_string (array|string)
	 * @since  0.1
	 * @return mixed
	 */
	function tcpsudomain_sanitize_field($array_or_string) {
		if (is_string($array_or_string)) {
			$array_or_string = sanitize_text_field($array_or_string);
		} elseif (is_array($array_or_string)) {
			foreach ($array_or_string as $key => &$value) {
				if (is_array($value)) {
					$value = $this->tcpsudomain_sanitize_field($value);
				} else {
					$value = sanitize_text_field($value);
				}
			}
		}
		return $array_or_string;
	}

}