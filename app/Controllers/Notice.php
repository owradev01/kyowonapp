<?php

namespace App\Controllers;

use \Hermawan\DataTables\DataTable;
use CodeIgniter\Model;
use CodeIgniter\Files\File;

class Notice extends BaseController
{
    public $pagename = '알림장';
    public $pn = 'notice';

    protected $data;
    protected $meal;
    protected $authinfo;
    protected $user_id;
    protected $userinfo;
    protected $is_teacher;
    protected $year;
    protected $class_list;
    protected $stdInfo;
    protected $limit = 1;
    protected $aca_id;

    public function __construct()
    {
        $session = session();
        $this->user_id = $session->get('_user_id');
        $this->authinfo = new \App\Models\AuthorInfo($this->user_id);
        $this->authinfo->year = $session->get("year");
        $this->userinfo = $this->authinfo->info();
        $this->is_teacher = $this->authinfo->is_teacher();
        $this->year = $session->get("year");

        $students = new \App\Models\Students();
        $params = [
            'userid' => $this->user_id,
            'aca_id' => $this->userinfo->ACA_ID,
            'is_teacher' => $this->is_teacher === true ? "Y" : "N",
            'year' => $this->year
        ];

        $this->aca_id = $this->userinfo->ACA_ID;
        if ($this->is_teacher !== true) {
            $this->stdInfo = $this->authinfo->stdInfo($session->get('_std_id'));
            $params['std_id'] = $session->get("_std_id");
            $this->aca_id = $this->stdInfo['ACA_ID'];
        }

        $this->class_list = $students->getClassListFromTeacher($params);  // 학원 리스트


    }

    public function index(){
        $this->list();
    }

    public function list()
    {
        // return view('welcome_message');

        $noticelist = [];
        
        $data = [
            'header' => ['title'=> $this->pagename , 'pn' => $this->pn],
            'list' => $noticelist,
        ];
        return $this->template('notice/notice', $data , 'sub');

    }

    public function proc($p = null){
        
        $content = trim(file_get_contents("php://input"));
        $this->data = json_decode($content, true);


        if ($content == "" && ( $_REQUEST ) ){
            $this->data = $_REQUEST;
        }

        return $this->{$this->data['action']}();
    }

    public function getStudentfromclasscd(){

        $params = [
                'class_cd' => $this->data['class_cd']
        ];

        $students = new \App\Models\Students();
        $student_list = $students->getStudentfromClass($params);  // 학원 리스트

        echo json_encode( $student_list );
    }
    public function getlists(){

        $session = session();
        $params = [
            'userid' => $this->data['USER_ID'],
            'aca_id' => $this->aca_id,
            'is_teacher' => $this->data['is_teacher'] === true ? "Y" : "N",
            'year' => $this->data['year']
        ];

        $params['search'] = $this->data['search'] == '' ? '' : $this->data['search'];
        $params['current'] = $this->data['page'];
        $params['perPage'] = $this->data['perPage'];

        if ( $this->data['is_teacher'] !== true ) {
            $params['std_id'] = $session->get('_std_id');
        }
        
        $noticemodel = new \App\Models\Notice();
        $list = $noticemodel->lists($params);
        $data = $list;

        echo json_encode( $data );
        die();
        // return 
    }

    public function detail($noti_seq){

        $noticemodel = new \App\Models\Notice();
        $detail = $noticemodel->detail($noti_seq);
        
        if ( $detail['data'] == null ){
            return redirect()->to('/notice');
        }

        $files = $noticemodel->get_noti_apnd_file($noti_seq);
        
        $f = [];
        foreach ($files['data'] as $file){
            $filenameext = strtolower($file->FILE_EXT);
            if ( in_array( $filenameext , array( 'jpg', 'jpeg' , 'gif' , 'png' , 'bmp' , 'webp', 'mp4' , 'pdf' ) ) ){
                $f['image'][] = $file;
            } else {
                $f['file'][] = $file;
            }
        }
        if ( $this->is_teacher == false ){
            $session = session();
            $noticemodel->_read_noti_update($noti_seq , $session->get("_std_id") , $this->user_id , date("Y-m-d H:i:s") );
        }

        $data = [
            'header' => ['title'=> $this->pagename , 'pn' => $this->pn],
            'data' => [
                'detail' => $detail,
                'file' => $f
            ],
            'noti_seq' => $noti_seq
        ];
        return $this->template('notice/detail', $data , 'sub');
    }

