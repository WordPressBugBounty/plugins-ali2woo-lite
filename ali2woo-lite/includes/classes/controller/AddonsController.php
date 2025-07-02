<?php
/**
 * Description of AddonsController
 *
 * @author Ali2Woo Team
 * 
 * @autoload: a2wl_admin_init
 * 
 */

namespace AliNext_Lite;;

use Pages;

class AddonsController extends AbstractAdminPage {
    private $update_period = 3600; //60*60*1;
    private $addons;
    
    public function __construct() {
        $this->addons = get_option('a2wl_addons_data', array());
        if(empty($this->addons['addons'])){
            $this->addons['addons'] = array();
        }
        $new_addons_cnt = is_admin()?$this->get_new_addons_count():0;

        $menuTitle = Pages::getLabel(Pages::ADDONS) .
            ($new_addons_cnt ? ' <span class="update-plugins count-' .
                $new_addons_cnt . '"><span class="plugin-count">' .
                $new_addons_cnt . '</span></span>' : '');

        parent::__construct(
            Pages::getLabel(Pages::ADDONS),
            $menuTitle,
            Capability::pluginAccess(),
            Pages::ADDONS,
            100
        );

        if (empty($this->addons['next_update']) || $this->addons['next_update'] < time()) {
            $request = a2wl_remote_get(get_setting('api_endpoint').'addons.php');
            if (!is_wp_error($request) && intval($request['response']['code']) == 200) {
                $this->addons['addons'] = json_decode($request['body'], true);
            }
            $this->addons['next_update'] = time() + $this->update_period;
            update_option('a2wl_addons_data', $this->addons, 'no');
        }
    }

    public function render($params = []): void
    {
        if (!PageGuardHelper::canAccessPage(Pages::ADDONS)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        $this->set_viewed_addons();
        $this->model_put('addons', $this->addons);
        $this->include_view('addons.php');
    }
    
    private function get_new_addons_count() {
        if(empty($this->addons['viewed_addons'])){
            return empty($this->addons['addons'])?0:count($this->addons['addons']);
        }else{
            $viewed_cnt = 0;
            $addons = !empty($this->addons['addons']) ? $this->addons['addons'] : array();
            foreach ($addons as $addon) {
                if (in_array($addon['id'], $this->addons['viewed_addons'])) {
                    $viewed_cnt++;
                }
            }
            return count($addons) - $viewed_cnt;
        }
    }
    
    private function set_viewed_addons() {
        $this->addons['viewed_addons'] = array();
        foreach ($this->addons['addons'] as $addon) {
            $this->addons['viewed_addons'][]=$addon['id'];
        }
        update_option('a2wl_addons_data', $this->addons, 'no');
    }
}
