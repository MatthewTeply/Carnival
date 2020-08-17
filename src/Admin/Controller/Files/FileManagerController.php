<?php

namespace Carnival\Admin\Controller\Files;

use Carnival\Admin\Core\Controller;
use Exception;
use Lampion\FileSystem\FileSystem;
use Lampion\Http\Request;
use Lampion\Session\Lampion as LampionSession;
use Lampion\Language\Translator;
use Lampion\Http\Url;

class FileManagerController extends Controller {

    # Public:
    public $fs;
    public $translator;
    public $dir;
    public $ls;

    public function __construct() {
        parent::__construct();

        $this->fs         = new FileSystem();
        $this->translator = new Translator(LampionSession::get('lang'));

        $this->dir = $this->request->query('dir') ?? '';

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
            $previousDir = explode('/', $this->request->query('dir'));
            
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

        if($this->request->hasQuery('dir')) {
            foreach(explode('/', ltrim($this->request->query('dir'), '/')) as $key => $breadcrumb) {
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
            'currentDir'  => $this->request->query('dir') ?? null,
            'user'        => $this->user,
            'breadcrumbs' => !empty($breadcrumbs[0]['name']) ? $breadcrumbs : null,
            'popup'       => $this->request->hasQuery('popup'),
            'ajax'        => $this->request->isAjax()
        ]));
    }

    public function deleteGet() {
        $previousDir = explode('/', $this->request->query('path'));
            
        unset($previousDir[sizeof($previousDir) - 1]);

        $previousDir = implode('/', $previousDir);

        try {
            $this->fs->rm($this->request->query('path'), true);
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
        $this->fs->mkdir($this->request->input('currentDir') . '/' . $this->request->input('dirName'));

        if(!$this->ajax) {
            Url::redirect('FileManager', [
                'success' => 'newDir',
                'dir'     => $this->request->input('currentDir')
            ]);
        }

        else {
            $this->response->json([
                'href' => Url::link('FileManager', [
                    'success' => 'newDir',
                    'dir'     => $this->request->input('currentDir')
                ])
            ]);
        }
    }

    public function movePost() {
        $from = $this->request->input('from');
        $to   = $this->request->input('to');

        if($this->fs->mv($from, $to)) {
            $this->response->json([
                'success' => $this->translator->read('files/manager')->get('File moved successfuly!'),
                'href'    => Url::link('FileManager', [
                    'dir' => $this->request->input('currentDir')
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
        $currentDir = $this->request->hasInput('currentDir') ? $this->request->input('currentDir') : ''; 

        for($i = 0; $i < sizeof($this->request->file('files')['name']); $i++) {
            $files[$i]['name']     = $this->request->file('files')['name'][$i];
            $files[$i]['type']     = $this->request->file('files')['type'][$i];
            $files[$i]['tmp_name'] = $this->request->file('files')['tmp_name'][$i];
            $files[$i]['error']    = $this->request->file('files')['error'][$i];
            $files[$i]['size']     = $this->request->file('files')['size'][$i];
        }

        foreach($files as $file) {
            $this->fs->upload($file, $currentDir . '/');
        }

        $getParams = [
            'dir' => $currentDir
        ];

        if($this->request->input('popup') != 0) {
            $getParams['popup'] = '';
        }

        $this->response->json([
            'href' => Url::link('FileManager', $getParams),
            'success' => $this->translator->read('files/manager')->get('File(s) upload successfully!')
        ]);
    }

}