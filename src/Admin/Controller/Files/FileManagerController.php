<?php

namespace Carnival\Admin\Controller\Files;

use Carnival\Admin\Core\Admin\AdminController;
use Exception;
use Lampion\FileSystem\FileSystem;
use Lampion\Http\Request;
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

        $breadcrumbs            = [];
        $breadcrumbFullPath     = Url::link('FileManager') . '?dir=';
        $breadcrumbRelativePath = '';

        if(isset($_GET['dir'])) {
            foreach(explode('/', ltrim($_GET['dir'], '/')) as $key => $breadcrumb) {
                $breadcrumbFullPath     .= '/' . $breadcrumb;
                $breadcrumbRelativePath .= '/' . $breadcrumb;
    
                $breadcrumbs[$key] = [
                    'name'         => $breadcrumb,
                    'fullPath'     => $breadcrumbFullPath,
                    'relativePath' => $breadcrumbRelativePath
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
            'empty'       => @((sizeof($dirs) == 1 && $dirs[0]['isBack']) && empty($files)) ? true : false,
            'currentDir'  => $_GET['dir'] ?? null,
            'user'        => $this->user,
            'breadcrumbs' => !empty($breadcrumbs[0]['name']) ? $breadcrumbs : null,
            'popup'       => isset($_GET['popup']),
            'ajax'        => Request::isAjax()
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
        $this->fs->mkdir($_POST['currentDir'] . '/' . $_POST['dirName']);

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

    public function uploadPost() {
        $files      = [];
        $currentDir = $_POST['currentDir']; 

        for($i = 0; $i < sizeof($_FILES['files']['name']); $i++) {
            $files[$i]['name']     = $_FILES['files']['name'][$i];
            $files[$i]['type']     = $_FILES['files']['type'][$i];
            $files[$i]['tmp_name'] = $_FILES['files']['tmp_name'][$i];
            $files[$i]['error']    = $_FILES['files']['error'][$i];
            $files[$i]['size']     = $_FILES['files']['size'][$i];
        }

        foreach($files as $file) {
            $this->fs->upload($file, $currentDir . '/');
        }

        $getParams = [
            'dir' => $currentDir
        ];

        if($_POST['popup'] != 0) {
            $getParams['popup'] = '';
        }

        $this->response->json([
            'href' => Url::link('FileManager', $getParams),
            'success' => $this->translator->read('files/manager')->get('File(s) upload successfully!')
        ]);
    }

}