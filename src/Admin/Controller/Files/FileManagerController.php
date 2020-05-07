<?php

namespace Carnival\Admin\Controller\Files;

use Carnival\Admin\Core\Admin\AdminController;
use Lampion\Core\FileSystem;
use Lampion\Application\Application;
use Lampion\Debug\Console;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Language\Translator;

class FileManagerController extends AdminController {

    # Public:
    public $fs;
    public $translator;
    public $dir;
    public $ls;

    public function __construct() {
        parent::__construct();

        $this->fs         = new FileSystem();
        $this->translator = new Translator(LampionSession::get('lang'));

        $this->dir = $_GET['dir'] ?? '';

        if(strpos($this->dir, '..') !== false) {
            $this->dir = '';
        }

        $this->ls  = $this->fs->ls($this->dir . '/');
    }

    public function listGet() {
        $dirs  = [];
        $files = [];

        $imgExts = [
            'png',
            'jpg',
            'jpeg',
            'svg',
            'gif'
        ];

        foreach($this->ls['dirs'] as $key => $dir) {
            $dirs[$key] = $dir;
        }

        if($this->dir != '') {
            $previousDir = explode('/', $_GET['dir']);
            
            unset($previousDir[sizeof($previousDir) - 1]);

            $previousDir = implode('/', $previousDir);

            array_unshift($dirs, [
                'icon'         => '<i class="fas fa-arrow-left"></i>',
                'name'         => $this->translator->read('global')->get('Back'),
                'relativePath' => $previousDir
            ]);
        }

        foreach($this->ls['files'] as $key => $file) {
            $files[$key] = $file;

            $files[$key]['isImg'] = in_array($file['extension'], $imgExts);
        }

        $this->renderTemplate($this->view->load('admin/files/manager', [
            'header'      => $this->header,
            'nav'         => $this->nav,
            'footer'      => $this->footer,
            'title'       => $this->title,
            'description' => $this->description,
            'dirs'        => $dirs,
            'files'       => $files,
            'currentDir'  => $_GET['dir'] ?? null
        ]));
    }

    public function deleteGet() {
        $this->fs->rm($_GET['path'], true);
    }

}