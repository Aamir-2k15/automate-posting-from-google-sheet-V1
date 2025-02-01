<?php
/* * * *
 * Class: Custom_Admin_Page
 * Version: 1
 * Date: 18 July, 2024
 * Description: Creates custom admin pages and subpages in the admin area.
 * * * * */

class CustomAdminPage {
    private $page_title;
    private $menu_title;
    private $capability;
    private $menu_slug;
    private $callback;
    private $subpages = [];

    public function __construct($args) {
        $this->page_title = $args['page_title'];
        $this->menu_title = $args['menu_title'];
        $this->capability = $args['capability'];
        $this->menu_slug = $args['menu_slug'];
        $this->callback = $args['callback'];

        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu() {
        add_menu_page(
            $this->page_title,
            $this->menu_title,
            $this->capability,
            $this->menu_slug,
            [$this, 'render_page']
        );

        foreach ($this->subpages as $subpage) {
            add_submenu_page(
                $this->menu_slug,
                $subpage['title'],
                $subpage['title'],
                $this->capability,
                $subpage['slug'],
                $subpage['callback']
            );
        }
    }

    public function render_page() {
        if (is_callable($this->callback)) {
            call_user_func($this->callback);
        } else {
            echo '<p>Callback function is not callable.</p>';
        }
    }

    public function add_subpage($title, $slug, $callback) {
        $this->subpages[] = [
            'title' => $title,
            'slug' => $slug,
            'callback' => $callback,
        ];
    }
}