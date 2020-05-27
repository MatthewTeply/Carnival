<?php

namespace Carnival\Admin\Controller\Files;

use Carnival\Admin\Core\Admin\AdminController;
use Exception;
use Lampion\FileSystem\FileSystem;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Language\Translator;
use Lampion\Http\Url;

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
        $this->user       = unserialize(LampionSession::get('user'));

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

        $dirs = $this->ls['dirs'];

        if(ltrim($this->dir, '/') != '') {
            $previousDir = explode('/', $_GET['dir']);
            
            unset($previousDir[sizeof($previousDir) - 1]);

            $previousDir = implode('/', $previousDir);

            array_unshift($dirs, [
                'icon'         => '<i class="fas fa-arrow-left"></i>',
                'filename'     => $this->translator->read('global')->get('Back'),
                'relativePath' => $previousDir,
                'isBack'       => true
            ]);
        }

        $files = $this->ls['files'];

        foreach($files as $key => $file) {
            $files[$key]['isImg'] = in_array($file['extension'], $imgExts);
        }

        $breadcrumbs    = [];
        $breadcrumbPath = Url::link('FileManager') . '?dir=';

        if(isset($_GET['dir'])) {
            foreach(explode('/', ltrim($_GET['dir'], '/')) as $key => $breadcrumb) {
                $breadcrumbPath .= '/' . $breadcrumb;
    
                $breadcrumbs[$key] = [
                    'name' => $breadcrumb,
                    'path' => $breadcrumbPath
                ];
            }
        }

        $this->renderTemplate($this->view->load('admin/files/manager', [
            'header'      => $this->header,
            'nav'         => $this->nav,
            'footer'      => $this->footer,
            'title'       => $this->title,
            'description' => $this->description,
            'dirs'        => $dirs,
            'files'       => $files,
            'empty'       => ((sizeof($dirs) == 1 && $dirs[0]['isBack']) && empty($files)) ? true : false,
            'currentDir'  => $_GET['dir'] ?? null,
            'user'        => $this->user,
            'breadcrumbs' => !empty($breadcrumbs[0]['name']) ? $breadcrumbs : null
        ]));
    }

    public function deleteGet() {
        $previousDir = explode('/', $_GET['path']);
            
        unset($previousDir[sizeof($previousDir) - 1]);

        $previousDir = implode('/', $previousDir);

        try {
            $this->fs->rm($_GET['path'], true);
        }

        catch(Exception $e) {
            $this->response->json([
                'error' => $e->getMessage()
            ]);
            exit();
        }

        if(!$this->ajax) {
            Url::redirect('FileManager', [
                'success' => 'delete',
                'dir'     => $previousDir
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link('FileManager', [
                    'success' => 'delete',
                    'dir'     => $previousDir
                ]),
                'success' => 'File deleted successfully!'
            ]);
        }
    }

    public function createDirPost() {
        $this->fs->mkdir($_POST['path']);

        if(!$this->ajax) {
            Url::redirect('FileManager', [
                'success' => 'newDir',
                'dir'     => $_POST['currentDir']
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link('FileManager', [
                    'success' => 'newDir',
                    'dir'     => $_POST['currentDir']
                ])
            ]);
        }
    }

    public function movePost() {
        $from = $_POST['from'];
        $to   = $_POST['to'];

        if($this->fs->mv($from, $to)) {
            $this->response->json([
                'success' => $this->translator->read('files/manager')->get('File moved successfuly!'),
                'href'    => Url::link('FileManager', [
                    'dir' => $_POST['currentDir']
                ])
            ]);
        }

        else {
            $this->response->json([
                'fail' => 'move',
            ]); 
        }
    }

}