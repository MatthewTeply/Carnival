<?php

namespace Carnival\Admin\Controller\Settings;

use Carnival\Admin\Core\Controller;
use Lampion\Config\Registry;
use Lampion\FileSystem\Path;
use Lampion\Http\Url;

class GeneralController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function listGet() {
        $settingsConfig = json_decode(file_get_contents(Path::get('config/carnival/admin/settings/general.json')));
        $settings       = $this->configureSettings($settingsConfig);

        $this->renderTemplate($this->view->load('admin/settings/general', [
            'settings'     => $settings,
            'header'       => $this->header,
            'nav'          => $this->nav,
            'footer'       => $this->footer
        ]));
    }

    private function configureSettings($settings) {
        $settingsReturn = [];

        foreach($settings as $key => $setting) {
            if(isset($setting->children)) {
                $settingsReturn[] = $setting;

                $this->configureSettings($setting->children);
            }

            else {
                if(isset($setting->field)) {
                    $setting->field = $this->view->load('admin/settings/fields/' . $setting->field, [
                        'setting' => $setting,
                        'name'    => $key
                    ]);
                }
    
                $settingsReturn[] = $setting;
            }
        }

        return $settingsReturn;
    }

    public function savePost() {
        $settings = $this->request->input('settings');

        foreach($settings as $key => $value) {
            Registry::set($key, $value);
        }

        if(!$this->request->isAjax()) {
            Url::redirect('GeneralSettings', [
                'success' => 'save'
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link('GeneralSettings', [
                    'success' => 'save'
                ])
            ]);
        }
    }
}