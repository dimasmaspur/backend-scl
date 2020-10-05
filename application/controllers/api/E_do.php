<?php

//  header('Access-Control-Allow-Origin: *');
//  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

use Restserver\Libraries\REST_Controller;
defined('BASEPATH') OR exit('No direct script access allowed');


// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
//To Solve File REST_Controller not found
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';
require APPPATH . 'libraries/JWT.php';
use \Firebase\JWT\JWT;
// use \Firebase\JWT\SignatureInvalidException;

// use Restserver\Libraries\REST_Controller;
/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 * 
 */


class E_do extends REST_Controller {

    private $secret = 'password jwt';

    function __construct()
    {
     
        parent::__construct();
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");
    
        if ( "OPTIONS" === $_SERVER['REQUEST_METHOD'] ) {
            die();
        }

        $this->load->model('Crud');
        $this->load->library('ciqrcode');	  

    }
    // function __construct($config = 'rest')
    // {
     
    //     parent::__construct($config);
    //     if (isset($_SERVER['HTTP_ORIGIN'])) {
    //         // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    //         // you want to allow, and if so:
    //         header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    //         header('Access-Control-Allow-Credentials: true');
    //         header('Access-Control-Max-Age: 86400');    // cache for 1 day
    //     }
    
    //     // Access-Control headers are received during OPTIONS requests
    //     if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    
    //         if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    //             // may also be using PUT, PATCH, HEAD etc
    //             header("Access-Control-Allow-Methods: GET, POST, OPTIONS,PUT,DELETE");         
    
    //         if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    //             header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
    //         exit(0);
    //     }

    //     $this->load->model('Crud');
    //     $this->load->library('ciqrcode');	  

    // }




    public function login_post(){
        $date = new DateTime();

        $email = $this->input->post('email');
        $password = $this->input->post('password');
        

        $query = $this->db->query("SELECT * FROM users where email ='".$email."'")->row_array();
        // var_dump($query['password']);
        if($query){
            $hash= $query['password'];

            // var_dump(password_verify($password, $hash));die();
            if(password_verify($password, $hash)){
                $is_valid = true;
            }else{
                $is_valid = false;
            }
    
    
            if($is_valid == false){
                $this->response([
                    'success' => false,
                    'message' => 'wrong email or password'
                ], REST_Controller::HTTP_NOT_FOUND);
    
              
            }else{
                $payload['id'] = $query['user_id'];  
                $payload['email'] = $query['email'];  
                $payload['name'] = $query['name'];  
                $payload['role'] = $query['role'];
                $payload['iat'] = $date->getTimestamp();
                $payload['exp'] = $date->getTimestamp() +60*60*8;

                $output['id_token'] = JWT::encode($payload, $this->secret); 
                $this->response($output);
            }
        }else{
            $this->response([
                'success' => false,
                'message' => 'email not found'
            ], REST_Controller::HTTP_NOT_FOUND);

          
        }
     

        
    }
    public function checktoken_get(){
   
        
        $jwt = $this->input->get_request_header('Authorization');
        try{
            $decoded = JWT::decode($jwt,$this->secret,array('HS256'));
       
            return $decoded;
        }catch( error $e){
           
            $this->response(['success' => false,'message' => 'Expired token'], REST_Controller::HTTP_NOT_FOUND);

        }
       
    }