    public function getdetail(){
        
        $params = [
            'userid' => $this->data['USER_ID'],
            'aca_id' => $this->data['ACA_ID'],
            'is_teacher' => $this->data['is_teacher'] === true ? "Y" : "N",
            'year' => $this->data['year']
        ];
        $params['noti_seq'] = $this->data['noti_seq'];

        $noticemodel = new \App\Models\Notice();
        $detail = $noticemodel->detail($params);

        $files = $noticemodel->get_noti_apnd_file($params['noti_seq']);

        $f = [];
        foreach ($files['data'] as $file){
            $filenameext = strtolower($file->FILE_EXT);
            if ( in_array( $filenameext , array( 'jpg', 'jpeg' , 'gif' , 'png' , 'bmp' , 'webp', 'mp4' ) ) ){
                $f['image'][] = $file;
            } else {
                $f['file'][] = $file;
            }
        }

        $data = [
                'data' => $detail,
                'file' => $f,
                'noti_seq' => $params['noti_seq']
        ];

        echo json_encode($data);
        die();

    }

    public function write(){
        $data = [
            'header' => ['title'=> $this->pagename , 'pn' => $this->pn],
        ];
        return $this->template('notice/write', $data , 'sub');
    }

    public function getwrite(){
        
        $params = [
            'userid' => $this->data['USER_ID'],
            'aca_id' => $this->data['ACA_ID'],
            'is_teacher' => $this->data['is_teacher'] === true ? "Y" : "N",
            'year' => $this->data['year']
        ];

        // $students = new \App\Models\Students();
        // $class_list = $students->getClassListFromTeacher($params);  // 학원 리스트

        $data = [
            'classList' => $this->class_list
        ];
        echo json_encode( $data );
        die();
    }

    public function writeProc(){

        $noticemodel = new \App\Models\Notice();
        $students = new \App\Models\Students();
        $pushmessage = new \App\Models\PushMessage();

        $std_id = is_array( $this->data['STD_ID'] ) ? $this->data['STD_ID'] : (array)$this->data['STD_ID'] ;

        date_default_timezone_set('ASIA/SEOUL');
        $classinfo = $students->getClassInfofromClassCd($this->data['selctClass']);
        $params = [
            'ACA_ID' => $this->data['ACA_ID'],
            'CLASS_CD' => $this->data['selctClass'],
            'NOTI_TP' => '01',
            'SEND_TP' => '02',
            'TITLE' => $this->data['noteTitle'],
            'CNTS' => str_nl2br($this->data['noteTxt']),
            'ENT_USER_ID' => $this->data['USER_ID'],
            'ENT_DTTM' => date("Y-m-d H:i:s"),
            'USE_YN' => 'Y',
            'VIEW_YN' => 'Y'
        ];
        $noti_seq = $noticemodel->_getSeq() ;
        $params['NOTI_SEQ'] = $noti_seq;

        
        $return = $noticemodel->_noti_main_insert($params);
        if ($return == true){
            // 학생 
            foreach ($std_id as $std){
                $subparams = [
                    'NOTI_SEQ' => $noti_seq,
                    'STD_ID' => $std,
                    'SEND_DTTM' => date("Y-m-d H:i:s")
                ];
                $noticemodel->_noti_std_insert($subparams);

                $parant_id = $students->getParentsInfoFromStudents($std);
                $stdinfo = $students->getUserInfo($std);
                
                $pushparams = [
                    'SENDER' => $this->data['USER_ID'], 
                    'USER_ID' => $parant_id, 
                    'ACA_ID' => $this->data['ACA_ID'] , 
                    'TITLE' => '[알림장]' . $classinfo->CLASS_NM . "-" . $stdinfo->USER_NM .'원생의 알림장', 
                    'MESSAGE' => '[알림장]' . $classinfo->CLASS_NM . "-" . $stdinfo->USER_NM .'원생의 알림장 - ' . $this->data['noteTitle'] , 
                    'REQUEST_PATH' => '/notice/' . $noti_seq, 
                    "INSERT_USER_ID" => $this->data['USER_ID'],
                    "INSERT_DTTM" => date("Y-m-d H:i:s")
                ];
                $pushmessage->insert($pushparams);
            }

            // 파일 업로드 
            if ( isset( $this->data['files'] ) && $this->data['files'] != '' ) {
                $files = json_decode( $this->data['files'] , true );

                // var_dump($files);
                foreach ( $files as $file){
                    $fileparams = [
                        'NOTI_SEQ' => $noti_seq,
                        'FILE_TP' => null,
                        'FILE_NM' => $file['FILE_NM'],
                        'FILE_PATH' => $file['FILE_PATH'],
                        'FILE_EXT' => $file['FILE_EXT'],
                        'ENT_DTTM' => date("Y-m-d H:i:s"),
                        'ORIGIN_FILE_NM' => $file['ORIGIN_FILE_NM'],
                        'FILE_SIZE' => $file['FILE_SIZE']
                    ];
                    if ( $thumbnail = mp4tojpg( $file['FILE_PATH'] , $file['FILE_NM'] , $file['FILE_EXT'] ) ) {
                        $fileparams['THUMBNAIL'] = "Y";
                    }
                    $noticemodel->_noti_apnd_file($fileparams);
                }
            }

            return json_encode(['status' => 'success' , 'msg'=>'등록성공' , 'redirect_to' => '/notice/' . $noti_seq ]);
        } else {
            return json_encode(['status' => 'fail' , 'msg'=>'등록실패']);
        }
    }

