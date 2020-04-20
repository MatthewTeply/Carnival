<?php

namespace Carnival\Admin\Controller\Settings;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Application\Application;
use Lampion\Core\FileSystem;
use Lampion\Debug\Console;
use Lampion\Http\Url;
use Lampion\Language\Translator;
use Lampion\Session\Lampion as LampionSession;

class TranslateController extends AdminController {

    # Public:
    public $fs;
    public $translator;

    public function __construct() {
        parent::__construct();

        $this->fs         = new FileSystem(ROOT . APP . Application::name() . LANG . LampionSession::get('lang') . DIRECTORY_SEPARATOR);
        $this->translator = new Translator(LampionSession::get('lang'));
    }

    public function listGet() {
        $dirRead = $this->fs->ls('');

        $sections = $dirRead['dirs'];

        foreach($sections as $key => $section) {
            $sections[$key]['items'] = $this->fs->ls($section['name'] . '/')['files'];
        
            Console::log($sections[$key]['items']);
        }

        $sections[] = [
            'name'  => null,
            'items' => $dirRead['files']
        ];

        $this->view->render('admin/settings/translate/list', [
            'sections'     => $sections,
            'header'       => $this->header,
            'nav'          => $this->nav,
            'footer'       => $this->footer,
        ]);
    }

    public function editGet() {
        $translationFile = DIRECTORY_SEPARATOR . $_GET['dir'] . DIRECTORY_SEPARATOR . explode('.', $_GET['filename'])[0];

        $itemsObj = $this->translator->read($translationFile)->getAll();
        $items    = [];

        $i = 0;
        foreach($itemsObj as $key => $item) {
            $items[$i]['keyword']     = $key;
            $items[$i]['translation'] = $item;

            $i++;
        }

        $this->view->render('admin/settings/translate/edit', [
            'translationFile' => $translationFile .  '.json',
            'items'           => $items,
            'header'          => $this->header,
            'nav'             => $this->nav,
            'footer'          => $this->footer,
        ]);
    }

    public function editPost() {
        $items = []; 
        
        foreach($_POST['items'] as $item) {
            $items[$item['keyword']] = $item['translation'];
        }

        $this->fs->write($_POST['translationFile'], json_encode($items));

        Url::redirect('translate', [
            'edit' => 'success'
        ]);
    }

    public function newGet() {
        $sections = [];

        foreach($this->translator->getSections() as $section) {
            $sections[] = $section['name'];
        }

        $this->view->render('admin/settings/translate/new', [
            'sections'        => $sections,
            'header'          => $this->header,
            'nav'             => $this->nav,
            'footer'          => $this->footer
        ]);
    }

    public function newPost() {
        $filename = $_POST['filename'];
        $dir      = $_POST['dir'] . DIRECTORY_SEPARATOR;

        $items = []; 
        
        foreach($_POST['items'] as $item) {
            $items[$item['keyword']] = $item['translation'];
        }

        $this->fs->write($dir . $filename . '.json', json_encode($items));

        Url::redirect('translate', [
            'new' => 'success'
        ]);
    }

    public function deleteGet() {
        $code = 'fail';

        if($this->fs->rm($_GET['path'], true)) {
            $code = 'success';
        } 

        Url::redirect('translate', [
            'delete' => $code
        ]);
    }

    public function editSectionPost() {
        $code = 'fail';

        if($this->fs->rename($_POST['from'], $_POST['to'])) {
            $code = 'success';
        }

        Url::redirect('translate', [
            'editSection' => $code
        ]);
    }

}