    // super admin scl dan admin scl getdata and count edo
    public function index_get($id=NULL)
    {
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'|| $role_user->role === 'admin'  || $role_user->role === 'adminspl' || $role_user->role === 'kasir' ){
         
   
        $countEDO= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo from e_do")->row_array();
        $countEDOapproved= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo_approved from e_do where status='Approved'")->row_array();
        $countEDOrejected= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo_rejected from e_do where status='Rejected'")->row_array();
        $countEDOpicked_up= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo_picked_up from e_do where status='Picked Up'")->row_array();

            if($id === NULL){
                $selectEDO = $this->Crud->readData('*','e_do'); 
                
            }else{
                $whereId = [
                    'edo_id'=>$id
                ];
                $selectEDO = $this->Crud->readData('*','e_do',$whereId); 

            }

            if($selectEDO->num_rows() > 0)
            {
                $data= $selectEDO->result_array();
                $this->response(['status'=>'success','total e-DO'=>$countEDO['jumlah_edo'],'total e-DO approved'=>$countEDOapproved['jumlah_edo_approved'],'total e-DO rejected'=>$countEDOrejected['jumlah_edo_rejected'],'total e-DO picked up'=>$countEDOpicked_up['jumlah_edo_picked_up'],'data'=>$data], REST_Controller::HTTP_OK);
            }else{
                $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }  
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }      
    }
    public function total_e_do_get()
    { 
        
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'|| $role_user->role === 'admin' ){
         
   
        $countEDO= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo from e_do")->row_array();
        $countEDOrequested= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo_requested from e_do where status='Requested'")->row_array();
        $countEDOapproved= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo_approved from e_do where status='Approved'")->row_array();
        $countEDOrejected= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo_rejected from e_do where status='Rejected'")->row_array();
        $countEDOpicked_up= $this->db->query("SELECT COUNT(edo_id) as jumlah_edo_picked_up from e_do where status='Picked Up'")->row_array();

 

            if($countEDO){
                    $this->response(['status'=>'success','total'=>$countEDO['jumlah_edo'],'requested'=>$countEDOrequested['jumlah_edo_requested'],'approved'=>$countEDOapproved['jumlah_edo_approved'],'rejected'=>$countEDOrejected['jumlah_edo_rejected'],'picked_up'=>$countEDOpicked_up['jumlah_edo_picked_up']], REST_Controller::HTTP_OK);
            }else{
                    $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }  
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }      
    }

    // admin scl
    public function index_post(){ 
        
   

        $role_user = $this->checktoken_get();

        if($role_user->role === 'admin'){
            $edo_number = $role_user->id.date('Y').date('m').date('d').date('His');

            // var_dump($edo_number);die();
            $data = [
                'edo_number' => $edo_number,
                'shipper_name' => $this->post('shipper_name'),
                'consignee_name' => $this->post('consignee_name'),
                'shipper_address' => $this->post('shipper_address'),
                'consignee_email' => $this->post('consignee_email'),
                'notify' => $this->post('notify'),
                'house_bl_number' => $this->post('house_bl_number'),
                'mbl_number' => $this->post('mbl_number'),
                'pre_carriage' => $this->post('pre_carriage'),
                'place_of_receipt' => $this->post('place_of_receipt'),
                'arrival_date' => $this->post('arrival_date'),
                'ocean_vessel' => $this->post('ocean_vessel'),
                'voyage_number' => $this->post('voyage_number'),
                'container_seal_number' => $this->post('container_seal_number'),
                'port_of_loading' => $this->post('port_of_loading'),
                'port_of_discharges' => $this->post('port_of_discharges'),
                'final_destination' => $this->post('final_destination'),
                'description_of_goods' => $this->post('description_of_goods'),
                'gross_weight' => $this->post('gross_weight'),
                'measurment' => $this->post('measurment'),
                'package' => $this->post('package'),
                'marks_and_number' => $this->post('marks_and_number'),
                'created_at' => date("Y-m-d H:i:s"),
                'created_by' => $role_user->name,
                'status' => 'Requested',
                'barcode_image'=> $edo_number.'.png'
            ];


            $createUser =  $this->Crud->createData('e_do',$data);

            if($createUser){
                // // generate qrcode
                // $config['cacheable']	= true; //boolean, the default is true
                // $config['cachedir']		= './assets/qrcode/'; //string, the default is application/cache/
                $config['imagedir']		= './assets/qrcode/'; //string, the default is application/cache/
                $config['quality']		= true; //boolean, the default is true
                $config['size']			= '1024'; //interger, the default is 1024
                $config['black']		= array(224,255,255); // array, default is array(255,255,255)
                $config['white']		= array(70,130,180); // array, default is array(0,0,0)
                $this->ciqrcode->initialize($config);


                $params['data'] = $edo_number;
                $params['level'] = 'H';
                $params['size'] = 10;
                $params['savename'] = FCPATH.$config['imagedir'].''.$edo_number.'.png';

                $this->ciqrcode->generate($params);

                $this->set_response(['status'=>'Success created e-DO!','data'=>$data], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
            }else{
                $this->response(['status'=>'Failed created e-DO!'], REST_Controller::HTTP_BAD_REQUEST); 
            }

        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }
    }

    // admin scl edit edo
    public function index_put($edo_id){
        
   

        $role_user = $this->checktoken_get();

        if($role_user->role === 'admin'){
            // var_dump($this->put('edo_number'));die();
            
            $whereId = [
                'edo_id'=>$edo_id
            ];
            $selectEDO = $this->Crud->readData('*','e_do',$whereId); 

            $data = [
                'shipper_name' => $this->put('shipper_name'),
                'consignee_name' => $this->put('consignee_name'),
                'shipper_address' => $this->put('shipper_address'),
                'consignee_email' => $this->put('consignee_email'),
                'notify' => $this->put('notify'),
                'house_bl_number' => $this->put('house_bl_number'),
                'mbl_number' => $this->put('mbl_number'),
                'pre_carriage' => $this->put('pre_carriage'),
                'place_of_receipt' => $this->put('place_of_receipt'),
                'arrival_date' => $this->put('arrival_date'),
                'ocean_vessel' => $this->put('ocean_vessel'),
                'voyage_number' => $this->put('voyage_number'),
                'container_seal_number' => $this->put('container_seal_number'),
                'port_of_loading' => $this->put('port_of_loading'),
                'port_of_discharges' => $this->put('port_of_discharges'),
                'final_destination' => $this->put('final_destination'),
                'description_of_goods' => $this->put('description_of_goods'),
                'gross_weight' => $this->put('gross_weight'),
                'measurment' => $this->put('measurment'),
                'package' => $this->put('package'),
                'marks_and_number' => $this->put('marks_and_number'),
                'updated_at' => date("Y-m-d H:i:s"),
            ];

            if($selectEDO->num_rows() > 0)
            {
                $selectstatus= $selectEDO->row_array();
                if($selectstatus['approved_at'] == null && $selectstatus['rejected_at'] == null){
                    // var_dump($edo_number);die();
                    $updateEDO =  $this->Crud->updateData('e_do',$data,$whereId);

                    if($updateEDO){
                        $this->set_response(['status'=>'Success Updated e-DO!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                    }else{
                        $this->response(['status'=>'Failed Updated e-DO!'], REST_Controller::HTTP_BAD_REQUEST); 
                    }
                }else{
                    $this->response(['status'=>'Failed Updated e-DO , e-DO has been approved/ rejected!'], REST_Controller::HTTP_BAD_REQUEST); 
                }
              
        
            }else{
                $this->response(['status'=>'Failed e-DO id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }     

    // admin scl delete edo
    public function index_delete($edo_id){
        
   

        $role_user = $this->checktoken_get();

        if($role_user->role === 'admin'){
          
            $whereId = [
                'edo_id'=>$edo_id
            ];
            $selectEDO = $this->Crud->readData('*','e_do',$whereId); 

            if($selectEDO->num_rows() > 0)
            {
                $deleteEDO =  $this->Crud->deleteData('e_do',$whereId);

                if($deleteEDO){
                    $this->set_response(['status'=>'Success Delete e-DO!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                }else{
                    $this->response(['status'=>'Failed Delete e-DO!'], REST_Controller::HTTP_BAD_REQUEST); 

                }
            }else{
                $this->response(['status'=>'Failed e-DO id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }

// supperadmin scl approve
    public function approve_put($edo_id){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
          
        
        
            $whereId = [
                'edo_id'=>$edo_id
            ];
            $selectEDO = $this->Crud->readData('*','e_do',$whereId); 

            $data = [
            
                'status' => 'Approved',
                'approved_at' => date("Y-m-d H:i:s"),
                'rejected_at' => null,
            ];

            if($selectEDO->num_rows() > 0)
            {
                $selectstatus= $selectEDO->row_array();

                // var_dump($selectstatus['approved_at']);die();

                if($selectstatus['rejected_at'] == null){
                // var_dump($edo_number);die();
                    $updateEDO =  $this->Crud->updateData('e_do',$data,$whereId);

                    if($updateEDO){
                        $this->set_response(['status'=>'Success Approved e-DO!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                    }else{
                        $this->response(['status'=>'Failed Approve e-DO!'], REST_Controller::HTTP_BAD_REQUEST); 
                    }
                }else{
                    $this->response(['status'=>'Failed to Approve! E-do has been rejected!'], REST_Controller::HTTP_BAD_REQUEST); 

                }
            }else{
                $this->response(['status'=>'Failed e-DO id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }     

// superadmin scl rejected
    public function reject_put($edo_id){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
          
        
            $whereId = [
                'edo_id'=>$edo_id
            ];
            $selectEDO = $this->Crud->readData('*','e_do',$whereId); 


            $data = [
            
                'status' => 'Rejected',
                'rejected_at' => date("Y-m-d H:i:s"),
                'approved_at' => null,
            ];

            if($selectEDO->num_rows() > 0)
            {
                $selectstatus= $selectEDO->row_array();

                // var_dump($selectstatus['approved_at']);die();

                if($selectstatus['approved_at'] == null){

                    // var_dump($edo_number);die();
                    $updateEDO =  $this->Crud->updateData('e_do',$data,$whereId);

                    if($updateEDO){
                        $this->set_response(['status'=>'Success Rejected e-DO!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                    }else{
                        $this->response(['status'=>'Failed Rejected e-DO!'], REST_Controller::HTTP_BAD_REQUEST); 
                    }
                }else{
                    $this->response(['status'=>'Failed to reject! E-do has been approved!'], REST_Controller::HTTP_BAD_REQUEST); 

                }
            }else{
                $this->response(['status'=>'Failed e-DO id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }     


    // superadmin scl, admin scl, admin spl dan kasir search by edo number
    public function search_get(){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'|| $role_user->role === 'admin'  || $role_user->role === 'adminspl' || $role_user->role === 'kasir' ){
            
            $edo_number = $this->get('e_do_number');
        
            $whereId = [
                'edo_number'=>$edo_number
            ];
            $selectEDO = $this->Crud->readData('*','e_do',$whereId); 


            if($selectEDO->num_rows() > 0)
            {
                $data= $selectEDO->result_array();
                $this->response(['status'=>'success','data'=>$data], REST_Controller::HTTP_OK);
            }else{
                $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }     
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }

// admin spl picked up
    public function picked_up_post($edo_id){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'adminspl'){
          
            $whereId = [
                'edo_id'=>$edo_id
            ];
            $selectEDO = $this->Crud->readData('*','e_do',$whereId); 


            $data = [
                'picked_up_by' => $role_user->name,
                'status' => 'Picked Up',
                'picked_up_at' => date("Y-m-d H:i:s")
            ];

            if($selectEDO->num_rows() > 0)
            {
                $selectstatus= $selectEDO->row_array();

                // var_dump($selectstatus['approved_at']);die();

                if($selectstatus['approved_at']){

                    // var_dump($edo_number);die();
                    $updateEDO =  $this->Crud->updateData('e_do',$data,$whereId);

                    if($updateEDO){
                        $this->set_response(['status'=>'Success Picked Up e-DO!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                    }else{
                        $this->response(['status'=>'Failed Picked Up e-DO!'], REST_Controller::HTTP_BAD_REQUEST); 
                    }
                }else{
                    $this->response(['status'=>'Failed to picked up! E-do not approved yet!'], REST_Controller::HTTP_BAD_REQUEST); 

                }
            }else{
                $this->response(['status'=>'Failed e-DO id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }
    }



    // endpoint user superadmin scl

    public function users_get($id=NULL){
   

        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
            $selectUser= $this->Crud->readData('*','users');
            $countUser= $this->db->query("SELECT COUNT(user_id) as jumlah_user from users")->row_array();

            if($id === NULL){
                $selectUser = $this->Crud->readData('*','users'); 
                
            }else{
                $whereId = [
                    'user_id'=>$id
                ];
                $selectUser = $this->Crud->readData('*','users',$whereId); 

            }

            if($selectUser->num_rows() > 0)
            {
                $data= $selectUser->result_array();
                $this->response(['status'=>'success','total users'=>$countUser['jumlah_user'],'data'=>$data], REST_Controller::HTTP_OK);
            }else{
                $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }
    }


    // superadmin scl create user
    public function users_post(){
   
        
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
            $email = $this->post('email');
            $whereId = [
                'email'=>$email
            ];
            $selectUser = $this->Crud->readData('*','users',$whereId)->row_array(); 
            // var_dump($selectUser);die();
            if($selectUser){

                $this->response(['status'=>'Failed created user, email already exist!'], REST_Controller::HTTP_BAD_REQUEST); 

            }else{
                $data = [
                    'email' => $email,
                    'name' => $this->post('name'),
                    'role' => $this->post('role'),
                    'password' => password_hash($this->input->post('password'),PASSWORD_DEFAULT),
                    'created_at' => date("Y-m-d H:i:s")
                ];


                $createUser =  $this->Crud->createData('users',$data);

                // var_dump($createUser);die();
                if($createUser){
                    $this->set_response(['status'=>'Success created user','data'=>$data], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                }else{
                    $this->response(['status'=>'Failed created user!'], REST_Controller::HTTP_BAD_REQUEST); 
                }
            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }
//    superadmin scl
    public function users_put($user_id){
   

        // var_dump($this->put('edo_number'));die();
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
            $whereId = [
                'user_id'=>$user_id
            ];
            $selectUser = $this->Crud->readData('*','users',$whereId); 

            $data = [
                'email' => $this->put('email'),
                'name' => $this->put('name'),
                'role' => $this->put('role'),
                'updated_at' => date("Y-m-d H:i:s")
            ];

            if($selectUser->num_rows() > 0)
            {

                // var_dump($edo_number);die();
                $updateUser =  $this->Crud->updateData('users',$data,$whereId);

                if($updateUser){
                    $this->set_response(['status'=>'Success Updated user!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                }else{
                    $this->response(['status'=>'Failed Updated user!'], REST_Controller::HTTP_BAD_REQUEST); 
                }
        
            }else{
                $this->response(['status'=>'Failed user id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }  
    

    //superadmin scl search user by name and email
    public function search_user_get(){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){

            $user_name = $this->get('name');
            $user_email = $this->get('email');
            
            // var_dump(isset($user_name) || isset($user_email));die();
            if(isset($user_name)){
            $selectUser = $this->db->query("SELECT * FROM users WHERE name='".$user_name."'");
            // var_dump($selectUser);die();
            }else if(isset($user_email)){
                $selectUser = $this->db->query("SELECT * FROM users WHERE email='".$user_email."'"); 
            }else{
                $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }



            if($selectUser->num_rows() > 0)
            {
                $data= $selectUser->row_array();
                $this->response(['status'=>'success','data'=>$data], REST_Controller::HTTP_OK);
            }else{
                $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }     
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }

    //superadmin scl delete user
    public function users_delete($user_id){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){

            $whereId = [
                'user_id'=>$user_id
            ];
            $selectUser = $this->Crud->readData('*','users',$whereId); 

            if($selectUser->num_rows() > 0)
            {
                $deleteUser =  $this->Crud->deleteData('users',$whereId);

                if($deleteUser){
                    $this->set_response(['status'=>'Success Delete user!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                }else{
                    $this->response(['status'=>'Failed Delete user!'], REST_Controller::HTTP_BAD_REQUEST); 

                }
            }else{
                $this->response(['status'=>'Failed user id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }

// all user edit passsword
    public function edit_password_post($user_id){

   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'|| $role_user->role === 'admin'  || $role_user->role === 'adminspl' || $role_user->role === 'kasir' ){
            $password= $this->put('password');
            $whereId = [
                'user_id'=>$user_id
            ];
            $selectUser = $this->Crud->readData('*','users',$whereId); 

                $data = [
                    'password' => password_hash($password,PASSWORD_DEFAULT)
                ];
        
                if($selectUser->num_rows() > 0)
                {
                    if($user_id == $role_user->id){
                        // var_dump($edo_number);die();
                        $updateUser =  $this->Crud->updateData('users',$data,$whereId);
                                
                        if($updateUser){
                            $this->set_response(['status'=>'Success Updated password!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                        }else{
                            $this->response(['status'=>'Failed Updated password!'], REST_Controller::HTTP_BAD_REQUEST); 
                        }
                    }else{
                        $this->response(['status'=>'Failed Updated password, user id not match with token!'], REST_Controller::HTTP_BAD_REQUEST); 
                    }
                   
            
                }else{
                    $this->response(['status'=>'Failed user id not found!'], REST_Controller::HTTP_BAD_REQUEST); 
        
                }
       
        }else{
                $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);
    
        }


    }
    // superadmin and admin scl
    public function print_get($edo_id){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'|| $role_user->role === 'admin' ){

        $whereId = [
           'edo_id'=>$edo_id
        ];
        $selectEDO = $this->db->query("SELECT * FROM e_do where edo_id =".$edo_id." AND printed = 0"); 

        // var_dump($selectEDO);die();
        if($selectEDO->num_rows() > 0)
        {
            $dataStatus = [
                'printed' => 1
            ];
            $this->Crud->updateData('e_do',$dataStatus,$whereId);
            $data= $selectEDO->result_array();
            $this->response(['status'=>'success','data'=>$data], REST_Controller::HTTP_OK);
        }else{
            $this->response(['data'=>'Data not found or already print!'], REST_Controller::HTTP_NOT_FOUND);
        }  

        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }
    }
// admin scl send to consignee
    public function send_to_consignee_post(){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'admin' ){

            $toEmail = $this->post('emailReceipt');
            $subject = $this->post('subject');
            $bodyEmail = $this->post('message');

            $config = Array(  
                'protocol' => 'mail',  
                // 'smtp_host' => 'ssl://smtp.gmail.com',  
                'smtp_host' => 'ssl://smtp.googlemail.com',  
                'smtp_port' => 465,  
                'mailtype' => 'html',   
                'charset' => 'iso-8859-1',
                'newline' => '\r\n'
            );  
            $this->load->library('email', $config);  
            $this->email->set_newline("\r\n");  
            $this->email->from('manajanjimanismu12@gmail.com');   
            $this->email->to($toEmail);   
            $this->email->subject($subject);   
            $this->email->message($bodyEmail);  
            if (!$this->email->send()) {  
                $this->response(['status'=>'failed'], REST_Controller::HTTP_NOT_FOUND);
            }else{  
                $this->response(['status'=>'success'], REST_Controller::HTTP_OK);
            } 
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }
    }

// scan barcode adminspl dan kasir
    public function scan_barcode_get(){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'adminspl' || $role_user->role === 'kasir' ){
            
            $edo_number = $this->get('e_do_number');
        
            $whereId = [
                'edo_number'=>$edo_number
            ];
            $selectEDO = $this->Crud->readData('*','e_do',$whereId); 


            if($selectEDO->num_rows() > 0)
            {
                $data= $selectEDO->result_array();
                $this->response(['status'=>'success scan e-DO','data'=>$data], REST_Controller::HTTP_OK);
            }else{
                $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }     
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }
    }


    // consignee

      // endpoint user superadmin scl

      public function consignee_get($id=NULL){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
            $selectUser= $this->Crud->readData('*','consignee');
            // $countUser= $this->db->query("SELECT COUNT(user_id) as jumlah_user from users")->row_array();

            if($id === NULL){
                $selectUser = $this->Crud->readData('*','consignee'); 
                
            }else{
                $whereId = [
                    'id'=>$id
                ];
                $selectUser = $this->Crud->readData('*','consignee',$whereId); 

            }

            if($selectUser->num_rows() > 0)
            {
                $data= $selectUser->result_array();
                $this->response(['status'=>'success','data'=>$data], REST_Controller::HTTP_OK);
            }else{
                $this->response(['data'=>'Data not found'], REST_Controller::HTTP_NOT_FOUND);
            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }
    }


    // superadmin scl create consignee
    public function consignee_post(){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
            $email = $this->post('consignee_email');
            $whereId = [
                'consignee_email'=>$email
            ];
            $selectConsignee = $this->Crud->readData('*','consignee',$whereId)->row_array(); 
            // var_dump($selectConsignee);die();
            if($selectConsignee){

                $this->response(['status'=>'Failed created Cosignee, already exist!'], REST_Controller::HTTP_BAD_REQUEST); 

            }else{
                $data = [
                    'consignee_email' => $email,
                    'consignee_name' => $this->post('consignee_name'),
                    'consignee_address' => $this->post('consignee_address')
                ];


                $createConsignee =  $this->Crud->createData('consignee',$data);

                // var_dump($createUser);die();
                if($createConsignee){
                    $this->set_response(['status'=>'Success created consignee','data'=>$data], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                }else{
                    $this->response(['status'=>'Failed created consignee!'], REST_Controller::HTTP_BAD_REQUEST); 
                }
            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }
//    superadmin scl
    public function consignee_put($id){
   
        // var_dump($this->put('edo_number'));die();
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){
            $whereId = [
                'id'=>$id
            ];
            $selectUser = $this->Crud->readData('*','consignee',$whereId); 

            $data = [
                'consignee_email' => $this->put('consignee_email'),
                'consignee_name' => $this->put('consignee_name'),
                'consignee_address' => $this->put('consignee_address')
            ];

            if($selectUser->num_rows() > 0)
            {

                // var_dump($edo_number);die();
                $updateUser =  $this->Crud->updateData('consignee',$data,$whereId);

                if($updateUser){
                    $this->set_response(['status'=>'Success Updated consignee!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                }else{
                    $this->response(['status'=>'Failed Updated consignee!'], REST_Controller::HTTP_BAD_REQUEST); 
                }
        
            }else{
                $this->response(['status'=>'Failed consignee id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }  
    

    //superadmin scl search user by name and email
   

    //superadmin scl delete user
    public function consignee_delete($id){
   
        $role_user = $this->checktoken_get();

        if($role_user->role === 'superadmin'){

            $whereId = [
                'id'=>$id
            ];
            $selectUser = $this->Crud->readData('*','consignee',$whereId); 

            if($selectUser->num_rows() > 0)
            {
                $deleteUser =  $this->Crud->deleteData('consignee',$whereId);

                if($deleteUser){
                    $this->set_response(['status'=>'Success Delete consignee!'], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
                }else{
                    $this->response(['status'=>'Failed Delete consignee!'], REST_Controller::HTTP_BAD_REQUEST); 

                }
            }else{
                $this->response(['status'=>'Failed consignee id not found!'], REST_Controller::HTTP_BAD_REQUEST); 

            }
        }else{
            $this->response(['status'=>false,'data'=>'Failed token'], REST_Controller::HTTP_NOT_FOUND);

        }

    }

  }
