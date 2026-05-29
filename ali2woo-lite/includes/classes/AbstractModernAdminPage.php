<?php

/* * class
 * Description of AbstractModernAdminPage
 *
 * @author Ali2Woo Team
 *
 * @position: 2
 */
namespace AliNext_Lite;;

abstract class AbstractModernAdminPage extends AbstractController
{
    protected string $page_title;
    protected string $menu_title;
    protected string $capability;
    protected string $menu_slug;

    protected array $styles = [];
    protected array $scripts = [];

    public function __construct(string $page_title, string $menu_title, string $capability, string $menu_slug)
    {
        parent::__construct(A2WL()->plugin_path() . '/view/');

        $this->page_title = $page_title;
        $this->menu_title = $menu_title;
        $this->capability = $capability;
        $this->menu_slug = $menu_slug;

        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }


    public function getPageTitle(): string
    {
        return $this->page_title;
    }

    public function getMenuTitle(): string
    {
        return $this->menu_title;
    }

    public function getCapability(): string
    {
        return $this->capability;
    }

    public function getSlug(): string
    {
        return $this->menu_slug;
    }

    public function add_style($handle, $src): void
    {
        $this->styles[$handle] = $src;
    }

    public function add_script($handle, $src): void
    {
        $this->scripts[$handle] = $src;
    }

    public function enqueue_assets(): void
    {
        if (!$this->is_current_page()) {
            return;
        }

        foreach ($this->styles as $handle => $src) {
            wp_enqueue_style($handle, A2WL()->plugin_url() . $src, [], A2WL()->version);
        }

        foreach ($this->scripts as $handle => $src) {
            wp_enqueue_script($handle, A2WL()->plugin_url() . $src, ['jquery'], A2WL()->version, true);
        }
    }

    protected function is_current_page(): bool
    {
        return isset($_GET['page']) && $_GET['page'] === $this->menu_slug;
    }

    abstract public function render();
}
