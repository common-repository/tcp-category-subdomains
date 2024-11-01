<?php
defined('ABSPATH') or exit;

class TCP_category_subdomains_options extends TCP_category_subdomains_options_base {
    private $category_slug;
    private $category_base;
    private $COOKIE_SUBDOMAIN_REFERER = "wp_subdomain_referer";
    private $COOKIE_HOST_DOMAIN = '';

    /**
     * texonomy query var
     * @var string
     */
    public $query_var;
    public $rules;

    public function __construct() {
        parent::__construct();

        $this->rules = array ();
        $this->query_var = 'product_cat';
        $url = getenv('HTTP_HOST');
        $domain = explode(".", $url);
        $this->category_slug = $domain[0];
        $wc_options = get_option('woocommerce_permalinks');
        $this->category_base = $wc_options['category_base'];
        $url_parts = parse_url(site_url());
        $host_domain = preg_replace('/^www\./', '', $url_parts['host']);
        $this->COOKIE_HOST_DOMAIN = $host_domain;
        $this->add_actions();
        $this->add_filters();
    }

    /**
     * hook wordpress init action & flush rewite rules
     */
    public function add_actions() {
        add_action('init', array (&$this, 'flush_rewite_rules'), 2);
        if (!is_admin()) {
            add_action('request', [$this, 'wp_redirect_subdomain_category']);
            add_action('wp', [$this, 'wp_redirect_new_link']);
        }
    }

    /**
     * hook requied filters
     */
    public function add_filters() {
        // get product category link in menu
        add_filter('nav_menu_link_attributes', array (&$this, 'post_product_category_link'), 10, 4);
        // change pagination
        add_filter('paginate_links', [&$this, 'pagination_link'], 1);
        //get all post category link in page
        add_filter('category_link', array (&$this, 'category_link'), 10, 3);
        // get all post link in the page
        add_filter('post_link', array (&$this, 'post_type_link'), 10, 2);
        add_filter('post_type_link', array (&$this, 'post_type_link'), 10, 2);
        add_filter('option_rewrite_rules', array (&$this, 'category_rewrite_rules'));
        add_filter('wp_safe_redirect_fallback', array (&$this, 'get_redirect'));
    }

    /**
     * flush rewite rules
     */
    public function flush_rewite_rules() {
        if (!is_admin()) {
            if (function_exists('set_transient')) {
                set_transient('rewrite_rules', "");
                update_option('rewrite_rules', "");
            } else {
                update_option('rewrite_rules', "");
            }
        }
    }

    /**
     * - only change redirect url when post password fallback
     * @param type $url
     * @return url
     */
    public function get_redirect($url) {
        $params = explode('&', $_SERVER['QUERY_STRING']);
        foreach ($params as $p) {
            $query = explode('=', $p);
            if ($query[0] && $query[0] == "action" && $query[1] && isset($_COOKIE[$this->COOKIE_SUBDOMAIN_REFERER])) {
                $url = $_COOKIE[$this->COOKIE_SUBDOMAIN_REFERER] ?: '';
                wp_redirect($url, 301);
                exit();
            }
        }
        return $url;
    }

    /**
     * redirect to category page when subdomain is category eg: https://{category}.domain.com/
     * @param type $request
     * @return type
     */
    public function wp_redirect_subdomain_category($request) {
        $r_query = new WP_Query();
        $r_query->parse_query($request);
        $page_slug = $this->current_subdomain_is_category();

        if (!$r_query->is_category && $page_slug) {
            $request[$this->query_var == "category" ? 'category_name' : $this->query_var] = $this->category_slug;
            $request['t'] = time();
        }
        return $request;
    }