    public function edit($noti_seq){
        $noticemodel = new \App\Models\Notice();
        $detail = $noticemodel->detail($noti_seq);

        $files = $noticemodel->get_noti_apnd_file($noti_seq);

        $f = [];
        foreach ($files['data'] as $file){
            $filenameext = strtolower($file->FILE_EXT);
            if ( in_array( $filenameext , array( 'jpg', 'jpeg' , 'gif' , 'png' , 'bmp' , 'webp', 'mp4' ) ) ){
                $f['image'][] = $file;
            } else {
                $f['file'][] = $file;
            }
        }

        $data = [
            'header' => ['title'=> $this->pagename , 'pn' => $this->pn],
            'data' => [
                'detail' => $detail,
                'file' => $f,
                'noti_seq' => $noti_seq
                ]
        ];
        return $this->template('notice/edit', $data , 'sub');
    }

    public function editProc(){
        $noticemodel = new \App\Models\Notice();
        date_default_timezone_set('ASIA/SEOUL');

        $params = [
            'NOTI_SEQ' => $this->data['noti_seq'],
            'TITLE' => $this->data['noteTitle'],
            'CNTS' => str_nl2br($this->data['noteTxt']),
            'UPT_DTTM' => date("Y-m_d H:i:s"),
            'UPT_USER_ID' => $this->data['USER_ID'],
        ];

        $return = $noticemodel->_noti_main_update($params);

        // 파일 업로드 
        if ( isset( $this->data['files'] ) && $this->data['files'] != '' ) {
            $files = json_decode( $this->data['files'] , true );

            // var_dump($files);
            foreach ( $files as $file){
                $fileparams = [
                    'NOTI_SEQ' => $params['NOTI_SEQ'],
                    'FILE_TP' => null,
                    'FILE_NM' => $file['FILE_NM'],
                    'FILE_PATH' => $file['FILE_PATH'],
                    'FILE_EXT' => $file['FILE_EXT'],
                    'ENT_DTTM' => date("Y-m-d H:i:s"),
                    'ORIGIN_FILE_NM' => $file['ORIGIN_FILE_NM'],
                    'FILE_SIZE' => $file['FILE_SIZE']
                ];
                if ( $thumbnail = mp4tojpg( $file['FILE_PATH'] , $file['FILE_NM'] , $file['FILE_EXT'] ) ) {
                    $fileparams['THUMBNAIL'] = "Y";
                }
                $noticemodel->_noti_apnd_file($fileparams);
            }
        }

        if ($return){
            return json_encode(['status' => 'success' , 'msg'=>'등록성공' , 'redirect_to' => '/notice/' . $params['NOTI_SEQ'] ]);
        } else {
            return json_encode(['status' => 'fail' , 'msg'=>'등록실패']);
        }

    }

    public function deleteProc(){
        $noticemodel = new \App\Models\Notice();
        date_default_timezone_set('ASIA/SEOUL');

        $session = session();
        
        $detail = $noticemodel->detail($this->data['noti_seq']);

        $UPT_USER_ID = $detail['data']->ENT_USER_ID;

        $params = [
            'NOTI_SEQ' => $this->data['noti_seq'],
            'UPT_DTTM' => date("Y-m_d H:i:s"),
            'UPT_USER_ID' => $UPT_USER_ID,
            'USER_ID' => $session->get('_user_id')
        ];

        $return = $noticemodel->_noti_main_delete($params);

        if ($return){
            return json_encode(['status' => 'success' , 'msg'=>'등록성공' , 'redirect_to' => '/notice/' . $params['NOTI_SEQ'] ]);
        } else {
            return json_encode(['status' => 'fail' , 'msg'=>'등록실패']);
        }
    }
}