    // redirect to new subdomain link if set categories is set inside page
    public function wp_redirect_new_link() {
        global $wp;
        $redirect = false;
       
        // add redirect cookie when page is password protected
        $p_post = get_post(get_queried_object_id());
        if ((is_singular('product') || is_singular('post') || is_category())) {
            if (post_password_required() || is_category()) {
                setcookie($this->COOKIE_SUBDOMAIN_REFERER, sprintf(
                                '%s://%s%s', isset($_SERVER['HTTPS']) ? 'https' : 'http', $_SERVER['HTTP_HOST'], is_category() ? '' : '/' . $_SERVER['REQUEST_URI']
                        ), time() + 3600, COOKIEPATH, $this->COOKIE_HOST_DOMAIN, false);
            } else if (isset($_COOKIE[$this->COOKIE_SUBDOMAIN_REFERER])) {
                unset($_COOKIE[$this->COOKIE_SUBDOMAIN_REFERER]);
                setcookie($this->COOKIE_SUBDOMAIN_REFERER, null, strtotime('-1 day'), COOKIEPATH, $this->COOKIE_HOST_DOMAIN, false);
            }
        }

        $page_slug = $this->current_subdomain_is_category();
        // check if product or post page
        if (!$page_slug) {
            $terms = [];
            $selected_option = [];
            if (is_singular('product') || is_singular('post')) {
                if (empty($p_post)) {
                    $p_post = get_post(get_queried_object_id());
                }
                $terms = $this->getTerm($p_post);
                $selected_option = $this->getOptions($p_post);
            } else if (is_product_category() || is_category()) {
                $temp_post = get_queried_object();
                $temp_post->post_type = is_product_category() ? "product" : "post";
                $terms[] = $temp_post;
                $selected_option = $this->getOptions($temp_post);
            }

            if ($terms && $selected_option) {
                $filteredTerm = $this->filterParentCat((array) $terms, $selected_option);
                if (isset($filteredTerm) && is_object($filteredTerm) && $filteredTerm->slug) {
                    $redirect = $this->get_subdomain_link($filteredTerm->slug, (is_product_category() || is_category()) ? "" : home_url($wp->request));
                }
            }
        }

        if (!$redirect && ((!$page_slug && $this->category_slug != $this->get_home_url_subdomain()))) {
            $redirect = $this->get_subdomain_link($this->get_home_url_subdomain(), home_url($wp->request));
        }

        if ($redirect) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $redirect);
            exit();
        }
    }

    private function get_home_url_subdomain() {
        $home_domain = explode(".", home_url());
        $http_domain = explode("//", $home_domain[0]);
        return $http_domain[1];
    }


    /**
     * Game changer
     * Replace rewrite rules if category page
     * @param array $rules wordpress genrated rewrite rules
     * @return array Final rewrite rules
     */
    public function rewrite_rules_array($rules) {
        if ($this->current_subdomain_is_category()) {
            $rules = $this->category_rewrite_rules($this->rules);
        }
        return $rules;
    }

    /** Change Rules
     * Replace rewrite rules if subdomain is category
     * @param array $rules wordpress from get_option('rewrite_rules');
     * @return array Final rewrite rules
     */
    function category_rewrite_rules($rules = array ()) {
        if (is_array($rules)) {
            $rules["feed/(feed|rdf|rss|rss2|atom)/?$"] = "index.php?" . $this->query_var . "=" . $this->category_slug . "&feed=\$matches[1]";
            $rules["(feed|rdf|rss|rss2|atom)/?$"] = "index.php?" . $this->query_var . "=" . $this->category_slug . "&feed=\$matches[1]";
            $rules["page/?([0-9]{1,})/?$"] = "index.php?" . $this->query_var . "=" . $this->category_slug . "&paged=\$matches[1]";
            $rules["$"] = "index.php?" . $this->query_var . "=" . $this->category_slug;
        }
        return $rules;
    }

    /**
     * - get and modify pagination link
     * @param type $link
     * @return type
     */
    public function pagination_link($link) {
        $link = ($this->current_subdomain_is_category()) ? $this->get_subdomain_link($this->category_slug, $link) : $link;
        return $link;
    }

    public function category_link($category_link, $category_id) {
        $selected = $this->options['wc_subdomain_post_cat'] ?: [];
        $temp_path = array_filter(explode("/", $category_link));
        $cat = end($temp_path);
        return in_array($category_id, $selected) ? $this->get_subdomain_link($cat) : $category_link;
    }

    public function post_product_category_link($atts, $item, $args, $depth) {
        if (strpos($atts['href'], $this->category_base) !== false) {
            $temp_path = array_filter(explode("/", $atts['href']));
            $cat = end($temp_path);
            $cat_term = get_term_by('slug', $cat, 'product_cat');
            $selected = $this->options['wc_subdomain_cat'] ?: [];

            $atts['href'] = in_array($cat_term->term_id, $selected) ? $this->get_subdomain_link($cat) : $atts['href'];
        }

        return $atts;
    }

    // rewrite post/product page link
    public function post_type_link($post_link, $post) {
        $terms = $this->getTerm($post);
        $selected_option = $this->getOptions($post);
        if (!isset($terms->errors)) {
            $filteredTerm = $this->filterParentCat($terms, $selected_option);
            if (isset($filteredTerm) && is_object($filteredTerm) && $filteredTerm->slug) {
                return $this->get_subdomain_link($filteredTerm->slug, $post_link);
            }
        }

        return $post_link;
    }

    public function getOptions($post) {
        $selected = null;
        if ($post->post_type == 'product' && isset($this->options['wc_subdomain_cat']) && is_array($this->options['wc_subdomain_cat'])) {
            $selected = $this->options['wc_subdomain_cat'];
        } else if ($post->post_type == 'post' && isset($this->options['wc_subdomain_post_cat']) && is_array($this->options['wc_subdomain_post_cat'])) {
            $selected = $this->options['wc_subdomain_post_cat'];
        }

        return $selected;
    }

    public function getTerm($post) {
        $terms = null;
        if ($post->post_type == 'product') {
            $terms = get_the_terms($post->ID, 'product_cat');
        } else if ($post->post_type == 'post') {
            $terms = get_the_terms($post->ID, 'category');
        }

        return $terms;
    }

    //filter product categories by current selected category domaim
    public function filterParentCat($arr, $selected_option) {
        $result = array ();
        $existed_term = array_filter($arr, function($term) use ($selected_option) {
            return (isset($term) && isset($selected_option) && is_array($selected_option) && in_array($term->term_id, $selected_option));
        });
        if (sizeof($existed_term) > 0) {
            $parentArr = array_filter($existed_term, function($item) {
                return $item->parent == 0;
            });

            if (sizeOf($parentArr) > 0) {
                $result = array_reduce($parentArr, function($a, $b) {
                    return $a ? ($a->term_id < $b->term_id ? $a : $b) : $b;
                });
            } else {
                $result = array_reduce($existed_term, function($a, $b) {
                    return $a ? ($a->term_id < $b->term_id ? $a : $b) : $b;
                });
            }
        }
        return $result;
    }

    public function current_subdomain_is_category() {
        // return false if not a category
        // return get_category_by_slug($this->category_slug);
        $cat_term = get_term_by('slug', $this->category_slug, 'product_cat') ?: (get_term_by('slug', $this->category_slug, 'category') ?: []);
        if (!$cat_term) {
            return [];
        }

        $this->query_var = $cat_term->taxonomy ?: 'category';
        $cat_term->post_type = $this->query_var == 'product_cat' ? 'product' : 'post';
        $selected_option = $this->getOptions($cat_term);


        return $selected_option ? (in_array($cat_term->term_id, $selected_option) ? $cat_term : []) : [];
    }

    public function get_subdomain_link($category_slug, $site_url = '') {

        if (empty($site_url)) {
            $site_url = home_url();
        }

        $link = str_replace('www.', '', $site_url);
        $link = str_replace('http://', 'http://' . $category_slug . '.', $link);
        $link = str_replace('https://', 'https://' . $category_slug . '.', $link);

        return $link;
    }
}

new TCP_category_subdomains_options();
